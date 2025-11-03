@extends('layouts.admin')
@section('title', 'สร้าง AI Bot ใหม่')

@push('styles')
<style>
    .form-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        overflow: hidden;
    }
    .form-section {
        padding: 24px;
        border-bottom: 2px solid #f3f4f6;
    }
    .form-section:last-child {
        border-bottom: none;
    }
    .form-section-title {
        font-size: 18px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .form-section-title i {
        color: #667eea;
    }
    .form-label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }
    .form-input, .form-select, .form-textarea {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        font-size: 14px;
        transition: all 0.3s ease;
    }
    .form-input:focus, .form-select:focus, .form-textarea:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    .form-help {
        font-size: 12px;
        color: #6b7280;
        margin-top: 6px;
    }
    .slider-container {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .slider {
        flex: 1;
        height: 8px;
        border-radius: 4px;
        background: #e5e7eb;
        outline: none;
        -webkit-appearance: none;
    }
    .slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.4);
    }
    .slider::-moz-range-thumb {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.4);
        border: none;
    }
    .slider-value {
        min-width: 60px;
        text-align: center;
        font-weight: 600;
        color: #667eea;
        font-size: 14px;
    }
    .checkbox-container {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px;
        background: #f9fafb;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .checkbox-container:hover {
        background: #f3f4f6;
    }
    .checkbox-input {
        width: 20px;
        height: 20px;
        cursor: pointer;
    }
    .checkbox-label {
        font-size: 14px;
        font-weight: 500;
        color: #374151;
        cursor: pointer;
        margin: 0;
    }
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 14px 32px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 16px;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
    }
    .btn-secondary {
        background: #e5e7eb;
        color: #374151;
        padding: 14px 32px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 16px;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .btn-secondary:hover {
        background: #d1d5db;
    }
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 32px;
        border-radius: 16px;
        margin-bottom: 24px;
    }
    .badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    .badge-info {
        background: #dbeafe;
        color: #1e40af;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4" x-data="botCreator()">

    <!-- Page Header -->
    <div class="page-header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold mb-2">
                    <i class="fas fa-robot mr-3"></i>สร้าง AI Bot ใหม่
                </h2>
                <p class="opacity-90">กำหนดค่าและปรับแต่ง AI Bot ของคุณ</p>
            </div>
            <a href="{{ route('admin.ai-bots.index') }}" class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-4 py-2 rounded-lg transition-all">
                <i class="fas fa-arrow-left mr-2"></i>กลับ
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.ai-bots.store') }}">
        @csrf

        <!-- Basic Information -->
        <div class="form-card mb-6">
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-info-circle"></i>
                    <span>ข้อมูลพื้นฐาน</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="form-label">ชื่อ Bot (Internal) <span class="text-red-500">*</span></label>
                        <input type="text" name="name" class="form-input" required
                               placeholder="my-chatbot" value="{{ old('name') }}">
                        <p class="form-help">ชื่อที่ใช้ภายในระบบ (ภาษาอังกฤษ, ไม่มีช่องว่าง)</p>
                    </div>

                    <div>
                        <label class="form-label">ชื่อแสดง <span class="text-red-500">*</span></label>
                        <input type="text" name="display_name" class="form-input" required
                               placeholder="แชทบอทของฉัน" value="{{ old('display_name') }}">
                        <p class="form-help">ชื่อที่แสดงให้ผู้ใช้เห็น</p>
                    </div>
                </div>

                <div class="mt-6">
                    <label class="form-label">คำอธิบาย</label>
                    <textarea name="description" class="form-textarea" rows="3"
                              placeholder="บอทสำหรับช่วยตอบคำถาม...">{{ old('description') }}</textarea>
                    <p class="form-help">อธิบายหน้าที่และความสามารถของ Bot</p>
                </div>
            </div>
        </div>

        <!-- AI Configuration -->
        <div class="form-card mb-6">
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-brain"></i>
                    <span>การตั้งค่า AI</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="form-label">AI Provider <span class="text-red-500">*</span></label>
                        <select name="provider_id" class="form-select" required @change="loadModels($event.target.value)">
                            <option value="">-- เลือก Provider --</option>
                            @foreach($providers as $provider)
                            <option value="{{ $provider->id }}" {{ old('provider_id') == $provider->id ? 'selected' : '' }}>
                                {{ $provider->display_name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="form-label">AI Model <span class="text-red-500">*</span></label>
                        <select name="model_id" class="form-select" required id="model_select">
                            <option value="">-- เลือก Model --</option>
                        </select>
                    </div>
                </div>

                <div class="mt-6">
                    <label class="form-label">System Prompt</label>
                    <textarea name="system_prompt" class="form-textarea" rows="6"
                              placeholder="You are a helpful assistant that...">{{ old('system_prompt', 'You are a helpful AI assistant.') }}</textarea>
                    <p class="form-help">คำสั่งเริ่มต้นที่กำหนดบุคลิกและพฤติกรรมของ Bot</p>
                </div>
            </div>
        </div>

        <!-- Advanced Parameters -->
        <div class="form-card mb-6">
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-sliders-h"></i>
                    <span>พารามิเตอร์ขั้นสูง</span>
                </div>

                <!-- Temperature -->
                <div class="mb-6">
                    <label class="form-label">Temperature (ความสร้างสรรค์)</label>
                    <div class="slider-container">
                        <input type="range" name="temperature" class="slider"
                               min="0" max="2" step="0.1" value="{{ old('temperature', '0.7') }}"
                               @input="temperature = $event.target.value">
                        <span class="slider-value" x-text="temperature"></span>
                    </div>
                    <p class="form-help">ค่าต่ำ = คำตอบที่แม่นยำและสม่ำเสมอ | ค่าสูง = คำตอบที่หลากหลายและสร้างสรรค์</p>
                </div>

                <!-- Max Tokens -->
                <div class="mb-6">
                    <label class="form-label">Max Tokens</label>
                    <div class="slider-container">
                        <input type="range" name="max_tokens" class="slider"
                               min="100" max="8000" step="100" value="{{ old('max_tokens', '2000') }}"
                               @input="maxTokens = $event.target.value">
                        <span class="slider-value" x-text="maxTokens"></span>
                    </div>
                    <p class="form-help">จำนวน token สูงสุดที่ Bot สามารถตอบกลับได้</p>
                </div>

                <!-- Top P -->
                <div class="mb-6">
                    <label class="form-label">Top P (Nucleus Sampling)</label>
                    <div class="slider-container">
                        <input type="range" name="top_p" class="slider"
                               min="0" max="1" step="0.05" value="{{ old('top_p', '1') }}"
                               @input="topP = $event.target.value">
                        <span class="slider-value" x-text="topP"></span>
                    </div>
                    <p class="form-help">ควบคุมความหลากหลายของคำตอบ (แนะนำ: 1.0)</p>
                </div>

                <!-- Advanced Options (Collapsible) -->
                <div class="mt-6">
                    <button type="button" @click="showAdvanced = !showAdvanced"
                            class="text-purple-600 hover:text-purple-700 font-semibold text-sm">
                        <i class="fas" :class="showAdvanced ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                        <span x-text="showAdvanced ? 'ซ่อนตัวเลือกเพิ่มเติม' : 'แสดงตัวเลือกเพิ่มเติม'"></span>
                    </button>

                    <div x-show="showAdvanced" x-transition class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                        <div>
                            <label class="form-label">Frequency Penalty</label>
                            <input type="number" name="frequency_penalty" class="form-input"
                                   step="0.1" min="-2" max="2" value="{{ old('frequency_penalty', '0') }}">
                            <p class="form-help">ลดการซ้ำคำ (-2 ถึง 2)</p>
                        </div>

                        <div>
                            <label class="form-label">Presence Penalty</label>
                            <input type="number" name="presence_penalty" class="form-input"
                                   step="0.1" min="-2" max="2" value="{{ old('presence_penalty', '0') }}">
                            <p class="form-help">เพิ่มความหลากหลายของหัวข้อ (-2 ถึง 2)</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bot Features -->
        <div class="form-card mb-6">
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-cog"></i>
                    <span>ฟีเจอร์และการตั้งค่า</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="checkbox-container">
                        <input type="checkbox" name="is_public" class="checkbox-input" {{ old('is_public') ? 'checked' : '' }}>
                        <div>
                            <div class="checkbox-label">เปิดเป็น Public Bot</div>
                            <p class="form-help mb-0">อนุญาตให้ผู้ใช้อื่นเห็นและใช้งาน Bot นี้</p>
                        </div>
                    </label>

                    <label class="checkbox-container">
                        <input type="checkbox" name="enable_knowledge_base" class="checkbox-input" {{ old('enable_knowledge_base') ? 'checked' : '' }}>
                        <div>
                            <div class="checkbox-label">เปิดใช้ Knowledge Base</div>
                            <p class="form-help mb-0">เชื่อมต่อกับฐานความรู้เพื่อให้คำตอบที่แม่นยำขึ้น</p>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <!-- LINE OA Integration (Optional) -->
        <div class="form-card mb-6">
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fab fa-line"></i>
                    <span>เชื่อมต่อ LINE Official Account (ตัวเลือก)</span>
                    <span class="badge badge-info ml-2">Optional</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="form-label">LINE Channel ID</label>
                        <input type="text" name="line_oa_channel_id" class="form-input"
                               placeholder="1234567890" value="{{ old('line_oa_channel_id') }}">
                    </div>

                    <div>
                        <label class="form-label">LINE Channel Secret</label>
                        <input type="text" name="line_oa_channel_secret" class="form-input"
                               placeholder="xxxxxxxxxxxxx" value="{{ old('line_oa_channel_secret') }}">
                    </div>
                </div>

                <div class="mt-6">
                    <label class="form-label">LINE Channel Access Token</label>
                    <input type="text" name="line_oa_access_token" class="form-input"
                           placeholder="Long-lived Channel Access Token" value="{{ old('line_oa_access_token') }}">
                    <p class="form-help">ใช้สำหรับส่งข้อความตอบกลับผ่าน LINE OA</p>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-4">
            <button type="submit" class="btn-primary">
                <i class="fas fa-save mr-2"></i>สร้าง Bot
            </button>
            <a href="{{ route('admin.ai-bots.index') }}" class="btn-secondary">
                <i class="fas fa-times mr-2"></i>ยกเลิก
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
            if (!providerId) {
                $('#model_select').html('<option value="">-- เลือก Model --</option>');
                return;
            }

            try {
                const response = await fetch(`/admin/ai-bots/providers/${providerId}/models`);
                const data = await response.json();

                let options = '<option value="">-- เลือก Model --</option>';
                data.data.forEach(model => {
                    options += `<option value="${model.id}">${model.display_name}</option>`;
                });

                $('#model_select').html(options);
            } catch (error) {
                console.error('Error loading models:', error);
            }
        }
    }
}

// Load models if provider is pre-selected (e.g., from validation errors)
document.addEventListener('DOMContentLoaded', function() {
    const providerSelect = document.querySelector('select[name="provider_id"]');
    if (providerSelect && providerSelect.value) {
        Alpine.store('botCreator').loadModels(providerSelect.value);

        // Wait a bit and then set the selected model
        setTimeout(() => {
            const oldModelId = '{{ old('model_id') }}';
            if (oldModelId) {
                document.getElementById('model_select').value = oldModelId;
            }
        }, 500);
    }
});
</script>
@endpush
