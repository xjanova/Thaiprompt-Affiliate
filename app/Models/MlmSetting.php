<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MlmSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
        'description_th',
    ];

    /**
     * Get the value with proper casting
     */
    public function getTypedValue()
    {
        return match ($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'number' => is_numeric($this->value) ? (float) $this->value : 0,
            'json' => json_decode($this->value, true),
            default => $this->value,
        };
    }

    /**
     * Set value with proper formatting
     */
    public function setTypedValue($value)
    {
        $this->value = match ($this->type) {
            'boolean' => $value ? '1' : '0',
            'json' => is_string($value) ? $value : json_encode($value),
            default => (string) $value,
        };

        $this->save();
    }

    /**
     * Get setting by key
     */
    public static function get($key, $default = null)
    {
        $setting = static::where('key', $key)->first();

        return $setting ? $setting->getTypedValue() : $default;
    }

    /**
     * Set setting by key
     */
    public static function set($key, $value, $type = 'string')
    {
        $setting = static::firstOrCreate(['key' => $key], ['type' => $type]);
        $setting->setTypedValue($value);

        return $setting;
    }
}
