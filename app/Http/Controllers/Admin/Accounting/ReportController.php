<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountingInvoice;
use App\Models\AccountingExpense;
use App\Models\AccountingChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'check.permission:accounting.view_reports']);
    }

    /**
     * Display reports index
     */
    public function index()
    {
        return view('admin.accounting.reports.index');
    }

    /**
     * Profit & Loss Report
     */
    public function profitLoss(Request $request)
    {
        $user = Auth::user();

        $fromDate = $request->get('from_date', now()->startOfMonth()->format('Y-m-d'));
        $toDate = $request->get('to_date', now()->endOfMonth()->format('Y-m-d'));

        // Revenue
        $totalRevenue = AccountingInvoice::where('user_id', $user->id)
            ->where('status', 'paid')
            ->whereBetween('document_date', [$fromDate, $toDate])
            ->sum('total_amount');

        $revenueByType = AccountingInvoice::where('user_id', $user->id)
            ->where('status', 'paid')
            ->whereBetween('document_date', [$fromDate, $toDate])
            ->select('document_type', DB::raw('SUM(total_amount) as total'))
            ->groupBy('document_type')
            ->get();

        // Expenses
        $totalExpenses = AccountingExpense::where('user_id', $user->id)
            ->where('status', 'paid')
            ->whereBetween('document_date', [$fromDate, $toDate])
            ->sum('total_amount');

        $expensesByCategory = AccountingExpense::where('user_id', $user->id)
            ->where('status', 'paid')
            ->whereBetween('document_date', [$fromDate, $toDate])
            ->with('category')
            ->select('category_id', DB::raw('SUM(total_amount) as total'))
            ->groupBy('category_id')
            ->get();

        // Calculate profit
        $grossProfit = $totalRevenue - $totalExpenses;
        $profitMargin = $totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0;

        $data = [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'total_revenue' => $totalRevenue,
            'revenue_by_type' => $revenueByType,
            'total_expenses' => $totalExpenses,
            'expenses_by_category' => $expensesByCategory,
            'gross_profit' => $grossProfit,
            'profit_margin' => $profitMargin,
        ];

        return view('admin.accounting.reports.profit-loss', $data);
    }

    /**
     * Balance Sheet Report
     */
    public function balanceSheet(Request $request)
    {
        $user = Auth::user();

        $asOfDate = $request->get('as_of_date', now()->format('Y-m-d'));

        // Assets
        $assets = AccountingChartOfAccount::where('user_id', $user->id)
            ->where('type', 'asset')
            ->where('is_active', true)
            ->get()
            ->groupBy('sub_type');

        $totalAssets = AccountingChartOfAccount::where('user_id', $user->id)
            ->where('type', 'asset')
            ->where('is_active', true)
            ->sum('current_balance');

        // Liabilities
        $liabilities = AccountingChartOfAccount::where('user_id', $user->id)
            ->where('type', 'liability')
            ->where('is_active', true)
            ->get()
            ->groupBy('sub_type');

        $totalLiabilities = AccountingChartOfAccount::where('user_id', $user->id)
            ->where('type', 'liability')
            ->where('is_active', true)
            ->sum('current_balance');

        // Equity
        $equity = AccountingChartOfAccount::where('user_id', $user->id)
            ->where('type', 'equity')
            ->where('is_active', true)
            ->get();

        $totalEquity = AccountingChartOfAccount::where('user_id', $user->id)
            ->where('type', 'equity')
            ->where('is_active', true)
            ->sum('current_balance');

        $data = [
            'as_of_date' => $asOfDate,
            'assets' => $assets,
            'total_assets' => $totalAssets,
            'liabilities' => $liabilities,
            'total_liabilities' => $totalLiabilities,
            'equity' => $equity,
            'total_equity' => $totalEquity,
        ];

        return view('admin.accounting.reports.balance-sheet', $data);
    }

    /**
     * Cash Flow Report
     */
    public function cashFlow(Request $request)
    {
        $user = Auth::user();

        $fromDate = $request->get('from_date', now()->startOfMonth()->format('Y-m-d'));
        $toDate = $request->get('to_date', now()->endOfMonth()->format('Y-m-d'));

        // Cash inflows (paid invoices)
        $cashInflows = AccountingInvoice::where('user_id', $user->id)
            ->where('status', 'paid')
            ->whereBetween('document_date', [$fromDate, $toDate])
            ->select(
                DB::raw('DATE(document_date) as date'),
                DB::raw('SUM(paid_amount) as amount')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $totalInflows = $cashInflows->sum('amount');

        // Cash outflows (paid expenses)
        $cashOutflows = AccountingExpense::where('user_id', $user->id)
            ->where('status', 'paid')
            ->whereBetween('document_date', [$fromDate, $toDate])
            ->select(
                DB::raw('DATE(document_date) as date'),
                DB::raw('SUM(paid_amount) as amount')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $totalOutflows = $cashOutflows->sum('amount');

        // Net cash flow
        $netCashFlow = $totalInflows - $totalOutflows;

        $data = [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'cash_inflows' => $cashInflows,
            'total_inflows' => $totalInflows,
            'cash_outflows' => $cashOutflows,
            'total_outflows' => $totalOutflows,
            'net_cash_flow' => $netCashFlow,
        ];

        return view('admin.accounting.reports.cash-flow', $data);
    }

    /**
     * Sales Report
     */
    public function sales(Request $request)
    {
        $user = Auth::user();

        $fromDate = $request->get('from_date', now()->startOfMonth()->format('Y-m-d'));
        $toDate = $request->get('to_date', now()->endOfMonth()->format('Y-m-d'));

        $invoices = AccountingInvoice::where('user_id', $user->id)
            ->whereBetween('document_date', [$fromDate, $toDate])
            ->with(['contact', 'items.product'])
            ->get();

        // Sales by customer
        $salesByCustomer = $invoices->groupBy('contact_id')->map(function ($group) {
            return [
                'contact' => $group->first()->contact,
                'total_amount' => $group->sum('total_amount'),
                'count' => $group->count(),
            ];
        })->sortByDesc('total_amount');

        // Sales by product
        $salesByProduct = $invoices->flatMap(function ($invoice) {
            return $invoice->items;
        })->groupBy('product_id')->map(function ($group) {
            return [
                'product' => $group->first()->product,
                'quantity' => $group->sum('quantity'),
                'total_amount' => $group->sum('total_amount'),
            ];
        })->sortByDesc('total_amount');

        $data = [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'invoices' => $invoices,
            'sales_by_customer' => $salesByCustomer,
            'sales_by_product' => $salesByProduct,
            'total_sales' => $invoices->sum('total_amount'),
        ];

        return view('admin.accounting.reports.sales', $data);
    }

    /**
     * Expenses Report
     */
    public function expenses(Request $request)
    {
        $user = Auth::user();

        $fromDate = $request->get('from_date', now()->startOfMonth()->format('Y-m-d'));
        $toDate = $request->get('to_date', now()->endOfMonth()->format('Y-m-d'));

        $expenses = AccountingExpense::where('user_id', $user->id)
            ->whereBetween('document_date', [$fromDate, $toDate])
            ->with(['contact', 'category', 'items'])
            ->get();

        // Expenses by category
        $expensesByCategory = $expenses->groupBy('category_id')->map(function ($group) {
            return [
                'category' => $group->first()->category,
                'total_amount' => $group->sum('total_amount'),
                'count' => $group->count(),
            ];
        })->sortByDesc('total_amount');

        // Expenses by vendor
        $expensesByVendor = $expenses->groupBy('contact_id')->map(function ($group) {
            return [
                'contact' => $group->first()->contact,
                'total_amount' => $group->sum('total_amount'),
                'count' => $group->count(),
            ];
        })->sortByDesc('total_amount');

        $data = [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'expenses' => $expenses,
            'expenses_by_category' => $expensesByCategory,
            'expenses_by_vendor' => $expensesByVendor,
            'total_expenses' => $expenses->sum('total_amount'),
        ];

        return view('admin.accounting.reports.expenses', $data);
    }

    /**
     * Tax Report
     */
    public function tax(Request $request)
    {
        $user = Auth::user();

        $fromDate = $request->get('from_date', now()->startOfMonth()->format('Y-m-d'));
        $toDate = $request->get('to_date', now()->endOfMonth()->format('Y-m-d'));

        // Output VAT (from invoices)
        $outputVat = AccountingInvoice::where('user_id', $user->id)
            ->whereBetween('document_date', [$fromDate, $toDate])
            ->sum('tax_amount');

        // Input VAT (from expenses)
        $inputVat = AccountingExpense::where('user_id', $user->id)
            ->whereBetween('document_date', [$fromDate, $toDate])
            ->sum('tax_amount');

        // Net VAT
        $netVat = $outputVat - $inputVat;

        $data = [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'output_vat' => $outputVat,
            'input_vat' => $inputVat,
            'net_vat' => $netVat,
        ];

        return view('admin.accounting.reports.tax', $data);
    }
}
