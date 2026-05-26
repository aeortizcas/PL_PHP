<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Contacts') }}
            </h2>
            <a href="{{ route('contacts.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Add Contact
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if ($contacts->isEmpty())
                        <p class="text-gray-500">No contacts yet. <a href="{{ route('contacts.create') }}" class="text-indigo-600 hover:text-indigo-900">Add your first contact</a>.</p>
                    @else
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Company</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($contacts as $contact)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                            <a href="{{ route('contacts.show', $contact) }}" class="text-indigo-600 hover:text-indigo-900">
                                                {{ $contact->name }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            <a href="https://st1-web2-dal.spectrumvoip.com/webphone/?call={{ urlencode($contact->phone) }}"
                                               target="_blank" rel="noopener noreferrer"
                                               class="text-indigo-600 hover:text-indigo-900">
                                                {{ $contact->phone }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ $contact->email ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ $contact->company ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            <a href="{{ route('contacts.edit', $contact) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                                            <form action="{{ route('contacts.destroy', $contact) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure?')">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="mt-4">
                            {{ $contacts->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
