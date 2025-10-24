<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorEmployee;
use App\Models\User;
use App\Models\SecurityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class VendorEmployeeController extends Controller
{
    /**
     * Display a listing of vendor employees
     */
    public function index(Request $request)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            abort(404, 'Vendor not found');
        }

        $employees = VendorEmployee::with(['user', 'creator'])
            ->where('vendor_id', $vendor->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('vendor.employees.index', compact('employees', 'vendor'));
    }

    /**
     * Show the form for creating a new employee
     */
    public function create(Request $request)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            abort(404, 'Vendor not found');
        }

        return view('vendor.employees.create', compact('vendor'));
    }

    /**
     * Store a newly created employee
     */
    public function store(Request $request)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            abort(404, 'Vendor not found');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'position' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'max_discount_percentage' => 'nullable|numeric|min:0|max:100',
            'max_transaction_amount' => 'nullable|numeric|min:0',
            'commission_percentage' => 'nullable|numeric|min:0|max:100',
            'allowed_ip_addresses' => 'nullable|array',
            'work_start_time' => 'nullable|date_format:H:i',
            'work_end_time' => 'nullable|date_format:H:i',
            'allowed_days' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Create user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'status' => 'active',
        ]);

        // Generate employee code
        $employeeCode = 'VE' . $vendor->id . '-' . str_pad($user->id, 4, '0', STR_PAD_LEFT);

        // Create employee record
        $employee = VendorEmployee::create([
            'vendor_id' => $vendor->id,
            'user_id' => $user->id,
            'created_by' => auth()->id(),
            'employee_code' => $employeeCode,
            'position' => $request->position,
            'employment_status' => 'active',
            'max_discount_percentage' => $request->max_discount_percentage,
            'max_transaction_amount' => $request->max_transaction_amount,
            'commission_percentage' => $request->commission_percentage ?? 0,
            'allowed_ip_addresses' => $request->allowed_ip_addresses,
            'work_start_time' => $request->work_start_time,
            'work_end_time' => $request->work_end_time,
            'allowed_days' => $request->allowed_days,
            'notes' => $request->notes,
            'hired_at' => now(),
        ]);

        // Set permissions
        $permissions = $request->input('permissions', []);
        foreach ($permissions as $permission => $value) {
            $employee->{'can_' . $permission} = (bool) $value;
        }
        $employee->save();

        // Log event
        SecurityLog::logEvent('employee_added', [
            'severity' => 'low',
            'description' => 'New vendor employee added',
            'metadata' => [
                'vendor_id' => $vendor->id,
                'employee_id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'user_id' => $user->id,
            ]
        ]);

        return redirect()->route('vendor.employees.index')
            ->with('success', 'Employee created successfully.');
    }

    /**
     * Display the specified employee
     */
    public function show(Request $request, VendorEmployee $employee)
    {
        $vendor = $request->user()->vendor;

        // Verify employee belongs to this vendor
        if ($employee->vendor_id !== $vendor->id) {
            abort(403, 'Unauthorized');
        }

        $employee->load(['user', 'creator']);

        return view('vendor.employees.show', compact('employee', 'vendor'));
    }

    /**
     * Show the form for editing the specified employee
     */
    public function edit(Request $request, VendorEmployee $employee)
    {
        $vendor = $request->user()->vendor;

        // Verify employee belongs to this vendor
        if ($employee->vendor_id !== $vendor->id) {
            abort(403, 'Unauthorized');
        }

        $employee->load('user');

        return view('vendor.employees.edit', compact('employee', 'vendor'));
    }

    /**
     * Update the specified employee
     */
    public function update(Request $request, VendorEmployee $employee)
    {
        $vendor = $request->user()->vendor;

        // Verify employee belongs to this vendor
        if ($employee->vendor_id !== $vendor->id) {
            abort(403, 'Unauthorized');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $employee->user_id,
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:255',
            'employment_status' => 'required|in:active,inactive,suspended,terminated',
            'permissions' => 'nullable|array',
            'max_discount_percentage' => 'nullable|numeric|min:0|max:100',
            'max_transaction_amount' => 'nullable|numeric|min:0',
            'commission_percentage' => 'nullable|numeric|min:0|max:100',
            'allowed_ip_addresses' => 'nullable|array',
            'work_start_time' => 'nullable|date_format:H:i',
            'work_end_time' => 'nullable|date_format:H:i',
            'allowed_days' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Update user
        $employee->user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        // Update employee
        $employee->update([
            'position' => $request->position,
            'employment_status' => $request->employment_status,
            'max_discount_percentage' => $request->max_discount_percentage,
            'max_transaction_amount' => $request->max_transaction_amount,
            'commission_percentage' => $request->commission_percentage ?? 0,
            'allowed_ip_addresses' => $request->allowed_ip_addresses,
            'work_start_time' => $request->work_start_time,
            'work_end_time' => $request->work_end_time,
            'allowed_days' => $request->allowed_days,
            'notes' => $request->notes,
        ]);

        // Update permissions
        $permissions = $request->input('permissions', []);
        foreach ($permissions as $permission => $value) {
            $employee->{'can_' . $permission} = (bool) $value;
        }
        $employee->save();

        // Handle termination
        if ($request->employment_status === 'terminated' && !$employee->terminated_at) {
            $employee->terminated_at = now();
            $employee->save();
        }

        // Log event
        SecurityLog::logEvent('permission_change', [
            'severity' => 'low',
            'description' => 'Vendor employee updated',
            'metadata' => [
                'vendor_id' => $vendor->id,
                'employee_id' => $employee->id,
                'changes' => $request->only(['employment_status', 'permissions']),
            ]
        ]);

        return redirect()->route('vendor.employees.index')
            ->with('success', 'Employee updated successfully.');
    }

    /**
     * Remove the specified employee
     */
    public function destroy(Request $request, VendorEmployee $employee)
    {
        $vendor = $request->user()->vendor;

        // Verify employee belongs to this vendor
        if ($employee->vendor_id !== $vendor->id) {
            abort(403, 'Unauthorized');
        }

        $employeeCode = $employee->employee_code;
        $userId = $employee->user_id;

        $employee->delete();

        // Log event
        SecurityLog::logEvent('employee_removed', [
            'severity' => 'low',
            'description' => 'Vendor employee deleted',
            'metadata' => [
                'vendor_id' => $vendor->id,
                'employee_code' => $employeeCode,
                'user_id' => $userId,
            ]
        ]);

        return redirect()->route('vendor.employees.index')
            ->with('success', 'Employee deleted successfully.');
    }
}
