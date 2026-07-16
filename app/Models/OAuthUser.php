<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;

/**
 * ผู้ใช้สำหรับ OAuth2 (Passport) — guard 'api-oauth' เท่านั้น
 *
 * 🔐 (2026-07-17) **ห้าม extends App\Models\User เด็ดขาด**
 *   User ใช้ Laravel\Sanctum\HasApiTokens อยู่แล้ว ซึ่งมี
 *     createToken(string $name, array $abilities = ['*'], ?DateTimeInterface $expiresAt = null)
 *   ถ้า subclass เพิ่ม Laravel\Passport\HasApiTokens (createToken($name, array $scopes = []))
 *   → Passport trait จะ override เมธอด createToken ที่สืบทอดมาจาก Sanctum ด้วย signature
 *     ที่ไม่ compatible → **E_COMPILE_ERROR "Declaration ... must be compatible"** (uncatchable)
 *   → คลาสโหลดไม่ได้ → guard 'api-oauth' resolve user ไม่ได้ → /api/user ที่มี token = 500
 *   (ตรวจแล้วบน prod จริง 2026-07-17 — subclass approach เดิมพัง ต้องแยกลำดับชั้น)
 *
 * แก้: extends Authenticatable (Illuminate\Foundation\Auth\User) ตรงๆ + Passport HasApiTokens
 *   → ได้ token() / tokenCan() / withAccessToken() ของ Passport โดยไม่สืบทอด Sanctum createToken
 *   → ไม่แตะ App\Models\User = mobile (Sanctum) + JuntraMlm API ปลอดภัย 100%
 *
 * ใช้ตาราง users เดียวกัน (ผู้ใช้คนเดิม แค่ resolve คนละ guard). ฟิลด์ที่ /api/user ใช้
 *   (id, email, name, line_user_id) เป็นคอลัมน์จริงบน users ทั้งหมด — ไม่ต้องพึ่ง accessor/relation ของ User
 */
class OAuthUser extends Authenticatable
{
    use HasApiTokens;

    /**
     * ใช้ตาราง users ร่วมกับ App\Models\User (คนละ guard เท่านั้น)
     *
     * @var string
     */
    protected $table = 'users';
}
