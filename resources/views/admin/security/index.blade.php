@extends('layouts.admin')

@section('title', 'ความปลอดภัย')

@section('content')
<div x-data="{ activeTab: 'dashboard' }" class="space-y-6">
    <!-- Page Header -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">🛡️ ระบบความปลอดภัย</h1>
    </div>

    <!-- Tab Navigation -->
    <div class="bg-white rounded-lg shadow-md">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px">
                <button @click="activeTab = 'dashboard'"
                        :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'dashboard', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'dashboard' }"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors duration-200">
                    <span class="flex items-center">
                        <span class="text-lg mr-2">📊</span>
                        Dashboard
                    </span>
                </button>

                <button @click="activeTab = 'turnstile'"
                        :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'turnstile', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'turnstile' }"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors duration-200">
                    <span class="flex items-center">
                        <span class="text-lg mr-2">🤖</span>
                        Cloudflare Turnstile
                    </span>
                </button>

                <button @click="activeTab = 'ratelimit'"
                        :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'ratelimit', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'ratelimit' }"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors duration-200">
                    <span class="flex items-center">
                        <span class="text-lg mr-2">⏱️</span>
                        Rate Limiting
                    </span>
                </button>

                <button @click="activeTab = 'autoban'"
                        :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'autoban', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'autoban' }"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors duration-200">
                    <span class="flex items-center">
                        <span class="text-lg mr-2">🚨</span>
                        Auto-Ban
                    </span>
                </button>

                <button @click="activeTab = 'ipmanagement'"
                        :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'ipmanagement', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'ipmanagement' }"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors duration-200">
                    <span class="flex items-center">
                        <span class="text-lg mr-2">🚫</span>
                        IP Blocking
                    </span>
                </button>

                <button @click="activeTab = 'logs'"
                        :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'logs', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'logs' }"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors duration-200">
                    <span class="flex items-center">
                        <span class="text-lg mr-2">📝</span>
                        Security Logs
                    </span>
                </button>
            </nav>
        </div>

        <!-- Tab Contents -->
        <div class="p-6">
            <!-- Dashboard Tab -->
            <div x-show="activeTab === 'dashboard'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="space-y-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">สถิติความปลอดภัย</h3>
                        <a href="{{ route('admin.security.analytics') }}"
                           class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition flex items-center gap-2">
                            <span>📊</span>
                            <span>View Full Analytics</span>
                        </a>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <!-- Blocked IPs -->
                        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-lg shadow-lg p-6 text-white">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-red-100 text-sm font-medium">IP ที่ถูกบล็อก</p>
                                    <p class="text-3xl font-bold mt-2">{{ number_format($stats['total_blocked_ips']) }}</p>
                                </div>
                                <div class="text-5xl opacity-50">🚫</div>
                            </div>
                        </div>

                        <!-- Whitelisted IPs -->
                        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-green-100 text-sm font-medium">IP Whitelist</p>
                                    <p class="text-3xl font-bold mt-2">{{ number_format($stats['total_whitelisted_ips']) }}</p>
                                </div>
                                <div class="text-5xl opacity-50">✅</div>
                            </div>
                        </div>

                        <!-- Failed Logins Today -->
                        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg shadow-lg p-6 text-white">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-orange-100 text-sm font-medium">Login ล้มเหลววันนี้</p>
                                    <p class="text-3xl font-bold mt-2">{{ number_format($stats['failed_logins_today']) }}</p>
                                </div>
                                <div class="text-5xl opacity-50">🔒</div>
                            </div>
                        </div>

                        <!-- Rate Limit Hits Today -->
                        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-purple-100 text-sm font-medium">Rate Limit วันนี้</p>
                                    <p class="text-3xl font-bold mt-2">{{ number_format($stats['rate_limit_hits_today']) }}</p>
                                </div>
                                <div class="text-5xl opacity-50">⏱️</div>
                            </div>
                        </div>

                        <!-- Turnstile Failures Today -->
                        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-blue-100 text-sm font-medium">Turnstile ล้มเหลววันนี้</p>
                                    <p class="text-3xl font-bold mt-2">{{ number_format($stats['turnstile_failures_today']) }}</p>
                                </div>
                                <div class="text-5xl opacity-50">🤖</div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Blocked IPs -->
                    <div class="bg-white rounded-lg border border-gray-200 p-6">
                        <h4 class="text-md font-semibold text-gray-900 mb-4">IP ที่ถูกบล็อกล่าสุด</h4>

                        @if($blockedIps->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ประเภท</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">เหตุผล</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่บล็อก</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($blockedIps->take(10) as $blockedIp)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">{{ $blockedIp->ip_address }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($blockedIp->type === 'blacklist')
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Blacklist</span>
                                            @else
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Whitelist</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">{{ $blockedIp->reason ?? '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $blockedIp->created_at->diffForHumans() }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($blockedIp->is_active)
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                                            @else
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <p class="text-gray-500 text-center py-8">ไม่มี IP ที่ถูกบล็อก</p>
                        @endif
                    </div>

                    <!-- Recent Security Logs -->
                    <div class="bg-white rounded-lg border border-gray-200 p-6">
                        <h4 class="text-md font-semibold text-gray-900 mb-4">Security Logs ล่าสุด</h4>

                        @if($securityLogs->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">เวลา</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Device</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ความรุนแรง</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($securityLogs->take(15) as $log)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <span class="px-2 py-1 text-xs font-mono rounded bg-gray-100">{{ $log->event_type }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            @if($log->country_code)
                                            <span class="flex items-center gap-2">
                                                <span>{{ \App\Services\IpIntelligenceService::getCountryFlag($log->country_code) }}</span>
                                                <span>{{ $log->country_name }}</span>
                                            </span>
                                            @else
                                            <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">
                                            {{ $log->ip_address }}
                                            @if($log->is_vpn || $log->is_proxy)
                                            <span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">VPN/Proxy</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            @if($log->os || $log->browser)
                                            <div class="flex flex-col gap-1">
                                                @if($log->os)
                                                <span class="flex items-center gap-1">
                                                    <span>{{ \App\Services\UserAgentParser::getOsIcon($log->os) }}</span>
                                                    <span>{{ $log->os }}</span>
                                                </span>
                                                @endif
                                                @if($log->browser)
                                                <span class="flex items-center gap-1">
                                                    <span>{{ \App\Services\UserAgentParser::getBrowserIcon($log->browser) }}</span>
                                                    <span>{{ $log->browser }}</span>
                                                </span>
                                                @endif
                                            </div>
                                            @else
                                            <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($log->severity === 'critical')
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Critical</span>
                                            @elseif($log->severity === 'high')
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">High</span>
                                            @elseif($log->severity === 'medium')
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Medium</span>
                                            @else
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Low</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <p class="text-gray-500 text-center py-8">ไม่มี Security Logs</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Cloudflare Turnstile Tab -->
            <div x-show="activeTab === 'turnstile'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <form method="POST" action="{{ route('admin.security.turnstile.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="space-y-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">ตั้งค่า Cloudflare Turnstile</h3>

                        <!-- Enable Turnstile -->
                        <div class="flex items-center">
                            <input type="checkbox" name="turnstile_enabled" id="turnstile_enabled"
                                   {{ config('turnstile.enabled') ? 'checked' : '' }}
                                   class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                            <label for="turnstile_enabled" class="ml-2 text-sm font-medium text-gray-700">
                                เปิดใช้งาน Cloudflare Turnstile
                            </label>
                        </div>

                        <!-- Site Key -->
                        <div>
                            <label for="turnstile_site_key" class="block text-sm font-medium text-gray-700 mb-2">Site Key</label>
                            <input type="text" name="turnstile_site_key" id="turnstile_site_key"
                                   value="{{ old('turnstile_site_key', config('turnstile.site_key')) }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="1x00000000000000000000AA">
                            <p class="mt-1 text-sm text-gray-500">Site Key ที่ได้จาก Cloudflare Dashboard</p>
                        </div>

                        <!-- Secret Key -->
                        <div>
                            <label for="turnstile_secret_key" class="block text-sm font-medium text-gray-700 mb-2">Secret Key</label>
                            <input type="text" name="turnstile_secret_key" id="turnstile_secret_key"
                                   value="{{ old('turnstile_secret_key', config('turnstile.secret_key')) }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="1x0000000000000000000000000000000AA">
                            <p class="mt-1 text-sm text-gray-500">Secret Key ที่ได้จาก Cloudflare Dashboard</p>
                        </div>

                        <!-- Admin Bypass -->
                        <div class="flex items-center">
                            <input type="checkbox" name="turnstile_bypass_admin" id="turnstile_bypass_admin"
                                   {{ config('turnstile.bypass_admin') ? 'checked' : '' }}
                                   class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                            <label for="turnstile_bypass_admin" class="ml-2 text-sm font-medium text-gray-700">
                                ยกเว้น Admin จากการตรวจสอบ Turnstile
                            </label>
                        </div>

                        <!-- Theme -->
                        <div>
                            <label for="turnstile_theme" class="block text-sm font-medium text-gray-700 mb-2">ธีม</label>
                            <select name="turnstile_theme" id="turnstile_theme"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="auto" {{ config('turnstile.theme') === 'auto' ? 'selected' : '' }}>Auto</option>
                                <option value="light" {{ config('turnstile.theme') === 'light' ? 'selected' : '' }}>Light</option>
                                <option value="dark" {{ config('turnstile.theme') === 'dark' ? 'selected' : '' }}>Dark</option>
                            </select>
                        </div>

                        <!-- Size -->
                        <div>
                            <label for="turnstile_size" class="block text-sm font-medium text-gray-700 mb-2">ขนาด</label>
                            <select name="turnstile_size" id="turnstile_size"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="normal" {{ config('turnstile.size') === 'normal' ? 'selected' : '' }}>Normal</option>
                                <option value="compact" {{ config('turnstile.size') === 'compact' ? 'selected' : '' }}>Compact</option>
                            </select>
                        </div>

                        <!-- Protection Points -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 mb-3">จุดป้องกัน</h4>
                            <div class="space-y-2">
                                <div class="flex items-center">
                                    <input type="checkbox" name="turnstile_login" id="turnstile_login"
                                           {{ config('turnstile.points.login') ? 'checked' : '' }}
                                           class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                    <label for="turnstile_login" class="ml-2 text-sm text-gray-700">หน้า Login</label>
                                </div>

                                <div class="flex items-center">
                                    <input type="checkbox" name="turnstile_register" id="turnstile_register"
                                           {{ config('turnstile.points.register') ? 'checked' : '' }}
                                           class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                    <label for="turnstile_register" class="ml-2 text-sm text-gray-700">หน้า Register</label>
                                </div>

                                <div class="flex items-center">
                                    <input type="checkbox" name="turnstile_password_change" id="turnstile_password_change"
                                           {{ config('turnstile.points.password_change') ? 'checked' : '' }}
                                           class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                    <label for="turnstile_password_change" class="ml-2 text-sm text-gray-700">การเปลี่ยนรหัสผ่าน</label>
                                </div>

                                <div class="flex items-center">
                                    <input type="checkbox" name="turnstile_profile_update" id="turnstile_profile_update"
                                           {{ config('turnstile.points.profile_update') ? 'checked' : '' }}
                                           class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                    <label for="turnstile_profile_update" class="ml-2 text-sm text-gray-700">การแก้ไขโปรไฟล์</label>
                                </div>

                                <div class="flex items-center">
                                    <input type="checkbox" name="turnstile_withdrawal" id="turnstile_withdrawal"
                                           {{ config('turnstile.points.withdrawal_request') ? 'checked' : '' }}
                                           class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                    <label for="turnstile_withdrawal" class="ml-2 text-sm text-gray-700">การถอนเงิน</label>
                                </div>

                                <div class="flex items-center">
                                    <input type="checkbox" name="turnstile_affiliate_app" id="turnstile_affiliate_app"
                                           {{ config('turnstile.points.affiliate_application') ? 'checked' : '' }}
                                           class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                    <label for="turnstile_affiliate_app" class="ml-2 text-sm text-gray-700">การสมัคร Affiliate</label>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
                                บันทึกการตั้งค่า
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Rate Limiting Tab -->
            <div x-show="activeTab === 'ratelimit'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <form method="POST" action="{{ route('admin.security.rate-limiting.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="space-y-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">ตั้งค่า Rate Limiting</h3>

                        <!-- Enable Rate Limiting -->
                        <div class="flex items-center">
                            <input type="checkbox" name="rate_limiting_enabled" id="rate_limiting_enabled"
                                   {{ config('ratelimit.enabled') ? 'checked' : '' }}
                                   class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                            <label for="rate_limiting_enabled" class="ml-2 text-sm font-medium text-gray-700">
                                เปิดใช้งาน Rate Limiting
                            </label>
                        </div>

                        <!-- Login Rate Limiting Settings -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-900 mb-3">การ Login</h4>

                            <div class="space-y-4">
                                <div>
                                    <label for="rate_limit_login_max_attempts" class="block text-sm font-medium text-gray-700 mb-2">
                                        จำนวนครั้งสูงสุดที่พยายาม Login
                                    </label>
                                    <input type="number" name="rate_limit_login_max_attempts" id="rate_limit_login_max_attempts"
                                           value="{{ old('rate_limit_login_max_attempts', config('ratelimit.login.max_attempts')) }}"
                                           min="1" max="50"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                    <p class="mt-1 text-sm text-gray-500">จำนวนครั้งที่อนุญาตให้พยายาม Login ล้มเหลวได้</p>
                                </div>

                                <div>
                                    <label for="rate_limit_login_decay_minutes" class="block text-sm font-medium text-gray-700 mb-2">
                                        เวลารีเซ็ตจำนวนครั้ง (นาที)
                                    </label>
                                    <input type="number" name="rate_limit_login_decay_minutes" id="rate_limit_login_decay_minutes"
                                           value="{{ old('rate_limit_login_decay_minutes', config('ratelimit.login.decay_minutes')) }}"
                                           min="1" max="1440"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                    <p class="mt-1 text-sm text-gray-500">ระยะเวลาที่จะรีเซ็ตจำนวนครั้งที่พยายาม</p>
                                </div>

                                <div>
                                    <label for="rate_limit_login_lockout_minutes" class="block text-sm font-medium text-gray-700 mb-2">
                                        เวลาล็อกบัญชี (นาที)
                                    </label>
                                    <input type="number" name="rate_limit_login_lockout_minutes" id="rate_limit_login_lockout_minutes"
                                           value="{{ old('rate_limit_login_lockout_minutes', config('ratelimit.login.lockout_minutes')) }}"
                                           min="1" max="1440"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                    <p class="mt-1 text-sm text-gray-500">ระยะเวลาที่จะล็อกไม่ให้ Login ได้</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <p class="text-sm text-blue-800">
                                <strong>คำแนะนำ:</strong> การตั้งค่า Rate Limiting ช่วยป้องกันการโจมตีแบบ Brute Force
                                โดยจำกัดจำนวนครั้งที่สามารถพยายาม Login ได้ในระยะเวลาหนึ่ง
                            </p>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
                                บันทึกการตั้งค่า
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- IP Management Tab -->
            <div x-show="activeTab === 'ipmanagement'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="space-y-6">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900">จัดการ IP Blocking</h3>
                        <button @click="showAddIpModal = true" class="px-4 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
                            เพิ่ม IP
                        </button>
                    </div>

                    <!-- Blocked IPs Table -->
                    @if($blockedIps->count() > 0)
                    <div class="bg-white rounded-lg border border-gray-200 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ประเภท</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">เหตุผล</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">บล็อกโดย</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่บล็อก</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">หมดอายุ</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">การดำเนินการ</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($blockedIps as $blockedIp)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">{{ $blockedIp->ip_address }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($blockedIp->type === 'blacklist')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Blacklist</span>
                                        @else
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Whitelist</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $blockedIp->reason ?? '-' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $blockedIp->blocker->name ?? 'System' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $blockedIp->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $blockedIp->expires_at ? $blockedIp->expires_at->format('Y-m-d H:i') : 'ถาวร' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($blockedIp->is_active)
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                                        @else
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <form method="POST" action="{{ route('admin.security.ip.unblock', $blockedIp->id) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('คุณต้องการลบ IP นี้หรือไม่?')">
                                                ลบ
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $blockedIps->links() }}
                    </div>
                    @else
                    <div class="bg-white rounded-lg border border-gray-200 p-8">
                        <p class="text-gray-500 text-center">ไม่มี IP ที่ถูกบล็อก</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Security Logs Tab -->
            <div x-show="activeTab === 'logs'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="space-y-6">
                    <h3 class="text-lg font-semibold text-gray-900">Security Logs</h3>

                    @if($securityLogs->count() > 0)
                    <div class="bg-white rounded-lg border border-gray-200 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">เวลา</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ความรุนแรง</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">รายละเอียด</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($securityLogs as $log)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <span class="px-2 py-1 text-xs font-mono rounded bg-gray-100">{{ $log->event_type }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">{{ $log->ip_address }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $log->user->name ?? '-' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $log->email ?? '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($log->severity === 'critical')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Critical</span>
                                        @elseif($log->severity === 'high')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">High</span>
                                        @elseif($log->severity === 'medium')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Medium</span>
                                        @else
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Low</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ Str::limit($log->description, 50) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="bg-white rounded-lg border border-gray-200 p-8">
                        <p class="text-gray-500 text-center">ไม่มี Security Logs</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add IP Modal (Simple inline form - can be enhanced with Alpine.js modal) -->
<div x-data="{ showAddIpModal: false }" x-show="showAddIpModal" x-cloak
     class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showAddIpModal = false"></div>

        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">เพิ่ม IP Address</h3>

            <form method="POST" action="{{ route('admin.security.ip.block') }}">
                @csrf

                <div class="space-y-4">
                    <div>
                        <label for="ip_address" class="block text-sm font-medium text-gray-700 mb-2">IP Address</label>
                        <input type="text" name="ip_address" id="ip_address" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                               placeholder="192.168.1.1">
                    </div>

                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700 mb-2">ประเภท</label>
                        <select name="type" id="type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <option value="blacklist">Blacklist</option>
                            <option value="whitelist">Whitelist</option>
                        </select>
                    </div>

                    <div>
                        <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">เหตุผล</label>
                        <textarea name="reason" id="reason" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                  placeholder="ระบุเหตุผลในการบล็อก IP นี้"></textarea>
                    </div>

                    <div>
                        <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-2">วันหมดอายุ (ถ้าไม่ระบุจะเป็นถาวร)</label>
                        <input type="datetime-local" name="expires_at" id="expires_at"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" @click="showAddIpModal = false"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
                        เพิ่ม IP
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if(session('success'))
<div class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg"
     x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)">
    {{ session('success') }}
</div>
@endif

@if($errors->any())
<div class="fixed bottom-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg"
     x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
    @foreach($errors->all() as $error)
        <p>{{ $error }}</p>
    @endforeach
</div>
@endif
@endsection
