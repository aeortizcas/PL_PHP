<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $contact->name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('contacts.edit', $contact) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Edit
                </a>
                <a href="https://st1-web2-dal.spectrumvoip.com/webphone/?call={{ urlencode($contact->phone) }}"
                   target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Call {{ $contact->phone }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Phone</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <a href="https://st1-web2-dal.spectrumvoip.com/webphone/?call={{ urlencode($contact->phone) }}"
                                   target="_blank" rel="noopener noreferrer"
                                   class="text-indigo-600 hover:text-indigo-900">{{ $contact->phone }}</a>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Email</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $contact->email ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Company</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $contact->company ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Added</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $contact->created_at->format('M j, Y') }}</dd>
                        </div>
                    </dl>
                    @if ($contact->notes)
                        <div class="mt-4">
                            <dt class="text-sm font-medium text-gray-500">Notes</dt>
                            <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ $contact->notes }}</dd>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold text-lg text-gray-800 mb-4">Call History</h3>

                    @if ($callLogs->isEmpty())
                        <p class="text-gray-500">No calls logged for this contact.</p>
                    @else
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Direction</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Number</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Disposition</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($callLogs as $log)
                                    <tr>
                                        <td class="px-4 py-2 text-sm">
                                            @if ($log->direction === 'outbound')
                                                <span class="text-green-600">Outbound</span>
                                            @else
                                                <span class="text-blue-600">Inbound</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-600">
                                            <a href="https://st1-web2-dal.spectrumvoip.com/webphone/?call={{ urlencode($log->caller_number) }}"
                                               target="_blank" rel="noopener noreferrer"
                                               class="text-indigo-600 hover:text-indigo-900">{{ $log->caller_number }}</a>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-600">{{ $log->duration ? $log->duration_formatted : '-' }}</td>
                                        <td class="px-4 py-2 text-sm">
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
                                        <td class="px-4 py-2 text-sm text-gray-500">{{ $log->called_at->format('M j, g:i A') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
