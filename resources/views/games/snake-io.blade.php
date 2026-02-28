<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <title>Snake.io - Thai Prompt Games</title>

    <!-- SEO & Social Sharing -->
    <meta name="description" content="เล่นเกมงู Snake.io ออนไลน์ฟรี! แข่งกับผู้เล่นจริงแบบ Multiplayer เก็บอาหาร โตขึ้น หลบงูคนอื่น ใครยาวที่สุดชนะ!">
    <meta name="keywords" content="snake.io, เกมงู, multiplayer, online game, Thai Prompt Games, เกมออนไลน์">

    <!-- Open Graph (Facebook, LINE, etc.) -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Snake.io - เกมงูออนไลน์ Multiplayer">
    <meta property="og:description" content="เล่นเกมงู Snake.io ออนไลน์ฟรี! แข่งกับผู้เล่นจริงแบบ Multiplayer เก็บอาหาร โตขึ้น หลบงูคนอื่น ใครยาวที่สุดชนะ!">
    <meta property="og:image" content="{{ asset('images/games/snake-io-og.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="Thai Prompt Games">
    <meta property="og:locale" content="th_TH">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Snake.io - เกมงูออนไลน์ Multiplayer">
    <meta name="twitter:description" content="เล่นเกมงู Snake.io ออนไลน์ฟรี! แข่งกับผู้เล่นจริง Multiplayer">
    <meta name="twitter:image" content="{{ asset('images/games/snake-io-og.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Noto+Sans+Thai:wght@400;600;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">

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

        /* 🏆 Title Screen - Top 100 Leaderboard */
        .title-leaderboard {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            width: 320px;
            max-height: 70vh;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(12px);
            border: 2px solid rgba(0, 255, 255, 0.4);
            border-radius: 16px;
            padding: 0;
            overflow: hidden;
            z-index: 110;
            animation: slideInRight 0.8s ease-out;
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateY(-50%) translateX(40px); }
            to { opacity: 1; transform: translateY(-50%) translateX(0); }
        }

        .title-lb-header {
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.3), rgba(255, 165, 0, 0.2));
            padding: 14px 16px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 215, 0, 0.3);
        }

        .title-lb-header h3 {
            color: #ffd700;
            font-size: 18px;
            margin: 0;
            text-shadow: 0 0 12px rgba(255, 215, 0, 0.6);
            letter-spacing: 2px;
        }

        .title-lb-scroll {
            max-height: calc(70vh - 60px);
            overflow-y: auto;
            padding: 8px;
        }

        .title-lb-scroll::-webkit-scrollbar {
            width: 4px;
        }
        .title-lb-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .title-lb-scroll::-webkit-scrollbar-thumb {
            background: rgba(0, 255, 255, 0.3);
            border-radius: 2px;
        }

        .title-lb-item {
            display: flex;
            align-items: center;
            padding: 8px 10px;
            margin: 3px 0;
            border-radius: 8px;
            font-size: 13px;
            transition: background 0.2s;
        }

        .title-lb-item:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .title-lb-item .lb-rank {
            width: 28px;
            font-weight: 700;
            text-align: center;
            flex-shrink: 0;
        }

        .title-lb-item .lb-name {
            flex: 1;
            color: #e0e0e0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            padding: 0 8px;
        }

        .title-lb-item .lb-score {
            color: #00ffff;
            font-weight: 700;
            flex-shrink: 0;
            font-variant-numeric: tabular-nums;
        }

        /* อันดับ 1-3 พิเศษ */
        .title-lb-item.rank-1 {
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.25), rgba(255, 165, 0, 0.15));
            border: 1px solid rgba(255, 215, 0, 0.4);
        }
        .title-lb-item.rank-1 .lb-rank { color: #ffd700; font-size: 16px; }
        .title-lb-item.rank-1 .lb-name { color: #fff; font-weight: 700; }
        .title-lb-item.rank-1 .lb-score { color: #ffd700; }

        .title-lb-item.rank-2 {
            background: linear-gradient(135deg, rgba(192, 192, 192, 0.2), rgba(169, 169, 169, 0.1));
            border: 1px solid rgba(192, 192, 192, 0.3);
        }
        .title-lb-item.rank-2 .lb-rank { color: #c0c0c0; font-size: 15px; }
        .title-lb-item.rank-2 .lb-name { color: #e8e8e8; font-weight: 600; }
        .title-lb-item.rank-2 .lb-score { color: #c0c0c0; }

        .title-lb-item.rank-3 {
            background: linear-gradient(135deg, rgba(205, 127, 50, 0.2), rgba(184, 115, 51, 0.1));
            border: 1px solid rgba(205, 127, 50, 0.3);
        }
        .title-lb-item.rank-3 .lb-rank { color: #cd7f32; font-size: 15px; }
        .title-lb-item.rank-3 .lb-name { color: #e0d0c0; font-weight: 600; }
        .title-lb-item.rank-3 .lb-score { color: #cd7f32; }

        .title-lb-empty {
            text-align: center;
            padding: 30px;
            color: #666;
            font-style: italic;
        }

        .title-lb-loading {
            text-align: center;
            padding: 30px;
            color: #00ffff;
        }

        .title-lb-loading .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(0, 255, 255, 0.3);
            border-top-color: #00ffff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-right: 8px;
            vertical-align: middle;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* 🏆 In-Game Top 3 Crown Display */
        .ingame-top3 {
            position: absolute;
            top: 70px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 8px;
            z-index: 90;
            pointer-events: none;
        }

        .top3-card {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            backdrop-filter: blur(8px);
            white-space: nowrap;
            transition: opacity 0.3s ease;
        }

        .top3-card.gold {
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.35), rgba(255, 165, 0, 0.2));
            border: 1.5px solid rgba(255, 215, 0, 0.6);
            color: #ffd700;
            text-shadow: 0 0 8px rgba(255, 215, 0, 0.5);
        }

        .top3-card.silver {
            background: linear-gradient(135deg, rgba(192, 192, 192, 0.3), rgba(169, 169, 169, 0.15));
            border: 1.5px solid rgba(192, 192, 192, 0.5);
            color: #e0e0e0;
            text-shadow: 0 0 6px rgba(192, 192, 192, 0.4);
        }

        .top3-card.bronze {
            background: linear-gradient(135deg, rgba(205, 127, 50, 0.3), rgba(184, 115, 51, 0.15));
            border: 1.5px solid rgba(205, 127, 50, 0.5);
            color: #e8a060;
            text-shadow: 0 0 6px rgba(205, 127, 50, 0.4);
        }

        .top3-medal {
            font-size: 16px;
            flex-shrink: 0;
        }

        .top3-name {
            max-width: 80px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .top3-score {
            font-variant-numeric: tabular-nums;
            opacity: 0.9;
        }

        /* Responsive: ซ่อน leaderboard title screen บน mobile */
        @media (max-width: 768px) {
            .title-leaderboard {
                position: relative;
                right: auto;
                top: auto;
                transform: none;
                width: 90%;
                max-width: 340px;
                max-height: 35vh;
                margin: 15px auto 0;
                animation: fadeInUp 0.6s ease-out;
            }

            .title-lb-scroll {
                max-height: calc(35vh - 50px);
            }

            .title-content {
                padding-top: 10px;
            }

            .title-logo {
                font-size: 48px !important;
            }

            .title-subtitle {
                font-size: 14px !important;
                margin-bottom: 15px !important;
            }

            .ingame-top3 {
                top: 55px;
                gap: 4px;
            }

            .top3-card {
                padding: 4px 8px;
                font-size: 11px;
            }

            .top3-name {
                max-width: 50px;
            }
        }

        /* ✨ Title Screen - หน้าแรกสะอาด */
        #title-screen {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #0a0a1a 0%, #1a1a2e 50%, #0a0a1a 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 100;
            overflow: hidden;
        }

        #title-screen.hidden {
            display: none;
        }

        /* Canvas พื้นหลังเคลื่อนไหว */
        #title-bg-canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.3;
            filter: blur(2px);
        }

        .title-content {
            position: relative;
            z-index: 2;
            text-align: center;
            animation: fadeInUp 1s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .title-logo {
            font-size: 96px;
            font-weight: 900;
            background: linear-gradient(45deg, #00ffff, #00ff00, #00ffff);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: none;
            margin-bottom: 10px;
            animation: glowPulse 2s ease-in-out infinite;
        }

        @keyframes glowPulse {
            0%, 100% {
                filter: drop-shadow(0 0 20px rgba(0, 255, 255, 0.8));
            }
            50% {
                filter: drop-shadow(0 0 40px rgba(0, 255, 255, 1));
            }
        }

        .title-subtitle {
            font-size: 20px;
            color: #00ffff;
            margin-bottom: 50px;
            letter-spacing: 3px;
            text-transform: uppercase;
            opacity: 0.9;
        }

        .title-version {
            font-size: 12px;
            color: #888;
            margin-top: 40px;
            opacity: 0.7;
        }

        /* ✨ Character Setup Screen - หน้าเลือกสี */
        #character-setup-screen {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 100;
            overflow-y: auto;
            padding: 20px;
        }

        #character-setup-screen.show {
            display: flex;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .setup-title {
            font-size: 48px;
            color: #00ffff;
            text-shadow: 0 0 30px rgba(0, 255, 255, 1);
            margin-bottom: 30px;
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

        /* ✅ Fullscreen Toggle Button */
        #fullscreen-toggle {
            position: absolute;
            bottom: 20px;
            right: 80px; /* ห่างจาก sound toggle */
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid #ffaa00;
            color: #ffaa00;
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

        #fullscreen-toggle:hover {
            background: rgba(255, 170, 0, 0.2);
            transform: scale(1.1);
        }

        #fullscreen-toggle.active {
            border-color: #00ff00;
            color: #00ff00;
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
            bottom: 10px;
            left: 10px;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            padding: 3px 10px;
            display: none;
            align-items: center;
            gap: 5px;
            font-family: 'Orbitron', 'Noto Sans Thai', sans-serif;
            font-size: 9px;
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

        /* ✅ Responsive: จอเล็กมาก (มือถือ) */
        @media (max-width: 480px) {
            #hud {
                top: 10px;
                left: 10px;
                right: 10px;
            }

            .hud-top {
                gap: 8px;
            }

            .hud-item {
                padding: 8px 12px;
            }

            .hud-label {
                font-size: 9px;
            }

            .hud-value {
                font-size: 18px;
            }

            /* ซ่อน leaderboard ในจอเล็ก */
            .leaderboard {
                display: none;
            }

            /* ย่อ powerup indicators */
            .powerup-indicators {
                top: 80px;
                left: 10px;
            }

            .powerup-indicator {
                padding: 6px 10px;
                font-size: 11px;
            }

            /* Start screen */
            #start-screen h1 {
                font-size: 36px;
            }

            .name-input {
                font-size: 14px;
                min-width: 200px;
                padding: 10px 20px;
            }

            .btn {
                font-size: 16px;
                padding: 12px 30px;
            }

            .skin-option {
                width: 50px;
                height: 50px;
            }

            /* ปุ่ม sound toggle */
            #sound-toggle {
                width: 40px;
                height: 40px;
                font-size: 18px;
                bottom: 10px;
                right: 10px;
            }

            /* ปุ่ม fullscreen toggle */
            #fullscreen-toggle {
                width: 40px;
                height: 40px;
                font-size: 18px;
                bottom: 10px;
                right: 60px;
            }
        }

        /* ✅ Responsive: จอแนวนอน (landscape) */
        @media (max-height: 500px) and (orientation: landscape) {
            #hud {
                top: 5px;
                left: 5px;
            }

            .hud-top {
                gap: 5px;
            }

            .hud-item {
                padding: 5px 10px;
            }

            .hud-label {
                font-size: 8px;
                margin-bottom: 2px;
            }

            .hud-value {
                font-size: 16px;
            }

            /* ซ่อน leaderboard ใน landscape */
            .leaderboard {
                display: none;
            }

            /* ย่อ powerup indicators */
            .powerup-indicators {
                top: 60px;
                left: 5px;
            }

            .powerup-indicator {
                padding: 4px 8px;
                font-size: 10px;
                margin-bottom: 3px;
            }

            /* Start screen ให้เล็กลง */
            #start-screen h1 {
                font-size: 32px;
                margin-bottom: 10px;
            }

            #start-screen p {
                font-size: 12px !important;
                margin: 3px 0 !important;
            }

            .name-input {
                font-size: 14px;
                min-width: 200px;
                padding: 8px 16px;
                margin: 10px 0;
            }

            .btn {
                font-size: 16px;
                padding: 10px 25px;
                margin: 5px;
            }

            .skin-selector {
                margin: 10px 0;
                gap: 10px;
            }

            .skin-option {
                width: 45px;
                height: 45px;
            }

            /* Custom color picker ให้เล็กลง */
            #start-screen > div {
                padding: 10px !important;
                margin: 10px 0 !important;
            }

            #start-screen h3 {
                font-size: 13px !important;
                margin-bottom: 8px !important;
            }

            input[type="color"] {
                width: 45px !important;
                height: 30px !important;
            }

            /* ปุ่ม sound toggle */
            #sound-toggle {
                width: 35px;
                height: 35px;
                font-size: 16px;
                bottom: 5px;
                right: 5px;
            }

            /* ปุ่ม fullscreen toggle */
            #fullscreen-toggle {
                width: 35px;
                height: 35px;
                font-size: 16px;
                bottom: 5px;
                right: 50px;
            }
        }

        /* ============================================================
         * ✅ Mobile/Tablet UX Fixes (v3.1.0)
         * ============================================================ */

        /* Safe area insets สำหรับ iPhone notch/Dynamic Island */
        @supports(padding: max(0px)) {
            #sound-toggle {
                bottom: max(10px, env(safe-area-inset-bottom, 10px));
                right: max(10px, env(safe-area-inset-right, 10px));
            }
            #fullscreen-toggle {
                bottom: max(10px, env(safe-area-inset-bottom, 10px));
                right: max(60px, calc(50px + env(safe-area-inset-right, 10px)));
            }
            #music-controls {
                bottom: max(10px, env(safe-area-inset-bottom, 10px));
                left: max(10px, env(safe-area-inset-left, 10px));
            }
            #game-version {
                bottom: max(10px, env(safe-area-inset-bottom, 10px));
                left: max(20px, env(safe-area-inset-left, 20px));
            }
        }

        /* ปรับ touch targets ขั้นต่ำ 44px สำหรับ touchscreen */
        @media (pointer: coarse) {
            #music-controls button {
                min-width: 40px;
                min-height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            #music-volume {
                height: 8px;
                min-width: 50px;
            }
            input[type="color"] {
                min-width: 50px !important;
                min-height: 44px !important;
            }
            .skin-option {
                min-width: 48px;
                min-height: 48px;
            }
            #sound-toggle, #fullscreen-toggle {
                min-width: 44px;
                min-height: 44px;
            }
        }

        /* จอเล็กมาก (320px เช่น iPhone SE) - landscape */
        @media (max-width: 600px) and (max-height: 400px) and (orientation: landscape) {
            .title-logo { font-size: 28px !important; }
            .title-subtitle { font-size: 12px !important; margin-bottom: 10px !important; }
            .name-input { min-width: 180px; font-size: 13px; padding: 8px 14px; }
            .btn { font-size: 14px; padding: 8px 20px; }
            .skin-option { width: 40px; height: 40px; }
            .skin-selector { gap: 8px; }
            #controls-mobile { font-size: 10px !important; bottom: 5px; }
            #controls-mobile div:last-child { display: none; }
            #game-version { font-size: 8px; bottom: 55px; }

            /* Character setup ลดขนาด */
            #character-setup-screen { padding: 10px !important; }
            #character-setup-screen > div { max-width: 95vw !important; }
            #character-setup-screen h3 { font-size: 12px !important; }
            input[type="color"] { width: 40px !important; height: 36px !important; }

            /* Game over ลดขนาด */
            #game-over { width: 90vw !important; padding: 15px !important; }
            #game-over h2 { font-size: 20px !important; }

            /* Connection status ย้ายให้ไม่บัง */
            #connection-status { font-size: 10px; padding: 4px 10px; }

            /* Leaderboard ย่อ */
            .leaderboard { max-height: 100px; }
            .leaderboard-entry { padding: 3px 6px; margin: 1px 0; font-size: 10px; }
        }

        /* แท็บเล็ต landscape (iPad) */
        @media (min-width: 768px) and (max-height: 1024px) and (orientation: landscape) {
            .leaderboard { max-height: 250px; }
        }

        /* มือถือ portrait - ลดขนาด leaderboard */
        @media (max-width: 768px) {
            .leaderboard { max-height: 150px; }
            .leaderboard-entry { padding: 4px 6px; margin: 2px 0; font-size: 11px; }
        }

        /* ซ่อน landscape lock overlay บน desktop/tablet ใหญ่ */
        @media (min-width: 901px) {
            #landscape-lock { display: none !important; }
        }

        /* Landscape lock responsive ย่อ */
        @media (max-width: 480px) and (orientation: portrait) {
            #landscape-lock h2 { font-size: 20px !important; }
            #landscape-lock p { font-size: 12px !important; }
            #landscape-lock > div:first-child { font-size: 48px !important; }
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

        <!-- Leaderboard (sidebar) -->
        <div class="leaderboard">
            <h3>🏆 TOP PLAYERS</h3>
            <div id="leaderboard-list"></div>
        </div>

        <!-- 🏆 In-Game Top 3 Display (แสดงตลอดระหว่างเล่น) -->
        <div class="ingame-top3" id="ingame-top3"></div>

        <!-- Power-up Indicators -->
        <div class="powerup-indicators" id="powerup-indicators"></div>

        <!-- ✨ Title Screen - หน้าแรกสะอาด -->
        <div id="title-screen">
            <!-- Canvas พื้นหลังเคลื่อนไหว -->
            <canvas id="title-bg-canvas"></canvas>

            <!-- เนื้อหาหลัก -->
            <div class="title-content">
                <h1 class="title-logo">🐍 SNAKE.IO</h1>
                <p class="title-subtitle">กินอาหารให้มากที่สุด แต่อย่าชนอะไร!</p>

                <button class="btn" id="play-btn" style="margin-top: 20px;">
                    ▶️ PLAY NOW
                </button>

                @guest
                    <div style="margin-top: 30px;">
                        <a href="{{ route('login') }}" style="color: #00ffff; text-decoration: none; margin: 0 10px; font-size: 14px;">เข้าสู่ระบบ</a>
                        |
                        <a href="{{ route('register') }}" style="color: #00ffff; text-decoration: none; margin: 0 10px; font-size: 14px;">สมัครสมาชิก</a>
                    </div>
                @endguest

                <!-- 🎵 Music Hint (แสดงเมื่อ autoplay ถูกบล็อค) -->
                <div id="music-hint" style="
                    display: none; align-items: center; justify-content: center; gap: 8px;
                    margin-top: 20px; padding: 10px 20px;
                    background: rgba(0, 255, 204, 0.1); border: 1px solid rgba(0, 255, 204, 0.3);
                    border-radius: 20px; cursor: pointer; animation: pulse 2s ease-in-out infinite;
                " onclick="if(window._onFirstUserInteract) window._onFirstUserInteract()">
                    <span style="font-size: 20px;">🎵</span>
                    <span style="color: #00ffcc; font-size: 13px;">แตะเพื่อเปิดเพลง</span>
                </div>

                <p class="title-version">
                    Version 2.6.0 - Advanced AI<br>
                    🎮 Created by XMAN STUDIO © 2025
                </p>
            </div>

            <!-- 🏆 Top 100 Leaderboard -->
            <div class="title-leaderboard" id="title-leaderboard">
                <div class="title-lb-header">
                    <h3>🏆 TOP 100 PLAYERS</h3>
                </div>
                <div class="title-lb-scroll" id="title-lb-list">
                    <div class="title-lb-loading">
                        <span class="spinner"></span> กำลังโหลด...
                    </div>
                </div>
            </div>
        </div>

        <!-- ✨ Character Setup Screen - หน้าเลือกสีและตั้งชื่อ -->
        <div id="character-setup-screen">
            <h1 class="setup-title">🎨 ปรับแต่งตัวละครของคุณ</h1>

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
            <div style="margin: 20px 0; padding: 20px; background: rgba(0, 0, 0, 0.5); border-radius: 10px; max-width: 500px;">
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
            <div style="margin: 20px 0; padding: 15px; background: rgba(0, 255, 255, 0.1); border: 1px solid #00ffff; border-radius: 10px; max-width: 500px;">
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

            <div style="display: flex; gap: 15px; margin-top: 30px;">
                <button class="btn" id="back-to-title-btn" style="background: linear-gradient(135deg, #666, #444);">
                    ← ย้อนกลับ
                </button>
                <button class="btn" id="start-game-btn">
                    ▶️ เริ่มเล่น
                </button>
            </div>
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
                        ค่าบันทึก: <span style="color: #ffff00; font-weight: bold;">1 แต้ม หรือ 1 เหรียญ</span>
                    </p>

                    <!-- ✅ เลือกวิธีจ่าย: Wallet หรือ Coin -->
                    <div id="payment-method-selector" style="margin: 12px 0; display: flex; gap: 8px; justify-content: center; flex-wrap: wrap;">
                        <button type="button" id="pay-wallet-btn" class="btn"
                            style="padding: 8px 16px; font-size: 13px; border: 2px solid transparent; background: linear-gradient(135deg, #ffaa00, #ff8800); transition: all 0.2s;"
                            onclick="selectPaymentMethod('wallet')">
                            💰 แต้ม <span id="pay-wallet-bal" style="opacity:0.8;">(...)</span>
                        </button>
                        <button type="button" id="pay-coin-btn" class="btn"
                            style="padding: 8px 16px; font-size: 13px; border: 2px solid transparent; background: linear-gradient(135deg, #aa66ff, #6633cc); transition: all 0.2s;"
                            onclick="selectPaymentMethod('coin')">
                            🪙 เหรียญ <span id="pay-coin-bal" style="opacity:0.8;">(...)</span>
                        </button>
                    </div>

                    <div id="wallet-status" style="margin: 10px 0; padding: 10px; background: rgba(255, 255, 255, 0.1); border-radius: 5px;">
                        <p style="color: #ccc; font-size: 14px;">⏳ กำลังโหลดข้อมูล...</p>
                    </div>
                    <div style="margin-top: 15px; display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                        <button class="btn" id="save-score-btn" style="background: linear-gradient(135deg, #00ff00, #00aa00);">
                            ✅ บันทึกคะแนน
                        </button>
                        <button class="btn" id="skip-save-btn" style="background: linear-gradient(135deg, #666, #444);">
                            ❌ ไม่บันทึก
                        </button>
                    </div>
                </div>
                @endauth
            </div>

            <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                <button class="btn" id="restart-btn">🔄 PLAY AGAIN</button>
                <button class="btn" id="back-title-btn" style="background: linear-gradient(135deg, #666, #444);">🏠 กลับหน้าหลัก</button>
            </div>
        </div>

        <!-- Controls (Desktop) -->
        <div id="controls">
            <span class="key">↑</span> <span class="key">↓</span> <span class="key">←</span> <span class="key">→</span> or
            <span class="key">MOUSE</span> to move |
            <span class="key">SPACE</span> / <span class="key">RIGHT CLICK</span> boost
        </div>

        <!-- Controls (Mobile) -->
        <div id="controls-mobile">
            <div style="color: #00ffff; margin-bottom: 5px;">👆 วาดนิ้วไปที่ต้องการ | 👆👆 แตะ 2 นิ้วเพื่อเร่ง</div>
            <div style="font-size: 10px; opacity: 0.7;">Swipe to move | 2 fingers to boost</div>
        </div>

        <!-- ✅ Music Controls Panel -->
        <div id="music-controls" style="
            position: fixed; bottom: 10px; left: 10px; z-index: 100;
            background: rgba(0,0,0,0.75); border-radius: 12px; padding: 6px 12px;
            display: flex; align-items: center; gap: 6px;
            backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.1);
        ">
            <button id="music-toggle-btn" onclick="toggleMusic()" title="เล่น/หยุดเพลง"
                style="background:none;border:none;color:#fff;font-size:18px;cursor:pointer;padding:4px;">🎵</button>
            <button onclick="playNextTrack()" title="เพลงถัดไป"
                style="background:none;border:none;color:#fff;font-size:14px;cursor:pointer;padding:4px;">⏭</button>
            <button id="music-mode-btn" onclick="setPlayMode(musicPlayer.playMode==='sequential'?'shuffle':musicPlayer.playMode==='shuffle'?'loop':'sequential')" title="โหมดเล่น"
                style="background:none;border:none;color:#fff;font-size:14px;cursor:pointer;padding:4px;">➡️</button>
            <input id="music-volume" type="range" min="0" max="100" value="50"
                oninput="setMusicVolume(this.value/100)"
                style="width:60px;height:4px;accent-color:#00ffcc;cursor:pointer;">
            <button onclick="toggleMute()" title="ปิด/เปิดเสียง"
                style="background:none;border:none;color:#fff;font-size:14px;cursor:pointer;padding:4px;">🔊</button>
            <span id="music-track-name" style="color:#00ffcc;font-size:11px;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:600;"></span>
        </div>

        <!-- Sound Toggle -->
        <button id="sound-toggle" title="Toggle Sound">🔊</button>

        <!-- ✅ Fullscreen Toggle -->
        <button id="fullscreen-toggle" title="Toggle Fullscreen">⛶</button>

        <!-- ✅ Landscape Lock Overlay (แนวตั้ง → บังคับให้หมุนจอ) -->
        <div id="landscape-lock" style="
            display:none; position:fixed; top:0; left:0; width:100%; height:100%; z-index:9999;
            background:linear-gradient(135deg, #0a0a1a 0%, #1a1a2e 100%);
            color:#fff; flex-direction:column; justify-content:center; align-items:center; text-align:center;
        ">
            <div style="font-size:60px;margin-bottom:20px;animation:rotate-phone 2s ease-in-out infinite;">📱</div>
            <h2 style="font-size:24px;margin-bottom:10px;color:#00ffcc;">กรุณาหมุนหน้าจอ</h2>
            <p style="font-size:14px;color:rgba(255,255,255,0.6);">เกมนี้รองรับการเล่นแนวนอนเท่านั้น</p>
            <p style="font-size:12px;color:rgba(255,255,255,0.4);margin-top:10px;">Please rotate your device to landscape</p>
        </div>
        <style>
            @keyframes rotate-phone {
                0%, 100% { transform: rotate(0deg); }
                25% { transform: rotate(90deg); }
                50% { transform: rotate(90deg); }
                75% { transform: rotate(0deg); }
            }
            /* ซ่อนเกมเมื่อแนวตั้งบนมือถือ */
            @media (max-width: 900px) and (orientation: portrait) {
                #landscape-lock { display: flex !important; }
                #game-container canvas,
                .hud, #leaderboard-container, #music-controls, #sound-toggle, #fullscreen-toggle,
                #title-screen, #character-setup-screen, #game-over { visibility: hidden; }
            }
            /* Music controls responsive */
            @media (max-width: 768px) {
                #music-controls { bottom: 5px; left: 5px; padding: 4px 8px; gap: 3px; }
                #music-controls button { font-size: 14px !important; padding: 2px !important; }
                #music-controls input[type=range] { width: 40px; }
                #music-track-name { display: none; }
            }
        </style>

        <!-- Game Version -->
        <div id="game-version">
            <span class="version-label">Snake.io</span> v3.1.0-mp |
            <span style="color: rgba(0, 200, 255, 0.5);">Multiplayer</span> +
            <span style="color: rgba(0, 255, 100, 0.5);">Anti-cheat</span>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script defer src="/js/optimized-touch-input.js"></script>
    <script defer src="/js/snake-multiplayer.js"></script>
    <script defer src="/js/snake-sync-client.js"></script>

    <script>
        /**
         * ✅ MULTIPLAYER ARCHITECTURE: Client-side Prediction
         *
         * แนวคิด:
         * -------
         * แทนที่จะ sync ตำแหน่งทุก frame (ทำให้ค้าง + lag)
         * เราใช้วิธี "Client-side Prediction with Server Reconciliation"
         *
         * วิธีทำงาน:
         * ----------
         * 1. Client ส่ง INPUT ไป Server (realtime):
         *    - direction (ทิศทางที่เคลื่อนไหว)
         *    - isBoosting (กำลังเร่งความเร็วหรือไม่)
         *    - ข้อมูลเล็ก ส่งได้บ่อย ไม่ค้าง
         *
         * 2. Client คำนวณ movement เอง (60 FPS):
         *    - ไม่ต้องรอ Server ส่งตำแหน่งมา
         *    - smooth ไม่กระตุก แม้ ping สูง
         *
         * 3. Server ส่ง Position Correction กลับมา (ทุก 500ms):
         *    - ตำแหน่งที่แท้จริงจาก Server (authoritative)
         *    - Client ทำ smooth lerp correction
         *
         * 4. Rubber Banding Prevention:
         *    - ถ้าห่าง > 3 หน่วย = teleport (ป้องกัน cheating)
         *    - ถ้าห่าง < 3 หน่วย = lerp ช้าๆ (smooth)
         *
         * ข้อดี:
         * ------
         * ✅ Real-time มากขึ้น (lag แค่ milliseconds)
         * ✅ Smooth 60 FPS (ไม่ค้าง)
         * ✅ ลด bandwidth 80% (ส่งแค่ input)
         * ✅ Graceful degradation (ถ้า lag = ผู้เล่นอื่นเคลื่อนไหวต่อ)
         *
         * ข้อเสีย:
         * -------
         * ❌ ซับซ้อนกว่า (ต้องจัดการ correction)
         * ❌ อาจมี rubber banding เล็กน้อย
         *
         * ดูเพิ่มเติม:
         * - https://www.gabrielgambetta.com/client-side-prediction-server-reconciliation.html
         * - https://gafferongames.com/post/what_every_programmer_needs_to_know_about_game_networking/
         */

        // Game configuration (จะโหลดจาก API)
        const CONFIG = {
            WORLD_SIZE: 200,
            FOOD_COUNT: 100,
            BOT_COUNT: 30, // เพิ่มเป็น 30 บอท
            INITIAL_LENGTH: 5,
            SEGMENT_SIZE: 0.5,
            MOVEMENT_SPEED: 0.15,
            BOOST_SPEED: 0.3,
            TURN_SPEED: 0.05,
            FOOD_VALUE: 1, // กินอาหาร 1 ก้อนได้ 1 คะแนน (ต้องกิน 10 คะแนนถึงจะเพิ่ม 1 ปล้อง)
            COLLISION_DISTANCE: 0.6,
            CAMERA_INITIAL_DISTANCE: 15, // กล้องใกล้มากขึ้น (เดิม 20)
            CAMERA_ZOOMED_OUT_DISTANCE: 50, // ซูมออกเต็มที่เมื่อมี powerup

            // ✅ WebSocket/API Server Configuration (default values, จะถูก override จาก API)
            GAME_SERVER_IP: '123.253.62.251', // IP ของเซิฟเวอร์เกม
            GAME_SERVER_PORT: '8080', // Port สำหรับ API (ปรับได้ตามการตั้งค่าเซิฟเวอร์)
            GAME_SERVER_WS_PORT: '6001', // Port สำหรับ WebSocket (Laravel Reverb default)
        };

        /**
         * ✅ โหลดการตั้งค่าเกมจาก API
         * ดึงค่า config ทั้งหมดจาก database แทน hardcode
         */
        async function loadGameConfig() {
            try {
                const response = await fetch('/api/games/config');
                const result = await response.json();

                if (result.success && result.data) {
                    const data = result.data;

                    // ✅ Server Configuration
                    CONFIG.GAME_SERVER_IP = data.server?.ip || CONFIG.GAME_SERVER_IP;
                    CONFIG.GAME_SERVER_PORT = data.server?.port || CONFIG.GAME_SERVER_PORT;
                    CONFIG.GAME_SERVER_WS_PORT = data.server?.ws_port || CONFIG.GAME_SERVER_WS_PORT;

                    // ✅ World Settings
                    CONFIG.WORLD_SIZE = data.world?.size || CONFIG.WORLD_SIZE;
                    CONFIG.INITIAL_LENGTH = data.world?.initial_snake_length || CONFIG.INITIAL_LENGTH;
                    CONFIG.SEGMENT_SIZE = data.world?.segment_size || CONFIG.SEGMENT_SIZE;
                    CONFIG.COLLISION_DISTANCE = data.world?.collision_distance || CONFIG.COLLISION_DISTANCE;

                    // ✅ Movement Settings
                    CONFIG.MOVEMENT_SPEED = data.movement?.speed || CONFIG.MOVEMENT_SPEED;
                    CONFIG.BOOST_SPEED = data.movement?.boost_speed || CONFIG.BOOST_SPEED;
                    CONFIG.TURN_SPEED = data.movement?.turn_speed || CONFIG.TURN_SPEED;

                    // ✅ Camera Settings
                    CONFIG.CAMERA_INITIAL_DISTANCE = data.camera?.initial_distance || CONFIG.CAMERA_INITIAL_DISTANCE;
                    CONFIG.CAMERA_ZOOMED_OUT_DISTANCE = data.camera?.zoomed_out_distance || CONFIG.CAMERA_ZOOMED_OUT_DISTANCE;

                    // ✅ Scoring System
                    CONFIG.FOOD_VALUE = data.scoring?.food_value || CONFIG.FOOD_VALUE;
                    CONFIG.POINTS_PER_GROWTH = data.scoring?.points_per_growth || 10;

                    // ✅ Food Settings
                    CONFIG.FOOD_COUNT = data.food?.count || CONFIG.FOOD_COUNT;

                    // ✅ Bot Settings
                    CONFIG.BOT_COUNT = data.bots?.count || CONFIG.BOT_COUNT;

                    // ✅ Powerup Settings - อัพเดท POWERUP_TYPES
                    if (data.powerups?.magnet) {
                        POWERUP_TYPES.MAGNET.duration = data.powerups.magnet.duration;
                        POWERUP_TYPES.MAGNET.spawnChance = data.powerups.magnet.spawn_chance;
                    }
                    if (data.powerups?.speed) {
                        POWERUP_TYPES.SPEED.duration = data.powerups.speed.duration;
                        POWERUP_TYPES.SPEED.spawnChance = data.powerups.speed.spawn_chance;
                    }
                    if (data.powerups?.multiplier) {
                        POWERUP_TYPES.MULTIPLIER.duration = data.powerups.multiplier.duration;
                        POWERUP_TYPES.MULTIPLIER.spawnChance = data.powerups.multiplier.spawn_chance;
                    }
                    if (data.powerups?.zoom) {
                        POWERUP_TYPES.ZOOM.duration = data.powerups.zoom.duration;
                        POWERUP_TYPES.ZOOM.spawnChance = data.powerups.zoom.spawn_chance;
                    }

                    // ✅ Music & Sound Settings
                    if (data.music) {
                        musicPlayer.enabled = data.music.enabled !== false;
                        musicPlayer.defaultVolume = data.music.default_volume || 0.5;
                        musicPlayer.volume = musicPlayer.defaultVolume;

                        // ✅ FIX: ป้องกัน tracks เป็น string แทน array (double-encoded JSON)
                        let titleTracks = data.music.title_tracks || [];
                        if (typeof titleTracks === 'string') {
                            try { titleTracks = JSON.parse(titleTracks); } catch(e) { titleTracks = []; }
                        }
                        let gameplayTracks = data.music.gameplay_tracks || [];
                        if (typeof gameplayTracks === 'string') {
                            try { gameplayTracks = JSON.parse(gameplayTracks); } catch(e) { gameplayTracks = []; }
                        }

                        musicPlayer.titleTracks = Array.isArray(titleTracks) ? titleTracks : [];
                        musicPlayer.gameplayTracks = Array.isArray(gameplayTracks) ? gameplayTracks : [];
                        musicPlayer.sfxEnabled = data.music.sfx_enabled !== false;
                        musicPlayer.sfxVolume = data.music.sfx_default_volume || 0.7;

                        console.log('[Music] Tracks loaded:', {
                            title: musicPlayer.titleTracks.length,
                            gameplay: musicPlayer.gameplayTracks.length,
                            tracks: [...musicPlayer.titleTracks, ...musicPlayer.gameplayTracks]
                        });
                    }

                    console.log('[Config] ✅ โหลด config จาก API สำเร็จทั้งหมด:', {
                        server: { ip: CONFIG.GAME_SERVER_IP, port: CONFIG.GAME_SERVER_PORT },
                        world: { size: CONFIG.WORLD_SIZE, bot_count: CONFIG.BOT_COUNT },
                        food: { count: CONFIG.FOOD_COUNT, value: CONFIG.FOOD_VALUE },
                        camera: { initial: CONFIG.CAMERA_INITIAL_DISTANCE, zoomed: CONFIG.CAMERA_ZOOMED_OUT_DISTANCE },
                        powerups: POWERUP_TYPES,
                        music: { tracks: musicPlayer.titleTracks.length + musicPlayer.gameplayTracks.length },
                    });
                } else {
                    console.warn('[Config] ใช้ config default (API ไม่ตอบกลับ)');
                }
            } catch (error) {
                console.warn('[Config] โหลด config ล้มเหลว, ใช้ค่า default:', error);
            }
        }

        // ============================================================
        // 🎵 Music & Sound System
        // ============================================================
        let audioContext;
        let soundEnabled = true;

        // ✅ Music Player State
        const musicPlayer = {
            enabled: true,
            volume: 0.5,
            defaultVolume: 0.5,
            sfxEnabled: true,
            sfxVolume: 0.7,
            titleTracks: [],
            gameplayTracks: [],
            currentAudio: null,
            currentTrackIndex: 0,
            currentMode: 'title', // 'title' | 'gameplay'
            playMode: 'sequential', // 'sequential' | 'shuffle' | 'loop'
            isPlaying: false,
            muted: false,
        };

        function initAudio() {
            try {
                if (!audioContext) {
                    audioContext = new (window.AudioContext || window.webkitAudioContext)();
                }
                if (audioContext.state === 'suspended') {
                    audioContext.resume();
                }
            } catch (error) {
                console.warn('Audio not supported:', error);
                soundEnabled = false;
            }
        }

        // ✅ Music Session ID — ป้องกัน race condition เพลงซ้อน
        let _musicSessionId = 0;

        /**
         * ✅ เล่นเพลง BGM — รองรับ crossfade เมื่อเปลี่ยนโหมด
         * @param {string} mode 'title' | 'gameplay'
         * @param {boolean} fade ใช้ fade effect หรือไม่ (default: false)
         */
        function playMusic(mode, fade = false) {
            if (!musicPlayer.enabled) return;

            if (fade && musicPlayer.isPlaying && musicPlayer.currentAudio) {
                // ✅ Crossfade: fade out เพลงเก่า → เล่นเพลงใหม่พร้อม fade in
                fadeOutMusic(MUSIC_FADE_DURATION).then(() => {
                    _startNewMusic(mode, true);
                });
            } else if (fade) {
                // ✅ ไม่มีเพลงเล่นอยู่ แต่ขอ fade → หยุดทันที + fade in เพลงใหม่
                stopMusicImmediate();
                _startNewMusic(mode, true);
            } else {
                // หยุดทันที แล้วเล่นเพลงใหม่ (ไม่ fade)
                stopMusicImmediate();
                _startNewMusic(mode, false);
            }
        }

        /**
         * เริ่มเล่นเพลงใหม่ (internal — ใช้หลัง stop/fade)
         */
        function _startNewMusic(mode, withFadeIn) {
            // กำหนด session ID ใหม่ *หลัง* stop เพื่อป้องกัน self-invalidation
            _musicSessionId++;
            const mySession = _musicSessionId;

            musicPlayer.currentMode = mode;
            musicErrorCount = 0;

            const tracks = mode === 'title' ? musicPlayer.titleTracks : musicPlayer.gameplayTracks;

            // ถ้าไม่มี tracks → เล่น procedural music
            if (!tracks || tracks.length === 0) {
                console.log('[Music] ไม่มี tracks - เล่น procedural music');
                playProceduralMusic();
                return;
            }

            // เลือกเพลงตาม playMode
            if (musicPlayer.playMode === 'shuffle') {
                musicPlayer.currentTrackIndex = Math.floor(Math.random() * tracks.length);
            } else {
                musicPlayer.currentTrackIndex = 0;
            }

            // ✅ เก็บ flag ไว้ให้ playTrackAtIndex รู้ว่าต้อง fade in
            musicPlayer._pendingFadeIn = withFadeIn;
            playTrackAtIndex(musicPlayer.currentTrackIndex);
        }

        // ✅ ตัวนับ error ป้องกัน infinite loop
        let musicErrorCount = 0;
        const MAX_MUSIC_ERRORS = 5;

        function playTrackAtIndex(index) {
            const tracks = musicPlayer.currentMode === 'title' ? musicPlayer.titleTracks : musicPlayer.gameplayTracks;
            if (!tracks || index >= tracks.length || index < 0) {
                // ✅ ถ้าไม่มี track เลย → เล่นเพลง procedural แทน
                playProceduralMusic();
                return;
            }

            // ✅ จำ session ID ณ ตอนสร้าง — ถ้าเปลี่ยนไปแล้วจะไม่ทำอะไร
            const mySession = _musicSessionId;

            try {
                // ✅ FIX: หยุด audio ตัวเก่าก่อนสร้างตัวใหม่
                // ❌ ห้าม set src = '' ที่นี่! เพราะจะ trigger error event บน audio เก่า
                //    ซึ่งทำให้ error handler เรียก playNextTrack() → วนลูปสลับเพลงไม่หยุด
                // ✅ แค่ pause() + mark abandoned + null ref — GC จะจัดการเอง
                if (musicPlayer.currentAudio) {
                    const oldAudio = musicPlayer.currentAudio;
                    oldAudio._abandoned = true; // ทำเครื่องหมายว่าถูกทิ้งแล้ว
                    oldAudio.pause();
                    musicPlayer.currentAudio = null;
                }

                const track = tracks[index];
                const audio = new Audio(track.url);
                audio._abandoned = false; // เริ่มต้น: ยังใช้งานอยู่
                const shouldFadeIn = musicPlayer._pendingFadeIn;
                musicPlayer._pendingFadeIn = false; // ใช้แล้วรีเซ็ต
                // ✅ ถ้า fade in → เริ่มจากเสียงเงียบ, ถ้าไม่ → ใช้ volume ปกติ
                audio.volume = shouldFadeIn ? 0 : (musicPlayer.muted ? 0 : musicPlayer.volume);
                audio.loop = (musicPlayer.playMode === 'loop');

                // เมื่อเพลงจบ → เล่นเพลงถัดไป
                audio.addEventListener('ended', () => {
                    // ✅ ตรวจสอบ 3 ชั้น: session + abandoned flag + ยังเป็น current audio
                    if (_musicSessionId !== mySession) return;
                    if (audio._abandoned) return;
                    if (musicPlayer.currentAudio !== audio) return;
                    musicErrorCount = 0; // reset error count เมื่อเพลงจบปกติ
                    if (musicPlayer.playMode === 'loop') return;
                    playNextTrack();
                });

                audio.addEventListener('error', () => {
                    // ✅ ตรวจสอบ 3 ชั้น: session + abandoned flag + ยังเป็น current audio
                    if (_musicSessionId !== mySession) return;
                    if (audio._abandoned) return;
                    if (musicPlayer.currentAudio !== audio) return;
                    console.warn('[Music] โหลดเพลงไม่สำเร็จ:', track.url);
                    musicErrorCount++;

                    // ✅ ป้องกัน infinite error loop
                    if (musicErrorCount >= MAX_MUSIC_ERRORS) {
                        console.warn('[Music] ไฟล์เพลงโหลดไม่ได้ทั้งหมด - เปลี่ยนเป็น procedural music');
                        musicErrorCount = 0;
                        playProceduralMusic();
                        return;
                    }

                    setTimeout(() => {
                        if (_musicSessionId !== mySession) return;
                        if (audio._abandoned) return;
                        if (musicPlayer.currentAudio !== audio) return;
                        playNextTrack();
                    }, 500);
                });

                // ✅ กำหนด currentAudio ก่อนเรียก play (ป้องกัน orphan)
                musicPlayer.currentAudio = audio;
                musicPlayer.currentTrackIndex = index;

                audio.play().then(() => {
                    // ✅ ตรวจว่ายังเป็น session เดียวกัน — ถ้าไม่ใช่ ให้หยุดทันที
                    if (_musicSessionId !== mySession || audio._abandoned) {
                        audio._abandoned = true;
                        audio.pause();
                        return;
                    }
                    musicPlayer.isPlaying = true;
                    musicErrorCount = 0; // reset เมื่อเล่นได้สำเร็จ

                    // ✅ Fade in ถ้าต้องการ
                    if (shouldFadeIn) {
                        fadeInMusic(MUSIC_FADE_DURATION);
                    }

                    updateMusicUI();
                    console.log(`[Music] กำลังเล่น: ${track.name}${shouldFadeIn ? ' (fade in)' : ''}`);
                }).catch(e => {
                    if (_musicSessionId !== mySession || audio._abandoned) return;
                    console.warn('[Music] ไม่สามารถเล่นเพลงได้:', e.message);
                    musicErrorCount++;
                    if (musicErrorCount >= MAX_MUSIC_ERRORS) {
                        playProceduralMusic();
                    }
                });
            } catch (error) {
                console.warn('[Music] Error:', error);
                playProceduralMusic();
            }
        }

        function playNextTrack() {
            const tracks = musicPlayer.currentMode === 'title' ? musicPlayer.titleTracks : musicPlayer.gameplayTracks;
            if (!tracks || tracks.length === 0) {
                playProceduralMusic();
                return;
            }

            let nextIndex;
            if (musicPlayer.playMode === 'shuffle') {
                nextIndex = Math.floor(Math.random() * tracks.length);
            } else {
                nextIndex = (musicPlayer.currentTrackIndex + 1) % tracks.length;
            }
            playTrackAtIndex(nextIndex);
        }

        /**
         * ✅ Procedural Music Generator
         * สร้างเพลง BGM แบบ synthesize เมื่อไฟล์ MP3 ไม่มี
         * ใช้ Web Audio API สร้าง ambient/chiptune music
         */
        let proceduralMusicNodes = null;

        function playProceduralMusic() {
            if (!audioContext || !musicPlayer.enabled) return;
            if (audioContext.state === 'suspended') audioContext.resume();

            // หยุด procedural music เก่า (ถ้ามี)
            stopProceduralMusic();

            try {
                const vol = musicPlayer.muted ? 0 : musicPlayer.volume * 0.3; // เบาลงเพื่อไม่รบกวน
                const isTitle = musicPlayer.currentMode === 'title';

                // สร้าง master gain
                const masterGain = audioContext.createGain();
                masterGain.gain.setValueAtTime(vol, audioContext.currentTime);
                masterGain.connect(audioContext.destination);

                // สร้าง nodes array เก็บไว้สำหรับ cleanup
                const nodes = { oscillators: [], gains: [], masterGain, interval: null };

                if (isTitle) {
                    // Title: Ambient drone + slow arpeggio (สงบ, mysterious)
                    // Drone bass
                    const drone = audioContext.createOscillator();
                    drone.type = 'sine';
                    drone.frequency.setValueAtTime(55, audioContext.currentTime); // A1
                    const droneGain = audioContext.createGain();
                    droneGain.gain.setValueAtTime(0.4, audioContext.currentTime);
                    drone.connect(droneGain);
                    droneGain.connect(masterGain);
                    drone.start();
                    nodes.oscillators.push(drone);
                    nodes.gains.push(droneGain);

                    // Pad chord
                    const padNotes = [130.81, 164.81, 196.00]; // C3, E3, G3
                    padNotes.forEach(freq => {
                        const osc = audioContext.createOscillator();
                        osc.type = 'triangle';
                        osc.frequency.setValueAtTime(freq, audioContext.currentTime);
                        const g = audioContext.createGain();
                        g.gain.setValueAtTime(0.15, audioContext.currentTime);
                        osc.connect(g);
                        g.connect(masterGain);
                        osc.start();
                        nodes.oscillators.push(osc);
                        nodes.gains.push(g);
                    });

                    // Slow arpeggio (ทุก 2 วินาที)
                    const arpeggioNotes = [261.63, 329.63, 392.00, 523.25, 392.00, 329.63]; // C4-E4-G4-C5-G4-E4
                    let noteIdx = 0;
                    nodes.interval = setInterval(() => {
                        if (!audioContext || !musicPlayer.isPlaying) return;
                        const osc = audioContext.createOscillator();
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(arpeggioNotes[noteIdx % arpeggioNotes.length], audioContext.currentTime);
                        const g = audioContext.createGain();
                        g.gain.setValueAtTime(0.2 * (musicPlayer.muted ? 0 : musicPlayer.volume), audioContext.currentTime);
                        g.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + 1.8);
                        osc.connect(g);
                        g.connect(masterGain);
                        osc.start();
                        osc.stop(audioContext.currentTime + 2);
                        noteIdx++;
                    }, 2000);
                } else {
                    // Gameplay: Upbeat chiptune bass + rhythm (สนุก, ตื่นเต้น)
                    // Bass pattern
                    const bassNotes = [65.41, 73.42, 82.41, 73.42]; // C2, D2, E2, D2
                    let bassIdx = 0;
                    nodes.interval = setInterval(() => {
                        if (!audioContext || !musicPlayer.isPlaying) return;
                        const currentVol = musicPlayer.muted ? 0 : musicPlayer.volume * 0.3;

                        // Bass note
                        const bass = audioContext.createOscillator();
                        bass.type = 'square';
                        bass.frequency.setValueAtTime(bassNotes[bassIdx % bassNotes.length], audioContext.currentTime);
                        const bg = audioContext.createGain();
                        bg.gain.setValueAtTime(0.25 * currentVol, audioContext.currentTime);
                        bg.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + 0.2);
                        bass.connect(bg);
                        bg.connect(audioContext.destination);
                        bass.start();
                        bass.stop(audioContext.currentTime + 0.25);

                        // Melody note (octave up)
                        if (bassIdx % 2 === 0) {
                            const mel = audioContext.createOscillator();
                            mel.type = 'square';
                            const melodyNotes = [523.25, 587.33, 659.25, 783.99, 659.25, 587.33];
                            mel.frequency.setValueAtTime(melodyNotes[bassIdx % melodyNotes.length], audioContext.currentTime);
                            const mg = audioContext.createGain();
                            mg.gain.setValueAtTime(0.12 * currentVol, audioContext.currentTime);
                            mg.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + 0.15);
                            mel.connect(mg);
                            mg.connect(audioContext.destination);
                            mel.start();
                            mel.stop(audioContext.currentTime + 0.2);
                        }

                        bassIdx++;
                    }, 250); // 240 BPM feel
                }

                proceduralMusicNodes = nodes;
                musicPlayer.isPlaying = true;
                musicPlayer.currentAudio = { // fake Audio interface สำหรับ toggle/volume
                    pause: () => stopProceduralMusic(),
                    play: () => playProceduralMusic(),
                    set volume(v) { if (nodes.masterGain) nodes.masterGain.gain.setValueAtTime(v * 0.3, audioContext.currentTime); },
                    get volume() { return musicPlayer.volume; },
                    set src(v) { /* no-op */ },
                };
                updateMusicUI();
                console.log(`[Music] กำลังเล่น: Procedural ${isTitle ? 'Ambient' : 'Chiptune'}`);
            } catch (error) {
                console.warn('[Music] Procedural music error:', error);
            }
        }

        function stopProceduralMusic() {
            if (proceduralMusicNodes) {
                try {
                    proceduralMusicNodes.oscillators.forEach(osc => {
                        try { osc.stop(); } catch(e) {}
                    });
                    if (proceduralMusicNodes.interval) {
                        clearInterval(proceduralMusicNodes.interval);
                    }
                } catch(e) {}
                proceduralMusicNodes = null;
            }
        }

        // ✅ Fade duration สำหรับการเปลี่ยนเพลง (ms)
        const MUSIC_FADE_DURATION = 800;
        let _fadeInterval = null;

        /**
         * หยุดเพลงทันที (ไม่ fade)
         */
        function stopMusicImmediate() {
            // เพิ่ม session ID — ทำให้ audio ที่กำลัง pending หยุดเองเมื่อ resolve
            _musicSessionId++;

            // หยุด fade ที่ค้างอยู่
            if (_fadeInterval) { clearInterval(_fadeInterval); _fadeInterval = null; }

            // หยุด HTML5 Audio
            if (musicPlayer.currentAudio) {
                try {
                    musicPlayer.currentAudio._abandoned = true; // ✅ mark ว่าถูกทิ้ง
                    musicPlayer.currentAudio.pause();
                    // ✅ ปลอดภัยที่จะ set src='' ที่นี่ เพราะ _musicSessionId เพิ่มแล้ว
                    //    + _abandoned = true → event handler จะ return ทันที
                    musicPlayer.currentAudio.src = '';
                } catch(e) { /* ignore */ }
                musicPlayer.currentAudio = null;
            }
            // หยุด procedural music ด้วย
            stopProceduralMusic();
            musicPlayer.isPlaying = false;
            musicErrorCount = 0;
            updateMusicUI();
        }

        /**
         * หยุดเพลงแบบ Fade out (ค่อยๆ เบาลงแล้วหยุด)
         * @param {number} duration ระยะเวลา fade (ms)
         * @returns {Promise} resolve เมื่อ fade เสร็จ
         */
        function fadeOutMusic(duration = MUSIC_FADE_DURATION) {
            return new Promise((resolve) => {
                const audio = musicPlayer.currentAudio;

                // ถ้าไม่มีเพลงเล่นอยู่ → หยุดทันที
                if (!audio || !musicPlayer.isPlaying) {
                    stopMusicImmediate();
                    resolve();
                    return;
                }

                // หยุด fade เก่าที่ค้าง
                if (_fadeInterval) { clearInterval(_fadeInterval); _fadeInterval = null; }

                const startVol = audio.volume;
                const steps = 20; // จำนวนขั้น fade
                const stepTime = duration / steps;
                const volStep = startVol / steps;
                let currentStep = 0;

                _fadeInterval = setInterval(() => {
                    currentStep++;
                    const newVol = Math.max(0, startVol - (volStep * currentStep));
                    try { audio.volume = newVol; } catch(e) { /* ignore */ }

                    if (currentStep >= steps) {
                        clearInterval(_fadeInterval);
                        _fadeInterval = null;
                        stopMusicImmediate();
                        resolve();
                    }
                }, stepTime);
            });
        }

        /**
         * Fade in เพลงปัจจุบัน (ค่อยๆ ดังขึ้น)
         * @param {number} duration ระยะเวลา fade (ms)
         */
        function fadeInMusic(duration = MUSIC_FADE_DURATION) {
            const audio = musicPlayer.currentAudio;
            if (!audio) return;

            const targetVol = musicPlayer.muted ? 0 : musicPlayer.volume;
            audio.volume = 0; // เริ่มจากเสียงเงียบ

            // หยุด fade เก่าที่ค้าง
            if (_fadeInterval) { clearInterval(_fadeInterval); _fadeInterval = null; }

            const steps = 20;
            const stepTime = duration / steps;
            const volStep = targetVol / steps;
            let currentStep = 0;

            _fadeInterval = setInterval(() => {
                currentStep++;
                const newVol = Math.min(targetVol, volStep * currentStep);
                try { audio.volume = newVol; } catch(e) { /* ignore */ }

                if (currentStep >= steps) {
                    clearInterval(_fadeInterval);
                    _fadeInterval = null;
                    try { audio.volume = targetVol; } catch(e) { /* ignore */ }
                }
            }, stepTime);
        }

        /**
         * stopMusic — wrapper ที่เรียกหยุดทันที (backward compatible)
         */
        function stopMusic() {
            stopMusicImmediate();
        }

        function toggleMusic() {
            if (musicPlayer.isPlaying) {
                if (musicPlayer.currentAudio) {
                    musicPlayer.currentAudio.pause();
                    musicPlayer.isPlaying = false;
                }
            } else {
                if (musicPlayer.currentAudio) {
                    musicPlayer.currentAudio.play();
                    musicPlayer.isPlaying = true;
                } else {
                    playMusic(musicPlayer.currentMode);
                }
            }
            updateMusicUI();
        }

        function toggleMute() {
            musicPlayer.muted = !musicPlayer.muted;
            if (musicPlayer.currentAudio) {
                musicPlayer.currentAudio.volume = musicPlayer.muted ? 0 : musicPlayer.volume;
            }
            updateMusicUI();
        }

        function setMusicVolume(vol) {
            musicPlayer.volume = Math.max(0, Math.min(1, vol));
            if (musicPlayer.currentAudio && !musicPlayer.muted) {
                musicPlayer.currentAudio.volume = musicPlayer.volume;
            }
            updateMusicUI();
        }

        function setPlayMode(mode) {
            musicPlayer.playMode = mode;
            if (musicPlayer.currentAudio) {
                musicPlayer.currentAudio.loop = (mode === 'loop');
            }
            updateMusicUI();
        }

        function updateMusicUI() {
            const btn = document.getElementById('music-toggle-btn');
            const volSlider = document.getElementById('music-volume');
            const trackName = document.getElementById('music-track-name');
            const modeBtn = document.getElementById('music-mode-btn');

            if (btn) {
                btn.textContent = musicPlayer.muted ? '🔇' : (musicPlayer.isPlaying ? '🎵' : '🔈');
                btn.title = musicPlayer.muted ? 'เปิดเสียง' : (musicPlayer.isPlaying ? 'หยุดเพลง' : 'เล่นเพลง');
            }
            if (volSlider) {
                volSlider.value = musicPlayer.volume * 100;
            }
            if (trackName) {
                if (musicPlayer.currentAudio) {
                    const tracks = musicPlayer.currentMode === 'title' ? musicPlayer.titleTracks : musicPlayer.gameplayTracks;
                    const track = tracks[musicPlayer.currentTrackIndex];
                    trackName.textContent = track ? `♪ ${track.name}` : '♪ Procedural';
                } else if (musicPlayer.isPlaying) {
                    trackName.textContent = '♪ Procedural';
                } else {
                    trackName.textContent = '';
                }
            }
            if (modeBtn) {
                const icons = { sequential: '➡️', shuffle: '🔀', loop: '🔁' };
                modeBtn.textContent = icons[musicPlayer.playMode] || '➡️';
                modeBtn.title = musicPlayer.playMode === 'sequential' ? 'เล่นตามลำดับ' : musicPlayer.playMode === 'shuffle' ? 'สุ่มเพลง' : 'วนเพลง';
            }
        }

        // ✅ SFX Sound Generator (เอฟเฟกต์เสียง)
        function playSound(type) {
            if (!soundEnabled || !audioContext) return;
            if (!musicPlayer.sfxEnabled) return;

            try {
                const osc = audioContext.createOscillator();
                const gain = audioContext.createGain();
                osc.connect(gain);
                gain.connect(audioContext.destination);

                const now = audioContext.currentTime;
                const vol = musicPlayer.sfxVolume;

                switch(type) {
                    case 'eat':
                        osc.frequency.setValueAtTime(800, now);
                        osc.frequency.exponentialRampToValueAtTime(1200, now + 0.05);
                        gain.gain.setValueAtTime(0.3 * vol, now);
                        gain.gain.exponentialRampToValueAtTime(0.01, now + 0.1);
                        osc.start(now);
                        osc.stop(now + 0.1);
                        break;

                    case 'grow':
                        osc.frequency.setValueAtTime(400, now);
                        osc.frequency.exponentialRampToValueAtTime(800, now + 0.15);
                        gain.gain.setValueAtTime(0.2 * vol, now);
                        gain.gain.exponentialRampToValueAtTime(0.01, now + 0.15);
                        osc.start(now);
                        osc.stop(now + 0.15);
                        break;

                    case 'die':
                        osc.type = 'sawtooth';
                        osc.frequency.setValueAtTime(500, now);
                        osc.frequency.exponentialRampToValueAtTime(100, now + 0.5);
                        gain.gain.setValueAtTime(0.5 * vol, now);
                        gain.gain.exponentialRampToValueAtTime(0.01, now + 0.5);
                        osc.start(now);
                        osc.stop(now + 0.5);
                        break;

                    case 'boost':
                        osc.type = 'sawtooth';
                        osc.frequency.setValueAtTime(200, now);
                        osc.frequency.exponentialRampToValueAtTime(400, now + 0.1);
                        gain.gain.setValueAtTime(0.15 * vol, now);
                        gain.gain.exponentialRampToValueAtTime(0.01, now + 0.1);
                        osc.start(now);
                        osc.stop(now + 0.1);
                        break;

                    case 'kill':
                        osc.frequency.setValueAtTime(600, now);
                        osc.frequency.setValueAtTime(700, now + 0.05);
                        osc.frequency.setValueAtTime(800, now + 0.1);
                        gain.gain.setValueAtTime(0.25 * vol, now);
                        gain.gain.exponentialRampToValueAtTime(0.01, now + 0.2);
                        osc.start(now);
                        osc.stop(now + 0.2);
                        break;

                    case 'powerup':
                        // ✅ เสียงเก็บพาวเวอร์อัพ - arpeggio ขึ้น
                        osc.type = 'square';
                        osc.frequency.setValueAtTime(523, now);
                        osc.frequency.setValueAtTime(659, now + 0.06);
                        osc.frequency.setValueAtTime(784, now + 0.12);
                        osc.frequency.setValueAtTime(1047, now + 0.18);
                        gain.gain.setValueAtTime(0.25 * vol, now);
                        gain.gain.exponentialRampToValueAtTime(0.01, now + 0.3);
                        osc.start(now);
                        osc.stop(now + 0.3);
                        break;

                    case 'collect':
                        // ✅ เสียงเก็บไอเทมพิเศษ - coin sound
                        osc.type = 'square';
                        osc.frequency.setValueAtTime(988, now);
                        osc.frequency.setValueAtTime(1319, now + 0.05);
                        gain.gain.setValueAtTime(0.2 * vol, now);
                        gain.gain.exponentialRampToValueAtTime(0.01, now + 0.15);
                        osc.start(now);
                        osc.stop(now + 0.15);
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

        // ✅ Session Keep-Alive: ป้องกัน session หมดอายุระหว่างเล่นเกมนานๆ
        // ping ทุก 10 นาที เพื่อต่ออายุ session → Auth::check() ไม่ fail ตอน save score
        let _sessionKeepAlive = null;
        if (isAuthenticated) {
            _sessionKeepAlive = setInterval(() => {
                fetch('/api/games/snake-io/service-status', {
                    credentials: 'same-origin'
                }).catch(() => {}); // ไม่ต้อง handle error — แค่ ping ต่ออายุ session
            }, 10 * 60 * 1000); // ทุก 10 นาที
        }

        // Multiplayer Manager (DB-based - ใช้สำหรับ save score, wallet)
        let multiplayerManager = null;
        // ✅ Sync Client (Cache-based - ใช้สำหรับ real-time multiplayer sync)
        let syncClient = null;
        let touchInputManager = null;

        // Connection monitoring
        let connectionMonitorInterval = null;
        let isOnline = false;
        let reconnectAttempts = 0;
        const MAX_RECONNECT_ATTEMPTS = 5;
        const CONNECTION_CHECK_INTERVAL = 5000; // เช็คทุก 5 วินาที

        // ✅ Service Status Polling (เช็คทุก 10 วินาที ว่า service เปิดหรือปิด)
        let serviceStatusInterval = null;
        let isServiceOnline = false;
        const SERVICE_CHECK_INTERVAL = 10000; // เช็คทุก 10 วินาที

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

        // ✅ Performance: แชร์ Geometry เดียวกัน (ลด memory + draw calls)
        let sharedSegmentGeometry = null; // สร้างตอน init()
        let sharedFoodGeometry = null;
        let sharedPowerupGeometry = null;

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
                // ✅ ระบบลดขนาด: เร่งติดต่อกัน 3 วินาที = หาย 1 ข้อ
                this.boostStartTime = null; // เวลาที่เริ่ม boost
                this.lastShrinkTime = 0; // เวลาที่ลดขนาดครั้งล่าสุด
                this.length = CONFIG.INITIAL_LENGTH;
                this.score = 0;
                this.alive = true;

                // ✅ ระบบคะแนนแปรผัน: คะแนนที่ต้องการต่อปล้อง
                this.nextGrowthScore = this.calculateNextGrowthScore();

                // ✅ ระบบ invincibility 5 วินาที (เมื่อเกิดใหม่) - ทั้งผู้เล่นและบอท
                this.invincible = true; // ทั้งผู้เล่นและบอทมี invincibility ตอนเกิด
                this.invincibleUntil = Date.now() + 5000; // 5 วินาที

                // Create initial segments (✅ ใช้ shared geometry)
                for (let i = 0; i < this.length; i++) {
                    const geometry = sharedSegmentGeometry || new THREE.SphereGeometry(CONFIG.SEGMENT_SIZE, 8, 8);
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

                // ✅ FIX: แสดงชื่อทุกตัวเสมอ (ทั้ง player, bot, online players)
                this.createNameTag();
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

            /**
             * อัปเดตสี skin ของหนอนที่สร้างไว้แล้ว
             * ใช้เมื่อโหลด skin จาก server หลังจากหนอนถูกสร้างแล้ว
             */
            applySkin(skinKey) {
                // ตรวจสอบว่าเป็น custom colors หรือ preset skin
                if (typeof skinKey === 'string' && skinKey.includes('#')) {
                    this.skinKey = 'custom';
                    this.skin = SKINS.custom;
                } else {
                    this.skinKey = skinKey;
                    this.skin = SKINS[skinKey] || SKINS.classic;
                }

                // อัปเดต material ของทุก segment
                this.segments.forEach((segment, i) => {
                    let segmentColor = i === 0 ? this.skin.primary : this.skin.secondary;
                    if (this.skin.colors && this.skin.colors.length > 0 && i > 0) {
                        const colorIndex = i % this.skin.colors.length;
                        segmentColor = this.skin.colors[colorIndex];
                    }

                    segment.material.color.setHex(segmentColor);
                    if (this.isPlayer) {
                        segment.material.emissive.setHex(segmentColor);
                    } else if (i === 0) {
                        segment.material.emissive.setHex(segmentColor);
                    }
                });

                console.log('[Skin] อัปเดตสีหนอนสำเร็จ:', skinKey);
            }

            update(now = Date.now()) {
                if (!this.alive) return;

                // Update direction smoothly
                this.direction.lerp(this.targetDirection.normalize(), CONFIG.TURN_SPEED);

                // ✅ บังคับให้ direction เป็น unit vector เสมอ (ป้องกันบอทนิ่ง)
                this.direction.normalize();

                // Move head
                const head = this.segments[0];
                const currentSpeed = this.isBoosting ? CONFIG.BOOST_SPEED : this.speed;

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

                // ✅ Simple segment following (ไม่ค้าง - ใช้ lerp ธรรมดา)
                this.segmentPositions[0] = head.position.clone();

                for (let i = 1; i < this.segments.length; i++) {
                    const target = this.segmentPositions[i - 1];
                    const current = this.segments[i].position;

                    // Simple lerp - เบาและรวดเร็ว
                    current.lerp(target, 0.2);

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

                // ✅ Rainbow animation for rainbow skin (ใช้ now แทน Date.now())
                if (this.skinKey === 'rainbow') {
                    const hue = (now * 0.001) % 1;
                    this.segments[0].material.color.setHSL(hue, 1, 0.5);
                }

                // ✅ ระบบกระพริบเมื่อมี invincibility (ใช้ now แทน Date.now())
                if (this.invincible && now < this.invincibleUntil) {
                    // กระพริบทุก 200ms (5 ครั้ง/วินาที)
                    const blinkInterval = 200;
                    const shouldShow = Math.floor(now / blinkInterval) % 2 === 0;
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
                } else if (this.invincible && now >= this.invincibleUntil) {
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

                // ✅ เอฟเฟคเรืองแสงเมื่อ boost (ทั้งผู้เล่นและบอท)
                if (this.isBoosting) {
                    // เพิ่มความเรืองแสงให้ทุก segment
                    this.segments.forEach((segment, i) => {
                        // ตั้งค่า emissive (เรืองแสง) เป็นสีของ segment
                        const segmentColor = segment.material.color.getHex();
                        segment.material.emissive.setHex(segmentColor);

                        // ความเข้มของแสงเรืองแสง (หัวเรืองแสงมากกว่าลำตัว)
                        segment.material.emissiveIntensity = i === 0 ? 0.7 : 0.5;
                    });

                    // เพิ่ม outline glow ถ้าเป็นผู้เล่น
                    if (this.outline && this.isPlayer) {
                        this.outline.material.opacity = 0.8; // เรืองแสงมากขึ้น
                    }
                } else {
                    // คืนค่าความเรืองแสงปกติเมื่อไม่ boost
                    this.segments.forEach((segment, i) => {
                        const segmentColor = segment.material.color.getHex();
                        segment.material.emissive.setHex(this.isPlayer ? segmentColor : (i === 0 ? segmentColor : 0x000000));
                        segment.material.emissiveIntensity = this.isPlayer ? (i === 0 ? 0.5 : 0.25) : (i === 0 ? 0.3 : 0);
                    });

                    // คืนค่า outline opacity ปกติ
                    if (this.outline && this.isPlayer) {
                        this.outline.material.opacity = 0.5;
                    }
                }
            }

            /**
             * คำนวณคะแนนที่ต้องการสำหรับการเติบโตครั้งต่อไป
             * ระบบการเติบโต:
             * - ข้อที่ 1-10: ต้อง 10 คะแนนต่อข้อ
             * - ข้อที่ 11-20: ต้อง 15 คะแนนต่อข้อ
             * - ข้อที่ 21-30: ต้อง 20 คะแนนต่อข้อ
             * - ข้อที่ 31+: ต้อง 25 คะแนนต่อข้อ
             *
             * @return {number} คะแนนที่ต้องการสำหรับการเติบโตครั้งต่อไป
             */
            calculateNextGrowthScore() {
                const scorePerSegment = 10 + Math.floor(this.length / 10) * 5;
                return this.score + scorePerSegment;
            }

            grow(amount = 1) {
                for (let i = 0; i < amount; i++) {
                    const lastSegment = this.segments[this.segments.length - 1];
                    const geometry = sharedSegmentGeometry || new THREE.SphereGeometry(CONFIG.SEGMENT_SIZE, 8, 8);

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

                // ✅ คำนวณคะแนนที่ต้องการสำหรับปล้องถัดไป (สะสมจากคะแนนปัจจุบัน)
                // ระบบการเติบโต:
                // - ข้อที่ 1-10: ต้อง 10 คะแนนต่อข้อ
                // - ข้อที่ 11-20: ต้อง 15 คะแนนต่อข้อ
                // - ข้อที่ 21-30: ต้อง 20 คะแนนต่อข้อ
                // - ข้อที่ 31+: ต้อง 25 คะแนนต่อข้อ
                const scorePerSegment = 10 + Math.floor(this.length / 10) * 5;
                this.nextGrowthScore = this.score + scorePerSegment;

                // Apply score multiplier if active (ไม่บวกคะแนนที่นี่ เพราะจะบวกตอนเก็บไอเทม)
                if (this.isPlayer) {
                    playSound('eat');

                    // Play grow sound every 5 segments
                    if (this.length % 5 === 0) {
                        playSound('grow');
                    }
                }
            }

            /**
             * ✅ ลดขนาดงู (สำหรับ boost system)
             * ลด 1 ข้อจากท้าย (ไม่ลดถ้าเหลือแค่ข้อเดียว)
             */
            shrink(amount = 1) {
                for (let i = 0; i < amount; i++) {
                    // ต้องมีอย่างน้อย 2 ข้อ (หัว + ลำตัว 1 ข้อ)
                    if (this.segments.length <= 2) {
                        break;
                    }

                    // ลบปล้องท้ายสุด
                    const lastSegment = this.segments.pop();
                    this.segmentPositions.pop();
                    scene.remove(lastSegment);

                    // ✅ dispose เฉพาะ material (ไม่ dispose shared geometry)
                    lastSegment.material.dispose();
                }

                this.length = this.segments.length;

                // ✅ อัพเดท nextGrowthScore ตามระบบเดียวกับ grow()
                const scorePerSegment = 10 + Math.floor(this.length / 10) * 5;
                this.nextGrowthScore = this.score + scorePerSegment;
            }

            checkSelfCollision() {
                // Self collision is disabled in .io games
                // Players should not die when hitting their own body
                return false;
            }

            die() {
                // ✅ ป้องกันเรียก die() ซ้ำ (ถ้าตายแล้วไม่ต้องทำอะไร)
                if (!this.alive) return;
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
                this.segments = []; // ✅ เคลียร์ segments ป้องกันซ้ำ

                if (this.nameSprite) {
                    // ✅ คืน memory: dispose texture + material ก่อน remove
                    if (this.nameSprite.material) {
                        if (this.nameSprite.material.map) this.nameSprite.material.map.dispose();
                        this.nameSprite.material.dispose();
                    }
                    scene.remove(this.nameSprite);
                    this.nameSprite = null;
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

        /**
         * 🏆 โหลด Top 100 Leaderboard สำหรับหน้า Title Screen
         * ดึงข้อมูลจาก API /games/snake-io/leaderboard
         */
        async function loadTitleLeaderboard() {
            const listEl = document.getElementById('title-lb-list');
            if (!listEl) return;

            try {
                const response = await fetch('/games/snake-io/leaderboard', {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' },
                    signal: AbortSignal.timeout(5000)
                });

                if (!response.ok) throw new Error('HTTP ' + response.status);

                const data = await response.json();
                const entries = Array.isArray(data) ? data : [];

                if (entries.length === 0) {
                    listEl.innerHTML = '<div class="title-lb-empty">ยังไม่มีคะแนนที่บันทึก<br>เล่นแล้วบันทึกคะแนนเลย!</div>';
                    return;
                }

                // สร้าง HTML สำหรับแต่ละอันดับ
                const medals = ['🥇', '🥈', '🥉'];
                listEl.innerHTML = entries.map((entry, index) => {
                    const rankNum = index + 1;
                    const rankClass = rankNum <= 3 ? ` rank-${rankNum}` : '';
                    const rankDisplay = rankNum <= 3 ? medals[rankNum - 1] : rankNum;
                    // ✅ ใช้ display_name (ชื่อหนอน) ก่อน, fallback เป็นชื่อบัญชี
                    const displayName = entry.display_name || entry.player_name || (entry.user ? entry.user.name : 'Unknown');
                    const score = Number(entry.score).toLocaleString();

                    return `<div class="title-lb-item${rankClass}">
                        <span class="lb-rank">${rankDisplay}</span>
                        <span class="lb-name">${displayName}</span>
                        <span class="lb-score">${score}</span>
                    </div>`;
                }).join('');

                // ✅ Cache top 3 สำหรับแสดงระหว่างเล่น (server leaderboard)
                _serverTop3Cache = entries.slice(0, 3).map(e => ({
                    name: e.display_name || e.player_name || (e.user ? e.user.name : 'Unknown'),
                    score: e.score
                }));

                console.log('[Leaderboard] โหลด Top 100 สำเร็จ:', entries.length, 'รายการ');

            } catch (err) {
                console.warn('[Leaderboard] ไม่สามารถโหลด Top 100:', err.message);
                listEl.innerHTML = '<div class="title-lb-empty">ไม่สามารถโหลด Leaderboard ได้<br>ลองรีเฟรชหน้า</div>';
            }
        }

        /**
         * 🏆 อัปเดต Top 3 In-Game Display
         * แสดง Top 3 จาก Server Leaderboard (คะแนนที่บันทึกไว้) ไม่ใช่ห้องปัจจุบัน
         * ✅ โหลดจาก cache ที่ loadTitleLeaderboard() ดึงมา
         */
        let top3LastKey = '';
        let _serverTop3Cache = []; // เก็บ top 3 จาก server

        function updateInGameTop3() {
            const container = document.getElementById('ingame-top3');
            if (!container) return;

            // ใช้ข้อมูลจาก server cache (โหลดตอนเริ่มเกม)
            if (_serverTop3Cache.length === 0) return;

            const medals = ['🥇', '🥈', '🥉'];
            const classes = ['gold', 'silver', 'bronze'];
            const top3 = _serverTop3Cache.slice(0, 3);

            // ✅ เปรียบเทียบ key → อัปเดตเฉพาะเมื่อเปลี่ยน (ไม่กระพริบ)
            const newKey = top3.map(e => `${e.name}:${e.score}`).join('|');
            if (newKey === top3LastKey) return;
            top3LastKey = newKey;

            container.innerHTML = top3.map((entry, i) => {
                const shortName = entry.name.length > 8 ? entry.name.substring(0, 8) + '…' : entry.name;
                return `<div class="top3-card ${classes[i]}">
                    <span class="top3-medal">${medals[i]}</span>
                    <span class="top3-name">${shortName}</span>
                    <span class="top3-score">${Number(entry.score).toLocaleString()}</span>
                </div>`;
            }).join('');
        }

        async function init() {
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

                // ✅ Performance: ตรวจจับ mobile + จำกัด pixel ratio
                const isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
                const maxPixelRatio = isMobile ? 1.5 : 2; // จำกัด pixel ratio (ลดภาระ GPU)

                renderer = new THREE.WebGLRenderer({
                    canvas,
                    antialias: !isMobile, // ปิด antialias บนมือถือ (ประหยัด GPU)
                    powerPreference: 'high-performance',
                });
                renderer.setSize(window.innerWidth, window.innerHeight);
                renderer.shadowMap.enabled = !isMobile; // ปิดเงาบนมือถือ
                renderer.setPixelRatio(Math.min(window.devicePixelRatio, maxPixelRatio));

            // Lights
            const ambientLight = new THREE.AmbientLight(0x404080, 0.6);
            scene.add(ambientLight);

            const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
            directionalLight.position.set(50, 50, 50);
            directionalLight.castShadow = !isMobile; // ✅ ปิดเงาบนมือถือ
            scene.add(directionalLight);

            // ✅ Performance: สร้าง shared geometries (ใช้ร่วมกันทั้งเกม)
            sharedSegmentGeometry = new THREE.SphereGeometry(CONFIG.SEGMENT_SIZE, 8, 8);
            sharedFoodGeometry = new THREE.SphereGeometry(0.3, 6, 6);
            sharedPowerupGeometry = new THREE.BoxGeometry(0.8, 0.8, 0.8);

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

            // ✅ คลิกขวาเมาส์ = เร่งสปีด (boost)
            document.addEventListener('mousedown', function(e) {
                if (e.button === 2 && player && player.alive && gameStarted && !gameOver) {
                    if (!player.isBoosting) {
                        player.isBoosting = true;
                        playSound('boost');
                    }
                }
            });
            document.addEventListener('mouseup', function(e) {
                if (e.button === 2 && player) {
                    player.isBoosting = false;
                }
            });
            // ป้องกัน context menu (เมนูคลิกขวา) ระหว่างเล่น
            document.addEventListener('contextmenu', function(e) {
                if (gameStarted && !gameOver) {
                    e.preventDefault();
                }
            });

            // Touch event listeners for mobile/tablet
            canvas.addEventListener('touchstart', onTouchStart, { passive: false });
            canvas.addEventListener('touchmove', onTouchMove, { passive: false });
            canvas.addEventListener('touchend', onTouchEnd, { passive: false });

            // Prevent default touch behavior (แต่อนุญาต pinch-zoom สำหรับ accessibility)
            document.addEventListener('touchmove', function(e) {
                if (gameStarted && e.touches.length === 1) {
                    e.preventDefault();
                }
                // pinch-zoom (2 นิ้ว) จะไม่ถูกบล็อก
            }, { passive: false });

            // ✅ ทำความสะอาดเมื่อปิดแท็บ/เปลี่ยนหน้า (ป้องกัน ghost players)
            window.addEventListener('beforeunload', function() {
                if (syncClient && syncClient.isOnline() && syncClient.playerId) {
                    // ใช้ sendBeacon เพื่อส่ง request แม้แท็บจะปิด
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                    navigator.sendBeacon('/api/snake-sync/leave', new Blob(
                        [JSON.stringify({ player_id: syncClient.playerId })],
                        { type: 'application/json' }
                    ));
                }
                if (multiplayerManager && multiplayerManager.playerId) {
                    navigator.sendBeacon('/api/games/snake-io/leave', new Blob(
                        [JSON.stringify({ player_id: multiplayerManager.playerId })],
                        { type: 'application/json' }
                    ));
                }
            });

            // ✅ FIX: เมื่อแท็บถูกซ่อน → ใช้ background loop แทน rAF
            // เกมยังเดินต่อ (บอทวิ่ง, sync ส่ง state) — คนอื่นจะไม่เห็นหนอนหยุดนิ่ง
            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    _isTabHidden = true;
                    startBackgroundLoop();
                    // ✅ ไม่หยุด sync — ให้ส่ง state ต่อเพื่อให้คนอื่นเห็นว่ายังเล่นอยู่
                    console.log('[Game] แท็บถูกซ่อน → เปลี่ยนเป็น background mode (10fps, ไม่ render)');
                } else {
                    _isTabHidden = false;
                    stopBackgroundLoop();
                    console.log('[Game] กลับมาที่แท็บ → เปลี่ยนเป็น foreground mode (rAF + render)');
                }
            });

            // ✨ UI Events - รองรับ Title Screen + Character Setup Screen
            // 1. Play Button → แสดงหน้า Character Setup
            document.getElementById('play-btn').addEventListener('click', function() {
                document.getElementById('title-screen').classList.add('hidden');
                document.getElementById('character-setup-screen').classList.add('show');
            });

            // 2. Back to Title Button → กลับหน้า Title
            document.getElementById('back-to-title-btn').addEventListener('click', function() {
                document.getElementById('character-setup-screen').classList.remove('show');
                document.getElementById('title-screen').classList.remove('hidden');
            });

            // 3. Start Game Button → เริ่มเกม
            document.getElementById('start-game-btn').addEventListener('click', startGame);

            // 4. Restart Button → เล่นใหม่
            document.getElementById('restart-btn').addEventListener('click', restartGame);

            // 5. Back to Title Button → กลับหน้า Title (จาก game over)
            document.getElementById('back-title-btn').addEventListener('click', function() {
                restartGame();
            });

            // ✅ Save score buttons (สำหรับสมาชิกเท่านั้น - ต้องมี null check)
            const saveScoreBtn = document.getElementById('save-score-btn');
            const skipSaveBtn = document.getElementById('skip-save-btn');
            if (saveScoreBtn) {
                saveScoreBtn.addEventListener('click', handleSaveScore);
            }
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

                // ✅ เลือก custom skin และบันทึกเป็น hex codes (ไม่ใช่ string 'custom')
                const customSkinString = `${color1},${color2},${color3}`; // "#00ff00,#00aa00,#00dd00"
                selectedSkin = customSkinString;
                document.querySelectorAll('.skin-option').forEach(o => o.classList.remove('selected'));

                    // ✅ บันทึกสี custom พร้อม hex codes (ถ้าเป็นสมาชิก)
                    saveSkinPreference(customSkinString);

                    alert('✅ ใช้สีของคุณเองแล้ว!\n\nสี 1: ' + color1 + '\nสี 2: ' + color2 + '\nสี 3: ' + color3);
                });
            }

            // Start animation loop
            animate();

            // ✅ โหลดสี skin ที่สมาชิกบันทึกไว้ (await ก่อน startGame จะถูกเรียก)
            if (isAuthenticated) {
                try {
                    await loadSavedSkin();
                    console.log('[Skin] โหลดสีสำเร็จ, selectedSkin =', selectedSkin);
                } catch (e) {
                    console.warn('[Skin] โหลดสีล้มเหลว, ใช้ classic:', e.message);
                }
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
                        // ✅ ตรวจสอบว่าเป็น custom colors (มี hex codes) หรือ preset skin
                        if (data.skin.includes('#')) {
                            // Custom colors - parse และ populate color picker
                            const colors = data.skin.split(',');
                            document.getElementById('color1').value = colors[0];
                            document.getElementById('color2').value = colors[1];
                            document.getElementById('color3').value = colors[2];

                            // อัปเดต customColors และ SKINS.custom
                            customColors = [
                                parseInt(colors[0]?.replace('#', '0x')),
                                parseInt(colors[1]?.replace('#', '0x')),
                                parseInt(colors[2]?.replace('#', '0x'))
                            ];
                            SKINS.custom.colors = customColors;
                            SKINS.custom.primary = customColors[0];
                            SKINS.custom.secondary = customColors[1];

                            selectedSkin = data.skin; // เก็บ hex codes

                            console.log('[Skin] โหลดสี custom:', colors);
                        } else {
                            // Preset skin
                            selectedSkin = data.skin;

                            // เลือก skin option ที่ตรงกับสีที่บันทึกไว้
                            document.querySelectorAll('.skin-option').forEach(option => {
                                option.classList.remove('selected');
                                if (option.dataset.skin === data.skin) {
                                    option.classList.add('selected');
                                }
                            });

                            console.log('[Skin] โหลดสี preset:', data.skin);
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
            const geometry = sharedFoodGeometry || new THREE.SphereGeometry(0.3, 6, 6);
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

            // Create spinning cube for powerup (✅ ใช้ shared geometry)
            const geometry = sharedPowerupGeometry || new THREE.BoxGeometry(0.8, 0.8, 0.8);
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

            // ✅ เพิ่มป้ายชื่อไอเท็มลอยเหนือ powerup (จำได้ง่าย)
            const labelCanvas = document.createElement('canvas');
            labelCanvas.width = 128;
            labelCanvas.height = 64;
            const ctx = labelCanvas.getContext('2d');

            // วาดพื้นหลังโปร่งใส + icon + ชื่อ
            ctx.clearRect(0, 0, 128, 64);

            // วาด icon ใหญ่ตรงกลาง
            ctx.font = '32px serif';
            ctx.textAlign = 'center';
            ctx.fillText(type.icon, 64, 34);

            // วาดชื่อด้านล่าง
            const labelNames = { magnet: 'MAGNET', speed: 'SPEED', multiplier: 'x2', zoom: 'ZOOM' };
            ctx.font = 'bold 14px Orbitron, sans-serif';
            ctx.fillStyle = '#ffffff';
            ctx.strokeStyle = '#000000';
            ctx.lineWidth = 3;
            ctx.strokeText(labelNames[type.name] || type.name.toUpperCase(), 64, 56);
            ctx.fillText(labelNames[type.name] || type.name.toUpperCase(), 64, 56);

            const labelTexture = new THREE.CanvasTexture(labelCanvas);
            const labelMaterial = new THREE.SpriteMaterial({
                map: labelTexture,
                transparent: true,
                depthTest: false
            });
            const labelSprite = new THREE.Sprite(labelMaterial);
            labelSprite.position.set(x, 2.5, z);
            labelSprite.scale.set(2.5, 1.25, 1);
            scene.add(labelSprite);
            powerup.userData.labelSprite = labelSprite;

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
                    return; // Only one death per frame
                }
            }

            // ✅ Check player vs online players (ชนกับผู้เล่นออนไลน์)
            for (const [playerId, onlineSnake] of otherPlayerSnakes) {
                if (!onlineSnake.alive) continue;

                const victim = checkSnakeCollision(player, onlineSnake);
                if (victim) {
                    if (victim === player) {
                        // ผู้เล่นตาย จากการชนผู้เล่นออนไลน์
                        victim.die();
                    }
                    // ถ้า victim เป็นผู้เล่นออนไลน์ ไม่ kill ฝั่งเรา (server จัดการ)
                    return;
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
            // ✅ เช็คจาก sync client ก่อน (primary)
            if (syncClient && syncClient.isOnline()) {
                return true;
            }

            if (!multiplayerManager && !syncClient) {
                return false;
            }

            try {
                // fallback: ping server
                const response = await fetch('/api/snake-sync/stats', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    signal: AbortSignal.timeout(2000)
                });

                const data = await response.json();
                return data.success === true;
            } catch (error) {
                console.warn('[Connection] Ping failed:', error.message);
                return false;
            }
        }

        /**
         * พยายามเชื่อมต่อใหม่
         */
        async function attemptReconnect() {
            if (!gameStarted || gameOver) {
                return;
            }

            if (reconnectAttempts >= MAX_RECONNECT_ATTEMPTS) {
                console.warn('[Connection] หมดจำนวนครั้งที่จะ reconnect แล้ว');
                updateConnectionStatus('offline', '🤖 OFFLINE MODE');
                return;
            }

            reconnectAttempts++;
            console.log(`[Connection] พยายาม reconnect ครั้งที่ ${reconnectAttempts}/${MAX_RECONNECT_ATTEMPTS}...`);

            updateConnectionStatus('offline', '⚡ RECONNECTING...');

            try {
                // ✅ Reconnect sync client - ดึง room_id เก่าก่อนทำความสะอาด
                let oldRoomId = null;
                if (syncClient) {
                    oldRoomId = syncClient.getRoomId();
                    try { await syncClient.leave(); } catch(e) {}
                    syncClient = null;
                }
                // ดึงจาก multiplayerManager ด้วย
                if (!oldRoomId && multiplayerManager && multiplayerManager.roomCode) {
                    oldRoomId = multiplayerManager.roomCode;
                }

                syncClient = new SnakeSyncClient();
                const syncJoined = await syncClient.join(playerName, selectedSkin, oldRoomId);

                if (syncJoined) {
                    console.log('[Connection] Sync reconnect สำเร็จ! ห้อง:', syncClient.getRoomId());
                    reconnectAttempts = 0;
                    updateConnectionStatus('online', '🌐 ONLINE MODE');
                    return true;
                }

                syncClient = null;
                console.error('[Connection] Reconnect ล้มเหลว');
                updateConnectionStatus('offline', '🤖 OFFLINE MODE');
                return false;
            } catch (error) {
                console.error('[Connection] Reconnect ล้มเหลว:', error.message);
                syncClient = null;
                updateConnectionStatus('offline', '🤖 OFFLINE MODE');
                return false;
            }
        }

        /**
         * เริ่มตรวจสอบการเชื่อมต่อเป็นระยะ
         */
        function startConnectionMonitoring() {
            // หยุด monitor เดิม (ถ้ามี)
            stopConnectionMonitoring();

            console.log('[Connection] เริ่มตรวจสอบการเชื่อมต่อทุก', CONNECTION_CHECK_INTERVAL / 1000, 'วินาที');

            connectionMonitorInterval = setInterval(async () => {
                if (!gameStarted || gameOver) {
                    return;
                }

                // เช็คว่ายังเชื่อมต่ออยู่หรือไม่
                const connected = await checkConnection();

                if (connected && !isOnline) {
                    // เพิ่งกลับมา online
                    updateConnectionStatus('online', '🌐 ONLINE MODE');
                } else if (!connected && isOnline) {
                    // เพิ่งหลุด offline - พยายาม reconnect
                    console.log('[Connection] หลุดการเชื่อมต่อ กำลัง reconnect...');
                    await attemptReconnect();
                }
            }, CONNECTION_CHECK_INTERVAL);
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

        /**
         * ✅ เช็คสถานะ service ว่าเปิดหรือปิด
         *
         * เรียก API เพื่อตรวจสอบว่า multiplayer service กำลังทำงานหรือไม่
         */
        async function checkServiceStatus() {
            try {
                const response = await fetch('/api/games/snake-io/service-status', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    signal: AbortSignal.timeout(3000) // timeout 3 วินาที
                });

                const data = await response.json();

                if (data.success) {
                    const wasOnline = isServiceOnline;
                    isServiceOnline = data.is_online;

                    console.log(`[Service] สถานะ: ${data.mode.toUpperCase()} (was: ${wasOnline ? 'online' : 'offline'}, now: ${isServiceOnline ? 'online' : 'offline'})`);

                    // ตรวจจับการเปลี่ยนสถานะ
                    if (!wasOnline && isServiceOnline) {
                        // Service เพิ่งเปิด - เข้าสู่ online mode
                        console.log('🟢 [Service] Service is now ONLINE - Switching to multiplayer mode');
                        await switchToOnlineMode();
                    } else if (wasOnline && !isServiceOnline) {
                        // Service เพิ่งปิด - เข้าสู่ offline mode
                        console.log('🔴 [Service] Service is now OFFLINE - Switching to offline mode');
                        await switchToOfflineMode();
                    }
                }
            } catch (error) {
                console.error('[Service] ตรวจสอบสถานะ service ไม่สำเร็จ:', error.message);
                // ถ้า error = ถือว่า offline
                if (isServiceOnline) {
                    isServiceOnline = false;
                    console.log('🔴 [Service] ไม่สามารถติดต่อ service ได้ - Switching to offline mode');
                    await switchToOfflineMode();
                }
            }
        }

        /**
         * ✅ สลับไปยัง Online Mode (Multiplayer)
         *
         * เมื่อ service เปิด จะพยายามเชื่อมต่อ multiplayer และเข้าห้อง
         */
        async function switchToOnlineMode() {
            // ตรวจสอบว่าเกมกำลังเล่นอยู่หรือไม่
            if (!gameStarted || gameOver) {
                console.log('[Service] เกมยังไม่เริ่มหรือจบแล้ว ข้าม online mode');
                return;
            }

            // ถ้ามี sync client อยู่แล้ว = online อยู่แล้ว
            if (syncClient && syncClient.isOnline()) {
                console.log('[Service] อยู่ใน online mode อยู่แล้ว');
                return;
            }

            try {
                console.log('[Service] กำลังเชื่อมต่อ multiplayer...');
                updateConnectionStatus('offline', '⚡ CONNECTING...');

                // ✅ เชื่อมต่อ DB Manager ก่อน (เพื่อได้ room_code)
                let roomCodeConnect = null;
                try {
                    multiplayerManager = new SnakeMultiplayerManager('/api/games/snake-io', {
                        ip: CONFIG.GAME_SERVER_IP,
                        port: CONFIG.GAME_SERVER_PORT
                    });

                    const timeoutPromise = new Promise((_, reject) =>
                        setTimeout(() => reject(new Error('Connection timeout')), 5000)
                    );

                    const joinRes = await Promise.race([
                        multiplayerManager.joinGame(playerName, selectedSkin),
                        timeoutPromise
                    ]);
                    roomCodeConnect = joinRes.room_code;
                } catch (dbError) {
                    console.warn('[Service] DB join ล้มเหลว (ไม่กระทบเกม):', dbError.message);
                    multiplayerManager = null;
                }

                // ✅ เชื่อมต่อ Sync Client พร้อม room_id
                syncClient = new SnakeSyncClient();
                const syncJoined = await syncClient.join(playerName, selectedSkin, roomCodeConnect);

                if (syncJoined) {
                    console.log('🌐 [Service] Sync เชื่อมต่อสำเร็จ! ห้อง:', syncClient.getRoomId());
                    updateConnectionStatus('online', '🌐 ONLINE MODE');
                    startConnectionMonitoring();
                    reconnectAttempts = 0;
                } else {
                    syncClient = null;
                    updateConnectionStatus('offline', '🤖 OFFLINE MODE');
                }

            } catch (error) {
                console.error('[Service] เชื่อมต่อ multiplayer ไม่สำเร็จ:', error.message);
                multiplayerManager = null;
                syncClient = null;
                updateConnectionStatus('offline', '🤖 OFFLINE MODE');
            }
        }

        /**
         * ✅ สลับไปยัง Offline Mode (กับ Bots)
         *
         * เมื่อ service ปิด จะตัดการเชื่อมต่อ multiplayer และเล่นกับ bots อย่างเดียว
         */
        async function switchToOfflineMode() {
            if (!multiplayerManager && !syncClient) {
                console.log('[Service] อยู่ใน offline mode อยู่แล้ว');
                return;
            }

            try {
                console.log('[Service] กำลังตัดการเชื่อมต่อ multiplayer...');

                // ✅ ตัดการเชื่อมต่อ sync client
                if (syncClient) {
                    await syncClient.leave();
                    syncClient = null;
                }

                // ตัดการเชื่อมต่อ DB multiplayer
                if (multiplayerManager) {
                    multiplayerManager.disconnectWebSocket();
                    multiplayerManager = null;
                }

                // หยุดตรวจสอบการเชื่อมต่อ
                stopConnectionMonitoring();

                console.log('🤖 [Service] สลับเป็น offline mode สำเร็จ - เล่นกับ bots');
                updateConnectionStatus('offline', '🤖 OFFLINE MODE');

            } catch (error) {
                console.error('[Service] สลับเป็น offline mode ล้มเหลว:', error.message);
                multiplayerManager = null;
                syncClient = null;
                updateConnectionStatus('offline', '🤖 OFFLINE MODE');
            }
        }

        /**
         * ✅ เริ่มตรวจสอบสถานะ service เป็นระยะ (ทุก 10 วินาที)
         *
         * ใช้สำหรับตรวจจับว่า service เปิด/ปิด เพื่อสลับ mode อัตโนมัติ
         */
        function startServicePolling() {
            // เช็คทันที
            checkServiceStatus();

            // หยุด polling เดิม (ถ้ามี)
            stopServicePolling();

            console.log('[Service] เริ่มตรวจสอบสถานะ service ทุก', SERVICE_CHECK_INTERVAL / 1000, 'วินาที');

            serviceStatusInterval = setInterval(() => {
                checkServiceStatus();
            }, SERVICE_CHECK_INTERVAL);
        }

        /**
         * ✅ หยุดตรวจสอบสถานะ service
         */
        function stopServicePolling() {
            if (serviceStatusInterval) {
                clearInterval(serviceStatusInterval);
                serviceStatusInterval = null;
                console.log('[Service] หยุดตรวจสอบสถานะ service');
            }
        }

        async function startGame() {
            console.log('Starting game...');

            // ✅ Performance: หยุด title animation (ประหยัด CPU)
            if (window.stopTitleAnimation) window.stopTitleAnimation();

            // 🎵 ป้องกันเพลงซ้อน: หยุดเพลงเก่าทั้งหมดก่อน + ลบ listener ที่ค้าง
            window._musicUserStarted = true; // ป้องกัน onFirstUserInteract ทำงานซ้อน
            if (window._onFirstUserInteract) {
                document.removeEventListener('click', window._onFirstUserInteract);
                document.removeEventListener('touchstart', window._onFirstUserInteract);
            }
            // ซ่อน music hint
            const musicHint = document.getElementById('music-hint');
            if (musicHint) musicHint.style.display = 'none';

            // ✅ โหลด config จาก API ก่อนเริ่มเกม
            await loadGameConfig();

            // Initialize audio on user interaction
            initAudio();

            // ✅ เปลี่ยนเพลงเป็น gameplay mode พร้อม crossfade (จะ fade out เพลง title เอง)
            playMusic('gameplay', true);

            playerName = document.getElementById('player-name').value.trim() ||
                         (isAuthenticated ? '{{ Auth::user()->name ?? "Player" }}' : 'Player');

            try {
                // ✅ เช็คสถานะ service ก่อนว่าเปิดหรือปิด
                console.log('[Service] กำลังตรวจสอบสถานะ multiplayer service...');
                updateConnectionStatus('offline', 'CHECKING SERVICE...');

                try {
                    // ตรวจสอบสถานะ service
                    const serviceResponse = await fetch('/api/games/snake-io/service-status', {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        signal: AbortSignal.timeout(3000)
                    });

                    const serviceData = await serviceResponse.json();
                    isServiceOnline = serviceData.success && serviceData.is_online;

                    console.log('[Service] สถานะ service:', isServiceOnline ? 'ONLINE' : 'OFFLINE');

                    // ถ้า service เปิด = เชื่อมต่อ multiplayer
                    if (isServiceOnline) {
                        try {
                            console.log('[Multiplayer] Service เปิดอยู่ - กำลังเชื่อมต่อ...');
                            updateConnectionStatus('offline', '⚡ CONNECTING...');

                            // ✅ เชื่อมต่อ DB Multiplayer Manager ก่อน (เพื่อได้ room_code)
                            let roomCode = null;
                            try {
                                multiplayerManager = new SnakeMultiplayerManager('/api/games/snake-io', {
                                    ip: CONFIG.GAME_SERVER_IP,
                                    port: CONFIG.GAME_SERVER_PORT
                                });

                                const timeoutPromise = new Promise((_, reject) =>
                                    setTimeout(() => reject(new Error('Connection timeout')), 5000)
                                );

                                const joinResult = await Promise.race([
                                    multiplayerManager.joinGame(playerName, selectedSkin),
                                    timeoutPromise
                                ]);

                                roomCode = joinResult.room_code;
                                console.log('🌐 [Multiplayer] เข้าร่วมห้อง:', roomCode);
                            } catch (dbError) {
                                console.warn('[Multiplayer] DB join ล้มเหลว (ไม่กระทบเกม):', dbError.message);
                                multiplayerManager = null;
                            }

                            // ✅ เชื่อมต่อ Sync Client พร้อม room_id (ให้เห็นเฉพาะคนในห้องเดียวกัน)
                            syncClient = new SnakeSyncClient();
                            const syncJoined = await syncClient.join(playerName, selectedSkin, roomCode);

                            if (syncJoined) {
                                console.log('🌐 [SnakeSync] เชื่อมต่อ sync สำเร็จ! ห้อง:', syncClient.getRoomId());
                                isOnline = true;
                                updateConnectionStatus('online', '🌐 ONLINE MODE');
                            } else {
                                console.warn('[SnakeSync] Sync join ล้มเหลว');
                                syncClient = null;
                            }

                            // ถ้า sync สำเร็จ = ถือว่า online
                            if (syncClient && syncClient.isOnline()) {
                                updateConnectionStatus('online', '🌐 ONLINE MODE');
                                startConnectionMonitoring();
                            } else {
                                updateConnectionStatus('offline', '🤖 OFFLINE MODE');
                            }

                        } catch (multiplayerError) {
                            console.warn('[Multiplayer] เชื่อมต่อไม่สำเร็จ เล่นแบบ offline:', multiplayerError.message);
                            multiplayerManager = null;
                            syncClient = null;
                            updateConnectionStatus('offline', '🤖 OFFLINE MODE');
                        }
                    } else {
                        // Service ปิด = เล่นแบบ offline
                        console.log('🤖 [Service] Service ปิดอยู่ - เริ่มเกมแบบ offline mode');
                        multiplayerManager = null;
                        syncClient = null;
                        updateConnectionStatus('offline', '🤖 OFFLINE MODE');
                    }

                } catch (serviceError) {
                    console.warn('[Service] ตรวจสอบสถานะ service ไม่สำเร็จ เล่นแบบ offline:', serviceError.message);
                    isServiceOnline = false;
                    multiplayerManager = null;
                    syncClient = null;
                    updateConnectionStatus('offline', '🤖 OFFLINE MODE');
                }

                // ✅ เริ่มตรวจสอบสถานะ service ทุก 10 วินาที
                startServicePolling();

                // Create player (selectedSkin ถูกโหลดจาก server แล้วก่อนถึงจุดนี้)
                player = new Snake(0, 0, true, playerName, selectedSkin);
                console.log('Player created:', playerName, '| Skin:', selectedSkin);

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
                // ✨ ซ่อน Character Setup Screen (ไม่ใช่ start-screen แล้ว)
                document.getElementById('character-setup-screen').classList.remove('show');

                console.log('✅ Game started successfully!');
            } catch (error) {
                console.error('[Game] เริ่มเกมล้มเหลว:', error);
                alert('เกิดข้อผิดพลาด: ' + error.message + '\n\nกรุณารีเฟรชหน้าใหม่');
            }
        }

        async function endGame() {
            gameOver = true;
            gameStarted = false;

            // ✅ Fade out เพลง gameplay เมื่อจบเกม
            fadeOutMusic(1200);

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

            // ✅ แจ้ง sync client (playerDied ลบ session แล้ว ไม่ต้อง leave ซ้ำ)
            if (syncClient) {
                await syncClient.playerDied();
                syncClient.stopSyncLoop();
                syncClient = null;
            }

            // ตรวจสอบ wallet status
            await checkWalletStatus();
        }

        // ✅ เก็บวิธีจ่ายที่เลือก + ยอดคงเหลือ
        let _selectedPayment = 'wallet'; // default
        let _walletBalance = 0;
        let _coinBalance = 0;

        /**
         * เลือกวิธีจ่ายค่าบันทึกคะแนน (wallet หรือ coin)
         */
        function selectPaymentMethod(method) {
            _selectedPayment = method;
            const walletBtn = document.getElementById('pay-wallet-btn');
            const coinBtn = document.getElementById('pay-coin-btn');
            const walletStatusEl = document.getElementById('wallet-status');
            const saveBtnEl = document.getElementById('save-score-btn');

            if (!walletBtn || !coinBtn) return;

            // ✅ Highlight ปุ่มที่เลือก
            if (method === 'wallet') {
                walletBtn.style.border = '2px solid #fff';
                walletBtn.style.transform = 'scale(1.05)';
                coinBtn.style.border = '2px solid transparent';
                coinBtn.style.transform = 'scale(1)';
            } else {
                coinBtn.style.border = '2px solid #fff';
                coinBtn.style.transform = 'scale(1.05)';
                walletBtn.style.border = '2px solid transparent';
                walletBtn.style.transform = 'scale(1)';
            }

            // ✅ อัปเดตสถานะ + ปุ่มบันทึก
            const bal = method === 'wallet' ? _walletBalance : _coinBalance;
            const label = method === 'wallet' ? 'แต้ม' : 'เหรียญ';
            const icon = method === 'wallet' ? '💰' : '🪙';
            const color = method === 'wallet' ? '#ffaa00' : '#aa66ff';

            if (bal >= 1) {
                walletStatusEl.innerHTML = `
                    <p style="color: #00ff00; font-size: 14px;">
                        ${icon} ${label}คงเหลือ: <span style="font-weight: bold; font-size: 16px; color: ${color};">${bal.toFixed(2)}</span> ${label}
                    </p>
                    <p style="color: #ccc; font-size: 12px; margin-top: 5px;">
                        หลังบันทึกจะเหลือ: ${(bal - 1).toFixed(2)} ${label}
                    </p>
                `;
                saveBtnEl.disabled = false;
                saveBtnEl.textContent = `✅ บันทึกคะแนน (1 ${label})`;
            } else {
                walletStatusEl.innerHTML = `
                    <p style="color: #ff4444; font-size: 14px;">
                        ⚠️ ${label}ไม่เพียงพอ!
                    </p>
                    <p style="color: #ccc; font-size: 13px; margin: 8px 0;">
                        ${label}คงเหลือ: <span style="color: ${color}; font-weight: bold;">${bal.toFixed(2)}</span> ${label}<br>
                        ต้องการ: <span style="color: #ff4444; font-weight: bold;">1</span> ${label}
                    </p>
                    ${method === 'wallet' ? '<a href="/user/wallet/topup" class="btn" style="background: linear-gradient(135deg, #ffaa00, #ff6600); text-decoration: none; display: inline-block; padding: 8px 16px; font-size: 13px; margin-top: 5px;">💳 เติมเงิน</a>' : '<p style="color:#ccc; font-size:12px; margin-top:5px;">ทำภารกิจหรือดูวิดีโอเพื่อรับเหรียญ</p>'}
                `;
                saveBtnEl.disabled = true;
                saveBtnEl.textContent = `✅ บันทึกคะแนน (1 ${label})`;
            }
        }

        async function checkWalletStatus() {
            // สำหรับ Guest ไม่ต้องทำอะไร (UI แสดงอยู่แล้ว)
            if (!isAuthenticated) {
                return;
            }

            // สำหรับ Member - ดึงข้อมูล wallet + coin
            const walletStatusEl = document.getElementById('wallet-status');
            const saveBtnEl = document.getElementById('save-score-btn');

            if (!walletStatusEl || !saveBtnEl) {
                console.warn('[Wallet] ไม่พบ elements');
                return;
            }

            try {
                const response = await fetch('/api/games/snake-io/check-wallet', {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    throw new Error('ไม่สามารถดึงข้อมูลได้');
                }

                const data = await response.json();
                _walletBalance = parseFloat(data.balance || 0);
                _coinBalance = parseFloat(data.coin_balance || 0);

                console.log('[Wallet] แต้ม:', _walletBalance, '| เหรียญ:', _coinBalance);

                // ✅ อัปเดตยอดบนปุ่มเลือก
                const walletBalEl = document.getElementById('pay-wallet-bal');
                const coinBalEl = document.getElementById('pay-coin-bal');
                if (walletBalEl) walletBalEl.textContent = `(${_walletBalance.toFixed(0)})`;
                if (coinBalEl) coinBalEl.textContent = `(${_coinBalance.toFixed(0)})`;

                // ✅ เลือกวิธีจ่ายอัตโนมัติ — เลือกอันที่มียอดพอ (wallet ก่อน)
                if (_walletBalance >= 1) {
                    selectPaymentMethod('wallet');
                } else if (_coinBalance >= 1) {
                    selectPaymentMethod('coin');
                } else {
                    // ไม่มียอดพอทั้งคู่ — แสดง wallet เป็น default
                    selectPaymentMethod('wallet');
                }

            } catch (error) {
                console.error('[Wallet] Error:', error);
                walletStatusEl.innerHTML = `
                    <p style="color: #ff4444; font-size: 14px;">
                        ⚠️ ไม่สามารถดึงข้อมูลได้
                    </p>
                    <p style="color: #ccc; font-size: 12px; margin-top: 5px;">
                        ${error.message}
                    </p>
                `;
                saveBtnEl.disabled = true;
            }
        }

        /**
         * ดึง CSRF token จาก meta tag หรือ cookie
         * @returns {string} CSRF token
         */
        function getCsrfToken() {
            // ลองจาก meta tag ก่อน
            const meta = document.querySelector('meta[name="csrf-token"]');
            if (meta && meta.content) return meta.content;

            // fallback: ดึงจาก XSRF-TOKEN cookie (Laravel ตั้งไว้)
            const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
            if (match) return decodeURIComponent(match[1]);

            return '';
        }

        /**
         * ส่ง POST request พร้อม CSRF token + auto-retry ถ้า 419
         * @param {string} url
         * @param {object} data
         * @param {number} retries จำนวนครั้งที่ retry (default 1)
         * @returns {Promise<Response>}
         */
        async function fetchWithCsrf(url, data, retries = 1) {
            const doFetch = () => fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-XSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'same-origin',
                body: JSON.stringify(data)
            });

            let response = await doFetch();

            // ถ้า 419 (CSRF mismatch) → รีเฟรช token แล้วลองอีกครั้ง
            if (response.status === 419 && retries > 0) {
                console.warn('[CSRF] Token mismatch — กำลัง refresh แล้วลองใหม่...');
                try {
                    // ดึง token ใหม่จากหน้าเว็บ
                    const refreshResp = await fetch(window.location.href, {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: { 'Accept': 'text/html' }
                    });
                    const html = await refreshResp.text();
                    const tokenMatch = html.match(/<meta name="csrf-token" content="([^"]+)"/);
                    if (tokenMatch) {
                        // อัปเดต meta tag ด้วย token ใหม่
                        const metaEl = document.querySelector('meta[name="csrf-token"]');
                        if (metaEl) metaEl.content = tokenMatch[1];
                        console.log('[CSRF] Token refreshed สำเร็จ');
                    }
                } catch (e) {
                    console.warn('[CSRF] ไม่สามารถ refresh token:', e);
                }
                response = await doFetch();
            }

            return response;
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
                // ✅ บันทึกคะแนนและหัก wallet/coin — ใช้ fetchWithCsrf ที่ auto-retry 419
                const response = await fetchWithCsrf('/api/games/snake-io/save-score', {
                    score: score,
                    length: player.length,
                    rank: getRank(),
                    payment_method: _selectedPayment,  // wallet หรือ coin
                    player_name: playerName             // ชื่อหนอนที่ผู้เล่นตั้ง
                });

                if (!response.ok) {
                    let errorMsg = 'บันทึกคะแนนล้มเหลว';
                    try {
                        const errorData = await response.json();
                        errorMsg = errorData.message || errorMsg;
                    } catch(e) {}

                    // ข้อความเฉพาะสำหรับ 419 (session หมดอายุ)
                    if (response.status === 419) {
                        errorMsg = 'Session หมดอายุ กรุณารีเฟรชหน้าเว็บแล้วลองใหม่';
                    }
                    throw new Error(errorMsg);
                }

                const result = await response.json();

                if (result.success) {
                    // แสดงข้อความตามวิธีชำระเงินที่ใช้
                    const payLabel = result.payment_label || (_selectedPayment === 'coin' ? 'เหรียญ' : 'แต้ม');
                    const payIcon = _selectedPayment === 'coin' ? '🪙' : '💰';
                    alert(`✅ บันทึกคะแนนสำเร็จ!\n\nคะแนน: ${score}\nความยาว: ${player.length}\n\n${payIcon} ${payLabel}คงเหลือ: ${result.remaining_balance} ${payLabel}`);
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
                // ✅ ใช้ fetchWithCsrf ที่ auto-retry 419
                const response = await fetchWithCsrf('/api/games/snake-io/save-skin-preference', {
                    skin: selectedSkin
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

                        // เลือก skin option ที่ตรงกับสีที่บันทึกไว้
                        document.querySelectorAll('.skin-option').forEach(option => {
                            option.classList.remove('selected');
                            if (option.dataset.skin === data.skin) {
                                option.classList.add('selected');
                            }
                        });

                        statusEl.textContent = '✅ โหลดสีสำเร็จ: ' + data.skin;
                        statusEl.style.color = '#00ff00';

                        setTimeout(() => {
                            statusEl.textContent = '';
                        }, 3000);

                        console.log('[Skin] โหลดสี skin ที่บันทึกไว้:', data.skin);
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

            // ✅ หยุดตรวจสอบสถานะ service
            stopServicePolling();

            // Clean up — ลบ player จาก scene (ไม่เรียก die() ซ้ำ เพราะตายไปแล้ว)
            if (player) {
                player.segments.forEach(seg => scene.remove(seg));
                player.segments = [];
                if (player.nameSprite) { scene.remove(player.nameSprite); player.nameSprite = null; }
                player = null;
            }
            bots.forEach(bot => {
                bot.segments.forEach(seg => scene.remove(seg));
                bot.segments = []; // ✅ เคลียร์ segments ป้องกันซ้ำ
                if (bot.nameSprite) scene.remove(bot.nameSprite);
            });
            bots = [];

            foods.forEach(food => scene.remove(food));
            foods = [];

            // ✅ ลบ powerups + label sprites (ป้องกัน object ค้างใน scene)
            powerups.forEach(p => {
                if (p.userData.labelSprite) scene.remove(p.userData.labelSprite);
                scene.remove(p);
            });
            powerups = [];

            // ✅ ทำลาย touch manager ป้องกัน memory leak
            if (touchInputManager) {
                touchInputManager.destroy();
                touchInputManager = null;
            }

            // Reset multiplayer
            if (multiplayerManager) {
                multiplayerManager.disconnectWebSocket();
                multiplayerManager = null;
            }

            // ✅ Reset sync client
            if (syncClient) {
                syncClient.stopSyncLoop();
                syncClient = null;
            }

            // ✅ ลบงูของผู้เล่นออนไลน์ทั้งหมด (ป้องกัน memory leak + ghost snakes)
            for (const [playerId, snake] of otherPlayerSnakes) {
                snake.segments.forEach(seg => scene.remove(seg));
                if (snake.nameSprite) scene.remove(snake.nameSprite);
                if (snake.outline) scene.remove(snake.outline);
            }
            otherPlayerSnakes.clear();

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

            // ✅ รีเซ็ตตัวเลือกการชำระเงิน
            _selectedPayment = 'wallet';
            const payWalletBtn = document.getElementById('pay-wallet-btn');
            const payCoinBtn = document.getElementById('pay-coin-btn');
            if (payWalletBtn) {
                payWalletBtn.style.borderColor = 'transparent';
                const walBal = document.getElementById('pay-wallet-bal');
                if (walBal) walBal.textContent = '(...)';
            }
            if (payCoinBtn) {
                payCoinBtn.style.borderColor = 'transparent';
                const coinBal = document.getElementById('pay-coin-bal');
                if (coinBal) coinBal.textContent = '(...)';
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
            // ✨ แสดง Title Screen (ไม่ใช่ start-screen แล้ว)
            document.getElementById('title-screen').classList.remove('hidden');

            // 🏆 รีโหลด leaderboard เมื่อกลับหน้า title (อาจมีคะแนนใหม่)
            loadTitleLeaderboard();

            // 🏆 ซ่อน Top 3 In-Game + รีเซ็ต key
            const top3El = document.getElementById('ingame-top3');
            if (top3El) top3El.innerHTML = '';
            top3LastKey = '';

            // Respawn food
            for (let i = 0; i < CONFIG.FOOD_COUNT; i++) {
                createFood();
            }

            // ✅ เล่นเพลง Title อีกครั้ง พร้อม fade in
            playMusic('title', true);
        }

        function getRank() {
            // ✅ รวมผู้เล่นออนไลน์ด้วย (รวม player ทั้งที่ตายแล้วเพื่อคำนวณ rank)
            const allSnakes = [player, ...bots].filter(s => s != null);
            for (const [, onlineSnake] of otherPlayerSnakes) {
                if (onlineSnake) {
                    allSnakes.push(onlineSnake);
                }
            }
            allSnakes.sort((a, b) => b.score - a.score);
            const rank = allSnakes.indexOf(player) + 1;
            return rank > 0 ? rank : 1; // ป้องกัน rank = 0
        }

        function updateLeaderboard() {
            // ✅ รวมผู้เล่นออนไลน์ใน leaderboard ด้วย
            const allSnakes = [player, ...bots].filter(s => s && s.alive);
            for (const [, onlineSnake] of otherPlayerSnakes) {
                if (onlineSnake && onlineSnake.alive) {
                    allSnakes.push(onlineSnake);
                }
            }
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

        // ✅ Performance: ตัวนับเฟรมสำหรับ throttle bot AI
        let botAIFrameCount = 0;

        function updateBots(now) {
            botAIFrameCount++;

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

            // ✅ Performance: รัน AI เต็มรูปแบบทุก 3 เฟรม (ลด CPU 66%)
            const runFullAI = (botAIFrameCount % 3 === 0);

            // อัปเดตบอทที่ยังมีชีวิต
            bots.forEach(bot => {
                const head = bot.segments[0].position;

                // ✅ Performance: รัน AI เต็มรูปแบบเฉพาะบางเฟรม
                if (runFullAI) {
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

                        // ✅ พฤติกรรม 2: หาอาหารใกล้ที่สุด
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
                } // end runFullAI

                // อัปเดตตำแหน่งบอท (ส่ง now เข้าไป)
                bot.update(now);

                // Check food collision
                for (let i = foods.length - 1; i >= 0; i--) {
                    const food = foods[i];
                    if (head.distanceTo(food.position) < 1) {
                        const foodValue = food.userData.value || 1;

                        // ✅ บอทกินอาหาร - ใช้ระบบเดียวกับผู้เล่น (ต้องกิน 10, 15, 20... คะแนนต่อปล้อง)
                        bot.score += foodValue;

                        // ✅ ตรวจสอบว่าคะแนนพอเติบโตหรือยัง
                        // ระบบใหม่: nextGrowthScore คำนวณใน grow() โดยบวกจากคะแนนปัจจุบัน
                        while (bot.score >= bot.nextGrowthScore) {
                            bot.grow(1); // เติบโต 1 ปล้อง (จะอัพเดท nextGrowthScore อัตโนมัติ)
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

            // ✅ แคช timestamp เพื่อลด Date.now() calls (ป้องกันเกมค้าง)
            const now = Date.now();

            // Animate powerups (spin + label ลอยตาม)
            powerups.forEach(powerup => {
                powerup.rotation.y += 0.05;
                powerup.position.y = 0.8 + Math.sin(now * 0.003) * 0.2;
                // ✅ อัพเดทตำแหน่ง label ให้ลอยเหนือ powerup
                if (powerup.userData.labelSprite) {
                    powerup.userData.labelSprite.position.y = powerup.position.y + 1.7;
                }
            });

            // ✅ Sync สถานะผู้เล่นกับ server
            if (player && player.alive) {
                const headPos = player.segments[0].position;

                // ✅ ใช้ Sync Client (Cache-based) - เก็บสถานะไว้ให้ sync loop ส่งเอง (ไม่ HTTP ทุก frame)
                if (syncClient && syncClient.isOnline()) {
                    syncClient.setCurrentState({
                        position: { x: headPos.x, y: headPos.y, z: headPos.z },
                        direction: { x: player.direction.x, y: player.direction.y, z: player.direction.z },
                        score: player.score,
                        length: player.length,
                        is_alive: player.alive,
                    });
                }

                // ✅ ใช้ DB Multiplayer Manager (throttled ทุก 200ms) - สำหรับ room sync
                if (multiplayerManager) {
                    multiplayerManager.updatePlayerState(
                        headPos,
                        player.direction,
                        player.score,
                        player.length
                    );
                }
            }

            // ✅ Render ผู้เล่นคนอื่น (เฉพาะเมื่อ online)
            if (isOnline) {
                renderOtherPlayers(now);
            }

            if (player && player.alive) {
                // Apply speed boost
                const speedMultiplier = activePowerups.speed ? 1.5 : 1;
                player.speed = CONFIG.MOVEMENT_SPEED * speedMultiplier;

                // ✅ ส่ง now เข้าไปเพื่อลด Date.now() calls
                player.update(now);

                // ✅ หักแต้มเมื่อเร่งความเร็ว 1 แต้ม/วินาที + ลด 1 ข้อ/3 วินาที (ติดต่อกัน) + เรืองแสง
                if (player.isBoosting) {
                    const pointsPerSecond = 1; // ✅ เปลี่ยนจาก 3 เป็น 1
                    const deltaTime = 1 / 60; // 60 FPS
                    const deduction = pointsPerSecond * deltaTime;

                    // หักคะแนนและใช้ Math.floor เพื่อไม่ให้มีทศนิยม
                    player.score = Math.floor(player.score - deduction);

                    // ✅ บันทึกเวลาเริ่ม boost (ถ้ายังไม่มี) - ใช้ now แทน Date.now()
                    if (player.boostStartTime === null) {
                        player.boostStartTime = now;
                    }

                    // ✅ เช็คว่า boost ต่อเนื่องครบ 3 วินาทีหรือยัง
                    const boostDuration = now - player.boostStartTime;
                    const shrinkInterval = 3000; // 3 วินาที = 3000ms

                    if (boostDuration >= shrinkInterval && (now - player.lastShrinkTime) >= shrinkInterval) {
                        // เร่งติดต่อกันครบ 3 วินาที และยังไม่เคย shrink ในช่วงนี้
                        player.shrink(1); // ลด 1 ข้อ
                        player.lastShrinkTime = now; // บันทึกเวลา shrink
                        player.boostStartTime = now; // รีสตาร์ทนับ 3 วินาทีใหม่
                    }

                    // หยุด boost ถ้าแต้มไม่พอ
                    if (player.score < 1) {
                        player.score = Math.max(0, player.score); // ป้องกันติดลบ
                        player.isBoosting = false;
                        player.boostStartTime = null; // ✅ รีเซ็ตเมื่อหยุด boost
                    }
                } else {
                    // ✅ รีเซ็ต boostStartTime เมื่อไม่ boost
                    player.boostStartTime = null;
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

                        // ✅ ตรวจสอบว่าคะแนนพอเติบโตหรือยัง
                        // ระบบใหม่: nextGrowthScore คำนวณใน grow() โดยบวกจากคะแนนปัจจุบัน
                        while (player.score >= player.nextGrowthScore) {
                            player.grow(1); // เติบโต 1 ปล้อง (จะอัพเดท nextGrowthScore อัตโนมัติ)
                        }

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
                        playSound('powerup'); // ✅ เสียงเก็บพาวเวอร์อัพ
                        activatePowerup(powerup.userData.type);
                        // ✅ ลบ label sprite ด้วย
                        if (powerup.userData.labelSprite) {
                            scene.remove(powerup.userData.labelSprite);
                        }
                        scene.remove(powerup);
                        powerups.splice(i, 1);
                    }
                }

                // ✅ ปรับระยะกล้องตามขนาดหนอน (ไม่เกิน 12 ปกติ, 20 เมื่อ zoom)
                const baseCameraDistance = 12; // ✅ ระยะกล้องตอนเกิด (เพิ่มจาก 5 เป็น 12 ให้ไกลขึ้น)
                const lengthMultiplier = 0; // ✅ ไม่ขยับกล้องตามความยาว (คงที่)
                const maxCameraDistance = 12; // ✅ จำกัดระยะสูงสุดปกติที่ 12

                // คำนวณระยะกล้องตามขนาดหนอน
                let calculatedDistance = baseCameraDistance + (player.length * lengthMultiplier);
                calculatedDistance = Math.min(calculatedDistance, maxCameraDistance);

                // Smooth camera zoom
                if (activePowerups.zoom) {
                    // ✅ มี zoom powerup ให้ออกไปได้ถึง 20 (แทน 50)
                    targetCameraDistance = 20;
                } else {
                    // ปกติจำกัดที่ 5 (ใกล้มาก)
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

            // ✅ ส่ง now เข้าไปเพื่อลด Date.now() calls
            updateBots(now);

            // Check all snake-to-snake collisions
            checkAllSnakeCollisions();

            updateLeaderboard();

            // 🏆 อัปเดต Top 3 In-Game Display
            updateInGameTop3();

            // Update powerup UI timers
            if (Object.values(activePowerups).some(p => p !== null)) {
                updatePowerupUI();
            }

            // ✅ Animate RGB สำหรับอาหารจากการตาย (เรืองแสง RGB) - ใช้ now แทน Date.now()
            const rgbHue = (now * 0.001) % 1; // เปลี่ยนทุก 1 วินาที

            // ✅ ตรวจสอบและลบอาหารที่หมดอายุ (30 วินาที)
            foods = foods.filter(food => {
                // อาหารจากการตาย - ตรวจสอบหมดอายุ
                if (food.userData.expiresAt && now >= food.userData.expiresAt) {
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

            // ✅ Maintain food count (เพิ่ม safety limit ป้องกันค้าง)
            let foodSpawnIterations = 0;
            const MAX_FOOD_SPAWN_PER_FRAME = 5; // สร้างสูงสุด 5 ชิ้นต่อเฟรม
            while (foods.length < CONFIG.FOOD_COUNT && foodSpawnIterations < MAX_FOOD_SPAWN_PER_FRAME) {
                createFood();
                foodSpawnIterations++;
            }

            // ✅ Maintain bot count (เพิ่ม safety limit ป้องกันค้าง)
            let botSpawnIterations = 0;
            const MAX_BOT_SPAWN_PER_FRAME = 2; // สร้างสูงสุด 2 บอทต่อเฟรม
            while (bots.length < CONFIG.BOT_COUNT && botSpawnIterations < MAX_BOT_SPAWN_PER_FRAME) {
                createBot();
                botSpawnIterations++;
            }

            // ✅ Spawn powerups (ลด spawn rate จาก 15% เป็น 2% เพื่อป้องกันค้าง)
            if (powerups.length < 4 && Math.random() < 0.02) {
                createPowerup();
            }
        }

        // ✅ FIX: Background Tab Support
        // requestAnimationFrame หยุดทำงานเมื่อแท็บถูกซ่อน
        // ใช้ setInterval เป็น fallback + ชดเชย frames ที่หายไป
        // (Chrome throttle setInterval เหลือ ~1 ครั้ง/วินาที ในแท็บที่ซ่อน)
        let _bgInterval = null;
        let _isTabHidden = false;
        let _lastBgTime = 0;

        function animate() {
            requestAnimationFrame(animate);
            if (_isTabHidden) return; // ถ้าแท็บซ่อน → ให้ setInterval จัดการแทน
            update();
            renderer.render(scene, camera);
        }

        /**
         * Game loop สำหรับตอนแท็บถูกซ่อน
         *
         * ปัญหา: Chrome throttle setInterval เหลือ ~1 ครั้ง/วินาที
         * แต่เกมทำงาน 60fps → ถ้าเรียก update() แค่ 1 ครั้ง/วินาที หนอนแทบไม่ขยับ
         *
         * แก้: ทุกครั้งที่ tick → คำนวณเวลาที่หายไป → เรียก update() หลายรอบชดเชย
         * เช่น ถ้า 1 วินาทีผ่านไป → เรียก update() 15 ครั้ง (จำลอง ~15fps)
         * → หนอนยังวิ่ง, บอทยังเล่น, sync ยังส่ง state
         */
        function startBackgroundLoop() {
            if (_bgInterval) return;
            _lastBgTime = Date.now();
            _bgInterval = setInterval(() => {
                if (!gameStarted || gameOver) return;

                const now = Date.now();
                const elapsed = now - _lastBgTime;
                _lastBgTime = now;

                // คำนวณจำนวน frames ที่ต้องชดเชย (~15fps target, cap ที่ 20 frames)
                // 66ms = 1000/15 → 15fps
                const steps = Math.min(Math.ceil(elapsed / 66), 20);

                for (let i = 0; i < steps; i++) {
                    update();
                }
            }, 100); // ตั้ง 100ms แต่ browser จะ throttle เป็น ~1000ms
        }

        function stopBackgroundLoop() {
            if (_bgInterval) {
                clearInterval(_bgInterval);
                _bgInterval = null;
            }
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
         * ✅ Render ผู้เล่นคนอื่นด้วย Client-side Prediction
         *
         * แนวคิด:
         * - ไม่ sync ตำแหน่งทุก frame (ทำให้ค้าง)
         * - Sync เฉพาะ INPUT: direction, isBoosting (ข้อมูลเล็ก, realtime)
         * - Client คำนวณ movement เอง (smooth 60 FPS)
         * - Server ส่ง position correction เป็นครั้งคราว (ทุก 500ms)
         * - Smooth lerp correction เพื่อไม่ให้กระตุก
         */
        function renderOtherPlayers(now) {
            // ✅ ใช้ Sync Client เป็นแหล่งข้อมูลหลัก, fallback ไป Multiplayer Manager
            let otherPlayers = [];
            if (syncClient && syncClient.isOnline()) {
                otherPlayers = syncClient.getOtherPlayers();
            } else if (multiplayerManager) {
                otherPlayers = multiplayerManager.getOtherPlayers();
            } else {
                return;
            }

            otherPlayers.forEach(playerData => {
                // สร้างหรืออัปเดตงูของผู้เล่นคนอื่น
                if (!otherPlayerSnakes.has(playerData.id)) {
                    // ✅ สร้างใหม่
                    const snake = new Snake(
                        playerData.position.x,
                        playerData.position.z,
                        false,
                        playerData.name,
                        playerData.skin
                    );

                    // ✅ เพิ่ม metadata สำหรับ client-side prediction
                    snake.lastServerPosition = new THREE.Vector3(
                        playerData.position.x,
                        0.5,
                        playerData.position.z
                    );
                    snake.lastServerUpdate = now;
                    snake.positionCorrectionNeeded = false;

                    otherPlayerSnakes.set(playerData.id, snake);
                } else {
                    // ✅ อัปเดต INPUT จาก server (realtime, ข้อมูลเล็ก)
                    const snake = otherPlayerSnakes.get(playerData.id);

                    // อัปเดตทิศทางจาก server (ทุกครั้งที่มีการเปลี่ยน)
                    if (playerData.direction) {
                        snake.targetDirection.set(
                            playerData.direction.x,
                            playerData.direction.y || 0,
                            playerData.direction.z
                        );
                    }

                    // อัปเดต boost state
                    if (playerData.isBoosting !== undefined) {
                        snake.isBoosting = playerData.isBoosting;
                    }

                    // อัปเดตข้อมูล meta
                    snake.score = playerData.score || snake.score;
                    snake.length = playerData.length || snake.length;
                    snake.alive = playerData.is_alive !== undefined ? playerData.is_alive : snake.alive;

                    // ✅ Position Correction (จาก server ทุกครั้งที่มีข้อมูลตำแหน่ง)
                    if (playerData.position) {
                        const serverPos = new THREE.Vector3(
                            playerData.position.x,
                            0.5,
                            playerData.position.z
                        );

                        // คำนวณระยะห่างจากตำแหน่งปัจจุบัน
                        const currentPos = snake.segments[0].position;
                        const distance = currentPos.distanceTo(serverPos);

                        // ✅ ถ้าห่างเกิน 3 หน่วย = correction แรง (teleport)
                        if (distance > 3) {
                            // Rubber banding - warp to server position
                            snake.segments[0].position.copy(serverPos);
                            snake.lastServerPosition.copy(serverPos);
                        }
                        // ✅ ถ้าห่างน้อย = smooth correction (lerp)
                        else if (distance > 0.5) {
                            // Gentle correction - lerp slowly
                            snake.lastServerPosition.copy(serverPos);
                            snake.positionCorrectionNeeded = true;
                        }

                        snake.lastServerUpdate = now;
                    }

                    // ✅ Smooth position correction (lerp)
                    if (snake.positionCorrectionNeeded) {
                        const currentPos = snake.segments[0].position;
                        const targetPos = snake.lastServerPosition;

                        // Lerp ช้าๆ เพื่อความ smooth
                        currentPos.lerp(targetPos, 0.1);

                        // ถ้าใกล้พอแล้ว = หยุด correction
                        if (currentPos.distanceTo(targetPos) < 0.1) {
                            snake.positionCorrectionNeeded = false;
                        }
                    }

                    // ✅ คำนวณ movement ที่ client (smooth 60 FPS)
                    // แทนที่จะรอ server ส่งตำแหน่งมา
                    snake.update(now);
                }
            });

            // ลบผู้เล่นที่ออกไปแล้ว
            for (const [playerId, snake] of otherPlayerSnakes) {
                const stillExists = otherPlayers.some(p => p.id === playerId);
                if (!stillExists) {
                    // ลบงู
                    snake.segments.forEach(seg => scene.remove(seg));
                    if (snake.nameSprite) scene.remove(snake.nameSprite);
                    if (snake.outline) scene.remove(snake.outline);
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

        /**
         * ✅ Fullscreen API Support
         */
        function toggleFullscreen() {
            const elem = document.documentElement;
            const btn = document.getElementById('fullscreen-toggle');

            if (!document.fullscreenElement && !document.webkitFullscreenElement && !document.mozFullScreenElement) {
                // เข้าสู่ fullscreen
                if (elem.requestFullscreen) {
                    elem.requestFullscreen();
                } else if (elem.webkitRequestFullscreen) { /* Safari */
                    elem.webkitRequestFullscreen();
                } else if (elem.mozRequestFullScreen) { /* Firefox */
                    elem.mozRequestFullScreen();
                } else if (elem.msRequestFullscreen) { /* IE11 */
                    elem.msRequestFullscreen();
                }
                btn.classList.add('active');
                btn.innerHTML = '⛶'; // icon เต็มจอ
            } else {
                // ออกจาก fullscreen
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) { /* Safari */
                    document.webkitExitFullscreen();
                } else if (document.mozCancelFullScreen) { /* Firefox */
                    document.mozCancelFullScreen();
                } else if (document.msExitFullscreen) { /* IE11 */
                    document.msExitFullscreen();
                }
                btn.classList.remove('active');
                btn.innerHTML = '⛶'; // icon ปกติ
            }
        }

        /**
         * ✅ ตรวจจับการออกจาก fullscreen (กด ESC)
         */
        function handleFullscreenChange() {
            const btn = document.getElementById('fullscreen-toggle');
            if (!document.fullscreenElement && !document.webkitFullscreenElement && !document.mozFullScreenElement) {
                btn.classList.remove('active');
                btn.innerHTML = '⛶';
            } else {
                btn.classList.add('active');
                btn.innerHTML = '⛶';
            }
        }

        /**
         * ✨ Title Screen Background Animation (งูเคลื่อนไหว)
         */
        function initTitleAnimation() {
            const canvas = document.getElementById('title-bg-canvas');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;

            class AnimatedSnake {
                constructor() {
                    this.x = Math.random() * canvas.width;
                    this.y = Math.random() * canvas.height;
                    this.length = 20 + Math.random() * 30;
                    this.segments = [];
                    this.angle = Math.random() * Math.PI * 2;
                    this.speed = 0.5 + Math.random();
                    this.hue = Math.random() * 60 + 120;

                    for (let i = 0; i < this.length; i++) {
                        this.segments.push({ x: this.x, y: this.y });
                    }
                }

                update() {
                    this.angle += (Math.random() - 0.5) * 0.1;
                    this.x += Math.cos(this.angle) * this.speed;
                    this.y += Math.sin(this.angle) * this.speed;

                    if (this.x < 0) this.x = canvas.width;
                    if (this.x > canvas.width) this.x = 0;
                    if (this.y < 0) this.y = canvas.height;
                    if (this.y > canvas.height) this.y = 0;

                    this.segments.unshift({ x: this.x, y: this.y });
                    if (this.segments.length > this.length) {
                        this.segments.pop();
                    }
                }

                draw() {
                    for (let i = 0; i < this.segments.length; i++) {
                        const segment = this.segments[i];
                        const alpha = (1 - i / this.segments.length) * 0.6;
                        const size = (1 - i / this.segments.length) * 15 + 5;

                        ctx.fillStyle = `hsla(${this.hue}, 70%, 50%, ${alpha})`;
                        ctx.beginPath();
                        ctx.arc(segment.x, segment.y, size, 0, Math.PI * 2);
                        ctx.fill();
                    }
                }
            }

            const snakes = [];
            for (let i = 0; i < 5; i++) {
                snakes.push(new AnimatedSnake());
            }

            // ✅ เก็บ animation ID เพื่อหยุดได้
            let titleAnimId = null;

            function animate() {
                titleAnimId = requestAnimationFrame(animate);

                ctx.fillStyle = 'rgba(10, 10, 26, 0.1)';
                ctx.fillRect(0, 0, canvas.width, canvas.height);

                snakes.forEach(snake => {
                    snake.update();
                    snake.draw();
                });
            }

            animate();

            // ✅ เปิด API ให้หยุด animation จากภายนอก
            window.stopTitleAnimation = function() {
                if (titleAnimId) {
                    cancelAnimationFrame(titleAnimId);
                    titleAnimId = null;
                }
            };

            window.addEventListener('resize', () => {
                canvas.width = window.innerWidth;
                canvas.height = window.innerHeight;
            });
        }

        /**
         * ✅ Event Listeners สำหรับ UI Controls
         */
        document.addEventListener('DOMContentLoaded', function() {
            // ✨ เริ่ม Title Background Animation
            initTitleAnimation();

            // 🏆 โหลด Top 100 Leaderboard สำหรับหน้า Title
            loadTitleLeaderboard();

            // 🎵 โหลด Config ทันที → พยายาม autoplay เพลง
            loadGameConfig().then(() => {
                console.log('[Init] Config loaded - music tracks ready');
                // พยายาม autoplay ทันที (จะสำเร็จถ้าผู้ใช้เคย interact กับเว็บมาก่อน)
                if (window._tryAutoplayMusic) window._tryAutoplayMusic();
            }).catch(err => {
                console.warn('[Init] Config load failed (will retry on game start):', err.message);
            });

            // Fullscreen toggle
            const fullscreenBtn = document.getElementById('fullscreen-toggle');
            if (fullscreenBtn) {
                fullscreenBtn.addEventListener('click', toggleFullscreen);
            }

            // ตรวจจับการเปลี่ยนสถานะ fullscreen
            document.addEventListener('fullscreenchange', handleFullscreenChange);
            document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
            document.addEventListener('mozfullscreenchange', handleFullscreenChange);
            document.addEventListener('MSFullscreenChange', handleFullscreenChange);

            // Sound toggle
            const soundBtn = document.getElementById('sound-toggle');
            if (soundBtn) {
                soundBtn.addEventListener('click', function() {
                    soundEnabled = !soundEnabled;
                    this.textContent = soundEnabled ? '🔊' : '🔇';
                    this.classList.toggle('muted', !soundEnabled);

                    // Resume audio context if suspended
                    if (soundEnabled && audioContext && audioContext.state === 'suspended') {
                        audioContext.resume();
                    }
                });
            }

            // 🎵 พยายาม autoplay เพลง (จะสำเร็จถ้า browser อนุญาต)
            window._tryAutoplayMusic = function() {
                if (window._musicUserStarted) return;
                if (gameStarted) return; // ถ้าเกมเริ่มแล้ว ไม่ต้อง autoplay title
                initAudio();
                if (musicPlayer.titleTracks.length > 0) {
                    const testAudio = new Audio(musicPlayer.titleTracks[0].url);
                    testAudio.volume = musicPlayer.volume;
                    testAudio.play().then(() => {
                        // ✅ ตรวจสอบสถานะอีกครั้ง — อาจเปลี่ยนระหว่างรอ play
                        if (window._musicUserStarted || gameStarted) {
                            testAudio.pause();
                            testAudio.src = '';
                            return;
                        }
                        console.log('[Music] ✅ Autoplay สำเร็จ!');
                        window._musicUserStarted = true;
                        // หยุด testAudio ชั่วคราว แล้วเล่นผ่าน playMusic ให้จัดการ
                        testAudio.pause();
                        testAudio.src = '';
                        playMusic('title');
                        const hint = document.getElementById('music-hint');
                        if (hint) hint.style.display = 'none';
                    }).catch(() => {
                        testAudio.pause();
                        testAudio.src = '';
                        console.log('[Music] Browser บล็อค autoplay - รอ user คลิก');
                        const hint = document.getElementById('music-hint');
                        if (hint) hint.style.display = 'flex';
                    });
                } else {
                    const hint = document.getElementById('music-hint');
                    if (hint) hint.style.display = 'flex';
                }
            };

            // ✅ เริ่มเพลงเมื่อผู้ใช้ interact ครั้งแรก (ถ้า autoplay ไม่สำเร็จ)
            window._onFirstUserInteract = function() {
                if (window._musicUserStarted) return;
                window._musicUserStarted = true;
                document.removeEventListener('click', window._onFirstUserInteract);
                document.removeEventListener('touchstart', window._onFirstUserInteract);

                initAudio();
                const hint = document.getElementById('music-hint');
                if (hint) hint.style.display = 'none';

                // ถ้าเกมเริ่มไปแล้ว → ไม่ต้องเล่น title music
                if (gameStarted) return;

                console.log('[Music] User interact → เล่น title music');
                playMusic('title');
            };
            document.addEventListener('click', window._onFirstUserInteract);
            document.addEventListener('touchstart', window._onFirstUserInteract);

            // ✅ Landscape lock - ลองขอ screen orientation lock
            if (screen.orientation && screen.orientation.lock) {
                screen.orientation.lock('landscape').catch(() => {
                    // ไม่สามารถ lock ได้ - ใช้ CSS media query แทน
                });
            }
        });

        init();
    </script>
</body>
</html>
