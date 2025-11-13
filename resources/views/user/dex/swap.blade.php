@extends('layouts.app')

@section('title', 'แลกเปลี่ยน Token - TPIX DEX')

@section('content')
<div class="container py-4">
    <!-- DEX Hero Section -->
    <div class="dex-hero text-center mb-5" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 60px 20px; border-radius: 20px; color: white;">
        <h1 class="display-4 fw-bold mb-3">
            <i class="fas fa-exchange-alt me-2"></i>
            TPIX DEX
        </h1>
        <p class="lead mb-0">แลกเปลี่ยน Token ได้ทันที ด้วยเทคโนโลยี AMM</p>
        <p class="small opacity-75 mt-2">ค่าธรรมเนียม 0.3% | ไม่มีค่าแก๊สเพิ่มเติม</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            <!-- Swap Card -->
            <div class="card shadow-lg border-0" style="border-radius: 24px;">
                <div class="card-body p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0 fw-bold">แลกเปลี่ยน Token</h5>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#settingsModal">
                            <i class="fas fa-cog"></i>
                        </button>
                    </div>

                    <form id="swapForm">
                        @csrf

                        <!-- From Token -->
                        <div class="swap-input-container mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <small class="text-muted">จาก</small>
                                <small class="text-muted">คงเหลือ: <span id="fromBalance">0.00</span></small>
                            </div>
                            <div class="input-group swap-input">
                                <input type="number" class="form-control form-control-lg border-0"
                                       id="amountIn" placeholder="0.0" step="0.00000001" required>
                                <button class="btn btn-light token-selector" type="button" data-bs-toggle="modal" data-bs-target="#tokenSelectModal" data-target="from">
                                    <span id="fromToken">
                                        <i class="fas fa-coins text-primary me-2"></i>
                                        เลือก Token
                                    </span>
                                    <i class="fas fa-chevron-down ms-2"></i>
                                </button>
                            </div>
                            <button type="button" class="btn btn-sm btn-link text-primary p-0 mt-1" id="maxButton">
                                <small>ใช้ทั้งหมด</small>
                            </button>
                        </div>

                        <!-- Swap Direction Button -->
                        <div class="text-center my-3">
                            <button type="button" class="btn btn-light rounded-circle swap-direction-btn" id="swapDirection">
                                <i class="fas fa-arrow-down"></i>
                            </button>
                        </div>

                        <!-- To Token -->
                        <div class="swap-input-container mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <small class="text-muted">ไปยัง</small>
                                <small class="text-muted">คงเหลือ: <span id="toBalance">0.00</span></small>
                            </div>
                            <div class="input-group swap-input">
                                <input type="number" class="form-control form-control-lg border-0"
                                       id="amountOut" placeholder="0.0" readonly>
                                <button class="btn btn-light token-selector" type="button" data-bs-toggle="modal" data-bs-target="#tokenSelectModal" data-target="to">
                                    <span id="toToken">
                                        <i class="fas fa-coins text-success me-2"></i>
                                        เลือก Token
                                    </span>
                                    <i class="fas fa-chevron-down ms-2"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Price Info -->
                        <div id="priceInfo" class="mb-4" style="display: none;">
                            <div class="alert alert-light border rounded-3 p-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <small class="text-muted">ราคา</small>
                                    <small class="fw-bold" id="price">-</small>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <small class="text-muted">Slippage Tolerance</small>
                                    <small id="slippageDisplay">0.5%</small>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <small class="text-muted">Price Impact</small>
                                    <small id="priceImpact" class="text-success">-</small>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted">ค่าธรรมเนียม (0.3%)</small>
                                    <small id="fee">-</small>
                                </div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted">คุณจะได้รับอย่างน้อย</small>
                                    <small class="fw-bold" id="minimumReceived">-</small>
                                </div>
                            </div>
                        </div>

                        <!-- Swap Button -->
                        <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold" id="swapButton" style="border-radius: 16px; padding: 16px;" disabled>
                            เลือก Token
                        </button>

                        <!-- Warning Messages -->
                        <div id="warningMessage" class="alert alert-warning mt-3" style="display: none;"></div>
                    </form>
                </div>
            </div>

            <!-- Recent Swaps -->
            <div class="card mt-4 border-0 shadow-sm">
                <div class="card-header bg-transparent border-0">
                    <h6 class="mb-0 fw-bold">
                        <i class="fas fa-history me-2"></i>
                        การแลกเปลี่ยนล่าสุด
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" id="recentSwaps">
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-exchange-alt fa-2x mb-2 opacity-25"></i>
                            <p class="small mb-0">ยังไม่มีประวัติการแลกเปลี่ยน</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Token Select Modal -->
<div class="modal fade" id="tokenSelectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">เลือก Token</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" class="form-control mb-3" id="tokenSearch" placeholder="ค้นหา Token ด้วยชื่อหรือสัญลักษณ์...">
                <div id="tokenList">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">กำลังโหลด...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Settings Modal -->
<div class="modal fade" id="settingsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ตั้งค่าการแลกเปลี่ยน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Slippage Tolerance (%)</label>
                <div class="btn-group w-100 mb-3" role="group">
                    <input type="radio" class="btn-check" name="slippage" id="slippage01" value="0.1">
                    <label class="btn btn-outline-primary" for="slippage01">0.1%</label>

                    <input type="radio" class="btn-check" name="slippage" id="slippage05" value="0.5" checked>
                    <label class="btn btn-outline-primary" for="slippage05">0.5%</label>

                    <input type="radio" class="btn-check" name="slippage" id="slippage10" value="1.0">
                    <label class="btn btn-outline-primary" for="slippage10">1.0%</label>

                    <input type="radio" class="btn-check" name="slippage" id="slippageCustom" value="custom">
                    <label class="btn btn-outline-primary" for="slippageCustom">กำหนดเอง</label>
                </div>
                <input type="number" class="form-control" id="customSlippage" placeholder="กำหนดเอง %" step="0.1" style="display: none;">

                <div class="alert alert-info mt-3">
                    <small>
                        <i class="fas fa-info-circle me-1"></i>
                        Slippage ที่สูงเกินไปอาจทำให้คุณเสียเปรียบในการแลกเปลี่ยน
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<style>
.swap-input-container {
    background: #f8f9fa;
    border-radius: 16px;
    padding: 16px;
}

.swap-input .form-control {
    background: transparent;
    font-size: 1.5rem;
    font-weight: 600;
}

.swap-input .form-control:focus {
    box-shadow: none;
}

.token-selector {
    border-radius: 12px;
    padding: 8px 16px;
    font-weight: 600;
    white-space: nowrap;
}

.token-selector:hover {
    background: #e9ecef !important;
}

.swap-direction-btn {
    width: 40px;
    height: 40px;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

.swap-direction-btn:hover {
    transform: rotate(180deg);
    background: #667eea !important;
    color: white !important;
    border-color: #667eea !important;
}

.token-item {
    cursor: pointer;
    transition: all 0.2s ease;
}

.token-item:hover {
    background: #f8f9fa;
}

.price-impact-low {
    color: #28a745;
}

.price-impact-medium {
    color: #ffc107;
}

.price-impact-high {
    color: #dc3545;
}
</style>

@push('scripts')
<script>
let fromTokenData = null;
let toTokenData = null;
let slippageTolerance = 0.5;
let selectingFor = 'from';
let quoteTimeout = null;

$(document).ready(function() {
    loadTokenList();
    loadRecentSwaps();

    // Amount input change
    $('#amountIn').on('input', debounce(getQuote, 500));

    // Max button
    $('#maxButton').click(function() {
        if (fromTokenData) {
            $('#amountIn').val(fromTokenData.balance).trigger('input');
        }
    });

    // Swap direction
    $('#swapDirection').click(function() {
        const temp = fromTokenData;
        fromTokenData = toTokenData;
        toTokenData = temp;

        updateTokenDisplay();
        getQuote();
    });

    // Slippage settings
    $('input[name="slippage"]').change(function() {
        if ($(this).val() === 'custom') {
            $('#customSlippage').show().focus();
        } else {
            $('#customSlippage').hide();
            slippageTolerance = parseFloat($(this).val());
            $('#slippageDisplay').text(slippageTolerance + '%');
            getQuote();
        }
    });

    $('#customSlippage').on('input', function() {
        slippageTolerance = parseFloat($(this).val()) || 0.5;
        $('#slippageDisplay').text(slippageTolerance + '%');
        getQuote();
    });

    // Token search
    $('#tokenSearch').on('input', debounce(searchTokens, 300));

    // Swap form submit
    $('#swapForm').submit(function(e) {
        e.preventDefault();
        executeSwap();
    });
});

function loadTokenList() {
    $.ajax({
        url: '/api/v1/tpix/tokens',
        method: 'GET',
        data: { status: 'active', per_page: 50 },
        success: function(response) {
            displayTokenList(response.data);
        }
    });
}

function displayTokenList(tokens) {
    const html = tokens.map(token => `
        <div class="token-item list-group-item list-group-item-action" data-token='${JSON.stringify(token)}'>
            <div class="d-flex align-items-center">
                <img src="${token.logo || '/images/default-token.png'}" alt="${token.symbol}"
                     class="rounded-circle me-3" width="40" height="40">
                <div class="flex-grow-1">
                    <div class="fw-bold">${token.symbol}</div>
                    <small class="text-muted">${token.name}</small>
                </div>
                <div class="text-end">
                    <div class="fw-bold">${parseFloat(token.balance || 0).toFixed(4)}</div>
                    <small class="text-muted">฿${parseFloat(token.current_price_tpix || 0).toFixed(2)}</small>
                </div>
            </div>
        </div>
    `).join('');

    $('#tokenList').html(html);

    $('.token-item').click(function() {
        const token = JSON.parse($(this).attr('data-token'));
        selectToken(token);
    });
}

function selectToken(token) {
    if (selectingFor === 'from') {
        fromTokenData = token;
        $('#fromToken').html(`
            <img src="${token.logo || '/images/default-token.png'}" width="24" height="24" class="rounded-circle me-2">
            ${token.symbol}
        `);
        $('#fromBalance').text(parseFloat(token.balance || 0).toFixed(4));
    } else {
        toTokenData = token;
        $('#toToken').html(`
            <img src="${token.logo || '/images/default-token.png'}" width="24" height="24" class="rounded-circle me-2">
            ${token.symbol}
        `);
        $('#toBalance').text(parseFloat(token.balance || 0).toFixed(4));
    }

    $('#tokenSelectModal').modal('hide');
    updateSwapButton();
    getQuote();
}

function getQuote() {
    const amountIn = $('#amountIn').val();

    if (!fromTokenData || !toTokenData || !amountIn || amountIn <= 0) {
        $('#priceInfo').hide();
        return;
    }

    $.ajax({
        url: '/api/v1/tpix/dex/quote',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        data: {
            token_in_id: fromTokenData.id,
            token_out_id: toTokenData.id,
            amount_in: amountIn
        },
        success: function(response) {
            displayQuote(response.data);
        },
        error: function(xhr) {
            console.error('Quote error:', xhr.responseJSON);
        }
    });
}

function displayQuote(quote) {
    $('#amountOut').val(parseFloat(quote.amount_out).toFixed(8));
    $('#price').text(`1 ${fromTokenData.symbol} = ${parseFloat(quote.price).toFixed(6)} ${toTokenData.symbol}`);
    $('#fee').text(`${parseFloat(quote.fee).toFixed(6)} ${fromTokenData.symbol}`);
    $('#minimumReceived').text(`${parseFloat(quote.minimum_received).toFixed(6)} ${toTokenData.symbol}`);

    const impact = parseFloat(quote.price_impact);
    let impactClass = 'price-impact-low';
    if (impact > 5) impactClass = 'price-impact-medium';
    if (impact > 15) impactClass = 'price-impact-high';

    $('#priceImpact').text(impact.toFixed(2) + '%').removeClass().addClass(impactClass);

    if (impact > 15) {
        $('#warningMessage').html(`
            <i class="fas fa-exclamation-triangle me-2"></i>
            Price Impact สูงมาก (${impact.toFixed(2)}%)! คุณอาจสูญเสียมูลค่า
        `).show();
    } else {
        $('#warningMessage').hide();
    }

    $('#priceInfo').show();
}

function updateSwapButton() {
    const amountIn = $('#amountIn').val();

    if (!fromTokenData || !toTokenData) {
        $('#swapButton').text('เลือก Token').prop('disabled', true);
    } else if (!amountIn || amountIn <= 0) {
        $('#swapButton').text('ใส่จำนวน').prop('disabled', true);
    } else if (parseFloat(amountIn) > parseFloat(fromTokenData.balance || 0)) {
        $('#swapButton').text('ยอดเงินไม่เพียงพอ').prop('disabled', true);
    } else {
        $('#swapButton').text('แลกเปลี่ยน').prop('disabled', false);
    }
}

function executeSwap() {
    const amountIn = $('#amountIn').val();
    const amountOut = $('#amountOut').val();

    $('#swapButton').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>กำลังดำเนินการ...');

    $.ajax({
        url: '/api/v1/tpix/dex/swap',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        data: {
            token_in_id: fromTokenData.id,
            token_out_id: toTokenData.id,
            amount_in: amountIn,
            min_amount_out: parseFloat(amountOut) * (1 - slippageTolerance / 100),
            slippage_tolerance: slippageTolerance
        },
        success: function(response) {
            alert('แลกเปลี่ยนสำเร็จ!\nTx Hash: ' + response.data.tx_hash);
            $('#amountIn').val('');
            $('#amountOut').val('');
            $('#priceInfo').hide();
            loadRecentSwaps();
            updateSwapButton();
        },
        error: function(xhr) {
            alert('เกิดข้อผิดพลาด: ' + (xhr.responseJSON?.message || 'กรุณาลองใหม่อีกครั้ง'));
        },
        complete: function() {
            $('#swapButton').prop('disabled', false).text('แลกเปลี่ยน');
        }
    });
}

function loadRecentSwaps() {
    $.ajax({
        url: '/api/v1/tpix/dex/my/swaps',
        method: 'GET',
        success: function(response) {
            if (response.data && response.data.length > 0) {
                const html = response.data.slice(0, 5).map(swap => `
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold">${swap.direction}</div>
                                <small class="text-muted">${new Date(swap.created_at).toLocaleString('th-TH')}</small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-${swap.status === 'completed' ? 'success' : swap.status === 'pending' ? 'warning' : 'danger'}">
                                    ${swap.status === 'completed' ? 'สำเร็จ' : swap.status === 'pending' ? 'รอดำเนินการ' : 'ล้มเหลว'}
                                </span>
                            </div>
                        </div>
                    </div>
                `).join('');
                $('#recentSwaps').html(html);
            }
        }
    });
}

function searchTokens() {
    const query = $('#tokenSearch').val().toLowerCase();
    $('.token-item').each(function() {
        const token = JSON.parse($(this).attr('data-token'));
        const match = token.name.toLowerCase().includes(query) || token.symbol.toLowerCase().includes(query);
        $(this).toggle(match);
    });
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

$('#tokenSelectModal').on('show.bs.modal', function(e) {
    selectingFor = $(e.relatedTarget).data('target');
});

$('#amountIn').on('input', updateSwapButton);
</script>
@endpush
@endsection
