@extends('layouts.app')

@section('title', 'เพิ่ม Liquidity - TPIX DEX')

@section('content')
<div class="container py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">
                <i class="fas fa-plus-circle text-primary me-2"></i>
                เพิ่ม Liquidity
            </h2>
            <p class="text-muted mb-0">เพิ่มสภาพคล่องและรับส่วนแบ่งค่าธรรมเนียม</p>
        </div>
        <a href="{{ route('user.dex.pools') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>กลับ
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <!-- Add Liquidity Card -->
            <div class="card shadow-lg border-0" style="border-radius: 24px;">
                <div class="card-body p-4">
                    <!-- Pool Selection or Create New -->
                    <div class="mb-4">
                        <h5 class="fw-bold mb-3">เลือกคู่ Token</h5>

                        <!-- Token A -->
                        <div class="mb-3">
                            <label class="form-label text-muted">Token A</label>
                            <div class="d-flex justify-content-between mb-2">
                                <small class="text-muted">คงเหลือ: <span id="balanceA">0.00</span></small>
                            </div>
                            <div class="input-group">
                                <input type="number" class="form-control form-control-lg"
                                       id="amountA" placeholder="0.0" step="0.00000001" required>
                                <button class="btn btn-light token-selector" type="button"
                                        data-bs-toggle="modal" data-bs-target="#tokenSelectModal" data-target="a">
                                    <span id="tokenA">
                                        <i class="fas fa-coins text-primary me-2"></i>
                                        เลือก Token
                                    </span>
                                    <i class="fas fa-chevron-down ms-2"></i>
                                </button>
                            </div>
                            <button type="button" class="btn btn-sm btn-link text-primary p-0 mt-1" id="maxButtonA">
                                <small>ใช้ทั้งหมด</small>
                            </button>
                        </div>

                        <!-- Plus Icon -->
                        <div class="text-center my-3">
                            <i class="fas fa-plus text-muted"></i>
                        </div>

                        <!-- Token B -->
                        <div class="mb-3">
                            <label class="form-label text-muted">Token B</label>
                            <div class="d-flex justify-content-between mb-2">
                                <small class="text-muted">คงเหลือ: <span id="balanceB">0.00</span></small>
                            </div>
                            <div class="input-group">
                                <input type="number" class="form-control form-control-lg"
                                       id="amountB" placeholder="0.0" step="0.00000001" required>
                                <button class="btn btn-light token-selector" type="button"
                                        data-bs-toggle="modal" data-bs-target="#tokenSelectModal" data-target="b">
                                    <span id="tokenB">
                                        <i class="fas fa-coins text-success me-2"></i>
                                        เลือก Token
                                    </span>
                                    <i class="fas fa-chevron-down ms-2"></i>
                                </button>
                            </div>
                            <button type="button" class="btn btn-sm btn-link text-primary p-0 mt-1" id="maxButtonB">
                                <small>ใช้ทั้งหมด</small>
                            </button>
                        </div>
                    </div>

                    <!-- Pool Info (shows if pool exists) -->
                    <div id="poolInfo" class="alert alert-light border rounded-3 p-3 mb-4" style="display: none;">
                        <h6 class="fw-bold mb-3">ข้อมูล Pool</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <small class="text-muted">สภาพคล่องทั้งหมด (TVL)</small>
                            <small class="fw-bold" id="poolTVL">-</small>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <small class="text-muted">อัตราส่วน</small>
                            <small id="poolRatio">-</small>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <small class="text-muted">APY</small>
                            <small class="text-success fw-bold" id="poolAPY">-</small>
                        </div>
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">ส่วนแบ่งของคุณ</small>
                            <small id="yourShare">0%</small>
                        </div>
                    </div>

                    <!-- New Pool Notice -->
                    <div id="newPoolNotice" class="alert alert-info" style="display: none;">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>คุณกำลังสร้าง Liquidity Pool ใหม่สำหรับคู่ token นี้</small>
                    </div>

                    <!-- Expected Output -->
                    <div id="expectedOutput" class="alert alert-light border rounded-3 p-3 mb-4" style="display: none;">
                        <h6 class="fw-bold mb-3">คุณจะได้รับ</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <small class="text-muted">LP Tokens</small>
                            <small class="fw-bold" id="lpTokenAmount">-</small>
                        </div>
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">ส่วนแบ่งใน Pool</small>
                            <small class="text-success fw-bold" id="sharePercent">-</small>
                        </div>
                    </div>

                    <!-- Estimated Earnings -->
                    <div id="estimatedEarnings" class="bg-light rounded-3 p-3 mb-4" style="display: none;">
                        <div class="row text-center">
                            <div class="col-4">
                                <small class="text-muted d-block">รายได้/วัน</small>
                                <strong class="text-success" id="dailyEarnings">-</strong>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">รายได้/เดือน</small>
                                <strong class="text-success" id="monthlyEarnings">-</strong>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">รายได้/ปี</small>
                                <strong class="text-success" id="yearlyEarnings">-</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary btn-lg w-100 mb-3" id="addLiquidityBtn" disabled>
                        <i class="fas fa-plus-circle me-2"></i>
                        เพิ่ม Liquidity
                    </button>

                    <!-- Info Box -->
                    <div class="alert alert-warning border-0 rounded-3">
                        <h6 class="fw-bold mb-2">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            คำเตือนสำคัญ
                        </h6>
                        <small class="text-muted">
                            • การเพิ่ม Liquidity มีความเสี่ยงจาก <strong>Impermanent Loss</strong><br>
                            • ควรศึกษาข้อมูลก่อนลงทุน<br>
                            • รายได้ที่แสดงเป็นเพียงการประมาณการ
                        </small>
                    </div>
                </div>
            </div>

            <!-- Educational Section -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">
                        <i class="fas fa-graduation-cap text-primary me-2"></i>
                        ทำไมต้องเพิ่ม Liquidity?
                    </h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <small>รับส่วนแบ่งค่าธรรมเนียม 0.3% จากทุก swap</small>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <small>สร้างรายได้แบบ Passive Income</small>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <small>ถอนได้ทุกเมื่อ ไม่มีการล็อก</small>
                        </li>
                        <li class="mb-0">
                            <i class="fas fa-info-circle text-info me-2"></i>
                            <small>รายได้ขึ้นอยู่กับปริมาณการซื้อขายใน Pool</small>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Side Info Panel -->
        <div class="col-lg-4">
            <!-- My LP Positions Summary -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">
                        <i class="fas fa-wallet text-primary me-2"></i>
                        ตำแหน่ง Liquidity ของคุณ
                    </h6>
                    <div class="text-center py-3">
                        <h3 class="mb-1" id="myTotalLiquidity">฿0.00</h3>
                        <small class="text-muted">มูลค่าสภาพคล่องทั้งหมด</small>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <small class="text-muted">จำนวน Pools</small>
                        <small class="fw-bold" id="myPoolsCount">0</small>
                    </div>
                    <div class="d-flex justify-content-between">
                        <small class="text-muted">รายได้รวม (24ชม.)</small>
                        <small class="text-success fw-bold" id="myEarnings24h">฿0.00</small>
                    </div>
                    <a href="{{ route('user.dex.my-positions') }}" class="btn btn-outline-primary btn-sm w-100 mt-3">
                        ดูทั้งหมด
                    </a>
                </div>
            </div>

            <!-- Top Pools -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">
                        <i class="fas fa-fire text-danger me-2"></i>
                        Pools ยอดนิยม
                    </h6>
                    <div id="topPools">
                        <!-- Will be populated via AJAX -->
                        <div class="text-center py-4">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Token Select Modal -->
<div class="modal fade" id="tokenSelectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">เลือก Token</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" class="form-control mb-3" id="tokenSearch" placeholder="ค้นหา Token...">
                <div id="tokenList" style="max-height: 400px; overflow-y: auto;">
                    <!-- Tokens will be loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let selectedTarget = '';
    let tokenAData = null;
    let tokenBData = null;
    let poolData = null;

    // Open token selector
    $('[data-bs-target="#tokenSelectModal"]').click(function() {
        selectedTarget = $(this).data('target');
        loadTokens();
    });

    // Load available tokens
    function loadTokens() {
        $.ajax({
            url: '/api/v1/tpix/tokens',
            method: 'GET',
            success: function(response) {
                let html = '';
                response.data.forEach(token => {
                    html += `
                        <div class="token-item p-3 border-bottom" style="cursor: pointer;" data-token='${JSON.stringify(token)}'>
                            <div class="d-flex align-items-center">
                                <img src="${token.logo_url}" class="rounded-circle me-3" width="40" height="40" onerror="this.src='/images/default-token.png'">
                                <div class="flex-grow-1">
                                    <div class="fw-bold">${token.name} <small class="text-muted">${token.symbol}</small></div>
                                    <small class="text-muted">คงเหลือ: ${token.balance || '0.00'}</small>
                                </div>
                            </div>
                        </div>
                    `;
                });
                $('#tokenList').html(html);

                // Token selection
                $('.token-item').click(function() {
                    const token = JSON.parse($(this).attr('data-token'));
                    selectToken(token);
                    $('#tokenSelectModal').modal('hide');
                });
            }
        });
    }

    // Select token
    function selectToken(token) {
        if (selectedTarget === 'a') {
            tokenAData = token;
            $('#tokenA').html(`
                <img src="${token.logo_url}" width="24" height="24" class="rounded-circle me-2" onerror="this.src='/images/default-token.png'">
                ${token.symbol}
            `);
            $('#balanceA').text(token.balance || '0.00');
        } else if (selectedTarget === 'b') {
            tokenBData = token;
            $('#tokenB').html(`
                <img src="${token.logo_url}" width="24" height="24" class="rounded-circle me-2" onerror="this.src='/images/default-token.png'">
                ${token.symbol}
            `);
            $('#balanceB').text(token.balance || '0.00');
        }

        checkBothTokensSelected();
    }

    // Check if both tokens selected
    function checkBothTokensSelected() {
        if (tokenAData && tokenBData) {
            checkPool();
            $('#addLiquidityBtn').prop('disabled', false);
        }
    }

    // Check if pool exists
    function checkPool() {
        $.ajax({
            url: '/api/v1/tpix/dex/pools/find',
            data: {
                token_a_id: tokenAData.id,
                token_b_id: tokenBData.id
            },
            success: function(response) {
                if (response.data) {
                    poolData = response.data;
                    displayPoolInfo();
                    $('#newPoolNotice').hide();
                } else {
                    poolData = null;
                    $('#poolInfo').hide();
                    $('#newPoolNotice').show();
                }
            }
        });
    }

    // Display pool info
    function displayPoolInfo() {
        $('#poolInfo').show();
        $('#poolTVL').text('฿' + formatNumber(poolData.tvl));
        $('#poolRatio').text(`1 ${tokenAData.symbol} = ${poolData.price} ${tokenBData.symbol}`);
        $('#poolAPY').text(poolData.apy.toFixed(2) + '%');
    }

    // Amount change - calculate
    $('#amountA, #amountB').on('input', function() {
        calculateLiquidity();
    });

    // Calculate liquidity
    function calculateLiquidity() {
        const amountA = parseFloat($('#amountA').val()) || 0;
        const amountB = parseFloat($('#amountB').val()) || 0;

        if (amountA > 0 && amountB > 0 && poolData) {
            // Show expected output
            $('#expectedOutput, #estimatedEarnings').show();

            // Calculate LP tokens (simplified)
            const lpTokens = Math.sqrt(amountA * amountB);
            $('#lpTokenAmount').text(formatNumber(lpTokens));

            // Calculate share
            const totalLiquidity = parseFloat(poolData.total_liquidity) || 1;
            const share = ((lpTokens / (totalLiquidity + lpTokens)) * 100).toFixed(4);
            $('#sharePercent').text(share + '%');

            // Estimate earnings based on pool APY
            const totalValue = amountA * tokenAData.price + amountB * tokenBData.price;
            const yearlyEarning = totalValue * (poolData.apy / 100);
            $('#dailyEarnings').text('฿' + formatNumber(yearlyEarning / 365));
            $('#monthlyEarnings').text('฿' + formatNumber(yearlyEarning / 12));
            $('#yearlyEarnings').text('฿' + formatNumber(yearlyEarning));
        }
    }

    // Max buttons
    $('#maxButtonA').click(function() {
        $('#amountA').val(tokenAData.balance);
        calculateLiquidity();
    });

    $('#maxButtonB').click(function() {
        $('#amountB').val(tokenBData.balance);
        calculateLiquidity();
    });

    // Submit
    $('#addLiquidityBtn').click(function() {
        const amountA = $('#amountA').val();
        const amountB = $('#amountB').val();

        if (!amountA || !amountB) {
            alert('กรุณากรอกจำนวน Token ทั้งสอง');
            return;
        }

        $.ajax({
            url: '/api/v1/tpix/dex/liquidity/add',
            method: 'POST',
            data: {
                token_a_id: tokenAData.id,
                token_b_id: tokenBData.id,
                amount_a: amountA,
                amount_b: amountB,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                alert('เพิ่ม Liquidity สำเร็จ!');
                window.location.href = '{{ route("user.dex.my-positions") }}';
            },
            error: function(xhr) {
                alert('เกิดข้อผิดพลาด: ' + (xhr.responseJSON?.message || 'Unknown error'));
            }
        });
    });

    // Load top pools
    loadTopPools();

    function loadTopPools() {
        $.ajax({
            url: '/api/v1/tpix/dex/pools/top',
            success: function(response) {
                let html = '';
                response.data.slice(0, 5).forEach(pool => {
                    html += `
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <div class="fw-bold small">${pool.pair}</div>
                                <small class="text-muted">TVL: ฿${formatNumber(pool.tvl)}</small>
                            </div>
                            <span class="badge bg-success">${pool.apy.toFixed(2)}%</span>
                        </div>
                    `;
                });
                $('#topPools').html(html);
            }
        });
    }

    // Format number helper
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
.token-item:hover {
    background-color: #f8f9fa;
}

.token-selector {
    min-width: 140px;
    border-left: 1px solid #dee2e6;
}

.swap-input .form-control:focus {
    box-shadow: none;
}
</style>
@endpush
