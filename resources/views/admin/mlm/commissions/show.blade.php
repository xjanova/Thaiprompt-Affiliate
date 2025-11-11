@extends('layouts.admin')

@section('title', 'รายละเอียดคอมมิชชั่น')

@section('content')
<div class="space-y-6">
    <!-- Back Button -->
    <div>
        <a href="{{ route('admin.mlm.commissions.index') }}"
           class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
            <i class="fas fa-arrow-left"></i>
            <span>กลับไปยังรายการคอมมิชชั่น</span>
        </a>
    </div>

    <!-- Commission Header -->
    <div class="bg-gradient-to-r from-emerald-600 via-green-600 to-teal-600 dark:from-emerald-800 dark:via-green-800 dark:to-teal-800 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-white opacity-5 rounded-full -mr-48 -mt-48"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 bg-white opacity-5 rounded-full -ml-32 -mb-32"></div>

        <div class="relative z-10">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="bg-white/20 backdrop-blur-sm p-3 rounded-xl">
                            <i class="fas fa-coins text-3xl"></i>
                        </div>
                        <div>
                            <p class="text-emerald-100 text-sm">รหัสคอมมิชชั่น</p>
                            <h1 class="text-3xl font-bold">#{{ $commission->id }}</h1>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 mt-4">
                        <span class="px-4 py-2 rounded-xl font-semibold text-sm
                            {{ $commission->status === 'pending' ? 'bg-yellow-500 text-white' : '' }}
                            {{ $commission->status === 'approved' ? 'bg-green-500 text-white' : '' }}
                            {{ $commission->status === 'paid' ? 'bg-blue-500 text-white' : '' }}
                            {{ $commission->status === 'rejected' ? 'bg-red-500 text-white' : '' }}">
                            <i class="fas fa-circle mr-2 text-xs"></i>
                            {{ ucfirst($commission->status) }}
                        </span>
                        <span class="px-4 py-2 bg-white/20 backdrop-blur-sm rounded-xl font-semibold text-sm">
                            <i class="fas fa-tag mr-2"></i>
                            {{ ucfirst($commission->type) }}
                            @if($commission->level)
                                <span class="ml-2 opacity-75">Level {{ $commission->level }}</span>
                            @endif
                        </span>
                    </div>
                </div>

                <div class="text-right">
                    <p class="text-emerald-100 text-sm mb-2">จำนวนคอมมิชชั่น</p>
                    <h2 class="text-5xl font-bold mb-4">฿{{ number_format($commission->commission_amount, 2) }}</h2>
                    @if($commission->pv_amount)
                        <p class="text-emerald-100">
                            <i class="fas fa-gem mr-2"></i>
                            {{ number_format($commission->pv_amount, 2) }} PV
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    @if($commission->status === 'pending')
    <div class="flex gap-3 justify-end">
        <form action="{{ route('admin.mlm.commissions.approve') }}" method="POST" class="inline-block">
            @csrf
            <input type="hidden" name="commission_ids[]" value="{{ $commission->id }}">
            <button type="submit" class="bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white px-6 py-3 rounded-xl font-semibold shadow-lg flex items-center gap-2 transition-all duration-300 transform hover:scale-105">
                <i class="fas fa-check-circle"></i>
                อนุมัติคอมมิชชั่น
            </button>
        </form>
        <button onclick="showRejectModal()" class="bg-gradient-to-r from-red-500 to-pink-600 hover:from-red-600 hover:to-pink-700 text-white px-6 py-3 rounded-xl font-semibold shadow-lg flex items-center gap-2 transition-all duration-300 transform hover:scale-105">
            <i class="fas fa-times-circle"></i>
            ปฏิเสธคอมมิชชั่น
        </button>
    </div>
    @endif

    @if($commission->status === 'approved')
    <div class="flex gap-3 justify-end">
        <form action="{{ route('admin.mlm.commissions.pay') }}" method="POST" class="inline-block">
            @csrf
            <input type="hidden" name="commission_ids[]" value="{{ $commission->id }}">
            <button type="submit" class="bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white px-6 py-3 rounded-xl font-semibold shadow-lg flex items-center gap-2 transition-all duration-300 transform hover:scale-105">
                <i class="fas fa-money-check-alt"></i>
                จ่ายเงินคอมมิชชั่น
            </button>
        </form>
    </div>
    @endif

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Member Information -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Recipient Info -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                    <div class="bg-gradient-to-r from-blue-500 to-purple-600 p-2 rounded-lg mr-3">
                        <i class="fas fa-user-circle text-white"></i>
                    </div>
                    ผู้รับคอมมิชชั่น
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2 block">ชื่อสมาชิก</label>
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center text-white font-bold text-lg">
                                {{ strtoupper(substr($commission->member->user->name, 0, 2)) }}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $commission->member->user->name }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $commission->member->member_code }}</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2 block">อีเมล</label>
                        <p class="text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-envelope text-gray-400"></i>
                            {{ $commission->member->user->email }}
                        </p>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2 block">แผน MLM</label>
                        <span class="inline-flex items-center px-3 py-1 rounded-lg bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 font-medium">
                            <i class="fas fa-award mr-2"></i>
                            {{ $commission->plan->display_name }}
                        </span>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2 block">Unilevel Level</label>
                        <span class="inline-flex items-center px-3 py-1 rounded-lg bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 font-bold">
                            <i class="fas fa-layer-group mr-2"></i>
                            Level {{ $commission->member->unilevel_level }}
                        </span>
                    </div>
                </div>

                <!-- Member Stats -->
                <div class="grid grid-cols-3 gap-4 mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <div class="text-center">
                        <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($commission->member->total_pv, 2) }}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Total PV</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">฿{{ number_format($commission->member->total_earnings, 2) }}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Total Earnings</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $commission->member->total_direct_referrals }}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Direct Referrals</p>
                    </div>
                </div>
            </div>

            <!-- Source Information -->
            @if($commission->fromMember)
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                    <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-2 rounded-lg mr-3">
                        <i class="fas fa-user-friends text-white"></i>
                    </div>
                    ที่มาของคอมมิชชั่น
                </h3>

                <div class="flex items-center justify-between bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 p-6 rounded-xl border border-green-200 dark:border-green-800">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center text-white font-bold text-2xl shadow-lg">
                            {{ strtoupper(substr($commission->fromMember->user->name, 0, 2)) }}
                        </div>
                        <div>
                            <p class="font-bold text-gray-900 dark:text-white text-lg">{{ $commission->fromMember->user->name }}</p>
                            <p class="text-gray-600 dark:text-gray-400">{{ $commission->fromMember->member_code }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">{{ $commission->fromMember->user->email }}</p>
                        </div>
                    </div>

                    <div class="text-right">
                        <p class="text-sm text-gray-600 dark:text-gray-400">สร้างคอมมิชชั่น</p>
                        <div class="flex items-center gap-2 mt-2">
                            <i class="fas fa-arrow-right text-green-500"></i>
                            <span class="text-2xl font-bold text-green-600 dark:text-green-400">฿{{ number_format($commission->commission_amount, 2) }}</span>
                        </div>
                    </div>
                </div>

                @if($commission->pv_amount)
                <div class="mt-4 bg-purple-50 dark:bg-purple-900/20 p-4 rounded-xl border border-purple-200 dark:border-purple-800">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-gem text-2xl text-purple-600 dark:text-purple-400"></i>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Point Value</p>
                                <p class="text-xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($commission->pv_amount, 2) }} PV</p>
                            </div>
                        </div>
                        @if($commission->sales_amount)
                        <div class="text-right">
                            <p class="text-sm text-gray-600 dark:text-gray-400">ยอดขาย</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-white">฿{{ number_format($commission->sales_amount, 2) }}</p>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>
            @endif

            <!-- Network Tree Visualization -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                    <div class="bg-gradient-to-r from-purple-500 to-pink-600 p-2 rounded-lg mr-3">
                        <i class="fas fa-project-diagram text-white"></i>
                    </div>
                    โครงสร้างเครือข่าย
                </h3>

                <div class="flex items-center justify-center p-8">
                    <div class="relative">
                        <!-- From Member (Source) -->
                        @if($commission->fromMember)
                        <div class="absolute -top-32 left-1/2 transform -translate-x-1/2">
                            <div class="text-center">
                                <div class="w-20 h-20 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center text-white font-bold text-2xl shadow-xl mx-auto mb-2">
                                    {{ strtoupper(substr($commission->fromMember->user->name, 0, 2)) }}
                                </div>
                                <p class="text-xs font-semibold text-gray-900 dark:text-white">{{ $commission->fromMember->user->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">ผู้สร้างรายได้</p>
                            </div>
                            <!-- Arrow Down -->
                            <div class="absolute -bottom-12 left-1/2 transform -translate-x-1/2">
                                <i class="fas fa-arrow-down text-3xl text-green-500"></i>
                            </div>
                        </div>
                        @endif

                        <!-- Current Member (Recipient) -->
                        <div class="relative mt-16">
                            <div class="text-center">
                                <div class="w-24 h-24 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center text-white font-bold text-3xl shadow-2xl mx-auto mb-3 ring-4 ring-blue-300 dark:ring-blue-700">
                                    {{ strtoupper(substr($commission->member->user->name, 0, 2)) }}
                                </div>
                                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $commission->member->user->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">ผู้รับคอมมิชชั่น</p>
                                <span class="inline-flex items-center px-3 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 rounded-lg text-xs font-bold">
                                    Level {{ $commission->level ?? $commission->member->unilevel_level }}
                                </span>
                            </div>
                        </div>

                        <!-- Commission Amount Display -->
                        <div class="mt-6 bg-gradient-to-r from-yellow-100 to-orange-100 dark:from-yellow-900/30 dark:to-orange-900/30 p-4 rounded-xl border-2 border-yellow-400 dark:border-yellow-600">
                            <p class="text-center text-sm text-gray-600 dark:text-gray-400 mb-1">ได้รับคอมมิชชั่น</p>
                            <p class="text-center text-3xl font-bold text-yellow-600 dark:text-yellow-400">฿{{ number_format($commission->commission_amount, 2) }}</p>
                            <p class="text-center text-xs text-gray-500 dark:text-gray-400 mt-1">{{ ucfirst($commission->type) }} Commission</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Timeline -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                    <div class="bg-gradient-to-r from-yellow-500 to-orange-600 p-2 rounded-lg mr-3">
                        <i class="fas fa-history text-white"></i>
                    </div>
                    ไทม์ไลน์
                </h3>

                <div class="space-y-4">
                    <!-- Created -->
                    <div class="flex gap-3">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                                <i class="fas fa-plus text-blue-600 dark:text-blue-400"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900 dark:text-white text-sm">สร้างคอมมิชชั่น</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">{{ $commission->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>

                    @if($commission->approved_at)
                    <!-- Approved -->
                    <div class="flex gap-3">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                                <i class="fas fa-check text-green-600 dark:text-green-400"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900 dark:text-white text-sm">อนุมัติ</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">{{ $commission->approved_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                    @endif

                    @if($commission->paid_at)
                    <!-- Paid -->
                    <div class="flex gap-3">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                                <i class="fas fa-money-bill-wave text-purple-600 dark:text-purple-400"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900 dark:text-white text-sm">จ่ายเงิน</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">{{ $commission->paid_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                    @endif

                    @if($commission->rejected_at)
                    <!-- Rejected -->
                    <div class="flex gap-3">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center">
                                <i class="fas fa-times text-red-600 dark:text-red-400"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900 dark:text-white text-sm">ปฏิเสธ</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">{{ $commission->rejected_at->format('d/m/Y H:i') }}</p>
                            @if($commission->rejection_reason)
                            <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $commission->rejection_reason }}</p>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Commission Details -->
            <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl shadow-xl p-6 text-white">
                <h3 class="text-lg font-bold mb-4 flex items-center">
                    <i class="fas fa-info-circle mr-2"></i>
                    รายละเอียด
                </h3>

                <div class="space-y-3">
                    <div class="flex justify-between items-center pb-3 border-b border-white/20">
                        <span class="text-sm opacity-90">ประเภท</span>
                        <span class="font-semibold">{{ ucfirst($commission->type) }}</span>
                    </div>

                    @if($commission->level)
                    <div class="flex justify-between items-center pb-3 border-b border-white/20">
                        <span class="text-sm opacity-90">ระดับ</span>
                        <span class="font-semibold">Level {{ $commission->level }}</span>
                    </div>
                    @endif

                    @if($commission->percentage)
                    <div class="flex justify-between items-center pb-3 border-b border-white/20">
                        <span class="text-sm opacity-90">เปอร์เซ็นต์</span>
                        <span class="font-semibold">{{ $commission->percentage }}%</span>
                    </div>
                    @endif

                    <div class="flex justify-between items-center pb-3 border-b border-white/20">
                        <span class="text-sm opacity-90">จำนวนเงิน</span>
                        <span class="font-bold text-xl">฿{{ number_format($commission->commission_amount, 2) }}</span>
                    </div>

                    @if($commission->pv_amount)
                    <div class="flex justify-between items-center">
                        <span class="text-sm opacity-90">PV</span>
                        <span class="font-semibold">{{ number_format($commission->pv_amount, 2) }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Wallet Transaction -->
            @if($commission->walletTransaction)
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-wallet text-green-600 dark:text-green-400 mr-2"></i>
                    ธุรกรรมกระเป๋าเงิน
                </h3>

                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Transaction ID</span>
                        <span class="font-mono text-gray-900 dark:text-white">#{{ $commission->walletTransaction->id }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">จำนวน</span>
                        <span class="font-bold text-green-600 dark:text-green-400">+฿{{ number_format($commission->walletTransaction->amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">วันที่</span>
                        <span class="text-gray-900 dark:text-white">{{ $commission->walletTransaction->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>
            @endif

            <!-- Actions -->
            <div class="bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 rounded-2xl shadow-xl p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-tools mr-2"></i>
                    การจัดการ
                </h3>

                <div class="space-y-2">
                    <a href="{{ route('admin.mlm.members.show', $commission->member) }}"
                       class="block w-full bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-950 text-gray-900 dark:text-white px-4 py-3 rounded-xl transition-colors text-center font-medium">
                        <i class="fas fa-user mr-2"></i>
                        ดูข้อมูลสมาชิก
                    </a>
                    @if($commission->fromMember)
                    <a href="{{ route('admin.mlm.members.show', $commission->fromMember) }}"
                       class="block w-full bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-950 text-gray-900 dark:text-white px-4 py-3 rounded-xl transition-colors text-center font-medium">
                        <i class="fas fa-user-friends mr-2"></i>
                        ดูผู้สร้างรายได้
                    </a>
                    @endif
                    <a href="{{ route('admin.mlm.genealogy.index', ['member' => $commission->member->id]) }}"
                       class="block w-full bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-950 text-gray-900 dark:text-white px-4 py-3 rounded-xl transition-colors text-center font-medium">
                        <i class="fas fa-project-diagram mr-2"></i>
                        ดูเครือข่าย
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full p-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">ปฏิเสธคอมมิชชั่น</h3>

        <form action="{{ route('admin.mlm.commissions.reject', $commission) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เหตุผล</label>
                <textarea name="reason" rows="4" required
                          class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg"
                          placeholder="ระบุเหตุผลในการปฏิเสธ..."></textarea>
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="hideRejectModal()"
                        class="flex-1 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-white px-4 py-2 rounded-lg font-medium">
                    ยกเลิก
                </button>
                <button type="submit"
                        class="flex-1 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium">
                    ปฏิเสธ
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showRejectModal() {
    document.getElementById('rejectModal').classList.remove('hidden');
}

function hideRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
}
</script>
@endsection
