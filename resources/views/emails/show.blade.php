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
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
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
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
