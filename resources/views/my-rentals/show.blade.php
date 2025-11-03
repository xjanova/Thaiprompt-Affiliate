@extends('layouts.app')

@section('title', 'รายละเอียดการเช่า')

@section('content')
<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('my-rentals.index') }}">การเช่าของฉัน</a></li>
            <li class="breadcrumb-item active">รายละเอียด</li>
        </ol>
    </nav>

    <div class="row">
        <!-- Left Column: Rental Details -->
        <div class="col-lg-8">
            <!-- Bot Info Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">🤖 ข้อมูลบอท</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            @if($rental->botProfile->avatar_url)
                                <img src="{{ $rental->botProfile->avatar_url }}"
                                     class="rounded-circle img-fluid mb-2"
                                     style="max-width: 100px;"
                                     alt="{{ $rental->botProfile->name }}">
                            @else
                                <div class="rounded-circle bg-primary text-white mx-auto mb-2 d-flex align-items-center justify-content-center"
                                     style="width: 100px; height: 100px; font-size: 2.5rem;">
                                    🤖
                                </div>
                            @endif
                        </div>
                        <div class="col-md-9">
                            <h4 class="mb-2">{{ $rental->botProfile->name }}</h4>
                            <p class="text-muted">{{ $rental->botProfile->description }}</p>
                            <p class="mb-1">
                                <strong>เจ้าของ:</strong> {{ $rental->botProfile->owner->name }}
                            </p>
                            <p class="mb-0">
                                <strong>Provider:</strong> {{ $rental->botProfile->provider->name }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rental Details Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">💼 รายละเอียดการเช่า</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-4 text-muted">ประเภท:</div>
                        <div class="col-8">
                            @if($rental->rental_type === 'monthly')
                                <span class="badge bg-primary">รายเดือน</span>
                            @else
                                <span class="badge bg-success">ต่อข้อความ</span>
                            @endif
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-4 text-muted">สถานะ:</div>
                        <div class="col-8">
                            @if($rental->status === 'active')
                                <span class="badge bg-success">ACTIVE</span>
                            @elseif($rental->status === 'expired')
                                <span class="badge bg-warning">หมดอายุ</span>
                            @elseif($rental->status === 'cancelled')
                                <span class="badge bg-danger">ยกเลิก</span>
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
                                @if($rental->isActive())
                                    <span class="text-muted small">(เหลืออีก {{ $rental->days_remaining }} วัน)</span>
                                @endif
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
                                @if($rental->isActive())
                                    <form method="POST" action="{{ route('my-rentals.toggle-auto-renew', $rental->id) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary ms-2">
                                            เปลี่ยน
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        @if($rental->next_billing_date)
                            <div class="row mb-3">
                                <div class="col-4 text-muted">วันชำระครั้งถัดไป:</div>
                                <div class="col-8">{{ $rental->next_billing_date->format('d/m/Y') }}</div>
                            </div>
                        @endif
                    @endif

                    @if($rental->rental_type === 'per_message')
                        <div class="row mb-3">
                            <div class="col-4 text-muted">ข้อความทั้งหมด:</div>
                            <div class="col-8">
                                <strong>{{ number_format($rental->total_messages) }}</strong>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-4 text-muted">ยอดรวม:</div>
                            <div class="col-8">
                                <strong class="text-success">฿{{ number_format($rental->total_amount, 2) }}</strong>
                            </div>
                        </div>
                    @endif

                    <div class="row mb-3">
                        <div class="col-4 text-muted">อัตราค่าคอมมิชชั่น:</div>
                        <div class="col-8">{{ number_format($rental->commission_rate, 2) }}%</div>
                    </div>

                    @if($rental->cancelled_at)
                        <div class="row mb-3">
                            <div class="col-4 text-muted">วันที่ยกเลิก:</div>
                            <div class="col-8">{{ $rental->cancelled_at->format('d/m/Y H:i') }}</div>
                        </div>
                    @endif

                    @if($rental->cancellation_reason)
                        <div class="row">
                            <div class="col-4 text-muted">เหตุผลที่ยกเลิก:</div>
                            <div class="col-8">{{ $rental->cancellation_reason }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Transaction History -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">💳 ประวัติธุรกรรม</h5>
                    <span class="badge bg-secondary">{{ $transactions->total() }} รายการ</span>
                </div>
                <div class="card-body">
                    @forelse($transactions as $transaction)
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div>
                                <div class="fw-bold">
                                    @if($transaction->type === 'subscription')
                                        📅 ค่าเช่ารายเดือน
                                    @elseif($transaction->type === 'usage')
                                        💬 ค่าใช้จ่ายต่อข้อความ
                                    @elseif($transaction->type === 'refund')
                                        🔄 คืนเงิน
                                    @endif
                                </div>
                                <div class="small text-muted">
                                    {{ $transaction->created_at->format('d/m/Y H:i') }}
                                </div>
                                @if($transaction->description)
                                    <div class="small text-muted">{{ $transaction->description }}</div>
                                @endif
                            </div>
                            <div class="text-end">
                                <div class="fw-bold text-primary">
                                    ฿{{ number_format($transaction->amount, 2) }}
                                </div>
                                <span class="badge bg-{{ $transaction->status === 'completed' ? 'success' : 'warning' }}">
                                    {{ strtoupper($transaction->status) }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted text-center mb-0">ยังไม่มีธุรกรรม</p>
                    @endforelse
                </div>
                @if($transactions->hasPages())
                    <div class="card-footer">
                        {{ $transactions->links() }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Right Column: Actions & Summary -->
        <div class="col-lg-4">
            <!-- Actions Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">⚙️ การจัดการ</h6>
                </div>
                <div class="card-body">
                    @if($rental->isActive())
                        <button class="btn btn-danger w-100 mb-2" onclick="cancelRental()">
                            <i class="fas fa-times"></i> ยกเลิกการเช่า
                        </button>
                    @else
                        <a href="{{ route('marketplace.show', $rental->bot_profile_id) }}" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-redo"></i> เช่าอีกครั้ง
                        </a>
                    @endif

                    <a href="{{ route('my-rentals.index') }}" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-arrow-left"></i> กลับไปรายการ
                    </a>
                </div>
            </div>

            <!-- Summary Card -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">📊 สรุป</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3 pb-3 border-bottom">
                        <div class="small text-muted">ค่าใช้จ่ายรวม</div>
                        <div class="h4 mb-0 text-primary">
                            ฿{{ number_format($rental->total_amount ?? $rental->price, 2) }}
                        </div>
                    </div>

                    @if($rental->rental_type === 'per_message')
                        <div class="mb-3">
                            <div class="small text-muted">จำนวนข้อความ</div>
                            <div class="h5 mb-0">{{ number_format($rental->total_messages) }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="small text-muted">ราคาเฉลี่ย/ข้อความ</div>
                            <div class="h5 mb-0">฿{{ number_format($rental->price, 2) }}</div>
                        </div>
                    @endif

                    @if($rental->rental_type === 'monthly' && $rental->isActive())
                        <div class="mb-3">
                            <div class="small text-muted">ค่าใช้จ่ายต่อเดือน</div>
                            <div class="h5 mb-0">฿{{ number_format($rental->price, 2) }}</div>
                        </div>
                        <div class="progress" style="height: 25px;">
                            @php
                                $totalDays = $rental->start_date->diffInDays($rental->end_date);
                                $usedDays = $rental->start_date->diffInDays(now());
                                $percentage = $totalDays > 0 ? ($usedDays / $totalDays) * 100 : 0;
                            @endphp
                            <div class="progress-bar" role="progressbar"
                                 style="width: {{ min(100, $percentage) }}%">
                                {{ round($percentage) }}%
                            </div>
                        </div>
                        <div class="small text-muted text-center mt-1">
                            ใช้ไปแล้ว {{ $usedDays }}/{{ $totalDays }} วัน
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('my-rentals.cancel', $rental->id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">ยกเลิกการเช่า</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>คุณแน่ใจหรือไม่ที่จะยกเลิกการเช่านี้?</p>
                    <div class="alert alert-warning">
                        <small>
                            <i class="fas fa-exclamation-triangle"></i>
                            หลังจากยกเลิก คุณจะไม่สามารถใช้งานบอทนี้ได้อีก
                        </small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">เหตุผล (ไม่บังคับ)</label>
                        <textarea name="reason" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                    <button type="submit" class="btn btn-danger">ยืนยันยกเลิก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function cancelRental() {
    new bootstrap.Modal(document.getElementById('cancelModal')).show();
}
</script>
@endsection
