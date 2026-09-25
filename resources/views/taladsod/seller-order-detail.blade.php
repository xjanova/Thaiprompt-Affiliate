{{--
    รายละเอียดออเดอร์ (ฝั่งผู้ขาย) - ตลาดสดไทยพร๊อม

    ตัวแปร:
    - $seller          FreshMarketSeller
    - $order           FreshMarketOrder (with buyer, listing, riderJob.rider)
    - $allowedActions  action ที่ร้านทำได้ตอนนี้ (accept|prepare|ready|handover|cancel)
    - $actionLabels    [action => ป้ายภาษาไทย]
    ฟอร์ม: PUT taladsod.seller.orders.status  (action, reason เมื่อ cancel)
    หน้านี้เป็นเวอร์ชันใช้งานได้ก่อน — จะถูกสร้างใหม่ในธีม V4
--}}
@extends('layouts.taladsod')

@section('title', 'ออเดอร์ #'.$order->order_number.' - ตลาดสดไทยพร๊อม')

@section('content')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8" x-data="{ showCancel: false }">
        <a href="{{ route('taladsod.seller.orders') }}" class="text-sm text-green-600 dark:text-green-400 hover:underline font-medium">← ออเดอร์ร้าน</a>

        <div class="mt-4">@include('taladsod.partials.flash')</div>

        <div class="p-5 bg-white dark:bg-gray-800 rounded-2xl shadow border border-gray-100 dark:border-gray-700">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">ออเดอร์ #{{ $order->order_number }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">สั่งเมื่อ {{ $order->created_at?->format('d/m/Y H:i') }}</p>
                </div>
                <span class="px-3 py-1 rounded-full text-sm font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                    {{ $order->status_label }}
                </span>
            </div>

            <dl class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">สินค้า</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $order->riderItemsSummary() }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">ผู้ซื้อ</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">
                        {{ $order->buyer?->name ?? '-' }}
                        @if (! in_array($order->order_status, ['pending', 'cancelled'], true) && $order->buyer?->phone)
                            <a href="tel:{{ $order->buyer->phone }}" class="text-green-600 dark:text-green-400 ml-1">{{ $order->buyer->phone }}</a>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">การจัดส่ง</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">
                        {{ $order->delivery_type === 'rider' ? 'ส่งด้วยไรเดอร์' : 'ลูกค้ามารับเองที่ร้าน' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">การชำระเงิน</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $order->payment_method_label }} ({{ $order->payment_status_label }})</dd>
                </div>
                @if ($order->delivery_type === 'rider')
                    <div class="sm:col-span-2">
                        <dt class="text-gray-500 dark:text-gray-400">ไรเดอร์</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">
                            @if ($order->riderJob)
                                {{ $order->riderJob->rider?->full_name ?? 'กำลังหาไรเดอร์' }}
                                ({{ $order->riderJob->status }})
                            @else
                                ยังไม่ได้เรียกไรเดอร์ — กด "สินค้าพร้อมส่ง" เพื่อเรียกไรเดอร์
                            @endif
                        </dd>
                    </div>
                @endif
                @if ($order->delivery_notes)
                    <div class="sm:col-span-2">
                        <dt class="text-gray-500 dark:text-gray-400">หมายเหตุจากผู้ซื้อ</dt>
                        <dd class="text-gray-900 dark:text-white">{{ $order->delivery_notes }}</dd>
                    </div>
                @endif
            </dl>

            {{-- สรุปเงิน: ราคา − GP = รับจริง --}}
            <div class="mt-5 p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50 text-sm space-y-1">
                <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-300">ยอดสินค้า</span><span class="text-gray-900 dark:text-white">฿{{ number_format((float) $order->total_amount, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-300">ค่า GP {{ $order->gp_rate !== null ? '('.rtrim(rtrim(number_format((float) $order->gp_rate, 2), '0'), '.').'%)' : '' }}</span><span class="text-red-600 dark:text-red-400">−฿{{ number_format((float) $order->platform_fee, 2) }}</span></div>
                <div class="flex justify-between font-bold"><span class="text-gray-900 dark:text-white">ร้านได้รับจริง</span><span class="text-green-600 dark:text-green-400">฿{{ number_format((float) $order->seller_earning, 2) }}</span></div>
                @if ($order->payment_method === 'cod' && $order->delivery_type === 'pickup')
                    <p class="text-xs text-gray-500 dark:text-gray-400 pt-1">เก็บเงินสดจากลูกค้าเต็มจำนวน ฿{{ number_format((float) $order->total_amount, 2) }} — ระบบหักค่า GP จาก Wallet ร้านเมื่อปิดออเดอร์</p>
                @endif
            </div>

            @if ($order->cancel_reason)
                <p class="mt-4 text-sm text-red-600 dark:text-red-400">เหตุผลที่ยกเลิก: {{ $order->cancel_reason }}</p>
            @endif

            {{-- ปุ่มจัดการ --}}
            @if (! empty($allowedActions))
                <div class="mt-6 flex flex-wrap gap-3">
                    @foreach ($allowedActions as $action)
                        @if ($action !== 'cancel')
                            <form method="POST" action="{{ route('taladsod.seller.orders.status', $order) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="action" value="{{ $action }}">
                                <button type="submit" class="px-5 py-3 rounded-xl bg-green-600 hover:bg-green-700 text-white font-semibold">
                                    {{ $actionLabels[$action] ?? $action }}
                                </button>
                            </form>
                        @endif
                    @endforeach

                    @if (in_array('cancel', $allowedActions, true))
                        <button type="button" @click="showCancel = true"
                                class="px-5 py-3 rounded-xl bg-white dark:bg-gray-800 border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 font-semibold">
                            ยกเลิกออเดอร์
                        </button>
                    @endif
                </div>

                {{-- ยืนยันการยกเลิก (ต้องมีเหตุผล) --}}
                <div x-show="showCancel" x-cloak class="mt-4 p-4 rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20">
                    <form method="POST" action="{{ route('taladsod.seller.orders.status', $order) }}"
                          onsubmit="return confirm('ยืนยันยกเลิกออเดอร์นี้? ระบบจะคืนเงินให้ลูกค้าอัตโนมัติ (ถ้าชำระแล้ว)');">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="action" value="cancel">
                        <label class="block text-sm font-medium text-red-800 dark:text-red-300 mb-2">เหตุผลที่ยกเลิก</label>
                        <textarea name="reason" rows="2" required maxlength="500"
                                  class="w-full rounded-lg border-red-300 dark:border-red-700 dark:bg-gray-800 dark:text-white text-sm"
                                  placeholder="เช่น สินค้าหมด / ร้านปิดวันนี้">{{ old('reason') }}</textarea>
                        <div class="mt-3 flex gap-2">
                            <button type="submit" class="px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm font-semibold">ยืนยันยกเลิก</button>
                            <button type="button" @click="showCancel = false" class="px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm">ปิด</button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endsection
