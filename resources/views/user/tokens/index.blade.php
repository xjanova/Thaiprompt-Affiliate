@extends('layouts.user')

@section('title', 'TPIX Token Marketplace')

@section('styles')
<style>
.token-card {
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.token-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}
.token-logo {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    object-fit: cover;
}
.price-up { color: #10b981; }
.price-down { color: #ef4444; }
.badge-verified {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
.featured-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}
.search-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 60px 0;
    color: white;
    border-radius: 20px;
    margin-bottom: 30px;
}
.category-btn {
    border-radius: 25px;
    padding: 8px 20px;
    margin: 5px;
    border: 2px solid #e5e7eb;
    background: white;
    transition: all 0.3s;
}
.category-btn:hover, .category-btn.active {
    background: #667eea;
    color: white;
    border-color: #667eea;
}
</style>
@endsection

@section('content')
<div class="container-fluid">
    {{-- Search Hero --}}
    <div class="search-hero">
        <div class="container text-center">
            <h1 class="display-4 fw-bold mb-3">🪙 TPIX Token Marketplace</h1>
            <p class="lead mb-4">Discover, Trade & Create Custom Tokens on TPIX Blockchain</p>

            <div class="row justify-content-center">
                <div class="col-md-8">
                    <form method="GET" class="input-group input-group-lg shadow">
                        <input type="text" name="search" class="form-control" placeholder="Search tokens by name or symbol..." value="{{ request('search') }}" style="border-radius: 50px 0 0 50px;">
                        <button class="btn btn-warning" type="submit" style="border-radius: 0 50px 50px 0;">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </form>
                </div>
            </div>

            <div class="mt-4">
                <a href="{{ route('user.tokens.create') }}" class="btn btn-warning btn-lg shadow">
                    <i class="fas fa-plus-circle"></i> Create Your Token
                </a>
                <a href="{{ route('user.tokens.my-tokens') }}" class="btn btn-outline-light btn-lg ms-2">
                    <i class="fas fa-wallet"></i> My Tokens
                </a>
            </div>
        </div>
    </div>

    {{-- Categories --}}
    <div class="text-center mb-4">
        <button class="category-btn {{ !request('category') ? 'active' : '' }}" onclick="filterCategory('')">
            All
        </button>
        <button class="category-btn {{ request('category') == 'defi' ? 'active' : '' }}" onclick="filterCategory('defi')">
            🏦 DeFi
        </button>
        <button class="category-btn {{ request('category') == 'gamefi' ? 'active' : '' }}" onclick="filterCategory('gamefi')">
            🎮 GameFi
        </button>
        <button class="category-btn {{ request('category') == 'meme' ? 'active' : '' }}" onclick="filterCategory('meme')">
            😂 Meme
        </button>
        <button class="category-btn {{ request('category') == 'utility' ? 'active' : '' }}" onclick="filterCategory('utility')">
            🔧 Utility
        </button>
        <button class="category-btn {{ request('category') == 'nft' ? 'active' : '' }}" onclick="filterCategory('nft')">
            🎨 NFT
        </button>
    </div>

    {{-- Featured Tokens --}}
    @if($featuredTokens->isNotEmpty())
    <div class="mb-5">
        <h3 class="mb-3">⭐ Featured Tokens</h3>
        <div class="row g-4">
            @foreach($featuredTokens as $token)
            <div class="col-md-4">
                <div class="card token-card" style="position: relative;">
                    <span class="featured-badge text-white">FEATURED</span>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            @if($token->logo)
                                <img src="{{ asset('storage/' . $token->logo) }}" class="token-logo me-3">
                            @else
                                <div class="token-logo bg-gradient-primary text-white d-flex align-items-center justify-content-center me-3 fs-2">
                                    {{ substr($token->symbol, 0, 1) }}
                                </div>
                            @endif
                            <div class="flex-grow-1">
                                <h5 class="mb-1">
                                    {{ $token->name }}
                                    @if($token->is_verified)
                                        <span class="badge badge-verified text-white">
                                            <i class="fas fa-check-circle"></i> Verified
                                        </span>
                                    @endif
                                </h5>
                                <span class="text-muted">{{ $token->symbol }}</span>
                            </div>
                        </div>

                        <div class="row text-center mb-3">
                            <div class="col-6">
                                <small class="text-muted">Price</small>
                                <div class="fw-bold">{{ number_format($token->current_price_tpix, 4) }} TPIX</div>
                                @if($token->price_change_24h)
                                    <small class="{{ $token->price_change_24h > 0 ? 'price-up' : 'price-down' }}">
                                        {{ $token->price_change_24h > 0 ? '+' : '' }}{{ number_format($token->price_change_24h, 2) }}%
                                    </small>
                                @endif
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Market Cap</small>
                                <div class="fw-bold">{{ number_format($token->market_cap, 0) }} TPIX</div>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <a href="{{ route('user.tokens.show', $token->id) }}" class="btn btn-primary">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- All Tokens --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>🔥 All Tokens</h3>
        <div>
            <select class="form-select" onchange="sortTokens(this.value)">
                <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Latest</option>
                <option value="market_cap" {{ request('sort') == 'market_cap' ? 'selected' : '' }}>Market Cap</option>
                <option value="volume" {{ request('sort') == 'volume' ? 'selected' : '' }}>Volume</option>
                <option value="gainers" {{ request('sort') == 'gainers' ? 'selected' : '' }}>Top Gainers</option>
            </select>
        </div>
    </div>

    <div class="row g-4">
        @forelse($tokens as $token)
        <div class="col-md-6 col-lg-4 col-xl-3">
            <div class="card token-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        @if($token->logo)
                            <img src="{{ asset('storage/' . $token->logo) }}" class="token-logo me-3">
                        @else
                            <div class="token-logo bg-gradient-info text-white d-flex align-items-center justify-content-center me-3 fs-2">
                                {{ substr($token->symbol, 0, 1) }}
                            </div>
                        @endif
                        <div class="flex-grow-1">
                            <h6 class="mb-1">
                                {{ $token->name }}
                                @if($token->is_verified)
                                    <i class="fas fa-check-circle text-primary" title="Verified"></i>
                                @endif
                            </h6>
                            <small class="text-muted">{{ $token->symbol }}</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">Price</small>
                            <strong>{{ number_format($token->current_price_tpix, 4) }} TPIX</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">24h Change</small>
                            <span class="{{ ($token->price_change_24h ?? 0) > 0 ? 'price-up' : 'price-down' }}">
                                {{ ($token->price_change_24h ?? 0) > 0 ? '+' : '' }}{{ number_format($token->price_change_24h ?? 0, 2) }}%
                            </span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">Holders</small>
                            <span>{{ number_format($token->holders_count ?? 0) }}</span>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="{{ route('user.tokens.show', $token->id) }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-eye"></i> View
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-search fs-1 text-muted mb-3"></i>
                    <h5>No tokens found</h5>
                    <p class="text-muted">Try adjusting your search or filters</p>
                </div>
            </div>
        </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $tokens->links() }}
    </div>
</div>

<script>
function filterCategory(category) {
    const url = new URL(window.location.href);
    if (category) {
        url.searchParams.set('category', category);
    } else {
        url.searchParams.delete('category');
    }
    window.location.href = url.toString();
}

function sortTokens(sort) {
    const url = new URL(window.location.href);
    url.searchParams.set('sort', sort);
    window.location.href = url.toString();
}
</script>
@endsection
