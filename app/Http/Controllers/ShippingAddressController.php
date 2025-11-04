<?php

namespace App\Http\Controllers;

use App\Models\ShippingAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShippingAddressController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the user's shipping addresses
     */
    public function index()
    {
        $addresses = ShippingAddress::where('user_id', auth()->id())
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('user.shipping-addresses.index', compact('addresses'));
    }

    /**
     * Show the form for creating a new shipping address
     */
    public function create()
    {
        return view('user.shipping-addresses.create');
    }

    /**
     * Store a newly created shipping address
     */
    public function store(Request $request)
    {
        $request->validate([
            'recipient_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'sub_district' => 'required|string|max:100',
            'district' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'postal_code' => 'required|string|max:10',
            'country' => 'nullable|string|max:100',
            'is_default' => 'nullable|boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();

        try {
            // If this is set as default, remove default from other addresses
            if ($request->is_default) {
                ShippingAddress::where('user_id', auth()->id())
                    ->update(['is_default' => false]);
            }

            // If user has no addresses, set this as default
            $hasAddresses = ShippingAddress::where('user_id', auth()->id())->exists();

            $address = ShippingAddress::create([
                'user_id' => auth()->id(),
                'recipient_name' => $request->recipient_name,
                'phone_number' => $request->phone_number,
                'address_line_1' => $request->address_line_1,
                'address_line_2' => $request->address_line_2,
                'sub_district' => $request->sub_district,
                'district' => $request->district,
                'province' => $request->province,
                'postal_code' => $request->postal_code,
                'country' => $request->country ?? 'ประเทศไทย',
                'is_default' => $request->is_default ?? !$hasAddresses,
                'notes' => $request->notes,
            ]);

            DB::commit();

            return redirect()
                ->route('shipping-addresses.index')
                ->with('success', 'เพิ่มที่อยู่จัดส่งสำเร็จ');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified shipping address
     */
    public function edit($id)
    {
        $address = ShippingAddress::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return view('user.shipping-addresses.edit', compact('address'));
    }

    /**
     * Update the specified shipping address
     */
    public function update(Request $request, $id)
    {
        $address = ShippingAddress::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $request->validate([
            'recipient_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'sub_district' => 'required|string|max:100',
            'district' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'postal_code' => 'required|string|max:10',
            'country' => 'nullable|string|max:100',
            'is_default' => 'nullable|boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();

        try {
            // If this is set as default, remove default from other addresses
            if ($request->is_default) {
                ShippingAddress::where('user_id', auth()->id())
                    ->where('id', '!=', $id)
                    ->update(['is_default' => false]);
            }

            $address->update([
                'recipient_name' => $request->recipient_name,
                'phone_number' => $request->phone_number,
                'address_line_1' => $request->address_line_1,
                'address_line_2' => $request->address_line_2,
                'sub_district' => $request->sub_district,
                'district' => $request->district,
                'province' => $request->province,
                'postal_code' => $request->postal_code,
                'country' => $request->country ?? 'ประเทศไทย',
                'is_default' => $request->is_default ?? false,
                'notes' => $request->notes,
            ]);

            DB::commit();

            return redirect()
                ->route('shipping-addresses.index')
                ->with('success', 'อัปเดตที่อยู่จัดส่งสำเร็จ');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified shipping address
     */
    public function destroy($id)
    {
        $address = ShippingAddress::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        DB::beginTransaction();

        try {
            $wasDefault = $address->is_default;
            $address->delete();

            // If deleted address was default, set another as default
            if ($wasDefault) {
                $newDefault = ShippingAddress::where('user_id', auth()->id())
                    ->first();

                if ($newDefault) {
                    $newDefault->update(['is_default' => true]);
                }
            }

            DB::commit();

            return redirect()
                ->route('shipping-addresses.index')
                ->with('success', 'ลบที่อยู่จัดส่งสำเร็จ');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Set shipping address as default
     */
    public function setDefault($id)
    {
        $address = ShippingAddress::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        DB::beginTransaction();

        try {
            // Remove default from all addresses
            ShippingAddress::where('user_id', auth()->id())
                ->update(['is_default' => false]);

            // Set this address as default
            $address->update(['is_default' => true]);

            DB::commit();

            return redirect()
                ->route('shipping-addresses.index')
                ->with('success', 'ตั้งเป็นที่อยู่เริ่มต้นสำเร็จ');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }
}
