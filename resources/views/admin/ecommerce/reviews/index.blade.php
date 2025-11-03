@extends('layouts.admin')

@section('title', 'จัดการรีวิวสินค้า')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">⭐ จัดการรีวิวสินค้า</h1>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="ค้นหารีวิว..." class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white">
            <select name="rating" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white">
                <option value="">ทุกคะแนน</option>
                <option value="5" {{ request('rating') == 5 ? 'selected' : '' }}>⭐⭐⭐⭐⭐ (5)</option>
                <option value="4" {{ request('rating') == 4 ? 'selected' : '' }}>⭐⭐⭐⭐ (4)</option>
                <option value="3" {{ request('rating') == 3 ? 'selected' : '' }}>⭐⭐⭐ (3)</option>
                <option value="2" {{ request('rating') == 2 ? 'selected' : '' }}>⭐⭐ (2)</option>
                <option value="1" {{ request('rating') == 1 ? 'selected' : '' }}>⭐ (1)</option>
            </select>
            <button type="submit" class="bg-orange-600 text-white px-6 py-2 rounded-lg hover:bg-orange-700">
                ค้นหา
            </button>
        </form>
    </div>

    <!-- Reviews Table -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สินค้า</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ลูกค้า</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">คะแนน</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ความคิดเห็น</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">วันที่</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">การกระทำ</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($reviews as $review)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $review->product->name ?? 'ไม่ระบุ' }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">SKU: {{ $review->product->sku ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 dark:text-white">{{ $review->user->name ?? 'ไม่ระบุ' }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $review->user->email ?? '' }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="text-yellow-500">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= $review->rating)
                                            ⭐
                                        @else
                                            ☆
                                        @endif
                                    @endfor
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ Str::limit($review->comment ?? '', 50) }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ $review->created_at->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4 text-sm space-x-2">
                                <form action="{{ route('admin.ecommerce.reviews.status.update', $review) }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="is_approved" value="{{ $review->is_approved ? 0 : 1 }}">
                                    <button type="submit" class="text-orange-600 dark:text-orange-400 hover:text-orange-700 font-medium">
                                        {{ $review->is_approved ? 'ซ่อน' : 'อนุมัติ' }}
                                    </button>
                                </form>
                                <form action="{{ route('admin.ecommerce.reviews.delete', $review) }}" method="POST" class="inline" onsubmit="return confirm('คุณแน่ใจหรือไม่?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-700 font-medium">
                                        ลบ
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                ไม่พบรีวิว
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $reviews->links() }}
        </div>
    </div>
</div>
@endsection
