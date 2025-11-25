@extends('layouts.app')

@section('title', 'จัดการ Keywords - ' . $bot->display_name)

@section('content')
<div class="container-fluid px-4 py-4" x-data="keywordsManager()">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-key text-primary me-2"></i>
                        จัดการ Keywords
                    </h2>
                    <p class="text-muted mb-0">{{ $bot->display_name }}</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" @click="showAddModal = true">
                        <i class="fas fa-plus me-2"></i>เพิ่ม Keyword
                    </button>
                    <a href="{{ route('chatbot.show', $bot->id) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>กลับ
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- คำอธิบาย -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info mb-0">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Keywords Response</strong> - กำหนดคำตอบอัตโนมัติเมื่อผู้ใช้พิมพ์คำ/ข้อความที่ตรงกับ keyword ที่กำหนดไว้ Bot จะตอบกลับทันทีโดยไม่ต้องใช้ AI ช่วยประหยัดค่าใช้จ่าย
            </div>
        </div>
    </div>

    <!-- รายการ Keywords -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">
                <i class="fas fa-list text-primary me-2"></i>
                รายการ Keywords
            </h5>
        </div>
        <div class="card-body">
            @if($bot->keywordResponses && $bot->keywordResponses->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Keyword</th>
                                <th>คำตอบ</th>
                                <th>ประเภท</th>
                                <th>สถานะ</th>
                                <th class="text-end">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bot->keywordResponses as $keyword)
                            <tr>
                                <td>
                                    <code class="bg-light px-2 py-1 rounded">{{ $keyword->keyword }}</code>
                                </td>
                                <td>{{ Str::limit($keyword->response, 50) }}</td>
                                <td>
                                    <span class="badge bg-{{ $keyword->match_type == 'exact' ? 'primary' : 'secondary' }}">
                                        {{ $keyword->match_type == 'exact' ? 'ตรงทุกตัว' : 'มีคำนี้' }}
                                    </span>
                                </td>
                                <td>
                                    @if($keyword->is_active)
                                        <span class="badge bg-success">ใช้งาน</span>
                                    @else
                                        <span class="badge bg-secondary">ปิด</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary" @click="editKeyword({{ $keyword->id }})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" @click="deleteKeyword({{ $keyword->id }})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-key fa-4x text-muted mb-3"></i>
                    <h4>ยังไม่มี Keywords</h4>
                    <p class="text-muted">เพิ่ม Keywords เพื่อให้ Bot ตอบกลับอัตโนมัติ</p>
                    <button class="btn btn-primary" @click="showAddModal = true">
                        <i class="fas fa-plus me-2"></i>เพิ่ม Keyword แรก
                    </button>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal เพิ่ม Keyword -->
    <div class="modal fade" :class="{ 'show d-block': showAddModal }" tabindex="-1" x-show="showAddModal" @click.self="showAddModal = false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle text-primary me-2"></i>
                        เพิ่ม Keyword ใหม่
                    </h5>
                    <button type="button" class="btn-close" @click="showAddModal = false"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Keyword <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" x-model="newKeyword.keyword" placeholder="สวัสดี">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">คำตอบ <span class="text-danger">*</span></label>
                        <textarea class="form-control" rows="3" x-model="newKeyword.response" placeholder="สวัสดีครับ ยินดีให้บริการ"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">ประเภทการจับคู่</label>
                        <select class="form-select" x-model="newKeyword.match_type">
                            <option value="exact">ตรงทุกตัว (Exact Match)</option>
                            <option value="contains">มีคำนี้ (Contains)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="showAddModal = false">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" @click="addKeyword()">
                        <i class="fas fa-save me-2"></i>บันทึก
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show" x-show="showAddModal" @click="showAddModal = false"></div>
</div>
@endsection

@push('scripts')
<script>
function keywordsManager() {
    return {
        showAddModal: false,
        newKeyword: {
            keyword: '',
            response: '',
            match_type: 'contains'
        },

        addKeyword() {
            // TODO: Implement API call to add keyword
            alert('ฟีเจอร์นี้กำลังพัฒนา');
            this.showAddModal = false;
        },

        editKeyword(id) {
            // TODO: Implement edit keyword
            alert('ฟีเจอร์นี้กำลังพัฒนา');
        },

        deleteKeyword(id) {
            if (confirm('ต้องการลบ Keyword นี้?')) {
                // TODO: Implement delete keyword
                alert('ฟีเจอร์นี้กำลังพัฒนา');
            }
        }
    }
}
</script>
@endpush
