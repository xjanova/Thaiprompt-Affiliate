@extends('layouts.admin')

@section('title', 'รายละเอียดบอทอัตโนมัติ')

@section('content')
<div class="space-y-6">
    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-2 border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-6 py-4 rounded-xl shadow-lg animate-pulse" role="alert">
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

    <!-- Header with Bot Info -->
    <div class="relative overflow-hidden bg-gradient-to-br from-cyan-500 via-purple-600 to-indigo-700 dark:from-cyan-900 dark:via-purple-900 dark:to-indigo-950 rounded-3xl shadow-2xl p-8">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: repeating-linear-gradient(45deg, transparent, transparent 35px, rgba(255,255,255,.1) 35px, rgba(255,255,255,.1) 70px);"></div>
        </div>

        <div class="relative">
            <!-- Back Button & Actions -->
            <div class="flex items-center justify-between mb-6">
                <a href="{{ route('admin.bot-automation.index') }}"
                   class="inline-flex items-center px-5 py-2.5 bg-white/20 backdrop-blur-sm text-white font-semibold rounded-xl hover:bg-white/30 transition-all duration-200 border border-white/30">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    กลับ
                </a>

                <div class="flex items-center gap-3">
                    <!-- Toggle Status -->
                    <form action="{{ route('admin.bot-automation.toggle', $automation) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                                onclick="return confirm('ต้องการเปลี่ยนสถานะบอทนี้หรือไม่?')"
                                class="px-6 py-2.5 @if($automation->is_active) bg-red-500/80 hover:bg-red-600 @else bg-green-500/80 hover:bg-green-600 @endif backdrop-blur-sm text-white font-semibold rounded-xl transition-all duration-200 shadow-lg border border-white/30">
                            @if($automation->is_active)
                                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                หยุดทำงาน
                            @else
                                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                เริ่มทำงาน
                            @endif
                        </button>
                    </form>

                    <!-- Execute Manually -->
                    <form action="{{ route('admin.bot-automation.execute', $automation) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                                onclick="return confirm('ต้องการรันบอทนี้ทันทีหรือไม่?')"
                                class="px-6 py-2.5 bg-yellow-500/80 hover:bg-yellow-600 backdrop-blur-sm text-white font-semibold rounded-xl transition-all duration-200 shadow-lg border border-white/30">
                            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            รันทันที
                        </button>
                    </form>

                    <!-- Edit -->
                    <a href="{{ route('admin.bot-automation.edit', $automation) }}"
                       class="px-6 py-2.5 bg-white/80 hover:bg-white backdrop-blur-sm text-cyan-700 font-semibold rounded-xl transition-all duration-200 shadow-lg border border-white/30">
                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        แก้ไข
                    </a>

                    <!-- Delete -->
                    <form action="{{ route('admin.bot-automation.destroy', $automation) }}" method="POST" class="inline"
                          onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบบอทนี้? การกระทำนี้ไม่สามารถย้อนกลับได้');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="px-6 py-2.5 bg-red-500/80 hover:bg-red-600 backdrop-blur-sm text-white font-semibold rounded-xl transition-all duration-200 shadow-lg border border-white/30">
                            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            ลบ
                        </button>
                    </form>
                </div>
            </div>

            <!-- Bot Header -->
            <div class="flex items-center gap-6">
                <!-- Animated Bot Avatar -->
                <div class="relative">
                    <div class="w-32 h-32 bg-white/20 backdrop-blur-sm rounded-3xl flex items-center justify-center shadow-2xl border-4 border-white/30 transform hover:scale-105 transition-all duration-300">
                        @if($automation->automation_type === 'scheduled_post')
                            <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        @elseif($automation->automation_type === 'customer_support')
                            <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        @elseif($automation->automation_type === 'sales_assistant')
                            <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        @elseif($automation->automation_type === 'engagement')
                            <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                            </svg>
                        @else
                            <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        @endif
                    </div>
                    <!-- Status Light with Animation -->
                    @if($automation->is_active)
                        <div class="absolute -bottom-2 -right-2 w-12 h-12 bg-green-400 rounded-full border-4 border-white dark:border-gray-900 animate-ping"></div>
                        <div class="absolute -bottom-2 -right-2 w-12 h-12 bg-green-400 rounded-full border-4 border-white dark:border-gray-900 flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    @else
                        <div class="absolute -bottom-2 -right-2 w-12 h-12 bg-gray-400 rounded-full border-4 border-white dark:border-gray-900 flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 00-1 1v4a1 1 0 001 1h4a1 1 0 001-1V8a1 1 0 00-1-1H8z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    @endif
                </div>

                <div class="flex-1">
                    <div class="flex items-center gap-4 mb-3">
                        <h2 class="text-4xl font-bold text-white">{{ $automation->name }}</h2>
                        @if($automation->is_active)
                            <span class="inline-flex items-center px-4 py-2 bg-green-500/80 backdrop-blur-sm text-white text-sm font-bold rounded-xl border border-white/30 animate-pulse">
                                <div class="w-2 h-2 bg-white rounded-full mr-2"></div>
                                กำลังทำงาน
                            </span>
                        @else
                            <span class="inline-flex items-center px-4 py-2 bg-gray-500/80 backdrop-blur-sm text-white text-sm font-bold rounded-xl border border-white/30">
                                <div class="w-2 h-2 bg-white rounded-full mr-2"></div>
                                หยุดทำงาน
                            </span>
                        @endif
                    </div>

                    @if($automation->description)
                        <p class="text-cyan-100 text-lg mb-4">{{ $automation->description }}</p>
                    @endif

                    <div class="flex items-center gap-6">
                        <!-- Type Badge -->
                        <div class="flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-sm rounded-xl border border-white/30">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            <span class="text-white font-semibold">
                                @if($automation->automation_type === 'scheduled_post')
                                    โพสต์ตามกำหนดเวลา
                                @elseif($automation->automation_type === 'customer_support')
                                    ซัพพอร์ตลูกค้า
                                @elseif($automation->automation_type === 'sales_assistant')
                                    ผู้ช่วยขาย
                                @elseif($automation->automation_type === 'engagement')
                                    เพิ่มการมีส่วนร่วม
                                @else
                                    วิเคราะห์ข้อมูล
                                @endif
                            </span>
                        </div>

                        <!-- Trigger Type -->
                        <div class="flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-sm rounded-xl border border-white/30">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <span class="text-white font-semibold">
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

                        @if($automation->user)
                            <div class="flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-sm rounded-xl border border-white/30">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <span class="text-white font-semibold">{{ $automation->user->name }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Dashboard -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Executions -->
        <div class="bg-gradient-to-br from-cyan-500 to-blue-600 dark:from-cyan-900 dark:to-blue-900 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <span class="text-xs font-semibold px-3 py-1 bg-white/20 backdrop-blur-sm rounded-lg">ทั้งหมด</span>
            </div>
            <div class="text-4xl font-bold mb-2">{{ $statistics['total_executions'] ?? 0 }}</div>
            <div class="text-cyan-100 text-sm font-medium">ครั้งที่ทำงาน</div>
        </div>

        <!-- Success Rate -->
        <div class="bg-gradient-to-br from-green-500 to-emerald-600 dark:from-green-900 dark:to-emerald-900 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-xs font-semibold px-3 py-1 bg-white/20 backdrop-blur-sm rounded-lg">อัตรา</span>
            </div>
            <div class="text-4xl font-bold mb-2">{{ $statistics['success_rate'] ?? 0 }}%</div>
            <div class="text-green-100 text-sm font-medium">อัตราความสำเร็จ</div>
        </div>

        <!-- Completed -->
        <div class="bg-gradient-to-br from-purple-500 to-pink-600 dark:from-purple-900 dark:to-pink-900 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <span class="text-xs font-semibold px-3 py-1 bg-white/20 backdrop-blur-sm rounded-lg">สำเร็จ</span>
            </div>
            <div class="text-4xl font-bold mb-2">{{ $statistics['completed_executions'] ?? 0 }}</div>
            <div class="text-purple-100 text-sm font-medium">ทำงานสำเร็จ</div>
        </div>

        <!-- Failed -->
        <div class="bg-gradient-to-br from-red-500 to-rose-600 dark:from-red-900 dark:to-rose-900 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
                <span class="text-xs font-semibold px-3 py-1 bg-white/20 backdrop-blur-sm rounded-lg">ล้มเหลว</span>
            </div>
            <div class="text-4xl font-bold mb-2">{{ $statistics['failed_executions'] ?? 0 }}</div>
            <div class="text-red-100 text-sm font-medium">ทำงานล้มเหลว</div>
        </div>
    </div>

    <!-- Workflow Visualization & Content -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Workflow Builder Visualization -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 dark:from-indigo-900 dark:to-purple-900 px-6 py-4">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    ขั้นตอนการทำงาน
                </h3>
            </div>

            <div class="p-6">
                <!-- Workflow Steps -->
                <div class="space-y-4">
                    <!-- Step 1: Trigger -->
                    <div class="relative bg-gradient-to-r from-cyan-50 to-blue-50 dark:from-cyan-900/20 dark:to-blue-900/20 rounded-xl p-4 border-2 border-cyan-200 dark:border-cyan-800">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-cyan-500 to-blue-600 text-white rounded-lg flex items-center justify-center font-bold text-lg">
                                1
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-900 dark:text-white mb-1">ทริกเกอร์</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    @if($automation->trigger_type === 'schedule')
                                        รันตามกำหนดเวลา
                                        @if($automation->schedule_type)
                                            ({{ ucfirst($automation->schedule_type) }})
                                        @endif
                                    @elseif($automation->trigger_type === 'event')
                                        รันเมื่อมีเหตุการณ์
                                    @elseif($automation->trigger_type === 'webhook')
                                        รับข้อมูลจาก Webhook
                                    @else
                                        เริ่มด้วยตนเอง
                                    @endif
                                </p>
                            </div>
                        </div>
                        <!-- Arrow -->
                        <div class="absolute left-5 -bottom-5 w-0.5 h-6 bg-gradient-to-b from-cyan-500 to-transparent"></div>
                    </div>

                    <!-- Step 2: Content -->
                    <div class="relative bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl p-4 border-2 border-purple-200 dark:border-purple-800">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-600 text-white rounded-lg flex items-center justify-center font-bold text-lg">
                                2
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-900 dark:text-white mb-1">เนื้อหา</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    @if($automation->content_source === 'custom')
                                        ใช้เนื้อหาที่กำหนดเอง
                                    @elseif($automation->content_source === 'template')
                                        ใช้เนื้อหาจากเทมเพลต
                                    @else
                                        สร้างด้วย AI
                                    @endif
                                </p>
                            </div>
                        </div>
                        <!-- Arrow -->
                        <div class="absolute left-5 -bottom-5 w-0.5 h-6 bg-gradient-to-b from-purple-500 to-transparent"></div>
                    </div>

                    <!-- Step 3: Action -->
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-4 border-2 border-green-200 dark:border-green-800">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 text-white rounded-lg flex items-center justify-center font-bold text-lg">
                                3
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-900 dark:text-white mb-1">ดำเนินการ</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    ทำงานตาม {{ $automation->automation_type === 'scheduled_post' ? 'โพสต์เนื้อหา' : ($automation->automation_type === 'customer_support' ? 'ตอบลูกค้า' : 'ประมวลผล') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Configuration -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
            <div class="bg-gradient-to-r from-pink-500 to-rose-600 dark:from-pink-900 dark:to-rose-900 px-6 py-4">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    การตั้งค่าเนื้อหา
                </h3>
            </div>

            <div class="p-6 space-y-4">
                <!-- Content Source -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">แหล่งเนื้อหา</label>
                    <div class="px-4 py-3 bg-gray-50 dark:bg-slate-700/50 rounded-xl">
                        <span class="text-gray-900 dark:text-white font-medium">
                            @if($automation->content_source === 'custom')
                                เนื้อหาที่กำหนดเอง
                            @elseif($automation->content_source === 'template')
                                จากเทมเพลต
                            @else
                                สร้างด้วย AI
                            @endif
                        </span>
                    </div>
                </div>

                <!-- Custom Content or AI Prompt -->
                @if($automation->content_source === 'custom' && $automation->custom_content)
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">เนื้อหา</label>
                        <div class="px-4 py-3 bg-gray-50 dark:bg-slate-700/50 rounded-xl max-h-64 overflow-y-auto">
                            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $automation->custom_content }}</p>
                        </div>
                    </div>
                @elseif($automation->content_source === 'ai_generated' && $automation->ai_generation_prompt)
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">คำสั่งสำหรับ AI</label>
                        <div class="px-4 py-3 bg-gradient-to-br from-cyan-50 to-purple-50 dark:from-cyan-900/20 dark:to-purple-900/20 rounded-xl border border-cyan-200 dark:border-cyan-800">
                            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $automation->ai_generation_prompt }}</p>
                        </div>
                    </div>
                @endif

                <!-- Template Info -->
                @if($automation->template)
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">เทมเพลต</label>
                        <div class="px-4 py-3 bg-gray-50 dark:bg-slate-700/50 rounded-xl">
                            <span class="text-gray-900 dark:text-white font-medium">{{ $automation->template->name }}</span>
                        </div>
                    </div>
                @endif

                <!-- Bot Profile -->
                @if($automation->aiBotProfile)
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">โปรไฟล์บอท</label>
                        <div class="px-4 py-3 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl border border-purple-200 dark:border-purple-800">
                            <span class="text-gray-900 dark:text-white font-medium">{{ $automation->aiBotProfile->name }}</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Recent Executions -->
    @if($automation->executions && $automation->executions->count() > 0)
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
            <div class="bg-gradient-to-r from-orange-500 to-amber-600 dark:from-orange-900 dark:to-amber-900 px-6 py-4">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    การทำงานล่าสุด
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-gray-50 dark:bg-slate-900/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">เวลา</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">สถานะ</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ทริกเกอร์</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ผลลัพธ์</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                        @foreach($automation->executions->take(10) as $execution)
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors duration-200">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $execution->created_at->format('d/m/Y') }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $execution->created_at->format('H:i:s') }} น.</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($execution->status === 'completed')
                                        <span class="inline-flex items-center px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 text-xs font-bold rounded-lg">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            สำเร็จ
                                        </span>
                                    @elseif($execution->status === 'failed')
                                        <span class="inline-flex items-center px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 text-xs font-bold rounded-lg">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                            </svg>
                                            ล้มเหลว
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 text-xs font-bold rounded-lg">
                                            <svg class="w-4 h-4 mr-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                            กำลังทำงาน
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                    {{ ucfirst($execution->trigger_source ?? 'manual') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $execution->result_message ?? '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 p-12 text-center">
            <div class="w-20 h-20 bg-gradient-to-br from-cyan-100 to-purple-100 dark:from-cyan-900/30 dark:to-purple-900/30 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200 mb-2">ยังไม่มีประวัติการทำงาน</h3>
            <p class="text-gray-600 dark:text-gray-400">บอทนี้ยังไม่เคยทำงานเลย</p>
        </div>
    @endif
</div>
@endsection
