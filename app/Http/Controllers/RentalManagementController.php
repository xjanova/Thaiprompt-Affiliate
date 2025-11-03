<?php

namespace App\Http\Controllers;

use App\Models\BotRental;
use App\Models\RentalTransaction;
use App\Services\RentalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RentalManagementController extends Controller
{
    private RentalService $rentalService;

    public function __construct()
    {
        $this->middleware('auth');
        $this->rentalService = new RentalService();
    }

    /**
     * Display user's rentals
     */
    public function index(Request $request)
    {
        $query = BotRental::where('renter_id', Auth::id())
            ->with(['botProfile', 'botProfile.owner']);

        // Status filter
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } else {
                $query->where('status', $request->status);
            }
        } else {
            // Default: show active rentals
            $query->active();
        }

        // Type filter
        if ($request->filled('type')) {
            $query->where('rental_type', $request->type);
        }

        $rentals = $query->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        // Stats
        $stats = [
            'active_rentals' => BotRental::where('renter_id', Auth::id())->active()->count(),
            'total_spent' => RentalTransaction::where('user_id', Auth::id())
                ->where('status', 'completed')
                ->sum('amount'),
            'total_messages' => BotRental::where('renter_id', Auth::id())
                ->where('rental_type', 'per_message')
                ->sum('total_messages'),
            'monthly_cost' => BotRental::where('renter_id', Auth::id())
                ->where('rental_type', 'monthly')
                ->active()
                ->sum('price'),
        ];

        return view('my-rentals.index', compact('rentals', 'stats'));
    }

    /**
     * Show rental details
     */
    public function show($id)
    {
        $rental = BotRental::with(['botProfile', 'botProfile.owner', 'transactions'])
            ->where('renter_id', Auth::id())
            ->findOrFail($id);

        // Get transaction history
        $transactions = $rental->transactions()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('my-rentals.show', compact('rental', 'transactions'));
    }

    /**
     * Cancel rental
     */
    public function cancel(Request $request, $id)
    {
        $rental = BotRental::where('renter_id', Auth::id())
            ->findOrFail($id);

        if (!$rental->isActive()) {
            return back()->with('error', 'ไม่สามารถยกเลิกการเช่าที่ไม่ได้ active');
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $result = $this->rentalService->cancelRental(
            $rental,
            $validated['reason'] ?? 'Cancelled by user'
        );

        if (!$result['success']) {
            return back()->with('error', $result['error']);
        }

        return redirect()
            ->route('my-rentals.index')
            ->with('success', 'ยกเลิกการเช่าสำเร็จ');
    }

    /**
     * Toggle auto-renewal
     */
    public function toggleAutoRenew($id)
    {
        $rental = BotRental::where('renter_id', Auth::id())
            ->where('rental_type', 'monthly')
            ->findOrFail($id);

        if (!$rental->isActive()) {
            return back()->with('error', 'ไม่สามารถแก้ไขการเช่าที่ไม่ได้ active');
        }

        $rental->auto_renew = !$rental->auto_renew;
        $rental->save();

        $message = $rental->auto_renew
            ? 'เปิดการต่ออายุอัตโนมัติแล้ว'
            : 'ปิดการต่ออายุอัตโนมัติแล้ว';

        return back()->with('success', $message);
    }

    /**
     * View transaction history
     */
    public function transactions(Request $request)
    {
        $query = RentalTransaction::where('user_id', Auth::id())
            ->with(['rental', 'rental.botProfile']);

        // Type filter
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Monthly summary
        $monthlySummary = RentalTransaction::where('user_id', Auth::id())
            ->where('status', 'completed')
            ->whereYear('created_at', date('Y'))
            ->whereMonth('created_at', date('m'))
            ->sum('amount');

        return view('my-rentals.transactions', compact('transactions', 'monthlySummary'));
    }
}
