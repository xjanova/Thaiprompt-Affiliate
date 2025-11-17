@extends('layouts.admin-v3')

@section('title', 'กระเป๋าเงินทั้งหมด')

@section('content')
<div class="space-y-6">
    <!-- System Statistics -->
    <div class="bg-gradient-to-br from-purple-600 via-indigo-600 to-blue-600 rounded-2xl shadow-2xl p-8 text-white">
        <div class="mb-6">
            <h2 class="text-3xl font-bold mb-2">สถิติระบบกระเป๋าเงิน</h2>
            <p class="text-indigo-100 text-sm">ภาพรวมกระเป๋าเงินทั้งหมดในระบบ</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <!-- Total Wallets -->
            <div class="glass-fusion backdrop-blur-sm rounded-xl p-6" border border-white/20 dark:border-white/10>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-indigo-100">กระเป๋าทั้งหมด</span>
                    <span class="text-2xl">💼</span>
                </div>
                <p class="text-4xl font-bold">{{ number_format($systemStats['total_wallets']) }}</p>
                <p class="text-sm text-indigo-100 mt-1">กระเป๋า</p>
            </div>

            <!-- Total Balance -->
            <div class="glass-fusion backdrop-blur-sm rounded-xl p-6" border border-white/20 dark:border-white/10>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-indigo-100">ยอดรวมทั้งหมด</span>
                    <span class="text-2xl">💰</span>
                </div>
                <p class="text-3xl font-bold">{{ number_format($systemStats['total_balance'], 2) }}</p>
                <p class="text-sm text-indigo-100 mt-1">THB</p>
            </div>

            <!-- Today Transactions -->
            <div class="glass-fusion backdrop-blur-sm rounded-xl p-6" border border-white/20 dark:border-white/10>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-indigo-100">ธุรกรรมวันนี้</span>
                    <span class="text-2xl">📊</span>
                </div>
                <p class="text-4xl font-bold">{{ number_format($systemStats['today_transactions']) }}</p>
                <p class="text-sm text-indigo-100 mt-1">รายการ</p>
            </div>

            <!-- Monthly Volume -->
            <div class="glass-fusion backdrop-blur-sm rounded-xl p-6" border border-white/20 dark:border-white/10>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-indigo-100">ยอดประจำเดือน</span>
                    <span class="text-2xl">📈</span>
                </div>
                <p class="text-3xl font-bold">{{ number_format($systemStats['monthly_volume'], 2) }}</p>
                <p class="text-sm text-indigo-100 mt-1">THB</p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">กรองข้อมูล</h3>
        <form method="GET" action="{{ route('admin.wallet.all') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ค้นหา</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="ชื่อ, อีเมล, Wallet Address" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สถานะ</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500">
                    <option value="">ทั้งหมด</option>
                    <option value="active" {{ ($filters['status'] ?? '') == 'active' ? 'selected' : '' }}>ใช้งานได้</option>
                    <option value="locked" {{ ($filters['status'] ?? '') == 'locked' ? 'selected' : '' }}>ถูกล็อก</option>
                    <option value="suspended" {{ ($filters['status'] ?? '') == 'suspended' ? 'selected' : '' }}>ระงับ</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ยอดขั้นต่ำ</label>
                <input type="number" name="min_balance" value="{{ $filters['min_balance'] ?? '' }}" step="0.01" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ยอดสูงสุด</label>
                <input type="number" name="max_balance" value="{{ $filters['max_balance'] ?? '' }}" step="0.01" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500">
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-xl transition">
                    🔍 ค้นหา
                </button>
            </div>
        </form>
    </div>

    <!-- Wallets Table -->
    <div class="glass-fusion rounded-xl shadow-lg overflow-hidden" border border-white/20 dark:border-white/10>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ผู้ใช้</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Wallet Address</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ยอดคงเหลือ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">สร้างเมื่อ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">การจัดการ</th>
                    </tr>
                </thead>
                <tbody class="glass-fusion divide-y divide-gray-200">
                    @forelse($wallets as $wallet)
                    <tr class="hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 transition">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                    <span class="text-indigo-600 font-bold">{{ substr($wallet->user->name, 0, 1) }}</span>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $wallet->user->name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $wallet->user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-mono text-gray-900 bg-gray-100/50 dark:bg-gray-800/50 px-2 py-1 rounded">{{ $wallet->wallet_address }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm">
                                <div class="font-bold text-indigo-600">{{ number_format($wallet->balance, 2) }} {{ $wallet->currency }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                @if($wallet->status == 'active') bg-green-100 text-green-800
                                @elseif($wallet->status == 'locked') bg-red-100 text-red-800
                                @else bg-yellow-100 text-yellow-800
                                @endif">
                                @if($wallet->status == 'active') ✅ ใช้งานได้
                                @elseif($wallet->status == 'locked') 🔒 ถูกล็อก
                                @else ⏸️ ระงับ
                                @endif
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $wallet->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                            <a href="{{ route('admin.wallet.show', $wallet->id) }}" class="text-indigo-600 hover:text-indigo-900">
                                ดูรายละเอียด →
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center">
                            <div class="text-gray-400">
                                <div class="text-4xl mb-2">💼</div>
                                <p>ไม่พบกระเป๋าเงิน</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($wallets->hasPages())
        <div class="px-6 py-4 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50">
            {{ $wallets->links() }}
        </div>
        @endif
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <span class="text-4xl">🟢</span>
                </div>
                <div class="ml-4">
                    <h4 class="text-lg font-bold text-gray-900 dark:text-white">กระเป๋าใช้งานได้</h4>
                    <p class="text-3xl font-bold text-green-600">{{ number_format($systemStats['active_wallets'] ?? 0) }}</p>
                </div>
            </div>
        </div>

        <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <span class="text-4xl">🔴</span>
                </div>
                <div class="ml-4">
                    <h4 class="text-lg font-bold text-gray-900 dark:text-white">กระเป๋าถูกล็อก</h4>
                    <p class="text-3xl font-bold text-red-600">{{ number_format($systemStats['locked_wallets'] ?? 0) }}</p>
                </div>
            </div>
        </div>

        <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <span class="text-4xl">💵</span>
                </div>
                <div class="ml-4">
                    <h4 class="text-lg font-bold text-gray-900 dark:text-white">ยอดเฉลี่ย/กระเป๋า</h4>
                    <p class="text-2xl font-bold text-indigo-600">{{ number_format($systemStats['average_balance'] ?? 0, 2) }} THB</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
