<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\SecurityLog;
use App\Models\VendorEmployee;

class CheckVendorEmployeePermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission = null): Response
    {
        $user = $request->user();

        // Get vendor from route parameter or user's vendor relationship
        $vendorId = $request->route('vendor') ?? $user->vendor?->id;

        if (!$vendorId) {
            abort(404, 'Vendor not found.');
        }

        // Vendor owner always has access
        if ($user->vendor && $user->vendor->id == $vendorId) {
            return $next($request);
        }

        // Check if user is an employee of this vendor
        $employee = VendorEmployee::where('vendor_id', $vendorId)
            ->where('user_id', $user->id)
            ->first();

        if (!$employee) {
            SecurityLog::logEvent('unauthorized_access', [
                'severity' => 'medium',
                'description' => 'User attempted to access vendor area without employee record',
                'metadata' => [
                    'user_id' => $user->id,
                    'vendor_id' => $vendorId,
                    'requested_url' => $request->fullUrl(),
                ]
            ]);

            abort(403, 'Access denied. You are not an employee of this vendor.');
        }

        // Check employment status
        if ($employee->employment_status !== 'active') {
            SecurityLog::logEvent('unauthorized_access', [
                'severity' => 'medium',
                'description' => 'Inactive vendor employee attempted access',
                'metadata' => [
                    'user_id' => $user->id,
                    'vendor_id' => $vendorId,
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
                'description' => 'Vendor employee attempted access from unauthorized IP',
                'metadata' => [
                    'user_id' => $user->id,
                    'vendor_id' => $vendorId,
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
                'description' => 'Vendor employee attempted to access resource without permission',
                'metadata' => [
                    'user_id' => $user->id,
                    'vendor_id' => $vendorId,
                    'required_permission' => $permission,
                    'employee_permissions' => $employee->getPermissions(),
                ]
            ]);

            abort(403, 'Access denied. You do not have permission to: ' . str_replace('_', ' ', $permission));
        }

        // Update last active timestamp
        $employee->updateLastActive();

        // Add employee to request for later use
        $request->merge(['vendor_employee' => $employee]);

        return $next($request);
    }
}
