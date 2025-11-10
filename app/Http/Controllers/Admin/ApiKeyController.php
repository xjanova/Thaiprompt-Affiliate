<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\ApiEndpoint;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ApiKeyController extends Controller
{
    /**
     * แสดงรายการ API keys
     */
    public function index(Request $request)
    {
        $query = ApiKey::with('user');

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } else if ($request->status === 'inactive') {
                $query->where('is_active', false);
            } else if ($request->status === 'expired') {
                $query->where('expires_at', '<', now());
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $apiKeys = $query->latest()->paginate(20);

        return view('admin.api-keys.index', compact('apiKeys'));
    }

    /**
     * แสดงฟอร์มสร้าง API key ใหม่
     */
    public function create()
    {
        $users = User::orderBy('name')->get();
        $endpoints = ApiEndpoint::active()->orderBy('category')->orderBy('name')->get();
        $scopes = ['read', 'write', 'delete'];

        return view('admin.api-keys.create', compact('users', 'endpoints', 'scopes'));
    }

    /**
     * บันทึก API key ใหม่
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'user_id' => 'nullable|exists:users,id',
            'description' => 'nullable|string',
            'allowed_endpoints' => 'nullable|array',
            'allowed_ips' => 'nullable|string',
            'scopes' => 'nullable|array',
            'rate_limit_per_minute' => 'nullable|integer|min:1',
            'rate_limit_per_hour' => 'nullable|integer|min:1',
            'rate_limit_per_day' => 'nullable|integer|min:1',
            'monthly_quota' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // สร้าง API key
        $prefix = 'sk_' . (app()->environment('production') ? 'live' : 'test') . '_';
        $randomKey = Str::random(48);
        $fullKey = $prefix . $randomKey;

        // แปลง allowed_ips จาก string เป็น array
        $allowedIps = null;
        if ($request->filled('allowed_ips')) {
            $allowedIps = array_map('trim', explode("\n", $request->allowed_ips));
        }

        $apiKey = ApiKey::create([
            'name' => $request->name,
            'key' => hash('sha256', $fullKey),
            'prefix' => $prefix . substr($randomKey, 0, 8) . '...',
            'user_id' => $request->user_id,
            'description' => $request->description,
            'allowed_endpoints' => $request->allowed_endpoints,
            'allowed_ips' => $allowedIps,
            'scopes' => $request->scopes,
            'rate_limit_per_minute' => $request->rate_limit_per_minute,
            'rate_limit_per_hour' => $request->rate_limit_per_hour,
            'rate_limit_per_day' => $request->rate_limit_per_day,
            'monthly_quota' => $request->monthly_quota,
            'expires_at' => $request->expires_at,
        ]);

        return redirect()->route('admin.api-keys.show', $apiKey)
            ->with('success', 'สร้าง API key สำเร็จ')
            ->with('api_key', $fullKey); // แสดง key เต็มเพียงครั้งเดียว
    }

    /**
     * แสดงรายละเอียด API key
     */
    public function show(ApiKey $apiKey)
    {
        // สถิติการใช้งาน
        $stats = [
            'total_requests' => $apiKey->usageLogs()->count(),
            'successful_requests' => $apiKey->usageLogs()->successful()->count(),
            'failed_requests' => $apiKey->usageLogs()->failed()->count(),
            'today_requests' => $apiKey->usageLogs()->today()->count(),
            'this_month_requests' => $apiKey->usageLogs()->thisMonth()->count(),
            'avg_response_time' => $apiKey->usageLogs()->avg('response_time_ms'),
            'quota_remaining' => $apiKey->monthly_quota ? ($apiKey->monthly_quota - $apiKey->monthly_usage) : null,
            'quota_percentage' => $apiKey->monthly_quota ? round(($apiKey->monthly_usage / $apiKey->monthly_quota) * 100, 2) : 0,
        ];

        // Recent logs
        $recentLogs = $apiKey->usageLogs()
            ->with('apiEndpoint')
            ->latest()
            ->limit(50)
            ->get();

        // Top endpoints
        $topEndpoints = $apiKey->usageLogs()
            ->selectRaw('api_endpoint_id, COUNT(*) as count')
            ->groupBy('api_endpoint_id')
            ->orderByDesc('count')
            ->limit(10)
            ->with('apiEndpoint')
            ->get();

        return view('admin.api-keys.show', compact('apiKey', 'stats', 'recentLogs', 'topEndpoints'));
    }

    /**
     * แสดงฟอร์มแก้ไข
     */
    public function edit(ApiKey $apiKey)
    {
        $users = User::orderBy('name')->get();
        $endpoints = ApiEndpoint::active()->orderBy('category')->orderBy('name')->get();
        $scopes = ['read', 'write', 'delete'];

        // แปลง allowed_ips จาก array เป็น string
        $allowedIpsString = $apiKey->allowed_ips ? implode("\n", $apiKey->allowed_ips) : '';

        return view('admin.api-keys.edit', compact('apiKey', 'users', 'endpoints', 'scopes', 'allowedIpsString'));
    }

    /**
     * อัปเดต API key
     */
    public function update(Request $request, ApiKey $apiKey)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'user_id' => 'nullable|exists:users,id',
            'description' => 'nullable|string',
            'allowed_endpoints' => 'nullable|array',
            'allowed_ips' => 'nullable|string',
            'scopes' => 'nullable|array',
            'rate_limit_per_minute' => 'nullable|integer|min:1',
            'rate_limit_per_hour' => 'nullable|integer|min:1',
            'rate_limit_per_day' => 'nullable|integer|min:1',
            'monthly_quota' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // แปลง allowed_ips จาก string เป็น array
        $allowedIps = null;
        if ($request->filled('allowed_ips')) {
            $allowedIps = array_map('trim', explode("\n", $request->allowed_ips));
        }

        $apiKey->update([
            'name' => $request->name,
            'user_id' => $request->user_id,
            'description' => $request->description,
            'allowed_endpoints' => $request->allowed_endpoints,
            'allowed_ips' => $allowedIps,
            'scopes' => $request->scopes,
            'rate_limit_per_minute' => $request->rate_limit_per_minute,
            'rate_limit_per_hour' => $request->rate_limit_per_hour,
            'rate_limit_per_day' => $request->rate_limit_per_day,
            'monthly_quota' => $request->monthly_quota,
            'expires_at' => $request->expires_at,
        ]);

        return redirect()->route('admin.api-keys.index')
            ->with('success', 'อัปเดต API key สำเร็จ');
    }

    /**
     * ลบ API key
     */
    public function destroy(ApiKey $apiKey)
    {
        $apiKey->delete();

        return redirect()->route('admin.api-keys.index')
            ->with('success', 'ลบ API key สำเร็จ');
    }

    /**
     * Toggle active status
     */
    public function toggleStatus(ApiKey $apiKey)
    {
        $apiKey->update([
            'is_active' => !$apiKey->is_active,
        ]);

        return response()->json([
            'success' => true,
            'is_active' => $apiKey->is_active,
            'message' => $apiKey->is_active ? 'เปิดใช้งาน API key' : 'ปิดใช้งาน API key',
        ]);
    }

    /**
     * Reset monthly usage
     */
    public function resetUsage(ApiKey $apiKey)
    {
        $apiKey->update(['monthly_usage' => 0]);

        return response()->json([
            'success' => true,
            'message' => 'รีเซ็ตการใช้งานรายเดือนสำเร็จ',
        ]);
    }

    /**
     * Get analytics
     */
    public function analytics(ApiKey $apiKey, Request $request)
    {
        $days = $request->get('days', 7);
        $startDate = now()->subDays($days)->startOfDay();

        // Requests per day
        $requestsPerDay = $apiKey->usageLogs()
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Response codes distribution
        $responseCodes = $apiKey->usageLogs()
            ->where('created_at', '>=', $startDate)
            ->selectRaw('response_code, COUNT(*) as count')
            ->groupBy('response_code')
            ->get();

        // Top endpoints
        $topEndpoints = $apiKey->usageLogs()
            ->where('created_at', '>=', $startDate)
            ->selectRaw('api_endpoint_id, COUNT(*) as count')
            ->groupBy('api_endpoint_id')
            ->orderByDesc('count')
            ->limit(10)
            ->with('apiEndpoint')
            ->get();

        return response()->json([
            'requests_per_day' => $requestsPerDay,
            'response_codes' => $responseCodes,
            'top_endpoints' => $topEndpoints,
        ]);
    }
}
