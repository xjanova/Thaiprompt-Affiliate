<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiGenProviderConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'config_key',
        'config_value',
        'is_encrypted',
        'description',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    /**
     * Get the provider that owns the config.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiGenProvider::class, 'provider_id');
    }

    /**
     * Get the decrypted config value.
     */
    public function getDecryptedValueAttribute()
    {
        if ($this->is_encrypted && $this->config_value) {
            try {
                return decrypt($this->config_value);
            } catch (\Exception $e) {
                return null;
            }
        }
        return $this->config_value;
    }
}
