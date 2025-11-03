@extends('layouts.app')

@section('title', 'การเช่าของฉัน')

@section('content')
<div class="container py-4">
    <h2 class="mb-4">💼 การเช่าของฉัน</h2>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-primary mb-2" style="font-size: 2rem;">🤖</div>
                    <h3 class="mb-1">{{ $stats['active_rentals'] }}</h3>
                    <p class="text-muted small mb-0">การเช่าที่ active</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-success mb-2" style="font-size: 2rem;">💰</div>
                    <h3 class="mb-1">฿{{ number_format($stats['total_spent'], 2) }}</h3>
                    <p class="text-muted small mb-0">ใช้จ่ายทั้งหมด</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-info mb-2" style="font-size: 2rem;">💬</div>
                    <h3 class="mb-1">{{ number_format($stats['total_messages']) }}</h3>
                    <p class="text-muted small mb-0">ข้อความทั้งหมด</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-warning mb-2" style="font-size: 2rem;">📅</div>
                    <h3 class="mb-1">฿{{ number_format($stats['monthly_cost'], 2) }}</h3>
                    <p class="text-muted small mb-0">ค่าใช้จ่ายรายเดือน</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">ทุกสถานะ</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>หมดอายุ</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>ยกเลิก</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="type" class="form-select">
                        <option value="">ทุกประเภท</option>
                        <option value="monthly" {{ request('type') == 'monthly' ? 'selected' : '' }}>รายเดือน</option>
                        <option value="per_message" {{ request('type') == 'per_message' ? 'selected' : '' }}>ต่อข้อความ</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">กรอง</button>
                </div>
                <div class="col-md-4 text-end">
                    <a href="{{ route('my-rentals.transactions') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-history"></i> ประวัติธุรกรรม
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Rentals List -->
    @forelse($rentals as $rental)
        <div class="card mb-3">
            <div class="card-body">
                <div class="row align-items-center">
                    <!-- Bot Info -->
                    <div class="col-md-6">
                        <div class="d-flex align-items-center">
                            @if($rental->botProfile->avatar_url)
                                <img src="{{ $rental->botProfile->avatar_url }}"
                                     class="rounded-circle me-3"
                                     style="width: 60px; height: 60px; object-fit: cover;"
                                     alt="{{ $rental->botProfile->name }}">
                            @else
                                <div class="rounded-circle bg-primary text-white me-3 d-flex align-items-center justify-content-center"
                                     style="width: 60px; height: 60px; font-size: 1.5rem;">
                                    🤖
                                </div>
                            @endif

                            <div>
                                <h5 class="mb-1">{{ $rental->botProfile->name }}</h5>
                                <p class="text-muted small mb-1">
                                    <i class="fas fa-user"></i> {{ $rental->botProfile->owner->name }}
                                </p>
                                <div>
                                    @if($rental->rental_type === 'monthly')
                                        <span class="badge bg-primary">รายเดือน</span>
                                    @else
                                        <span class="badge bg-success">ต่อข้อความ</span>
                                    @endif

                                    @if($rental->status === 'active')
                                        <span class="badge bg-success">ACTIVE</span>
                                    @elseif($rental->status === 'expired')
                                        <span class="badge bg-warning">หมดอายุ</span>
                                    @elseif($rental->status === 'cancelled')
                                        <span class="badge bg-danger">ยกเลิก</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rental Details -->
                    <div class="col-md-4">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="fw-bold text-primary">฿{{ number_format($rental->price, 2) }}</div>
                                <div class="small text-muted">
                                    @if($rental->rental_type === 'monthly')
                                        /เดือน
                                    @else
                                        /ข้อความ
                                    @endif
                                </div>
                            </div>
                            <div class="col-6">
                                @if($rental->rental_type === 'monthly')
                                    <div class="fw-bold">{{ $rental->days_remaining }}</div>
                                    <div class="small text-muted">วันเหลือ</div>
                                @else
                                    <div class="fw-bold">{{ number_format($rental->total_messages) }}</div>
                                    <div class="small text-muted">ข้อความ</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="col-md-2 text-end">
                        <div class="btn-group">
                            <a href="{{ route('my-rentals.show', $rental->id) }}"
                               class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> ดู
                            </a>
                            @if($rental->isActive())
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="cancelRental({{ $rental->id }})">
                                    <i class="fas fa-times"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Additional Info for Monthly -->
                @if($rental->rental_type === 'monthly' && $rental->isActive())
                    <div class="mt-3 pt-3 border-top">
                        <div class="row">
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <i class="fas fa-calendar"></i>
                                    เริ่ม: {{ $rental->start_date->format('d/m/Y') }} |
                                    หมดอายุ: {{ $rental->end_date->format('d/m/Y') }}
                                </small>
                            </div>
                            <div class="col-md-6 text-end">
                                <form method="POST"
                                      action="{{ route('my-rentals.toggle-auto-renew', $rental->id) }}"
                                      style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                                        @if($rental->auto_renew)
                                            <i class="fas fa-toggle-on text-success"></i> ต่ออายุอัตโนมัติ: เปิด
                                        @else
                                            <i class="fas fa-toggle-off"></i> ต่ออายุอัตโนมัติ: ปิด
                                        @endif
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="alert alert-info text-center">
            <i class="fas fa-info-circle fa-2x mb-2"></i>
            <p class="mb-2">คุณยังไม่มีการเช่าบอท</p>
            <a href="{{ route('marketplace.index') }}" class="btn btn-primary">
                <i class="fas fa-store"></i> ไปตลาดบอท
            </a>
        </div>
    @endforelse

    <!-- Pagination -->
    <div class="d-flex justify-content-center">
        {{ $rentals->links() }}
    </div>
</div>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="cancelForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">ยกเลิกการเช่า</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>คุณแน่ใจหรือไม่ที่จะยกเลิกการเช่านี้?</p>
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
function cancelRental(rentalId) {
    const form = document.getElementById('cancelForm');
    form.action = `/my-rentals/${rentalId}/cancel`;
    new bootstrap.Modal(document.getElementById('cancelModal')).show();
}
</script>
@endsection
