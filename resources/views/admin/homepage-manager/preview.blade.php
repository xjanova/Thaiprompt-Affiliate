<!DOCTYPE html>
<html lang="th" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" :class="darkMode && 'dark'">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview - หน้าแรก</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Animation Classes */
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeInDown { from { opacity: 0; transform: translateY(-30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeInLeft { from { opacity: 0; transform: translateX(-30px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes fadeInRight { from { opacity: 0; transform: translateX(30px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes slideInUp { from { transform: translateY(100%); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        @keyframes zoomIn { from { opacity: 0; transform: scale(0.8); } to { opacity: 1; transform: scale(1); } }
        @keyframes bounceIn { 0% { opacity: 0; transform: scale(0.3); } 50% { transform: scale(1.05); } 70% { transform: scale(0.9); } 100% { opacity: 1; transform: scale(1); } }

        .animate-on-scroll { opacity: 0; }
        .animate-on-scroll.animated { opacity: 1; }
        .animate-fadeIn { animation: fadeIn 0.6s ease-out forwards; }
        .animate-fadeInUp { animation: fadeInUp 0.6s ease-out forwards; }
        .animate-fadeInDown { animation: fadeInDown 0.6s ease-out forwards; }
        .animate-fadeInLeft { animation: fadeInLeft 0.6s ease-out forwards; }
        .animate-fadeInRight { animation: fadeInRight 0.6s ease-out forwards; }
        .animate-slideInUp { animation: slideInUp 0.6s ease-out forwards; }
        .animate-zoomIn { animation: zoomIn 0.6s ease-out forwards; }
        .animate-bounceIn { animation: bounceIn 0.8s ease-out forwards; }
    </style>
</head>
<body class="bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100">
    {{-- Preview Toolbar --}}
    <div class="fixed top-0 left-0 right-0 bg-gray-800 text-white px-4 py-2 flex items-center justify-between z-50">
        <div class="flex items-center space-x-4">
            <span class="text-sm font-medium">
                <i class="fas fa-eye mr-1"></i> Preview Mode
            </span>
            <div class="flex items-center space-x-2 text-sm">
                <button onclick="setDevice('mobile')" class="px-2 py-1 rounded hover:bg-gray-700">
                    <i class="fas fa-mobile-alt"></i>
                </button>
                <button onclick="setDevice('tablet')" class="px-2 py-1 rounded hover:bg-gray-700">
                    <i class="fas fa-tablet-alt"></i>
                </button>
                <button onclick="setDevice('desktop')" class="px-2 py-1 rounded hover:bg-gray-700">
                    <i class="fas fa-desktop"></i>
                </button>
            </div>
        </div>
        <div class="flex items-center space-x-4">
            <button @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)" class="px-3 py-1 rounded hover:bg-gray-700">
                <i class="fas" :class="darkMode ? 'fa-sun' : 'fa-moon'"></i>
            </button>
            <a href="{{ route('admin.homepage-manager.index') }}" class="px-3 py-1 bg-indigo-600 rounded hover:bg-indigo-700">
                <i class="fas fa-edit mr-1"></i> กลับไปแก้ไข
            </a>
        </div>
    </div>

    {{-- Preview Content --}}
    <div id="preview-container" class="pt-12 transition-all duration-300">
        @foreach($sections as $section)
        <section
            id="section-{{ $section->id }}"
            class="homepage-section relative {{ $section->animation ? 'animate-on-scroll' : '' }}"
            style="{{ $section->getStyleAttribute(false) }}"
            data-animation="{{ $section->animation }}"
            data-animation-delay="{{ $section->animation_delay }}"
        >
            {{-- Background Video --}}
            @if($section->background_type === 'video' && $section->background_video)
            <video class="absolute inset-0 w-full h-full object-cover -z-10" autoplay loop muted playsinline>
                <source src="{{ $section->background_video }}" type="video/mp4">
            </video>
            @endif

            {{-- Background Overlay --}}
            @if($section->background_overlay)
            <div class="absolute inset-0 -z-5" style="background-color: {{ $section->background_overlay }};"></div>
            @endif

            {{-- Content Container --}}
            <div class="{{ $section->is_fullwidth ? 'w-full' : 'container mx-auto' }} relative" style="min-height: inherit;">
                @foreach($section->activeElements as $element)
                <div
                    class="homepage-element {{ $element->animation ? 'animate-on-scroll' : '' }} {{ $element->position_type === 'absolute' ? 'absolute' : 'relative' }}"
                    style="{{ $element->getStyleString(false) }}"
                    data-animation="{{ $element->animation }}"
                    data-animation-delay="{{ $element->animation_delay }}"
                    data-animation-duration="{{ $element->animation_duration }}"
                >
                    @switch($element->type)
                        @case('heading')
                            @php $tag = $element->settings['heading_level'] ?? 'h2'; @endphp
                            <{{ $tag }}>{!! $element->content !!}</{{ $tag }}>
                            @break

                        @case('text')
                            <p>{!! nl2br(e($element->content)) !!}</p>
                            @break

                        @case('image')
                            @if($element->link_url)
                            <a href="{{ $element->link_url }}" target="{{ $element->link_target }}">
                            @endif
                            <img src="{{ $element->image_url }}" alt="{{ $element->settings['alt'] ?? '' }}" class="max-w-full h-auto">
                            @if($element->link_url)
                            </a>
                            @endif
                            @break

                        @case('button')
                            @if($element->link_url)
                            <a href="{{ $element->link_url }}" target="{{ $element->link_target }}" class="inline-flex items-center justify-center">
                                {{ $element->content }}
                            </a>
                            @else
                            <button class="inline-flex items-center justify-center">
                                {{ $element->content }}
                            </button>
                            @endif
                            @break

                        @case('video')
                            @if($element->video_url)
                                @if(str_contains($element->video_url, 'youtube') || str_contains($element->video_url, 'youtu.be'))
                                    @php
                                        preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $element->video_url, $matches);
                                        $videoId = $matches[1] ?? '';
                                    @endphp
                                    <iframe src="https://www.youtube.com/embed/{{ $videoId }}" frameborder="0" allowfullscreen class="w-full aspect-video"></iframe>
                                @elseif(str_contains($element->video_url, 'vimeo'))
                                    @php
                                        preg_match('/vimeo\.com\/(?:.*\/)?(\d+)/', $element->video_url, $matches);
                                        $videoId = $matches[1] ?? '';
                                    @endphp
                                    <iframe src="https://player.vimeo.com/video/{{ $videoId }}" frameborder="0" allowfullscreen class="w-full aspect-video"></iframe>
                                @else
                                    <video src="{{ $element->video_url }}" controls class="w-full"></video>
                                @endif
                            @endif
                            @break

                        @case('icon')
                            <i class="{{ $element->icon_class ?? 'fas fa-star' }}"></i>
                            @break

                        @case('html')
                            {!! $element->content !!}
                            @break

                        @case('spacer')
                            {{-- Spacer - just takes up space --}}
                            @break

                        @default
                            {!! $element->content !!}
                    @endswitch
                </div>
                @endforeach

                {{-- Empty Section Message --}}
                @if($section->activeElements->isEmpty())
                <div class="flex items-center justify-center min-h-[200px] text-gray-400">
                    <p>Section นี้ยังไม่มีเนื้อหา</p>
                </div>
                @endif
            </div>
        </section>
        @endforeach

        {{-- Empty State --}}
        @if($sections->isEmpty())
        <div class="flex flex-col items-center justify-center min-h-screen text-gray-400">
            <i class="fas fa-layer-group text-6xl mb-4"></i>
            <p class="text-xl">ยังไม่มี Section ในหน้าแรก</p>
            <a href="{{ route('admin.homepage-manager.index') }}" class="mt-4 px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                <i class="fas fa-plus mr-2"></i> เพิ่ม Section
            </a>
        </div>
        @endif
    </div>

    <script>
        // Intersection Observer for scroll animations
        document.addEventListener('DOMContentLoaded', function() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const el = entry.target;
                        const animation = el.dataset.animation;
                        const delay = parseInt(el.dataset.animationDelay) || 0;

                        setTimeout(() => {
                            el.classList.add('animated', `animate-${animation}`);
                        }, delay);

                        observer.unobserve(el);
                    }
                });
            }, { threshold: 0.1 });

            document.querySelectorAll('.animate-on-scroll').forEach(el => {
                observer.observe(el);
            });
        });

        // Device switching
        function setDevice(device) {
            const container = document.getElementById('preview-container');
            switch(device) {
                case 'mobile':
                    container.style.maxWidth = '375px';
                    container.style.margin = '0 auto';
                    break;
                case 'tablet':
                    container.style.maxWidth = '768px';
                    container.style.margin = '0 auto';
                    break;
                default:
                    container.style.maxWidth = 'none';
                    container.style.margin = '0';
            }
        }
    </script>
</body>
</html>
