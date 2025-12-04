{{--
/**
 * Presentation Slides Modal Only
 *
 * คอมโพเนนต์แยกเฉพาะส่วน Modal และ Scripts ของ Presentation Slides
 * ใช้เมื่อหน้าเว็บมี section แสดง CTA เองแล้ว ไม่ต้องการ section ซ้ำ
 *
 * @version 1.0.0
 * @author Thaiprompt Team
 * @see components/presentation-slides.blade.php สำหรับเวอร์ชันเต็มที่มี section
 */
--}}

@php
    $appName = \App\Models\Setting::get('app_name', 'TP-Affiliate Pro');
    $logo = \App\Models\Setting::get('logo');
@endphp

<!-- Fullscreen Presentation Modal -->
<div id="presentation-fullscreen" class="fixed inset-0 bg-black z-[200] hidden">
    <!-- Logo Watermark Overlay -->
    <div class="absolute top-8 left-8 z-20 opacity-20 pointer-events-none">
        @if($logo)
            <img src="{{ asset($logo) }}" alt="{{ $appName }}" class="w-20 h-20 object-contain filter drop-shadow-lg">
        @else
            <div class="w-20 h-20 bg-gradient-to-br from-blue-600 to-purple-600 rounded-2xl flex items-center justify-center">
                <span class="text-white font-bold text-2xl">TP</span>
            </div>
        @endif
    </div>

    <!-- Control Panel -->
    <div class="absolute top-0 left-0 right-0 bg-gradient-to-b from-black/80 to-transparent p-6 z-30 transition-opacity duration-300" id="control-panel">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-4">
                <h3 class="text-white text-xl font-bold">{{ $appName }} - ระบบแพลตฟอร์มครบวงจร</h3>

                <!-- Topic Selector -->
                <div class="relative">
                    <button onclick="toggleTopicMenu()" class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 backdrop-blur-sm rounded-lg text-white transition-all shadow-lg" id="topic-selector-btn">
                        <span class="font-semibold" id="current-topic-name">ภาพรวมระบบ</span>
                        <svg class="w-5 h-5 transition-transform" id="topic-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <!-- Dropdown Menu -->
                    <div id="topic-menu" class="hidden absolute top-full mt-2 left-0 bg-gray-900/95 backdrop-blur-md rounded-xl shadow-2xl border border-white/20 overflow-hidden min-w-[280px]">
                        <div class="p-2 space-y-1">
                            <button onclick="switchTopic('system-overview')" class="topic-option active flex items-center gap-3 w-full px-4 py-3 rounded-lg hover:bg-white/10 transition-colors text-left" data-topic="system-overview">
                                <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center text-xl">
                                    <i class="fas fa-desktop"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-white font-semibold">ภาพรวมระบบ</p>
                                    <p class="text-white/60 text-xs">System Overview</p>
                                </div>
                            </button>
                            <button onclick="switchTopic('mlm-plans')" class="topic-option flex items-center gap-3 w-full px-4 py-3 rounded-lg hover:bg-white/10 transition-colors text-left" data-topic="mlm-plans">
                                <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-600 rounded-lg flex items-center justify-center text-xl">
                                    <i class="fas fa-sitemap"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-white font-semibold">แผนการตลาด MLM</p>
                                    <p class="text-white/60 text-xs">Unilevel & Binary Plans</p>
                                </div>
                            </button>
                            <button onclick="switchTopic('ai-automation')" class="topic-option flex items-center gap-3 w-full px-4 py-3 rounded-lg hover:bg-white/10 transition-colors text-left" data-topic="ai-automation">
                                <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-lg flex items-center justify-center text-xl">
                                    <i class="fas fa-robot"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-white font-semibold">AI & Automation</p>
                                    <p class="text-white/60 text-xs">AI Chatbot, LINE Bot & Marketing</p>
                                </div>
                            </button>
                            <button onclick="switchTopic('tpix-token')" class="topic-option flex items-center gap-3 w-full px-4 py-3 rounded-lg hover:bg-white/10 transition-colors text-left" data-topic="tpix-token">
                                <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-amber-500 to-orange-600 rounded-lg flex items-center justify-center text-xl overflow-hidden">
                                    <img src="{{ asset('images/tpix-logo.svg') }}" alt="TPIX" class="w-7 h-7 object-contain">
                                </div>
                                <div class="flex-1">
                                    <p class="text-white font-semibold">TPIX Token</p>
                                    <p class="text-white/60 text-xs">Blockchain, Staking & DEX</p>
                                </div>
                            </button>
                            <button onclick="switchTopic('academy-freedom')" class="topic-option flex items-center gap-3 w-full px-4 py-3 rounded-lg hover:bg-white/10 transition-colors text-left" data-topic="academy-freedom">
                                <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-rose-500 to-orange-600 rounded-lg flex items-center justify-center text-xl">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-white font-semibold">Academy & ปลดแอกธุรกิจ</p>
                                    <p class="text-white/60 text-xs">การเรียนรู้ & อิสรภาพทางธุรกิจ</p>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-white text-sm" id="slide-counter">
                    1 / 15
                </span>
            </div>
            <div class="flex items-center gap-4">
                <!-- Settings Button -->
                <button onclick="toggleSettings()" class="p-2 bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-lg text-white transition-colors" title="ตั้งค่า">
                    <i class="fas fa-cog text-xl"></i>
                </button>
                <!-- Auto-play Toggle -->
                <button onclick="toggleAutoplay()" id="autoplay-btn" class="p-2 bg-emerald-600 hover:bg-emerald-700 backdrop-blur-sm rounded-lg text-white transition-colors" title="Auto-play">
                    <i class="fas fa-play text-xl"></i>
                </button>
                <!-- Close Button -->
                <button onclick="closePresentationFullscreen()" class="p-2 bg-red-600 hover:bg-red-700 backdrop-blur-sm rounded-lg text-white transition-colors" title="ปิด">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Settings Panel -->
    <div id="settings-panel" class="absolute top-20 right-6 bg-white/95 backdrop-blur-sm rounded-2xl shadow-2xl p-6 z-30 hidden min-w-[300px]">
        <h4 class="text-lg font-bold text-gray-900 mb-4"><i class="fas fa-cog mr-2"></i>ตั้งค่าการนำเสนอ</h4>

        <div class="space-y-4">
            <!-- Auto-advance delay -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">เวลาแต่ละสไลด์ (วินาที)</label>
                <input type="range" id="slide-duration" min="5" max="60" value="15" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer" oninput="updateSlideDuration(this.value)">
                <div class="flex justify-between text-xs text-gray-500 mt-1">
                    <span>5s</span>
                    <span id="duration-value" class="font-bold text-indigo-600">15s</span>
                    <span>60s</span>
                </div>
            </div>

            <!-- Countdown visibility -->
            <div class="flex items-center justify-between">
                <label class="text-sm font-medium text-gray-700">แสดงตัวนับถอยหลัง</label>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="show-countdown" class="sr-only peer" checked onchange="toggleCountdownVisibility()">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                </label>
            </div>

            <!-- Loop slides -->
            <div class="flex items-center justify-between">
                <label class="text-sm font-medium text-gray-700">วนลูปอัตโนมัติ</label>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="loop-slides" class="sr-only peer" checked onchange="toggleLoop()">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                </label>
            </div>
        </div>

        <button onclick="toggleSettings()" class="mt-4 w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors">
            บันทึก
        </button>
    </div>

    <!-- Slides Container -->
    <div class="relative h-full flex items-center justify-center" id="slides-container">
        <div id="slides-system-overview" class="slides-topic-container active">
            @include('components.presentation-slides-content')
        </div>
        <div id="slides-mlm-plans" class="slides-topic-container">
            @include('components.presentation-slides-mlm-plans')
        </div>
    </div>

    <!-- Navigation Controls -->
    <div class="absolute bottom-8 left-0 right-0 z-30">
        <div class="max-w-7xl mx-auto px-6 flex items-center justify-between">
            <!-- Previous Button -->
            <button onclick="previousSlide()" class="group flex items-center gap-2 px-6 py-3 bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-xl text-white transition-all">
                <i class="fas fa-chevron-left text-xl group-hover:-translate-x-1 transition-transform"></i>
                <span class="font-medium">ย้อนกลับ</span>
            </button>

            <!-- Progress Dots -->
            <div class="flex gap-2" id="progress-dots">
                <!-- Dots will be generated by JavaScript -->
            </div>

            <!-- Next Button -->
            <button onclick="nextSlide()" class="group flex items-center gap-2 px-6 py-3 bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-xl text-white transition-all">
                <span class="font-medium">ถัดไป</span>
                <i class="fas fa-chevron-right text-xl group-hover:translate-x-1 transition-transform"></i>
            </button>
        </div>
    </div>

    <!-- Countdown Timer -->
    <div id="countdown-timer" class="absolute bottom-24 left-8 z-30" style="display: none;">
        <div class="flex items-center gap-3 px-6 py-3 bg-white/10 backdrop-blur-sm rounded-full border border-white/20">
            <i class="fas fa-spinner fa-spin text-white"></i>
            <span class="text-white font-bold text-lg">สไลด์ถัดไปใน <span id="countdown-value">15</span> วินาที</span>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Slide animations */
    .slide {
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        overflow-y: auto;
    }

    .slide.active {
        opacity: 1;
        transform: translateX(0);
        position: relative;
    }

    .slide.prev {
        transform: translateX(-100%);
    }

    /* Fullscreen responsive fixes */
    #presentation-fullscreen {
        width: 100vw;
        height: 100vh;
        max-width: 100vw;
        max-height: 100vh;
    }

    /* Progress dots */
    .progress-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transition: all 0.3s;
        cursor: pointer;
    }

    .progress-dot.active {
        background: white;
        width: 32px;
        border-radius: 6px;
    }

    .progress-dot:hover {
        background: rgba(255, 255, 255, 0.6);
    }

    /* Topic Selector */
    .topic-option.active {
        background: rgba(255, 255, 255, 0.1);
    }

    .slides-topic-container {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.5s ease;
    }

    .slides-topic-container.active {
        opacity: 1;
        pointer-events: auto;
        position: relative;
    }

    #topic-arrow.rotated {
        transform: rotate(180deg);
    }

    /* Auto-hide toolbar styles เมื่อ autoplay */
    #presentation-fullscreen.toolbar-hidden #control-panel {
        opacity: 0;
        transform: translateY(-100%);
        pointer-events: none;
    }

    #presentation-fullscreen.toolbar-hidden .absolute.bottom-8 {
        opacity: 0;
        transform: translateY(100%);
        pointer-events: none;
    }

    #presentation-fullscreen.toolbar-hidden #countdown-timer {
        opacity: 0;
        transform: translateY(100%);
        pointer-events: none;
    }

    #control-panel,
    .absolute.bottom-8,
    #countdown-timer {
        transition: opacity 0.5s ease, transform 0.5s ease;
    }

    /* เมื่อ hover หรือคลิก ให้แสดง toolbar ชั่วคราว */
    #presentation-fullscreen.toolbar-hidden.toolbar-peek #control-panel {
        opacity: 1;
        transform: translateY(0);
        pointer-events: auto;
    }

    #presentation-fullscreen.toolbar-hidden.toolbar-peek .absolute.bottom-8 {
        opacity: 1;
        transform: translateY(0);
        pointer-events: auto;
    }

    #presentation-fullscreen.toolbar-hidden.toolbar-peek #countdown-timer {
        opacity: 1;
        transform: translateY(0);
        pointer-events: auto;
    }
</style>
@endpush

@push('scripts')
<script>
let currentSlide = 0;
let autoplayInterval = null;
let countdownInterval = null;
let slideDuration = 15;
let isAutoplayActive = false;
let showCountdown = true;
let loopSlides = true;
let currentTopic = 'system-overview';
let toolbarHideTimeout = null;
let autoHideToolbar = true;  // เปิดใช้งาน auto-hide เมื่อ autoplay
const TOOLBAR_HIDE_DELAY = 3000;  // ซ่อน toolbar หลังจาก 3 วินาที

let slides = document.querySelectorAll('#slides-system-overview .slide');
let totalSlides = slides.length;

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    initializeProgressDots();
    updateSlideCounter();
    updateTopicDisplay();
});

function toggleTopicMenu() {
    const menu = document.getElementById('topic-menu');
    const arrow = document.getElementById('topic-arrow');
    menu.classList.toggle('hidden');
    arrow.classList.toggle('rotated');
}

// Mapping topics ไปยัง slide index ใน system-overview
const topicSlideMapping = {
    'system-overview': 0,
    'ai-automation': 7,      // Slide 5: AI & Automation
    'tpix-token': 11,        // Slide 6: TPIX Token
    'academy-freedom': 15    // Slide 7: Academy & ปลดแอกธุรกิจ
};

function switchTopic(topicId) {
    if (currentTopic === topicId) {
        toggleTopicMenu();
        return;
    }

    if (isAutoplayActive) {
        stopAutoplay();
    }

    // ถ้าเป็น AI, TPIX หรือ Academy ใช้ system-overview container แต่ jump ไป slide ที่เกี่ยวข้อง
    let containerId = topicId;
    let startSlide = 0;

    if (topicId === 'ai-automation' || topicId === 'tpix-token' || topicId === 'academy-freedom') {
        containerId = 'system-overview';
        startSlide = topicSlideMapping[topicId] || 0;
    }

    document.querySelectorAll('.slides-topic-container').forEach(container => {
        container.classList.remove('active');
    });

    const selectedContainer = document.getElementById('slides-' + containerId);
    selectedContainer.classList.add('active');

    document.querySelectorAll('.topic-option').forEach(option => {
        option.classList.remove('active');
    });
    document.querySelector(`.topic-option[data-topic="${topicId}"]`).classList.add('active');

    currentTopic = topicId;
    slides = document.querySelectorAll(`#slides-${containerId} .slide`);
    totalSlides = slides.length;

    showSlide(startSlide);
    initializeProgressDots();
    updateSlideCounter();
    updateTopicDisplay();
    toggleTopicMenu();
}

// เปิด presentation พร้อมเลือก topic (สำหรับเรียกจากการ์ด)
function openPresentationWithTopic(topicId) {
    const fullscreen = document.getElementById('presentation-fullscreen');
    fullscreen.classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    const requestFullscreen = fullscreen.requestFullscreen ||
                             fullscreen.webkitRequestFullscreen ||
                             fullscreen.mozRequestFullScreen ||
                             fullscreen.msRequestFullscreen;

    if (requestFullscreen) {
        requestFullscreen.call(fullscreen).catch(err => {
            console.log('Fullscreen request failed:', err);
        });
    }

    setTimeout(() => {
        window.dispatchEvent(new Event('resize'));
    }, 100);

    // เรียก switchTopic โดยไม่ toggle menu
    if (isAutoplayActive) {
        stopAutoplay();
    }

    let containerId = topicId;
    let startSlide = 0;

    if (topicId === 'ai-automation' || topicId === 'tpix-token' || topicId === 'academy-freedom') {
        containerId = 'system-overview';
        startSlide = topicSlideMapping[topicId] || 0;
    }

    document.querySelectorAll('.slides-topic-container').forEach(container => {
        container.classList.remove('active');
    });

    const selectedContainer = document.getElementById('slides-' + containerId);
    selectedContainer.classList.add('active');

    document.querySelectorAll('.topic-option').forEach(option => {
        option.classList.remove('active');
    });
    const topicOption = document.querySelector(`.topic-option[data-topic="${topicId}"]`);
    if (topicOption) topicOption.classList.add('active');

    currentTopic = topicId;
    slides = document.querySelectorAll(`#slides-${containerId} .slide`);
    totalSlides = slides.length;

    showSlide(startSlide);
    initializeProgressDots();
    updateSlideCounter();
    updateTopicDisplay();
}

function updateTopicDisplay() {
    const topicNames = {
        'system-overview': 'ภาพรวมระบบ',
        'mlm-plans': 'แผนการตลาด MLM',
        'ai-automation': 'AI & Automation',
        'tpix-token': 'TPIX Token',
        'academy-freedom': 'Academy & ปลดแอกธุรกิจ'
    };
    document.getElementById('current-topic-name').textContent = topicNames[currentTopic] || 'ภาพรวมระบบ';
}

function openPresentationFullscreen() {
    const fullscreen = document.getElementById('presentation-fullscreen');
    fullscreen.classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    const requestFullscreen = fullscreen.requestFullscreen ||
                             fullscreen.webkitRequestFullscreen ||
                             fullscreen.mozRequestFullScreen ||
                             fullscreen.msRequestFullscreen;

    if (requestFullscreen) {
        requestFullscreen.call(fullscreen).catch(err => {
            console.log('Fullscreen request failed:', err);
        });
    }

    setTimeout(() => {
        window.dispatchEvent(new Event('resize'));
    }, 100);

    showSlide(0);
}

function closePresentationFullscreen() {
    const fullscreen = document.getElementById('presentation-fullscreen');
    fullscreen.classList.add('hidden');
    document.body.style.overflow = 'auto';

    const exitFullscreen = document.exitFullscreen ||
                          document.webkitExitFullscreen ||
                          document.mozCancelFullScreen ||
                          document.msExitFullscreen;

    if (exitFullscreen) {
        exitFullscreen.call(document).catch(err => {
            console.log('Exit fullscreen failed:', err);
        });
    }

    stopAutoplay();
}

function showSlide(index) {
    slides.forEach((slide, i) => {
        slide.classList.remove('active', 'prev');
        if (i < index) {
            slide.classList.add('prev');
        }
    });

    slides[index].classList.add('active');
    currentSlide = index;

    updateProgressDots();
    updateSlideCounter();

    if (isAutoplayActive) {
        resetCountdown();
    }
}

function nextSlide() {
    let nextIndex = currentSlide + 1;
    if (nextIndex >= totalSlides) {
        if (loopSlides) {
            nextIndex = 0;
        } else {
            stopAutoplay();
            return;
        }
    }
    showSlide(nextIndex);
}

function previousSlide() {
    let prevIndex = currentSlide - 1;
    if (prevIndex < 0) {
        prevIndex = totalSlides - 1;
    }
    showSlide(prevIndex);
}

function initializeProgressDots() {
    const dotsContainer = document.getElementById('progress-dots');
    dotsContainer.innerHTML = '';

    for (let i = 0; i < totalSlides; i++) {
        const dot = document.createElement('div');
        dot.className = 'progress-dot';
        if (i === 0) dot.classList.add('active');
        dot.onclick = () => showSlide(i);
        dotsContainer.appendChild(dot);
    }
}

function updateProgressDots() {
    const dots = document.querySelectorAll('.progress-dot');
    dots.forEach((dot, i) => {
        if (i === currentSlide) {
            dot.classList.add('active');
        } else {
            dot.classList.remove('active');
        }
    });
}

function updateSlideCounter() {
    const counter = document.getElementById('slide-counter');
    if (counter) {
        counter.textContent = `${currentSlide + 1} / ${totalSlides}`;
    }
}

function toggleAutoplay() {
    if (isAutoplayActive) {
        stopAutoplay();
    } else {
        startAutoplay();
    }
}

function startAutoplay() {
    isAutoplayActive = true;
    const btn = document.getElementById('autoplay-btn');
    btn.classList.remove('bg-emerald-600', 'hover:bg-emerald-700');
    btn.classList.add('bg-red-600', 'hover:bg-red-700');
    btn.innerHTML = '<i class="fas fa-pause text-xl"></i>';

    const timer = document.getElementById('countdown-timer');
    if (timer && showCountdown) {
        timer.style.display = 'block';
    }

    resetCountdown();

    // เริ่มการซ่อน toolbar อัตโนมัติ
    if (autoHideToolbar) {
        startToolbarHideTimer();
    }
}

function stopAutoplay() {
    isAutoplayActive = false;
    const btn = document.getElementById('autoplay-btn');
    btn.classList.remove('bg-red-600', 'hover:bg-red-700');
    btn.classList.add('bg-emerald-600', 'hover:bg-emerald-700');
    btn.innerHTML = '<i class="fas fa-play text-xl"></i>';

    if (autoplayInterval) {
        clearInterval(autoplayInterval);
    }
    if (countdownInterval) {
        clearInterval(countdownInterval);
    }

    const timer = document.getElementById('countdown-timer');
    if (timer) {
        timer.style.display = 'none';
    }

    // แสดง toolbar กลับมาเมื่อหยุด autoplay
    stopToolbarHideTimer();
    showToolbar();
}

function resetCountdown() {
    if (autoplayInterval) {
        clearInterval(autoplayInterval);
    }
    if (countdownInterval) {
        clearInterval(countdownInterval);
    }

    let timeLeft = slideDuration;
    const countdownValue = document.getElementById('countdown-value');
    const timer = document.getElementById('countdown-timer');

    if (isAutoplayActive && showCountdown && timer) {
        timer.style.display = 'block';
        countdownValue.textContent = timeLeft;

        countdownInterval = setInterval(() => {
            timeLeft--;
            countdownValue.textContent = timeLeft;
            if (timeLeft <= 0) {
                clearInterval(countdownInterval);
            }
        }, 1000);
    }

    autoplayInterval = setTimeout(() => {
        nextSlide();
    }, slideDuration * 1000);
}

function toggleSettings() {
    const panel = document.getElementById('settings-panel');
    panel.classList.toggle('hidden');
}

function updateSlideDuration(value) {
    slideDuration = parseInt(value);
    document.getElementById('duration-value').textContent = value + 's';
    if (isAutoplayActive) {
        resetCountdown();
    }
}

function toggleCountdownVisibility() {
    showCountdown = document.getElementById('show-countdown').checked;
    const timer = document.getElementById('countdown-timer');
    if (!showCountdown && timer) {
        timer.style.display = 'none';
    } else if (isAutoplayActive && timer) {
        timer.style.display = 'block';
    }
}

function toggleLoop() {
    loopSlides = document.getElementById('loop-slides').checked;
}

// Auto-hide toolbar functions
function startToolbarHideTimer() {
    stopToolbarHideTimer();  // Clear existing timer
    toolbarHideTimeout = setTimeout(() => {
        hideToolbar();
    }, TOOLBAR_HIDE_DELAY);
}

function stopToolbarHideTimer() {
    if (toolbarHideTimeout) {
        clearTimeout(toolbarHideTimeout);
        toolbarHideTimeout = null;
    }
}

function hideToolbar() {
    const fullscreen = document.getElementById('presentation-fullscreen');
    if (fullscreen && isAutoplayActive && autoHideToolbar) {
        fullscreen.classList.add('toolbar-hidden');
    }
}

function showToolbar() {
    const fullscreen = document.getElementById('presentation-fullscreen');
    if (fullscreen) {
        fullscreen.classList.remove('toolbar-hidden');
    }
}

function peekToolbar() {
    const fullscreen = document.getElementById('presentation-fullscreen');
    if (fullscreen && fullscreen.classList.contains('toolbar-hidden')) {
        fullscreen.classList.add('toolbar-peek');

        // ซ่อนอีกครั้งหลังจาก 3 วินาที
        stopToolbarHideTimer();
        toolbarHideTimeout = setTimeout(() => {
            fullscreen.classList.remove('toolbar-peek');
        }, TOOLBAR_HIDE_DELAY);
    }
}

// Event listeners สำหรับการแสดง toolbar เมื่อ click
document.addEventListener('DOMContentLoaded', function() {
    const fullscreen = document.getElementById('presentation-fullscreen');
    if (fullscreen) {
        // คลิกที่หน้าจอจะแสดง toolbar ชั่วคราว
        fullscreen.addEventListener('click', function(e) {
            // ไม่ทำงานถ้าคลิกที่ปุ่มควบคุม
            if (e.target.closest('#control-panel') ||
                e.target.closest('.absolute.bottom-8') ||
                e.target.closest('#settings-panel') ||
                e.target.closest('#topic-menu')) {
                return;
            }

            if (isAutoplayActive && fullscreen.classList.contains('toolbar-hidden')) {
                peekToolbar();
            }
        });

        // เมื่อเลื่อนเมาส์ไปที่ขอบบนหรือล่าง แสดง toolbar
        fullscreen.addEventListener('mousemove', function(e) {
            if (!isAutoplayActive || !fullscreen.classList.contains('toolbar-hidden')) return;

            const rect = fullscreen.getBoundingClientRect();
            const topZone = rect.top + 100;  // 100px จากขอบบน
            const bottomZone = rect.bottom - 100;  // 100px จากขอบล่าง

            if (e.clientY <= topZone || e.clientY >= bottomZone) {
                peekToolbar();
            }
        });
    }
});

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    const fullscreen = document.getElementById('presentation-fullscreen');
    if (fullscreen.classList.contains('hidden')) return;

    if (e.key === 'ArrowRight' || e.key === ' ') {
        e.preventDefault();
        nextSlide();
    } else if (e.key === 'ArrowLeft') {
        e.preventDefault();
        previousSlide();
    } else if (e.key === 'Escape') {
        closePresentationFullscreen();
    } else if (e.key === 'f' || e.key === 'F') {
        toggleAutoplay();
    }
});
</script>
@endpush
