<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ isset($mailbox) && $mailbox === 'sent' ? 'Sent Emails' : 'Inbox' }}
                @if (!isset($mailbox) && $unreadCount > 0)
                    <span class="text-sm font-normal text-gray-500">({{ $unreadCount }} unread)</span>
                @endif
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('emails.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Compose
                </a>
                @if (!isset($mailbox) || $mailbox !== 'sent')
                    <form action="{{ route('emails.sync') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Sync
                        </button>
                    </form>
                @endif
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

            @if (!isset($mailbox) || $mailbox !== 'sent')
                <div class="mb-4 flex gap-4">
                    <a href="{{ route('emails.index') }}"
                       class="text-sm font-medium {{ !isset($mailbox) ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700' }}">
                        Inbox
                    </a>
                    <a href="{{ route('emails.sent') }}"
                       class="text-sm font-medium {{ isset($mailbox) && $mailbox === 'sent' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700' }}">
                        Sent
                    </a>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if ($emails->isEmpty())
                        <p class="text-gray-500 text-center py-8">
                            @if (!isset($mailbox) || $mailbox !== 'sent')
                                No emails in your inbox.
                                <form action="{{ route('emails.sync') }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-indigo-600 hover:text-indigo-900 underline">Sync now</button>
                                </form>
                            @else
                                No sent emails yet.
                                <a href="{{ route('emails.create') }}" class="text-indigo-600 hover:text-indigo-900 underline">Compose one</a>.
                            @endif
                        </p>
                    @else
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                        {{ isset($mailbox) && $mailbox === 'sent' ? 'To' : 'From' }}
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($emails as $email)
                                    <tr class="hover:bg-gray-50 {{ !$email->is_read ? 'font-semibold bg-indigo-50/50' : '' }}">
                                        <td class="px-4 py-3 text-sm {{ !$email->is_read ? 'text-gray-900' : 'text-gray-600' }}">
                                            {{ isset($mailbox) && $mailbox === 'sent' ? $email->to_email : ($email->from_name ?: $email->from_email) }}
                                        </td>
                                        <td class="px-4 py-3 text-sm {{ !$email->is_read ? 'text-gray-900' : 'text-gray-600' }}">
                                            <a href="{{ route('emails.show', $email) }}" class="hover:text-indigo-600">
                                                {{ $email->subject }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">
                                            {{ $email->received_at?->diffForHumans() ?? $email->sent_at?->diffForHumans() }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right">
                                            <a href="{{ route('emails.show', $email) }}"
                                               class="text-indigo-600 hover:text-indigo-900 mr-3">View</a>
                                            <form action="{{ route('emails.destroy', $email) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900"
                                                        onclick="return confirm('Are you sure?')">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="mt-4">
                            {{ $emails->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
