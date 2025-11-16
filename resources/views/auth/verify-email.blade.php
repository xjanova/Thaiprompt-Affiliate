@extends('layouts.auth-arrow-x')

@section('title', 'ยืนยันอีเมล')
@section('subtitle', 'กรุณายืนยันอีเมลของคุณ')

@section('content')
    {{-- Header --}}
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-full mb-4 shadow-lg">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
            ยืนยันอีเมลของคุณ
        </h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            ก่อนดำเนินการต่อ กรุณายืนยันที่อยู่อีเมลของคุณ
        </p>
    </div>

    {{-- Success Message (After resend) --}}
    @if (session('message'))
        <div class="mb-6 p-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-l-4 border-green-500 rounded-lg" x-data="{ show: true }" x-show="show" x-transition>
            <div class="flex items-start">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <div class="flex-1">
                    <p class="font-medium text-green-800 dark:text-green-200">
                        {{ session('message') }}
                    </p>
                </div>
                <button @click="show = false" class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-200">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>
    @endif

    {{-- Info Message --}}
    <div class="mb-6 p-4 bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div class="flex-1">
                <p class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">
                    เราได้ส่งลิงก์ยืนยันไปยังอีเมลของคุณแล้ว
                </p>
                <p class="text-xs text-blue-700 dark:text-blue-300">
                    กรุณาตรวจสอบกล่องจดหมายของคุณและคลิกที่ลิงก์ยืนยันในอีเมล
                    หากไม่พบอีเมล กรุณาตรวจสอบในโฟลเดอร์สแปม
                </p>
            </div>
        </div>
    </div>

    {{-- Email Info --}}
    <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center mr-3">
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                </svg>
            </div>
            <div class="flex-1">
                <p class="text-xs text-gray-500 dark:text-gray-400">อีเมลที่ลงทะเบียน</p>
                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ Auth::user()->email }}</p>
            </div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="space-y-4" x-data="{ resending: false, canResend: true, countdown: 0, countdownInterval: null }">
        {{-- Resend Verification Email Form --}}
        <form method="POST" action="{{ route('verification.send') }}" @submit.prevent="
            if (canResend) {
                resending = true;
                canResend = false;
                countdown = 60;
                $el.submit();
                countdownInterval = setInterval(() => {
                    countdown--;
                    if (countdown <= 0) {
                        clearInterval(countdownInterval);
                        canResend = true;
                        resending = false;
                    }
                }, 1000);
            }
        ">
            @csrf

            <button type="submit"
                    :disabled="!canResend"
                    class="btn-shimmer w-full py-4 px-6 bg-gradient-to-r from-blue-600 via-cyan-600 to-teal-600
                           hover:from-blue-700 hover:via-cyan-700 hover:to-teal-700
                           dark:from-blue-500 dark:via-cyan-500 dark:to-teal-500
                           dark:hover:from-blue-600 dark:hover:via-cyan-600 dark:hover:to-teal-600
                           text-white font-bold text-lg rounded-xl
                           shadow-lg hover:shadow-2xl
                           transform hover:-translate-y-1
                           transition-all duration-300
                           disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none
                           focus:ring-4 focus:ring-blue-500/50 focus:outline-none">
                <span x-show="canResend && !resending" class="flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    ส่งอีเมลยืนยันอีกครั้ง
                </span>
                <span x-show="resending" class="flex items-center justify-center">
                    <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    กำลังส่ง...
                </span>
                <span x-show="!canResend && !resending" class="flex items-center justify-center" x-text="'รอ ' + countdown + ' วินาที'">
                </span>
            </button>
        </form>

        {{-- Logout Button --}}
        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit"
                    class="w-full py-3 px-6 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600
                           text-gray-700 dark:text-gray-300 font-semibold rounded-xl
                           hover:bg-gray-50 dark:hover:bg-gray-700
                           hover:border-gray-400 dark:hover:border-gray-500
                           transform hover:-translate-y-0.5
                           transition-all duration-300
                           focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600 focus:outline-none">
                <span class="flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    ออกจากระบบ
                </span>
            </button>
        </form>
    </div>

    {{-- Help Section --}}
    <div class="mt-8 p-4 bg-gradient-to-r from-orange-50 to-yellow-50 dark:from-orange-900/10 dark:to-yellow-900/10 border border-orange-200 dark:border-orange-800 rounded-lg">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-orange-600 dark:text-orange-400 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
            </svg>
            <div class="flex-1">
                <p class="text-sm font-semibold text-orange-800 dark:text-orange-200 mb-1">
                    ต้องการความช่วยเหลือ?
                </p>
                <p class="text-xs text-orange-700 dark:text-orange-300">
                    หากคุณไม่ได้รับอีเมลภายใน 5 นาที กรุณาติดต่อทีมสนับสนุนของเรา
                </p>
            </div>
        </div>
    </div>
@endsection

@section('footer-links')
    <div class="text-center text-white/80 dark:text-gray-400 text-sm">
        <p>ยังมีปัญหาอยู่? <a href="mailto:support@example.com" class="text-white dark:text-white font-semibold hover:underline">ติดต่อเรา</a></p>
    </div>
@endsection
