@extends('layouts.admin')

@section('title', 'TPIX Wallets Management')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="bg-gradient-to-r from-cyan-600 via-blue-600 to-indigo-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white opacity-10 rounded-full"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-white opacity-10 rounded-full"></div>
        <div class="relative z-10">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold mb-2 flex items-center gap-3">
                        <span class="text-5xl">👛</span>
                        <span>TPIX Wallets Management</span>
                    </h1>
                    <p class="text-cyan-100">จัดการ Wallets ทั้งหมดในระบบ TPIX Blockchain</p>
                </div>
                <a href="{{ route('admin.tpix.dashboard') }}" class="px-4 py-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg text-sm font-semibold transition">
                    ← Dashboard
                </a>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6">
        <form method="GET" action="{{ route('admin.tpix.wallets') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            {{-- Search --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    🔍 ค้นหา
                </label>
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="ที่อยู่, ชื่อ, อีเมล..."
                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                >
            </div>

            {{-- Status Filter --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    📊 สถานะ
                </label>
                <select
                    name="status"
                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                >
                    <option value="">ทั้งหมด</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                </select>
            </div>

            {{-- Sort --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    📈 เรียงตาม
                </label>
                <select
                    name="sort"
                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                >
                    <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>วันที่สร้าง</option>
                    <option value="balance" {{ request('sort') == 'balance' ? 'selected' : '' }}>ยอดคงเหลือ</option>
                    <option value="address" {{ request('sort') == 'address' ? 'selected' : '' }}>ที่อยู่</option>
                </select>
            </div>

            {{-- Submit --}}
            <div class="flex items-end gap-2">
                <button
                    type="submit"
                    class="flex-1 px-6 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transition"
                >
                    ค้นหา
                </button>
                <a
                    href="{{ route('admin.tpix.wallets') }}"
                    class="px-4 py-2 bg-gray-200 dark:bg-slate-700 hover:bg-gray-300 dark:hover:bg-slate-600 text-gray-700 dark:text-gray-300 font-semibold rounded-lg transition"
                >
                    รีเซ็ต
                </a>
            </div>
        </form>
    </div>

    {{-- Wallets Table --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <span>📋</span>
                    <span>รายการ Wallets ({{ number_format($wallets->total()) }} รายการ)</span>
                </h2>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                <thead class="bg-gray-50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ที่อยู่ Wallet
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            เจ้าของ
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ยอดคงเหลือ
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            สถานะ
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            สร้างเมื่อ
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse($wallets as $wallet)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-cyan-400 to-blue-500 flex items-center justify-center text-white font-bold text-xs">
                                    {{ strtoupper(substr($wallet->address, 2, 2)) }}
                                </div>
                                <div>
                                    <div class="text-sm font-mono font-semibold text-gray-900 dark:text-white">
                                        {{ Str::limit($wallet->address, 20) }}
                                    </div>
                                    <button
                                        onclick="copyToClipboard('{{ $wallet->address }}')"
                                        class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300"
                                    >
                                        📋 Copy
                                    </button>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($wallet->user)
                            <div>
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $wallet->user->name }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $wallet->user->email }}
                                </div>
                            </div>
                            @else
                            <span class="text-sm text-gray-500 dark:text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ number_format($wallet->balance, 4) }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                TPIX
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $statusColors = [
                                    'active' => 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
                                    'inactive' => 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300',
                                    'suspended' => 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$wallet->status] ?? $statusColors['inactive'] }}">
                                {{ ucfirst($wallet->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                            {{ $wallet->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                            <button
                                onclick="viewWalletDetails({{ $wallet->id }})"
                                class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium"
                            >
                                ดูรายละเอียด →
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center text-gray-500 dark:text-gray-400">
                                <div class="text-6xl mb-4">👛</div>
                                <div class="text-lg font-semibold mb-2">ไม่พบ Wallets</div>
                                <div class="text-sm">ลองปรับเกณฑ์การค้นหาใหม่</div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($wallets->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-700">
            {{ $wallets->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('คัดลอกที่อยู่ไปยังคลิปบอร์ดแล้ว!');
    }).catch(err => {
        console.error('Failed to copy:', err);
    });
}

function viewWalletDetails(walletId) {
    // Navigate to wallet details page (implement as needed)
    alert('Wallet ID: ' + walletId + '\nรายละเอียดเต็มจะแสดงในเวอร์ชันถัดไป');
}
</script>
@endpush
@endsection
