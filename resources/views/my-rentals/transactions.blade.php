@extends('layouts.app')

@section('title', 'ประวัติธุรกรรม')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>💳 ประวัติธุรกรรม</h2>
        <a href="{{ route('my-rentals.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> กลับ
        </a>
    </div>

    <!-- Monthly Summary -->
    <div class="card mb-4 bg-primary text-white">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="mb-2">💰 สรุปค่าใช้จ่ายเดือนนี้</h5>
                    <p class="mb-0 opacity-90">{{ now()->format('F Y') }}</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="display-6 fw-bold">฿{{ number_format($monthlySummary, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small">ประเภท</label>
                    <select name="type" class="form-select">
                        <option value="">ทุกประเภท</option>
                        <option value="subscription" {{ request('type') == 'subscription' ? 'selected' : '' }}>
                            Subscription
                        </option>
                        <option value="usage" {{ request('type') == 'usage' ? 'selected' : '' }}>
                            Usage
                        </option>
                        <option value="refund" {{ request('type') == 'refund' ? 'selected' : '' }}>
                            Refund
                        </option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small">วันที่เริ่มต้น</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label small">วันที่สิ้นสุด</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
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

    <!-- Transactions Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">รายการธุรกรรม</h5>
            <span class="badge bg-secondary">{{ $transactions->total() }} รายการ</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>วันที่</th>
                        <th>บอท</th>
                        <th>ประเภท</th>
                        <th>รายละเอียด</th>
                        <th class="text-end">จำนวนเงิน</th>
                        <th>สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr>
                            <td>
                                <div>{{ $transaction->created_at->format('d/m/Y') }}</div>
                                <div class="small text-muted">{{ $transaction->created_at->format('H:i') }}</div>
                            </td>
                            <td>
                                <div class="fw-bold">{{ $transaction->rental->botProfile->name }}</div>
                                <div class="small text-muted">
                                    @if($transaction->rental->rental_type === 'monthly')
                                        รายเดือน
                                    @else
                                        ต่อข้อความ
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($transaction->type === 'subscription')
                                    <span class="badge bg-primary">📅 Subscription</span>
                                @elseif($transaction->type === 'usage')
                                    <span class="badge bg-success">💬 Usage</span>
                                @elseif($transaction->type === 'refund')
                                    <span class="badge bg-info">🔄 Refund</span>
                                @endif
                            </td>
                            <td>
                                <div>{{ $transaction->description }}</div>
                                @if($transaction->message_count)
                                    <div class="small text-muted">
                                        {{ $transaction->message_count }} ข้อความ
                                        @ ฿{{ number_format($transaction->rate_per_message, 2) }}
                                    </div>
                                @endif
                                @if($transaction->period_start && $transaction->period_end)
                                    <div class="small text-muted">
                                        {{ $transaction->period_start->format('d/m/Y') }} -
                                        {{ $transaction->period_end->format('d/m/Y') }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="fw-bold text-primary">฿{{ number_format($transaction->amount, 2) }}</div>
                                @if($transaction->platform_commission > 0)
                                    <div class="small text-muted">
                                        ค่าคอมฯ: ฿{{ number_format($transaction->platform_commission, 2) }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($transaction->status === 'completed')
                                    <span class="badge bg-success">สำเร็จ</span>
                                @elseif($transaction->status === 'pending')
                                    <span class="badge bg-warning">รอดำเนินการ</span>
                                @elseif($transaction->status === 'failed')
                                    <span class="badge bg-danger">ล้มเหลว</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">ไม่มีรายการธุรกรรม</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
            <div class="card-footer">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

    <!-- Summary Stats -->
    @if($transactions->isNotEmpty())
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <div class="text-primary mb-2" style="font-size: 2rem;">💰</div>
                        <h5 class="mb-0">฿{{ number_format($transactions->where('type', 'subscription')->sum('amount'), 2) }}</h5>
                        <p class="text-muted small mb-0">Subscription</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <div class="text-success mb-2" style="font-size: 2rem;">💬</div>
                        <h5 class="mb-0">฿{{ number_format($transactions->where('type', 'usage')->sum('amount'), 2) }}</h5>
                        <p class="text-muted small mb-0">Usage</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <div class="text-info mb-2" style="font-size: 2rem;">🔄</div>
                        <h5 class="mb-0">฿{{ number_format($transactions->where('type', 'refund')->sum('amount'), 2) }}</h5>
                        <p class="text-muted small mb-0">Refund</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
