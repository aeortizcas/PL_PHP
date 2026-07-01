<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Email Triage
            </h2>
            <div class="flex gap-2">
                <form action="{{ route('emails.triage.run') }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Run Triage
                    </button>
                </form>
                <a href="{{ route('emails.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-600 focus:bg-gray-600 active:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Inbox
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 px-4 py-3 rounded-md text-sm
                    {{ session('status_type') === 'error' ? 'bg-red-50 border border-red-200 text-red-700' : '' }}
                    {{ session('status_type') === 'warning' ? 'bg-yellow-50 border border-yellow-200 text-yellow-700' : '' }}
                    {{ session('status_type') === 'success' || !session('status_type') ? 'bg-green-50 border border-green-200 text-green-700' : '' }}">
                    {{ session('status') }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4">
                        <div class="text-xs text-gray-500 uppercase tracking-wider">Untriaged</div>
                        <div class="text-2xl font-bold {{ $untriagedCount > 0 ? 'text-yellow-600' : 'text-gray-900' }}">
                            {{ $untriagedCount }}
                        </div>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4">
                        <div class="text-xs text-gray-500 uppercase tracking-wider">High Priority</div>
                        <div class="text-2xl font-bold {{ $highPriority > 0 ? 'text-red-600' : 'text-gray-900' }}">
                            {{ $highPriority }}
                        </div>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4">
                        <div class="text-xs text-gray-500 uppercase tracking-wider">Needs Response</div>
                        <div class="text-2xl font-bold {{ $needsResponse > 0 ? 'text-orange-600' : 'text-gray-900' }}">
                            {{ $needsResponse }}
                        </div>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4">
                        <div class="text-xs text-gray-500 uppercase tracking-wider">Total Inbox</div>
                        <div class="text-2xl font-bold text-gray-900">{{ $emails->total() }}</div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if ($emails->isEmpty())
                        <p class="text-gray-500 text-center py-8">No emails in your inbox.</p>
                    @else
                        <div class="space-y-4">
                            @php
                                $grouped = $emails->groupBy(function ($e) {
                                    if ($e->priority === 'high') return 'High Priority';
                                    if ($e->priority === 'medium') return 'Medium Priority';
                                    if ($e->priority === 'low') return 'Low Priority';
                                    return 'Untriaged';
                                });
                                $groupOrder = ['High Priority', 'Medium Priority', 'Low Priority', 'Untriaged'];
                            @endphp

                            @foreach ($groupOrder as $group)
                                @if (!isset($grouped[$group]) || $grouped[$group]->isEmpty())
                                    @continue
                                @endif

                                <div>
                                    <h3 class="text-sm font-semibold uppercase tracking-wider mb-2
                                        {{ $group === 'High Priority' ? 'text-red-600' : '' }}
                                        {{ $group === 'Medium Priority' ? 'text-yellow-600' : '' }}
                                        {{ $group === 'Low Priority' ? 'text-green-600' : '' }}
                                        {{ $group === 'Untriaged' ? 'text-gray-400' : '' }}">
                                        {{ $group }}
                                        <span class="text-xs font-normal text-gray-400">({{ $grouped[$group]->count() }})</span>
                                    </h3>
                                    <div class="border rounded-lg overflow-hidden mb-4">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">From</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Summary</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase"></th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200">
                                                @foreach ($grouped[$group] as $email)
                                                    <tr class="hover:bg-gray-50 {{ !$email->is_read ? 'bg-indigo-50/30' : '' }}">
                                                        <td class="px-4 py-3 text-sm {{ !$email->is_read ? 'font-semibold text-gray-900' : 'text-gray-600' }}">
                                                            {{ $email->from_name ?: $email->from_email }}
                                                        </td>
                                                        <td class="px-4 py-3 text-sm max-w-xs truncate">
                                                            <a href="{{ route('emails.show', $email) }}" class="text-indigo-600 hover:text-indigo-900">
                                                                {{ $email->subject }}
                                                            </a>
                                                        </td>
                                                        <td class="px-4 py-3 text-sm text-gray-600 max-w-md">
                                                            @if ($email->summary)
                                                                <span class="text-xs">{{ $email->summary }}</span>
                                                                @if ($email->action_items)
                                                                    <br><span class="text-xs text-amber-600">⚑ {{ $email->action_items }}</span>
                                                                @endif
                                                            @else
                                                                <span class="text-gray-400 italic">not triaged</span>
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-3 text-sm">
                                                            @if ($email->needs_response !== null)
                                                                @if ($email->needs_response)
                                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                                                                        Needs reply
                                                                    </span>
                                                                @else
                                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                                                                        FYI
                                                                    </span>
                                                                @endif
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">
                                                            {{ $email->received_at?->diffForHumans() }}
                                                        </td>
                                                        <td class="px-4 py-3 text-sm text-right whitespace-nowrap">
                                                            <a href="{{ route('emails.show', $email) }}" class="text-indigo-600 hover:text-indigo-900 mr-2 text-xs">View</a>
                                                            @if (!$email->is_read)
                                                                <form action="{{ route('emails.triage.mark-read', $email) }}" method="POST" class="inline">
                                                                    @csrf
                                                                    <button type="submit" class="text-green-600 hover:text-green-900 text-xs">Read</button>
                                                                </form>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4">
                            {{ $emails->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
