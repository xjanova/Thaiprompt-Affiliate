@extends('layouts.admin-v3')

@section('title', 'สร้าง Signup Flow ใหม่')

@vite(['resources/css/app.css', 'resources/js/app.js'])

@section('content')
<div class="space-y-8 py-4">
    {{-- Header Section - V3 Design --}}
    <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-2xl shadow-lg p-6 border border-white/20 dark:border-slate-700/50">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-gradient-to-br from-[#00B900] to-[#00E600] rounded-2xl flex items-center justify-center shadow-lg shadow-[#06C755]/30">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-[#00B900] to-[#00E600] bg-clip-text text-transparent">สร้าง Signup Flow ใหม่</h1>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">เพิ่มขั้นตอนการสมัครสมาชิกผ่าน LINE ใหม่</p>
                </div>
            </div>
            <a href="{{ route('admin.line-signup-flow.index') }}"
               class="px-6 py-3 min-h-[44px] glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 border border-white/20 dark:border-slate-700/50 text-slate-700 dark:text-slate-300 rounded-xl font-semibold hover:border-[#06C755] transform hover:scale-105 transition-all shadow-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                กลับ
            </a>
        </div>
    </div>

    {{-- Form Section --}}
    <form method="POST" action="{{ route('admin.line-signup-flow.store') }}" x-data="signupFlowForm()">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Main Form --}}
            <div class="lg:col-span-2 space-y-8">
                {{-- Basic Information - V3 Design --}}
                <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-2xl shadow-lg p-6 border border-white/20 dark:border-slate-700/50">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/30">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        ข้อมูลพื้นฐาน
                    </h2>

                    <div class="space-y-5">
                        {{-- Name --}}
                        <div>
                            <label for="name" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                ชื่อขั้นตอน <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   name="name"
                                   id="name"
                                   value="{{ old('name') }}"
                                   required
                                   class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-[#06C755] focus:border-[#06C755] bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-all"
                                   placeholder="เช่น: ขอชื่อ-นามสกุล">
                            @error('name')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- Step Key --}}
                        <div>
                            <label for="step_key" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                Step Key <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   name="step_key"
                                   id="step_key"
                                   value="{{ old('step_key') }}"
                                   required
                                   pattern="[a-z0-9_]+"
                                   class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-[#06C755] focus:border-[#06C755] bg-white dark:bg-slate-700 text-slate-900 dark:text-white font-mono transition-all"
                                   placeholder="เช่น: ask_name">
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                ใช้ตัวพิมพ์เล็ก ตัวเลข และ underscore เท่านั้น
                            </p>
                            @error('step_key')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- Step Order --}}
                        <div>
                            <label for="step_order" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                ลำดับขั้นตอน <span class="text-red-500">*</span>
                            </label>
                            <input type="number"
                                   name="step_order"
                                   id="step_order"
                                   value="{{ old('step_order', 1) }}"
                                   required
                                   min="1"
                                   class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-[#06C755] focus:border-[#06C755] bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-all"
                                   placeholder="1">
                            @error('step_order')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Message Configuration - V3 Design --}}
                <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-2xl shadow-lg p-6 border border-white/20 dark:border-slate-700/50">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-[#00B900] to-[#00E600] rounded-xl flex items-center justify-center shadow-lg shadow-[#06C755]/30">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                            </svg>
                        </div>
                        ข้อความและการตอบกลับ
                    </h2>

                    <div class="space-y-4">
                        {{-- Message Text --}}
                        <div>
                            <label for="message_text" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ข้อความที่จะส่งให้ผู้ใช้ <span class="text-red-500">*</span>
                            </label>
                            <textarea name="message_text"
                                      id="message_text"
                                      rows="4"
                                      required
                                      x-model="messageText"
                                      class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                      placeholder="ใส่ข้อความที่จะส่งไปให้ผู้ใช้...">{{ old('message_text') }}</textarea>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                จำนวนตัวอักษร: <span x-text="messageText.length"></span> / 5000
                            </p>
                            @error('message_text')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Input Type --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ประเภทการรับข้อมูล <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                @php
                                    $inputTypes = [
                                        'text' => ['label' => 'ข้อความ', 'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
                                        'phone' => ['label' => 'เบอร์โทร', 'icon' => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z'],
                                        'email' => ['label' => 'อีเมล', 'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                                        'name' => ['label' => 'ชื่อ', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                                        'confirm' => ['label' => 'ยืนยัน', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                                        'choice' => ['label' => 'เลือก', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
                                        'none' => ['label' => 'ไม่รับ', 'icon' => 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636'],
                                    ];
                                @endphp

                                @foreach($inputTypes as $type => $info)
                                    <label class="relative cursor-pointer">
                                        <input type="radio"
                                               name="input_type"
                                               value="{{ $type }}"
                                               x-model="inputType"
                                               {{ old('input_type') === $type ? 'checked' : '' }}
                                               class="peer sr-only">
                                        <div class="flex flex-col items-center p-4 border-2 border-gray-300 dark:border-gray-600 rounded-lg peer-checked:border-indigo-600 dark:peer-checked:border-indigo-400 peer-checked:bg-indigo-50 dark:peer-checked:bg-indigo-900/20 hover:border-indigo-400 dark:hover:border-indigo-500 transition">
                                            <svg class="w-6 h-6 text-gray-600 dark:text-gray-400 peer-checked:text-indigo-600 dark:peer-checked:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $info['icon'] }}" />
                                            </svg>
                                            <span class="mt-2 text-sm font-medium text-gray-700 dark:text-gray-300">{{ $info['label'] }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                            @error('input_type')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Next Step --}}
                        <div>
                            <label for="next_step_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ขั้นตอนถัดไป
                            </label>
                            <select name="next_step_key"
                                    id="next_step_key"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                                <option value="">-- สิ้นสุดการสมัคร --</option>
                                @foreach($availableSteps as $step)
                                    <option value="{{ $step->step_key }}" {{ old('next_step_key') === $step->step_key ? 'selected' : '' }}>
                                        {{ $step->name }} ({{ $step->step_key }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">เว้นว่างไว้หากนี่คือขั้นตอนสุดท้าย</p>
                            @error('next_step_key')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Settings - V3 Design --}}
                <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-2xl shadow-lg p-6 border border-white/20 dark:border-slate-700/50">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg shadow-purple-500/30">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        การตั้งค่า
                    </h2>

                    <div class="space-y-4">
                        {{-- Active Toggle --}}
                        <div class="flex items-center justify-between p-5 glass-fusion bg-gradient-to-br from-green-50/80 to-emerald-50/80 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl border border-green-200/50 dark:border-green-700/50">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center shadow-md">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <label for="is_active" class="text-sm font-bold text-slate-900 dark:text-white">เปิดใช้งาน</label>
                                </div>
                                <p class="mt-2 text-xs text-slate-600 dark:text-slate-400 ml-10">ขั้นตอนนี้จะทำงานเมื่อเปิดใช้งาน</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox"
                                       name="is_active"
                                       id="is_active"
                                       value="1"
                                       {{ old('is_active', true) ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-14 h-7 bg-slate-300 dark:bg-slate-600 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-[#00B900] peer-checked:to-[#00E600] shadow-lg"></div>
                            </label>
                        </div>

                        {{-- Skippable Toggle --}}
                        <div class="flex items-center justify-between p-5 glass-fusion bg-gradient-to-br from-amber-50/80 to-orange-50/80 dark:from-amber-900/20 dark:to-orange-900/20 rounded-xl border border-amber-200/50 dark:border-amber-700/50">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 bg-gradient-to-br from-amber-500 to-amber-600 rounded-lg flex items-center justify-center shadow-md">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <label for="is_skippable" class="text-sm font-bold text-slate-900 dark:text-white">ข้ามได้</label>
                                </div>
                                <p class="mt-2 text-xs text-slate-600 dark:text-slate-400 ml-10">อนุญาตให้ผู้ใช้ข้ามขั้นตอนนี้ได้</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox"
                                       name="is_skippable"
                                       id="is_skippable"
                                       value="1"
                                       {{ old('is_skippable', false) ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-14 h-7 bg-slate-300 dark:bg-slate-600 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-amber-500 peer-checked:to-amber-600 shadow-lg"></div>
                            </label>
                        </div>

                        {{-- AI Toggle --}}
                        <div class="flex items-center justify-between p-5 glass-fusion bg-gradient-to-br from-purple-50/80 to-violet-50/80 dark:from-purple-900/20 dark:to-violet-900/20 rounded-xl border border-purple-200/50 dark:border-purple-700/50">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg flex items-center justify-center shadow-md">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                        </svg>
                                    </div>
                                    <label for="require_ai" class="text-sm font-bold text-slate-900 dark:text-white">ใช้ AI</label>
                                </div>
                                <p class="mt-2 text-xs text-slate-600 dark:text-slate-400 ml-10">ประมวลผลคำตอบด้วย AI</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox"
                                       name="require_ai"
                                       id="require_ai"
                                       value="1"
                                       {{ old('require_ai', false) ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-14 h-7 bg-slate-300 dark:bg-slate-600 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-purple-500 peer-checked:to-purple-600 shadow-lg"></div>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons - V3 Design --}}
                <div class="flex items-center justify-end gap-4 pt-4">
                    <a href="{{ route('admin.line-signup-flow.index') }}"
                       class="px-8 py-4 min-h-[44px] glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 border border-white/20 dark:border-slate-700/50 text-slate-700 dark:text-slate-300 rounded-xl font-semibold hover:border-slate-400 transform hover:scale-105 transition-all shadow-lg flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        ยกเลิก
                    </a>
                    <button type="submit"
                            class="px-8 py-4 min-h-[44px] bg-gradient-to-r from-[#00B900] to-[#00E600] hover:from-[#00A000] hover:to-[#00D000] text-white rounded-xl font-semibold transform hover:scale-105 transition-all shadow-lg shadow-[#06C755]/30 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        บันทึก Flow
                    </button>
                </div>
            </div>

            {{-- Preview Panel --}}
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 sticky top-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        ตัวอย่าง LINE Chat
                    </h2>

                    {{-- LINE Chat Preview --}}
                    <div class="bg-[#06C755] rounded-lg p-4">
                        {{-- LINE Header --}}
                        <div class="bg-white rounded-t-lg px-4 py-3 flex items-center border-b">
                            <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <div class="font-semibold text-gray-900">Bot Signup</div>
                            </div>
                        </div>

                        {{-- Chat Messages --}}
                        <div class="bg-gray-100 rounded-b-lg p-4 min-h-[300px]">
                            {{-- Bot Message --}}
                            <div class="flex items-start mb-4">
                                <div class="bg-white rounded-2xl rounded-tl-none px-4 py-3 shadow max-w-[85%]">
                                    <p class="text-sm text-gray-800 whitespace-pre-wrap" x-text="messageText || 'ข้อความจะแสดงที่นี่...'"></p>
                                </div>
                            </div>

                            {{-- Input Type Indicator --}}
                            <div class="flex items-start justify-end">
                                <div class="bg-blue-500 rounded-2xl rounded-tr-none px-4 py-3 shadow max-w-[85%]">
                                    <p class="text-sm text-white">
                                        <template x-if="inputType === 'text'">
                                            <span>ข้อความตัวอย่าง...</span>
                                        </template>
                                        <template x-if="inputType === 'phone'">
                                            <span>0812345678</span>
                                        </template>
                                        <template x-if="inputType === 'email'">
                                            <span>example@email.com</span>
                                        </template>
                                        <template x-if="inputType === 'name'">
                                            <span>ชื่อ-นามสกุล</span>
                                        </template>
                                        <template x-if="inputType === 'confirm'">
                                            <span>✓ ยืนยัน</span>
                                        </template>
                                        <template x-if="inputType === 'choice'">
                                            <span>เลือกตัวเลือก</span>
                                        </template>
                                        <template x-if="inputType === 'none'">
                                            <span class="italic opacity-75">ไม่รับข้อมูล</span>
                                        </template>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Preview Info --}}
                    <div class="mt-4 space-y-2 text-sm">
                        <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700/50 rounded">
                            <span class="text-gray-600 dark:text-gray-400">ประเภทการรับ:</span>
                            <span class="font-medium text-gray-900 dark:text-gray-100" x-text="inputType || 'text'"></span>
                        </div>
                        <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700/50 rounded">
                            <span class="text-gray-600 dark:text-gray-400">ความยาวข้อความ:</span>
                            <span class="font-medium text-gray-900 dark:text-gray-100" x-text="messageText.length + ' ตัวอักษร'"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function signupFlowForm() {
    return {
        messageText: '{{ old('message_text', '') }}',
        inputType: '{{ old('input_type', 'text') }}',

        init() {
            // Initialize with old values if available
            this.messageText = document.getElementById('message_text').value || '';

            // Find checked radio button
            const checkedRadio = document.querySelector('input[name="input_type"]:checked');
            if (checkedRadio) {
                this.inputType = checkedRadio.value;
            }
        }
    }
}
</script>
@endsection
