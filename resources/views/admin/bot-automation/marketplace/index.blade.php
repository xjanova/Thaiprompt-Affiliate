@extends('layouts.admin')

@section('title', 'Bot Marketplace')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Bot Marketplace</h1>
        <a href="{{ route('admin.bot-automation.marketplace.listings.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-2"></i>Create New Listing
        </a>
    </div>

    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Listings</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalListings ?? '0' }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-store fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Subscriptions</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $activeSubscriptions ?? '0' }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Revenue</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">${{ number_format($totalRevenue ?? 0, 2) }}</div>
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
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Avg Rating</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($avgRating ?? 0, 1) }} <i class="fas fa-star text-warning"></i></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-star fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Marketplace Listings</h6>
            <div>
                <a href="{{ route('admin.bot-automation.marketplace.subscriptions.index') }}" class="btn btn-sm btn-info">
                    <i class="fas fa-list mr-1"></i>View Subscriptions
                </a>
                <a href="{{ route('admin.bot-automation.marketplace.reviews.index') }}" class="btn btn-sm btn-secondary">
                    <i class="fas fa-comments mr-1"></i>View Reviews
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" placeholder="Search listings..." id="searchListings">
                </div>
                <div class="col-md-3">
                    <select class="form-control" id="filterStatus">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="pending">Pending</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-control" id="filterCategory">
                        <option value="">All Categories</option>
                        <option value="sales">Sales</option>
                        <option value="support">Support</option>
                        <option value="marketing">Marketing</option>
                    </select>
                </div>
            </div>

            <div class="row">
                @forelse($listings ?? [] as $listing)
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">{{ $listing->title ?? 'N/A' }}</h5>
                            <p class="card-text text-muted">{{ Str::limit($listing->description ?? '', 100) }}</p>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge badge-{{ $listing->status == 'active' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($listing->status ?? 'N/A') }}
                                </span>
                                <span class="text-primary font-weight-bold">${{ number_format($listing->price ?? 0, 2) }}</span>
                            </div>
                            <div class="mb-2">
                                @for($i = 0; $i < 5; $i++)
                                    <i class="fas fa-star {{ $i < ($listing->rating ?? 0) ? 'text-warning' : 'text-muted' }}"></i>
                                @endfor
                                <small class="text-muted">({{ $listing->reviews_count ?? 0 }} reviews)</small>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="{{ route('admin.bot-automation.marketplace.listings.edit', $listing->id ?? 0) }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <button class="btn btn-sm btn-info" onclick="viewListing({{ $listing->id ?? 0 }})">
                                <i class="fas fa-eye"></i> View
                            </button>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12 text-center text-muted py-5">
                    <i class="fas fa-store fa-3x mb-3"></i>
                    <p>No listings available. Create your first listing to get started!</p>
                </div>
                @endforelse
            </div>

            @if(isset($listings) && method_exists($listings, 'links'))
            <div class="mt-3">
                {{ $listings->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
