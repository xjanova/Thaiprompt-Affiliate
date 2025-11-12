@extends('layouts.admin')

@section('title', 'Template Details')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ $template->name ?? 'Template Details' }}</h1>
        <div>
            <a href="{{ route('admin.bot-automation.templates.edit', $template->id ?? 0) }}" class="btn btn-primary">
                <i class="fas fa-edit mr-2"></i>Edit Template
            </a>
            <a href="{{ route('admin.bot-automation.templates.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i>Back to Templates
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Template Information</h6>
                    <div>
                        <span class="badge badge-{{ isset($template->status) && $template->status == 'active' ? 'success' : 'secondary' }}">
                            {{ isset($template->status) ? ucfirst($template->status) : 'N/A' }}
                        </span>
                        <span class="badge badge-info">{{ isset($template->category) ? ucfirst($template->category) : 'N/A' }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">Template Name</h6>
                            <p class="font-weight-bold">{{ $template->name ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Language</h6>
                            <p class="font-weight-bold">{{ isset($template->language) ? strtoupper($template->language) : 'N/A' }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="text-muted">Description</h6>
                            <p>{{ $template->description ?? 'No description available' }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="text-muted">Tags</h6>
                            @if(isset($template->tags) && $template->tags)
                                @foreach(explode(',', $template->tags) as $tag)
                                    <span class="badge badge-secondary">{{ trim($tag) }}</span>
                                @endforeach
                            @else
                                <p class="text-muted">No tags</p>
                            @endif
                        </div>
                    </div>

                    <hr>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">Welcome Message</h6>
                            <p>{{ $template->welcome_message ?? 'Not set' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Fallback Message</h6>
                            <p>{{ $template->fallback_message ?? 'Not set' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Template Flows</h6>
                </div>
                <div class="card-body">
                    @forelse($template->flows ?? [] as $index => $flow)
                    <div class="card mb-3 border-left-primary">
                        <div class="card-body">
                            <h6 class="font-weight-bold">Flow {{ $index + 1 }}</h6>
                            <div class="mb-2">
                                <strong>Trigger Keywords:</strong>
                                <p class="text-muted mb-0">{{ $flow->keywords ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <strong>Response:</strong>
                                <p class="text-muted mb-0">{{ $flow->response ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>No flows configured for this template</p>
                    </div>
                    @endforelse
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Usage History</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>User</th>
                                    <th>Bot</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($usageHistory ?? [] as $usage)
                                <tr>
                                    <td>{{ isset($usage->created_at) ? $usage->created_at->format('M d, Y') : 'N/A' }}</td>
                                    <td>{{ $usage->user_name ?? 'N/A' }}</td>
                                    <td>{{ $usage->bot_name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge badge-{{ $usage->status == 'active' ? 'success' : 'secondary' }}">
                                            {{ ucfirst($usage->status ?? 'N/A') }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No usage history available</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="{{ route('admin.bot-automation.templates.edit', $template->id ?? 0) }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-edit mr-2"></i>Edit Template
                        </a>
                        <a href="#" class="list-group-item list-group-item-action" onclick="duplicateTemplate()">
                            <i class="fas fa-copy mr-2"></i>Duplicate Template
                        </a>
                        <a href="#" class="list-group-item list-group-item-action" onclick="exportTemplate()">
                            <i class="fas fa-download mr-2"></i>Export Template
                        </a>
                        <a href="#" class="list-group-item list-group-item-action" onclick="testTemplate()">
                            <i class="fas fa-vial mr-2"></i>Test Template
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="list-group-item list-group-item-action text-danger" onclick="deleteTemplate()">
                            <i class="fas fa-trash mr-2"></i>Delete Template
                        </a>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Statistics</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span><strong>Total Uses:</strong></span>
                                <span class="badge badge-primary">{{ $template->uses_count ?? '0' }}</span>
                            </div>
                        </li>
                        <li class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span><strong>Rating:</strong></span>
                                <span>
                                    {{ number_format($template->rating ?? 0, 1) }}/5.0
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="fas fa-star {{ $i <= ($template->rating ?? 0) ? 'text-warning' : 'text-muted' }}"></i>
                                    @endfor
                                </span>
                            </div>
                        </li>
                        <li class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span><strong>Total Flows:</strong></span>
                                <span class="badge badge-info">{{ count($template->flows ?? []) }}</span>
                            </div>
                        </li>
                        <li class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span><strong>Public:</strong></span>
                                <span>{{ isset($template->is_public) && $template->is_public ? 'Yes' : 'No' }}</span>
                            </div>
                        </li>
                        <li class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span><strong>Featured:</strong></span>
                                <span>{{ isset($template->is_featured) && $template->is_featured ? 'Yes' : 'No' }}</span>
                            </div>
                        </li>
                        <li class="mb-3">
                            <div>
                                <strong>Created:</strong><br>
                                <small class="text-muted">{{ isset($template->created_at) ? $template->created_at->format('M d, Y h:i A') : 'N/A' }}</small>
                            </div>
                        </li>
                        <li>
                            <div>
                                <strong>Last Updated:</strong><br>
                                <small class="text-muted">{{ isset($template->updated_at) ? $template->updated_at->diffForHumans() : 'N/A' }}</small>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Preview</h6>
                </div>
                <div class="card-body">
                    <div class="chat-preview bg-light p-3 rounded">
                        <div class="mb-2">
                            <div class="chat-message bot-message bg-primary text-white p-2 rounded">
                                {{ $template->welcome_message ?? 'Welcome message...' }}
                            </div>
                        </div>
                        <div class="text-center text-muted small">
                            <em>Preview mode</em>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function duplicateTemplate() {
    if (confirm('Create a copy of this template?')) {
        console.log('Duplicating template');
    }
}

function exportTemplate() {
    console.log('Exporting template');
}

function testTemplate() {
    console.log('Testing template');
}

function deleteTemplate() {
    if (confirm('Are you sure you want to delete this template? This action cannot be undone.')) {
        console.log('Deleting template');
    }
}
</script>
@endsection
