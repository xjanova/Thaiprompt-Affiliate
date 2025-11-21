@extends('layouts.user-arrow-x')

@section('title', 'รายละเอียด Staking Pool - TPIX')

@section('content')
<div class="container py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1" id="poolName">Loading...</h2>
            <p class="text-muted mb-0">ข้อมูลและสถิติ Staking Pool</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('user.staking.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>กลับ
            </a>
            <a href="{{ route('user.staking.stake-form', $poolId ?? 0) }}" class="btn btn-primary" id="stakeBtn">
                <i class="fas fa-lock me-2"></i>Stake ตอนนี้
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Main Info -->
        <div class="col-lg-8">
            <!-- Pool Stats Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-3">
                            <div class="text-center">
                                <img src="" id="tokenLogo" width="80" height="80" class="rounded-circle mb-3" onerror="this.src='/images/default-token.png'">
                                <h6 class="fw-bold mb-0" id="tokenSymbol">-</h6>
                                <small class="text-muted">Token</small>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="border-start border-primary border-4 ps-3">
                                        <small class="text-muted d-block">APY</small>
                                        <h4 class="mb-0 text-success" id="poolAPY">-</h4>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="border-start border-info border-4 ps-3">
                                        <small class="text-muted d-block">Total Staked</small>
                                        <h4 class="mb-0" id="totalStaked">-</h4>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="border-start border-warning border-4 ps-3">
                                        <small class="text-muted d-block">จำนวนผู้ Stake</small>
                                        <h4 class="mb-0" id="stakerCount">-</h4>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="border-start border-danger border-4 ps-3">
                                        <small class="text-muted d-block">Min. Stake</small>
                                        <h4 class="mb-0" id="minStake">-</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lock Periods -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white dark:bg-gray-800 border-0 py-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="fas fa-calendar-alt text-primary me-2"></i>
                        ระยะเวลาการล็อก และ APY
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3" id="lockPeriods">
                        <!-- Will be loaded via AJAX -->
                    </div>
                </div>
            </div>

            <!-- Pool Description -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white dark:bg-gray-800 border-0 py-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        เกี่ยวกับ Pool นี้
                    </h6>
                </div>
                <div class="card-body">
                    <p class="mb-3" id="poolDescription">Loading...</p>

                    <h6 class="fw-bold mb-3">คุณสมบัติ</h6>
                    <ul class="list-unstyled" id="poolFeatures">
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <span>Auto-compound รางวัล</span>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <span>รับรางวัลทุกบล็อก</span>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <span>ถอนได้ทุกเมื่อ (มีค่าปรับหากยังไม่ครบกำหนด)</span>
                        </li>
                    </ul>

                    <div class="alert alert-warning border-0 mt-3">
                        <h6 class="fw-bold mb-2">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ข้อควรรู้
                        </h6>
                        <small>
                            • การถอนก่อนครบกำหนดจะมีค่าปรับ <strong id="earlyWithdrawalPenalty">-</strong>%<br>
                            • APY อาจเปลี่ยนแปลงตามปริมาณการ Stake<br>
                            • รางวัลจ่ายเป็น Token เดียวกับที่ Stake
                        </small>
                    </div>
                </div>
            </div>

            <!-- Recent Stakes -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white dark:bg-gray-800 border-0 py-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="fas fa-history text-primary me-2"></i>
                        ธุรกรรมล่าสุด
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>ผู้ใช้</th>
                                    <th>ประเภท</th>
                                    <th class="text-end">จำนวน</th>
                                    <th>เวลา</th>
                                </tr>
                            </thead>
                            <tbody id="recentStakes">
                                <tr>
                                    <td colspan="4" class="text-center py-3">
                                        <small class="text-muted">กำลังโหลด...</small>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- My Stake in this Pool -->
            <div class="card border-0 shadow-sm mb-4" id="myStakeCard">
                <div class="card-header bg-primary text-white py-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="fas fa-wallet me-2"></i>
                        การ Stake ของฉัน
                    </h6>
                </div>
                <div class="card-body">
                    <div id="myStakeContent">
                        <!-- No stake state -->
                        <div class="text-center py-4" id="noStakeState">
                            <i class="fas fa-lock-open text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-3 mb-3">คุณยังไม่ได้ Stake ใน Pool นี้</p>
                            <a href="{{ route('user.staking.stake-form', $poolId ?? 0) }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-lock me-2"></i>Stake ตอนนี้
                            </a>
                        </div>

                        <!-- Has stake state -->
                        <div id="hasStakeState" style="display: none;">
                            <div class="mb-3">
                                <small class="text-muted d-block mb-1">จำนวนที่ Stake</small>
                                <h4 class="mb-0" id="myStakedAmount">-</h4>
                                <small class="text-muted" id="myStakedValue">≈ ฿0.00</small>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-2">
                                <small class="text-muted">ระยะเวลาล็อก</small>
                                <small class="fw-bold" id="myLockPeriod">-</small>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <small class="text-muted">เหลือเวลา</small>
                                <small class="fw-bold" id="myTimeRemaining">-</small>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <small class="text-muted">APY ของคุณ</small>
                                <small class="text-success fw-bold" id="myAPY">-</small>
                            </div>

                            <div class="bg-light rounded-3 p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="text-muted d-block">รางวัลที่ได้รับ</small>
                                        <h5 class="mb-0 text-success" id="myPendingRewards">-</h5>
                                    </div>
                                    <button class="btn btn-success btn-sm" id="claimBtn">
                                        <i class="fas fa-hand-holding-usd me-1"></i>Claim
                                    </button>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary btn-sm" id="unstakeBtn">
                                    <i class="fas fa-unlock me-2"></i>Unstake
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" id="addMoreBtn">
                                    <i class="fas fa-plus me-2"></i>Stake เพิ่ม
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rewards Calculator -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white dark:bg-gray-800 border-0 py-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="fas fa-calculator text-primary me-2"></i>
                        คำนวณรางวัล
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">จำนวนที่ต้องการ Stake</label>
                        <input type="number" class="form-control" id="calcAmount" placeholder="0.0" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ระยะเวลา</label>
                        <select class="form-select" id="calcLockPeriod">
                            <option value="">เลือกระยะเวลา</option>
                        </select>
                    </div>

                    <div class="bg-light rounded-3 p-3">
                        <div class="d-flex justify-content-between mb-2">
                            <small class="text-muted">รายได้/วัน</small>
                            <strong class="text-success" id="calcDaily">฿0.00</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <small class="text-muted">รายได้/เดือน</small>
                            <strong class="text-success" id="calcMonthly">฿0.00</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">รายได้/ปี</small>
                            <strong class="text-success" id="calcYearly">฿0.00</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pool Stats -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white dark:bg-gray-800 border-0 py-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="fas fa-chart-bar text-primary me-2"></i>
                        สถิติ Pool
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <small class="text-muted">รางวัลจ่ายไปแล้ว</small>
                        <small class="fw-bold" id="totalRewardsPaid">-</small>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <small class="text-muted">AVG Stake ต่อคน</small>
                        <small id="avgStakePerUser">-</small>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <small class="text-muted">สร้างเมื่อ</small>
                        <small id="poolCreatedAt">-</small>
                    </div>
                    <div class="d-flex justify-content-between">
                        <small class="text-muted">สถานะ</small>
                        <small id="poolStatus">-</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const poolId = {{ $poolId ?? 'null' }};
    let poolData = null;
    let myStake = null;

    // Load pool data
    loadPoolData();
    loadMyStake();
    loadRecentStakes();

    function loadPoolData() {
        $.ajax({
            url: `/api/v1/tpix/staking/pools/${poolId}`,
            success: function(response) {
                poolData = response.data;
                displayPoolData();
            }
        });
    }

    function displayPoolData() {
        $('#poolName').html(`<i class="fas fa-lock me-2"></i>${poolData.token.name} Staking Pool`);
        $('#tokenLogo').attr('src', poolData.token.logo_url);
        $('#tokenSymbol').text(poolData.token.symbol);
        $('#poolAPY').text(poolData.apy.toFixed(2) + '%');
        $('#totalStaked').text(formatNumber(poolData.total_staked) + ' ' + poolData.token.symbol);
        $('#stakerCount').text(poolData.staker_count.toLocaleString());
        $('#minStake').text(formatNumber(poolData.min_stake_amount) + ' ' + poolData.token.symbol);

        $('#poolDescription').text(poolData.description || 'Stake ' + poolData.token.symbol + ' และรับรางวัลทุกบล็อก');
        $('#earlyWithdrawalPenalty').text(poolData.early_withdrawal_penalty_percent);

        // Lock periods
        displayLockPeriods();

        // Stats
        $('#totalRewardsPaid').text(formatNumber(poolData.total_rewards_paid) + ' ' + poolData.token.symbol);
        $('#avgStakePerUser').text(formatNumber(poolData.avg_stake_per_user) + ' ' + poolData.token.symbol);
        $('#poolCreatedAt').text(new Date(poolData.created_at).toLocaleDateString('th-TH'));
        $('#poolStatus').html(`<span class="badge bg-success">${poolData.status}</span>`);
    }

    function displayLockPeriods() {
        const periods = poolData.lock_periods || [
            {days: 0, apy: poolData.apy * 0.5, label: 'Flexible'},
            {days: 7, apy: poolData.apy * 0.7, label: '7 วัน'},
            {days: 30, apy: poolData.apy * 0.9, label: '30 วัน'},
            {days: 90, apy: poolData.apy, label: '90 วัน'},
            {days: 180, apy: poolData.apy * 1.2, label: '180 วัน'},
            {days: 365, apy: poolData.apy * 1.5, label: '365 วัน'}
        ];

        let html = '';
        let selectHtml = '<option value="">เลือกระยะเวลา</option>';

        periods.forEach(period => {
            html += `
                <div class="col-md-6">
                    <div class="border rounded-3 p-3 text-center h-100">
                        <h6 class="fw-bold mb-1">${period.label}</h6>
                        <h4 class="text-success mb-0">${period.apy.toFixed(2)}%</h4>
                        <small class="text-muted">APY</small>
                    </div>
                </div>
            `;
            selectHtml += `<option value="${period.days}" data-apy="${period.apy}">${period.label} - ${period.apy.toFixed(2)}% APY</option>`;
        });

        $('#lockPeriods').html(html);
        $('#calcLockPeriod').html(selectHtml);
    }

    function loadMyStake() {
        $.ajax({
            url: `/api/v1/tpix/staking/my-stake/${poolId}`,
            success: function(response) {
                if (response.data) {
                    myStake = response.data;
                    displayMyStake();
                }
            }
        });
    }

    function displayMyStake() {
        $('#noStakeState').hide();
        $('#hasStakeState').show();

        $('#myStakedAmount').text(formatNumber(myStake.staked_amount) + ' ' + poolData.token.symbol);
        $('#myStakedValue').text('≈ ฿' + formatNumber(myStake.staked_value_thb));
        $('#myLockPeriod').text(myStake.lock_period_days + ' วัน');
        $('#myTimeRemaining').text(calculateTimeRemaining(myStake.unlock_date));
        $('#myAPY').text(myStake.apy.toFixed(2) + '%');
        $('#myPendingRewards').text(formatNumber(myStake.pending_rewards) + ' ' + poolData.token.symbol);

        // Bind buttons
        $('#claimBtn').click(() => claimRewards());
        $('#unstakeBtn').click(() => unstake());
        $('#addMoreBtn').attr('href', '{{ route("user.staking.stake-form", $poolId ?? 0) }}');
    }

    function loadRecentStakes() {
        $.ajax({
            url: `/api/v1/tpix/staking/pools/${poolId}/recent`,
            success: function(response) {
                let html = '';
                response.data.forEach(stake => {
                    html += `
                        <tr>
                            <td><small>${stake.user.username || 'User #' + stake.user.id}</small></td>
                            <td><span class="badge bg-${stake.type === 'stake' ? 'success' : 'danger'}">${stake.type}</span></td>
                            <td class="text-end"><small>${formatNumber(stake.amount)}</small></td>
                            <td><small>${timeAgo(stake.created_at)}</small></td>
                        </tr>
                    `;
                });
                $('#recentStakes').html(html || '<tr><td colspan="4" class="text-center"><small class="text-muted">ยังไม่มีธุรกรรม</small></td></tr>');
            }
        });
    }

    // Calculator
    $('#calcAmount, #calcLockPeriod').on('input change', function() {
        const amount = parseFloat($('#calcAmount').val()) || 0;
        const selected = $('#calcLockPeriod option:selected');
        const apy = parseFloat(selected.data('apy')) || 0;

        if (amount > 0 && apy > 0) {
            const yearlyReward = amount * (apy / 100);
            $('#calcDaily').text('฿' + formatNumber(yearlyReward / 365));
            $('#calcMonthly').text('฿' + formatNumber(yearlyReward / 12));
            $('#calcYearly').text('฿' + formatNumber(yearlyReward));
        }
    });

    function claimRewards() {
        if (confirm('ต้องการเบิกรางวัลหรือไม่?')) {
            $.post('/api/v1/tpix/staking/claim', {
                pool_id: poolId,
                _token: $('meta[name="csrf-token"]').attr('content')
            }).done(() => {
                alert('เบิกรางวัลสำเร็จ!');
                loadMyStake();
            });
        }
    }

    function unstake() {
        if (confirm('ต้องการ Unstake หรือไม่? (อาจมีค่าปรับหากยังไม่ครบกำหนด)')) {
            $.post('/api/v1/tpix/staking/unstake', {
                pool_id: poolId,
                _token: $('meta[name="csrf-token"]').attr('content')
            }).done(() => {
                alert('Unstake สำเร็จ!');
                window.location.reload();
            });
        }
    }

    function calculateTimeRemaining(unlockDate) {
        const now = new Date();
        const unlock = new Date(unlockDate);
        const diff = unlock - now;
        if (diff <= 0) return 'ปลดล็อกแล้ว';
        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        return days + ' วัน';
    }

    function timeAgo(date) {
        const seconds = Math.floor((new Date() - new Date(date)) / 1000);
        if (seconds < 60) return seconds + ' วินาทีที่แล้ว';
        const minutes = Math.floor(seconds / 60);
        if (minutes < 60) return minutes + ' นาทีที่แล้ว';
        const hours = Math.floor(minutes / 60);
        if (hours < 24) return hours + ' ชั่วโมงที่แล้ว';
        const days = Math.floor(hours / 24);
        return days + ' วันที่แล้ว';
    }

    function formatNumber(num) {
        return parseFloat(num).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 8
        });
    }
});
</script>
@endpush
