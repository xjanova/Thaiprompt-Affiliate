<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiEndpoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiEndpointController extends Controller
{
    /**
     * แสดงรายการ API endpoints
     */
    public function index(Request $request)
    {
        $query = ApiEndpoint::query();

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Filter by method
        if ($request->filled('method')) {
            $query->where('method', $request->method);
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } else if ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('path', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $endpoints = $query->orderBy('category')
            ->orderBy('priority')
            ->orderBy('name')
            ->paginate(20);

        // Get categories for filter
        $categories = ApiEndpoint::select('category')
            ->distinct()
            ->whereNotNull('category')
            ->orderBy('category')
            ->pluck('category');

        return view('admin.api-endpoints.index', compact('endpoints', 'categories'));
    }

    /**
     * แสดงฟอร์มสร้าง endpoint ใหม่
     */
    public function create()
    {
        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
        $categories = $this->getCategories();

        return view('admin.api-endpoints.create', compact('methods', 'categories'));
    }

    /**
     * บันทึก endpoint ใหม่
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'path' => 'required|string|max:255',
            'method' => 'required|in:GET,POST,PUT,PATCH,DELETE',
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'parameters' => 'nullable|string',
            'response_example' => 'nullable|string',
            'is_active' => 'boolean',
            'requires_auth' => 'boolean',
            'rate_limit_per_minute' => 'nullable|integer|min:1',
            'rate_limit_per_hour' => 'nullable|integer|min:1',
            'rate_limit_per_day' => 'nullable|integer|min:1',
            'allowed_roles' => 'nullable|array',
            'priority' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $endpoint = ApiEndpoint::create($request->all());

        return redirect()->route('admin.api-endpoints.index')
            ->with('success', 'เพิ่ม API endpoint สำเร็จ');
    }

    /**
     * แสดงรายละเอียด endpoint
     */
    public function show(ApiEndpoint $apiEndpoint)
    {
        // สถิติการใช้งาน
        $stats = [
            'total_requests' => $apiEndpoint->usageLogs()->count(),
            'successful_requests' => $apiEndpoint->usageLogs()->successful()->count(),
            'failed_requests' => $apiEndpoint->usageLogs()->failed()->count(),
            'today_requests' => $apiEndpoint->usageLogs()->today()->count(),
            'this_month_requests' => $apiEndpoint->usageLogs()->thisMonth()->count(),
            'avg_response_time' => $apiEndpoint->usageLogs()->avg('response_time_ms'),
        ];

        // Recent logs
        $recentLogs = $apiEndpoint->usageLogs()
            ->with('apiKey')
            ->latest()
            ->limit(50)
            ->get();

        return view('admin.api-endpoints.show', compact('apiEndpoint', 'stats', 'recentLogs'));
    }

    /**
     * แสดงฟอร์มแก้ไข
     */
    public function edit(ApiEndpoint $apiEndpoint)
    {
        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
        $categories = $this->getCategories();

        return view('admin.api-endpoints.edit', compact('apiEndpoint', 'methods', 'categories'));
    }

    /**
     * อัปเดต endpoint
     */
    public function update(Request $request, ApiEndpoint $apiEndpoint)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'path' => 'required|string|max:255',
            'method' => 'required|in:GET,POST,PUT,PATCH,DELETE',
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'parameters' => 'nullable|string',
            'response_example' => 'nullable|string',
            'is_active' => 'boolean',
            'requires_auth' => 'boolean',
            'rate_limit_per_minute' => 'nullable|integer|min:1',
            'rate_limit_per_hour' => 'nullable|integer|min:1',
            'rate_limit_per_day' => 'nullable|integer|min:1',
            'allowed_roles' => 'nullable|array',
            'priority' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $apiEndpoint->update($request->all());

        return redirect()->route('admin.api-endpoints.index')
            ->with('success', 'อัปเดต API endpoint สำเร็จ');
    }

    /**
     * ลบ endpoint
     */
    public function destroy(ApiEndpoint $apiEndpoint)
    {
        $apiEndpoint->delete();

        return redirect()->route('admin.api-endpoints.index')
            ->with('success', 'ลบ API endpoint สำเร็จ');
    }

    /**
     * Toggle active status
     */
    public function toggleStatus(ApiEndpoint $apiEndpoint)
    {
        $apiEndpoint->update([
            'is_active' => !$apiEndpoint->is_active,
        ]);

        return response()->json([
            'success' => true,
            'is_active' => $apiEndpoint->is_active,
            'message' => $apiEndpoint->is_active ? 'เปิดใช้งาน endpoint' : 'ปิดใช้งาน endpoint',
        ]);
    }

    /**
     * Get analytics
     */
    public function analytics(ApiEndpoint $apiEndpoint, Request $request)
    {
        $days = $request->get('days', 7);
        $startDate = now()->subDays($days)->startOfDay();

        // Requests per day
        $requestsPerDay = $apiEndpoint->usageLogs()
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Response codes distribution
        $responseCodes = $apiEndpoint->usageLogs()
            ->where('created_at', '>=', $startDate)
            ->selectRaw('response_code, COUNT(*) as count')
            ->groupBy('response_code')
            ->get();

        // Average response time per day
        $avgResponseTime = $apiEndpoint->usageLogs()
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, AVG(response_time_ms) as avg_time')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'requests_per_day' => $requestsPerDay,
            'response_codes' => $responseCodes,
            'avg_response_time' => $avgResponseTime,
        ]);
    }

    /**
     * Get distinct categories
     */
    protected function getCategories()
    {
        return ApiEndpoint::select('category')
            ->distinct()
            ->whereNotNull('category')
            ->orderBy('category')
            ->pluck('category');
    }
}
