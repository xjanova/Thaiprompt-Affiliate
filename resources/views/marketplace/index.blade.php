@extends('layouts.app')

@section('title', 'ตลาดบอท AI')

@section('content')
<div class="container py-4">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg p-6 mb-4 text-white">
        <h1 class="text-3xl font-bold mb-2">🤖 ตลาดบอท AI</h1>
        <p class="text-lg opacity-90">เช่าบอท AI คุณภาพสูงจากผู้สร้างทั่วโลก</p>
    </div>

    <!-- Search & Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('marketplace.index') }}" class="row g-3">
                <!-- Search -->
                <div class="col-md-4">
                    <input type="text"
                           name="search"
                           class="form-control"
                           placeholder="🔍 ค้นหาบอท..."
                           value="{{ request('search') }}">
                </div>

                <!-- Rental Type -->
                <div class="col-md-2">
                    <select name="rental_type" class="form-select">
                        <option value="">ทุกประเภท</option>
                        <option value="monthly" {{ request('rental_type') == 'monthly' ? 'selected' : '' }}>
                            รายเดือน
                        </option>
                        <option value="per_message" {{ request('rental_type') == 'per_message' ? 'selected' : '' }}>
                            ต่อข้อความ
                        </option>
                    </select>
                </div>

                <!-- Provider Filter -->
                <div class="col-md-2">
                    <select name="provider" class="form-select">
                        <option value="">ทุกผู้ให้บริการ</option>
                        @foreach($providers as $provider)
                            <option value="{{ $provider->id }}" {{ request('provider') == $provider->id ? 'selected' : '' }}>
                                {{ $provider->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Sort -->
                <div class="col-md-2">
                    <select name="sort_by" class="form-select">
                        <option value="newest" {{ request('sort_by') == 'newest' ? 'selected' : '' }}>ใหม่ล่าสุด</option>
                        <option value="popular" {{ request('sort_by') == 'popular' ? 'selected' : '' }}>ยอดนิยม</option>
                        <option value="price_low" {{ request('sort_by') == 'price_low' ? 'selected' : '' }}>ราคาต่ำ-สูง</option>
                        <option value="price_high" {{ request('sort_by') == 'price_high' ? 'selected' : '' }}>ราคาสูง-ต่ำ</option>
                    </select>
                </div>

                <!-- Submit -->
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">กรอง</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Count -->
    <div class="mb-3">
        <p class="text-muted">
            พบ <strong>{{ $bots->total() }}</strong> บอท
        </p>
    </div>

    <!-- Bots Grid -->
    <div class="row">
        @forelse($bots as $bot)
            <div class="col-md-4 col-lg-3 mb-4">
                <div class="card h-100 hover-shadow">
                    <!-- Bot Avatar -->
                    <div class="card-header text-center bg-light py-3">
                        @if($bot->avatar_url)
                            <img src="{{ $bot->avatar_url }}"
                                 alt="{{ $bot->name }}"
                                 class="rounded-circle"
                                 style="width: 80px; height: 80px; object-fit: cover;">
                        @else
                            <div class="d-flex align-items-center justify-content-center rounded-circle bg-primary text-white mx-auto"
                                 style="width: 80px; height: 80px; font-size: 2rem;">
                                🤖
                            </div>
                        @endif
                    </div>

                    <div class="card-body">
                        <h5 class="card-title mb-2">{{ $bot->name }}</h5>

                        <!-- Description -->
                        <p class="text-muted small mb-2" style="height: 60px; overflow: hidden;">
                            {{ Str::limit($bot->description, 100) }}
                        </p>

                        <!-- Provider -->
                        <div class="small text-muted mb-2">
                            <i class="fas fa-robot"></i> {{ $bot->provider->name }}
                        </div>

                        <!-- Owner -->
                        <div class="small text-muted mb-3">
                            <i class="fas fa-user"></i> {{ $bot->owner->name }}
                        </div>

                        <!-- Pricing -->
                        <div class="mb-3">
                            @if($bot->rental_price_per_month)
                                <div class="mb-1">
                                    <span class="fw-bold text-primary">฿{{ number_format($bot->rental_price_per_month, 2) }}</span>
                                    <span class="small text-muted">/เดือน</span>
                                </div>
                            @endif
                            @if($bot->rental_price_per_message)
                                <div>
                                    <span class="fw-bold text-success">฿{{ number_format($bot->rental_price_per_message, 2) }}</span>
                                    <span class="small text-muted">/ข้อความ</span>
                                </div>
                            @endif
                        </div>

                        <!-- Stats -->
                        <div class="d-flex justify-content-between text-muted small mb-3">
                            <span><i class="fas fa-users"></i> {{ $bot->rentals_count ?? 0 }}</span>
                            @if($bot->enable_knowledge_base)
                                <span><i class="fas fa-book text-info"></i> RAG</span>
                            @endif
                        </div>
                    </div>

                    <div class="card-footer bg-white">
                        <a href="{{ route('marketplace.show', $bot->id) }}" class="btn btn-primary btn-sm w-100">
                            ดูรายละเอียด
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-2x mb-2"></i>
                    <p class="mb-0">ไม่พบบอทที่ตรงกับเงื่อนไข</p>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-center">
        {{ $bots->links() }}
    </div>
</div>

<style>
.hover-shadow {
    transition: all 0.3s ease;
}
.hover-shadow:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}
</style>
@endsection
