<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountingExpense;
use App\Models\AccountingExpenseItem;
use App\Models\AccountingContact;
use App\Models\AccountingProduct;
use App\Models\AccountingCompany;
use App\Models\AccountingChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'check.permission:accounting.view_expenses']);
    }

    /**
     * Display a listing of expenses
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = AccountingExpense::where('user_id', $user->id)
            ->with(['contact', 'company', 'category']);

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('document_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('document_date', '<=', $request->to_date);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('document_number', 'like', "%{$search}%")
                  ->orWhereHas('contact', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $expenses = $query->latest()->paginate(15);

        $stats = [
            'total' => AccountingExpense::where('user_id', $user->id)->count(),
            'draft' => AccountingExpense::where('user_id', $user->id)->where('status', 'draft')->count(),
            'pending' => AccountingExpense::where('user_id', $user->id)->where('status', 'pending')->count(),
            'paid' => AccountingExpense::where('user_id', $user->id)->where('status', 'paid')->count(),
        ];

        return view('admin.accounting.expenses.index', compact('expenses', 'stats'));
    }

    /**
     * Show the form for creating a new expense
     */
    public function create()
    {
        $user = Auth::user();

        $contacts = AccountingContact::where('user_id', $user->id)
            ->whereIn('type', ['vendor', 'both'])
            ->where('is_active', true)
            ->get();

        $products = AccountingProduct::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        $companies = AccountingCompany::where('user_id', $user->id)->get();

        $categories = AccountingChartOfAccount::where('user_id', $user->id)
            ->where('type', 'expense')
            ->where('is_active', true)
            ->get();

        $defaultCompany = $companies->where('is_default', true)->first() ?? $companies->first();

        return view('admin.accounting.expenses.create', compact('contacts', 'products', 'companies', 'categories', 'defaultCompany'));
    }

    /**
     * Store a newly created expense
     */
    public function store(Request $request)
    {
        $this->middleware('check.permission:accounting.create_expenses');

        $validated = $request->validate([
            'contact_id' => 'nullable|exists:accounting_contacts,id',
            'company_id' => 'nullable|exists:accounting_companies,id',
            'category_id' => 'nullable|exists:accounting_chart_of_accounts,id',
            'document_type' => 'required|in:expense,purchase,bill',
            'document_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:document_date',
            'reference_number' => 'nullable|string|max:255',
            'note' => 'nullable|string',
            'receipt_image' => 'nullable|image|max:5120',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:accounting_products,id',
            'items.*.chart_account_id' => 'nullable|exists:accounting_chart_of_accounts,id',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            DB::beginTransaction();

            $user = Auth::user();

            // Handle receipt image upload
            $receiptPath = null;
            if ($request->hasFile('receipt_image')) {
                $receiptPath = $request->file('receipt_image')->store('accounting/receipts', 'public');
            }

            // Create expense
            $expense = AccountingExpense::create([
                'user_id' => $user->id,
                'company_id' => $validated['company_id'],
                'contact_id' => $validated['contact_id'],
                'category_id' => $validated['category_id'],
                'document_type' => $validated['document_type'],
                'document_date' => $validated['document_date'],
                'due_date' => $validated['due_date'],
                'reference_number' => $validated['reference_number'] ?? null,
                'note' => $validated['note'] ?? null,
                'receipt_image' => $receiptPath,
                'status' => 'draft',
            ]);

            // Create expense items
            foreach ($validated['items'] as $index => $itemData) {
                AccountingExpenseItem::create([
                    'expense_id' => $expense->id,
                    'product_id' => $itemData['product_id'] ?? null,
                    'chart_account_id' => $itemData['chart_account_id'] ?? null,
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'],
                    'unit_price' => $itemData['unit_price'],
                    'tax_rate' => $itemData['tax_rate'] ?? 7,
                    'sort_order' => $index,
                ]);
            }

            // Recalculate totals
            $expense->fresh();
            $expense->calculateTotals();
            $expense->save();

            DB::commit();

            return redirect()->route('admin.accounting.expenses.show', $expense)
                ->with('success', 'สร้างรายจ่ายเรียบร้อยแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified expense
     */
    public function show(AccountingExpense $expense)
    {
        $this->authorize('view', $expense);

        $expense->load(['contact', 'company', 'category', 'items.product', 'items.chartAccount', 'payments']);

        return view('admin.accounting.expenses.show', compact('expense'));
    }

    /**
     * Show the form for editing the expense
     */
    public function edit(AccountingExpense $expense)
    {
        $this->middleware('check.permission:accounting.edit_expenses');
        $this->authorize('update', $expense);

        $user = Auth::user();

        $contacts = AccountingContact::where('user_id', $user->id)
            ->whereIn('type', ['vendor', 'both'])
            ->where('is_active', true)
            ->get();

        $products = AccountingProduct::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        $companies = AccountingCompany::where('user_id', $user->id)->get();

        $categories = AccountingChartOfAccount::where('user_id', $user->id)
            ->where('type', 'expense')
            ->where('is_active', true)
            ->get();

        $expense->load(['items']);

        return view('admin.accounting.expenses.edit', compact('expense', 'contacts', 'products', 'companies', 'categories'));
    }

    /**
     * Update the specified expense
     */
    public function update(Request $request, AccountingExpense $expense)
    {
        $this->middleware('check.permission:accounting.edit_expenses');
        $this->authorize('update', $expense);

        $validated = $request->validate([
            'contact_id' => 'nullable|exists:accounting_contacts,id',
            'company_id' => 'nullable|exists:accounting_companies,id',
            'category_id' => 'nullable|exists:accounting_chart_of_accounts,id',
            'document_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:document_date',
            'reference_number' => 'nullable|string|max:255',
            'note' => 'nullable|string',
            'receipt_image' => 'nullable|image|max:5120',
            'status' => 'required|in:draft,pending,paid,partial,cancelled',
            'items' => 'required|array|min:1',
        ]);

        try {
            DB::beginTransaction();

            // Handle receipt image upload
            if ($request->hasFile('receipt_image')) {
                // Delete old receipt if exists
                if ($expense->receipt_image) {
                    \Storage::disk('public')->delete($expense->receipt_image);
                }
                $receiptPath = $request->file('receipt_image')->store('accounting/receipts', 'public');
                $validated['receipt_image'] = $receiptPath;
            }

            // Update expense
            $expense->update($validated);

            // Update items (similar to invoice)
            $itemIds = collect($validated['items'])->pluck('id')->filter();
            $expense->items()->whereNotIn('id', $itemIds)->delete();

            foreach ($validated['items'] as $index => $itemData) {
                if (isset($itemData['id'])) {
                    AccountingExpenseItem::where('id', $itemData['id'])->update([
                        'product_id' => $itemData['product_id'] ?? null,
                        'chart_account_id' => $itemData['chart_account_id'] ?? null,
                        'description' => $itemData['description'],
                        'quantity' => $itemData['quantity'],
                        'unit' => $itemData['unit'],
                        'unit_price' => $itemData['unit_price'],
                        'tax_rate' => $itemData['tax_rate'] ?? 7,
                        'sort_order' => $index,
                    ]);
                } else {
                    AccountingExpenseItem::create([
                        'expense_id' => $expense->id,
                        'product_id' => $itemData['product_id'] ?? null,
                        'chart_account_id' => $itemData['chart_account_id'] ?? null,
                        'description' => $itemData['description'],
                        'quantity' => $itemData['quantity'],
                        'unit' => $itemData['unit'],
                        'unit_price' => $itemData['unit_price'],
                        'tax_rate' => $itemData['tax_rate'] ?? 7,
                        'sort_order' => $index,
                    ]);
                }
            }

            $expense->fresh();
            $expense->calculateTotals();
            $expense->save();

            DB::commit();

            return redirect()->route('admin.accounting.expenses.show', $expense)
                ->with('success', 'อัปเดตรายจ่ายเรียบร้อยแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified expense
     */
    public function destroy(AccountingExpense $expense)
    {
        $this->middleware('check.permission:accounting.delete_expenses');
        $this->authorize('delete', $expense);

        try {
            if ($expense->receipt_image) {
                \Storage::disk('public')->delete($expense->receipt_image);
            }

            $expense->delete();

            return redirect()->route('admin.accounting.expenses.index')
                ->with('success', 'ลบรายจ่ายเรียบร้อยแล้ว');
        } catch (\Exception $e) {
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Record payment for expense
     */
    public function recordPayment(Request $request, AccountingExpense $expense)
    {
        $this->middleware('check.permission:accounting.create_payments');
        $this->authorize('update', $expense);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $expense->balance,
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,credit_card,cheque,other',
            'bank_account_id' => 'nullable|exists:accounting_bank_accounts,id',
            'reference' => 'nullable|string|max:255',
            'note' => 'nullable|string',
        ]);

        try {
            $payment = $expense->recordPayment($validated['amount'], [
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'bank_account_id' => $validated['bank_account_id'] ?? null,
                'reference' => $validated['reference'] ?? null,
                'note' => $validated['note'] ?? null,
            ]);

            return back()->with('success', 'บันทึกการจ่ายเงินเรียบร้อยแล้ว');
        } catch (\Exception $e) {
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }
}
