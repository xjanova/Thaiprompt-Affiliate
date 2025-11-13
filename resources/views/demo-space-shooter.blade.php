<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Space Shooter 3D Ultimate - Three.js Demo</title>
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
            flex-wrap: wrap;
            gap: 15px;
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
            font-size: 12px;
            opacity: 0.8;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .hud-value {
            font-size: 28px;
            font-weight: 900;
            color: #00ffff;
            text-shadow: 0 0 10px rgba(0, 255, 255, 0.8);
        }

        .health-bar, .boss-health-bar {
            width: 200px;
            height: 30px;
            background: rgba(255, 0, 0, 0.2);
            border: 2px solid rgba(255, 0, 0, 0.5);
            border-radius: 5px;
            overflow: hidden;
            position: relative;
        }

        .health-fill, .boss-health-fill {
            height: 100%;
            background: linear-gradient(90deg, #ff0000, #ff6600);
            box-shadow: 0 0 10px rgba(255, 0, 0, 0.8);
            transition: width 0.3s ease;
        }

        .health-text, .boss-health-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 14px;
            font-weight: bold;
            color: white;
            text-shadow: 1px 1px 2px black;
        }

        /* Boss Health Bar (center top) */
        #boss-health-container {
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: none;
            text-align: center;
        }

        #boss-health-container.active {
            display: block;
            animation: bossAppear 0.5s ease;
        }

        @keyframes bossAppear {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }

        .boss-health-bar {
            width: 400px;
            height: 40px;
            background: rgba(139, 0, 0, 0.3);
            border: 3px solid rgba(255, 0, 0, 0.8);
        }

        .boss-name {
            font-size: 24px;
            color: #ff0000;
            text-shadow: 0 0 15px rgba(255, 0, 0, 1);
            margin-bottom: 10px;
            font-weight: 900;
        }

        /* Power-up Status */
        .powerup-status {
            position: absolute;
            bottom: 100px;
            left: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .powerup-indicator {
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid;
            border-radius: 8px;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: bold;
            display: none;
            animation: pulse 1s infinite;
        }

        .powerup-indicator.active {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .powerup-shield {
            border-color: #00ffff;
            color: #00ffff;
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.5);
        }

        .powerup-rapidfire {
            border-color: #ffff00;
            color: #ffff00;
            box-shadow: 0 0 20px rgba(255, 255, 0, 0.5);
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        /* Game Over Screen */
        #game-over {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(20px);
            border: 3px solid #ff0000;
            border-radius: 20px;
            padding: 50px;
            display: none;
            z-index: 100;
            pointer-events: auto;
            box-shadow: 0 0 50px rgba(255, 0, 0, 0.5);
            max-width: 600px;
            width: 90%;
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

        #restart-btn, #save-score-btn {
            background: linear-gradient(135deg, #00ffff, #0080ff);
            border: none;
            color: #000;
            font-family: 'Orbitron', sans-serif;
            font-size: 20px;
            font-weight: 900;
            padding: 15px 40px;
            border-radius: 50px;
            cursor: pointer;
            pointer-events: auto;
            transition: all 0.3s ease;
            box-shadow: 0 0 30px rgba(0, 255, 255, 0.5);
            margin: 5px;
        }

        #restart-btn:hover, #save-score-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 0 50px rgba(0, 255, 255, 0.8);
        }

        /* Leaderboard */
        .leaderboard {
            margin-top: 30px;
            background: rgba(0, 0, 0, 0.5);
            border: 2px solid rgba(0, 255, 255, 0.3);
            border-radius: 10px;
            padding: 20px;
            max-height: 300px;
            overflow-y: auto;
        }

        .leaderboard h3 {
            color: #00ffff;
            margin-bottom: 15px;
            font-size: 20px;
        }

        .leaderboard-entry {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            margin: 5px 0;
            background: rgba(0, 255, 255, 0.1);
            border-radius: 5px;
            font-size: 14px;
        }

        .leaderboard-entry.highlight {
            background: rgba(255, 255, 0, 0.2);
            border: 1px solid #ffff00;
        }

        .name-input {
            background: rgba(0, 0, 0, 0.5);
            border: 2px solid #00ffff;
            color: #fff;
            padding: 10px 20px;
            border-radius: 10px;
            font-family: 'Orbitron', sans-serif;
            font-size: 16px;
            margin: 10px 0;
            text-align: center;
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
            font-size: 12px;
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

        /* Wave notification */
        .wave-notification {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 64px;
            font-weight: 900;
            color: #ff0000;
            text-shadow: 0 0 30px rgba(255, 0, 0, 1);
            pointer-events: none;
            z-index: 50;
            animation: waveAppear 2s ease-out forwards;
        }

        @keyframes waveAppear {
            0% {
                opacity: 0;
                transform: translate(-50%, -50%) scale(0.5);
            }
            50% {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1.2);
            }
            100% {
                opacity: 0;
                transform: translate(-50%, -50%) scale(1);
            }
        }
    </style>
</head>
<body>
    <div id="game-container">
        <!-- Loading Screen -->
        <div id="loading">
            <div class="spinner"></div>
            <p>LOADING WEAPONS...</p>
        </div>

        <!-- Canvas -->
        <canvas id="game-canvas"></canvas>

        <!-- HUD -->
        <div id="hud">
            <div class="hud-top">
                <div class="hud-item">
                    <div class="hud-label">Score</div>
                    <div class="hud-value" id="score">0</div>
                </div>

                <div class="hud-item">
                    <div class="hud-label">Wave</div>
                    <div class="hud-value" id="wave">1</div>
                </div>

                <div class="hud-item">
                    <div class="hud-label">Health</div>
                    <div class="health-bar">
                        <div class="health-fill" id="health-fill"></div>
                        <div class="health-text" id="health-text">100</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Boss Health -->
        <div id="boss-health-container">
            <div class="boss-name">⚡ MEGA BOSS ⚡</div>
            <div class="boss-health-bar">
                <div class="boss-health-fill" id="boss-health-fill"></div>
                <div class="boss-health-text" id="boss-health-text">1000</div>
            </div>
        </div>

        <!-- Power-up Status -->
        <div class="powerup-status">
            <div class="powerup-indicator powerup-shield" id="shield-indicator">
                🛡️ SHIELD ACTIVE
            </div>
            <div class="powerup-indicator powerup-rapidfire" id="rapidfire-indicator">
                ⚡ RAPID FIRE
            </div>
        </div>

        <!-- Game Over -->
        <div id="game-over">
            <h1>GAME OVER</h1>
            <p>Final Score: <span id="final-score">0</span></p>
            <p>Wave Reached: <span id="final-wave">1</span></p>

            <div id="score-save-section">
                <input type="text" id="player-name" class="name-input" placeholder="Enter your name" maxlength="20">
                <br>
                <button id="save-score-btn">💾 SAVE SCORE</button>
            </div>

            <button id="restart-btn">🚀 PLAY AGAIN</button>

            <div class="leaderboard">
                <h3>🏆 TOP PILOTS 🏆</h3>
                <div id="leaderboard-list"></div>
            </div>
        </div>

        <!-- Instructions -->
        <div id="instructions">
            <p>
                <span class="key">←</span> <span class="key">→</span> Move |
                <span class="key">SPACE</span> Shoot |
                Power-ups: 🛡️ Shield | ⚡ Rapid Fire
            </p>
        </div>
    </div>

    <!-- Three.js + Effects -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>

    <script>
        // ==================== AUDIO SYSTEM ====================
        class AudioSystem {
            constructor() {
                this.sounds = {};
                this.muted = false;
            }

            createSound(frequency, duration, type = 'sine') {
                if (this.muted) return;
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();

                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);

                oscillator.frequency.value = frequency;
                oscillator.type = type;

                gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + duration);

                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + duration);
            }

            playShoot() {
                this.createSound(800, 0.1, 'square');
            }

            playExplosion() {
                this.createSound(100, 0.3, 'sawtooth');
            }

            playPowerUp() {
                this.createSound(1200, 0.2, 'sine');
                setTimeout(() => this.createSound(1400, 0.2, 'sine'), 100);
            }

            playBossHit() {
                this.createSound(150, 0.15, 'triangle');
            }

            playDamage() {
                this.createSound(200, 0.2, 'sawtooth');
            }
        }

        // ==================== GAME SETUP ====================
        let scene, camera, renderer;
        let playerShip;
        let enemies = [];
        let bullets = [];
        let stars = [];
        let explosions = [];
        let powerups = [];
        let boss = null;
        let trails = [];

        let score = 0;
        let health = 100;
        let wave = 1;
        let gameOver = false;
        let keys = {};

        // Power-up states
        let hasShield = false;
        let hasRapidFire = false;
        let shieldTimer = 0;
        let rapidFireTimer = 0;

        // Game Settings
        const PLAYER_SPEED = 0.5;
        const BULLET_SPEED = 1.5;
        const ENEMY_SPEED = 0.3;
        const SPAWN_RATE = 50;
        const POWERUP_SPAWN_RATE = 600;
        const BOSS_WAVE = 5;

        let frameCount = 0;
        let enemiesKilled = 0;

        const audio = new AudioSystem();

        // ==================== INIT ====================
        function init() {
            // Scene
            scene = new THREE.Scene();
            scene.fog = new THREE.FogExp2(0x000033, 0.002);

            // Camera
            camera = new THREE.PerspectiveCamera(
                75,
                window.innerWidth / window.innerHeight,
                0.1,
                1000
            );
            camera.position.set(0, 5, 15);
            camera.lookAt(0, 0, 0);

            // Renderer with improved settings
            const canvas = document.getElementById('game-canvas');
            renderer = new THREE.WebGLRenderer({
                canvas,
                antialias: true,
                alpha: true
            });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setPixelRatio(window.devicePixelRatio);
            renderer.shadowMap.enabled = true;
            renderer.shadowMap.type = THREE.PCFSoftShadowMap;

            // Enhanced Lights
            const ambientLight = new THREE.AmbientLight(0x404080, 0.5);
            scene.add(ambientLight);

            const directionalLight = new THREE.DirectionalLight(0xffffff, 1);
            directionalLight.position.set(5, 10, 5);
            directionalLight.castShadow = true;
            scene.add(directionalLight);

            // Point lights for atmosphere
            const pointLight1 = new THREE.PointLight(0x00ffff, 2, 50);
            pointLight1.position.set(-10, 5, -10);
            scene.add(pointLight1);

            const pointLight2 = new THREE.PointLight(0xff00ff, 2, 50);
            pointLight2.position.set(10, 5, -10);
            scene.add(pointLight2);

            // Create starfield
            createStarfield();

            // Create player ship with trail
            createPlayerShip();

            // Event listeners
            window.addEventListener('resize', onWindowResize);
            window.addEventListener('keydown', (e) => {
                keys[e.code] = true;
                if (e.code === 'KeyM') audio.muted = !audio.muted;
            });
            window.addEventListener('keyup', (e) => keys[e.code] = false);

            document.getElementById('restart-btn').addEventListener('click', restartGame);
            document.getElementById('save-score-btn').addEventListener('click', saveScore);

            // Load leaderboard
            loadLeaderboard();

            // Hide loading screen
            document.getElementById('loading').style.display = 'none';

            // Start game loop
            animate();
        }

        // ==================== CREATE OBJECTS ====================
        function createStarfield() {
            const starGeometry = new THREE.BufferGeometry();
            const starVertices = [];
            const starColors = [];

            for (let i = 0; i < 2000; i++) {
                const x = (Math.random() - 0.5) * 150;
                const y = (Math.random() - 0.5) * 150;
                const z = (Math.random() - 0.5) * 150 - 50;
                starVertices.push(x, y, z);

                // Random colors
                const color = new THREE.Color();
                color.setHSL(Math.random(), 0.5, 0.8);
                starColors.push(color.r, color.g, color.b);
            }

            starGeometry.setAttribute('position',
                new THREE.Float32BufferAttribute(starVertices, 3));
            starGeometry.setAttribute('color',
                new THREE.Float32BufferAttribute(starColors, 3));

            const starMaterial = new THREE.PointsMaterial({
                size: 0.3,
                vertexColors: true,
                transparent: true,
                opacity: 0.8
            });

            const starField = new THREE.Points(starGeometry, starMaterial);
            scene.add(starField);
            stars.push(starField);
        }

        function createPlayerShip() {
            const shipGroup = new THREE.Group();

            // Main body (pyramid) with texture-like material
            const bodyGeometry = new THREE.ConeGeometry(0.5, 2, 4);
            const bodyMaterial = new THREE.MeshPhongMaterial({
                color: 0x00ffff,
                emissive: 0x00ffff,
                emissiveIntensity: 0.5,
                shininess: 100,
                specular: 0xffffff
            });
            const body = new THREE.Mesh(bodyGeometry, bodyMaterial);
            body.rotation.x = Math.PI;
            body.castShadow = true;
            shipGroup.add(body);

            // Wings with metallic look
            const wingGeometry = new THREE.BoxGeometry(2.5, 0.1, 1);
            const wingMaterial = new THREE.MeshPhongMaterial({
                color: 0x0080ff,
                emissive: 0x004080,
                emissiveIntensity: 0.3,
                shininess: 80
            });
            const wings = new THREE.Mesh(wingGeometry, wingMaterial);
            wings.position.y = -0.3;
            wings.castShadow = true;
            shipGroup.add(wings);

            // Engine glow (animated)
            const glowGeometry = new THREE.SphereGeometry(0.3, 8, 8);
            const glowMaterial = new THREE.MeshBasicMaterial({
                color: 0xff6600,
                transparent: true,
                opacity: 0.8
            });
            const glow = new THREE.Mesh(glowGeometry, glowMaterial);
            glow.position.z = 1;
            shipGroup.add(glow);
            shipGroup.engineGlow = glow; // Save reference for animation

            // Cockpit detail
            const cockpitGeometry = new THREE.SphereGeometry(0.2, 8, 8);
            const cockpitMaterial = new THREE.MeshPhongMaterial({
                color: 0xffff00,
                emissive: 0xffff00,
                emissiveIntensity: 0.8,
                transparent: true,
                opacity: 0.9
            });
            const cockpit = new THREE.Mesh(cockpitGeometry, cockpitMaterial);
            cockpit.position.z = -0.5;
            shipGroup.add(cockpit);

            shipGroup.position.set(0, 0, 10);
            scene.add(shipGroup);
            playerShip = shipGroup;
        }

        function createEnemy() {
            const enemyGroup = new THREE.Group();
            const enemyType = Math.random();

            if (enemyType < 0.3) {
                // Type 1: Box enemy
                const bodyGeometry = new THREE.BoxGeometry(1, 1, 1);
                const bodyMaterial = new THREE.MeshPhongMaterial({
                    color: 0xff0000,
                    emissive: 0xff0000,
                    emissiveIntensity: 0.5,
                    shininess: 50
                });
                const body = new THREE.Mesh(bodyGeometry, bodyMaterial);
                body.castShadow = true;
                enemyGroup.add(body);

                for (let i = 0; i < 4; i++) {
                    const spikeGeometry = new THREE.ConeGeometry(0.2, 0.5, 4);
                    const spike = new THREE.Mesh(spikeGeometry, bodyMaterial);
                    spike.rotation.z = (Math.PI / 2) * i;
                    spike.position.x = Math.sin((Math.PI / 2) * i) * 0.7;
                    spike.position.y = Math.cos((Math.PI / 2) * i) * 0.7;
                    enemyGroup.add(spike);
                }
            } else if (enemyType < 0.6) {
                // Type 2: Octahedron enemy
                const bodyGeometry = new THREE.OctahedronGeometry(0.7);
                const bodyMaterial = new THREE.MeshPhongMaterial({
                    color: 0xff6600,
                    emissive: 0xff3300,
                    emissiveIntensity: 0.5,
                    shininess: 80
                });
                const body = new THREE.Mesh(bodyGeometry, bodyMaterial);
                body.castShadow = true;
                enemyGroup.add(body);
            } else {
                // Type 3: Torus enemy
                const bodyGeometry = new THREE.TorusGeometry(0.5, 0.2, 8, 16);
                const bodyMaterial = new THREE.MeshPhongMaterial({
                    color: 0xff00ff,
                    emissive: 0x800080,
                    emissiveIntensity: 0.5,
                    shininess: 100
                });
                const body = new THREE.Mesh(bodyGeometry, bodyMaterial);
                body.castShadow = true;
                enemyGroup.add(body);
            }

            const x = (Math.random() - 0.5) * 20;
            const y = (Math.random() - 0.5) * 10;
            enemyGroup.position.set(x, y, -25);
            enemyGroup.speed = ENEMY_SPEED * (1 + wave * 0.1);

            scene.add(enemyGroup);
            enemies.push(enemyGroup);
        }

        function createBoss() {
            const bossGroup = new THREE.Group();

            // Main body - large dodecahedron
            const bodyGeometry = new THREE.DodecahedronGeometry(3);
            const bodyMaterial = new THREE.MeshPhongMaterial({
                color: 0x8b0000,
                emissive: 0xff0000,
                emissiveIntensity: 0.8,
                shininess: 100,
                specular: 0xffffff
            });
            const body = new THREE.Mesh(bodyGeometry, bodyMaterial);
            body.castShadow = true;
            bossGroup.add(body);

            // Rotating rings
            for (let i = 0; i < 3; i++) {
                const ringGeometry = new THREE.TorusGeometry(4 + i, 0.3, 8, 32);
                const ringMaterial = new THREE.MeshPhongMaterial({
                    color: 0xff0000,
                    emissive: 0xff0000,
                    emissiveIntensity: 0.6,
                    transparent: true,
                    opacity: 0.7
                });
                const ring = new THREE.Mesh(ringGeometry, ringMaterial);
                ring.rotation.x = Math.PI / 2 + (i * 0.3);
                bossGroup.add(ring);
                if (!bossGroup.rings) bossGroup.rings = [];
                bossGroup.rings.push(ring);
            }

            // Glowing core
            const coreGeometry = new THREE.SphereGeometry(1.5, 16, 16);
            const coreMaterial = new THREE.MeshBasicMaterial({
                color: 0xffff00,
                transparent: true,
                opacity: 0.8
            });
            const core = new THREE.Mesh(coreGeometry, coreMaterial);
            bossGroup.add(core);
            bossGroup.core = core;

            bossGroup.position.set(0, 0, -20);
            bossGroup.health = 1000;
            bossGroup.maxHealth = 1000;
            bossGroup.moveDirection = 1;

            scene.add(bossGroup);
            boss = bossGroup;

            // Show boss health bar
            document.getElementById('boss-health-container').classList.add('active');
            updateBossHealth();

            // Boss wave notification
            showWaveNotification('⚡ BOSS WAVE! ⚡');
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
            const glowGeometry = new THREE.SphereGeometry(0.35, 8, 8);
            const glowMaterial = new THREE.MeshBasicMaterial({
                color: 0xffff00,
                transparent: true,
                opacity: 0.5
            });
            const glow = new THREE.Mesh(glowGeometry, glowMaterial);
            bullet.add(glow);

            // Trail effect
            createTrail(bullet, 0xffff00);

            scene.add(bullet);
            bullets.push(bullet);

            audio.playShoot();
        }

        function createPowerUp(position) {
            const powerupGroup = new THREE.Group();
            const type = Math.random() > 0.5 ? 'shield' : 'rapidfire';

            const geometry = new THREE.OctahedronGeometry(0.5);
            const material = new THREE.MeshPhongMaterial({
                color: type === 'shield' ? 0x00ffff : 0xffff00,
                emissive: type === 'shield' ? 0x00ffff : 0xffff00,
                emissiveIntensity: 0.8,
                transparent: true,
                opacity: 0.9
            });
            const mesh = new THREE.Mesh(geometry, material);
            powerupGroup.add(mesh);

            // Rotating ring indicator
            const ringGeometry = new THREE.TorusGeometry(0.7, 0.1, 8, 16);
            const ring = new THREE.Mesh(ringGeometry, material);
            powerupGroup.add(ring);
            powerupGroup.ring = ring;

            powerupGroup.position.copy(position);
            powerupGroup.type = type;
            powerupGroup.rotationSpeed = 0.05;

            scene.add(powerupGroup);
            powerups.push(powerupGroup);
        }

        function createTrail(object, color) {
            const trailPoints = [];
            for (let i = 0; i < 10; i++) {
                trailPoints.push(object.position.clone());
            }

            const trail = {
                object: object,
                points: trailPoints,
                color: color,
                line: null
            };

            trails.push(trail);
        }

        function updateTrails() {
            trails.forEach((trail, index) => {
                // Update points
                trail.points.shift();
                trail.points.push(trail.object.position.clone());

                // Remove old line
                if (trail.line) {
                    scene.remove(trail.line);
                }

                // Create new line
                const geometry = new THREE.BufferGeometry().setFromPoints(trail.points);
                const material = new THREE.LineBasicMaterial({
                    color: trail.color,
                    transparent: true,
                    opacity: 0.5
                });
                trail.line = new THREE.Line(geometry, material);
                scene.add(trail.line);

                // Clean up if object is removed
                if (!bullets.includes(trail.object) && trail.object !== playerShip) {
                    scene.remove(trail.line);
                    trails.splice(index, 1);
                }
            });
        }

        function createExplosion(position, large = false) {
            const particleCount = large ? 50 : 30;
            const particles = [];

            for (let i = 0; i < particleCount; i++) {
                const geometry = new THREE.SphereGeometry(large ? 0.2 : 0.1, 4, 4);
                const material = new THREE.MeshBasicMaterial({
                    color: Math.random() > 0.5 ? 0xff6600 : 0xffff00
                });
                const particle = new THREE.Mesh(geometry, material);

                particle.position.copy(position);
                particle.velocity = new THREE.Vector3(
                    (Math.random() - 0.5) * (large ? 0.8 : 0.5),
                    (Math.random() - 0.5) * (large ? 0.8 : 0.5),
                    (Math.random() - 0.5) * (large ? 0.8 : 0.5)
                );
                particle.life = large ? 50 : 30;

                scene.add(particle);
                particles.push(particle);
            }

            explosions.push({ particles, life: large ? 50 : 30 });
            audio.playExplosion();
        }

        // ==================== UPDATE ====================
        function update() {
            if (gameOver) return;

            // Player movement
            if ((keys['ArrowLeft'] || keys['KeyA']) && playerShip.position.x > -10) {
                playerShip.position.x -= PLAYER_SPEED;
                playerShip.rotation.z = 0.2;
            } else if ((keys['ArrowRight'] || keys['KeyD']) && playerShip.position.x < 10) {
                playerShip.position.x += PLAYER_SPEED;
                playerShip.rotation.z = -0.2;
            } else {
                playerShip.rotation.z *= 0.9;
            }

            if ((keys['ArrowUp'] || keys['KeyW']) && playerShip.position.y < 8) {
                playerShip.position.y += PLAYER_SPEED;
            }
            if ((keys['ArrowDown'] || keys['KeyS']) && playerShip.position.y > -8) {
                playerShip.position.y -= PLAYER_SPEED;
            }

            // Engine glow animation
            if (playerShip.engineGlow) {
                const pulse = Math.sin(frameCount * 0.1) * 0.2 + 0.8;
                playerShip.engineGlow.material.opacity = pulse;
                playerShip.engineGlow.scale.set(pulse, pulse, pulse);
            }

            // Shooting
            const fireRate = hasRapidFire ? 5 : 10;
            if (keys['Space']) {
                if (frameCount % fireRate === 0) {
                    createBullet();
                }
            }

            // Move bullets
            for (let i = bullets.length - 1; i >= 0; i--) {
                bullets[i].position.z -= BULLET_SPEED;
                bullets[i].rotation.y += 0.2;

                if (bullets[i].position.z < -30) {
                    scene.remove(bullets[i]);
                    bullets.splice(i, 1);
                }
            }

            // Move and update enemies
            for (let i = enemies.length - 1; i >= 0; i--) {
                enemies[i].position.z += enemies[i].speed;
                enemies[i].rotation.x += 0.02;
                enemies[i].rotation.y += 0.02;

                if (enemies[i].position.z > 20) {
                    scene.remove(enemies[i]);
                    enemies.splice(i, 1);
                    if (!hasShield) {
                        takeDamage(10);
                    }
                }
            }

            // Update boss
            if (boss) {
                // Boss movement pattern
                boss.position.x += boss.moveDirection * 0.1;
                if (Math.abs(boss.position.x) > 8) {
                    boss.moveDirection *= -1;
                }

                // Rotate rings
                if (boss.rings) {
                    boss.rings.forEach((ring, i) => {
                        ring.rotation.z += 0.02 * (i % 2 === 0 ? 1 : -1);
                    });
                }

                // Pulse core
                if (boss.core) {
                    const pulse = Math.sin(frameCount * 0.1) * 0.3 + 1;
                    boss.core.scale.set(pulse, pulse, pulse);
                }

                boss.rotation.y += 0.01;
            }

            // Move power-ups
            for (let i = powerups.length - 1; i >= 0; i--) {
                powerups[i].position.z += 0.2;
                powerups[i].rotation.y += powerups[i].rotationSpeed;
                if (powerups[i].ring) {
                    powerups[i].ring.rotation.x += 0.05;
                }

                // Bobbing motion
                powerups[i].position.y += Math.sin(frameCount * 0.05 + i) * 0.02;

                if (powerups[i].position.z > 20) {
                    scene.remove(powerups[i]);
                    powerups.splice(i, 1);
                }
            }

            // Collision detection
            checkCollisions();

            // Update explosions
            updateExplosions();

            // Update trails
            updateTrails();

            // Update power-up timers
            updatePowerUps();

            // Spawn enemies
            frameCount++;
            if (!boss && frameCount % SPAWN_RATE === 0) {
                createEnemy();
            }

            // Spawn power-ups
            if (frameCount % POWERUP_SPAWN_RATE === 0) {
                const x = (Math.random() - 0.5) * 15;
                const y = (Math.random() - 0.5) * 8;
                createPowerUp(new THREE.Vector3(x, y, -20));
            }

            // Check for boss wave
            if (wave % BOSS_WAVE === 0 && !boss && enemies.length === 0 && enemiesKilled >= wave * 10) {
                createBoss();
            }

            // Animate stars
            stars.forEach(star => {
                star.rotation.y += 0.0001;
            });

            // Camera shake on damage
            if (frameCount % 2 === 0 && health < 30) {
                camera.position.x += (Math.random() - 0.5) * 0.1;
                camera.position.y += (Math.random() - 0.5) * 0.1;
            }
        }

        function checkCollisions() {
            // Bullet-Enemy collision
            for (let i = bullets.length - 1; i >= 0; i--) {
                if (!bullets[i]) continue;

                for (let j = enemies.length - 1; j >= 0; j--) {
                    const distance = bullets[i].position.distanceTo(enemies[j].position);

                    if (distance < 1) {
                        createExplosion(enemies[j].position);

                        scene.remove(bullets[i]);
                        bullets.splice(i, 1);

                        scene.remove(enemies[j]);
                        enemies.splice(j, 1);

                        addScore(10 * wave);
                        enemiesKilled++;

                        // Chance to drop power-up
                        if (Math.random() < 0.15) {
                            createPowerUp(enemies[j].position);
                        }
                        break;
                    }
                }

                // Bullet-Boss collision
                if (boss && bullets[i]) {
                    const distance = bullets[i].position.distanceTo(boss.position);
                    if (distance < 4) {
                        boss.health -= 10;
                        updateBossHealth();
                        audio.playBossHit();

                        scene.remove(bullets[i]);
                        bullets.splice(i, 1);

                        // Small explosion on boss
                        const hitPos = boss.position.clone();
                        hitPos.x += (Math.random() - 0.5) * 3;
                        hitPos.y += (Math.random() - 0.5) * 3;
                        createExplosion(hitPos, false);

                        if (boss.health <= 0) {
                            defeatBoss();
                        }
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

                    if (!hasShield) {
                        takeDamage(20);
                    } else {
                        audio.playPowerUp();
                    }
                }
            }

            // Player-PowerUp collision
            for (let i = powerups.length - 1; i >= 0; i--) {
                const distance = playerShip.position.distanceTo(powerups[i].position);

                if (distance < 1.5) {
                    collectPowerUp(powerups[i].type);
                    scene.remove(powerups[i]);
                    powerups.splice(i, 1);
                }
            }

            // Player-Boss collision
            if (boss) {
                const distance = playerShip.position.distanceTo(boss.position);
                if (distance < 5) {
                    if (!hasShield) {
                        takeDamage(30);
                    }
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
                    particle.velocity.multiplyScalar(0.98);
                });

                if (explosion.life <= 0) {
                    explosion.particles.forEach(particle => scene.remove(particle));
                    explosions.splice(i, 1);
                }
            }
        }

        function updatePowerUps() {
            if (hasShield) {
                shieldTimer--;
                if (shieldTimer <= 0) {
                    hasShield = false;
                    document.getElementById('shield-indicator').classList.remove('active');
                }
            }

            if (hasRapidFire) {
                rapidFireTimer--;
                if (rapidFireTimer <= 0) {
                    hasRapidFire = false;
                    document.getElementById('rapidfire-indicator').classList.remove('active');
                }
            }
        }

        function collectPowerUp(type) {
            audio.playPowerUp();

            if (type === 'shield') {
                hasShield = true;
                shieldTimer = 600; // 10 seconds at 60fps
                document.getElementById('shield-indicator').classList.add('active');
            } else if (type === 'rapidfire') {
                hasRapidFire = true;
                rapidFireTimer = 600;
                document.getElementById('rapidfire-indicator').classList.add('active');
            }
        }

        function defeatBoss() {
            createExplosion(boss.position, true);
            scene.remove(boss);
            boss = null;

            addScore(1000 * wave);
            document.getElementById('boss-health-container').classList.remove('active');

            // Next wave
            wave++;
            document.getElementById('wave').textContent = wave;
            enemiesKilled = 0;
            showWaveNotification(`WAVE ${wave}`);
        }

        function updateBossHealth() {
            if (!boss) return;
            const percent = (boss.health / boss.maxHealth) * 100;
            document.getElementById('boss-health-fill').style.width = percent + '%';
            document.getElementById('boss-health-text').textContent = Math.max(0, boss.health);
        }

        function showWaveNotification(text) {
            const notification = document.createElement('div');
            notification.className = 'wave-notification';
            notification.textContent = text;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, 2000);
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

            audio.playDamage();

            // Screen flash
            renderer.domElement.style.filter = 'brightness(1.5)';
            setTimeout(() => {
                renderer.domElement.style.filter = 'brightness(1)';
            }, 100);

            if (health <= 0) {
                endGame();
            }
        }

        function endGame() {
            gameOver = true;
            document.getElementById('final-score').textContent = score;
            document.getElementById('final-wave').textContent = wave;
            document.getElementById('game-over').classList.add('show');
            loadLeaderboard();
        }

        function restartGame() {
            // Reset variables
            score = 0;
            health = 100;
            wave = 1;
            gameOver = false;
            frameCount = 0;
            enemiesKilled = 0;
            hasShield = false;
            hasRapidFire = false;
            shieldTimer = 0;
            rapidFireTimer = 0;

            // Clear arrays
            bullets.forEach(b => scene.remove(b));
            bullets = [];
            enemies.forEach(e => scene.remove(e));
            enemies = [];
            powerups.forEach(p => scene.remove(p));
            powerups = [];
            explosions.forEach(e => e.particles.forEach(p => scene.remove(p)));
            explosions = [];
            trails.forEach(t => { if (t.line) scene.remove(t.line); });
            trails = [];

            if (boss) {
                scene.remove(boss);
                boss = null;
            }

            // Reset UI
            document.getElementById('score').textContent = '0';
            document.getElementById('wave').textContent = '1';
            document.getElementById('health-fill').style.width = '100%';
            document.getElementById('health-text').textContent = '100';
            document.getElementById('game-over').classList.remove('show');
            document.getElementById('boss-health-container').classList.remove('active');
            document.getElementById('shield-indicator').classList.remove('active');
            document.getElementById('rapidfire-indicator').classList.remove('active');

            // Reset player position
            playerShip.position.set(0, 0, 10);
            playerShip.rotation.z = 0;

            // Reset camera
            camera.position.set(0, 5, 15);
        }

        // ==================== LEADERBOARD ====================
        function saveScore() {
            const name = document.getElementById('player-name').value.trim() || 'Anonymous';
            const leaderboard = JSON.parse(localStorage.getItem('spaceShooterLeaderboard') || '[]');

            leaderboard.push({
                name: name,
                score: score,
                wave: wave,
                date: new Date().toISOString()
            });

            // Sort by score
            leaderboard.sort((a, b) => b.score - a.score);

            // Keep top 10
            const top10 = leaderboard.slice(0, 10);
            localStorage.setItem('spaceShooterLeaderboard', JSON.stringify(top10));

            loadLeaderboard();
            document.getElementById('score-save-section').style.display = 'none';
        }

        function loadLeaderboard() {
            const leaderboard = JSON.parse(localStorage.getItem('spaceShooterLeaderboard') || '[]');
            const list = document.getElementById('leaderboard-list');

            if (leaderboard.length === 0) {
                list.innerHTML = '<p style="text-align: center; opacity: 0.5;">No scores yet. Be the first!</p>';
                return;
            }

            list.innerHTML = leaderboard.map((entry, index) => {
                const isCurrentScore = entry.score === score && !gameOver;
                return `
                    <div class="leaderboard-entry ${isCurrentScore ? 'highlight' : ''}">
                        <span>${index + 1}. ${entry.name}</span>
                        <span>${entry.score} pts (Wave ${entry.wave})</span>
                    </div>
                `;
            }).join('');
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
