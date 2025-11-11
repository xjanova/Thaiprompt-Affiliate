@extends('layouts.admin')

@section('title', 'จัดการบอทอัตโนมัติ')

@section('content')
<div class="space-y-6">
    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-2 border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-6 py-4 rounded-xl shadow-lg" role="alert">
            <div class="flex items-center">
                <svg class="w-6 h-6 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <span class="font-semibold">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 border-2 border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-6 py-4 rounded-xl shadow-lg" role="alert">
            <div class="flex items-center">
                <svg class="w-6 h-6 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <span class="font-semibold">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- Header with Futuristic Bot Design -->
    <div class="relative overflow-hidden bg-gradient-to-br from-cyan-500 via-purple-600 to-indigo-700 dark:from-cyan-900 dark:via-purple-900 dark:to-indigo-950 rounded-3xl shadow-2xl p-8">
        <!-- Animated Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: repeating-linear-gradient(45deg, transparent, transparent 35px, rgba(255,255,255,.1) 35px, rgba(255,255,255,.1) 70px);"></div>
        </div>

        <div class="relative flex flex-col sm:flex-row justify-between items-start sm:items-center gap-6">
            <div class="flex items-center gap-6">
                <!-- Animated Robot Avatar -->
                <div class="relative">
                    <div class="w-24 h-24 bg-gradient-to-br from-cyan-400 to-purple-500 rounded-2xl flex items-center justify-center shadow-2xl transform hover:scale-110 transition-all duration-300 animate-pulse">
                        <svg class="w-14 h-14 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <!-- Status Light -->
                    <div class="absolute -top-1 -right-1 w-6 h-6 bg-green-400 rounded-full border-4 border-white dark:border-gray-900 animate-ping"></div>
                    <div class="absolute -top-1 -right-1 w-6 h-6 bg-green-400 rounded-full border-4 border-white dark:border-gray-900"></div>
                </div>

                <div>
                    <h2 class="text-4xl font-bold text-white flex items-center gap-3">
                        <span>บอทอัตโนมัติ</span>
                        <span class="inline-flex items-center px-4 py-1.5 bg-white/20 backdrop-blur-sm text-sm font-semibold rounded-full border border-white/30">
                            AI Powered
                        </span>
                    </h2>
                    <p class="text-cyan-100 mt-2 text-lg">ระบบบอทอัจฉริยะที่ทำงานอัตโนมัติตลอด 24/7</p>
                    <div class="flex items-center gap-4 mt-4">
                        <div class="flex items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur-sm rounded-xl">
                            <div class="w-3 h-3 bg-green-400 rounded-full animate-pulse"></div>
                            <span class="text-white text-sm font-medium">{{ $automations->where('is_active', true)->count() }} บอทกำลังทำงาน</span>
                        </div>
                        <div class="flex items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur-sm rounded-xl">
                            <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                            <span class="text-white text-sm font-medium">{{ $automations->where('is_active', false)->count() }} บอทหยุดทำงาน</span>
                        </div>
                    </div>
                </div>
            </div>

            <a href="{{ route('admin.bot-automation.create') }}"
               class="inline-flex items-center px-8 py-4 bg-white dark:bg-gray-800 text-transparent bg-clip-text bg-gradient-to-r from-cyan-600 to-purple-600 font-bold rounded-2xl hover:shadow-2xl transition-all duration-300 transform hover:scale-105 border-2 border-white/50">
                <svg class="w-6 h-6 mr-3 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path>
                </svg>
                <span class="text-gray-900 dark:text-white">สร้างบอทใหม่</span>
            </a>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 p-6">
        <form method="GET" action="{{ route('admin.bot-automation.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ค้นหา</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="ชื่อบอทหรือคำอธิบาย"
                       class="w-full px-4 py-3 border-2 border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-all duration-200">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ประเภทบอท</label>
                <select name="automation_type" class="w-full px-4 py-3 border-2 border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-all duration-200">
                    <option value="">ทั้งหมด</option>
                    <option value="scheduled_post" {{ request('automation_type') == 'scheduled_post' ? 'selected' : '' }}>โพสต์ตามกำหนดเวลา</option>
                    <option value="customer_support" {{ request('automation_type') == 'customer_support' ? 'selected' : '' }}>ซัพพอร์ตลูกค้า</option>
                    <option value="sales_assistant" {{ request('automation_type') == 'sales_assistant' ? 'selected' : '' }}>ผู้ช่วยขาย</option>
                    <option value="engagement" {{ request('automation_type') == 'engagement' ? 'selected' : '' }}>เพิ่มการมีส่วนร่วม</option>
                    <option value="analytics" {{ request('automation_type') == 'analytics' ? 'selected' : '' }}>วิเคราะห์ข้อมูล</option>
                </select>
            </div>

            <div class="flex items-end gap-3">
                <button type="submit" class="flex-1 px-5 py-3 bg-gradient-to-r from-cyan-600 to-purple-600 text-white font-semibold rounded-xl hover:from-cyan-700 hover:to-purple-700 transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                    <svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    ค้นหา
                </button>
                <a href="{{ route('admin.bot-automation.index') }}" class="px-5 py-3 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-300 dark:hover:bg-slate-600 transition-all duration-200 shadow-md hover:shadow-lg">
                    ล้าง
                </a>
            </div>
        </form>
    </div>

    <!-- Bot Automation Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($automations as $automation)
            <div class="group bg-gradient-to-br from-white to-gray-50 dark:from-slate-800 dark:to-slate-900 rounded-2xl shadow-xl border-2 border-gray-100 dark:border-slate-700 overflow-hidden hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
                <!-- Card Header with Bot Icon -->
                <div class="relative p-6 bg-gradient-to-br from-cyan-500 to-purple-600 dark:from-cyan-900 dark:to-purple-900">
                    <!-- Background Pattern -->
                    <div class="absolute inset-0 opacity-10">
                        <div class="absolute inset-0" style="background-image: repeating-linear-gradient(45deg, transparent, transparent 20px, rgba(255,255,255,.1) 20px, rgba(255,255,255,.1) 40px);"></div>
                    </div>

                    <div class="relative flex items-start justify-between">
                        <div class="flex items-center gap-4">
                            <!-- Animated Bot Avatar -->
                            <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center border-2 border-white/30 group-hover:scale-110 transition-all duration-300">
                                @if($automation->automation_type === 'scheduled_post')
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                @elseif($automation->automation_type === 'customer_support')
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                                    </svg>
                                @elseif($automation->automation_type === 'sales_assistant')
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                @elseif($automation->automation_type === 'engagement')
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                                    </svg>
                                @else
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                @endif
                            </div>

                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <!-- Status Light -->
                                    @if($automation->is_active)
                                        <div class="relative">
                                            <div class="w-3 h-3 bg-green-400 rounded-full animate-ping absolute"></div>
                                            <div class="w-3 h-3 bg-green-400 rounded-full"></div>
                                        </div>
                                        <span class="text-xs font-bold text-white">ทำงานอยู่</span>
                                    @else
                                        <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                                        <span class="text-xs font-bold text-white/70">หยุดทำงาน</span>
                                    @endif
                                </div>
                                <h3 class="text-lg font-bold text-white line-clamp-1">{{ $automation->name }}</h3>
                            </div>
                        </div>

                        <!-- Bot Type Badge -->
                        <span class="px-3 py-1 bg-white/20 backdrop-blur-sm text-white text-xs font-bold rounded-lg border border-white/30">
                            @if($automation->automation_type === 'scheduled_post')
                                โพสต์
                            @elseif($automation->automation_type === 'customer_support')
                                ซัพพอร์ต
                            @elseif($automation->automation_type === 'sales_assistant')
                                ขาย
                            @elseif($automation->automation_type === 'engagement')
                                เพิ่มยอด
                            @else
                                วิเคราะห์
                            @endif
                        </span>
                    </div>
                </div>

                <!-- Card Body -->
                <div class="p-6">
                    <!-- Description -->
                    <p class="text-gray-600 dark:text-gray-400 text-sm mb-4 line-clamp-2 min-h-[40px]">
                        {{ $automation->description ?? 'ไม่มีคำอธิบาย' }}
                    </p>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <!-- Executions -->
                        <div class="bg-gradient-to-br from-cyan-50 to-cyan-100 dark:from-cyan-900/20 dark:to-cyan-800/20 rounded-xl p-4 border border-cyan-200 dark:border-cyan-800">
                            <div class="flex items-center gap-2 mb-1">
                                <svg class="w-4 h-4 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                <span class="text-xs font-medium text-cyan-700 dark:text-cyan-300">ครั้งที่ทำงาน</span>
                            </div>
                            <div class="text-2xl font-bold text-cyan-600 dark:text-cyan-400">{{ $automation->executions_count ?? 0 }}</div>
                        </div>

                        <!-- Success Rate -->
                        @php
                            $successRate = $automation->executions_count > 0
                                ? round(($automation->executions()->where('status', 'completed')->count() / $automation->executions_count) * 100)
                                : 0;
                        @endphp
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-xl p-4 border border-purple-200 dark:border-purple-800">
                            <div class="flex items-center gap-2 mb-1">
                                <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-xs font-medium text-purple-700 dark:text-purple-300">อัตราสำเร็จ</span>
                            </div>
                            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $successRate }}%</div>
                        </div>
                    </div>

                    <!-- Trigger Type -->
                    <div class="flex items-center gap-2 p-3 bg-gray-50 dark:bg-slate-700/50 rounded-xl mb-4">
                        <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            ทริกเกอร์:
                            @if($automation->trigger_type === 'schedule')
                                ตามกำหนดเวลา
                            @elseif($automation->trigger_type === 'event')
                                เมื่อมีเหตุการณ์
                            @elseif($automation->trigger_type === 'webhook')
                                Webhook
                            @else
                                ด้วยตนเอง
                            @endif
                        </span>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-3">
                        <a href="{{ route('admin.bot-automation.show', $automation) }}"
                           class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-gradient-to-r from-cyan-600 to-purple-600 text-white font-semibold rounded-xl hover:from-cyan-700 hover:to-purple-700 transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            ดูรายละเอียด
                        </a>

                        <!-- Toggle Status -->
                        <form action="{{ route('admin.bot-automation.toggle', $automation) }}" method="POST">
                            @csrf
                            <button type="submit"
                                    onclick="return confirm('ต้องการเปลี่ยนสถานะบอทนี้หรือไม่?')"
                                    class="p-2.5 @if($automation->is_active) bg-gradient-to-r from-red-500 to-rose-500 @else bg-gradient-to-r from-green-500 to-emerald-500 @endif text-white rounded-xl hover:shadow-lg transition-all duration-200 transform hover:-translate-y-0.5"
                                    title="@if($automation->is_active) หยุดทำงาน @else เริ่มทำงาน @endif">
                                @if($automation->is_active)
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                @endif
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Card Footer -->
                <div class="px-6 py-4 bg-gray-50 dark:bg-slate-900/50 border-t border-gray-100 dark:border-slate-700">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-500 dark:text-gray-400">
                            สร้างเมื่อ {{ $automation->created_at->format('d/m/Y') }}
                        </span>
                        @if($automation->user)
                            <span class="text-gray-500 dark:text-gray-400">
                                โดย {{ $automation->user->name }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <!-- Empty State -->
            <div class="col-span-full">
                <div class="bg-gradient-to-br from-cyan-50 to-purple-50 dark:from-cyan-900/10 dark:to-purple-900/10 rounded-3xl p-16 text-center border-2 border-dashed border-cyan-300 dark:border-cyan-800">
                    <div class="flex justify-center mb-6">
                        <div class="w-32 h-32 bg-gradient-to-br from-cyan-400 to-purple-500 rounded-3xl flex items-center justify-center shadow-2xl animate-bounce">
                            <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-800 dark:text-gray-200 mb-3">ยังไม่มีบอทอัตโนมัติ</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-8 text-lg">เริ่มสร้างบอทอัจฉริยะเพื่อทำงานอัตโนมัติตลอด 24/7</p>
                    <a href="{{ route('admin.bot-automation.create') }}"
                       class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-cyan-600 to-purple-600 text-white font-bold rounded-2xl hover:from-cyan-700 hover:to-purple-700 transition-all duration-300 shadow-xl hover:shadow-2xl transform hover:scale-105">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path>
                        </svg>
                        สร้างบอทแรกของคุณ
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($automations->hasPages())
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between">
                <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    แสดง <span class="font-bold text-cyan-600 dark:text-cyan-400">{{ $automations->firstItem() ?? 0 }}</span>
                    ถึง <span class="font-bold text-cyan-600 dark:text-cyan-400">{{ $automations->lastItem() ?? 0 }}</span>
                    จากทั้งหมด <span class="font-bold text-cyan-600 dark:text-cyan-400">{{ $automations->total() }}</span> รายการ
                </div>
                <div>
                    {{ $automations->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    @endif
</div>

@push('styles')
<style>
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }
    .animate-float {
        animation: float 3s ease-in-out infinite;
    }
</style>
@endpush
@endsection
