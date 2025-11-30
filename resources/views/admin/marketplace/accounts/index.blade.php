@extends('layouts.admin-v3')

@section('title', 'จัดการบัญชี Marketplace')

@section('content')
<div class="p-6 space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">บัญชี Marketplace Affiliate</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">จัดการบัญชี Lazada, Shopee, TikTok Shop สำหรับระบบ Affiliate</p>
        </div>
        <a href="{{ route('admin.marketplace.accounts.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl hover:shadow-lg transition">
            <i class="fas fa-plus"></i>
            <span>เพิ่มบัญชีใหม่</span>
        </a>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        {{-- Total Accounts --}}
        <div class="glass-card p-4 rounded-xl">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-store text-blue-500 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">บัญชีทั้งหมด</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_accounts']) }}</p>
                </div>
            </div>
        </div>

        {{-- Active Accounts --}}
        <div class="glass-card p-4 rounded-xl">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-green-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-500 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">บัญชีใช้งาน</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['active_accounts']) }}</p>
                </div>
            </div>
        </div>

        {{-- Total Products --}}
        <div class="glass-card p-4 rounded-xl">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-purple-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-box text-purple-500 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">สินค้า Sync</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_products']) }}</p>
                </div>
            </div>
        </div>

        {{-- Total Orders --}}
        <div class="glass-card p-4 rounded-xl">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-orange-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-shopping-cart text-orange-500 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ออเดอร์ทั้งหมด</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_orders']) }}</p>
                </div>
            </div>
        </div>

        {{-- Total Commission --}}
        <div class="glass-card p-4 rounded-xl">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-emerald-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-coins text-emerald-500 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">คอมมิชชั่นรวม</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">฿{{ number_format($stats['total_commission'], 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="glass-card p-4 rounded-xl">
        <form method="GET" action="{{ route('admin.marketplace.accounts.index') }}" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="ค้นหาชื่อบัญชี, ร้านค้า..."
                       class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="w-40">
                <select name="platform" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">ทุก Platform</option>
                    @foreach($platforms as $platform)
                        <option value="{{ $platform->id }}" {{ request('platform') == $platform->id ? 'selected' : '' }}>
                            {{ $platform->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="w-40">
                <select name="status" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">ทุกสถานะ</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>ใช้งาน</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>ไม่ใช้งาน</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>รอตรวจสอบ</option>
                    <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>ระงับ</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                <i class="fas fa-search mr-1"></i> ค้นหา
            </button>
            <a href="{{ route('admin.marketplace.accounts.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                <i class="fas fa-redo mr-1"></i> รีเซ็ต
            </a>
        </form>
    </div>

    {{-- Accounts Table --}}
    <div class="glass-card rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">บัญชี</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Platform</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สินค้า</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ออเดอร์</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">คอมมิชชั่น</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Sync ล่าสุด</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($accounts as $account)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold">
                                        {{ strtoupper(substr($account->account_name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $account->account_name }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $account->shop_name ?? $account->shop_id }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if($account->platform)
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-medium
                                        @if($account->platform->slug == 'lazada') bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400
                                        @elseif($account->platform->slug == 'shopee') bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                                        @elseif($account->platform->slug == 'tiktok') bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-400
                                        @else bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300
                                        @endif">
                                        {{ $account->platform->name }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    $statusColors = [
                                        'active' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                        'inactive' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                        'pending' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                        'suspended' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                    ];
                                    $statusLabels = [
                                        'active' => 'ใช้งาน',
                                        'inactive' => 'ไม่ใช้งาน',
                                        'pending' => 'รอตรวจสอบ',
                                        'suspended' => 'ระงับ',
                                    ];
                                @endphp
                                <span class="px-2 py-1 rounded-lg text-xs font-medium {{ $statusColors[$account->status] ?? $statusColors['inactive'] }}">
                                    {{ $statusLabels[$account->status] ?? $account->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center text-gray-900 dark:text-white">
                                {{ number_format($account->total_products_synced ?? 0) }}
                            </td>
                            <td class="px-6 py-4 text-center text-gray-900 dark:text-white">
                                {{ number_format($account->total_orders_synced ?? 0) }}
                            </td>
                            <td class="px-6 py-4 text-right text-gray-900 dark:text-white font-medium">
                                ฿{{ number_format($account->total_commission ?? 0, 2) }}
                            </td>
                            <td class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ $account->last_sync_at ? $account->last_sync_at->diffForHumans() : '-' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('admin.marketplace.accounts.show', $account) }}"
                                       class="p-2 text-blue-600 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition"
                                       title="ดูรายละเอียด">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.marketplace.accounts.edit', $account) }}"
                                       class="p-2 text-yellow-600 hover:bg-yellow-100 dark:hover:bg-yellow-900/30 rounded-lg transition"
                                       title="แก้ไข">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button"
                                            onclick="syncAll({{ $account->id }})"
                                            class="p-2 text-green-600 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-lg transition"
                                            title="Sync ข้อมูล">
                                        <i class="fas fa-sync"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-store text-4xl mb-3 text-gray-300 dark:text-gray-600"></i>
                                    <p>ยังไม่มีบัญชี Marketplace</p>
                                    <a href="{{ route('admin.marketplace.accounts.create') }}" class="mt-2 text-blue-500 hover:underline">
                                        + เพิ่มบัญชีใหม่
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($accounts->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $accounts->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    // Sync All Products & Orders
    function syncAll(accountId) {
        if (!confirm('ต้องการ Sync สินค้าและออเดอร์ทั้งหมดหรือไม่?')) return;

        const btn = event.target.closest('button');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        fetch(`{{ url('admin/marketplace/accounts') }}/${accountId}/sync-all`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('เกิดข้อผิดพลาด: ' + data.message);
            }
        })
        .catch(error => {
            alert('เกิดข้อผิดพลาด: ' + error.message);
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync"></i>';
        });
    }
</script>
@endpush
@endsection
