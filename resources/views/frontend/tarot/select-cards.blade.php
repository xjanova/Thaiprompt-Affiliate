@extends('layouts.app')

@section('content')
{{-- หน้าเลือกไพ่ทาโร่ต์ - V3 Premium Design with Mystical Effects --}}
<div class="min-h-screen bg-gradient-to-br from-slate-950 via-purple-950 to-indigo-950 overflow-hidden relative"
     x-data="{ showInstructions: false }">

    {{-- พื้นหลังแบบ Mystical --}}
    <div class="absolute inset-0 pointer-events-none">
        {{-- ดาวกระพริบ --}}
        <div class="stars-bg absolute inset-0"></div>

        {{-- หมอกเรืองแสง --}}
        <div class="absolute -top-32 -left-32 w-96 h-96 bg-purple-600/30 rounded-full blur-[120px] animate-pulse"></div>
        <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-pink-600/30 rounded-full blur-[120px] animate-pulse" style="animation-delay: 1s;"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-cyan-600/10 rounded-full blur-[100px] animate-pulse" style="animation-delay: 2s;"></div>

        {{-- วงแหวนเวทย์มนต์รอบ Canvas --}}
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[900px] h-[900px]">
            <div class="absolute inset-0 border border-purple-500/20 rounded-full animate-spin-slow"></div>
            <div class="absolute inset-12 border border-pink-500/15 rounded-full animate-spin-slow-reverse"></div>
            <div class="absolute inset-24 border border-cyan-500/10 rounded-full animate-spin-slow" style="animation-duration: 50s;"></div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-6 relative z-10">
        {{-- Header --}}
        <div class="text-center mb-6">
            <h1 class="text-4xl md:text-5xl font-black mb-2" id="header-title">
                <span class="bg-gradient-to-r from-purple-300 via-pink-300 to-cyan-300 bg-clip-text text-transparent">
                    กำลังสับไพ่...
                </span>
            </h1>
            <p class="text-purple-200/80 text-lg" id="header-subtitle">
                โปรดรอสักครู่ ไพ่กำลังถูกสับ
            </p>
        </div>

        {{-- Progress Indicator ที่สวยงามขึ้น --}}
        <div class="max-w-lg mx-auto mb-6">
            <div class="relative bg-gradient-to-br from-slate-900/80 via-purple-900/60 to-slate-900/80 backdrop-blur-xl rounded-2xl p-5 border border-white/10 shadow-2xl">
                {{-- Glow Effect --}}
                <div class="absolute inset-0 bg-gradient-to-r from-purple-500/20 via-pink-500/10 to-cyan-500/20 rounded-2xl blur-xl opacity-50"></div>

                <div class="relative">
                    {{-- ตัวนับ --}}
                    <div class="flex items-center justify-between text-white mb-3">
                        <div class="flex items-center gap-2">
                            <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center shadow-lg shadow-purple-500/30">
                                <span class="text-xl">🎴</span>
                            </div>
                            <div>
                                <div class="text-xs text-purple-300/70">เลือกแล้ว</div>
                                <div class="font-bold text-lg"><span id="selected-count">0</span> ใบ</div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <div>
                                <div class="text-xs text-purple-300/70 text-right">ต้องการ</div>
                                <div class="font-bold text-lg"><span id="required-count">{{ $spreadType->card_count }}</span> ใบ</div>
                            </div>
                            <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-yellow-500 rounded-xl flex items-center justify-center shadow-lg shadow-amber-500/30">
                                <span class="text-xl">✨</span>
                            </div>
                        </div>
                    </div>

                    {{-- Progress Bar ที่มี Glow --}}
                    <div class="relative">
                        <div class="w-full bg-slate-800/80 rounded-full h-4 overflow-hidden border border-white/10">
                            <div id="progress-bar"
                                 class="h-full bg-gradient-to-r from-purple-500 via-pink-500 to-amber-500 rounded-full transition-all duration-700 ease-out relative"
                                 style="width: 0%">
                                {{-- Shine effect on progress --}}
                                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent animate-shimmer"></div>
                            </div>
                        </div>
                        {{-- Glow under progress --}}
                        <div id="progress-glow" class="absolute -bottom-2 left-0 h-4 bg-gradient-to-r from-purple-500 via-pink-500 to-amber-500 rounded-full blur-md opacity-50 transition-all duration-700" style="width: 0%"></div>
                    </div>

                    {{-- Selected Cards Preview --}}
                    <div id="selected-preview" class="flex justify-center gap-2 mt-4 min-h-[40px]">
                        {{-- จะถูก populate โดย JavaScript --}}
                    </div>
                </div>
            </div>
        </div>

        {{-- 3D WebGL Canvas --}}
        <div id="canvas-container" class="relative mx-auto rounded-3xl overflow-hidden" style="height: 550px; max-width: 1200px;">
            {{-- Glow Border --}}
            <div class="absolute inset-0 bg-gradient-to-r from-purple-500 via-pink-500 to-cyan-500 rounded-3xl blur-sm opacity-30"></div>
            <div class="absolute inset-[2px] bg-slate-950 rounded-3xl"></div>
            <canvas id="tarot-canvas" class="relative z-10"></canvas>

            {{-- Corner Decorations --}}
            <div class="absolute top-4 left-4 w-12 h-12 border-l-2 border-t-2 border-purple-400/50 rounded-tl-lg"></div>
            <div class="absolute top-4 right-4 w-12 h-12 border-r-2 border-t-2 border-pink-400/50 rounded-tr-lg"></div>
            <div class="absolute bottom-4 left-4 w-12 h-12 border-l-2 border-b-2 border-cyan-400/50 rounded-bl-lg"></div>
            <div class="absolute bottom-4 right-4 w-12 h-12 border-r-2 border-b-2 border-amber-400/50 rounded-br-lg"></div>
        </div>

        {{-- Instructions --}}
        <div id="instructions" class="text-center mt-6 opacity-0 transform translate-y-4 transition-all duration-500">
            <div class="inline-block bg-gradient-to-br from-slate-900/80 via-purple-900/60 to-slate-900/80 backdrop-blur-xl rounded-2xl px-8 py-4 border border-white/10">
                <p class="text-white text-xl font-bold mb-2 flex items-center justify-center gap-2">
                    <span class="animate-pulse">✨</span>
                    เลือกไพ่ {{ $spreadType->card_count }} ใบ
                    <span class="animate-pulse">✨</span>
                </p>
                <p class="text-purple-200/70">
                    ใช้สัญชาตญาณของคุณ • ชี้เม้าส์และคลิกเพื่อเลือก
                </p>
            </div>
        </div>

        {{-- Confirm Button --}}
        <div id="confirm-button-container" class="text-center mt-6 opacity-0 transform scale-90 transition-all duration-500">
            <button id="confirm-selection"
                    class="group relative overflow-hidden px-14 py-5 bg-gradient-to-r from-purple-600 via-pink-600 to-purple-600 rounded-2xl font-bold text-xl text-white shadow-2xl shadow-purple-500/40 transform hover:scale-105 transition-all duration-300">
                {{-- Shine Effect --}}
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                {{-- Glow Pulse --}}
                <div class="absolute inset-0 bg-gradient-to-r from-purple-400 to-pink-400 rounded-2xl blur-xl opacity-0 group-hover:opacity-50 animate-pulse"></div>

                <span class="relative flex items-center justify-center gap-3">
                    <span class="text-2xl">🔮</span>
                    <span>เปิดไพ่ทำนาย</span>
                </span>
            </button>

            <p class="text-purple-300/60 text-sm mt-3">
                ไพ่ที่คุณเลือกจะเปิดเผยคำตอบ
            </p>
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

    /* หมุนช้าๆ */
    @keyframes spin-slow {
        from { transform: translate(-50%, -50%) rotate(0deg); }
        to { transform: translate(-50%, -50%) rotate(360deg); }
    }
    .animate-spin-slow {
        animation: spin-slow 60s linear infinite;
    }
    .animate-spin-slow-reverse {
        animation: spin-slow 45s linear infinite reverse;
    }

    /* Shimmer effect */
    @keyframes shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }
    .animate-shimmer {
        animation: shimmer 2s linear infinite;
    }

    /* Card Selection Animation */
    @keyframes card-selected {
        0% { transform: scale(1); }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }

    .card-preview-item {
        animation: card-selected 0.5s ease-out;
    }

    /* Loading overlay */
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(10px);
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 22px;
        z-index: 1000;
    }

    /* Mystical Glow Animation */
    @keyframes mystical-glow {
        0%, 100% {
            box-shadow: 0 0 20px rgba(168, 85, 247, 0.5),
                        0 0 40px rgba(236, 72, 153, 0.3),
                        0 0 60px rgba(168, 85, 247, 0.2);
        }
        50% {
            box-shadow: 0 0 30px rgba(168, 85, 247, 0.7),
                        0 0 60px rgba(236, 72, 153, 0.5),
                        0 0 90px rgba(168, 85, 247, 0.3);
        }
    }

    #canvas-container:hover {
        animation: mystical-glow 2s ease-in-out infinite;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
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
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { EffectComposer } from 'three/addons/postprocessing/EffectComposer.js';
import { RenderPass } from 'three/addons/postprocessing/RenderPass.js';
import { UnrealBloomPass } from 'three/addons/postprocessing/UnrealBloomPass.js';

document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('tarot-canvas');
    const canvasContainer = document.getElementById('canvas-container');
    const headerTitle = document.getElementById('header-title');
    const headerSubtitle = document.getElementById('header-subtitle');
    const instructions = document.getElementById('instructions');
    const progressBar = document.getElementById('progress-bar');
    const selectedCountEl = document.getElementById('selected-count');
    const confirmButtonContainer = document.getElementById('confirm-button-container');
    const confirmButton = document.getElementById('confirm-selection');

    const requiredCards = {{ $spreadType->card_count }};
    const totalCards = 78; // Full tarot deck

    let selectedCards = [];
    let cardMeshes = [];
    let hoveredCard = null;
    let scene, camera, renderer, composer;
    let raycaster, mouse;
    let particles = [];

    // Initialize Three.js Scene
    function initScene() {
        // Scene
        scene = new THREE.Scene();
        scene.background = new THREE.Color(0x1a1a2e);
        scene.fog = new THREE.Fog(0x1a1a2e, 20, 80);

        // Camera
        camera = new THREE.PerspectiveCamera(
            50,
            canvasContainer.clientWidth / canvasContainer.clientHeight,
            0.1,
            1000
        );
        camera.position.set(0, 15, 40);
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
        renderer.toneMappingExposure = 1.2;

        // Post-processing
        composer = new EffectComposer(renderer);
        const renderPass = new RenderPass(scene, camera);
        composer.addPass(renderPass);

        const bloomPass = new UnrealBloomPass(
            new THREE.Vector2(window.innerWidth, window.innerHeight),
            0.5, // strength
            0.4, // radius
            0.85 // threshold
        );
        composer.addPass(bloomPass);

        // Raycaster for mouse interaction
        raycaster = new THREE.Raycaster();
        mouse = new THREE.Vector2();

        // Lighting
        setupLighting();

        // Table surface
        createTableSurface();

        // Create particle system
        createParticles();

        // Create cards
        createCards();

        // Event listeners
        window.addEventListener('resize', onWindowResize);
        canvas.addEventListener('mousemove', onMouseMove);
        canvas.addEventListener('click', onMouseClick);

        // Start animation loop
        animate();

        // Deal cards after short delay
        setTimeout(() => {
            dealCards();
        }, 500);
    }

    // Setup lighting system
    function setupLighting() {
        // Ambient light
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.3);
        scene.add(ambientLight);

        // Directional light (main light)
        const directionalLight = new THREE.DirectionalLight(0xffffff, 1);
        directionalLight.position.set(10, 20, 10);
        directionalLight.castShadow = true;
        directionalLight.shadow.camera.left = -50;
        directionalLight.shadow.camera.right = 50;
        directionalLight.shadow.camera.top = 50;
        directionalLight.shadow.camera.bottom = -50;
        directionalLight.shadow.mapSize.width = 2048;
        directionalLight.shadow.mapSize.height = 2048;
        scene.add(directionalLight);

        // Purple point light (left)
        const purpleLight = new THREE.PointLight(0x9333ea, 2, 50);
        purpleLight.position.set(-15, 10, 10);
        scene.add(purpleLight);

        // Pink point light (right)
        const pinkLight = new THREE.PointLight(0xec4899, 2, 50);
        pinkLight.position.set(15, 10, 10);
        scene.add(pinkLight);

        // Blue point light (back)
        const blueLight = new THREE.PointLight(0x3b82f6, 1.5, 50);
        blueLight.position.set(0, 5, -20);
        scene.add(blueLight);

        // Animate lights
        function animateLights(time) {
            purpleLight.intensity = 2 + Math.sin(time * 0.001) * 0.5;
            pinkLight.intensity = 2 + Math.cos(time * 0.001) * 0.5;
            blueLight.intensity = 1.5 + Math.sin(time * 0.0015) * 0.3;
        }

        // Add to animation loop
        scene.userData.animateLights = animateLights;
    }

    // Create table surface
    function createTableSurface() {
        const geometry = new THREE.PlaneGeometry(100, 40);
        const material = new THREE.MeshStandardMaterial({
            color: 0x1a4d2e,
            roughness: 0.7,
            metalness: 0.2
        });
        const table = new THREE.Mesh(geometry, material);
        table.rotation.x = -Math.PI / 2;
        table.position.y = -2;
        table.receiveShadow = true;
        scene.add(table);
    }

    // Create particle system
    function createParticles() {
        const particleCount = 200;
        const geometry = new THREE.BufferGeometry();
        const positions = [];
        const colors = [];
        const sizes = [];

        const colorOptions = [
            new THREE.Color(0x9333ea), // purple
            new THREE.Color(0xec4899), // pink
            new THREE.Color(0x3b82f6), // blue
            new THREE.Color(0xfbbf24)  // gold
        ];

        for (let i = 0; i < particleCount; i++) {
            positions.push(
                (Math.random() - 0.5) * 100,
                Math.random() * 50,
                (Math.random() - 0.5) * 100
            );

            const color = colorOptions[Math.floor(Math.random() * colorOptions.length)];
            colors.push(color.r, color.g, color.b);

            sizes.push(Math.random() * 2 + 1);

            particles.push({
                velocity: new THREE.Vector3(
                    (Math.random() - 0.5) * 0.02,
                    (Math.random() - 0.5) * 0.02,
                    (Math.random() - 0.5) * 0.02
                ),
                index: i
            });
        }

        geometry.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));
        geometry.setAttribute('color', new THREE.Float32BufferAttribute(colors, 3));
        geometry.setAttribute('size', new THREE.Float32BufferAttribute(sizes, 1));

        const material = new THREE.PointsMaterial({
            size: 0.5,
            vertexColors: true,
            transparent: true,
            opacity: 0.6,
            blending: THREE.AdditiveBlending
        });

        const particleSystem = new THREE.Points(geometry, material);
        scene.add(particleSystem);

        scene.userData.particleSystem = particleSystem;
    }

    // Create card meshes
    function createCards() {
        const cardWidth = 2;
        const cardHeight = 3;
        const cardDepth = 0.05;

        for (let i = 0; i < totalCards; i++) {
            const geometry = new THREE.BoxGeometry(cardWidth, cardHeight, cardDepth);

            // Card back material with gradient effect
            const material = new THREE.MeshStandardMaterial({
                color: 0x667eea,
                emissive: 0x764ba2,
                emissiveIntensity: 0.2,
                roughness: 0.3,
                metalness: 0.7
            });

            const card = new THREE.Mesh(geometry, material);

            // Start at center for dealing animation
            card.position.set(0, 0, 0);
            card.rotation.z = Math.random() * 0.1 - 0.05;
            card.castShadow = true;
            card.receiveShadow = true;

            card.userData = {
                index: i,
                selected: false,
                targetPosition: new THREE.Vector3(),
                targetRotation: new THREE.Euler(),
                hovered: false
            };

            scene.add(card);
            cardMeshes.push(card);
        }
    }

    // Calculate card positions in arc layout
    function calculateCardPositions(totalCards) {
        const positions = [];
        const radius = 25;
        const arcAngle = Math.PI * 0.8; // 144 degrees
        const startAngle = -arcAngle / 2;

        for (let i = 0; i < totalCards; i++) {
            const progress = i / (totalCards - 1);
            const angle = startAngle + (arcAngle * progress);

            const x = Math.sin(angle) * radius;
            const z = -Math.cos(angle) * radius + 5;
            const y = Math.sin(progress * Math.PI) * 1; // Slight arc in Y

            positions.push({
                position: new THREE.Vector3(x, y, z),
                rotation: new THREE.Euler(0, -angle, 0)
            });
        }

        return positions;
    }

    // Deal cards animation
    function dealCards() {
        const positions = calculateCardPositions(totalCards);

        cardMeshes.forEach((card, index) => {
            card.userData.targetPosition.copy(positions[index].position);
            card.userData.targetRotation.copy(positions[index].rotation);

            // Animate with GSAP
            gsap.to(card.position, {
                duration: 0.8,
                delay: index * 0.02,
                x: positions[index].position.x,
                y: positions[index].position.y,
                z: positions[index].position.z,
                ease: "power2.out",
                onComplete: () => {
                    if (index === cardMeshes.length - 1) {
                        showInstructions();
                    }
                }
            });

            gsap.to(card.rotation, {
                duration: 0.8,
                delay: index * 0.02,
                y: positions[index].rotation.y,
                ease: "power2.out"
            });
        });
    }

    // Show instructions พร้อม animation ที่สวยงาม
    function showInstructions() {
        gsap.to(headerTitle, {
            duration: 0.5,
            opacity: 0,
            y: -20,
            onComplete: () => {
                headerTitle.innerHTML = '<span class="bg-gradient-to-r from-purple-300 via-pink-300 to-cyan-300 bg-clip-text text-transparent">🔮 เลือกไพ่ทาโร่ของคุณ</span>';
                gsap.to(headerTitle, { duration: 0.5, opacity: 1, y: 0 });
            }
        });

        gsap.to(headerSubtitle, {
            duration: 0.5,
            opacity: 0,
            onComplete: () => {
                headerSubtitle.textContent = 'ใช้สัญชาตญาณของคุณในการเลือกไพ่';
                gsap.to(headerSubtitle, { duration: 0.5, opacity: 1 });
            }
        });

        gsap.to(instructions, {
            duration: 0.7,
            opacity: 1,
            y: 0,
            ease: "back.out(1.7)",
            delay: 0.3
        });
    }

    // เพิ่ม preview card ตอนเลือก
    function addSelectedCardPreview(cardIndex) {
        const preview = document.getElementById('selected-preview');
        const item = document.createElement('div');
        item.className = 'card-preview-item w-8 h-12 bg-gradient-to-br from-purple-500 via-pink-500 to-purple-600 rounded-lg shadow-lg shadow-purple-500/30 flex items-center justify-center';
        item.innerHTML = '<span class="text-white text-xs font-bold">' + selectedCards.length + '</span>';
        preview.appendChild(item);
    }

    // Mouse move handler
    function onMouseMove(event) {
        const rect = canvas.getBoundingClientRect();
        mouse.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
        mouse.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;

        // Raycast to check intersection
        raycaster.setFromCamera(mouse, camera);
        const intersects = raycaster.intersectObjects(cardMeshes);

        // Reset all cards
        cardMeshes.forEach(card => {
            if (!card.userData.selected && !card.userData.hovered) {
                gsap.to(card.position, {
                    duration: 0.3,
                    y: card.userData.targetPosition.y,
                    ease: "power2.out"
                });
                card.material.emissiveIntensity = 0.2;
            }
        });

        // Hover effect
        if (intersects.length > 0) {
            const card = intersects[0].object;
            if (!card.userData.selected) {
                hoveredCard = card;
                card.userData.hovered = true;
                canvas.style.cursor = 'pointer';

                gsap.to(card.position, {
                    duration: 0.3,
                    y: card.userData.targetPosition.y + 2,
                    ease: "power2.out"
                });

                gsap.to(card.material, {
                    duration: 0.3,
                    emissiveIntensity: 0.5
                });
            }
        } else {
            hoveredCard = null;
            canvas.style.cursor = 'default';
            cardMeshes.forEach(card => {
                card.userData.hovered = false;
            });
        }
    }

    // Mouse click handler
    function onMouseClick(event) {
        if (!hoveredCard) return;
        if (hoveredCard.userData.selected) return;
        if (selectedCards.length >= requiredCards) return;

        // Mark as selected
        hoveredCard.userData.selected = true;
        selectedCards.push(hoveredCard.userData.index);

        // Animate card away
        gsap.to(hoveredCard.position, {
            duration: 0.6,
            y: hoveredCard.userData.targetPosition.y + 8,
            ease: "power2.in"
        });

        gsap.to(hoveredCard.material, {
            duration: 0.6,
            opacity: 0.3,
            transparent: true
        });

        gsap.to(hoveredCard.rotation, {
            duration: 0.6,
            x: Math.PI * 2,
            ease: "power2.in"
        });

        // Update progress
        updateProgress();

        // Check if all cards selected
        if (selectedCards.length === requiredCards) {
            showConfirmButton();
        }
    }

    // Update progress bar พร้อม glow effect
    function updateProgress() {
        const progress = (selectedCards.length / requiredCards) * 100;
        selectedCountEl.textContent = selectedCards.length;

        // Progress bar
        gsap.to(progressBar, {
            duration: 0.7,
            width: progress + '%',
            ease: "power2.out"
        });

        // Progress glow
        const progressGlow = document.getElementById('progress-glow');
        if (progressGlow) {
            gsap.to(progressGlow, {
                duration: 0.7,
                width: progress + '%',
                ease: "power2.out"
            });
        }

        // เพิ่ม preview card
        addSelectedCardPreview(selectedCards.length);
    }

    // Show confirm button พร้อม celebration effect
    function showConfirmButton() {
        gsap.to(instructions, {
            duration: 0.5,
            opacity: 0,
            y: -20
        });

        gsap.to(confirmButtonContainer, {
            duration: 0.7,
            opacity: 1,
            scale: 1,
            ease: "elastic.out(1, 0.5)",
            delay: 0.3
        });

        // เพิ่ม particle burst เมื่อเลือกครบ
        createCelebrationParticles();
    }

    // สร้าง particles เฉลิมฉลองเมื่อเลือกครบ
    function createCelebrationParticles() {
        const particleCount = 100;
        const geometry = new THREE.BufferGeometry();
        const positions = [];
        const velocities = [];
        const colors = [];

        const celebrationColors = [
            new THREE.Color(0xfbbf24), // gold
            new THREE.Color(0x9333ea), // purple
            new THREE.Color(0xec4899), // pink
            new THREE.Color(0x22d3ee)  // cyan
        ];

        for (let i = 0; i < particleCount; i++) {
            positions.push(0, 5, 0);

            const velocity = new THREE.Vector3(
                (Math.random() - 0.5) * 0.8,
                Math.random() * 0.5 + 0.2,
                (Math.random() - 0.5) * 0.8
            );
            velocities.push(velocity);

            const color = celebrationColors[Math.floor(Math.random() * celebrationColors.length)];
            colors.push(color.r, color.g, color.b);
        }

        geometry.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));
        geometry.setAttribute('color', new THREE.Float32BufferAttribute(colors, 3));

        const material = new THREE.PointsMaterial({
            size: 0.5,
            vertexColors: true,
            transparent: true,
            opacity: 1,
            blending: THREE.AdditiveBlending
        });

        const celebrationParticles = new THREE.Points(geometry, material);
        scene.add(celebrationParticles);

        // Animate particles
        let frame = 0;
        const maxFrames = 90;
        const animateCelebration = () => {
            frame++;
            const posArray = celebrationParticles.geometry.attributes.position.array;

            for (let i = 0; i < particleCount; i++) {
                const i3 = i * 3;
                posArray[i3] += velocities[i].x;
                posArray[i3 + 1] += velocities[i].y;
                posArray[i3 + 2] += velocities[i].z;

                velocities[i].y -= 0.015; // gravity
            }

            celebrationParticles.geometry.attributes.position.needsUpdate = true;
            celebrationParticles.material.opacity = 1 - (frame / maxFrames);

            if (frame < maxFrames) {
                requestAnimationFrame(animateCelebration);
            } else {
                scene.remove(celebrationParticles);
            }
        };
        animateCelebration();
    }

    // Animation loop
    function animate() {
        requestAnimationFrame(animate);

        const time = Date.now();

        // Animate lights
        if (scene.userData.animateLights) {
            scene.userData.animateLights(time);
        }

        // Animate particles
        const particleSystem = scene.userData.particleSystem;
        if (particleSystem) {
            const positions = particleSystem.geometry.attributes.position.array;
            particles.forEach((particle, i) => {
                const i3 = i * 3;
                positions[i3] += particle.velocity.x;
                positions[i3 + 1] += particle.velocity.y;
                positions[i3 + 2] += particle.velocity.z;

                // Boundary check
                if (Math.abs(positions[i3]) > 50) particle.velocity.x *= -1;
                if (positions[i3 + 1] > 50 || positions[i3 + 1] < 0) particle.velocity.y *= -1;
                if (Math.abs(positions[i3 + 2]) > 50) particle.velocity.z *= -1;
            });
            particleSystem.geometry.attributes.position.needsUpdate = true;
            particleSystem.rotation.y += 0.0002;
        }

        // Render with post-processing
        composer.render();
    }

    // Window resize handler
    function onWindowResize() {
        camera.aspect = canvasContainer.clientWidth / canvasContainer.clientHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(canvasContainer.clientWidth, canvasContainer.clientHeight);
        composer.setSize(canvasContainer.clientWidth, canvasContainer.clientHeight);
    }

    // Confirm selection
    confirmButton.addEventListener('click', function() {
        // Disable button
        confirmButton.disabled = true;
        confirmButton.innerHTML = '🔮 กำลังเปิดไพ่...';

        // Send selected cards to server
        fetch('{{ route('tarot.save-selection') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                reading_id: '{{ $readingId }}',
                selected_card_indices: selectedCards
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Redirect to reading result
                window.location.href = data.redirect_url;
            } else {
                alert('เกิดข้อผิดพลาด: ' + data.message);
                confirmButton.disabled = false;
                confirmButton.innerHTML = '🔮 เปิดไพ่';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง');
            confirmButton.disabled = false;
            confirmButton.innerHTML = '🔮 เปิดไพ่';
        });
    });

    // Initialize the scene
    initScene();
});
</script>
@endpush
@endsection
