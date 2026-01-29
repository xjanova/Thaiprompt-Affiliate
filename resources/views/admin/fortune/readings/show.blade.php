@extends('layouts.admin')

@section('title', 'รายละเอียดการทำนาย #' . $reading->id)

@section('content')
<div class="container mx-auto px-4 py-8 max-w-5xl">
    {{-- Header --}}
    <div class="mb-8">
        <a href="{{ route('admin.fortune.readings.index') }}" 
           class="text-blue-600 hover:text-blue-800 dark:text-blue-400 mb-4 inline-block">
            ← กลับไปรายการ
        </a>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            รายละเอียดการทำนาย #{{ $reading->id }}
        </h1>
    </div>

    {{-- User Info --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">ข้อมูลผู้ใช้</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <span class="text-gray-600 dark:text-gray-400">ชื่อ:</span>
                <span class="ml-2 text-gray-900 dark:text-white font-medium">{{ $reading->facebook_user_name ?? 'ไม่ระบุ' }}</span>
            </div>
            <div>
                <span class="text-gray-600 dark:text-gray-400">Facebook ID:</span>
                <span class="ml-2 text-gray-900 dark:text-white font-mono text-sm">{{ $reading->facebook_user_id }}</span>
            </div>
            <div>
                <span class="text-gray-600 dark:text-gray-400">วันที่:</span>
                <span class="ml-2 text-gray-900 dark:text-white">{{ $reading->created_at->format('d/m/Y H:i:s') }}</span>
            </div>
            <div>
                <span class="text-gray-600 dark:text-gray-400">ประเภท:</span>
                <span class="ml-2">
                    <span class="px-3 py-1 text-xs font-semibold rounded-full {{ $reading->response_type === 'comment' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                        {{ $reading->response_type === 'comment' ? 'Comment' : 'Private Message' }}
                    </span>
                </span>
            </div>
        </div>
    </div>

    {{-- Questions --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">คำถาม</h3>
        <ol class="list-decimal list-inside space-y-2">
            @foreach($reading->questions as $question)
                <li class="text-gray-900 dark:text-white">{{ $question }}</li>
            @endforeach
        </ol>
    </div>

    {{-- AI Response --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">คำทำนาย</h3>
            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                {{ strtoupper($reading->ai_provider) }} / {{ $reading->ai_model }}
            </span>
        </div>
        <div class="prose dark:prose-invert max-w-none">
            <div class="whitespace-pre-wrap text-gray-900 dark:text-white">{{ $reading->ai_response }}</div>
        </div>
        @if($reading->tokens_used)
            <div class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                Tokens ที่ใช้: {{ number_format($reading->tokens_used) }}
            </div>
        @endif
    </div>

    {{-- Payment Info --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">ข้อมูลการชำระเงิน</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <span class="text-gray-600 dark:text-gray-400">สถานะ:</span>
                <span class="ml-2">
                    @if($reading->is_paid)
                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">ชำระแล้ว</span>
                    @else
                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">ฟรี</span>
                    @endif
                </span>
            </div>
            <div>
                <span class="text-gray-600 dark:text-gray-400">จำนวนเงิน:</span>
                <span class="ml-2 text-gray-900 dark:text-white font-bold">฿{{ number_format($reading->amount_paid, 2) }}</span>
            </div>
            <div>
                <span class="text-gray-600 dark:text-gray-400">วันที่ชำระ:</span>
                <span class="ml-2 text-gray-900 dark:text-white">{{ $reading->paid_at ? $reading->paid_at->format('d/m/Y H:i') : '-' }}</span>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">สถิติ</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <span class="text-gray-600 dark:text-gray-400">จำนวนการดู:</span>
                <span class="ml-2 text-gray-900 dark:text-white font-bold">{{ number_format($reading->view_count) }}</span>
            </div>
            <div>
                <span class="text-gray-600 dark:text-gray-400">คะแนน:</span>
                <span class="ml-2 text-gray-900 dark:text-white">{{ $reading->rating ? $reading->getRatingStars() : '-' }}</span>
            </div>
            <div>
                <span class="text-gray-600 dark:text-gray-400">ตอบกลับเมื่อ:</span>
                <span class="ml-2 text-gray-900 dark:text-white">{{ $reading->responded_at ? $reading->responded_at->format('d/m/Y H:i') : 'ยังไม่ตอบ' }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
