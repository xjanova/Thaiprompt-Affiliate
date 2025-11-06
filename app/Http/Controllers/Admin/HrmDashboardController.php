<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\HrmService;
use Illuminate\Http\Request;

class HrmDashboardController extends Controller
{
    protected $hrmService;

    public function __construct(HrmService $hrmService)
    {
        $this->hrmService = $hrmService;
    }

    /**
     * Display HRM dashboard
     */
    public function index(Request $request)
    {
        $departmentId = $request->get('department_id');

        $stats = $this->hrmService->getDashboardStats($departmentId);
        $employeesByDepartment = $this->hrmService->getEmployeesByDepartment();
        $employeesByType = $this->hrmService->getEmployeesByType();
        $headcountTrend = $this->hrmService->getHeadcountTrend();
        $upcomingBirthdays = $this->hrmService->getUpcomingBirthdays(30);
        $upcomingAnniversaries = $this->hrmService->getUpcomingAnniversaries(30);
        $expiringDocuments = $this->hrmService->getExpiringDocuments(30);

        return view('admin.hrm.dashboard', compact(
            'stats',
            'employeesByDepartment',
            'employeesByType',
            'headcountTrend',
            'upcomingBirthdays',
            'upcomingAnniversaries',
            'expiringDocuments'
        ));
    }
}
