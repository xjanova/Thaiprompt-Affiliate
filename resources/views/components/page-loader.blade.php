@php
    // Get loader settings from database
    $loaderEnabled = \App\Models\Setting::get('page_loader_enabled', true);
    $loaderType = \App\Models\Setting::get('page_loader_type', 'spinner');
    $loaderColor = \App\Models\Setting::get('page_loader_color', '#6366f1');
    $loaderColorSecondary = \App\Models\Setting::get('page_loader_color_secondary', '#8b5cf6');
    $loaderGif = \App\Models\Setting::get('page_loader_gif');
    $progressMode = \App\Models\Setting::get('page_loader_progress_mode', 'real'); // 'real' or 'fake'
@endphp

@if($loaderEnabled)
<div id="page-loader" class="fixed inset-0 bg-white dark:bg-gray-900 z-[9999] flex flex-col items-center justify-center transition-opacity duration-500">
    <div class="flex flex-col items-center space-y-4">
        @if($loaderType === 'spinner')
            <!-- Spinner Loader -->
            <div class="relative">
                <div class="w-20 h-20 border-4 border-gray-200 dark:border-gray-700 border-t-4 rounded-full animate-spin"
                     style="border-top-color: {{ $loaderColor }}">
                </div>
                @php
                    $logo = \App\Models\Setting::get('logo');
                @endphp
                @if($logo)
                    <img src="{{ asset($logo) }}" alt="Logo" class="absolute inset-0 m-auto w-10 h-10 object-contain">
                @endif
            </div>

    @elseif($loaderType === 'gradient_spinner')
        <!-- Gradient Spinner Loader -->
        <div class="relative w-20 h-20">
            <div class="absolute inset-0 rounded-full animate-spin"
                 style="background: conic-gradient(from 0deg, {{ $loaderColor }}, {{ $loaderColorSecondary }}, {{ $loaderColor }});
                        -webkit-mask: radial-gradient(farthest-side, transparent calc(100% - 4px), #000 calc(100% - 4px));
                        mask: radial-gradient(farthest-side, transparent calc(100% - 4px), #000 calc(100% - 4px));">
            </div>
        </div>

    @elseif($loaderType === 'dots')
        <!-- Dots Loader -->
        <div class="flex space-x-2">
            <div class="w-4 h-4 rounded-full dot-bounce"
                 style="background-color: {{ $loaderColor }}; animation-delay: 0s"></div>
            <div class="w-4 h-4 rounded-full dot-bounce"
                 style="background-color: {{ $loaderColor }}; animation-delay: 0.2s"></div>
            <div class="w-4 h-4 rounded-full dot-bounce"
                 style="background-color: {{ $loaderColor }}; animation-delay: 0.4s"></div>
        </div>

    @elseif($loaderType === 'pulse')
        <!-- Pulse Loader -->
        <div class="relative">
            <div class="w-20 h-20 rounded-full animate-ping absolute"
                 style="background-color: {{ $loaderColor }}; opacity: 0.3"></div>
            <div class="w-20 h-20 rounded-full animate-pulse"
                 style="background-color: {{ $loaderColor }}"></div>
        </div>

    @elseif($loaderType === 'progress')
        <!-- Progress Bar Loader with Gradient -->
        <div class="w-64">
            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                <div id="progress-bar" class="h-full rounded-full transition-all duration-300"
                     style="background: linear-gradient(90deg, {{ $loaderColor }}, {{ $loaderColorSecondary }}); width: 0%"></div>
            </div>
        </div>

    @elseif($loaderType === 'wave')
        <!-- Wave Loader -->
        <div class="flex items-end space-x-1">
            <div class="w-2 h-8 rounded-full wave-bar"
                 style="background: linear-gradient(180deg, {{ $loaderColor }}, {{ $loaderColorSecondary }}); animation-delay: 0s"></div>
            <div class="w-2 h-12 rounded-full wave-bar"
                 style="background: linear-gradient(180deg, {{ $loaderColor }}, {{ $loaderColorSecondary }}); animation-delay: 0.1s"></div>
            <div class="w-2 h-16 rounded-full wave-bar"
                 style="background: linear-gradient(180deg, {{ $loaderColor }}, {{ $loaderColorSecondary }}); animation-delay: 0.2s"></div>
            <div class="w-2 h-12 rounded-full wave-bar"
                 style="background: linear-gradient(180deg, {{ $loaderColor }}, {{ $loaderColorSecondary }}); animation-delay: 0.3s"></div>
            <div class="w-2 h-8 rounded-full wave-bar"
                 style="background: linear-gradient(180deg, {{ $loaderColor }}, {{ $loaderColorSecondary }}); animation-delay: 0.4s"></div>
        </div>

    @elseif($loaderType === 'bouncing_balls')
        <!-- Bouncing Balls Loader -->
        <div class="flex space-x-2">
            <div class="w-5 h-5 rounded-full animate-bounce"
                 style="background-color: {{ $loaderColor }}"></div>
            <div class="w-5 h-5 rounded-full animate-bounce"
                 style="background-color: {{ $loaderColor }}; animation-delay: 0.1s; opacity: 0.8"></div>
            <div class="w-5 h-5 rounded-full animate-bounce"
                 style="background-color: {{ $loaderColor }}; animation-delay: 0.2s; opacity: 0.6"></div>
        </div>

        @elseif($loaderType === 'custom_gif' && $loaderGif)
            <!-- Custom GIF Loader -->
            <div class="flex items-center justify-center">
                <img src="{{ asset($loaderGif) }}" alt="Loading..." class="max-w-xs max-h-64 object-contain">
            </div>
        @endif

        <!-- Loading Progress Text (แสดงสำหรับทุก loader type) -->
        <div class="text-center mt-6">
            <div id="loading-percentage" class="text-2xl font-bold mb-2" style="color: {{ $loaderColor }}">
                0%
            </div>
            <div id="loading-status" class="text-sm text-gray-600 dark:text-gray-400">
                กำลังเริ่มต้น...
            </div>
            <div id="loading-details" class="text-xs text-gray-500 dark:text-gray-500 mt-1">

            </div>
        </div>
    </div>
</div>

<script>
    // Page Loader Script - Supports both Real and Fake progress modes
    (function() {
        const loader = document.getElementById('page-loader');
        const progressBar = document.getElementById('progress-bar');
        const percentageEl = document.getElementById('loading-percentage');
        const statusEl = document.getElementById('loading-status');
        const detailsEl = document.getElementById('loading-details');

        // Progress mode: 'real' or 'fake'
        const progressMode = '{{ $progressMode }}';

        // Loading state
        let totalResources = 0;
        let loadedResources = 0;
        let currentProgress = 0;
        const resourceTypes = {
            script: { count: 0, loaded: 0, label: 'JavaScript' },
            stylesheet: { count: 0, loaded: 0, label: 'CSS' },
            link: { count: 0, loaded: 0, label: 'Styles' },
            img: { count: 0, loaded: 0, label: 'รูปภาพ' },
            font: { count: 0, loaded: 0, label: 'ฟอนต์' },
            other: { count: 0, loaded: 0, label: 'อื่นๆ' }
        };

        // ============================================
        // FAKE PROGRESS MODE (แสดง Animation เฉยๆ ไม่ติดตาม)
        // ============================================
        function startFakeProgress() {
            console.log('Using FAKE progress mode (Animation only - no progress tracking)');

            // ไม่แสดง progress text และรายละเอียด - แสดงแค่ animation
            if (percentageEl) {
                percentageEl.style.display = 'none';
            }
            if (statusEl) {
                statusEl.style.display = 'none';
            }
            if (detailsEl) {
                detailsEl.style.display = 'none';
            }

            // รอให้หน้าโหลดเสร็จแล้วซ่อน loader
            window.addEventListener('load', () => {
                setTimeout(() => {
                    if (loader) {
                        loader.style.opacity = '0';
                        setTimeout(() => {
                            loader.style.display = 'none';
                        }, 500);
                    }
                }, 300);
            });

            // Fallback: ซ่อนหลังจาก 5 วินาที
            setTimeout(() => {
                if (loader && loader.style.opacity !== '0') {
                    loader.style.opacity = '0';
                    setTimeout(() => {
                        loader.style.display = 'none';
                    }, 500);
                }
            }, 5000);
        }

        // ============================================
        // REAL PROGRESS MODE
        // ============================================

        // Initialize - count all resources that need to be loaded
        function initializeResourceTracking() {
            // Count script tags
            const scripts = document.querySelectorAll('script[src]');
            resourceTypes.script.count = scripts.length;

            // Count stylesheets
            const stylesheets = document.querySelectorAll('link[rel="stylesheet"]');
            resourceTypes.stylesheet.count = stylesheets.length;

            // Count images
            const images = document.querySelectorAll('img[src]');
            resourceTypes.img.count = images.length;

            // Calculate total
            totalResources = Object.values(resourceTypes).reduce((sum, type) => sum + type.count, 0);

            // Add base resources (DOM, Alpine.js init, etc.) - estimate
            totalResources += 5; // DOM parsing, Alpine init, etc.

            console.log('Total resources to track:', totalResources);
        }

        // Update progress display
        function updateProgress(progress, status, details = '') {
            currentProgress = Math.min(Math.round(progress), 100);

            if (percentageEl) {
                percentageEl.textContent = currentProgress + '%';
            }

            if (statusEl) {
                statusEl.textContent = status;
            }

            if (detailsEl && details) {
                detailsEl.textContent = details;
            }

            if (progressBar) {
                progressBar.style.width = currentProgress + '%';
            }

            console.log(`Progress: ${currentProgress}% - ${status} - ${details}`);
        }

        // Track resource loaded
        function trackResourceLoaded(type) {
            if (resourceTypes[type]) {
                resourceTypes[type].loaded++;
            }
            loadedResources++;

            const progress = (loadedResources / totalResources) * 100;
            const currentType = resourceTypes[type];

            updateProgress(
                progress,
                `กำลังโหลด ${currentType.label}...`,
                `${currentType.loaded}/${currentType.count} ${currentType.label}`
            );
        }

        // Track using Performance API
        function trackWithPerformanceAPI() {
            if (!window.PerformanceObserver) {
                return false;
            }

            try {
                const observer = new PerformanceObserver((list) => {
                    for (const entry of list.getEntries()) {
                        if (entry.entryType === 'resource') {
                            let type = 'other';

                            if (entry.initiatorType === 'script' || entry.name.includes('.js')) {
                                type = 'script';
                            } else if (entry.initiatorType === 'link' || entry.name.includes('.css')) {
                                type = 'stylesheet';
                            } else if (entry.initiatorType === 'img' || /\.(jpg|jpeg|png|gif|svg|webp)/.test(entry.name)) {
                                type = 'img';
                            } else if (entry.name.includes('font') || /\.(woff|woff2|ttf|eot)/.test(entry.name)) {
                                type = 'font';
                                resourceTypes.font.count++;
                                totalResources++;
                            }

                            trackResourceLoaded(type);
                        }
                    }
                });

                observer.observe({ entryTypes: ['resource'] });
                return true;
            } catch (e) {
                console.error('PerformanceObserver error:', e);
                return false;
            }
        }

        // Fallback: Track resources manually
        function trackResourcesManually() {
            updateProgress(10, 'กำลังโหลด HTML...', 'กำลังประมวลผล DOM');

            // Track stylesheets
            const stylesheets = document.querySelectorAll('link[rel="stylesheet"]');
            let stylesheetsLoaded = 0;
            stylesheets.forEach((link, index) => {
                link.addEventListener('load', () => {
                    stylesheetsLoaded++;
                    trackResourceLoaded('stylesheet');
                });
                link.addEventListener('error', () => {
                    stylesheetsLoaded++;
                    trackResourceLoaded('stylesheet');
                });
            });

            // Track images
            const images = document.querySelectorAll('img[src]');
            let imagesLoaded = 0;
            images.forEach((img, index) => {
                if (img.complete) {
                    imagesLoaded++;
                    trackResourceLoaded('img');
                } else {
                    img.addEventListener('load', () => {
                        imagesLoaded++;
                        trackResourceLoaded('img');
                    });
                    img.addEventListener('error', () => {
                        imagesLoaded++;
                        trackResourceLoaded('img');
                    });
                }
            });

            // Track scripts
            const scripts = document.querySelectorAll('script[src]');
            let scriptsLoaded = 0;
            scripts.forEach((script, index) => {
                script.addEventListener('load', () => {
                    scriptsLoaded++;
                    trackResourceLoaded('script');
                });
                script.addEventListener('error', () => {
                    scriptsLoaded++;
                    trackResourceLoaded('script');
                });
            });
        }

        // ============================================
        // MODE SELECTOR
        // ============================================
        console.log('Progress Mode:', progressMode);

        if (progressMode === 'fake') {
            // Use FAKE progress mode
            startFakeProgress();
        } else {
            // Use REAL progress mode (default)
            console.log('Using REAL progress mode');

            // Initialize tracking
            initializeResourceTracking();
            updateProgress(5, 'กำลังเริ่มต้น...', 'เตรียมโหลดทรัพยากร');

            // Use PerformanceAPI or fallback to manual tracking
            const usingPerformanceAPI = trackWithPerformanceAPI();
            if (!usingPerformanceAPI) {
                trackResourcesManually();
            }

            // DOMContentLoaded event - DOM is ready
            document.addEventListener('DOMContentLoaded', () => {
                loadedResources += 2;
                const progress = Math.min((loadedResources / totalResources) * 100, 85);
                updateProgress(progress, 'กำลังเตรียม Alpine.js...', 'DOM พร้อมแล้ว');
            });

            // Wait for Alpine.js initialization
            document.addEventListener('alpine:init', () => {
                loadedResources += 1;
                const progress = Math.min((loadedResources / totalResources) * 100, 90);
                updateProgress(progress, 'กำลังเริ่มต้น Components...', 'Alpine.js พร้อมแล้ว');
            });

            // window.load event - everything is loaded
            window.addEventListener('load', () => {
                loadedResources = totalResources;
                updateProgress(95, 'เกือบเสร็จแล้ว...', 'โหลดทรัพยากรเสร็จสมบูรณ์');

                // Final step
                setTimeout(() => {
                    updateProgress(100, 'เสร็จสมบูรณ์!', 'พร้อมใช้งาน');

                    // Hide loader
                    setTimeout(() => {
                        if (loader) {
                            loader.style.opacity = '0';
                            setTimeout(() => {
                                loader.style.display = 'none';
                            }, 500);
                        }
                    }, 300);
                }, 200);
            });

            // Fallback: Force hide loader after 10 seconds (increased from 5)
            setTimeout(() => {
                if (loader && loader.style.opacity !== '0') {
                    updateProgress(100, 'โหลดเสร็จสิ้น', 'ใช้เวลานานกว่าปกติ');
                    loader.style.opacity = '0';
                    setTimeout(() => {
                        loader.style.display = 'none';
                    }, 500);
                }
            }, 10000);

            // Progressive updates for better UX (even if resources load slowly)
            let fakeProgressInterval = setInterval(() => {
                if (currentProgress < 85 && currentProgress < ((loadedResources / totalResources) * 100) - 10) {
                    // Gradually increase progress if it's lagging behind
                    const targetProgress = Math.min((loadedResources / totalResources) * 100, 85);
                    currentProgress = Math.min(currentProgress + 1, targetProgress);

                    if (percentageEl) {
                        percentageEl.textContent = Math.round(currentProgress) + '%';
                    }
                    if (progressBar) {
                        progressBar.style.width = Math.round(currentProgress) + '%';
                    }
                }

                // Stop interval when we reach 95%
                if (currentProgress >= 95) {
                    clearInterval(fakeProgressInterval);
                }
            }, 100);
        }
    })();
</script>

<style>
    /* Custom animations */
    @keyframes dot-bounce {
        0%, 80%, 100% {
            transform: scale(0);
        }
        40% {
            transform: scale(1);
        }
    }

    .dot-bounce {
        animation: dot-bounce 1.4s infinite ease-in-out both;
    }

    @keyframes wave-animation {
        0%, 100% {
            transform: scaleY(0.5);
        }
        50% {
            transform: scaleY(1);
        }
    }

    .wave-bar {
        animation: wave-animation 1.2s infinite ease-in-out;
        transform-origin: bottom;
    }

    /* Ensure gradient spinner works in all browsers */
    @supports not (mask: radial-gradient(farthest-side, transparent calc(100% - 4px), #000 calc(100% - 4px))) {
        .gradient-spinner-fallback {
            border: 4px solid transparent;
            border-image: conic-gradient(from 0deg, var(--loader-color, #6366f1), var(--loader-color-secondary, #8b5cf6), var(--loader-color, #6366f1)) 1;
            border-radius: 50%;
        }
    }
</style>
@endif
