@extends('layouts.admin')

@section('title', 'สร้างรางวัลใหม่')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-5xl">
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
            สร้างรางวัลการสมัครสมาชิกใหม่
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
            กำหนดรางวัลที่ผู้ใช้จะได้รับเมื่อสมัครสมาชิกผ่าน LINE OA
        </p>
    </div>

    {{-- Form --}}
    <form action="{{ route('admin.line-membership-signup.rewards.store') }}" method="POST">
        @csrf
        @include('admin.line-membership-signup.rewards._form')
    </form>
</div>
@endsection
