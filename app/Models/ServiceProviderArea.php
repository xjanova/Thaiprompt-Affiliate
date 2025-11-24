<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * ServiceProviderArea Model - ความสัมพันธ์ระหว่าง Provider กับพื้นที่
 *
 * Pivot model สำหรับเชื่อม service_providers กับ service_areas
 *
 * @property int $id
 * @property int $provider_id
 * @property int $area_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ServiceProviderArea extends Pivot
{
    protected $table = 'service_provider_areas';

    public $incrementing = true;

    protected $fillable = [
        'provider_id',
        'area_id',
    ];

    //===========================================
    // Relationships
    //===========================================

    /**
     * ผู้ให้บริการ
     */
    public function provider()
    {
        return $this->belongsTo(ServiceProvider::class, 'provider_id');
    }

    /**
     * พื้นที่บริการ
     */
    public function area()
    {
        return $this->belongsTo(ServiceArea::class, 'area_id');
    }
}
