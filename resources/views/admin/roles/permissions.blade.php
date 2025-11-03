@extends('layouts.admin')

@section('title', 'จัดการสิทธิ์')

@section('content')
<div class="max-w-5xl mx-auto">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.roles.show', $role) }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                    ← กลับ
                </a>
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                        🔐 จัดการสิทธิ์: {{ $role->display_name }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        กำหนดสิทธิ์การเข้าถึงสำหรับบทบาทนี้
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">สิทธิ์ปัจจุบัน</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-gray-200 mt-2">{{ $role->permissions->count() }}</p>
                </div>
                <div class="text-4xl">🔐</div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">สิทธิ์ที่มีทั้งหมด</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-gray-200 mt-2">{{ $permissions->flatten()->count() }}</p>
                </div>
                <div class="text-4xl">📋</div>
            </div>
        </div>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.roles.permissions.update', $role) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
            @if($permissions->count() > 0)
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">เลือกสิทธิ์ที่ต้องการ</h3>
                    <div class="flex gap-2">
                        <button type="button"
                                onclick="selectAll()"
                                class="px-3 py-1 text-xs bg-indigo-100 text-indigo-700 rounded hover:bg-indigo-200 transition">
                            เลือกทั้งหมด
                        </button>
                        <button type="button"
                                onclick="deselectAll()"
                                class="px-3 py-1 text-xs bg-gray-100 text-gray-700 rounded hover:bg-gray-200 transition">
                            ยกเลิกทั้งหมด
                        </button>
                    </div>
                </div>

                <div class="space-y-4">
                    @foreach($permissions as $category => $categoryPermissions)
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                            <!-- Category Header -->
                            <div class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/30 dark:to-purple-900/30 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between">
                                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                        <span>{{ \App\Models\Permission::getCategoryDisplayName($category) }}</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            ({{ $categoryPermissions->count() }} สิทธิ์)
                                        </span>
                                    </h4>
                                    <button type="button"
                                            onclick="toggleCategory('{{ $category }}')"
                                            class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
                                        เลือกทั้งหมดในหมวดนี้
                                    </button>
                                </div>
                            </div>

                            <!-- Permissions List -->
                            <div class="p-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    @foreach($categoryPermissions as $permission)
                                        <label class="flex items-start cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-3 rounded-lg transition-all duration-200 border border-transparent hover:border-indigo-200 dark:hover:border-indigo-800"
                                               data-category="{{ $category }}">
                                            <input type="checkbox"
                                                   name="permissions[]"
                                                   value="{{ $permission->id }}"
                                                   {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}
                                                   class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 permission-checkbox"
                                                   data-category="{{ $category }}">
                                            <div class="ml-3 flex-1">
                                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $permission->display_name }}
                                                </div>
                                                @if($permission->description)
                                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                        {{ $permission->description }}
                                                    </div>
                                                @endif
                                                <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                                    <code class="bg-gray-100 dark:bg-gray-800 px-1 py-0.5 rounded">{{ $permission->name }}</code>
                                                </div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <span class="text-6xl mb-4 block">🔒</span>
                    <p class="text-gray-500 dark:text-gray-400 text-lg">ยังไม่มีสิทธิ์ในระบบ</p>
                </div>
            @endif

            @error('permissions')
                <p class="text-red-500 text-sm mt-4">{{ $message }}</p>
            @enderror

            <!-- Buttons -->
            <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-700 mt-6">
                <a href="{{ route('admin.roles.show', $role) }}"
                   class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200">
                    ยกเลิก
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-lg transition-all duration-200 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    บันทึกสิทธิ์
                </button>
            </div>
        </div>
    </form>

    @if(session('success'))
    <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-4 py-3 rounded-lg">
        {{ session('error') }}
    </div>
    @endif
</div>

<script>
function selectAll() {
    document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
        checkbox.checked = true;
    });
}

function deselectAll() {
    document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
}

function toggleCategory(category) {
    const checkboxes = document.querySelectorAll(`.permission-checkbox[data-category="${category}"]`);
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);

    checkboxes.forEach(checkbox => {
        checkbox.checked = !allChecked;
    });
}
</script>
@endsection
