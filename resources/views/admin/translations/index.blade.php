@extends('layouts.admin')

@section('title', 'ตั้งค่าการแปลภาษา')

@section('content')
<div x-data="translationManager()" class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">ตั้งค่าการแปลภาษา</h1>
                <p class="mt-1 text-sm text-gray-600">
                    กำหนดคำแปลเฉพาะสำหรับคำหรือวลีที่ต้องการ (มีความสำคัญสูงกว่า Google Translate)
                </p>
            </div>
            <a href="{{ route('admin.translations.create') }}"
               class="px-6 py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                + เพิ่มคำแปล
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <form action="{{ route('admin.translations.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ค้นหา</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="ค้นหาคำหรือคำแปล..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <!-- Source Language -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ภาษาต้นทาง</label>
                <select name="source_lang" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">ทั้งหมด</option>
                    @foreach($languages as $lang)
                        <option value="{{ $lang->code }}" {{ request('source_lang') === $lang->code ? 'selected' : '' }}>
                            {{ $lang->flag_emoji }} {{ $lang->native_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Target Language -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ภาษาปลายทาง</label>
                <select name="target_lang" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">ทั้งหมด</option>
                    @foreach($languages as $lang)
                        <option value="{{ $lang->code }}" {{ request('target_lang') === $lang->code ? 'selected' : '' }}>
                            {{ $lang->flag_emoji }} {{ $lang->native_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Status -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">สถานะ</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">ทั้งหมด</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>เปิดใช้งาน</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>ปิดใช้งาน</option>
                </select>
            </div>

            <!-- Submit Buttons -->
            <div class="md:col-span-4 flex justify-end space-x-3">
                <a href="{{ route('admin.translations.index') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    ล้างตัวกรอง
                </a>
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                    ค้นหา
                </button>
            </div>
        </form>
    </div>

    <!-- Translations List -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        @if($mappings->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">คำต้นทาง</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ภาษา</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">คำแปล</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">บริบท</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ลำดับ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($mappings as $mapping)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $mapping->key }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-600">
                                        {{ $mapping->source_lang }} → {{ $mapping->target_lang }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">{{ $mapping->translation }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-500 max-w-xs truncate" title="{{ $mapping->context }}">
                                        {{ $mapping->context ?: '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">
                                        {{ $mapping->priority }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <button @click="toggleStatus({{ $mapping->id }}, {{ $mapping->is_active ? 'true' : 'false' }})"
                                            class="px-3 py-1 text-xs font-semibold rounded-full {{ $mapping->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }} hover:opacity-75 transition-opacity">
                                        {{ $mapping->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
                                    </button>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('admin.translations.edit', $mapping) }}"
                                           class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                            แก้ไข
                                        </a>
                                        <form action="{{ route('admin.translations.destroy', $mapping) }}" method="POST"
                                              onsubmit="return confirm('ต้องการลบคำแปลนี้?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 text-sm font-medium">
                                                ลบ
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $mappings->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">ไม่พบคำแปล</h3>
                <p class="mt-1 text-sm text-gray-500">เริ่มต้นด้วยการเพิ่มคำแปลแรกของคุณ</p>
                <div class="mt-6">
                    <a href="{{ route('admin.translations.create') }}"
                       class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                        + เพิ่มคำแปล
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function translationManager() {
    return {
        async toggleStatus(id, currentStatus) {
            try {
                const response = await fetch(`/admin/translations/${id}/toggle`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    window.location.reload();
                } else {
                    alert('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาด: ' + error.message);
            }
        }
    }
}
</script>
@endpush
@endsection
