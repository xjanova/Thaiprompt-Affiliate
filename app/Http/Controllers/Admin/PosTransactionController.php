<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PosTransaction;
use App\Models\PosDevice;
use App\Models\VendorStore;
use Illuminate\Http\Request;

class PosTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = PosTransaction::with(['posDevice', 'store', 'user', 'customer'])
            ->latest('transaction_date');

        // Filters
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_code', 'like', "%{$search}%")
                  ->orWhere('receipt_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        if ($request->has('device_id')) {
            $query->where('pos_device_id', $request->device_id);
        }

        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        if ($request->has('min_amount')) {
            $query->where('total_amount', '>=', $request->min_amount);
        }

        if ($request->has('max_amount')) {
            $query->where('total_amount', '<=', $request->max_amount);
        }

        $transactions = $query->paginate(20);
        $devices = PosDevice::active()->get();
        $stores = VendorStore::active()->get();

        $summary = [
            'total_transactions' => $query->count(),
            'total_amount' => $query->sum('total_amount'),
            'average_amount' => $query->avg('total_amount'),
        ];

        return view('admin.pos.transactions.index', compact(
            'transactions',
            'devices',
            'stores',
            'summary'
        ));
    }

    public function show(PosTransaction $transaction)
    {
        $transaction->load([
            'posDevice',
            'posSession',
            'store',
            'user',
            'customer',
            'approvedBy',
            'items.product'
        ]);

        return view('admin.pos.transactions.show', compact('transaction'));
    }

    public function refund(Request $request, PosTransaction $transaction)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        if (!$transaction->canBeRefunded()) {
            return back()->with('error', 'This transaction cannot be refunded.');
        }

        try {
            $transaction->refund($validated['reason'], auth()->id());

            // Log the refund
            activity()
                ->performedOn($transaction)
                ->withProperties([
                    'reason' => $validated['reason'],
                    'amount' => $transaction->total_amount,
                ])
                ->log('Transaction refunded');

            return back()->with('success', 'Transaction refunded successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to refund transaction: ' . $e->getMessage());
        }
    }

    public function void(Request $request, PosTransaction $transaction)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $transaction->update([
            'status' => 'void',
            'notes' => $validated['reason'],
        ]);

        // Restore stock
        foreach ($transaction->items as $item) {
            if ($item->product && $item->product->track_inventory) {
                $item->product->increment('stock_quantity', $item->quantity);
            }
        }

        activity()
            ->performedOn($transaction)
            ->withProperties(['reason' => $validated['reason']])
            ->log('Transaction voided');

        return back()->with('success', 'Transaction voided successfully!');
    }

    public function receipt(PosTransaction $transaction)
    {
        $transaction->load(['items.product', 'store', 'user']);

        return view('admin.pos.transactions.receipt', compact('transaction'));
    }

    public function printReceipt(PosTransaction $transaction)
    {
        $transaction->load(['items.product', 'store', 'user']);

        // Return a printable version
        return view('admin.pos.transactions.print-receipt', compact('transaction'))
            ->with('print', true);
    }

    public function export(Request $request)
    {
        $query = PosTransaction::with(['posDevice', 'store', 'user'])
            ->latest('transaction_date');

        // Apply filters
        if ($request->has('device_id')) {
            $query->where('pos_device_id', $request->device_id);
        }

        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        $transactions = $query->get();

        $filename = 'pos_transactions_' . now()->format('YmdHis') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');

            // Headers
            fputcsv($file, [
                'Transaction Code',
                'Receipt Number',
                'Date',
                'Device',
                'Store',
                'Cashier',
                'Customer',
                'Items',
                'Subtotal',
                'Discount',
                'Tax',
                'Total',
                'Payment Method',
                'Status',
            ]);

            // Data
            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    $transaction->transaction_code,
                    $transaction->receipt_number,
                    $transaction->transaction_date->format('Y-m-d H:i:s'),
                    $transaction->posDevice?->device_name ?? 'N/A',
                    $transaction->store?->store_name ?? 'N/A',
                    $transaction->user?->name ?? 'N/A',
                    $transaction->customer_name ?? 'Walk-in',
                    $transaction->total_items,
                    $transaction->subtotal,
                    $transaction->discount_amount,
                    $transaction->tax_amount,
                    $transaction->total_amount,
                    $transaction->payment_method,
                    $transaction->status,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function analytics(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subDays(30));
        $dateTo = $request->get('date_to', now());

        $analytics = [
            'total_transactions' => PosTransaction::whereBetween('transaction_date', [$dateFrom, $dateTo])->count(),
            'total_sales' => PosTransaction::whereBetween('transaction_date', [$dateFrom, $dateTo])->sum('total_amount'),
            'average_transaction' => PosTransaction::whereBetween('transaction_date', [$dateFrom, $dateTo])->avg('total_amount'),
            'top_products' => $this->getTopProducts($dateFrom, $dateTo),
            'payment_methods' => $this->getPaymentMethodBreakdown($dateFrom, $dateTo),
            'hourly_distribution' => $this->getHourlyDistribution($dateFrom, $dateTo),
        ];

        return view('admin.pos.transactions.analytics', compact('analytics', 'dateFrom', 'dateTo'));
    }

    protected function getTopProducts($dateFrom, $dateTo, $limit = 20)
    {
        return \DB::table('pos_transaction_items')
            ->join('pos_transactions', 'pos_transaction_items.pos_transaction_id', '=', 'pos_transactions.id')
            ->select(
                'pos_transaction_items.product_name',
                \DB::raw('SUM(pos_transaction_items.quantity) as total_quantity'),
                \DB::raw('SUM(pos_transaction_items.total) as total_sales'),
                \DB::raw('COUNT(DISTINCT pos_transaction_items.pos_transaction_id) as transaction_count')
            )
            ->whereBetween('pos_transactions.transaction_date', [$dateFrom, $dateTo])
            ->groupBy('pos_transaction_items.product_id', 'pos_transaction_items.product_name')
            ->orderByDesc('total_sales')
            ->limit($limit)
            ->get();
    }

    protected function getPaymentMethodBreakdown($dateFrom, $dateTo)
    {
        return PosTransaction::select('payment_method')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(total_amount) as total')
            ->whereBetween('transaction_date', [$dateFrom, $dateTo])
            ->groupBy('payment_method')
            ->get();
    }

    protected function getHourlyDistribution($dateFrom, $dateTo)
    {
        return PosTransaction::selectRaw('HOUR(transaction_date) as hour')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(total_amount) as total')
            ->whereBetween('transaction_date', [$dateFrom, $dateTo])
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();
    }
}
