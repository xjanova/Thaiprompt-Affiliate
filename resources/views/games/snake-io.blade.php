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
            justify-content: flex-start;
            align-items: flex-start;
            gap: 15px;
            flex-wrap: wrap;
        }

        .hud-item {
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(0, 255, 255, 0.6);
            border-radius: 10px;
            padding: 15px 25px;
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.3);
        }

        .hud-label {
            font-size: 12px;
            opacity: 1;
            margin-bottom: 5px;
            color: #ffffff;
            font-weight: 600;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.8);
        }

        .hud-value {
            font-size: 32px;
            font-weight: 900;
            color: #00ffff;
            text-shadow:
                0 0 10px rgba(0, 255, 255, 1),
                0 0 20px rgba(0, 255, 255, 0.5),
                2px 2px 4px rgba(0, 0, 0, 1);
        }

        /* Power-up Indicators */
        .powerup-indicators {
            position: absolute;
            top: 120px;
            left: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 10;
        }

        .powerup-active {
            background: rgba(0, 0, 0, 0.9);
            border: 2px solid;
            border-radius: 10px;
            padding: 10px 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: pulse 1s infinite;
        }

        .powerup-active.magnet {
            border-color: #ff00ff;
            box-shadow: 0 0 20px rgba(255, 0, 255, 0.5);
        }

        .powerup-active.speed {
            border-color: #ffff00;
            box-shadow: 0 0 20px rgba(255, 255, 0, 0.5);
        }

        .powerup-active.multiplier {
            border-color: #00ff00;
            box-shadow: 0 0 20px rgba(0, 255, 0, 0.5);
        }

        .powerup-active.zoom {
            border-color: #00ffff;
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.5);
        }

        .powerup-icon {
            font-size: 24px;
        }

        .powerup-time {
            font-size: 14px;
            font-weight: bold;
            color: #ffffff;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.8);
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        /* Leaderboard */
        .leaderboard {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(0, 255, 255, 0.6);
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
            text-shadow:
                0 0 10px rgba(0, 255, 255, 0.8),
                2px 2px 4px rgba(0, 0, 0, 1);
        }

        .leaderboard-entry {
            padding: 8px;
            margin: 5px 0;
            background: rgba(0, 255, 255, 0.1);
            border-radius: 5px;
            font-size: 14px;
            display: flex;
            justify-content: space-between;
            color: #ffffff;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.8);
        }

        .leaderboard-entry.you {
            background: rgba(255, 255, 0, 0.3);
            border: 1px solid #ffff00;
            font-weight: bold;
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

        /* Game Version */
        #game-version {
            position: absolute;
            bottom: 10px;
            left: 20px;
            font-family: 'Orbitron', 'Noto Sans Thai', sans-serif;
            font-size: 11px;
            color: rgba(255, 255, 255, 0.4);
            pointer-events: none;
            z-index: 10;
            text-shadow: 0 0 5px rgba(0, 0, 0, 0.8);
            transition: color 0.3s ease;
        }

        #game-version:hover {
            color: rgba(0, 255, 255, 0.6);
        }

        #game-version .version-label {
            font-weight: 700;
            color: rgba(0, 255, 255, 0.5);
        }

        /* Connection Status Indicator */
        #connection-status {
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 25px;
            padding: 8px 20px;
            display: none;
            align-items: center;
            gap: 8px;
            font-family: 'Orbitron', 'Noto Sans Thai', sans-serif;
            font-size: 12px;
            font-weight: bold;
            z-index: 50;
            transition: all 0.3s ease;
        }

        #connection-status.show {
            display: flex;
        }

        #connection-status.online {
            border-color: rgba(0, 255, 0, 0.6);
            box-shadow: 0 0 20px rgba(0, 255, 0, 0.3);
        }

        #connection-status.offline {
            border-color: rgba(255, 170, 0, 0.6);
            box-shadow: 0 0 20px rgba(255, 170, 0, 0.3);
        }

        #connection-status .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            animation: pulse-dot 2s infinite;
        }

        #connection-status.online .status-dot {
            background: #00ff00;
            box-shadow: 0 0 10px #00ff00;
        }

        #connection-status.offline .status-dot {
            background: #ffaa00;
            box-shadow: 0 0 10px #ffaa00;
        }

        #connection-status .status-text {
            color: #ffffff;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.8);
        }

        @keyframes pulse-dot {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.6;
                transform: scale(1.2);
            }
        }

        @media (max-width: 768px) {
            #connection-status {
                font-size: 11px;
                padding: 6px 16px;
            }
        }
    </style>
</head>
<body>
    <div id="game-container">
        <canvas id="game-canvas"></canvas>

        <!-- Connection Status Indicator -->
        <div id="connection-status">
            <div class="status-dot"></div>
            <span class="status-text">CONNECTING...</span>
        </div>

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

        <!-- Power-up Indicators -->
        <div class="powerup-indicators" id="powerup-indicators"></div>

        <!-- Start Screen -->
        <div id="start-screen">
            <h1>🐍 SNAKE.IO</h1>

            <!-- เวอร์ชั่นและผู้สร้าง -->
            <div style="margin-bottom: 15px;">
                <p style="color: #00ffff; font-size: 14px; margin: 5px 0; text-shadow: 0 0 10px rgba(0, 255, 255, 0.5);">
                    <strong>Version 2.6.0</strong> - Advanced AI
                </p>
                <p style="color: #ffaa00; font-size: 13px; margin: 5px 0; text-shadow: 0 0 8px rgba(255, 170, 0, 0.5);">
                    🎮 Created by <strong>XMAN STUDIO</strong> © 2025
                </p>
            </div>

            <p style="color: #ccc; font-size: 18px; margin-bottom: 10px;">
                กินอาหารให้มากที่สุด แต่อย่าชนอะไร!
            </p>

            @auth
                <p style="color: #00ff00; font-size: 14px; margin-bottom: 20px;">
                    ✅ เข้าสู่ระบบแล้ว: <strong>{{ Auth::user()->name }}</strong>
                </p>
            @else
                <p style="color: #ffaa00; font-size: 14px; margin-bottom: 20px;">
                    👤 เล่นในโหมด Guest (คะแนนจะไม่ถูกบันทึก)
                </p>
            @endauth

            <input type="text" id="player-name" class="name-input"
                   value="{{ Auth::check() ? Auth::user()->name : '' }}"
                   placeholder="กรอกชื่อของคุณ"
                   maxlength="20">

            <div class="skin-selector">
                @php
                    $defaultSkins = [
                        ['slug' => 'classic', 'name' => 'Classic', 'color' => '#00ff00', 'price' => 0],
                        ['slug' => 'fire', 'name' => 'Fire', 'color' => '#ff4400', 'price' => 0],
                        ['slug' => 'ice', 'name' => 'Ice', 'color' => '#00aaff', 'price' => 0],
                        ['slug' => 'gold', 'name' => 'Gold', 'color' => '#ffd700', 'price' => 0],
                        ['slug' => 'rainbow', 'name' => 'Rainbow', 'color' => 'linear-gradient(90deg, #ff0000, #00ff00, #0000ff)', 'price' => 0],
                    ];
                @endphp

                @foreach($defaultSkins as $skin)
                    <div class="skin-option"
                         data-skin="{{ $skin['slug'] }}"
                         style="background: {{ $skin['color'] }};"
                         title="{{ $skin['name'] }}">
                    </div>
                @endforeach
            </div>

            <!-- Custom Color Picker -->
            <div style="margin: 20px 0; padding: 20px; background: rgba(0, 0, 0, 0.5); border-radius: 10px;">
                <h3 style="color: #00ffff; font-size: 16px; margin-bottom: 15px;">🎨 สร้างสีของคุณเอง (3 สี)</h3>
                <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                    <div>
                        <label style="color: #fff; font-size: 12px;">สี 1</label><br>
                        <input type="color" id="color1" value="#00ff00" style="width: 60px; height: 40px; border: 2px solid #00ffff; border-radius: 5px;">
                    </div>
                    <div>
                        <label style="color: #fff; font-size: 12px;">สี 2</label><br>
                        <input type="color" id="color2" value="#00aa00" style="width: 60px; height: 40px; border: 2px solid #00ffff; border-radius: 5px;">
                    </div>
                    <div>
                        <label style="color: #fff; font-size: 12px;">สี 3</label><br>
                        <input type="color" id="color3" value="#00dd00" style="width: 60px; height: 40px; border: 2px solid #00ffff; border-radius: 5px;">
                    </div>
                </div>
                <button class="btn" id="apply-custom-colors" style="margin-top: 15px; padding: 10px 20px; font-size: 14px;">
                    ✅ ใช้สีนี้
                </button>
            </div>

            @auth
            <!-- ปุ่มบันทึกและโหลดสี (สำหรับสมาชิก) -->
            <div style="margin: 20px 0; padding: 15px; background: rgba(0, 255, 255, 0.1); border: 1px solid #00ffff; border-radius: 10px;">
                <p style="color: #00ffff; font-size: 14px; margin-bottom: 10px;">
                    💾 จัดการสีที่บันทึกไว้
                </p>
                <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                    <button class="btn" id="save-skin-btn" style="padding: 8px 16px; font-size: 13px;">
                        💾 บันทึกสีนี้
                    </button>
                    <button class="btn" id="load-skin-btn" style="padding: 8px 16px; font-size: 13px; background: linear-gradient(135deg, #ff9900, #ff6600);">
                        📥 โหลดสีที่บันทึก
                    </button>
                </div>
                <span id="save-skin-status" style="color: #00ff00; font-size: 12px; display: block; text-align: center; margin-top: 10px;"></span>
            </div>
            @endauth

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

            <!-- บันทึกคะแนน -->
            <div id="save-score-section" style="margin-top: 30px; padding: 20px; background: rgba(0, 0, 0, 0.5); border-radius: 10px;">
                <!-- สำหรับ Guest -->
                @guest
                <div id="guest-save-section">
                    <h3 style="color: #ffaa00; margin-bottom: 15px;">👤 ผู้เล่นแบบ Guest</h3>
                    <p style="font-size: 14px; color: #ccc; margin-bottom: 15px;">
                        คะแนนของคุณจะไม่ถูกบันทึก<br>
                        กลายเป็นสมาชิกเพื่อบันทึกคะแนนและแข่งขันในลีดเดอร์บอร์ด!
                    </p>
                    <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                        <a href="{{ route('login') }}" class="btn" style="background: linear-gradient(135deg, #00ff00, #00aa00); text-decoration: none; display: inline-block;">
                            🔑 เข้าสู่ระบบ
                        </a>
                        <a href="{{ route('register') }}" class="btn" style="background: linear-gradient(135deg, #00aaff, #0066ff); text-decoration: none; display: inline-block;">
                            ✨ สมัครสมาชิก
                        </a>
                    </div>
                </div>
                @endguest

                <!-- สำหรับ Member -->
                @auth
                <div id="member-save-section">
                    <h3 style="color: #00ffff; margin-bottom: 15px;">💾 บันทึกคะแนนในสถิติเซิร์ฟเวอร์?</h3>
                    <p style="font-size: 14px; color: #ccc; margin-bottom: 10px;">
                        ค่าบันทึก: <span style="color: #ffff00; font-weight: bold;">1 แต้ม</span>
                    </p>
                    <div id="wallet-status" style="margin: 15px 0; padding: 10px; background: rgba(255, 255, 255, 0.1); border-radius: 5px;">
                        <p style="color: #ccc; font-size: 14px;">⏳ กำลังโหลดข้อมูล...</p>
                    </div>
                    <div style="margin-top: 15px; display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                        <button class="btn" id="save-score-btn" style="background: linear-gradient(135deg, #00ff00, #00aa00);">
                            ✅ บันทึกคะแนน (1 แต้ม)
                        </button>
                        <button class="btn" id="skip-save-btn" style="background: linear-gradient(135deg, #666, #444);">
                            ❌ ไม่บันทึก
                        </button>
                    </div>
                </div>
                @endauth
            </div>

            <div style="margin-top: 20px;">
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

        <!-- Game Version -->
        <div id="game-version">
            <span class="version-label">Snake.io</span> v2.5.0-ws |
            <span style="color: rgba(255, 170, 0, 0.5);">WebSocket</span> +
            <span style="color: rgba(0, 255, 100, 0.5);">Anti-cheat</span>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="/js/optimized-touch-input.js"></script>
    <script src="/js/snake-multiplayer.js"></script>

    <script>
        // Game configuration
        const CONFIG = {
            WORLD_SIZE: 200,
            FOOD_COUNT: 100,
            BOT_COUNT: 12, // ✅ ลดเหลือ 12 บอท (ป้องกันเกมค้าง)
            INITIAL_LENGTH: 5,
            SEGMENT_SIZE: 0.5,
            MOVEMENT_SPEED: 0.15,
            BOOST_SPEED: 0.3,
            TURN_SPEED: 0.05,
            FOOD_VALUE: 1, // กินอาหาร 1 ก้อนได้ 1 คะแนน (ต้องกิน 10 คะแนนถึงจะเพิ่ม 1 ปล้อง)
            COLLISION_DISTANCE: 0.6,
            CAMERA_INITIAL_DISTANCE: 15, // กล้องใกล้มากขึ้น (เดิม 20)
            CAMERA_ZOOMED_OUT_DISTANCE: 50, // ซูมออกเต็มที่เมื่อมี powerup
        };

        // Audio System (16-bit style)
        let audioContext;
        let soundEnabled = true;

        // Initialize audio context on user interaction
        function initAudio() {
            try {
                if (!audioContext) {
                    audioContext = new (window.AudioContext || window.webkitAudioContext)();
                }
                // Resume audio context if suspended
                if (audioContext.state === 'suspended') {
                    audioContext.resume();
                }
            } catch (error) {
                console.warn('Audio not supported:', error);
                soundEnabled = false;
            }
        }

        // 16-bit sound generator
        function playSound(type) {
            if (!soundEnabled || !audioContext) return;

            try {
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
            } catch (error) {
                console.warn('Sound playback error:', error);
            }
        }

        // Game state
        let scene, camera, renderer;
        let player;
        let foods = [];
        let bots = [];
        let otherPlayerSnakes = new Map(); // สำหรับเก็บงูของผู้เล่นคนอื่น
        let gameStarted = false;
        let gameOver = false;
        let playerName = '';
        let selectedSkin = 'classic';
        let score = 0;
        let isAuthenticated = {{ Auth::check() ? 'true' : 'false' }};

        // ✅ Performance Optimization - Throttling
        let lastCollisionCheck = 0;
        let lastLeaderboardUpdate = 0;
        const COLLISION_CHECK_INTERVAL = 50; // เช็ค collision ทุก 50ms (20 ครั้ง/วินาที แทนที่จะเป็น 60 ครั้ง)
        const LEADERBOARD_UPDATE_INTERVAL = 500; // อัปเดต leaderboard ทุก 500ms (2 ครั้ง/วินาที)

        // Multiplayer Manager
        let multiplayerManager = null;
        let touchInputManager = null;

        // Connection monitoring
        let connectionMonitorInterval = null;
        let isOnline = false;
        let reconnectAttempts = 0;
        const MAX_RECONNECT_ATTEMPTS = 5;
        const CONNECTION_CHECK_INTERVAL = 5000; // เช็คทุก 5 วินาที

        // Power-ups
        let powerups = [];
        let activePowerups = {
            magnet: null,
            speed: null,
            multiplier: null,
            zoom: null
        };

        // Camera zoom state
        let currentCameraDistance = CONFIG.CAMERA_INITIAL_DISTANCE;
        let targetCameraDistance = CONFIG.CAMERA_INITIAL_DISTANCE;

        // ✅ Multiplayer sync throttling (เพื่อป้องกันเกมค้าง)
        let lastSyncTime = 0;
        const SYNC_INTERVAL = 1000; // sync ทุก 1 วินาที

        // ✅ Other players rendering throttling (ป้องกันเกมค้าง)
        let lastRenderOthersTime = 0;
        const RENDER_OTHERS_INTERVAL = 100; // render ผู้เล่นคนอื่นทุก 100ms (10 FPS)

        const POWERUP_TYPES = {
            MAGNET: {
                name: 'magnet',
                icon: '🧲',
                color: 0xff00ff,
                duration: 10000,
                spawnChance: 0.25
            },
            SPEED: {
                name: 'speed',
                icon: '⚡',
                color: 0xffff00,
                duration: 10000,
                spawnChance: 0.25
            },
            MULTIPLIER: {
                name: 'multiplier',
                icon: '✖️',
                color: 0x00ff00,
                duration: 10000,
                spawnChance: 0.25
            },
            ZOOM: {
                name: 'zoom',
                icon: '🔍',
                color: 0x00ffff,
                duration: 15000,
                spawnChance: 0.25
            }
        };

        // Skin colors - รองรับ 3 สีสลับกัน
        const SKINS = {
            'classic': {
                primary: 0x00ff00,
                secondary: 0x00aa00,
                colors: [0x00ff00, 0x00aa00, 0x00dd00] // 3 สีเขียว
            },
            'fire': {
                primary: 0xff4400,
                secondary: 0xaa2200,
                colors: [0xff4400, 0xff6600, 0xaa2200] // 3 สีส้ม-แดง
            },
            'ice': {
                primary: 0x00aaff,
                secondary: 0x0077cc,
                colors: [0x00aaff, 0x0088dd, 0x0077cc] // 3 สีฟ้า
            },
            'gold': {
                primary: 0xffd700,
                secondary: 0xffaa00,
                colors: [0xffd700, 0xffcc00, 0xffaa00] // 3 สีทอง
            },
            'rainbow': {
                primary: 0xff00ff,
                secondary: 0x00ffff,
                colors: [0xff0000, 0x00ff00, 0x0000ff] // RGB
            },
            'custom': {
                primary: 0x00ff00,
                secondary: 0x00aa00,
                colors: [0x00ff00, 0x00aa00, 0x00dd00] // จะถูกแทนที่โดยสีที่เลือก
            }
        };

        // Custom colors selected by user
        let customColors = [0x00ff00, 0x00aa00, 0x00dd00];

        class Snake {
            constructor(x, z, isPlayer = false, name = 'Bot', skinKey = 'classic') {
                this.isPlayer = isPlayer;
                this.name = name;

                // ✅ ตรวจสอบว่าเป็น custom colors (มี hex codes) หรือ preset skin
                if (typeof skinKey === 'string' && skinKey.includes('#')) {
                    // Custom colors - ใช้ SKINS.custom ที่ได้อัปเดตแล้ว
                    this.skinKey = 'custom';
                    this.skin = SKINS.custom;
                } else {
                    // Preset skin (classic, fire, ice, gold, rainbow)
                    this.skinKey = skinKey;
                    this.skin = SKINS[skinKey] || SKINS.classic;
                }

                this.segments = [];
                this.segmentPositions = [];

                // สุ่ม direction เริ่มต้น (สำหรับบอท)
                if (!isPlayer) {
                    const randomAngle = Math.random() * Math.PI * 2;
                    this.direction = new THREE.Vector3(
                        Math.cos(randomAngle),
                        0,
                        Math.sin(randomAngle)
                    ).normalize();
                } else {
                    this.direction = new THREE.Vector3(1, 0, 0);
                }

                this.targetDirection = this.direction.clone();
                this.speed = CONFIG.MOVEMENT_SPEED;
                this.isBoosting = false;
                this.length = CONFIG.INITIAL_LENGTH;
                this.score = 0;
                this.alive = true;

                // ✅ ระบบคะแนนแปรผัน: คะแนนที่ต้องการต่อปล้อง
                this.nextGrowthScore = this.calculateNextGrowthScore();

                // ✅ ระบบเติบโตทีละเม็ด (ไม่พร้อมกัน)
                this.pendingGrowth = 0; // จำนวนปล้องที่รอเติบโต
                this.lastGrowthTime = 0; // เวลาที่เติบโตครั้งล่าสุด

                // ✅ ระบบ invincibility 5 วินาที (เมื่อเกิดใหม่) - ทั้งผู้เล่นและบอท
                this.invincible = true; // ทั้งผู้เล่นและบอทมี invincibility ตอนเกิด
                this.invincibleUntil = Date.now() + 5000; // 5 วินาที

                // Create initial segments
                for (let i = 0; i < this.length; i++) {
                    const geometry = new THREE.SphereGeometry(CONFIG.SEGMENT_SIZE, 8, 8);
                    // ใช้สีสลับถ้ามีหลายสี
                    let segmentColor = i === 0 ? this.skin.primary : this.skin.secondary;
                    if (this.skin.colors && this.skin.colors.length > 0 && i > 0) {
                        const colorIndex = i % this.skin.colors.length;
                        segmentColor = this.skin.colors[colorIndex];
                    }

                    const material = new THREE.MeshPhongMaterial({
                        color: segmentColor,
                        // ✅ ทำให้หนอนของผู้เล่นเรืองแสงทั้งตัว (ไฮไลท์)
                        emissive: this.isPlayer ? segmentColor : (i === 0 ? segmentColor : 0x000000),
                        emissiveIntensity: this.isPlayer ? (i === 0 ? 0.5 : 0.25) : (i === 0 ? 0.3 : 0),
                        shininess: 100,
                        // เพิ่มเอฟเฟกต์พิเศษสำหรับผู้เล่น
                        transparent: this.isPlayer,
                        opacity: this.isPlayer ? 0.98 : 1.0,
                    });

                    // สร้าง segment ก่อน
                    const segment = new THREE.Mesh(geometry, material);
                    segment.position.set(x - i, 0.5, z);
                    segment.castShadow = true;

                    // เพิ่ม outline สำหรับผู้เล่น (ไฮไลท์) - หลังจากสร้าง segment แล้ว
                    if (this.isPlayer && i === 0) {
                        // ✅ สร้าง outline effect ที่เด่นขึ้น
                        const outlineGeometry = new THREE.SphereGeometry(CONFIG.SEGMENT_SIZE * 1.3, 8, 8);
                        const outlineMaterial = new THREE.MeshBasicMaterial({
                            color: 0x00ffff, // สีฟ้าเรืองแสง
                            transparent: true,
                            opacity: 0.5, // เพิ่มจาก 0.3 เป็น 0.5
                            side: THREE.BackSide
                        });
                        this.outline = new THREE.Mesh(outlineGeometry, outlineMaterial);
                        this.outline.position.copy(segment.position);
                        scene.add(this.outline);
                    }

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
                // ✅ ตั้งตำแหน่งทันทีก่อน add เข้า scene เพื่อไม่ให้เกิดที่ตรงกลาง
                if (this.segments.length > 0) {
                    this.nameSprite.position.copy(this.segments[0].position);
                    this.nameSprite.position.y = 2;
                }
                scene.add(this.nameSprite);
            }

            update() {
                if (!this.alive) return;

                // Update direction smoothly
                this.direction.lerp(this.targetDirection.normalize(), CONFIG.TURN_SPEED);

                // ✅ บังคับให้ direction เป็น unit vector เสมอ (ไม่สามารถหยุดนิ่งได้)
                this.direction.normalize();

                // ✅ ระบบเติบโตทีละเม็ด (ทุก 100ms = 0.1 วินาที)
                const currentTime = Date.now();
                if (this.pendingGrowth > 0 && (currentTime - this.lastGrowthTime) >= 100) {
                    this.grow(1); // เติบโต 1 ปล้อง
                    this.pendingGrowth--;
                    this.lastGrowthTime = currentTime;

                    // เล่นเสียงเติบโต (แยกจากเสียงกิน)
                    if (this.isPlayer) {
                        playSound('grow');
                    }
                }

                // Move head - ปล้องทุกปล้องดันให้เดินหน้าเสมอ
                const head = this.segments[0];
                const currentSpeed = this.isBoosting ? CONFIG.BOOST_SPEED : this.speed;

                // ✅ บังคับให้เคลื่อนที่ไปข้างหน้าเสมอ (ไม่สามารถใช้เทคนิคขยับๆให้นิ่งได้)
                head.position.x += this.direction.x * currentSpeed;
                head.position.z += this.direction.z * currentSpeed;

                // ✅ ตรวจสอบชนกำแพง (แทน wrap around) - ชนตาย
                const halfWorld = CONFIG.WORLD_SIZE / 2;
                if (head.position.x > halfWorld || head.position.x < -halfWorld ||
                    head.position.z > halfWorld || head.position.z < -halfWorld) {
                    // ชนกำแพง - ตาย!
                    this.die();
                    return;
                }

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

                // Update outline position
                if (this.outline) {
                    this.outline.position.copy(head.position);
                }

                // Rainbow animation for rainbow skin
                if (this.skinKey === 'rainbow') {
                    const hue = (Date.now() * 0.001) % 1;
                    this.segments[0].material.color.setHSL(hue, 1, 0.5);
                }

                // ✅ ระบบกระพริบเมื่อมี invincibility
                if (this.invincible && Date.now() < this.invincibleUntil) {
                    // กระพริบทุก 200ms (5 ครั้ง/วินาที)
                    const blinkInterval = 200;
                    const shouldShow = Math.floor(Date.now() / blinkInterval) % 2 === 0;
                    const targetOpacity = shouldShow ? 0.5 : 0.95;

                    // ปรับ opacity สำหรับ segment ทุกตัว
                    this.segments.forEach(segment => {
                        if (segment.material.transparent) {
                            segment.material.opacity = targetOpacity;
                        }
                    });

                    // ปรับ outline opacity
                    if (this.outline) {
                        this.outline.material.opacity = shouldShow ? 0.2 : 0.5;
                    }
                } else if (this.invincible && Date.now() >= this.invincibleUntil) {
                    // หมดเวลา invincibility - คืนค่า opacity ปกติ
                    this.invincible = false;
                    this.segments.forEach(segment => {
                        if (segment.material.transparent) {
                            segment.material.opacity = this.isPlayer ? 0.98 : 1.0;
                        }
                    });
                    if (this.outline) {
                        this.outline.material.opacity = 0.5;
                    }
                }
            }

            grow(amount = 1) {
                for (let i = 0; i < amount; i++) {
                    const lastSegment = this.segments[this.segments.length - 1];
                    const geometry = new THREE.SphereGeometry(CONFIG.SEGMENT_SIZE, 8, 8);

                    // ใช้สีสลับตามปล้อง (ถ้ามี 3 สี)
                    let segmentColor = this.skin.secondary;
                    if (this.skin.colors && this.skin.colors.length > 0) {
                        const colorIndex = this.segments.length % this.skin.colors.length;
                        segmentColor = this.skin.colors[colorIndex];
                    }

                    const material = new THREE.MeshPhongMaterial({
                        color: segmentColor,
                        shininess: 80,
                        // เพิ่มความโปร่งใสเล็กน้อยถ้าเป็นผู้เล่น
                        transparent: this.isPlayer,
                        opacity: this.isPlayer ? 0.95 : 1.0,
                    });

                    const segment = new THREE.Mesh(geometry, material);
                    segment.position.copy(lastSegment.position);
                    segment.castShadow = true;

                    this.segments.push(segment);
                    this.segmentPositions.push(segment.position.clone());
                    scene.add(segment);
                }

                this.length += amount;

                // ✅ ไม่เล่นเสียงที่นี่ - จะเล่นใน update() แทน (แยกเสียงกินกับเสียงโต)
            }

            /**
             * คำนวณคะแนนที่ต้องการสำหรับปล้องถัดไป
             * สูตร: 10 + Math.floor(length / 10) * 5
             * - ปล้องที่ 1-10: ต้องการ 10 คะแนน
             * - ปล้องที่ 11-20: ต้องการ 15 คะแนน
             * - ปล้องที่ 21-30: ต้องการ 20 คะแนน
             */
            calculateNextGrowthScore() {
                const baseScore = 10;
                const increment = Math.floor(this.length / 10) * 5;
                return baseScore + increment;
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
            console.log('Initializing game...');

            // Check if THREE.js is loaded
            if (typeof THREE === 'undefined') {
                console.error('THREE.js not loaded!');
                alert('เกมโหลดไม่สำเร็จ กรุณารีเฟรชหน้าใหม่');
                return;
            }

            try {
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
                // เริ่มต้นกล้องใกล้ขึ้น
                camera.position.set(0, CONFIG.CAMERA_INITIAL_DISTANCE, CONFIG.CAMERA_INITIAL_DISTANCE);
                camera.lookAt(0, 0, 0);

                // Renderer
                const canvas = document.getElementById('game-canvas');
                if (!canvas) {
                    console.error('Canvas element not found!');
                    return;
                }

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

            // ✅ สร้างบาร์เรียสีแดงรอบขอบแมพ (ชนตาย)
            const halfWorld = CONFIG.WORLD_SIZE / 2;
            const wallHeight = 5;
            const wallThickness = 1;
            const wallMaterial = new THREE.MeshPhongMaterial({
                color: 0xff0000,
                emissive: 0xff0000,
                emissiveIntensity: 0.6,
                transparent: true,
                opacity: 0.7,
            });

            // กำแพงซ้าย (West)
            const wallLeft = new THREE.Mesh(
                new THREE.BoxGeometry(wallThickness, wallHeight, CONFIG.WORLD_SIZE),
                wallMaterial
            );
            wallLeft.position.set(-halfWorld, wallHeight / 2, 0);
            scene.add(wallLeft);

            // กำแพงขวา (East)
            const wallRight = new THREE.Mesh(
                new THREE.BoxGeometry(wallThickness, wallHeight, CONFIG.WORLD_SIZE),
                wallMaterial
            );
            wallRight.position.set(halfWorld, wallHeight / 2, 0);
            scene.add(wallRight);

            // กำแพงหน้า (North)
            const wallFront = new THREE.Mesh(
                new THREE.BoxGeometry(CONFIG.WORLD_SIZE, wallHeight, wallThickness),
                wallMaterial
            );
            wallFront.position.set(0, wallHeight / 2, -halfWorld);
            scene.add(wallFront);

            // กำแพงหลัง (South)
            const wallBack = new THREE.Mesh(
                new THREE.BoxGeometry(CONFIG.WORLD_SIZE, wallHeight, wallThickness),
                wallMaterial
            );
            wallBack.position.set(0, wallHeight / 2, halfWorld);
            scene.add(wallBack);

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

            // Save/Skip score buttons (สำหรับสมาชิกเท่านั้น)
            const saveScoreBtn = document.getElementById('save-score-btn');
            if (saveScoreBtn) {
                saveScoreBtn.addEventListener('click', handleSaveScore);
            }

            const skipSaveBtn = document.getElementById('skip-save-btn');
            if (skipSaveBtn) {
                skipSaveBtn.addEventListener('click', handleSkipSave);
            }

            // Save skin button (สำหรับสมาชิก)
            const saveSkinBtn = document.getElementById('save-skin-btn');
            if (saveSkinBtn) {
                saveSkinBtn.addEventListener('click', handleSaveSkin);
            }

            // Load skin button (สำหรับสมาชิก)
            const loadSkinBtn = document.getElementById('load-skin-btn');
            if (loadSkinBtn) {
                loadSkinBtn.addEventListener('click', handleLoadSkin);
            }

            // Sound toggle
            const soundToggle = document.getElementById('sound-toggle');
            if (soundToggle) {
                soundToggle.addEventListener('click', function() {
                    soundEnabled = !soundEnabled;
                    this.textContent = soundEnabled ? '🔊' : '🔇';
                    this.classList.toggle('muted', !soundEnabled);
                });
            }

            // Skin selection
            document.querySelectorAll('.skin-option').forEach(option => {
                option.addEventListener('click', function() {
                    if (!this.classList.contains('locked')) {
                        document.querySelectorAll('.skin-option').forEach(o => o.classList.remove('selected'));
                        this.classList.add('selected');
                        selectedSkin = this.dataset.skin;

                        // ✅ บันทึกสี skin (ถ้าเป็นสมาชิก)
                        saveSkinPreference(selectedSkin);
                    }
                });
            });

            // Select default skin
            document.querySelector('.skin-option').classList.add('selected');

            // Custom color picker
            const applyCustomColorsBtn = document.getElementById('apply-custom-colors');
            if (applyCustomColorsBtn) {
                applyCustomColorsBtn.addEventListener('click', function() {
                    const color1 = document.getElementById('color1').value;
                    const color2 = document.getElementById('color2').value;
                    const color3 = document.getElementById('color3').value;

                    // แปลง hex เป็น integer
                    customColors = [
                        parseInt(color1.replace('#', '0x')),
                        parseInt(color2.replace('#', '0x')),
                        parseInt(color3.replace('#', '0x'))
                    ];

                    // อัปเดต custom skin
                    SKINS.custom.colors = customColors;
                    SKINS.custom.primary = customColors[0];
                    SKINS.custom.secondary = customColors[1];

                    // เลือก custom skin
                    const customSkinString = `${color1},${color2},${color3}`; // ✅ บันทึก hex codes จริงๆ
                    selectedSkin = customSkinString;
                    document.querySelectorAll('.skin-option').forEach(o => o.classList.remove('selected'));

                    // ✅ บันทึกสี custom พร้อม hex codes (ถ้าเป็นสมาชิก)
                    saveSkinPreference(customSkinString);

                    alert('✅ ใช้สีของคุณเองแล้ว!\n\nสี 1: ' + color1 + '\nสี 2: ' + color2 + '\nสี 3: ' + color3);
                });
            }

            // Start animation loop
            animate();

            // ✅ โหลดสี skin ที่สมาชิกบันทึกไว้
            if (isAuthenticated) {
                loadSavedSkin();
            }

            console.log('Game initialization complete!');

            } catch (error) {
                console.error('Error initializing game:', error);
                alert('เกมโหลดไม่สำเร็จ: ' + error.message);
            }
        }

        /**
         * โหลดสี skin ที่สมาชิกบันทึกไว้
         */
        async function loadSavedSkin() {
            try {
                const response = await fetch('/api/games/snake-io/get-skin-preference', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    credentials: 'same-origin'
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.success && data.skin) {
                        // ✅ ตรวจสอบว่าเป็น custom colors (มี hex code) หรือ preset skin
                        if (data.skin.includes('#')) {
                            // Custom colors - แยกสีและนำไปใส่ใน color inputs
                            const colors = data.skin.split(',');

                            // ใส่สีใน color inputs
                            if (colors[0]) document.getElementById('color1').value = colors[0];
                            if (colors[1]) document.getElementById('color2').value = colors[1];
                            if (colors[2]) document.getElementById('color3').value = colors[2];

                            // อัพเดต customColors และ SKINS.custom
                            customColors = [
                                parseInt(colors[0]?.replace('#', '0x') || '0x00ff00'),
                                parseInt(colors[1]?.replace('#', '0x') || '0x00aa00'),
                                parseInt(colors[2]?.replace('#', '0x') || '0x00dd00')
                            ];
                            SKINS.custom.colors = customColors;
                            SKINS.custom.primary = customColors[0];
                            SKINS.custom.secondary = customColors[1];

                            // ตั้ง selectedSkin เป็น custom colors string
                            selectedSkin = data.skin;

                            console.log('[Skin] โหลดสี custom อัตโนมัติ:', colors);
                        } else {
                            // Preset skin (classic, fire, ice, gold, rainbow)
                            selectedSkin = data.skin;

                            // เลือก skin option ที่ตรงกับสีที่บันทึกไว้
                            document.querySelectorAll('.skin-option').forEach(option => {
                                option.classList.remove('selected');
                                if (option.dataset.skin === data.skin) {
                                    option.classList.add('selected');
                                }
                            });

                            console.log('[Skin] โหลดสี preset อัตโนมัติ:', data.skin);
                        }
                    }
                }
            } catch (error) {
                console.warn('[Skin] ไม่สามารถโหลดสีที่บันทึกไว้:', error.message);
            }
        }

        /**
         * บันทึกสี skin ที่เลือก (สำหรับสมาชิก)
         */
        async function saveSkinPreference(skin) {
            if (!isAuthenticated) {
                return; // Guest ไม่ต้องบันทึก
            }

            try {
                const response = await fetch('/api/games/snake-io/save-skin-preference', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ skin })
                });

                if (response.ok) {
                    const data = await response.json();
                    console.log('[Skin] บันทึกสีสำเร็จ:', skin);
                }
            } catch (error) {
                console.warn('[Skin] ไม่สามารถบันทึกสี:', error.message);
            }
        }

        function createFood(x = null, z = null, fromDeath = false) {
            const geometry = new THREE.SphereGeometry(0.3, 6, 6);
            // ✅ อาหารจากตาย = RGB เรืองแสง, อาหารธรรมดา = เหลือง/ชมพู
            const color = fromDeath ? 0xff0000 : // เริ่มจากสีแดง (RGB จะเปลี่ยนทุกเฟรม)
                          (Math.random() > 0.7 ? 0xffff00 : 0xff00ff);
            const material = new THREE.MeshPhongMaterial({
                color: color,
                emissive: color,
                emissiveIntensity: fromDeath ? 0.8 : 0.5, // เรืองแสงเยอะกว่า
            });

            const food = new THREE.Mesh(geometry, material);

            if (x === null || z === null) {
                const halfWorld = CONFIG.WORLD_SIZE / 2 - 10;
                x = (Math.random() - 0.5) * halfWorld * 2;
                z = (Math.random() - 0.5) * halfWorld * 2;
            }

            food.position.set(x, 0.3, z);
            food.userData.value = fromDeath ? 2 : 1;
            food.userData.isRGB = fromDeath; // ✅ Flag สำหรับ animate RGB

            // ✅ เพิ่มเวลาสร้างและหมดอายุ (อาหารจากตายหายใน 30 วินาที)
            if (fromDeath) {
                food.userData.createdAt = Date.now();
                food.userData.expiresAt = Date.now() + 30000; // 30 วินาที
            }

            scene.add(food);
            foods.push(food);
        }

        function createBot() {
            const halfWorld = CONFIG.WORLD_SIZE / 2 - 20;
            const x = (Math.random() - 0.5) * halfWorld * 2;
            const z = (Math.random() - 0.5) * halfWorld * 2;

            const names = ['Alpha', 'Beta', 'Gamma', 'Delta', 'Epsilon', 'Zeta', 'Eta', 'Theta'];
            const baseName = names[Math.floor(Math.random() * names.length)] + Math.floor(Math.random() * 100);
            const name = '(AI) ' + baseName; // ✅ เพิ่ม (AI) ข้างหน้าชื่อบอท
            const skinKeys = Object.keys(SKINS);
            const skin = skinKeys[Math.floor(Math.random() * skinKeys.length)];

            const bot = new Snake(x, z, false, name, skin);
            bots.push(bot);
        }

        /**
         * Create power-up item
         */
        function createPowerup() {
            const types = [POWERUP_TYPES.MAGNET, POWERUP_TYPES.SPEED, POWERUP_TYPES.MULTIPLIER];
            const type = types[Math.floor(Math.random() * types.length)];

            const halfWorld = CONFIG.WORLD_SIZE / 2 - 15;
            const x = (Math.random() - 0.5) * halfWorld * 2;
            const z = (Math.random() - 0.5) * halfWorld * 2;

            // Create spinning cube for powerup
            const geometry = new THREE.BoxGeometry(0.8, 0.8, 0.8);
            const material = new THREE.MeshPhongMaterial({
                color: type.color,
                emissive: type.color,
                emissiveIntensity: 0.5,
                shininess: 100,
            });

            const powerup = new THREE.Mesh(geometry, material);
            powerup.position.set(x, 0.8, z);
            powerup.userData.type = type.name;
            powerup.userData.icon = type.icon;
            powerup.userData.duration = type.duration;

            scene.add(powerup);
            powerups.push(powerup);
        }

        /**
         * Activate a power-up
         */
        function activatePowerup(type) {
            const powerupType = Object.values(POWERUP_TYPES).find(p => p.name === type);
            if (!powerupType) return;

            // Clear existing powerup of same type
            if (activePowerups[type]) {
                clearTimeout(activePowerups[type].timeout);
            }

            // Activate new powerup
            const endTime = Date.now() + powerupType.duration;
            activePowerups[type] = {
                endTime: endTime,
                timeout: setTimeout(() => {
                    deactivatePowerup(type);
                }, powerupType.duration)
            };

            updatePowerupUI();
            playSound('grow');
        }

        /**
         * Deactivate a power-up
         */
        function deactivatePowerup(type) {
            if (activePowerups[type]) {
                clearTimeout(activePowerups[type].timeout);
                activePowerups[type] = null;
                updatePowerupUI();
            }
        }

        /**
         * Update power-up UI indicators
         */
        function updatePowerupUI() {
            const container = document.getElementById('powerup-indicators');
            container.innerHTML = '';

            Object.entries(activePowerups).forEach(([type, data]) => {
                if (!data) return;

                const powerupType = Object.values(POWERUP_TYPES).find(p => p.name === type);
                const timeLeft = Math.ceil((data.endTime - Date.now()) / 1000);

                const div = document.createElement('div');
                div.className = `powerup-active ${type}`;
                div.innerHTML = `
                    <span class="powerup-icon">${powerupType.icon}</span>
                    <span class="powerup-time">${timeLeft}s</span>
                `;
                container.appendChild(div);
            });
        }

        /**
         * Check collision between two snakes
         * Returns the snake that should die, or null if no collision
         */
        function checkSnakeCollision(snake1, snake2) {
            if (!snake1.alive || !snake2.alive) return null;

            // ✅ ข้ามการชนถ้ามี invincibility (5 วินาทีแรก)
            if (snake1.invincible || snake2.invincible) {
                return null; // ไม่มีการชน
            }

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
                    // ✅ ไม่สร้างบอทใหม่ทันที ให้ระบบ maintain bot count จัดการ
                    return; // Only one death per frame
                }
            }

            // Check bot vs bot collisions (Optimized with spatial culling)
            for (let i = bots.length - 1; i >= 0; i--) {
                const bot1 = bots[i];
                if (!bot1.alive) continue;

                const bot1Head = bot1.segments[0].position;

                for (let j = i - 1; j >= 0; j--) {
                    const bot2 = bots[j];
                    if (!bot2.alive) continue;

                    // ✅ Spatial Culling - ข้ามการเช็คถ้าบอทอยู่ห่างกันมากกว่า 20 หน่วย (ลด collision checks ลง 80%!)
                    const bot2Head = bot2.segments[0].position;
                    const roughDistance = bot1Head.distanceTo(bot2Head);
                    if (roughDistance > 20) continue; // ไกลเกินไป ไม่มีทางชนได้

                    const victim = checkSnakeCollision(bot1, bot2);
                    if (victim) {
                        victim.die();
                        // ✅ ไม่สร้างบอทใหม่ทันที ให้ระบบ maintain bot count จัดการ
                        return; // Only one death per frame
                    }
                }
            }
        }

        /**
         * อัปเดตสถานะการเชื่อมต่อ
         */
        function updateConnectionStatus(status, text) {
            const statusEl = document.getElementById('connection-status');
            const statusText = statusEl.querySelector('.status-text');

            statusEl.classList.remove('online', 'offline');
            statusEl.classList.add(status, 'show');
            statusText.textContent = text;

            // อัปเดตสถานะ global
            const wasOnline = isOnline;
            isOnline = (status === 'online');

            // แจ้งเตือนเมื่อสถานะเปลี่ยน
            if (wasOnline !== isOnline && gameStarted) {
                if (isOnline) {
                    console.log('🟢 [Connection] กลับมา ONLINE แล้ว!');
                } else {
                    console.log('🔴 [Connection] หลุดเป็น OFFLINE');
                }
            }

            console.log(`[Connection] ${status.toUpperCase()}: ${text}`);
        }

        /**
         * เช็คสถานะการเชื่อมต่อ
         */
        async function checkConnection() {
            if (!multiplayerManager) {
                return false;
            }

            try {
                // ลองเช็คว่ายังเชื่อมต่ออยู่หรือไม่
                const response = await fetch(`${multiplayerManager.apiBaseUrl}/ping`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    signal: AbortSignal.timeout(2000) // timeout 2 วินาที
                });

                const data = await response.json();
                return data.success === true;
            } catch (error) {
                console.warn('[Connection] Ping failed:', error.message);
                return false;
            }
        }

        /**
         * ✅ ปิด reconnect (ไม่ใช้ multiplayer แล้ว)
         */
        async function attemptReconnect() {
            // ไม่ทำอะไร - เล่นแบบ offline อย่างเดียว
            console.log('[Connection] OFFLINE MODE - ไม่มี reconnection');
            return false;
        }

        /**
         * ✅ ปิด connection monitoring (ไม่ใช้ multiplayer แล้ว)
         */
        function startConnectionMonitoring() {
            // ไม่ทำอะไร - เล่นแบบ offline อย่างเดียว
            console.log('[Connection] OFFLINE MODE - ไม่มี connection monitoring');
        }

        /**
         * หยุดตรวจสอบการเชื่อมต่อ
         */
        function stopConnectionMonitoring() {
            if (connectionMonitorInterval) {
                clearInterval(connectionMonitorInterval);
                connectionMonitorInterval = null;
                console.log('[Connection] หยุดตรวจสอบการเชื่อมต่อ');
            }
        }

        async function startGame() {
            console.log('Starting game...');

            // Initialize audio on user interaction
            initAudio();

            playerName = document.getElementById('player-name').value.trim() ||
                         (isAuthenticated ? '{{ Auth::user()->name ?? "Player" }}' : 'Player');

            try {
                // ✅ เล่นแบบ OFFLINE เท่านั้น (ปิด multiplayer เพื่อป้องกันเกมค้าง)
                updateConnectionStatus('offline', '🤖 OFFLINE MODE');
                multiplayerManager = null;
                isOnline = false;

                console.log('[Game] โหมด OFFLINE - เล่นกับบอทเท่านั้น');

                // Create player
                player = new Snake(0, 0, true, playerName, selectedSkin);
                console.log('Player created:', playerName);

                // Spawn bots (30 บอท)
                const botsNeeded = CONFIG.BOT_COUNT;
                for (let i = 0; i < botsNeeded; i++) {
                    createBot();
                }
                console.log('Bots spawned:', botsNeeded);

                // เริ่ม optimized touch input
                try {
                    if (touchInputManager) {
                        touchInputManager.destroy();
                    }

                    // ตรวจสอบว่า OptimizedTouchInput มีอยู่จริง
                    if (typeof OptimizedTouchInput !== 'undefined') {
                        touchInputManager = new OptimizedTouchInput(camera, player.segments[0]);
                        touchInputManager.init(renderer.domElement);

                        // ตั้งค่า callbacks
                        touchInputManager.setOnDirectionChange((direction) => {
                            if (player && player.alive) {
                                player.targetDirection.copy(direction);
                            }
                        });

                        touchInputManager.setOnBoostStart(() => {
                            if (player && player.alive && !player.isBoosting) {
                                player.isBoosting = true;
                                playSound('boost');
                            }
                        });

                        touchInputManager.setOnBoostEnd(() => {
                            if (player) {
                                player.isBoosting = false;
                            }
                        });

                        console.log('[TouchInput] เปิดใช้งาน optimized touch input');
                    } else {
                        console.warn('[TouchInput] OptimizedTouchInput ไม่พร้อมใช้งาน ใช้ touch events ธรรมดา');
                    }
                } catch (touchError) {
                    console.warn('[TouchInput] เปิดใช้งานไม่สำเร็จ:', touchError.message);
                }

                gameStarted = true;
                document.getElementById('start-screen').classList.add('hidden');

                // ✅ ไม่ใช้ connection monitoring (เล่นแบบ offline อย่างเดียว)
                // startConnectionMonitoring(); // ปิดการใช้งาน

                console.log('✅ Game started successfully!');
            } catch (error) {
                console.error('[Game] เริ่มเกมล้มเหลว:', error);
                alert('เกิดข้อผิดพลาด: ' + error.message + '\n\nกรุณารีเฟรชหน้าใหม่');
            }
        }

        async function endGame() {
            gameOver = true;
            gameStarted = false;

            // หยุดตรวจสอบการเชื่อมต่อ
            stopConnectionMonitoring();

            document.getElementById('final-score').textContent = score;
            document.getElementById('final-length').textContent = player.length;
            document.getElementById('final-rank').textContent = '#' + getRank();
            document.getElementById('game-over').classList.add('show');

            // แจ้ง multiplayer manager
            if (multiplayerManager) {
                await multiplayerManager.playerDied();
            }

            // ตรวจสอบ wallet status
            await checkWalletStatus();
        }

        async function checkWalletStatus() {
            // สำหรับ Guest ไม่ต้องทำอะไร (UI แสดงอยู่แล้ว)
            if (!isAuthenticated) {
                return;
            }

            // สำหรับ Member - ดึงข้อมูล wallet
            const walletStatusEl = document.getElementById('wallet-status');
            const saveBtnEl = document.getElementById('save-score-btn');
            const skipBtnEl = document.getElementById('skip-save-btn');

            if (!walletStatusEl || !saveBtnEl) {
                console.warn('[Wallet] ไม่พบ elements');
                return;
            }

            try {
                // ดึงข้อมูล wallet จาก API
                const response = await fetch('/api/games/snake-io/check-wallet', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    throw new Error('ไม่สามารถดึงข้อมูล wallet ได้');
                }

                const data = await response.json();
                const balance = parseFloat(data.balance || 0);
                const canSave = data.can_save_score !== false;
                const requiredPoints = 1;

                console.log('[Wallet] Balance:', balance, 'แต้ม', 'Can save:', canSave);

                if (balance >= requiredPoints) {
                    // แต้มพอ - อนุญาตให้บันทึก
                    walletStatusEl.innerHTML = `
                        <p style="color: #00ff00; font-size: 14px;">
                            💰 แต้มคงเหลือ: <span style="font-weight: bold; font-size: 16px;">${balance.toFixed(2)}</span> แต้ม
                        </p>
                        <p style="color: #ccc; font-size: 12px; margin-top: 5px;">
                            หลังบันทึกจะเหลือ: ${(balance - requiredPoints).toFixed(2)} แต้ม
                        </p>
                    `;
                    saveBtnEl.disabled = false;
                } else {
                    // แต้มไม่พอ - ต้องเติมเงิน
                    walletStatusEl.innerHTML = `
                        <p style="color: #ff4444; font-size: 14px;">
                            ⚠️ แต้มไม่เพียงพอ!
                        </p>
                        <p style="color: #ccc; font-size: 13px; margin: 8px 0;">
                            แต้มคงเหลือ: <span style="color: #ffaa00; font-weight: bold;">${balance.toFixed(2)}</span> แต้ม<br>
                            ต้องการ: <span style="color: #ff4444; font-weight: bold;">${requiredPoints}</span> แต้ม
                        </p>
                        <a href="/user/wallet/topup" class="btn" style="background: linear-gradient(135deg, #ffaa00, #ff6600); text-decoration: none; display: inline-block; padding: 8px 16px; font-size: 13px; margin-top: 5px;">
                            💳 เติมเงิน
                        </a>
                    `;
                    saveBtnEl.disabled = true;
                }
            } catch (error) {
                console.error('[Wallet] Error:', error);
                walletStatusEl.innerHTML = `
                    <p style="color: #ff4444; font-size: 14px;">
                        ⚠️ ไม่สามารถดึงข้อมูล wallet ได้
                    </p>
                    <p style="color: #ccc; font-size: 12px; margin-top: 5px;">
                        ${error.message}
                    </p>
                `;
                saveBtnEl.disabled = true;
            }
        }

        async function handleSaveScore() {
            if (!isAuthenticated) {
                alert('กรุณาเข้าสู่ระบบก่อนบันทึกคะแนน');
                return;
            }

            const saveBtnEl = document.getElementById('save-score-btn');
            const skipBtnEl = document.getElementById('skip-save-btn');

            // Disable buttons
            saveBtnEl.disabled = true;
            skipBtnEl.disabled = true;
            const originalText = saveBtnEl.textContent;
            saveBtnEl.textContent = '⏳ กำลังบันทึก...';

            try {
                // บันทึกคะแนนและหัก wallet
                const response = await fetch('/api/games/snake-io/save-score', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        score: score,
                        length: player.length,
                        rank: getRank()
                    })
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || 'บันทึกคะแนนล้มเหลว');
                }

                const result = await response.json();

                if (result.success) {
                    alert(`✅ บันทึกคะแนนสำเร็จ!\n\nคะแนน: ${score}\nความยาว: ${player.length}\n\n💰 แต้มคงเหลือ: ${result.remaining_balance} แต้ม`);
                    document.getElementById('save-score-section').style.display = 'none';
                } else {
                    throw new Error(result.message || 'บันทึกล้มเหลว');
                }
            } catch (error) {
                console.error('[Save Score] Error:', error);
                alert('❌ เกิดข้อผิดพลาด: ' + error.message);
                saveBtnEl.disabled = false;
                skipBtnEl.disabled = false;
                saveBtnEl.textContent = originalText;
            }
        }

        function handleSkipSave() {
            document.getElementById('save-score-section').style.display = 'none';
        }

        /**
         * บันทึกสี skin ของสมาชิก
         */
        async function handleSaveSkin() {
            const saveSkinBtn = document.getElementById('save-skin-btn');
            const statusEl = document.getElementById('save-skin-status');

            if (!saveSkinBtn || !selectedSkin) return;

            saveSkinBtn.disabled = true;
            const originalText = saveSkinBtn.textContent;
            saveSkinBtn.textContent = '⏳ กำลังบันทึก...';
            statusEl.textContent = '';

            try {
                const response = await fetch('/api/games/snake-io/save-skin-preference', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        skin: selectedSkin
                    })
                });

                const data = await response.json();

                if (data.success) {
                    statusEl.textContent = '✅ บันทึกสำเร็จ!';
                    statusEl.style.color = '#00ff00';

                    setTimeout(() => {
                        statusEl.textContent = '';
                    }, 3000);
                } else {
                    throw new Error(data.message || 'บันทึกล้มเหลว');
                }
            } catch (error) {
                console.error('[Save Skin] Error:', error);
                statusEl.textContent = '❌ ' + error.message;
                statusEl.style.color = '#ff4444';
            } finally {
                saveSkinBtn.disabled = false;
                saveSkinBtn.textContent = originalText;
            }
        }

        /**
         * โหลดสี skin ที่บันทึกไว้
         */
        async function handleLoadSkin() {
            const loadSkinBtn = document.getElementById('load-skin-btn');
            const statusEl = document.getElementById('save-skin-status');

            if (!loadSkinBtn) return;

            loadSkinBtn.disabled = true;
            const originalText = loadSkinBtn.textContent;
            loadSkinBtn.textContent = '⏳ กำลังโหลด...';
            statusEl.textContent = '';

            try {
                const response = await fetch('/api/games/snake-io/get-skin-preference', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    credentials: 'same-origin'
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.success && data.skin) {
                        selectedSkin = data.skin;

                        // ✅ ตรวจสอบว่าเป็น custom colors (มี hex code) หรือ preset skin
                        if (data.skin.includes('#')) {
                            // Custom colors - แยกสีและนำไปใส่ใน color inputs
                            const colors = data.skin.split(',');

                            // ใส่สีใน color inputs
                            if (colors[0]) document.getElementById('color1').value = colors[0];
                            if (colors[1]) document.getElementById('color2').value = colors[1];
                            if (colors[2]) document.getElementById('color3').value = colors[2];

                            // อัพเดต customColors และ SKINS.custom
                            customColors = [
                                parseInt(colors[0]?.replace('#', '0x') || '0x00ff00'),
                                parseInt(colors[1]?.replace('#', '0x') || '0x00aa00'),
                                parseInt(colors[2]?.replace('#', '0x') || '0x00dd00')
                            ];
                            SKINS.custom.colors = customColors;
                            SKINS.custom.primary = customColors[0];
                            SKINS.custom.secondary = customColors[1];

                            // เลือก custom skin option
                            document.querySelectorAll('.skin-option').forEach(option => {
                                option.classList.remove('selected');
                                if (option.dataset.skin === 'custom') {
                                    option.classList.add('selected');
                                }
                            });
                            selectedSkin = 'custom';

                            statusEl.textContent = '✅ โหลดสี Custom สำเร็จ!';
                            statusEl.style.color = '#00ff00';

                            console.log('[Skin] โหลดสี custom:', colors);
                        } else {
                            // Preset skin (classic, fire, ice, gold, rainbow)
                            document.querySelectorAll('.skin-option').forEach(option => {
                                option.classList.remove('selected');
                                if (option.dataset.skin === data.skin) {
                                    option.classList.add('selected');
                                }
                            });

                            statusEl.textContent = '✅ โหลดสีสำเร็จ: ' + data.skin;
                            statusEl.style.color = '#00ff00';

                            console.log('[Skin] โหลดสี preset:', data.skin);
                        }

                        setTimeout(() => {
                            statusEl.textContent = '';
                        }, 3000);
                    } else {
                        throw new Error('ไม่พบสีที่บันทึกไว้');
                    }
                } else {
                    throw new Error('ไม่สามารถโหลดสีได้');
                }
            } catch (error) {
                console.error('[Load Skin] Error:', error);
                statusEl.textContent = '❌ ' + error.message;
                statusEl.style.color = '#ff4444';
            } finally {
                loadSkinBtn.disabled = false;
                loadSkinBtn.textContent = originalText;
            }
        }

        function restartGame() {
            // หยุดตรวจสอบการเชื่อมต่อ
            stopConnectionMonitoring();

            // Clean up
            if (player) player.die();
            bots.forEach(bot => {
                bot.segments.forEach(seg => scene.remove(seg));
                if (bot.nameSprite) scene.remove(bot.nameSprite);
            });
            bots = [];

            foods.forEach(food => scene.remove(food));
            foods = [];

            // Reset multiplayer
            if (multiplayerManager) {
                multiplayerManager.disconnectWebSocket();
                multiplayerManager = null;
            }

            // ซ่อนสถานะการเชื่อมต่อ
            const statusEl = document.getElementById('connection-status');
            statusEl.classList.remove('show', 'online', 'offline');

            // Reset connection state
            isOnline = false;
            reconnectAttempts = 0;

            // ✅ รีเซ็ต save-score-section เพื่อให้แสดงในรอบถัดไป
            const saveScoreSection = document.getElementById('save-score-section');
            if (saveScoreSection) {
                saveScoreSection.style.display = ''; // รีเซ็ตกลับเป็นค่าเริ่มต้น
            }

            // ✅ รีเซ็ต wallet status สำหรับสมาชิก
            const walletStatusEl = document.getElementById('wallet-status');
            if (walletStatusEl) {
                walletStatusEl.innerHTML = '<p style="color: #ccc; font-size: 14px;">⏳ กำลังโหลดข้อมูล...</p>';
            }

            // ✅ รีเซ็ตปุ่ม save และ skip
            const saveBtnEl = document.getElementById('save-score-btn');
            const skipBtnEl = document.getElementById('skip-save-btn');
            if (saveBtnEl) {
                saveBtnEl.disabled = false;
                saveBtnEl.textContent = '✅ บันทึกคะแนน (1 แต้ม)';
            }
            if (skipBtnEl) {
                skipBtnEl.disabled = false;
            }

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

        /**
         * ตรวจจับอันตรายข้างหน้าบอท (งูอื่น)
         *
         * @param {Snake} bot บอทที่ต้องการตรวจสอบ
         * @param {THREE.Vector3} direction ทิศทางที่กำลังมุ่งหน้า
         * @return {Object|null} { snake, distance } ของงูที่อันตราย หรือ null
         */
        function detectDangerAhead(bot, direction) {
            const head = bot.segments[0].position;
            const lookAheadDistance = 8; // มองไปข้างหน้า 8 หน่วย

            // คำนวณตำแหน่งที่จะไปถึง
            const futurePos = new THREE.Vector3()
                .copy(head)
                .add(direction.clone().multiplyScalar(lookAheadDistance));

            let nearestDanger = null;
            let nearestDangerDistance = Infinity;

            // ตรวจสอบงูทั้งหมด (ผู้เล่น + บอทอื่น)
            const allSnakes = player ? [player, ...bots] : bots;

            allSnakes.forEach(snake => {
                if (snake === bot || !snake.alive) return;

                // ตรวจสอบทุก segment ของงู
                snake.segments.forEach((segment, index) => {
                    const distance = futurePos.distanceTo(segment.position);

                    // ถ้าใกล้เกินไป (< 3 หน่วย) = อันตราย!
                    if (distance < 3 && distance < nearestDangerDistance) {
                        nearestDangerDistance = distance;
                        nearestDanger = {
                            snake: snake,
                            distance: distance,
                            segmentIndex: index
                        };
                    }
                });
            });

            return nearestDanger;
        }

        /**
         * ตรวจสอบโอกาสตัดหน้า (เจอเหยื่อที่เล็กกว่า)
         *
         * @param {Snake} bot บอทที่ต้องการตรวจสอบ
         * @return {Snake|null} งูที่สามารถตัดหน้าได้
         */
        function findCutoffTarget(bot) {
            const head = bot.segments[0].position;
            const cutoffRange = 10; // ระยะที่พิจารณาตัดหน้า
            let bestTarget = null;
            let bestScore = -Infinity;

            // หาผู้เล่นหรือบอทที่เล็กกว่า
            const allSnakes = player ? [player, ...bots] : bots;

            allSnakes.forEach(snake => {
                if (snake === bot || !snake.alive) return;

                const targetHead = snake.segments[0].position;
                const distance = head.distanceTo(targetHead);

                // เงื่อนไขตัดหน้า:
                // 1. อยู่ในระยะ (< 10)
                // 2. บอทใหญ่กว่าเป้าหมาย (length > target.length)
                // 3. ยิ่งเป้าหมายเล็ก ยิ่งดี (สำหรับผู้เล่น ให้คะแนนพิเศษ)
                if (distance < cutoffRange && bot.length > snake.length) {
                    let score = (bot.length - snake.length) / distance;

                    // ✅ ให้คะแนนพิเศษถ้าเป้าหมายเป็นผู้เล่น (ตัดหน้าผู้เล่นเป็นพิเศษ!)
                    if (snake.isPlayer) {
                        score *= 2.5; // เพิ่ม priority ให้ตัดหน้าผู้เล่น
                    }

                    if (score > bestScore) {
                        bestScore = score;
                        bestTarget = snake;
                    }
                }
            });

            return bestTarget;
        }

        /**
         * หาทิศทางหลบอันตราย
         *
         * @param {THREE.Vector3} currentDirection ทิศทางปัจจุบัน
         * @param {THREE.Vector3} dangerPosition ตำแหน่งอันตราย
         * @param {THREE.Vector3} botPosition ตำแหน่งบอท
         * @return {THREE.Vector3} ทิศทางใหม่ที่หลบ
         */
        function getAvoidanceDirection(currentDirection, dangerPosition, botPosition) {
            // คำนวณทิศทางหนีจากอันตราย
            const awayFromDanger = new THREE.Vector3()
                .subVectors(botPosition, dangerPosition)
                .normalize();

            // ผสมทิศทางเดิมกับทิศทางหนี (70% หนี + 30% เดิม)
            const blended = new THREE.Vector3()
                .addVectors(
                    awayFromDanger.multiplyScalar(0.7),
                    currentDirection.clone().multiplyScalar(0.3)
                )
                .normalize();

            return blended;
        }

        function updateBots() {
            // ✅ อัปเดตเฉพาะบอทที่มีชีวิต และลบบอทที่ตายออกจาก array
            bots = bots.filter(bot => {
                if (!bot || !bot.alive) {
                    // ลบบอทที่ตายออกจาก scene
                    if (bot && bot.segments) {
                        bot.segments.forEach(seg => scene.remove(seg));
                        if (bot.nameSprite) scene.remove(bot.nameSprite);
                    }
                    return false; // ลบออกจาก array
                }
                return true; // เก็บไว้
            });

            // อัปเดตบอทที่ยังมีชีวิต
            bots.forEach(bot => {
                const head = bot.segments[0].position;

                // ✅ พฤติกรรม 1: ตรวจสอบโอกาสตัดหน้า (Aggressive AI)
                const cutoffTarget = findCutoffTarget(bot);

                let targetDirection = null;

                if (cutoffTarget) {
                    // ✅ มีเป้าหมายให้ตัดหน้า - เข้าใส่!
                    const targetHead = cutoffTarget.segments[0].position;

                    // คำนวณตำแหน่งตัดหน้า (ข้างหน้าเป้าหมายเล็กน้อย)
                    const targetFuturePos = new THREE.Vector3()
                        .copy(targetHead)
                        .add(cutoffTarget.direction.clone().multiplyScalar(3));

                    targetDirection = new THREE.Vector3()
                        .subVectors(targetFuturePos, head)
                        .normalize();

                    // ✅ ใช้ boost เพื่อเร่งตัดหน้า!
                    if (bot.score >= 10) { // ต้องมีแต้มพอ
                        bot.isBoosting = true;
                    }
                } else {
                    // ไม่มีเป้าหมายตัดหน้า - หยุด boost
                    bot.isBoosting = false;

                    // ✅ พฤติกรรม 2: หาอาหารใกล้ที่สุด (Optimized - เช็คแค่ 30 อันแรกแทนที่จะทั้งหมด)
                    let nearestFood = null;
                    let nearestDistance = Infinity;

                    // ✅ เช็คแค่ 30 อาหารแรก แทนที่จะ 100 ทั้งหมด (ลด CPU ลง 70%!)
                    const foodSampleSize = Math.min(30, foods.length);
                    for (let i = 0; i < foodSampleSize; i++) {
                        const food = foods[i];
                        const distance = head.distanceTo(food.position);
                        if (distance < nearestDistance) {
                            nearestDistance = distance;
                            nearestFood = food;
                        }
                    }

                    if (nearestFood) {
                        targetDirection = new THREE.Vector3()
                            .subVectors(nearestFood.position, head)
                            .normalize();
                    }
                }

                // ✅ พฤติกรรม 3: หลบอันตราย (Collision Avoidance) - สำคัญที่สุด!
                if (targetDirection) {
                    const danger = detectDangerAhead(bot, targetDirection);

                    if (danger) {
                        // มีอันตราย! หลบ!
                        const dangerPos = danger.snake.segments[danger.segmentIndex].position;
                        targetDirection = getAvoidanceDirection(targetDirection, dangerPos, head);

                        // ✅ ถ้าอันตรายใกล้มาก ให้ใช้ boost หนี!
                        if (danger.distance < 2 && bot.score >= 5) {
                            bot.isBoosting = true;
                        }
                    }

                    bot.targetDirection.copy(targetDirection);
                }

                // อัปเดตตำแหน่งบอท
                bot.update();

                // Check food collision
                for (let i = foods.length - 1; i >= 0; i--) {
                    const food = foods[i];
                    if (head.distanceTo(food.position) < 1) {
                        const foodValue = food.userData.value || 1;

                        // ✅ บอทกินอาหาร - ใช้ระบบเดียวกับผู้เล่น (ต้องกิน 10, 15, 20... คะแนนต่อปล้อง)
                        bot.score += foodValue;

                        // ✅ ตรวจสอบว่าคะแนนพอเติบโตหรือยัง - เพิ่มเข้าคิว (ไม่เติบโตทันที)
                        while (bot.score >= bot.nextGrowthScore) {
                            bot.pendingGrowth++; // เพิ่มคิวการเติบโต (จะค่อยๆ โตทีละเม็ดใน update())
                            bot.nextGrowthScore = bot.calculateNextGrowthScore();
                        }

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

            // Animate powerups (spin)
            powerups.forEach(powerup => {
                powerup.rotation.y += 0.05;
                powerup.position.y = 0.8 + Math.sin(Date.now() * 0.003) * 0.2;
            });

            // ✅ ปิด multiplayer ทั้งหมด (เพื่อป้องกันเกมค้าง)
            // Multiplayer ถูกปิดการใช้งาน - เล่นแบบ offline กับบอทเท่านั้น
            const currentTime = Date.now();

            if (player && player.alive) {
                // Apply speed boost
                const speedMultiplier = activePowerups.speed ? 1.5 : 1;
                player.speed = CONFIG.MOVEMENT_SPEED * speedMultiplier;

                player.update();

                // ✅ หักแต้มเมื่อเร่งความเร็ว 3 แต้ม/วินาที (ไม่มีทศนิยม)
                if (player.isBoosting) {
                    const pointsPerSecond = 3;
                    const deltaTime = 1 / 60; // 60 FPS
                    const deduction = pointsPerSecond * deltaTime;

                    // หักคะแนนและใช้ Math.floor เพื่อไม่ให้มีทศนิยม
                    player.score = Math.floor(player.score - deduction);

                    // หยุด boost ถ้าแต้มไม่พอ
                    if (player.score < 3) {
                        player.score = Math.max(0, player.score); // ป้องกันติดลบ
                        player.isBoosting = false;
                    }
                }

                // ดึง head position
                const head = player.segments[0].position;

                // Update UI
                score = player.score;
                // แสดงคะแนนปัจจุบัน / คะแนนที่ต้องการ เพื่อโตปล้องถัดไป
                document.getElementById('score').textContent = `${score} / ${player.nextGrowthScore}`;
                document.getElementById('length').textContent = player.length;
                document.getElementById('rank').textContent = '#' + getRank();

                // Magnet effect - attract nearby food (เฉพาะเมื่อมี powerup เท่านั้น!)
                if (activePowerups.magnet && activePowerups.magnet !== null) {
                    const magnetRange = 8;
                    foods.forEach(food => {
                        const distance = head.distanceTo(food.position);
                        if (distance < magnetRange && distance > 0.5) {
                            const direction = new THREE.Vector3()
                                .subVectors(head, food.position)
                                .normalize()
                                .multiplyScalar(0.2);
                            food.position.add(direction);
                        }
                    });
                }

                // Check food collision
                for (let i = foods.length - 1; i >= 0; i--) {
                    const food = foods[i];
                    if (head.distanceTo(food.position) < 1) {
                        const foodValue = food.userData.value || CONFIG.FOOD_VALUE; // 1 คะแนน
                        const multiplier = activePowerups.multiplier ? 2 : 1;

                        // บวกคะแนน
                        player.score += foodValue * multiplier;
                        score = player.score; // อัปเดต global score

                        // ✅ ตรวจสอบว่าคะแนนพอเติบโตหรือยัง - เพิ่มเข้าคิว (ไม่เติบโตทันที)
                        while (player.score >= player.nextGrowthScore) {
                            player.pendingGrowth++; // เพิ่มคิวการเติบโต (จะค่อยๆ โตทีละเม็ดใน update())
                            player.nextGrowthScore = player.calculateNextGrowthScore();
                        }

                        // ✅ เล่นเสียงกิน (แยกจากเสียงโต ซึ่งจะเล่นตอนเติบโตจริง)
                        playSound('eat');

                        scene.remove(food);
                        foods.splice(i, 1);
                        createFood();
                        break;
                    }
                }

                // Check powerup collision
                for (let i = powerups.length - 1; i >= 0; i--) {
                    const powerup = powerups[i];
                    if (head.distanceTo(powerup.position) < 1.5) {
                        activatePowerup(powerup.userData.type);
                        scene.remove(powerup);
                        powerups.splice(i, 1);
                    }
                }

                // ✅ ปรับระยะกล้องตามขนาดหนอน (ให้เห็นใกล้หนอน)
                const baseCameraDistance = CONFIG.CAMERA_INITIAL_DISTANCE; // 15
                const lengthMultiplier = 0.1; // ✅ ทุกๆ 1 ความยาว กล้องออก 0.1 (ช้ามาก - ปรับลดจาก 0.2)
                const maxCameraDistance = 17; // ✅ จำกัดระยะสูงสุดที่ 17 (ใกล้มากขึ้น - ปรับลดจาก 20)

                // คำนวณระยะกล้องตามขนาดหนอน
                let calculatedDistance = baseCameraDistance + (player.length * lengthMultiplier);
                calculatedDistance = Math.min(calculatedDistance, maxCameraDistance);

                // Smooth camera zoom
                if (activePowerups.zoom) {
                    // มี zoom powerup ให้ออกไปได้ถึง 50
                    targetCameraDistance = CONFIG.CAMERA_ZOOMED_OUT_DISTANCE; // 50
                } else {
                    // ปกติจำกัดที่ 25 (ใกล้มาก)
                    targetCameraDistance = calculatedDistance;
                }

                // Lerp camera distance
                currentCameraDistance += (targetCameraDistance - currentCameraDistance) * 0.05;

                // Update camera to follow player with zoom
                camera.position.x = head.x;
                camera.position.y = currentCameraDistance;
                camera.position.z = head.z + currentCameraDistance;
                camera.lookAt(head.x, 0, head.z);
            }

            updateBots();

            // ✅ Throttle collision checks - ทุก 50ms แทนที่จะทุก frame (ลด CPU ลง 66%!)
            if (currentTime - lastCollisionCheck >= COLLISION_CHECK_INTERVAL) {
                checkAllSnakeCollisions();
                lastCollisionCheck = currentTime;
            }

            // ✅ Throttle leaderboard updates - ทุก 500ms (ลด DOM manipulation)
            if (currentTime - lastLeaderboardUpdate >= LEADERBOARD_UPDATE_INTERVAL) {
                updateLeaderboard();
                lastLeaderboardUpdate = currentTime;
            }

            // Update powerup UI timers
            if (Object.values(activePowerups).some(p => p !== null)) {
                updatePowerupUI();
            }

            // ✅ Animate RGB สำหรับอาหารจากการตาย (เรืองแสง RGB)
            const rgbHue = (currentTime * 0.001) % 1; // เปลี่ยนทุก 1 วินาที

            // ✅ ตรวจสอบและลบอาหารที่หมดอายุ (30 วินาที) - ใช้ currentTime จาก line ก่อนหน้า
            foods = foods.filter(food => {
                // อาหารจากการตาย - ตรวจสอบหมดอายุ
                if (food.userData.expiresAt && currentTime >= food.userData.expiresAt) {
                    scene.remove(food);
                    return false; // ลบออกจาก array
                }

                // Animate RGB
                if (food.userData.isRGB) {
                    food.material.color.setHSL(rgbHue, 1, 0.6);
                    food.material.emissive.setHSL(rgbHue, 1, 0.6);
                }

                return true; // เก็บไว้
            });

            // Maintain food count
            while (foods.length < CONFIG.FOOD_COUNT) {
                createFood();
            }

            // Maintain bot count
            while (bots.length < CONFIG.BOT_COUNT) {
                createBot();
            }

            // ✅ Spawn powerups (สูงสุด 4 ชิ้นพร้อมกัน, อัตรา 15% ต่อเฟรม = บ่อยมาก!)
            if (powerups.length < 4 && Math.random() < 0.15) {
                createPowerup();
            }
        }

        function animate() {
            requestAnimationFrame(animate);
            // ✅ requestAnimationFrame มี built-in 60 FPS อยู่แล้ว ไม่ต้อง limit เอง!
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

        /**
         * Render ผู้เล่นคนอื่นจาก server
         */
        function renderOtherPlayers() {
            if (!multiplayerManager) return;

            const otherPlayers = multiplayerManager.getOtherPlayers();

            otherPlayers.forEach(playerData => {
                // สร้างหรืออัปเดตงูของผู้เล่นคนอื่น
                if (!otherPlayerSnakes.has(playerData.id)) {
                    // สร้างใหม่
                    const snake = new Snake(
                        playerData.position.x,
                        playerData.position.z,
                        false,
                        playerData.name,
                        playerData.skin
                    );
                    otherPlayerSnakes.set(playerData.id, snake);
                } else {
                    // อัปเดตตำแหน่ง
                    const snake = otherPlayerSnakes.get(playerData.id);
                    if (snake.segments[0] && playerData.position) {
                        snake.segments[0].position.set(
                            playerData.position.x,
                            playerData.position.y || 0.5,
                            playerData.position.z
                        );
                    }

                    // อัปเดตข้อมูล
                    snake.score = playerData.score;
                    snake.length = playerData.length;
                    snake.alive = playerData.is_alive;

                    // อัปเดตทิศทาง
                    if (playerData.direction) {
                        snake.direction.set(
                            playerData.direction.x,
                            playerData.direction.y || 0,
                            playerData.direction.z
                        );
                    }

                    snake.update();
                }
            });

            // ลบผู้เล่นที่ออกไปแล้ว
            for (const [playerId, snake] of otherPlayerSnakes) {
                const stillExists = otherPlayers.some(p => p.id === playerId);
                if (!stillExists) {
                    // ลบงู
                    snake.segments.forEach(seg => scene.remove(seg));
                    if (snake.nameSprite) scene.remove(snake.nameSprite);
                    otherPlayerSnakes.delete(playerId);
                }
            }
        }

        /**
         * Render ไอเทมจาก server
         */
        function renderServerItems() {
            if (!multiplayerManager) return;

            const serverItems = multiplayerManager.getServerItems();

            // ลบไอเทมในหน้าจอที่ไม่มีใน server แล้ว
            foods = foods.filter(food => {
                const stillExists = serverItems.some(item => {
                    // ใช้ position เป็นตัวเปรียบเทียบ
                    return Math.abs(food.position.x - item.position.x) < 0.1 &&
                           Math.abs(food.position.z - item.position.z) < 0.1;
                });

                if (!stillExists) {
                    scene.remove(food);
                    return false;
                }
                return true;
            });

            // เพิ่มไอเทมใหม่ที่ยังไม่มีในหน้าจอ
            serverItems.forEach(itemData => {
                const exists = foods.some(food => {
                    return Math.abs(food.position.x - itemData.position.x) < 0.1 &&
                           Math.abs(food.position.z - itemData.position.z) < 0.1;
                });

                if (!exists) {
                    // สร้างไอเทมใหม่
                    createFood(itemData.position.x, itemData.position.z, itemData.value > 1);
                }
            });
        }

        init();
    </script>
</body>
</html>
