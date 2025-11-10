@extends('layouts.seller')

@section('title', 'หมวดหมู่ POS')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-600 via-pink-600 to-rose-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white opacity-10 rounded-full"></div>
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold mb-2">📂 หมวดหมู่ POS</h1>
                    <p class="text-purple-100">จัดการหมวดหมู่สินค้าในระบบ POS</p>
                </div>
                <button onclick="openCreateModal()" class="px-6 py-3 bg-white text-purple-600 rounded-lg hover:bg-purple-50 transition font-semibold">
                    + เพิ่มหมวดหมู่
                </button>
            </div>
        </div>
    </div>

    <!-- Categories Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($categories as $category)
            <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-lg flex items-center justify-center text-2xl"
                             style="background: {{ $category->color ?? '#6366f1' }}20">
                            {{ $category->icon ?? '📦' }}
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">{{ $category->name }}</h3>
                            <p class="text-sm text-gray-500">{{ $category->products_count ?? 0 }} สินค้า</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="editCategory({{ $category->id }})" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition">
                            ✏️
                        </button>
                        <form action="{{ route('seller.pos.categories.destroy', $category->id) }}" method="POST" class="inline" onsubmit="return confirm('ต้องการลบหมวดหมู่นี้?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                                🗑️
                            </button>
                        </form>
                    </div>
                </div>

                @if($category->description)
                    <p class="text-gray-600 text-sm mb-3">{{ $category->description }}</p>
                @endif

                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                    <div class="flex items-center gap-2">
                        @if($category->is_active)
                            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">เปิดใช้งาน</span>
                        @else
                            <span class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-semibold">ปิดใช้งาน</span>
                        @endif

                        @if($category->show_in_pos)
                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-semibold">แสดงใน POS</span>
                        @endif
                    </div>
                    <span class="text-sm text-gray-500">#{{ $category->order ?? 0 }}</span>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="bg-white rounded-2xl shadow-xl p-12 text-center">
                    <span class="text-6xl block mb-4">📂</span>
                    <p class="text-gray-500 text-lg font-medium mb-2">ยังไม่มีหมวดหมู่</p>
                    <p class="text-gray-400 text-sm mb-6">เพิ่มหมวดหมู่เพื่อจัดระเบียบสินค้าใน POS</p>
                    <button onclick="openCreateModal()" class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-semibold rounded-lg hover:opacity-90 transition">
                        + เพิ่มหมวดหมู่แรก
                    </button>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($categories->hasPages())
        <div class="mt-6">
            {{ $categories->links() }}
        </div>
    @endif
</div>

<!-- Create/Edit Modal -->
<div id="categoryModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-2xl font-bold text-gray-900" id="modalTitle">เพิ่มหมวดหมู่ใหม่</h2>
        </div>
        <form id="categoryForm" method="POST" action="{{ route('seller.pos.categories.store') }}" class="p-6">
            @csrf
            <div id="methodField"></div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">ชื่อหมวดหมู่ <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">คำอธิบาย</label>
                    <textarea name="description" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">ไอคอน</label>
                        <input type="text" name="icon" placeholder="📦" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">สี</label>
                        <input type="color" name="color" value="#6366f1" class="w-full h-12 px-2 border border-gray-300 rounded-lg">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">ลำดับ</label>
                    <input type="number" name="order" value="0" min="0" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>

                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" checked class="w-5 h-5 text-purple-600 rounded">
                        <span class="text-sm font-semibold text-gray-700">เปิดใช้งาน</span>
                    </label>

                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="show_in_pos" value="1" checked class="w-5 h-5 text-purple-600 rounded">
                        <span class="text-sm font-semibold text-gray-700">แสดงใน POS</span>
                    </label>
                </div>
            </div>

            <div class="flex gap-3 mt-6 pt-6 border-t border-gray-200">
                <button type="button" onclick="closeModal()" class="flex-1 px-6 py-3 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition font-semibold">
                    ยกเลิก
                </button>
                <button type="submit" class="flex-1 px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg hover:opacity-90 transition font-semibold">
                    บันทึก
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'เพิ่มหมวดหมู่ใหม่';
    document.getElementById('categoryForm').action = '{{ route("seller.pos.categories.store") }}';
    document.getElementById('methodField').innerHTML = '';
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryModal').classList.remove('hidden');
}

function editCategory(id) {
    // Implement edit functionality
    alert('Edit category ' + id);
}

function closeModal() {
    document.getElementById('categoryModal').classList.add('hidden');
}
</script>
@endpush
@endsection
