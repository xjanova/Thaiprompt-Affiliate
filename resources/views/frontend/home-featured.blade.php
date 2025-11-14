<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $siteInfo['name'] }} - หน้าแรก</title>

    <!-- Tailwind CSS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Three.js for 3D Effects -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&display=swap');

        * {
            font-family: 'Kanit', sans-serif;
        }

        body {
            overflow-x: hidden;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        /* 3D Canvas */
        #hero3d {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }

        /* Smooth Scroll */
        html {
            scroll-behavior: smooth;
        }

        /* Glass Morphism */
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .glass-strong {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        /* Animations */
        @keyframes glow {
            0%, 100% { box-shadow: 0 0 20px rgba(102, 126, 234, 0.5); }
            50% { box-shadow: 0 0 40px rgba(102, 126, 234, 0.8); }
        }

        .glow-animation {
            animation: glow 2s ease-in-out infinite;
        }

        /* Product Card Hover */
        .product-card {
            transition: all 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        /* Store Card */
        .store-card {
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        .store-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            transform: rotate(45deg);
            transition: all 0.5s;
        }

        .store-card:hover::before {
            left: 100%;
        }

        .store-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        }

        /* Gradient Text */
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Header */
        .header-fixed {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-white">

    <!-- Simple Header - Logo + Back Button Only -->
    <header class="header-fixed">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <!-- Logo -->
                <div class="flex items-center">
                    <img src="{{ $siteLogo }}" alt="{{ $siteInfo['name'] }}" class="h-12 w-auto">
                </div>

                <!-- Back Button -->
                <a href="javascript:history.back()" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-semibold rounded-full hover:from-purple-700 hover:to-indigo-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    กลับ
                </a>
            </div>
        </div>
    </header>

    <!-- Hero Section with 3D Background - Compact Version -->
    <section class="relative h-[350px] flex items-center justify-center overflow-hidden pt-16">
        <!-- 3D Canvas -->
        <canvas id="hero3d"></canvas>

        <!-- Hero Content -->
        <div class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-center">
            <h1 class="text-2xl md:text-3xl lg:text-4xl font-bold text-white mb-2 drop-shadow-2xl">
                {{ $siteInfo['name'] }}
            </h1>
            <p class="text-base md:text-lg text-white/90 mb-2 font-light">
                {{ $siteInfo['tagline'] }}
            </p>
            <p class="text-sm md:text-base text-white/80 mb-6 max-w-xl mx-auto">
                {{ $siteInfo['description'] }}
            </p>

            <!-- CTA Buttons -->
            <div class="flex flex-wrap gap-2 justify-center items-center">
                <a href="{{ route('user.dashboard') }}" class="px-6 py-2 bg-white text-purple-600 font-semibold text-sm rounded-full hover:bg-purple-50 transition-all duration-300 shadow-lg">
                    เริ่มต้นใช้งาน
                </a>
                <a href="#featured-stores" class="px-6 py-2 bg-transparent border-2 border-white text-white font-semibold text-sm rounded-full hover:bg-white hover:text-purple-600 transition-all duration-300">
                    ดูร้านค้า
                </a>
                <a href="#new-products" class="px-6 py-2 bg-transparent border-2 border-white text-white font-semibold text-sm rounded-full hover:bg-white hover:text-purple-600 transition-all duration-300">
                    สินค้ามาใหม่
                </a>
            </div>
        </div>

        <!-- Scroll Indicator -->
        <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 z-10">
            <a href="#featured-stores" class="block">
                <svg class="w-5 h-5 text-white animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                </svg>
            </a>
        </div>
    </section>

    <!-- Featured Stores Section -->
    <section id="featured-stores" class="py-8 bg-gradient-to-b from-white to-purple-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-6">
                <h2 class="text-2xl md:text-3xl font-bold gradient-text mb-2">
                    ร้านค้าแนะนำ
                </h2>
                <div class="w-16 h-1 bg-gradient-to-r from-purple-600 to-indigo-600 mx-auto rounded-full"></div>
                <p class="text-sm text-gray-600 mt-3">ร้านค้าคุณภาพ ได้รับการรับรองและคัดสรร</p>
            </div>

            @if($featuredStores->count() > 0)
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach($featuredStores as $store)
                        <div class="store-card bg-white rounded-xl shadow-md p-3 flex flex-col items-center">
                            <!-- Store Logo -->
                            <div class="w-20 h-20 rounded-full overflow-hidden mb-2 border-2 border-purple-200 shadow-lg">
                                @if($store->store_logo)
                                    <img src="{{ asset($store->store_logo) }}" alt="{{ $store->store_name }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full bg-gradient-to-br from-purple-400 to-indigo-400 flex items-center justify-center text-white text-4xl font-bold">
                                        {{ substr($store->store_name, 0, 1) }}
                                    </div>
                                @endif
                            </div>

                            <!-- Store Info -->
                            <h3 class="text-sm font-bold text-gray-800 mb-1 text-center line-clamp-1">{{ $store->store_name }}</h3>

                            <!-- Rating -->
                            <div class="flex items-center mb-1">
                                @for($i = 1; $i <= 5; $i++)
                                    @if($i <= $store->rating_average)
                                        <svg class="w-3 h-3 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    @else
                                        <svg class="w-3 h-3 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    @endif
                                @endfor
                                <span class="ml-1 text-xs text-gray-600">({{ number_format($store->rating_average, 1) }})</span>
                            </div>

                            <!-- Stats -->
                            <div class="grid grid-cols-2 gap-2 w-full mb-2">
                                <div class="text-center">
                                    <p class="text-base font-bold text-purple-600">{{ number_format($store->total_products) }}</p>
                                    <p class="text-xs text-gray-500">สินค้า</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-base font-bold text-indigo-600">{{ number_format($store->total_sales) }}</p>
                                    <p class="text-xs text-gray-500">ยอดขาย</p>
                                </div>
                            </div>

                            <!-- Visit Button -->
                            <a href="{{ route('vendor.store.show', $store->store_slug) }}" class="w-full px-3 py-1.5 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-semibold text-xs text-center rounded-full hover:from-purple-700 hover:to-indigo-700 transition-all duration-300">
                                เยี่ยมชมร้านค้า
                            </a>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-20">
                    <div class="text-6xl mb-4">🏪</div>
                    <p class="text-xl text-gray-500">ยังไม่มีร้านค้าแนะนำในขณะนี้</p>
                </div>
            @endif
        </div>
    </section>

    <!-- New Products Section -->
    <section id="new-products" class="py-8 bg-gradient-to-b from-purple-50 to-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-6">
                <h2 class="text-2xl md:text-3xl font-bold gradient-text mb-2">
                    สินค้ามาใหม่
                </h2>
                <div class="w-16 h-1 bg-gradient-to-r from-purple-600 to-indigo-600 mx-auto rounded-full"></div>
                <p class="text-sm text-gray-600 mt-3">สินค้าล่าสุดที่เพิ่งเข้ามาในระบบ</p>
            </div>

            @if($newProducts->count() > 0)
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach($newProducts as $product)
                        <div class="product-card bg-white rounded-lg shadow-md overflow-hidden">
                            <!-- Product Image -->
                            <div class="relative h-40 bg-gray-200 overflow-hidden group">
                                @if($product->main_image_url)
                                    <img src="{{ asset($product->main_image_url) }}" alt="{{ $product->name }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                @else
                                    <div class="w-full h-full bg-gradient-to-br from-purple-300 to-indigo-300 flex items-center justify-center">
                                        <svg class="w-24 h-24 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </div>
                                @endif

                                <!-- New Badge -->
                                <div class="absolute top-4 right-4 bg-red-500 text-white px-3 py-1 rounded-full text-xs font-bold shadow-lg">
                                    ใหม่!
                                </div>

                                @if($product->is_featured)
                                    <div class="absolute top-4 left-4 bg-yellow-500 text-white px-3 py-1 rounded-full text-xs font-bold shadow-lg">
                                        แนะนำ
                                    </div>
                                @endif
                            </div>

                            <!-- Product Info -->
                            <div class="p-3">
                                <h3 class="text-xs font-bold text-gray-800 mb-1 line-clamp-2 h-8">{{ $product->name }}</h3>

                                <!-- Category -->
                                @if($product->category)
                                    <p class="text-xs text-gray-500 mb-1">{{ $product->category->name }}</p>
                                @endif

                                <!-- Price -->
                                <div class="mb-2">
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-base font-bold text-purple-600">฿{{ number_format($product->price, 0) }}</span>
                                        @if($product->compare_at_price && $product->compare_at_price > $product->price)
                                            <span class="text-xs text-gray-400 line-through">฿{{ number_format($product->compare_at_price, 0) }}</span>
                                        @endif
                                    </div>
                                </div>

                                <!-- View Button -->
                                <a href="{{ route('products.show', $product->slug) }}" class="block w-full px-2 py-1.5 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-semibold text-xs text-center rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all duration-300">
                                    ดูสินค้า
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-20">
                    <div class="text-6xl mb-4">📦</div>
                    <p class="text-xl text-gray-500">ยังไม่มีสินค้ามาใหม่ในขณะนี้</p>
                </div>
            @endif
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gradient-to-r from-purple-900 to-indigo-900 text-white py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <img src="{{ $siteLogo }}" alt="{{ $siteInfo['name'] }}" class="h-12 w-auto mx-auto mb-4 filter brightness-0 invert">
            <p class="text-base mb-2">{{ $siteInfo['name'] }}</p>
            <p class="text-sm text-purple-200">{{ $siteInfo['tagline'] }}</p>
            <div class="mt-4 text-xs text-purple-300">
                &copy; {{ date('Y') }} {{ $siteInfo['name'] }}. All rights reserved.
            </div>
        </div>
    </footer>

    <!-- 3D Hero Animation Script -->
    <script>
        // Three.js 3D Background Animation
        const canvas = document.getElementById('hero3d');
        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
        const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });

        renderer.setSize(window.innerWidth, window.innerHeight);
        renderer.setPixelRatio(window.devicePixelRatio);

        // Create particles
        const particlesGeometry = new THREE.BufferGeometry();
        const particlesCount = 1000;
        const posArray = new Float32Array(particlesCount * 3);

        for(let i = 0; i < particlesCount * 3; i++) {
            posArray[i] = (Math.random() - 0.5) * 100;
        }

        particlesGeometry.setAttribute('position', new THREE.BufferAttribute(posArray, 3));

        const particlesMaterial = new THREE.PointsMaterial({
            size: 0.15,
            color: 0xffffff,
            transparent: true,
            opacity: 0.8,
            blending: THREE.AdditiveBlending
        });

        const particlesMesh = new THREE.Points(particlesGeometry, particlesMaterial);
        scene.add(particlesMesh);

        // Create geometric shapes
        const geometry = new THREE.TorusKnotGeometry(10, 3, 100, 16);
        const material = new THREE.MeshBasicMaterial({
            color: 0x667eea,
            wireframe: true,
            transparent: true,
            opacity: 0.3
        });
        const torusKnot = new THREE.Mesh(geometry, material);
        scene.add(torusKnot);

        camera.position.z = 30;

        // Mouse movement effect
        let mouseX = 0;
        let mouseY = 0;

        document.addEventListener('mousemove', (event) => {
            mouseX = (event.clientX / window.innerWidth) * 2 - 1;
            mouseY = -(event.clientY / window.innerHeight) * 2 + 1;
        });

        // Animation loop
        function animate() {
            requestAnimationFrame(animate);

            // Rotate particles
            particlesMesh.rotation.y += 0.001;
            particlesMesh.rotation.x += 0.0005;

            // Rotate torus knot
            torusKnot.rotation.x += 0.01;
            torusKnot.rotation.y += 0.01;

            // Mouse interaction
            camera.position.x += (mouseX * 5 - camera.position.x) * 0.05;
            camera.position.y += (mouseY * 5 - camera.position.y) * 0.05;
            camera.lookAt(scene.position);

            renderer.render(scene, camera);
        }

        animate();

        // Handle window resize
        window.addEventListener('resize', () => {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        });
    </script>
</body>
</html>
