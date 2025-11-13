@extends('layouts.app')

@section('title', 'รายละเอียด Token - TPIX')

@section('content')
<div class="container py-4">
    <div class="row">
        <!-- Left Column - Token Info -->
        <div class="col-lg-8">
            <!-- Token Header -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <img src="{{ $token->logo ? asset('storage/' . $token->logo) : asset('images/default-token.png') }}"
                                 alt="{{ $token->symbol }}"
                                 class="rounded-circle"
                                 width="80"
                                 height="80">
                        </div>
                        <div class="col">
                            <div class="d-flex align-items-center mb-2">
                                <h2 class="mb-0 me-3">{{ $token->name }}</h2>
                                <span class="badge bg-primary">{{ $token->symbol }}</span>
                                @if($token->is_verified)
                                    <i class="fas fa-check-circle text-primary ms-2" title="Verified Token"></i>
                                @endif
                            </div>
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <span class="badge bg-secondary">{{ ucfirst($token->type) }}</span>
                                <span class="badge bg-{{ $token->status === 'active' ? 'success' : 'warning' }}">
                                    {{ ucfirst($token->status) }}
                                </span>
                                @if($token->is_featured)
                                    <span class="badge bg-warning">
                                        <i class="fas fa-star"></i> Featured
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="col-auto">
                            <h3 class="text-primary mb-0">฿{{ number_format($token->current_price_tpix, 2) }}</h3>
                            <small class="text-muted">~${{ number_format($token->current_price_usd, 2) }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <small class="text-muted d-block mb-1">Market Cap</small>
                            <h5 class="mb-0">฿{{ number_format($token->market_cap, 0) }}</h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <small class="text-muted d-block mb-1">24h Volume</small>
                            <h5 class="mb-0">฿{{ number_format($token->volume_24h, 0) }}</h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <small class="text-muted d-block mb-1">Holders</small>
                            <h5 class="mb-0">{{ number_format($token->holders_count) }}</h5>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Description -->
            @if($token->description)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-info-circle me-2"></i>
                        เกี่ยวกับ Token
                    </h5>
                </div>
                <div class="card-body">
                    <p class="mb-0">{{ $token->description }}</p>
                    @if($token->website)
                        <div class="mt-3">
                            <a href="{{ $token->website }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-globe me-1"></i> Website
                            </a>
                        </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Token Details -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-list me-2"></i>
                        รายละเอียด
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <td class="text-muted" width="40%">Contract Address</td>
                            <td class="fw-bold">
                                @if($token->contract_address)
                                    <a href="{{ config('tpix.blockchain.explorer_url') }}/address/{{ $token->contract_address }}"
                                       target="_blank" class="text-decoration-none">
                                        {{ substr($token->contract_address, 0, 10) }}...{{ substr($token->contract_address, -8) }}
                                        <i class="fas fa-external-link-alt ms-1 small"></i>
                                    </a>
                                @else
                                    <span class="text-muted">Not deployed</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Total Supply</td>
                            <td class="fw-bold">{{ number_format($token->total_supply, 0) }} {{ $token->symbol }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Circulating Supply</td>
                            <td class="fw-bold">{{ number_format($token->circulating_supply, 0) }} {{ $token->symbol }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Decimals</td>
                            <td class="fw-bold">{{ $token->decimals }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Creator</td>
                            <td class="fw-bold">{{ $token->creator->name ?? 'Unknown' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Created At</td>
                            <td class="fw-bold">{{ $token->created_at->format('d M Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Features -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-cogs me-2"></i>
                        Token Features
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-{{ $token->is_burnable ? 'check-circle text-success' : 'times-circle text-muted' }} me-2"></i>
                                <span>Burnable</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-{{ $token->is_mintable ? 'check-circle text-success' : 'times-circle text-muted' }} me-2"></i>
                                <span>Mintable</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-{{ $token->is_pausable ? 'check-circle text-success' : 'times-circle text-muted' }} me-2"></i>
                                <span>Pausable</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-{{ $token->can_freeze_addresses ? 'check-circle text-success' : 'times-circle text-muted' }} me-2"></i>
                                <span>Freezable Addresses</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-exchange-alt me-2"></i>
                        Recent Transactions
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Type</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Amount</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody id="recentTransactions">
                                <tr>
                                    <td colspan="5" class="text-center py-3">
                                        <div class="spinner-border spinner-border-sm text-primary"></div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Actions -->
        <div class="col-lg-4">
            <!-- Your Balance -->
            <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <div class="card-body text-center">
                    <small class="opacity-75 d-block mb-2">Your Balance</small>
                    <h2 class="mb-0" id="userBalance">-</h2>
                    <small class="opacity-75">{{ $token->symbol }}</small>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 fw-bold">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#buyModal">
                            <i class="fas fa-shopping-cart me-2"></i>
                            Buy {{ $token->symbol }}
                        </button>
                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#sellModal">
                            <i class="fas fa-dollar-sign me-2"></i>
                            Sell {{ $token->symbol }}
                        </button>
                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#transferModal">
                            <i class="fas fa-paper-plane me-2"></i>
                            Transfer
                        </button>
                        <a href="{{ route('user.dex.swap') }}?from={{ $token->id }}" class="btn btn-outline-primary">
                            <i class="fas fa-exchange-alt me-2"></i>
                            Swap on DEX
                        </a>
                    </div>
                </div>
            </div>

            <!-- Price Chart (Placeholder) -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 fw-bold">Price Chart</h6>
                </div>
                <div class="card-body">
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-chart-line fa-3x mb-3 opacity-25"></i>
                        <p class="small mb-0">Price chart coming soon</p>
                    </div>
                </div>
            </div>

            <!-- Links -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 fw-bold">Links</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if($token->contract_address)
                            <a href="{{ config('tpix.blockchain.explorer_url') }}/token/{{ $token->contract_address }}"
                               target="_blank" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-search me-2"></i>
                                View on Explorer
                            </a>
                        @endif
                        @if($token->website)
                            <a href="{{ $token->website }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-globe me-2"></i>
                                Website
                            </a>
                        @endif
                        @if($token->whitepaper_url)
                            <a href="{{ $token->whitepaper_url }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-file-pdf me-2"></i>
                                Whitepaper
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<!-- Buy Modal -->
<div class="modal fade" id="buyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Buy {{ $token->symbol }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="buyForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Amount (TPIX)</label>
                        <input type="number" class="form-control" name="amount_tpix" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">You will receive</label>
                        <input type="text" class="form-control" readonly id="buyReceiveAmount">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Buy Now</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    loadUserBalance();
    loadRecentTransactions();

    $('#buyForm').submit(function(e) {
        e.preventDefault();
        // Buy logic here
    });
});

function loadUserBalance() {
    $.ajax({
        url: '/api/v1/tpix/balances',
        method: 'GET',
        success: function(response) {
            const balance = response.data.find(b => b.token_id === {{ $token->id }});
            $('#userBalance').text(balance ? parseFloat(balance.balance).toFixed(4) : '0.0000');
        }
    });
}

function loadRecentTransactions() {
    $.ajax({
        url: '/api/v1/tpix/tokens/{{ $token->id }}/transactions',
        method: 'GET',
        data: { per_page: 10 },
        success: function(response) {
            displayTransactions(response.data);
        }
    });
}

function displayTransactions(transactions) {
    if (transactions.length === 0) {
        $('#recentTransactions').html(`
            <tr>
                <td colspan="5" class="text-center py-3 text-muted">
                    No transactions yet
                </td>
            </tr>
        `);
        return;
    }

    const html = transactions.map(tx => `
        <tr>
            <td><span class="badge bg-primary">${tx.type}</span></td>
            <td><small>${tx.from_address.substring(0, 8)}...</small></td>
            <td><small>${tx.to_address.substring(0, 8)}...</small></td>
            <td class="fw-bold">${parseFloat(tx.amount).toFixed(4)}</td>
            <td><small>${timeAgo(tx.created_at)}</small></td>
        </tr>
    `).join('');

    $('#recentTransactions').html(html);
}

function timeAgo(timestamp) {
    const seconds = Math.floor((new Date() - new Date(timestamp)) / 1000);
    if (seconds < 60) return seconds + 's ago';
    if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
    if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
    return Math.floor(seconds / 86400) + 'd ago';
}
</script>
@endpush
@endsection
