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
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-md p-4">
            <div class="text-sm text-gray-600">Root Affiliates</div>
            <div class="text-2xl font-bold text-indigo-600">{{ $affiliates->count() }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-md p-4">
            <div class="text-sm text-gray-600">เครือข่ายทั้งหมด</div>
            <div class="text-2xl font-bold text-purple-600">{{ App\Models\Affiliate::count() }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-md p-4">
            <div class="text-sm text-gray-600">รายได้รวม</div>
            <div class="text-2xl font-bold text-green-600">฿{{ number_format(App\Models\Affiliate::sum('total_earnings'), 0) }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-md p-4">
            <div class="text-sm text-gray-600">ใช้งานอยู่</div>
            <div class="text-2xl font-bold text-blue-600">{{ App\Models\Affiliate::where('status', 'active')->count() }}</div>
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
            this.classList.add('opacity-50');
        });
        
        node.addEventListener('dragend', function(e) {
            this.classList.remove('opacity-50');
        });
        
        node.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('ring-4', 'ring-green-400');
        });
        
        node.addEventListener('dragleave', function(e) {
            this.classList.remove('ring-4', 'ring-green-400');
        });
        
        node.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('ring-4', 'ring-green-400');
            
            const draggedId = e.dataTransfer.getData('text/plain');
            const targetId = this.dataset.affiliateId;
            
            if (draggedId !== targetId) {
                if (confirm('คุณต้องการย้าย Affiliate นี้หรือไม่?')) {
                    moveAffiliate(draggedId, targetId);
                }
            }
        });
    });
});

function moveAffiliate(affiliateId, newParentId) {
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
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        alert('เกิดข้อผิดพลาด: ' + error.message);
    });
}
</script>
@endpush
@endsection
