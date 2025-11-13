<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audio Spectrum Visualizer - Thai Prompt</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;600;700;900&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Noto Sans Thai', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 50%, #16213e 100%);
            color: white;
            overflow-x: hidden;
            min-height: 100vh;
        }

        .container {
            max-width: 100%;
            width: 100%;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 900;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.8;
            font-size: 1rem;
        }

        /* Main Layout */
        .main-layout {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 20px;
            height: calc(100vh - 200px);
        }

        /* Control Panel */
        .control-panel {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 20px;
            overflow-y: auto;
            max-height: calc(100vh - 200px);
        }

        .control-section {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .control-section:last-child {
            border-bottom: none;
        }

        .control-section h3 {
            font-size: 1.1rem;
            margin-bottom: 15px;
            color: #667eea;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .control-group {
            margin-bottom: 15px;
        }

        .control-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.9rem;
            opacity: 0.9;
        }

        /* Buttons */
        .btn {
            width: 100%;
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Noto Sans Thai', sans-serif;
            margin-bottom: 10px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff0844 0%, #ffb199 100%);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255, 8, 68, 0.4);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Input Controls */
        input[type="range"] {
            width: 100%;
            height: 6px;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.1);
            outline: none;
            -webkit-appearance: none;
        }

        input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            cursor: pointer;
        }

        input[type="range"]::-moz-range-thumb {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            cursor: pointer;
            border: none;
        }

        input[type="color"] {
            width: 100%;
            height: 40px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            background: transparent;
        }

        select {
            width: 100%;
            padding: 10px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-family: 'Noto Sans Thai', sans-serif;
            font-size: 0.95rem;
            cursor: pointer;
        }

        select option {
            background: #1a1a2e;
            color: white;
        }

        /* File Upload */
        .file-upload {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }

        .file-upload input[type="file"] {
            position: absolute;
            left: -9999px;
        }

        .file-upload-label {
            display: block;
            padding: 12px 20px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px dashed rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-upload-label:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.5);
        }

        .file-name {
            margin-top: 8px;
            font-size: 0.85rem;
            opacity: 0.7;
            word-break: break-all;
        }

        /* Canvas Container */
        .canvas-container {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 500px;
        }

        #visualizer-canvas {
            display: block;
            width: 100%;
            height: 100%;
        }

        #visualizer-3d {
            display: none;
            width: 100%;
            height: 100%;
        }

        .canvas-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            pointer-events: none;
        }

        .canvas-overlay-icon {
            font-size: 4rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .canvas-overlay-text {
            font-size: 1.2rem;
            opacity: 0.7;
        }

        /* Preset Buttons */
        .preset-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .preset-btn {
            padding: 10px;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Noto Sans Thai', sans-serif;
            color: white;
        }

        .preset-btn:hover {
            transform: translateY(-2px);
        }

        .preset-neon {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .preset-ocean {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .preset-sunset {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }

        .preset-forest {
            background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);
        }

        .preset-fire {
            background: linear-gradient(135deg, #ff0844 0%, #ffb199 100%);
        }

        .preset-galaxy {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .preset-cyber {
            background: linear-gradient(135deg, #00ff88 0%, #00d4ff 100%);
        }

        .preset-aurora {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        }

        .preset-ice {
            background: linear-gradient(135deg, #e0c3fc 0%, #8ec5fc 100%);
        }

        .preset-lava {
            background: linear-gradient(135deg, #ff9a56 0%, #ff4e50 100%);
        }

        .preset-electric {
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
        }

        .preset-space {
            background: linear-gradient(135deg, #2c3e50 0%, #4ca1af 100%);
        }

        /* Value Display */
        .value-display {
            display: inline-block;
            min-width: 40px;
            text-align: right;
            font-weight: 600;
            color: #667eea;
        }

        .control-label {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        /* Back Button */
        .back-button {
            margin-top: 20px;
            text-align: center;
        }

        .back-button a {
            display: inline-block;
            padding: 12px 30px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .back-button a:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: scale(1.05);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .main-layout {
                grid-template-columns: 1fr;
            }

            .control-panel {
                max-height: none;
            }
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.8rem;
            }

            .preset-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Scrollbar */
        .control-panel::-webkit-scrollbar {
            width: 8px;
        }

        .control-panel::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }

        .control-panel::-webkit-scrollbar-thumb {
            background: rgba(102, 126, 234, 0.5);
            border-radius: 10px;
        }

        .control-panel::-webkit-scrollbar-thumb:hover {
            background: rgba(102, 126, 234, 0.7);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🎵 Audio Spectrum Visualizer</h1>
            <p>แปลงไฟล์เสียงเป็นภาพสเปคตรัมสวยงามแบบเรียลไทม์</p>
        </div>

        <!-- Main Layout -->
        <div class="main-layout">
            <!-- Control Panel -->
            <div class="control-panel">
                <!-- Audio Input Section -->
                <div class="control-section">
                    <h3>🎤 แหล่งเสียง</h3>

                    <div class="file-upload">
                        <input type="file" id="audioFile" accept="audio/*">
                        <label for="audioFile" class="file-upload-label">
                            📁 เลือกไฟล์เสียง
                        </label>
                        <div class="file-name" id="fileName">ยังไม่ได้เลือกไฟล์</div>
                    </div>

                    <button class="btn btn-primary" id="micButton">
                        🎤 ใช้ไมโครโฟน
                    </button>

                    <button class="btn btn-danger" id="stopButton" disabled>
                        ⏹️ หยุด
                    </button>
                </div>

                <!-- Visualization Style Section -->
                <div class="control-section">
                    <h3>🎨 รูปแบบการแสดงผล</h3>

                    <div class="control-group">
                        <label>ประเภท:</label>
                        <select id="visualizationType">
                            <optgroup label="🎨 2D Visualizations">
                                <option value="bars">แท่งแนวตั้ง (Bars)</option>
                                <option value="circular">วงกลม (Circular)</option>
                                <option value="waveform">คลื่นเสียง (Waveform)</option>
                                <option value="particles">อนุภาค (Particles)</option>
                                <option value="spiral">เกลียว (Spiral)</option>
                                <option value="radial">รัศมี (Radial)</option>
                                <option value="symmetrical">สะท้อนกระจก (Symmetrical)</option>
                                <option value="double-helix">เกลียวคู่ (Double Helix)</option>
                                <option value="flower">ดอกไม้ (Flower)</option>
                                <option value="lightning">สายฟ้า (Lightning)</option>
                                <option value="matrix">Matrix Rain</option>
                                <option value="fireworks">ดอกไม้ไฟ (Fireworks)</option>
                                <option value="dna">DNA Helix</option>
                                <option value="crystal">คริสตัล (Crystal)</option>
                                <option value="gravity">Gravity Balls</option>
                            </optgroup>
                            <optgroup label="🎮 3D Visualizations">
                                <option value="3d-bars">แท่ง 3D (3D Bars)</option>
                                <option value="3d-sphere">ทรงกลม 3D (3D Sphere)</option>
                                <option value="3d-tunnel">อุโมงค์ 3D (3D Tunnel)</option>
                                <option value="3d-wave">คลื่น 3D (3D Wave)</option>
                                <option value="3d-galaxy">กาแล็กซี่ 3D (3D Galaxy)</option>
                                <option value="3d-cubes">ลูกบาศก์ 3D (3D Cubes)</option>
                                <option value="3d-particles">อนุภาค 3D (3D Particles)</option>
                            </optgroup>
                        </select>
                    </div>
                </div>

                <!-- Color & Effect Section -->
                <div class="control-section">
                    <h3>🌈 สีและเอฟเฟกต์</h3>

                    <div class="control-group">
                        <label>สีหลัก:</label>
                        <input type="color" id="primaryColor" value="#667eea">
                    </div>

                    <div class="control-group">
                        <label>สีรอง:</label>
                        <input type="color" id="secondaryColor" value="#764ba2">
                    </div>

                    <div class="control-group">
                        <label class="control-label">
                            <span>ความสว่าง:</span>
                            <span class="value-display" id="brightnessValue">100%</span>
                        </label>
                        <input type="range" id="brightness" min="50" max="200" value="100">
                    </div>

                    <div class="control-group">
                        <label class="control-label">
                            <span>เบลอพื้นหลัง:</span>
                            <span class="value-display" id="blurValue">0</span>
                        </label>
                        <input type="range" id="blur" min="0" max="20" value="0">
                    </div>

                    <div class="control-section">
                        <h3>🎭 ธีมสำเร็จรูป</h3>
                        <div class="preset-grid">
                            <button class="preset-btn preset-neon" data-preset="neon">💖 Neon</button>
                            <button class="preset-btn preset-ocean" data-preset="ocean">🌊 Ocean</button>
                            <button class="preset-btn preset-sunset" data-preset="sunset">🌅 Sunset</button>
                            <button class="preset-btn preset-forest" data-preset="forest">🌲 Forest</button>
                            <button class="preset-btn preset-fire" data-preset="fire">🔥 Fire</button>
                            <button class="preset-btn preset-galaxy" data-preset="galaxy">🌌 Galaxy</button>
                            <button class="preset-btn preset-cyber" data-preset="cyber">🤖 Cyber</button>
                            <button class="preset-btn preset-aurora" data-preset="aurora">🌈 Aurora</button>
                            <button class="preset-btn preset-ice" data-preset="ice">❄️ Ice</button>
                            <button class="preset-btn preset-lava" data-preset="lava">🌋 Lava</button>
                            <button class="preset-btn preset-electric" data-preset="electric">⚡ Electric</button>
                            <button class="preset-btn preset-space" data-preset="space">🚀 Space</button>
                        </div>
                    </div>
                </div>

                <!-- Audio Settings Section -->
                <div class="control-section">
                    <h3>🎛️ การตั้งค่าเสียง</h3>

                    <div class="control-group">
                        <label class="control-label">
                            <span>ความละเอียด FFT:</span>
                            <span class="value-display" id="fftValue">2048</span>
                        </label>
                        <select id="fftSize">
                            <option value="512">512</option>
                            <option value="1024">1024</option>
                            <option value="2048" selected>2048</option>
                            <option value="4096">4096</option>
                            <option value="8192">8192</option>
                        </select>
                    </div>

                    <div class="control-group">
                        <label class="control-label">
                            <span>ความเร็วการตอบสนอง:</span>
                            <span class="value-display" id="smoothingValue">0.8</span>
                        </label>
                        <input type="range" id="smoothing" min="0" max="0.99" step="0.01" value="0.8">
                    </div>

                    <div class="control-group">
                        <label class="control-label">
                            <span>จำนวนแท่ง:</span>
                            <span class="value-display" id="barCountValue">64</span>
                        </label>
                        <input type="range" id="barCount" min="16" max="256" step="16" value="64">
                    </div>

                    <div class="control-group">
                        <label class="control-label">
                            <span>ความอ่อนไหว:</span>
                            <span class="value-display" id="sensitivityValue">100%</span>
                        </label>
                        <input type="range" id="sensitivity" min="50" max="200" value="100">
                    </div>
                </div>

                <!-- Animation Settings -->
                <div class="control-section">
                    <h3>✨ แอนิเมชัน</h3>

                    <div class="control-group">
                        <label class="control-label">
                            <span>ความเร็วหมุน (3D):</span>
                            <span class="value-display" id="rotationSpeedValue">1</span>
                        </label>
                        <input type="range" id="rotationSpeed" min="0" max="5" step="0.1" value="1">
                    </div>

                    <div class="control-group">
                        <label class="control-label">
                            <span>ความเร็วซูม:</span>
                            <span class="value-display" id="zoomSpeedValue">0</span>
                        </label>
                        <input type="range" id="zoomSpeed" min="0" max="5" step="0.1" value="0">
                    </div>
                </div>

                <!-- Special Effects Section -->
                <div class="control-section">
                    <h3>🌟 เอฟเฟกต์พิเศษ</h3>

                    <div class="control-group">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" id="mirrorMode" style="cursor: pointer;">
                            <span>โหมดสะท้อนกระจก</span>
                        </label>
                    </div>

                    <div class="control-group">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" id="rainbowMode" style="cursor: pointer;">
                            <span>โหมดสีรุ้ง</span>
                        </label>
                    </div>

                    <div class="control-group">
                        <label class="control-label">
                            <span>เอฟเฟกต์หาง (Trail):</span>
                            <span class="value-display" id="trailValue">0.1</span>
                        </label>
                        <input type="range" id="trail" min="0" max="0.9" step="0.1" value="0.1">
                    </div>

                    <div class="control-group">
                        <label class="control-label">
                            <span>ความเข้มเงา:</span>
                            <span class="value-display" id="shadowValue">20</span>
                        </label>
                        <input type="range" id="shadow" min="0" max="100" value="20">
                    </div>
                </div>

                <!-- Actions Section -->
                <div class="control-section">
                    <h3>📸 การจับภาพ</h3>

                    <button class="btn btn-secondary" id="fullscreenButton">
                        🖥️ เต็มจอ (Fullscreen)
                    </button>

                    <button class="btn btn-secondary" id="screenshotButton">
                        📷 ถ่ายภาพ (Screenshot)
                    </button>

                    <button class="btn btn-secondary" id="recordButton">
                        🎥 บันทึกวิดีโอ
                    </button>

                    <button class="btn btn-danger" id="stopRecordButton" disabled style="display: none;">
                        ⏹️ หยุดบันทึก
                    </button>
                </div>
            </div>

            <!-- Canvas Container -->
            <div class="canvas-container">
                <canvas id="visualizer-canvas"></canvas>
                <div id="visualizer-3d"></div>
                <div class="canvas-overlay" id="canvasOverlay">
                    <div class="canvas-overlay-icon">🎵</div>
                    <div class="canvas-overlay-text">เลือกไฟล์เสียงหรือเปิดไมโครโฟนเพื่อเริ่มต้น</div>
                </div>
            </div>
        </div>

        <!-- Back Button -->
        <div class="back-button">
            <a href="/">← กลับหน้าหลัก</a>
        </div>
    </div>

    <script>
/**
 * Audio Spectrum Visualizer
 * Advanced real-time audio visualization with 2D and 3D modes
 */

class AudioSpectrumVisualizer {
    constructor() {
        // Audio Context
        this.audioContext = null;
        this.analyser = null;
        this.dataArray = null;
        this.bufferLength = null;
        this.source = null;
        this.audioElement = null;
        this.microphone = null;

        // Canvas
        this.canvas = document.getElementById('visualizer-canvas');
        this.ctx = this.canvas.getContext('2d');
        this.container3D = document.getElementById('visualizer-3d');

        // Three.js
        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.meshes = [];

        // Settings
        this.settings = {
            visualizationType: 'bars',
            primaryColor: '#667eea',
            secondaryColor: '#764ba2',
            brightness: 100,
            blur: 0,
            fftSize: 2048,
            smoothing: 0.8,
            barCount: 64,
            sensitivity: 100,
            rotationSpeed: 1,
            zoomSpeed: 0,
            mirrorMode: false,
            rainbowMode: false,
            trail: 0.1,
            shadow: 20
        };

        // Animation
        this.animationId = null;
        this.isPlaying = false;
        this.rotation = 0;
        this.zoom = 1;

        // Recording
        this.mediaRecorder = null;
        this.recordedChunks = [];

        // Particles (for certain visualizations)
        this.particles = [];

        // Initialize
        this.init();
    }

    init() {
        this.setupCanvas();
        this.setupEventListeners();
        this.setupThreeJS();
    }

    setupCanvas() {
        const container = this.canvas.parentElement;
        this.canvas.width = container.clientWidth;
        this.canvas.height = container.clientHeight;

        window.addEventListener('resize', () => {
            this.canvas.width = container.clientWidth;
            this.canvas.height = container.clientHeight;

            if (this.renderer) {
                this.renderer.setSize(container.clientWidth, container.clientHeight);
                this.camera.aspect = container.clientWidth / container.clientHeight;
                this.camera.updateProjectionMatrix();
            }
        });
    }

    setupThreeJS() {
        // Scene
        this.scene = new THREE.Scene();
        this.scene.background = new THREE.Color(0x0a0a0a);

        // Camera
        const container = this.canvas.parentElement;
        this.camera = new THREE.PerspectiveCamera(
            75,
            container.clientWidth / container.clientHeight,
            0.1,
            1000
        );
        this.camera.position.z = 50;

        // Renderer
        this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
        this.renderer.setSize(container.clientWidth, container.clientHeight);
        this.container3D.appendChild(this.renderer.domElement);

        // Lighting
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
        this.scene.add(ambientLight);

        const pointLight = new THREE.PointLight(0xffffff, 1);
        pointLight.position.set(0, 50, 50);
        this.scene.add(pointLight);
    }

    setupEventListeners() {
        // Audio File Upload
        document.getElementById('audioFile').addEventListener('change', (e) => {
            this.loadAudioFile(e.target.files[0]);
        });

        // Microphone
        document.getElementById('micButton').addEventListener('click', () => {
            this.startMicrophone();
        });

        // Stop Button
        document.getElementById('stopButton').addEventListener('click', () => {
            this.stop();
        });

        // Visualization Type
        document.getElementById('visualizationType').addEventListener('change', (e) => {
            this.settings.visualizationType = e.target.value;
            this.updateVisualizationMode();
        });

        // Color Controls
        document.getElementById('primaryColor').addEventListener('input', (e) => {
            this.settings.primaryColor = e.target.value;
        });

        document.getElementById('secondaryColor').addEventListener('input', (e) => {
            this.settings.secondaryColor = e.target.value;
        });

        // Brightness
        document.getElementById('brightness').addEventListener('input', (e) => {
            this.settings.brightness = parseInt(e.target.value);
            document.getElementById('brightnessValue').textContent = e.target.value + '%';
        });

        // Blur
        document.getElementById('blur').addEventListener('input', (e) => {
            this.settings.blur = parseInt(e.target.value);
            document.getElementById('blurValue').textContent = e.target.value;
        });

        // FFT Size
        document.getElementById('fftSize').addEventListener('change', (e) => {
            this.settings.fftSize = parseInt(e.target.value);
            document.getElementById('fftValue').textContent = e.target.value;
            if (this.analyser) {
                this.analyser.fftSize = this.settings.fftSize;
                this.bufferLength = this.analyser.frequencyBinCount;
                this.dataArray = new Uint8Array(this.bufferLength);
            }
        });

        // Smoothing
        document.getElementById('smoothing').addEventListener('input', (e) => {
            this.settings.smoothing = parseFloat(e.target.value);
            document.getElementById('smoothingValue').textContent = e.target.value;
            if (this.analyser) {
                this.analyser.smoothingTimeConstant = this.settings.smoothing;
            }
        });

        // Bar Count
        document.getElementById('barCount').addEventListener('input', (e) => {
            this.settings.barCount = parseInt(e.target.value);
            document.getElementById('barCountValue').textContent = e.target.value;
        });

        // Sensitivity
        document.getElementById('sensitivity').addEventListener('input', (e) => {
            this.settings.sensitivity = parseInt(e.target.value);
            document.getElementById('sensitivityValue').textContent = e.target.value + '%';
        });

        // Rotation Speed
        document.getElementById('rotationSpeed').addEventListener('input', (e) => {
            this.settings.rotationSpeed = parseFloat(e.target.value);
            document.getElementById('rotationSpeedValue').textContent = e.target.value;
        });

        // Zoom Speed
        document.getElementById('zoomSpeed').addEventListener('input', (e) => {
            this.settings.zoomSpeed = parseFloat(e.target.value);
            document.getElementById('zoomSpeedValue').textContent = e.target.value;
        });

        // Mirror Mode
        document.getElementById('mirrorMode').addEventListener('change', (e) => {
            this.settings.mirrorMode = e.target.checked;
        });

        // Rainbow Mode
        document.getElementById('rainbowMode').addEventListener('change', (e) => {
            this.settings.rainbowMode = e.target.checked;
        });

        // Trail Effect
        document.getElementById('trail').addEventListener('input', (e) => {
            this.settings.trail = parseFloat(e.target.value);
            document.getElementById('trailValue').textContent = e.target.value;
        });

        // Shadow
        document.getElementById('shadow').addEventListener('input', (e) => {
            this.settings.shadow = parseInt(e.target.value);
            document.getElementById('shadowValue').textContent = e.target.value;
        });

        // Fullscreen Button
        document.getElementById('fullscreenButton').addEventListener('click', () => {
            this.toggleFullscreen();
        });

        // Screenshot Button
        document.getElementById('screenshotButton').addEventListener('click', () => {
            this.takeScreenshot();
        });

        // Record Button
        document.getElementById('recordButton').addEventListener('click', () => {
            this.startRecording();
        });

        // Stop Record Button
        document.getElementById('stopRecordButton').addEventListener('click', () => {
            this.stopRecording();
        });

        // Preset Buttons
        document.querySelectorAll('.preset-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.applyPreset(e.target.dataset.preset);
            });
        });
    }

    async loadAudioFile(file) {
        if (!file) return;

        // Display file name
        document.getElementById('fileName').textContent = file.name;

        // Stop current playback
        this.stop();

        // Create audio element
        this.audioElement = new Audio();
        this.audioElement.src = URL.createObjectURL(file);

        // Setup audio context
        await this.setupAudioContext();
        this.source = this.audioContext.createMediaElementSource(this.audioElement);
        this.source.connect(this.analyser);
        this.analyser.connect(this.audioContext.destination);

        // Play audio
        this.audioElement.play();
        this.start();
    }

    async startMicrophone() {
        try {
            // Stop current playback
            this.stop();

            // Get microphone stream
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            this.microphone = stream;

            // Setup audio context
            await this.setupAudioContext();
            this.source = this.audioContext.createMediaStreamSource(stream);
            this.source.connect(this.analyser);

            // Start visualization
            this.start();
            document.getElementById('fileName').textContent = 'กำลังใช้ไมโครโฟน';
        } catch (error) {
            alert('ไม่สามารถเข้าถึงไมโครโฟนได้: ' + error.message);
        }
    }

    async setupAudioContext() {
        if (!this.audioContext) {
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
        }

        if (this.audioContext.state === 'suspended') {
            await this.audioContext.resume();
        }

        if (!this.analyser) {
            this.analyser = this.audioContext.createAnalyser();
            this.analyser.fftSize = this.settings.fftSize;
            this.analyser.smoothingTimeConstant = this.settings.smoothing;
            this.bufferLength = this.analyser.frequencyBinCount;
            this.dataArray = new Uint8Array(this.bufferLength);
        }
    }

    start() {
        this.isPlaying = true;
        document.getElementById('stopButton').disabled = false;
        document.getElementById('canvasOverlay').style.display = 'none';
        this.updateVisualizationMode();
        this.animate();
    }

    stop() {
        this.isPlaying = false;
        document.getElementById('stopButton').disabled = true;
        document.getElementById('canvasOverlay').style.display = 'block';

        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
        }

        if (this.audioElement) {
            this.audioElement.pause();
            this.audioElement = null;
        }

        if (this.microphone) {
            this.microphone.getTracks().forEach(track => track.stop());
            this.microphone = null;
        }

        if (this.source) {
            this.source.disconnect();
            this.source = null;
        }

        // Clear canvas
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
    }

    updateVisualizationMode() {
        const is3D = this.settings.visualizationType.startsWith('3d-');

        if (is3D) {
            this.canvas.style.display = 'none';
            this.container3D.style.display = 'block';
            this.setup3DVisualization();
        } else {
            this.canvas.style.display = 'block';
            this.container3D.style.display = 'none';
        }
    }

    setup3DVisualization() {
        // Clear existing meshes
        this.meshes.forEach(mesh => this.scene.remove(mesh));
        this.meshes = [];

        const type = this.settings.visualizationType;

        if (type === '3d-bars') {
            // Create 3D bars
            const barCount = this.settings.barCount;
            const radius = 30;

            for (let i = 0; i < barCount; i++) {
                const geometry = new THREE.BoxGeometry(0.5, 1, 0.5);
                const material = new THREE.MeshPhongMaterial({
                    color: new THREE.Color(this.settings.primaryColor),
                    emissive: new THREE.Color(this.settings.primaryColor),
                    emissiveIntensity: 0.5
                });
                const mesh = new THREE.Mesh(geometry, material);

                const angle = (i / barCount) * Math.PI * 2;
                mesh.position.x = Math.cos(angle) * radius;
                mesh.position.z = Math.sin(angle) * radius;
                mesh.rotation.y = -angle;

                this.scene.add(mesh);
                this.meshes.push(mesh);
            }
        } else if (type === '3d-sphere') {
            // Create sphere with vertices
            const geometry = new THREE.SphereGeometry(20, 32, 32);
            const material = new THREE.MeshPhongMaterial({
                color: new THREE.Color(this.settings.primaryColor),
                wireframe: true,
                emissive: new THREE.Color(this.settings.primaryColor),
                emissiveIntensity: 0.5
            });
            const mesh = new THREE.Mesh(geometry, material);
            this.scene.add(mesh);
            this.meshes.push(mesh);
        } else if (type === '3d-tunnel') {
            // Create tunnel effect
            for (let i = 0; i < 20; i++) {
                const geometry = new THREE.TorusGeometry(10 + i * 2, 1, 16, 100);
                const material = new THREE.MeshPhongMaterial({
                    color: new THREE.Color(this.settings.primaryColor),
                    emissive: new THREE.Color(this.settings.primaryColor),
                    emissiveIntensity: 0.5,
                    transparent: true,
                    opacity: 0.7
                });
                const mesh = new THREE.Mesh(geometry, material);
                mesh.position.z = -i * 3;
                this.scene.add(mesh);
                this.meshes.push(mesh);
            }
        }
    }

    animate() {
        if (!this.isPlaying) return;

        this.animationId = requestAnimationFrame(() => this.animate());

        // Get frequency data
        this.analyser.getByteFrequencyData(this.dataArray);

        // Update rotation and zoom
        this.rotation += this.settings.rotationSpeed * 0.01;
        this.zoom = 1 + Math.sin(Date.now() * this.settings.zoomSpeed * 0.001) * 0.2;

        // Render based on type
        const type = this.settings.visualizationType;

        if (type.startsWith('3d-')) {
            this.render3D();
        } else {
            this.render2D();
        }
    }

    render2D() {
        const width = this.canvas.width;
        const height = this.canvas.height;

        // Apply blur
        if (this.settings.blur > 0) {
            this.ctx.filter = `blur(${this.settings.blur}px)`;
        } else {
            this.ctx.filter = 'none';
        }

        // Clear canvas with trail effect
        this.ctx.fillStyle = `rgba(10, 10, 10, ${this.settings.trail})`;
        this.ctx.fillRect(0, 0, width, height);

        // Save context for mirror mode
        if (this.settings.mirrorMode) {
            this.ctx.save();
        }

        const type = this.settings.visualizationType;

        switch (type) {
            case 'bars':
                this.renderBars();
                break;
            case 'circular':
                this.renderCircular();
                break;
            case 'waveform':
                this.renderWaveform();
                break;
            case 'particles':
                this.renderParticles();
                break;
            case 'spiral':
                this.renderSpiral();
                break;
            case 'radial':
                this.renderRadial();
                break;
            case 'symmetrical':
                this.renderSymmetrical();
                break;
            case 'double-helix':
                this.renderDoubleHelix();
                break;
            case 'flower':
                this.renderFlower();
                break;
            case 'lightning':
                this.renderLightning();
                break;
            case 'dna':
                this.renderDNA();
                break;
            case 'matrix':
                this.renderMatrix();
                break;
            case 'fireworks':
                this.renderFireworks();
                break;
            case 'crystal':
                this.renderCrystal();
                break;
            case 'gravity':
                this.renderGravity();
                break;
        }

        // Apply mirror effect
        if (this.settings.mirrorMode) {
            this.ctx.restore();
            this.ctx.save();
            this.ctx.scale(1, -1);
            this.ctx.translate(0, -height);
            this.ctx.globalAlpha = 0.5;
            this.ctx.drawImage(this.canvas, 0, 0);
            this.ctx.restore();
        }
    }

    renderBars() {
        const width = this.canvas.width;
        const height = this.canvas.height;
        const barCount = this.settings.barCount;
        const barWidth = (width / barCount) * 0.8;
        const gap = (width / barCount) * 0.2;

        for (let i = 0; i < barCount; i++) {
            const index = Math.floor((i / barCount) * this.bufferLength);
            const value = this.dataArray[index];
            const percent = (value / 255) * (this.settings.sensitivity / 100);
            const barHeight = percent * height * 0.8;

            const x = i * (barWidth + gap);
            const y = height - barHeight;

            // Create gradient
            const gradient = this.ctx.createLinearGradient(x, y, x, height);
            gradient.addColorStop(0, this.settings.primaryColor);
            gradient.addColorStop(1, this.settings.secondaryColor);

            this.ctx.fillStyle = gradient;
            this.ctx.fillRect(x, y, barWidth, barHeight);

            // Add glow effect
            this.ctx.shadowBlur = 20 * (this.settings.brightness / 100);
            this.ctx.shadowColor = this.settings.primaryColor;
        }

        this.ctx.shadowBlur = 0;
    }

    renderCircular() {
        const width = this.canvas.width;
        const height = this.canvas.height;
        const centerX = width / 2;
        const centerY = height / 2;
        const barCount = this.settings.barCount;
        const radius = Math.min(width, height) * 0.3;

        for (let i = 0; i < barCount; i++) {
            const index = Math.floor((i / barCount) * this.bufferLength);
            const value = this.dataArray[index];
            const percent = (value / 255) * (this.settings.sensitivity / 100);
            const barHeight = percent * radius * 0.8;

            const angle = (i / barCount) * Math.PI * 2 + this.rotation;
            const x1 = centerX + Math.cos(angle) * radius;
            const y1 = centerY + Math.sin(angle) * radius;
            const x2 = centerX + Math.cos(angle) * (radius + barHeight);
            const y2 = centerY + Math.sin(angle) * (radius + barHeight);

            // Create gradient
            const hue = (i / barCount) * 360;
            this.ctx.strokeStyle = `hsl(${hue}, 80%, ${50 * (this.settings.brightness / 100)}%)`;
            this.ctx.lineWidth = 3;

            this.ctx.beginPath();
            this.ctx.moveTo(x1, y1);
            this.ctx.lineTo(x2, y2);
            this.ctx.stroke();

            // Add glow
            this.ctx.shadowBlur = 15 * (this.settings.brightness / 100);
            this.ctx.shadowColor = this.ctx.strokeStyle;
        }

        this.ctx.shadowBlur = 0;
    }

    renderWaveform() {
        const width = this.canvas.width;
        const height = this.canvas.height;

        this.analyser.getByteTimeDomainData(this.dataArray);

        this.ctx.lineWidth = 3;
        this.ctx.strokeStyle = this.settings.primaryColor;
        this.ctx.shadowBlur = 20 * (this.settings.brightness / 100);
        this.ctx.shadowColor = this.settings.primaryColor;

        this.ctx.beginPath();

        const sliceWidth = width / this.bufferLength;
        let x = 0;

        for (let i = 0; i < this.bufferLength; i++) {
            const v = this.dataArray[i] / 128.0;
            const y = (v * height * 0.5 * (this.settings.sensitivity / 100)) * this.zoom;

            if (i === 0) {
                this.ctx.moveTo(x, y);
            } else {
                this.ctx.lineTo(x, y);
            }

            x += sliceWidth;
        }

        this.ctx.stroke();
        this.ctx.shadowBlur = 0;
    }

    renderParticles() {
        const width = this.canvas.width;
        const height = this.canvas.height;
        const centerX = width / 2;
        const centerY = height / 2;
        const particleCount = this.settings.barCount;

        for (let i = 0; i < particleCount; i++) {
            const index = Math.floor((i / particleCount) * this.bufferLength);
            const value = this.dataArray[index];
            const percent = (value / 255) * (this.settings.sensitivity / 100);

            const angle = (i / particleCount) * Math.PI * 2 + this.rotation;
            const distance = percent * Math.min(width, height) * 0.4 * this.zoom;
            const x = centerX + Math.cos(angle) * distance;
            const y = centerY + Math.sin(angle) * distance;
            const size = 5 + percent * 10;

            // Create gradient
            const gradient = this.ctx.createRadialGradient(x, y, 0, x, y, size);
            gradient.addColorStop(0, this.settings.primaryColor);
            gradient.addColorStop(1, 'transparent');

            this.ctx.fillStyle = gradient;
            this.ctx.beginPath();
            this.ctx.arc(x, y, size, 0, Math.PI * 2);
            this.ctx.fill();

            // Add glow
            this.ctx.shadowBlur = 20 * (this.settings.brightness / 100);
            this.ctx.shadowColor = this.settings.primaryColor;
        }

        this.ctx.shadowBlur = 0;
    }

    renderSpiral() {
        const width = this.canvas.width;
        const height = this.canvas.height;
        const centerX = width / 2;
        const centerY = height / 2;
        const barCount = this.settings.barCount;

        this.ctx.beginPath();

        for (let i = 0; i < barCount; i++) {
            const index = Math.floor((i / barCount) * this.bufferLength);
            const value = this.dataArray[index];
            const percent = (value / 255) * (this.settings.sensitivity / 100);

            const angle = (i / barCount) * Math.PI * 6 + this.rotation;
            const distance = (i / barCount) * Math.min(width, height) * 0.4 + percent * 50;
            const x = centerX + Math.cos(angle) * distance * this.zoom;
            const y = centerY + Math.sin(angle) * distance * this.zoom;

            if (i === 0) {
                this.ctx.moveTo(x, y);
            } else {
                this.ctx.lineTo(x, y);
            }
        }

        this.ctx.strokeStyle = this.settings.primaryColor;
        this.ctx.lineWidth = 3;
        this.ctx.shadowBlur = 20 * (this.settings.brightness / 100);
        this.ctx.shadowColor = this.settings.primaryColor;
        this.ctx.stroke();
        this.ctx.shadowBlur = 0;
    }

    renderRadial() {
        const width = this.canvas.width;
        const height = this.canvas.height;
        const centerX = width / 2;
        const centerY = height / 2;
        const barCount = this.settings.barCount;

        for (let i = 0; i < barCount; i++) {
            const index = Math.floor((i / barCount) * this.bufferLength);
            const value = this.dataArray[index];
            const percent = (value / 255) * (this.settings.sensitivity / 100);

            const angle = (i / barCount) * Math.PI * 2 + this.rotation;
            const distance = percent * Math.min(width, height) * 0.4 * this.zoom;

            // Draw line from center
            this.ctx.beginPath();
            this.ctx.moveTo(centerX, centerY);
            this.ctx.lineTo(
                centerX + Math.cos(angle) * distance,
                centerY + Math.sin(angle) * distance
            );

            const hue = (i / barCount) * 360;
            this.ctx.strokeStyle = `hsl(${hue}, 80%, ${50 * (this.settings.brightness / 100)}%)`;
            this.ctx.lineWidth = 2;
            this.ctx.shadowBlur = 10 * (this.settings.brightness / 100);
            this.ctx.shadowColor = this.ctx.strokeStyle;
            this.ctx.stroke();
        }

        this.ctx.shadowBlur = 0;
    }

    renderSymmetrical() {
        const width = this.canvas.width;
        const height = this.canvas.height;
        const barCount = this.settings.barCount / 2;
        const barWidth = (width / (barCount * 2)) * 0.8;

        for (let i = 0; i < barCount; i++) {
            const index = Math.floor((i / barCount) * this.bufferLength);
            const value = this.dataArray[index];
            const percent = (value / 255) * (this.settings.sensitivity / 100);
            const barHeight = percent * height * 0.4;

            const x1 = width/2 - (i * barWidth);
            const x2 = width/2 + (i * barWidth);
            const y = height/2 - barHeight/2;

            const color = this.getColor(i / barCount);
            this.ctx.fillStyle = color;
            this.ctx.fillRect(x1, y, barWidth * 0.9, barHeight);
            this.ctx.fillRect(x2, y, barWidth * 0.9, barHeight);

            this.ctx.shadowBlur = this.settings.shadow * (this.settings.brightness / 100);
            this.ctx.shadowColor = color;
        }
        this.ctx.shadowBlur = 0;
    }

    renderDoubleHelix() {
        const width = this.canvas.width;
        const height = this.canvas.height;
        const centerX = width / 2;
        const centerY = height / 2;
        const barCount = this.settings.barCount;

        for (let i = 0; i < barCount; i++) {
            const index = Math.floor((i / barCount) * this.bufferLength);
            const value = this.dataArray[index];
            const percent = (value / 255) * (this.settings.sensitivity / 100);

            const angle = (i / barCount) * Math.PI * 4 + this.rotation;
            const radius = 100;
            const x1 = centerX + Math.cos(angle) * radius * (1 + percent);
            const y1 = centerY + (i / barCount - 0.5) * height * 0.8;
            const x2 = centerX - Math.cos(angle) * radius * (1 + percent);
            const y2 = y1;

            const color = this.getColor(i / barCount);
            const size = 5 + percent * 10;

            this.ctx.fillStyle = color;
            this.ctx.beginPath();
            this.ctx.arc(x1, y1, size, 0, Math.PI * 2);
            this.ctx.fill();

            this.ctx.beginPath();
            this.ctx.arc(x2, y2, size, 0, Math.PI * 2);
            this.ctx.fill();

            this.ctx.shadowBlur = this.settings.shadow * (this.settings.brightness / 100);
            this.ctx.shadowColor = color;
        }
        this.ctx.shadowBlur = 0;
    }

    renderFlower() {
        const width = this.canvas.width;
        const height = this.canvas.height;
        const centerX = width / 2;
        const centerY = height / 2;
        const barCount = this.settings.barCount;
        const petals = 8;

        for (let i = 0; i < barCount; i++) {
            const index = Math.floor((i / barCount) * this.bufferLength);
            const value = this.dataArray[index];
            const percent = (value / 255) * (this.settings.sensitivity / 100);

            const angle = (i / barCount) * Math.PI * 2 + this.rotation;
            const petalAngle = Math.sin(angle * petals) * 0.5 + 0.5;
            const radius = 50 + petalAngle * 100 + percent * 100;

            const x = centerX + Math.cos(angle) * radius * this.zoom;
            const y = centerY + Math.sin(angle) * radius * this.zoom;

            const color = this.getColor(i / barCount);
            const size = 3 + percent * 8;

            const gradient = this.ctx.createRadialGradient(x, y, 0, x, y, size);
            gradient.addColorStop(0, color);
            gradient.addColorStop(1, 'transparent');

            this.ctx.fillStyle = gradient;
            this.ctx.beginPath();
            this.ctx.arc(x, y, size, 0, Math.PI * 2);
            this.ctx.fill();

            this.ctx.shadowBlur = this.settings.shadow * (this.settings.brightness / 100);
            this.ctx.shadowColor = color;
        }
        this.ctx.shadowBlur = 0;
    }

    renderLightning() {
        const width = this.canvas.width;
        const height = this.canvas.height;
        const centerX = width / 2;
        const barCount = Math.min(this.settings.barCount, 32);

        for (let i = 0; i < barCount; i++) {
            const index = Math.floor((i / barCount) * this.bufferLength);
            const value = this.dataArray[index];
            const percent = (value / 255) * (this.settings.sensitivity / 100);

            if (percent > 0.3) {
                const startX = centerX + (Math.random() - 0.5) * width * 0.3;
                const startY = 0;
                let x = startX;
                let y = startY;

                this.ctx.beginPath();
                this.ctx.moveTo(x, y);

                const segments = Math.floor(5 + percent * 10);
                for (let j = 0; j < segments; j++) {
                    x += (Math.random() - 0.5) * 50;
                    y += height / segments;
                    this.ctx.lineTo(x, y);
                }

                const color = this.getColor(Math.random());
                this.ctx.strokeStyle = color;
                this.ctx.lineWidth = 2 + percent * 4;
                this.ctx.shadowBlur = this.settings.shadow * 2 * (this.settings.brightness / 100);
                this.ctx.shadowColor = color;
                this.ctx.stroke();
            }
        }
        this.ctx.shadowBlur = 0;
    }

    renderDNA() {
        const width = this.canvas.width;
        const height = this.canvas.height;
        const centerX = width / 2;
        const barCount = this.settings.barCount;

        this.ctx.beginPath();
        for (let i = 0; i < barCount; i++) {
            const index = Math.floor((i / barCount) * this.bufferLength);
            const value = this.dataArray[index];
            const percent = (value / 255) * (this.settings.sensitivity / 100);

            const y = (i / barCount) * height;
            const angle = (i / barCount) * Math.PI * 4 + this.rotation;
            const radius = 50 + percent * 50;

            const x1 = centerX + Math.cos(angle) * radius;
            const x2 = centerX - Math.cos(angle) * radius;

            const color = this.getColor(i / barCount);

            // Draw backbone
            if (i > 0) {
                this.ctx.strokeStyle = color;
                this.ctx.lineWidth = 3;
                this.ctx.lineTo(x1, y);
                this.ctx.stroke();
                this.ctx.beginPath();
                this.ctx.moveTo(x1, y);
            } else {
                this.ctx.moveTo(x1, y);
            }

            // Draw rungs
            this.ctx.strokeStyle = color;
            this.ctx.lineWidth = 2;
            this.ctx.beginPath();
            this.ctx.moveTo(x1, y);
            this.ctx.lineTo(x2, y);
            this.ctx.stroke();

            this.ctx.shadowBlur = this.settings.shadow * (this.settings.brightness / 100);
            this.ctx.shadowColor = color;
        }
        this.ctx.shadowBlur = 0;
    }

    renderMatrix() {
        // Simple matrix rain effect
        const barCount = Math.min(this.settings.barCount, 50);
        const width = this.canvas.width;
        const height = this.canvas.height;
        const columnWidth = width / barCount;

        for (let i = 0; i < barCount; i++) {
            const index = Math.floor((i / barCount) * this.bufferLength);
            const value = this.dataArray[index];
            const percent = (value / 255) * (this.settings.sensitivity / 100);

            const x = i * columnWidth;
            const segments = Math.floor(3 + percent * 10);

            for (let j = 0; j < segments; j++) {
                const y = (j / segments + (this.rotation % 1)) * height;
                const alpha = 1 - j / segments;
                const color = this.getColor(i / barCount);

                this.ctx.fillStyle = color.replace(')', `, ${alpha})`).replace('rgb', 'rgba');
                this.ctx.font = `${12 + percent * 8}px monospace`;
                this.ctx.fillText(String.fromCharCode(0x30A0 + Math.random() * 96), x, y);
            }
        }
    }

    renderFireworks() {
        if (!this.particles.length || Math.random() > 0.95) {
            const barCount = this.settings.barCount;
            for (let i = 0; i < barCount; i++) {
                const index = Math.floor((i / barCount) * this.bufferLength);
                const value = this.dataArray[index];
                const percent = (value / 255) * (this.settings.sensitivity / 100);

                if (percent > 0.6) {
                    const width = this.canvas.width;
                    const height = this.canvas.height;

                    for (let j = 0; j < 20; j++) {
                        this.particles.push({
                            x: width / 2,
                            y: height / 2,
                            vx: (Math.random() - 0.5) * 10 * percent,
                            vy: (Math.random() - 0.5) * 10 * percent,
                            life: 1,
                            color: this.getColor(Math.random())
                        });
                    }
                }
            }
        }

        this.particles = this.particles.filter(p => {
            p.x += p.vx;
            p.y += p.vy;
            p.vy += 0.2;
            p.life -= 0.02;

            if (p.life > 0) {
                this.ctx.fillStyle = p.color.replace(')', `, ${p.life})`).replace('rgb', 'rgba');
                this.ctx.beginPath();
                this.ctx.arc(p.x, p.y, 3, 0, Math.PI * 2);
                this.ctx.fill();
                return true;
            }
            return false;
        });
    }

    renderCrystal() {
        const width = this.canvas.width;
        const height = this.canvas.height;
        const centerX = width / 2;
        const centerY = height / 2;
        const barCount = this.settings.barCount;
        const sides = 6;

        for (let i = 0; i < barCount; i++) {
            const index = Math.floor((i / barCount) * this.bufferLength);
            const value = this.dataArray[index];
            const percent = (value / 255) * (this.settings.sensitivity / 100);

            const layer = Math.floor(i / sides);
            const angle = (i % sides) / sides * Math.PI * 2 + this.rotation;
            const radius = (layer + 1) * 20 + percent * 50;

            const x = centerX + Math.cos(angle) * radius * this.zoom;
            const y = centerY + Math.sin(angle) * radius * this.zoom;

            const color = this.getColor(i / barCount);

            this.ctx.beginPath();
            this.ctx.moveTo(centerX, centerY);
            this.ctx.lineTo(x, y);
            this.ctx.strokeStyle = color;
            this.ctx.lineWidth = 2 + percent * 3;
            this.ctx.shadowBlur = this.settings.shadow * (this.settings.brightness / 100);
            this.ctx.shadowColor = color;
            this.ctx.stroke();
        }
        this.ctx.shadowBlur = 0;
    }

    renderGravity() {
        const width = this.canvas.width;
        const height = this.canvas.height;
        const centerX = width / 2;
        const centerY = height / 2;
        const barCount = this.settings.barCount;

        for (let i = 0; i < barCount; i++) {
            const index = Math.floor((i / barCount) * this.bufferLength);
            const value = this.dataArray[index];
            const percent = (value / 255) * (this.settings.sensitivity / 100);

            const angle = (i / barCount) * Math.PI * 2 + this.rotation;
            const baseRadius = 100;
            const gravity = Math.sin(Date.now() * 0.001 + i) * 50;
            const radius = baseRadius + gravity + percent * 100;

            const x = centerX + Math.cos(angle) * radius * this.zoom;
            const y = centerY + Math.sin(angle) * radius * this.zoom;

            const color = this.getColor(i / barCount);
            const size = 5 + percent * 15;

            const gradient = this.ctx.createRadialGradient(x, y, 0, x, y, size);
            gradient.addColorStop(0, color);
            gradient.addColorStop(1, 'transparent');

            this.ctx.fillStyle = gradient;
            this.ctx.beginPath();
            this.ctx.arc(x, y, size, 0, Math.PI * 2);
            this.ctx.fill();

            this.ctx.shadowBlur = this.settings.shadow * (this.settings.brightness / 100);
            this.ctx.shadowColor = color;
        }
        this.ctx.shadowBlur = 0;
    }

    getColor(t) {
        if (this.settings.rainbowMode) {
            const hue = (t * 360 + this.rotation * 100) % 360;
            return `hsl(${hue}, 80%, ${50 * (this.settings.brightness / 100)}%)`;
        }

        const r1 = parseInt(this.settings.primaryColor.slice(1, 3), 16);
        const g1 = parseInt(this.settings.primaryColor.slice(3, 5), 16);
        const b1 = parseInt(this.settings.primaryColor.slice(5, 7), 16);
        const r2 = parseInt(this.settings.secondaryColor.slice(1, 3), 16);
        const g2 = parseInt(this.settings.secondaryColor.slice(3, 5), 16);
        const b2 = parseInt(this.settings.secondaryColor.slice(5, 7), 16);

        const r = Math.floor(r1 + (r2 - r1) * t);
        const g = Math.floor(g1 + (g2 - g1) * t);
        const b = Math.floor(b1 + (b2 - b1) * t);

        return `rgb(${r}, ${g}, ${b})`;
    }

    render3D() {
        const type = this.settings.visualizationType;

        if (type === '3d-bars') {
            const barCount = this.settings.barCount;

            this.meshes.forEach((mesh, i) => {
                const index = Math.floor((i / barCount) * this.bufferLength);
                const value = this.dataArray[index];
                const percent = (value / 255) * (this.settings.sensitivity / 100);

                mesh.scale.y = 1 + percent * 20 * this.zoom;
                mesh.position.y = mesh.scale.y * 0.5;

                // Update color
                const hue = (i / barCount) * 360;
                mesh.material.color.setHSL(hue / 360, 0.8, 0.5 * (this.settings.brightness / 100));
                mesh.material.emissive.setHSL(hue / 360, 0.8, 0.3 * (this.settings.brightness / 100));
            });

            // Rotate camera
            this.camera.position.x = Math.cos(this.rotation) * 50;
            this.camera.position.z = Math.sin(this.rotation) * 50;
            this.camera.lookAt(0, 0, 0);

        } else if (type === '3d-sphere') {
            const mesh = this.meshes[0];
            const avgValue = this.dataArray.reduce((a, b) => a + b, 0) / this.dataArray.length;
            const percent = (avgValue / 255) * (this.settings.sensitivity / 100);

            mesh.scale.setScalar(1 + percent * 0.5 * this.zoom);
            mesh.rotation.x += 0.01 * this.settings.rotationSpeed;
            mesh.rotation.y += 0.01 * this.settings.rotationSpeed;

            mesh.material.color.set(this.settings.primaryColor);
            mesh.material.emissive.set(this.settings.primaryColor);
            mesh.material.emissiveIntensity = 0.5 * (this.settings.brightness / 100);

        } else if (type === '3d-tunnel') {
            this.meshes.forEach((mesh, i) => {
                const index = Math.floor((i / this.meshes.length) * this.bufferLength);
                const value = this.dataArray[index];
                const percent = (value / 255) * (this.settings.sensitivity / 100);

                mesh.scale.setScalar(1 + percent * this.zoom);
                mesh.rotation.z += 0.005 * this.settings.rotationSpeed;

                const hue = (i / this.meshes.length) * 360;
                mesh.material.color.setHSL(hue / 360, 0.8, 0.5 * (this.settings.brightness / 100));
                mesh.material.emissive.setHSL(hue / 360, 0.8, 0.3 * (this.settings.brightness / 100));
            });

            this.camera.position.z = 50 + Math.sin(Date.now() * 0.001) * 10;
        }

        this.renderer.render(this.scene, this.camera);
    }

    applyPreset(preset) {
        const presets = {
            neon: { primaryColor: '#f093fb', secondaryColor: '#f5576c', brightness: 150 },
            ocean: { primaryColor: '#4facfe', secondaryColor: '#00f2fe', brightness: 120 },
            sunset: { primaryColor: '#fa709a', secondaryColor: '#fee140', brightness: 130 },
            forest: { primaryColor: '#30cfd0', secondaryColor: '#330867', brightness: 110 },
            fire: { primaryColor: '#ff0844', secondaryColor: '#ffb199', brightness: 140 },
            galaxy: { primaryColor: '#667eea', secondaryColor: '#764ba2', brightness: 100 },
            cyber: { primaryColor: '#00ff88', secondaryColor: '#00d4ff', brightness: 160 },
            aurora: { primaryColor: '#a8edea', secondaryColor: '#fed6e3', brightness: 110 },
            ice: { primaryColor: '#e0c3fc', secondaryColor: '#8ec5fc', brightness: 120 },
            lava: { primaryColor: '#ff9a56', secondaryColor: '#ff4e50', brightness: 150 },
            electric: { primaryColor: '#f6d365', secondaryColor: '#fda085', brightness: 140 },
            space: { primaryColor: '#2c3e50', secondaryColor: '#4ca1af', brightness: 100 }
        };

        const config = presets[preset];
        if (config) {
            this.settings.primaryColor = config.primaryColor;
            this.settings.secondaryColor = config.secondaryColor;
            this.settings.brightness = config.brightness;

            document.getElementById('primaryColor').value = config.primaryColor;
            document.getElementById('secondaryColor').value = config.secondaryColor;
            document.getElementById('brightness').value = config.brightness;
            document.getElementById('brightnessValue').textContent = config.brightness + '%';
        }
    }

    toggleFullscreen() {
        const container = document.querySelector('.canvas-container');
        if (!document.fullscreenElement) {
            container.requestFullscreen().catch(err => {
                alert(`Error attempting to enable fullscreen: ${err.message}`);
            });
        } else {
            document.exitFullscreen();
        }
    }

    takeScreenshot() {
        const canvas = this.settings.visualizationType.startsWith('3d-')
            ? this.renderer.domElement
            : this.canvas;

        canvas.toBlob((blob) => {
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `audio-spectrum-${Date.now()}.png`;
            a.click();
            URL.revokeObjectURL(url);
        });
    }

    async startRecording() {
        try {
            const canvas = this.settings.visualizationType.startsWith('3d-')
                ? this.renderer.domElement
                : this.canvas;

            const stream = canvas.captureStream(30); // 30 FPS

            // Add audio if available
            if (this.audioElement) {
                const audioContext = new AudioContext();
                const audioSource = audioContext.createMediaElementSource(this.audioElement);
                const dest = audioContext.createMediaStreamDestination();
                audioSource.connect(dest);
                audioSource.connect(audioContext.destination);

                stream.addTrack(dest.stream.getAudioTracks()[0]);
            }

            this.mediaRecorder = new MediaRecorder(stream, {
                mimeType: 'video/webm;codecs=vp9',
                videoBitsPerSecond: 2500000
            });

            this.recordedChunks = [];

            this.mediaRecorder.ondataavailable = (event) => {
                if (event.data.size > 0) {
                    this.recordedChunks.push(event.data);
                }
            };

            this.mediaRecorder.onstop = () => {
                const blob = new Blob(this.recordedChunks, { type: 'video/webm' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `audio-spectrum-${Date.now()}.webm`;
                a.click();
                URL.revokeObjectURL(url);
            };

            this.mediaRecorder.start();
            document.getElementById('recordButton').style.display = 'none';
            document.getElementById('stopRecordButton').style.display = 'block';
            document.getElementById('stopRecordButton').disabled = false;

        } catch (error) {
            alert('ไม่สามารถบันทึกวิดีโอได้: ' + error.message);
        }
    }

    stopRecording() {
        if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
            this.mediaRecorder.stop();
            document.getElementById('recordButton').style.display = 'block';
            document.getElementById('stopRecordButton').style.display = 'none';
        }
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    const visualizer = new AudioSpectrumVisualizer();
});
    </script>
</body>
</html>
