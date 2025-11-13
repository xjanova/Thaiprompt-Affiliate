@extends('layouts.app')

@section('title', 'Liquidity Pools - TPIX DEX')

@section('content')
<div class="container py-4">
    <!-- Hero Section -->
    <div class="text-center mb-5" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); padding: 60px 20px; border-radius: 20px; color: white;">
        <h1 class="display-4 fw-bold mb-3">
            <i class="fas fa-swimming-pool me-2"></i>
            Liquidity Pools
        </h1>
        <p class="lead mb-0">เพิ่ม Liquidity และรับค่าธรรมเนียม 0.25% จากทุกการแลกเปลี่ยน</p>
        <div class="mt-4">
            <a href="{{ route('user.dex.add-liquidity') }}" class="btn btn-light btn-lg fw-bold px-5">
                <i class="fas fa-plus-circle me-2"></i>
                เพิ่ม Liquidity
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="fas fa-chart-line fa-2x text-primary mb-3"></i>
                    <h3 class="mb-0" id="totalTVL">-</h3>
                    <small class="text-muted">Total TVL</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="fas fa-exchange-alt fa-2x text-success mb-3"></i>
                    <h3 class="mb-0" id="totalVolume24h">-</h3>
                    <small class="text-muted">Volume 24h</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="fas fa-coins fa-2x text-warning mb-3"></i>
                    <h3 class="mb-0" id="totalPools">-</h3>
                    <small class="text-muted">Active Pools</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="fas fa-fire fa-2x text-danger mb-3"></i>
                    <h3 class="mb-0" id="totalFees">-</h3>
                    <small class="text-muted">Fees Collected</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <input type="text" class="form-control" id="searchPool" placeholder="🔍 ค้นหา Pool...">
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="sortBy">
                        <option value="tvl">TVL สูงสุด</option>
                        <option value="volume">Volume 24h สูงสุด</option>
                        <option value="apy">APY สูงสุด</option>
                        <option value="newest">Pool ใหม่ล่าสุด</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" id="myPoolsOnly">
                        <label class="form-check-label" for="myPoolsOnly">
                            Pool ของฉันเท่านั้น
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pools List -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0 fw-bold">
                <i class="fas fa-list me-2"></i>
                All Liquidity Pools
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 30%;">Pool</th>
                            <th class="text-end">TVL</th>
                            <th class="text-end">Volume 24h</th>
                            <th class="text-end">APY</th>
                            <th class="text-end">My Liquidity</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="poolsList">
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- My Positions -->
    <div class="card border-0 shadow-sm mt-5">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0 fw-bold">
                <i class="fas fa-wallet me-2"></i>
                My Liquidity Positions
            </h5>
        </div>
        <div class="card-body p-0">
            <div id="myPositions">
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-folder-open fa-3x mb-3 opacity-25"></i>
                    <p class="mb-0">คุณยังไม่มี Liquidity Position</p>
                    <a href="{{ route('user.dex.add-liquidity') }}" class="btn btn-primary mt-3">
                        เพิ่ม Liquidity เลย
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pool Details Modal -->
<div class="modal fade" id="poolDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="poolPairName">-</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="poolDetailsContent">
                <!-- Loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<style>
.pool-logo {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 2px solid white;
}

.pool-logo-group {
    display: flex;
    align-items: center;
}

.pool-logo-group .pool-logo:not(:first-child) {
    margin-left: -12px;
}

.apy-badge {
    font-size: 1.1rem;
    font-weight: 700;
}

tr.my-pool {
    background: #f0f9ff;
}
</style>

@push('scripts')
<script>
let allPools = [];
let myPositions = [];

$(document).ready(function() {
    loadStats();
    loadPools();
    loadMyPositions();

    $('#searchPool').on('input', debounce(filterPools, 300));
    $('#sortBy').change(filterPools);
    $('#myPoolsOnly').change(filterPools);
});

function loadStats() {
    $.ajax({
        url: '/api/v1/tpix/dex/statistics',
        method: 'GET',
        success: function(response) {
            const data = response.data;
            $('#totalTVL').text(formatNumber(data.total_tvl) + ' TPIX');
            $('#totalVolume24h').text(formatNumber(data.total_volume_24h) + ' TPIX');
            $('#totalPools').text(data.total_pools);
            $('#totalFees').text(formatNumber(data.total_fees_collected) + ' TPIX');
        }
    });
}

function loadPools() {
    $.ajax({
        url: '/api/v1/tpix/dex/pools',
        method: 'GET',
        data: { per_page: 100 },
        success: function(response) {
            allPools = response.data;
            displayPools(allPools);
        }
    });
}

function displayPools(pools) {
    if (pools.length === 0) {
        $('#poolsList').html(`
            <tr>
                <td colspan="6" class="text-center py-5 text-muted">
                    <i class="fas fa-inbox fa-3x mb-3 opacity-25"></i>
                    <p class="mb-0">ไม่พบ Liquidity Pool</p>
                </td>
            </tr>
        `);
        return;
    }

    const html = pools.map(pool => {
        const hasPosition = myPositions.some(p => p.pool.id === pool.id);

        return `
            <tr class="${hasPosition ? 'my-pool' : ''}">
                <td>
                    <div class="d-flex align-items-center">
                        <div class="pool-logo-group me-3">
                            <img src="${pool.token_a.logo || '/images/default-token.png'}" class="pool-logo">
                            <img src="${pool.token_b.logo || '/images/default-token.png'}" class="pool-logo">
                        </div>
                        <div>
                            <div class="fw-bold">${pool.pair}</div>
                            <small class="text-muted">Fee: ${pool.fee_percentage}%</small>
                        </div>
                    </div>
                </td>
                <td class="text-end fw-bold">฿${formatNumber(pool.tvl)}</td>
                <td class="text-end">฿${formatNumber(pool.volume_24h)}</td>
                <td class="text-end">
                    <span class="badge bg-success apy-badge">${pool.apy.toFixed(2)}%</span>
                </td>
                <td class="text-end">
                    ${hasPosition ? '<span class="text-success">✓ มี Position</span>' : '<span class="text-muted">-</span>'}
                </td>
                <td class="text-center">
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary btn-sm" onclick="viewPoolDetails(${pool.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                        <a href="/user/dex/add-liquidity?pool=${pool.id}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add
                        </a>
                    </div>
                </td>
            </tr>
        `;
    }).join('');

    $('#poolsList').html(html);
}

function loadMyPositions() {
    $.ajax({
        url: '/api/v1/tpix/dex/my/positions',
        method: 'GET',
        success: function(response) {
            myPositions = response.data;

            if (myPositions.length > 0) {
                displayMyPositions(myPositions);
            }
        }
    });
}

function displayMyPositions(positions) {
    const html = positions.map(position => `
        <div class="list-group-item">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <div class="d-flex align-items-center">
                        <div class="pool-logo-group me-3">
                            <img src="${position.pool.token_a.logo || '/images/default-token.png'}" class="pool-logo">
                            <img src="${position.pool.token_b.logo || '/images/default-token.png'}" class="pool-logo">
                        </div>
                        <div>
                            <div class="fw-bold">${position.pool.pair}</div>
                            <small class="text-muted">Pool Share: ${position.share_percentage.toFixed(4)}%</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 text-center">
                    <small class="text-muted d-block">Pooled ${position.token_a.symbol}</small>
                    <div class="fw-bold">${formatNumber(position.token_a.amount)}</div>
                </div>
                <div class="col-md-2 text-center">
                    <small class="text-muted d-block">Pooled ${position.token_b.symbol}</small>
                    <div class="fw-bold">${formatNumber(position.token_b.amount)}</div>
                </div>
                <div class="col-md-2 text-center">
                    <small class="text-muted d-block">Value</small>
                    <div class="fw-bold">฿${formatNumber(position.current_value_tpix)}</div>
                </div>
                <div class="col-md-2 text-center">
                    <small class="text-muted d-block">Fees Earned</small>
                    <div class="text-success fw-bold">+฿${formatNumber(position.unclaimed_fees)}</div>
                </div>
                <div class="col-md-1 text-end">
                    <div class="btn-group-vertical btn-group-sm">
                        <a href="/user/dex/remove-liquidity/${position.id}" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-minus"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    `).join('');

    $('#myPositions').html(`<div class="list-group list-group-flush">${html}</div>`);
}

function viewPoolDetails(poolId) {
    $.ajax({
        url: `/api/v1/tpix/dex/pools/${poolId}`,
        method: 'GET',
        success: function(response) {
            const pool = response.data;

            $('#poolPairName').text(pool.pair);

            const html = `
                <div class="row g-4">
                    <div class="col-md-6">
                        <h6 class="fw-bold mb-3">Pool Information</h6>
                        <table class="table table-sm">
                            <tr>
                                <td class="text-muted">Total TVL</td>
                                <td class="text-end fw-bold">฿${formatNumber(pool.tvl)}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Volume 24h</td>
                                <td class="text-end">฿${formatNumber(pool.volume_24h)}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Volume 7d</td>
                                <td class="text-end">฿${formatNumber(pool.volume_7d)}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Fees Collected</td>
                                <td class="text-end">฿${formatNumber(pool.fees_collected)}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Total Swaps</td>
                                <td class="text-end">${pool.tx_count.toLocaleString()}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">APY</td>
                                <td class="text-end"><span class="badge bg-success">${pool.apy.toFixed(2)}%</span></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold mb-3">Reserves</h6>
                        <table class="table table-sm">
                            <tr>
                                <td class="text-muted">${pool.token_a.symbol}</td>
                                <td class="text-end fw-bold">${formatNumber(pool.reserves.token_a)}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">${pool.token_b.symbol}</td>
                                <td class="text-end fw-bold">${formatNumber(pool.reserves.token_b)}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Current Price</td>
                                <td class="text-end">1 ${pool.token_a.symbol} = ${parseFloat(pool.price).toFixed(6)} ${pool.token_b.symbol}</td>
                            </tr>
                        </table>

                        <div class="d-grid gap-2 mt-4">
                            <a href="/user/dex/add-liquidity?pool=${pool.id}" class="btn btn-primary">
                                <i class="fas fa-plus-circle me-2"></i>
                                Add Liquidity
                            </a>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <h6 class="fw-bold mb-3">Recent Swaps</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Direction</th>
                                <th class="text-end">Amount In</th>
                                <th class="text-end">Amount Out</th>
                                <th class="text-end">Price Impact</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${pool.recent_swaps.map(swap => `
                                <tr>
                                    <td><small>${swap.direction}</small></td>
                                    <td class="text-end"><small>${formatNumber(swap.amount_in)}</small></td>
                                    <td class="text-end"><small>${formatNumber(swap.amount_out)}</small></td>
                                    <td class="text-end">
                                        <small class="${swap.price_impact > 5 ? 'text-danger' : 'text-success'}">
                                            ${swap.price_impact.toFixed(2)}%
                                        </small>
                                    </td>
                                    <td><small>${timeAgo(swap.timestamp)}</small></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;

            $('#poolDetailsContent').html(html);
            $('#poolDetailsModal').modal('show');
        }
    });
}

function filterPools() {
    let filtered = [...allPools];

    // Search
    const search = $('#searchPool').val().toLowerCase();
    if (search) {
        filtered = filtered.filter(pool =>
            pool.pair.toLowerCase().includes(search) ||
            pool.token_a.symbol.toLowerCase().includes(search) ||
            pool.token_b.symbol.toLowerCase().includes(search)
        );
    }

    // My pools only
    if ($('#myPoolsOnly').is(':checked')) {
        filtered = filtered.filter(pool =>
            myPositions.some(p => p.pool.id === pool.id)
        );
    }

    // Sort
    const sortBy = $('#sortBy').val();
    filtered.sort((a, b) => {
        switch(sortBy) {
            case 'tvl': return parseFloat(b.tvl) - parseFloat(a.tvl);
            case 'volume': return parseFloat(b.volume_24h) - parseFloat(a.volume_24h);
            case 'apy': return b.apy - a.apy;
            case 'newest': return b.id - a.id;
            default: return 0;
        }
    });

    displayPools(filtered);
}

function formatNumber(num) {
    return parseFloat(num).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function timeAgo(timestamp) {
    const seconds = Math.floor((new Date() - new Date(timestamp)) / 1000);
    if (seconds < 60) return seconds + 's ago';
    if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
    if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
    return Math.floor(seconds / 86400) + 'd ago';
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
</script>
@endpush
@endsection
