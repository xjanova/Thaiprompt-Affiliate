@extends('layouts.guest')

@section('title', 'ลืมรหัสผ่าน')

@section('content')
<div>
    <h2 class="text-2xl font-bold text-center text-gray-900 mb-6">ลืมรหัสผ่าน</h2>

    <div class="mb-4 text-sm text-gray-600">
        กรอกอีเมลของคุณและเราจะส่งลิงก์สำหรับรีเซ็ตรหัสผ่านให้คุณ
    </div>

    @if (session('status'))
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
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

        <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            ส่งลิงก์รีเซ็ตรหัสผ่าน
        </button>
    </form>

    <div class="mt-6">
        <p class="text-center text-sm text-gray-600">
            <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-500">กลับไปหน้าเข้าสู่ระบบ</a>
        </p>
    </div>
</div>
@endsection
