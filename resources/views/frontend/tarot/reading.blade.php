@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-900 via-indigo-900 to-blue-900 py-12">
    <div class="container mx-auto px-4">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-white mb-4">
                ผลการทำนาย
            </h1>
            <p class="text-purple-200 text-lg">
                {{ $reading->category->name_th }} - {{ $reading->spreadType->name_th }}
            </p>
            @if($reading->question)
            <p class="text-white mt-4 text-xl">
                คำถาม: "{{ $reading->question }}"
            </p>
            @endif
        </div>

        <!-- 3D WebGL Canvas -->
        <div id="canvas-container" class="relative mx-auto mb-8" style="height: 600px; max-width: 1200px;">
            <canvas id="tarot-canvas"></canvas>
        </div>

        <!-- Card Details (Hidden initially, shown after animation) -->
        <div id="card-details" class="opacity-0">
            <!-- Cards Information Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                @foreach($reading->cards as $index => $readingCard)
                <div class="card-info bg-white bg-opacity-10 backdrop-blur-lg rounded-xl p-6 border border-white border-opacity-20" data-card-index="{{ $index }}">
                    <h3 class="text-2xl font-bold text-white mb-2">
                        {{ $readingCard->card->getName() }}
                        @if($readingCard->is_reversed)
                            <span class="text-red-400 text-sm">(กลับหัว)</span>
                        @endif
                    </h3>
                    <p class="text-purple-300 font-semibold mb-3">
                        {{ $readingCard->position_name }}
                    </p>
                    <div class="bg-purple-900 bg-opacity-40 p-4 rounded-lg">
                        <p class="text-purple-100">
                            {{ $readingCard->card->getMeaning($readingCard->is_reversed) }}
                        </p>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Overall Interpretation -->
            @if($reading->interpretation)
            <div class="bg-white bg-opacity-10 backdrop-blur-lg rounded-2xl p-8 mb-8">
                <h2 class="text-2xl font-bold text-white mb-4">คำแนะนำรวม</h2>
                <p class="text-purple-100 text-lg leading-relaxed">
                    {{ $reading->interpretation }}
                </p>
            </div>
            @endif

            <!-- Actions -->
            <div class="flex flex-wrap gap-4 justify-center">
                @auth
                    @if(!$reading->is_saved)
                    <button onclick="saveReading()" class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-lg font-semibold transition-all">
                        <i class="fas fa-save mr-2"></i> บันทึกคำทำนาย
                    </button>
                    @else
                    <div class="bg-green-600 text-white px-8 py-3 rounded-lg font-semibold">
                        <i class="fas fa-check mr-2"></i> บันทึกแล้ว
                    </div>
                    @endif
                @endauth

                <a href="{{ route('tarot.category', $reading->category->slug) }}" class="bg-purple-600 hover:bg-purple-700 text-white px-8 py-3 rounded-lg font-semibold transition-all">
                    <i class="fas fa-redo mr-2"></i> ทำนายใหม่
                </a>

                <a href="{{ route('tarot.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-8 py-3 rounded-lg font-semibold transition-all">
                    <i class="fas fa-home mr-2"></i> หน้าหลัก
                </a>
            </div>
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

    .card-info {
        transform: translateY(20px);
        transition: all 0.5s ease;
    }

    .card-info.visible {
        transform: translateY(0);
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
        const cardWidth = 3;
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
