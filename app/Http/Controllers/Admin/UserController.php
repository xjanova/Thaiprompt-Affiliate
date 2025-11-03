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
        $query = User::with(['affiliate', 'roleModel']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                  ->orWhere('email', 'like', '%'.$search.'%');
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
        $user->load(['affiliate', 'commissions']);
        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user
     */
    public function edit(User $user)
    {
        $roles = Role::all();
        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        if ($request->filled('password')) {
            $request->validate([
                'password' => ['confirmed', Password::defaults()],
            ]);
            $validated['password'] = Hash::make($request->password);
        }

        // Get role to set the old role field for backward compatibility
        $role = Role::find($validated['role_id']);
        $validated['role'] = $role->name;

        $user->update($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'อัพเดทผู้ใช้สำเร็จ');
    }

    /**
     * Remove the specified user
     */
    public function destroy(User $user)
    {
        if ($user->is_super_admin) {
            return back()->with('error', 'Cannot delete super admin user.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Show permissions page for user
     */
    public function permissions(User $user)
    {
        $availablePermissions = User::availablePermissions();
        return view('admin.users.permissions', compact('user', 'availablePermissions'));
    }

    /**
     * Update user permissions
     */
    public function updatePermissions(Request $request, User $user)
    {
        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'in:' . implode(',', User::availablePermissions())],
        ]);

        $user->permissions = $validated['permissions'] ?? [];
        $user->save();

        return redirect()->route('admin.users.index')
            ->with('success', 'User permissions updated successfully.');
    }

    /**
     * View user's dashboard (impersonate user view)
     */
    public function viewDashboard(User $user)
    {
        // Load user relationships for dashboard
        $user->load(['affiliate', 'commissions']);

        // Get user statistics
        $stats = [
            'total_commissions' => $user->commissions()->count(),
            'pending_commissions' => $user->commissions()->where('status', 'pending')->count(),
            'approved_commissions' => $user->commissions()->where('status', 'approved')->count(),
            'paid_commissions' => $user->commissions()->where('status', 'paid')->count(),
            'total_earnings' => $user->commissions()->where('status', 'paid')->sum('amount'),
            'pending_earnings' => $user->commissions()->whereIn('status', ['pending', 'approved'])->sum('amount'),
        ];

        // Get recent commissions
        $recentCommissions = $user->commissions()
            ->with('affiliate')
            ->latest()
            ->limit(10)
            ->get();

        // Get commission chart data (last 6 months)
        $chartData = $user->commissions()
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count, SUM(amount) as total')
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return view('admin.users.dashboard', compact('user', 'stats', 'recentCommissions', 'chartData'));
    }
}
