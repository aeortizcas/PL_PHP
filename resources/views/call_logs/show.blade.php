<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Call Details
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('call-logs.edit', $callLog) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Edit
                </a>
                <a href="https://st1-web2-dal.spectrumvoip.com/webphone/?call={{ urlencode($callLog->caller_number) }}"
                   target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Call {{ $callLog->caller_number }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Contact</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if ($callLog->contact)
                                    <a href="{{ route('contacts.show', $callLog->contact) }}" class="text-indigo-600 hover:text-indigo-900">
                                        {{ $callLog->contact->name }}
                                    </a>
                                @else
                                    <span class="text-gray-400">Unknown</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Phone Number</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <a href="https://st1-web2-dal.spectrumvoip.com/webphone/?call={{ urlencode($callLog->caller_number) }}"
                                   target="_blank" rel="noopener noreferrer"
                                   class="text-indigo-600 hover:text-indigo-900">{{ $callLog->caller_number }}</a>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Direction</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if ($callLog->direction === 'outbound')
                                    <span class="text-green-600">Outbound</span>
                                @else
                                    <span class="text-blue-600">Inbound</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Duration</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $callLog->duration ? $callLog->duration_formatted : '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Disposition</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if ($callLog->disposition)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                        {{ in_array($callLog->disposition, ['sold', 'answered']) ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $callLog->disposition === 'callback' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        {{ in_array($callLog->disposition, ['no_answer', 'busy', 'failed']) ? 'bg-red-100 text-red-800' : '' }}
                                    ">
                                        {{ str_replace('_', ' ', ucfirst($callLog->disposition)) }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Called At</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $callLog->called_at->format('F j, Y g:i A') }}</dd>
                        </div>
                    </dl>
                    @if ($callLog->notes)
                        <div class="mt-4">
                            <dt class="text-sm font-medium text-gray-500">Notes</dt>
                            <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ $callLog->notes }}</dd>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
