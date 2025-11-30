@extends('layouts.admin-v3')

@section('title', 'รายละเอียดบัญชี - ' . $account->account_name)

@section('content')
<div class="p-6 space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.marketplace.accounts.index') }}"
               class="p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="flex items-center gap-3">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-xl font-bold">
                    {{ strtoupper(substr($account->account_name, 0, 2)) }}
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $account->account_name }}</h1>
                    <p class="text-gray-600 dark:text-gray-400">{{ $account->shop_name ?? $account->shop_id ?? 'ไม่ระบุร้านค้า' }}</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" onclick="testConnection()"
                    class="px-4 py-2 bg-green-500 text-white rounded-xl hover:bg-green-600 transition">
                <i class="fas fa-plug mr-2"></i>ทดสอบ API
            </button>
            <button type="button" onclick="syncAll()"
                    class="px-4 py-2 bg-blue-500 text-white rounded-xl hover:bg-blue-600 transition">
                <i class="fas fa-sync mr-2"></i>Sync ทั้งหมด
            </button>
            <a href="{{ route('admin.marketplace.accounts.edit', $account) }}"
               class="px-4 py-2 bg-yellow-500 text-white rounded-xl hover:bg-yellow-600 transition">
                <i class="fas fa-edit mr-2"></i>แก้ไข
            </a>
        </div>
    </div>

    {{-- Status Badge --}}
    <div class="flex items-center gap-4">
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
        <span class="px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$account->status] ?? $statusColors['inactive'] }}">
            {{ $statusLabels[$account->status] ?? $account->status }}
        </span>

        @if($account->platform)
            <span class="px-3 py-1 rounded-full text-sm font-medium
                @if($account->platform->slug == 'lazada') bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400
                @elseif($account->platform->slug == 'shopee') bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                @elseif($account->platform->slug == 'tiktok') bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-400
                @else bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300
                @endif">
                {{ $account->platform->name }}
            </span>
        @endif

        @if($account->last_error)
            <span class="px-3 py-1 bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded-full text-sm">
                <i class="fas fa-exclamation-triangle mr-1"></i>มีข้อผิดพลาด
            </span>
        @endif
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="glass-card p-4 rounded-xl text-center">
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_products']) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">สินค้าทั้งหมด</p>
        </div>
        <div class="glass-card p-4 rounded-xl text-center">
            <p class="text-3xl font-bold text-green-600">{{ number_format($stats['active_products']) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">สินค้าใช้งาน</p>
        </div>
        <div class="glass-card p-4 rounded-xl text-center">
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_orders']) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">ออเดอร์ทั้งหมด</p>
        </div>
        <div class="glass-card p-4 rounded-xl text-center">
            <p class="text-3xl font-bold text-orange-600">{{ number_format($stats['pending_orders']) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">ออเดอร์รอดำเนินการ</p>
        </div>
        <div class="glass-card p-4 rounded-xl text-center">
            <p class="text-3xl font-bold text-blue-600">฿{{ number_format($stats['total_sales'], 0) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">ยอดขายรวม</p>
        </div>
        <div class="glass-card p-4 rounded-xl text-center">
            <p class="text-3xl font-bold text-emerald-600">฿{{ number_format($stats['total_commission'], 0) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">คอมมิชชั่นรวม</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Account Details --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Account Info --}}
            <div class="glass-card p-6 rounded-xl">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ข้อมูลบัญชี</h3>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Shop ID</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $account->shop_id ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">ชื่อร้านค้า</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $account->shop_name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">เจ้าของบัญชี</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $account->user->name ?? 'Admin' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">อัตราคอมมิชชั่น</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ number_format($account->commission_rate ?? 0, 2) }}%</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Sync สินค้าอัตโนมัติ</p>
                        <p class="font-medium {{ $account->auto_sync_products ? 'text-green-600' : 'text-gray-400' }}">
                            {{ $account->auto_sync_products ? 'เปิดใช้งาน' : 'ปิด' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Sync ออเดอร์อัตโนมัติ</p>
                        <p class="font-medium {{ $account->auto_sync_orders ? 'text-green-600' : 'text-gray-400' }}">
                            {{ $account->auto_sync_orders ? 'เปิดใช้งาน' : 'ปิด' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Sync ล่าสุด</p>
                        <p class="font-medium text-gray-900 dark:text-white">
                            {{ $account->last_sync_at ? $account->last_sync_at->format('d/m/Y H:i') : '-' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Token หมดอายุ</p>
                        <p class="font-medium {{ $account->isTokenExpired() ? 'text-red-600' : 'text-gray-900 dark:text-white' }}">
                            {{ $account->token_expires_at ? $account->token_expires_at->format('d/m/Y H:i') : '-' }}
                            @if($account->isTokenExpired())
                                <span class="text-red-500 text-sm">(หมดอายุแล้ว)</span>
                            @endif
                        </p>
                    </div>
                </div>

                @if($account->last_error)
                    <div class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                        <p class="text-sm text-red-700 dark:text-red-400">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            <strong>ข้อผิดพลาดล่าสุด:</strong> {{ $account->last_error }}
                        </p>
                    </div>
                @endif
            </div>

            {{-- Sync Logs --}}
            <div class="glass-card p-6 rounded-xl">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ประวัติ Sync (10 รายการล่าสุด)</h3>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">ประเภท</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400">สถานะ</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400">รายการ</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400">ระยะเวลา</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400">เวลา</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($account->syncLogs as $log)
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                        {{ $log->sync_type == 'products' ? 'สินค้า' : 'ออเดอร์' }}
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        @if($log->sync_status == 'completed')
                                            <span class="px-2 py-0.5 bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded text-xs">สำเร็จ</span>
                                        @elseif($log->sync_status == 'failed')
                                            <span class="px-2 py-0.5 bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded text-xs">ล้มเหลว</span>
                                        @elseif($log->sync_status == 'partial')
                                            <span class="px-2 py-0.5 bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 rounded text-xs">บางส่วน</span>
                                        @else
                                            <span class="px-2 py-0.5 bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 rounded text-xs">กำลังทำงาน</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-center text-sm text-gray-600 dark:text-gray-400">
                                        {{ $log->items_created ?? 0 }} สร้าง / {{ $log->items_updated ?? 0 }} อัพเดท
                                    </td>
                                    <td class="px-4 py-2 text-center text-sm text-gray-600 dark:text-gray-400">
                                        {{ $log->duration_seconds ?? 0 }}s
                                    </td>
                                    <td class="px-4 py-2 text-right text-sm text-gray-500 dark:text-gray-400">
                                        {{ $log->created_at->diffForHumans() }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                        ยังไม่มีประวัติ Sync
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="space-y-6">
            {{-- Sync Actions --}}
            <div class="glass-card p-6 rounded-xl space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Sync ข้อมูล</h3>

                <button type="button" onclick="syncProducts()"
                        class="w-full px-4 py-3 bg-purple-500 text-white rounded-xl hover:bg-purple-600 transition flex items-center justify-center gap-2">
                    <i class="fas fa-box"></i>
                    <span>Sync สินค้า</span>
                </button>

                <button type="button" onclick="syncOrders()"
                        class="w-full px-4 py-3 bg-orange-500 text-white rounded-xl hover:bg-orange-600 transition flex items-center justify-center gap-2">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Sync ออเดอร์</span>
                </button>

                <button type="button" onclick="syncAll()"
                        class="w-full px-4 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl hover:shadow-lg transition flex items-center justify-center gap-2">
                    <i class="fas fa-sync"></i>
                    <span>Sync ทั้งหมด</span>
                </button>
            </div>

            {{-- Quick Links --}}
            <div class="glass-card p-6 rounded-xl space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ลิงก์ด่วน</h3>

                <a href="{{ route('admin.marketplace.products.index', ['account' => $account->id]) }}"
                   class="block px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                    <i class="fas fa-box mr-2"></i>ดูสินค้าทั้งหมด ({{ $stats['total_products'] }})
                </a>

                <a href="{{ route('admin.marketplace.orders.index', ['account' => $account->id]) }}"
                   class="block px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                    <i class="fas fa-shopping-cart mr-2"></i>ดูออเดอร์ทั้งหมด ({{ $stats['total_orders'] }})
                </a>
            </div>

            {{-- Danger Zone --}}
            <div class="glass-card p-6 rounded-xl border border-red-200 dark:border-red-800">
                <h3 class="text-lg font-semibold text-red-600 dark:text-red-400 mb-4">Danger Zone</h3>

                <form action="{{ route('admin.marketplace.accounts.destroy', $account) }}" method="POST"
                      onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบบัญชีนี้? ข้อมูลสินค้าและออเดอร์ทั้งหมดจะถูกลบด้วย');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="w-full px-4 py-3 bg-red-500 text-white rounded-xl hover:bg-red-600 transition">
                        <i class="fas fa-trash mr-2"></i>ลบบัญชี
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const accountId = {{ $account->id }};

    function testConnection() {
        const btn = event.target.closest('button');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>กำลังทดสอบ...';

        fetch(`{{ route('admin.marketplace.accounts.test-connection', $account) }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        })
        .catch(error => alert('เกิดข้อผิดพลาด: ' + error.message))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plug mr-2"></i>ทดสอบ API';
        });
    }

    function syncProducts() {
        const btn = event.target.closest('button');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลัง Sync...';

        fetch(`{{ route('admin.marketplace.accounts.sync-products', $account) }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        })
        .catch(error => alert('เกิดข้อผิดพลาด: ' + error.message))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-box"></i> <span>Sync สินค้า</span>';
        });
    }

    function syncOrders() {
        const btn = event.target.closest('button');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลัง Sync...';

        fetch(`{{ route('admin.marketplace.accounts.sync-orders', $account) }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        })
        .catch(error => alert('เกิดข้อผิดพลาด: ' + error.message))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-shopping-cart"></i> <span>Sync ออเดอร์</span>';
        });
    }

    function syncAll() {
        const btn = event.target.closest('button');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลัง Sync...';

        fetch(`{{ route('admin.marketplace.accounts.sync-all', $account) }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        })
        .catch(error => alert('เกิดข้อผิดพลาด: ' + error.message))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync"></i> <span>Sync ทั้งหมด</span>';
        });
    }
</script>
@endpush
@endsection
