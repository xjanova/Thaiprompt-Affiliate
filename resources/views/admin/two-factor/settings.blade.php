@extends('layouts.admin-v3')

@section('title', 'Two-Factor Authentication Settings')

@section('content')
<div class="container-fluid px-4 py-6" x-data="{
    enabled: {{ old('enabled', $settings->enabled) ? 'true' : 'false' }},
    defaultMethod: '{{ old('default_method', $settings->default_method) }}'
}">
    <!-- Animated Header -->
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-700 p-8 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>
        <div class="relative flex items-center justify-between">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-14 h-14 rounded-xl glass-fusion backdrop-blur-sm flex items-center justify-center" border border-white/20 dark:border-white/10>
                        <i class="fas fa-user-shield text-3xl text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-1">Two-Factor Authentication</h1>
                        <p class="text-purple-100">Advanced security with multi-channel 2FA verification</p>
                    </div>
                </div>
            </div>
            <div>
                <a href="{{ route('admin.dashboard') }}"
                   class="px-6 py-3 glass-fusion backdrop-blur-md border border-white/30 text-white rounded-xl hover:glass-fusion transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <i class="fas fa-arrow-left me-2"></i>Back
                </a>
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

    <form action="{{ route('admin.two-factor.settings') }}" method="POST">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Settings (Left - 2 columns) -->
            <div class="lg:col-span-2 space-y-6">

                <!-- System Status Card -->
                <div class="glass-fusion rounded-2xl shadow-lg border border-gray-100 overflow-hidden" border border-white/20 dark:border-white/10>
                    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white flex items-center">
                            <i class="fas fa-power-off mr-2"></i>
                            System Control
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <h4 class="text-lg font-bold text-gray-900 mb-1">Enable Two-Factor Authentication</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Activate 2FA security for your application</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="enabled" name="enabled" value="1" class="sr-only peer"
                                    {{ old('enabled', $settings->enabled) ? 'checked' : '' }}
                                    x-model="enabled">
                                <div class="w-16 h-8 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-8 peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:glass-fusion after:border-gray-300 dark:border-gray-600 after:border after:rounded-full after:h-7 after:w-7 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-purple-500 peer-checked:to-indigo-600" border border-white/20 dark:border-white/10></div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- When to Require 2FA -->
                <div class="glass-fusion rounded-2xl shadow-lg border border-gray-100 overflow-hidden" border border-white/20 dark:border-white/10>
                    <div class="bg-gradient-to-r from-blue-500 to-cyan-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white flex items-center">
                            <i class="fas fa-lock mr-2"></i>
                            When to Require 2FA
                        </h3>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Choose which actions require two-factor authentication</p>

                        <div class="grid grid-cols-2 gap-4">
                            <!-- Login -->
                            <div class="flex items-center justify-between p-4 bg-gradient-to-r from-purple-50 to-indigo-50 rounded-xl border border-purple-200">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-purple-500 flex items-center justify-center">
                                        <i class="fas fa-sign-in-alt text-white"></i>
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-900">Login</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">User login attempts</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="require_on_login" value="1" class="sr-only peer"
                                        {{ old('require_on_login', $settings->require_on_login) ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:glass-fusion after:border-gray-300 dark:border-gray-600 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-500" border border-white/20 dark:border-white/10></div>
                                </label>
                            </div>

                            <!-- Withdrawal -->
                            <div class="flex items-center justify-between p-4 bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl border border-green-200">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-green-500 flex items-center justify-center">
                                        <i class="fas fa-money-bill-wave text-white"></i>
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-900">Withdrawal</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">Money withdrawals</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="require_on_withdrawal" value="1" class="sr-only peer"
                                        {{ old('require_on_withdrawal', $settings->require_on_withdrawal) ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:glass-fusion after:border-gray-300 dark:border-gray-600 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500" border border-white/20 dark:border-white/10></div>
                                </label>
                            </div>

                            <!-- Transfer -->
                            <div class="flex items-center justify-between p-4 bg-gradient-to-r from-blue-50 to-cyan-50 rounded-xl border border-blue-200">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-blue-500 flex items-center justify-center">
                                        <i class="fas fa-exchange-alt text-white"></i>
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-900">Transfer</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">Money transfers</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="require_on_transfer" value="1" class="sr-only peer"
                                        {{ old('require_on_transfer', $settings->require_on_transfer) ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:glass-fusion after:border-gray-300 dark:border-gray-600 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-500" border border-white/20 dark:border-white/10></div>
                                </label>
                            </div>

                            <!-- Profile Change -->
                            <div class="flex items-center justify-between p-4 bg-gradient-to-r from-orange-50 to-amber-50 rounded-xl border border-orange-200">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-orange-500 flex items-center justify-center">
                                        <i class="fas fa-user-edit text-white"></i>
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-900">Profile Change</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">Edit profile info</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="require_on_profile_change" value="1" class="sr-only peer"
                                        {{ old('require_on_profile_change', $settings->require_on_profile_change) ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:glass-fusion after:border-gray-300 dark:border-gray-600 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-500" border border-white/20 dark:border-white/10></div>
                                </label>
                            </div>

                            <!-- Password Change -->
                            <div class="flex items-center justify-between p-4 bg-gradient-to-r from-red-50 to-pink-50 rounded-xl border border-red-200">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-red-500 flex items-center justify-center">
                                        <i class="fas fa-key text-white"></i>
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-900">Password Change</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">Change password</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="require_on_password_change" value="1" class="sr-only peer"
                                        {{ old('require_on_password_change', $settings->require_on_password_change) ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-red-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:glass-fusion after:border-gray-300 dark:border-gray-600 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-red-500" border border-white/20 dark:border-white/10></div>
                                </label>
                            </div>

                            <!-- Payment Method Change -->
                            <div class="flex items-center justify-between p-4 bg-gradient-to-r from-violet-50 to-purple-50 rounded-xl border border-violet-200">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-violet-500 flex items-center justify-center">
                                        <i class="fas fa-credit-card text-white"></i>
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-900">Payment Method</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">Update payment info</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="require_on_payment_method_change" value="1" class="sr-only peer"
                                        {{ old('require_on_payment_method_change', $settings->require_on_payment_method_change) ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-violet-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:glass-fusion after:border-gray-300 dark:border-gray-600 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-violet-500" border border-white/20 dark:border-white/10></div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Verification Methods -->
                <div class="glass-fusion rounded-2xl shadow-lg border border-gray-100 overflow-hidden" border border-white/20 dark:border-white/10>
                    <div class="bg-gradient-to-r from-pink-500 to-rose-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white flex items-center">
                            <i class="fas fa-mobile-alt mr-2"></i>
                            Verification Methods
                        </h3>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Enable or disable verification channels</p>

                        <div class="grid grid-cols-3 gap-4">
                            <!-- SMS -->
                            <label class="relative cursor-pointer group">
                                <input type="checkbox" name="allow_sms" value="1" class="sr-only peer"
                                    {{ old('allow_sms', $settings->allow_sms) ? 'checked' : '' }}>
                                <div class="h-32 rounded-xl border-2 transition-all duration-300 flex flex-col items-center justify-center peer-checked:border-green-500 peer-checked:bg-green-50 peer-checked:shadow-lg peer-checked:scale-105 border-gray-200 dark:border-gray-700 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 group-hover:border-green-300">
                                    <div class="text-4xl mb-2">
                                        <i class="fas fa-sms" :class="'text-green-500'"></i>
                                    </div>
                                    <span class="font-bold text-gray-700 dark:text-gray-300">SMS</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Via phone</span>
                                </div>
                            </label>

                            <!-- LINE OA -->
                            <label class="relative cursor-pointer group">
                                <input type="checkbox" name="allow_line" value="1" class="sr-only peer"
                                    {{ old('allow_line', $settings->allow_line) ? 'checked' : '' }}>
                                <div class="h-32 rounded-xl border-2 transition-all duration-300 flex flex-col items-center justify-center peer-checked:border-green-500 peer-checked:bg-green-50 peer-checked:shadow-lg peer-checked:scale-105 border-gray-200 dark:border-gray-700 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 group-hover:border-green-300">
                                    <div class="text-4xl mb-2">
                                        <i class="fab fa-line" :class="'text-green-500'"></i>
                                    </div>
                                    <span class="font-bold text-gray-700 dark:text-gray-300">LINE OA</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Via LINE</span>
                                </div>
                            </label>

                            <!-- Email (Disabled) -->
                            <div class="relative opacity-50 cursor-not-allowed" title="Email verification coming soon">
                                <input type="checkbox" name="allow_email" value="1" class="sr-only" disabled>
                                <div class="h-32 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-gray-100/50 dark:bg-gray-800/50 flex flex-col items-center justify-center">
                                    <div class="text-4xl mb-2">
                                        <i class="fas fa-envelope text-gray-400"></i>
                                    </div>
                                    <span class="font-bold text-gray-500 dark:text-gray-400">Email</span>
                                    <span class="text-xs text-gray-400">Coming Soon</span>
                                </div>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="bg-gray-800 text-white text-xs px-3 py-1 rounded-full">Coming Soon</span>
                                </div>
                            </div>
                        </div>

                        <!-- Default Method -->
                        <div class="mt-6">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                                <i class="fas fa-star text-yellow-500 mr-1"></i> Default Verification Method
                            </label>
                            <div class="grid grid-cols-3 gap-3">
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="default_method" value="sms" class="sr-only peer"
                                        {{ old('default_method', $settings->default_method) === 'sms' ? 'checked' : '' }}
                                        x-model="defaultMethod">
                                    <div class="p-4 rounded-xl border-2 transition-all peer-checked:border-pink-500 peer-checked:bg-pink-50 border-gray-200 dark:border-gray-700 glass-fusion text-center" border border-white/20 dark:border-white/10>
                                        <i class="fas fa-sms text-2xl mb-2" :class="defaultMethod === 'sms' ? 'text-pink-500' : 'text-gray-400'"></i>
                                        <p class="font-bold text-sm" :class="defaultMethod === 'sms' ? 'text-pink-700' : 'text-gray-600 dark:text-gray-400'">SMS</p>
                                    </div>
                                </label>

                                <label class="relative cursor-pointer">
                                    <input type="radio" name="default_method" value="line" class="sr-only peer"
                                        {{ old('default_method', $settings->default_method) === 'line' ? 'checked' : '' }}
                                        x-model="defaultMethod">
                                    <div class="p-4 rounded-xl border-2 transition-all peer-checked:border-pink-500 peer-checked:bg-pink-50 border-gray-200 dark:border-gray-700 glass-fusion text-center" border border-white/20 dark:border-white/10>
                                        <i class="fab fa-line text-2xl mb-2" :class="defaultMethod === 'line' ? 'text-pink-500' : 'text-gray-400'"></i>
                                        <p class="font-bold text-sm" :class="defaultMethod === 'line' ? 'text-pink-700' : 'text-gray-600 dark:text-gray-400'">LINE</p>
                                    </div>
                                </label>

                                <div class="opacity-50 cursor-not-allowed">
                                    <div class="p-4 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-gray-100/50 dark:bg-gray-800/50 text-center">
                                        <i class="fas fa-envelope text-2xl mb-2 text-gray-400"></i>
                                        <p class="font-bold text-sm text-gray-500 dark:text-gray-400">Email</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security Settings -->
                <div class="glass-fusion rounded-2xl shadow-lg border border-gray-100 overflow-hidden" border border-white/20 dark:border-white/10>
                    <div class="bg-gradient-to-r from-amber-500 to-orange-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white flex items-center">
                            <i class="fas fa-shield-alt mr-2"></i>
                            Security Settings
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Grace Period -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-clock text-amber-500 mr-1"></i> Grace Period
                                </label>
                                <div class="relative">
                                    <input type="number" name="grace_period_minutes" value="{{ old('grace_period_minutes', $settings->grace_period_minutes) }}"
                                        min="0" max="120"
                                        class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all">
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm text-gray-500 dark:text-gray-400">min</span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Don't ask again for X minutes after verification</p>
                            </div>

                            <!-- Remember Device Days -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-laptop text-amber-500 mr-1"></i> Remember Device
                                </label>
                                <div class="relative">
                                    <input type="number" name="remember_device_days" value="{{ old('remember_device_days', $settings->remember_device_days) }}"
                                        min="1" max="365"
                                        class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all">
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm text-gray-500 dark:text-gray-400">days</span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Trust devices for X days</p>
                            </div>

                            <!-- Withdrawal Threshold -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-coins text-amber-500 mr-1"></i> Withdrawal Threshold
                                </label>
                                <div class="relative">
                                    <input type="number" name="withdrawal_threshold" value="{{ old('withdrawal_threshold', $settings->withdrawal_threshold) }}"
                                        min="0" step="0.01"
                                        class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all"
                                        placeholder="0.00">
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm text-gray-500 dark:text-gray-400">THB</span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Require 2FA above this amount (0 = always)</p>
                            </div>

                            <!-- Transfer Threshold -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-wallet text-amber-500 mr-1"></i> Transfer Threshold
                                </label>
                                <div class="relative">
                                    <input type="number" name="transfer_threshold" value="{{ old('transfer_threshold', $settings->transfer_threshold) }}"
                                        min="0" step="0.01"
                                        class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all"
                                        placeholder="0.00">
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm text-gray-500 dark:text-gray-400">THB</span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Require 2FA above this amount (0 = always)</p>
                            </div>

                            <!-- Allow Remember Device Toggle -->
                            <div class="col-span-2 flex items-center justify-between p-4 bg-gradient-to-r from-amber-50 to-orange-50 rounded-xl border border-amber-200">
                                <div>
                                    <p class="font-bold text-gray-900">Allow Remember Device</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Let users trust devices for faster login</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="allow_remember_device" value="1" class="sr-only peer"
                                        {{ old('allow_remember_device', $settings->allow_remember_device) ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:glass-fusion after:border-gray-300 dark:border-gray-600 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500" border border-white/20 dark:border-white/10></div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Status Sidebar (Right - 1 column) -->
            <div class="lg:col-span-1 space-y-6">

                <!-- Current Status Card -->
                <div class="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-2xl shadow-2xl p-6 text-white sticky top-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-12 h-12 rounded-xl glass-fusion backdrop-blur-sm flex items-center justify-center" border border-white/20 dark:border-white/10>
                            <i class="fas fa-chart-bar text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold">Current Status</h3>
                            <p class="text-sm text-purple-200">2FA Configuration</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 glass-fusion backdrop-blur-sm rounded-xl" border border-white/20 dark:border-white/10>
                            <span class="text-sm">System:</span>
                            @if($settings->enabled)
                                <span class="px-3 py-1 bg-green-500 rounded-full text-xs font-bold">Enabled</span>
                            @else
                                <span class="px-3 py-1 bg-red-500 rounded-full text-xs font-bold">Disabled</span>
                            @endif
                        </div>

                        <div class="flex items-center justify-between p-3 glass-fusion backdrop-blur-sm rounded-xl" border border-white/20 dark:border-white/10>
                            <span class="text-sm">Default Method:</span>
                            <span class="px-3 py-1 glass-fusion rounded-full text-xs font-bold uppercase">{{ $settings->default_method }}</span>
                        </div>

                        <div class="flex items-center justify-between p-3 glass-fusion backdrop-blur-sm rounded-xl" border border-white/20 dark:border-white/10>
                            <span class="text-sm">Grace Period:</span>
                            <span class="font-bold text-sm">{{ $settings->grace_period_minutes }} min</span>
                        </div>

                        <div class="flex items-center justify-between p-3 glass-fusion backdrop-blur-sm rounded-xl" border border-white/20 dark:border-white/10>
                            <span class="text-sm">Remember Device:</span>
                            <span class="font-bold text-sm">{{ $settings->remember_device_days }} days</span>
                        </div>
                    </div>

                    <div class="mt-6 p-4 glass-fusion backdrop-blur-sm rounded-xl" border border-white/20 dark:border-white/10>
                        <p class="text-xs text-purple-200 mb-2">Protected Actions:</p>
                        <div class="flex flex-wrap gap-2">
                            @if($settings->require_on_login)
                                <span class="px-2 py-1 glass-fusion rounded text-xs">Login</span>
                            @endif
                            @if($settings->require_on_withdrawal)
                                <span class="px-2 py-1 glass-fusion rounded text-xs">Withdrawal</span>
                            @endif
                            @if($settings->require_on_transfer)
                                <span class="px-2 py-1 glass-fusion rounded text-xs">Transfer</span>
                            @endif
                            @if($settings->require_on_profile_change)
                                <span class="px-2 py-1 glass-fusion rounded text-xs">Profile</span>
                            @endif
                            @if($settings->require_on_password_change)
                                <span class="px-2 py-1 glass-fusion rounded text-xs">Password</span>
                            @endif
                            @if($settings->require_on_payment_method_change)
                                <span class="px-2 py-1 glass-fusion rounded text-xs">Payment</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Setup Guide -->
                <div class="glass-fusion rounded-2xl shadow-lg border border-gray-100 p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center">
                            <i class="fas fa-book text-indigo-600"></i>
                        </div>
                        <h3 class="font-bold text-gray-900">Setup Guide</h3>
                    </div>

                    <ol class="space-y-3">
                        <li class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold">1</span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">Configure OTP providers in OTP Settings</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold">2</span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">Enable verification methods</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold">3</span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">Choose protected actions</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold">4</span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">Set security preferences</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xs font-bold">5</span>
                            <span class="text-sm text-gray-700 dark:text-gray-300 font-semibold">Enable 2FA system</span>
                        </li>
                    </ol>
                </div>

                <!-- Quick Links -->
                <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-2xl border border-purple-200 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-purple-500 flex items-center justify-center">
                            <i class="fas fa-link text-white"></i>
                        </div>
                        <h3 class="font-bold text-purple-900">Quick Links</h3>
                    </div>

                    <div class="space-y-2">
                        <a href="{{ route('admin.otp.settings') }}" class="flex items-center gap-2 p-3 glass-fusion rounded-xl hover:bg-purple-100 transition text-sm text-purple-700 font-medium">
                            <i class="fas fa-cog"></i>
                            <span>OTP Settings</span>
                        </a>
                        <a href="{{ route('user.two-factor.setup') }}" class="flex items-center gap-2 p-3 glass-fusion rounded-xl hover:bg-purple-100 transition text-sm text-purple-700 font-medium">
                            <i class="fas fa-user-shield"></i>
                            <span>User 2FA Setup</span>
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
                        <i class="fas fa-info-circle text-indigo-500 text-xl"></i>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Changes will take effect immediately for all users
                        </p>
                    </div>
                    <button type="submit" class="px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-bold">
                        <i class="fas fa-save mr-2"></i>Save Settings
                    </button>
                </div>
            </div>
        </div>
    </form>
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
@endsection
