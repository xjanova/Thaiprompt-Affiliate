@extends('layouts.admin')
@section('title', 'จัดการ AI Bots')
@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5>AI Bots</h5>
                    <a href="{{ route('admin.ai-bots.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> สร้าง Bot ใหม่
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ชื่อ</th>
                                    <th>Provider</th>
                                    <th>Model</th>
                                    <th>สถานะ</th>
                                    <th>สร้างเมื่อ</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bots as $bot)
                                <tr>
                                    <td>
                                        <strong>{{ $bot->display_name }}</strong><br>
                                        <small class="text-muted">{{ $bot->name }}</small>
                                    </td>
                                    <td>{{ $bot->provider->display_name }}</td>
                                    <td>{{ $bot->model->display_name }}</td>
                                    <td>
                                        <span class="badge badge-{{ $bot->is_active ? 'success' : 'secondary' }}">
                                            {{ $bot->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
                                        </span>
                                    </td>
                                    <td>{{ $bot->created_at->diffForHumans() }}</td>
                                    <td>
                                        <a href="{{ route('admin.ai-bots.show', $bot->id) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($bot->owner_id === Auth::id())
                                        <a href="{{ route('admin.ai-bots.edit', $bot->id) }}" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn btn-sm btn-danger" onclick="deleteBot({{ $bot->id }})">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{ $bots->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
function deleteBot(id) {
    if(!confirm('ต้องการลบ Bot นี้?')) return;
    $.ajax({
        url: `/admin/ai-bots/${id}`,
        type: 'DELETE',
        data: {_token: '{{ csrf_token() }}'},
        success: () => location.reload()
    });
}
</script>
@endpush
