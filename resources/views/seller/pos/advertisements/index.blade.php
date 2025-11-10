@extends('layouts.seller')

@section('title', 'โฆษณา POS')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-pink-600 via-rose-600 to-red-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white opacity-10 rounded-full"></div>
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold mb-2">📺 โฆษณา POS</h1>
                    <p class="text-pink-100">จัดการโฆษณาแสดงในหน้าจอ POS</p>
                </div>
                <button onclick="openCreateModal()" class="px-6 py-3 bg-white text-pink-600 rounded-lg hover:bg-pink-50 transition font-semibold">
                    + เพิ่มโฆษณา
                </button>
            </div>
        </div>
    </div>

    <!-- Advertisements Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($advertisements as $ad)
            <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition overflow-hidden">
                @if($ad->image_path)
                    <div class="h-48 bg-gradient-to-br from-pink-100 to-purple-100 overflow-hidden">
                        <img src="{{ Storage::url($ad->image_path) }}" alt="{{ $ad->title }}" class="w-full h-full object-cover">
                    </div>
                @else
                    <div class="h-48 bg-gradient-to-br from-pink-100 to-purple-100 flex items-center justify-center">
                        <span class="text-6xl">📺</span>
                    </div>
                @endif

                <div class="p-6">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-gray-900 mb-1">{{ $ad->title }}</h3>
                            <p class="text-sm text-gray-600">{{ $ad->description }}</p>
                        </div>
                        <div class="flex gap-2 ml-2">
                            <button onclick="editAd({{ $ad->id }})" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition">
                                ✏️
                            </button>
                            <form action="{{ route('seller.pos.advertisements.destroy', $ad->id) }}" method="POST" class="inline" onsubmit="return confirm('ต้องการลบโฆษณานี้?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                                    🗑️
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 mb-3">
                        @if($ad->type === 'image')
                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-semibold">รูปภาพ</span>
                        @elseif($ad->type === 'video')
                            <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-xs font-semibold">วิดีโอ</span>
                        @elseif($ad->type === 'promotion')
                            <span class="px-3 py-1 bg-orange-100 text-orange-800 rounded-full text-xs font-semibold">โปรโมชั่น</span>
                        @else
                            <span class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-semibold">{{ $ad->type }}</span>
                        @endif

                        @if($ad->is_active)
                            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">เปิดใช้งาน</span>
                        @else
                            <span class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-semibold">ปิดใช้งาน</span>
                        @endif
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                        <span class="text-sm text-gray-500">⏱️ {{ $ad->duration_seconds }}s</span>
                        <span class="text-sm text-gray-500">#{{ $ad->order ?? 0 }}</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="bg-white rounded-2xl shadow-xl p-12 text-center">
                    <span class="text-6xl block mb-4">📺</span>
                    <p class="text-gray-500 text-lg font-medium mb-2">ยังไม่มีโฆษณา</p>
                    <p class="text-gray-400 text-sm mb-6">เพิ่มโฆษณาเพื่อแสดงในหน้าจอ POS ของคุณ</p>
                    <button onclick="openCreateModal()" class="px-6 py-3 bg-gradient-to-r from-pink-600 to-red-600 text-white font-semibold rounded-lg hover:opacity-90 transition">
                        + เพิ่มโฆษณาแรก
                    </button>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($advertisements->hasPages())
        <div class="mt-6">
            {{ $advertisements->links() }}
        </div>
    @endif
</div>

<!-- Create/Edit Modal -->
<div id="adModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-2xl font-bold text-gray-900" id="modalTitle">เพิ่มโฆษณาใหม่</h2>
        </div>
        <form id="adForm" method="POST" action="{{ route('seller.pos.advertisements.store') }}" enctype="multipart/form-data" class="p-6">
            @csrf
            <div id="methodField"></div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">ชื่อโฆษณา <span class="text-red-500">*</span></label>
                    <input type="text" name="title" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">คำอธิบาย</label>
                    <textarea name="description" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">ประเภท <span class="text-red-500">*</span></label>
                    <select name="type" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500">
                        <option value="image">รูปภาพ</option>
                        <option value="video">วิดีโอ</option>
                        <option value="promotion">โปรโมชั่น</option>
                        <option value="html">HTML</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">รูปภาพ</label>
                    <input type="file" name="image" accept="image/*" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500">
                    <p class="text-xs text-gray-500 mt-1">รองรับไฟล์ภาพขนาดไม่เกิน 5MB</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">ระยะเวลา (วินาที) <span class="text-red-500">*</span></label>
                        <input type="number" name="duration_seconds" value="10" min="1" max="300" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">ลำดับ</label>
                        <input type="number" name="order" value="0" min="0" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500">
                    </div>
                </div>

                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" checked class="w-5 h-5 text-pink-600 rounded">
                        <span class="text-sm font-semibold text-gray-700">เปิดใช้งาน</span>
                    </label>
                </div>
            </div>

            <div class="flex gap-3 mt-6 pt-6 border-t border-gray-200">
                <button type="button" onclick="closeModal()" class="flex-1 px-6 py-3 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition font-semibold">
                    ยกเลิก
                </button>
                <button type="submit" class="flex-1 px-6 py-3 bg-gradient-to-r from-pink-600 to-red-600 text-white rounded-lg hover:opacity-90 transition font-semibold">
                    บันทึก
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'เพิ่มโฆษณาใหม่';
    document.getElementById('adForm').action = '{{ route("seller.pos.advertisements.store") }}';
    document.getElementById('methodField').innerHTML = '';
    document.getElementById('adForm').reset();
    document.getElementById('adModal').classList.remove('hidden');
}

function editAd(id) {
    // Implement edit functionality
    alert('Edit advertisement ' + id);
}

function closeModal() {
    document.getElementById('adModal').classList.add('hidden');
}
</script>
@endpush
@endsection
