@extends('layouts.user')

@section('title', 'สำรวจ - AI Generation')

@section('content')
<div class="explore-container" x-data="exploreData()">
    <!-- Hero Banner -->
    <div class="explore-hero">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <div class="hero-badge">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
                </svg>
                <span>สำรวจความคิดสร้างสรรค์</span>
            </div>
            <h1 class="hero-title">
                ค้นพบ<span class="gradient-text">ผลงานสุดล้ำ</span><br>จากทั่วโลก
            </h1>
            <p class="hero-subtitle">แรงบันดาลใจไร้ขีดจำกัดจากชุมชนผู้สร้างสรรค์ด้วย AI</p>

            <!-- Search Bar -->
            <div class="search-bar">
                <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text"
                       x-model="searchQuery"
                       @keyup.enter="search()"
                       placeholder="ค้นหาแรงบันดาลใจ..."
                       class="search-input">
                <button @click="search()" class="search-btn">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Categories -->
    <div class="categories-section">
        <div class="categories-scroll">
            <button @click="setCategory(null)"
                    :class="{'active': selectedCategory === null}"
                    class="category-chip">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                </svg>
                ทั้งหมด
            </button>
            <button @click="setCategory('art')"
                    :class="{'active': selectedCategory === 'art'}"
                    class="category-chip">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                </svg>
                ศิลปะ
            </button>
            <button @click="setCategory('photography')"
                    :class="{'active': selectedCategory === 'photography'}"
                    class="category-chip">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                ภาพถ่าย
            </button>
            <button @click="setCategory('anime')"
                    :class="{'active': selectedCategory === 'anime'}"
                    class="category-chip">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                อนิเมะ
            </button>
            <button @click="setCategory('3d')"
                    :class="{'active': selectedCategory === '3d'}"
                    class="category-chip">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                3D
            </button>
            <button @click="setCategory('abstract')"
                    :class="{'active': selectedCategory === 'abstract'}"
                    class="category-chip">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/>
                </svg>
                นามธรรม
            </button>
            <button @click="setCategory('nature')"
                    :class="{'active': selectedCategory === 'nature'}"
                    class="category-chip">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                ธรรมชาติ
            </button>
            <button @click="setCategory('fantasy')"
                    :class="{'active': selectedCategory === 'fantasy'}"
                    class="category-chip">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                </svg>
                แฟนตาซี
            </button>
        </div>
    </div>

    <!-- Featured Section -->
    <div class="featured-section" x-show="selectedCategory === null">
        <div class="section-header">
            <div>
                <h2 class="section-title">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    ผลงานแนะนำ
                </h2>
                <p class="section-subtitle">ผลงานคัดสรรที่ดีที่สุดจากชุมชน</p>
            </div>
        </div>

        <div class="featured-grid">
            <template x-for="(item, index) in featured" :key="item.id">
                <div class="featured-card"
                     @click="viewItem(item)"
                     data-aos="zoom-in"
                     :data-aos-delay="index * 100">
                    <div class="featured-image">
                        <img :src="item.image_url" :alt="item.title" class="image-content">
                        <div class="featured-overlay">
                            <div class="overlay-content">
                                <h3 class="overlay-title" x-text="item.title"></h3>
                                <p class="overlay-creator">โดย <span x-text="item.creator"></span></p>
                                <div class="overlay-stats">
                                    <span class="stat">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                                        </svg>
                                        <span x-text="item.likes"></span>
                                    </span>
                                    <span class="stat">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <span x-text="item.views"></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="featured-badge">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            Featured
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Trending Section -->
    <div class="trending-section">
        <div class="section-header">
            <div>
                <h2 class="section-title">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                    กำลังมาแรง
                </h2>
                <p class="section-subtitle">ผลงานยอดนิยมตอนนี้</p>
            </div>
            <div class="time-filter">
                <button @click="trendingPeriod = 'today'"
                        :class="{'active': trendingPeriod === 'today'}"
                        class="time-btn">วันนี้</button>
                <button @click="trendingPeriod = 'week'"
                        :class="{'active': trendingPeriod === 'week'}"
                        class="time-btn">สัปดาห์นี้</button>
                <button @click="trendingPeriod = 'month'"
                        :class="{'active': trendingPeriod === 'month'}"
                        class="time-btn">เดือนนี้</button>
            </div>
        </div>

        <div class="masonry-grid" x-show="!loading">
            <template x-for="(item, index) in items" :key="item.id">
                <div class="masonry-item"
                     @click="viewItem(item)"
                     data-aos="fade-up"
                     :data-aos-delay="(index % 12) * 50">
                    <div class="item-image">
                        <img :src="item.image_url" :alt="item.title" class="image-content">
                        <div class="item-hover">
                            <div class="hover-top">
                                <button @click.stop="likeItem(item)" class="action-btn">
                                    <svg class="w-5 h-5" :class="{'fill-current text-pink-500': item.is_liked}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                    </svg>
                                </button>
                                <button @click.stop="shareItem(item)" class="action-btn">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="hover-bottom">
                                <div class="item-info">
                                    <p class="item-prompt" x-text="truncate(item.prompt, 100)"></p>
                                    <div class="item-meta">
                                        <span class="creator-name" x-text="item.creator"></span>
                                        <div class="item-stats">
                                            <span>
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                                                </svg>
                                                <span x-text="formatNumber(item.likes)"></span>
                                            </span>
                                            <span>
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                <span x-text="formatNumber(item.views)"></span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="loading-state">
            <div class="loading-grid">
                <template x-for="i in 12" :key="i">
                    <div class="skeleton-card"></div>
                </template>
            </div>
        </div>

        <!-- Load More -->
        <div x-show="hasMore && !loading" class="load-more-section">
            <button @click="loadMore()" class="load-more-btn">
                <span>โหลดเพิ่มเติม</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- Creators Spotlight -->
    <div class="creators-section">
        <div class="section-header">
            <div>
                <h2 class="section-title">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    ผู้สร้างสรรค์ยอดนิยม
                </h2>
                <p class="section-subtitle">ติดตามผู้สร้างสรรค์ที่คุณชื่นชอบ</p>
            </div>
        </div>

        <div class="creators-scroll">
            <template x-for="creator in topCreators" :key="creator.id">
                <div class="creator-card" data-aos="fade-up">
                    <div class="creator-banner" :style="`background: linear-gradient(135deg, ${creator.color1}, ${creator.color2})`"></div>
                    <div class="creator-avatar">
                        <img :src="creator.avatar_url" :alt="creator.name" class="avatar-image">
                        <div class="creator-badge">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>
                    <div class="creator-info">
                        <h3 class="creator-name" x-text="creator.name"></h3>
                        <p class="creator-stats">
                            <span x-text="formatNumber(creator.followers)"></span> ผู้ติดตาม •
                            <span x-text="creator.works_count"></span> ผลงาน
                        </p>
                        <button @click="followCreator(creator)" class="follow-btn" :class="{'following': creator.is_following}">
                            <template x-if="!creator.is_following">
                                <span>ติดตาม</span>
                            </template>
                            <template x-if="creator.is_following">
                                <span>กำลังติดตาม</span>
                            </template>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
<style>
    .explore-container {
        min-height: 100vh;
        padding-bottom: 60px;
    }

    /* Hero Section */
    .explore-hero {
        position: relative;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
        padding: 100px 20px 80px;
        margin: -24px -24px 0;
        overflow: hidden;
    }

    .dark .explore-hero {
        background: linear-gradient(135deg, #1e1b4b 0%, #4c1d95 50%, #581c87 100%);
    }

    .hero-overlay {
        position: absolute;
        inset: 0;
        background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse"><path d="M 40 0 L 0 0 0 40" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
        opacity: 0.5;
    }

    .hero-content {
        position: relative;
        max-width: 800px;
        margin: 0 auto;
        text-align: center;
        color: white;
    }

    .hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        padding: 10px 20px;
        border-radius: 30px;
        font-weight: 600;
        font-size: 0.875rem;
        margin-bottom: 24px;
    }

    .hero-title {
        font-size: 3.5rem;
        font-weight: 900;
        line-height: 1.2;
        margin-bottom: 20px;
        text-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    }

    .gradient-text {
        background: linear-gradient(90deg, #ffd89b 0%, #19547b 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .dark .gradient-text {
        background: linear-gradient(90deg, #fbbf24 0%, #f59e0b 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .hero-subtitle {
        font-size: 1.25rem;
        opacity: 0.95;
        margin-bottom: 40px;
    }

    /* Search Bar */
    .search-bar {
        display: flex;
        align-items: center;
        max-width: 600px;
        margin: 0 auto;
        background: white;
        border-radius: 60px;
        padding: 8px 8px 8px 24px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
    }

    .dark .search-bar {
        background: #1e293b;
    }

    .search-bar:focus-within {
        box-shadow: 0 15px 50px rgba(0, 0, 0, 0.3);
        transform: translateY(-2px);
    }

    .search-icon {
        width: 24px;
        height: 24px;
        color: #9ca3af;
        margin-right: 12px;
    }

    .search-input {
        flex: 1;
        border: none;
        outline: none;
        background: transparent;
        font-size: 1.125rem;
        color: #1f2937;
        padding: 12px 0;
    }

    .dark .search-input {
        color: #f1f5f9;
    }

    .search-input::placeholder {
        color: #9ca3af;
    }

    .search-btn {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 50%;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .dark .search-btn {
        background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
    }

    .search-btn:hover {
        transform: scale(1.1);
    }

    /* Categories */
    .categories-section {
        padding: 32px 0;
        margin-bottom: 40px;
    }

    .categories-scroll {
        display: flex;
        gap: 12px;
        overflow-x: auto;
        padding-bottom: 16px;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
    }

    .categories-scroll::-webkit-scrollbar {
        height: 6px;
    }

    .categories-scroll::-webkit-scrollbar-track {
        background: transparent;
    }

    .categories-scroll::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }

    .category-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        background: white;
        color: #6b7280;
        border: 2px solid #e5e7eb;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.95rem;
        white-space: nowrap;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .dark .category-chip {
        background: #1e293b;
        color: #94a3b8;
        border-color: #334155;
    }

    .category-chip:hover {
        border-color: #667eea;
        color: #667eea;
        transform: translateY(-2px);
    }

    .dark .category-chip:hover {
        border-color: #8b5cf6;
        color: #a78bfa;
    }

    .category-chip.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-color: transparent;
        color: white;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }

    .dark .category-chip.active {
        background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
        box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4);
    }

    /* Section Header */
    .section-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        margin-bottom: 32px;
    }

    .section-title {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 2rem;
        font-weight: 800;
        color: #1f2937;
        margin-bottom: 8px;
    }

    .dark .section-title {
        color: #f1f5f9;
    }

    .section-title svg {
        color: #667eea;
    }

    .dark .section-title svg {
        color: #a78bfa;
    }

    .section-subtitle {
        color: #6b7280;
        font-size: 1rem;
    }

    .dark .section-subtitle {
        color: #94a3b8;
    }

    .time-filter {
        display: flex;
        gap: 8px;
        background: white;
        border-radius: 12px;
        padding: 4px;
    }

    .dark .time-filter {
        background: #1e293b;
    }

    .time-btn {
        padding: 8px 20px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.875rem;
        color: #6b7280;
        background: transparent;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .dark .time-btn {
        color: #94a3b8;
    }

    .time-btn:hover {
        color: #667eea;
    }

    .dark .time-btn:hover {
        color: #a78bfa;
    }

    .time-btn.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .dark .time-btn.active {
        background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
    }

    /* Featured Section */
    .featured-section {
        margin-bottom: 60px;
    }

    .featured-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 24px;
    }

    .featured-card {
        border-radius: 24px;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .featured-card:hover {
        transform: translateY(-8px);
    }

    .featured-image {
        position: relative;
        padding-bottom: 75%;
        overflow: hidden;
    }

    .image-content {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .featured-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(0deg, rgba(0, 0, 0, 0.8) 0%, rgba(0, 0, 0, 0) 50%);
        opacity: 0;
        transition: all 0.4s ease;
    }

    .featured-card:hover .featured-overlay {
        opacity: 1;
    }

    .overlay-content {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 24px;
        color: white;
        transform: translateY(10px);
        transition: all 0.4s ease;
    }

    .featured-card:hover .overlay-content {
        transform: translateY(0);
    }

    .overlay-title {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .overlay-creator {
        font-size: 0.875rem;
        opacity: 0.9;
        margin-bottom: 16px;
    }

    .overlay-stats {
        display: flex;
        gap: 20px;
    }

    .overlay-stats .stat {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.875rem;
    }

    .featured-badge {
        position: absolute;
        top: 16px;
        right: 16px;
        display: flex;
        align-items: center;
        gap: 6px;
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 700;
        font-size: 0.75rem;
        box-shadow: 0 4px 15px rgba(251, 191, 36, 0.4);
    }

    /* Trending Section */
    .trending-section {
        margin-bottom: 60px;
    }

    /* Masonry Grid */
    .masonry-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
    }

    .masonry-item {
        break-inside: avoid;
        cursor: pointer;
    }

    .item-image {
        position: relative;
        border-radius: 20px;
        overflow: hidden;
        background: #f3f4f6;
        transition: all 0.3s ease;
    }

    .dark .item-image {
        background: #0f172a;
    }

    .masonry-item:hover .item-image {
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        transform: translateY(-4px);
    }

    .item-hover {
        position: absolute;
        inset: 0;
        background: linear-gradient(0deg, rgba(0, 0, 0, 0.9) 0%, rgba(0, 0, 0, 0.3) 40%, rgba(0, 0, 0, 0) 70%);
        opacity: 0;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 16px;
    }

    .masonry-item:hover .item-hover {
        opacity: 1;
    }

    .hover-top {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
    }

    .action-btn {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border-radius: 50%;
        border: none;
        color: #1f2937;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .dark .action-btn {
        background: rgba(0, 0, 0, 0.5);
        color: #f1f5f9;
    }

    .action-btn:hover {
        transform: scale(1.1);
    }

    .item-info {
        color: white;
    }

    .item-prompt {
        font-size: 0.875rem;
        line-height: 1.5;
        margin-bottom: 12px;
    }

    .item-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 0.75rem;
    }

    .creator-name {
        font-weight: 600;
    }

    .item-stats {
        display: flex;
        gap: 12px;
    }

    .item-stats span {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    /* Loading State */
    .loading-state {
        padding: 40px 0;
    }

    .loading-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
    }

    .skeleton-card {
        background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
        background-size: 200% 100%;
        animation: shimmer 1.5s infinite;
        border-radius: 20px;
        height: 350px;
    }

    .dark .skeleton-card {
        background: linear-gradient(90deg, #1e293b 25%, #334155 50%, #1e293b 75%);
        background-size: 200% 100%;
    }

    @keyframes shimmer {
        0% { background-position: -200% 0; }
        100% { background-position: 200% 0; }
    }

    /* Load More */
    .load-more-section {
        text-align: center;
        margin-top: 40px;
    }

    .load-more-btn {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        padding: 16px 40px;
        background: white;
        color: #667eea;
        border: 2px solid #667eea;
        border-radius: 50px;
        font-weight: 700;
        font-size: 1.125rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .dark .load-more-btn {
        background: #1e293b;
        border-color: #8b5cf6;
        color: #a78bfa;
    }

    .load-more-btn:hover {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-color: transparent;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    }

    .dark .load-more-btn:hover {
        background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
    }

    /* Creators Section */
    .creators-section {
        margin-bottom: 60px;
    }

    .creators-scroll {
        display: flex;
        gap: 24px;
        overflow-x: auto;
        padding-bottom: 16px;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
    }

    .creators-scroll::-webkit-scrollbar {
        height: 6px;
    }

    .creators-scroll::-webkit-scrollbar-track {
        background: transparent;
    }

    .creators-scroll::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }

    .creator-card {
        flex: 0 0 280px;
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .dark .creator-card {
        background: #1e293b;
    }

    .creator-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 35px rgba(0, 0, 0, 0.2);
    }

    .creator-banner {
        height: 80px;
    }

    .creator-avatar {
        position: relative;
        width: 80px;
        height: 80px;
        margin: -40px auto 16px;
    }

    .avatar-image {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        border: 4px solid white;
        object-fit: cover;
    }

    .dark .avatar-image {
        border-color: #1e293b;
    }

    .creator-badge {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 28px;
        height: 28px;
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        border: 3px solid white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
    }

    .dark .creator-badge {
        border-color: #1e293b;
    }

    .creator-info {
        padding: 0 20px 20px;
        text-align: center;
    }

    .creator-name {
        font-size: 1.125rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 8px;
    }

    .dark .creator-name {
        color: #f1f5f9;
    }

    .creator-stats {
        font-size: 0.875rem;
        color: #6b7280;
        margin-bottom: 16px;
    }

    .dark .creator-stats {
        color: #94a3b8;
    }

    .follow-btn {
        width: 100%;
        padding: 10px 24px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .dark .follow-btn {
        background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
    }

    .follow-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }

    .follow-btn.following {
        background: white;
        color: #667eea;
        border: 2px solid #667eea;
    }

    .dark .follow-btn.following {
        background: #1e293b;
        color: #a78bfa;
        border: 2px solid #8b5cf6;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .hero-title {
            font-size: 2rem;
        }

        .featured-grid {
            grid-template-columns: 1fr;
        }

        .masonry-grid {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 12px;
        }

        .section-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 16px;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
    AOS.init({
        duration: 600,
        once: true
    });

    function exploreData() {
        return {
            loading: false,
            searchQuery: '',
            selectedCategory: null,
            trendingPeriod: 'today',
            hasMore: true,
            page: 1,

            // Mock featured data
            featured: [
                {
                    id: 1,
                    title: 'Cyberpunk City at Night',
                    creator: 'Digital Artist',
                    image_url: 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=800&h=600&fit=crop',
                    likes: 1234,
                    views: 5678
                },
                {
                    id: 2,
                    title: 'Fantasy Dragon Portrait',
                    creator: 'AI Master',
                    image_url: 'https://images.unsplash.com/photo-1579546929518-9e396f3cc809?w=800&h=600&fit=crop',
                    likes: 2345,
                    views: 8901
                },
                {
                    id: 3,
                    title: 'Serene Mountain Landscape',
                    creator: 'Nature Lover',
                    image_url: 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=800&h=600&fit=crop',
                    likes: 3456,
                    views: 12345
                }
            ],

            // Mock items data
            items: [
                { id: 1, image_url: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=400&h=500&fit=crop', title: 'AI Art 1', prompt: 'Beautiful sunset over mountains with purple sky', creator: 'User1', likes: 123, views: 456, is_liked: false },
                { id: 2, image_url: 'https://images.unsplash.com/photo-1579546929662-711aa81148cf?w=400&h=600&fit=crop', title: 'AI Art 2', prompt: 'Futuristic city with neon lights', creator: 'User2', likes: 234, views: 567, is_liked: false },
                { id: 3, image_url: 'https://images.unsplash.com/photo-1558591710-4b4a1ae0f04d?w=400&h=400&fit=crop', title: 'AI Art 3', prompt: 'Fantasy character design', creator: 'User3', likes: 345, views: 678, is_liked: false },
                { id: 4, image_url: 'https://images.unsplash.com/photo-1618172193622-ae2d025f4032?w=400&h=550&fit=crop', title: 'AI Art 4', prompt: 'Abstract colorful patterns', creator: 'User4', likes: 456, views: 789, is_liked: false },
                { id: 5, image_url: 'https://images.unsplash.com/photo-1618556450994-a6a128ef0d9d?w=400&h=450&fit=crop', title: 'AI Art 5', prompt: 'Magical forest scene', creator: 'User5', likes: 567, views: 890, is_liked: false },
                { id: 6, image_url: 'https://images.unsplash.com/photo-1579762715118-a6f1d4b934f1?w=400&h=500&fit=crop', title: 'AI Art 6', prompt: 'Space exploration theme', creator: 'User6', likes: 678, views: 901, is_liked: false },
            ],

            // Mock top creators
            topCreators: [
                { id: 1, name: 'Creative Mind', avatar_url: 'https://i.pravatar.cc/150?img=1', followers: 12500, works_count: 234, is_following: false, color1: '#667eea', color2: '#764ba2' },
                { id: 2, name: 'AI Wizard', avatar_url: 'https://i.pravatar.cc/150?img=2', followers: 9800, works_count: 156, is_following: false, color1: '#f093fb', color2: '#f5576c' },
                { id: 3, name: 'Digital Dreamer', avatar_url: 'https://i.pravatar.cc/150?img=3', followers: 15200, works_count: 389, is_following: true, color1: '#4facfe', color2: '#00f2fe' },
                { id: 4, name: 'Art Pioneer', avatar_url: 'https://i.pravatar.cc/150?img=4', followers: 7600, works_count: 98, is_following: false, color1: '#43e97b', color2: '#38f9d7' },
                { id: 5, name: 'Pixel Master', avatar_url: 'https://i.pravatar.cc/150?img=5', followers: 11300, works_count: 267, is_following: false, color1: '#fa709a', color2: '#fee140' },
            ],

            setCategory(category) {
                this.selectedCategory = category;
                this.page = 1;
                this.loadItems();
            },

            search() {
                if (this.searchQuery.trim()) {
                    this.page = 1;
                    this.loadItems();
                }
            },

            async loadItems() {
                this.loading = true;
                // Simulate API call
                await new Promise(resolve => setTimeout(resolve, 1000));
                this.loading = false;
            },

            async loadMore() {
                this.page++;
                await this.loadItems();
            },

            viewItem(item) {
                console.log('View item:', item);
                // Navigate to item detail page
            },

            likeItem(item) {
                item.is_liked = !item.is_liked;
                item.likes += item.is_liked ? 1 : -1;
            },

            shareItem(item) {
                console.log('Share item:', item);
                // Implement share functionality
            },

            followCreator(creator) {
                creator.is_following = !creator.is_following;
                creator.followers += creator.is_following ? 1 : -1;
            },

            formatNumber(num) {
                if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
                if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
                return num.toString();
            },

            truncate(text, length) {
                if (!text) return '';
                return text.length > length ? text.substring(0, length) + '...' : text;
            }
        }
    }
</script>
@endpush
