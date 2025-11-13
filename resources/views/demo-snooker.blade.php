<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เกมสนุ๊กเกอร์ 2D - Thaiprompt Affiliate</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        #gameContainer {
            position: relative;
            max-width: 95vw;
            max-height: 95vh;
        }

        canvas {
            border: 4px solid #8B4513;
            border-radius: 10px;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.8), inset 0 0 20px rgba(139, 69, 19, 0.3);
            background: #1a5f2e;
            display: block;
        }

        #gameUI {
            position: absolute;
            top: 10px;
            left: 10px;
            right: 10px;
            pointer-events: none;
        }

        .ui-panel {
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid #FFD700;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            pointer-events: auto;
        }

        .score-panel {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .player-info {
            flex: 1;
            text-align: center;
        }

        .player-info.active {
            background: rgba(255, 215, 0, 0.2);
            border-radius: 5px;
            padding: 10px;
            animation: pulse 1s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .player-name {
            font-size: 20px;
            font-weight: bold;
            color: #FFD700;
            margin-bottom: 5px;
        }

        .player-score {
            font-size: 32px;
            font-weight: bold;
            color: white;
        }

        .control-panel {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
        }

        button {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-size: 16px;
            font-weight: bold;
            color: #1a1a2e;
            cursor: pointer;
            transition: all 0.3s;
            pointer-events: auto;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.5);
        }

        button:active {
            transform: translateY(0);
        }

        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        #powerMeter {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: 300px;
            height: 30px;
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid #FFD700;
            border-radius: 15px;
            display: none;
            overflow: hidden;
        }

        #powerBar {
            height: 100%;
            background: linear-gradient(90deg, #00ff00 0%, #ffff00 50%, #ff0000 100%);
            width: 0%;
            transition: width 0.1s;
        }

        #instructions {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.95);
            border: 3px solid #FFD700;
            border-radius: 15px;
            padding: 30px;
            max-width: 600px;
            text-align: center;
            z-index: 1000;
        }

        #instructions h2 {
            color: #FFD700;
            margin-bottom: 20px;
            font-size: 28px;
        }

        #instructions p {
            margin: 10px 0;
            line-height: 1.8;
            font-size: 16px;
        }

        #instructions .controls {
            text-align: left;
            margin: 20px 0;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        .game-over {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.95);
            border: 3px solid #FFD700;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            z-index: 1000;
            display: none;
        }

        .game-over h2 {
            color: #FFD700;
            font-size: 36px;
            margin-bottom: 20px;
        }

        .game-over .winner {
            font-size: 48px;
            color: #00ff00;
            margin: 20px 0;
        }

        #gameInfo {
            position: absolute;
            bottom: 70px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid #FFD700;
            border-radius: 10px;
            padding: 10px 20px;
            font-size: 18px;
            font-weight: bold;
            color: #FFD700;
            display: none;
        }

        .ball-indicator {
            display: flex;
            gap: 5px;
            justify-content: center;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .ball-dot {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid white;
        }

        .ball-dot.potted {
            opacity: 0.3;
        }

        #targetBall {
            text-align: center;
            margin-top: 10px;
            font-size: 16px;
            color: #FFD700;
        }
    </style>
</head>
<body>
    <div id="gameContainer">
        <canvas id="gameCanvas"></canvas>

        <div id="gameUI">
            <div class="ui-panel score-panel">
                <div class="player-info" id="player1Info">
                    <div class="player-name">ผู้เล่น 1</div>
                    <div class="player-score" id="player1Score">0</div>
                    <div id="player1Balls" class="ball-indicator"></div>
                </div>
                <div style="text-align: center;">
                    <div id="gameMode" style="font-size: 20px; color: #FFD700; font-weight: bold;">สนุ๊กเกอร์</div>
                    <div id="targetBall"></div>
                </div>
                <div class="player-info" id="player2Info">
                    <div class="player-name">ผู้เล่น 2</div>
                    <div class="player-score" id="player2Score">0</div>
                    <div id="player2Balls" class="ball-indicator"></div>
                </div>
            </div>

            <div class="ui-panel control-panel">
                <button id="newGameBtn">เกมใหม่</button>
                <button id="snookerModeBtn">โหมดสนุ๊กเกอร์</button>
                <button id="nineBallModeBtn">โหมด 9-Ball</button>
                <button id="onePlayerBtn">1 ผู้เล่น</button>
                <button id="twoPlayerBtn">2 ผู้เล่น</button>
                <button id="helpBtn">วิธีเล่น</button>
            </div>
        </div>

        <div id="powerMeter">
            <div id="powerBar"></div>
        </div>

        <div id="gameInfo"></div>

        <div id="instructions">
            <h2>🎱 วิธีเล่นสนุ๊กเกอร์</h2>
            <div class="controls">
                <p><strong>🖱️ เมาส์:</strong> เลื่อนเพื่อเล็งไม้คิว</p>
                <p><strong>🖱️ คลิกซ้าย:</strong> กดค้างเพื่อกำหนดแรงตี (นานขึ้น = แรงมากขึ้น)</p>
                <p><strong>📱 มือถือ:</strong> แตะเพื่อเล็ง กดค้างเพื่อตี</p>
            </div>
            <p><strong>โหมดสนุ๊กเกอร์:</strong> ต้องตีลูกแดงให้เข้าหลุมสลับกับลูกสี ลูกแดง = 1 คะแนน, ลูกสี = 2-7 คะแนน</p>
            <p><strong>โหมด 9-Ball:</strong> ต้องตีลูกตามลำดับ 1-9 ลูกหมายเลข 9 เข้าหลุม = ชนะ</p>
            <p><strong>โหมด 1 ผู้เล่น:</strong> เล่นกับคอมพิวเตอร์ (AI)</p>
            <p><strong>โหมด 2 ผู้เล่น:</strong> เล่นกับเพื่อนสลับกันตี</p>
            <button id="closeInstructions">เริ่มเล่น</button>
        </div>

        <div id="gameOver" class="game-over">
            <h2>🎉 จบเกม! 🎉</h2>
            <div class="winner" id="winnerText"></div>
            <div id="finalScore"></div>
            <button id="playAgainBtn">เล่นอีกครั้ง</button>
        </div>
    </div>

    <script>
        const canvas = document.getElementById('gameCanvas');
        const ctx = canvas.getContext('2d');

        // Set canvas size
        const setCanvasSize = () => {
            const maxWidth = window.innerWidth * 0.9;
            const maxHeight = window.innerHeight * 0.9;
            const aspectRatio = 2; // Pool table is roughly 2:1

            if (maxWidth / aspectRatio < maxHeight) {
                canvas.width = maxWidth;
                canvas.height = maxWidth / aspectRatio;
            } else {
                canvas.height = maxHeight;
                canvas.width = maxHeight * aspectRatio;
            }
        };
        setCanvasSize();

        // Game constants
        const TABLE_MARGIN = 50;
        const POCKET_RADIUS = 20;
        const BALL_RADIUS = 10;
        const CUE_BALL_RADIUS = 10;
        const FRICTION = 0.98;
        const MIN_VELOCITY = 0.1;

        // Game state
        let gameMode = 'snooker'; // 'snooker' or '9ball'
        let playerMode = 2; // 1 or 2
        let currentPlayer = 1;
        let player1Score = 0;
        let player2Score = 0;
        let balls = [];
        let cueBall = null;
        let isAiming = false;
        let aimAngle = 0;
        let power = 0;
        let powerCharging = false;
        let ballsMoving = false;
        let pockets = [];
        let targetBallNumber = null; // For 9-ball
        let snookerPhase = 'red'; // 'red' or 'color'
        let player1BallType = null; // For 9-ball: 'solids' or 'stripes'
        let player2BallType = null;
        let pottedBalls = [];

        // Ball colors
        const BALL_COLORS = {
            white: '#FFFFFF',
            red: '#DC143C',
            yellow: '#FFFF00',
            green: '#228B22',
            brown: '#8B4513',
            blue: '#0000FF',
            pink: '#FFC0CB',
            black: '#000000',
            // 9-ball colors
            1: '#FFFF00',
            2: '#0000FF',
            3: '#DC143C',
            4: '#9370DB',
            5: '#FF8C00',
            6: '#228B22',
            7: '#8B0000',
            8: '#000000',
            9: '#FFFF00'
        };

        const BALL_VALUES = {
            red: 1,
            yellow: 2,
            green: 3,
            brown: 4,
            blue: 5,
            pink: 6,
            black: 7
        };

        // Initialize game
        function init() {
            setupPockets();
            setupBalls();
            updateUI();
            document.getElementById('instructions').style.display = 'block';
            gameLoop();
        }

        function setupPockets() {
            pockets = [
                { x: TABLE_MARGIN, y: TABLE_MARGIN },
                { x: canvas.width / 2, y: TABLE_MARGIN },
                { x: canvas.width - TABLE_MARGIN, y: TABLE_MARGIN },
                { x: TABLE_MARGIN, y: canvas.height - TABLE_MARGIN },
                { x: canvas.width / 2, y: canvas.height - TABLE_MARGIN },
                { x: canvas.width - TABLE_MARGIN, y: canvas.height - TABLE_MARGIN }
            ];
        }

        function setupBalls() {
            balls = [];
            pottedBalls = [];

            // Cue ball
            cueBall = {
                x: canvas.width * 0.25,
                y: canvas.height / 2,
                vx: 0,
                vy: 0,
                radius: CUE_BALL_RADIUS,
                color: BALL_COLORS.white,
                type: 'cue',
                mass: 1
            };

            if (gameMode === 'snooker') {
                setupSnookerBalls();
            } else {
                setup9BallBalls();
            }

            targetBallNumber = gameMode === '9ball' ? 1 : null;
        }

        function setupSnookerBalls() {
            const startX = canvas.width * 0.7;
            const startY = canvas.height / 2;

            // Triangle of 15 red balls
            let row = 0;
            let ballsInRow = 1;
            let redBallCount = 0;
            const rowSpacing = BALL_RADIUS * 2 + 2;
            const ballSpacing = BALL_RADIUS * 2 + 2;

            for (let i = 0; i < 15; i++) {
                const posInRow = redBallCount % ballsInRow;
                const offsetY = (posInRow - (ballsInRow - 1) / 2) * ballSpacing;

                balls.push({
                    x: startX + row * rowSpacing,
                    y: startY + offsetY,
                    vx: 0,
                    vy: 0,
                    radius: BALL_RADIUS,
                    color: BALL_COLORS.red,
                    type: 'red',
                    value: BALL_VALUES.red,
                    mass: 1
                });

                redBallCount++;
                if (redBallCount >= ballsInRow) {
                    row++;
                    ballsInRow++;
                    redBallCount = 0;
                }
            }

            // Colored balls
            balls.push(
                { x: canvas.width * 0.7, y: canvas.height / 2, vx: 0, vy: 0, radius: BALL_RADIUS, color: BALL_COLORS.yellow, type: 'yellow', value: BALL_VALUES.yellow, mass: 1 },
                { x: canvas.width * 0.35, y: canvas.height * 0.3, vx: 0, vy: 0, radius: BALL_RADIUS, color: BALL_COLORS.green, type: 'green', value: BALL_VALUES.green, mass: 1 },
                { x: canvas.width * 0.35, y: canvas.height * 0.7, vx: 0, vy: 0, radius: BALL_RADIUS, color: BALL_COLORS.brown, type: 'brown', value: BALL_VALUES.brown, mass: 1 },
                { x: canvas.width / 2, y: canvas.height / 2, vx: 0, vy: 0, radius: BALL_RADIUS, color: BALL_COLORS.blue, type: 'blue', value: BALL_VALUES.blue, mass: 1 },
                { x: canvas.width * 0.65, y: canvas.height / 2, vx: 0, vy: 0, radius: BALL_RADIUS, color: BALL_COLORS.pink, type: 'pink', value: BALL_VALUES.pink, mass: 1 },
                { x: canvas.width * 0.75, y: canvas.height / 2, vx: 0, vy: 0, radius: BALL_RADIUS, color: BALL_COLORS.black, type: 'black', value: BALL_VALUES.black, mass: 1 }
            );
        }

        function setup9BallBalls() {
            const startX = canvas.width * 0.7;
            const startY = canvas.height / 2;
            const ballSpacing = BALL_RADIUS * 2 + 2;

            // Diamond rack for 9-ball
            const positions = [
                [0, 0],  // 1
                [-1, 1], // 2
                [1, 1],  // 3
                [-2, 2], // 4
                [0, 2],  // 5 (9-ball in center)
                [2, 2],  // 6
                [-1, 3], // 7
                [1, 3],  // 8
                [0, 1]   // 9
            ];

            const ballOrder = [1, 2, 9, 3, 5, 4, 6, 7, 8]; // 9-ball goes in center (index 4)

            positions.forEach((pos, i) => {
                balls.push({
                    x: startX + pos[1] * ballSpacing * 0.866, // cos(30°)
                    y: startY + pos[0] * ballSpacing,
                    vx: 0,
                    vy: 0,
                    radius: BALL_RADIUS,
                    color: BALL_COLORS[ballOrder[i]],
                    type: 'numbered',
                    number: ballOrder[i],
                    value: 1,
                    mass: 1
                });
            });
        }

        function update() {
            if (ballsMoving) {
                let anyMoving = false;

                // Update cue ball
                if (cueBall) {
                    cueBall.x += cueBall.vx;
                    cueBall.y += cueBall.vy;
                    cueBall.vx *= FRICTION;
                    cueBall.vy *= FRICTION;

                    if (Math.abs(cueBall.vx) < MIN_VELOCITY) cueBall.vx = 0;
                    if (Math.abs(cueBall.vy) < MIN_VELOCITY) cueBall.vy = 0;

                    handleTableCollision(cueBall);

                    if (cueBall.vx !== 0 || cueBall.vy !== 0) anyMoving = true;

                    // Check if cue ball falls in pocket
                    for (let pocket of pockets) {
                        const dx = cueBall.x - pocket.x;
                        const dy = cueBall.y - pocket.y;
                        const dist = Math.sqrt(dx * dx + dy * dy);
                        if (dist < POCKET_RADIUS) {
                            // Cue ball potted - foul
                            handleFoul();
                            resetCueBall();
                            anyMoving = false;
                            break;
                        }
                    }
                }

                // Update other balls
                for (let i = balls.length - 1; i >= 0; i--) {
                    const ball = balls[i];
                    ball.x += ball.vx;
                    ball.y += ball.vy;
                    ball.vx *= FRICTION;
                    ball.vy *= FRICTION;

                    if (Math.abs(ball.vx) < MIN_VELOCITY) ball.vx = 0;
                    if (Math.abs(ball.vy) < MIN_VELOCITY) ball.vy = 0;

                    handleTableCollision(ball);

                    if (ball.vx !== 0 || ball.vy !== 0) anyMoving = true;

                    // Check if ball falls in pocket
                    for (let pocket of pockets) {
                        const dx = ball.x - pocket.x;
                        const dy = ball.y - pocket.y;
                        const dist = Math.sqrt(dx * dx + dy * dy);
                        if (dist < POCKET_RADIUS) {
                            handleBallPotted(ball);
                            balls.splice(i, 1);
                            break;
                        }
                    }
                }

                // Check collisions
                if (cueBall) {
                    for (let ball of balls) {
                        checkBallCollision(cueBall, ball);
                    }
                }

                for (let i = 0; i < balls.length; i++) {
                    for (let j = i + 1; j < balls.length; j++) {
                        checkBallCollision(balls[i], balls[j]);
                    }
                }

                if (!anyMoving) {
                    ballsMoving = false;
                    checkGameEnd();

                    if (gameMode === 'snooker') {
                        // Switch to next player or next color
                        if (pottedBalls.length === 0 || pottedBalls[pottedBalls.length - 1].wasLegal === false) {
                            switchPlayer();
                        }
                        pottedBalls = [];
                    } else if (gameMode === '9ball') {
                        if (pottedBalls.length === 0 || (pottedBalls.length > 0 && !pottedBalls.some(b => b.number === targetBallNumber))) {
                            switchPlayer();
                        }
                        pottedBalls = [];
                        updateTargetBall();
                    }

                    if (playerMode === 1 && currentPlayer === 2 && !checkGameEnd()) {
                        setTimeout(aiTurn, 1000);
                    }

                    document.getElementById('powerMeter').style.display = 'none';
                    document.getElementById('gameInfo').style.display = 'none';
                }
            }

            if (powerCharging && !ballsMoving) {
                power = (power + 2) % 100;
                document.getElementById('powerBar').style.width = power + '%';
            }
        }

        function handleTableCollision(ball) {
            const minX = TABLE_MARGIN + ball.radius;
            const maxX = canvas.width - TABLE_MARGIN - ball.radius;
            const minY = TABLE_MARGIN + ball.radius;
            const maxY = canvas.height - TABLE_MARGIN - ball.radius;

            if (ball.x < minX) {
                ball.x = minX;
                ball.vx = -ball.vx * 0.8;
            } else if (ball.x > maxX) {
                ball.x = maxX;
                ball.vx = -ball.vx * 0.8;
            }

            if (ball.y < minY) {
                ball.y = minY;
                ball.vy = -ball.vy * 0.8;
            } else if (ball.y > maxY) {
                ball.y = maxY;
                ball.vy = -ball.vy * 0.8;
            }
        }

        function checkBallCollision(ball1, ball2) {
            const dx = ball2.x - ball1.x;
            const dy = ball2.y - ball1.y;
            const distance = Math.sqrt(dx * dx + dy * dy);

            if (distance < ball1.radius + ball2.radius) {
                // Collision detected
                const angle = Math.atan2(dy, dx);
                const sin = Math.sin(angle);
                const cos = Math.cos(angle);

                // Rotate ball positions
                const pos1 = { x: 0, y: 0 };
                const pos2 = rotate(dx, dy, sin, cos, true);

                // Rotate ball velocities
                const vel1 = rotate(ball1.vx, ball1.vy, sin, cos, true);
                const vel2 = rotate(ball2.vx, ball2.vy, sin, cos, true);

                // Collision reaction
                const vxTotal = vel1.x - vel2.x;
                vel1.x = ((ball1.mass - ball2.mass) * vel1.x + 2 * ball2.mass * vel2.x) / (ball1.mass + ball2.mass);
                vel2.x = vxTotal + vel1.x;

                // Update positions
                pos1.x += vel1.x;
                pos2.x += vel2.x;

                // Rotate positions back
                const pos1Final = rotate(pos1.x, pos1.y, sin, cos, false);
                const pos2Final = rotate(pos2.x, pos2.y, sin, cos, false);

                // Update actual positions
                ball2.x = ball1.x + pos2Final.x;
                ball2.y = ball1.y + pos2Final.y;
                ball1.x = ball1.x + pos1Final.x;
                ball1.y = ball1.y + pos1Final.y;

                // Rotate velocities back
                const vel1Final = rotate(vel1.x, vel1.y, sin, cos, false);
                const vel2Final = rotate(vel2.x, vel2.y, sin, cos, false);

                ball1.vx = vel1Final.x;
                ball1.vy = vel1Final.y;
                ball2.vx = vel2Final.x;
                ball2.vy = vel2Final.y;
            }
        }

        function rotate(x, y, sin, cos, reverse) {
            return {
                x: reverse ? (x * cos + y * sin) : (x * cos - y * sin),
                y: reverse ? (y * cos - x * sin) : (y * cos + x * sin)
            };
        }

        function handleBallPotted(ball) {
            if (gameMode === 'snooker') {
                let isLegal = false;
                if (snookerPhase === 'red' && ball.type === 'red') {
                    isLegal = true;
                    addScore(ball.value);
                    snookerPhase = 'color';
                } else if (snookerPhase === 'color' && ball.type !== 'red') {
                    isLegal = true;
                    addScore(ball.value);
                    snookerPhase = 'red';
                    // Return colored ball (except red)
                    balls.push({ ...ball, vx: 0, vy: 0 });
                }
                pottedBalls.push({ ...ball, wasLegal: isLegal });

                showGameInfo(isLegal ? `${ball.type} +${ball.value} คะแนน!` : 'ฟาล์ว!');
            } else if (gameMode === '9ball') {
                if (ball.number === 9) {
                    // Win condition
                    addScore(10);
                    endGame();
                } else {
                    addScore(1);
                }
                pottedBalls.push(ball);
                showGameInfo(`ลูกหมายเลข ${ball.number} เข้าหลุม!`);
            }
        }

        function handleFoul() {
            showGameInfo('ฟาล์ว! ลูกคิวเข้าหลุม');
            if (gameMode === 'snooker') {
                // Opponent gets 4 points
                if (currentPlayer === 1) {
                    player2Score += 4;
                } else {
                    player1Score += 4;
                }
            }
            switchPlayer();
        }

        function resetCueBall() {
            cueBall = {
                x: canvas.width * 0.25,
                y: canvas.height / 2,
                vx: 0,
                vy: 0,
                radius: CUE_BALL_RADIUS,
                color: BALL_COLORS.white,
                type: 'cue',
                mass: 1
            };
        }

        function addScore(points) {
            if (currentPlayer === 1) {
                player1Score += points;
            } else {
                player2Score += points;
            }
            updateUI();
        }

        function switchPlayer() {
            currentPlayer = currentPlayer === 1 ? 2 : 1;
            updateUI();
        }

        function updateTargetBall() {
            if (gameMode === '9ball') {
                // Find lowest numbered ball
                let lowestNumber = 10;
                for (let ball of balls) {
                    if (ball.number && ball.number < lowestNumber) {
                        lowestNumber = ball.number;
                    }
                }
                targetBallNumber = lowestNumber <= 9 ? lowestNumber : null;
                updateUI();
            }
        }

        function checkGameEnd() {
            if (gameMode === 'snooker') {
                // Check if all balls are potted
                const redsRemaining = balls.filter(b => b.type === 'red').length;
                if (redsRemaining === 0 && balls.length === 0) {
                    endGame();
                    return true;
                }
            } else if (gameMode === '9ball') {
                // Check if 9-ball is potted
                const nineBallExists = balls.some(b => b.number === 9);
                if (!nineBallExists) {
                    endGame();
                    return true;
                }
            }
            return false;
        }

        function endGame() {
            const winner = player1Score > player2Score ? 'ผู้เล่น 1' :
                          player1Score < player2Score ? 'ผู้เล่น 2' : 'เสมอ';

            document.getElementById('winnerText').textContent = winner + ' ชนะ!';
            document.getElementById('finalScore').innerHTML = `
                <p style="font-size: 24px; margin: 20px 0;">
                    ผู้เล่น 1: ${player1Score} | ผู้เล่น 2: ${player2Score}
                </p>
            `;
            document.getElementById('gameOver').style.display = 'block';
        }

        function showGameInfo(text) {
            const info = document.getElementById('gameInfo');
            info.textContent = text;
            info.style.display = 'block';
            setTimeout(() => {
                info.style.display = 'none';
            }, 2000);
        }

        function aiTurn() {
            if (ballsMoving || currentPlayer !== 2) return;

            // Simple AI: aim at closest ball
            let closestBall = null;
            let closestDist = Infinity;

            for (let ball of balls) {
                if (gameMode === '9ball' && targetBallNumber && ball.number !== targetBallNumber) continue;
                if (gameMode === 'snooker' && snookerPhase === 'red' && ball.type !== 'red') continue;
                if (gameMode === 'snooker' && snookerPhase === 'color' && ball.type === 'red') continue;

                const dx = ball.x - cueBall.x;
                const dy = ball.y - cueBall.y;
                const dist = Math.sqrt(dx * dx + dy * dy);
                if (dist < closestDist) {
                    closestDist = dist;
                    closestBall = ball;
                }
            }

            if (closestBall) {
                // Aim at the ball
                const dx = closestBall.x - cueBall.x;
                const dy = closestBall.y - cueBall.y;
                aimAngle = Math.atan2(dy, dx);

                // Shoot with medium power
                const shootPower = 40 + Math.random() * 30;
                cueBall.vx = Math.cos(aimAngle) * shootPower * 0.3;
                cueBall.vy = Math.sin(aimAngle) * shootPower * 0.3;
                ballsMoving = true;
            }
        }

        function render() {
            // Clear canvas
            ctx.fillStyle = '#1a5f2e';
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            // Draw table border
            ctx.strokeStyle = '#8B4513';
            ctx.lineWidth = 8;
            ctx.strokeRect(TABLE_MARGIN, TABLE_MARGIN, canvas.width - TABLE_MARGIN * 2, canvas.height - TABLE_MARGIN * 2);

            // Draw pockets
            for (let pocket of pockets) {
                ctx.fillStyle = '#000000';
                ctx.beginPath();
                ctx.arc(pocket.x, pocket.y, POCKET_RADIUS, 0, Math.PI * 2);
                ctx.fill();
                ctx.strokeStyle = '#8B4513';
                ctx.lineWidth = 3;
                ctx.stroke();
            }

            // Draw balls
            for (let ball of balls) {
                drawBall(ball);
            }

            // Draw cue ball
            if (cueBall) {
                drawBall(cueBall);
            }

            // Draw cue stick when aiming
            if (!ballsMoving && cueBall && isAiming) {
                const cueLength = 200;
                const cueStartX = cueBall.x - Math.cos(aimAngle) * (cueBall.radius + 10);
                const cueStartY = cueBall.y - Math.sin(aimAngle) * (cueBall.radius + 10);
                const cueEndX = cueStartX - Math.cos(aimAngle) * cueLength;
                const cueEndY = cueStartY - Math.sin(aimAngle) * cueLength;

                // Draw aim line
                ctx.strokeStyle = 'rgba(255, 255, 255, 0.5)';
                ctx.setLineDash([10, 5]);
                ctx.lineWidth = 2;
                ctx.beginPath();
                ctx.moveTo(cueBall.x, cueBall.y);
                ctx.lineTo(cueBall.x + Math.cos(aimAngle) * 300, cueBall.y + Math.sin(aimAngle) * 300);
                ctx.stroke();
                ctx.setLineDash([]);

                // Draw cue stick
                ctx.strokeStyle = '#D2691E';
                ctx.lineWidth = 6;
                ctx.beginPath();
                ctx.moveTo(cueStartX, cueStartY);
                ctx.lineTo(cueEndX, cueEndY);
                ctx.stroke();

                // Draw cue tip
                ctx.fillStyle = '#FFFFFF';
                ctx.beginPath();
                ctx.arc(cueStartX, cueStartY, 4, 0, Math.PI * 2);
                ctx.fill();
            }
        }

        function drawBall(ball) {
            // Shadow
            ctx.fillStyle = 'rgba(0, 0, 0, 0.3)';
            ctx.beginPath();
            ctx.ellipse(ball.x + 3, ball.y + 3, ball.radius, ball.radius * 0.5, 0, 0, Math.PI * 2);
            ctx.fill();

            // Ball
            const gradient = ctx.createRadialGradient(
                ball.x - ball.radius * 0.3,
                ball.y - ball.radius * 0.3,
                ball.radius * 0.1,
                ball.x,
                ball.y,
                ball.radius
            );
            gradient.addColorStop(0, lightenColor(ball.color, 40));
            gradient.addColorStop(1, ball.color);

            ctx.fillStyle = gradient;
            ctx.beginPath();
            ctx.arc(ball.x, ball.y, ball.radius, 0, Math.PI * 2);
            ctx.fill();

            // Ball outline
            ctx.strokeStyle = 'rgba(0, 0, 0, 0.3)';
            ctx.lineWidth = 1;
            ctx.stroke();

            // Draw number for 9-ball
            if (ball.number) {
                ctx.fillStyle = '#FFFFFF';
                ctx.fillRect(ball.x - ball.radius * 0.6, ball.y - ball.radius * 0.4, ball.radius * 1.2, ball.radius * 0.8);
                ctx.fillStyle = '#000000';
                ctx.font = `bold ${ball.radius * 1.2}px Arial`;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(ball.number, ball.x, ball.y);
            }

            // Shine effect
            ctx.fillStyle = 'rgba(255, 255, 255, 0.4)';
            ctx.beginPath();
            ctx.arc(ball.x - ball.radius * 0.4, ball.y - ball.radius * 0.4, ball.radius * 0.3, 0, Math.PI * 2);
            ctx.fill();
        }

        function lightenColor(color, percent) {
            const num = parseInt(color.replace('#', ''), 16);
            const amt = Math.round(2.55 * percent);
            const R = Math.min(255, (num >> 16) + amt);
            const G = Math.min(255, (num >> 8 & 0x00FF) + amt);
            const B = Math.min(255, (num & 0x0000FF) + amt);
            return '#' + (0x1000000 + R * 0x10000 + G * 0x100 + B).toString(16).slice(1);
        }

        function gameLoop() {
            update();
            render();
            requestAnimationFrame(gameLoop);
        }

        function updateUI() {
            document.getElementById('player1Score').textContent = player1Score;
            document.getElementById('player2Score').textContent = player2Score;
            document.getElementById('gameMode').textContent = gameMode === 'snooker' ? 'สนุ๊กเกอร์' : '9-Ball';

            // Update active player
            const p1Info = document.getElementById('player1Info');
            const p2Info = document.getElementById('player2Info');

            if (currentPlayer === 1) {
                p1Info.classList.add('active');
                p2Info.classList.remove('active');
            } else {
                p1Info.classList.remove('active');
                p2Info.classList.add('active');
            }

            // Update player names
            document.querySelector('#player1Info .player-name').textContent =
                playerMode === 1 ? 'ผู้เล่น' : 'ผู้เล่น 1';
            document.querySelector('#player2Info .player-name').textContent =
                playerMode === 1 ? 'AI' : 'ผู้เล่น 2';

            // Update target ball display
            const targetBallDiv = document.getElementById('targetBall');
            if (gameMode === '9ball' && targetBallNumber) {
                targetBallDiv.textContent = `เป้าหมาย: ลูกหมายเลข ${targetBallNumber}`;
            } else if (gameMode === 'snooker') {
                targetBallDiv.textContent = `เป้าหมาย: ${snookerPhase === 'red' ? 'ลูกแดง' : 'ลูกสี'}`;
            } else {
                targetBallDiv.textContent = '';
            }

            // Update ball indicators
            updateBallIndicators();
        }

        function updateBallIndicators() {
            const p1Balls = document.getElementById('player1Balls');
            const p2Balls = document.getElementById('player2Balls');
            p1Balls.innerHTML = '';
            p2Balls.innerHTML = '';

            if (gameMode === 'snooker') {
                // Show remaining reds
                const redsCount = balls.filter(b => b.type === 'red').length;
                for (let i = 0; i < Math.min(15, redsCount); i++) {
                    const dot = document.createElement('div');
                    dot.className = 'ball-dot';
                    dot.style.backgroundColor = BALL_COLORS.red;
                    p1Balls.appendChild(dot);
                }
            }
        }

        // Event handlers
        canvas.addEventListener('mousemove', (e) => {
            if (ballsMoving || currentPlayer === 2 && playerMode === 1) return;

            const rect = canvas.getBoundingClientRect();
            const mouseX = e.clientX - rect.left;
            const mouseY = e.clientY - rect.top;

            if (cueBall) {
                const dx = mouseX - cueBall.x;
                const dy = mouseY - cueBall.y;
                aimAngle = Math.atan2(dy, dx);
                isAiming = true;
            }
        });

        canvas.addEventListener('mousedown', (e) => {
            if (ballsMoving || currentPlayer === 2 && playerMode === 1) return;

            powerCharging = true;
            power = 0;
            document.getElementById('powerMeter').style.display = 'block';
        });

        canvas.addEventListener('mouseup', (e) => {
            if (!powerCharging || ballsMoving) return;

            powerCharging = false;

            if (cueBall && power > 0) {
                cueBall.vx = Math.cos(aimAngle) * power * 0.3;
                cueBall.vy = Math.sin(aimAngle) * power * 0.3;
                ballsMoving = true;
                isAiming = false;
            }

            document.getElementById('powerMeter').style.display = 'none';
        });

        canvas.addEventListener('mouseleave', () => {
            isAiming = false;
        });

        // Touch events
        canvas.addEventListener('touchstart', (e) => {
            e.preventDefault();
            if (ballsMoving || currentPlayer === 2 && playerMode === 1) return;

            const rect = canvas.getBoundingClientRect();
            const touch = e.touches[0];
            const mouseX = touch.clientX - rect.left;
            const mouseY = touch.clientY - rect.top;

            if (cueBall) {
                const dx = mouseX - cueBall.x;
                const dy = mouseY - cueBall.y;
                aimAngle = Math.atan2(dy, dx);
                isAiming = true;
            }

            powerCharging = true;
            power = 0;
            document.getElementById('powerMeter').style.display = 'block';
        });

        canvas.addEventListener('touchmove', (e) => {
            e.preventDefault();
            if (ballsMoving || currentPlayer === 2 && playerMode === 1) return;

            const rect = canvas.getBoundingClientRect();
            const touch = e.touches[0];
            const mouseX = touch.clientX - rect.left;
            const mouseY = touch.clientY - rect.top;

            if (cueBall) {
                const dx = mouseX - cueBall.x;
                const dy = mouseY - cueBall.y;
                aimAngle = Math.atan2(dy, dx);
            }
        });

        canvas.addEventListener('touchend', (e) => {
            e.preventDefault();
            if (!powerCharging || ballsMoving) return;

            powerCharging = false;

            if (cueBall && power > 0) {
                cueBall.vx = Math.cos(aimAngle) * power * 0.3;
                cueBall.vy = Math.sin(aimAngle) * power * 0.3;
                ballsMoving = true;
                isAiming = false;
            }

            document.getElementById('powerMeter').style.display = 'none';
        });

        // Button handlers
        document.getElementById('newGameBtn').addEventListener('click', () => {
            player1Score = 0;
            player2Score = 0;
            currentPlayer = 1;
            snookerPhase = 'red';
            setupBalls();
            updateUI();
            document.getElementById('gameOver').style.display = 'none';
        });

        document.getElementById('snookerModeBtn').addEventListener('click', () => {
            gameMode = 'snooker';
            player1Score = 0;
            player2Score = 0;
            currentPlayer = 1;
            snookerPhase = 'red';
            setupBalls();
            updateUI();
            document.getElementById('gameOver').style.display = 'none';
        });

        document.getElementById('nineBallModeBtn').addEventListener('click', () => {
            gameMode = '9ball';
            player1Score = 0;
            player2Score = 0;
            currentPlayer = 1;
            setupBalls();
            updateUI();
            document.getElementById('gameOver').style.display = 'none';
        });

        document.getElementById('onePlayerBtn').addEventListener('click', () => {
            playerMode = 1;
            player1Score = 0;
            player2Score = 0;
            currentPlayer = 1;
            setupBalls();
            updateUI();
            document.getElementById('gameOver').style.display = 'none';
        });

        document.getElementById('twoPlayerBtn').addEventListener('click', () => {
            playerMode = 2;
            player1Score = 0;
            player2Score = 0;
            currentPlayer = 1;
            setupBalls();
            updateUI();
            document.getElementById('gameOver').style.display = 'none';
        });

        document.getElementById('helpBtn').addEventListener('click', () => {
            document.getElementById('instructions').style.display = 'block';
        });

        document.getElementById('closeInstructions').addEventListener('click', () => {
            document.getElementById('instructions').style.display = 'none';
        });

        document.getElementById('playAgainBtn').addEventListener('click', () => {
            player1Score = 0;
            player2Score = 0;
            currentPlayer = 1;
            snookerPhase = 'red';
            setupBalls();
            updateUI();
            document.getElementById('gameOver').style.display = 'none';
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            setCanvasSize();
            setupPockets();
        });

        // Initialize game
        init();
    </script>
</body>
</html>
