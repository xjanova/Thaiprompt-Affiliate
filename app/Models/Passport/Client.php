<?php

namespace App\Models\Passport;

use Laravel\Passport\Client as PassportClient;

/**
 * Custom Passport Client — ข้ามหน้า consent เฉพาะ client ที่เชื่อถือ (juntraweb)
 *
 * 🔐 (2026-07-17) auto-login: user ที่ล็อกอิน Thaiprompt อยู่แล้ว คลิกจากเว็บ →
 *   /oauth/authorize (web+auth guard) → skipsAuthorization()=true → 302 กลับ
 *   พร้อม code ทันที ไม่มีหน้าขออนุญาต
 *
 * gate ราย client ผ่านคอลัมน์ oauth_clients.skip_authorization — client อื่น
 * ที่สร้างในอนาคตจะไม่ auto-approve เว้นแต่ตั้ง flag ให้ชัด (defense in depth)
 *
 * signature แบบ variadic (...$args) — รองรับทั้ง Passport ที่เรียกแบบไม่มี arg
 * และแบบ ($user, $scopes) (LSP widening ปลอดภัยข้าม minor 12.x)
 */
class Client extends PassportClient
{
    public function skipsAuthorization(...$args): bool
    {
        return (bool) ($this->skip_authorization ?? false);
    }
}
