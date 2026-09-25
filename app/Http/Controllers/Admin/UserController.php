<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\AccountDeletionBlockedException;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Rules\NotReservedEmailDomain;
use App\Services\AccountDeletionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        $query = User::with(['mlmMembers', 'roleModel']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('member_number', 'like', '%'.$search.'%');
            });
        }

        // Role filter
        if ($request->filled('role')) {
            $query->where('role_id', $request->get('role'));
        }

        // Pagination
        $perPage = $request->get('per_page', 10);
        $users = $query->latest()->paginate($perPage)->withQueryString();

        // Get all roles for filter dropdown
        $roles = Role::all();

        return view('admin.users.index', compact('users', 'roles'));
    }

    /**
     * Show the form for creating a new user
     */
    public function create()
    {
        $roles = Role::all();

        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users', new NotReservedEmailDomain],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        // Get role to set the old role field for backward compatibility
        $role = Role::find($validated['role_id']);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => $validated['role_id'],
            'role' => $role->name, // Backward compatibility
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'สร้างผู้ใช้สำเร็จ');
    }

    /**
     * Display the specified user
     */
    public function show(User $user)
    {
        $user->load(['mlmMembers', 'mlmCommissions']);

        return view('admin.users.show', compact('user'));
    }

    /**
     * แสดงฟอร์มแก้ไข user
     *
     * ⚠️ SECURITY: ตรวจสอบสิทธิ์ด้วย Policy
     */
    public function edit(User $user)
    {
        // ✅ ตรวจสอบสิทธิ์ก่อนแก้ไข
        $this->authorize('update', $user);

        $roles = Role::all();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * อัพเดท user
     *
     * ⚠️ SECURITY: ป้องกัน unauthorized update
     */
    public function update(Request $request, User $user)
    {
        // ✅ ตรวจสอบสิทธิ์ก่อนอัพเดท
        $this->authorize('update', $user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id, new NotReservedEmailDomain],
            'role_id' => ['required', 'exists:roles,id'],
            'menu_theme_preference' => ['nullable', 'string', 'in:millennium,classic_x'],
        ]);

        if ($request->filled('password')) {
            $request->validate([
                'password' => ['confirmed', Password::defaults()],
            ]);
            $validated['password'] = Hash::make($request->password);
        }

        // ⚠️ CRITICAL: การเปลี่ยน role ต้องมีสิทธิ์พิเศษ
        if ($validated['role_id'] != $user->role_id) {
            $this->authorize('changeRole', $user);
        }

        // Get role to set the old role field for backward compatibility
        $role = Role::find($validated['role_id']);
        $validated['role'] = $role->name;

        // Set default theme if not provided
        if (! isset($validated['menu_theme_preference'])) {
            $validated['menu_theme_preference'] = $user->menu_theme_preference ?? 'millennium';
        }

        $user->update($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'อัพเดทผู้ใช้สำเร็จ');
    }

    /**
     * ลบ user
     *
     * ⚠️ SECURITY: ป้องกัน unauthorized delete
     *
     * 🗑️ (2026-09-25) CC-01: เดิม $user->delete() = ลบถาวร → FK CASCADE ลากออเดอร์/order_items ของ
     *    ผู้ซื้อทุกคนที่เคยซื้อจากร้านนั้น, wallet, ledger, งานไรเดอร์ หายตามไปหมด
     *    ตอนนี้ผ่าน AccountDeletionService: ปกปิด PII + soft delete และบล็อกถ้ายังมีเงิน/ออเดอร์ค้าง
     *    (ถ้าแค่ต้องการหยุดการใช้งาน ให้ใช้ "ระงับบัญชี" แทน — ย้อนกลับได้)
     */
    public function destroy(User $user)
    {
        // ✅ ตรวจสอบสิทธิ์ก่อนลบ
        $this->authorize('delete', $user);

        // Policy::before ปล่อย super admin ผ่านทุกอย่าง → กันลบตัวเอง/ลบ super admin ซ้ำที่นี่
        if (auth()->id() === $user->id) {
            return back()->with('error', 'ไม่สามารถลบบัญชีของตัวเองได้');
        }
        if ($user->is_super_admin) {
            return back()->with('error', 'ไม่สามารถลบผู้ดูแลระบบสูงสุดได้');
        }

        try {
            $ref = app(AccountDeletionService::class)->delete($user, 'admin', auth()->user());
        } catch (AccountDeletionBlockedException $e) {
            $reasons = collect($e->blockers())->pluck('message')->implode(' · ');

            return back()->with('error', 'ยังลบบัญชีนี้ไม่ได้: '.$reasons.' (หากต้องการหยุดการใช้งานทันที ให้ใช้ "ระงับบัญชี")');
        } catch (\Throwable $e) {
            Log::error('Admin delete user failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'ลบผู้ใช้ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'ลบบัญชีผู้ใช้และปกปิดข้อมูลส่วนบุคคลเรียบร้อย (อ้างอิง '.$ref.')');
    }

    /**
     * ระงับบัญชีผู้ใช้ (ย้อนกลับได้) — เพิกถอน token แอปทั้งหมด + เว็บจะถูกออกจากระบบใน request ถัดไป
     *
     * POST admin/users/{user}/suspend  body: reason (ไม่บังคับ, สูงสุด 500 ตัวอักษร)
     */
    public function suspend(Request $request, User $user)
    {
        $this->authorize('block', $user);

        if (auth()->id() === $user->id) {
            return back()->with('error', 'ไม่สามารถระงับบัญชีของตัวเองได้');
        }
        if ($user->is_super_admin) {
            return back()->with('error', 'ไม่สามารถระงับผู้ดูแลระบบสูงสุดได้');
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ], [
            'reason.max' => 'เหตุผลต้องไม่เกิน 500 ตัวอักษร',
        ]);

        if ($user->isSuspended()) {
            return back()->with('info', 'บัญชีนี้ถูกระงับอยู่แล้ว');
        }

        $user->suspend(auth()->user(), $validated['reason'] ?? null);

        Log::info('Admin suspended user', [
            'user_id' => $user->id,
            'admin_id' => auth()->id(),
        ]);

        return back()->with('success', 'ระงับบัญชี '.$user->name.' เรียบร้อย');
    }

    /**
     * ยกเลิกการระงับบัญชี
     *
     * POST admin/users/{user}/unsuspend
     */
    public function unsuspend(User $user)
    {
        $this->authorize('block', $user);

        if (! $user->isSuspended()) {
            return back()->with('info', 'บัญชีนี้ไม่ได้ถูกระงับ');
        }

        $user->unsuspend();

        Log::info('Admin unsuspended user', [
            'user_id' => $user->id,
            'admin_id' => auth()->id(),
        ]);

        return back()->with('success', 'ยกเลิกการระงับบัญชี '.$user->name.' เรียบร้อย');
    }

    /**
     * แสดงหน้าจัดการ permissions
     *
     * ⚠️ SECURITY: เฉพาะ Super Admin เท่านั้น
     */
    public function permissions(User $user)
    {
        // ✅ ตรวจสอบสิทธิ์การเปลี่ยน permissions
        $this->authorize('changePermissions', $user);

        $availablePermissions = User::availablePermissions();

        return view('admin.users.permissions', compact('user', 'availablePermissions'));
    }

    /**
     * อัพเดท permissions ของ user
     *
     * ⚠️ SECURITY: เฉพาะ Super Admin เท่านั้น
     */
    public function updatePermissions(Request $request, User $user)
    {
        // ✅ ตรวจสอบสิทธิ์การเปลี่ยน permissions
        $this->authorize('changePermissions', $user);

        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'in:'.implode(',', User::availablePermissions())],
        ]);

        $user->permissions = $validated['permissions'] ?? [];
        $user->save();

        return redirect()->route('admin.users.index')
            ->with('success', 'อัพเดท permissions สำเร็จ');
    }

    /**
     * View user's dashboard (impersonate user view)
     */
    public function viewDashboard(User $user)
    {
        // Load user relationships for dashboard
        $user->load(['mlmMembers', 'mlmCommissions']);

        // Get user statistics (MLM Commissions)
        $stats = [
            'total_commissions' => $user->mlmCommissions()->count(),
            'pending_commissions' => $user->mlmCommissions()->where('status', 'pending')->count(),
            'approved_commissions' => $user->mlmCommissions()->where('status', 'approved')->count(),
            'paid_commissions' => $user->mlmCommissions()->where('status', 'paid')->count(),
            'total_earnings' => $user->mlmCommissions()->where('status', 'paid')->sum('commission_amount'),
            'pending_earnings' => $user->mlmCommissions()->whereIn('status', ['pending', 'approved'])->sum('commission_amount'),
        ];

        // Get recent commissions
        $recentCommissions = $user->mlmCommissions()
            ->with('mlmMember')
            ->latest()
            ->limit(10)
            ->get();

        // Get commission chart data (last 6 months)
        $chartData = $user->mlmCommissions()
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count, SUM(commission_amount) as total')
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return view('admin.users.dashboard', compact('user', 'stats', 'recentCommissions', 'chartData'));
    }

    /**
     * Generate member number for a user
     */
    public function generateMemberNumber(User $user)
    {
        // Check if user already has a member number
        if ($user->member_number) {
            return back()->with('error', 'ผู้ใช้นี้มีเลขสมาชิกแล้ว');
        }

        // Generate new member number
        $user->member_number = User::generateMemberNumber();
        $user->save();

        return back()->with('success', 'สร้างเลขสมาชิกสำเร็จ: '.$user->member_number);
    }
}
