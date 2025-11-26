@extends('layouts.admin-v3')

@section('title', 'จัดการสิทธิ์')

@section('content')
<div class="max-w-7xl mx-auto" x-data="permissionManager()">
    <!-- Header -->
    <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-md p-6 mb-6 border border-white/20 dark:border-white/10">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.roles.show', $role) }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                    ← กลับ
                </a>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                        🔐 จัดการสิทธิ์: {{ $role->display_name }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        กำหนดสิทธิ์การเข้าถึงสำหรับบทบาทนี้ ({{ $permissions->flatten()->count() }} สิทธิ์ทั้งหมด)
                    </p>
                </div>
            </div>

            <!-- Search -->
            <div class="relative">
                <input type="text"
                       x-model="searchQuery"
                       placeholder="ค้นหาสิทธิ์..."
                       class="w-64 pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white/50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow p-4 border border-white/20 dark:border-white/10">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">สิทธิ์ที่เลือก</p>
                    <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400" x-text="selectedCount"></p>
                </div>
                <div class="text-3xl">✅</div>
            </div>
        </div>

        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow p-4 border border-white/20 dark:border-white/10">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">สิทธิ์ทั้งหมด</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $permissions->flatten()->count() }}</p>
                </div>
                <div class="text-3xl">📋</div>
            </div>
        </div>

        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow p-4 border border-white/20 dark:border-white/10">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">หมวดหมู่</p>
                    <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $permissions->count() }}</p>
                </div>
                <div class="text-3xl">📂</div>
            </div>
        </div>

        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow p-4 border border-white/20 dark:border-white/10">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">ผู้ใช้ในบทบาท</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $role->users->count() }}</p>
                </div>
                <div class="text-3xl">👥</div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-md p-4 mb-6 border border-white/20 dark:border-white/10">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-sm text-gray-600 dark:text-gray-400">การดำเนินการด่วน:</span>
                <button type="button"
                        @click="selectAll()"
                        class="px-3 py-1.5 text-xs bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-lg hover:from-green-600 hover:to-emerald-700 transition shadow-sm">
                    ✅ เลือกทั้งหมด
                </button>
                <button type="button"
                        @click="deselectAll()"
                        class="px-3 py-1.5 text-xs bg-gradient-to-r from-red-500 to-rose-600 text-white rounded-lg hover:from-red-600 hover:to-rose-700 transition shadow-sm">
                    ❌ ยกเลิกทั้งหมด
                </button>
                <button type="button"
                        @click="selectViewOnly()"
                        class="px-3 py-1.5 text-xs bg-gradient-to-r from-blue-500 to-cyan-600 text-white rounded-lg hover:from-blue-600 hover:to-cyan-700 transition shadow-sm">
                    👁️ เฉพาะดู (View)
                </button>
                <button type="button"
                        @click="expandAll()"
                        class="px-3 py-1.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                    📂 ขยายทั้งหมด
                </button>
                <button type="button"
                        @click="collapseAll()"
                        class="px-3 py-1.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                    📁 ย่อทั้งหมด
                </button>
            </div>

            <div class="text-sm text-gray-500 dark:text-gray-400">
                <span x-show="searchQuery.length > 0">
                    พบ <span class="font-bold text-indigo-600" x-text="filteredCount"></span> รายการ
                </span>
            </div>
        </div>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.roles.permissions.update', $role) }}" method="POST" id="permissionForm">
        @csrf
        @method('PUT')

        <div class="space-y-4 mb-6">
            @php
                // จัดกลุ่ม permissions ตาม category group
                $categoryDefinitions = \App\Models\Permission::getAllCategoryDefinitions();
                $groupedPermissions = [];

                foreach ($permissions as $category => $categoryPermissions) {
                    $group = $categoryDefinitions[$category]['group'] ?? 'other';
                    if (!isset($groupedPermissions[$group])) {
                        $groupedPermissions[$group] = [
                            'name' => \App\Models\Permission::getGroupDisplayName($group),
                            'categories' => [],
                        ];
                    }
                    $groupedPermissions[$group]['categories'][$category] = [
                        'name' => $categoryDefinitions[$category]['name'] ?? $category,
                        'icon' => $categoryDefinitions[$category]['icon'] ?? '📂',
                        'permissions' => $categoryPermissions,
                    ];
                }
            @endphp

            @foreach($groupedPermissions as $groupKey => $group)
                <!-- Group Container -->
                <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-white/20 dark:border-white/10"
                     x-show="isGroupVisible('{{ $groupKey }}')"
                     x-data="{ expanded: true }">

                    <!-- Group Header -->
                    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-4 py-3 cursor-pointer"
                         @click="expanded = !expanded">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-white flex items-center gap-3">
                                <span class="text-2xl">{{ explode(' ', $group['name'])[0] }}</span>
                                <span>{{ implode(' ', array_slice(explode(' ', $group['name']), 1)) }}</span>
                                <span class="text-xs bg-white/20 px-2 py-0.5 rounded-full">
                                    {{ collect($group['categories'])->sum(fn($c) => count($c['permissions'])) }} สิทธิ์
                                </span>
                            </h3>
                            <div class="flex items-center gap-2">
                                <button type="button"
                                        @click.stop="selectGroup('{{ $groupKey }}')"
                                        class="text-xs bg-white/20 hover:bg-white/30 text-white px-2 py-1 rounded transition">
                                    เลือกทั้งกลุ่ม
                                </button>
                                <svg class="w-5 h-5 text-white transition-transform" :class="{ 'rotate-180': expanded }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Group Content -->
                    <div x-show="expanded" x-collapse>
                        <div class="p-4 space-y-4">
                            @foreach($group['categories'] as $categoryKey => $category)
                                <!-- Category -->
                                <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden"
                                     x-show="isCategoryVisible('{{ $categoryKey }}')"
                                     x-data="{ categoryExpanded: true }">

                                    <!-- Category Header -->
                                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600 px-4 py-2.5 cursor-pointer border-b border-gray-200 dark:border-gray-600"
                                         @click="categoryExpanded = !categoryExpanded">
                                        <div class="flex items-center justify-between">
                                            <h4 class="font-semibold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                                                <span class="text-xl">{{ $category['icon'] }}</span>
                                                <span>{{ $category['name'] }}</span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400 font-normal">
                                                    ({{ count($category['permissions']) }} สิทธิ์)
                                                </span>
                                            </h4>
                                            <div class="flex items-center gap-2">
                                                <button type="button"
                                                        @click.stop="toggleCategory('{{ $categoryKey }}')"
                                                        class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">
                                                    สลับเลือก
                                                </button>
                                                <svg class="w-4 h-4 text-gray-500 transition-transform" :class="{ 'rotate-180': categoryExpanded }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Permissions List -->
                                    <div x-show="categoryExpanded" x-collapse>
                                        <div class="p-3 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                                            @foreach($category['permissions'] as $permission)
                                                <label class="flex items-start cursor-pointer hover:bg-indigo-50 dark:hover:bg-gray-700 p-2.5 rounded-lg transition-all duration-200 border border-transparent hover:border-indigo-200 dark:hover:border-indigo-800"
                                                       x-show="isPermissionVisible('{{ $permission->name }}', '{{ $permission->display_name }}')"
                                                       data-category="{{ $categoryKey }}"
                                                       data-group="{{ $groupKey }}">
                                                    <input type="checkbox"
                                                           name="permissions[]"
                                                           value="{{ $permission->id }}"
                                                           {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}
                                                           @change="updateCount()"
                                                           class="mt-0.5 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 permission-checkbox"
                                                           data-category="{{ $categoryKey }}"
                                                           data-group="{{ $groupKey }}"
                                                           data-name="{{ $permission->name }}">
                                                    <div class="ml-2.5 flex-1 min-w-0">
                                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                                            {{ $permission->display_name }}
                                                        </div>
                                                        @if($permission->description)
                                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-1">
                                                                {{ $permission->description }}
                                                            </div>
                                                        @endif
                                                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                                            <code class="bg-gray-100 dark:bg-gray-800 px-1 py-0.5 rounded text-[10px]">{{ $permission->name }}</code>
                                                        </div>
                                                    </div>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @error('permissions')
            <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-4 py-3 rounded-xl mb-6">
                {{ $message }}
            </div>
        @enderror

        <!-- Sticky Footer -->
        <div class="sticky bottom-0 glass-fusion dark:bg-gray-800 rounded-xl shadow-lg p-4 border border-white/20 dark:border-white/10">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <span class="font-bold text-indigo-600 dark:text-indigo-400" x-text="selectedCount"></span> สิทธิ์ที่เลือก
                    จาก {{ $permissions->flatten()->count() }} สิทธิ์ทั้งหมด
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.roles.show', $role) }}"
                       class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-200">
                        ยกเลิก
                    </a>
                    <button type="submit"
                            class="px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl transition-all duration-200 flex items-center gap-2 shadow-lg hover:shadow-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        บันทึกสิทธิ์
                    </button>
                </div>
            </div>
        </div>
    </form>

    @if(session('success'))
    <div class="fixed bottom-20 right-4 bg-green-50 dark:bg-green-900/90 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-6 py-4 rounded-xl shadow-lg z-50"
         x-data="{ show: true }"
         x-show="show"
         x-init="setTimeout(() => show = false, 3000)"
         x-transition>
        ✅ {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="fixed bottom-20 right-4 bg-red-50 dark:bg-red-900/90 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-6 py-4 rounded-xl shadow-lg z-50"
         x-data="{ show: true }"
         x-show="show"
         x-init="setTimeout(() => show = false, 5000)"
         x-transition>
        ❌ {{ session('error') }}
    </div>
    @endif
</div>

<script>
function permissionManager() {
    return {
        searchQuery: '',
        selectedCount: {{ count($rolePermissions) }},
        filteredCount: {{ $permissions->flatten()->count() }},

        init() {
            this.updateCount();
        },

        updateCount() {
            this.selectedCount = document.querySelectorAll('.permission-checkbox:checked').length;
        },

        selectAll() {
            document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
                if (this.isVisibleCheckbox(checkbox)) {
                    checkbox.checked = true;
                }
            });
            this.updateCount();
        },

        deselectAll() {
            document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });
            this.updateCount();
        },

        selectViewOnly() {
            document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
                const name = checkbox.dataset.name || '';
                checkbox.checked = name.startsWith('view_');
            });
            this.updateCount();
        },

        toggleCategory(category) {
            const checkboxes = document.querySelectorAll(`.permission-checkbox[data-category="${category}"]`);
            const visibleCheckboxes = Array.from(checkboxes).filter(cb => this.isVisibleCheckbox(cb));
            const allChecked = visibleCheckboxes.every(cb => cb.checked);

            visibleCheckboxes.forEach(checkbox => {
                checkbox.checked = !allChecked;
            });
            this.updateCount();
        },

        selectGroup(group) {
            const checkboxes = document.querySelectorAll(`.permission-checkbox[data-group="${group}"]`);
            const visibleCheckboxes = Array.from(checkboxes).filter(cb => this.isVisibleCheckbox(cb));
            const allChecked = visibleCheckboxes.every(cb => cb.checked);

            visibleCheckboxes.forEach(checkbox => {
                checkbox.checked = !allChecked;
            });
            this.updateCount();
        },

        expandAll() {
            // ใช้ Alpine.js เพื่อขยายทุก accordion
            document.querySelectorAll('[x-data]').forEach(el => {
                if (el.__x) {
                    el.__x.$data.expanded = true;
                    if (el.__x.$data.categoryExpanded !== undefined) {
                        el.__x.$data.categoryExpanded = true;
                    }
                }
            });
        },

        collapseAll() {
            document.querySelectorAll('[x-data]').forEach(el => {
                if (el.__x && el.__x.$data.expanded !== undefined) {
                    el.__x.$data.expanded = false;
                }
            });
        },

        isVisibleCheckbox(checkbox) {
            const label = checkbox.closest('label');
            return label && label.offsetParent !== null;
        },

        isPermissionVisible(name, displayName) {
            if (!this.searchQuery) return true;
            const query = this.searchQuery.toLowerCase();
            return name.toLowerCase().includes(query) ||
                   displayName.toLowerCase().includes(query);
        },

        isCategoryVisible(category) {
            if (!this.searchQuery) return true;
            // แสดง category ถ้ามี permission ที่ตรงกับการค้นหา
            const checkboxes = document.querySelectorAll(`.permission-checkbox[data-category="${category}"]`);
            return Array.from(checkboxes).some(cb => {
                const label = cb.closest('label');
                const name = cb.dataset.name || '';
                const displayName = label?.querySelector('.font-medium')?.textContent || '';
                const query = this.searchQuery.toLowerCase();
                return name.toLowerCase().includes(query) ||
                       displayName.toLowerCase().includes(query);
            });
        },

        isGroupVisible(group) {
            if (!this.searchQuery) return true;
            // แสดง group ถ้ามี permission ที่ตรงกับการค้นหา
            const checkboxes = document.querySelectorAll(`.permission-checkbox[data-group="${group}"]`);
            return Array.from(checkboxes).some(cb => {
                const label = cb.closest('label');
                const name = cb.dataset.name || '';
                const displayName = label?.querySelector('.font-medium')?.textContent || '';
                const query = this.searchQuery.toLowerCase();
                return name.toLowerCase().includes(query) ||
                       displayName.toLowerCase().includes(query);
            });
        }
    };
}
</script>
@endsection
