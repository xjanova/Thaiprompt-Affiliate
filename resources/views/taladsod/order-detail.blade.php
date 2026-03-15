@extends('layouts.taladsod')
@section('title', 'ออเดอร์ #' . $order->order_number . ' - ตลาดสดไทยพร๊อม')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <!-- Back button -->
    <a href="{{ route('taladsod.orders') }}" class="inline-flex items-center text-green-600 hover:text-green-700 mb-4">
        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        กลับไปรายการออเดอร์
    </a>

    <!-- Order Header -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">ออเดอร์ #{{ $order->order_number }}</h1>
            @php
                $statusColors = [
                    'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-400',
                    'accepted' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-400',
                    'preparing' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-400',
                    'ready' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-400',
                    'delivering' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-400',
                    'delivered' => 'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-400',
                    'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-400',
                    'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-400',
                ];
                $statusLabels = [
                    'pending' => 'รอดำเนินการ', 'accepted' => 'รับออเดอร์แล้ว', 'preparing' => 'กำลังจัดเตรียม',
                    'ready' => 'พร้อมส่ง/รับ', 'delivering' => 'กำลังจัดส่ง', 'delivered' => 'ส่งแล้ว',
                    'completed' => 'สำเร็จ', 'cancelled' => 'ยกเลิก',
                ];
            @endphp
            <span class="px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$order->order_status] ?? 'bg-gray-100 text-gray-800' }}">
                {{ $statusLabels[$order->order_status] ?? $order->order_status }}
            </span>
        </div>

        <div class="text-sm text-gray-500 dark:text-gray-400">
            สั่งซื้อเมื่อ {{ $order->created_at->format('d/m/Y H:i') }}
        </div>
    </div>

    <!-- Product Info -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-6">
        <h2 class="font-bold text-gray-900 dark:text-white mb-4">สินค้า</h2>
        @if($order->listing)
            <div class="flex gap-4">
                <div class="w-20 h-20 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700 flex-shrink-0">
                    @if($order->listing->main_image_url)
                        <img src="{{ $order->listing->main_image_url }}" alt="{{ $order->listing->title }}" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-2xl">🥬</div>
                    @endif
                </div>
                <div class="flex-1">
                    <h3 class="font-medium text-gray-900 dark:text-white">{{ $order->listing->title }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ $order->quantity }} {{ $order->listing->unit }} x ฿{{ number_format($order->unit_price) }}
                    </p>
                </div>
                <div class="text-right">
                    <span class="text-lg font-bold text-green-600">฿{{ number_format($order->total_amount) }}</span>
                </div>
            </div>
        @endif
    </div>

    <!-- Order Details -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-6">
        <h2 class="font-bold text-gray-900 dark:text-white mb-4">รายละเอียด</h2>
        <dl class="space-y-3 text-sm">
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">การจัดส่ง</dt>
                <dd class="text-gray-900 dark:text-white">{{ ['pickup' => 'นัดรับเอง', 'rider' => 'ไรเดอร์จัดส่ง', 'shipping' => 'ส่งพัสดุ'][$order->delivery_type] ?? $order->delivery_type }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">วิธีชำระ</dt>
                <dd class="text-gray-900 dark:text-white">{{ ['escrow' => 'Escrow', 'cod' => 'เก็บเงินปลายทาง', 'transfer' => 'โอนเงิน', 'wallet' => 'Wallet'][$order->payment_method] ?? $order->payment_method }}</dd>
            </div>
            @if($order->delivery_fee > 0)
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">ค่าจัดส่ง</dt>
                    <dd class="text-gray-900 dark:text-white">฿{{ number_format($order->delivery_fee) }}</dd>
                </div>
            @endif
            @if($order->cashback_amount > 0)
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">แคชแบ็ค</dt>
                    <dd class="text-green-600 font-medium">+฿{{ number_format($order->cashback_amount) }}</dd>
                </div>
            @endif
            @if($order->delivery_address)
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">ที่อยู่จัดส่ง</dt>
                    <dd class="text-gray-900 dark:text-white text-right max-w-xs">{{ $order->delivery_address }}</dd>
                </div>
            @endif
            @if($order->tracking_number)
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">เลขพัสดุ</dt>
                    <dd class="text-gray-900 dark:text-white font-mono">{{ $order->tracking_number }}</dd>
                </div>
            @endif
        </dl>
    </div>

    <!-- Seller Info -->
    @if($order->seller)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-6">
            <h2 class="font-bold text-gray-900 dark:text-white mb-4">ผู้ขาย</h2>
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center text-xl">🏪</div>
                <div>
                    <h3 class="font-medium text-gray-900 dark:text-white">{{ $order->seller->shop_name }}</h3>
                    @if($order->seller->phone)
                        <a href="tel:{{ $order->seller->phone }}" class="text-sm text-green-600 hover:underline">{{ $order->seller->phone }}</a>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Rider Info -->
    @if($order->riderJob)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-6">
            <h2 class="font-bold text-gray-900 dark:text-white mb-4">ข้อมูลไรเดอร์</h2>
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-orange-100 dark:bg-orange-900/40 flex items-center justify-center text-xl">🛵</div>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">สถานะ: {{ $order->riderJob->status }}</p>
                    @if($order->riderJob->rider)
                        <p class="text-sm text-gray-500 dark:text-gray-400">ไรเดอร์: {{ $order->riderJob->rider->name }}</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Cancel Reason -->
    @if($order->cancel_reason)
        <div class="bg-red-50 dark:bg-red-900/20 rounded-2xl p-6 mb-6">
            <h2 class="font-bold text-red-800 dark:text-red-400 mb-2">เหตุผลที่ยกเลิก</h2>
            <p class="text-red-700 dark:text-red-300">{{ $order->cancel_reason }}</p>
        </div>
    @endif

    <!-- Actions: ยืนยันรับสินค้า -->
    @if($order->order_status === 'delivered' && !$order->buyer_confirmed_at)
        <div class="text-center mb-6">
            <form action="{{ route('taladsod.orders.confirm', $order) }}" method="POST" class="inline">
                @csrf @method('PUT')
                <button type="submit" class="px-8 py-3 bg-green-600 hover:bg-green-700 text-white rounded-xl font-medium transition shadow-lg">
                    ✅ ยืนยันรับสินค้าแล้ว
                </button>
            </form>
        </div>
    @endif

    <!-- ฟอร์มรีวิว/ให้ดาว -->
    @if($order->canBeReviewed())
        <div x-data="{ rating: 0, hoverRating: 0, review: '' }" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-6">
            <h2 class="font-bold text-gray-900 dark:text-white mb-4">⭐ ให้คะแนนผู้ขาย</h2>
            <form action="{{ route('taladsod.orders.review', $order) }}" method="POST">
                @csrf
                {{-- ดาว 1-5 --}}
                <div class="flex items-center gap-1 mb-4">
                    @for($i = 1; $i <= 5; $i++)
                        <button type="button"
                                @click="rating = {{ $i }}"
                                @mouseenter="hoverRating = {{ $i }}"
                                @mouseleave="hoverRating = 0"
                                class="text-3xl transition-transform hover:scale-125 focus:outline-none cursor-pointer">
                            <i :class="(hoverRating || rating) >= {{ $i }} ? 'fas fa-star text-yellow-400' : 'far fa-star text-gray-300 dark:text-gray-600'"></i>
                        </button>
                    @endfor
                    <span class="ml-2 text-sm text-gray-500 dark:text-gray-400" x-show="rating > 0">
                        <span x-text="['', 'แย่มาก', 'แย่', 'พอใช้', 'ดี', 'ยอดเยี่ยม'][rating]"></span>
                    </span>
                </div>
                <input type="hidden" name="buyer_rating" :value="rating">

                {{-- เขียนรีวิว --}}
                <div class="mb-4">
                    <textarea name="buyer_review"
                              x-model="review"
                              rows="3"
                              maxlength="1000"
                              placeholder="เขียนรีวิวให้ผู้ขาย (ไม่บังคับ)..."
                              class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-green-500 focus:border-green-500 resize-none"></textarea>
                    <div class="text-right text-xs text-gray-400 mt-1">
                        <span x-text="review.length"></span>/1000
                    </div>
                </div>

                <button type="submit"
                        :disabled="rating === 0"
                        class="w-full py-3 bg-green-600 hover:bg-green-700 disabled:bg-gray-300 dark:disabled:bg-gray-600 text-white font-medium rounded-xl transition-all">
                    <span x-show="rating > 0">ส่งรีวิว ⭐</span>
                    <span x-show="rating === 0">กรุณาเลือกคะแนนก่อน</span>
                </button>
            </form>
        </div>
    @endif

    <!-- แสดงรีวิวที่เคยให้ -->
    @if($order->buyer_rating)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-6">
            <h2 class="font-bold text-gray-900 dark:text-white mb-3">รีวิวของคุณ</h2>
            <div class="flex items-center gap-1 mb-2">
                @for($i = 1; $i <= 5; $i++)
                    <i class="{{ $i <= $order->buyer_rating ? 'fas fa-star text-yellow-400' : 'far fa-star text-gray-300 dark:text-gray-600' }} text-lg"></i>
                @endfor
                <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">{{ $order->buyer_rating }}/5</span>
            </div>
            @if($order->buyer_review)
                <p class="text-gray-700 dark:text-gray-300 text-sm leading-relaxed">{{ $order->buyer_review }}</p>
            @endif
        </div>
    @endif
</div>
@endsection