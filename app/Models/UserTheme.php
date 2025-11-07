<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserTheme extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'theme_id',
        'mode',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the theme
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the theme
     */
    public function theme()
    {
        return $this->belongsTo(Theme::class);
    }

    /**
     * Get active theme for user
     */
    public static function getActiveThemeForUser($userId)
    {
        return self::where('user_id', $userId)
            ->where('is_active', true)
            ->with('theme')
            ->first();
    }

    /**
     * Set theme for user
     */
    public static function setThemeForUser($userId, $themeId, $mode = 'auto')
    {
        // Deactivate all themes for user
        self::where('user_id', $userId)->update(['is_active' => false]);

        // Set new theme
        return self::updateOrCreate(
            [
                'user_id' => $userId,
                'theme_id' => $themeId,
            ],
            [
                'mode' => $mode,
                'is_active' => true,
            ]
        );
    }
}
