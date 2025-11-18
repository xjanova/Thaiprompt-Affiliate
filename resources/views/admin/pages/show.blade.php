@extends('layouts.admin-v3')

@section('title', $page->title)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.pages.index') }}" class="text-gray-600 hover:text-gray-900">
                ← กลับ
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $page->title }}</h1>
                <p class="text-sm text-gray-600 mt-1">
                    <code class="px-2 py-1 bg-gray-100 rounded">{{ $page->slug }}</code>
                </p>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.pages.edit', $page) }}"
               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                แก้ไข
            </a>
        </div>
    </div>

    <!-- Info -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6 pb-6 border-b">
            <div>
                <p class="text-sm text-gray-600 mb-1">ประเภท</p>
                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded text-sm font-medium">
                    {{ \App\Models\Page::getTypes()[$page->type] ?? $page->type }}
                </span>
            </div>

            <div>
                <p class="text-sm text-gray-600 mb-1">สถานะ</p>
                @if($page->is_published)
                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">เผยแพร่</span>
                @else
                    <span class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm font-medium">แบบร่าง</span>
                @endif
            </div>

            <div>
                <p class="text-sm text-gray-600 mb-1">ลำดับ</p>
                <p class="text-lg font-semibold">{{ $page->sort_order }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-600 mb-1">อัปเดตล่าสุด</p>
                <p class="text-sm">{{ $page->updated_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>

        <!-- Content Preview -->
        <div>
            <h2 class="text-lg font-semibold text-gray-900 mb-4">เนื้อหา</h2>
            <div class="prose max-w-none p-6 bg-gray-50 rounded-lg border border-gray-200">
                {!! $page->content !!}
            </div>
        </div>
    </div>
</div>
@endsection
