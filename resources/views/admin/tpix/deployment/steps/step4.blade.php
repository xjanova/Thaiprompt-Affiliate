@extends('admin.tpix.deployment._wizard-layout')

@section('step-content')
<form method="POST" action="{{ route('admin.tpix.deployment.step4.save', $config->slug) }}" class="space-y-8">
    @csrf

    {{-- Header --}}
    <div class="mb-8">
        <h2 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent mb-3">
            Step 4: Smart Contract
        </h2>
        <p class="text-gray-600 dark:text-gray-400">
            ปรับแต่ง Smart Contract และเลือกฟีเจอร์เพิ่มเติม
        </p>
    </div>

    {{-- Contract Features --}}
    <div class="p-6 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-2xl">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path>
            </svg>
            เลือกฟีเจอร์ของ Contract
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($availableFeatures as $key => $label)
                <label x-data="{ enabled: {{ in_array($key, old('contract_features', $config->contract_features ?? [])) ? 'true' : 'false' }} }"
                       class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 border-2 rounded-xl cursor-pointer transition-all duration-300 hover:shadow-lg"
                       :class="enabled ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/30' : 'border-gray-200 dark:border-gray-700'">
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $label }}</span>
                    <div @click="enabled = !enabled" class="relative">
                        <input type="checkbox" name="contract_features[]" value="{{ $key }}" :checked="enabled" class="sr-only" />
                        <div class="w-14 h-8 rounded-full transition-colors duration-300"
                             :class="enabled ? 'bg-gradient-to-r from-purple-500 to-pink-600' : 'bg-gray-300 dark:bg-gray-600'">
                        </div>
                        <div class="absolute left-1 top-1 w-6 h-6 bg-white rounded-full shadow-lg transition-transform duration-300"
                             :class="enabled ? 'transform translate-x-6' : ''">
                        </div>
                    </div>
                </label>
            @endforeach
        </div>
    </div>

    {{-- Access Control --}}
    <div class="p-6 bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-2xl">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 8a6 6 0 01-7.743 5.743L10 14l-1 1-1 1H6v2H2v-4l4.257-4.257A6 6 0 1118 8zm-6-4a1 1 0 100 2 2 2 0 012 2 1 1 0 102 0 4 4 0 00-4-4z" clip-rule="evenodd"></path>
            </svg>
            ระบบควบคุมการเข้าถึง <span class="text-red-500">*</span>
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach(['owner' => 'Owner Only', 'role_based' => 'Role-Based Access', 'dao' => 'DAO Governance', 'multi_sig' => 'Multi-Signature'] as $key => $label)
                <label class="relative cursor-pointer">
                    <input type="radio" name="access_control" value="{{ $key }}"
                           {{ old('access_control', $config->access_control) === $key ? 'checked' : '' }}
                           class="peer sr-only" required />
                    <div class="p-4 bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl
                                peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/30
                                hover:border-blue-300 transition-all duration-300 text-center">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300
                                     peer-checked:text-blue-900 dark:peer-checked:text-blue-100">
                            {{ $label }}
                        </span>
                    </div>
                </label>
            @endforeach
        </div>
        @error('access_control')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- Contract Code Preview --}}
    <div class="p-6 bg-gradient-to-r from-gray-50 to-slate-50 dark:from-gray-900/50 dark:to-slate-900/50 rounded-2xl">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <svg class="w-6 h-6 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
            </svg>
            Smart Contract Code
        </h3>

        <div class="relative">
            <textarea name="contract_code" rows="20"
                      class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white
                             focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition font-mono text-sm resize-none"
                      placeholder="// Contract code will be generated based on your configuration..."
                      required>{{ old('contract_code', $config->contract_code) }}</textarea>
            @error('contract_code')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                💡 Contract code จะถูก generate อัตโนมัติตาม configuration ที่คุณเลือก
            </p>
        </div>
    </div>

    {{-- Upgradeable Option --}}
    <div class="p-6 bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 rounded-2xl">
        <label x-data="{ enabled: {{ old('is_upgradeable', $config->is_upgradeable) ? 'true' : 'false' }} }"
               class="flex items-center justify-between cursor-pointer">
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                    <svg class="w-6 h-6 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"></path>
                    </svg>
                    Upgradeable Contract
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    เปิดใช้งาน Proxy Pattern เพื่อให้สามารถ upgrade contract ในอนาคตได้
                </p>
            </div>
            <div @click="enabled = !enabled" class="relative ml-6">
                <input type="checkbox" name="is_upgradeable" value="1" :checked="enabled" class="sr-only" />
                <div class="w-14 h-8 rounded-full transition-colors duration-300"
                     :class="enabled ? 'bg-gradient-to-r from-yellow-500 to-amber-600' : 'bg-gray-300 dark:bg-gray-600'">
                </div>
                <div class="absolute left-1 top-1 w-6 h-6 bg-white rounded-full shadow-lg transition-transform duration-300"
                     :class="enabled ? 'transform translate-x-6' : ''">
                </div>
            </div>
        </label>
    </div>

    {{-- Action Buttons --}}
    <div class="flex gap-4 pt-6">
        <a href="{{ route('admin.tpix.deployment.step3', $config->slug) }}"
           class="px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition">
            ← ย้อนกลับ
        </a>
        <button type="submit"
                class="flex-1 px-8 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300 flex items-center justify-center gap-2">
            ถัดไป: Deploy & Verify →
        </button>
    </div>
</form>
@endsection
