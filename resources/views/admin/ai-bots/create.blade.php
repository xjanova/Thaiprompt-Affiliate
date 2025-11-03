@extends('layouts.admin')
@section('title', 'สร้าง AI Bot ใหม่')
@section('content')
<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header"><h5>สร้าง AI Bot ใหม่</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.ai-bots.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>ชื่อ Bot (Internal)</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>ชื่อแสดง</label>
                        <input type="text" name="display_name" class="form-control" required>
                    </div>
                    <div class="col-12 mb-3">
                        <label>คำอธิบาย</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Provider</label>
                        <select name="provider_id" class="form-control" required onchange="loadModels(this.value)">
                            <option value="">เลือก Provider</option>
                            @foreach($providers as $provider)
                            <option value="{{ $provider->id }}">{{ $provider->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Model</label>
                        <select name="model_id" class="form-control" required id="model_select">
                            <option value="">เลือก Model</option>
                        </select>
                    </div>
                    <div class="col-12 mb-3">
                        <label>System Prompt</label>
                        <textarea name="system_prompt" class="form-control" rows="4" placeholder="You are a helpful assistant..."></textarea>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Temperature (0-2)</label>
                        <input type="number" name="temperature" class="form-control" step="0.1" min="0" max="2" value="0.7">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Max Tokens</label>
                        <input type="number" name="max_tokens" class="form-control" value="2000">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Top P</label>
                        <input type="number" name="top_p" class="form-control" step="0.1" min="0" max="1" value="1">
                    </div>
                    <div class="col-12 mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_public" class="form-check-input" id="is_public">
                            <label class="form-check-label" for="is_public">เปิดเป็น Public</label>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">สร้าง Bot</button>
                <a href="{{ route('admin.ai-bots.index') }}" class="btn btn-secondary">ยกเลิก</a>
            </form>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
function loadModels(providerId) {
    if(!providerId) return;
    $.get(`/admin/ai-bots/providers/${providerId}/models`, function(r) {
        $('#model_select').html('<option value="">เลือก Model</option>');
        r.data.forEach(m => {
            $('#model_select').append(`<option value="${m.id}">${m.display_name}</option>`);
        });
    });
}
</script>
@endpush
