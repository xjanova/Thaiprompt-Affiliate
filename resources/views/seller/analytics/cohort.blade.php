@extends('layouts.seller')

@section('title', 'Cohort Analysis')

@section('content')
<div class="space-y-6">
    <div class="bg-gradient-to-r from-green-600 to-teal-600 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-4xl font-bold">📊 Cohort Analysis</h1>
                <p class="text-lg mt-2">ติดตามพฤติกรรมลูกค้าตามช่วงเวลา</p>
            </div>
            <a href="{{ route('seller.analytics.ai-insights') }}" class="px-6 py-3 bg-white/20 backdrop-blur-sm rounded-xl hover:bg-white/30 transition">
                ← กลับ AI Insights
            </a>
        </div>
    </div>

    @if(!empty($cohorts))
    <div class="bg-white rounded-2xl shadow-xl p-6 overflow-x-auto">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Retention Rate by Cohort</h2>

        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Cohort Month</th>
                    <th class="px-4 py-3 text-center font-medium text-gray-700">Size</th>
                    @for($i = 0; $i <= 6; $i++)
                    <th class="px-4 py-3 text-center font-medium text-gray-700">Month {{ $i }}</th>
                    @endfor
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($cohorts as $cohort)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-semibold text-gray-900">{{ $cohort['cohort'] }}</td>
                    <td class="px-4 py-3 text-center font-semibold text-blue-600">{{ $cohort['size'] }}</td>
                    @foreach($cohort['retention'] as $month => $data)
                    <td class="px-4 py-3 text-center">
                        <div class="inline-block px-3 py-1 rounded-lg font-semibold"
                             style="background-color: rgba(34, 197, 94, {{ $data['rate'] / 100 }});">
                            {{ $data['rate'] }}%
                        </div>
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-6 p-4 bg-blue-50 rounded-lg">
            <h3 class="font-bold text-gray-800 mb-2">📖 How to Read:</h3>
            <ul class="text-sm text-gray-700 space-y-1 list-disc list-inside">
                <li>แต่ละแถว = กลุ่มลูกค้าที่เริ่มซื้อในเดือนนั้น</li>
                <li>Month 0 = เดือนแรกที่ซื้อ (100%)</li>
                <li>Month 1+ = % ลูกค้าที่กลับมาซื้ออีกในเดือนนั้น</li>
                <li>สีเข้มขึ้น = Retention Rate สูงขึ้น</li>
            </ul>
        </div>
    </div>
    @else
    <div class="bg-white rounded-2xl shadow-xl p-12 text-center">
        <div class="text-6xl mb-4">📊</div>
        <p class="text-xl text-gray-600">ยังไม่มีข้อมูลเพียงพอสำหรับ Cohort Analysis</p>
    </div>
    @endif
</div>
@endsection
