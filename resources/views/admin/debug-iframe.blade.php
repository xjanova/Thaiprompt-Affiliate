<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🐛 Iframe Debug Test</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(0,0,0,0.3);
            padding: 30px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }
        h1 { font-size: 2.5em; margin-bottom: 20px; text-align: center; }
        .section {
            background: rgba(255,255,255,0.1);
            padding: 20px;
            margin: 20px 0;
            border-radius: 10px;
            border: 2px solid rgba(255,255,255,0.2);
        }
        .section h2 { color: #ffd700; margin-bottom: 15px; }
        .info {
            background: rgba(255,255,255,0.05);
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #4ade80;
            font-size: 14px;
            line-height: 1.6;
        }
        .error { border-left-color: #f87171; }
        .warning { border-left-color: #fbbf24; }
        .success { border-left-color: #4ade80; }
        code {
            background: rgba(0,0,0,0.4);
            padding: 2px 8px;
            border-radius: 4px;
            color: #ffd700;
        }
        .test-iframe {
            width: 100%;
            height: 400px;
            border: 3px solid #4ade80;
            border-radius: 10px;
            background: white;
            margin-top: 20px;
        }
        button {
            background: #4ade80;
            color: black;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            margin: 10px 5px;
        }
        button:hover { background: #22c55e; transform: scale(1.05); }
        #console-log {
            background: black;
            color: #0f0;
            padding: 20px;
            border-radius: 10px;
            font-family: monospace;
            max-height: 300px;
            overflow-y: auto;
            margin-top: 20px;
        }
        .log-line { margin: 5px 0; }
        .log-error { color: #ff6b6b; }
        .log-warn { color: #ffd93d; }
        .log-success { color: #6bcf7f; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🐛 Iframe Navigation Debug Test</h1>

        <div class="section">
            <h2>📍 Current Status</h2>
            <div class="info">
                <strong>URL:</strong> <code id="current-url"></code><br>
                <strong>Is Iframe Mode:</strong> <code id="is-iframe-mode"></code><br>
                <strong>DetectIframeMode Middleware:</strong> <code id="middleware-status"></code><br>
                <strong>Alpine.js Loaded:</strong> <code id="alpine-status"></code>
            </div>
        </div>

        <div class="section">
            <h2>🧪 Test 1: Simple Iframe Test</h2>
            <p>ทดสอบ iframe พื้นฐาน - ถ้าเห็นข้อความด้านล่าง = iframe ทำงาน</p>
            <iframe class="test-iframe" srcdoc="<h1 style='text-align:center; margin-top:100px; color:green;'>✅ Iframe Works!</h1>"></iframe>
        </div>

        <div class="section">
            <h2>🧪 Test 2: Load Dashboard with ?iframe=1</h2>
            <p>ทดสอบโหลด dashboard ด้วย ?iframe=1 parameter</p>
            <button onclick="loadDashboard()">Load Dashboard in Iframe</button>
            <button onclick="openInNewTab()">Open in New Tab</button>
            <iframe id="dashboard-iframe" class="test-iframe" style="display:none;"></iframe>
        </div>

        <div class="section">
            <h2>📝 Console Log</h2>
            <div id="console-log"></div>
        </div>
    </div>

    <script>
        // Capture console logs
        const consoleLog = document.getElementById('console-log');
        const originalLog = console.log;
        const originalError = console.error;
        const originalWarn = console.warn;

        function addLog(message, type = 'info') {
            const line = document.createElement('div');
            line.className = `log-line log-${type}`;
            line.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
            consoleLog.appendChild(line);
            consoleLog.scrollTop = consoleLog.scrollHeight;
        }

        console.log = function(...args) {
            addLog(args.join(' '), 'success');
            originalLog.apply(console, args);
        };

        console.error = function(...args) {
            addLog(args.join(' '), 'error');
            originalError.apply(console, args);
        };

        console.warn = function(...args) {
            addLog(args.join(' '), 'warn');
            originalWarn.apply(console, args);
        };

        // Update status
        document.getElementById('current-url').textContent = window.location.href;
        document.getElementById('is-iframe-mode').textContent = '{{ $isIframeMode ?? "NOT SET" }}';
        document.getElementById('middleware-status').textContent = '{{ request()->attributes->get("iframe_mode") !== null ? "✅ ACTIVE" : "❌ NOT ACTIVE" }}';
        document.getElementById('alpine-status').textContent = typeof Alpine !== 'undefined' ? '✅ LOADED' : '❌ NOT LOADED';

        console.log('🚀 Debug page loaded');
        console.log('📍 Current URL: ' + window.location.href);
        console.log('🔍 isIframeMode: {{ $isIframeMode ?? "NOT SET" }}');

        function loadDashboard() {
            const iframe = document.getElementById('dashboard-iframe');
            iframe.style.display = 'block';

            const dashboardUrl = '{{ route("admin.dashboard") }}?iframe=1';
            console.log('📨 Loading dashboard: ' + dashboardUrl);

            iframe.src = dashboardUrl;

            iframe.onload = function() {
                console.log('✅ Dashboard iframe loaded successfully');
                try {
                    const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                    console.log('📄 Iframe document title: ' + iframeDoc.title);
                    console.log('📊 Iframe body height: ' + iframeDoc.body.scrollHeight + 'px');
                } catch (e) {
                    console.error('❌ Cannot access iframe content (CORS): ' + e.message);
                }
            };

            iframe.onerror = function() {
                console.error('❌ Iframe failed to load');
            };
        }

        function openInNewTab() {
            const url = '{{ route("admin.dashboard") }}?iframe=1';
            console.log('🔗 Opening in new tab: ' + url);
            window.open(url, '_blank');
        }

        // Test Alpine.js after delay
        setTimeout(() => {
            if (typeof Alpine !== 'undefined') {
                console.log('✅ Alpine.js is available');
            } else {
                console.warn('⚠️ Alpine.js not loaded yet');
            }
        }, 1000);
    </script>
</body>
</html>
