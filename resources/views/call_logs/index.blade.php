<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Call Log') }}
            </h2>
            <a href="{{ route('call-logs.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Log Call
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-md text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-4">
                    <form method="GET" action="{{ route('call-logs.index') }}" class="flex flex-wrap gap-4 items-end">
                        <div>
                            <x-input-label for="search" :value="__('Search')" />
                            <x-text-input id="search" name="search" type="text" class="mt-1 block" :value="request('search')" placeholder="Name or number..." />
                        </div>
                        <div>
                            <x-input-label for="direction" :value="__('Direction')" />
                            <select id="direction" name="direction" class="mt-1 block border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">All</option>
                                <option value="outbound" {{ request('direction') === 'outbound' ? 'selected' : '' }}>Outbound</option>
                                <option value="inbound" {{ request('direction') === 'inbound' ? 'selected' : '' }}>Inbound</option>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="disposition" :value="__('Disposition')" />
                            <select id="disposition" name="disposition" class="mt-1 block border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">All</option>
                                <option value="answered" {{ request('disposition') === 'answered' ? 'selected' : '' }}>Answered</option>
                                <option value="no_answer" {{ request('disposition') === 'no_answer' ? 'selected' : '' }}>No Answer</option>
                                <option value="busy" {{ request('disposition') === 'busy' ? 'selected' : '' }}>Busy</option>
                                <option value="callback" {{ request('disposition') === 'callback' ? 'selected' : '' }}>Callback</option>
                                <option value="sold" {{ request('disposition') === 'sold' ? 'selected' : '' }}>Sold</option>
                                <option value="not_interested" {{ request('disposition') === 'not_interested' ? 'selected' : '' }}>Not Interested</option>
                            </select>
                        </div>
                        <div>
                            <x-primary-button>{{ __('Filter') }}</x-primary-button>
                        </div>
                        @if (request()->anyFilled(['search', 'direction', 'disposition']))
                            <div>
                                <a href="{{ route('call-logs.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Clear filters</a>
                            </div>
                        @endif
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if ($callLogs->isEmpty())
                        <p class="text-gray-500">No calls logged yet.</p>
                    @else
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Number</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Direction</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Disposition</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Called At</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($callLogs as $log)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm">
                                            @if ($log->contact)
                                                <a href="{{ route('contacts.show', $log->contact) }}" class="text-indigo-600 hover:text-indigo-900">
                                                    {{ $log->contact->name }}
                                                </a>
                                            @else
                                                <span class="text-gray-400">Unknown</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            <a href="https://st1-web2-dal.spectrumvoip.com/webphone/?call={{ urlencode($log->caller_number) }}"
                                               target="_blank" rel="noopener noreferrer"
                                               class="text-indigo-600 hover:text-indigo-900">{{ $log->caller_number }}</a>
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            @if ($log->direction === 'outbound')
                                                <span class="text-green-600">Outbound</span>
                                            @else
                                                <span class="text-blue-600">Inbound</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ $log->duration ? $log->duration_formatted : '-' }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            @if ($log->disposition)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                    {{ in_array($log->disposition, ['sold', 'answered']) ? 'bg-green-100 text-green-800' : '' }}
                                                    {{ $log->disposition === 'callback' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                    {{ in_array($log->disposition, ['no_answer', 'busy', 'failed']) ? 'bg-red-100 text-red-800' : '' }}
                                                ">
                                                    {{ str_replace('_', ' ', ucfirst($log->disposition)) }}
                                                </span>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500">{{ $log->called_at->format('M j, g:i A') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="mt-4">
                            {{ $callLogs->withQueryString()->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
