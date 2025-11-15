<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ThemeColor Model
 *
 * จัดการสี Gradient สำหรับ Arrow X Theme
 *
 * @property int $id
 * @property int $theme_setting_id
 * @property string $scheme_name
 * @property bool $is_default
 * @property string $primary_start
 * @property string $primary_middle
 * @property string $primary_end
 * @property string $secondary_start
 * @property string $secondary_middle
 * @property string $secondary_end
 * @property string $accent_color
 * @property string $success_color
 * @property string $warning_color
 * @property string $error_color
 * @property string $info_color
 * @property string $gradient_direction
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ThemeColor extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'theme_colors';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'theme_setting_id',
        'scheme_name',
        'is_default',
        'primary_start',
        'primary_middle',
        'primary_end',
        'secondary_start',
        'secondary_middle',
        'secondary_end',
        'accent_color',
        'success_color',
        'warning_color',
        'error_color',
        'info_color',
        'background_primary',
        'background_secondary',
        'background_tertiary',
        'dark_background_primary',
        'dark_background_secondary',
        'dark_background_tertiary',
        'text_primary',
        'text_secondary',
        'text_tertiary',
        'dark_text_primary',
        'dark_text_secondary',
        'dark_text_tertiary',
        'border_color',
        'divider_color',
        'dark_border_color',
        'dark_divider_color',
        'gradient_direction',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'theme_setting_id' => 'integer',
        'is_default' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ ThemeSetting
     *
     * @return BelongsTo
     */
    public function themeSetting(): BelongsTo
    {
        return $this->belongsTo(ThemeSetting::class);
    }

    /**
     * สร้าง CSS Gradient string
     *
     * @param string $type 'primary' | 'secondary'
     * @return string
     */
    public function getGradientCss(string $type = 'primary'): string
    {
        $start = $this->{"{$type}_start"};
        $middle = $this->{"{$type}_middle"};
        $end = $this->{"{$type}_end"};
        $direction = str_replace('-', ' ', $this->gradient_direction);

        return "linear-gradient({$direction}, {$start}, {$middle}, {$end})";
    }
}
