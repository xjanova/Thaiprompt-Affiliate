<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** บันทึกที่มาจากสตอรี่ โดยไม่เก็บเนื้อหาแชตหรือแก้สถานะบิล */
class FortuneStoryEvent extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'inbound_at' => 'datetime',
            'welcomed_at' => 'datetime',
        ];
    }
}
