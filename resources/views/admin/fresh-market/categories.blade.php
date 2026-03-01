@extends('layouts.admin-v3')

@section('title', 'จัดการหมวดหมู่ - ตลาดสดไทยพร้อม')

@push('styles')
<style>
    .sortable-ghost {
        opacity: 0.4;
        background: rgba(16, 185, 129, 0.1);
    }
    .sortable-drag {
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    }
</style>
@endpush

@section('content')
<div class="space-y-6" x-data="categoryManager()">
    {{-- ส่วนหัว --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">จัดการหมวดหมู่ - ตลาดสดไทยพร้อม</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">จัดการหมวดหมู่สินค้าตลาดสด ลากเพื่อเรียงลำดับ</p>
        </div>
        <button @click="openCreateModal()"
                class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200">
            <i class="fas fa-plus mr-2"></i> เพิ่มหมวดหมู่
        </button>
    </div>

    {{-- แจ้งเตือน --}}
    @if(session('success'))
        <div class="bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-400 px-4 py-3 rounded-xl">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-400 px-4 py-3 rounded-xl">
            {{ session('error') }}
        </div>
    @endif

    {{-- ตารางหมวดหมู่ --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                <i class="fas fa-grip-vertical mr-1"></i> ลากแถวเพื่อเรียงลำดับหมวดหมู่
            </p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase w-10"></th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase w-16">ไอคอน</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ชื่อหมวดหมู่</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">คำอธิบาย</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จำนวนรายการ</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จัดการ</th>
                    </tr>
                </thead>
                <tbody id="categories-sortable" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($categories as $category)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition cursor-move"
                            data-id="{{ $category->id }}">
                            <td class="px-3 py-4 text-center text-gray-400 dark:text-gray-500">
                                <i class="fas fa-grip-vertical"></i>
                            </td>
                            <td class="px-4 py-4">
                                <span class="text-2xl">{{ $category->icon ?? '🏷️' }}</span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $category->name }}</div>
                                @if($category->slug)
                                    <div class="text-xs text-gray-400">{{ $category->slug }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ Str::limit($category->description, 60) ?? '-' }}
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                    {{ number_format($category->listings_count ?? 0) }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <form method="POST" action="{{ route('admin.fresh-market.categories.toggle', $category) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="focus:outline-none">
                                        @if($category->is_active)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 cursor-pointer hover:bg-green-200 dark:hover:bg-green-900/50 transition">
                                                เปิดใช้งาน
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                                                ปิดใช้งาน
                                            </span>
                                        @endif
                                    </button>
                                </form>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button @click="openEditModal({{ json_encode($category) }})"
                                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 transition">
                                        <i class="fas fa-edit mr-1"></i> แก้ไข
                                    </button>
                                    <form method="POST" action="{{ route('admin.fresh-market.categories.destroy', $category) }}"
                                          onsubmit="return confirm('ยืนยันการลบหมวดหมู่นี้?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition">
                                            <i class="fas fa-trash mr-1"></i> ลบ
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                <i class="fas fa-folder-open text-4xl mb-3 block"></i>
                                ยังไม่มีหมวดหมู่ กดปุ่ม "เพิ่มหมวดหมู่" เพื่อเริ่มต้น
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal สร้าง/แก้ไขหมวดหมู่ --}}
    <div x-show="showModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            {{-- Overlay --}}
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showModal = false"></div>

            {{-- Modal Content --}}
            <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg p-6 z-10"
                 @click.away="showModal = false">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white"
                        x-text="isEditing ? 'แก้ไขหมวดหมู่' : 'เพิ่มหมวดหมู่ใหม่'"></h3>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form :action="isEditing ? '{{ url('admin/fresh-market/categories') }}/' + editingId : '{{ route('admin.fresh-market.categories.store') }}'"
                      method="POST">
                    @csrf
                    <template x-if="isEditing">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ไอคอน (Emoji)</label>
                            <input type="text" name="icon" x-model="form.icon"
                                   class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500"
                                   placeholder="เช่น 🥬 🍎 🐟">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชื่อหมวดหมู่ <span class="text-red-500">*</span></label>
                            <input type="text" name="name" x-model="form.name" required
                                   class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500"
                                   placeholder="เช่น ผักสด, ผลไม้, เนื้อสัตว์">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">คำอธิบาย</label>
                            <textarea name="description" x-model="form.description" rows="3"
                                      class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500"
                                      placeholder="คำอธิบายสั้นๆ เกี่ยวกับหมวดหมู่นี้"></textarea>
                        </div>
                        <div class="flex items-center">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" x-model="form.is_active"
                                   class="rounded border-gray-300 text-green-600 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600">
                            <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">เปิดใช้งาน</label>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="showModal = false"
                                class="px-5 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                            ยกเลิก
                        </button>
                        <button type="submit"
                                class="px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-xl shadow-lg transition">
                            <i class="fas fa-save mr-1"></i>
                            <span x-text="isEditing ? 'บันทึกการแก้ไข' : 'สร้างหมวดหมู่'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    // ตั้งค่า SortableJS สำหรับลากเรียงลำดับหมวดหมู่
    document.addEventListener('DOMContentLoaded', function () {
        const el = document.getElementById('categories-sortable');
        if (el) {
            Sortable.create(el, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                dragClass: 'sortable-drag',
                handle: '.fa-grip-vertical',
                onEnd: function (evt) {
                    // ส่งลำดับใหม่ไปบันทึก
                    const items = el.querySelectorAll('tr[data-id]');
                    const order = Array.from(items).map((item, index) => ({
                        id: item.dataset.id,
                        sort_order: index
                    }));

                    fetch('{{ route("admin.fresh-market.categories.reorder") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ order: order })
                    });
                }
            });
        }
    });

    // Alpine.js component สำหรับจัดการ Modal
    function categoryManager() {
        return {
            showModal: false,
            isEditing: false,
            editingId: null,
            form: {
                icon: '',
                name: '',
                description: '',
                is_active: true,
            },
            openCreateModal() {
                this.isEditing = false;
                this.editingId = null;
                this.form = { icon: '', name: '', description: '', is_active: true };
                this.showModal = true;
            },
            openEditModal(category) {
                this.isEditing = true;
                this.editingId = category.id;
                this.form = {
                    icon: category.icon || '',
                    name: category.name || '',
                    description: category.description || '',
                    is_active: category.is_active ? true : false,
                };
                this.showModal = true;
            }
        };
    }
</script>
@endpush
@endsection
