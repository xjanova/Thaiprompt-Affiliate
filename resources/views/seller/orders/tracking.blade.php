@extends('layouts.seller')

@section('title', 'จัดการการจัดส่ง #' . $order->order_number)

@section('content')
<div class="space-y-6" x-data="{ activeTab: 'tracking' }">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <a href="{{ route('seller.orders.show', $order) }}" class="text-blue-600 hover:text-blue-800 text-sm mb-2 inline-block">
                ← กลับไปรายละเอียดคำสั่งซื้อ
            </a>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-white">
                จัดการการจัดส่ง #{{ $order->order_number }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                ลูกค้า: {{ $order->user->name ?? 'ไม่ระบุ' }}
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('seller.orders.print', $order) }}" target="_blank"
               class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-medium transition">
                พิมพ์ใบส่งของ
            </a>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="flex gap-4">
            <button @click="activeTab = 'tracking'"
                    :class="activeTab === 'tracking' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="px-4 py-3 border-b-2 font-medium transition">
                ข้อมูลการจัดส่ง
            </button>
            <button @click="activeTab = 'history'"
                    :class="activeTab === 'history' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="px-4 py-3 border-b-2 font-medium transition">
                ประวัติการจัดส่ง
            </button>
            <button @click="activeTab = 'chat'"
                    :class="activeTab === 'chat' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="px-4 py-3 border-b-2 font-medium transition relative">
                แชทกับลูกค้า
                @if($order->messages()->where('is_read', false)->where('sender_type', 'customer')->count() > 0)
                    <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                        {{ $order->messages()->where('is_read', false)->where('sender_type', 'customer')->count() }}
                    </span>
                @endif
            </button>
        </nav>
    </div>

    {{-- Tracking Tab --}}
    <div x-show="activeTab === 'tracking'" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Tracking Form --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">
                อัพเดทข้อมูลการจัดส่ง
            </h3>

            <form action="{{ route('seller.orders.add-tracking', $order) }}" method="POST" class="space-y-4">
                @csrf

                {{-- Shipping Provider --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        บริษัทขนส่ง <span class="text-red-500">*</span>
                    </label>
                    <select name="shipping_provider_id" required
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        <option value="">เลือกบริษัทขนส่ง</option>
                        @foreach($shippingProviders as $provider)
                            <option value="{{ $provider->id }}"
                                    {{ $order->shipping_provider_id == $provider->id ? 'selected' : '' }}>
                                {{ $provider->name }} ({{ $provider->name_en }})
                            </option>
                        @endforeach
                    </select>
                    @error('shipping_provider_id')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Tracking Number --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        หมายเลขพัสดุ <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="tracking_number" value="{{ $order->tracking_number }}" required
                           placeholder="เช่น TH123456789"
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    @error('tracking_number')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Estimated Delivery --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        วันที่คาดว่าจะถึง
                    </label>
                    <input type="date" name="estimated_delivery_at"
                           value="{{ $order->estimated_delivery_at ? $order->estimated_delivery_at->format('Y-m-d') : '' }}"
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                </div>

                <button type="submit"
                        class="w-full px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                    บันทึกข้อมูลการจัดส่ง
                </button>
            </form>
        </div>

        {{-- Current Status --}}
        <div class="space-y-6">
            {{-- Order Status --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">
                    สถานะปัจจุบัน
                </h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">สถานะ:</span>
                        <span class="px-3 py-1 rounded-full text-sm font-medium
                            @if($order->status === 'delivered') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                            @elseif($order->status === 'shipped') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                            @elseif($order->status === 'cancelled') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                            @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                            @endif">
                            {{ $order->status }}
                        </span>
                    </div>

                    @if($order->tracking_number)
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">หมายเลขพัสดุ:</span>
                            <span class="font-mono font-semibold text-gray-800 dark:text-white">{{ $order->tracking_number }}</span>
                        </div>
                    @endif

                    @if($order->shippingProvider)
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">ขนส่ง:</span>
                            <span class="text-gray-800 dark:text-white">{{ $order->shippingProvider->name }}</span>
                        </div>

                        @if($order->tracking_number && $order->shippingProvider->getTrackingLink($order->tracking_number))
                            <a href="{{ $order->shippingProvider->getTrackingLink($order->tracking_number) }}"
                               target="_blank"
                               class="block mt-4 px-4 py-2 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-200 text-center rounded-lg hover:bg-blue-200 dark:hover:bg-blue-800 transition">
                                ติดตามพัสดุ
                            </a>
                        @endif
                    @endif

                    @if($order->shipped_at)
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">จัดส่งเมื่อ:</span>
                            <span class="text-gray-800 dark:text-white">{{ $order->shipped_at->format('d/m/Y H:i') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Shipping Address --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">
                    ที่อยู่จัดส่ง
                </h3>
                <div class="text-gray-700 dark:text-gray-300 space-y-1">
                    <p class="font-semibold">{{ $order->shipping_name }}</p>
                    <p>{{ $order->shipping_phone }}</p>
                    <p>{{ $order->shipping_address }}</p>
                    <p>{{ $order->shipping_subdistrict }} {{ $order->shipping_district }}</p>
                    <p>{{ $order->shipping_province }} {{ $order->shipping_postal_code }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- History Tab --}}
    <div x-show="activeTab === 'history'" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Add History Form --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">
                เพิ่มประวัติการจัดส่ง
            </h3>

            <form action="{{ route('seller.orders.tracking.history', $order) }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        สถานะ <span class="text-red-500">*</span>
                    </label>
                    <select name="status" required
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        <option value="pending">รอดำเนินการ</option>
                        <option value="processing">กำลังเตรียมสินค้า</option>
                        <option value="shipped">จัดส่งแล้ว</option>
                        <option value="in_transit">กำลังจัดส่ง</option>
                        <option value="out_for_delivery">กำลังนำส่ง</option>
                        <option value="delivered">ส่งถึงแล้ว</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        รายละเอียด <span class="text-red-500">*</span>
                    </label>
                    <textarea name="description" rows="3" required
                              placeholder="เช่น สินค้าถึงศูนย์กระจายสินค้า กรุงเทพฯ"
                              class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        สถานที่
                    </label>
                    <input type="text" name="location"
                           placeholder="เช่น ศูนย์กระจายสินค้า กรุงเทพฯ"
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                </div>

                <button type="submit"
                        class="w-full px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
                    เพิ่มประวัติ
                </button>
            </form>
        </div>

        {{-- History Timeline --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">
                ประวัติการจัดส่ง
            </h3>

            @if($order->trackingHistory->isEmpty())
                <p class="text-gray-500 dark:text-gray-400 text-center py-8">ยังไม่มีประวัติ</p>
            @else
                <div class="space-y-4">
                    @foreach($order->trackingHistory as $history)
                        <div class="flex gap-4">
                            <div class="flex flex-col items-center">
                                <div class="w-3 h-3 rounded-full {{ $loop->first ? 'bg-blue-500' : 'bg-gray-300 dark:bg-gray-600' }}"></div>
                                @if(!$loop->last)
                                    <div class="w-0.5 flex-1 bg-gray-200 dark:bg-gray-700"></div>
                                @endif
                            </div>
                            <div class="flex-1 pb-4">
                                <p class="font-medium text-gray-800 dark:text-white">{{ $history->description }}</p>
                                @if($history->location)
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $history->location }}</p>
                                @endif
                                <p class="text-xs text-gray-400 mt-1">
                                    {{ $history->created_at->format('d/m/Y H:i') }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Chat Tab --}}
    <div x-show="activeTab === 'chat'" class="bg-white dark:bg-gray-800 rounded-xl shadow p-6"
         x-data="{ loading: false }"
         x-init="
            fetch('{{ route('seller.orders.messages.read', $order) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            });
         ">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">
            แชทกับลูกค้า
        </h3>

        {{-- Messages --}}
        <div class="h-96 overflow-y-auto space-y-4 mb-4 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
            @forelse($order->messages->reverse() as $message)
                <div class="{{ $message->sender_type === 'seller' ? 'flex justify-end' : 'flex justify-start' }}">
                    <div class="max-w-[70%] {{ $message->sender_type === 'seller' ? 'bg-blue-500 text-white' : ($message->is_system_message ? 'bg-gray-200 dark:bg-gray-700' : 'bg-white dark:bg-gray-800 border dark:border-gray-700') }} rounded-2xl px-4 py-3 shadow">
                        @if($message->sender_type !== 'seller')
                            <p class="text-xs {{ $message->is_system_message ? 'text-gray-500' : 'text-gray-500 dark:text-gray-400' }} mb-1">
                                {{ $message->sender_name }}
                            </p>
                        @endif
                        <p class="{{ $message->sender_type === 'seller' ? 'text-white' : 'text-gray-800 dark:text-white' }}">
                            {{ $message->message }}
                        </p>
                        <p class="text-xs {{ $message->sender_type === 'seller' ? 'text-blue-100' : 'text-gray-400' }} mt-1 text-right">
                            {{ $message->created_at->format('H:i') }}
                        </p>
                    </div>
                </div>
            @empty
                <div class="flex items-center justify-center h-full">
                    <div class="text-center text-gray-500 dark:text-gray-400">
                        <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <p>ยังไม่มีข้อความ</p>
                    </div>
                </div>
            @endforelse
        </div>

        {{-- Send Message Form --}}
        <form action="{{ route('seller.orders.messages.send', $order) }}" method="POST" class="flex gap-3">
            @csrf
            <input type="text" name="message" required
                   placeholder="พิมพ์ข้อความ..."
                   class="flex-1 px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
            <button type="submit"
                    class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                ส่ง
            </button>
        </form>
    </div>
</div>
@endsection
