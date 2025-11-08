<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AppMaintenance extends Model
{
    use HasFactory;

    protected $table = 'app_maintenance';

    protected $fillable = [
        'is_maintenance_mode',
        'title',
        'title_en',
        'message',
        'message_en',
        'image_url',
        'scheduled_start',
        'scheduled_end',
        'actual_start',
        'actual_end',
        'show_countdown',
        'allow_admin_access',
        'allowed_user_ids',
        'bypass_key',
        'platform',
    ];

    protected $casts = [
        'is_maintenance_mode' => 'boolean',
        'show_countdown' => 'boolean',
        'allow_admin_access' => 'boolean',
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
        'actual_start' => 'datetime',
        'actual_end' => 'datetime',
        'allowed_user_ids' => 'array',
    ];

    /**
     * Get the singleton instance
     */
    public static function getInstance()
    {
        return static::firstOrCreate([], [
            'is_maintenance_mode' => false,
            'title' => 'ระบบกำลังปรับปรุง',
            'title_en' => 'Under Maintenance',
            'show_countdown' => true,
            'allow_admin_access' => true,
        ]);
    }

    /**
     * Check if app is in maintenance mode
     */
    public static function isInMaintenanceMode($platform = null)
    {
        $maintenance = static::getInstance();

        if (!$maintenance->is_maintenance_mode) {
            return false;
        }

        // Check platform
        if ($platform && $maintenance->platform &&
            $maintenance->platform !== 'all' &&
            $maintenance->platform !== $platform) {
            return false;
        }

        return true;
    }

    /**
     * Check if user can bypass maintenance mode
     */
    public function canUserBypass($userId = null, $bypassKey = null, $isAdmin = false)
    {
        if (!$this->is_maintenance_mode) {
            return true;
        }

        // Check admin access
        if ($isAdmin && $this->allow_admin_access) {
            return true;
        }

        // Check bypass key
        if ($bypassKey && $this->bypass_key && $bypassKey === $this->bypass_key) {
            return true;
        }

        // Check allowed user IDs
        if ($userId && $this->allowed_user_ids && in_array($userId, $this->allowed_user_ids)) {
            return true;
        }

        return false;
    }

    /**
     * Get maintenance end time for countdown
     */
    public function getEndTime()
    {
        return $this->scheduled_end ?? $this->actual_end;
    }

    /**
     * Start maintenance mode
     */
    public function startMaintenance()
    {
        $this->update([
            'is_maintenance_mode' => true,
            'actual_start' => Carbon::now(),
        ]);
    }

    /**
     * End maintenance mode
     */
    public function endMaintenance()
    {
        $this->update([
            'is_maintenance_mode' => false,
            'actual_end' => Carbon::now(),
        ]);
    }
}
