@extends('layouts.app')

@section('title', 'จัดการแผนผ่อนชำระ - ผู้ดูแลระบบ')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-orange-50/20 to-amber-50/30 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">

    <!-- Hero Header -->
    <div class="relative overflow-hidden bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 dark:from-indigo-700 dark:via-purple-700 dark:to-pink-700">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 40px 40px;"></div>
        </div>

        <div class="container mx-auto px-4 py-12 relative z-10">
            <div class="max-w-6xl">
                <div class="inline-flex items-center gap-2 bg-white/20 backdrop-blur-lg px-4 py-2 rounded-full mb-4 border border-white/30">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd"/>
                    </svg>
                    <span class="font-semibold text-white">ผู้ดูแลระบบ</span>
                </div>

                <h1 class="text-4xl md:text-5xl font-black text-white mb-3 tracking-tight drop-shadow-lg">
                    จัดการแผนผ่อนชำระ
                </h1>
                <p class="text-xl text-purple-100 font-medium">
                    ติดตาม อนุมัติ และจัดการแผนผ่อนชำระของลูกค้าทั้งหมดในระบบ
                </p>
            </div>
        </div>

        <!-- Wave Divider -->
        <div class="absolute bottom-0 left-0 right-0">
            <svg viewBox="0 0 1440 48" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full dark:hidden">
                <path d="M0 48h1440V24C1440 24 1200 0 720 0S0 24 0 24v24z" fill="rgb(249, 250, 251)"/>
            </svg>
            <svg viewBox="0 0 1440 48" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full hidden dark:block">
                <path d="M0 48h1440V24C1440 24 1200 0 720 0S0 24 0 24v24z" fill="rgb(17, 24, 39)"/>
            </svg>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8 -mt-6 relative z-10">

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Total -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-br from-blue-600 to-indigo-600 dark:from-blue-700 dark:to-indigo-700 text-white p-6">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="text-right">
                            <div class="text-3xl font-black">{{ $stats['total'] }}</div>
                        </div>
                    </div>
                    <div class="font-semibold">แผนทั้งหมด</div>
                </div>
            </div>

            <!-- Active -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-br from-green-600 to-emerald-600 dark:from-green-700 dark:to-emerald-700 text-white p-6">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="text-right">
                            <div class="text-3xl font-black">{{ $stats['active'] }}</div>
                        </div>
                    </div>
                    <div class="font-semibold">กำลังดำเนินการ</div>
                </div>
            </div>

            <!-- Overdue -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-br from-red-600 to-rose-600 dark:from-red-700 dark:to-rose-700 text-white p-6">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="text-right">
                            <div class="text-3xl font-black">{{ $stats['overdue'] }}</div>
                        </div>
                    </div>
                    <div class="font-semibold">ค้างชำระ</div>
                </div>
            </div>

            <!-- Completed -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-br from-purple-600 to-pink-600 dark:from-purple-700 dark:to-pink-700 text-white p-6">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm9.707 5.707a1 1 0 00-1.414-1.414L9 12.586l-1.293-1.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="text-right">
                            <div class="text-3xl font-black">{{ $stats['completed'] }}</div>
                        </div>
                    </div>
                    <div class="font-semibold">ชำระครบ</div>
                </div>
            </div>
        </div>

        <!-- Filters & Search -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6 mb-6">
            <form method="GET" action="{{ route('admin.installment-plans.index') }}" class="flex flex-col md:flex-row gap-4">
                <!-- Search -->
                <div class="flex-1">
                    <div class="relative">
                        <input type="text"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="ค้นหาเลขแผน, ชื่อลูกค้า, อีเมล..."
                               class="w-full pl-12 pr-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:border-purple-500 dark:focus:border-purple-400 focus:ring-4 focus:ring-purple-100 dark:focus:ring-purple-900/50 transition-all placeholder-gray-400 dark:placeholder-gray-500">
                        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="md:w-64">
                    <select name="status"
                            onchange="this.form.submit()"
                            class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:border-purple-500 dark:focus:border-purple-400 focus:ring-4 focus:ring-purple-100 dark:focus:ring-purple-900/50 transition-all font-medium">
                        <option value="">ทุกสถานะ</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รอชำระเงินดาวน์</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>กำลังผ่อนชำระ</option>
                        <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>ค้างชำระ</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>ชำระครบแล้ว</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>ยกเลิก</option>
                    </select>
                </div>

                <!-- Search Button -->
                <button type="submit"
                        class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                    </svg>
                    ค้นหา
                </button>

                @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('admin.installment-plans.index') }}"
                   class="px-6 py-3 border-2 border-gray-300 dark:border-gray-600 hover:border-red-500 dark:hover:border-red-500 text-gray-700 dark:text-gray-300 hover:text-red-500 dark:hover:text-red-400 font-bold rounded-xl transition-all flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                    ล้าง
                </a>
                @endif
            </form>
        </div>

        <!-- Plans Table -->
        @if($plans->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden mb-8">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-700 dark:to-pink-700 text-white">
                        <tr>
                            <th class="px-6 py-4 text-left font-bold">เลขแผน</th>
                            <th class="px-6 py-4 text-left font-bold">ลูกค้า</th>
                            <th class="px-6 py-4 text-center font-bold">สถานะ</th>
                            <th class="px-6 py-4 text-right font-bold">ยอดรวม</th>
                            <th class="px-6 py-4 text-center font-bold">งวด</th>
                            <th class="px-6 py-4 text-center font-bold">ความคืบหน้า</th>
                            <th class="px-6 py-4 text-center font-bold">กำหนดชำระถัดไป</th>
                            <th class="px-6 py-4 text-center font-bold">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($plans as $plan)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <!-- Plan Number -->
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-900 dark:text-gray-100">
                                    {{ $plan->plan_number }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $plan->created_at->format('d/m/Y') }}
                                </div>
                            </td>

                            <!-- Customer -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-800 dark:to-pink-800 rounded-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $plan->user->name }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $plan->user->email }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold bg-{{ $plan->status_color }}-100 text-{{ $plan->status_color }}-800 dark:bg-{{ $plan->status_color }}-900/30 dark:text-{{ $plan->status_color }}-400">
                                    {{ $plan->status_label }}
                                </span>
                            </td>

                            <!-- Total Amount -->
                            <td class="px-6 py-4 text-right">
                                <div class="font-bold text-gray-900 dark:text-gray-100">
                                    ฿{{ number_format($plan->total_amount, 2) }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    ชำระแล้ว ฿{{ number_format($plan->total_paid, 2) }}
                                </div>
                            </td>

                            <!-- Installments -->
                            <td class="px-6 py-4 text-center">
                                <div class="font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $plan->paid_installments }}/{{ $plan->total_installments }}
                                </div>
                            </td>

                            <!-- Progress -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                                        <div class="bg-gradient-to-r from-orange-500 to-amber-500 h-full rounded-full transition-all duration-500"
                                             style="width: {{ $plan->progress_percentage }}%"></div>
                                    </div>
                                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400 min-w-[3rem] text-right">
                                        {{ number_format($plan->progress_percentage, 0) }}%
                                    </span>
                                </div>
                            </td>

                            <!-- Next Payment -->
                            <td class="px-6 py-4 text-center">
                                @if($plan->next_payment_date && $plan->status !== 'completed')
                                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $plan->next_payment_date->format('d/m/Y') }}
                                    </div>
                                    @if($plan->next_payment_date->isPast())
                                        <div class="text-xs text-red-600 dark:text-red-400 font-semibold">
                                            เลยกำหนด
                                        </div>
                                    @else
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $plan->next_payment_date->diffForHumans() }}
                                        </div>
                                    @endif
                                @else
                                    <span class="text-sm text-gray-400 dark:text-gray-500">-</span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('admin.installment-plans.show', $plan->id) }}"
                                       class="p-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-all"
                                       title="ดูรายละเอียด">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </a>

                                    @if($plan->order)
                                    <a href="{{ route('orders.show', $plan->order->id) }}"
                                       class="p-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all"
                                       title="ดูคำสั่งซื้อ">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/>
                                        </svg>
                                    </a>
                                    @endif

                                    @if($plan->status !== 'cancelled' && $plan->status !== 'completed')
                                    <form action="{{ route('admin.installment-plans.cancel', $plan->id) }}"
                                          method="POST"
                                          onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการยกเลิกแผนนี้?')">
                                        @csrf
                                        <button type="submit"
                                                class="p-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-all"
                                                title="ยกเลิกแผน">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                            </svg>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="flex justify-center">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-4 border border-gray-100 dark:border-gray-700">
                {{ $plans->appends(request()->query())->links() }}
            </div>
        </div>
        @else
        <!-- Empty State -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-12 text-center border border-gray-100 dark:border-gray-700">
            <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30 rounded-full mb-6">
                <svg class="w-12 h-12 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-3">ไม่พบแผนผ่อนชำระ</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-md mx-auto">
                @if(request()->hasAny(['search', 'status']))
                    ไม่พบแผนผ่อนชำระที่ตรงกับเงื่อนไขการค้นหา ลองปรับเปลี่ยนเงื่อนไข
                @else
                    ยังไม่มีแผนผ่อนชำระในระบบ
                @endif
            </p>
            @if(request()->hasAny(['search', 'status']))
            <a href="{{ route('admin.installment-plans.index') }}"
               class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
                ล้างตัวกรอง
            </a>
            @endif
        </div>
        @endif
    </div>
</div>
@endsection
