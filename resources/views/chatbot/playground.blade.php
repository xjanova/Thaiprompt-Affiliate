@extends('layouts.app')

@section('title', 'ทดสอบ Bot - ' . $bot->display_name)

@section('content')
<div class="container-fluid px-4 py-4" x-data="botPlayground()">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-vial text-primary me-2"></i>
                        ทดสอบ Bot
                    </h2>
                    <p class="text-muted mb-0">{{ $bot->display_name }}</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-danger" @click="clearChat()">
                        <i class="fas fa-trash me-2"></i>ล้างประวัติ
                    </button>
                    <a href="{{ route('chatbot.show', $bot->id) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>กลับ
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Chat Area -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm" style="height: 600px;">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex align-items-center">
                        <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">{{ $bot->display_name }}</h6>
                            <small class="opacity-75">{{ $bot->provider->display_name ?? '-' }} / {{ $bot->model->display_name ?? '-' }}</small>
                        </div>
                    </div>
                </div>

                <!-- Chat Messages -->
                <div class="card-body" style="overflow-y: auto; height: calc(100% - 140px);" id="chatMessages">
                    <template x-for="(message, index) in messages" :key="index">
                        <div class="mb-3" :class="message.role === 'user' ? 'text-end' : ''">
                            <div class="d-inline-block p-3 rounded-3" style="max-width: 80%;"
                                 :class="message.role === 'user' ? 'bg-primary text-white' : 'bg-light'">
                                <div x-text="message.content" style="white-space: pre-wrap;"></div>
                            </div>
                            <div class="small text-muted mt-1" x-text="message.time"></div>
                        </div>
                    </template>

                    <!-- Typing Indicator -->
                    <div class="mb-3" x-show="isTyping">
                        <div class="d-inline-block p-3 rounded-3 bg-light">
                            <div class="typing-indicator">
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div class="text-center py-5" x-show="messages.length === 0 && !isTyping">
                        <i class="fas fa-comments fa-4x text-muted mb-3"></i>
                        <h5>เริ่มทดสอบ Bot</h5>
                        <p class="text-muted">พิมพ์ข้อความเพื่อทดสอบการทำงานของ Bot</p>
                    </div>
                </div>

                <!-- Input Area -->
                <div class="card-footer bg-white border-top">
                    <div class="d-flex gap-2">
                        <textarea class="form-control" rows="2" placeholder="พิมพ์ข้อความ..."
                                  x-model="inputMessage" @keydown.ctrl.enter="sendMessage()"
                                  :disabled="isTyping"></textarea>
                        <button class="btn btn-primary" @click="sendMessage()" :disabled="!inputMessage || isTyping">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                    <small class="text-muted">กด Ctrl + Enter เพื่อส่ง</small>
                </div>
            </div>
        </div>

        <!-- Bot Info Sidebar -->
        <div class="col-lg-4">
            <!-- Bot Settings -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-sliders-h text-primary me-2"></i>
                        การตั้งค่า Bot
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-6 text-muted">Temperature</div>
                        <div class="col-6 text-end fw-semibold">{{ $bot->temperature ?? '0.7' }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6 text-muted">Max Tokens</div>
                        <div class="col-6 text-end fw-semibold">{{ number_format($bot->max_tokens ?? 2000) }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6 text-muted">Top P</div>
                        <div class="col-6 text-end fw-semibold">{{ $bot->top_p ?? '1.0' }}</div>
                    </div>
                    <div class="row">
                        <div class="col-6 text-muted">Model</div>
                        <div class="col-6 text-end fw-semibold">{{ $bot->model->display_name ?? '-' }}</div>
                    </div>
                </div>
            </div>

            <!-- System Prompt -->
            @if($bot->system_prompt)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-terminal text-primary me-2"></i>
                        System Prompt
                    </h5>
                </div>
                <div class="card-body">
                    <pre class="bg-dark text-light p-3 rounded mb-0 small" style="max-height: 200px; overflow-y: auto; white-space: pre-wrap;">{{ $bot->system_prompt }}</pre>
                </div>
            </div>
            @endif

            <!-- Quick Actions -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-bolt text-primary me-2"></i>
                        ทดสอบด่วน
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary btn-sm" @click="sendQuickMessage('สวัสดี')">
                            สวัสดี
                        </button>
                        <button class="btn btn-outline-primary btn-sm" @click="sendQuickMessage('คุณทำอะไรได้บ้าง?')">
                            คุณทำอะไรได้บ้าง?
                        </button>
                        <button class="btn btn-outline-primary btn-sm" @click="sendQuickMessage('ช่วยอธิบายเพิ่มเติม')">
                            ช่วยอธิบายเพิ่มเติม
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.typing-indicator {
    display: flex;
    gap: 4px;
}
.typing-indicator span {
    width: 8px;
    height: 8px;
    background-color: #6c757d;
    border-radius: 50%;
    animation: typing 1s infinite;
}
.typing-indicator span:nth-child(2) {
    animation-delay: 0.2s;
}
.typing-indicator span:nth-child(3) {
    animation-delay: 0.4s;
}
@keyframes typing {
    0%, 100% { opacity: 0.3; transform: scale(0.8); }
    50% { opacity: 1; transform: scale(1); }
}
</style>
@endsection

@push('scripts')
<script>
function botPlayground() {
    return {
        messages: [],
        inputMessage: '',
        isTyping: false,

        sendMessage() {
            if (!this.inputMessage.trim() || this.isTyping) return;

            const userMessage = this.inputMessage.trim();
            this.messages.push({
                role: 'user',
                content: userMessage,
                time: new Date().toLocaleTimeString('th-TH')
            });

            this.inputMessage = '';
            this.isTyping = true;
            this.scrollToBottom();

            // จำลองการตอบกลับ (ในการใช้งานจริงจะเรียก API)
            setTimeout(() => {
                this.messages.push({
                    role: 'assistant',
                    content: 'นี่คือการทดสอบ Bot สำหรับการพัฒนา API จะถูกเชื่อมต่อในภายหลัง\n\nคุณพิมพ์: "' + userMessage + '"',
                    time: new Date().toLocaleTimeString('th-TH')
                });
                this.isTyping = false;
                this.scrollToBottom();
            }, 1500);
        },

        sendQuickMessage(message) {
            this.inputMessage = message;
            this.sendMessage();
        },

        clearChat() {
            if (confirm('ต้องการล้างประวัติการสนทนา?')) {
                this.messages = [];
            }
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const chatMessages = document.getElementById('chatMessages');
                if (chatMessages) {
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }
            });
        }
    }
}
</script>
@endpush
