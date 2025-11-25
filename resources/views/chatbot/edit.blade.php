@extends('layouts.app')

@section('title', 'แก้ไข ' . $bot->display_name)

@section('content')
<div class="container-fluid px-4 py-4" x-data="botEditor()">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-edit text-primary me-2"></i>
                        แก้ไข Bot
                    </h2>
                    <p class="text-muted mb-0">{{ $bot->display_name }}</p>
                </div>
                <div>
                    <a href="{{ route('chatbot.show', $bot->id) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>กลับ
                    </a>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('chatbot.update', $bot->id) }}">
        @csrf
        @method('PUT')

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
                               required placeholder="my-chatbot" value="{{ old('name', $bot->name) }}">
                        <div class="form-text">ชื่อที่ใช้ภายในระบบ (ภาษาอังกฤษ, ไม่มีช่องว่าง)</div>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">ชื่อแสดง <span class="text-danger">*</span></label>
                        <input type="text" name="display_name" class="form-control @error('display_name') is-invalid @enderror"
                               required placeholder="แชทบอทของฉัน" value="{{ old('display_name', $bot->display_name) }}">
                        <div class="form-text">ชื่อที่แสดงให้ผู้ใช้เห็น</div>
                        @error('display_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">คำอธิบาย</label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                              rows="3" placeholder="บอทสำหรับช่วยตอบคำถาม...">{{ old('description', $bot->description) }}</textarea>
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
                            <option value="{{ $provider->id }}" {{ old('provider_id', $bot->provider_id) == $provider->id ? 'selected' : '' }}>
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
                              rows="5" placeholder="You are a helpful assistant that...">{{ old('system_prompt', $bot->system_prompt) }}</textarea>
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
                </div>

                <!-- Max Tokens -->
                <div class="mb-4">
                    <label class="form-label fw-semibold">Max Tokens</label>
                    <div class="d-flex align-items-center gap-3">
                        <input type="range" name="max_tokens" class="form-range flex-grow-1"
                               min="100" max="8000" step="100" x-model="maxTokens">
                        <span class="badge bg-primary" x-text="maxTokens" style="min-width: 60px;"></span>
                    </div>
                </div>

                <!-- Top P -->
                <div class="mb-4">
                    <label class="form-label fw-semibold">Top P (Nucleus Sampling)</label>
                    <div class="d-flex align-items-center gap-3">
                        <input type="range" name="top_p" class="form-range flex-grow-1"
                               min="0" max="1" step="0.05" x-model="topP">
                        <span class="badge bg-primary" x-text="topP" style="min-width: 50px;"></span>
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
                            <input type="checkbox" name="is_active" class="form-check-input" id="is_active"
                                   {{ old('is_active', $bot->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="is_active">
                                เปิดใช้งาน Bot
                            </label>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="form-check p-3 bg-light rounded">
                            <input type="checkbox" name="is_public" class="form-check-input" id="is_public"
                                   {{ old('is_public', $bot->is_public) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="is_public">
                                เปิดเป็น Public Bot
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ปุ่มดำเนินการ -->
        <div class="d-flex gap-3">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-2"></i>บันทึกการเปลี่ยนแปลง
            </button>
            <a href="{{ route('chatbot.show', $bot->id) }}" class="btn btn-outline-secondary">
                <i class="fas fa-times me-2"></i>ยกเลิก
            </a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function botEditor() {
    return {
        temperature: {{ old('temperature', $bot->temperature ?? 0.7) }},
        maxTokens: {{ old('max_tokens', $bot->max_tokens ?? 2000) }},
        topP: {{ old('top_p', $bot->top_p ?? 1) }},
        currentModelId: '{{ old('model_id', $bot->model_id) }}',

        async loadModels(providerId) {
            const modelSelect = this.$refs.modelSelect;

            if (!providerId) {
                modelSelect.innerHTML = '<option value="">-- เลือก Model --</option>';
                return;
            }

            modelSelect.innerHTML = '<option value="">กำลังโหลด...</option>';
            modelSelect.disabled = true;

            try {
                const response = await fetch(`/api/v1/ai-bots/providers/${providerId}/models`);
                const data = await response.json();

                let options = '<option value="">-- เลือก Model --</option>';

                if (data.success && data.data.length > 0) {
                    data.data.forEach(model => {
                        const selected = model.id == this.currentModelId ? 'selected' : '';
                        options += `<option value="${model.id}" ${selected}>${model.display_name}</option>`;
                    });
                }

                modelSelect.innerHTML = options;
                modelSelect.disabled = false;

            } catch (error) {
                console.error('Error loading models:', error);
                modelSelect.innerHTML = '<option value="">-- เกิดข้อผิดพลาด --</option>';
                modelSelect.disabled = false;
            }
        },

        init() {
            const providerSelect = document.getElementById('provider_select');
            if (providerSelect && providerSelect.value) {
                this.loadModels(providerSelect.value);
            }
        }
    }
}
</script>
@endpush
