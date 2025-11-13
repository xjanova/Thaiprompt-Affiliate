<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3D Navigation Demo - Thai Prompt</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;600;700;900&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Noto Sans Thai', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow-x: hidden;
        }

        .container {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
        }

        .title {
            text-align: center;
            color: white;
            margin-bottom: 50px;
            animation: fadeInDown 1s ease-out;
        }

        .title h1 {
            font-size: 3rem;
            font-weight: 900;
            margin-bottom: 10px;
            text-shadow: 2px 2px 10px rgba(0,0,0,0.3);
        }

        .title p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .blocks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            perspective: 1500px;
            padding: 20px;
        }

        .block-3d {
            position: relative;
            width: 100%;
            height: 250px;
            transform-style: preserve-3d;
            transition: transform 0.5s ease;
            animation: floatIn 1s ease-out backwards;
            cursor: pointer;
            text-decoration: none;
        }

        .block-3d:nth-child(1) { animation-delay: 0.1s; }
        .block-3d:nth-child(2) { animation-delay: 0.2s; }
        .block-3d:nth-child(3) { animation-delay: 0.3s; }
        .block-3d:nth-child(4) { animation-delay: 0.4s; }
        .block-3d:nth-child(5) { animation-delay: 0.5s; }
        .block-3d:nth-child(6) { animation-delay: 0.6s; }

        .block-3d:hover {
            transform: translateY(-20px) rotateX(10deg) rotateY(10deg) scale(1.05);
        }

        .block-inner {
            position: relative;
            width: 100%;
            height: 100%;
            transform-style: preserve-3d;
            transform: translateZ(30px);
        }

        .block-face {
            position: absolute;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 20px;
            padding: 30px;
            color: white;
            font-weight: bold;
            backface-visibility: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }

        .block-front {
            transform: translateZ(30px);
        }

        .block-top {
            transform: rotateX(90deg) translateZ(30px);
            opacity: 0.8;
        }

        .block-right {
            transform: rotateY(90deg) translateZ(280px);
            opacity: 0.6;
        }

        .block-icon {
            font-size: 4rem;
            margin-bottom: 15px;
            filter: drop-shadow(0 5px 10px rgba(0,0,0,0.3));
        }

        .block-title {
            font-size: 1.8rem;
            margin-bottom: 8px;
            text-shadow: 2px 2px 5px rgba(0,0,0,0.3);
        }

        .block-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
        }

        /* Colors */
        .bg-purple { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .bg-blue { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .bg-pink { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .bg-orange { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        .bg-green { background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); }
        .bg-red { background: linear-gradient(135deg, #ff0844 0%, #ffb199 100%); }

        .back-button {
            margin-top: 50px;
            text-align: center;
        }

        .back-button a {
            display: inline-block;
            padding: 15px 40px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }

        .back-button a:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes floatIn {
            from {
                opacity: 0;
                transform: translateY(100px) scale(0.5);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .title h1 {
                font-size: 2rem;
            }
            .blocks-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <!-- Title -->
        <div class="title">
            <h1>🎮 3D Navigation Demo</h1>
            <p>คลิกที่ก้อน 3D เพื่อไปยังหน้าต่างๆ</p>
        </div>

        <!-- 3D Blocks Grid -->
        <div class="blocks-grid">

            <!-- Block 1: Dashboard -->
            <a href="/user/dashboard" class="block-3d">
                <div class="block-inner">
                    <div class="block-face block-front bg-purple">
                        <div class="block-icon">🏠</div>
                        <div class="block-title">Dashboard</div>
                        <div class="block-subtitle">แดชบอร์ด</div>
                    </div>
                    <div class="block-face block-top bg-purple"></div>
                    <div class="block-face block-right bg-purple"></div>
                </div>
            </a>

            <!-- Block 2: Shop -->
            <a href="/shop" class="block-3d">
                <div class="block-inner">
                    <div class="block-face block-front bg-blue">
                        <div class="block-icon">🛍️</div>
                        <div class="block-title">Shop</div>
                        <div class="block-subtitle">ร้านค้า</div>
                    </div>
                    <div class="block-face block-top bg-blue"></div>
                    <div class="block-face block-right bg-blue"></div>
                </div>
            </a>

            <!-- Block 3: Organization -->
            <a href="/user/organization" class="block-3d">
                <div class="block-inner">
                    <div class="block-face block-front bg-pink">
                        <div class="block-icon">👥</div>
                        <div class="block-title">Organization</div>
                        <div class="block-subtitle">องค์กร</div>
                    </div>
                    <div class="block-face block-top bg-pink"></div>
                    <div class="block-face block-right bg-pink"></div>
                </div>
            </a>

            <!-- Block 4: Hotels -->
            <a href="/hotels" class="block-3d">
                <div class="block-inner">
                    <div class="block-face block-front bg-orange">
                        <div class="block-icon">🏨</div>
                        <div class="block-title">Hotels</div>
                        <div class="block-subtitle">โรงแรม</div>
                    </div>
                    <div class="block-face block-top bg-orange"></div>
                    <div class="block-face block-right bg-orange"></div>
                </div>
            </a>

            <!-- Block 5: Trading Bot -->
            <a href="/trading-bot" class="block-3d">
                <div class="block-inner">
                    <div class="block-face block-front bg-green">
                        <div class="block-icon">📈</div>
                        <div class="block-title">Trading Bot</div>
                        <div class="block-subtitle">บอทเทรด</div>
                    </div>
                    <div class="block-face block-top bg-green"></div>
                    <div class="block-face block-right bg-green"></div>
                </div>
            </a>

            <!-- Block 6: Marketplace -->
            <a href="/marketplace" class="block-3d">
                <div class="block-inner">
                    <div class="block-face block-front bg-red">
                        <div class="block-icon">🛒</div>
                        <div class="block-title">Marketplace</div>
                        <div class="block-subtitle">ตลาดกลาง</div>
                    </div>
                    <div class="block-face block-top bg-red"></div>
                    <div class="block-face block-right bg-red"></div>
                </div>
            </a>

        </div>

        <!-- Back Button -->
        <div class="back-button">
            <a href="/">← กลับหน้าหลัก</a>
        </div>
    </div>

    <script>
        // Simple mouse parallax effect
        document.addEventListener('mousemove', (e) => {
            const blocks = document.querySelectorAll('.block-3d');
            const mouseX = (e.clientX / window.innerWidth) - 0.5;
            const mouseY = (e.clientY / window.innerHeight) - 0.5;

            blocks.forEach((block, index) => {
                const depth = (index + 1) * 10;
                const moveX = mouseX * depth;
                const moveY = mouseY * depth;

                block.style.transform = `translateX(${moveX}px) translateY(${moveY}px)`;
            });
        });

        // Click animation
        document.querySelectorAll('.block-3d').forEach(block => {
            block.addEventListener('click', function(e) {
                this.style.transform = 'scale(0.9)';
                setTimeout(() => {
                    window.location.href = this.href;
                }, 200);
            });
        });
    </script>
</body>
</html>
