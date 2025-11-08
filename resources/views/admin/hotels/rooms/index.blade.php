@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">จัดการห้องพัก: {{ $hotel->name }}</h1>
            <p class="text-muted mb-0">{{ $hotel->city }}</p>
        </div>
        <div>
            <a href="{{ route('admin.hotels.index') }}" class="btn btn-secondary mr-2">
                <i class="fas fa-arrow-left"></i> กลับ
            </a>
            <a href="{{ route('admin.hotels.rooms.create', $hotel->id) }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> เพิ่มห้องพัก
            </a>
        </div>
    </div>

    <!-- Room Types -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>รูปภาพ</th>
                            <th>ชื่อห้อง</th>
                            <th>ขนาด</th>
                            <th>ผู้เข้าพัก</th>
                            <th>ราคา</th>
                            <th>จำนวนห้อง</th>
                            <th>สถานะ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($roomTypes as $room)
                            <tr>
                                <td>
                                    <img src="{{ $room->main_image_url }}" alt="{{ $room->name }}"
                                         class="img-thumbnail" style="width: 80px; height: 60px; object-fit: cover;">
                                </td>
                                <td>
                                    <strong>{{ $room->name }}</strong>
                                    @if($room->is_popular)
                                        <span class="badge badge-warning">⭐ ยอดนิยม</span>
                                    @endif
                                    @if($room->view_type)
                                        <br><small class="text-muted">วิว: {{ ucfirst($room->view_type) }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($room->size_sqm)
                                        {{ $room->size_sqm }} ตร.ม.
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    {{ $room->max_adults }} ผู้ใหญ่<br>
                                    <small class="text-muted">{{ $room->max_children }} เด็ก</small>
                                </td>
                                <td>
                                    <strong class="text-primary">฿{{ number_format($room->base_price) }}</strong>
                                    @if($room->weekend_price)
                                        <br><small class="text-muted">วันหยุด: ฿{{ number_format($room->weekend_price) }}</small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <strong>{{ $room->total_rooms }}</strong> ห้อง
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="status{{ $room->id }}"
                                               {{ $room->is_active ? 'checked' : '' }}
                                               onchange="toggleStatus({{ $room->id }})">
                                        <label class="custom-control-label" for="status{{ $room->id }}">
                                            {{ $room->is_active ? 'เปิด' : 'ปิด' }}
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.hotels.rooms.show', [$hotel->id, $room->id]) }}"
                                           class="btn btn-sm btn-info" title="ดู">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.hotels.rooms.edit', [$hotel->id, $room->id]) }}"
                                           class="btn btn-sm btn-warning" title="แก้ไข">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="{{ route('admin.hotels.rooms.availability', [$hotel->id, $room->id]) }}"
                                           class="btn btn-sm btn-success" title="ปฏิทิน">
                                            <i class="fas fa-calendar"></i>
                                        </a>
                                        <button onclick="deleteRoom({{ $room->id }})"
                                                class="btn btn-sm btn-danger" title="ลบ">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">ยังไม่มีห้องพัก กรุณาเพิ่มห้องพัก</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($roomTypes->hasPages())
                <div class="mt-3">
                    {{ $roomTypes->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function toggleStatus(roomId) {
    if (confirm('คุณต้องการเปลี่ยนสถานะห้องพักนี้?')) {
        // TODO: Implement AJAX toggle
        location.reload();
    } else {
        location.reload();
    }
}

function deleteRoom(roomId) {
    if (confirm('คุณแน่ใจหรือว่าต้องการลบห้องพักนี้?')) {
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
@endpush
@endsection
