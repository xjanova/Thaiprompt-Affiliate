{{--
    Report Modal Component
    Modal สำหรับรายงานเนื้อหาที่ไม่เหมาะสม
--}}

<div x-show="showReportModal"
     x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 flex items-center justify-center p-4">

    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showReportModal = false"></div>

    {{-- Modal Content --}}
    <div x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="scale-95 opacity-0"
         x-transition:enter-end="scale-100 opacity-100"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="scale-100 opacity-100"
         x-transition:leave-end="scale-95 opacity-0"
         class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">

        {{-- Header --}}
        <div class="bg-gradient-to-r from-red-500 to-pink-600 px-6 py-4">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-white flex items-center gap-2">
                    <i class="fas fa-flag"></i>
                    รายงาน{{ $type === 'thread' ? 'กระทู้' : 'คอมเมนต์' }}
                </h3>
                <button @click="showReportModal = false"
                        class="text-white hover:text-gray-200 transition-colors">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
        </div>

        {{-- Body --}}
        <form action="{{ $type === 'thread' ? route('forum.thread.report', $id) : route('forum.post.report', $id) }}"
              method="POST"
              @submit.prevent="submitReport($event)"
              class="p-6">
            @csrf

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        เหตุผลการรายงาน *
                    </label>
                    <select name="reason_type"
                            required
                            class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-700 border-0 rounded-xl focus:ring-2 focus:ring-red-500">
                        <option value="">-- เลือกเหตุผล --</option>
                        <option value="spam">🚫 สแปม</option>
                        <option value="harassment">😠 คุกคาม/กลั่นแกล้ง</option>
                        <option value="inappropriate">🔞 เนื้อหาไม่เหมาะสม</option>
                        <option value="misinformation">❌ ข้อมูลเท็จ</option>
                        <option value="hate_speech">💢 Hate Speech</option>
                        <option value="copyright">©️ ละเมิดลิขสิทธิ์</option>
                        <option value="other">📝 อื่นๆ</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        รายละเอียดเพิ่มเติม
                    </label>
                    <textarea name="reason_detail"
                              rows="4"
                              placeholder="อธิบายเพิ่มเติมว่าเนื้อหานี้ละเมิดกฎอย่างไร..."
                              class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-700 border-0 rounded-xl focus:ring-2 focus:ring-red-500 resize-none"></textarea>
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex justify-end gap-3 mt-6">
                <button type="button"
                        @click="showReportModal = false"
                        class="px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                    ยกเลิก
                </button>
                <button type="submit"
                        class="px-6 py-2 bg-gradient-to-r from-red-500 to-pink-600 text-white rounded-xl hover:shadow-lg transition-all">
                    <i class="fas fa-paper-plane mr-2"></i>
                    ส่งรายงาน
                </button>
            </div>
        </form>
    </div>
</div>

@once
@push('scripts')
<script>
async function submitReport(event) {
    const form = event.target;
    const formData = new FormData(form);

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            alert(data.message);
            // ปิด modal
            Alpine.$data(form.closest('[x-data]')).showReportModal = false;
        } else {
            alert(data.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่');
        }
    } catch (error) {
        console.error('Error submitting report:', error);
        alert('เกิดข้อผิดพลาด กรุณาลองใหม่');
    }
}
</script>
@endpush
@endonce
