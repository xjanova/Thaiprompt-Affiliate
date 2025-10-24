@extends('layouts.guest')

@section('title', 'เข้าสู่ระบบ')

@section('content')
<div>
    <h2 class="text-2xl font-bold text-center text-gray-900 mb-6">เข้าสู่ระบบ</h2>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700">อีเมล</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('email') border-red-500 @enderror">
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <div class="mb-4">
            <label for="password" class="block text-sm font-medium text-gray-700">รหัสผ่าน</label>
            <input id="password" type="password" name="password" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('password') border-red-500 @enderror">
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Remember Me -->
        <div class="mb-4 flex items-center">
            <input id="remember_me" type="checkbox" name="remember" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
            <label for="remember_me" class="ml-2 block text-sm text-gray-900">จดจำฉันไว้</label>
        </div>

        <div class="flex items-center justify-between mb-4">
            <a href="{{ route('password.request') }}" class="text-sm text-indigo-600 hover:text-indigo-500">ลืมรหัสผ่าน?</a>
        </div>

        <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            เข้าสู่ระบบ
        </button>
    </form>

    <div class="mt-6">
        <div class="relative">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-300"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="px-2 bg-white text-gray-500">หรือ</span>
            </div>
        </div>

        <div class="mt-6">
            <p class="text-center text-sm text-gray-600">
                ยังไม่มีบัญชี?
                <a href="{{ route('register') }}" class="font-medium text-indigo-600 hover:text-indigo-500">สมัครสมาชิก</a>
            </p>
        </div>
    </div>
</div>
@endsection
