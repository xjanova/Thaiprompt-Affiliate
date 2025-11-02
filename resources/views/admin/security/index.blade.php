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

                <button @click="activeTab = 'threat'"
                        :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'threat', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'threat' }"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors duration-200">
                    <span class="flex items-center">
                        <span class="text-lg mr-2">🌍</span>
                        Threat Intelligence
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

            <!-- Auto-Ban Settings Tab -->
            <div x-show="activeTab === 'autoban'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <form method="POST" action="{{ route('admin.security.auto-ban.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="space-y-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">ตั้งค่า Auto-Ban System</h3>

                        <!-- Enable Auto-Ban -->
                        <div class="flex items-center">
                            <input type="checkbox" name="auto_ban_enabled" id="auto_ban_enabled"
                                   {{ config('autoban.enabled') ? 'checked' : '' }}
                                   class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                            <label for="auto_ban_enabled" class="ml-2 text-sm font-medium text-gray-700">
                                เปิดใช้งาน Auto-Ban System
                            </label>
                        </div>

                        <!-- Failed Login Auto-Ban -->
                        <div class="bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-lg p-6">
                            <div class="flex items-center mb-4">
                                <span class="text-2xl mr-3">🔒</span>
                                <h4 class="text-md font-semibold text-gray-900">Failed Login Auto-Ban</h4>
                            </div>

                            <div class="space-y-4">
                                <div class="flex items-center">
                                    <input type="checkbox" name="auto_ban_failed_login_enabled" id="auto_ban_failed_login_enabled"
                                           {{ config('autoban.failed_login.enabled') ? 'checked' : '' }}
                                           class="w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                                    <label for="auto_ban_failed_login_enabled" class="ml-2 text-sm font-medium text-gray-700">
                                        เปิดใช้งาน
                                    </label>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">จำนวนครั้ง (Threshold)</label>
                                        <input type="number" name="auto_ban_failed_login_threshold"
                                               value="{{ old('auto_ban_failed_login_threshold', config('autoban.failed_login.threshold')) }}"
                                               min="1" max="100"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                                        <p class="mt-1 text-xs text-gray-500">ครั้ง</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">ช่วงเวลา (Time Window)</label>
                                        <input type="number" name="auto_ban_failed_login_time_window"
                                               value="{{ old('auto_ban_failed_login_time_window', config('autoban.failed_login.time_window')) }}"
                                               min="1" max="1440"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                                        <p class="mt-1 text-xs text-gray-500">นาที</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">ระยะเวลาแบน (Ban Duration)</label>
                                        <input type="number" name="auto_ban_failed_login_ban_duration"
                                               value="{{ old('auto_ban_failed_login_ban_duration', config('autoban.failed_login.ban_duration')) }}"
                                               min="1" max="10080"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                                        <p class="mt-1 text-xs text-gray-500">นาที ({{ round(config('autoban.failed_login.ban_duration') / 60, 1) }} ชม.)</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Turnstile Failure Auto-Ban -->
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-lg p-6">
                            <div class="flex items-center mb-4">
                                <span class="text-2xl mr-3">🤖</span>
                                <h4 class="text-md font-semibold text-gray-900">Turnstile Failure Auto-Ban</h4>
                            </div>

                            <div class="space-y-4">
                                <div class="flex items-center">
                                    <input type="checkbox" name="auto_ban_turnstile_enabled" id="auto_ban_turnstile_enabled"
                                           {{ config('autoban.turnstile_failure.enabled') ? 'checked' : '' }}
                                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                    <label for="auto_ban_turnstile_enabled" class="ml-2 text-sm font-medium text-gray-700">
                                        เปิดใช้งาน
                                    </label>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">จำนวนครั้ง</label>
                                        <input type="number" name="auto_ban_turnstile_threshold"
                                               value="{{ old('auto_ban_turnstile_threshold', config('autoban.turnstile_failure.threshold')) }}"
                                               min="1" max="100"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">ช่วงเวลา (นาที)</label>
                                        <input type="number" name="auto_ban_turnstile_time_window"
                                               value="{{ old('auto_ban_turnstile_time_window', config('autoban.turnstile_failure.time_window')) }}"
                                               min="1" max="1440"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">ระยะเวลาแบน (นาที)</label>
                                        <input type="number" name="auto_ban_turnstile_ban_duration"
                                               value="{{ old('auto_ban_turnstile_ban_duration', config('autoban.turnstile_failure.ban_duration')) }}"
                                               min="1" max="10080"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                        <p class="mt-1 text-xs text-gray-500">{{ round(config('autoban.turnstile_failure.ban_duration') / 60, 1) }} ชม.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Rate Limit Auto-Ban -->
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200 rounded-lg p-6">
                            <div class="flex items-center mb-4">
                                <span class="text-2xl mr-3">⏱️</span>
                                <h4 class="text-md font-semibold text-gray-900">Rate Limit Auto-Ban</h4>
                            </div>

                            <div class="space-y-4">
                                <div class="flex items-center">
                                    <input type="checkbox" name="auto_ban_rate_limit_enabled" id="auto_ban_rate_limit_enabled"
                                           {{ config('autoban.rate_limit.enabled') ? 'checked' : '' }}
                                           class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                                    <label for="auto_ban_rate_limit_enabled" class="ml-2 text-sm font-medium text-gray-700">
                                        เปิดใช้งาน
                                    </label>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">จำนวนครั้ง</label>
                                        <input type="number" name="auto_ban_rate_limit_threshold"
                                               value="{{ old('auto_ban_rate_limit_threshold', config('autoban.rate_limit.threshold')) }}"
                                               min="1" max="100"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">ช่วงเวลา (นาที)</label>
                                        <input type="number" name="auto_ban_rate_limit_time_window"
                                               value="{{ old('auto_ban_rate_limit_time_window', config('autoban.rate_limit.time_window')) }}"
                                               min="1" max="1440"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">ระยะเวลาแบน (นาที)</label>
                                        <input type="number" name="auto_ban_rate_limit_ban_duration"
                                               value="{{ old('auto_ban_rate_limit_ban_duration', config('autoban.rate_limit.ban_duration')) }}"
                                               min="1" max="10080"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                                        <p class="mt-1 text-xs text-gray-500">{{ round(config('autoban.rate_limit.ban_duration') / 60, 1) }} ชม.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Email Notifications -->
                        <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-lg p-6">
                            <div class="flex items-center mb-4">
                                <span class="text-2xl mr-3">📧</span>
                                <h4 class="text-md font-semibold text-gray-900">Email Notifications</h4>
                            </div>

                            <div class="space-y-4">
                                <div class="flex items-center">
                                    <input type="checkbox" name="auto_ban_notifications_enabled" id="auto_ban_notifications_enabled"
                                           {{ config('autoban.notifications.enabled') ? 'checked' : '' }}
                                           class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500">
                                    <label for="auto_ban_notifications_enabled" class="ml-2 text-sm font-medium text-gray-700">
                                        เปิดใช้งานการแจ้งเตือน
                                    </label>
                                </div>

                                <div class="flex items-center">
                                    <input type="checkbox" name="auto_ban_email_enabled" id="auto_ban_email_enabled"
                                           {{ config('autoban.notifications.email.enabled') ? 'checked' : '' }}
                                           class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500">
                                    <label for="auto_ban_email_enabled" class="ml-2 text-sm font-medium text-gray-700">
                                        ส่งอีเมล
                                    </label>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">อีเมลผู้รับ (คั่นด้วย comma)</label>
                                    <input type="text" name="auto_ban_email_recipients"
                                           value="{{ old('auto_ban_email_recipients', config('autoban.notifications.email.recipients')) }}"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                                           placeholder="admin@example.com, security@example.com">
                                    <p class="mt-1 text-xs text-gray-500">อีเมลที่จะได้รับการแจ้งเตือนเมื่อมี IP ถูก Auto-Ban</p>
                                </div>
                            </div>
                        </div>

                        <!-- Info Box -->
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex items-start">
                                <span class="text-2xl mr-3">💡</span>
                                <div>
                                    <h5 class="font-semibold text-yellow-900 mb-2">คำอธิบาย Auto-Ban System</h5>
                                    <ul class="text-sm text-yellow-800 space-y-1 list-disc list-inside">
                                        <li><strong>Threshold:</strong> จำนวนครั้งที่ล้มเหลวก่อนแบน IP</li>
                                        <li><strong>Time Window:</strong> ช่วงเวลาที่นับจำนวนครั้ง (นาที)</li>
                                        <li><strong>Ban Duration:</strong> ระยะเวลาที่แบน IP (นาที)</li>
                                        <li>ตัวอย่าง: 10 ครั้ง / 30 นาที → แบน 1440 นาที (24 ชม.) หมายความว่า ถ้า Login ล้มเหลว 10 ครั้งใน 30 นาที จะถูกแบน IP เป็นเวลา 24 ชั่วโมง</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
                                บันทึกการตั้งค่า Auto-Ban
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- IP Management Tab -->
            <div x-show="activeTab === 'ipmanagement'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="space-y-6" x-data="{ showAddModal: false }">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900">จัดการ IP Blocking</h3>
                        <button @click="showAddModal = true"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition flex items-center gap-2">
                            <span>➕</span>
                            <span>เพิ่ม IP</span>
                        </button>
                    </div>

                    <!-- Add IP Modal -->
                    <div x-show="showAddModal"
                         x-cloak
                         @click.self="showAddModal = false"
                         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
                         style="display: none;">
                        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                            <div class="p-6">
                                <div class="flex justify-between items-center mb-6">
                                    <h3 class="text-xl font-semibold text-gray-900">เพิ่ม IP Address</h3>
                                    <button @click="showAddModal = false" class="text-gray-400 hover:text-gray-600">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>

                                <form method="POST" action="{{ route('admin.security.ip.block') }}" class="space-y-6" x-data="{ ipType: 'single' }">
                                    @csrf

                                    <!-- IP Type Selection -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            ประเภท IP <span class="text-red-500">*</span>
                                        </label>
                                        <div class="grid grid-cols-3 gap-3">
                                            <label class="relative flex items-center justify-center p-3 border rounded-lg cursor-pointer transition-all"
                                                   :class="ipType === 'single' ? 'border-indigo-600 bg-indigo-50' : 'border-gray-300 hover:border-indigo-400'">
                                                <input type="radio" name="ip_type" value="single" x-model="ipType" class="sr-only" checked>
                                                <div class="text-center">
                                                    <div class="text-2xl mb-1">🎯</div>
                                                    <div class="text-sm font-medium">Single IP</div>
                                                    <div class="text-xs text-gray-500">IP เดียว</div>
                                                </div>
                                            </label>

                                            <label class="relative flex items-center justify-center p-3 border rounded-lg cursor-pointer transition-all"
                                                   :class="ipType === 'range' ? 'border-indigo-600 bg-indigo-50' : 'border-gray-300 hover:border-indigo-400'">
                                                <input type="radio" name="ip_type" value="range" x-model="ipType" class="sr-only">
                                                <div class="text-center">
                                                    <div class="text-2xl mb-1">↔️</div>
                                                    <div class="text-sm font-medium">IP Range</div>
                                                    <div class="text-xs text-gray-500">ช่วง IP</div>
                                                </div>
                                            </label>

                                            <label class="relative flex items-center justify-center p-3 border rounded-lg cursor-pointer transition-all"
                                                   :class="ipType === 'cidr' ? 'border-indigo-600 bg-indigo-50' : 'border-gray-300 hover:border-indigo-400'">
                                                <input type="radio" name="ip_type" value="cidr" x-model="ipType" class="sr-only">
                                                <div class="text-center">
                                                    <div class="text-2xl mb-1">🌐</div>
                                                    <div class="text-sm font-medium">CIDR</div>
                                                    <div class="text-xs text-gray-500">Class IP</div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Single IP Address -->
                                    <div x-show="ipType === 'single'" x-transition>
                                        <label for="ip_address" class="block text-sm font-medium text-gray-700 mb-2">
                                            IP Address <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text"
                                               name="ip_address"
                                               id="ip_address"
                                               placeholder="192.168.1.1 หรือ 2001:0db8:85a3::8a2e:0370:7334"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                        <p class="mt-1 text-xs text-gray-500">รองรับทั้ง IPv4 และ IPv6</p>
                                    </div>

                                    <!-- IP Range -->
                                    <div x-show="ipType === 'range'" x-transition>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            ช่วง IP Address <span class="text-red-500">*</span>
                                        </label>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <input type="text"
                                                       name="ip_range_start"
                                                       placeholder="เริ่มต้น: 192.168.1.1"
                                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                                <p class="mt-1 text-xs text-gray-500">IP เริ่มต้น</p>
                                            </div>
                                            <div>
                                                <input type="text"
                                                       name="ip_range_end"
                                                       placeholder="สิ้นสุด: 192.168.1.255"
                                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                                <p class="mt-1 text-xs text-gray-500">IP สิ้นสุด</p>
                                            </div>
                                        </div>
                                        <p class="mt-2 text-xs text-blue-600">
                                            💡 ตัวอย่าง: 192.168.1.1 ถึง 192.168.1.255 (256 IP addresses)
                                        </p>
                                    </div>

                                    <!-- CIDR Notation -->
                                    <div x-show="ipType === 'cidr'" x-transition>
                                        <label for="ip_cidr" class="block text-sm font-medium text-gray-700 mb-2">
                                            CIDR Notation <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text"
                                               name="ip_cidr"
                                               id="ip_cidr"
                                               placeholder="192.168.1.0/24 หรือ 2001:db8::/32"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                        <p class="mt-1 text-xs text-gray-500">รูปแบบ CIDR เช่น 192.168.1.0/24 (256 addresses)</p>
                                        <div class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                            <p class="text-xs text-blue-800 font-medium mb-1">📚 CIDR ที่ใช้บ่อย:</p>
                                            <ul class="text-xs text-blue-700 space-y-1">
                                                <li>• /32 = 1 IP (Single host)</li>
                                                <li>• /24 = 256 IPs (Class C)</li>
                                                <li>• /16 = 65,536 IPs (Class B)</li>
                                                <li>• /8 = 16,777,216 IPs (Class A)</li>
                                            </ul>
                                        </div>
                                    </div>

                                    <!-- Type -->
                                    <div>
                                        <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                                            ประเภท <span class="text-red-500">*</span>
                                        </label>
                                        <select name="type"
                                                id="type"
                                                required
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                            <option value="blacklist">🚫 Blacklist (บล็อก)</option>
                                            <option value="whitelist">✅ Whitelist (อนุญาต)</option>
                                        </select>
                                        <p class="mt-1 text-xs text-gray-500">
                                            Blacklist จะบล็อกการเข้าถึง / Whitelist จะอนุญาตเสมอ (ข้าม Auto-Ban)
                                        </p>
                                    </div>

                                    <!-- Reason -->
                                    <div>
                                        <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">
                                            เหตุผล
                                        </label>
                                        <textarea name="reason"
                                                  id="reason"
                                                  rows="3"
                                                  maxlength="500"
                                                  placeholder="ระบุเหตุผลในการบล็อกหรือเพิ่ม IP นี้..."
                                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"></textarea>
                                        <p class="mt-1 text-xs text-gray-500">สูงสุด 500 ตัวอักษร (ไม่จำเป็น)</p>
                                    </div>

                                    <!-- Expires At -->
                                    <div>
                                        <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-2">
                                            วันหมดอายุ
                                        </label>
                                        <input type="datetime-local"
                                               name="expires_at"
                                               id="expires_at"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                        <p class="mt-1 text-xs text-gray-500">
                                            ถ้าไม่ระบุจะเป็นการบล็อก/อนุญาตแบบถาวร
                                        </p>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="flex justify-end gap-3 pt-4 border-t">
                                        <button type="button"
                                                @click="showAddModal = false"
                                                class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                                            ยกเลิก
                                        </button>
                                        <button type="submit"
                                                class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
                                            เพิ่ม IP
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Blocked IPs Table -->
                    @if($blockedIps->count() > 0)
                    <div class="bg-white rounded-lg border border-gray-200 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Type</th>
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
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($blockedIp->ip_type === 'single')
                                        <span class="px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-800">🎯 Single</span>
                                        @elseif($blockedIp->ip_type === 'range')
                                        <span class="px-2 py-1 text-xs font-semibold rounded bg-purple-100 text-purple-800">↔️ Range</span>
                                        @elseif($blockedIp->ip_type === 'cidr')
                                        <span class="px-2 py-1 text-xs font-semibold rounded bg-indigo-100 text-indigo-800">🌐 CIDR</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm font-mono text-gray-900">
                                        {{ $blockedIp->getIpDisplay() }}
                                    </td>
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

            <!-- Threat Intelligence Tab -->
            <div x-show="activeTab === 'threat'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="space-y-6">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900">🌍 Global Threat Intelligence</h3>
                        <a href="{{ route('admin.security.threat-intelligence') }}"
                           class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition flex items-center gap-2">
                            <span>📊</span>
                            <span>View Full Dashboard</span>
                        </a>
                    </div>

                    <!-- Settings Form -->
                    <form method="POST" action="{{ route('admin.security.threat-intelligence.settings') }}">
                        @csrf
                        @method('PUT')

                        <div class="space-y-6">
                            <!-- Enable Threat Intelligence -->
                            <div class="bg-gradient-to-r from-purple-50 to-indigo-50 border border-purple-200 rounded-lg p-6">
                                <div class="flex items-center mb-4">
                                    <input type="checkbox" name="threat_intelligence_enabled" id="threat_intelligence_enabled"
                                           {{ \App\Models\Setting::get('threat_intelligence_enabled', 'boolean', false) ? 'checked' : '' }}
                                           class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                                    <label for="threat_intelligence_enabled" class="ml-2 text-sm font-medium text-gray-900">
                                        เปิดใช้งาน Global Threat Intelligence
                                    </label>
                                </div>

                                <p class="text-sm text-gray-700 mb-4">
                                    ระบบจะตรวจสอบและบล็อก IP ที่อยู่ในบล็อกลิสต์ทั่วโลกโดยอัตโนมัติ จากแหล่งข้อมูลที่น่าเชื่อถือ
                                </p>

                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div class="flex items-center">
                                        <input type="checkbox" name="threat_block_proxy" id="threat_block_proxy"
                                               {{ \App\Models\Setting::get('threat_block_proxy', 'boolean', true) ? 'checked' : '' }}
                                               class="w-4 h-4 text-orange-600 border-gray-300 rounded">
                                        <label for="threat_block_proxy" class="ml-2 text-sm text-gray-700">🔀 Proxy</label>
                                    </div>

                                    <div class="flex items-center">
                                        <input type="checkbox" name="threat_block_vpn" id="threat_block_vpn"
                                               {{ \App\Models\Setting::get('threat_block_vpn', 'boolean', false) ? 'checked' : '' }}
                                               class="w-4 h-4 text-yellow-600 border-gray-300 rounded">
                                        <label for="threat_block_vpn" class="ml-2 text-sm text-gray-700">🔒 VPN</label>
                                    </div>

                                    <div class="flex items-center">
                                        <input type="checkbox" name="threat_block_tor" id="threat_block_tor"
                                               {{ \App\Models\Setting::get('threat_block_tor', 'boolean', true) ? 'checked' : '' }}
                                               class="w-4 h-4 text-purple-600 border-gray-300 rounded">
                                        <label for="threat_block_tor" class="ml-2 text-sm text-gray-700">🧅 Tor</label>
                                    </div>

                                    <div class="flex items-center">
                                        <input type="checkbox" name="threat_block_abuse" id="threat_block_abuse"
                                               {{ \App\Models\Setting::get('threat_block_abuse', 'boolean', true) ? 'checked' : '' }}
                                               class="w-4 h-4 text-red-600 border-gray-300 rounded">
                                        <label for="threat_block_abuse" class="ml-2 text-sm text-gray-700">⚠️ Abuse</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Confidence Threshold -->
                            <div>
                                <label for="threat_confidence_threshold" class="block text-sm font-medium text-gray-700 mb-2">
                                    Confidence Threshold (%)
                                </label>
                                <input type="number" name="threat_confidence_threshold" id="threat_confidence_threshold"
                                       value="{{ old('threat_confidence_threshold', \App\Models\Setting::get('threat_confidence_threshold', 'integer', 70)) }}"
                                       min="0" max="100"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                                <p class="mt-1 text-xs text-gray-500">
                                    บล็อกเฉพาะ IP ที่มีคะแนนความน่าเชื่อถือมากกว่าหรือเท่ากับค่านี้
                                </p>
                            </div>

                            <!-- Auto Update Schedule -->
                            <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg p-6" x-data="{
                                frequency: '{{ \App\Models\Setting::get('threat_update_frequency', 'string', 'daily') }}'
                            }">
                                <h4 class="text-md font-semibold text-gray-900 mb-4">⏰ ตั้งเวลาอัปเดตอัตโนมัติ</h4>

                                <div class="space-y-4">
                                    <!-- Enable Auto Update -->
                                    <div class="flex items-center">
                                        <input type="checkbox" name="threat_auto_update_enabled" id="threat_auto_update_enabled"
                                               {{ \App\Models\Setting::get('threat_auto_update_enabled', 'boolean', true) ? 'checked' : '' }}
                                               class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500">
                                        <label for="threat_auto_update_enabled" class="ml-2 text-sm font-medium text-gray-900">
                                            เปิดใช้งานการอัปเดตอัตโนมัติ
                                        </label>
                                    </div>

                                    <!-- Frequency Selection -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            ความถี่ในการอัปเดต
                                        </label>
                                        <select name="threat_update_frequency"
                                                x-model="frequency"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                                            <option value="hourly">⏱️ ทุกชั่วโมง (Hourly)</option>
                                            <option value="daily">📅 ทุกวัน (Daily)</option>
                                            <option value="weekly">📆 ทุกสัปดาห์ (Weekly)</option>
                                            <option value="custom">⚙️ กำหนดเอง (Custom Cron)</option>
                                        </select>
                                    </div>

                                    <!-- Time Selection (for daily and weekly) -->
                                    <div x-show="frequency === 'daily' || frequency === 'weekly'" x-transition>
                                        <label for="threat_update_time" class="block text-sm font-medium text-gray-700 mb-2">
                                            เวลาที่ต้องการอัปเดต
                                        </label>
                                        <input type="time" name="threat_update_time" id="threat_update_time"
                                               value="{{ old('threat_update_time', \App\Models\Setting::get('threat_update_time', 'string', '03:00')) }}"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                                        <p class="mt-1 text-xs text-gray-500">
                                            แนะนำ: 03:00 (ช่วงที่เซิร์ฟเวอร์ไม่ยุ่ง)
                                        </p>
                                    </div>

                                    <!-- Day Selection (for weekly) -->
                                    <div x-show="frequency === 'weekly'" x-transition>
                                        <label for="threat_update_day" class="block text-sm font-medium text-gray-700 mb-2">
                                            วันที่ต้องการอัปเดต
                                        </label>
                                        <select name="threat_update_day" id="threat_update_day"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                                            <option value="0" {{ \App\Models\Setting::get('threat_update_day', 'integer', 0) == 0 ? 'selected' : '' }}>อาทิตย์ (Sunday)</option>
                                            <option value="1" {{ \App\Models\Setting::get('threat_update_day', 'integer', 0) == 1 ? 'selected' : '' }}>จันทร์ (Monday)</option>
                                            <option value="2" {{ \App\Models\Setting::get('threat_update_day', 'integer', 0) == 2 ? 'selected' : '' }}>อังคาร (Tuesday)</option>
                                            <option value="3" {{ \App\Models\Setting::get('threat_update_day', 'integer', 0) == 3 ? 'selected' : '' }}>พุธ (Wednesday)</option>
                                            <option value="4" {{ \App\Models\Setting::get('threat_update_day', 'integer', 0) == 4 ? 'selected' : '' }}>พฤหัสบดี (Thursday)</option>
                                            <option value="5" {{ \App\Models\Setting::get('threat_update_day', 'integer', 0) == 5 ? 'selected' : '' }}>ศุกร์ (Friday)</option>
                                            <option value="6" {{ \App\Models\Setting::get('threat_update_day', 'integer', 0) == 6 ? 'selected' : '' }}>เสาร์ (Saturday)</option>
                                        </select>
                                    </div>

                                    <!-- Custom Cron Expression -->
                                    <div x-show="frequency === 'custom'" x-transition>
                                        <label for="threat_update_cron" class="block text-sm font-medium text-gray-700 mb-2">
                                            Cron Expression
                                        </label>
                                        <input type="text" name="threat_update_cron" id="threat_update_cron"
                                               value="{{ old('threat_update_cron', \App\Models\Setting::get('threat_update_cron', 'string', '')) }}"
                                               placeholder="0 3 * * *"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 font-mono">
                                        <div class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded">
                                            <p class="text-xs text-blue-800 font-medium mb-2">📖 รูปแบบ Cron Expression:</p>
                                            <code class="text-xs text-blue-900">* * * * *</code>
                                            <p class="text-xs text-blue-700 mt-1">นาที ชั่วโมง วัน เดือน วันในสัปดาห์</p>
                                            <div class="mt-2 space-y-1 text-xs text-blue-700">
                                                <p>• <code>0 3 * * *</code> - ทุกวันเวลา 03:00</p>
                                                <p>• <code>0 */6 * * *</code> - ทุก 6 ชั่วโมง</p>
                                                <p>• <code>0 0 * * 0</code> - ทุกวันอาทิตย์เวลา 00:00</p>
                                                <p>• <code>*/30 * * * *</code> - ทุก 30 นาที</p>
                                            </div>
                                            <p class="text-xs text-blue-600 mt-2">
                                                🔗 <a href="https://crontab.guru/" target="_blank" class="underline hover:text-blue-800">ตรวจสอบ Cron Expression ที่ crontab.guru</a>
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Current Schedule Info -->
                                    <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
                                        <p class="text-xs text-yellow-800">
                                            <strong>💡 สำคัญ:</strong> ต้องตั้งค่า Cron Job บนเซิร์ฟเวอร์ด้วย:
                                        </p>
                                        <code class="block mt-2 p-2 bg-gray-800 text-green-400 text-xs rounded font-mono">
                                            * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
                                        </code>
                                        <p class="text-xs text-yellow-700 mt-2">
                                            Cron job นี้จะรันทุกนาทีและ Laravel จะจัดการ schedule ที่คุณตั้งค่าไว้
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- API Keys -->
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                                <h4 class="text-md font-semibold text-gray-900 mb-4">🔑 API Keys (Optional)</h4>

                                <div class="space-y-4">
                                    <div>
                                        <label for="abuseipdb_api_key" class="block text-sm font-medium text-gray-700 mb-2">
                                            AbuseIPDB API Key
                                        </label>
                                        <input type="text" name="abuseipdb_api_key" id="abuseipdb_api_key"
                                               value="{{ old('abuseipdb_api_key', env('ABUSEIPDB_API_KEY', '')) }}"
                                               placeholder="Your AbuseIPDB API Key"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                        <p class="mt-1 text-xs text-gray-500">
                                            Free tier: 1,000 requests/day • <a href="https://www.abuseipdb.com/api" target="_blank" class="text-blue-600 hover:underline">Get API Key</a>
                                        </p>
                                    </div>

                                    <div>
                                        <label for="ipqualityscore_api_key" class="block text-sm font-medium text-gray-700 mb-2">
                                            IPQualityScore API Key
                                        </label>
                                        <input type="text" name="ipqualityscore_api_key" id="ipqualityscore_api_key"
                                               value="{{ old('ipqualityscore_api_key', env('IPQUALITYSCORE_API_KEY', '')) }}"
                                               placeholder="Your IPQualityScore API Key"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                        <p class="mt-1 text-xs text-gray-500">
                                            Free tier: 5,000 requests/month • <a href="https://www.ipqualityscore.com/create-account" target="_blank" class="text-blue-600 hover:underline">Get API Key</a>
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
                                    <p class="text-xs text-yellow-800">
                                        <strong>💡 หมายเหตุ:</strong> ระบบจะใช้ Firehol Blocklists (ฟรี) โดยอัตโนมัติ API keys ข้างต้นเป็น optional เพื่อเพิ่มความแม่นยำ
                                    </p>
                                </div>
                            </div>

                            <!-- Update Button -->
                            <div class="flex items-center justify-between">
                                <form method="POST" action="{{ route('admin.security.threat-intelligence.update') }}" class="inline">
                                    @csrf
                                    <button type="submit" class="px-6 py-2 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition">
                                        🔄 อัปเดตข้อมูลทันที
                                    </button>
                                </form>

                                <button type="submit" class="px-6 py-2 bg-purple-600 text-white font-semibold rounded-lg hover:bg-purple-700 transition">
                                    บันทึกการตั้งค่า
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Info Box -->
                    <div class="bg-gradient-to-r from-blue-50 to-cyan-50 border border-blue-200 rounded-lg p-6">
                        <h4 class="text-md font-semibold text-gray-900 mb-3">📚 แหล่งข้อมูล Threat Intelligence</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                            <div>
                                <h5 class="font-semibold text-blue-900 mb-2">🆓 Firehol Blocklists</h5>
                                <ul class="text-blue-800 space-y-1 text-xs">
                                    <li>• ฟรี ไม่ต้อง API Key</li>
                                    <li>• อัปเดตทุกวันเวลา 03:00</li>
                                    <li>• บล็อกลิสต์ทั่วโลก</li>
                                </ul>
                            </div>

                            <div>
                                <h5 class="font-semibold text-blue-900 mb-2">🔍 AbuseIPDB</h5>
                                <ul class="text-blue-800 space-y-1 text-xs">
                                    <li>• Free: 1,000 req/day</li>
                                    <li>• IP abuse reports</li>
                                    <li>• Community-driven</li>
                                </ul>
                            </div>

                            <div>
                                <h5 class="font-semibold text-blue-900 mb-2">🛡️ IPQualityScore</h5>
                                <ul class="text-blue-800 space-y-1 text-xs">
                                    <li>• Free: 5,000 req/month</li>
                                    <li>• VPN/Proxy detection</li>
                                    <li>• Fraud scoring</li>
                                </ul>
                            </div>
                        </div>
                    </div>
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
