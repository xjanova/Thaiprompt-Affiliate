{{--
    หน้าแสดงข้อความที่ยังไม่อ่าน
    แสดงเฉพาะ conversations ที่มีข้อความ unread
--}}
@extends('layouts.seller')

@section('title', 'ข้อความยังไม่อ่าน')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-white">🔔 ข้อความยังไม่อ่าน</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">ข้อความจากลูกค้าที่รอการตอบกลับ</p>
        </div>
        <a href="{{ route('seller.messages.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors">
            <span>←</span>
            <span>ดูข้อความทั้งหมด</span>
        </a>
    </div>

    {{-- Alert Card --}}
    @if($stats['unread_conversations'] > 0)
        <div class="bg-gradient-to-r from-red-500 to-orange-500 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center gap-4">
                <div class="text-5xl animate-bounce">📩</div>
                <div>
                    <h3 class="text-xl font-bold">คุณมี {{ $stats['unread_conversations'] }} แชทที่รอตอบ</h3>
                    <p class="text-red-100 mt-1">มีข้อความที่ยังไม่ได้อ่านทั้งหมด {{ $stats['unread_messages'] }} ข้อความ</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Conversations List --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-red-50 dark:bg-red-900/20">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 bg-red-500 rounded-full animate-pulse"></span>
                <h2 class="text-lg font-semibold text-red-700 dark:text-red-300">แชทที่รอตอบกลับ</h2>
            </div>
        </div>

        @if($conversations->count() > 0)
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($conversations as $order)
                    @php
                        $latestMessage = $order->messages->first();
                        $sellerItems = $order->items->where('seller_id', auth()->id());
                    @endphp
                    <a href="{{ route('seller.orders.tracking', $order->id) }}?tab=chat"
                       class="block hover:bg-red-50 dark:hover:bg-gray-700 transition-colors border-l-4 border-red-500">
                        <div class="px-6 py-4 flex items-start gap-4">
                            {{-- Avatar with Notification --}}
                            <div class="flex-shrink-0 relative">
                                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-red-400 to-red-600 flex items-center justify-center text-white font-bold text-lg">
                                    {{ strtoupper(substr($order->user->name ?? 'U', 0, 1)) }}
                                </div>
                                <div class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full flex items-center justify-center border-2 border-white dark:border-gray-800">
                                    <span class="text-white text-xs font-bold">!</span>
                                </div>
                            </div>

                            {{-- Content --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ $order->user->name }}</span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">•</span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">#{{ $order->order_number }}</span>
                                    <span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full font-medium">ใหม่</span>
                                </div>

                                {{-- Latest Message --}}
                                @if($latestMessage)
                                    <p class="text-sm text-gray-800 dark:text-gray-200 mt-1 font-medium">
                                        {{ Str::limit($latestMessage->message, 80) }}
                                    </p>
                                @endif

                                {{-- Products --}}
                                <div class="flex items-center gap-2 mt-2">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        📦 {{ $sellerItems->count() }} สินค้า
                                    </span>
                                    <span class="text-xs px-2 py-0.5 rounded-full {{ $order->status === 'delivered' ? 'bg-green-100 text-green-700' : ($order->status === 'shipped' ? 'bg-purple-100 text-purple-700' : 'bg-yellow-100 text-yellow-700') }}">
                                        {{ $order->status_label ?? $order->status }}
                                    </span>
                                </div>
                            </div>

                            {{-- Time & Action --}}
                            <div class="flex-shrink-0 text-right">
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $latestMessage ? $latestMessage->created_at->diffForHumans() : $order->updated_at->diffForHumans() }}
                                </span>
                                <div class="mt-2">
                                    <span class="inline-flex items-center gap-1 text-red-600 dark:text-red-400 text-sm font-medium">
                                        ตอบกลับ
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $conversations->links() }}
            </div>
        @else
            <div class="text-center py-16">
                <div class="text-7xl mb-4">✅</div>
                <p class="text-xl text-gray-500 dark:text-gray-400">ไม่มีข้อความที่ยังไม่ได้อ่าน</p>
                <p class="text-gray-400 dark:text-gray-500 mt-2">คุณตอบกลับลูกค้าครบทุกข้อความแล้ว!</p>
                <a href="{{ route('seller.messages.index') }}"
                   class="inline-block mt-4 px-6 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors">
                    ดูข้อความทั้งหมด
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
