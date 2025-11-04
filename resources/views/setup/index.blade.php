<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>TP-Affiliate Setup Wizard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">
                <i class="fas fa-rocket text-blue-600"></i>
                TP-Affiliate Setup Wizard
            </h1>
            <p class="text-gray-600">ติดตั้งและกำหนดค่าระบบของคุณในไม่กี่ขั้นตอน</p>
        </div>

        <!-- Progress Bar -->
        <div class="max-w-4xl mx-auto mb-8">
            <div class="flex items-center justify-between">
                <div class="step-indicator active" data-step="1">
                    <div class="step-circle">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="step-label">ตรวจสอบระบบ</div>
                </div>
                <div class="step-line"></div>
                <div class="step-indicator" data-step="2">
                    <div class="step-circle">
                        <i class="fas fa-key"></i>
                    </div>
                    <div class="step-label">ยืนยัน License</div>
                </div>
                <div class="step-line"></div>
                <div class="step-indicator" data-step="3">
                    <div class="step-circle">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="step-label">สร้างแอดมิน</div>
                </div>
                <div class="step-line"></div>
                <div class="step-indicator" data-step="4">
                    <div class="step-circle">
                        <i class="fas fa-database"></i>
                    </div>
                    <div class="step-label">ข้อมูลตัวอย่าง</div>
                </div>
                <div class="step-line"></div>
                <div class="step-indicator" data-step="5">
                    <div class="step-circle">
                        <i class="fas fa-flag-checkered"></i>
                    </div>
                    <div class="step-label">เสร็จสิ้น</div>
                </div>
            </div>
        </div>

        <!-- Steps Container -->
        <div class="max-w-3xl mx-auto bg-white rounded-lg shadow-lg p-8">
            <!-- Step 1: System Requirements -->
            <div class="setup-step active" id="step-1">
                <h2 class="text-2xl font-bold mb-4">
                    <i class="fas fa-check-circle text-blue-600"></i>
                    ตรวจสอบความพร้อมของระบบ
                </h2>
                <p class="text-gray-600 mb-6">กำลังตรวจสอบว่าเซิร์ฟเวอร์ของคุณพร้อมสำหรับการติดตั้งหรือไม่...</p>

                <div id="requirements-list" class="space-y-3">
                    <!-- Requirements will be loaded here -->
                </div>

                <div class="mt-8 flex justify-between">
                    <button class="btn btn-secondary" disabled>
                        <i class="fas fa-arrow-left"></i> ย้อนกลับ
                    </button>
                    <button class="btn btn-primary" id="btn-next-1" disabled>
                        ถัดไป <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- Step 2: License Verification -->
            <div class="setup-step" id="step-2">
                <h2 class="text-2xl font-bold mb-4">
                    <i class="fas fa-key text-blue-600"></i>
                    ยืนยัน License Key
                </h2>
                <p class="text-gray-600 mb-6">กรุณากรอก License Key ที่คุณได้รับจากการซื้อสินค้า</p>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            License Key <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="license_key"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="XXXX-XXXX-XXXX-XXXX"
                               pattern="[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}">
                        <p class="text-sm text-gray-500 mt-1">
                            รูปแบบ: XXXX-XXXX-XXXX-XXXX
                        </p>
                    </div>

                    <div id="license-status" class="hidden"></div>
                </div>

                <div class="mt-8 flex justify-between">
                    <button class="btn btn-secondary" onclick="goToStep(1)">
                        <i class="fas fa-arrow-left"></i> ย้อนกลับ
                    </button>
                    <button class="btn btn-primary" id="btn-verify-license">
                        ยืนยัน License <i class="fas fa-check"></i>
                    </button>
                </div>
            </div>

            <!-- Step 3: Create Admin -->
            <div class="setup-step" id="step-3">
                <h2 class="text-2xl font-bold mb-4">
                    <i class="fas fa-user-shield text-blue-600"></i>
                    สร้างบัญชีผู้ดูแลระบบ
                </h2>
                <p class="text-gray-600 mb-6">สร้างบัญชีแอดมินเพื่อใช้จัดการระบบ</p>

                <form id="admin-form" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            ชื่อ-นามสกุล <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            อีเมล <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="email" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            รหัสผ่าน <span class="text-red-500">*</span>
                        </label>
                        <input type="password" name="password" required minlength="8"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <p class="text-sm text-gray-500 mt-1">อย่างน้อย 8 ตัวอักษร</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            ยืนยันรหัสผ่าน <span class="text-red-500">*</span>
                        </label>
                        <input type="password" name="password_confirmation" required minlength="8"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div id="admin-status" class="hidden"></div>
                </form>

                <div class="mt-8 flex justify-between">
                    <button class="btn btn-secondary" onclick="goToStep(2)">
                        <i class="fas fa-arrow-left"></i> ย้อนกลับ
                    </button>
                    <button class="btn btn-primary" id="btn-create-admin">
                        สร้างบัญชี <i class="fas fa-user-plus"></i>
                    </button>
                </div>
            </div>

            <!-- Step 4: Demo Data -->
            <div class="setup-step" id="step-4">
                <h2 class="text-2xl font-bold mb-4">
                    <i class="fas fa-database text-blue-600"></i>
                    ข้อมูลตัวอย่าง
                </h2>
                <p class="text-gray-600 mb-6">คุณต้องการติดตั้งข้อมูลตัวอย่างหรือไม่?</p>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                    <h3 class="font-semibold mb-3">ข้อมูลตัวอย่างประกอบด้วย:</h3>
                    <ul class="space-y-2">
                        <li><i class="fas fa-check text-green-500"></i> ผู้ใช้ทดสอบ</li>
                        <li><i class="fas fa-check text-green-500"></i> Affiliates ตัวอย่าง</li>
                        <li><i class="fas fa-check text-green-500"></i> คอมมิชชั่นตัวอย่าง</li>
                        <li><i class="fas fa-check text-green-500"></i> หน้าเพจต่างๆ</li>
                        <li><i class="fas fa-check text-green-500"></i> Email Templates</li>
                    </ul>
                </div>

                <div class="flex items-center space-x-4 mb-6">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="seed_demo" class="form-checkbox h-5 w-5 text-blue-600">
                        <span class="ml-2 text-gray-700">ติดตั้งข้อมูลตัวอย่าง (แนะนำสำหรับทดสอบระบบ)</span>
                    </label>
                </div>

                <div id="seed-status" class="hidden"></div>

                <div class="mt-8 flex justify-between">
                    <button class="btn btn-secondary" onclick="goToStep(3)">
                        <i class="fas fa-arrow-left"></i> ย้อนกลับ
                    </button>
                    <button class="btn btn-primary" id="btn-seed-data">
                        ถัดไป <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- Step 5: Complete -->
            <div class="setup-step" id="step-5">
                <div class="text-center">
                    <div class="mb-6">
                        <i class="fas fa-check-circle text-green-500 text-6xl"></i>
                    </div>
                    <h2 class="text-3xl font-bold mb-4 text-green-600">
                        การติดตั้งเสร็จสมบูรณ์!
                    </h2>
                    <p class="text-gray-600 mb-8">
                        ระบบของคุณพร้อมใช้งานแล้ว คุณสามารถเข้าสู่ระบบเพื่อเริ่มต้นใช้งานได้ทันที
                    </p>

                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-6 mb-6">
                        <h3 class="font-semibold mb-4">ขั้นตอนถัดไป:</h3>
                        <ul class="space-y-2 text-left">
                            <li class="flex items-start">
                                <i class="fas fa-angle-right text-blue-600 mt-1 mr-2"></i>
                                <span>เข้าสู่ระบบด้วยบัญชีแอดมินที่สร้างไว้</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-angle-right text-blue-600 mt-1 mr-2"></i>
                                <span>ตั้งค่าระบบตามความต้องการ</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-angle-right text-blue-600 mt-1 mr-2"></i>
                                <span>เริ่มใช้งาน Affiliate Marketing Platform</span>
                            </li>
                        </ul>
                    </div>

                    <button class="btn btn-primary btn-lg" id="btn-finish">
                        เข้าสู่ระบบ <i class="fas fa-sign-in-alt"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .step-indicator {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }

        .step-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #9ca3af;
            margin-bottom: 8px;
            transition: all 0.3s;
        }

        .step-indicator.active .step-circle {
            background: #3b82f6;
            color: white;
        }

        .step-indicator.completed .step-circle {
            background: #10b981;
            color: white;
        }

        .step-label {
            font-size: 12px;
            color: #6b7280;
            text-align: center;
        }

        .step-line {
            flex: 1;
            height: 2px;
            background: #e5e7eb;
            margin: 0 8px;
            position: relative;
            top: -20px;
        }

        .setup-step {
            display: none;
        }

        .setup-step.active {
            display: block;
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
        }

        .btn-primary:hover:not(:disabled) {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }

        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-secondary {
            background: #e5e7eb;
            color: #374151;
        }

        .btn-secondary:hover:not(:disabled) {
            background: #d1d5db;
        }

        .btn-lg {
            padding: 16px 32px;
            font-size: 18px;
        }

        .requirement-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            border-radius: 8px;
            background: #f9fafb;
        }

        .requirement-item.met {
            background: #ecfdf5;
            border-left: 4px solid #10b981;
        }

        .requirement-item.not-met {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3b82f6;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-right: 8px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>

    <script>
        let currentStep = 1;
        let licenseActivated = false;
        let adminCreated = false;

        document.addEventListener('DOMContentLoaded', function() {
            checkRequirements();
            setupEventListeners();
        });

        function setupEventListeners() {
            document.getElementById('btn-next-1').addEventListener('click', () => goToStep(2));
            document.getElementById('btn-verify-license').addEventListener('click', verifyLicense);
            document.getElementById('btn-create-admin').addEventListener('click', createAdmin);
            document.getElementById('btn-seed-data').addEventListener('click', seedData);
            document.getElementById('btn-finish').addEventListener('click', finishSetup);
        }

        function goToStep(step) {
            document.querySelectorAll('.setup-step').forEach(el => el.classList.remove('active'));
            document.getElementById(`step-${step}`).classList.add('active');

            document.querySelectorAll('.step-indicator').forEach((el, index) => {
                el.classList.remove('active', 'completed');
                if (index + 1 < step) {
                    el.classList.add('completed');
                } else if (index + 1 === step) {
                    el.classList.add('active');
                }
            });

            currentStep = step;
        }

        async function checkRequirements() {
            try {
                const response = await fetch('/setup/check-requirements');
                const data = await response.json();

                const list = document.getElementById('requirements-list');
                list.innerHTML = '';

                for (const [key, req] of Object.entries(data.requirements)) {
                    const div = document.createElement('div');
                    div.className = `requirement-item ${req.met ? 'met' : 'not-met'}`;
                    div.innerHTML = `
                        <span>${req.label}</span>
                        <span class="flex items-center">
                            ${req.value}
                            <i class="fas fa-${req.met ? 'check text-green-500' : 'times text-red-500'} ml-2"></i>
                        </span>
                    `;
                    list.appendChild(div);
                }

                document.getElementById('btn-next-1').disabled = !data.all_met;

                if (!data.all_met) {
                    const warning = document.createElement('div');
                    warning.className = 'mt-4 bg-red-50 border border-red-200 rounded-lg p-4 text-red-700';
                    warning.innerHTML = '<i class="fas fa-exclamation-triangle"></i> กรุณาแก้ไขข้อกำหนดที่ไม่ผ่านก่อนดำเนินการต่อ';
                    list.appendChild(warning);
                }
            } catch (error) {
                console.error('Error checking requirements:', error);
            }
        }

        async function verifyLicense() {
            const licenseKey = document.getElementById('license_key').value.trim();
            const btn = document.getElementById('btn-verify-license');
            const statusDiv = document.getElementById('license-status');

            if (!licenseKey) {
                showStatus(statusDiv, 'error', 'กรุณากรอก License Key');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> กำลังตรวจสอบ...';

            try {
                const response = await fetch('/setup/verify-license', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ license_key: licenseKey })
                });

                const data = await response.json();

                if (data.success) {
                    showStatus(statusDiv, 'success', data.message);
                    licenseActivated = true;
                    setTimeout(() => goToStep(3), 1500);
                } else {
                    showStatus(statusDiv, 'error', data.message);
                    btn.disabled = false;
                    btn.innerHTML = 'ยืนยัน License <i class="fas fa-check"></i>';
                }
            } catch (error) {
                showStatus(statusDiv, 'error', 'เกิดข้อผิดพลาด: ' + error.message);
                btn.disabled = false;
                btn.innerHTML = 'ยืนยัน License <i class="fas fa-check"></i>';
            }
        }

        async function createAdmin() {
            const form = document.getElementById('admin-form');
            const formData = new FormData(form);
            const btn = document.getElementById('btn-create-admin');
            const statusDiv = document.getElementById('admin-status');

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> กำลังสร้างบัญชี...';

            try {
                const response = await fetch('/setup/create-admin', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(Object.fromEntries(formData))
                });

                const data = await response.json();

                if (data.success) {
                    showStatus(statusDiv, 'success', data.message);
                    adminCreated = true;
                    setTimeout(() => goToStep(4), 1500);
                } else {
                    const errors = data.errors ? Object.values(data.errors).flat().join(', ') : data.message;
                    showStatus(statusDiv, 'error', errors);
                    btn.disabled = false;
                    btn.innerHTML = 'สร้างบัญชี <i class="fas fa-user-plus"></i>';
                }
            } catch (error) {
                showStatus(statusDiv, 'error', 'เกิดข้อผิดพลาด: ' + error.message);
                btn.disabled = false;
                btn.innerHTML = 'สร้างบัญชี <i class="fas fa-user-plus"></i>';
            }
        }

        async function seedData() {
            const seedDemo = document.getElementById('seed_demo').checked;
            const btn = document.getElementById('btn-seed-data');
            const statusDiv = document.getElementById('seed-status');

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> กำลังดำเนินการ...';

            try {
                const response = await fetch('/setup/seed-data', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ seed_demo: seedDemo })
                });

                const data = await response.json();

                if (data.success) {
                    showStatus(statusDiv, 'success', data.message);
                    setTimeout(() => goToStep(5), 1500);
                } else {
                    showStatus(statusDiv, 'error', data.message);
                    btn.disabled = false;
                    btn.innerHTML = 'ถัดไป <i class="fas fa-arrow-right"></i>';
                }
            } catch (error) {
                showStatus(statusDiv, 'error', 'เกิดข้อผิดพลาด: ' + error.message);
                btn.disabled = false;
                btn.innerHTML = 'ถัดไป <i class="fas fa-arrow-right"></i>';
            }
        }

        async function finishSetup() {
            const btn = document.getElementById('btn-finish');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> กำลังเสร็จสิ้น...';

            try {
                const response = await fetch('/setup/finalize', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    window.location.href = data.redirect_url;
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                    btn.disabled = false;
                    btn.innerHTML = 'เข้าสู่ระบบ <i class="fas fa-sign-in-alt"></i>';
                }
            } catch (error) {
                alert('เกิดข้อผิดพลาด: ' + error.message);
                btn.disabled = false;
                btn.innerHTML = 'เข้าสู่ระบบ <i class="fas fa-sign-in-alt"></i>';
            }
        }

        function showStatus(element, type, message) {
            element.className = `mt-4 p-4 rounded-lg ${type === 'success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700'}`;
            element.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                ${message}
            `;
            element.classList.remove('hidden');
        }
    </script>
</body>
</html>
