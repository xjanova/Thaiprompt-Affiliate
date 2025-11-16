@extends('layouts.auth-arrow-x')

@section('title', 'ลืมรหัสผ่าน')
@section('subtitle', 'ขอรีเซ็ตรหัสผ่าน')

@section('content')
    {{-- Header --}}
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full mb-4 shadow-lg">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
            ลืมรหัสผ่าน?
        </h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            ไม่ต้องกังวล! กรอกอีเมลของคุณแล้วเราจะส่งลิงก์รีเซ็ตรหัสผ่านให้
        </p>
    </div>

    {{-- Success Message --}}
    @if (session('status'))
        <div class="mb-6 p-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-l-4 border-green-500 rounded-lg" x-data="{ show: true }" x-show="show" x-transition>
            <div class="flex items-start">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <div class="flex-1">
                    <p class="font-medium text-green-800 dark:text-green-200">
                        {{ session('status') }}
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

    {{-- Error Messages --}}
    @if ($errors->any())
        <div class="mb-6 p-4 bg-gradient-to-r from-red-50 to-pink-50 dark:from-red-900/20 dark:to-pink-900/20 border-l-4 border-red-500 rounded-lg" x-data="{ show: true }" x-show="show" x-transition>
            <div class="flex items-start">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <div class="flex-1">
                    <ul class="list-disc list-inside space-y-1 text-sm font-medium text-red-800 dark:text-red-200">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <button @click="show = false" class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-200">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>
    @endif

    {{-- Form --}}
    <form method="POST" action="{{ route('password.email') }}" x-data="{ loading: false }" @submit="loading = true">
        @csrf

        <div class="space-y-6">
            {{-- Email Input --}}
            <div>
                <label for="email" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-2 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                        </svg>
                        อีเมลของคุณ
                    </span>
                </label>
                <input type="email"
                       name="email"
                       id="email"
                       value="{{ old('email') }}"
                       class="input-3d w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl
                              focus:ring-2 focus:ring-purple-500 focus:border-purple-500
                              dark:bg-gray-800 dark:text-white
                              transition-all duration-300
                              @error('email') border-red-500 @enderror"
                       placeholder="example@email.com"
                       required
                       autofocus>
                @error('email')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Submit Button --}}
            <button type="submit"
                    :disabled="loading"
                    class="btn-shimmer w-full py-4 px-6 bg-gradient-to-r from-purple-600 via-pink-600 to-orange-600
                           hover:from-purple-700 hover:via-pink-700 hover:to-orange-700
                           dark:from-purple-500 dark:via-pink-500 dark:to-orange-500
                           dark:hover:from-purple-600 dark:hover:via-pink-600 dark:hover:to-orange-600
                           text-white font-bold text-lg rounded-xl
                           shadow-lg hover:shadow-2xl
                           transform hover:-translate-y-1
                           transition-all duration-300
                           disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none
                           focus:ring-4 focus:ring-purple-500/50 focus:outline-none">
                <span x-show="!loading" class="flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    ส่งลิงก์รีเซ็ตรหัสผ่าน
                </span>
                <span x-show="loading" class="flex items-center justify-center">
                    <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    กำลังส่ง...
                </span>
            </button>
        </div>
    </form>

    {{-- Info Box --}}
    <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div class="flex-1">
                <p class="text-sm font-medium text-blue-800 dark:text-blue-200">
                    คำแนะนำ
                </p>
                <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">
                    ลิงก์รีเซ็ตรหัสผ่านจะถูกส่งไปยังอีเมลของคุณ กรุณาตรวจสอบกล่องจดหมายและโฟลเดอร์สแปม
                </p>
            </div>
        </div>
    </div>
@endsection

@section('footer-links')
    <div class="space-y-3">
        <a href="{{ route('login') }}"
           class="inline-flex items-center text-white/90 dark:text-gray-300 hover:text-white dark:hover:text-white font-semibold transition duration-300 group">
            <svg class="w-5 h-5 mr-2 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
            </svg>
            กลับไปหน้าเข้าสู่ระบบ
        </a>
        <span class="mx-3 text-white/50">•</span>
        <a href="{{ route('register') }}"
           class="inline-flex items-center text-white/90 dark:text-gray-300 hover:text-white dark:hover:text-white font-semibold transition duration-300">
            ยังไม่มีบัญชี? สมัครสมาชิก
        </a>
    </div>
@endsection
