@extends('layouts.admin')
@section('title', $bot->display_name)
@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5>{{ $bot->display_name }}</h5>
                    <div>
                        @if($bot->owner_id === Auth::id())
                        <a href="{{ route('admin.knowledge-bases.index', $bot->id) }}" class="btn btn-sm btn-info me-2">
                            <i class="fas fa-book"></i> Knowledge Base
                        </a>
                        <a href="{{ route('admin.ai-bots.edit', $bot->id) }}" class="btn btn-sm btn-warning">
                            <i class="fas fa-edit"></i> แก้ไข
                        </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <p><strong>Provider:</strong> {{ $bot->provider->display_name }}</p>
                    <p><strong>Model:</strong> {{ $bot->model->display_name }}</p>
                    <p><strong>Description:</strong> {{ $bot->description }}</p>
                    <p><strong>System Prompt:</strong></p>
                    <pre>{{ $bot->system_prompt }}</pre>
                </div>
            </div>
            <div class="card mt-3">
                <div class="card-header"><h6>ทดสอบ Bot</h6></div>
                <div class="card-body">
                    <textarea id="test-message" class="form-control mb-2" placeholder="พิมพ์ข้อความ..."></textarea>
                    <button class="btn btn-primary" onclick="testBot()">ส่ง</button>
                    <div id="test-result" class="mt-3"></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h6>สถิติ</h6></div>
                <div class="card-body">
                    <p><strong>Conversations:</strong> {{ $stats['total_conversations'] }}</p>
                    <p><strong>Messages:</strong> {{ $stats['total_messages'] }}</p>
                    <p><strong>Tokens:</strong> {{ number_format($stats['total_tokens']) }}</p>
                    <p><strong>Cost:</strong> ${{ number_format($stats['total_cost'], 4) }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
function testBot() {
    const message = $('#test-message').val();
    if(!message) return;
    $('#test-result').html('<div class="spinner-border"></div>');
    $.post('{{ route('admin.ai-bots.test', $bot->id) }}', {
        _token: '{{ csrf_token() }}',
        message: message
    }, function(r) {
        $('#test-result').html('<div class="alert alert-success"><strong>Response:</strong><br>' + r.message + '</div>');
    }).fail(function(xhr) {
        $('#test-result').html('<div class="alert alert-danger">Error: ' + xhr.responseJSON.error + '</div>');
    });
}
</script>
@endpush
