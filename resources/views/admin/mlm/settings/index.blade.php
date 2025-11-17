@extends('layouts.admin-v3')

@section('title', 'ตั้งค่า MLM')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Premium Header -->
    <div class="mb-8">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h1 class="text-4xl font-bold bg-gradient-to-r from-purple-600 via-pink-600 to-orange-500 bg-clip-text text-transparent mb-2 animate-gradient">
                    <i class="fas fa-cog"></i> ตั้งค่า MLM
                </h1>
                <p class="text-gray-600 dark:text-gray-400 text-lg">Premium Edition - ระบบตั้งค่าแบบมืออาชีพ พร้อมการคำนวณ Real-time</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.mlm.calculator') }}"
                   class="px-6 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white rounded-xl shadow-lg transition-all duration-200 flex items-center gap-2 transform hover:scale-105">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    เครื่องคำนวณ
                </a>
            </div>
        </div>
    </div>

    <!-- Overpay Warning Alert -->
    @if(isset($currentCommissionPercentage))
    <div class="mb-6 rounded-xl p-4 border
        {{ $currentCommissionPercentage > 50 ? 'bg-red-50 border-red-200' : ($currentCommissionPercentage > 40 ? 'bg-yellow-50 border-yellow-200' : 'bg-green-50 border-green-200') }}">
        <div class="flex items-start">
            <svg class="w-6 h-6 mt-0.5 mr-3
                {{ $currentCommissionPercentage > 50 ? 'text-red-500' : ($currentCommissionPercentage > 40 ? 'text-yellow-500' : 'text-green-500') }}"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                @if($currentCommissionPercentage > 50)
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                @elseif($currentCommissionPercentage > 40)
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                @else
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                @endif
            </svg>
            <div class="flex-1">
                <h3 class="text-sm font-semibold
                    {{ $currentCommissionPercentage > 50 ? 'text-red-800' : ($currentCommissionPercentage > 40 ? 'text-yellow-800' : 'text-green-800') }}">
                    ประมาณการเปอร์เซ็นต์คอมมิชชั่นรวม: {{ number_format($currentCommissionPercentage, 2) }}%
                </h3>
                <p class="text-sm mt-1
                    {{ $currentCommissionPercentage > 50 ? 'text-red-700' : ($currentCommissionPercentage > 40 ? 'text-yellow-700' : 'text-green-700') }}">
                    @if($currentCommissionPercentage > 50)
                        <strong><i class="fas fa-exclamation-triangle"></i> อันตราย - Overpay!</strong> เปอร์เซ็นต์คอมมิชชั่นรวมเกิน 50% อาจทำให้ขาดทุน
                        <br>แนะนำ: ลดเปอร์เซ็นต์ Unilevel หรือ Binary เพื่อควบคุมต้นทุน
                    @elseif($currentCommissionPercentage > 40)
                        <strong><i class="fas fa-bolt"></i> คำเตือน</strong> เปอร์เซ็นต์คอมมิชชั่นใกล้ขีดจำกัด กรุณาตรวจสอบการคำนวณอีกครั้ง
                    @else
                        <strong>✓ ปลอดภัย</strong> เปอร์เซ็นต์คอมมิชชั่นอยู่ในเกณฑ์ที่เหมาะสม
                    @endif
                </p>
            </div>
        </div>
    </div>
    @endif

    <!-- Info Alert -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-blue-500 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <h3 class="text-sm font-semibold text-blue-800">การตั้งค่าระบบ MLM</h3>
                <p class="text-sm text-blue-700 mt-1">
                    ค่าเหล่านี้ใช้ควบคุมการทำงานของระบบ MLM โปรดตรวจสอบให้แน่ใจก่อนทำการเปลี่ยนแปลง
                    การเปลี่ยนแปลงบางอย่างอาจส่งผลกระทบต่อการคำนวณคอมมิชชั่น
                </p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <!-- Left Column: Settings Form (2/3 width) -->
        <div class="xl:col-span-2">
            <form id="settings-form" action="{{ route('admin.mlm.settings.update') }}" method="POST">
                @csrf
                @method('PUT')

                @forelse($settings as $group => $groupSettings)
        <!-- Settings Group -->
        <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl mb-6 overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-2xl" border border-white/20 dark:border-white/10>
            <div class="bg-gradient-to-r from-purple-600 to-pink-600 px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 glass-fusion rounded-xl flex items-center justify-center backdrop-blur-sm" border border-white/20 dark:border-white/10>
                        <span class="text-2xl">{{ getGroupIcon($group) }}</span>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-white">{{ getGroupName($group) }}</h2>
                        <p class="text-sm text-white/80">{{ getGroupDescription($group) }}</p>
                    </div>
                </div>
            </div>

            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($groupSettings as $setting)
                <div class="p-6 hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 transition-colors">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                        <!-- Setting Info -->
                        <div class="lg:col-span-2">
                            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-1">
                                {{ $setting->key }}
                            </label>
                            <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">
                                {{ app()->getLocale() === 'th' && $setting->description_th
                                    ? $setting->description_th
                                    : $setting->description }}
                            </p>
                            <div class="flex items-center gap-2 mt-2 flex-wrap">
                                <span class="px-2 py-1 text-xs rounded-full
                                    {{ $setting->type === 'string' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $setting->type === 'number' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $setting->type === 'boolean' ? 'bg-purple-100 text-purple-800' : '' }}
                                    {{ $setting->type === 'json' ? 'bg-pink-100 text-pink-800' : '' }}">
                                    {{ $setting->type }}
                                </span>

                                <!-- Visual Help Triggers -->
                                @if($setting->key === 'default_placement_strategy')
                                    <button type="button" onclick="showPlacementDemo('{{ $setting->value }}')"
                                            class="visual-help-trigger">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        ดูแอนิเมชั่น
                                    </button>
                                @elseif(str_contains($setting->key, 'compression') || str_contains($setting->key, 'rollup'))
                                    <button type="button" onclick="showRollupDemo()"
                                            class="visual-help-trigger">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        ดูแอนิเมชั่น Roll-up
                                    </button>
                                @elseif(str_contains($setting->key, 'binary'))
                                    <button type="button" onclick="showBinaryDemo()"
                                            class="visual-help-trigger">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 011-1V5a1 1 0 011-1h4a1 1 0 011 1v14a1 1 0 01-1 1h-4a1 1 0 01-1-1v-2a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5z"/>
                                        </svg>
                                        ดู Binary Tree
                                    </button>
                                @elseif($setting->key === 'unilevel_levels')
                                    <button type="button" onclick="showUnilevelDemo()"
                                            class="visual-help-trigger">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>
                                        </svg>
                                        ดู Unilevel Tree
                                    </button>
                                @endif
                            </div>
                        </div>

                        <!-- Setting Input -->
                        <div class="w-full">
                            @if(!$setting->is_editable)
                                <!-- Read-only display -->
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 px-4 py-3 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-xl border-2 border-gray-200 dark:border-gray-700 dark:border-gray-700">
                                        @if($setting->type === 'boolean')
                                            <span class="font-medium">{{ $setting->getTypedValue() ? '✓ เปิดใช้งาน' : '✗ ปิดใช้งาน' }}</span>
                                        @elseif($setting->type === 'json')
                                            <pre class="font-mono text-xs">{{ $setting->value }}</pre>
                                        @else
                                            <span class="font-medium">{{ $setting->value }}</span>
                                        @endif
                                    </div>
                                    <span class="px-3 py-2 text-xs bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-full whitespace-nowrap font-medium flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                        Read-only
                                    </span>
                                </div>
                            @else
                                <!-- Editable inputs based on input_type -->
                                @if(($setting->input_type ?? 'text') === 'toggle')
                                    <!-- Modern Toggle Switch (Line OA Style) -->
                                    <label class="flex items-center justify-between p-4 bg-gradient-to-r from-gray-50 to-white rounded-xl border-2 border-gray-200 dark:border-gray-700 dark:border-gray-700 hover:border-purple-300 cursor-pointer transition-all duration-200 group">
                                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 group-hover:text-purple-700 transition-colors">
                                            {{ $setting->getTypedValue() ? '✓ เปิดใช้งาน' : 'ปิดใช้งาน' }}
                                        </span>
                                        <div class="relative">
                                            <input type="checkbox"
                                                   name="settings[{{ $setting->key }}]"
                                                   value="1"
                                                   {{ $setting->getTypedValue() ? 'checked' : '' }}
                                                   class="sr-only peer">
                                            <div class="w-16 h-8 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-200 rounded-full peer peer-checked:after:translate-x-8 peer-checked:after:border-white after:content-[''] after:absolute after:top-1 after:left-1 after:glass-fusion dark:bg-gray-800 after:border-gray-300 dark:border-gray-600 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-purple-600 peer-checked:to-pink-600 shadow-inner" border border-white/20 dark:border-white/10></div>
                                        </div>
                                    </label>

                                @elseif(($setting->input_type ?? 'text') === 'select')
                                    <!-- Beautiful Dropdown (Line OA Style) -->
                                    <div class="relative">
                                        <select name="settings[{{ $setting->key }}]"
                                                class="w-full px-4 py-3 pr-10 glass-fusion dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 dark:border-gray-700 rounded-xl focus:ring-4 focus:ring-purple-200 focus:border-purple-500 appearance-none cursor-pointer text-base font-medium text-gray-700 dark:text-gray-300 hover:border-purple-300 transition-all duration-200"
                                                @if($setting->key === 'auto_placement_strategy') onchange="showPlacementDemo(this.value)" @endif>
                                            @foreach($setting->getAllowedValuesArray() as $option)
                                                <option value="{{ $option }}" {{ $setting->value == $option ? 'selected' : '' }}>
                                                    {{ match($option) {
                                                        'manual' => '<i class="fas fa-map-marker-alt"></i> Manual - วางด้วยตนเอง',
                                                        'left_first', 'fill_left' => '<i class="fas fa-arrow-left"></i> Fill Left - เติมซ้ายก่อน',
                                                        'right_first', 'fill_right' => '<i class="fas fa-arrow-right"></i> Fill Right - เติมขวาก่อน',
                                                        'weak_leg' => '<i class="fas fa-balance-scale"></i> Weak Leg - เติมขาอ่อน',
                                                        'strong_leg' => '<i class="fas fa-dumbbell"></i> Strong Leg - เติมขาแข็ง',
                                                        'balanced' => '<i class="fas fa-balance-scale"></i> Balanced - สมดุล',
                                                        'fill_level' => '<i class="fas fa-chart-bar"></i> Fill Level - เติมเต็มระดับ',
                                                        'percentage' => '% เปอร์เซ็นต์',
                                                        'full' => '<i class="fas fa-sync-alt"></i> เต็มทั้งหมด',
                                                        'none' => '<i class="fas fa-times-circle"></i> ไม่ล้าง',
                                                        'THB' => '🇹🇭 THB - บาทไทย',
                                                        'USD' => '🇺🇸 USD - ดอลลาร์สหรัฐ',
                                                        'EUR' => '🇪🇺 EUR - ยูโร',
                                                        'GBP' => '🇬🇧 GBP - ปอนด์',
                                                        'JPY' => '🇯🇵 JPY - เยน',
                                                        default => $option
                                                    } }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3">
                                            <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </div>
                                    </div>

                                @elseif(($setting->input_type ?? 'text') === 'number')
                                    <!-- Number Input with Unit (Line OA Style) -->
                                    <div class="relative">
                                        <input type="number"
                                               name="settings[{{ $setting->key }}]"
                                               value="{{ $setting->value }}"
                                               step="{{ $setting->type === 'integer' ? '1' : 'any' }}"
                                               placeholder="{{ $setting->placeholder ?? '' }}"
                                               onchange="updatePreview()"
                                               class="w-full px-4 py-3 {{ $setting->unit ? 'pr-20' : '' }} glass-fusion dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 dark:border-gray-700 rounded-xl focus:ring-4 focus:ring-purple-200 focus:border-purple-500 text-base font-semibold text-gray-700 dark:text-gray-300 placeholder-gray-400 hover:border-purple-300 transition-all duration-200">
                                        @if($setting->unit)
                                            <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                                <span class="text-sm font-bold text-purple-600 bg-purple-50 px-3 py-1 rounded-xl">{{ $setting->unit }}</span>
                                            </div>
                                        @endif
                                    </div>

                                @elseif(($setting->input_type ?? 'text') === 'unilevel_editor')
                                    <!-- Visual Unilevel Editor -->
                                    <div class="w-full">
                                        <div id="unilevel-editor" class="space-y-2 mb-3">
                                            <!-- Will be populated by JavaScript -->
                                        </div>
                                        <textarea name="settings[{{ $setting->key }}]"
                                                  id="unilevel-json"
                                                  class="hidden">{{ $setting->value }}</textarea>
                                        <button type="button" onclick="addUnilevelLevel()"
                                                class="w-full px-4 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl font-bold text-sm shadow-lg hover:shadow-xl transition-all duration-200 flex items-center justify-center gap-2 transform hover:scale-[1.02]">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            เพิ่มระดับใหม่
                                        </button>
                                    </div>

                                @elseif($setting->type === 'json' || $setting->type === 'array')
                                    <!-- JSON Textarea -->
                                    <textarea name="settings[{{ $setting->key }}]"
                                              rows="4"
                                              placeholder="{{ $setting->placeholder ?? 'กรอกข้อมูล JSON' }}"
                                              class="w-full px-4 py-3 glass-fusion dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 dark:border-gray-700 rounded-xl focus:ring-4 focus:ring-purple-200 focus:border-purple-500 font-mono text-sm text-gray-700 dark:text-gray-300 placeholder-gray-400 hover:border-purple-300 transition-all duration-200">{{ $setting->value }}</textarea>

                                @else
                                    <!-- Text Input (Line OA Style) -->
                                    <input type="text"
                                           name="settings[{{ $setting->key }}]"
                                           value="{{ $setting->value }}"
                                           placeholder="{{ $setting->placeholder ?? '' }}"
                                           class="w-full px-4 py-3 glass-fusion dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 dark:border-gray-700 rounded-xl focus:ring-4 focus:ring-purple-200 focus:border-purple-500 text-base font-medium text-gray-700 dark:text-gray-300 placeholder-gray-400 hover:border-purple-300 transition-all duration-200">
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach

        <!-- Save Button -->
        @if($settings->count() > 0)
        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.dashboard') }}"
               class="px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 text-gray-900 dark:text-white rounded-xl font-medium transition-colors">
                ยกเลิก
            </a>
            <button type="submit"
                    class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl font-medium shadow-lg transition-all duration-200 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                บันทึกการเปลี่ยนแปลง
            </button>
        </div>
        @endif
            </form>
        </div>

        <!-- Right Column: Live Preview & Dashboard (1/3 width) -->
        <div class="xl:col-span-1">
            <div class="sticky top-4 space-y-6">
                <!-- Commission Dashboard -->
                <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 bg-gradient-to-r from-purple-600 to-pink-600 rounded-xl flex items-center justify-center text-white text-sm"><i class="fas fa-bolt"></i></span>
                        ภาพรวมคอมมิชชั่น
                    </h3>

                    <!-- Commission Percentage Gauge -->
                    <div class="mb-6">
                        <div class="flex justify-between items-end mb-2">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400 dark:text-gray-400">เปอร์เซ็นต์รวม</span>
                            <span id="total-percentage-text" class="text-3xl font-bold text-purple-600">{{ number_format($currentCommissionPercentage ?? 0, 2) }}%</span>
                        </div>
                        <div class="relative w-full h-6 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden shadow-inner">
                            <div id="percentage-bar"
                                 class="absolute top-0 left-0 h-full transition-all duration-500 ease-out rounded-full"
                                 style="width: {{ min($currentCommissionPercentage ?? 0, 100) }}%; background: linear-gradient(90deg, #9333ea, #ec4899);"></div>
                            <div class="absolute top-0 left-1/2 w-0.5 h-full bg-yellow-400 opacity-50"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                            <span>0%</span>
                            <span class="text-yellow-600 font-semibold">← 50% (Limit)</span>
                            <span>100%</span>
                        </div>
                    </div>

                    <!-- Status Badge -->
                    <div id="overpay-status" class="mb-6 p-4 rounded-xl border-2 {{ ($currentCommissionPercentage ?? 0) > 50 ? 'bg-red-50 border-red-300' : (($currentCommissionPercentage ?? 0) > 40 ? 'bg-yellow-50 border-yellow-300' : 'bg-green-50 border-green-300') }}">
                        <div class="flex items-start gap-3">
                            <span class="text-2xl">
                                @if(($currentCommissionPercentage ?? 0) > 50)
                                    <i class="fas fa-exclamation-triangle"></i>
                                @elseif(($currentCommissionPercentage ?? 0) > 40)
                                    <i class="fas fa-bolt"></i>
                                @else
                                    <i class="fas fa-check-circle"></i>
                                @endif
                            </span>
                            <div class="flex-1">
                                <h4 id="status-title" class="font-bold text-sm {{ ($currentCommissionPercentage ?? 0) > 50 ? 'text-red-800' : (($currentCommissionPercentage ?? 0) > 40 ? 'text-yellow-800' : 'text-green-800') }}">
                                    @if(($currentCommissionPercentage ?? 0) > 50)
                                        อันตราย - Overpay!
                                    @elseif(($currentCommissionPercentage ?? 0) > 40)
                                        ใกล้ขีดจำกัด
                                    @else
                                        ปลอดภัย
                                    @endif
                                </h4>
                                <p id="status-message" class="text-xs mt-1 {{ ($currentCommissionPercentage ?? 0) > 50 ? 'text-red-700' : (($currentCommissionPercentage ?? 0) > 40 ? 'text-yellow-700' : 'text-green-700') }}">
                                    @if(($currentCommissionPercentage ?? 0) > 50)
                                        เปอร์เซ็นต์เกิน 50% อาจขาดทุน
                                    @elseif(($currentCommissionPercentage ?? 0) > 40)
                                        ควรตรวจสอบอีกครั้ง
                                    @else
                                        เปอร์เซ็นต์เหมาะสม
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Commission Chart -->
                    <div class="mb-4">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">แยกตามประเภท</h4>
                        <div style="height: 200px;">
                            <canvas id="commission-chart"></canvas>
                        </div>
                    </div>

                    <!-- Breakdown Details -->
                    <div id="breakdown-details" class="space-y-2 mb-4">
                        <!-- Will be populated by JavaScript -->
                    </div>

                    <!-- Recalculate Button -->
                    <button type="button" onclick="calculatePreview()" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium transition-colors flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        คำนวณใหม่
                    </button>
                </div>

                <!-- Tips Card -->
                <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-2xl shadow-lg p-5 border border-purple-200">
                    <h3 class="text-base font-bold text-purple-900 mb-3 flex items-center gap-2">
                        <i class="fas fa-lightbulb"></i> เคล็ดลับ
                    </h3>
                    <ul class="space-y-2 text-sm text-purple-800">
                        <li class="flex items-start gap-2">
                            <span class="text-purple-600 font-bold">•</span>
                            <span>เปอร์เซ็นต์รวมไม่ควรเกิน 50%</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-purple-600 font-bold">•</span>
                            <span>Unilevel ชั้นแรกควรสูงสุด</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-purple-600 font-bold">•</span>
                            <span>ใช้ Constraints จำกัดจ่ายต่อวัน</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-purple-600 font-bold">•</span>
                            <span>เปิด Roll-up รักษา flow</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Visual Learning Section -->
    <div class="mt-8 bg-gradient-to-br from-purple-600 via-pink-600 to-orange-500 rounded-3xl shadow-2xl p-8 text-white">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-12 h-12 glass-fusion rounded-2xl flex items-center justify-center backdrop-blur-sm" border border-white/20 dark:border-white/10>
                <span class="text-3xl"><i class="fas fa-film"></i></span>
            </div>
            <div>
                <h3 class="text-2xl font-bold">เรียนรู้ผ่านภาพเคลื่อนไหว</h3>
                <p class="text-white/80 text-sm">คลิกเพื่อดูแอนิเมชั่นอธิบายแนวคิดต่างๆ ของระบบ MLM</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Placement Strategies -->
            <div class="glass-fusion backdrop-blur-lg rounded-2xl p-5 hover:glass-fusion transition-all duration-200 cursor-pointer border border-white/20" border border-white/20 dark:border-white/10
                 onclick="showPlacementDemo('manual')">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 glass-fusion rounded-xl flex items-center justify-center" border border-white/20 dark:border-white/10>
                        <span class="text-2xl"><i class="fas fa-map-marker-alt"></i></span>
                    </div>
                    <h4 class="font-bold text-lg">Manual Placement</h4>
                </div>
                <p class="text-sm text-white/80">วางตำแหน่งด้วยตนเอง</p>
            </div>

            <div class="glass-fusion backdrop-blur-lg rounded-2xl p-5 hover:glass-fusion transition-all duration-200 cursor-pointer border border-white/20" border border-white/20 dark:border-white/10
                 onclick="showPlacementDemo('fill_left')">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 glass-fusion rounded-xl flex items-center justify-center" border border-white/20 dark:border-white/10>
                        <span class="text-2xl"><i class="fas fa-arrow-left"></i></span>
                    </div>
                    <h4 class="font-bold text-lg">Fill Left</h4>
                </div>
                <p class="text-sm text-white/80">เติมซ้ายก่อนเสมอ</p>
            </div>

            <div class="glass-fusion backdrop-blur-lg rounded-2xl p-5 hover:glass-fusion transition-all duration-200 cursor-pointer border border-white/20" border border-white/20 dark:border-white/10
                 onclick="showPlacementDemo('fill_right')">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 glass-fusion rounded-xl flex items-center justify-center" border border-white/20 dark:border-white/10>
                        <span class="text-2xl"><i class="fas fa-arrow-right"></i></span>
                    </div>
                    <h4 class="font-bold text-lg">Fill Right</h4>
                </div>
                <p class="text-sm text-white/80">เติมขวาก่อนเสมอ</p>
            </div>

            <div class="glass-fusion backdrop-blur-lg rounded-2xl p-5 hover:glass-fusion transition-all duration-200 cursor-pointer border border-white/20" border border-white/20 dark:border-white/10
                 onclick="showPlacementDemo('weak_leg')">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 glass-fusion rounded-xl flex items-center justify-center" border border-white/20 dark:border-white/10>
                        <span class="text-2xl"><i class="fas fa-balance-scale"></i></span>
                    </div>
                    <h4 class="font-bold text-lg">Weak Leg</h4>
                </div>
                <p class="text-sm text-white/80">เติมขาอ่อนเพื่อสมดุล</p>
            </div>

            <div class="glass-fusion backdrop-blur-lg rounded-2xl p-5 hover:glass-fusion transition-all duration-200 cursor-pointer border border-white/20" border border-white/20 dark:border-white/10
                 onclick="showPlacementDemo('strong_leg')">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 glass-fusion rounded-xl flex items-center justify-center" border border-white/20 dark:border-white/10>
                        <span class="text-2xl"><i class="fas fa-dumbbell"></i></span>
                    </div>
                    <h4 class="font-bold text-lg">Strong Leg</h4>
                </div>
                <p class="text-sm text-white/80">เติมขาแข็งสร้างโมเมนตัม</p>
            </div>

            <div class="glass-fusion backdrop-blur-lg rounded-2xl p-5 hover:glass-fusion transition-all duration-200 cursor-pointer border border-white/20" border border-white/20 dark:border-white/10
                 onclick="showRollupDemo()">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 glass-fusion rounded-xl flex items-center justify-center" border border-white/20 dark:border-white/10>
                        <span class="text-2xl"><i class="fas fa-arrow-up"></i></span>
                    </div>
                    <h4 class="font-bold text-lg">Roll-up</h4>
                </div>
                <p class="text-sm text-white/80">การยกคอมมิชชั่นข้าม Inactive</p>
            </div>

            <div class="glass-fusion backdrop-blur-lg rounded-2xl p-5 hover:glass-fusion transition-all duration-200 cursor-pointer border border-white/20" border border-white/20 dark:border-white/10
                 onclick="showBinaryDemo()">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 glass-fusion rounded-xl flex items-center justify-center" border border-white/20 dark:border-white/10>
                        <span class="text-2xl"><i class="fas fa-sync-alt"></i></span>
                    </div>
                    <h4 class="font-bold text-lg">Binary Tree</h4>
                </div>
                <p class="text-sm text-white/80">โครงสร้างแบบ 2 ขา</p>
            </div>

            <div class="glass-fusion backdrop-blur-lg rounded-2xl p-5 hover:glass-fusion transition-all duration-200 cursor-pointer border border-white/20" border border-white/20 dark:border-white/10
                 onclick="showUnilevelDemo()">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 glass-fusion rounded-xl flex items-center justify-center" border border-white/20 dark:border-white/10>
                        <span class="text-2xl"><i class="fas fa-chart-bar"></i></span>
                    </div>
                    <h4 class="font-bold text-lg">Unilevel Tree</h4>
                </div>
                <p class="text-sm text-white/80">โครงสร้างแบบไม่จำกัดกิ่ง</p>
            </div>
        </div>
    </div>

    <!-- Common Settings Reference -->
    <div class="mt-6 bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">การตั้งค่าทั่วไป</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-2">General Settings</h4>
                <ul class="space-y-1 text-gray-600 dark:text-gray-400 dark:text-gray-400">
                    <li>• <code class="glass-fusion dark:bg-gray-800 px-2 py-0.5 rounded">auto_approve_commissions</code> - อนุมัติคอมมิชชั่นอัตโนมัติ</li>
                    <li>• <code class="glass-fusion dark:bg-gray-800 px-2 py-0.5 rounded">commission_payout_day</code> - วันจ่ายคอมมิชชั่น</li>
                    <li>• <code class="glass-fusion dark:bg-gray-800 px-2 py-0.5 rounded">minimum_payout</code> - ขั้นต่ำในการถอน</li>
                </ul>
            </div>
            <div>
                <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-2">Calculation Settings</h4>
                <ul class="space-y-1 text-gray-600 dark:text-gray-400 dark:text-gray-400">
                    <li>• <code class="glass-fusion dark:bg-gray-800 px-2 py-0.5 rounded">global_commission_per_pv</code> - อัตราค่าคอม/PV โกลบอล</li>
                    <li>• <code class="glass-fusion dark:bg-gray-800 px-2 py-0.5 rounded">enable_compression</code> - เปิดใช้งาน Compression</li>
                    <li>• <code class="glass-fusion dark:bg-gray-800 px-2 py-0.5 rounded">enable_carry_forward</code> - เปิดใช้งาน Carry Forward</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Animated Visualizations Modal -->
<div id="visualModal" class="modal-overlay" onclick="closeModal(event)">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="sticky top-0 glass-fusion dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700 px-8 py-6 rounded-t-3xl z-10" border border-white/20 dark:border-white/10>
            <div class="flex justify-between items-center">
                <h2 id="modalTitle" class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent"></h2>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 dark:text-gray-400 transition-colors">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
        <div id="modalBody" class="p-8">
            <!-- Content will be injected here -->
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
let unilevelData = [];
let commissionChart = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeUnilevelEditor();
    initializeChart();
    updatePreview();
});

// Initialize Unilevel Visual Editor
function initializeUnilevelEditor() {
    const jsonTextarea = document.getElementById('unilevel-json');
    if (!jsonTextarea) return;

    try {
        unilevelData = JSON.parse(jsonTextarea.value);
    } catch(e) {
        unilevelData = [];
    }

    renderUnilevelEditor();
}

function renderUnilevelEditor() {
    const container = document.getElementById('unilevel-editor');
    if (!container) return;

    container.innerHTML = '';

    unilevelData.forEach((level, index) => {
        const levelDiv = document.createElement('div');
        levelDiv.className = 'flex items-center gap-3 p-4 bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl border-2 border-purple-200 hover:border-purple-400 transition-all duration-200 shadow-sm hover:shadow-md';
        levelDiv.innerHTML = `
            <div class="flex-shrink-0 w-20 text-center">
                <div class="px-3 py-1 bg-purple-600 text-white rounded-xl font-bold text-sm">
                    ชั้น ${level.level}
                </div>
            </div>
            <div class="flex-1 flex items-center gap-2">
                <input type="number"
                       value="${level.percentage}"
                       onchange="updateUnilevelLevel(${index}, this.value)"
                       class="flex-1 px-4 py-3 text-xl font-bold text-center border-2 border-purple-300 rounded-xl focus:ring-4 focus:ring-purple-500 focus:border-purple-500 glass-fusion"
                       step="0.1" min="0" max="100">
                <span class="text-2xl font-bold text-purple-600">%</span>
            </div>
            <button type="button" onclick="removeUnilevelLevel(${index})"
                    class="flex-shrink-0 p-3 text-red-600 hover:bg-red-100 rounded-xl transition-all duration-200 hover:scale-110">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
        `;
        container.appendChild(levelDiv);
    });

    updateUnilevelJSON();
    updatePreview();
}

function updateUnilevelLevel(index, newValue) {
    unilevelData[index].percentage = parseFloat(newValue);
    updateUnilevelJSON();
    updatePreview();
}

function addUnilevelLevel() {
    const nextLevel = unilevelData.length + 1;
    unilevelData.push({
        level: nextLevel,
        percentage: 1.0
    });
    renderUnilevelEditor();
}

function removeUnilevelLevel(index) {
    if (unilevelData.length <= 1) {
        alert('ต้องมีอย่างน้อย 1 ระดับ');
        return;
    }

    unilevelData.splice(index, 1);
    // Re-number levels
    unilevelData.forEach((level, idx) => {
        level.level = idx + 1;
    });
    renderUnilevelEditor();
}

function updateUnilevelJSON() {
    const jsonTextarea = document.getElementById('unilevel-json');
    if (jsonTextarea) {
        jsonTextarea.value = JSON.stringify(unilevelData);
    }
}

// Initialize Chart
function initializeChart() {
    const ctx = document.getElementById('commission-chart');
    if (!ctx) return;

    commissionChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Unilevel', 'Binary', 'เหลือ'],
            datasets: [{
                data: [26, 12, 62],
                backgroundColor: [
                    'rgba(147, 51, 234, 0.9)',
                    'rgba(236, 72, 153, 0.9)',
                    'rgba(209, 213, 219, 0.3)'
                ],
                borderColor: [
                    'rgb(147, 51, 234)',
                    'rgb(236, 72, 153)',
                    'rgb(209, 213, 219)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 10,
                        font: {
                            size: 11,
                            weight: 'bold'
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.parsed.toFixed(2) + '%';
                        }
                    }
                }
            }
        }
    });
}

// Update Preview and Calculate
function updatePreview() {
    calculatePreview();
}

async function calculatePreview() {
    try {
        // Calculate Unilevel total
        let unilevelTotal = 0;
        if (unilevelData.length > 0) {
            unilevelTotal = unilevelData.reduce((sum, level) => sum + parseFloat(level.percentage || 0), 0);
        }

        // Get Binary percentage
        const binaryInput = document.querySelector('input[name="settings[binary_match_percentage]"]');
        const binaryPercentage = binaryInput ? parseFloat(binaryInput.value || 0) : 0;
        const binaryEstimate = binaryPercentage * 0.5; // Estimate 50% weak leg

        const totalPercentage = unilevelTotal + binaryEstimate;

        // Update Dashboard UI
        updateDashboard(totalPercentage, unilevelTotal, binaryEstimate);

    } catch (error) {
        console.error('Preview calculation error:', error);
    }
}

function updateDashboard(total, unilevel, binary) {
    // Update percentage text
    const percentText = document.getElementById('total-percentage-text');
    if (percentText) {
        percentText.textContent = total.toFixed(2) + '%';
    }

    // Update progress bar
    const bar = document.getElementById('percentage-bar');
    if (bar) {
        bar.style.width = Math.min(total, 100) + '%';

        // Change color based on threshold
        if (total > 50) {
            bar.style.background = 'linear-gradient(90deg, #dc2626, #ef4444)';
        } else if (total > 40) {
            bar.style.background = 'linear-gradient(90deg, #f59e0b, #fbbf24)';
        } else {
            bar.style.background = 'linear-gradient(90deg, #9333ea, #ec4899)';
        }
    }

    // Update status
    const statusDiv = document.getElementById('overpay-status');
    const statusTitle = document.getElementById('status-title');
    const statusMessage = document.getElementById('status-message');

    if (statusDiv && statusTitle && statusMessage) {
        if (total > 50) {
            statusDiv.className = 'mb-6 p-4 rounded-xl border-2 bg-red-50 border-red-300';
            statusTitle.className = 'font-bold text-sm text-red-800';
            statusTitle.innerHTML = '<i class="fas fa-exclamation-triangle"></i> อันตราย - Overpay!';
            statusMessage.className = 'text-xs mt-1 text-red-700';
            statusMessage.textContent = 'เปอร์เซ็นต์เกิน 50% อาจขาดทุน';
        } else if (total > 40) {
            statusDiv.className = 'mb-6 p-4 rounded-xl border-2 bg-yellow-50 border-yellow-300';
            statusTitle.className = 'font-bold text-sm text-yellow-800';
            statusTitle.innerHTML = '<i class="fas fa-bolt"></i> ใกล้ขีดจำกัด';
            statusMessage.className = 'text-xs mt-1 text-yellow-700';
            statusMessage.textContent = 'ควรตรวจสอบอีกครั้ง';
        } else {
            statusDiv.className = 'mb-6 p-4 rounded-xl border-2 bg-green-50 border-green-300';
            statusTitle.className = 'font-bold text-sm text-green-800';
            statusTitle.innerHTML = '<i class="fas fa-check-circle"></i> ปลอดภัย';
            statusMessage.className = 'text-xs mt-1 text-green-700';
            statusMessage.textContent = 'เปอร์เซ็นต์เหมาะสม';
        }
    }

    // Update chart
    if (commissionChart) {
        const remaining = Math.max(0, 100 - total);
        commissionChart.data.datasets[0].data = [unilevel, binary, remaining];
        commissionChart.update();
    }

    // Update breakdown details
    const breakdownDiv = document.getElementById('breakdown-details');
    if (breakdownDiv) {
        const totalColor = total > 50 ? 'text-red-600' : (total > 40 ? 'text-yellow-600' : 'text-green-600');
        breakdownDiv.innerHTML = `
            <div class="flex justify-between items-center p-3 bg-purple-50 rounded-xl">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300">Unilevel</span>
                <span class="text-lg font-bold text-purple-600">${unilevel.toFixed(2)}%</span>
            </div>
            <div class="flex justify-between items-center p-3 bg-pink-50 rounded-xl">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300">Binary (ประมาณ)</span>
                <span class="text-lg font-bold text-pink-600">${binary.toFixed(2)}%</span>
            </div>
            <div class="flex justify-between items-center p-3 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 rounded-xl border-t-2 border-gray-300 dark:border-gray-600">
                <span class="text-sm font-bold text-gray-900">รวม</span>
                <span class="text-xl font-bold ${totalColor}">${total.toFixed(2)}%</span>
            </div>
        `;
    }
}

// ============= Visual Demonstration Functions =============

function closeModal(event) {
    if (event && event.target !== event.currentTarget && event.type !== 'click') return;
    document.getElementById('visualModal').classList.remove('active');
    document.body.style.overflow = '';
}

function showPlacementDemo(strategy) {
    const modal = document.getElementById('visualModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');

    const strategyInfo = {
        'manual': {
            title: '<i class="fas fa-map-marker-alt"></i> Manual Placement - วางตำแหน่งด้วยตนเอง',
            description: 'ผู้สนับสนุนเลือกตำแหน่งที่จะวางสมาชิกใหม่ด้วยตนเอง มีความยืดหยุ่นสูงสุด',
            color: '#3b82f6'
        },
        'fill_left': {
            title: '<i class="fas fa-arrow-left"></i> Fill Left - เติมซ้ายก่อน',
            description: 'วางสมาชิกใหม่ทางซ้ายก่อนเสมอ จนกว่าจะเต็ม แล้วค่อยไปขวา',
            color: '#10b981'
        },
        'fill_right': {
            title: '<i class="fas fa-arrow-right"></i> Fill Right - เติมขวาก่อน',
            description: 'วางสมาชิกใหม่ทางขวาก่อนเสมอ จนกว่าจะเต็ม แล้วค่อยไปซ้าย',
            color: '#f59e0b'
        },
        'weak_leg': {
            title: '<i class="fas fa-balance-scale"></i> Weak Leg - เติมขาอ่อน',
            description: 'วางสมาชิกใหม่ในขาที่มี PV น้อยกว่า เพื่อสร้างความสมดุล',
            color: '#ec4899'
        },
        'strong_leg': {
            title: '<i class="fas fa-dumbbell"></i> Strong Leg - เติมขาแข็ง',
            description: 'วางสมาชิกใหม่ในขาที่มี PV มากกว่า เพื่อสร้างโมเมนตัม',
            color: '#9333ea'
        }
    };

    const info = strategyInfo[strategy] || strategyInfo['manual'];

    modalTitle.textContent = info.title;
    modalBody.innerHTML = `
        <div class="space-y-6">
            <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-2xl p-6 border-2 border-purple-200">
                <p class="text-gray-700 dark:text-gray-300 text-lg">${info.description}</p>
            </div>

            <div class="placement-demo" id="placementDemoContainer">
                <svg width="100%" height="300" style="position: absolute; top: 0; left: 0;">
                    <!-- Lines will be drawn here -->
                    <line x1="50%" y1="60" x2="30%" y2="150" stroke="${info.color}" stroke-width="3" opacity="0.6" class="placement-line" />
                    <line x1="50%" y1="60" x2="70%" y2="150" stroke="${info.color}" stroke-width="3" opacity="0.6" class="placement-line" />
                    <line x1="30%" y1="150" x2="20%" y2="240" stroke="${info.color}" stroke-width="2" opacity="0.4" class="placement-line" />
                    <line x1="30%" y1="150" x2="40%" y2="240" stroke="${info.color}" stroke-width="2" opacity="0.4" class="placement-line" />
                </svg>

                <!-- Sponsor Node -->
                <div class="placement-node sponsor" style="top: 30px; left: calc(50% - 25px);">
                    <span>👤</span>
                </div>

                <!-- Level 1 Nodes -->
                <div class="placement-node member" style="top: 120px; left: calc(30% - 25px);">
                    <span>A</span>
                </div>
                <div class="placement-node member" style="top: 120px; left: calc(70% - 25px);">
                    <span>B</span>
                </div>

                <!-- Level 2 Nodes -->
                <div class="placement-node inactive" style="top: 210px; left: calc(20% - 25px);">
                    <span>?</span>
                </div>
                <div class="placement-node inactive" style="top: 210px; left: calc(40% - 25px);">
                    <span>?</span>
                </div>

                <!-- New Member (animated) -->
                <div class="placement-node new" id="newMemberNode" style="top: 210px; left: calc(60% - 25px); display: none;">
                    <span>NEW</span>
                </div>
            </div>

            <div class="flex justify-center">
                <button onclick="animatePlacement('${strategy}')"
                        class="px-8 py-4 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl font-bold text-lg shadow-lg transition-all duration-200 transform hover:scale-105 flex items-center gap-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    เล่นอนิเมชั่น
                </button>
            </div>

            <div class="bg-blue-50 border-2 border-blue-200 rounded-2xl p-6">
                <h4 class="font-bold text-blue-900 mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    เมื่อไหร่ควรใช้?
                </h4>
                <p class="text-blue-800 text-sm leading-relaxed" id="strategyAdvice">
                    ${getStrategyAdvice(strategy)}
                </p>
            </div>
        </div>
    `;

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';

    // Auto-play animation
    setTimeout(() => animatePlacement(strategy), 500);
}

function animatePlacement(strategy) {
    const newNode = document.getElementById('newMemberNode');
    if (!newNode) return;

    // Reset
    newNode.style.display = 'none';

    // Determine position based on strategy
    let positions = {
        'manual': { left: 'calc(60% - 25px)', top: '210px' },
        'fill_left': { left: 'calc(20% - 25px)', top: '210px' },
        'fill_right': { left: 'calc(70% - 25px)', top: '210px' },
        'weak_leg': { left: 'calc(60% - 25px)', top: '210px' },
        'strong_leg': { left: 'calc(20% - 25px)', top: '210px' }
    };

    let pos = positions[strategy] || positions['manual'];

    setTimeout(() => {
        newNode.style.left = pos.left;
        newNode.style.top = pos.top;
        newNode.style.display = 'flex';
    }, 100);
}

function getStrategyAdvice(strategy) {
    const advice = {
        'manual': 'เหมาะสำหรับทีมเล็ก หรือต้องการควบคุมโครงสร้างแบบละเอียด สามารถสร้างกลยุทธ์เฉพาะสำหรับสมาชิกแต่ละคน',
        'fill_left': 'เหมาะสำหรับระบบ Binary ที่ต้องการสร้างความลึกก่อน ช่วยให้ Binary matching เกิดได้เร็ว',
        'fill_right': 'เหมาะสำหรับระบบที่มีกลยุทธ์พิเศษ หรือต้องการสร้างทีมในขาเฉพาะ',
        'weak_leg': 'เหมาะสำหรับระบบ Binary ที่ต้องการความสมดุล ช่วยให้ทั้งสองขามีการเติบโตใกล้เคียงกัน',
        'strong_leg': 'เหมาะสำหรับสร้างโมเมนตัมและกำลังใจให้ทีม แต่อาจทำให้เกิดความไม่สมดุล'
    };
    return advice[strategy] || advice['manual'];
}

function showRollupDemo() {
    const modal = document.getElementById('visualModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');

    modalTitle.textContent = '<i class="fas fa-arrow-up"></i> Roll-up / Compression - การยกคอมมิชชั่น';
    modalBody.innerHTML = `
        <div class="space-y-6">
            <div class="bg-gradient-to-r from-yellow-50 to-orange-50 rounded-2xl p-6 border-2 border-yellow-300">
                <p class="text-gray-700 dark:text-gray-300 text-lg">
                    เมื่อสมาชิกในสายไม่มี PV หรือไม่ active คอมมิชชันจะถูก "ยกขึ้น" ไปให้ผู้บริหารคนแรกที่ active
                </p>
            </div>

            <div class="rollup-demo" style="background: white; border: 3px solid #e5e7eb;">
                <svg width="100%" height="350">
                    <!-- Main Line -->
                    <line x1="50%" y1="40" x2="50%" y2="310" stroke="#d1d5db" stroke-width="4" stroke-dasharray="8" />

                    <!-- Commission Flow Lines (animated) -->
                    <line x1="50%" y1="130" x2="50%" y2="70" stroke="#10b981" stroke-width="5" class="commission-flow" />
                    <line x1="50%" y1="220" x2="50%" y2="70" stroke="#10b981" stroke-width="5" class="commission-flow" style="animation-delay: 0.3s;" />
                    <line x1="50%" y1="310" x2="50%" y2="70" stroke="#10b981" stroke-width="5" class="commission-flow" style="animation-delay: 0.6s;" />

                    <!-- Nodes -->
                    <!-- Active Sponsor -->
                    <circle cx="50%" cy="40" r="35" fill="url(#gradientActive)" stroke="#10b981" stroke-width="3" />
                    <text x="50%" y="48" text-anchor="middle" fill="white" font-size="16" font-weight="bold">✓ Active</text>

                    <!-- Inactive 1 -->
                    <circle cx="50%" cy="130" r="30" fill="#e5e7eb" stroke="#9ca3af" stroke-width="2" />
                    <text x="50%" y="137" text-anchor="middle" fill="#6b7280" font-size="14" font-weight="bold">✗ Inactive</text>

                    <!-- Inactive 2 -->
                    <circle cx="50%" cy="220" r="30" fill="#e5e7eb" stroke="#9ca3af" stroke-width="2" />
                    <text x="50%" y="227" text-anchor="middle" fill="#6b7280" font-size="14" font-weight="bold">✗ Inactive</text>

                    <!-- Member with Sales -->
                    <circle cx="50%" cy="310" r="30" fill="url(#gradientMember)" stroke="#3b82f6" stroke-width="3" />
                    <text x="50%" y="317" text-anchor="middle" fill="white" font-size="14" font-weight="bold"><i class="fas fa-dollar-sign"></i> Sales</text>

                    <!-- Gradients -->
                    <defs>
                        <linearGradient id="gradientActive" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#10b981;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#34d399;stop-opacity:1" />
                        </linearGradient>
                        <linearGradient id="gradientMember" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#3b82f6;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#60a5fa;stop-opacity:1" />
                        </linearGradient>
                    </defs>

                    <!-- Arrows -->
                    <polygon points="48%,90 50%,80 52%,90" fill="#10b981" />
                    <polygon points="48%,180 50%,170 52%,180" fill="#10b981" style="animation: rollupFloat 2s ease-in-out infinite; animation-delay: 0.3s;" />
                    <polygon points="48%,270 50%,260 52%,270" fill="#10b981" style="animation: rollupFloat 2s ease-in-out infinite; animation-delay: 0.6s;" />
                </svg>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-green-50 border-2 border-green-200 rounded-xl p-4">
                    <h4 class="font-bold text-green-900 mb-2 flex items-center gap-2">
                        ✓ ข้อดี
                    </h4>
                    <ul class="text-sm text-green-800 space-y-1">
                        <li>• รักษา Commission Flow ให้ต่อเนื่อง</li>
                        <li>• ป้องกันรายได้หาย</li>
                        <li>• สร้างแรงจูงใจให้รักษาความ active</li>
                    </ul>
                </div>

                <div class="bg-orange-50 border-2 border-orange-200 rounded-xl p-4">
                    <h4 class="font-bold text-orange-900 mb-2 flex items-center gap-2">
                        <i class="fas fa-exclamation-triangle"></i> ข้อควรระวัง
                    </h4>
                    <ul class="text-sm text-orange-800 space-y-1">
                        <li>• อาจทำให้ผู้นำได้รับมากเกิน</li>
                        <li>• ต้องมีเงื่อนไขที่ชัดเจน</li>
                        <li>• อาจทำให้สายล่างท้อใจ</li>
                    </ul>
                </div>
            </div>
        </div>
    `;

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function showBinaryDemo() {
    const modal = document.getElementById('visualModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');

    modalTitle.textContent = '<i class="fas fa-sync-alt"></i> Binary Tree Structure - โครงสร้างแบบ Binary';
    modalBody.innerHTML = `
        <div class="space-y-6">
            <div class="bg-gradient-to-r from-pink-50 to-purple-50 rounded-2xl p-6 border-2 border-pink-200">
                <p class="text-gray-700 dark:text-gray-300 text-lg">
                    โครงสร้างแบบ Binary แต่ละคนมีได้เพียง 2 ขา (ซ้าย-ขวา) คอมมิชชันคำนวณจากขาที่อ่อนกว่า (Weak Leg)
                </p>
            </div>

            <div style="background: white; padding: 40px; border-radius: 16px; border: 2px solid #e5e7eb;">
                <svg width="100%" height="300" viewBox="0 0 600 300">
                    <!-- Lines -->
                    <line x1="300" y1="50" x2="150" y2="130" stroke="#9333ea" stroke-width="3" opacity="0.5" />
                    <line x1="300" y1="50" x2="450" y2="130" stroke="#ec4899" stroke-width="3" opacity="0.5" />
                    <line x1="150" y1="130" x2="75" y2="210" stroke="#9333ea" stroke-width="2" opacity="0.4" />
                    <line x1="150" y1="130" x2="225" y2="210" stroke="#9333ea" stroke-width="2" opacity="0.4" />
                    <line x1="450" y1="130" x2="375" y2="210" stroke="#ec4899" stroke-width="2" opacity="0.4" />
                    <line x1="450" y1="130" x2="525" y2="210" stroke="#ec4899" stroke-width="2" opacity="0.4" />

                    <!-- You -->
                    <circle cx="300" cy="50" r="35" class="tree-node level-1" />
                    <text x="300" y="58" text-anchor="middle" fill="white" font-size="18" font-weight="bold">YOU</text>

                    <!-- Level 1 Left -->
                    <circle cx="150" cy="130" r="30" class="tree-node level-2" />
                    <text x="150" y="137" text-anchor="middle" fill="white" font-size="16" font-weight="bold">L</text>
                    <text x="150" y="170" text-anchor="middle" fill="#9333ea" font-size="12" font-weight="bold">Weak Leg</text>
                    <text x="150" y="185" text-anchor="middle" fill="#9333ea" font-size="11">1,000 PV</text>

                    <!-- Level 1 Right -->
                    <circle cx="450" cy="130" r="30" class="tree-node level-2" />
                    <text x="450" y="137" text-anchor="middle" fill="white" font-size="16" font-weight="bold">R</text>
                    <text x="450" y="170" text-anchor="middle" fill="#ec4899" font-size="12" font-weight="bold">Strong Leg</text>
                    <text x="450" y="185" text-anchor="middle" fill="#ec4899" font-size="11">3,500 PV</text>

                    <!-- Level 2 -->
                    <circle cx="75" cy="210" r="25" class="tree-node level-3" />
                    <text x="75" y="217" text-anchor="middle" fill="white" font-size="14" font-weight="bold">A</text>

                    <circle cx="225" cy="210" r="25" class="tree-node level-3" />
                    <text x="225" y="217" text-anchor="middle" fill="white" font-size="14" font-weight="bold">B</text>

                    <circle cx="375" cy="210" r="25" class="tree-node level-4" />
                    <text x="375" y="217" text-anchor="middle" fill="white" font-size="14" font-weight="bold">C</text>

                    <circle cx="525" cy="210" r="25" class="tree-node level-4" />
                    <text x="525" y="217" text-anchor="middle" fill="white" font-size="14" font-weight="bold">D</text>

                    <!-- Commission Arrow -->
                    <path d="M 280 80 Q 250 105 180 130" stroke="#10b981" stroke-width="4" fill="none" stroke-dasharray="5" />
                    <polygon points="175,132 180,130 178,125" fill="#10b981" />
                    <text x="230" y="100" fill="#10b981" font-size="13" font-weight="bold">คำนวณจากขานี้!</text>
                </svg>
            </div>

            <div class="bg-purple-50 border-2 border-purple-200 rounded-xl p-5">
                <h4 class="font-bold text-purple-900 mb-3"><i class="fas fa-lightbulb"></i> วิธีคำนวณ Binary Commission</h4>
                <div class="space-y-2 text-sm text-purple-800">
                    <p class="font-medium">1. นับ PV แต่ละขา: ซ้าย 1,000 PV | ขวา 3,500 PV</p>
                    <p class="font-medium">2. เลือกขาอ่อน (Weak Leg): ขวาซ้าย (1,000 PV)</p>
                    <p class="font-medium">3. คำนวณ: 1,000 PV × 12% = <span class="text-purple-600 font-bold">120 บาท</span></p>
                    <p class="text-xs text-purple-600 mt-2">* ขาแข็ง (Strong Leg) เหลือ 2,500 PV ยกไปงวดหน้า (Carry Forward)</p>
                </div>
            </div>
        </div>
    `;

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function showUnilevelDemo() {
    const modal = document.getElementById('visualModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');

    modalTitle.textContent = '<i class="fas fa-chart-bar"></i> Unilevel Structure - โครงสร้างแบบ Unilevel';
    modalBody.innerHTML = `
        <div class="space-y-6">
            <div class="bg-gradient-to-r from-purple-50 to-blue-50 rounded-2xl p-6 border-2 border-purple-200">
                <p class="text-gray-700 dark:text-gray-300 text-lg">
                    โครงสร้างแบบ Unilevel ไม่จำกัดจำนวนคนต่อชั้น ได้คอมมิชชั่นจากทุกคนในสายโดยตรง ตามเปอร์เซ็นต์แต่ละชั้น
                </p>
            </div>

            <div style="background: white; padding: 40px; border-radius: 16px; border: 2px solid #e5e7eb;">
                <svg width="100%" height="350" viewBox="0 0 700 350">
                    <!-- You -->
                    <circle cx="350" cy="40" r="35" class="tree-node level-1" />
                    <text x="350" y="48" text-anchor="middle" fill="white" font-size="18" font-weight="bold">YOU</text>

                    <!-- Level 1 (5 people) -->
                    <g>
                        <text x="350" y="95" text-anchor="middle" fill="#9333ea" font-size="13" font-weight="bold">ชั้นที่ 1 (10%)</text>
                        <line x1="350" y1="75" x2="150" y2="125" stroke="#9333ea" stroke-width="2" opacity="0.5" />
                        <line x1="350" y1="75" x2="250" y2="125" stroke="#9333ea" stroke-width="2" opacity="0.5" />
                        <line x1="350" y1="75" x2="350" y2="125" stroke="#9333ea" stroke-width="2" opacity="0.5" />
                        <line x1="350" y1="75" x2="450" y2="125" stroke="#9333ea" stroke-width="2" opacity="0.5" />
                        <line x1="350" y1="75" x2="550" y2="125" stroke="#9333ea" stroke-width="2" opacity="0.5" />

                        <circle cx="150" cy="130" r="25" class="tree-node level-1" />
                        <circle cx="250" cy="130" r="25" class="tree-node level-1" />
                        <circle cx="350" cy="130" r="25" class="tree-node level-1" />
                        <circle cx="450" cy="130" r="25" class="tree-node level-1" />
                        <circle cx="550" cy="130" r="25" class="tree-node level-1" />
                    </g>

                    <!-- Level 2 (3 people shown) -->
                    <g>
                        <text x="350" y="185" text-anchor="middle" fill="#ec4899" font-size="13" font-weight="bold">ชั้นที่ 2 (6%)</text>
                        <line x1="250" y1="155" x2="200" y2="215" stroke="#ec4899" stroke-width="2" opacity="0.5" />
                        <line x1="350" y1="155" x2="300" y2="215" stroke="#ec4899" stroke-width="2" opacity="0.5" />
                        <line x1="350" y1="155" x2="400" y2="215" stroke="#ec4899" stroke-width="2" opacity="0.5" />

                        <circle cx="200" cy="220" r="22" class="tree-node level-2" />
                        <circle cx="300" cy="220" r="22" class="tree-node level-2" />
                        <circle cx="400" cy="220" r="22" class="tree-node level-2" />
                        <text x="500" y="225" fill="#6b7280" font-size="12">...</text>
                    </g>

                    <!-- Level 3 (2 people shown) -->
                    <g>
                        <text x="350" y="270" text-anchor="middle" fill="#f59e0b" font-size="13" font-weight="bold">ชั้นที่ 3 (4%)</text>
                        <line x1="300" y1="242" x2="280" y2="300" stroke="#f59e0b" stroke-width="2" opacity="0.5" />
                        <line x1="300" y1="242" x2="360" y2="300" stroke="#f59e0b" stroke-width="2" opacity="0.5" />

                        <circle cx="280" cy="305" r="20" class="tree-node level-3" />
                        <circle cx="360" cy="305" r="20" class="tree-node level-3" />
                        <text x="420" y="310" fill="#6b7280" font-size="12">...</text>
                    </g>
                </svg>
            </div>

            <div class="bg-gradient-to-r from-purple-50 to-pink-50 border-2 border-purple-300 rounded-xl p-5">
                <h4 class="font-bold text-purple-900 mb-3"><i class="fas fa-lightbulb"></i> ตัวอย่างการคำนวณ</h4>
                <div class="space-y-3">
                    <div class="flex items-center justify-between glass-fusion dark:bg-gray-800 rounded-xl p-3 shadow-sm" border border-white/20 dark:border-white/10>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300">ชั้น 1: 5 คน × 1,000 PV × 10%</span>
                        <span class="text-lg font-bold text-purple-600">= 500 บาท</span>
                    </div>
                    <div class="flex items-center justify-between glass-fusion dark:bg-gray-800 rounded-xl p-3 shadow-sm" border border-white/20 dark:border-white/10>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300">ชั้น 2: 15 คน × 1,000 PV × 6%</span>
                        <span class="text-lg font-bold text-pink-600">= 900 บาท</span>
                    </div>
                    <div class="flex items-center justify-between glass-fusion dark:bg-gray-800 rounded-xl p-3 shadow-sm" border border-white/20 dark:border-white/10>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300">ชั้น 3: 45 คน × 1,000 PV × 4%</span>
                        <span class="text-lg font-bold text-orange-600">= 1,800 บาท</span>
                    </div>
                    <div class="flex items-center justify-between bg-gradient-to-r from-purple-100 to-pink-100 rounded-xl p-4 border-2 border-purple-300">
                        <span class="text-base font-bold text-gray-900 dark:text-white dark:text-white">รวมทั้งหมด</span>
                        <span class="text-2xl font-bold text-purple-600">3,200 บาท</span>
                    </div>
                </div>
            </div>
        </div>
    `;

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}
</script>

@php
function getGroupIcon($group) {
    return match($group) {
        'general' => '<i class="fas fa-cog"></i>',
        'pv' => '💎',
        'unilevel' => '<i class="fas fa-chart-bar"></i>',
        'binary' => '<i class="fas fa-sync-alt"></i>',
        'flush' => '🌊',
        'placement' => '<i class="fas fa-map-marker-alt"></i>',
        'rollup' => '<i class="fas fa-arrow-up"></i>',
        'retention' => '🎯',
        'commission' => '<i class="fas fa-dollar-sign"></i>',
        default => '📋'
    };
}

function getGroupName($group) {
    return match($group) {
        'general' => 'ทั่วไป',
        'pv' => 'ระบบ PV',
        'unilevel' => 'Unilevel Commission',
        'binary' => 'Binary Commission',
        'flush' => 'การล้าง PV',
        'placement' => 'การจัดวางสมาชิก',
        'rollup' => 'Roll-up / Compression',
        'retention' => 'การรักษายอด',
        'commission' => 'ป้องกัน Overpay',
        default => ucfirst($group)
    };
}

function getGroupDescription($group) {
    return match($group) {
        'general' => 'การตั้งค่าพื้นฐานของระบบ',
        'pv' => 'อัตราแลกเปลี่ยนและการคำนวณ PV',
        'unilevel' => 'เปอร์เซ็นต์คอมมิชชันแต่ละชั้น',
        'binary' => 'การจับคู่และคอมมิชชันแบบ Binary',
        'flush' => 'การล้างหรือยกยอด PV',
        'placement' => 'กลยุทธ์การจัดวางสมาชิกใหม่',
        'rollup' => 'การยกคอมมิชชันข้ามคนไม่ active',
        'retention' => 'เงื่อนไขการรักษายอดขาย',
        'commission' => 'การป้องกันการจ่ายเกิน',
        default => ''
    };
}
@endphp

<style>
/* Premium Animations */
@keyframes gradient {
    0%, 100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}

.animate-gradient {
    background-size: 200% 200%;
    animation: gradient 3s ease infinite;
}

/* Custom Scrollbar */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #9333ea, #ec4899);
    border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, #7e22ce, #db2777);
}

/* Smooth transitions for all interactive elements */
input, select, textarea, button {
    transition: all 0.2s ease;
}

/* Pulse animation for important elements */
@keyframes pulse-subtle {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.8; }
}

.animate-pulse-subtle {
    animation: pulse-subtle 2s ease-in-out infinite;
}

/* Modal Styles */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(4px);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-overlay.active {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 24px;
    max-width: 900px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Placement Animation Styles */
.placement-demo {
    position: relative;
    width: 100%;
    height: 300px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 16px;
    overflow: hidden;
}

.placement-node {
    position: absolute;
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 14px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transition: all 0.3s ease;
}

.placement-node.sponsor {
    background: linear-gradient(135deg, #9333ea, #ec4899);
    color: white;
}

.placement-node.member {
    background: linear-gradient(135deg, #60a5fa, #3b82f6);
    color: white;
}

.placement-node.new {
    background: linear-gradient(135deg, #34d399, #10b981);
    color: white;
    animation: nodeAppear 0.5s ease-out;
}

.placement-node.inactive {
    background: #e5e7eb;
    color: #9ca3af;
}

@keyframes nodeAppear {
    from {
        transform: scale(0) rotate(-180deg);
        opacity: 0;
    }
    to {
        transform: scale(1) rotate(0deg);
        opacity: 1;
    }
}

.placement-line {
    position: absolute;
    background: linear-gradient(90deg, #9333ea, #ec4899);
    height: 3px;
    transform-origin: left center;
    opacity: 0.6;
    animation: lineGrow 0.5s ease-out;
}

@keyframes lineGrow {
    from {
        transform: scaleX(0);
    }
    to {
        transform: scaleX(1);
    }
}

/* Rollup Animation */
.rollup-demo {
    position: relative;
    width: 100%;
    height: 350px;
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-radius: 16px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.rollup-arrow {
    position: absolute;
    width: 40px;
    height: 40px;
    animation: rollupFloat 2s ease-in-out infinite;
}

@keyframes rollupFloat {
    0%, 100% {
        transform: translateY(0) scale(1);
        opacity: 1;
    }
    50% {
        transform: translateY(-60px) scale(1.2);
        opacity: 0.6;
    }
}

.commission-flow {
    stroke-dasharray: 10;
    stroke-dashoffset: 0;
    animation: flowAnimation 2s linear infinite;
}

@keyframes flowAnimation {
    to {
        stroke-dashoffset: 100;
    }
}

/* Tree Structure Animations */
.tree-node {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    font-weight: bold;
    color: white;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    animation: nodePulse 2s ease-in-out infinite;
}

@keyframes nodePulse {
    0%, 100% {
        transform: scale(1);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    50% {
        transform: scale(1.05);
        box-shadow: 0 8px 20px rgba(147, 51, 234, 0.4);
    }
}

.tree-node.level-1 { background: linear-gradient(135deg, #9333ea, #a855f7); }
.tree-node.level-2 { background: linear-gradient(135deg, #ec4899, #f472b6); }
.tree-node.level-3 { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
.tree-node.level-4 { background: linear-gradient(135deg, #10b981, #34d399); }
.tree-node.level-5 { background: linear-gradient(135deg, #3b82f6, #60a5fa); }

/* Help Icon */
.help-icon {
    cursor: pointer;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: linear-gradient(135deg, #9333ea, #ec4899);
    color: white;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: bold;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(147, 51, 234, 0.3);
}

.help-icon:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(147, 51, 234, 0.5);
}

/* Line OA Style Cards */
.setting-card {
    background: white;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 12px;
    border: 1px solid #e5e7eb;
    transition: all 0.2s ease;
}

.setting-card:hover {
    border-color: #9333ea;
    box-shadow: 0 4px 12px rgba(147, 51, 234, 0.1);
}

.setting-label {
    font-size: 14px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.setting-description {
    font-size: 13px;
    color: #6b7280;
    margin-bottom: 12px;
    line-height: 1.5;
}

.visual-help-trigger {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    background: linear-gradient(135deg, #f3e8ff, #fce7f3);
    border: 1px solid #e9d5ff;
    border-radius: 20px;
    font-size: 12px;
    color: #7c3aed;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-top: 8px;
}

.visual-help-trigger:hover {
    background: linear-gradient(135deg, #e9d5ff, #fbcfe8);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(124, 58, 237, 0.2);
}
</style>

@endsection
