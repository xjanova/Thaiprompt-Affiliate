@php
    $colors = [
        1 => ['bg' => 'bg-gradient-to-r from-blue-50 to-cyan-50', 'border' => 'border-blue-300', 'text' => 'text-blue-700', 'badge' => 'bg-blue-100 text-blue-800'],
        2 => ['bg' => 'bg-gradient-to-r from-green-50 to-emerald-50', 'border' => 'border-green-300', 'text' => 'text-green-700', 'badge' => 'bg-green-100 text-green-800'],
        3 => ['bg' => 'bg-gradient-to-r from-yellow-50 to-orange-50', 'border' => 'border-yellow-300', 'text' => 'text-yellow-700', 'badge' => 'bg-yellow-100 text-yellow-800'],
        4 => ['bg' => 'bg-gradient-to-r from-purple-50 to-pink-50', 'border' => 'border-purple-300', 'text' => 'text-purple-700', 'badge' => 'bg-purple-100 text-purple-800'],
    ];
    $colorScheme = $colors[($level % 4) + 1] ?? $colors[1];
    $indentClass = 'ml-' . ($level * 4);
@endphp

<div class="tree-node-container mb-3" style="margin-left: {{ $level * 20 }}px;">
    <!-- Node Card -->
    <div class="tree-node relative {{ $colorScheme['bg'] }} border-2 {{ $colorScheme['border'] }} rounded-xl p-3 lg:p-4 shadow-md hover:shadow-lg transition-all duration-200">

        <!-- Connector Line (Visual indicator of hierarchy) -->
        @if($level > 0)
            <div class="absolute w-4 h-px {{ $colorScheme['border'] }}" style="left: -20px; top: 50%;"></div>
        @endif

        <div class="flex items-start lg:items-center justify-between gap-3 flex-col lg:flex-row">
            <!-- Info Section -->
            <div class="flex-1 w-full">
                <div class="flex items-center gap-3 mb-2">
                    <!-- Avatar -->
                    <div class="w-10 h-10 lg:w-12 lg:h-12 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white font-bold text-base lg:text-lg shadow-md shrink-0">
                        {{ strtoupper(substr($node->user->name, 0, 1)) }}
                    </div>

                    <!-- Name & Basic Info -->
                    <div class="flex-1 min-w-0">
                        <h3 class="text-base lg:text-lg font-bold text-gray-900 truncate">{{ $node->user->name }}</h3>
                        <p class="text-xs lg:text-sm text-gray-600 truncate">{{ $node->user->email }}</p>
                    </div>

                    <!-- Level Badge -->
                    <span class="px-2 py-1 {{ $colorScheme['badge'] }} rounded-full text-xs font-bold shrink-0">
                        L{{ $level }}
                    </span>
                </div>

                <!-- Stats Row -->
                <div class="grid grid-cols-2 lg:flex lg:flex-wrap gap-2 lg:gap-4 text-xs lg:text-sm text-gray-600 ml-0 lg:ml-14">
                    <div class="flex items-center gap-1">
                        <span class="text-base lg:text-lg">🆔</span>
                        <code class="px-2 py-0.5 bg-white rounded text-xs font-mono">{{ $node->referral_code }}</code>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="text-base lg:text-lg">👥</span>
                        <strong>{{ $node->children->count() }}</strong>
                        <span class="hidden lg:inline">ลูกทีม</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="text-base lg:text-lg">🌐</span>
                        <strong>{{ $node->total_referrals }}</strong>
                        <span class="hidden lg:inline">เครือข่าย</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="text-base lg:text-lg">💰</span>
                        <strong class="text-green-600">฿{{ number_format($node->total_earnings, 0) }}</strong>
                    </div>
                </div>
            </div>

            <!-- Status Badge -->
            <div class="flex items-center gap-2 shrink-0">
                @if($node->status === 'active')
                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">
                        ✅ ใช้งาน
                    </span>
                @else
                    <span class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-medium">
                        ⏸️ ไม่ใช้งาน
                    </span>
                @endif

                <!-- Expand/Collapse Button for Mobile -->
                @if($node->children && $node->children->count() > 0)
                    <button
                        class="lg:hidden px-2 py-1 bg-white border {{ $colorScheme['border'] }} rounded-lg text-xs font-medium expand-toggle"
                        data-target="children-{{ $node->id }}">
                        ▼
                    </button>
                @endif
            </div>
        </div>

        <!-- Additional Info (Expandable on mobile) -->
        <div class="mt-3 pt-3 border-t {{ $colorScheme['border'] }} hidden lg:block">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 text-xs">
                <div class="bg-white rounded-lg p-2 text-center">
                    <p class="text-gray-500">สมัคร</p>
                    <p class="font-semibold text-gray-900">{{ $node->created_at->format('d/m/Y') }}</p>
                </div>
                <div class="bg-white rounded-lg p-2 text-center">
                    <p class="text-gray-500">ระดับ</p>
                    <p class="font-semibold {{ $colorScheme['text'] }}">{{ $node->level }}</p>
                </div>
                <div class="bg-white rounded-lg p-2 text-center">
                    <p class="text-gray-500">ลูกทีมโดยตรง</p>
                    <p class="font-semibold text-blue-600">{{ $node->children->count() }}</p>
                </div>
                <div class="bg-white rounded-lg p-2 text-center">
                    <p class="text-gray-500">อัตราเติบโต</p>
                    <p class="font-semibold text-green-600">
                        @if($node->total_referrals > 0)
                            {{ number_format(($node->children->count() / max($node->total_referrals, 1)) * 100, 1) }}%
                        @else
                            0%
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Children Nodes (Recursive) -->
    @if($node->children && $node->children->count() > 0)
        <div class="children-container mt-2 space-y-2" id="children-{{ $node->id }}">
            <!-- Show count badge for collapsed state -->
            @if($node->children->count() > 3 && $level >= 2)
                <div class="flex items-center gap-2 mb-2" style="margin-left: {{ ($level + 1) * 20 }}px;">
                    <button
                        class="px-3 py-1 bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded-lg text-xs font-medium transition expand-toggle"
                        data-target="deep-children-{{ $node->id }}">
                        ▶ แสดง {{ $node->children->count() }} คน
                    </button>
                </div>
                <div id="deep-children-{{ $node->id }}" class="hidden">
                    @foreach($node->children as $child)
                        @include('user.partials.tree-node', ['node' => $child, 'level' => $level + 1])
                    @endforeach
                </div>
            @else
                @foreach($node->children as $child)
                    @include('user.partials.tree-node', ['node' => $child, 'level' => $level + 1])
                @endforeach
            @endif
        </div>
    @endif
</div>

@if($level === 1)
<style>
/* Responsive tree styling */
@media (max-width: 768px) {
    .tree-node-container {
        margin-left: {{ $level * 10 }}px !important;
    }
}

/* Smooth animations */
.tree-node {
    transition: all 0.3s ease;
}

.tree-node:hover {
    transform: translateY(-2px);
}

/* Expand/Collapse animation */
.children-container {
    transition: all 0.3s ease;
}

.hidden {
    display: none;
}

/* Better spacing for deep levels */
.tree-node-container[style*="margin-left: 60px"],
.tree-node-container[style*="margin-left: 80px"],
.tree-node-container[style*="margin-left: 100px"] {
    margin-top: 0.75rem;
}
</style>
@endif
