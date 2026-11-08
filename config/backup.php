<?php

/**
 * 🗄️ สำรองข้อมูลไป Cloudflare R2 (2026-11-08)
 *
 * เจ้าของสั่ง: "สำรองฐานข้อมูลทั้งก้อนไป R2 อัตโนมัติทุกวันเวลา 2.00 น. เก็บย้อนหลัง 7 วัน
 * ลบเขียนทับวนไป / สำรองข้อมูลเว็บไซต์ทั้งหมดวันที่ 10 และ 25 เก็บไว้เดือนเดียว"
 * และ "เก็บที่ R2 ต้องไม่ให้คนอื่นเข้าถึงไฟล์ได้"
 *
 * ## ช่องเก็บ (slot) — เขียนทับวนเอง ไม่ต้องมีโค้ดลบ
 *   db/db-1-mon.sql.zst.tpbk … db/db-7-sun.sql.zst.tpbk      ← 7 ช่อง ตามวันในสัปดาห์
 *   site/day10/files.tar.zst.tpbk + site/day10/database.sql.zst.tpbk
 *   site/day25/files.tar.zst.tpbk + site/day25/database.sql.zst.tpbk
 *
 * ## การเข้ารหัส (บังคับ)
 * เซิร์ฟเวอร์ถือแค่ "แม่กุญแจ" (public key) — ล็อกไฟล์ได้แต่เปิดไม่ได้
 * "ลูกกุญแจ" (private key) อยู่กับเจ้าของคนเดียว ⇒ ต่อให้ R2 หรือเซิร์ฟเวอร์หลุด ไฟล์สำรองก็อ่านไม่ได้
 * ⚠️ ทำลูกกุญแจหาย = ไฟล์สำรองทุกไฟล์เปิดไม่ได้อีกเลย
 * วิธีถอดรหัส: scripts/backup/decrypt-backup.sh — อ่าน docs/BACKUP_R2.md
 */
return [

    // เปิดด้วย BACKUP_R2_ENABLED=true ใน .env — ปิดเป็นค่าเริ่มต้นเพราะโค้ดนี้ส่งไปเครื่องลูกค้าด้วย
    // เปิดแล้วตั้งค่าไม่ครบ = Telegram เตือนทุกคืน (ตั้งใจ — สำรองเงียบหายแย่กว่าโดนเตือน)
    'enabled' => env('BACKUP_R2_ENABLED', false),

    // ═══ Cloudflare R2 — bucket ส่วนตัวแยกจาก fortune-voice ═══
    // ⚠️ ห้ามเปิด public access / r2.dev / custom domain ให้ bucket นี้
    //    token ควรสิทธิ์ "Object Read & Write" เฉพาะ bucket นี้เท่านั้น
    'r2' => [
        // ค่าว่างใน .env (deploy STEP 4.6 เติมบรรทัดว่างให้) ต้องตกไปใช้ account เดียวกับ fortune-voice — จึงใช้ ?: ไม่ใช่ default ของ env()
        'account_id' => env('BACKUP_R2_ACCOUNT_ID') ?: env('FORTUNE_VOICE_R2_ACCOUNT_ID'),
        'access_key_id' => env('BACKUP_R2_ACCESS_KEY_ID'),
        'secret_access_key' => env('BACKUP_R2_SECRET_ACCESS_KEY'),
        'bucket' => env('BACKUP_R2_BUCKET') ?: 'thaiprompt-backup',
        // คำนำหน้าในบัคเก็ต — แยกเว็บหลายตัวลงบัคเก็ตเดียวกันได้
        'prefix' => env('BACKUP_R2_PREFIX') ?: 'main.thaiprompt.online',
    ],

    // ═══ แม่กุญแจ (RSA public key, PEM) ═══
    // ไม่ใช่ความลับ แต่ต้องคู่กับลูกกุญแจที่เจ้าของเก็บไว้ — ไม่มีไฟล์นี้ = ไม่ยอมสำรอง (กันอัปโหลดไฟล์เปล่า)
    // ⚠️ storage/app/backup-key ต้องรอด deploy: deploy.sh ย้ายไป ../backup_key_data แล้ว symlink กลับ (STEP 4.5.2)
    'public_key_path' => env('BACKUP_PUBLIC_KEY_PATH', storage_path('app/backup-key/backup-public.pem')),

    // ที่ทำงานชั่วคราว (ถูกตัดออกจากไฟล์สำรองเว็บเอง และลบทิ้งทุกครั้งที่จบงาน)
    'work_dir' => env('BACKUP_WORK_DIR', storage_path('app/backup-tmp')),

    // พื้นที่ว่างขั้นต่ำก่อนเริ่ม (GB) — ฐานข้อมูลบีบแล้ว ~0.5 GB, ไฟล์เว็บ ~2.5 GB
    'min_free_gb' => (int) env('BACKUP_MIN_FREE_GB', 8),

    // จำนวน thread ของ zstd — เครื่องมี 24 core ใช้แค่ 4 ไม่ให้เว็บช้าตอนตีสอง
    'zstd_threads' => (int) env('BACKUP_ZSTD_THREADS', 4),

    // ═══ ไฟล์เว็บ — ตัดของที่ติดตั้งใหม่ได้/ไม่มีค่าออก (path เทียบกับ base_path()) ═══
    // โฟลเดอร์ส่วนตัวนอก git tree ที่ storage/app/* symlink ไป (fortune_data, ekyc_data …) ถูกเก็บให้เอง
    'site_excludes' => [
        './vendor',
        './node_modules',
        './.git',
        './storage/logs',
        './storage/framework/cache',
        './storage/framework/sessions',
        './storage/framework/views',
        './storage/debugbar',
        './storage/app/backup-tmp',
        // dump ฐานข้อมูลก่อน deploy ของ deploy.sh — วัด prod 2026-11-08 ได้ 7.7 GB (ฐานข้อมูลเรามีชุดของตัวเองแล้ว)
        './backups',
    ],
];
