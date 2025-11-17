@extends('layouts.admin-v3')

@section('title', $knowledgeBase->name)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">{{ $knowledgeBase->type_icon }} {{ $knowledgeBase->name }}</h4>
            <p class="text-gray-600 dark:text-gray-400 mb-0">
                Bot: <strong>{{ $bot->name }}</strong> |
                Status: <span class="badge bg-{{ $knowledgeBase->status_color }}">{{ $knowledgeBase->status_label }}</span>
            </p>
        </div>
        <div>
            <a href="{{ route('admin.knowledge-bases.index', $bot->id) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> กลับ
            </a>
            <form action="{{ route('admin.knowledge-bases.destroy', [$bot->id, $knowledgeBase->id]) }}"
                  method="POST"
                  class="d-inline"
                  onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบ Knowledge Base นี้?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> ลบ
                </button>
            </form>
        </div>
    </div>

    <div class="row">
        <!-- Left Column: Details -->
        <div class="col-lg-8">
            <!-- Details Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header glass-fusion" border border-white/20 dark:border-white/10>
                    <h5 class="mb-0">รายละเอียด</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">ประเภท</label>
                            <div>{{ $knowledgeBase->type_icon }} {{ ucfirst($knowledgeBase->type) }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">สถานะ</label>
                            <div>
                                <span class="badge bg-{{ $knowledgeBase->status_color }}">{{ $knowledgeBase->status_label }}</span>
                                @if($knowledgeBase->processed_at)
                                    <br><small class="text-muted">{{ $knowledgeBase->processed_at->diffForHumans() }}</small>
                                @endif
                            </div>
                        </div>
                        @if($knowledgeBase->description)
                        <div class="col-12 mb-3">
                            <label class="text-muted small">คำอธิบาย</label>
                            <div>{{ $knowledgeBase->description }}</div>
                        </div>
                        @endif
                        @if($knowledgeBase->source_url)
                        <div class="col-12 mb-3">
                            <label class="text-muted small">URL แหล่งที่มา</label>
                            <div>
                                <a href="{{ $knowledgeBase->source_url }}" target="_blank" rel="noopener">
                                    {{ $knowledgeBase->source_url }} <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>
                        </div>
                        @endif
                        @if($knowledgeBase->file_name)
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">ชื่อไฟล์</label>
                            <div>{{ $knowledgeBase->file_name }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">ขนาดไฟล์</label>
                            <div>{{ $knowledgeBase->file_size_human }}</div>
                        </div>
                        @endif
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">จำนวน Chunks</label>
                            <div><strong>{{ number_format($knowledgeBase->total_chunks) }}</strong></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">Total Tokens</label>
                            <div><strong>{{ number_format($knowledgeBase->total_tokens) }}</strong></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">สร้างเมื่อ</label>
                            <div>{{ $knowledgeBase->created_at->format('d M Y H:i') }}</div>
                        </div>
                        @if($knowledgeBase->status === 'failed')
                        <div class="col-12">
                            <div class="alert alert-danger mb-0">
                                <strong>Error:</strong> {{ $knowledgeBase->error_message }}
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Test Search Card -->
            @if($knowledgeBase->status === 'completed')
            <div class="card shadow-sm mb-4">
                <div class="card-header glass-fusion" border border-white/20 dark:border-white/10>
                    <h5 class="mb-0">🔍 ทดสอบการค้นหา</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <input type="text"
                               id="searchQuery"
                               class="form-control"
                               placeholder="พิมพ์คำถามเพื่อทดสอบการค้นหา..."
                               onkeypress="if(event.key === 'Enter') testSearch()">
                    </div>
                    <button onclick="testSearch()" class="btn btn-primary" id="searchBtn">
                        <i class="fas fa-search"></i> ค้นหา
                    </button>

                    <div id="searchResults" class="mt-3" style="display: none;">
                        <h6>ผลลัพธ์:</h6>
                        <div id="searchResultsContent"></div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Chunks List -->
            <div class="card shadow-sm">
                <div class="card-header glass-fusion d-flex justify-content-between align-items-center" border border-white/20 dark:border-white/10>
                    <h5 class="mb-0">Chunks ({{ $knowledgeBase->total_chunks }})</h5>
                    <span class="badge bg-info">{{ $chunks->total() }} total</span>
                </div>
                <div class="card-body">
                    @if($chunks->isEmpty())
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>ยังไม่มี chunks</p>
                        </div>
                    @else
                        <div class="list-group">
                            @foreach($chunks as $chunk)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <span class="badge bg-secondary">Chunk #{{ $chunk->chunk_index + 1 }}</span>
                                        <span class="badge bg-info">{{ $chunk->token_count }} tokens</span>
                                        @if($chunk->embedding_dimension)
                                        <span class="badge bg-success">
                                            <i class="fas fa-check"></i> Embedded ({{ $chunk->embedding_dimension }}D)
                                        </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-gray-700 dark:text-gray-300">
                                    {{ Str::limit($chunk->content, 300) }}
                                </div>
                                @if(strlen($chunk->content) > 300)
                                <button class="btn btn-sm btn-link p-0 mt-2"
                                        onclick="toggleFullContent({{ $chunk->id }})">
                                    <span id="toggle-{{ $chunk->id }}">แสดงทั้งหมด</span>
                                </button>
                                <div id="full-{{ $chunk->id }}" style="display: none;" class="mt-2">
                                    {{ $chunk->content }}
                                </div>
                                @endif
                            </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        <div class="mt-3">
                            {{ $chunks->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Column: Statistics -->
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4 bg-gradient-primary text-white">
                <div class="card-body">
                    <h6 class="text-white mb-3">สถิติ</h6>
                    <div class="mb-3">
                        <div class="small opacity-75">Total Chunks</div>
                        <h3 class="mb-0">{{ number_format($knowledgeBase->total_chunks) }}</h3>
                    </div>
                    <div class="mb-3">
                        <div class="small opacity-75">Total Tokens</div>
                        <h3 class="mb-0">{{ number_format($knowledgeBase->total_tokens) }}</h3>
                    </div>
                    @if($knowledgeBase->file_size)
                    <div>
                        <div class="small opacity-75">File Size</div>
                        <h4 class="mb-0">{{ $knowledgeBase->file_size_human }}</h4>
                    </div>
                    @endif
                </div>
            </div>

            @if($knowledgeBase->status === 'processing')
            <div class="card shadow-sm border-warning">
                <div class="card-body text-center">
                    <div class="spinner-border text-warning mb-3" role="status">
                        <span class="visually-hidden">กำลังประมวลผล...</span>
                    </div>
                    <h6>กำลังประมวลผล</h6>
                    <p class="text-muted small mb-0">
                        ระบบกำลังสร้าง chunks และ embeddings<br>
                        โปรดรอสักครู่...
                    </p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function testSearch() {
    const query = document.getElementById('searchQuery').value;
    if (!query || query.length < 3) {
        alert('กรุณาพิมพ์คำค้นหาอย่างน้อย 3 ตัวอักษร');
        return;
    }

    const btn = document.getElementById('searchBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังค้นหา...';

    fetch('{{ route("admin.knowledge-bases.test-search", $bot->id) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ query: query, top_k: 5 })
    })
    .then(response => response.json())
    .then(data => {
        displaySearchResults(data);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-search"></i> ค้นหา';
    })
    .catch(error => {
        alert('เกิดข้อผิดพลาด: ' + error.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-search"></i> ค้นหา';
    });
}

function displaySearchResults(data) {
    const container = document.getElementById('searchResults');
    const content = document.getElementById('searchResultsContent');

    if (!data.success || data.chunks.length === 0) {
        content.innerHTML = '<div class="alert alert-warning">ไม่พบผลลัพธ์ที่เกี่ยวข้อง</div>';
    } else {
        let html = `
            <div class="alert alert-info">
                <strong>พบ ${data.chunks.length} chunks</strong> |
                Avg Similarity: ${(data.avg_similarity * 100).toFixed(1)}% |
                Search Time: ${data.search_time_ms.toFixed(0)}ms
            </div>
        `;

        data.chunks.forEach((chunk, index) => {
            html += `
                <div class="card mb-2">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="badge bg-secondary">Chunk #${chunk.chunk_index + 1}</span>
                            <span class="badge bg-success">${(chunk.similarity_score * 100).toFixed(1)}% match</span>
                        </div>
                        <p class="mb-0">${chunk.content}</p>
                    </div>
                </div>
            `;
        });

        content.innerHTML = html;
    }

    container.style.display = 'block';
}

function toggleFullContent(chunkId) {
    const fullDiv = document.getElementById('full-' + chunkId);
    const toggle = document.getElementById('toggle-' + chunkId);

    if (fullDiv.style.display === 'none') {
        fullDiv.style.display = 'block';
        toggle.textContent = 'ซ่อน';
    } else {
        fullDiv.style.display = 'none';
        toggle.textContent = 'แสดงทั้งหมด';
    }
}
</script>
@endpush
@endsection
