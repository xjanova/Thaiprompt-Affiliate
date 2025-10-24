@extends('layouts.guest')

@section('title', 'สมัครสมาชิก')

@section('content')
<div>
    <h2 class="text-2xl font-bold text-center text-gray-900 mb-6">สมัครสมาชิก</h2>

    @if(isset($referralCode))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-md">
            <p class="text-sm text-green-800">
                <strong>รหัสแนะนำ:</strong> {{ $referralCode }}
                @if(isset($sponsor))
                    <br><strong>ผู้แนะนำ:</strong> {{ $sponsor->name }}
                @endif
            </p>
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div class="mb-4">
            <label for="name" class="block text-sm font-medium text-gray-700">ชื่อ-นามสกุล</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('name') border-red-500 @enderror">
            @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Email -->
        <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700">อีเมล</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('email') border-red-500 @enderror">
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Phone -->
        <div class="mb-4">
            <label for="phone" class="block text-sm font-medium text-gray-700">เบอร์โทรศัพท์</label>
            <input id="phone" type="text" name="phone" value="{{ old('phone') }}"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('phone') border-red-500 @enderror">
            @error('phone')
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

        <!-- Confirm Password -->
        <div class="mb-4">
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">ยืนยันรหัสผ่าน</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>

        <!-- Referral Code -->
        <div class="mb-4">
            <label for="referral_code" class="block text-sm font-medium text-gray-700">รหัสแนะนำ (ถ้ามี)</label>
            <input id="referral_code" type="text" name="referral_code" value="{{ old('referral_code', $referralCode ?? '') }}"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('referral_code') border-red-500 @enderror">
            @error('referral_code')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Terms and Conditions -->
        <div class="mb-4">
            <label class="flex items-start">
                <input type="checkbox" name="terms" required class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 mt-1">
                <span class="ml-2 text-sm text-gray-600">
                    ฉันยอมรับ <a href="{{ url('/terms') }}" class="text-indigo-600 hover:text-indigo-500">เงื่อนไขการใช้งาน</a>
                    และ <a href="{{ url('/privacy') }}" class="text-indigo-600 hover:text-indigo-500">นโยบายความเป็นส่วนตัว</a>
                </span>
            </label>
            @error('terms')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            สมัครสมาชิก
        </button>
    </form>

    <div class="mt-6">
        <p class="text-center text-sm text-gray-600">
            มีบัญชีอยู่แล้ว?
            <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-500">เข้าสู่ระบบ</a>
        </p>
    </div>
</div>
@endsection
