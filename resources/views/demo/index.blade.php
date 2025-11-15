@extends('demo.layout')

@section('title', 'Arrow X Theme System')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    {{-- Hero Section --}}
    <div class="text-center mb-16 animate-fade-in">
        <div class="inline-block mb-4 px-6 py-2 bg-gradient-to-r from-purple-500 via-pink-500 to-orange-500 text-white rounded-full text-sm font-semibold shadow-lg">
            Arrow X Theme System v1.0
        </div>
        <h1 class="text-5xl md:text-7xl font-bold mb-6 bg-gradient-to-r from-purple-600 via-pink-600 to-orange-600 bg-clip-text text-transparent">
            🚀 Arrow X Theme System
        </h1>
        <p class="text-xl md:text-2xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
            ระบบ Theme ที่ปรับแต่งได้ทุกอย่างสำหรับ Thaiprompt-Affiliate
            <br>
            <span class="text-lg font-semibold mt-2 block">
                <span class="text-purple-600 dark:text-purple-400">Tailwind CSS</span> +
                <span class="text-pink-600 dark:text-pink-400">Alpine.js</span> +
                <span class="text-orange-600 dark:text-orange-400">SortableJS</span> +
                <span class="text-blue-600 dark:text-blue-400">RGB Effects</span>
            </span>
        </p>
    </div>

    {{-- Features Overview --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-16">
        <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 p-6 rounded-2xl border border-purple-200 dark:border-purple-700">
            <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mb-4">
                <i class="fas fa-palette text-white text-xl"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">ปรับแต่งได้ทุกอย่าง</h3>
            <p class="text-gray-600 dark:text-gray-400 text-sm">
                สี, ฟอนต์, โลโก้, ขนาด, ความโปร่งใส, และอื่นๆ อีกมากมาย
            </p>
        </div>

        <div class="bg-gradient-to-br from-pink-50 to-pink-100 dark:from-pink-900/20 dark:to-pink-800/20 p-6 rounded-2xl border border-pink-200 dark:border-pink-700">
            <div class="w-12 h-12 bg-gradient-to-br from-pink-500 to-pink-600 rounded-xl flex items-center justify-center mb-4">
                <i class="fas fa-magic text-white text-xl"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">RGB Effects System</h3>
            <p class="text-gray-600 dark:text-gray-400 text-sm">
                เอฟเฟกต์ RGB เรืองแสงสำหรับเมนู, ปุ่ม, และ Active States
            </p>
        </div>

        <div class="bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20 p-6 rounded-2xl border border-orange-200 dark:border-orange-700">
            <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl flex items-center justify-center mb-4">
                <i class="fas fa-globe text-white text-xl"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Multi-Language</h3>
            <p class="text-gray-600 dark:text-gray-400 text-sm">
                Google Translate API อัตโนมัติ รองรับ 100+ ภาษา
            </p>
        </div>
    </div>

    {{-- Arrow X Theme Card --}}
    <div class="max-w-4xl mx-auto">
        <div class="group perspective-1000 animate-scale-in">
            <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105">
                {{-- Ultimate Fusion Glow Effect --}}
                <div class="absolute inset-0 bg-gradient-to-br from-purple-500 via-pink-500 to-orange-500 rounded-3xl blur-3xl opacity-60 group-hover:opacity-90 transition-opacity animate-pulse-slow"></div>

                {{-- Card --}}
                <div class="relative bg-white dark:bg-gray-800 rounded-3xl shadow-2xl overflow-hidden border border-gray-200 dark:border-gray-700">

                    {{-- Preview Image with Glass Effect --}}
                    <div class="relative h-80 bg-gradient-to-br from-purple-500 via-pink-500 to-orange-500 overflow-hidden">
                        {{-- Animated Background Patterns --}}
                        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxwYXRoIGQ9Ik0zNiAxOGMzLjMxNCAwIDYgMi42ODYgNiA2cy0yLjY4NiA2LTYgNi02LTIuNjg2LTYtNiAyLjY4Ni02IDYtNiIgc3Ryb2tlPSIjZmZmIiBzdHJva2Utd2lkdGg9IjIiIG9wYWNpdHk9Ii4xIi8+PC9nPjwvc3ZnPg==')] opacity-20 animate-spin-slow"></div>

                        {{-- Glass Orbs --}}
                        <div class="absolute top-1/4 left-1/4 w-40 h-40 bg-white/20 rounded-full blur-3xl animate-float"></div>
                        <div class="absolute bottom-1/4 right-1/4 w-40 h-40 bg-white/20 rounded-full blur-3xl animate-float-delayed"></div>

                        {{-- Content --}}
                        <div class="relative z-10 h-full flex flex-col items-center justify-center p-8">
                            {{-- Icon with Glass Effect --}}
                            <div class="w-32 h-32 bg-white/20 backdrop-blur-xl rounded-3xl flex items-center justify-center mb-6 shadow-2xl transform group-hover:scale-110 transition-transform duration-500 border border-white/30">
                                <i class="fas fa-rocket text-white text-5xl drop-shadow-lg"></i>
                            </div>

                            <h3 class="text-4xl md:text-5xl font-black text-white mb-3 drop-shadow-2xl">
                                Arrow X
                            </h3>
                            <p class="text-white/90 text-lg font-medium drop-shadow-lg">
                                Ultimate Theme System
                            </p>
                        </div>
                    </div>

                    {{-- Content --}}
                    <div class="p-10">
                        <div class="flex items-center gap-3 mb-6">
                            <span class="inline-block px-4 py-2 bg-gradient-to-r from-purple-500 via-pink-500 to-orange-500 text-white text-sm font-bold rounded-full shadow-lg">
                                ⭐ Ultimate Fusion
                            </span>
                            <span class="inline-block px-4 py-2 bg-gradient-to-r from-blue-500 to-cyan-500 text-white text-sm font-bold rounded-full shadow-lg">
                                <i class="fas fa-bolt mr-1"></i> RGB Effects
                            </span>
                        </div>

                        <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                            Arrow X Theme System
                        </h3>

                        <p class="text-gray-600 dark:text-gray-400 mb-8 text-lg leading-relaxed">
                            ระบบ Theme ที่รวมทุกสิ่งที่ดีที่สุด: <strong>สีสัน Gradient ที่สวยงาม</strong>,
                            <strong>Glass Effects ความใส</strong>, <strong>Neumorphic 3D Buttons</strong>,
                            และ <strong>RGB Lighting Effects</strong> ที่ปรับแต่งได้ทุกอย่าง
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                            {{-- Features List Column 1 --}}
                            <div class="space-y-3 text-sm">
                                <div class="flex items-start gap-3">
                                    <div class="w-6 h-6 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                                        <i class="fas fa-check text-white text-xs"></i>
                                    </div>
                                    <div>
                                        <span class="font-semibold text-gray-900 dark:text-white">Gradient Colors</span>
                                        <p class="text-gray-600 dark:text-gray-400 text-xs">สีสรรสวยงาม ปรับแต่งได้</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <div class="w-6 h-6 bg-gradient-to-br from-pink-500 to-orange-500 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                                        <i class="fas fa-check text-white text-xs"></i>
                                    </div>
                                    <div>
                                        <span class="font-semibold text-gray-900 dark:text-white">Glass Transparency</span>
                                        <p class="text-gray-600 dark:text-gray-400 text-xs">ความโปร่งใส 0-100%</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <div class="w-6 h-6 bg-gradient-to-br from-orange-500 to-yellow-500 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                                        <i class="fas fa-check text-white text-xs"></i>
                                    </div>
                                    <div>
                                        <span class="font-semibold text-gray-900 dark:text-white">Neumorphic 3D</span>
                                        <p class="text-gray-600 dark:text-gray-400 text-xs">ปุ่มและการ์ด 3D</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <div class="w-6 h-6 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                                        <i class="fas fa-check text-white text-xs"></i>
                                    </div>
                                    <div>
                                        <span class="font-semibold text-gray-900 dark:text-white">RGB Effects</span>
                                        <p class="text-gray-600 dark:text-gray-400 text-xs">เรืองแสงหลายแบบ</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Features List Column 2 --}}
                            <div class="space-y-3 text-sm">
                                <div class="flex items-start gap-3">
                                    <div class="w-6 h-6 bg-gradient-to-br from-green-500 to-emerald-500 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                                        <i class="fas fa-check text-white text-xs"></i>
                                    </div>
                                    <div>
                                        <span class="font-semibold text-gray-900 dark:text-white">Active State RGB</span>
                                        <p class="text-gray-600 dark:text-gray-400 text-xs">เมนู/ปุ่ม Active เรืองแสง</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <div class="w-6 h-6 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                                        <i class="fas fa-check text-white text-xs"></i>
                                    </div>
                                    <div>
                                        <span class="font-semibold text-gray-900 dark:text-white">Dark/Light Mode</span>
                                        <p class="text-gray-600 dark:text-gray-400 text-xs">รองรับทั้ง 2 โหมด</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <div class="w-6 h-6 bg-gradient-to-br from-rose-500 to-pink-500 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                                        <i class="fas fa-check text-white text-xs"></i>
                                    </div>
                                    <div>
                                        <span class="font-semibold text-gray-900 dark:text-white">Fully Responsive</span>
                                        <p class="text-gray-600 dark:text-gray-400 text-xs">Mobile-first design</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <div class="w-6 h-6 bg-gradient-to-br from-violet-500 to-fuchsia-500 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                                        <i class="fas fa-check text-white text-xs"></i>
                                    </div>
                                    <div>
                                        <span class="font-semibold text-gray-900 dark:text-white">Google Translate</span>
                                        <p class="text-gray-600 dark:text-gray-400 text-xs">รองรับ 100+ ภาษา</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex gap-4">
                            <a href="{{ route('demo.dashboard4') }}"
                               class="flex-1 px-6 py-4 bg-gradient-to-r from-purple-500 via-pink-500 to-orange-500 text-white text-center font-bold rounded-2xl hover:shadow-2xl transform hover:scale-105 transition-all duration-300 flex items-center justify-center gap-2 group">
                                <i class="fas fa-th-large group-hover:rotate-12 transition-transform"></i>
                                <span>View Dashboard</span>
                            </a>
                        </div>

                        {{-- Info Footer --}}
                        <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                                <span>
                                    <i class="fas fa-code mr-2 text-purple-500"></i>
                                    Built with Tailwind CSS + Alpine.js
                                </span>
                                <span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-3 py-1 rounded-full">
                                    v1.0.0
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Documentation Link --}}
    <div class="text-center mt-16">
        <p class="text-gray-600 dark:text-gray-400 mb-4">
            อ่านเอกสารฉบับเต็ม
        </p>
        <a href="/.claude/ARROW_X_THEME_PLAN.md"
           class="inline-flex items-center gap-2 px-6 py-3 bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
            <i class="fas fa-book"></i>
            <span class="font-semibold">Arrow X Theme Plan Documentation</span>
        </a>
    </div>

</div>

<style>
@keyframes float {
    0%, 100% { transform: translateY(0) translateX(0); }
    50% { transform: translateY(-20px) translateX(10px); }
}

@keyframes float-delayed {
    0%, 100% { transform: translateY(0) translateX(0); }
    50% { transform: translateY(20px) translateX(-10px); }
}

@keyframes spin-slow {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@keyframes pulse-slow {
    0%, 100% { opacity: 0.6; }
    50% { opacity: 0.9; }
}

.animate-float {
    animation: float 6s ease-in-out infinite;
}

.animate-float-delayed {
    animation: float-delayed 8s ease-in-out infinite;
}

.animate-spin-slow {
    animation: spin-slow 20s linear infinite;
}

.animate-pulse-slow {
    animation: pulse-slow 3s ease-in-out infinite;
}

.animate-fade-in {
    animation: fadeIn 0.6s ease-out;
}

.animate-scale-in {
    animation: scaleIn 0.6s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes scaleIn {
    from { opacity: 0; transform: scale(0.9); }
    to { opacity: 1; transform: scale(1); }
}

.perspective-1000 {
    perspective: 1000px;
}
</style>
@endsection
