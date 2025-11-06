@extends('layouts.admin')

@section('title', 'จัดการใบประกาศนียบัตร - Admin')

@push('styles')
<style>
:root {
    --primary: #6366f1;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --gray-50: #f9fafb;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-600: #4b5563;
    --gray-700: #374151;
    --gray-900: #111827;
}

.certificates-container {
    max-width: 1600px;
    margin: 0 auto;
    padding: 32px 20px;
}

.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    padding: 32px;
    color: white;
    margin-bottom: 32px;
}

.header-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.page-title {
    font-size: 32px;
    font-weight: 800;
    margin: 0;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}

.stat-card {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    padding: 20px;
    text-align: center;
}

.stat-value {
    font-size: 36px;
    font-weight: 700;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 14px;
    opacity: 0.95;
}

/* Filters */
.filters-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 16px;
}

.form-group {
    margin-bottom: 0;
}

.form-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--gray-700);
    margin-bottom: 6px;
}

.form-input, .form-select {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    font-size: 14px;
}

.form-input:focus, .form-select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

/* Table */
.table-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.table-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.table-title {
    font-size: 18px;
    font-weight: 700;
    color: var(--gray-900);
}

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

thead {
    background: var(--gray-50);
}

th {
    padding: 12px 16px;
    text-align: left;
    font-size: 13px;
    font-weight: 600;
    color: var(--gray-700);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

td {
    padding: 16px;
    border-top: 1px solid var(--gray-100);
    font-size: 14px;
}

tr:hover {
    background: var(--gray-50);
}

/* Badges */
.badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}

.badge-active {
    background: #d1fae5;
    color: #065f46;
}

.badge-revoked {
    background: #fee2e2;
    color: #991b1b;
}

/* Buttons */
.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: #4f46e5;
    transform: translateY(-1px);
}

.btn-success {
    background: var(--success);
    color: white;
}

.btn-danger {
    background: var(--danger);
    color: white;
}

.btn-secondary {
    background: var(--gray-200);
    color: var(--gray-700);
}

.btn-sm {
    padding: 6px 12px;
    font-size: 13px;
}

/* Actions dropdown */
.actions-dropdown {
    position: relative;
}

.actions-btn {
    padding: 6px 12px;
    background: var(--gray-100);
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
}

.actions-menu {
    display: none;
    position: absolute;
    right: 0;
    top: 100%;
    background: white;
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    min-width: 160px;
    z-index: 10;
    margin-top: 4px;
}

.actions-dropdown:hover .actions-menu {
    display: block;
}

.actions-menu a, .actions-menu button {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border: none;
    background: none;
    width: 100%;
    text-align: left;
    font-size: 14px;
    color: var(--gray-700);
    cursor: pointer;
    text-decoration: none;
}

.actions-menu a:hover, .actions-menu button:hover {
    background: var(--gray-50);
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    padding: 20px;
}

.pagination a, .pagination span {
    padding: 8px 12px;
    border: 1px solid var(--gray-200);
    border-radius: 6px;
    font-size: 14px;
    text-decoration: none;
    color: var(--gray-700);
}

.pagination a:hover {
    background: var(--gray-50);
}

.pagination .active {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 80px 20px;
}

.empty-icon {
    font-size: 64px;
    margin-bottom: 16px;
}

.empty-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 8px;
}

.empty-text {
    color: var(--gray-600);
    margin-bottom: 24px;
}

@media (max-width: 768px) {
    .certificates-container {
        padding: 20px 16px;
    }

    .page-header {
        padding: 24px 20px;
    }

    .page-title {
        font-size: 24px;
    }

    .header-top {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }

    .filters-grid {
        grid-template-columns: 1fr;
    }

    table {
        font-size: 13px;
    }

    th, td {
        padding: 10px;
    }
}
</style>
@endpush

@section('content')
<div class="certificates-container">
    <!-- Header -->
    <div class="page-header">
        <div class="header-top">
            <h1 class="page-title">📜 จัดการใบประกาศนียบัตร</h1>
            <div style="display: flex; gap: 12px;">
                <a href="{{ route('admin.academy.certificates.create') }}" class="btn btn-success">
                    ➕ สร้างใบประกาศใหม่
                </a>
                <a href="{{ route('admin.academy.certificates.export') }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}"
                   class="btn btn-secondary">
                    📥 Export CSV
                </a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value">{{ number_format($stats['total']) }}</div>
                <div class="stat-label">ทั้งหมด</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ number_format($stats['active']) }}</div>
                <div class="stat-label">ใช้งานได้</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ number_format($stats['revoked']) }}</div>
                <div class="stat-label">ถูกเพิกถอน</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ number_format($stats['this_month']) }}</div>
                <div class="stat-label">เดือนนี้</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-card">
        <form method="GET" action="{{ route('admin.academy.certificates.index') }}">
            <div class="filters-grid">
                <div class="form-group">
                    <label class="form-label">ค้นหา</label>
                    <input type="text" name="search" class="form-input"
                           placeholder="เลขที่, ชื่อ, อีเมล..."
                           value="{{ request('search') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">คอร์ส</label>
                    <select name="course_id" class="form-select">
                        <option value="">ทั้งหมด</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                                {{ $course->title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">สถานะ</label>
                    <select name="status" class="form-select">
                        <option value="">ทั้งหมด</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>ใช้งานได้</option>
                        <option value="revoked" {{ request('status') === 'revoked' ? 'selected' : '' }}>ถูกเพิกถอน</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">วันที่ออก (จาก)</label>
                    <input type="date" name="date_from" class="form-input" value="{{ request('date_from') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">วันที่ออก (ถึง)</label>
                    <input type="date" name="date_to" class="form-input" value="{{ request('date_to') }}">
                </div>
            </div>

            <div style="display: flex; gap: 12px;">
                <button type="submit" class="btn btn-primary">
                    🔍 ค้นหา
                </button>
                <a href="{{ route('admin.academy.certificates.index') }}" class="btn btn-secondary">
                    ล้างตัวกรอง
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="table-card">
        <div class="table-header">
            <h2 class="table-title">ใบประกาศนียบัตรทั้งหมด ({{ $certificates->total() }})</h2>
        </div>

        @if($certificates->isEmpty())
            <div class="empty-state">
                <div class="empty-icon">📜</div>
                <h3 class="empty-title">ไม่พบใบประกาศนียบัตร</h3>
                <p class="empty-text">ยังไม่มีใบประกาศนียบัตรในระบบ หรือลองปรับตัวกรองใหม่</p>
                <a href="{{ route('admin.academy.certificates.create') }}" class="btn btn-primary">
                    ➕ สร้างใบประกาศใหม่
                </a>
            </div>
        @else
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>เลขที่</th>
                            <th>นักเรียน</th>
                            <th>คอร์ส</th>
                            <th>คะแนน</th>
                            <th>วันที่ออก</th>
                            <th>สถานะ</th>
                            <th>การกระทำ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($certificates as $cert)
                            <tr>
                                <td>
                                    <strong style="font-family: monospace; color: var(--primary);">
                                        {{ $cert->certificate_number }}
                                    </strong>
                                </td>
                                <td>
                                    <div style="font-weight: 600;">{{ $cert->student_name }}</div>
                                    <div style="font-size: 13px; color: var(--gray-600);">
                                        {{ $cert->student_email }}
                                    </div>
                                </td>
                                <td>
                                    <div style="max-width: 250px;">{{ $cert->course_title }}</div>
                                    <div style="font-size: 13px; color: var(--gray-600);">
                                        ครู: {{ $cert->instructor_name }}
                                    </div>
                                </td>
                                <td>
                                    @if($cert->quiz_score)
                                        <span style="font-weight: 700; color: var(--success);">
                                            {{ number_format($cert->quiz_score, 0) }}%
                                        </span>
                                    @else
                                        <span style="color: var(--gray-600);">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $cert->issued_at->format('d/m/Y') }}</div>
                                    <div style="font-size: 12px; color: var(--gray-600);">
                                        {{ $cert->issued_at->format('H:i') }}
                                    </div>
                                </td>
                                <td>
                                    @if($cert->is_revoked)
                                        <span class="badge badge-revoked">
                                            ❌ เพิกถอนแล้ว
                                        </span>
                                    @else
                                        <span class="badge badge-active">
                                            ✅ ใช้งานได้
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="actions-dropdown">
                                        <button class="actions-btn">⋮</button>
                                        <div class="actions-menu">
                                            <a href="{{ route('admin.academy.certificates.show', $cert->id) }}">
                                                👁️ ดูรายละเอียด
                                            </a>
                                            <a href="{{ route('admin.academy.certificates.edit', $cert->id) }}">
                                                ✏️ แก้ไข
                                            </a>
                                            <a href="{{ route('admin.certificates.download', $cert->id) }}" target="_blank">
                                                📥 ดาวน์โหลด
                                            </a>
                                            @if(!$cert->is_revoked)
                                                <button onclick="revokeCertificate({{ $cert->id }})">
                                                    🚫 เพิกถอน
                                                </button>
                                            @else
                                                <button onclick="restoreCertificate({{ $cert->id }})">
                                                    ♻️ กู้คืน
                                                </button>
                                            @endif
                                            <button onclick="deleteCertificate({{ $cert->id }})" style="color: var(--danger);">
                                                🗑️ ลบ
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pagination">
                {{ $certificates->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function revokeCertificate(id) {
    const reason = prompt('กรุณาระบุเหตุผลในการเพิกถอน (ถ้ามี):');
    if (reason === null) return;

    fetch(`{{ url('admin/academy/certificates') }}/${id}/revoke`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ revoked_reason: reason })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ เพิกถอนใบประกาศเรียบร้อยแล้ว');
            location.reload();
        } else {
            alert('❌ เกิดข้อผิดพลาด');
        }
    });
}

function restoreCertificate(id) {
    if (!confirm('ต้องการกู้คืนใบประกาศนี้?')) return;

    fetch(`{{ url('admin/academy/certificates') }}/${id}/restore`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ กู้คืนใบประกาศเรียบร้อยแล้ว');
            location.reload();
        } else {
            alert('❌ เกิดข้อผิดพลาด');
        }
    });
}

function deleteCertificate(id) {
    if (!confirm('ต้องการลบใบประกาศนี้? การกระทำนี้ไม่สามารถย้อนกลับได้!')) return;

    fetch(`{{ url('admin/academy/certificates') }}/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ ลบใบประกาศเรียบร้อยแล้ว');
            location.reload();
        } else {
            alert('❌ เกิดข้อผิดพลาด');
        }
    });
}
</script>
@endpush
@endsection
