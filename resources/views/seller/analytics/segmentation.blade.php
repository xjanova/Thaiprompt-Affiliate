@extends('layouts.seller')

@section('title', 'Customer Segmentation - RFM Analysis')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-4xl font-bold">👥 Customer Segmentation</h1>
                <p class="text-lg mt-2">RFM Analysis: Recency, Frequency, Monetary</p>
            </div>
            <a href="{{ route('seller.analytics.ai-insights') }}" class="px-6 py-3 bg-white/20 backdrop-blur-sm rounded-xl hover:bg-white/30 transition">
                ← กลับ AI Insights
            </a>
        </div>
    </div>

    <!-- Segment Overview -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div class="bg-gradient-to-br from-yellow-400 to-orange-500 rounded-xl shadow-lg p-6 text-white">
            <div class="text-3xl mb-2">🏆</div>
            <h3 class="font-bold text-lg">Champions</h3>
            <p class="text-3xl font-bold mt-2">{{ count($rfmSegments['champions']) }}</p>
            <p class="text-sm mt-1 opacity-90">ลูกค้า VIP สุดๆ</p>
        </div>

        <div class="bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl shadow-lg p-6 text-white">
            <div class="text-3xl mb-2">💙</div>
            <h3 class="font-bold text-lg">Loyal</h3>
            <p class="text-3xl font-bold mt-2">{{ count($rfmSegments['loyal']) }}</p>
            <p class="text-sm mt-1 opacity-90">ลูกค้าประจำ</p>
        </div>

        <div class="bg-gradient-to-br from-green-400 to-green-600 rounded-xl shadow-lg p-6 text-white">
            <div class="text-3xl mb-2">🌱</div>
            <h3 class="font-bold text-lg">Potential</h3>
            <p class="text-3xl font-bold mt-2">{{ count($rfmSegments['potential']) }}</p>
            <p class="text-sm mt-1 opacity-90">มีศักยภาพ</p>
        </div>

        <div class="bg-gradient-to-br from-orange-400 to-red-500 rounded-xl shadow-lg p-6 text-white">
            <div class="text-3xl mb-2">⚠️</div>
            <h3 class="font-bold text-lg">At Risk</h3>
            <p class="text-3xl font-bold mt-2">{{ count($rfmSegments['at_risk']) }}</p>
            <p class="text-sm mt-1 opacity-90">กำลังจะหาย</p>
        </div>

        <div class="bg-gradient-to-br from-gray-400 to-gray-600 rounded-xl shadow-lg p-6 text-white">
            <div class="text-3xl mb-2">💔</div>
            <h3 class="font-bold text-lg">Lost</h3>
            <p class="text-3xl font-bold mt-2">{{ count($rfmSegments['lost']) }}</p>
            <p class="text-sm mt-1 opacity-90">หายไปแล้ว</p>
        </div>
    </div>

    <!-- Detailed Segments -->
    @foreach(['champions' => '🏆 Champions', 'loyal' => '💙 Loyal Customers', 'potential' => '🌱 Potential', 'at_risk' => '⚠️ At Risk', 'lost' => '💔 Lost Customers'] as $key => $title)
    @if(!empty($rfmSegments[$key]))
    <div class="bg-white rounded-2xl shadow-xl p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">{{ $title }} ({{ count($rfmSegments[$key]) }})</h2>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Recency (days)</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Frequency</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Monetary</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">RFM Score</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach(array_slice($rfmSegments[$key], 0, 10) as $customer)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $customer['user_name'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $customer['recency'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $customer['frequency'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">฿{{ number_format($customer['monetary'], 2) }}</td>
                        <td class="px-4 py-3 text-sm">
                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full font-bold">
                                {{ number_format($customer['rfm_score'], 2) }}/5
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if(count($rfmSegments[$key]) > 10)
        <p class="text-sm text-gray-500 mt-4 text-center">และอีก {{ count($rfmSegments[$key]) - 10 }} คน...</p>
        @endif
    </div>
    @endif
    @endforeach
</div>
@endsection
