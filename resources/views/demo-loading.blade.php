<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Realistic Loading Progress Demo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Simulate multiple resources to track -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Noto Sans Thai', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Include the page loader component -->
    <x-page-loader />

    <div class="min-h-screen flex items-center justify-center p-8">
        <div class="max-w-4xl w-full bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-4">
                    🎯 Realistic Loading Progress Demo
                </h1>
                <p class="text-gray-600">
                    ระบบ Loading Progress ที่แสดงความก้าวหน้าการโหลดที่เป็นจริง
                </p>
            </div>

            <div class="grid md:grid-cols-2 gap-6 mb-8">
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg p-6 border border-blue-200">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-check-circle text-white text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800">Performance API</h3>
                            <p class="text-sm text-gray-600">ติดตามทรัพยากรจริง</p>
                        </div>
                    </div>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li>✓ Track JavaScript files</li>
                        <li>✓ Track CSS stylesheets</li>
                        <li>✓ Track images</li>
                        <li>✓ Track fonts</li>
                    </ul>
                </div>

                <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-lg p-6 border border-green-200">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-chart-line text-white text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800">Real Progress</h3>
                            <p class="text-sm text-gray-600">เปอร์เซ็นต์จริง</p>
                        </div>
                    </div>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li>✓ คำนวณจากทรัพยากรจริง</li>
                        <li>✓ แสดงสถานะการโหลด</li>
                        <li>✓ แสดงรายละเอียด</li>
                        <li>✓ อัพเดทแบบ realtime</li>
                    </ul>
                </div>
            </div>

            <div class="bg-gray-50 rounded-lg p-6 mb-8">
                <h3 class="font-semibold text-gray-800 mb-4">
                    <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                    ฟีเจอร์ที่เพิ่มขึ้น
                </h3>
                <div class="grid md:grid-cols-3 gap-4">
                    <div class="bg-white rounded p-4">
                        <div class="text-2xl font-bold text-blue-600 mb-2">0-100%</div>
                        <div class="text-sm text-gray-600">เปอร์เซ็นต์จริง</div>
                    </div>
                    <div class="bg-white rounded p-4">
                        <div class="text-2xl font-bold text-green-600 mb-2">
                            <i class="fas fa-spinner fa-spin"></i>
                        </div>
                        <div class="text-sm text-gray-600">สถานะ Realtime</div>
                    </div>
                    <div class="bg-white rounded p-4">
                        <div class="text-2xl font-bold text-purple-600 mb-2">N/M</div>
                        <div class="text-sm text-gray-600">นับทรัพยากร</div>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-indigo-500 to-purple-500 rounded-lg p-6 text-white">
                <h3 class="font-semibold mb-3">
                    <i class="fas fa-code mr-2"></i>
                    การทำงาน
                </h3>
                <ol class="space-y-2 text-sm">
                    <li>1. นับจำนวนทรัพยากรทั้งหมด (scripts, stylesheets, images)</li>
                    <li>2. ใช้ PerformanceObserver ติดตามการโหลดแบบ realtime</li>
                    <li>3. คำนวณเปอร์เซ็นต์จาก: (โหลดแล้ว / ทั้งหมด) × 100</li>
                    <li>4. แสดงสถานะและรายละเอียดที่กำลังโหลด</li>
                    <li>5. รอ DOMContentLoaded และ Alpine.js init</li>
                    <li>6. เสร็จสมบูรณ์เมื่อ window.load</li>
                </ol>
            </div>

            <div class="mt-8 text-center">
                <button
                    onclick="location.reload()"
                    class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-lg transition-all duration-200 transform hover:scale-105"
                >
                    <i class="fas fa-redo-alt mr-2"></i>
                    Reload เพื่อดู Loading Progress อีกครั้ง
                </button>
            </div>

            <div class="mt-6 text-center text-sm text-gray-500">
                <p>เปิด Developer Console (F12) เพื่อดู Log การทำงาน</p>
            </div>
        </div>
    </div>

    <!-- Dummy images to simulate loading -->
    <div class="hidden">
        <img src="https://picsum.photos/200/200?random=1" alt="dummy1">
        <img src="https://picsum.photos/200/200?random=2" alt="dummy2">
        <img src="https://picsum.photos/200/200?random=3" alt="dummy3">
        <img src="https://picsum.photos/200/200?random=4" alt="dummy4">
        <img src="https://picsum.photos/200/200?random=5" alt="dummy5">
    </div>

    <!-- Dummy scripts to simulate loading -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>

    <script>
        // Log when Alpine is initialized
        document.addEventListener('alpine:init', () => {
            console.log('✅ Alpine.js initialized');
        });

        // Log when DOM is ready
        document.addEventListener('DOMContentLoaded', () => {
            console.log('✅ DOM Content Loaded');
        });

        // Log when everything is loaded
        window.addEventListener('load', () => {
            console.log('✅ Window Loaded - All resources ready!');
        });
    </script>
</body>
</html>
