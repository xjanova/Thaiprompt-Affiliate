<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountingInvoice;
use App\Models\AccountingExpense;
use App\Models\AccountingContact;
use App\Models\AccountingProduct;
use App\Models\AccountingSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountingDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'check.permission:accounting.view_dashboard']);
    }

    /**
     * Display accounting dashboard
     */
    public function index()
    {
        $user = Auth::user();

        // Check if accounting is enabled
        $settings = AccountingSetting::firstOrCreate(
            ['user_id' => $user->id],
            ['is_enabled' => true]
        );

        if (!$settings->is_enabled) {
            return redirect()->route('admin.accounting.setup')->with('info', 'กรุณาตั้งค่าระบบบัญชีก่อนใช้งาน');
        }

        // Get statistics
        $stats = [
            'total_invoices' => AccountingInvoice::where('user_id', $user->id)->count(),
            'pending_invoices' => AccountingInvoice::where('user_id', $user->id)->whereIn('status', ['pending', 'overdue'])->count(),
            'total_expenses' => AccountingExpense::where('user_id', $user->id)->count(),
            'total_contacts' => AccountingContact::where('user_id', $user->id)->count(),
            'total_products' => AccountingProduct::where('user_id', $user->id)->count(),
        ];

        // Get financial summary
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $financial = [
            'monthly_revenue' => AccountingInvoice::where('user_id', $user->id)
                ->where('status', 'paid')
                ->whereMonth('document_date', $currentMonth)
                ->whereYear('document_date', $currentYear)
                ->sum('total_amount'),
            'monthly_expenses' => AccountingExpense::where('user_id', $user->id)
                ->where('status', 'paid')
                ->whereMonth('document_date', $currentMonth)
                ->whereYear('document_date', $currentYear)
                ->sum('total_amount'),
            'outstanding_receivables' => AccountingInvoice::where('user_id', $user->id)
                ->whereIn('status', ['pending', 'partial', 'overdue'])
                ->sum('balance'),
            'outstanding_payables' => AccountingExpense::where('user_id', $user->id)
                ->whereIn('status', ['pending', 'partial'])
                ->sum('balance'),
        ];

        $financial['monthly_profit'] = $financial['monthly_revenue'] - $financial['monthly_expenses'];

        // Get recent invoices
        $recentInvoices = AccountingInvoice::where('user_id', $user->id)
            ->with(['contact', 'company'])
            ->latest()
            ->limit(5)
            ->get();

        // Get recent expenses
        $recentExpenses = AccountingExpense::where('user_id', $user->id)
            ->with(['contact', 'company'])
            ->latest()
            ->limit(5)
            ->get();

        // Get overdue invoices
        $overdueInvoices = AccountingInvoice::where('user_id', $user->id)
            ->where('status', 'overdue')
            ->with('contact')
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.accounting.dashboard', compact(
            'stats',
            'financial',
            'recentInvoices',
            'recentExpenses',
            'overdueInvoices',
            'settings'
        ));
    }

    /**
     * Display setup page
     */
    public function setup()
    {
        $user = Auth::user();
        $settings = AccountingSetting::firstOrCreate(
            ['user_id' => $user->id],
            ['is_enabled' => false]
        );

        return view('admin.accounting.setup', compact('settings'));
    }

    /**
     * Save setup
     */
    public function saveSetup(Request $request)
    {
        $validated = $request->validate([
            'currency' => 'required|string|size:3',
            'date_format' => 'required|string',
            'timezone' => 'required|string',
            'fiscal_year_start' => 'required|string',
        ]);

        $user = Auth::user();
        $settings = AccountingSetting::updateOrCreate(
            ['user_id' => $user->id],
            array_merge($validated, ['is_enabled' => true])
        );

        // Create default company if not exists
        if (!$user->accountingCompanies()->exists()) {
            $user->accountingCompanies()->create([
                'name' => $user->name,
                'email' => $user->email,
                'is_default' => true,
            ]);
        }

        return redirect()->route('admin.accounting.dashboard')
            ->with('success', 'ตั้งค่าระบบบัญชีเรียบร้อยแล้ว');
    }
}
