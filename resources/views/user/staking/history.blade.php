@extends('layouts.user-arrow-x')

@section('title', 'ประวัติ Staking - TPIX')

@section('content')
<div class="container py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">
                <i class="fas fa-history text-primary me-2"></i>
                ประวัติ Staking
            </h2>
            <p class="text-muted mb-0">ดูประวัติการ Stake และ Unstake ทั้งหมด</p>
        </div>
        <a href="{{ route('user.staking.index') }}" class="btn btn-primary">
            <i class="fas fa-lock me-2"></i>Stake Token
        </a>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <small class="text-muted">Staked ทั้งหมด</small>
                    <h4 class="mb-0" id="totalStaked">฿0.00</h4>
                    <small class="text-muted" id="totalStakedTokens">0 Tokens</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <small class="text-muted">รางวัลที่ได้รับ</small>
                    <h4 class="mb-0 text-success" id="totalRewards">฿0.00</h4>
                    <small class="text-muted" id="totalRewardsTokens">0 Tokens</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <small class="text-muted">Active Stakes</small>
                    <h4 class="mb-0" id="activeStakes">0</h4>
                    <small class="text-muted" id="activePools">ใน 0 Pools</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <small class="text-muted">AVG APY</small>
                    <h4 class="mb-0 text-primary" id="avgAPY">0.00%</h4>
                    <small class="text-muted" id="avgDuration">AVG: 0 วัน</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" id="searchToken" placeholder="ค้นหา Token...">
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="typeFilter">
                        <option value="">ทุกประเภท</option>
                        <option value="stake">Stake</option>
                        <option value="unstake">Unstake</option>
                        <option value="claim">Claim</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="statusFilter">
                        <option value="">ทุกสถานะ</option>
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                        <option value="withdrawn">Withdrawn</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="timeFilter">
                        <option value="all">ทุกช่วงเวลา</option>
                        <option value="today">วันนี้</option>
                        <option value="week">7 วันที่แล้ว</option>
                        <option value="month">30 วันที่แล้ว</option>
                        <option value="year">ปีนี้</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="sortBy">
                        <option value="date_desc">วันที่: ใหม่-เก่า</option>
                        <option value="date_asc">วันที่: เก่า-ใหม่</option>
                        <option value="amount_desc">จำนวน: สูง-ต่ำ</option>
                        <option value="amount_asc">จำนวน: ต่ำ-สูง</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button class="btn btn-outline-secondary w-100" id="exportBtn">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- History Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>วันที่ / เวลา</th>
                            <th>ประเภท</th>
                            <th>Pool</th>
                            <th class="text-end">จำนวน</th>
                            <th>Lock Period</th>
                            <th class="text-end">APY</th>
                            <th class="text-end">รางวัล</th>
                            <th class="text-center">สถานะ</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="historyTable">
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="text-muted mt-2 mb-0">กำลังโหลดข้อมูล...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-3 border-top" id="paginationContainer" style="display: none;">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted" id="paginationInfo">แสดง 0-0 จาก 0 รายการ</small>
                    <div id="paginationButtons"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Empty State -->
    <div id="emptyState" class="text-center py-5" style="display: none;">
        <i class="fas fa-history text-muted" style="font-size: 4rem;"></i>
        <h5 class="mt-3">ยังไม่มีประวัติ Staking</h5>
        <p class="text-muted">เริ่มต้น Stake Token เพื่อดูประวัติที่นี่</p>
        <a href="{{ route('user.staking.index') }}" class="btn btn-primary">
            <i class="fas fa-lock me-2"></i>Stake Token
        </a>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2"></i>
                    รายละเอียด Stake
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailsContent">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let allStakes = [];
    let currentPage = 1;
    let perPage = 20;

    // Load history
    loadHistory();

    function loadHistory() {
        $.ajax({
            url: '/api/v1/tpix/staking/history',
            success: function(response) {
                allStakes = response.data;
                displaySummary(response.summary);
                filterAndDisplay();
            },
            error: function() {
                $('#historyTable').html(`
                    <tr>
                        <td colspan="9" class="text-center py-4">
                            <div class="alert alert-danger mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                ไม่สามารถโหลดข้อมูลได้
                            </div>
                        </td>
                    </tr>
                `);
            }
        });
    }

    function displaySummary(summary) {
        $('#totalStaked').text('฿' + formatNumber(summary.total_staked_thb));
        $('#totalStakedTokens').text(summary.total_staked_count + ' Tokens');

        $('#totalRewards').text('฿' + formatNumber(summary.total_rewards_thb));
        $('#totalRewardsTokens').text(formatNumber(summary.total_rewards_tokens) + ' Tokens');

        $('#activeStakes').text(summary.active_stakes);
        $('#activePools').text('ใน ' + summary.active_pools + ' Pools');

        $('#avgAPY').text(summary.avg_apy.toFixed(2) + '%');
        $('#avgDuration').text('AVG: ' + Math.round(summary.avg_duration_days) + ' วัน');
    }

    function filterAndDisplay() {
        let filtered = allStakes;

        // Search
        const searchTerm = $('#searchToken').val().toLowerCase();
        if (searchTerm) {
            filtered = filtered.filter(s =>
                s.pool.token.name.toLowerCase().includes(searchTerm) ||
                s.pool.token.symbol.toLowerCase().includes(searchTerm)
            );
        }

        // Type filter
        const type = $('#typeFilter').val();
        if (type) {
            filtered = filtered.filter(s => s.type === type);
        }

        // Status filter
        const status = $('#statusFilter').val();
        if (status) {
            filtered = filtered.filter(s => s.status === status);
        }

        // Time filter
        const timeFilter = $('#timeFilter').val();
        if (timeFilter !== 'all') {
            const now = new Date();
            filtered = filtered.filter(s => {
                const stakeDate = new Date(s.created_at);
                switch(timeFilter) {
                    case 'today': return stakeDate.toDateString() === now.toDateString();
                    case 'week': return (now - stakeDate) <= 7 * 24 * 60 * 60 * 1000;
                    case 'month': return (now - stakeDate) <= 30 * 24 * 60 * 60 * 1000;
                    case 'year': return stakeDate.getFullYear() === now.getFullYear();
                    default: return true;
                }
            });
        }

        // Sort
        const sortBy = $('#sortBy').val();
        filtered.sort((a, b) => {
            switch(sortBy) {
                case 'date_desc': return new Date(b.created_at) - new Date(a.created_at);
                case 'date_asc': return new Date(a.created_at) - new Date(b.created_at);
                case 'amount_desc': return b.amount - a.amount;
                case 'amount_asc': return a.amount - b.amount;
                default: return 0;
            }
        });

        displayStakes(filtered);
    }

    function displayStakes(stakes) {
        if (stakes.length === 0) {
            $('#historyTable').html(`
                <tr>
                    <td colspan="9" class="text-center py-4">
                        <p class="text-muted mb-0">ไม่พบข้อมูล</p>
                    </td>
                </tr>
            `);
            $('#paginationContainer').hide();
            $('#emptyState').show();
            return;
        }

        $('#emptyState').hide();
        $('#paginationContainer').show();

        // Pagination
        const start = (currentPage - 1) * perPage;
        const end = start + perPage;
        const paginatedStakes = stakes.slice(start, end);

        let html = '';
        paginatedStakes.forEach(stake => {
            const typeBadge = getTypeBadge(stake.type);
            const statusBadge = getStatusBadge(stake.status);

            html += `
                <tr>
                    <td>
                        <div>${new Date(stake.created_at).toLocaleDateString('th-TH')}</div>
                        <small class="text-muted">${new Date(stake.created_at).toLocaleTimeString('th-TH', {hour: '2-digit', minute: '2-digit'})}</small>
                    </td>
                    <td>${typeBadge}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <img src="${stake.pool.token.logo_url}" width="24" height="24" class="rounded-circle me-2" onerror="this.src='/images/default-token.png'">
                            <small>${stake.pool.token.symbol}</small>
                        </div>
                    </td>
                    <td class="text-end">
                        <div class="fw-bold">${formatNumber(stake.amount)}</div>
                        <small class="text-muted">฿${formatNumber(stake.amount_thb)}</small>
                    </td>
                    <td>
                        <small>${stake.lock_period_days === 0 ? 'Flexible' : stake.lock_period_days + ' วัน'}</small>
                    </td>
                    <td class="text-end">
                        <span class="badge bg-success">${stake.apy.toFixed(2)}%</span>
                    </td>
                    <td class="text-end">
                        <div class="text-success fw-bold">${formatNumber(stake.rewards_earned)}</div>
                        <small class="text-muted">${stake.pool.token.symbol}</small>
                    </td>
                    <td class="text-center">
                        ${statusBadge}
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary view-details-btn" data-stake-id="${stake.id}">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        $('#historyTable').html(html);

        // Pagination info
        $('#paginationInfo').text(`แสดง ${start + 1}-${Math.min(end, stakes.length)} จาก ${stakes.length} รายการ`);

        // Generate pagination
        generatePagination(stakes.length);

        // Bind view button
        $('.view-details-btn').click(function() {
            const stakeId = $(this).data('stake-id');
            showDetails(stakeId);
        });
    }

    function getTypeBadge(type) {
        const badges = {
            'stake': '<span class="badge bg-primary">Stake</span>',
            'unstake': '<span class="badge bg-danger">Unstake</span>',
            'claim': '<span class="badge bg-success">Claim</span>'
        };
        return badges[type] || '<span class="badge bg-secondary">Unknown</span>';
    }

    function getStatusBadge(status) {
        const badges = {
            'active': '<span class="badge bg-success">Active</span>',
            'completed': '<span class="badge bg-info">Completed</span>',
            'withdrawn': '<span class="badge bg-secondary">Withdrawn</span>'
        };
        return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
    }

    function generatePagination(totalItems) {
        const totalPages = Math.ceil(totalItems / perPage);
        let html = '<nav><ul class="pagination pagination-sm mb-0">';

        // Previous
        html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${currentPage - 1}">ก่อนหน้า</a>
        </li>`;

        // Pages
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>`;
            } else if (i === currentPage - 3 || i === currentPage + 3) {
                html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        // Next
        html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${currentPage + 1}">ถัดไป</a>
        </li>`;

        html += '</ul></nav>';
        $('#paginationButtons').html(html);

        // Bind clicks
        $('.page-link').click(function(e) {
            e.preventDefault();
            const page = parseInt($(this).data('page'));
            if (page > 0 && page <= totalPages) {
                currentPage = page;
                filterAndDisplay();
            }
        });
    }

    function showDetails(stakeId) {
        $.ajax({
            url: `/api/v1/tpix/staking/stakes/${stakeId}`,
            success: function(response) {
                const stake = response.data;
                let html = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">ข้อมูล Stake</h6>
                            <table class="table table-sm">
                                <tr><td>Stake ID:</td><td class="fw-bold">#${stake.id}</td></tr>
                                <tr><td>Pool:</td><td>${stake.pool.token.name} (${stake.pool.token.symbol})</td></tr>
                                <tr><td>จำนวน:</td><td class="fw-bold">${formatNumber(stake.amount)} ${stake.pool.token.symbol}</td></tr>
                                <tr><td>Lock Period:</td><td>${stake.lock_period_days === 0 ? 'Flexible' : stake.lock_period_days + ' วัน'}</td></tr>
                                <tr><td>APY:</td><td class="text-success fw-bold">${stake.apy.toFixed(2)}%</td></tr>
                                <tr><td>วันที่ Stake:</td><td>${new Date(stake.created_at).toLocaleDateString('th-TH')}</td></tr>
                                <tr><td>วันที่ปลดล็อก:</td><td>${stake.unlock_date ? new Date(stake.unlock_date).toLocaleDateString('th-TH') : '-'}</td></tr>
                                <tr><td>สถานะ:</td><td>${getStatusBadge(stake.status)}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">รางวัล</h6>
                            <table class="table table-sm">
                                <tr><td>รางวัลที่ได้รับ:</td><td class="text-success fw-bold">${formatNumber(stake.rewards_earned)} ${stake.pool.token.symbol}</td></tr>
                                <tr><td>รางวัลรอเบิก:</td><td>${formatNumber(stake.pending_rewards)} ${stake.pool.token.symbol}</td></tr>
                                <tr><td>มูลค่ารวม:</td><td class="fw-bold">฿${formatNumber(stake.total_value_thb)}</td></tr>
                            </table>

                            ${stake.transaction_hash ? `
                                <h6 class="fw-bold mb-2 mt-3">Blockchain</h6>
                                <a href="${stake.blockchain_explorer_url}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-external-link-alt me-1"></i>
                                    View on Explorer
                                </a>
                            ` : ''}
                        </div>
                    </div>
                `;
                $('#detailsContent').html(html);
                $('#detailsModal').modal('show');
            }
        });
    }

    // Filters
    $('#searchToken, #typeFilter, #statusFilter, #timeFilter, #sortBy').on('change input', filterAndDisplay);

    // Export
    $('#exportBtn').click(function() {
        const csv = generateCSV(allStakes);
        downloadCSV(csv, 'staking-history.csv');
    });

    function generateCSV(stakes) {
        let csv = 'Date,Type,Pool,Amount,Lock Period,APY,Rewards,Status\n';
        stakes.forEach(s => {
            csv += `${new Date(s.created_at).toISOString()},${s.type},${s.pool.token.symbol},${s.amount},${s.lock_period_days},${s.apy},${s.rewards_earned},${s.status}\n`;
        });
        return csv;
    }

    function downloadCSV(content, filename) {
        const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        link.click();
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
