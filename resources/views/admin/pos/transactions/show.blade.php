@extends('layouts.admin')

@section('title', 'รายละเอียดรายการขาย')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">💰 รายละเอียดรายการขาย</h1>
            <p class="text-gray-500 mt-1">รหัส: {{ $transaction->transaction_code }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.pos.transactions.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                ← กลับ
            </a>
            <a href="{{ route('admin.pos.transactions.receipt', $transaction) }}" target="_blank" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                📄 ดูใบเสร็จ
            </a>
        </div>
    </div>

    <!-- Transaction Info -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Transaction Details -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">ข้อมูลรายการ</h3>
            <div class="space-y-3">
                <div>
                    <span class="text-sm text-gray-500">รหัสรายการ</span>
                    <div class="text-sm font-medium text-gray-900 mt-1">{{ $transaction->transaction_code }}</div>
                </div>
                @if($transaction->receipt_number)
                <div>
                    <span class="text-sm text-gray-500">เลขใบเสร็จ</span>
                    <div class="text-sm font-medium text-gray-900 mt-1">{{ $transaction->receipt_number }}</div>
                </div>
                @endif
                <div>
                    <span class="text-sm text-gray-500">วันที่</span>
                    <div class="text-sm font-medium text-gray-900 mt-1">{{ $transaction->transaction_date->format('d/m/Y H:i:s') }}</div>
                </div>
                <div>
                    <span class="text-sm text-gray-500">สถานะ</span>
                    <div class="mt-1">
                        @if($transaction->status === 'completed')
                            <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">✅ สำเร็จ</span>
                        @elseif($transaction->status === 'refunded')
                            <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm font-medium">↩️ คืนเงิน</span>
                        @elseif($transaction->status === 'void')
                            <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm font-medium">❌ ยกเลิก</span>
                        @else
                            <span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-sm font-medium">{{ $transaction->status }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Store & Device Info -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">ร้านค้าและอุปกรณ์</h3>
            <div class="space-y-3">
                <div>
                    <span class="text-sm text-gray-500">ร้านค้า</span>
                    <div class="text-sm font-medium text-gray-900 mt-1">{{ $transaction->store->store_name ?? 'N/A' }}</div>
                    @if($transaction->store)
                    <div class="text-xs text-gray-500">{{ $transaction->store->store_code }}</div>
                    @endif
                </div>
                <div>
                    <span class="text-sm text-gray-500">อุปกรณ์</span>
                    <div class="text-sm font-medium text-gray-900 mt-1">{{ $transaction->posDevice->device_name ?? 'N/A' }}</div>
                    @if($transaction->posDevice)
                    <div class="text-xs text-gray-500">{{ $transaction->posDevice->device_code }}</div>
                    @endif
                </div>
                <div>
                    <span class="text-sm text-gray-500">พนักงานขาย</span>
                    <div class="text-sm font-medium text-gray-900 mt-1">{{ $transaction->user->name ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        <!-- Customer Info -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">ข้อมูลลูกค้า</h3>
            <div class="space-y-3">
                @if($transaction->customer_name || $transaction->customer_phone)
                    <div>
                        <span class="text-sm text-gray-500">ชื่อลูกค้า</span>
                        <div class="text-sm font-medium text-gray-900 mt-1">{{ $transaction->customer_name ?? '-' }}</div>
                    </div>
                    @if($transaction->customer_phone)
                    <div>
                        <span class="text-sm text-gray-500">เบอร์โทร</span>
                        <div class="text-sm font-medium text-gray-900 mt-1">{{ $transaction->customer_phone }}</div>
                    </div>
                    @endif
                    @if($transaction->customer_email)
                    <div>
                        <span class="text-sm text-gray-500">อีเมล</span>
                        <div class="text-sm font-medium text-gray-900 mt-1">{{ $transaction->customer_email }}</div>
                    </div>
                    @endif
                @else
                    <div class="text-center py-4">
                        <span class="text-4xl">🚶</span>
                        <p class="text-sm text-gray-500 mt-2">Walk-in Customer</p>
                        <p class="text-xs text-gray-400">ไม่มีข้อมูลลูกค้า</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Payment Info -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Payment Details -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">การชำระเงิน</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">วิธีชำระเงิน</span>
                    <div>
                        @if($transaction->payment_method === 'cash')
                            <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">💵 เงินสด</span>
                        @elseif($transaction->payment_method === 'card')
                            <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">💳 บัตร</span>
                        @elseif($transaction->payment_method === 'qr')
                            <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-sm font-medium">📱 QR Code</span>
                        @else
                            <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm font-medium">{{ $transaction->payment_method }}</span>
                        @endif
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">สถานะการชำระเงิน</span>
                    <div>
                        @if($transaction->payment_status === 'paid')
                            <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm">ชำระแล้ว</span>
                        @else
                            <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">{{ $transaction->payment_status }}</span>
                        @endif
                    </div>
                </div>
                @if($transaction->payment_reference)
                <div>
                    <span class="text-sm text-gray-500">หมายเลขอ้างอิง</span>
                    <div class="text-sm font-medium text-gray-900 mt-1">{{ $transaction->payment_reference }}</div>
                </div>
                @endif
            </div>
        </div>

        <!-- Amount Summary -->
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
            <h3 class="text-lg font-bold mb-4">สรุปยอดเงิน</h3>
            <div class="space-y-2">
                <div class="flex items-center justify-between text-sm opacity-90">
                    <span>ยอดรวมสินค้า</span>
                    <span>฿{{ number_format($transaction->subtotal, 2) }}</span>
                </div>
                @if($transaction->discount_amount > 0)
                <div class="flex items-center justify-between text-sm opacity-90">
                    <span>ส่วนลด</span>
                    <span>-฿{{ number_format($transaction->discount_amount, 2) }}</span>
                </div>
                @endif
                @if($transaction->tax_amount > 0)
                <div class="flex items-center justify-between text-sm opacity-90">
                    <span>ภาษี</span>
                    <span>฿{{ number_format($transaction->tax_amount, 2) }}</span>
                </div>
                @endif
                <div class="border-t border-white/30 pt-2 mt-2"></div>
                <div class="flex items-center justify-between">
                    <span class="text-lg font-bold">ยอดชำระทั้งหมด</span>
                    <span class="text-2xl font-bold">฿{{ number_format($transaction->total_amount, 2) }}</span>
                </div>
                @if($transaction->payment_method === 'cash' && $transaction->cash_received)
                <div class="border-t border-white/30 pt-2 mt-2 text-sm">
                    <div class="flex items-center justify-between opacity-90">
                        <span>รับเงินสด</span>
                        <span>฿{{ number_format($transaction->cash_received, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between opacity-90 mt-1">
                        <span>เงินทอน</span>
                        <span>฿{{ number_format($transaction->change_amount, 2) }}</span>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Transaction Items -->
    @if($transaction->items && $transaction->items->count() > 0)
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">รายการสินค้า</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">สินค้า</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">ราคา</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">จำนวน</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">ส่วนลด</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">รวม</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($transaction->items as $index => $item)
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-900">{{ $index + 1 }}</td>
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-gray-900">{{ $item->product_name }}</div>
                            @if($item->product_sku)
                            <div class="text-xs text-gray-500">SKU: {{ $item->product_sku }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-gray-900">฿{{ number_format($item->unit_price, 2) }}</td>
                        <td class="px-4 py-3 text-center text-sm text-gray-900">{{ $item->quantity }}</td>
                        <td class="px-4 py-3 text-right text-sm text-red-600">
                            @if($item->discount_amount > 0)
                                -฿{{ number_format($item->discount_amount, 2) }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-bold text-gray-900">
                            ฿{{ number_format($item->total_amount, 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="5" class="px-4 py-3 text-right text-sm font-bold text-gray-900">รวมทั้งหมด</td>
                        <td class="px-4 py-3 text-right text-lg font-bold text-green-600">
                            ฿{{ number_format($transaction->total_amount, 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif

    <!-- Actions -->
    @if(auth()->user()->isSuperAdmin() && $transaction->status === 'completed')
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">การจัดการ</h3>
        <div class="flex gap-4">
            <form action="{{ route('admin.pos.transactions.refund', $transaction) }}" method="POST"
                  onsubmit="return confirm('ต้องการคืนเงินรายการนี้?\n\nจำนวน: ฿{{ number_format($transaction->total_amount, 2) }}')">
                @csrf
                <button type="submit" class="px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    ↩️ คืนเงิน
                </button>
            </form>
            <a href="{{ route('admin.pos.transactions.receipt', $transaction) }}" target="_blank"
               class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                📄 พิมพ์ใบเสร็จ
            </a>
        </div>
    </div>
    @endif

    <!-- Notes -->
    @if($transaction->notes)
    <div class="bg-yellow-50 rounded-xl shadow p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-2">📝 หมายเหตุ</h3>
        <p class="text-sm text-gray-700">{{ $transaction->notes }}</p>
    </div>
    @endif
</div>

@if(session('success'))
<script>
    alert('{{ session("success") }}');
</script>
@endif
@endsection
