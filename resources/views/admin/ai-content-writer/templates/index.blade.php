@extends('layouts.admin')

@section('title', $pageTitle ?? 'จัดการเทมเพลต')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">จัดการเทมเพลต</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">สร้างและจัดการเทมเพลตสำหรับสร้าง Content</p>
        </div>
        <a href="{{ route('admin.platform-revenue.ai-content-writer.templates.create') }}"
           class="inline-flex items-center px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-xl transition">
            <i class="fas fa-plus mr-2"></i>
            สร้างเทมเพลตใหม่
        </a>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
        <form method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="ค้นหาเทมเพลต..."
                       class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <select name="type" class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                <option value="">ทุกประเภท</option>
                @foreach($contentTypes as $key => $label)
                    <option value="{{ $key }}" {{ request('type') == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="category" class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                <option value="">ทุกหมวดหมู่</option>
                @foreach($categories as $key => $label)
                    <option value="{{ $key }}" {{ request('category') == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                <i class="fas fa-search mr-2"></i> ค้นหา
            </button>
        </form>
    </div>

    {{-- Templates Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($templates as $template)
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:border-purple-500 dark:hover:border-purple-500 transition group">
            <div class="p-5">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-purple-600 dark:text-purple-400">
                            <i class="fas {{ $template->icon ?? 'fa-file-alt' }}"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-purple-500 transition">
                                {{ $template->name }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $template->content_type_label }}</p>
                        </div>
                    </div>
                    @if($template->is_featured)
                        <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 rounded-full">
                            <i class="fas fa-star mr-1"></i> แนะนำ
                        </span>
                    @endif
                </div>

                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                    {{ $template->description ?? 'ไม่มีคำอธิบาย' }}
                </p>

                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>
                        <i class="fas fa-chart-line mr-1"></i>
                        {{ number_format($template->usage_count) }} ครั้ง
                    </span>
                    <span>{{ $template->provider_label }}</span>
                </div>
            </div>

            <div class="px-5 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex justify-between">
                <a href="{{ route('admin.platform-revenue.ai-content-writer.templates.edit', $template->id) }}"
                   class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                    <i class="fas fa-edit mr-1"></i> แก้ไข
                </a>
                <button onclick="deleteTemplate({{ $template->id }})"
                        class="text-sm text-red-600 dark:text-red-400 hover:underline">
                    <i class="fas fa-trash mr-1"></i> ลบ
                </button>
            </div>
        </div>
        @empty
        <div class="col-span-full text-center py-12 text-gray-500 dark:text-gray-400">
            <i class="fas fa-file-alt text-4xl mb-4"></i>
            <p>ยังไม่มีเทมเพลต</p>
            <a href="{{ route('admin.platform-revenue.ai-content-writer.templates.create') }}" class="text-purple-600 hover:underline">สร้างเทมเพลตแรก</a>
        </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $templates->links() }}
    </div>
</div>

@push('scripts')
<script>
async function deleteTemplate(id) {
    if (!confirm('ต้องการลบเทมเพลตนี้?')) return;

    try {
        const response = await fetch(`{{ url('admin/ai-content-writer/templates') }}/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            }
        });

        const result = await response.json();

        if (result.success) {
            location.reload();
        } else {
            alert(result.message || 'เกิดข้อผิดพลาด');
        }
    } catch (error) {
        alert('เกิดข้อผิดพลาดในการลบ');
    }
}
</script>
@endpush
@endsection
