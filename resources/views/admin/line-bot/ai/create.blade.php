@extends('layouts.admin')

@section('title', isset($aiSetting) ? 'Edit AI Setting' : 'Create AI Setting')

@section('content')
<div class="container-fluid px-4 py-6" x-data="{
    provider: '{{ old('provider', $aiSetting->provider ?? 'openai') }}',
    isActive: {{ old('is_active', $aiSetting->is_active ?? false) ? 'true' : 'false' }}
}">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center gap-3 mb-2">
            <a href="{{ route('admin.line-bot.ai.index') }}" class="text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    @isset($aiSetting)
                        Edit AI Setting
                    @else
                        Create New AI Setting
                    @endisset
                </h1>
                <p class="text-sm text-gray-600">Configure AI provider for intelligent chat responses</p>
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-6 p-4 rounded-xl bg-gradient-to-r from-red-50 to-pink-50 border border-red-200">
            <div class="flex items-start">
                <div class="w-10 h-10 rounded-full bg-red-500 flex items-center justify-center mr-3 flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-white"></i>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-red-900 mb-2">Please fix the following errors:</p>
                    <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ isset($aiSetting) ? route('admin.line-bot.ai.update', $aiSetting->id) : route('admin.line-bot.ai.store') }}">
        @csrf
        @isset($aiSetting)
            @method('PUT')
        @endisset

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Form (Left - 2 columns) -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Basic Information -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-500 to-indigo-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white flex items-center">
                            <i class="fas fa-info-circle mr-2"></i>
                            Basic Information
                        </h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-tag text-purple-500 mr-1"></i> Setting Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" value="{{ old('name', $aiSetting->name ?? '') }}" required
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all"
                                placeholder="My ChatGPT Configuration">
                            <p class="text-xs text-gray-500 mt-1">A friendly name to identify this configuration</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-server text-purple-500 mr-1"></i> AI Provider <span class="text-red-500">*</span>
                            </label>
                            <select name="provider" x-model="provider" required
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                                <option value="openai">OpenAI (GPT-3.5, GPT-4)</option>
                                <option value="deepseek">DeepSeek</option>
                                <option value="anthropic">Anthropic (Claude)</option>
                                <option value="gemini">Google Gemini</option>
                                <option value="custom">Custom API Endpoint</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-microchip text-purple-500 mr-1"></i> Model <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="model" value="{{ old('model', $aiSetting->model ?? 'gpt-3.5-turbo') }}" required
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all"
                                placeholder="gpt-3.5-turbo">
                            <p class="text-xs text-gray-500 mt-1">
                                Examples: gpt-3.5-turbo, gpt-4, deepseek-chat, claude-3-sonnet, gemini-pro
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-key text-purple-500 mr-1"></i> API Key <span class="text-red-500">*</span>
                            </label>
                            <input type="password" name="api_key" value="{{ old('api_key', $aiSetting->api_key ?? '') }}" required
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all font-mono text-sm"
                                placeholder="sk-...">
                            <p class="text-xs text-gray-500 mt-1">Your API key from the provider's dashboard</p>
                        </div>

                        <!-- Custom API Endpoint (only shown for custom provider) -->
                        <div x-show="provider === 'custom'" x-cloak>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-link text-purple-500 mr-1"></i> API Endpoint URL
                            </label>
                            <input type="url" name="api_endpoint" value="{{ old('api_endpoint', $aiSetting->api_endpoint ?? '') }}"
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all"
                                placeholder="https://api.example.com/v1/chat/completions">
                            <p class="text-xs text-gray-500 mt-1">Custom API endpoint that follows OpenAI format</p>
                        </div>
                    </div>
                </div>

                <!-- AI Parameters -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-500 to-cyan-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white flex items-center">
                            <i class="fas fa-sliders-h mr-2"></i>
                            AI Parameters
                        </h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-thermometer-half text-blue-500 mr-1"></i> Temperature: <span id="temp-value" class="font-mono text-blue-600">{{ old('temperature', $aiSetting->temperature ?? 0.7) }}</span>
                            </label>
                            <input type="range" name="temperature" min="0" max="2" step="0.1"
                                value="{{ old('temperature', $aiSetting->temperature ?? 0.7) }}"
                                oninput="document.getElementById('temp-value').textContent = this.value"
                                class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-blue-600">
                            <div class="flex justify-between text-xs text-gray-500 mt-1">
                                <span>More Focused (0)</span>
                                <span>Balanced (1)</span>
                                <span>More Creative (2)</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-coins text-blue-500 mr-1"></i> Max Tokens
                            </label>
                            <input type="number" name="max_tokens" value="{{ old('max_tokens', $aiSetting->max_tokens ?? 1000) }}" min="100" max="4000" step="100"
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                placeholder="1000">
                            <p class="text-xs text-gray-500 mt-1">Maximum length of AI responses (100-4000)</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-history text-blue-500 mr-1"></i> Conversation Memory Limit
                            </label>
                            <input type="number" name="conversation_memory_limit" value="{{ old('conversation_memory_limit', $aiSetting->conversation_memory_limit ?? 10) }}" min="1" max="50"
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                placeholder="10">
                            <p class="text-xs text-gray-500 mt-1">How many previous messages to include in context</p>
                        </div>
                    </div>
                </div>

                <!-- System Prompt -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white flex items-center">
                            <i class="fas fa-file-alt mr-2"></i>
                            System Prompt
                        </h3>
                    </div>
                    <div class="p-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-robot text-green-500 mr-1"></i> System Instructions
                        </label>
                        <textarea name="system_prompt" rows="8"
                            class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all font-mono text-sm"
                            placeholder="You are a helpful assistant for our affiliate program...">{{ old('system_prompt', $aiSetting->system_prompt ?? 'You are a helpful and friendly assistant. You help users with their questions about our service.') }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">Define the AI's personality, role, and behavior</p>

                        <div class="mt-4 p-4 bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl border border-green-200">
                            <p class="text-sm font-semibold text-green-900 mb-2">
                                <i class="fas fa-lightbulb text-green-600 mr-1"></i> Pro Tips:
                            </p>
                            <ul class="text-sm text-green-800 space-y-1">
                                <li>• Be specific about the AI's role and expertise</li>
                                <li>• Include guidelines for tone and style</li>
                                <li>• Mention any restrictions or limitations</li>
                                <li>• Add context about your business or service</li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Sidebar (Right - 1 column) -->
            <div class="lg:col-span-1 space-y-6">

                <!-- Status Card -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden sticky top-6">
                    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white flex items-center">
                            <i class="fas fa-toggle-on mr-2"></i>
                            Status
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h4 class="text-base font-bold text-gray-900 mb-1">Activate This Setting</h4>
                                <p class="text-sm text-gray-600">Enable AI responses</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" class="sr-only peer"
                                    {{ old('is_active', $aiSetting->is_active ?? false) ? 'checked' : '' }}
                                    x-model="isActive">
                                <div class="w-14 h-7 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-7 peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-purple-500 peer-checked:to-indigo-600"></div>
                            </label>
                        </div>

                        <div class="p-4 bg-yellow-50 rounded-xl border border-yellow-200">
                            <div class="flex items-start gap-2">
                                <i class="fas fa-exclamation-triangle text-yellow-600 mt-0.5"></i>
                                <p class="text-sm text-yellow-800">
                                    Only one AI setting can be active at a time. Activating this will deactivate others.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Guide -->
                <div class="bg-gradient-to-br from-purple-50 to-indigo-50 rounded-2xl border border-purple-200 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-lg bg-purple-500 flex items-center justify-center">
                            <i class="fas fa-question text-white"></i>
                        </div>
                        <h3 class="font-bold text-purple-900">Quick Guide</h3>
                    </div>

                    <div class="space-y-3 text-sm text-purple-800">
                        <div class="flex items-start gap-2">
                            <i class="fas fa-check-circle text-purple-600 mt-0.5"></i>
                            <p>Choose your preferred AI provider</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <i class="fas fa-check-circle text-purple-600 mt-0.5"></i>
                            <p>Get API key from provider's dashboard</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <i class="fas fa-check-circle text-purple-600 mt-0.5"></i>
                            <p>Configure parameters for best results</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <i class="fas fa-check-circle text-purple-600 mt-0.5"></i>
                            <p>Add knowledge bases for context</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <i class="fas fa-check-circle text-purple-600 mt-0.5"></i>
                            <p>Test before activating</p>
                        </div>
                    </div>
                </div>

                <!-- Provider Links -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                    <h3 class="font-bold text-gray-900 mb-4">
                        <i class="fas fa-external-link-alt text-blue-500 mr-1"></i> Provider Links
                    </h3>
                    <div class="space-y-2 text-sm">
                        <a href="https://platform.openai.com/api-keys" target="_blank"
                           class="flex items-center gap-2 p-2 hover:bg-gray-50 rounded-lg transition">
                            <i class="fas fa-key text-green-500"></i>
                            <span class="text-gray-700">OpenAI API Keys</span>
                        </a>
                        <a href="https://platform.deepseek.com/" target="_blank"
                           class="flex items-center gap-2 p-2 hover:bg-gray-50 rounded-lg transition">
                            <i class="fas fa-key text-blue-500"></i>
                            <span class="text-gray-700">DeepSeek Platform</span>
                        </a>
                        <a href="https://console.anthropic.com/" target="_blank"
                           class="flex items-center gap-2 p-2 hover:bg-gray-50 rounded-lg transition">
                            <i class="fas fa-key text-orange-500"></i>
                            <span class="text-gray-700">Anthropic Console</span>
                        </a>
                        <a href="https://makersuite.google.com/app/apikey" target="_blank"
                           class="flex items-center gap-2 p-2 hover:bg-gray-50 rounded-lg transition">
                            <i class="fas fa-key text-purple-500"></i>
                            <span class="text-gray-700">Google AI Studio</span>
                        </a>
                    </div>
                </div>

            </div>
        </div>

        <!-- Submit Button -->
        <div class="mt-8">
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <a href="{{ route('admin.line-bot.ai.index') }}" class="text-gray-600 hover:text-gray-900 text-sm">
                            <i class="fas fa-arrow-left mr-1"></i> Back to list
                        </a>
                    </div>
                    <div class="flex gap-3">
                        <button type="submit" class="px-8 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl hover:from-purple-700 hover:to-indigo-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-bold">
                            <i class="fas fa-save mr-2"></i>
                            @isset($aiSetting)
                                Update Setting
                            @else
                                Create Setting
                            @endisset
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
[x-cloak] {
    display: none !important;
}
</style>
@endsection
