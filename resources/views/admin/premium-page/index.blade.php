@extends('layouts.admin')

@section('title', 'จัดการหน้าแรก Premium')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">จัดการหน้าแรก Premium</h1>
            <p class="text-sm text-gray-600 mt-1">ปรับแต่งเนื้อหาและการแสดงผลของแต่ละส่วนในหน้าแรก</p>
        </div>
    </div>

    <!-- Sections Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($sections as $key => $section)
            @php
                $enabled = \App\Models\Setting::get("premium_page_{$key}_enabled", true);
            @endphp
            <div class="bg-white rounded-lg shadow-md hover:shadow-xl transition-shadow">
                <!-- Section Header -->
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <span class="text-3xl">{{ $section['icon'] }}</span>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">{{ $section['name'] }}</h3>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <!-- Toggle Button -->
                            <button onclick="toggleSection('{{ $key }}')"
                                    class="px-3 py-1 rounded-lg text-sm font-medium transition
                                           {{ $enabled ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $enabled ? 'เปิด' : 'ปิด' }}
                            </button>
                        </div>
                    </div>

                    <!-- Status Badge -->
                    @if($enabled)
                        <span class="inline-flex items-center px-2 py-1 bg-green-50 text-green-700 rounded-full text-xs">
                            <span class="w-2 h-2 bg-green-500 rounded-full mr-1"></span>
                            กำลังแสดงผล
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-1 bg-gray-50 text-gray-700 rounded-full text-xs">
                            <span class="w-2 h-2 bg-gray-400 rounded-full mr-1"></span>
                            ปิดการแสดงผล
                        </span>
                    @endif
                </div>

                <!-- Section Content Preview -->
                <div class="p-6">
                    @php
                        $title = \App\Models\Setting::get("premium_page_{$key}_title");
                        $subtitle = \App\Models\Setting::get("premium_page_{$key}_subtitle");
                    @endphp

                    @if($title)
                        <p class="text-sm text-gray-900 font-medium mb-2">{{ Str::limit($title, 50) }}</p>
                    @endif
                    @if($subtitle)
                        <p class="text-xs text-gray-600 mb-4">{{ Str::limit($subtitle, 80) }}</p>
                    @endif

                    <!-- Edit Button -->
                    <a href="{{ route('admin.premium-page.edit', $key) }}"
                       class="inline-flex items-center justify-center w-full px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        แก้ไขเนื้อหา
                    </a>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Help Text -->
    <div class="bg-blue-50 rounded-lg p-6">
        <div class="flex gap-3">
            <div class="text-2xl">💡</div>
            <div>
                <h4 class="text-blue-900 font-semibold mb-2">คำแนะนำการใช้งาน</h4>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li>• คลิก "แก้ไขเนื้อหา" เพื่อปรับแต่งข้อความและการแสดงผลของแต่ละส่วน</li>
                    <li>• ใช้ปุ่ม "เปิด/ปิด" เพื่อควบคุมการแสดงผลของแต่ละส่วนโดยไม่ต้องลบข้อมูล</li>
                    <li>• การเปลี่ยนแปลงจะมีผลทันทีที่บันทึก ไม่ต้องรีเฟรชเว็บไซต์</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function toggleSection(section) {
    fetch(`/admin/premium-page/${section}/toggle`, {
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
