@extends('layouts.admin-v3')

@section('title', $pageTitle)

@section('content')
<div x-data="providerDashboard()" x-init="init()" class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.ai-api-keys.index') }}"
               class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $providerName }}</h1>
                <p class="text-gray-500 dark:text-gray-400 mt-1">จัดการ API Keys สำหรับ {{ $providerName }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <button @click="refreshStats()"
                    class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition flex items-center gap-2">
                <svg class="w-4 h-4" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                รีเฟรช
            </button>
            <a href="{{ route('admin.ai-api-keys.create', $provider) }}"
               class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                เพิ่ม API Key
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_keys'] }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Keys ทั้งหมด</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['active_keys'] }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Active</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $stats['available_keys'] }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">พร้อมใช้</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($stats['tokens_used_today']) }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Tokens วันนี้</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
            <p class="text-2xl font-bold text-cyan-600 dark:text-cyan-400">{{ number_format($stats['tokens_used_month']) }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Tokens เดือนนี้</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($stats['tokens_used_total']) }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Tokens รวม</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
            <p class="text-2xl font-bold text-rose-600 dark:text-rose-400">{{ number_format($stats['requests_today']) }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Requests วันนี้</p>
        </div>
    </div>

    {{-- Settings Card --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ตั้งค่าการวนใช้ Keys</h3>
        <form @submit.prevent="saveSettings()" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">โหมดวนใช้</label>
                <select x-model="currentSettings.rotation_mode"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    @foreach($rotationModes as $mode => $description)
                    <option value="{{ $mode }}">{{ ucfirst(str_replace('_', ' ', $mode)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Max Errors ก่อน Disable</label>
                <input type="number" x-model="currentSettings.max_consecutive_errors" min="1" max="10"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Disable Duration (นาที)</label>
                <input type="number" x-model="currentSettings.disable_duration_minutes" min="1" max="1440"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <div class="flex items-end">
                <button type="submit" :disabled="savingSettings"
                        class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white rounded-lg transition flex items-center justify-center gap-2">
                    <svg x-show="savingSettings" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    บันทึก Settings
                </button>
            </div>
        </form>
    </div>

    {{-- Keys Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ชื่อ / Key</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Priority</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tokens วันนี้</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tokens เดือนนี้</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Requests</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ใช้ล่าสุด</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($keys as $key)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition" x-data="{ showActions: false }">
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="font-medium text-gray-900 dark:text-white">{{ $key['name'] }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $key['masked_key'] }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            {{-- 🔴 (2026-05-01) Critical state — สูงสุด priority --}}
                            @if(!empty($key['is_critical']))
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-600 text-white animate-pulse">
                                🔴 CRITICAL
                            </span>
                            @elseif($key['is_available'])
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                พร้อมใช้
                            </span>
                            @elseif($key['is_active'])
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300">
                                @if($key['disabled_until'])
                                    Disabled {{ $key['disabled_until'] }}
                                @elseif($key['daily_usage_percent'] >= 100)
                                    ถึง Limit
                                @else
                                    Active
                                @endif
                            </span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">
                                ปิดใช้งาน
                            </span>
                            @endif

                            {{-- 🩺 Health-check attempts (1/3, 2/3, 3/3) --}}
                            @if(!empty($key['error_check_attempts']) && $key['error_check_attempts'] > 0)
                            <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300"
                                  title="Health-check attempts ก่อน critical">
                                🩺 {{ $key['error_check_attempts'] }}/3
                            </span>
                            @endif

                            {{-- 🎯 Per-key model badge --}}
                            @if(!empty($key['model']))
                            <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300"
                                  title="Model สำหรับ key นี้">
                                🎯 {{ $key['model'] }}
                            </span>
                            @endif

                            {{-- 🌟 (2026-05-05) Purpose badge — แสดงวัตถุประสงค์ของ key --}}
                            @php
                                $purposeBadges = [
                                    'free_card' => ['emoji' => '🎁', 'label' => 'ฟรี', 'class' => 'bg-pink-100 dark:bg-pink-900/30 text-pink-700 dark:text-pink-300'],
                                    'prediction' => ['emoji' => '💎', 'label' => 'ทำนายเสีย', 'class' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300'],
                                    'prediction_deep' => ['emoji' => '🌟', 'label' => 'Deep 39', 'class' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300'],
                                    'prediction_celtic' => ['emoji' => '💎', 'label' => 'Celtic 99', 'class' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300'],
                                    'chat' => ['emoji' => '💬', 'label' => 'แชท', 'class' => 'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300'],
                                    // 💙 (2026-05-23) Paid chat — LAST RESORT badge สีฟ้าเข้ม
                                    'chat_paid' => ['emoji' => '💙', 'label' => 'แชทพิเศษ', 'class' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 ring-1 ring-blue-400'],
                                    'sensitive' => ['emoji' => '🌟', 'label' => 'Sensitive', 'class' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300'],
                                    'tts' => ['emoji' => '🎙️', 'label' => 'TTS', 'class' => 'bg-gray-100 dark:bg-gray-900/30 text-gray-700 dark:text-gray-300'],
                                ];
                                $purpose = $key['purpose'] ?? 'any';
                            @endphp
                            @if(isset($purposeBadges[$purpose]))
                            <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $purposeBadges[$purpose]['class'] }}"
                                  title="วัตถุประสงค์: {{ $purpose }}">
                                {{ $purposeBadges[$purpose]['emoji'] }} {{ $purposeBadges[$purpose]['label'] }}
                            </span>
                            @endif

                            @if($key['consecutive_errors'] > 0)
                            {{-- 🔍 Clickable error badge — เปิด modal แสดง last_error + ปุ่มไปดู logs ทั้งหมด --}}
                            <button type="button"
                                    @click="showErrorDetail({
                                        id: {{ $key['id'] }},
                                        name: @js($key['name']),
                                        consecutive_errors: {{ $key['consecutive_errors'] }},
                                        last_error: @js($key['last_error'] ?? ''),
                                        last_error_at: @js($key['last_error_at'] ?? ''),
                                    })"
                                    class="ml-1 inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 hover:bg-red-200 dark:hover:bg-red-900/50 transition cursor-pointer"
                                    title="คลิกเพื่อดู error message">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                {{ $key['consecutive_errors'] }} errors
                            </button>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900 dark:text-white">{{ $key['priority'] }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="text-sm text-gray-900 dark:text-white">{{ number_format($key['tokens_used_today']) }}</span>
                                @if($key['tokens_limit_daily'])
                                <div class="w-20 h-1.5 bg-gray-200 dark:bg-gray-600 rounded-full mt-1 overflow-hidden">
                                    <div class="h-full rounded-full {{ $key['daily_usage_percent'] >= 90 ? 'bg-red-500' : ($key['daily_usage_percent'] >= 70 ? 'bg-yellow-500' : 'bg-green-500') }}"
                                         style="width: {{ min(100, $key['daily_usage_percent']) }}%"></div>
                                </div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $key['daily_usage_percent'] }}%</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="text-sm text-gray-900 dark:text-white">{{ number_format($key['tokens_used_month']) }}</span>
                                @if($key['tokens_limit_monthly'])
                                <div class="w-20 h-1.5 bg-gray-200 dark:bg-gray-600 rounded-full mt-1 overflow-hidden">
                                    <div class="h-full rounded-full {{ $key['monthly_usage_percent'] >= 90 ? 'bg-red-500' : ($key['monthly_usage_percent'] >= 70 ? 'bg-yellow-500' : 'bg-green-500') }}"
                                         style="width: {{ min(100, $key['monthly_usage_percent']) }}%"></div>
                                </div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $key['monthly_usage_percent'] }}%</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900 dark:text-white">{{ number_format($key['requests_today']) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $key['last_used_at'] ?? 'ยังไม่ใช้งาน' }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button @click="toggleKey({{ $key['id'] }})"
                                        class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition"
                                        title="{{ $key['is_active'] ? 'ปิดใช้งาน' : 'เปิดใช้งาน' }}">
                                    @if($key['is_active'])
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    @else
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                                    </svg>
                                    @endif
                                </button>
                                <button @click="testKey({{ $key['id'] }})"
                                        class="p-1.5 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition"
                                        title="ทดสอบ">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </button>
                                @if($key['consecutive_errors'] > 0)
                                <button @click="resetErrors({{ $key['id'] }})"
                                        class="p-1.5 text-gray-400 hover:text-yellow-600 dark:hover:text-yellow-400 transition"
                                        title="รีเซ็ต Errors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                </button>
                                @endif
                                {{-- 📜 ดู logs ของ key นี้ทั้งหมด — รองรับทุก provider --}}
                                <a href="{{ route('admin.ai-api-keys.key.logs', $key['id']) }}"
                                   class="p-1.5 text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition"
                                   title="ดู Usage Logs">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                    </svg>
                                </a>
                                <a href="{{ route('admin.ai-api-keys.edit', $key['id']) }}"
                                   class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition"
                                   title="แก้ไข">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                <button @click="deleteKey({{ $key['id'] }}, '{{ $key['name'] }}')"
                                        class="p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition"
                                        title="ลบ">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                </svg>
                                <p>ยังไม่มี API Key สำหรับ {{ $providerName }}</p>
                                <a href="{{ route('admin.ai-api-keys.create', $provider) }}"
                                   class="mt-3 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                                    + เพิ่ม API Key
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 🚨 Error Detail Modal — ดูรายละเอียด error ของ key ที่คลิก --}}
    <div x-show="errorModal.open"
         x-cloak
         x-transition.opacity
         @keydown.escape.window="errorModal.open = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
         @click.self="errorModal.open = false">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-2xl w-full max-h-[85vh] overflow-hidden flex flex-col"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">

            {{-- Header --}}
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Error ล่าสุดของ <span x-text="errorModal.name"></span>
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            <span x-text="errorModal.consecutive_errors"></span> errors ติดต่อกัน
                            <template x-if="errorModal.last_error_at">
                                <span> · เกิดเมื่อ <span x-text="errorModal.last_error_at"></span></span>
                            </template>
                        </p>
                    </div>
                </div>
                <button type="button" @click="errorModal.open = false"
                        class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-4">
                <template x-if="errorModal.last_error">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">
                            Error message
                        </label>
                        <pre class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-sm text-red-700 dark:text-red-300 whitespace-pre-wrap break-words font-mono"
                             x-text="errorModal.last_error"></pre>
                    </div>
                </template>
                <template x-if="!errorModal.last_error">
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-sm">ยังไม่มี error message ที่บันทึกไว้</p>
                        <p class="text-xs mt-1">บิลรุ่นเก่าอาจไม่ได้บันทึก — ดู Usage Logs เพื่อดูรายละเอียด</p>
                    </div>
                </template>

                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                    <p class="text-xs text-blue-800 dark:text-blue-300">
                        💡 ดู usage logs ทั้งหมดของ key นี้เพื่อตรวจประวัติ error และคำขอที่สำเร็จ/ล้มเหลว
                    </p>
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between gap-3">
                <button type="button"
                        @click="resetErrors(errorModal.id); errorModal.open = false"
                        class="px-4 py-2 text-sm font-medium text-yellow-700 dark:text-yellow-300 bg-yellow-50 dark:bg-yellow-900/20 hover:bg-yellow-100 dark:hover:bg-yellow-900/40 rounded-lg transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    รีเซ็ต Errors
                </button>
                <div class="flex items-center gap-2">
                    <button type="button" @click="errorModal.open = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition">
                        ปิด
                    </button>
                    <a :href="`/admin/ai-api-keys/${errorModal.id}/logs`"
                       class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        ดู Usage Logs ทั้งหมด
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function providerDashboard() {
    return {
        loading: false,
        savingSettings: false,
        currentSettings: @json($settings),

        // 🚨 Error detail modal state
        errorModal: {
            open: false,
            id: null,
            name: '',
            consecutive_errors: 0,
            last_error: '',
            last_error_at: '',
        },

        // 📋 เปิด modal แสดง error detail (เรียกจาก clickable badge)
        showErrorDetail(payload) {
            this.errorModal = {
                open: true,
                id: payload.id,
                name: payload.name || '',
                consecutive_errors: payload.consecutive_errors || 0,
                last_error: payload.last_error || '',
                last_error_at: payload.last_error_at || '',
            };
        },

        init() {
            setInterval(() => this.refreshStats(), 30000);
        },

        async refreshStats() {
            this.loading = true;
            try {
                const response = await fetch('{{ route('admin.ai-api-keys.provider.stats', $provider) }}');
                const data = await response.json();
                if (data.success) {
                    // Refresh page to update table data
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
            } finally {
                this.loading = false;
            }
        },

        async saveSettings() {
            this.savingSettings = true;
            try {
                const response = await fetch('{{ route('admin.ai-api-keys.provider.settings', $provider) }}', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.currentSettings)
                });
                const data = await response.json();
                if (data.success) {
                    alert('บันทึก Settings สำเร็จ');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาด');
            } finally {
                this.savingSettings = false;
            }
        },

        async toggleKey(id) {
            try {
                const response = await fetch(`/admin/ai-api-keys/${id}/toggle`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                if (data.success) {
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        },

        async testKey(id) {
            try {
                const response = await fetch(`/admin/ai-api-keys/${id}/test`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                alert(data.message);
            } catch (error) {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการทดสอบ');
            }
        },

        async resetErrors(id) {
            try {
                const response = await fetch(`/admin/ai-api-keys/${id}/reset-errors`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                if (data.success) {
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        },

        async deleteKey(id, name) {
            if (!confirm(`ต้องการลบ API Key "${name}" หรือไม่?`)) return;

            try {
                const response = await fetch(`/admin/ai-api-keys/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                if (data.success) {
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
    };
}
</script>
@endpush
@endsection
