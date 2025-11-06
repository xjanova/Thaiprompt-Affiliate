<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayrollRecord;
use App\Models\Employee;
use App\Models\Department;
use App\Services\PayrollService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PayrollController extends Controller
{
    protected $payrollService;

    public function __construct(PayrollService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    public function index(Request $request)
    {
        $query = PayrollRecord::with(['employee.department', 'employee.position']);

        // Period filter
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        if ($month && $year) {
            $query->where('month', $month)->where('year', $year);
        }

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

        $perPage = $request->get('per_page', 20);
        $payrolls = $query->latest('month')->latest('year')->paginate($perPage)->withQueryString();

        $departments = Department::where('is_active', true)->get();

        // Calculate totals
        $totals = [
            'gross' => $payrolls->sum('gross_salary'),
            'deductions' => $payrolls->sum('total_deductions'),
            'net' => $payrolls->sum('net_salary'),
        ];

        $stats = [
            'draft' => PayrollRecord::where('status', 'draft')->count(),
            'pending' => PayrollRecord::where('status', 'pending')->count(),
            'approved' => PayrollRecord::where('status', 'approved')->count(),
            'paid' => PayrollRecord::where('status', 'paid')->count(),
        ];

        return view('admin.hrm.payroll.index', compact('payrolls', 'departments', 'month', 'year', 'totals', 'stats'));
    }

    public function show(PayrollRecord $payroll)
    {
        $payroll->load(['employee.department', 'employee.position', 'generator', 'approver', 'payer']);

        return view('admin.hrm.payroll.show', compact('payroll'));
    }

    public function generate(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        try {
            $result = $this->payrollService->bulkGeneratePayrolls(
                $request->month,
                $request->year,
                $request->department_id,
                auth()->id()
            );

            $message = count($result['generated']) . ' payroll records generated';
            if (count($result['errors']) > 0) {
                $message .= ', ' . count($result['errors']) . ' errors occurred';
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate payrolls: ' . $e->getMessage());
        }
    }

    public function approve(PayrollRecord $payroll)
    {
        if ($payroll->status !== 'pending' && $payroll->status !== 'draft') {
            return back()->with('error', 'Payroll cannot be approved in current status');
        }

        $this->payrollService->approvePayroll($payroll->id, auth()->id());

        return back()->with('success', 'Payroll approved successfully');
    }

    public function markAsPaid(Request $request, PayrollRecord $payroll)
    {
        $request->validate([
            'payment_reference' => 'nullable|string',
        ]);

        if ($payroll->status !== 'approved') {
            return back()->with('error', 'Only approved payrolls can be marked as paid');
        }

        $this->payrollService->markAsPaid($payroll->id, auth()->id(), $request->payment_reference);

        return back()->with('success', 'Payroll marked as paid');
    }

    public function downloadPayslip(PayrollRecord $payroll)
    {
        // TODO: Generate PDF payslip

        return back()->with('info', 'Payslip download coming soon');
    }

    public function bulkApprove(Request $request)
    {
        $request->validate([
            'payroll_ids' => 'required|array',
            'payroll_ids.*' => 'exists:payroll_records,id',
        ]);

        $count = 0;
        foreach ($request->payroll_ids as $payrollId) {
            try {
                $this->payrollService->approvePayroll($payrollId, auth()->id());
                $count++;
            } catch (\Exception $e) {
                // Continue with next
            }
        }

        return back()->with('success', $count . ' payroll records approved');
    }

    public function export(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $payrolls = PayrollRecord::with(['employee.department', 'employee.position'])
                                ->where('month', $month)
                                ->where('year', $year)
                                ->get();

        $filename = 'payroll_' . $year . '_' . str_pad($month, 2, '0', STR_PAD_LEFT) . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($payrolls) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Employee ID', 'Name', 'Department', 'Position',
                'Basic Salary', 'Allowances', 'Bonuses', 'Overtime', 'Commission',
                'Gross Salary', 'Tax', 'Social Security', 'Provident Fund',
                'Other Deductions', 'Total Deductions', 'Net Salary', 'Status'
            ]);

            foreach ($payrolls as $payroll) {
                fputcsv($file, [
                    $payroll->employee->employee_id,
                    $payroll->employee->full_name,
                    $payroll->employee->department->name ?? '',
                    $payroll->employee->position->title ?? '',
                    $payroll->basic_salary,
                    $payroll->allowances,
                    $payroll->bonuses,
                    $payroll->overtime_pay,
                    $payroll->commission,
                    $payroll->gross_salary,
                    $payroll->tax_deduction,
                    $payroll->social_security,
                    $payroll->provident_fund,
                    $payroll->other_deductions,
                    $payroll->total_deductions,
                    $payroll->net_salary,
                    $payroll->status,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
