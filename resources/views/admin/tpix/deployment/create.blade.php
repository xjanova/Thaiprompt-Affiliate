@extends('layouts.admin')

@section('title', 'สร้าง TPIX Configuration ใหม่')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Back Button --}}
        <div class="mb-6">
            <a href="{{ route('admin.tpix.deployment.index') }}"
               class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                กลับไปรายการ
            </a>
        </div>

        {{-- Header --}}
        <div class="text-center mb-12">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full mb-6 shadow-lg">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
            </div>
            <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent mb-4">
                สร้าง TPIX Configuration ใหม่
            </h1>
            <p class="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                เริ่มต้นสร้าง Native Coin บน TPIX Blockchain ด้วย Wizard แบบ Step-by-Step
            </p>
        </div>

        {{-- Card --}}
        <div class="relative group">
            {{-- Glow Effect --}}
            <div class="absolute -inset-1 bg-gradient-to-r from-blue-600 to-purple-600 rounded-3xl blur-lg opacity-25 group-hover:opacity-40 transition duration-300"></div>

            {{-- Card Content --}}
            <div class="relative bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl rounded-3xl shadow-2xl p-8 md:p-12 border border-white/20 dark:border-gray-700/50">
                {{-- Info Box --}}
                <div class="mb-8 p-6 bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 rounded-r-xl">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-2">
                                ข้อมูลที่ต้องเตรียม
                            </h3>
                            <ul class="space-y-1 text-sm text-blue-800 dark:text-blue-200">
                                <li>✅ ชื่อโครงการ (Project Name)</li>
                                <li>✅ Logo และ Branding Materials</li>
                                <li>✅ Whitepaper (ถ้ามี)</li>
                                <li>✅ Social Media Accounts</li>
                                <li>✅ TPIX สำหรับ Gas Fee (≥1 TPIX)</li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Form --}}
                <form method="POST" action="{{ route('admin.tpix.deployment.store') }}" class="space-y-8">
                    @csrf

                    {{-- Configuration Name --}}
                    <div x-data="{ focused: false }">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                            ชื่อ Configuration <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input
                                type="text"
                                name="name"
                                value="{{ old('name') }}"
                                @focus="focused = true"
                                @blur="focused = false"
                                class="w-full px-6 py-4 text-lg rounded-2xl border-2 transition-all duration-300
                                       focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20
                                       dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                :class="focused ? 'border-blue-500 shadow-lg' : 'border-gray-300 dark:border-gray-600'"
                                placeholder="เช่น: My Awesome Token Project"
                                required
                                autofocus
                            />
                            <div class="absolute right-4 top-1/2 -translate-y-1/2">
                                <svg class="w-6 h-6 transition-colors duration-300"
                                     :class="focused ? 'text-blue-500' : 'text-gray-400'"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                        </div>
                        @error('name')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            💡 ใช้ชื่อที่สื่อความหมายและจดจำง่าย เช่น "DeFi Token 2024", "My GameFi Coin"
                        </p>
                    </div>

                    {{-- Features Preview --}}
                    <div class="p-6 bg-gradient-to-r from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 rounded-2xl border border-purple-200 dark:border-purple-800">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path>
                            </svg>
                            สิ่งที่คุณจะได้รับ
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach([
                                'Wizard แบบ Step-by-Step (7 ขั้นตอน)',
                                'Prerequisites Check อัตโนมัติ',
                                'Smart Contract Generator',
                                'One-Click Deployment',
                                'DEX Integration (Liquidity Pool)',
                                'CoinMarketCap/CoinGecko Ready',
                                'Real-time Progress Tracking',
                                'Full Documentation & Support'
                            ] as $feature)
                                <div class="flex items-center gap-3">
                                    <div class="flex-shrink-0 w-6 h-6 bg-green-500 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $feature }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- 7 Steps Preview --}}
                    <div class="p-6 bg-gray-50 dark:bg-gray-900/50 rounded-2xl">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                            📋 ขั้นตอนการ Deploy (7 Steps)
                        </h3>
                        <div class="space-y-3">
                            @foreach([
                                ['num' => 1, 'title' => 'Prerequisites Check', 'desc' => 'ตรวจสอบความพร้อมของระบบ'],
                                ['num' => 2, 'title' => 'Token Configuration', 'desc' => 'ตั้งค่าพื้นฐานของเหรียญ'],
                                ['num' => 3, 'title' => 'Tokenomics', 'desc' => 'กำหนดเศรษฐศาสตร์เหรียญ'],
                                ['num' => 4, 'title' => 'Smart Contract', 'desc' => 'ปรับแต่ง Contract'],
                                ['num' => 5, 'title' => 'Deploy & Verify', 'desc' => 'Deploy ขึ้น Blockchain'],
                                ['num' => 6, 'title' => 'DEX Integration', 'desc' => 'สร้าง Liquidity Pool'],
                                ['num' => 7, 'title' => 'Listing', 'desc' => 'List บน CMC/CoinGecko']
                            ] as $step)
                                <div class="flex items-center gap-4 p-3 bg-white dark:bg-gray-800 rounded-xl hover:shadow-md transition">
                                    <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center text-white font-bold shadow-lg">
                                        {{ $step['num'] }}
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-gray-900 dark:text-white">{{ $step['title'] }}</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $step['desc'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Estimated Time --}}
                    <div class="flex items-center justify-center gap-2 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl">
                        <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                            ⏱️ เวลาโดยประมาณ: <strong>20-30 นาที</strong> (ถ้าเตรียมข้อมูลครบ)
                        </span>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex gap-4 pt-6">
                        <a href="{{ route('admin.tpix.deployment.index') }}"
                           class="flex-1 px-6 py-4 text-center bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                            ยกเลิก
                        </a>
                        <button type="submit"
                                class="flex-1 px-6 py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300 flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                            เริ่มต้น Wizard
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Help Box --}}
        <div class="mt-8 text-center text-sm text-gray-600 dark:text-gray-400">
            <p>💡 <strong>Tip:</strong> คุณสามารถบันทึกและกลับมาทำต่อได้ทุกเมื่อ ไม่ต้องทำให้เสร็จในครั้งเดียว</p>
        </div>
    </div>
</div>

{{-- Error Messages --}}
@if(session('error'))
    <div x-data="{ show: true }" x-show="show" x-transition
         class="fixed top-4 right-4 z-50 max-w-md">
        <div class="flex items-center gap-3 p-4 bg-gradient-to-r from-red-50 to-pink-50 dark:from-red-900/20 dark:to-pink-900/20 border-l-4 border-red-500 rounded-r-xl shadow-lg">
            <svg class="w-6 h-6 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
            </svg>
            <p class="text-red-800 dark:text-red-200 font-medium">{{ session('error') }}</p>
            <button @click="show = false" class="ml-auto text-red-600 hover:text-red-800 text-2xl leading-none">&times;</button>
        </div>
    </div>
@endif
@endsection
