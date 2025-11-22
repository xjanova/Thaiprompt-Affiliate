@extends('layouts.admin-v3')

@section('title', 'ตั้งค่า LINE Official Account')

@section('content')
<div class="container-fluid px-4 py-6"
     x-data="{
        requireLineReg: {{ old('require_line_registration', $settings->require_line_registration) ? 'true' : 'false' }},
        enableMessaging: {{ old('enable_line_messaging', $settings->enable_line_messaging) ? 'true' : 'false' }},
        isActive: {{ old('is_active', $settings->is_active) ? 'true' : 'false' }},
        showLoginSecret: false,
        showChannelSecret: false,
        formDirty: false,
        connectionStatus: 'checking',

        init() {
            this.checkConnection();
            // Track form changes
            this.$watch('requireLineReg', () => this.formDirty = true);
            this.$watch('enableMessaging', () => this.formDirty = true);
            this.$watch('isActive', () => this.formDirty = true);
        },

        async checkConnection() {
            try {
                const response = await fetch('{{ route('admin.line-oa.test-connection') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                this.connectionStatus = data.overall_status;
            } catch (error) {
                this.connectionStatus = 'error';
            }
        },

        copyToClipboard(text, button) {
            navigator.clipboard.writeText(text).then(() => {
                const icon = button.querySelector('i');
                icon.classList.remove('fa-copy');
                icon.classList.add('fa-check');
                setTimeout(() => {
                    icon.classList.remove('fa-check');
                    icon.classList.add('fa-copy');
                }, 2000);
            });
        }
    }">

    <!-- Enhanced Animated Header -->
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-green-500 via-emerald-600 to-teal-700 p-8 shadow-2xl">
        <!-- Animated particles background -->
        <div class="absolute inset-0 opacity-30">
            <div class="absolute top-0 left-0 w-72 h-72 bg-white/10 rounded-full blur-3xl animate-float"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 bg-emerald-300/10 rounded-full blur-3xl animate-float-delayed"></div>
            <div class="absolute top-1/2 left-1/2 w-64 h-64 bg-teal-300/10 rounded-full blur-2xl animate-pulse-slow"></div>
        </div>

        <!-- Grid pattern overlay -->
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>

        <div class="relative flex items-center justify-between">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-16 h-16 rounded-2xl glass-fusion backdrop-blur-sm flex items-center justify-center border border-white/20 shadow-xl hover:scale-110 transition-transform duration-300">
                        <svg class="w-9 h-9 text-white drop-shadow-lg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-1 drop-shadow-lg">LINE Official Account</h1>
                        <p class="text-green-100 text-sm">ระบบเชื่อมต่อ LINE Login & Messaging API</p>
                    </div>
                </div>
            </div>

            <div class="flex gap-3">
                <!-- Status Indicator -->
                <div class="hidden lg:flex items-center gap-2 px-4 py-2 glass-fusion backdrop-blur-md border border-white/30 rounded-xl">
                    <div class="flex items-center gap-2">
                        <div :class="{
                            'w-2 h-2 rounded-full animate-pulse': true,
                            'bg-green-300': connectionStatus === 'success',
                            'bg-yellow-300': connectionStatus === 'warning',
                            'bg-red-300': connectionStatus === 'error',
                            'bg-gray-300': connectionStatus === 'checking'
                        }"></div>
                        <span class="text-white text-sm font-medium">
                            <span x-show="connectionStatus === 'success'">Connected</span>
                            <span x-show="connectionStatus === 'warning'">Warning</span>
                            <span x-show="connectionStatus === 'error'">Offline</span>
                            <span x-show="connectionStatus === 'checking'">Checking...</span>
                        </span>
                    </div>
                </div>

                <button type="button" onclick="testConnection()"
                        class="px-6 py-3 glass-fusion backdrop-blur-md border border-white/30 text-white rounded-xl hover:bg-white/10 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <i class="fas fa-plug me-2"></i>
                    <span class="hidden sm:inline">Test Connection</span>
                    <span class="sm:hidden">Test</span>
                </button>

                <button type="button" onclick="showTestModal()"
                        class="px-6 py-3 glass-fusion backdrop-blur-md border border-white/30 text-white rounded-xl hover:bg-white/10 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <i class="fas fa-paper-plane me-2"></i>
                    <span class="hidden sm:inline">Test Message</span>
                    <span class="sm:hidden">Send</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-200 dark:border-green-800 animate-fade-in">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-full bg-green-500 flex items-center justify-center mr-3 shadow-lg">
                    <i class="fas fa-check text-white"></i>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-green-900 dark:text-green-100">Success!</p>
                    <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="text-green-500 hover:text-green-700 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 rounded-xl bg-gradient-to-r from-red-50 to-pink-50 dark:from-red-900/20 dark:to-pink-900/20 border border-red-200 dark:border-red-800 animate-fade-in">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-full bg-red-500 flex items-center justify-center mr-3 shadow-lg">
                    <i class="fas fa-exclamation-circle text-white"></i>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-red-900 dark:text-red-100">Error!</p>
                    <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="text-red-500 hover:text-red-700 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.line-oa.update') }}" @input="formDirty = true">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Settings (Left - 2 columns) -->
            <div class="lg:col-span-2 space-y-6">

                <!-- System Status Card -->
                <div class="glass-fusion rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-2xl transition-shadow duration-300">
                    <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white flex items-center">
                            <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center mr-2">
                                <i class="fas fa-power-off"></i>
                            </div>
                            System Control
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-1">เปิดใช้งานระบบ LINE OA</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">เปิดการเชื่อมต่อกับ LINE Official Account</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer group">
                                <input type="checkbox" id="is_active" name="is_active" value="1" class="sr-only peer"
                                    {{ old('is_active', $settings->is_active) ? 'checked' : '' }}
                                    x-model="isActive">
                                <div class="w-20 h-10 bg-gray-300 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer peer-checked:after:translate-x-10 peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 dark:after:border-gray-600 after:border after:rounded-full after:h-9 after:w-9 after:transition-all after:shadow-lg peer-checked:bg-gradient-to-r peer-checked:from-green-500 peer-checked:to-emerald-600 group-hover:shadow-xl transition-shadow"></div>
                                <div class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-white pointer-events-none transition-opacity" :class="isActive ? 'opacity-0' : 'opacity-100'">
                                    OFF
                                </div>
                                <div class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-white pointer-events-none transition-opacity" :class="isActive ? 'opacity-100' : 'opacity-0'">
                                    ON
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- LINE Login Channel Configuration -->
                <div class="glass-fusion rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-2xl transition-shadow duration-300">
                    <div class="bg-gradient-to-r from-blue-500 to-cyan-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white flex items-center">
                            <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center mr-2">
                                <i class="fas fa-sign-in-alt"></i>
                            </div>
                            LINE Login Channel (OAuth)
                        </h3>
                    </div>
                    <div class="p-6 space-y-6">
                        <!-- Info Alert -->
                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-blue-500 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-info-circle text-white"></i>
                                </div>
                                <p class="text-sm text-blue-800 dark:text-blue-200">
                                    <strong>หมายเหตุ:</strong> LINE Login Channel ใช้สำหรับการยืนยันตัวตน (เข้าสู่ระบบ/สมัครสมาชิก) ซึ่งแยกต่างหากจาก Messaging API Channel
                                </p>
                            </div>
                        </div>

                        <!-- Channel ID -->
                        <div class="group">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-id-card text-blue-500 mr-1"></i> LINE Login Channel ID <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                                    <i class="fas fa-hashtag"></i>
                                </div>
                                <input type="text" name="login_channel_id" value="{{ old('login_channel_id', $settings->login_channel_id) }}"
                                    class="w-full pl-12 pr-4 py-3 border border-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-sm hover:shadow-md"
                                    placeholder="1234567890">
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 ml-1">
                                <i class="fas fa-arrow-right text-blue-400 mr-1"></i>
                                จาก LINE Developers Console → LINE Login Channel → Basic Settings
                            </p>
                        </div>

                        <!-- Channel Secret -->
                        <div class="group">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-lock text-blue-500 mr-1"></i> LINE Login Channel Secret <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                                    <i class="fas fa-key"></i>
                                </div>
                                <input :type="showLoginSecret ? 'text' : 'password'" name="channel_secret"
                                    value="{{ old('channel_secret', $settings->channel_secret) }}"
                                    class="w-full pl-12 pr-24 py-3 border border-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-sm hover:shadow-md font-mono text-sm"
                                    placeholder="••••••••••••••••">
                                <div class="absolute right-2 top-1/2 -translate-y-1/2 flex gap-1">
                                    <button type="button" @click="showLoginSecret = !showLoginSecret"
                                        class="px-3 py-2 text-gray-500 hover:text-blue-600 dark:hover:text-blue-400 transition rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <i :class="showLoginSecret ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                                    </button>
                                    <button type="button" @click="copyToClipboard('{{ $settings->channel_secret }}', $el)"
                                        class="px-3 py-2 text-gray-500 hover:text-blue-600 dark:hover:text-blue-400 transition rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                                        title="Copy to clipboard">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 ml-1">
                                <i class="fas fa-arrow-right text-blue-400 mr-1"></i>
                                จาก LINE Developers Console → LINE Login Channel → Basic Settings
                            </p>
                        </div>

                        <!-- Redirect URI -->
                        <div class="group">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-link text-blue-500 mr-1"></i> Redirect URI (Callback URL) <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                                    <i class="fas fa-globe"></i>
                                </div>
                                <input type="url" name="redirect_uri" value="{{ old('redirect_uri', $settings->redirect_uri) }}"
                                    class="w-full pl-12 pr-16 py-3 border border-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-sm hover:shadow-md"
                                    placeholder="{{ route('line.callback') }}"
                                    required>
                                <button type="button" @click="copyToClipboard('{{ route('line.callback') }}', $el)"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 px-3 py-2 text-gray-500 hover:text-blue-600 dark:hover:text-blue-400 transition rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                                    title="Copy suggested URL">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                            <div class="mt-2 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                                <p class="text-xs text-gray-600 dark:text-gray-400">
                                    <strong class="text-blue-600 dark:text-blue-400">แนะนำ:</strong>
                                    <code class="ml-1 px-2 py-1 bg-white dark:bg-gray-900 rounded text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-800">{{ route('line.callback') }}</code>
                                </p>
                            </div>
                            <div class="mt-2 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                                <p class="text-xs text-red-800 dark:text-red-300">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    <strong>สำคัญ:</strong> URL นี้ต้องตรงกับที่ลงทะเบียนใน LINE Developers Console → LINE Login Channel → Callback URL มิฉะนั้นจะได้รับ error "400 Bad Request - Invalid redirect_uri"
                                </p>
                            </div>
                        </div>

                        <!-- LIFF ID -->
                        <div class="group">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-window-maximize text-blue-500 mr-1"></i> LIFF ID <span class="text-gray-400 text-xs">(ไม่บังคับ)</span>
                            </label>
                            <div class="relative">
                                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                                <input type="text" name="liff_id" value="{{ old('liff_id', $settings->liff_id) }}"
                                    class="w-full pl-12 pr-4 py-3 border border-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-sm hover:shadow-md"
                                    placeholder="1234567890-abcdefgh">
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 ml-1">
                                <i class="fas fa-arrow-right text-blue-400 mr-1"></i>
                                จาก LINE Developers Console → LINE Login Channel → LIFF tab
                            </p>
                        </div>
                    </div>
                </div>

                <!-- LINE Messaging API Configuration -->
                <div class="glass-fusion rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-2xl transition-shadow duration-300">
                    <div class="bg-gradient-to-r from-green-500 to-teal-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white flex items-center">
                            <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center mr-2">
                                <i class="fas fa-comments"></i>
                            </div>
                            LINE Messaging API Channel <span class="text-green-200 text-sm ml-2">(ไม่บังคับ)</span>
                        </h3>
                    </div>
                    <div class="p-6 space-y-6">
                        <!-- Info Alert -->
                        <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-800">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-green-500 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-info-circle text-white"></i>
                                </div>
                                <p class="text-sm text-green-800 dark:text-green-200">
                                    <strong>หมายเหตุ:</strong> LINE Messaging API Channel ใช้สำหรับส่งข้อความถึงผู้ใช้ ซึ่งแยกต่างหากจาก LINE Login Channel
                                </p>
                            </div>
                        </div>

                        <!-- Messaging Channel ID -->
                        <div class="group">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-hashtag text-green-500 mr-1"></i> Messaging API Channel ID <span class="text-gray-400 text-xs">(ไม่บังคับ)</span>
                            </label>
                            <div class="relative">
                                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                                    <i class="fas fa-id-badge"></i>
                                </div>
                                <input type="text" name="messaging_channel_id" value="{{ old('messaging_channel_id', $settings->messaging_channel_id) }}"
                                    class="w-full pl-12 pr-4 py-3 border border-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all shadow-sm hover:shadow-md"
                                    placeholder="1234567890">
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 ml-1">
                                <i class="fas fa-arrow-right text-green-400 mr-1"></i>
                                จาก LINE Developers Console → Messaging API Channel → Basic Settings
                            </p>
                        </div>

                        <!-- Channel Access Token -->
                        <div class="group">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-ticket-alt text-green-500 mr-1"></i> Channel Access Token (Long-lived)
                            </label>
                            <div class="relative" x-data="{ charCount: {{ strlen(old('channel_access_token', $settings->channel_access_token)) }} }">
                                <textarea name="channel_access_token" rows="3"
                                    @input="charCount = $el.value.length"
                                    class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all font-mono text-sm shadow-sm hover:shadow-md resize-none"
                                    placeholder="ระบุ Channel Access Token แบบ Long-lived...">{{ old('channel_access_token', $settings->channel_access_token) }}</textarea>
                                <div class="absolute bottom-2 right-2 flex items-center gap-2">
                                    <span class="text-xs text-gray-400 bg-white dark:bg-gray-900 px-2 py-1 rounded border border-gray-200 dark:border-gray-700">
                                        <i class="fas fa-text-width mr-1"></i>
                                        <span x-text="charCount"></span> chars
                                    </span>
                                    @if($settings->channel_access_token)
                                    <button type="button" @click="copyToClipboard('{{ $settings->channel_access_token }}', $el)"
                                        class="px-2 py-1 text-gray-500 hover:text-green-600 dark:hover:text-green-400 transition rounded bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700"
                                        title="Copy token">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                    @endif
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 ml-1">
                                <i class="fas fa-arrow-right text-green-400 mr-1"></i>
                                จาก LINE Developers Console → Messaging API Channel → Messaging API tab
                            </p>
                        </div>

                        <!-- Webhook URL Info -->
                        <div class="p-4 bg-gradient-to-r from-green-50 to-teal-50 dark:from-green-900/20 dark:to-teal-900/20 rounded-xl border border-green-200 dark:border-green-800">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-green-500 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-webhook text-white"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="font-semibold text-green-900 dark:text-green-100 mb-2">Webhook URL</p>
                                    <div class="flex items-center gap-2">
                                        <code class="flex-1 px-3 py-2 bg-white dark:bg-gray-900 rounded text-xs text-green-700 dark:text-green-300 border border-green-300 dark:border-green-700 font-mono">{{ url('/api/webhook/line') }}</code>
                                        <button type="button" @click="copyToClipboard('{{ url('/api/webhook/line') }}', $el)"
                                            class="px-3 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition shadow-sm"
                                            title="Copy webhook URL">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                    <p class="text-xs text-green-600 dark:text-green-400 mt-2">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                        ลงทะเบียน URL นี้ใน LINE Developers Console → Messaging API Channel → Webhook settings
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Registration Settings -->
                <div class="glass-fusion rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-2xl transition-shadow duration-300">
                    <div class="bg-gradient-to-r from-purple-500 to-pink-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white flex items-center">
                            <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center mr-2">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            การตั้งค่าการสมัครสมาชิก
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex-1 pr-4">
                                <h4 class="text-base font-bold text-gray-900 dark:text-white mb-1">
                                    <i class="fas fa-shield-alt text-purple-500 mr-2"></i>
                                    บังคับใช้ LINE ในการสมัคร
                                </h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">บังคับให้ผู้ใช้ต้องล็อกอินด้วย LINE ก่อนสมัครสมาชิก (KYC Level 1)</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer group">
                                <input type="checkbox" name="require_line_registration" value="1" class="sr-only peer"
                                    {{ old('require_line_registration', $settings->require_line_registration) ? 'checked' : '' }}
                                    x-model="requireLineReg">
                                <div class="w-16 h-8 bg-gray-300 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer peer-checked:after:translate-x-8 peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 dark:after:border-gray-600 after:border after:rounded-full after:h-7 after:w-7 after:transition-all after:shadow-lg peer-checked:bg-gradient-to-r peer-checked:from-purple-500 peer-checked:to-pink-600 group-hover:shadow-xl transition-shadow"></div>
                            </label>
                        </div>

                        <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl border border-purple-200 dark:border-purple-800">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-purple-500 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-check-circle text-white"></i>
                                </div>
                                <div class="text-sm text-purple-800 dark:text-purple-200">
                                    <p class="font-semibold mb-2">ประโยชน์ด้านความปลอดภัย</p>
                                    <ul class="space-y-1">
                                        <li class="flex items-center gap-2">
                                            <i class="fas fa-check text-purple-500 text-xs"></i>
                                            ยืนยันตัวตนผ่าน LINE
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <i class="fas fa-check text-purple-500 text-xs"></i>
                                            ลดบัญชีปลอม
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <i class="fas fa-check text-purple-500 text-xs"></i>
                                            ติดตามผู้ใช้ได้ดีขึ้น
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <i class="fas fa-check text-purple-500 text-xs"></i>
                                            ช่องทางสื่อสารตรง
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Messaging Settings -->
                <div class="glass-fusion rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-2xl transition-shadow duration-300" x-data="{
                    welcomeCharCount: {{ strlen(old('welcome_message', $settings->welcome_message)) }},
                    registrationCharCount: {{ strlen(old('registration_success_message', $settings->registration_success_message)) }}
                }">
                    <div class="bg-gradient-to-r from-orange-500 to-red-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white flex items-center">
                            <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center mr-2">
                                <i class="fas fa-envelope"></i>
                            </div>
                            การตั้งค่าข้อความ
                        </h3>
                    </div>
                    <div class="p-6 space-y-6">
                        <!-- Enable Messaging Toggle -->
                        <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex-1 pr-4">
                                <h4 class="text-base font-bold text-gray-900 dark:text-white mb-1">
                                    <i class="fas fa-paper-plane text-orange-500 mr-2"></i>
                                    เปิดใช้งานส่งข้อความ
                                </h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">อนุญาตให้ส่งข้อความผ่าน LINE Messaging API</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer group">
                                <input type="checkbox" name="enable_line_messaging" value="1" class="sr-only peer"
                                    {{ old('enable_line_messaging', $settings->enable_line_messaging) ? 'checked' : '' }}
                                    x-model="enableMessaging">
                                <div class="w-16 h-8 bg-gray-300 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 dark:peer-focus:ring-orange-800 rounded-full peer peer-checked:after:translate-x-8 peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 dark:after:border-gray-600 after:border after:rounded-full after:h-7 after:w-7 after:transition-all after:shadow-lg peer-checked:bg-gradient-to-r peer-checked:from-orange-500 peer-checked:to-red-600 group-hover:shadow-xl transition-shadow"></div>
                            </label>
                        </div>

                        <!-- Welcome Message -->
                        <div class="group">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-hand-peace text-orange-500 mr-1"></i> ข้อความต้อนรับ
                            </label>
                            <div class="relative">
                                <textarea name="welcome_message" rows="3"
                                    @input="welcomeCharCount = $el.value.length"
                                    class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all shadow-sm hover:shadow-md resize-none"
                                    placeholder="ยินดีต้อนรับสู่ระบบ Affiliate! 🎉">{{ old('welcome_message', $settings->welcome_message) }}</textarea>
                                <div class="absolute bottom-2 right-2 text-xs text-gray-400 bg-white dark:bg-gray-900 px-2 py-1 rounded border border-gray-200 dark:border-gray-700">
                                    <i class="fas fa-text-width mr-1"></i>
                                    <span x-text="welcomeCharCount"></span> / 500
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 ml-1">
                                ส่งเมื่อผู้ใช้เพิ่ม LINE OA เป็นเพื่อน
                            </p>
                        </div>

                        <!-- Registration Success Message -->
                        <div class="group">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-check-circle text-orange-500 mr-1"></i> ข้อความสมัครสมาชิกสำเร็จ
                            </label>
                            <div class="relative">
                                <textarea name="registration_success_message" rows="4"
                                    @input="registrationCharCount = $el.value.length"
                                    class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all shadow-sm hover:shadow-md resize-none"
                                    placeholder="🎉 ยินดีด้วย! คุณสมัครสมาชิกสำเร็จแล้ว">{{ old('registration_success_message', $settings->registration_success_message) }}</textarea>
                                <div class="absolute bottom-2 right-2 text-xs text-gray-400 bg-white dark:bg-gray-900 px-2 py-1 rounded border border-gray-200 dark:border-gray-700">
                                    <i class="fas fa-text-width mr-1"></i>
                                    <span x-text="registrationCharCount"></span> / 1000
                                </div>
                            </div>
                            <div class="mt-2 p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                                <p class="text-xs text-orange-800 dark:text-orange-200">
                                    <strong>ตัวแปรที่ใช้ได้:</strong>
                                    <code class="ml-1 px-2 py-1 bg-white dark:bg-gray-900 rounded text-orange-600 dark:text-orange-400">{name}</code>
                                    <code class="ml-1 px-2 py-1 bg-white dark:bg-gray-900 rounded text-orange-600 dark:text-orange-400">{email}</code>
                                    <code class="ml-1 px-2 py-1 bg-white dark:bg-gray-900 rounded text-orange-600 dark:text-orange-400">{referral_code}</code>
                                </p>
                            </div>
                        </div>

                        <!-- Pro Tips -->
                        <div class="p-4 bg-gradient-to-r from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20 rounded-xl border border-orange-200 dark:border-orange-800">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-orange-500 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-lightbulb text-white"></i>
                                </div>
                                <div class="text-sm text-orange-800 dark:text-orange-200">
                                    <p class="font-semibold mb-2">เคล็ดลับการเขียนข้อความ</p>
                                    <ul class="space-y-1">
                                        <li class="flex items-center gap-2">
                                            <i class="fas fa-star text-orange-500 text-xs"></i>
                                            ใช้ emoji ให้น่าสนใจ
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <i class="fas fa-star text-orange-500 text-xs"></i>
                                            เขียนสั้น กระชับ ชัดเจน
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <i class="fas fa-star text-orange-500 text-xs"></i>
                                            ใส่ข้อมูลสำคัญ เช่น รหัสแนะนำ
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Status Sidebar (Right - 1 column) -->
            <div class="lg:col-span-1 space-y-6">

                <!-- Real-time Connection Status Card -->
                <div class="bg-gradient-to-br from-green-600 to-emerald-700 dark:from-green-700 dark:to-emerald-800 rounded-2xl shadow-2xl p-6 text-white sticky top-6 hover:scale-105 transition-transform duration-300">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-12 h-12 rounded-xl glass-fusion backdrop-blur-sm flex items-center justify-center border border-white/20 shadow-lg">
                            <i class="fas fa-signal text-2xl animate-pulse"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold">System Status</h3>
                            <p class="text-sm text-green-200">Real-time monitoring</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <!-- System Status -->
                        <div class="flex items-center justify-between p-3 glass-fusion backdrop-blur-sm rounded-xl border border-white/20 hover:bg-white/10 transition-colors">
                            <div class="flex items-center gap-2">
                                <div :class="{
                                    'w-2 h-2 rounded-full animate-pulse': true,
                                    'bg-green-300': isActive,
                                    'bg-gray-400': !isActive
                                }"></div>
                                <span class="text-sm font-medium">System:</span>
                            </div>
                            <span :class="{
                                'px-3 py-1 rounded-full text-xs font-bold': true,
                                'bg-green-400 text-green-900': isActive,
                                'bg-gray-400 text-gray-900': !isActive
                            }" x-text="isActive ? 'Active' : 'Inactive'"></span>
                        </div>

                        <!-- Connection Status -->
                        <div class="flex items-center justify-between p-3 glass-fusion backdrop-blur-sm rounded-xl border border-white/20 hover:bg-white/10 transition-colors">
                            <div class="flex items-center gap-2">
                                <div :class="{
                                    'w-2 h-2 rounded-full': true,
                                    'animate-pulse bg-green-300': connectionStatus === 'success',
                                    'animate-pulse bg-yellow-300': connectionStatus === 'warning',
                                    'animate-pulse bg-red-300': connectionStatus === 'error',
                                    'animate-spin bg-gray-300': connectionStatus === 'checking'
                                }"></div>
                                <span class="text-sm font-medium">Connection:</span>
                            </div>
                            <span :class="{
                                'px-3 py-1 rounded-full text-xs font-bold': true,
                                'bg-green-400 text-green-900': connectionStatus === 'success',
                                'bg-yellow-400 text-yellow-900': connectionStatus === 'warning',
                                'bg-red-400 text-red-900': connectionStatus === 'error',
                                'bg-gray-400 text-gray-900': connectionStatus === 'checking'
                            }">
                                <span x-show="connectionStatus === 'success'">Connected</span>
                                <span x-show="connectionStatus === 'warning'">Warning</span>
                                <span x-show="connectionStatus === 'error'">Error</span>
                                <span x-show="connectionStatus === 'checking'">Checking...</span>
                            </span>
                        </div>

                        <!-- Login Channel -->
                        <div class="flex items-center justify-between p-3 glass-fusion backdrop-blur-sm rounded-xl border border-white/20 hover:bg-white/10 transition-colors">
                            <span class="text-sm font-medium">Login:</span>
                            @if($settings->login_channel_id)
                                <span class="px-3 py-1 bg-blue-400 rounded-full text-xs font-bold text-blue-900">
                                    <i class="fas fa-check mr-1"></i>Set
                                </span>
                            @else
                                <span class="px-3 py-1 bg-gray-400 rounded-full text-xs font-bold text-gray-900">Not Set</span>
                            @endif
                        </div>

                        <!-- Messaging -->
                        <div class="flex items-center justify-between p-3 glass-fusion backdrop-blur-sm rounded-xl border border-white/20 hover:bg-white/10 transition-colors">
                            <span class="text-sm font-medium">Messaging:</span>
                            <span :class="{
                                'px-3 py-1 rounded-full text-xs font-bold': true,
                                'bg-blue-400 text-blue-900': enableMessaging,
                                'bg-gray-400 text-gray-900': !enableMessaging
                            }" x-text="enableMessaging ? 'Enabled' : 'Disabled'"></span>
                        </div>

                        <!-- Registration -->
                        <div class="flex items-center justify-between p-3 glass-fusion backdrop-blur-sm rounded-xl border border-white/20 hover:bg-white/10 transition-colors">
                            <span class="text-sm font-medium">Required:</span>
                            <span :class="{
                                'px-3 py-1 rounded-full text-xs font-bold': true,
                                'bg-yellow-400 text-yellow-900': requireLineReg,
                                'bg-gray-400 text-gray-900': !requireLineReg
                            }" x-text="requireLineReg ? 'Yes' : 'No'"></span>
                        </div>
                    </div>

                    <!-- Refresh Button -->
                    <button type="button" @click="checkConnection()"
                        class="w-full mt-4 py-2 bg-white/20 hover:bg-white/30 rounded-xl transition-colors text-sm font-semibold flex items-center justify-center gap-2 border border-white/30">
                        <i class="fas fa-sync-alt" :class="{ 'fa-spin': connectionStatus === 'checking' }"></i>
                        Refresh Status
                    </button>
                </div>

                <!-- Setup Guide -->
                <div class="glass-fusion rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6 hover:shadow-2xl transition-all duration-300">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center shadow-lg">
                            <i class="fas fa-book text-white"></i>
                        </div>
                        <h3 class="font-bold text-gray-900 dark:text-white">คู่มือการติดตั้ง</h3>
                    </div>

                    <ol class="space-y-3">
                        <li class="flex items-start gap-3 group cursor-pointer hover:translate-x-1 transition-transform">
                            <span class="flex-shrink-0 w-7 h-7 rounded-full bg-gradient-to-br from-green-400 to-green-600 text-white flex items-center justify-center text-xs font-bold shadow-lg">1</span>
                            <span class="text-sm text-gray-700 dark:text-gray-300 group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors">สร้าง LINE Provider</span>
                        </li>
                        <li class="flex items-start gap-3 group cursor-pointer hover:translate-x-1 transition-transform">
                            <span class="flex-shrink-0 w-7 h-7 rounded-full bg-gradient-to-br from-green-400 to-green-600 text-white flex items-center justify-center text-xs font-bold shadow-lg">2</span>
                            <span class="text-sm text-gray-700 dark:text-gray-300 group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors">สร้าง LINE Login Channel</span>
                        </li>
                        <li class="flex items-start gap-3 group cursor-pointer hover:translate-x-1 transition-transform">
                            <span class="flex-shrink-0 w-7 h-7 rounded-full bg-gradient-to-br from-green-400 to-green-600 text-white flex items-center justify-center text-xs font-bold shadow-lg">3</span>
                            <span class="text-sm text-gray-700 dark:text-gray-300 group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors">คัดลอก Channel ID & Secret</span>
                        </li>
                        <li class="flex items-start gap-3 group cursor-pointer hover:translate-x-1 transition-transform">
                            <span class="flex-shrink-0 w-7 h-7 rounded-full bg-gradient-to-br from-green-400 to-green-600 text-white flex items-center justify-center text-xs font-bold shadow-lg">4</span>
                            <span class="text-sm text-gray-700 dark:text-gray-300 group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors">ตั้งค่า Callback URL</span>
                        </li>
                        <li class="flex items-start gap-3 group cursor-pointer hover:translate-x-1 transition-transform">
                            <span class="flex-shrink-0 w-7 h-7 rounded-full bg-gradient-to-br from-green-400 to-green-600 text-white flex items-center justify-center text-xs font-bold shadow-lg">5</span>
                            <span class="text-sm text-gray-700 dark:text-gray-300 group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors">เปิดใช้ Messaging API</span>
                        </li>
                        <li class="flex items-start gap-3 group cursor-pointer hover:translate-x-1 transition-transform">
                            <span class="flex-shrink-0 w-7 h-7 rounded-full bg-gradient-to-br from-green-400 to-green-600 text-white flex items-center justify-center text-xs font-bold shadow-lg">6</span>
                            <span class="text-sm text-gray-700 dark:text-gray-300 group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors">ตั้งค่า Webhook</span>
                        </li>
                        <li class="flex items-start gap-3 group cursor-pointer hover:translate-x-1 transition-transform">
                            <span class="flex-shrink-0 w-7 h-7 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 text-white flex items-center justify-center text-xs font-bold shadow-lg">7</span>
                            <span class="text-sm text-gray-700 dark:text-gray-300 font-semibold group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">ทดสอบและเปิดใช้งาน</span>
                        </li>
                    </ol>
                </div>

                <!-- Quick Links -->
                <div class="glass-fusion rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6 hover:shadow-2xl transition-all duration-300">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center shadow-lg">
                            <i class="fas fa-link text-white"></i>
                        </div>
                        <h3 class="font-bold text-gray-900 dark:text-white">ลิงก์ด่วน</h3>
                    </div>

                    <div class="space-y-2">
                        <a href="{{ route('admin.line-oa.logs') }}"
                           class="flex items-center gap-3 p-3 glass-fusion rounded-xl hover:shadow-md transition-all group border border-gray-200 dark:border-gray-600 hover:border-blue-500 dark:hover:border-blue-400">
                            <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                                <i class="fas fa-history text-blue-500"></i>
                            </div>
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 group-hover:text-blue-600 dark:group-hover:text-blue-400">Usage Logs</span>
                            <i class="fas fa-arrow-right ml-auto text-gray-400 group-hover:text-blue-500 transition-colors"></i>
                        </a>

                        <a href="https://developers.line.biz/console/" target="_blank"
                           class="flex items-center gap-3 p-3 glass-fusion rounded-xl hover:shadow-md transition-all group border border-gray-200 dark:border-gray-600 hover:border-green-500 dark:hover:border-green-400">
                            <div class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                                <i class="fas fa-external-link-alt text-green-500"></i>
                            </div>
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 group-hover:text-green-600 dark:group-hover:text-green-400">LINE Console</span>
                            <i class="fas fa-arrow-right ml-auto text-gray-400 group-hover:text-green-500 transition-colors"></i>
                        </a>

                        <a href="https://developers.line.biz/en/docs/line-login/" target="_blank"
                           class="flex items-center gap-3 p-3 glass-fusion rounded-xl hover:shadow-md transition-all group border border-gray-200 dark:border-gray-600 hover:border-purple-500 dark:hover:border-purple-400">
                            <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                                <i class="fas fa-book-open text-purple-500"></i>
                            </div>
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 group-hover:text-purple-600 dark:group-hover:text-purple-400">Documentation</span>
                            <i class="fas fa-arrow-right ml-auto text-gray-400 group-hover:text-purple-500 transition-colors"></i>
                        </a>
                    </div>
                </div>

            </div>
        </div>

        <!-- Sticky Save Button -->
        <div class="fixed bottom-0 left-0 right-0 z-40 transform transition-transform duration-300"
             :class="formDirty ? 'translate-y-0' : 'translate-y-full'">
            <div class="max-w-7xl mx-auto px-4 pb-4">
                <div class="glass-fusion rounded-2xl shadow-2xl border border-white/20 p-4 bg-gradient-to-r from-green-600/95 to-emerald-600/95 backdrop-blur-xl">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3 text-white">
                            <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center animate-pulse">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div>
                                <p class="font-bold">มีการเปลี่ยนแปลงที่ยังไม่ได้บันทึก</p>
                                <p class="text-sm text-green-100">กรุณาบันทึกการตั้งค่าก่อนออกจากหน้านี้</p>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <button type="button" @click="formDirty = false; location.reload()"
                                    class="px-6 py-3 bg-white/20 hover:bg-white/30 text-white rounded-xl transition font-semibold border border-white/30">
                                <i class="fas fa-times mr-2"></i>ยกเลิก
                            </button>
                            <button type="submit"
                                    class="px-8 py-3 bg-white text-green-600 rounded-xl hover:bg-green-50 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-bold">
                                <i class="fas fa-save mr-2"></i>บันทึกการตั้งค่า
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Regular Save Button (for non-sticky view) -->
        <div class="mt-8" x-show="!formDirty">
            <div class="glass-fusion rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6 hover:shadow-2xl transition-all duration-300">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-info-circle text-green-500 text-xl"></i>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            อย่าลืมทดสอบการเชื่อมต่อ LINE ก่อนเปิดใช้งาน
                        </p>
                    </div>
                    <button type="submit"
                        class="w-full sm:w-auto px-8 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-bold">
                        <i class="fas fa-save mr-2"></i>บันทึกการตั้งค่า
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Test Connection Results Modal -->
<div id="connectionTestModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4" @click.self="closeConnectionTestModal()">
    <div class="glass-fusion rounded-2xl shadow-2xl max-w-2xl w-full overflow-hidden transform transition-all border border-white/20 animate-scale-in">
        <div class="bg-gradient-to-r from-blue-500 to-cyan-600 px-6 py-4">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center mr-2">
                        <i class="fas fa-plug"></i>
                    </div>
                    LINE API Connection Test
                </h3>
                <button onclick="closeConnectionTestModal()" class="text-white/80 hover:text-white transition hover:rotate-90 transform duration-300">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>

        <div class="p-6">
            <div id="connectionTestLoading" class="text-center py-12">
                <div class="inline-block">
                    <i class="fas fa-spinner fa-spin text-5xl text-blue-500 mb-4"></i>
                </div>
                <p class="text-gray-600 dark:text-gray-400 font-semibold text-lg">กำลังทดสอบการเชื่อมต่อ...</p>
                <p class="text-gray-500 dark:text-gray-500 text-sm mt-2">กรุณารอสักครู่</p>
            </div>

            <div id="connectionTestResults" class="hidden space-y-4">
                <div id="overallStatus" class="p-4 rounded-xl"></div>
                <div id="testDetails" class="space-y-3"></div>
            </div>
        </div>

        <div class="bg-gray-50 dark:bg-gray-800 px-6 py-4 flex justify-end gap-3">
            <button onclick="closeConnectionTestModal()"
                    class="px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition font-semibold">
                ปิด
            </button>
        </div>
    </div>
</div>

<!-- Test Message Modal -->
<div id="testModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4"
     x-data="{ tab: 'select' }"
     @click.self="closeTestModal()">
    <div class="glass-fusion rounded-2xl shadow-2xl max-w-3xl w-full overflow-hidden transform transition-all max-h-[90vh] flex flex-col border border-white/20 animate-scale-in">
        <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-4">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center mr-2">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                    Test LINE Message
                </h3>
                <button onclick="closeTestModal()" class="text-white/80 hover:text-white transition hover:rotate-90 transform duration-300">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex border-b border-gray-200 dark:border-gray-700 px-6 bg-gray-50 dark:bg-gray-800/50">
            <button type="button" @click="tab = 'select'"
                    class="px-6 py-3 font-semibold transition-all border-b-2 relative"
                    :class="tab === 'select' ? 'border-green-500 text-green-600 dark:text-green-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'">
                <i class="fas fa-users mr-2"></i>เลือกผู้ใช้
                <div x-show="tab === 'select'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-green-500 animate-expand"></div>
            </button>
            <button type="button" @click="tab = 'manual'"
                    class="px-6 py-3 font-semibold transition-all border-b-2 relative"
                    :class="tab === 'manual' ? 'border-green-500 text-green-600 dark:text-green-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'">
                <i class="fas fa-keyboard mr-2"></i>ระบุ User ID
                <div x-show="tab === 'manual'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-green-500 animate-expand"></div>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto">
            <!-- Select User Tab -->
            <div x-show="tab === 'select'" class="p-6 space-y-4" x-transition>
                <!-- Search Box -->
                <div class="relative">
                    <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                        <i class="fas fa-search"></i>
                    </div>
                    <input type="text" id="userSearch" placeholder="ค้นหาผู้ใช้ด้วยชื่อ, อีเมล, หรือชื่อ LINE..."
                           class="w-full pl-12 pr-4 py-3 border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all shadow-sm">
                </div>

                <!-- Users Table -->
                <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm">
                    <div id="usersTableLoading" class="p-8 text-center">
                        <i class="fas fa-spinner fa-spin text-3xl text-green-500 mb-3"></i>
                        <p class="text-gray-600 dark:text-gray-400">กำลังโหลดผู้ใช้...</p>
                    </div>

                    <div id="usersTableContent" class="hidden">
                        <div class="max-h-96 overflow-y-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 dark:bg-gray-800 sticky top-0 shadow-sm">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">ผู้ใช้</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">LINE Display</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">สถานะ</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">เลือก</th>
                                    </tr>
                                </thead>
                                <tbody id="usersTableBody" class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <!-- Users will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="usersTableEmpty" class="hidden p-12 text-center">
                        <i class="fas fa-users-slash text-5xl text-gray-300 dark:text-gray-600 mb-4"></i>
                        <p class="text-gray-600 dark:text-gray-400 font-semibold">ไม่พบผู้ใช้ที่มีบัญชี LINE</p>
                    </div>
                </div>
            </div>

            <!-- Manual Input Tab -->
            <div x-show="tab === 'manual'" class="p-6 space-y-4" x-transition>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-user text-green-500 mr-1"></i> LINE User ID
                    </label>
                    <div class="relative">
                        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-fingerprint"></i>
                        </div>
                        <input type="text" id="manualLineUserId"
                            class="w-full pl-12 pr-4 py-3 border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all shadow-sm"
                            placeholder="U1234567890abcdef1234567890abcdef">
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">LINE User ID ของผู้รับข้อความ</p>
                </div>
            </div>
        </div>

        <!-- Message Form (Common) -->
        <form method="POST" action="{{ route('admin.line-oa.test-message') }}" class="p-6 border-t border-gray-200 dark:border-gray-700 space-y-4 bg-gray-50 dark:bg-gray-800/50">
            @csrf
            <input type="hidden" name="line_user_id" id="selectedLineUserId">

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-comment text-green-500 mr-1"></i> ข้อความทดสอบ
                </label>
                <textarea name="message" rows="3" required
                    class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all shadow-sm"
                    placeholder="ระบุข้อความที่ต้องการส่ง...">ทดสอบการส่งข้อความจากระบบ</textarea>
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="closeTestModal()"
                        class="flex-1 px-4 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition font-semibold">
                    ยกเลิก
                </button>
                <button type="submit"
                        class="flex-1 px-4 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl hover:from-green-600 hover:to-emerald-700 transition shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-semibold">
                    <i class="fas fa-paper-plane mr-2"></i>ส่งข้อความ
                </button>
            </div>
        </form>
    </div>
</div>

<style>
@keyframes fade-in {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes scale-in {
    from { opacity: 0; transform: scale(0.9); }
    to { opacity: 1; transform: scale(1); }
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}

@keyframes float-delayed {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-15px); }
}

@keyframes pulse-slow {
    0%, 100% { opacity: 0.3; }
    50% { opacity: 0.5; }
}

@keyframes expand {
    from { width: 0; }
    to { width: 100%; }
}

.animate-fade-in { animation: fade-in 0.3s ease-out; }
.animate-scale-in { animation: scale-in 0.3s ease-out; }
.animate-float { animation: float 6s ease-in-out infinite; }
.animate-float-delayed { animation: float-delayed 8s ease-in-out infinite; }
.animate-pulse-slow { animation: pulse-slow 4s ease-in-out infinite; }
.animate-expand { animation: expand 0.3s ease-out; }

[x-cloak] { display: none !important; }
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
        tr.className = 'hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors';
        tr.innerHTML = `
            <td class="px-4 py-3">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center text-white font-bold mr-3 shadow-lg">
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
                    '<span class="inline-flex items-center px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 text-xs font-semibold rounded"><i class="fas fa-check-circle mr-1"></i>Verified</span>' :
                    '<span class="inline-flex items-center px-2 py-1 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 text-xs font-semibold rounded">Unverified</span>'
                }
            </td>
            <td class="px-4 py-3 text-center">
                <button type="button" onclick="selectUser('${user.line_user_id}', '${user.name.replace(/'/g, "\\'")}')"
                        class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white text-sm rounded-xl transition font-semibold shadow-sm hover:shadow-md transform hover:-translate-y-0.5">
                    <i class="fas fa-check mr-1"></i>เลือก
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// Select user
function selectUser(lineUserId, userName) {
    document.getElementById('selectedLineUserId').value = lineUserId;

    // Show better notification
    const notification = document.createElement('div');
    notification.className = 'fixed top-4 right-4 z-50 p-4 bg-green-500 text-white rounded-xl shadow-2xl animate-fade-in';
    notification.innerHTML = `
        <div class="flex items-center gap-3">
            <i class="fas fa-check-circle text-2xl"></i>
            <div>
                <p class="font-bold">เลือกผู้ใช้แล้ว</p>
                <p class="text-sm">${userName}</p>
            </div>
        </div>
    `;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
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
            'success': 'bg-green-100 dark:bg-green-900/30 border-green-300 dark:border-green-700 text-green-800 dark:text-green-200',
            'warning': 'bg-yellow-100 dark:bg-yellow-900/30 border-yellow-300 dark:border-yellow-700 text-yellow-800 dark:text-yellow-200',
            'error': 'bg-red-100 dark:bg-red-900/30 border-red-300 dark:border-red-700 text-red-800 dark:text-red-200'
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
                    <h4 class="font-bold text-lg">${data.overall_status.toUpperCase()}</h4>
                    <p class="text-sm">การทดสอบเชื่อมต่อเสร็จสมบูรณ์</p>
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
                        <p><strong>ชื่อ Bot:</strong> ${test.bot_info.displayName}</p>
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

        overallStatus.className = 'p-4 rounded-xl border bg-red-100 dark:bg-red-900/30 border-red-300 dark:border-red-700 text-red-800 dark:text-red-200';
        overallStatus.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-times-circle text-red-600 text-2xl mr-3"></i>
                <div>
                    <h4 class="font-bold text-lg">การทดสอบเชื่อมต่อล้มเหลว</h4>
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
