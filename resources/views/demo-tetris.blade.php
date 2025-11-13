<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tetris Game - เกมส์เตตริสเท่ห์ๆ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;700&family=Press+Start+2P&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Noto Sans Thai', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            overflow: hidden;
            position: relative;
        }

        /* Animated background */
        body::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background:
                linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.05) 30%, rgba(255, 255, 255, 0.05) 70%, transparent 70%),
                linear-gradient(-45deg, transparent 30%, rgba(255, 255, 255, 0.05) 30%, rgba(255, 255, 255, 0.05) 70%, transparent 70%);
            background-size: 100px 100px;
            animation: moveBackground 20s linear infinite;
            z-index: 0;
        }

        @keyframes moveBackground {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        .game-container {
            position: relative;
            z-index: 1;
            display: flex;
            gap: 30px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .left-panel {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .game-board {
            position: relative;
        }

        #tetris-canvas {
            border: 3px solid rgba(255, 255, 255, 0.5);
            border-radius: 10px;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.5);
            display: block;
            background: rgba(0, 0, 0, 0.8);
        }

        .controls-info {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(5px);
            padding: 15px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .controls-info h3 {
            color: #fff;
            font-size: 14px;
            margin-bottom: 10px;
            text-align: center;
            font-weight: 700;
        }

        .control-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 8px 0;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.9);
        }

        .key {
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            font-family: 'Press Start 2P', monospace;
            font-size: 10px;
        }

        .right-panel {
            display: flex;
            flex-direction: column;
            gap: 20px;
            min-width: 200px;
        }

        .info-box {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(5px);
            padding: 20px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .info-box h2 {
            color: #fff;
            font-size: 16px;
            margin-bottom: 15px;
            text-align: center;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .stat-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 10px 0;
            padding: 10px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 5px;
        }

        .stat-label {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
        }

        .stat-value {
            color: #fff;
            font-size: 20px;
            font-weight: 700;
            font-family: 'Press Start 2P', monospace;
        }

        .next-piece-box {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(5px);
            padding: 20px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .next-piece-box h2 {
            color: #fff;
            font-size: 16px;
            margin-bottom: 15px;
            text-align: center;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        #next-canvas {
            display: block;
            margin: 0 auto;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 5px;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .high-score-box {
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.2), rgba(255, 165, 0, 0.2));
            backdrop-filter: blur(5px);
            padding: 20px;
            border-radius: 10px;
            border: 2px solid rgba(255, 215, 0, 0.5);
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 10px rgba(255, 215, 0, 0.3); }
            50% { box-shadow: 0 0 20px rgba(255, 215, 0, 0.6); }
        }

        .high-score-box h2 {
            color: #ffd700;
            font-size: 16px;
            margin-bottom: 15px;
            text-align: center;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .high-score-value {
            color: #ffd700;
            font-size: 28px;
            font-weight: 700;
            font-family: 'Press Start 2P', monospace;
            text-align: center;
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
        }

        /* Game Over Overlay */
        #game-over-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .game-over-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            border: 3px solid rgba(255, 255, 255, 0.3);
            max-width: 400px;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .game-over-content h1 {
            color: #fff;
            font-size: 48px;
            font-family: 'Press Start 2P', monospace;
            margin-bottom: 20px;
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.5);
        }

        .game-over-stats {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }

        .game-over-stat {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            color: #fff;
            font-size: 18px;
        }

        .game-over-stat .label {
            color: rgba(255, 255, 255, 0.8);
        }

        .game-over-stat .value {
            font-weight: 700;
            font-family: 'Press Start 2P', monospace;
        }

        .new-high-score {
            color: #ffd700;
            font-size: 24px;
            font-weight: 700;
            margin: 20px 0;
            animation: glow 1s ease-in-out infinite;
        }

        @keyframes glow {
            0%, 100% { text-shadow: 0 0 10px rgba(255, 215, 0, 0.5); }
            50% { text-shadow: 0 0 20px rgba(255, 215, 0, 1); }
        }

        .btn {
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            border: 2px solid rgba(255, 255, 255, 0.5);
            padding: 15px 30px;
            font-size: 18px;
            font-weight: 700;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Noto Sans Thai', sans-serif;
            margin: 5px;
        }

        .btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: 2px solid #fff;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }

        /* Pause Overlay */
        #pause-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 999;
        }

        .pause-content {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .pause-content h2 {
            color: #fff;
            font-size: 48px;
            font-family: 'Press Start 2P', monospace;
            margin-bottom: 20px;
        }

        .pause-content p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 18px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .game-container {
                flex-direction: column;
                padding: 20px;
            }

            .right-panel {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="game-container">
        <div class="left-panel">
            <div class="game-board">
                <canvas id="tetris-canvas" width="300" height="600"></canvas>
            </div>
            <div class="controls-info">
                <h3>🎮 การควบคุม</h3>
                <div class="control-row">
                    <span>ซ้าย/ขวา:</span>
                    <span><span class="key">←</span> <span class="key">→</span></span>
                </div>
                <div class="control-row">
                    <span>หมุน:</span>
                    <span><span class="key">↑</span></span>
                </div>
                <div class="control-row">
                    <span>ตกเร็ว:</span>
                    <span><span class="key">↓</span></span>
                </div>
                <div class="control-row">
                    <span>ตกทันที:</span>
                    <span><span class="key">SPACE</span></span>
                </div>
                <div class="control-row">
                    <span>หยุดชั่วคราว:</span>
                    <span><span class="key">P</span></span>
                </div>
            </div>
        </div>

        <div class="right-panel">
            <div class="info-box">
                <h2>📊 สถิติ</h2>
                <div class="stat-row">
                    <span class="stat-label">คะแนน:</span>
                    <span class="stat-value" id="score">0</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">ระดับ:</span>
                    <span class="stat-value" id="level">1</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">เส้น:</span>
                    <span class="stat-value" id="lines">0</span>
                </div>
            </div>

            <div class="next-piece-box">
                <h2>⏭️ ชิ้นถัดไป</h2>
                <canvas id="next-canvas" width="120" height="120"></canvas>
            </div>

            <div class="high-score-box">
                <h2>🏆 สถิติสูงสุด</h2>
                <div class="high-score-value" id="high-score">0</div>
            </div>
        </div>
    </div>

    <!-- Pause Overlay -->
    <div id="pause-overlay">
        <div class="pause-content">
            <h2>PAUSE</h2>
            <p>กด P เพื่อเล่นต่อ</p>
        </div>
    </div>

    <!-- Game Over Overlay -->
    <div id="game-over-overlay">
        <div class="game-over-content">
            <h1>GAME OVER</h1>
            <div class="game-over-stats">
                <div class="game-over-stat">
                    <span class="label">คะแนนของคุณ:</span>
                    <span class="value" id="final-score">0</span>
                </div>
                <div class="game-over-stat">
                    <span class="label">ระดับ:</span>
                    <span class="value" id="final-level">1</span>
                </div>
                <div class="game-over-stat">
                    <span class="label">เส้นทั้งหมด:</span>
                    <span class="value" id="final-lines">0</span>
                </div>
            </div>
            <div id="new-high-score-message" class="new-high-score" style="display: none;">
                🎉 สถิติใหม่! 🎉
            </div>
            <button class="btn btn-primary" onclick="restartGame()">เล่นอีกครั้ง</button>
            <button class="btn" onclick="location.href='/'">กลับหน้าหลัก</button>
        </div>
    </div>

    <script>
        // Game Configuration
        const COLS = 10;
        const ROWS = 20;
        const BLOCK_SIZE = 30;
        const COLORS = {
            I: '#00f0f0',
            O: '#f0f000',
            T: '#a000f0',
            S: '#00f000',
            Z: '#f00000',
            J: '#0000f0',
            L: '#f0a000'
        };

        // Tetromino Shapes
        const SHAPES = {
            I: [[1, 1, 1, 1]],
            O: [[1, 1], [1, 1]],
            T: [[0, 1, 0], [1, 1, 1]],
            S: [[0, 1, 1], [1, 1, 0]],
            Z: [[1, 1, 0], [0, 1, 1]],
            J: [[1, 0, 0], [1, 1, 1]],
            L: [[0, 0, 1], [1, 1, 1]]
        };

        // Game State
        let canvas, ctx, nextCanvas, nextCtx;
        let board = [];
        let currentPiece = null;
        let nextPiece = null;
        let score = 0;
        let level = 1;
        let lines = 0;
        let gameOver = false;
        let isPaused = false;
        let dropCounter = 0;
        let dropInterval = 1000;
        let lastTime = 0;
        let highScore = 0;

        // Initialize Game
        function init() {
            canvas = document.getElementById('tetris-canvas');
            ctx = canvas.getContext('2d');
            nextCanvas = document.getElementById('next-canvas');
            nextCtx = nextCanvas.getContext('2d');

            // Load high score
            highScore = localStorage.getItem('tetris-high-score') || 0;
            document.getElementById('high-score').textContent = highScore;

            // Create empty board
            board = Array(ROWS).fill(null).map(() => Array(COLS).fill(0));

            // Generate first pieces
            nextPiece = generatePiece();
            spawnPiece();

            // Start game loop
            requestAnimationFrame(update);

            // Keyboard controls
            document.addEventListener('keydown', handleKeyPress);
        }

        // Generate random piece
        function generatePiece() {
            const types = Object.keys(SHAPES);
            const type = types[Math.floor(Math.random() * types.length)];
            return {
                type: type,
                shape: SHAPES[type],
                color: COLORS[type],
                x: Math.floor(COLS / 2) - Math.floor(SHAPES[type][0].length / 2),
                y: 0
            };
        }

        // Spawn new piece
        function spawnPiece() {
            currentPiece = nextPiece;
            nextPiece = generatePiece();

            // Check if spawn position is valid
            if (!isValidMove(currentPiece.x, currentPiece.y, currentPiece.shape)) {
                endGame();
            }

            drawNextPiece();
        }

        // Check if move is valid
        function isValidMove(x, y, shape) {
            for (let row = 0; row < shape.length; row++) {
                for (let col = 0; col < shape[row].length; col++) {
                    if (shape[row][col]) {
                        const newX = x + col;
                        const newY = y + row;

                        if (newX < 0 || newX >= COLS || newY >= ROWS) {
                            return false;
                        }

                        if (newY >= 0 && board[newY][newX]) {
                            return false;
                        }
                    }
                }
            }
            return true;
        }

        // Rotate piece
        function rotate(shape) {
            const rows = shape.length;
            const cols = shape[0].length;
            const rotated = [];

            for (let col = 0; col < cols; col++) {
                const newRow = [];
                for (let row = rows - 1; row >= 0; row--) {
                    newRow.push(shape[row][col]);
                }
                rotated.push(newRow);
            }

            return rotated;
        }

        // Merge piece to board
        function mergePiece() {
            for (let row = 0; row < currentPiece.shape.length; row++) {
                for (let col = 0; col < currentPiece.shape[row].length; col++) {
                    if (currentPiece.shape[row][col]) {
                        const y = currentPiece.y + row;
                        const x = currentPiece.x + col;
                        if (y >= 0) {
                            board[y][x] = currentPiece.color;
                        }
                    }
                }
            }
        }

        // Clear completed lines
        function clearLines() {
            let linesCleared = 0;

            for (let row = ROWS - 1; row >= 0; row--) {
                if (board[row].every(cell => cell !== 0)) {
                    board.splice(row, 1);
                    board.unshift(Array(COLS).fill(0));
                    linesCleared++;
                    row++; // Check the same row again
                }
            }

            if (linesCleared > 0) {
                lines += linesCleared;

                // Score calculation
                const points = [0, 100, 300, 500, 800];
                score += points[linesCleared] * level;

                // Level up every 10 lines
                level = Math.floor(lines / 10) + 1;
                dropInterval = Math.max(100, 1000 - (level - 1) * 100);

                updateStats();
            }
        }

        // Move piece down
        function moveDown() {
            if (isValidMove(currentPiece.x, currentPiece.y + 1, currentPiece.shape)) {
                currentPiece.y++;
                return true;
            } else {
                mergePiece();
                clearLines();
                spawnPiece();
                return false;
            }
        }

        // Hard drop
        function hardDrop() {
            while (moveDown()) {}
        }

        // Handle keyboard input
        function handleKeyPress(e) {
            if (gameOver) return;

            if (e.key === 'p' || e.key === 'P') {
                togglePause();
                return;
            }

            if (isPaused) return;

            switch (e.key) {
                case 'ArrowLeft':
                    if (isValidMove(currentPiece.x - 1, currentPiece.y, currentPiece.shape)) {
                        currentPiece.x--;
                    }
                    break;

                case 'ArrowRight':
                    if (isValidMove(currentPiece.x + 1, currentPiece.y, currentPiece.shape)) {
                        currentPiece.x++;
                    }
                    break;

                case 'ArrowDown':
                    moveDown();
                    break;

                case 'ArrowUp':
                    const rotated = rotate(currentPiece.shape);
                    if (isValidMove(currentPiece.x, currentPiece.y, rotated)) {
                        currentPiece.shape = rotated;
                    }
                    break;

                case ' ':
                    e.preventDefault();
                    hardDrop();
                    break;
            }

            draw();
        }

        // Toggle pause
        function togglePause() {
            isPaused = !isPaused;
            document.getElementById('pause-overlay').style.display = isPaused ? 'flex' : 'none';
        }

        // Update game stats
        function updateStats() {
            document.getElementById('score').textContent = score;
            document.getElementById('level').textContent = level;
            document.getElementById('lines').textContent = lines;

            // Update high score
            if (score > highScore) {
                highScore = score;
                localStorage.setItem('tetris-high-score', highScore);
                document.getElementById('high-score').textContent = highScore;
            }
        }

        // Draw game board
        function draw() {
            // Clear canvas
            ctx.fillStyle = 'rgba(0, 0, 0, 0.8)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            // Draw grid
            ctx.strokeStyle = 'rgba(255, 255, 255, 0.05)';
            ctx.lineWidth = 1;
            for (let row = 0; row <= ROWS; row++) {
                ctx.beginPath();
                ctx.moveTo(0, row * BLOCK_SIZE);
                ctx.lineTo(COLS * BLOCK_SIZE, row * BLOCK_SIZE);
                ctx.stroke();
            }
            for (let col = 0; col <= COLS; col++) {
                ctx.beginPath();
                ctx.moveTo(col * BLOCK_SIZE, 0);
                ctx.lineTo(col * BLOCK_SIZE, ROWS * BLOCK_SIZE);
                ctx.stroke();
            }

            // Draw board
            for (let row = 0; row < ROWS; row++) {
                for (let col = 0; col < COLS; col++) {
                    if (board[row][col]) {
                        drawBlock(ctx, col * BLOCK_SIZE, row * BLOCK_SIZE, board[row][col]);
                    }
                }
            }

            // Draw current piece
            if (currentPiece) {
                for (let row = 0; row < currentPiece.shape.length; row++) {
                    for (let col = 0; col < currentPiece.shape[row].length; col++) {
                        if (currentPiece.shape[row][col]) {
                            const x = (currentPiece.x + col) * BLOCK_SIZE;
                            const y = (currentPiece.y + row) * BLOCK_SIZE;
                            drawBlock(ctx, x, y, currentPiece.color);
                        }
                    }
                }

                // Draw ghost piece
                let ghostY = currentPiece.y;
                while (isValidMove(currentPiece.x, ghostY + 1, currentPiece.shape)) {
                    ghostY++;
                }

                ctx.globalAlpha = 0.2;
                for (let row = 0; row < currentPiece.shape.length; row++) {
                    for (let col = 0; col < currentPiece.shape[row].length; col++) {
                        if (currentPiece.shape[row][col]) {
                            const x = (currentPiece.x + col) * BLOCK_SIZE;
                            const y = (ghostY + row) * BLOCK_SIZE;
                            drawBlock(ctx, x, y, currentPiece.color);
                        }
                    }
                }
                ctx.globalAlpha = 1.0;
            }
        }

        // Draw a single block
        function drawBlock(context, x, y, color) {
            // Main block
            context.fillStyle = color;
            context.fillRect(x, y, BLOCK_SIZE, BLOCK_SIZE);

            // Gradient overlay
            const gradient = context.createLinearGradient(x, y, x + BLOCK_SIZE, y + BLOCK_SIZE);
            gradient.addColorStop(0, 'rgba(255, 255, 255, 0.4)');
            gradient.addColorStop(1, 'rgba(0, 0, 0, 0.4)');
            context.fillStyle = gradient;
            context.fillRect(x, y, BLOCK_SIZE, BLOCK_SIZE);

            // Border
            context.strokeStyle = 'rgba(255, 255, 255, 0.6)';
            context.lineWidth = 2;
            context.strokeRect(x + 1, y + 1, BLOCK_SIZE - 2, BLOCK_SIZE - 2);
        }

        // Draw next piece preview
        function drawNextPiece() {
            nextCtx.fillStyle = 'rgba(0, 0, 0, 0.3)';
            nextCtx.fillRect(0, 0, nextCanvas.width, nextCanvas.height);

            if (nextPiece) {
                const blockSize = 20;
                const offsetX = (nextCanvas.width - nextPiece.shape[0].length * blockSize) / 2;
                const offsetY = (nextCanvas.height - nextPiece.shape.length * blockSize) / 2;

                for (let row = 0; row < nextPiece.shape.length; row++) {
                    for (let col = 0; col < nextPiece.shape[row].length; col++) {
                        if (nextPiece.shape[row][col]) {
                            const x = offsetX + col * blockSize;
                            const y = offsetY + row * blockSize;
                            drawBlock(nextCtx, x, y, nextPiece.color);
                        }
                    }
                }
            }
        }

        // Game loop
        function update(time = 0) {
            if (gameOver) return;

            if (!isPaused) {
                const deltaTime = time - lastTime;
                lastTime = time;

                dropCounter += deltaTime;

                if (dropCounter > dropInterval) {
                    moveDown();
                    dropCounter = 0;
                }

                draw();
            }

            requestAnimationFrame(update);
        }

        // End game
        function endGame() {
            gameOver = true;

            document.getElementById('final-score').textContent = score;
            document.getElementById('final-level').textContent = level;
            document.getElementById('final-lines').textContent = lines;

            // Check for new high score
            if (score > parseInt(localStorage.getItem('tetris-high-score') || 0)) {
                document.getElementById('new-high-score-message').style.display = 'block';
            }

            document.getElementById('game-over-overlay').style.display = 'flex';
        }

        // Restart game
        function restartGame() {
            // Reset game state
            board = Array(ROWS).fill(null).map(() => Array(COLS).fill(0));
            score = 0;
            level = 1;
            lines = 0;
            gameOver = false;
            isPaused = false;
            dropCounter = 0;
            dropInterval = 1000;

            // Hide overlays
            document.getElementById('game-over-overlay').style.display = 'none';
            document.getElementById('new-high-score-message').style.display = 'none';

            // Update UI
            updateStats();

            // Generate new pieces
            nextPiece = generatePiece();
            spawnPiece();

            // Restart game loop
            lastTime = 0;
            requestAnimationFrame(update);
        }

        // Start game when page loads
        window.addEventListener('load', init);
    </script>
</body>
</html>
