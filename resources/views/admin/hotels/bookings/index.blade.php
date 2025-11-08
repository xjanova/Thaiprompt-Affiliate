@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">จัดการการจองโรงแรม</h1>
        <div>
            <a href="{{ route('admin.hotels.bookings.calendar') }}" class="btn btn-info mr-2">
                <i class="fas fa-calendar"></i> ปฏิทิน
            </a>
            <a href="{{ route('admin.hotels.bookings.analytics') }}" class="btn btn-success mr-2">
                <i class="fas fa-chart-bar"></i> รายงาน
            </a>
            <a href="{{ route('admin.hotels.bookings.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> จองแบบ Manual
            </a>
        </div>
    </div>

    <!-- Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-primary">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">การจองทั้งหมด</div>
                    <div class="h5 mb-0 font-weight-bold">{{ number_format($stats['total']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-warning">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">รอชำระเงิน</div>
                    <div class="h5 mb-0 font-weight-bold">{{ number_format($stats['pending']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-success">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">ยืนยันแล้ว</div>
                    <div class="h5 mb-0 font-weight-bold">{{ number_format($stats['confirmed']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-info">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">รายได้รวม</div>
                    <div class="h5 mb-0 font-weight-bold">฿{{ number_format($stats['total_revenue']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.hotels.bookings.index') }}">
                <div class="row">
                    <div class="col-md-3">
                        <input type="text" name="search" class="form-control" placeholder="ค้นหาเลขที่จอง, ชื่อผู้จอง..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-control">
                            <option value="all">สถานะทั้งหมด</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>รอชำระเงิน</option>
                            <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>ยืนยันแล้ว</option>
                            <option value="checked_in" {{ request('status') == 'checked_in' ? 'selected' : '' }}>เช็คอินแล้ว</option>
                            <option value="checked_out" {{ request('status') == 'checked_out' ? 'selected' : '' }}>เช็คเอาท์แล้ว</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>ยกเลิกแล้ว</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>เสร็จสิ้น</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="hotel_id" class="form-control">
                            <option value="">โรงแรมทั้งหมด</option>
                            @foreach($hotels as $hotel)
                                <option value="{{ $hotel->id }}" {{ request('hotel_id') == $hotel->id ? 'selected' : '' }}>
                                    {{ $hotel->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="จากวันที่">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="ถึงวันที่">
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Bookings Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>เลขที่จอง</th>
                            <th>โรงแรม</th>
                            <th>ผู้จอง</th>
                            <th>เช็คอิน</th>
                            <th>เช็คเอาท์</th>
                            <th>จำนวนคืน</th>
                            <th>ยอดเงิน</th>
                            <th>สถานะ</th>
                            <th>การชำระเงิน</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $booking)
                            <tr>
                                <td>
                                    <strong>{{ $booking->booking_number }}</strong><br>
                                    <small class="text-muted">{{ $booking->created_at->format('d/m/Y H:i') }}</small>
                                </td>
                                <td>
                                    {{ $booking->hotel->name }}<br>
                                    <small class="text-muted">{{ $booking->roomType->name }}</small>
                                </td>
                                <td>
                                    {{ $booking->guest_name }}<br>
                                    <small class="text-muted">{{ $booking->guest_email }}</small>
                                </td>
                                <td>{{ $booking->check_in_date->format('d/m/Y') }}</td>
                                <td>{{ $booking->check_out_date->format('d/m/Y') }}</td>
                                <td class="text-center">{{ $booking->number_of_nights }}</td>
                                <td>
                                    <strong>฿{{ number_format($booking->total_amount) }}</strong>
                                </td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'pending' => 'warning',
                                            'confirmed' => 'success',
                                            'checked_in' => 'info',
                                            'checked_out' => 'primary',
                                            'cancelled' => 'danger',
                                            'completed' => 'success'
                                        ];
                                        $statusLabels = [
                                            'pending' => 'รอชำระเงิน',
                                            'confirmed' => 'ยืนยันแล้ว',
                                            'checked_in' => 'เช็คอินแล้ว',
                                            'checked_out' => 'เช็คเอาท์แล้ว',
                                            'cancelled' => 'ยกเลิกแล้ว',
                                            'completed' => 'เสร็จสิ้น'
                                        ];
                                    @endphp
                                    <span class="badge badge-{{ $statusColors[$booking->status] ?? 'secondary' }}">
                                        {{ $statusLabels[$booking->status] ?? $booking->status }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $paymentColors = [
                                            'paid' => 'success',
                                            'pending' => 'warning',
                                            'failed' => 'danger',
                                            'refunded' => 'info'
                                        ];
                                    @endphp
                                    <span class="badge badge-{{ $paymentColors[$booking->payment_status] ?? 'secondary' }}">
                                        {{ $booking->payment_status === 'paid' ? 'ชำระแล้ว' : ($booking->payment_status === 'pending' ? 'รอชำระ' : $booking->payment_status) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.hotels.bookings.show', $booking->id) }}"
                                           class="btn btn-sm btn-info" title="ดูรายละเอียด">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        @if($booking->status === 'confirmed')
                                            <button onclick="updateStatus({{ $booking->id }}, 'checked_in')"
                                                    class="btn btn-sm btn-success" title="เช็คอิน">
                                                <i class="fas fa-sign-in-alt"></i>
                                            </button>
                                        @endif

                                        @if($booking->status === 'checked_in')
                                            <button onclick="updateStatus({{ $booking->id }}, 'checked_out')"
                                                    class="btn btn-sm btn-primary" title="เช็คเอาท์">
                                                <i class="fas fa-sign-out-alt"></i>
                                            </button>
                                        @endif

                                        @if(in_array($booking->status, ['pending', 'confirmed']))
                                            <button onclick="cancelBooking({{ $booking->id }})"
                                                    class="btn btn-sm btn-danger" title="ยกเลิก">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted">ไม่พบข้อมูลการจอง</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-3">
                {{ $bookings->links() }}
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function updateStatus(bookingId, status) {
    if (confirm('คุณต้องการเปลี่ยนสถานะการจองนี้?')) {
        fetch(`/admin/hotels/bookings/${bookingId}/update-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ status: status })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'เกิดข้อผิดพลาด');
            }
        });
    }
}

function cancelBooking(bookingId) {
    const reason = prompt('กรุณาระบุเหตุผลในการยกเลิก:');
    if (reason !== null) {
        fetch(`/admin/hotels/bookings/${bookingId}/cancel`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ reason: reason })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'เกิดข้อผิดพลาด');
            }
        });
    }
}
</script>
@endpush
@endsection
