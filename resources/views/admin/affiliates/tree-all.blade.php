@extends('layouts.admin')

@section('title', 'Affiliate Tree - ทั้งหมด')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold mb-1">🌳 Affiliate Tree ทั้งหมด</h1>
                <p class="text-indigo-100">โครงสร้างสายงานทั้งหมด - ลากวางเพื่อย้ายสายงาน</p>
            </div>
            <a href="{{ route('admin.affiliates.index') }}" class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition">
                ← กลับ
            </a>
        </div>
    </div>

    <!-- Stats -->
    @php
        $totalAffiliates = App\Models\Affiliate::count();
        $activeAffiliates = App\Models\Affiliate::where('status', 'active')->count();
        $totalEarnings = App\Models\Affiliate::sum('total_earnings');
        $totalReferrals = App\Models\Affiliate::sum('total_referrals');
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-indigo-500 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-gray-600 mb-1">Root Affiliates</div>
                    <div class="text-3xl font-bold text-indigo-600">{{ $affiliates->count() }}</div>
                </div>
                <div class="text-4xl">🌱</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-purple-500 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-gray-600 mb-1">เครือข่ายทั้งหมด</div>
                    <div class="text-3xl font-bold text-purple-600">{{ number_format($totalAffiliates) }}</div>
                    <div class="text-xs text-green-600 mt-1">{{ $activeAffiliates }} ใช้งาน</div>
                </div>
                <div class="text-4xl">👥</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-gray-600 mb-1">รายได้รวม</div>
                    <div class="text-3xl font-bold text-green-600">฿{{ number_format($totalEarnings, 2) }}</div>
                </div>
                <div class="text-4xl">💰</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-gray-600 mb-1">Referrals ทั้งหมด</div>
                    <div class="text-3xl font-bold text-blue-600">{{ number_format($totalReferrals) }}</div>
                </div>
                <div class="text-4xl">🌐</div>
            </div>
        </div>
    </div>

    <!-- Tree Visualization -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="mb-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
            <div class="flex items-start gap-3">
                <div class="text-2xl">💡</div>
                <div class="flex-1 text-sm text-blue-900">
                    <strong>วิธีใช้งาน:</strong>
                    <ul class="list-disc list-inside mt-2 space-y-1">
                        <li>ลากและวาง Affiliate card เพื่อย้ายสายงาน</li>
                        <li>การ์ดจะเปลี่ยนสีเมื่อสามารถวางได้</li>
                        <li>ระบบจะตรวจสอบความถูกต้อง (ไม่สามารถย้ายไปยังลูกสายของตัวเอง)</li>
                    </ul>
                </div>
            </div>
        </div>

        @if($affiliates->count() > 0)
            <div class="space-y-6">
                @foreach($affiliates as $affiliate)
                    <div class="border-2 border-gray-200 rounded-xl p-4">
                        <!-- Root Node -->
                        @include('admin.affiliates.partials.tree-node', ['node' => $affiliate, 'level' => 0])
                        
                        <!-- Children -->
                        @if($affiliate->children && $affiliate->children->count() > 0)
                            <div class="ml-8 mt-4 space-y-4 border-l-2 border-gray-200 pl-4">
                                @foreach($affiliate->children as $child)
                                    @include('admin.affiliates.partials.tree-node', ['node' => $child, 'level' => 1])
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12 text-gray-500">
                <div class="text-6xl mb-4">🌱</div>
                <p class="text-xl font-semibold mb-2">ยังไม่มี Affiliate</p>
                <p class="text-sm">เริ่มสร้าง Affiliate แรกของคุณเลย!</p>
                <a href="{{ route('admin.affiliates.create') }}" class="inline-block mt-4 px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                    + สร้าง Affiliate
                </a>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
// Drag and Drop functionality
document.addEventListener('DOMContentLoaded', function() {
    const affiliateNodes = document.querySelectorAll('[data-affiliate-id]');

    affiliateNodes.forEach(node => {
        node.draggable = true;

        node.addEventListener('dragstart', function(e) {
            e.dataTransfer.setData('text/plain', this.dataset.affiliateId);
            this.classList.add('opacity-50', 'scale-95');
        });

        node.addEventListener('dragend', function(e) {
            this.classList.remove('opacity-50', 'scale-95');
        });

        node.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('ring-4', 'ring-green-400', 'scale-105');
        });

        node.addEventListener('dragleave', function(e) {
            this.classList.remove('ring-4', 'ring-green-400', 'scale-105');
        });

        node.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('ring-4', 'ring-green-400', 'scale-105');

            const draggedId = e.dataTransfer.getData('text/plain');
            const targetId = this.dataset.affiliateId;

            if (draggedId !== targetId) {
                if (confirm('คุณต้องการย้าย Affiliate นี้ให้เป็นลูกสายของ ID: ' + targetId + ' หรือไม่?')) {
                    moveAffiliate(draggedId, targetId);
                }
            }
        });
    });

    console.log('✅ Tree view loaded successfully! Found ' + affiliateNodes.length + ' affiliate nodes');
});

function moveAffiliate(affiliateId, newParentId) {
    // Show loading overlay
    const overlay = document.createElement('div');
    overlay.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    overlay.innerHTML = '<div class="bg-white rounded-lg p-6 shadow-xl"><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto"></div><p class="mt-4 text-gray-700">กำลังย้ายสายงาน...</p></div>';
    document.body.appendChild(overlay);

    fetch(`/admin/affiliates/${affiliateId}/move`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            new_parent_id: newParentId
        })
    })
    .then(response => response.json())
    .then(data => {
        overlay.remove();
        if (data.success) {
            showNotification('success', data.message);
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification('error', data.message);
        }
    })
    .catch(error => {
        overlay.remove();
        showNotification('error', 'เกิดข้อผิดพลาด: ' + error.message);
    });
}

function showNotification(type, message) {
    const notification = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
    notification.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-4 rounded-lg shadow-lg z-50 animate-slide-in`;
    notification.innerHTML = `
        <div class="flex items-center gap-3">
            <span class="text-2xl">${type === 'success' ? '✅' : '❌'}</span>
            <span>${message}</span>
        </div>
    `;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
}
</script>

<style>
@keyframes slide-in {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.animate-slide-in {
    animation: slide-in 0.3s ease-out;
}
</style>
@endpush
@endsection
