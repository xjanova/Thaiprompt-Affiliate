@extends('layouts.admin')

@section('title', 'Marketplace Reviews')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Marketplace Reviews</h1>
        <a href="{{ route('admin.bot-automation.marketplace.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Marketplace
        </a>
    </div>

    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Reviews</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalReviews ?? '0' }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-comments fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Average Rating</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($avgRating ?? 0, 1) }}/5.0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-star fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Approval</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $pendingReviews ?? '0' }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">This Month</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $monthlyReviews ?? '0' }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Review Management</h6>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" placeholder="Search reviews..." id="searchReviews">
                </div>
                <div class="col-md-3">
                    <select class="form-control" id="filterRating">
                        <option value="">All Ratings</option>
                        <option value="5">5 Stars</option>
                        <option value="4">4 Stars</option>
                        <option value="3">3 Stars</option>
                        <option value="2">2 Stars</option>
                        <option value="1">1 Star</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-control" id="filterStatus">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Listing</th>
                            <th>User</th>
                            <th>Rating</th>
                            <th>Review</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reviews ?? [] as $review)
                        <tr>
                            <td>{{ $review->id }}</td>
                            <td>{{ $review->listing_title ?? 'N/A' }}</td>
                            <td>{{ $review->user_name ?? 'N/A' }}</td>
                            <td>
                                @for($i = 0; $i < 5; $i++)
                                    <i class="fas fa-star {{ $i < ($review->rating ?? 0) ? 'text-warning' : 'text-muted' }}"></i>
                                @endfor
                            </td>
                            <td>{{ Str::limit($review->comment ?? '', 50) }}</td>
                            <td>
                                @if($review->status == 'approved')
                                    <span class="badge badge-success">Approved</span>
                                @elseif($review->status == 'rejected')
                                    <span class="badge badge-danger">Rejected</span>
                                @else
                                    <span class="badge badge-warning">Pending</span>
                                @endif
                            </td>
                            <td>{{ isset($review->created_at) ? $review->created_at->format('M d, Y') : 'N/A' }}</td>
                            <td>
                                @if($review->status == 'pending')
                                <button class="btn btn-sm btn-success" onclick="approveReview({{ $review->id }})">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="rejectReview({{ $review->id }})">
                                    <i class="fas fa-times"></i>
                                </button>
                                @endif
                                <button class="btn btn-sm btn-info" onclick="viewReview({{ $review->id }})">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">No reviews found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(isset($reviews) && method_exists($reviews, 'links'))
            <div class="mt-3">
                {{ $reviews->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<script>
function approveReview(id) {
    if (confirm('Are you sure you want to approve this review?')) {
        // Submit approval request
        console.log('Approving review:', id);
    }
}

function rejectReview(id) {
    if (confirm('Are you sure you want to reject this review?')) {
        // Submit rejection request
        console.log('Rejecting review:', id);
    }
}

function viewReview(id) {
    // View review details
    console.log('Viewing review:', id);
}
</script>
@endsection
