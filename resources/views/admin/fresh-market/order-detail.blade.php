{{--
    รายละเอียดออเดอร์ตลาดสด (แอดมิน)

    ตัวแปร: $order (with buyer, seller.user, listing, riderJob.rider), $allowedActions (admin), $canRedispatch,
            $walletTransactions, $platformTransactions, $gpDebt (WalletDebt|null), $history (status_history array)
    ฟอร์ม: POST admin.fresh-market.orders.cancel (reason*), orders.complete (reason), orders.redispatch
    หน้านี้เป็นเวอร์ชันใช้งานได้ก่อน — จะถูกสร้างใหม่ในธีม V4
--}}
@extends('layouts.admin-v3')

@section('title', 'ออเดอร์ #'.$order->order_number.' - ตลาดสดไทยพร๊อม')

@section('content')
<div class="space-y-6" x-data="{ showCancel: false }">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">ออเดอร์ #{{ $order->order_number }}</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $order->status_label }} • {{ $order->payment_method_label }} • {{ $order->payment_status_label }}</p>
        </div>
        <a href="{{ route('admin.fresh-market.orders') }}" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl">← รายการออเดอร์</a>
    </div>

    @if (session('success'))
        <div class="p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300">{{ $errors->first() }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl shadow p-5 space-y-3 text-sm">
            <div class="grid grid-cols-2 gap-3">
                <div><span class="text-gray-500 dark:text-gray-400">ผู้ซื้อ</span><p class="font-medium text-gray-900 dark:text-white">{{ $order->buyer?->name ?? '-' }} (#{{ $order->buyer_id }}) {{ $order->buyer?->phone }}</p></div>
                <div><span class="text-gray-500 dark:text-gray-400">ร้าน</span><p class="font-medium text-gray-900 dark:text-white">{{ $order->seller?->shop_name ?? '-' }} {{ $order->seller?->phone }}</p></div>
                <div><span class="text-gray-500 dark:text-gray-400">สินค้า</span><p class="font-medium text-gray-900 dark:text-white">{{ $order->riderItemsSummary() }}</p></div>
                <div><span class="text-gray-500 dark:text-gray-400">การจัดส่ง</span><p class="font-medium text-gray-900 dark:text-white">{{ $order->delivery_type === 'rider' ? 'ไรเดอร์' : 'รับเอง' }} {{ $order->delivery_address }}</p></div>
                <div><span class="text-gray-500 dark:text-gray-400">ยอดสินค้า / ค่าส่ง</span><p class="font-medium text-gray-900 dark:text-white">฿{{ number_format((float) $order->total_amount, 2) }} / ฿{{ number_format((float) $order->delivery_fee, 2) }}</p></div>
                <div><span class="text-gray-500 dark:text-gray-400">GP ({{ $order->gp_rate ?? '-' }}%) / ร้านได้รับ</span><p class="font-medium text-gray-900 dark:text-white">฿{{ number_format((float) $order->platform_fee, 2) }} / ฿{{ number_format((float) $order->seller_earning, 2) }}</p></div>
                <div><span class="text-gray-500 dark:text-gray-400">Escrow</span><p class="font-medium text-gray-900 dark:text-white">{{ $order->escrow_status ?? '-' }}</p></div>
                <div><span class="text-gray-500 dark:text-gray-400">คืนเงินแล้ว</span><p class="font-medium text-gray-900 dark:text-white">฿{{ number_format((float) $order->refunded_amount, 2) }}</p></div>
                @if ($order->riderJob)
                    <div class="col-span-2"><span class="text-gray-500 dark:text-gray-400">งานไรเดอร์</span><p class="font-medium text-gray-900 dark:text-white">#{{ $order->riderJob->job_number }} • {{ $order->riderJob->status }} • {{ $order->riderJob->rider?->full_name ?? 'ยังไม่มีไรเดอร์' }}</p></div>
                @endif
                @if ($order->cancel_reason)
                    <div class="col-span-2"><span class="text-gray-500 dark:text-gray-400">เหตุผลยกเลิก</span><p class="font-medium text-red-600 dark:text-red-400">{{ $order->cancel_reason }} ({{ $order->cancelled_by }})</p></div>
                @endif
                @if ($gpDebt)
                    <div class="col-span-2"><span class="text-gray-500 dark:text-gray-400">หนี้ค่า GP ของร้าน (COD)</span><p class="font-medium text-orange-600 dark:text-orange-400">คงค้าง ฿{{ number_format((float) $gpDebt->remaining_amount, 2) }} ({{ $gpDebt->status }})</p></div>
                @endif
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-5 space-y-3">
            <h2 class="font-bold text-gray-900 dark:text-white">จัดการออเดอร์</h2>
            @if (in_array('complete', $allowedActions, true))
                <form method="POST" action="{{ route('admin.fresh-market.orders.complete', $order) }}"
                      onsubmit="return confirm('ปิดออเดอร์และโอนเงินให้ร้าน? ทำซ้ำไม่ได้');">
                    @csrf
                    <input type="hidden" name="reason" value="แอดมินปิดออเดอร์แทนผู้ซื้อ">
                    <button class="w-full px-4 py-2 rounded-xl bg-green-600 hover:bg-green-700 text-white font-semibold">ปิดออเดอร์ (ปล่อยเงินให้ร้าน)</button>
                </form>
            @endif
            @if ($canRedispatch)
                <form method="POST" action="{{ route('admin.fresh-market.orders.redispatch', $order) }}"
                      onsubmit="return confirm('สร้างงานไรเดอร์ใหม่ให้ออเดอร์นี้?');">
                    @csrf
                    <button class="w-full px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold">เรียกไรเดอร์ใหม่</button>
                </form>
            @endif
            @if (in_array('cancel', $allowedActions, true))
                <button type="button" @click="showCancel = ! showCancel" class="w-full px-4 py-2 rounded-xl border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 font-semibold">ยกเลิก / คืนเงิน</button>
                <form x-show="showCancel" x-cloak method="POST" action="{{ route('admin.fresh-market.orders.cancel', $order) }}"
                      onsubmit="return confirm('ยืนยันยกเลิก? ระบบคืนเงินเฉพาะยอดที่เก็บมาแล้วจริงเข้า Wallet ผู้ซื้อ');" class="space-y-2">
                    @csrf
                    <textarea name="reason" rows="3" required minlength="3" maxlength="500" placeholder="เหตุผล (บันทึกในประวัติ)"
                              class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></textarea>
                    <button class="w-full px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold">ยืนยันยกเลิก</button>
                </form>
            @endif
            @if (empty($allowedActions) && ! $canRedispatch)
                <p class="text-sm text-gray-500 dark:text-gray-400">ไม่มีการกระทำที่ทำได้ในสถานะนี้</p>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-5">
            <h2 class="font-bold text-gray-900 dark:text-white mb-3">ประวัติสถานะ</h2>
            <ul class="space-y-2 text-sm">
                @forelse (array_reverse($history) as $entry)
                    <li class="text-gray-700 dark:text-gray-300">
                        <span class="text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Carbon::parse($entry['at'] ?? now())->format('d/m H:i') }}</span>
                        • {{ $entry['action'] ?? '-' }} ({{ $entry['from'] ?? '-' }} → {{ $entry['to'] ?? '-' }}) โดย {{ $entry['by'] ?? '-' }}
                        @if (! empty($entry['reason'])) — {{ $entry['reason'] }} @endif
                    </li>
                @empty
                    <li class="text-gray-500 dark:text-gray-400">ไม่มีประวัติ</li>
                @endforelse
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-5">
            <h2 class="font-bold text-gray-900 dark:text-white mb-3">รายการเงิน</h2>
            <ul class="space-y-2 text-sm">
                @foreach ($walletTransactions as $tx)
                    <li class="text-gray-700 dark:text-gray-300">Wallet #{{ $tx->user_id }} • {{ $tx->reference_type }} • {{ $tx->type }} • ฿{{ number_format((float) $tx->amount, 2) }}</li>
                @endforeach
                @foreach ($platformTransactions as $tx)
                    <li class="text-gray-700 dark:text-gray-300">แพลตฟอร์ม • {{ $tx->sub_type }} • {{ $tx->type }} • ฿{{ number_format((float) $tx->amount, 2) }}</li>
                @endforeach
                @if ($walletTransactions->isEmpty() && $platformTransactions->isEmpty())
                    <li class="text-gray-500 dark:text-gray-400">ยังไม่มีรายการเงิน</li>
                @endif
            </ul>
        </div>
    </div>
</div>
@endsection
