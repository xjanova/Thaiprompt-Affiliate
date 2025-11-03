@extends('layouts.admin')

@section('title', 'จัดการแผน MLM')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">จัดการแผน MLM</h1>
            <p class="text-gray-600 mt-1">จัดการแผน MLM (Unilevel, Binary, Hybrid)</p>
        </div>
        <a href="{{ route('admin.mlm.plans.create') }}"
           class="bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white px-6 py-2.5 rounded-lg shadow-lg transition-all duration-200 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            สร้างแผนใหม่
        </a>
    </div>

    <!-- Plans List -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($plans as $plan)
        <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-2xl transition-all duration-300 border-2 {{ $plan->is_active ? 'border-green-400' : 'border-gray-300' }}">
            <!-- Plan Header -->
            <div class="p-6" style="background: linear-gradient(135deg, {{ $plan->color }} 0%, {{ $plan->color }}dd 100%);">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-xl font-bold text-white">{{ $plan->display_name }}</h3>
                        <p class="text-white/80 text-sm mt-1">{{ ucfirst($plan->type) }}</p>
                    </div>
                    @if($plan->is_default)
                        <span class="bg-white/20 backdrop-blur-sm text-white text-xs px-3 py-1 rounded-full">
                            ค่าเริ่มต้น
                        </span>
                    @endif
                </div>

                <div class="flex items-center gap-2 text-white/90 text-sm">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                    </svg>
                    <span>{{ $plan->members_count ?? 0 }} สมาชิก</span>
                </div>
            </div>

            <!-- Plan Details -->
            <div class="p-6 space-y-3">
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-600">PV Rate:</span>
                    <span class="font-semibold text-gray-800">1 THB = {{ $plan->global_pv_rate }} PV</span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-600">Commission/PV:</span>
                    <span class="font-semibold text-gray-800">{{ $plan->global_commission_per_pv }} THB</span>
                </div>

                @if($plan->type === 'unilevel' || $plan->type === 'hybrid')
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-600">Unilevel Levels:</span>
                    <span class="font-semibold text-gray-800">{{ count($plan->unilevel_levels ?? []) }} ระดับ</span>
                </div>
                @endif

                @if($plan->type === 'binary' || $plan->type === 'hybrid')
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-600">Binary Commission:</span>
                    <span class="font-semibold text-gray-800">{{ $plan->binary_pair_commission }} THB/คู่</span>
                </div>
                @endif

                <!-- Status Badge -->
                <div class="pt-3 border-t border-gray-200">
                    <span class="inline-flex items-center gap-2 text-sm px-3 py-1 rounded-full {{ $plan->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        <span class="w-2 h-2 rounded-full {{ $plan->is_active ? 'bg-green-500' : 'bg-red-500' }}"></span>
                        {{ $plan->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
                    </span>
                </div>
            </div>

            <!-- Actions -->
            <div class="px-6 pb-6 flex gap-2">
                <a href="{{ route('admin.mlm.plans.edit', $plan) }}"
                   class="flex-1 bg-blue-500 hover:bg-blue-600 text-white text-center px-4 py-2 rounded-lg transition-colors">
                    แก้ไข
                </a>
                <button onclick="togglePlanStatus({{ $plan->id }})"
                        class="flex-1 {{ $plan->is_active ? 'bg-yellow-500 hover:bg-yellow-600' : 'bg-green-500 hover:bg-green-600' }} text-white px-4 py-2 rounded-lg transition-colors">
                    {{ $plan->is_active ? 'ปิด' : 'เปิด' }}
                </button>
                @if(!$plan->is_default)
                <button onclick="setDefaultPlan({{ $plan->id }})"
                        class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg transition-colors">
                    ตั้งเป็นค่าเริ่มต้น
                </button>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    @if($plans->isEmpty())
    <div class="bg-white rounded-xl shadow-lg p-12 text-center">
        <div class="text-gray-400 text-6xl mb-4">📋</div>
        <h3 class="text-xl font-semibold text-gray-700 mb-2">ยังไม่มีแผน MLM</h3>
        <p class="text-gray-500 mb-6">สร้างแผน MLM แรกของคุณเพื่อเริ่มต้นใช้งาน</p>
        <a href="{{ route('admin.mlm.plans.create') }}"
           class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            สร้างแผนแรก
        </a>
    </div>
    @endif
</div>

<script>
function togglePlanStatus(planId) {
    if (confirm('คุณต้องการเปลี่ยนสถานะแผนนี้หรือไม่?')) {
        fetch(`/admin/mlm/plans/${planId}/toggle-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            }
        });
    }
}

function setDefaultPlan(planId) {
    if (confirm('ตั้งเป็นแผนค่าเริ่มต้นหรือไม่?')) {
        fetch(`/admin/mlm/plans/${planId}/set-default`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            window.location.reload();
        });
    }
}
</script>
@endsection
