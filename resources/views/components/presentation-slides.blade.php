@php
    $primaryColor = \App\Models\Setting::get('primary_color', '#3B82F6');
    $secondaryColor = \App\Models\Setting::get('secondary_color', '#8B5CF6');
    $accentColor = \App\Models\Setting::get('accent_color', '#EC4899');
@endphp

<!-- Premium Presentation Slides Section -->
<section class="py-20 bg-gradient-to-br from-slate-900 via-indigo-900 to-slate-900 relative overflow-hidden" id="presentation-section">
    <!-- Animated Grid Background -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image:
            linear-gradient(to right, rgba(99, 102, 241, 0.15) 1px, transparent 1px),
            linear-gradient(to bottom, rgba(99, 102, 241, 0.15) 1px, transparent 1px);
            background-size: 40px 40px;"></div>
    </div>

    <!-- Floating Orbs -->
    <div class="absolute top-20 left-10 w-96 h-96 bg-indigo-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
    <div class="absolute top-40 right-10 w-96 h-96 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000"></div>
    <div class="absolute -bottom-8 left-1/3 w-96 h-96 bg-pink-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-4000"></div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <!-- Badge -->
            <div class="inline-flex items-center gap-2 px-6 py-3 bg-white/10 backdrop-blur-sm rounded-full border border-white/20 mb-8 animate-fade-in-down">
                <span class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                </span>
                <span class="text-white font-semibold">🎯 งานนำเสนอระดับพรีเมี่ยม</span>
            </div>

            <!-- Main Heading -->
            <h2 class="text-5xl md:text-6xl font-black mb-6 leading-tight">
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-white via-indigo-200 to-purple-200">
                    ไทยพร๊อม Presentation
                </span>
                <br>
                <span class="text-white">สำหรับวิทยากรและนักลงทุน</span>
            </h2>

            <p class="text-xl md:text-2xl text-gray-300 max-w-4xl mx-auto mb-8 leading-relaxed">
                สไลด์นำเสนอระบบแบบครบถ้วน สวยงาม มืออาชีพ พร้อมใช้งานทันที
                <br>
                <span class="text-indigo-300 font-semibold">เหมาะสำหรับนักลงทุน ผู้อยากร่วม และผู้สนใจทุกท่าน</span>
            </p>

            <!-- CTA Button -->
            <div class="relative inline-block mb-8">
                <div class="absolute -inset-1 bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl blur-lg opacity-75 group-hover:opacity-100 transition duration-1000 animate-pulse"></div>
                <button onclick="openPresentationFullscreen()" class="relative inline-flex items-center gap-3 px-10 py-5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-xl font-bold rounded-2xl shadow-2xl hover:shadow-indigo-500/50 transition-all duration-300 transform hover:scale-105 group">
                    <svg class="w-8 h-8 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"></path>
                    </svg>
                    <span>เปิดสไลด์นำเสนอ</span>
                    <svg class="w-6 h-6 group-hover:translate-x-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                </button>
            </div>

            <!-- Features -->
            <div class="flex flex-wrap items-center justify-center gap-6 text-white/80 text-sm">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span>เต็มจอ Fullscreen</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Auto-play อัจฉริยะ</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span>นับถอยหลังอัตโนมัติ</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span>ตั้งค่าได้ในวิดเจ็ต</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Fullscreen Presentation Modal -->
<div id="presentation-fullscreen" class="fixed inset-0 bg-black z-50 hidden">
    <!-- Logo Watermark Overlay -->
    <div class="absolute top-8 left-8 z-20 opacity-15 pointer-events-none">
        <img src="{{ asset('images/logo.svg') }}" alt="ไทยพร๊อม" width="80" height="80" class="w-20 h-20">
    </div>

    <!-- Control Panel -->
    <div class="absolute top-0 left-0 right-0 bg-gradient-to-b from-black/80 to-transparent p-6 z-30 transition-opacity duration-300" id="control-panel">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-4">
                <h3 class="text-white text-xl font-bold">ไทยพร๊อม - ระบบแพลตฟอร์มครบวงจร</h3>
                <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-white text-sm" id="slide-counter">
                    1 / 15
                </span>
            </div>
            <div class="flex items-center gap-4">
                <!-- Settings Button -->
                <button onclick="toggleSettings()" class="p-2 bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-lg text-white transition-colors" title="ตั้งค่า">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </button>
                <!-- Auto-play Toggle -->
                <button onclick="toggleAutoplay()" id="autoplay-btn" class="p-2 bg-emerald-600 hover:bg-emerald-700 backdrop-blur-sm rounded-lg text-white transition-colors" title="Auto-play">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </button>
                <!-- Close Button -->
                <button onclick="closePresentationFullscreen()" class="p-2 bg-red-600 hover:bg-red-700 backdrop-blur-sm rounded-lg text-white transition-colors" title="ปิด">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Settings Panel -->
    <div id="settings-panel" class="absolute top-20 right-6 bg-white/95 backdrop-blur-sm rounded-2xl shadow-2xl p-6 z-30 hidden min-w-[300px]">
        <h4 class="text-lg font-bold text-gray-900 mb-4">⚙️ ตั้งค่าการนำเสนอ</h4>

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
        @include('components.presentation-slides-content')
    </div>

    <!-- Navigation Controls -->
    <div class="absolute bottom-8 left-0 right-0 z-30">
        <div class="max-w-7xl mx-auto px-6 flex items-center justify-between">
            <!-- Previous Button -->
            <button onclick="previousSlide()" class="group flex items-center gap-2 px-6 py-3 bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-xl text-white transition-all">
                <svg class="w-6 h-6 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                <span class="font-medium">ย้อนกลับ</span>
            </button>

            <!-- Progress Dots -->
            <div class="flex gap-2" id="progress-dots">
                <!-- Dots will be generated by JavaScript -->
            </div>

            <!-- Next Button -->
            <button onclick="nextSlide()" class="group flex items-center gap-2 px-6 py-3 bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-xl text-white transition-all">
                <span class="font-medium">ถัดไป</span>
                <svg class="w-6 h-6 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
        </div>
    </div>

    <!-- Countdown Timer -->
    <div id="countdown-timer" class="absolute bottom-24 left-1/2 transform -translate-x-1/2 z-30">
        <div class="flex items-center gap-3 px-6 py-3 bg-white/10 backdrop-blur-sm rounded-full border border-white/20">
            <svg class="w-5 h-5 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-white font-bold text-lg">สไลด์ถัดไปใน <span id="countdown-value">15</span> วินาที</span>
        </div>
    </div>
</div>

@push('styles')
<style>
    @keyframes blob {
        0% { transform: translate(0px, 0px) scale(1); }
        33% { transform: translate(30px, -50px) scale(1.1); }
        66% { transform: translate(-20px, 20px) scale(0.9); }
        100% { transform: translate(0px, 0px) scale(1); }
    }

    .animate-blob {
        animation: blob 7s infinite;
    }

    .animation-delay-2000 {
        animation-delay: 2s;
    }

    .animation-delay-4000 {
        animation-delay: 4s;
    }

    @keyframes fade-in-down {
        0% { opacity: 0; transform: translateY(-20px); }
        100% { opacity: 1; transform: translateY(0); }
    }

    .animate-fade-in-down {
        animation: fade-in-down 1s ease-out;
    }

    /* Slide animations */
    .slide {
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        position: absolute;
        inset: 0;
    }

    .slide.active {
        opacity: 1;
        transform: translateX(0);
        position: relative;
    }

    .slide.prev {
        transform: translateX(-100%);
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
</style>
@endpush

@push('scripts')
<script>
let currentSlide = 0;
let autoplayInterval = null;
let countdownInterval = null;
let slideDuration = 15; // seconds
let isAutoplayActive = false;
let showCountdown = true;
let loopSlides = true;

const slides = document.querySelectorAll('.slide');
const totalSlides = slides.length;

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    initializeProgressDots();
    updateSlideCounter();
});

function openPresentationFullscreen() {
    const fullscreen = document.getElementById('presentation-fullscreen');
    fullscreen.classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    // Request fullscreen
    if (fullscreen.requestFullscreen) {
        fullscreen.requestFullscreen();
    } else if (fullscreen.webkitRequestFullscreen) {
        fullscreen.webkitRequestFullscreen();
    } else if (fullscreen.msRequestFullscreen) {
        fullscreen.msRequestFullscreen();
    }

    showSlide(0);
}

function closePresentationFullscreen() {
    const fullscreen = document.getElementById('presentation-fullscreen');
    fullscreen.classList.add('hidden');
    document.body.style.overflow = 'auto';

    if (document.exitFullscreen) {
        document.exitFullscreen();
    } else if (document.webkitExitFullscreen) {
        document.webkitExitFullscreen();
    } else if (document.msExitFullscreen) {
        document.msExitFullscreen();
    }

    stopAutoplay();
}

function showSlide(index) {
    // Remove active class from all slides
    slides.forEach((slide, i) => {
        slide.classList.remove('active', 'prev');
        if (i < index) {
            slide.classList.add('prev');
        }
    });

    // Add active class to current slide
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
    btn.innerHTML = `
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
    `;

    resetCountdown();
}

function stopAutoplay() {
    isAutoplayActive = false;
    const btn = document.getElementById('autoplay-btn');
    btn.classList.remove('bg-red-600', 'hover:bg-red-700');
    btn.classList.add('bg-emerald-600', 'hover:bg-emerald-700');
    btn.innerHTML = `
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
    `;

    if (autoplayInterval) {
        clearInterval(autoplayInterval);
    }
    if (countdownInterval) {
        clearInterval(countdownInterval);
    }

    const timer = document.getElementById('countdown-timer');
    if (timer && showCountdown) {
        timer.style.display = 'none';
    }
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

    if (showCountdown && timer) {
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

// Auto-hide control panel on mouse idle
let controlPanelTimeout;
const controlPanel = document.getElementById('control-panel');
const presentationFullscreen = document.getElementById('presentation-fullscreen');

presentationFullscreen.addEventListener('mousemove', function() {
    controlPanel.style.opacity = '1';
    clearTimeout(controlPanelTimeout);

    controlPanelTimeout = setTimeout(() => {
        if (isAutoplayActive) {
            controlPanel.style.opacity = '0';
        }
    }, 3000);
});
</script>
@endpush
