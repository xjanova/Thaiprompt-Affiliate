<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
            background: #0a0a0f;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            overflow: hidden;
            position: relative;
        }

        /* Floating Orbs Background */
        .orbs-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
            pointer-events: none;
        }

        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.4;
            animation: floatOrb 20s ease-in-out infinite;
        }

        .orb-1 {
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, var(--orb-color-1, #6366f1) 0%, transparent 70%);
            top: -10%;
            left: -5%;
            animation-delay: 0s;
            animation-duration: 25s;
            transition: background 1s ease;
        }

        .orb-2 {
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, var(--orb-color-2, #a855f7) 0%, transparent 70%);
            top: 60%;
            right: -10%;
            animation-delay: -5s;
            animation-duration: 20s;
            transition: background 1s ease;
        }

        .orb-3 {
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, var(--orb-color-3, #ec4899) 0%, transparent 70%);
            bottom: -5%;
            left: 30%;
            animation-delay: -10s;
            animation-duration: 22s;
            transition: background 1s ease;
        }

        .orb-4 {
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, var(--orb-color-4, #06b6d4) 0%, transparent 70%);
            top: 20%;
            right: 20%;
            animation-delay: -7s;
            animation-duration: 18s;
            transition: background 1s ease;
        }

        .orb-5 {
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, var(--orb-color-5, #10b981) 0%, transparent 70%);
            top: 40%;
            left: 10%;
            animation-delay: -3s;
            animation-duration: 23s;
            transition: background 1s ease;
        }

        /* Level Themes - สีพื้นหลังตามระดับ */
        body.theme-calm {
            --orb-color-1: #6366f1;
            --orb-color-2: #a855f7;
            --orb-color-3: #818cf8;
            --orb-color-4: #06b6d4;
            --orb-color-5: #10b981;
        }

        body.theme-warm {
            --orb-color-1: #f59e0b;
            --orb-color-2: #f97316;
            --orb-color-3: #eab308;
            --orb-color-4: #facc15;
            --orb-color-5: #fb923c;
        }

        body.theme-danger {
            --orb-color-1: #ef4444;
            --orb-color-2: #f43f5e;
            --orb-color-3: #ec4899;
            --orb-color-4: #dc2626;
            --orb-color-5: #f87171;
        }

        body.theme-extreme {
            --orb-color-1: #7f1d1d;
            --orb-color-2: #991b1b;
            --orb-color-3: #b91c1c;
            --orb-color-4: #450a0a;
            --orb-color-5: #dc2626;
        }

        body.theme-nightmare {
            --orb-color-1: #18181b;
            --orb-color-2: #7c3aed;
            --orb-color-3: #dc2626;
            --orb-color-4: #4c1d95;
            --orb-color-5: #6b21a8;
        }

        /* Obstacle block style */
        .obstacle-warning {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: linear-gradient(0deg, rgba(255, 0, 0, 0.3), transparent);
            pointer-events: none;
            animation: warningPulse 0.5s ease-in-out;
            border-radius: 0 0 10px 10px;
        }

        @keyframes warningPulse {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 1; }
        }

        /* Level indicator */
        .level-indicator {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.8), rgba(30, 30, 30, 0.9));
            padding: 10px 30px;
            border-radius: 30px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            z-index: 100;
            display: flex;
            align-items: center;
            gap: 15px;
            backdrop-filter: blur(10px);
        }

        .level-badge {
            font-family: 'Press Start 2P', monospace;
            font-size: 14px;
            color: #ffd700;
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
        }

        .difficulty-label {
            font-size: 12px;
            padding: 4px 12px;
            border-radius: 15px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .diff-easy { background: #10b981; color: white; }
        .diff-normal { background: #3b82f6; color: white; }
        .diff-hard { background: #f59e0b; color: white; }
        .diff-expert { background: #ef4444; color: white; }
        .diff-nightmare { background: linear-gradient(135deg, #7c3aed, #dc2626); color: white; }

        @keyframes floatOrb {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            25% {
                transform: translate(30px, -40px) scale(1.1);
            }
            50% {
                transform: translate(-20px, 30px) scale(0.9);
            }
            75% {
                transform: translate(40px, 20px) scale(1.05);
            }
        }

        /* Grid overlay */
        .grid-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: 0;
            pointer-events: none;
        }

        .main-wrapper {
            position: relative;
            z-index: 1;
            display: flex;
            gap: 20px;
            align-items: flex-start;
            padding: 20px;
        }

        .game-container {
            position: relative;
            display: flex;
            gap: 20px;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(20px);
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5), 0 0 100px rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: transform 0.05s ease-out;
        }

        /* Live Leaderboard Panel */
        .live-leaderboard {
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(20px);
            padding: 20px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            min-width: 220px;
            max-height: 600px;
            overflow-y: auto;
        }

        .live-leaderboard h2 {
            color: #fbbf24;
            font-size: 14px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 2px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .live-leaderboard-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .live-lb-entry {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }

        .live-lb-entry:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .live-lb-entry.top-1 {
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.2), rgba(255, 165, 0, 0.1));
            border-color: rgba(255, 215, 0, 0.3);
        }

        .live-lb-entry.top-2 {
            background: linear-gradient(135deg, rgba(192, 192, 192, 0.15), rgba(169, 169, 169, 0.1));
            border-color: rgba(192, 192, 192, 0.2);
        }

        .live-lb-entry.top-3 {
            background: linear-gradient(135deg, rgba(205, 127, 50, 0.15), rgba(184, 115, 51, 0.1));
            border-color: rgba(205, 127, 50, 0.2);
        }

        .live-lb-rank {
            font-family: 'Press Start 2P', monospace;
            font-size: 10px;
            min-width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.1);
        }

        .live-lb-entry.top-1 .live-lb-rank { color: #ffd700; background: rgba(255, 215, 0, 0.2); }
        .live-lb-entry.top-2 .live-lb-rank { color: #c0c0c0; background: rgba(192, 192, 192, 0.2); }
        .live-lb-entry.top-3 .live-lb-rank { color: #cd7f32; background: rgba(205, 127, 50, 0.2); }

        .live-lb-info {
            flex: 1;
            min-width: 0;
        }

        .live-lb-name {
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .live-lb-date {
            color: rgba(255, 255, 255, 0.4);
            font-size: 9px;
            margin-top: 2px;
        }

        .live-lb-score {
            font-family: 'Press Start 2P', monospace;
            font-size: 11px;
            color: #22d3ee;
            text-shadow: 0 0 10px rgba(34, 211, 238, 0.5);
        }

        .live-lb-empty {
            text-align: center;
            color: rgba(255, 255, 255, 0.4);
            font-size: 12px;
            padding: 20px;
        }

        .game-container.shake {
            animation: shake 0.3s ease-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0) translateY(0); }
            25% { transform: translateX(-5px) translateY(2px); }
            50% { transform: translateX(5px) translateY(-2px); }
            75% { transform: translateX(-3px) translateY(1px); }
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

        /* Particle container */
        #particle-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
            border-radius: 10px;
        }

        .particle {
            position: absolute;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            pointer-events: none;
            animation: particleFade 0.8s ease-out forwards;
        }

        @keyframes particleFade {
            0% { opacity: 1; transform: scale(1); }
            100% { opacity: 0; transform: scale(0) translateY(-50px); }
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
            gap: 15px;
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
            font-size: 14px;
            margin-bottom: 12px;
            text-align: center;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .stat-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 8px 0;
            padding: 8px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 5px;
        }

        .stat-label {
            color: rgba(255, 255, 255, 0.8);
            font-size: 12px;
        }

        .stat-value {
            color: #fff;
            font-size: 16px;
            font-weight: 700;
            font-family: 'Press Start 2P', monospace;
        }

        /* Combo display */
        .combo-display {
            text-align: center;
            padding: 10px;
            background: linear-gradient(135deg, rgba(255, 165, 0, 0.3), rgba(255, 69, 0, 0.3));
            border-radius: 8px;
            border: 2px solid rgba(255, 165, 0, 0.5);
            display: none;
        }

        .combo-display.active {
            display: block;
            animation: comboPulse 0.5s ease-out;
        }

        @keyframes comboPulse {
            0% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        .combo-text {
            color: #ffa500;
            font-size: 14px;
            font-weight: 700;
            font-family: 'Press Start 2P', monospace;
            text-shadow: 0 0 10px rgba(255, 165, 0, 0.8);
        }

        .next-piece-box, .hold-piece-box {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(5px);
            padding: 15px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .next-piece-box h2, .hold-piece-box h2 {
            color: #fff;
            font-size: 12px;
            margin-bottom: 10px;
            text-align: center;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        #next-canvas, #hold-canvas {
            display: block;
            margin: 0 auto;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 5px;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .hold-piece-box.used {
            opacity: 0.5;
        }

        .high-score-box {
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.2), rgba(255, 165, 0, 0.2));
            backdrop-filter: blur(5px);
            padding: 15px;
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
            font-size: 12px;
            margin-bottom: 10px;
            text-align: center;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .high-score-value {
            color: #ffd700;
            font-size: 20px;
            font-weight: 700;
            font-family: 'Press Start 2P', monospace;
            text-align: center;
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
        }

        /* Sound control */
        .sound-control {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 5px;
        }

        .sound-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            padding: 8px 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #fff;
            font-size: 16px;
        }

        .sound-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
        }

        .sound-btn.muted {
            opacity: 0.5;
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
            padding: 30px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            border: 3px solid rgba(255, 255, 255, 0.3);
            max-width: 500px;
            width: 90%;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .game-over-content h1 {
            color: #fff;
            font-size: 36px;
            font-family: 'Press Start 2P', monospace;
            margin-bottom: 20px;
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.5);
        }

        .game-over-stats {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
        }

        .game-over-stat {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            color: #fff;
            font-size: 14px;
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
            font-size: 20px;
            font-weight: 700;
            margin: 15px 0;
            animation: glow 1s ease-in-out infinite;
        }

        @keyframes glow {
            0%, 100% { text-shadow: 0 0 10px rgba(255, 215, 0, 0.5); }
            50% { text-shadow: 0 0 20px rgba(255, 215, 0, 1); }
        }

        /* Name input */
        .name-input-container {
            margin: 15px 0;
            display: none;
        }

        .name-input-container.show {
            display: block;
        }

        .name-input-container label {
            color: #ffd700;
            font-size: 14px;
            display: block;
            margin-bottom: 8px;
        }

        .name-input-container input {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 215, 0, 0.5);
            border-radius: 8px;
            padding: 10px 15px;
            color: #fff;
            font-size: 16px;
            width: 100%;
            max-width: 200px;
            text-align: center;
            font-family: 'Press Start 2P', monospace;
        }

        .name-input-container input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        /* Leaderboard */
        .leaderboard-box {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            max-height: 200px;
            overflow-y: auto;
        }

        .leaderboard-box h3 {
            color: #ffd700;
            font-size: 14px;
            margin-bottom: 10px;
            text-align: center;
        }

        .leaderboard-entry {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px;
            margin: 4px 0;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 5px;
            color: #fff;
            font-size: 12px;
        }

        .leaderboard-entry.highlight {
            background: rgba(255, 215, 0, 0.3);
            border: 1px solid rgba(255, 215, 0, 0.5);
        }

        .leaderboard-rank {
            font-family: 'Press Start 2P', monospace;
            color: #ffd700;
            width: 30px;
        }

        .leaderboard-name {
            flex: 1;
            text-align: left;
            padding: 0 10px;
        }

        .leaderboard-score {
            font-family: 'Press Start 2P', monospace;
            color: #00f0f0;
        }

        .btn {
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            border: 2px solid rgba(255, 255, 255, 0.5);
            padding: 12px 25px;
            font-size: 16px;
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

        /* Line clear flash */
        .line-clear-flash {
            position: absolute;
            left: 0;
            width: 100%;
            height: 30px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.8), transparent);
            pointer-events: none;
            animation: flashLine 0.3s ease-out forwards;
        }

        @keyframes flashLine {
            0% { opacity: 1; transform: scaleY(1); }
            100% { opacity: 0; transform: scaleY(2); }
        }

        /* Score popup */
        .score-popup {
            position: absolute;
            color: #ffd700;
            font-family: 'Press Start 2P', monospace;
            font-size: 14px;
            pointer-events: none;
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.8);
            animation: scoreFloat 1s ease-out forwards;
        }

        @keyframes scoreFloat {
            0% { opacity: 1; transform: translateY(0) scale(1); }
            100% { opacity: 0; transform: translateY(-50px) scale(1.5); }
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .main-wrapper {
                flex-direction: column;
                align-items: center;
            }

            .live-leaderboard {
                width: 100%;
                max-width: 600px;
                max-height: 200px;
            }

            .live-leaderboard-list {
                flex-direction: row;
                flex-wrap: wrap;
                gap: 8px;
            }

            .live-lb-entry {
                flex: 1;
                min-width: 180px;
            }
        }

        @media (max-width: 768px) {
            .game-container {
                flex-direction: column;
                padding: 15px;
                gap: 15px;
            }

            .right-panel {
                min-width: 100%;
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
            }

            .right-panel > div {
                flex: 1;
                min-width: 140px;
            }

            .live-leaderboard {
                padding: 15px;
            }

            #tetris-canvas {
                width: 250px;
                height: 500px;
            }
        }
    </style>
</head>
<body>
    <!-- Level Indicator -->
    <div class="level-indicator" id="level-indicator">
        <span class="level-badge">LV <span id="level-display">1</span></span>
        <span class="difficulty-label diff-easy" id="difficulty-label">EASY</span>
    </div>

    <!-- Floating Orbs Background -->
    <div class="orbs-container" id="orbs-container">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
        <div class="orb orb-4"></div>
        <div class="orb orb-5"></div>
    </div>
    <div class="grid-overlay"></div>

    <div class="main-wrapper">
        <!-- Live Leaderboard -->
        <div class="live-leaderboard">
            <h2>🏆 Top Scores</h2>
            <div class="live-leaderboard-list" id="live-leaderboard-list">
                <div class="live-lb-empty">ยังไม่มีข้อมูล</div>
            </div>
        </div>

        <div class="game-container" id="game-container">
            <div class="left-panel">
                <div class="game-board">
                    <canvas id="tetris-canvas" width="300" height="600"></canvas>
                    <div id="particle-container"></div>
                </div>
            <div class="controls-info">
                <h3>🎮 การควบคุม</h3>
                <div class="control-row">
                    <span>ซ้าย/ขวา:</span>
                    <span><span class="key">←</span> <span class="key">→</span></span>
                </div>
                <div class="control-row">
                    <span>หมุน:</span>
                    <span><span class="key">↑</span> / <span class="key">Z</span></span>
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
                    <span>เก็บชิ้น:</span>
                    <span><span class="key">C</span> / <span class="key">SHIFT</span></span>
                </div>
                <div class="control-row">
                    <span>หยุดชั่วคราว:</span>
                    <span><span class="key">P</span> / <span class="key">ESC</span></span>
                </div>
                <div class="sound-control">
                    <button class="sound-btn" id="sound-toggle" title="เปิด/ปิดเสียง">🔊</button>
                    <button class="sound-btn" id="music-toggle" title="เปิด/ปิดเพลง">🎵</button>
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
                <div class="combo-display" id="combo-display">
                    <span class="combo-text" id="combo-text">COMBO x2</span>
                </div>
            </div>

            <div class="hold-piece-box" id="hold-box">
                <h2>📦 เก็บไว้ (C)</h2>
                <canvas id="hold-canvas" width="100" height="100"></canvas>
            </div>

            <div class="next-piece-box">
                <h2>⏭️ ชิ้นถัดไป</h2>
                <canvas id="next-canvas" width="100" height="100"></canvas>
            </div>

            <div class="high-score-box">
                <h2>🏆 สถิติสูงสุด</h2>
                <div class="high-score-value" id="high-score">0</div>
            </div>
        </div>
    </div>
    </div> <!-- Close main-wrapper -->

    <!-- Pause Overlay -->
    <div id="pause-overlay">
        <div class="pause-content">
            <h2>PAUSE</h2>
            <p>กด P หรือ ESC เพื่อเล่นต่อ</p>
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
                    <span class="label">ความยาก:</span>
                    <span class="value" id="final-difficulty">EASY</span>
                </div>
                <div class="game-over-stat">
                    <span class="label">เส้นทั้งหมด:</span>
                    <span class="value" id="final-lines">0</span>
                </div>
                <div class="game-over-stat">
                    <span class="label">Combo สูงสุด:</span>
                    <span class="value" id="final-combo">0</span>
                </div>
                <div class="game-over-stat">
                    <span class="label">แถวขยะที่รอด:</span>
                    <span class="value" id="final-garbage">0</span>
                </div>
            </div>
            <div id="new-high-score-message" class="new-high-score" style="display: none;">
                🎉 สถิติใหม่! 🎉
            </div>
            <div class="name-input-container" id="name-input-container">
                <label>ใส่ชื่อของคุณ:</label>
                <input type="text" id="player-name" maxlength="10" placeholder="ชื่อ">
            </div>
            <div class="leaderboard-box" id="leaderboard-box">
                <h3>🏅 อันดับ 10 สูงสุด</h3>
                <div id="leaderboard-list"></div>
            </div>
            <button class="btn btn-primary" onclick="restartGame()">เล่นอีกครั้ง</button>
            <button class="btn" onclick="location.href='/'">กลับหน้าหลัก</button>
        </div>
    </div>

    <script>
        // ============================================
        // 🔊 SOUND SYSTEM (Web Audio API)
        // ============================================
        class SoundManager {
            constructor() {
                this.audioContext = null;
                this.isMuted = false;
                this.isMusicMuted = false;
                this.musicOscillator = null;
                this.musicGain = null;
                this.initialized = false;
            }

            // เริ่มต้น Audio Context (ต้องเรียกหลัง user interaction)
            init() {
                if (this.initialized) return;
                try {
                    this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
                    this.initialized = true;
                } catch (e) {
                    console.warn('Web Audio API ไม่รองรับ:', e);
                }
            }

            // สร้างเสียง beep พื้นฐาน
            playTone(frequency, duration, type = 'square', volume = 0.3) {
                if (!this.audioContext || this.isMuted) return;

                const oscillator = this.audioContext.createOscillator();
                const gainNode = this.audioContext.createGain();

                oscillator.connect(gainNode);
                gainNode.connect(this.audioContext.destination);

                oscillator.frequency.value = frequency;
                oscillator.type = type;

                gainNode.gain.setValueAtTime(volume, this.audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + duration);

                oscillator.start(this.audioContext.currentTime);
                oscillator.stop(this.audioContext.currentTime + duration);
            }

            // เสียงเลื่อนชิ้นส่วน
            playMove() {
                this.playTone(200, 0.05, 'sine', 0.1);
            }

            // เสียงหมุน
            playRotate() {
                this.playTone(400, 0.1, 'sine', 0.2);
            }

            // เสียงวางชิ้นส่วน (lock)
            playLock() {
                this.playTone(150, 0.15, 'triangle', 0.3);
            }

            // เสียง Hard Drop
            playHardDrop() {
                this.playTone(100, 0.2, 'sawtooth', 0.4);
                setTimeout(() => this.playTone(80, 0.1, 'square', 0.3), 50);
            }

            // เสียงล้างแถว (ต่างกันตามจำนวนแถว)
            playClearLine(lineCount) {
                if (!this.audioContext || this.isMuted) return;

                const baseFreq = 300;
                for (let i = 0; i < lineCount; i++) {
                    setTimeout(() => {
                        this.playTone(baseFreq + (i * 100), 0.15, 'sine', 0.3);
                    }, i * 80);
                }

                // Tetris! (4 แถว)
                if (lineCount === 4) {
                    setTimeout(() => {
                        this.playTone(600, 0.1, 'square', 0.4);
                        setTimeout(() => this.playTone(800, 0.1, 'square', 0.4), 100);
                        setTimeout(() => this.playTone(1000, 0.2, 'square', 0.5), 200);
                    }, 300);
                }
            }

            // เสียง Combo
            playCombo(comboCount) {
                if (!this.audioContext || this.isMuted) return;
                const freq = 400 + (comboCount * 50);
                this.playTone(freq, 0.1, 'sine', 0.3);
                setTimeout(() => this.playTone(freq + 100, 0.15, 'sine', 0.3), 100);
            }

            // เสียง Level Up
            playLevelUp() {
                if (!this.audioContext || this.isMuted) return;

                const notes = [523, 659, 784, 1047]; // C5, E5, G5, C6
                notes.forEach((freq, i) => {
                    setTimeout(() => {
                        this.playTone(freq, 0.2, 'sine', 0.3);
                    }, i * 100);
                });
            }

            // เสียง Hold
            playHold() {
                this.playTone(350, 0.1, 'sine', 0.2);
                setTimeout(() => this.playTone(450, 0.1, 'sine', 0.2), 80);
            }

            // เสียง Game Over
            playGameOver() {
                if (!this.audioContext || this.isMuted) return;

                const notes = [400, 350, 300, 250, 200];
                notes.forEach((freq, i) => {
                    setTimeout(() => {
                        this.playTone(freq, 0.3, 'sawtooth', 0.3);
                    }, i * 150);
                });
            }

            // เสียง High Score ใหม่
            playHighScore() {
                if (!this.audioContext || this.isMuted) return;

                const melody = [523, 659, 784, 880, 1047, 880, 784, 1047];
                melody.forEach((freq, i) => {
                    setTimeout(() => {
                        this.playTone(freq, 0.15, 'sine', 0.3);
                    }, i * 100);
                });
            }

            // เพลงพื้นหลัง (simple loop)
            startMusic() {
                if (!this.audioContext || this.isMusicMuted) return;
                this.stopMusic();

                // สร้าง pattern เพลงง่ายๆ
                this.playMusicLoop();
            }

            playMusicLoop() {
                if (this.isMusicMuted || !this.audioContext) return;

                const bassNotes = [130.81, 146.83, 164.81, 174.61]; // C3, D3, E3, F3
                let noteIndex = 0;

                const playNote = () => {
                    if (this.isMusicMuted) return;

                    const osc = this.audioContext.createOscillator();
                    const gain = this.audioContext.createGain();

                    osc.connect(gain);
                    gain.connect(this.audioContext.destination);

                    osc.frequency.value = bassNotes[noteIndex % bassNotes.length];
                    osc.type = 'sine';

                    gain.gain.setValueAtTime(0.1, this.audioContext.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + 0.4);

                    osc.start();
                    osc.stop(this.audioContext.currentTime + 0.4);

                    noteIndex++;
                    this.musicTimeout = setTimeout(playNote, 500);
                };

                playNote();
            }

            stopMusic() {
                if (this.musicTimeout) {
                    clearTimeout(this.musicTimeout);
                    this.musicTimeout = null;
                }
            }

            toggleMute() {
                this.isMuted = !this.isMuted;
                return this.isMuted;
            }

            toggleMusic() {
                this.isMusicMuted = !this.isMusicMuted;
                if (this.isMusicMuted) {
                    this.stopMusic();
                } else {
                    this.startMusic();
                }
                return this.isMusicMuted;
            }
        }

        // ============================================
        // 🎮 GAME CONFIGURATION
        // ============================================
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

        // ============================================
        // 🎯 GAME STATE
        // ============================================
        let canvas, ctx, nextCanvas, nextCtx, holdCanvas, holdCtx;
        let board = [];
        let currentPiece = null;
        let nextPiece = null;
        let holdPiece = null;
        let canHold = true;
        let score = 0;
        let level = 1;
        let lines = 0;
        let combo = 0;
        let maxCombo = 0;
        let gameOver = false;
        let isPaused = false;
        let dropCounter = 0;
        let dropInterval = 1000;
        let lastTime = 0;
        let highScore = 0;
        let leaderboard = [];

        // ============================================
        // 🚧 OBSTACLE SYSTEM
        // ============================================
        let obstacleCounter = 0;
        let obstacleInterval = 30000; // 30 วินาทีเริ่มต้น
        let garbageRows = 0;
        let currentTheme = 'calm';

        // ระดับความยาก
        const DIFFICULTY_LEVELS = {
            1: { name: 'EASY', theme: 'calm', class: 'diff-easy', obstacleChance: 0, garbageHoles: 3 },
            5: { name: 'NORMAL', theme: 'calm', class: 'diff-normal', obstacleChance: 0.1, garbageHoles: 2 },
            10: { name: 'HARD', theme: 'warm', class: 'diff-hard', obstacleChance: 0.2, garbageHoles: 2 },
            15: { name: 'EXPERT', theme: 'danger', class: 'diff-expert', obstacleChance: 0.3, garbageHoles: 1 },
            20: { name: 'NIGHTMARE', theme: 'extreme', class: 'diff-nightmare', obstacleChance: 0.4, garbageHoles: 1 },
            25: { name: 'HELL', theme: 'nightmare', class: 'diff-nightmare', obstacleChance: 0.5, garbageHoles: 1 }
        };

        // สีของ obstacle blocks
        const OBSTACLE_COLOR = '#4a4a4a';
        const OBSTACLE_HIGHLIGHT = '#666666';

        // Sound Manager
        const soundManager = new SoundManager();

        // ============================================
        // 🚀 INITIALIZE GAME
        // ============================================
        function init() {
            canvas = document.getElementById('tetris-canvas');
            ctx = canvas.getContext('2d');
            nextCanvas = document.getElementById('next-canvas');
            nextCtx = nextCanvas.getContext('2d');
            holdCanvas = document.getElementById('hold-canvas');
            holdCtx = holdCanvas.getContext('2d');

            // โหลด high score และ leaderboard
            highScore = parseInt(localStorage.getItem('tetris-high-score')) || 0;
            document.getElementById('high-score').textContent = highScore;
            loadLeaderboard();
            renderLiveLeaderboard();

            // ตั้งค่า theme เริ่มต้น
            updateTheme(1);

            // สร้างบอร์ดเปล่า
            board = Array(ROWS).fill(null).map(() => Array(COLS).fill(0));

            // สร้างชิ้นแรก
            nextPiece = generatePiece();
            spawnPiece();

            // เริ่ม game loop
            requestAnimationFrame(update);

            // Keyboard controls
            document.addEventListener('keydown', handleKeyPress);

            // Sound controls
            document.getElementById('sound-toggle').addEventListener('click', () => {
                soundManager.init();
                const muted = soundManager.toggleMute();
                document.getElementById('sound-toggle').textContent = muted ? '🔇' : '🔊';
                document.getElementById('sound-toggle').classList.toggle('muted', muted);
            });

            document.getElementById('music-toggle').addEventListener('click', () => {
                soundManager.init();
                const muted = soundManager.toggleMusic();
                document.getElementById('music-toggle').textContent = muted ? '🎵' : '🎶';
                document.getElementById('music-toggle').classList.toggle('muted', muted);
            });

            // First interaction to enable audio
            document.addEventListener('click', () => soundManager.init(), { once: true });
            document.addEventListener('keydown', () => soundManager.init(), { once: true });

            // Name input handler
            document.getElementById('player-name').addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    saveScore();
                }
            });
        }

        // ============================================
        // 📦 LEADERBOARD SYSTEM
        // ============================================
        function loadLeaderboard() {
            const saved = localStorage.getItem('tetris-leaderboard');
            leaderboard = saved ? JSON.parse(saved) : [];
        }

        function saveLeaderboard() {
            localStorage.setItem('tetris-leaderboard', JSON.stringify(leaderboard));
        }

        // ============================================
        // 🎨 THEME & DIFFICULTY SYSTEM
        // ============================================
        function getDifficultyForLevel(lvl) {
            // หาระดับความยากที่เหมาะกับเลเวลปัจจุบัน
            let difficulty = DIFFICULTY_LEVELS[1];
            for (const key of Object.keys(DIFFICULTY_LEVELS).map(Number).sort((a, b) => b - a)) {
                if (lvl >= key) {
                    difficulty = DIFFICULTY_LEVELS[key];
                    break;
                }
            }
            return difficulty;
        }

        function updateTheme(lvl) {
            const difficulty = getDifficultyForLevel(lvl);

            // อัพเดท theme ของ body
            document.body.classList.remove('theme-calm', 'theme-warm', 'theme-danger', 'theme-extreme', 'theme-nightmare');
            document.body.classList.add('theme-' + difficulty.theme);
            currentTheme = difficulty.theme;

            // อัพเดท level indicator
            document.getElementById('level-display').textContent = lvl;
            const diffLabel = document.getElementById('difficulty-label');
            diffLabel.textContent = difficulty.name;
            diffLabel.className = 'difficulty-label ' + difficulty.class;

            // ปรับ obstacle interval ตามเลเวล (ยิ่งสูงยิ่งถี่)
            obstacleInterval = Math.max(5000, 30000 - (lvl - 1) * 1000);
        }

        // ============================================
        // 🚧 OBSTACLE FUNCTIONS
        // ============================================
        function addGarbageRows(count = 1) {
            const difficulty = getDifficultyForLevel(level);
            const holes = difficulty.garbageHoles;

            soundManager.playTone(100, 0.3, 'sawtooth', 0.4);
            shakeScreen();

            for (let i = 0; i < count; i++) {
                // สร้างแถวขยะพร้อมช่องว่าง
                const garbageRow = Array(COLS).fill(OBSTACLE_COLOR);

                // สร้างช่องว่างแบบสุ่ม
                const holePositions = [];
                while (holePositions.length < holes) {
                    const pos = Math.floor(Math.random() * COLS);
                    if (!holePositions.includes(pos)) {
                        holePositions.push(pos);
                        garbageRow[pos] = 0;
                    }
                }

                // เลื่อนบอร์ดขึ้น
                board.shift();
                board.push(garbageRow);
            }

            // แสดง warning effect
            showGarbageWarning();
            garbageRows += count;
        }

        function showGarbageWarning() {
            const gameBoard = document.querySelector('.game-board');
            const warning = document.createElement('div');
            warning.className = 'obstacle-warning';
            gameBoard.appendChild(warning);
            setTimeout(() => warning.remove(), 500);
        }

        function checkForRandomObstacles() {
            const difficulty = getDifficultyForLevel(level);

            // โอกาสเกิด obstacle หลังวางชิ้นส่วน
            if (Math.random() < difficulty.obstacleChance) {
                // หน่วงเวลาสักครู่ก่อนเพิ่มขยะ
                setTimeout(() => {
                    if (!gameOver && !isPaused) {
                        const rowsToAdd = level >= 20 ? 2 : 1;
                        addGarbageRows(rowsToAdd);
                    }
                }, 300);
            }
        }

        function spawnRandomBlock() {
            // เพิ่ม block แบบสุ่มบนบอร์ด (สำหรับเลเวลสูง)
            if (level >= 15) {
                const chance = 0.05 + (level - 15) * 0.01;
                if (Math.random() < chance) {
                    const col = Math.floor(Math.random() * COLS);
                    const row = Math.floor(Math.random() * 5) + ROWS - 6; // ล่างสุด 6 แถว

                    if (board[row][col] === 0) {
                        board[row][col] = OBSTACLE_COLOR;
                    }
                }
            }
        }

        // แสดง Live Leaderboard (ด้านข้าง)
        function renderLiveLeaderboard() {
            const list = document.getElementById('live-leaderboard-list');
            list.innerHTML = '';

            if (leaderboard.length === 0) {
                list.innerHTML = '<div class="live-lb-empty">ยังไม่มีข้อมูล<br>เล่นเพื่อบันทึกคะแนน!</div>';
                return;
            }

            leaderboard.slice(0, 10).forEach((entry, index) => {
                const div = document.createElement('div');
                div.className = 'live-lb-entry';
                if (index === 0) div.classList.add('top-1');
                if (index === 1) div.classList.add('top-2');
                if (index === 2) div.classList.add('top-3');

                const medal = index === 0 ? '🥇' : index === 1 ? '🥈' : index === 2 ? '🥉' : `#${index + 1}`;

                div.innerHTML = `
                    <div class="live-lb-rank">${medal}</div>
                    <div class="live-lb-info">
                        <div class="live-lb-name">${entry.name || 'ผู้เล่น'}</div>
                        <div class="live-lb-date">LV.${entry.level || 1} | ${entry.date || ''}</div>
                    </div>
                    <div class="live-lb-score">${entry.score.toLocaleString()}</div>
                `;
                list.appendChild(div);
            });
        }

        function addToLeaderboard(name, playerScore) {
            leaderboard.push({
                name: name || 'ผู้เล่น',
                score: playerScore,
                level: level,
                lines: lines,
                date: new Date().toLocaleDateString('th-TH')
            });

            // เรียงลำดับและเก็บแค่ 10 อันดับ
            leaderboard.sort((a, b) => b.score - a.score);
            leaderboard = leaderboard.slice(0, 10);
            saveLeaderboard();
        }

        function renderLeaderboard(highlightScore = null) {
            const list = document.getElementById('leaderboard-list');
            list.innerHTML = '';

            if (leaderboard.length === 0) {
                list.innerHTML = '<p style="color: rgba(255,255,255,0.5); text-align: center;">ยังไม่มีข้อมูล</p>';
                return;
            }

            leaderboard.forEach((entry, index) => {
                const div = document.createElement('div');
                div.className = 'leaderboard-entry';
                if (highlightScore && entry.score === highlightScore) {
                    div.classList.add('highlight');
                }
                div.innerHTML = `
                    <span class="leaderboard-rank">#${index + 1}</span>
                    <span class="leaderboard-name">${entry.name} <small style="color:#888;">LV.${entry.level || 1}</small></span>
                    <span class="leaderboard-score">${entry.score.toLocaleString()}</span>
                `;
                list.appendChild(div);
            });
        }

        function saveScore() {
            const nameInput = document.getElementById('player-name');
            const name = nameInput.value.trim() || 'ผู้เล่น';
            addToLeaderboard(name, score);
            renderLeaderboard(score);
            renderLiveLeaderboard();
            document.getElementById('name-input-container').classList.remove('show');
        }

        // ============================================
        // 🧩 PIECE MANAGEMENT
        // ============================================
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

        function spawnPiece() {
            currentPiece = nextPiece;
            nextPiece = generatePiece();
            canHold = true;

            // ตรวจสอบ spawn position
            if (!isValidMove(currentPiece.x, currentPiece.y, currentPiece.shape)) {
                endGame();
            }

            drawNextPiece();
        }

        // Hold Piece
        function holdCurrentPiece() {
            if (!canHold) return;

            soundManager.playHold();
            canHold = false;

            if (holdPiece === null) {
                holdPiece = {
                    type: currentPiece.type,
                    shape: SHAPES[currentPiece.type],
                    color: COLORS[currentPiece.type]
                };
                spawnPiece();
            } else {
                const temp = {
                    type: currentPiece.type,
                    shape: SHAPES[currentPiece.type],
                    color: COLORS[currentPiece.type]
                };
                currentPiece = {
                    type: holdPiece.type,
                    shape: holdPiece.shape,
                    color: holdPiece.color,
                    x: Math.floor(COLS / 2) - Math.floor(holdPiece.shape[0].length / 2),
                    y: 0
                };
                holdPiece = temp;
            }

            drawHoldPiece();
            updateHoldBoxStyle();
        }

        function updateHoldBoxStyle() {
            const holdBox = document.getElementById('hold-box');
            holdBox.classList.toggle('used', !canHold);
        }

        // ============================================
        // 🎯 COLLISION DETECTION
        // ============================================
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

        // หมุนชิ้นส่วน
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

        // Wall kick - พยายามหมุนแม้ชนกำแพง
        function tryRotate() {
            const rotated = rotate(currentPiece.shape);
            const kicks = [0, -1, 1, -2, 2]; // ลองขยับซ้าย-ขวา

            for (const kick of kicks) {
                if (isValidMove(currentPiece.x + kick, currentPiece.y, rotated)) {
                    currentPiece.shape = rotated;
                    currentPiece.x += kick;
                    soundManager.playRotate();
                    return true;
                }
            }
            return false;
        }

        // ============================================
        // 📍 PIECE MOVEMENT
        // ============================================
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

        // ล้างแถว
        function clearLines() {
            let linesCleared = 0;
            const clearedRows = [];

            for (let row = ROWS - 1; row >= 0; row--) {
                if (board[row].every(cell => cell !== 0)) {
                    clearedRows.push(row);
                    board.splice(row, 1);
                    board.unshift(Array(COLS).fill(0));
                    linesCleared++;
                    row++;
                }
            }

            if (linesCleared > 0) {
                lines += linesCleared;
                combo++;
                if (combo > maxCombo) maxCombo = combo;

                // คำนวณคะแนน (รวม combo bonus)
                const basePoints = [0, 100, 300, 500, 800];
                let earnedPoints = basePoints[linesCleared] * level;

                // Combo bonus
                if (combo > 1) {
                    earnedPoints += 50 * combo * level;
                    showCombo(combo);
                    soundManager.playCombo(combo);
                }

                score += earnedPoints;

                // แสดง score popup
                showScorePopup(earnedPoints, clearedRows[0]);

                // Level up ทุก 10 เส้น
                const newLevel = Math.floor(lines / 10) + 1;
                if (newLevel > level) {
                    level = newLevel;
                    dropInterval = Math.max(50, 1000 - (level - 1) * 50); // เร็วขึ้นตามเลเวล
                    soundManager.playLevelUp();
                    updateTheme(level); // อัพเดทธีมตามเลเวล
                }

                // Visual effects
                createParticles(clearedRows);
                soundManager.playClearLine(linesCleared);

                updateStats();
            } else {
                // Reset combo ถ้าไม่ได้ล้างแถว
                combo = 0;
                hideCombo();
            }
        }

        // เลื่อนลง
        function moveDown() {
            if (isValidMove(currentPiece.x, currentPiece.y + 1, currentPiece.shape)) {
                currentPiece.y++;
                return true;
            } else {
                soundManager.playLock();
                mergePiece();
                clearLines();

                // เช็คโอกาสเกิดอุปสรรคแบบสุ่ม (ตามระดับความยาก)
                checkForRandomObstacles();
                spawnRandomBlock();

                spawnPiece();
                return false;
            }
        }

        // Hard drop
        function hardDrop() {
            let dropDistance = 0;
            while (isValidMove(currentPiece.x, currentPiece.y + 1, currentPiece.shape)) {
                currentPiece.y++;
                dropDistance++;
            }

            // Bonus score สำหรับ hard drop
            score += dropDistance * 2;
            updateStats();

            soundManager.playHardDrop();
            shakeScreen();

            mergePiece();
            clearLines();

            // เช็คโอกาสเกิดอุปสรรคแบบสุ่ม
            checkForRandomObstacles();
            spawnRandomBlock();

            spawnPiece();
        }

        // ============================================
        // 🎨 VISUAL EFFECTS
        // ============================================
        function createParticles(rows) {
            const container = document.getElementById('particle-container');

            rows.forEach(row => {
                for (let i = 0; i < 20; i++) {
                    const particle = document.createElement('div');
                    particle.className = 'particle';
                    particle.style.left = Math.random() * 300 + 'px';
                    particle.style.top = (row * BLOCK_SIZE) + 'px';
                    particle.style.backgroundColor = `hsl(${Math.random() * 360}, 80%, 60%)`;
                    container.appendChild(particle);

                    setTimeout(() => particle.remove(), 800);
                }

                // Line flash effect
                const flash = document.createElement('div');
                flash.className = 'line-clear-flash';
                flash.style.top = (row * BLOCK_SIZE) + 'px';
                container.appendChild(flash);
                setTimeout(() => flash.remove(), 300);
            });
        }

        function showScorePopup(points, row) {
            const container = document.getElementById('particle-container');
            const popup = document.createElement('div');
            popup.className = 'score-popup';
            popup.textContent = '+' + points.toLocaleString();
            popup.style.left = '150px';
            popup.style.top = (row * BLOCK_SIZE) + 'px';
            container.appendChild(popup);
            setTimeout(() => popup.remove(), 1000);
        }

        function shakeScreen() {
            const container = document.getElementById('game-container');
            container.classList.add('shake');
            setTimeout(() => container.classList.remove('shake'), 300);
        }

        function showCombo(count) {
            const display = document.getElementById('combo-display');
            const text = document.getElementById('combo-text');
            text.textContent = `COMBO x${count}`;
            display.classList.add('active');
        }

        function hideCombo() {
            document.getElementById('combo-display').classList.remove('active');
        }

        // ============================================
        // ⌨️ KEYBOARD CONTROLS
        // ============================================
        function handleKeyPress(e) {
            if (gameOver) return;

            // Pause controls
            if (e.key === 'p' || e.key === 'P' || e.key === 'Escape') {
                togglePause();
                return;
            }

            if (isPaused) return;

            switch (e.key) {
                case 'ArrowLeft':
                    if (isValidMove(currentPiece.x - 1, currentPiece.y, currentPiece.shape)) {
                        currentPiece.x--;
                        soundManager.playMove();
                    }
                    break;

                case 'ArrowRight':
                    if (isValidMove(currentPiece.x + 1, currentPiece.y, currentPiece.shape)) {
                        currentPiece.x++;
                        soundManager.playMove();
                    }
                    break;

                case 'ArrowDown':
                    moveDown();
                    score += 1; // Soft drop bonus
                    updateStats();
                    break;

                case 'ArrowUp':
                case 'z':
                case 'Z':
                    tryRotate();
                    break;

                case ' ':
                    e.preventDefault();
                    hardDrop();
                    break;

                case 'c':
                case 'C':
                case 'Shift':
                    holdCurrentPiece();
                    break;
            }

            draw();
        }

        // Toggle pause
        function togglePause() {
            isPaused = !isPaused;
            document.getElementById('pause-overlay').style.display = isPaused ? 'flex' : 'none';

            if (isPaused) {
                soundManager.stopMusic();
            } else {
                if (!soundManager.isMusicMuted) {
                    soundManager.startMusic();
                }
            }
        }

        // ============================================
        // 📊 UI UPDATES
        // ============================================
        function updateStats() {
            document.getElementById('score').textContent = score.toLocaleString();
            document.getElementById('level').textContent = level;
            document.getElementById('lines').textContent = lines;

            // Update high score
            if (score > highScore) {
                highScore = score;
                localStorage.setItem('tetris-high-score', highScore);
                document.getElementById('high-score').textContent = highScore.toLocaleString();
            }
        }

        // ============================================
        // 🎨 DRAWING FUNCTIONS
        // ============================================
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

            // Draw ghost piece
            if (currentPiece) {
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

                // Draw current piece
                for (let row = 0; row < currentPiece.shape.length; row++) {
                    for (let col = 0; col < currentPiece.shape[row].length; col++) {
                        if (currentPiece.shape[row][col]) {
                            const x = (currentPiece.x + col) * BLOCK_SIZE;
                            const y = (currentPiece.y + row) * BLOCK_SIZE;
                            drawBlock(ctx, x, y, currentPiece.color);
                        }
                    }
                }
            }
        }

        function drawBlock(context, x, y, color) {
            const size = context === ctx ? BLOCK_SIZE : 20;

            // Main block
            context.fillStyle = color;
            context.fillRect(x, y, size, size);

            // Gradient overlay
            const gradient = context.createLinearGradient(x, y, x + size, y + size);
            gradient.addColorStop(0, 'rgba(255, 255, 255, 0.4)');
            gradient.addColorStop(1, 'rgba(0, 0, 0, 0.4)');
            context.fillStyle = gradient;
            context.fillRect(x, y, size, size);

            // Inner highlight
            context.fillStyle = 'rgba(255, 255, 255, 0.2)';
            context.fillRect(x + 2, y + 2, size - 4, size / 3);

            // Border
            context.strokeStyle = 'rgba(255, 255, 255, 0.6)';
            context.lineWidth = 2;
            context.strokeRect(x + 1, y + 1, size - 2, size - 2);
        }

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

        function drawHoldPiece() {
            holdCtx.fillStyle = 'rgba(0, 0, 0, 0.3)';
            holdCtx.fillRect(0, 0, holdCanvas.width, holdCanvas.height);

            if (holdPiece) {
                const blockSize = 20;
                const offsetX = (holdCanvas.width - holdPiece.shape[0].length * blockSize) / 2;
                const offsetY = (holdCanvas.height - holdPiece.shape.length * blockSize) / 2;

                for (let row = 0; row < holdPiece.shape.length; row++) {
                    for (let col = 0; col < holdPiece.shape[row].length; col++) {
                        if (holdPiece.shape[row][col]) {
                            const x = offsetX + col * blockSize;
                            const y = offsetY + row * blockSize;
                            drawBlock(holdCtx, x, y, holdPiece.color);
                        }
                    }
                }
            }
        }

        // ============================================
        // 🔄 GAME LOOP
        // ============================================
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

        // ============================================
        // 🏁 GAME END
        // ============================================
        function endGame() {
            gameOver = true;
            soundManager.stopMusic();
            soundManager.playGameOver();

            document.getElementById('final-score').textContent = score.toLocaleString();
            document.getElementById('final-level').textContent = level;
            document.getElementById('final-difficulty').textContent = getDifficultyForLevel(level).name;
            document.getElementById('final-lines').textContent = lines;
            document.getElementById('final-combo').textContent = maxCombo;
            document.getElementById('final-garbage').textContent = garbageRows;

            // Check for new high score
            const isNewHighScore = score > parseInt(localStorage.getItem('tetris-high-score') || 0);
            if (isNewHighScore) {
                document.getElementById('new-high-score-message').style.display = 'block';
                document.getElementById('name-input-container').classList.add('show');
                soundManager.playHighScore();
            }

            // Check if qualifies for leaderboard
            const qualifiesForLeaderboard = leaderboard.length < 10 ||
                (leaderboard.length > 0 && score > leaderboard[leaderboard.length - 1].score);

            if (qualifiesForLeaderboard && !isNewHighScore) {
                document.getElementById('name-input-container').classList.add('show');
            }

            renderLeaderboard();
            document.getElementById('game-over-overlay').style.display = 'flex';
        }

        // Restart game
        function restartGame() {
            // Save score if name entered
            const nameInput = document.getElementById('player-name');
            if (nameInput.value.trim()) {
                saveScore();
            }

            // Reset game state
            board = Array(ROWS).fill(null).map(() => Array(COLS).fill(0));
            score = 0;
            level = 1;
            lines = 0;
            combo = 0;
            maxCombo = 0;
            gameOver = false;
            isPaused = false;
            dropCounter = 0;
            dropInterval = 1000;
            holdPiece = null;
            canHold = true;

            // Reset obstacle counters
            obstacleCounter = 0;
            garbageRows = 0;

            // Reset theme to initial
            updateTheme(1);

            // Hide overlays
            document.getElementById('game-over-overlay').style.display = 'none';
            document.getElementById('new-high-score-message').style.display = 'none';
            document.getElementById('name-input-container').classList.remove('show');
            nameInput.value = '';
            hideCombo();

            // Update UI
            updateStats();
            drawHoldPiece();
            updateHoldBoxStyle();

            // Generate new pieces
            nextPiece = generatePiece();
            spawnPiece();

            // Restart game loop
            lastTime = 0;
            requestAnimationFrame(update);

            // Restart music if enabled
            if (!soundManager.isMusicMuted) {
                soundManager.startMusic();
            }
        }

        // ============================================
        // 🎬 START GAME
        // ============================================
        window.addEventListener('load', init);
    </script>
</body>
</html>
