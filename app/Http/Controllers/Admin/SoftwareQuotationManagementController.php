<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SoftwareQuotation;
use App\Mail\QuotationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SoftwareQuotationManagementController extends Controller
{
    /**
     * Display a listing of quotations
     */
    public function index(Request $request)
    {
        $query = SoftwareQuotation::with(['user', 'softwareProduct']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('quotation_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%");
            });
        }

        $quotations = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get stats
        $stats = [
            'total' => SoftwareQuotation::count(),
            'pending' => SoftwareQuotation::where('status', 'pending')->count(),
            'sent' => SoftwareQuotation::where('status', 'sent')->count(),
            'accepted' => SoftwareQuotation::where('status', 'accepted')->count(),
            'converted' => SoftwareQuotation::where('status', 'converted')->count(),
        ];

        return view('admin.quotations.index', compact('quotations', 'stats'));
    }

    /**
     * Display the specified quotation
     */
    public function show(SoftwareQuotation $softwareQuotation)
    {
        $softwareQuotation->load([
            'user',
            'softwareProduct',
            'items.selectedOptions',
            'order',
            'installmentPlan'
        ]);

        return view('admin.quotations.show', compact('softwareQuotation'));
    }

    /**
     * Show the form for editing the specified quotation
     */
    public function edit(SoftwareQuotation $softwareQuotation)
    {
        $softwareQuotation->load(['items.selectedOptions']);
        return view('admin.quotations.edit', compact('softwareQuotation'));
    }

    /**
     * Update the specified quotation
     */
    public function update(Request $request, SoftwareQuotation $softwareQuotation)
    {
        $validated = $request->validate([
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'admin_notes' => 'nullable|string',
            'valid_until' => 'nullable|date',
        ]);

        // Recalculate if discount or tax changed
        if (isset($validated['discount_percentage']) || isset($validated['tax_rate'])) {
            if (isset($validated['discount_percentage'])) {
                $softwareQuotation->discount_percentage = $validated['discount_percentage'];
                $softwareQuotation->discount_amount = $softwareQuotation->subtotal * ($validated['discount_percentage'] / 100);
            }
            if (isset($validated['tax_rate'])) {
                $softwareQuotation->tax_rate = $validated['tax_rate'];
            }

            $amountAfterDiscount = $softwareQuotation->subtotal - $softwareQuotation->discount_amount;
            $softwareQuotation->tax_amount = $amountAfterDiscount * ($softwareQuotation->tax_rate / 100);
            $softwareQuotation->total_amount = $amountAfterDiscount + $softwareQuotation->tax_amount;
        }

        if (isset($validated['admin_notes'])) {
            $softwareQuotation->admin_notes = $validated['admin_notes'];
        }

        if (isset($validated['valid_until'])) {
            $softwareQuotation->valid_until = $validated['valid_until'];
        }

        $softwareQuotation->save();

        return redirect()
            ->route('admin.software-quotations.show', $softwareQuotation)
            ->with('success', 'อัพเดทใบเสนอราคาเรียบร้อยแล้ว');
    }

    /**
     * Send quotation to customer via email
     */
    public function send(SoftwareQuotation $softwareQuotation)
    {
        if ($softwareQuotation->status === 'draft') {
            $softwareQuotation->status = 'reviewed';
            $softwareQuotation->save();
        }

        try {
            Mail::to($softwareQuotation->customer_email)
                ->send(new QuotationMail($softwareQuotation));

            $softwareQuotation->markAsSent();

            return back()->with('success', 'ส่งใบเสนอราคาทางอีเมลเรียบร้อยแล้ว');
        } catch (\Exception $e) {
            return back()->with('error', 'ไม่สามารถส่งอีเมลได้: ' . $e->getMessage());
        }
    }

    /**
     * Approve quotation
     */
    public function approve(Request $request, SoftwareQuotation $softwareQuotation)
    {
        if (!in_array($softwareQuotation->status, ['pending', 'draft'])) {
            return back()->with('error', 'ไม่สามารถอนุมัติใบเสนอราคานี้ได้');
        }

        $softwareQuotation->status = 'reviewed';

        if ($request->boolean('send_email')) {
            $softwareQuotation->save();
            return $this->send($softwareQuotation);
        }

        $softwareQuotation->save();

        return back()->with('success', 'อนุมัติใบเสนอราคาเรียบร้อยแล้ว');
    }

    /**
     * Reject quotation
     */
    public function reject(Request $request, SoftwareQuotation $softwareQuotation)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        $softwareQuotation->status = 'rejected';
        if (isset($validated['reason'])) {
            $softwareQuotation->admin_notes = $validated['reason'];
        }
        $softwareQuotation->rejected_at = now();
        $softwareQuotation->save();

        return back()->with('success', 'ปฏิเสธใบเสนอราคาเรียบร้อยแล้ว');
    }

    /**
     * Remove the specified quotation
     */
    public function destroy(SoftwareQuotation $softwareQuotation)
    {
        if ($softwareQuotation->order_id) {
            return back()->with('error', 'ไม่สามารถลบใบเสนอราคาที่แปลงเป็นคำสั่งซื้อแล้วได้');
        }

        $softwareQuotation->delete();

        return redirect()
            ->route('admin.software-quotations.index')
            ->with('success', 'ลบใบเสนอราคาเรียบร้อยแล้ว');
    }
}
