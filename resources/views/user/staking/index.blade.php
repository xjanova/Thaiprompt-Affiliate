@extends('layouts.user')

@section('title', 'Token Staking - TPIX')

@section('content')
{{-- Hero Section --}}
<div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-orange-500 via-pink-500 to-purple-600 dark:from-orange-900 dark:via-pink-900 dark:to-purple-900 p-12 mb-8 shadow-2xl">
    <div class="absolute inset-0 bg-black/10 dark:bg-white/5"></div>
    <div class="relative z-10 text-center">
        <div class="inline-flex items-center gap-3 bg-white/20 dark:bg-black/30 backdrop-blur-sm rounded-full px-6 py-2 mb-4">
            <i class="fas fa-coins text-2xl text-white"></i>
            <span class="text-white font-bold text-xl">TPIX Staking</span>
        </div>
        <h1 class="text-4xl md:text-5xl font-bold text-white mb-3 drop-shadow-lg">
            Stake Token รับรางวัลทุกชั่วโมง
        </h1>
        <p class="text-white/90 text-lg mb-2">รองรับ 5 ระยะเวลา Lock Period • APY สูงสุด 200%</p>
        <p class="text-white/80 text-sm">ปลอดภัยด้วย Smart Contract บน TPIX Blockchain</p>
    </div>
</div>

{{-- Stats Overview --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-200 dark:border-gray-700 text-center transform hover:scale-105 transition-transform duration-300">
        <div class="w-14 h-14 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 dark:from-blue-600 dark:to-indigo-700 flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-layer-group text-2xl text-white"></i>
        </div>
        <div id="totalStaked" class="text-3xl font-bold text-gray-900 dark:text-white mb-2">-</div>
        <div class="text-sm text-gray-600 dark:text-gray-400 font-medium">Total Staked</div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-200 dark:border-gray-700 text-center transform hover:scale-105 transition-transform duration-300">
        <div class="w-14 h-14 rounded-full bg-gradient-to-br from-green-500 to-emerald-600 dark:from-green-600 dark:to-emerald-700 flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-users text-2xl text-white"></i>
        </div>
        <div id="totalStakers" class="text-3xl font-bold text-gray-900 dark:text-white mb-2">-</div>
        <div class="text-sm text-gray-600 dark:text-gray-400 font-medium">Active Stakers</div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-200 dark:border-gray-700 text-center transform hover:scale-105 transition-transform duration-300">
        <div class="w-14 h-14 rounded-full bg-gradient-to-br from-yellow-500 to-orange-600 dark:from-yellow-600 dark:to-orange-700 flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-percentage text-2xl text-white"></i>
        </div>
        <div id="avgAPY" class="text-3xl font-bold text-gray-900 dark:text-white mb-2">-</div>
        <div class="text-sm text-gray-600 dark:text-gray-400 font-medium">Average APY</div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-200 dark:border-gray-700 text-center transform hover:scale-105 transition-transform duration-300">
        <div class="w-14 h-14 rounded-full bg-gradient-to-br from-pink-500 to-rose-600 dark:from-pink-600 dark:to-rose-700 flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-gift text-2xl text-white"></i>
        </div>
        <div id="totalRewards" class="text-3xl font-bold text-gray-900 dark:text-white mb-2">-</div>
        <div class="text-sm text-gray-600 dark:text-gray-400 font-medium">Total Rewards</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    {{-- Staking Pools --}}
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            {{-- Header --}}
            <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-gray-900 dark:to-gray-800 border-b border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                        <span class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-600 to-pink-600 flex items-center justify-center">
                            <i class="fas fa-swimming-pool text-white text-lg"></i>
                        </span>
                        Staking Pools
                    </h2>

                    {{-- Filter Buttons --}}
                    <div class="flex items-center gap-2">
                        <button onclick="filterPools('all')" class="pool-filter active px-4 py-2 rounded-lg bg-purple-600 text-white text-sm font-medium transition-all duration-200" data-filter="all">
                            ทั้งหมด
                        </button>
                        <button onclick="filterPools('active')" class="pool-filter px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition-all duration-200" data-filter="active">
                            กำลังใช้งาน
                        </button>
                        <button onclick="filterPools('mine')" class="pool-filter px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition-all duration-200" data-filter="mine">
                            ของฉัน
                        </button>
                    </div>
                </div>
            </div>

            {{-- Pool List --}}
            <div id="stakingPools" class="divide-y divide-gray-200 dark:divide-gray-700">
                <div class="flex items-center justify-center py-12">
                    <div class="animate-spin rounded-full h-12 w-12 border-4 border-purple-600 border-t-transparent"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- My Stakes Sidebar --}}
    <div class="lg:col-span-1">
        {{-- My Total Staking Card --}}
        <div class="bg-gradient-to-br from-purple-600 via-pink-600 to-blue-600 dark:from-purple-800 dark:via-pink-800 dark:to-blue-800 rounded-3xl shadow-2xl overflow-hidden mb-6">
            <div class="p-8 text-center text-white">
                <div class="text-sm opacity-90 mb-2">ยอด Stake ของฉัน</div>
                <div id="myTotalStaked" class="text-4xl font-bold mb-1">0.00</div>
                <div class="text-sm opacity-75 mb-6">TPIX</div>

                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="text-xs opacity-75 mb-1">Pending Rewards</div>
                            <div id="myPendingRewards" class="text-lg font-bold">0.00</div>
                        </div>
                        <div>
                            <div class="text-xs opacity-75 mb-1">Total Earned</div>
                            <div id="myTotalEarned" class="text-lg font-bold">0.00</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- My Active Stakes --}}
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 border-b border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-wallet text-purple-600 dark:text-purple-400"></i>
                    Stakes ที่ใช้งานอยู่
                </h3>
            </div>
            <div id="myStakes" class="divide-y divide-gray-200 dark:divide-gray-700 max-h-[600px] overflow-y-auto">
                <div class="flex flex-col items-center justify-center py-12 px-6 text-center">
                    <i class="fas fa-inbox text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ยังไม่มี Stake ที่ใช้งานอยู่</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * ฟังก์ชันจัดการ Pool Filters
 * ใช้สำหรับกรองแสดง Staking Pools
 */
function filterPools(filter) {
    // อัพเดท active state ของปุ่ม
    document.querySelectorAll('.pool-filter').forEach(btn => {
        btn.classList.remove('active', 'bg-purple-600', 'text-white');
        btn.classList.add('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
    });

    const activeBtn = document.querySelector(`[data-filter="${filter}"]`);
    if (activeBtn) {
        activeBtn.classList.add('active', 'bg-purple-600', 'text-white');
        activeBtn.classList.remove('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
    }

    // จะเพิ่ม logic กรอง pools ในภายหลัง
    console.log('Filter pools:', filter);
}

// Log เมื่อโหลดหน้าสำเร็จ
console.log('TPIX Staking - View loaded');
</script>
@endpush
@endsection
