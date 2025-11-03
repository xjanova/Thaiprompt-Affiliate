@extends('layouts.app')

@section('title', 'รายงานรายได้')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>📊 รายงานรายได้</h2>
        <a href="{{ route('owner-dashboard.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> กลับ
        </a>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-primary mb-2" style="font-size: 2rem;">💵</div>
                    <h4 class="mb-1">฿{{ number_format($summary['total_gross'], 2) }}</h4>
                    <p class="text-muted small mb-0">รายได้รวม (Gross)</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-danger mb-2" style="font-size: 2rem;">💸</div>
                    <h4 class="mb-1">฿{{ number_format($summary['total_commission'], 2) }}</h4>
                    <p class="text-muted small mb-0">ค่าคอมมิชชั่น</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-success mb-2" style="font-size: 2rem;">💰</div>
                    <h4 class="mb-1">฿{{ number_format($summary['total_net'], 2) }}</h4>
                    <p class="text-muted small mb-0">รายได้สุทธิ (Net)</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-warning mb-2" style="font-size: 2rem;">⏳</div>
                    <h4 class="mb-1">฿{{ number_format($summary['pending_payout'], 2) }}</h4>
                    <p class="text-muted small mb-0">รอจ่าย</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small">เดือน</label>
                    <select name="month" class="form-select">
                        <option value="">เดือนปัจจุบัน</option>
                        @foreach($availableMonths as $month)
                            <option value="{{ $month }}" {{ request('month') == $month ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small">บอท</label>
                    <select name="bot_id" class="form-select">
                        <option value="">ทุกบอท</option>
                        @foreach($bots as $bot)
                            <option value="{{ $bot->id }}" {{ request('bot_id') == $bot->id ? 'selected' : '' }}>
                                {{ $bot->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small">สถานะ</label>
                    <select name="payout_status" class="form-select">
                        <option value="">ทุกสถานะ</option>
                        <option value="pending" {{ request('payout_status') == 'pending' ? 'selected' : '' }}>รอจ่าย</option>
                        <option value="processing" {{ request('payout_status') == 'processing' ? 'selected' : '' }}>กำลังดำเนินการ</option>
                        <option value="paid" {{ request('payout_status') == 'paid' ? 'selected' : '' }}>จ่ายแล้ว</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> กรอง
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Request Payout Button -->
    @if($summary['pending_payout'] >= 100)
        <div class="alert alert-info d-flex justify-content-between align-items-center mb-4">
            <div>
                <strong>คุณมีรายได้รอจ่าย ฿{{ number_format($summary['pending_payout'], 2) }}</strong>
                <p class="mb-0 small">ยอดขั้นต่ำสำหรับขอถอน: ฿100</p>
            </div>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#payoutModal">
                <i class="fas fa-money-bill-wave"></i> ขอถอนเงิน
            </button>
        </div>
    @endif

    <!-- Earnings Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">รายการรายได้</h5>
            <span class="badge bg-secondary">{{ $earnings->total() }} รายการ</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>วันที่</th>
                        <th>บอท</th>
                        <th>รายละเอียด</th>
                        <th class="text-end">Gross</th>
                        <th class="text-end">Commission</th>
                        <th class="text-end">Net</th>
                        <th>สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($earnings as $earning)
                        <tr>
                            <td>
                                <div>{{ \Carbon\Carbon::parse($earning->earning_date)->format('d/m/Y') }}</div>
                            </td>
                            <td>
                                <div class="fw-bold">{{ $earning->botProfile->name }}</div>
                                <div class="small text-muted">
                                    @if($earning->rental)
                                        {{ $earning->rental->rental_type === 'monthly' ? 'รายเดือน' : 'ต่อข้อความ' }}
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div>{{ $earning->description }}</div>
                                @if($earning->transaction)
                                    <div class="small text-muted">
                                        Ref: #{{ $earning->transaction->id }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-end">฿{{ number_format($earning->gross_amount, 2) }}</td>
                            <td class="text-end text-danger">
                                -฿{{ number_format($earning->commission, 2) }}
                                <div class="small text-muted">
                                    ({{ number_format($earning->commission_rate, 2) }}%)
                                </div>
                            </td>
                            <td class="text-end">
                                <strong class="text-success">฿{{ number_format($earning->net_amount, 2) }}</strong>
                            </td>
                            <td>
                                <span class="badge bg-{{ $earning->payout_status_color }}">
                                    {{ $earning->payout_status_label }}
                                </span>
                                @if($earning->payout_date)
                                    <div class="small text-muted">
                                        {{ $earning->payout_date->format('d/m/Y') }}
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">ไม่มีรายการรายได้</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($earnings->isNotEmpty())
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="3" class="text-end">รวม:</th>
                            <th class="text-end">฿{{ number_format($earnings->sum('gross_amount'), 2) }}</th>
                            <th class="text-end text-danger">-฿{{ number_format($earnings->sum('commission'), 2) }}</th>
                            <th class="text-end text-success">฿{{ number_format($earnings->sum('net_amount'), 2) }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        @if($earnings->hasPages())
            <div class="card-footer">
                {{ $earnings->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Payout Request Modal -->
<div class="modal fade" id="payoutModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('owner-dashboard.request-payout') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">ขอถอนเงิน</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>ยอดรอจ่ายปัจจุบัน:</strong> ฿{{ number_format($summary['pending_payout'], 2) }}
                    </div>

                    <div class="mb-3">
                        <label class="form-label">จำนวนเงินที่ต้องการถอน <span class="text-danger">*</span></label>
                        <input type="number"
                               name="amount"
                               class="form-control"
                               min="100"
                               max="{{ $summary['pending_payout'] }}"
                               step="0.01"
                               required>
                        <div class="form-text">ยอดขั้นต่ำ: ฿100</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">วิธีการจ่าย <span class="text-danger">*</span></label>
                        <select name="payout_method" class="form-select" required>
                            <option value="">เลือกวิธีการ</option>
                            <option value="bank_transfer">โอนเข้าบัญชีธนาคาร</option>
                            <option value="promptpay">พร้อมเพย์</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">เลขที่บัญชี/พร้อมเพย์ <span class="text-danger">*</span></label>
                        <input type="text" name="account_number" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">ชื่อบัญชี <span class="text-danger">*</span></label>
                        <input type="text" name="account_name" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                    <button type="submit" class="btn btn-success">ส่งคำขอ</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
