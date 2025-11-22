@extends('layouts.admin-v3')

@section('title', 'ตั้งค่า LINE Official Account')

@section('content')
<div class="container-fluid px-4 py-6" x-data="{
    requireLineReg: {{ old('require_line_registration', $settings->require_line_registration) ? 'true' : 'false' }},
    enableMessaging: {{ old('enable_line_messaging', $settings->enable_line_messaging) ? 'true' : 'false' }},
    isActive: {{ old('is_active', $settings->is_active) ? 'true' : 'false' }}
}">
    <!-- Animated Header -->
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-green-500 via-emerald-600 to-teal-700 p-8 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>
        <div class="relative flex items-center justify-between">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-14 h-14 rounded-xl glass-fusion backdrop-blur-sm flex items-center justify-center" border border-white/20 dark:border-white/10>
                        <svg class="w-8 h-8 text-white" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-1">LINE Official Account</h1>
                        <p class="text-green-100">Connect with LINE Login & Messaging API</p>
                    </div>
                </div>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="testConnection()"
                        class="px-6 py-3 glass-fusion backdrop-blur-md border border-white/30 text-white rounded-xl hover:glass-fusion transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <i class="fas fa-plug me-2"></i>Test Connection
                </button>
                <button type="button" onclick="showTestModal()"
                        class="px-6 py-3 glass-fusion backdrop-blur-md border border-white/30 text-white rounded-xl hover:glass-fusion transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <i class="fas fa-paper-plane me-2"></i>Test Message
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

    <form method="POST" action="{{ route('admin.line-oa.update') }}">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Settings (Left - 2 columns) -->
            <div class="lg:col-span-2 space-y-6">

                <!-- System Status Card -->
                <div class="glass-fusion rounded-2xl shadow-lg border border-gray-100 overflow-hidden" border border-white/20 dark:border-white/10>
                    <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white flex items-center">
                            <i class="fas fa-power-off mr-2"></i>
                            System Control
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <h4 class="text-lg font-bold text-gray-900 mb-1">Enable LINE OA System</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Activate LINE Official Account integration</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="is_active" name="is_active" value="1" class="sr-only peer"
                                    {{ old('is_active', $settings->is_active) ? 'checked' : '' }}
                                    x-model="isActive">
                                <div class="w-16 h-8 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-8 peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:glass-fusion after:border-gray-300 dark:border-gray-600 after:border after:rounded-full after:h-7 after:w-7 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-green-500 peer-checked:to-emerald-600" border border-white/20 dark:border-white/10></div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- LINE Login Channel Configuration -->
                <div class="glass-fusion rounded-2xl shadow-lg border border-gray-100 overflow-hidden" border border-white/20 dark:border-white/10>
                    <div class="bg-gradient-to-r from-blue-500 to-cyan-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white flex items-center">
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            LINE Login Channel (OAuth)
                        </h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="p-3 bg-blue-50 rounded-xl border border-blue-200 mb-4">
                            <p class="text-xs text-blue-800">
                                <i class="fas fa-info-circle mr-1"></i>
                                <strong>Note:</strong> LINE Login Channel is used for user authentication (login/register). This is different from Messaging API Channel.
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-id-card text-blue-500 mr-1"></i> LINE Login Channel ID <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="login_channel_id" value="{{ old('login_channel_id', $settings->login_channel_id) }}"
                                class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                placeholder="1234567890">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">From LINE Developers Console → LINE Login Channel → Basic Settings</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-lock text-blue-500 mr-1"></i> LINE Login Channel Secret <span class="text-red-500">*</span>
                            </label>
                            <input type="password" name="channel_secret" value="{{ old('channel_secret', $settings->channel_secret) }}"
                                class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                placeholder="••••••••••••••••">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">From LINE Developers Console → LINE Login Channel → Basic Settings</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-link text-blue-500 mr-1"></i> Redirect URI (Callback URL) <span class="text-red-500">*</span>
                            </label>
                            <input type="url" name="redirect_uri" value="{{ old('redirect_uri', $settings->redirect_uri) }}"
                                class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                placeholder="{{ route('line.callback') }}"
                                required>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                <strong>Suggested:</strong> <code class="bg-gray-100/50 dark:bg-gray-800/50 px-2 py-0.5 rounded text-xs">{{ route('line.callback') }}</code>
                            </p>
                            <div class="mt-2 p-3 bg-red-50 rounded-xl border border-red-200">
                                <p class="text-xs text-red-800">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    <strong>REQUIRED:</strong> This exact URL must be registered in LINE Developers Console → LINE Login Channel → Callback URL.
                                    If not set correctly, you will get "400 Bad Request - Invalid redirect_uri" error.
                                </p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-window-maximize text-blue-500 mr-1"></i> LIFF ID (Optional)
                            </label>
                            <input type="text" name="liff_id" value="{{ old('liff_id', $settings->liff_id) }}"
                                class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                placeholder="1234567890-abcdefgh">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">From LINE Developers Console → LINE Login Channel → LIFF tab</p>
                        </div>
                    </div>
                </div>

                <!-- LINE Messaging API Configuration -->
                <div class="glass-fusion rounded-2xl shadow-lg border border-gray-100 overflow-hidden" border border-white/20 dark:border-white/10>
                    <div class="bg-gradient-to-r from-green-500 to-teal-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white flex items-center">
                            <i class="fas fa-comments mr-2"></i>
                            LINE Messaging API Channel (Optional)
                        </h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="p-3 bg-green-50 rounded-xl border border-green-200 mb-4">
                            <p class="text-xs text-green-800">
                                <i class="fas fa-info-circle mr-1"></i>
                                <strong>Note:</strong> LINE Messaging API Channel is used for sending messages to users. This is separate from LINE Login Channel.
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-hashtag text-green-500 mr-1"></i> Messaging API Channel ID (Optional)
                            </label>
                            <input type="text" name="messaging_channel_id" value="{{ old('messaging_channel_id', $settings->messaging_channel_id) }}"
                                class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all"
                                placeholder="1234567890">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">From LINE Developers Console → Messaging API Channel → Basic Settings (for reference only)</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-ticket-alt text-green-500 mr-1"></i> Channel Access Token (Long-lived)
                            </label>
                            <textarea name="channel_access_token" rows="3"
                                class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all font-mono text-sm"
                                placeholder="Enter your long-lived channel access token here">{{ old('channel_access_token', $settings->channel_access_token) }}</textarea>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">From LINE Developers Console → Messaging API Channel → Messaging API tab</p>
                        </div>

                        <div class="mt-4 p-4 bg-gradient-to-r from-green-50 to-teal-50 rounded-xl border border-green-200">
                            <div class="flex items-start gap-3">
                                <i class="fas fa-info-circle text-green-500 text-xl mt-0.5"></i>
                                <div class="text-sm">
                                    <p class="font-semibold text-green-900 mb-1">Webhook URL</p>
                                    <ul class="text-green-700 space-y-1">
                                        <li><strong>Webhook URL:</strong> <code class="glass-fusion px-2 py-1 rounded text-xs">{{ url('/api/webhook/line') }}</code></li>
                                    </ul>
                                    <p class="text-xs text-green-600 mt-2">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                        Register this URL in LINE Developers Console → Messaging API Channel → Webhook settings
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Registration Settings -->
                <div class="glass-fusion rounded-2xl shadow-lg border border-gray-100 overflow-hidden" border border-white/20 dark:border-white/10>
                    <div class="bg-gradient-to-r from-purple-500 to-pink-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white flex items-center">
                            <i class="fas fa-user-plus mr-2"></i>
                            Registration Settings
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex-1">
                                <h4 class="text-base font-bold text-gray-900 mb-1">Require LINE Registration</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Force users to login with LINE before registration (KYC Level 1)</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="require_line_registration" value="1" class="sr-only peer"
                                    {{ old('require_line_registration', $settings->require_line_registration) ? 'checked' : '' }}
                                    x-model="requireLineReg">
                                <div class="w-14 h-7 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-7 peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:glass-fusion after:border-gray-300 dark:border-gray-600 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-purple-500 peer-checked:to-pink-600" border border-white/20 dark:border-white/10></div>
                            </label>
                        </div>

                        <div class="p-4 bg-purple-50 rounded-xl border border-purple-200">
                            <div class="flex items-start gap-3">
                                <i class="fas fa-shield-alt text-purple-500 text-lg mt-0.5"></i>
                                <div class="text-sm text-purple-800">
                                    <p class="font-semibold mb-1">Security Benefits</p>
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>Verified identity through LINE</li>
                                        <li>Reduced fake accounts</li>
                                        <li>Better user tracking</li>
                                        <li>Direct communication channel</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Messaging Settings -->
                <div class="glass-fusion rounded-2xl shadow-lg border border-gray-100 overflow-hidden" border border-white/20 dark:border-white/10>
                    <div class="bg-gradient-to-r from-orange-500 to-red-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white flex items-center">
                            <i class="fas fa-comments mr-2"></i>
                            Messaging Settings
                        </h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex-1">
                                <h4 class="text-base font-bold text-gray-900 mb-1">Enable LINE Messaging</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Allow sending messages through LINE Messaging API</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="enable_line_messaging" value="1" class="sr-only peer"
                                    {{ old('enable_line_messaging', $settings->enable_line_messaging) ? 'checked' : '' }}
                                    x-model="enableMessaging">
                                <div class="w-14 h-7 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 rounded-full peer peer-checked:after:translate-x-7 peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:glass-fusion after:border-gray-300 dark:border-gray-600 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-orange-500 peer-checked:to-red-600" border border-white/20 dark:border-white/10></div>
                            </label>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-hand-peace text-orange-500 mr-1"></i> Welcome Message
                            </label>
                            <textarea name="welcome_message" rows="3"
                                class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all"
                                placeholder="ยินดีต้อนรับสู่ระบบ Affiliate! 🎉">{{ old('welcome_message', $settings->welcome_message) }}</textarea>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Sent when user adds your LINE OA as friend</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-check-circle text-orange-500 mr-1"></i> Registration Success Message
                            </label>
                            <textarea name="registration_success_message" rows="4"
                                class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all"
                                placeholder="🎉 ยินดีด้วย! คุณสมัครสมาชิกสำเร็จแล้ว">{{ old('registration_success_message', $settings->registration_success_message) }}</textarea>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Available variables: <code class="bg-gray-100/50 dark:bg-gray-800/50 px-2 py-1 rounded text-orange-600">{name}</code>, <code class="bg-gray-100/50 dark:bg-gray-800/50 px-2 py-1 rounded text-orange-600">{email}</code>, <code class="bg-gray-100/50 dark:bg-gray-800/50 px-2 py-1 rounded text-orange-600">{referral_code}</code>
                            </p>
                        </div>

                        <div class="p-4 bg-gradient-to-r from-orange-50 to-red-50 rounded-xl border border-orange-200">
                            <div class="flex items-start gap-3">
                                <i class="fas fa-lightbulb text-orange-500 text-lg mt-0.5"></i>
                                <div class="text-sm text-orange-800">
                                    <p class="font-semibold mb-1">Pro Tips</p>
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>Use emojis to make messages friendly</li>
                                        <li>Keep messages concise and clear</li>
                                        <li>Include important information like referral code</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Status Sidebar (Right - 1 column) -->
            <div class="lg:col-span-1 space-y-6">

                <!-- Current Status Card -->
                <div class="bg-gradient-to-br from-green-600 to-emerald-700 rounded-2xl shadow-2xl p-6 text-white sticky top-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-12 h-12 rounded-xl glass-fusion backdrop-blur-sm flex items-center justify-center" border border-white/20 dark:border-white/10>
                            <i class="fas fa-chart-line text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold">System Status</h3>
                            <p class="text-sm text-green-200">Current Configuration</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-3 glass-fusion backdrop-blur-sm rounded-xl" border border-white/20 dark:border-white/10>
                            <span class="text-sm">System:</span>
                            @if($settings->is_active)
                                <span class="px-3 py-1 bg-green-400 rounded-full text-xs font-bold text-green-900">Active</span>
                            @else
                                <span class="px-3 py-1 bg-red-500 rounded-full text-xs font-bold">Inactive</span>
                            @endif
                        </div>

                        <div class="flex items-center justify-between p-3 glass-fusion backdrop-blur-sm rounded-xl" border border-white/20 dark:border-white/10>
                            <span class="text-sm">Login:</span>
                            @if($settings->login_channel_id)
                                <span class="px-3 py-1 glass-fusion rounded-full text-xs font-bold">Connected</span>
                            @else
                                <span class="px-3 py-1 bg-red-500 rounded-full text-xs font-bold">Not Set</span>
                            @endif
                        </div>

                        <div class="flex items-center justify-between p-3 glass-fusion backdrop-blur-sm rounded-xl" border border-white/20 dark:border-white/10>
                            <span class="text-sm">Messaging:</span>
                            @if($settings->enable_line_messaging)
                                <span class="px-3 py-1 bg-blue-400 rounded-full text-xs font-bold text-blue-900">Enabled</span>
                            @else
                                <span class="px-3 py-1 bg-gray-400 rounded-full text-xs font-bold text-gray-900">Disabled</span>
                            @endif
                        </div>

                        <div class="flex items-center justify-between p-3 glass-fusion backdrop-blur-sm rounded-xl" border border-white/20 dark:border-white/10>
                            <span class="text-sm">Required:</span>
                            @if($settings->require_line_registration)
                                <span class="px-3 py-1 bg-yellow-400 rounded-full text-xs font-bold text-yellow-900">Yes</span>
                            @else
                                <span class="px-3 py-1 bg-gray-400 rounded-full text-xs font-bold text-gray-900">No</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Setup Guide -->
                <div class="glass-fusion rounded-2xl shadow-lg border border-gray-100 p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center">
                            <i class="fas fa-book text-green-600"></i>
                        </div>
                        <h3 class="font-bold text-gray-900">Setup Guide</h3>
                    </div>

                    <ol class="space-y-3">
                        <li class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xs font-bold">1</span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">Create LINE Provider</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xs font-bold">2</span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">Create LINE Login Channel</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xs font-bold">3</span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">Copy Channel ID & Secret</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xs font-bold">4</span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">Set Callback URL</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xs font-bold">5</span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">Enable Messaging API</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xs font-bold">6</span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">Configure Webhook</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold">7</span>
                            <span class="text-sm text-gray-700 dark:text-gray-300 font-semibold">Test & Activate</span>
                        </li>
                    </ol>
                </div>

                <!-- Quick Links -->
                <div class="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-2xl border border-blue-200 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-blue-500 flex items-center justify-center">
                            <i class="fas fa-link text-white"></i>
                        </div>
                        <h3 class="font-bold text-blue-900">Quick Links</h3>
                    </div>

                    <div class="space-y-2">
                        <a href="{{ route('admin.line-oa.logs') }}"
                           class="flex items-center gap-2 p-3 glass-fusion rounded-xl hover:shadow-md transition-all group">
                            <i class="fas fa-history text-blue-500"></i>
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 group-hover:text-blue-600">Usage Logs</span>
                        </a>

                        <a href="https://developers.line.biz/console/" target="_blank"
                           class="flex items-center gap-2 p-3 glass-fusion rounded-xl hover:shadow-md transition-all group">
                            <i class="fas fa-external-link-alt text-blue-500"></i>
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 group-hover:text-blue-600">LINE Console</span>
                        </a>

                        <a href="https://developers.line.biz/en/docs/line-login/" target="_blank"
                           class="flex items-center gap-2 p-3 glass-fusion rounded-xl hover:shadow-md transition-all group">
                            <i class="fas fa-book-open text-blue-500"></i>
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 group-hover:text-blue-600">Documentation</span>
                        </a>
                    </div>
                </div>

            </div>
        </div>

        <!-- Save Button -->
        <div class="mt-8">
            <div class="glass-fusion rounded-2xl shadow-lg border border-gray-100 p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-info-circle text-green-500 text-xl"></i>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Make sure to test the LINE integration before activating it
                        </p>
                    </div>
                    <button type="submit" class="px-8 py-3 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-xl hover:from-green-700 hover:to-emerald-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-bold">
                        <i class="fas fa-save mr-2"></i>Save Settings
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Test Connection Results Modal -->
<div id="connectionTestModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="glass-fusion rounded-2xl shadow-2xl max-w-2xl w-full overflow-hidden transform transition-all" border border-white/20 dark:border-white/10>
        <div class="bg-gradient-to-r from-blue-500 to-cyan-600 px-6 py-4">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <i class="fas fa-plug mr-2"></i>
                    LINE API Connection Test
                </h3>
                <button onclick="closeConnectionTestModal()" class="text-white/80 hover:text-white transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>

        <div class="p-6">
            <div id="connectionTestLoading" class="text-center py-8">
                <i class="fas fa-spinner fa-spin text-4xl text-blue-500 mb-4"></i>
                <p class="text-gray-600 dark:text-gray-400 font-semibold">Testing connection...</p>
            </div>

            <div id="connectionTestResults" class="hidden space-y-4">
                <div id="overallStatus" class="p-4 rounded-xl"></div>
                <div id="testDetails" class="space-y-3"></div>
            </div>
        </div>

        <div class="bg-gray-100/50 dark:bg-gray-800/50 px-6 py-4 flex justify-end">
            <button onclick="closeConnectionTestModal()"
                    class="px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 transition font-semibold">
                Close
            </button>
        </div>
    </div>
</div>

<!-- Test Message Modal -->
<div id="testModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4" x-data="{ tab: 'select' }">
    <div class="glass-fusion rounded-2xl shadow-2xl max-w-3xl w-full overflow-hidden transform transition-all max-h-[90vh] flex flex-col" border border-white/20 dark:border-white/10>
        <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-4">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Test LINE Message
                </h3>
                <button onclick="closeTestModal()" class="text-white/80 hover:text-white transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex border-b border-gray-200 dark:border-gray-700 px-6">
            <button type="button" @click="tab = 'select'"
                    class="px-6 py-3 font-semibold transition-colors border-b-2"
                    :class="tab === 'select' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:text-gray-300'">
                <i class="fas fa-users mr-2"></i>Select User
            </button>
            <button type="button" @click="tab = 'manual'"
                    class="px-6 py-3 font-semibold transition-colors border-b-2"
                    :class="tab === 'manual' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:text-gray-300'">
                <i class="fas fa-keyboard mr-2"></i>Manual Input
            </button>
        </div>

        <div class="flex-1 overflow-y-auto">
            <!-- Select User Tab -->
            <div x-show="tab === 'select'" class="p-6 space-y-4">
                <!-- Search Box -->
                <div>
                    <input type="text" id="userSearch" placeholder="Search users by name, email, or LINE display name..."
                           class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
                </div>

                <!-- Users Table -->
                <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                    <div id="usersTableLoading" class="p-8 text-center">
                        <i class="fas fa-spinner fa-spin text-2xl text-green-500 mb-2"></i>
                        <p class="text-gray-600 dark:text-gray-400">Loading users...</p>
                    </div>

                    <div id="usersTableContent" class="hidden">
                        <div class="max-h-96 overflow-y-auto">
                            <table class="w-full">
                                <thead class="bg-gray-100/50 dark:bg-gray-800/50 sticky top-0">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">User</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">LINE Display</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Status</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="usersTableBody" class="divide-y divide-gray-200">
                                    <!-- Users will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="usersTableEmpty" class="hidden p-8 text-center">
                        <i class="fas fa-users-slash text-4xl text-gray-300 mb-3"></i>
                        <p class="text-gray-600 dark:text-gray-400">No users with LINE account found</p>
                    </div>
                </div>
            </div>

            <!-- Manual Input Tab -->
            <div x-show="tab === 'manual'" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-user text-green-500 mr-1"></i> LINE User ID
                    </label>
                    <input type="text" id="manualLineUserId"
                        class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all"
                        placeholder="U1234567890abcdef1234567890abcdef">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">The LINE User ID of the recipient</p>
                </div>
            </div>
        </div>

        <!-- Message Form (Common) -->
        <form method="POST" action="{{ route('admin.line-oa.test-message') }}" class="p-6 border-t border-gray-200 dark:border-gray-700 space-y-4">
            @csrf
            <input type="hidden" name="line_user_id" id="selectedLineUserId">

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-comment text-green-500 mr-1"></i> Test Message
                </label>
                <textarea name="message" rows="3" required
                    class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all"
                    placeholder="Enter your test message here...">ทดสอบการส่งข้อความจากระบบ</textarea>
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="closeTestModal()"
                        class="flex-1 px-4 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 transition font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="flex-1 px-4 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl hover:from-green-600 hover:to-emerald-700 transition shadow-lg font-semibold">
                    <i class="fas fa-paper-plane mr-2"></i>Send Message
                </button>
            </div>
        </form>
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
let lineUsersData = [];

// Show/close test modal
function showTestModal() {
    document.getElementById('testModal').classList.remove('hidden');
    document.getElementById('testModal').classList.add('flex');
    loadLineUsers();
}

function closeTestModal() {
    document.getElementById('testModal').classList.add('hidden');
    document.getElementById('testModal').classList.remove('flex');
}

// Load LINE users
async function loadLineUsers() {
    const loading = document.getElementById('usersTableLoading');
    const content = document.getElementById('usersTableContent');
    const empty = document.getElementById('usersTableEmpty');

    loading.classList.remove('hidden');
    content.classList.add('hidden');
    empty.classList.add('hidden');

    try {
        const response = await fetch('{{ route('admin.line-oa.line-users') }}');
        const data = await response.json();

        lineUsersData = data.data;

        if (lineUsersData.length === 0) {
            loading.classList.add('hidden');
            empty.classList.remove('hidden');
            return;
        }

        renderUsersTable(lineUsersData);
        loading.classList.add('hidden');
        content.classList.remove('hidden');
    } catch (error) {
        console.error('Error loading users:', error);
        loading.classList.add('hidden');
        empty.classList.remove('hidden');
    }
}

// Render users table
function renderUsersTable(users) {
    const tbody = document.getElementById('usersTableBody');
    tbody.innerHTML = '';

    users.forEach(user => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-100/50 dark:bg-gray-800/50';
        tr.innerHTML = `
            <td class="px-4 py-3">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center text-white font-bold mr-3">
                        ${user.name.charAt(0).toUpperCase()}
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-white">${user.name}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">${user.email}</p>
                    </div>
                </div>
            </td>
            <td class="px-4 py-3">
                <p class="text-sm text-gray-900 dark:text-white">${user.line_display_name || 'N/A'}</p>
                <p class="text-xs text-gray-400 font-mono">${user.line_user_id.substring(0, 20)}...</p>
            </td>
            <td class="px-4 py-3">
                ${user.line_verified ?
                    '<span class="inline-flex items-center px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded"><i class="fas fa-check-circle mr-1"></i>Verified</span>' :
                    '<span class="inline-flex items-center px-2 py-1 bg-gray-100/50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-400 text-xs font-semibold rounded">Unverified</span>'
                }
            </td>
            <td class="px-4 py-3 text-center">
                <button type="button" onclick="selectUser('${user.line_user_id}', '${user.name}')"
                        class="px-4 py-2 bg-green-500 text-white text-sm rounded-xl hover:bg-green-600 transition font-semibold">
                    <i class="fas fa-check mr-1"></i>Select
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// Select user
function selectUser(lineUserId, userName) {
    document.getElementById('selectedLineUserId').value = lineUserId;

    // Show confirmation
    alert(`Selected user: ${userName}\nLINE User ID: ${lineUserId}`);
}

// Search users
let searchTimeout;
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('userSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const searchTerm = e.target.value.toLowerCase();
                const filtered = lineUsersData.filter(user => {
                    return user.name.toLowerCase().includes(searchTerm) ||
                           user.email.toLowerCase().includes(searchTerm) ||
                           (user.line_display_name && user.line_display_name.toLowerCase().includes(searchTerm)) ||
                           user.line_user_id.toLowerCase().includes(searchTerm);
                });
                renderUsersTable(filtered);

                if (filtered.length === 0) {
                    document.getElementById('usersTableContent').classList.add('hidden');
                    document.getElementById('usersTableEmpty').classList.remove('hidden');
                } else {
                    document.getElementById('usersTableContent').classList.remove('hidden');
                    document.getElementById('usersTableEmpty').classList.add('hidden');
                }
            }, 300);
        });
    }

    // Handle manual input
    const manualInput = document.getElementById('manualLineUserId');
    if (manualInput) {
        manualInput.addEventListener('input', function(e) {
            document.getElementById('selectedLineUserId').value = e.target.value;
        });
    }
});

// Connection test modal
function closeConnectionTestModal() {
    document.getElementById('connectionTestModal').classList.add('hidden');
    document.getElementById('connectionTestModal').classList.remove('flex');
}

// Test LINE API connection
async function testConnection() {
    const modal = document.getElementById('connectionTestModal');
    const loading = document.getElementById('connectionTestLoading');
    const results = document.getElementById('connectionTestResults');
    const overallStatus = document.getElementById('overallStatus');
    const testDetails = document.getElementById('testDetails');

    // Show modal with loading state
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    loading.classList.remove('hidden');
    results.classList.add('hidden');

    try {
        const response = await fetch('{{ route('admin.line-oa.test-connection') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        const data = await response.json();

        // Hide loading, show results
        loading.classList.add('hidden');
        results.classList.remove('hidden');

        // Display overall status
        const statusColors = {
            'success': 'bg-green-100 border-green-300 text-green-800',
            'warning': 'bg-yellow-100 border-yellow-300 text-yellow-800',
            'error': 'bg-red-100 border-red-300 text-red-800'
        };

        const statusIcons = {
            'success': 'fa-check-circle text-green-600',
            'warning': 'fa-exclamation-triangle text-yellow-600',
            'error': 'fa-times-circle text-red-600'
        };

        overallStatus.className = `p-4 rounded-xl border ${statusColors[data.overall_status]}`;
        overallStatus.innerHTML = `
            <div class="flex items-center">
                <i class="fas ${statusIcons[data.overall_status]} text-2xl mr-3"></i>
                <div>
                    <h4 class="font-bold text-lg">Overall Status: ${data.overall_status.toUpperCase()}</h4>
                    <p class="text-sm">Connection test completed</p>
                </div>
            </div>
        `;

        // Display test details
        testDetails.innerHTML = '';
        for (const [key, test] of Object.entries(data.tests)) {
            const testCard = document.createElement('div');
            testCard.className = `p-4 rounded-xl border ${statusColors[test.status]}`;

            let detailsHtml = '';
            if (test.bot_info) {
                detailsHtml = `
                    <div class="mt-2 text-sm space-y-1">
                        <p><strong>Bot Name:</strong> ${test.bot_info.displayName}</p>
                        <p class="text-xs opacity-75">User ID: ${test.bot_info.userId}</p>
                    </div>
                `;
            }

            testCard.innerHTML = `
                <div class="flex items-start">
                    <i class="fas ${statusIcons[test.status]} text-xl mr-3 mt-1"></i>
                    <div class="flex-1">
                        <h5 class="font-bold capitalize">${key.replace(/_/g, ' ')}</h5>
                        <p class="text-sm mt-1">${test.message}</p>
                        ${detailsHtml}
                    </div>
                </div>
            `;
            testDetails.appendChild(testCard);
        }

    } catch (error) {
        loading.classList.add('hidden');
        results.classList.remove('hidden');

        overallStatus.className = 'p-4 rounded-xl border bg-red-100 border-red-300 text-red-800';
        overallStatus.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-times-circle text-red-600 text-2xl mr-3"></i>
                <div>
                    <h4 class="font-bold text-lg">Connection Test Failed</h4>
                    <p class="text-sm">${error.message}</p>
                </div>
            </div>
        `;
        testDetails.innerHTML = '';
    }
}

// Close modal on ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeTestModal();
        closeConnectionTestModal();
    }
});
</script>
@endsection
