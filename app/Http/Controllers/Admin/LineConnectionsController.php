<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LineTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * LineConnectionsController - จัดการผู้ใช้ที่เชื่อมต่อ LINE
 *
 * ฟีเจอร์:
 * - แสดงรายการผู้ใช้ที่เชื่อมต่อ LINE
 * - ค้นหาและฟิลเตอร์
 * - ตัดการเชื่อมต่อ LINE (single/bulk)
 * - Export ข้อมูล
 */
class LineConnectionsController extends Controller
{
    protected LineTokenService $tokenService;

    public function __construct(LineTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * แสดงรายการผู้ใช้ที่เชื่อมต่อ LINE
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = User::whereNotNull('line_user_id')
            ->select([
                'id',
                'name',
                'email',
                'phone',
                'line_user_id',
                'line_display_name',
                'line_picture_url',
                'line_linked_at',
                'line_verified',
                'created_at',
            ]);

        // ค้นหา
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('line_display_name', 'like', "%{$search}%")
                    ->orWhere('line_user_id', 'like', "%{$search}%");
            });
        }

        // ฟิลเตอร์ตาม verified status
        if ($request->filled('verified')) {
            $verified = $request->input('verified');
            if ($verified === '1') {
                $query->where('line_verified', true);
            } elseif ($verified === '0') {
                $query->where('line_verified', false);
            }
        }

        // ฟิลเตอร์ตามวันที่เชื่อมต่อ
        if ($request->filled('date_from')) {
            $query->where('line_linked_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->where('line_linked_at', '<=', $request->input('date_to') . ' 23:59:59');
        }

        // เรียงลำดับ
        $sortBy = $request->input('sort', 'line_linked_at');
        $sortDir = $request->input('dir', 'desc');
        $allowedSorts = ['name', 'email', 'line_display_name', 'line_linked_at', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('line_linked_at', 'desc');
        }

        // Pagination
        $perPage = $request->input('per_page', 20);
        $users = $query->paginate($perPage)->withQueryString();

        // สถิติ
        $stats = [
            'total_connected' => User::whereNotNull('line_user_id')->count(),
            'verified' => User::whereNotNull('line_user_id')->where('line_verified', true)->count(),
            'today' => User::whereNotNull('line_user_id')
                ->whereDate('line_linked_at', today())
                ->count(),
            'this_week' => User::whereNotNull('line_user_id')
                ->where('line_linked_at', '>=', now()->startOfWeek())
                ->count(),
        ];

        return view('admin.line-connections.index', compact('users', 'stats'));
    }

    /**
     * ตัดการเชื่อมต่อ LINE ของผู้ใช้
     *
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function disconnect(Request $request, User $user)
    {
        if (!$user->line_user_id) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ผู้ใช้นี้ไม่ได้เชื่อมต่อ LINE',
                ], 400);
            }
            return back()->with('error', 'ผู้ใช้นี้ไม่ได้เชื่อมต่อ LINE');
        }

        try {
            DB::transaction(function () use ($user) {
                $lineUserId = $user->line_user_id;

                // Revoke access token (ถ้ามี service)
                try {
                    $this->tokenService->revokeAccessToken($user);
                } catch (\Exception $e) {
                    Log::warning('Failed to revoke LINE token', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                // ล้างข้อมูล LINE
                $user->update([
                    'line_user_id' => null,
                    'line_display_name' => null,
                    'line_picture_url' => null,
                    'line_access_token' => null,
                    'line_linked_at' => null,
                    'line_verified' => false,
                ]);

                // Log activity
                activity()
                    ->performedOn($user)
                    ->causedBy(auth()->user())
                    ->withProperties(['line_user_id' => $lineUserId])
                    ->log('Admin ตัดการเชื่อมต่อ LINE');
            });

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'ตัดการเชื่อมต่อ LINE สำเร็จ',
                ]);
            }

            return back()->with('success', 'ตัดการเชื่อมต่อ LINE สำเร็จ');

        } catch (\Exception $e) {
            Log::error('Failed to disconnect LINE', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * ตัดการเชื่อมต่อ LINE หลายคนพร้อมกัน (Bulk disconnect)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkDisconnect(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        $userIds = $request->input('user_ids');
        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($userIds as $userId) {
            try {
                $user = User::find($userId);

                if (!$user || !$user->line_user_id) {
                    $failedCount++;
                    continue;
                }

                // Revoke access token
                try {
                    $this->tokenService->revokeAccessToken($user);
                } catch (\Exception $e) {
                    // Continue anyway
                }

                // ล้างข้อมูล LINE
                $user->update([
                    'line_user_id' => null,
                    'line_display_name' => null,
                    'line_picture_url' => null,
                    'line_access_token' => null,
                    'line_linked_at' => null,
                    'line_verified' => false,
                ]);

                $successCount++;

            } catch (\Exception $e) {
                $failedCount++;
                $errors[] = "User ID {$userId}: " . $e->getMessage();
            }
        }

        // Log bulk action
        activity()
            ->causedBy(auth()->user())
            ->withProperties([
                'user_ids' => $userIds,
                'success_count' => $successCount,
                'failed_count' => $failedCount,
            ])
            ->log('Admin ตัดการเชื่อมต่อ LINE แบบ bulk');

        return response()->json([
            'success' => true,
            'message' => "ตัดการเชื่อมต่อสำเร็จ {$successCount} รายการ" .
                ($failedCount > 0 ? ", ล้มเหลว {$failedCount} รายการ" : ''),
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'errors' => $errors,
        ]);
    }

    /**
     * Export รายการผู้ใช้ที่เชื่อมต่อ LINE
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(Request $request)
    {
        $query = User::whereNotNull('line_user_id');

        // Apply same filters as index
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('line_display_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('verified')) {
            $query->where('line_verified', $request->input('verified') === '1');
        }

        if ($request->filled('date_from')) {
            $query->where('line_linked_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->where('line_linked_at', '<=', $request->input('date_to') . ' 23:59:59');
        }

        $users = $query->orderBy('line_linked_at', 'desc')->get();

        $filename = 'line_connections_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($users) {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM for Excel
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Headers
            fputcsv($file, [
                'ID',
                'ชื่อ',
                'อีเมล',
                'เบอร์โทร',
                'LINE User ID',
                'LINE Display Name',
                'Verified',
                'วันที่เชื่อมต่อ',
                'วันที่สมัคร',
            ]);

            // Data
            foreach ($users as $user) {
                fputcsv($file, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->phone ?? '-',
                    $user->line_user_id,
                    $user->line_display_name ?? '-',
                    $user->line_verified ? 'Yes' : 'No',
                    $user->line_linked_at?->format('Y-m-d H:i:s') ?? '-',
                    $user->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * แสดงรายละเอียดผู้ใช้
     *
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(User $user)
    {
        if (!$user->line_user_id) {
            return response()->json([
                'success' => false,
                'message' => 'ผู้ใช้นี้ไม่ได้เชื่อมต่อ LINE',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'line_user_id' => $user->line_user_id,
                'line_display_name' => $user->line_display_name,
                'line_picture_url' => $user->line_picture_url,
                'line_linked_at' => $user->line_linked_at?->format('Y-m-d H:i:s'),
                'line_verified' => $user->line_verified,
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }
}
