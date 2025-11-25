@extends('layouts.app')

@section('title', 'สร้างบอทใหม่')

@section('content')
<div class="container-fluid px-4 py-4" x-data="botCreator()">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-plus-circle text-primary me-2"></i>
                        สร้างบอทใหม่
                    </h2>
                    <p class="text-muted mb-0">กำหนดค่าและปรับแต่ง AI Bot ของคุณ</p>
                </div>
                <div>
                    <a href="{{ route('chatbot.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>กลับ
                    </a>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('chatbot.store') }}">
        @csrf

        <!-- ข้อมูลพื้นฐาน -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle text-primary me-2"></i>
                    ข้อมูลพื้นฐาน
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">ชื่อ Bot (Internal) <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               required placeholder="my-chatbot" value="{{ old('name') }}">
                        <div class="form-text">ชื่อที่ใช้ภายในระบบ (ภาษาอังกฤษ, ไม่มีช่องว่าง)</div>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">ชื่อแสดง <span class="text-danger">*</span></label>
                        <input type="text" name="display_name" class="form-control @error('display_name') is-invalid @enderror"
                               required placeholder="แชทบอทของฉัน" value="{{ old('display_name') }}">
                        <div class="form-text">ชื่อที่แสดงให้ผู้ใช้เห็น</div>
                        @error('display_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">คำอธิบาย</label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                              rows="3" placeholder="บอทสำหรับช่วยตอบคำถาม...">{{ old('description') }}</textarea>
                    <div class="form-text">อธิบายหน้าที่และความสามารถของ Bot</div>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <!-- การตั้งค่า AI -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="fas fa-brain text-primary me-2"></i>
                    การตั้งค่า AI
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">AI Provider <span class="text-danger">*</span></label>
                        <select name="provider_id" class="form-select @error('provider_id') is-invalid @enderror"
                                required id="provider_select" @change="loadModels($event.target.value)">
                            <option value="">-- เลือก Provider --</option>
                            @foreach($providers as $provider)
                            <option value="{{ $provider->id }}" {{ old('provider_id') == $provider->id ? 'selected' : '' }}>
                                {{ $provider->display_name }}
                            </option>
                            @endforeach
                        </select>
                        @error('provider_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">AI Model <span class="text-danger">*</span></label>
                        <select name="model_id" class="form-select @error('model_id') is-invalid @enderror"
                                required id="model_select" x-ref="modelSelect">
                            <option value="">-- เลือก Model --</option>
                        </select>
                        @error('model_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">System Prompt</label>
                    <textarea name="system_prompt" class="form-control @error('system_prompt') is-invalid @enderror"
                              rows="5" placeholder="You are a helpful assistant that...">{{ old('system_prompt', 'You are a helpful AI assistant.') }}</textarea>
                    <div class="form-text">คำสั่งเริ่มต้นที่กำหนดบุคลิกและพฤติกรรมของ Bot</div>
                    @error('system_prompt')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <!-- พารามิเตอร์ขั้นสูง -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="fas fa-sliders-h text-primary me-2"></i>
                    พารามิเตอร์ขั้นสูง
                </h5>
            </div>
            <div class="card-body">
                <!-- Temperature -->
                <div class="mb-4">
                    <label class="form-label fw-semibold">Temperature (ความสร้างสรรค์)</label>
                    <div class="d-flex align-items-center gap-3">
                        <input type="range" name="temperature" class="form-range flex-grow-1"
                               min="0" max="2" step="0.1" x-model="temperature">
                        <span class="badge bg-primary" x-text="temperature" style="min-width: 50px;"></span>
                    </div>
                    <div class="form-text">ค่าต่ำ = คำตอบที่แม่นยำและสม่ำเสมอ | ค่าสูง = คำตอบที่หลากหลายและสร้างสรรค์</div>
                </div>

                <!-- Max Tokens -->
                <div class="mb-4">
                    <label class="form-label fw-semibold">Max Tokens</label>
                    <div class="d-flex align-items-center gap-3">
                        <input type="range" name="max_tokens" class="form-range flex-grow-1"
                               min="100" max="8000" step="100" x-model="maxTokens">
                        <span class="badge bg-primary" x-text="maxTokens" style="min-width: 60px;"></span>
                    </div>
                    <div class="form-text">จำนวน token สูงสุดที่ Bot สามารถตอบกลับได้</div>
                </div>

                <!-- Top P -->
                <div class="mb-4">
                    <label class="form-label fw-semibold">Top P (Nucleus Sampling)</label>
                    <div class="d-flex align-items-center gap-3">
                        <input type="range" name="top_p" class="form-range flex-grow-1"
                               min="0" max="1" step="0.05" x-model="topP">
                        <span class="badge bg-primary" x-text="topP" style="min-width: 50px;"></span>
                    </div>
                    <div class="form-text">ควบคุมความหลากหลายของคำตอบ (แนะนำ: 1.0)</div>
                </div>

                <!-- ตัวเลือกเพิ่มเติม -->
                <div>
                    <button type="button" class="btn btn-link text-decoration-none p-0" @click="showAdvanced = !showAdvanced">
                        <i class="fas" :class="showAdvanced ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                        <span x-text="showAdvanced ? 'ซ่อนตัวเลือกเพิ่มเติม' : 'แสดงตัวเลือกเพิ่มเติม'"></span>
                    </button>

                    <div x-show="showAdvanced" x-transition class="row mt-3">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Frequency Penalty</label>
                            <input type="number" name="frequency_penalty" class="form-control"
                                   step="0.1" min="-2" max="2" value="{{ old('frequency_penalty', '0') }}">
                            <div class="form-text">ลดการซ้ำคำ (-2 ถึง 2)</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Presence Penalty</label>
                            <input type="number" name="presence_penalty" class="form-control"
                                   step="0.1" min="-2" max="2" value="{{ old('presence_penalty', '0') }}">
                            <div class="form-text">เพิ่มความหลากหลายของหัวข้อ (-2 ถึง 2)</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ฟีเจอร์และการตั้งค่า -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="fas fa-cog text-primary me-2"></i>
                    ฟีเจอร์และการตั้งค่า
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="form-check p-3 bg-light rounded">
                            <input type="checkbox" name="is_public" class="form-check-input" id="is_public"
                                   {{ old('is_public') ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="is_public">
                                เปิดเป็น Public Bot
                            </label>
                            <div class="form-text mb-0">อนุญาตให้ผู้ใช้อื่นเห็นและใช้งาน Bot นี้</div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="form-check p-3 bg-light rounded">
                            <input type="checkbox" name="enable_knowledge_base" class="form-check-input" id="enable_knowledge_base"
                                   {{ old('enable_knowledge_base') ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="enable_knowledge_base">
                                เปิดใช้ Knowledge Base
                            </label>
                            <div class="form-text mb-0">เชื่อมต่อกับฐานความรู้เพื่อให้คำตอบที่แม่นยำขึ้น</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- เชื่อมต่อ LINE Official Account -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="fab fa-line text-success me-2"></i>
                    เชื่อมต่อ LINE Official Account
                    <span class="badge bg-info ms-2">ตัวเลือก</span>
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">LINE Channel ID</label>
                        <input type="text" name="line_oa_channel_id" class="form-control"
                               placeholder="1234567890" value="{{ old('line_oa_channel_id') }}">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">LINE Channel Secret</label>
                        <input type="text" name="line_oa_channel_secret" class="form-control"
                               placeholder="xxxxxxxxxxxxx" value="{{ old('line_oa_channel_secret') }}">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">LINE Channel Access Token</label>
                    <input type="text" name="line_oa_access_token" class="form-control"
                           placeholder="Long-lived Channel Access Token" value="{{ old('line_oa_access_token') }}">
                    <div class="form-text">ใช้สำหรับส่งข้อความตอบกลับผ่าน LINE OA</div>
                </div>
            </div>
        </div>

        <!-- ปุ่มดำเนินการ -->
        <div class="d-flex gap-3">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-2"></i>สร้าง Bot
            </button>
            <a href="{{ route('chatbot.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-times me-2"></i>ยกเลิก
            </a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function botCreator() {
    return {
        temperature: {{ old('temperature', '0.7') }},
        maxTokens: {{ old('max_tokens', '2000') }},
        topP: {{ old('top_p', '1') }},
        showAdvanced: false,

        async loadModels(providerId) {
            const modelSelect = this.$refs.modelSelect;

            if (!providerId) {
                modelSelect.innerHTML = '<option value="">-- เลือก Model --</option>';
                return;
            }

            // แสดงสถานะกำลังโหลด
            modelSelect.innerHTML = '<option value="">กำลังโหลด...</option>';
            modelSelect.disabled = true;

            try {
                const response = await fetch(`/api/v1/ai-bots/providers/${providerId}/models`);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                if (!data.success || !data.data) {
                    throw new Error('Invalid response format');
                }

                let options = '<option value="">-- เลือก Model --</option>';

                if (data.data.length === 0) {
                    options = '<option value="">-- ไม่มีโมเดลสำหรับ Provider นี้ --</option>';
                } else {
                    data.data.forEach(model => {
                        const contextInfo = model.context_window ? ` (${model.context_window.toLocaleString()} tokens)` : '';
                        options += `<option value="${model.id}">${model.display_name}${contextInfo}</option>`;
                    });
                }

                modelSelect.innerHTML = options;
                modelSelect.disabled = false;

                // เลือก model เดิมถ้ามี
                const oldModelId = '{{ old('model_id') }}';
                if (oldModelId) {
                    modelSelect.value = oldModelId;
                }

            } catch (error) {
                console.error('Error loading models:', error);
                modelSelect.innerHTML = '<option value="">-- เกิดข้อผิดพลาด กรุณาลองใหม่ --</option>';
                modelSelect.disabled = false;
            }
        },

        init() {
            // โหลด models ถ้ามี provider ที่เลือกไว้แล้ว
            const providerSelect = document.getElementById('provider_select');
            if (providerSelect && providerSelect.value) {
                this.loadModels(providerSelect.value);
            }
        }
    }
}
</script>
@endpush
