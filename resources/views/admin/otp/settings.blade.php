@extends('layouts.admin-v3')

@section('title', 'OTP Settings')

@section('content')
<div class="container-fluid px-4 py-6" x-data="{
    provider: '{{ old('provider', $settings->provider) }}',
    enabled: {{ old('enabled', $settings->enabled) ? 'true' : 'false' }}
}">
    <!-- Animated Header -->
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-purple-600 via-blue-600 to-indigo-700 p-8 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>
        <div class="relative flex items-center justify-between">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-14 h-14 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                        <i class="fas fa-shield-alt text-3xl text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-1">OTP Verification System</h1>
                        <p class="text-blue-100">Secure phone verification with multi-provider support</p>
                    </div>
                </div>
            </div>
            <div>
                <button type="button" onclick="showTestModal()"
                        class="px-6 py-3 bg-white/20 backdrop-blur-md border border-white/30 text-white rounded-xl hover:bg-white/30 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <i class="fas fa-paper-plane me-2"></i>Test OTP
                </button>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 animate-fade-in">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-full bg-green-500 flex items-center justify-center mr-3">
                    <i class="fas fa-check text-white"></i>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-green-900">Success!</p>
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 rounded-xl bg-gradient-to-r from-red-50 to-pink-50 border border-red-200 animate-fade-in">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-full bg-red-500 flex items-center justify-center mr-3">
                    <i class="fas fa-exclamation-circle text-white"></i>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-red-900">Error!</p>
                    <p class="text-sm text-red-700">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('admin.otp.settings.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Settings (Left - 2 columns) -->
            <div class="lg:col-span-2 space-y-6">

                <!-- System Status Card -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white flex items-center">
                            <i class="fas fa-power-off mr-2"></i>
                            System Control
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <h4 class="text-lg font-bold text-gray-900 mb-1">Enable OTP Verification</h4>
                                <p class="text-sm text-gray-600">Activate phone number verification using OTP codes</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="enabled" name="enabled" value="1" class="sr-only peer"
                                    {{ old('enabled', $settings->enabled) ? 'checked' : '' }}
                                    x-model="enabled">
                                <div class="w-16 h-8 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-8 peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-7 after:w-7 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-purple-500 peer-checked:to-indigo-600"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Provider Selection Card -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-500 to-cyan-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white flex items-center">
                            <i class="fas fa-server mr-2"></i>
                            SMS Provider
                        </h3>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-gray-600 mb-6">Choose your SMS service provider for sending OTP codes</p>

                        <!-- Provider Cards -->
                        <div class="grid grid-cols-3 gap-4 mb-6">
                            <!-- Twilio -->
                            <label class="relative cursor-pointer group">
                                <input type="radio" name="provider" value="twilio" class="sr-only"
                                    {{ old('provider', $settings->provider) === 'twilio' ? 'checked' : '' }}
                                    x-model="provider">
                                <div class="h-28 rounded-xl border-2 transition-all duration-300 flex flex-col items-center justify-center"
                                    :class="provider === 'twilio' ? 'border-red-500 bg-red-50 shadow-lg transform scale-105' : 'border-gray-200 bg-gray-50 group-hover:border-red-300'">
                                    <div class="text-4xl mb-2">
                                        <i class="fas fa-comments" :class="provider === 'twilio' ? 'text-red-500' : 'text-gray-400'"></i>
                                    </div>
                                    <span class="font-bold" :class="provider === 'twilio' ? 'text-red-700' : 'text-gray-600'">Twilio</span>
                                    <div class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-red-500 text-white flex items-center justify-center transition-all duration-300"
                                        x-show="provider === 'twilio'">
                                        <i class="fas fa-check text-xs"></i>
                                    </div>
                                </div>
                            </label>

                            <!-- Nexmo -->
                            <label class="relative cursor-pointer group">
                                <input type="radio" name="provider" value="nexmo" class="sr-only"
                                    {{ old('provider', $settings->provider) === 'nexmo' ? 'checked' : '' }}
                                    x-model="provider">
                                <div class="h-28 rounded-xl border-2 transition-all duration-300 flex flex-col items-center justify-center"
                                    :class="provider === 'nexmo' ? 'border-blue-500 bg-blue-50 shadow-lg transform scale-105' : 'border-gray-200 bg-gray-50 group-hover:border-blue-300'">
                                    <div class="text-4xl mb-2">
                                        <i class="fas fa-sms" :class="provider === 'nexmo' ? 'text-blue-500' : 'text-gray-400'"></i>
                                    </div>
                                    <span class="font-bold" :class="provider === 'nexmo' ? 'text-blue-700' : 'text-gray-600'">Nexmo</span>
                                    <div class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-blue-500 text-white flex items-center justify-center transition-all duration-300"
                                        x-show="provider === 'nexmo'">
                                        <i class="fas fa-check text-xs"></i>
                                    </div>
                                </div>
                            </label>

                            <!-- Custom API -->
                            <label class="relative cursor-pointer group">
                                <input type="radio" name="provider" value="custom" class="sr-only"
                                    {{ old('provider', $settings->provider) === 'custom' ? 'checked' : '' }}
                                    x-model="provider">
                                <div class="h-28 rounded-xl border-2 transition-all duration-300 flex flex-col items-center justify-center"
                                    :class="provider === 'custom' ? 'border-purple-500 bg-purple-50 shadow-lg transform scale-105' : 'border-gray-200 bg-gray-50 group-hover:border-purple-300'">
                                    <div class="text-4xl mb-2">
                                        <i class="fas fa-code" :class="provider === 'custom' ? 'text-purple-500' : 'text-gray-400'"></i>
                                    </div>
                                    <span class="font-bold" :class="provider === 'custom' ? 'text-purple-700' : 'text-gray-600'">Custom</span>
                                    <div class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-purple-500 text-white flex items-center justify-center transition-all duration-300"
                                        x-show="provider === 'custom'">
                                        <i class="fas fa-check text-xs"></i>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <!-- Provider Settings -->
                        <!-- Twilio Settings -->
                        <div x-show="provider === 'twilio'"
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 transform scale-95"
                             x-transition:enter-end="opacity-100 transform scale-100"
                             class="space-y-4 p-5 bg-red-50 rounded-xl border border-red-100">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 rounded-lg bg-red-500 flex items-center justify-center">
                                    <i class="fas fa-comments text-white"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-red-900">Twilio Configuration</h4>
                                    <p class="text-xs text-red-600">Enter your Twilio API credentials</p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Account SID</label>
                                <input type="text" name="twilio_sid" value="{{ old('twilio_sid', $settings->twilio_sid) }}"
                                    class="w-full px-4 py-3 border border-red-200 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white transition-all"
                                    placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Auth Token</label>
                                <input type="password" name="twilio_token" value="{{ old('twilio_token', $settings->twilio_token) }}"
                                    class="w-full px-4 py-3 border border-red-200 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white transition-all"
                                    placeholder="your_auth_token">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">From Number</label>
                                <input type="text" name="twilio_from" value="{{ old('twilio_from', $settings->twilio_from) }}"
                                    class="w-full px-4 py-3 border border-red-200 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white transition-all"
                                    placeholder="+1234567890">
                            </div>

                            <div class="flex items-start gap-3 p-3 bg-red-100 rounded-lg">
                                <i class="fas fa-info-circle text-red-600 mt-0.5"></i>
                                <div class="text-sm text-red-800">
                                    <strong>Quick Start:</strong> Get your credentials from <a href="https://console.twilio.com" target="_blank" class="underline hover:no-underline">Twilio Console</a>
                                </div>
                            </div>
                        </div>

                        <!-- Nexmo Settings -->
                        <div x-show="provider === 'nexmo'"
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 transform scale-95"
                             x-transition:enter-end="opacity-100 transform scale-100"
                             class="space-y-4 p-5 bg-blue-50 rounded-xl border border-blue-100">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 rounded-lg bg-blue-500 flex items-center justify-center">
                                    <i class="fas fa-sms text-white"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-blue-900">Nexmo/Vonage Configuration</h4>
                                    <p class="text-xs text-blue-600">Enter your Nexmo API credentials</p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">API Key</label>
                                <input type="text" name="nexmo_key" value="{{ old('nexmo_key', $settings->nexmo_key) }}"
                                    class="w-full px-4 py-3 border border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white transition-all"
                                    placeholder="your_api_key">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">API Secret</label>
                                <input type="password" name="nexmo_secret" value="{{ old('nexmo_secret', $settings->nexmo_secret) }}"
                                    class="w-full px-4 py-3 border border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white transition-all"
                                    placeholder="your_api_secret">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">From Name/Number</label>
                                <input type="text" name="nexmo_from" value="{{ old('nexmo_from', $settings->nexmo_from) }}"
                                    class="w-full px-4 py-3 border border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white transition-all"
                                    placeholder="YourBrand or +1234567890">
                            </div>

                            <div class="flex items-start gap-3 p-3 bg-blue-100 rounded-lg">
                                <i class="fas fa-info-circle text-blue-600 mt-0.5"></i>
                                <div class="text-sm text-blue-800">
                                    <strong>Quick Start:</strong> Get your credentials from <a href="https://dashboard.nexmo.com" target="_blank" class="underline hover:no-underline">Nexmo Dashboard</a>
                                </div>
                            </div>
                        </div>

                        <!-- Custom API Settings -->
                        <div x-show="provider === 'custom'"
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 transform scale-95"
                             x-transition:enter-end="opacity-100 transform scale-100"
                             class="space-y-4 p-5 bg-purple-50 rounded-xl border border-purple-100">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 rounded-lg bg-purple-500 flex items-center justify-center">
                                    <i class="fas fa-code text-white"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-purple-900">Custom API Configuration</h4>
                                    <p class="text-xs text-purple-600">Connect to your own SMS API</p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">API URL</label>
                                <input type="url" name="custom_api_url" value="{{ old('custom_api_url', $settings->custom_api_url) }}"
                                    class="w-full px-4 py-3 border border-purple-200 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 bg-white transition-all"
                                    placeholder="https://api.example.com/send-sms">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">API Key (Optional)</label>
                                <input type="password" name="custom_api_key" value="{{ old('custom_api_key', $settings->custom_api_key) }}"
                                    class="w-full px-4 py-3 border border-purple-200 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 bg-white transition-all"
                                    placeholder="your_api_key">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Custom Headers (JSON)</label>
                                <textarea name="custom_api_headers" rows="3"
                                    class="w-full px-4 py-3 border border-purple-200 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 bg-white transition-all font-mono text-sm"
                                    placeholder='{"Authorization": "Bearer token", "Content-Type": "application/json"}'>{{ old('custom_api_headers', $settings->custom_api_headers) }}</textarea>
                            </div>

                            <div class="flex items-start gap-3 p-3 bg-purple-100 rounded-lg">
                                <i class="fas fa-info-circle text-purple-600 mt-0.5"></i>
                                <div class="text-sm text-purple-800">
                                    <strong>Expected Format:</strong> API should accept POST with JSON: <code class="bg-purple-200 px-1 rounded">{"phone": "+66812345678", "message": "...", "code": "123456"}</code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- OTP Configuration Card -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white flex items-center">
                            <i class="fas fa-key mr-2"></i>
                            OTP Configuration
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-sort-numeric-up text-green-500 mr-1"></i> OTP Length
                                </label>
                                <input type="number" name="otp_length" value="{{ old('otp_length', $settings->otp_length) }}"
                                    min="4" max="8"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
                                <p class="text-xs text-gray-500 mt-1">Number of digits (4-8)</p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-clock text-green-500 mr-1"></i> Expiry Time
                                </label>
                                <div class="relative">
                                    <input type="number" name="otp_expiry_minutes" value="{{ old('otp_expiry_minutes', $settings->otp_expiry_minutes) }}"
                                        min="1" max="60"
                                        class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm text-gray-500">min</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Validity duration (1-60)</p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-redo text-green-500 mr-1"></i> Max Attempts
                                </label>
                                <input type="number" name="max_attempts" value="{{ old('max_attempts', $settings->max_attempts) }}"
                                    min="1" max="10"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
                                <p class="text-xs text-gray-500 mt-1">Failed attempts allowed (1-10)</p>
                            </div>

                            <div class="flex items-center">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="alphanumeric" value="1" class="sr-only peer"
                                        {{ old('alphanumeric', $settings->alphanumeric) ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500"></div>
                                    <span class="ml-3 text-sm font-semibold text-gray-700">
                                        <i class="fas fa-font text-green-500 mr-1"></i> Alphanumeric OTP
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rate Limiting Card -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-orange-500 to-red-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white flex items-center">
                            <i class="fas fa-shield-alt mr-2"></i>
                            Rate Limiting & Security
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-ban text-orange-500 mr-1"></i> Max Requests
                                </label>
                                <input type="number" name="rate_limit_per_phone" value="{{ old('rate_limit_per_phone', $settings->rate_limit_per_phone) }}"
                                    min="1" max="20"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all">
                                <p class="text-xs text-gray-500 mt-1">Maximum OTP requests allowed</p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-hourglass-half text-orange-500 mr-1"></i> Time Window
                                </label>
                                <div class="relative">
                                    <input type="number" name="rate_limit_window" value="{{ old('rate_limit_window', $settings->rate_limit_window) }}"
                                        min="1" max="1440"
                                        class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all">
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm text-gray-500">min</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Rate limit time window</p>
                            </div>
                        </div>

                        <div class="mt-4 p-4 bg-gradient-to-r from-orange-50 to-red-50 rounded-xl border border-orange-200">
                            <div class="flex items-start gap-3">
                                <i class="fas fa-lightbulb text-orange-500 text-xl mt-0.5"></i>
                                <div class="text-sm">
                                    <p class="font-semibold text-orange-900 mb-1">Example Configuration</p>
                                    <p class="text-orange-700"><strong>{{ $settings->rate_limit_per_phone }} requests</strong> per <strong>{{ $settings->rate_limit_window }} minutes</strong> = A phone can request OTP maximum {{ $settings->rate_limit_per_phone }} times in {{ $settings->rate_limit_window }} minutes</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Message Template Card -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-pink-500 to-rose-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white flex items-center">
                            <i class="fas fa-comment-alt mr-2"></i>
                            Message Template
                        </h3>
                    </div>
                    <div class="p-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">SMS Message Template</label>
                        <textarea name="message_template" rows="3" id="messageTemplate"
                            class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500 transition-all">{{ old('message_template', $settings->message_template) }}</textarea>
                        <p class="text-xs text-gray-500 mt-2">
                            Available placeholders: <code class="bg-gray-100 px-2 py-1 rounded text-pink-600">{code}</code>, <code class="bg-gray-100 px-2 py-1 rounded text-pink-600">{expiry}</code>
                        </p>

                        <div class="mt-4 p-4 bg-gradient-to-r from-pink-50 to-rose-50 rounded-xl border border-pink-200">
                            <div class="flex items-start gap-3">
                                <i class="fas fa-eye text-pink-500 text-xl mt-0.5"></i>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-pink-900 mb-2">Preview:</p>
                                    <p class="text-sm text-pink-700 font-mono" id="messagePreview">{{ str_replace(['{code}', '{expiry}'], ['123456', $settings->otp_expiry_minutes], old('message_template', $settings->message_template)) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Status Sidebar (Right - 1 column) -->
            <div class="lg:col-span-1 space-y-6">

                <!-- Current Status Card -->
                <div class="bg-gradient-to-br from-purple-600 to-indigo-700 rounded-2xl shadow-2xl p-6 text-white sticky top-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                            <i class="fas fa-chart-line text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold">System Status</h3>
                            <p class="text-sm text-purple-200">Current Configuration</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-3 bg-white/10 backdrop-blur-sm rounded-lg">
                            <span class="text-sm">System:</span>
                            @if($settings->enabled)
                                <span class="px-3 py-1 bg-green-500 rounded-full text-xs font-bold">Active</span>
                            @else
                                <span class="px-3 py-1 bg-red-500 rounded-full text-xs font-bold">Inactive</span>
                            @endif
                        </div>

                        <div class="flex items-center justify-between p-3 bg-white/10 backdrop-blur-sm rounded-lg">
                            <span class="text-sm">Provider:</span>
                            <span class="px-3 py-1 bg-white/20 rounded-full text-xs font-bold">{{ ucfirst($settings->provider) }}</span>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-white/10 backdrop-blur-sm rounded-lg">
                            <span class="text-sm">OTP Length:</span>
                            <span class="font-bold">{{ $settings->otp_length }} digits</span>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-white/10 backdrop-blur-sm rounded-lg">
                            <span class="text-sm">Expiry:</span>
                            <span class="font-bold">{{ $settings->otp_expiry_minutes }} min</span>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-white/10 backdrop-blur-sm rounded-lg">
                            <span class="text-sm">Max Attempts:</span>
                            <span class="font-bold">{{ $settings->max_attempts }}x</span>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-white/10 backdrop-blur-sm rounded-lg">
                            <span class="text-sm">Rate Limit:</span>
                            <span class="font-bold text-xs">{{ $settings->rate_limit_per_phone }}/{{ $settings->rate_limit_window }}min</span>
                        </div>
                    </div>
                </div>

                <!-- Setup Guide Card -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center">
                            <i class="fas fa-book text-indigo-600"></i>
                        </div>
                        <h3 class="font-bold text-gray-900">Setup Guide</h3>
                    </div>

                    <ol class="space-y-3">
                        <li class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold">1</span>
                            <span class="text-sm text-gray-700">Choose SMS provider</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold">2</span>
                            <span class="text-sm text-gray-700">Enter provider credentials</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold">3</span>
                            <span class="text-sm text-gray-700">Configure OTP settings</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold">4</span>
                            <span class="text-sm text-gray-700">Set rate limiting rules</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold">5</span>
                            <span class="text-sm text-gray-700">Customize message template</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold">6</span>
                            <span class="text-sm text-gray-700">Test the system</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xs font-bold">7</span>
                            <span class="text-sm text-gray-700 font-semibold">Enable OTP verification</span>
                        </li>
                    </ol>
                </div>

                <!-- Thai Phone Format Card -->
                <div class="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-2xl border border-blue-200 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-lg bg-blue-500 flex items-center justify-center">
                            <i class="fas fa-phone text-white"></i>
                        </div>
                        <h3 class="font-bold text-blue-900">Thai Phone Format</h3>
                    </div>

                    <p class="text-sm text-blue-700 mb-3">Supported formats:</p>
                    <ul class="space-y-2">
                        <li class="flex items-center gap-2 text-sm">
                            <i class="fas fa-check-circle text-green-500"></i>
                            <code class="bg-white px-2 py-1 rounded text-blue-900">0812345678</code>
                        </li>
                        <li class="flex items-center gap-2 text-sm">
                            <i class="fas fa-check-circle text-green-500"></i>
                            <code class="bg-white px-2 py-1 rounded text-blue-900">66812345678</code>
                        </li>
                        <li class="flex items-center gap-2 text-sm">
                            <i class="fas fa-check-circle text-green-500"></i>
                            <code class="bg-white px-2 py-1 rounded text-blue-900">+66812345678</code>
                        </li>
                    </ul>
                </div>

            </div>
        </div>

        <!-- Save Button -->
        <div class="mt-8">
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-info-circle text-indigo-500 text-xl"></i>
                        <p class="text-sm text-gray-600">
                            Make sure to test the OTP system before enabling it for users
                        </p>
                    </div>
                    <button type="submit" class="px-8 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl hover:from-purple-700 hover:to-indigo-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-bold">
                        <i class="fas fa-save mr-2"></i>Save Settings
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Test OTP Modal -->
<div id="testModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden transform transition-all">
        <div class="bg-gradient-to-r from-cyan-500 to-blue-600 px-6 py-4">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Test OTP Sending
                </h3>
                <button onclick="closeTestModal()" class="text-white/80 hover:text-white transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>

        <div class="p-6">
            <div id="test-alert" class="hidden mb-4 p-3 rounded-lg"></div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Phone Number</label>
                <input type="text" id="test_phone"
                    class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-all"
                    placeholder="0812345678 or +66812345678">
                <p class="text-xs text-gray-500 mt-2">Enter a valid Thai phone number</p>
            </div>
        </div>

        <div class="px-6 pb-6 flex gap-3">
            <button type="button" onclick="closeTestModal()"
                    class="flex-1 px-4 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-semibold">
                Cancel
            </button>
            <button type="button" onclick="sendTestOtp()"
                    class="flex-1 px-4 py-3 bg-gradient-to-r from-cyan-500 to-blue-600 text-white rounded-lg hover:from-cyan-600 hover:to-blue-700 transition shadow-lg font-semibold">
                <i class="fas fa-paper-plane mr-2"></i>Send OTP
            </button>
        </div>
    </div>
</div>

<style>
@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fade-in 0.3s ease-out;
}

[x-cloak] {
    display: none !important;
}
</style>

<script>
// Show/close test modal
function showTestModal() {
    document.getElementById('testModal').classList.remove('hidden');
    document.getElementById('testModal').classList.add('flex');
}

function closeTestModal() {
    document.getElementById('testModal').classList.add('hidden');
    document.getElementById('testModal').classList.remove('flex');
    document.getElementById('test-alert').classList.add('hidden');
    document.getElementById('test_phone').value = '';
}

// Send test OTP
async function sendTestOtp() {
    const phone = document.getElementById('test_phone').value;
    const alertDiv = document.getElementById('test-alert');

    if (!phone) {
        showTestAlert('กรุณากรอกเบอร์โทรศัพท์', 'error');
        return;
    }

    try {
        const response = await fetch('{{ route("admin.otp.test") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ test_phone: phone })
        });

        const data = await response.json();

        if (data.success) {
            showTestAlert(data.message, 'success');
        } else {
            showTestAlert(data.message, 'error');
        }
    } catch (error) {
        showTestAlert('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง', 'error');
    }
}

function showTestAlert(message, type) {
    const alertDiv = document.getElementById('test-alert');
    alertDiv.classList.remove('hidden', 'bg-green-100', 'text-green-800', 'bg-red-100', 'text-red-800', 'border-green-200', 'border-red-200');

    if (type === 'success') {
        alertDiv.classList.add('bg-green-100', 'text-green-800', 'border', 'border-green-200');
        alertDiv.innerHTML = `<div class="flex items-center"><i class="fas fa-check-circle mr-2"></i>${message}</div>`;
    } else {
        alertDiv.classList.add('bg-red-100', 'text-red-800', 'border', 'border-red-200');
        alertDiv.innerHTML = `<div class="flex items-center"><i class="fas fa-exclamation-circle mr-2"></i>${message}</div>`;
    }

    alertDiv.classList.remove('hidden');
}

// Update message preview
document.addEventListener('DOMContentLoaded', function() {
    const templateInput = document.getElementById('messageTemplate');
    const previewDiv = document.getElementById('messagePreview');
    const expiryInput = document.querySelector('[name="otp_expiry_minutes"]');

    function updatePreview() {
        const template = templateInput.value;
        const expiry = expiryInput.value || '5';
        const preview = template.replace('{code}', '123456').replace('{expiry}', expiry);
        previewDiv.textContent = preview;
    }

    if (templateInput && previewDiv && expiryInput) {
        templateInput.addEventListener('input', updatePreview);
        expiryInput.addEventListener('input', updatePreview);
    }
});

// Close modal on ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeTestModal();
    }
});
</script>
@endsection
