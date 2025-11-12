@extends('layouts.app')

@section('title', 'ตลาดบอท - Marketplace')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="text-center mb-4">
                <h1 class="display-5 fw-bold mb-2">
                    <i class="fas fa-store text-primary me-2"></i>
                    ตลาดบอทแชท
                </h1>
                <p class="lead text-muted">เช่าหรือซื้อบอทแชทอัจฉริยะพร้อมใช้งานทันที</p>
            </div>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form action="{{ route('chatbot.marketplace.index') }}" method="GET">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input type="text"
                                           name="search"
                                           class="form-control"
                                           placeholder="ค้นหาบอท..."
                                           value="{{ request('search') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select name="sort_by" class="form-select">
                                    <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>
                                        ใหม่ล่าสุด
                                    </option>
                                    <option value="popular" {{ request('sort_by') == 'popular' ? 'selected' : '' }}>
                                        ยอดนิยม
                                    </option>
                                    <option value="price" {{ request('sort_by') == 'price' ? 'selected' : '' }}>
                                        ราคา
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-2"></i>ค้นหา
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bots Grid -->
    <div class="row">
        @forelse($bots as $bot)
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm h-100 hover-shadow">
                <div class="card-body">
                    <!-- Badge -->
                    @if($bot->active_rentals_count > 10)
                    <span class="badge bg-danger position-absolute top-0 end-0 m-2">
                        <i class="fas fa-fire me-1"></i>ยอดนิยม
                    </span>
                    @endif

                    <!-- Bot Info -->
                    <div class="mb-3">
                        <h5 class="card-title mb-2">{{ $bot->name }}</h5>
                        <p class="text-muted small mb-2">
                            <i class="fas fa-user me-1"></i>{{ $bot->owner->name }}
                        </p>
                        <p class="text-muted small">
                            <i class="fas fa-microchip me-1"></i>
                            {{ $bot->provider->display_name }} - {{ $bot->model->display_name }}
                        </p>
                    </div>

                    <p class="card-text text-muted small mb-3">
                        {{ Str::limit($bot->description, 120) }}
                    </p>

                    <!-- Stats -->
                    <div class="d-flex justify-content-between text-muted small mb-3">
                        <div>
                            <i class="fas fa-users me-1"></i>
                            {{ $bot->active_rentals_count }} คนเช่า
                        </div>
                        <div>
                            <i class="fas fa-star text-warning me-1"></i>
                            4.8 (125)
                        </div>
                    </div>

                    <!-- Price -->
                    <div class="mb-3">
                        @if($bot->rental_price_per_month)
                        <div class="d-flex align-items-baseline">
                            <h4 class="text-primary mb-0">฿{{ number_format($bot->rental_price_per_month) }}</h4>
                            <span class="text-muted ms-2">/เดือน</span>
                        </div>
                        @endif
                        @if($bot->rental_price_per_message)
                        <div class="text-muted small">
                            หรือ ฿{{ number_format($bot->rental_price_per_message, 2) }}/ข้อความ
                        </div>
                        @endif
                    </div>

                    <!-- Action -->
                    <div class="d-grid">
                        <a href="{{ route('chatbot.marketplace.show', $bot->id) }}" class="btn btn-primary">
                            <i class="fas fa-info-circle me-2"></i>ดูรายละเอียด
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
                    <h4>ไม่พบบอทที่ตรงกับการค้นหา</h4>
                    <p class="text-muted">ลองค้นหาด้วยคำอื่นหรือเปลี่ยนตัวกรอง</p>
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

@push('styles')
<style>
.hover-shadow {
    transition: all 0.3s ease;
}
.hover-shadow:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}
</style>
@endpush
