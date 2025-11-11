@php
    $colors = [
        0 => [
            'bg' => 'bg-gradient-to-br from-indigo-100 via-purple-100 to-pink-100 dark:from-indigo-900/30 dark:via-purple-900/30 dark:to-pink-900/30',
            'border' => 'border-indigo-400 dark:border-indigo-600',
            'text' => 'text-indigo-900 dark:text-indigo-300',
            'badge' => 'bg-indigo-500',
            'glow' => 'shadow-indigo-500/50'
        ],
        1 => [
            'bg' => 'bg-gradient-to-br from-blue-100 via-cyan-100 to-teal-100 dark:from-blue-900/30 dark:via-cyan-900/30 dark:to-teal-900/30',
            'border' => 'border-blue-400 dark:border-blue-600',
            'text' => 'text-blue-900 dark:text-blue-300',
            'badge' => 'bg-blue-500',
            'glow' => 'shadow-blue-500/50'
        ],
        2 => [
            'bg' => 'bg-gradient-to-br from-green-100 via-emerald-100 to-teal-100 dark:from-green-900/30 dark:via-emerald-900/30 dark:to-teal-900/30',
            'border' => 'border-green-400 dark:border-green-600',
            'text' => 'text-green-900 dark:text-green-300',
            'badge' => 'bg-green-500',
            'glow' => 'shadow-green-500/50'
        ],
        3 => [
            'bg' => 'bg-gradient-to-br from-orange-100 via-amber-100 to-yellow-100 dark:from-orange-900/30 dark:via-amber-900/30 dark:to-yellow-900/30',
            'border' => 'border-orange-400 dark:border-orange-600',
            'text' => 'text-orange-900 dark:text-orange-300',
            'badge' => 'bg-orange-500',
            'glow' => 'shadow-orange-500/50'
        ],
    ];
    $colorScheme = $colors[$level % 4];
@endphp

<div class="affiliate-node-container mb-6 relative" style="margin-left: {{ $level * 50 }}px;">
    <!-- Enhanced Connector Lines -->
    @if($level > 0)
        <div class="connector-line absolute w-10 h-0.5 bg-gradient-to-r from-gray-300 to-gray-400 dark:from-slate-600 dark:to-slate-500 rounded-full" style="left: -40px; top: 50%;"></div>
        <div class="connector-vertical absolute w-0.5 bg-gradient-to-b from-gray-300 to-gray-400 dark:from-slate-600 dark:to-slate-500 rounded-full" style="left: -40px; height: 100%;"></div>
        <!-- Connection Dot -->
        <div class="absolute w-2 h-2 {{ $colorScheme['badge'] }} rounded-full shadow-lg" style="left: -41px; top: calc(50% - 4px);"></div>
    @endif

    <!-- Enhanced Node Card -->
    <div class="affiliate-node group relative cursor-move transition-all duration-300 hover:scale-105 {{ $colorScheme['bg'] }} border-2 {{ $colorScheme['border'] }} rounded-2xl p-5 shadow-xl hover:shadow-2xl {{ $colorScheme['glow'] }} hover:-translate-y-1"
         data-affiliate-id="{{ $node->id }}"
         data-level="{{ $node->level }}">

        <!-- Animated Background Effect -->
        <div class="absolute inset-0 bg-white/5 dark:bg-white/10 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

        <!-- Enhanced Drag Handle -->
        <div class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-slate-500 cursor-grab active:cursor-grabbing group-hover:text-gray-600 dark:group-hover:text-slate-400 transition-colors duration-200">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
            </svg>
        </div>

        <div class="relative z-10 flex items-center justify-between ml-10">
            <!-- Enhanced Info Section -->
            <div class="flex-1">
                <div class="flex items-center gap-4 mb-3">
                    <!-- Enhanced Avatar with Rank -->
                    @php
                        $rank = $node->rank ?? null;
                        $rankColor = $rank ? $rank->color : '#94A3B8'; // Slate-400 for no rank
                        $rankStars = $rank ? $rank->stars : 0;
                    @endphp
                    <div class="relative group/avatar">
                        <!-- Glow Effect -->
                        <div class="absolute inset-0 rounded-full blur-md opacity-75 group-hover/avatar:opacity-100 transition-opacity duration-300"
                             style="background: {{ $rankColor }};"></div>

                        <!-- Avatar Circle with Enhanced Design -->
                        <div class="relative w-14 h-14 rounded-full bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 flex items-center justify-center text-white font-black text-xl shadow-xl group-hover/avatar:scale-110 transition-transform duration-300"
                             style="border: 3px solid {{ $rankColor }};">
                            {{ strtoupper(substr($node->user->name, 0, 2)) }}
                        </div>

                        <!-- Enhanced Rank Stars Badge -->
                        @if($rankStars > 0)
                            <div class="absolute -bottom-2 left-1/2 transform -translate-x-1/2 flex gap-0.5 px-2 py-1 rounded-full shadow-lg border-2 group-hover/avatar:scale-110 transition-transform duration-300"
                                 style="background-color: {{ $rankColor }}; border-color: white;"
                                 title="{{ $rank->display_name }}">
                                @for($i = 0; $i < min($rankStars, 5); $i++)
                                    <span class="text-xs">⭐</span>
                                @endfor
                            </div>
                        @else
                            <div class="absolute -bottom-2 left-1/2 transform -translate-x-1/2 px-2 py-1 bg-gray-200 dark:bg-slate-700 rounded-full shadow-md border-2 border-white dark:border-slate-600"
                                 title="ไม่มี Rank">
                                <span class="text-xs">☆</span>
                            </div>
                        @endif

                        <!-- Status Indicator -->
                        @if($node->status === 'active')
                            <div class="absolute top-0 right-0 w-4 h-4 bg-green-400 rounded-full border-2 border-white dark:border-slate-800 shadow-lg animate-pulse"></div>
                        @endif
                    </div>

                    <!-- Enhanced Name & Info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                            <h3 class="text-lg font-black {{ $colorScheme['text'] }} truncate">{{ $node->user->name }}</h3>
                            @if($rank)
                                <span class="px-3 py-1 rounded-lg text-xs font-bold text-white shadow-lg hover:scale-110 transition-transform duration-200 flex items-center gap-1"
                                      style="background: linear-gradient(135deg, {{ $rankColor }}, {{ $rankColor }}dd);"
                                      title="{{ $rank->display_description ?? '' }}">
                                    <span>{{ $rank->badge_icon ?? '🏆' }}</span>
                                    <span>{{ $rank->display_name }}</span>
                                </span>
                            @else
                                <span class="px-3 py-1 rounded-lg text-xs font-bold bg-gray-200 dark:bg-slate-700 text-gray-600 dark:text-gray-400 border-2 border-gray-300 dark:border-slate-600"
                                      title="ยังไม่มี Rank">
                                    ไม่มี Rank
                                </span>
                            @endif

                            <!-- Level Badge -->
                            <span class="px-3 py-1 {{ $colorScheme['badge'] }} text-white rounded-lg text-xs font-black shadow-lg hover:scale-110 transition-transform duration-200 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                L{{ $node->level }}
                            </span>

                            <!-- Enhanced Status Badge -->
                            @if($node->status === 'active')
                                <span class="px-3 py-1 bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 text-green-800 dark:text-green-300 rounded-lg text-xs font-bold border-2 border-green-300 dark:border-green-700 shadow-sm flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Active
                                </span>
                            @else
                                <span class="px-3 py-1 bg-gradient-to-r from-gray-100 to-slate-100 dark:from-slate-700 dark:to-slate-600 text-gray-700 dark:text-gray-300 rounded-lg text-xs font-bold border-2 border-gray-300 dark:border-slate-600 shadow-sm">Inactive</span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 font-medium truncate">{{ $node->user->email }}</p>
                    </div>
                </div>

                <!-- Enhanced Stats Section -->
                <div class="flex flex-wrap gap-3 text-sm ml-[72px]">
                    <div class="flex items-center gap-2 px-3 py-1.5 bg-white/50 dark:bg-slate-800/50 rounded-lg border border-gray-200 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow duration-200">
                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        <code class="font-mono font-bold text-gray-700 dark:text-gray-300">{{ $node->referral_code }}</code>
                    </div>
                    <div class="flex items-center gap-2 px-3 py-1.5 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800 shadow-sm hover:shadow-md transition-shadow duration-200">
                        <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <strong class="font-bold text-purple-700 dark:text-purple-300">{{ $node->children->count() }}</strong>
                        <span class="text-gray-600 dark:text-gray-400 font-medium">ลูกทีม</span>
                    </div>
                    <div class="flex items-center gap-2 px-3 py-1.5 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 shadow-sm hover:shadow-md transition-shadow duration-200">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                        </svg>
                        <strong class="font-bold text-blue-700 dark:text-blue-300">{{ $node->total_referrals }}</strong>
                        <span class="text-gray-600 dark:text-gray-400 font-medium">เครือข่าย</span>
                    </div>
                    <div class="flex items-center gap-2 px-3 py-1.5 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800 shadow-sm hover:shadow-md transition-shadow duration-200">
                        <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <strong class="font-black text-green-600 dark:text-green-400">฿{{ number_format($node->total_earnings, 0) }}</strong>
                    </div>
                </div>
            </div>

            <!-- Enhanced Actions -->
            <div class="flex items-center gap-2 ml-4">
                <a href="{{ route('admin.affiliates.show', $node) }}"
                   class="group/btn px-4 py-2.5 bg-white dark:bg-slate-800 border-2 border-indigo-200 dark:border-indigo-800 rounded-xl hover:bg-indigo-50 dark:hover:bg-indigo-900/30 hover:border-indigo-400 dark:hover:border-indigo-600 text-sm font-bold transition-all duration-200 hover:scale-110 shadow-sm hover:shadow-lg flex items-center gap-2"
                   title="ดูรายละเอียด">
                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400 group-hover/btn:scale-110 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <span class="text-indigo-700 dark:text-indigo-300">ดู</span>
                </a>
                <a href="{{ route('admin.affiliates.tree', $node) }}"
                   class="group/btn px-4 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white rounded-xl text-sm font-bold transition-all duration-200 hover:scale-110 shadow-lg hover:shadow-xl flex items-center gap-2"
                   title="ดู Tree">
                    <svg class="w-4 h-4 group-hover/btn:rotate-12 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                    </svg>
                    <span>Tree</span>
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
