@extends('layouts.admin-v3')

@section('title', 'LINE Signup Rewards')

@section('content')
{{--
    LINE Signup Rewards - V3 Theme
    ใช้ Tailwind CSS + Alpine.js
    Dark Mode Support + Responsive
--}}
<div class="min-h-screen" x-data="rewardsManager()" x-init="init()">
    {{-- Page Header --}}
    <div class="relative mb-8 rounded-2xl bg-gradient-to-br from-yellow-500 via-orange-500 to-red-500 p-8 shadow-2xl overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 40px 40px;"></div>
        </div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="text-white">
                <div class="flex items-center gap-3 mb-2">
                    <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                        <i class="fas fa-gift text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl md:text-4xl font-bold tracking-tight">
                            Signup Rewards
                        </h1>
                        <p class="text-white/90 text-sm md:text-base mt-1">
                            จัดการรางวัลสำหรับผู้ที่ signup สำเร็จ
                        </p>
                    </div>
                </div>
            </div>

            <a href="{{ route('admin.line-membership-signup.index') }}"
               class="px-6 py-3 bg-white/20 backdrop-blur-md border border-white/30 rounded-xl text-white font-medium hover:bg-white/30 transition-all duration-300 flex items-center gap-2">
                <i class="fas fa-arrow-left"></i>
                <span>กลับ Dashboard</span>
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-6">
        <form method="GET" action="{{ route('admin.line-membership-signup.rewards.index') }}" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-filter mr-1"></i>
                    สถานะ
                </label>
                <select
                    name="status"
                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-yellow-500 transition-all duration-300"
                    x-model="statusFilter"
                >
                    <option value="">ทั้งหมด</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="granted" {{ request('status') === 'granted' ? 'selected' : '' }}>Granted</option>
                    <option value="claimed" {{ request('status') === 'claimed' ? 'selected' : '' }}>Claimed</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                </select>
            </div>

            <div class="flex items-end gap-3">
                <button
                    type="submit"
                    class="px-6 py-3 bg-gradient-to-r from-yellow-500 to-orange-500 hover:from-yellow-600 hover:to-orange-600 text-white rounded-xl font-medium transition-all duration-300 hover:shadow-lg transform hover:-translate-y-0.5 flex items-center gap-2"
                >
                    <i class="fas fa-filter"></i>
                    <span>กรองข้อมูล</span>
                </button>
                <a
                    href="{{ route('admin.line-membership-signup.rewards.index') }}"
                    class="px-6 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl font-medium transition-all duration-300 flex items-center gap-2"
                >
                    <i class="fas fa-redo"></i>
                    <span>รีเซ็ต</span>
                </a>
            </div>
        </form>
    </div>

    {{-- Rewards Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-table text-yellow-500"></i>
                รายการ Rewards ({{ $rewards->total() }} รายการ)
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">User</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Session</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Reward Type</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($rewards as $reward)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-colors duration-200">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-bold text-gray-900 dark:text-white">#{{ $reward->id }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($reward->user)
                                <div class="flex items-center gap-2">
                                    <div class="w-10 h-10 bg-gradient-to-br from-purple-400 to-purple-600 rounded-lg flex items-center justify-center text-white font-bold">
                                        {{ substr($reward->user->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-900 dark:text-white font-medium">{{ $reward->user->name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $reward->user->email }}</div>
                                    </div>
                                </div>
                            @else
                                <span class="text-sm text-gray-400 dark:text-gray-500">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($reward->session)
                                <a href="{{ route('admin.line-membership-signup.sessions.show', $reward->session) }}"
                                   class="inline-flex items-center gap-1 text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                    <i class="fas fa-link text-xs"></i>
                                    Session #{{ $reward->session->id }}
                                </a>
                            @else
                                <span class="text-sm text-gray-400 dark:text-gray-500">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                @if($reward->reward_type === 'points')
                                    <i class="fas fa-coins text-yellow-500"></i>
                                @elseif($reward->reward_type === 'bonus')
                                    <i class="fas fa-money-bill-wave text-green-500"></i>
                                @elseif($reward->reward_type === 'discount')
                                    <i class="fas fa-percent text-blue-500"></i>
                                @else
                                    <i class="fas fa-gift text-purple-500"></i>
                                @endif
                                <span class="text-sm font-medium text-gray-900 dark:text-white capitalize">
                                    {{ $reward->reward_type }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-bold text-gray-900 dark:text-white">
                                {{ $reward->reward_amount ?? 'N/A' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($reward->status === 'claimed')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                    <i class="fas fa-check-circle"></i>
                                    Claimed
                                </span>
                            @elseif($reward->status === 'granted')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                    <i class="fas fa-gift"></i>
                                    Granted
                                </span>
                            @elseif($reward->status === 'pending')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300">
                                    <i class="fas fa-clock"></i>
                                    Pending
                                </span>
                            @elseif($reward->status === 'expired')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">
                                    <i class="fas fa-times-circle"></i>
                                    Expired
                                </span>
                            @else
                                <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
                                    {{ $reward->status }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                            <div class="flex flex-col">
                                <span class="font-medium">{{ $reward->created_at->format('d M Y') }}</span>
                                <span class="text-xs text-gray-500">{{ $reward->created_at->format('H:i') }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                @if($reward->status === 'pending')
                                <form action="{{ route('admin.line-membership-signup.rewards.grant', $reward) }}" method="POST" class="inline">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white text-xs font-medium transition-all duration-300 hover:shadow-lg"
                                        @click="return confirm('ต้องการมอบรางวัลนี้ใช่หรือไม่?')"
                                    >
                                        <i class="fas fa-hand-holding-heart mr-1"></i>
                                        Grant
                                    </button>
                                </form>
                                @endif

                                <button
                                    @click="viewDetails({{ json_encode($reward) }})"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-yellow-50 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400 hover:bg-yellow-100 dark:hover:bg-yellow-900/50 transition-colors duration-200"
                                    title="ดูรายละเอียด"
                                >
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                    <i class="fas fa-inbox text-2xl text-gray-400"></i>
                                </div>
                                <p class="text-gray-500 dark:text-gray-400 font-medium">ไม่พบข้อมูล rewards</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($rewards->hasPages())
        <div class="p-6 border-t border-gray-200 dark:border-gray-700">
            {{ $rewards->links() }}
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
/**
 * Rewards Manager - Alpine.js Component
 *
 * จัดการ state สำหรับ rewards
 */
function rewardsManager() {
    return {
        statusFilter: '{{ request("status") }}',

        /**
         * เริ่มต้น component
         */
        init() {
            // เพิ่ม functionality เพิ่มเติมได้ที่นี่
        },

        /**
         * ดูรายละเอียด reward
         */
        viewDetails(reward) {
            console.log('Reward details:', reward);
            // เพิ่ม modal หรือ redirect ไปหน้ารายละเอียดได้ที่นี่
        }
    };
}
</script>
@endpush
@endsection
