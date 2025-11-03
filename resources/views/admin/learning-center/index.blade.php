@extends('layouts.admin')

@section('title', 'ศูนย์เรียนรู้')

@push('styles')
<style>
:root {
    --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --gradient-secondary: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.hero-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 24px;
    padding: 60px;
    color: white;
    position: relative;
    overflow: hidden;
    margin-bottom: 40px;
}

.hero-section::before {
    content: '';
    position: absolute;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: pulse 4s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.1); opacity: 0.8; }
}

.category-card {
    background: white;
    border-radius: 20px;
    padding: 32px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 2px solid #e2e8f0;
    height: 100%;
    cursor: pointer;
}

.category-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    border-color: transparent;
}

.category-icon {
    width: 72px;
    height: 72px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 36px;
    margin-bottom: 24px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}

.article-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    transition: all 0.3s ease;
    border: 1px solid #e2e8f0;
    cursor: pointer;
}

.article-card:hover {
    transform: translateX(8px);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.1);
    border-color: #667eea;
}

.search-box {
    position: relative;
    z-index: 10;
}

.search-input {
    padding: 16px 24px 16px 56px;
    border-radius: 50px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    color: white;
    font-size: 16px;
    width: 100%;
    transition: all 0.3s ease;
}

.search-input::placeholder {
    color: rgba(255, 255, 255, 0.7);
}

.search-input:focus {
    outline: none;
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.5);
}

.search-icon {
    position: absolute;
    left: 24px;
    top: 50%;
    transform: translateY(-50%);
    color: rgba(255, 255, 255, 0.7);
    font-size: 20px;
}

.stats-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    background: rgba(102, 126, 234, 0.1);
    color: #667eea;
}

.breadcrumb-custom {
    background: transparent;
    padding: 0;
    margin-bottom: 24px;
}

.breadcrumb-custom .breadcrumb-item + .breadcrumb-item::before {
    content: "›";
    color: #64748b;
}

.section-title {
    font-size: 28px;
    font-weight: 800;
    color: #1e293b;
    margin-bottom: 24px;
}
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-custom">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a></li>
            <li class="breadcrumb-item active">ศูนย์เรียนรู้</li>
        </ol>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 style="font-size: 48px; font-weight: 800; margin-bottom: 16px;">ศูนย์เรียนรู้</h1>
                <p style="font-size: 20px; opacity: 0.9; margin-bottom: 32px;">
                    เรียนรู้การใช้งาน AI และระบบ Affiliate อย่างมืออาชีพ
                </p>

                <!-- Search Box -->
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="ค้นหาบทความ คู่มือ หรือวิธีการใช้งาน...">
                </div>
            </div>
            <div class="col-lg-4 text-center d-none d-lg-block">
                <div style="font-size: 180px; opacity: 0.2;">📚</div>
            </div>
        </div>
    </div>

    <!-- Categories -->
    <div class="mb-5">
        <h2 class="section-title">หมวดหมู่</h2>
        <div class="row g-4">
            @foreach($categories as $category)
            <div class="col-lg-4 col-md-6">
                <div class="category-card" onclick="window.location.href='#'">
                    <div class="category-icon bg-gradient-{{ $loop->index % 6 }}" style="background: linear-gradient(135deg, {{ $category['color'] ?? 'from-gray-600 to-gray-800' }});">
                        {{ $category['icon'] }}
                    </div>
                    <h3 style="font-size: 22px; font-weight: 700; margin-bottom: 12px; color: #1e293b;">
                        {{ $category['name'] }}
                    </h3>
                    <p style="color: #64748b; margin-bottom: 20px; line-height: 1.6;">
                        {{ $category['description'] }}
                    </p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="stats-badge">
                            <i class="fas fa-book"></i>
                            {{ $category['articles_count'] }} บทความ
                        </span>
                        <i class="fas fa-arrow-right" style="color: #667eea;"></i>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Popular Articles -->
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-title mb-0">บทความยอดนิยม</h2>
            <a href="#" class="btn btn-link text-decoration-none" style="color: #667eea; font-weight: 600;">
                ดูทั้งหมด <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="row g-3">
            @foreach($popular_articles as $article)
            <div class="col-12">
                <div class="article-card">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <span class="badge bg-light text-dark" style="font-size: 11px; font-weight: 600;">
                                    {{ $article['category'] }}
                                </span>
                                <span style="color: #94a3b8; font-size: 14px;">
                                    <i class="far fa-clock me-1"></i>{{ $article['duration'] }}
                                </span>
                            </div>
                            <h4 style="font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 8px;">
                                {{ $article['title'] }}
                            </h4>
                        </div>
                        <div class="col-lg-4 text-lg-end">
                            <div class="d-flex align-items-center justify-content-lg-end gap-3">
                                <span style="color: #94a3b8; font-size: 14px;">
                                    <i class="fas fa-eye me-1"></i>{{ number_format($article['views']) }} views
                                </span>
                                <button class="btn btn-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 8px 20px; border-radius: 20px;">
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
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; margin-right: 16px;">
                            🚀
                        </div>
                        <h5 class="mb-0" style="font-weight: 700;">เริ่มต้นใช้งาน</h5>
                    </div>
                    <p class="text-muted mb-3">คู่มือสำหรับผู้เริ่มต้น พร้อมวิดีโอแนะนำ</p>
                    <a href="#" class="btn btn-link p-0 text-decoration-none" style="color: #667eea; font-weight: 600;">
                        เริ่มเลย <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; margin-right: 16px;">
                            🎥
                        </div>
                        <h5 class="mb-0" style="font-weight: 700;">วิดีโอสอน</h5>
                    </div>
                    <p class="text-muted mb-3">เรียนรู้ผ่านวิดีโอสอนใช้งานทีละขั้นตอน</p>
                    <a href="#" class="btn btn-link p-0 text-decoration-none" style="color: #f5576c; font-weight: 600;">
                        ดูวิดีโอ <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; margin-right: 16px;">
                            💬
                        </div>
                        <h5 class="mb-0" style="font-weight: 700;">ติดต่อสนับสนุน</h5>
                    </div>
                    <p class="text-muted mb-3">ติดปัญหา? ทีมงานพร้อมช่วยเหลือคุณ</p>
                    <a href="#" class="btn btn-link p-0 text-decoration-none" style="color: #4facfe; font-weight: 600;">
                        ติดต่อเรา <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Search functionality (placeholder)
    $('.search-input').on('keyup', function(e) {
        if (e.key === 'Enter') {
            const query = $(this).val();
            console.log('Searching for:', query);
            // TODO: Implement search
        }
    });
});
</script>
@endpush
