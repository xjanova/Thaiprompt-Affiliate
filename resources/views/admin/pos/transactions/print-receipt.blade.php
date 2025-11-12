<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt #{{ $transaction->id ?? 'N/A' }}</title>
    <style>
        body { font-family: Arial, sans-serif; width: 300px; margin: 0 auto; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        td, th { padding: 5px; }
        .total-row { border-top: 2px solid #000; }
        hr { border: 1px dashed #000; }
    </style>
</head>
<body>
    <div class="text-center">
        <h2>{{ config('app.name') }}</h2>
        <p>Receipt #{{ $transaction->id ?? 'N/A' }}</p>
        <p>{{ isset($transaction->created_at) ? $transaction->created_at->format('M d, Y h:i A') : 'N/A' }}</p>
    </div>

    <hr>

    <p><strong>Customer:</strong> {{ $transaction->customer_name ?? 'Walk-in' }}</p>
    <p><strong>Cashier:</strong> {{ $transaction->cashier_name ?? 'N/A' }}</p>

    <hr>

    <table>
        @foreach($transaction->items ?? [] as $item)
        <tr>
            <td>{{ $item->name }}</td>
            <td class="text-right">{{ $item->quantity }}x</td>
            <td class="text-right">${{ number_format($item->total ?? 0, 2) }}</td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="2"><strong>Total:</strong></td>
            <td class="text-right"><strong>${{ number_format($transaction->total ?? 0, 2) }}</strong></td>
        </tr>
    </table>

    <div class="text-center">
        <p>Thank you!</p>
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
