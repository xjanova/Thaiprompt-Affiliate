@extends('layouts.admin')
@section('title', 'จัดการรีวิว')
@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4">จัดการรีวิว</h1>

    <div class="card">
        <div class="card-header">
            <form method="GET" class="form-inline">
                <select name="status" class="form-control mr-2">
                    <option value="">ทุกสถานะ</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>รออนุมัติ</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>อนุมัติแล้ว</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>ปฏิเสธ</option>
                </select>
                <select name="hotel_id" class="form-control mr-2">
                    <option value="">ทุกโรงแรม</option>
                    @foreach($hotels as $hotel)
                        <option value="{{ $hotel->id }}" {{ request('hotel_id') == $hotel->id ? 'selected' : '' }}>
                            {{ $hotel->name }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary">กรอง</button>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>โรงแรม</th><th>ผู้รีวิว</th><th>คะแนน</th><th>รีวิว</th><th>วันที่</th><th>สถานะ</th><th>การจัดการ</th></tr>
                    </thead>
                    <tbody>
                        @forelse($reviews as $review)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.hotels.show', $review->hotel->id) }}">{{ $review->hotel->name }}</a>
                                </td>
                                <td>{{ $review->user->name }}</td>
                                <td>
                                    <div class="text-warning">
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="fas fa-star{{ $i <= $review->overall_rating ? '' : '-o' }}"></i>
                                        @endfor
                                        <span class="ml-1">{{ number_format($review->overall_rating, 1) }}</span>
                                    </div>
                                </td>
                                <td>{{ Str::limit($review->content, 100) }}</td>
                                <td>{{ $review->created_at->format('d/m/Y') }}</td>
                                <td>
                                    @if($review->is_approved)
                                        <span class="badge badge-success">อนุมัติแล้ว</span>
                                    @else
                                        <span class="badge badge-warning">รออนุมัติ</span>
                                    @endif
                                    @if($review->is_featured)
                                        <span class="badge badge-primary">แนะนำ</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.hotels.reviews.show', $review->id) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if(!$review->is_approved)
                                        <button onclick="approveReview({{ $review->id }})" class="btn btn-sm btn-success">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button onclick="rejectReview({{ $review->id }})" class="btn btn-sm btn-danger">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center">ไม่พบรีวิว</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($reviews->hasPages())
            <div class="card-footer">
                {{ $reviews->links() }}
            </div>
        @endif
    </div>
</div>

<script>
function approveReview(id) {
    fetch(`/admin/hotels/reviews/${id}/approve`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'}
    }).then(() => location.reload());
}

function rejectReview(id) {
    if (confirm('คุณต้องการปฏิเสธรีวิวนี้?')) {
        fetch(`/admin/hotels/reviews/${id}/reject`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'}
        }).then(() => location.reload());
    }
}
</script>
@endsection
