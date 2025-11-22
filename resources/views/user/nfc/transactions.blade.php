@extends('layouts.user')

@section('title', 'ประวัติการใช้งาน - ' . ($card->card_name ?? 'การ์ด NFC'))

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Back Button --}}
    <div class="mb-6">
        <a href="{{ route('user.nfc.show', $card) }}" class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition">
            <i class="fas fa-arrow-left"></i>
            <span>กลับไปรายละเอียดการ์ด</span>
        </a>
    </div>

    {{-- Header --}}
    <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl p-6 text-white shadow-xl mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2">
                    <i class="fas fa-history mr-2"></i>
                    ประวัติการทำธุรกรรม
                </h1>
                <p class="text-blue-100">{{ $card->card_name ?? 'การ์ด NFC' }} • {{ $card->masked_card_number }}</p>
            </div>
            <div class="text-right">
                <p class="text-blue-100 text-sm mb-1">ยอดคงเหลือ</p>
                <p class="text-4xl font-bold">฿{{ number_format($card->balance, 2) }}</p>
            </div>
        </div>
    </div>

    {{-- Transactions List --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
        @if($transactions->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                เวลา
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                ประเภท
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                สถานที่
                            </th>
                            <th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                จำนวนเงิน
                            </th>
                            <th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                ยอดคงเหลือ
                            </th>
                            <th class="px-6 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                สถานะ
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($transactions as $transaction)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                {{-- Date/Time --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white font-medium">
                                        {{ $transaction->created_at->format('d/m/Y') }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $transaction->created_at->format('H:i:s') }}
                                    </div>
                                </td>

                                {{-- Type --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-10 h-10 rounded-full bg-{{ $transaction->type === 'payment' ? 'red' : 'green' }}-100 dark:bg-{{ $transaction->type === 'payment' ? 'red' : 'green' }}-900/30 flex items-center justify-center">
                                            <i class="fas fa-{{ $transaction->type === 'payment' ? 'arrow-up' : 'arrow-down' }} text-{{ $transaction->type === 'payment' ? 'red' : 'green' }}-600"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $transaction->type_label }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                #{{ $transaction->transaction_id }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Location --}}
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        @if($transaction->nfcReader)
                                            <i class="fas fa-map-marker-alt text-blue-500 mr-1"></i>
                                            {{ $transaction->nfcReader->name }}
                                        @elseif($transaction->location)
                                            <i class="fas fa-map-marker-alt text-blue-500 mr-1"></i>
                                            {{ $transaction->location }}
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Amount --}}
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-lg font-bold {{ $transaction->type === 'payment' ? 'text-red-600' : 'text-green-600' }}">
                                        {{ $transaction->type === 'payment' ? '-' : '+' }}฿{{ number_format($transaction->amount, 2) }}
                                    </div>
                                </td>

                                {{-- Balance After --}}
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        ฿{{ number_format($transaction->balance_after, 2) }}
                                    </div>
                                </td>

                                {{-- Status --}}
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-{{ $transaction->status_badge_color }}-100 text-{{ $transaction->status_badge_color }}-700 dark:bg-{{ $transaction->status_badge_color }}-900/30 dark:text-{{ $transaction->status_badge_color }}-400">
                                        @if($transaction->status === 'completed')
                                            <i class="fas fa-check-circle mr-1"></i>
                                        @elseif($transaction->status === 'failed')
                                            <i class="fas fa-times-circle mr-1"></i>
                                        @elseif($transaction->status === 'processing')
                                            <i class="fas fa-spinner fa-spin mr-1"></i>
                                        @endif
                                        {{ $transaction->status_label }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/30">
                {{ $transactions->links() }}
            </div>
        @else
            {{-- Empty State --}}
            <div class="text-center py-16">
                <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 mb-6">
                    <i class="fas fa-receipt text-5xl text-gray-400"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">ยังไม่มีธุรกรรม</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    ยังไม่มีประวัติการทำธุรกรรมสำหรับการ์ดนี้
                </p>
                <a href="{{ route('user.nfc.show', $card) }}" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white rounded-lg font-medium transition-all duration-200 shadow-lg hover:shadow-xl">
                    <i class="fas fa-arrow-left"></i>
                    <span>กลับไปหน้าการ์ด</span>
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
