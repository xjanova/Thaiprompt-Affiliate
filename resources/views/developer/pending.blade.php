@extends('layouts.app')

@section('title', 'รอการอนุมัติ')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-amber-50 to-orange-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 flex items-center justify-center py-12">

    {{-- Background Decorations --}}
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-0 right-0 w-96 h-96 bg-amber-500/20 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-orange-500/20 rounded-full blur-3xl animate-pulse"></div>
    </div>

    <div class="container mx-auto px-4 relative z-10">
        <div class="max-w-3xl mx-auto">

            {{-- Main Card --}}
            <div class="bg-white/90 dark:bg-slate-800/90 backdrop-blur-xl rounded-3xl p-8 lg:p-12 border-2 border-white/50 dark:border-slate-700/50 shadow-2xl text-center">

                {{-- Status Icon --}}
                <div class="w-32 h-32 mx-auto mb-8 bg-gradient-to-br from-amber-500 to-orange-500 rounded-full flex items-center justify-center shadow-2xl animate-bounce">
                    <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>

                {{-- Status Badge --}}
                <div class="inline-block px-6 py-3 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-full font-bold text-lg mb-6 shadow-lg">
                    ⏳ รอการอนุมัติ
                </div>

                {{-- Title --}}
                <h1 class="text-4xl font-black text-slate-900 dark:text-white mb-4">
                    กำลังตรวจสอบข้อมูลของคุณ
                </h1>

                <p class="text-lg text-slate-600 dark:text-slate-400 mb-8">
                    ระบบกำลังตรวจสอบข้อมูล KYC และเอกสารของคุณ<br>
                    โปรดรอการอนุมัติจากทีมงาน
                </p>

                {{-- Timeline --}}
                <div class="max-w-2xl mx-auto mb-8">
                    <div class="flex items-center justify-between">
                        {{-- Step 1: Submitted --}}
                        <div class="flex-1">
                            <div class="relative">
                                <div class="w-12 h-12 mx-auto bg-green-500 rounded-full flex items-center justify-center shadow-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <div class="mt-3 text-sm font-bold text-green-600 dark:text-green-400">ส่งข้อมูลแล้ว</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">{{ $developerProfile->created_at->format('d/m/Y H:i') }}</div>
                            </div>
                        </div>

                        {{-- Connector Line --}}
                        <div class="flex-1 h-1 bg-gradient-to-r from-green-500 to-amber-500"></div>

                        {{-- Step 2: Under Review --}}
                        <div class="flex-1">
                            <div class="relative">
                                <div class="w-12 h-12 mx-auto bg-amber-500 rounded-full flex items-center justify-center shadow-lg animate-pulse">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="mt-3 text-sm font-bold text-amber-600 dark:text-amber-400">กำลังตรวจสอบ</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">รอดำเนินการ</div>
                            </div>
                        </div>

                        {{-- Connector Line --}}
                        <div class="flex-1 h-1 bg-slate-200 dark:bg-slate-700"></div>

                        {{-- Step 3: Approved --}}
                        <div class="flex-1">
                            <div class="relative opacity-50">
                                <div class="w-12 h-12 mx-auto bg-slate-300 dark:bg-slate-700 rounded-full flex items-center justify-center shadow-lg">
                                    <svg class="w-6 h-6 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                                    </svg>
                                </div>
                                <div class="mt-3 text-sm font-bold text-slate-500">อนุมัติแล้ว</div>
                                <div class="text-xs text-slate-400">รอดำเนินการ</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Information Card --}}
                <div class="bg-blue-50 dark:bg-blue-900/20 border-2 border-blue-200 dark:border-blue-800 rounded-2xl p-6 mb-8">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="flex-1 text-left">
                            <h3 class="font-bold text-slate-900 dark:text-white mb-2">💡 ข้อมูลเพิ่มเติม</h3>
                            <ul class="text-sm text-slate-600 dark:text-slate-400 space-y-2">
                                <li>• ระยะเวลาตรวจสอบโดยเฉลี่ย: 1-3 วันทำการ</li>
                                <li>• ทีมงานจะตรวจสอบข้อมูล KYC และเอกสารประกอบ</li>
                                <li>• คุณจะได้รับอีเมลแจ้งเตือนเมื่อการอนุมัติเสร็จสิ้น</li>
                                <li>• หากข้อมูลไม่ครบถ้วน ทีมงานจะติดต่อกลับ</li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Your Information Summary --}}
                <div class="bg-slate-50 dark:bg-slate-700/50 rounded-2xl p-6 mb-8 text-left">
                    <h3 class="font-black text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        ข้อมูลที่ส่งมา
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-slate-500 dark:text-slate-400">ชื่อที่แสดง:</span>
                            <span class="ml-2 font-bold text-slate-900 dark:text-white">{{ $developerProfile->display_name }}</span>
                        </div>
                        @if($developerProfile->company_name)
                        <div>
                            <span class="text-slate-500 dark:text-slate-400">บริษัท:</span>
                            <span class="ml-2 font-bold text-slate-900 dark:text-white">{{ $developerProfile->company_name }}</span>
                        </div>
                        @endif
                        <div>
                            <span class="text-slate-500 dark:text-slate-400">อีเมล:</span>
                            <span class="ml-2 font-bold text-slate-900 dark:text-white">{{ $developerProfile->email }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 dark:text-slate-400">เบอร์โทร:</span>
                            <span class="ml-2 font-bold text-slate-900 dark:text-white">{{ $developerProfile->phone }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 dark:text-slate-400">LINE ID:</span>
                            <span class="ml-2 font-bold text-slate-900 dark:text-white">{{ $developerProfile->line_id }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 dark:text-slate-400">สถานะ:</span>
                            <span class="ml-2">{!! $developerProfile->status_badge !!}</span>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('home') }}"
                       class="px-8 py-4 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600 text-white rounded-xl font-bold shadow-lg hover:scale-105 transition-all duration-300">
                        🏠 กลับหน้าหลัก
                    </a>
                    <button onclick="window.location.reload()"
                            class="px-8 py-4 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-900 dark:text-white rounded-xl font-bold shadow-lg hover:scale-105 transition-all duration-300">
                        🔄 รีเฟรช
                    </button>
                </div>

                {{-- Contact Support --}}
                <div class="mt-8 pt-8 border-t-2 border-slate-200 dark:border-slate-700">
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        หากมีคำถามหรือข้อสงสัย โปรดติดต่อ
                        <a href="mailto:support@thaiprompt.com" class="text-blue-600 dark:text-blue-400 hover:underline font-bold">
                            support@thaiprompt.com
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * Auto-refresh ทุก 5 นาที เพื่อเช็คสถานะใหม่
 */
setTimeout(() => {
    window.location.reload();
}, 5 * 60 * 1000); // 5 minutes
</script>
@endpush
@endsection
