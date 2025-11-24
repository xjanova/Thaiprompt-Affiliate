<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NFCReader;
use Illuminate\Http\Request;
use Exception;

class NFCReaderController extends Controller
{
    /**
     * Display a listing of NFC readers
     *
     * ⚠️ SECURITY: ตรวจสอบสิทธิ์ก่อนดูรายการ
     */
    public function index(Request $request)
    {
        // ✅ ตรวจสอบสิทธิ์ในการดูรายการ
        $this->authorize('viewAny', NFCReader::class);

        $query = NFCReader::with('creator')->latest();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('reader_id', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by location
        if ($request->filled('location')) {
            $query->where('location', 'like', "%{$request->location}%");
        }

        $readers = $query->paginate(20);

        // Statistics
        $statistics = [
            'total_readers' => NFCReader::count(),
            'active_readers' => NFCReader::active()->count(),
            'online_readers' => NFCReader::online()->count(),
            'offline_readers' => NFCReader::count() - NFCReader::online()->count(),
        ];

        return view('admin.nfc-readers.index', compact('readers', 'statistics'));
    }

    /**
     * Show the form for creating a new reader
     *
     * ⚠️ SECURITY: ตรวจสอบสิทธิ์ก่อนสร้าง
     */
    public function create()
    {
        // ✅ ตรวจสอบสิทธิ์ในการสร้าง
        $this->authorize('create', NFCReader::class);

        return view('admin.nfc-readers.create');
    }

    /**
     * Store a newly created reader
     *
     * ⚠️ SECURITY: ป้องกัน unauthorized creation
     */
    public function store(Request $request)
    {
        // ✅ ตรวจสอบสิทธิ์ในการสร้าง
        $this->authorize('create', NFCReader::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'reader_id' => 'required|string|unique:nfc_readers,reader_id',
            'serial_number' => 'required|string|unique:nfc_readers,serial_number',
            'location' => 'required|string|max:255',
            'description' => 'nullable|string',
            'ip_address' => 'nullable|ip',
            'mac_address' => 'nullable|string|max:17',
            'settings' => 'nullable|array',
        ]);

        try {
            $reader = NFCReader::create(array_merge($validated, [
                'status' => NFCReader::STATUS_ACTIVE,
                'created_by' => auth()->id(),
                'last_heartbeat' => now(),
            ]));

            return redirect()
                ->route('admin.nfc-readers.show', $reader)
                ->with('success', 'เพิ่มเครื่องอ่านบัตรสำเร็จ');
        } catch (Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'ไม่สามารถเพิ่มเครื่องอ่านบัตรได้: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified reader
     *
     * ⚠️ SECURITY: ตรวจสอบสิทธิ์ก่อนดูข้อมูล
     */
    public function show(NFCReader $nfcReader)
    {
        // ✅ ตรวจสอบสิทธิ์ก่อนดู
        $this->authorize('view', $nfcReader);

        $nfcReader->load('creator');

        // Get recent transactions
        $recentTransactions = $nfcReader->transactions()
            ->with(['nfcCard', 'user'])
            ->latest()
            ->limit(20)
            ->get();

        // Get transaction statistics
        $statistics = [
            'total_transactions' => $nfcReader->transactions()->count(),
            'today_transactions' => $nfcReader->transactions()->today()->count(),
            'completed_transactions' => $nfcReader->transactions()->completed()->count(),
            'failed_transactions' => $nfcReader->transactions()->failed()->count(),
            'total_amount_today' => $nfcReader->transactions()
                ->today()
                ->where('type', 'payment')
                ->where('status', 'completed')
                ->sum('amount'),
        ];

        return view('admin.nfc-readers.show', compact('nfcReader', 'recentTransactions', 'statistics'));
    }

    /**
     * Show the form for editing the reader
     *
     * ⚠️ SECURITY: ตรวจสอบสิทธิ์ก่อนแก้ไข
     */
    public function edit(NFCReader $nfcReader)
    {
        // ✅ ตรวจสอบสิทธิ์ก่อนแก้ไข
        $this->authorize('update', $nfcReader);

        return view('admin.nfc-readers.edit', compact('nfcReader'));
    }

    /**
     * Update the reader
     *
     * ⚠️ SECURITY: ป้องกัน IDOR - ตรวจสอบสิทธิ์ก่อนแก้ไข
     */
    public function update(Request $request, NFCReader $nfcReader)
    {
        // ✅ ตรวจสอบสิทธิ์ก่อนอัพเดท
        $this->authorize('update', $nfcReader);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'description' => 'nullable|string',
            'ip_address' => 'nullable|ip',
            'mac_address' => 'nullable|string|max:17',
            'settings' => 'nullable|array',
        ]);

        try {
            $nfcReader->update($validated);

            return redirect()
                ->route('admin.nfc-readers.show', $nfcReader)
                ->with('success', 'อัพเดทข้อมูลเครื่องอ่านบัตรสำเร็จ');
        } catch (Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'ไม่สามารถอัพเดทข้อมูลได้: ' . $e->getMessage());
        }
    }

    /**
     * Delete the reader
     *
     * ⚠️ SECURITY: ป้องกัน IDOR - ตรวจสอบสิทธิ์ก่อนลบ
     */
    public function destroy(NFCReader $nfcReader)
    {
        // ✅ ตรวจสอบสิทธิ์ก่อนลบ
        $this->authorize('delete', $nfcReader);

        try {
            $nfcReader->delete();

            return redirect()
                ->route('admin.nfc-readers.index')
                ->with('success', 'ลบเครื่องอ่านบัตรสำเร็จ');
        } catch (Exception $e) {
            return back()->with('error', 'ไม่สามารถลบเครื่องอ่านบัตรได้: ' . $e->getMessage());
        }
    }

    /**
     * Activate reader
     *
     * ⚠️ SECURITY: ตรวจสอบสิทธิ์ก่อนเปลี่ยนสถานะ
     */
    public function activate(NFCReader $nfcReader)
    {
        // ✅ ตรวจสอบสิทธิ์ก่อนเปลี่ยนสถานะ
        $this->authorize('update', $nfcReader);

        try {
            $nfcReader->activate();

            return back()->with('success', 'เปิดใช้งานเครื่องอ่านบัตรสำเร็จ');
        } catch (Exception $e) {
            return back()->with('error', 'ไม่สามารถเปิดใช้งานได้: ' . $e->getMessage());
        }
    }

    /**
     * Deactivate reader
     *
     * ⚠️ SECURITY: ตรวจสอบสิทธิ์ก่อนเปลี่ยนสถานะ
     */
    public function deactivate(NFCReader $nfcReader)
    {
        // ✅ ตรวจสอบสิทธิ์ก่อนเปลี่ยนสถานะ
        $this->authorize('update', $nfcReader);

        try {
            $nfcReader->deactivate();

            return back()->with('success', 'ปิดการใช้งานเครื่องอ่านบัตรสำเร็จ');
        } catch (Exception $e) {
            return back()->with('error', 'ไม่สามารถปิดการใช้งานได้: ' . $e->getMessage());
        }
    }

    /**
     * Set reader to maintenance mode
     *
     * ⚠️ SECURITY: ตรวจสอบสิทธิ์ก่อนเปลี่ยนสถานะ
     */
    public function maintenance(NFCReader $nfcReader)
    {
        // ✅ ตรวจสอบสิทธิ์ก่อนเปลี่ยนสถานะ
        $this->authorize('update', $nfcReader);

        try {
            $nfcReader->setMaintenance();

            return back()->with('success', 'ตั้งค่าเครื่องอ่านบัตรเป็นโหมดปรับปรุงสำเร็จ');
        } catch (Exception $e) {
            return back()->with('error', 'ไม่สามารถตั้งค่าได้: ' . $e->getMessage());
        }
    }

    /**
     * Update heartbeat
     */
    public function heartbeat(Request $request, NFCReader $nfcReader)
    {
        try {
            $nfcReader->updateHeartbeat();

            return response()->json([
                'success' => true,
                'message' => 'Heartbeat updated',
                'is_online' => $nfcReader->isOnline(),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get reader status
     *
     * ⚠️ SECURITY: ตรวจสอบสิทธิ์ก่อนดูข้อมูล
     */
    public function status(NFCReader $nfcReader)
    {
        // ✅ ตรวจสอบสิทธิ์ก่อนดู status
        $this->authorize('view', $nfcReader);

        return response()->json([
            'reader_id' => $nfcReader->reader_id,
            'name' => $nfcReader->name,
            'status' => $nfcReader->status,
            'is_online' => $nfcReader->isOnline(),
            'last_heartbeat' => $nfcReader->last_heartbeat,
            'location' => $nfcReader->location,
        ]);
    }
}
