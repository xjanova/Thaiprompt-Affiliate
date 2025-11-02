@extends('layouts.admin')

@section('title', 'Email Templates')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">📋 Email Templates</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">จัดการ Template อีเมลสำหรับส่งอัตโนมัติ</p>
        </div>
        <a href="{{ route('admin.email.templates.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
            + สร้าง Template
        </a>
    </div>

    <!-- Templates Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($templates as $template)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <!-- Header -->
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $template->name }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {{ $template->category }} • {{ strtoupper($template->language) }}
                        </p>
                    </div>
                    <div>
                        @if($template->is_active)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                ✅ ใช้งาน
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                ⭕ ปิด
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Subject Preview -->
                <div class="mb-4">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">เรื่อง:</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ Str::limit($template->subject, 60) }}</p>
                </div>

                <!-- Variables -->
                @if($template->variables && count($template->variables) > 0)
                    <div class="mb-4">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Variables:</p>
                        <div class="flex flex-wrap gap-1">
                            @foreach($template->variables as $variable)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {!! '{{' . $variable . '}}' !!}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Description -->
                @if($template->description)
                    <div class="mb-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ Str::limit($template->description, 100) }}
                        </p>
                    </div>
                @endif

                <!-- Actions -->
                <div class="flex items-center space-x-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('admin.email.templates.edit', $template) }}" class="flex-1 px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm text-center">
                        แก้ไข
                    </a>
                    <button onclick="previewTemplate({{ $template->id }})" class="flex-1 px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-600 text-sm">
                        👁️ ดูตัวอย่าง
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-3 text-center py-12">
                <span class="text-6xl">📭</span>
                <p class="mt-4 text-gray-500 dark:text-gray-400">ยังไม่มี Email Template</p>
                <a href="{{ route('admin.email.templates.create') }}" class="mt-4 inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    สร้าง Template แรก
                </a>
            </div>
        @endforelse
    </div>
</div>

<!-- Preview Modal -->
<div id="previewModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closePreviewModal()"></div>

        <!-- Center modal -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <!-- Modal panel -->
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            <!-- Header -->
            <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white" id="modal-title">
                        📧 ตัวอย่าง Email Template
                    </h3>
                    <button onclick="closePreviewModal()" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div class="px-6 py-4">
                <!-- Loading State -->
                <div id="previewLoading" class="text-center py-12">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                    <p class="mt-4 text-gray-600 dark:text-gray-400">กำลังโหลดตัวอย่าง...</p>
                </div>

                <!-- Preview Content -->
                <div id="previewContent" class="hidden">
                    <!-- Subject -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เรื่อง (Subject):</label>
                        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                            <p id="previewSubject" class="text-gray-900 dark:text-white"></p>
                        </div>
                    </div>

                    <!-- Variables Used -->
                    <div id="previewVariablesSection" class="mb-4 hidden">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ตัวแปรที่ใช้:</label>
                        <div class="px-4 py-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                            <div id="previewVariables" class="flex flex-wrap gap-2"></div>
                        </div>
                    </div>

                    <!-- Email Body Tabs -->
                    <div class="mb-4">
                        <div class="border-b border-gray-200 dark:border-gray-700">
                            <nav class="-mb-px flex space-x-4" aria-label="Tabs">
                                <button onclick="switchPreviewTab('html')" id="htmlTab" class="preview-tab border-b-2 border-blue-500 py-2 px-1 text-sm font-medium text-blue-600 dark:text-blue-400">
                                    HTML Preview
                                </button>
                                <button onclick="switchPreviewTab('text')" id="textTab" class="preview-tab border-b-2 border-transparent py-2 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300">
                                    Plain Text
                                </button>
                            </nav>
                        </div>
                    </div>

                    <!-- HTML Preview -->
                    <div id="htmlPreview" class="preview-pane">
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden" style="max-height: 500px; overflow-y: auto;">
                            <iframe id="previewHtmlFrame" class="w-full" style="min-height: 400px; border: none;" sandbox="allow-same-origin"></iframe>
                        </div>
                    </div>

                    <!-- Text Preview -->
                    <div id="textPreview" class="preview-pane hidden">
                        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700" style="max-height: 500px; overflow-y: auto;">
                            <pre id="previewText" class="text-sm text-gray-900 dark:text-white whitespace-pre-wrap font-mono"></pre>
                        </div>
                    </div>
                </div>

                <!-- Error State -->
                <div id="previewError" class="hidden text-center py-12">
                    <span class="text-6xl">⚠️</span>
                    <p class="mt-4 text-red-600 dark:text-red-400" id="previewErrorMessage"></p>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                <div class="flex justify-end">
                    <button onclick="closePreviewModal()" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">
                        ปิด
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentPreviewTab = 'html';

function previewTemplate(templateId) {
    // Show modal
    document.getElementById('previewModal').classList.remove('hidden');

    // Reset states
    document.getElementById('previewLoading').classList.remove('hidden');
    document.getElementById('previewContent').classList.add('hidden');
    document.getElementById('previewError').classList.add('hidden');

    // Fetch preview data
    fetch(`/admin/email/templates/${templateId}/preview`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Failed to load preview');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Hide loading, show content
            document.getElementById('previewLoading').classList.add('hidden');
            document.getElementById('previewContent').classList.remove('hidden');

            // Populate subject
            document.getElementById('previewSubject').textContent = data.subject;

            // Populate variables
            if (data.variables && data.variables.length > 0) {
                document.getElementById('previewVariablesSection').classList.remove('hidden');
                const variablesContainer = document.getElementById('previewVariables');
                variablesContainer.innerHTML = '';

                data.variables.forEach(variable => {
                    const value = data.sample_data[variable] || 'N/A';
                    const badge = document.createElement('span');
                    badge.className = 'inline-flex items-center px-3 py-1 rounded-full text-xs bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
                    badge.innerHTML = `<code class="font-mono">@{{${variable}}}</code> = <strong class="ml-1">${value}</strong>`;
                    variablesContainer.appendChild(badge);
                });
            } else {
                document.getElementById('previewVariablesSection').classList.add('hidden');
            }

            // Populate HTML preview
            const iframe = document.getElementById('previewHtmlFrame');
            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            iframeDoc.open();
            iframeDoc.write(data.body_html);
            iframeDoc.close();

            // Adjust iframe height
            setTimeout(() => {
                iframe.style.height = (iframeDoc.body.scrollHeight + 20) + 'px';
            }, 100);

            // Populate text preview
            document.getElementById('previewText').textContent = data.body_text || 'ไม่มีข้อความแบบ Plain Text';

            // Reset to HTML tab
            switchPreviewTab('html');
        } else {
            throw new Error(data.message || 'Unknown error');
        }
    })
    .catch(error => {
        // Hide loading, show error
        document.getElementById('previewLoading').classList.add('hidden');
        document.getElementById('previewError').classList.remove('hidden');
        document.getElementById('previewErrorMessage').textContent = 'ไม่สามารถโหลดตัวอย่างได้: ' + error.message;
    });
}

function closePreviewModal() {
    document.getElementById('previewModal').classList.add('hidden');
}

function switchPreviewTab(tab) {
    currentPreviewTab = tab;

    // Update tab styles
    document.querySelectorAll('.preview-tab').forEach(tabBtn => {
        if (tabBtn.id === tab + 'Tab') {
            tabBtn.classList.remove('border-transparent', 'text-gray-500', 'dark:text-gray-400');
            tabBtn.classList.add('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
        } else {
            tabBtn.classList.add('border-transparent', 'text-gray-500', 'dark:text-gray-400');
            tabBtn.classList.remove('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
        }
    });

    // Show/hide panes
    document.querySelectorAll('.preview-pane').forEach(pane => {
        if (pane.id === tab + 'Preview') {
            pane.classList.remove('hidden');
        } else {
            pane.classList.add('hidden');
        }
    });
}

// Close modal on Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closePreviewModal();
    }
});
</script>
@endsection
