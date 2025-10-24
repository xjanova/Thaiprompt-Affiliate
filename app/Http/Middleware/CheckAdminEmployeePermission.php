<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\SecurityLog;

class CheckAdminEmployeePermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission = null): Response
    {
        $user = $request->user();

        // Super admin always has access
        if ($user->hasRole('admin') && !$user->isAdminEmployee()) {
            return $next($request);
        }

        // Check if user is an admin employee
        $employee = $user->adminEmployee;

        if (!$employee) {
            SecurityLog::logEvent('unauthorized_access', [
                'severity' => 'medium',
                'description' => 'User attempted to access admin area without employee record',
                'metadata' => [
                    'user_id' => $user->id,
                    'requested_url' => $request->fullUrl(),
                ]
            ]);

            abort(403, 'Access denied. You do not have admin employee privileges.');
        }

        // Check employment status
        if ($employee->employment_status !== 'active') {
            SecurityLog::logEvent('unauthorized_access', [
                'severity' => 'medium',
                'description' => 'Inactive employee attempted to access admin area',
                'metadata' => [
                    'user_id' => $user->id,
                    'employee_status' => $employee->employment_status,
                ]
            ]);

            abort(403, 'Access denied. Your employment status is: ' . $employee->employment_status);
        }

        // Check work schedule
        if (!$employee->isWorkingNow()) {
            abort(403, 'Access denied. You can only access this area during your work hours.');
        }

        // Check IP address
        if (!$employee->isIpAllowed($request->ip())) {
            SecurityLog::logEvent('unauthorized_access', [
                'severity' => 'high',
                'description' => 'Employee attempted access from unauthorized IP',
                'metadata' => [
                    'user_id' => $user->id,
                    'ip_address' => $request->ip(),
                    'allowed_ips' => $employee->allowed_ip_addresses,
                ]
            ]);

            abort(403, 'Access denied. Your IP address is not authorized.');
        }

        // Check specific permission if provided
        if ($permission && !$employee->can($permission)) {
            SecurityLog::logEvent('unauthorized_access', [
                'severity' => 'medium',
                'description' => 'Employee attempted to access resource without permission',
                'metadata' => [
                    'user_id' => $user->id,
                    'required_permission' => $permission,
                    'employee_permissions' => $employee->getPermissions(),
                ]
            ]);

            abort(403, 'Access denied. You do not have permission to: ' . str_replace('_', ' ', $permission));
        }

        // Update last active timestamp
        $employee->updateLastActive();

        return $next($request);
    }
}
