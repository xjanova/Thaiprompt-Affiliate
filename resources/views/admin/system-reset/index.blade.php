@extends('layouts.admin-v3')

@section('title', 'ระบบรีเซ็ต/ล้างข้อมูล')

@section('content')
<div class="space-y-6" x-data="systemReset()">
    <!-- Warning Banner -->
    <div class="bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 border-2 border-red-300 dark:border-red-800 rounded-xl p-6 shadow-lg">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-8 w-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <div class="ml-4 flex-1">
                <h3 class="text-lg font-bold text-red-800 dark:text-red-200">⚠️ คำเตือนสำคัญ!</h3>
                <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                    <p class="font-semibold">การรีเซ็ตข้อมูลเป็นการดำเนินการที่ไม่สามารถย้อนกลับได้!</p>
                    <ul class="list-disc list-inside mt-2 space-y-1">
                        <li><strong>ลบข้อมูล:</strong> ผู้ใช้ทั้งหมด, ธุรกรรม, Log, กิจกรรมผู้ใช้</li>
                        <li><strong>เก็บไว้:</strong> Super Admin (1 คน), การตั้งค่าระบบ, หมวดหมู่, Templates</li>
                        <li><strong>ผลลัพธ์:</strong> ระบบพร้อมใช้งานทันที เหมือนเริ่มต้นใหม่แต่การตั้งค่าครบถ้วน</li>
                        <li>ข้อมูลที่ถูกลบจะไม่สามารถกู้คืนได้</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white">ระบบรีเซ็ต/ล้างข้อมูล</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">เลือกประเภทข้อมูลที่ต้องการรีเซ็ต (เฉพาะ Super Admin เท่านั้น)</p>
        </div>
        <div class="flex gap-3">
            <button @click="showLogs = !showLogs"
                    class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-lg hover:shadow-xl">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                ประวัติการรีเซ็ต
            </button>
        </div>
    </div>

    <!-- Quick Reset Option -->
    @if(isset($resetOptions['full_reset']))
    <div class="bg-gradient-to-br from-red-500 to-rose-600 rounded-2xl shadow-2xl overflow-hidden">
        <div class="p-8">
            <div class="flex items-start gap-6">
                <div class="flex-shrink-0">
                    <div class="w-20 h-20 bg-white/20 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-sync-alt text-4xl text-white"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-2xl font-bold text-white mb-2">🔄 รีเซ็ตระบบทั้งหมด (Full Reset)</h3>
                    <p class="text-white/90 mb-4">ลบข้อมูลธุรกรรมและผู้ใช้ทั้งหมด เหลือเฉพาะ Super Admin และการตั้งค่าระบบ - ระบบพร้อมใช้งานทันที</p>
                    <div class="flex items-center gap-4 text-sm text-white/80 mb-4">
                        <span><i class="fas fa-database mr-2"></i>~{{ number_format($resetOptions['full_reset']['count']) }} รายการ</span>
                        <span><i class="fas fa-clock mr-2"></i>ใช้เวลา 1-3 นาที</span>
                    </div>
                    <button @click="selectFullReset()"
                            class="inline-flex items-center px-6 py-3 bg-white text-red-600 font-bold rounded-xl hover:bg-red-50 transition-all duration-200 shadow-lg hover:shadow-xl">
                        <i class="fas fa-sync-alt mr-2"></i>
                        รีเซ็ตระบบทั้งหมดทันที
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Advanced Options Toggle -->
    <div class="flex items-center justify-center">
        <button @click="showAdvanced = !showAdvanced"
                class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-gray-700 to-gray-800 dark:from-slate-700 dark:to-slate-800 text-white font-semibold rounded-xl hover:from-gray-800 hover:to-gray-900 transition-all duration-200 shadow-lg">
            <i class="fas fa-sliders-h mr-2"></i>
            <span x-text="showAdvanced ? 'ซ่อนตัวเลือกขั้นสูง' : 'แสดงตัวเลือกขั้นสูง (เลือกเอง)'"></span>
            <i class="fas ml-2" :class="showAdvanced ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
        </button>
    </div>

    <!-- Reset Options Grid (Advanced) -->
    <div x-show="showAdvanced" x-cloak x-transition class="space-y-4">
        <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-4 rounded">
            <p class="text-sm text-blue-800 dark:text-blue-200">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>ตัวเลือกขั้นสูง:</strong> เลือกเฉพาะหมวดข้อมูลที่ต้องการลบ (สามารถเลือกได้หลายหมวด)
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($resetOptions as $key => $option)
                @if($key !== 'full_reset')
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border-2 border-gray-200 dark:border-slate-700 overflow-hidden hover:shadow-xl transition-all duration-200"
                     :class="selectedOptions.includes('{{ $key }}') ? 'ring-2 ring-offset-2 ring-indigo-500 border-indigo-500' : ''">
                    <div class="p-5">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-start flex-1">
                                <input type="checkbox"
                                       x-model="selectedOptions"
                                       value="{{ $key }}"
                                       id="option_{{ $key }}"
                                       class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 mt-0.5">
                                <label for="option_{{ $key }}" class="ml-3 cursor-pointer flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <i class="fas fa-{{ $option['icon'] }} {{ $option['danger_level'] === 'critical' ? 'text-red-500' : ($option['danger_level'] === 'high' ? 'text-orange-500' : ($option['danger_level'] === 'medium' ? 'text-yellow-500' : 'text-blue-500')) }}"></i>
                                        <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $option['label'] }}</span>
                                    </div>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 leading-tight">{{ $option['description'] }}</p>
                                </label>
                            </div>
                        </div>
                        <div class="flex items-center justify-between text-xs pt-3 border-t border-gray-200 dark:border-slate-600">
                            <span class="px-2 py-1 rounded-full {{ $option['danger_level'] === 'critical' ? 'bg-red-100 text-red-800' : ($option['danger_level'] === 'high' ? 'bg-orange-100 text-orange-800' : ($option['danger_level'] === 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800')) }}">
                                {{ $option['danger_level'] === 'critical' ? 'วิกฤติ' : ($option['danger_level'] === 'high' ? 'สูง' : ($option['danger_level'] === 'medium' ? 'ปานกลาง' : 'ต่ำ')) }}
                            </span>
                            <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($option['count']) }}</span>
                        </div>
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex justify-between items-center bg-white dark:bg-slate-800 rounded-xl shadow-lg border-2 border-gray-200 dark:border-slate-700 p-6">
        <div class="text-sm text-gray-600 dark:text-gray-300">
            <span x-show="selectedOptions.length === 0">กรุณาเลือกประเภทข้อมูลที่ต้องการรีเซ็ต</span>
            <span x-show="selectedOptions.length > 0" class="font-bold text-indigo-600">
                เลือกแล้ว <span x-text="selectedOptions.length"></span> รายการ
            </span>
        </div>
        <div class="flex gap-3">
            <button @click="selectAll()"
                    class="px-5 py-2.5 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-300 dark:hover:bg-slate-600 transition-all duration-200">
                เลือกทั้งหมด
            </button>
            <button @click="clearSelection()"
                    class="px-5 py-2.5 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-300 dark:hover:bg-slate-600 transition-all duration-200">
                ยกเลิกการเลือก
            </button>
            <button @click="confirmReset()"
                    :disabled="selectedOptions.length === 0"
                    :class="selectedOptions.length === 0 ? 'opacity-50 cursor-not-allowed' : 'hover:from-red-700 hover:to-rose-700 hover:shadow-xl'"
                    class="px-6 py-2.5 bg-gradient-to-r from-red-600 to-rose-600 text-white font-bold rounded-xl transition-all duration-200 shadow-lg">
                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                รีเซ็ตข้อมูล
            </button>
        </div>
    </div>

    <!-- Reset Logs (Toggle) -->
    <div x-show="showLogs" x-cloak x-transition class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border-2 border-gray-200 dark:border-slate-700 overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border-b border-gray-200 dark:border-slate-700">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">ประวัติการรีเซ็ต</h3>
        </div>
        <div class="p-6">
            @if($recentLogs->isEmpty())
                <p class="text-center text-gray-500 dark:text-gray-400 py-8">ยังไม่มีประวัติการรีเซ็ต</p>
            @else
                <div class="space-y-4">
                    @foreach($recentLogs as $log)
                    <div class="border-2 border-gray-200 dark:border-slate-700 rounded-xl p-4 hover:shadow-md transition-all duration-200">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="px-3 py-1 text-xs font-bold rounded-full
                                        {{ $log->status === 'completed' ? 'bg-green-100 text-green-800' :
                                           ($log->status === 'failed' ? 'bg-red-100 text-red-800' :
                                           ($log->status === 'processing' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800')) }}">
                                        {{ $log->status_label }}
                                    </span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $log->created_at->diffForHumans() }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">
                                    ดำเนินการโดย: <span class="font-semibold">{{ $log->performedBy->name }}</span>
                                </p>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    รีเซ็ต: <span class="font-semibold">{{ count($log->reset_options) }}</span> รายการ
                                    @if($log->status === 'completed' && $log->duration_seconds)
                                        | ใช้เวลา: <span class="font-semibold">{{ $log->duration_human }}</span>
                                    @endif
                                </div>
                            </div>
                            <a href="{{ route('admin.system-reset.show', $log->id) }}"
                               class="px-4 py-2 bg-blue-100 text-blue-800 rounded-lg hover:bg-blue-200 transition-all duration-200 text-sm font-semibold">
                                ดูรายละเอียด
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div x-show="showConfirmModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <!-- Background overlay -->
            <div x-show="showConfirmModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75"
                 @click="showConfirmModal = false"></div>

            <!-- Modal panel -->
            <div x-show="showConfirmModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative inline-block align-bottom bg-white dark:bg-slate-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">

                <div class="bg-gradient-to-r from-red-600 to-rose-600 px-6 py-4">
                    <h3 class="text-xl font-bold text-white flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        ยืนยันการรีเซ็ตข้อมูล
                    </h3>
                </div>

                <div class="px-6 py-5">
                    <div class="mb-4">
                        <p class="text-gray-700 dark:text-gray-300 mb-3 font-semibold">คุณกำลังจะรีเซ็ตข้อมูลประเภทต่อไปนี้:</p>
                        <ul class="list-disc list-inside space-y-1 text-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-slate-900 rounded-lg p-4">
                            <template x-for="option in selectedOptions" :key="option">
                                <li x-text="getOptionLabel(option)"></li>
                            </template>
                        </ul>
                    </div>

                    <div class="bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-800 rounded-xl p-4 mb-4">
                        <p class="text-sm text-red-800 dark:text-red-200 font-semibold">
                            ⚠️ การดำเนินการนี้ไม่สามารถย้อนกลับได้! ข้อมูลที่ถูกลบจะไม่สามารถกู้คืนได้
                        </p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            กรุณาพิมพ์ <span class="text-red-600 font-mono">RESET</span> เพื่อยืนยัน:
                        </label>
                        <input type="text"
                               x-model="confirmationText"
                               placeholder="พิมพ์ RESET"
                               class="w-full px-4 py-3 border-2 border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 font-mono text-lg">
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-slate-900 px-6 py-4 flex gap-3 justify-end">
                    <button @click="showConfirmModal = false"
                            class="px-5 py-2.5 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-300 dark:hover:bg-slate-600 transition-all duration-200">
                        ยกเลิก
                    </button>
                    <button @click="performReset()"
                            :disabled="confirmationText !== 'RESET' || isProcessing"
                            :class="confirmationText === 'RESET' && !isProcessing ? 'hover:from-red-700 hover:to-rose-700' : 'opacity-50 cursor-not-allowed'"
                            class="px-6 py-2.5 bg-gradient-to-r from-red-600 to-rose-600 text-white font-bold rounded-xl transition-all duration-200 shadow-lg">
                        <span x-show="!isProcessing">ยืนยันการรีเซ็ต</span>
                        <span x-show="isProcessing" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            กำลังดำเนินการ...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function systemReset() {
    return {
        selectedOptions: [],
        showConfirmModal: false,
        showLogs: false,
        showAdvanced: false,
        confirmationText: '',
        isProcessing: false,
        resetOptions: @json($resetOptions),

        selectFullReset() {
            this.selectedOptions = ['full_reset'];
            this.confirmationText = '';
            this.showConfirmModal = true;
        },

        selectAll() {
            this.selectedOptions = Object.keys(this.resetOptions).filter(key => key !== 'full_reset');
        },

        clearSelection() {
            this.selectedOptions = [];
        },

        getOptionLabel(key) {
            return this.resetOptions[key]?.label || key;
        },

        confirmReset() {
            if (this.selectedOptions.length === 0) {
                alert('กรุณาเลือกประเภทข้อมูลที่ต้องการรีเซ็ต');
                return;
            }
            this.confirmationText = '';
            this.showConfirmModal = true;
        },

        async performReset() {
            if (this.confirmationText !== 'RESET') {
                return;
            }

            this.isProcessing = true;

            try {
                const response = await fetch('{{ route('admin.system-reset.reset') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        options: this.selectedOptions,
                        confirmation_text: this.confirmationText
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert('✅ รีเซ็ตข้อมูลเรียบร้อยแล้ว!\n\n' + this.formatSummary(data.summary) + '\n\nระบบพร้อมใช้งานแล้ว');
                    this.showConfirmModal = false;
                    this.selectedOptions = [];
                    setTimeout(() => location.reload(), 1500);
                } else {
                    alert('❌ เกิดข้อผิดพลาด: ' + data.message);
                }
            } catch (error) {
                alert('❌ เกิดข้อผิดพลาด: ' + error.message);
            } finally {
                this.isProcessing = false;
            }
        },

        formatSummary(summary) {
            let text = '';
            let totalDeleted = 0;
            for (const [key, value] of Object.entries(summary)) {
                text += `• ${value.label}: ${value.deleted_count.toLocaleString()} รายการ\n`;
                totalDeleted += value.deleted_count;
            }
            text += `\n📊 รวมทั้งหมด: ${totalDeleted.toLocaleString()} รายการ`;
            return text;
        }
    }
}
</script>
@endsection
