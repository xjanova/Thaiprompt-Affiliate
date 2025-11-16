@extends('layouts.user-arrow-x')

@section('title', 'รายละเอียดการลงทุน')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Back Button -->
    <a href="{{ route('user.investments.index') }}" class="inline-flex items-center gap-2 text-purple-600 hover:text-purple-700 font-semibold">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
        กลับ
    </a>

    <!-- Position Header -->
    <div class="bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
            <div>
                <h1 class="text-3xl font-bold mb-2">{{ $position->investmentPlan->display_name }}</h1>
                <p class="text-pink-100">{{ $position->position_number }}</p>
            </div>
            <div class="text-right">
                @if($position->status == 'active')
                    <span class="px-4 py-2 bg-green-500 text-white rounded-full text-sm font-semibold">✓ กำลังลงทุน</span>
                @elseif($position->status == 'pending')
                    <span class="px-4 py-2 bg-yellow-500 text-white rounded-full text-sm font-semibold">⏳ รออนุมัติ</span>
                @elseif($position->status == 'matured')
                    <span class="px-4 py-2 bg-blue-500 text-white rounded-full text-sm font-semibold">✓ ครบกำหนด</span>
                @elseif($position->status == 'withdrawn')
                    <span class="px-4 py-2 bg-gray-500 text-white rounded-full text-sm font-semibold">ถอนแล้ว</span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 bg-opacity-20 rounded-xl p-4 backdrop-blur-sm">
                <p class="text-pink-100 text-xs mb-1">จำนวนลงทุน</p>
                <p class="text-2xl font-bold">฿{{ number_format($position->amount, 2) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 bg-opacity-20 rounded-xl p-4 backdrop-blur-sm">
                <p class="text-pink-100 text-xs mb-1">ROI ที่ได้รับ</p>
                <p class="text-2xl font-bold text-green-300">฿{{ number_format($position->earned_roi, 2) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 bg-opacity-20 rounded-xl p-4 backdrop-blur-sm">
                <p class="text-pink-100 text-xs mb-1">ROI คาดหวัง</p>
                <p class="text-2xl font-bold">฿{{ number_format($position->expected_roi, 2) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 bg-opacity-20 rounded-xl p-4 backdrop-blur-sm">
                <p class="text-pink-100 text-xs mb-1">มูลค่ารวม</p>
                <p class="text-2xl font-bold text-yellow-300">฿{{ number_format($position->total_value, 2) }}</p>
            </div>
        </div>
    </div>

    <!-- Progress Section -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-6">📊 ความคืบหน้า</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">ความคืบหน้า</p>
                <div class="relative inline-flex items-center justify-center">
                    <svg class="w-32 h-32 transform -rotate-90">
                        <circle cx="64" cy="64" r="56" stroke="#e5e7eb" stroke-width="8" fill="none"></circle>
                        <circle cx="64" cy="64" r="56" stroke="#a855f7" stroke-width="8" fill="none"
                                stroke-dasharray="{{ 2 * 3.14159 * 56 }}"
                                stroke-dashoffset="{{ 2 * 3.14159 * 56 * (1 - $position->progress_percentage / 100) }}"
                                stroke-linecap="round"></circle>
                    </svg>
                    <span class="absolute text-2xl font-bold text-purple-600">{{ number_format($position->progress_percentage, 0) }}%</span>
                </div>
            </div>

            <div class="space-y-3">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">วันที่เริ่มต้น</p>
                    <p class="font-semibold text-gray-800 dark:text-white">{{ $position->invested_at ? $position->invested_at->format('d M Y') : '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">วันครบกำหนด</p>
                    <p class="font-semibold text-gray-800 dark:text-white">{{ $position->maturity_date ? $position->maturity_date->format('d M Y') : '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">วันปลดล็อค</p>
                    <p class="font-semibold text-gray-800 dark:text-white">{{ $position->unlock_date ? $position->unlock_date->format('d M Y') : '-' }}</p>
                </div>
            </div>

            <div class="space-y-3">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">วันที่ผ่านไป</p>
                    <p class="font-semibold text-gray-800 dark:text-white">{{ $position->days_elapsed }} วัน</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">วันที่เหลือ</p>
                    <p class="font-semibold text-gray-800 dark:text-white">{{ $position->days_remaining }} วัน</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">วันถึงปลดล็อค</p>
                    <p class="font-semibold text-gray-800 dark:text-white">{{ $position->days_until_unlock }} วัน</p>
                </div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="mb-6">
            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
                <span>วันที่ {{ $position->days_elapsed }}/{{ $position->term_days }}</span>
                <span>{{ number_format($position->progress_percentage, 1) }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-4">
                <div class="bg-gradient-to-r from-purple-500 to-pink-500 h-4 rounded-full transition-all duration-500" style="width: {{ $position->progress_percentage }}%"></div>
            </div>
        </div>

        <!-- ROI Progress -->
        <div>
            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
                <span>ROI ที่ได้รับ</span>
                <span>฿{{ number_format($position->earned_roi, 2) }} / ฿{{ number_format($position->expected_roi, 2) }}</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-4">
                <div class="bg-gradient-to-r from-green-500 to-emerald-500 h-4 rounded-full transition-all duration-500" style="width: {{ $position->roi_progress_percentage }}%"></div>
            </div>
        </div>
    </div>

    <!-- Withdrawal Section -->
    @if($withdrawalInfo)
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-6">💸 ข้อมูลการถอนเงิน</h2>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">เงินต้น</p>
                <p class="font-semibold text-gray-800 dark:text-white">฿{{ number_format($withdrawalInfo['principal'], 2) }}</p>
            </div>
            <div class="bg-green-50 rounded-lg p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">ROI ที่ได้รับ</p>
                <p class="font-semibold text-green-600">฿{{ number_format($withdrawalInfo['earned_roi'], 2) }}</p>
            </div>
            @if($withdrawalInfo['penalty'] > 0)
            <div class="bg-red-50 rounded-lg p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">ค่าปรับ</p>
                <p class="font-semibold text-red-600">-฿{{ number_format($withdrawalInfo['penalty'], 2) }}</p>
            </div>
            @endif
            <div class="bg-purple-50 rounded-lg p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">ยอดสุทธิ</p>
                <p class="font-semibold text-purple-600">฿{{ number_format($withdrawalInfo['net_amount'], 2) }}</p>
            </div>
        </div>

        @if($position->isLocked() && $withdrawalInfo['penalty'] > 0)
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <p class="text-sm text-yellow-800">
                ⚠️ <strong>หมายเหตุ:</strong> การถอนก่อนกำหนดจะมีค่าปรับ {{ $position->investmentPlan->early_withdrawal_penalty }}% จากยอดรวม
            </p>
        </div>
        @endif

        <form action="{{ route('user.investments.withdraw', $position) }}" method="POST" onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะถอนเงิน?');">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">เหตุผลในการถอน (ถ้ามี):</label>
                <textarea name="reason" rows="3" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent" placeholder="ระบุเหตุผล..."></textarea>
            </div>
            <button type="submit" class="w-full px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-semibold">
                ถอนเงินเลย
            </button>
        </form>
    </div>
    @endif

    <!-- ROI Distribution History -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-6">💰 ประวัติการจ่าย ROI</h2>

        @if($position->roiDistributions->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">วันที่</th>
                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">ประเภท</th>
                            <th class="text-right py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">จำนวน</th>
                            <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($position->roiDistributions as $distribution)
                        <tr class="border-b border-gray-100 hover:bg-gray-50 dark:bg-gray-900/50">
                            <td class="py-3 px-4 text-sm text-gray-800 dark:text-white">
                                {{ $distribution->distribution_date->format('d M Y') }}
                            </td>
                            <td class="py-3 px-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ $distribution->display_type }}
                                @if($distribution->day_number)
                                    <span class="text-xs text-gray-400">(วันที่ {{ $distribution->day_number }})</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-sm font-semibold text-right">
                                <span class="text-green-600">+฿{{ number_format($distribution->roi_amount, 2) }}</span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($distribution->status == 'completed')
                                    <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">จ่ายแล้ว</span>
                                @elseif($distribution->status == 'pending')
                                    <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs">รอจ่าย</span>
                                @elseif($distribution->status == 'processing')
                                    <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs">กำลังจ่าย</span>
                                @elseif($distribution->status == 'failed')
                                    <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs">ล้มเหลว</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                ยังไม่มีประวัติการจ่าย ROI
            </div>
        @endif
    </div>

    <!-- Plan Details -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-6">📋 รายละเอียดแผน</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex justify-between py-2 border-b border-gray-100">
                <span class="text-gray-600 dark:text-gray-400">อัตรา ROI:</span>
                <span class="font-semibold text-gray-800 dark:text-white">{{ $position->roi_rate }}% {{ $position->roi_frequency === 'daily' ? 'ต่อวัน' : 'ต่อสัปดาห์' }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-100">
                <span class="text-gray-600 dark:text-gray-400">ระยะเวลา:</span>
                <span class="font-semibold text-gray-800 dark:text-white">{{ $position->term_days }} วัน</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-100">
                <span class="text-gray-600 dark:text-gray-400">ระยะล็อค:</span>
                <span class="font-semibold text-gray-800 dark:text-white">{{ $position->lock_days }} วัน</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-100">
                <span class="text-gray-600 dark:text-gray-400">ตัวคูณโบนัส:</span>
                <span class="font-semibold text-gray-800 dark:text-white">×{{ $position->rank_bonus_multiplier }}</span>
            </div>
            @if($position->auto_compound)
            <div class="flex justify-between py-2 border-b border-gray-100">
                <span class="text-gray-600 dark:text-gray-400">รีลงทุนอัตโนมัติ:</span>
                <span class="font-semibold text-green-600">✓ เปิดใช้งาน</span>
            </div>
            @endif
            @if($position->referral_bonus > 0)
            <div class="flex justify-between py-2 border-b border-gray-100">
                <span class="text-gray-600 dark:text-gray-400">โบนัสแนะนำเพื่อน:</span>
                <span class="font-semibold text-purple-600">฿{{ number_format($position->referral_bonus, 2) }}</span>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
