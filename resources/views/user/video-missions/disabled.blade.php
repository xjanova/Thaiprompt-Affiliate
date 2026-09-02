@extends('layouts.user-v4')

@section('title', 'ระบบปิดให้บริการ')

@section('content')
<div class="max-w-lg mx-auto px-4 py-20 text-center">
    <div class="text-8xl mb-6">🔒</div>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
        ระบบภารกิจดูคลิปปิดให้บริการชั่วคราว
    </h1>
    <p class="text-gray-600 dark:text-gray-400 mb-8">
        ระบบนี้ปิดให้บริการชั่วคราว กรุณากลับมาใหม่ภายหลัง
    </p>
    <a href="{{ route('user.dashboard') }}"
       class="inline-block px-6 py-3 bg-gradient-to-r from-pink-600 to-purple-600 text-white rounded-xl font-medium hover:from-pink-700 hover:to-purple-700 transition-all">
        กลับหน้าหลัก
    </a>
</div>
@endsection
