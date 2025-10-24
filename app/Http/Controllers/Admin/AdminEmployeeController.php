<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminEmployee;
use App\Models\User;
use App\Models\SecurityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class AdminEmployeeController extends Controller
{
    /**
     * Display a listing of admin employees
     */
    public function index()
    {
        $employees = AdminEmployee::with(['user', 'creator'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.employees.index', compact('employees'));
    }

    /**
     * Show the form for creating a new employee
     */
    public function create()
    {
        return view('admin.employees.create');
    }

    /**
     * Store a newly created employee
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'department' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
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

        // Assign admin role
        $user->assignRole('admin');

        // Generate employee code
        $employeeCode = 'AE' . str_pad($user->id, 6, '0', STR_PAD_LEFT);

        // Create employee record
        $employee = AdminEmployee::create([
            'user_id' => $user->id,
            'created_by' => auth()->id(),
            'employee_code' => $employeeCode,
            'department' => $request->department,
            'position' => $request->position,
            'employment_status' => 'active',
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
            'severity' => 'medium',
            'description' => 'New admin employee added',
            'metadata' => [
                'employee_id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'user_id' => $user->id,
            ]
        ]);

        return redirect()->route('admin.employees.index')
            ->with('success', 'Employee created successfully.');
    }

    /**
     * Display the specified employee
     */
    public function show(AdminEmployee $employee)
    {
        $employee->load(['user', 'creator']);

        return view('admin.employees.show', compact('employee'));
    }

    /**
     * Show the form for editing the specified employee
     */
    public function edit(AdminEmployee $employee)
    {
        $employee->load('user');

        return view('admin.employees.edit', compact('employee'));
    }

    /**
     * Update the specified employee
     */
    public function update(Request $request, AdminEmployee $employee)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $employee->user_id,
            'phone' => 'nullable|string|max:20',
            'department' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'employment_status' => 'required|in:active,inactive,suspended,terminated',
            'permissions' => 'nullable|array',
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
            'department' => $request->department,
            'position' => $request->position,
            'employment_status' => $request->employment_status,
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
            'severity' => 'medium',
            'description' => 'Admin employee updated',
            'metadata' => [
                'employee_id' => $employee->id,
                'changes' => $request->only(['employment_status', 'permissions']),
            ]
        ]);

        return redirect()->route('admin.employees.index')
            ->with('success', 'Employee updated successfully.');
    }

    /**
     * Remove the specified employee
     */
    public function destroy(AdminEmployee $employee)
    {
        // Prevent self-deletion
        if ($employee->user_id === auth()->id()) {
            return redirect()->back()
                ->with('error', 'You cannot delete your own employee record.');
        }

        $employeeCode = $employee->employee_code;
        $userId = $employee->user_id;

        $employee->delete();

        // Log event
        SecurityLog::logEvent('employee_removed', [
            'severity' => 'medium',
            'description' => 'Admin employee deleted',
            'metadata' => [
                'employee_code' => $employeeCode,
                'user_id' => $userId,
            ]
        ]);

        return redirect()->route('admin.employees.index')
            ->with('success', 'Employee deleted successfully.');
    }
}
