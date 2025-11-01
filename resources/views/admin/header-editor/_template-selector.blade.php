<div class="bg-white rounded-lg shadow-lg p-6">
    <h2 class="text-2xl font-bold text-gray-900 mb-4">🎨 เลือกเทมเพลตหน้าแรก</h2>
    <p class="text-gray-600 mb-6">เลือกเทมเพลตที่ต้องการใช้แสดงในหน้าแรกของเว็บไซต์</p>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach($templates as $template)
        <div @click="selectTemplate({{ $template->id }})"
             class="border-2 rounded-lg p-4 cursor-pointer transition-all hover:shadow-lg"
             :class="currentTemplateId == {{ $template->id }} ? 'border-indigo-600 bg-indigo-50' : 'border-gray-200'">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <h3 class="font-bold text-gray-900">{{ $template->name }}</h3>
                    <p class="text-sm text-gray-600 mt-1">{{ $template->description }}</p>
                </div>
                <div>
                    @if($template->is_default)
                    <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-bold rounded-full">Default</span>
                    @endif
                </div>
            </div>

            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500">{{ $template->sections_count }} sections</span>
                <div class="flex gap-2">
                    <a href="{{ route('admin.templates.show', $template->id) }}"
                       target="_blank"
                       class="text-indigo-600 hover:text-indigo-800">
                        แก้ไข →
                    </a>
                </div>
            </div>

            @if($currentTemplateId == $template->id)
            <div class="mt-3 pt-3 border-t border-indigo-200">
                <span class="flex items-center text-sm text-indigo-700">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                    </svg>
                    กำลังใช้งานอยู่
                </span>
            </div>
            @endif
        </div>
        @endforeach
    </div>

    @if($templates->isEmpty())
    <div class="text-center py-12 text-gray-400">
        <p class="mb-4">ยังไม่มีเทมเพลต</p>
        <a href="{{ route('admin.templates.index') }}" class="text-indigo-600 hover:text-indigo-800">
            ไปสร้างเทมเพลตใหม่ →
        </a>
    </div>
    @endif
</div>

<script>
function templateSelector() {
    return {
        currentTemplateId: {{ $currentTemplateId ?? 'null' }},

        async selectTemplate(templateId) {
            if (!confirm('ต้องการเปลี่ยนเทมเพลตหน้าแรก?')) return;

            try {
                const response = await fetch('{{ route('admin.header-editor.template') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ template_id: templateId })
                });

                const data = await response.json();
                if (data.success) {
                    this.currentTemplateId = templateId;
                    alert(data.message);
                }
            } catch (error) {
                alert('เกิดข้อผิดพลาด: ' + error.message);
            }
        }
    };
}
</script>
