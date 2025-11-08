@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">จัดการโรงแรม</h1>
        <a href="{{ route('admin.hotels.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> เพิ่มโรงแรมใหม่
        </a>
    </div>

    <!-- Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-primary">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">โรงแรมทั้งหมด</div>
                    <div class="h5 mb-0 font-weight-bold">{{ number_format($stats['total']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-success">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">เปิดใช้งาน</div>
                    <div class="h5 mb-0 font-weight-bold">{{ number_format($stats['active']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-warning">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">แนะนำ</div>
                    <div class="h5 mb-0 font-weight-bold">{{ number_format($stats['featured']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-info">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">ห้องทั้งหมด</div>
                    <div class="h5 mb-0 font-weight-bold">{{ number_format($stats['total_rooms']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.hotels.index') }}">
                <div class="row">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="ค้นหา..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <select name="type" class="form-control">
                            <option value="">ประเภททั้งหมด</option>
                            <option value="hotel" {{ request('type') == 'hotel' ? 'selected' : '' }}>โรงแรม</option>
                            <option value="resort" {{ request('type') == 'resort' ? 'selected' : '' }}>รีสอร์ท</option>
                            <option value="hostel" {{ request('type') == 'hostel' ? 'selected' : '' }}>โฮสเทล</option>
                            <option value="villa" {{ request('type') == 'villa' ? 'selected' : '' }}>วิลล่า</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-control">
                            <option value="">สถานะทั้งหมด</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>เปิดใช้งาน</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>ปิดใช้งาน</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-search"></i> ค้นหา
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Hotels Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>รูปภาพ</th>
                            <th>ชื่อโรงแรม</th>
                            <th>ที่ตั้ง</th>
                            <th>ประเภท</th>
                            <th>คะแนน</th>
                            <th>ห้องพัก</th>
                            <th>สถานะ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($hotels as $hotel)
                            <tr>
                                <td>
                                    <img src="{{ $hotel->main_image_url }}" alt="{{ $hotel->name }}"
                                         class="img-thumbnail" style="width: 80px; height: 60px; object-fit: cover;">
                                </td>
                                <td>
                                    <strong>{{ $hotel->name }}</strong>
                                    @if($hotel->is_featured)
                                        <span class="badge badge-warning">⭐ แนะนำ</span>
                                    @endif
                                    @if($hotel->star_rating)
                                        <br><small class="text-muted">
                                            @for($i = 0; $i < $hotel->star_rating; $i++) ⭐ @endfor
                                        </small>
                                    @endif
                                </td>
                                <td>{{ $hotel->city }}</td>
                                <td>
                                    <span class="badge badge-secondary">
                                        {{ ucfirst($hotel->type) }}
                                    </span>
                                </td>
                                <td>
                                    @if($hotel->rating > 0)
                                        <span class="badge badge-primary">{{ number_format($hotel->rating, 1) }}</span>
                                        <small class="text-muted">({{ $hotel->review_count }})</small>
                                    @else
                                        <small class="text-muted">ไม่มีรีวิว</small>
                                    @endif
                                </td>
                                <td>
                                    {{ $hotel->roomTypes->count() }} ประเภท
                                    <small class="text-muted">({{ $hotel->roomTypes->sum('total_rooms') }} ห้อง)</small>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="status{{ $hotel->id }}"
                                               {{ $hotel->is_active ? 'checked' : '' }}
                                               onchange="toggleStatus({{ $hotel->id }})">
                                        <label class="custom-control-label" for="status{{ $hotel->id }}">
                                            {{ $hotel->is_active ? 'เปิด' : 'ปิด' }}
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.hotels.show', $hotel->id) }}" class="btn btn-sm btn-info" title="ดู">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.hotels.edit', $hotel->id) }}" class="btn btn-sm btn-warning" title="แก้ไข">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="{{ route('admin.hotels.rooms.index', $hotel->id) }}" class="btn btn-sm btn-success" title="ห้องพัก">
                                            <i class="fas fa-door-open"></i>
                                        </a>
                                        <button onclick="deleteHotel({{ $hotel->id }})" class="btn btn-sm btn-danger" title="ลบ">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">ไม่พบข้อมูลโรงแรม</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-3">
                {{ $hotels->links() }}
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function toggleStatus(hotelId) {
    if (confirm('คุณต้องการเปลี่ยนสถานะโรงแรมนี้?')) {
        fetch(`/admin/hotels/${hotelId}/toggle-status`, {
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
        });
    } else {
        location.reload();
    }
}

function deleteHotel(hotelId) {
    if (confirm('คุณแน่ใจหรือว่าต้องการลบโรงแรมนี้? การกระทำนี้ไม่สามารถย้อนกลับได้')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/hotels/${hotelId}`;
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
