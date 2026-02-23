<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>XmanApp — Facebook App Review Demo | Permission Usage Guide</title>
    <meta name="description" content="XmanApp uses Facebook permissions to power an AI fortune telling bot on Messenger, providing automated readings and comment engagement.">
    <meta property="og:title" content="XmanApp — Facebook Permission Usage Demo">
    <meta property="og:description" content="How XmanApp uses Facebook permissions for AI fortune telling via Messenger">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://main.thaiprompt.online/facebook-app-review-demo">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --bg-deep: #06070d;
            --bg-card: rgba(255,255,255,0.04);
            --bg-card-hover: rgba(255,255,255,0.07);
            --border: rgba(255,255,255,0.08);
            --border-active: rgba(99,102,241,0.5);
            --text-primary: #e8eaf0;
            --text-secondary: #8b8fa3;
            --text-muted: #5c6079;
            --accent-indigo: #6366f1;
            --accent-violet: #8b5cf6;
            --accent-blue: #3b82f6;
            --accent-emerald: #10b981;
            --accent-amber: #f59e0b;
            --accent-rose: #f43f5e;
            --glow-indigo: rgba(99,102,241,0.15);
            --glow-violet: rgba(139,92,246,0.12);
        }

        body {
            font-family: 'DM Sans', -apple-system, sans-serif;
            background: var(--bg-deep);
            color: var(--text-primary);
            line-height: 1.7;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Animated constellation background */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(1.5px 1.5px at 20% 30%, rgba(99,102,241,0.4) 50%, transparent 100%),
                radial-gradient(1px 1px at 40% 70%, rgba(139,92,246,0.3) 50%, transparent 100%),
                radial-gradient(1.2px 1.2px at 60% 20%, rgba(59,130,246,0.35) 50%, transparent 100%),
                radial-gradient(1px 1px at 80% 50%, rgba(139,92,246,0.25) 50%, transparent 100%),
                radial-gradient(1.5px 1.5px at 10% 80%, rgba(99,102,241,0.3) 50%, transparent 100%),
                radial-gradient(1px 1px at 90% 90%, rgba(59,130,246,0.2) 50%, transparent 100%),
                radial-gradient(0.8px 0.8px at 50% 50%, rgba(255,255,255,0.15) 50%, transparent 100%),
                radial-gradient(0.6px 0.6px at 30% 45%, rgba(255,255,255,0.1) 50%, transparent 100%),
                radial-gradient(0.8px 0.8px at 70% 35%, rgba(255,255,255,0.12) 50%, transparent 100%),
                radial-gradient(0.5px 0.5px at 15% 55%, rgba(255,255,255,0.08) 50%, transparent 100%);
            background-size: 250px 250px, 300px 350px, 200px 280px, 350px 200px, 280px 320px, 220px 280px, 180px 200px, 260px 300px, 320px 180px, 240px 260px;
            animation: drift 60s linear infinite;
            pointer-events: none;
            z-index: 0;
        }

        @keyframes drift {
            0% { transform: translateY(0); }
            100% { transform: translateY(-200px); }
        }

        .wrapper { position: relative; z-index: 1; }

        /* ─── Navigation ─── */
        .nav {
            position: sticky; top: 0; z-index: 50;
            background: rgba(6,7,13,0.85);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            padding: 0 24px;
        }
        .nav-inner {
            max-width: 1120px; margin: 0 auto;
            display: flex; align-items: center; justify-content: space-between;
            height: 64px;
        }
        .nav-brand {
            display: flex; align-items: center; gap: 12px;
            font-weight: 700; font-size: 17px; letter-spacing: -0.02em;
            color: var(--text-primary); text-decoration: none;
        }
        .nav-brand-icon {
            width: 36px; height: 36px; border-radius: 10px;
            background: linear-gradient(135deg, var(--accent-indigo), var(--accent-violet));
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; color: white;
        }
        .nav-badge {
            font-family: 'Space Mono', monospace;
            font-size: 11px; font-weight: 400;
            padding: 3px 10px; border-radius: 20px;
            background: var(--glow-indigo);
            border: 1px solid var(--border-active);
            color: var(--accent-indigo);
            letter-spacing: 0.05em;
        }
        .nav-links { display: flex; gap: 8px; }
        .nav-links a {
            font-size: 13px; color: var(--text-secondary);
            text-decoration: none; padding: 6px 14px; border-radius: 8px;
            transition: all 0.2s;
        }
        .nav-links a:hover { color: var(--text-primary); background: var(--bg-card); }

        /* ─── Hero ─── */
        .hero {
            text-align: center;
            padding: 100px 24px 80px;
            position: relative;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: -100px; left: 50%; transform: translateX(-50%);
            width: 600px; height: 600px;
            background: radial-gradient(circle, var(--glow-violet) 0%, transparent 70%);
            pointer-events: none;
        }
        .hero-eyebrow {
            font-family: 'Space Mono', monospace;
            font-size: 12px; letter-spacing: 0.15em; text-transform: uppercase;
            color: var(--accent-indigo);
            margin-bottom: 20px;
        }
        .hero h1 {
            font-size: clamp(32px, 5vw, 56px);
            font-weight: 700; letter-spacing: -0.03em;
            line-height: 1.15;
            background: linear-gradient(135deg, #fff 0%, #c4b5fd 50%, #818cf8 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
            max-width: 720px; margin: 0 auto 24px;
        }
        .hero p {
            font-size: 17px; color: var(--text-secondary);
            max-width: 580px; margin: 0 auto 40px;
            line-height: 1.8;
        }
        .hero-stats {
            display: flex; justify-content: center; gap: 48px;
            flex-wrap: wrap;
        }
        .hero-stat { text-align: center; }
        .hero-stat-value {
            font-family: 'Space Mono', monospace;
            font-size: 28px; font-weight: 700;
            color: var(--text-primary);
        }
        .hero-stat-label {
            font-size: 12px; color: var(--text-muted);
            text-transform: uppercase; letter-spacing: 0.1em;
            margin-top: 4px;
        }

        /* ─── Sections ─── */
        .section {
            max-width: 1120px; margin: 0 auto;
            padding: 0 24px 80px;
        }
        .section-label {
            font-family: 'Space Mono', monospace;
            font-size: 11px; letter-spacing: 0.15em; text-transform: uppercase;
            color: var(--accent-indigo);
            margin-bottom: 12px;
        }
        .section-title {
            font-size: clamp(24px, 3vw, 36px);
            font-weight: 700; letter-spacing: -0.02em;
            margin-bottom: 16px;
        }
        .section-desc {
            font-size: 16px; color: var(--text-secondary);
            max-width: 640px; margin-bottom: 48px;
            line-height: 1.8;
        }

        /* ─── Permission Cards ─── */
        .perm-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 520px), 1fr));
            gap: 24px;
        }
        .perm-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 32px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .perm-card:hover {
            background: var(--bg-card-hover);
            border-color: var(--border-active);
            transform: translateY(-2px);
        }
        .perm-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
        }
        .perm-card[data-accent="indigo"]::before { background: linear-gradient(90deg, var(--accent-indigo), transparent); }
        .perm-card[data-accent="violet"]::before { background: linear-gradient(90deg, var(--accent-violet), transparent); }
        .perm-card[data-accent="blue"]::before { background: linear-gradient(90deg, var(--accent-blue), transparent); }
        .perm-card[data-accent="emerald"]::before { background: linear-gradient(90deg, var(--accent-emerald), transparent); }

        .perm-header { display: flex; align-items: flex-start; gap: 16px; margin-bottom: 20px; }
        .perm-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; flex-shrink: 0;
        }
        .perm-icon.indigo { background: rgba(99,102,241,0.15); }
        .perm-icon.violet { background: rgba(139,92,246,0.15); }
        .perm-icon.blue { background: rgba(59,130,246,0.15); }
        .perm-icon.emerald { background: rgba(16,185,129,0.15); }

        .perm-name {
            font-family: 'Space Mono', monospace;
            font-size: 13px; color: var(--text-muted);
            margin-bottom: 4px;
        }
        .perm-title { font-size: 20px; font-weight: 600; letter-spacing: -0.01em; }

        .perm-body p { font-size: 14px; color: var(--text-secondary); line-height: 1.75; margin-bottom: 20px; }

        .perm-usecase {
            font-size: 13px; font-weight: 600;
            color: var(--text-muted); text-transform: uppercase;
            letter-spacing: 0.08em; margin-bottom: 10px;
        }
        .perm-usecase-text {
            font-size: 14px; color: var(--text-secondary);
            padding-left: 16px;
            border-left: 2px solid var(--border);
            margin-bottom: 24px;
        }

        /* ─── Flow Diagrams ─── */
        .flow {
            display: flex; align-items: center; gap: 0;
            flex-wrap: wrap; justify-content: center;
            margin-top: 8px;
        }
        .flow-step {
            display: flex; align-items: center; gap: 8px;
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px 16px;
            font-size: 13px; color: var(--text-secondary);
            white-space: nowrap;
        }
        .flow-step-icon { font-size: 16px; }
        .flow-arrow {
            color: var(--text-muted);
            font-size: 16px;
            padding: 0 4px;
            flex-shrink: 0;
        }

        /* ─── Vertical Flow ─── */
        .vflow { display: flex; flex-direction: column; align-items: stretch; gap: 0; margin-top: 8px; }
        .vflow-step {
            display: flex; align-items: center; gap: 14px;
            padding: 12px 16px;
            position: relative;
        }
        .vflow-step::before {
            content: '';
            position: absolute;
            left: 28px; top: 0; bottom: 0;
            width: 1px;
            background: var(--border);
        }
        .vflow-step:first-child::before { top: 50%; }
        .vflow-step:last-child::before { bottom: 50%; }
        .vflow-dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
            position: relative; z-index: 1;
        }
        .vflow-dot.indigo { background: var(--accent-indigo); box-shadow: 0 0 8px var(--glow-indigo); }
        .vflow-dot.violet { background: var(--accent-violet); box-shadow: 0 0 8px var(--glow-violet); }
        .vflow-dot.blue { background: var(--accent-blue); box-shadow: 0 0 8px rgba(59,130,246,0.3); }
        .vflow-dot.emerald { background: var(--accent-emerald); box-shadow: 0 0 8px rgba(16,185,129,0.3); }
        .vflow-content {
            flex: 1;
            background: rgba(255,255,255,0.02);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px 16px;
        }
        .vflow-label { font-size: 13px; font-weight: 600; color: var(--text-primary); }
        .vflow-desc { font-size: 12px; color: var(--text-muted); margin-top: 2px; }

        /* ─── Data section ─── */
        .data-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 320px), 1fr));
            gap: 24px;
        }
        .data-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 28px;
        }
        .data-card h3 {
            font-size: 16px; font-weight: 600;
            margin-bottom: 16px;
            display: flex; align-items: center; gap: 10px;
        }
        .data-list { list-style: none; }
        .data-list li {
            font-size: 14px; color: var(--text-secondary);
            padding: 8px 0;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: flex-start; gap: 10px;
        }
        .data-list li:last-child { border-bottom: none; }
        .data-list li::before { content: '→'; color: var(--text-muted); flex-shrink: 0; }

        /* ─── API Endpoints ─── */
        .api-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            margin-top: 20px;
        }
        .api-table th {
            text-align: left;
            font-family: 'Space Mono', monospace;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-muted);
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
        }
        .api-table td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
            color: var(--text-secondary);
        }
        .api-method {
            font-family: 'Space Mono', monospace;
            font-size: 11px; font-weight: 700;
            padding: 3px 8px; border-radius: 4px;
        }
        .api-method.get { background: rgba(16,185,129,0.15); color: var(--accent-emerald); }
        .api-method.post { background: rgba(59,130,246,0.15); color: var(--accent-blue); }
        .api-endpoint {
            font-family: 'Space Mono', monospace;
            font-size: 12px; color: var(--text-primary);
        }

        /* ─── Footer ─── */
        .footer {
            max-width: 1120px; margin: 0 auto;
            padding: 40px 24px 60px;
            border-top: 1px solid var(--border);
            text-align: center;
        }
        .footer-links {
            display: flex; justify-content: center; gap: 32px;
            margin-bottom: 20px; flex-wrap: wrap;
        }
        .footer-links a {
            font-size: 14px; color: var(--text-secondary);
            text-decoration: none; transition: color 0.2s;
        }
        .footer-links a:hover { color: var(--accent-indigo); }
        .footer p { font-size: 13px; color: var(--text-muted); }

        /* ─── Responsive ─── */
        @media (max-width: 768px) {
            .nav-links { display: none; }
            .hero { padding: 60px 16px 48px; }
            .hero-stats { gap: 24px; }
            .section { padding: 0 16px 56px; }
            .flow { gap: 4px; }
            .flow-step { padding: 8px 10px; font-size: 11px; }
            .flow-arrow { font-size: 14px; padding: 0 2px; }
        }

        /* ─── Entrance animations ─── */
        .fade-up {
            opacity: 0; transform: translateY(20px);
            animation: fadeUp 0.6s ease forwards;
        }
        .fade-up:nth-child(1) { animation-delay: 0.1s; }
        .fade-up:nth-child(2) { animation-delay: 0.2s; }
        .fade-up:nth-child(3) { animation-delay: 0.3s; }
        .fade-up:nth-child(4) { animation-delay: 0.4s; }
        @keyframes fadeUp {
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
<div class="wrapper">

    <!-- ═══ Navigation ═══ -->
    <nav class="nav">
        <div class="nav-inner">
            <a href="/" class="nav-brand">
                <div class="nav-brand-icon">🔮</div>
                XmanApp
                <span class="nav-badge">App Review</span>
            </a>
            <div class="nav-links">
                <a href="#overview">Overview</a>
                <a href="#permissions">Permissions</a>
                <a href="#data">Data &amp; Privacy</a>
                <a href="#api">API Usage</a>
            </div>
        </div>
    </nav>

    <!-- ═══ Hero ═══ -->
    <header class="hero" id="overview">
        <p class="hero-eyebrow">Facebook App Review Submission</p>
        <h1>XmanApp — AI Fortune Telling Bot for Messenger</h1>
        <p>
            XmanApp provides AI-powered Thai fortune telling (ดูดวง) through Facebook Messenger.
            Users receive personalized astrological readings based on their birth date, with automated
            comment engagement on Page posts.
        </p>
        <div class="hero-stats">
            <div class="hero-stat">
                <div class="hero-stat-value">4</div>
                <div class="hero-stat-label">Permissions</div>
            </div>
            <div class="hero-stat">
                <div class="hero-stat-value">v21.0</div>
                <div class="hero-stat-label">Graph API</div>
            </div>
            <div class="hero-stat">
                <div class="hero-stat-value">24/7</div>
                <div class="hero-stat-label">Automated</div>
            </div>
            <div class="hero-stat">
                <div class="hero-stat-value">AI</div>
                <div class="hero-stat-label">Powered</div>
            </div>
        </div>
    </header>

    <!-- ═══ Permissions ═══ -->
    <section class="section" id="permissions">
        <div class="section-label">Permission Usage</div>
        <h2 class="section-title">How XmanApp Uses Each Permission</h2>
        <p class="section-desc">
            Each permission below is essential for the app to function correctly.
            Here's exactly what we use them for and the user experience flow.
        </p>

        <div class="perm-grid">

            <!-- ── pages_show_list ── -->
            <div class="perm-card fade-up" data-accent="indigo">
                <div class="perm-header">
                    <div class="perm-icon indigo">📋</div>
                    <div>
                        <div class="perm-name">pages_show_list</div>
                        <div class="perm-title">Page Selection &amp; Connection</div>
                    </div>
                </div>
                <div class="perm-body">
                    <p>
                        Allows the admin to view and select which Facebook Page to connect with
                        the fortune telling bot. The admin dashboard lists all Pages they manage
                        so they can choose the correct one.
                    </p>

                    <div class="perm-usecase">Use Case</div>
                    <div class="perm-usecase-text">
                        The Page owner opens the admin dashboard, views their list of managed Pages,
                        selects the fortune telling Page, and connects it to the bot. This is a
                        one-time setup step.
                    </div>

                    <div class="perm-usecase" style="margin-top: 20px;">Admin Flow</div>
                    <div class="vflow">
                        <div class="vflow-step">
                            <div class="vflow-dot indigo"></div>
                            <div class="vflow-content">
                                <div class="vflow-label">Open Admin Dashboard</div>
                                <div class="vflow-desc">Navigate to /admin/fortune/settings</div>
                            </div>
                        </div>
                        <div class="vflow-step">
                            <div class="vflow-dot indigo"></div>
                            <div class="vflow-content">
                                <div class="vflow-label">View Managed Pages</div>
                                <div class="vflow-desc">API call: GET /me/accounts (pages_show_list)</div>
                            </div>
                        </div>
                        <div class="vflow-step">
                            <div class="vflow-dot indigo"></div>
                            <div class="vflow-content">
                                <div class="vflow-label">Select Target Page</div>
                                <div class="vflow-desc">Choose the fortune telling page from the list</div>
                            </div>
                        </div>
                        <div class="vflow-step">
                            <div class="vflow-dot indigo"></div>
                            <div class="vflow-content">
                                <div class="vflow-label">Connection Confirmed</div>
                                <div class="vflow-desc">Page ID and token saved securely in database</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── pages_manage_metadata ── -->
            <div class="perm-card fade-up" data-accent="violet">
                <div class="perm-header">
                    <div class="perm-icon violet">⚙️</div>
                    <div>
                        <div class="perm-name">pages_manage_metadata</div>
                        <div class="perm-title">Messenger Bot Profile Setup</div>
                    </div>
                </div>
                <div class="perm-body">
                    <p>
                        Configures the Messenger experience: the "Get Started" button,
                        persistent menu, and greeting text. This ensures users know
                        they're interacting with a fortune telling bot.
                    </p>

                    <div class="perm-usecase">Use Case</div>
                    <div class="perm-usecase-text">
                        Admin sets up the Messenger profile so new users see a welcome greeting
                        and a "Get Started" button. The persistent menu provides quick access
                        to fortune readings, deep readings, and help.
                    </div>

                    <div class="perm-usecase" style="margin-top: 20px;">Configuration Flow</div>
                    <div class="vflow">
                        <div class="vflow-step">
                            <div class="vflow-dot violet"></div>
                            <div class="vflow-content">
                                <div class="vflow-label">Configure "Get Started" Button</div>
                                <div class="vflow-desc">POST /me/messenger_profile — Sets welcome postback</div>
                            </div>
                        </div>
                        <div class="vflow-step">
                            <div class="vflow-dot violet"></div>
                            <div class="vflow-content">
                                <div class="vflow-label">Set Greeting Text</div>
                                <div class="vflow-desc">"Welcome! 🔮 I'm an AI fortune teller. Tap below to begin."</div>
                            </div>
                        </div>
                        <div class="vflow-step">
                            <div class="vflow-dot violet"></div>
                            <div class="vflow-content">
                                <div class="vflow-label">Configure Persistent Menu</div>
                                <div class="vflow-desc">Menu items: 🔮 Fortune Reading, 🌟 Deep Reading, ❓ Help</div>
                            </div>
                        </div>
                        <div class="vflow-step">
                            <div class="vflow-dot violet"></div>
                            <div class="vflow-content">
                                <div class="vflow-label">Subscribe to Webhook Events</div>
                                <div class="vflow-desc">Events: messages, messaging_postbacks, feed, message_echoes</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── pages_messaging ── -->
            <div class="perm-card fade-up" data-accent="blue">
                <div class="perm-header">
                    <div class="perm-icon blue">💬</div>
                    <div>
                        <div class="perm-name">pages_messaging</div>
                        <div class="perm-title">AI Fortune Telling via Messenger</div>
                    </div>
                </div>
                <div class="perm-body">
                    <p>
                        The core functionality. When a user messages the Page, the AI bot
                        processes their birth date and generates a personalized astrological
                        reading, sent directly through Messenger.
                    </p>

                    <div class="perm-usecase">Use Case</div>
                    <div class="perm-usecase-text">
                        User sends "ดูดวง" (fortune telling) → Bot asks for birth date →
                        User provides date → AI generates reading using Thai astrology →
                        Bot sends personalized fortune with lucky numbers, colors, and directions.
                    </div>

                    <div class="perm-usecase" style="margin-top: 20px;">Messenger Conversation Flow</div>
                    <div class="vflow">
                        <div class="vflow-step">
                            <div class="vflow-dot blue"></div>
                            <div class="vflow-content">
                                <div class="vflow-label">👤 User sends "ดูดวง" (Fortune)</div>
                                <div class="vflow-desc">Or taps "Get Started" or persistent menu item</div>
                            </div>
                        </div>
                        <div class="vflow-step">
                            <div class="vflow-dot blue"></div>
                            <div class="vflow-content">
                                <div class="vflow-label">🤖 Bot asks for birth date</div>
                                <div class="vflow-desc">POST /me/messages — Quick reply buttons for days of week</div>
                            </div>
                        </div>
                        <div class="vflow-step">
                            <div class="vflow-dot blue"></div>
                            <div class="vflow-content">
                                <div class="vflow-label">👤 User selects birth day</div>
                                <div class="vflow-desc">e.g. "วันอังคาร" (Tuesday)</div>
                            </div>
                        </div>
                        <div class="vflow-step">
                            <div class="vflow-dot blue"></div>
                            <div class="vflow-content">
                                <div class="vflow-label">🔮 AI generates personalized reading</div>
                                <div class="vflow-desc">Thai astrology + AI prediction engine (async via queue)</div>
                            </div>
                        </div>
                        <div class="vflow-step">
                            <div class="vflow-dot blue"></div>
                            <div class="vflow-content">
                                <div class="vflow-label">🤖 Bot sends fortune reading</div>
                                <div class="vflow-desc">POST /me/messages — Text + image with lucky info</div>
                            </div>
                        </div>
                        <div class="vflow-step">
                            <div class="vflow-dot blue"></div>
                            <div class="vflow-content">
                                <div class="vflow-label">🌟 Offer Deep Reading (Optional)</div>
                                <div class="vflow-desc">Quick reply: "ดูดวงเชิงลึก" (49 THB) for premium content</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── pages_read_engagement ── -->
            <div class="perm-card fade-up" data-accent="emerald">
                <div class="perm-header">
                    <div class="perm-icon emerald">🗨️</div>
                    <div>
                        <div class="perm-name">pages_read_engagement</div>
                        <div class="perm-title">Comment Auto-Reply Engagement</div>
                    </div>
                </div>
                <div class="perm-body">
                    <p>
                        When users comment on fortune-related Page posts, the bot automatically
                        replies to the comment and sends a Messenger DM inviting them to try
                        the fortune telling service.
                    </p>

                    <div class="perm-usecase">Use Case</div>
                    <div class="perm-usecase-text">
                        Page publishes a daily horoscope post → User comments "ดูดวง" →
                        Bot replies in comment "ส่งผลดูดวงให้คุณทาง inbox แล้วค่ะ" →
                        Bot DMs user with Quick Replies to start a reading.
                        Duplicate engagement is prevented per user per post.
                    </div>

                    <div class="perm-usecase" style="margin-top: 20px;">Comment Engagement Flow</div>
                    <div class="vflow">
                        <div class="vflow-step">
                            <div class="vflow-dot emerald"></div>
                            <div class="vflow-content">
                                <div class="vflow-label">📝 Page posts daily horoscope content</div>
                                <div class="vflow-desc">Automated or manual post about fortune/horoscope</div>
                            </div>
                        </div>
                        <div class="vflow-step">
                            <div class="vflow-dot emerald"></div>
                            <div class="vflow-content">
                                <div class="vflow-label">👤 User comments on the post</div>
                                <div class="vflow-desc">Webhook receives feed event (pages_read_engagement)</div>
                            </div>
                        </div>
                        <div class="vflow-step">
                            <div class="vflow-dot emerald"></div>
                            <div class="vflow-content">
                                <div class="vflow-label">🤖 Bot replies to comment</div>
                                <div class="vflow-desc">POST /{comment_id}/comments — "ส่งผลดูดวงทาง inbox แล้วค่ะ 🔮"</div>
                            </div>
                        </div>
                        <div class="vflow-step">
                            <div class="vflow-dot emerald"></div>
                            <div class="vflow-content">
                                <div class="vflow-label">💬 Bot sends DM with Quick Replies</div>
                                <div class="vflow-desc">POST /me/messages — Invites user to start fortune reading</div>
                            </div>
                        </div>
                        <div class="vflow-step">
                            <div class="vflow-dot emerald"></div>
                            <div class="vflow-content">
                                <div class="vflow-label">📊 Engagement tracked</div>
                                <div class="vflow-desc">Stored in fortune_comment_engagements — prevents duplicates</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- ═══ Data Handling ═══ -->
    <section class="section" id="data">
        <div class="section-label">Data &amp; Privacy</div>
        <h2 class="section-title">How We Handle User Data</h2>
        <p class="section-desc">
            XmanApp collects minimal data necessary for the fortune telling service
            and follows strict data handling practices.
        </p>

        <div class="data-grid">

            <div class="data-card">
                <h3>🔐 Data Collected</h3>
                <ul class="data-list">
                    <li>Facebook PSID (Platform-Scoped User ID) for session tracking</li>
                    <li>Display name (for personalized greetings only)</li>
                    <li>Birth day of week (required for Thai astrological calculation)</li>
                    <li>User questions (optional, for deep readings)</li>
                    <li>Comment text (for engagement replies)</li>
                </ul>
            </div>

            <div class="data-card">
                <h3>🛡️ Data NOT Collected</h3>
                <ul class="data-list">
                    <li>Email address or phone number</li>
                    <li>Friends list or social connections</li>
                    <li>Photos, videos, or media files</li>
                    <li>Location or GPS data</li>
                    <li>Browsing history or device information</li>
                </ul>
            </div>

            <div class="data-card">
                <h3>📋 Data Usage Purpose</h3>
                <ul class="data-list">
                    <li>Generate personalized fortune readings</li>
                    <li>Track daily free reading limits (max 3/day)</li>
                    <li>Prevent duplicate comment engagement per post</li>
                    <li>Process payment for premium deep readings</li>
                    <li>Admin handover detection (pause bot when human helps)</li>
                </ul>
            </div>

            <div class="data-card">
                <h3>🔒 Security Measures</h3>
                <ul class="data-list">
                    <li>Webhook signature verification (X-Hub-Signature-256 / SHA256)</li>
                    <li>Page Access Tokens encrypted at rest in database</li>
                    <li>Rate limiting on API endpoints</li>
                    <li>Admin authentication required for all settings</li>
                    <li>Data retained only as long as necessary for service</li>
                </ul>
            </div>

        </div>
    </section>

    <!-- ═══ API Usage ═══ -->
    <section class="section" id="api">
        <div class="section-label">Technical Details</div>
        <h2 class="section-title">Graph API Endpoints Used</h2>
        <p class="section-desc">
            All API calls use Facebook Graph API v21.0 with proper authentication
            via Page Access Token.
        </p>

        <div class="data-card" style="overflow-x: auto;">
            <table class="api-table">
                <thead>
                    <tr>
                        <th>Method</th>
                        <th>Endpoint</th>
                        <th>Permission</th>
                        <th>Purpose</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="api-method get">GET</span></td>
                        <td><span class="api-endpoint">/me/accounts</span></td>
                        <td>pages_show_list</td>
                        <td>List admin's managed Pages for selection</td>
                    </tr>
                    <tr>
                        <td><span class="api-method post">POST</span></td>
                        <td><span class="api-endpoint">/me/messenger_profile</span></td>
                        <td>pages_manage_metadata</td>
                        <td>Setup Get Started button, menu, greeting</td>
                    </tr>
                    <tr>
                        <td><span class="api-method post">POST</span></td>
                        <td><span class="api-endpoint">/me/messages</span></td>
                        <td>pages_messaging</td>
                        <td>Send fortune readings &amp; quick replies</td>
                    </tr>
                    <tr>
                        <td><span class="api-method post">POST</span></td>
                        <td><span class="api-endpoint">/{comment_id}/comments</span></td>
                        <td>pages_read_engagement</td>
                        <td>Reply to user comments on posts</td>
                    </tr>
                    <tr>
                        <td><span class="api-method get">GET</span></td>
                        <td><span class="api-endpoint">/{user_id}</span></td>
                        <td>pages_messaging</td>
                        <td>Get user profile (name) for personalization</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- ═══ Admin Dashboard Info ═══ -->
    <section class="section">
        <div class="section-label">Admin Interface</div>
        <h2 class="section-title">Admin Dashboard Overview</h2>
        <p class="section-desc">
            The admin dashboard at <code style="background:var(--bg-card);padding:2px 8px;border-radius:4px;font-family:'Space Mono',monospace;font-size:13px;">/admin/fortune/settings</code>
            provides complete control over the bot's behavior.
        </p>

        <div class="data-grid">
            <div class="data-card">
                <h3>📡 Facebook Connection</h3>
                <ul class="data-list">
                    <li>Configure App ID, App Secret, Page ID</li>
                    <li>Set Page Access Token for API calls</li>
                    <li>Set Webhook Verify Token for security</li>
                    <li>Test connection status with one click</li>
                </ul>
            </div>
            <div class="data-card">
                <h3>🤖 AI Configuration</h3>
                <ul class="data-list">
                    <li>Select AI provider (Gemini, Groq, etc.)</li>
                    <li>Customize fortune telling AI prompts</li>
                    <li>Set reading limits (free vs. paid)</li>
                    <li>Configure pricing for deep readings</li>
                </ul>
            </div>
            <div class="data-card">
                <h3>🗨️ Comment Engagement</h3>
                <ul class="data-list">
                    <li>Enable/disable auto-reply on comments</li>
                    <li>Choose mode: AI-generated or template-based</li>
                    <li>Customize reply templates with placeholders</li>
                    <li>View engagement statistics and history</li>
                </ul>
            </div>
            <div class="data-card">
                <h3>🙋 Admin Handover</h3>
                <ul class="data-list">
                    <li>Bot pauses when human admin replies via Page Inbox</li>
                    <li>Configurable timeout (default: 15 minutes)</li>
                    <li>Seamless transition between bot and human support</li>
                    <li>Automatic bot resume after timeout expires</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- ═══ Footer ═══ -->
    <footer class="footer">
        <div class="footer-links">
            <a href="/privacy-policy.html" target="_blank">Privacy Policy</a>
            <a href="/terms-of-service.html" target="_blank">Terms of Service</a>
            <a href="https://main.thaiprompt.online" target="_blank">Main Website</a>
            <a href="https://main.thaiprompt.online/admin/fortune/settings" target="_blank">Admin Dashboard</a>
        </div>
        <p>
            &copy; 2026 XmanApp by Thaiprompt — AI Fortune Telling Platform.
            App ID: 664172615253513
        </p>
    </footer>

</div>
</body>
</html>
