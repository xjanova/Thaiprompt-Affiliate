@extends('layouts.admin')

@section('title', 'TPIX Deployment Wizard - รายการ Configurations')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                        🚀 TPIX Deployment Wizard
                    </h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        จัดการการ Deploy TPIX Native Coin แบบ Step-by-Step
                    </p>
                </div>
                <a href="{{ route('admin.tpix.deployment.create') }}"
                   class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    สร้าง Configuration ใหม่
                </a>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            @foreach([
                ['label' => 'ทั้งหมด', 'value' => $stats['total'], 'color' => 'blue', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                ['label' => 'ฉบับร่าง', 'value' => $stats['draft'], 'color' => 'gray', 'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
                ['label' => 'Deploy แล้ว', 'value' => $stats['deployed'], 'color' => 'green', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['label' => 'List แล้ว', 'value' => $stats['listed'], 'color' => 'purple', 'icon' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z']
            ] as $stat)
                <div class="relative group">
                    <div class="absolute -inset-1 bg-gradient-to-r from-{{ $stat['color'] }}-600 to-{{ $stat['color'] }}-400 rounded-2xl blur opacity-25 group-hover:opacity-40 transition duration-300"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-xl">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ $stat['label'] }}</p>
                                <p class="text-3xl font-bold text-{{ $stat['color'] }}-600 dark:text-{{ $stat['color'] }}-400 mt-2">
                                    {{ $stat['value'] }}
                                </p>
                            </div>
                            <div class="p-3 bg-{{ $stat['color'] }}-100 dark:bg-{{ $stat['color'] }}-900/30 rounded-xl">
                                <svg class="w-8 h-8 text-{{ $stat['color'] }}-600 dark:text-{{ $stat['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $stat['icon'] }}"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Filters --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6" x-data="{ showFilters: false }">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ตัวกรอง</h3>
                <button @click="showFilters = !showFilters" class="text-blue-600 dark:text-blue-400 hover:underline">
                    <span x-text="showFilters ? 'ซ่อน' : 'แสดง'"></span> ตัวกรอง
                </button>
            </div>

            <form method="GET" x-show="showFilters" x-transition class="grid grid-cols-1 md:grid-cols-4 gap-4">
                {{-- Search --}}
                <div>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="ค้นหา (ชื่อ, Symbol, Contract)..."
                           class="w-full px-4 py-2 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition">
                </div>

                {{-- Status Filter --}}
                <div>
                    <select name="status" class="w-full px-4 py-2 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 transition">
                        <option value="">-- ทุกสถานะ --</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>ฉบับร่าง</option>
                        <option value="deployed" {{ request('status') === 'deployed' ? 'selected' : '' }}>Deploy แล้ว</option>
                        <option value="listed" {{ request('status') === 'listed' ? 'selected' : '' }}>List แล้ว</option>
                    </select>
                </div>

                {{-- Step Filter --}}
                <div>
                    <select name="step" class="w-full px-4 py-2 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 transition">
                        <option value="">-- ทุก Step --</option>
                        @for($i = 1; $i <= 7; $i++)
                            <option value="{{ $i }}" {{ request('step') == $i ? 'selected' : '' }}>Step {{ $i }}</option>
                        @endfor
                    </select>
                </div>

                {{-- Submit Button --}}
                <div>
                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition">
                        ค้นหา
                    </button>
                </div>
            </form>
        </div>

        {{-- Configurations List --}}
        <div class="space-y-4">
            @forelse($configurations as $config)
                <div class="relative group">
                    <div class="absolute -inset-1 bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl blur opacity-0 group-hover:opacity-25 transition duration-300"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 hover:shadow-2xl transition-all duration-300">
                        <div class="flex items-start justify-between">
                            {{-- Left: Info --}}
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-3">
                                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                                        {{ $config->name }}
                                    </h3>
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-{{ $config->getStatusBadgeColor() }}-100 text-{{ $config->getStatusBadgeColor() }}-800 dark:bg-{{ $config->getStatusBadgeColor() }}-900/30 dark:text-{{ $config->getStatusBadgeColor() }}-400">
                                        {{ $config->getStatusLabel() }}
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                    @if($config->token_name)
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400">Token:</span>
                                            <span class="font-semibold text-gray-900 dark:text-white">{{ $config->token_name }} ({{ $config->token_symbol }})</span>
                                        </div>
                                    @endif

                                    @if($config->contract_address)
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400">Contract:</span>
                                            <span class="font-mono text-xs text-blue-600 dark:text-blue-400">{{ Str::limit($config->contract_address, 20) }}</span>
                                        </div>
                                    @endif

                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">สร้างโดย:</span>
                                        <span class="font-semibold text-gray-900 dark:text-white">{{ $config->user->name ?? 'N/A' }}</span>
                                    </div>
                                </div>

                                {{-- Progress Bar --}}
                                <div class="mt-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">
                                            ความคืบหน้า: Step {{ $config->current_step }}/7
                                        </span>
                                        <span class="text-xs font-bold text-blue-600 dark:text-blue-400">
                                            {{ number_format($config->getProgressPercentage(), 0) }}%
                                        </span>
                                    </div>
                                    <div class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-blue-500 to-purple-600 rounded-full transition-all duration-500"
                                             style="width: {{ $config->getProgressPercentage() }}%"></div>
                                    </div>
                                </div>
                            </div>

                            {{-- Right: Actions --}}
                            <div class="ml-6 flex flex-col gap-2">
                                @if($config->current_step < 8)
                                    <a href="{{ route('admin.tpix.deployment.wizard', $config->slug) }}"
                                       class="px-6 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold rounded-xl hover:shadow-lg hover:scale-105 transition-all duration-300 text-center">
                                        ดำเนินการต่อ →
                                    </a>
                                @else
                                    <a href="{{ route('admin.tpix.deployment.wizard', $config->slug) }}"
                                       class="px-6 py-2 bg-green-600 text-white font-semibold rounded-xl hover:shadow-lg hover:scale-105 transition-all duration-300 text-center">
                                        ดูรายละเอียด
                                    </a>
                                @endif

                                @if(!$config->isDeployed())
                                    <form method="POST" action="{{ route('admin.tpix.deployment.destroy', $config->slug) }}"
                                          onsubmit="return confirm('คุณแน่ใจหรือไม่?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-full px-6 py-2 bg-red-600 text-white font-semibold rounded-xl hover:bg-red-700 transition">
                                            ลบ
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-16">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-100 dark:bg-gray-800 rounded-full mb-4">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">ยังไม่มี Configuration</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6">เริ่มต้นสร้าง TPIX Native Coin ของคุณเลย!</p>
                    <a href="{{ route('admin.tpix.deployment.create') }}"
                       class="inline-flex items-center gap-2 px-8 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        สร้าง Configuration แรก
                    </a>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($configurations->hasPages())
            <div class="mt-8">
                {{ $configurations->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Success/Error Messages --}}
@if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-transition
         class="fixed top-4 right-4 z-50 max-w-md">
        <div class="flex items-center gap-3 p-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-l-4 border-green-500 rounded-r-xl shadow-lg">
            <svg class="w-6 h-6 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            <p class="text-green-800 dark:text-green-200 font-medium">{{ session('success') }}</p>
            <button @click="show = false" class="ml-auto text-green-600 hover:text-green-800 text-2xl leading-none">&times;</button>
        </div>
    </div>
@endif

@if(session('error'))
    <div x-data="{ show: true }" x-show="show" x-transition
         class="fixed top-4 right-4 z-50 max-w-md">
        <div class="flex items-center gap-3 p-4 bg-gradient-to-r from-red-50 to-pink-50 dark:from-red-900/20 dark:to-pink-900/20 border-l-4 border-red-500 rounded-r-xl shadow-lg">
            <svg class="w-6 h-6 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
            </svg>
            <p class="text-red-800 dark:text-red-200 font-medium">{{ session('error') }}</p>
            <button @click="show = false" class="ml-auto text-red-600 hover:text-red-800 text-2xl leading-none">&times;</button>
        </div>
    </div>
@endif
@endsection
