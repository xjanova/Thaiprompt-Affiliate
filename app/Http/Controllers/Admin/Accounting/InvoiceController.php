<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountingInvoice;
use App\Models\AccountingInvoiceItem;
use App\Models\AccountingContact;
use App\Models\AccountingProduct;
use App\Models\AccountingCompany;
use App\Services\FlowAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    protected $flowAccountService;

    public function __construct(FlowAccountService $flowAccountService)
    {
        $this->middleware(['auth', 'check.permission:accounting.view_invoices']);
        $this->flowAccountService = $flowAccountService;
    }

    /**
     * Display a listing of invoices
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = AccountingInvoice::where('user_id', $user->id)
            ->with(['contact', 'company']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('document_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('document_date', '<=', $request->to_date);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('document_number', 'like', "%{$search}%")
                  ->orWhereHas('contact', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $invoices = $query->latest()->paginate(15);

        // Statistics
        $stats = [
            'total' => AccountingInvoice::where('user_id', $user->id)->count(),
            'draft' => AccountingInvoice::where('user_id', $user->id)->where('status', 'draft')->count(),
            'pending' => AccountingInvoice::where('user_id', $user->id)->where('status', 'pending')->count(),
            'paid' => AccountingInvoice::where('user_id', $user->id)->where('status', 'paid')->count(),
            'overdue' => AccountingInvoice::where('user_id', $user->id)->where('status', 'overdue')->count(),
        ];

        return view('admin.accounting.invoices.index', compact('invoices', 'stats'));
    }

    /**
     * Show the form for creating a new invoice
     */
    public function create()
    {
        $user = Auth::user();

        $contacts = AccountingContact::where('user_id', $user->id)
            ->where('type', 'customer')
            ->where('is_active', true)
            ->get();

        $products = AccountingProduct::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        $companies = AccountingCompany::where('user_id', $user->id)->get();

        $defaultCompany = $companies->where('is_default', true)->first() ?? $companies->first();

        return view('admin.accounting.invoices.create', compact('contacts', 'products', 'companies', 'defaultCompany'));
    }

    /**
     * Store a newly created invoice
     */
    public function store(Request $request)
    {
        $this->middleware('check.permission:accounting.create_invoices');

        $validated = $request->validate([
            'contact_id' => 'required|exists:accounting_contacts,id',
            'company_id' => 'nullable|exists:accounting_companies,id',
            'document_type' => 'required|in:invoice,tax_invoice,receipt,quotation',
            'document_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:document_date',
            'reference_number' => 'nullable|string|max:255',
            'discount_amount' => 'nullable|numeric|min:0',
            'note' => 'nullable|string',
            'terms' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:accounting_products,id',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            DB::beginTransaction();

            $user = Auth::user();

            // Create invoice
            $invoice = AccountingInvoice::create([
                'user_id' => $user->id,
                'company_id' => $validated['company_id'],
                'contact_id' => $validated['contact_id'],
                'document_type' => $validated['document_type'],
                'document_date' => $validated['document_date'],
                'due_date' => $validated['due_date'],
                'reference_number' => $validated['reference_number'] ?? null,
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'note' => $validated['note'] ?? null,
                'terms' => $validated['terms'] ?? null,
                'status' => 'draft',
            ]);

            // Create invoice items
            foreach ($validated['items'] as $index => $itemData) {
                AccountingInvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $itemData['product_id'] ?? null,
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'],
                    'unit_price' => $itemData['unit_price'],
                    'discount_amount' => $itemData['discount_amount'] ?? 0,
                    'tax_rate' => $itemData['tax_rate'] ?? 7,
                    'sort_order' => $index,
                ]);
            }

            // Recalculate totals
            $invoice->fresh();
            $invoice->calculateTotals();
            $invoice->save();

            DB::commit();

            return redirect()->route('admin.accounting.invoices.show', $invoice)
                ->with('success', 'สร้างใบแจ้งหนี้เรียบร้อยแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified invoice
     */
    public function show(AccountingInvoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['contact', 'company', 'items.product', 'payments']);

        return view('admin.accounting.invoices.show', compact('invoice'));
    }

    /**
     * Show the form for editing the invoice
     */
    public function edit(AccountingInvoice $invoice)
    {
        $this->middleware('check.permission:accounting.edit_invoices');
        $this->authorize('update', $invoice);

        $user = Auth::user();

        $contacts = AccountingContact::where('user_id', $user->id)
            ->where('type', 'customer')
            ->where('is_active', true)
            ->get();

        $products = AccountingProduct::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        $companies = AccountingCompany::where('user_id', $user->id)->get();

        $invoice->load(['items']);

        return view('admin.accounting.invoices.edit', compact('invoice', 'contacts', 'products', 'companies'));
    }

    /**
     * Update the specified invoice
     */
    public function update(Request $request, AccountingInvoice $invoice)
    {
        $this->middleware('check.permission:accounting.edit_invoices');
        $this->authorize('update', $invoice);

        $validated = $request->validate([
            'contact_id' => 'required|exists:accounting_contacts,id',
            'company_id' => 'nullable|exists:accounting_companies,id',
            'document_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:document_date',
            'reference_number' => 'nullable|string|max:255',
            'discount_amount' => 'nullable|numeric|min:0',
            'note' => 'nullable|string',
            'terms' => 'nullable|string',
            'status' => 'required|in:draft,pending,paid,partial,overdue,cancelled',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:accounting_invoice_items,id',
            'items.*.product_id' => 'nullable|exists:accounting_products,id',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            DB::beginTransaction();

            // Update invoice
            $invoice->update([
                'company_id' => $validated['company_id'],
                'contact_id' => $validated['contact_id'],
                'document_date' => $validated['document_date'],
                'due_date' => $validated['due_date'],
                'reference_number' => $validated['reference_number'] ?? null,
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'note' => $validated['note'] ?? null,
                'terms' => $validated['terms'] ?? null,
                'status' => $validated['status'],
            ]);

            // Delete removed items
            $itemIds = collect($validated['items'])->pluck('id')->filter();
            $invoice->items()->whereNotIn('id', $itemIds)->delete();

            // Update or create items
            foreach ($validated['items'] as $index => $itemData) {
                if (isset($itemData['id'])) {
                    // Update existing item
                    AccountingInvoiceItem::where('id', $itemData['id'])->update([
                        'product_id' => $itemData['product_id'] ?? null,
                        'description' => $itemData['description'],
                        'quantity' => $itemData['quantity'],
                        'unit' => $itemData['unit'],
                        'unit_price' => $itemData['unit_price'],
                        'discount_amount' => $itemData['discount_amount'] ?? 0,
                        'tax_rate' => $itemData['tax_rate'] ?? 7,
                        'sort_order' => $index,
                    ]);
                } else {
                    // Create new item
                    AccountingInvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'product_id' => $itemData['product_id'] ?? null,
                        'description' => $itemData['description'],
                        'quantity' => $itemData['quantity'],
                        'unit' => $itemData['unit'],
                        'unit_price' => $itemData['unit_price'],
                        'discount_amount' => $itemData['discount_amount'] ?? 0,
                        'tax_rate' => $itemData['tax_rate'] ?? 7,
                        'sort_order' => $index,
                    ]);
                }
            }

            // Recalculate totals
            $invoice->fresh();
            $invoice->calculateTotals();
            $invoice->save();

            DB::commit();

            return redirect()->route('admin.accounting.invoices.show', $invoice)
                ->with('success', 'อัปเดตใบแจ้งหนี้เรียบร้อยแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified invoice
     */
    public function destroy(AccountingInvoice $invoice)
    {
        $this->middleware('check.permission:accounting.delete_invoices');
        $this->authorize('delete', $invoice);

        try {
            $invoice->delete();
            return redirect()->route('admin.accounting.invoices.index')
                ->with('success', 'ลบใบแจ้งหนี้เรียบร้อยแล้ว');
        } catch (\Exception $e) {
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Record payment for invoice
     */
    public function recordPayment(Request $request, AccountingInvoice $invoice)
    {
        $this->middleware('check.permission:accounting.create_payments');
        $this->authorize('update', $invoice);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $invoice->balance,
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,credit_card,cheque,other',
            'bank_account_id' => 'nullable|exists:accounting_bank_accounts,id',
            'reference' => 'nullable|string|max:255',
            'note' => 'nullable|string',
        ]);

        try {
            $payment = $invoice->recordPayment($validated['amount'], [
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'bank_account_id' => $validated['bank_account_id'] ?? null,
                'reference' => $validated['reference'] ?? null,
                'note' => $validated['note'] ?? null,
            ]);

            return back()->with('success', 'บันทึกการชำระเงินเรียบร้อยแล้ว');
        } catch (\Exception $e) {
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Download invoice as PDF
     */
    public function downloadPdf(AccountingInvoice $invoice)
    {
        $this->authorize('view', $invoice);

        // TODO: Implement PDF generation
        return back()->with('info', 'ฟีเจอร์ Export PDF กำลังพัฒนา');
    }

    /**
     * Send invoice via email
     */
    public function sendEmail(Request $request, AccountingInvoice $invoice)
    {
        $this->authorize('view', $invoice);

        $validated = $request->validate([
            'email' => 'required|email',
            'subject' => 'required|string|max:255',
            'message' => 'nullable|string',
        ]);

        // TODO: Implement email sending
        return back()->with('info', 'ฟีเจอร์ส่ง Email กำลังพัฒนา');
    }
}
