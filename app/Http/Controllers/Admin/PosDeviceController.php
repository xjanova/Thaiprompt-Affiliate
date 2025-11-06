<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PosDevice;
use App\Models\VendorStore;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PosDeviceController extends Controller
{
    public function index(Request $request)
    {
        $query = PosDevice::with('store')
            ->latest();

        // Filters
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('device_name', 'like', "%{$search}%")
                  ->orWhere('device_code', 'like', "%{$search}%")
                  ->orWhere('license_key', 'like', "%{$search}%");
            });
        }

        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->has('status')) {
            $query->where('subscription_status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('device_type', $request->type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $devices = $query->paginate(20);
        $stores = VendorStore::active()->get();

        return view('admin.pos.devices.index', compact('devices', 'stores'));
    }

    public function create()
    {
        $stores = VendorStore::active()->get();
        return view('admin.pos.devices.create', compact('stores'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:vendor_stores,id',
            'device_name' => 'required|string|max:255',
            'device_type' => 'required|in:standard,premium',
            'hardware_id' => 'nullable|string|max:255',
            'subscription_status' => 'required|in:trial,active,expired,suspended',
            'trial_ends_at' => 'nullable|date',
            'subscription_starts_at' => 'nullable|date',
            'subscription_ends_at' => 'nullable|date',
            'monthly_fee' => 'required|numeric|min:0',
            'auto_renew' => 'boolean',
            'features' => 'nullable|array',
            'settings' => 'nullable|array',
            'dual_screen_enabled' => 'boolean',
            'offline_mode_enabled' => 'boolean',
            'receipt_printer_enabled' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $device = PosDevice::create($validated);

        return redirect()
            ->route('admin.pos.devices.show', $device)
            ->with('success', 'POS device created successfully!');
    }

    public function show(PosDevice $device)
    {
        $device->load(['store', 'sessions.user', 'transactions']);

        $stats = [
            'total_transactions' => $device->transactions()->count(),
            'today_transactions' => $device->transactions()->whereDate('transaction_date', today())->count(),
            'total_sales' => $device->transactions()->sum('total_amount'),
            'today_sales' => $device->transactions()->whereDate('transaction_date', today())->sum('total_amount'),
            'active_session' => $device->sessions()->open()->first(),
            'last_session' => $device->sessions()->latest()->first(),
        ];

        return view('admin.pos.devices.show', compact('device', 'stats'));
    }

    public function edit(PosDevice $device)
    {
        $stores = VendorStore::active()->get();
        return view('admin.pos.devices.edit', compact('device', 'stores'));
    }

    public function update(Request $request, PosDevice $device)
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:vendor_stores,id',
            'device_name' => 'required|string|max:255',
            'device_type' => 'required|in:standard,premium',
            'hardware_id' => 'nullable|string|max:255',
            'subscription_status' => 'required|in:trial,active,expired,suspended',
            'trial_ends_at' => 'nullable|date',
            'subscription_starts_at' => 'nullable|date',
            'subscription_ends_at' => 'nullable|date',
            'monthly_fee' => 'required|numeric|min:0',
            'auto_renew' => 'boolean',
            'features' => 'nullable|array',
            'settings' => 'nullable|array',
            'dual_screen_enabled' => 'boolean',
            'offline_mode_enabled' => 'boolean',
            'receipt_printer_enabled' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $device->update($validated);

        return redirect()
            ->route('admin.pos.devices.show', $device)
            ->with('success', 'POS device updated successfully!');
    }

    public function destroy(PosDevice $device)
    {
        $device->delete();

        return redirect()
            ->route('admin.pos.devices.index')
            ->with('success', 'POS device deleted successfully!');
    }

    public function regenerateLicenseKey(PosDevice $device)
    {
        $device->update([
            'license_key' => $device->generateLicenseKey(),
        ]);

        return back()->with('success', 'License key regenerated successfully!');
    }

    public function toggleStatus(PosDevice $device)
    {
        $device->update([
            'is_active' => !$device->is_active,
        ]);

        $status = $device->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Device {$status} successfully!");
    }

    public function extendSubscription(Request $request, PosDevice $device)
    {
        $validated = $request->validate([
            'extension_months' => 'required|integer|min:1|max:36',
        ]);

        $currentEnd = $device->subscription_ends_at ?? now();
        $newEnd = $currentEnd->addMonths($validated['extension_months']);

        $device->update([
            'subscription_ends_at' => $newEnd,
            'subscription_status' => 'active',
        ]);

        return back()->with('success', "Subscription extended by {$validated['extension_months']} months!");
    }

    public function suspend(Request $request, PosDevice $device)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $device->update([
            'subscription_status' => 'suspended',
            'is_active' => false,
        ]);

        // Log the suspension
        activity()
            ->performedOn($device)
            ->withProperties(['reason' => $validated['reason'] ?? 'No reason provided'])
            ->log('Device suspended');

        return back()->with('success', 'Device suspended successfully!');
    }

    public function reactivate(PosDevice $device)
    {
        $device->update([
            'subscription_status' => 'active',
            'is_active' => true,
        ]);

        activity()
            ->performedOn($device)
            ->log('Device reactivated');

        return back()->with('success', 'Device reactivated successfully!');
    }

    public function forceOffline(PosDevice $device)
    {
        $device->updateOnlineStatus(false);

        // Force close any open sessions
        $device->sessions()->open()->each(function ($session) {
            $session->forceClose('Device forced offline by admin');
        });

        return back()->with('success', 'Device forced offline and all sessions closed!');
    }

    public function export(Request $request)
    {
        $devices = PosDevice::with('store')
            ->when($request->has('store_id'), fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->has('status'), fn($q) => $q->where('subscription_status', $request->status))
            ->get();

        $filename = 'pos_devices_' . now()->format('YmdHis') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($devices) {
            $file = fopen('php://output', 'w');

            // Headers
            fputcsv($file, [
                'Device Code',
                'Device Name',
                'Store',
                'Type',
                'Status',
                'License Key',
                'Monthly Fee',
                'Total Transactions',
                'Total Sales',
                'Last Online',
                'Created At',
            ]);

            // Data
            foreach ($devices as $device) {
                fputcsv($file, [
                    $device->device_code,
                    $device->device_name,
                    $device->store?->store_name ?? 'N/A',
                    $device->device_type,
                    $device->subscription_status,
                    $device->license_key,
                    $device->monthly_fee,
                    $device->total_transactions,
                    $device->total_sales,
                    $device->last_online_at?->format('Y-m-d H:i:s') ?? 'Never',
                    $device->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
