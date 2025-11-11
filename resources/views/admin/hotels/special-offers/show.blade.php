@extends('layouts.admin')

@section('title', 'รายละเอียดโปรโมชั่น - ' . $offer->name)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-2">{{ $offer->name }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.hotels.special-offers.index') }}">โปรโมชั่นพิเศษ</a></li>
                    <li class="breadcrumb-item active">{{ $offer->name }}</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.hotels.special-offers.edit', $offer->id) }}" class="btn btn-primary">
                <i class="fas fa-edit"></i> แก้ไข
            </a>
            <form action="{{ route('admin.hotels.special-offers.destroy', $offer->id) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบโปรโมชั่นนี้?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> ลบ
                </button>
            </form>
        </div>
    </div>

    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Banner -->
            @if($offer->banner_image)
                <div class="card mb-4">
                    <div class="card-body p-0">
                        <img src="{{ Storage::url($offer->banner_image) }}" alt="{{ $offer->name }}"
                             class="img-fluid w-100" style="max-height: 300px; object-fit: cover;">
                    </div>
                </div>
            @endif

            <!-- Basic Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> ข้อมูลพื้นฐาน</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="d-block mb-1"><strong>ชื่อโปรโมชั่น:</strong></label>
                            <p class="mb-0">{{ $offer->name }}</p>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="d-block mb-1"><strong>โค้ดส่วนลด:</strong></label>
                            <div class="d-flex align-items-center">
                                <code class="bg-light px-3 py-2 rounded" id="offerCode">{{ $offer->code }}</code>
                                <button type="button" class="btn btn-sm btn-outline-primary ml-2" onclick="copyCode()">
                                    <i class="fas fa-copy"></i> คัดลอก
                                </button>
                            </div>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="d-block mb-1"><strong>โรงแรม:</strong></label>
                            <a href="{{ route('admin.hotels.show', $offer->hotel_id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-hotel"></i> {{ $offer->hotel->name }}
                            </a>
                        </div>

                        @if($offer->description)
                            <div class="col-md-12">
                                <label class="d-block mb-1"><strong>คำอธิบาย:</strong></label>
                                <p class="mb-0">{{ $offer->description }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Discount Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-percent"></i> รายละเอียดส่วนลด</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="d-block mb-1"><strong>ประเภทส่วนลด:</strong></label>
                            <p class="mb-0">
                                @if($offer->discount_type == 'percentage')
                                    <span class="badge badge-primary">เปอร์เซ็นต์</span>
                                @elseif($offer->discount_type == 'fixed')
                                    <span class="badge badge-success">จำนวนเงินคงที่</span>
                                @else
                                    <span class="badge badge-info">ฟรีจำนวนคืน</span>
                                @endif
                            </p>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="d-block mb-1"><strong>มูลค่าส่วนลด:</strong></label>
                            <p class="mb-0 text-success font-weight-bold h4">
                                @if($offer->discount_type == 'percentage')
                                    {{ $offer->discount_value }}%
                                @elseif($offer->discount_type == 'fixed')
                                    ฿{{ number_format($offer->discount_value) }}
                                @else
                                    {{ $offer->free_nights }} คืน
                                @endif
                            </p>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="d-block mb-1"><strong>สถานะ:</strong></label>
                            <p class="mb-0">
                                @if($offer->is_active)
                                    <span class="badge badge-success">เปิดใช้งาน</span>
                                @else
                                    <span class="badge badge-secondary">ปิดใช้งาน</span>
                                @endif
                                @if($offer->is_featured)
                                    <span class="badge badge-warning ml-1">แนะนำ</span>
                                @endif
                            </p>
                        </div>

                        @if($offer->min_nights)
                            <div class="col-md-4 mb-3">
                                <label class="d-block mb-1"><strong>จำนวนคืนขั้นต่ำ:</strong></label>
                                <p class="mb-0">{{ $offer->min_nights }} คืน</p>
                            </div>
                        @endif

                        @if($offer->min_amount)
                            <div class="col-md-4 mb-3">
                                <label class="d-block mb-1"><strong>ยอดขั้นต่ำ:</strong></label>
                                <p class="mb-0">฿{{ number_format($offer->min_amount) }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Validity Period -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-calendar"></i> ระยะเวลาใช้งาน</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="d-block mb-1"><strong>วันที่เริ่มต้น:</strong></label>
                            <p class="mb-0">{{ \Carbon\Carbon::parse($offer->valid_from)->format('d/m/Y') }}</p>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="d-block mb-1"><strong>วันที่สิ้นสุด:</strong></label>
                            <p class="mb-0">{{ \Carbon\Carbon::parse($offer->valid_to)->format('d/m/Y') }}</p>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="d-block mb-1"><strong>สถานะ:</strong></label>
                            <p class="mb-0">
                                @php
                                    $now = now();
                                    $validFrom = \Carbon\Carbon::parse($offer->valid_from);
                                    $validTo = \Carbon\Carbon::parse($offer->valid_to);
                                @endphp

                                @if($now->lt($validFrom))
                                    <span class="badge badge-info">ยังไม่เริ่ม</span>
                                @elseif($now->gt($validTo))
                                    <span class="badge badge-danger">หมดอายุ</span>
                                @else
                                    <span class="badge badge-success">กำลังใช้งาน</span>
                                @endif
                            </p>
                        </div>

                        @if($offer->applicable_days && count($offer->applicable_days) > 0)
                            <div class="col-md-12">
                                <label class="d-block mb-1"><strong>วันที่ใช้ได้:</strong></label>
                                <p class="mb-0">
                                    @php
                                        $dayNames = [
                                            'monday' => 'จันทร์', 'tuesday' => 'อังคาร', 'wednesday' => 'พุธ',
                                            'thursday' => 'พฤหัสบดี', 'friday' => 'ศุกร์', 'saturday' => 'เสาร์', 'sunday' => 'อาทิตย์'
                                        ];
                                        $days = array_map(function($day) use ($dayNames) {
                                            return $dayNames[$day] ?? $day;
                                        }, $offer->applicable_days);
                                    @endphp
                                    {{ implode(', ', $days) }}
                                </p>
                            </div>
                        @else
                            <div class="col-md-12">
                                <p class="text-muted mb-0"><em>ใช้ได้ทุกวัน</em></p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Room Types -->
            @if($offer->room_type_ids && count($offer->room_type_ids) > 0)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-bed"></i> ห้องพักที่ใช้ได้</h5>
                    </div>
                    <div class="card-body">
                        @php
                            $roomTypes = \App\Models\RoomType::whereIn('id', $offer->room_type_ids)->get();
                        @endphp

                        @if($roomTypes->count() > 0)
                            <ul class="list-unstyled mb-0">
                                @foreach($roomTypes as $room)
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success"></i>
                                        <a href="{{ route('admin.hotels.rooms.show', [$offer->hotel_id, $room->id]) }}">
                                            {{ $room->name }}
                                        </a>
                                        <span class="text-muted small">(฿{{ number_format($room->base_price) }})</span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted mb-0">ไม่พบห้องพัก</p>
                        @endif
                    </div>
                </div>
            @else
                <div class="card mb-4">
                    <div class="card-body">
                        <p class="text-muted mb-0"><em>ใช้ได้กับทุกห้องพัก</em></p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Statistics -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> สถิติการใช้งาน</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="d-block mb-1"><strong>จำนวนครั้งที่ใช้:</strong></label>
                        <p class="mb-0 h3 text-primary">{{ number_format($stats['total_uses']) }}</p>
                        @if($offer->max_uses)
                            <small class="text-muted">จาก {{ number_format($offer->max_uses) }} ครั้ง</small>
                            <div class="progress mt-2" style="height: 5px;">
                                <div class="progress-bar" role="progressbar"
                                     style="width: {{ ($stats['total_uses'] / $offer->max_uses) * 100 }}%"></div>
                            </div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="d-block mb-1"><strong>ยอดส่วนลดรวม:</strong></label>
                        <p class="mb-0 h4 text-success">฿{{ number_format($stats['total_savings']) }}</p>
                    </div>

                    @if($offer->max_uses_per_user)
                        <div class="mb-3">
                            <label class="d-block mb-1"><strong>ใช้ได้ต่อคน:</strong></label>
                            <p class="mb-0">{{ $offer->max_uses_per_user }} ครั้ง</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bolt"></i> การดำเนินการ</h5>
                </div>
                <div class="card-body">
                    @if($offer->is_active)
                        <form action="{{ route('admin.hotels.special-offers.toggle-status', $offer->id) }}" method="POST" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-warning btn-block">
                                <i class="fas fa-pause"></i> ปิดใช้งาน
                            </button>
                        </form>
                    @else
                        <form action="{{ route('admin.hotels.special-offers.toggle-status', $offer->id) }}" method="POST" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-success btn-block">
                                <i class="fas fa-play"></i> เปิดใช้งาน
                            </button>
                        </form>
                    @endif

                    <form action="{{ route('admin.hotels.special-offers.toggle-featured', $offer->id) }}" method="POST" class="mb-2">
                        @csrf
                        <button type="submit" class="btn btn-info btn-block">
                            <i class="fas fa-star"></i> {{ $offer->is_featured ? 'ยกเลิกแนะนำ' : 'ตั้งเป็นแนะนำ' }}
                        </button>
                    </form>

                    <a href="{{ route('admin.hotels.special-offers.edit', $offer->id) }}" class="btn btn-primary btn-block">
                        <i class="fas fa-edit"></i> แก้ไขโปรโมชั่น
                    </a>
                </div>
            </div>

            <!-- Metadata -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info"></i> ข้อมูลเพิ่มเติม</h5>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>สร้างเมื่อ:</strong><br>
                        <small class="text-muted">{{ $offer->created_at->format('d/m/Y H:i') }}</small>
                    </div>
                    <div>
                        <strong>แก้ไขล่าสุด:</strong><br>
                        <small class="text-muted">{{ $offer->updated_at->format('d/m/Y H:i') }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function copyCode() {
    const code = document.getElementById('offerCode').textContent;
    navigator.clipboard.writeText(code).then(() => {
        alert('คัดลอกโค้ด ' + code + ' เรียบร้อยแล้ว!');
    });
}
</script>
@endpush
@endsection
