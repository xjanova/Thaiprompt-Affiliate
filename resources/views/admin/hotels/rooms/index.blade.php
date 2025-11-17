@extends('layouts.admin-v3')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 p-6">
    <!-- Gradient Header -->
    <div class="relative overflow-hidden bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-600 rounded-3xl shadow-2xl p-8 mb-8">
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="relative flex items-center justify-between">
            <div>
                <h1 class="text-4xl font-bold text-white mb-2" data-translate>จัดการห้องพัก</h1>
                <p class="text-blue-100">{{ $hotel->name }}</p>
                <p class="text-blue-200 text-sm">{{ $hotel->city }}</p>
            </div>

            <div class="flex space-x-3">
                <a href="{{ route('admin.hotels.index') }}"
                   class="px-6 py-3 bg-white/20 backdrop-blur-sm hover:bg-white/30 rounded-xl transition text-white font-semibold flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span data-translate>กลับ</span>
                </a>
                <a href="{{ route('admin.hotels.rooms.create', $hotel->id) }}"
                   class="px-6 py-3 bg-white/20 backdrop-blur-sm hover:bg-white/30 rounded-xl transition text-white font-semibold flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span data-translate>เพิ่มห้องพัก</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Rooms Table -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-700 dark:to-gray-600">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>รูปภาพ</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>ชื่อห้อง</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>ขนาด</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>ผู้เข้าพัก</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>ราคา</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>จำนวน</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>สถานะ</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>จัดการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($roomTypes as $room)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <td class="px-6 py-4">
                            @if($room->main_image_url)
                            <img src="{{ $room->main_image_url }}" alt="{{ $room->name }}" class="w-20 h-16 object-cover rounded-lg shadow">
                            @else
                            <div class="w-20 h-16 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                </svg>
                            </div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div>
                                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $room->name }}</p>
                                @if($room->is_popular)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300 mt-1">
                                    ⭐ <span data-translate>ยอดนิยม</span>
                                </span>
                                @endif
                                @if($room->view_type)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">วิว: {{ ucfirst($room->view_type) }}</p>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                            @if($room->size_sqm)
                            {{ $room->size_sqm }} ตร.ม.
                            @else
                            -
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm text-gray-700 dark:text-gray-300">{{ $room->max_adults }} <span data-translate>ผู้ใหญ่</span></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $room->max_children }} <span data-translate>เด็ก</span></p>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm font-bold text-blue-600 dark:text-blue-400">฿{{ number_format($room->base_price) }}</p>
                            @if($room->weekend_price)
                            <p class="text-xs text-gray-500 dark:text-gray-400">วันหยุด: ฿{{ number_format($room->weekend_price) }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300">
                                {{ $room->total_rooms }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only peer" {{ $room->is_active ? 'checked' : '' }} onchange="toggleStatus({{ $room->id }})">
                                <div class="w-11 h-6 bg-gray-300 peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                            </label>
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <a href="{{ route('admin.hotels.rooms.show', [$hotel->id, $room->id]) }}" class="inline-flex items-center px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-xs font-semibold transition" title="ดู">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                            <a href="{{ route('admin.hotels.rooms.edit', [$hotel->id, $room->id]) }}" class="inline-flex items-center px-3 py-1 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-xs font-semibold transition" title="แก้ไข">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                            <a href="{{ route('admin.hotels.rooms.availability', [$hotel->id, $room->id]) }}" class="inline-flex items-center px-3 py-1 bg-green-500 hover:bg-green-600 text-white rounded-lg text-xs font-semibold transition" title="ปฏิทิน">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </a>
                            <button onclick="deleteRoom({{ $room->id }})" class="inline-flex items-center px-3 py-1 bg-red-500 hover:bg-red-600 text-white rounded-lg text-xs font-semibold transition" title="ลบ">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400" data-translate>ยังไม่มีห้องพัก กรุณาเพิ่มห้องพัก</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($roomTypes->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $roomTypes->links() }}
        </div>
        @endif
    </div>
</div>

<script>
/**
 * สลับสถานะห้องพัก
 */
function toggleStatus(roomId) {
    if (confirm('คุณต้องการเปลี่ยนสถานะห้องพักนี้?')) {
        fetch(`/admin/hotels/{{ $hotel->id }}/rooms/${roomId}/toggle-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('เปลี่ยนสถานะสำเร็จ');
            } else {
                location.reload();
            }
        })
        .catch(() => location.reload());
    } else {
        location.reload();
    }
}

/**
 * ลบห้องพัก
 */
function deleteRoom(roomId) {
    if (confirm('คุณแน่ใจหรือว่าต้องการลบห้องพักนี้? การกระทำนี้ไม่สามารถย้อนกลับได้')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/hotels/{{ $hotel->id }}/rooms/${roomId}`;
        form.innerHTML = `
            @csrf
            @method('DELETE')
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endsection
