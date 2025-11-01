@extends('layouts.user')

@section('title', 'ผังสายงาน')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-2xl lg:text-3xl font-bold mb-2">🌳 ผังสายงานของคุณ</h1>
                <p class="text-indigo-100 text-sm lg:text-base">แสดงโครงสร้างทีมงานและลูกข่ายของคุณ</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-right">
                    <p class="text-sm text-indigo-100">รหัสแนะนำของคุณ</p>
                    <p class="text-2xl font-bold">{{ $affiliate->referral_code }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Direct Team -->
        <div class="bg-white rounded-xl shadow-md p-4 lg:p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs lg:text-sm text-gray-600 mb-1">ลูกทีมโดยตรง</p>
                    <p class="text-2xl lg:text-3xl font-bold text-indigo-600">{{ $affiliate->children->count() }}</p>
                </div>
                <div class="text-3xl lg:text-4xl">👥</div>
            </div>
        </div>

        <!-- Total Network -->
        <div class="bg-white rounded-xl shadow-md p-4 lg:p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs lg:text-sm text-gray-600 mb-1">เครือข่ายทั้งหมด</p>
                    <p class="text-2xl lg:text-3xl font-bold text-blue-600">{{ $totalNetwork }}</p>
                </div>
                <div class="text-3xl lg:text-4xl">🌐</div>
            </div>
        </div>

        <!-- Network Earnings -->
        <div class="bg-white rounded-xl shadow-md p-4 lg:p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs lg:text-sm text-gray-600 mb-1">รายได้เครือข่าย</p>
                    <p class="text-xl lg:text-2xl font-bold text-green-600">฿{{ number_format($totalNetworkEarnings, 0) }}</p>
                </div>
                <div class="text-3xl lg:text-4xl">💰</div>
            </div>
        </div>

        <!-- Organization Depth -->
        <div class="bg-white rounded-xl shadow-md p-4 lg:p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs lg:text-sm text-gray-600 mb-1">ความลึกองค์กร</p>
                    <p class="text-2xl lg:text-3xl font-bold text-purple-600">{{ $maxDepth }}</p>
                    <p class="text-xs text-gray-500">ระดับ</p>
                </div>
                <div class="text-3xl lg:text-4xl">📊</div>
            </div>
        </div>
    </div>

    <!-- Info Box -->
    <div class="bg-blue-50 rounded-xl border border-blue-200 p-4 lg:p-5">
        <div class="flex items-start gap-3">
            <div class="text-2xl lg:text-3xl">💡</div>
            <div class="flex-1">
                <p class="font-semibold text-blue-900 mb-2">ข้อมูลการแสดงผล:</p>
                <ul class="list-disc list-inside space-y-1 text-sm text-blue-800">
                    <li>แสดงเฉพาะลูกข่ายของคุณ (ไม่แสดงผู้แนะนำด้านบน)</li>
                    <li>ข้อมูลจะอัพเดทแบบเรียลไทม์</li>
                    <li>สามารถเลื่อนดูผังได้ทุกทิศทาง</li>
                    <li>คลิกที่สมาชิกเพื่อดูรายละเอียด</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Organization Chart -->
    <div class="bg-white rounded-xl shadow-md p-4 lg:p-6">
        <div class="mb-4 pb-4 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-900">โครงสร้างองค์กร</h2>
            <p class="text-sm text-gray-600 mt-1">เริ่มจากคุณและแสดงลูกข่ายทั้งหมด</p>
        </div>

        <!-- Root Node (Current User) -->
        <div class="mb-6">
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl p-4 lg:p-6 text-white shadow-lg">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-4">
                        <!-- Avatar -->
                        <div class="w-14 h-14 lg:w-16 lg:h-16 rounded-full bg-white flex items-center justify-center text-indigo-600 font-bold text-xl lg:text-2xl shadow-md">
                            {{ strtoupper(substr($affiliate->user->name, 0, 1)) }}
                        </div>

                        <!-- Info -->
                        <div>
                            <h3 class="text-lg lg:text-xl font-bold">{{ $affiliate->user->name }} (คุณ)</h3>
                            <p class="text-sm text-indigo-100">{{ $affiliate->user->email }}</p>
                            <div class="flex gap-2 mt-2 flex-wrap">
                                <span class="px-2 py-1 bg-white/20 rounded-full text-xs font-medium">
                                    Level {{ $affiliate->level }}
                                </span>
                                <span class="px-2 py-1 bg-white/20 rounded-full text-xs font-medium">
                                    {{ $affiliate->status === 'active' ? '✅ ใช้งาน' : '⏸️ ไม่ใช้งาน' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="text-right">
                        <div class="text-3xl lg:text-4xl font-bold">฿{{ number_format($affiliate->total_earnings, 0) }}</div>
                        <p class="text-sm text-indigo-100">รายได้รวม</p>
                    </div>
                </div>

                <!-- Mini Stats -->
                <div class="grid grid-cols-3 gap-3 mt-4 pt-4 border-t border-white/20">
                    <div class="text-center">
                        <p class="text-2xl font-bold">{{ $affiliate->children->count() }}</p>
                        <p class="text-xs text-indigo-100">ลูกทีมโดยตรง</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold">{{ $totalNetwork }}</p>
                        <p class="text-xs text-indigo-100">เครือข่ายทั้งหมด</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold">{{ $affiliate->referral_code }}</p>
                        <p class="text-xs text-indigo-100">รหัสแนะนำ</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tree Visualization -->
        @if($affiliate->children && $affiliate->children->count() > 0)
            <div class="space-y-3" id="tree-container">
                @foreach($affiliate->children as $child)
                    @include('user.partials.tree-node', ['node' => $child, 'level' => 1])
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <div class="text-6xl mb-4">👥</div>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">ยังไม่มีลูกทีม</h3>
                <p class="text-gray-500 mb-4">เชิญเพื่อนของคุณด้วยรหัสแนะนำเพื่อสร้างทีมของคุณ</p>
                <div class="bg-gray-100 rounded-lg p-4 inline-block">
                    <p class="text-sm text-gray-600 mb-1">รหัสแนะนำของคุณ</p>
                    <p class="text-2xl font-bold text-indigo-600">{{ $affiliate->referral_code }}</p>
                </div>
            </div>
        @endif
    </div>

    <!-- Legend -->
    <div class="bg-white rounded-xl shadow-md p-4 lg:p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">สัญลักษณ์และความหมาย</h3>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="flex items-center gap-3 p-3 bg-indigo-50 rounded-lg">
                <span class="text-2xl">L1, L2, L3...</span>
                <div>
                    <p class="font-semibold text-gray-900">ระดับในองค์กร</p>
                    <p class="text-sm text-gray-600">แสดงความลึกของสายงาน</p>
                </div>
            </div>
            <div class="flex items-center gap-3 p-3 bg-green-50 rounded-lg">
                <span class="text-2xl">✅</span>
                <div>
                    <p class="font-semibold text-gray-900">สถานะใช้งาน</p>
                    <p class="text-sm text-gray-600">สมาชิกที่ active</p>
                </div>
            </div>
            <div class="flex items-center gap-3 p-3 bg-blue-50 rounded-lg">
                <span class="text-2xl">👥</span>
                <div>
                    <p class="font-semibold text-gray-900">จำนวนลูกทีม</p>
                    <p class="text-sm text-gray-600">ลูกทีมโดยตรงของแต่ละคน</p>
                </div>
            </div>
            <div class="flex items-center gap-3 p-3 bg-yellow-50 rounded-lg">
                <span class="text-2xl">💰</span>
                <div>
                    <p class="font-semibold text-gray-900">รายได้</p>
                    <p class="text-sm text-gray-600">รายได้รวมของแต่ละคน</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Smooth scroll animations
document.addEventListener('DOMContentLoaded', function() {
    // Animate tree nodes on load
    const nodes = document.querySelectorAll('.tree-node');
    nodes.forEach((node, index) => {
        setTimeout(() => {
            node.style.opacity = '0';
            node.style.transform = 'translateY(20px)';
            setTimeout(() => {
                node.style.transition = 'all 0.5s ease';
                node.style.opacity = '1';
                node.style.transform = 'translateY(0)';
            }, 50);
        }, index * 50);
    });

    // Expandable/Collapsible functionality for large trees
    const expandButtons = document.querySelectorAll('.expand-toggle');
    expandButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.dataset.target;
            const target = document.getElementById(targetId);
            if (target) {
                target.classList.toggle('hidden');
                this.textContent = target.classList.contains('hidden') ? '▶ ขยาย' : '▼ ย่อ';
            }
        });
    });
});
</script>
@endpush
@endsection
