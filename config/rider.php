<?php

/**
 * 🛵 ระบบไรเดอร์ — ค่าที่ต้องอ่านก่อนถึง DB (ค่าธุรกิจอื่นอยู่ในตาราง settings ผ่าน DeliveryFeeCalculator)
 *
 * owner ตัดสิน (2026-10-13): "ไรเดอร์รับงานผ่านแอปเท่านั้น ในเว็บรับไม่ได้ แต่สมัครและใช้โปรไฟล์ได้"
 *   - หน้าเว็บ /user/rider/* : สมัคร ดูสถานะ เอกสาร รายได้ ตั้งค่า ได้ — รับงาน/ทำงานส่ง/เปิดรับงานไม่ได้
 *   - LINE : รับงานไม่ได้ (ตอบ reply ให้ไปใช้แอป)
 *   - API แอป : เส้นรับงาน/ทำงานส่ง/เปิดรับงาน ต้องส่ง header X-App-Build ≥ min_app_build
 *     (middleware App\Http\Middleware\EnsureRiderAppBuild) — ปิดรับงาน (offline) ทำได้เสมอ
 *
 * ⚠️ แก้ .env บน prod แล้วต้อง `php artisan config:cache` ทุกครั้ง (ห้ามเรียก env() นอกโฟลเดอร์ config/)
 */
return [

    // แอป Thai Prompt APP รุ่นแรกที่มีหน้าส่งมอบ (สแกน QR / รูป 2 รอบ) + ด่าน eKYC ครบ
    // แอปตอนนี้ส่ง X-App-Build: 44 คงที่ (thaiprompt/config/appConfig.ts → APP_FEATURE_BUILD)
    // ต่ำกว่านี้ / ไม่ส่ง header = 426 APP_UPDATE_REQUIRED
    'min_app_build' => (int) env('RIDER_MIN_APP_BUILD', 44),

    // หน้าดาวน์โหลดแอป (ลิงก์ในแบนเนอร์หน้าเว็บ + ข้อความ LINE + คำตอบ API)
    'app_download_path' => '/app/download',

];
