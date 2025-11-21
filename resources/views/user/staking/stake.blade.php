@extends('layouts.user-arrow-x')

@section('title', 'Stake Token - TPIX')

@section('content')
<div class="container py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">
                <i class="fas fa-lock text-primary me-2"></i>
                Stake Token
            </h2>
            <p class="text-muted mb-0">ล็อก Token และรับรางวัล</p>
        </div>
        <a href="{{ route('user.staking.show', $pool->id) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>กลับ
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <!-- Stake Form Card -->
            <div class="card border-0 shadow-lg" style="border-radius: 24px;">
                <div class="card-body p-4">
                    <!-- Pool Info Header -->
                    <div class="text-center mb-4">
                        <img src="{{ $pool->token->logo_url ?? '/images/default-token.png' }}" width="64" height="64" class="rounded-circle mb-3">
                        <h5 class="fw-bold mb-1">{{ $pool->token->name }}</h5>
                        <p class="text-muted mb-0">{{ $pool->token->symbol }} Staking Pool</p>
                    </div>

                    <form id="stakeForm">
                        @csrf

                        <!-- Amount to Stake -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">จำนวนที่ต้องการ Stake</label>
                            <div class="input-group input-group-lg">
                                <input type="number" class="form-control" id="stakeAmount"
                                       placeholder="0.0" step="0.00000001" required>
                                <span class="input-group-text">{{ $pool->token->symbol }}</span>
                            </div>
                            <div class="d-flex justify-content-between mt-2">
                                <small class="text-muted">
                                    คงเหลือ: <span id="balance">{{ $balance->amount ?? '0.00' }}</span> {{ $pool->token->symbol }}
                                </small>
                                <button type="button" class="btn btn-sm btn-link text-primary p-0" id="maxBtn">
                                    <small>ใช้ทั้งหมด</small>
                                </button>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">
                                    ขั้นต่ำ: {{ $pool->min_stake_amount }} {{ $pool->token->symbol }}
                                </small>
                            </div>
                        </div>

                        <!-- Lock Period Selection -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">เลือกระยะเวลาล็อก</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="lockPeriod" id="lock0" value="0" data-apy="{{ $pool->apy * 0.5 }}">
                                    <label class="btn btn-outline-primary w-100" for="lock0">
                                        <div class="fw-bold">Flexible</div>
                                        <small>{{ number_format($pool->apy * 0.5, 2) }}% APY</small>
                                    </label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="lockPeriod" id="lock7" value="7" data-apy="{{ $pool->apy * 0.7 }}">
                                    <label class="btn btn-outline-primary w-100" for="lock7">
                                        <div class="fw-bold">7 วัน</div>
                                        <small>{{ number_format($pool->apy * 0.7, 2) }}% APY</small>
                                    </label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="lockPeriod" id="lock30" value="30" data-apy="{{ $pool->apy * 0.9 }}" checked>
                                    <label class="btn btn-outline-primary w-100" for="lock30">
                                        <div class="fw-bold">30 วัน</div>
                                        <small>{{ number_format($pool->apy * 0.9, 2) }}% APY</small>
                                    </label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="lockPeriod" id="lock90" value="90" data-apy="{{ $pool->apy }}">
                                    <label class="btn btn-outline-primary w-100" for="lock90">
                                        <div class="fw-bold">90 วัน</div>
                                        <small>{{ number_format($pool->apy, 2) }}% APY</small>
                                    </label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="lockPeriod" id="lock180" value="180" data-apy="{{ $pool->apy * 1.2 }}">
                                    <label class="btn btn-outline-primary w-100" for="lock180">
                                        <div class="fw-bold">180 วัน</div>
                                        <small>{{ number_format($pool->apy * 1.2, 2) }}% APY</small>
                                    </label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="lockPeriod" id="lock365" value="365" data-apy="{{ $pool->apy * 1.5 }}">
                                    <label class="btn btn-outline-primary w-100" for="lock365">
                                        <div class="fw-bold">365 วัน</div>
                                        <small>{{ number_format($pool->apy * 1.5, 2) }}% APY</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Summary -->
                        <div class="alert alert-light border rounded-3 p-3 mb-4" id="stakeSummary">
                            <h6 class="fw-bold mb-3">สรุป</h6>
                            <div class="d-flex justify-content-between mb-2">
                                <small class="text-muted">จำนวน Stake</small>
                                <small class="fw-bold" id="summaryAmount">0.00 {{ $pool->token->symbol }}</small>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <small class="text-muted">ระยะเวลาล็อก</small>
                                <small id="summaryLockPeriod">30 วัน</small>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <small class="text-muted">APY</small>
                                <small class="text-success fw-bold" id="summaryAPY">{{ number_format($pool->apy * 0.9, 2) }}%</small>
                            </div>
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">วันที่ปลดล็อก</small>
                                <small id="summaryUnlockDate">-</small>
                            </div>
                        </div>

                        <!-- Estimated Rewards -->
                        <div class="bg-light rounded-3 p-3 mb-4">
                            <h6 class="fw-bold mb-3">
                                <i class="fas fa-gift text-warning me-2"></i>
                                รางวัลโดยประมาณ
                            </h6>
                            <div class="row text-center">
                                <div class="col-4">
                                    <small class="text-muted d-block">รายได้/วัน</small>
                                    <strong class="text-success" id="rewardDaily">0.00</strong>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">รายได้/เดือน</small>
                                    <strong class="text-success" id="rewardMonthly">0.00</strong>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">รายได้/ปี</small>
                                    <strong class="text-success" id="rewardYearly">0.00</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary btn-lg w-100 mb-3" id="stakeBtn">
                            <i class="fas fa-lock me-2"></i>
                            Stake ตอนนี้
                        </button>

                        <!-- Warning -->
                        <div class="alert alert-warning border-0 rounded-3">
                            <h6 class="fw-bold mb-2">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                ข้อควรรู้
                            </h6>
                            <small>
                                • Token ที่ Stake จะถูกล็อกตามระยะเวลาที่เลือก<br>
                                • การ Unstake ก่อนครบกำหนดจะมีค่าปรับ {{ $pool->early_withdrawal_penalty_percent ?? '5' }}%<br>
                                • รางวัลที่แสดงเป็นเพียงการประมาณการ<br>
                                • รางวัลสะสมอัตโนมัติ (Auto-compound)
                            </small>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Side Panel -->
        <div class="col-lg-4">
            <!-- Pool Info -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white dark:bg-gray-800 border-0 py-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        ข้อมูล Pool
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <small class="text-muted">Total Staked</small>
                        <small class="fw-bold">{{ number_format($pool->total_staked ?? 0, 2) }} {{ $pool->token->symbol }}</small>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <small class="text-muted">จำนวนผู้ Stake</small>
                        <small>{{ number_format($pool->staker_count ?? 0) }}</small>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <small class="text-muted">APY สูงสุด</small>
                        <small class="text-success fw-bold">{{ number_format($pool->apy * 1.5, 2) }}%</small>
                    </div>
                    <div class="d-flex justify-content-between">
                        <small class="text-muted">สถานะ Pool</small>
                        <small><span class="badge bg-success">Active</span></small>
                    </div>
                </div>
            </div>

            <!-- Why Stake? -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white dark:bg-gray-800 border-0 py-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="fas fa-question-circle text-primary me-2"></i>
                        ทำไมต้อง Stake?
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <small>รับรางวัลทุกบล็อก (ทุก 2 วินาที)</small>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <small>Auto-compound รางวัลอัตโนมัติ</small>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <small>APY สูงถึง {{ number_format($pool->apy * 1.5, 2) }}%</small>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <small>ถอนได้ทุกเมื่อ (Flexible)</small>
                        </li>
                        <li class="mb-0">
                            <i class="fas fa-shield-alt text-info me-2"></i>
                            <small>ปลอดภัย 100% - Smart Contract Audited</small>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const poolId = {{ $pool->id }};
    const balance = parseFloat('{{ $balance->amount ?? 0 }}');
    const tokenSymbol = '{{ $pool->token->symbol }}';

    // Update summary on input
    $('#stakeAmount, input[name="lockPeriod"]').on('input change', updateSummary);

    // Max button
    $('#maxBtn').click(function() {
        $('#stakeAmount').val(balance).trigger('input');
    });

    function updateSummary() {
        const amount = parseFloat($('#stakeAmount').val()) || 0;
        const lockPeriod = parseInt($('input[name="lockPeriod"]:checked').val());
        const apy = parseFloat($('input[name="lockPeriod"]:checked').data('apy'));

        // Update summary
        $('#summaryAmount').text(amount.toFixed(8) + ' ' + tokenSymbol);

        const lockLabel = lockPeriod === 0 ? 'Flexible' : lockPeriod + ' วัน';
        $('#summaryLockPeriod').text(lockLabel);

        $('#summaryAPY').text(apy.toFixed(2) + '%');

        // Calculate unlock date
        if (lockPeriod > 0) {
            const unlockDate = new Date();
            unlockDate.setDate(unlockDate.getDate() + lockPeriod);
            $('#summaryUnlockDate').text(unlockDate.toLocaleDateString('th-TH'));
        } else {
            $('#summaryUnlockDate').text('ไม่มีการล็อก');
        }

        // Calculate rewards
        if (amount > 0 && apy > 0) {
            const yearlyReward = amount * (apy / 100);
            const dailyReward = yearlyReward / 365;
            const monthlyReward = yearlyReward / 12;

            $('#rewardDaily').text(dailyReward.toFixed(8));
            $('#rewardMonthly').text(monthlyReward.toFixed(8));
            $('#rewardYearly').text(yearlyReward.toFixed(8));
        } else {
            $('#rewardDaily, #rewardMonthly, #rewardYearly').text('0.00');
        }
    }

    // Initial update
    updateSummary();

    // Form submission
    $('#stakeForm').submit(function(e) {
        e.preventDefault();

        const amount = parseFloat($('#stakeAmount').val());
        const lockPeriod = parseInt($('input[name="lockPeriod"]:checked').val());

        // Validation
        if (!amount || amount <= 0) {
            alert('กรุณาระบุจำนวน Token ที่ต้องการ Stake');
            return;
        }

        if (amount > balance) {
            alert('ยอดคงเหลือไม่เพียงพอ');
            return;
        }

        if (amount < {{ $pool->min_stake_amount }}) {
            alert('จำนวน Stake ต้องไม่ต่ำกว่า {{ $pool->min_stake_amount }} ' + tokenSymbol);
            return;
        }

        // Disable button
        $('#stakeBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>กำลังประมวลผล...');

        // Submit
        $.ajax({
            url: '/api/v1/tpix/staking/stake',
            method: 'POST',
            data: {
                pool_id: poolId,
                amount: amount,
                lock_period_days: lockPeriod,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                alert('Stake สำเร็จ!');
                window.location.href = '{{ route("user.staking.show", $pool->id) }}';
            },
            error: function(xhr) {
                alert('เกิดข้อผิดพลาด: ' + (xhr.responseJSON?.message || 'Unknown error'));
                $('#stakeBtn').prop('disabled', false).html('<i class="fas fa-lock me-2"></i>Stake ตอนนี้');
            }
        });
    });
});
</script>
@endpush

@push('styles')
<style>
.btn-check:checked + .btn-outline-primary {
    background-color: #0d6efd;
    color: white;
}
</style>
@endpush
