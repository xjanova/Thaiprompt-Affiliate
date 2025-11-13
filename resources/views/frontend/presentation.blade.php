<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3D Interactive Presentation - Thaiprompt Affiliate Platform</title>
    <meta name="description" content="สำรวจระบบแพลตฟอร์มด้วยประสบการณ์ 3D แบบโต้ตอบ">

    <!-- Three.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Kanit', 'IBM Plex Sans Thai', sans-serif;
            overflow: hidden;
            background: #000;
            color: #fff;
        }

        /* 3D Canvas */
        #canvas-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            z-index: 1;
        }

        /* UI Overlay */
        .ui-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10;
            pointer-events: none;
        }

        .ui-overlay > * {
            pointer-events: auto;
        }

        /* Header */
        .header {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            padding: 2rem;
            background: linear-gradient(180deg, rgba(0,0,0,0.8) 0%, transparent 100%);
            z-index: 100;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #3B82F6 0%, #8B5CF6 50%, #EC4899 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .back-btn {
            position: absolute;
            top: 2rem;
            right: 2rem;
            padding: 0.75rem 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        /* Instructions */
        .instructions {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            text-align: center;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(10px);
            padding: 1.5rem 3rem;
            border-radius: 20px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            animation: fadeInUp 1s ease;
        }

        .instructions h2 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #3B82F6, #8B5CF6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .instructions p {
            font-size: 1rem;
            opacity: 0.8;
        }

        /* System Info Tooltip */
        .system-tooltip {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(20px);
            padding: 2rem;
            border-radius: 20px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            text-align: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
            max-width: 400px;
        }

        .system-tooltip.active {
            opacity: 1;
        }

        .system-tooltip .icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .system-tooltip h3 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .system-tooltip p {
            opacity: 0.8;
            margin-bottom: 1.5rem;
        }

        .system-tooltip .action {
            font-size: 0.9rem;
            opacity: 0.6;
            font-style: italic;
        }

        /* Slide View (Fullscreen Presentation) */
        .slide-view {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #000;
            z-index: 1000;
            display: none;
            opacity: 0;
            transition: opacity 0.5s ease;
        }

        .slide-view.active {
            display: block;
            opacity: 1;
        }

        .slide-container {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4rem;
        }

        .slide {
            max-width: 1200px;
            width: 100%;
            display: none;
        }

        .slide.active {
            display: block;
            animation: slideIn 0.5s ease;
        }

        .slide-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .slide-header .icon {
            font-size: 5rem;
            margin-bottom: 1rem;
            animation: float 3s ease-in-out infinite;
        }

        .slide-header h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #3B82F6, #8B5CF6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .slide-header p {
            font-size: 1.5rem;
            opacity: 0.7;
        }

        .slide-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 20px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            border-color: rgba(59, 130, 246, 0.5);
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.3);
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #3B82F6;
        }

        .feature-card ul {
            list-style: none;
        }

        .feature-card ul li {
            padding: 0.5rem 0;
            padding-left: 1.5rem;
            position: relative;
        }

        .feature-card ul li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #10B981;
            font-weight: bold;
        }

        /* Navigation Controls */
        .slide-nav {
            position: fixed;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .nav-btn {
            padding: 1rem 2rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .nav-btn:hover:not(:disabled) {
            background: rgba(59, 130, 246, 0.3);
            border-color: #3B82F6;
            transform: translateY(-2px);
        }

        .nav-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        .slide-indicator {
            padding: 0.75rem 1.5rem;
            background: rgba(0, 0, 0, 0.6);
            border-radius: 50px;
            font-weight: 600;
        }

        .close-slide-btn {
            position: fixed;
            top: 2rem;
            right: 2rem;
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            color: #fff;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .close-slide-btn:hover {
            background: rgba(239, 68, 68, 0.3);
            border-color: #EF4444;
            transform: rotate(90deg);
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-20px);
            }
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        /* Loading Screen */
        .loading-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            flex-direction: column;
            gap: 2rem;
        }

        .loading-screen.hidden {
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.5s ease;
        }

        .loader {
            width: 60px;
            height: 60px;
            border: 5px solid rgba(59, 130, 246, 0.2);
            border-top-color: #3B82F6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .loading-text {
            font-size: 1.5rem;
            opacity: 0.8;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .instructions {
                padding: 1rem 2rem;
                bottom: 1rem;
            }

            .instructions h2 {
                font-size: 1.2rem;
            }

            .instructions p {
                font-size: 0.9rem;
            }

            .slide-container {
                padding: 2rem 1rem;
            }

            .slide-header h1 {
                font-size: 2rem;
            }

            .slide-header p {
                font-size: 1.2rem;
            }

            .slide-content {
                grid-template-columns: 1fr;
            }

            .nav-btn {
                padding: 0.75rem 1.5rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <!-- Loading Screen -->
    <div class="loading-screen" id="loadingScreen">
        <div class="loader"></div>
        <div class="loading-text">กำลังโหลด 3D Experience...</div>
    </div>

    <!-- 3D Canvas -->
    <div id="canvas-container"></div>

    <!-- UI Overlay -->
    <div class="ui-overlay">
        <!-- Header -->
        <div class="header">
            <div class="logo">🚀 Thaiprompt Affiliate Platform</div>
            <a href="{{ route('home') }}" class="back-btn">
                <span>←</span>
                <span>กลับหน้าแรก</span>
            </a>
        </div>

        <!-- Instructions -->
        <div class="instructions" id="instructions">
            <h2>🎮 วิธีการใช้งาน</h2>
            <p>🖱️ ลากเมาส์เพื่อหมุนกล้อง | 🔍 Scroll เพื่อ Zoom | 🎯 คลิกทรงกลมเพื่อดูรายละเอียด</p>
        </div>

        <!-- System Tooltip -->
        <div class="system-tooltip" id="systemTooltip">
            <div class="icon" id="tooltipIcon"></div>
            <h3 id="tooltipName"></h3>
            <p id="tooltipDesc"></p>
            <div class="action">คลิกเพื่อเข้าสู่ระบบ →</div>
        </div>
    </div>

    <!-- Slide View (Fullscreen Presentation) -->
    <div class="slide-view" id="slideView">
        <button class="close-slide-btn" onclick="closeSlideView()">✕</button>

        <div class="slide-container" id="slideContainer">
            <!-- Slides will be dynamically inserted here -->
        </div>

        <div class="slide-nav">
            <button class="nav-btn" id="prevBtn" onclick="previousSlide()">← ก่อนหน้า</button>
            <div class="slide-indicator">
                <span id="currentSlide">1</span> / <span id="totalSlides">1</span>
            </div>
            <button class="nav-btn" id="nextBtn" onclick="nextSlide()">ถัดไป →</button>
        </div>
    </div>

    <!-- System Data (from Laravel) -->
    <script>
        const SYSTEMS_DATA = @json($systems);
    </script>

    <!-- Main Script -->
    <script>
        // Global variables
        let scene, camera, renderer, raycaster, mouse;
        let spheres = [];
        let particles = [];
        let currentSystem = null;
        let currentSlideIndex = 0;
        let isAnimating = false;

        // Initialize
        function init() {
            // Scene setup
            scene = new THREE.Scene();
            scene.fog = new THREE.FogExp2(0x000000, 0.0003);

            // Camera
            camera = new THREE.PerspectiveCamera(
                75,
                window.innerWidth / window.innerHeight,
                0.1,
                10000
            );
            camera.position.z = 100;

            // Renderer
            renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setPixelRatio(window.devicePixelRatio);
            document.getElementById('canvas-container').appendChild(renderer.domElement);

            // Raycaster for mouse interaction
            raycaster = new THREE.Raycaster();
            mouse = new THREE.Vector2();

            // Create systems (spheres)
            createSystems();

            // Create particle field
            createParticleField();

            // Event listeners
            window.addEventListener('resize', onWindowResize);
            window.addEventListener('mousemove', onMouseMove);
            window.addEventListener('click', onMouseClick);
            document.addEventListener('keydown', onKeyDown);

            // Start animation
            animate();

            // Hide loading screen
            setTimeout(() => {
                document.getElementById('loadingScreen').classList.add('hidden');
            }, 1500);
        }

        // Create 3D Spheres for each system
        function createSystems() {
            const radius = 80;
            const count = SYSTEMS_DATA.length;

            SYSTEMS_DATA.forEach((system, index) => {
                // Calculate position in circle
                const angle = (index / count) * Math.PI * 2;
                const x = Math.cos(angle) * radius;
                const z = Math.sin(angle) * radius;
                const y = (Math.random() - 0.5) * 30;

                // Create sphere geometry
                const geometry = new THREE.SphereGeometry(8, 32, 32);

                // Create material with system color
                const material = new THREE.MeshPhongMaterial({
                    color: new THREE.Color(system.color),
                    emissive: new THREE.Color(system.color),
                    emissiveIntensity: 0.3,
                    shininess: 100,
                    transparent: true,
                    opacity: 0.9
                });

                const sphere = new THREE.Mesh(geometry, material);
                sphere.position.set(x, y, z);
                sphere.userData = system;

                // Add glow effect
                const glowGeometry = new THREE.SphereGeometry(9, 32, 32);
                const glowMaterial = new THREE.MeshBasicMaterial({
                    color: new THREE.Color(system.color),
                    transparent: true,
                    opacity: 0.2
                });
                const glow = new THREE.Mesh(glowGeometry, glowMaterial);
                sphere.add(glow);

                scene.add(sphere);
                spheres.push(sphere);
            });

            // Add lights
            const ambientLight = new THREE.AmbientLight(0xffffff, 0.3);
            scene.add(ambientLight);

            const pointLight = new THREE.PointLight(0xffffff, 1, 300);
            pointLight.position.set(0, 0, 100);
            scene.add(pointLight);
        }

        // Create particle field
        function createParticleField() {
            const particleCount = 1000;
            const geometry = new THREE.BufferGeometry();
            const positions = [];
            const colors = [];

            for (let i = 0; i < particleCount; i++) {
                positions.push(
                    (Math.random() - 0.5) * 400,
                    (Math.random() - 0.5) * 400,
                    (Math.random() - 0.5) * 400
                );

                const color = new THREE.Color();
                color.setHSL(Math.random(), 0.7, 0.5);
                colors.push(color.r, color.g, color.b);
            }

            geometry.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));
            geometry.setAttribute('color', new THREE.Float32BufferAttribute(colors, 3));

            const material = new THREE.PointsMaterial({
                size: 1.5,
                vertexColors: true,
                transparent: true,
                opacity: 0.6
            });

            const particleSystem = new THREE.Points(geometry, material);
            scene.add(particleSystem);
            particles.push(particleSystem);
        }

        // Animation loop
        function animate() {
            requestAnimationFrame(animate);

            // Rotate spheres
            spheres.forEach((sphere, index) => {
                sphere.rotation.y += 0.005;
                sphere.rotation.x += 0.002;

                // Float animation
                sphere.position.y += Math.sin(Date.now() * 0.001 + index) * 0.01;
            });

            // Rotate particle field
            particles.forEach(particle => {
                particle.rotation.y += 0.0002;
                particle.rotation.x += 0.0001;
            });

            // Auto rotate camera slowly
            if (!isAnimating) {
                camera.position.x += Math.sin(Date.now() * 0.0001) * 0.05;
                camera.position.y += Math.cos(Date.now() * 0.0001) * 0.03;
                camera.lookAt(scene.position);
            }

            renderer.render(scene, camera);
        }

        // Mouse move handler
        function onMouseMove(event) {
            mouse.x = (event.clientX / window.innerWidth) * 2 - 1;
            mouse.y = -(event.clientY / window.innerHeight) * 2 + 1;

            // Raycast to detect hover
            raycaster.setFromCamera(mouse, camera);
            const intersects = raycaster.intersectObjects(spheres);

            // Reset all spheres
            spheres.forEach(sphere => {
                sphere.scale.set(1, 1, 1);
                sphere.material.emissiveIntensity = 0.3;
            });

            // Highlight hovered sphere
            if (intersects.length > 0) {
                const sphere = intersects[0].object;
                sphere.scale.set(1.2, 1.2, 1.2);
                sphere.material.emissiveIntensity = 0.6;

                // Show tooltip
                showTooltip(sphere.userData);
                document.body.style.cursor = 'pointer';
            } else {
                hideTooltip();
                document.body.style.cursor = 'default';
            }
        }

        // Mouse click handler
        function onMouseClick(event) {
            raycaster.setFromCamera(mouse, camera);
            const intersects = raycaster.intersectObjects(spheres);

            if (intersects.length > 0) {
                const sphere = intersects[0].object;
                openSystem(sphere.userData);
            }
        }

        // Show tooltip
        function showTooltip(system) {
            const tooltip = document.getElementById('systemTooltip');
            document.getElementById('tooltipIcon').textContent = system.icon;
            document.getElementById('tooltipName').textContent = system.name;
            document.getElementById('tooltipDesc').textContent = system.description;
            tooltip.classList.add('active');
        }

        // Hide tooltip
        function hideTooltip() {
            document.getElementById('systemTooltip').classList.remove('active');
        }

        // Open system presentation
        function openSystem(system) {
            currentSystem = system;
            currentSlideIndex = 0;

            // Generate slides for this system
            generateSlides(system);

            // Show slide view
            document.getElementById('slideView').classList.add('active');
            document.getElementById('instructions').style.display = 'none';

            // Show first slide
            showSlide(0);
        }

        // Generate slides based on system
        function generateSlides(system) {
            const container = document.getElementById('slideContainer');
            container.innerHTML = '';

            // Slide 1: Overview
            const slide1 = createSlide(
                system.icon,
                system.name,
                system.description,
                getSystemOverview(system.id)
            );
            container.appendChild(slide1);

            // Slide 2: Features
            const slide2 = createSlide(
                '⭐',
                'ฟีเจอร์หลัก',
                `คุณสมบัติสำคัญของ ${system.name}`,
                getSystemFeatures(system.id)
            );
            container.appendChild(slide2);

            // Slide 3: Technology
            const slide3 = createSlide(
                '🔧',
                'เทคโนโลยี',
                `สแต็คเทคโนโลยีที่ใช้`,
                getSystemTechnology(system.id)
            );
            container.appendChild(slide3);

            // Update slide count
            document.getElementById('totalSlides').textContent = container.children.length;
        }

        // Create slide element
        function createSlide(icon, title, subtitle, content) {
            const slide = document.createElement('div');
            slide.className = 'slide';
            slide.innerHTML = `
                <div class="slide-header">
                    <div class="icon">${icon}</div>
                    <h1>${title}</h1>
                    <p>${subtitle}</p>
                </div>
                <div class="slide-content">
                    ${content}
                </div>
            `;
            return slide;
        }

        // Show specific slide
        function showSlide(index) {
            const slides = document.querySelectorAll('.slide');
            slides.forEach((slide, i) => {
                slide.classList.toggle('active', i === index);
            });

            currentSlideIndex = index;
            document.getElementById('currentSlide').textContent = index + 1;

            // Update button states
            document.getElementById('prevBtn').disabled = index === 0;
            document.getElementById('nextBtn').disabled = index === slides.length - 1;
        }

        // Navigation functions
        function nextSlide() {
            const slides = document.querySelectorAll('.slide');
            if (currentSlideIndex < slides.length - 1) {
                showSlide(currentSlideIndex + 1);
            }
        }

        function previousSlide() {
            if (currentSlideIndex > 0) {
                showSlide(currentSlideIndex - 1);
            }
        }

        function closeSlideView() {
            document.getElementById('slideView').classList.remove('active');
            document.getElementById('instructions').style.display = 'block';
            currentSystem = null;
        }

        // Keyboard navigation
        function onKeyDown(event) {
            if (document.getElementById('slideView').classList.contains('active')) {
                if (event.key === 'ArrowRight') nextSlide();
                if (event.key === 'ArrowLeft') previousSlide();
                if (event.key === 'Escape') closeSlideView();
            }
        }

        // Window resize handler
        function onWindowResize() {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        }

        // System content generators
        function getSystemOverview(systemId) {
            const overviews = {
                'blockchain': `
                    <div class="feature-card">
                        <h3>🏗️ Native Blockchain</h3>
                        <ul>
                            <li>Chain ID: 7000</li>
                            <li>Block Time: 2 วินาที</li>
                            <li>Total Supply: 7B TPIX</li>
                            <li>Consensus: IBFT (Proof of Stake)</li>
                        </ul>
                    </div>
                    <div class="feature-card">
                        <h3>💎 TPIX Token</h3>
                        <ul>
                            <li>เหรียญหลักของระบบ</li>
                            <li>EVM Compatible</li>
                            <li>Smart Contract Support</li>
                            <li>Block Explorer</li>
                        </ul>
                    </div>
                    <div class="feature-card">
                        <h3>🏭 Token Factory</h3>
                        <ul>
                            <li>สร้าง ERC20 Token</li>
                            <li>Deploy บน Blockchain</li>
                            <li>ระบบ Referral</li>
                            <li>Trading & Exchange</li>
                        </ul>
                    </div>
                `,
                'foodpassport': `
                    <div class="feature-card">
                        <h3>🌾 Farm to Fork</h3>
                        <ul>
                            <li>ติดตามที่มาอาหาร</li>
                            <li>Quality Control</li>
                            <li>QR Code Scanning</li>
                            <li>Blockchain Verified</li>
                        </ul>
                    </div>
                    <div class="feature-card">
                        <h3>🌍 Carbon Credit</h3>
                        <ul>
                            <li>คำนวณ Carbon Footprint</li>
                            <li>ออก Carbon Credit</li>
                            <li>ซื้อขายได้</li>
                            <li>รางวัลสำหรับเกษตรกร</li>
                        </ul>
                    </div>
                    <div class="feature-card">
                        <h3>✅ Certification</h3>
                        <ul>
                            <li>มาตรฐาน ISO 22000</li>
                            <li>HACCP, GMP, GAP</li>
                            <li>Digital Certificates</li>
                            <li>NFT Badges</li>
                        </ul>
                    </div>
                `,
                'games': `
                    <div class="feature-card">
                        <h3>🎯 Tetris Classic</h3>
                        <ul>
                            <li>เกม Tetris แบบคลาสสิก</li>
                            <li>Top Score Leaderboard</li>
                            <li>Multiplayer Mode</li>
                            <li>Reward System</li>
                        </ul>
                    </div>
                    <div class="feature-card">
                        <h3>🚀 Space Shooter</h3>
                        <ul>
                            <li>3D Space Combat</li>
                            <li>Multiple Levels</li>
                            <li>Power-ups & Weapons</li>
                            <li>Boss Battles</li>
                        </ul>
                    </div>
                    <div class="feature-card">
                        <h3>🎴 Tarot 3D</h3>
                        <ul>
                            <li>3D WebGL Rendering</li>
                            <li>Interactive Card System</li>
                            <li>Beautiful Animations</li>
                            <li>Fortune Telling AI</li>
                        </ul>
                    </div>
                `,
                'mlm': `
                    <div class="feature-card">
                        <h3>📊 Unilevel Plan</h3>
                        <ul>
                            <li>Unlimited Width</li>
                            <li>Multiple Level Commission</li>
                            <li>Spillover System</li>
                            <li>Auto-placement</li>
                        </ul>
                    </div>
                    <div class="feature-card">
                        <h3>🌳 Binary Plan</h3>
                        <ul>
                            <li>Left & Right Leg</li>
                            <li>Matching Bonus</li>
                            <li>Balanced Genealogy</li>
                            <li>Real-time Calculations</li>
                        </ul>
                    </div>
                    <div class="feature-card">
                        <h3>💰 Commission Engine</h3>
                        <ul>
                            <li>Auto Commission Calculation</li>
                            <li>Multiple Bonus Types</li>
                            <li>Rank Advancement</li>
                            <li>Instant Payout</li>
                        </ul>
                    </div>
                `,
                'ecommerce': `
                    <div class="feature-card">
                        <h3>🏪 Multi-Vendor</h3>
                        <ul>
                            <li>หลายผู้ขายในแพลตฟอร์มเดียว</li>
                            <li>Vendor Dashboard</li>
                            <li>Product Management</li>
                            <li>Commission System</li>
                        </ul>
                    </div>
                    <div class="feature-card">
                        <h3>💳 Payment Gateway</h3>
                        <ul>
                            <li>PromptPay QR Code</li>
                            <li>Credit/Debit Card</li>
                            <li>Cryptocurrency</li>
                            <li>Installment Plans</li>
                        </ul>
                    </div>
                    <div class="feature-card">
                        <h3>📦 Order Management</h3>
                        <ul>
                            <li>Order Tracking</li>
                            <li>Shipping Integration</li>
                            <li>Return & Refund</li>
                            <li>Customer Reviews</li>
                        </ul>
                    </div>
                `,
                'wallet': `
                    <div class="feature-card">
                        <h3>💵 THB Wallet</h3>
                        <ul>
                            <li>กระเป๋าเงินบาทไทย</li>
                            <li>Deposit & Withdraw</li>
                            <li>Bank Transfer</li>
                            <li>Instant Settlement</li>
                        </ul>
                    </div>
                    <div class="feature-card">
                        <h3>🪙 Crypto Wallet</h3>
                        <ul>
                            <li>20+ Cryptocurrencies</li>
                            <li>TPIX, BTC, ETH, USDT</li>
                            <li>Swap & Exchange</li>
                            <li>Secure Storage</li>
                        </ul>
                    </div>
                    <div class="feature-card">
                        <h3>💸 Withdrawal System</h3>
                        <ul>
                            <li>Fast Withdrawal</li>
                            <li>Multiple Methods</li>
                            <li>Low Fees</li>
                            <li>24/7 Processing</li>
                        </ul>
                    </div>
                `,
                'ai': `
                    <div class="feature-card">
                        <h3>🧠 OpenAI GPT-4</h3>
                        <ul>
                            <li>Natural Language Processing</li>
                            <li>Content Generation</li>
                            <li>Intelligent Responses</li>
                            <li>Multi-language Support</li>
                        </ul>
                    </div>
                    <div class="feature-card">
                        <h3>💬 Claude AI</h3>
                        <ul>
                            <li>Advanced Reasoning</li>
                            <li>Context Understanding</li>
                            <li>Long Conversations</li>
                            <li>Code Generation</li>
                        </ul>
                    </div>
                    <div class="feature-card">
                        <h3>📱 LINE Bot</h3>
                        <ul>
                            <li>AI-Powered Chatbot</li>
                            <li>24/7 Customer Support</li>
                            <li>Auto-Reply System</li>
                            <li>Rich Menu Integration</li>
                        </ul>
                    </div>
                `,
                'investment': `
                    <div class="feature-card">
                        <h3>📈 Staking Pools</h3>
                        <ul>
                            <li>Multiple APY Options</li>
                            <li>Flexible & Locked Staking</li>
                            <li>Auto-compound</li>
                            <li>Daily Rewards</li>
                        </ul>
                    </div>
                    <div class="feature-card">
                        <h3>💎 Yield Farming</h3>
                        <ul>
                            <li>Liquidity Providing</li>
                            <li>High APR Returns</li>
                            <li>LP Token Rewards</li>
                            <li>Impermanent Loss Protection</li>
                        </ul>
                    </div>
                    <div class="feature-card">
                        <h3>🏆 ROI System</h3>
                        <ul>
                            <li>Guaranteed Returns</li>
                            <li>Multiple Investment Plans</li>
                            <li>Risk Management</li>
                            <li>Performance Dashboard</li>
                        </ul>
                    </div>
                `,
                'hotel': `
                    <div class="feature-card">
                        <h3>🏨 Booking System</h3>
                        <ul>
                            <li>Real-time Availability</li>
                            <li>Instant Confirmation</li>
                            <li>Multiple Room Types</li>
                            <li>Special Packages</li>
                        </ul>
                    </div>
                    <div class="feature-card">
                        <h3>🗺️ Property Management</h3>
                        <ul>
                            <li>Hotel Dashboard</li>
                            <li>Inventory Control</li>
                            <li>Revenue Management</li>
                            <li>Analytics & Reports</li>
                        </ul>
                    </div>
                    <div class="feature-card">
                        <h3>⭐ Guest Experience</h3>
                        <ul>
                            <li>Online Check-in</li>
                            <li>Digital Room Keys</li>
                            <li>Concierge Services</li>
                            <li>Review System</li>
                        </ul>
                    </div>
                `,
                'academy': `
                    <div class="feature-card">
                        <h3>📚 Course Management</h3>
                        <ul>
                            <li>Video Lessons</li>
                            <li>Interactive Quizzes</li>
                            <li>Progress Tracking</li>
                            <li>Certificates</li>
                        </ul>
                    </div>
                    <div class="feature-card">
                        <h3>👨‍🏫 Instructor Tools</h3>
                        <ul>
                            <li>Course Builder</li>
                            <li>Student Analytics</li>
                            <li>Live Classes</li>
                            <li>Revenue Sharing</li>
                        </ul>
                    </div>
                    <div class="feature-card">
                        <h3>🎓 Learning Experience</h3>
                        <ul>
                            <li>Personalized Learning Path</li>
                            <li>Discussion Forums</li>
                            <li>Peer Learning</li>
                            <li>Gamification</li>
                        </ul>
                    </div>
                `,
                'hrm': `
                    <div class="feature-card">
                        <h3>👥 Employee Management</h3>
                        <ul>
                            <li>Employee Database</li>
                            <li>Attendance Tracking</li>
                            <li>Leave Management</li>
                            <li>Performance Reviews</li>
                        </ul>
                    </div>
                    <div class="feature-card">
                        <h3>💰 Payroll System</h3>
                        <ul>
                            <li>Salary Calculation</li>
                            <li>Tax Deductions</li>
                            <li>Payslip Generation</li>
                            <li>Direct Deposit</li>
                        </ul>
                    </div>
                    <div class="feature-card">
                        <h3>📊 Reports & Analytics</h3>
                        <ul>
                            <li>HR Metrics Dashboard</li>
                            <li>Turnover Analysis</li>
                            <li>Cost Reports</li>
                            <li>Compliance Tracking</li>
                        </ul>
                    </div>
                `,
                'platform': `
                    <div class="feature-card">
                        <h3>🌐 Complete Ecosystem</h3>
                        <ul>
                            <li>12+ Integrated Systems</li>
                            <li>Single Sign-On (SSO)</li>
                            <li>Unified Dashboard</li>
                            <li>Cross-platform Features</li>
                        </ul>
                    </div>
                    <div class="feature-card">
                        <h3>🔐 Security & Performance</h3>
                        <ul>
                            <li>2FA Authentication</li>
                            <li>End-to-end Encryption</li>
                            <li>DDoS Protection</li>
                            <li>99.9% Uptime SLA</li>
                        </ul>
                    </div>
                    <div class="feature-card">
                        <h3>🚀 Scalability</h3>
                        <ul>
                            <li>Microservices Architecture</li>
                            <li>Cloud Infrastructure</li>
                            <li>Auto-scaling</li>
                            <li>Global CDN</li>
                        </ul>
                    </div>
                `
            };

            return overviews[systemId] || overviews['platform'];
        }

        function getSystemFeatures(systemId) {
            // Similar structure for features...
            return getSystemOverview(systemId); // Simplified for demo
        }

        function getSystemTechnology(systemId) {
            const tech = {
                'blockchain': `
                    <div class="feature-card">
                        <h3>⚙️ Core Technology</h3>
                        <ul>
                            <li>Polygon Edge</li>
                            <li>Go Language</li>
                            <li>IBFT Consensus</li>
                            <li>EVM Compatible</li>
                        </ul>
                    </div>
                    <div class="feature-card">
                        <h3>🔧 Development Stack</h3>
                        <ul>
                            <li>Solidity ^0.8.20</li>
                            <li>ethers.js / Web3.js</li>
                            <li>Hardhat / Truffle</li>
                            <li>Blockscout Explorer</li>
                        </ul>
                    </div>
                    <div class="feature-card">
                        <h3>📊 Infrastructure</h3>
                        <ul>
                            <li>Docker & Kubernetes</li>
                            <li>PostgreSQL</li>
                            <li>Prometheus & Grafana</li>
                            <li>Load Balancing</li>
                        </ul>
                    </div>
                `,
                'foodpassport': `
                    <div class="feature-card">
                        <h3>⚙️ Backend</h3>
                        <ul>
                            <li>Laravel 11</li>
                            <li>PHP 8.2+</li>
                            <li>MySQL 8.0</li>
                            <li>Redis Cache</li>
                        </ul>
                    </div>
                    <div class="feature-card">
                        <h3>🔗 Blockchain Integration</h3>
                        <ul>
                            <li>Ethereum / BSC</li>
                            <li>IPFS for Documents</li>
                            <li>Smart Contracts</li>
                            <li>NFT Certificates</li>
                        </ul>
                    </div>
                    <div class="feature-card">
                        <h3>📱 Mobile & IoT</h3>
                        <ul>
                            <li>QR Code Scanner</li>
                            <li>GPS Tracking</li>
                            <li>Temperature Sensors</li>
                            <li>Google Maps API</li>
                        </ul>
                    </div>
                `
            };

            return tech[systemId] || `
                <div class="feature-card">
                    <h3>⚙️ Technology Stack</h3>
                    <ul>
                        <li>Laravel 11 Framework</li>
                        <li>Vue.js 3 / React</li>
                        <li>MySQL / PostgreSQL</li>
                        <li>Redis Cache</li>
                        <li>Docker & CI/CD</li>
                    </ul>
                </div>
                <div class="feature-card">
                    <h3>🔐 Security</h3>
                    <ul>
                        <li>SSL/TLS Encryption</li>
                        <li>JWT Authentication</li>
                        <li>Rate Limiting</li>
                        <li>XSS/CSRF Protection</li>
                        <li>Regular Security Audits</li>
                    </ul>
                </div>
                <div class="feature-card">
                    <h3>☁️ Cloud & DevOps</h3>
                    <ul>
                        <li>AWS / Google Cloud</li>
                        <li>Kubernetes Orchestration</li>
                        <li>Auto-scaling</li>
                        <li>CDN (Cloudflare)</li>
                        <li>Monitoring & Logging</li>
                    </ul>
                </div>
            `;
        }

        // Initialize when page loads
        window.addEventListener('DOMContentLoaded', init);
    </script>
</body>
</html>
