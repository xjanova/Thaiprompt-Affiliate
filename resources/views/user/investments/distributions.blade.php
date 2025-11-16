@extends('layouts.user-arrow-x')

@section('title', 'การจ่ายผลตอบแทน')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-emerald-600 via-green-600 to-teal-600 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                <span class="text-3xl">💸</span>
            </div>
            <div>
                <h1 class="text-3xl font-bold">การจ่ายผลตอบแทน</h1>
                <p class="text-emerald-100 mt-1">ประวัติการรับผลตอบแทนจากการลงทุน</p>
            </div>
        </div>
    </div>

    @if(isset($position))
        <!-- Investment Summary Card -->
        <div class="bg-white rounded-2xl shadow-xl p-6">
            <div class="grid md:grid-cols-2 gap-6">
                <!-- Left: Investment Details -->
                <div>
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <span>💼</span> ข้อมูลการลงทุน
                    </h2>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center py-2 border-b">
                            <span class="text-gray-600">แผนการลงทุน:</span>
                            <span class="font-bold text-gray-800">{{ $position->plan->name ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b">
                            <span class="text-gray-600">จำนวนลงทุน:</span>
                            <span class="font-bold text-blue-600">฿{{ number_format($position->amount ?? 0, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b">
                            <span class="text-gray-600">อัตราผลตอบแทน:</span>
                            <span class="font-bold text-purple-600">{{ $position->plan->roi_percentage ?? 0 }}% / {{ $position->plan->roi_period ?? 'month' }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-gray-600">สถานะ:</span>
                            <span class="px-3 py-1 rounded-full font-bold text-sm
                                @if($position->status === 'active') bg-green-100 text-green-800
                                @elseif($position->status === 'completed') bg-blue-100 text-blue-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                @if($position->status === 'active') ✅ ใช้งาน
                                @elseif($position->status === 'completed') 🎉 เสร็จสิ้น
                                @else {{ $position->status }}
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Right: Returns Summary -->
                <div>
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <span>📊</span> สรุปผลตอบแทน
                    </h2>
                    <div class="space-y-3">
                        <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-4 border border-green-200">
                            <div class="text-sm text-gray-600 mb-1">รวมรับแล้ว</div>
                            <div class="text-3xl font-bold text-green-600">
                                ฿{{ number_format($distributions->sum('amount') ?? 0, 2) }}
                            </div>
                        </div>
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-4 border border-blue-200">
                            <div class="text-sm text-gray-600 mb-1">จำนวนครั้ง</div>
                            <div class="text-3xl font-bold text-blue-600">
                                {{ $distributions->count() }} ครั้ง
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Distributions Table -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                    <span>📋</span> ประวัติการจ่ายผลตอบแทน
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100 border-b-2 border-gray-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">#</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">วันที่</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">ประเภท</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">จำนวนเงิน</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">สถานะ</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">หมายเหตุ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($distributions as $index => $distribution)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ $index + 1 }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $distribution->distributed_at ? $distribution->distributed_at->format('d/m/Y') : '-' }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ $distribution->distributed_at ? $distribution->distributed_at->format('H:i') : '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full
                                        @if($distribution->type === 'roi') bg-blue-100 text-blue-800
                                        @elseif($distribution->type === 'dividend') bg-purple-100 text-purple-800
                                        @elseif($distribution->type === 'bonus') bg-orange-100 text-orange-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        @if($distribution->type === 'roi') 📈 ผลตอบแทน
                                        @elseif($distribution->type === 'dividend') 💰 เงินปันผล
                                        @elseif($distribution->type === 'bonus') 🎁 โบนัส
                                        @else {{ ucfirst($distribution->type) }}
                                        @endif
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-lg font-bold text-green-600">
                                        ฿{{ number_format($distribution->amount ?? 0, 2) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($distribution->status === 'paid')
                                        <span class="px-3 py-1 text-xs font-semibold bg-green-100 text-green-800 rounded-full">
                                            ✅ จ่ายแล้ว
                                        </span>
                                    @elseif($distribution->status === 'pending')
                                        <span class="px-3 py-1 text-xs font-semibold bg-yellow-100 text-yellow-800 rounded-full">
                                            ⏳ รอจ่าย
                                        </span>
                                    @elseif($distribution->status === 'cancelled')
                                        <span class="px-3 py-1 text-xs font-semibold bg-red-100 text-red-800 rounded-full">
                                            ❌ ยกเลิก
                                        </span>
                                    @else
                                        <span class="px-3 py-1 text-xs font-semibold bg-gray-100 text-gray-800 rounded-full">
                                            {{ $distribution->status }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $distribution->notes ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <span class="text-4xl mb-4 block">💸</span>
                                    <p class="text-gray-600">ยังไม่มีการจ่ายผลตอบแทน</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Distribution Schedule (if available) -->
        @if(isset($position->plan->distribution_schedule))
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl shadow-xl p-6 border border-blue-200">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <span>📅</span> ตารางการจ่ายผลตอบแทน
                </h3>
                <div class="text-sm text-gray-700">
                    <p><strong>ความถี่:</strong> {{ $position->plan->distribution_frequency ?? 'Monthly' }}</p>
                    <p><strong>วันที่จ่าย:</strong> {{ $position->plan->distribution_day ?? 'วันที่ 1 ของเดือน' }}</p>
                    <p><strong>ระยะเวลา:</strong> {{ $position->plan->duration ?? 'N/A' }} เดือน</p>
                </div>
            </div>
        @endif

        <!-- Back Button -->
        <div class="text-center">
            <a href="{{ route('user.investments.show', $position->id) }}"
               class="inline-block px-8 py-4 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl font-bold transition-colors">
                ← กลับไปดูรายละเอียดการลงทุน
            </a>
        </div>
    @else
        <!-- No Investment Found -->
        <div class="bg-white rounded-2xl shadow-xl p-12 text-center">
            <span class="text-6xl mb-4 block">❌</span>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">ไม่พบข้อมูลการลงทุน</h2>
            <p class="text-gray-600 mb-6">ไม่สามารถโหลดข้อมูลการลงทุนได้</p>
            <a href="{{ route('user.investments.index') }}"
               class="inline-block px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold transition-colors">
                ← กลับไปหน้าการลงทุน
            </a>
        </div>
    @endif
</div>
@endsection
