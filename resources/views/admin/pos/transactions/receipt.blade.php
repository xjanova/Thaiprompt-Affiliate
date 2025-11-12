@extends('layouts.admin')

@section('title', 'Transaction Receipt')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Transaction Receipt</h1>
        <button class="btn btn-primary" onclick="window.print()">
            <i class="fas fa-print mr-2"></i>Print Receipt
        </button>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="text-center mb-4">
                <h2>{{ config('app.name') }}</h2>
                <p class="mb-0">Receipt #{{ $transaction->id ?? 'N/A' }}</p>
                <p class="text-muted">{{ isset($transaction->created_at) ? $transaction->created_at->format('M d, Y h:i A') : 'N/A' }}</p>
            </div>

            <hr>

            <div class="row mb-3">
                <div class="col-6">
                    <strong>Customer:</strong><br>
                    {{ $transaction->customer_name ?? 'Walk-in Customer' }}
                </div>
                <div class="col-6 text-right">
                    <strong>Cashier:</strong><br>
                    {{ $transaction->cashier_name ?? 'N/A' }}
                </div>
            </div>

            <hr>

            <table class="table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="text-center">Qty</th>
                        <th class="text-right">Price</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transaction->items ?? [] as $item)
                    <tr>
                        <td>{{ $item->name ?? 'N/A' }}</td>
                        <td class="text-center">{{ $item->quantity ?? '0' }}</td>
                        <td class="text-right">${{ number_format($item->price ?? 0, 2) }}</td>
                        <td class="text-right">${{ number_format($item->total ?? 0, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted">No items</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-right"><strong>Subtotal:</strong></td>
                        <td class="text-right">${{ number_format($transaction->subtotal ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-right"><strong>Tax:</strong></td>
                        <td class="text-right">${{ number_format($transaction->tax ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-right"><strong>Total:</strong></td>
                        <td class="text-right"><strong>${{ number_format($transaction->total ?? 0, 2) }}</strong></td>
                    </tr>
                </tfoot>
            </table>

            <div class="text-center mt-4">
                <p class="mb-0">Thank you for your business!</p>
            </div>
        </div>
    </div>
</div>
@endsection
