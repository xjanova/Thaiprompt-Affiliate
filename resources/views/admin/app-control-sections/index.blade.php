@extends('layouts.admin')

@section('title', 'จัดการเซคชั่น Control')

@section('content')
<div x-data="{
    deleteId: null
}" class="container-fluid px-4 py-6">

    <!-- Animated Header -->
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-cyan-500 via-blue-600 to-indigo-600 dark:from-cyan-600 dark:via-blue-700 dark:to-indigo-700 p-8 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>
        <div class="relative flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-14 h-14 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white mb-1">จัดการเซคชั่น Control</h1>
                    <p class="text-cyan-100 dark:text-blue-200">ออกแบบและจัดการเซคชั่น UI ของแอพ</p>
                </div>
            </div>
            <a href="{{ route('admin.app-control-sections.create') }}" class="px-6 py-3 bg-white/20 backdrop-blur-md hover:bg-white/30 text-white rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-bold border border-white/30">
                <i class="fas fa-plus mr-2"></i>สร้างเซคชั่นใหม่
            </a>
        </div>
    </div>

    <!-- Success Message -->
    @if(session('success'))
        <div class="mb-6 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-l-4 border-green-500 p-4 rounded-xl shadow-md">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-green-800 dark:text-green-300 font-semibold">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Sections Grid -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
        @forelse($sections as $section)
            <div class="p-6 border-b border-gray-200 dark:border-slate-700 last:border-b-0 hover:bg-gradient-to-r hover:from-cyan-50/50 hover:to-blue-50/50 dark:hover:from-cyan-900/10 dark:hover:to-blue-900/10 transition-all duration-300">
                <div class="flex items-start justify-between">
                    <!-- Section Info -->
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-3">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ $section->section_name }}
                                @if($section->section_name_en)
                                    <span class="text-sm text-gray-500 dark:text-gray-400 font-normal">
                                        ({{ $section->section_name_en }})
                                    </span>
                                @endif
                            </h3>

                            <!-- Status Badges -->
                            <div class="flex gap-2">
                                @if($section->is_visible)
                                    <span class="px-2 py-1 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 text-xs font-semibold rounded-full">
                                        <i class="fas fa-eye mr-1"></i>มองเห็น
                                    </span>
                                @else
                                    <span class="px-2 py-1 bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300 text-xs font-semibold rounded-full">
                                        <i class="fas fa-eye-slash mr-1"></i>ซ่อน
                                    </span>
                                @endif

                                @if($section->is_active)
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 text-xs font-semibold rounded-full">
                                        <i class="fas fa-check-circle mr-1"></i>ใช้งาน
                                    </span>
                                @else
                                    <span class="px-2 py-1 bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 text-xs font-semibold rounded-full">
                                        <i class="fas fa-times-circle mr-1"></i>ปิด
                                    </span>
                                @endif

                                <!-- Platform Badge -->
                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                    {{ $section->platform === 'android' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : '' }}
                                    {{ $section->platform === 'ios' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : '' }}
                                    {{ $section->platform === 'all' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300' : '' }}">
                                    <i class="fas fa-{{ $section->platform === 'android' ? 'robot' : ($section->platform === 'ios' ? 'apple' : 'mobile-alt') }} mr-1"></i>
                                    {{ strtoupper($section->platform) }}
                                </span>
                            </div>
                        </div>

                        <!-- Section ID & Details -->
                        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 mb-3 text-sm">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-key text-cyan-500"></i>
                                <span class="text-gray-600 dark:text-gray-400">ID:</span>
                                <code class="px-2 py-1 bg-gray-100 dark:bg-slate-700 rounded text-xs font-mono text-gray-800 dark:text-gray-200">
                                    {{ $section->section_id }}
                                </code>
                            </div>

                            <div class="flex items-center gap-2">
                                <i class="fas fa-sort text-blue-500"></i>
                                <span class="text-gray-600 dark:text-gray-400">ลำดับ:</span>
                                <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $section->sort_order }}</span>
                            </div>

                            @if($section->layout_type)
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-th-large text-indigo-500"></i>
                                    <span class="text-gray-600 dark:text-gray-400">Layout:</span>
                                    <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $section->layout_type }}</span>
                                </div>
                            @endif

                            @if($section->display_position)
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-map-marker-alt text-red-500"></i>
                                    <span class="text-gray-600 dark:text-gray-400">ตำแหน่ง:</span>
                                    <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $section->display_position }}</span>
                                </div>
                            @endif
                        </div>

                        @if($section->description)
                            <p class="text-gray-700 dark:text-gray-300 mb-2">{{ $section->description }}</p>
                        @endif

                        <!-- Style Preview -->
                        <div class="flex flex-wrap gap-3 mt-3">
                            @if($section->background_color)
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-600 dark:text-gray-400">สี BG:</span>
                                    <div class="flex items-center gap-1">
                                        <div class="w-6 h-6 rounded border border-gray-300 dark:border-slate-600" style="background-color: {{ $section->background_color }}"></div>
                                        <code class="text-xs text-gray-600 dark:text-gray-400">{{ $section->background_color }}</code>
                                    </div>
                                    @if($section->background_dark_color)
                                        <div class="flex items-center gap-1">
                                            <div class="w-6 h-6 rounded border border-gray-300 dark:border-slate-600" style="background-color: {{ $section->background_dark_color }}"></div>
                                            <code class="text-xs text-gray-600 dark:text-gray-400">{{ $section->background_dark_color }}</code>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            @if($section->border_radius)
                                <span class="px-2 py-1 bg-gray-100 dark:bg-slate-700 text-xs rounded">
                                    <i class="fas fa-circle text-purple-500 mr-1"></i>
                                    Radius: {{ $section->border_radius }}px
                                </span>
                            @endif

                            @if($section->elevation)
                                <span class="px-2 py-1 bg-gray-100 dark:bg-slate-700 text-xs rounded">
                                    <i class="fas fa-layer-group text-orange-500 mr-1"></i>
                                    Elevation: {{ $section->elevation }}
                                </span>
                            @endif

                            @if($section->animation_enabled)
                                <span class="px-2 py-1 bg-gradient-to-r from-purple-100 to-pink-100 dark:from-purple-900 dark:to-pink-900 text-purple-800 dark:text-purple-300 text-xs rounded font-semibold">
                                    <i class="fas fa-magic mr-1"></i>
                                    Animation: {{ $section->animation_type ?? 'enabled' }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2 ml-4">
                        <a href="{{ route('admin.app-control-sections.edit', $section) }}"
                           class="px-4 py-2 bg-gradient-to-r from-blue-500 to-cyan-600 hover:from-blue-600 hover:to-cyan-700 text-white rounded-lg transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                            <i class="fas fa-edit mr-1"></i>แก้ไข
                        </a>
                        <button @click="deleteId = {{ $section->id }}"
                                class="px-4 py-2 bg-gradient-to-r from-red-500 to-pink-600 hover:from-red-600 hover:to-pink-700 text-white rounded-lg transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                            <i class="fas fa-trash mr-1"></i>ลบ
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="p-12 text-center">
                <div class="w-24 h-24 bg-gradient-to-br from-cyan-100 to-blue-100 dark:from-cyan-900/30 dark:to-blue-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-inbox text-4xl text-cyan-600 dark:text-cyan-400"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ยังไม่มีเซคชั่น</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">เริ่มต้นสร้างเซคชั่น UI แรกของคุณ</p>
                <a href="{{ route('admin.app-control-sections.create') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-700 hover:to-blue-700 text-white rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-bold">
                    <i class="fas fa-plus mr-2"></i>สร้างเซคชั่นใหม่
                </a>
            </div>
        @endforelse

        <!-- Pagination -->
        @if($sections->hasPages())
            <div class="p-6 border-t border-gray-200 dark:border-slate-700">
                {{ $sections->links() }}
            </div>
        @endif
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-show="deleteId"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
         style="display: none;">
        <div @click.away="deleteId = null"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-90"
             x-transition:enter-end="opacity-100 transform scale-100"
             class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-md w-full p-6">
            <div class="text-center">
                <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exclamation-triangle text-3xl text-red-600 dark:text-red-400"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ยืนยันการลบ</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">คุณแน่ใจหรือไม่ว่าต้องการลบเซคชั่นนี้?</p>
                <div class="flex gap-3">
                    <button @click="deleteId = null"
                            class="flex-1 px-4 py-2 bg-gray-200 dark:bg-slate-700 hover:bg-gray-300 dark:hover:bg-slate-600 text-gray-800 dark:text-gray-200 rounded-lg transition-all duration-200 font-medium">
                        ยกเลิก
                    </button>
                    <form :action="`/admin/app-control-sections/${deleteId}`" method="POST" class="flex-1">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="w-full px-4 py-2 bg-gradient-to-r from-red-500 to-pink-600 hover:from-red-600 hover:to-pink-700 text-white rounded-lg transition-all duration-200 font-medium shadow-lg">
                            ลบ
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
