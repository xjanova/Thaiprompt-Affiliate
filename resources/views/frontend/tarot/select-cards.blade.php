@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-900 via-indigo-900 to-blue-900 overflow-hidden">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-white mb-2" id="header-title">
                กำลังสับไพ่...
            </h1>
            <p class="text-purple-200 text-lg" id="header-subtitle">
                โปรดรอสักครู่
            </p>
        </div>

        <!-- Progress Indicator -->
        <div class="max-w-md mx-auto mb-8">
            <div class="bg-white bg-opacity-10 backdrop-blur-lg rounded-full p-4 border border-white border-opacity-20">
                <div class="flex items-center justify-between text-white text-sm mb-2">
                    <span>เลือกแล้ว: <span id="selected-count">0</span> ใบ</span>
                    <span>ต้องการ: <span id="required-count">{{ $spreadType->card_count }}</span> ใบ</span>
                </div>
                <div class="w-full bg-white bg-opacity-20 rounded-full h-3 overflow-hidden">
                    <div id="progress-bar" class="h-full bg-gradient-to-r from-purple-500 to-pink-500 rounded-full transition-all duration-500" style="width: 0%"></div>
                </div>
            </div>
        </div>

        <!-- 3D WebGL Canvas -->
        <div id="canvas-container" class="relative mx-auto" style="height: 600px; max-width: 1200px;">
            <canvas id="tarot-canvas"></canvas>
        </div>

        <!-- Instructions -->
        <div id="instructions" class="text-center mt-8 opacity-0">
            <p class="text-white text-xl font-semibold mb-2">
                ✨ เลือกไพ่ {{ $spreadType->card_count }} ใบ
            </p>
            <p class="text-purple-200">
                ชี้เม้าส์ที่ไพ่เพื่อเลือก (คลิกเพื่อยืนยัน)
            </p>
        </div>

        <!-- Confirm Button (appears when all cards selected) -->
        <div id="confirm-button-container" class="text-center mt-8 opacity-0">
            <button id="confirm-selection" class="bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white px-12 py-4 rounded-full font-bold text-xl shadow-2xl transform hover:scale-105 transition-all">
                🔮 เปิดไพ่
            </button>
        </div>
    </div>
</div>

@push('styles')
<style>
    #tarot-canvas {
        width: 100%;
        height: 100%;
        display: block;
        border-radius: 24px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    }

    #canvas-container {
        position: relative;
    }

    /* Loading overlay */
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 24px;
        z-index: 1000;
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

    // Show instructions
    function showInstructions() {
        gsap.to(headerTitle, {
            duration: 0.5,
            opacity: 0,
            y: -20,
            onComplete: () => {
                headerTitle.textContent = '🔮 เลือกไพ่ทาโร่ของคุณ';
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
            duration: 0.5,
            opacity: 1,
            y: 0,
            delay: 0.3
        });
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

    // Update progress bar
    function updateProgress() {
        const progress = (selectedCards.length / requiredCards) * 100;
        selectedCountEl.textContent = selectedCards.length;

        gsap.to(progressBar, {
            duration: 0.5,
            width: progress + '%',
            ease: "power2.out"
        });
    }

    // Show confirm button
    function showConfirmButton() {
        gsap.to(instructions, {
            duration: 0.5,
            opacity: 0,
            y: -20
        });

        gsap.to(confirmButtonContainer, {
            duration: 0.5,
            opacity: 1,
            scale: 1,
            ease: "back.out(1.7)",
            delay: 0.3
        });
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
