<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LineSignupTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_key',
        'template_name',
        'description',
        'flex_message_json',
        'variables',
        'is_active',
        'usage_count',
    ];

    protected $casts = [
        'flex_message_json' => 'array',
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Replace variables in template
     */
    public function render(array $data = []): array
    {
        $json = json_encode($this->flex_message_json);

        foreach ($data as $key => $value) {
            $json = str_replace("{{" . $key . "}}", $value, $json);
        }

        return json_decode($json, true);
    }
}
