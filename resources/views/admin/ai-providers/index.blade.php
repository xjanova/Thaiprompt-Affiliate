@extends('layouts.admin')
@section('title', 'จัดการ AI Providers')
@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h5>จัดการ AI Providers</h5></div>
                <div class="card-body">
                    @foreach($providers as $provider)
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6>{{ $provider->display_name }} 
                                        <span class="badge badge-{{ $provider->provider_type === 'cloud' ? 'info' : 'success' }}">
                                            {{ $provider->provider_type }}
                                        </span>
                                    </h6>
                                    <p class="text-sm mb-0">{{ $provider->models->count() }} models available</p>
                                </div>
                                <div>
                                    @if($provider->name === 'deepseek-local')
                                        @if($localAiStatus && $localAiStatus['running'])
                                            <button class="btn btn-sm btn-danger" onclick="stopLocalAi()">
                                                <i class="fas fa-stop"></i> หยุด
                                            </button>
                                            <button class="btn btn-sm btn-warning" onclick="restartLocalAi()">
                                                <i class="fas fa-redo"></i> Restart
                                            </button>
                                        @else
                                            <button class="btn btn-sm btn-success" onclick="startLocalAi()">
                                                <i class="fas fa-play"></i> เริ่ม
                                            </button>
                                        @endif
                                    @endif
                                    <button class="btn btn-sm btn-{{ $provider->is_active ? 'success' : 'secondary' }}" 
                                            onclick="toggleProvider({{ $provider->id }})">
                                        {{ $provider->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
                                    </button>
                                    <button class="btn btn-sm btn-info" onclick="testConnection({{ $provider->id }})">
                                        <i class="fas fa-plug"></i> ทดสอบ
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
function toggleProvider(id) {
    $.post(`/admin/ai-providers/${id}/toggle`, {_token: '{{ csrf_token() }}'}, function(r) {
        alert(r.message);
        location.reload();
    });
}
function testConnection(id) {
    $.post(`/admin/ai-providers/${id}/test`, {_token: '{{ csrf_token() }}'}, function(r) {
        alert(r.message);
    });
}
function startLocalAi() {
    $.post('/admin/ai-providers/local/start', {_token: '{{ csrf_token() }}'}, function(r) {
        alert(r.message);
        location.reload();
    });
}
function stopLocalAi() {
    $.post('/admin/ai-providers/local/stop', {_token: '{{ csrf_token() }}'}, function(r) {
        alert(r.message);
        location.reload();
    });
}
function restartLocalAi() {
    $.post('/admin/ai-providers/local/restart', {_token: '{{ csrf_token() }}'}, function(r) {
        alert(r.message);
        location.reload();
    });
}
</script>
@endpush
