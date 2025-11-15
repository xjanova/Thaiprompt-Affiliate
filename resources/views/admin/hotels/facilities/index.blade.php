@extends('layouts.admin')

@section('title', 'จัดการสิ่งอำนวยความสะดวก')

@section('content')
<div class="container mx-auto px-4 py-6" x-data="facilitiesManager()">
    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-concierge-bell text-emerald-600 dark:text-emerald-400"></i>
                จัดการสิ่งอำนวยความสะดวก
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">จัดการรายการสิ่งอำนวยความสะดวกของโรงแรม</p>
        </div>
        <button @click="showAddModal = true"
                class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all">
            <i class="fas fa-plus"></i>
            <span>เพิ่มสิ่งอำนวยความสะดวก</span>
        </button>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm">ทั้งหมด</p>
                    <p class="text-3xl font-bold mt-1">{{ $facilities->count() }}</p>
                </div>
                <div class="bg-white/20 rounded-full p-3">
                    <i class="fas fa-list text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm">เปิดใช้งาน</p>
                    <p class="text-3xl font-bold mt-1">{{ $facilities->where('is_active', true)->count() }}</p>
                </div>
                <div class="bg-white/20 rounded-full p-3">
                    <i class="fas fa-check-circle text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-amber-100 text-sm">ปิดใช้งาน</p>
                    <p class="text-3xl font-bold mt-1">{{ $facilities->where('is_active', false)->count() }}</p>
                </div>
                <div class="bg-white/20 rounded-full p-3">
                    <i class="fas fa-times-circle text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm">หมวดหมู่</p>
                    <p class="text-3xl font-bold mt-1">{{ $facilities->unique('category')->count() }}</p>
                </div>
                <div class="bg-white/20 rounded-full p-3">
                    <i class="fas fa-tags text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Table Card --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        {{-- Table Header --}}
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">รายการสิ่งอำนวยความสะดวก</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">ลากเพื่อเรียงลำดับ</p>
                </div>
                <div class="flex gap-2">
                    <select x-model="filterCategory"
                            class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 transition-all">
                        <option value="">ทุกหมวดหมู่</option>
                        <option value="general">ทั่วไป</option>
                        <option value="recreation">สันทนาการ</option>
                        <option value="service">บริการ</option>
                        <option value="food">อาหาร</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-20">
                            ลำดับ
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-24">
                            ไอคอน
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ชื่อ
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            หมวดหมู่
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            สถานะ
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-40">
                            จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700" id="sortable">
                    @forelse($facilities as $facility)
                        <tr data-id="{{ $facility->id }}"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group"
                            x-show="filterCategory === '' || filterCategory === '{{ $facility->category }}'">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-grip-vertical text-gray-400 dark:text-gray-500 cursor-move hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                                       title="ลากเพื่อเรียงลำดับ"></i>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $facility->sort_order }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-gradient-to-br from-emerald-100 to-teal-100 dark:from-emerald-900/30 dark:to-teal-900/30">
                                    <i class="fas fa-{{ $facility->icon }} text-2xl text-emerald-600 dark:text-emerald-400"></i>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $facility->name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $categoryColors = [
                                        'general' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                                        'recreation' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
                                        'service' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                        'food' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                                    ];
                                    $categoryLabels = [
                                        'general' => 'ทั่วไป',
                                        'recreation' => 'สันทนาการ',
                                        'service' => 'บริการ',
                                        'food' => 'อาหาร',
                                    ];
                                @endphp
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $categoryColors[$facility->category] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                    {{ $categoryLabels[$facility->category] ?? $facility->category }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <label class="relative inline-flex items-center cursor-pointer"
                                       title="{{ $facility->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}">
                                    <input type="checkbox"
                                           class="sr-only peer"
                                           {{ $facility->is_active ? 'checked' : '' }}
                                           onchange="toggleStatus({{ $facility->id }}, this)">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 dark:peer-focus:ring-emerald-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-emerald-600"></div>
                                </label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="editFacility({{ $facility->id }}, '{{ $facility->name }}', '{{ $facility->icon }}', '{{ $facility->category }}')"
                                            class="inline-flex items-center gap-1 px-3 py-2 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors"
                                            title="แก้ไข">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button @click="deleteFacility({{ $facility->id }})"
                                            class="inline-flex items-center gap-1 px-3 py-2 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors"
                                            title="ลบ">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="fas fa-inbox text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                                    <p class="text-gray-500 dark:text-gray-400 text-lg">ยังไม่มีข้อมูล</p>
                                    <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">เพิ่มสิ่งอำนวยความสะดวกใหม่เพื่อเริ่มต้น</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Add Modal --}}
    <div x-show="showAddModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title"
         role="dialog"
         aria-modal="true"
         @keydown.escape.window="showAddModal = false">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            {{-- Background overlay --}}
            <div x-show="showAddModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75 transition-opacity"
                 @click="showAddModal = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            {{-- Modal panel --}}
            <div x-show="showAddModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">

                <form action="{{ route('admin.hotels.facilities.store') }}" method="POST">
                    @csrf

                    {{-- Modal Header --}}
                    <div class="bg-gradient-to-r from-emerald-500 to-teal-600 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-semibold text-white flex items-center gap-2">
                                <i class="fas fa-plus-circle"></i>
                                เพิ่มสิ่งอำนวยความสะดวก
                            </h3>
                            <button type="button"
                                    @click="showAddModal = false"
                                    class="text-white hover:text-gray-200 transition-colors">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Modal Body --}}
                    <div class="px-6 py-6 space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-tag text-emerald-600 dark:text-emerald-400 mr-1"></i>
                                ชื่อ <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   name="name"
                                   required
                                   class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-transparent transition-all"
                                   placeholder="เช่น Wi-Fi ฟรี, สระว่ายน้ำ">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-icons text-purple-600 dark:text-purple-400 mr-1"></i>
                                ไอคอน (Font Awesome) <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   name="icon"
                                   required
                                   class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-transparent transition-all"
                                   placeholder="wifi, swimming-pool, parking">
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                ดูไอคอนเพิ่มเติมที่ <a href="https://fontawesome.com/icons" target="_blank" class="text-emerald-600 dark:text-emerald-400 hover:underline">FontAwesome</a>
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-folder text-blue-600 dark:text-blue-400 mr-1"></i>
                                หมวดหมู่
                            </label>
                            <select name="category"
                                    class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-transparent transition-all">
                                <option value="general">🏨 ทั่วไป</option>
                                <option value="recreation">🎯 สันทนาการ</option>
                                <option value="service">🛎️ บริการ</option>
                                <option value="food">🍽️ อาหาร</option>
                            </select>
                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="bg-gray-50 dark:bg-gray-900/50 px-6 py-4 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                        <button type="button"
                                @click="showAddModal = false"
                                class="w-full sm:w-auto px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors font-medium">
                            ยกเลิก
                        </button>
                        <button type="submit"
                                class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-lg shadow-lg hover:shadow-xl transition-all font-semibold">
                            <i class="fas fa-save mr-2"></i>บันทึก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div x-show="showEditModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title"
         role="dialog"
         aria-modal="true"
         @keydown.escape.window="showEditModal = false">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            {{-- Background overlay --}}
            <div x-show="showEditModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75 transition-opacity"
                 @click="showEditModal = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            {{-- Modal panel --}}
            <div x-show="showEditModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">

                <form :action="`{{ url('admin/hotels/facilities') }}/${editId}`" method="POST">
                    @csrf
                    @method('PUT')

                    {{-- Modal Header --}}
                    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-semibold text-white flex items-center gap-2">
                                <i class="fas fa-edit"></i>
                                แก้ไขสิ่งอำนวยความสะดวก
                            </h3>
                            <button type="button"
                                    @click="showEditModal = false"
                                    class="text-white hover:text-gray-200 transition-colors">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Modal Body --}}
                    <div class="px-6 py-6 space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-tag text-blue-600 dark:text-blue-400 mr-1"></i>
                                ชื่อ <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   name="name"
                                   x-model="editName"
                                   required
                                   class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-icons text-purple-600 dark:text-purple-400 mr-1"></i>
                                ไอคอน (Font Awesome) <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   name="icon"
                                   x-model="editIcon"
                                   required
                                   class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-folder text-green-600 dark:text-green-400 mr-1"></i>
                                หมวดหมู่
                            </label>
                            <select name="category"
                                    x-model="editCategory"
                                    class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all">
                                <option value="general">🏨 ทั่วไป</option>
                                <option value="recreation">🎯 สันทนาการ</option>
                                <option value="service">🛎️ บริการ</option>
                                <option value="food">🍽️ อาหาร</option>
                            </select>
                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="bg-gray-50 dark:bg-gray-900/50 px-6 py-4 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                        <button type="button"
                                @click="showEditModal = false"
                                class="w-full sm:w-auto px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors font-medium">
                            ยกเลิก
                        </button>
                        <button type="submit"
                                class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-lg shadow-lg hover:shadow-xl transition-all font-semibold">
                            <i class="fas fa-save mr-2"></i>บันทึกการแก้ไข
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
function facilitiesManager() {
    return {
        showAddModal: false,
        showEditModal: false,
        editId: null,
        editName: '',
        editIcon: '',
        editCategory: 'general',
        filterCategory: '',

        init() {
            // Initialize SortableJS
            const el = document.getElementById('sortable');
            if (el) {
                Sortable.create(el, {
                    handle: '.fa-grip-vertical',
                    animation: 150,
                    onEnd: (evt) => {
                        const order = Array.from(el.querySelectorAll('tr[data-id]'))
                            .map(row => row.dataset.id);

                        fetch('{{ route("admin.hotels.facilities.reorder") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ order })
                        });
                    }
                });
            }
        },

        editFacility(id, name, icon, category) {
            this.editId = id;
            this.editName = name;
            this.editIcon = icon;
            this.editCategory = category;
            this.showEditModal = true;
        },

        deleteFacility(id) {
            if (confirm('คุณต้องการลบรายการนี้?')) {
                fetch(`{{ url('admin/hotels/facilities') }}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                }).then(() => location.reload());
            }
        }
    }
}

function toggleStatus(id, checkbox) {
    fetch(`{{ url('admin/hotels/facilities') }}/${id}/toggle-status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    }).then(response => {
        if (!response.ok) {
            checkbox.checked = !checkbox.checked;
        }
    });
}
</script>

<style>
[x-cloak] {
    display: none !important;
}
</style>
@endpush
@endsection
