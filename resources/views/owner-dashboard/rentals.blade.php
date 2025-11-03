@extends('layouts.app')

@section('title', 'จัดการผู้เช่า')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>👥 จัดการผู้เช่า</h2>
        <a href="{{ route('owner-dashboard.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> กลับ
        </a>
    </div>

    <!-- Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="mb-1">{{ $stats['total_rentals'] }}</h4>
                    <p class="text-muted small mb-0">การเช่าทั้งหมด</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="mb-1 text-success">{{ $stats['active_rentals'] }}</h4>
                    <p class="text-muted small mb-0">Active</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="mb-1 text-primary">{{ $stats['monthly_rentals'] }}</h4>
                    <p class="text-muted small mb-0">รายเดือน</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="mb-1 text-info">{{ $stats['per_message_rentals'] }}</h4>
                    <p class="text-muted small mb-0">ต่อข้อความ</p>
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
                    <select name="type" class="form-select">
                        <option value="">ทุกประเภท</option>
                        <option value="monthly" {{ request('type') == 'monthly' ? 'selected' : '' }}>รายเดือน</option>
                        <option value="per_message" {{ request('type') == 'per_message' ? 'selected' : '' }}>ต่อข้อความ</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> กรอง
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Rentals Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">รายการผู้เช่า</h5>
            <span class="badge bg-secondary">{{ $rentals->total() }} รายการ</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ผู้เช่า</th>
                        <th>บอท</th>
                        <th>ประเภท</th>
                        <th class="text-end">ราคา</th>
                        <th>วันที่เริ่ม</th>
                        <th>สถานะ</th>
                        <th class="text-end">Usage</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rentals as $rental)
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $rental->renter->name }}</div>
                                <div class="small text-muted">{{ $rental->renter->email }}</div>
                            </td>
                            <td>
                                <div class="fw-bold">{{ $rental->botProfile->name }}</div>
                            </td>
                            <td>
                                @if($rental->rental_type === 'monthly')
                                    <span class="badge bg-primary">รายเดือน</span>
                                @else
                                    <span class="badge bg-success">ต่อข้อความ</span>
                                @endif
                            </td>
                            <td class="text-end">฿{{ number_format($rental->price, 2) }}</td>
                            <td>
                                {{ $rental->start_date->format('d/m/Y') }}
                                @if($rental->rental_type === 'monthly' && $rental->isActive())
                                    <div class="small text-muted">
                                        เหลือ {{ $rental->days_remaining }} วัน
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($rental->status === 'active')
                                    <span class="badge bg-success">ACTIVE</span>
                                @elseif($rental->status === 'expired')
                                    <span class="badge bg-warning">หมดอายุ</span>
                                @elseif($rental->status === 'cancelled')
                                    <span class="badge bg-danger">ยกเลิก</span>
                                @endif
                                @if($rental->rental_type === 'monthly' && $rental->auto_renew && $rental->isActive())
                                    <div class="small text-success">
                                        <i class="fas fa-sync"></i> Auto-renew
                                    </div>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($rental->rental_type === 'per_message')
                                    <div class="fw-bold">{{ number_format($rental->total_messages) }}</div>
                                    <div class="small text-muted">ข้อความ</div>
                                    <div class="small text-success">
                                        ฿{{ number_format($rental->total_amount, 2) }}
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">ยังไม่มีผู้เช่า</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($rentals->hasPages())
            <div class="card-footer">
                {{ $rentals->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
