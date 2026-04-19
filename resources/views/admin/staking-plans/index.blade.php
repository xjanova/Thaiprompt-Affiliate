@extends('layouts.admin-v3')

@section('title', 'จัดการแผนการลงทุน')

@section('content')
<div class="container mx-auto px-4 py-6" x-data="stakingPlansManager()">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center">
                    <i class="fas fa-chart-line text-white"></i>
                </span>
                จัดการแผนการลงทุน (Staking)
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">จัดการแผนการลงทุน, เปิด/ปิดแผน, ตั้งค่าอัตราแลกเปลี่ยน Coin</p>
        </div>

        <div class="flex items-center gap-3 mt-4 md:mt-0">
            <a href="{{ route('admin.staking-plans.coin-settings') }}"
               class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg transition flex items-center gap-2">
                <i class="fas fa-coins"></i>
                ตั้งค่า Coin
            </a>
            <a href="{{ route('admin.staking-plans.create') }}"
               class="px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-lg transition flex items-center gap-2">
                <i class="fas fa-plus"></i>
                สร้างแผนใหม่
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-200 dark:border-gray-700">
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_plans']) }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">แผนทั้งหมด</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-200 dark:border-gray-700">
            <div class="text-2xl font-bold text-green-600">{{ number_format($stats['active_plans']) }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">เปิดใช้งาน</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-200 dark:border-gray-700">
            <div class="text-2xl font-bold text-yellow-600">{{ number_format($stats['paused_plans']) }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">หยุดชั่วคราว</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-200 dark:border-gray-700">
            <div class="text-2xl font-bold text-blue-600">{{ number_format($stats['total_positions']) }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">การลงทุนที่ใช้งาน</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-200 dark:border-gray-700">
            <div class="text-2xl font-bold text-purple-600">฿{{ number_format($stats['total_invested'], 2) }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">ยอดลงทุนรวม</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-200 dark:border-gray-700">
            <div class="text-2xl font-bold text-pink-600">฿{{ number_format($stats['total_roi_paid'], 2) }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">ROI ที่จ่ายแล้ว</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-200 dark:border-gray-700">
            <div class="text-2xl font-bold text-amber-600">{{ number_format($stats['coin_invested'], 2) }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">Coin ที่ใช้ลงทุน</div>
        </div>
    </div>

    {{-- Coin Settings Banner --}}
    @if($coinSettings->allow_coin_staking)
    <div class="bg-gradient-to-r from-amber-500/10 to-orange-500/10 border border-amber-500/30 rounded-xl p-4 mb-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-amber-500 flex items-center justify-center">
                <i class="fas fa-coins text-white"></i>
            </div>
            <div>
                <div class="font-medium text-gray-900 dark:text-white">อัตราแลกเปลี่ยน Coin ปัจจุบัน</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    1 Coin = ฿{{ number_format($coinSettings->coin_to_thb_rate, 4) }}
                    @if($coinSettings->allow_mixed_payment)
                        • รองรับการชำระแบบผสม (Coin + เงิน)
                    @endif
                </div>
            </div>
        </div>
        <a href="{{ route('admin.staking-plans.coin-settings') }}" class="text-amber-600 hover:text-amber-700 font-medium">
            <i class="fas fa-cog"></i> ตั้งค่า
        </a>
    </div>
    @endif

    {{-- Plans Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">แผน</th>
                        <th class="px-4 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ROI</th>
                        <th class="px-4 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ระยะเวลา</th>
                        <th class="px-4 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ยอดขั้นต่ำ</th>
                        <th class="px-4 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Coin</th>
                        <th class="px-4 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ความเสี่ยง</th>
                        <th class="px-4 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">นักลงทุน</th>
                        <th class="px-4 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">สถานะ</th>
                        <th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($plans as $plan)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white text-lg"
                                     style="background: {{ $plan->color ?? 'linear-gradient(135deg, #8B5CF6, #EC4899)' }}">
                                    {{ $plan->icon ?? '📈' }}
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        {{ $plan->display_name }}
                                        @if($plan->is_featured)
                                            <span class="ml-2 px-2 py-0.5 bg-yellow-100 text-yellow-800 text-xs rounded-full">⭐ แนะนำ</span>
                                        @endif
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($plan->display_description, 50) }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <div class="text-lg font-bold text-green-600">{{ number_format($plan->roi_rate, 2) }}%</div>
                            <div class="text-xs text-gray-500">{{ $plan->roi_frequency === 'daily' ? 'ต่อวัน' : ($plan->roi_frequency === 'weekly' ? 'ต่อสัปดาห์' : 'ต่อเดือน') }}</div>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <div class="font-medium text-gray-900 dark:text-white">{{ number_format($plan->term_days) }} วัน</div>
                            @if($plan->lock_days > 0)
                                <div class="text-xs text-red-500">ล็อค {{ $plan->lock_days }} วัน</div>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-center">
                            <div class="font-medium text-gray-900 dark:text-white">฿{{ number_format($plan->min_amount) }}</div>
                            @if($plan->max_amount)
                                <div class="text-xs text-gray-500">- ฿{{ number_format($plan->max_amount) }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-center">
                            @if($plan->allow_coin_investment)
                                <span class="px-2 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 text-xs rounded-full">
                                    <i class="fas fa-coins mr-1"></i>
                                    {{ number_format($plan->coin_to_money_rate, 2) }}:1
                                </span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-center">
                            @php
                                $riskInfo = $plan->risk_level_info;
                            @endphp
                            <span class="px-2 py-1 rounded-full text-xs font-medium
                                @if($riskInfo['color'] === 'green') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200
                                @elseif($riskInfo['color'] === 'yellow') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200
                                @elseif($riskInfo['color'] === 'orange') bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-200
                                @else bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200
                                @endif">
                                {{ $riskInfo['icon'] }} {{ $riskInfo['label'] }}
                            </span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <div class="font-medium text-gray-900 dark:text-white">{{ number_format($plan->active_positions ?? 0) }}</div>
                            @if($plan->max_investors)
                                <div class="text-xs text-gray-500">/ {{ number_format($plan->max_investors) }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-center">
                            @if(!$plan->is_active)
                                <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-xs rounded-full">ปิดใช้งาน</span>
                            @elseif($plan->is_paused)
                                <span class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-200 text-xs rounded-full">หยุดชั่วคราว</span>
                            @elseif(!$plan->isOpen())
                                <span class="px-2 py-1 bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-200 text-xs rounded-full">ปิดรับการลงทุน</span>
                            @else
                                <span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 text-xs rounded-full">เปิดรับการลงทุน</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                {{-- Toggle Pause/Resume --}}
                                @if($plan->is_active && !$plan->is_paused)
                                    <form action="{{ route('admin.staking-plans.pause', $plan) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="p-2 text-yellow-600 hover:bg-yellow-100 dark:hover:bg-yellow-900/30 rounded-lg transition" title="หยุดชั่วคราว">
                                            <i class="fas fa-pause"></i>
                                        </button>
                                    </form>
                                @elseif($plan->is_paused)
                                    <form action="{{ route('admin.staking-plans.resume', $plan) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="p-2 text-green-600 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-lg transition" title="เปิดรับการลงทุน">
                                            <i class="fas fa-play"></i>
                                        </button>
                                    </form>
                                @endif

                                {{-- View --}}
                                <a href="{{ route('admin.staking-plans.show', $plan) }}" class="p-2 text-blue-600 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition" title="ดูรายละเอียด">
                                    <i class="fas fa-eye"></i>
                                </a>

                                {{-- Edit --}}
                                <a href="{{ route('admin.staking-plans.edit', $plan) }}" class="p-2 text-purple-600 hover:bg-purple-100 dark:hover:bg-purple-900/30 rounded-lg transition" title="แก้ไข">
                                    <i class="fas fa-edit"></i>
                                </a>

                                {{-- Delete --}}
                                <form action="{{ route('admin.staking-plans.destroy', $plan) }}" method="POST" class="inline"
                                      onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบแผนนี้?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition" title="ลบ">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            <i class="fas fa-inbox text-4xl mb-4"></i>
                            <p>ยังไม่มีแผนการลงทุน</p>
                            <a href="{{ route('admin.staking-plans.create') }}" class="text-purple-600 hover:underline mt-2 inline-block">
                                + สร้างแผนแรก
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * Alpine.js component สำหรับจัดการ Staking Plans
 */
function stakingPlansManager() {
    return {
        // สถานะและข้อมูลต่างๆ
    };
}
</script>
@endpush
@endsection
