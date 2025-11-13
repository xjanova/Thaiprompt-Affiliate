<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3D Navigation Demo - Thai Prompt</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;600;700;900&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Noto Sans Thai', sans-serif;
            overflow-x: hidden;
            perspective: 1500px;
        }

        /* 3D Scene Container */
        .scene-3d {
            perspective: 1200px;
            perspective-origin: 50% 50%;
        }

        /* 3D Block Base */
        .block-3d {
            position: relative;
            transform-style: preserve-3d;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
        }

        .block-3d:hover {
            transform: translateZ(40px) rotateX(5deg) rotateY(5deg) scale(1.05);
        }

        /* 3D Faces */
        .block-face {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
        }

        .block-front {
            transform: translateZ(30px);
        }

        .block-back {
            transform: translateZ(-30px) rotateY(180deg);
        }

        .block-right {
            transform: rotateY(90deg) translateZ(30px);
        }

        .block-left {
            transform: rotateY(-90deg) translateZ(30px);
        }

        .block-top {
            transform: rotateX(90deg) translateZ(30px);
        }

        .block-bottom {
            transform: rotateX(-90deg) translateZ(30px);
        }

        /* Floating Animation */
        @keyframes float {
            0%, 100% {
                transform: translateY(0px) rotateX(0deg) rotateY(0deg);
            }
            25% {
                transform: translateY(-20px) rotateX(5deg) rotateY(5deg);
            }
            75% {
                transform: translateY(-10px) rotateX(-3deg) rotateY(-3deg);
            }
        }

        .floating {
            animation: float 6s ease-in-out infinite;
        }

        /* 3D Text */
        .text-3d {
            text-shadow:
                0 1px 0 #ccc,
                0 2px 0 #c9c9c9,
                0 3px 0 #bbb,
                0 4px 0 #b9b9b9,
                0 5px 0 #aaa,
                0 6px 1px rgba(0,0,0,.1),
                0 0 5px rgba(0,0,0,.1),
                0 1px 3px rgba(0,0,0,.3),
                0 3px 5px rgba(0,0,0,.2),
                0 5px 10px rgba(0,0,0,.25),
                0 10px 10px rgba(0,0,0,.2),
                0 20px 20px rgba(0,0,0,.15);
        }

        /* Gradient backgrounds */
        .gradient-purple {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .gradient-blue {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .gradient-pink {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .gradient-orange {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }

        .gradient-green {
            background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);
        }

        .gradient-red {
            background: linear-gradient(135deg, #ff0844 0%, #ffb199 100%);
        }

        /* Particle Background */
        .particle {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
            opacity: 0.6;
            animation: particle-float 10s infinite;
        }

        @keyframes particle-float {
            0%, 100% {
                transform: translate(0, 0) scale(1);
                opacity: 0.6;
            }
            50% {
                transform: translate(50px, -50px) scale(1.5);
                opacity: 0.3;
            }
        }

        /* Glow effect */
        .glow {
            box-shadow:
                0 0 20px rgba(255, 255, 255, 0.3),
                0 0 40px rgba(255, 255, 255, 0.2),
                0 0 60px rgba(255, 255, 255, 0.1);
        }

        /* Glass morphism */
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-indigo-900 via-purple-900 to-pink-900 overflow-hidden">

    <!-- Particle Background -->
    <div id="particles" class="fixed inset-0 pointer-events-none"></div>

    <!-- Main Container -->
    <div class="relative min-h-screen flex flex-col items-center justify-center p-8">

        <!-- Hero Title -->
        <div class="text-center mb-16 relative z-10" id="hero">
            <h1 class="text-7xl font-black text-white text-3d mb-4">
                3D Navigation
            </h1>
            <p class="text-2xl text-white/80 font-semibold">
                คลิกที่ก้อน 3D เพื่อไปยังหน้าต่างๆ
            </p>
        </div>

        <!-- 3D Navigation Blocks Grid -->
        <div class="scene-3d grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-12 max-w-7xl mx-auto relative z-10" id="blocks-container">

            <!-- Block 1: Dashboard -->
            <a href="/user/dashboard" class="block-3d floating w-64 h-64" style="animation-delay: 0s;">
                <div class="relative w-full h-full">
                    <!-- Front Face -->
                    <div class="block-face block-front gradient-purple rounded-2xl flex flex-col items-center justify-center p-6 glow">
                        <svg class="w-20 h-20 text-white mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        <h3 class="text-2xl font-bold text-white">Dashboard</h3>
                        <p class="text-white/80 text-sm mt-2">แดชบอร์ด</p>
                    </div>
                    <!-- Top Face -->
                    <div class="block-face block-top gradient-purple opacity-70 rounded-2xl"></div>
                    <!-- Right Face -->
                    <div class="block-face block-right gradient-purple opacity-50 rounded-2xl"></div>
                </div>
            </a>

            <!-- Block 2: Shop -->
            <a href="/shop" class="block-3d floating w-64 h-64" style="animation-delay: 0.5s;">
                <div class="relative w-full h-full">
                    <div class="block-face block-front gradient-blue rounded-2xl flex flex-col items-center justify-center p-6 glow">
                        <svg class="w-20 h-20 text-white mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                        <h3 class="text-2xl font-bold text-white">Shop</h3>
                        <p class="text-white/80 text-sm mt-2">ร้านค้า</p>
                    </div>
                    <div class="block-face block-top gradient-blue opacity-70 rounded-2xl"></div>
                    <div class="block-face block-right gradient-blue opacity-50 rounded-2xl"></div>
                </div>
            </a>

            <!-- Block 3: Organization -->
            <a href="/user/organization" class="block-3d floating w-64 h-64" style="animation-delay: 1s;">
                <div class="relative w-full h-full">
                    <div class="block-face block-front gradient-pink rounded-2xl flex flex-col items-center justify-center p-6 glow">
                        <svg class="w-20 h-20 text-white mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <h3 class="text-2xl font-bold text-white">Organization</h3>
                        <p class="text-white/80 text-sm mt-2">องค์กร</p>
                    </div>
                    <div class="block-face block-top gradient-pink opacity-70 rounded-2xl"></div>
                    <div class="block-face block-right gradient-pink opacity-50 rounded-2xl"></div>
                </div>
            </a>

            <!-- Block 4: Hotels -->
            <a href="/hotels" class="block-3d floating w-64 h-64" style="animation-delay: 1.5s;">
                <div class="relative w-full h-full">
                    <div class="block-face block-front gradient-orange rounded-2xl flex flex-col items-center justify-center p-6 glow">
                        <svg class="w-20 h-20 text-white mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        <h3 class="text-2xl font-bold text-white">Hotels</h3>
                        <p class="text-white/80 text-sm mt-2">โรงแรม</p>
                    </div>
                    <div class="block-face block-top gradient-orange opacity-70 rounded-2xl"></div>
                    <div class="block-face block-right gradient-orange opacity-50 rounded-2xl"></div>
                </div>
            </a>

            <!-- Block 5: Trading Bot -->
            <a href="/trading-bot" class="block-3d floating w-64 h-64" style="animation-delay: 2s;">
                <div class="relative w-full h-full">
                    <div class="block-face block-front gradient-green rounded-2xl flex flex-col items-center justify-center p-6 glow">
                        <svg class="w-20 h-20 text-white mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                        <h3 class="text-2xl font-bold text-white">Trading Bot</h3>
                        <p class="text-white/80 text-sm mt-2">บอทเทรด</p>
                    </div>
                    <div class="block-face block-top gradient-green opacity-70 rounded-2xl"></div>
                    <div class="block-face block-right gradient-green opacity-50 rounded-2xl"></div>
                </div>
            </a>

            <!-- Block 6: Marketplace -->
            <a href="/marketplace" class="block-3d floating w-64 h-64" style="animation-delay: 2.5s;">
                <div class="relative w-full h-full">
                    <div class="block-face block-front gradient-red rounded-2xl flex flex-col items-center justify-center p-6 glow">
                        <svg class="w-20 h-20 text-white mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <h3 class="text-2xl font-bold text-white">Marketplace</h3>
                        <p class="text-white/80 text-sm mt-2">ตลาดกลาง</p>
                    </div>
                    <div class="block-face block-top gradient-red opacity-70 rounded-2xl"></div>
                    <div class="block-face block-right gradient-red opacity-50 rounded-2xl"></div>
                </div>
            </a>

        </div>

        <!-- Back Button -->
        <div class="mt-16 relative z-10">
            <a href="/" class="glass px-8 py-4 rounded-full text-white font-semibold text-lg hover:bg-white/20 transition-all duration-300 inline-block">
                ← กลับหน้าหลัก
            </a>
        </div>

    </div>

    <script>
        // GSAP Animations
        gsap.registerPlugin();

        // Animate hero on load
        gsap.from("#hero", {
            duration: 1.5,
            y: -100,
            opacity: 0,
            ease: "power4.out"
        });

        // Animate blocks with stagger
        gsap.from(".block-3d", {
            duration: 1,
            scale: 0,
            opacity: 0,
            rotateX: -90,
            stagger: 0.15,
            ease: "back.out(1.7)",
            delay: 0.5
        });

        // Mouse parallax effect
        document.addEventListener('mousemove', (e) => {
            const blocks = document.querySelectorAll('.block-3d');
            const x = (e.clientX / window.innerWidth - 0.5) * 20;
            const y = (e.clientY / window.innerHeight - 0.5) * 20;

            blocks.forEach((block, index) => {
                const speed = (index + 1) * 0.5;
                gsap.to(block, {
                    duration: 0.5,
                    rotateY: x * speed,
                    rotateX: -y * speed,
                    ease: "power2.out"
                });
            });
        });

        // Create floating particles
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 50;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';

                const size = Math.random() * 10 + 5;
                const x = Math.random() * window.innerWidth;
                const y = Math.random() * window.innerHeight;
                const delay = Math.random() * 5;
                const duration = Math.random() * 10 + 10;

                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                particle.style.left = `${x}px`;
                particle.style.top = `${y}px`;
                particle.style.animationDelay = `${delay}s`;
                particle.style.animationDuration = `${duration}s`;
                particle.style.background = `rgba(255, 255, 255, ${Math.random() * 0.5 + 0.2})`;

                particlesContainer.appendChild(particle);
            }
        }

        createParticles();

        // Add click ripple effect
        document.querySelectorAll('.block-3d').forEach(block => {
            block.addEventListener('click', function(e) {
                // Prevent default to add animation before navigation
                e.preventDefault();
                const href = this.href;

                // Scale and fade animation
                gsap.to(this, {
                    duration: 0.3,
                    scale: 0.8,
                    opacity: 0,
                    ease: "power2.in",
                    onComplete: () => {
                        window.location.href = href;
                    }
                });
            });

            // Enhanced hover animation with GSAP
            block.addEventListener('mouseenter', function() {
                gsap.to(this, {
                    duration: 0.3,
                    scale: 1.1,
                    z: 50,
                    ease: "power2.out"
                });
            });

            block.addEventListener('mouseleave', function() {
                gsap.to(this, {
                    duration: 0.3,
                    scale: 1,
                    z: 0,
                    ease: "power2.out"
                });
            });
        });

        // Continuous rotation animation
        gsap.to(".block-3d", {
            rotateY: "+=360",
            duration: 20,
            repeat: -1,
            ease: "none",
            stagger: {
                each: 2,
                repeat: -1
            }
        });
    </script>
</body>
</html>
