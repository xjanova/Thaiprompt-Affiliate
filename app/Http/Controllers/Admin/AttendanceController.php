<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Department;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function index(Request $request)
    {
        $query = AttendanceRecord::with(['employee.department', 'employee.position']);

        // Date filter
        $date = $request->get('date', today()->toDateString());
        $query->whereDate('date', $date);

        // Department filter
        if ($request->filled('department_id')) {
            $query->whereHas('employee', function($q) use ($request) {
                $q->where('department_id', $request->get('department_id'));
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $perPage = $request->get('per_page', 50);
        $attendances = $query->latest('date')->paginate($perPage)->withQueryString();

        $departments = Department::where('is_active', true)->get();

        // Get summary stats for the selected date
        $stats = [
            'total' => AttendanceRecord::whereDate('date', $date)->count(),
            'present' => AttendanceRecord::whereDate('date', $date)->where('status', 'present')->count(),
            'absent' => AttendanceRecord::whereDate('date', $date)->where('status', 'absent')->count(),
            'late' => AttendanceRecord::whereDate('date', $date)->where('is_late', true)->count(),
            'on_leave' => AttendanceRecord::whereDate('date', $date)->where('status', 'leave')->count(),
        ];

        return view('admin.hrm.attendance.index', compact('attendances', 'departments', 'date', 'stats'));
    }

    public function employeeReport(Request $request, Employee $employee)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $attendances = AttendanceRecord::where('employee_id', $employee->id)
                                      ->whereBetween('date', [$startDate, $endDate])
                                      ->orderBy('date')
                                      ->get();

        $summary = $this->attendanceService->getEmployeeAttendanceSummary($employee->id, $month, $year);

        return view('admin.hrm.attendance.employee-report', compact('employee', 'attendances', 'summary', 'month', 'year'));
    }

    public function markAbsent(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'remarks' => 'nullable|string',
        ]);

        $this->attendanceService->markAbsent(
            $validated['employee_id'],
            $validated['date'],
            $validated['remarks'] ?? null
        );

        return back()->with('success', 'Employee marked as absent');
    }

    public function bulkImport(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'file' => 'required|file|mimes:csv,txt',
        ]);

        // TODO: Implement CSV import logic

        return back()->with('success', 'Attendance imported successfully');
    }
}
