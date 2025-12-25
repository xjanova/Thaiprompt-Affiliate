@extends('layouts.admin-v3')

@section('title', 'จัดการหมวดหมู่')

@section('content')
<div class="space-y-6">
    {{-- Premium Hero Header (Teal-Cyan-Blue for Categories) --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-teal-600 via-cyan-600 to-blue-600 dark:from-teal-800 dark:via-cyan-800 dark:to-blue-800 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>

        {{-- Floating Icons --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-folder-open"></i>
            </div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="glass-fusion p-4 rounded-2xl">
                        <i class="fas fa-folder-open text-4xl text-white drop-shadow-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-4xl font-bold text-white drop-shadow-lg">จัดการหมวดหมู่</h1>
                        <p class="text-teal-100 text-lg mt-1">จัดระเบียบเนื้อหาการเรียนรู้</p>
                    </div>
                </div>
                <a href="{{ route('admin.categories.create') }}"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <i class="fas fa-plus"></i>
                    สร้างหมวดหมู่ใหม่
                </a>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Total Categories Card (Teal-Cyan) --}}
        <div class="group relative overflow-hidden rounded-2xl shadow-xl p-6 transform hover:scale-105 transition-all duration-300 cursor-pointer bg-gradient-to-br from-teal-500 to-cyan-600 dark:from-teal-600 dark:to-cyan-800">
            <div class="absolute -right-8 -top-8 opacity-10">
                <i class="fas fa-folder text-9xl text-white"></i>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-5xl">📂</span>
                    <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                        <i class="fas fa-folder text-white text-xl"></i>
                    </div>
                </div>
                <p class="text-3xl font-bold text-white mb-1">{{ $categories->count() }}</p>
                <p class="text-sm text-teal-100">หมวดหมู่ทั้งหมด</p>
            </div>
        </div>

        {{-- Active Categories Card (Green-Emerald) --}}
        <div class="group relative overflow-hidden rounded-2xl shadow-xl p-6 transform hover:scale-105 transition-all duration-300 cursor-pointer bg-gradient-to-br from-green-500 to-emerald-700 dark:from-green-600 dark:to-emerald-900">
            <div class="absolute -right-8 -top-8 opacity-10">
                <i class="fas fa-check-circle text-9xl text-white"></i>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-5xl">✅</span>
                    <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                        <i class="fas fa-check-circle text-white text-xl"></i>
                    </div>
                </div>
                <p class="text-3xl font-bold text-white mb-1">{{ $categories->where('is_active', true)->count() }}</p>
                <p class="text-sm text-green-100">เปิดใช้งาน</p>
            </div>
        </div>

        {{-- Total Articles Card (Blue-Indigo) --}}
        <div class="group relative overflow-hidden rounded-2xl shadow-xl p-6 transform hover:scale-105 transition-all duration-300 cursor-pointer bg-gradient-to-br from-blue-500 to-indigo-600 dark:from-blue-600 dark:to-indigo-800">
            <div class="absolute -right-8 -top-8 opacity-10">
                <i class="fas fa-book text-9xl text-white"></i>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-5xl">📚</span>
                    <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                        <i class="fas fa-book text-white text-xl"></i>
                    </div>
                </div>
                <p class="text-3xl font-bold text-white mb-1">{{ $categories->sum('articles_count') }}</p>
                <p class="text-sm text-blue-100">บทความทั้งหมด</p>
            </div>
        </div>
    </div>

    {{-- Categories List --}}
    <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-8">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <i class="fas fa-list text-teal-600 dark:text-teal-400"></i>
            รายการหมวดหมู่
        </h2>

        @if($categories->count() > 0)
            <div class="space-y-4">
                @foreach($categories as $category)
                    <div class="bg-white/60 dark:bg-white/10 backdrop-blur-sm border-2 border-gray-200 dark:border-white/20 rounded-xl p-6 hover:bg-white/80 dark:hover:bg-white/15 transition-all duration-200">
                        <div class="flex items-center gap-4">
                            {{-- Category Icon --}}
                            <div class="flex-shrink-0">
                                <div class="w-16 h-16 rounded-xl flex items-center justify-center text-3xl shadow-lg" style="background: {{ $category->color ?? '#e5e7eb' }}22;">
                                    {{ $category->icon ?? '📚' }}
                                </div>
                            </div>

                            {{-- Category Info --}}
                            <div class="flex-1">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">{{ $category->name }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $category->description }}</p>
                            </div>

                            {{-- Articles Count Badge --}}
                            <div class="flex-shrink-0">
                                <span class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-100 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30 text-blue-700 dark:text-blue-300 rounded-lg font-semibold">
                                    <i class="fas fa-book"></i>
                                    {{ $category->articles_count }} บทความ
                                </span>
                            </div>

                            {{-- Status Badge --}}
                            <div class="flex-shrink-0">
                                @if($category->is_active)
                                    <span class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 text-green-700 dark:text-green-300 rounded-lg font-semibold">
                                        <i class="fas fa-check-circle"></i>
                                        เปิดใช้งาน
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-red-100 to-rose-100 dark:from-red-900/30 dark:to-rose-900/30 text-red-700 dark:text-red-300 rounded-lg font-semibold">
                                        <i class="fas fa-times-circle"></i>
                                        ปิดใช้งาน
                                    </span>
                                @endif
                            </div>

                            {{-- Action Buttons --}}
                            <div class="flex-shrink-0 flex items-center gap-2">
                                <a href="{{ route('admin.categories.edit', $category) }}"
                                   class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white rounded-lg transition-all duration-200 shadow-md hover:shadow-lg">
                                    <i class="fas fa-edit"></i>
                                    แก้ไข
                                </a>

                                @if($category->articles_count == 0)
                                    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}"
                                          onsubmit="return confirm('ต้องการลบหมวดหมู่นี้?');"
                                          class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-red-500 to-rose-600 hover:from-red-600 hover:to-rose-700 text-white rounded-lg transition-all duration-200 shadow-md hover:shadow-lg">
                                            <i class="fas fa-trash"></i>
                                            ลบ
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            {{-- Empty State --}}
            <div class="text-center py-16">
                <div class="inline-block p-6 bg-gradient-to-br from-teal-100 to-cyan-100 dark:from-teal-900/20 dark:to-cyan-900/20 rounded-full mb-6">
                    <i class="fas fa-folder-open text-6xl text-teal-600 dark:text-teal-400"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">ยังไม่มีหมวดหมู่</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">สร้างหมวดหมู่แรกของคุณเพื่อจัดระเบียบเนื้อหา</p>
                <a href="{{ route('admin.categories.create') }}"
                   style="background: var(--arrow-x-primary-gradient)"
                   class="inline-flex items-center gap-2 px-6 py-3 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:-translate-y-0.5">
                    <i class="fas fa-plus"></i>
                    สร้างหมวดหมู่ใหม่
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
