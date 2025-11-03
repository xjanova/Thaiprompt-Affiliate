@extends('layouts.app')

@section('title', 'ประวัติการจ่ายเงิน')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>💸 ประวัติการจ่ายเงิน</h2>
        <a href="{{ route('owner-dashboard.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> กลับ
        </a>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-success mb-2" style="font-size: 2rem;">✓</div>
                    <h4 class="mb-1">฿{{ number_format($summary['total_paid'], 2) }}</h4>
                    <p class="text-muted small mb-0">จ่ายแล้ว</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-info mb-2" style="font-size: 2rem;">⏳</div>
                    <h4 class="mb-1">฿{{ number_format($summary['processing'], 2) }}</h4>
                    <p class="text-muted small mb-0">กำลังดำเนินการ</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-warning mb-2" style="font-size: 2rem;">⏸️</div>
                    <h4 class="mb-1">฿{{ number_format($summary['pending'], 2) }}</h4>
                    <p class="text-muted small mb-0">รอจ่าย</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Request Payout Button -->
    @if($summary['pending'] >= 100)
        <div class="alert alert-success d-flex justify-content-between align-items-center mb-4">
            <div>
                <strong>🎉 คุณสามารถขอถอนเงินได้!</strong>
                <p class="mb-0 small">ยอดรอจ่ายปัจจุบัน: ฿{{ number_format($summary['pending'], 2) }}</p>
            </div>
            <a href="{{ route('owner-dashboard.earnings') }}" class="btn btn-success">
                <i class="fas fa-money-bill-wave"></i> ขอถอนเงิน
            </a>
        </div>
    @endif

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <select name="status" class="form-select">
                        <option value="">ทุกสถานะ</option>
                        <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>
                            กำลังดำเนินการ
                        </option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>
                            จ่ายแล้ว
                        </option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> กรอง
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Payouts Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">รายการจ่ายเงิน</h5>
            <span class="badge bg-secondary">{{ $payouts->total() }} รายการ</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>วันที่</th>
                        <th>บอท</th>
                        <th>รายละเอียด</th>
                        <th>วิธีการจ่าย</th>
                        <th>Reference</th>
                        <th class="text-end">จำนวน</th>
                        <th>สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payouts as $payout)
                        <tr>
                            <td>
                                @if($payout->payout_date)
                                    <div>{{ $payout->payout_date->format('d/m/Y') }}</div>
                                    <div class="small text-muted">{{ $payout->payout_date->format('H:i') }}</div>
                                @else
                                    <div>{{ $payout->created_at->format('d/m/Y') }}</div>
                                    <div class="small text-muted">{{ $payout->created_at->format('H:i') }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="fw-bold">{{ $payout->botProfile->name }}</div>
                            </td>
                            <td>
                                <div>{{ $payout->description }}</div>
                                <div class="small text-muted">
                                    Earning Date: {{ \Carbon\Carbon::parse($payout->earning_date)->format('d/m/Y') }}
                                </div>
                            </td>
                            <td>
                                @if($payout->payout_method)
                                    @if($payout->payout_method === 'bank_transfer')
                                        <span class="badge bg-primary">
                                            <i class="fas fa-university"></i> Bank Transfer
                                        </span>
                                    @elseif($payout->payout_method === 'promptpay')
                                        <span class="badge bg-info">
                                            <i class="fas fa-mobile-alt"></i> PromptPay
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">{{ $payout->payout_method }}</span>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($payout->payout_reference)
                                    <code class="small">{{ $payout->payout_reference }}</code>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="fw-bold text-success">฿{{ number_format($payout->net_amount, 2) }}</div>
                                <div class="small text-muted">
                                    จาก ฿{{ number_format($payout->gross_amount, 2) }}
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-{{ $payout->payout_status_color }}">
                                    {{ $payout->payout_status_label }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">ยังไม่มีประวัติการจ่ายเงิน</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($payouts->hasPages())
            <div class="card-footer">
                {{ $payouts->links() }}
            </div>
        @endif
    </div>

    <!-- Help Section -->
    <div class="card mt-4 border-info">
        <div class="card-header bg-info text-white">
            <h6 class="mb-0"><i class="fas fa-info-circle"></i> ข้อมูลเกี่ยวกับการจ่ายเงิน</h6>
        </div>
        <div class="card-body">
            <h6>เงื่อนไขการถอนเงิน:</h6>
            <ul class="mb-3">
                <li>ยอดขั้นต่ำสำหรับการถอนเงิน: <strong>฿100</strong></li>
                <li>ระยะเวลาดำเนินการ: <strong>3-5 วันทำการ</strong></li>
                <li>สามารถขอถอนเงินได้ทุกวัน</li>
            </ul>

            <h6>วิธีการจ่ายเงิน:</h6>
            <ul class="mb-0">
                <li><strong>Bank Transfer:</strong> โอนเข้าบัญชีธนาคารโดยตรง</li>
                <li><strong>PromptPay:</strong> โอนผ่านระบบพร้อมเพย์</li>
            </ul>
        </div>
    </div>
</div>
@endsection
