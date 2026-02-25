@extends('layouts.admin')

@section('title', 'ภาพรวมคอมมิชชั่นดูดวง')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                💰 ภาพรวมคอมมิชชั่นดูดวง
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                สถิติและประวัติคอมมิชชั่นจากระบบแนะนำดูดวง
            </p>
        </div>
        <div class="mt-4 md:mt-0 flex gap-3">
            <a href="{{ route('admin.fortune.commissions.manage') }}"
               class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition flex items-center">
                <span class="mr-2">⚙️</span>
                จัดการคอมมิชชั่น
            </a>
            <a href="{{ route('admin.fortune.commissions.export', request()->query()) }}"
               class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition flex items-center">
                <span class="mr-2">📥</span>
                Export CSV
            </a>
        </div>
    </div>

    {{-- Stats Cards (Primary) --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        {{-- รวมทั้งหมด --}}
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-purple-100 text-sm">รวมทั้งหมด</span>
                <span class="text-2xl">💎</span>
            </div>
            <div class="text-2xl font-bold mb-1">฿{{ number_format($stats['total_amount'], 2) }}</div>
            <div class="text-purple-100 text-sm">{{ number_format($stats['total_count']) }} รายการ</div>
        </div>

        {{-- L1 สายตรง --}}
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-blue-100 text-sm">L1 สายตรง</span>
                <span class="text-2xl">👤</span>
            </div>
            <div class="text-2xl font-bold mb-1">฿{{ number_format($stats['l1_amount'], 2) }}</div>
            <div class="text-blue-100 text-sm">{{ number_format($stats['l1_count']) }} รายการ</div>
        </div>

        {{-- L2 ชั้นหลาน --}}
        <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-indigo-100 text-sm">L2 ชั้นหลาน</span>
                <span class="text-2xl">👥</span>
            </div>
            <div class="text-2xl font-bold mb-1">฿{{ number_format($stats['l2_amount'], 2) }}</div>
            <div class="text-indigo-100 text-sm">{{ number_format($stats['l2_count']) }} รายการ</div>
        </div>

        {{-- รอดำเนินการ --}}
        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-yellow-100 text-sm">รอดำเนินการ</span>
                <span class="text-2xl">⏳</span>
            </div>
            <div class="text-2xl font-bold mb-1">฿{{ number_format($stats['pending_amount'], 2) }}</div>
            <div class="text-yellow-100 text-sm">{{ number_format($stats['pending_count']) }} รายการ</div>
        </div>

        {{-- อนุมัติแล้ว --}}
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-green-100 text-sm">อนุมัติแล้ว</span>
                <span class="text-2xl">✅</span>
            </div>
            <div class="text-2xl font-bold mb-1">฿{{ number_format($stats['approved_amount'], 2) }}</div>
            <div class="text-green-100 text-sm">{{ number_format($stats['approved_count']) }} รายการ</div>
        </div>

        {{-- จ่ายแล้ว --}}
        <div class="bg-gradient-to-br from-teal-500 to-teal-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-teal-100 text-sm">จ่ายแล้ว</span>
                <span class="text-2xl">💵</span>
            </div>
            <div class="text-2xl font-bold mb-1">฿{{ number_format($stats['paid_amount'], 2) }}</div>
            <div class="text-teal-100 text-sm">{{ number_format($stats['paid_count']) }} รายการ</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
        <form method="GET" action="{{ route('admin.fortune.commissions.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สถานะ</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="all" {{ ($filters['status'] ?? 'all') === 'all' ? 'selected' : '' }}>ทั้งหมด</option>
                    <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                    <option value="approved" {{ ($filters['status'] ?? '') === 'approved' ? 'selected' : '' }}>อนุมัติแล้ว</option>
                    <option value="paid" {{ ($filters['status'] ?? '') === 'paid' ? 'selected' : '' }}>จ่ายแล้ว</option>
                    <option value="rejected" {{ ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' }}>ปฏิเสธ</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชั้น</label>
                <select name="level" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="all" {{ ($filters['level'] ?? 'all') === 'all' ? 'selected' : '' }}>ทั้งหมด</option>
                    <option value="1" {{ ($filters['level'] ?? '') == '1' ? 'selected' : '' }}>L1 สายตรง</option>
                    <option value="2" {{ ($filters['level'] ?? '') == '2' ? 'selected' : '' }}>L2 ชั้นหลาน</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">วันที่เริ่มต้น</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">วันที่สิ้นสุด</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div class="flex items-end gap-3">
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    🔍 กรอง
                </button>
                <a href="{{ route('admin.fortune.commissions.index') }}"
                   class="px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition">
                    ล้าง
                </a>
            </div>
        </form>
        {{-- ค้นหา (แถวที่ 2) --}}
        <form method="GET" action="{{ route('admin.fortune.commissions.index') }}" class="mt-4">
            @foreach(request()->except('search', 'page') as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <div class="flex gap-3">
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                       placeholder="ค้นหาชื่อ/email ผู้รับหรือผู้จ่าย..."
                       class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                <button type="submit" class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition">
                    🔎 ค้นหา
                </button>
            </div>
        </form>
    </div>

    {{-- Commission Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ผู้รับ
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            จากผู้ใช้
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            บิล #
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ชั้น
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ประเภท
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            จำนวน (฿)
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            สถานะ
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            วันที่
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ดู
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($commissions as $c)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            {{-- ผู้รับ --}}
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-900 flex items-center justify-center mr-3">
                                        <span class="text-purple-600 dark:text-purple-300 text-xs font-bold">
                                            {{ mb_substr($c->user?->name ?? '?', 0, 1) }}
                                        </span>
                                    </div>
                                    <div>
                                        <div class="text-gray-900 dark:text-gray-100 font-medium">{{ Str::limit($c->user?->name ?? '-', 20) }}</div>
                                        <div class="text-gray-500 dark:text-gray-400 text-xs">{{ $c->user?->email ?? '' }}</div>
                                    </div>
                                </div>
                            </td>

                            {{-- จากผู้ใช้ --}}
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                {{ Str::limit($c->fromUser?->name ?? '-', 20) }}
                            </td>

                            {{-- บิล # --}}
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                #{{ $c->fortune_reading_id ?? '-' }}
                            </td>

                            {{-- ชั้น --}}
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($c->level === 1)
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                        L1 สายตรง
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                        L2 หลาน
                                    </span>
                                @endif
                            </td>

                            {{-- ประเภท --}}
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                {{ $c->commission_type === 'percent' ? $c->commission_rate.'%' : '฿'.$c->commission_rate }}
                            </td>

                            {{-- จำนวน --}}
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-gray-900 dark:text-gray-100">
                                ฿{{ number_format($c->amount, 2) }}
                            </td>

                            {{-- สถานะ --}}
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @switch($c->status)
                                    @case('pending')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                            ⏳ รอดำเนินการ
                                        </span>
                                        @break
                                    @case('approved')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            ✅ อนุมัติแล้ว
                                        </span>
                                        @break
                                    @case('paid')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            💵 จ่ายแล้ว
                                        </span>
                                        @break
                                    @case('rejected')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                            ❌ ปฏิเสธ
                                        </span>
                                        @break
                                @endswitch
                            </td>

                            {{-- วันที่ --}}
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                <div>{{ $c->created_at?->format('d/m/Y') }}</div>
                                <div class="text-xs text-gray-500">{{ $c->created_at?->format('H:i') }}</div>
                            </td>

                            {{-- ดู --}}
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button onclick="showDetail({{ $c->id }})"
                                        class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                    ดูรายละเอียด
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <div class="text-4xl mb-3">📭</div>
                                <p>ไม่พบข้อมูลคอมมิชชั่น</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($commissions->hasPages())
        <div class="mt-6">
            {{ $commissions->appends(request()->query())->links() }}
        </div>
    @endif
</div>

{{-- Detail Modal --}}
<div id="detailModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center"
     x-data="{ show: false }"
     x-show="show"
     x-transition
     @keydown.escape.window="show = false; document.getElementById('detailModal').classList.add('hidden')">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-6 w-full max-w-lg mx-4" @click.outside="show = false; document.getElementById('detailModal').classList.add('hidden')">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">รายละเอียดคอมมิชชั่น</h3>
            <button onclick="closeDetail()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div id="detailContent" class="space-y-3 text-sm">
            <div class="text-center py-8 text-gray-500">
                <div class="animate-spin h-8 w-8 border-4 border-purple-600 border-t-transparent rounded-full mx-auto mb-3"></div>
                กำลังโหลด...
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * แสดงรายละเอียดคอมมิชชั่น (modal)
 */
function showDetail(id) {
    const modal = document.getElementById('detailModal');
    const content = document.getElementById('detailContent');

    modal.classList.remove('hidden');
    modal.classList.add('flex');

    content.innerHTML = '<div class="text-center py-8 text-gray-500"><div class="animate-spin h-8 w-8 border-4 border-purple-600 border-t-transparent rounded-full mx-auto mb-3"></div>กำลังโหลด...</div>';

    fetch(`{{ url('admin/fortune/commissions') }}/${id}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        }
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) {
            content.innerHTML = '<div class="text-center text-red-500 py-4">โหลดข้อมูลล้มเหลว</div>';
            return;
        }
        const d = res.data;
        content.innerHTML = `
            <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="text-gray-500 dark:text-gray-400 text-xs mb-1">ID / สถานะ</div>
                    <div class="font-bold text-gray-900 dark:text-white">#${d.id} — ${d.status_name}</div>
                </div>
                <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="text-gray-500 dark:text-gray-400 text-xs mb-1">ผู้รับคอมมิชชั่น</div>
                    <div class="font-medium text-gray-900 dark:text-white">${d.user?.name ?? '-'}</div>
                    <div class="text-xs text-gray-500">${d.user?.email ?? ''}</div>
                </div>
                <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="text-gray-500 dark:text-gray-400 text-xs mb-1">จากผู้ใช้ (คนดูดวง)</div>
                    <div class="font-medium text-gray-900 dark:text-white">${d.from_user?.name ?? '-'}</div>
                    <div class="text-xs text-gray-500">${d.from_user?.email ?? ''}</div>
                </div>
                <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="text-gray-500 dark:text-gray-400 text-xs mb-1">ชั้น</div>
                    <div class="font-bold text-gray-900 dark:text-white">L${d.level} (${d.level_name})</div>
                </div>
                <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="text-gray-500 dark:text-gray-400 text-xs mb-1">ประเภท / อัตรา</div>
                    <div class="font-medium text-gray-900 dark:text-white">${d.commission_type === 'percent' ? d.commission_rate + '%' : '฿' + d.commission_rate}</div>
                </div>
                <div class="p-3 bg-purple-50 dark:bg-purple-900/30 rounded-lg">
                    <div class="text-purple-500 dark:text-purple-400 text-xs mb-1">จำนวนเงิน</div>
                    <div class="text-xl font-bold text-purple-700 dark:text-purple-300">฿${Number(d.amount).toLocaleString('th-TH', {minimumFractionDigits: 2})}</div>
                </div>
                <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="text-gray-500 dark:text-gray-400 text-xs mb-1">ราคาดูดวง</div>
                    <div class="font-medium text-gray-900 dark:text-white">฿${Number(d.reading_price).toLocaleString('th-TH', {minimumFractionDigits: 2})}</div>
                </div>
                ${d.reading ? `
                <div class="col-span-2 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="text-gray-500 dark:text-gray-400 text-xs mb-1">บิลดูดวง</div>
                    <div class="font-medium text-gray-900 dark:text-white">#${d.reading.id} (${d.reading.reading_type}) — ชำระ ฿${Number(d.reading.amount_paid ?? 0).toLocaleString('th-TH', {minimumFractionDigits: 2})}</div>
                    <div class="text-xs text-gray-500">${d.reading.created_at ?? ''}</div>
                </div>
                ` : ''}
                ${d.notes ? `
                <div class="col-span-2 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                    <div class="text-yellow-600 dark:text-yellow-400 text-xs mb-1">หมายเหตุ</div>
                    <div class="text-gray-900 dark:text-white text-sm">${d.notes}</div>
                </div>
                ` : ''}
                <div class="col-span-2 grid grid-cols-3 gap-2 text-xs text-gray-500 dark:text-gray-400">
                    <div>สร้าง: ${d.created_at ?? '-'}</div>
                    <div>อนุมัติ: ${d.approved_at ?? '-'}</div>
                    <div>จ่าย: ${d.paid_at ?? '-'}</div>
                </div>
            </div>
        `;
    })
    .catch(() => {
        content.innerHTML = '<div class="text-center text-red-500 py-4">เกิดข้อผิดพลาด กรุณาลองใหม่</div>';
    });
}

/**
 * ปิด modal รายละเอียด
 */
function closeDetail() {
    const modal = document.getElementById('detailModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>
@endpush
@endsection
