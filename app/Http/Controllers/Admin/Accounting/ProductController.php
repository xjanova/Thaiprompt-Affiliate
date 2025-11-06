<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountingProduct;
use App\Models\AccountingChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'check.permission:accounting.view_products']);
    }

    /**
     * Display a listing of products
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = AccountingProduct::where('user_id', $user->id)
            ->with(['incomeAccount', 'expenseAccount']);

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $products = $query->latest()->paginate(15);

        $stats = [
            'total' => AccountingProduct::where('user_id', $user->id)->count(),
            'products' => AccountingProduct::where('user_id', $user->id)->where('type', 'product')->count(),
            'services' => AccountingProduct::where('user_id', $user->id)->where('type', 'service')->count(),
            'active' => AccountingProduct::where('user_id', $user->id)->where('is_active', true)->count(),
        ];

        return view('admin.accounting.products.index', compact('products', 'stats'));
    }

    /**
     * Show the form for creating a new product
     */
    public function create()
    {
        $user = Auth::user();

        $incomeAccounts = AccountingChartOfAccount::where('user_id', $user->id)
            ->where('type', 'revenue')
            ->where('is_active', true)
            ->get();

        $expenseAccounts = AccountingChartOfAccount::where('user_id', $user->id)
            ->where('type', 'expense')
            ->where('is_active', true)
            ->get();

        return view('admin.accounting.products.create', compact('incomeAccounts', 'expenseAccounts'));
    }

    /**
     * Store a newly created product
     */
    public function store(Request $request)
    {
        $this->middleware('check.permission:accounting.create_products');

        $validated = $request->validate([
            'type' => 'required|in:product,service',
            'name' => 'required|string|max:255',
            'name_eng' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'income_account_id' => 'nullable|exists:accounting_chart_of_accounts,id',
            'expense_account_id' => 'nullable|exists:accounting_chart_of_accounts,id',
            'is_taxable' => 'boolean',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        $user = Auth::user();

        $product = AccountingProduct::create(array_merge($validated, [
            'user_id' => $user->id,
            'tax_rate' => $validated['tax_rate'] ?? 7,
        ]));

        return redirect()->route('admin.accounting.products.show', $product)
            ->with('success', 'สร้างสินค้า/บริการเรียบร้อยแล้ว');
    }

    /**
     * Display the specified product
     */
    public function show(AccountingProduct $product)
    {
        $this->authorize('view', $product);

        $product->load(['incomeAccount', 'expenseAccount']);

        return view('admin.accounting.products.show', compact('product'));
    }

    /**
     * Show the form for editing the product
     */
    public function edit(AccountingProduct $product)
    {
        $this->middleware('check.permission:accounting.edit_products');
        $this->authorize('update', $product);

        $user = Auth::user();

        $incomeAccounts = AccountingChartOfAccount::where('user_id', $user->id)
            ->where('type', 'revenue')
            ->where('is_active', true)
            ->get();

        $expenseAccounts = AccountingChartOfAccount::where('user_id', $user->id)
            ->where('type', 'expense')
            ->where('is_active', true)
            ->get();

        return view('admin.accounting.products.edit', compact('product', 'incomeAccounts', 'expenseAccounts'));
    }

    /**
     * Update the specified product
     */
    public function update(Request $request, AccountingProduct $product)
    {
        $this->middleware('check.permission:accounting.edit_products');
        $this->authorize('update', $product);

        $validated = $request->validate([
            'type' => 'required|in:product,service',
            'name' => 'required|string|max:255',
            'name_eng' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'income_account_id' => 'nullable|exists:accounting_chart_of_accounts,id',
            'expense_account_id' => 'nullable|exists:accounting_chart_of_accounts,id',
            'is_taxable' => 'boolean',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        $product->update($validated);

        return redirect()->route('admin.accounting.products.show', $product)
            ->with('success', 'อัปเดตสินค้า/บริการเรียบร้อยแล้ว');
    }

    /**
     * Remove the specified product
     */
    public function destroy(AccountingProduct $product)
    {
        $this->middleware('check.permission:accounting.delete_products');
        $this->authorize('delete', $product);

        try {
            $product->delete();
            return redirect()->route('admin.accounting.products.index')
                ->with('success', 'ลบสินค้า/บริการเรียบร้อยแล้ว');
        } catch (\Exception $e) {
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Bulk import products
     */
    public function bulkImport(Request $request)
    {
        $this->middleware('check.permission:accounting.create_products');

        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:5120',
        ]);

        // TODO: Implement bulk import
        return back()->with('info', 'ฟีเจอร์ Import CSV กำลังพัฒนา');
    }
}
