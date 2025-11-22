{{--
    หน้าจัดการ LINE Signup Flow - V3 Design System

    หน้านี้ใช้สำหรับ:
    - แสดงรายการ Signup Flow Steps ทั้งหมด
    - จัดเรียงลำดับขั้นตอน (Drag & Drop)
    - สร้าง/แก้ไข/ลบ Flow Steps
    - เปิด/ปิดใช้งาน Steps

    💡 V3 Features:
    - LINE Green Theme (#06C755, #00B900, #00E600)
    - Glassmorphism effects
    - Alpine.js components
    - Dark mode support
    - Animated statistics counters
    - Search & filter flows

    📚 คู่มือ: LINE_CHATBOT_MLM_SIGNUP.md
--}}

@extends('layouts.admin-v3')
@section('title', 'จัดการ Signup Flow')

@vite(['resources/css/app.css', 'resources/js/app.js'])

@section('content')
<div class="container-fluid py-8 px-4 md:px-6" x-data="signupFlowManager()">

    {{-- Page Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h2 class="text-4xl font-bold bg-gradient-to-r from-[#00B900] to-[#00E600] bg-clip-text text-transparent flex items-center gap-3">
                <div class="w-14 h-14 bg-gradient-to-br from-[#00B900] to-[#00E600] rounded-2xl flex items-center justify-center shadow-lg shadow-[#06C755]/30">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                LINE Signup Flow
            </h2>
            <p class="text-slate-600 dark:text-slate-400 mt-2 text-lg">
                จัดการขั้นตอนการสมัครสมาชิกผ่าน LINE Chatbot
            </p>
        </div>

        <div class="flex items-center gap-3">
            {{-- Back to LINE OA --}}
            <a href="{{ route('admin.line-oa.index') }}"
               class="px-6 py-3 min-h-[44px] glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 border border-white/20 dark:border-slate-700/50 text-slate-700 dark:text-slate-300 rounded-xl font-semibold hover:border-[#06C755] transform hover:scale-105 transition-all shadow-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>กลับ</span>
            </a>

            {{-- Create New Flow --}}
            <a href="{{ route('admin.signup-flow.create') }}"
               class="px-6 py-3 min-h-[44px] bg-gradient-to-r from-[#00B900] to-[#00E600] hover:from-[#00A000] hover:to-[#00D000] text-white rounded-xl font-semibold transform hover:scale-105 transition-all shadow-lg shadow-[#06C755]/30 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                สร้าง Flow ใหม่
            </a>
        </div>
    </div>

    {{-- Summary Stats with V3 Design & Animated Counters --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {{-- Total Flows - LINE Green Theme --}}
        <div class="glass-fusion backdrop-blur-xl bg-gradient-to-br from-[#00B900]/10 to-[#00E600]/10 dark:from-[#00B900]/20 dark:to-[#00E600]/20 p-6 rounded-2xl border border-[#06C755]/30 transform hover:scale-105 transition-all">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-[#00B900] dark:text-[#00E600]">Signup Flows ทั้งหมด</h3>
                <div class="w-12 h-12 bg-gradient-to-br from-[#00B900] to-[#00E600] rounded-xl flex items-center justify-center shadow-lg shadow-[#06C755]/30">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
            <div x-data="{ count: 0 }" x-init="animateCount(0, {{ $flows->count() }}, 1500, val => count = val)">
                <h2 class="text-4xl font-bold text-slate-900 dark:text-white mb-1" x-text="Math.floor(count)">0</h2>
            </div>
            <p class="text-xs text-[#00B900] dark:text-[#00E600]">ทั้งหมดในระบบ</p>
        </div>

        {{-- Active Flows --}}
        <div class="glass-fusion backdrop-blur-xl bg-gradient-to-br from-blue-500/10 to-blue-600/10 dark:from-blue-500/20 dark:to-blue-600/20 p-6 rounded-2xl border border-blue-500/30 dark:border-blue-400/30 transform hover:scale-105 transition-all">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-blue-600 dark:text-blue-400">Flows ที่เปิดใช้งาน</h3>
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/30">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div x-data="{ count: 0 }" x-init="animateCount(0, {{ $flows->where('is_active', true)->count() }}, 1500, val => count = val)">
                <h2 class="text-4xl font-bold text-slate-900 dark:text-white mb-1" x-text="Math.floor(count)">0</h2>
            </div>
            <p class="text-xs text-blue-600 dark:text-blue-400">พร้อมใช้งาน</p>
        </div>

        {{-- AI Enabled --}}
        <div class="glass-fusion backdrop-blur-xl bg-gradient-to-br from-purple-500/10 to-purple-600/10 dark:from-purple-500/20 dark:to-purple-600/20 p-6 rounded-2xl border border-purple-500/30 dark:border-purple-400/30 transform hover:scale-105 transition-all">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-purple-600 dark:text-purple-400">เปิดใช้ AI</h3>
                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg shadow-purple-500/30">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
            </div>
            <div x-data="{ count: 0 }" x-init="animateCount(0, {{ $flows->where('require_ai', true)->count() }}, 1500, val => count = val)">
                <h2 class="text-4xl font-bold text-slate-900 dark:text-white mb-1" x-text="Math.floor(count)">0</h2>
            </div>
            <p class="text-xs text-purple-600 dark:text-purple-400">AI Processing</p>
        </div>

        {{-- Skippable --}}
        <div class="glass-fusion backdrop-blur-xl bg-gradient-to-br from-amber-500/10 to-amber-600/10 dark:from-amber-500/20 dark:to-amber-600/20 p-6 rounded-2xl border border-amber-500/30 dark:border-amber-400/30 transform hover:scale-105 transition-all">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-amber-600 dark:text-amber-400">สามารถข้ามได้</h3>
                <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center shadow-lg shadow-amber-500/30">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div x-data="{ count: 0 }" x-init="animateCount(0, {{ $flows->where('is_skippable', true)->count() }}, 1500, val => count = val)">
                <h2 class="text-4xl font-bold text-slate-900 dark:text-white mb-1" x-text="Math.floor(count)">0</h2>
            </div>
            <p class="text-xs text-amber-600 dark:text-amber-400">Optional Steps</p>
        </div>
    </div>

    {{-- Search & Filter Bar --}}
    <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-2xl p-4 mb-6 border border-white/20 dark:border-slate-700/50">
        <div class="flex flex-col md:flex-row gap-4">
            {{-- Search Input --}}
            <div class="flex-1">
                <div class="relative">
                    <svg class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text"
                           x-model="searchQuery"
                           @input="filterFlows()"
                           placeholder="ค้นหา Flow..."
                           class="w-full pl-12 pr-4 py-3 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-[#06C755] focus:border-transparent text-slate-900 dark:text-white">
                </div>
            </div>

            {{-- Status Filter --}}
            <select x-model="statusFilter"
                    @change="filterFlows()"
                    class="px-4 py-3 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-[#06C755] focus:border-transparent text-slate-900 dark:text-white">
                <option value="all">ทั้งหมด</option>
                <option value="active">เปิดใช้งาน</option>
                <option value="inactive">ปิดใช้งาน</option>
            </select>
        </div>
    </div>

    {{-- Info Box - V3 Style --}}
    <div class="glass-fusion backdrop-blur-xl bg-gradient-to-br from-blue-500/10 to-blue-600/10 dark:from-blue-500/20 dark:to-blue-600/20 border-l-4 border-blue-500 p-6 rounded-2xl mb-8">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg shadow-blue-500/30">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h4 class="font-bold text-blue-900 dark:text-blue-100 mb-3 text-lg">
                    💡 วิธีใช้งาน Signup Flow
                </h4>
                <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-2">
                    <li class="flex items-start gap-2">
                        <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        <span><strong>ลาก</strong> การ์ดเพื่อเปลี่ยนลำดับขั้นตอน (Drag & Drop)</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        <span><strong>Input Type</strong> จะกำหนดประเภทข้อมูลที่รับจากผู้ใช้</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        <span><strong>Next Step</strong> จะเชื่อมโยงไปยังขั้นตอนถัดไป</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        <span>เปิด <strong>Require AI</strong> หากต้องการให้ AI ช่วยตอบคำถาม</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Signup Flow List - V3 Design --}}
    <div id="flowList" class="space-y-6">
        @forelse($flows as $flow)
        <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-2xl p-6 border-l-4 {{ $flow->is_active ? 'border-[#06C755]' : 'border-slate-400 dark:border-slate-600 opacity-75' }} hover:border-[#06C755] transform hover:scale-[1.02] transition-all shadow-lg hover:shadow-xl border-r border-t border-b border-white/20 dark:border-slate-700/50 cursor-move"
             data-id="{{ $flow->id }}"
             draggable="true">

            <div class="flex items-start gap-6">
                {{-- Drag Handle --}}
                <div class="flex-shrink-0 text-slate-400 hover:text-[#06C755] transition-colors cursor-grab active:cursor-grabbing">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                    </svg>
                </div>

                {{-- Step Order Badge --}}
                <div class="w-12 h-12 rounded-full flex items-center justify-center bg-gradient-to-br from-[#00B900] to-[#00E600] text-white font-bold text-xl flex-shrink-0 shadow-lg shadow-[#06C755]/30">
                    {{ $flow->step_order }}
                </div>

                {{-- Flow Content --}}
                <div class="flex-1 min-w-0">
                    {{-- Header --}}
                    <div class="flex flex-wrap items-start justify-between gap-4 mb-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 mb-2 flex-wrap">
                                <h3 class="text-xl font-bold text-slate-900 dark:text-white">
                                    {{ $flow->name }}
                                </h3>
                                <span class="px-3 py-1 rounded-full text-xs font-bold flex items-center gap-1.5 {{ $flow->is_active ? 'bg-gradient-to-r from-[#00B900] to-[#00E600] text-white shadow-lg shadow-[#06C755]/30' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-400' }}">
                                    <span class="w-2 h-2 rounded-full {{ $flow->is_active ? 'bg-white animate-pulse' : 'bg-slate-500' }}"></span>
                                    {{ $flow->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <code class="inline-block bg-slate-100 dark:bg-slate-700 px-3 py-1 rounded-lg text-xs font-mono text-slate-700 dark:text-slate-300">
                                {{ $flow->step_key }}
                            </code>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.signup-flow.edit', $flow->id) }}"
                               class="px-4 py-2 min-h-[44px] bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-xl font-semibold transform hover:scale-105 transition-all shadow-lg shadow-blue-500/30 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                <span class="hidden md:inline">แก้ไข</span>
                            </a>

                            <button @click="deleteFlow({{ $flow->id }})"
                                    class="px-4 py-2 min-h-[44px] bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white rounded-xl font-semibold transform hover:scale-105 transition-all shadow-lg shadow-red-500/30 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Message Preview --}}
                    <div class="glass-fusion bg-gradient-to-br from-slate-50/80 to-slate-100/80 dark:from-slate-700/50 dark:to-slate-800/50 rounded-xl p-4 mb-4 border border-slate-200/50 dark:border-slate-600/50">
                        <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed">
                            {{ Str::limit($flow->message_text, 150) }}
                        </p>
                    </div>

                    {{-- Flow Metadata --}}
                    <div class="flex flex-wrap items-center gap-3 text-sm">
                        {{-- Input Type --}}
                        <div class="flex items-center gap-2">
                            <span class="text-slate-500 dark:text-slate-400 font-medium">Input:</span>
                            @php
                                $inputConfig = [
                                    'text' => ['label' => 'Text', 'gradient' => 'from-blue-500 to-blue-600', 'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
                                    'phone' => ['label' => 'Phone', 'gradient' => 'from-green-500 to-green-600', 'icon' => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z'],
                                    'email' => ['label' => 'Email', 'gradient' => 'from-amber-500 to-amber-600', 'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                                    'name' => ['label' => 'Name', 'gradient' => 'from-purple-500 to-purple-600', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                                    'confirm' => ['label' => 'Confirm', 'gradient' => 'from-cyan-500 to-cyan-600', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                                    'choice' => ['label' => 'Choice', 'gradient' => 'from-pink-500 to-pink-600', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                                    'none' => ['label' => 'None', 'gradient' => 'from-slate-400 to-slate-500', 'icon' => 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636'],
                                ];
                                $config = $inputConfig[$flow->input_type] ?? $inputConfig['none'];
                            @endphp
                            <span class="px-3 py-1.5 bg-gradient-to-r {{ $config['gradient'] }} text-white rounded-lg font-semibold flex items-center gap-2 shadow-md">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $config['icon'] }}"/>
                                </svg>
                                {{ $config['label'] }}
                            </span>
                        </div>

                        {{-- Next Step --}}
                        @if($flow->next_step_key)
                        <div class="flex items-center gap-2">
                            <span class="text-slate-500 dark:text-slate-400 font-medium">Next:</span>
                            <span class="px-3 py-1.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-lg text-xs font-semibold flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                                {{ $flow->next_step_key }}
                            </span>
                        </div>
                        @endif

                        {{-- Skippable --}}
                        @if($flow->is_skippable)
                        <span class="px-3 py-1.5 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-lg text-xs font-semibold flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Skippable
                        </span>
                        @endif

                        {{-- AI Required --}}
                        @if($flow->require_ai)
                        <span class="px-3 py-1.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-lg text-xs font-semibold flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                            AI Enabled
                        </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @empty
        {{-- Empty State - V3 Design --}}
        <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-2xl p-16 text-center border border-white/20 dark:border-slate-700/50 shadow-lg">
            <div class="w-24 h-24 bg-gradient-to-br from-slate-200 to-slate-300 dark:from-slate-700 dark:to-slate-800 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-12 h-12 text-slate-400 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-slate-700 dark:text-slate-300 mb-2">
                ยังไม่มี Signup Flow
            </h3>
            <p class="text-slate-500 dark:text-slate-400 mb-6 text-lg">
                เริ่มสร้างขั้นตอนการสมัครสมาชิกแรกของคุณ
            </p>
            <a href="{{ route('admin.signup-flow.create') }}"
               class="inline-flex items-center gap-2 px-8 py-4 min-h-[44px] bg-gradient-to-r from-[#00B900] to-[#00E600] hover:from-[#00A000] hover:to-[#00D000] text-white rounded-xl font-semibold transform hover:scale-105 transition-all shadow-lg shadow-[#06C755]/30">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                สร้าง Flow แรก
            </a>
        </div>
        @endforelse
    </div>

</div>
@endsection

@push('scripts')
<script>
/**
 * Alpine.js Component สำหรับ Signup Flow Manager - V3
 *
 * Features:
 * - Animated counters
 * - Search & Filter flows
 * - Drag & Drop Reordering
 * - Delete Flow with Confirmation
 * - AJAX Operations
 */

/**
 * ฟังก์ชัน Animate Counter สำหรับ Statistics Cards
 *
 * @param {number} start - ค่าเริ่มต้น
 * @param {number} end - ค่าสิ้นสุด
 * @param {number} duration - ระยะเวลา animation (ms)
 * @param {function} callback - Callback function เมื่อค่าเปลี่ยน
 */
function animateCount(start, end, duration, callback) {
    let startTime = null;

    function animate(currentTime) {
        if (startTime === null) startTime = currentTime;
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);

        // Easing function (easeOutQuart)
        const easing = 1 - Math.pow(1 - progress, 4);
        const current = start + (end - start) * easing;

        callback(current);

        if (progress < 1) {
            requestAnimationFrame(animate);
        }
    }

    requestAnimationFrame(animate);
}

function signupFlowManager() {
    return {
        draggedElement: null,
        searchQuery: '',
        statusFilter: 'all',
        flows: [],

        /**
         * Initialize Component
         */
        init() {
            this.initDragAndDrop();
            this.cacheFlows();
        },

        /**
         * Cache flows สำหรับ filter
         */
        cacheFlows() {
            const flowList = document.getElementById('flowList');
            const flowCards = flowList.querySelectorAll('[data-id]');
            this.flows = Array.from(flowCards);
        },

        /**
         * Filter flows ตาม search query และ status
         */
        filterFlows() {
            this.flows.forEach(flow => {
                const name = flow.querySelector('h3').textContent.toLowerCase();
                const stepKey = flow.querySelector('code').textContent.toLowerCase();
                const message = flow.querySelector('.glass-fusion p').textContent.toLowerCase();
                const searchMatch = name.includes(this.searchQuery.toLowerCase()) ||
                                  stepKey.includes(this.searchQuery.toLowerCase()) ||
                                  message.includes(this.searchQuery.toLowerCase());

                const isActive = flow.classList.contains('border-[#06C755]');
                const statusMatch = this.statusFilter === 'all' ||
                                  (this.statusFilter === 'active' && isActive) ||
                                  (this.statusFilter === 'inactive' && !isActive);

                if (searchMatch && statusMatch) {
                    flow.style.display = '';
                } else {
                    flow.style.display = 'none';
                }
            });
        },

        /**
         * Initialize Drag and Drop functionality
         *
         * ทำให้สามารถลากการ์ดเพื่อเปลี่ยนลำดับได้
         */
        initDragAndDrop() {
            const flowList = document.getElementById('flowList');
            if (!flowList) return;

            const cards = flowList.querySelectorAll('[data-id][draggable="true"]');

            cards.forEach(card => {
                // เริ่มลาก
                card.addEventListener('dragstart', (e) => {
                    this.draggedElement = card;
                    card.style.opacity = '0.5';
                    card.style.transform = 'rotate(2deg)';
                });

                // สิ้นสุดการลาก
                card.addEventListener('dragend', (e) => {
                    card.style.opacity = '';
                    card.style.transform = '';
                });

                // ลากผ่าน
                card.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    const afterElement = this.getDragAfterElement(flowList, e.clientY);
                    if (afterElement == null) {
                        flowList.appendChild(this.draggedElement);
                    } else {
                        flowList.insertBefore(this.draggedElement, afterElement);
                    }
                });
            });

            // เมื่อวางการ์ด ให้บันทึกลำดับใหม่
            flowList.addEventListener('drop', async (e) => {
                e.preventDefault();
                await this.saveOrder();
            });
        },

        /**
         * หา element ที่ควรจะวางการ์ดไว้ข้างหลัง
         */
        getDragAfterElement(container, y) {
            const draggableElements = [...container.querySelectorAll('[data-id][draggable="true"]')].filter(
                el => el !== this.draggedElement
            );

            return draggableElements.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;

                if (offset < 0 && offset > closest.offset) {
                    return { offset: offset, element: child };
                } else {
                    return closest;
                }
            }, { offset: Number.NEGATIVE_INFINITY }).element;
        },

        /**
         * บันทึกลำดับใหม่ไปยัง server
         */
        async saveOrder() {
            const cards = document.querySelectorAll('[data-id][draggable="true"]');
            const order = Array.from(cards).map(card => card.dataset.id);

            try {
                const response = await fetch('{{ route('admin.signup-flow.reorder') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ order })
                });

                const data = await response.json();

                if (data.success) {
                    this.showToast('บันทึกลำดับสำเร็จ!', 'success');

                    // อัปเดตหมายเลขลำดับ
                    cards.forEach((card, index) => {
                        const badge = card.querySelector('.w-12.h-12.rounded-full');
                        if (badge) {
                            badge.textContent = index + 1;
                        }
                    });
                } else {
                    this.showToast('เกิดข้อผิดพลาด', 'error');
                }
            } catch (error) {
                console.error('Error saving order:', error);
                this.showToast('เกิดข้อผิดพลาด: ' + error.message, 'error');
            }
        },

        /**
         * ลบ Flow (พร้อม Confirmation)
         *
         * @param {number} flowId - ID ของ flow ที่ต้องการลบ
         */
        async deleteFlow(flowId) {
            if (!confirm('ต้องการลบ Signup Flow นี้? การกระทำนี้ไม่สามารถย้อนกลับได้')) {
                return;
            }

            try {
                const response = await fetch(`/admin/signup-flow/${flowId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                if (response.ok) {
                    this.showToast('ลบ Flow สำเร็จ!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    const data = await response.json();
                    this.showToast(data.message || 'เกิดข้อผิดพลาด', 'error');
                }
            } catch (error) {
                console.error('Error deleting flow:', error);
                this.showToast('เกิดข้อผิดพลาด: ' + error.message, 'error');
            }
        },

        /**
         * แสดง Toast Notification
         *
         * @param {string} message - ข้อความที่จะแสดง
         * @param {string} type - ประเภท (success, error, info, warning)
         */
        showToast(message, type = 'info') {
            // TODO: ใช้ toast library เช่น toastr, sweetalert2
            alert(message);
        }
    }
}
</script>
@endpush
