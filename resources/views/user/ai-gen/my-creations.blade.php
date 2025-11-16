@extends('layouts.user-arrow-x')

@section('title', 'ผลงานของฉัน - AI Generation')

@section('content')
<div class="my-creations-container" x-data="myCreationsData()">
    <!-- Header Section -->
    <div class="creations-header">
        <div class="header-content">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <h1 class="header-title">
                        <svg class="inline-block w-10 h-10 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        ผลงานของฉัน
                    </h1>
                    <p class="header-subtitle">จัดการและดูผลงาน AI ที่คุณสร้างขึ้น</p>
                </div>

                <button @click="showCreateModal()" class="btn-create">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    สร้างใหม่
                </button>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="filters-section">
        <div class="filters-wrapper">
            <!-- Type Filter -->
            <div class="filter-group">
                <label class="filter-label">ประเภท</label>
                <div class="filter-buttons">
                    <button @click="setFilter('type', null)"
                            :class="{'active': filters.type === null}"
                            class="filter-btn">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        ทั้งหมด
                    </button>
                    <button @click="setFilter('type', 'image')"
                            :class="{'active': filters.type === 'image'}"
                            class="filter-btn">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        ภาพ
                    </button>
                    <button @click="setFilter('type', 'video')"
                            :class="{'active': filters.type === 'video'}"
                            class="filter-btn">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        วิดีโอ
                    </button>
                </div>
            </div>

            <!-- Status Filter -->
            <div class="filter-group">
                <label class="filter-label">สถานะ</label>
                <div class="filter-buttons">
                    <button @click="setFilter('status', null)"
                            :class="{'active': filters.status === null}"
                            class="filter-btn">ทั้งหมด</button>
                    <button @click="setFilter('status', 'completed')"
                            :class="{'active': filters.status === 'completed'}"
                            class="filter-btn">สำเร็จ</button>
                    <button @click="setFilter('status', 'processing')"
                            :class="{'active': filters.status === 'processing'}"
                            class="filter-btn">กำลังสร้าง</button>
                    <button @click="setFilter('status', 'failed')"
                            :class="{'active': filters.status === 'failed'}"
                            class="filter-btn">ล้มเหลว</button>
                </div>
            </div>

            <!-- Favorite Filter -->
            <div class="filter-group">
                <button @click="toggleFavoriteFilter()"
                        :class="{'active': filters.is_favorite}"
                        class="filter-btn favorite-btn">
                    <svg class="w-4 h-4" :class="{'fill-current': filters.is_favorite}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                    รายการโปรด
                </button>
            </div>

            <!-- Sort -->
            <div class="filter-group ml-auto">
                <select x-model="sortBy" @change="loadGenerations()" class="sort-select">
                    <option value="newest">ใหม่สุด</option>
                    <option value="oldest">เก่าสุด</option>
                    <option value="popular">ยอดนิยม</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Stats Bar -->
    <div class="stats-bar">
        <div class="stat-card">
            <div class="stat-icon bg-gradient-to-br from-purple-500 to-pink-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <div class="stat-value" x-text="stats.total_images"></div>
                <div class="stat-label">ภาพทั้งหมด</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-gradient-to-br from-blue-500 to-cyan-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <div class="stat-value" x-text="stats.total_videos"></div>
                <div class="stat-label">วิดีโอทั้งหมด</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-gradient-to-br from-pink-500 to-rose-500">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div>
                <div class="stat-value" x-text="stats.total_favorites"></div>
                <div class="stat-label">รายการโปรด</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-gradient-to-br from-orange-500 to-yellow-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <div>
                <div class="stat-value" x-text="stats.credits_remaining"></div>
                <div class="stat-label">เครดิตคงเหลือ</div>
            </div>
        </div>
    </div>

    <!-- Creations Grid -->
    <div class="creations-grid" x-show="!loading">
        <template x-if="generations.length === 0">
            <div class="empty-state">
                <div class="empty-icon">
                    <svg class="w-24 h-24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="empty-title">ยังไม่มีผลงาน</h3>
                <p class="empty-text">เริ่มสร้างผลงานด้วย AI กันเลย!</p>
                <button @click="showCreateModal()" class="btn-create mt-6">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    สร้างเลย
                </button>
            </div>
        </template>

        <template x-for="(generation, index) in generations" :key="generation.id">
            <div class="creation-card"
                 @click="viewGeneration(generation)"
                 data-aos="fade-up"
                 :data-aos-delay="index * 50">

                <!-- Image/Video Preview -->
                <div class="creation-media">
                    <template x-if="generation.type === 'image'">
                        <img :src="generation.file_url || generation.thumbnail_url || '/images/placeholder.jpg'"
                             :alt="generation.prompt"
                             class="media-content">
                    </template>
                    <template x-if="generation.type === 'video'">
                        <div class="relative">
                            <img :src="generation.thumbnail_url || '/images/video-placeholder.jpg'"
                                 :alt="generation.prompt"
                                 class="media-content">
                            <div class="video-overlay">
                                <svg class="w-16 h-16 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </div>
                    </template>

                    <!-- Status Badge -->
                    <div class="status-badge" :class="'status-' + generation.status">
                        <template x-if="generation.status === 'completed'">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </template>
                        <template x-if="generation.status === 'processing'">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </template>
                        <template x-if="generation.status === 'failed'">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </template>
                        <span x-text="getStatusText(generation.status)"></span>
                    </div>

                    <!-- Favorite Button -->
                    <button @click.stop="toggleFavorite(generation)"
                            class="favorite-icon"
                            :class="{'active': generation.is_favorite}">
                        <svg class="w-6 h-6" :class="{'fill-current': generation.is_favorite}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </button>
                </div>

                <!-- Creation Info -->
                <div class="creation-info">
                    <div class="creation-meta">
                        <span class="type-badge" :class="'type-' + generation.type">
                            <template x-if="generation.type === 'image'">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </template>
                            <template x-if="generation.type === 'video'">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </template>
                            <span x-text="generation.type === 'image' ? 'ภาพ' : 'วิดีโอ'"></span>
                        </span>
                        <span class="creation-date" x-text="formatDate(generation.created_at)"></span>
                    </div>
                    <p class="creation-prompt" x-text="truncate(generation.prompt, 80)"></p>
                    <div class="creation-provider">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                        </svg>
                        <span x-text="generation.provider_name || 'Unknown'"></span>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="loading-state">
        <div class="loading-spinner">
            <svg class="animate-spin h-12 w-12 text-purple-500" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
        <p class="loading-text">กำลังโหลดผลงานของคุณ...</p>
    </div>

    <!-- Pagination -->
    <div x-show="!loading && generations.length > 0" class="pagination-wrapper">
        <button @click="prevPage()"
                :disabled="currentPage === 1"
                class="pagination-btn">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            ก่อนหน้า
        </button>

        <div class="pagination-info">
            หน้า <span x-text="currentPage"></span> จาก <span x-text="totalPages"></span>
        </div>

        <button @click="nextPage()"
                :disabled="currentPage === totalPages"
                class="pagination-btn">
            ถัดไป
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
<style>
    .my-creations-container {
        min-height: 100vh;
    }

    /* Header Section */
    .creations-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 40px 20px;
        margin: -24px -24px 24px;
        border-radius: 0 0 30px 30px;
    }

    .dark .creations-header {
        background: linear-gradient(135deg, #4c1d95 0%, #7c3aed 100%);
    }

    .header-content {
        max-width: 1200px;
        margin: 0 auto;
    }

    .header-title {
        font-size: 2.5rem;
        font-weight: 800;
        color: white;
        display: flex;
        align-items: center;
        margin-bottom: 10px;
    }

    .header-subtitle {
        font-size: 1.125rem;
        color: rgba(255, 255, 255, 0.9);
    }

    .btn-create {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        background: white;
        color: #667eea;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }

    .dark .btn-create {
        background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
        color: white;
    }

    .btn-create:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
    }

    /* Filters Section */
    .filters-section {
        background: white;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 24px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }

    .dark .filters-section {
        background: #1e293b;
    }

    .filters-wrapper {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        align-items: center;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .filter-label {
        font-size: 0.875rem;
        font-weight: 600;
        color: #6b7280;
    }

    .dark .filter-label {
        color: #94a3b8;
    }

    .filter-buttons {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .filter-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        background: #f3f4f6;
        color: #6b7280;
        border-radius: 10px;
        border: 2px solid transparent;
        font-weight: 500;
        font-size: 0.875rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .dark .filter-btn {
        background: #334155;
        color: #94a3b8;
    }

    .filter-btn:hover {
        background: #e5e7eb;
        color: #374151;
    }

    .dark .filter-btn:hover {
        background: #475569;
        color: #e2e8f0;
    }

    .filter-btn.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-color: transparent;
    }

    .dark .filter-btn.active {
        background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
    }

    .favorite-btn.active {
        background: linear-gradient(135deg, #ec4899 0%, #f43f5e 100%);
    }

    .sort-select {
        padding: 8px 16px;
        background: #f3f4f6;
        color: #374151;
        border: 2px solid transparent;
        border-radius: 10px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .dark .sort-select {
        background: #334155;
        color: #e2e8f0;
    }

    /* Stats Bar */
    .stats-bar {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 32px;
    }

    .stat-card {
        display: flex;
        align-items: center;
        gap: 16px;
        background: white;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }

    .dark .stat-card {
        background: #1e293b;
    }

    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .stat-icon {
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        color: white;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 800;
        color: #1f2937;
    }

    .dark .stat-value {
        color: #f1f5f9;
    }

    .stat-label {
        font-size: 0.875rem;
        color: #6b7280;
    }

    .dark .stat-label {
        color: #94a3b8;
    }

    /* Creations Grid */
    .creations-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 24px;
        margin-bottom: 32px;
    }

    .creation-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
    }

    .dark .creation-card {
        background: #1e293b;
    }

    .creation-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 35px rgba(0, 0, 0, 0.2);
    }

    .creation-media {
        position: relative;
        width: 100%;
        padding-bottom: 100%;
        overflow: hidden;
        background: #f3f4f6;
    }

    .dark .creation-media {
        background: #0f172a;
    }

    .media-content {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .video-overlay {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(5px);
        border-radius: 50%;
        padding: 20px;
        transition: all 0.3s ease;
    }

    .creation-card:hover .video-overlay {
        background: rgba(0, 0, 0, 0.7);
        transform: translate(-50%, -50%) scale(1.1);
    }

    .status-badge {
        position: absolute;
        top: 12px;
        left: 12px;
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        backdrop-filter: blur(10px);
    }

    .status-completed {
        background: rgba(16, 185, 129, 0.9);
        color: white;
    }

    .status-processing {
        background: rgba(59, 130, 246, 0.9);
        color: white;
    }

    .status-failed {
        background: rgba(239, 68, 68, 0.9);
        color: white;
    }

    .favorite-icon {
        position: absolute;
        top: 12px;
        right: 12px;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border-radius: 50%;
        color: #6b7280;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .dark .favorite-icon {
        background: rgba(0, 0, 0, 0.5);
        color: #94a3b8;
    }

    .favorite-icon:hover {
        transform: scale(1.1);
    }

    .favorite-icon.active {
        color: #ec4899;
    }

    /* Creation Info */
    .creation-info {
        padding: 16px;
    }

    .creation-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 10px;
    }

    .type-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .type-image {
        background: linear-gradient(135deg, #a78bfa 0%, #8b5cf6 100%);
        color: white;
    }

    .type-video {
        background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
        color: white;
    }

    .creation-date {
        font-size: 0.75rem;
        color: #9ca3af;
    }

    .creation-prompt {
        color: #4b5563;
        font-size: 0.875rem;
        line-height: 1.5;
        margin-bottom: 12px;
        min-height: 42px;
    }

    .dark .creation-prompt {
        color: #cbd5e1;
    }

    .creation-provider {
        display: flex;
        align-items: center;
        gap: 6px;
        color: #9ca3af;
        font-size: 0.75rem;
    }

    /* Empty State */
    .empty-state {
        grid-column: 1 / -1;
        text-align: center;
        padding: 80px 20px;
    }

    .empty-icon {
        color: #d1d5db;
        margin-bottom: 20px;
    }

    .dark .empty-icon {
        color: #475569;
    }

    .empty-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 8px;
    }

    .dark .empty-title {
        color: #f1f5f9;
    }

    .empty-text {
        color: #6b7280;
        font-size: 1.125rem;
    }

    .dark .empty-text {
        color: #94a3b8;
    }

    /* Loading State */
    .loading-state {
        text-align: center;
        padding: 80px 20px;
    }

    .loading-spinner {
        display: flex;
        justify-content: center;
        margin-bottom: 20px;
    }

    .loading-text {
        color: #6b7280;
        font-size: 1.125rem;
    }

    .dark .loading-text {
        color: #94a3b8;
    }

    /* Pagination */
    .pagination-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 20px;
        margin-top: 40px;
    }

    .pagination-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: white;
        color: #667eea;
        border: 2px solid #667eea;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .dark .pagination-btn {
        background: #1e293b;
        border-color: #8b5cf6;
        color: #a78bfa;
    }

    .pagination-btn:hover:not(:disabled) {
        background: #667eea;
        color: white;
    }

    .dark .pagination-btn:hover:not(:disabled) {
        background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
        border-color: transparent;
        color: white;
    }

    .pagination-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .pagination-info {
        font-weight: 600;
        color: #4b5563;
    }

    .dark .pagination-info {
        color: #cbd5e1;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .header-title {
            font-size: 1.75rem;
        }

        .filters-wrapper {
            flex-direction: column;
            align-items: stretch;
        }

        .filter-group {
            width: 100%;
        }

        .filter-group.ml-auto {
            margin-left: 0;
        }

        .creations-grid {
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 16px;
        }

        .stats-bar {
            grid-template-columns: repeat(2, 1fr);
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

    function myCreationsData() {
        return {
            loading: true,
            generations: @json($generations->items() ?? []),
            filters: @json($filters ?? []),
            sortBy: 'newest',
            currentPage: {{ $generations->currentPage() ?? 1 }},
            totalPages: {{ $generations->lastPage() ?? 1 }},
            stats: {
                total_images: {{ $generations->where('type', 'image')->count() ?? 0 }},
                total_videos: {{ $generations->where('type', 'video')->count() ?? 0 }},
                total_favorites: {{ $generations->where('is_favorite', true)->count() ?? 0 }},
                credits_remaining: 100
            },

            init() {
                this.loadGenerations();
            },

            setFilter(type, value) {
                this.filters[type] = value;
                this.currentPage = 1;
                this.loadGenerations();
            },

            toggleFavoriteFilter() {
                this.filters.is_favorite = !this.filters.is_favorite;
                this.currentPage = 1;
                this.loadGenerations();
            },

            async loadGenerations() {
                this.loading = true;

                try {
                    const params = new URLSearchParams();
                    if (this.filters.type) params.append('type', this.filters.type);
                    if (this.filters.status) params.append('status', this.filters.status);
                    if (this.filters.is_favorite) params.append('is_favorite', '1');
                    params.append('sort', this.sortBy);
                    params.append('page', this.currentPage);

                    const response = await fetch(`/user/ai-gen/my-creations?${params}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (response.ok) {
                        window.location.href = `/user/ai-gen/my-creations?${params}`;
                    }
                } catch (error) {
                    console.error('Error loading generations:', error);
                } finally {
                    this.loading = false;
                }
            },

            viewGeneration(generation) {
                window.location.href = `/user/ai-gen/generations/${generation.id}`;
            },

            async toggleFavorite(generation) {
                try {
                    const response = await fetch(`/user/ai-gen/generations/${generation.id}/favorite`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    });

                    if (response.ok) {
                        generation.is_favorite = !generation.is_favorite;
                        this.stats.total_favorites += generation.is_favorite ? 1 : -1;
                    }
                } catch (error) {
                    console.error('Error toggling favorite:', error);
                }
            },

            showCreateModal() {
                window.location.href = '/user/ai-gen';
            },

            prevPage() {
                if (this.currentPage > 1) {
                    this.currentPage--;
                    this.loadGenerations();
                }
            },

            nextPage() {
                if (this.currentPage < this.totalPages) {
                    this.currentPage++;
                    this.loadGenerations();
                }
            },

            getStatusText(status) {
                const statuses = {
                    'completed': 'สำเร็จ',
                    'processing': 'กำลังสร้าง',
                    'pending': 'รอดำเนินการ',
                    'failed': 'ล้มเหลว'
                };
                return statuses[status] || status;
            },

            formatDate(dateString) {
                const date = new Date(dateString);
                const now = new Date();
                const diff = now - date;
                const minutes = Math.floor(diff / 60000);
                const hours = Math.floor(diff / 3600000);
                const days = Math.floor(diff / 86400000);

                if (minutes < 1) return 'เมื่อสักครู่';
                if (minutes < 60) return `${minutes} นาทีที่แล้ว`;
                if (hours < 24) return `${hours} ชั่วโมงที่แล้ว`;
                if (days < 7) return `${days} วันที่แล้ว`;
                return date.toLocaleDateString('th-TH');
            },

            truncate(text, length) {
                if (!text) return '';
                return text.length > length ? text.substring(0, length) + '...' : text;
            }
        }
    }
</script>
@endpush
