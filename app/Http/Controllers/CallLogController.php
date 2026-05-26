<?php

namespace App\Http\Controllers;

use App\Models\CallLog;
use App\Models\Contact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CallLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = CallLog::with('contact')
            ->where('user_id', Auth::id());

        if ($request->filled('direction')) {
            $query->where('direction', $request->direction);
        }

        if ($request->filled('disposition')) {
            $query->where('disposition', $request->disposition);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('caller_number', 'like', "%{$search}%")
                  ->orWhereHas('contact', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $callLogs = $query->latest('called_at')->paginate(20);

        return view('call_logs.index', compact('callLogs'));
    }

    public function create(): View
    {
        $contacts = Contact::where('user_id', Auth::id())
            ->orderBy('name')
            ->get();

        return view('call_logs.create', compact('contacts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'caller_number' => ['required', 'string', 'max:20'],
            'direction' => ['required', 'in:inbound,outbound'],
            'duration' => ['nullable', 'integer', 'min:0'],
            'disposition' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'called_at' => ['required', 'date'],
        ]);

        $validated['user_id'] = Auth::id();

        CallLog::create($validated);

        return to_route('call-logs.index')
            ->with('status', 'Call logged successfully.');
    }

    public function show(CallLog $callLog): View
    {
        $this->authorizeAccess($callLog);

        $callLog->load('contact');

        return view('call_logs.show', compact('callLog'));
    }

    public function edit(CallLog $callLog): View
    {
        $this->authorizeAccess($callLog);

        $contacts = Contact::where('user_id', Auth::id())
            ->orderBy('name')
            ->get();

        return view('call_logs.edit', compact('callLog', 'contacts'));
    }

    public function update(Request $request, CallLog $callLog): RedirectResponse
    {
        $this->authorizeAccess($callLog);

        $validated = $request->validate([
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'caller_number' => ['required', 'string', 'max:20'],
            'direction' => ['required', 'in:inbound,outbound'],
            'duration' => ['nullable', 'integer', 'min:0'],
            'disposition' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'called_at' => ['required', 'date'],
        ]);

        $callLog->update($validated);

        return to_route('call-logs.index')
            ->with('status', 'Call log updated successfully.');
    }

    public function destroy(CallLog $callLog): RedirectResponse
    {
        $this->authorizeAccess($callLog);

        $callLog->delete();

        return to_route('call-logs.index')
            ->with('status', 'Call log deleted successfully.');
    }

    private function authorizeAccess(CallLog $callLog): void
    {
        if ($callLog->user_id !== Auth::id()) {
            abort(403);
        }
    }
}
