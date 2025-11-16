@extends('layouts.user-arrow-x')

@section('title', 'แผนการลงทุน')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 rounded-2xl shadow-2xl p-8 text-white">
        <h1 class="text-3xl md:text-4xl font-bold mb-2">💎 แผนการลงทุน ROI</h1>
        <p class="text-indigo-100">เลือกแผนที่เหมาะกับคุณและรับผลตอบแทนที่คุ้มค่า</p>
    </div>

    <!-- Investment Plans -->
    @if($plans->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($plans as $plan)
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden transform hover:scale-105 transition {{ $plan->is_featured ? 'ring-2 ring-purple-500' : '' }}">
                @if($plan->is_featured)
                    <div class="bg-gradient-to-r from-purple-500 to-pink-500 text-white text-center py-2 text-sm font-semibold">
                        ⭐ แนะนำ
                    </div>
                @endif

                <!-- Plan Header -->
                <div class="p-6 {{ $plan->color ? 'bg-gradient-to-br' : 'bg-gray-50' }}" style="{{ $plan->color ? 'background: linear-gradient(135deg, '.$plan->color.', '.$plan->color.'dd)' : '' }}">
                    <div class="text-center">
                        @if($plan->icon)
                            <div class="text-5xl mb-3">{{ $plan->icon }}</div>
                        @else
                            <div class="text-5xl mb-3">💎</div>
                        @endif
                        <h3 class="text-2xl font-bold {{ $plan->color ? 'text-white' : 'text-gray-800' }} mb-2">
                            {{ $plan->display_name }}
                        </h3>
                        <p class="text-sm {{ $plan->color ? 'text-white opacity-90' : 'text-gray-600' }}">
                            {{ $plan->display_description }}
                        </p>
                    </div>
                </div>

                <!-- Plan Details -->
                <div class="p-6">
                    <!-- ROI Rate -->
                    <div class="text-center mb-6 pb-6 border-b">
                        <p class="text-sm text-gray-500 mb-2">อัตราผลตอบแทน</p>
                        <p class="text-4xl font-bold text-purple-600">{{ $plan->roi_rate }}%</p>
                        <p class="text-sm text-gray-600 mt-1">
                            {{ $plan->roi_frequency === 'daily' ? 'ต่อวัน' : ($plan->roi_frequency === 'weekly' ? 'ต่อสัปดาห์' : 'ต่อเดือน') }}
                        </p>
                    </div>

                    <!-- Investment Range -->
                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-500">ขั้นต่ำ:</span>
                            <span class="font-semibold text-gray-800">฿{{ number_format($plan->min_amount, 0) }}</span>
                        </div>
                        @if($plan->max_amount)
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-500">สูงสุด:</span>
                            <span class="font-semibold text-gray-800">฿{{ number_format($plan->max_amount, 0) }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-500">ระยะเวลา:</span>
                            <span class="font-semibold text-gray-800">{{ $plan->term_days }} วัน</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-500">ระยะล็อค:</span>
                            <span class="font-semibold text-gray-800">{{ $plan->lock_days }} วัน</span>
                        </div>
                    </div>

                    <!-- Features -->
                    @if($plan->features)
                    <div class="mb-6">
                        <p class="text-sm font-semibold text-gray-700 mb-3">คุณสมบัติ:</p>
                        <ul class="space-y-2">
                            @foreach($plan->features as $feature)
                            <li class="flex items-start gap-2 text-sm text-gray-600">
                                <span class="text-green-500 mt-0.5">✓</span>
                                <span>{{ $feature }}</span>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <!-- Availability -->
                    @if($plan->max_investors)
                    <div class="mb-4">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-500">ที่ว่าง:</span>
                            <span class="font-semibold">{{ $plan->max_investors - $plan->current_investors }}/{{ $plan->max_investors }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-purple-600 h-2 rounded-full" style="width: {{ ($plan->current_investors / $plan->max_investors) * 100 }}%"></div>
                        </div>
                    </div>
                    @endif

                    <!-- Action Button -->
                    <a href="{{ route('user.investments.plans.show', $plan) }}" class="block w-full px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg hover:from-purple-700 hover:to-pink-700 transition text-center font-semibold shadow-lg">
                        ลงทุนเลย
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    @else
        <div class="bg-white rounded-2xl shadow-xl p-12 text-center">
            <div class="text-6xl mb-4">📊</div>
            <p class="text-gray-500 text-lg">ยังไม่มีแผนการลงทุนที่เปิดให้บริการ</p>
        </div>
    @endif

    <!-- ROI Calculator -->
    <div class="bg-white rounded-2xl shadow-xl p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">🧮 คำนวณ ROI</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6" x-data="roiCalculator()">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">เลือกแผน:</label>
                <select x-model="selectedPlan" @change="calculateROI()" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <option value="">-- เลือกแผน --</option>
                    @foreach($plans as $plan)
                    <option value="{{ $plan->id }}" data-rate="{{ $plan->roi_rate }}" data-term="{{ $plan->term_days }}">
                        {{ $plan->display_name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">จำนวนเงิน (฿):</label>
                <input type="number" x-model="amount" @input="calculateROI()" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent" placeholder="10000">
            </div>

            <div class="md:col-span-2" x-show="result" x-transition>
                <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl p-6 border border-purple-200">
                    <h3 class="font-semibold text-gray-800 mb-4">ผลการคำนวณ:</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 mb-1">จำนวนลงทุน</p>
                            <p class="text-lg font-bold text-gray-800" x-text="'฿' + amount.toLocaleString()"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">ROI รายวัน</p>
                            <p class="text-lg font-bold text-green-600" x-text="'฿' + dailyROI.toLocaleString()"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">ROI รวม</p>
                            <p class="text-lg font-bold text-purple-600" x-text="'฿' + totalROI.toLocaleString()"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">ยอดรวมทั้งหมด</p>
                            <p class="text-lg font-bold text-pink-600" x-text="'฿' + totalReturn.toLocaleString()"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function roiCalculator() {
    return {
        selectedPlan: '',
        amount: 10000,
        result: false,
        dailyROI: 0,
        totalROI: 0,
        totalReturn: 0,

        calculateROI() {
            if (!this.selectedPlan || !this.amount) {
                this.result = false;
                return;
            }

            // Send AJAX request to calculate ROI
            fetch('{{ route('user.investments.calculate-roi') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    plan_id: this.selectedPlan,
                    amount: this.amount
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.dailyROI = parseFloat(data.daily_roi);
                    this.totalROI = parseFloat(data.expected_roi);
                    this.totalReturn = parseFloat(data.total_return);
                    this.result = true;
                }
            });
        }
    }
}
</script>
@endsection
