<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ThemeTypography Model
 *
 * จัดการฟอนต์และตัวอักษรสำหรับ Arrow X Theme
 *
 * @property int $id
 * @property int $theme_setting_id
 * @property string $primary_font
 * @property string $secondary_font
 * @property string $code_font
 * @property float $base_font_size
 * @property float $heading_h1_size
 * @property float $heading_h2_size
 * @property float $heading_h3_size
 * @property float $heading_h4_size
 * @property float $heading_h5_size
 * @property float $heading_h6_size
 * @property int $thin_weight
 * @property int $normal_weight
 * @property int $medium_weight
 * @property int $semibold_weight
 * @property int $bold_weight
 * @property int $extrabold_weight
 * @property float $heading_line_height
 * @property float $body_line_height
 * @property float $heading_letter_spacing
 * @property float $body_letter_spacing
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ThemeTypography extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'theme_typography';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'theme_setting_id',
        'primary_font',
        'secondary_font',
        'code_font',
        'base_font_size',
        'heading_h1_size',
        'heading_h2_size',
        'heading_h3_size',
        'heading_h4_size',
        'heading_h5_size',
        'heading_h6_size',
        'thin_weight',
        'normal_weight',
        'medium_weight',
        'semibold_weight',
        'bold_weight',
        'extrabold_weight',
        'heading_line_height',
        'body_line_height',
        'heading_letter_spacing',
        'body_letter_spacing',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'theme_setting_id' => 'integer',
        'base_font_size' => 'float',
        'heading_h1_size' => 'float',
        'heading_h2_size' => 'float',
        'heading_h3_size' => 'float',
        'heading_h4_size' => 'float',
        'heading_h5_size' => 'float',
        'heading_h6_size' => 'float',
        'thin_weight' => 'integer',
        'normal_weight' => 'integer',
        'medium_weight' => 'integer',
        'semibold_weight' => 'integer',
        'bold_weight' => 'integer',
        'extrabold_weight' => 'integer',
        'heading_line_height' => 'float',
        'body_line_height' => 'float',
        'heading_letter_spacing' => 'float',
        'body_letter_spacing' => 'float',
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
}
