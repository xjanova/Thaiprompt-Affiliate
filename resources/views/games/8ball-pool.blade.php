<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <title>8 Ball Pool - Thai Prompt Games</title>

    <meta name="description" content="เล่นเกม 8 Ball Pool ออนไลน์! แข่ง PVP วางเดิมพัน ใช้ wallet เล่นได้เลย!">
    <meta property="og:type" content="website">
    <meta property="og:title" content="8 Ball Pool - Thai Prompt Games">
    <meta property="og:description" content="เล่นเกม 8 Ball Pool ออนไลน์! แข่ง PVP วางเดิมพัน ใช้ wallet เล่นได้เลย!">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="Thai Prompt Games">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Noto+Sans+Thai:wght@400;600;700&family=Rajdhani:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            overflow: hidden;
            background: #060a10;
            font-family: 'Rajdhani', 'Noto Sans Thai', sans-serif;
            touch-action: none;
            -webkit-user-select: none;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
            color: #e0e8f0;
        }

        #game-container {
            width: 100vw;
            height: 100vh;
            position: relative;
            overflow: hidden;
        }

        #pool-canvas {
            display: block;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        /* ===== HUD ===== */
        #hud {
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 16px;
            background: linear-gradient(180deg, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0) 100%);
            z-index: 20;
            pointer-events: none;
        }

        .player-hud {
            display: flex;
            align-items: center;
            gap: 10px;
            pointer-events: auto;
        }

        .player-hud .p-name {
            font-family: 'Orbitron', sans-serif;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .player-hud .p-balls {
            display: flex;
            gap: 4px;
        }

        .player-hud .p-ball-dot {
            width: 16px; height: 16px;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.3);
            transition: all 0.3s;
        }

        .player-hud .p-ball-dot.pocketed {
            opacity: 0.3;
            transform: scale(0.7);
        }

        .player-hud.active .p-name {
            color: #00e5ff;
            text-shadow: 0 0 12px rgba(0,229,255,0.6);
        }

        .hud-center {
            text-align: center;
        }

        .hud-center .bet-display {
            font-family: 'Orbitron', sans-serif;
            font-size: 13px;
            color: #ffd700;
            text-shadow: 0 0 8px rgba(255,215,0,0.5);
        }

        .hud-center .turn-text {
            font-size: 12px;
            color: #00e5ff;
            opacity: 0.9;
        }

        /* ===== BOTTOM HUD ===== */
        #bottom-hud {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background: linear-gradient(0deg, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0) 100%);
            z-index: 20;
            pointer-events: none;
        }

        .pocketed-ball {
            width: 22px; height: 22px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            font-weight: 700;
            color: #fff;
            text-shadow: 0 1px 2px rgba(0,0,0,0.8);
            border: 1px solid rgba(255,255,255,0.3);
            animation: pocketIn 0.4s ease-out;
        }

        @keyframes pocketIn {
            from { transform: scale(0) rotate(180deg); opacity: 0; }
            to { transform: scale(1) rotate(0deg); opacity: 1; }
        }

        /* ===== POWER METER ===== */
        #power-meter {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            width: 24px;
            height: 200px;
            background: rgba(0,0,0,0.7);
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 12px;
            overflow: hidden;
            z-index: 20;
            display: none;
        }

        #power-fill {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            background: linear-gradient(0deg, #39ff14, #ffd700, #ff3366);
            border-radius: 0 0 10px 10px;
            transition: height 0.05s;
        }

        /* ===== GAME MESSAGE ===== */
        #game-message {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-family: 'Orbitron', sans-serif;
            font-size: 32px;
            font-weight: 900;
            color: #fff;
            text-shadow: 0 0 20px rgba(0,229,255,0.8), 0 0 40px rgba(0,229,255,0.4);
            z-index: 30;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s;
            text-align: center;
        }

        #game-message.show {
            opacity: 1;
            animation: msgPop 0.4s ease-out;
        }

        @keyframes msgPop {
            from { transform: translate(-50%, -50%) scale(0.5); opacity: 0; }
            to { transform: translate(-50%, -50%) scale(1); opacity: 1; }
        }

        /* ===== TITLE SCREEN ===== */
        #title-screen {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(ellipse at center, #0d1520 0%, #060a10 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 100;
            overflow-y: auto;
        }

        #title-screen.hidden { display: none; }

        .title-content {
            text-align: center;
            z-index: 2;
            animation: fadeInUp 0.8s ease-out;
            padding: 20px;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .title-logo {
            font-family: 'Orbitron', sans-serif;
            font-size: 56px;
            font-weight: 900;
            color: #fff;
            text-shadow:
                0 0 20px rgba(0,229,255,0.6),
                0 0 40px rgba(0,229,255,0.3),
                0 4px 8px rgba(0,0,0,0.8);
            line-height: 1.1;
            margin-bottom: 6px;
        }

        .title-logo .accent { color: #00e5ff; }

        .title-subtitle {
            font-size: 16px;
            color: #8899aa;
            margin-bottom: 30px;
        }

        /* Mode Selection */
        .mode-cards {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        .mode-card {
            background: rgba(255,255,255,0.04);
            border: 2px solid rgba(255,255,255,0.1);
            border-radius: 14px;
            padding: 20px 28px;
            cursor: pointer;
            transition: all 0.3s;
            min-width: 150px;
        }

        .mode-card:hover {
            border-color: rgba(0,229,255,0.4);
            background: rgba(0,229,255,0.06);
            transform: translateY(-2px);
        }

        .mode-card.selected {
            border-color: #00e5ff;
            background: rgba(0,229,255,0.1);
            box-shadow: 0 0 20px rgba(0,229,255,0.2);
        }

        .mode-card .mode-icon { font-size: 32px; margin-bottom: 8px; }
        .mode-card .mode-name {
            font-family: 'Orbitron', sans-serif;
            font-size: 14px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 4px;
        }
        .mode-card .mode-desc {
            font-size: 12px;
            color: #8899aa;
        }

        /* Bet Selector */
        .bet-section {
            margin-bottom: 24px;
        }

        .bet-section h3 {
            font-family: 'Orbitron', sans-serif;
            font-size: 14px;
            color: #ffd700;
            margin-bottom: 12px;
            text-shadow: 0 0 8px rgba(255,215,0,0.3);
        }

        .bet-chips {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .bet-chip {
            background: rgba(255,215,0,0.08);
            border: 2px solid rgba(255,215,0,0.2);
            border-radius: 50px;
            padding: 10px 22px;
            cursor: pointer;
            font-family: 'Orbitron', sans-serif;
            font-size: 14px;
            font-weight: 700;
            color: #ffd700;
            transition: all 0.3s;
        }

        .bet-chip:hover {
            border-color: rgba(255,215,0,0.5);
            background: rgba(255,215,0,0.12);
        }

        .bet-chip.selected {
            border-color: #ffd700;
            background: rgba(255,215,0,0.2);
            box-shadow: 0 0 16px rgba(255,215,0,0.3);
        }

        .wallet-info {
            font-size: 14px;
            color: #ffd700;
            margin-bottom: 12px;
        }

        .wallet-info .bal { font-weight: 700; font-size: 18px; }

        /* Play Button */
        .btn-play {
            background: linear-gradient(135deg, #00e5ff, #0080ff);
            border: none;
            border-radius: 50px;
            padding: 16px 60px;
            font-family: 'Orbitron', sans-serif;
            font-size: 18px;
            font-weight: 900;
            color: #fff;
            cursor: pointer;
            letter-spacing: 3px;
            transition: all 0.3s;
            box-shadow: 0 4px 20px rgba(0,229,255,0.4);
        }

        .btn-play:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 30px rgba(0,229,255,0.6);
        }

        .btn-play:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            transform: none;
        }

        .auth-links {
            margin-top: 20px;
        }

        .auth-links a {
            color: #00e5ff;
            text-decoration: none;
            font-size: 14px;
            margin: 0 10px;
        }

        .title-version {
            margin-top: 24px;
            font-size: 11px;
            color: #445566;
        }

        /* ===== GAME OVER SCREEN ===== */
        #gameover-screen {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.85);
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 100;
        }

        #gameover-screen.show { display: flex; }

        .gameover-content {
            text-align: center;
            animation: fadeInUp 0.6s ease-out;
            padding: 20px;
        }

        .gameover-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 48px;
            font-weight: 900;
            margin-bottom: 8px;
        }

        .gameover-title.win {
            color: #ffd700;
            text-shadow: 0 0 30px rgba(255,215,0,0.6);
        }

        .gameover-title.lose {
            color: #ff3366;
            text-shadow: 0 0 30px rgba(255,51,102,0.6);
        }

        .gameover-winner {
            font-size: 20px;
            color: #00e5ff;
            margin-bottom: 20px;
        }

        .gameover-coins {
            font-family: 'Orbitron', sans-serif;
            font-size: 28px;
            color: #ffd700;
            margin-bottom: 24px;
        }

        .gameover-coins.lost { color: #ff3366; }

        .gameover-btns {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            border: none;
            border-radius: 50px;
            padding: 12px 36px;
            font-family: 'Orbitron', sans-serif;
            font-size: 14px;
            font-weight: 700;
            color: #fff;
            cursor: pointer;
            transition: all 0.3s;
            letter-spacing: 1px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #00e5ff, #0080ff);
            box-shadow: 0 4px 16px rgba(0,229,255,0.3);
        }

        .btn-secondary {
            background: rgba(255,255,255,0.1);
            border: 2px solid rgba(255,255,255,0.2);
        }

        .btn-gold {
            background: linear-gradient(135deg, #ffd700, #ff8c00);
            box-shadow: 0 4px 16px rgba(255,215,0,0.3);
        }

        .btn:hover { transform: scale(1.05); }

        /* ===== SAVE SCORE SECTION ===== */
        .save-section {
            margin-top: 24px;
            padding: 16px;
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .save-section .payment-btns {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 10px 0;
        }

        .pay-btn {
            background: rgba(255,255,255,0.08);
            border: 2px solid transparent;
            border-radius: 8px;
            padding: 8px 18px;
            cursor: pointer;
            color: #fff;
            font-size: 13px;
            transition: all 0.2s;
        }

        .pay-btn.active { border-color: #fff; transform: scale(1.05); }

        #wallet-status {
            margin: 8px 0;
            padding: 8px;
            background: rgba(255,255,255,0.05);
            border-radius: 6px;
            font-size: 13px;
        }

        /* ===== LEADERBOARD PANEL ===== */
        .lb-panel {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            width: 280px;
            max-height: 60vh;
            background: rgba(0,0,0,0.85);
            border: 2px solid rgba(0,229,255,0.3);
            border-radius: 14px;
            overflow: hidden;
            z-index: 50;
            animation: slideInRight 0.6s ease-out;
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateY(-50%) translateX(30px); }
            to { opacity: 1; transform: translateY(-50%) translateX(0); }
        }

        .lb-header {
            background: linear-gradient(135deg, rgba(255,215,0,0.2), rgba(255,165,0,0.1));
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid rgba(255,215,0,0.2);
        }

        .lb-header h3 {
            font-family: 'Orbitron', sans-serif;
            font-size: 14px;
            color: #ffd700;
            text-shadow: 0 0 8px rgba(255,215,0,0.4);
        }

        .lb-list {
            max-height: calc(60vh - 50px);
            overflow-y: auto;
            padding: 6px;
        }

        .lb-item {
            display: flex;
            align-items: center;
            padding: 6px 8px;
            margin: 2px 0;
            border-radius: 6px;
            font-size: 12px;
        }

        .lb-item .lb-rank { width: 24px; font-weight: 700; text-align: center; }
        .lb-item .lb-name { flex: 1; color: #ccc; padding: 0 6px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .lb-item .lb-score { color: #00e5ff; font-weight: 700; }

        .lb-item.rank-1 { background: rgba(255,215,0,0.15); }
        .lb-item.rank-1 .lb-rank { color: #ffd700; }
        .lb-item.rank-2 { background: rgba(192,192,192,0.1); }
        .lb-item.rank-2 .lb-rank { color: #c0c0c0; }
        .lb-item.rank-3 { background: rgba(205,127,50,0.1); }
        .lb-item.rank-3 .lb-rank { color: #cd7f32; }

        /* ===== TOAST ===== */
        #toast {
            position: fixed;
            bottom: 80px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: rgba(0,0,0,0.9);
            border: 1px solid rgba(0,229,255,0.4);
            border-radius: 10px;
            padding: 12px 24px;
            font-size: 14px;
            color: #fff;
            z-index: 999;
            transition: transform 0.3s;
            pointer-events: none;
        }

        #toast.show { transform: translateX(-50%) translateY(0); }

        /* ===== QUIT BUTTON ===== */
        #btn-quit {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 8px;
            padding: 6px 14px;
            color: #aaa;
            font-size: 12px;
            cursor: pointer;
            z-index: 25;
            transition: all 0.2s;
        }

        #btn-quit:hover { background: rgba(255,51,102,0.2); border-color: #ff3366; color: #ff3366; }

        /* ===== MOBILE RESPONSIVE ===== */
        @media (max-width: 768px) {
            .title-logo { font-size: 36px; }
            .title-subtitle { font-size: 13px; }
            .mode-card { padding: 14px 18px; min-width: 120px; }
            .mode-card .mode-icon { font-size: 24px; }
            .mode-card .mode-name { font-size: 12px; }
            .bet-chip { padding: 8px 16px; font-size: 12px; }
            .btn-play { padding: 12px 40px; font-size: 15px; }
            .lb-panel { display: none; }
            .gameover-title { font-size: 32px; }
            #power-meter { right: 10px; height: 150px; width: 20px; }
            .player-hud .p-name { font-size: 11px; }
            .player-hud .p-ball-dot { width: 12px; height: 12px; }
        }

        @media (max-height: 500px) {
            #hud { height: 40px; }
            #bottom-hud { height: 36px; }
            .pocketed-ball { width: 18px; height: 18px; font-size: 8px; }
        }
    </style>
</head>
<body>
    <div id="game-container">
        <canvas id="pool-canvas"></canvas>

        <!-- HUD -->
        <div id="hud" style="display:none;">
            <div class="player-hud" id="p1-hud">
                <div class="p-name" id="p1-name">Player 1</div>
                <div class="p-balls" id="p1-balls"></div>
            </div>
            <div class="hud-center">
                <div class="bet-display" id="hud-bet"></div>
                <div class="turn-text" id="hud-turn"></div>
            </div>
            <div class="player-hud" id="p2-hud">
                <div class="p-balls" id="p2-balls"></div>
                <div class="p-name" id="p2-name">Player 2</div>
            </div>
        </div>

        <!-- Bottom: Pocketed Balls -->
        <div id="bottom-hud" style="display:none;">
            <div id="pocketed-tray"></div>
        </div>

        <!-- Power Meter -->
        <div id="power-meter">
            <div id="power-fill"></div>
        </div>

        <!-- Game Message -->
        <div id="game-message"></div>

        <!-- Quit Button -->
        <button id="btn-quit" style="display:none;" onclick="quitGame()">✕ ออก</button>

        <!-- ===== TITLE SCREEN ===== -->
        <div id="title-screen">
            <div class="title-content">
                <h1 class="title-logo"><span class="accent">8</span> BALL POOL</h1>
                <p class="title-subtitle">เกมพูลออนไลน์ แข่ง PVP วางเดิมพัน!</p>

                <!-- Mode Selection -->
                <div class="mode-cards">
                    <div class="mode-card selected" data-mode="ai" onclick="selectMode('ai')">
                        <div class="mode-icon">🤖</div>
                        <div class="mode-name">VS AI</div>
                        <div class="mode-desc">แข่งกับ AI วางเดิมพันได้</div>
                    </div>
                    <div class="mode-card" data-mode="local" onclick="selectMode('local')">
                        <div class="mode-icon">👥</div>
                        <div class="mode-name">LOCAL PVP</div>
                        <div class="mode-desc">เล่น 2 คน สลับกัน</div>
                    </div>
                    <div class="mode-card" data-mode="practice" onclick="selectMode('practice')">
                        <div class="mode-icon">🎯</div>
                        <div class="mode-name">PRACTICE</div>
                        <div class="mode-desc">ซ้อมฟรี ไม่มีเดิมพัน</div>
                    </div>
                </div>

                <!-- Bet Selector -->
                <div class="bet-section" id="bet-section">
                    @auth
                    <div class="wallet-info">
                        💰 Wallet: <span class="bal" id="title-balance">...</span> แต้ม
                    </div>
                    @endauth
                    <h3>💰 เลือกเดิมพัน</h3>
                    <div class="bet-chips">
                        <div class="bet-chip" data-bet="0" onclick="selectBet(0)">FREE</div>
                        <div class="bet-chip selected" data-bet="10" onclick="selectBet(10)">10</div>
                        <div class="bet-chip" data-bet="50" onclick="selectBet(50)">50</div>
                        <div class="bet-chip" data-bet="100" onclick="selectBet(100)">100</div>
                        <div class="bet-chip" data-bet="500" onclick="selectBet(500)">500</div>
                    </div>
                </div>

                <button class="btn-play" id="btn-start" onclick="startMatch()">▶ PLAY</button>

                @guest
                <div class="auth-links">
                    <a href="{{ route('login') }}">เข้าสู่ระบบ</a> |
                    <a href="{{ route('register') }}">สมัครสมาชิก</a>
                </div>
                @endguest

                <p class="title-version">Version 1.0.0 — 🎮 Created by XMAN STUDIO © 2025</p>
            </div>

            <!-- Leaderboard -->
            <div class="lb-panel" id="title-lb">
                <div class="lb-header"><h3>🏆 TOP PLAYERS</h3></div>
                <div class="lb-list" id="title-lb-list">
                    <div style="text-align:center;padding:20px;color:#556;">กำลังโหลด...</div>
                </div>
            </div>
        </div>

        <!-- ===== GAME OVER SCREEN ===== -->
        <div id="gameover-screen">
            <div class="gameover-content">
                <div class="gameover-title" id="go-title">VICTORY!</div>
                <div class="gameover-winner" id="go-winner"></div>
                <div class="gameover-coins" id="go-coins"></div>

                @auth
                <div class="save-section" id="save-section">
                    <p style="font-size:14px;margin-bottom:8px;">บันทึกผลและรับคะแนน</p>
                    <div class="payment-btns">
                        <button class="pay-btn active" id="pay-wallet-btn" onclick="selectPayment('wallet')">
                            💰 แต้ม <span id="pay-wallet-bal">(...)</span>
                        </button>
                        <button class="pay-btn" id="pay-coin-btn" onclick="selectPayment('coin')">
                            🪙 เหรียญ <span id="pay-coin-bal">(...)</span>
                        </button>
                    </div>
                    <div id="wallet-status"></div>
                    <button class="btn btn-gold" id="save-btn" onclick="saveResult()" disabled>
                        ✅ บันทึกผล (1 แต้ม)
                    </button>
                </div>
                @endauth

                <div class="gameover-btns" style="margin-top:16px;">
                    <button class="btn btn-primary" onclick="playAgain()">🔄 PLAY AGAIN</button>
                    <button class="btn btn-secondary" onclick="backToLobby()">◀ LOBBY</button>
                </div>
            </div>
        </div>

        <!-- Toast -->
        <div id="toast"></div>
    </div>

    <script>
    // ============================================================
    //  8 BALL POOL - COMPLETE GAME ENGINE
    //  Integrated with Thaiprompt-Affiliate Wallet System
    // ============================================================
    'use strict';

    // --- Auth State ---
    const isAuthenticated = @json(Auth::check());
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    // ===========================
    //  CONSTANTS
    // ===========================
    const CW = 960, CH = 540;
    const TBL = { l: 50, t: 50, w: 860, h: 440 };
    const BALL_R = 11;
    const POCKET_R = 22;
    const SIDE_POCKET_R = 19;
    const FRICTION = 0.987;
    const CUSHION_LOSS = 0.78;
    const MIN_VEL = 0.12;
    const MAX_POWER = 20;
    const POCKET_ZONE = POCKET_R * 2.2;

    // Pocket positions
    const POCKETS = [
        { x: TBL.l + 4,          y: TBL.t + 4,          r: POCKET_R },     // TL
        { x: TBL.l + TBL.w / 2,  y: TBL.t - 2,          r: SIDE_POCKET_R },// TC
        { x: TBL.l + TBL.w - 4,  y: TBL.t + 4,          r: POCKET_R },     // TR
        { x: TBL.l + 4,          y: TBL.t + TBL.h - 4,  r: POCKET_R },     // BL
        { x: TBL.l + TBL.w / 2,  y: TBL.t + TBL.h + 2,  r: SIDE_POCKET_R },// BC
        { x: TBL.l + TBL.w - 4,  y: TBL.t + TBL.h - 4,  r: POCKET_R },    // BR
    ];

    // Ball colors
    const BALL_CLR = {
        0: '#f0f0f0',
        1: '#ffc800', 2: '#003ad6', 3: '#e01010', 4: '#6b1aad',
        5: '#e86000', 6: '#008833', 7: '#8b1030', 8: '#1a1a1a',
        9: '#ffc800', 10:'#003ad6', 11:'#e01010', 12:'#6b1aad',
        13:'#e86000', 14:'#008833', 15:'#8b1030'
    };

    // ===========================
    //  SOUND
    // ===========================
    let audioCtx = null;
    function getAudio() {
        if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        return audioCtx;
    }
    function playSound(freq, dur, vol, type) {
        try {
            const ctx = getAudio();
            const osc = ctx.createOscillator();
            const g = ctx.createGain();
            osc.frequency.value = freq;
            osc.type = type || 'sine';
            g.gain.setValueAtTime(vol || 0.15, ctx.currentTime);
            g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + (dur || 0.1));
            osc.connect(g); g.connect(ctx.destination);
            osc.start(); osc.stop(ctx.currentTime + (dur || 0.1) + 0.05);
        } catch(e) {}
    }
    function sndHit(force)  { playSound(600 + force * 400, 0.06, Math.min(force * 0.3, 0.2), 'triangle'); }
    function sndCushion()   { playSound(180, 0.04, 0.1, 'square'); }
    function sndPocket()    { playSound(300, 0.2, 0.15, 'sine'); setTimeout(() => playSound(200, 0.15, 0.1, 'sine'), 80); }
    function sndCue(power)  { playSound(400 + power * 500, 0.08, 0.18, 'triangle'); }

    // ===========================
    //  BALL CLASS
    // ===========================
    class Ball {
        constructor(x, y, num) {
            this.x = x; this.y = y;
            this.vx = 0; this.vy = 0;
            this.num = num;
            this.pocketed = false;
        }
        get speed() { return Math.sqrt(this.vx * this.vx + this.vy * this.vy); }
        get moving() { return this.speed > MIN_VEL; }
        get solid() { return this.num >= 1 && this.num <= 7; }
        get stripe() { return this.num >= 9 && this.num <= 15; }
        get eight() { return this.num === 8; }
        get cue() { return this.num === 0; }
    }

    // ===========================
    //  GAME STATE
    // ===========================
    let canvas, ctx;
    let balls = [];
    let state = 'idle'; // idle | aiming | charging | shooting | moving | placing | gameover
    let currentPlayer = 0;
    let players = [
        { name: 'Player 1', type: null, pocketed: [], isAI: false, allCleared: false },
        { name: 'Player 2', type: null, pocketed: [], isAI: false, allCleared: false }
    ];
    let aimAngle = 0;
    let power = 0;
    let isBreakShot = true;
    let firstHitBall = null;
    let shotPocketed = [];
    let cueScratch = false;
    let foulReason = '';
    let gameMode = 'ai';
    let betAmount = 10;
    let matchId = null;
    let mouseX = CW / 2, mouseY = CH / 2;
    let dragStartX = 0, dragStartY = 0;
    let isDragging = false;
    let msgText = '';
    let msgTimer = 0;
    let cueAnimPhase = 0; // 0 = none, 1 = pullback anim, 2 = strike
    let cueAnimT = 0;
    let placingValid = false;
    let running = false;
    let scaleX = 1, scaleY = 1, canvasOffX = 0, canvasOffY = 0;

    // Wallet
    let walletBalance = 0;
    let coinBalance = 0;
    let selectedPayment = 'wallet';

    // ===========================
    //  BALL SETUP
    // ===========================
    function shuffle(a) {
        for (let i = a.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [a[i], a[j]] = [a[j], a[i]];
        }
        return a;
    }

    function setupBalls() {
        balls = [];
        // Cue ball
        balls.push(new Ball(TBL.l + TBL.w * 0.25, TBL.t + TBL.h / 2, 0));

        // Rack positions
        const cx = TBL.l + TBL.w * 0.73;
        const cy = TBL.t + TBL.h / 2;
        const dx = BALL_R * Math.sqrt(3);
        const r = BALL_R;

        const rowCols = [
            [[0, 0]],
            [[1, -1], [1, 1]],
            [[2, -2], [2, 0], [2, 2]],
            [[3, -3], [3, -1], [3, 1], [3, 3]],
            [[4, -4], [4, -2], [4, 0], [4, 2], [4, 4]]
        ];

        const positions = rowCols.flat();

        // Ball number assignment: 8-ball at center (index 4)
        const solids = shuffle([1, 2, 3, 4, 5, 6, 7]);
        const stripes = shuffle([9, 10, 11, 12, 13, 14, 15]);
        const nums = new Array(15);
        nums[4] = 8;
        // Corners of last row: one solid one stripe
        if (Math.random() < 0.5) {
            nums[10] = solids.pop(); nums[14] = stripes.pop();
        } else {
            nums[10] = stripes.pop(); nums[14] = solids.pop();
        }
        const rest = shuffle([...solids, ...stripes]);
        for (let i = 0; i < 15; i++) {
            if (nums[i] === undefined) nums[i] = rest.pop();
        }

        for (let i = 0; i < 15; i++) {
            const [row, col] = positions[i];
            const bx = cx + row * dx;
            const by = cy + col * r;
            balls.push(new Ball(bx, by, nums[i]));
        }
    }

    // ===========================
    //  PHYSICS
    // ===========================
    function nearPocket(x, y) {
        for (const p of POCKETS) {
            const dx = x - p.x, dy = y - p.y;
            if (dx * dx + dy * dy < POCKET_ZONE * POCKET_ZONE) return true;
        }
        return false;
    }

    function checkPockets() {
        for (const b of balls) {
            if (b.pocketed) continue;
            for (const p of POCKETS) {
                const dx = b.x - p.x, dy = b.y - p.y;
                const dist = Math.sqrt(dx * dx + dy * dy);
                if (dist < p.r + BALL_R * 0.4) {
                    b.pocketed = true;
                    b.vx = 0; b.vy = 0;
                    sndPocket();
                    if (b.cue) {
                        cueScratch = true;
                    } else {
                        shotPocketed.push(b.num);
                    }
                    break;
                }
            }
        }
    }

    function ballCollisions() {
        const active = balls.filter(b => !b.pocketed);
        for (let i = 0; i < active.length; i++) {
            for (let j = i + 1; j < active.length; j++) {
                const a = active[i], b = active[j];
                const dx = b.x - a.x, dy = b.y - a.y;
                const dist = Math.sqrt(dx * dx + dy * dy);
                const minD = BALL_R * 2;
                if (dist < minD && dist > 0.001) {
                    // Elastic collision
                    const nx = dx / dist, ny = dy / dist;
                    const dvx = a.vx - b.vx, dvy = a.vy - b.vy;
                    const dvn = dvx * nx + dvy * ny;
                    if (dvn <= 0) continue;

                    a.vx -= dvn * nx;
                    a.vy -= dvn * ny;
                    b.vx += dvn * nx;
                    b.vy += dvn * ny;

                    // Separate
                    const overlap = minD - dist;
                    a.x -= overlap / 2 * nx;
                    a.y -= overlap / 2 * ny;
                    b.x += overlap / 2 * nx;
                    b.y += overlap / 2 * ny;

                    const force = Math.sqrt(dvn * dvn) / MAX_POWER;
                    sndHit(force);

                    // Track first hit
                    if (a.cue && firstHitBall === null) firstHitBall = b;
                    if (b.cue && firstHitBall === null) firstHitBall = a;
                }
            }
        }
    }

    function wallCollisions() {
        const l = TBL.l + BALL_R, r = TBL.l + TBL.w - BALL_R;
        const t = TBL.t + BALL_R, b = TBL.t + TBL.h - BALL_R;

        for (const ball of balls) {
            if (ball.pocketed) continue;
            if (nearPocket(ball.x, ball.y)) continue;

            let bounced = false;
            if (ball.x < l) { ball.x = l; ball.vx = -ball.vx * CUSHION_LOSS; bounced = true; }
            if (ball.x > r) { ball.x = r; ball.vx = -ball.vx * CUSHION_LOSS; bounced = true; }
            if (ball.y < t) { ball.y = t; ball.vy = -ball.vy * CUSHION_LOSS; bounced = true; }
            if (ball.y > b) { ball.y = b; ball.vy = -ball.vy * CUSHION_LOSS; bounced = true; }
            if (bounced) sndCushion();
        }
    }

    function updatePhysics() {
        for (const b of balls) {
            if (b.pocketed) continue;
            b.x += b.vx;
            b.y += b.vy;
            b.vx *= FRICTION;
            b.vy *= FRICTION;
            if (Math.abs(b.vx) < MIN_VEL * 0.5) b.vx = 0;
            if (Math.abs(b.vy) < MIN_VEL * 0.5) b.vy = 0;
        }
        ballCollisions();
        wallCollisions();
        checkPockets();
    }

    function allStopped() {
        return balls.every(b => b.pocketed || !b.moving);
    }

    // ===========================
    //  RENDERING
    // ===========================
    function drawTable() {
        // Shadow
        ctx.fillStyle = '#03060a';
        ctx.fillRect(0, 0, CW, CH);

        // Outer frame
        const pad = 12;
        ctx.fillStyle = '#2a1205';
        roundRect(ctx, TBL.l - 38, TBL.t - 38, TBL.w + 76, TBL.h + 76, 10);
        ctx.fill();

        // Rail wood
        ctx.fillStyle = '#3d1a08';
        roundRect(ctx, TBL.l - 28, TBL.t - 28, TBL.w + 56, TBL.h + 56, 6);
        ctx.fill();

        // Inner rail edge
        ctx.fillStyle = '#1a7a3a';
        ctx.fillRect(TBL.l - 6, TBL.t - 6, TBL.w + 12, TBL.h + 12);

        // Felt
        const feltGrad = ctx.createRadialGradient(
            TBL.l + TBL.w / 2, TBL.t + TBL.h / 2, 50,
            TBL.l + TBL.w / 2, TBL.t + TBL.h / 2, TBL.w * 0.7
        );
        feltGrad.addColorStop(0, '#1a8c45');
        feltGrad.addColorStop(1, '#126630');
        ctx.fillStyle = feltGrad;
        ctx.fillRect(TBL.l, TBL.t, TBL.w, TBL.h);

        // Pocket openings
        for (const p of POCKETS) {
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.r + 4, 0, Math.PI * 2);
            ctx.fillStyle = '#0a0a0a';
            ctx.fill();
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
            const pg = ctx.createRadialGradient(p.x, p.y, 0, p.x, p.y, p.r);
            pg.addColorStop(0, '#050505');
            pg.addColorStop(1, '#1a1a1a');
            ctx.fillStyle = pg;
            ctx.fill();
        }

        // Diamond markers on rails
        const dColor = '#c4a44a';
        const dSize = 3;
        // Top rail diamonds
        for (let i = 1; i <= 3; i++) {
            drawDiamond(TBL.l + (TBL.w / 2) * (i / 4), TBL.t - 18, dSize, dColor);
            drawDiamond(TBL.l + TBL.w / 2 + (TBL.w / 2) * (i / 4), TBL.t - 18, dSize, dColor);
        }
        // Bottom
        for (let i = 1; i <= 3; i++) {
            drawDiamond(TBL.l + (TBL.w / 2) * (i / 4), TBL.t + TBL.h + 18, dSize, dColor);
            drawDiamond(TBL.l + TBL.w / 2 + (TBL.w / 2) * (i / 4), TBL.t + TBL.h + 18, dSize, dColor);
        }
        // Left
        for (let i = 1; i <= 3; i++) {
            drawDiamond(TBL.l - 18, TBL.t + TBL.h * (i / 4), dSize, dColor);
        }
        // Right
        for (let i = 1; i <= 3; i++) {
            drawDiamond(TBL.l + TBL.w + 18, TBL.t + TBL.h * (i / 4), dSize, dColor);
        }

        // Head string line
        const headX = TBL.l + TBL.w * 0.25;
        ctx.beginPath();
        ctx.moveTo(headX, TBL.t + 1);
        ctx.lineTo(headX, TBL.t + TBL.h - 1);
        ctx.strokeStyle = 'rgba(255,255,255,0.08)';
        ctx.lineWidth = 1;
        ctx.stroke();

        // Foot spot
        ctx.beginPath();
        ctx.arc(TBL.l + TBL.w * 0.73, TBL.t + TBL.h / 2, 3, 0, Math.PI * 2);
        ctx.fillStyle = 'rgba(255,255,255,0.15)';
        ctx.fill();
    }

    function drawDiamond(x, y, s, c) {
        ctx.beginPath();
        ctx.moveTo(x, y - s);
        ctx.lineTo(x + s, y);
        ctx.lineTo(x, y + s);
        ctx.lineTo(x - s, y);
        ctx.closePath();
        ctx.fillStyle = c;
        ctx.fill();
    }

    function roundRect(ctx, x, y, w, h, r) {
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.lineTo(x + w - r, y);
        ctx.quadraticCurveTo(x + w, y, x + w, y + r);
        ctx.lineTo(x + w, y + h - r);
        ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
        ctx.lineTo(x + r, y + h);
        ctx.quadraticCurveTo(x, y + h, x, y + h - r);
        ctx.lineTo(x, y + r);
        ctx.quadraticCurveTo(x, y, x + r, y);
        ctx.closePath();
    }

    function drawBall(b) {
        if (b.pocketed) return;
        const { x, y, num } = b;

        ctx.save();
        ctx.translate(x, y);

        // Shadow
        ctx.beginPath();
        ctx.arc(2, 2, BALL_R, 0, Math.PI * 2);
        ctx.fillStyle = 'rgba(0,0,0,0.35)';
        ctx.fill();

        // Base
        ctx.beginPath();
        ctx.arc(0, 0, BALL_R, 0, Math.PI * 2);

        if (num === 0) {
            ctx.fillStyle = '#f0f0f0';
            ctx.fill();
        } else if (num <= 8) {
            // Solid or 8-ball
            ctx.fillStyle = BALL_CLR[num];
            ctx.fill();
        } else {
            // Stripe: white base + colored band
            ctx.fillStyle = '#f0f0f0';
            ctx.fill();
            ctx.save();
            ctx.beginPath();
            ctx.arc(0, 0, BALL_R, 0, Math.PI * 2);
            ctx.clip();
            ctx.fillStyle = BALL_CLR[num];
            ctx.fillRect(-BALL_R, -BALL_R * 0.5, BALL_R * 2, BALL_R);
            ctx.restore();
        }

        // 3D highlight
        const hg = ctx.createRadialGradient(-BALL_R * 0.3, -BALL_R * 0.3, BALL_R * 0.05, 0, 0, BALL_R);
        hg.addColorStop(0, 'rgba(255,255,255,0.45)');
        hg.addColorStop(0.5, 'rgba(255,255,255,0.08)');
        hg.addColorStop(1, 'rgba(0,0,0,0.15)');
        ctx.beginPath();
        ctx.arc(0, 0, BALL_R, 0, Math.PI * 2);
        ctx.fillStyle = hg;
        ctx.fill();

        // Number circle + text
        if (num > 0) {
            ctx.beginPath();
            ctx.arc(0, 0, BALL_R * 0.42, 0, Math.PI * 2);
            ctx.fillStyle = '#fff';
            ctx.fill();

            ctx.fillStyle = '#000';
            ctx.font = `bold ${BALL_R * 0.65}px 'Rajdhani', sans-serif`;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(num, 0, 0.5);
        }

        ctx.restore();
    }

    function drawCue() {
        const cb = balls[0];
        if (!cb || cb.pocketed) return;
        if (state !== 'aiming' && state !== 'charging' && state !== 'shooting') return;

        const angle = aimAngle;
        let pullback = 0;

        if (state === 'charging') pullback = power * 60;
        if (state === 'shooting') pullback = -cueAnimT * 20;

        ctx.save();
        ctx.translate(cb.x, cb.y);
        ctx.rotate(angle + Math.PI);

        const offset = BALL_R + 6 + pullback;
        const len = 220;

        // Cue body
        ctx.beginPath();
        ctx.moveTo(offset, -2.5);
        ctx.lineTo(offset + len, -4.5);
        ctx.lineTo(offset + len, 4.5);
        ctx.lineTo(offset, 2.5);
        ctx.closePath();

        const cg = ctx.createLinearGradient(offset, 0, offset + len, 0);
        cg.addColorStop(0, '#e8c84a');
        cg.addColorStop(0.04, '#b87333');
        cg.addColorStop(0.4, '#a0522d');
        cg.addColorStop(0.6, '#654321');
        cg.addColorStop(1, '#2a1000');
        ctx.fillStyle = cg;
        ctx.fill();

        // Ferrule
        ctx.fillStyle = '#f5f5dc';
        ctx.fillRect(offset, -2, 8, 4);

        // Tip
        ctx.beginPath();
        ctx.arc(offset, 0, 2.5, 0, Math.PI * 2);
        ctx.fillStyle = '#4169E1';
        ctx.fill();

        ctx.restore();
    }

    function drawAimGuide() {
        const cb = balls[0];
        if (!cb || cb.pocketed) return;
        if (state !== 'aiming' && state !== 'charging') return;

        const dx = Math.cos(aimAngle);
        const dy = Math.sin(aimAngle);

        // Find first ball intersection
        let closestT = 9999;
        let hitBall = null;

        for (const b of balls) {
            if (b.pocketed || b.cue) continue;
            const ocx = b.x - cb.x, ocy = b.y - cb.y;
            const t = ocx * dx + ocy * dy;
            if (t < 0) continue;
            const dSq = ocx * ocx + ocy * ocy - t * t;
            const minD = BALL_R * 2;
            if (dSq < minD * minD) {
                const tHit = t - Math.sqrt(minD * minD - dSq);
                if (tHit > 0 && tHit < closestT) {
                    closestT = tHit;
                    hitBall = b;
                }
            }
        }

        // Find wall intersection
        let wallT = 9999;
        // Left wall
        if (dx < 0) { const t = (TBL.l + BALL_R - cb.x) / dx; if (t > 0 && t < wallT) wallT = t; }
        // Right wall
        if (dx > 0) { const t = (TBL.l + TBL.w - BALL_R - cb.x) / dx; if (t > 0 && t < wallT) wallT = t; }
        // Top wall
        if (dy < 0) { const t = (TBL.t + BALL_R - cb.y) / dy; if (t > 0 && t < wallT) wallT = t; }
        // Bottom wall
        if (dy > 0) { const t = (TBL.l + TBL.h - BALL_R - cb.y) / dy; if (t > 0 && t < wallT) wallT = t; }

        const endT = Math.min(closestT, wallT, 500);

        // Dotted aim line
        ctx.save();
        ctx.setLineDash([6, 6]);
        ctx.strokeStyle = 'rgba(255,255,255,0.3)';
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(cb.x + dx * (BALL_R + 8), cb.y + dy * (BALL_R + 8));
        ctx.lineTo(cb.x + dx * endT, cb.y + dy * endT);
        ctx.stroke();
        ctx.setLineDash([]);

        // Ghost ball
        if (hitBall && closestT < 500) {
            const gx = cb.x + dx * closestT;
            const gy = cb.y + dy * closestT;

            // Ghost cue ball position
            ctx.beginPath();
            ctx.arc(gx, gy, BALL_R, 0, Math.PI * 2);
            ctx.strokeStyle = 'rgba(255,255,255,0.2)';
            ctx.lineWidth = 1;
            ctx.stroke();

            // Predicted object ball direction
            const hitDx = hitBall.x - gx;
            const hitDy = hitBall.y - gy;
            const hitDist = Math.sqrt(hitDx * hitDx + hitDy * hitDy);
            if (hitDist > 0) {
                const hnx = hitDx / hitDist, hny = hitDy / hitDist;
                ctx.beginPath();
                ctx.moveTo(hitBall.x, hitBall.y);
                ctx.lineTo(hitBall.x + hnx * 60, hitBall.y + hny * 60);
                ctx.strokeStyle = 'rgba(255,215,0,0.3)';
                ctx.lineWidth = 1.5;
                ctx.setLineDash([4, 4]);
                ctx.stroke();
                ctx.setLineDash([]);
            }
        }
        ctx.restore();
    }

    function drawPlacing() {
        if (state !== 'placing') return;
        const gx = mouseX, gy = mouseY;

        // Clamp to table
        const px = Math.max(TBL.l + BALL_R, Math.min(TBL.l + TBL.w - BALL_R, gx));
        const py = Math.max(TBL.t + BALL_R, Math.min(TBL.t + TBL.h - BALL_R, gy));

        // Check overlap
        placingValid = true;
        for (const b of balls) {
            if (b.pocketed || b.cue) continue;
            const dx = px - b.x, dy = py - b.y;
            if (Math.sqrt(dx * dx + dy * dy) < BALL_R * 2.2) {
                placingValid = false;
                break;
            }
        }

        ctx.save();
        ctx.globalAlpha = 0.6;
        ctx.beginPath();
        ctx.arc(px, py, BALL_R, 0, Math.PI * 2);
        ctx.fillStyle = '#f0f0f0';
        ctx.fill();
        ctx.strokeStyle = placingValid ? '#39ff14' : '#ff3366';
        ctx.lineWidth = 2;
        ctx.stroke();
        ctx.globalAlpha = 1;
        ctx.restore();
    }

    // ===========================
    //  INPUT HANDLING
    // ===========================
    function toCanvas(e) {
        const rect = canvas.getBoundingClientRect();
        return {
            x: (e.clientX - rect.left) * (CW / rect.width),
            y: (e.clientY - rect.top) * (CH / rect.height)
        };
    }

    function onMouseDown(e) {
        e.preventDefault();
        const pos = toCanvas(e);
        mouseX = pos.x; mouseY = pos.y;

        if (state === 'placing') {
            if (placingValid) placeCueBall();
            return;
        }

        if (state === 'aiming') {
            isDragging = true;
            dragStartX = pos.x;
            dragStartY = pos.y;
            state = 'charging';
            power = 0;
        }
    }

    function onMouseMove(e) {
        e.preventDefault();
        const pos = toCanvas(e);
        mouseX = pos.x; mouseY = pos.y;

        const cb = balls[0];
        if (!cb || cb.pocketed) return;

        if (state === 'aiming' || state === 'charging') {
            aimAngle = Math.atan2(pos.y - cb.y, pos.x - cb.x);
        }

        if (state === 'charging' && isDragging) {
            const ddx = pos.x - dragStartX;
            const ddy = pos.y - dragStartY;
            // Power = pull back distance projected onto opposite of aim direction
            const pullBack = -(ddx * Math.cos(aimAngle) + ddy * Math.sin(aimAngle));
            power = Math.max(0, Math.min(1, pullBack / 150));
            updatePowerMeter();
        }
    }

    function onMouseUp(e) {
        e.preventDefault();
        if (state === 'charging') {
            isDragging = false;
            if (power > 0.02) {
                shoot();
            } else {
                state = 'aiming';
                power = 0;
                updatePowerMeter();
            }
        }
    }

    function onTouchStart(e) {
        e.preventDefault();
        if (e.touches.length > 0) {
            const t = e.touches[0];
            onMouseDown({ clientX: t.clientX, clientY: t.clientY, preventDefault: () => {} });
        }
    }

    function onTouchMove(e) {
        e.preventDefault();
        if (e.touches.length > 0) {
            const t = e.touches[0];
            onMouseMove({ clientX: t.clientX, clientY: t.clientY, preventDefault: () => {} });
        }
    }

    function onTouchEnd(e) {
        e.preventDefault();
        onMouseUp({ preventDefault: () => {} });
    }

    // ===========================
    //  GAME ACTIONS
    // ===========================
    function shoot() {
        const cb = balls[0];
        if (!cb || cb.pocketed) return;

        const shotPower = power * MAX_POWER;
        cb.vx = Math.cos(aimAngle) * shotPower;
        cb.vy = Math.sin(aimAngle) * shotPower;

        sndCue(power);

        firstHitBall = null;
        shotPocketed = [];
        cueScratch = false;
        foulReason = '';

        state = 'shooting';
        cueAnimT = 1;
        power = 0;
        updatePowerMeter();

        setTimeout(() => {
            state = 'moving';
        }, 100);
    }

    function placeCueBall() {
        const cb = balls[0];
        const px = Math.max(TBL.l + BALL_R, Math.min(TBL.l + TBL.w - BALL_R, mouseX));
        const py = Math.max(TBL.t + BALL_R, Math.min(TBL.t + TBL.h - BALL_R, mouseY));

        cb.x = px;
        cb.y = py;
        cb.vx = 0;
        cb.vy = 0;
        cb.pocketed = false;

        state = 'aiming';
        showMsg('');
    }

    function updatePowerMeter() {
        const meter = document.getElementById('power-meter');
        const fill = document.getElementById('power-fill');
        if (state === 'charging' || state === 'aiming') {
            meter.style.display = 'block';
            fill.style.height = (power * 100) + '%';
        } else {
            meter.style.display = 'none';
        }
    }

    // ===========================
    //  TURN EVALUATION (8-BALL RULES)
    // ===========================
    function evaluateTurn() {
        const p = players[currentPlayer];
        const opp = players[1 - currentPlayer];
        let foul = false;
        let switchTurn = false;
        let gameEnd = false;
        let winner = -1;

        // Check if 8-ball pocketed
        const eightPocketed = shotPocketed.includes(8);

        // Check foul: scratch
        if (cueScratch) {
            foul = true;
            foulReason = 'Scratch! ลูกคิวตก!';
        }

        // Check foul: didn't hit anything
        if (!foul && firstHitBall === null && !cueScratch) {
            foul = true;
            foulReason = 'Foul! ไม่โดนลูกใดเลย!';
        }

        // Check foul: hit wrong ball first (only after types assigned)
        if (!foul && firstHitBall && p.type) {
            const hitNum = firstHitBall.num;
            if (p.allCleared) {
                // Should hit 8 ball
                if (hitNum !== 8) { foul = true; foulReason = 'Foul! ต้องแทง 8 ก่อน!'; }
            } else {
                const isMyBall = (p.type === 'solid' && firstHitBall.solid) ||
                                 (p.type === 'stripe' && firstHitBall.stripe);
                if (!isMyBall && hitNum !== 8) {
                    foul = true;
                    foulReason = 'Foul! แทงผิดกลุ่ม!';
                }
            }
        }

        // 8-ball rules
        if (eightPocketed) {
            if (p.allCleared && !foul) {
                winner = currentPlayer;
                gameEnd = true;
            } else {
                // Pocketed 8-ball too early or with foul = lose
                winner = 1 - currentPlayer;
                gameEnd = true;
            }
        }

        if (gameEnd) {
            endGame(winner);
            return;
        }

        // Assign ball types on first legal pocket (after break)
        if (isBreakShot) {
            isBreakShot = false;
            if (shotPocketed.length > 0 && !foul) {
                assignTypes(shotPocketed[0]);
            }
        } else if (!p.type && shotPocketed.length > 0 && !foul) {
            assignTypes(shotPocketed[0]);
        }

        // Add pocketed balls to player records
        for (const num of shotPocketed) {
            if (num === 8) continue;
            if (p.type === 'solid' && num >= 1 && num <= 7) p.pocketed.push(num);
            else if (p.type === 'stripe' && num >= 9 && num <= 15) p.pocketed.push(num);
            else if (opp.type === 'solid' && num >= 1 && num <= 7) opp.pocketed.push(num);
            else if (opp.type === 'stripe' && num >= 9 && num <= 15) opp.pocketed.push(num);
            else {
                // Types not assigned yet, whoever pocketed it gets credit
                p.pocketed.push(num);
            }
        }

        // Check if player cleared all their balls
        checkAllCleared();

        // Determine if turn continues
        let pocketedOwn = false;
        if (p.type && !foul) {
            for (const num of shotPocketed) {
                if ((p.type === 'solid' && num >= 1 && num <= 7) ||
                    (p.type === 'stripe' && num >= 9 && num <= 15)) {
                    pocketedOwn = true;
                    break;
                }
            }
        }

        if (foul) {
            showMsg(foulReason);
            switchTurn = true;
            // Ball in hand for opponent
            setTimeout(() => {
                currentPlayer = 1 - currentPlayer;
                respawnCueBall();
                state = 'placing';
                showMsg('วางลูกคิว');
                updateHUD();
            }, 1500);
            return;
        }

        if (pocketedOwn) {
            showMsg('เยี่ยม! เล่นต่อ!');
            setTimeout(() => {
                state = 'aiming';
                showMsg('');
                updateHUD();
            }, 800);
        } else {
            showMsg('สลับตา');
            setTimeout(() => {
                currentPlayer = 1 - currentPlayer;
                state = 'aiming';
                showMsg('');
                updateHUD();
                if (players[currentPlayer].isAI) {
                    setTimeout(() => aiTurn(), 600);
                }
            }, 1000);
        }
    }

    function assignTypes(firstNum) {
        if (firstNum >= 1 && firstNum <= 7) {
            players[currentPlayer].type = 'solid';
            players[1 - currentPlayer].type = 'stripe';
        } else if (firstNum >= 9 && firstNum <= 15) {
            players[currentPlayer].type = 'stripe';
            players[1 - currentPlayer].type = 'solid';
        }
        showMsg(players[currentPlayer].name + ': ' + (players[currentPlayer].type === 'solid' ? 'ลูกทึบ (1-7)' : 'ลูกแถบ (9-15)'));
        updateHUD();
    }

    function checkAllCleared() {
        for (let pi = 0; pi < 2; pi++) {
            const p = players[pi];
            if (!p.type) continue;
            const range = p.type === 'solid' ? [1,2,3,4,5,6,7] : [9,10,11,12,13,14,15];
            const allPocketed = range.every(n => balls.find(b => b.num === n)?.pocketed);
            p.allCleared = allPocketed;
        }
    }

    function respawnCueBall() {
        const cb = balls[0];
        cb.pocketed = false;
        cb.vx = 0; cb.vy = 0;
        cb.x = TBL.l + TBL.w * 0.25;
        cb.y = TBL.t + TBL.h / 2;
    }

    // ===========================
    //  AI
    // ===========================
    function aiTurn() {
        if (state === 'placing') {
            // AI places cue ball
            const cb = balls[0];
            cb.x = TBL.l + TBL.w * 0.25;
            cb.y = TBL.t + TBL.h / 2 + (Math.random() - 0.5) * 100;
            cb.pocketed = false;
            state = 'aiming';
            showMsg('');
            setTimeout(() => aiShoot(), 500);
            return;
        }

        if (state !== 'aiming') return;
        const shot = calculateAIShot();

        // Animate aiming
        aimAngle = shot.angle;
        state = 'charging';
        power = 0;

        const targetPower = shot.power;
        const aimDuration = 600;
        const chargeDuration = 400;

        setTimeout(() => {
            // Charge animation
            const startTime = Date.now();
            const chargeInterval = setInterval(() => {
                const elapsed = Date.now() - startTime;
                power = Math.min(targetPower, (elapsed / chargeDuration) * targetPower);
                updatePowerMeter();
                if (elapsed >= chargeDuration) {
                    clearInterval(chargeInterval);
                    shoot();
                }
            }, 30);
        }, aimDuration);
    }

    function calculateAIShot() {
        const cb = balls[0];
        const p = players[currentPlayer];
        let targetBalls;

        if (p.allCleared) {
            targetBalls = balls.filter(b => b.eight && !b.pocketed);
        } else if (p.type === 'solid') {
            targetBalls = balls.filter(b => b.solid && !b.pocketed);
        } else if (p.type === 'stripe') {
            targetBalls = balls.filter(b => b.stripe && !b.pocketed);
        } else {
            // No type yet (break or first shot)
            targetBalls = balls.filter(b => !b.pocketed && !b.cue && !b.eight);
        }

        if (targetBalls.length === 0) {
            targetBalls = balls.filter(b => b.eight && !b.pocketed);
        }

        let bestShot = null;
        let bestScore = -Infinity;

        for (const target of targetBalls) {
            for (const pocket of POCKETS) {
                // Direction from pocket to target
                const tpx = target.x - pocket.x;
                const tpy = target.y - pocket.y;
                const tpd = Math.sqrt(tpx * tpx + tpy * tpy);
                if (tpd < 1) continue;
                const tnx = tpx / tpd, tny = tpy / tpd;

                // Ghost ball position
                const gx = target.x + tnx * BALL_R * 2;
                const gy = target.y + tny * BALL_R * 2;

                // Angle from cue to ghost
                const cgx = gx - cb.x, cgy = gy - cb.y;
                const cgd = Math.sqrt(cgx * cgx + cgy * cgy);
                if (cgd < BALL_R * 3) continue;

                const angle = Math.atan2(cgy, cgx);

                // Check path clear
                let blocked = false;
                for (const other of balls) {
                    if (other.pocketed || other === target || other.cue) continue;
                    const ox = other.x - cb.x, oy = other.y - cb.y;
                    const proj = (ox * cgx + oy * cgy) / (cgd * cgd);
                    if (proj > 0.05 && proj < 0.95) {
                        const cx = cb.x + proj * cgx;
                        const cy = cb.y + proj * cgy;
                        const dd = Math.sqrt((other.x - cx) ** 2 + (other.y - cy) ** 2);
                        if (dd < BALL_R * 2.5) { blocked = true; break; }
                    }
                }

                if (blocked) continue;

                // Score: closer shots to pocket + closer cue to target = better
                const score = 500 / (tpd + 1) + 300 / (cgd + 1);
                if (score > bestScore) {
                    bestScore = score;
                    bestShot = { angle, power: Math.min(0.3 + cgd / 600, 0.85) };
                }
            }
        }

        if (!bestShot) {
            // Fallback: hit a random target
            const target = targetBalls[0] || balls.find(b => !b.pocketed && !b.cue);
            if (target) {
                const angle = Math.atan2(target.y - cb.y, target.x - cb.x);
                bestShot = { angle, power: 0.4 + Math.random() * 0.3 };
            } else {
                bestShot = { angle: Math.random() * Math.PI * 2, power: 0.4 };
            }
        }

        // Add AI error
        bestShot.angle += (Math.random() - 0.5) * 0.06;
        bestShot.power = Math.max(0.15, Math.min(0.9, bestShot.power + (Math.random() - 0.5) * 0.1));

        return bestShot;
    }

    // ===========================
    //  GAME FLOW
    // ===========================
    function endGame(winnerIdx) {
        state = 'gameover';
        const winner = players[winnerIdx];
        const loser = players[1 - winnerIdx];
        const isP1Win = winnerIdx === 0;

        // Show game over screen
        const goTitle = document.getElementById('go-title');
        const goWinner = document.getElementById('go-winner');
        const goCoins = document.getElementById('go-coins');

        if (gameMode === 'practice') {
            goTitle.textContent = 'GAME OVER';
            goTitle.className = 'gameover-title win';
            goWinner.textContent = winner.name + ' ชนะ!';
            goCoins.textContent = '';
        } else {
            // Check if authenticated user won
            const userWon = (gameMode === 'ai' && isP1Win) || (gameMode === 'local' && isP1Win);

            if (userWon || gameMode === 'local') {
                goTitle.textContent = 'VICTORY!';
                goTitle.className = 'gameover-title win';
                goWinner.textContent = winner.name + ' ชนะ!';
                if (betAmount > 0) {
                    goCoins.textContent = '+' + (betAmount * 2) + ' 💰';
                    goCoins.className = 'gameover-coins';
                } else {
                    goCoins.textContent = '';
                }
            } else {
                goTitle.textContent = 'DEFEAT';
                goTitle.className = 'gameover-title lose';
                goWinner.textContent = winner.name + ' ชนะ...';
                if (betAmount > 0) {
                    goCoins.textContent = '-' + betAmount + ' 💰';
                    goCoins.className = 'gameover-coins lost';
                } else {
                    goCoins.textContent = '';
                }
            }

            // Settle match via API
            if (betAmount > 0 && isAuthenticated) {
                settleMatch(userWon);
            }
        }

        document.getElementById('gameover-screen').classList.add('show');
        if (isAuthenticated) checkWalletStatus();
    }

    function showMsg(text) {
        const el = document.getElementById('game-message');
        if (text) {
            el.textContent = text;
            el.classList.add('show');
            clearTimeout(msgTimer);
            msgTimer = setTimeout(() => el.classList.remove('show'), 2000);
        } else {
            el.classList.remove('show');
        }
    }

    // ===========================
    //  HUD UPDATE
    // ===========================
    function updateHUD() {
        // Player names
        document.getElementById('p1-name').textContent = players[0].name;
        document.getElementById('p2-name').textContent = players[1].name;

        // Active player highlight
        document.getElementById('p1-hud').classList.toggle('active', currentPlayer === 0);
        document.getElementById('p2-hud').classList.toggle('active', currentPlayer === 1);

        // Turn text
        const turnEl = document.getElementById('hud-turn');
        turnEl.textContent = players[currentPlayer].name + ' กำลังเล่น';

        // Bet display
        const betEl = document.getElementById('hud-bet');
        betEl.textContent = betAmount > 0 ? '💰 ' + betAmount : '';

        // Ball type indicators
        updateBallIndicators();

        // Pocketed balls tray
        updatePocketedTray();
    }

    function updateBallIndicators() {
        for (let pi = 0; pi < 2; pi++) {
            const container = document.getElementById(`p${pi + 1}-balls`);
            container.innerHTML = '';
            const p = players[pi];
            if (!p.type) continue;

            const range = p.type === 'solid' ? [1,2,3,4,5,6,7] : [9,10,11,12,13,14,15];
            for (const num of range) {
                const dot = document.createElement('div');
                dot.className = 'p-ball-dot';
                dot.style.background = BALL_CLR[num];
                const b = balls.find(b => b.num === num);
                if (b && b.pocketed) dot.classList.add('pocketed');
                container.appendChild(dot);
            }
        }
    }

    function updatePocketedTray() {
        const tray = document.getElementById('pocketed-tray');
        tray.innerHTML = '';
        for (const b of balls) {
            if (!b.pocketed || b.cue || b.eight) continue;
            const el = document.createElement('div');
            el.className = 'pocketed-ball';
            el.style.background = BALL_CLR[b.num];
            if (b.stripe) {
                el.style.background = `linear-gradient(180deg, #f0f0f0 30%, ${BALL_CLR[b.num]} 30%, ${BALL_CLR[b.num]} 70%, #f0f0f0 70%)`;
            }
            el.textContent = b.num;
            tray.appendChild(el);
        }
    }

    // ===========================
    //  GAME LOOP
    // ===========================
    function gameLoop() {
        if (!running) return;

        if (state === 'moving') {
            updatePhysics();
            if (allStopped()) {
                evaluateTurn();
            }
        }

        if (state === 'shooting') {
            cueAnimT -= 0.15;
            if (cueAnimT <= 0) cueAnimT = 0;
        }

        render();
        requestAnimationFrame(gameLoop);
    }

    function render() {
        drawTable();
        for (const b of balls) drawBall(b);
        drawAimGuide();
        drawCue();
        drawPlacing();
    }

    // ===========================
    //  CANVAS RESIZE
    // ===========================
    function resizeCanvas() {
        const container = document.getElementById('game-container');
        const cw = container.clientWidth;
        const ch = container.clientHeight;
        const aspect = CW / CH;
        let w, h;

        if (cw / ch > aspect) {
            h = ch;
            w = h * aspect;
        } else {
            w = cw;
            h = w / aspect;
        }

        canvas.style.width = w + 'px';
        canvas.style.height = h + 'px';
    }

    // ===========================
    //  LOBBY / UI
    // ===========================
    let selectedMode = 'ai';
    let selectedBet = 10;

    function selectMode(mode) {
        selectedMode = mode;
        document.querySelectorAll('.mode-card').forEach(c => c.classList.remove('selected'));
        document.querySelector(`.mode-card[data-mode="${mode}"]`)?.classList.add('selected');

        // Show/hide bet section
        const betSec = document.getElementById('bet-section');
        if (mode === 'practice') {
            betSec.style.display = 'none';
        } else {
            betSec.style.display = 'block';
        }
    }

    function selectBet(amount) {
        selectedBet = amount;
        document.querySelectorAll('.bet-chip').forEach(c => c.classList.remove('selected'));
        document.querySelector(`.bet-chip[data-bet="${amount}"]`)?.classList.add('selected');
    }

    async function startMatch() {
        gameMode = selectedMode;
        betAmount = (gameMode === 'practice') ? 0 : selectedBet;

        // Setup players
        players[0] = {
            name: @json(Auth::check() ? Auth::user()->name : 'Player 1'),
            type: null, pocketed: [], isAI: false, allCleared: false
        };

        if (gameMode === 'ai') {
            players[1] = { name: '🤖 AI', type: null, pocketed: [], isAI: true, allCleared: false };
        } else if (gameMode === 'local') {
            players[1] = { name: 'Player 2', type: null, pocketed: [], isAI: false, allCleared: false };
        } else {
            players[1] = { name: '🤖 AI', type: null, pocketed: [], isAI: true, allCleared: false };
        }

        // Place bet via API
        if (betAmount > 0 && isAuthenticated) {
            const ok = await placeBet(betAmount);
            if (!ok) return;
        }

        // Hide title, show game
        document.getElementById('title-screen').classList.add('hidden');
        document.getElementById('hud').style.display = 'flex';
        document.getElementById('bottom-hud').style.display = 'flex';
        document.getElementById('btn-quit').style.display = 'block';

        // Setup game
        setupBalls();
        currentPlayer = 0;
        isBreakShot = true;
        state = 'aiming';
        running = true;

        updateHUD();
        showMsg('BREAK!');

        resizeCanvas();
        gameLoop();
    }

    function playAgain() {
        document.getElementById('gameover-screen').classList.remove('show');
        setupBalls();
        currentPlayer = 0;
        isBreakShot = true;
        players[0].type = null; players[0].pocketed = []; players[0].allCleared = false;
        players[1].type = null; players[1].pocketed = []; players[1].allCleared = false;
        state = 'aiming';
        updateHUD();
        showMsg('BREAK!');

        // Re-bet if applicable
        if (betAmount > 0 && isAuthenticated) {
            placeBet(betAmount);
        }
    }

    function backToLobby() {
        document.getElementById('gameover-screen').classList.remove('show');
        document.getElementById('title-screen').classList.remove('hidden');
        document.getElementById('hud').style.display = 'none';
        document.getElementById('bottom-hud').style.display = 'none';
        document.getElementById('btn-quit').style.display = 'none';
        running = false;
        state = 'idle';

        if (isAuthenticated) loadWalletBalance();
    }

    function quitGame() {
        if (state !== 'gameover' && betAmount > 0) {
            if (!confirm('ออกจากเกม? คุณจะเสียเดิมพัน ' + betAmount + ' แต้ม')) return;
        }
        backToLobby();
    }

    function showToast(msg) {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 3000);
    }

    // ===========================
    //  API INTEGRATION
    // ===========================
    async function fetchApi(url, options = {}) {
        const headers = {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
        };
        return fetch(url, { ...options, headers, credentials: 'same-origin' });
    }

    async function loadWalletBalance() {
        if (!isAuthenticated) return;
        try {
            const res = await fetchApi('/api/games/8ball-pool/check-wallet');
            const data = await res.json();
            if (data.success) {
                walletBalance = parseFloat(data.balance || 0);
                coinBalance = parseFloat(data.coin_balance || 0);
                const balEl = document.getElementById('title-balance');
                if (balEl) balEl.textContent = walletBalance.toFixed(0);
            }
        } catch (e) {
            console.error('Wallet check failed:', e);
        }
    }

    async function placeBet(amount) {
        if (!isAuthenticated) {
            showToast('กรุณาเข้าสู่ระบบก่อนวางเดิมพัน');
            return false;
        }
        try {
            const res = await fetchApi('/api/games/8ball-pool/place-bet', {
                method: 'POST',
                body: JSON.stringify({ amount })
            });
            const data = await res.json();
            if (data.success) {
                matchId = data.match_id;
                walletBalance = data.remaining_balance;
                return true;
            } else {
                showToast(data.message || 'ไม่สามารถวางเดิมพันได้');
                return false;
            }
        } catch (e) {
            showToast('เกิดข้อผิดพลาด');
            return false;
        }
    }

    async function settleMatch(userWon) {
        if (!isAuthenticated || !matchId) return;
        try {
            await fetchApi('/api/games/8ball-pool/settle-match', {
                method: 'POST',
                body: JSON.stringify({
                    match_id: matchId,
                    won: userWon,
                    amount: betAmount
                })
            });
        } catch (e) {
            console.error('Settle failed:', e);
        }
    }

    async function checkWalletStatus() {
        if (!isAuthenticated) return;
        try {
            const res = await fetchApi('/api/games/8ball-pool/check-wallet');
            const data = await res.json();
            walletBalance = parseFloat(data.balance || 0);
            coinBalance = parseFloat(data.coin_balance || 0);

            const wBal = document.getElementById('pay-wallet-bal');
            const cBal = document.getElementById('pay-coin-bal');
            if (wBal) wBal.textContent = `(${walletBalance.toFixed(0)})`;
            if (cBal) cBal.textContent = `(${coinBalance.toFixed(0)})`;

            selectPayment(walletBalance >= 1 ? 'wallet' : (coinBalance >= 1 ? 'coin' : 'wallet'));
        } catch (e) {}
    }

    function selectPayment(method) {
        selectedPayment = method;
        const wBtn = document.getElementById('pay-wallet-btn');
        const cBtn = document.getElementById('pay-coin-btn');
        const statusEl = document.getElementById('wallet-status');
        const saveBtn = document.getElementById('save-btn');

        if (!wBtn || !cBtn) return;

        wBtn.classList.toggle('active', method === 'wallet');
        cBtn.classList.toggle('active', method === 'coin');

        const bal = method === 'wallet' ? walletBalance : coinBalance;
        const label = method === 'wallet' ? 'แต้ม' : 'เหรียญ';

        if (bal >= 1) {
            statusEl.innerHTML = `<p style="color:#39ff14;">💰 ${label}คงเหลือ: <b>${bal.toFixed(0)}</b> ${label}</p>`;
            if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = `✅ บันทึกผล (1 ${label})`; }
        } else {
            statusEl.innerHTML = `<p style="color:#ff3366;">⚠️ ${label}ไม่เพียงพอ!</p>
                <a href="/user/wallet/topup" style="color:#ffd700;font-size:12px;">💳 เติมเงิน</a>`;
            if (saveBtn) { saveBtn.disabled = true; }
        }
    }

    async function saveResult() {
        if (!isAuthenticated) return;
        const saveBtn = document.getElementById('save-btn');
        if (saveBtn) { saveBtn.disabled = true; saveBtn.textContent = '⏳ กำลังบันทึก...'; }

        try {
            const res = await fetchApi('/api/games/8ball-pool/save-result', {
                method: 'POST',
                body: JSON.stringify({
                    won: document.getElementById('go-title').textContent === 'VICTORY!',
                    bet_amount: betAmount,
                    payment_method: selectedPayment,
                    player_name: players[0].name
                })
            });
            const data = await res.json();
            if (data.success) {
                showToast('✅ บันทึกผลสำเร็จ!');
                if (saveBtn) saveBtn.textContent = '✅ บันทึกแล้ว!';
                walletBalance = data.remaining_balance || walletBalance;
            } else {
                showToast(data.message || 'บันทึกไม่สำเร็จ');
                if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = '✅ บันทึกผล'; }
            }
        } catch (e) {
            showToast('เกิดข้อผิดพลาด');
            if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = '✅ บันทึกผล'; }
        }
    }

    async function loadLeaderboard() {
        try {
            const res = await fetch('/games/8ball-pool/leaderboard');
            const data = await res.json();
            const list = document.getElementById('title-lb-list');
            if (!list) return;

            if (!data.data || data.data.length === 0) {
                list.innerHTML = '<div style="text-align:center;padding:20px;color:#556;">ยังไม่มีข้อมูล</div>';
                return;
            }

            list.innerHTML = data.data.map((entry, i) => {
                const rankClass = i < 3 ? ` rank-${i + 1}` : '';
                const medal = i === 0 ? '🥇' : i === 1 ? '🥈' : i === 2 ? '🥉' : (i + 1);
                return `<div class="lb-item${rankClass}">
                    <span class="lb-rank">${medal}</span>
                    <span class="lb-name">${entry.player_name || 'Player'}</span>
                    <span class="lb-score">${entry.score}</span>
                </div>`;
            }).join('');
        } catch (e) {
            console.error('Leaderboard load failed:', e);
        }
    }

    // Session keep-alive ping
    setInterval(() => {
        if (isAuthenticated) fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' }).catch(() => {});
    }, 600000);

    // ===========================
    //  INIT
    // ===========================
    function init() {
        canvas = document.getElementById('pool-canvas');
        ctx = canvas.getContext('2d');
        canvas.width = CW;
        canvas.height = CH;

        // Input events
        canvas.addEventListener('mousedown', onMouseDown);
        canvas.addEventListener('mousemove', onMouseMove);
        canvas.addEventListener('mouseup', onMouseUp);
        canvas.addEventListener('mouseleave', onMouseUp);
        canvas.addEventListener('touchstart', onTouchStart, { passive: false });
        canvas.addEventListener('touchmove', onTouchMove, { passive: false });
        canvas.addEventListener('touchend', onTouchEnd, { passive: false });

        // Prevent context menu
        canvas.addEventListener('contextmenu', e => e.preventDefault());

        window.addEventListener('resize', resizeCanvas);
        resizeCanvas();

        // Draw idle table
        setupBalls();
        render();

        // Load data
        if (isAuthenticated) loadWalletBalance();
        loadLeaderboard();
    }

    document.addEventListener('DOMContentLoaded', init);
    </script>
</body>
</html>
