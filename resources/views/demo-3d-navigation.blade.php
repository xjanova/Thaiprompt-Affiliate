<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3D Navigation Demo - Thai Prompt</title>
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
            overflow: hidden;
            min-height: 100vh;
        }

        #canvas-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
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
            pointer-events: auto;
        }

        .header h1 {
            font-size: 2rem;
            font-weight: 900;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
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
            pointer-events: auto;
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
    </style>
</head>
<body>
    <div id="canvas-container"></div>

    <div class="ui-overlay">
        <a href="{{ route('demo.game-selector') }}" class="back-button">← กลับ</a>

        <div class="header">
            <h1>🧭 3D Navigation Demo</h1>
            <p>สำรวจโลก 3D ด้วยระบบนำทางที่ทันสมัย</p>
        </div>

        <div class="controls">
            <p>🖱️ ลากเมาส์เพื่อหมุนกล้อง</p>
            <p>⌨️ ใช้ WASD หรือลูกศรเพื่อเคลื่อนที่</p>
            <p>🔍 Scroll เพื่อซูมเข้า-ออก</p>
        </div>
    </div>

    <script>
        // Scene setup
        const scene = new THREE.Scene();
        scene.background = new THREE.Color(0x0a0a1a);
        scene.fog = new THREE.Fog(0x0a0a1a, 10, 50);

        const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
        camera.position.set(0, 2, 5);

        const renderer = new THREE.WebGLRenderer({ antialias: true });
        renderer.setSize(window.innerWidth, window.innerHeight);
        renderer.shadowMap.enabled = true;
        document.getElementById('canvas-container').appendChild(renderer.domElement);

        // Lighting
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
        scene.add(ambientLight);

        const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
        directionalLight.position.set(5, 10, 5);
        directionalLight.castShadow = true;
        scene.add(directionalLight);

        // Ground
        const groundGeometry = new THREE.PlaneGeometry(50, 50);
        const groundMaterial = new THREE.MeshStandardMaterial({
            color: 0x1a1a2e,
            roughness: 0.8,
            metalness: 0.2
        });
        const ground = new THREE.Mesh(groundGeometry, groundMaterial);
        ground.rotation.x = -Math.PI / 2;
        ground.receiveShadow = true;
        scene.add(ground);

        // Create grid
        const gridHelper = new THREE.GridHelper(50, 50, 0x667eea, 0x333344);
        scene.add(gridHelper);

        // Create some 3D objects
        const objects = [];
        for (let i = 0; i < 10; i++) {
            const geometry = new THREE.BoxGeometry(1, 2, 1);
            const material = new THREE.MeshStandardMaterial({
                color: Math.random() * 0xffffff,
                roughness: 0.5,
                metalness: 0.5
            });
            const cube = new THREE.Mesh(geometry, material);

            cube.position.x = (Math.random() - 0.5) * 20;
            cube.position.y = 1;
            cube.position.z = (Math.random() - 0.5) * 20;

            cube.castShadow = true;
            cube.receiveShadow = true;

            scene.add(cube);
            objects.push(cube);
        }

        // Camera controls
        let mouseX = 0, mouseY = 0;
        let targetX = 0, targetY = 0;

        document.addEventListener('mousemove', (event) => {
            mouseX = (event.clientX / window.innerWidth) * 2 - 1;
            mouseY = -(event.clientY / window.innerHeight) * 2 + 1;
        });

        // Keyboard controls
        const keys = {};
        const moveSpeed = 0.1;

        document.addEventListener('keydown', (e) => keys[e.key.toLowerCase()] = true);
        document.addEventListener('keyup', (e) => keys[e.key.toLowerCase()] = false);

        function updateCameraPosition() {
            if (keys['w'] || keys['arrowup']) camera.position.z -= moveSpeed;
            if (keys['s'] || keys['arrowdown']) camera.position.z += moveSpeed;
            if (keys['a'] || keys['arrowleft']) camera.position.x -= moveSpeed;
            if (keys['d'] || keys['arrowright']) camera.position.x += moveSpeed;

            camera.position.y = Math.max(camera.position.y, 1);
        }

        // Animation loop
        function animate() {
            requestAnimationFrame(animate);

            // Rotate objects
            objects.forEach((obj, index) => {
                obj.rotation.y += 0.01;
                obj.position.y = 1 + Math.sin(Date.now() * 0.001 + index) * 0.3;
            });

            // Update camera
            updateCameraPosition();
            targetX = mouseX * 0.3;
            targetY = mouseY * 0.3;
            camera.rotation.y += (targetX - camera.rotation.y) * 0.05;
            camera.rotation.x += (targetY - camera.rotation.x) * 0.05;

            renderer.render(scene, camera);
        }

        // Handle window resize
        window.addEventListener('resize', () => {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        });

        animate();
    </script>
</body>
</html>
