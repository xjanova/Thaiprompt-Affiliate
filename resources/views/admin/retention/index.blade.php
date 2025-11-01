@extends('layouts.admin')

@section('title', 'จัดการระบบรักษายอด - Admin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header mb-4">
                <h1 class="page-title">
                    <i class="fas fa-heartbeat text-danger"></i>
                    จัดการระบบรักษายอด
                </h1>
                <p class="text-muted">Membership Retention Management</p>
            </div>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card bg-primary">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ number_format($totalUsers) }}</h3>
                    <p>สมาชิกทั้งหมด</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-success">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ number_format($activeUsers) }}</h3>
                    <p>ใช้งานอยู่</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-danger">
                <div class="stat-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ number_format($expiredUsers) }}</h3>
                    <p>หมดอายุ</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-warning">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ number_format($expiringUsers) }}</h3>
                    <p>ใกล้หมดอายุ (7 วัน)</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Settings Panel --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-cog"></i>
                        การตั้งค่าระบบ
                    </h3>
                </div>
                <div class="card-body">
                    <form id="settingsForm">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>แต้มขั้นต่ำต่อเดือน</label>
                                    <input type="number" class="form-control" name="minimum_points_per_month"
                                           value="{{ $settings['minimum_points_per_month'] ?? 1000 }}" step="0.01">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>ค่าซ่อมต่อแต้ม (เท่า)</label>
                                    <input type="number" class="form-control" name="repair_cost_per_point"
                                           value="{{ $settings['repair_cost_per_point'] ?? 1.5 }}" step="0.1">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>ส่วนลดเติมล่วงหน้า (0.9 = ลด 10%)</label>
                                    <input type="number" class="form-control" name="advance_renewal_discount"
                                           value="{{ $settings['advance_renewal_discount'] ?? 0.9 }}" step="0.01" min="0" max="1">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>ระยะเวลาผ่อนผัน (วัน)</label>
                                    <input type="number" class="form-control" name="grace_period_days"
                                           value="{{ $settings['grace_period_days'] ?? 3 }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>แจ้งเตือนก่อนหมดอายุ (วัน)</label>
                                    <input type="number" class="form-control" name="warning_days_before_expiry"
                                           value="{{ $settings['warning_days_before_expiry'] ?? 7 }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group mb-3">
                                    <label>เปิดใช้งานระบบ</label>
                                    <select class="form-control" name="enable_retention_system">
                                        <option value="1" {{ ($settings['enable_retention_system'] ?? 'true') === 'true' ? 'selected' : '' }}>เปิด</option>
                                        <option value="0" {{ ($settings['enable_retention_system'] ?? 'true') === 'false' ? 'selected' : '' }}>ปิด</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group mb-3">
                                    <label>บล็อกคอมมิชชั่นหากหมดอายุ</label>
                                    <select class="form-control" name="block_commission_if_expired">
                                        <option value="1" {{ ($settings['block_commission_if_expired'] ?? 'true') === 'true' ? 'selected' : '' }}>เปิด</option>
                                        <option value="0" {{ ($settings['block_commission_if_expired'] ?? 'true') === 'false' ? 'selected' : '' }}>ปิด</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                บันทึกการตั้งค่า
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Manual Operations --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-tools"></i>
                        การดำเนินการด้วยตนเอง
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <button class="btn btn-info w-100 mb-2" onclick="processRenewal()">
                                <i class="fas fa-sync"></i>
                                ประมวลผลรายเดือน
                            </button>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-warning w-100 mb-2" onclick="expireUsers()">
                                <i class="fas fa-clock"></i>
                                ตรวจสอบและหมดอายุ
                            </button>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('admin.retention.users') }}" class="btn btn-primary w-100 mb-2">
                                <i class="fas fa-users"></i>
                                จัดการสมาชิก
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Statuses --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list"></i>
                        สถานะล่าสุด
                    </h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ชื่อ</th>
                                    <th>อีเมล</th>
                                    <th>สถานะ</th>
                                    <th>แต้มปัจจุบัน</th>
                                    <th>แต้มที่ต้องการ</th>
                                    <th>วันต่ออายุ</th>
                                    <th>วันที่เหลือ</th>
                                    <th>การดำเนินการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentStatuses as $status)
                                <tr>
                                    <td>{{ $status->user->name }}</td>
                                    <td>{{ $status->user->email }}</td>
                                    <td>
                                        @if($status->status === 'active')
                                            <span class="badge bg-success">ใช้งานอยู่</span>
                                        @elseif($status->status === 'expired')
                                            <span class="badge bg-danger">หมดอายุ</span>
                                        @else
                                            <span class="badge bg-secondary">ไม่ใช้งาน</span>
                                        @endif
                                    </td>
                                    <td>{{ number_format($status->current_points, 2) }}</td>
                                    <td>{{ number_format($status->required_points, 2) }}</td>
                                    <td>{{ $status->next_renewal_date ? $status->next_renewal_date->format('d/m/Y') : '-' }}</td>
                                    <td>
                                        @php
                                            $days = $status->daysRemaining();
                                            $color = $status->getHealthColor();
                                        @endphp
                                        <span class="badge bg-{{ $color === 'green' ? 'success' : ($color === 'yellow' ? 'warning' : ($color === 'orange' ? 'warning' : 'danger')) }}">
                                            {{ $days }} วัน
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.retention.users.show', $status->user_id) }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                        <i class="fas fa-inbox"></i>
                                        ยังไม่มีข้อมูล
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stat-card {
    background: #fff;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 20px;
    color: #fff;
}

.stat-card.bg-primary { background: linear-gradient(135deg, #3b82f6, #2563eb); }
.stat-card.bg-success { background: linear-gradient(135deg, #10b981, #059669); }
.stat-card.bg-danger { background: linear-gradient(135deg, #ef4444, #dc2626); }
.stat-card.bg-warning { background: linear-gradient(135deg, #f59e0b, #d97706); }

.stat-icon {
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
}

.stat-info h3 {
    margin: 0;
    font-size: 36px;
    font-weight: 700;
}

.stat-info p {
    margin: 0;
    font-size: 14px;
    opacity: 0.9;
}

.card {
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    border: none;
}

.card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    border-radius: 15px 15px 0 0 !important;
    padding: 20px;
}

.card-title {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
}
</style>

<script>
document.getElementById('settingsForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());

    // Convert string to number/boolean
    data.minimum_points_per_month = parseFloat(data.minimum_points_per_month);
    data.repair_cost_per_point = parseFloat(data.repair_cost_per_point);
    data.advance_renewal_discount = parseFloat(data.advance_renewal_discount);
    data.grace_period_days = parseInt(data.grace_period_days);
    data.warning_days_before_expiry = parseInt(data.warning_days_before_expiry);
    data.enable_retention_system = data.enable_retention_system === '1';
    data.block_commission_if_expired = data.block_commission_if_expired === '1';

    try {
        const response = await fetch('{{ route('admin.retention.settings.update') }}', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            alert('✅ ' + result.message);
        } else {
            alert('❌ ' + result.message);
        }
    } catch (error) {
        alert('❌ เกิดข้อผิดพลาด: ' + error.message);
    }
});

async function processRenewal() {
    if (!confirm('คุณต้องการประมวลผลรายเดือนใช่หรือไม่?')) {
        return;
    }

    try {
        const response = await fetch('{{ route('admin.retention.process-renewal') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        const result = await response.json();

        if (result.success) {
            alert('✅ ประมวลผลสำเร็จ\n\nดำเนินการ: ' + result.data.processed + '\nสำเร็จ: ' + result.data.succeeded + '\nล้มเหลว: ' + result.data.failed);
            window.location.reload();
        } else {
            alert('❌ ' + result.message);
        }
    } catch (error) {
        alert('❌ เกิดข้อผิดพลาด: ' + error.message);
    }
}

async function expireUsers() {
    if (!confirm('คุณต้องการตรวจสอบและหมดอายุผู้ใช้ใช่หรือไม่?')) {
        return;
    }

    try {
        const response = await fetch('{{ route('admin.retention.expire-users') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        const result = await response.json();

        if (result.success) {
            alert('✅ ตรวจสอบสำเร็จ\n\nดำเนินการ: ' + result.data.processed + '\nหมดอายุ: ' + result.data.expired);
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
