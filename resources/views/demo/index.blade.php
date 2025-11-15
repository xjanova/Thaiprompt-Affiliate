@extends('demo.layout')

@section('title', 'V3 UI Themes Gallery')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    {{-- Hero Section --}}
    <div class="text-center mb-16 animate-fade-in">
        <div class="inline-block mb-4 px-4 py-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-full text-sm font-semibold">
            Version 3.0
        </div>
        <h1 class="text-5xl md:text-7xl font-bold mb-6 bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">
            V3 UI Themes Gallery
        </h1>
        <p class="text-xl md:text-2xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
            เลือก Theme ที่คุณชอบสำหรับโปรเจค Thaiprompt-Affiliate
            <br>
            <span class="text-lg">Tailwind CSS + Alpine.js + SortableJS</span>
        </p>
    </div>

    {{-- Theme Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-16">

        {{-- Theme 1: Premium Gradient --}}
        <div class="group perspective-1000 animate-scale-in">
            <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105 group-hover:rotate-y-3">
                {{-- Glow Effect --}}
                <div class="absolute inset-0 bg-gradient-to-br from-blue-500 via-purple-500 to-pink-500 rounded-3xl blur-2xl opacity-50 group-hover:opacity-75 transition-opacity"></div>

                {{-- Card --}}
                <div class="relative bg-white dark:bg-gray-800 rounded-3xl shadow-2xl overflow-hidden border border-gray-200 dark:border-gray-700">
                    {{-- Preview Image --}}
                    <div class="relative h-64 bg-gradient-to-br from-blue-500 via-purple-500 to-pink-500 overflow-hidden">
                        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxwYXRoIGQ9Ik0zNiAxOGMzLjMxNCAwIDYgMi42ODYgNiA2cy0yLjY4NiA2LTYgNi02LTIuNjg2LTYtNiAyLjY4Ni02IDYtNiIgc3Ryb2tlPSIjZmZmIiBzdHJva2Utd2lkdGg9IjIiIG9wYWNpdHk9Ii4xIi8+PC9nPjwvc3ZnPg==')] opacity-30"></div>

                        <div class="relative z-10 h-full flex flex-col items-center justify-center p-8">
                            <div class="w-20 h-20 bg-white/20 backdrop-blur-sm rounded-3xl flex items-center justify-center mb-4">
                                <i class="fas fa-rocket text-white text-4xl"></i>
                            </div>
                            <h3 class="text-3xl font-bold text-white">Theme 1</h3>
                        </div>
                    </div>

                    {{-- Content --}}
                    <div class="p-8">
                        <span class="inline-block px-3 py-1 bg-gradient-to-r from-blue-500 to-purple-600 text-white text-xs font-bold rounded-full mb-4">
                            Premium
                        </span>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                            Premium Gradient
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">
                            เน้นสีสันสดใส Gradient ที่สวยงาม มีพลัง ดึงดูดสายตา เหมาะกับธุรกิจที่ต้องการสร้างความโดดเด่น
                        </p>

                        <div class="space-y-2 mb-6 text-sm text-gray-600 dark:text-gray-400">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-check text-green-500"></i>
                                <span>Colorful Gradients</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-check text-green-500"></i>
                                <span>3D Hover Effects</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-check text-green-500"></i>
                                <span>Smooth Animations</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <a href="{{ route('demo.theme1') }}"
                               class="block px-4 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white text-center font-bold rounded-xl hover:shadow-lg transform hover:scale-105 transition-all">
                                <i class="fas fa-eye mr-2"></i>
                                Theme
                            </a>
                            <a href="{{ route('demo.dashboard1') }}"
                               class="block px-4 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white text-center font-bold rounded-xl hover:shadow-lg transform hover:scale-105 transition-all">
                                <i class="fas fa-th-large mr-2"></i>
                                Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Theme 2: Glassmorphism --}}
        <div class="group perspective-1000 animate-scale-in" style="animation-delay: 150ms;">
            <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105 group-hover:rotate-y-3">
                <div class="absolute inset-0 bg-gradient-to-br from-cyan-400 to-blue-600 rounded-3xl blur-2xl opacity-50 group-hover:opacity-75 transition-opacity"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-3xl shadow-2xl overflow-hidden border border-gray-200 dark:border-gray-700">
                    {{-- Preview Image --}}
                    <div class="relative h-64 bg-gradient-to-br from-cyan-400 via-blue-500 to-purple-600 overflow-hidden">
                        <div class="absolute top-1/4 left-1/4 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
                        <div class="absolute bottom-1/4 right-1/4 w-32 h-32 bg-pink-500/10 rounded-full blur-2xl"></div>

                        <div class="relative z-10 h-full flex flex-col items-center justify-center p-8">
                            <div class="w-20 h-20 bg-white/20 backdrop-blur-lg border border-white/30 rounded-3xl flex items-center justify-center mb-4">
                                <i class="fas fa-gem text-white text-4xl"></i>
                            </div>
                            <h3 class="text-3xl font-bold text-white">Theme 2</h3>
                        </div>
                    </div>

                    <div class="p-8">
                        <span class="inline-block px-3 py-1 bg-gradient-to-r from-cyan-500 to-blue-600 text-white text-xs font-bold rounded-full mb-4">
                            Modern
                        </span>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                            Glassmorphism
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">
                            โปร่งใส Backdrop Blur สไตล์ Glass ทันสมัย Minimal & Elegant เหมาะกับ Premium Apps
                        </p>

                        <div class="space-y-2 mb-6 text-sm text-gray-600 dark:text-gray-400">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-check text-cyan-500"></i>
                                <span>Transparent Glass</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-check text-cyan-500"></i>
                                <span>Backdrop Blur</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-check text-cyan-500"></i>
                                <span>Minimal Design</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <a href="{{ route('demo.theme2') }}"
                               class="block px-4 py-3 bg-gradient-to-r from-cyan-500 to-blue-600 text-white text-center font-bold rounded-xl hover:shadow-lg transform hover:scale-105 transition-all">
                                <i class="fas fa-eye mr-2"></i>
                                Theme
                            </a>
                            <a href="{{ route('demo.dashboard2') }}"
                               class="block px-4 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white text-center font-bold rounded-xl hover:shadow-lg transform hover:scale-105 transition-all">
                                <i class="fas fa-th-large mr-2"></i>
                                Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Theme 3: Neumorphism --}}
        <div class="group perspective-1000 animate-scale-in" style="animation-delay: 300ms;">
            <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105 group-hover:rotate-y-3">
                <div class="absolute inset-0 bg-gradient-to-br from-gray-400 to-gray-600 rounded-3xl blur-2xl opacity-40 group-hover:opacity-60 transition-opacity"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-3xl shadow-2xl overflow-hidden border border-gray-200 dark:border-gray-700">
                    {{-- Preview Image --}}
                    <div class="relative h-64 bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-700 dark:to-gray-800 overflow-hidden">
                        <div class="relative z-10 h-full flex flex-col items-center justify-center p-8">
                            <div class="w-20 h-20 bg-gray-100 dark:bg-gray-700 shadow-[8px_8px_16px_#b3b3b3,-8px_-8px_16px_#ffffff] dark:shadow-[8px_8px_16px_#1a1a1a,-8px_-8px_16px_#404d64] rounded-3xl flex items-center justify-center mb-4">
                                <i class="fas fa-cube text-gray-700 dark:text-gray-300 text-4xl"></i>
                            </div>
                            <h3 class="text-3xl font-bold text-gray-800 dark:text-gray-100">Theme 3</h3>
                        </div>
                    </div>

                    <div class="p-8">
                        <span class="inline-block px-3 py-1 bg-gray-600 text-white text-xs font-bold rounded-full mb-4">
                            Elegant
                        </span>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                            Neumorphism
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">
                            Soft Shadow มิติ 3D สไตล์ Minimal นูนและเว้า สร้างความรู้สึกสัมผัสได้ เหมาะกับ Clean UI
                        </p>

                        <div class="space-y-2 mb-6 text-sm text-gray-600 dark:text-gray-400">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-check text-gray-600"></i>
                                <span>Soft Shadows</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-check text-gray-600"></i>
                                <span>3D Depth</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-check text-gray-600"></i>
                                <span>Tactile Feel</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <a href="{{ route('demo.theme3') }}"
                               class="block px-4 py-3 bg-gray-700 dark:bg-gray-600 text-white text-center font-bold rounded-xl hover:shadow-lg transform hover:scale-105 transition-all">
                                <i class="fas fa-eye mr-2"></i>
                                Theme
                            </a>
                            <a href="{{ route('demo.dashboard3') }}"
                               class="block px-4 py-3 bg-gray-800 dark:bg-gray-700 text-white text-center font-bold rounded-xl hover:shadow-lg transform hover:scale-105 transition-all">
                                <i class="fas fa-th-large mr-2"></i>
                                Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Theme 4: Ultimate Fusion --}}
        <div class="group perspective-1000 animate-scale-in" style="animation-delay: 300ms;">
            <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105 group-hover:rotate-y-3">
                {{-- Glow Effect (Rainbow) --}}
                <div class="absolute inset-0 bg-gradient-to-br from-purple-500 via-pink-500 to-orange-500 rounded-3xl blur-2xl opacity-50 group-hover:opacity-75 transition-opacity"></div>

                {{-- Card --}}
                <div class="relative bg-white dark:bg-gray-800 rounded-3xl shadow-2xl overflow-hidden border border-gray-200 dark:border-gray-700">
                    {{-- Preview Image --}}
                    <div class="relative h-64 bg-gradient-to-br from-purple-500 via-pink-500 to-orange-500 overflow-hidden">
                        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxwYXRoIGQ9Ik0zNiAxOGMzLjMxNCAwIDYgMi42ODYgNiA2cy0yLjY4NiA2LTYgNi02LTIuNjg2LTYtNiAyLjY4Ni02IDYtNiIgc3Ryb2tlPSIjZmZmIiBzdHJva2Utd2lkdGg9IjIiIG9wYWNpdHk9Ii4yIi8+PC9nPjwvc3ZnPg==')] opacity-30"></div>

                        <div class="relative z-10 h-full flex flex-col items-center justify-center p-8">
                            <div class="w-20 h-20 bg-white/25 backdrop-blur-md rounded-3xl flex items-center justify-center mb-4 shadow-xl border border-white/30">
                                <i class="fas fa-rocket text-white text-4xl"></i>
                            </div>
                            <h3 class="text-3xl font-bold text-white drop-shadow-lg">Theme 4</h3>
                        </div>
                    </div>

                    {{-- Content --}}
                    <div class="p-8">
                        <span class="inline-block px-3 py-1 bg-gradient-to-r from-purple-500 to-orange-500 text-white text-xs font-bold rounded-full mb-4 shadow-lg">
                            Ultimate Fusion
                        </span>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                            Ultimate Fusion
                        </h2>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">
                            รวม Best of All Worlds: สีสรร Gradient + ความใส Glass + ปุ่ม Neumorphic
                        </p>

                        <div class="space-y-3 mb-6">
                            <div class="flex items-center gap-2 text-sm">
                                <i class="fas fa-check-circle text-purple-600 dark:text-purple-400"></i>
                                <span class="text-gray-700 dark:text-gray-300">Gradient Colors (Theme 1)</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <i class="fas fa-check-circle text-pink-600 dark:text-pink-400"></i>
                                <span class="text-gray-700 dark:text-gray-300">Glass Effect (Theme 2)</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <i class="fas fa-check-circle text-orange-600 dark:text-orange-400"></i>
                                <span class="text-gray-700 dark:text-gray-300">Neumorphic Buttons (Theme 3)</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <a href="{{ route('demo.dashboard4') }}"
                               class="block px-4 py-3 bg-gradient-to-r from-purple-500 to-orange-500 text-white text-center font-bold rounded-xl hover:shadow-lg transform hover:scale-105 transition-all">
                                <i class="fas fa-th-large mr-2"></i>
                                Dashboard
                            </a>
                            <a href="{{ route('demo.dashboard4') }}"
                               class="block px-4 py-3 border-2 border-purple-500 dark:border-purple-400 text-purple-600 dark:text-purple-400 text-center font-bold rounded-xl hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-all">
                                <i class="fas fa-eye mr-2"></i>
                                Preview
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Features Section --}}
    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-12 mb-16 animate-fade-in border border-gray-200 dark:border-gray-700">
        <h2 class="text-3xl font-bold text-center text-gray-900 dark:text-white mb-12">
            เทคโนโลยีที่ใช้ใน V3
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="w-20 h-20 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <i class="fab fa-css3-alt text-white text-4xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Tailwind CSS</h3>
                <p class="text-gray-600 dark:text-gray-400">Utility-first CSS framework เร็ว ยืดหยุ่น ปรับแต่งได้ง่าย</p>
            </div>

            <div class="text-center">
                <div class="w-20 h-20 bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <i class="fas fa-mountain text-white text-4xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Alpine.js</h3>
                <p class="text-gray-600 dark:text-gray-400">Lightweight JavaScript framework แค่ ~15KB เบาและเร็ว</p>
            </div>

            <div class="text-center">
                <div class="w-20 h-20 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <i class="fas fa-arrows-alt text-white text-4xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">SortableJS</h3>
                <p class="text-gray-600 dark:text-gray-400">Modern drag & drop library รองรับ touch devices</p>
            </div>
        </div>
    </div>

    {{-- CTA Section --}}
    <div class="relative overflow-hidden rounded-3xl animate-fade-in">
        <div class="absolute inset-0 bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600"></div>
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_50%,rgba(255,255,255,0.1),transparent_50%)] opacity-50"></div>

        <div class="relative z-10 text-center py-20 px-6">
            <h2 class="text-4xl md:text-5xl font-bold text-white mb-6">
                เลือก Theme ที่คุณชอบ
            </h2>
            <p class="text-xl text-white/90 mb-8 max-w-2xl mx-auto">
                เริ่มพัฒนาโปรเจคของคุณด้วย Theme ที่สวยงามและทันสมัยที่สุด
            </p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="https://github.com/xjanova/Thaiprompt-Affiliate" target="_blank"
                   class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-white text-purple-600 rounded-xl font-bold hover:shadow-2xl transform hover:scale-105 transition-all">
                    <i class="fab fa-github"></i>
                    <span>View on GitHub</span>
                </a>
                <a href="/demo/theme1"
                   class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-white/10 backdrop-blur-sm border-2 border-white text-white rounded-xl font-bold hover:bg-white/20 transition-all">
                    <i class="fas fa-play"></i>
                    <span>ดู Theme 1</span>
                </a>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
console.log('V3 UI Themes Gallery loaded');
</script>
@endpush
