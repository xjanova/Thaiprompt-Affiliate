<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NFCTransaction;
use App\Models\NFCCard;
use App\Models\NFCReader;
use Illuminate\Http\Request;
use Carbon\Carbon;

class NFCTransactionController extends Controller
{
    /**
     * Display a listing of NFC transactions
     */
    public function index(Request $request)
    {
        $query = NFCTransaction::with(['nfcCard', 'user', 'nfcReader'])->latest();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                    ->orWhere('receipt_number', 'like', "%{$search}%")
                    ->orWhere('reference_id', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('nfcCard', function ($q) use ($search) {
                        $q->where('card_number', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by card
        if ($request->filled('card_id')) {
            $query->where('nfc_card_id', $request->card_id);
        }

        // Filter by reader
        if ($request->filled('reader_id')) {
            $query->where('nfc_reader_id', $request->reader_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->paginate(20);

        // Statistics
        $statistics = [
            'total_transactions' => NFCTransaction::count(),
            'today_transactions' => NFCTransaction::today()->count(),
            'completed_transactions' => NFCTransaction::completed()->count(),
            'failed_transactions' => NFCTransaction::failed()->count(),
            'total_amount_today' => NFCTransaction::today()
                ->where('type', NFCTransaction::TYPE_PAYMENT)
                ->where('status', NFCTransaction::STATUS_COMPLETED)
                ->sum('amount'),
            'total_amount_this_month' => NFCTransaction::thisMonth()
                ->where('type', NFCTransaction::TYPE_PAYMENT)
                ->where('status', NFCTransaction::STATUS_COMPLETED)
                ->sum('amount'),
        ];

        // For filters
        $cards = NFCCard::orderBy('card_number')->get();
        $readers = NFCReader::orderBy('name')->get();

        return view('admin.nfc-transactions.index', compact(
            'transactions',
            'statistics',
            'cards',
            'readers'
        ));
    }

    /**
     * Display the specified transaction
     */
    public function show(NFCTransaction $nfcTransaction)
    {
        $nfcTransaction->load([
            'nfcCard.user',
            'user',
            'nfcReader',
            'paymentTransaction',
            'walletTransaction',
        ]);

        return view('admin.nfc-transactions.show', compact('nfcTransaction'));
    }

    /**
     * Export transactions
     */
    public function export(Request $request)
    {
        $query = NFCTransaction::with(['nfcCard', 'user', 'nfcReader']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->get();

        $filename = 'nfc-transactions-' . date('Y-m-d-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');

            // Add UTF-8 BOM
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header
            fputcsv($file, [
                'Transaction ID',
                'Receipt Number',
                'Card Number',
                'User',
                'Reader',
                'Type',
                'Amount',
                'Balance Before',
                'Balance After',
                'Status',
                'Location',
                'Created At',
                'Completed At',
            ]);

            // Data
            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    $transaction->transaction_id,
                    $transaction->receipt_number,
                    $transaction->nfcCard?->card_number,
                    $transaction->user?->name,
                    $transaction->nfcReader?->name,
                    $transaction->type_label,
                    $transaction->amount,
                    $transaction->balance_before,
                    $transaction->balance_after,
                    $transaction->status_label,
                    $transaction->location,
                    $transaction->created_at->format('Y-m-d H:i:s'),
                    $transaction->completed_at?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get transaction statistics for charts
     */
    public function statistics(Request $request)
    {
        $period = $request->input('period', 'week'); // week, month, year

        $startDate = match ($period) {
            'week' => Carbon::now()->subDays(7),
            'month' => Carbon::now()->subDays(30),
            'year' => Carbon::now()->subYear(),
            default => Carbon::now()->subDays(7),
        };

        // Transaction count by day
        $transactionsByDay = NFCTransaction::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Amount by day
        $amountByDay = NFCTransaction::where('created_at', '>=', $startDate)
            ->where('type', NFCTransaction::TYPE_PAYMENT)
            ->where('status', NFCTransaction::STATUS_COMPLETED)
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Transaction by type
        $transactionsByType = NFCTransaction::where('created_at', '>=', $startDate)
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get();

        // Transaction by status
        $transactionsByStatus = NFCTransaction::where('created_at', '>=', $startDate)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        return response()->json([
            'transactions_by_day' => $transactionsByDay,
            'amount_by_day' => $amountByDay,
            'transactions_by_type' => $transactionsByType,
            'transactions_by_status' => $transactionsByStatus,
        ]);
    }

    /**
     * Get real-time transaction feed
     */
    public function feed(Request $request)
    {
        $limit = $request->input('limit', 10);
        $lastId = $request->input('last_id');

        $query = NFCTransaction::with(['nfcCard', 'user', 'nfcReader'])
            ->latest();

        if ($lastId) {
            $query->where('id', '>', $lastId);
        }

        $transactions = $query->limit($limit)->get();

        return response()->json([
            'transactions' => $transactions,
            'last_id' => $transactions->last()?->id,
        ]);
    }
}
