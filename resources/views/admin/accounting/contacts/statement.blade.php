@extends('layouts.admin')

@section('title', 'ใบแจ้งยอด')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div class="flex items-center">
            <a href="{{ route('admin.accounting.contacts.show', $contact) }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white mr-4">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">ใบแจ้งยอด</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">{{ $contact->name }}</p>
            </div>
        </div>
        <div class="flex space-x-3">
            <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-print mr-2"></i>พิมพ์
            </button>
            <button onclick="exportPDF()" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700">
                <i class="fas fa-file-pdf mr-2"></i>ส่งออก PDF
            </button>
        </div>
    </div>

    <!-- Date Filter -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="date" name="from_date" value="{{ request('from_date', now()->startOfMonth()->format('Y-m-d')) }}"
                   class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">

            <input type="date" name="to_date" value="{{ request('to_date', now()->format('Y-m-d')) }}"
                   class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">

            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-search mr-2"></i>ค้นหา
            </button>
        </form>
    </div>

    <!-- Statement -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <!-- Header Section -->
        <div class="p-8 border-b border-gray-200 dark:border-gray-700">
            <div class="grid grid-cols-2 gap-8">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">ใบแจ้งยอด</h2>
                    <div class="text-gray-600 dark:text-gray-400">
                        <p>ระหว่างวันที่: {{ request('from_date', now()->startOfMonth()->format('d/m/Y')) }} - {{ request('to_date', now()->format('d/m/Y')) }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ $contact->name }}</h3>
                    @if($contact->company_name)
                    <p class="text-gray-600 dark:text-gray-400">{{ $contact->company_name }}</p>
                    @endif
                    @if($contact->address)
                    <p class="text-gray-600 dark:text-gray-400 text-sm mt-2">
                        {{ $contact->address }}<br>
                        {{ $contact->district }} {{ $contact->province }} {{ $contact->postal_code }}
                    </p>
                    @endif
                    @if($contact->phone)
                    <p class="text-gray-600 dark:text-gray-400 text-sm">โทร: {{ $contact->phone }}</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">วันที่</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">เลขที่เอกสาร</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">รายละเอียด</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">เดบิต</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">เครดิต</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">คงเหลือ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <!-- Opening Balance -->
                    <tr class="bg-gray-50 dark:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300" colspan="3">
                            <strong>ยอดยกมา</strong>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-300">-</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-300">-</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold text-gray-900 dark:text-gray-300">
                            ฿{{ number_format($openingBalance ?? 0, 2) }}
                        </td>
                    </tr>

                    @php
                        $runningBalance = $openingBalance ?? 0;
                    @endphp

                    @forelse($transactions ?? [] as $transaction)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                            {{ $transaction->date->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                            {{ $transaction->document_number }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-300">
                            {{ $transaction->description }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-300">
                            @if($transaction->type === 'debit')
                                ฿{{ number_format($transaction->amount, 2) }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-300">
                            @if($transaction->type === 'credit')
                                ฿{{ number_format($transaction->amount, 2) }}
                            @else
                                -
                            @endif
                        </td>
                        @php
                            if ($transaction->type === 'debit') {
                                $runningBalance += $transaction->amount;
                            } else {
                                $runningBalance -= $transaction->amount;
                            }
                        @endphp
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold {{ $runningBalance > 0 ? 'text-red-600' : 'text-green-600' }}">
                            ฿{{ number_format($runningBalance, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                            ไม่มีรายการในช่วงเวลาที่เลือก
                        </td>
                    </tr>
                    @endforelse

                    <!-- Closing Balance -->
                    <tr class="bg-gray-100 dark:bg-gray-700 font-bold">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300" colspan="3">
                            <strong>ยอดคงเหลือ</strong>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-300">
                            ฿{{ number_format($totalDebit ?? 0, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-300">
                            ฿{{ number_format($totalCredit ?? 0, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-lg text-right font-bold {{ $runningBalance > 0 ? 'text-red-600' : 'text-green-600' }}">
                            ฿{{ number_format($runningBalance, 2) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="p-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <p>พิมพ์เมื่อ: {{ now()->format('d/m/Y H:i:s') }}</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function exportPDF() {
    // Implementation for PDF export
    alert('ฟังก์ชันส่งออก PDF จะถูกพัฒนาในอนาคต');
}
</script>
@endpush
@endsection
