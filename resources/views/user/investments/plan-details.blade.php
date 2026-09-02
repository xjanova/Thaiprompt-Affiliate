@extends('layouts.user-v4')

@section('title', $plan->display_name)

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <a href="{{ route('user.investments.plans') }}" class="inline-flex items-center gap-2 text-purple-600 hover:text-purple-700 font-semibold">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
        กลับ
    </a>

    {{-- Premium Hero Header (Purple-Pink-Red for Plan Details) --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 dark:from-purple-800 dark:via-pink-800 dark:to-red-800 rounded-2xl shadow-2xl p-8">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-gem"></i>
            </div>
        </div>
        <div class="relative z-10">
            <div class="text-center mb-8">
                <div class="text-6xl mb-4">{{ $plan->icon ?? '💎' }}</div>
                <h1 class="text-4xl font-bold text-white drop-shadow-lg mb-2">{{ $plan->display_name }}</h1>
                <p class="text-pink-100 text-lg">{{ $plan->display_description }}</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="glass-fusion rounded-xl p-4 text-center">
                    <p class="text-pink-100 text-xs mb-1">อัตรา ROI</p>
                    <p class="text-3xl font-bold text-white drop-shadow-lg">{{ $plan->roi_rate }}%</p>
                    <p class="text-xs text-pink-100">{{ $plan->roi_frequency === 'daily' ? 'รายวัน' : 'รายสัปดาห์' }}</p>
                </div>
                <div class="glass-fusion rounded-xl p-4 text-center">
                    <p class="text-pink-100 text-xs mb-1">ระยะเวลา</p>
                    <p class="text-3xl font-bold text-white drop-shadow-lg">{{ $plan->term_days }}</p>
                    <p class="text-xs text-pink-100">วัน</p>
                </div>
                <div class="glass-fusion rounded-xl p-4 text-center">
                    <p class="text-pink-100 text-xs mb-1">ขั้นต่ำ</p>
                    <p class="text-3xl font-bold text-white drop-shadow-lg">{{ number_format($plan->min_amount / 1000, 0) }}K</p>
                    <p class="text-xs text-pink-100">บาท</p>
                </div>
                <div class="glass-fusion rounded-xl p-4 text-center">
                    <p class="text-pink-100 text-xs mb-1">ROI รวม</p>
                    <p class="text-3xl font-bold text-yellow-300 drop-shadow-lg">{{ number_format($expectedRoi / 1000, 1) }}K</p>
                    <p class="text-xs text-pink-100">บาท (min)</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Investment Form -->
    <div class="tp-card rounded-2xl p-8" x-data="investmentForm()">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-6">💰 ลงทุนเลย</h2>

        @if(!$canInvest)
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <p class="text-red-800">
                    ❌ คุณไม่สามารถลงทุนในแผนนี้ได้ในขณะนี้
                </p>
            </div>
        @else
            <form action="{{ route('user.investments.store') }}" method="POST" @submit="return validateForm()">
                @csrf
                <input type="hidden" name="plan_id" value="{{ $plan->id }}">

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">จำนวนเงินที่ต้องการลงทุน (฿):</label>
                    <input type="number" name="amount" x-model="amount" @input="calculateROI()" required
                           min="{{ $plan->min_amount }}"
                           max="{{ $plan->max_amount ?? '' }}"
                           step="1000"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent text-lg"
                           placeholder="{{ number_format($plan->min_amount, 0) }}">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                        ขั้นต่ำ: ฿{{ number_format($plan->min_amount, 0) }}
                        @if($plan->max_amount)
                            | สูงสุด: ฿{{ number_format($plan->max_amount, 0) }}
                        @endif
                    </p>
                </div>

                @if($plan->allow_compound)
                <div class="mb-6">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="auto_compound" class="w-5 h-5 text-purple-600 rounded focus:ring-purple-500">
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">เปิดใช้งานการรีลงทุนอัตโนมัติ (Auto-Compound)</span>
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 ml-7">ROI จะถูกรีลงทุนโดยอัตโนมัติเมื่อถึงจำนวนขั้นต่ำ</p>
                </div>
                @endif

                <!-- ROI Calculation Preview -->
                <div x-show="amount >= {{ $plan->min_amount }}" x-transition class="bg-purple-50 rounded-xl p-6 mb-6 border border-purple-200">
                    <h3 class="font-semibold text-gray-800 dark:text-white mb-4">การคำนวณผลตอบแทน:</h3>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">จำนวนลงทุน</p>
                            <p class="text-lg font-bold text-gray-800 dark:text-white" x-text="'฿' + parseFloat(amount).toLocaleString()"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">ROI รายวัน</p>
                            <p class="text-lg font-bold text-green-600" x-text="'฿' + dailyROI.toLocaleString()"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">ROI รวมทั้งหมด</p>
                            <p class="text-lg font-bold text-purple-600" x-text="'฿' + totalROI.toLocaleString()"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">ยอดที่ได้รับคืน</p>
                            <p class="text-lg font-bold text-pink-600" x-text="'฿' + totalReturn.toLocaleString()"></p>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-purple-200">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">ผลตอบแทนรวม:</span>
                            <span class="text-2xl font-bold text-green-600" x-text="'+' + profitPercent.toFixed(1) + '%'"></span>
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full px-6 py-4 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg hover:from-purple-700 hover:to-pink-700 transition text-lg font-semibold shadow-lg">
                    ยืนยันการลงทุน
                </button>
            </form>
        @endif
    </div>

    <!-- Features & Benefits -->
    @if($plan->features)
    <div class="tp-card rounded-2xl p-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-6">✨ คุณสมบัติและสิทธิประโยชน์</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($plan->features as $feature)
            <div class="flex items-start gap-3 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                <span class="text-green-500 text-xl mt-0.5">✓</span>
                <span class="text-gray-700 dark:text-gray-300">{{ $feature }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Terms & Conditions -->
    @if($plan->terms_conditions)
    <div class="tp-card rounded-2xl p-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-6">📋 เงื่อนไขและข้อตกลง</h2>

        <div class="space-y-3">
            @foreach($plan->terms_conditions as $term)
            <div class="flex items-start gap-3">
                <span class="text-purple-500 mt-1">•</span>
                <span class="text-gray-600 dark:text-gray-400 text-sm">{{ $term }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

<script>
function investmentForm() {
    return {
        amount: {{ $plan->min_amount }},
        dailyROI: 0,
        totalROI: 0,
        totalReturn: 0,
        profitPercent: 0,

        init() {
            this.calculateROI();
        },

        calculateROI() {
            if (this.amount < {{ $plan->min_amount }}) {
                return;
            }

            fetch('{{ route('user.investments.calculate-roi') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    plan_id: {{ $plan->id }},
                    amount: this.amount
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.dailyROI = parseFloat(data.daily_roi);
                    this.totalROI = parseFloat(data.expected_roi);
                    this.totalReturn = parseFloat(data.total_return);
                    this.profitPercent = (this.totalROI / this.amount) * 100;
                }
            });
        },

        validateForm() {
            if (this.amount < {{ $plan->min_amount }}) {
                alert('จำนวนเงินต่ำกว่าขั้นต่ำ');
                return false;
            }
            @if($plan->max_amount)
            if (this.amount > {{ $plan->max_amount }}) {
                alert('จำนวนเงินเกินสูงสุด');
                return false;
            }
            @endif
            return confirm('คุณแน่ใจหรือไม่ที่จะลงทุน ฿' + this.amount.toLocaleString() + ' ?');
        }
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
