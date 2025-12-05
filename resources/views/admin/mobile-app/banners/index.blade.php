@extends('layouts.admin')

@section('title', 'Banner โฆษณา')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <span class="text-4xl">🖼️</span>
                Banner โฆษณา
            </h1>
            <p class="text-gray-500 dark:text-gray-400 mt-2">
                จัดการ Banner โฆษณาในแอพมือถือ (หน้าช้อปปิ้ง)
            </p>
        </div>
        <div class="mt-4 md:mt-0 flex gap-3">
            <a href="{{ route('admin.mobile-app.banners.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg transition">
                <span>➕</span>
                <span>สร้าง Banner ใหม่</span>
            </a>
            <a href="{{ route('admin.mobile-app.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition">
                <span>←</span>
                <span>กลับ</span>
            </a>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="text-sm text-gray-500 dark:text-gray-400">Active</div>
            <div class="text-2xl font-bold text-green-500">{{ number_format($stats['active']) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="text-sm text-gray-500 dark:text-gray-400">Inactive</div>
            <div class="text-2xl font-bold text-gray-500">{{ number_format($stats['inactive']) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Views</div>
            <div class="text-2xl font-bold text-blue-500">{{ number_format($stats['total_views']) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Clicks</div>
            <div class="text-2xl font-bold text-purple-500">{{ number_format($stats['total_clicks']) }}</div>
        </div>
    </div>

    {{-- Banners Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" x-data="{ dragging: false }">
        @forelse($banners as $banner)
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden
                        {{ !$banner->is_active ? 'opacity-60' : '' }}">
                {{-- Image --}}
                <div class="aspect-video bg-gray-100 dark:bg-gray-700 relative">
                    @if($banner->image)
                        <img src="{{ $banner->image }}" alt="{{ $banner->title }}"
                             class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-4xl text-gray-400">
                            🖼️
                        </div>
                    @endif

                    {{-- Status Badge --}}
                    <div class="absolute top-2 right-2">
                        <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium
                            {{ $banner->is_active ? 'bg-green-500 text-white' : 'bg-gray-500 text-white' }}">
                            {{ $banner->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    {{-- Position Badge --}}
                    <div class="absolute top-2 left-2">
                        <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium bg-blue-500 text-white">
                            {{ ucfirst($banner->position) }}
                        </span>
                    </div>
                </div>

                {{-- Info --}}
                <div class="p-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2">
                        {{ $banner->title }}
                    </h3>

                    <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400 mb-3">
                        <span>👁️ {{ number_format($banner->view_count) }}</span>
                        <span>👆 {{ number_format($banner->click_count) }}</span>
                        <span>📊 {{ $banner->ctr }}% CTR</span>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-2">
                        <button type="button"
                                class="flex-1 px-3 py-2 text-sm rounded-lg transition
                                    {{ $banner->is_active
                                        ? 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-400'
                                        : 'bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400' }}"
                                onclick="toggleBanner({{ $banner->id }})">
                            {{ $banner->is_active ? '⏸️ Pause' : '▶️ Activate' }}
                        </button>
                        <a href="{{ route('admin.mobile-app.banners.edit', $banner) }}"
                           class="px-3 py-2 text-sm bg-blue-100 text-blue-700 hover:bg-blue-200
                                  dark:bg-blue-900/30 dark:text-blue-400 rounded-lg transition">
                            ✏️ แก้ไข
                        </a>
                        <button type="button"
                                class="px-3 py-2 text-sm bg-red-100 text-red-700 hover:bg-red-200
                                       dark:bg-red-900/30 dark:text-red-400 rounded-lg transition"
                                onclick="deleteBanner({{ $banner->id }})">
                            🗑️
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-3 text-center py-12">
                <div class="text-6xl mb-4">🖼️</div>
                <p class="text-gray-500 dark:text-gray-400 mb-4">ยังไม่มี Banner</p>
                <a href="{{ route('admin.mobile-app.banners.create') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg transition">
                    <span>➕</span>
                    <span>สร้าง Banner แรก</span>
                </a>
            </div>
        @endforelse
    </div>

    @if($banners->hasPages())
        <div class="mt-8">
            {{ $banners->links() }}
        </div>
    @endif
</div>

@push('scripts')
<script>
    function toggleBanner(id) {
        fetch(`{{ url('admin/mobile-app/banners') }}/${id}/toggle`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }

    function deleteBanner(id) {
        if (confirm('ต้องการลบ Banner นี้?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `{{ url('admin/mobile-app/banners') }}/${id}`;
            form.innerHTML = `
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="_method" value="DELETE">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>
@endpush
@endsection
