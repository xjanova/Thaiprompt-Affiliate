<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountingContact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'check.permission:accounting.view_contacts']);
    }

    /**
     * Display a listing of contacts
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = AccountingContact::where('user_id', $user->id);

        // Filter by type
        if ($request->filled('type')) {
            if ($request->type === 'customer') {
                $query->whereIn('type', ['customer', 'both']);
            } elseif ($request->type === 'vendor') {
                $query->whereIn('type', ['vendor', 'both']);
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $contacts = $query->latest()->paginate(15);

        $stats = [
            'total' => AccountingContact::where('user_id', $user->id)->count(),
            'customers' => AccountingContact::where('user_id', $user->id)->whereIn('type', ['customer', 'both'])->count(),
            'vendors' => AccountingContact::where('user_id', $user->id)->whereIn('type', ['vendor', 'both'])->count(),
            'active' => AccountingContact::where('user_id', $user->id)->where('is_active', true)->count(),
        ];

        return view('admin.accounting.contacts.index', compact('contacts', 'stats'));
    }

    /**
     * Show the form for creating a new contact
     */
    public function create()
    {
        return view('admin.accounting.contacts.create');
    }

    /**
     * Store a newly created contact
     */
    public function store(Request $request)
    {
        $this->middleware('check.permission:accounting.create_contacts');

        $validated = $request->validate([
            'type' => 'required|in:customer,vendor,both',
            'name' => 'required|string|max:255',
            'name_eng' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:20',
            'branch_code' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string',
            'district' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
            'credit_days' => 'nullable|integer|min:0',
            'payment_terms' => 'required|in:cash,credit,cod',
            'note' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $user = Auth::user();

        $contact = AccountingContact::create(array_merge($validated, [
            'user_id' => $user->id,
        ]));

        return redirect()->route('admin.accounting.contacts.show', $contact)
            ->with('success', 'สร้างผู้ติดต่อเรียบร้อยแล้ว');
    }

    /**
     * Display the specified contact
     */
    public function show(AccountingContact $contact)
    {
        $this->authorize('view', $contact);

        // Get statistics
        $stats = [
            'total_invoices' => $contact->invoices()->count(),
            'total_expenses' => $contact->expenses()->count(),
            'outstanding_receivables' => $contact->invoices()->whereIn('status', ['pending', 'partial', 'overdue'])->sum('balance'),
            'outstanding_payables' => $contact->expenses()->whereIn('status', ['pending', 'partial'])->sum('balance'),
        ];

        // Recent transactions
        $recentInvoices = $contact->invoices()->latest()->limit(5)->get();
        $recentExpenses = $contact->expenses()->latest()->limit(5)->get();

        return view('admin.accounting.contacts.show', compact('contact', 'stats', 'recentInvoices', 'recentExpenses'));
    }

    /**
     * Show the form for editing the contact
     */
    public function edit(AccountingContact $contact)
    {
        $this->middleware('check.permission:accounting.edit_contacts');
        $this->authorize('update', $contact);

        return view('admin.accounting.contacts.edit', compact('contact'));
    }

    /**
     * Update the specified contact
     */
    public function update(Request $request, AccountingContact $contact)
    {
        $this->middleware('check.permission:accounting.edit_contacts');
        $this->authorize('update', $contact);

        $validated = $request->validate([
            'type' => 'required|in:customer,vendor,both',
            'name' => 'required|string|max:255',
            'name_eng' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:20',
            'branch_code' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string',
            'district' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
            'credit_days' => 'nullable|integer|min:0',
            'payment_terms' => 'required|in:cash,credit,cod',
            'note' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $contact->update($validated);

        return redirect()->route('admin.accounting.contacts.show', $contact)
            ->with('success', 'อัปเดตผู้ติดต่อเรียบร้อยแล้ว');
    }

    /**
     * Remove the specified contact
     */
    public function destroy(AccountingContact $contact)
    {
        $this->middleware('check.permission:accounting.delete_contacts');
        $this->authorize('delete', $contact);

        // Check if contact has any transactions
        if ($contact->invoices()->count() > 0 || $contact->expenses()->count() > 0) {
            return back()->with('error', 'ไม่สามารถลบผู้ติดต่อที่มีรายการได้ กรุณาตั้งค่าเป็นไม่ใช้งานแทน');
        }

        try {
            $contact->delete();
            return redirect()->route('admin.accounting.contacts.index')
                ->with('success', 'ลบผู้ติดต่อเรียบร้อยแล้ว');
        } catch (\Exception $e) {
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Show contact statement
     */
    public function statement(Request $request, AccountingContact $contact)
    {
        $this->authorize('view', $contact);

        $fromDate = $request->get('from_date', now()->startOfMonth()->format('Y-m-d'));
        $toDate = $request->get('to_date', now()->endOfMonth()->format('Y-m-d'));

        // Get transactions
        $invoices = $contact->invoices()
            ->whereBetween('document_date', [$fromDate, $toDate])
            ->orderBy('document_date')
            ->get();

        $expenses = $contact->expenses()
            ->whereBetween('document_date', [$fromDate, $toDate])
            ->orderBy('document_date')
            ->get();

        return view('admin.accounting.contacts.statement', compact('contact', 'invoices', 'expenses', 'fromDate', 'toDate'));
    }
}
