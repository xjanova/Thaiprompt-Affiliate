@extends('layouts.admin')

@section('title', 'รายละเอียด Affiliate - ' . $affiliate->user->name)

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.affiliates.index') }}" class="text-indigo-600 hover:text-indigo-900">
        ← กลับไปรายการ Affiliates
    </a>
</div>

<!-- Affiliate Header Card -->
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">{{ $affiliate->user->name }}</h2>
            <p class="text-gray-600">{{ $affiliate->user->email }}</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.affiliates.tree', $affiliate) }}"
               class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                ดู Tree
            </a>
            <a href="{{ route('admin.affiliates.edit', $affiliate) }}"
               class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                แก้ไข
            </a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-indigo-50 p-4 rounded-lg">
            <h3 class="text-sm font-medium text-gray-600 mb-1">รหัสแนะนำ</h3>
            <code class="text-lg font-mono font-bold text-indigo-600">{{ $affiliate->referral_code }}</code>
        </div>

        <div class="bg-blue-50 p-4 rounded-lg">
            <h3 class="text-sm font-medium text-gray-600 mb-1">ระดับ</h3>
            <p class="text-2xl font-bold text-blue-600">{{ $affiliate->level }}</p>
        </div>

        <div class="bg-green-50 p-4 rounded-lg">
            <h3 class="text-sm font-medium text-gray-600 mb-1">Total Referrals</h3>
            <p class="text-2xl font-bold text-green-600">{{ $affiliate->total_referrals }}</p>
        </div>

        <div class="bg-yellow-50 p-4 rounded-lg">
            <h3 class="text-sm font-medium text-gray-600 mb-1">Total Earnings</h3>
            <p class="text-2xl font-bold text-yellow-600">{{ number_format($affiliate->total_earnings, 2) }}฿</p>
        </div>
    </div>
</div>

<!-- Details Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <!-- Affiliate Information -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">ข้อมูล Affiliate</h3>
        <dl class="space-y-3">
            <div>
                <dt class="text-sm font-medium text-gray-600">สถานะ</dt>
                <dd class="mt-1">
                    <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full
                        @if($affiliate->status === 'active') bg-green-100 text-green-800
                        @elseif($affiliate->status === 'inactive') bg-gray-100 text-gray-800
                        @else bg-red-100 text-red-800
                        @endif">
                        {{ ucfirst($affiliate->status) }}
                    </span>
                </dd>
            </div>

            @if($affiliate->parent)
            <div>
                <dt class="text-sm font-medium text-gray-600">แนะนำโดย</dt>
                <dd class="mt-1 text-sm text-gray-900">
                    <a href="{{ route('admin.affiliates.show', $affiliate->parent) }}"
                       class="text-indigo-600 hover:text-indigo-900">
                        {{ $affiliate->parent->user->name }} ({{ $affiliate->parent->referral_code }})
                    </a>
                </dd>
            </div>
            @endif

            <div>
                <dt class="text-sm font-medium text-gray-600">วันที่สมัคร</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $affiliate->created_at->format('d/m/Y H:i') }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-600">อัพเดทล่าสุด</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $affiliate->updated_at->format('d/m/Y H:i') }}</dd>
            </div>
        </dl>
    </div>

    <!-- User Information -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">ข้อมูลผู้ใช้</h3>
        <dl class="space-y-3">
            <div>
                <dt class="text-sm font-medium text-gray-600">ชื่อ</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $affiliate->user->name }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-600">อีเมล</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $affiliate->user->email }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-600">Role</dt>
                <dd class="mt-1">
                    <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded">
                        {{ $affiliate->user->role ?? 'user' }}
                    </span>
                </dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-600">สร้างบัญชีเมื่อ</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $affiliate->user->created_at->format('d/m/Y H:i') }}</dd>
            </div>
        </dl>
    </div>
</div>

<!-- Commission History -->
<div class="bg-white rounded-lg shadow-md p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">ประวัติคอมมิชชั่น</h3>

    @if($affiliate->commissions->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">วันที่</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ยอดขาย</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">เปอร์เซ็นต์</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">คอมมิชชั่น</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($affiliate->commissions->take(10) as $commission)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $commission->created_at->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ number_format($commission->sale_amount ?? 0, 2) }}฿
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $commission->commission_rate ?? 0 }}%
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">
                                {{ number_format($commission->amount, 2) }}฿
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                    @if($commission->status === 'paid') bg-green-100 text-green-800
                                    @elseif($commission->status === 'pending') bg-yellow-100 text-yellow-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ ucfirst($commission->status) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($affiliate->commissions->count() > 10)
            <p class="mt-4 text-sm text-gray-600 text-center">
                แสดง 10 รายการล่าสุด จากทั้งหมด {{ $affiliate->commissions->count() }} รายการ
            </p>
        @endif
    @else
        <div class="text-center py-8 text-gray-500">
            <p>ยังไม่มีคอมมิชชั่น</p>
        </div>
    @endif
</div>

<!-- Direct Referrals -->
@if($affiliate->children->count() > 0)
<div class="bg-white rounded-lg shadow-md p-6 mt-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">
        ผู้ใช้ที่แนะนำโดยตรง ({{ $affiliate->children->count() }})
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($affiliate->children as $child)
            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="font-semibold text-gray-900">{{ $child->user->name }}</h4>
                    <span class="text-xs px-2 py-1 rounded
                        @if($child->status === 'active') bg-green-100 text-green-800
                        @else bg-gray-100 text-gray-800
                        @endif">
                        {{ $child->status }}
                    </span>
                </div>
                <p class="text-sm text-gray-600 mb-2">{{ $child->user->email }}</p>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">Referrals: <strong>{{ $child->total_referrals }}</strong></span>
                    <a href="{{ route('admin.affiliates.show', $child) }}"
                       class="text-indigo-600 hover:text-indigo-900">
                        ดูรายละเอียด →
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif

@endsection
