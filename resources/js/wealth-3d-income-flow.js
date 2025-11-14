/**
 * 3D Income Flow Visualization using Three.js
 * Shows animated money flow through different income streams
 */

import * as THREE from 'three';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls.js';

export class Wealth3DIncomeFlow {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error(`Container ${containerId} not found`);
            return;
        }

        this.options = {
            showAmounts: true,
            animationSpeed: 1.0,
            incomeData: {
                direct: 15000,
                binary: 8000,
                rank: 25000,
                sponsor: 5000,
                marketplace: 12000,
                team: 18000
            },
            ...options
        };

        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.controls = null;
        this.particles = [];
        this.streams = [];
        this.animationId = null;
        this.time = 0;

        this.init();
        this.createIncomeStreams();
        this.animate();
    }

    init() {
        // Scene
        this.scene = new THREE.Scene();
        this.scene.background = new THREE.Color(0x000510);

        // Camera
        const aspect = this.container.clientWidth / this.container.clientHeight;
        this.camera = new THREE.PerspectiveCamera(60, aspect, 0.1, 1000);
        this.camera.position.set(0, 8, 20);
        this.camera.lookAt(0, 0, 0);

        // Renderer
        this.renderer = new THREE.WebGLRenderer({
            antialias: true,
            alpha: true
        });
        this.renderer.setSize(this.container.clientWidth, this.container.clientHeight);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this.container.appendChild(this.renderer.domElement);

        // Controls
        this.controls = new OrbitControls(this.camera, this.renderer.domElement);
        this.controls.enableDamping = true;
        this.controls.dampingFactor = 0.05;
        this.controls.minDistance = 15;
        this.controls.maxDistance = 40;

        // Lights
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
        this.scene.add(ambientLight);

        const pointLight1 = new THREE.PointLight(0xffd700, 2, 50);
        pointLight1.position.set(0, 10, 0);
        this.scene.add(pointLight1);

        const pointLight2 = new THREE.PointLight(0x00ff88, 1.5, 40);
        pointLight2.position.set(10, 5, 10);
        this.scene.add(pointLight2);

        const pointLight3 = new THREE.PointLight(0xff0088, 1.5, 40);
        pointLight3.position.set(-10, 5, -10);
        this.scene.add(pointLight3);

        // Add grid
        this.addGrid();

        // Resize listener
        window.addEventListener('resize', () => this.handleResize());
    }

    addGrid() {
        const gridHelper = new THREE.GridHelper(40, 40, 0x444444, 0x222222);
        gridHelper.position.y = -5;
        this.scene.add(gridHelper);

        // Add glowing base
        const baseGeometry = new THREE.CircleGeometry(12, 64);
        const baseMaterial = new THREE.MeshBasicMaterial({
            color: 0xffd700,
            transparent: true,
            opacity: 0.1,
            side: THREE.DoubleSide
        });
        const base = new THREE.Mesh(baseGeometry, baseMaterial);
        base.rotation.x = -Math.PI / 2;
        base.position.y = -4.9;
        this.scene.add(base);
    }

    createIncomeStreams() {
        const centerY = 0;

        // Central wallet/collector
        const walletGeometry = new THREE.CylinderGeometry(1.5, 1.5, 3, 32);
        const walletMaterial = new THREE.MeshStandardMaterial({
            color: 0xffd700,
            metalness: 0.8,
            roughness: 0.2,
            emissive: 0xffd700,
            emissiveIntensity: 0.5
        });
        const wallet = new THREE.Mesh(walletGeometry, walletMaterial);
        wallet.position.y = centerY;
        this.scene.add(wallet);

        // Add glow to wallet
        const walletGlowGeometry = new THREE.CylinderGeometry(2, 2, 3.5, 32);
        const walletGlowMaterial = new THREE.MeshBasicMaterial({
            color: 0xffd700,
            transparent: true,
            opacity: 0.2,
            side: THREE.BackSide
        });
        const walletGlow = new THREE.Mesh(walletGlowGeometry, walletGlowMaterial);
        wallet.add(walletGlow);

        // Income source positions (arranged in a circle)
        const incomeStreams = [
            {
                name: 'Direct Commission',
                angle: 0,
                color: 0x4ecdc4,
                amount: this.options.incomeData.direct,
                icon: '💵'
            },
            {
                name: 'Binary Matching',
                angle: Math.PI / 3,
                color: 0xff6b6b,
                amount: this.options.incomeData.binary,
                icon: '⚖️'
            },
            {
                name: 'Rank Bonus',
                angle: 2 * Math.PI / 3,
                color: 0x95e1d3,
                amount: this.options.incomeData.rank,
                icon: '👑'
            },
            {
                name: 'Sponsor Bonus',
                angle: Math.PI,
                color: 0xf38181,
                amount: this.options.incomeData.sponsor,
                icon: '🤝'
            },
            {
                name: 'Marketplace',
                angle: 4 * Math.PI / 3,
                color: 0xfeca57,
                amount: this.options.incomeData.marketplace,
                icon: '🛒'
            },
            {
                name: 'Team Override',
                angle: 5 * Math.PI / 3,
                color: 0xa29bfe,
                amount: this.options.incomeData.team,
                icon: '👥'
            }
        ];

        const radius = 10;

        incomeStreams.forEach(stream => {
            const x = Math.cos(stream.angle) * radius;
            const z = Math.sin(stream.angle) * radius;
            const y = centerY + 5;

            // Create source node
            const sourceGeometry = new THREE.SphereGeometry(0.8, 32, 32);
            const sourceMaterial = new THREE.MeshStandardMaterial({
                color: stream.color,
                metalness: 0.6,
                roughness: 0.3,
                emissive: stream.color,
                emissiveIntensity: 0.4
            });
            const source = new THREE.Mesh(sourceGeometry, sourceMaterial);
            source.position.set(x, y, z);
            this.scene.add(source);

            // Add glow
            const glowGeometry = new THREE.SphereGeometry(1.2, 32, 32);
            const glowMaterial = new THREE.MeshBasicMaterial({
                color: stream.color,
                transparent: true,
                opacity: 0.3,
                side: THREE.BackSide
            });
            const glow = new THREE.Mesh(glowGeometry, glowMaterial);
            source.add(glow);

            // Create particle stream
            const streamData = {
                source: source.position,
                target: wallet.position,
                color: stream.color,
                name: stream.name,
                amount: stream.amount,
                particles: []
            };

            // Initialize particles for this stream
            for (let i = 0; i < 30; i++) {
                const particle = this.createParticle(stream.color);
                particle.userData = {
                    stream: streamData,
                    progress: i / 30,
                    speed: 0.01 * this.options.animationSpeed * (0.8 + Math.random() * 0.4)
                };
                streamData.particles.push(particle);
                this.particles.push(particle);
                this.scene.add(particle);
            }

            this.streams.push(streamData);

            // Add label billboard (will be added in HTML overlay)
            source.userData = {
                type: 'income-source',
                name: stream.name,
                amount: stream.amount,
                icon: stream.icon,
                color: stream.color
            };

            // Add pulsing animation to source
            source.userData.animate = () => {
                const scale = 1 + Math.sin(Date.now() * 0.003) * 0.1;
                source.scale.set(scale, scale, scale);
                glow.scale.set(scale * 0.9, scale * 0.9, scale * 0.9);
            };
        });

        // Create HTML overlay for labels
        this.createLabelsOverlay(incomeStreams, wallet);

        // Add total income display
        this.createTotalDisplay();
    }

    createParticle(color) {
        const geometry = new THREE.SphereGeometry(0.15, 16, 16);
        const material = new THREE.MeshBasicMaterial({
            color: color,
            transparent: true,
            opacity: 0.8
        });
        const particle = new THREE.Mesh(geometry, material);

        // Add trail effect
        const trailGeometry = new THREE.SphereGeometry(0.25, 16, 16);
        const trailMaterial = new THREE.MeshBasicMaterial({
            color: color,
            transparent: true,
            opacity: 0.2
        });
        const trail = new THREE.Mesh(trailGeometry, trailMaterial);
        particle.add(trail);

        return particle;
    }

    createLabelsOverlay(incomeStreams, wallet) {
        // Create overlay container
        let overlay = document.getElementById('income-flow-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'income-flow-overlay';
            overlay.style.cssText = `
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                pointer-events: none;
                z-index: 10;
            `;
            this.container.style.position = 'relative';
            this.container.appendChild(overlay);
        }

        // Create labels for each stream
        incomeStreams.forEach((stream, index) => {
            const label = document.createElement('div');
            label.className = 'income-label';
            label.id = `income-label-${index}`;
            label.innerHTML = `
                <div style="
                    background: linear-gradient(135deg, rgba(${this.hexToRgb(stream.color)}, 0.9) 0%, rgba(${this.hexToRgb(stream.color)}, 0.7) 100%);
                    color: white;
                    padding: 8px 12px;
                    border-radius: 8px;
                    font-size: 12px;
                    font-weight: bold;
                    text-align: center;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
                    border: 2px solid rgba(255, 255, 255, 0.3);
                    backdrop-filter: blur(10px);
                    transform: translate(-50%, -50%);
                    white-space: nowrap;
                ">
                    <div style="font-size: 18px; margin-bottom: 4px;">${stream.icon}</div>
                    <div>${stream.name}</div>
                    <div style="font-size: 14px; margin-top: 4px; color: #ffd700;">฿${stream.amount.toLocaleString()}</div>
                </div>
            `;
            label.style.cssText = `
                position: absolute;
                pointer-events: none;
            `;
            overlay.appendChild(label);
        });

        // Store for position updates
        this.overlay = overlay;
    }

    createTotalDisplay() {
        const total = Object.values(this.options.incomeData).reduce((a, b) => a + b, 0);

        const display = document.createElement('div');
        display.id = 'total-income-display';
        display.style.cssText = `
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.95) 0%, rgba(255, 165, 0, 0.95) 100%);
            color: white;
            padding: 16px 32px;
            border-radius: 12px;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.4);
            border: 3px solid rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(10px);
            z-index: 20;
        `;
        display.innerHTML = `
            <div style="font-size: 14px; opacity: 0.9; margin-bottom: 4px;">รายได้รวมต่อเดือน</div>
            <div style="font-size: 32px;">฿${total.toLocaleString()}</div>
            <div style="font-size: 12px; opacity: 0.8; margin-top: 4px;">จากทุกช่องทาง</div>
        `;

        this.container.style.position = 'relative';
        this.container.appendChild(display);

        // Animate number
        this.animateNumber(display.querySelector('div:nth-child(2)'), 0, total, 2000);
    }

    animateNumber(element, start, end, duration) {
        const startTime = Date.now();
        const animate = () => {
            const elapsed = Date.now() - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const current = Math.floor(start + (end - start) * this.easeOutCubic(progress));
            element.textContent = `฿${current.toLocaleString()}`;

            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };
        animate();
    }

    easeOutCubic(t) {
        return 1 - Math.pow(1 - t, 3);
    }

    hexToRgb(hex) {
        const result = /^0x?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex.toString(16).padStart(6, '0'));
        return result ? `${parseInt(result[1], 16)}, ${parseInt(result[2], 16)}, ${parseInt(result[3], 16)}` : '255, 255, 255';
    }

    animate() {
        this.animationId = requestAnimationFrame(() => this.animate());

        this.time += 0.01;

        // Update particles
        this.particles.forEach(particle => {
            const userData = particle.userData;
            const stream = userData.stream;

            // Update progress
            userData.progress += userData.speed;
            if (userData.progress > 1) {
                userData.progress = 0;
            }

            // Calculate position along curve
            const t = userData.progress;
            const source = stream.source;
            const target = stream.target;

            // Create a smooth curve (catenary-like)
            const midY = Math.max(source.y, target.y) - Math.abs(source.y - target.y) * 0.5 - 2;

            const x = source.x + (target.x - source.x) * t;
            const z = source.z + (target.z - source.z) * t;
            const y = source.y + (target.y - source.y) * t - Math.sin(t * Math.PI) * 3;

            particle.position.set(x, y, z);

            // Fade in/out
            const opacity = Math.sin(t * Math.PI) * 0.8;
            particle.material.opacity = opacity;
            if (particle.children[0]) {
                particle.children[0].material.opacity = opacity * 0.3;
            }

            // Scale based on progress
            const scale = 0.5 + Math.sin(t * Math.PI) * 0.5;
            particle.scale.set(scale, scale, scale);
        });

        // Animate source nodes
        this.scene.children.forEach(child => {
            if (child.userData.animate) {
                child.userData.animate();
            }
        });

        // Update label positions
        if (this.overlay) {
            this.streams.forEach((stream, index) => {
                const source = stream.source;
                const vector = new THREE.Vector3(source.x, source.y, source.z);
                vector.project(this.camera);

                const x = (vector.x * 0.5 + 0.5) * this.container.clientWidth;
                const y = (-(vector.y * 0.5) + 0.5) * this.container.clientHeight;

                const label = document.getElementById(`income-label-${index}`);
                if (label && vector.z < 1) {
                    label.style.left = `${x}px`;
                    label.style.top = `${y}px`;
                    label.style.opacity = vector.z < 0.5 ? '1' : '0.3';
                }
            });
        }

        this.controls.update();
        this.renderer.render(this.scene, this.camera);
    }

    handleResize() {
        const width = this.container.clientWidth;
        const height = this.container.clientHeight;

        this.camera.aspect = width / height;
        this.camera.updateProjectionMatrix();

        this.renderer.setSize(width, height);
    }

    updateIncomeData(newData) {
        Object.assign(this.options.incomeData, newData);

        // Update total display
        const total = Object.values(this.options.incomeData).reduce((a, b) => a + b, 0);
        const display = document.getElementById('total-income-display');
        if (display) {
            const numberElement = display.querySelector('div:nth-child(2)');
            const currentValue = parseInt(numberElement.textContent.replace(/[^0-9]/g, '')) || 0;
            this.animateNumber(numberElement, currentValue, total, 1000);
        }

        // Update stream labels
        this.streams.forEach((stream, index) => {
            const key = Object.keys(this.options.incomeData)[index];
            if (key) {
                stream.amount = this.options.incomeData[key];
                const label = document.getElementById(`income-label-${index}`);
                if (label) {
                    const amountElement = label.querySelector('div > div:last-child');
                    if (amountElement) {
                        amountElement.textContent = `฿${stream.amount.toLocaleString()}`;
                    }
                }
            }
        });
    }

    destroy() {
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
        }

        this.renderer.dispose();
        this.container.removeChild(this.renderer.domElement);

        // Remove overlays
        const overlay = document.getElementById('income-flow-overlay');
        if (overlay) overlay.remove();

        const display = document.getElementById('total-income-display');
        if (display) display.remove();
    }

    pauseAnimation() {
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
            this.animationId = null;
        }
    }

    resumeAnimation() {
        if (!this.animationId) {
            this.animate();
        }
    }
}

export default Wealth3DIncomeFlow;
