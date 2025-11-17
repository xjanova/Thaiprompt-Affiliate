@extends('layouts.admin-v3')
@section('title', $bot->display_name)

@push('styles')
<style>
    .detail-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        overflow: hidden;
        margin-bottom: 24px;
    }
    .dark .detail-card {
        background: #1e293b;
        box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    }
    .detail-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 32px;
        border-radius: 16px;
        margin-bottom: 24px;
    }
    .dark .detail-header {
        background: linear-gradient(135deg, #5568d3 0%, #6941a0 100%);
    }
    .stat-box {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        transition: transform 0.3s ease;
    }
    .dark .stat-box {
        background: #1e293b;
        box-shadow: 0 2px 12px rgba(0,0,0,0.3);
    }
    .stat-box:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    .dark .stat-box:hover {
        box-shadow: 0 8px 20px rgba(0,0,0,0.4);
    }
    .stat-label {
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .dark .stat-label {
        color: #9ca3af;
    }
    .stat-value {
        font-size: 32px;
        font-weight: 700;
        color: #1f2937;
    }
    .dark .stat-value {
        color: #f3f4f6;
    }
    .stat-icon {
        font-size: 24px;
        color: #667eea;
        opacity: 0.8;
    }
    .section-title {
        font-size: 18px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 16px;
        padding: 16px 24px;
        border-bottom: 2px solid #f3f4f6;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .dark .section-title {
        color: #f3f4f6;
        border-bottom-color: #334155;
    }
    .section-title i {
        color: #667eea;
    }
    .info-row {
        padding: 16px 24px;
        border-bottom: 1px solid #f3f4f6;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .dark .info-row {
        border-bottom-color: #334155;
    }
    .info-row:last-child {
        border-bottom: none;
    }
    .info-label {
        font-size: 14px;
        font-weight: 600;
        color: #6b7280;
    }
    .dark .info-label {
        color: #9ca3af;
    }
    .info-value {
        font-size: 14px;
        color: #1f2937;
        text-align: right;
    }
    .dark .info-value {
        color: #e5e7eb;
    }
    .badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    .badge-success {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }
    .badge-danger {
        background: #fee2e2;
        color: #991b1b;
    }
    .badge-info {
        background: #dbeafe;
        color: #1e40af;
    }
    .test-panel {
        background: #f9fafb;
        border-radius: 12px;
        padding: 24px;
    }
    .dark .test-panel {
        background: #0f172a;
    }
    .chat-message {
        padding: 16px;
        border-radius: 12px;
        margin-bottom: 12px;
        max-width: 80%;
    }
    .chat-message.user {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        margin-left: auto;
    }
    .dark .chat-message.user {
        background: linear-gradient(135deg, #5568d3 0%, #6941a0 100%);
    }
    .chat-message.bot {
        background: white;
        border: 2px solid #e5e7eb;
        margin-right: auto;
    }
    .dark .chat-message.bot {
        background: #1e293b;
        border-color: #475569;
        color: #e5e7eb;
    }
    .btn {
        padding: 12px 24px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 14px;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
    }
    .btn-secondary {
        background: #e5e7eb;
        color: #374151;
    }
    .btn-secondary:hover {
        background: #d1d5db;
    }
    .btn-warning {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
    }
    .btn-warning:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(245, 158, 11, 0.4);
    }
    .loading-spinner {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 1s ease-in-out infinite;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    .code-block {
        background: #1f2937;
        color: #f3f4f6;
        padding: 16px;
        border-radius: 8px;
        font-family: 'Courier New', monospace;
        font-size: 13px;
        overflow-x: auto;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4" x-data="botDetail()">

    <!-- Page Header -->
    <div class="detail-header">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-4">
                <div class="w-20 h-20 rounded-xl flex items-center justify-center text-4xl glass-fusion bg-opacity-20" border border-white/20 dark:border-white/10>
                    <i class="fas fa-robot"></i>
                </div>
                <div>
                    <h2 class="text-3xl font-bold mb-2">{{ $bot->display_name }}</h2>
                    <p class="opacity-90">
                        <span class="font-mono glass-fusion bg-opacity-20 px-3 py-1 rounded">{{ $bot->name }}</span>
                    </p>
                </div>
            </div>
            <div class="flex gap-2">
                @if($bot->owner_id === Auth::id())
                <a href="{{ route('admin.ai-bots.edit', $bot->id) }}" class="btn btn-warning">
                    <i class="fas fa-edit"></i> แก้ไข
                </a>
                @endif
                <a href="{{ route('admin.ai-bots.index') }}" class="glass-fusion bg-opacity-20 hover:bg-opacity-30 text-white px-4 py-2 rounded-xl transition-all">
                    <i class="fas fa-arrow-left mr-2"></i>กลับ
                </a>
            </div>
        </div>

        <div class="flex gap-3">
            <span class="badge {{ $bot->is_active ? 'badge-success' : 'badge-danger' }}">
                <i class="fas fa-circle mr-2" style="font-size: 8px;"></i>
                {{ $bot->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
            </span>
            @if($bot->is_public)
            <span class="badge" style="background: white; color: #667eea;">
                <i class="fas fa-globe mr-2"></i>Public
            </span>
            @endif
            @if($bot->line_oa_channel_id)
            <span class="badge" style="background: #06c755; color: white;">
                <i class="fab fa-line mr-2"></i>LINE OA Connected
            </span>
            @endif
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="stat-box">
            <div class="flex items-center justify-between mb-2">
                <span class="stat-label">Conversations</span>
                <i class="fas fa-comments stat-icon"></i>
            </div>
            <div class="stat-value">{{ number_format($stats['total_conversations']) }}</div>
        </div>

        <div class="stat-box">
            <div class="flex items-center justify-between mb-2">
                <span class="stat-label">Messages</span>
                <i class="fas fa-paper-plane stat-icon"></i>
            </div>
            <div class="stat-value">{{ number_format($stats['total_messages']) }}</div>
        </div>

        <div class="stat-box">
            <div class="flex items-center justify-between mb-2">
                <span class="stat-label">Tokens Used</span>
                <i class="fas fa-microchip stat-icon"></i>
            </div>
            <div class="stat-value">{{ number_format($stats['total_tokens']) }}</div>
        </div>

        <div class="stat-box">
            <div class="flex items-center justify-between mb-2">
                <span class="stat-label">Total Cost</span>
                <i class="fas fa-dollar-sign stat-icon"></i>
            </div>
            <div class="stat-value">${{ number_format($stats['total_cost'], 4) }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Bot Details -->
        <div class="lg:col-span-2">
            <!-- Bot Information -->
            <div class="detail-card">
                <div class="section-title">
                    <i class="fas fa-info-circle"></i>
                    <span>ข้อมูล Bot</span>
                </div>

                @if($bot->description)
                <div class="info-row">
                    <span class="info-label">คำอธิบาย</span>
                    <span class="info-value">{{ $bot->description }}</span>
                </div>
                @endif

                <div class="info-row">
                    <span class="info-label">AI Provider</span>
                    <span class="info-value">
                        <i class="fas fa-server mr-2" style="color: #667eea;"></i>
                        {{ $bot->provider->display_name }}
                    </span>
                </div>

                <div class="info-row">
                    <span class="info-label">AI Model</span>
                    <span class="info-value">
                        <i class="fas fa-brain mr-2" style="color: #667eea;"></i>
                        {{ $bot->model->display_name }}
                    </span>
                </div>

                <div class="info-row">
                    <span class="info-label">เจ้าของ</span>
                    <span class="info-value">
                        <i class="fas fa-user mr-2" style="color: #667eea;"></i>
                        {{ $bot->owner->name }}
                    </span>
                </div>

                <div class="info-row">
                    <span class="info-label">สร้างเมื่อ</span>
                    <span class="info-value">{{ $bot->created_at->format('d M Y H:i') }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">อัปเดตล่าสุด</span>
                    <span class="info-value">{{ $bot->updated_at->diffForHumans() }}</span>
                </div>
            </div>

            <!-- AI Parameters -->
            <div class="detail-card">
                <div class="section-title">
                    <i class="fas fa-sliders-h"></i>
                    <span>พารามิเตอร์ AI</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Temperature</span>
                    <span class="info-value">{{ $bot->temperature ?? '0.7' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Max Tokens</span>
                    <span class="info-value">{{ number_format($bot->max_tokens ?? 2000) }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Top P</span>
                    <span class="info-value">{{ $bot->top_p ?? '1.0' }}</span>
                </div>

                @if($bot->frequency_penalty || $bot->presence_penalty)
                <div class="info-row">
                    <span class="info-label">Frequency Penalty</span>
                    <span class="info-value">{{ $bot->frequency_penalty ?? '0' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Presence Penalty</span>
                    <span class="info-value">{{ $bot->presence_penalty ?? '0' }}</span>
                </div>
                @endif
            </div>

            <!-- System Prompt -->
            @if($bot->system_prompt)
            <div class="detail-card">
                <div class="section-title">
                    <i class="fas fa-terminal"></i>
                    <span>System Prompt</span>
                </div>
                <div class="px-6 pb-6">
                    <div class="code-block">{{ $bot->system_prompt }}</div>
                </div>
            </div>
            @endif

            <!-- Test Bot -->
            <div class="detail-card">
                <div class="section-title">
                    <i class="fas fa-vial"></i>
                    <span>ทดสอบ Bot</span>
                </div>
                <div class="px-6 pb-6">
                    <div class="test-panel">
                        <div id="chat-history" class="mb-4" style="max-height: 400px; overflow-y: auto;">
                            <!-- Chat messages will appear here -->
                        </div>

                        <div class="flex gap-2">
                            <textarea id="test-message" x-model="testMessage"
                                      class="flex-1 p-3 border-2 border-gray-300 dark:border-gray-600 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-100 rounded-xl resize-none focus:outline-none focus:border-purple-500"
                                      rows="3" placeholder="พิมพ์ข้อความที่ต้องการทดสอบ..."
                                      @keydown.ctrl.enter="testBot()"></textarea>
                            <button @click="testBot()" :disabled="testing || !testMessage"
                                    class="btn btn-primary" style="align-self: flex-end;">
                                <span x-show="!testing">
                                    <i class="fas fa-paper-plane"></i> ส่ง
                                </span>
                                <span x-show="testing" class="loading-spinner"></span>
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">กด Ctrl + Enter เพื่อส่งข้อความ</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Additional Info -->
        <div>
            <!-- Performance Stats -->
            <div class="detail-card">
                <div class="section-title">
                    <i class="fas fa-chart-line"></i>
                    <span>ประสิทธิภาพ</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Avg Response Time</span>
                    <span class="info-value">{{ number_format($stats['avg_response_time'] ?? 0) }} ms</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Success Rate</span>
                    <span class="info-value">{{ number_format($stats['success_rate'] ?? 0, 1) }}%</span>
                </div>
            </div>

            <!-- Quick Actions -->
            @if($bot->owner_id === Auth::id())
            <div class="detail-card">
                <div class="section-title">
                    <i class="fas fa-bolt"></i>
                    <span>การจัดการ</span>
                </div>

                <div class="px-6 pb-6 space-y-3">
                    <button @click="toggleBot()"
                            class="btn w-full"
                            :class="isActive ? 'bg-red-500 hover:bg-red-600 text-white' : 'bg-green-500 hover:bg-green-600 text-white'">
                        <i class="fas" :class="isActive ? 'fa-power-off' : 'fa-power-off'"></i>
                        <span x-text="isActive ? 'ปิดใช้งาน Bot' : 'เปิดใช้งาน Bot'"></span>
                    </button>

                    <a href="{{ route('admin.ai-bots.edit', $bot->id) }}" class="btn btn-warning w-full">
                        <i class="fas fa-edit"></i> แก้ไข Bot
                    </a>

                    <button @click="duplicateBot()" class="btn btn-secondary w-full">
                        <i class="fas fa-copy"></i> คัดลอก Bot
                    </button>

                    <button @click="deleteBot()" class="btn bg-red-500 hover:bg-red-600 text-white w-full">
                        <i class="fas fa-trash"></i> ลบ Bot
                    </button>
                </div>
            </div>
            @endif

            <!-- LINE OA Info -->
            @if($bot->line_oa_channel_id)
            <div class="detail-card">
                <div class="section-title">
                    <i class="fab fa-line"></i>
                    <span>LINE OA Integration</span>
                </div>

                <div class="px-6 pb-6">
                    <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                        <p class="text-sm text-green-800 mb-2">
                            <i class="fas fa-check-circle mr-2"></i>
                            <strong>เชื่อมต่อกับ LINE OA แล้ว</strong>
                        </p>
                        <div class="text-xs text-green-700 space-y-2">
                            <div>
                                <strong>Channel ID:</strong>
                                <code class="glass-fusion px-2 py-1 rounded">{{ $bot->line_oa_channel_id }}</code>
                            </div>
                            <div>
                                <strong>Webhook URL:</strong>
                                <code class="glass-fusion px-2 py-1 rounded block mt-1">{{ url('/webhook/line/' . $bot->id) }}</code>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function botDetail() {
    return {
        isActive: {{ $bot->is_active ? 'true' : 'false' }},
        testing: false,
        testMessage: '',

        async testBot() {
            if (!this.testMessage || this.testing) return;

            this.testing = true;

            // Add user message to chat
            this.addMessage(this.testMessage, 'user');
            const userMessage = this.testMessage;
            this.testMessage = '';

            try {
                const response = await fetch('{{ route('admin.ai-bots.test', $bot->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ message: userMessage })
                });

                const data = await response.json();

                if (data.success) {
                    this.addMessage(data.message, 'bot');
                } else {
                    this.addMessage('เกิดข้อผิดพลาด: ' + data.error, 'error');
                }
            } catch (error) {
                this.addMessage('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            }

            this.testing = false;
        },

        addMessage(text, type) {
            const chatHistory = document.getElementById('chat-history');
            const messageDiv = document.createElement('div');
            messageDiv.className = `chat-message ${type}`;
            messageDiv.textContent = text;
            chatHistory.appendChild(messageDiv);
            chatHistory.scrollTop = chatHistory.scrollHeight;
        },

        async toggleBot() {
            try {
                const response = await fetch(`/admin/ai-bots/{{ $bot->id }}/toggle`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.isActive = data.is_active;
                    alert(data.message);
                    location.reload();
                }
            } catch (error) {
                alert('เกิดข้อผิดพลาด');
            }
        },

        async duplicateBot() {
            if (!confirm('ต้องการคัดลอก Bot นี้?')) return;

            try {
                const response = await fetch(`/admin/ai-bots/{{ $bot->id }}/duplicate`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                if (response.ok) {
                    alert('คัดลอก Bot สำเร็จ');
                    window.location.href = '{{ route('admin.ai-bots.index') }}';
                }
            } catch (error) {
                alert('เกิดข้อผิดพลาด');
            }
        },

        async deleteBot() {
            if (!confirm('ต้องการลบ Bot นี้? การกระทำนี้ไม่สามารถย้อนกลับได้')) return;

            try {
                const response = await fetch(`/admin/ai-bots/{{ $bot->id }}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                if (response.ok) {
                    alert('ลบ Bot สำเร็จ');
                    window.location.href = '{{ route('admin.ai-bots.index') }}';
                } else {
                    const data = await response.json();
                    alert(data.error || 'เกิดข้อผิดพลาด');
                }
            } catch (error) {
                alert('เกิดข้อผิดพลาด');
            }
        }
    }
}
</script>
@endpush
