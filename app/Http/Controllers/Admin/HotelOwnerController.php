<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class HotelOwnerController extends Controller
{
    /**
     * แสดงรายการเจ้าของโรงแรมทั้งหมด
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = User::where('is_hotel_admin', true)
            ->with(['managedHotel']);

        // ค้นหา
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // กรองตามสถานะ
        if ($request->has('status')) {
            switch ($request->status) {
                case 'active':
                    $query->whereNull('blocked_at');
                    break;
                case 'blocked':
                    $query->whereNotNull('blocked_at');
                    break;
                case 'verified':
                    $query->whereNotNull('email_verified_at');
                    break;
                case 'unverified':
                    $query->whereNull('email_verified_at');
                    break;
            }
        }

        // กรองตามสถานะโรงแรม
        if ($request->has('hotel_status')) {
            $query->whereHas('managedHotel', function($q) use ($request) {
                if ($request->hotel_status === 'active') {
                    $q->where('is_active', true);
                } else if ($request->hotel_status === 'inactive') {
                    $q->where('is_active', false);
                }
            });
        }

        // เรียงลำดับ
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $query->orderBy($sortBy, $sortOrder);

        $hotelOwners = $query->paginate($request->get('per_page', 20));

        // เพิ่มสถิติสำหรับแต่ละเจ้าของ
        $hotelOwners->getCollection()->transform(function($owner) {
            $hotel = $owner->managedHotel;

            if ($hotel) {
                $owner->statistics = [
                    'total_bookings' => $hotel->bookings()->count(),
                    'total_revenue' => $hotel->bookings()
                        ->whereIn('status', ['confirmed', 'checked_in', 'completed'])
                        ->sum('total_amount'),
                    'total_reviews' => $hotel->reviews()->count(),
                    'average_rating' => round($hotel->rating, 2),
                    'total_rooms' => $hotel->roomTypes()->sum('total_rooms'),
                ];
            } else {
                $owner->statistics = null;
            }

            return $owner;
        });

        // คำนวณสถิติรวม
        $stats = [
            'total' => User::where('is_hotel_admin', true)->count(),
            'active' => User::where('is_hotel_admin', true)->whereNull('blocked_at')->count(),
            'blocked' => User::where('is_hotel_admin', true)->whereNotNull('blocked_at')->count(),
            'with_hotel' => User::where('is_hotel_admin', true)->whereNotNull('managed_hotel_id')->count(),
        ];

        return view('admin.hotel-owners.index', compact('hotelOwners', 'stats'));
    }

    /**
     * แสดงรายละเอียดเจ้าของโรงแรม
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $hotelOwner = User::where('is_hotel_admin', true)
            ->with(['managedHotel.roomTypes', 'managedHotel.bookings', 'managedHotel.reviews'])
            ->findOrFail($id);

        $hotel = $hotelOwner->managedHotel;

        $statistics = [];
        if ($hotel) {
            $statistics = [
                'total_bookings' => $hotel->bookings()->count(),
                'confirmed_bookings' => $hotel->bookings()->where('status', 'confirmed')->count(),
                'completed_bookings' => $hotel->bookings()->where('status', 'completed')->count(),
                'cancelled_bookings' => $hotel->bookings()->where('status', 'cancelled')->count(),
                'total_revenue' => $hotel->bookings()
                    ->whereIn('status', ['confirmed', 'checked_in', 'completed'])
                    ->sum('total_amount'),
                'total_reviews' => $hotel->reviews()->count(),
                'approved_reviews' => $hotel->reviews()->where('is_approved', true)->count(),
                'average_rating' => round($hotel->rating, 2),
                'total_rooms' => $hotel->roomTypes()->sum('total_rooms'),
                'active_room_types' => $hotel->roomTypes()->where('is_active', true)->count(),
            ];
        }

        return view('admin.hotel-owners.show', compact('hotelOwner', 'statistics'));
    }

    /**
     * แสดงฟอร์มสร้างเจ้าของโรงแรมใหม่
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // ดึงรายการโรงแรมที่ยังไม่มีเจ้าของ
        $availableHotels = Hotel::whereNull('owner_id')->get();

        return view('admin.hotel-owners.create', compact('availableHotels'));
    }

    /**
     * แสดงฟอร์มแก้ไขเจ้าของโรงแรม
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $hotelOwner = User::where('is_hotel_admin', true)
            ->with('managedHotel')
            ->findOrFail($id);

        // ดึงรายการโรงแรมที่ยังไม่มีเจ้าของ หรือเป็นโรงแรมที่เจ้าของคนนี้จัดการอยู่
        $availableHotels = Hotel::where(function($query) use ($hotelOwner) {
            $query->whereNull('owner_id')
                  ->orWhere('owner_id', $hotelOwner->id);
        })->get();

        return view('admin.hotel-owners.edit', compact('hotelOwner', 'availableHotels'));
    }

    /**
     * บันทึกเจ้าของโรงแรมใหม่
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:50',
            'password' => 'required|string|min:8',
            'managed_hotel_id' => 'nullable|exists:hotels,id',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_hotel_admin'] = true;

        $hotelOwner = User::create($validated);

        // อัพเดท hotel owner_id ถ้ามีการกำหนดโรงแรม
        if (isset($validated['managed_hotel_id'])) {
            Hotel::where('id', $validated['managed_hotel_id'])
                ->update(['owner_id' => $hotelOwner->id]);
        }

        return redirect()
            ->route('admin.hotel-owners.index')
            ->with('success', 'สร้างเจ้าของโรงแรมสำเร็จ');
    }

    /**
     * อัพเดทเจ้าของโรงแรม
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $hotelOwner = User::where('is_hotel_admin', true)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($hotelOwner->id)],
            'phone' => 'nullable|string|max:50',
            'password' => 'nullable|string|min:8',
            'managed_hotel_id' => 'nullable|exists:hotels,id',
        ]);

        // เก็บ managed_hotel_id เดิมไว้เปรียบเทียบ
        $oldManagedHotelId = $hotelOwner->managed_hotel_id;

        // hash password ถ้ามีการกรอก
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $hotelOwner->update($validated);

        // อัพเดท hotel owner_id
        if ($oldManagedHotelId != $validated['managed_hotel_id']) {
            // ลบ owner_id เดิม (ถ้ามี)
            if ($oldManagedHotelId) {
                Hotel::where('id', $oldManagedHotelId)
                    ->update(['owner_id' => null]);
            }

            // เพิ่ม owner_id ใหม่
            if (isset($validated['managed_hotel_id'])) {
                Hotel::where('id', $validated['managed_hotel_id'])
                    ->update(['owner_id' => $hotelOwner->id]);
            }
        }

        return redirect()
            ->route('admin.hotel-owners.index')
            ->with('success', 'อัพเดทเจ้าของโรงแรมสำเร็จ');
    }

    /**
     * Block hotel owner
     */
    public function block($id)
    {
        $hotelOwner = User::where('is_hotel_admin', true)
            ->findOrFail($id);

        if ($hotelOwner->blocked_at) {
            return response()->json([
                'success' => false,
                'message' => 'Hotel owner is already blocked',
            ], 422);
        }

        $hotelOwner->update([
            'blocked_at' => now(),
        ]);

        // Also deactivate the managed hotel
        if ($hotelOwner->managedHotel) {
            $hotelOwner->managedHotel->update(['is_active' => false]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Hotel owner blocked successfully',
            'hotel_owner' => $hotelOwner->load('managedHotel'),
        ]);
    }

    /**
     * Unblock hotel owner
     */
    public function unblock($id)
    {
        $hotelOwner = User::where('is_hotel_admin', true)
            ->findOrFail($id);

        if (!$hotelOwner->blocked_at) {
            return response()->json([
                'success' => false,
                'message' => 'Hotel owner is not blocked',
            ], 422);
        }

        $hotelOwner->update([
            'blocked_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Hotel owner unblocked successfully',
            'hotel_owner' => $hotelOwner->load('managedHotel'),
        ]);
    }

    /**
     * ลบเจ้าของโรงแรม
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $hotelOwner = User::where('is_hotel_admin', true)
            ->findOrFail($id);

        // ตรวจสอบว่ามีโรงแรมที่จัดการอยู่หรือไม่
        if ($hotelOwner->managedHotel) {
            return redirect()
                ->route('admin.hotel-owners.index')
                ->with('error', 'ไม่สามารถลบเจ้าของโรงแรมที่มีโรงแรมในการดูแลได้ กรุณาถอดโรงแรมออกก่อน');
        }

        $hotelOwner->delete();

        return redirect()
            ->route('admin.hotel-owners.index')
            ->with('success', 'ลบเจ้าของโรงแรมสำเร็จ');
    }

    /**
     * Assign hotel to owner
     */
    public function assignHotel(Request $request, $id)
    {
        $hotelOwner = User::where('is_hotel_admin', true)
            ->findOrFail($id);

        $validated = $request->validate([
            'hotel_id' => 'required|exists:hotels,id',
        ]);

        // Check if hotel is already assigned to another owner
        $existingOwner = User::where('managed_hotel_id', $validated['hotel_id'])
            ->where('id', '!=', $hotelOwner->id)
            ->first();

        if ($existingOwner) {
            return response()->json([
                'success' => false,
                'message' => 'Hotel is already assigned to another owner',
            ], 422);
        }

        $hotelOwner->update(['managed_hotel_id' => $validated['hotel_id']]);

        // Update hotel owner_id
        Hotel::where('id', $validated['hotel_id'])
            ->update(['owner_id' => $hotelOwner->id]);

        return response()->json([
            'success' => true,
            'message' => 'Hotel assigned successfully',
            'hotel_owner' => $hotelOwner->load('managedHotel'),
        ]);
    }

    /**
     * Unassign hotel from owner
     */
    public function unassignHotel($id)
    {
        $hotelOwner = User::where('is_hotel_admin', true)
            ->findOrFail($id);

        if (!$hotelOwner->managed_hotel_id) {
            return response()->json([
                'success' => false,
                'message' => 'Hotel owner does not have an assigned hotel',
            ], 422);
        }

        $hotelId = $hotelOwner->managed_hotel_id;

        $hotelOwner->update(['managed_hotel_id' => null]);

        // Update hotel owner_id
        Hotel::where('id', $hotelId)
            ->update(['owner_id' => null]);

        return response()->json([
            'success' => true,
            'message' => 'Hotel unassigned successfully',
            'hotel_owner' => $hotelOwner,
        ]);
    }

    /**
     * Get statistics for all hotel owners
     */
    public function statistics()
    {
        $totalOwners = User::where('is_hotel_admin', true)->count();
        $activeOwners = User::where('is_hotel_admin', true)
            ->whereNull('blocked_at')
            ->count();
        $blockedOwners = User::where('is_hotel_admin', true)
            ->whereNotNull('blocked_at')
            ->count();

        $ownersWithHotels = User::where('is_hotel_admin', true)
            ->whereNotNull('managed_hotel_id')
            ->count();
        $ownersWithoutHotels = $totalOwners - $ownersWithHotels;

        $totalRevenue = Hotel::join('hotel_bookings', 'hotels.id', '=', 'hotel_bookings.hotel_id')
            ->whereIn('hotel_bookings.status', ['confirmed', 'checked_in', 'completed'])
            ->sum('hotel_bookings.total_amount');

        $totalBookings = Hotel::join('hotel_bookings', 'hotels.id', '=', 'hotel_bookings.hotel_id')
            ->count();

        return response()->json([
            'success' => true,
            'statistics' => [
                'total_owners' => $totalOwners,
                'active_owners' => $activeOwners,
                'blocked_owners' => $blockedOwners,
                'owners_with_hotels' => $ownersWithHotels,
                'owners_without_hotels' => $ownersWithoutHotels,
                'total_revenue' => $totalRevenue,
                'total_bookings' => $totalBookings,
            ],
        ]);
    }
}
