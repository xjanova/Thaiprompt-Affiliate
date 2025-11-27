<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\PosStaffAssignment;
use App\Models\VendorStore;
use App\Models\WorkShift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * StaffController
 *
 * จัดการพนักงานสำหรับเจ้าของร้าน (Seller)
 * ระบบ ERP ฟรีทุกร้าน แยกข้อมูลตาม store_id
 *
 * @author AI Assistant
 * @since 2024-11-27
 */
class StaffController extends Controller
{
    /**
     * ดึงร้านค้าของ user ปัจจุบัน
     */
    protected function getStore()
    {
        return VendorStore::where('user_id', auth()->id())->first();
    }

    /**
     * หน้าแรก - รายการพนักงาน
     */
    public function index(Request $request)
    {
        $store = $this->getStore();

        if (!$store) {
            return redirect()->route('seller.store.settings')
                ->with('error', 'กรุณาสร้างร้านค้าก่อน');
        }

        $query = Employee::byStore($store->id)
            ->with(['department', 'position', 'posAssignment']);

        // ค้นหา
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('first_name_th', 'like', "%{$search}%")
                    ->orWhere('last_name_th', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%")
                    ->orWhere('mobile_phone', 'like', "%{$search}%");
            });
        }

        // กรองตามแผนก
        if ($request->filled('department')) {
            $query->where('department_id', $request->department);
        }

        // กรองตามสถานะ
        if ($request->filled('status')) {
            $query->where('employment_status', $request->status);
        }

        $employees = $query->latest()->paginate(15);
        $departments = Department::byStore($store->id)->where('is_active', true)->get();

        // สถิติ
        $stats = [
            'total' => Employee::byStore($store->id)->count(),
            'active' => Employee::byStore($store->id)->active()->count(),
            'probation' => Employee::byStore($store->id)->where('employment_status', 'probation')->count(),
        ];

        return view('seller.staff.index', compact('employees', 'departments', 'stats', 'store'));
    }

    /**
     * ฟอร์มเพิ่มพนักงานใหม่
     */
    public function create()
    {
        $store = $this->getStore();

        if (!$store) {
            return redirect()->route('seller.store.settings')
                ->with('error', 'กรุณาสร้างร้านค้าก่อน');
        }

        $departments = Department::byStore($store->id)->where('is_active', true)->get();
        $positions = Position::byStore($store->id)->where('is_active', true)->get();
        $workShifts = WorkShift::forStore($store->id)->active()->ordered()->get();

        return view('seller.staff.create', compact('departments', 'positions', 'workShifts', 'store'));
    }

    /**
     * บันทึกพนักงานใหม่
     */
    public function store(Request $request)
    {
        $store = $this->getStore();

        if (!$store) {
            return back()->with('error', 'ไม่พบร้านค้า');
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'first_name_th' => 'nullable|string|max:255',
            'last_name_th' => 'nullable|string|max:255',
            'nickname' => 'nullable|string|max:100',
            'mobile_phone' => 'nullable|string|max:20',
            'personal_email' => 'nullable|email|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'hire_date' => 'required|date',
            'employment_type' => 'required|in:full_time,part_time,contract,intern,freelance',
            'basic_salary' => 'nullable|numeric|min:0',
            'work_shift_id' => 'nullable|exists:work_shifts,id',
            'pin_code' => 'nullable|string|min:4|max:6',
            'pos_permissions' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            // สร้างรหัสพนักงานอัตโนมัติ
            $employeeId = $this->generateEmployeeId($store);

            // ตรวจสอบว่าต้องสร้าง department/position ก่อนหรือไม่
            if (empty($validated['department_id'])) {
                $department = $this->getOrCreateDefaultDepartment($store);
                $validated['department_id'] = $department->id;
            }

            if (empty($validated['position_id'])) {
                $position = $this->getOrCreateDefaultPosition($store, $validated['department_id']);
                $validated['position_id'] = $position->id;
            }

            // สร้างพนักงาน
            $employee = Employee::create([
                'store_id' => $store->id,
                'employee_id' => $employeeId,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'first_name_th' => $validated['first_name_th'],
                'last_name_th' => $validated['last_name_th'],
                'nickname' => $validated['nickname'],
                'mobile_phone' => $validated['mobile_phone'],
                'personal_email' => $validated['personal_email'],
                'department_id' => $validated['department_id'],
                'position_id' => $validated['position_id'],
                'hire_date' => $validated['hire_date'],
                'employment_type' => $validated['employment_type'],
                'employment_status' => 'active',
                'basic_salary' => $validated['basic_salary'] ?? 0,
            ]);

            // สร้าง POS Staff Assignment (ถ้ามี PIN)
            if (!empty($validated['pin_code'])) {
                PosStaffAssignment::create([
                    'store_id' => $store->id,
                    'employee_id' => $employee->id,
                    'staff_code' => $employeeId,
                    'pin_code' => Hash::make($validated['pin_code']),
                    'work_shift_id' => $validated['work_shift_id'] ?? null,
                    'pos_permissions' => $validated['pos_permissions'] ?? ['sales'],
                    'is_active' => true,
                ]);
            }

            DB::commit();

            return redirect()->route('seller.staff.index')
                ->with('success', "เพิ่มพนักงาน {$employee->full_name} สำเร็จ");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * แสดงข้อมูลพนักงาน
     */
    public function show(Employee $employee)
    {
        $store = $this->getStore();

        if ($employee->store_id !== $store->id) {
            abort(403, 'ไม่มีสิทธิ์ดูข้อมูลพนักงานนี้');
        }

        $employee->load(['department', 'position', 'posAssignment', 'attendanceRecords' => function ($q) {
            $q->orderBy('date', 'desc')->limit(10);
        }]);

        return view('seller.staff.show', compact('employee', 'store'));
    }

    /**
     * ฟอร์มแก้ไขพนักงาน
     */
    public function edit(Employee $employee)
    {
        $store = $this->getStore();

        if ($employee->store_id !== $store->id) {
            abort(403, 'ไม่มีสิทธิ์แก้ไขพนักงานนี้');
        }

        $employee->load('posAssignment');
        $departments = Department::byStore($store->id)->where('is_active', true)->get();
        $positions = Position::byStore($store->id)->where('is_active', true)->get();
        $workShifts = WorkShift::forStore($store->id)->active()->ordered()->get();

        return view('seller.staff.edit', compact('employee', 'departments', 'positions', 'workShifts', 'store'));
    }

    /**
     * อัปเดตพนักงาน
     */
    public function update(Request $request, Employee $employee)
    {
        $store = $this->getStore();

        if ($employee->store_id !== $store->id) {
            abort(403, 'ไม่มีสิทธิ์แก้ไขพนักงานนี้');
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'first_name_th' => 'nullable|string|max:255',
            'last_name_th' => 'nullable|string|max:255',
            'nickname' => 'nullable|string|max:100',
            'mobile_phone' => 'nullable|string|max:20',
            'personal_email' => 'nullable|email|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'employment_type' => 'required|in:full_time,part_time,contract,intern,freelance',
            'employment_status' => 'required|in:active,probation,notice_period,resigned,terminated',
            'basic_salary' => 'nullable|numeric|min:0',
            'work_shift_id' => 'nullable|exists:work_shifts,id',
            'pin_code' => 'nullable|string|min:4|max:6',
            'pos_permissions' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            // อัปเดตพนักงาน
            $employee->update([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'first_name_th' => $validated['first_name_th'],
                'last_name_th' => $validated['last_name_th'],
                'nickname' => $validated['nickname'],
                'mobile_phone' => $validated['mobile_phone'],
                'personal_email' => $validated['personal_email'],
                'department_id' => $validated['department_id'],
                'position_id' => $validated['position_id'],
                'employment_type' => $validated['employment_type'],
                'employment_status' => $validated['employment_status'],
                'basic_salary' => $validated['basic_salary'] ?? 0,
            ]);

            // อัปเดต/สร้าง POS Staff Assignment
            $posData = [
                'store_id' => $store->id,
                'employee_id' => $employee->id,
                'staff_code' => $employee->employee_id,
                'work_shift_id' => $validated['work_shift_id'] ?? null,
                'pos_permissions' => $validated['pos_permissions'] ?? ['sales'],
                'is_active' => $validated['employment_status'] === 'active',
            ];

            if (!empty($validated['pin_code'])) {
                $posData['pin_code'] = Hash::make($validated['pin_code']);
            }

            PosStaffAssignment::updateOrCreate(
                ['employee_id' => $employee->id],
                $posData
            );

            DB::commit();

            return redirect()->route('seller.staff.index')
                ->with('success', "อัปเดตข้อมูล {$employee->full_name} สำเร็จ");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * ลบพนักงาน
     */
    public function destroy(Employee $employee)
    {
        $store = $this->getStore();

        if ($employee->store_id !== $store->id) {
            abort(403, 'ไม่มีสิทธิ์ลบพนักงานนี้');
        }

        $name = $employee->full_name;

        // Soft delete
        $employee->delete();

        return redirect()->route('seller.staff.index')
            ->with('success', "ลบพนักงาน {$name} สำเร็จ");
    }

    // ========================================
    // DEPARTMENTS MANAGEMENT
    // ========================================

    /**
     * หน้าจัดการแผนก
     */
    public function departments()
    {
        $store = $this->getStore();

        if (!$store) {
            return redirect()->route('seller.store.settings')
                ->with('error', 'กรุณาสร้างร้านค้าก่อน');
        }

        $departments = Department::byStore($store->id)
            ->withCount(['employees' => function ($q) {
                $q->where('employment_status', 'active');
            }])
            ->get();

        return view('seller.staff.departments', compact('departments', 'store'));
    }

    /**
     * บันทึกแผนกใหม่
     */
    public function storeDepartment(Request $request)
    {
        $store = $this->getStore();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        Department::create([
            'store_id' => $store->id,
            'name' => $validated['name'],
            'code' => $validated['code'] ?? Str::upper(Str::slug($validated['name'], '')),
            'description' => $validated['description'],
            'is_active' => true,
        ]);

        return back()->with('success', "สร้างแผนก {$validated['name']} สำเร็จ");
    }

    /**
     * ลบแผนก
     */
    public function destroyDepartment(Department $department)
    {
        $store = $this->getStore();

        if ($department->store_id !== $store->id) {
            abort(403);
        }

        // ตรวจสอบว่ามีพนักงานอยู่หรือไม่
        if ($department->employees()->exists()) {
            return back()->with('error', 'ไม่สามารถลบแผนกที่มีพนักงานอยู่ได้');
        }

        $department->delete();

        return back()->with('success', 'ลบแผนกสำเร็จ');
    }

    // ========================================
    // POSITIONS MANAGEMENT
    // ========================================

    /**
     * หน้าจัดการตำแหน่ง
     */
    public function positions()
    {
        $store = $this->getStore();

        if (!$store) {
            return redirect()->route('seller.store.settings')
                ->with('error', 'กรุณาสร้างร้านค้าก่อน');
        }

        $positions = Position::byStore($store->id)
            ->with('department')
            ->withCount(['employees' => function ($q) {
                $q->where('employment_status', 'active');
            }])
            ->get();

        $departments = Department::byStore($store->id)->where('is_active', true)->get();

        return view('seller.staff.positions', compact('positions', 'departments', 'store'));
    }

    /**
     * บันทึกตำแหน่งใหม่
     */
    public function storePosition(Request $request)
    {
        $store = $this->getStore();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'department_id' => 'nullable|exists:departments,id',
            'min_salary' => 'nullable|numeric|min:0',
            'max_salary' => 'nullable|numeric|min:0',
        ]);

        Position::create([
            'store_id' => $store->id,
            'title' => $validated['title'],
            'code' => $validated['code'] ?? Str::upper(Str::slug($validated['title'], '')),
            'department_id' => $validated['department_id'],
            'min_salary' => $validated['min_salary'] ?? 0,
            'max_salary' => $validated['max_salary'] ?? 0,
            'is_active' => true,
        ]);

        return back()->with('success', "สร้างตำแหน่ง {$validated['title']} สำเร็จ");
    }

    /**
     * ลบตำแหน่ง
     */
    public function destroyPosition(Position $position)
    {
        $store = $this->getStore();

        if ($position->store_id !== $store->id) {
            abort(403);
        }

        if ($position->employees()->exists()) {
            return back()->with('error', 'ไม่สามารถลบตำแหน่งที่มีพนักงานอยู่ได้');
        }

        $position->delete();

        return back()->with('success', 'ลบตำแหน่งสำเร็จ');
    }

    // ========================================
    // WORK SHIFTS MANAGEMENT
    // ========================================

    /**
     * หน้าจัดการกะการทำงาน
     */
    public function shifts()
    {
        $store = $this->getStore();

        if (!$store) {
            return redirect()->route('seller.store.settings')
                ->with('error', 'กรุณาสร้างร้านค้าก่อน');
        }

        $shifts = WorkShift::forStore($store->id)->ordered()->get();

        return view('seller.staff.shifts', compact('shifts', 'store'));
    }

    /**
     * บันทึกกะใหม่
     */
    public function storeShift(Request $request)
    {
        $store = $this->getStore();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'break_duration_minutes' => 'nullable|integer|min:0',
            'daily_allowance' => 'nullable|numeric|min:0',
            'color' => 'nullable|string|max:20',
        ]);

        // ตรวจสอบว่าเป็นกะข้ามวันหรือไม่
        $crossesMidnight = $validated['start_time'] > $validated['end_time'];

        WorkShift::create([
            'store_id' => $store->id,
            'name' => $validated['name'],
            'code' => $validated['code'] ?? Str::upper(Str::slug($validated['name'], '')),
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'crosses_midnight' => $crossesMidnight,
            'break_duration_minutes' => $validated['break_duration_minutes'] ?? 60,
            'daily_allowance' => $validated['daily_allowance'] ?? 0,
            'color' => $validated['color'] ?? '#3B82F6',
            'is_active' => true,
        ]);

        return back()->with('success', "สร้างกะ {$validated['name']} สำเร็จ");
    }

    /**
     * ลบกะ
     */
    public function destroyShift(WorkShift $shift)
    {
        $store = $this->getStore();

        if ($shift->store_id !== $store->id) {
            abort(403);
        }

        $shift->delete();

        return back()->with('success', 'ลบกะสำเร็จ');
    }

    // ========================================
    // HELPERS
    // ========================================

    /**
     * สร้างรหัสพนักงานอัตโนมัติ
     */
    protected function generateEmployeeId(VendorStore $store): string
    {
        $prefix = Str::upper(Str::substr($store->store_slug, 0, 3));
        $count = Employee::byStore($store->id)->withTrashed()->count() + 1;

        return $prefix . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * สร้างแผนกเริ่มต้น (ถ้ายังไม่มี)
     */
    protected function getOrCreateDefaultDepartment(VendorStore $store): Department
    {
        return Department::firstOrCreate(
            ['store_id' => $store->id, 'code' => 'GENERAL'],
            [
                'name' => 'ทั่วไป',
                'description' => 'แผนกทั่วไป',
                'is_active' => true,
            ]
        );
    }

    /**
     * สร้างตำแหน่งเริ่มต้น (ถ้ายังไม่มี)
     */
    protected function getOrCreateDefaultPosition(VendorStore $store, int $departmentId): Position
    {
        return Position::firstOrCreate(
            ['store_id' => $store->id, 'code' => 'STAFF'],
            [
                'title' => 'พนักงาน',
                'department_id' => $departmentId,
                'is_active' => true,
            ]
        );
    }
}
