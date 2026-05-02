<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * บันทึกภาษาที่ผู้ใช้เลือก / auto-detect ต่อ platform+user
 *
 * @property int $id
 * @property string $platform
 * @property string $platform_user_id
 * @property string $locale  ('th' | 'lo')
 * @property string $source  ('auto' | 'manual' | 'admin')
 */
class FortuneUserLanguage extends Model
{
    protected $table = 'fortune_user_languages';

    protected $fillable = [
        'platform',
        'platform_user_id',
        'locale',
        'source',
    ];
}
