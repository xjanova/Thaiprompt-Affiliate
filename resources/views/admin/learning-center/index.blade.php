@extends('layouts.admin')

@section('title', 'ศูนย์เรียนรู้')

@push('styles')
<style>
:root {
    --primary-color: #4f46e5;
    --primary-dark: #4338ca;
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

/* Hero Section - Professional and Clean */
.lc-hero {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
    border-radius: 16px;
    padding: 48px;
    color: white;
    position: relative;
    overflow: hidden;
    margin-bottom: 32px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.lc-hero-title {
    font-size: 36px;
    font-weight: 700;
    margin-bottom: 12px;
    letter-spacing: -0.5px;
}

.lc-hero-subtitle {
    font-size: 18px;
    opacity: 0.95;
    margin-bottom: 24px;
    font-weight: 400;
}

/* Search Box */
.lc-search-wrapper {
    position: relative;
    max-width: 600px;
}

.lc-search-icon {
    position: absolute;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gray-600);
    z-index: 2;
}

.lc-search-input {
    width: 100%;
    padding: 14px 20px 14px 48px;
    border-radius: 12px;
    border: 1px solid var(--gray-200);
    background: white;
    font-size: 15px;
    transition: all 0.2s ease;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.lc-search-input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.lc-search-input::placeholder {
    color: var(--gray-600);
}

/* Category Cards */
.lc-category-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid var(--gray-200);
    height: 100%;
    cursor: pointer;
    position: relative;
}

.lc-category-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    border-color: var(--primary-color);
}

.lc-category-icon {
    width: 64px;
    height: 64px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    margin-bottom: 16px;
}

.lc-category-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--gray-900);
}

.lc-category-desc {
    color: var(--gray-600);
    margin-bottom: 16px;
    line-height: 1.5;
    font-size: 14px;
}

.lc-category-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: auto;
    padding-top: 16px;
    border-top: 1px solid var(--gray-100);
}

.lc-stats-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    background: var(--gray-100);
    color: var(--gray-700);
}

.lc-arrow-icon {
    color: var(--primary-color);
    font-size: 18px;
    transition: transform 0.2s ease;
}

.lc-category-card:hover .lc-arrow-icon {
    transform: translateX(4px);
}

/* Article Cards */
.lc-article-card {
    background: white;
    border-radius: 12px;
    padding: 20px 24px;
    transition: all 0.2s ease;
    border: 1px solid var(--gray-200);
    cursor: pointer;
}

.lc-article-card:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    border-color: var(--gray-300);
}

.lc-article-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
}

.lc-article-category {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    background: var(--gray-100);
    color: var(--gray-700);
}

.lc-article-time {
    color: var(--gray-600);
    font-size: 13px;
}

.lc-article-title {
    font-size: 16px;
    font-weight: 600;
    color: var(--gray-900);
    margin-bottom: 0;
}

.lc-article-stats {
    display: flex;
    align-items: center;
    gap: 16px;
}

.lc-article-views {
    color: var(--gray-600);
    font-size: 13px;
}

.lc-btn-read {
    padding: 8px 20px;
    border-radius: 8px;
    border: none;
    background: var(--primary-color);
    color: white;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s ease;
    cursor: pointer;
}

.lc-btn-read:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
}

/* Quick Links */
.lc-quick-link {
    background: white;
    border-radius: 12px;
    padding: 24px;
    border: 1px solid var(--gray-200);
    transition: all 0.2s ease;
    height: 100%;
}

.lc-quick-link:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.lc-quick-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-right: 16px;
    flex-shrink: 0;
}

.lc-quick-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--gray-900);
}

.lc-quick-desc {
    color: var(--gray-600);
    margin-bottom: 12px;
    font-size: 14px;
    line-height: 1.5;
}

.lc-quick-link-btn {
    color: var(--primary-color);
    font-weight: 500;
    text-decoration: none;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.lc-quick-link-btn:hover {
    color: var(--primary-dark);
}

/* Section Title */
.lc-section-title {
    font-size: 24px;
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 20px;
}

/* Breadcrumb */
.lc-breadcrumb {
    background: transparent;
    padding: 0;
    margin-bottom: 20px;
}

.lc-breadcrumb .breadcrumb-item + .breadcrumb-item::before {
    content: "›";
    color: var(--gray-600);
}

.lc-breadcrumb a {
    color: var(--gray-600);
    text-decoration: none;
}

.lc-breadcrumb a:hover {
    color: var(--primary-color);
}

.lc-breadcrumb .active {
    color: var(--gray-900);
}

/* Gradient backgrounds for category icons */
.lc-gradient-purple { background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%); }
.lc-gradient-blue { background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%); }
.lc-gradient-red { background: linear-gradient(135deg, #ef4444 0%, #ec4899 100%); }
.lc-gradient-amber { background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%); }
.lc-gradient-green { background: linear-gradient(135deg, #10b981 0%, #14b8a6 100%); }
.lc-gradient-yellow { background: linear-gradient(135deg, #eab308 0%, #f59e0b 100%); }

/* View all link */
.lc-view-all {
    color: var(--primary-color);
    font-weight: 500;
    text-decoration: none;
    font-size: 14px;
}

.lc-view-all:hover {
    color: var(--primary-dark);
}

/* Responsive */
@media (max-width: 768px) {
    .lc-hero {
        padding: 32px 24px;
    }

    .lc-hero-title {
        font-size: 28px;
    }

    .lc-hero-subtitle {
        font-size: 16px;
    }

    .lc-category-card {
        margin-bottom: 16px;
    }
}
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb lc-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a></li>
            <li class="breadcrumb-item active">ศูนย์เรียนรู้</li>
        </ol>
    </nav>

    <!-- Hero Section -->
    <div class="lc-hero">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <h1 class="lc-hero-title">ศูนย์เรียนรู้</h1>
                <p class="lc-hero-subtitle">
                    เรียนรู้การใช้งาน AI และระบบ Affiliate อย่างมืออาชีพ
                </p>

                <!-- Search Box -->
                <div class="lc-search-wrapper">
                    <i class="fas fa-search lc-search-icon"></i>
                    <input type="text" class="lc-search-input" placeholder="ค้นหาบทความ คู่มือ หรือวิธีการใช้งาน...">
                </div>
            </div>
        </div>
    </div>

    <!-- Categories -->
    <div class="mb-5">
        <h2 class="lc-section-title">หมวดหมู่</h2>
        <div class="row g-4">
            @foreach($categories as $category)
            <div class="col-lg-4 col-md-6">
                <div class="lc-category-card" onclick="window.location.href='#'">
                    @php
                        $gradients = ['lc-gradient-purple', 'lc-gradient-blue', 'lc-gradient-red', 'lc-gradient-amber', 'lc-gradient-green', 'lc-gradient-yellow'];
                        $gradientClass = $gradients[$loop->index % 6];
                    @endphp
                    <div class="lc-category-icon {{ $gradientClass }}">
                        {{ $category['icon'] }}
                    </div>
                    <h3 class="lc-category-title">
                        {{ $category['name'] }}
                    </h3>
                    <p class="lc-category-desc">
                        {{ $category['description'] }}
                    </p>
                    <div class="lc-category-footer">
                        <span class="lc-stats-badge">
                            <i class="fas fa-book"></i>
                            {{ $category['articles_count'] }} บทความ
                        </span>
                        <i class="fas fa-arrow-right lc-arrow-icon"></i>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Popular Articles -->
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="lc-section-title mb-0">บทความยอดนิยม</h2>
            <a href="#" class="lc-view-all">
                ดูทั้งหมด <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="row g-3">
            @foreach($popular_articles as $article)
            <div class="col-12">
                <div class="lc-article-card">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <div class="lc-article-meta">
                                <span class="lc-article-category">
                                    {{ $article['category'] }}
                                </span>
                                <span class="lc-article-time">
                                    <i class="far fa-clock me-1"></i>{{ $article['duration'] }}
                                </span>
                            </div>
                            <h4 class="lc-article-title">
                                {{ $article['title'] }}
                            </h4>
                        </div>
                        <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                            <div class="lc-article-stats">
                                <span class="lc-article-views">
                                    <i class="fas fa-eye me-1"></i>{{ number_format($article['views']) }} views
                                </span>
                                <button class="lc-btn-read">
                                    อ่านเลย
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Quick Links -->
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="lc-quick-link">
                <div class="d-flex align-items-start">
                    <div class="lc-quick-icon lc-gradient-purple">
                        🚀
                    </div>
                    <div>
                        <h5 class="lc-quick-title">เริ่มต้นใช้งาน</h5>
                        <p class="lc-quick-desc">คู่มือสำหรับผู้เริ่มต้น พร้อมวิดีโอแนะนำ</p>
                        <a href="#" class="lc-quick-link-btn">
                            เริ่มเลย <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="lc-quick-link">
                <div class="d-flex align-items-start">
                    <div class="lc-quick-icon lc-gradient-red">
                        🎥
                    </div>
                    <div>
                        <h5 class="lc-quick-title">วิดีโอสอน</h5>
                        <p class="lc-quick-desc">เรียนรู้ผ่านวิดีโอสอนใช้งานทีละขั้นตอน</p>
                        <a href="#" class="lc-quick-link-btn">
                            ดูวิดีโอ <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="lc-quick-link">
                <div class="d-flex align-items-start">
                    <div class="lc-quick-icon lc-gradient-blue">
                        💬
                    </div>
                    <div>
                        <h5 class="lc-quick-title">ติดต่อสนับสนุน</h5>
                        <p class="lc-quick-desc">ติดปัญหา? ทีมงานพร้อมช่วยเหลือคุณ</p>
                        <a href="#" class="lc-quick-link-btn">
                            ติดต่อเรา <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Search functionality
    $('.lc-search-input').on('keyup', function(e) {
        if (e.key === 'Enter') {
            const query = $(this).val().trim();
            if (query) {
                console.log('Searching for:', query);
                // TODO: Implement search functionality
            }
        }
    });

    // Category card click handlers
    $('.lc-category-card').on('click', function() {
        // TODO: Implement category navigation
        console.log('Category clicked');
    });

    // Article card click handlers
    $('.lc-article-card').on('click', function(e) {
        if (!$(e.target).hasClass('lc-btn-read')) {
            // TODO: Implement article navigation
            console.log('Article clicked');
        }
    });

    // Read button handlers
    $('.lc-btn-read').on('click', function(e) {
        e.stopPropagation();
        // TODO: Implement article view
        console.log('Read button clicked');
    });
});
</script>
@endpush
