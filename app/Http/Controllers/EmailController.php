<?php

namespace App\Http\Controllers;

use App\Models\Email;
use App\Services\GmailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        ]);

        $sent = $this->gmail->sendEmail(
            $validated['to'],
            $validated['subject'],
            $validated['body'],
            nl2br(e($validated['body']))
        );

        if (!$sent) {
            Email::create([
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

        return to_route('emails.index')
            ->with('status', 'Email sent successfully.')
            ->with('status_type', 'success');
    }

    public function sync(): RedirectResponse
    {
        $count = count($this->gmail->syncInbox());

        return to_route('emails.index')
            ->with('status', "Synced {$count} emails.")
            ->with('status_type', 'success');
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
