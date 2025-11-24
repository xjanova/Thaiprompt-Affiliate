<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * ServiceCategory Model - หมวดหมู่บริการ
 *
 * เก็บหมวดหมู่ของบริการต่างๆ เช่น นวด, สปา, ทำความสะอาด, delivery
 *
 * @property int $id
 * @property string $name ชื่อหมวดหมู่
 * @property string $slug URL slug
 * @property string|null $description คำอธิบาย
 * @property string|null $icon Font Awesome icon class
 * @property string|null $image URL รูปภาพ
 * @property string|null $color สีประจำหมวดหมู่
 * @property bool $is_active เปิดใช้งาน
 * @property int $sort_order ลำดับการแสดงผล
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class ServiceCategory extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'service_categories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'image',
        'color',
        'is_active',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot method - สร้าง slug อัตโนมัติ
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('name') && empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    //===========================================
    // Relationships
    //===========================================

    /**
     * บริการทั้งหมดในหมวดหมู่นี้
     *
     * @return HasMany
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'category_id');
    }

    /**
     * บริการที่เปิดใช้งานในหมวดหมู่นี้
     *
     * @return HasMany
     */
    public function activeServices(): HasMany
    {
        return $this->hasMany(Service::class, 'category_id')
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    //===========================================
    // Scopes
    //===========================================

    /**
     * Scope: เฉพาะหมวดหมู่ที่เปิดใช้งาน
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: เรียงตามลำดับ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    //===========================================
    // Methods
    //===========================================

    /**
     * ตรวจสอบว่าหมวดหมู่มีบริการหรือไม่
     *
     * @return bool
     */
    public function hasServices(): bool
    {
        return $this->services()->exists();
    }

    /**
     * นับจำนวนบริการที่เปิดใช้งาน
     *
     * @return int
     */
    public function getActiveServicesCount(): int
    {
        return $this->activeServices()->count();
    }

    /**
     * ได้ icon HTML
     *
     * @return string
     */
    public function getIconHtml(): string
    {
        if (empty($this->icon)) {
            return '<i class="fas fa-folder"></i>';
        }

        return '<i class="' . e($this->icon) . '"></i>';
    }

    /**
     * ได้ URL หมวดหมู่
     *
     * @return string
     */
    public function getUrl(): string
    {
        return route('services.category', $this->slug);
    }
}
