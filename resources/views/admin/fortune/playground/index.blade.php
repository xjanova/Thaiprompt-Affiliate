@extends('layouts.admin')

@section('title', 'AI Playground - ทดสอบดูดวง')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="fortunePlayground()">
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                    🎮 AI Playground - ทดสอบดูดวง
                </h1>
                <p class="text-gray-600 dark:text-gray-400">
                    ทดสอบสนทนากับ AI หมอดู เพื่อตรวจสอบคุณภาพคำทำนายก่อนใช้งานจริง
                </p>
            </div>
            <a href="{{ route('admin.fortune.settings.index') }}"
               class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition flex items-center gap-2">
                <span>⚙️</span>
                <span>กลับหน้าตั้งค่า</span>
            </a>
        </div>
    </div>

    {{-- AI Provider Selector --}}
    <div class="bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl shadow-lg p-4 mb-6 text-white">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-3">
                <span class="text-3xl">🔮</span>
                <div>
                    <div class="font-bold text-sm mb-1">เลือก AI Provider ทดสอบ</div>
                    <select x-model="selectedProviderIndex"
                            @change="onProviderChange()"
                            class="w-full min-w-[280px] px-3 py-1.5 rounded-lg bg-white/20 text-white border border-white/30 text-sm focus:ring-2 focus:ring-white/50 focus:outline-none">
                        @foreach($availableProviders as $index => $p)
                        <option value="{{ $index }}" class="text-gray-900">{{ $p['label'] }}</option>
                        @endforeach
                        @if(count($availableProviders) === 0)
                        <option value="-1" class="text-gray-900">❌ ไม่มี Provider ที่พร้อมใช้</option>
                        @endif
                    </select>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-400/30 text-green-100"
                      x-text="'✅ ' + currentProvider + ' / ' + currentModel">
                </span>
                <span class="px-3 py-1 bg-yellow-400/30 text-yellow-100 rounded-full text-xs font-semibold"
                      x-text="'📦 ' + providerSource">
                </span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Chat Area --}}
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden flex flex-col" style="height: 600px;">
                {{-- Chat Header --}}
                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">🔮</span>
                        <span class="font-semibold text-gray-900 dark:text-white">หมอดูประจำเพจ</span>
                        <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <select x-model="readingType"
                                class="text-xs px-2 py-1 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                            <option value="basic">ทำนายพื้นฐาน</option>
                            <option value="deep">ทำนายเชิงลึก</option>
                        </select>
                        <button @click="clearChat()"
                                class="text-xs px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded hover:bg-red-200 dark:hover:bg-red-900/50 transition">
                            🗑️ ล้างแชท
                        </button>
                    </div>
                </div>

                {{-- Chat Messages --}}
                <div class="flex-1 overflow-y-auto p-4 space-y-4" x-ref="chatContainer">
                    {{-- Welcome Message --}}
                    <div x-show="messages.length === 0" class="text-center py-12 text-gray-400 dark:text-gray-500">
                        <span class="text-6xl block mb-4">🔮</span>
                        <p class="text-lg font-medium mb-2">ลองสนทนากับ AI หมอดู</p>
                        <p class="text-sm">พิมพ์ข้อความด้านล่าง หรือกดปุ่มตัวอย่างทางขวา</p>
                    </div>

                    {{-- Messages --}}
                    <template x-for="(msg, index) in messages" :key="index">
                        <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                            <div :class="msg.role === 'user'
                                ? 'bg-blue-500 text-white rounded-2xl rounded-br-md px-4 py-3 max-w-[80%]'
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-2xl rounded-bl-md px-4 py-3 max-w-[80%]'">
                                <div x-show="msg.role === 'assistant'" class="flex items-center gap-1.5 mb-1">
                                    <span class="text-sm">🔮</span>
                                    <span class="text-xs font-semibold text-purple-600 dark:text-purple-400">หมอดูประจำเพจ</span>
                                </div>
                                <div class="text-sm whitespace-pre-wrap" x-text="msg.content"></div>
                                <div x-show="msg.debug" class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-600">
                                    <div class="text-xs text-gray-400 dark:text-gray-500 space-y-0.5">
                                        <div x-show="msg.debug?.provider" x-text="'Provider: ' + msg.debug?.provider"></div>
                                        <div x-show="msg.debug?.model" x-text="'Model: ' + msg.debug?.model"></div>
                                        <div x-show="msg.debug?.tokens_used" x-text="'Tokens: ' + msg.debug?.tokens_used"></div>
                                        <div x-show="msg.debug?.response_time_ms" x-text="'เวลาตอบ: ' + msg.debug?.response_time_ms + 'ms'"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Typing Indicator --}}
                    <div x-show="loading" class="flex justify-start">
                        <div class="bg-gray-100 dark:bg-gray-700 rounded-2xl rounded-bl-md px-4 py-3">
                            <div class="flex items-center gap-1.5 mb-1">
                                <span class="text-sm">🔮</span>
                                <span class="text-xs font-semibold text-purple-600 dark:text-purple-400">หมอดูประจำเพจ</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Error Message --}}
                    <div x-show="error" x-transition class="flex justify-center">
                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl px-4 py-3 max-w-[90%]">
                            <div class="flex items-start gap-2">
                                <span class="text-lg">❌</span>
                                <div>
                                    <p class="text-sm font-semibold text-red-800 dark:text-red-200">เกิดข้อผิดพลาด</p>
                                    <p class="text-xs text-red-600 dark:text-red-400 mt-1" x-text="error"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Chat Input --}}
                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
                    <div class="flex items-end gap-3">
                        <textarea x-model="inputMessage"
                                  @keydown.enter.prevent="if (!$event.shiftKey) sendMessage()"
                                  :disabled="loading"
                                  rows="2"
                                  class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 resize-none text-sm"
                                  placeholder="พิมพ์ข้อความ... (Enter เพื่อส่ง, Shift+Enter เพื่อขึ้นบรรทัดใหม่)"></textarea>
                        <button @click="sendMessage()"
                                :disabled="loading || !inputMessage.trim()"
                                class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 disabled:bg-gray-400 text-white rounded-xl transition flex items-center gap-2 font-medium">
                            <span x-show="!loading">ส่ง</span>
                            <span x-show="loading" class="animate-spin">⏳</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar - Example Questions --}}
        <div class="lg:col-span-1 space-y-4">
            {{-- ตัวอย่างคำถาม --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3">💬 ตัวอย่างคำถาม</h3>

                <div class="space-y-2">
                    <button @click="sendQuick('สวัสดีค่ะ')"
                            class="w-full text-left px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-purple-50 dark:hover:bg-purple-900/20 transition text-xs text-gray-700 dark:text-gray-300">
                        👋 สวัสดีค่ะ
                    </button>

                    <div class="pt-1">
                        <p class="text-xs font-semibold text-purple-600 dark:text-purple-400 mb-1.5">💕 ความรัก</p>
                        <button @click="sendQuick('ปีนี้จะมีคู่ครองไหมคะ อยากรู้ว่าดวงความรักเป็นอย่างไร')"
                                class="w-full text-left px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-purple-50 dark:hover:bg-purple-900/20 transition text-xs text-gray-700 dark:text-gray-300 mb-1">
                            ปีนี้จะมีคู่ครองไหมคะ
                        </button>
                        <button @click="sendQuick('แฟนรักจริงหรือเปล่าคะ รู้สึกไม่มั่นใจ')"
                                class="w-full text-left px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-purple-50 dark:hover:bg-purple-900/20 transition text-xs text-gray-700 dark:text-gray-300">
                            แฟนรักจริงหรือเปล่า
                        </button>
                    </div>

                    <div class="pt-1">
                        <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 mb-1.5">💼 การงาน</p>
                        <button @click="sendQuick('ควรเปลี่ยนงานดีไหมคะ ทำงานที่นี่มา 3 ปีแล้ว')"
                                class="w-full text-left px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition text-xs text-gray-700 dark:text-gray-300 mb-1">
                            ควรเปลี่ยนงานดีไหม
                        </button>
                        <button @click="sendQuick('ปีนี้จะได้เลื่อนตำแหน่งไหมคะ')"
                                class="w-full text-left px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition text-xs text-gray-700 dark:text-gray-300">
                            จะได้เลื่อนตำแหน่งไหม
                        </button>
                    </div>

                    <div class="pt-1">
                        <p class="text-xs font-semibold text-green-600 dark:text-green-400 mb-1.5">💰 การเงิน</p>
                        <button @click="sendQuick('ดวงการเงินปีนี้เป็นอย่างไรคะ ลงทุนได้ไหม')"
                                class="w-full text-left px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-green-50 dark:hover:bg-green-900/20 transition text-xs text-gray-700 dark:text-gray-300 mb-1">
                            ดวงการเงินปีนี้เป็นอย่างไร
                        </button>
                    </div>

                    <div class="pt-1">
                        <p class="text-xs font-semibold text-orange-600 dark:text-orange-400 mb-1.5">🔮 ทั่วไป</p>
                        <button @click="sendQuick('ดูดวงภาพรวมปีนี้ให้หน่อยค่ะ เกิดวันที่ 15 มีนาคม 2533')"
                                class="w-full text-left px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-orange-50 dark:hover:bg-orange-900/20 transition text-xs text-gray-700 dark:text-gray-300 mb-1">
                            ดูดวงพร้อมวันเกิด (มีนาคม 2533)
                        </button>
                        <button @click="sendQuick('เช็คสิทธิ์')"
                                class="w-full text-left px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-orange-50 dark:hover:bg-orange-900/20 transition text-xs text-gray-700 dark:text-gray-300 mb-1">
                            เช็คสิทธิ์
                        </button>
                        <button @click="sendQuick('คุณเป็น AI หรือเปล่า')"
                                class="w-full text-left px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-orange-50 dark:hover:bg-orange-900/20 transition text-xs text-gray-700 dark:text-gray-300">
                            ทดสอบถามว่าเป็น AI
                        </button>
                    </div>
                </div>
            </div>

            {{-- สถิติ Playground --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3">📊 สถิติเซสชั่นนี้</h3>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between text-gray-600 dark:text-gray-400">
                        <span>ข้อความทั้งหมด</span>
                        <span class="font-medium text-gray-900 dark:text-white" x-text="messages.length"></span>
                    </div>
                    <div class="flex justify-between text-gray-600 dark:text-gray-400">
                        <span>Tokens ใช้ไป</span>
                        <span class="font-medium text-gray-900 dark:text-white" x-text="totalTokens.toLocaleString()"></span>
                    </div>
                    <div class="flex justify-between text-gray-600 dark:text-gray-400">
                        <span>เวลาตอบเฉลี่ย</span>
                        <span class="font-medium text-gray-900 dark:text-white" x-text="avgResponseTime + 'ms'"></span>
                    </div>
                </div>
            </div>

            {{-- คำแนะนำ --}}
            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
                <h3 class="text-sm font-bold text-amber-800 dark:text-amber-200 mb-2">💡 คำแนะนำ</h3>
                <ul class="text-xs text-amber-700 dark:text-amber-300 space-y-1.5 list-disc list-inside">
                    <li>ทดสอบทั้งคำถามดูดวงและคำถามนอกเรื่อง</li>
                    <li>ลองถามว่าเป็น AI เพื่อดูว่า bot ตอบถูกต้อง</li>
                    <li>ทดสอบส่งวันเดือนปีเกิดเพื่อดูการวิเคราะห์ราศี</li>
                    <li>สลับระหว่าง "พื้นฐาน" กับ "เชิงลึก" เพื่อเทียบคุณภาพ</li>
                    <li>ข้อความนี้ส่งตรงไป AI จริง ใช้ tokens จริง</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function fortunePlayground() {
    const providers = @json($availableProviders);

    return {
        messages: [],
        inputMessage: '',
        loading: false,
        error: null,
        readingType: 'basic',
        totalTokens: 0,
        totalResponseTime: 0,
        responseCount: 0,

        // Provider selection
        providers: providers,
        selectedProviderIndex: 0,
        currentProvider: providers.length ? providers[0].provider : '',
        currentModel: providers.length ? providers[0].model : '',
        providerSource: providers.length ? providers[0].source : '',
        poolKeyId: providers.length ? (providers[0].pool_key_id || null) : null,

        onProviderChange() {
            const idx = parseInt(this.selectedProviderIndex);
            if (idx >= 0 && idx < this.providers.length) {
                const p = this.providers[idx];
                this.currentProvider = p.provider;
                this.currentModel = p.model;
                this.providerSource = p.source;
                this.poolKeyId = p.pool_key_id || null;
            }
        },

        get avgResponseTime() {
            if (this.responseCount === 0) return 0;
            return Math.round(this.totalResponseTime / this.responseCount);
        },

        async sendMessage() {
            const text = this.inputMessage.trim();
            if (!text || this.loading) return;

            this.inputMessage = '';
            this.error = null;

            // เพิ่มข้อความผู้ใช้
            this.messages.push({ role: 'user', content: text });
            this.scrollToBottom();

            this.loading = true;

            try {
                const payload = {
                    messages: this.messages.map(m => ({
                        role: m.role,
                        content: m.content
                    })),
                    reading_type: this.readingType,
                    provider: this.currentProvider,
                    model: this.currentModel,
                };
                if (this.poolKeyId) {
                    payload.pool_key_id = this.poolKeyId;
                }

                const response = await fetch('{{ route("admin.fortune.playground.chat") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (data.success) {
                    this.messages.push({
                        role: 'assistant',
                        content: data.response,
                        debug: data.debug || {}
                    });

                    // อัพเดทสถิติ
                    if (data.debug) {
                        this.totalTokens += data.debug.tokens_used || 0;
                        this.totalResponseTime += data.debug.response_time_ms || 0;
                        this.responseCount++;
                    }
                } else {
                    this.error = data.error || 'ไม่ทราบสาเหตุ';
                }
            } catch (err) {
                this.error = 'เชื่อมต่อไม่ได้: ' + err.message;
            } finally {
                this.loading = false;
                this.scrollToBottom();
            }
        },

        sendQuick(text) {
            this.inputMessage = text;
            this.sendMessage();
        },

        clearChat() {
            this.messages = [];
            this.error = null;
            this.totalTokens = 0;
            this.totalResponseTime = 0;
            this.responseCount = 0;
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const container = this.$refs.chatContainer;
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            });
        }
    };
}
</script>
@endpush
@endsection
