@extends('layouts.admin')

@section('title', $category->name . ' - Academy')

@push('styles')
<style>
:root {
    --primary-color: #6366f1;
    --primary-dark: #4f46e5;
    --primary-light: #818cf8;
    --secondary-color: #06b6d4;
    --success-color: #10b981;
    --warning-color: #f59e0b;
    --danger-color: #ef4444;
    --gray-50: #f9fafb;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-300: #d1d5db;
    --gray-600: #4b5563;
    --gray-700: #374151;
    --gray-800: #1f2937;
    --gray-900: #111827;
}

/* Premium Typography */
body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

.category-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px;
}

/* Category Hero */
.category-hero {
    background: linear-gradient(135deg, {{ $category->color ?? '#667eea' }} 0%, {{ $category->color ? $category->color . 'dd' : '#764ba2' }} 100%);
    border-radius: 24px;
    padding: 48px;
    color: white;
    margin-bottom: 40px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.category-hero::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 50%;
    height: 100%;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity: 0.1;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 24px;
    font-size: 14px;
    opacity: 0.9;
}

.breadcrumb a {
    color: white;
    text-decoration: none;
    transition: opacity 0.2s;
}

.breadcrumb a:hover {
    opacity: 0.8;
}

.breadcrumb-separator {
    opacity: 0.6;
}

.category-header {
    display: flex;
    align-items: center;
    gap: 24px;
    margin-bottom: 16px;
}

.category-icon-large {
    font-size: 64px;
    line-height: 1;
}

.category-title {
    font-size: 42px;
    font-weight: 800;
    margin: 0;
    line-height: 1.2;
    letter-spacing: -0.5px;
}

.category-description {
    font-size: 18px;
    opacity: 0.95;
    line-height: 1.6;
    margin: 0;
    max-width: 800px;
}

/* Articles Section */
.articles-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.section-title {
    font-size: 28px;
    font-weight: 700;
    color: var(--gray-900);
    margin: 0;
}

.articles-count {
    font-size: 14px;
    color: var(--gray-600);
    font-weight: 500;
}

.articles-grid {
    display: grid;
    gap: 24px;
}

/* Article Card */
.article-card {
    background: white;
    border-radius: 16px;
    border: 1px solid var(--gray-200);
    overflow: hidden;
    transition: all 0.3s ease;
    position: relative;
}

.article-card:hover {
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    transform: translateY(-2px);
    border-color: var(--primary-light);
}

.article-card.locked {
    opacity: 0.7;
    cursor: not-allowed;
}

.article-card.locked::before {
    content: '🔒';
    position: absolute;
    top: 16px;
    right: 16px;
    font-size: 24px;
    z-index: 2;
}

.article-content {
    padding: 24px;
}

.article-header {
    display: flex;
    gap: 20px;
    margin-bottom: 16px;
}

.article-thumbnail {
    width: 120px;
    height: 80px;
    border-radius: 8px;
    object-fit: cover;
    flex-shrink: 0;
    background: var(--gray-100);
}

.article-info {
    flex: 1;
    min-width: 0;
}

.article-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--gray-900);
    margin: 0 0 8px 0;
    line-height: 1.3;
}

.article-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    font-size: 14px;
    color: var(--gray-600);
    margin-bottom: 12px;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.article-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.tag {
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    background: var(--gray-100);
    color: var(--gray-700);
}

.tag.difficulty-beginner {
    background: #d1fae5;
    color: #065f46;
}

.tag.difficulty-intermediate {
    background: #fed7aa;
    color: #92400e;
}

.tag.difficulty-advanced {
    background: #fecaca;
    color: #991b1b;
}

/* Progress Section */
.article-progress {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid var(--gray-100);
}

.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.progress-label {
    font-size: 13px;
    font-weight: 600;
    color: var(--gray-700);
}

.progress-percentage {
    font-size: 13px;
    font-weight: 700;
    color: var(--primary-color);
}

.progress-bar {
    height: 6px;
    background: var(--gray-100);
    border-radius: 3px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
    border-radius: 3px;
    transition: width 0.3s ease;
}

/* Status Badges */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-badge.not-started {
    background: var(--gray-100);
    color: var(--gray-700);
}

.status-badge.in-progress {
    background: #dbeafe;
    color: #1e40af;
}

.status-badge.completed {
    background: #d1fae5;
    color: #065f46;
}

/* Action Button */
.article-action {
    display: flex;
    justify-content: flex-end;
    padding: 0 24px 24px;
}

.btn-start {
    padding: 12px 24px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
    color: white;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);
}

.btn-continue {
    background: linear-gradient(135deg, var(--secondary-color), var(--success-color));
    color: white;
}

.btn-continue:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 15px -3px rgba(6, 182, 212, 0.3);
}

.btn-locked {
    background: var(--gray-200);
    color: var(--gray-600);
    cursor: not-allowed;
    opacity: 0.6;
}

.btn-locked:hover {
    transform: none;
    box-shadow: none;
}

/* Prerequisites Warning */
.prerequisites-warning {
    background: #fef3c7;
    border: 1px solid #fcd34d;
    border-radius: 12px;
    padding: 16px;
    margin-top: 16px;
}

.prerequisites-title {
    font-size: 14px;
    font-weight: 600;
    color: #92400e;
    margin-bottom: 8px;
}

.prerequisites-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.prerequisites-list li {
    font-size: 13px;
    color: #92400e;
    padding: 4px 0;
}

.prerequisites-list li::before {
    content: '→ ';
    margin-right: 4px;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 80px 20px;
}

.empty-icon {
    font-size: 64px;
    margin-bottom: 16px;
}

.empty-title {
    font-size: 24px;
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 8px;
}

.empty-text {
    font-size: 16px;
    color: var(--gray-600);
    margin-bottom: 24px;
}

/* Responsive */
@media (max-width: 768px) {
    .category-container {
        padding: 20px 16px;
    }

    .category-hero {
        padding: 32px 24px;
    }

    .category-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }

    .category-icon-large {
        font-size: 48px;
    }

    .category-title {
        font-size: 32px;
    }

    .category-description {
        font-size: 16px;
    }

    .article-header {
        flex-direction: column;
    }

    .article-thumbnail {
        width: 100%;
        height: 160px;
    }

    .articles-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
}
</style>
@endpush

@section('content')
<div class="category-container">
    <!-- Category Hero -->
    <div class="category-hero">
        <div class="breadcrumb">
            <a href="{{ route('admin.learning-center.index') }}">🏠 หน้าแรก</a>
            <span class="breadcrumb-separator">›</span>
            <span>{{ $category->name }}</span>
        </div>

        <div class="category-header">
            @if($category->icon)
                <div class="category-icon-large">{{ $category->icon }}</div>
            @endif
            <div>
                <h1 class="category-title">{{ $category->name }}</h1>
            </div>
        </div>

        @if($category->description)
            <p class="category-description">{{ $category->description }}</p>
        @endif
    </div>

    <!-- Articles Section -->
    <div class="articles-header">
        <h2 class="section-title">คอร์สทั้งหมด</h2>
        <span class="articles-count">{{ $articles->count() }} คอร์ส</span>
    </div>

    @if($articles->isEmpty())
        <div class="empty-state">
            <div class="empty-icon">📚</div>
            <h3 class="empty-title">ยังไม่มีคอร์สในหมวดหมู่นี้</h3>
            <p class="empty-text">กรุณากลับมาใหม่ในภายหลัง</p>
            <a href="{{ route('admin.learning-center.index') }}" class="btn-start btn-primary">
                ← กลับหน้าหลัก
            </a>
        </div>
    @else
        <div class="articles-grid">
            @foreach($articles as $item)
                @php
                    $article = $item['article'];
                    $progress = $item['progress'];
                    $canUnlock = $item['can_unlock'];
                    $isLocked = $item['is_locked'];

                    $statusLabel = 'ยังไม่เริ่ม';
                    $statusClass = 'not-started';
                    $progressPercentage = 0;

                    if ($progress) {
                        $progressPercentage = $progress->progress_percentage;
                        if ($progress->status === 'completed') {
                            $statusLabel = 'เรียนจบแล้ว';
                            $statusClass = 'completed';
                        } elseif ($progress->status === 'in_progress') {
                            $statusLabel = 'กำลังเรียน';
                            $statusClass = 'in-progress';
                        }
                    }
                @endphp

                <div class="article-card {{ $isLocked ? 'locked' : '' }}">
                    <div class="article-content">
                        <div class="article-header">
                            @if($article->thumbnail_url)
                                <img src="{{ $article->thumbnail_url }}" alt="{{ $article->title }}" class="article-thumbnail">
                            @else
                                <div class="article-thumbnail" style="display: flex; align-items: center; justify-content: center; font-size: 32px;">
                                    📖
                                </div>
                            @endif

                            <div class="article-info">
                                <h3 class="article-title">{{ $article->title }}</h3>

                                <div class="article-meta">
                                    @if($article->estimated_duration)
                                        <span class="meta-item">
                                            ⏱️ {{ $article->formatted_duration }}
                                        </span>
                                    @endif
                                    <span class="meta-item">
                                        👁️ {{ number_format($article->views) }} ครั้ง
                                    </span>
                                    @if(!$isLocked)
                                        <span class="status-badge {{ $statusClass }}">
                                            @if($statusClass === 'completed')
                                                ✅
                                            @elseif($statusClass === 'in-progress')
                                                ⏳
                                            @else
                                                📝
                                            @endif
                                            {{ $statusLabel }}
                                        </span>
                                    @endif
                                </div>

                                <div class="article-tags">
                                    @if($article->difficulty)
                                        <span class="tag difficulty-{{ $article->difficulty }}">
                                            @if($article->difficulty === 'beginner')
                                                🟢 เริ่มต้น
                                            @elseif($article->difficulty === 'intermediate')
                                                🟡 ปานกลาง
                                            @else
                                                🔴 ขั้นสูง
                                            @endif
                                        </span>
                                    @endif

                                    @if($article->tags)
                                        @foreach($article->tags as $tag)
                                            <span class="tag">{{ $tag }}</span>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if(!$isLocked && $progress)
                            <div class="article-progress">
                                <div class="progress-header">
                                    <span class="progress-label">ความคืบหน้า</span>
                                    <span class="progress-percentage">{{ number_format($progressPercentage, 0) }}%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: {{ $progressPercentage }}%"></div>
                                </div>
                            </div>
                        @endif

                        @if($isLocked && $article->prerequisites->isNotEmpty())
                            <div class="prerequisites-warning">
                                <div class="prerequisites-title">🔒 คุณต้องเรียนคอร์สเหล่านี้ก่อน:</div>
                                <ul class="prerequisites-list">
                                    @foreach($article->prerequisites as $prereq)
                                        <li>{{ $prereq->title }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    <div class="article-action">
                        @if($isLocked)
                            <button class="btn-start btn-locked" disabled>
                                🔒 ล็อคอยู่
                            </button>
                        @elseif($statusClass === 'completed')
                            <a href="{{ route('admin.learning-center.article', $article->slug) }}" class="btn-start btn-continue">
                                ดูอีกครั้ง →
                            </a>
                        @elseif($statusClass === 'in-progress')
                            <a href="{{ route('admin.learning-center.article', $article->slug) }}" class="btn-start btn-continue">
                                เรียนต่อ →
                            </a>
                        @else
                            <a href="{{ route('admin.learning-center.article', $article->slug) }}" class="btn-start btn-primary">
                                เริ่มเรียน →
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
