@extends('layouts.seller')

@section('title', 'รายละเอียดคำสั่งซื้อ #' . $order->order_number)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <a href="{{ route('seller.orders.index') }}" class="text-blue-600 hover:text-blue-800 text-sm mb-2 inline-block">
                ← กลับไปรายการคำสั่งซื้อ
            </a>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-white">คำสั่งซื้อ #{{ $order->order_number }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">วันที่สั่งซื้อ: {{ $order->created_at->format('d/m/Y H:i') }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('seller.orders.print', $order) }}" target="_blank"
               class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-medium transition">
                🖨️ พิมพ์ใบส่งของ
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Order Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Order Items -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white">รายการสินค้า</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        @foreach($sellerItems as $item)
                            <div class="flex gap-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                @php($itemImage = \App\Services\Shop\ShopPresenter::imageUrl($item->product_image ?: $item->product?->main_image_url))
                                @if($itemImage)
                                    <img src="{{ $itemImage }}"
                                         alt="{{ $item->product_name }}"
                                         class="w-20 h-20 object-cover rounded-lg">
                                @else
                                    <div class="w-20 h-20 bg-gray-200 dark:bg-gray-600 rounded-lg flex items-center justify-center">
                                        <span class="text-3xl">📦</span>
                                    </div>
                                @endif
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-800 dark:text-white">{{ $item->product_name }}</h3>
                                    @if($item->product_sku)
                                        <p class="text-sm text-gray-500 dark:text-gray-400">SKU: {{ $item->product_sku }}</p>
                                    @endif
                                    <div class="mt-2 flex items-center gap-4">
                                        <span class="text-sm text-gray-600 dark:text-gray-300">ราคา: ฿{{ number_format((float) $item->unit_price, 2) }}</span>
                                        <span class="text-sm text-gray-600 dark:text-gray-300">จำนวน: {{ $item->quantity }}</span>
                                        <span class="text-sm font-semibold text-gray-800 dark:text-white">รวม: ฿{{ number_format($item->total, 2) }}</span>
                                    </div>
                                    <div class="mt-2">
                                        @php
                                            $statusColors = [
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'processing' => 'bg-blue-100 text-blue-800',
                                                'shipped' => 'bg-purple-100 text-purple-800',
                                                'delivered' => 'bg-green-100 text-green-800',
                                                'completed' => 'bg-green-100 text-green-800',
                                            ];
                                            $statusNames = [
                                                'pending' => 'รอดำเนินการ',
                                                'processing' => 'กำลังดำเนินการ',
                                                'shipped' => 'จัดส่งแล้ว',
                                                'delivered' => 'ส่งสำเร็จ',
                                                'completed' => 'เสร็จสิ้น',
                                            ];
                                        @endphp
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$item->status] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ $statusNames[$item->status] ?? $item->status }}
                                        </span>
                                    </div>

                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Seller Summary -->
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">ยอดรวมของคุณ</span>
                                <span class="text-gray-800 dark:text-white font-medium">฿{{ number_format($sellerTotal, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">ค่า GP แพลตฟอร์ม</span>
                                <span class="text-red-600 font-medium">-฿{{ number_format($sellerCommission, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-lg font-bold border-t pt-2">
                                <span class="text-gray-800 dark:text-white">รายได้สุทธิของคุณ</span>
                                <span class="text-green-600">฿{{ number_format($sellerEarning, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 🛒 (2026-09-25) ปุ่มจัดการออเดอร์ของร้าน (SELLER-03/14) — แสดงเฉพาะปุ่มที่ทำได้จริงตามสถานะ -->
            @if(! empty($allowedActions))
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">จัดการคำสั่งซื้อ</h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex flex-wrap gap-2">
                            @if(in_array('confirm', $allowedActions, true))
                                <form action="{{ route('seller.orders.action', $order->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="action" value="confirm">
                                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition">
                                        ✅ ยืนยันรับคำสั่งซื้อ
                                    </button>
                                </form>
                            @endif
                            @if(in_array('request_rider', $allowedActions, true))
                                <form action="{{ route('seller.orders.action', $order->id) }}" method="POST"
                                      onsubmit="return confirm('เรียกไรเดอร์มารับสินค้าตอนนี้?');">
                                    @csrf
                                    <input type="hidden" name="action" value="request_rider">
                                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium transition">
                                        🛵 เรียกไรเดอร์มารับของ
                                    </button>
                                </form>
                            @endif
                            @if(in_array('deliver', $allowedActions, true))
                                <form action="{{ route('seller.orders.action', $order->id) }}" method="POST"
                                      onsubmit="return confirm('ยืนยันว่าลูกค้าได้รับสินค้าแล้ว?');">
                                    @csrf
                                    <input type="hidden" name="action" value="deliver">
                                    <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition">
                                        📦 ยืนยันส่งถึงแล้ว
                                    </button>
                                </form>
                            @endif
                        </div>

                        @if(in_array('ship', $allowedActions, true))
                            <form action="{{ route('seller.orders.action', $order->id) }}" method="POST" class="space-y-4 pt-2">
                                @csrf
                                <input type="hidden" name="action" value="ship">
                                <div>
                                    <label for="shipping_provider_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">บริษัทขนส่ง</label>
                                    <select name="shipping_provider_id" id="shipping_provider_id" required
                                            class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                                        <option value="">— เลือกบริษัทขนส่ง —</option>
                                        @foreach($shippingProviders as $provider)
                                            <option value="{{ $provider->id }}" @selected(old('shipping_provider_id') == $provider->id)>{{ $provider->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="tracking_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เลขพัสดุ</label>
                                    <input type="text" name="tracking_number" id="tracking_number" required maxlength="100"
                                           value="{{ old('tracking_number') }}"
                                           class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg"
                                           placeholder="กรอกเลขพัสดุ">
                                </div>
                                <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition">
                                    บันทึกเลขพัสดุและอัพเดตสถานะเป็น "จัดส่งแล้ว"
                                </button>
                            </form>
                        @endif

                        @if(in_array('cancel', $allowedActions, true))
                            <form action="{{ route('seller.orders.action', $order->id) }}" method="POST" class="space-y-2 pt-2 border-t border-gray-200 dark:border-gray-700"
                                  onsubmit="return confirm('ยืนยันยกเลิกคำสั่งซื้อนี้? ถ้าลูกค้าจ่ายแล้วระบบจะคืนเงินให้ลูกค้าทันที');">
                                @csrf
                                <input type="hidden" name="action" value="cancel">
                                <label for="cancel_reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300">ยกเลิกคำสั่งซื้อ (เช่น สินค้าหมด)</label>
                                <input type="text" name="reason" id="cancel_reason" required minlength="3" maxlength="500"
                                       class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg"
                                       placeholder="ระบุเหตุผลที่ยกเลิก">
                                <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition">
                                    ยกเลิกคำสั่งซื้อ
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @elseif(! in_array($order->status, ['cancelled', 'refunded', 'completed', 'delivered', 'shipped'], true) && $order->payment_status !== 'paid' && ! $order->isCod())
                <div class="bg-yellow-50 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-200 rounded-xl p-4">
                    คำสั่งซื้อนี้ยังไม่ได้ชำระเงิน — รอลูกค้าชำระก่อนจึงเตรียม/จัดส่งสินค้าได้
                </div>
            @endif

            @if($riderSummary)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-2">ส่งด้วยไรเดอร์</h2>
                    <p class="text-gray-700 dark:text-gray-300">สถานะ: {{ $riderSummary['status_label'] ?? '-' }}</p>
                    @if(! empty($riderSummary['rider']['name']))
                        <p class="text-gray-700 dark:text-gray-300">ไรเดอร์: {{ $riderSummary['rider']['name'] }}</p>
                    @endif
                </div>
            @endif

            @if($order->tracking_number)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">ข้อมูลการจัดส่ง</h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">บริษัทขนส่ง</span>
                                <span class="text-gray-800 dark:text-white font-medium">{{ $order->shipping_provider }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">เลขพัสดุ</span>
                                <span class="text-gray-800 dark:text-white font-medium">{{ $order->tracking_number }}</span>
                            </div>
                            @if($order->shipped_at)
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">วันที่จัดส่ง</span>
                                    <span class="text-gray-800 dark:text-white font-medium">{{ $order->shipped_at->format('d/m/Y H:i') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Customer Info -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white">ข้อมูลลูกค้า</h2>
                </div>
                <div class="p-6 space-y-3">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">ชื่อ</p>
                        <p class="text-gray-800 dark:text-white font-medium">{{ $order->user->name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">อีเมล</p>
                        <p class="text-gray-800 dark:text-white">{{ $order->user->email }}</p>
                    </div>
                    @if($order->user->phone)
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">เบอร์โทร</p>
                            <p class="text-gray-800 dark:text-white">{{ $order->user->phone }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Shipping Address (จาก snapshot ณ เวลาสั่ง — ที่อยู่ที่ลูกค้าแก้ทีหลังไม่กระทบออเดอร์นี้) -->
            @php($ship = \App\Services\Shop\ShopPresenter::shipping($order))
            @if($ship)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">ที่อยู่จัดส่ง</h2>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-800 dark:text-white">{{ $ship['name'] }}</p>
                        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">{{ $ship['phone'] }}</p>
                        <p class="text-gray-600 dark:text-gray-400 text-sm mt-2">
                            {{ $ship['address'] }}<br>
                            @if($ship['address_line_2'])
                                {{ $ship['address_line_2'] }}<br>
                            @endif
                            {{ trim(($ship['subdistrict'] ?? '').' '.($ship['district'] ?? '')) }}<br>
                            {{ $ship['province'] }} {{ $ship['postal_code'] }}
                        </p>
                        @if($ship['notes'])
                            <p class="text-gray-500 dark:text-gray-400 text-xs mt-2">หมายเหตุ: {{ $ship['notes'] }}</p>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Order Status -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white">สถานะคำสั่งซื้อ</h2>
                </div>
                <div class="p-6">
                    @php
                        $statusColors = [
                            'pending' => 'bg-yellow-100 text-yellow-800',
                            'processing' => 'bg-blue-100 text-blue-800',
                            'shipped' => 'bg-purple-100 text-purple-800',
                            'delivered' => 'bg-green-100 text-green-800',
                            'completed' => 'bg-green-100 text-green-800',
                            'cancelled' => 'bg-red-100 text-red-800',
                        ];
                        $statusNames = [
                            'pending' => 'รอดำเนินการ',
                            'processing' => 'กำลังดำเนินการ',
                            'shipped' => 'จัดส่งแล้ว',
                            'delivered' => 'ส่งสำเร็จ',
                            'completed' => 'เสร็จสิ้น',
                            'cancelled' => 'ยกเลิก',
                        ];
                    @endphp
                    <span class="px-3 py-2 inline-flex text-sm leading-5 font-semibold rounded-full {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-800' }}">
                        {{ $order->status_label }}
                    </span>

                    <div class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">วันที่สั่งซื้อ</span>
                            <span class="text-gray-800 dark:text-white">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">วิธีชำระเงิน</span>
                            <span class="text-gray-800 dark:text-white">{{ $paymentMethodLabel }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">การจัดส่ง</span>
                            <span class="text-gray-800 dark:text-white">{{ $order->isRiderDelivery() ? 'ไรเดอร์' : 'พัสดุ' }}</span>
                        </div>
                        @if($order->payment_status === 'paid')
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">ชำระเงินแล้ว</span>
                                <span class="text-green-600 font-medium">✓</span>
                            </div>
                        @elseif($order->isCod())
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">ชำระเงิน</span>
                                <span class="text-yellow-600 font-medium">เก็บเงินปลายทาง</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
