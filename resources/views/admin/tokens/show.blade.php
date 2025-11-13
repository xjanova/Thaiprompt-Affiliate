@extends('layouts.admin')

@section('title', $token->name . ' - Token Details')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <a href="{{ route('admin.crypto.tokens.index') }}" class="btn btn-outline-secondary me-3">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            @if($token->logo)
                <img src="{{ asset('storage/' . $token->logo) }}" class="rounded-circle me-3" width="48" height="48">
            @endif
            <div>
                <h1 class="h3 mb-0">{{ $token->name }}</h1>
                <span class="text-muted">{{ $token->symbol }}</span>
            </div>
        </div>
        <div>
            <span class="badge bg-{{ $token->status == 'active' ? 'success' : ($token->status == 'pending' ? 'warning' : 'secondary') }} fs-6">
                {{ ucfirst($token->status) }}
            </span>
            @if($token->is_verified)
                <i class="fas fa-check-circle text-success fs-5 ms-2" title="Verified"></i>
            @endif
        </div>
    </div>

    <div class="row">
        {{-- Left Column --}}
        <div class="col-md-8">
            {{-- Token Information --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Token Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Contract Address</label>
                            <div class="d-flex align-items-center">
                                <code class="me-2">{{ $token->contract_address ?? 'Not deployed' }}</code>
                                @if($token->contract_address)
                                    <button class="btn btn-sm btn-outline-primary" onclick="copyToClipboard('{{ $token->contract_address }}')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Total Supply</label>
                            <div><strong>{{ number_format($token->total_supply, 2) }} {{ $token->symbol }}</strong></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Decimals</label>
                            <div>{{ $token->decimals }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Current Price</label>
                            <div>{{ number_format($token->current_price_tpix ?? 0, 8) }} TPIX</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Market Cap</label>
                            <div>{{ number_format($token->market_cap ?? 0, 2) }} TPIX</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Holders</label>
                            <div>{{ number_format($token->holders_count ?? 0) }}</div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="text-muted small">Description</label>
                            <div>{{ $token->description }}</div>
                        </div>
                    </div>

                    {{-- Features --}}
                    <div class="mt-3">
                        <label class="text-muted small">Features</label>
                        <div>
                            @if($token->is_mintable)
                                <span class="badge bg-info me-2">Mintable</span>
                            @endif
                            @if($token->is_burnable)
                                <span class="badge bg-danger me-2">Burnable</span>
                            @endif
                            @if($token->is_pausable)
                                <span class="badge bg-warning me-2">Pausable</span>
                            @endif
                            @if($token->is_freezable)
                                <span class="badge bg-primary me-2">Freezable</span>
                            @endif
                        </div>
                    </div>

                    {{-- Social Links --}}
                    @if($token->website || $token->twitter_handle || $token->telegram_group)
                    <div class="mt-3">
                        <label class="text-muted small">Social Links</label>
                        <div>
                            @if($token->website)
                                <a href="{{ $token->website }}" target="_blank" class="btn btn-sm btn-outline-primary me-2">
                                    <i class="fas fa-globe"></i> Website
                                </a>
                            @endif
                            @if($token->twitter_handle)
                                <a href="https://twitter.com/{{ $token->twitter_handle }}" target="_blank" class="btn btn-sm btn-outline-info me-2">
                                    <i class="fab fa-twitter"></i> Twitter
                                </a>
                            @endif
                            @if($token->telegram_group)
                                <a href="{{ $token->telegram_group }}" target="_blank" class="btn btn-sm btn-outline-primary me-2">
                                    <i class="fab fa-telegram"></i> Telegram
                                </a>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Coin Control Actions --}}
            @if($token->status == 'active')
            <div class="card mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-shield-alt"></i> Coin Control Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @if($token->is_mintable)
                        <div class="col-md-6">
                            <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#mintModal">
                                <i class="fas fa-plus-circle"></i> Mint Tokens
                            </button>
                        </div>
                        @endif
                        @if($token->is_burnable)
                        <div class="col-md-6">
                            <button class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#burnModal">
                                <i class="fas fa-fire"></i> Burn Tokens
                            </button>
                        </div>
                        @endif
                        @if($token->is_freezable)
                        <div class="col-md-6">
                            <button class="btn btn-warning w-100" data-bs-toggle="modal" data-bs-target="#freezeModal">
                                <i class="fas fa-snowflake"></i> Freeze Address
                            </button>
                        </div>
                        @endif
                        @if($token->is_pausable)
                        <div class="col-md-6">
                            <button class="btn btn-info w-100" data-bs-toggle="modal" data-bs-target="#pauseModal">
                                <i class="fas fa-pause-circle"></i> Pause Contract
                            </button>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Recent Transactions --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-exchange-alt"></i> Recent Transactions</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>TX Hash</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Amount</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($token->transfers()->latest()->limit(10)->get() as $transfer)
                                <tr>
                                    <td><code>{{ substr($transfer->tx_hash, 0, 10) }}...</code></td>
                                    <td><small>{{ substr($transfer->from_address, 0, 10) }}...</small></td>
                                    <td><small>{{ substr($transfer->to_address, 0, 10) }}...</small></td>
                                    <td>{{ number_format($transfer->amount, 4) }}</td>
                                    <td><small>{{ $transfer->created_at->diffForHumans() }}</small></td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-3">No transactions yet</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column --}}
        <div class="col-md-4">
            {{-- Creator Info --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user"></i> Creator</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($token->creator->name) }}" class="rounded-circle me-3" width="48">
                        <div>
                            <strong>{{ $token->creator->name }}</strong><br>
                            <small class="text-muted">{{ $token->creator->email }}</small>
                        </div>
                    </div>
                    <a href="{{ route('admin.users.show', $token->creator_id) }}" class="btn btn-sm btn-outline-primary w-100">
                        View Profile
                    </a>
                </div>
            </div>

            {{-- Admin Actions --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-tools"></i> Admin Actions</h5>
                </div>
                <div class="card-body">
                    @if($token->status == 'pending')
                    <form action="{{ route('admin.crypto.tokens.approve', $token->id) }}" method="POST" class="mb-2">
                        @csrf
                        <button type="submit" class="btn btn-success w-100" onclick="return confirm('Approve this token?')">
                            <i class="fas fa-check"></i> Approve Token
                        </button>
                    </form>
                    <button class="btn btn-danger w-100 mb-2" data-bs-toggle="modal" data-bs-target="#rejectModal">
                        <i class="fas fa-times"></i> Reject Token
                    </button>
                    @endif

                    @if(!$token->is_verified && $token->status == 'active')
                    <form action="{{ route('admin.crypto.tokens.verify', $token->id) }}" method="POST" class="mb-2">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-check-circle"></i> Verify Token
                        </button>
                    </form>
                    @endif

                    @if(!$token->is_featured)
                    <form action="{{ route('admin.crypto.tokens.feature', $token->id) }}" method="POST" class="mb-2">
                        @csrf
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="fas fa-star"></i> Feature Token
                        </button>
                    </form>
                    @else
                    <form action="{{ route('admin.crypto.tokens.unfeature', $token->id) }}" method="POST" class="mb-2">
                        @csrf
                        <button type="submit" class="btn btn-outline-warning w-100">
                            <i class="far fa-star"></i> Unfeature Token
                        </button>
                    </form>
                    @endif

                    <a href="{{ route('admin.crypto.tokens.edit', $token->id) }}" class="btn btn-outline-primary w-100 mb-2">
                        <i class="fas fa-edit"></i> Edit Token
                    </a>

                    @if($token->cmc_id)
                    <form action="{{ route('admin.crypto.tokens.sync-cmc', $token->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-info w-100">
                            <i class="fas fa-sync"></i> Sync with CMC
                        </button>
                    </form>
                    @else
                    <button class="btn btn-outline-info w-100" data-bs-toggle="modal" data-bs-target="#linkCMCModal">
                        <i class="fas fa-link"></i> Link to CMC
                    </button>
                    @endif
                </div>
            </div>

            {{-- Statistics --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">24h Volume</small>
                        <div><strong>{{ number_format($token->volume_24h ?? 0, 2) }} TPIX</strong></div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">24h Transactions</small>
                        <div><strong>{{ number_format($token->tx_count_24h ?? 0) }}</strong></div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Total Minted</small>
                        <div><strong>{{ number_format($token->total_minted ?? 0, 2) }}</strong></div>
                    </div>
                    <div>
                        <small class="text-muted">Total Burned</small>
                        <div><strong>{{ number_format($token->total_burned ?? 0, 2) }}</strong></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text);
    alert('Copied to clipboard!');
}
</script>
@endsection
