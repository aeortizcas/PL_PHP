<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Log Call') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('call-logs.store') }}" method="POST" class="space-y-6">
                        @csrf

                        <div>
                            <x-input-label for="contact_id" :value="__('Contact (optional)')" />
                            <select id="contact_id" name="contact_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">None</option>
                                @foreach ($contacts as $contact)
                                    <option value="{{ $contact->id }}" {{ old('contact_id') == $contact->id ? 'selected' : '' }}>
                                        {{ $contact->name }} ({{ $contact->phone }})
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('contact_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="caller_number" :value="__('Phone Number')" />
                            <x-text-input id="caller_number" name="caller_number" type="text" class="mt-1 block w-full" :value="old('caller_number')" required placeholder="+1234567890" />
                            <x-input-error :messages="$errors->get('caller_number')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="direction" :value="__('Direction')" />
                                <select id="direction" name="direction" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    <option value="outbound" {{ old('direction') === 'outbound' ? 'selected' : '' }}>Outbound</option>
                                    <option value="inbound" {{ old('direction') === 'inbound' ? 'selected' : '' }}>Inbound</option>
                                </select>
                                <x-input-error :messages="$errors->get('direction')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="disposition" :value="__('Disposition')" />
                                <select id="disposition" name="disposition" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">Select...</option>
                                    <option value="answered" {{ old('disposition') === 'answered' ? 'selected' : '' }}>Answered</option>
                                    <option value="no_answer" {{ old('disposition') === 'no_answer' ? 'selected' : '' }}>No Answer</option>
                                    <option value="busy" {{ old('disposition') === 'busy' ? 'selected' : '' }}>Busy</option>
                                    <option value="failed" {{ old('disposition') === 'failed' ? 'selected' : '' }}>Failed</option>
                                    <option value="voicemail" {{ old('disposition') === 'voicemail' ? 'selected' : '' }}>Voicemail</option>
                                    <option value="callback" {{ old('disposition') === 'callback' ? 'selected' : '' }}>Callback</option>
                                    <option value="sold" {{ old('disposition') === 'sold' ? 'selected' : '' }}>Sold</option>
                                    <option value="not_interested" {{ old('disposition') === 'not_interested' ? 'selected' : '' }}>Not Interested</option>
                                </select>
                                <x-input-error :messages="$errors->get('disposition')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="called_at" :value="__('Date & Time')" />
                                <x-text-input id="called_at" name="called_at" type="datetime-local" class="mt-1 block w-full" :value="old('called_at', now()->format('Y-m-d\TH:i'))" required />
                                <x-input-error :messages="$errors->get('called_at')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="duration" :value="__('Duration (seconds)')" />
                                <x-text-input id="duration" name="duration" type="number" class="mt-1 block w-full" :value="old('duration')" min="0" placeholder="0" />
                                <x-input-error :messages="$errors->get('duration')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="notes" :value="__('Notes')" />
                            <textarea id="notes" name="notes" rows="4" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('notes') }}</textarea>
                            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Save Call Log') }}</x-primary-button>
                            <a href="{{ route('call-logs.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
