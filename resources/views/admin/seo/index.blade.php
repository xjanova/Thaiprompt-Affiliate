@extends('layouts.admin-v3')
@section('title', 'จัดการ SEO')
@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- ส่วนหัว --}}
    <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 rounded-lg shadow-lg p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-white">จัดการ SEO</h1>
                <p class="text-indigo-100 mt-1">ตั้งค่า Meta Tags, Open Graph และ Robots ของแต่ละหน้า</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.seo.create') }}"
                   class="bg-white text-indigo-600 hover:bg-indigo-50 px-4 py-2 rounded-lg font-medium transition">
                    + เพิ่ม SEO Meta
                </a>
                <a href="{{ route('admin.seo.analysis') }}"
                   class="bg-indigo-500/40 hover:bg-indigo-500/60 text-white px-4 py-2 rounded-lg font-medium transition">
                    วิเคราะห์
                </a>
                <a href="{{ route('admin.seo.settings') }}"
                   class="bg-indigo-500/40 hover:bg-indigo-500/60 text-white px-4 py-2 rounded-lg font-medium transition">
                    ตั้งค่า
                </a>
            </div>
        </div>
    </div>

    {{-- ข้อความแจ้งเตือน --}}
    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/30 border-l-4 border-green-400 p-4 mb-6">
            <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
        </div>
    @endif

    {{-- ตารางรายการ SEO Meta --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ประเภทหน้า</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ภาษา</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Meta Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Robots</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($seoMetas as $seo)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $seo->page_type }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 uppercase">
                                {{ $seo->language }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ Str::limit($seo->meta_title, 60) ?: '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                    {{ $seo->index ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300' }}">
                                    {{ $seo->robots }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right text-sm space-x-3">
                                <a href="{{ route('admin.seo.edit', $seo) }}"
                                   class="text-blue-600 dark:text-blue-400 hover:underline">แก้ไข</a>
                                <form action="{{ route('admin.seo.destroy', $seo) }}" method="POST" class="inline"
                                      onsubmit="return confirm('ยืนยันการลบ SEO Meta ของหน้า {{ $seo->page_type }} ({{ $seo->language }})?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 dark:text-red-400 hover:underline">ลบ</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                ยังไม่มีข้อมูล SEO Meta — คลิก "เพิ่ม SEO Meta" เพื่อเริ่มต้น
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- แบ่งหน้า --}}
    <div class="mt-6">
        {{ $seoMetas->links() }}
    </div>
</div>
@endsection
