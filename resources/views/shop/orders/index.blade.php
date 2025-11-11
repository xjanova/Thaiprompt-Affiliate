@extends('layouts.app')

@section('title', 'คำสั่งซื้อของฉัน')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-orange-50/20 to-amber-50/30 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">

    <!-- Hero Header -->
    <div class="relative overflow-hidden bg-gradient-to-r from-orange-600 via-amber-600 to-yellow-600 dark:from-orange-700 dark:via-amber-700 dark:to-yellow-700">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 40px 40px;"></div>
        </div>

        <div class="container mx-auto px-4 py-12 relative z-10">
            <div class="max-w-4xl">
                <div class="inline-flex items-center gap-2 bg-white/20 backdrop-blur-lg px-4 py-2 rounded-full mb-4 border border-white/30">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/>
                    </svg>
                    <span class="font-semibold text-white">การจัดการคำสั่งซื้อ</span>
                </div>

                <h1 class="text-4xl md:text-5xl font-black text-white mb-3 tracking-tight drop-shadow-lg">
                    คำสั่งซื้อของฉัน
                </h1>
                <p class="text-xl text-orange-100 font-medium">
                    ติดตามและจัดการคำสั่งซื้อของคุณได้ง่ายๆ ในที่เดียว
                </p>
            </div>
        </div>

        <!-- Wave Divider -->
        <div class="absolute bottom-0 left-0 right-0">
            <svg viewBox="0 0 1440 48" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full dark:hidden">
                <path d="M0 48h1440V24C1440 24 1200 0 720 0S0 24 0 24v24z" fill="rgb(249, 250, 251)"/>
            </svg>
            <svg viewBox="0 0 1440 48" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full hidden dark:block">
                <path d="M0 48h1440V24C1440 24 1200 0 720 0S0 24 0 24v24z" fill="rgb(17, 24, 39)"/>
            </svg>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8 -mt-6 relative z-10">

        <!-- Status Filter Tabs -->
        <div class="mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-2 border border-gray-100 dark:border-gray-700">
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('orders.index') }}"
                       class="flex-1 min-w-[120px] px-4 py-3 rounded-xl font-bold text-center transition-all {{ !request('status') ? 'bg-gradient-to-r from-orange-600 to-amber-600 text-white shadow-lg' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                        <div class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                            </svg>
                            <span>ทั้งหมด</span>
                            <span class="px-2 py-1 bg-white/20 dark:bg-gray-900/40 rounded-lg text-xs">{{ $statusCounts['all'] }}</span>
                        </div>
                    </a>

                    <a href="{{ route('orders.index', ['status' => 'pending']) }}"
                       class="flex-1 min-w-[120px] px-4 py-3 rounded-xl font-bold text-center transition-all {{ request('status') === 'pending' ? 'bg-gradient-to-r from-yellow-500 to-amber-500 text-white shadow-lg' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                        <div class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                            </svg>
                            <span>รอชำระ</span>
                            <span class="px-2 py-1 bg-white/20 dark:bg-gray-900/40 rounded-lg text-xs">{{ $statusCounts['pending'] }}</span>
                        </div>
                    </a>

                    <a href="{{ route('orders.index', ['status' => 'processing']) }}"
                       class="flex-1 min-w-[120px] px-4 py-3 rounded-xl font-bold text-center transition-all {{ request('status') === 'processing' ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                        <div class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm9.707 5.707a1 1 0 00-1.414-1.414L9 12.586l-1.293-1.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>เตรียมสินค้า</span>
                            <span class="px-2 py-1 bg-white/20 dark:bg-gray-900/40 rounded-lg text-xs">{{ $statusCounts['processing'] }}</span>
                        </div>
                    </a>

                    <a href="{{ route('orders.index', ['status' => 'shipped']) }}"
                       class="flex-1 min-w-[120px] px-4 py-3 rounded-xl font-bold text-center transition-all {{ request('status') === 'shipped' ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-lg' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                        <div class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                                <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>
                            </svg>
                            <span>กำลังส่ง</span>
                            <span class="px-2 py-1 bg-white/20 dark:bg-gray-900/40 rounded-lg text-xs">{{ $statusCounts['shipped'] }}</span>
                        </div>
                    </a>

                    <a href="{{ route('orders.index', ['status' => 'completed']) }}"
                       class="flex-1 min-w-[120px] px-4 py-3 rounded-xl font-bold text-center transition-all {{ request('status') === 'completed' ? 'bg-gradient-to-r from-green-600 to-emerald-600 text-white shadow-lg' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                        <div class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>สำเร็จ</span>
                            <span class="px-2 py-1 bg-white/20 dark:bg-gray-900/40 rounded-lg text-xs">{{ $statusCounts['completed'] }}</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Orders List -->
        @if($orders->count() > 0)
        <div class="space-y-4 mb-8">
            @foreach($orders as $order)
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-xl transition-all">
                <!-- Order Header -->
                <div class="bg-gradient-to-r from-gray-50 to-white dark:from-gray-700 dark:to-gray-800 px-6 py-4 border-b border-gray-200 dark:border-gray-600">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-orange-100 to-amber-100 dark:from-orange-800 dark:to-amber-800 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ $order->order_number }}</span>
                                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-{{ $order->status_color }}-100 text-{{ $order->status_color }}-800 dark:bg-{{ $order->status_color }}-900/30 dark:text-{{ $order->status_color }}-400">
                                        {{ $order->status_label }}
                                    </span>
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $order->created_at->format('d/m/Y H:i') }} • {{ $order->total_items }} รายการ
                                </div>
                            </div>
                        </div>

                        <div class="text-right">
                            <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">ยอดรวมทั้งหมด</div>
                            <div class="text-2xl font-black text-orange-600 dark:text-orange-400">
                                ฿{{ number_format($order->total_amount, 2) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Items Preview -->
                <div class="p-6">
                    <div class="space-y-3 mb-4">
                        @foreach($order->items->take(3) as $item)
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden flex-shrink-0">
                                @if($item->product_image)
                                    <img src="{{ asset($item->product_image) }}"
                                         alt="{{ $item->product_name }}"
                                         class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-gray-900 dark:text-gray-100 truncate">
                                    {{ $item->product_name }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $item->quantity }} x ฿{{ number_format($item->unit_price, 2) }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-gray-900 dark:text-gray-100">
                                    ฿{{ number_format($item->total, 2) }}
                                </div>
                            </div>
                        </div>
                        @endforeach

                        @if($order->items->count() > 3)
                        <div class="text-center text-sm text-gray-500 dark:text-gray-400 pt-2">
                            และอีก {{ $order->items->count() - 3 }} รายการ
                        </div>
                        @endif
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap gap-3 pt-4 border-t border-gray-200 dark:border-gray-600">
                        <a href="{{ route('orders.show', $order->id) }}"
                           class="flex-1 min-w-[150px] px-6 py-3 bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-700 hover:to-amber-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all transform hover:scale-105 flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                            </svg>
                            ดูรายละเอียด
                        </a>

                        @if($order->tracking_number)
                        <button onclick="alert('เลขพัสดุ: {{ $order->tracking_number }}')"
                                class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                                <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>
                            </svg>
                            ติดตามพัสดุ
                        </button>
                        @endif

                        @if($order->canBeCancelled())
                        <button onclick="if(confirm('คุณแน่ใจหรือไม่ว่าต้องการยกเลิกคำสั่งซื้อนี้?')) document.getElementById('cancel-{{ $order->id }}').submit()"
                                class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            ยกเลิก
                        </button>
                        <form id="cancel-{{ $order->id }}" action="{{ route('orders.cancel', $order->id) }}" method="POST" class="hidden">
                            @csrf
                            <input type="hidden" name="reason" value="ยกเลิกโดยผู้ใช้">
                        </form>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="flex justify-center">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-4 border border-gray-100 dark:border-gray-700">
                {{ $orders->links() }}
            </div>
        </div>
        @else
        <!-- Empty State -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-12 text-center border border-gray-100 dark:border-gray-700">
            <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-orange-100 to-amber-100 dark:from-orange-900/30 dark:to-amber-900/30 rounded-full mb-6">
                <svg class="w-12 h-12 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-3">ยังไม่มีคำสั่งซื้อ</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-md mx-auto">
                คุณยังไม่มีคำสั่งซื้อในขณะนี้ เริ่มช้อปปิ้งสินค้าที่คุณชอบได้เลย
            </p>
            <a href="{{ route('shop.index') }}"
               class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-700 hover:to-amber-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/>
                </svg>
                เริ่มช้อปปิ้ง
            </a>
        </div>
        @endif
    </div>
</div>
@endsection
