@extends('layouts.admin')

@section('title', 'Marketplace Subscriptions')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Marketplace Subscriptions</h1>
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
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Subscriptions</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalSubscriptions ?? '0' }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $activeSubscriptions ?? '0' }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Monthly Revenue</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">${{ number_format($monthlyRevenue ?? 0, 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Expiring Soon</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $expiringSubscriptions ?? '0' }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Subscription Management</h6>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" placeholder="Search subscriptions..." id="searchSubscriptions">
                </div>
                <div class="col-md-3">
                    <select class="form-control" id="filterStatus">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="expired">Expired</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-control" id="filterListing">
                        <option value="">All Listings</option>
                        @foreach($listings ?? [] as $listing)
                        <option value="{{ $listing->id }}">{{ $listing->title }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Listing</th>
                            <th>Status</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subscriptions ?? [] as $subscription)
                        <tr>
                            <td>{{ $subscription->id }}</td>
                            <td>{{ $subscription->user_name ?? 'N/A' }}</td>
                            <td>{{ $subscription->listing_title ?? 'N/A' }}</td>
                            <td>
                                @if($subscription->status == 'active')
                                    <span class="badge badge-success">Active</span>
                                @elseif($subscription->status == 'expired')
                                    <span class="badge badge-danger">Expired</span>
                                @elseif($subscription->status == 'cancelled')
                                    <span class="badge badge-secondary">Cancelled</span>
                                @else
                                    <span class="badge badge-warning">{{ ucfirst($subscription->status) }}</span>
                                @endif
                            </td>
                            <td>{{ isset($subscription->start_date) ? date('M d, Y', strtotime($subscription->start_date)) : 'N/A' }}</td>
                            <td>{{ isset($subscription->end_date) ? date('M d, Y', strtotime($subscription->end_date)) : 'N/A' }}</td>
                            <td>${{ number_format($subscription->amount ?? 0, 2) }}</td>
                            <td>
                                <a href="{{ route('admin.bot-automation.marketplace.subscriptions.show', $subscription->id ?? 0) }}"
                                   class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($subscription->status == 'active')
                                <button class="btn btn-sm btn-warning" onclick="cancelSubscription({{ $subscription->id }})">
                                    <i class="fas fa-ban"></i>
                                </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">No subscriptions found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(isset($subscriptions) && method_exists($subscriptions, 'links'))
            <div class="mt-3">
                {{ $subscriptions->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<script>
function cancelSubscription(id) {
    if (confirm('Are you sure you want to cancel this subscription?')) {
        // Submit cancellation request
        console.log('Cancelling subscription:', id);
    }
}
</script>
@endsection
