@extends('layouts.app')

@section('title', 'แดชบอร์ดเจ้าของบอท')

@section('content')
<div class="container py-4">
    <h2 class="mb-4">📊 แดชบอร์ดเจ้าของบอท</h2>

    <!-- Current Month Summary -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-success mb-2" style="font-size: 2rem;">💰</div>
                    <h4 class="mb-1">฿{{ number_format($earningsSummary['total_net'], 2) }}</h4>
                    <p class="text-muted small mb-0">รายได้เดือนนี้</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-primary mb-2" style="font-size: 2rem;">🤖</div>
                    <h4 class="mb-1">{{ $bots->sum('rentals_count') }}</h4>
                    <p class="text-muted small mb-0">ผู้เช่าปัจจุบัน</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-warning mb-2" style="font-size: 2rem;">⏳</div>
                    <h4 class="mb-1">฿{{ number_format($earningsSummary['pending_payout'], 2) }}</h4>
                    <p class="text-muted small mb-0">รอจ่าย</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-info mb-2" style="font-size: 2rem;">✓</div>
                    <h4 class="mb-1">฿{{ number_format($earningsSummary['paid_out'], 2) }}</h4>
                    <p class="text-muted small mb-0">จ่ายแล้ว</p>
                </div>
            </div>
        </div>
    </div>

    <!-- All-Time Summary -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">💎 สรุปรวมทั้งหมด</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="text-center">
                        <div class="text-muted small">รายได้รวม (Gross)</div>
                        <div class="h5 text-primary mb-0">฿{{ number_format($allTimeSummary['total_gross'], 2) }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <div class="text-muted small">ค่าคอมมิชชั่น</div>
                        <div class="h5 text-danger mb-0">฿{{ number_format($allTimeSummary['total_commission'], 2) }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <div class="text-muted small">รายได้สุทธิ (Net)</div>
                        <div class="h5 text-success mb-0">฿{{ number_format($allTimeSummary['total_net'], 2) }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <div class="text-muted small">อัตราค่าคอมฯ เฉลี่ย</div>
                        <div class="h5 text-warning mb-0">{{ number_format($allTimeSummary['commission_rate'], 2) }}%</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Monthly Earnings Chart -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">📈 รายได้รายเดือน (12 เดือนล่าสุด)</h5>
                </div>
                <div class="card-body">
                    <canvas id="earningsChart" height="80"></canvas>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">💳 ธุรกรรมล่าสุด</h5>
                    <a href="{{ route('owner-dashboard.earnings') }}" class="btn btn-sm btn-outline-primary">
                        ดูทั้งหมด
                    </a>
                </div>
                <div class="card-body">
                    @forelse($recentTransactions as $transaction)
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div>
                                <div class="fw-bold">{{ $transaction->rental->botProfile->name }}</div>
                                <div class="small text-muted">
                                    {{ $transaction->user->name }} • {{ $transaction->created_at->format('d/m/Y H:i') }}
                                </div>
                                <div class="small text-muted">{{ $transaction->description }}</div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold text-success">
                                    +฿{{ number_format($transaction->owner_earning, 2) }}
                                </div>
                                <div class="small text-muted">
                                    จาก ฿{{ number_format($transaction->amount, 2) }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted text-center mb-0">ยังไม่มีธุรกรรม</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- My Bots -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">🤖 บอทของฉัน</h6>
                    <a href="{{ route('admin.ai-bots.index') }}" class="btn btn-sm btn-outline-primary">
                        จัดการ
                    </a>
                </div>
                <div class="card-body">
                    @forelse($botStats as $stat)
                        <div class="mb-3 pb-3 border-bottom">
                            <div class="fw-bold">{{ $stat['bot']->name }}</div>
                            <div class="row mt-2">
                                <div class="col-6">
                                    <div class="small text-muted">ผู้เช่า</div>
                                    <div class="fw-bold">{{ $stat['active_rentals'] }}</div>
                                </div>
                                <div class="col-6">
                                    <div class="small text-muted">รายได้เดือนนี้</div>
                                    <div class="fw-bold text-success">
                                        ฿{{ number_format($stat['monthly_earnings'], 2) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted text-center mb-0">ยังไม่มีบอทที่เปิดให้เช่า</p>
                    @endforelse
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">⚡ เมนูด่วน</h6>
                </div>
                <div class="card-body">
                    <a href="{{ route('owner-dashboard.earnings') }}" class="btn btn-outline-primary w-100 mb-2">
                        <i class="fas fa-chart-line"></i> รายงานรายได้
                    </a>
                    <a href="{{ route('owner-dashboard.rentals') }}" class="btn btn-outline-info w-100 mb-2">
                        <i class="fas fa-users"></i> จัดการผู้เช่า
                    </a>
                    <a href="{{ route('owner-dashboard.payouts') }}" class="btn btn-outline-success w-100 mb-2">
                        <i class="fas fa-money-bill-wave"></i> ประวัติการจ่ายเงิน
                    </a>
                    <a href="{{ route('admin.ai-bots.create') }}" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-plus"></i> สร้างบอทใหม่
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
// Monthly Earnings Chart
const ctx = document.getElementById('earningsChart').getContext('2d');
const monthlyData = @json($monthlyEarnings);

// Generate labels and data
const labels = [];
const data = [];
const currentDate = new Date();

for (let i = 11; i >= 0; i--) {
    const date = new Date(currentDate.getFullYear(), currentDate.getMonth() - i, 1);
    const monthKey = date.toISOString().slice(0, 7);
    labels.push(date.toLocaleDateString('th-TH', { month: 'short', year: '2-digit' }));
    data.push(monthlyData[monthKey] || 0);
}

new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'รายได้ (บาท)',
            data: data,
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '฿' + value.toLocaleString();
                    }
                }
            }
        }
    }
});
</script>
@endsection
