@extends('layouts.admin-v3')

@section('title', 'Manage Trend Sources')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Trend Sources</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.trends.index') }}">Trends</a></li>
        <li class="breadcrumb-item active">Sources</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-globe me-1"></i>
            Trend Sources
            <a href="{{ route('admin.trends.sources.create') }}" class="btn btn-sm btn-primary float-end">
                <i class="fas fa-plus"></i> Add New Source
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>URL</th>
                            <th>Method</th>
                            <th>Check Interval</th>
                            <th>Last Checked</th>
                            <th>Data Count</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sources as $source)
                        <tr>
                            <td>
                                <strong>{{ $source->name }}</strong>
                                @if($source->description)
                                <br><small class="text-muted">{{ Str::limit($source->description, 50) }}</small>
                                @endif
                            </td>
                            <td><span class="badge bg-info">{{ $source->type }}</span></td>
                            <td>
                                <a href="{{ $source->url }}" target="_blank" class="small">
                                    {{ Str::limit($source->url, 40) }}
                                </a>
                            </td>
                            <td><span class="badge bg-secondary">{{ $source->scraping_method }}</span></td>
                            <td>{{ $source->check_interval }} min</td>
                            <td>
                                @if($source->last_checked_at)
                                    {{ $source->last_checked_at->diffForHumans() }}
                                @else
                                    <span class="text-muted">Never</span>
                                @endif
                            </td>
                            <td>{{ number_format($source->trend_data_count) }}</td>
                            <td>
                                <span class="badge bg-{{ $source->priority >= 7 ? 'success' : ($source->priority >= 4 ? 'warning' : 'secondary') }}">
                                    {{ $source->priority }}
                                </span>
                            </td>
                            <td>
                                @if($source->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-info"
                                            onclick="testScrape({{ $source->id }})"
                                            title="Test Scrape">
                                        <i class="fas fa-flask"></i>
                                    </button>
                                    <a href="{{ route('admin.trends.sources.edit', $source) }}"
                                       class="btn btn-sm btn-warning"
                                       title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger"
                                            onclick="deleteSource({{ $source->id }})"
                                            title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center">
                                No sources found. <a href="{{ route('admin.trends.sources.create') }}">Add your first source</a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($sources->hasPages())
            <div class="mt-3">
                {{ $sources->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Test Results Modal -->
<div class="modal fade" id="testModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Test Scrape Results</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="testResults">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Testing...</span>
                        </div>
                        <p class="mt-2">Testing scraper...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function testScrape(sourceId) {
        const modal = new bootstrap.Modal(document.getElementById('testModal'));
        modal.show();

        fetch(`/admin/trends/sources/${sourceId}/test-scrape`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            let html = '';
            if (data.success) {
                html = `
                    <div class="alert alert-success">
                        Successfully scraped ${data.count} items
                    </div>
                    <pre class="bg-light p-3" style="max-height: 400px; overflow-y: auto;">${JSON.stringify(data.data, null, 2)}</pre>
                `;
            } else {
                html = `
                    <div class="alert alert-danger">
                        Error: ${data.error || 'Scraping failed'}
                    </div>
                `;
            }
            document.getElementById('testResults').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('testResults').innerHTML = `
                <div class="alert alert-danger">
                    Error: ${error.message}
                </div>
            `;
        });
    }

    function deleteSource(sourceId) {
        if (!confirm('Are you sure you want to delete this source?')) {
            return;
        }

        fetch(`/admin/trends/sources/${sourceId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success || data.message) {
                location.reload();
            } else {
                alert('Failed to delete source');
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
        });
    }
</script>
@endsection
