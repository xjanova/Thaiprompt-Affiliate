@extends('layouts.admin')

@section('title', 'Affiliate Tree - ' . $affiliate->user->name)

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.affiliates.index') }}" class="text-indigo-600 hover:text-indigo-900">
        ← กลับไปรายการ Affiliates
    </a>
</div>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h2 class="text-2xl font-bold text-gray-900 mb-4">Affiliate Tree</h2>

    <!-- Root Affiliate -->
    <div class="border-l-4 border-indigo-500 pl-4 mb-6">
        <div class="bg-indigo-50 p-4 rounded-lg">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ $affiliate->user->name }}</h3>
                    <p class="text-sm text-gray-600">{{ $affiliate->user->email }}</p>
                    <div class="mt-2 flex gap-4">
                        <span class="text-sm">
                            <strong>รหัสแนะนำ:</strong>
                            <code class="px-2 py-1 bg-white rounded">{{ $affiliate->referral_code }}</code>
                        </span>
                        <span class="text-sm">
                            <strong>ระดับ:</strong> {{ $affiliate->level }}
                        </span>
                        <span class="text-sm">
                            <strong>Total Referrals:</strong> {{ $affiliate->total_referrals }}
                        </span>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold text-indigo-600">
                        {{ number_format($affiliate->total_earnings, 2) }}฿
                    </div>
                    <p class="text-sm text-gray-600">Total Earnings</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Level 1 - Direct Referrals -->
    @if($affiliate->children->count() > 0)
        <div class="ml-8">
            <h4 class="text-lg font-semibold text-gray-700 mb-3">Direct Referrals (Level 1)</h4>
            @foreach($affiliate->children as $child)
                <div class="border-l-4 border-blue-400 pl-4 mb-4">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <h5 class="font-semibold text-gray-900">{{ $child->user->name }}</h5>
                                <p class="text-sm text-gray-600">{{ $child->user->email }}</p>
                                <div class="mt-1 flex gap-3 text-sm">
                                    <span>
                                        <strong>รหัส:</strong>
                                        <code class="px-1 py-0.5 bg-white rounded text-xs">{{ $child->referral_code }}</code>
                                    </span>
                                    <span><strong>Referrals:</strong> {{ $child->total_referrals }}</span>
                                    <span class="px-2 py-0.5 rounded text-xs
                                        @if($child->status === 'active') bg-green-100 text-green-800
                                        @elseif($child->status === 'inactive') bg-gray-100 text-gray-800
                                        @else bg-red-100 text-red-800
                                        @endif">
                                        {{ $child->status }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-bold text-blue-600">
                                    {{ number_format($child->total_earnings, 2) }}฿
                                </div>
                                <a href="{{ route('admin.affiliates.tree', $child) }}"
                                   class="text-sm text-blue-600 hover:text-blue-900">
                                    ดู Tree →
                                </a>
                            </div>
                        </div>

                        <!-- Level 2 - Sub Referrals -->
                        @if($child->children && $child->children->count() > 0)
                            <div class="mt-3 ml-4 border-l-2 border-green-300 pl-3">
                                <p class="text-xs font-semibold text-gray-600 mb-2">Sub-Referrals (Level 2):</p>
                                @foreach($child->children as $grandchild)
                                    <div class="bg-green-50 p-2 rounded mb-2">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <span class="text-sm font-medium">{{ $grandchild->user->name }}</span>
                                                <span class="text-xs text-gray-500 ml-2">{{ $grandchild->user->email }}</span>
                                            </div>
                                            <div class="text-sm text-green-700 font-semibold">
                                                {{ number_format($grandchild->total_earnings, 2) }}฿
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-8 text-gray-500">
            <p>ยังไม่มีผู้ใช้ที่ถูกแนะนำโดย affiliate นี้</p>
        </div>
    @endif
</div>

<!-- Statistics Summary -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-sm font-medium text-gray-600 mb-2">Direct Referrals</h3>
        <p class="text-3xl font-bold text-indigo-600">{{ $affiliate->children->count() }}</p>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-sm font-medium text-gray-600 mb-2">Total Network</h3>
        <p class="text-3xl font-bold text-blue-600">{{ $affiliate->total_referrals }}</p>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-sm font-medium text-gray-600 mb-2">Total Earnings</h3>
        <p class="text-3xl font-bold text-green-600">{{ number_format($affiliate->total_earnings, 2) }}฿</p>
    </div>
</div>
@endsection
