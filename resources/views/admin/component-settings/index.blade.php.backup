@extends('layouts.admin')

@section('title', 'การตั้งค่า Component')

@section('content')
<div x-data="{ deleteId: null, filter: '{{ request('component_type', 'all') }}' }" class="container-fluid px-4 py-6">

    <!-- Animated Header -->
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 via-teal-600 to-cyan-600 dark:from-emerald-600 dark:via-teal-700 dark:to-cyan-700 p-8 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>
        <div class="relative flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-14 h-14 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white mb-1">การตั้งค่า Component</h1>
                    <p class="text-emerald-100 dark:text-teal-200">จัดการสไตล์ Component แอพพลิเคชั่น</p>
                </div>
            </div>
            <a href="{{ route('admin.component-settings.create') }}" class="px-6 py-3 bg-white/20 backdrop-blur-md hover:bg-white/30 text-white rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-bold border border-white/30">
                <i class="fas fa-plus mr-2"></i>สร้าง Component
            </a>
        </div>
    </div>

    <!-- Success Message -->
    @if(session('success'))
        <div class="mb-6 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-l-4 border-green-500 p-4 rounded-xl shadow-md">
            <div class="flex items-center">
                <svg class="h-6 w-6 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-green-800 dark:text-green-300 font-semibold">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    <!-- Filter Bar -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-gray-100 dark:border-slate-700 p-4 mb-6">
        <div class="flex flex-wrap items-center gap-3">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                <i class="fas fa-filter mr-2"></i>กรอง:
            </span>
            @php
                $types = ['all' => 'ทั้งหมด', 'button' => 'ปุ่ม', 'card' => 'การ์ด', 'input' => 'อินพุต', 'text' => 'ข้อความ', 'icon' => 'ไอคอน'];
            @endphp
            @foreach($types as $type => $label)
                <a href="?component_type={{ $type }}"
                   class="px-4 py-2 rounded-lg font-medium transition-all duration-200
                   {{ request('component_type', 'all') === $type ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-md' : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-600' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    <!-- Components Grid -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
        @forelse($components as $component)
            <div class="p-6 border-b border-gray-200 dark:border-slate-700 last:border-b-0 hover:bg-gradient-to-r hover:from-emerald-50/50 hover:to-teal-50/50 dark:hover:from-emerald-900/10 dark:hover:to-teal-900/10 transition-all duration-300">
                <div class="flex items-start justify-between">
                    <!-- Component Info -->
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-3">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ $component->component_name }}
                                @if($component->component_name_en)
                                    <span class="text-sm text-gray-500 dark:text-gray-400 font-normal">({{ $component->component_name_en }})</span>
                                @endif
                            </h3>

                            <!-- Status & Platform -->
                            <div class="flex gap-2">
                                @if($component->is_enabled)
                                    <span class="px-2 py-1 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 text-xs font-semibold rounded-full">
                                        <i class="fas fa-check-circle mr-1"></i>ใช้งาน
                                    </span>
                                @else
                                    <span class="px-2 py-1 bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300 text-xs font-semibold rounded-full">
                                        <i class="fas fa-times-circle mr-1"></i>ปิด
                                    </span>
                                @endif

                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                    {{ $component->platform === 'android' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : '' }}
                                    {{ $component->platform === 'ios' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : '' }}
                                    {{ $component->platform === 'all' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300' : '' }}">
                                    <i class="fas fa-mobile-alt mr-1"></i>{{ strtoupper($component->platform) }}
                                </span>
                            </div>
                        </div>

                        <!-- Component Details -->
                        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 mb-3 text-sm">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-key text-emerald-500"></i>
                                <span class="text-gray-600 dark:text-gray-400">ID:</span>
                                <code class="px-2 py-1 bg-gray-100 dark:bg-slate-700 rounded text-xs font-mono">{{ $component->component_id }}</code>
                            </div>

                            <div class="flex items-center gap-2">
                                <i class="fas fa-cube text-teal-500"></i>
                                <span class="text-gray-600 dark:text-gray-400">Type:</span>
                                <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $component->component_type }}</span>
                            </div>

                            @if($component->font_size)
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-text-height text-blue-500"></i>
                                    <span class="text-gray-600 dark:text-gray-400">Font:</span>
                                    <span class="font-semibold">{{ $component->font_size }}px</span>
                                </div>
                            @endif

                            @if($component->corner_radius)
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-circle text-purple-500"></i>
                                    <span class="text-gray-600 dark:text-gray-400">Radius:</span>
                                    <span class="font-semibold">{{ $component->corner_radius }}px</span>
                                </div>
                            @endif
                        </div>

                        @if($component->description)
                            <p class="text-gray-700 dark:text-gray-300 mb-2">{{ $component->description }}</p>
                        @endif

                        <!-- Style Preview -->
                        <div class="flex flex-wrap gap-3 mt-3">
                            @if($component->background_color)
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-600 dark:text-gray-400">BG:</span>
                                    <div class="w-6 h-6 rounded border" style="background-color: {{ $component->background_color }}"></div>
                                    <code class="text-xs">{{ $component->background_color }}</code>
                                    @if($component->background_dark_color)
                                        <div class="w-6 h-6 rounded border" style="background-color: {{ $component->background_dark_color }}"></div>
                                        <code class="text-xs">{{ $component->background_dark_color }}</code>
                                    @endif
                                </div>
                            @endif

                            @if($component->text_color)
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-600 dark:text-gray-400">Text:</span>
                                    <div class="w-6 h-6 rounded border" style="background-color: {{ $component->text_color }}"></div>
                                    <code class="text-xs">{{ $component->text_color }}</code>
                                </div>
                            @endif

                            @if($component->animation_enabled)
                                <span class="px-2 py-1 bg-gradient-to-r from-purple-100 to-pink-100 dark:from-purple-900 dark:to-pink-900 text-purple-800 dark:text-purple-300 text-xs rounded font-semibold">
                                    <i class="fas fa-magic mr-1"></i>Animation
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2 ml-4">
                        <a href="{{ route('admin.component-settings.edit', $component) }}"
                           class="px-4 py-2 bg-gradient-to-r from-blue-500 to-cyan-600 hover:from-blue-600 hover:to-cyan-700 text-white rounded-lg transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                            <i class="fas fa-edit mr-1"></i>แก้ไข
                        </a>
                        <button @click="deleteId = {{ $component->id }}"
                                class="px-4 py-2 bg-gradient-to-r from-red-500 to-pink-600 hover:from-red-600 hover:to-pink-700 text-white rounded-lg transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                            <i class="fas fa-trash mr-1"></i>ลบ
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="p-12 text-center">
                <div class="w-24 h-24 bg-gradient-to-br from-emerald-100 to-teal-100 dark:from-emerald-900/30 dark:to-teal-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-cube text-4xl text-emerald-600 dark:text-emerald-400"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ยังไม่มี Component</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">เริ่มต้นสร้าง Component แรกของคุณ</p>
                <a href="{{ route('admin.component-settings.create') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-bold">
                    <i class="fas fa-plus mr-2"></i>สร้าง Component
                </a>
            </div>
        @endforelse

        <!-- Pagination -->
        @if($components->hasPages())
            <div class="p-6 border-t border-gray-200 dark:border-slate-700">
                {{ $components->links() }}
            </div>
        @endif
    </div>

    <!-- Delete Modal -->
    <div x-show="deleteId" x-transition class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" style="display: none;">
        <div @click.away="deleteId = null" class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-md w-full p-6">
            <div class="text-center">
                <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exclamation-triangle text-3xl text-red-600 dark:text-red-400"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ยืนยันการลบ</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">คุณแน่ใจหรือไม่ว่าต้องการลบ Component นี้?</p>
                <div class="flex gap-3">
                    <button @click="deleteId = null" class="flex-1 px-4 py-2 bg-gray-200 dark:bg-slate-700 hover:bg-gray-300 dark:hover:bg-slate-600 text-gray-800 dark:text-gray-200 rounded-lg transition-all duration-200 font-medium">
                        ยกเลิก
                    </button>
                    <form :action="`/admin/component-settings/${deleteId}`" method="POST" class="flex-1">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full px-4 py-2 bg-gradient-to-r from-red-500 to-pink-600 hover:from-red-600 hover:to-pink-700 text-white rounded-lg transition-all duration-200 font-medium shadow-lg">
                            ลบ
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
