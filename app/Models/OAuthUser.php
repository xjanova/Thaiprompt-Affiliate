<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens;

/**
 * ผู้ใช้สำหรับ OAuth2 (Passport) — guard 'api-oauth' เท่านั้น
 *
 * 🔐 (2026-07-17) แยกออกจาก App\Models\User โดยเจตนา:
 *   - User หลักใช้ Laravel\Sanctum\HasApiTokens (mobile + JuntraMlm API)
 *   - ถ้าเอา Passport\HasApiTokens ไปใส่ใน User ตรงๆ จะชนกับ Sanctum
 *     (createToken()/tokens()/token()) → mobile login + MLM API พัง
 *   - subclass นี้ override เมธอดที่สืบทอดมาจาก Sanctum ด้วย Passport trait
 *     (คนละคลาส ไม่ชนกันในคลาสเดียว) เพื่อให้ middleware 'scopes' เรียก
 *     $request->user()->token() / ->tokenCan($scope) ได้แบบ Passport
 *
 * ใช้ตาราง users เดียวกัน — เป็นผู้ใช้คนเดิม แค่ resolve ผ่าน guard คนละตัว
 */
class OAuthUser extends User
{
    use HasApiTokens;

    protected $table = 'users';
}
