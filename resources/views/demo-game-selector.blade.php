<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎮 Game Center - Thai Prompt 3D Gallery</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Noto+Sans+Thai:wght@400;600;700;900&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            overflow: hidden;
            background: #000;
            font-family: 'Orbitron', 'Noto Sans Thai', sans-serif;
            color: #fff;
        }

        #scene-container {
            width: 100vw;
            height: 100vh;
            position: relative;
        }

        #game-canvas {
            display: block;
            width: 100%;
            height: 100%;
        }

        /* Header */
        .header {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            padding: 30px;
            z-index: 10;
            text-align: center;
            background: linear-gradient(180deg, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0) 100%);
            pointer-events: none;
        }

        .header h1 {
            font-size: 3.5rem;
            font-weight: 900;
            background: linear-gradient(135deg, #00ffff, #ff00ff, #ffff00);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 0 30px rgba(0, 255, 255, 0.5);
            animation: glow 2s ease-in-out infinite alternate;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.2rem;
            color: #00ffff;
            text-shadow: 0 0 10px rgba(0, 255, 255, 0.8);
            opacity: 0.9;
        }

        @keyframes glow {
            from {
                filter: drop-shadow(0 0 10px rgba(0, 255, 255, 0.5));
            }
            to {
                filter: drop-shadow(0 0 30px rgba(255, 0, 255, 0.8));
            }
        }

        /* Game Cards Overlay */
        .game-cards {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            display: flex;
            gap: 50px;
            z-index: 5;
            pointer-events: none;
            flex-wrap: wrap;
            justify-content: center;
            max-width: 90%;
            padding: 20px;
        }

        .game-card {
            width: 350px;
            height: 450px;
            position: relative;
            cursor: pointer;
            pointer-events: auto;
            transform-style: preserve-3d;
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            animation: cardFloat 3s ease-in-out infinite;
        }

        .game-card:nth-child(1) {
            animation-delay: 0s;
        }

        .game-card:nth-child(2) {
            animation-delay: 0.5s;
        }

        .game-card:nth-child(3) {
            animation-delay: 1s;
        }

        @keyframes cardFloat {
            0%, 100% {
                transform: translateY(0px) rotateY(0deg);
            }
            50% {
                transform: translateY(-20px) rotateY(5deg);
            }
        }

        .game-card:hover {
            transform: scale(1.15) translateY(-30px) rotateX(5deg) !important;
            z-index: 10;
        }

        .game-card:hover .card-inner {
            box-shadow: 0 30px 80px rgba(0, 255, 255, 0.6),
                        0 0 100px rgba(255, 0, 255, 0.4),
                        inset 0 0 50px rgba(255, 255, 255, 0.1);
        }

        .card-inner {
            width: 100%;
            height: 100%;
            background: rgba(10, 10, 30, 0.85);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            border: 2px solid rgba(0, 255, 255, 0.4);
            padding: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.8),
                        0 0 50px rgba(0, 255, 255, 0.3);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        .card-inner::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                45deg,
                transparent,
                rgba(0, 255, 255, 0.1),
                transparent
            );
            transform: rotate(45deg);
            transition: all 0.6s ease;
        }

        .game-card:hover .card-inner::before {
            left: 100%;
        }

        .game-icon {
            font-size: 6rem;
            margin-bottom: 20px;
            filter: drop-shadow(0 0 20px currentColor);
            animation: iconPulse 2s ease-in-out infinite;
        }

        @keyframes iconPulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }

        .game-card:nth-child(1) .game-icon {
            color: #00ffff;
        }

        .game-card:nth-child(2) .game-icon {
            color: #ff00ff;
        }

        .game-card:nth-child(3) .game-icon {
            color: #ffff00;
        }

        .game-title {
            font-size: 2rem;
            font-weight: 900;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .game-card:nth-child(1) .game-title {
            color: #00ffff;
            text-shadow: 0 0 20px rgba(0, 255, 255, 0.8);
        }

        .game-card:nth-child(2) .game-title {
            color: #ff00ff;
            text-shadow: 0 0 20px rgba(255, 0, 255, 0.8);
        }

        .game-card:nth-child(3) .game-title {
            color: #ffff00;
            text-shadow: 0 0 20px rgba(255, 255, 0, 0.8);
        }

        .game-description {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 25px;
            line-height: 1.6;
            color: #ccc;
        }

        .play-button {
            background: linear-gradient(135deg, #00ffff, #0080ff);
            border: none;
            padding: 15px 40px;
            border-radius: 50px;
            font-size: 1.2rem;
            font-weight: 900;
            color: #000;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 2px;
            box-shadow: 0 10px 30px rgba(0, 255, 255, 0.4);
            transition: all 0.3s ease;
            font-family: 'Orbitron', sans-serif;
        }

        .play-button:hover {
            transform: scale(1.1);
            box-shadow: 0 15px 50px rgba(0, 255, 255, 0.7);
        }

        .game-card:nth-child(2) .play-button {
            background: linear-gradient(135deg, #ff00ff, #ff0080);
        }

        .game-card:nth-child(3) .play-button {
            background: linear-gradient(135deg, #ffff00, #ff8800);
        }

        /* Loading Screen */
        #loading {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #000;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            transition: opacity 0.5s ease;
        }

        #loading.fade-out {
            opacity: 0;
            pointer-events: none;
        }

        .spinner {
            width: 80px;
            height: 80px;
            border: 6px solid rgba(0, 255, 255, 0.2);
            border-top-color: #00ffff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        #loading p {
            color: #00ffff;
            font-size: 1.5rem;
            margin-top: 30px;
            animation: pulse 1.5s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Instructions */
        .instructions {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(0, 255, 255, 0.3);
            border-radius: 15px;
            padding: 15px 30px;
            z-index: 10;
            pointer-events: none;
            text-align: center;
        }

        .instructions p {
            color: #00ffff;
            font-size: 0.9rem;
            margin: 5px 0;
        }

        /* Particle overlay */
        .particle-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .game-cards {
                gap: 30px;
            }

            .game-card {
                width: 300px;
                height: 400px;
            }

            .header h1 {
                font-size: 2.5rem;
            }
        }

        @media (max-width: 768px) {
            .game-card {
                width: 280px;
                height: 380px;
            }

            .game-icon {
                font-size: 4rem;
            }

            .game-title {
                font-size: 1.5rem;
            }

            .header h1 {
                font-size: 2rem;
            }

            .header p {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div id="scene-container">
        <!-- Loading Screen -->
        <div id="loading">
            <div class="spinner"></div>
            <p>🎮 LOADING GAME CENTER...</p>
        </div>

        <!-- Canvas -->
        <canvas id="game-canvas"></canvas>

        <!-- Header -->
        <div class="header">
            <h1>🎮 GAME CENTER 🎮</h1>
            <p>เลือกเกมที่คุณต้องการเล่น - Choose Your Adventure</p>
        </div>

        <!-- Game Cards -->
        <div class="game-cards">
            <!-- Card 1: 3D Navigation -->
            <div class="game-card" onclick="window.location.href='{{ route('demo.3d-navigation') }}'">
                <div class="card-inner">
                    <div class="game-icon">🧭</div>
                    <div class="game-title">3D Navigation</div>
                    <div class="game-description">
                        สำรวจโลก 3D ที่สวยงาม<br>
                        ด้วยระบบนำทางที่ทันสมัย
                    </div>
                    <button class="play-button">เริ่มเล่น</button>
                </div>
            </div>

            <!-- Card 2: Space Shooter -->
            <div class="game-card" onclick="window.location.href='{{ route('demo.space-shooter') }}'">
                <div class="card-inner">
                    <div class="game-icon">🚀</div>
                    <div class="game-title">Space Shooter</div>
                    <div class="game-description">
                        ยิงยานอวกาศศัตรู<br>
                        ในสงครามอวกาศที่ตื่นเต้น
                    </div>
                    <button class="play-button">เริ่มเล่น</button>
                </div>
            </div>

            <!-- Card 3: Loading Demo -->
            <div class="game-card" onclick="window.location.href='{{ route('demo.loading') }}'">
                <div class="card-inner">
                    <div class="game-icon">⚡</div>
                    <div class="game-title">Loading Demo</div>
                    <div class="game-description">
                        ชมเอฟเฟกต์การโหลด<br>
                        ที่สวยงามและทันสมัย
                    </div>
                    <button class="play-button">เริ่มเล่น</button>
                </div>
            </div>
        </div>

        <!-- Instructions -->
        <div class="instructions">
            <p>💡 คลิกที่การ์ดเพื่อเข้าสู่เกม | Move mouse to control camera</p>
        </div>
    </div>

    <!-- Three.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>

    <script>
        // ==================== SCENE SETUP ====================
        let scene, camera, renderer;
        let stars = [];
        let particles = [];
        let galaxies = [];
        let mouseX = 0;
        let mouseY = 0;
        let targetX = 0;
        let targetY = 0;

        function init() {
            // Scene
            scene = new THREE.Scene();
            scene.fog = new THREE.FogExp2(0x000000, 0.0008);

            // Camera
            camera = new THREE.PerspectiveCamera(
                75,
                window.innerWidth / window.innerHeight,
                0.1,
                2000
            );
            camera.position.set(0, 0, 50);

            // Renderer
            const canvas = document.getElementById('game-canvas');
            renderer = new THREE.WebGLRenderer({
                canvas,
                antialias: true,
                alpha: true
            });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

            // Lighting
            const ambientLight = new THREE.AmbientLight(0x404080, 0.5);
            scene.add(ambientLight);

            const pointLight1 = new THREE.PointLight(0x00ffff, 2, 200);
            pointLight1.position.set(-50, 50, 50);
            scene.add(pointLight1);

            const pointLight2 = new THREE.PointLight(0xff00ff, 2, 200);
            pointLight2.position.set(50, -50, 50);
            scene.add(pointLight2);

            const pointLight3 = new THREE.PointLight(0xffff00, 2, 200);
            pointLight3.position.set(0, 50, -50);
            scene.add(pointLight3);

            // Create environment
            createStarfield();
            createGalaxies();
            createParticleRings();
            createNebula();

            // Event listeners
            window.addEventListener('resize', onWindowResize);
            document.addEventListener('mousemove', onMouseMove);

            // Hide loading
            setTimeout(() => {
                document.getElementById('loading').classList.add('fade-out');
            }, 1500);

            // Start animation
            animate();
        }

        // ==================== CREATE ENVIRONMENT ====================
        function createStarfield() {
            const starGeometry = new THREE.BufferGeometry();
            const starVertices = [];
            const starColors = [];
            const starSizes = [];

            for (let i = 0; i < 5000; i++) {
                const x = (Math.random() - 0.5) * 2000;
                const y = (Math.random() - 0.5) * 2000;
                const z = (Math.random() - 0.5) * 2000;
                starVertices.push(x, y, z);

                // Random colors
                const color = new THREE.Color();
                const hue = Math.random();
                color.setHSL(hue, 0.7, 0.7);
                starColors.push(color.r, color.g, color.b);

                // Random sizes
                starSizes.push(Math.random() * 3 + 0.5);
            }

            starGeometry.setAttribute('position',
                new THREE.Float32BufferAttribute(starVertices, 3));
            starGeometry.setAttribute('color',
                new THREE.Float32BufferAttribute(starColors, 3));
            starGeometry.setAttribute('size',
                new THREE.Float32BufferAttribute(starSizes, 1));

            const starMaterial = new THREE.PointsMaterial({
                size: 2,
                sizeAttenuation: true,
                vertexColors: true,
                transparent: true,
                opacity: 0.8,
                blending: THREE.AdditiveBlending
            });

            const starField = new THREE.Points(starGeometry, starMaterial);
            scene.add(starField);
            stars.push(starField);
        }

        function createGalaxies() {
            for (let i = 0; i < 3; i++) {
                const galaxyGeometry = new THREE.BufferGeometry();
                const galaxyVertices = [];
                const galaxyColors = [];

                const centerColor = new THREE.Color(
                    i === 0 ? 0x00ffff : (i === 1 ? 0xff00ff : 0xffff00)
                );

                for (let j = 0; j < 2000; j++) {
                    const angle = Math.random() * Math.PI * 2;
                    const radius = Math.random() * 100 + 50;
                    const spiral = angle * 3;

                    const x = Math.cos(angle + spiral) * radius;
                    const y = (Math.random() - 0.5) * 20;
                    const z = Math.sin(angle + spiral) * radius;

                    galaxyVertices.push(x, y, z);

                    const color = centerColor.clone();
                    const intensity = 1 - (radius / 150);
                    color.multiplyScalar(intensity + 0.3);
                    galaxyColors.push(color.r, color.g, color.b);
                }

                galaxyGeometry.setAttribute('position',
                    new THREE.Float32BufferAttribute(galaxyVertices, 3));
                galaxyGeometry.setAttribute('color',
                    new THREE.Float32BufferAttribute(galaxyColors, 3));

                const galaxyMaterial = new THREE.PointsMaterial({
                    size: 1.5,
                    vertexColors: true,
                    transparent: true,
                    opacity: 0.6,
                    blending: THREE.AdditiveBlending
                });

                const galaxy = new THREE.Points(galaxyGeometry, galaxyMaterial);
                galaxy.position.set(
                    (Math.random() - 0.5) * 500,
                    (Math.random() - 0.5) * 300,
                    -200 - Math.random() * 200
                );
                scene.add(galaxy);
                galaxies.push(galaxy);
            }
        }

        function createParticleRings() {
            for (let i = 0; i < 5; i++) {
                const ringGeometry = new THREE.BufferGeometry();
                const ringVertices = [];
                const ringColors = [];

                const radius = 80 + i * 30;
                const particleCount = 100;

                for (let j = 0; j < particleCount; j++) {
                    const angle = (j / particleCount) * Math.PI * 2;
                    const x = Math.cos(angle) * radius;
                    const y = Math.sin(angle) * radius;
                    const z = (Math.random() - 0.5) * 10;

                    ringVertices.push(x, y, z);

                    const color = new THREE.Color();
                    color.setHSL(i * 0.2, 0.8, 0.6);
                    ringColors.push(color.r, color.g, color.b);
                }

                ringGeometry.setAttribute('position',
                    new THREE.Float32BufferAttribute(ringVertices, 3));
                ringGeometry.setAttribute('color',
                    new THREE.Float32BufferAttribute(ringColors, 3));

                const ringMaterial = new THREE.PointsMaterial({
                    size: 3,
                    vertexColors: true,
                    transparent: true,
                    opacity: 0.5,
                    blending: THREE.AdditiveBlending
                });

                const ring = new THREE.Points(ringGeometry, ringMaterial);
                ring.position.z = -100;
                ring.rotationSpeed = 0.001 * (i % 2 === 0 ? 1 : -1);
                scene.add(ring);
                particles.push(ring);
            }
        }

        function createNebula() {
            const nebulaGeometry = new THREE.BufferGeometry();
            const nebulaVertices = [];
            const nebulaColors = [];

            for (let i = 0; i < 1000; i++) {
                const x = (Math.random() - 0.5) * 600;
                const y = (Math.random() - 0.5) * 400;
                const z = (Math.random() - 0.5) * 400 - 200;

                nebulaVertices.push(x, y, z);

                const colors = [
                    new THREE.Color(0x00ffff),
                    new THREE.Color(0xff00ff),
                    new THREE.Color(0xffff00)
                ];
                const color = colors[Math.floor(Math.random() * colors.length)];
                nebulaColors.push(color.r, color.g, color.b);
            }

            nebulaGeometry.setAttribute('position',
                new THREE.Float32BufferAttribute(nebulaVertices, 3));
            nebulaGeometry.setAttribute('color',
                new THREE.Float32BufferAttribute(nebulaColors, 3));

            const nebulaMaterial = new THREE.PointsMaterial({
                size: 20,
                vertexColors: true,
                transparent: true,
                opacity: 0.15,
                blending: THREE.AdditiveBlending,
                depthWrite: false
            });

            const nebula = new THREE.Points(nebulaGeometry, nebulaMaterial);
            scene.add(nebula);
            particles.push(nebula);
        }

        // ==================== ANIMATION ====================
        function animate() {
            requestAnimationFrame(animate);

            // Smooth camera follow mouse
            targetX = mouseX * 0.001;
            targetY = mouseY * 0.001;

            camera.position.x += (targetX * 10 - camera.position.x) * 0.05;
            camera.position.y += (-targetY * 10 - camera.position.y) * 0.05;
            camera.lookAt(scene.position);

            // Rotate stars
            stars.forEach(star => {
                star.rotation.y += 0.0001;
                star.rotation.x += 0.00005;
            });

            // Rotate galaxies
            galaxies.forEach((galaxy, index) => {
                galaxy.rotation.z += 0.0005 * (index % 2 === 0 ? 1 : -1);
                galaxy.rotation.y += 0.0002;
            });

            // Rotate particle rings
            particles.forEach(particle => {
                if (particle.rotationSpeed) {
                    particle.rotation.z += particle.rotationSpeed;
                }
                particle.rotation.y += 0.0003;
            });

            renderer.render(scene, camera);
        }

        // ==================== EVENT HANDLERS ====================
        function onMouseMove(event) {
            mouseX = event.clientX - window.innerWidth / 2;
            mouseY = event.clientY - window.innerHeight / 2;
        }

        function onWindowResize() {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        }

        // ==================== START ====================
        init();
    </script>
</body>
</html>
