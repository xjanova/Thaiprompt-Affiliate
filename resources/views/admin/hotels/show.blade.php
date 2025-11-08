@extends('layouts.admin')

@section('title', 'รายละเอียดโรงแรม - ' . $hotel->name)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-2">{{ $hotel->name }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.hotels.index') }}">โรงแรม</a></li>
                    <li class="breadcrumb-item active">{{ $hotel->name }}</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.hotels.edit', $hotel->id) }}" class="btn btn-primary">
                <i class="fas fa-edit"></i> แก้ไข
            </a>
            <a href="{{ route('hotels.show', $hotel->slug) }}" target="_blank" class="btn btn-info">
                <i class="fas fa-external-link-alt"></i> ดูหน้าเว็บ
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Hotel Images -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-images"></i> รูปภาพ</h5>
                </div>
                <div class="card-body">
                    @if($hotel->main_image)
                        <div class="mb-3">
                            <label class="d-block mb-2"><strong>รูปหลัก:</strong></label>
                            <img src="{{ $hotel->main_image_url }}" alt="{{ $hotel->name }}" class="img-fluid rounded" style="max-height: 400px;">
                        </div>
                    @endif

                    @if($hotel->gallery_images && count($hotel->gallery_images) > 0)
                        <div>
                            <label class="d-block mb-2"><strong>แกลเลอรี่ ({{ count($hotel->gallery_images) }} รูป):</strong></label>
                            <div class="row g-2">
                                @foreach($hotel->gallery_images as $image)
                                    <div class="col-md-3 col-sm-4 col-6">
                                        <img src="{{ Storage::url($image) }}" alt="Gallery" class="img-fluid rounded">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Description -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-align-left"></i> รายละเอียด</h5>
                </div>
                <div class="card-body">
                    {!! nl2br(e($hotel->description)) !!}
                </div>
            </div>

            <!-- Room Types -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-bed"></i> ห้องพัก ({{ $hotel->roomTypes->count() }} ประเภท)</h5>
                    <a href="{{ route('admin.hotels.rooms.create', $hotel->id) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> เพิ่มห้องพัก
                    </a>
                </div>
                <div class="card-body p-0">
                    @if($hotel->roomTypes->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>รูปภาพ</th>
                                        <th>ชื่อห้อง</th>
                                        <th>ราคา</th>
                                        <th>จำนวนห้อง</th>
                                        <th>ผู้เข้าพัก</th>
                                        <th>สถานะ</th>
                                        <th>การจัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($hotel->roomTypes as $room)
                                        <tr>
                                            <td>
                                                @if($room->main_image)
                                                    <img src="{{ $room->main_image_url }}" alt="{{ $room->name }}"
                                                         class="img-thumbnail" style="width: 80px; height: 60px; object-fit: cover;">
                                                @else
                                                    <div class="bg-light text-center" style="width: 80px; height: 60px; line-height: 60px;">
                                                        <i class="fas fa-bed text-muted"></i>
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                <strong>{{ $room->name }}</strong>
                                                <br><small class="text-muted">{{ $room->size_sqm }} ตร.ม.</small>
                                            </td>
                                            <td>
                                                <strong class="text-primary">฿{{ number_format($room->base_price) }}</strong>
                                                @if($room->weekend_price)
                                                    <br><small class="text-muted">วันหยุด: ฿{{ number_format($room->weekend_price) }}</small>
                                                @endif
                                            </td>
                                            <td>{{ $room->total_rooms }} ห้อง</td>
                                            <td>
                                                {{ $room->max_adults }} ผู้ใหญ่
                                                @if($room->max_children > 0)
                                                    <br><small class="text-muted">{{ $room->max_children }} เด็ก</small>
                                                @endif
                                            </td>
                                            <td>
                                                @if($room->is_active)
                                                    <span class="badge badge-success">เปิดใช้งาน</span>
                                                @else
                                                    <span class="badge badge-secondary">ปิดใช้งาน</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.hotels.rooms.show', [$hotel->id, $room->id]) }}"
                                                   class="btn btn-sm btn-info" title="ดู">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.hotels.rooms.edit', [$hotel->id, $room->id]) }}"
                                                   class="btn btn-sm btn-primary" title="แก้ไข">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="{{ route('admin.hotels.rooms.availability', [$hotel->id, $room->id]) }}"
                                                   class="btn btn-sm btn-success" title="ปฏิทิน">
                                                    <i class="fas fa-calendar"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-bed fa-3x mb-3"></i>
                            <p>ยังไม่มีห้องพัก</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Facilities -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-concierge-bell"></i> สิ่งอำนวยความสะดวก</h5>
                </div>
                <div class="card-body">
                    @if($hotel->facilities->count() > 0)
                        <div class="row">
                            @foreach($hotel->facilities as $facility)
                                <div class="col-md-4 col-sm-6 mb-2">
                                    <i class="fas fa-{{ $facility->icon }} text-primary"></i>
                                    {{ $facility->name }}
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted mb-0">ยังไม่ได้เพิ่มสิ่งอำนวยความสะดวก</p>
                    @endif
                </div>
            </div>

            <!-- Reviews -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-star text-warning"></i> รีวิว
                        ({{ $hotel->reviews->count() }} รีวิว, คะแนนเฉลี่ย {{ number_format($hotel->rating, 1) }}/5.0)
                    </h5>
                    <a href="{{ route('admin.hotels.reviews.index', ['hotel_id' => $hotel->id]) }}" class="btn btn-sm btn-primary">
                        ดูทั้งหมด
                    </a>
                </div>
                <div class="card-body">
                    @if($hotel->reviews->take(5)->count() > 0)
                        @foreach($hotel->reviews->take(5) as $review)
                            <div class="mb-3 pb-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong>{{ $review->user->name }}</strong>
                                        <div class="text-warning">
                                            @for($i = 1; $i <= 5; $i++)
                                                @if($i <= $review->overall_rating)
                                                    <i class="fas fa-star"></i>
                                                @else
                                                    <i class="far fa-star"></i>
                                                @endif
                                            @endfor
                                            <span class="text-dark ml-1">{{ number_format($review->overall_rating, 1) }}</span>
                                        </div>
                                    </div>
                                    <small class="text-muted">{{ $review->created_at->diffForHumans() }}</small>
                                </div>
                                <p class="mb-1 mt-2">{{ Str::limit($review->content, 200) }}</p>
                                @if(!$review->is_approved)
                                    <span class="badge badge-warning">รออนุมัติ</span>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted mb-0">ยังไม่มีรีวิว</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Status & Settings -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-cog"></i> สถานะและการตั้งค่า</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="d-block mb-2"><strong>สถานะ:</strong></label>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="statusSwitch"
                                   {{ $hotel->is_active ? 'checked' : '' }}
                                   onchange="toggleStatus({{ $hotel->id }})">
                            <label class="custom-control-label" for="statusSwitch">
                                <span id="statusLabel">{{ $hotel->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}</span>
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="d-block mb-2"><strong>แนะนำพิเศษ:</strong></label>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="featuredSwitch"
                                   {{ $hotel->is_featured ? 'checked' : '' }}
                                   onchange="toggleFeatured({{ $hotel->id }})">
                            <label class="custom-control-label" for="featuredSwitch">
                                {{ $hotel->is_featured ? 'แนะนำแล้ว' : 'ไม่แนะนำ' }}
                            </label>
                        </div>
                    </div>

                    <hr>

                    <div class="mb-2">
                        <strong>ประเภท:</strong>
                        <span class="badge badge-info">{{ ucfirst($hotel->type) }}</span>
                    </div>

                    <div class="mb-2">
                        <strong>ระดับดาว:</strong>
                        <span class="text-warning">
                            @for($i = 1; $i <= $hotel->star_rating; $i++)
                                <i class="fas fa-star"></i>
                            @endfor
                        </span>
                    </div>

                    <div class="mb-2">
                        <strong>อัตราค่าคอมมิชชั่น:</strong>
                        <span class="badge badge-success">{{ $hotel->commission_rate }}%</span>
                    </div>

                    @if($hotel->owner)
                        <div class="mb-2">
                            <strong>เจ้าของ:</strong>
                            <a href="{{ route('admin.users.show', $hotel->owner_id) }}">{{ $hotel->owner->name }}</a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Location -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-map-marker-alt"></i> ที่อยู่</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2">{{ $hotel->address }}</p>
                    <p class="mb-2">
                        {{ $hotel->district }}{{ $hotel->district && $hotel->city ? ', ' : '' }}{{ $hotel->city }}
                    </p>
                    <p class="mb-2">{{ $hotel->province }} {{ $hotel->postal_code }}</p>
                    <p class="mb-0">{{ $hotel->country }}</p>

                    @if($hotel->latitude && $hotel->longitude)
                        <hr>
                        <small class="text-muted">
                            <i class="fas fa-globe"></i>
                            {{ $hotel->latitude }}, {{ $hotel->longitude }}
                        </small>
                    @endif
                </div>
            </div>

            <!-- Contact -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-phone"></i> ติดต่อ</h5>
                </div>
                <div class="card-body">
                    @if($hotel->phone)
                        <p class="mb-2">
                            <i class="fas fa-phone text-primary"></i> {{ $hotel->phone }}
                        </p>
                    @endif
                    @if($hotel->email)
                        <p class="mb-2">
                            <i class="fas fa-envelope text-primary"></i> {{ $hotel->email }}
                        </p>
                    @endif
                    @if($hotel->website)
                        <p class="mb-0">
                            <i class="fas fa-globe text-primary"></i>
                            <a href="{{ $hotel->website }}" target="_blank">{{ $hotel->website }}</a>
                        </p>
                    @endif
                </div>
            </div>

            <!-- Policies -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> นโยบาย</h5>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>เช็คอิน:</strong> {{ $hotel->check_in_time }}
                    </div>
                    <div class="mb-2">
                        <strong>เช็คเอาท์:</strong> {{ $hotel->check_out_time }}
                    </div>
                    @if($hotel->cancellation_policy)
                        <hr>
                        <div class="mb-2">
                            <strong>นโยบายการยกเลิก:</strong>
                            <p class="mb-0 mt-1">{{ $hotel->cancellation_policy }}</p>
                        </div>
                    @endif
                    @if($hotel->house_rules)
                        <hr>
                        <div>
                            <strong>กฎของที่พัก:</strong>
                            <p class="mb-0 mt-1">{{ $hotel->house_rules }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Statistics -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> สถิติ</h5>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>จำนวนการดู:</strong>
                        {{ number_format($hotel->views_count ?? 0) }} ครั้ง
                    </div>
                    <div class="mb-2">
                        <strong>จำนวนการจอง:</strong>
                        {{ number_format($hotel->bookings->count()) }} ครั้ง
                    </div>
                    <div class="mb-2">
                        <strong>รายได้รวม:</strong>
                        ฿{{ number_format($hotel->bookings->where('status', 'completed')->sum('total_amount')) }}
                    </div>
                    <div>
                        <strong>สร้างเมื่อ:</strong>
                        {{ $hotel->created_at->format('d/m/Y H:i') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleStatus(hotelId) {
    if (!confirm('คุณต้องการเปลี่ยนสถานะโรงแรมนี้หรือไม่?')) {
        location.reload();
        return;
    }

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
            document.getElementById('statusLabel').textContent = data.is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
            alert('เปลี่ยนสถานะสำเร็จ');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาด');
        location.reload();
    });
}

function toggleFeatured(hotelId) {
    fetch(`/admin/hotels/${hotelId}/toggle-featured`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('เปลี่ยนการแนะนำสำเร็จ');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาด');
        location.reload();
    });
}
</script>
@endsection
