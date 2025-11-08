@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">รายงานการจองโรงแรม</h1>
        <div>
            <a href="{{ route('admin.hotels.bookings.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> กลับ
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> พิมพ์รายงาน
            </button>
        </div>
    </div>

    <!-- Date Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.hotels.bookings.analytics') }}">
                <div class="row">
                    <div class="col-md-3">
                        <label>จากวันที่</label>
                        <input type="date" name="start_date" class="form-control"
                               value="{{ $startDate->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-3">
                        <label>ถึงวันที่</label>
                        <input type="date" name="end_date" class="form-control"
                               value="{{ $endDate->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-3">
                        <label>โรงแรม</label>
                        <select name="hotel_id" class="form-control">
                            <option value="">ทั้งหมด</option>
                            @foreach($hotels as $hotel)
                                <option value="{{ $hotel->id }}" {{ request('hotel_id') == $hotel->id ? 'selected' : '' }}>
                                    {{ $hotel->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-search"></i> ค้นหา
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-primary">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">การจองทั้งหมด</div>
                    <div class="h5 mb-0 font-weight-bold">{{ number_format($stats['total_bookings']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-success">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">ยืนยันแล้ว</div>
                    <div class="h5 mb-0 font-weight-bold">{{ number_format($stats['confirmed_bookings']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-danger">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">ยกเลิกแล้ว</div>
                    <div class="h5 mb-0 font-weight-bold">{{ number_format($stats['cancelled_bookings']) }}</div>
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
        <div class="col-md-3 mt-3">
            <div class="card border-left-warning">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">ยอดเฉลี่ย/การจอง</div>
                    <div class="h5 mb-0 font-weight-bold">฿{{ number_format($stats['average_booking_value']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Bookings by Day -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">การจองแต่ละวัน</h5>
                </div>
                <div class="card-body">
                    <canvas id="bookingsChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <!-- Bookings by Status -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">สถานะการจอง</h5>
                </div>
                <div class="card-body">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Hotels -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">โรงแรมยอดนิยม (Top 10)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>โรงแรม</th>
                            <th>จำนวนการจอง</th>
                            <th>รายได้</th>
                            <th>ยอดเฉลี่ย</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topHotels as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <strong>{{ $item->hotel->name }}</strong><br>
                                    <small class="text-muted">{{ $item->hotel->city }}</small>
                                </td>
                                <td>{{ number_format($item->bookings) }}</td>
                                <td class="text-primary font-weight-bold">฿{{ number_format($item->revenue) }}</td>
                                <td>฿{{ number_format($item->revenue / $item->bookings) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
// Bookings by Day Chart
const bookingsData = {!! json_encode($bookingsByDay) !!};
const bookingsCtx = document.getElementById('bookingsChart').getContext('2d');
new Chart(bookingsCtx, {
    type: 'line',
    data: {
        labels: bookingsData.map(item => item.date),
        datasets: [{
            label: 'จำนวนการจอง',
            data: bookingsData.map(item => item.count),
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.1
        }, {
            label: 'รายได้ (x1000)',
            data: bookingsData.map(item => item.revenue / 1000),
            borderColor: 'rgb(255, 99, 132)',
            backgroundColor: 'rgba(255, 99, 132, 0.2)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            },
            title: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Status Pie Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: ['ยืนยันแล้ว', 'รอชำระ', 'ยกเลิก', 'เสร็จสิ้น'],
        datasets: [{
            data: [
                {{ $stats['confirmed_bookings'] }},
                {{ $stats['pending'] ?? 0 }},
                {{ $stats['cancelled_bookings'] }},
                {{ $stats['total_bookings'] - $stats['confirmed_bookings'] - ($stats['pending'] ?? 0) - $stats['cancelled_bookings'] }}
            ],
            backgroundColor: [
                'rgb(75, 192, 192)',
                'rgb(255, 205, 86)',
                'rgb(255, 99, 132)',
                'rgb(54, 162, 235)'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
            }
        }
    }
});
</script>
@endpush
@endsection
