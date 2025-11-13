@extends('layouts.app')

@section('title', 'ตำแหน่ง Liquidity ของฉัน - TPIX DEX')

@section('content')
<div class="container py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">
                <i class="fas fa-wallet text-primary me-2"></i>
                ตำแหน่ง Liquidity ของฉัน
            </h2>
            <p class="text-muted mb-0">จัดการสภาพคล่องของคุณใน DEX</p>
        </div>
        <a href="{{ route('user.dex.add-liquidity') }}" class="btn btn-primary">
            <i class="fas fa-plus-circle me-2"></i>เพิ่ม Liquidity
        </a>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <small class="text-muted">มูลค่าสภาพคล่องทั้งหมด</small>
                        <i class="fas fa-coins text-primary"></i>
                    </div>
                    <h4 class="mb-0" id="totalLiquidity">฿0.00</h4>
                    <small class="text-success" id="totalLiquidityChange">+0.00%</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <small class="text-muted">รายได้รวม (24ชม.)</small>
                        <i class="fas fa-chart-line text-success"></i>
                    </div>
                    <h4 class="mb-0" id="totalEarnings24h">฿0.00</h4>
                    <small class="text-muted" id="earningsAPY">APY: 0.00%</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <small class="text-muted">จำนวน Pools</small>
                        <i class="fas fa-swimming-pool text-info"></i>
                    </div>
                    <h4 class="mb-0" id="totalPools">0</h4>
                    <small class="text-muted" id="activePools">Active: 0</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <small class="text-muted">ROI เฉลี่ย</small>
                        <i class="fas fa-percentage text-warning"></i>
                    </div>
                    <h4 class="mb-0" id="avgROI">0.00%</h4>
                    <small class="text-muted" id="totalProfit">Profit: ฿0.00</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" id="searchPool" placeholder="ค้นหา Pool...">
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="statusFilter">
                        <option value="">ทุกสถานะ</option>
                        <option value="active">Active</option>
                        <option value="withdrawn">Withdrawn</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="sortBy">
                        <option value="value_desc">มูลค่า: สูง-ต่ำ</option>
                        <option value="value_asc">มูลค่า: ต่ำ-สูง</option>
                        <option value="roi_desc">ROI: สูง-ต่ำ</option>
                        <option value="date_desc">วันที่: ใหม่-เก่า</option>
                        <option value="date_asc">วันที่: เก่า-ใหม่</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-secondary w-100" id="refreshBtn">
                        <i class="fas fa-sync-alt"></i> รีเฟรช
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Positions List -->
    <div class="row" id="positionsList">
        <!-- Loading state -->
        <div class="col-12 text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-muted mt-2">กำลังโหลดข้อมูล...</p>
        </div>
    </div>

    <!-- Empty State -->
    <div id="emptyState" class="text-center py-5" style="display: none;">
        <i class="fas fa-wallet text-muted" style="font-size: 4rem;"></i>
        <h5 class="mt-3">ยังไม่มีตำแหน่ง Liquidity</h5>
        <p class="text-muted">เริ่มต้นเพิ่มสภาพคล่องและรับรายได้จากค่าธรรมเนียม</p>
        <a href="{{ route('user.dex.add-liquidity') }}" class="btn btn-primary">
            <i class="fas fa-plus-circle me-2"></i>เพิ่ม Liquidity ครั้งแรก
        </a>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let allPositions = [];

    // Load positions
    loadPositions();

    function loadPositions() {
        $.ajax({
            url: '/api/v1/tpix/dex/my-positions',
            method: 'GET',
            success: function(response) {
                allPositions = response.data;
                displaySummary(response.summary);
                displayPositions(allPositions);
            },
            error: function() {
                $('#positionsList').html(`
                    <div class="col-12">
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ไม่สามารถโหลดข้อมูลได้ กรุณาลองใหม่อีกครั้ง
                        </div>
                    </div>
                `);
            }
        });
    }

    function displaySummary(summary) {
        $('#totalLiquidity').text('฿' + formatNumber(summary.total_liquidity));
        $('#totalLiquidityChange').text((summary.change_24h >= 0 ? '+' : '') + summary.change_24h.toFixed(2) + '%')
            .removeClass('text-success text-danger')
            .addClass(summary.change_24h >= 0 ? 'text-success' : 'text-danger');

        $('#totalEarnings24h').text('฿' + formatNumber(summary.earnings_24h));
        $('#earningsAPY').text('APY: ' + summary.avg_apy.toFixed(2) + '%');

        $('#totalPools').text(summary.total_pools);
        $('#activePools').text('Active: ' + summary.active_pools);

        $('#avgROI').text(summary.avg_roi.toFixed(2) + '%')
            .removeClass('text-success text-danger')
            .addClass(summary.avg_roi >= 0 ? 'text-success' : 'text-danger');
        $('#totalProfit').text('Profit: ฿' + formatNumber(summary.total_profit));
    }

    function displayPositions(positions) {
        if (positions.length === 0) {
            $('#positionsList').hide();
            $('#emptyState').show();
            return;
        }

        $('#positionsList').show();
        $('#emptyState').hide();

        let html = '';
        positions.forEach(position => {
            const isProfit = position.roi_percent >= 0;
            html += `
                <div class="col-lg-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <!-- Header -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="position-relative me-3">
                                        <img src="${position.pool.token_a.logo_url}" class="rounded-circle" width="36" height="36" style="border: 2px solid white;" onerror="this.src='/images/default-token.png'">
                                        <img src="${position.pool.token_b.logo_url}" class="rounded-circle" width="36" height="36" style="border: 2px solid white; margin-left: -12px;" onerror="this.src='/images/default-token.png'">
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-bold">${position.pool.pair}</h6>
                                        <small class="text-muted">Pool #${position.pool.id}</small>
                                    </div>
                                </div>
                                <span class="badge ${position.status === 'active' ? 'bg-success' : 'bg-secondary'}">
                                    ${position.status === 'active' ? 'Active' : 'Withdrawn'}
                                </span>
                            </div>

                            <!-- Stats -->
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <div class="bg-light rounded p-2">
                                        <small class="text-muted d-block">มูลค่าปัจจุบัน</small>
                                        <strong class="text-primary">฿${formatNumber(position.current_value)}</strong>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="bg-light rounded p-2">
                                        <small class="text-muted d-block">LP Tokens</small>
                                        <strong>${formatNumber(position.lp_token_balance)}</strong>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="bg-light rounded p-2">
                                        <small class="text-muted d-block">ส่วนแบ่งใน Pool</small>
                                        <strong>${position.share_percentage.toFixed(4)}%</strong>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="bg-light rounded p-2">
                                        <small class="text-muted d-block">APY</small>
                                        <strong class="text-success">${position.pool.apy.toFixed(2)}%</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- Token Amounts -->
                            <div class="border rounded p-2 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div class="d-flex align-items-center">
                                        <img src="${position.pool.token_a.logo_url}" width="20" height="20" class="rounded-circle me-2" onerror="this.src='/images/default-token.png'">
                                        <small>${position.pool.token_a.symbol}</small>
                                    </div>
                                    <small class="fw-bold">${formatNumber(position.token_a_amount)}</small>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <img src="${position.pool.token_b.logo_url}" width="20" height="20" class="rounded-circle me-2" onerror="this.src='/images/default-token.png'">
                                        <small>${position.pool.token_b.symbol}</small>
                                    </div>
                                    <small class="fw-bold">${formatNumber(position.token_b_amount)}</small>
                                </div>
                            </div>

                            <!-- ROI & Profit -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <small class="text-muted d-block">ROI</small>
                                    <strong class="${isProfit ? 'text-success' : 'text-danger'}">
                                        ${isProfit ? '+' : ''}${position.roi_percent.toFixed(2)}%
                                    </strong>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted d-block">กำไร/ขาดทุน</small>
                                    <strong class="${isProfit ? 'text-success' : 'text-danger'}">
                                        ฿${formatNumber(position.profit_loss)}
                                    </strong>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted d-block">ค่าธรรมเนียม</small>
                                    <strong class="text-primary">฿${formatNumber(position.fees_earned)}</strong>
                                </div>
                            </div>

                            <!-- Impermanent Loss Warning -->
                            ${position.impermanent_loss && position.impermanent_loss.loss_percentage < -1 ? `
                                <div class="alert alert-warning py-2 px-3 mb-3">
                                    <small>
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        Impermanent Loss: ${position.impermanent_loss.loss_percentage.toFixed(2)}%
                                    </small>
                                </div>
                            ` : ''}

                            <!-- Actions -->
                            <div class="d-flex gap-2">
                                ${position.status === 'active' ? `
                                    <a href="${window.location.origin}/user/dex/remove-liquidity/${position.id}" class="btn btn-outline-danger btn-sm flex-fill">
                                        <i class="fas fa-minus-circle me-1"></i>ถอน
                                    </a>
                                    <button class="btn btn-outline-primary btn-sm flex-fill add-more-btn" data-pool-id="${position.pool.id}">
                                        <i class="fas fa-plus-circle me-1"></i>เพิ่ม
                                    </button>
                                ` : ''}
                                <button class="btn btn-outline-secondary btn-sm view-details-btn" data-position-id="${position.id}">
                                    <i class="fas fa-chart-line me-1"></i>รายละเอียด
                                </button>
                            </div>

                            <!-- Footer Info -->
                            <div class="mt-3 pt-3 border-top">
                                <div class="d-flex justify-content-between text-muted">
                                    <small><i class="far fa-calendar me-1"></i>${new Date(position.created_at).toLocaleDateString('th-TH')}</small>
                                    <small><i class="far fa-clock me-1"></i>${calculateDuration(position.created_at)}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        $('#positionsList').html(html);

        // Bind event handlers
        $('.add-more-btn').click(function() {
            const poolId = $(this).data('pool-id');
            window.location.href = '{{ route("user.dex.add-liquidity") }}?pool_id=' + poolId;
        });

        $('.view-details-btn').click(function() {
            const positionId = $(this).data('position-id');
            showPositionDetails(positionId);
        });
    }

    function showPositionDetails(positionId) {
        // Show detailed analytics modal
        alert('Position details modal - To be implemented');
    }

    // Search & Filter
    $('#searchPool, #statusFilter, #sortBy').on('change input', function() {
        filterAndSort();
    });

    function filterAndSort() {
        let filtered = allPositions;

        // Search
        const searchTerm = $('#searchPool').val().toLowerCase();
        if (searchTerm) {
            filtered = filtered.filter(p =>
                p.pool.pair.toLowerCase().includes(searchTerm) ||
                p.pool.token_a.symbol.toLowerCase().includes(searchTerm) ||
                p.pool.token_b.symbol.toLowerCase().includes(searchTerm)
            );
        }

        // Status filter
        const status = $('#statusFilter').val();
        if (status) {
            filtered = filtered.filter(p => p.status === status);
        }

        // Sort
        const sortBy = $('#sortBy').val();
        filtered.sort((a, b) => {
            switch(sortBy) {
                case 'value_desc': return b.current_value - a.current_value;
                case 'value_asc': return a.current_value - b.current_value;
                case 'roi_desc': return b.roi_percent - a.roi_percent;
                case 'date_desc': return new Date(b.created_at) - new Date(a.created_at);
                case 'date_asc': return new Date(a.created_at) - new Date(b.created_at);
                default: return 0;
            }
        });

        displayPositions(filtered);
    }

    // Refresh
    $('#refreshBtn').click(function() {
        $(this).find('i').addClass('fa-spin');
        loadPositions();
        setTimeout(() => {
            $(this).find('i').removeClass('fa-spin');
        }, 1000);
    });

    function calculateDuration(createdAt) {
        const now = new Date();
        const created = new Date(createdAt);
        const diff = now - created;
        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        if (days === 0) return 'วันนี้';
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
