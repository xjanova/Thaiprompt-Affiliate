@extends('layouts.user-arrow-x')

@section('title', 'การลงทุน ROI')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Investment Header -->
    <div class="bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white opacity-10 rounded-full"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-white opacity-10 rounded-full"></div>
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold mb-2">📈 การลงทุน ROI</h1>
                    <p class="text-pink-100">รับผลตอบแทนจากการลงทุนของคุณ</p>
                </div>
            </div>

            <!-- Summary Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                <div class="bg-white bg-opacity-20 rounded-xl p-4 backdrop-blur-sm">
                    <p class="text-pink-100 text-xs mb-1">ลงทุนทั้งหมด</p>
                    <p class="text-2xl font-bold">฿{{ number_format($summary['total_invested'], 2) }}</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-xl p-4 backdrop-blur-sm">
                    <p class="text-pink-100 text-xs mb-1">ROI ที่ได้รับ</p>
                    <p class="text-2xl font-bold">฿{{ number_format($summary['total_earned_roi'], 2) }}</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-xl p-4 backdrop-blur-sm">
                    <p class="text-pink-100 text-xs mb-1">ROI คาดหวัง</p>
                    <p class="text-2xl font-bold">฿{{ number_format($summary['total_expected_roi'], 2) }}</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-xl p-4 backdrop-blur-sm">
                    <p class="text-pink-100 text-xs mb-1">ตำแหน่งทั้งหมด</p>
                    <p class="text-2xl font-bold">{{ $summary['total_positions'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
        <a href="{{ route('user.investments.plans') }}" class="bg-white rounded-xl shadow-md hover:shadow-xl p-6 text-center transition transform hover:scale-105">
            <div class="text-4xl mb-3">💎</div>
            <p class="text-gray-800 font-semibold">แผนการลงทุน</p>
            <p class="text-xs text-gray-500 mt-1">Investment Plans</p>
        </a>

        <a href="{{ route('user.wallet.index') }}" class="bg-white rounded-xl shadow-md hover:shadow-xl p-6 text-center transition transform hover:scale-105">
            <div class="text-4xl mb-3">💳</div>
            <p class="text-gray-800 font-semibold">กระเป๋าเงิน</p>
            <p class="text-xs text-gray-500 mt-1">Wallet</p>
        </a>

        <a href="#roi-distributions" class="bg-white rounded-xl shadow-md hover:shadow-xl p-6 text-center transition transform hover:scale-105">
            <div class="text-4xl mb-3">📊</div>
            <p class="text-gray-800 font-semibold">ประวัติ ROI</p>
            <p class="text-xs text-gray-500 mt-1">ROI History</p>
        </a>
    </div>

    <!-- Active Positions -->
    <div class="bg-white rounded-2xl shadow-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-800">📍 ตำแหน่งการลงทุน</h2>
            <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-sm font-semibold">
                {{ $positions->total() }} รายการ
            </span>
        </div>

        @if($positions->count() > 0)
            <div class="space-y-4">
                @foreach($positions as $position)
                <div class="border border-gray-200 rounded-xl p-6 hover:shadow-md transition">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <h3 class="text-lg font-bold text-gray-800">{{ $position->investmentPlan->display_name }}</h3>
                                @if($position->status == 'active')
                                    <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-semibold">Active</span>
                                @elseif($position->status == 'pending')
                                    <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs font-semibold">รออนุมัติ</span>
                                @elseif($position->status == 'matured')
                                    <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-semibold">ครบกำหนด</span>
                                @elseif($position->status == 'withdrawn')
                                    <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-semibold">ถอนแล้ว</span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-500">{{ $position->position_number }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold text-purple-600">฿{{ number_format($position->amount, 2) }}</p>
                            <p class="text-xs text-gray-500">{{ $position->roi_rate }}% {{ $position->roi_frequency === 'daily' ? 'รายวัน' : 'รายสัปดาห์' }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                        <div>
                            <p class="text-xs text-gray-500 mb-1">ROI ที่ได้รับ</p>
                            <p class="font-semibold text-green-600">฿{{ number_format($position->earned_roi, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">ROI คาดหวัง</p>
                            <p class="font-semibold text-gray-800">฿{{ number_format($position->expected_roi, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">ความคืบหน้า</p>
                            <p class="font-semibold text-blue-600">{{ number_format($position->progress_percentage, 1) }}%</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">วันที่เหลือ</p>
                            <p class="font-semibold text-gray-800">{{ $position->days_remaining }} วัน</p>
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
                <p class="text-gray-500 mb-4">ยังไม่มีการลงทุน</p>
                <a href="{{ route('user.investments.plans') }}" class="inline-block px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition font-semibold">
                    เริ่มลงทุนเลย
                </a>
            </div>
        @endif
    </div>

    <!-- Recent ROI Distributions -->
    <div id="roi-distributions" class="bg-white rounded-2xl shadow-xl p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">💰 ROI ล่าสุด</h2>

        @if($recentDistributions->count() > 0)
            <div class="space-y-3">
                @foreach($recentDistributions as $distribution)
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                    <div class="flex-1">
                        <p class="font-semibold text-gray-800">{{ $distribution->stakingPosition->investmentPlan->display_name }}</p>
                        <p class="text-sm text-gray-500">{{ $distribution->distribution_date->format('d M Y') }} - {{ $distribution->display_type }}</p>
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
            <div class="text-center py-8 text-gray-500">
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
@endsection
