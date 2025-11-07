@extends('layouts.seller')

@section('title', 'รายงานและสถิติ POS')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">📊 รายงานและสถิติ POS</h1>
            <p class="text-gray-500 mt-1">วิเคราะห์ข้อมูลและรายงานโดยละเอียด</p>
        </div>
        <a href="{{ route('seller.pos.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
            ← กลับ
        </a>
    </div>

    <!-- Date Range Filter -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <form method="GET" class="flex items-end gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">วันที่เริ่ม</label>
                <input type="date" name="date_from" value="{{ request('date_from', $dateFrom) }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">วันที่สิ้นสุด</label>
                <input type="date" name="date_to" value="{{ request('date_to', $dateTo) }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                ค้นหา
            </button>
        </form>
    </div>

    <!-- Sales by Payment Method -->
    @if(isset($analytics['sales_by_payment']) && $analytics['sales_by_payment']->count() > 0)
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">📊 วิเคราะห์ตามวิธีการชำระเงิน</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($analytics['sales_by_payment'] as $method)
            <div class="p-4 rounded-lg
                {{ $method->payment_method === 'cash' ? 'bg-green-50' : ($method->payment_method === 'card' ? 'bg-blue-50' : 'bg-purple-50') }}">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center">
                        @if($method->payment_method === 'cash')
                            <span class="text-3xl mr-2">💵</span>
                        @elseif($method->payment_method === 'card')
                            <span class="text-3xl mr-2">💳</span>
                        @else
                            <span class="text-3xl mr-2">📱</span>
                        @endif
                        <div>
                            <div class="text-sm font-medium text-gray-700">
                                {{ $method->payment_method === 'cash' ? 'เงินสด' : ($method->payment_method === 'card' ? 'บัตร' : 'QR Code') }}
                            </div>
                            <div class="text-xs text-gray-500">{{ number_format($method->count) }} รายการ</div>
                        </div>
                    </div>
                </div>
                <div class="text-2xl font-bold
                    {{ $method->payment_method === 'cash' ? 'text-green-600' : ($method->payment_method === 'card' ? 'text-blue-600' : 'text-purple-600') }}">
                    ฿{{ number_format($method->total, 2) }}
                </div>
                <div class="text-xs text-gray-500 mt-1">
                    เฉลี่ย ฿{{ number_format($method->total / $method->count, 2) }}/รายการ
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Hourly Sales Analysis -->
    @if(isset($analytics['hourly_sales']) && $analytics['hourly_sales']->count() > 0)
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">🕐 ยอดขายแยกตามชั่วโมง</h3>
        <div class="overflow-x-auto">
            <div class="flex gap-2">
                @foreach($analytics['hourly_sales'] as $hourData)
                <div class="flex-shrink-0 w-24">
                    <div class="text-center mb-2">
                        <div class="h-32 bg-gray-100 rounded-lg relative overflow-hidden">
                            @php
                                $maxHeight = $analytics['hourly_sales']->max('total') ?: 1;
                                $heightPercent = ($hourData->total / $maxHeight) * 100;
                            @endphp
                            <div class="absolute bottom-0 left-0 right-0 bg-blue-500 transition-all"
                                 style="height: {{ $heightPercent }}%"
                                 title="฿{{ number_format($hourData->total, 2) }}">
                            </div>
                        </div>
                        <div class="text-xs text-gray-600 mt-2">
                            {{ str_pad($hourData->hour, 2, '0', STR_PAD_LEFT) }}:00
                        </div>
                        <div class="text-xs font-medium text-gray-900">
                            {{ $hourData->count }}
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        <p class="text-xs text-gray-500 mt-4 text-center">แกนตั้ง: จำนวนรายการ | สีฟ้า: มูลค่ายอดขาย</p>
    </div>
    @endif

    <!-- Device Performance -->
    @if(isset($analytics['sales_by_device']) && $analytics['sales_by_device']->count() > 0)
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">🏆 ประสิทธิภาพอุปกรณ์</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">อุปกรณ์</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">รายการ</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">ยอดขาย</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">เฉลี่ย/รายการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($analytics['sales_by_device'] as $index => $device)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm">
                            @if($index == 0)
                                <span class="text-2xl">🥇</span>
                            @elseif($index == 1)
                                <span class="text-2xl">🥈</span>
                            @elseif($index == 2)
                                <span class="text-2xl">🥉</span>
                            @else
                                <span class="text-gray-500">{{ $index + 1 }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-gray-900">{{ $device->device_name }}</div>
                            <div class="text-xs text-gray-500">{{ $device->device_code }}</div>
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-gray-900">
                            {{ number_format($device->transaction_count) }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="text-sm font-bold text-green-600">
                                ฿{{ number_format($device->total_sales, 2) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-gray-600">
                            ฿{{ number_format($device->total_sales / $device->transaction_count, 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Top Products -->
    @if(isset($analytics['top_products']) && $analytics['top_products']->count() > 0)
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">🌟 สินค้าขายดี (Top 20)</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">สินค้า</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">จำนวนขาย</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">ยอดขาย</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($analytics['top_products'] as $index => $product)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm">
                            @if($index == 0)
                                <span class="text-2xl">🥇</span>
                            @elseif($index == 1)
                                <span class="text-2xl">🥈</span>
                            @elseif($index == 2)
                                <span class="text-2xl">🥉</span>
                            @else
                                <span class="text-gray-500">{{ $index + 1 }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-gray-900">{{ $product->product_name }}</div>
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-gray-900">
                            {{ number_format($product->total_quantity) }} ชิ้น
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="text-sm font-bold text-green-600">
                                ฿{{ number_format($product->total_sales, 2) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @if(isset($analytics['sales_by_device']))
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
            <div class="text-4xl mb-3">📱</div>
            <div class="text-sm opacity-80 mb-1">อุปกรณ์ที่มีข้อมูล</div>
            <div class="text-3xl font-bold">{{ $analytics['sales_by_device']->count() }}</div>
            <div class="text-xs opacity-70 mt-2">อุปกรณ์ในช่วงเวลาที่เลือก</div>
        </div>
        @endif

        @if(isset($analytics['sales_by_payment']))
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
            <div class="text-4xl mb-3">💰</div>
            <div class="text-sm opacity-80 mb-1">ยอดขายรวม</div>
            <div class="text-3xl font-bold">฿{{ number_format($analytics['sales_by_payment']->sum('total'), 2) }}</div>
            <div class="text-xs opacity-70 mt-2">{{ number_format($analytics['sales_by_payment']->sum('count')) }} รายการ</div>
        </div>
        @endif

        @if(isset($analytics['top_products']))
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
            <div class="text-4xl mb-3">🌟</div>
            <div class="text-sm opacity-80 mb-1">สินค้าที่ขาย</div>
            <div class="text-3xl font-bold">{{ $analytics['top_products']->count() }}</div>
            <div class="text-xs opacity-70 mt-2">รายการสินค้าในช่วงเวลาที่เลือก</div>
        </div>
        @endif
    </div>
</div>
@endsection
