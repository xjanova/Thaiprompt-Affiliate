/**
 * Wealth Guide 3D Mind Map using Three.js
 * Interactive 3D visualization of income streams and business structure
 */

import * as THREE from 'three';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls.js';

export class Wealth3DMindMap {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error(`Container ${containerId} not found`);
            return;
        }

        this.options = {
            theme: 'gold',
            autoRotate: true,
            showLabels: true,
            ...options
        };

        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.controls = null;
        this.nodes = [];
        this.connections = [];
        this.animationId = null;

        this.init();
        this.createMindMap();
        this.animate();
        this.setupEventListeners();
    }

    init() {
        // Scene setup
        this.scene = new THREE.Scene();
        this.scene.background = new THREE.Color(0x0a0a0a);
        this.scene.fog = new THREE.Fog(0x0a0a0a, 10, 50);

        // Camera setup
        const aspect = this.container.clientWidth / this.container.clientHeight;
        this.camera = new THREE.PerspectiveCamera(75, aspect, 0.1, 1000);
        this.camera.position.set(0, 5, 15);
        this.camera.lookAt(0, 0, 0);

        // Renderer setup
        this.renderer = new THREE.WebGLRenderer({
            antialias: true,
            alpha: true,
            powerPreference: 'high-performance'
        });
        this.renderer.setSize(this.container.clientWidth, this.container.clientHeight);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this.renderer.shadowMap.enabled = true;
        this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        this.container.appendChild(this.renderer.domElement);

        // Controls setup
        this.controls = new OrbitControls(this.camera, this.renderer.domElement);
        this.controls.enableDamping = true;
        this.controls.dampingFactor = 0.05;
        this.controls.autoRotate = this.options.autoRotate;
        this.controls.autoRotateSpeed = 1.0;
        this.controls.minDistance = 10;
        this.controls.maxDistance = 30;

        // Lights
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.4);
        this.scene.add(ambientLight);

        const mainLight = new THREE.DirectionalLight(0xffd700, 1.2);
        mainLight.position.set(5, 10, 5);
        mainLight.castShadow = true;
        mainLight.shadow.mapSize.width = 2048;
        mainLight.shadow.mapSize.height = 2048;
        this.scene.add(mainLight);

        const fillLight = new THREE.PointLight(0xff6b35, 0.8, 30);
        fillLight.position.set(-5, 5, -5);
        this.scene.add(fillLight);

        const accentLight = new THREE.PointLight(0x4ecdc4, 0.6, 25);
        accentLight.position.set(0, -5, 10);
        this.scene.add(accentLight);

        // Add stars background
        this.addStarfield();
    }

    addStarfield() {
        const starGeometry = new THREE.BufferGeometry();
        const starCount = 2000;
        const positions = new Float32Array(starCount * 3);

        for (let i = 0; i < starCount * 3; i += 3) {
            positions[i] = (Math.random() - 0.5) * 100;
            positions[i + 1] = (Math.random() - 0.5) * 100;
            positions[i + 2] = (Math.random() - 0.5) * 100;
        }

        starGeometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));

        const starMaterial = new THREE.PointsMaterial({
            color: 0xffffff,
            size: 0.1,
            transparent: true,
            opacity: 0.8
        });

        const stars = new THREE.Points(starGeometry, starMaterial);
        this.scene.add(stars);
    }

    createMindMap() {
        // Central node - Main Platform
        const centerNode = this.createNode({
            position: { x: 0, y: 0, z: 0 },
            radius: 1.5,
            color: 0xffd700,
            label: 'ธุรกิจหลัก',
            icon: '💰',
            data: {
                title: 'แพลตฟอร์ม Thaiprompt Affiliate',
                description: 'ศูนย์กลางรายได้ทั้งหมด'
            }
        });

        // Income Stream Nodes - Level 1
        const incomeStreams = [
            {
                position: { x: 5, y: 2, z: 0 },
                color: 0x4ecdc4,
                label: 'รายได้ตรง',
                icon: '💵',
                data: {
                    title: 'Direct Commission',
                    description: 'รายได้จากการขายตรง 30-15%',
                    amount: '30-15% ต่อชั้น'
                }
            },
            {
                position: { x: -5, y: 2, z: 0 },
                color: 0xff6b6b,
                label: 'Binary Matching',
                icon: '⚖️',
                data: {
                    title: 'Binary Matching Bonus',
                    description: 'รายได้จากคู่ขา 1,000฿/คู่',
                    amount: '1,000฿/คู่'
                }
            },
            {
                position: { x: 0, y: 2, z: 5 },
                color: 0x95e1d3,
                label: 'Rank Bonus',
                icon: '👑',
                data: {
                    title: 'Rank Achievement Bonus',
                    description: 'โบนัสตามยศ 5,000฿-500,000฿',
                    amount: 'ถึง 500,000฿'
                }
            },
            {
                position: { x: 0, y: 2, z: -5 },
                color: 0xf38181,
                label: 'Sponsor Bonus',
                icon: '🤝',
                data: {
                    title: 'Sponsorship Bonus',
                    description: 'โบนัสแนะนำ 10-20%',
                    amount: '10-20%'
                }
            }
        ];

        incomeStreams.forEach(stream => {
            const node = this.createNode(stream);
            this.createConnection(centerNode, node, stream.color);
        });

        // Marketplace Affiliate Nodes - Level 2
        const marketplaces = [
            {
                position: { x: 7, y: -2, z: 3 },
                color: 0xff9ff3,
                label: 'Lazada',
                icon: '🛒',
                data: {
                    title: 'Lazada Affiliate',
                    description: 'รายได้จาก Lazada Affiliate',
                    amount: '2-10% Commission'
                }
            },
            {
                position: { x: 7, y: -2, z: -3 },
                color: 0xfeca57,
                label: 'Shopee',
                icon: '🛍️',
                data: {
                    title: 'Shopee Affiliate',
                    description: 'รายได้จาก Shopee Affiliate',
                    amount: '5-15% Commission'
                }
            },
            {
                position: { x: -7, y: -2, z: 0 },
                color: 0x00d2d3,
                label: 'TikTok Shop',
                icon: '🎵',
                data: {
                    title: 'TikTok Shop Affiliate',
                    description: 'รายได้จาก TikTok Shop',
                    amount: '8-20% Commission'
                }
            }
        ];

        marketplaces.forEach(marketplace => {
            const node = this.createNode(marketplace);
            this.createConnection(centerNode, node, marketplace.color);
        });

        // Additional Income Sources - Level 2
        const additionalSources = [
            {
                position: { x: -3, y: -3, z: 5 },
                color: 0xa29bfe,
                label: 'Video Rewards',
                icon: '📹',
                data: {
                    title: 'Video Referral Rewards',
                    description: 'รายได้จากวิดีโอ',
                    amount: 'ต่อ View'
                }
            },
            {
                position: { x: 3, y: -3, z: 5 },
                color: 0xfd79a8,
                label: 'Team Override',
                icon: '👥',
                data: {
                    title: 'Team Override Bonus',
                    description: 'รายได้จากทีม',
                    amount: '5-10% Override'
                }
            },
            {
                position: { x: 0, y: -4, z: -3 },
                color: 0x6c5ce7,
                label: 'Leadership',
                icon: '🎯',
                data: {
                    title: 'Leadership Bonus',
                    description: 'โบนัสผู้นำ',
                    amount: 'Pool Share'
                }
            }
        ];

        additionalSources.forEach(source => {
            const node = this.createNode(source);
            this.createConnection(centerNode, node, source.color);
        });

        // Add floating particles
        this.addFloatingParticles();
    }

    createNode(options) {
        const { position, radius = 0.8, color, label, icon, data } = options;

        // Node group
        const nodeGroup = new THREE.Group();
        nodeGroup.position.set(position.x, position.y, position.z);

        // Sphere
        const geometry = new THREE.SphereGeometry(radius, 32, 32);
        const material = new THREE.MeshStandardMaterial({
            color: color,
            metalness: 0.6,
            roughness: 0.2,
            emissive: color,
            emissiveIntensity: 0.3
        });
        const sphere = new THREE.Mesh(geometry, material);
        sphere.castShadow = true;
        sphere.receiveShadow = true;
        nodeGroup.add(sphere);

        // Glow effect
        const glowGeometry = new THREE.SphereGeometry(radius * 1.2, 32, 32);
        const glowMaterial = new THREE.MeshBasicMaterial({
            color: color,
            transparent: true,
            opacity: 0.2,
            side: THREE.BackSide
        });
        const glow = new THREE.Mesh(glowGeometry, glowMaterial);
        nodeGroup.add(glow);

        // Ring
        const ringGeometry = new THREE.TorusGeometry(radius * 1.3, 0.05, 16, 100);
        const ringMaterial = new THREE.MeshBasicMaterial({
            color: color,
            transparent: true,
            opacity: 0.6
        });
        const ring = new THREE.Mesh(ringGeometry, ringMaterial);
        ring.rotation.x = Math.PI / 2;
        nodeGroup.add(ring);

        // Store data
        nodeGroup.userData = {
            type: 'node',
            label,
            icon,
            data,
            originalColor: color,
            sphere,
            glow,
            ring
        };

        this.scene.add(nodeGroup);
        this.nodes.push(nodeGroup);

        // Add pulsing animation
        this.addPulseAnimation(nodeGroup);

        return nodeGroup;
    }

    createConnection(node1, node2, color) {
        const points = [];
        points.push(node1.position);

        // Create a curved path
        const midPoint = new THREE.Vector3(
            (node1.position.x + node2.position.x) / 2,
            (node1.position.y + node2.position.y) / 2 + 1,
            (node1.position.z + node2.position.z) / 2
        );
        points.push(midPoint);
        points.push(node2.position);

        const curve = new THREE.CatmullRomCurve3(points);
        const tubeGeometry = new THREE.TubeGeometry(curve, 64, 0.05, 8, false);
        const tubeMaterial = new THREE.MeshBasicMaterial({
            color: color,
            transparent: true,
            opacity: 0.4
        });
        const tube = new THREE.Mesh(tubeGeometry, tubeMaterial);

        tube.userData = {
            type: 'connection',
            originalColor: color
        };

        this.scene.add(tube);
        this.connections.push(tube);

        return tube;
    }

    addFloatingParticles() {
        const particleCount = 100;
        const geometry = new THREE.BufferGeometry();
        const positions = new Float32Array(particleCount * 3);
        const colors = new Float32Array(particleCount * 3);

        const colorPalette = [
            new THREE.Color(0xffd700),
            new THREE.Color(0x4ecdc4),
            new THREE.Color(0xff6b6b),
            new THREE.Color(0x95e1d3)
        ];

        for (let i = 0; i < particleCount * 3; i += 3) {
            positions[i] = (Math.random() - 0.5) * 20;
            positions[i + 1] = (Math.random() - 0.5) * 20;
            positions[i + 2] = (Math.random() - 0.5) * 20;

            const color = colorPalette[Math.floor(Math.random() * colorPalette.length)];
            colors[i] = color.r;
            colors[i + 1] = color.g;
            colors[i + 2] = color.b;
        }

        geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        geometry.setAttribute('color', new THREE.BufferAttribute(colors, 3));

        const material = new THREE.PointsMaterial({
            size: 0.15,
            vertexColors: true,
            transparent: true,
            opacity: 0.6,
            blending: THREE.AdditiveBlending
        });

        const particles = new THREE.Points(geometry, material);
        particles.userData = { type: 'particles' };
        this.scene.add(particles);
    }

    addPulseAnimation(node) {
        const sphere = node.userData.sphere;
        const glow = node.userData.glow;
        const ring = node.userData.ring;

        const animate = () => {
            const time = Date.now() * 0.001;
            const scale = 1 + Math.sin(time * 2) * 0.05;

            glow.scale.set(scale, scale, scale);
            ring.rotation.z += 0.01;
        };

        node.userData.animate = animate;
    }

    animate() {
        this.animationId = requestAnimationFrame(() => this.animate());

        // Animate nodes
        this.nodes.forEach(node => {
            if (node.userData.animate) {
                node.userData.animate();
            }
        });

        // Rotate particles
        this.scene.children.forEach(child => {
            if (child.userData.type === 'particles') {
                child.rotation.y += 0.001;
            }
        });

        this.controls.update();
        this.renderer.render(this.scene, this.camera);
    }

    setupEventListeners() {
        // Window resize
        window.addEventListener('resize', () => this.handleResize());

        // Mouse interaction
        const raycaster = new THREE.Raycaster();
        const mouse = new THREE.Vector2();

        this.renderer.domElement.addEventListener('mousemove', (event) => {
            const rect = this.renderer.domElement.getBoundingClientRect();
            mouse.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
            mouse.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;

            raycaster.setFromCamera(mouse, this.camera);
            const intersects = raycaster.intersectObjects(this.nodes, true);

            // Reset all nodes
            this.nodes.forEach(node => {
                if (node.userData.sphere) {
                    node.userData.sphere.material.emissiveIntensity = 0.3;
                    node.scale.set(1, 1, 1);
                }
            });

            if (intersects.length > 0) {
                const node = intersects[0].object.parent;
                if (node.userData.type === 'node') {
                    node.userData.sphere.material.emissiveIntensity = 0.8;
                    node.scale.set(1.1, 1.1, 1.1);
                    this.renderer.domElement.style.cursor = 'pointer';

                    // Show tooltip
                    this.showTooltip(event, node.userData);
                }
            } else {
                this.renderer.domElement.style.cursor = 'default';
                this.hideTooltip();
            }
        });

        this.renderer.domElement.addEventListener('click', (event) => {
            const rect = this.renderer.domElement.getBoundingClientRect();
            mouse.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
            mouse.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;

            raycaster.setFromCamera(mouse, this.camera);
            const intersects = raycaster.intersectObjects(this.nodes, true);

            if (intersects.length > 0) {
                const node = intersects[0].object.parent;
                if (node.userData.type === 'node') {
                    this.handleNodeClick(node.userData);
                }
            }
        });
    }

    showTooltip(event, nodeData) {
        let tooltip = document.getElementById('mindmap-tooltip');
        if (!tooltip) {
            tooltip = document.createElement('div');
            tooltip.id = 'mindmap-tooltip';
            tooltip.style.cssText = `
                position: fixed;
                background: rgba(0, 0, 0, 0.9);
                color: white;
                padding: 12px 16px;
                border-radius: 8px;
                font-size: 14px;
                pointer-events: none;
                z-index: 1000;
                max-width: 250px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
                border: 1px solid rgba(255, 215, 0, 0.3);
            `;
            document.body.appendChild(tooltip);
        }

        tooltip.innerHTML = `
            <div style="font-size: 20px; margin-bottom: 8px;">${nodeData.icon}</div>
            <div style="font-weight: bold; margin-bottom: 4px;">${nodeData.data.title}</div>
            <div style="opacity: 0.8; font-size: 12px; margin-bottom: 4px;">${nodeData.data.description}</div>
            <div style="color: #ffd700; font-weight: bold;">${nodeData.data.amount || ''}</div>
        `;

        tooltip.style.left = `${event.clientX + 15}px`;
        tooltip.style.top = `${event.clientY + 15}px`;
        tooltip.style.display = 'block';
    }

    hideTooltip() {
        const tooltip = document.getElementById('mindmap-tooltip');
        if (tooltip) {
            tooltip.style.display = 'none';
        }
    }

    handleNodeClick(nodeData) {
        console.log('Node clicked:', nodeData);
        // Emit custom event for external handling
        const event = new CustomEvent('nodeClick', { detail: nodeData });
        this.container.dispatchEvent(event);
    }

    handleResize() {
        const width = this.container.clientWidth;
        const height = this.container.clientHeight;

        this.camera.aspect = width / height;
        this.camera.updateProjectionMatrix();

        this.renderer.setSize(width, height);
    }

    destroy() {
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
        }

        this.renderer.dispose();
        this.container.removeChild(this.renderer.domElement);

        const tooltip = document.getElementById('mindmap-tooltip');
        if (tooltip) {
            tooltip.remove();
        }
    }

    toggleAutoRotate() {
        this.controls.autoRotate = !this.controls.autoRotate;
        return this.controls.autoRotate;
    }

    focusOnNode(label) {
        const node = this.nodes.find(n => n.userData.label === label);
        if (node) {
            const targetPosition = node.position.clone();
            targetPosition.z += 5;

            // Smooth camera transition
            this.animateCamera(targetPosition, node.position);
        }
    }

    animateCamera(position, lookAt) {
        const startPosition = this.camera.position.clone();
        const startLookAt = this.controls.target.clone();
        const duration = 1000;
        const startTime = Date.now();

        const animate = () => {
            const elapsed = Date.now() - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const eased = this.easeInOutCubic(progress);

            this.camera.position.lerpVectors(startPosition, position, eased);
            this.controls.target.lerpVectors(startLookAt, lookAt, eased);

            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };

        animate();
    }

    easeInOutCubic(t) {
        return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
    }
}

// Export for use in other modules
export default Wealth3DMindMap;
