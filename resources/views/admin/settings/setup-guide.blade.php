@extends('layouts.admin')

@section('title', 'คู่มือการติดตั้ง Google Cloud Vision API')

@section('content')
<div class="max-w-5xl mx-auto space-y-6 pb-10">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-lg shadow-lg p-8 text-white">
        <div class="flex items-center gap-4 mb-4">
            <i class="fas fa-book-open text-4xl"></i>
            <div>
                <h1 class="text-3xl font-bold">คู่มือการติดตั้ง Google Cloud Vision API</h1>
                <p class="text-blue-100 mt-2">สำหรับระบบ OCR (Optical Character Recognition) ในการตรวจจับข้อมูลจากบัตรประชาชนและใบขับขี่</p>
            </div>
        </div>
    </div>

    <!-- Table of Contents -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="fas fa-list text-indigo-600"></i>
            สารบัญ
        </h2>
        <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300">
            <li><a href="#overview" class="text-blue-600 hover:underline">ภาพรวมของ Google Cloud Vision API</a></li>
            <li><a href="#prerequisites" class="text-blue-600 hover:underline">สิ่งที่ต้องเตรียมก่อนเริ่มต้น</a></li>
            <li><a href="#step1" class="text-blue-600 hover:underline">สร้างโปรเจกต์ใน Google Cloud Console</a></li>
            <li><a href="#step2" class="text-blue-600 hover:underline">เปิดใช้งาน Cloud Vision API</a></li>
            <li><a href="#step3" class="text-blue-600 hover:underline">สร้าง Service Account</a></li>
            <li><a href="#step4" class="text-blue-600 hover:underline">ดาวน์โหลดและอัปโหลด Credentials</a></li>
            <li><a href="#step5" class="text-blue-600 hover:underline">ทดสอบการเชื่อมต่อ</a></li>
            <li><a href="#pricing" class="text-blue-600 hover:underline">ราคาและค่าใช้จ่าย</a></li>
            <li><a href="#troubleshooting" class="text-blue-600 hover:underline">แก้ไขปัญหาที่พบบ่อย</a></li>
        </ol>
    </div>

    <!-- Overview -->
    <div id="overview" class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <span class="text-indigo-600">1.</span>
            <i class="fas fa-cloud text-indigo-600"></i>
            ภาพรวมของ Google Cloud Vision API
        </h2>
        <div class="prose dark:prose-invert max-w-none">
            <p class="text-gray-700 dark:text-gray-300 mb-4">
                Google Cloud Vision API เป็นเครื่องมือ AI ที่ทรงพลังในการวิเคราะห์รูปภาพ รองรับการอ่านข้อความจากภาพ (OCR) ในหลายภาษา รวมถึงภาษาไทย
            </p>
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <h3 class="font-semibold text-blue-900 dark:text-blue-300 mb-2">ฟีเจอร์หลัก:</h3>
                <ul class="list-disc list-inside space-y-1 text-blue-800 dark:text-blue-300">
                    <li>อ่านข้อความจากภาพได้แม่นยำสูง (รองรับภาษาไทย)</li>
                    <li>ตรวจจับและจำแนกข้อมูลเอกสารอัตโนมัติ</li>
                    <li>รองรับภาพที่มีคุณภาพหลากหลาย</li>
                    <li>API ที่ใช้งานง่าย มีเอกสารประกอบครบถ้วน</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Prerequisites -->
    <div id="prerequisites" class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <span class="text-indigo-600">2.</span>
            <i class="fas fa-check-square text-indigo-600"></i>
            สิ่งที่ต้องเตรียมก่อนเริ่มต้น
        </h2>
        <div class="space-y-3">
            <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-slate-700 rounded-lg">
                <i class="fas fa-check-circle text-green-600 mt-1"></i>
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white">บัญชี Google</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ต้องมีบัญชี Google (Gmail) สำหรับเข้าใช้ Google Cloud Console</p>
                </div>
            </div>
            <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-slate-700 rounded-lg">
                <i class="fas fa-check-circle text-green-600 mt-1"></i>
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white">บัตรเครดิต/เดบิต</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">จำเป็นสำหรับการยืนยันตัวตน (Google มี Free Tier ให้ใช้งานฟรีรายเดือน)</p>
                </div>
            </div>
            <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-slate-700 rounded-lg">
                <i class="fas fa-check-circle text-green-600 mt-1"></i>
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white">การเข้าถึงอินเทอร์เน็ต</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">สำหรับการเข้าถึง Google Cloud Console และการเรียกใช้ API</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 1 -->
    <div id="step1" class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <span class="text-indigo-600">3.</span>
            <i class="fas fa-folder-plus text-indigo-600"></i>
            สร้างโปรเจกต์ใน Google Cloud Console
        </h2>
        <div class="space-y-4">
            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold">1</span>
                <div class="flex-1">
                    <p class="text-gray-700 dark:text-gray-300 mb-2">
                        เปิดเบราว์เซอร์และไปที่ <a href="https://console.cloud.google.com" target="_blank" class="text-blue-600 hover:underline font-semibold">console.cloud.google.com</a>
                    </p>
                    <div class="bg-gray-100 dark:bg-slate-700 rounded p-2 text-sm font-mono">
                        https://console.cloud.google.com
                    </div>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold">2</span>
                <div class="flex-1">
                    <p class="text-gray-700 dark:text-gray-300">
                        คลิกที่ dropdown "Select a project" ด้านบนซ้าย แล้วคลิก "NEW PROJECT"
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold">3</span>
                <div class="flex-1">
                    <p class="text-gray-700 dark:text-gray-300 mb-2">
                        ตั้งชื่อโปรเจกต์ เช่น "my-ocr-project" แล้วคลิก "CREATE"
                    </p>
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded p-3 text-sm">
                        <i class="fas fa-lightbulb text-yellow-600 mr-2"></i>
                        <span class="text-yellow-800 dark:text-yellow-300">
                            <strong>เคล็ดลับ:</strong> ตั้งชื่อให้สื่อความหมายและจดจำง่าย
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2 -->
    <div id="step2" class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <span class="text-indigo-600">4.</span>
            <i class="fas fa-toggle-on text-indigo-600"></i>
            เปิดใช้งาน Cloud Vision API
        </h2>
        <div class="space-y-4">
            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold">1</span>
                <div class="flex-1">
                    <p class="text-gray-700 dark:text-gray-300">
                        ในเมนูด้านซ้าย ไปที่ "APIs & Services" > "Library"
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold">2</span>
                <div class="flex-1">
                    <p class="text-gray-700 dark:text-gray-300 mb-2">
                        ค้นหา "Cloud Vision API" ในช่องค้นหา
                    </p>
                    <div class="bg-gray-100 dark:bg-slate-700 rounded p-2 text-sm font-mono">
                        Cloud Vision API
                    </div>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold">3</span>
                <div class="flex-1">
                    <p class="text-gray-700 dark:text-gray-300">
                        คลิกที่ "Cloud Vision API" แล้วคลิกปุ่ม "ENABLE"
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold">4</span>
                <div class="flex-1">
                    <p class="text-gray-700 dark:text-gray-300">
                        รอสักครู่ให้ API เปิดใช้งานเสร็จสมบูรณ์
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 3 -->
    <div id="step3" class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <span class="text-indigo-600">5.</span>
            <i class="fas fa-user-shield text-indigo-600"></i>
            สร้าง Service Account
        </h2>
        <div class="space-y-4">
            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold">1</span>
                <div class="flex-1">
                    <p class="text-gray-700 dark:text-gray-300">
                        ไปที่ "APIs & Services" > "Credentials"
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold">2</span>
                <div class="flex-1">
                    <p class="text-gray-700 dark:text-gray-300">
                        คลิก "CREATE CREDENTIALS" > "Service account"
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold">3</span>
                <div class="flex-1">
                    <p class="text-gray-700 dark:text-gray-300 mb-2">
                        กรอกข้อมูล Service Account:
                    </p>
                    <ul class="list-disc list-inside space-y-1 ml-4 text-gray-600 dark:text-gray-400 text-sm">
                        <li>Service account name: <code class="bg-gray-100 dark:bg-slate-700 px-2 py-1 rounded">ocr-service</code></li>
                        <li>Description: <code class="bg-gray-100 dark:bg-slate-700 px-2 py-1 rounded">Service account for OCR operations</code></li>
                    </ul>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold">4</span>
                <div class="flex-1">
                    <p class="text-gray-700 dark:text-gray-300">
                        คลิก "CREATE AND CONTINUE"
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold">5</span>
                <div class="flex-1">
                    <p class="text-gray-700 dark:text-gray-300 mb-2">
                        เลือก Role: <strong>Cloud Vision AI Service Agent</strong> หรือ <strong>Owner</strong>
                    </p>
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded p-3 text-sm">
                        <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                        <span class="text-blue-800 dark:text-blue-300">
                            Role กำหนดสิทธิ์การเข้าถึง API ควรเลือก Role ที่เหมาะสมตามความจำเป็น
                        </span>
                    </div>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold">6</span>
                <div class="flex-1">
                    <p class="text-gray-700 dark:text-gray-300">
                        คลิก "CONTINUE" แล้วคลิก "DONE"
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 4 -->
    <div id="step4" class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <span class="text-indigo-600">6.</span>
            <i class="fas fa-key text-indigo-600"></i>
            ดาวน์โหลดและอัปโหลด Credentials
        </h2>
        <div class="space-y-4">
            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold">1</span>
                <div class="flex-1">
                    <p class="text-gray-700 dark:text-gray-300">
                        ในหน้า Credentials คลิกที่ Service Account ที่สร้างไว้
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold">2</span>
                <div class="flex-1">
                    <p class="text-gray-700 dark:text-gray-300">
                        ไปที่แท็บ "KEYS" แล้วคลิก "ADD KEY" > "Create new key"
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold">3</span>
                <div class="flex-1">
                    <p class="text-gray-700 dark:text-gray-300 mb-2">
                        เลือกประเภท <strong>JSON</strong> แล้วคลิก "CREATE"
                    </p>
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded p-3 text-sm">
                        <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                        <span class="text-red-800 dark:text-red-300">
                            <strong>คำเตือน:</strong> เก็บไฟล์นี้ให้ปลอดภัย! อย่าแชร์หรืออัปโหลดไปที่สาธารณะ
                        </span>
                    </div>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold">4</span>
                <div class="flex-1">
                    <p class="text-gray-700 dark:text-gray-300 mb-2">
                        ไฟล์ JSON จะถูกดาวน์โหลดอัตโนมัติ กลับมาที่หน้า
                        <a href="{{ route('admin.settings.ocr') }}" class="text-blue-600 hover:underline font-semibold">ตั้งค่า OCR</a>
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold">5</span>
                <div class="flex-1">
                    <p class="text-gray-700 dark:text-gray-300">
                        อัปโหลดไฟล์ JSON ในหน้าตั้งค่า OCR แล้วคลิก "บันทึกการตั้งค่า"
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 5 -->
    <div id="step5" class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <span class="text-indigo-600">7.</span>
            <i class="fas fa-vial text-indigo-600"></i>
            ทดสอบการเชื่อมต่อ
        </h2>
        <div class="space-y-4">
            <p class="text-gray-700 dark:text-gray-300">
                หลังจากอัปโหลดไฟล์ credentials แล้ว ให้ทดสอบการเชื่อมต่อโดย:
            </p>

            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-8 h-8 bg-green-600 text-white rounded-full flex items-center justify-center font-bold">✓</span>
                <div class="flex-1">
                    <p class="text-gray-700 dark:text-gray-300">
                        คลิกปุ่ม "ทดสอบการเชื่อมต่อ" ในหน้าตั้งค่า OCR
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-8 h-8 bg-green-600 text-white rounded-full flex items-center justify-center font-bold">✓</span>
                <div class="flex-1">
                    <p class="text-gray-700 dark:text-gray-300">
                        หากสำเร็จ จะมีข้อความแจ้งเตือนว่า "เชื่อมต่อ Google Cloud Vision API สำเร็จ!"
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-8 h-8 bg-green-600 text-white rounded-full flex items-center justify-center font-bold">✓</span>
                <div class="flex-1">
                    <p class="text-gray-700 dark:text-gray-300">
                        ทดสอบด้วยการอัปโหลดบัตรประชาชนหรือใบขับขี่ในหน้า KYC
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Pricing -->
    <div id="pricing" class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <span class="text-indigo-600">8.</span>
            <i class="fas fa-dollar-sign text-indigo-600"></i>
            ราคาและค่าใช้จ่าย
        </h2>
        <div class="space-y-4">
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                <h3 class="font-semibold text-green-900 dark:text-green-300 mb-2 flex items-center gap-2">
                    <i class="fas fa-gift"></i>
                    Free Tier (ฟรี)
                </h3>
                <p class="text-green-800 dark:text-green-300 text-sm">
                    Google มอบ <strong>1,000 requests ต่อเดือน ฟรี</strong> สำหรับ OCR Text Detection
                </p>
            </div>

            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <h3 class="font-semibold text-blue-900 dark:text-blue-300 mb-2">อัตราค่าบริการ (เกินโควต้าฟรี)</h3>
                <ul class="space-y-1 text-blue-800 dark:text-blue-300 text-sm">
                    <li>• 1-1,000 requests: <strong>ฟรี</strong></li>
                    <li>• 1,001-5,000,000 requests: <strong>$1.50 ต่อ 1,000 requests</strong></li>
                    <li>• มากกว่า 5,000,000 requests: <strong>$0.60 ต่อ 1,000 requests</strong></li>
                </ul>
            </div>

            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                <p class="text-yellow-800 dark:text-yellow-300 text-sm">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>หมายเหตุ:</strong> ราคาอาจมีการเปลี่ยนแปลง ตรวจสอบราคาล่าสุดได้ที่
                    <a href="https://cloud.google.com/vision/pricing" target="_blank" class="text-yellow-900 dark:text-yellow-200 underline font-semibold">
                        cloud.google.com/vision/pricing
                    </a>
                </p>
            </div>
        </div>
    </div>

    <!-- Troubleshooting -->
    <div id="troubleshooting" class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <span class="text-indigo-600">9.</span>
            <i class="fas fa-wrench text-indigo-600"></i>
            แก้ไขปัญหาที่พบบ่อย
        </h2>
        <div class="space-y-4">
            <!-- Problem 1 -->
            <div class="border-l-4 border-red-500 bg-red-50 dark:bg-red-900/20 p-4 rounded">
                <h3 class="font-semibold text-red-900 dark:text-red-300 mb-2">
                    ❌ ไม่พบไฟล์ credentials
                </h3>
                <p class="text-red-800 dark:text-red-300 text-sm mb-2"><strong>สาเหตุ:</strong> ยังไม่ได้อัปโหลดไฟล์ JSON</p>
                <p class="text-red-700 dark:text-red-400 text-sm"><strong>วิธีแก้:</strong> อัปโหลดไฟล์ JSON ที่ดาวน์โหลดจาก Google Cloud Console</p>
            </div>

            <!-- Problem 2 -->
            <div class="border-l-4 border-red-500 bg-red-50 dark:bg-red-900/20 p-4 rounded">
                <h3 class="font-semibold text-red-900 dark:text-red-300 mb-2">
                    ❌ API ไม่ได้เปิดใช้งาน
                </h3>
                <p class="text-red-800 dark:text-red-300 text-sm mb-2"><strong>สาเหตุ:</strong> ยังไม่ได้เปิดใช้งาน Cloud Vision API ในโปรเจกต์</p>
                <p class="text-red-700 dark:text-red-400 text-sm"><strong>วิธีแก้:</strong> ไปที่ Google Cloud Console > APIs & Services > Library > เปิดใช้ Cloud Vision API</p>
            </div>

            <!-- Problem 3 -->
            <div class="border-l-4 border-red-500 bg-red-50 dark:bg-red-900/20 p-4 rounded">
                <h3 class="font-semibold text-red-900 dark:text-red-300 mb-2">
                    ❌ Permission denied
                </h3>
                <p class="text-red-800 dark:text-red-300 text-sm mb-2"><strong>สาเหตุ:</strong> Service Account ไม่มีสิทธิ์เข้าถึง API</p>
                <p class="text-red-700 dark:text-red-400 text-sm"><strong>วิธีแก้:</strong> ตรวจสอบ Role ของ Service Account ควรเป็น "Cloud Vision AI Service Agent" หรือ "Owner"</p>
            </div>

            <!-- Problem 4 -->
            <div class="border-l-4 border-red-500 bg-red-50 dark:bg-red-900/20 p-4 rounded">
                <h3 class="font-semibold text-red-900 dark:text-red-300 mb-2">
                    ❌ Quota exceeded
                </h3>
                <p class="text-red-800 dark:text-red-300 text-sm mb-2"><strong>สาเหตุ:</strong> ใช้ API เกินโควต้าที่กำหนด (1,000 requests/เดือน สำหรับ Free Tier)</p>
                <p class="text-red-700 dark:text-red-400 text-sm"><strong>วิธีแก้:</strong> รอให้โควต้ารีเซ็ตหรือเปิดใช้งาน Billing เพื่อใช้แบบเสียค่าบริการ</p>
            </div>
        </div>
    </div>

    <!-- Back Button -->
    <div class="flex justify-center">
        <a href="{{ route('admin.settings.ocr') }}"
           class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition flex items-center gap-2 shadow-lg">
            <i class="fas fa-arrow-left"></i>
            <span>กลับไปหน้าตั้งค่า OCR</span>
        </a>
    </div>
</div>
@endsection
