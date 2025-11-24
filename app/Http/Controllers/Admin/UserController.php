<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
            $query->where(function($q) use ($search) {
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
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
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
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
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
        if (!isset($validated['menu_theme_preference'])) {
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
     */
    public function destroy(User $user)
    {
        // ✅ ตรวจสอบสิทธิ์ก่อนลบ
        $this->authorize('delete', $user);

        // Policy จะ block การลบตัวเองและ super admin อยู่แล้ว
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'ลบผู้ใช้สำเร็จ');
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
            'permissions.*' => ['string', 'in:' . implode(',', User::availablePermissions())],
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

        return back()->with('success', 'สร้างเลขสมาชิกสำเร็จ: ' . $user->member_number);
    }
}
