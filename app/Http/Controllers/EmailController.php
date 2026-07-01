<?php

namespace App\Http\Controllers;

use App\Models\Email;
use App\Models\EmailReplyHistory;
use App\Models\EmailReplyPreference;
use App\Services\AIService;
use App\Services\ClaudeService;
use App\Services\GmailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class EmailController extends Controller
{
    protected GmailService $gmail;

    public function __construct(GmailService $gmail)
    {
        $this->gmail = $gmail;
    }

    public function index(): View
    {
        $emails = Email::where('user_id', Auth::id())
            ->where('label', 'INBOX')
            ->latest('received_at')
            ->paginate(20);

        $unreadCount = Email::where('user_id', Auth::id())
            ->where('label', 'INBOX')
            ->where('is_read', false)
            ->count();

        return view('emails.index', compact('emails', 'unreadCount'));
    }

    public function sent(): View
    {
        $emails = Email::where('user_id', Auth::id())
            ->where('label', 'SENT')
            ->latest('sent_at')
            ->paginate(20);

        return view('emails.index', [
            'emails' => $emails,
            'unreadCount' => 0,
            'mailbox' => 'sent',
        ]);
    }

    public function show(Email $email): View
    {
        if ($email->user_id !== Auth::id()) {
            abort(403);
        }

        if (!$email->is_read) {
            $email->update(['is_read' => true]);
            if ($email->gmail_id) {
                $this->gmail->markAsRead($email->gmail_id);
            }
        }

        return view('emails.show', compact('email'));
    }

    public function create(): View
    {
        return view('emails.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'to' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'reply_to_email_id' => ['nullable', 'exists:emails,id'],
            'ai_suggestion' => ['nullable', 'string'],
        ]);

        $sent = $this->gmail->sendEmail(
            $validated['to'],
            $validated['subject'],
            $validated['body'],
            nl2br(e($validated['body']))
        );

        if (!$sent) {
            $email = Email::create([
                'user_id' => Auth::id(),
                'subject' => $validated['subject'],
                'body_plain' => $validated['body'],
                'to_email' => $validated['to'],
                'label' => 'DRAFT',
                'is_read' => true,
            ]);

            return to_route('emails.index')
                ->with('status', 'Email saved as draft. Check your SMTP settings to send.')
                ->with('status_type', 'warning');
        }

        if ($validated['reply_to_email_id']) {
            $original = Email::find($validated['reply_to_email_id']);
            if ($original && $original->user_id === Auth::id()) {
                $aiSuggestion = $validated['ai_suggestion'] ?? null;
                $wasEdited = $aiSuggestion && $aiSuggestion !== $validated['body'];

                EmailReplyHistory::create([
                    'user_id' => Auth::id(),
                    'email_id' => $original->id,
                    'original_subject' => $original->subject,
                    'original_body' => $original->body_plain ?? $original->body_html ?? '',
                    'reply_body' => $validated['body'],
                    'ai_suggestion' => $aiSuggestion,
                    'was_edited' => $wasEdited,
                ]);
            }
        }

        return to_route('emails.index')
            ->with('status', 'Email sent successfully.')
            ->with('status_type', 'success');
    }

    public function preferences(): View
    {
        $prefs = EmailReplyPreference::firstOrCreate(
            ['user_id' => Auth::id()],
            [
                'tone' => 'professional',
                'language' => 'auto',
                'include_signature' => true,
                'learn_from_replies' => true,
            ]
        );

        return view('emails.preferences', compact('prefs'));
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tone' => ['required', 'string', 'in:professional,friendly,formal,concise,detailed'],
            'language' => ['required', 'string', 'in:auto,en,es,fr,pt'],
            'signature' => ['nullable', 'string', 'max:500'],
            'style_notes' => ['nullable', 'string', 'max:1000'],
            'include_signature' => ['boolean'],
            'learn_from_replies' => ['boolean'],
        ]);

        EmailReplyPreference::updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'tone' => $validated['tone'],
                'language' => $validated['language'],
                'signature' => $validated['signature'],
                'style_notes' => $validated['style_notes'],
                'include_signature' => $request->boolean('include_signature'),
                'learn_from_replies' => $request->boolean('learn_from_replies'),
            ]
        );

        return to_route('emails.preferences')
            ->with('status', 'Preferences saved.')
            ->with('status_type', 'success');
    }

    public function sync(): RedirectResponse
    {
        $count = count($this->gmail->syncInbox());

        return to_route('emails.index')
            ->with('status', "Synced {$count} emails.")
            ->with('status_type', 'success');
    }

    public function triage(): View
    {
        $untriagedCount = Email::where('user_id', Auth::id())
            ->where('label', 'INBOX')
            ->whereNull('triaged_at')
            ->count();

        $highPriority = Email::where('user_id', Auth::id())
            ->where('label', 'INBOX')
            ->where('priority', 'high')
            ->whereNull('triaged_at')
            ->count();

        $needsResponse = Email::where('user_id', Auth::id())
            ->where('label', 'INBOX')
            ->where('needs_response', true)
            ->whereNull('triaged_at')
            ->count();

        $emails = Email::where('user_id', Auth::id())
            ->where('label', 'INBOX')
            ->orderByRaw("CASE WHEN priority IS NULL THEN 0 ELSE 1 END")
            ->orderByRaw("CASE priority WHEN 'high' THEN 0 WHEN 'medium' THEN 1 WHEN 'low' THEN 2 ELSE 3 END")
            ->latest('received_at')
            ->paginate(50);

        return view('emails.triage', compact('emails', 'untriagedCount', 'highPriority', 'needsResponse'));
    }

    public function runTriage(ClaudeService $claude): RedirectResponse
    {
        $emails = Email::where('user_id', Auth::id())
            ->where('label', 'INBOX')
            ->whereNull('triaged_at')
            ->where('is_read', false)
            ->latest('received_at')
            ->take(50)
            ->get();

        if ($emails->isEmpty()) {
            return to_route('emails.triage')
                ->with('status', 'No new emails to triage.')
                ->with('status_type', 'warning');
        }

        if (!$claude->isAvailable()) {
            return to_route('emails.triage')
                ->with('status', 'Claude API key is not configured. Set CLAUDE_API_KEY in your .env file.')
                ->with('status_type', 'error');
        }

        $results = $claude->triageEmails($emails);
        $updated = 0;

        foreach ($results as $result) {
            try {
                $result['email']->update([
                    'summary' => $result['summary'],
                    'priority' => $result['priority'],
                    'needs_response' => $result['needs_response'],
                    'action_items' => $result['action_items'],
                    'triaged_at' => now(),
                    'is_read' => true,
                ]);

                if ($result['email']->gmail_id) {
                    $this->gmail->markAsRead($result['email']->gmail_id);
                }

                $updated++;
            } catch (\Exception $e) {
                Log::error('Failed to update triage result for email ' . $result['email']->id . ': ' . $e->getMessage());
            }
        }

        $skipped = $emails->count() - count($results);

        $message = "Triaged {$updated} emails.";
        if ($skipped > 0) {
            $message .= " {$skipped} could not be processed.";
        }

        return to_route('emails.triage')
            ->with('status', $message)
            ->with('status_type', 'success');
    }

    public function triageMarkRead(Email $email): RedirectResponse
    {
        if ($email->user_id !== Auth::id()) {
            abort(403);
        }

        $email->update(['is_read' => true]);

        if ($email->gmail_id) {
            $this->gmail->markAsRead($email->gmail_id);
        }

        return to_route('emails.triage')
            ->with('status', 'Email marked as read.')
            ->with('status_type', 'success');
    }

    public function suggestReply(Email $email, AIService $ai): JsonResponse
    {
        if ($email->user_id !== Auth::id()) {
            abort(403);
        }

        $raw = $ai->suggestEmailReply(
            subject: $email->subject,
            fromName: $email->from_name ?: $email->from_email,
            body: $email->body_plain ?? $email->body_html ?? '',
            userId: Auth::id(),
            fromEmail: $email->from_email,
        );

        if (!$raw) {
            return response()->json([
                'success' => false,
                'message' => 'AI service is not available. Make sure Ollama is running.',
            ], 503);
        }

        $analysis = '';
        $suggestion = $raw;

        if (preg_match('/ANÁLISIS:\s*(.*?)(?=\nRESPUESTA:)/s', $raw, $m)) {
            $analysis = trim($m[1]);
        }
        if (preg_match('/RESPUESTA:\s*(.*)$/s', $raw, $m)) {
            $suggestion = trim($m[1]);
        }

        return response()->json([
            'success' => true,
            'analysis' => $analysis,
            'suggestion' => $suggestion,
            'raw' => $raw,
        ]);
    }

    public function destroy(Email $email): RedirectResponse
    {
        if ($email->user_id !== Auth::id()) {
            abort(403);
        }

        if ($email->gmail_id) {
            $this->gmail->trashMessage($email->gmail_id);
        }

        $email->delete();

        return to_route('emails.index')
            ->with('status', 'Email deleted.')
            ->with('status_type', 'success');
    }
}
