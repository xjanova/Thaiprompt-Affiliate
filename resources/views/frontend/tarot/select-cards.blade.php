@extends('layouts.app')

@section('content')
{{-- หน้าเลือกไพ่ทาโร่ต์ - Fullscreen Immersive Experience --}}
<div class="fixed inset-0 bg-gradient-to-br from-slate-950 via-purple-950 to-indigo-950 overflow-hidden"
     x-data="{ ready: false }">

    {{-- พื้นหลังแบบ Mystical --}}
    <div class="absolute inset-0 pointer-events-none">
        {{-- ดาวกระพริบ --}}
        <div class="stars-bg absolute inset-0"></div>

        {{-- หมอกเรืองแสง --}}
        <div class="absolute -top-32 -left-32 w-96 h-96 bg-purple-600/30 rounded-full blur-[120px] animate-pulse"></div>
        <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-pink-600/30 rounded-full blur-[120px] animate-pulse" style="animation-delay: 1s;"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-cyan-600/10 rounded-full blur-[100px] animate-pulse" style="animation-delay: 2s;"></div>

        {{-- วงแหวนเวทย์มนต์รอบ Canvas --}}
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[120vmin] h-[120vmin]">
            <div class="absolute inset-0 border border-purple-500/20 rounded-full animate-spin-slow"></div>
            <div class="absolute inset-12 border border-pink-500/15 rounded-full animate-spin-slow-reverse"></div>
            <div class="absolute inset-24 border border-cyan-500/10 rounded-full animate-spin-slow" style="animation-duration: 50s;"></div>
        </div>
    </div>

    {{-- Header Overlay --}}
    <div class="absolute top-0 left-0 right-0 z-20 p-4 md:p-6">
        <div class="flex items-center justify-between max-w-7xl mx-auto">
            {{-- Logo/Title --}}
            <div class="text-center flex-1">
                <h1 class="text-2xl md:text-4xl font-black" id="header-title">
                    <span class="bg-gradient-to-r from-purple-300 via-pink-300 to-cyan-300 bg-clip-text text-transparent">
                        กำลังสับไพ่...
                    </span>
                </h1>
                <p class="text-purple-200/80 text-sm md:text-base mt-1" id="header-subtitle">
                    โปรดรอสักครู่
                </p>
            </div>
        </div>
    </div>

    {{-- Progress Indicator - Fixed at bottom --}}
    <div class="absolute bottom-0 left-0 right-0 z-20 p-4 md:p-6">
        <div class="max-w-md mx-auto">
            <div class="relative bg-gradient-to-br from-slate-900/90 via-purple-900/70 to-slate-900/90 backdrop-blur-xl rounded-2xl p-4 border border-white/10 shadow-2xl">
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
                                <div class="font-bold text-lg"><span id="selected-count">0</span> / {{ $spreadType->card_count }} ใบ</div>
                            </div>
                        </div>

                        {{-- Selected Cards Preview --}}
                        <div id="selected-preview" class="flex gap-1">
                            {{-- จะถูก populate โดย JavaScript --}}
                        </div>
                    </div>

                    {{-- Progress Bar ที่มี Glow --}}
                    <div class="relative">
                        <div class="w-full bg-slate-800/80 rounded-full h-3 overflow-hidden border border-white/10">
                            <div id="progress-bar"
                                 class="h-full bg-gradient-to-r from-purple-500 via-pink-500 to-amber-500 rounded-full transition-all duration-700 ease-out relative"
                                 style="width: 0%">
                                {{-- Shine effect on progress --}}
                                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent animate-shimmer"></div>
                            </div>
                        </div>
                        {{-- Glow under progress --}}
                        <div id="progress-glow" class="absolute -bottom-1 left-0 h-3 bg-gradient-to-r from-purple-500 via-pink-500 to-amber-500 rounded-full blur-md opacity-50 transition-all duration-700" style="width: 0%"></div>
                    </div>

                    {{-- คำแนะนำ --}}
                    <p id="instruction-text" class="text-center text-purple-200/70 text-sm mt-3">
                        คลิกที่ไพ่เพื่อเลือก
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Fullscreen 3D Canvas --}}
    <canvas id="tarot-canvas" class="absolute inset-0 w-full h-full z-10"></canvas>

    {{-- Loading Overlay --}}
    <div id="loading-overlay" class="absolute inset-0 z-50 bg-black/80 backdrop-blur-md flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-500">
        <div class="text-center">
            <div class="w-20 h-20 border-4 border-purple-400 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
            <p class="text-white text-2xl font-bold mb-2">🔮 กำลังเปิดไพ่...</p>
            <p class="text-purple-300">โปรดรอสักครู่</p>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Hide scrollbars for fullscreen experience */
    body {
        overflow: hidden !important;
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
        50% { transform: scale(1.3); }
        100% { transform: scale(1); }
    }

    .card-preview-item {
        animation: card-selected 0.4s ease-out;
    }

    /* Pulse glow animation */
    @keyframes pulse-glow {
        0%, 100% { opacity: 0.5; transform: scale(1); }
        50% { opacity: 1; transform: scale(1.05); }
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
import { EffectComposer } from 'three/addons/postprocessing/EffectComposer.js';
import { RenderPass } from 'three/addons/postprocessing/RenderPass.js';
import { UnrealBloomPass } from 'three/addons/postprocessing/UnrealBloomPass.js';

document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('tarot-canvas');
    const headerTitle = document.getElementById('header-title');
    const headerSubtitle = document.getElementById('header-subtitle');
    const progressBar = document.getElementById('progress-bar');
    const progressGlow = document.getElementById('progress-glow');
    const selectedCountEl = document.getElementById('selected-count');
    const selectedPreview = document.getElementById('selected-preview');
    const instructionText = document.getElementById('instruction-text');
    const loadingOverlay = document.getElementById('loading-overlay');

    const requiredCards = {{ $spreadType->card_count }};
    const totalCards = 78;

    let selectedCards = [];
    let cardMeshes = [];
    let hoveredCard = null;
    let scene, camera, renderer, composer;
    let raycaster, mouse;
    let particles = [];
    let isSubmitting = false;

    // Initialize Three.js Scene
    function initScene() {
        scene = new THREE.Scene();
        scene.background = new THREE.Color(0x0a0a1a);
        scene.fog = new THREE.Fog(0x0a0a1a, 30, 100);

        // Camera - adjusted for fullscreen
        camera = new THREE.PerspectiveCamera(
            60,
            window.innerWidth / window.innerHeight,
            0.1,
            1000
        );
        camera.position.set(0, 18, 35);
        camera.lookAt(0, 0, 0);

        // Renderer
        renderer = new THREE.WebGLRenderer({
            canvas: canvas,
            antialias: true,
            alpha: true
        });
        renderer.setSize(window.innerWidth, window.innerHeight);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        renderer.shadowMap.enabled = true;
        renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        renderer.toneMapping = THREE.ACESFilmicToneMapping;
        renderer.toneMappingExposure = 1.2;

        // Post-processing
        composer = new EffectComposer(renderer);
        composer.addPass(new RenderPass(scene, camera));
        composer.addPass(new UnrealBloomPass(
            new THREE.Vector2(window.innerWidth, window.innerHeight),
            0.6, 0.4, 0.85
        ));

        // Raycaster
        raycaster = new THREE.Raycaster();
        mouse = new THREE.Vector2();

        setupLighting();
        createTableSurface();
        createParticles();
        createCards();

        // Event listeners
        window.addEventListener('resize', onWindowResize);
        canvas.addEventListener('mousemove', onMouseMove);
        canvas.addEventListener('click', onMouseClick);
        canvas.addEventListener('touchstart', onTouchStart, { passive: false });

        animate();

        // Deal cards after short delay
        setTimeout(dealCards, 500);
    }

    function setupLighting() {
        scene.add(new THREE.AmbientLight(0xffffff, 0.3));

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

        const purpleLight = new THREE.PointLight(0x9333ea, 2, 60);
        purpleLight.position.set(-20, 15, 15);
        scene.add(purpleLight);

        const pinkLight = new THREE.PointLight(0xec4899, 2, 60);
        pinkLight.position.set(20, 15, 15);
        scene.add(pinkLight);

        const blueLight = new THREE.PointLight(0x3b82f6, 1.5, 60);
        blueLight.position.set(0, 10, -25);
        scene.add(blueLight);

        scene.userData.lights = { purpleLight, pinkLight, blueLight };
    }

    function createTableSurface() {
        const geometry = new THREE.PlaneGeometry(150, 60);
        const material = new THREE.MeshStandardMaterial({
            color: 0x1a3d2e,
            roughness: 0.7,
            metalness: 0.2
        });
        const table = new THREE.Mesh(geometry, material);
        table.rotation.x = -Math.PI / 2;
        table.position.y = -2;
        table.receiveShadow = true;
        scene.add(table);
    }

    function createParticles() {
        const particleCount = 300;
        const geometry = new THREE.BufferGeometry();
        const positions = [];
        const colors = [];

        const colorOptions = [
            new THREE.Color(0x9333ea),
            new THREE.Color(0xec4899),
            new THREE.Color(0x3b82f6),
            new THREE.Color(0xfbbf24)
        ];

        for (let i = 0; i < particleCount; i++) {
            positions.push(
                (Math.random() - 0.5) * 150,
                Math.random() * 60,
                (Math.random() - 0.5) * 150
            );

            const color = colorOptions[Math.floor(Math.random() * colorOptions.length)];
            colors.push(color.r, color.g, color.b);

            particles.push({
                velocity: new THREE.Vector3(
                    (Math.random() - 0.5) * 0.02,
                    (Math.random() - 0.5) * 0.02,
                    (Math.random() - 0.5) * 0.02
                )
            });
        }

        geometry.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));
        geometry.setAttribute('color', new THREE.Float32BufferAttribute(colors, 3));

        const particleSystem = new THREE.Points(geometry, new THREE.PointsMaterial({
            size: 0.5,
            vertexColors: true,
            transparent: true,
            opacity: 0.6,
            blending: THREE.AdditiveBlending
        }));
        scene.add(particleSystem);
        scene.userData.particleSystem = particleSystem;
    }

    function createCards() {
        const cardWidth = 2.2;
        const cardHeight = 3.3;
        const cardDepth = 0.05;

        for (let i = 0; i < totalCards; i++) {
            const geometry = new THREE.BoxGeometry(cardWidth, cardHeight, cardDepth);
            const material = new THREE.MeshStandardMaterial({
                color: 0x667eea,
                emissive: 0x764ba2,
                emissiveIntensity: 0.2,
                roughness: 0.3,
                metalness: 0.7
            });

            const card = new THREE.Mesh(geometry, material);
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

    function calculateCardPositions(count) {
        const positions = [];
        const radius = 30;
        const arcAngle = Math.PI * 0.85;
        const startAngle = -arcAngle / 2;

        for (let i = 0; i < count; i++) {
            const progress = i / (count - 1);
            const angle = startAngle + (arcAngle * progress);

            positions.push({
                position: new THREE.Vector3(
                    Math.sin(angle) * radius,
                    Math.sin(progress * Math.PI) * 1.5,
                    -Math.cos(angle) * radius + 8
                ),
                rotation: new THREE.Euler(0, -angle, 0)
            });
        }

        return positions;
    }

    function dealCards() {
        const positions = calculateCardPositions(totalCards);

        cardMeshes.forEach((card, index) => {
            card.userData.targetPosition.copy(positions[index].position);
            card.userData.targetRotation.copy(positions[index].rotation);

            gsap.to(card.position, {
                duration: 0.7,
                delay: index * 0.015,
                x: positions[index].position.x,
                y: positions[index].position.y,
                z: positions[index].position.z,
                ease: "power2.out",
                onComplete: () => {
                    if (index === cardMeshes.length - 1) {
                        showReady();
                    }
                }
            });

            gsap.to(card.rotation, {
                duration: 0.7,
                delay: index * 0.015,
                y: positions[index].rotation.y,
                ease: "power2.out"
            });
        });
    }

    function showReady() {
        gsap.to(headerTitle, {
            duration: 0.4,
            opacity: 0,
            y: -10,
            onComplete: () => {
                headerTitle.innerHTML = '<span class="bg-gradient-to-r from-purple-300 via-pink-300 to-cyan-300 bg-clip-text text-transparent">🔮 เลือกไพ่ของคุณ</span>';
                gsap.to(headerTitle, { duration: 0.4, opacity: 1, y: 0 });
            }
        });

        gsap.to(headerSubtitle, {
            duration: 0.4,
            opacity: 0,
            onComplete: () => {
                headerSubtitle.textContent = 'ใช้สัญชาตญาณเลือกไพ่ ' + requiredCards + ' ใบ';
                gsap.to(headerSubtitle, { duration: 0.4, opacity: 1 });
            }
        });
    }

    function onMouseMove(event) {
        mouse.x = (event.clientX / window.innerWidth) * 2 - 1;
        mouse.y = -(event.clientY / window.innerHeight) * 2 + 1;
        checkHover();
    }

    function onTouchStart(event) {
        event.preventDefault();
        const touch = event.touches[0];
        mouse.x = (touch.clientX / window.innerWidth) * 2 - 1;
        mouse.y = -(touch.clientY / window.innerHeight) * 2 + 1;
        checkHover();
        if (hoveredCard) selectCard(hoveredCard);
    }

    function checkHover() {
        raycaster.setFromCamera(mouse, camera);
        const intersects = raycaster.intersectObjects(cardMeshes);

        cardMeshes.forEach(card => {
            if (!card.userData.selected && !card.userData.hovered) {
                gsap.to(card.position, {
                    duration: 0.2,
                    y: card.userData.targetPosition.y,
                    ease: "power2.out"
                });
                card.material.emissiveIntensity = 0.2;
            }
        });

        if (intersects.length > 0) {
            const card = intersects[0].object;
            if (!card.userData.selected) {
                hoveredCard = card;
                card.userData.hovered = true;
                canvas.style.cursor = 'pointer';

                gsap.to(card.position, {
                    duration: 0.2,
                    y: card.userData.targetPosition.y + 3,
                    ease: "power2.out"
                });
                card.material.emissiveIntensity = 0.6;
            }
        } else {
            hoveredCard = null;
            canvas.style.cursor = 'default';
            cardMeshes.forEach(c => c.userData.hovered = false);
        }
    }

    function onMouseClick() {
        if (hoveredCard && !isSubmitting) {
            selectCard(hoveredCard);
        }
    }

    function selectCard(card) {
        if (card.userData.selected || selectedCards.length >= requiredCards || isSubmitting) return;

        card.userData.selected = true;
        selectedCards.push(card.userData.index);

        // Fly card away animation
        gsap.to(card.position, {
            duration: 0.5,
            y: 15,
            z: card.position.z - 10,
            ease: "power2.in"
        });

        gsap.to(card.material, {
            duration: 0.5,
            opacity: 0,
            transparent: true
        });

        gsap.to(card.rotation, {
            duration: 0.5,
            x: Math.PI,
            ease: "power2.in"
        });

        // Add preview
        addPreviewCard();
        updateProgress();

        // Auto submit when complete
        if (selectedCards.length === requiredCards) {
            setTimeout(submitSelection, 800);
        }
    }

    function addPreviewCard() {
        const item = document.createElement('div');
        item.className = 'card-preview-item w-6 h-9 bg-gradient-to-br from-purple-500 via-pink-500 to-purple-600 rounded shadow-lg shadow-purple-500/30';
        selectedPreview.appendChild(item);
    }

    function updateProgress() {
        const progress = (selectedCards.length / requiredCards) * 100;
        selectedCountEl.textContent = selectedCards.length;

        gsap.to(progressBar, { duration: 0.5, width: progress + '%', ease: "power2.out" });
        gsap.to(progressGlow, { duration: 0.5, width: progress + '%', ease: "power2.out" });

        // Update instruction text
        const remaining = requiredCards - selectedCards.length;
        if (remaining > 0) {
            instructionText.textContent = `เลือกอีก ${remaining} ใบ`;
        } else {
            instructionText.innerHTML = '<span class="text-amber-400">✨ กำลังเปิดไพ่...</span>';
        }
    }

    function submitSelection() {
        if (isSubmitting) return;
        isSubmitting = true;

        // Show loading overlay
        loadingOverlay.classList.remove('pointer-events-none');
        gsap.to(loadingOverlay, { duration: 0.3, opacity: 1 });

        // Create celebration particles
        createCelebrationParticles();

        // Send to server
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
                window.location.href = data.redirect_url;
            } else {
                alert('เกิดข้อผิดพลาด: ' + data.message);
                gsap.to(loadingOverlay, { duration: 0.3, opacity: 0 });
                loadingOverlay.classList.add('pointer-events-none');
                isSubmitting = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('เกิดข้อผิดพลาด กรุณาลองใหม่');
            gsap.to(loadingOverlay, { duration: 0.3, opacity: 0 });
            loadingOverlay.classList.add('pointer-events-none');
            isSubmitting = false;
        });
    }

    function createCelebrationParticles() {
        const count = 150;
        const geometry = new THREE.BufferGeometry();
        const positions = [];
        const velocities = [];
        const colors = [];

        const celebColors = [
            new THREE.Color(0xfbbf24),
            new THREE.Color(0x9333ea),
            new THREE.Color(0xec4899),
            new THREE.Color(0x22d3ee)
        ];

        for (let i = 0; i < count; i++) {
            positions.push(0, 8, 0);
            velocities.push(new THREE.Vector3(
                (Math.random() - 0.5) * 1,
                Math.random() * 0.6 + 0.3,
                (Math.random() - 0.5) * 1
            ));
            const c = celebColors[Math.floor(Math.random() * celebColors.length)];
            colors.push(c.r, c.g, c.b);
        }

        geometry.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));
        geometry.setAttribute('color', new THREE.Float32BufferAttribute(colors, 3));

        const celebParticles = new THREE.Points(geometry, new THREE.PointsMaterial({
            size: 0.6,
            vertexColors: true,
            transparent: true,
            blending: THREE.AdditiveBlending
        }));
        scene.add(celebParticles);

        let frame = 0;
        const animate = () => {
            frame++;
            const pos = celebParticles.geometry.attributes.position.array;
            for (let i = 0; i < count; i++) {
                const i3 = i * 3;
                pos[i3] += velocities[i].x;
                pos[i3 + 1] += velocities[i].y;
                pos[i3 + 2] += velocities[i].z;
                velocities[i].y -= 0.02;
            }
            celebParticles.geometry.attributes.position.needsUpdate = true;
            celebParticles.material.opacity = 1 - frame / 90;
            if (frame < 90) requestAnimationFrame(animate);
            else scene.remove(celebParticles);
        };
        animate();
    }

    function animate() {
        requestAnimationFrame(animate);

        const time = Date.now();

        // Animate lights
        if (scene.userData.lights) {
            const { purpleLight, pinkLight, blueLight } = scene.userData.lights;
            purpleLight.intensity = 2 + Math.sin(time * 0.001) * 0.5;
            pinkLight.intensity = 2 + Math.cos(time * 0.001) * 0.5;
            blueLight.intensity = 1.5 + Math.sin(time * 0.0015) * 0.3;
        }

        // Animate particles
        const ps = scene.userData.particleSystem;
        if (ps) {
            const positions = ps.geometry.attributes.position.array;
            particles.forEach((p, i) => {
                const i3 = i * 3;
                positions[i3] += p.velocity.x;
                positions[i3 + 1] += p.velocity.y;
                positions[i3 + 2] += p.velocity.z;
                if (Math.abs(positions[i3]) > 75) p.velocity.x *= -1;
                if (positions[i3 + 1] > 60 || positions[i3 + 1] < 0) p.velocity.y *= -1;
                if (Math.abs(positions[i3 + 2]) > 75) p.velocity.z *= -1;
            });
            ps.geometry.attributes.position.needsUpdate = true;
            ps.rotation.y += 0.0002;
        }

        composer.render();
    }

    function onWindowResize() {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
        composer.setSize(window.innerWidth, window.innerHeight);
    }

    initScene();
});
</script>
@endpush
@endsection
