@extends('layouts.admin')
@section('title', 'จัดการโปรโมชั่นพิเศษ')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">โปรโมชั่นพิเศษ</h1>
        <a href="{{ route('admin.hotels.special-offers.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> สร้างโปรโมชั่น
        </a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>ชื่อ</th><th>โรงแรม/ห้องพัก</th><th>ส่วนลด</th><th>ระยะเวลา</th><th>สถานะ</th><th>การจัดการ</th></tr>
                    </thead>
                    <tbody>
                        @forelse($offers as $offer)
                            <tr>
                                <td>
                                    <strong>{{ $offer->name }}</strong><br>
                                    <small class="text-muted">{{ $offer->code }}</small>
                                </td>
                                <td>
                                    @if($offer->hotel)
                                        <a href="{{ route('admin.hotels.show', $offer->hotel_id) }}">{{ $offer->hotel->name }}</a>
                                    @elseif($offer->roomType)
                                        {{ $offer->roomType->hotel->name }}<br><small>{{ $offer->roomType->name }}</small>
                                    @else
                                        <span class="text-muted">ทุกโรงแรม</span>
                                    @endif
                                </td>
                                <td>
                                    @if($offer->discount_type == 'percentage')
                                        {{ $offer->discount_value }}%
                                    @else
                                        ฿{{ number_format($offer->discount_value) }}
                                    @endif
                                </td>
                                <td>
                                    {{ $offer->start_date->format('d/m/Y') }}<br>
                                    <small>ถึง {{ $offer->end_date->format('d/m/Y') }}</small>
                                </td>
                                <td>
                                    @if($offer->is_active)
                                        <span class="badge badge-success">เปิดใช้งาน</span>
                                    @else
                                        <span class="badge badge-secondary">ปิดใช้งาน</span>
                                    @endif
                                    @if($offer->is_featured)
                                        <span class="badge badge-warning">แนะนำ</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.hotels.special-offers.edit', $offer->id) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('admin.hotels.special-offers.destroy', $offer->id) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('ยืนยันการลบ?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center">ยังไม่มีโปรโมชั่น</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($offers->hasPages())
            <div class="card-footer">{{ $offers->links() }}</div>
        @endif
    </div>
</div>
@endsection
