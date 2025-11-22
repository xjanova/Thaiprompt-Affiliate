@extends('layouts.admin')

@section('title', 'TPIX Deployment Wizard - Step ' . $config->current_step)

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Progress Bar --}}
        <div class="mb-8">
            <div class="relative">
                {{-- Progress Line --}}
                <div class="absolute top-5 left-0 w-full h-1 bg-gray-200 dark:bg-gray-700 rounded-full">
                    <div class="h-full bg-gradient-to-r from-blue-500 to-purple-500 rounded-full transition-all duration-500"
                         style="width: {{ ($config->current_step / 7) * 100 }}%"></div>
                </div>

                {{-- Steps --}}
                <div class="relative flex justify-between">
                    @foreach([
                        ['num' => 1, 'label' => 'Prerequisites', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['num' => 2, 'label' => 'Config', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
                        ['num' => 3, 'label' => 'Tokenomics', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['num' => 4, 'label' => 'Contract', 'icon' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4'],
                        ['num' => 5, 'label' => 'Deploy', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z'],
                        ['num' => 6, 'label' => 'DEX', 'icon' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4'],
                        ['num' => 7, 'label' => 'List', 'icon' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z']
                    ] as $step)
                        @php $isCompleted = $config->current_step > $step['num']; @endphp
                        @php $isCurrent = $config->current_step === $step['num']; @endphp

                        <div class="flex flex-col items-center" x-data="{ show: false }" @mouseenter="show = true" @mouseleave="show = false">
                            {{-- Circle --}}
                            <div class="w-12 h-12 rounded-full flex items-center justify-center transition-all duration-300 transform
                                        {{ $isCompleted ? 'bg-gradient-to-br from-green-500 to-emerald-600 text-white shadow-lg scale-110' : '' }}
                                        {{ $isCurrent ? 'bg-gradient-to-br from-blue-500 to-purple-600 text-white shadow-xl scale-125 ring-4 ring-blue-500/30' : '' }}
                                        {{ !$isCompleted && !$isCurrent ? 'bg-gray-200 dark:bg-gray-700 text-gray-500' : '' }}">
                                @if($isCompleted)
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                @else
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $step['icon'] }}"></path>
                                    </svg>
                                @endif
                            </div>

                            {{-- Label --}}
                            <span class="mt-2 text-xs font-medium transition-colors duration-300
                                         {{ $isCurrent ? 'text-blue-600 dark:text-blue-400 font-bold' : 'text-gray-600 dark:text-gray-400' }}">
                                {{ $step['label'] }}
                            </span>

                            {{-- Tooltip --}}
                            <div x-show="show" x-transition
                                 class="absolute top-16 z-10 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg shadow-lg whitespace-nowrap">
                                Step {{ $step['num'] }}: {{ $step['label'] }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Step Info --}}
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Step <strong class="text-blue-600 dark:text-blue-400">{{ $config->current_step }}/7</strong> •
                    <strong>{{ $config->getCurrentStepTitle() }}</strong> •
                    <span class="text-gray-500">Progress: {{ number_format($config->getProgressPercentage(), 0) }}%</span>
                </p>
            </div>
        </div>

        {{-- Wallet Connection (ด้านบน) --}}
        <div class="mb-6">
            @php
                $pricingService = app(\App\Services\TPIX\TpixPricingService::class);
                $pricing = $pricingService->calculateTotalPrice($config);
                $requiredTPIX = $pricing['total'];
            @endphp
            <x-tpix.wallet-connector
                :requiredAmount="$requiredTPIX"
                :showBalance="true"
                :required="true"
            />
        </div>

        {{-- Content Area (2 Columns: Content + Pricing Sidebar) --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Content (Left - 2/3) --}}
            <div class="lg:col-span-2">
                <div class="relative group">
                    {{-- Glow Effect --}}
                    <div class="absolute -inset-1 bg-gradient-to-r from-blue-600 to-purple-600 rounded-3xl blur-lg opacity-25 group-hover:opacity-30 transition duration-300"></div>

                    {{-- Card --}}
                    <div class="relative bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl rounded-3xl shadow-2xl p-8 md:p-12 border border-white/20 dark:border-gray-700/50">
                        @yield('step-content')
                    </div>
                </div>
            </div>

            {{-- Pricing Sidebar (Right - 1/3) --}}
            <div class="lg:col-span-1">
                <div class="sticky top-4">
                    <x-tpix.pricing-calculator
                        :config="$config"
                        :showBreakdown="true"
                    />
                </div>
            </div>
        </div>

        {{-- Help Box --}}
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                💡 <strong>Tip:</strong> คุณสามารถบันทึกและกลับมาทำต่อได้ทุกเมื่อ
                • <a href="{{ route('admin.tpix.deployment.index') }}" class="text-blue-600 dark:text-blue-400 hover:underline">กลับไปรายการ</a>
            </p>
        </div>
    </div>
</div>

{{-- Success/Error/Warning Messages --}}
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

@if(session('warning'))
    <div x-data="{ show: true }" x-show="show" x-transition
         class="fixed top-4 right-4 z-50 max-w-md">
        <div class="flex items-center gap-3 p-4 bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 border-l-4 border-yellow-500 rounded-r-xl shadow-lg">
            <svg class="w-6 h-6 text-yellow-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
            </svg>
            <p class="text-yellow-800 dark:text-yellow-200 font-medium">{{ session('warning') }}</p>
            <button @click="show = false" class="ml-auto text-yellow-600 hover:text-yellow-800 text-2xl leading-none">&times;</button>
        </div>
    </div>
@endif
@endsection
