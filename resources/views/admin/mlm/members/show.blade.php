@extends('layouts.admin')

@section('title', 'รายละเอียดสมาชิก MLM')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">รายละเอียดสมาชิก MLM</h1>
            <p class="text-gray-600 mt-1">{{ $member->user->name }} ({{ $member->member_code }})</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.mlm.members.genealogy', $member) }}"
               class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
                ดูผังสายงาน
            </a>
            <a href="{{ route('admin.mlm.members.index') }}"
               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                กลับ
            </a>
        </div>
    </div>

    <!-- Member Info Card -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
        <div class="flex items-start justify-between">
            <div class="flex items-center gap-4">
                <div class="w-20 h-20 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white text-2xl font-bold">
                    {{ strtoupper(substr($member->user->name, 0, 2)) }}
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">{{ $member->user->name }}</h2>
                    <p class="text-gray-600">{{ $member->user->email }}</p>
                    <div class="flex items-center gap-3 mt-2">
                        <span class="px-3 py-1 text-xs rounded-full bg-purple-100 text-purple-800 font-medium">
                            {{ $member->plan->display_name }}
                        </span>
                        <span class="px-3 py-1 text-xs rounded-full font-medium
                            {{ $member->status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $member->status === 'inactive' ? 'bg-gray-100 text-gray-800' : '' }}
                            {{ $member->status === 'suspended' ? 'bg-red-100 text-red-800' : '' }}">
                            {{ ucfirst($member->status) }}
                        </span>
                        @if($member->is_qualified)
                            <span class="px-3 py-1 text-xs rounded-full bg-blue-100 text-blue-800 font-medium">
                                ✓ Qualified
                            </span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-600">Member Code</p>
                <p class="text-2xl font-bold text-purple-600">{{ $member->member_code }}</p>
                <p class="text-xs text-gray-500 mt-1">เข้าร่วมเมื่อ {{ $member->joined_at->format('d/m/Y') }}</p>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
            <p class="text-sm opacity-90">PV รวม</p>
            <h3 class="text-3xl font-bold mt-2">{{ number_format($member->total_pv, 2) }}</h3>
            <p class="text-xs mt-1 opacity-90">Point Value</p>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
            <p class="text-sm opacity-90">รายได้รวม</p>
            <h3 class="text-3xl font-bold mt-2">฿{{ number_format($member->total_earnings, 2) }}</h3>
            <p class="text-xs mt-1 opacity-90">Total Earnings</p>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
            <p class="text-sm opacity-90">ผู้แนะนำโดยตรง</p>
            <h3 class="text-3xl font-bold mt-2">{{ number_format($member->total_direct_referrals) }}</h3>
            <p class="text-xs mt-1 opacity-90">Direct Referrals</p>
        </div>

        <div class="bg-gradient-to-br from-pink-500 to-pink-600 rounded-xl shadow-lg p-6 text-white">
            <p class="text-sm opacity-90">ผู้แนะนำทั้งหมด</p>
            <h3 class="text-3xl font-bold mt-2">{{ number_format($member->total_team_members) }}</h3>
            <p class="text-xs mt-1 opacity-90">Total Team</p>
        </div>
    </div>

    <!-- Relationship Info -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Unilevel Structure -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
                โครงสร้าง Unilevel
            </h3>
            <div class="space-y-3">
                @if($member->unilevelSponsor)
                    <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                        <div>
                            <p class="text-sm text-gray-600">ผู้สนับสนุน</p>
                            <p class="font-semibold text-gray-800">{{ $member->unilevelSponsor->user->name }}</p>
                            <p class="text-xs text-gray-500">{{ $member->unilevelSponsor->member_code }}</p>
                        </div>
                        <a href="{{ route('admin.mlm.members.show', $member->unilevelSponsor) }}"
                           class="text-blue-600 hover:text-blue-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                @else
                    <p class="text-sm text-gray-500 italic">ไม่มีผู้สนับสนุน (Root Member)</p>
                @endif

                <div class="p-3 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-600">ระดับ Unilevel</p>
                    <p class="text-xl font-bold text-gray-800">Level {{ $member->unilevel_level }}</p>
                </div>

                <div class="p-3 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-600">ลูกทีมโดยตรง</p>
                    <p class="text-xl font-bold text-gray-800">{{ $member->unilevelChildren->count() }} คน</p>
                </div>
            </div>
        </div>

        <!-- Binary Structure -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                โครงสร้าง Binary
            </h3>
            <div class="space-y-3">
                @if($member->binaryParent)
                    <div class="flex items-center justify-between p-3 bg-purple-50 rounded-lg">
                        <div>
                            <p class="text-sm text-gray-600">Parent</p>
                            <p class="font-semibold text-gray-800">{{ $member->binaryParent->user->name }}</p>
                            <p class="text-xs text-gray-500">{{ $member->binaryParent->member_code }}</p>
                        </div>
                        <span class="px-2 py-1 text-xs rounded-full {{ $member->binary_position === 'left' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800' }}">
                            {{ ucfirst($member->binary_position) }}
                        </span>
                    </div>
                @else
                    <p class="text-sm text-gray-500 italic">ไม่มี Parent (Root Member)</p>
                @endif

                <div class="grid grid-cols-2 gap-3">
                    <div class="p-3 bg-blue-50 rounded-lg">
                        <p class="text-xs text-gray-600">Left Leg PV</p>
                        <p class="text-lg font-bold text-blue-600">{{ number_format($member->left_leg_pv, 2) }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $member->left_leg_members }} สมาชิก</p>
                    </div>
                    <div class="p-3 bg-pink-50 rounded-lg">
                        <p class="text-xs text-gray-600">Right Leg PV</p>
                        <p class="text-lg font-bold text-pink-600">{{ number_format($member->right_leg_pv, 2) }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $member->right_leg_members }} สมาชิก</p>
                    </div>
                </div>

                @if($member->carry_forward_left > 0 || $member->carry_forward_right > 0)
                    <div class="p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                        <p class="text-sm text-gray-600 mb-2">Carry Forward PV</p>
                        <div class="flex justify-between text-sm">
                            <span>Left: <strong>{{ number_format($member->carry_forward_left, 2) }}</strong></span>
                            <span>Right: <strong>{{ number_format($member->carry_forward_right, 2) }}</strong></span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Recent Commissions -->
    <div class="bg-white rounded-xl shadow-lg mb-6">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">คอมมิชชั่นล่าสุด</h3>
                <a href="{{ route('admin.mlm.commissions.index', ['member_id' => $member->id]) }}"
                   class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    ดูทั้งหมด →
                </a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">วันที่</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ประเภท</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">จาก</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">จำนวน</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($member->commissions as $commission)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $commission->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full
                                {{ $commission->type === 'unilevel' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $commission->type === 'binary' ? 'bg-purple-100 text-purple-800' : '' }}
                                {{ $commission->type === 'direct_sponsor' ? 'bg-green-100 text-green-800' : '' }}">
                                {{ ucfirst($commission->type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @if($commission->fromMember)
                                {{ $commission->fromMember->user->name }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">
                            ฿{{ number_format($commission->commission_amount, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full
                                {{ $commission->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $commission->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $commission->status === 'paid' ? 'bg-blue-100 text-blue-800' : '' }}">
                                {{ ucfirst($commission->status) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            ยังไม่มีคอมมิชชั่น
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent PV Transactions -->
    <div class="bg-white rounded-xl shadow-lg">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">ธุรกรรม PV ล่าสุด</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">วันที่</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ประเภท</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">PV</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ยอดขาย</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">คำอธิบาย</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($member->pvTransactions as $transaction)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $transaction->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full
                                {{ $transaction->transaction_type === 'purchase' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $transaction->transaction_type === 'adjustment' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                {{ ucfirst($transaction->transaction_type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-purple-600">
                            {{ number_format($transaction->pv_amount, 2) }} PV
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ฿{{ number_format($transaction->sales_amount, 2) }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ $transaction->description }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            ยังไม่มีธุรกรรม PV
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
