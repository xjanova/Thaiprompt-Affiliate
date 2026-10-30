@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-cyan-50 to-blue-100 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-6">ใบเสนอราคาของฉัน</h1>

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            @forelse($quotations as $quotation)
                <a href="{{ route('quotations.show', $quotation) }}"
                   class="flex flex-col md:flex-row md:items-center justify-between gap-3 px-6 py-4 border-b border-gray-200 dark:border-gray-700 last:border-b-0 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $quotation->quotation_number }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $quotation->softwareProduct?->name ?? 'ไม่พบสินค้า' }} · {{ $quotation->created_at->format('d/m/Y') }}
                        </p>
                    </div>
                    <div class="md:text-right">
                        <p class="font-semibold text-gray-900 dark:text-gray-100">{{ number_format($quotation->total_amount, 2) }} ฿</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $quotation->status_label }}</p>
                    </div>
                </a>
            @empty
                <p class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">ยังไม่มีใบเสนอราคา</p>
            @endforelse
        </div>

        <div class="mt-6">
            {{ $quotations->links() }}
        </div>
    </div>
</div>
@endsection
