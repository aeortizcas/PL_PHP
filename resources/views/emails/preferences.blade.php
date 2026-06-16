<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('emails.index') }}" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">AI Reply Preferences</h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 p-4 rounded-md text-sm {{ session('status_type') === 'success' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('emails.preferences.update') }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label for="tone" class="block text-sm font-medium text-gray-700 mb-1">Tone</label>
                            <select name="tone" id="tone" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="professional" @selected($prefs->tone === 'professional')>Professional</option>
                                <option value="friendly" @selected($prefs->tone === 'friendly')>Friendly</option>
                                <option value="formal" @selected($prefs->tone === 'formal')>Formal</option>
                                <option value="concise" @selected($prefs->tone === 'concise')>Concise</option>
                                <option value="detailed" @selected($prefs->tone === 'detailed')>Detailed</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="language" class="block text-sm font-medium text-gray-700 mb-1">Language</label>
                            <select name="language" id="language" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="auto" @selected($prefs->language === 'auto')>Auto (match incoming email)</option>
                                <option value="en" @selected($prefs->language === 'en')>English</option>
                                <option value="es" @selected($prefs->language === 'es')>Español</option>
                                <option value="fr" @selected($prefs->language === 'fr')>Français</option>
                                <option value="pt" @selected($prefs->language === 'pt')>Português</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="style_notes" class="block text-sm font-medium text-gray-700 mb-1">Style Notes</label>
                            <textarea name="style_notes" id="style_notes" rows="3"
                                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                      placeholder="e.g. Be direct, avoid jargon, always thank the customer...">{{ old('style_notes', $prefs->style_notes) }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">Instructions the AI will follow when writing replies.</p>
                        </div>

                        <div class="mb-4">
                            <label for="signature" class="block text-sm font-medium text-gray-700 mb-1">Signature</label>
                            <textarea name="signature" id="signature" rows="2"
                                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                      placeholder="e.g. Best regards, John">{{ old('signature', $prefs->signature) }}</textarea>
                        </div>

                        <div class="mb-4 space-y-2">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="include_signature" value="1" @checked($prefs->include_signature) class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="text-sm text-gray-700">Include signature in replies</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="learn_from_replies" value="1" @checked($prefs->learn_from_replies) class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="text-sm text-gray-700">Learn from my past replies to match my style</span>
                            </label>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                Save Preferences
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
