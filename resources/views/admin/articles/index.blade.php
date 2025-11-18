@extends('layouts.admin-v3')

@section('title', 'จัดการบทความ')

@push('styles')
<style>
:root {
    --primary: #4f46e5;
    --primary-dark: #4338ca;
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

.am-header {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    border-radius: 16px;
    padding: 32px;
    color: white;
    margin-bottom: 24px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.am-header-title {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 8px;
}

.am-header-subtitle {
    opacity: 0.9;
    font-size: 15px;
}

.am-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.am-stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    border: 1px solid var(--gray-200);
}

.am-stat-label {
    font-size: 13px;
    color: var(--gray-600);
    margin-bottom: 8px;
}

.am-stat-value {
    font-size: 32px;
    font-weight: 700;
    color: var(--gray-900);
}

.am-stat-icon {
    font-size: 24px;
    margin-bottom: 12px;
}

.am-filters {
    background: white;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
    border: 1px solid var(--gray-200);
}

.am-filter-group {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 16px;
}

.am-filter-label {
    font-size: 13px;
    font-weight: 500;
    color: var(--gray-700);
    margin-bottom: 6px;
    display: block;
}

.am-filter-input,
.am-filter-select {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s;
}

.am-filter-input:focus,
.am-filter-select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.am-btn {
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.am-btn-primary {
    background: var(--primary);
    color: white;
}

.am-btn-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
}

.am-btn-success {
    background: var(--success);
    color: white;
}

.am-btn-success:hover {
    background: #059669;
}

.am-btn-outline {
    background: white;
    border: 1px solid var(--gray-200);
    color: var(--gray-700);
}

.am-btn-outline:hover {
    background: var(--gray-50);
}

.am-table-container {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid var(--gray-200);
}

.am-table {
    width: 100%;
    border-collapse: collapse;
}

.am-table thead {
    background: var(--gray-50);
}

.am-table th {
    padding: 16px;
    text-align: left;
    font-size: 13px;
    font-weight: 600;
    color: var(--gray-700);
    border-bottom: 1px solid var(--gray-200);
}

.am-table td {
    padding: 16px;
    border-bottom: 1px solid var(--gray-200);
    font-size: 14px;
}

.am-table tbody tr {
    transition: background 0.2s;
}

.am-table tbody tr:hover {
    background: var(--gray-50);
}

.am-article-title {
    font-weight: 600;
    color: var(--gray-900);
    margin-bottom: 4px;
}

.am-article-excerpt {
    color: var(--gray-600);
    font-size: 13px;
}

.am-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
}

.am-badge-success {
    background: #d1fae5;
    color: #065f46;
}

.am-badge-warning {
    background: #fef3c7;
    color: #92400e;
}

.am-badge-gray {
    background: var(--gray-100);
    color: var(--gray-700);
}

.am-badge-blue {
    background: #dbeafe;
    color: #1e40af;
}

.am-badge-purple {
    background: #e9d5ff;
    color: #6b21a8;
}

.am-badge-red {
    background: #fee2e2;
    color: #991b1b;
}

.am-actions {
    display: flex;
    gap: 8px;
}

.am-action-btn {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 13px;
    text-decoration: none;
    transition: all 0.2s;
}

.am-action-edit {
    background: var(--gray-100);
    color: var(--gray-700);
}

.am-action-edit:hover {
    background: var(--gray-200);
}

.am-action-delete {
    background: #fee2e2;
    color: #991b1b;
}

.am-action-delete:hover {
    background: #fecaca;
}

.am-pagination {
    display: flex;
    justify-content: center;
    padding: 24px;
}

.am-empty {
    text-align: center;
    padding: 60px 20px;
}

.am-empty-icon {
    font-size: 64px;
    opacity: 0.3;
    margin-bottom: 16px;
}

.am-empty-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--gray-900);
    margin-bottom: 8px;
}

.am-empty-text {
    color: var(--gray-600);
    margin-bottom: 24px;
}

.am-thumbnail {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    object-fit: cover;
}

.am-views {
    display: flex;
    align-items: center;
    gap: 6px;
    color: var(--gray-600);
    font-size: 13px;
}
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="am-header">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h1 class="am-header-title">จัดการบทความ</h1>
                <p class="am-header-subtitle mb-0">สร้างและจัดการเนื้อหาการเรียนรู้</p>
            </div>
            <a href="{{ route('admin.articles.create') }}" class="am-btn am-btn-success">
                <i class="fas fa-plus"></i>
                สร้างบทความใหม่
            </a>
        </div>
    </div>

    <!-- Stats -->
    <div class="am-stats">
        <div class="am-stat-card">
            <div class="am-stat-icon">📚</div>
            <div class="am-stat-label">บทความทั้งหมด</div>
            <div class="am-stat-value">{{ $articles->total() }}</div>
        </div>
        <div class="am-stat-card">
            <div class="am-stat-icon">✅</div>
            <div class="am-stat-label">เผยแพร่แล้ว</div>
            <div class="am-stat-value">{{ $articles->where('is_published', true)->count() }}</div>
        </div>
        <div class="am-stat-card">
            <div class="am-stat-icon">📝</div>
            <div class="am-stat-label">ฉบับร่าง</div>
            <div class="am-stat-value">{{ $articles->where('is_published', false)->count() }}</div>
        </div>
        <div class="am-stat-card">
            <div class="am-stat-icon">👁️</div>
            <div class="am-stat-label">ยอดเข้าชมรวม</div>
            <div class="am-stat-value">{{ number_format($articles->sum('views')) }}</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="am-filters">
        <form method="GET" action="{{ route('admin.articles.index') }}">
            <div class="am-filter-group">
                <div>
                    <label class="am-filter-label">ค้นหา</label>
                    <input type="text" name="search" class="am-filter-input"
                           placeholder="ค้นหาบทความ..."
                           value="{{ request('search') }}">
                </div>
                <div>
                    <label class="am-filter-label">หมวดหมู่</label>
                    <select name="category" class="am-filter-select">
                        <option value="">ทั้งหมด</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="am-filter-label">สถานะ</label>
                    <select name="status" class="am-filter-select">
                        <option value="">ทั้งหมด</option>
                        <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>เผยแพร่แล้ว</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>ฉบับร่าง</option>
                    </select>
                </div>
                <div>
                    <label class="am-filter-label">ระดับความยาก</label>
                    <select name="difficulty" class="am-filter-select">
                        <option value="">ทั้งหมด</option>
                        <option value="beginner" {{ request('difficulty') == 'beginner' ? 'selected' : '' }}>เริ่มต้น</option>
                        <option value="intermediate" {{ request('difficulty') == 'intermediate' ? 'selected' : '' }}>ปานกลาง</option>
                        <option value="advanced" {{ request('difficulty') == 'advanced' ? 'selected' : '' }}>ขั้นสูง</option>
                    </select>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="am-btn am-btn-primary">
                    <i class="fas fa-search"></i>
                    ค้นหา
                </button>
                <a href="{{ route('admin.articles.index') }}" class="am-btn am-btn-outline">
                    <i class="fas fa-times"></i>
                    ล้างตัวกรอง
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="am-table-container">
        @if($articles->count() > 0)
            <table class="am-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">ภาพ</th>
                        <th>บทความ</th>
                        <th style="width: 150px;">หมวดหมู่</th>
                        <th style="width: 120px;">ระดับ</th>
                        <th style="width: 100px;">สถานะ</th>
                        <th style="width: 100px;">ยอดเข้าชม</th>
                        <th style="width: 120px;">สร้างโดย</th>
                        <th style="width: 180px;">การกระทำ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($articles as $article)
                    <tr>
                        <td>
                            @if($article->thumbnail)
                                <img src="{{ asset('storage/' . $article->thumbnail) }}"
                                     alt="{{ $article->title }}"
                                     class="am-thumbnail">
                            @else
                                <div class="am-thumbnail" style="background: var(--gray-100); display: flex; align-items: center; justify-content: center; color: var(--gray-600);">
                                    <i class="fas fa-image"></i>
                                </div>
                            @endif
                        </td>
                        <td>
                            <div class="am-article-title">{{ $article->title }}</div>
                            <div class="am-article-excerpt">
                                {{ Str::limit($article->excerpt ?? strip_tags($article->content), 80) }}
                            </div>
                        </td>
                        <td>
                            <span class="am-badge am-badge-gray">
                                {{ $article->category->icon ?? '📚' }} {{ $article->category->name }}
                            </span>
                        </td>
                        <td>
                            @if($article->difficulty == 'beginner')
                                <span class="am-badge am-badge-success">เริ่มต้น</span>
                            @elseif($article->difficulty == 'intermediate')
                                <span class="am-badge am-badge-warning">ปานกลาง</span>
                            @else
                                <span class="am-badge am-badge-red">ขั้นสูง</span>
                            @endif
                        </td>
                        <td>
                            @if($article->is_published)
                                <span class="am-badge am-badge-success">
                                    <i class="fas fa-check-circle"></i> เผยแพร่
                                </span>
                            @else
                                <span class="am-badge am-badge-gray">
                                    <i class="fas fa-circle"></i> ร่าง
                                </span>
                            @endif
                        </td>
                        <td>
                            <div class="am-views">
                                <i class="fas fa-eye"></i>
                                {{ number_format($article->views) }}
                            </div>
                        </td>
                        <td>
                            <div style="font-size: 13px; color: var(--gray-600);">
                                {{ $article->creator->name ?? 'N/A' }}
                            </div>
                        </td>
                        <td>
                            <div class="am-actions">
                                <a href="{{ route('admin.articles.edit', $article) }}"
                                   class="am-action-btn am-action-edit">
                                    <i class="fas fa-edit"></i> แก้ไข
                                </a>
                                <form method="POST"
                                      action="{{ route('admin.articles.destroy', $article) }}"
                                      onsubmit="return confirm('ต้องการลบบทความนี้?');"
                                      style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="am-action-btn am-action-delete">
                                        <i class="fas fa-trash"></i> ลบ
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="am-pagination">
                {{ $articles->links() }}
            </div>
        @else
            <!-- Empty State -->
            <div class="am-empty">
                <div class="am-empty-icon">📝</div>
                <div class="am-empty-title">ยังไม่มีบทความ</div>
                <div class="am-empty-text">เริ่มสร้างบทความแรกของคุณ</div>
                <a href="{{ route('admin.articles.create') }}" class="am-btn am-btn-success">
                    <i class="fas fa-plus"></i>
                    สร้างบทความใหม่
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Auto-submit on filter change (optional)
    $('.am-filter-select').on('change', function() {
        // $(this).closest('form').submit();
    });
});
</script>
@endpush
