{{--
    หน้าอัพเกรดดาว - แสดงดาวปัจจุบันและตัวเลือกการอัพเกรด

    @author Video Reward System
--}}

@extends('layouts.user-arrow-x')

@section('title', $pageTitle ?? 'อัพเกรดดาว')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="starUpgrade()">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <span class="text-4xl">⭐</span>
                    อัพเกรดดาว
                </h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    ซื้อดาวเพิ่มเพื่อแสดงสถานะพิเศษและรับสิทธิประโยชน์
                </p>
            </div>

            {{-- Coin Balance Card --}}
            <div class="bg-gradient-to-br from-yellow-400 via-yellow-500 to-orange-500 rounded-2xl p-4 shadow-lg min-w-[200px]">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-yellow-300 rounded-full flex items-center justify-center shadow-lg">
                        <span class="text-yellow-800 font-black text-lg">C</span>
                    </div>
                    <div>
                        <p class="text-yellow-100 text-sm">Coins ของคุณ</p>
                        <p class="text-white text-2xl font-bold">{{ number_format($coinBalance, 2) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Current Star Status --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">ดาวปัจจุบันของคุณ</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Level-based Stars --}}
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-5 text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">ดาวจาก Level</p>
                <div class="flex justify-center items-center gap-1 mb-2">
                    @for($i = 0; $i < $levelStars; $i++)
                        <svg class="w-8 h-8 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    @endfor
                    @for($i = $levelStars; $i < 5; $i++)
                        <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    @endfor
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $levelStars }} ดาว</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Level {{ $videoLevel->currentLevel?->level ?? 1 }}</p>
            </div>

            {{-- Purchased Stars --}}
            <div class="bg-gradient-to-br {{ $starColors[$videoLevel->purchased_star_color ?? 'bronze']['gradient'] ?? 'from-amber-600 to-amber-800' }} rounded-xl p-5 text-center text-white">
                <p class="text-sm text-white/80 mb-2">ดาวที่ซื้อ</p>
                <div class="flex justify-center items-center gap-1 mb-2">
                    @for($i = 0; $i < $currentPurchasedStars; $i++)
                        <svg class="w-8 h-8 text-white drop-shadow-lg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    @endfor
                    @for($i = $currentPurchasedStars; $i < 5; $i++)
                        <svg class="w-8 h-8 text-white/30" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    @endfor
                </div>
                <p class="text-2xl font-bold">{{ $currentPurchasedStars }} ดาว</p>
                @if($currentPurchasedStars > 0)
                    <p class="text-xs text-white/80 mt-1">{{ $starColors[$videoLevel->purchased_star_color ?? 'bronze']['name'] ?? 'ทองแดง' }}</p>
                @else
                    <p class="text-xs text-white/80 mt-1">ยังไม่ได้ซื้อ</p>
                @endif
            </div>

            {{-- Effective Stars (แสดงจริง) --}}
            <div class="bg-gradient-to-br from-purple-500 via-purple-600 to-indigo-600 rounded-xl p-5 text-center text-white">
                <p class="text-sm text-white/80 mb-2">ดาวที่แสดง</p>
                <div class="flex justify-center items-center gap-1 mb-2">
                    @for($i = 0; $i < $effectiveStars; $i++)
                        <svg class="w-8 h-8 text-yellow-300 drop-shadow-lg animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    @endfor
                    @for($i = $effectiveStars; $i < 5; $i++)
                        <svg class="w-8 h-8 text-white/30" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    @endfor
                </div>
                <p class="text-2xl font-bold">{{ $effectiveStars }} ดาว</p>
                <p class="text-xs text-white/80 mt-1">สูงสุดของทั้งสอง</p>
            </div>
        </div>
    </div>

    {{-- Star Upgrade Options --}}
    <div class="mb-8">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <span class="text-2xl">🌟</span>
            ตัวเลือกอัพเกรดดาว
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
            @foreach($upgradeOptions as $option)
                <div class="relative group">
                    {{-- Card --}}
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden transition-all duration-300 {{ $option['is_owned'] ? 'ring-2 ring-green-500' : '' }} {{ $option['can_upgrade'] ? 'hover:shadow-xl hover:scale-105' : 'opacity-60' }}">
                        {{-- Header with gradient --}}
                        <div class="bg-gradient-to-br {{ $option['color_info']['gradient'] }} p-6 text-center text-white">
                            {{-- Stars --}}
                            <div class="flex justify-center items-center gap-1 mb-3">
                                @for($i = 0; $i < $option['stars']; $i++)
                                    <svg class="w-6 h-6 text-white drop-shadow-lg" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @endfor
                            </div>
                            <h3 class="text-lg font-bold">{{ $option['name'] }}</h3>
                            <p class="text-sm text-white/80">{{ $option['color_info']['name'] }}</p>
                        </div>

                        {{-- Content --}}
                        <div class="p-4">
                            {{-- Price --}}
                            <div class="text-center mb-4">
                                <p class="text-sm text-gray-500 dark:text-gray-400">ราคา</p>
                                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                                    {{ number_format($option['price'], 0) }}
                                    <span class="text-sm font-normal">Coins</span>
                                </p>
                            </div>

                            {{-- Benefits --}}
                            <div class="space-y-2 mb-4 text-sm">
                                <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                                    <span class="text-green-500">✓</span>
                                    <span>EXP x{{ number_format($option['exp_multiplier'], 2) }}</span>
                                </div>
                                <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                                    <span class="text-green-500">✓</span>
                                    <span>Coins x{{ number_format($option['coin_multiplier'], 2) }}</span>
                                </div>
                                <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                                    <span class="text-blue-500">📊</span>
                                    <span>Level ขั้นต่ำ: {{ $option['min_level'] }}</span>
                                </div>
                            </div>

                            {{-- Action Button --}}
                            @if($option['is_owned'])
                                <button type="button" disabled
                                        class="w-full py-3 bg-green-500 text-white rounded-xl font-semibold cursor-not-allowed">
                                    ✓ เป็นเจ้าของแล้ว
                                </button>
                            @elseif($option['can_upgrade'])
                                <button type="button"
                                        @click="confirmUpgrade({{ $option['stars'] }}, '{{ $option['name'] }}', {{ $option['price'] }}, '{{ $option['color_info']['gradient'] }}')"
                                        class="w-full py-3 bg-gradient-to-r from-yellow-500 to-orange-500 hover:from-yellow-600 hover:to-orange-600 text-white rounded-xl font-semibold transition shadow-lg">
                                    ซื้อเลย
                                </button>
                            @else
                                <button type="button" disabled
                                        class="w-full py-3 bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 rounded-xl font-semibold cursor-not-allowed text-sm">
                                    {{ $option['reason'] ?? 'ไม่สามารถซื้อได้' }}
                                </button>
                            @endif
                        </div>

                        {{-- Owned Badge --}}
                        @if($option['is_current'])
                            <div class="absolute top-2 right-2 bg-green-500 text-white text-xs px-2 py-1 rounded-full font-semibold">
                                ปัจจุบัน
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Recent Upgrades --}}
    @if($recentUpgrades->count() > 0)
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <span class="text-2xl">📜</span>
                ประวัติอัพเกรดล่าสุด
            </h2>
            <a href="{{ route('user.star-upgrade.history') }}"
               class="text-blue-600 dark:text-blue-400 hover:underline text-sm">
                ดูทั้งหมด
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="text-left py-3 px-4 text-gray-500 dark:text-gray-400 text-sm font-medium">วันที่</th>
                        <th class="text-center py-3 px-4 text-gray-500 dark:text-gray-400 text-sm font-medium">จาก</th>
                        <th class="text-center py-3 px-4 text-gray-500 dark:text-gray-400 text-sm font-medium">เป็น</th>
                        <th class="text-right py-3 px-4 text-gray-500 dark:text-gray-400 text-sm font-medium">ราคา</th>
                        <th class="text-center py-3 px-4 text-gray-500 dark:text-gray-400 text-sm font-medium">สถานะ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($recentUpgrades as $upgrade)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <td class="py-3 px-4 text-gray-900 dark:text-white text-sm">
                                {{ $upgrade->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="inline-flex items-center gap-1">
                                    @for($i = 0; $i < $upgrade->from_stars; $i++)
                                        <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    @endfor
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="inline-flex items-center gap-1">
                                    @for($i = 0; $i < $upgrade->to_stars; $i++)
                                        <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    @endfor
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right text-yellow-600 dark:text-yellow-400 font-semibold">
                                {{ number_format($upgrade->coins_paid, 0) }} C
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($upgrade->status === 'completed')
                                    <span class="inline-flex items-center px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 text-xs rounded-full">
                                        สำเร็จ
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 text-xs rounded-full">
                                        คืนเงิน
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Upgrade Confirmation Modal --}}
    <div x-show="showModal"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4">
            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-black/50" @click="showModal = false"></div>

            {{-- Modal --}}
            <div x-show="showModal"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full mx-auto overflow-hidden">

                {{-- Header --}}
                <div class="p-6 text-center" :class="'bg-gradient-to-br ' + selectedGradient">
                    <div class="flex justify-center items-center gap-1 mb-3">
                        <template x-for="i in selectedStars">
                            <svg class="w-8 h-8 text-white drop-shadow-lg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        </template>
                    </div>
                    <h3 class="text-xl font-bold text-white" x-text="selectedName"></h3>
                </div>

                {{-- Content --}}
                <div class="p-6">
                    <div class="text-center mb-6">
                        <p class="text-gray-600 dark:text-gray-400 mb-2">คุณต้องการอัพเกรดเป็น</p>
                        <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">
                            <span x-text="selectedPrice.toLocaleString()"></span> Coins
                        </p>
                    </div>

                    <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 mb-6">
                        <p class="text-sm text-yellow-800 dark:text-yellow-200">
                            <strong>หมายเหตุ:</strong> การซื้อดาวไม่สามารถคืนเงินได้ กรุณาตรวจสอบให้แน่ใจก่อนยืนยัน
                        </p>
                    </div>

                    {{-- Buttons --}}
                    <div class="flex gap-3">
                        <button type="button"
                                @click="showModal = false"
                                class="flex-1 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl font-semibold hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            ยกเลิก
                        </button>
                        <form method="POST" action="{{ route('user.star-upgrade.upgrade') }}" class="flex-1">
                            @csrf
                            <input type="hidden" name="target_stars" x-model="selectedStars">
                            <button type="submit"
                                    class="w-full py-3 bg-gradient-to-r from-yellow-500 to-orange-500 hover:from-yellow-600 hover:to-orange-600 text-white rounded-xl font-semibold transition shadow-lg">
                                ยืนยันซื้อ
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function starUpgrade() {
    return {
        showModal: false,
        selectedStars: 0,
        selectedName: '',
        selectedPrice: 0,
        selectedGradient: '',

        confirmUpgrade(stars, name, price, gradient) {
            this.selectedStars = stars;
            this.selectedName = name;
            this.selectedPrice = price;
            this.selectedGradient = gradient;
            this.showModal = true;
        }
    };
}
</script>
@endpush
@endsection
