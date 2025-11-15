@extends('layouts.admin')

@section('title', 'Arrow X Theme System')

@section('content')
<div class="container-fluid px-4 py-8" x-data="arrowXDashboard()">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">
                    🚀 Arrow X Theme System
                </h1>
                <p class="text-gray-600 dark:text-gray-400">
                    ระบบ Theme ที่ปรับแต่งได้ทุกอย่าง - Version {{ $themeSetting->theme_version }}
                </p>
            </div>
            <div class="flex gap-3">
                <button @click="previewTheme" class="px-6 py-3 bg-gradient-to-r from-purple-500 via-pink-500 to-orange-500 text-white rounded-xl hover:shadow-lg transition">
                    <i class="fas fa-eye mr-2"></i>
                    Preview Theme
                </button>
            </div>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 p-6 rounded-2xl border border-purple-200 dark:border-purple-700">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-palette text-white text-xl"></i>
                </div>
                <span class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $themeSetting->color ? '1' : '0' }}</span>
            </div>
            <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400">Color Schemes</h3>
        </div>

        <div class="bg-gradient-to-br from-pink-50 to-pink-100 dark:from-pink-900/20 dark:to-pink-800/20 p-6 rounded-2xl border border-pink-200 dark:border-pink-700">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-pink-500 to-pink-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-bolt text-white text-xl"></i>
                </div>
                <span class="text-2xl font-bold text-pink-600 dark:text-pink-400">{{ $themeSetting->rgbEffects->count() }}</span>
            </div>
            <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400">RGB Effects</h3>
        </div>

        <div class="bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20 p-6 rounded-2xl border border-orange-200 dark:border-orange-700">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-font text-white text-xl"></i>
                </div>
                <span class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $themeSetting->typography ? '1' : '0' }}</span>
            </div>
            <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400">Typography Sets</h3>
        </div>

        <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 p-6 rounded-2xl border border-blue-200 dark:border-blue-700">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-cubes text-white text-xl"></i>
                </div>
                <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $themeSetting->components->count() }}</span>
            </div>
            <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400">Components</h3>
        </div>
    </div>

    {{-- Settings Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {{-- General Settings --}}
        <a href="{{ route('admin.arrow-x-theme.general-settings') }}" class="group block">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 hover:shadow-xl transition-all hover:scale-105 border border-gray-200 dark:border-gray-700">
                <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <i class="fas fa-cog text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">การตั้งค่าทั่วไป</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">
                    โลโก้, ขนาด, ความโปร่งใส, Layout
                </p>
                <div class="flex items-center text-purple-600 dark:text-purple-400 font-semibold text-sm">
                    <span>ตั้งค่า</span>
                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        {{-- Color Settings --}}
        <a href="{{ route('admin.arrow-x-theme.color-settings') }}" class="group block">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 hover:shadow-xl transition-all hover:scale-105 border border-gray-200 dark:border-gray-700">
                <div class="w-16 h-16 bg-gradient-to-br from-pink-500 to-pink-600 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <i class="fas fa-palette text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ตั้งค่าสี</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">
                    Gradient Colors, สีหลัก, สีสถานะ
                </p>
                <div class="flex items-center text-pink-600 dark:text-pink-400 font-semibold text-sm">
                    <span>ตั้งค่า</span>
                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        {{-- RGB Effects --}}
        <a href="{{ route('admin.arrow-x-theme.rgb-effects') }}" class="group block">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 hover:shadow-xl transition-all hover:scale-105 border border-gray-200 dark:border-gray-700">
                <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <i class="fas fa-bolt text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">RGB Effects</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">
                    เอฟเฟกต์เรืองแสง, Active States
                </p>
                <div class="flex items-center text-orange-600 dark:text-orange-400 font-semibold text-sm">
                    <span>ตั้งค่า</span>
                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        {{-- Typography --}}
        <a href="{{ route('admin.arrow-x-theme.typography') }}" class="group block">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 hover:shadow-xl transition-all hover:scale-105 border border-gray-200 dark:border-gray-700">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <i class="fas fa-font text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ตั้งค่าฟอนต์</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">
                    Font Family, ขนาด, น้ำหนัก
                </p>
                <div class="flex items-center text-blue-600 dark:text-blue-400 font-semibold text-sm">
                    <span>ตั้งค่า</span>
                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        {{-- Cache Management --}}
        <div class="group" x-data="{ showActions: false }">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                <div class="w-16 h-16 bg-gradient-to-br from-teal-500 to-teal-600 rounded-2xl flex items-center justify-center mb-4">
                    <i class="fas fa-database text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Cache Management</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">
                    Compile, Cache, Optimize
                </p>

                {{-- Action Buttons --}}
                <div class="space-y-2">
                    <form action="{{ route('admin.arrow-x-theme.compile') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full px-4 py-2 bg-teal-100 dark:bg-teal-900/30 text-teal-800 dark:text-teal-300 rounded-lg text-sm hover:bg-teal-200 dark:hover:bg-teal-900/50 transition">
                            <i class="fas fa-sync mr-2"></i> Compile Theme
                        </button>
                    </form>

                    <form action="{{ route('admin.arrow-x-theme.clear-cache') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full px-4 py-2 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 rounded-lg text-sm hover:bg-red-200 dark:hover:bg-red-900/50 transition">
                            <i class="fas fa-trash mr-2"></i> Clear Cache
                        </button>
                    </form>

                    <form action="{{ route('admin.arrow-x-theme.compile-files') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full px-4 py-2 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-lg text-sm hover:bg-blue-200 dark:hover:bg-blue-900/50 transition">
                            <i class="fas fa-file-code mr-2"></i> Compile to Files
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Google Translate & Language Switcher --}}
        <div class="group">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <i class="fas fa-globe text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Multi-Language</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">
                    14 ภาษา, Auto-Translate, Caching
                </p>

                {{-- Language Switcher Demo --}}
                <div class="mb-3">
                    <x-arrow-x.language-switcher variant="dropdown" />
                </div>

                <div class="inline-block px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 text-xs rounded-full">
                    ✅ Active
                </div>
            </div>
        </div>

        {{-- Documentation --}}
        <a href="/.claude/ARROW_X_THEME_PLAN.md" target="_blank" class="group block">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 hover:shadow-xl transition-all hover:scale-105 border border-gray-200 dark:border-gray-700">
                <div class="w-16 h-16 bg-gradient-to-br from-gray-500 to-gray-600 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <i class="fas fa-book text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">เอกสาร</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">
                    Arrow X Theme Plan Documentation
                </p>
                <div class="flex items-center text-gray-600 dark:text-gray-400 font-semibold text-sm">
                    <span>อ่านเอกสาร</span>
                    <i class="fas fa-external-link-alt ml-2"></i>
                </div>
            </div>
        </a>
    </div>
</div>

<script>
function arrowXDashboard() {
    return {
        previewTheme() {
            window.open('{{ route('demo.dashboard4') }}', '_blank');
        }
    }
}
</script>
@endsection
