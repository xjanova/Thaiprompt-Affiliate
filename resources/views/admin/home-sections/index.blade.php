@extends('layouts.admin')

@section('title', 'จัดการ Home Sections')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">จัดการ Home Sections</h1>
            <p class="text-sm text-gray-600 mt-1">จัดการ sections ที่แสดงในหน้าแรก พร้อมลากวางเรียงลำดับ</p>
        </div>
        <a href="{{ route('admin.home-sections.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
            + สร้าง Section ใหม่
        </a>
    </div>

    <!-- Sections List (Sortable) -->
    <div class="bg-white rounded-lg shadow-md p-6" id="sections-container">
        @if($sections->count() > 0)
            <div id="sortable-sections" class="space-y-4">
                @foreach($sections as $section)
                    <div class="section-item bg-gray-50 rounded-lg p-4 border-2 border-gray-200 hover:border-indigo-300 transition cursor-move"
                         data-id="{{ $section->id }}"
                         data-order="{{ $section->sort_order }}">
                        <div class="flex items-center justify-between">
                            <!-- Drag Handle & Info -->
                            <div class="flex items-center gap-4 flex-1">
                                <div class="text-2xl cursor-grab active:cursor-grabbing">
                                    ⋮⋮
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-1">
                                        <h3 class="text-lg font-semibold text-gray-900">{{ $section->name }}</h3>
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-medium">
                                            {{ $types[$section->type] ?? $section->type }}
                                        </span>
                                        @if($section->is_active)
                                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">ใช้งาน</span>
                                        @else
                                            <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-medium">ปิด</span>
                                        @endif
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        <p class="font-medium">{{ $section->title }}</p>
                                        @if($section->subtitle)
                                            <p class="text-gray-500">{{ $section->subtitle }}</p>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                                        <span>ลำดับ: {{ $section->sort_order }}</span>
                                        @if($section->allowed_roles)
                                            <span>สิทธิ์: {{ implode(', ', $section->allowed_roles) }}</span>
                                        @else
                                            <span>สิทธิ์: ทุกคน</span>
                                        @endif
                                        @if($section->schedule_start || $section->schedule_end)
                                            <span>⏰ มีกำหนดเวลา</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2">
                                <!-- Toggle Active -->
                                <button onclick="toggleSection({{ $section->id }})"
                                        class="px-3 py-1 rounded-lg text-sm font-medium transition
                                               {{ $section->is_active ? 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200' : 'bg-green-100 text-green-800 hover:bg-green-200' }}">
                                    {{ $section->is_active ? 'ปิด' : 'เปิด' }}
                                </button>

                                <a href="{{ route('admin.home-sections.edit', $section) }}"
                                   class="text-blue-600 hover:text-blue-900 text-2xl" title="แก้ไข">
                                    ✏️
                                </a>

                                <form action="{{ route('admin.home-sections.destroy', $section) }}" method="POST" class="inline"
                                      onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบ section นี้?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 text-2xl" title="ลบ">🗑️</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                <p class="text-sm text-blue-800">
                    💡 <strong>คำแนะนำ:</strong> ลากและวางเพื่อเรียงลำดับ sections จะถูกบันทึกอัตโนมัติ
                </p>
            </div>
        @else
            <div class="text-center py-12">
                <div class="text-6xl mb-4">📦</div>
                <p class="text-gray-600 text-lg">ยังไม่มี Section</p>
                <a href="{{ route('admin.home-sections.create') }}" class="inline-block mt-4 text-indigo-600 hover:text-indigo-900">
                    สร้าง Section แรก →
                </a>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
// Initialize Sortable
const el = document.getElementById('sortable-sections');
if (el) {
    const sortable = Sortable.create(el, {
        animation: 150,
        handle: '.section-item',
        ghostClass: 'bg-indigo-100',
        onEnd: function (evt) {
            // Get new order
            const items = el.querySelectorAll('.section-item');
            const sections = [];

            items.forEach((item, index) => {
                sections.push({
                    id: parseInt(item.dataset.id),
                    sort_order: index
                });
            });

            // Save new order
            fetch('{{ route('admin.home-sections.reorder') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ sections })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update visual order numbers
                    items.forEach((item, index) => {
                        const orderSpan = item.querySelector('.text-xs.text-gray-500 span:first-child');
                        if (orderSpan) {
                            orderSpan.textContent = `ลำดับ: ${index}`;
                        }
                    });
                }
            })
            .catch(error => console.error('Error:', error));
        }
    });
}

// Toggle section active status
function toggleSection(id) {
    fetch(`/admin/home-sections/${id}/toggle`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
}
</script>
@endpush
@endsection
