<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Space Shooter Demo - Thai Prompt</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;600;700;900&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Noto Sans Thai', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #000;
            color: white;
            overflow: hidden;
            min-height: 100vh;
        }

        #gameCanvas {
            display: block;
            background: linear-gradient(180deg, #000033 0%, #000000 100%);
        }

        .ui-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 10;
        }

        .header {
            text-align: center;
            margin: 20px;
            padding: 20px;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .header h1 {
            font-size: 2rem;
            font-weight: 900;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .score-display {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 15px 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 1.5rem;
            font-weight: bold;
        }

        .controls {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 15px 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .controls p {
            margin: 5px 0;
            font-size: 0.9rem;
        }

        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            pointer-events: auto;
            z-index: 20;
        }

        .back-button:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .game-over {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.9);
            padding: 40px;
            border-radius: 20px;
            border: 2px solid #667eea;
            text-align: center;
            pointer-events: auto;
        }

        .game-over h2 {
            font-size: 2.5rem;
            color: #667eea;
            margin-bottom: 20px;
        }

        .restart-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 10px;
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
            transition: transform 0.3s ease;
        }

        .restart-button:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <canvas id="gameCanvas"></canvas>

    <div class="ui-overlay">
        <a href="{{ route('demo.game-selector') }}" class="back-button">← กลับ</a>

        <div class="score-display">
            🎯 คะแนน: <span id="score">0</span>
        </div>

        <div class="controls">
            <p>← → เคลื่อนที่ซ้าย-ขวา</p>
            <p>Space หรือ Click เพื่อยิง</p>
        </div>

        <div class="game-over" id="gameOver">
            <h2>🎮 Game Over!</h2>
            <p style="font-size: 1.5rem;">คะแนนสุดท้าย: <span id="finalScore">0</span></p>
            <button class="restart-button" onclick="restartGame()">🔄 เล่นอีกครั้ง</button>
        </div>
    </div>

    <script>
        const canvas = document.getElementById('gameCanvas');
        const ctx = canvas.getContext('2d');

        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;

        let score = 0;
        let gameActive = true;

        // Player
        const player = {
            x: canvas.width / 2,
            y: canvas.height - 100,
            width: 40,
            height: 40,
            speed: 5,
            color: '#667eea'
        };

        // Bullets
        const bullets = [];
        const bulletSpeed = 7;
        let lastShot = 0;
        const shootDelay = 250;

        // Enemies
        const enemies = [];
        const enemySpeed = 2;
        let enemySpawnRate = 60;
        let frameCount = 0;

        // Stars background
        const stars = [];
        for (let i = 0; i < 100; i++) {
            stars.push({
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height,
                size: Math.random() * 2,
                speed: Math.random() * 2 + 1
            });
        }

        // Input handling
        const keys = {};
        document.addEventListener('keydown', (e) => {
            keys[e.key] = true;
            if (e.key === ' ' && gameActive) {
                e.preventDefault();
                shoot();
            }
        });
        document.addEventListener('keyup', (e) => keys[e.key] = false);
        document.addEventListener('click', (e) => {
            if (gameActive && e.target === canvas) shoot();
        });

        function shoot() {
            const now = Date.now();
            if (now - lastShot > shootDelay) {
                bullets.push({
                    x: player.x + player.width / 2 - 2,
                    y: player.y,
                    width: 4,
                    height: 15
                });
                lastShot = now;
            }
        }

        function spawnEnemy() {
            enemies.push({
                x: Math.random() * (canvas.width - 30),
                y: -30,
                width: 30,
                height: 30,
                color: `hsl(${Math.random() * 360}, 70%, 50%)`
            });
        }

        function drawPlayer() {
            ctx.fillStyle = player.color;
            ctx.beginPath();
            ctx.moveTo(player.x + player.width / 2, player.y);
            ctx.lineTo(player.x, player.y + player.height);
            ctx.lineTo(player.x + player.width, player.y + player.height);
            ctx.closePath();
            ctx.fill();
        }

        function drawBullets() {
            ctx.fillStyle = '#ffff00';
            bullets.forEach(bullet => {
                ctx.fillRect(bullet.x, bullet.y, bullet.width, bullet.height);
            });
        }

        function drawEnemies() {
            enemies.forEach(enemy => {
                ctx.fillStyle = enemy.color;
                ctx.fillRect(enemy.x, enemy.y, enemy.width, enemy.height);
            });
        }

        function drawStars() {
            ctx.fillStyle = '#ffffff';
            stars.forEach(star => {
                ctx.beginPath();
                ctx.arc(star.x, star.y, star.size, 0, Math.PI * 2);
                ctx.fill();
            });
        }

        function update() {
            if (!gameActive) return;

            // Move player
            if (keys['ArrowLeft'] || keys['a']) player.x -= player.speed;
            if (keys['ArrowRight'] || keys['d']) player.x += player.speed;
            player.x = Math.max(0, Math.min(canvas.width - player.width, player.x));

            // Move stars
            stars.forEach(star => {
                star.y += star.speed;
                if (star.y > canvas.height) {
                    star.y = 0;
                    star.x = Math.random() * canvas.width;
                }
            });

            // Move bullets
            for (let i = bullets.length - 1; i >= 0; i--) {
                bullets[i].y -= bulletSpeed;
                if (bullets[i].y < 0) bullets.splice(i, 1);
            }

            // Move enemies
            for (let i = enemies.length - 1; i >= 0; i--) {
                enemies[i].y += enemySpeed;

                // Check if enemy reached bottom
                if (enemies[i].y > canvas.height) {
                    enemies.splice(i, 1);
                    gameActive = false;
                    showGameOver();
                    continue;
                }

                // Check collision with bullets
                for (let j = bullets.length - 1; j >= 0; j--) {
                    if (bullets[j].x < enemies[i].x + enemies[i].width &&
                        bullets[j].x + bullets[j].width > enemies[i].x &&
                        bullets[j].y < enemies[i].y + enemies[i].height &&
                        bullets[j].y + bullets[j].height > enemies[i].y) {

                        enemies.splice(i, 1);
                        bullets.splice(j, 1);
                        score += 10;
                        document.getElementById('score').textContent = score;
                        break;
                    }
                }
            }

            // Spawn enemies
            frameCount++;
            if (frameCount % enemySpawnRate === 0) {
                spawnEnemy();
                if (enemySpawnRate > 30) enemySpawnRate--;
            }
        }

        function draw() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            drawStars();
            drawPlayer();
            drawBullets();
            drawEnemies();
        }

        function gameLoop() {
            update();
            draw();
            requestAnimationFrame(gameLoop);
        }

        function showGameOver() {
            document.getElementById('finalScore').textContent = score;
            document.getElementById('gameOver').style.display = 'block';
        }

        function restartGame() {
            score = 0;
            gameActive = true;
            bullets.length = 0;
            enemies.length = 0;
            enemySpawnRate = 60;
            frameCount = 0;
            player.x = canvas.width / 2;
            document.getElementById('score').textContent = '0';
            document.getElementById('gameOver').style.display = 'none';
        }

        window.addEventListener('resize', () => {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            player.x = canvas.width / 2;
        });

        gameLoop();
    </script>
</body>
</html>
