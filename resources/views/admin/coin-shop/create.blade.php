{{--
    หน้าเพิ่มสินค้าใหม่
--}}

@extends('layouts.admin')

@section('title', $pageTitle ?? 'เพิ่มสินค้าใหม่')

@section('content')
<div class="container-fluid px-4 py-6">
    {{-- Header --}}
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('admin.coin-shop.index') }}" class="p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">เพิ่มสินค้าใหม่</h1>
    </div>

    {{-- Form --}}
    <form action="{{ route('admin.coin-shop.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @include('admin.coin-shop._form')

        <div class="flex justify-end gap-4 mt-6">
            <a href="{{ route('admin.coin-shop.index') }}"
               class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                ยกเลิก
            </a>
            <button type="submit"
                    class="px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition">
                บันทึกสินค้า
            </button>
        </div>
    </form>
</div>
@endsection
