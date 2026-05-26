<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm text-gray-500">Contacts</div>
                        <div class="text-3xl font-bold text-gray-900">{{ $contactsCount }}</div>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm text-gray-500">Calls Today</div>
                        <div class="text-3xl font-bold text-gray-900">{{ $callsToday }}</div>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm text-gray-500">Outbound Today</div>
                        <div class="text-3xl font-bold text-gray-900">{{ $outboundToday }}</div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold text-lg text-gray-800 mb-4">Recent Calls</h3>

                    @if ($recentCalls->isEmpty())
                        <p class="text-gray-500">No calls logged yet.</p>
                    @else
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Number</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Direction</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Disposition</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($recentCalls as $call)
                                    <tr>
                                        <td class="px-4 py-2 text-sm">
                                            @if ($call->contact)
                                                <a href="{{ route('contacts.show', $call->contact) }}" class="text-indigo-600 hover:text-indigo-900">
                                                    {{ $call->contact->name }}
                                                </a>
                                            @else
                                                <span class="text-gray-400">Unknown</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-sm">
                                            <a href="https://st1-web2-dal.spectrumvoip.com/webphone/?call={{ urlencode($call->caller_number) }}"
                                               target="_blank" rel="noopener noreferrer"
                                               class="text-indigo-600 hover:text-indigo-900">
                                                {{ $call->caller_number }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-2 text-sm">
                                            @if ($call->direction === 'outbound')
                                                <span class="text-green-600">Outbound</span>
                                            @else
                                                <span class="text-blue-600">Inbound</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-600">
                                            {{ $call->duration ? $call->duration_formatted : '-' }}
                                        </td>
                                        <td class="px-4 py-2 text-sm">
                                            @if ($call->disposition)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                    {{ in_array($call->disposition, ['sold', 'answered']) ? 'bg-green-100 text-green-800' : '' }}
                                                    {{ in_array($call->disposition, ['callback']) ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                    {{ in_array($call->disposition, ['no_answer', 'busy', 'failed']) ? 'bg-red-100 text-red-800' : '' }}
                                                ">
                                                    {{ str_replace('_', ' ', ucfirst($call->disposition)) }}
                                                </span>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-500">
                                            {{ $call->called_at->format('M j, g:i A') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="mt-4">
                            <a href="{{ route('call-logs.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">View all calls &rarr;</a>
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold text-lg text-gray-800 mb-4">Spectrum Webphone</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Open the Spectrum VOIP webphone to make and receive calls.
                    </p>
                    <a href="https://st1-web2-dal.spectrumvoip.com/webphone/agent-center"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Open Webphone
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
