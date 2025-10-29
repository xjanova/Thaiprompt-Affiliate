@extends('layouts.admin')

@section('title', 'Affiliate Tree - ' . $affiliate->user->name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.affiliates.index') }}" class="text-gray-600 hover:text-gray-900">
                ← กลับ
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Affiliate Tree</h1>
                <p class="text-sm text-gray-600 mt-1">โครงสร้างสายงาน - ลากวางเพื่อย้ายสายงาน</p>
            </div>
        </div>
    </div>

    <!-- Root Info Card -->
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold mb-1">{{ $affiliate->user->name }}</h2>
                <p class="text-indigo-100 mb-3">{{ $affiliate->user->email }}</p>
                <div class="flex gap-4 text-sm">
                    <span class="bg-white/20 px-3 py-1 rounded-full">
                        รหัส: <strong>{{ $affiliate->referral_code }}</strong>
                    </span>
                    <span class="bg-white/20 px-3 py-1 rounded-full">
                        ระดับ: <strong>{{ $affiliate->level }}</strong>
                    </span>
                    <span class="bg-white/20 px-3 py-1 rounded-full">
                        ลูกทีม: <strong>{{ $affiliate->total_referrals }}</strong>
                    </span>
                </div>
            </div>
            <div class="text-right">
                <div class="text-4xl font-bold mb-1">฿{{ number_format($affiliate->total_earnings, 0) }}</div>
                <p class="text-indigo-100 text-sm">รายได้รวม</p>
            </div>
        </div>
    </div>

    <!-- Tree Visualization -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="mb-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
            <div class="flex items-start gap-3">
                <div class="text-2xl">💡</div>
                <div class="flex-1 text-sm text-blue-900">
                    <p class="font-semibold mb-1">วิธีการใช้งาน:</p>
                    <ul class="list-disc list-inside space-y-1 text-blue-800">
                        <li>ลาก <span class="font-semibold">Affiliate</span> ไปวางบน <span class="font-semibold">Affiliate อื่น</span> เพื่อย้ายสายงาน</li>
                        <li>ไม่สามารถย้ายไปยังตัวเองหรือลูกสายของตัวเองได้</li>
                        <li>การเปลี่ยนแปลงจะบันทึกทันที</li>
                    </ul>
                </div>
            </div>
        </div>

        <div id="tree-container" class="relative">
            @include('admin.affiliates.partials.tree-node', ['node' => $affiliate, 'level' => 0])
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">ลูกทีมโดยตรง</p>
                    <p class="text-3xl font-bold text-indigo-600">{{ $affiliate->children->count() }}</p>
                </div>
                <div class="text-4xl">👥</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">เครือข่ายทั้งหมด</p>
                    <p class="text-3xl font-bold text-blue-600">{{ $affiliate->total_referrals }}</p>
                </div>
                <div class="text-4xl">🌐</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">รายได้รวม</p>
                    <p class="text-3xl font-bold text-green-600">฿{{ number_format($affiliate->total_earnings, 0) }}</p>
                </div>
                <div class="text-4xl">💰</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">สถานะ</p>
                    <p class="text-lg font-bold {{ $affiliate->status === 'active' ? 'text-green-600' : 'text-gray-600' }}">
                        {{ $affiliate->status === 'active' ? 'ใช้งาน' : 'ไม่ใช้งาน' }}
                    </p>
                </div>
                <div class="text-4xl">{{ $affiliate->status === 'active' ? '✅' : '⏸️' }}</div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Drag & Drop functionality
document.addEventListener('DOMContentLoaded', function() {
    const affiliateNodes = document.querySelectorAll('.affiliate-node');

    affiliateNodes.forEach(node => {
        // Make draggable
        node.setAttribute('draggable', true);

        node.addEventListener('dragstart', function(e) {
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', this.dataset.id);
            this.classList.add('opacity-50');
        });

        node.addEventListener('dragend', function(e) {
            this.classList.remove('opacity-50');
            // Remove all drop indicators
            document.querySelectorAll('.affiliate-node').forEach(n => {
                n.classList.remove('ring-4', 'ring-green-400', 'ring-red-400');
            });
        });

        // Make drop target
        node.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';

            // Visual feedback
            const draggedId = e.dataTransfer.getData('text/plain');
            if (draggedId != this.dataset.id) {
                this.classList.add('ring-4', 'ring-green-400');
            }
        });

        node.addEventListener('dragleave', function(e) {
            this.classList.remove('ring-4', 'ring-green-400', 'ring-red-400');
        });

        node.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('ring-4', 'ring-green-400', 'ring-red-400');

            const draggedId = e.dataTransfer.getData('text/plain');
            const targetId = this.dataset.id;

            if (draggedId === targetId) {
                return;
            }

            // Send move request
            moveAffiliate(draggedId, targetId);
        });
    });
});

function moveAffiliate(affiliateId, newParentId) {
    if (!confirm('คุณต้องการย้ายสายงานนี้ใช่หรือไม่?')) {
        return;
    }

    fetch(`/admin/affiliates/${affiliateId}/move`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            new_parent_id: newParentId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
            location.reload();
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการย้ายสายงาน');
    });
}
</script>
@endpush
@endsection
