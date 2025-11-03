@extends('layouts.admin')

@section('title', 'จัดการหมวดหมู่สินค้า')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">🏷️ จัดการหมวดหมู่สินค้า</h1>
        <button onclick="document.getElementById('createModal').classList.remove('hidden')" class="bg-orange-600 text-white px-6 py-2 rounded-lg hover:bg-orange-700">
            + เพิ่มหมวดหมู่
        </button>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Categories Table -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ชื่อหมวดหมู่</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Slug</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จำนวนสินค้า</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">การกระทำ</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($categories as $category)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $category->name }}</div>
                                @if($category->description)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ Str::limit($category->description, 50) }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ $category->slug }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ $category->products_count ?? 0 }} สินค้า
                            </td>
                            <td class="px-6 py-4">
                                @if($category->is_active)
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">ใช้งาน</span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200">ไม่ใช้งาน</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm space-x-2">
                                <button onclick="editCategory({{ $category }})" class="text-orange-600 dark:text-orange-400 hover:text-orange-700 dark:hover:text-orange-300 font-medium">
                                    แก้ไข
                                </button>
                                <form action="{{ route('admin.ecommerce.categories.delete', $category) }}" method="POST" class="inline" onsubmit="return confirm('คุณแน่ใจหรือไม่?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 font-medium">
                                        ลบ
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                ไม่พบหมวดหมู่
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $categories->links() }}
        </div>
    </div>
</div>

<!-- Create Modal -->
<div id="createModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl p-6 w-full max-w-md">
        <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">เพิ่มหมวดหมู่ใหม่</h2>
        <form action="{{ route('admin.ecommerce.categories.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อหมวดหมู่</label>
                    <input type="text" name="name" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Slug (เว้นว่างไว้เพื่อสร้างอัตโนมัติ)</label>
                    <input type="text" name="slug" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
                    <textarea name="description" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">URL รูปภาพ</label>
                    <input type="text" name="image_url" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ลำดับการแสดงผล</label>
                    <input type="number" name="sort_order" value="0" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">หมวดหมู่แม่</label>
                    <select name="parent_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white">
                        <option value="">ไม่มี (หมวดหมู่หลัก)</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                    <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">ใช้งาน</label>
                </div>
            </div>
            <div class="flex justify-end space-x-2 mt-6">
                <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')" class="px-4 py-2 bg-gray-300 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-400 dark:hover:bg-gray-600">
                    ยกเลิก
                </button>
                <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
                    บันทึก
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl p-6 w-full max-w-md">
        <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">แก้ไขหมวดหมู่</h2>
        <form id="editForm" method="POST">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อหมวดหมู่</label>
                    <input type="text" id="edit_name" name="name" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Slug (เว้นว่างไว้เพื่อสร้างอัตโนมัติ)</label>
                    <input type="text" id="edit_slug" name="slug" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
                    <textarea id="edit_description" name="description" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">URL รูปภาพ</label>
                    <input type="text" id="edit_image_url" name="image_url" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ลำดับการแสดงผล</label>
                    <input type="number" id="edit_sort_order" name="sort_order" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">หมวดหมู่แม่</label>
                    <select id="edit_parent_id" name="parent_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white">
                        <option value="">ไม่มี (หมวดหมู่หลัก)</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" id="edit_is_active" name="is_active" value="1" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                    <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">ใช้งาน</label>
                </div>
            </div>
            <div class="flex justify-end space-x-2 mt-6">
                <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" class="px-4 py-2 bg-gray-300 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-400 dark:hover:bg-gray-600">
                    ยกเลิก
                </button>
                <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
                    บันทึกการแก้ไข
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editCategory(category) {
    // Set form action
    const form = document.getElementById('editForm');
    form.action = `/admin/ecommerce/categories/${category.id}`;

    // Populate form fields
    document.getElementById('edit_name').value = category.name || '';
    document.getElementById('edit_slug').value = category.slug || '';
    document.getElementById('edit_description').value = category.description || '';
    document.getElementById('edit_image_url').value = category.image_url || '';
    document.getElementById('edit_sort_order').value = category.sort_order || 0;
    document.getElementById('edit_parent_id').value = category.parent_id || '';
    document.getElementById('edit_is_active').checked = category.is_active;

    // Show modal
    document.getElementById('editModal').classList.remove('hidden');
}
</script>
@endsection
