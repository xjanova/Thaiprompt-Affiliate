{{--
    LINE Signup Rewards Display Component

    แสดงรางวัลการสมัครสมาชิก แยกตามฟรี/แพคเกจ

    @props
        - freeRewards: Collection รางวัลสำหรับสมัครฟรี
        - packageRewards: Collection รางวัลสำหรับซื้อแพคเกจ
        - compareMode: bool แสดงแบบเปรียบเทียบ (default: true)
--}}

@props([
    'freeRewards' => [],
    'packageRewards' => [],
    'compareMode' => true,
])

<div class="w-full">
    @if($compareMode)
        {{-- เปรียบเทียบ: แบบฟรี vs แบบแพคเกจ --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- สมัครฟรี --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border-2 border-gray-200 dark:border-gray-700">
                {{-- Header --}}
                <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-6 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-2xl font-bold">สมัครฟรี</h3>
                        <div class="px-4 py-2 bg-white/20 backdrop-blur rounded-full text-sm font-medium">
                            FREE
                        </div>
                    </div>
                    <p class="text-green-100">ไม่เสียค่าใช้จ่ายใดๆ</p>
                </div>

                {{-- Rewards List --}}
                <div class="p-6 space-y-4">
                    @forelse($freeRewards as $reward)
                        <div class="flex items-start gap-4 p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            {{-- Icon --}}
                            <div class="flex-shrink-0 w-12 h-12 flex items-center justify-center rounded-xl text-2xl"
                                 style="background-color: {{ $reward->badge_color }}20; color: {{ $reward->badge_color }}">
                                {!! $reward->getIconHtml() !!}
                            </div>

                            {{-- Details --}}
                            <div class="flex-1 min-w-0">
                                <h4 class="font-semibold text-gray-900 dark:text-white">
                                    {{ $reward->name }}
                                </h4>
                                @if($reward->description)
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        {{ $reward->description }}
                                    </p>
                                @endif
                                <div class="mt-2 inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                                     style="background-color: {{ $reward->badge_color }}20; color: {{ $reward->badge_color }}">
                                    {{ $reward->getDisplayText() }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <i class="fas fa-gift text-4xl mb-3"></i>
                            <p>ยังไม่มีรางวัลสำหรับสมัครฟรี</p>
                        </div>
                    @endforelse
                </div>

                {{-- Footer --}}
                <div class="px-6 pb-6">
                    <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-800">
                        <div class="flex items-center text-green-700 dark:text-green-300">
                            <i class="fas fa-check-circle mr-2"></i>
                            <span class="text-sm font-medium">รับทันทีเมื่อสมัครสำเร็จ</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- สมัครแพคเกจ --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border-2 border-purple-300 dark:border-purple-700 relative">
                {{-- Premium Badge --}}
                <div class="absolute top-4 right-4 z-10">
                    <div class="px-4 py-2 bg-yellow-400 text-yellow-900 rounded-full text-sm font-bold shadow-lg flex items-center gap-2">
                        <i class="fas fa-crown"></i>
                        PREMIUM
                    </div>
                </div>

                {{-- Header --}}
                <div class="bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 p-6 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-2xl font-bold">สมัครแพคเกจ</h3>
                        <div class="px-4 py-2 bg-white/20 backdrop-blur rounded-full text-sm font-medium">
                            PACKAGE
                        </div>
                    </div>
                    <p class="text-purple-100">รับรางวัลพิเศษมากกว่า!</p>
                </div>

                {{-- Rewards List --}}
                <div class="p-6 space-y-4">
                    @forelse($packageRewards as $reward)
                        <div class="flex items-start gap-4 p-4 rounded-xl bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 hover:from-purple-100 hover:to-pink-100 dark:hover:from-purple-900/30 dark:hover:to-pink-900/30 transition border border-purple-200 dark:border-purple-800">
                            {{-- Icon --}}
                            <div class="flex-shrink-0 w-12 h-12 flex items-center justify-center rounded-xl text-2xl"
                                 style="background-color: {{ $reward->badge_color }}; color: white">
                                {!! $reward->getIconHtml() !!}
                            </div>

                            {{-- Details --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <h4 class="font-semibold text-gray-900 dark:text-white">
                                        {{ $reward->name }}
                                    </h4>
                                    @if($reward->reward_type === 'free_downlines' || $reward->amount > 100)
                                        <span class="flex-shrink-0 px-2 py-1 bg-red-500 text-white text-xs font-bold rounded-full">
                                            HOT!
                                        </span>
                                    @endif
                                </div>
                                @if($reward->description)
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        {{ $reward->description }}
                                    </p>
                                @endif
                                <div class="mt-2 inline-flex items-center px-3 py-1 rounded-full text-sm font-bold text-white"
                                     style="background: linear-gradient(135deg, {{ $reward->badge_color }} 0%, {{ $reward->badge_color }}dd 100%);">
                                    {{ $reward->getDisplayText() }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <i class="fas fa-gift text-4xl mb-3"></i>
                            <p>ยังไม่มีรางวัลสำหรับแพคเกจ</p>
                        </div>
                    @endforelse
                </div>

                {{-- Footer --}}
                <div class="px-6 pb-6">
                    <div class="p-4 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/30 dark:to-pink-900/30 rounded-xl border border-purple-200 dark:border-purple-800">
                        <div class="flex items-center text-purple-700 dark:text-purple-300">
                            <i class="fas fa-star mr-2"></i>
                            <span class="text-sm font-medium">รับทันทีเมื่อซื้อแพคเกจสำเร็จ</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Summary --}}
        <div class="mt-8 text-center">
            <div class="inline-block px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-full shadow-lg">
                <i class="fas fa-info-circle mr-2"></i>
                <span class="font-medium">รางวัลทั้งหมดจะได้รับทันทีหลังสมัครสมาชิกสำเร็จ</span>
            </div>
        </div>
    @else
        {{-- แสดงแบบรายการ --}}
        <div class="space-y-4">
            @foreach(array_merge($freeRewards->toArray(), $packageRewards->toArray()) as $reward)
                <div class="flex items-start gap-4 p-6 bg-white dark:bg-gray-800 rounded-xl shadow hover:shadow-lg transition">
                    {{-- Icon --}}
                    <div class="flex-shrink-0 w-14 h-14 flex items-center justify-center rounded-xl text-2xl"
                         style="background-color: {{ $reward->badge_color }}20; color: {{ $reward->badge_color }}">
                        {!! $reward->getIconHtml() !!}
                    </div>

                    {{-- Details --}}
                    <div class="flex-1">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $reward->name }}
                            </h4>
                            <span class="flex-shrink-0 px-3 py-1 rounded-full text-xs font-medium"
                                  style="background-color: {{ $reward->signup_type === 'free' ? '#10b981' : '#9333ea' }}20; color: {{ $reward->signup_type === 'free' ? '#10b981' : '#9333ea' }}">
                                {{ $reward->signup_type === 'free' ? 'ฟรี' : 'แพคเกจ' }}
                            </span>
                        </div>

                        @if($reward->description)
                            <p class="text-gray-600 dark:text-gray-400 mb-2">
                                {{ $reward->description }}
                            </p>
                        @endif

                        <div class="inline-flex items-center px-4 py-2 rounded-lg font-medium"
                             style="background-color: {{ $reward->badge_color }}20; color: {{ $reward->badge_color }}">
                            {{ $reward->getDisplayText() }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@push('scripts')
<script>
// Alpine.js component สำหรับ interactivity (ถ้าต้องการ)
document.addEventListener('alpine:init', () => {
    Alpine.data('rewardsDisplay', () => ({
        showDetails: false,
        toggleDetails() {
            this.showDetails = !this.showDetails;
        }
    }));
});
</script>
@endpush
