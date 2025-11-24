@extends('layouts.admin')

@section('title', 'จัดการหมวดหมู่บริการ')

@push('styles')
<style>
    /* Sortable drag effects */
    .sortable-ghost {
        opacity: 0.4;
        background: rgba(99, 102, 241, 0.1);
    }

    .sortable-drag {
        opacity: 1 !important;
        cursor: grabbing !important;
        transform: rotate(2deg);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }
</style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto" x-data="serviceCategoryManager()">
    {{-- Header Section - Glassmorphism Card --}}
    <div class="mb-6 p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 shadow-xl">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-400 dark:to-pink-400 bg-clip-text text-transparent">
                    <i class="fas fa-layer-group mr-3"></i>จัดการหมวดหมู่บริการ
                </h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    จัดการหมวดหมู่ของบริการต่างๆ - สามารถลากเพื่อเรียงลำดับได้
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.service-categories.create') }}"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200">
                    <i class="fas fa-plus"></i>
                    <span>เพิ่มหมวดหมู่ใหม่</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 shadow-lg hover:shadow-xl transition-all duration-300 group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">หมวดหมู่ทั้งหมด</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $stats['total'] ?? 0 }}</p>
                </div>
                <div class="p-4 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-layer-group text-2xl text-white"></i>
                </div>
            </div>
        </div>

        <div class="p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 shadow-lg hover:shadow-xl transition-all duration-300 group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">หมวดหมู่ที่เปิดใช้งาน</p>
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-1">{{ $stats['active'] ?? 0 }}</p>
                </div>
                <div class="p-4 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-check-circle text-2xl text-white"></i>
                </div>
            </div>
        </div>

        <div class="p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 shadow-lg hover:shadow-xl transition-all duration-300 group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">บริการทั้งหมด</p>
                    <p class="text-3xl font-bold text-purple-600 dark:text-purple-400 mt-1">{{ $stats['total_services'] ?? 0 }}</p>
                </div>
                <div class="p-4 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-concierge-bell text-2xl text-white"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Categories List - Sortable --}}
    <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    <i class="fas fa-list mr-2 text-purple-600 dark:text-purple-400"></i>
                    รายการหมวดหมู่บริการ
                </h2>
                <span class="text-sm text-gray-600 dark:text-gray-400">
                    <i class="fas fa-info-circle mr-1"></i>
                    ลากเพื่อเรียงลำดับ
                </span>
            </div>
        </div>

        @if($categories->isEmpty())
            <div class="p-12 text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-100 dark:bg-gray-700 rounded-full mb-4">
                    <i class="fas fa-layer-group text-3xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">ยังไม่มีหมวดหมู่บริการ</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">เริ่มต้นสร้างหมวดหมู่บริการแรกของคุณ</p>
                <a href="{{ route('admin.service-categories.create') }}"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-200">
                    <i class="fas fa-plus"></i>
                    <span>เพิ่มหมวดหมู่ใหม่</span>
                </a>
            </div>
        @else
            <div id="categoriesList" class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($categories as $category)
                <div class="category-item p-6 hover:bg-gray-50/50 dark:hover:bg-gray-700/50 transition-all duration-200 cursor-grab active:cursor-grabbing group"
                     data-id="{{ $category->id }}">
                    <div class="flex items-center gap-6">
                        {{-- Drag Handle --}}
                        <div class="flex-shrink-0 text-gray-400 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">
                            <i class="fas fa-grip-vertical text-xl"></i>
                        </div>

                        {{-- Icon & Color --}}
                        <div class="flex-shrink-0">
                            <div class="w-14 h-14 rounded-xl flex items-center justify-center text-2xl shadow-lg"
                                 style="background: linear-gradient(135deg, {{ $category->color }}CC, {{ $category->color }}FF);">
                                {{ $category->icon ?? '📦' }}
                            </div>
                        </div>

                        {{-- Category Info --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 mb-1">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                    {{ $category->name }}
                                </h3>
                                @if($category->is_active)
                                    <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-xs font-semibold rounded-full">
                                        <i class="fas fa-check-circle mr-1"></i>เปิดใช้งาน
                                    </span>
                                @else
                                    <span class="px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs font-semibold rounded-full">
                                        <i class="fas fa-times-circle mr-1"></i>ปิดใช้งาน
                                    </span>
                                @endif
                            </div>

                            @if($category->description)
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2 line-clamp-1">
                                    {{ $category->description }}
                                </p>
                            @endif

                            <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                                <span>
                                    <i class="fas fa-hashtag mr-1"></i>
                                    {{ $category->slug }}
                                </span>
                                <span>
                                    <i class="fas fa-concierge-bell mr-1"></i>
                                    {{ $category->active_services_count ?? 0 }} บริการ
                                </span>
                                <span>
                                    <i class="fas fa-sort mr-1"></i>
                                    ลำดับที่ {{ $category->sort_order ?? 0 }}
                                </span>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex-shrink-0 flex items-center gap-2">
                            {{-- Toggle Active/Inactive --}}
                            <button @click="toggleActive({{ $category->id }}, {{ $category->is_active ? 'false' : 'true' }})"
                                    class="p-3 rounded-lg transition-all duration-200 hover:scale-110
                                           {{ $category->is_active
                                              ? 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 hover:bg-green-200 dark:hover:bg-green-900/50'
                                              : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
                                    title="{{ $category->is_active ? 'ปิดใช้งาน' : 'เปิดใช้งาน' }}">
                                <i class="fas fa-{{ $category->is_active ? 'toggle-on' : 'toggle-off' }} text-lg"></i>
                            </button>

                            {{-- Edit --}}
                            <a href="{{ route('admin.service-categories.edit', $category) }}"
                               class="p-3 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-all duration-200 hover:scale-110"
                               title="แก้ไข">
                                <i class="fas fa-edit text-lg"></i>
                            </a>

                            {{-- Delete --}}
                            <button @click="confirmDelete({{ $category->id }}, '{{ $category->name }}')"
                                    class="p-3 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition-all duration-200 hover:scale-110"
                                    title="ลบ">
                                <i class="fas fa-trash text-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Pagination --}}
    @if($categories->hasPages())
        <div class="mt-6">
            {{ $categories->links() }}
        </div>
    @endif
</div>

{{-- Delete Confirmation Modal --}}
<div x-show="showDeleteModal"
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        {{-- Backdrop --}}
        <div x-show="showDeleteModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"
             @click="showDeleteModal = false"></div>

        {{-- Modal --}}
        <div x-show="showDeleteModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">

            <div class="sm:flex sm:items-start">
                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/30 sm:mx-0 sm:h-10 sm:w-10">
                    <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400"></i>
                </div>
                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                    <h3 class="text-lg leading-6 font-bold text-gray-900 dark:text-white">
                        ยืนยันการลบหมวดหมู่
                    </h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            คุณแน่ใจหรือไม่ว่าต้องการลบหมวดหมู่ "<span x-text="deleteItem.name" class="font-semibold"></span>"?
                            การกระทำนี้ไม่สามารถย้อนกลับได้
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-3">
                <button @click="deleteCategory()"
                        :disabled="deleting"
                        class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200">
                    <span x-show="!deleting">ลบหมวดหมู่</span>
                    <span x-show="deleting">
                        <i class="fas fa-spinner fa-spin mr-2"></i>กำลังลบ...
                    </span>
                </button>
                <button @click="showDeleteModal = false"
                        type="button"
                        class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:mt-0 sm:w-auto sm:text-sm transition-all duration-200">
                    ยกเลิก
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
function serviceCategoryManager() {
    return {
        showDeleteModal: false,
        deleting: false,
        deleteItem: { id: null, name: '' },
        sortable: null,

        init() {
            this.initSortable();
        },

        initSortable() {
            const el = document.getElementById('categoriesList');
            if (!el) return;

            this.sortable = Sortable.create(el, {
                animation: 150,
                handle: '.fa-grip-vertical',
                ghostClass: 'sortable-ghost',
                dragClass: 'sortable-drag',
                onEnd: (evt) => {
                    this.updateOrder();
                }
            });
        },

        async updateOrder() {
            const items = document.querySelectorAll('.category-item');
            const order = Array.from(items).map(item => parseInt(item.dataset.id));

            try {
                const response = await fetch('{{ route('admin.service-categories.reorder') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ order })
                });

                if (!response.ok) throw new Error('Failed to update order');

                // แสดงแจ้งเตือนสำเร็จ (ถ้ามี notification system)
                this.showNotification('success', 'เรียงลำดับหมวดหมู่สำเร็จ');
            } catch (error) {
                console.error('Error updating order:', error);
                this.showNotification('error', 'เกิดข้อผิดพลาดในการเรียงลำดับ');
                location.reload(); // Reload เพื่อให้กลับสู่ลำดับเดิม
            }
        },

        async toggleActive(id, newStatus) {
            try {
                const response = await fetch(`/admin/service-categories/${id}/toggle-active`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ is_active: newStatus })
                });

                if (!response.ok) throw new Error('Failed to toggle status');

                location.reload(); // Reload เพื่ออัพเดท UI
            } catch (error) {
                console.error('Error toggling status:', error);
                this.showNotification('error', 'เกิดข้อผิดพลาดในการเปลี่ยนสถานะ');
            }
        },

        confirmDelete(id, name) {
            this.deleteItem = { id, name };
            this.showDeleteModal = true;
        },

        async deleteCategory() {
            this.deleting = true;

            try {
                const response = await fetch(`/admin/service-categories/${this.deleteItem.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (!response.ok) throw new Error('Failed to delete');

                this.showNotification('success', 'ลบหมวดหมู่สำเร็จ');
                location.reload();
            } catch (error) {
                console.error('Error deleting category:', error);
                this.showNotification('error', 'เกิดข้อผิดพลาดในการลบ');
                this.deleting = false;
            }
        },

        showNotification(type, message) {
            // ใช้ notification system ของ project (ถ้ามี)
            // หรือใช้ alert ชั่วคราว
            if (type === 'success') {
                alert('✓ ' + message);
            } else {
                alert('✗ ' + message);
            }
        }
    }
}
</script>
@endpush
