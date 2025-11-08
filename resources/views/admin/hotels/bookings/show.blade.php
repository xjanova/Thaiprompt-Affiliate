@extends('layouts.admin')
@section('title', 'รายละเอียดการจอง #' . $booking->booking_number)
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">การจอง #{{ $booking->booking_number }}</h1>
        <div>
            <a href="{{ route('admin.hotels.bookings.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> กลับ
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">รายละเอียดการจอง</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>โรงแรม:</strong><br>
                                <a href="{{ route('admin.hotels.show', $booking->roomType->hotel->id) }}">
                                    {{ $booking->roomType->hotel->name }}
                                </a>
                            </p>
                            <p><strong>ประเภทห้อง:</strong><br>{{ $booking->roomType->name }}</p>
                            <p><strong>จำนวนห้อง:</strong> {{ $booking->rooms_count }} ห้อง</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>เช็คอิน:</strong> {{ $booking->check_in_date->format('d/m/Y') }}</p>
                            <p><strong>เช็คเอาท์:</strong> {{ $booking->check_out_date->format('d/m/Y') }}</p>
                            <p><strong>จำนวนคืน:</strong> {{ $booking->nights }} คืน</p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>ผู้จอง:</strong><br>
                                {{ $booking->user->name }}<br>
                                {{ $booking->user->email }}<br>
                                {{ $booking->user->phone }}
                            </p>
                            <p><strong>ผู้เข้าพัก:</strong><br>
                                {{ $booking->guest_name }}<br>
                                {{ $booking->guest_email }}<br>
                                {{ $booking->guest_phone }}
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>ผู้เข้าพัก:</strong><br>
                                ผู้ใหญ่: {{ $booking->adults }} คน<br>
                                เด็ก: {{ $booking->children }} คน
                            </p>
                            @if($booking->special_requests)
                                <p><strong>คำขอพิเศษ:</strong><br>{{ $booking->special_requests }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">ข้อมูลการชำระเงิน</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <tr><td>ค่าห้องพัก:</td><td class="text-right">฿{{ number_format($booking->room_total, 2) }}</td></tr>
                            <tr><td>ภาษี ({{ $booking->tax_rate }}%):</td><td class="text-right">฿{{ number_format($booking->tax_amount, 2) }}</td></tr>
                            <tr><td>ค่าบริการ:</td><td class="text-right">฿{{ number_format($booking->service_charge, 2) }}</td></tr>
                            @if($booking->discount_amount > 0)
                                <tr><td>ส่วนลด:</td><td class="text-right text-success">-฿{{ number_format($booking->discount_amount, 2) }}</td></tr>
                            @endif
                            <tr class="font-weight-bold"><td>ยอดรวมทั้งสิ้น:</td><td class="text-right">฿{{ number_format($booking->total_amount, 2) }}</td></tr>
                        </table>
                    </div>
                    <hr>
                    <p><strong>วิธีชำระเงิน:</strong> {{ ucfirst($booking->payment_method) }}</p>
                    @if($booking->payment_status == 'paid')
                        <p><strong>Transaction ID:</strong> {{ $booking->transaction_id }}</p>
                        <p><strong>ชำระเมื่อ:</strong> {{ $booking->paid_at?->format('d/m/Y H:i') }}</p>
                    @endif
                </div>
            </div>

            @if($booking->affiliate)
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">ข้อมูล Affiliate</h5></div>
                    <div class="card-body">
                        <p><strong>Affiliate:</strong> {{ $booking->affiliate->name }}</p>
                        <p><strong>ค่าคอมมิชชั่น:</strong> ฿{{ number_format($booking->commission_amount, 2) }}</p>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">สถานะ</h5></div>
                <div class="card-body">
                    <p><strong>สถานะการจอง:</strong><br>
                        <span class="badge badge-{{ $booking->getStatusColor() }} badge-lg">
                            {{ $booking->getStatusLabel() }}
                        </span>
                    </p>
                    <p><strong>สถานะการชำระเงิน:</strong><br>
                        <span class="badge badge-{{ $booking->payment_status == 'paid' ? 'success' : 'warning' }}">
                            {{ $booking->payment_status == 'paid' ? 'ชำระแล้ว' : 'รอชำระ' }}
                        </span>
                    </p>
                    <p><strong>สร้างเมื่อ:</strong><br>{{ $booking->created_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">การจัดการ</h5></div>
                <div class="card-body">
                    @if($booking->status == 'confirmed')
                        <button onclick="updateStatus('checked_in')" class="btn btn-success btn-block">
                            <i class="fas fa-sign-in-alt"></i> Check In
                        </button>
                    @endif
                    @if($booking->status == 'checked_in')
                        <button onclick="updateStatus('checked_out')" class="btn btn-primary btn-block">
                            <i class="fas fa-sign-out-alt"></i> Check Out
                        </button>
                    @endif
                    @if($booking->status == 'checked_out')
                        <button onclick="updateStatus('completed')" class="btn btn-info btn-block">
                            <i class="fas fa-check-circle"></i> ทำรายการสำเร็จ
                        </button>
                    @endif
                    @if(in_array($booking->status, ['pending', 'confirmed']))
                        <button onclick="cancelBooking()" class="btn btn-danger btn-block">
                            <i class="fas fa-times-circle"></i> ยกเลิกการจอง
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateStatus(status) {
    if (confirm('คุณต้องการเปลี่ยนสถานะ?')) {
        fetch('/admin/hotels/bookings/{{ $booking->id }}/update-status', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
            body: JSON.stringify({status})
        }).then(() => location.reload());
    }
}

function cancelBooking() {
    if (confirm('คุณต้องการยกเลิกการจองนี้? การกระทำนี้ไม่สามารถย้อนกลับได้')) {
        fetch('/admin/hotels/bookings/{{ $booking->id }}/cancel', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'}
        }).then(() => location.reload());
    }
}
</script>
@endsection
