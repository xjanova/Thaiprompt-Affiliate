@extends('layouts.user-arrow-x')

@section('title', 'MLM Dashboard')

@push('styles')
<style>
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        transition: all 0.3s;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    .progress-ring {
        transform: rotate(-90deg);
    }

    .progress-ring-circle {
        transition: stroke-dashoffset 0.5s;
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-blue-50 to-pink-50 py-8">
    <div class="max-w-7xl mx-auto px-4">
        <!-- Welcome Header -->
        <x-arrow-x.card-v3 class="p-8 mb-8 bg-gradient-to-r from-purple-600 to-pink-600">
            <div class="flex items-center justify-between text-white">
                <div>
                    <h1 class="text-4xl font-black mb-2">
                        👋 สวัสดี, {{ auth()->user()->name }}!
                    </h1>
                    <p class="text-lg opacity-90">
                        รหัสสมาชิก: <span class="font-bold">{{ $member->member_code }}</span>
                    </p>
                </div>
                <div class="text-right">
                    <div class="text-sm opacity-75">แผน MLM ของคุณ</div>
                    <div class="text-2xl font-bold">{{ $member->plan->display_name }}</div>
                </div>
            </div>
        </x-arrow-x.card-v3>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total PV -->
            <div class="stat-card">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-4xl">⭐</div>
                    <div class="text-right">
                        <div class="text-sm text-gray-600 dark:text-gray-400">PV รวม</div>
                        <div class="text-3xl font-bold text-purple-600">{{ number_format($member->total_pv ?? 0) }}</div>
                    </div>
                </div>
                <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-purple-500 to-pink-500" style="width: {{ min(($member->total_pv ?? 0) / 10000 * 100, 100) }}%"></div>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-2">เป้า: 10,000 PV</div>
            </div>

            <!-- Direct Referrals -->
            <div class="stat-card">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-4xl">👥</div>
                    <div class="text-right">
                        <div class="text-sm text-gray-600 dark:text-gray-400">สมาชิกโดยตรง</div>
                        <div class="text-3xl font-bold text-blue-600">{{ $member->total_direct_referrals ?? 0 }}</div>
                    </div>
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400 mt-4">
                    ทีมทั้งหมด: <span class="font-bold text-gray-800 dark:text-white">{{ $member->total_team_members ?? 0 }}</span> คน
                </div>
            </div>

            <!-- Total Earnings -->
            <div class="stat-card">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-4xl">💰</div>
                    <div class="text-right">
                        <div class="text-sm text-gray-600 dark:text-gray-400">รายได้รวม</div>
                        <div class="text-3xl font-bold text-green-600">฿{{ number_format($member->total_earnings ?? 0, 2) }}</div>
                    </div>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-4">
                    <span class="inline-block px-2 py-1 bg-green-100 text-green-700 rounded-full">
                        +12% จากเดือนที่แล้ว
                    </span>
                </div>
            </div>

            <!-- Current Rank -->
            <div class="stat-card">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-4xl">🏆</div>
                    <div class="text-right">
                        <div class="text-sm text-gray-600 dark:text-gray-400">ระดับปัจจุบัน</div>
                        <div class="text-2xl font-bold text-yellow-600">{{ $member->rank->name ?? 'Starter' }}</div>
                    </div>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-4">
                    ระดับถัดไป: <span class="font-bold">Silver</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Binary Legs -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Binary Legs Stats -->
                @if($member->plan->type === 'binary' || $member->plan->type === 'hybrid')
                <x-arrow-x.card-v3 class="p-8">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-6 flex items-center">
                        <span class="mr-3">🔀</span>
                        Binary Legs
                    </h2>

                    <div class="grid grid-cols-2 gap-6 mb-6">
                        <!-- Left Leg -->
                        <div class="p-6 bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl border-2 border-green-200">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-bold text-green-800">⬅️ Left Leg</h3>
                            </div>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">PV:</span>
                                    <span class="font-bold text-green-600">{{ number_format($member->left_leg_pv ?? 0) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Sales:</span>
                                    <span class="font-bold text-green-600">฿{{ number_format($member->left_leg_sales ?? 0, 2) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Members:</span>
                                    <span class="font-bold text-green-600">{{ $member->left_leg_members ?? 0 }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Right Leg -->
                        <div class="p-6 bg-gradient-to-br from-red-50 to-pink-50 rounded-xl border-2 border-red-200">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-bold text-red-800">➡️ Right Leg</h3>
                            </div>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">PV:</span>
                                    <span class="font-bold text-red-600">{{ number_format($member->right_leg_pv ?? 0) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Sales:</span>
                                    <span class="font-bold text-red-600">฿{{ number_format($member->right_leg_sales ?? 0, 2) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Members:</span>
                                    <span class="font-bold text-red-600">{{ $member->right_leg_members ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pair Matching Info -->
                    <div class="p-6 bg-purple-50 rounded-xl">
                        <h3 class="font-bold text-purple-800 mb-4">📊 Pair Matching Today</h3>
                        <div class="grid grid-cols-3 gap-4 text-center">
                            <div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Weak Leg PV</div>
                                <div class="text-2xl font-bold text-purple-600">
                                    {{ number_format(min($member->left_leg_pv ?? 0, $member->right_leg_pv ?? 0)) }}
                                </div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Pairs</div>
                                <div class="text-2xl font-bold text-purple-600">
                                    {{ floor(min($member->left_leg_pv ?? 0, $member->right_leg_pv ?? 0)) }}
                                </div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Est. Commission</div>
                                <div class="text-2xl font-bold text-green-600">
                                    ฿{{ number_format(floor(min($member->left_leg_pv ?? 0, $member->right_leg_pv ?? 0)) * ($member->plan->binary_pair_commission ?? 100), 2) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </x-arrow-x.card-v3>
                @endif

                <!-- Recent Commissions -->
                <x-arrow-x.card-v3 class="p-8">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-6 flex items-center justify-between">
                        <span><span class="mr-3">💸</span>คอมมิชชันล่าสุด</span>
                        <a href="{{ route('user.mlm.commissions') }}" class="text-sm text-purple-600 hover:text-purple-700">
                            ดูทั้งหมด →
                        </a>
                    </h2>

                    <div class="space-y-3">
                        @forelse($recentCommissions ?? [] as $commission)
                        <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl flex items-center justify-between">
                            <div>
                                <div class="font-semibold text-gray-800 dark:text-white">{{ $commission->type_display }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $commission->created_at->diffForHumans() }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-bold text-green-600">฿{{ number_format($commission->commission_amount, 2) }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    <span class="inline-block px-2 py-0.5 rounded-full {{ $commission->status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                        {{ ucfirst($commission->status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <div class="text-4xl mb-2">💰</div>
                            <p>ยังไม่มีคอมมิชชัน</p>
                        </div>
                        @endforelse
                    </div>
                </x-arrow-x.card-v3>

                <!-- Quick Actions -->
                <x-arrow-x.card-v3 class="p-8">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-6 flex items-center">
                        <span class="mr-3">🚀</span>
                        Quick Actions
                    </h2>

                    <div class="grid grid-cols-2 gap-4">
                        <a href="{{ route('user.mlm.genealogy') }}" class="p-6 bg-purple-50 hover:bg-purple-100 rounded-xl text-center transition-all">
                            <div class="text-4xl mb-2">🌳</div>
                            <div class="font-bold text-gray-800 dark:text-white">ดูผังสายงาน</div>
                        </a>

                        <a href="{{ route('user.mlm.referral') }}" class="p-6 bg-blue-50 hover:bg-blue-100 rounded-xl text-center transition-all">
                            <div class="text-4xl mb-2">🔗</div>
                            <div class="font-bold text-gray-800 dark:text-white">Referral Link</div>
                        </a>

                        <a href="{{ route('user.mlm.commissions') }}" class="p-6 bg-green-50 hover:bg-green-100 rounded-xl text-center transition-all">
                            <div class="text-4xl mb-2">💸</div>
                            <div class="font-bold text-gray-800 dark:text-white">ประวัติคอมมิชชัน</div>
                        </a>

                        <a href="{{ route('user.mlm.team') }}" class="p-6 bg-pink-50 hover:bg-pink-100 rounded-xl text-center transition-all">
                            <div class="text-4xl mb-2">👥</div>
                            <div class="font-bold text-gray-800 dark:text-white">ทีมของฉัน</div>
                        </a>
                    </div>
                </x-arrow-x.card-v3>
            </div>

            <!-- Right Column: Additional Info -->
            <div class="space-y-8">
                <!-- Rank Progress -->
                <x-arrow-x.card-v3 class="p-8">
                    <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-6 flex items-center">
                        <span class="mr-3">🎯</span>
                        Rank Progress
                    </h3>

                    <div class="flex items-center justify-center mb-6">
                        <svg width="150" height="150">
                            <circle cx="75" cy="75" r="65" fill="none" stroke="#e5e7eb" stroke-width="10"/>
                            <circle cx="75" cy="75" r="65" fill="none" stroke="url(#gradient)" stroke-width="10"
                                    stroke-dasharray="408.4" stroke-dashoffset="{{ 408.4 - (408.4 * 0.65) }}"
                                    class="progress-ring-circle" style="transform: rotate(-90deg); transform-origin: center;"/>
                            <defs>
                                <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" style="stop-color:#7c3aed;stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:#ec4899;stop-opacity:1" />
                                </linearGradient>
                            </defs>
                            <text x="75" y="75" text-anchor="middle" dominant-baseline="middle" class="text-3xl font-bold fill-purple-600">65%</text>
                        </svg>
                    </div>

                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">✓ PV Requirement:</span>
                            <span class="font-bold text-green-600">5,000 / 5,000</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">○ Team PV:</span>
                            <span class="font-bold text-gray-800 dark:text-white">12,000 / 20,000</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">✓ Direct Referrals:</span>
                            <span class="font-bold text-green-600">3 / 3</span>
                        </div>
                    </div>

                    <div class="mt-6 p-4 bg-purple-50 rounded-xl">
                        <div class="text-xs text-purple-700 font-semibold mb-1">Next Rank:</div>
                        <div class="text-lg font-bold text-purple-800">Silver</div>
                        <div class="text-xs text-purple-600 mt-1">8,000 Team PV needed</div>
                    </div>
                </x-arrow-x.card-v3>

                <!-- Referral Link -->
                <x-arrow-x.card-v3 class="p-8">
                    <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-4 flex items-center">
                        <span class="mr-3">🔗</span>
                        Referral Link
                    </h3>

                    <div class="mb-4">
                        <input type="text" id="referral-link" value="{{ route('register', ['ref' => $member->member_code]) }}" readonly
                               class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900/50 text-sm">
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <button onclick="copyReferralLink()" class="px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg font-semibold transition-all">
                            📋 Copy
                        </button>
                        <button onclick="shareReferralLink()" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-semibold transition-all">
                            📤 Share
                        </button>
                    </div>

                    <!-- QR Code -->
                    <div class="mt-6 text-center">
                        <div class="inline-block p-4 bg-gray-100 dark:bg-gray-700 rounded-xl">
                            <div id="qr-code"></div>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-2">Scan QR Code to Register</div>
                    </div>
                </x-arrow-x.card-v3>

                <!-- Tips -->
                <div class="bg-gradient-to-br from-purple-100 to-pink-100 rounded-2xl p-8">
                    <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-4">💡 Tips</h3>
                    <ul class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                        <li class="flex items-start">
                            <span class="mr-2">•</span>
                            <span>สร้างสมดุลระหว่างขาซ้ายและขวาเพื่อเพิ่มคอมมิชชัน Binary</span>
                        </li>
                        <li class="flex items-start">
                            <span class="mr-2">•</span>
                            <span>แนะนำทีมให้ซื้อสินค้าต่อเนื่องเพื่อสะสม PV</span>
                        </li>
                        <li class="flex items-start">
                            <span class="mr-2">•</span>
                            <span>ติดตามผังสายงานเพื่อดูการเติบโตของทีม</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyReferralLink() {
    const input = document.getElementById('referral-link');
    input.select();
    document.execCommand('copy');
    alert('Copied referral link!');
}

function shareReferralLink() {
    const link = document.getElementById('referral-link').value;
    if (navigator.share) {
        navigator.share({
            title: 'Join my MLM team!',
            text: 'Join my MLM team and start earning today!',
            url: link
        });
    } else {
        copyReferralLink();
    }
}
</script>
@endsection
