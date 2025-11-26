{{--
/**
 * หน้าแรก Thaiprompt - 3 Doors to Success
 *
 * Storytelling Design: ทำไมต้องเป็น Thaiprompt?
 * เราไม่ได้แค่มีเครื่องมือ - เราแก้ปัญหาที่แท้จริง
 *
 * 3 ประตูสู่ความสำเร็จ:
 * - 🚪 ประตูที่ 1: นักลงทุน - การลงทุนที่เชื่อมกับชีวิตจริง
 * - 🚪 ประตูที่ 2: นักพัฒนา - เปลี่ยน Code เป็นรายได้
 * - 🚪 ประตูที่ 3: ชุมชน - เติบโตไปด้วยกัน ไม่ทิ้งใครไว้ข้างหลัง
 *
 * V3 Technologies:
 * - Tailwind CSS (pure utility-first)
 * - Alpine.js (reactive components)
 * - GSAP (premium animations)
 * - Three.js (3D effects)
 * - Glassmorphism + Particles
 *
 * @version 4.0.0
 * @author Thaiprompt Team
 * @created 2025-11-22
 */
--}}

@extends('layouts.guest')

@section('title', 'Thaiprompt - เราไม่ได้แค่มีเครื่องมือ เราแก้ปัญหาที่แท้จริง')

@push('styles')
<style>
/**
 * Custom Animations & Styles - Futuristic 3D Edition
 * เอฟเฟกต์ 3D ล้ำสมัย สำหรับ Hero Section
 */

/* 3D Perspective Container */
.perspective-2000 {
    perspective: 2000px;
    transform-style: preserve-3d;
}

.preserve-3d {
    transform-style: preserve-3d;
}

/* ============================================
   3D ORBITAL RINGS - วงแหวนโคจรรอบโลโก้
   ============================================ */
@keyframes orbit-x {
    0% { transform: rotateX(70deg) rotateY(0deg) rotateZ(0deg); }
    100% { transform: rotateX(70deg) rotateY(360deg) rotateZ(0deg); }
}

@keyframes orbit-y {
    0% { transform: rotateX(0deg) rotateY(70deg) rotateZ(0deg); }
    100% { transform: rotateX(0deg) rotateY(70deg) rotateZ(360deg); }
}

@keyframes orbit-z {
    0% { transform: rotateX(45deg) rotateY(45deg) rotateZ(0deg); }
    100% { transform: rotateX(45deg) rotateY(45deg) rotateZ(360deg); }
}

.orbit-ring {
    position: absolute;
    border-radius: 50%;
    border: 2px solid transparent;
    pointer-events: none;
}

.orbit-ring-1 {
    width: 120%;
    height: 120%;
    top: -10%;
    left: -10%;
    border-image: linear-gradient(45deg, #a855f7, #ec4899, #f97316, #a855f7) 1;
    animation: orbit-x 15s linear infinite;
}

.orbit-ring-2 {
    width: 140%;
    height: 140%;
    top: -20%;
    left: -20%;
    border-image: linear-gradient(135deg, #06b6d4, #8b5cf6, #f43f5e, #06b6d4) 1;
    animation: orbit-y 20s linear infinite reverse;
}

.orbit-ring-3 {
    width: 160%;
    height: 160%;
    top: -30%;
    left: -30%;
    border-image: linear-gradient(225deg, #22c55e, #eab308, #ef4444, #22c55e) 1;
    animation: orbit-z 25s linear infinite;
}

/* ============================================
   HOLOGRAPHIC EFFECT - เอฟเฟกต์ Hologram
   ============================================ */
@keyframes holographic {
    0%, 100% {
        background-position: 0% 50%;
        filter: hue-rotate(0deg) brightness(1);
    }
    25% {
        filter: hue-rotate(30deg) brightness(1.1);
    }
    50% {
        background-position: 100% 50%;
        filter: hue-rotate(60deg) brightness(1.2);
    }
    75% {
        filter: hue-rotate(30deg) brightness(1.1);
    }
}

.holographic-overlay {
    background: linear-gradient(
        135deg,
        rgba(168, 85, 247, 0.15) 0%,
        rgba(236, 72, 153, 0.15) 25%,
        rgba(6, 182, 212, 0.15) 50%,
        rgba(34, 197, 94, 0.15) 75%,
        rgba(168, 85, 247, 0.15) 100%
    );
    background-size: 300% 300%;
    animation: holographic 8s ease infinite;
    mix-blend-mode: overlay;
}

/* ============================================
   CYBER GLOW - เรืองแสงแบบ Cyberpunk
   ============================================ */
@keyframes cyber-glow {
    0%, 100% {
        box-shadow:
            0 0 30px rgba(168, 85, 247, 0.5),
            0 0 60px rgba(236, 72, 153, 0.3),
            0 0 90px rgba(6, 182, 212, 0.2),
            inset 0 0 30px rgba(255, 255, 255, 0.1);
    }
    33% {
        box-shadow:
            0 0 40px rgba(6, 182, 212, 0.5),
            0 0 80px rgba(168, 85, 247, 0.3),
            0 0 120px rgba(236, 72, 153, 0.2),
            inset 0 0 40px rgba(255, 255, 255, 0.15);
    }
    66% {
        box-shadow:
            0 0 50px rgba(236, 72, 153, 0.5),
            0 0 100px rgba(6, 182, 212, 0.3),
            0 0 150px rgba(168, 85, 247, 0.2),
            inset 0 0 50px rgba(255, 255, 255, 0.2);
    }
}

.animate-cyber-glow {
    animation: cyber-glow 4s ease-in-out infinite;
}

/* ============================================
   FLOATING ANIMATION - การลอยตัว
   ============================================ */
@keyframes float {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-30px) rotate(2deg); }
}

@keyframes float-3d {
    0%, 100% {
        transform: translateY(0px) translateZ(0px) rotateX(0deg);
    }
    25% {
        transform: translateY(-15px) translateZ(20px) rotateX(5deg);
    }
    50% {
        transform: translateY(-30px) translateZ(0px) rotateX(0deg);
    }
    75% {
        transform: translateY(-15px) translateZ(-20px) rotateX(-5deg);
    }
}

.animate-float {
    animation: float 8s ease-in-out infinite;
}

.animate-float-3d {
    animation: float-3d 10s ease-in-out infinite;
}

/* ============================================
   ROTATING SHAPES - รูปทรง 3D หมุน
   ============================================ */
@keyframes rotate-3d {
    0% { transform: rotateX(0deg) rotateY(0deg) rotateZ(0deg); }
    100% { transform: rotateX(360deg) rotateY(360deg) rotateZ(360deg); }
}

@keyframes rotate-reverse {
    0% { transform: rotateX(0deg) rotateY(0deg); }
    100% { transform: rotateX(-360deg) rotateY(-360deg); }
}

.animate-rotate-3d {
    animation: rotate-3d 30s linear infinite;
}

.animate-rotate-reverse {
    animation: rotate-reverse 40s linear infinite;
}

/* ============================================
   PULSE & GLOW EFFECTS
   ============================================ */
@keyframes pulse-ring {
    0% {
        transform: scale(0.8);
        opacity: 1;
    }
    100% {
        transform: scale(2);
        opacity: 0;
    }
}

@keyframes pulse-glow {
    0%, 100% { opacity: 0.5; transform: scale(1); }
    50% { opacity: 1; transform: scale(1.1); }
}

.animate-pulse-ring {
    animation: pulse-ring 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

.animate-pulse-glow {
    animation: pulse-glow 3s ease-in-out infinite;
}

/* ============================================
   GRADIENT MESH BACKGROUND - พื้นหลังล้ำสมัย
   ============================================ */
@keyframes mesh-move {
    0%, 100% {
        transform: translate(0%, 0%) rotate(0deg);
    }
    25% {
        transform: translate(5%, 5%) rotate(90deg);
    }
    50% {
        transform: translate(0%, 10%) rotate(180deg);
    }
    75% {
        transform: translate(-5%, 5%) rotate(270deg);
    }
}

.animated-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    animation: mesh-move 20s ease-in-out infinite;
}

.orb-1 {
    width: 600px;
    height: 600px;
    background: radial-gradient(circle, rgba(168, 85, 247, 0.4) 0%, transparent 70%);
    top: -200px;
    left: -200px;
}

.orb-2 {
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(236, 72, 153, 0.4) 0%, transparent 70%);
    top: 50%;
    right: -150px;
    animation-delay: -5s;
}

.orb-3 {
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(6, 182, 212, 0.3) 0%, transparent 70%);
    bottom: -100px;
    left: 30%;
    animation-delay: -10s;
}

.orb-4 {
    width: 350px;
    height: 350px;
    background: radial-gradient(circle, rgba(34, 197, 94, 0.3) 0%, transparent 70%);
    top: 40%;
    left: 10%;
    animation-delay: -15s;
}

/* ============================================
   CYBER GRID - ตารางแบบ Cyberpunk
   ============================================ */
@keyframes grid-pulse {
    0%, 100% { opacity: 0.15; }
    50% { opacity: 0.25; }
}

.cyber-grid {
    background-image:
        linear-gradient(rgba(168, 85, 247, 0.2) 1px, transparent 1px),
        linear-gradient(90deg, rgba(168, 85, 247, 0.2) 1px, transparent 1px),
        linear-gradient(rgba(168, 85, 247, 0.1) 1px, transparent 1px),
        linear-gradient(90deg, rgba(168, 85, 247, 0.1) 1px, transparent 1px);
    background-size: 100px 100px, 100px 100px, 20px 20px, 20px 20px;
    animation: grid-pulse 4s ease-in-out infinite;
}

/* ============================================
   FLOATING 3D SHAPES - รูปทรงลอยตัว
   ============================================ */
.floating-shape {
    position: absolute;
    pointer-events: none;
    opacity: 0.6;
}

@keyframes shape-float-1 {
    0%, 100% { transform: translateY(0) translateX(0) rotate(0deg); }
    50% { transform: translateY(-50px) translateX(30px) rotate(180deg); }
}

@keyframes shape-float-2 {
    0%, 100% { transform: translateY(0) translateX(0) rotate(0deg) scale(1); }
    50% { transform: translateY(-40px) translateX(-20px) rotate(-180deg) scale(1.2); }
}

@keyframes shape-float-3 {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    33% { transform: translateY(-30px) rotate(120deg); }
    66% { transform: translateY(-60px) rotate(240deg); }
}

.shape-1 { animation: shape-float-1 12s ease-in-out infinite; }
.shape-2 { animation: shape-float-2 15s ease-in-out infinite; }
.shape-3 { animation: shape-float-3 18s ease-in-out infinite; }

/* ============================================
   OTHER ANIMATIONS
   ============================================ */
@keyframes door-open {
    from {
        transform: perspective(1000px) rotateY(-90deg);
        opacity: 0;
    }
    to {
        transform: perspective(1000px) rotateY(0deg);
        opacity: 1;
    }
}

.door-enter {
    animation: door-open 0.8s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

@keyframes gradient-shift {
    0%, 100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}

.animate-gradient {
    background-size: 200% 200%;
    animation: gradient-shift 5s ease infinite;
}

@keyframes particle-float {
    0% {
        transform: translateY(0) translateX(0);
        opacity: 0;
    }
    10% { opacity: 1; }
    90% { opacity: 1; }
    100% {
        transform: translateY(-100vh) translateX(50px);
        opacity: 0;
    }
}

.particle {
    animation: particle-float linear infinite;
}

@keyframes sparkle {
    0%, 100% { opacity: 0; transform: scale(0); }
    50% { opacity: 1; transform: scale(1); }
}

.sparkle {
    animation: sparkle 2s ease-in-out infinite;
}

/* ============================================
   SCROLLBAR & UTILITIES
   ============================================ */
html {
    scroll-behavior: smooth;
}

::-webkit-scrollbar {
    width: 12px;
}

::-webkit-scrollbar-track {
    background: transparent;
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #a855f7, #ec4899);
    border-radius: 6px;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, #9333ea, #db2777);
}

/* Logo 3D Container */
.logo-3d-container {
    transform-style: preserve-3d;
    transition: transform 0.3s ease-out;
}

/* Scan Line Effect */
@keyframes scan-line {
    0% { top: -100%; }
    100% { top: 100%; }
}

.scan-line::after {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    height: 50px;
    background: linear-gradient(
        to bottom,
        transparent,
        rgba(168, 85, 247, 0.1),
        rgba(6, 182, 212, 0.1),
        transparent
    );
    animation: scan-line 4s linear infinite;
    pointer-events: none;
}
</style>
@endpush

@section('content')

{{-- Alpine.js Main Component --}}
<div x-data="homepageStorytellingManager()" x-init="init()" class="relative overflow-hidden">

    {{-- ================================================================
        HERO SECTION - อลังการ ล้ำสมัย สไตล์ Futuristic 3D
    ================================================================ --}}
    <section class="relative min-h-screen flex items-center justify-center overflow-hidden bg-gradient-to-br from-purple-950 via-black to-pink-950">

        {{-- Animated Gradient Orbs (พื้นหลังล้ำสมัย) --}}
        <div class="absolute inset-0 overflow-hidden">
            <div class="animated-orb orb-1"></div>
            <div class="animated-orb orb-2"></div>
            <div class="animated-orb orb-3"></div>
            <div class="animated-orb orb-4"></div>
        </div>

        {{-- Cyber Grid Pattern --}}
        <div class="absolute inset-0 cyber-grid"></div>

        {{-- Floating 3D Shapes --}}
        <div class="absolute inset-0 pointer-events-none preserve-3d">
            {{-- Shape 1: Cube wireframe --}}
            <div class="floating-shape shape-1 top-[15%] left-[10%]">
                <svg width="60" height="60" viewBox="0 0 60 60" class="text-purple-500/40">
                    <rect x="10" y="10" width="40" height="40" fill="none" stroke="currentColor" stroke-width="1"/>
                    <rect x="15" y="5" width="40" height="40" fill="none" stroke="currentColor" stroke-width="1" transform="translate(5, 5)"/>
                    <line x1="10" y1="10" x2="20" y2="5" stroke="currentColor" stroke-width="1"/>
                    <line x1="50" y1="10" x2="55" y2="5" stroke="currentColor" stroke-width="1"/>
                    <line x1="10" y1="50" x2="15" y2="45" stroke="currentColor" stroke-width="1"/>
                    <line x1="50" y1="50" x2="55" y2="45" stroke="currentColor" stroke-width="1"/>
                </svg>
            </div>

            {{-- Shape 2: Hexagon --}}
            <div class="floating-shape shape-2 top-[25%] right-[15%]">
                <svg width="80" height="80" viewBox="0 0 80 80" class="text-pink-500/30">
                    <polygon points="40,5 72,22 72,58 40,75 8,58 8,22" fill="none" stroke="currentColor" stroke-width="1.5"/>
                    <polygon points="40,15 60,27 60,53 40,65 20,53 20,27" fill="none" stroke="currentColor" stroke-width="1"/>
                </svg>
            </div>

            {{-- Shape 3: Triangle --}}
            <div class="floating-shape shape-3 bottom-[20%] left-[20%]">
                <svg width="70" height="70" viewBox="0 0 70 70" class="text-cyan-500/30">
                    <polygon points="35,5 65,60 5,60" fill="none" stroke="currentColor" stroke-width="1.5"/>
                    <polygon points="35,20 52,52 18,52" fill="none" stroke="currentColor" stroke-width="1"/>
                </svg>
            </div>

            {{-- Shape 4: Circle --}}
            <div class="floating-shape shape-1 bottom-[30%] right-[10%]" style="animation-delay: -3s;">
                <svg width="50" height="50" viewBox="0 0 50 50" class="text-green-500/30">
                    <circle cx="25" cy="25" r="20" fill="none" stroke="currentColor" stroke-width="1.5"/>
                    <circle cx="25" cy="25" r="12" fill="none" stroke="currentColor" stroke-width="1"/>
                    <circle cx="25" cy="25" r="5" fill="currentColor" opacity="0.5"/>
                </svg>
            </div>

            {{-- Shape 5: Diamond --}}
            <div class="floating-shape shape-2 top-[60%] left-[5%]" style="animation-delay: -5s;">
                <svg width="40" height="60" viewBox="0 0 40 60" class="text-yellow-500/30">
                    <polygon points="20,5 38,30 20,55 2,30" fill="none" stroke="currentColor" stroke-width="1.5"/>
                </svg>
            </div>
        </div>

        {{-- Floating Particles --}}
        <div class="absolute inset-0 pointer-events-none">
            <template x-for="(particle, index) in particles" :key="index">
                <div class="particle absolute rounded-full"
                     :class="particle.size === 'lg' ? 'w-2 h-2 bg-purple-400' : (particle.size === 'md' ? 'w-1.5 h-1.5 bg-pink-400' : 'w-1 h-1 bg-cyan-400')"
                     :style="`left: ${particle.x}%;
                              animation-duration: ${particle.duration}s;
                              animation-delay: ${particle.delay}s;`">
                </div>
            </template>
        </div>

        {{-- Sparkle Effects --}}
        <div class="absolute inset-0 pointer-events-none">
            <template x-for="(sparkle, index) in sparkles" :key="'sparkle-' + index">
                <div class="sparkle absolute w-2 h-2 bg-white rounded-full"
                     :style="`left: ${sparkle.x}%; top: ${sparkle.y}%; animation-delay: ${sparkle.delay}s;`">
                </div>
            </template>
        </div>

        {{-- Main Content --}}
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 text-center">

            {{-- Logo Container (3D Floating with Orbital Rings) --}}
            <div class="mb-16 perspective-2000"
                 x-data="logo3DController()"
                 @mousemove.window="handleMouseMove($event)"
                 @mouseleave.window="resetRotation()">

                <div class="inline-block animate-float-3d relative preserve-3d">

                    {{-- 3D Orbital Rings --}}
                    <div class="absolute inset-0 preserve-3d" style="transform: translateZ(-50px);">
                        {{-- Ring 1: หมุนรอบแกน X --}}
                        <div class="absolute inset-[-40%] border-2 border-purple-500/30 rounded-full"
                             style="animation: orbit-x 15s linear infinite; transform-style: preserve-3d;">
                            {{-- Dot on ring --}}
                            <div class="absolute w-3 h-3 bg-purple-500 rounded-full shadow-lg shadow-purple-500/50"
                                 style="top: 0; left: 50%; transform: translateX(-50%) translateY(-50%);"></div>
                        </div>

                        {{-- Ring 2: หมุนรอบแกน Y --}}
                        <div class="absolute inset-[-55%] border-2 border-pink-500/25 rounded-full"
                             style="animation: orbit-y 20s linear infinite reverse; transform-style: preserve-3d;">
                            <div class="absolute w-2 h-2 bg-pink-500 rounded-full shadow-lg shadow-pink-500/50"
                                 style="top: 50%; right: 0; transform: translateY(-50%) translateX(50%);"></div>
                        </div>

                        {{-- Ring 3: หมุนรอบแกน Z --}}
                        <div class="absolute inset-[-70%] border border-cyan-500/20 rounded-full"
                             style="animation: orbit-z 25s linear infinite; transform-style: preserve-3d;">
                            <div class="absolute w-2 h-2 bg-cyan-500 rounded-full shadow-lg shadow-cyan-500/50"
                                 style="bottom: 0; left: 50%; transform: translateX(-50%) translateY(50%);"></div>
                        </div>
                    </div>

                    {{-- Pulsing Glow Rings --}}
                    <div class="absolute inset-[-20%] animate-pulse-ring">
                        <div class="w-full h-full rounded-full border-2 border-purple-500/40"></div>
                    </div>
                    <div class="absolute inset-[-20%] animate-pulse-ring" style="animation-delay: 0.7s;">
                        <div class="w-full h-full rounded-full border-2 border-pink-500/30"></div>
                    </div>
                    <div class="absolute inset-[-20%] animate-pulse-ring" style="animation-delay: 1.4s;">
                        <div class="w-full h-full rounded-full border-2 border-cyan-500/20"></div>
                    </div>

                    {{-- Logo Card (3D with Holographic Effect) --}}
                    <div class="logo-3d-container relative p-10 md:p-14 backdrop-blur-2xl bg-white/5 border-2 border-white/10 rounded-[3rem] animate-cyber-glow scan-line"
                         :style="`transform: rotateY(${mouseX}deg) rotateX(${-mouseY}deg) translateZ(50px);`">

                        {{-- Holographic Overlay --}}
                        <div class="absolute inset-0 rounded-[3rem] holographic-overlay pointer-events-none"></div>

                        {{-- Corner Accents --}}
                        <div class="absolute top-2 left-2 w-8 h-8 border-t-2 border-l-2 border-purple-500/50 rounded-tl-xl"></div>
                        <div class="absolute top-2 right-2 w-8 h-8 border-t-2 border-r-2 border-pink-500/50 rounded-tr-xl"></div>
                        <div class="absolute bottom-2 left-2 w-8 h-8 border-b-2 border-l-2 border-cyan-500/50 rounded-bl-xl"></div>
                        <div class="absolute bottom-2 right-2 w-8 h-8 border-b-2 border-r-2 border-purple-500/50 rounded-br-xl"></div>

                        {{-- Logo Image (ดึงจากระบบอัตโนมัติ) --}}
                        @php
                            $systemLogo = \App\Models\Setting::get('logo');
                            $systemLogoSpin = \App\Models\Setting::get('logo_spin');
                            $appName = \App\Models\Setting::get('app_name') ?? config('app.name', 'Thaiprompt');
                        @endphp

                        @if($systemLogo)
                            <div class="relative">
                                {{-- Main Logo --}}
                                <img src="{{ asset($systemLogo) }}"
                                     alt="{{ $appName }} Logo"
                                     class="w-32 h-32 md:w-44 md:h-44 lg:w-56 lg:h-56 mx-auto object-contain relative z-10"
                                     style="filter: drop-shadow(0 0 40px rgba(168, 85, 247, 0.6)) drop-shadow(0 0 80px rgba(236, 72, 153, 0.4));"
                                     loading="eager">

                                {{-- Glow Behind Logo --}}
                                <div class="absolute inset-0 bg-gradient-to-r from-purple-500/20 via-pink-500/20 to-cyan-500/20 blur-3xl rounded-full animate-pulse-glow"></div>
                            </div>
                        @else
                            {{-- Fallback Logo (กรณีไม่มีโลโก้) --}}
                            <div class="relative w-32 h-32 md:w-44 md:h-44 lg:w-56 lg:h-56 mx-auto">
                                <div class="absolute inset-0 bg-gradient-to-br from-purple-500 via-pink-500 to-orange-500 rounded-3xl animate-rotate-3d" style="animation-duration: 20s;"></div>
                                <div class="absolute inset-1 bg-gradient-to-br from-purple-600 via-pink-600 to-orange-600 rounded-3xl flex items-center justify-center">
                                    <span class="text-5xl md:text-7xl lg:text-8xl font-black text-white drop-shadow-2xl">{{ substr($appName, 0, 2) }}</span>
                                </div>
                            </div>
                        @endif

                        {{-- Version Badge (3D floating) --}}
                        <div class="absolute -top-3 -right-3 md:-top-4 md:-right-4 z-20"
                             style="transform: translateZ(30px);">
                            <div class="px-3 py-1.5 md:px-4 md:py-2 bg-gradient-to-r from-purple-600 via-pink-600 to-cyan-600 rounded-full text-white text-xs md:text-sm font-bold shadow-lg animate-pulse-glow">
                                <span class="flex items-center gap-1">
                                    <svg class="w-3 h-3 md:w-4 md:h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                    V4.0
                                </span>
                            </div>
                        </div>

                        {{-- Tech Badge (3D floating) --}}
                        <div class="absolute -bottom-3 -left-3 md:-bottom-4 md:-left-4 z-20"
                             style="transform: translateZ(20px);">
                            <div class="px-3 py-1.5 bg-black/50 backdrop-blur-lg rounded-full text-white/80 text-xs font-medium border border-white/20">
                                <span class="flex items-center gap-1">
                                    <svg class="w-3 h-3 text-cyan-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                    3D Tech
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Hero Headline --}}
            <div class="space-y-8 mb-12">
                <h1 class="text-5xl md:text-7xl lg:text-8xl font-black leading-tight">
                    <span class="block text-white mb-4 drop-shadow-2xl">
                        เรา<span class="underline decoration-wavy decoration-yellow-400">ไม่ได้</span>แค่มีเครื่องมือ
                    </span>
                    <span class="block text-transparent bg-clip-text bg-gradient-to-r from-purple-400 via-pink-400 to-orange-400 animate-gradient">
                        เราแก้ปัญหาที่แท้จริง
                    </span>
                </h1>

                <p class="text-xl md:text-3xl lg:text-4xl text-gray-300 max-w-5xl mx-auto leading-relaxed font-light">
                    ปัญหาของคุณคือ<span class="text-pink-400 font-semibold">ปัญหาของเรา</span><br>
                    เราจะพาคุณไปถึง<span class="text-purple-400 font-semibold">จุดหมาย</span>ด้วยกัน
                </p>
            </div>

            {{-- CTA Buttons --}}
            <div class="flex flex-col sm:flex-row gap-6 justify-center items-center mb-20">
                <button @click="scrollToSection('doors')"
                        class="group relative overflow-hidden px-12 py-6 bg-gradient-to-r from-purple-600 via-pink-600 to-orange-600 text-white font-bold text-xl rounded-2xl shadow-2xl hover:shadow-purple-500/50 transform hover:scale-105 transition-all duration-300">
                    {{-- Shine Effect --}}
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>

                    <span class="relative z-10 flex items-center gap-3">
                        <i class="fas fa-door-open text-2xl"></i>
                        <span>เปิดประตูสู่ความสำเร็จ</span>
                        <i class="fas fa-arrow-right group-hover:translate-x-2 transition-transform"></i>
                    </span>
                </button>

                <a href="{{ route('about') }}"
                   class="px-12 py-6 backdrop-blur-xl bg-white/10 border-2 border-white/20 text-white font-bold text-xl rounded-2xl hover:bg-white/20 transition-all duration-300">
                    <i class="fas fa-book-open mr-2"></i>
                    เรื่องราวของเรา
                </a>
            </div>

            {{-- Trust Indicators --}}
            <div class="grid grid-cols-3 gap-8 max-w-3xl mx-auto">
                <div class="text-center">
                    <div class="text-4xl md:text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-pink-400 mb-2">
                        15K+
                    </div>
                    <div class="text-sm text-gray-400">ผู้ใช้ที่เชื่อใจ</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl md:text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-pink-400 to-orange-400 mb-2">
                        ฿50M+
                    </div>
                    <div class="text-sm text-gray-400">จ่ายจริง</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl md:text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-purple-400 mb-2">
                        24/7
                    </div>
                    <div class="text-sm text-gray-400">พร้อมช่วยเหลือ</div>
                </div>
            </div>

            {{-- Scroll Indicator --}}
            <div class="absolute bottom-10 left-1/2 transform -translate-x-1/2 animate-bounce">
                <button @click="scrollToSection('problem')"
                        class="flex flex-col items-center gap-2 text-gray-400 hover:text-white transition-colors">
                    <span class="text-sm">เลื่อนลง</span>
                    <div class="w-8 h-12 border-2 border-gray-400 rounded-full flex items-start justify-center p-2">
                        <div class="w-1 h-3 bg-gray-400 rounded-full animate-pulse"></div>
                    </div>
                </button>
            </div>
        </div>
    </section>

    {{-- ================================================================
        PROBLEM SECTION - คุณเคยประสบปัญหาเหล่านี้ไหม?
    ================================================================ --}}
    <section id="problem" class="relative py-32 bg-gradient-to-b from-black to-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Section Header --}}
            <div class="text-center mb-20"
                 x-intersect="$el.classList.add('door-enter')">
                <h2 class="text-4xl md:text-6xl font-black text-white mb-6">
                    คุณเคยประสบ<span class="text-transparent bg-clip-text bg-gradient-to-r from-red-500 to-pink-500">ปัญหาเหล่านี้</span>ไหม?
                </h2>
                <p class="text-xl text-gray-400 max-w-3xl mx-auto">
                    หลายคนเหมือนคุณ กำลังเผชิญกับปัญหาเดียวกัน<br>
                    และเรา<span class="text-purple-400 font-semibold">มีคำตอบ</span>
                </p>
            </div>

            {{-- Problem Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">

                {{-- Problem 1: นักลงทุน --}}
                <div class="group relative backdrop-blur-xl bg-white/5 border-2 border-white/10 rounded-3xl p-8 hover:bg-white/10 transition-all duration-300 hover:scale-105"
                     x-intersect.once="$el.classList.add('door-enter')"
                     style="animation-delay: 0.1s;">
                    <div class="text-6xl mb-6">😰</div>
                    <h3 class="text-2xl font-bold text-white mb-4">
                        การลงทุนซับซ้อนเกินไป
                    </h3>
                    <p class="text-gray-400 mb-6">
                        อยากลงทุนเพื่ออนาคต แต่ไม่รู้จะเริ่มต้นอย่างไร มีเงินน้อย กลัวเสี่ยง ไม่มีความรู้
                    </p>
                    <div class="flex items-center gap-2 text-red-400 font-semibold">
                        <i class="fas fa-times-circle"></i>
                        <span>ปัญหาที่พบบ่อย</span>
                    </div>
                </div>

                {{-- Problem 2: นักพัฒนา --}}
                <div class="group relative backdrop-blur-xl bg-white/5 border-2 border-white/10 rounded-3xl p-8 hover:bg-white/10 transition-all duration-300 hover:scale-105"
                     x-intersect.once="$el.classList.add('door-enter')"
                     style="animation-delay: 0.2s;">
                    <div class="text-6xl mb-6">😣</div>
                    <h3 class="text-2xl font-bold text-white mb-4">
                        มี Skill แต่หาเงินยาก
                    </h3>
                    <p class="text-gray-400 mb-6">
                        เขียนโค้ดเก่ง ทำงาน Freelance แต่หาลูกค้าไม่ได้ ขายผลงานไม่เป็น รายได้ไม่สม่ำเสมอ
                    </p>
                    <div class="flex items-center gap-2 text-red-400 font-semibold">
                        <i class="fas fa-times-circle"></i>
                        <span>ปัญหาที่พบบ่อย</span>
                    </div>
                </div>

                {{-- Problem 3: ชุมชน --}}
                <div class="group relative backdrop-blur-xl bg-white/5 border-2 border-white/10 rounded-3xl p-8 hover:bg-white/10 transition-all duration-300 hover:scale-105"
                     x-intersect.once="$el.classList.add('door-enter')"
                     style="animation-delay: 0.3s;">
                    <div class="text-6xl mb-6">😔</div>
                    <h3 class="text-2xl font-bold text-white mb-4">
                        ทำธุรกิจคนเดียวลำบาก
                    </h3>
                    <p class="text-gray-400 mb-6">
                        ไม่มีทีม ไม่มีเครือข่าย ต้องทำทุกอย่างเอง เหนื่อย ท้อ อยากมีคนช่วย
                    </p>
                    <div class="flex items-center gap-2 text-red-400 font-semibold">
                        <i class="fas fa-times-circle"></i>
                        <span>ปัญหาที่พบบ่อย</span>
                    </div>
                </div>
            </div>

            {{-- Transition to Solution --}}
            <div class="text-center">
                <div class="inline-block px-8 py-4 backdrop-blur-xl bg-gradient-to-r from-purple-600/20 to-pink-600/20 border-2 border-purple-500/30 rounded-2xl">
                    <p class="text-2xl text-white font-semibold">
                        แต่ถ้าเรา<span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-orange-400">บอกคุณ</span>ว่า...
                    </p>
                    <p class="text-xl text-gray-300 mt-2">
                        มี<span class="text-purple-400 font-bold">ทางออก</span>ที่ดีกว่า และ<span class="text-pink-400 font-bold">ง่ายกว่า</span>ที่คุณคิด
                    </p>
                </div>
            </div>

        </div>
    </section>

    {{-- ================================================================
        3 DOORS SECTION - ทางแก้ปัญหา 3 ประตู (Landing Page)
    ================================================================ --}}
    <section id="doors" class="relative py-32 bg-gradient-to-b from-gray-900 to-black">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Section Header --}}
            <div class="text-center mb-24"
                 x-intersect.once="$el.classList.add('door-enter')">
                <h2 class="text-5xl md:text-7xl font-black text-white mb-6">
                    3 ประตูสู่<span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-400 via-pink-400 to-orange-400">ความสำเร็จ</span>
                </h2>
                <p class="text-2xl text-gray-400 max-w-4xl mx-auto">
                    เลือกประตูของคุณ หรือเปิดทั้ง 3 ประตูได้ในเวลาเดียวกัน<br>
                    เพราะที่นี่ <span class="text-purple-400 font-semibold">โอกาสไม่มีขีดจำกัด</span>
                </p>
            </div>

            {{-- 3 Door Cards Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

                {{-- Door 1: นักลงทุน --}}
                <a href="{{ route('frontend.investors') }}"
                   class="group relative block perspective-2000"
                   x-intersect.once="$el.classList.add('door-enter')"
                   style="animation-delay: 0.1s;">

                    {{-- Glow Effect --}}
                    <div class="absolute inset-0 bg-gradient-to-br from-purple-500 to-pink-500 rounded-3xl blur-3xl opacity-0 group-hover:opacity-40 transition-opacity duration-500"></div>

                    {{-- Card --}}
                    <div class="relative backdrop-blur-xl bg-white/5 border-2 border-white/10 rounded-3xl p-8 shadow-2xl
                                hover:bg-white/10 hover:border-purple-500/50
                                transform group-hover:scale-105 group-hover:-translate-y-2
                                transition-all duration-500">

                        {{-- Door Icon --}}
                        <div class="text-7xl mb-6 text-center animate-float group-hover:scale-110 transition-transform duration-300">
                            💰
                        </div>

                        {{-- Door Number Badge --}}
                        <div class="inline-flex items-center gap-2 px-4 py-2 backdrop-blur-xl bg-purple-600/20 border border-purple-500/30 rounded-full mb-4">
                            <span class="text-2xl">🚪</span>
                            <span class="text-sm font-bold text-purple-400">ประตูที่ 1</span>
                        </div>

                        {{-- Title --}}
                        <h3 class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-pink-400 mb-4 leading-tight">
                            สำหรับนักลงทุน
                        </h3>

                        {{-- Description --}}
                        <p class="text-lg text-gray-300 mb-6 leading-relaxed">
                            การลงทุนที่<span class="text-purple-400 font-semibold">เชื่อมกับชีวิตจริง</span>
                            เริ่มต้นง่าย ไม่ต้องมีเงินมาก
                        </p>

                        {{-- Key Points --}}
                        <ul class="space-y-3 mb-8">
                            <li class="flex items-start gap-2 text-gray-400">
                                <i class="fas fa-check-circle text-purple-400 mt-1"></i>
                                <span>ระบบอัตโนมัติ ทำงาน 24/7</span>
                            </li>
                            <li class="flex items-start gap-2 text-gray-400">
                                <i class="fas fa-check-circle text-purple-400 mt-1"></i>
                                <span>ROI สูง มั่นคง โปร่งใส</span>
                            </li>
                            <li class="flex items-start gap-2 text-gray-400">
                                <i class="fas fa-check-circle text-purple-400 mt-1"></i>
                                <span>สร้างรายได้เสริมครอบครัว</span>
                            </li>
                        </ul>

                        {{-- CTA --}}
                        <div class="flex items-center justify-between text-purple-400 font-bold group-hover:text-purple-300 transition-colors">
                            <span>เรียนรู้เพิ่มเติม</span>
                            <i class="fas fa-arrow-right group-hover:translate-x-2 transition-transform"></i>
                        </div>

                        {{-- Hover Shine Effect --}}
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000 pointer-events-none rounded-3xl"></div>
                    </div>
                </a>

                {{-- Door 2: นักพัฒนา --}}
                <a href="{{ route('frontend.developers') }}"
                   class="group relative block perspective-2000"
                   x-intersect.once="$el.classList.add('door-enter')"
                   style="animation-delay: 0.2s;">

                    {{-- Glow Effect --}}
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-3xl blur-3xl opacity-0 group-hover:opacity-40 transition-opacity duration-500"></div>

                    {{-- Card --}}
                    <div class="relative backdrop-blur-xl bg-white/5 border-2 border-white/10 rounded-3xl p-8 shadow-2xl
                                hover:bg-white/10 hover:border-blue-500/50
                                transform group-hover:scale-105 group-hover:-translate-y-2
                                transition-all duration-500">

                        {{-- Door Icon --}}
                        <div class="text-7xl mb-6 text-center animate-float-delayed group-hover:scale-110 transition-transform duration-300">
                            💻
                        </div>

                        {{-- Door Number Badge --}}
                        <div class="inline-flex items-center gap-2 px-4 py-2 backdrop-blur-xl bg-blue-600/20 border border-blue-500/30 rounded-full mb-4">
                            <span class="text-2xl">🚪</span>
                            <span class="text-sm font-bold text-blue-400">ประตูที่ 2</span>
                        </div>

                        {{-- Title --}}
                        <h3 class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-cyan-400 mb-4 leading-tight">
                            สำหรับนักพัฒนา
                        </h3>

                        {{-- Description --}}
                        <p class="text-lg text-gray-300 mb-6 leading-relaxed">
                            เปลี่ยน<span class="text-blue-400 font-semibold">Code เป็นรายได้</span>
                            ขายผลงานได้ทันที มี Marketplace พร้อม
                        </p>

                        {{-- Key Points --}}
                        <ul class="space-y-3 mb-8">
                            <li class="flex items-start gap-2 text-gray-400">
                                <i class="fas fa-check-circle text-blue-400 mt-1"></i>
                                <span>ได้ถึง 70% Revenue Share</span>
                            </li>
                            <li class="flex items-start gap-2 text-gray-400">
                                <i class="fas fa-check-circle text-blue-400 mt-1"></i>
                                <span>Passive Income ต่อเนื่อง</span>
                            </li>
                            <li class="flex items-start gap-2 text-gray-400">
                                <i class="fas fa-check-circle text-blue-400 mt-1"></i>
                                <span>Community นักพัฒนาแน่น</span>
                            </li>
                        </ul>

                        {{-- CTA --}}
                        <div class="flex items-center justify-between text-blue-400 font-bold group-hover:text-blue-300 transition-colors">
                            <span>เรียนรู้เพิ่มเติม</span>
                            <i class="fas fa-arrow-right group-hover:translate-x-2 transition-transform"></i>
                        </div>

                        {{-- Hover Shine Effect --}}
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000 pointer-events-none rounded-3xl"></div>
                    </div>
                </a>

                {{-- Door 3: ชุมชน --}}
                <a href="{{ route('frontend.community') }}"
                   class="group relative block perspective-2000"
                   x-intersect.once="$el.classList.add('door-enter')"
                   style="animation-delay: 0.3s;">

                    {{-- Glow Effect --}}
                    <div class="absolute inset-0 bg-gradient-to-br from-green-500 to-emerald-500 rounded-3xl blur-3xl opacity-0 group-hover:opacity-40 transition-opacity duration-500"></div>

                    {{-- Card --}}
                    <div class="relative backdrop-blur-xl bg-white/5 border-2 border-white/10 rounded-3xl p-8 shadow-2xl
                                hover:bg-white/10 hover:border-green-500/50
                                transform group-hover:scale-105 group-hover:-translate-y-2
                                transition-all duration-500">

                        {{-- Door Icon --}}
                        <div class="text-7xl mb-6 text-center animate-float group-hover:scale-110 transition-transform duration-300">
                            🌍
                        </div>

                        {{-- Door Number Badge --}}
                        <div class="inline-flex items-center gap-2 px-4 py-2 backdrop-blur-xl bg-green-600/20 border border-green-500/30 rounded-full mb-4">
                            <span class="text-2xl">🚪</span>
                            <span class="text-sm font-bold text-green-400">ประตูที่ 3</span>
                        </div>

                        {{-- Title --}}
                        <h3 class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-green-400 to-emerald-400 mb-4 leading-tight">
                            สำหรับชุมชน
                        </h3>

                        {{-- Description --}}
                        <p class="text-lg text-gray-300 mb-6 leading-relaxed">
                            <span class="text-green-400 font-semibold">เติบโตไปด้วยกัน</span>
                            แชร์ความรู้ สร้างเครือข่าย ช่วยเหลือกัน
                        </p>

                        {{-- Key Points --}}
                        <ul class="space-y-3 mb-8">
                            <li class="flex items-start gap-2 text-gray-400">
                                <i class="fas fa-check-circle text-green-400 mt-1"></i>
                                <span>Affiliate System ได้ทั้ง 2 ฝ่าย</span>
                            </li>
                            <li class="flex items-start gap-2 text-gray-400">
                                <i class="fas fa-check-circle text-green-400 mt-1"></i>
                                <span>เรียนรู้ฟรี แลกเปลี่ยนความรู้</span>
                            </li>
                            <li class="flex items-start gap-2 text-gray-400">
                                <i class="fas fa-check-circle text-green-400 mt-1"></i>
                                <span>ช่วยเหลือสังคมร่วมกัน</span>
                            </li>
                        </ul>

                        {{-- CTA --}}
                        <div class="flex items-center justify-between text-green-400 font-bold group-hover:text-green-300 transition-colors">
                            <span>เรียนรู้เพิ่มเติม</span>
                            <i class="fas fa-arrow-right group-hover:translate-x-2 transition-transform"></i>
                        </div>

                        {{-- Hover Shine Effect --}}
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000 pointer-events-none rounded-3xl"></div>
                    </div>
</a>

            </div>

            {{-- Bottom CTA --}}
            <div class="text-center mt-20"
                 x-intersect.once="$el.classList.add('door-enter')">
                <p class="text-xl text-gray-400 mb-6">
                    ยังไม่แน่ใจว่าประตูไหนเหมาะกับคุณ?
                </p>
                <a href="{{ route('register') }}"
                   class="inline-flex items-center gap-3 px-12 py-5 bg-gradient-to-r from-purple-600 via-pink-600 to-orange-600 hover:from-purple-700 hover:via-pink-700 hover:to-orange-700 text-white font-bold text-xl rounded-2xl shadow-lg hover:shadow-2xl transform hover:scale-105 transition-all duration-300">
                    <i class="fas fa-rocket"></i>
                    <span>ลงทะเบียนฟรี เปิดทั้ง 3 ประตูได้เลย!</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>

        </div>
    </section>

    {{-- ================================================================
        FINAL CTA SECTION
    ================================================================ --}}
    <section class="relative py-32 overflow-hidden bg-gradient-to-br from-purple-900 via-pink-900 to-orange-900">

        {{-- Background Pattern --}}
        <div class="absolute inset-0 opacity-20"
             style="background-image: radial-gradient(circle, white 1px, transparent 1px);
                    background-size: 40px 40px;">
        </div>

        <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-white">

            <div x-intersect.once="$el.classList.add('door-enter')">
                <h2 class="text-5xl md:text-7xl font-black mb-8 leading-tight">
                    พร้อมที่จะเปิดประตู<br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 to-orange-300">
                        สู่ความสำเร็จ
                    </span>แล้วหรือยัง?
                </h2>

                <p class="text-2xl md:text-3xl mb-16 text-white/90 leading-relaxed">
                    เข้าร่วมกับผู้ใช้นับพันที่<span class="font-bold text-yellow-300">เปลี่ยนชีวิต</span>ด้วย Thaiprompt<br>
                    <span class="text-xl text-white/70">วันนี้คือวันที่ดีที่สุดที่จะเริ่มต้น</span>
                </p>

                {{-- CTA Buttons --}}
                <div class="flex flex-col sm:flex-row gap-6 justify-center items-center mb-16">
                    <a href="{{ route('register') }}"
                       class="group relative overflow-hidden px-16 py-6 bg-white text-purple-900 font-black text-2xl rounded-2xl shadow-2xl hover:shadow-white/50 transform hover:scale-110 transition-all duration-300">
                        {{-- Shine Effect --}}
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-yellow-200/50 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>

                        <span class="relative z-10 flex items-center gap-3">
                            <i class="fas fa-rocket text-3xl"></i>
                            <span>ลงทะเบียนฟรีเลย!</span>
                            <i class="fas fa-arrow-right group-hover:translate-x-2 transition-transform"></i>
                        </span>
                    </a>

                    <a href="#doors"
                       class="px-16 py-6 backdrop-blur-xl bg-white/10 border-2 border-white/30 font-bold text-2xl rounded-2xl hover:bg-white/20 transition-all duration-300">
                        <i class="fas fa-book-open mr-2"></i>
                        อ่านเพิ่มเติม
                    </a>
                </div>

                {{-- Final Trust Badges --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-3xl mx-auto">
                    <div class="backdrop-blur-xl bg-white/10 rounded-2xl p-6 border border-white/20">
                        <div class="text-4xl mb-2">🔒</div>
                        <p class="font-bold text-lg mb-1">ปลอดภัย 100%</p>
                        <p class="text-sm text-white/70">เข้ารหัส SSL</p>
                    </div>
                    <div class="backdrop-blur-xl bg-white/10 rounded-2xl p-6 border border-white/20">
                        <div class="text-4xl mb-2">⚡</div>
                        <p class="font-bold text-lg mb-1">รองรับ 24/7</p>
                        <p class="text-sm text-white/70">ทีมพร้อมช่วย</p>
                    </div>
                    <div class="backdrop-blur-xl bg-white/10 rounded-2xl p-6 border border-white/20">
                        <div class="text-4xl mb-2">💎</div>
                        <p class="font-bold text-lg mb-1">มาตรฐานสากล</p>
                        <p class="text-sm text-white/70">คุณภาพรับรอง</p>
                    </div>
                </div>
            </div>

        </div>
    </section>

</div>

@endsection

@push('scripts')
{{-- GSAP for advanced animations --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>

<script>
/**
 * Logo 3D Controller
 *
 * ควบคุมการหมุน 3D ของโลโก้ตามตำแหน่งเมาส์
 */
function logo3DController() {
    return {
        // ----------------
        // STATE
        // ----------------
        mouseX: 0,
        mouseY: 0,
        targetX: 0,
        targetY: 0,
        isHovering: false,

        // ----------------
        // METHODS
        // ----------------

        /**
         * จัดการการเคลื่อนที่ของเมาส์
         * คำนวณมุมหมุนจากตำแหน่งเมาส์
         */
        handleMouseMove(event) {
            // คำนวณตำแหน่งสัมพัทธ์ของเมาส์ (-1 ถึง 1)
            const centerX = window.innerWidth / 2;
            const centerY = window.innerHeight / 2;

            // ความไวต่อการหมุน (ค่ามากขึ้น = หมุนมากขึ้น)
            const sensitivity = 15;

            this.targetX = ((event.clientX - centerX) / centerX) * sensitivity;
            this.targetY = ((event.clientY - centerY) / centerY) * sensitivity;

            // Smooth interpolation (ทำให้การหมุนลื่นไหล)
            this.mouseX = this.lerp(this.mouseX, this.targetX, 0.1);
            this.mouseY = this.lerp(this.mouseY, this.targetY, 0.1);
        },

        /**
         * รีเซ็ตการหมุนเมื่อเมาส์ออก
         */
        resetRotation() {
            // Smooth return to center
            this.targetX = 0;
            this.targetY = 0;

            // Animate back to center
            const animate = () => {
                this.mouseX = this.lerp(this.mouseX, 0, 0.05);
                this.mouseY = this.lerp(this.mouseY, 0, 0.05);

                if (Math.abs(this.mouseX) > 0.01 || Math.abs(this.mouseY) > 0.01) {
                    requestAnimationFrame(animate);
                } else {
                    this.mouseX = 0;
                    this.mouseY = 0;
                }
            };

            requestAnimationFrame(animate);
        },

        /**
         * Linear interpolation สำหรับ smooth animation
         */
        lerp(start, end, factor) {
            return start + (end - start) * factor;
        }
    };
}

/**
 * Homepage Storytelling Manager
 *
 * จัดการ animations, particles, และ interactivity ทั้งหมด
 * เวอร์ชัน 4.0 - Futuristic 3D Edition
 */
function homepageStorytellingManager() {
    return {
        // ----------------
        // STATE
        // ----------------
        particles: [],
        sparkles: [],
        isVisible: false,

        // ----------------
        // LIFECYCLE
        // ----------------
        init() {
            console.log('🎨 Homepage Storytelling V4.0 - Futuristic 3D Edition');

            // Generate particles (หลายขนาด)
            this.generateParticles();

            // Generate sparkles (ประกายแสง)
            this.generateSparkles();

            // Initialize GSAP animations
            this.initGSAPAnimations();

            // Initialize 3D effects
            this.init3DEffects();

            // Show content
            setTimeout(() => {
                this.isVisible = true;
            }, 100);
        },

        // ----------------
        // METHODS
        // ----------------

        /**
         * สร้าง floating particles (หลายขนาดและสี)
         */
        generateParticles() {
            const particleCount = 50;
            const sizes = ['sm', 'md', 'lg'];

            for (let i = 0; i < particleCount; i++) {
                this.particles.push({
                    x: Math.random() * 100,
                    duration: 12 + Math.random() * 15,
                    delay: Math.random() * 8,
                    size: sizes[Math.floor(Math.random() * sizes.length)]
                });
            }
        },

        /**
         * สร้าง sparkle effects (ประกายแสงกระพริบ)
         */
        generateSparkles() {
            const sparkleCount = 20;

            for (let i = 0; i < sparkleCount; i++) {
                this.sparkles.push({
                    x: Math.random() * 100,
                    y: Math.random() * 100,
                    delay: Math.random() * 5
                });
            }
        },

        /**
         * เริ่มต้น GSAP animations
         */
        initGSAPAnimations() {
            gsap.registerPlugin(ScrollTrigger);

            // Parallax effect สำหรับ hero section
            gsap.to('.animate-float-3d', {
                y: -80,
                scrollTrigger: {
                    trigger: 'body',
                    start: 'top top',
                    end: '50% top',
                    scrub: 1
                }
            });

            // Parallax สำหรับ floating shapes
            gsap.to('.floating-shape', {
                y: -100,
                rotation: 45,
                scrollTrigger: {
                    trigger: 'body',
                    start: 'top top',
                    end: 'bottom top',
                    scrub: 2
                }
            });

            // Fade out animated orbs on scroll
            gsap.to('.animated-orb', {
                opacity: 0.2,
                scale: 0.8,
                scrollTrigger: {
                    trigger: 'body',
                    start: 'top top',
                    end: '30% top',
                    scrub: true
                }
            });

            // Scale down orbital rings on scroll
            gsap.to('.orbit-ring', {
                scale: 0.7,
                opacity: 0,
                scrollTrigger: {
                    trigger: 'body',
                    start: 'top top',
                    end: '40% top',
                    scrub: true
                }
            });

            console.log('✅ GSAP animations initialized');
        },

        /**
         * เริ่มต้น 3D effects เพิ่มเติม
         */
        init3DEffects() {
            // Mouse movement parallax for background elements
            document.addEventListener('mousemove', (e) => {
                const moveX = (e.clientX / window.innerWidth - 0.5) * 20;
                const moveY = (e.clientY / window.innerHeight - 0.5) * 20;

                // Move floating shapes slightly
                document.querySelectorAll('.floating-shape').forEach((shape, index) => {
                    const factor = (index + 1) * 0.3;
                    shape.style.transform = `translate(${moveX * factor}px, ${moveY * factor}px)`;
                });

                // Move animated orbs
                document.querySelectorAll('.animated-orb').forEach((orb, index) => {
                    const factor = (index + 1) * 0.1;
                    orb.style.marginLeft = `${moveX * factor}px`;
                    orb.style.marginTop = `${moveY * factor}px`;
                });
            });

            console.log('✅ 3D effects initialized');
        },

        /**
         * Scroll ไปยัง section
         */
        scrollToSection(sectionId) {
            const element = document.getElementById(sectionId);
            if (element) {
                element.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }
    };
}

// Export to global
window.logo3DController = logo3DController;
window.homepageStorytellingManager = homepageStorytellingManager;
</script>
@endpush
