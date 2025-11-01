@extends('layouts.user')

@section('title', 'ระบบรักษายอด - Membership Retention')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header mb-4">
                <h1 class="page-title">
                    <i class="fas fa-heartbeat text-danger"></i>
                    ระบบรักษายอด
                </h1>
                <p class="text-muted">Membership Retention System</p>
            </div>
        </div>
    </div>

    {{-- Life Power Widget --}}
    <div class="row">
        <div class="col-12">
            @include('components.life-power-widget')
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-primary">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ $statistics['consecutive_months'] }}</h3>
                    <p>เดือนติดต่อกัน</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ $statistics['achieved_count'] }}</h3>
                    <p>ผ่านเกณฑ์</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-danger">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ $statistics['failed_count'] }}</h3>
                    <p>ไม่ผ่านเกณฑ์</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ $statistics['repair_count'] }}</h3>
                    <p>ซ่อมสิทธิ์</p>
                </div>
            </div>
        </div>
    </div>

    {{-- History Table --}}
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-history"></i>
                        ประวัติการรักษายอด
                    </h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>เดือน</th>
                                    <th>แต้มที่ต้องการ</th>
                                    <th>แต้มที่ได้</th>
                                    <th>สถานะ</th>
                                    <th>วันที่บรรลุ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($history as $record)
                                <tr>
                                    <td>{{ $record->period_month }}</td>
                                    <td>{{ number_format($record->required_points, 2) }}</td>
                                    <td>{{ number_format($record->earned_points, 2) }}</td>
                                    <td>
                                        @if($record->status === 'achieved')
                                            <span class="badge bg-success">
                                                <i class="fas fa-check"></i> ผ่านเกณฑ์
                                            </span>
                                        @elseif($record->status === 'failed')
                                            <span class="badge bg-danger">
                                                <i class="fas fa-times"></i> ไม่ผ่านเกณฑ์
                                            </span>
                                        @else
                                            <span class="badge bg-warning">
                                                <i class="fas fa-clock"></i> รอดำเนินการ
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $record->achieved_at ? $record->achieved_at->format('d/m/Y') : '-' }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">
                                        <i class="fas fa-inbox"></i>
                                        ยังไม่มีประวัติการรักษายอด
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
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 15px;
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #fff;
}

.stat-info h3 {
    margin: 0;
    font-size: 32px;
    font-weight: 700;
    color: #333;
}

.stat-info p {
    margin: 0;
    font-size: 14px;
    color: #666;
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

.badge {
    padding: 8px 12px;
    font-size: 12px;
    font-weight: 600;
}
</style>
@endsection
