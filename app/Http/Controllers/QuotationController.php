<?php

namespace App\Http\Controllers;

use App\Models\SoftwareProduct;
use App\Models\SoftwareQuotation;
use App\Models\Order;
use App\Models\User;
use App\Services\QuotationCalculatorService;
use App\Mail\QuotationMail;
use App\Notifications\NewQuotationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class QuotationController extends Controller
{
    protected QuotationCalculatorService $calculatorService;

    public function __construct(QuotationCalculatorService $calculatorService)
    {
        $this->calculatorService = $calculatorService;
    }

    /**
     * Display user's quotations
     */
    public function index()
    {
        $quotations = SoftwareQuotation::where('user_id', Auth::id())
            ->with('softwareProduct')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('quotations.index', compact('quotations'));
    }

    /**
     * Store a newly created quotation
     */
    public function store(Request $request, SoftwareProduct $product)
    {
        $validated = $request->validate([
            'selections' => 'required|array',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'company_name' => 'nullable|string|max:255',
            'company_address' => 'nullable|string',
            'tax_id' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:1000',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $customerInfo = [
            'name' => $validated['customer_name'],
            'email' => $validated['customer_email'],
            'phone' => $validated['customer_phone'] ?? null,
            'company_name' => $validated['company_name'] ?? null,
            'company_address' => $validated['company_address'] ?? null,
            'tax_id' => $validated['tax_id'] ?? null,
        ];

        $quotation = $this->calculatorService->createQuotation(
            Auth::id(),
            $product,
            $validated['selections'],
            $customerInfo,
            $validated['tax_rate'] ?? 7,
            $validated['discount_percentage'] ?? 0,
            $validated['notes'] ?? null
        );

        // Update status to pending
        $quotation->status = 'pending';
        $quotation->save();

        // Notify admins
        $admins = User::where('is_admin', true)->get();
        Notification::send($admins, new NewQuotationNotification($quotation));

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('success', 'สร้างใบเสนอราคาเรียบร้อยแล้ว กรุณารอการติดต่อจากเจ้าหน้าที่');
    }

    /**
     * Display the specified quotation
     */
    public function show(SoftwareQuotation $quotation)
    {
        // Check authorization
        if ($quotation->user_id !== Auth::id() && !Auth::user()->is_admin) {
            abort(403);
        }

        $quotation->load(['softwareProduct', 'items.selectedOptions', 'order', 'installmentPlan']);

        return view('quotations.show', compact('quotation'));
    }

    /**
     * Accept quotation
     */
    public function accept(SoftwareQuotation $quotation)
    {
        // Check authorization
        if ($quotation->user_id !== Auth::id()) {
            abort(403);
        }

        if (!$quotation->canBeAccepted()) {
            return back()->with('error', 'ไม่สามารถยอมรับใบเสนอราคานี้ได้');
        }

        $quotation->markAsAccepted();

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('success', 'ยอมรับใบเสนอราคาเรียบร้อยแล้ว คุณสามารถดำเนินการสั่งซื้อได้');
    }

    /**
     * Reject quotation
     */
    public function reject(Request $request, SoftwareQuotation $quotation)
    {
        // Check authorization
        if ($quotation->user_id !== Auth::id()) {
            abort(403);
        }

        $quotation->markAsRejected();

        return redirect()
            ->route('quotations.index')
            ->with('success', 'ปฏิเสธใบเสนอราคาเรียบร้อยแล้ว');
    }

    /**
     * Convert quotation to order
     */
    public function convertToOrder(Request $request, SoftwareQuotation $quotation)
    {
        // Check authorization
        if ($quotation->user_id !== Auth::id()) {
            abort(403);
        }

        if ($quotation->status !== 'accepted') {
            return back()->with('error', 'กรุณายอมรับใบเสนอราคาก่อนสั่งซื้อ');
        }

        if ($quotation->order_id) {
            return redirect()
                ->route('orders.show', $quotation->order_id)
                ->with('info', 'ใบเสนอราคานี้ถูกแปลงเป็นคำสั่งซื้อแล้ว');
        }

        // Create order
        $order = Order::create([
            'user_id' => Auth::id(),
            'order_number' => 'ORD-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(6)),
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => $quotation->subtotal,
            'discount_amount' => $quotation->discount_amount,
            'tax_amount' => $quotation->tax_amount,
            'total_amount' => $quotation->total_amount,
            'payment_method' => 'pending',
        ]);

        // Create order items from quotation items
        foreach ($quotation->items as $item) {
            $order->items()->create([
                'product_id' => $item->software_product_id,
                'product_name' => $item->item_name,
                'product_description' => $item->item_description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total_price' => $item->subtotal,
            ]);
        }

        // Mark quotation as converted
        $quotation->markAsConverted($order);

        return redirect()
            ->route('checkout', $order)
            ->with('success', 'สร้างคำสั่งซื้อเรียบร้อยแล้ว กรุณาดำเนินการชำระเงิน');
    }
}
