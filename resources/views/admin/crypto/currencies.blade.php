@extends('layouts.admin-v3')

@section('title', 'จัดการเหรียญ/สกุลเงินดิจิทัล')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-amber-600 via-orange-600 to-red-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white opacity-10 rounded-full"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-white opacity-10 rounded-full"></div>
        <div class="relative z-10">
            <h1 class="text-3xl font-bold mb-2">💰 จัดการเหรียญ/สกุลเงินดิจิทัล</h1>
            <p class="text-orange-100">ตั้งค่าและจัดการ Cryptocurrency ที่รองรับในระบบ</p>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="bg-green-100 dark:bg-green-900 border-l-4 border-green-500 text-green-700 dark:text-green-100 p-4 rounded-lg shadow-md" role="alert">
            <div class="flex items-center">
                <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="font-semibold">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 dark:bg-red-900 border-l-4 border-red-500 text-red-700 dark:text-red-100 p-4 rounded-lg shadow-md" role="alert">
            <div class="flex items-center">
                <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="font-semibold">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    <!-- Currencies Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @forelse($currencies as $currency)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border-2 {{ $currency->is_active ? 'border-green-500' : 'border-gray-300 dark:border-gray-600' }}">
            <div class="p-6">
                <!-- Currency Header -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 rounded-full bg-gradient-to-br from-{{ $currency->color ?? 'blue' }}-400 to-{{ $currency->color ?? 'blue' }}-600 flex items-center justify-center text-white text-2xl font-bold shadow-lg">
                            {{ $currency->symbol ?? substr($currency->code, 0, 1) }}
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $currency->code }}</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $currency->name }}</p>
                            @if($currency->network)
                                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                    🌐 {{ $currency->network }}
                                </p>
                            @endif
                        </div>
                    </div>
                    <div class="text-right">
                        @if($currency->is_active)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                <span class="w-2 h-2 mr-2 bg-green-500 rounded-full animate-pulse"></span>
                                เปิดใช้งาน
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                <span class="w-2 h-2 mr-2 bg-red-500 rounded-full"></span>
                                ปิดใช้งาน
                            </span>
                        @endif
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                            {{ number_format($currency->transactions_count ?? 0) }} ธุรกรรม
                        </div>
                    </div>
                </div>

                <!-- Currency Settings Form -->
                <form action="{{ route('admin.crypto.currencies.update', $currency->id) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <!-- Toggle Switches -->
                    <div class="grid grid-cols-3 gap-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <!-- Active Status -->
                        <label class="flex items-center cursor-pointer group">
                            <input type="checkbox" name="is_active" value="1" {{ $currency->is_active ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-green-600"></div>
                            <span class="ms-3 text-sm font-medium text-gray-700 dark:text-gray-300">เปิดใช้งาน</span>
                        </label>

                        <!-- Deposit Enabled -->
                        <label class="flex items-center cursor-pointer group">
                            <input type="checkbox" name="deposit_enabled" value="1" {{ $currency->deposit_enabled ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                            <span class="ms-3 text-sm font-medium text-gray-700 dark:text-gray-300">ฝากได้</span>
                        </label>

                        <!-- Withdrawal Enabled -->
                        <label class="flex items-center cursor-pointer group">
                            <input type="checkbox" name="withdrawal_enabled" value="1" {{ $currency->withdrawal_enabled ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-purple-600"></div>
                            <span class="ms-3 text-sm font-medium text-gray-700 dark:text-gray-300">ถอนได้</span>
                        </label>
                    </div>

                    <!-- Limits & Fees -->
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Min Deposit -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                ฝากขั้นต่ำ ({{ $currency->code }})
                            </label>
                            <input type="number" step="0.00000001" name="min_deposit" value="{{ $currency->min_deposit ?? 0 }}"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <!-- Withdrawal Fee -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                ค่าธรรมเนียมถอน ({{ $currency->code }})
                            </label>
                            <input type="number" step="0.00000001" name="withdrawal_fee" value="{{ $currency->withdrawal_fee ?? 0 }}"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <!-- Min Withdrawal -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                ถอนขั้นต่ำ ({{ $currency->code }})
                            </label>
                            <input type="number" step="0.00000001" name="min_withdrawal" value="{{ $currency->min_withdrawal ?? 0 }}"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <!-- Max Withdrawal -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                ถอนสูงสุด ({{ $currency->code }})
                            </label>
                            <input type="number" step="0.00000001" name="max_withdrawal" value="{{ $currency->max_withdrawal ?? 0 }}"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="flex justify-end pt-4">
                        <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-lg shadow-lg transform hover:scale-105 transition duration-200 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            บันทึกการตั้งค่า
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @empty
        <div class="col-span-2">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-12">
                <div class="flex flex-col items-center justify-center text-center">
                    <svg class="w-20 h-20 text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400 text-xl font-semibold">ไม่พบสกุลเงินดิจิทัล</p>
                    <p class="text-gray-400 dark:text-gray-500 text-sm mt-2">กรุณาเพิ่มสกุลเงินใหม่ในระบบ</p>
                </div>
            </div>
        </div>
        @endforelse
    </div>
</div>

@push('scripts')
<script>
    // Auto-submit form on toggle change (optional)
    document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            // Optional: Auto-save on toggle
            // this.closest('form').submit();
        });
    });
</script>
@endpush
@endsection
