<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(): View
    {
        $contacts = Contact::where('user_id', Auth::id())
            ->latest()
            ->paginate(20);

        return view('contacts.index', compact('contacts'));
    }

    public function create(): View
    {
        return view('contacts.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['user_id'] = Auth::id();

        Contact::create($validated);

        return to_route('contacts.index')
            ->with('status', 'Contact created successfully.');
    }

    public function show(Contact $contact): View
    {
        $this->authorizeAccess($contact);

        $callLogs = $contact->callLogs()
            ->where('user_id', Auth::id())
            ->latest('called_at')
            ->get();

        return view('contacts.show', compact('contact', 'callLogs'));
    }

    public function edit(Contact $contact): View
    {
        $this->authorizeAccess($contact);

        return view('contacts.edit', compact('contact'));
    }

    public function update(Request $request, Contact $contact): RedirectResponse
    {
        $this->authorizeAccess($contact);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $contact->update($validated);

        return to_route('contacts.index')
            ->with('status', 'Contact updated successfully.');
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        $this->authorizeAccess($contact);

        $contact->delete();

        return to_route('contacts.index')
            ->with('status', 'Contact deleted successfully.');
    }

    private function authorizeAccess(Contact $contact): void
    {
        if ($contact->user_id !== Auth::id()) {
            abort(403);
        }
    }
}
