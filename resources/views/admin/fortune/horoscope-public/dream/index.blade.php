{{-- พจนานุกรมทำนายฝัน — รายการสัญลักษณ์ --}}
@extends('layouts.admin-v3')

@section('title', $pageTitle)

@section('content')
<div class="container mx-auto px-4 py-8">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">📖 พจนานุกรมทำนายฝัน</h1>
            <p class="text-gray-500 dark:text-gray-400 text-sm">จัดการสัญลักษณ์ฝัน + เลขเด็ด</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.fortune.horoscope-public.dream.categories') }}"
               class="px-4 py-2 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-lg text-sm font-medium hover:bg-purple-200 dark:hover:bg-purple-900/50 transition">
                📂 หมวดหมู่
            </a>
            <a href="{{ route('admin.fortune.horoscope-public.dream.readings') }}"
               class="px-4 py-2 bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300 rounded-lg text-sm font-medium hover:bg-cyan-200 dark:hover:bg-cyan-900/50 transition">
                📋 ผลทำนาย
            </a>
            <a href="{{ route('admin.fortune.horoscope-public.dream.create') }}"
               class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition shadow-lg">
                ➕ เพิ่มสัญลักษณ์
            </a>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 rounded-xl text-green-800 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-xl text-red-800 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    {{-- ==================== ตัวกรอง ==================== --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 mb-6">
        <form method="GET" class="flex flex-col md:flex-row gap-3">
            <div class="flex-1">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="🔍 ค้นหาสัญลักษณ์..."
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <select name="category_id"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">-- ทุกหมวด --</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->icon }} {{ $cat->name_th }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="omen"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">-- ลาง --</option>
                    <option value="good" {{ request('omen') === 'good' ? 'selected' : '' }}>✅ ลางดี</option>
                    <option value="bad" {{ request('omen') === 'bad' ? 'selected' : '' }}>⚠️ ระวัง</option>
                </select>
            </div>
            <div>
                <select name="status"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">-- สถานะ --</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>✅ เปิด</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>⏸️ ปิด</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm transition">
                    🔍 ค้นหา
                </button>
                <a href="{{ route('admin.fortune.horoscope-public.dream.index') }}"
                   class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm transition">
                    ล้าง
                </a>
            </div>
        </form>
    </div>

    {{-- ==================== ตารางสัญลักษณ์ ==================== --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
            <p class="text-sm text-gray-600 dark:text-gray-300">ทั้งหมด {{ $symbols->total() }} สัญลักษณ์</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สัญลักษณ์</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ความหมาย</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">หมวด</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ลาง</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">เลขเด็ด</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ระดับ</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ค้นหา</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($symbols as $symbol)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <td class="px-4 py-4">
                                <span class="text-sm font-bold text-gray-900 dark:text-white">
                                    ฝันเห็น{{ $symbol->keyword_th }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <span class="text-sm text-gray-600 dark:text-gray-400 line-clamp-1">
                                    {{ Str::limit($symbol->meaning_th, 60) }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-center">
                                @if($symbol->category)
                                    <span class="text-sm" title="{{ $symbol->category->name_th }}">
                                        {{ $symbol->category->icon }}
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-center">
                                @if($symbol->is_good_omen)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">✅ ดี</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">⚠️ ระวัง</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-center">
                                @if(!empty($symbol->lucky_numbers))
                                    <div class="flex flex-wrap justify-center gap-1">
                                        @foreach(array_slice($symbol->lucky_numbers, 0, 3) as $num)
                                            <span class="px-1.5 py-0.5 bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 rounded text-xs font-mono font-bold">{{ $num }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-center">
                                <div class="flex justify-center gap-0.5">
                                    @for($i = 1; $i <= 5; $i++)
                                        <div class="w-2 h-2 rounded-full {{ $i <= $symbol->intensity ? 'bg-indigo-500' : 'bg-gray-200 dark:bg-gray-600' }}"></div>
                                    @endfor
                                </div>
                            </td>
                            <td class="px-4 py-4 text-center text-sm text-gray-600 dark:text-gray-400">
                                {{ number_format($symbol->search_count) }}
                            </td>
                            <td class="px-4 py-4 text-right">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('admin.fortune.horoscope-public.dream.edit', $symbol) }}"
                                       class="px-2 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-xs transition" title="แก้ไข">
                                        ✏️
                                    </a>
                                    <form action="{{ route('admin.fortune.horoscope-public.dream.destroy', $symbol) }}" method="POST" class="inline"
                                          onsubmit="return confirm('ลบสัญลักษณ์ «{{ $symbol->keyword_th }}»?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="px-2 py-1.5 bg-red-100 hover:bg-red-200 dark:bg-red-900/50 dark:hover:bg-red-900 text-red-700 dark:text-red-300 rounded-lg text-xs transition" title="ลบ">
                                            🗑️
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                ไม่พบสัญลักษณ์ฝัน
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($symbols->hasPages())
        <div class="mt-6">
            {{ $symbols->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection
