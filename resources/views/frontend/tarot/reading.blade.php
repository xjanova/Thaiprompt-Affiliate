@extends('layouts.app')

@section('content')
{{-- หน้าผลการทำนายไพ่ทาโร่ต์ - V3 Premium Design --}}
<div class="min-h-screen bg-gradient-to-br from-slate-950 via-purple-950 to-indigo-950 py-8 relative overflow-hidden">

    {{-- พื้นหลังแบบ Mystical --}}
    <div class="absolute inset-0 pointer-events-none">
        {{-- ดาวกระพริบ --}}
        <div class="stars-bg absolute inset-0"></div>

        {{-- หมอกเรืองแสง --}}
        <div class="absolute -top-32 -left-32 w-96 h-96 bg-purple-600/20 rounded-full blur-[120px] animate-pulse"></div>
        <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-pink-600/20 rounded-full blur-[120px] animate-pulse" style="animation-delay: 1s;"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-amber-600/5 rounded-full blur-[100px]"></div>
    </div>

    <div class="container mx-auto px-4 relative z-10">
        {{-- Header --}}
        <div class="text-center mb-8">
            <div class="inline-block mb-4">
                <div class="w-20 h-20 bg-gradient-to-br from-amber-400 to-yellow-500 rounded-2xl flex items-center justify-center mx-auto shadow-2xl shadow-amber-500/30 animate-bounce-slow">
                    <span class="text-4xl">🔮</span>
                </div>
            </div>

            <h1 class="text-4xl md:text-5xl font-black mb-4">
                <span class="bg-gradient-to-r from-amber-300 via-yellow-300 to-amber-300 bg-clip-text text-transparent">
                    ผลการทำนาย
                </span>
            </h1>

            <div class="inline-block bg-gradient-to-br from-slate-900/80 via-purple-900/60 to-slate-900/80 backdrop-blur-xl rounded-2xl px-6 py-3 border border-white/10">
                <p class="text-purple-200 text-lg">
                    {{ $reading->category->name_th }} • {{ $reading->spreadType->name_th }}
                </p>
            </div>

            @if($reading->question)
            <div class="mt-4 max-w-2xl mx-auto">
                <div class="bg-gradient-to-r from-purple-900/60 via-pink-900/40 to-purple-900/60 backdrop-blur-lg rounded-2xl px-6 py-4 border border-white/10">
                    <p class="text-sm text-purple-300/70 mb-1">คำถามของคุณ</p>
                    <p class="text-white text-xl font-medium">
                        "{{ $reading->question }}"
                    </p>
                </div>
            </div>
            @endif
        </div>

        {{-- 3D WebGL Canvas --}}
        <div id="canvas-container" class="relative mx-auto mb-8 rounded-3xl overflow-hidden" style="height: 550px; max-width: 1200px;">
            {{-- Glow Border --}}
            <div class="absolute inset-0 bg-gradient-to-r from-amber-500 via-purple-500 to-pink-500 rounded-3xl blur-sm opacity-30"></div>
            <div class="absolute inset-[2px] bg-slate-950 rounded-3xl"></div>
            <canvas id="tarot-canvas" class="relative z-10"></canvas>

            {{-- Corner Decorations --}}
            <div class="absolute top-4 left-4 w-12 h-12 border-l-2 border-t-2 border-amber-400/50 rounded-tl-lg"></div>
            <div class="absolute top-4 right-4 w-12 h-12 border-r-2 border-t-2 border-purple-400/50 rounded-tr-lg"></div>
            <div class="absolute bottom-4 left-4 w-12 h-12 border-l-2 border-b-2 border-pink-400/50 rounded-bl-lg"></div>
            <div class="absolute bottom-4 right-4 w-12 h-12 border-r-2 border-b-2 border-cyan-400/50 rounded-br-lg"></div>
        </div>

        {{-- Card Details --}}
        <div id="card-details" class="opacity-0">
            {{-- Cards Information Grid --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8 max-w-7xl mx-auto">
                @foreach($reading->cards as $index => $readingCard)
                <div class="card-info group" data-card-index="{{ $index }}">
                    <div class="relative">
                        {{-- Glow Effect --}}
                        <div class="absolute inset-0 bg-gradient-to-br from-purple-500/20 via-pink-500/15 to-amber-500/20 rounded-3xl blur-xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

                        {{-- Card Body --}}
                        <div class="relative bg-gradient-to-br from-slate-900/95 via-purple-900/85 to-slate-900/95 backdrop-blur-xl rounded-3xl p-6 border border-white/10 group-hover:border-purple-400/40 shadow-2xl transition-all duration-500">
                            {{-- Card Header --}}
                            <div class="flex items-start gap-4 mb-4">
                                {{-- Card Number Badge --}}
                                <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-amber-400 to-yellow-500 rounded-xl flex items-center justify-center text-slate-900 font-bold text-lg shadow-lg shadow-amber-500/30">
                                    {{ $index + 1 }}
                                </div>

                                <div class="flex-1">
                                    {{-- Position Name --}}
                                    <div class="text-sm text-purple-400 font-medium mb-1 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                        </svg>
                                        {{ $readingCard->position_name }}
                                    </div>

                                    {{-- Card Name --}}
                                    <h3 class="text-xl md:text-2xl font-bold text-white flex items-center gap-2 flex-wrap">
                                        {{ $readingCard->card->getName() }}
                                        @if($readingCard->is_reversed)
                                            <span class="inline-flex items-center gap-1 px-2 py-1 bg-red-500/20 border border-red-500/40 text-red-400 text-xs rounded-full">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                                                </svg>
                                                กลับหัว
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-1 bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 text-xs rounded-full">
                                                ✨ หัวตั้ง
                                            </span>
                                        @endif
                                    </h3>
                                </div>
                            </div>

                            {{-- Detailed Interpretation --}}
                            @if($readingCard->interpretation)
                            <div class="bg-gradient-to-br from-purple-900/50 to-slate-900/50 rounded-xl p-4 border border-purple-500/20">
                                <div class="tarot-interpretation">{{ $readingCard->interpretation }}</div>
                            </div>
                            @else
                            {{-- Fallback: Basic Card Meaning --}}
                            <div class="bg-gradient-to-br from-purple-900/60 to-slate-900/60 rounded-xl p-4 border border-purple-500/20">
                                <p class="text-purple-100/90 leading-relaxed">
                                    {{ $readingCard->card->getMeaning($readingCard->is_reversed) }}
                                </p>
                            </div>
                            @endif

                            {{-- Keywords --}}
                            @php
                                $keywords = $readingCard->card->keywords_th ?? [];
                            @endphp
                            @if(!empty($keywords))
                            <div class="mt-4 pt-4 border-t border-white/10">
                                <div class="flex flex-wrap gap-2">
                                    @foreach(array_slice($keywords, 0, 4) as $keyword)
                                    <span class="px-3 py-1 bg-purple-500/20 text-purple-300 text-xs rounded-full border border-purple-500/30">
                                        {{ $keyword }}
                                    </span>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Overall Interpretation --}}
            @if($reading->interpretation)
            <div class="max-w-5xl mx-auto mb-8">
                <div class="relative">
                    {{-- Glow Effect --}}
                    <div class="absolute inset-0 bg-gradient-to-r from-amber-500/20 via-purple-500/20 to-pink-500/20 rounded-3xl blur-2xl"></div>

                    {{-- Content --}}
                    <div class="relative bg-gradient-to-br from-slate-900/95 via-purple-900/85 to-slate-900/95 backdrop-blur-xl rounded-3xl p-8 border border-amber-500/20 shadow-2xl">
                        {{-- Header --}}
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-14 h-14 bg-gradient-to-br from-amber-400 to-yellow-500 rounded-2xl flex items-center justify-center shadow-lg shadow-amber-500/40">
                                <span class="text-3xl">✨</span>
                            </div>
                            <div>
                                <h2 class="text-2xl md:text-3xl font-bold text-white">บทสรุปการทำนาย</h2>
                                <p class="text-amber-300/70 text-sm">คำแนะนำโดยรวมจากไพ่ทั้งหมด</p>
                            </div>
                        </div>

                        {{-- Interpretation Content --}}
                        <div class="tarot-overall-interpretation">{{ $reading->interpretation }}</div>

                        {{-- Footer --}}
                        <div class="mt-8 pt-6 border-t border-white/10 flex items-center justify-between flex-wrap gap-4">
                            <div class="flex items-center gap-3 text-purple-300/60 text-sm">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                </svg>
                                <span>ทำนายเมื่อ: {{ $reading->created_at->format('d/m/Y H:i') }}</span>
                            </div>

                            <div class="flex items-center gap-2">
                                @if($reading->is_free)
                                <span class="px-3 py-1 bg-emerald-500/20 text-emerald-400 text-xs rounded-full border border-emerald-500/40">
                                    🎁 ทำนายฟรี
                                </span>
                                @endif
                                <span class="px-3 py-1 bg-purple-500/20 text-purple-300 text-xs rounded-full border border-purple-500/40">
                                    {{ $reading->spreadType->name_th }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Actions --}}
            <div class="flex flex-wrap gap-4 justify-center">
                @auth
                    @if(!$reading->is_saved)
                    <button onclick="saveReading()"
                            class="group relative overflow-hidden px-8 py-4 bg-gradient-to-r from-emerald-500 to-teal-500 rounded-2xl font-bold text-white shadow-lg shadow-emerald-500/30 transform hover:scale-105 transition-all duration-300">
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                        <span class="relative flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                            </svg>
                            บันทึกคำทำนาย
                        </span>
                    </button>
                    @else
                    <div class="px-8 py-4 bg-emerald-600/30 border border-emerald-500/50 rounded-2xl font-bold text-emerald-300 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        บันทึกแล้ว
                    </div>
                    @endif
                @endauth

                <a href="{{ route('tarot.category', $reading->category->slug) }}"
                   class="group relative overflow-hidden px-8 py-4 bg-gradient-to-r from-purple-500 to-pink-500 rounded-2xl font-bold text-white shadow-lg shadow-purple-500/30 transform hover:scale-105 transition-all duration-300">
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                    <span class="relative flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        ทำนายใหม่
                    </span>
                </a>

                <a href="{{ route('tarot.index') }}"
                   class="px-8 py-4 bg-white/10 backdrop-blur-lg border border-white/20 rounded-2xl font-bold text-white hover:bg-white/20 transition-all duration-300 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    หน้าหลัก
                </a>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Canvas Styling */
    #tarot-canvas {
        width: 100%;
        height: 100%;
        display: block;
        border-radius: 22px;
    }

    #canvas-container {
        position: relative;
    }

    /* ดาวบนพื้นหลัง */
    .stars-bg {
        background-image:
            radial-gradient(2px 2px at 20px 30px, white, transparent),
            radial-gradient(2px 2px at 40px 70px, rgba(255,255,255,0.8), transparent),
            radial-gradient(1px 1px at 90px 40px, white, transparent),
            radial-gradient(2px 2px at 160px 120px, rgba(255,255,255,0.6), transparent),
            radial-gradient(1px 1px at 230px 80px, white, transparent),
            radial-gradient(2px 2px at 300px 150px, rgba(255,255,255,0.7), transparent);
        background-size: 350px 200px;
        animation: stars-twinkle 8s ease-in-out infinite;
    }

    @keyframes stars-twinkle {
        0%, 100% { opacity: 0.5; }
        50% { opacity: 1; }
    }

    /* Bounce ช้า */
    @keyframes bounce-slow {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }
    .animate-bounce-slow {
        animation: bounce-slow 3s ease-in-out infinite;
    }

    /* Perspective สำหรับ 3D */
    .perspective-1000 { perspective: 1000px; }
    .rotate-y-3 { transform: rotateY(3deg); }

    /* Card info animation */
    .card-info {
        opacity: 0;
        transform: translateY(30px);
        transition: all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .card-info.visible {
        opacity: 1;
        transform: translateY(0);
    }

    /* Glow Animation */
    @keyframes mystical-glow {
        0%, 100% {
            box-shadow: 0 0 20px rgba(251, 191, 36, 0.3),
                        0 0 40px rgba(168, 85, 247, 0.2),
                        0 0 60px rgba(236, 72, 153, 0.1);
        }
        50% {
            box-shadow: 0 0 30px rgba(251, 191, 36, 0.5),
                        0 0 60px rgba(168, 85, 247, 0.3),
                        0 0 90px rgba(236, 72, 153, 0.2);
        }
    }

    #canvas-container {
        animation: mystical-glow 4s ease-in-out infinite;
    }

    /* =================================
       Tarot Interpretation Styling
       ================================= */

    /* Card Interpretation */
    .tarot-interpretation {
        color: rgba(233, 213, 255, 0.95);
        line-height: 1.9;
        font-size: 0.95rem;
        white-space: pre-line;
    }

    /* Overall Interpretation Styling */
    .tarot-overall-interpretation {
        color: rgba(233, 213, 255, 0.95);
        line-height: 1.9;
        font-size: 1rem;
        white-space: pre-line;
    }

    /* Card highlight on hover */
    .card-info:hover .tarot-interpretation {
        color: rgba(255, 255, 255, 0.98);
    }

    /* Selection highlight */
    .tarot-interpretation::selection,
    .tarot-overall-interpretation::selection {
        background: rgba(168, 85, 247, 0.4);
        color: white;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .tarot-interpretation {
            font-size: 0.9rem;
            line-height: 1.7;
        }

        .tarot-overall-interpretation {
            font-size: 0.95rem;
            line-height: 1.8;
        }
    }
</style>
@endpush

@push('scripts')
@php
$cardsData = $reading->cards->map(function($card) {
    return [
        'name' => $card->card->getName(),
        'image_url' => $card->card->image_url,
        'position_name' => $card->position_name,
        'is_reversed' => $card->is_reversed
    ];
});
@endphp
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="importmap">
{
  "imports": {
    "three": "https://cdn.jsdelivr.net/npm/three@0.160.0/build/three.module.js",
    "three/addons/": "https://cdn.jsdelivr.net/npm/three@0.160.0/examples/jsm/"
  }
}
</script>
<script type="module">
import * as THREE from 'three';
import { EffectComposer } from 'three/addons/postprocessing/EffectComposer.js';
import { RenderPass } from 'three/addons/postprocessing/RenderPass.js';
import { UnrealBloomPass } from 'three/addons/postprocessing/UnrealBloomPass.js';

document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('tarot-canvas');
    const canvasContainer = document.getElementById('canvas-container');
    const cardDetails = document.getElementById('card-details');

    // Card data from Laravel
    const cardsData = @json($cardsData);

    let scene, camera, renderer, composer;
    let cardMeshes = [];
    let particles = [];
    let lights = {};

    // Initialize Three.js Scene
    function initScene() {
        // Scene
        scene = new THREE.Scene();
        scene.background = new THREE.Color(0x1a1a2e);
        scene.fog = new THREE.Fog(0x1a1a2e, 20, 100);

        // Camera
        camera = new THREE.PerspectiveCamera(
            60,
            canvasContainer.clientWidth / canvasContainer.clientHeight,
            0.1,
            1000
        );
        camera.position.set(0, 5, 20);
        camera.lookAt(0, 0, 0);

        // Renderer
        renderer = new THREE.WebGLRenderer({
            canvas: canvas,
            antialias: true,
            alpha: true
        });
        renderer.setSize(canvasContainer.clientWidth, canvasContainer.clientHeight);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        renderer.shadowMap.enabled = true;
        renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        renderer.toneMapping = THREE.ACESFilmicToneMapping;
        renderer.toneMappingExposure = 1.5;

        // Post-processing
        composer = new EffectComposer(renderer);
        const renderPass = new RenderPass(scene, camera);
        composer.addPass(renderPass);

        const bloomPass = new UnrealBloomPass(
            new THREE.Vector2(window.innerWidth, window.innerHeight),
            1.2, // strength
            0.6, // radius
            0.8  // threshold
        );
        composer.addPass(bloomPass);

        // Setup lighting
        setupLighting();

        // Create magical floor
        createMagicalFloor();

        // Create particle system
        createParticles();

        // Create cards
        createCards();

        // Event listeners
        window.addEventListener('resize', onWindowResize);

        // Start animation loop
        animate();

        // Start the reveal animation
        setTimeout(() => {
            revealCards();
        }, 500);
    }

    // Setup dramatic lighting
    function setupLighting() {
        // Ambient light
        const ambientLight = new THREE.AmbientLight(0x6b21a8, 0.4);
        scene.add(ambientLight);

        // Main directional light
        const directionalLight = new THREE.DirectionalLight(0xffffff, 1.5);
        directionalLight.position.set(0, 20, 10);
        directionalLight.castShadow = true;
        directionalLight.shadow.camera.left = -30;
        directionalLight.shadow.camera.right = 30;
        directionalLight.shadow.camera.top = 30;
        directionalLight.shadow.camera.bottom = -30;
        directionalLight.shadow.mapSize.width = 2048;
        directionalLight.shadow.mapSize.height = 2048;
        scene.add(directionalLight);

        // Dramatic colored point lights
        lights.purple = new THREE.PointLight(0x9333ea, 3, 40);
        lights.purple.position.set(-10, 8, 5);
        scene.add(lights.purple);

        lights.pink = new THREE.PointLight(0xec4899, 3, 40);
        lights.pink.position.set(10, 8, 5);
        scene.add(lights.pink);

        lights.gold = new THREE.PointLight(0xfbbf24, 2.5, 35);
        lights.gold.position.set(0, 12, -5);
        scene.add(lights.gold);

        lights.blue = new THREE.PointLight(0x3b82f6, 2, 30);
        lights.blue.position.set(0, 5, 15);
        scene.add(lights.blue);

        // Spotlight for drama
        const spotlight = new THREE.SpotLight(0xffffff, 2);
        spotlight.position.set(0, 25, 0);
        spotlight.angle = Math.PI / 6;
        spotlight.penumbra = 0.3;
        spotlight.castShadow = true;
        scene.add(spotlight);
        lights.spotlight = spotlight;
    }

    // Create magical floor
    function createMagicalFloor() {
        const geometry = new THREE.CircleGeometry(30, 64);
        const material = new THREE.MeshStandardMaterial({
            color: 0x2d1b69,
            emissive: 0x4c1d95,
            emissiveIntensity: 0.3,
            roughness: 0.4,
            metalness: 0.6
        });
        const floor = new THREE.Mesh(geometry, material);
        floor.rotation.x = -Math.PI / 2;
        floor.position.y = -3;
        floor.receiveShadow = true;
        scene.add(floor);

        // Add magical circle pattern
        const ringGeometry = new THREE.RingGeometry(15, 15.2, 64);
        const ringMaterial = new THREE.MeshBasicMaterial({
            color: 0xfbbf24,
            side: THREE.DoubleSide,
            transparent: true,
            opacity: 0.8
        });
        const ring = new THREE.Mesh(ringGeometry, ringMaterial);
        ring.rotation.x = -Math.PI / 2;
        ring.position.y = -2.9;
        scene.add(ring);
        scene.userData.magicRing = ring;
    }

    // Create particle system
    function createParticles() {
        const particleCount = 500;
        const geometry = new THREE.BufferGeometry();
        const positions = [];
        const colors = [];
        const sizes = [];
        const velocities = [];

        const colorOptions = [
            new THREE.Color(0x9333ea),
            new THREE.Color(0xec4899),
            new THREE.Color(0xfbbf24),
            new THREE.Color(0x3b82f6)
        ];

        for (let i = 0; i < particleCount; i++) {
            // Spiral distribution
            const angle = (i / particleCount) * Math.PI * 4;
            const radius = 15 + (i / particleCount) * 10;

            positions.push(
                Math.cos(angle) * radius,
                (Math.random() - 0.5) * 20,
                Math.sin(angle) * radius
            );

            const color = colorOptions[Math.floor(Math.random() * colorOptions.length)];
            colors.push(color.r, color.g, color.b);

            sizes.push(Math.random() * 3 + 1);

            velocities.push({
                x: (Math.random() - 0.5) * 0.03,
                y: (Math.random() - 0.5) * 0.03,
                z: (Math.random() - 0.5) * 0.03,
                rotation: Math.random() * 0.01
            });
        }

        geometry.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));
        geometry.setAttribute('color', new THREE.Float32BufferAttribute(colors, 3));
        geometry.setAttribute('size', new THREE.Float32BufferAttribute(sizes, 1));

        const material = new THREE.PointsMaterial({
            size: 1,
            vertexColors: true,
            transparent: true,
            opacity: 0.8,
            blending: THREE.AdditiveBlending,
            sizeAttenuation: true
        });

        const particleSystem = new THREE.Points(geometry, material);
        scene.add(particleSystem);

        scene.userData.particleSystem = particleSystem;
        scene.userData.particleVelocities = velocities;
    }

    // Create card meshes with textures
    function createCards() {
        // 🃏 (2026-06-08) อัตราส่วน 9:16 ให้ตรงรูปไพ่ใหม่ (เดิม 3×4.5 = 2:3 → รูป 9:16 ถูกบีบแนวนอน)
        //   หุบความกว้างแทนเพิ่มความสูง → กล้อง/แนวตั้งของฉากไม่กระทบ
        const cardWidth = 2.53; // = 4.5 × 9 / 16 ≈ 2.53
        const cardHeight = 4.5;
        const cardDepth = 0.1;
        const spacing = cardWidth + 1.5;

        const totalCards = cardsData.length;
        const startX = -(totalCards - 1) * spacing / 2;

        const textureLoader = new THREE.TextureLoader();

        cardsData.forEach((cardData, index) => {
            // Card geometry
            const geometry = new THREE.BoxGeometry(cardWidth, cardHeight, cardDepth);

            // Materials array for different faces
            const materials = [];

            // Load card front texture
            const frontTexture = textureLoader.load(
                cardData.image_url,
                () => {
                    // Texture loaded successfully
                },
                undefined,
                () => {
                    // Fallback if texture fails to load
                    console.log('Failed to load texture for card:', cardData.name);
                }
            );

            // Front face (with card image)
            const frontMaterial = new THREE.MeshStandardMaterial({
                map: frontTexture,
                roughness: 0.3,
                metalness: 0.2
            });

            // Back face (purple gradient)
            const backMaterial = new THREE.MeshStandardMaterial({
                color: 0x667eea,
                emissive: 0x764ba2,
                emissiveIntensity: 0.3,
                roughness: 0.3,
                metalness: 0.7
            });

            // Side materials (golden)
            const sideMaterial = new THREE.MeshStandardMaterial({
                color: 0xfbbf24,
                roughness: 0.4,
                metalness: 0.8
            });

            // Order: right, left, top, bottom, front, back
            materials.push(sideMaterial, sideMaterial, sideMaterial, sideMaterial, frontMaterial, backMaterial);

            const card = new THREE.Mesh(geometry, materials);

            // Position cards
            const xPos = startX + (index * spacing);
            card.position.set(xPos, 0, 0);

            // Start with card back facing camera (rotated 180 degrees on Y)
            card.rotation.y = Math.PI;
            card.rotation.x = -Math.PI / 8; // Slight tilt

            // If reversed, rotate on Z axis
            if (cardData.is_reversed) {
                card.userData.isReversed = true;
                card.userData.reverseRotation = Math.PI;
            }

            card.castShadow = true;
            card.receiveShadow = true;

            card.userData = {
                ...card.userData,
                index: index,
                targetRotation: 0,
                flipped: false
            };

            scene.add(card);
            cardMeshes.push(card);
        });

        // Camera initial position based on number of cards
        const cameraDistance = Math.max(15, totalCards * 2);
        camera.position.set(0, 5, cameraDistance);
        camera.lookAt(0, 0, 0);
    }

    // Reveal cards with dramatic animation
    function revealCards() {
        // Animate camera
        gsap.to(camera.position, {
            duration: 2,
            y: 8,
            z: camera.position.z * 0.8,
            ease: "power2.inOut"
        });

        // Flip cards one by one
        cardMeshes.forEach((card, index) => {
            // Move card up first
            gsap.to(card.position, {
                duration: 0.6,
                delay: index * 0.4,
                y: 3,
                ease: "power2.out"
            });

            // Flip card
            gsap.to(card.rotation, {
                duration: 1.2,
                delay: index * 0.4 + 0.3,
                y: card.userData.isReversed ? Math.PI * 2 : 0,
                x: 0,
                ease: "power3.inOut",
                onStart: () => {
                    // Particle explosion on flip
                    createCardExplosion(card.position);

                    // Flash light
                    flashLight(lights.spotlight);
                },
                onComplete: () => {
                    card.userData.flipped = true;

                    // Settle card down
                    gsap.to(card.position, {
                        duration: 0.8,
                        y: 0,
                        ease: "bounce.out"
                    });

                    // Add floating animation
                    gsap.to(card.position, {
                        duration: 2 + Math.random(),
                        y: Math.sin(index) * 0.3,
                        repeat: -1,
                        yoyo: true,
                        ease: "sine.inOut",
                        delay: Math.random() * 0.5
                    });

                    // Gentle rotation animation
                    gsap.to(card.rotation, {
                        duration: 3 + Math.random(),
                        z: (Math.random() - 0.5) * 0.1,
                        repeat: -1,
                        yoyo: true,
                        ease: "sine.inOut"
                    });

                    // Show card details after last card
                    if (index === cardMeshes.length - 1) {
                        setTimeout(() => {
                            showCardDetails();
                        }, 1000);
                    }
                }
            });
        });
    }

    // Create particle explosion effect
    function createCardExplosion(position) {
        const particleCount = 50;
        const geometry = new THREE.BufferGeometry();
        const positions = [];
        const velocities = [];
        const colors = [];

        for (let i = 0; i < particleCount; i++) {
            positions.push(position.x, position.y, position.z);

            const velocity = new THREE.Vector3(
                (Math.random() - 0.5) * 0.5,
                Math.random() * 0.3,
                (Math.random() - 0.5) * 0.5
            );
            velocities.push(velocity);

            const color = new THREE.Color();
            color.setHSL(Math.random(), 1, 0.6);
            colors.push(color.r, color.g, color.b);
        }

        geometry.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));
        geometry.setAttribute('color', new THREE.Float32BufferAttribute(colors, 3));

        const material = new THREE.PointsMaterial({
            size: 0.3,
            vertexColors: true,
            transparent: true,
            opacity: 1,
            blending: THREE.AdditiveBlending
        });

        const explosion = new THREE.Points(geometry, material);
        scene.add(explosion);

        // Animate explosion
        let frame = 0;
        const maxFrames = 60;
        const explode = () => {
            frame++;
            const posArray = explosion.geometry.attributes.position.array;

            for (let i = 0; i < particleCount; i++) {
                const i3 = i * 3;
                posArray[i3] += velocities[i].x;
                posArray[i3 + 1] += velocities[i].y;
                posArray[i3 + 2] += velocities[i].z;

                velocities[i].y -= 0.01; // Gravity
            }

            explosion.geometry.attributes.position.needsUpdate = true;
            explosion.material.opacity = 1 - (frame / maxFrames);

            if (frame < maxFrames) {
                requestAnimationFrame(explode);
            } else {
                scene.remove(explosion);
            }
        };
        explode();
    }

    // Flash light effect
    function flashLight(light) {
        const originalIntensity = light.intensity;
        gsap.to(light, {
            duration: 0.1,
            intensity: originalIntensity * 3,
            onComplete: () => {
                gsap.to(light, {
                    duration: 0.3,
                    intensity: originalIntensity
                });
            }
        });
    }

    // Show card details
    function showCardDetails() {
        gsap.to(cardDetails, {
            duration: 1,
            opacity: 1,
            ease: "power2.out"
        });

        // Animate each card info
        const cardInfos = document.querySelectorAll('.card-info');
        cardInfos.forEach((info, index) => {
            gsap.to(info, {
                duration: 0.6,
                delay: index * 0.15,
                opacity: 1,
                y: 0,
                ease: "back.out(1.7)"
            });
        });
    }

    // Animation loop
    function animate() {
        requestAnimationFrame(animate);

        const time = Date.now() * 0.001;

        // Animate lights
        lights.purple.intensity = 3 + Math.sin(time * 0.7) * 0.5;
        lights.pink.intensity = 3 + Math.cos(time * 0.8) * 0.5;
        lights.gold.intensity = 2.5 + Math.sin(time * 0.6) * 0.4;
        lights.blue.intensity = 2 + Math.cos(time * 0.9) * 0.3;

        // Rotate magic ring
        if (scene.userData.magicRing) {
            scene.userData.magicRing.rotation.z += 0.002;
        }

        // Animate particles
        const particleSystem = scene.userData.particleSystem;
        if (particleSystem) {
            const positions = particleSystem.geometry.attributes.position.array;
            const velocities = scene.userData.particleVelocities;

            for (let i = 0; i < velocities.length; i++) {
                const i3 = i * 3;
                positions[i3] += velocities[i].x;
                positions[i3 + 1] += velocities[i].y;
                positions[i3 + 2] += velocities[i].z;

                // Boundary check and bounce
                const x = positions[i3];
                const y = positions[i3 + 1];
                const z = positions[i3 + 2];
                const dist = Math.sqrt(x * x + z * z);

                if (dist > 30) velocities[i].x *= -1;
                if (y > 15 || y < -5) velocities[i].y *= -1;
            }

            particleSystem.geometry.attributes.position.needsUpdate = true;
            particleSystem.rotation.y += 0.0003;
        }

        // Render
        composer.render();
    }

    // Window resize handler
    function onWindowResize() {
        camera.aspect = canvasContainer.clientWidth / canvasContainer.clientHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(canvasContainer.clientWidth, canvasContainer.clientHeight);
        composer.setSize(canvasContainer.clientWidth, canvasContainer.clientHeight);
    }

    // Initialize
    initScene();
});

function saveReading() {
    fetch('{{ route('tarot.reading.save', $reading->id) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire('สำเร็จ!', data.message, 'success').then(() => {
                location.reload();
            });
        } else {
            Swal.fire('เกิดข้อผิดพลาด', data.error, 'error');
        }
    });
}

</script>
@endpush
@endsection
