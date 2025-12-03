{{--
    Admin: Storefront Banners Management

    หน้าจัดการ Banners สำหรับ Storefront
    รองรับ CRUD, Drag & Drop Reorder, และ Toggle Status
--}}

@extends('layouts.admin')

@section('title', 'จัดการ Banner หน้าร้าน')

@section('content')
<div class="container-fluid py-6" x-data="bannersManager()">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-images text-orange-500 mr-3"></i>
                จัดการ Banner หน้าร้าน
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                จัดการ Banners ที่แสดงบนหน้า Storefront หลัก
            </p>
        </div>

        <a href="{{ route('admin.storefront.banners.create') }}"
           class="inline-flex items-center gap-2 px-6 py-3
                 bg-gradient-to-r from-orange-500 to-red-500
                 hover:from-orange-600 hover:to-red-600
                 text-white font-bold rounded-xl
                 shadow-lg hover:shadow-xl
                 transition-all">
            <i class="fas fa-plus"></i>
            <span>เพิ่ม Banner ใหม่</span>
        </a>
    </div>

    {{-- Banners List --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden
               border border-gray-100 dark:border-gray-700">

        {{-- Table Header --}}
        <div class="bg-gradient-to-r from-orange-500 to-red-500 px-6 py-4">
            <div class="flex items-center justify-between text-white">
                <h2 class="font-bold text-lg">
                    รายการ Banners ({{ $banners->count() }})
                </h2>
                <div class="text-sm opacity-80">
                    ลากเพื่อเรียงลำดับ
                </div>
            </div>
        </div>

        {{-- Banners Grid --}}
        @if($banners->count() > 0)
        <div class="p-6">
            <div id="banners-sortable" class="space-y-4">
                @foreach($banners as $banner)
                <div class="banner-item flex items-center gap-4 p-4
                           bg-gray-50 dark:bg-gray-700/50
                           rounded-xl border border-gray-200 dark:border-gray-600
                           hover:shadow-lg transition-all cursor-move"
                     data-id="{{ $banner->id }}">

                    {{-- Drag Handle --}}
                    <div class="drag-handle text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <i class="fas fa-grip-vertical text-xl"></i>
                    </div>

                    {{-- Banner Preview --}}
                    <div class="w-48 h-24 rounded-xl overflow-hidden shadow-md flex-shrink-0">
                        @if($banner->image_url)
                        <img src="{{ $banner->image_url }}"
                             alt="{{ $banner->title }}"
                             class="w-full h-full object-cover">
                        @else
                        <div class="w-full h-full bg-gradient-to-br {{ $banner->gradient ?? 'from-orange-500 to-red-500' }}
                                   flex items-center justify-center text-white">
                            <i class="fas fa-image text-2xl opacity-50"></i>
                        </div>
                        @endif
                    </div>

                    {{-- Banner Info --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            @if($banner->badge)
                            <span class="px-2 py-0.5 bg-orange-100 dark:bg-orange-900/30
                                       text-orange-700 dark:text-orange-400
                                       text-xs font-bold rounded">
                                {{ $banner->badge }}
                            </span>
                            @endif
                            <span class="px-2 py-0.5 bg-gray-200 dark:bg-gray-600
                                       text-gray-600 dark:text-gray-300
                                       text-xs font-medium rounded">
                                {{ $banner->location === 'homepage' ? 'หน้าแรก' : 'หมวดหมู่' }}
                            </span>
                        </div>

                        <h3 class="font-bold text-gray-900 dark:text-white truncate">
                            {{ $banner->title ?? 'ไม่มีชื่อ' }}
                        </h3>

                        @if($banner->subtitle)
                        <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                            {{ $banner->subtitle }}
                        </p>
                        @endif

                        @if($banner->cta_url)
                        <a href="{{ $banner->cta_url }}" target="_blank"
                           class="inline-flex items-center gap-1 text-xs text-blue-600 dark:text-blue-400 mt-1">
                            <i class="fas fa-external-link-alt"></i>
                            {{ Str::limit($banner->cta_url, 40) }}
                        </a>
                        @endif
                    </div>

                    {{-- Stats --}}
                    <div class="hidden md:flex items-center gap-6 text-sm text-gray-500 dark:text-gray-400">
                        <div class="text-center">
                            <div class="font-bold text-gray-900 dark:text-white">
                                {{ number_format($banner->view_count) }}
                            </div>
                            <div>Views</div>
                        </div>
                        <div class="text-center">
                            <div class="font-bold text-gray-900 dark:text-white">
                                {{ number_format($banner->click_count) }}
                            </div>
                            <div>Clicks</div>
                        </div>
                        <div class="text-center">
                            <div class="font-bold text-gray-900 dark:text-white">
                                {{ $banner->ctr }}%
                            </div>
                            <div>CTR</div>
                        </div>
                    </div>

                    {{-- Status Toggle --}}
                    <div class="flex items-center">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox"
                                   {{ $banner->is_active ? 'checked' : '' }}
                                   @change="toggleStatus({{ $banner->id }})"
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 dark:bg-gray-600
                                       peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300
                                       rounded-full peer
                                       peer-checked:after:translate-x-full peer-checked:after:border-white
                                       after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                       after:bg-white after:border-gray-300 after:border after:rounded-full
                                       after:h-5 after:w-5 after:transition-all
                                       peer-checked:bg-gradient-to-r peer-checked:from-orange-500 peer-checked:to-red-500"></div>
                        </label>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.storefront.banners.edit', $banner->id) }}"
                           class="p-2 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                           title="แก้ไข">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button @click="deleteBanner({{ $banner->id }})"
                                class="p-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                title="ลบ">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @else
        {{-- Empty State --}}
        <div class="p-12 text-center">
            <div class="w-24 h-24 mx-auto mb-6 rounded-full
                       bg-gray-100 dark:bg-gray-700
                       flex items-center justify-center">
                <i class="fas fa-images text-4xl text-gray-400"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                ยังไม่มี Banner
            </h3>
            <p class="text-gray-500 dark:text-gray-400 mb-6">
                เริ่มสร้าง Banner แรกเพื่อแสดงบนหน้า Storefront
            </p>
            <a href="{{ route('admin.storefront.banners.create') }}"
               class="inline-flex items-center gap-2 px-6 py-3
                     bg-gradient-to-r from-orange-500 to-red-500
                     text-white font-bold rounded-xl shadow-lg">
                <i class="fas fa-plus"></i>
                <span>เพิ่ม Banner แรก</span>
            </a>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
/**
 * Banners Manager Component
 */
function bannersManager() {
    return {
        init() {
            this.initSortable();
        },

        /**
         * Initialize SortableJS for drag & drop
         */
        initSortable() {
            const el = document.getElementById('banners-sortable');
            if (!el) return;

            new Sortable(el, {
                animation: 150,
                handle: '.drag-handle',
                ghostClass: 'opacity-50',
                onEnd: async (evt) => {
                    const items = [...el.querySelectorAll('.banner-item')].map((item, index) => ({
                        id: parseInt(item.dataset.id),
                        sort_order: index
                    }));

                    // ส่งลำดับใหม่ไป server
                    try {
                        const response = await fetch('{{ route("admin.storefront.banners.reorder") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({ banners: items })
                        });

                        const data = await response.json();
                        if (data.success) {
                            this.$dispatch('notify', { message: data.message, type: 'success' });
                        }
                    } catch (error) {
                        console.error('Error:', error);
                    }
                }
            });
        },

        /**
         * Toggle banner status
         */
        async toggleStatus(id) {
            try {
                const response = await fetch(`{{ url('admin/storefront/banners') }}/${id}/toggle`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();
                if (data.success) {
                    this.$dispatch('notify', { message: data.message, type: 'success' });
                }
            } catch (error) {
                console.error('Error:', error);
            }
        },

        /**
         * Delete banner
         */
        async deleteBanner(id) {
            if (!confirm('คุณแน่ใจหรือไม่ที่จะลบ Banner นี้?')) {
                return;
            }

            // Submit form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `{{ url('admin/storefront/banners') }}/${id}`;
            form.innerHTML = `
                <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}">
                <input type="hidden" name="_method" value="DELETE">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    };
}
</script>
@endpush
@endsection
