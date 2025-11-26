<?php

namespace App\Http\Middleware;

use App\Models\AppMaintenance;
use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware ตรวจสอบโหมดปิดปรับปรุงระบบ
 *
 * ถ้าระบบอยู่ในโหมด maintenance จะแสดงหน้า 503
 * ยกเว้น:
 * - Admin users (ถ้าตั้งค่าอนุญาต)
 * - Routes ที่อนุญาต (เช่น login, admin)
 * - มี bypass key
 */
class CheckMaintenanceMode
{
    /**
     * Routes ที่อนุญาตให้เข้าถึงได้แม้อยู่ใน maintenance mode
     *
     * @var array<string>
     */
    protected array $allowedRoutes = [
        'login',
        'admin/*',
        'api/v1/health',
        'api/v1/app-config',
        'sanctum/*',
        '_debugbar/*',
        'livewire/*',
    ];

    /**
     * จัดการ request
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // ตรวจสอบโหมด maintenance
        if (!$this->isInMaintenanceMode()) {
            return $next($request);
        }

        // ตรวจสอบว่าสามารถ bypass ได้หรือไม่
        if ($this->canBypass($request)) {
            return $next($request);
        }

        // แสดงหน้า 503 Maintenance
        return response()->view('errors.503', [], 503);
    }

    /**
     * ตรวจสอบว่าระบบอยู่ในโหมด maintenance หรือไม่
     *
     * @return bool
     */
    protected function isInMaintenanceMode(): bool
    {
        // ตรวจสอบจาก SiteSetting
        try {
            $siteSettings = SiteSetting::getSetting();
            if ($siteSettings && $siteSettings->maintenance_mode) {
                return true;
            }
        } catch (\Exception $e) {
            // ถ้าดึง SiteSetting ไม่ได้ ให้ข้ามไป
        }

        // ตรวจสอบจาก AppMaintenance (สำหรับ web platform)
        try {
            if (class_exists(AppMaintenance::class)) {
                $appMaintenance = AppMaintenance::getInstance();
                if ($appMaintenance && $appMaintenance->isInMaintenanceMode('web')) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            // ถ้าดึง AppMaintenance ไม่ได้ ให้ข้ามไป
        }

        return false;
    }

    /**
     * ตรวจสอบว่าสามารถ bypass maintenance mode ได้หรือไม่
     *
     * @param Request $request
     * @return bool
     */
    protected function canBypass(Request $request): bool
    {
        // 1. ตรวจสอบว่าเป็น route ที่อนุญาตหรือไม่
        if ($this->isAllowedRoute($request)) {
            return true;
        }

        // 2. ตรวจสอบว่าเป็น Admin หรือไม่
        if ($this->isAdminUser($request)) {
            return true;
        }

        // 3. ตรวจสอบ bypass key จาก query string หรือ cookie
        if ($this->hasValidBypassKey($request)) {
            return true;
        }

        // 4. ตรวจสอบจาก AppMaintenance allowed_user_ids
        if ($this->isAllowedUser($request)) {
            return true;
        }

        return false;
    }

    /**
     * ตรวจสอบว่าเป็น route ที่อนุญาตหรือไม่
     *
     * @param Request $request
     * @return bool
     */
    protected function isAllowedRoute(Request $request): bool
    {
        $path = $request->path();

        foreach ($this->allowedRoutes as $allowedRoute) {
            // รองรับ wildcard pattern
            $pattern = str_replace(['*', '/'], ['.*', '\/'], $allowedRoute);

            if (preg_match('/^' . $pattern . '$/i', $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * ตรวจสอบว่าเป็น Admin user หรือไม่
     *
     * @param Request $request
     * @return bool
     */
    protected function isAdminUser(Request $request): bool
    {
        // ตรวจสอบจาก AppMaintenance ว่าอนุญาต admin เข้าถึงหรือไม่
        try {
            if (class_exists(AppMaintenance::class)) {
                $appMaintenance = AppMaintenance::getInstance();

                // ถ้าไม่อนุญาต admin ให้ข้ามไป
                if ($appMaintenance && !$appMaintenance->allow_admin_access) {
                    return false;
                }
            }
        } catch (\Exception $e) {
            // ถ้าดึงไม่ได้ ให้อนุญาต admin เข้าถึงได้เป็นค่าเริ่มต้น
        }

        // ตรวจสอบว่า user เป็น admin หรือไม่
        $user = $request->user();
        if (!$user) {
            return false;
        }

        // ตรวจสอบ role admin หรือ super_admin
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        // ตรวจสอบจาก role field
        if (isset($user->role) && in_array($user->role, ['admin', 'super_admin'])) {
            return true;
        }

        return false;
    }

    /**
     * ตรวจสอบ bypass key
     *
     * @param Request $request
     * @return bool
     */
    protected function hasValidBypassKey(Request $request): bool
    {
        try {
            if (class_exists(AppMaintenance::class)) {
                $appMaintenance = AppMaintenance::getInstance();

                if ($appMaintenance && $appMaintenance->bypass_key) {
                    $bypassKey = $request->query('maintenance_bypass')
                        ?? $request->cookie('maintenance_bypass');

                    if ($bypassKey && $bypassKey === $appMaintenance->bypass_key) {
                        return true;
                    }
                }
            }
        } catch (\Exception $e) {
            // ถ้าดึงไม่ได้ ให้ข้ามไป
        }

        return false;
    }

    /**
     * ตรวจสอบว่าเป็น user ที่อนุญาตหรือไม่
     *
     * @param Request $request
     * @return bool
     */
    protected function isAllowedUser(Request $request): bool
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        try {
            if (class_exists(AppMaintenance::class)) {
                $appMaintenance = AppMaintenance::getInstance();

                if ($appMaintenance && is_array($appMaintenance->allowed_user_ids)) {
                    if (in_array($user->id, $appMaintenance->allowed_user_ids)) {
                        return true;
                    }
                }
            }
        } catch (\Exception $e) {
            // ถ้าดึงไม่ได้ ให้ข้ามไป
        }

        return false;
    }
}
