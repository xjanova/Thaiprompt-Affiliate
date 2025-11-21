@extends('layouts.user-arrow-x')

@section('title', 'ประวัติการแลกเปลี่ยน - TPIX DEX')

@section('content')
<div class="container py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">
                <i class="fas fa-history text-primary me-2"></i>
                ประวัติการแลกเปลี่ยน
            </h2>
            <p class="text-muted mb-0">ดูรายการ Swap ทั้งหมดของคุณ</p>
        </div>
        <a href="{{ route('user.dex.swap') }}" class="btn btn-primary">
            <i class="fas fa-exchange-alt me-2"></i>แลกเปลี่ยน Token
        </a>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <small class="text-muted">จำนวนครั้งทั้งหมด</small>
                    <h4 class="mb-0" id="totalSwaps">0</h4>
                    <small class="text-muted" id="swapsThisMonth">เดือนนี้: 0</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <small class="text-muted">ปริมาณทั้งหมด (THB)</small>
                    <h4 class="mb-0" id="totalVolume">฿0.00</h4>
                    <small class="text-muted" id="volumeThisMonth">เดือนนี้: ฿0.00</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <small class="text-muted">ค่าธรรมเนียมรวม</small>
                    <h4 class="mb-0" id="totalFees">฿0.00</h4>
                    <small class="text-muted" id="feesThisMonth">เดือนนี้: ฿0.00</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" id="searchSwap" placeholder="ค้นหา Token...">
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="statusFilter">
                        <option value="">ทุกสถานะ</option>
                        <option value="completed">สำเร็จ</option>
                        <option value="pending">กำลังดำเนินการ</option>
                        <option value="failed">ล้มเหลว</option>
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
                <div class="col-md-3">
                    <select class="form-select" id="sortBy">
                        <option value="date_desc">วันที่: ใหม่-เก่า</option>
                        <option value="date_asc">วันที่: เก่า-ใหม่</option>
                        <option value="amount_desc">มูลค่า: สูง-ต่ำ</option>
                        <option value="amount_asc">มูลค่า: ต่ำ-สูง</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-secondary w-100" id="exportBtn">
                        <i class="fas fa-download me-1"></i>ส่งออก
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Swap History Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>วันที่ / เวลา</th>
                            <th>จาก</th>
                            <th></th>
                            <th>ไปยัง</th>
                            <th class="text-end">มูลค่า (THB)</th>
                            <th class="text-end">ค่าธรรมเนียม</th>
                            <th class="text-center">สถานะ</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="swapHistoryTable">
                        <!-- Loading state -->
                        <tr>
                            <td colspan="8" class="text-center py-5">
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
        <i class="fas fa-exchange-alt text-muted" style="font-size: 4rem;"></i>
        <h5 class="mt-3">ยังไม่มีประวัติการแลกเปลี่ยน</h5>
        <p class="text-muted">เริ่มต้น Swap Token เพื่อดูประวัติที่นี่</p>
        <a href="{{ route('user.dex.swap') }}" class="btn btn-primary">
            <i class="fas fa-exchange-alt me-2"></i>แลกเปลี่ยน Token
        </a>
    </div>
</div>

<!-- Swap Details Modal -->
<div class="modal fade" id="swapDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2"></i>
                    รายละเอียด Swap
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="swapDetailsContent">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let allSwaps = [];
    let currentPage = 1;
    let perPage = 20;

    // Load swap history
    loadSwapHistory();

    function loadSwapHistory() {
        $.ajax({
            url: '/api/v1/tpix/dex/my-swaps',
            method: 'GET',
            success: function(response) {
                allSwaps = response.data;
                displaySummary(response.summary);
                filterAndDisplay();
            },
            error: function() {
                $('#swapHistoryTable').html(`
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <div class="alert alert-danger mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                ไม่สามารถโหลดข้อมูลได้ กรุณาลองใหม่อีกครั้ง
                            </div>
                        </td>
                    </tr>
                `);
            }
        });
    }

    function displaySummary(summary) {
        $('#totalSwaps').text(summary.total_swaps.toLocaleString());
        $('#swapsThisMonth').text('เดือนนี้: ' + summary.swaps_this_month.toLocaleString());

        $('#totalVolume').text('฿' + formatNumber(summary.total_volume));
        $('#volumeThisMonth').text('เดือนนี้: ฿' + formatNumber(summary.volume_this_month));

        $('#totalFees').text('฿' + formatNumber(summary.total_fees));
        $('#feesThisMonth').text('เดือนนี้: ฿' + formatNumber(summary.fees_this_month));
    }

    function filterAndDisplay() {
        let filtered = allSwaps;

        // Search filter
        const searchTerm = $('#searchSwap').val().toLowerCase();
        if (searchTerm) {
            filtered = filtered.filter(s =>
                s.token_in.symbol.toLowerCase().includes(searchTerm) ||
                s.token_out.symbol.toLowerCase().includes(searchTerm)
            );
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
                const swapDate = new Date(s.created_at);
                switch(timeFilter) {
                    case 'today':
                        return swapDate.toDateString() === now.toDateString();
                    case 'week':
                        return (now - swapDate) <= 7 * 24 * 60 * 60 * 1000;
                    case 'month':
                        return (now - swapDate) <= 30 * 24 * 60 * 60 * 1000;
                    case 'year':
                        return swapDate.getFullYear() === now.getFullYear();
                    default:
                        return true;
                }
            });
        }

        // Sort
        const sortBy = $('#sortBy').val();
        filtered.sort((a, b) => {
            switch(sortBy) {
                case 'date_desc': return new Date(b.created_at) - new Date(a.created_at);
                case 'date_asc': return new Date(a.created_at) - new Date(b.created_at);
                case 'amount_desc': return b.amount_in_thb - a.amount_in_thb;
                case 'amount_asc': return a.amount_in_thb - b.amount_in_thb;
                default: return 0;
            }
        });

        displaySwaps(filtered);
    }

    function displaySwaps(swaps) {
        if (swaps.length === 0) {
            $('#swapHistoryTable').html(`
                <tr>
                    <td colspan="8" class="text-center py-4">
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
        const paginatedSwaps = swaps.slice(start, end);

        let html = '';
        paginatedSwaps.forEach(swap => {
            const statusBadge = getStatusBadge(swap.status);
            const priceImpactClass = Math.abs(swap.price_impact) > 5 ? 'text-danger' : 'text-warning';

            html += `
                <tr>
                    <td>
                        <div>${new Date(swap.created_at).toLocaleDateString('th-TH')}</div>
                        <small class="text-muted">${new Date(swap.created_at).toLocaleTimeString('th-TH', {hour: '2-digit', minute: '2-digit'})}</small>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <img src="${swap.token_in.logo_url}" width="24" height="24" class="rounded-circle me-2" onerror="this.src='/images/default-token.png'">
                            <div>
                                <div class="fw-bold">${formatNumber(swap.amount_in)}</div>
                                <small class="text-muted">${swap.token_in.symbol}</small>
                            </div>
                        </div>
                    </td>
                    <td class="text-center">
                        <i class="fas fa-arrow-right text-primary"></i>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <img src="${swap.token_out.logo_url}" width="24" height="24" class="rounded-circle me-2" onerror="this.src='/images/default-token.png'">
                            <div>
                                <div class="fw-bold">${formatNumber(swap.amount_out)}</div>
                                <small class="text-muted">${swap.token_out.symbol}</small>
                            </div>
                        </div>
                    </td>
                    <td class="text-end">
                        <div>฿${formatNumber(swap.amount_in_thb)}</div>
                        ${swap.price_impact ? `<small class="${priceImpactClass}">Impact: ${swap.price_impact.toFixed(2)}%</small>` : ''}
                    </td>
                    <td class="text-end">
                        <div>฿${formatNumber(swap.fee_amount)}</div>
                        <small class="text-muted">${swap.fee_percentage}%</small>
                    </td>
                    <td class="text-center">
                        ${statusBadge}
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary view-swap-btn" data-swap-id="${swap.id}">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        $('#swapHistoryTable').html(html);

        // Update pagination info
        $('#paginationInfo').text(`แสดง ${start + 1}-${Math.min(end, swaps.length)} จาก ${swaps.length} รายการ`);

        // Generate pagination buttons
        generatePagination(swaps.length);

        // Bind view button
        $('.view-swap-btn').click(function() {
            const swapId = $(this).data('swap-id');
            showSwapDetails(swapId);
        });
    }

    function getStatusBadge(status) {
        const badges = {
            'completed': '<span class="badge bg-success">สำเร็จ</span>',
            'pending': '<span class="badge bg-warning">กำลังดำเนินการ</span>',
            'failed': '<span class="badge bg-danger">ล้มเหลว</span>'
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

        // Bind pagination clicks
        $('.page-link').click(function(e) {
            e.preventDefault();
            const page = parseInt($(this).data('page'));
            if (page > 0 && page <= totalPages) {
                currentPage = page;
                filterAndDisplay();
            }
        });
    }

    function showSwapDetails(swapId) {
        $.ajax({
            url: `/api/v1/tpix/dex/swaps/${swapId}`,
            success: function(response) {
                const swap = response.data;
                let html = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">ข้อมูล Swap</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td>Swap ID:</td>
                                    <td class="fw-bold">#${swap.id}</td>
                                </tr>
                                <tr>
                                    <td>วันที่:</td>
                                    <td>${new Date(swap.created_at).toLocaleString('th-TH')}</td>
                                </tr>
                                <tr>
                                    <td>Pool:</td>
                                    <td>${swap.pool.pair}</td>
                                </tr>
                                <tr>
                                    <td>Exchange Rate:</td>
                                    <td>${swap.exchange_rate}</td>
                                </tr>
                                <tr>
                                    <td>Price Impact:</td>
                                    <td class="${Math.abs(swap.price_impact) > 5 ? 'text-danger' : ''}">${swap.price_impact.toFixed(2)}%</td>
                                </tr>
                                <tr>
                                    <td>Slippage:</td>
                                    <td>${swap.slippage_tolerance}%</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Transaction</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td>TxHash:</td>
                                    <td>
                                        <a href="${swap.blockchain_tx_url}" target="_blank" class="text-decoration-none">
                                            ${swap.blockchain_tx_hash ? swap.blockchain_tx_hash.substring(0, 10) + '...' : '-'}
                                            <i class="fas fa-external-link-alt ms-1"></i>
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Gas Used:</td>
                                    <td>${swap.gas_used || '0'} (฿0.00)</td>
                                </tr>
                                <tr>
                                    <td>สถานะ:</td>
                                    <td>${getStatusBadge(swap.status)}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                `;
                $('#swapDetailsContent').html(html);
                $('#swapDetailsModal').modal('show');
            }
        });
    }

    // Filters
    $('#searchSwap, #statusFilter, #timeFilter, #sortBy').on('change input', filterAndDisplay);

    // Export
    $('#exportBtn').click(function() {
        const csvContent = generateCSV(allSwaps);
        downloadCSV(csvContent, 'swap-history.csv');
    });

    function generateCSV(swaps) {
        let csv = 'Date,From Token,Amount In,To Token,Amount Out,Value (THB),Fee,Status\n';
        swaps.forEach(s => {
            csv += `${new Date(s.created_at).toISOString()},${s.token_in.symbol},${s.amount_in},${s.token_out.symbol},${s.amount_out},${s.amount_in_thb},${s.fee_amount},${s.status}\n`;
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
