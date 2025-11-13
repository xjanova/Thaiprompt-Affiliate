@extends('layouts.app')

@section('title', 'Token Staking - TPIX')

@section('content')
<div class="container py-4">
    <!-- Hero Section -->
    <div class="text-center mb-5" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); padding: 60px 20px; border-radius: 20px; color: white;">
        <h1 class="display-4 fw-bold mb-3">
            <i class="fas fa-coins me-2"></i>
            Token Staking
        </h1>
        <p class="lead mb-0">Stake Token ของคุณและรับรางวัลทุกชั่วโมง</p>
        <p class="small opacity-75 mt-2">รองรับ 5 ระยะเวลา Lock | APY สูงสุด 200%</p>
    </div>

    <!-- Stats Overview -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center h-100">
                <div class="card-body">
                    <i class="fas fa-layer-group fa-2x text-primary mb-3"></i>
                    <h4 class="mb-0" id="totalStaked">-</h4>
                    <small class="text-muted">Total Staked</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center h-100">
                <div class="card-body">
                    <i class="fas fa-users fa-2x text-success mb-3"></i>
                    <h4 class="mb-0" id="totalStakers">-</h4>
                    <small class="text-muted">Active Stakers</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center h-100">
                <div class="card-body">
                    <i class="fas fa-percentage fa-2x text-warning mb-3"></i>
                    <h4 class="mb-0" id="avgAPY">-</h4>
                    <small class="text-muted">Average APY</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center h-100">
                <div class="card-body">
                    <i class="fas fa-gift fa-2x text-danger mb-3"></i>
                    <h4 class="mb-0" id="totalRewards">-</h4>
                    <small class="text-muted">Total Rewards</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Staking Pools -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-swimming-pool me-2"></i>
                            Staking Pools
                        </h5>
                        <div class="btn-group btn-group-sm" role="group">
                            <input type="radio" class="btn-check" name="poolFilter" id="allPools" value="all" checked>
                            <label class="btn btn-outline-primary" for="allPools">All</label>

                            <input type="radio" class="btn-check" name="poolFilter" id="activePools" value="active">
                            <label class="btn btn-outline-primary" for="activePools">Active</label>

                            <input type="radio" class="btn-check" name="poolFilter" id="myPools" value="mine">
                            <label class="btn btn-outline-primary" for="myPools">My Pools</label>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div id="stakingPools">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- My Stakes Sidebar -->
        <div class="col-lg-4">
            <!-- My Total Staking -->
            <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <div class="card-body text-center">
                    <small class="opacity-75 d-block mb-2">My Total Staked</small>
                    <h2 class="mb-0" id="myTotalStaked">0.00</h2>
                    <small class="opacity-75">TPIX</small>
                    <hr class="my-3 opacity-25">
                    <div class="d-flex justify-content-between">
                        <div>
                            <small class="opacity-75 d-block">Pending Rewards</small>
                            <div class="fw-bold" id="myPendingRewards">0.00</div>
                        </div>
                        <div>
                            <small class="opacity-75 d-block">Total Earned</small>
                            <div class="fw-bold" id="myTotalEarned">0.00</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- My Active Stakes -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 fw-bold">My Active Stakes</h6>
                </div>
                <div class="card-body p-0">
                    <div id="myStakes">
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-inbox fa-2x mb-2 opacity-25"></i>
                            <p class="small mb-0">No active stakes</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stake Modal -->
<div class="modal fade" id="stakeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Stake Tokens</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="stakeForm">
                @csrf
                <input type="hidden" id="poolId" name="pool_id">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Pool:</span>
                            <strong id="modalPoolName">-</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>APY:</span>
                            <strong class="text-success" id="modalPoolAPY">-</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Lock Period:</span>
                            <strong id="modalLockPeriod">-</strong>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Amount to Stake</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="stakeAmount" name="amount" step="0.00000001" required>
                            <span class="input-group-text" id="modalTokenSymbol">TOKEN</span>
                        </div>
                        <div class="form-text">
                            Available: <span id="modalAvailableBalance">0.00</span>
                            <button type="button" class="btn btn-link btn-sm p-0 ms-2" onclick="$('#stakeAmount').val($('#modalAvailableBalance').text())">Max</button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Estimated Rewards</label>
                        <div class="card bg-light">
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col">
                                        <small class="text-muted d-block">Daily</small>
                                        <strong id="dailyReward">-</strong>
                                    </div>
                                    <div class="col">
                                        <small class="text-muted d-block">Monthly</small>
                                        <strong id="monthlyReward">-</strong>
                                    </div>
                                    <div class="col">
                                        <small class="text-muted d-block">Yearly</small>
                                        <strong id="yearlyReward">-</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <small>
                            การถอนก่อนกำหนดจะมีค่าปรับ 10% ของจำนวนที่ Stake
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Stake Now</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Unstake Modal -->
<div class="modal fade" id="unstakeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Unstake Tokens</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="unstakeForm">
                @csrf
                <input type="hidden" id="unstakeStakeId" name="stake_id">
                <div class="modal-body">
                    <div class="alert alert-info" id="unstakeInfo"></div>

                    <div class="mb-3">
                        <label class="form-label">Amount to Unstake</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="unstakeAmount" name="amount" step="0.00000001" required>
                            <span class="input-group-text" id="unstakeTokenSymbol">TOKEN</span>
                        </div>
                        <div class="form-text">
                            Staked: <span id="unstakeStakedAmount">0.00</span>
                        </div>
                    </div>

                    <div id="earlyWithdrawalWarning" class="alert alert-danger" style="display: none;">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>Early Withdrawal!</strong>
                        <p class="mb-0">You will be charged 10% penalty for early withdrawal.</p>
                        <div class="mt-2">
                            <small>Penalty: <strong id="penaltyAmount">-</strong></small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Unstake</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.pool-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.pool-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
}

.apy-display {
    font-size: 2rem;
    font-weight: 700;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.stake-card {
    border-left: 4px solid #667eea;
}
</style>

@push('scripts')
<script>
let stakingPools = [];
let myStakes = [];
let currentPool = null;

$(document).ready(function() {
    loadStats();
    loadStakingPools();
    loadMyStakes();

    $('input[name="poolFilter"]').change(filterPools);

    $('#stakeAmount').on('input', calculateRewards);

    $('#stakeForm').submit(function(e) {
        e.preventDefault();
        executeStake();
    });

    $('#unstakeForm').submit(function(e) {
        e.preventDefault();
        executeUnstake();
    });

    $('#unstakeAmount').on('input', calculatePenalty);
});

function loadStats() {
    // Load global staking stats
    $('#totalStaked').text('Loading...');
    $('#totalStakers').text('Loading...');
    $('#avgAPY').text('Loading...');
    $('#totalRewards').text('Loading...');
}

function loadStakingPools() {
    $.ajax({
        url: '/api/v1/tpix/staking/pools',
        method: 'GET',
        success: function(response) {
            stakingPools = response.data;
            displayPools(stakingPools);
        }
    });
}

function displayPools(pools) {
    if (pools.length === 0) {
        $('#stakingPools').html(`
            <div class="text-center py-5 text-muted">
                <i class="fas fa-inbox fa-3x mb-3 opacity-25"></i>
                <p class="mb-0">No staking pools available</p>
            </div>
        `);
        return;
    }

    const html = pools.map(pool => `
        <div class="pool-card border-bottom p-4">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <img src="${pool.token.logo || '/images/default-token.png'}"
                         alt="${pool.token.symbol}"
                         class="rounded-circle mb-2"
                         width="64"
                         height="64">
                    <div class="fw-bold">${pool.token.symbol}</div>
                </div>
                <div class="col-md-3">
                    <div class="apy-display">${pool.apy}%</div>
                    <small class="text-muted">APY</small>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block mb-1">Lock Period</small>
                    <div class="fw-bold">${pool.lock_period_display}</div>
                    <small class="text-muted">Min Stake: ${pool.min_stake_amount}</small>
                </div>
                <div class="col-md-2 text-center">
                    <small class="text-muted d-block mb-1">Total Staked</small>
                    <div class="fw-bold">${formatNumber(pool.total_staked)}</div>
                </div>
                <div class="col-md-2 text-end">
                    <button class="btn btn-primary w-100" onclick="openStakeModal(${pool.id})">
                        <i class="fas fa-plus-circle me-1"></i>
                        Stake
                    </button>
                </div>
            </div>
        </div>
    `).join('');

    $('#stakingPools').html(html);
}

function loadMyStakes() {
    $.ajax({
        url: '/api/v1/tpix/staking/my-stakes',
        method: 'GET',
        success: function(response) {
            myStakes = response.data;

            if (myStakes.length > 0) {
                displayMyStakes(myStakes);
                updateMyStats(myStakes);
            }
        }
    });
}

function displayMyStakes(stakes) {
    const html = stakes.map(stake => `
        <div class="stake-card list-group-item">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <strong>${stake.pool.token.symbol}</strong>
                    <small class="text-muted d-block">${stake.pool.apy}% APY</small>
                </div>
                <span class="badge bg-${stake.is_locked ? 'warning' : 'success'}">
                    ${stake.is_locked ? 'Locked' : 'Unlocked'}
                </span>
            </div>
            <div class="mb-2">
                <small class="text-muted d-block">Staked Amount</small>
                <div class="fw-bold">${formatNumber(stake.staked_amount)} ${stake.pool.token.symbol}</div>
            </div>
            <div class="mb-2">
                <small class="text-muted d-block">Pending Rewards</small>
                <div class="text-success fw-bold">+${formatNumber(stake.pending_rewards)}</div>
            </div>
            ${stake.is_locked ? `
                <small class="text-muted d-block mb-2">
                    <i class="fas fa-clock me-1"></i>
                    Unlocks in ${stake.unlock_time_remaining}
                </small>
            ` : ''}
            <div class="d-grid gap-2">
                <button class="btn btn-sm btn-outline-success" onclick="claimRewards(${stake.id})">
                    <i class="fas fa-hand-holding-usd me-1"></i>
                    Claim Rewards
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="openUnstakeModal(${stake.id})">
                    <i class="fas fa-minus-circle me-1"></i>
                    Unstake
                </button>
            </div>
        </div>
    `).join('');

    $('#myStakes').html(`<div class="list-group list-group-flush">${html}</div>`);
}

function updateMyStats(stakes) {
    const totalStaked = stakes.reduce((sum, s) => sum + parseFloat(s.staked_amount), 0);
    const pendingRewards = stakes.reduce((sum, s) => sum + parseFloat(s.pending_rewards), 0);
    const totalEarned = stakes.reduce((sum, s) => sum + parseFloat(s.total_earned), 0);

    $('#myTotalStaked').text(formatNumber(totalStaked));
    $('#myPendingRewards').text(formatNumber(pendingRewards));
    $('#myTotalEarned').text(formatNumber(totalEarned));
}

function openStakeModal(poolId) {
    currentPool = stakingPools.find(p => p.id === poolId);
    if (!currentPool) return;

    $('#poolId').val(poolId);
    $('#modalPoolName').text(currentPool.token.symbol + ' Staking');
    $('#modalPoolAPY').text(currentPool.apy + '%');
    $('#modalLockPeriod').text(currentPool.lock_period_display);
    $('#modalTokenSymbol').text(currentPool.token.symbol);
    $('#modalAvailableBalance').text(formatNumber(currentPool.user_balance || 0));

    $('#stakeModal').modal('show');
}

function calculateRewards() {
    const amount = parseFloat($('#stakeAmount').val()) || 0;
    if (!currentPool || amount === 0) {
        $('#dailyReward, #monthlyReward, #yearlyReward').text('-');
        return;
    }

    const apy = currentPool.apy / 100;
    const daily = (amount * apy) / 365;
    const monthly = daily * 30;
    const yearly = amount * apy;

    $('#dailyReward').text(formatNumber(daily));
    $('#monthlyReward').text(formatNumber(monthly));
    $('#yearlyReward').text(formatNumber(yearly));
}

function executeStake() {
    const data = $('#stakeForm').serialize();

    $.ajax({
        url: '/api/v1/tpix/staking/stake',
        method: 'POST',
        data: data,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            alert('Staking successful!');
            $('#stakeModal').modal('hide');
            $('#stakeForm')[0].reset();
            loadMyStakes();
            loadStakingPools();
        },
        error: function(xhr) {
            alert('Error: ' + (xhr.responseJSON?.message || 'Please try again'));
        }
    });
}

function openUnstakeModal(stakeId) {
    const stake = myStakes.find(s => s.id === stakeId);
    if (!stake) return;

    $('#unstakeStakeId').val(stakeId);
    $('#unstakeTokenSymbol').text(stake.pool.token.symbol);
    $('#unstakeStakedAmount').text(formatNumber(stake.staked_amount));

    const infoHtml = `
        <div class="d-flex justify-content-between mb-2">
            <span>Pool:</span>
            <strong>${stake.pool.token.symbol}</strong>
        </div>
        <div class="d-flex justify-content-between mb-2">
            <span>Staked:</span>
            <strong>${formatNumber(stake.staked_amount)}</strong>
        </div>
        <div class="d-flex justify-content-between">
            <span>Pending Rewards:</span>
            <strong class="text-success">+${formatNumber(stake.pending_rewards)}</strong>
        </div>
    `;
    $('#unstakeInfo').html(infoHtml);

    if (stake.is_locked) {
        $('#earlyWithdrawalWarning').show();
    } else {
        $('#earlyWithdrawalWarning').hide();
    }

    $('#unstakeModal').modal('show');
}

function calculatePenalty() {
    const amount = parseFloat($('#unstakeAmount').val()) || 0;
    const penalty = amount * 0.1;
    $('#penaltyAmount').text(formatNumber(penalty));
}

function executeUnstake() {
    const data = $('#unstakeForm').serialize();

    $.ajax({
        url: '/api/v1/tpix/staking/unstake',
        method: 'POST',
        data: data,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            alert('Unstaked successfully!');
            $('#unstakeModal').modal('hide');
            $('#unstakeForm')[0].reset();
            loadMyStakes();
            loadStakingPools();
        },
        error: function(xhr) {
            alert('Error: ' + (xhr.responseJSON?.message || 'Please try again'));
        }
    });
}

function claimRewards(stakeId) {
    if (!confirm('Claim all pending rewards?')) return;

    $.ajax({
        url: '/api/v1/tpix/staking/claim-rewards',
        method: 'POST',
        data: { stake_id: stakeId },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            alert('Rewards claimed successfully!');
            loadMyStakes();
        },
        error: function(xhr) {
            alert('Error: ' + (xhr.responseJSON?.message || 'Please try again'));
        }
    });
}

function filterPools() {
    const filter = $('input[name="poolFilter"]:checked').val();
    let filtered = [...stakingPools];

    if (filter === 'active') {
        filtered = filtered.filter(p => p.status === 'active');
    } else if (filter === 'mine') {
        const myPoolIds = myStakes.map(s => s.pool_id);
        filtered = filtered.filter(p => myPoolIds.includes(p.id));
    }

    displayPools(filtered);
}

function formatNumber(num) {
    return parseFloat(num).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 8 });
}
</script>
@endpush
@endsection
