@extends('layouts.user-v4')

@section('title', 'ถูกระงับการเข้าถึง')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4 py-12">
    <div class="max-w-md w-full">
        {{-- Ban Card --}}
        <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-xl overflow-hidden backdrop-blur-sm border border-red-200/50 dark:border-red-700/50">
            {{-- Header --}}
            <div class="bg-gradient-to-r from-red-600 to-red-700 px-6 py-8 text-center">
                <div class="w-20 h-20 mx-auto bg-white/20 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-white mb-2">
                    ถูกระงับการเข้าถึง
                </h1>
                <p class="text-red-100 text-sm">
                    บัญชีของคุณถูกระงับจากการดูคลิปเพื่อรับรางวัล
                </p>
            </div>

            {{-- Content --}}
            <div class="p-6 space-y-6">
                {{-- Reason Box --}}
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
                    <h3 class="font-semibold text-red-800 dark:text-red-200 mb-2 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        สาเหตุการระงับ
                    </h3>
                    <p class="text-red-700 dark:text-red-300">
                        {{ $reason ?? 'ตรวจพบพฤติกรรมที่ไม่เหมาะสม' }}
                    </p>
                </div>

                {{-- Ban Time --}}
                @if(isset($banned_at))
                <div class="flex items-center gap-3 text-gray-600 dark:text-gray-400 text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>
                        ถูกระงับเมื่อ: {{ $banned_at instanceof \Carbon\Carbon ? $banned_at->format('d/m/Y H:i') : $banned_at }}
                    </span>
                </div>
                @endif

                {{-- Warning Info --}}
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4">
                    <h3 class="font-semibold text-yellow-800 dark:text-yellow-200 mb-2 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        ข้อมูลสำคัญ
                    </h3>
                    <ul class="text-yellow-700 dark:text-yellow-300 text-sm space-y-1">
                        <li>• การใช้เครื่องมือพัฒนา (DevTools) ขณะดูคลิปถือเป็นการละเมิดกฎ</li>
                        <li>• การพยายามโกงจะถูกตรวจจับและบันทึกไว้ทุกครั้ง</li>
                        <li>• การระงับนี้เป็นการระงับถาวร</li>
                    </ul>
                </div>

                {{-- Appeal Info --}}
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                    <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">
                        ต้องการอุทธรณ์?
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">
                        หากคุณเชื่อว่าเกิดความผิดพลาด กรุณาติดต่อทีมสนับสนุนพร้อมแจ้งรายละเอียด
                    </p>
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-6 pb-6">
                <a href="{{ route('user.dashboard') }}"
                   class="block w-full px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-xl font-medium text-center transition-all">
                    กลับหน้าหลัก
                </a>
            </div>
        </div>

        {{-- Additional Warning --}}
        <p class="text-center text-gray-500 dark:text-gray-400 text-xs mt-6">
            หากมีข้อสงสัย กรุณาติดต่อทีมสนับสนุน
        </p>
    </div>
</div>
@endsection
