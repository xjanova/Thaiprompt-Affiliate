@extends('layouts.app')

@section('title', 'จัดการบอทแชท')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-robot text-primary me-2"></i>
                        จัดการบอทแชท
                    </h2>
                    <p class="text-muted mb-0">ระบบให้เช่าบอทแชทอัจฉริยะแบบไฮบริด</p>
                </div>
                <div>
                    <a href="{{ route('chatbot.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>สร้างบอทใหม่
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">บอททั้งหมด</p>
                            <h3 class="mb-0">{{ $stats['total_bots'] }}</h3>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-robot fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">บอทที่ใช้งาน</p>
                            <h3 class="mb-0">{{ $stats['active_bots'] }}</h3>
                        </div>
                        <div class="text-success">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">การเช่าทั้งหมด</p>
                            <h3 class="mb-0">{{ $stats['total_rentals'] }}</h3>
                        </div>
                        <div class="text-info">
                            <i class="fas fa-handshake fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">รายได้ทั้งหมด</p>
                            <h3 class="mb-0">฿{{ number_format($stats['total_revenue'], 2) }}</h3>
                        </div>
                        <div class="text-warning">
                            <i class="fas fa-coins fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bots List -->
    <div class="row">
        @forelse($bots as $bot)
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="flex-grow-1">
                            <h5 class="card-title mb-1">{{ $bot->name }}</h5>
                            <p class="text-muted small mb-0">
                                <i class="fas fa-microchip me-1"></i>
                                {{ $bot->provider->display_name }} - {{ $bot->model->display_name }}
                            </p>
                        </div>
                        <div>
                            @if($bot->is_active)
                                <span class="badge bg-success">ใช้งาน</span>
                            @else
                                <span class="badge bg-secondary">ปิดใช้งาน</span>
                            @endif
                        </div>
                    </div>

                    <p class="card-text text-muted small mb-3">
                        {{ Str::limit($bot->description, 100) }}
                    </p>

                    <div class="d-flex justify-content-between text-muted small mb-3">
                        <div>
                            <i class="fas fa-comments me-1"></i>
                            {{ $bot->conversations_count }} บทสนทนา
                        </div>
                        <div>
                            <i class="fas fa-users me-1"></i>
                            {{ $bot->active_rentals_count }} การเช่า
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="{{ route('chatbot.show', $bot->id) }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-cog me-1"></i>จัดการ
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-robot fa-4x text-muted mb-3"></i>
                    <h4>ยังไม่มีบอท</h4>
                    <p class="text-muted">เริ่มต้นสร้างบอทแชทอัจฉริยะของคุณเลย</p>
                    <a href="{{ route('chatbot.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>สร้างบอทแรกของคุณ
                    </a>
                </div>
            </div>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($bots->hasPages())
    <div class="row">
        <div class="col-12">
            {{ $bots->links() }}
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Add any JavaScript here
});
</script>
@endpush
