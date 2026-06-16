<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-4">
                <a href="{{ route('emails.index') }}" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight truncate max-w-xl">
                    {{ $email->subject }}
                </h2>
            </div>
            <form action="{{ route('emails.destroy', $email) }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700"
                        onclick="return confirm('Delete this email?')">Delete</button>
            </form>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="border-b border-gray-200 pb-4 mb-4">
                        <dl class="grid grid-cols-1 gap-2 text-sm">
                            <div class="flex">
                                <dt class="text-gray-500 w-16 shrink-0">From:</dt>
                                <dd class="text-gray-900">
                                    @if ($email->from_name)
                                        {{ $email->from_name }} &lt;{{ $email->from_email }}&gt;
                                    @else
                                        {{ $email->from_email }}
                                    @endif
                                </dd>
                            </div>
                            <div class="flex">
                                <dt class="text-gray-500 w-16 shrink-0">To:</dt>
                                <dd class="text-gray-900">
                                    @if ($email->to_name)
                                        {{ $email->to_name }} &lt;{{ $email->to_email }}&gt;
                                    @else
                                        {{ $email->to_email }}
                                    @endif
                                </dd>
                            </div>
                            @if ($email->cc)
                                <div class="flex">
                                    <dt class="text-gray-500 w-16 shrink-0">CC:</dt>
                                    <dd class="text-gray-900">{{ is_array($email->cc) ? implode(', ', $email->cc) : $email->cc }}</dd>
                                </div>
                            @endif
                            <div class="flex">
                                <dt class="text-gray-500 w-16 shrink-0">Date:</dt>
                                <dd class="text-gray-900">{{ $email->received_at?->format('M j, Y g:i A') ?? $email->sent_at?->format('M j, Y g:i A') }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="prose prose-sm max-w-none text-gray-800 whitespace-pre-wrap">
                        {{ $email->body_plain ?? 'No plain text content.' }}
                    </div>

                    @if ($email->body_html && !$email->body_plain)
                        <div class="mt-6 border-t border-gray-200 pt-4">
                            <details>
                                <summary class="text-sm text-indigo-600 cursor-pointer hover:text-indigo-900">Show HTML version</summary>
                                <div class="mt-2 p-4 bg-gray-50 rounded">
                                    {!! $email->body_html !!}
                                </div>
                            </details>
                        </div>
                    @endif

                    <div class="mt-6 pt-4 border-t border-gray-200">
                        <button id="suggest-reply-btn"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 disabled:opacity-50"
                                onclick="suggestReply({{ $email->id }})">
                            <svg id="spinner" class="hidden animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Suggest Reply with AI
                        </button>
                    </div>
                </div>
            </div>

            <div id="suggestion-card" class="hidden bg-white overflow-hidden shadow-sm sm:rounded-lg border border-indigo-200">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="text-sm font-semibold text-indigo-800 uppercase tracking-widest">AI Suggested Reply</h3>
                        <div class="flex gap-2">
                            <button id="edit-btn" onclick="toggleEdit()"
                                    class="inline-flex items-center px-3 py-1.5 bg-yellow-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-600">
                                Edit
                            </button>
                            <a id="use-reply-link"
                               class="inline-flex items-center px-3 py-1.5 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                                Use Reply
                            </a>
                            <button onclick="document.getElementById('suggestion-card').classList.add('hidden')"
                                    class="inline-flex items-center px-3 py-1.5 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300">
                                Dismiss
                            </button>
                        </div>
                    </div>

                    <div id="analysis-box" class="hidden mb-4 p-3 bg-gray-50 rounded border border-gray-200 text-sm text-gray-700"></div>

                    <div id="suggestion-text" class="prose prose-sm max-w-none text-gray-800 whitespace-pre-wrap"></div>

                    <div id="edit-area" class="hidden">
                        <textarea id="suggestion-edit" rows="8"
                                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"></textarea>
                        <div class="flex justify-end gap-2 mt-2">
                            <button onclick="toggleEdit()"
                                    class="inline-flex items-center px-3 py-1.5 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300">
                                Cancel
                            </button>
                            <button onclick="saveEdit()"
                                    class="inline-flex items-center px-3 py-1.5 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                Save
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentSuggestion = '';

        function suggestReply(emailId) {
            const btn = document.getElementById('suggest-reply-btn');
            const spinner = document.getElementById('spinner');
            const card = document.getElementById('suggestion-card');
            const text = document.getElementById('suggestion-text');
            const link = document.getElementById('use-reply-link');
            const analysis = document.getElementById('analysis-box');

            const editArea = document.getElementById('edit-area');
            const suggestText = document.getElementById('suggestion-text');
            document.getElementById('edit-btn').textContent = 'Edit';
            editArea.classList.add('hidden');
            suggestText.classList.remove('hidden');

            btn.disabled = true;
            spinner.classList.remove('hidden');
            btn.textContent = 'Generating...';

            fetch(`/emails/${emailId}/suggest-reply`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    currentSuggestion = data.suggestion;
                    text.textContent = data.suggestion;

                    if (data.analysis) {
                        analysis.textContent = 'Analysis: ' + data.analysis;
                        analysis.classList.remove('hidden');
                    } else {
                        analysis.classList.add('hidden');
                    }

                    const origSuggestion = data.suggestion;
                    link.href = `{{ route('emails.create') }}?to=${encodeURIComponent('{{ $email->from_email }}')}&subject=${encodeURIComponent('Re: {{ $email->subject }}')}&body=${encodeURIComponent(data.suggestion)}&reply_to_email_id={{ $email->id }}&ai_suggestion=${encodeURIComponent(origSuggestion)}`;
                    card.classList.remove('hidden');
                } else {
                    alert(data.message || 'Failed to generate suggestion.');
                }
            })
            .catch(err => {
                alert('Error connecting to AI service. Make sure Ollama is running.');
                console.error(err);
            })
            .finally(() => {
                btn.disabled = false;
                spinner.classList.add('hidden');
                btn.textContent = 'Suggest Reply with AI';
            });
        }

        function toggleEdit() {
            const editArea = document.getElementById('edit-area');
            const suggestText = document.getElementById('suggestion-text');
            const editBox = document.getElementById('suggestion-edit');
            const btn = document.getElementById('edit-btn');

            if (editArea.classList.contains('hidden')) {
                editBox.value = currentSuggestion;
                suggestText.classList.add('hidden');
                editArea.classList.remove('hidden');
                btn.textContent = 'Preview';
            } else {
                currentSuggestion = editBox.value;
                suggestText.textContent = currentSuggestion;
                suggestText.classList.remove('hidden');
                editArea.classList.add('hidden');
                updateUseReplyLink(currentSuggestion);
                btn.textContent = 'Edit';
            }
        }

        function saveEdit() {
            const editBox = document.getElementById('suggestion-edit');
            currentSuggestion = editBox.value;
            document.getElementById('suggestion-text').textContent = currentSuggestion;
            document.getElementById('suggestion-text').classList.remove('hidden');
            document.getElementById('edit-area').classList.add('hidden');
            document.getElementById('edit-btn').textContent = 'Edit';
            updateUseReplyLink(currentSuggestion);
        }

        function updateUseReplyLink(body) {
            const link = document.getElementById('use-reply-link');
            const aiSugg = encodeURIComponent(currentSuggestion);
            link.href = `{{ route('emails.create') }}?to=${encodeURIComponent('{{ $email->from_email }}')}&subject=${encodeURIComponent('Re: {{ $email->subject }}')}&body=${encodeURIComponent(body)}&reply_to_email_id={{ $email->id }}&ai_suggestion=${aiSugg}`;
        }
    </script>
</x-app-layout>
