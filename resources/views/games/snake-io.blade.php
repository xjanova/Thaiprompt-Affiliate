<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <title>Snake.io - Thai Prompt Games</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Noto+Sans+Thai:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            overflow: hidden;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            font-family: 'Orbitron', 'Noto Sans Thai', sans-serif;
            touch-action: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
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

        /* HUD */
        #hud {
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
            pointer-events: none;
            z-index: 10;
            color: white;
        }

        .hud-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .hud-item {
            background: rgba(0, 0, 0, 0.8);
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
        }

        .hud-value {
            font-size: 32px;
            font-weight: 900;
            color: #00ffff;
            text-shadow: 0 0 10px rgba(0, 255, 255, 0.8);
        }

        /* Leaderboard */
        .leaderboard {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(0, 255, 255, 0.5);
            border-radius: 10px;
            padding: 15px;
            min-width: 250px;
            max-height: 400px;
            overflow-y: auto;
        }

        .leaderboard h3 {
            color: #00ffff;
            margin-bottom: 10px;
            font-size: 18px;
        }

        .leaderboard-entry {
            padding: 8px;
            margin: 5px 0;
            background: rgba(0, 255, 255, 0.1);
            border-radius: 5px;
            font-size: 14px;
            display: flex;
            justify-content: space-between;
        }

        .leaderboard-entry.you {
            background: rgba(255, 255, 0, 0.2);
            border: 1px solid #ffff00;
        }

        /* Start Screen */
        #start-screen {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 100;
        }

        #start-screen.hidden {
            display: none;
        }

        #start-screen h1 {
            font-size: 72px;
            color: #00ffff;
            text-shadow: 0 0 30px rgba(0, 255, 255, 1);
            margin-bottom: 20px;
        }

        .name-input {
            background: rgba(0, 0, 0, 0.5);
            border: 2px solid #00ffff;
            color: #fff;
            padding: 15px 30px;
            border-radius: 10px;
            font-family: 'Orbitron', sans-serif;
            font-size: 18px;
            margin: 20px 0;
            text-align: center;
            min-width: 300px;
        }

        .btn {
            background: linear-gradient(135deg, #00ffff, #0080ff);
            border: none;
            color: #000;
            font-family: 'Orbitron', sans-serif;
            font-size: 24px;
            font-weight: 900;
            padding: 20px 60px;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 0 30px rgba(0, 255, 255, 0.5);
            margin: 10px;
        }

        .btn:hover {
            transform: scale(1.05);
            box-shadow: 0 0 50px rgba(0, 255, 255, 0.8);
        }

        /* Skin Selector */
        .skin-selector {
            display: flex;
            gap: 15px;
            margin: 20px 0;
            flex-wrap: wrap;
            justify-content: center;
        }

        .skin-option {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            cursor: pointer;
            border: 3px solid transparent;
            transition: all 0.3s ease;
            position: relative;
        }

        .skin-option:hover {
            transform: scale(1.1);
        }

        .skin-option.selected {
            border-color: #00ffff;
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.8);
        }

        .skin-option.locked {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .skin-price {
            position: absolute;
            bottom: -20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.8);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            color: #ffff00;
            white-space: nowrap;
        }

        /* Game Over */
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
            font-size: 48px;
            color: #ff0000;
            margin-bottom: 20px;
        }

        #game-over p {
            font-size: 20px;
            color: #fff;
            margin: 10px 0;
        }

        /* Controls Help */
        #controls {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.7);
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 14px;
            color: #fff;
            pointer-events: none;
            text-align: center;
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

        /* Mobile Controls Help */
        #controls-mobile {
            display: none;
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.7);
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 12px;
            color: #fff;
            pointer-events: none;
            text-align: center;
            max-width: 90%;
        }

        /* Responsive adjustments for mobile */
        @media (max-width: 768px) {
            #controls {
                display: none;
            }

            #controls-mobile {
                display: block;
            }

            .hud-item {
                padding: 10px 15px;
            }

            .hud-label {
                font-size: 10px;
            }

            .hud-value {
                font-size: 24px;
            }

            .leaderboard {
                min-width: 180px;
                max-height: 300px;
                padding: 10px;
            }

            .leaderboard h3 {
                font-size: 14px;
            }

            .leaderboard-entry {
                font-size: 12px;
                padding: 6px;
            }

            #start-screen h1 {
                font-size: 48px;
            }

            .name-input {
                font-size: 16px;
                min-width: 250px;
            }

            .btn {
                font-size: 20px;
                padding: 15px 40px;
            }

            .skin-option {
                width: 60px;
                height: 60px;
            }

            #sound-toggle {
                width: 45px;
                height: 45px;
                font-size: 20px;
            }
        }

        /* Sound Toggle Button */
        #sound-toggle {
            position: absolute;
            bottom: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid #00ffff;
            color: #00ffff;
            font-size: 24px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            z-index: 50;
            pointer-events: auto;
        }

        #sound-toggle:hover {
            background: rgba(0, 255, 255, 0.2);
            transform: scale(1.1);
        }

        #sound-toggle.muted {
            border-color: #ff4444;
            color: #ff4444;
        }

        /* Shop Button */
        #shop-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #ffaa00, #ff6600);
            border: none;
            color: #fff;
            font-family: 'Orbitron', sans-serif;
            font-size: 16px;
            font-weight: bold;
            padding: 12px 24px;
            border-radius: 25px;
            cursor: pointer;
            box-shadow: 0 0 20px rgba(255, 170, 0, 0.5);
            pointer-events: auto;
            z-index: 50;
        }

        #shop-btn:hover {
            transform: scale(1.05);
        }

        /* Minimap */
        #minimap {
            position: absolute;
            bottom: 20px;
            right: 20px;
            width: 200px;
            height: 200px;
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid rgba(0, 255, 255, 0.5);
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div id="game-container">
        <canvas id="game-canvas"></canvas>

        <!-- HUD -->
        <div id="hud">
            <div class="hud-top">
                <div class="hud-item">
                    <div class="hud-label">SCORE</div>
                    <div class="hud-value" id="score">0</div>
                </div>
                <div class="hud-item">
                    <div class="hud-label">LENGTH</div>
                    <div class="hud-value" id="length">1</div>
                </div>
                <div class="hud-item">
                    <div class="hud-label">RANK</div>
                    <div class="hud-value" id="rank">-</div>
                </div>
            </div>
        </div>

        <!-- Leaderboard -->
        <div class="leaderboard">
            <h3>🏆 TOP PLAYERS</h3>
            <div id="leaderboard-list"></div>
        </div>

        <!-- Start Screen -->
        <div id="start-screen">
            <h1>🐍 SNAKE.IO</h1>
            <p style="color: #ccc; font-size: 18px; margin-bottom: 30px;">
                กินอาหารให้มากที่สุด แต่อย่าชนอะไร!
            </p>

            <input type="text" id="player-name" class="name-input"
                   placeholder="{{ Auth::check() ? Auth::user()->name : 'Enter your name' }}"
                   maxlength="20">

            <div class="skin-selector">
                @php
                    $defaultSkins = [
                        ['slug' => 'classic', 'name' => 'Classic', 'color' => '#00ff00', 'price' => 0],
                        ['slug' => 'fire', 'name' => 'Fire', 'color' => '#ff4400', 'price' => 100],
                        ['slug' => 'ice', 'name' => 'Ice', 'color' => '#00aaff', 'price' => 100],
                        ['slug' => 'gold', 'name' => 'Gold', 'color' => '#ffd700', 'price' => 500],
                        ['slug' => 'rainbow', 'name' => 'Rainbow', 'color' => 'linear-gradient(90deg, #ff0000, #00ff00, #0000ff)', 'price' => 1000],
                    ];
                @endphp

                @foreach($defaultSkins as $skin)
                    <div class="skin-option {{ $skin['price'] > 0 && !Auth::check() ? 'locked' : '' }}"
                         data-skin="{{ $skin['slug'] }}"
                         style="background: {{ $skin['color'] }};"
                         title="{{ $skin['name'] }}">
                        @if($skin['price'] > 0)
                            <span class="skin-price">{{ $skin['price'] }} ฿</span>
                        @endif
                    </div>
                @endforeach
            </div>

            @guest
                <p style="color: #ffaa00; font-size: 14px; margin: 20px 0;">
                    💡 เข้าสู่ระบบเพื่อปลดล็อค skins พิเศษและบันทึกสกอร์!
                </p>
            @endguest

            <div>
                <button class="btn" id="start-btn">▶️ PLAY NOW</button>
            </div>

            @guest
                <div style="margin-top: 20px;">
                    <a href="{{ route('login') }}" style="color: #00ffff; text-decoration: none; margin: 0 10px;">เข้าสู่ระบบ</a>
                    |
                    <a href="{{ route('register') }}" style="color: #00ffff; text-decoration: none; margin: 0 10px;">สมัครสมาชิก</a>
                </div>
            @endguest
        </div>

        <!-- Game Over -->
        <div id="game-over">
            <h1>GAME OVER!</h1>
            <p>Final Score: <span id="final-score" style="color: #00ffff;">0</span></p>
            <p>Final Length: <span id="final-length" style="color: #ffff00;">0</span></p>
            <p>Final Rank: <span id="final-rank" style="color: #ff00ff;">#0</span></p>
            <div style="margin-top: 30px;">
                <button class="btn" id="restart-btn">🔄 PLAY AGAIN</button>
            </div>
        </div>

        <!-- Controls (Desktop) -->
        <div id="controls">
            <span class="key">↑</span> <span class="key">↓</span> <span class="key">←</span> <span class="key">→</span> or
            <span class="key">MOUSE</span> to move |
            <span class="key">SPACE</span> boost
        </div>

        <!-- Controls (Mobile) -->
        <div id="controls-mobile">
            <div style="color: #00ffff; margin-bottom: 5px;">👆 วาดนิ้วไปที่ต้องการ | 👆👆 แตะ 2 นิ้วเพื่อเร่ง</div>
            <div style="font-size: 10px; opacity: 0.7;">Swipe to move | 2 fingers to boost</div>
        </div>

        <!-- Sound Toggle -->
        <button id="sound-toggle" title="Toggle Sound">🔊</button>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>

    <script>
        // Game configuration
        const CONFIG = {
            WORLD_SIZE: 200,
            FOOD_COUNT: 100,
            BOT_COUNT: 10,
            INITIAL_LENGTH: 5,
            SEGMENT_SIZE: 0.5,
            MOVEMENT_SPEED: 0.15,
            BOOST_SPEED: 0.3,
            TURN_SPEED: 0.05,
            FOOD_VALUE: 1,
            COLLISION_DISTANCE: 0.6,
        };

        // Audio System (16-bit style)
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        let soundEnabled = true;

        // 16-bit sound generator
        function playSound(type) {
            if (!soundEnabled) return;

            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            const now = audioContext.currentTime;

            switch(type) {
                case 'eat':
                    // Food collection sound - happy beep
                    oscillator.frequency.setValueAtTime(800, now);
                    oscillator.frequency.exponentialRampToValueAtTime(1200, now + 0.05);
                    gainNode.gain.setValueAtTime(0.3, now);
                    gainNode.gain.exponentialRampToValueAtTime(0.01, now + 0.1);
                    oscillator.start(now);
                    oscillator.stop(now + 0.1);
                    break;

                case 'grow':
                    // Growth sound - power up
                    oscillator.frequency.setValueAtTime(400, now);
                    oscillator.frequency.exponentialRampToValueAtTime(800, now + 0.15);
                    gainNode.gain.setValueAtTime(0.2, now);
                    gainNode.gain.exponentialRampToValueAtTime(0.01, now + 0.15);
                    oscillator.start(now);
                    oscillator.stop(now + 0.15);
                    break;

                case 'die':
                    // Death sound - descending explosion
                    oscillator.type = 'sawtooth';
                    oscillator.frequency.setValueAtTime(500, now);
                    oscillator.frequency.exponentialRampToValueAtTime(100, now + 0.5);
                    gainNode.gain.setValueAtTime(0.5, now);
                    gainNode.gain.exponentialRampToValueAtTime(0.01, now + 0.5);
                    oscillator.start(now);
                    oscillator.stop(now + 0.5);
                    break;

                case 'boost':
                    // Boost sound - whoosh
                    oscillator.type = 'sawtooth';
                    oscillator.frequency.setValueAtTime(200, now);
                    oscillator.frequency.exponentialRampToValueAtTime(400, now + 0.1);
                    gainNode.gain.setValueAtTime(0.15, now);
                    gainNode.gain.exponentialRampToValueAtTime(0.01, now + 0.1);
                    oscillator.start(now);
                    oscillator.stop(now + 0.1);
                    break;

                case 'kill':
                    // Kill enemy sound - victory beep
                    oscillator.frequency.setValueAtTime(600, now);
                    oscillator.frequency.setValueAtTime(700, now + 0.05);
                    oscillator.frequency.setValueAtTime(800, now + 0.1);
                    gainNode.gain.setValueAtTime(0.25, now);
                    gainNode.gain.exponentialRampToValueAtTime(0.01, now + 0.2);
                    oscillator.start(now);
                    oscillator.stop(now + 0.2);
                    break;
            }
        }

        // Game state
        let scene, camera, renderer;
        let player;
        let foods = [];
        let bots = [];
        let gameStarted = false;
        let gameOver = false;
        let playerName = '';
        let selectedSkin = 'classic';
        let score = 0;
        let isAuthenticated = {{ Auth::check() ? 'true' : 'false' }};

        // Skin colors
        const SKINS = {
            'classic': { primary: 0x00ff00, secondary: 0x00aa00 },
            'fire': { primary: 0xff4400, secondary: 0xaa2200 },
            'ice': { primary: 0x00aaff, secondary: 0x0077cc },
            'gold': { primary: 0xffd700, secondary: 0xffaa00 },
            'rainbow': { primary: 0xff00ff, secondary: 0x00ffff }, // will animate
        };

        class Snake {
            constructor(x, z, isPlayer = false, name = 'Bot', skinKey = 'classic') {
                this.isPlayer = isPlayer;
                this.name = name;
                this.skinKey = skinKey;
                this.skin = SKINS[skinKey] || SKINS.classic;

                this.segments = [];
                this.segmentPositions = [];
                this.direction = new THREE.Vector3(1, 0, 0);
                this.targetDirection = this.direction.clone();
                this.speed = CONFIG.MOVEMENT_SPEED;
                this.isBoosting = false;
                this.length = CONFIG.INITIAL_LENGTH;
                this.score = 0;
                this.alive = true;

                // Create initial segments
                for (let i = 0; i < this.length; i++) {
                    const geometry = new THREE.SphereGeometry(CONFIG.SEGMENT_SIZE, 8, 8);
                    const material = new THREE.MeshPhongMaterial({
                        color: i === 0 ? this.skin.primary : this.skin.secondary,
                        emissive: i === 0 ? this.skin.primary : 0x000000,
                        emissiveIntensity: i === 0 ? 0.3 : 0,
                        shininess: 100,
                    });

                    const segment = new THREE.Mesh(geometry, material);
                    segment.position.set(x - i, 0.5, z);
                    segment.castShadow = true;

                    this.segments.push(segment);
                    this.segmentPositions.push(segment.position.clone());
                    scene.add(segment);
                }

                // Add name tag for players
                if (this.isPlayer || Math.random() > 0.5) {
                    this.createNameTag();
                }
            }

            createNameTag() {
                const canvas = document.createElement('canvas');
                canvas.width = 256;
                canvas.height = 64;
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = 'rgba(0, 0, 0, 0.7)';
                ctx.fillRect(0, 0, 256, 64);
                ctx.fillStyle = '#00ffff';
                ctx.font = 'bold 32px Orbitron';
                ctx.textAlign = 'center';
                ctx.fillText(this.name, 128, 42);

                const texture = new THREE.CanvasTexture(canvas);
                const material = new THREE.SpriteMaterial({ map: texture });
                this.nameSprite = new THREE.Sprite(material);
                this.nameSprite.scale.set(4, 1, 1);
                scene.add(this.nameSprite);
            }

            update() {
                if (!this.alive) return;

                // Update direction smoothly
                this.direction.lerp(this.targetDirection.normalize(), CONFIG.TURN_SPEED);

                // Move head
                const head = this.segments[0];
                const currentSpeed = this.isBoosting ? CONFIG.BOOST_SPEED : this.speed;

                head.position.x += this.direction.x * currentSpeed;
                head.position.z += this.direction.z * currentSpeed;

                // Wrap around world
                const halfWorld = CONFIG.WORLD_SIZE / 2;
                if (head.position.x > halfWorld) head.position.x = -halfWorld;
                if (head.position.x < -halfWorld) head.position.x = halfWorld;
                if (head.position.z > halfWorld) head.position.z = -halfWorld;
                if (head.position.z < -halfWorld) head.position.z = halfWorld;

                // Update segment positions
                this.segmentPositions[0] = head.position.clone();

                for (let i = 1; i < this.segments.length; i++) {
                    const target = this.segmentPositions[i - 1];
                    const current = this.segments[i].position;
                    const distance = current.distanceTo(target);

                    if (distance > CONFIG.SEGMENT_SIZE) {
                        const direction = new THREE.Vector3()
                            .subVectors(target, current)
                            .normalize();

                        current.add(direction.multiplyScalar(distance - CONFIG.SEGMENT_SIZE));
                    }

                    this.segmentPositions[i] = current.clone();
                }

                // Update name tag
                if (this.nameSprite) {
                    this.nameSprite.position.copy(head.position);
                    this.nameSprite.position.y = 2;
                }

                // Rainbow animation for rainbow skin
                if (this.skinKey === 'rainbow') {
                    const hue = (Date.now() * 0.001) % 1;
                    this.segments[0].material.color.setHSL(hue, 1, 0.5);
                }
            }

            grow(amount = 1) {
                for (let i = 0; i < amount; i++) {
                    const lastSegment = this.segments[this.segments.length - 1];
                    const geometry = new THREE.SphereGeometry(CONFIG.SEGMENT_SIZE, 8, 8);
                    const material = new THREE.MeshPhongMaterial({
                        color: this.skin.secondary,
                        shininess: 80,
                    });

                    const segment = new THREE.Mesh(geometry, material);
                    segment.position.copy(lastSegment.position);
                    segment.castShadow = true;

                    this.segments.push(segment);
                    this.segmentPositions.push(segment.position.clone());
                    scene.add(segment);
                }

                this.length += amount;
                this.score += amount * CONFIG.FOOD_VALUE;

                // Play eat sound
                if (this.isPlayer) {
                    playSound('eat');

                    // Play grow sound every 5 segments
                    if (this.length % 5 === 0) {
                        playSound('grow');
                    }
                }
            }

            checkSelfCollision() {
                // Self collision is disabled in .io games
                // Players should not die when hitting their own body
                return false;
            }

            die() {
                this.alive = false;

                // Play death sound
                if (this.isPlayer) {
                    playSound('die');
                }

                // Convert segments to food
                this.segments.forEach(segment => {
                    createFood(segment.position.x, segment.position.z, true);
                    scene.remove(segment);
                });

                if (this.nameSprite) {
                    scene.remove(this.nameSprite);
                }

                // Remove from arrays
                if (this.isPlayer) {
                    endGame();
                } else {
                    const index = bots.indexOf(this);
                    if (index > -1) bots.splice(index, 1);
                }
            }
        }

        function init() {
            // Scene
            scene = new THREE.Scene();
            scene.fog = new THREE.Fog(0x16213e, 50, CONFIG.WORLD_SIZE);

            // Camera
            camera = new THREE.PerspectiveCamera(
                60,
                window.innerWidth / window.innerHeight,
                0.1,
                CONFIG.WORLD_SIZE * 2
            );
            camera.position.set(0, 40, 40);
            camera.lookAt(0, 0, 0);

            // Renderer
            const canvas = document.getElementById('game-canvas');
            renderer = new THREE.WebGLRenderer({ canvas, antialias: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.shadowMap.enabled = true;
            renderer.setPixelRatio(window.devicePixelRatio);

            // Lights
            const ambientLight = new THREE.AmbientLight(0x404080, 0.6);
            scene.add(ambientLight);

            const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
            directionalLight.position.set(50, 50, 50);
            directionalLight.castShadow = true;
            scene.add(directionalLight);

            // Ground
            const groundGeometry = new THREE.PlaneGeometry(CONFIG.WORLD_SIZE, CONFIG.WORLD_SIZE);
            const groundMaterial = new THREE.MeshPhongMaterial({
                color: 0x0a2239,
                shininess: 30,
            });
            const ground = new THREE.Mesh(groundGeometry, groundMaterial);
            ground.rotation.x = -Math.PI / 2;
            ground.receiveShadow = true;
            scene.add(ground);

            // Grid
            const gridHelper = new THREE.GridHelper(CONFIG.WORLD_SIZE, 50, 0x00ffff, 0x004466);
            gridHelper.material.opacity = 0.2;
            gridHelper.material.transparent = true;
            scene.add(gridHelper);

            // Spawn food
            for (let i = 0; i < CONFIG.FOOD_COUNT; i++) {
                createFood();
            }

            // Event listeners
            window.addEventListener('resize', onWindowResize);
            document.addEventListener('keydown', onKeyDown);
            document.addEventListener('keyup', onKeyUp);
            document.addEventListener('mousemove', onMouseMove);

            // Touch event listeners for mobile/tablet
            const canvas = document.getElementById('game-canvas');
            canvas.addEventListener('touchstart', onTouchStart, { passive: false });
            canvas.addEventListener('touchmove', onTouchMove, { passive: false });
            canvas.addEventListener('touchend', onTouchEnd, { passive: false });

            // Prevent default touch behavior on the entire document
            document.addEventListener('touchmove', function(e) {
                if (gameStarted) {
                    e.preventDefault();
                }
            }, { passive: false });

            // UI Events
            document.getElementById('start-btn').addEventListener('click', startGame);
            document.getElementById('restart-btn').addEventListener('click', restartGame);

            // Sound toggle
            const soundToggle = document.getElementById('sound-toggle');
            soundToggle.addEventListener('click', function() {
                soundEnabled = !soundEnabled;
                this.textContent = soundEnabled ? '🔊' : '🔇';
                this.classList.toggle('muted', !soundEnabled);
            });

            // Skin selection
            document.querySelectorAll('.skin-option').forEach(option => {
                option.addEventListener('click', function() {
                    if (!this.classList.contains('locked')) {
                        document.querySelectorAll('.skin-option').forEach(o => o.classList.remove('selected'));
                        this.classList.add('selected');
                        selectedSkin = this.dataset.skin;
                    }
                });
            });

            // Select default skin
            document.querySelector('.skin-option').classList.add('selected');

            // Start animation loop
            animate();
        }

        function createFood(x = null, z = null, fromDeath = false) {
            const geometry = new THREE.SphereGeometry(0.3, 6, 6);
            const color = fromDeath ? 0xffffff :
                          (Math.random() > 0.7 ? 0xffff00 : 0xff00ff);
            const material = new THREE.MeshPhongMaterial({
                color: color,
                emissive: color,
                emissiveIntensity: 0.5,
            });

            const food = new THREE.Mesh(geometry, material);

            if (x === null || z === null) {
                const halfWorld = CONFIG.WORLD_SIZE / 2 - 10;
                x = (Math.random() - 0.5) * halfWorld * 2;
                z = (Math.random() - 0.5) * halfWorld * 2;
            }

            food.position.set(x, 0.3, z);
            food.userData.value = fromDeath ? 2 : 1;

            scene.add(food);
            foods.push(food);
        }

        function createBot() {
            const halfWorld = CONFIG.WORLD_SIZE / 2 - 20;
            const x = (Math.random() - 0.5) * halfWorld * 2;
            const z = (Math.random() - 0.5) * halfWorld * 2;

            const names = ['Alpha', 'Beta', 'Gamma', 'Delta', 'Epsilon', 'Zeta', 'Eta', 'Theta'];
            const name = names[Math.floor(Math.random() * names.length)] + Math.floor(Math.random() * 100);
            const skinKeys = Object.keys(SKINS);
            const skin = skinKeys[Math.floor(Math.random() * skinKeys.length)];

            const bot = new Snake(x, z, false, name, skin);
            bots.push(bot);
        }

        /**
         * Check collision between two snakes
         * Returns the snake that should die, or null if no collision
         */
        function checkSnakeCollision(snake1, snake2) {
            if (!snake1.alive || !snake2.alive) return null;

            const head1 = snake1.segments[0].position;
            const head2 = snake2.segments[0].position;

            // Check head-to-head collision
            const headToHeadDistance = head1.distanceTo(head2);
            if (headToHeadDistance < CONFIG.COLLISION_DISTANCE * 1.5) {
                // Head-to-head collision
                // Determine winner based on:
                // 1. Size (longer snake wins)
                // 2. If same size, boosting snake wins
                // 3. If both boosting or both not, both die (return both)

                if (snake1.length > snake2.length) {
                    return snake2; // snake1 wins
                } else if (snake2.length > snake1.length) {
                    return snake1; // snake2 wins
                } else {
                    // Same length - check boost
                    if (snake1.isBoosting && !snake2.isBoosting) {
                        return snake2; // boosting snake wins
                    } else if (snake2.isBoosting && !snake1.isBoosting) {
                        return snake1; // boosting snake wins
                    } else {
                        // Both equal - smaller one dies (or random)
                        return snake1.score <= snake2.score ? snake1 : snake2;
                    }
                }
            }

            // Check if snake1's head hits snake2's body
            for (let i = 1; i < snake2.segments.length; i++) {
                const distance = head1.distanceTo(snake2.segments[i].position);
                if (distance < CONFIG.COLLISION_DISTANCE) {
                    return snake1; // snake1 dies (hit snake2's body)
                }
            }

            // Check if snake2's head hits snake1's body
            for (let i = 1; i < snake1.segments.length; i++) {
                const distance = head2.distanceTo(snake1.segments[i].position);
                if (distance < CONFIG.COLLISION_DISTANCE) {
                    return snake2; // snake2 dies (hit snake1's body)
                }
            }

            return null; // No collision
        }

        /**
         * Check all snake collisions and handle deaths
         */
        function checkAllSnakeCollisions() {
            if (!player || !player.alive) return;

            // Check player vs all bots
            for (let i = bots.length - 1; i >= 0; i--) {
                const bot = bots[i];
                if (!bot.alive) continue;

                const victim = checkSnakeCollision(player, bot);
                if (victim) {
                    if (victim === bot) {
                        // Player killed a bot!
                        playSound('kill');
                    }
                    victim.die();
                    if (victim === bot) {
                        createBot(); // Respawn bot
                    }
                    return; // Only one death per frame
                }
            }

            // Check bot vs bot collisions
            for (let i = bots.length - 1; i >= 0; i--) {
                const bot1 = bots[i];
                if (!bot1.alive) continue;

                for (let j = i - 1; j >= 0; j--) {
                    const bot2 = bots[j];
                    if (!bot2.alive) continue;

                    const victim = checkSnakeCollision(bot1, bot2);
                    if (victim) {
                        victim.die();
                        createBot(); // Respawn
                        return; // Only one death per frame
                    }
                }
            }
        }

        function startGame() {
            playerName = document.getElementById('player-name').value.trim() ||
                         (isAuthenticated ? '{{ Auth::user()->name ?? "Player" }}' : 'Player');

            // Create player
            player = new Snake(0, 0, true, playerName, selectedSkin);

            // Spawn bots
            for (let i = 0; i < CONFIG.BOT_COUNT; i++) {
                createBot();
            }

            gameStarted = true;
            document.getElementById('start-screen').classList.add('hidden');
        }

        function endGame() {
            gameOver = true;
            gameStarted = false;

            document.getElementById('final-score').textContent = score;
            document.getElementById('final-length').textContent = player.length;
            document.getElementById('final-rank').textContent = '#' + getRank();
            document.getElementById('game-over').classList.add('show');

            // Save score if authenticated
            if (isAuthenticated) {
                saveScore();
            }
        }

        function restartGame() {
            // Clean up
            if (player) player.die();
            bots.forEach(bot => {
                bot.segments.forEach(seg => scene.remove(seg));
                if (bot.nameSprite) scene.remove(bot.nameSprite);
            });
            bots = [];

            foods.forEach(food => scene.remove(food));
            foods = [];

            // Reset
            score = 0;
            gameOver = false;
            document.getElementById('game-over').classList.remove('show');
            document.getElementById('start-screen').classList.remove('hidden');

            // Respawn food
            for (let i = 0; i < CONFIG.FOOD_COUNT; i++) {
                createFood();
            }
        }

        function saveScore() {
            fetch('/games/snake-io/save-progress', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    score: score,
                    wave: 1,
                    kills: 0,
                    bosses: 0,
                    playtime: Math.floor(Date.now() / 1000),
                }),
            });
        }

        function getRank() {
            const allSnakes = [player, ...bots].filter(s => s.alive);
            allSnakes.sort((a, b) => b.score - a.score);
            return allSnakes.indexOf(player) + 1;
        }

        function updateLeaderboard() {
            const allSnakes = [player, ...bots].filter(s => s && s.alive);
            allSnakes.sort((a, b) => b.score - a.score);

            const list = document.getElementById('leaderboard-list');
            list.innerHTML = allSnakes.slice(0, 10).map((snake, index) => `
                <div class="leaderboard-entry ${snake.isPlayer ? 'you' : ''}">
                    <span>${index + 1}. ${snake.name}</span>
                    <span>${snake.score}</span>
                </div>
            `).join('');
        }

        function updateBots() {
            bots.forEach(bot => {
                // Simple AI: move towards nearest food
                const head = bot.segments[0].position;
                let nearestFood = null;
                let nearestDistance = Infinity;

                foods.forEach(food => {
                    const distance = head.distanceTo(food.position);
                    if (distance < nearestDistance) {
                        nearestDistance = distance;
                        nearestFood = food;
                    }
                });

                if (nearestFood) {
                    const direction = new THREE.Vector3()
                        .subVectors(nearestFood.position, head)
                        .normalize();
                    bot.targetDirection.copy(direction);
                }

                bot.update();

                // Check food collision
                for (let i = foods.length - 1; i >= 0; i--) {
                    const food = foods[i];
                    if (head.distanceTo(food.position) < 1) {
                        bot.grow(food.userData.value);
                        scene.remove(food);
                        foods.splice(i, 1);
                        createFood();
                        break;
                    }
                }
            });
        }

        function update() {
            if (!gameStarted || gameOver) return;

            if (player && player.alive) {
                player.update();

                // Update camera to follow player
                const head = player.segments[0].position;
                camera.position.x = head.x;
                camera.position.z = head.z + 30;
                camera.lookAt(head.x, 0, head.z);

                // Update UI
                score = player.score;
                document.getElementById('score').textContent = score;
                document.getElementById('length').textContent = player.length;
                document.getElementById('rank').textContent = '#' + getRank();

                // Check food collision
                for (let i = foods.length - 1; i >= 0; i--) {
                    const food = foods[i];
                    if (head.distanceTo(food.position) < 1) {
                        player.grow(food.userData.value);
                        scene.remove(food);
                        foods.splice(i, 1);
                        createFood();
                        break;
                    }
                }
            }

            updateBots();

            // Check all snake-to-snake collisions
            checkAllSnakeCollisions();

            updateLeaderboard();

            // Maintain food count
            while (foods.length < CONFIG.FOOD_COUNT) {
                createFood();
            }

            // Maintain bot count
            while (bots.length < CONFIG.BOT_COUNT) {
                createBot();
            }
        }

        function animate() {
            requestAnimationFrame(animate);
            update();
            renderer.render(scene, camera);
        }

        function onWindowResize() {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        }

        let keys = {};
        let raycaster = new THREE.Raycaster();
        let mouseVector = new THREE.Vector2();

        function onKeyDown(e) {
            keys[e.code] = true;

            if (!player || !player.alive) return;

            if (e.code === 'Space') {
                if (!player.isBoosting) {
                    player.isBoosting = true;
                    playSound('boost');
                }
            }

            const directions = {
                'ArrowUp': new THREE.Vector3(0, 0, -1),
                'ArrowDown': new THREE.Vector3(0, 0, 1),
                'ArrowLeft': new THREE.Vector3(-1, 0, 0),
                'ArrowRight': new THREE.Vector3(1, 0, 0),
                'KeyW': new THREE.Vector3(0, 0, -1),
                'KeyS': new THREE.Vector3(0, 0, 1),
                'KeyA': new THREE.Vector3(-1, 0, 0),
                'KeyD': new THREE.Vector3(1, 0, 0),
            };

            if (directions[e.code]) {
                player.targetDirection.copy(directions[e.code]);
            }
        }

        function onKeyUp(e) {
            keys[e.code] = false;

            if (e.code === 'Space' && player) {
                player.isBoosting = false;
            }
        }

        function onMouseMove(e) {
            if (!player || !player.alive) return;

            // Convert mouse position to normalized device coordinates (-1 to +1)
            mouseVector.x = (e.clientX / window.innerWidth) * 2 - 1;
            mouseVector.y = -(e.clientY / window.innerHeight) * 2 + 1;

            // Use raycaster to find where mouse points on the ground plane
            raycaster.setFromCamera(mouseVector, camera);

            // Create a plane at y=0 (ground level)
            const groundPlane = new THREE.Plane(new THREE.Vector3(0, 1, 0), 0);
            const intersectPoint = new THREE.Vector3();

            // Find intersection point
            raycaster.ray.intersectPlane(groundPlane, intersectPoint);

            if (intersectPoint) {
                // Calculate direction from snake head to mouse position
                const head = player.segments[0].position;
                const direction = new THREE.Vector3()
                    .subVectors(intersectPoint, head)
                    .normalize();

                // Only update if direction is valid
                if (direction.length() > 0) {
                    player.targetDirection.copy(direction);
                }
            }
        }

        // Touch event handlers for mobile/tablet support
        function updateDirectionFromTouch(clientX, clientY) {
            if (!player || !player.alive) return;

            // Convert touch position to normalized device coordinates
            mouseVector.x = (clientX / window.innerWidth) * 2 - 1;
            mouseVector.y = -(clientY / window.innerHeight) * 2 + 1;

            // Use raycaster to find where touch points on the ground plane
            raycaster.setFromCamera(mouseVector, camera);

            const groundPlane = new THREE.Plane(new THREE.Vector3(0, 1, 0), 0);
            const intersectPoint = new THREE.Vector3();

            raycaster.ray.intersectPlane(groundPlane, intersectPoint);

            if (intersectPoint) {
                const head = player.segments[0].position;
                const direction = new THREE.Vector3()
                    .subVectors(intersectPoint, head)
                    .normalize();

                if (direction.length() > 0) {
                    player.targetDirection.copy(direction);
                }
            }
        }

        function onTouchStart(e) {
            e.preventDefault();

            if (e.touches.length > 0) {
                const touch = e.touches[0];
                updateDirectionFromTouch(touch.clientX, touch.clientY);
            }

            // Enable boost on double tap
            if (player && player.alive && e.touches.length === 2) {
                if (!player.isBoosting) {
                    player.isBoosting = true;
                    playSound('boost');
                }
            }
        }

        function onTouchMove(e) {
            e.preventDefault();

            if (e.touches.length > 0) {
                const touch = e.touches[0];
                updateDirectionFromTouch(touch.clientX, touch.clientY);
            }
        }

        function onTouchEnd(e) {
            e.preventDefault();

            // Stop boosting when lifting fingers
            if (player && e.touches.length < 2) {
                player.isBoosting = false;
            }
        }

        init();
    </script>
</body>
</html>
