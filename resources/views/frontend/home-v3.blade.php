{{--
/**
 * หน้าแรก (Homepage) - Version 3
 *
 * ออกแบบด้วย V3 Coding Guidelines:
 * - Tailwind CSS (pure utility-first)
 * - Alpine.js (reactive components)
 * - Glassmorphism & 3D effects
 * - Dark Mode support (100%)
 * - Mobile-first responsive
 *
 * Sections:
 * 1. Hero Section - Glassmorphism + 3D Logo + Particle Effects
 * 2. Stats Section - Animated Counters
 * 3. Features Section - 8 ระบบหลัก
 * 4. Product Showcase - AI Bots & Software
 * 5. How It Works - 4 ขั้นตอน
 * 6. Testimonials - รีวิวลูกค้า
 * 7. CTA Section - เชิญชวนลงทะเบียน
 *
 * @author Development Team
 * @version 3.0.0
 * @created 2025-11-17
 */
--}}

@extends('layouts.guest')

@section('title', 'หน้าแรก - Ecosystem แห่งรายได้ไม่จำกัด')

@section('content')

{{-- Main Container with Alpine.js --}}
<div x-data="homepageManager()" x-init="init()" class="min-h-screen">

    {{-- ================================================================
        FLOATING NAVIGATION BAR
        ================================================================ --}}
    <nav x-data="{ scrolled: false }"
         @scroll.window="scrolled = window.pageYOffset > 50"
         :class="scrolled ? 'bg-white/95 dark:bg-gray-900/95 shadow-lg' : 'bg-transparent'"
         class="fixed top-0 left-0 right-0 z-50 transition-all duration-300 backdrop-blur-md">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 md:h-20">

                {{-- Logo --}}
                <div class="flex items-center gap-3">
                    @if(\App\Models\Setting::get('logo'))
                        <img src="{{ asset(\App\Models\Setting::get('logo')) }}"
                             alt="Logo"
                             class="h-10 w-auto"
                             loading="lazy">
                    @else
                        <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg flex items-center justify-center">
                            <i class="fas fa-rocket text-white text-xl"></i>
                        </div>
                    @endif
                    <span class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ \App\Models\Setting::get('app_name', 'TP-Affiliate') }}
                    </span>
                </div>

                {{-- Navigation Links (Desktop) --}}
                <div class="hidden md:flex items-center gap-8">
                    <a href="#features" class="text-gray-700 dark:text-gray-300 hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
                        คุณสมบัติ
                    </a>
                    <a href="#stats" class="text-gray-700 dark:text-gray-300 hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
                        สถิติ
                    </a>
                    <a href="{{ route('about') }}" class="text-gray-700 dark:text-gray-300 hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
                        เกี่ยวกับเรา
                    </a>
                </div>

                {{-- Auth Buttons --}}
                <div class="flex items-center gap-3">
                    {{-- Dark Mode Toggle --}}
                    <button @click="darkMode = !darkMode"
                            class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        <i class="fas fa-moon text-gray-600 dark:text-gray-400" x-show="!darkMode"></i>
                        <i class="fas fa-sun text-yellow-400" x-show="darkMode" style="display: none;"></i>
                    </button>

                    @auth
                        {{-- Dashboard Link --}}
                        <a href="{{ route('user.dashboard') }}"
                           class="hidden sm:inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors">
                            <i class="fas fa-dashboard"></i>
                            <span>แดชบอร์ด</span>
                        </a>
                    @else
                        {{-- Login --}}
                        <a href="{{ route('login') }}"
                           class="hidden sm:inline-block px-4 py-2 text-gray-700 dark:text-gray-300 hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
                            เข้าสู่ระบบ
                        </a>

                        {{-- Register --}}
                        <a href="{{ route('register') }}"
                           class="px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-lg transition-all">
                            ลงทะเบียนฟรี
                        </a>
                    @endauth
                </div>

            </div>
        </div>
    </nav>

    {{-- ================================================================
        SECTION 1: HERO SECTION - Glassmorphism + 3D Effects
        ================================================================ --}}
    <section class="relative min-h-screen flex items-center justify-center overflow-hidden">

        {{-- Gradient Mesh Background --}}
        <div class="absolute inset-0 bg-gradient-to-br from-purple-500/20 via-pink-500/20 to-orange-500/20 dark:from-purple-900/30 dark:via-pink-900/30 dark:to-orange-900/30"></div>

        {{-- Animated Gradient Orbs --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute w-96 h-96 bg-gradient-to-r from-purple-400 to-pink-600 dark:from-purple-600 dark:to-pink-800 opacity-20 dark:opacity-10 rounded-full -top-20 -left-20 animate-pulse blur-3xl"></div>
            <div class="absolute w-80 h-80 bg-gradient-to-r from-blue-400 to-cyan-600 dark:from-blue-600 dark:to-cyan-800 opacity-20 dark:opacity-10 rounded-full top-40 right-20 animate-pulse blur-3xl" style="animation-delay: 1s;"></div>
            <div class="absolute w-64 h-64 bg-gradient-to-r from-pink-400 to-orange-600 dark:from-pink-600 dark:to-orange-800 opacity-20 dark:opacity-10 rounded-full bottom-20 left-1/3 animate-pulse blur-3xl" style="animation-delay: 2s;"></div>
        </div>

        {{-- Grid Pattern Overlay --}}
        <div class="absolute inset-0 opacity-5 dark:opacity-10" style="background-image: linear-gradient(rgba(255,255,255,.1) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.1) 1px, transparent 1px); background-size: 50px 50px;"></div>

        {{-- Floating Particles (Alpine.js Animated) --}}
        <template x-for="(particle, index) in particles" :key="index">
            <div class="absolute w-2 h-2 bg-yellow-400 dark:bg-yellow-500 rounded-full animate-ping pointer-events-none"
                 :style="`top: ${particle.top}%; left: ${particle.left}%; animation-delay: ${particle.delay}s;`"></div>
        </template>

        {{-- Hero Content --}}
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 text-center">

            {{-- 3D Logo with Glassmorphism --}}
            <div class="mb-12 animate-fade-in perspective-1000" x-show="isVisible" x-transition>
                <div class="inline-block p-8 backdrop-blur-xl bg-white/10 dark:bg-gray-900/10 border-2 border-white/20 dark:border-gray-700/30 rounded-3xl shadow-2xl transform hover:scale-105 hover:rotate-y-6 transition-all duration-500">
                    {{-- Logo (ถ้ามี) --}}
                    @if(\App\Models\Setting::get('logo'))
                        <img src="{{ asset(\App\Models\Setting::get('logo')) }}"
                             alt="Logo"
                             class="w-32 h-32 mx-auto drop-shadow-2xl"
                             loading="lazy">
                    @else
                        {{-- Fallback Icon --}}
                        <div class="w-32 h-32 mx-auto bg-gradient-to-br from-purple-500 to-pink-500 rounded-2xl flex items-center justify-center shadow-lg">
                            <i class="fas fa-rocket text-white text-6xl"></i>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Headline --}}
            <div class="animate-fade-in-up" x-show="isVisible" x-transition:enter.delay.100ms>
                <h1 class="text-5xl md:text-7xl lg:text-8xl font-black mb-6 leading-tight">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-600 via-pink-600 to-orange-600 dark:from-purple-400 dark:via-pink-400 dark:to-orange-400 animate-pulse">
                        Ecosystem แห่ง
                    </span><br>
                    <span class="text-gray-900 dark:text-white drop-shadow-2xl">
                        รายได้ไม่จำกัด
                    </span>
                </h1>

                <p class="text-xl md:text-2xl lg:text-3xl mb-12 text-gray-700 dark:text-gray-300 max-w-4xl mx-auto leading-relaxed">
                    แพลตฟอร์มครบวงจร • AI-Powered • Blockchain •
                    <span class="text-purple-600 dark:text-purple-400 font-bold">มั่นคง จ่ายจริง</span>
                </p>

                {{-- CTA Buttons --}}
                <div class="flex flex-col sm:flex-row gap-6 justify-center items-center mb-12">
                    {{-- Primary CTA --}}
                    <a href="{{ route('register') }}"
                       class="group relative overflow-hidden px-10 py-5 bg-gradient-to-r from-purple-600 via-pink-600 to-orange-600 hover:from-purple-700 hover:via-pink-700 hover:to-orange-700 text-white font-bold text-lg rounded-2xl shadow-2xl hover:shadow-purple-500/50 dark:hover:shadow-purple-400/30 transform hover:scale-105 transition-all duration-300">
                        {{-- Shine Effect --}}
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>

                        <span class="relative z-10 flex items-center gap-3">
                            <i class="fas fa-rocket"></i>
                            <span>เริ่มต้นฟรีวันนี้</span>
                            <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                        </span>
                    </a>

                    {{-- Secondary CTA --}}
                    <a href="#features"
                       class="px-10 py-5 border-2 border-purple-600 dark:border-purple-400 text-purple-600 dark:text-purple-400 hover:bg-purple-600 dark:hover:bg-purple-500 hover:text-white font-bold text-lg rounded-2xl transition-all duration-300">
                        <i class="fas fa-info-circle mr-2"></i>
                        เรียนรู้เพิ่มเติม
                    </a>
                </div>

                {{-- Trust Badges --}}
                <div class="flex flex-wrap gap-6 justify-center items-center text-sm text-gray-600 dark:text-gray-400">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-check-circle text-green-500"></i>
                        <span>ไม่มีค่าธรรมเนียมแรกเข้า</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-shield-alt text-blue-500"></i>
                        <span>ระบบรักษาความปลอดภัย SSL</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-users text-purple-500"></i>
                        <span x-text="`ผู้ใช้งาน ${stats.users.toLocaleString()}+ คน`"></span>
                    </div>
                </div>
            </div>

        </div>

        {{-- Scroll Down Indicator --}}
        <div class="absolute bottom-10 left-1/2 transform -translate-x-1/2 animate-bounce">
            <a href="#stats"
               class="flex flex-col items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
                <span class="text-sm font-medium">เลื่อนลงเพื่อดูเพิ่มเติม</span>
                <i class="fas fa-chevron-down text-2xl"></i>
            </a>
        </div>

    </section>

    {{-- ================================================================
        SECTION 2: STATS SECTION - Animated Counters
        ================================================================ --}}
    <section id="stats" class="relative py-20 bg-white dark:bg-gray-900">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Section Header --}}
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-black mb-4 text-gray-900 dark:text-white">
                    ตัวเลขที่พิสูจน์
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-400 dark:to-pink-400">
                        ความสำเร็จ
                    </span>
                </h2>
                <p class="text-xl text-gray-600 dark:text-gray-400">
                    ร่วมเป็นส่วนหนึ่งของชุมชนที่เติบโตอย่างต่อเนื่อง
                </p>
            </div>

            {{-- Stats Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">

                {{-- Stat Card Template --}}
                <template x-for="(stat, index) in statsData" :key="index">
                    <div class="group perspective-1000"
                         x-intersect="startCounter(stat)">
                        <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105 group-hover:rotate-y-3">

                            {{-- Glow Effect --}}
                            <div class="absolute inset-0 opacity-50 group-hover:opacity-75 transition-opacity rounded-2xl blur-xl"
                                 :class="stat.gradient"></div>

                            {{-- Card Body --}}
                            <div class="relative backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-2xl p-8">

                                {{-- Icon --}}
                                <div class="flex items-center justify-between mb-6">
                                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center shadow-lg"
                                         :class="stat.iconBg">
                                        <i :class="stat.icon" class="text-white text-3xl"></i>
                                    </div>

                                    {{-- Trend Badge --}}
                                    <span class="px-3 py-1 rounded-lg text-xs font-bold"
                                          :class="stat.trendColor"
                                          x-text="stat.trend"></span>
                                </div>

                                {{-- Value --}}
                                <h3 class="text-4xl font-black text-gray-900 dark:text-white mb-2">
                                    <span x-text="stat.prefix"></span>
                                    <span x-text="stat.displayValue.toLocaleString()"></span>
                                    <span x-text="stat.suffix"></span>
                                </h3>

                                {{-- Label --}}
                                <p class="text-sm text-gray-600 dark:text-gray-400"
                                   x-text="stat.label"></p>

                            </div>
                        </div>
                    </div>
                </template>

            </div>

        </div>

    </section>

    {{-- ================================================================
        SECTION 3: FEATURES SECTION - 8 ระบบหลัก
        ================================================================ --}}
    <section id="features" class="relative py-20 bg-gray-50 dark:bg-gray-800">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Section Header --}}
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-black mb-4 text-gray-900 dark:text-white">
                    ระบบครบวงจร
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-400 dark:to-pink-400">
                        ในที่เดียว
                    </span>
                </h2>
                <p class="text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
                    8 ระบบหลักที่ทรงพลัง ออกแบบมาเพื่อสร้างรายได้ให้คุณได้ทุกรูปแบบ
                </p>
            </div>

            {{-- Features Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">

                {{-- Feature Card Template --}}
                <template x-for="(feature, index) in features" :key="index">
                    <a :href="feature.link"
                       class="group relative overflow-hidden backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-lg hover:shadow-2xl p-8 transform hover:scale-105 hover:-translate-y-2 transition-all duration-300">

                        {{-- Gradient Overlay on Hover --}}
                        <div class="absolute inset-0 opacity-0 group-hover:opacity-20 rounded-2xl transition-opacity duration-300"
                             :class="feature.gradient"></div>

                        {{-- Content --}}
                        <div class="relative z-10">

                            {{-- Icon --}}
                            <div class="text-6xl mb-6 group-hover:scale-110 group-hover:rotate-6 transition-all duration-300"
                                 x-text="feature.icon"></div>

                            {{-- Title --}}
                            <h3 class="text-2xl font-bold mb-3 text-gray-900 dark:text-white"
                                x-text="feature.title"></h3>

                            {{-- Description --}}
                            <p class="text-gray-600 dark:text-gray-400 mb-4"
                               x-text="feature.description"></p>

                            {{-- CTA --}}
                            <div class="flex items-center gap-2 text-purple-600 dark:text-purple-400 font-semibold group-hover:gap-3 transition-all">
                                <span x-text="feature.cta"></span>
                                <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                            </div>

                            {{-- Badge (if new) --}}
                            <div x-show="feature.isNew"
                                 class="absolute -top-2 -right-2 bg-gradient-to-r from-yellow-400 to-orange-500 text-white text-xs font-bold px-3 py-1 rounded-full shadow-lg animate-pulse">
                                ใหม่!
                            </div>

                        </div>

                    </a>
                </template>

            </div>

        </div>

    </section>

    {{-- ================================================================
        SECTION 4: PRODUCT SHOWCASE (AI Bots & Software)
        ================================================================ --}}
    <section class="relative py-20 bg-white dark:bg-gray-900">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Section Header --}}
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-black mb-4 text-gray-900 dark:text-white">
                    สินค้า & บริการ
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-400 dark:to-pink-400">
                        ยอดนิยม
                    </span>
                </h2>
                <p class="text-xl text-gray-600 dark:text-gray-400">
                    เลือกซื้อสินค้าและบริการคุณภาพสูงจากเรา
                </p>
            </div>

            {{-- Product Carousel --}}
            <div class="relative" x-data="{ activeSlide: 0 }">

                {{-- Products Grid --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

                    <template x-for="(product, index) in showcaseProducts.slice(0, 6)" :key="index">
                        <div class="group relative backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-lg hover:shadow-2xl overflow-hidden transform hover:scale-105 transition-all duration-300">

                            {{-- Product Image --}}
                            <div class="relative h-48 bg-gradient-to-br overflow-hidden"
                                 :class="product.gradient">
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <i :class="product.icon" class="text-white text-6xl opacity-50"></i>
                                </div>
                            </div>

                            {{-- Product Info --}}
                            <div class="p-6">
                                <h3 class="text-xl font-bold mb-2 text-gray-900 dark:text-white"
                                    x-text="product.name"></h3>

                                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4"
                                   x-text="product.description"></p>

                                {{-- Price --}}
                                <div class="flex items-center justify-between mb-4">
                                    <div>
                                        <span class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                            ฿<span x-text="product.price.toLocaleString()"></span>
                                        </span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">
                                            / <span x-text="product.unit"></span>
                                        </span>
                                    </div>
                                </div>

                                {{-- CTA Button --}}
                                <a :href="product.link"
                                   class="block w-full px-4 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white text-center font-bold rounded-xl transition-all">
                                    <i class="fas fa-shopping-cart mr-2"></i>
                                    <span x-text="product.cta"></span>
                                </a>
                            </div>

                        </div>
                    </template>

                </div>

                {{-- View All Button --}}
                <div class="text-center mt-12">
                    <a href="{{ route('software.products.index') }}"
                       class="inline-block px-10 py-4 border-2 border-purple-600 dark:border-purple-400 text-purple-600 dark:text-purple-400 hover:bg-purple-600 dark:hover:bg-purple-500 hover:text-white font-bold text-lg rounded-2xl transition-all duration-300">
                        <i class="fas fa-th-large mr-2"></i>
                        ดูสินค้าทั้งหมด
                    </a>
                </div>

            </div>

        </div>

    </section>

    {{-- ================================================================
        SECTION 5: HOW IT WORKS - 4 ขั้นตอน
        ================================================================ --}}
    <section class="relative py-20 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-gray-900 dark:to-gray-800">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Section Header --}}
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-black mb-4 text-gray-900 dark:text-white">
                    วิธีเริ่มต้น
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-400 dark:to-pink-400">
                        ง่ายๆ 4 ขั้นตอน
                    </span>
                </h2>
                <p class="text-xl text-gray-600 dark:text-gray-400">
                    เพียง 4 ขั้นตอนง่ายๆ คุณก็พร้อมสร้างรายได้แล้ว
                </p>
            </div>

            {{-- Steps Timeline --}}
            <div class="relative">

                {{-- Timeline Line (Desktop) --}}
                <div class="hidden lg:block absolute top-1/2 left-0 right-0 h-1 bg-gradient-to-r from-purple-600 via-pink-600 to-orange-600 transform -translate-y-1/2"></div>

                {{-- Steps Grid --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12">

                    <template x-for="(step, index) in steps" :key="index">
                        <div class="relative text-center">

                            {{-- Step Number Circle --}}
                            <div class="relative z-10 mx-auto w-20 h-20 mb-6 backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border-4 rounded-full flex items-center justify-center shadow-lg transform hover:scale-110 transition-all duration-300"
                                 :class="step.borderColor">
                                <span class="text-2xl font-black"
                                      :class="step.textColor"
                                      x-text="index + 1"></span>
                            </div>

                            {{-- Step Icon --}}
                            <div class="text-5xl mb-4"
                                 x-text="step.icon"></div>

                            {{-- Step Title --}}
                            <h3 class="text-xl font-bold mb-2 text-gray-900 dark:text-white"
                                x-text="step.title"></h3>

                            {{-- Step Description --}}
                            <p class="text-gray-600 dark:text-gray-400"
                               x-text="step.description"></p>

                        </div>
                    </template>

                </div>

            </div>

        </div>

    </section>

    {{-- ================================================================
        SECTION 6: TESTIMONIALS - รีวิวลูกค้า
        ================================================================ --}}
    <section class="relative py-20 bg-white dark:bg-gray-900">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Section Header --}}
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-black mb-4 text-gray-900 dark:text-white">
                    เสียงจาก
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-400 dark:to-pink-400">
                        ผู้ใช้จริง
                    </span>
                </h2>
                <p class="text-xl text-gray-600 dark:text-gray-400">
                    ร่วมเป็นส่วนหนึ่งของผู้ใช้นับพันที่ประสบความสำเร็จ
                </p>
            </div>

            {{-- Testimonials Carousel --}}
            <div class="relative" x-data="{ activeTestimonial: 0 }">

                {{-- Testimonials Grid --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

                    <template x-for="(testimonial, index) in testimonials" :key="index">
                        <div class="relative backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-lg p-8 transform hover:scale-105 transition-all duration-300">

                            {{-- Quote Icon --}}
                            <i class="fas fa-quote-left text-4xl text-purple-600/20 dark:text-purple-400/20 mb-4"></i>

                            {{-- Review Text --}}
                            <p class="text-gray-700 dark:text-gray-300 mb-6 leading-relaxed"
                               x-text="testimonial.review"></p>

                            {{-- Star Rating --}}
                            <div class="flex gap-1 mb-4">
                                <template x-for="star in 5" :key="star">
                                    <i class="fas fa-star"
                                       :class="star <= testimonial.rating ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600'"></i>
                                </template>
                            </div>

                            {{-- Author --}}
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white font-bold text-xl">
                                    <span x-text="testimonial.name.charAt(0)"></span>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-900 dark:text-white"
                                        x-text="testimonial.name"></h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400"
                                       x-text="testimonial.role"></p>
                                </div>
                            </div>

                        </div>
                    </template>

                </div>

            </div>

        </div>

    </section>

    {{-- ================================================================
        SECTION 7: FINAL CTA - เชิญชวนลงทะเบียน
        ================================================================ --}}
    <section class="relative py-20 overflow-hidden">

        {{-- Gradient Background --}}
        <div class="absolute inset-0 bg-gradient-to-br from-purple-600 via-pink-600 to-orange-600"></div>

        {{-- Pattern Overlay --}}
        <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 40px 40px;"></div>

        {{-- Content --}}
        <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-white">

            <h2 class="text-4xl md:text-6xl font-black mb-6">
                พร้อมเริ่มต้นแล้วหรือยัง?
            </h2>

            <p class="text-xl md:text-2xl mb-12 text-white/90">
                เข้าร่วมกับผู้ใช้นับพันที่สร้างรายได้ด้วย TP-Affiliate วันนี้
            </p>

            {{-- CTA Buttons --}}
            <div class="flex flex-col sm:flex-row gap-6 justify-center items-center mb-12">
                <a href="{{ route('register') }}"
                   class="px-12 py-6 bg-white text-purple-600 hover:bg-gray-100 font-bold text-xl rounded-2xl shadow-2xl transform hover:scale-105 transition-all duration-300">
                    <i class="fas fa-user-plus mr-2"></i>
                    ลงทะเบียนฟรี
                </a>

                <a href="{{ route('login') }}"
                   class="px-12 py-6 border-2 border-white text-white hover:bg-white/10 font-bold text-xl rounded-2xl transition-all duration-300">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    เข้าสู่ระบบ
                </a>
            </div>

            {{-- Final Trust Badges --}}
            <div class="flex flex-wrap gap-8 justify-center items-center text-white/80">
                <div class="flex items-center gap-2">
                    <i class="fas fa-shield-check text-2xl"></i>
                    <span>ปลอดภัย 100%</span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fas fa-clock text-2xl"></i>
                    <span>รองรับ 24/7</span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fas fa-award text-2xl"></i>
                    <span>มาตรฐานสากล</span>
                </div>
            </div>

        </div>

    </section>

</div>

{{-- ================================================================
    ALPINE.JS COMPONENTS & SCRIPTS
    ================================================================ --}}
@push('scripts')
<script>
/**
 * Homepage Manager Component
 *
 * จัดการ state และ logic ทั้งหมดของหน้าแรก
 *
 * Features:
 * - Animated counters
 * - Particle effects
 * - Intersection observers
 * - Auto-play carousels
 *
 * @returns {object} Alpine component object
 */
function homepageManager() {
    return {
        // ----------------------------------------------------------------
        // STATE
        // ----------------------------------------------------------------

        isVisible: false,

        // Particles for animation
        particles: [],

        // Stats counters
        stats: {
            users: 0,
            revenue: 0,
            bots: 0,
            products: 0,
        },

        // Stats data
        statsData: [
            {
                id: 1,
                label: 'ผู้ใช้งานทั้งหมด',
                value: 15420,
                displayValue: 0,
                prefix: '',
                suffix: '+',
                trend: '+12% เดือนนี้',
                trendColor: 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400',
                icon: 'fas fa-users',
                iconBg: 'bg-gradient-to-br from-blue-500 to-cyan-600',
                gradient: 'bg-gradient-to-br from-blue-500 to-cyan-600',
                animated: false,
            },
            {
                id: 2,
                label: 'รายได้จ่ายรวม',
                value: 8540000,
                displayValue: 0,
                prefix: '฿',
                suffix: '',
                trend: '+18% เดือนนี้',
                trendColor: 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400',
                icon: 'fas fa-dollar-sign',
                iconBg: 'bg-gradient-to-br from-green-500 to-emerald-600',
                gradient: 'bg-gradient-to-br from-green-500 to-emerald-600',
                animated: false,
            },
            {
                id: 3,
                label: 'AI Bots ทั้งหมด',
                value: 245,
                displayValue: 0,
                prefix: '',
                suffix: '+',
                trend: '+25 บอทใหม่',
                trendColor: 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
                icon: 'fas fa-robot',
                iconBg: 'bg-gradient-to-br from-purple-500 to-pink-600',
                gradient: 'bg-gradient-to-br from-purple-500 to-pink-600',
                animated: false,
            },
            {
                id: 4,
                label: 'สินค้าและบริการ',
                value: 1280,
                displayValue: 0,
                prefix: '',
                suffix: '+',
                trend: '+8% เดือนนี้',
                trendColor: 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400',
                icon: 'fas fa-box',
                iconBg: 'bg-gradient-to-br from-orange-500 to-red-600',
                gradient: 'bg-gradient-to-br from-orange-500 to-red-600',
                animated: false,
            },
        ],

        // Features
        features: [
            {
                id: 1,
                title: 'ระบบ Affiliate',
                description: 'หารายได้จากการแนะนำสินค้า คอมมิชชั่นสูง จ่ายจริง',
                icon: '💼',
                gradient: 'bg-gradient-to-br from-purple-600 to-pink-600',
                link: '{{ route("user.dashboard") }}',
                cta: 'เริ่มเลย',
                isNew: false,
            },
            {
                id: 2,
                title: 'AI Bot Marketplace',
                description: 'ซื้อ-ขาย AI Chatbots และ LINE Bots คุณภาพสูง',
                icon: '🤖',
                gradient: 'bg-gradient-to-br from-blue-600 to-cyan-600',
                link: '{{ route("marketplace.index") }}',
                cta: 'ดูบอท',
                isNew: false,
            },
            {
                id: 3,
                title: 'Software Sales',
                description: 'ซอฟแวร์และระบบครบวงจร ราคาคุ้มค่า มาตรฐานสากล',
                icon: '💻',
                gradient: 'bg-gradient-to-br from-green-600 to-emerald-600',
                link: '{{ route("software.products.index") }}',
                cta: 'ดูสินค้า',
                isNew: true,
            },
            {
                id: 4,
                title: 'E-Commerce',
                description: 'ขายสินค้าออนไลน์ ระบบครบวงจร จัดการง่าย',
                icon: '🛒',
                gradient: 'bg-gradient-to-br from-yellow-600 to-orange-600',
                link: '{{ route("marketplace.index") }}',
                cta: 'เปิดร้าน',
                isNew: false,
            },
            {
                id: 5,
                title: 'MLM System',
                description: 'ระบบขายตรงหลายชั้น โครงสร้างทีมไม่จำกัด',
                icon: '🌐',
                gradient: 'bg-gradient-to-br from-red-600 to-pink-600',
                link: '{{ route("user.dashboard") }}',
                cta: 'เริ่มทีม',
                isNew: false,
            },
            {
                id: 6,
                title: 'Blockchain/TPIX',
                description: 'รองรับ crypto และ TPIX token ระบบทันสมัย',
                icon: '⛓️',
                gradient: 'bg-gradient-to-br from-indigo-600 to-purple-600',
                link: '{{ route("crypto.charts") }}',
                cta: 'เรียนรู้',
                isNew: false,
            },
            {
                id: 7,
                title: 'Tarot AI',
                description: 'ทำนายดวงชะตาด้วย AI ฟรีทุกวัน แม่นยำสูง',
                icon: '🔮',
                gradient: 'bg-gradient-to-br from-purple-600 to-pink-600',
                link: '{{ route("tarot.index") }}',
                cta: 'ทำนายเลย',
                isNew: false,
            },
            {
                id: 8,
                title: 'Hotel Booking',
                description: 'จองโรงแรม ที่พัก ราคาพิเศษ คอมมิชชั่นสูง',
                icon: '🏨',
                gradient: 'bg-gradient-to-br from-teal-600 to-green-600',
                link: '{{ route("hotels.index") }}',
                cta: 'จองเลย',
                isNew: false,
            },
        ],

        // Showcase Products
        showcaseProducts: [
            {
                id: 1,
                name: 'LINE Bot AI Premium',
                description: 'ระบบ AI Chatbot สำหรับ LINE ตอบคำถามอัตโนมัติ',
                price: 2990,
                unit: 'เดือน',
                icon: 'fab fa-line',
                gradient: 'bg-gradient-to-br from-green-500 to-emerald-600',
                link: '{{ route("marketplace.bots.show", 1) }}',
                cta: 'ดูรายละเอียด',
            },
            {
                id: 2,
                name: 'TP-Affiliate Pro',
                description: 'ระบบ Affiliate Marketing ครบวงจร พร้อมใช้งาน',
                price: 15900,
                unit: 'ครั้ง',
                icon: 'fas fa-briefcase',
                gradient: 'bg-gradient-to-br from-purple-500 to-pink-600',
                link: '{{ route("software.products.show", 1) }}',
                cta: 'ซื้อเลย',
            },
            {
                id: 3,
                name: 'E-Commerce Starter',
                description: 'ระบบร้านค้าออนไลน์ พร้อม Payment Gateway',
                price: 8900,
                unit: 'ครั้ง',
                icon: 'fas fa-shopping-cart',
                gradient: 'bg-gradient-to-br from-blue-500 to-cyan-600',
                link: '{{ route("software.products.show", 2) }}',
                cta: 'ซื้อเลย',
            },
            {
                id: 4,
                name: 'Chatbot Facebook',
                description: 'ระบบตอบกลับอัตโนมัติสำหรับ Facebook Messenger',
                price: 1990,
                unit: 'เดือน',
                icon: 'fab fa-facebook-messenger',
                gradient: 'bg-gradient-to-br from-blue-600 to-indigo-600',
                link: '{{ route("marketplace.bots.show", 2) }}',
                cta: 'ดูรายละเอียด',
            },
            {
                id: 5,
                name: 'MLM Pro System',
                description: 'ระบบขายตรงหลายชั้น พร้อมคำนวณคอมมิชชั่น',
                price: 25900,
                unit: 'ครั้ง',
                icon: 'fas fa-sitemap',
                gradient: 'bg-gradient-to-br from-red-500 to-pink-600',
                link: '{{ route("software.products.show", 3) }}',
                cta: 'ซื้อเลย',
            },
            {
                id: 6,
                name: 'Tarot AI Bot',
                description: 'ระบบทำนายดวงด้วย AI สำหรับ LINE และ Facebook',
                price: 3990,
                unit: 'เดือน',
                icon: 'fas fa-crystal-ball',
                gradient: 'bg-gradient-to-br from-purple-600 to-indigo-600',
                link: '{{ route("marketplace.bots.show", 3) }}',
                cta: 'ดูรายละเอียด',
            },
        ],

        // Steps (How it works)
        steps: [
            {
                id: 1,
                title: 'ลงทะเบียน',
                description: 'สมัครสมาชิกฟรี ใช้เวลาไม่ถึง 1 นาที',
                icon: '📝',
                borderColor: 'border-purple-600 dark:border-purple-400',
                textColor: 'text-purple-600 dark:text-purple-400',
            },
            {
                id: 2,
                title: 'เลือกระบบ',
                description: 'เลือกระบบที่ต้องการ จาก 8 ระบบหลัก',
                icon: '🎯',
                borderColor: 'border-pink-600 dark:border-pink-400',
                textColor: 'text-pink-600 dark:text-pink-400',
            },
            {
                id: 3,
                title: 'เริ่มสร้างรายได้',
                description: 'เริ่มทำงานและสร้างรายได้ได้ทันที',
                icon: '💰',
                borderColor: 'border-orange-600 dark:border-orange-400',
                textColor: 'text-orange-600 dark:text-orange-400',
            },
            {
                id: 4,
                title: 'รับเงินจริง',
                description: 'ถอนเงินได้ทุกวัน จ่ายไว ไม่มีขั้นต่ำ',
                icon: '🎉',
                borderColor: 'border-green-600 dark:border-green-400',
                textColor: 'text-green-600 dark:text-green-400',
            },
        ],

        // Testimonials
        testimonials: [
            {
                id: 1,
                name: 'คุณสมชาย',
                role: 'Affiliate Partner',
                review: 'ใช้งาน TP-Affiliate มา 6 เดือน รายได้เพิ่มขึ้น 3 เท่า ระบบใช้งานง่าย จ่ายเงินตรงเวลา ประทับใจมาก!',
                rating: 5,
            },
            {
                id: 2,
                name: 'คุณวรรณา',
                role: 'AI Bot Developer',
                review: 'ขาย LINE Bot ผ่าน Marketplace ได้เงินดี มีลูกค้าเยอะ ระบบจัดการครบวงจร แนะนำเลยค่ะ',
                rating: 5,
            },
            {
                id: 3,
                name: 'คุณประยุทธ์',
                role: 'E-Commerce Owner',
                review: 'ซื้อระบบ E-Commerce ราคาคุ้มค่า Support ดีมาก ตอบไว แก้ปัญหาได้รวดเร็ว ขอบคุณครับ',
                rating: 5,
            },
            {
                id: 4,
                name: 'คุณสุภา',
                role: 'MLM Leader',
                description: 'ระบบ MLM ที่ดีที่สุดที่เคยใช้ คำนวณคอมมิชชั่นแม่นยำ ทีมเติบโตเร็วมาก',
                rating: 5,
            },
            {
                id: 5,
                name: 'คุณธนา',
                role: 'Software Reseller',
                review: 'ขายซอฟแวร์ได้กำไรดี สินค้าคุณภาพ ลูกค้าพึงพอใจ อยากให้มีสินค้าเพิ่มอีกครับ',
                rating: 5,
            },
            {
                id: 6,
                name: 'คุณนภา',
                role: 'Tarot Reader',
                review: 'ใช้ Tarot AI Bot ทำนายให้ลูกค้า แม่นยำมาก ลูกค้าชอบ รายได้ดีขึ้นเยอะค่ะ',
                rating: 5,
            },
        ],

        // ----------------------------------------------------------------
        // LIFECYCLE METHODS
        // ----------------------------------------------------------------

        /**
         * เริ่มต้น component
         */
        init() {
            console.log('🚀 Homepage initialized');

            // Generate particles
            this.generateParticles();

            // Show hero content
            setTimeout(() => {
                this.isVisible = true;
            }, 100);

            // Auto-scroll observer
            this.setupIntersectionObserver();
        },

        // ----------------------------------------------------------------
        // METHODS
        // ----------------------------------------------------------------

        /**
         * สร้าง particles สำหรับ animation
         */
        generateParticles() {
            const particleCount = 10;

            for (let i = 0; i < particleCount; i++) {
                this.particles.push({
                    top: Math.random() * 100,
                    left: Math.random() * 100,
                    delay: Math.random() * 3,
                });
            }
        },

        /**
         * เริ่ม counter animation
         *
         * @param {object} stat - Stat object
         */
        startCounter(stat) {
            // ถ้า animated แล้ว ข้าม
            if (stat.animated) return;

            stat.animated = true;

            const duration = 2000; // 2 วินาที
            const steps = 60;
            const increment = stat.value / steps;
            let current = 0;

            const interval = setInterval(() => {
                current += increment;

                if (current >= stat.value) {
                    stat.displayValue = stat.value;
                    clearInterval(interval);
                } else {
                    stat.displayValue = Math.floor(current);
                }
            }, duration / steps);
        },

        /**
         * Setup intersection observer สำหรับ animations
         */
        setupIntersectionObserver() {
            // Already handled by x-intersect directive
            console.log('Intersection observer active');
        },

    };
}

/**
 * Register global component
 */
window.homepageManager = homepageManager;
</script>

<style>
/**
 * Custom Styles สำหรับหน้าแรก V3
 */

/* Perspective for 3D effects */
.perspective-1000 {
    perspective: 1000px;
}

/* 3D Rotation */
.rotate-y-3 {
    transform: rotateY(3deg);
}

.rotate-y-6 {
    transform: rotateY(6deg);
}

/* Animations */
@keyframes fade-in {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes fade-in-up {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fade-in-down {
    from {
        opacity: 0;
        transform: translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fade-in 0.6s ease-out;
}

.animate-fade-in-up {
    animation: fade-in-up 0.8s ease-out;
}

.animate-fade-in-down {
    animation: fade-in-down 0.8s ease-out;
}

/* Smooth scroll */
html {
    scroll-behavior: smooth;
}

/* Hide scrollbar but keep functionality */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: transparent;
}

::-webkit-scrollbar-thumb {
    background: rgba(139, 92, 246, 0.5);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: rgba(139, 92, 246, 0.7);
}
</style>
@endpush

@endsection
