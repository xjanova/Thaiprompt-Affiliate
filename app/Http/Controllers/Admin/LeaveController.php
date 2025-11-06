<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Employee;
use App\Models\Department;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $query = LeaveRequest::with(['employee.department', 'employee.position', 'leaveType', 'approver']);

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // Department filter
        if ($request->filled('department_id')) {
            $query->whereHas('employee', function($q) use ($request) {
                $q->where('department_id', $request->get('department_id'));
            });
        }

        // Leave type filter
        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', $request->get('leave_type_id'));
        }

        // Date range filter
        if ($request->filled('start_date')) {
            $query->where('start_date', '>=', $request->get('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->where('end_date', '<=', $request->get('end_date'));
        }

        $perPage = $request->get('per_page', 20);
        $leaveRequests = $query->latest()->paginate($perPage)->withQueryString();

        $leaveTypes = LeaveType::active()->get();
        $departments = Department::where('is_active', true)->get();

        // Stats
        $stats = [
            'pending' => LeaveRequest::where('status', 'pending')->count(),
            'approved' => LeaveRequest::where('status', 'approved')->count(),
            'rejected' => LeaveRequest::where('status', 'rejected')->count(),
        ];

        return view('admin.hrm.leave.index', compact('leaveRequests', 'leaveTypes', 'departments', 'stats'));
    }

    public function show(LeaveRequest $leaveRequest)
    {
        $leaveRequest->load(['employee.department', 'employee.position', 'employee.manager', 'leaveType', 'approver', 'rejector']);

        return view('admin.hrm.leave.show', compact('leaveRequest'));
    }

    public function approve(Request $request, LeaveRequest $leaveRequest)
    {
        $request->validate([
            'remarks' => 'nullable|string',
        ]);

        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Leave request is not in pending status');
        }

        $leaveRequest->approve(auth()->id(), $request->remarks);

        return back()->with('success', 'Leave request approved successfully');
    }

    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        $request->validate([
            'reason' => 'required|string',
        ]);

        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Leave request is not in pending status');
        }

        $leaveRequest->reject(auth()->id(), $request->reason);

        return back()->with('success', 'Leave request rejected');
    }

    public function calendar(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $startDate = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = \Carbon\Carbon::create($year, $month, 1)->endOfMonth();

        $leaveRequests = LeaveRequest::with(['employee', 'leaveType'])
                                    ->where('status', 'approved')
                                    ->dateRange($startDate, $endDate)
                                    ->get();

        return view('admin.hrm.leave.calendar', compact('leaveRequests', 'month', 'year'));
    }

    // Leave Types Management
    public function leaveTypes()
    {
        $leaveTypes = LeaveType::withCount('leaveRequests')->get();

        return view('admin.hrm.leave.types.index', compact('leaveTypes'));
    }

    public function createLeaveType()
    {
        return view('admin.hrm.leave.types.create');
    }

    public function storeLeaveType(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:leave_types,code',
            'description' => 'nullable|string',
            'default_days_per_year' => 'required|numeric|min:0',
            'is_paid' => 'boolean',
            'requires_approval' => 'boolean',
            'requires_document' => 'boolean',
            'max_consecutive_days' => 'nullable|integer|min:1',
            'min_advance_notice_days' => 'integer|min:0',
            'carry_forward' => 'boolean',
            'max_carry_forward_days' => 'nullable|integer|min:0',
            'color' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        LeaveType::create($validated);

        return redirect()->route('admin.hrm.leave.types')
                       ->with('success', 'Leave type created successfully');
    }

    public function editLeaveType(LeaveType $leaveType)
    {
        return view('admin.hrm.leave.types.edit', compact('leaveType'));
    }

    public function updateLeaveType(Request $request, LeaveType $leaveType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:leave_types,code,' . $leaveType->id,
            'description' => 'nullable|string',
            'default_days_per_year' => 'required|numeric|min:0',
            'is_paid' => 'boolean',
            'requires_approval' => 'boolean',
            'requires_document' => 'boolean',
            'max_consecutive_days' => 'nullable|integer|min:1',
            'min_advance_notice_days' => 'integer|min:0',
            'carry_forward' => 'boolean',
            'max_carry_forward_days' => 'nullable|integer|min:0',
            'color' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $leaveType->update($validated);

        return redirect()->route('admin.hrm.leave.types')
                       ->with('success', 'Leave type updated successfully');
    }
}
