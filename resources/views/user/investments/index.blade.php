@extends('layouts.user-v4')

@section('title', 'การลงทุน ROI')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    {{-- Premium Hero Header (Purple-Pink-Red for Investments) --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 dark:from-purple-800 dark:via-pink-800 dark:to-red-800 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>

        {{-- Floating Icons --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex items-center gap-4 mb-6">
                <div class="glass-fusion p-4 rounded-2xl">
                    <i class="fas fa-chart-pie text-4xl text-white drop-shadow-lg"></i>
                </div>
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold text-white drop-shadow-lg">📈 การลงทุน ROI</h1>
                    <p class="text-pink-100 mt-1">รับผลตอบแทนจากการลงทุนของคุณ</p>
                </div>
            </div>

            {{-- Summary Stats --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="glass-fusion rounded-xl p-4">
                    <p class="text-pink-100 text-xs mb-1">ลงทุนทั้งหมด</p>
                    <p class="text-2xl font-bold text-white drop-shadow-lg">฿{{ number_format($summary['total_invested'], 2) }}</p>
                </div>
                <div class="glass-fusion rounded-xl p-4">
                    <p class="text-pink-100 text-xs mb-1">ROI ที่ได้รับ</p>
                    <p class="text-2xl font-bold text-white drop-shadow-lg">฿{{ number_format($summary['total_earned_roi'], 2) }}</p>
                </div>
                <div class="glass-fusion rounded-xl p-4">
                    <p class="text-pink-100 text-xs mb-1">ROI คาดหวัง</p>
                    <p class="text-2xl font-bold text-white drop-shadow-lg">฿{{ number_format($summary['total_expected_roi'], 2) }}</p>
                </div>
                <div class="glass-fusion rounded-xl p-4">
                    <p class="text-pink-100 text-xs mb-1">ตำแหน่งทั้งหมด</p>
                    <p class="text-2xl font-bold text-white drop-shadow-lg">{{ $summary['total_positions'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
        <a href="{{ route('user.investments.plans') }}" class="tp-card rounded-xl hover:shadow-xl p-6 text-center transition transition-transform hover:scale-[1.02]">
            <div class="text-4xl mb-3">💎</div>
            <p class="text-gray-800 dark:text-white font-semibold">แผนการลงทุน</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Investment Plans</p>
        </a>

        <a href="{{ route('user.wallet.index') }}" class="tp-card rounded-xl hover:shadow-xl p-6 text-center transition transition-transform hover:scale-[1.02]">
            <div class="text-4xl mb-3">💳</div>
            <p class="text-gray-800 dark:text-white font-semibold">กระเป๋าเงิน</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Wallet</p>
        </a>

        <a href="#roi-distributions" class="tp-card rounded-xl hover:shadow-xl p-6 text-center transition transition-transform hover:scale-[1.02]">
            <div class="text-4xl mb-3">📊</div>
            <p class="text-gray-800 dark:text-white font-semibold">ประวัติ ROI</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ROI History</p>
        </a>
    </div>

    <!-- Active Positions -->
    <div class="tp-card rounded-2xl p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">📍 ตำแหน่งการลงทุน</h2>
            <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-sm font-semibold">
                {{ $positions->total() }} รายการ
            </span>
        </div>

        @if($positions->count() > 0)
            <div class="space-y-4">
                @foreach($positions as $position)
                <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-6 hover:shadow-md transition">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <h3 class="text-lg font-bold text-gray-800 dark:text-white">{{ $position->investmentPlan->display_name }}</h3>
                                @if($position->status == 'active')
                                    <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-semibold">Active</span>
                                @elseif($position->status == 'pending')
                                    <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs font-semibold">รออนุมัติ</span>
                                @elseif($position->status == 'matured')
                                    <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-semibold">ครบกำหนด</span>
                                @elseif($position->status == 'withdrawn')
                                    <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded text-xs font-semibold">ถอนแล้ว</span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $position->position_number }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold text-purple-600">฿{{ number_format($position->amount, 2) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $position->roi_rate }}% {{ $position->roi_frequency === 'daily' ? 'รายวัน' : 'รายสัปดาห์' }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">ROI ที่ได้รับ</p>
                            <p class="font-semibold text-green-600">฿{{ number_format($position->earned_roi, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">ROI คาดหวัง</p>
                            <p class="font-semibold text-gray-800 dark:text-white">฿{{ number_format($position->expected_roi, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">ความคืบหน้า</p>
                            <p class="font-semibold text-blue-600">{{ number_format($position->progress_percentage, 1) }}%</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">วันที่เหลือ</p>
                            <p class="font-semibold text-gray-800 dark:text-white">{{ $position->days_remaining }} วัน</p>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mb-4">
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-gradient-to-r from-purple-500 to-pink-500 h-2 rounded-full" style="width: {{ $position->progress_percentage }}%"></div>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('user.investments.show', $position) }}" class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition text-center text-sm font-semibold">
                            รายละเอียด
                        </a>
                        @if($position->canWithdraw())
                            <button onclick="confirmWithdraw({{ $position->id }})" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm font-semibold">
                                ถอนเงิน
                            </button>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $positions->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <div class="text-6xl mb-4">📊</div>
                <p class="text-gray-500 dark:text-gray-400 mb-4">ยังไม่มีการลงทุน</p>
                <a href="{{ route('user.investments.plans') }}" class="inline-block px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition font-semibold">
                    เริ่มลงทุนเลย
                </a>
            </div>
        @endif
    </div>

    <!-- Recent ROI Distributions -->
    <div id="roi-distributions" class="tp-card rounded-2xl p-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-6">💰 ROI ล่าสุด</h2>

        @if($recentDistributions->count() > 0)
            <div class="space-y-3">
                @foreach($recentDistributions as $distribution)
                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg hover:bg-gray-100 dark:bg-gray-700 transition">
                    <div class="flex-1">
                        <p class="font-semibold text-gray-800 dark:text-white">{{ $distribution->stakingPosition->investmentPlan->display_name }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $distribution->distribution_date->format('d M Y') }} - {{ $distribution->display_type }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold text-green-600">+฿{{ number_format($distribution->roi_amount, 2) }}</p>
                        @if($distribution->status == 'completed')
                            <span class="text-xs text-green-600">✓ จ่ายแล้ว</span>
                        @elseif($distribution->status == 'pending')
                            <span class="text-xs text-yellow-600">⏳ รอจ่าย</span>
                        @elseif($distribution->status == 'failed')
                            <span class="text-xs text-red-600">✗ ล้มเหลว</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                ยังไม่มีประวัติการจ่าย ROI
            </div>
        @endif
    </div>
</div>

<script>
function confirmWithdraw(positionId) {
    if (confirm('คุณแน่ใจหรือไม่ที่จะถอนเงินจากการลงทุนนี้?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/investments/${positionId}/withdraw`;

        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = '{{ csrf_token() }}';
        form.appendChild(csrf);

        document.body.appendChild(form);
        form.submit();
    }
}
</script>

@push('styles')
<style>
.glass-fusion {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}
</style>
@endpush
@endsection
