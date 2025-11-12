@extends('layouts.admin')

@section('title', 'Bot Templates')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Bot Templates</h1>
        <a href="{{ route('admin.bot-automation.templates.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-2"></i>Create Template
        </a>
    </div>

    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Templates</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalTemplates ?? '0' }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-code fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Templates</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $activeTemplates ?? '0' }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Uses</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalUses ?? '0' }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-download fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Categories</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalCategories ?? '0' }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tags fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Template Library</h6>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" placeholder="Search templates..." id="searchTemplates">
                </div>
                <div class="col-md-3">
                    <select class="form-control" id="filterCategory">
                        <option value="">All Categories</option>
                        <option value="sales">Sales</option>
                        <option value="support">Support</option>
                        <option value="marketing">Marketing</option>
                        <option value="education">Education</option>
                        <option value="ecommerce">E-commerce</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-control" id="filterStatus">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="draft">Draft</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-control" id="sortBy">
                        <option value="newest">Newest First</option>
                        <option value="popular">Most Popular</option>
                        <option value="name">Name (A-Z)</option>
                    </select>
                </div>
            </div>

            <div class="row">
                @forelse($templates ?? [] as $template)
                <div class="col-md-4 mb-4">
                    <div class="card h-100 border-left-{{ $template->status == 'active' ? 'success' : 'secondary' }}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-1">{{ $template->name ?? 'N/A' }}</h5>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light" type="button" data-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a class="dropdown-item" href="{{ route('admin.bot-automation.templates.show', $template->id ?? 0) }}">
                                            <i class="fas fa-eye mr-2"></i>View
                                        </a>
                                        <a class="dropdown-item" href="{{ route('admin.bot-automation.templates.edit', $template->id ?? 0) }}">
                                            <i class="fas fa-edit mr-2"></i>Edit
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="#" onclick="duplicateTemplate({{ $template->id }})">
                                            <i class="fas fa-copy mr-2"></i>Duplicate
                                        </a>
                                        <a class="dropdown-item" href="#" onclick="exportTemplate({{ $template->id }})">
                                            <i class="fas fa-download mr-2"></i>Export
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item text-danger" href="#" onclick="deleteTemplate({{ $template->id }})">
                                            <i class="fas fa-trash mr-2"></i>Delete
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-2">
                                <span class="badge badge-{{ $template->status == 'active' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($template->status ?? 'N/A') }}
                                </span>
                                <span class="badge badge-info">{{ ucfirst($template->category ?? 'N/A') }}</span>
                            </div>

                            <p class="card-text text-muted small">{{ Str::limit($template->description ?? '', 100) }}</p>

                            <div class="mt-3">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <small class="text-muted d-block">Uses</small>
                                        <strong>{{ $template->uses_count ?? '0' }}</strong>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted d-block">Rating</small>
                                        <strong>{{ number_format($template->rating ?? 0, 1) }}</strong>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted d-block">Flows</small>
                                        <strong>{{ $template->flows_count ?? '0' }}</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 pt-3 border-top">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">Updated {{ isset($template->updated_at) ? $template->updated_at->diffForHumans() : 'N/A' }}</small>
                                    <a href="{{ route('admin.bot-automation.templates.show', $template->id ?? 0) }}" class="btn btn-sm btn-primary">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12 text-center text-muted py-5">
                    <i class="fas fa-file-code fa-3x mb-3"></i>
                    <p>No templates available. Create your first template to get started!</p>
                    <a href="{{ route('admin.bot-automation.templates.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus mr-2"></i>Create Template
                    </a>
                </div>
                @endforelse
            </div>

            @if(isset($templates) && method_exists($templates, 'links'))
            <div class="mt-3">
                {{ $templates->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<script>
function duplicateTemplate(id) {
    if (confirm('Duplicate this template?')) {
        console.log('Duplicating template:', id);
    }
}

function exportTemplate(id) {
    console.log('Exporting template:', id);
}

function deleteTemplate(id) {
    if (confirm('Are you sure you want to delete this template? This action cannot be undone.')) {
        console.log('Deleting template:', id);
    }
}
</script>
@endsection
