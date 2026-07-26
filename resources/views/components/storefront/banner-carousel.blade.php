{{--
    Banner Carousel Component - สไตล์ AliExpress

    แสดง Banner แบบ carousel พร้อม auto-play และ controls
    รองรับ Dark Mode และ Responsive

    @param Collection $banners - รายการ banners
    @param int $autoPlayInterval - ความถี่ในการเปลี่ยน slide (ms)
--}}

@props([
    'banners' => collect(),
    'autoPlayInterval' => 5000,
    'height' => 'h-[400px] md:h-[450px] lg:h-[500px]',
])

<div x-data="bannerCarousel({{ $autoPlayInterval }})"
     x-init="init()"
     class="relative overflow-hidden rounded-2xl lg:rounded-3xl group {{ $height }}">

    {{-- Slides Container --}}
    <div class="relative w-full h-full">
        @forelse($banners as $index => $banner)
        <div x-show="currentSlide === {{ $index }}"
             x-transition:enter="transition ease-out duration-500"
             x-transition:enter-start="opacity-0 transform translate-x-full"
             x-transition:enter-end="opacity-100 transform translate-x-0"
             x-transition:leave="transition ease-in duration-500"
             x-transition:leave-start="opacity-100 transform translate-x-0"
             x-transition:leave-end="opacity-0 transform -translate-x-full"
             class="absolute inset-0">

            {{-- Background Image --}}
            {{-- สไลด์แรกเป็น LCP ของหน้า → โหลดทันที ที่เหลือค่อย lazy --}}
            @if($banner['image'] ?? null)
            <img src="{{ $banner['image'] }}"
                 alt="{{ $banner['title'] ?? 'แบนเนอร์โปรโมชัน' }}"
                 width="1600"
                 height="500"
                 class="w-full h-full object-cover"
                 loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                 fetchpriority="{{ $loop->first ? 'high' : 'auto' }}"
                 decoding="async"
                 onerror="this.onerror=null; this.src='{{ asset('images/no-image.png') }}';">
            @else
            {{-- Gradient Background Fallback --}}
            <div class="w-full h-full bg-gradient-to-br {{ $banner['gradient'] ?? 'from-orange-500 via-red-500 to-pink-600' }}">
                {{-- Decorative Pattern --}}
                <div class="absolute inset-0 opacity-20">
                    <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 40px 40px;"></div>
                </div>

                {{-- Floating Shapes --}}
                <div class="absolute top-10 right-10 w-40 h-40 bg-white/10 rounded-full blur-3xl animate-pulse"></div>
                <div class="absolute bottom-20 left-20 w-60 h-60 bg-white/10 rounded-full blur-3xl animate-pulse delay-500"></div>
            </div>
            @endif

            {{-- Gradient Overlay --}}
            <div class="absolute inset-0 bg-gradient-to-r from-black/60 via-black/20 to-transparent"></div>

            {{-- Content --}}
            <div class="absolute inset-0 flex items-center">
                <div class="container mx-auto px-6 lg:px-12">
                    <div class="max-w-xl">
                        {{-- Badge --}}
                        @if($banner['badge'] ?? null)
                        <div class="inline-flex items-center gap-2 px-4 py-2 mb-4
                                   bg-white/20 backdrop-blur-lg border border-white/30
                                   rounded-full animate-fade-in">
                            <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                            <span class="text-white text-sm font-semibold">{{ $banner['badge'] }}</span>
                        </div>
                        @endif

                        {{-- Title --}}
                        @if($banner['title'] ?? null)
                        <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white mb-4
                                  drop-shadow-2xl animate-slide-up"
                            style="animation-delay: 100ms;">
                            {{ $banner['title'] }}
                        </h2>
                        @endif

                        {{-- Subtitle --}}
                        @if($banner['subtitle'] ?? null)
                        <p class="text-xl md:text-2xl text-white/90 mb-6 font-medium
                                 animate-slide-up"
                           style="animation-delay: 200ms;">
                            {{ $banner['subtitle'] }}
                        </p>
                        @endif

                        {{-- Highlight Text --}}
                        @if($banner['highlight'] ?? null)
                        <div class="inline-flex items-baseline gap-3 mb-8 animate-slide-up"
                             style="animation-delay: 300ms;">
                            <span class="text-5xl md:text-6xl font-black text-yellow-400 drop-shadow-lg">
                                {{ $banner['highlight'] }}
                            </span>
                            <span class="text-xl text-white/80">{{ $banner['highlight_label'] ?? '' }}</span>
                        </div>
                        @endif

                        {{-- CTA Button --}}
                        @if($banner['cta_url'] ?? null)
                        <div class="animate-slide-up" style="animation-delay: 400ms;">
                            <a href="{{ $banner['cta_url'] }}"
                               class="inline-flex items-center gap-3 px-8 py-4
                                     bg-white hover:bg-gray-100
                                     text-gray-900 font-bold text-lg
                                     rounded-2xl shadow-2xl hover:shadow-3xl
                                     transform hover:scale-105
                                     transition-all duration-300 group">
                                <span>{{ $banner['cta_text'] ?? 'ช้อปเลย' }}</span>
                                <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                </svg>
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @empty
        {{-- Default Banner when no banners --}}
        <div class="absolute inset-0 bg-gradient-to-br from-orange-500 via-red-500 to-pink-600 flex items-center justify-center">
            <div class="text-center text-white">
                <h2 class="text-4xl font-bold mb-4">ยินดีต้อนรับสู่ร้านค้าของเรา</h2>
                <p class="text-xl opacity-90">ค้นพบสินค้าคุณภาพหลากหลายรายการ</p>
            </div>
        </div>
        @endforelse
    </div>

    {{-- Navigation Arrows --}}
    @if($banners->count() > 1)
    <button @click="prevSlide()"
            class="absolute left-4 top-1/2 -translate-y-1/2 z-20
                   w-12 h-12 md:w-14 md:h-14
                   bg-white/20 hover:bg-white/40
                   backdrop-blur-lg
                   border border-white/30
                   text-white
                   rounded-full shadow-xl
                   flex items-center justify-center
                   opacity-0 group-hover:opacity-100
                   -translate-x-4 group-hover:translate-x-0
                   transition-all duration-300
                   hover:scale-110">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </button>

    <button @click="nextSlide()"
            class="absolute right-4 top-1/2 -translate-y-1/2 z-20
                   w-12 h-12 md:w-14 md:h-14
                   bg-white/20 hover:bg-white/40
                   backdrop-blur-lg
                   border border-white/30
                   text-white
                   rounded-full shadow-xl
                   flex items-center justify-center
                   opacity-0 group-hover:opacity-100
                   translate-x-4 group-hover:translate-x-0
                   transition-all duration-300
                   hover:scale-110">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </button>
    @endif

    {{-- Dots Indicator --}}
    @if($banners->count() > 1)
    <div class="absolute bottom-6 left-1/2 -translate-x-1/2 z-20
                flex items-center gap-2">
        @foreach($banners as $index => $banner)
        <button @click="goToSlide({{ $index }})"
                class="w-3 h-3 rounded-full transition-all duration-300"
                :class="currentSlide === {{ $index }}
                       ? 'bg-white w-8 shadow-lg'
                       : 'bg-white/50 hover:bg-white/80'">
        </button>
        @endforeach
    </div>
    @endif

    {{-- Progress Bar --}}
    @if($banners->count() > 1)
    <div class="absolute bottom-0 left-0 right-0 h-1 bg-white/20">
        <div class="h-full bg-white transition-all duration-100 ease-linear"
             :style="`width: ${progress}%`"></div>
    </div>
    @endif
</div>

<style>
/* Slide Animations */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
    animation: fadeIn 0.5s ease-out forwards;
}

.animate-slide-up {
    animation: slideUp 0.6s ease-out forwards;
    opacity: 0;
}

/* Delay classes */
.delay-500 {
    animation-delay: 500ms;
}
</style>

<script>
/**
 * Banner Carousel Alpine Component
 *
 * จัดการ carousel animation และ auto-play
 *
 * @param {number} interval - ความถี่ในการเปลี่ยน slide (ms)
 */
function bannerCarousel(interval = 5000) {
    return {
        currentSlide: 0,
        totalSlides: {{ $banners->count() }},
        autoPlayInterval: interval,
        intervalId: null,
        progress: 0,
        progressInterval: null,
        isPaused: false,

        /**
         * เริ่มต้น carousel
         */
        init() {
            this.startAutoPlay();
            this.startProgressBar();

            // หยุดเมื่อ hover
            this.$el.addEventListener('mouseenter', () => {
                this.isPaused = true;
            });

            this.$el.addEventListener('mouseleave', () => {
                this.isPaused = false;
            });

            // Keyboard navigation
            document.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowLeft') this.prevSlide();
                if (e.key === 'ArrowRight') this.nextSlide();
            });
        },

        /**
         * เริ่ม auto-play
         */
        startAutoPlay() {
            this.intervalId = setInterval(() => {
                if (!this.isPaused) {
                    this.nextSlide();
                }
            }, this.autoPlayInterval);
        },

        /**
         * เริ่ม progress bar
         */
        startProgressBar() {
            const step = 100 / (this.autoPlayInterval / 50);
            this.progressInterval = setInterval(() => {
                if (!this.isPaused) {
                    this.progress += step;
                    if (this.progress >= 100) {
                        this.progress = 0;
                    }
                }
            }, 50);
        },

        /**
         * ไปยัง slide ถัดไป
         */
        nextSlide() {
            this.currentSlide = (this.currentSlide + 1) % this.totalSlides;
            this.progress = 0;
        },

        /**
         * ไปยัง slide ก่อนหน้า
         */
        prevSlide() {
            this.currentSlide = (this.currentSlide - 1 + this.totalSlides) % this.totalSlides;
            this.progress = 0;
        },

        /**
         * ไปยัง slide ที่กำหนด
         *
         * @param {number} index - index ของ slide
         */
        goToSlide(index) {
            this.currentSlide = index;
            this.progress = 0;
        },

        /**
         * ทำลาย carousel (cleanup)
         */
        destroy() {
            if (this.intervalId) clearInterval(this.intervalId);
            if (this.progressInterval) clearInterval(this.progressInterval);
        }
    };
}
</script>
