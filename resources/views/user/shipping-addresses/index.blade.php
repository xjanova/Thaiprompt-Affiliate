@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900/50 py-8">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="mb-8" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2 text-sm">
                <li>
                    <a href="{{ route('home') }}" class="text-gray-500 dark:text-gray-400 hover:text-indigo-600 transition">
                        หน้าแรก
                    </a>
                </li>
                <li class="text-gray-400">/</li>
                <li class="text-gray-700 dark:text-gray-300 font-medium">ที่อยู่จัดส่ง</li>
            </ol>
        </nav>

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
            <div class="mb-4 sm:mb-0">
                <h1 class="text-3xl font-black text-gray-900 dark:text-white mb-2">📍 ที่อยู่จัดส่ง</h1>
                <p class="text-gray-600 dark:text-gray-400">จัดการที่อยู่จัดส่งของคุณ</p>
            </div>
            <a href="{{ route('shipping-addresses.create') }}"
               class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                + เพิ่มที่อยู่ใหม่
            </a>
        </div>

        <!-- Success Message -->
        @if(session('success'))
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            </div>
        </div>
        @endif

        <!-- Error Message -->
        @if(session('error'))
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                </div>
            </div>
        </div>
        @endif

        <!-- Address List -->
        @if($addresses->count() > 0)
            <div class="space-y-4">
                @foreach($addresses as $address)
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 hover:shadow-xl transition-shadow">
                    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between">
                        <!-- Address Info -->
                        <div class="flex-1 mb-4 lg:mb-0">
                            <div class="flex items-center gap-2 mb-3">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $address->recipient_name }}</h3>
                                @if($address->is_default)
                                <span class="px-3 py-1 bg-indigo-100 text-indigo-700 text-xs font-semibold rounded-full">
                                    ค่าเริ่มต้น
                                </span>
                                @endif
                            </div>

                            <div class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                                <p class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                    <span>{{ $address->phone_number }}</span>
                                </p>
                                <p class="flex items-start gap-2">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <span>{{ $address->full_address }}</span>
                                </p>
                                @if($address->notes)
                                <p class="flex items-start gap-2 text-gray-600 dark:text-gray-400 italic">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                    </svg>
                                    <span>{{ $address->notes }}</span>
                                </p>
                                @endif
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-wrap gap-2 lg:ml-6">
                            @if(!$address->is_default)
                            <form action="{{ route('shipping-addresses.set-default', $address->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit"
                                        class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 text-gray-700 dark:text-gray-300 font-medium rounded-lg transition text-sm">
                                    ตั้งเป็นค่าเริ่มต้น
                                </button>
                            </form>
                            @endif

                            <a href="{{ route('shipping-addresses.edit', $address->id) }}"
                               class="px-4 py-2 bg-indigo-100 hover:bg-indigo-200 text-indigo-700 font-medium rounded-lg transition text-sm">
                                แก้ไข
                            </a>

                            <form action="{{ route('shipping-addresses.destroy', $address->id) }}"
                                  method="POST"
                                  class="inline"
                                  onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบที่อยู่นี้?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="px-4 py-2 bg-red-100 hover:bg-red-200 text-red-700 font-medium rounded-lg transition text-sm">
                                    ลบ
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <!-- Empty State -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-12 text-center">
                <div class="text-6xl mb-4">📭</div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ยังไม่มีที่อยู่จัดส่ง</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">เพิ่มที่อยู่จัดส่งเพื่อใช้ในการสั่งซื้อสินค้า</p>
                <a href="{{ route('shipping-addresses.create') }}"
                   class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                    + เพิ่มที่อยู่จัดส่ง
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
