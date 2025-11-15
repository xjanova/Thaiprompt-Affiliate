@extends('layouts.admin')

@section('title', 'รายละเอียดสมาชิก MLM')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-user-circle text-purple-600 dark:text-purple-400"></i>
                รายละเอียดสมาชิก MLM
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $member->user->name }} ({{ $member->member_code }})</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.mlm.members.genealogy', $member) }}"
               class="bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 dark:from-purple-500 dark:to-pink-500 dark:hover:from-purple-600 dark:hover:to-pink-600 text-white px-6 py-3 rounded-lg flex items-center gap-2 shadow-lg hover:shadow-xl transition-all duration-200">
                <i class="fas fa-sitemap"></i>
                ดูผังสายงาน
            </a>
            <a href="{{ route('admin.mlm.members.index') }}"
               class="bg-gray-600 hover:bg-gray-700 dark:bg-gray-500 dark:hover:bg-gray-600 text-white px-6 py-3 rounded-lg flex items-center gap-2 shadow-lg hover:shadow-xl transition-all duration-200">
                <i class="fas fa-arrow-left"></i>
                กลับ
            </a>
        </div>
    </div>

    <!-- Member Info Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
        <div class="flex flex-col md:flex-row items-start md:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-20 h-20 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white text-2xl font-bold shadow-lg">
                    {{ strtoupper(substr($member->user->name, 0, 2)) }}
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $member->user->name }}</h2>
                    <p class="text-gray-600 dark:text-gray-400 flex items-center gap-2">
                        <i class="fas fa-envelope text-sm"></i>
                        {{ $member->user->email }}
                    </p>
                    <div class="flex items-center gap-3 mt-2 flex-wrap">
                        <span class="inline-flex items-center px-3 py-1 text-xs rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400 font-medium">
                            <i class="fas fa-layer-group mr-1"></i>
                            {{ $member->plan->display_name }}
                        </span>
                        <span class="inline-flex items-center px-3 py-1 text-xs rounded-full font-semibold
                            {{ $member->status === 'active' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400' : '' }}
                            {{ $member->status === 'inactive' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400' : '' }}
                            {{ $member->status === 'suspended' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : '' }}">
                            <i class="fas fa-circle text-xs mr-1"></i>
                            {{ ucfirst($member->status) }}
                        </span>
                        @if($member->is_qualified)
                            <span class="inline-flex items-center px-3 py-1 text-xs rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 font-medium">
                                <i class="fas fa-check-circle mr-1"></i>
                                Qualified
                            </span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="text-left md:text-right">
                <p class="text-sm text-gray-600 dark:text-gray-400">Member Code</p>
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $member->member_code }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1 flex items-center gap-1 md:justify-end">
                    <i class="fas fa-calendar-alt"></i>
                    เข้าร่วมเมื่อ {{ $member->joined_at->format('d/m/Y') }}
                </p>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm">PV รวม</p>
                    <h3 class="text-3xl font-bold mt-1">{{ number_format($member->total_pv, 2) }}</h3>
                    <p class="text-blue-100 text-xs mt-1">Point Value</p>
                </div>
                <div class="bg-white/20 rounded-full p-3">
                    <i class="fas fa-chart-line text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 dark:from-emerald-600 dark:to-emerald-700 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-emerald-100 text-sm">รายได้รวม</p>
                    <h3 class="text-3xl font-bold mt-1">฿{{ number_format($member->total_earnings, 2) }}</h3>
                    <p class="text-emerald-100 text-xs mt-1">Total Earnings</p>
                </div>
                <div class="bg-white/20 rounded-full p-3">
                    <i class="fas fa-dollar-sign text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 dark:from-purple-600 dark:to-purple-700 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm">ผู้แนะนำโดยตรง</p>
                    <h3 class="text-3xl font-bold mt-1">{{ number_format($member->total_direct_referrals) }}</h3>
                    <p class="text-purple-100 text-xs mt-1">Direct Referrals</p>
                </div>
                <div class="bg-white/20 rounded-full p-3">
                    <i class="fas fa-user-plus text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-pink-500 to-pink-600 dark:from-pink-600 dark:to-pink-700 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-pink-100 text-sm">ผู้แนะนำทั้งหมด</p>
                    <h3 class="text-3xl font-bold mt-1">{{ number_format($member->total_team_members) }}</h3>
                    <p class="text-pink-100 text-xs mt-1">Total Team</p>
                </div>
                <div class="bg-white/20 rounded-full p-3">
                    <i class="fas fa-users text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Relationship Info -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Unilevel Structure -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <i class="fas fa-network-wired text-blue-600 dark:text-blue-400"></i>
                โครงสร้าง Unilevel
            </h3>
            <div class="space-y-3">
                @if($member->unilevelSponsor)
                    <div class="flex items-center justify-between p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-800">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">ผู้สนับสนุน</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $member->unilevelSponsor->user->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-500">{{ $member->unilevelSponsor->member_code }}</p>
                        </div>
                        <a href="{{ route('admin.mlm.members.show', $member->unilevelSponsor) }}"
                           class="px-3 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-lg transition">
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                @else
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
                        <p class="text-sm text-gray-500 dark:text-gray-400 italic flex items-center gap-2">
                            <i class="fas fa-info-circle"></i>
                            ไม่มีผู้สนับสนุน (Root Member)
                        </p>
                    </div>
                @endif

                <div class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg border border-blue-100 dark:border-blue-800">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">ระดับ Unilevel</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 flex items-center gap-2">
                        <i class="fas fa-layer-group"></i>
                        Level {{ $member->unilevel_level }}
                    </p>
                </div>

                <div class="p-4 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-lg border border-purple-100 dark:border-purple-800">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">ลูกทีมโดยตรง</p>
                    <p class="text-2xl font-bold text-purple-600 dark:text-purple-400 flex items-center gap-2">
                        <i class="fas fa-user-friends"></i>
                        {{ $member->unilevelChildren->count() }} คน
                    </p>
                </div>
            </div>
        </div>

        <!-- Binary Structure -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <i class="fas fa-code-branch text-purple-600 dark:text-purple-400"></i>
                โครงสร้าง Binary
            </h3>
            <div class="space-y-3">
                @if($member->binaryParent)
                    <div class="flex items-center justify-between p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-100 dark:border-purple-800">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Parent</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $member->binaryParent->user->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-500">{{ $member->binaryParent->member_code }}</p>
                        </div>
                        <span class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-full {{ $member->binary_position === 'left' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-pink-100 text-pink-800 dark:bg-pink-900/30 dark:text-pink-400' }}">
                            <i class="fas fa-{{ $member->binary_position === 'left' ? 'arrow-left' : 'arrow-right' }} mr-1"></i>
                            {{ ucfirst($member->binary_position) }}
                        </span>
                    </div>
                @else
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
                        <p class="text-sm text-gray-500 dark:text-gray-400 italic flex items-center gap-2">
                            <i class="fas fa-info-circle"></i>
                            ไม่มี Parent (Root Member)
                        </p>
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-3">
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-800">
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1 flex items-center gap-1">
                            <i class="fas fa-arrow-left"></i>
                            Left Leg PV
                        </p>
                        <p class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($member->left_leg_pv, 2) }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">{{ $member->left_leg_members }} สมาชิก</p>
                    </div>
                    <div class="p-4 bg-pink-50 dark:bg-pink-900/20 rounded-lg border border-pink-100 dark:border-pink-800">
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1 flex items-center gap-1">
                            <i class="fas fa-arrow-right"></i>
                            Right Leg PV
                        </p>
                        <p class="text-xl font-bold text-pink-600 dark:text-pink-400">{{ number_format($member->right_leg_pv, 2) }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">{{ $member->right_leg_members }} สมาชิก</p>
                    </div>
                </div>

                @if($member->carry_forward_left > 0 || $member->carry_forward_right > 0)
                    <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-700">
                        <p class="text-sm text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                            <i class="fas fa-forward text-yellow-600 dark:text-yellow-400"></i>
                            Carry Forward PV
                        </p>
                        <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                            <span>Left: <strong class="text-gray-900 dark:text-white">{{ number_format($member->carry_forward_left, 2) }}</strong></span>
                            <span>Right: <strong class="text-gray-900 dark:text-white">{{ number_format($member->carry_forward_right, 2) }}</strong></span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Recent Commissions -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg mb-6">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-money-bill-wave text-emerald-600 dark:text-emerald-400"></i>
                    คอมมิชชั่นล่าสุด
                </h3>
                <a href="{{ route('admin.mlm.commissions.index', ['member_id' => $member->id]) }}"
                   class="text-blue-600 dark:text-blue-400 hover:underline text-sm font-medium flex items-center gap-1">
                    ดูทั้งหมด
                    <i class="fas fa-arrow-right text-xs"></i>
                </a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-700">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-calendar mr-1"></i>วันที่
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-tag mr-1"></i>ประเภท
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-user mr-1"></i>จาก
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-dollar-sign mr-1"></i>จำนวน
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-info-circle mr-1"></i>สถานะ
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($member->commissions as $commission)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                            {{ $commission->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full
                                {{ $commission->type === 'unilevel' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                                {{ $commission->type === 'binary' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400' : '' }}
                                {{ $commission->type === 'direct_sponsor' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400' : '' }}">
                                {{ ucfirst($commission->type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                            @if($commission->fromMember)
                                {{ $commission->fromMember->user->name }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-emerald-600 dark:text-emerald-400">
                            ฿{{ number_format($commission->commission_amount, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full
                                {{ $commission->status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}
                                {{ $commission->status === 'approved' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400' : '' }}
                                {{ $commission->status === 'paid' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : '' }}">
                                {{ ucfirst($commission->status) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            <i class="fas fa-inbox text-4xl mb-3"></i>
                            <p class="text-lg">ยังไม่มีคอมมิชชั่น</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent PV Transactions -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-exchange-alt text-purple-600 dark:text-purple-400"></i>
                ธุรกรรม PV ล่าสุด
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-700">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-calendar mr-1"></i>วันที่
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-tag mr-1"></i>ประเภท
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-chart-bar mr-1"></i>PV
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-dollar-sign mr-1"></i>ยอดขาย
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-file-alt mr-1"></i>คำอธิบาย
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($member->pvTransactions as $transaction)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                            {{ $transaction->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full
                                {{ $transaction->transaction_type === 'purchase' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400' : '' }}
                                {{ $transaction->transaction_type === 'adjustment' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}">
                                {{ ucfirst($transaction->transaction_type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-purple-600 dark:text-purple-400">
                            {{ number_format($transaction->pv_amount, 2) }} PV
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-100">
                            ฿{{ number_format($transaction->sales_amount, 2) }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            {{ $transaction->description }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            <i class="fas fa-inbox text-4xl mb-3"></i>
                            <p class="text-lg">ยังไม่มีธุรกรรม PV</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
