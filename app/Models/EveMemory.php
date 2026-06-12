<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 🧠 (2026-06-12) One distilled lesson in Eve's long-term memory.
 *
 * Written by the LLM itself via the [REMEMBER:keywords|lesson] tag (extracted
 * server-side in EveController::chat) or by the operator via POST
 * /api/admin/eve/memories. Retrieved per chat turn by keyword overlap.
 */
class EveMemory extends Model
{
    protected $fillable = [
        'title',
        'keywords',
        'content',
        'source',
        'admin_name',
        'use_count',
        'last_used_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'use_count' => 'integer',
    ];
}
