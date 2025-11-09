@extends('layouts.seller')

@section('title', 'Product Performance - AI Analysis')

@section('content')
<div class="space-y-6">
    <div class="bg-gradient-to-r from-orange-600 to-red-600 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-4xl font-bold">🏆 Product Performance Matrix</h1>
                <p class="text-lg mt-2">AI Ranking สินค้าตามยอดขาย, จำนวน, และราคา</p>
            </div>
            <a href="{{ route('seller.analytics.ai-insights') }}" class="px-6 py-3 bg-white/20 backdrop-blur-sm rounded-xl hover:bg-white/30 transition">
                ← กลับ AI Insights
            </a>
        </div>
    </div>

    @if(!empty($products))
    <!-- Top 3 Products -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach(array_slice($products, 0, 3) as $index => $product)
        <div class="bg-gradient-to-br {{ $index === 0 ? 'from-yellow-400 to-yellow-600' : ($index === 1 ? 'from-gray-300 to-gray-500' : 'from-orange-400 to-orange-600') }} rounded-2xl shadow-xl p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="text-5xl">{{ $index === 0 ? '🥇' : ($index === 1 ? '🥈' : '🥉') }}</div>
                <div class="text-3xl font-bold">#{{ $index + 1 }}</div>
            </div>
            <h3 class="font-bold text-xl mb-2 truncate">{{ $product['product_name'] }}</h3>
            <div class="space-y-2 text-sm">
                <p><span class="opacity-80">Revenue:</span> <span class="font-bold">฿{{ number_format($product['revenue'], 2) }}</span></p>
                <p><span class="opacity-80">Orders:</span> <span class="font-bold">{{ $product['orders'] }}</span></p>
                <p><span class="opacity-80">Quantity:</span> <span class="font-bold">{{ $product['quantity_sold'] }}</span></p>
                <p><span class="opacity-80">AI Score:</span> <span class="font-bold">{{ $product['performance'] }}/100</span></p>
            </div>
        </div>
        @endforeach
    </div>

    <!-- All Products Table -->
    <div class="bg-white rounded-2xl shadow-xl p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">📊 Full Performance Matrix</h2>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rank</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Orders</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qty Sold</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Revenue</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Avg Price</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">AI Score</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($products as $index => $product)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm">
                            <span class="font-bold {{ $index < 3 ? 'text-yellow-600' : 'text-gray-600' }}">
                                #{{ $index + 1 }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $product['product_name'] }}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-700">{{ number_format($product['orders']) }}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-700">{{ number_format($product['quantity_sold']) }}</td>
                        <td class="px-4 py-3 text-sm text-right font-semibold text-green-600">฿{{ number_format($product['revenue'], 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-700">฿{{ number_format($product['avg_price'], 2) }}</td>
                        <td class="px-4 py-3 text-center">
                            <div class="inline-flex items-center px-3 py-1 rounded-full font-bold text-sm
                                {{ $product['performance'] >= 70 ? 'bg-green-100 text-green-800' :
                                   ($product['performance'] >= 40 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                {{ $product['performance'] }}/100
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6 p-4 bg-purple-50 rounded-lg">
            <h3 class="font-bold text-gray-800 mb-2">🤖 AI Scoring Algorithm:</h3>
            <ul class="text-sm text-gray-700 space-y-1 list-disc list-inside">
                <li><strong>Revenue Weight:</strong> 50% - ยอดขายรวม</li>
                <li><strong>Quantity Weight:</strong> 30% - จำนวนที่ขาย</li>
                <li><strong>Order Count Weight:</strong> 20% - จำนวนออเดอร์</li>
                <li>คะแนนสูง = สินค้าที่ทำเงินได้ดีที่สุด</li>
            </ul>
        </div>
    </div>
    @else
    <div class="bg-white rounded-2xl shadow-xl p-12 text-center">
        <div class="text-6xl mb-4">📦</div>
        <p class="text-xl text-gray-600">ยังไม่มีข้อมูลการขายในช่วง 30 วันที่ผ่านมา</p>
    </div>
    @endif
</div>
@endsection
