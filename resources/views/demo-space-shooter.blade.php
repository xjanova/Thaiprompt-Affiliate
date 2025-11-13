<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Space Shooter 3D - Three.js Demo</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Noto+Sans+Thai:wght@400;600;700&display=swap" rel="stylesheet">

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
        }

        #game-container {
            width: 100vw;
            height: 100vh;
            position: relative;
        }

        #game-canvas {
            display: block;
            width: 100%;
            height: 100%;
        }

        /* HUD Overlay */
        #hud {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            pointer-events: none;
            z-index: 10;
            padding: 20px;
            color: #fff;
        }

        .hud-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .hud-item {
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(0, 255, 255, 0.5);
            border-radius: 10px;
            padding: 15px 25px;
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.3);
        }

        .hud-label {
            font-size: 14px;
            opacity: 0.8;
            margin-bottom: 5px;
        }

        .hud-value {
            font-size: 32px;
            font-weight: 900;
            color: #00ffff;
            text-shadow: 0 0 10px rgba(0, 255, 255, 0.8);
        }

        .health-bar {
            width: 200px;
            height: 30px;
            background: rgba(255, 0, 0, 0.2);
            border: 2px solid rgba(255, 0, 0, 0.5);
            border-radius: 5px;
            overflow: hidden;
            position: relative;
        }

        .health-fill {
            height: 100%;
            background: linear-gradient(90deg, #ff0000, #ff6600);
            box-shadow: 0 0 10px rgba(255, 0, 0, 0.8);
            transition: width 0.3s ease;
        }

        .health-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 14px;
            font-weight: bold;
            color: white;
            text-shadow: 1px 1px 2px black;
        }

        /* Game Over Screen */
        #game-over {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(20px);
            border: 3px solid #ff0000;
            border-radius: 20px;
            padding: 50px;
            display: none;
            z-index: 100;
            pointer-events: auto;
            box-shadow: 0 0 50px rgba(255, 0, 0, 0.5);
        }

        #game-over.show {
            display: block;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translate(-50%, -60%);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
        }

        #game-over h1 {
            font-size: 64px;
            color: #ff0000;
            margin-bottom: 20px;
            text-shadow: 0 0 20px rgba(255, 0, 0, 0.8);
        }

        #game-over p {
            font-size: 24px;
            color: #fff;
            margin-bottom: 30px;
        }

        #restart-btn {
            background: linear-gradient(135deg, #00ffff, #0080ff);
            border: none;
            color: #000;
            font-family: 'Orbitron', sans-serif;
            font-size: 24px;
            font-weight: 900;
            padding: 20px 50px;
            border-radius: 50px;
            cursor: pointer;
            pointer-events: auto;
            transition: all 0.3s ease;
            box-shadow: 0 0 30px rgba(0, 255, 255, 0.5);
        }

        #restart-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 0 50px rgba(0, 255, 255, 0.8);
        }

        /* Instructions */
        #instructions {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(0, 255, 255, 0.5);
            border-radius: 10px;
            padding: 15px 30px;
            pointer-events: none;
            text-align: center;
        }

        #instructions p {
            color: #fff;
            font-size: 14px;
            margin: 5px 0;
        }

        .key {
            display: inline-block;
            background: rgba(0, 255, 255, 0.2);
            border: 1px solid #00ffff;
            border-radius: 5px;
            padding: 2px 8px;
            margin: 0 3px;
            color: #00ffff;
            font-weight: bold;
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
        }

        .spinner {
            width: 60px;
            height: 60px;
            border: 5px solid rgba(0, 255, 255, 0.3);
            border-top-color: #00ffff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        #loading p {
            color: #00ffff;
            font-size: 24px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div id="game-container">
        <!-- Loading Screen -->
        <div id="loading">
            <div class="spinner"></div>
            <p>LOADING...</p>
        </div>

        <!-- Canvas -->
        <canvas id="game-canvas"></canvas>

        <!-- HUD -->
        <div id="hud">
            <div class="hud-top">
                <div class="hud-item">
                    <div class="hud-label">SCORE</div>
                    <div class="hud-value" id="score">0</div>
                </div>

                <div class="hud-item">
                    <div class="hud-label">HEALTH</div>
                    <div class="health-bar">
                        <div class="health-fill" id="health-fill"></div>
                        <div class="health-text" id="health-text">100</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Game Over -->
        <div id="game-over">
            <h1>GAME OVER</h1>
            <p>Final Score: <span id="final-score">0</span></p>
            <button id="restart-btn">PLAY AGAIN</button>
        </div>

        <!-- Instructions -->
        <div id="instructions">
            <p>
                <span class="key">←</span> <span class="key">→</span> เคลื่อนที่ |
                <span class="key">SPACE</span> ยิง |
                <span class="key">MOUSE</span> เล็ง
            </p>
        </div>
    </div>

    <!-- Three.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>

    <script>
        // ==================== GAME SETUP ====================
        let scene, camera, renderer;
        let playerShip;
        let enemies = [];
        let bullets = [];
        let stars = [];
        let explosions = [];
        let score = 0;
        let health = 100;
        let gameOver = false;
        let keys = {};

        // Game Settings
        const PLAYER_SPEED = 0.5;
        const BULLET_SPEED = 1;
        const ENEMY_SPEED = 0.3;
        const SPAWN_RATE = 60; // frames
        let frameCount = 0;

        // ==================== INIT ====================
        function init() {
            // Scene
            scene = new THREE.Scene();
            scene.fog = new THREE.FogExp2(0x000000, 0.0025);

            // Camera
            camera = new THREE.PerspectiveCamera(
                75,
                window.innerWidth / window.innerHeight,
                0.1,
                1000
            );
            camera.position.set(0, 5, 15);
            camera.lookAt(0, 0, 0);

            // Renderer
            const canvas = document.getElementById('game-canvas');
            renderer = new THREE.WebGLRenderer({
                canvas,
                antialias: true
            });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.shadowMap.enabled = true;

            // Lights
            const ambientLight = new THREE.AmbientLight(0x404040, 1);
            scene.add(ambientLight);

            const directionalLight = new THREE.DirectionalLight(0xffffff, 1);
            directionalLight.position.set(5, 10, 5);
            directionalLight.castShadow = true;
            scene.add(directionalLight);

            // Create starfield
            createStarfield();

            // Create player ship
            createPlayerShip();

            // Event listeners
            window.addEventListener('resize', onWindowResize);
            window.addEventListener('keydown', (e) => keys[e.code] = true);
            window.addEventListener('keyup', (e) => keys[e.code] = false);
            document.getElementById('restart-btn').addEventListener('click', restartGame);

            // Hide loading screen
            document.getElementById('loading').style.display = 'none';

            // Start game loop
            animate();
        }

        // ==================== CREATE OBJECTS ====================
        function createStarfield() {
            const starGeometry = new THREE.BufferGeometry();
            const starVertices = [];

            for (let i = 0; i < 1000; i++) {
                const x = (Math.random() - 0.5) * 100;
                const y = (Math.random() - 0.5) * 100;
                const z = (Math.random() - 0.5) * 100 - 50;
                starVertices.push(x, y, z);
            }

            starGeometry.setAttribute('position',
                new THREE.Float32BufferAttribute(starVertices, 3));

            const starMaterial = new THREE.PointsMaterial({
                color: 0xffffff,
                size: 0.3,
                transparent: true,
                opacity: 0.8
            });

            const starField = new THREE.Points(starGeometry, starMaterial);
            scene.add(starField);
            stars.push(starField);
        }

        function createPlayerShip() {
            const shipGroup = new THREE.Group();

            // Main body (pyramid)
            const bodyGeometry = new THREE.ConeGeometry(0.5, 2, 4);
            const bodyMaterial = new THREE.MeshPhongMaterial({
                color: 0x00ffff,
                emissive: 0x00ffff,
                emissiveIntensity: 0.3,
                shininess: 100
            });
            const body = new THREE.Mesh(bodyGeometry, bodyMaterial);
            body.rotation.x = Math.PI;
            shipGroup.add(body);

            // Wings
            const wingGeometry = new THREE.BoxGeometry(2, 0.1, 1);
            const wingMaterial = new THREE.MeshPhongMaterial({
                color: 0x0080ff,
                emissive: 0x0080ff,
                emissiveIntensity: 0.2
            });
            const wings = new THREE.Mesh(wingGeometry, wingMaterial);
            wings.position.y = -0.3;
            shipGroup.add(wings);

            // Engine glow
            const glowGeometry = new THREE.SphereGeometry(0.3, 8, 8);
            const glowMaterial = new THREE.MeshBasicMaterial({
                color: 0xff6600,
                transparent: true,
                opacity: 0.8
            });
            const glow = new THREE.Mesh(glowGeometry, glowMaterial);
            glow.position.z = 1;
            shipGroup.add(glow);

            shipGroup.position.set(0, 0, 10);
            scene.add(shipGroup);
            playerShip = shipGroup;
        }

        function createEnemy() {
            const enemyGroup = new THREE.Group();

            // Body
            const bodyGeometry = new THREE.BoxGeometry(1, 1, 1);
            const bodyMaterial = new THREE.MeshPhongMaterial({
                color: 0xff0000,
                emissive: 0xff0000,
                emissiveIntensity: 0.3
            });
            const body = new THREE.Mesh(bodyGeometry, bodyMaterial);
            enemyGroup.add(body);

            // Spikes
            for (let i = 0; i < 4; i++) {
                const spikeGeometry = new THREE.ConeGeometry(0.2, 0.5, 4);
                const spike = new THREE.Mesh(spikeGeometry, bodyMaterial);
                spike.rotation.z = (Math.PI / 2) * i;
                spike.position.x = Math.sin((Math.PI / 2) * i) * 0.7;
                spike.position.y = Math.cos((Math.PI / 2) * i) * 0.7;
                enemyGroup.add(spike);
            }

            const x = (Math.random() - 0.5) * 20;
            const y = (Math.random() - 0.5) * 10;
            enemyGroup.position.set(x, y, -20);

            scene.add(enemyGroup);
            enemies.push(enemyGroup);
        }

        function createBullet() {
            const bulletGeometry = new THREE.SphereGeometry(0.2, 8, 8);
            const bulletMaterial = new THREE.MeshBasicMaterial({
                color: 0xffff00
            });
            const bullet = new THREE.Mesh(bulletGeometry, bulletMaterial);

            bullet.position.copy(playerShip.position);
            bullet.position.z -= 1;

            // Add glow
            const glowGeometry = new THREE.SphereGeometry(0.3, 8, 8);
            const glowMaterial = new THREE.MeshBasicMaterial({
                color: 0xffff00,
                transparent: true,
                opacity: 0.5
            });
            const glow = new THREE.Mesh(glowGeometry, glowMaterial);
            bullet.add(glow);

            scene.add(bullet);
            bullets.push(bullet);
        }

        function createExplosion(position) {
            const particleCount = 30;
            const particles = [];

            for (let i = 0; i < particleCount; i++) {
                const geometry = new THREE.SphereGeometry(0.1, 4, 4);
                const material = new THREE.MeshBasicMaterial({
                    color: Math.random() > 0.5 ? 0xff6600 : 0xffff00
                });
                const particle = new THREE.Mesh(geometry, material);

                particle.position.copy(position);
                particle.velocity = new THREE.Vector3(
                    (Math.random() - 0.5) * 0.5,
                    (Math.random() - 0.5) * 0.5,
                    (Math.random() - 0.5) * 0.5
                );
                particle.life = 30;

                scene.add(particle);
                particles.push(particle);
            }

            explosions.push({ particles, life: 30 });
        }

        // ==================== UPDATE ====================
        function update() {
            if (gameOver) return;

            // Player movement
            if ((keys['ArrowLeft'] || keys['KeyA']) && playerShip.position.x > -10) {
                playerShip.position.x -= PLAYER_SPEED;
            }
            if ((keys['ArrowRight'] || keys['KeyD']) && playerShip.position.x < 10) {
                playerShip.position.x += PLAYER_SPEED;
            }
            if ((keys['ArrowUp'] || keys['KeyW']) && playerShip.position.y < 8) {
                playerShip.position.y += PLAYER_SPEED;
            }
            if ((keys['ArrowDown'] || keys['KeyS']) && playerShip.position.y > -8) {
                playerShip.position.y -= PLAYER_SPEED;
            }

            // Shooting
            if (keys['Space']) {
                if (frameCount % 10 === 0) { // Rate limit
                    createBullet();
                }
            }

            // Move bullets
            for (let i = bullets.length - 1; i >= 0; i--) {
                bullets[i].position.z -= BULLET_SPEED;

                // Remove if off screen
                if (bullets[i].position.z < -30) {
                    scene.remove(bullets[i]);
                    bullets.splice(i, 1);
                }
            }

            // Move enemies
            for (let i = enemies.length - 1; i >= 0; i--) {
                enemies[i].position.z += ENEMY_SPEED;
                enemies[i].rotation.x += 0.02;
                enemies[i].rotation.y += 0.02;

                // Remove if past player
                if (enemies[i].position.z > 20) {
                    scene.remove(enemies[i]);
                    enemies.splice(i, 1);
                    takeDamage(10);
                }
            }

            // Collision detection
            checkCollisions();

            // Update explosions
            updateExplosions();

            // Spawn enemies
            frameCount++;
            if (frameCount % SPAWN_RATE === 0) {
                createEnemy();
            }

            // Animate stars
            stars.forEach(star => {
                star.position.z += 0.5;
                if (star.position.z > 50) {
                    star.position.z = -50;
                }
            });
        }

        function checkCollisions() {
            // Bullet-Enemy collision
            for (let i = bullets.length - 1; i >= 0; i--) {
                for (let j = enemies.length - 1; j >= 0; j--) {
                    const distance = bullets[i].position.distanceTo(enemies[j].position);

                    if (distance < 1) {
                        // Hit!
                        createExplosion(enemies[j].position);

                        scene.remove(bullets[i]);
                        bullets.splice(i, 1);

                        scene.remove(enemies[j]);
                        enemies.splice(j, 1);

                        addScore(10);
                        break;
                    }
                }
            }

            // Player-Enemy collision
            for (let i = enemies.length - 1; i >= 0; i--) {
                const distance = playerShip.position.distanceTo(enemies[i].position);

                if (distance < 1.5) {
                    createExplosion(enemies[i].position);
                    scene.remove(enemies[i]);
                    enemies.splice(i, 1);
                    takeDamage(20);
                }
            }
        }

        function updateExplosions() {
            for (let i = explosions.length - 1; i >= 0; i--) {
                const explosion = explosions[i];
                explosion.life--;

                explosion.particles.forEach(particle => {
                    particle.position.add(particle.velocity);
                    particle.material.opacity = explosion.life / 30;
                });

                if (explosion.life <= 0) {
                    explosion.particles.forEach(particle => scene.remove(particle));
                    explosions.splice(i, 1);
                }
            }
        }

        // ==================== GAME LOGIC ====================
        function addScore(points) {
            score += points;
            document.getElementById('score').textContent = score;
        }

        function takeDamage(amount) {
            health -= amount;
            if (health < 0) health = 0;

            const healthPercent = health;
            document.getElementById('health-fill').style.width = healthPercent + '%';
            document.getElementById('health-text').textContent = health;

            if (health <= 0) {
                endGame();
            }
        }

        function endGame() {
            gameOver = true;
            document.getElementById('final-score').textContent = score;
            document.getElementById('game-over').classList.add('show');
        }

        function restartGame() {
            // Reset variables
            score = 0;
            health = 100;
            gameOver = false;
            frameCount = 0;

            // Clear arrays
            bullets.forEach(b => scene.remove(b));
            bullets = [];
            enemies.forEach(e => scene.remove(e));
            enemies = [];
            explosions.forEach(e => e.particles.forEach(p => scene.remove(p)));
            explosions = [];

            // Reset UI
            document.getElementById('score').textContent = '0';
            document.getElementById('health-fill').style.width = '100%';
            document.getElementById('health-text').textContent = '100';
            document.getElementById('game-over').classList.remove('show');

            // Reset player position
            playerShip.position.set(0, 0, 10);
        }

        // ==================== ANIMATION LOOP ====================
        function animate() {
            requestAnimationFrame(animate);
            update();
            renderer.render(scene, camera);
        }

        // ==================== WINDOW RESIZE ====================
        function onWindowResize() {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        }

        // ==================== START GAME ====================
        init();
    </script>
</body>
</html>
