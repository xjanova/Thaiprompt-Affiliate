@extends('layouts.admin-v3')

@section('title', 'คลังความรู้แม่หมอ (RAG)')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-7xl">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">🧠 คลังความรู้แม่หมอ (RAG)</h1>
            <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">
                องค์ความรู้ที่แม่หมอ (AI) ดึงไปทำนายตามหน้าไพ่ — สุขภาพ / ฮวงจุ้ย / เจ้าที่ / องค์เทพ / ไสยศาสตร์ ·
                แก้ไขแล้วใช้ได้ทันที
            </p>
        </div>
        <a href="{{ route('admin.fortune.knowledge.create') }}"
           class="inline-flex items-center justify-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-lg transition shadow">
            + เพิ่มองค์ความรู้
        </a>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 rounded-xl text-green-800 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
            <div class="text-xs text-gray-500 dark:text-gray-400">ทั้งหมด</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
            <div class="text-xs text-gray-500 dark:text-gray-400">เปิดใช้งาน</div>
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($stats['active']) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 col-span-2">
            <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">แยกตามหมวด</div>
            <div class="flex flex-wrap gap-1.5">
                @foreach($categoryLabels as $key => $label)
                    <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200">
                        {{ $label }}: <b>{{ $stats['category_breakdown'][$key] ?? 0 }}</b>
                    </span>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Filter --}}
    <form method="GET" class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 mb-4 grid grid-cols-1 md:grid-cols-4 gap-3">
        <input type="text" name="search" value="{{ $search }}" placeholder="ค้นหา หัวข้อ/เนื้อหา/ไพ่..."
               class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
        <select name="category" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100">
            <option value="">— ทุกหมวด —</option>
            @foreach($categoryLabels as $key => $label)
                <option value="{{ $key }}" @selected($category === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="active" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100">
            <option value="">— ทุกสถานะ —</option>
            <option value="1" @selected($active === '1')>เปิดใช้งาน</option>
            <option value="0" @selected($active === '0')>ปิด</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-gray-700 hover:bg-gray-800 dark:bg-gray-600 dark:hover:bg-gray-500 text-white rounded-lg transition">
            🔍 ค้นหา
        </button>
    </form>

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">หมวด</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">หัวข้อ / เนื้อหา</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ไพ่/หัวเรื่อง</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($rows as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition">
                            <td class="px-4 py-3 align-top">
                                <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-800 dark:text-indigo-200 whitespace-nowrap">
                                    {{ $categoryLabels[$row->category] ?? $row->category }}
                                </span>
                                @if($row->severity)
                                    <div class="mt-1 text-xs text-red-600 dark:text-red-400">⚠️ {{ $row->severity }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top max-w-md">
                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $row->title }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2">{{ \Illuminate\Support\Str::limit($row->content, 160) }}</div>
                            </td>
                            <td class="px-4 py-3 align-top text-sm text-gray-700 dark:text-gray-300">
                                @if($row->card_name)<div>🃏 {{ $row->card_name }}</div>@endif
                                @if($row->subject)<div class="text-xs text-gray-500 dark:text-gray-400">{{ $row->subject }}</div>@endif
                            </td>
                            <td class="px-4 py-3 align-top text-center">
                                <form method="POST" action="{{ route('admin.fortune.knowledge.toggle', $row) }}">
                                    @csrf
                                    <button type="submit"
                                            class="text-xs px-2.5 py-1 rounded-full {{ $row->is_active ? 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-200' : 'bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300' }}">
                                        {{ $row->is_active ? '● เปิด' : '○ ปิด' }}
                                    </button>
                                </form>
                            </td>
                            <td class="px-4 py-3 align-top text-right whitespace-nowrap">
                                <a href="{{ route('admin.fortune.knowledge.edit', $row) }}" class="text-blue-600 dark:text-blue-400 hover:underline text-sm">แก้ไข</a>
                                <form method="POST" action="{{ route('admin.fortune.knowledge.destroy', $row) }}" class="inline"
                                      onsubmit="return confirm('ยืนยันลบองค์ความรู้นี้?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 dark:text-red-400 hover:underline text-sm ml-2">ลบ</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                ยังไม่มีองค์ความรู้ — กด "เพิ่มองค์ความรู้" เพื่อเริ่ม หรือรัน seeder
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $rows->links() }}</div>
</div>
@endsection
