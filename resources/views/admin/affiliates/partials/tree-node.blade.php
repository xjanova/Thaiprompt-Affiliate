@php
    $colors = [
        0 => ['bg' => 'bg-gradient-to-r from-indigo-50 to-purple-50', 'border' => 'border-indigo-300', 'text' => 'text-indigo-700'],
        1 => ['bg' => 'bg-gradient-to-r from-blue-50 to-cyan-50', 'border' => 'border-blue-300', 'text' => 'text-blue-700'],
        2 => ['bg' => 'bg-gradient-to-r from-green-50 to-emerald-50', 'border' => 'border-green-300', 'text' => 'text-green-700'],
        3 => ['bg' => 'bg-gradient-to-r from-yellow-50 to-orange-50', 'border' => 'border-yellow-300', 'text' => 'text-yellow-700'],
    ];
    $colorScheme = $colors[$level % 4];
@endphp

<div class="affiliate-node-container mb-4" style="margin-left: {{ $level * 40 }}px;">
    <!-- Connector Line -->
    @if($level > 0)
        <div class="connector-line absolute w-8 h-px bg-gray-300" style="left: -32px; top: 50%;"></div>
        <div class="connector-vertical absolute w-px bg-gray-300" style="left: -32px; height: 100%;"></div>
    @endif

    <!-- Node Card -->
    <div class="affiliate-node relative cursor-move transition-all duration-200 hover:scale-102 {{ $colorScheme['bg'] }} border-2 {{ $colorScheme['border'] }} rounded-xl p-4 shadow-md hover:shadow-lg"
         data-id="{{ $node->id }}"
         data-level="{{ $node->level }}">

        <!-- Drag Handle -->
        <div class="absolute left-2 top-1/2 transform -translate-y-1/2 text-gray-400 text-2xl cursor-grab active:cursor-grabbing">
            ⋮⋮
        </div>

        <div class="flex items-center justify-between ml-8">
            <!-- Info -->
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                    <!-- Avatar -->
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white font-bold text-lg shadow-md">
                        {{ strtoupper(substr($node->user->name, 0, 1)) }}
                    </div>

                    <!-- Name & Info -->
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">{{ $node->user->name }}</h3>
                        <p class="text-sm text-gray-600">{{ $node->user->email }}</p>
                    </div>

                    <!-- Level Badge -->
                    <span class="px-3 py-1 {{ $colorScheme['bg'] }} {{ $colorScheme['text'] }} rounded-full text-xs font-bold border {{ $colorScheme['border'] }}">
                        L{{ $node->level }}
                    </span>

                    <!-- Status Badge -->
                    @if($node->status === 'active')
                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">ใช้งาน</span>
                    @else
                        <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-medium">ไม่ใช้งาน</span>
                    @endif
                </div>

                <!-- Stats -->
                <div class="flex gap-4 text-sm text-gray-600 ml-14">
                    <span class="flex items-center gap-1">
                        <span class="text-lg">🆔</span>
                        <code class="px-2 py-0.5 bg-white rounded text-xs font-mono">{{ $node->referral_code }}</code>
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="text-lg">👥</span>
                        <strong>{{ $node->children->count() }}</strong> ลูกทีม
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="text-lg">🌐</span>
                        <strong>{{ $node->total_referrals }}</strong> เครือข่าย
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="text-lg">💰</span>
                        <strong class="text-green-600">฿{{ number_format($node->total_earnings, 0) }}</strong>
                    </span>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.affiliates.show', $node) }}"
                   class="px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 text-sm font-medium transition"
                   title="ดูรายละเอียด">
                    👁️ ดู
                </a>
                <a href="{{ route('admin.affiliates.tree', $node) }}"
                   class="px-3 py-2 bg-indigo-500 text-white rounded-lg hover:bg-indigo-600 text-sm font-medium transition"
                   title="ดู Tree">
                    🌳 Tree
                </a>
            </div>
        </div>
    </div>

    <!-- Children Nodes (Recursive) -->
    @if($node->children && $node->children->count() > 0)
        <div class="children-container mt-2 relative">
            @foreach($node->children as $child)
                @include('admin.affiliates.partials.tree-node', ['node' => $child, 'level' => $level + 1])
            @endforeach
        </div>
    @endif
</div>

@if($level === 0)
<style>
/* Connector lines CSS */
.connector-line {
    position: relative;
    margin-top: 24px;
}

.connector-vertical {
    top: -20px;
}

/* Hover effects */
.affiliate-node:hover {
    transform: translateY(-2px);
}

.hover\:scale-102:hover {
    transform: scale(1.02);
}

/* Drag feedback */
.affiliate-node.opacity-50 {
    opacity: 0.5;
}

.affiliate-node.ring-4 {
    transition: all 0.2s;
}
</style>
@endif
