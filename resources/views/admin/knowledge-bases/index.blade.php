@extends('layouts.admin')

@section('title', 'Knowledge Base - ' . $bot->name)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">📚 Knowledge Base</h4>
            <p class="text-gray-600 mb-0">จัดการฐานความรู้สำหรับ: <strong>{{ $bot->name }}</strong></p>
        </div>
        <div>
            <a href="{{ route('admin.ai-bots.show', $bot->id) }}" class="btn btn-secondary me-2">
                <i class="fas fa-arrow-left"></i> กลับ
            </a>
            <a href="{{ route('admin.knowledge-bases.create', $bot->id) }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> เพิ่ม Knowledge Base
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-gradient-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white mb-1">Knowledge Bases</h6>
                            <h3 class="mb-0">{{ $stats['knowledge_bases'] }}</h3>
                        </div>
                        <div class="text-white" style="font-size: 2rem;">📚</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-gradient-secondary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white mb-1">Total Chunks</h6>
                            <h3 class="mb-0">{{ number_format($stats['total_chunks']) }}</h3>
                        </div>
                        <div class="text-white" style="font-size: 2rem;">📄</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white mb-1">Total Usage</h6>
                            <h3 class="mb-0">{{ number_format($stats['total_usage']) }}</h3>
                        </div>
                        <div class="text-white" style="font-size: 2rem;">🔍</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white mb-1">Avg Similarity</h6>
                            <h3 class="mb-0">{{ number_format($stats['avg_similarity'], 2) }}</h3>
                        </div>
                        <div class="text-white" style="font-size: 2rem;">⚡</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Knowledge Bases List -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">รายการ Knowledge Bases</h5>
        </div>
        <div class="card-body">
            @if($knowledgeBases->isEmpty())
                <div class="text-center py-5">
                    <div style="font-size: 4rem;" class="mb-3">📚</div>
                    <h5 class="text-gray-600">ยังไม่มี Knowledge Base</h5>
                    <p class="text-gray-500 mb-4">เริ่มต้นโดยการเพิ่มเอกสาร, PDF, หรือ URL</p>
                    <a href="{{ route('admin.knowledge-bases.create', $bot->id) }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> เพิ่ม Knowledge Base แรก
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Chunks</th>
                                <th>Tokens</th>
                                <th>Size</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($knowledgeBases as $kb)
                            <tr>
                                <td>
                                    <span style="font-size: 1.5rem;">{{ $kb->type_icon }}</span>
                                </td>
                                <td>
                                    <strong>{{ $kb->name }}</strong>
                                    @if($kb->description)
                                        <br><small class="text-muted">{{ Str::limit($kb->description, 50) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $kb->status_color }}">
                                        {{ $kb->status_label }}
                                    </span>
                                    @if($kb->status === 'failed')
                                        <br><small class="text-danger">{{ Str::limit($kb->error_message, 30) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ number_format($kb->total_chunks) }} chunks</span>
                                </td>
                                <td>
                                    {{ number_format($kb->total_tokens) }} tokens
                                </td>
                                <td>
                                    @if($kb->file_size)
                                        {{ $kb->file_size_human }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <small>{{ $kb->created_at->diffForHumans() }}</small>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.knowledge-bases.show', [$bot->id, $kb->id]) }}"
                                           class="btn btn-sm btn-info" title="ดูรายละเอียด">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form action="{{ route('admin.knowledge-bases.destroy', [$bot->id, $kb->id]) }}"
                                              method="POST"
                                              onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบ Knowledge Base นี้?')"
                                              class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="ลบ">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-3">
                    {{ $knowledgeBases->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
