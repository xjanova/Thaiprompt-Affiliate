@extends('layouts.admin-v3')

@section('title', 'Chat Widget Settings')

@section('content')
<div class="container-fluid px-4 py-6">
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-cyan-500 via-blue-600 to-indigo-700 p-8 shadow-2xl">
        <div class="relative">
            <h1 class="text-3xl font-bold text-white mb-1">Chat Widget Settings</h1>
            <p class="text-cyan-100">Configure floating chat widget for your website</p>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-green-50 border border-green-200">
            <p class="text-green-800"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.line-bot.chat-widget.update') }}">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="glass-fusion rounded-2xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                    <h3 class="text-lg font-bold text-gray-900 mb-4">General Settings</h3>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-bold text-gray-900">Enable Chat Widget</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Show widget on your website</p>
                            </div>
                            <input type="checkbox" name="is_enabled" value="1" {{ old('is_enabled', $settings->is_enabled ?? false) ? 'checked' : '' }}
                                   class="w-12 h-6 rounded-full">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Widget Title</label>
                            <input type="text" name="widget_title" value="{{ old('widget_title', $settings->widget_title ?? 'Chat with us') }}"
                                class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Welcome Message</label>
                            <textarea name="welcome_message" rows="3"
                                class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl">{{ old('welcome_message', $settings->welcome_message ?? 'Hello! How can we help?') }}</textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Position</label>
                            <select name="position" class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl">
                                <option value="bottom-right" {{ old('position', $settings->position ?? 'bottom-right') === 'bottom-right' ? 'selected' : '' }}>Bottom Right</option>
                                <option value="bottom-left" {{ old('position', $settings->position ?? '') === 'bottom-left' ? 'selected' : '' }}>Bottom Left</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Primary Color</label>
                            <input type="color" name="primary_color" value="{{ old('primary_color', $settings->primary_color ?? '#06B6D4') }}"
                                class="w-full h-12 border border-gray-200 dark:border-gray-700 rounded-xl">
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="bg-gradient-to-br from-cyan-50 to-blue-50 rounded-2xl border border-cyan-200 p-6 sticky top-6">
                    <h3 class="font-bold text-cyan-900 mb-4">Preview</h3>
                    <div class="glass-fusion rounded-xl p-4 shadow-lg" border border-white/20 dark:border-white/10>
                        <p class="text-sm text-gray-600 dark:text-gray-400 text-center">Widget preview will appear here</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-8 glass-fusion rounded-2xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex justify-end">
                <button type="submit" class="px-8 py-3 bg-gradient-to-r from-cyan-600 to-blue-600 text-white rounded-xl hover:from-cyan-700 hover:to-blue-700 transition shadow-lg font-bold">
                    <i class="fas fa-save mr-2"></i>Save Settings
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
