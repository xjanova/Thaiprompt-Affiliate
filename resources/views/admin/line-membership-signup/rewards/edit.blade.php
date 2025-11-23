@extends('layouts.admin')

@section('title', 'แก้ไขรางวัล')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-5xl">
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
            แก้ไขรางวัล: {{ $reward->name }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
            อัพเดทข้อมูลรางวัลการสมัครสมาชิก
        </p>
    </div>

    {{-- Form --}}
    <form action="{{ route('admin.line-membership-signup.rewards.update', $reward) }}" method="POST">
        @csrf
        @method('PUT')
        @include('admin.line-membership-signup.rewards._form')
    </form>
</div>
@endsection
