@extends('layouts.admin-v3')

@section('title', 'รายละเอียดคำสั่งซื้อ')

@section('content')
<div class="space-y-6" x-data="{ language: 'th' }">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">📋 <span data-translate>คำสั่งซื้อ</span> #{{ $order->order_number }}</h1>

        <div class="flex items-center gap-4">
            {{-- Language Switcher --}}
            <div class="relative inline-block" x-data="{ open: false }">
                <button @click="open = !open" class="px-4 py-2 bg-gradient-to-r from-orange-500 to-pink-600 text-white rounded-xl hover:from-orange-600 hover:to-pink-700 transition-all duration-200 shadow-lg flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                    </svg>
                    <span data-translate>ภาษา</span>
                </button>

                <div x-show="open" @click.away="open = false" x-transition
                     class="absolute right-0 mt-2 w-48 bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-gray-200 dark:border-slate-700 overflow-hidden z-50">
                    <a href="#" @click.prevent="language = 'th'" class="block px-4 py-3 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors">
                        <span class="mr-2">🇹🇭</span> <span data-translate>ไทย</span>
                    </a>
                    <a href="#" @click.prevent="language = 'en'" class="block px-4 py-3 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors">
                        <span class="mr-2">🇬🇧</span> English
                    </a>
                    <a href="#" @click.prevent="language = 'zh'" class="block px-4 py-3 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors">
                        <span class="mr-2">🇨🇳</span> 中文
                    </a>
                    <a href="#" @click.prevent="language = 'ja'" class="block px-4 py-3 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors">
                        <span class="mr-2">🇯🇵</span> 日本語
                    </a>
                </div>
            </div>

            {{-- จัดการพัสดุ --}}
            <a href="{{ route('admin.ecommerce.orders.tracking', $order) }}" class="px-4 py-2 bg-gradient-to-r from-blue-500 to-cyan-600 text-white rounded-xl hover:from-blue-600 hover:to-cyan-700 transition-all duration-200 shadow-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                <span data-translate>จัดการพัสดุ</span>
            </a>

            <a href="{{ route('admin.ecommerce.orders.index') }}" class="text-orange-600 dark:text-orange-400 hover:text-orange-700">
                ← <span data-translate>กลับ</span>
            </a>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 px-4 py-3 rounded-xl">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-xl">
            {{ session('error') }}
        </div>
    @endif

    <!-- ข้อมูลทั่วไป -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- ข้อมูลลูกค้า --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-slate-700">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span data-translate>ข้อมูลลูกค้า</span>
            </h3>
            <div class="space-y-2 text-sm">
                <p><span class="font-semibold text-gray-700 dark:text-gray-300" data-translate>ชื่อ:</span> <span class="text-gray-900 dark:text-white">{!! $order->user->name ?? '<span class="text-gray-400" data-translate>ไม่ระบุ</span>' !!}</span></p>
                <p><span class="font-semibold text-gray-700 dark:text-gray-300" data-translate>อีเมล:</span> <span class="text-gray-900 dark:text-white">{!! $order->user->email ?? '<span class="text-gray-400" data-translate>ไม่ระบุ</span>' !!}</span></p>
                <p><span class="font-semibold text-gray-700 dark:text-gray-300" data-translate>เบอร์โทร:</span> <span class="text-gray-900 dark:text-white">{!! $order->customer_phone ?? '<span class="text-gray-400" data-translate>ไม่ระบุ</span>' !!}</span></p>
            </div>
        </div>

        {{-- วันที่ --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-slate-700">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span data-translate>วันที่</span>
            </h3>
            <div class="space-y-2 text-sm">
                <p><span class="font-semibold text-gray-700 dark:text-gray-300" data-translate>สั่งซื้อเมื่อ:</span> <span class="text-gray-900 dark:text-white">{{ $order->created_at->format('d/m/Y H:i') }}</span></p>
                @if($order->paid_at)
                    <p><span class="font-semibold text-gray-700 dark:text-gray-300" data-translate>ชำระเมื่อ:</span> <span class="text-gray-900 dark:text-white">{{ $order->paid_at->format('d/m/Y H:i') }}</span></p>
                @endif
                @if($order->shipped_at)
                    <p><span class="font-semibold text-gray-700 dark:text-gray-300" data-translate>จัดส่งเมื่อ:</span> <span class="text-gray-900 dark:text-white">{{ $order->shipped_at->format('d/m/Y H:i') }}</span></p>
                @endif
                @if($order->delivered_at)
                    <p><span class="font-semibold text-gray-700 dark:text-gray-300" data-translate>ส่งถึงเมื่อ:</span> <span class="text-gray-900 dark:text-white">{{ $order->delivered_at->format('d/m/Y H:i') }}</span></p>
                @endif
                <p><span class="font-semibold text-gray-700 dark:text-gray-300" data-translate>วิธีชำระเงิน:</span> <span class="text-gray-900 dark:text-white">{!! $order->payment_method ?? '<span class="text-gray-400" data-translate>ไม่ระบุ</span>' !!}</span></p>
            </div>
        </div>

        {{-- ที่อยู่จัดส่ง --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-slate-700">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span data-translate>ที่อยู่จัดส่ง</span>
            </h3>
            @if($order->shippingAddress)
                <div class="space-y-2 text-sm">
                    <p><span class="font-semibold text-gray-700 dark:text-gray-300" data-translate>ผู้รับ:</span> <span class="text-gray-900 dark:text-white">{{ $order->shippingAddress->recipient_name }}</span></p>
                    <p><span class="font-semibold text-gray-700 dark:text-gray-300" data-translate>เบอร์โทร:</span> <span class="text-gray-900 dark:text-white">{{ $order->shippingAddress->phone_number }}</span></p>
                    <p class="text-gray-900 dark:text-white">{{ $order->shippingAddress->full_address }}</p>
                    @if($order->shippingAddress->notes)
                        <p class="text-gray-500 dark:text-gray-400 italic">{{ $order->shippingAddress->notes }}</p>
                    @endif
                </div>
            @else
                <p class="text-sm text-gray-400 dark:text-gray-500" data-translate>ไม่มีข้อมูลที่อยู่จัดส่ง</p>
            @endif
        </div>
    </div>

    <!-- อัปเดตสถานะ -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- อัปเดตสถานะคำสั่งซื้อ --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-slate-700">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span data-translate>อัปเดตสถานะคำสั่งซื้อ</span>
            </h3>
            <form action="{{ route('admin.ecommerce.orders.status.update', $order) }}" method="POST" class="space-y-4"
                  x-data="{ selectedStatus: '{{ $order->status }}' }"
                  @submit.prevent="
                    if (selectedStatus === 'cancelled') {
                        if (!confirm('ยืนยันยกเลิกคำสั่งซื้อ?\n\n{{ in_array($order->status, ['paid', 'processing']) ? '- ระบบจะคืนเงิน ฿' . number_format($order->total_amount, 2) . ' เข้า Wallet ลูกค้า\n- Clawback Commission/Cashback ที่แจกไปแล้ว\n- คืน Stock สินค้า' : '- คืน Stock สินค้า' }}')) return;
                    } else if (selectedStatus === 'refunded') {
                        if (!confirm('ยืนยันคืนเงินคำสั่งซื้อ?\n\n- คืนเงิน ฿{{ number_format($order->total_amount, 2) }} เข้า Wallet ลูกค้า\n- Clawback Commission/Cashback ที่แจกไปแล้ว\n- อาจสร้างหนี้ (Debt) ถ้าผู้ขายถอนเงินไปแล้ว')) return;
                    }
                    $el.submit();
                  ">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" data-translate>สถานะปัจจุบัน</label>
                    <select name="status" x-model="selectedStatus" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:ring-orange-500 focus:border-orange-500">
                        @php
                            $statuses = [
                                'pending' => 'รอดำเนินการ',
                                'processing' => 'กำลังดำเนินการ',
                                'shipped' => 'จัดส่งแล้ว',
                                'completed' => 'สำเร็จ',
                                'cancelled' => 'ยกเลิก',
                                'refunded' => 'คืนเงิน',
                            ];
                        @endphp
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" {{ $order->status === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- คำเตือนเมื่อเลือกยกเลิก/คืนเงิน --}}
                <div x-show="selectedStatus === 'cancelled'" x-cloak
                     class="p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-700 dark:text-red-300">
                    <p class="font-semibold mb-1">คำเตือน: การยกเลิกคำสั่งซื้อ</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li>คืน Stock สินค้าทั้งหมด</li>
                        @if(in_array($order->status, ['paid', 'processing']))
                            <li>คืนเงิน ฿{{ number_format($order->total_amount, 2) }} เข้า Wallet ลูกค้าอัตโนมัติ</li>
                            <li>Clawback Commission/Cashback ที่แจกไปแล้ว</li>
                        @endif
                    </ul>
                </div>

                <div x-show="selectedStatus === 'refunded'" x-cloak
                     class="p-3 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-lg text-sm text-amber-700 dark:text-amber-300">
                    <p class="font-semibold mb-1">คำเตือน: การคืนเงิน</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li>คืนเงิน ฿{{ number_format($order->total_amount, 2) }} เข้า Wallet ลูกค้า</li>
                        <li>Clawback Commission/Cashback ที่แจกไปแล้ว</li>
                        <li>หักเงินจาก Wallet ผู้ขาย (หรือสร้างหนี้ถ้ายอดไม่พอ)</li>
                    </ul>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" data-translate>หมายเหตุแอดมิน</label>
                    <textarea name="admin_notes" rows="2"
                              class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:ring-orange-500 focus:border-orange-500"
                              :placeholder="(selectedStatus === 'cancelled' || selectedStatus === 'refunded') ? 'กรุณาระบุเหตุผล' : 'หมายเหตุเพิ่มเติม (ไม่บังคับ)'"
                              :required="selectedStatus === 'cancelled' || selectedStatus === 'refunded'">{{ $order->admin_notes }}</textarea>
                </div>
                <button type="submit" class="w-full px-4 py-2 bg-gradient-to-r from-orange-500 to-pink-600 text-white rounded-lg hover:from-orange-600 hover:to-pink-700 transition-all duration-200 shadow-lg font-medium"
                        :class="{ 'from-red-500 to-red-600 hover:from-red-600 hover:to-red-700': selectedStatus === 'cancelled', 'from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700': selectedStatus === 'refunded' }">
                    <span x-text="selectedStatus === 'cancelled' ? 'ยกเลิกคำสั่งซื้อ' : (selectedStatus === 'refunded' ? 'คืนเงินคำสั่งซื้อ' : 'บันทึกสถานะคำสั่งซื้อ')"></span>
                </button>
            </form>
        </div>

        {{-- อัปเดตสถานะการชำระเงิน --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-slate-700">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span data-translate>อัปเดตสถานะการชำระเงิน</span>
            </h3>
            <form action="{{ route('admin.ecommerce.orders.payment-status.update', $order) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" data-translate>สถานะการชำระเงิน</label>
                    <select name="payment_status" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                        @php
                            $paymentStatuses = [
                                'pending' => 'รอชำระเงิน',
                                'paid' => 'ชำระแล้ว',
                                'failed' => 'ล้มเหลว',
                                'refunded' => 'คืนเงินแล้ว',
                            ];
                        @endphp
                        @foreach($paymentStatuses as $value => $label)
                            <option value="{{ $value }}" {{ $order->payment_status === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="p-3 bg-gray-50 dark:bg-slate-700/50 rounded-lg text-sm space-y-1">
                    <p class="text-gray-600 dark:text-gray-400"><span class="font-semibold" data-translate>ยอดรวม:</span> <span class="text-gray-900 dark:text-white font-bold">฿{{ number_format($order->total_amount, 2) }}</span></p>
                    <p class="text-gray-600 dark:text-gray-400"><span class="font-semibold" data-translate>วิธีชำระเงิน:</span> <span class="text-gray-900 dark:text-white">{!! $order->payment_method ?? '<span class="text-gray-400">ไม่ระบุ</span>' !!}</span></p>
                </div>
                <button type="submit" class="w-full px-4 py-2 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-lg hover:from-emerald-600 hover:to-teal-700 transition-all duration-200 shadow-lg font-medium">
                    <span data-translate>บันทึกสถานะการชำระเงิน</span>
                </button>
            </form>
        </div>
    </div>

    <!-- รายการสินค้า -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-slate-700">
        <h3 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
            <span data-translate>รายการสินค้า</span>
        </h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase" data-translate>สินค้า</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase" data-translate>ราคา</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase" data-translate>จำนวน</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase" data-translate>รวม</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($order->items as $item)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    @if($item->product && $item->product->images && $item->product->images->isNotEmpty())
                                        <img src="{{ asset('storage/' . $item->product->images->first()->image_path) }}" alt="{{ $item->product->name }}" class="w-12 h-12 rounded-lg object-cover">
                                    @else
                                        <div class="w-12 h-12 rounded-lg bg-gray-200 dark:bg-slate-600 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{!! $item->product->name ?? '<span class="text-gray-400" data-translate>ไม่ระบุ</span>' !!}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">SKU: {{ $item->product->sku ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                ฿{{ number_format($item->price, 2) }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ $item->quantity }}
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                ฿{{ number_format($item->price * $item->quantity, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 dark:bg-slate-700">
                    @if($order->shipping_fee > 0)
                    <tr>
                        <td colspan="3" class="px-6 py-3 text-right text-sm text-gray-600 dark:text-gray-400" data-translate>ค่าจัดส่ง:</td>
                        <td class="px-6 py-3 text-sm text-gray-900 dark:text-white">฿{{ number_format($order->shipping_fee, 2) }}</td>
                    </tr>
                    @endif
                    @if($order->discount_amount > 0)
                    <tr>
                        <td colspan="3" class="px-6 py-3 text-right text-sm text-gray-600 dark:text-gray-400" data-translate>ส่วนลด:</td>
                        <td class="px-6 py-3 text-sm text-red-500">-฿{{ number_format($order->discount_amount, 2) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td colspan="3" class="px-6 py-4 text-right text-sm font-semibold text-gray-900 dark:text-white" data-translate>ยอดรวมทั้งสิ้น:</td>
                        <td class="px-6 py-4 text-lg font-bold text-orange-600 dark:text-orange-400">฿{{ number_format($order->total_amount, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- ข้อมูลการยกเลิก/คืนเงิน --}}
    @if(in_array($order->status, ['cancelled', 'refunded']))
    <div class="bg-red-50 dark:bg-red-900/20 rounded-xl shadow-lg p-6 border border-red-200 dark:border-red-800">
        <h3 class="font-semibold text-red-800 dark:text-red-300 mb-3 flex items-center gap-2">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            {{ $order->status === 'refunded' ? 'ข้อมูลการคืนเงิน' : 'ข้อมูลการยกเลิก' }}
        </h3>
        <div class="space-y-2 text-sm">
            @if($order->cancellation_reason)
                <p><span class="font-semibold text-red-700 dark:text-red-300">เหตุผลการยกเลิก:</span> <span class="text-red-600 dark:text-red-400">{{ $order->cancellation_reason }}</span></p>
            @endif
            @if($order->refund_reason)
                <p><span class="font-semibold text-red-700 dark:text-red-300">เหตุผลการคืนเงิน:</span> <span class="text-red-600 dark:text-red-400">{{ $order->refund_reason }}</span></p>
            @endif
            @if($order->cancelled_at)
                <p><span class="font-semibold text-red-700 dark:text-red-300">ยกเลิกเมื่อ:</span> <span class="text-red-600 dark:text-red-400">{{ $order->cancelled_at->format('d/m/Y H:i') }}</span></p>
            @endif
            @if($order->refunded_at)
                <p><span class="font-semibold text-red-700 dark:text-red-300">คืนเงินเมื่อ:</span> <span class="text-red-600 dark:text-red-400">{{ $order->refunded_at->format('d/m/Y H:i') }}</span></p>
            @endif
            @if($order->refunded_by)
                @php $refundedByUser = \App\Models\User::find($order->refunded_by); @endphp
                @if($refundedByUser)
                    <p><span class="font-semibold text-red-700 dark:text-red-300">คืนเงินโดย:</span> <span class="text-red-600 dark:text-red-400">{{ $refundedByUser->name }}</span></p>
                @endif
            @endif
        </div>
    </div>
    @endif

    {{-- หมายเหตุแอดมิน --}}
    @if($order->admin_notes)
    <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl shadow-lg p-6 border border-yellow-200 dark:border-yellow-800">
        <h3 class="font-semibold text-yellow-800 dark:text-yellow-300 mb-2 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            <span data-translate>หมายเหตุแอดมิน</span>
        </h3>
        <p class="text-yellow-700 dark:text-yellow-400 text-sm whitespace-pre-wrap">{{ $order->admin_notes }}</p>
    </div>
    @endif
</div>

@push('scripts')
<script>
/**
 * ฟังก์ชันแปลภาษาด้วย Google Translate API
 *
 * @param {string} targetLang รหัสภาษาเป้าหมาย (en, zh, ja)
 */
async function translatePage(targetLang) {
    // ถ้าเลือกภาษาไทย ให้โหลดหน้าใหม่เพื่อแสดงข้อความต้นฉบับ
    if (targetLang === 'th') {
        location.reload();
        return;
    }

    // ดึง elements ที่มี data-translate attribute
    const elements = document.querySelectorAll('[data-translate]');

    try {
        // สร้าง array ของข้อความที่ต้องการแปล
        const textsToTranslate = Array.from(elements).map(el => {
            // เก็บข้อความต้นฉบับไว้ใน dataset
            if (!el.dataset.originalText) {
                el.dataset.originalText = el.textContent.trim();
            }
            return el.dataset.originalText;
        });

        // เรียก Google Translate API
        const apiKey = '{{ config("services.google_translate.key", "") }}';

        if (!apiKey) {
            console.warn('Google Translate API key not configured');
            return;
        }

        const response = await fetch(`https://translation.googleapis.com/language/translate/v2?key=${apiKey}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                q: textsToTranslate,
                source: 'th',
                target: targetLang,
                format: 'text'
            })
        });

        const data = await response.json();

        // อัพเดทข้อความที่แปลแล้ว
        if (data.data && data.data.translations) {
            data.data.translations.forEach((translation, index) => {
                if (elements[index]) {
                    elements[index].textContent = translation.translatedText;
                }
            });
        }

    } catch (error) {
        console.error('Translation error:', error);
    }
}

// ติดตั้ง watcher สำหรับ Alpine.js
document.addEventListener('alpine:init', () => {
    Alpine.watch('language', (value) => {
        if (value !== 'th') {
            translatePage(value);
        }
    });
});
</script>
@endpush
@endsection
