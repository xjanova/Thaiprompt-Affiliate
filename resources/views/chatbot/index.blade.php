@extends('layouts.app')

@section('title', 'จัดการบอทแชท')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-purple-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Hero Header with Gradient --}}
        <div class="relative overflow-hidden rounded-3xl mb-8">
            {{-- Background Gradient --}}
            <div class="absolute inset-0 bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600"></div>

            {{-- Decorative Elements --}}
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl transform translate-x-1/2 -translate-y-1/2"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-purple-400/20 rounded-full blur-2xl transform -translate-x-1/2 translate-y-1/2"></div>

            {{-- Content --}}
            <div class="relative px-8 py-12 md:py-16">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                                <i class="fas fa-robot text-white text-2xl"></i>
                            </div>
                            <span class="px-3 py-1 bg-white/20 backdrop-blur-sm text-white text-sm font-medium rounded-full">
                                AI Chatbot System
                            </span>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">
                            จัดการบอทแชท
                        </h1>
                        <p class="text-white/80 text-lg">
                            ระบบให้เช่าบอทแชทอัจฉริยะแบบไฮบริด
                        </p>
                    </div>

                    <a href="{{ route('chatbot.create') }}"
                       class="group relative inline-flex items-center gap-2 px-8 py-4 bg-white text-purple-600 font-bold rounded-2xl shadow-xl hover:shadow-2xl transform hover:scale-105 transition-all duration-300 overflow-hidden">
                        {{-- Shine Effect --}}
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-purple-100 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                        <span class="relative z-10 flex items-center gap-2">
                            <i class="fas fa-plus"></i>
                            <span>สร้างบอทใหม่</span>
                        </span>
                    </a>
                </div>
            </div>
        </div>

        {{-- Stats Cards Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            {{-- Total Bots --}}
            <div class="group relative">
                <div class="absolute inset-0 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-2xl blur-lg opacity-30 group-hover:opacity-50 transition-opacity"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-all duration-300">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 dark:text-gray-400 text-sm font-medium mb-1">บอททั้งหมด</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_bots']) }}</p>
                        </div>
                        <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-2xl flex items-center justify-center shadow-lg transform group-hover:scale-110 group-hover:rotate-6 transition-transform duration-300">
                            <i class="fas fa-robot text-white text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            <i class="fas fa-chart-line text-blue-500 mr-1"></i>
                            ทั้งหมดในระบบ
                        </span>
                    </div>
                </div>
            </div>

            {{-- Active Bots --}}
            <div class="group relative">
                <div class="absolute inset-0 bg-gradient-to-br from-green-500 to-emerald-500 rounded-2xl blur-lg opacity-30 group-hover:opacity-50 transition-opacity"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-all duration-300">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 dark:text-gray-400 text-sm font-medium mb-1">บอทที่ใช้งาน</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['active_bots']) }}</p>
                        </div>
                        <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-emerald-500 rounded-2xl flex items-center justify-center shadow-lg transform group-hover:scale-110 group-hover:rotate-6 transition-transform duration-300">
                            <i class="fas fa-check-circle text-white text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <span class="text-sm text-green-600 dark:text-green-400 font-medium">
                            <i class="fas fa-circle text-xs mr-1 animate-pulse"></i>
                            กำลังทำงาน
                        </span>
                    </div>
                </div>
            </div>

            {{-- Total Rentals --}}
            <div class="group relative">
                <div class="absolute inset-0 bg-gradient-to-br from-purple-500 to-pink-500 rounded-2xl blur-lg opacity-30 group-hover:opacity-50 transition-opacity"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-all duration-300">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 dark:text-gray-400 text-sm font-medium mb-1">การเช่าทั้งหมด</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_rentals']) }}</p>
                        </div>
                        <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-pink-500 rounded-2xl flex items-center justify-center shadow-lg transform group-hover:scale-110 group-hover:rotate-6 transition-transform duration-300">
                            <i class="fas fa-handshake text-white text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            <i class="fas fa-users text-purple-500 mr-1"></i>
                            ผู้เช่าทั้งหมด
                        </span>
                    </div>
                </div>
            </div>

            {{-- Total Revenue --}}
            <div class="group relative">
                <div class="absolute inset-0 bg-gradient-to-br from-amber-500 to-orange-500 rounded-2xl blur-lg opacity-30 group-hover:opacity-50 transition-opacity"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-all duration-300">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 dark:text-gray-400 text-sm font-medium mb-1">รายได้ทั้งหมด</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">฿{{ number_format($stats['total_revenue'], 2) }}</p>
                        </div>
                        <div class="w-14 h-14 bg-gradient-to-br from-amber-500 to-orange-500 rounded-2xl flex items-center justify-center shadow-lg transform group-hover:scale-110 group-hover:rotate-6 transition-transform duration-300">
                            <i class="fas fa-coins text-white text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <span class="text-sm text-amber-600 dark:text-amber-400 font-medium">
                            <i class="fas fa-arrow-up mr-1"></i>
                            รายได้สะสม
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section Title --}}
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-th-large text-blue-500 mr-2"></i>
                บอทของคุณ
            </h2>
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $bots->total() }} บอท
                </span>
            </div>
        </div>

        {{-- Bots Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            @forelse($bots as $bot)
            <div class="group relative">
                {{-- Glow Effect --}}
                <div class="absolute inset-0 bg-gradient-to-br from-blue-500/20 to-purple-500/20 rounded-2xl blur-xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

                {{-- Card --}}
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300">
                    {{-- Header Gradient Bar --}}
                    <div class="h-2 bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500"></div>

                    <div class="p-6">
                        {{-- Bot Header --}}
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                                    <i class="fas fa-robot text-white text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white truncate">
                                        {{ $bot->display_name ?? $bot->name }}
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                        <i class="fas fa-microchip text-xs"></i>
                                        {{ $bot->provider->display_name ?? 'N/A' }}
                                    </p>
                                </div>
                            </div>

                            {{-- Status Badge --}}
                            @if($bot->is_active)
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-xs font-semibold rounded-full">
                                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                                ใช้งาน
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-xs font-semibold rounded-full">
                                <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                                ปิดใช้งาน
                            </span>
                            @endif
                        </div>

                        {{-- Description --}}
                        <p class="text-gray-600 dark:text-gray-400 text-sm mb-4 line-clamp-2 min-h-[2.5rem]">
                            {{ Str::limit($bot->description ?? 'ไม่มีคำอธิบาย', 80) }}
                        </p>

                        {{-- Model Info --}}
                        <div class="flex items-center gap-2 mb-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                            <i class="fas fa-brain text-purple-500"></i>
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                {{ $bot->model->display_name ?? 'N/A' }}
                            </span>
                        </div>

                        {{-- Stats Row --}}
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                                <div class="text-xl font-bold text-blue-600 dark:text-blue-400">
                                    {{ number_format($bot->conversations_count ?? 0) }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-comments mr-1"></i>บทสนทนา
                                </div>
                            </div>
                            <div class="text-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
                                <div class="text-xl font-bold text-purple-600 dark:text-purple-400">
                                    {{ number_format($bot->active_rentals_count ?? 0) }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-users mr-1"></i>การเช่า
                                </div>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex gap-2">
                            <a href="{{ route('chatbot.show', $bot->id) }}"
                               class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white font-medium rounded-xl hover:shadow-lg transform hover:scale-[1.02] transition-all duration-300">
                                <i class="fas fa-cog"></i>
                                <span>จัดการ</span>
                            </a>
                            <a href="{{ route('chatbot.playground', $bot->id) }}"
                               class="inline-flex items-center justify-center w-12 h-12 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                               title="ทดสอบบอท">
                                <i class="fas fa-play"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            {{-- Empty State --}}
            <div class="col-span-full">
                <div class="relative overflow-hidden rounded-3xl">
                    {{-- Background --}}
                    <div class="absolute inset-0 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-900"></div>

                    {{-- Content --}}
                    <div class="relative px-8 py-16 text-center">
                        <div class="w-24 h-24 bg-gradient-to-br from-blue-500 to-purple-600 rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-2xl">
                            <i class="fas fa-robot text-white text-4xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                            ยังไม่มีบอท
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-8 max-w-md mx-auto">
                            เริ่มต้นสร้างบอทแชทอัจฉริยะของคุณเลย เชื่อมต่อกับ LINE, Facebook และแพลตฟอร์มอื่นๆ
                        </p>
                        <a href="{{ route('chatbot.create') }}"
                           class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 text-white font-bold rounded-2xl shadow-xl hover:shadow-2xl transform hover:scale-105 transition-all duration-300">
                            <i class="fas fa-plus"></i>
                            <span>สร้างบอทแรกของคุณ</span>
                        </a>
                    </div>
                </div>
            </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($bots->hasPages())
        <div class="flex justify-center">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-4 border border-gray-100 dark:border-gray-700">
                {{ $bots->links() }}
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Custom Styles --}}
<style>
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>
@endsection
