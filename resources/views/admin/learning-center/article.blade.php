@extends('layouts.admin-v3')

@section('title', $article->title . ' - Academy')

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

body {
    background: var(--gray-50);
}

/* Course Layout */
.course-container {
    display: flex;
    min-height: calc(100vh - 100px);
    gap: 0;
}

.course-sidebar {
    width: 380px;
    background: white;
    border-right: 1px solid var(--gray-200);
    overflow-y: auto;
    position: sticky;
    top: 0;
    height: calc(100vh - 100px);
}

.course-main {
    flex: 1;
    background: var(--gray-900);
    position: relative;
}

.course-content-area {
    background: white;
    padding: 0;
}

/* Video Player */
.video-container {
    position: relative;
    width: 100%;
    padding-top: 56.25%; /* 16:9 Aspect Ratio */
    background: #000;
}

.video-player {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

.video-placeholder {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.video-placeholder-content {
    text-align: center;
}

.play-button {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 3px solid white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.play-button:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: scale(1.1);
}

.play-button i {
    font-size: 32px;
    margin-left: 4px;
}

/* Custom Video Controls */
.custom-video-controls {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
    padding: 40px 20px 20px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.video-container:hover .custom-video-controls {
    opacity: 1;
}

.video-progress-bar {
    width: 100%;
    height: 6px;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 3px;
    margin-bottom: 15px;
    cursor: pointer;
    position: relative;
}

.video-progress-filled {
    height: 100%;
    background: var(--primary-color);
    border-radius: 3px;
    position: relative;
}

.video-progress-handle {
    position: absolute;
    right: -6px;
    top: 50%;
    transform: translateY(-50%);
    width: 12px;
    height: 12px;
    background: white;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.video-controls-buttons {
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: white;
}

.video-controls-left,
.video-controls-right {
    display: flex;
    align-items: center;
    gap: 15px;
}

.video-control-btn {
    background: none;
    border: none;
    color: white;
    font-size: 18px;
    cursor: pointer;
    padding: 8px;
    border-radius: 4px;
    transition: background 0.2s;
}

.video-control-btn:hover {
    background: rgba(255, 255, 255, 0.1);
}

.video-time {
    font-size: 14px;
}

/* Sidebar Styling */
.sidebar-header {
    padding: 24px;
    border-bottom: 1px solid var(--gray-200);
}

.course-title {
    font-size: 18px;
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 12px;
}

.course-progress-section {
    margin-top: 16px;
}

.progress-label {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 13px;
    font-weight: 600;
    color: var(--gray-700);
}

.progress-bar-wrapper {
    background: var(--gray-200);
    border-radius: 8px;
    height: 10px;
    overflow: hidden;
}

.progress-bar-fill {
    background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
    height: 100%;
    border-radius: 8px;
    transition: width 0.5s ease;
}

/* Lesson List */
.lesson-list {
    padding: 0;
}

.lesson-item {
    padding: 16px 24px;
    border-bottom: 1px solid var(--gray-100);
    cursor: pointer;
    transition: background 0.2s;
    display: flex;
    align-items: center;
    gap: 12px;
}

.lesson-item:hover {
    background: var(--gray-50);
}

.lesson-item.active {
    background: var(--primary-color);
    color: white;
}

.lesson-item.active .lesson-title {
    color: white;
}

.lesson-item.active .lesson-duration {
    color: rgba(255, 255, 255, 0.8);
}

.lesson-number {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--gray-100);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    flex-shrink: 0;
}

.lesson-item.active .lesson-number {
    background: rgba(255, 255, 255, 0.2);
}

.lesson-item.completed .lesson-number {
    background: var(--success-color);
    color: white;
}

.lesson-info {
    flex: 1;
}

.lesson-title {
    font-size: 14px;
    font-weight: 600;
    color: var(--gray-900);
    margin-bottom: 4px;
}

.lesson-duration {
    font-size: 12px;
    color: var(--gray-600);
}

.lesson-status {
    font-size: 16px;
}

/* Content Area */
.content-section {
    padding: 32px;
}

.content-header {
    margin-bottom: 24px;
}

.content-title {
    font-size: 28px;
    font-weight: 800;
    color: var(--gray-900);
    margin-bottom: 16px;
}

.content-meta {
    display: flex;
    align-items: center;
    gap: 24px;
    flex-wrap: wrap;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--gray-600);
    font-size: 14px;
}

.meta-item i {
    color: var(--primary-color);
}

/* Tabs */
.content-tabs {
    display: flex;
    gap: 8px;
    border-bottom: 2px solid var(--gray-200);
    margin-bottom: 24px;
}

.content-tab {
    padding: 12px 24px;
    background: none;
    border: none;
    color: var(--gray-600);
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    position: relative;
    transition: color 0.2s;
}

.content-tab:hover {
    color: var(--primary-color);
}

.content-tab.active {
    color: var(--primary-color);
}

.content-tab.active::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    right: 0;
    height: 2px;
    background: var(--primary-color);
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* Article Content Styling */
.article-content {
    font-size: 16px;
    line-height: 1.8;
    color: var(--gray-700);
}

.article-content h2 {
    font-size: 24px;
    font-weight: 700;
    color: var(--gray-900);
    margin-top: 32px;
    margin-bottom: 16px;
}

.article-content h3 {
    font-size: 20px;
    font-weight: 600;
    color: var(--gray-900);
    margin-top: 24px;
    margin-bottom: 12px;
}

.article-content p {
    margin-bottom: 16px;
}

.article-content ul,
.article-content ol {
    margin-left: 24px;
    margin-bottom: 16px;
}

.article-content li {
    margin-bottom: 8px;
}

.article-content code {
    background: var(--gray-100);
    padding: 2px 8px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    font-size: 14px;
}

.article-content pre {
    background: var(--gray-900);
    color: white;
    padding: 20px;
    border-radius: 8px;
    overflow-x: auto;
    margin-bottom: 16px;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 12px;
    margin-top: 32px;
    padding-top: 32px;
    border-top: 1px solid var(--gray-200);
}

.btn-primary {
    padding: 14px 32px;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
}

.btn-secondary {
    padding: 14px 32px;
    background: white;
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-secondary:hover {
    background: var(--primary-color);
    color: white;
}

/* Notes Section */
.notes-area {
    margin-top: 24px;
}

.note-input {
    width: 100%;
    min-height: 120px;
    padding: 16px;
    border: 2px solid var(--gray-200);
    border-radius: 8px;
    font-size: 15px;
    resize: vertical;
    font-family: inherit;
}

.note-input:focus {
    outline: none;
    border-color: var(--primary-color);
}

/* Responsive */
@media (max-width: 1024px) {
    .course-sidebar {
        width: 320px;
    }
}

@media (max-width: 768px) {
    .course-container {
        flex-direction: column;
    }

    .course-sidebar {
        width: 100%;
        height: auto;
        position: relative;
    }

    .content-section {
        padding: 20px;
    }

    .content-title {
        font-size: 22px;
    }
}
</style>
@endpush

@section('content')
<div class="course-container">
    <!-- Sidebar - Course Navigation -->
    <div class="course-sidebar">
        <div class="sidebar-header">
            <a href="{{ route('admin.learning-center.index') }}" class="text-muted" style="font-size: 14px; text-decoration: none;">
                <i class="fas fa-arrow-left me-2"></i>กลับไปยังศูนย์เรียนรู้
            </a>

            <h1 class="course-title mt-3">{{ $article->title }}</h1>

            <div class="d-flex align-items-center gap-2 text-muted" style="font-size: 13px;">
                <span><i class="fas fa-layer-group me-1"></i>{{ $article->category->name }}</span>
                <span>•</span>
                <span><i class="far fa-clock me-1"></i>{{ $article->formatted_duration }}</span>
            </div>

            <!-- Progress -->
            <div class="course-progress-section">
                <div class="progress-label">
                    <span>ความก้าวหน้า</span>
                    <span>{{ $progress->progress_percentage }}%</span>
                </div>
                <div class="progress-bar-wrapper">
                    <div class="progress-bar-fill" style="width: {{ $progress->progress_percentage }}%"></div>
                </div>
            </div>
        </div>

        <!-- Lesson List -->
        <div class="lesson-list">
            @php
                $lessons = [
                    ['title' => 'บทนำและภาพรวม', 'duration' => '10 นาที', 'completed' => true],
                    ['title' => $article->title, 'duration' => $article->formatted_duration, 'active' => true],
                    ['title' => 'แบบฝึกหัดและทดสอบ', 'duration' => '15 นาที', 'locked' => false],
                    ['title' => 'สรุปและแนวทางต่อไป', 'duration' => '8 นาที', 'locked' => false],
                ];
            @endphp

            @foreach($lessons as $index => $lesson)
            <div class="lesson-item {{ $lesson['active'] ?? false ? 'active' : '' }} {{ $lesson['completed'] ?? false ? 'completed' : '' }}">
                <div class="lesson-number">
                    @if($lesson['completed'] ?? false)
                        <i class="fas fa-check"></i>
                    @else
                        {{ $index + 1 }}
                    @endif
                </div>
                <div class="lesson-info">
                    <div class="lesson-title">{{ $lesson['title'] }}</div>
                    <div class="lesson-duration">{{ $lesson['duration'] }}</div>
                </div>
                @if($lesson['locked'] ?? false)
                    <div class="lesson-status text-muted">
                        <i class="fas fa-lock"></i>
                    </div>
                @elseif($lesson['active'] ?? false)
                    <div class="lesson-status">
                        <i class="fas fa-play-circle"></i>
                    </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    <!-- Main Content -->
    <div class="course-main">
        <!-- Video Player -->
        @if($article->video_url)
        <div class="video-container">
            <div class="video-player">
                <iframe
                    src="{{ $article->video_url }}"
                    frameborder="0"
                    allowfullscreen
                    style="width: 100%; height: 100%;"
                ></iframe>
            </div>

            <!-- Custom Video Controls (Optional - for native videos) -->
            <div class="custom-video-controls d-none">
                <div class="video-progress-bar">
                    <div class="video-progress-filled" style="width: 45%;">
                        <div class="video-progress-handle"></div>
                    </div>
                </div>
                <div class="video-controls-buttons">
                    <div class="video-controls-left">
                        <button class="video-control-btn">
                            <i class="fas fa-play"></i>
                        </button>
                        <button class="video-control-btn">
                            <i class="fas fa-volume-up"></i>
                        </button>
                        <span class="video-time">05:32 / 12:18</span>
                    </div>
                    <div class="video-controls-right">
                        <button class="video-control-btn">
                            <i class="fas fa-cog"></i>
                        </button>
                        <button class="video-control-btn">
                            <i class="fas fa-expand"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @else
        <div class="video-container">
            <div class="video-placeholder">
                <div class="video-placeholder-content">
                    <div class="play-button">
                        <i class="fas fa-play"></i>
                    </div>
                    <h3 style="font-size: 24px; font-weight: 700; margin-bottom: 8px;">พร้อมเริ่มเรียนแล้ว!</h3>
                    <p style="opacity: 0.9;">คอร์สนี้ยังไม่มีวิดีโอ กรุณาอ่านเนื้อหาด้านล่าง</p>
                </div>
            </div>
        </div>
        @endif

        <!-- Content Area -->
        <div class="course-content-area">
            <div class="content-section">
                <div class="content-header">
                    <h2 class="content-title">{{ $article->title }}</h2>
                    <div class="content-meta">
                        <div class="meta-item">
                            <i class="fas fa-user"></i>
                            <span>ทีมงาน ThaiPrompt</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-calendar"></i>
                            <span>อัพเดตล่าสุด {{ $article->updated_at->format('d/m/Y') }}</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-eye"></i>
                            <span>{{ number_format($article->views) }} views</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-signal"></i>
                            <span class="badge" style="background: #10b981; color: white; padding: 4px 12px; border-radius: 4px;">
                                {{ ucfirst($article->difficulty) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="content-tabs">
                    <button class="content-tab active" data-tab="overview">
                        <i class="fas fa-book me-2"></i>ภาพรวม
                    </button>
                    <button class="content-tab" data-tab="content">
                        <i class="fas fa-file-alt me-2"></i>เนื้อหา
                    </button>
                    <button class="content-tab" data-tab="notes">
                        <i class="fas fa-sticky-note me-2"></i>บันทึกของฉัน
                    </button>
                    <button class="content-tab" data-tab="resources">
                        <i class="fas fa-download me-2"></i>ไฟล์ดาวน์โหลด
                    </button>
                </div>

                <!-- Tab Contents -->
                <div class="tab-content active" id="overview">
                    <div class="article-content">
                        <h2>เกี่ยวกับคอร์สนี้</h2>
                        <p>{{ $article->excerpt }}</p>

                        <h3>สิ่งที่คุณจะได้เรียนรู้</h3>
                        <ul>
                            <li>เข้าใจหลักการพื้นฐานอย่างถ่องแท้</li>
                            <li>สามารถนำไปประยุกต์ใช้กับงานจริงได้ทันที</li>
                            <li>เทคนิคและ best practices จากผู้เชี่ยวชาญ</li>
                            <li>กรณีศึกษาและตัวอย่างจากการใช้งานจริง</li>
                        </ul>

                        @if($article->prerequisites->count() > 0)
                        <h3>ความรู้ที่จำเป็นก่อนเรียน</h3>
                        <ul>
                            @foreach($article->prerequisites as $prereq)
                            <li>
                                <a href="{{ route('admin.learning-center.article', $prereq->slug) }}" class="text-primary">
                                    {{ $prereq->title }}
                                </a>
                            </li>
                            @endforeach
                        </ul>
                        @endif
                    </div>
                </div>

                <div class="tab-content" id="content">
                    <div class="article-content">
                        {!! $article->content !!}
                    </div>
                </div>

                <div class="tab-content" id="notes">
                    <div class="notes-area">
                        <h3>บันทึกของฉัน</h3>
                        <p class="text-muted">จดบันทึกสิ่งที่คุณเรียนรู้ได้ที่นี่</p>
                        <textarea class="note-input" placeholder="พิมพ์บันทึกของคุณที่นี่..."></textarea>
                        <button class="btn-primary mt-3">
                            <i class="fas fa-save me-2"></i>บันทึก
                        </button>
                    </div>
                </div>

                <div class="tab-content" id="resources">
                    <div class="article-content">
                        <h3>ไฟล์และเอกสารดาวน์โหลด</h3>
                        <p class="text-muted">ไฟล์เสริมสำหรับคอร์สนี้</p>

                        <div class="list-group mt-3">
                            <a href="#" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="fas fa-file-pdf fa-2x text-danger"></i>
                                    <div>
                                        <div class="fw-bold">สไลด์ประกอบการสอน.pdf</div>
                                        <div class="small text-muted">2.5 MB</div>
                                    </div>
                                </div>
                                <i class="fas fa-download"></i>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="fas fa-file-code fa-2x text-primary"></i>
                                    <div>
                                        <div class="fw-bold">ตัวอย่างโค้ด.zip</div>
                                        <div class="small text-muted">1.2 MB</div>
                                    </div>
                                </div>
                                <i class="fas fa-download"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button class="btn-primary" id="complete-lesson">
                        <i class="fas fa-check-circle me-2"></i>ทำเครื่องหมายว่าเรียนจบ
                    </button>
                    <button class="btn-secondary">
                        <i class="fas fa-arrow-right me-2"></i>บทเรียนถัดไป
                    </button>
                </div>

                <!-- Related Articles -->
                @if($relatedArticles->count() > 0)
                <div class="mt-5 pt-4" style="border-top: 1px solid var(--gray-200);">
                    <h3 class="mb-4" style="font-size: 20px; font-weight: 700;">บทเรียนที่เกี่ยวข้อง</h3>
                    <div class="row g-3">
                        @foreach($relatedArticles as $related)
                        <div class="col-md-4">
                            <a href="{{ route('admin.learning-center.article', $related->slug) }}" class="text-decoration-none">
                                <div class="card h-100 border" style="transition: all 0.2s;">
                                    <div class="card-body">
                                        <div class="badge bg-primary mb-2">{{ $related->category->name }}</div>
                                        <h5 class="card-title" style="font-size: 16px; font-weight: 600;">{{ $related->title }}</h5>
                                        <p class="card-text text-muted small">{{ Str::limit($related->excerpt, 100) }}</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Tab switching
    $('.content-tab').on('click', function() {
        const tabName = $(this).data('tab');

        $('.content-tab').removeClass('active');
        $(this).addClass('active');

        $('.tab-content').removeClass('active');
        $(`#${tabName}`).addClass('active');
    });

    // Complete lesson
    $('#complete-lesson').on('click', function() {
        const articleSlug = '{{ $article->slug }}';

        $.ajax({
            url: `/admin/learning-center/article/${articleSlug}/complete`,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    // Update progress bar
                    $('.progress-bar-fill').css('width', '100%');
                    $('.progress-label span:last-child').text('100%');

                    // Show success message
                    alert('ยินดีด้วย! คุณเรียนจบบทเรียนนี้แล้ว 🎉');

                    // Optionally reload the page
                    location.reload();
                }
            },
            error: function(xhr) {
                alert('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง');
            }
        });
    });

    // Auto-update progress as user scrolls
    let progressInterval;
    $(window).on('scroll', function() {
        clearTimeout(progressInterval);
        progressInterval = setTimeout(function() {
            updateProgress();
        }, 2000);
    });

    function updateProgress() {
        const scrollTop = $(window).scrollTop();
        const docHeight = $(document).height() - $(window).height();
        const scrollPercent = Math.round((scrollTop / docHeight) * 100);

        if (scrollPercent > 0 && scrollPercent <= 100) {
            const articleSlug = '{{ $article->slug }}';

            $.ajax({
                url: `/admin/learning-center/article/${articleSlug}/progress`,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    progress: scrollPercent
                },
                success: function(response) {
                    // Update UI
                    $('.progress-bar-fill').css('width', scrollPercent + '%');
                    $('.progress-label span:last-child').text(scrollPercent + '%');
                }
            });
        }
    }

    // Video player tracking
    // TODO: Implement video progress tracking when using custom player
});
</script>
@endpush
