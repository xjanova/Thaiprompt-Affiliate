@extends('layouts.app')

@section('title', 'ถอน Liquidity - TPIX DEX')

@section('content')
<div class="container py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">
                <i class="fas fa-minus-circle text-danger me-2"></i>
                ถอน Liquidity
            </h2>
            <p class="text-muted mb-0">ถอนสภาพคล่องและรับ Token กลับ</p>
        </div>
        <a href="{{ route('user.dex.my-positions') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>กลับ
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <!-- Remove Liquidity Card -->
            <div class="card shadow-lg border-0" style="border-radius: 24px;">
                <div class="card-body p-4">
                    <!-- Position Info -->
                    <div class="alert alert-light border rounded-3 p-3 mb-4" id="positionInfo">
                        <div class="text-center mb-3">
                            <h5 class="fw-bold mb-0" id="poolPair">Loading...</h5>
                            <small class="text-muted">Liquidity Pool</small>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <small class="text-muted">LP Tokens ของคุณ</small>
                            <small class="fw-bold" id="yourLPTokens">-</small>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <small class="text-muted">ส่วนแบ่งใน Pool</small>
                            <small class="fw-bold" id="yourSharePercent">-</small>
                        </div>
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">มูลค่าปัจจุบัน</small>
                            <small class="text-success fw-bold" id="currentValue">-</small>
                        </div>
                    </div>

                    <!-- Amount to Remove -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">จำนวนที่ต้องการถอน</label>

                        <!-- Percentage Buttons -->
                        <div class="btn-group w-100 mb-3" role="group">
                            <button type="button" class="btn btn-outline-primary percent-btn" data-percent="25">25%</button>
                            <button type="button" class="btn btn-outline-primary percent-btn" data-percent="50">50%</button>
                            <button type="button" class="btn btn-outline-primary percent-btn" data-percent="75">75%</button>
                            <button type="button" class="btn btn-outline-primary percent-btn" data-percent="100">100%</button>
                        </div>

                        <!-- Custom Amount Slider -->
                        <div class="mb-3">
                            <input type="range" class="form-range" id="removePercent" min="0" max="100" value="50" step="1">
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">0%</small>
                                <strong class="text-primary" id="percentDisplay">50%</strong>
                                <small class="text-muted">100%</small>
                            </div>
                        </div>

                        <!-- LP Token Amount Input -->
                        <div class="input-group">
                            <input type="number" class="form-control form-control-lg"
                                   id="lpTokenAmount" placeholder="0.0" step="0.00000001">
                            <span class="input-group-text">LP Tokens</span>
                        </div>
                    </div>

                    <!-- Expected Receive -->
                    <div class="alert alert-success border-0 rounded-3 p-3 mb-4">
                        <h6 class="fw-bold mb-3">
                            <i class="fas fa-hand-holding-usd me-2"></i>
                            คุณจะได้รับ
                        </h6>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex align-items-center">
                                <img src="" id="tokenALogo" width="24" height="24" class="rounded-circle me-2" onerror="this.src='/images/default-token.png'">
                                <span id="tokenASymbol">-</span>
                            </div>
                            <strong id="receiveAmountA">0.00</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <img src="" id="tokenBLogo" width="24" height="24" class="rounded-circle me-2" onerror="this.src='/images/default-token.png'">
                                <span id="tokenBSymbol">-</span>
                            </div>
                            <strong id="receiveAmountB">0.00</strong>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">มูลค่ารวม (THB)</small>
                            <strong class="text-success" id="totalValueTHB">฿0.00</strong>
                        </div>
                    </div>

                    <!-- ROI & Earnings Summary -->
                    <div class="bg-light rounded-3 p-3 mb-4" id="earningsSummary">
                        <h6 class="fw-bold mb-3">สรุปผลตอบแทน</h6>
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <small class="text-muted d-block">ROI</small>
                                <strong id="roiPercent" class="text-success">-</strong>
                            </div>
                            <div class="col-6 mb-3">
                                <small class="text-muted d-block">กำไร/ขาดทุน</small>
                                <strong id="profitLoss" class="text-success">-</strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">ค่าธรรมเนียมที่ได้รับ</small>
                                <strong class="text-primary" id="feesEarned">฿0.00</strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Impermanent Loss</small>
                                <strong id="impermanentLoss" class="text-warning">-</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Price Details -->
                    <div class="alert alert-light border rounded-3 p-3 mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <small class="text-muted">อัตราแลกเปลี่ยนปัจจุบัน</small>
                            <small id="currentRate">-</small>
                        </div>
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">ส่วนแบ่งคงเหลือ</small>
                            <small id="remainingShare">-</small>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="button" class="btn btn-danger btn-lg w-100 mb-3" id="removeLiquidityBtn">
                        <i class="fas fa-minus-circle me-2"></i>
                        ถอน Liquidity
                    </button>

                    <!-- Warning -->
                    <div class="alert alert-warning border-0 rounded-3">
                        <h6 class="fw-bold mb-2">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            คำเตือน
                        </h6>
                        <small class="text-muted">
                            • การถอน Liquidity จะได้รับ Token ทั้งสองชนิดตามสัดส่วนใน Pool<br>
                            • ราคา Token อาจเปลี่ยนแปลงไปจากตอนที่ฝาก (Impermanent Loss)<br>
                            • การถอนเสร็จสิ้นทันที ไม่สามารถยกเลิกได้
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Side Info Panel -->
        <div class="col-lg-4">
            <!-- Position History -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">
                        <i class="fas fa-chart-line text-primary me-2"></i>
                        ประวัติตำแหน่งนี้
                    </h6>
                    <div id="positionHistory">
                        <div class="d-flex justify-content-between mb-2">
                            <small class="text-muted">วันที่เพิ่ม</small>
                            <small id="createdAt">-</small>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <small class="text-muted">ระยะเวลา</small>
                            <small id="duration">-</small>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <small class="text-muted">เงินลงทุนเริ่มต้น</small>
                            <small id="initialInvestment">-</small>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <small class="text-muted">APY เฉลี่ย</small>
                            <small class="text-success" id="avgAPY">-</small>
                        </div>
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">ธุรกรรมทั้งหมด</small>
                            <small id="transactionCount">-</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pool Stats -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">
                        <i class="fas fa-swimming-pool text-info me-2"></i>
                        สถิติ Pool
                    </h6>
                    <div id="poolStats">
                        <div class="d-flex justify-content-between mb-2">
                            <small class="text-muted">TVL</small>
                            <small class="fw-bold" id="poolTVL">-</small>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <small class="text-muted">ปริมาณ 24ชม.</small>
                            <small id="volume24h">-</small>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <small class="text-muted">APY ปัจจุบัน</small>
                            <small class="text-success fw-bold" id="currentAPY">-</small>
                        </div>
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">LP Providers</small>
                            <small id="lpProviders">-</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ยืนยันการถอน Liquidity
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>คุณกำลังจะถอน Liquidity จำนวน:</p>
                <div class="alert alert-light">
                    <strong id="confirmAmount">-</strong> LP Tokens
                </div>
                <p>และจะได้รับ:</p>
                <div class="alert alert-success">
                    <div><strong id="confirmTokenA">-</strong></div>
                    <div><strong id="confirmTokenB">-</strong></div>
                </div>
                <p class="text-danger mb-0">
                    <small><strong>หมายเหตุ:</strong> การดำเนินการนี้ไม่สามารถยกเลิกได้</small>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-danger" id="confirmRemoveBtn">
                    <i class="fas fa-check me-2"></i>ยืนยันการถอน
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const positionId = {{ $positionId ?? 'null' }};
    let positionData = null;

    // Load position data
    loadPosition();

    function loadPosition() {
        $.ajax({
            url: `/api/v1/tpix/dex/positions/${positionId}`,
            method: 'GET',
            success: function(response) {
                positionData = response.data;
                displayPosition();
            },
            error: function() {
                alert('ไม่พบข้อมูลตำแหน่ง Liquidity');
                window.location.href = '{{ route("user.dex.my-positions") }}';
            }
        });
    }

    function displayPosition() {
        // Position info
        $('#poolPair').text(positionData.pool.pair);
        $('#yourLPTokens').text(formatNumber(positionData.lp_token_balance));
        $('#yourSharePercent').text(positionData.share_percentage.toFixed(4) + '%');
        $('#currentValue').text('฿' + formatNumber(positionData.current_value));

        // Token info
        $('#tokenASymbol').text(positionData.pool.token_a.symbol);
        $('#tokenBSymbol').text(positionData.pool.token_b.symbol);
        $('#tokenALogo').attr('src', positionData.pool.token_a.logo_url);
        $('#tokenBLogo').attr('src', positionData.pool.token_b.logo_url);

        // Position history
        $('#createdAt').text(new Date(positionData.created_at).toLocaleDateString('th-TH'));
        $('#duration').text(calculateDuration(positionData.created_at));
        $('#initialInvestment').text('฿' + formatNumber(positionData.initial_value));
        $('#avgAPY').text(positionData.avg_apy.toFixed(2) + '%');

        // Pool stats
        $('#poolTVL').text('฿' + formatNumber(positionData.pool.tvl));
        $('#volume24h').text('฿' + formatNumber(positionData.pool.volume_24h));
        $('#currentAPY').text(positionData.pool.apy.toFixed(2) + '%');
        $('#lpProviders').text(positionData.pool.lp_count || '-');

        // Calculate initial amounts
        calculateRemoveAmounts();
    }

    // Percentage buttons
    $('.percent-btn').click(function() {
        const percent = $(this).data('percent');
        $('#removePercent').val(percent);
        updatePercentDisplay();
        calculateRemoveAmounts();
    });

    // Range slider
    $('#removePercent').on('input', function() {
        updatePercentDisplay();
        calculateRemoveAmounts();
    });

    function updatePercentDisplay() {
        const percent = $('#removePercent').val();
        $('#percentDisplay').text(percent + '%');

        const lpAmount = (positionData.lp_token_balance * percent / 100).toFixed(8);
        $('#lpTokenAmount').val(lpAmount);
    }

    // Manual LP token input
    $('#lpTokenAmount').on('input', function() {
        const amount = parseFloat($(this).val()) || 0;
        const percent = (amount / positionData.lp_token_balance * 100).toFixed(0);
        $('#removePercent').val(Math.min(percent, 100));
        $('#percentDisplay').text(Math.min(percent, 100) + '%');
        calculateRemoveAmounts();
    });

    function calculateRemoveAmounts() {
        const lpAmount = parseFloat($('#lpTokenAmount').val()) || 0;
        const percent = lpAmount / positionData.lp_token_balance;

        // Calculate token amounts
        const amountA = positionData.token_a_amount * percent;
        const amountB = positionData.token_b_amount * percent;

        $('#receiveAmountA').text(formatNumber(amountA));
        $('#receiveAmountB').text(formatNumber(amountB));

        // Calculate total value
        const totalValue = amountA * positionData.pool.token_a.price_thb +
                          amountB * positionData.pool.token_b.price_thb;
        $('#totalValueTHB').text('฿' + formatNumber(totalValue));

        // ROI calculation
        const initialValue = positionData.initial_value * percent;
        const profit = totalValue - initialValue;
        const roiPercent = (profit / initialValue * 100).toFixed(2);

        $('#roiPercent').text(roiPercent + '%').removeClass('text-success text-danger')
            .addClass(profit >= 0 ? 'text-success' : 'text-danger');
        $('#profitLoss').text('฿' + formatNumber(profit)).removeClass('text-success text-danger')
            .addClass(profit >= 0 ? 'text-success' : 'text-danger');

        // Fees earned
        const feesEarned = positionData.fees_earned * percent;
        $('#feesEarned').text('฿' + formatNumber(feesEarned));

        // Impermanent loss
        if (positionData.impermanent_loss) {
            const il = positionData.impermanent_loss.loss_percentage * percent;
            $('#impermanentLoss').text(il.toFixed(2) + '%');
        }

        // Current rate
        const rate = positionData.pool.price;
        $('#currentRate').text(`1 ${positionData.pool.token_a.symbol} = ${formatNumber(rate)} ${positionData.pool.token_b.symbol}`);

        // Remaining share
        const remainingPercent = ((1 - percent) * positionData.share_percentage).toFixed(4);
        $('#remainingShare').text(remainingPercent + '%');
    }

    // Remove liquidity
    $('#removeLiquidityBtn').click(function() {
        const lpAmount = $('#lpTokenAmount').val();

        if (!lpAmount || lpAmount <= 0) {
            alert('กรุณาระบุจำนวนที่ต้องการถอน');
            return;
        }

        // Show confirmation modal
        $('#confirmAmount').text(formatNumber(lpAmount));
        $('#confirmTokenA').text(formatNumber($('#receiveAmountA').text()) + ' ' + $('#tokenASymbol').text());
        $('#confirmTokenB').text(formatNumber($('#receiveAmountB').text()) + ' ' + $('#tokenBSymbol').text());
        $('#confirmModal').modal('show');
    });

    // Confirm remove
    $('#confirmRemoveBtn').click(function() {
        const lpAmount = $('#lpTokenAmount').val();

        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>กำลังประมวลผล...');

        $.ajax({
            url: '/api/v1/tpix/dex/liquidity/remove',
            method: 'POST',
            data: {
                position_id: positionId,
                lp_token_amount: lpAmount,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                $('#confirmModal').modal('hide');
                alert('ถอน Liquidity สำเร็จ!');
                window.location.href = '{{ route("user.dex.my-positions") }}';
            },
            error: function(xhr) {
                alert('เกิดข้อผิดพลาด: ' + (xhr.responseJSON?.message || 'Unknown error'));
                $('#confirmRemoveBtn').prop('disabled', false).html('<i class="fas fa-check me-2"></i>ยืนยันการถอน');
            }
        });
    });

    function calculateDuration(createdAt) {
        const now = new Date();
        const created = new Date(createdAt);
        const diff = now - created;
        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        return days + ' วัน';
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

@push('styles')
<style>
.percent-btn {
    font-size: 0.875rem;
}

.percent-btn.active {
    background-color: #0d6efd;
    color: white;
}

.form-range::-webkit-slider-thumb {
    background-color: #0d6efd;
}

.form-range::-moz-range-thumb {
    background-color: #0d6efd;
}
</style>
@endpush
