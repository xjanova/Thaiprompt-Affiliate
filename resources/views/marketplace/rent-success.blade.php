@extends('layouts.app')

@section('title', 'เช่าบอทสำเร็จ')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Success Message -->
            <div class="text-center mb-4">
                <div class="success-icon mb-3">
                    <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
                </div>
                <h2 class="mb-2">🎉 เช่าบอทสำเร็จ!</h2>
                <p class="text-muted">คุณสามารถเริ่มใช้งานบอทได้ทันที</p>
            </div>

            <!-- Rental Details Card -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">รายละเอียดการเช่า</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-4 text-muted">บอท:</div>
                        <div class="col-8">
                            <strong>{{ $rental->botProfile->name }}</strong>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-4 text-muted">ประเภทการเช่า:</div>
                        <div class="col-8">
                            @if($rental->rental_type === 'monthly')
                                <span class="badge bg-primary">รายเดือน</span>
                            @else
                                <span class="badge bg-success">ต่อข้อความ</span>
                            @endif
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-4 text-muted">ราคา:</div>
                        <div class="col-8">
                            <strong class="text-primary">฿{{ number_format($rental->price, 2) }}</strong>
                            @if($rental->rental_type === 'monthly')
                                <span class="small text-muted">/ เดือน</span>
                            @else
                                <span class="small text-muted">/ ข้อความ</span>
                            @endif
                        </div>
                    </div>

                    @if($rental->rental_type === 'monthly')
                        <div class="row mb-3">
                            <div class="col-4 text-muted">วันที่เริ่ม:</div>
                            <div class="col-8">{{ $rental->start_date->format('d/m/Y H:i') }}</div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-4 text-muted">วันหมดอายุ:</div>
                            <div class="col-8">
                                {{ $rental->end_date->format('d/m/Y H:i') }}
                                <span class="text-muted small">({{ $rental->days_remaining }} วัน)</span>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-4 text-muted">ต่ออายุอัตโนมัติ:</div>
                            <div class="col-8">
                                @if($rental->auto_renew)
                                    <span class="badge bg-success">เปิด</span>
                                @else
                                    <span class="badge bg-secondary">ปิด</span>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-4 text-muted">สถานะ:</div>
                        <div class="col-8">
                            <span class="badge bg-success">{{ strtoupper($rental->status) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Next Steps -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">📋 ขั้นตอนถัดไป</h5>
                </div>
                <div class="card-body">
                    <ol class="mb-0">
                        <li class="mb-2">
                            <strong>เริ่มใช้งาน:</strong> ไปที่หน้า <a href="{{ route('my-rentals.index') }}">การเช่าของฉัน</a> เพื่อดูรายละเอียดและเริ่มสนทนากับบอท
                        </li>
                        <li class="mb-2">
                            <strong>ตั้งค่า:</strong> คุณสามารถปรับแต่งการตั้งค่าการเช่าได้ตามต้องการ
                        </li>
                        @if($rental->rental_type === 'monthly')
                            <li class="mb-2">
                                <strong>ต่ออายุ:</strong> การเช่าจะต่ออายุอัตโนมัติทุกเดือน คุณสามารถยกเลิกได้ทุกเวลา
                            </li>
                        @endif
                        <li class="mb-0">
                            <strong>ติดตาม:</strong> ดูประวัติการใช้งานและค่าใช้จ่ายได้ที่หน้าการเช่า
                        </li>
                    </ol>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex gap-2 justify-content-center">
                <a href="{{ route('my-rentals.show', $rental->id) }}" class="btn btn-primary">
                    <i class="fas fa-eye"></i> ดูรายละเอียดการเช่า
                </a>
                <a href="{{ route('marketplace.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-store"></i> กลับไปตลาด
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.success-icon {
    animation: scaleIn 0.5s ease-in-out;
}

@keyframes scaleIn {
    0% {
        transform: scale(0);
        opacity: 0;
    }
    50% {
        transform: scale(1.1);
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}
</style>
@endsection
