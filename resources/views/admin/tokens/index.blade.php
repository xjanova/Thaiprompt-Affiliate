@extends('layouts.admin')

@section('title', 'TPIX Token Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-coins text-primary"></i> TPIX Token Management
        </h1>
        <div>
            <a href="{{ route('admin.tokens.import-cmc') }}" class="btn btn-info">
                <i class="fas fa-download"></i> Import from CMC
            </a>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Tokens</h6>
                    <h2>{{ $stats['total'] ?? 0 }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Active Tokens</h6>
                    <h2>{{ $stats['active'] ?? 0 }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-title">Pending Approval</h6>
                    <h2>{{ $stats['pending'] ?? 0 }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Market Cap</h6>
                    <h2>{{ number_format($stats['total_market_cap'] ?? 0, 2) }} TPIX</h2>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Search tokens..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <option value="defi">DeFi</option>
                        <option value="gamefi">GameFi</option>
                        <option value="meme">Meme</option>
                        <option value="utility">Utility</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="sort" class="form-select">
                        <option value="created_at">Newest First</option>
                        <option value="market_cap">Market Cap</option>
                        <option value="holders">Holders</option>
                        <option value="volume">Volume</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Tokens Table --}}
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Token</th>
                            <th>Creator</th>
                            <th>Status</th>
                            <th>Market Cap</th>
                            <th>Holders</th>
                            <th>24h Volume</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tokens as $token)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    @if($token->logo)
                                        <img src="{{ asset('storage/' . $token->logo) }}" class="rounded-circle me-2" width="32" height="32">
                                    @else
                                        <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2" style="width:32px; height:32px;">
                                            {{ substr($token->symbol, 0, 1) }}
                                        </div>
                                    @endif
                                    <div>
                                        <strong>{{ $token->name }}</strong>
                                        <br><small class="text-muted">{{ $token->symbol }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                {{ $token->creator->name }}<br>
                                <small class="text-muted">{{ $token->creator->email }}</small>
                            </td>
                            <td>
                                <span class="badge bg-{{ $token->status == 'active' ? 'success' : ($token->status == 'pending' ? 'warning' : 'secondary') }}">
                                    {{ ucfirst($token->status) }}
                                </span>
                                @if($token->is_verified)
                                    <i class="fas fa-check-circle text-success" title="Verified"></i>
                                @endif
                            </td>
                            <td>{{ number_format($token->market_cap ?? 0, 2) }} TPIX</td>
                            <td>{{ number_format($token->holders_count ?? 0) }}</td>
                            <td>{{ number_format($token->volume_24h ?? 0, 2) }}</td>
                            <td>
                                <a href="{{ route('admin.tokens.show', $token->id) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($token->status == 'pending')
                                <form action="{{ route('admin.tokens.approve', $token->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve this token?')">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">No tokens found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $tokens->links() }}
        </div>
    </div>
</div>
@endsection
