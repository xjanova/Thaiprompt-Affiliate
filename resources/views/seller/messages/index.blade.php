{{--
    หน้าแสดงข้อความทั้งหมดจากลูกค้า
    แสดงรายการ conversations ที่มีข้อความ พร้อมลิงก์ไปหน้า tracking/chat
--}}
@extends('layouts.seller')

@section('title', 'แชทกับลูกค้า')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-white">💬 แชทกับลูกค้า</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">จัดการข้อความจากลูกค้าทั้งหมด</p>
        </div>
        <a href="{{ route('seller.messages.unread') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors shadow-lg">
            <span>🔔</span>
            <span>ข้อความยังไม่อ่าน</span>
            @if($stats['unread_conversations'] > 0)
                <span class="bg-white text-red-500 px-2 py-0.5 rounded-full text-xs font-bold">{{ $stats['unread_conversations'] }}</span>
            @endif
        </a>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-blue-100">แชททั้งหมด</p>
                    <p class="text-3xl font-bold">{{ number_format($stats['total_conversations']) }}</p>
                </div>
                <div class="text-5xl opacity-80">💬</div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-red-100">ยังไม่อ่าน</p>
                    <p class="text-3xl font-bold">{{ number_format($stats['unread_conversations']) }}</p>
                </div>
                <div class="text-5xl opacity-80">📩</div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-green-100">ข้อความวันนี้</p>
                    <p class="text-3xl font-bold">{{ number_format($stats['messages_today']) }}</p>
                </div>
                <div class="text-5xl opacity-80">📅</div>
            </div>
        </div>
    </div>

    {{-- Conversations List --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white">รายการแชท</h2>
        </div>

        @if($conversations->count() > 0)
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($conversations as $order)
                    @php
                        $latestMessage = $order->messages->first();
                        $sellerItems = $order->items->where('seller_id', auth()->id());
                    @endphp
                    <a href="{{ route('seller.orders.tracking', $order->id) }}?tab=chat"
                       class="block hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <div class="px-6 py-4 flex items-start gap-4">
                            {{-- Avatar --}}
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-bold text-lg">
                                    {{ strtoupper(substr($order->user->name ?? 'U', 0, 1)) }}
                                </div>
                            </div>

                            {{-- Content --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $order->user->name }}</span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">•</span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">#{{ $order->order_number }}</span>
                                    @if($order->has_unread_messages)
                                        <span class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                                    @endif
                                </div>

                                {{-- Latest Message --}}
                                @if($latestMessage)
                                    <p class="text-sm text-gray-600 dark:text-gray-400 truncate mt-1">
                                        @if($latestMessage->sender_type === 'seller')
                                            <span class="text-blue-500">คุณ: </span>
                                        @elseif($latestMessage->is_system_message)
                                            <span class="text-gray-400">ระบบ: </span>
                                        @endif
                                        {{ Str::limit($latestMessage->message, 60) }}
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

                            {{-- Time --}}
                            <div class="flex-shrink-0 text-right">
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $latestMessage ? $latestMessage->created_at->diffForHumans() : $order->updated_at->diffForHumans() }}
                                </span>
                                @if($order->has_unread_messages)
                                    <div class="mt-2">
                                        <span class="inline-flex items-center justify-center w-6 h-6 bg-red-500 text-white text-xs font-bold rounded-full">
                                            !
                                        </span>
                                    </div>
                                @endif
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
                <div class="text-7xl mb-4">💬</div>
                <p class="text-xl text-gray-500 dark:text-gray-400">ยังไม่มีข้อความจากลูกค้า</p>
                <p class="text-gray-400 dark:text-gray-500 mt-2">เมื่อลูกค้าสั่งซื้อและส่งข้อความ จะแสดงที่นี่</p>
            </div>
        @endif
    </div>
</div>
@endsection
