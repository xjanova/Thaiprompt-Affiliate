<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees
     */
    public function index(Request $request)
    {
        $query = Employee::with(['user', 'department', 'position', 'manager']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('employee_id', 'like', '%'.$search.'%')
                  ->orWhere('first_name', 'like', '%'.$search.'%')
                  ->orWhere('last_name', 'like', '%'.$search.'%')
                  ->orWhere('work_email', 'like', '%'.$search.'%')
                  ->orWhere('mobile_phone', 'like', '%'.$search.'%');
            });
        }

        // Department filter
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->get('department_id'));
        }

        // Position filter
        if ($request->filled('position_id')) {
            $query->where('position_id', $request->get('position_id'));
        }

        // Employment status filter
        if ($request->filled('employment_status')) {
            $query->where('employment_status', $request->get('employment_status'));
        }

        // Employment type filter
        if ($request->filled('employment_type')) {
            $query->where('employment_type', $request->get('employment_type'));
        }

        // Pagination
        $perPage = $request->get('per_page', 20);
        $employees = $query->latest()->paginate($perPage)->withQueryString();

        $departments = Department::where('is_active', true)->get();
        $positions = Position::where('is_active', true)->get();

        return view('admin.hrm.employees.index', compact('employees', 'departments', 'positions'));
    }

    /**
     * Show the form for creating a new employee
     */
    public function create()
    {
        $departments = Department::where('is_active', true)->get();
        $positions = Position::where('is_active', true)->get();
        $managers = Employee::where('employment_status', 'active')
                           ->whereHas('position', function($q) {
                               $q->where('level', 'in', ['manager', 'director', 'lead']);
                           })
                           ->get();

        return view('admin.hrm.employees.create', compact('departments', 'positions', 'managers'));
    }

    /**
     * Store a newly created employee
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'employee_id' => 'required|string|unique:employees,employee_id',
            'department_id' => 'required|exists:departments,id',
            'position_id' => 'required|exists:positions,id',
            'hire_date' => 'required|date',
            'employment_type' => 'required|in:full_time,part_time,contract,intern,freelance',
            'employment_status' => 'required|in:active,probation,notice_period,resigned,terminated,retired',
            'work_email' => 'required|email|unique:employees,work_email',
            'personal_email' => 'nullable|email',
            'mobile_phone' => 'required|string',
            'basic_salary' => 'required|numeric|min:0',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'nationality' => 'nullable|string',
            'id_card_number' => 'nullable|string',
            'manager_id' => 'nullable|exists:employees,id',
            'bank_name' => 'nullable|string',
            'bank_account_number' => 'nullable|string',
            'current_address' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Create user account
            $user = User::create([
                'name' => $request->first_name . ' ' . $request->last_name,
                'email' => $request->work_email,
                'password' => Hash::make('password123'), // Default password
                'role' => 'employee',
            ]);

            // Create employee record
            $validated['user_id'] = $user->id;
            $employee = Employee::create($validated);

            DB::commit();

            return redirect()->route('admin.hrm.employees.show', $employee)
                           ->with('success', 'Employee created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                       ->with('error', 'Failed to create employee: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified employee
     */
    public function show(Employee $employee)
    {
        $employee->load([
            'user',
            'department',
            'position',
            'manager',
            'directReports',
            'documents',
            'attendanceRecords' => function($q) {
                $q->latest()->limit(30);
            },
            'leaveRequests' => function($q) {
                $q->latest()->limit(10);
            },
            'payrollRecords' => function($q) {
                $q->latest()->limit(12);
            },
            'performanceReviews' => function($q) {
                $q->latest()->limit(5);
            },
            'performanceGoals' => function($q) {
                $q->latest()->limit(10);
            },
            'trainingEnrollments.trainingCourse' => function($q) {
                $q->latest();
            },
        ]);

        return view('admin.hrm.employees.show', compact('employee'));
    }

    /**
     * Show the form for editing the specified employee
     */
    public function edit(Employee $employee)
    {
        $departments = Department::where('is_active', true)->get();
        $positions = Position::where('is_active', true)->get();
        $managers = Employee::where('employment_status', 'active')
                           ->where('id', '!=', $employee->id)
                           ->whereHas('position', function($q) {
                               $q->where('level', 'in', ['manager', 'director', 'lead']);
                           })
                           ->get();

        return view('admin.hrm.employees.edit', compact('employee', 'departments', 'positions', 'managers'));
    }

    /**
     * Update the specified employee
     */
    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'position_id' => 'required|exists:positions,id',
            'employment_type' => 'required|in:full_time,part_time,contract,intern,freelance',
            'employment_status' => 'required|in:active,probation,notice_period,resigned,terminated,retired',
            'work_email' => 'required|email|unique:employees,work_email,' . $employee->id,
            'personal_email' => 'nullable|email',
            'mobile_phone' => 'required|string',
            'basic_salary' => 'required|numeric|min:0',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'nationality' => 'nullable|string',
            'id_card_number' => 'nullable|string',
            'manager_id' => 'nullable|exists:employees,id',
            'bank_name' => 'nullable|string',
            'bank_account_number' => 'nullable|string',
            'current_address' => 'nullable|string',
        ]);

        $employee->update($validated);

        return redirect()->route('admin.hrm.employees.show', $employee)
                       ->with('success', 'Employee updated successfully');
    }

    /**
     * Remove the specified employee (soft delete)
     */
    public function destroy(Employee $employee)
    {
        $employee->delete();

        return redirect()->route('admin.hrm.employees.index')
                       ->with('success', 'Employee deleted successfully');
    }

    /**
     * Export employees to CSV
     */
    public function export(Request $request)
    {
        $query = Employee::with(['department', 'position']);

        // Apply same filters as index
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->get('department_id'));
        }

        if ($request->filled('employment_status')) {
            $query->where('employment_status', $request->get('employment_status'));
        }

        $employees = $query->get();

        $filename = 'employees_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($employees) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'Employee ID',
                'Full Name',
                'Email',
                'Phone',
                'Department',
                'Position',
                'Employment Type',
                'Employment Status',
                'Hire Date',
                'Basic Salary',
            ]);

            // CSV data
            foreach ($employees as $employee) {
                fputcsv($file, [
                    $employee->employee_id,
                    $employee->full_name,
                    $employee->work_email,
                    $employee->mobile_phone,
                    $employee->department->name ?? '',
                    $employee->position->title ?? '',
                    ucwords(str_replace('_', ' ', $employee->employment_type)),
                    ucwords(str_replace('_', ' ', $employee->employment_status)),
                    $employee->hire_date->format('Y-m-d'),
                    $employee->basic_salary,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
