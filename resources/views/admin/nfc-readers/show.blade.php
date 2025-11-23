@extends('layouts.admin-v3')

@section('title', 'รายละเอียดเครื่องอ่านบัตร NFC - ' . $nfcReader->name)

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Header Section --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.nfc-readers.index') }}"
               class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <i class="fas fa-broadcast-tower text-blue-600 dark:text-blue-400"></i>
                    {{ $nfcReader->name }}
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    รายละเอียดและสถิติการใช้งานเครื่องอ่านบัตร
                </p>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.nfc-readers.edit', $nfcReader) }}"
               class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white rounded-lg font-semibold shadow-lg transition-all duration-200 flex items-center gap-2">
                <i class="fas fa-edit"></i>
                แก้ไข
            </a>
        </div>
    </div>

    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-l-4 border-green-500 text-green-700 dark:text-green-400 px-6 py-4 rounded-lg mb-6 shadow-md"
             x-data="{ show: true }"
             x-show="show"
             x-transition
             x-init="setTimeout(() => show = false, 5000)">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fas fa-check-circle text-xl"></i>
                    <span>{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="text-green-700 dark:text-green-400 hover:text-green-900">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 border-l-4 border-red-500 text-red-700 dark:text-red-400 px-6 py-4 rounded-lg mb-6 shadow-md"
             x-data="{ show: true }"
             x-show="show"
             x-transition>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fas fa-exclamation-circle text-xl"></i>
                    <span>{{ session('error') }}</span>
                </div>
                <button @click="show = false" class="text-red-700 dark:text-red-400 hover:text-red-900">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        {{-- Total Transactions --}}
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm font-medium">รายการทั้งหมด</p>
                    <h3 class="text-3xl font-bold mt-1">{{ number_format($statistics['total_transactions']) }}</h3>
                </div>
                <div class="bg-white/20 backdrop-blur-sm rounded-full p-4">
                    <i class="fas fa-list text-2xl"></i>
                </div>
            </div>
        </div>

        {{-- Today Transactions --}}
        <div class="bg-gradient-to-br from-green-500 to-green-600 dark:from-green-600 dark:to-green-700 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm font-medium">วันนี้</p>
                    <h3 class="text-3xl font-bold mt-1">{{ number_format($statistics['today_transactions']) }}</h3>
                </div>
                <div class="bg-white/20 backdrop-blur-sm rounded-full p-4">
                    <i class="fas fa-calendar-day text-2xl"></i>
                </div>
            </div>
        </div>

        {{-- Completed --}}
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 dark:from-emerald-600 dark:to-emerald-700 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-emerald-100 text-sm font-medium">สำเร็จ</p>
                    <h3 class="text-3xl font-bold mt-1">{{ number_format($statistics['completed_transactions']) }}</h3>
                </div>
                <div class="bg-white/20 backdrop-blur-sm rounded-full p-4">
                    <i class="fas fa-check-circle text-2xl"></i>
                </div>
            </div>
        </div>

        {{-- Failed --}}
        <div class="bg-gradient-to-br from-red-500 to-red-600 dark:from-red-600 dark:to-red-700 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-100 text-sm font-medium">ล้มเหลว</p>
                    <h3 class="text-3xl font-bold mt-1">{{ number_format($statistics['failed_transactions']) }}</h3>
                </div>
                <div class="bg-white/20 backdrop-blur-sm rounded-full p-4">
                    <i class="fas fa-times-circle text-2xl"></i>
                </div>
            </div>
        </div>

        {{-- Total Amount Today --}}
        <div class="bg-gradient-to-br from-amber-500 to-amber-600 dark:from-amber-600 dark:to-amber-700 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-amber-100 text-sm font-medium">ยอดเงินวันนี้</p>
                    <h3 class="text-2xl font-bold mt-1">฿{{ number_format($statistics['total_amount_today'], 2) }}</h3>
                </div>
                <div class="bg-white/20 backdrop-blur-sm rounded-full p-4">
                    <i class="fas fa-dollar-sign text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Reader Information --}}
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-info-circle text-blue-600 dark:text-blue-400"></i>
                    ข้อมูลเครื่องอ่านบัตร
                </h3>

                <div class="space-y-4">
                    {{-- Status Badge --}}
                    <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-sm text-gray-600 dark:text-gray-400">สถานะ</span>
                        <div class="flex gap-2">
                            @if($nfcReader->status === 'active')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    เปิดใช้งาน
                                </span>
                            @elseif($nfcReader->status === 'inactive')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                    <i class="fas fa-times-circle mr-1"></i>
                                    ปิดใช้งาน
                                </span>
                            @elseif($nfcReader->status === 'maintenance')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300">
                                    <i class="fas fa-wrench mr-1"></i>
                                    ปรับปรุง
                                </span>
                            @endif

                            @if($nfcReader->isOnline())
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300">
                                    <i class="fas fa-wifi mr-1"></i>
                                    ออนไลน์
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">
                                    <i class="fas fa-wifi-slash mr-1"></i>
                                    ออฟไลน์
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Reader ID --}}
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Reader ID</span>
                        <span class="text-sm font-mono font-semibold text-gray-900 dark:text-white">{{ $nfcReader->reader_id }}</span>
                    </div>

                    {{-- Serial Number --}}
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Serial Number</span>
                        <span class="text-sm font-mono text-gray-900 dark:text-white">{{ $nfcReader->serial_number }}</span>
                    </div>

                    {{-- Location --}}
                    <div class="flex items-start justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">สถานที่</span>
                        <span class="text-sm text-gray-900 dark:text-white text-right">{{ $nfcReader->location }}</span>
                    </div>

                    {{-- IP Address --}}
                    @if($nfcReader->ip_address)
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">IP Address</span>
                            <span class="text-sm font-mono text-gray-900 dark:text-white">{{ $nfcReader->ip_address }}</span>
                        </div>
                    @endif

                    {{-- MAC Address --}}
                    @if($nfcReader->mac_address)
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">MAC Address</span>
                            <span class="text-sm font-mono text-gray-900 dark:text-white">{{ $nfcReader->mac_address }}</span>
                        </div>
                    @endif

                    {{-- Last Heartbeat --}}
                    <div class="flex items-start justify-between pt-3 border-t border-gray-200 dark:border-gray-700">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Heartbeat ล่าสุด</span>
                        <div class="text-right">
                            @if($nfcReader->last_heartbeat)
                                <div class="text-sm text-gray-900 dark:text-white">{{ $nfcReader->last_heartbeat->diffForHumans() }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $nfcReader->last_heartbeat->format('d/m/Y H:i:s') }}</div>
                            @else
                                <span class="text-sm text-gray-500 dark:text-gray-400 italic">ไม่มีข้อมูล</span>
                            @endif
                        </div>
                    </div>

                    {{-- Created By --}}
                    @if($nfcReader->creator)
                        <div class="flex items-center justify-between pt-3 border-t border-gray-200 dark:border-gray-700">
                            <span class="text-sm text-gray-600 dark:text-gray-400">สร้างโดย</span>
                            <span class="text-sm text-gray-900 dark:text-white">{{ $nfcReader->creator->name }}</span>
                        </div>
                    @endif

                    {{-- Created At --}}
                    <div class="flex items-start justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">สร้างเมื่อ</span>
                        <div class="text-right">
                            <div class="text-sm text-gray-900 dark:text-white">{{ $nfcReader->created_at->diffForHumans() }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $nfcReader->created_at->format('d/m/Y H:i:s') }}</div>
                        </div>
                    </div>
                </div>

                {{-- Description --}}
                @if($nfcReader->description)
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">คำอธิบาย</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $nfcReader->description }}</p>
                    </div>
                @endif

                {{-- Settings --}}
                @if($nfcReader->settings)
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">การตั้งค่า</h4>
                        <pre class="text-xs bg-gray-100 dark:bg-gray-700 p-3 rounded-lg overflow-x-auto font-mono">{{ json_encode($nfcReader->settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                @endif

                {{-- Actions --}}
                <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700 space-y-2">
                    @if($nfcReader->status === 'active')
                        <form action="{{ route('admin.nfc-readers.deactivate', $nfcReader) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-semibold transition-colors flex items-center justify-center gap-2">
                                <i class="fas fa-stop-circle"></i>
                                ปิดการใช้งาน
                            </button>
                        </form>
                    @else
                        <form action="{{ route('admin.nfc-readers.activate', $nfcReader) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold transition-colors flex items-center justify-center gap-2">
                                <i class="fas fa-play-circle"></i>
                                เปิดการใช้งาน
                            </button>
                        </form>
                    @endif

                    <form action="{{ route('admin.nfc-readers.maintenance', $nfcReader) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-semibold transition-colors flex items-center justify-center gap-2">
                            <i class="fas fa-wrench"></i>
                            ตั้งค่าปรับปรุง
                        </button>
                    </form>

                    <form action="{{ route('admin.nfc-readers.destroy', $nfcReader) }}"
                          method="POST"
                          onsubmit="return confirm('คุณแน่ใจที่จะลบเครื่องอ่านบัตรนี้? การลบจะไม่สามารถกู้คืนได้')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold transition-colors flex items-center justify-center gap-2">
                            <i class="fas fa-trash"></i>
                            ลบเครื่องอ่านบัตร
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Recent Transactions --}}
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-history text-blue-600 dark:text-blue-400"></i>
                        รายการล่าสุด (20 รายการ)
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">เวลา</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">บัตร</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">ผู้ใช้</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">ประเภท</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">จำนวน</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">สถานะ</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($recentTransactions as $transaction)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                        <div>{{ $transaction->created_at->format('d/m/Y') }}</div>
                                        <div class="text-xs">{{ $transaction->created_at->format('H:i:s') }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($transaction->nfcCard)
                                            <span class="text-sm font-mono text-gray-900 dark:text-white">{{ $transaction->nfcCard->card_number }}</span>
                                        @else
                                            <span class="text-sm text-gray-500 italic">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($transaction->user)
                                            <div class="text-sm text-gray-900 dark:text-white">{{ $transaction->user->name }}</div>
                                        @else
                                            <span class="text-sm text-gray-500 italic">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                        {{ $transaction->type }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-gray-900 dark:text-white">
                                        @if($transaction->amount)
                                            ฿{{ number_format($transaction->amount, 2) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        @if($transaction->status === 'completed')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                                สำเร็จ
                                            </span>
                                        @elseif($transaction->status === 'pending')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                                                รอดำเนินการ
                                            </span>
                                        @elseif($transaction->status === 'failed')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">
                                                ล้มเหลว
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center text-gray-500 dark:text-gray-400">
                                            <i class="fas fa-inbox text-4xl mb-3 text-gray-300 dark:text-gray-600"></i>
                                            <p class="text-sm">ยังไม่มีรายการธุรกรรม</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
