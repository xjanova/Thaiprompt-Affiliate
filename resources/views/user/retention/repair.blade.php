@extends('layouts.user')

@section('title', 'ซ่อมสิทธิ์ - Repair Membership')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header mb-4">
                <h1 class="page-title">
                    <i class="fas fa-tools text-warning"></i>
                    ซ่อมสิทธิ์
                </h1>
                <p class="text-muted">ซื้อซ่อมเดือนที่ไม่ผ่านเกณฑ์เพื่อกู้สิทธิ์การรักษายอด</p>
            </div>
        </div>
    </div>

    <div class="row">
        @forelse($repairablePeriods as $period)
        <div class="col-md-6 mb-4">
            <div class="repair-card">
                <div class="repair-header">
                    <div class="period-badge">
                        <i class="fas fa-calendar-alt"></i>
                        <span>{{ $period['period_month'] }}</span>
                    </div>
                    <div class="status-badge failed">
                        <i class="fas fa-exclamation-circle"></i>
                        ไม่ผ่านเกณฑ์
                    </div>
                </div>

                <div class="repair-body">
                    <div class="points-info">
                        <div class="info-row">
                            <span class="label">แต้มที่ต้องการ:</span>
                            <span class="value">{{ number_format($period['required_points'], 2) }}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">แต้มที่ได้:</span>
                            <span class="value">{{ number_format($period['earned_points'], 2) }}</span>
                        </div>
                        <div class="info-row highlight">
                            <span class="label">แต้มที่ต้องซ่อม:</span>
                            <span class="value">{{ number_format($period['points_needed'], 2) }}</span>
                        </div>
                    </div>

                    <div class="cost-section">
                        <div class="cost-label">ค่าใช้จ่ายในการซ่อม</div>
                        <div class="cost-value">฿{{ number_format($period['repair_cost'], 2) }}</div>
                    </div>

                    <button class="btn btn-repair" onclick="repairPeriod('{{ $period['period_month'] }}', {{ $period['repair_cost'] }})">
                        <i class="fas fa-shopping-cart"></i>
                        ซื้อซ่อมเดือนนี้
                    </button>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="empty-state">
                <i class="fas fa-check-circle"></i>
                <h3>ไม่มีเดือนที่ต้องซ่อม</h3>
                <p>คุณผ่านเกณฑ์การรักษายอดทุกเดือน ยอดเยี่ยม!</p>
            </div>
        </div>
        @endforelse
    </div>
</div>

<style>
.repair-card {
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: transform 0.3s ease;
}

.repair-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
}

.repair-header {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.period-badge {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #fff;
    font-size: 18px;
    font-weight: 600;
}

.status-badge {
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.status-badge.failed {
    background: rgba(255, 255, 255, 0.3);
    color: #fff;
}

.repair-body {
    padding: 25px;
}

.points-info {
    margin-bottom: 20px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
}

.info-row.highlight {
    background: #fef3c7;
    padding: 12px 15px;
    border-radius: 8px;
    border: none;
    margin-top: 10px;
}

.info-row .label {
    color: #666;
    font-size: 14px;
}

.info-row .value {
    color: #333;
    font-size: 16px;
    font-weight: 600;
}

.cost-section {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    margin-bottom: 20px;
}

.cost-label {
    color: #92400e;
    font-size: 14px;
    margin-bottom: 8px;
}

.cost-value {
    color: #78350f;
    font-size: 32px;
    font-weight: 700;
}

.btn-repair {
    width: 100%;
    padding: 15px;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.btn-repair:hover {
    background: linear-gradient(135deg, #d97706, #b45309);
    transform: scale(1.02);
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.empty-state i {
    font-size: 64px;
    color: #10b981;
    margin-bottom: 20px;
}

.empty-state h3 {
    color: #333;
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 10px;
}

.empty-state p {
    color: #666;
    font-size: 16px;
}
</style>

<script>
async function repairPeriod(periodMonth, cost) {
    if (!confirm(`คุณต้องการซ่อมสิทธิ์เดือน ${periodMonth} ด้วยค่าใช้จ่าย ฿${cost.toFixed(2)} ใช่หรือไม่?`)) {
        return;
    }

    try {
        const response = await fetch('{{ route('user.retention.repair.process') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                period_month: periodMonth,
                amount: cost
            })
        });

        const result = await response.json();

        if (result.success) {
            alert('✅ ' + result.message);
            window.location.reload();
        } else {
            alert('❌ ' + result.message);
        }
    } catch (error) {
        alert('❌ เกิดข้อผิดพลาด: ' + error.message);
    }
}
</script>
@endsection
