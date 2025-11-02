@extends('layouts.admin')

@section('title', 'การเลื่อนระดับ Rank')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header Section -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                    ⬆️ การเลื่อนระดับ Rank
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    ตรวจสอบและอนุมัติการเลื่อนระดับของผู้ใช้
                </p>
            </div>
            <a href="{{ route('admin.ranks.index') }}"
               class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-all duration-200">
                ← กลับ
            </a>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ทั้งหมด</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-gray-200 mt-1">{{ $promotions->total() }}</p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-r from-blue-100 to-indigo-100 dark:from-blue-900 dark:to-indigo-900 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">📊</span>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">รออนุมัติ</p>
                    <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">
                        {{ $promotions->where('status', 'pending')->count() }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-r from-yellow-100 to-orange-100 dark:from-yellow-900 dark:to-orange-900 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">⏳</span>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">อนุมัติแล้ว</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">
                        {{ $promotions->where('status', 'approved')->count() }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900 dark:to-emerald-900 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">✅</span>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ปฏิเสธ</p>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">
                        {{ $promotions->where('status', 'rejected')->count() }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-r from-red-100 to-pink-100 dark:from-red-900 dark:to-pink-900 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">❌</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Promotions List -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            วันที่
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            ผู้ใช้
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            การเปลี่ยนแปลง
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            สถานะ
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            ผู้อนุมัติ
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($promotions as $promotion)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-gray-100">
                                {{ $promotion->created_at->format('d/m/Y') }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $promotion->created_at->format('H:i') }} น.
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gradient-to-r from-pink-500 to-rose-500 flex items-center justify-center text-white font-bold">
                                    {{ substr($promotion->user->name ?? 'U', 0, 1) }}
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $promotion->user->name ?? 'Unknown' }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $promotion->user->email ?? 'N/A' }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                @if($promotion->fromRank)
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-xs font-bold"
                                         style="background: {{ $promotion->fromRank->color }};">
                                        {{ $promotion->fromRank->level }}
                                    </div>
                                    <span class="ml-1 text-xs text-gray-600 dark:text-gray-400">{{ $promotion->fromRank->name }}</span>
                                </div>
                                @else
                                <span class="text-xs text-gray-500 dark:text-gray-400">ไม่มี Rank</span>
                                @endif

                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                </svg>

                                @if($promotion->toRank)
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-xs font-bold"
                                         style="background: {{ $promotion->toRank->color }};">
                                        {{ $promotion->toRank->level }}
                                    </div>
                                    <span class="ml-1 text-xs text-gray-600 dark:text-gray-400">{{ $promotion->toRank->name }}</span>
                                </div>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($promotion->status === 'pending')
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                รออนุมัติ
                            </span>
                            @elseif($promotion->status === 'approved')
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                อนุมัติแล้ว
                            </span>
                            @else
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                ปฏิเสธ
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($promotion->approved_by)
                            <div class="text-sm text-gray-900 dark:text-gray-100">
                                {{ $promotion->approver->name ?? 'N/A' }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $promotion->approved_at?->format('d/m/Y H:i') }}
                            </div>
                            @else
                            <span class="text-sm text-gray-400 dark:text-gray-500">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            @if($promotion->status === 'pending')
                            <div class="flex items-center justify-end gap-2">
                                <form action="{{ route('admin.ranks.promotions.approve', $promotion) }}" method="POST" class="inline-block"
                                      onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการอนุมัติการเลื่อนระดับนี้?');">
                                    @csrf
                                    <button type="submit"
                                            class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-xs rounded-lg transition-all duration-200">
                                        อนุมัติ
                                    </button>
                                </form>

                                <button onclick="showRejectModal({{ $promotion->id }})"
                                        class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-xs rounded-lg transition-all duration-200">
                                    ปฏิเสธ
                                </button>
                            </div>
                            @else
                            <span class="text-gray-400 dark:text-gray-500">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <span class="text-6xl mb-4">⬆️</span>
                                <p class="text-gray-500 dark:text-gray-400 text-lg">ยังไม่มีการเลื่อนระดับ</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($promotions->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $promotions->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 mb-4">
                ปฏิเสธการเลื่อนระดับ
            </h3>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        เหตุผลในการปฏิเสธ
                    </label>
                    <textarea name="reason" rows="4"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:text-gray-200"
                              placeholder="กรุณาระบุเหตุผล..."></textarea>
                </div>
                <div class="flex items-center justify-end gap-3">
                    <button type="button" onclick="closeRejectModal()"
                            class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-all duration-200">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-all duration-200">
                        ยืนยันการปฏิเสธ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showRejectModal(promotionId) {
    const modal = document.getElementById('rejectModal');
    const form = document.getElementById('rejectForm');
    form.action = `/admin/ranks/promotions/${promotionId}/reject`;
    modal.classList.remove('hidden');
}

function closeRejectModal() {
    const modal = document.getElementById('rejectModal');
    modal.classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('rejectModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeRejectModal();
    }
});
</script>
@endsection
