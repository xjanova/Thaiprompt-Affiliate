@extends('layouts.admin-v3')

@section('title', 'จัดการร้านค้าแนะนำในหน้าแรก')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-3">จัดการร้านค้าแนะนำในหน้าแรก</h1>
            <p class="text-muted">เลือกร้านค้าที่จะแสดงในหน้าแรก (ร้านค้าที่มี 5 ดาวจะแสดงอัตโนมัติ)</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <!-- Featured Stores (Currently Showing) -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">ร้านค้าที่แสดงในหน้าแรก ({{ $featuredStores->count() }})</h5>
                </div>
                <div class="card-body">
                    @if($featuredStores->count() > 0)
                        <form action="{{ route('admin.featured-stores.update-order') }}" method="POST" id="orderForm">
                            @csrf
                            @method('PUT')
                            <div id="sortable-list" class="list-group">
                                @foreach($featuredStores as $store)
                                    <div class="list-group-item d-flex justify-content-between align-items-center mb-2" data-id="{{ $store->id }}">
                                        <div class="d-flex align-items-center">
                                            <span class="handle me-3" style="cursor: move;">
                                                <i class="fas fa-grip-vertical"></i>
                                            </span>
                                            @if($store->store_logo)
                                                <img src="{{ asset($store->store_logo) }}" alt="{{ $store->store_name }}" class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                            @else
                                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-center me-3" style="width: 50px; height: 50px;">
                                                    <span class="fw-bold">{{ substr($store->store_name, 0, 1) }}</span>
                                                </div>
                                            @endif
                                            <div>
                                                <h6 class="mb-0">{{ $store->store_name }}</h6>
                                                <small class="text-muted">
                                                    <i class="fas fa-star text-warning"></i> {{ number_format($store->rating_average, 1) }}
                                                    | <i class="fas fa-box"></i> {{ number_format($store->total_products) }} สินค้า
                                                </small>
                                                <input type="hidden" name="order[{{ $store->id }}]" value="{{ $store->featured_home_order }}" class="order-input">
                                            </div>
                                        </div>
                                        <form action="{{ route('admin.featured-stores.remove', $store->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบร้านค้านี้ออกจากหน้าแรก?')">
                                                <i class="fas fa-times"></i> ลบ
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                            <button type="submit" class="btn btn-success mt-3 w-100">
                                <i class="fas fa-save"></i> บันทึกลำดับการแสดง
                            </button>
                        </form>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-store fa-3x mb-3"></i>
                            <p>ยังไม่มีร้านค้าที่เลือกให้แสดงในหน้าแรก</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Available Stores -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">ร้านค้าที่สามารถเลือกได้ ({{ $availableStores->count() }})</h5>
                </div>
                <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                    @if($availableStores->count() > 0)
                        <div class="list-group">
                            @foreach($availableStores as $store)
                                <div class="list-group-item d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center">
                                        @if($store->store_logo)
                                            <img src="{{ asset($store->store_logo) }}" alt="{{ $store->store_name }}" class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                        @else
                                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-center me-3" style="width: 50px; height: 50px;">
                                                <span class="fw-bold">{{ substr($store->store_name, 0, 1) }}</span>
                                            </div>
                                        @endif
                                        <div>
                                            <h6 class="mb-0">{{ $store->store_name }}</h6>
                                            <small class="text-muted">
                                                <i class="fas fa-star text-warning"></i> {{ number_format($store->rating_average, 1) }}
                                                | <i class="fas fa-box"></i> {{ number_format($store->total_products) }} สินค้า
                                            </small>
                                        </div>
                                    </div>
                                    <form action="{{ route('admin.featured-stores.add', $store->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="fas fa-plus"></i> เพิ่ม
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-check-circle fa-3x mb-3"></i>
                            <p>ร้านค้าทั้งหมดถูกเลือกให้แสดงในหน้าแรกแล้ว</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- jQuery UI for Sortable -->
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<script>
$(function() {
    $("#sortable-list").sortable({
        handle: ".handle",
        update: function(event, ui) {
            // Update order inputs
            $("#sortable-list .list-group-item").each(function(index) {
                $(this).find('.order-input').val(index + 1);
            });
        }
    });
});
</script>

<style>
.handle {
    color: #6c757d;
}

.handle:hover {
    color: #495057;
}

.list-group-item {
    border: 1px solid #dee2e6;
    border-radius: 0.5rem !important;
    transition: all 0.3s ease;
}

.list-group-item:hover {
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
    transform: translateY(-2px);
}

.ui-sortable-helper {
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important;
    transform: rotate(2deg);
}
</style>
@endsection
