@extends('layouts.admin')

@section('title', 'Edit Bot Template')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Bot Template</h1>
        <a href="{{ route('admin.bot-automation.templates.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Templates
        </a>
    </div>

    <form action="{{ route('admin.bot-automation.templates.update', $template->id ?? 0) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Basic Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="name">Template Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name', $template->name ?? '') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="description">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="3" required>{{ old('description', $template->description ?? '') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="category">Category <span class="text-danger">*</span></label>
                                    <select class="form-control @error('category') is-invalid @enderror"
                                            id="category" name="category" required>
                                        <option value="">Select Category</option>
                                        <option value="sales" {{ old('category', $template->category ?? '') == 'sales' ? 'selected' : '' }}>Sales</option>
                                        <option value="support" {{ old('category', $template->category ?? '') == 'support' ? 'selected' : '' }}>Support</option>
                                        <option value="marketing" {{ old('category', $template->category ?? '') == 'marketing' ? 'selected' : '' }}>Marketing</option>
                                        <option value="education" {{ old('category', $template->category ?? '') == 'education' ? 'selected' : '' }}>Education</option>
                                        <option value="ecommerce" {{ old('category', $template->category ?? '') == 'ecommerce' ? 'selected' : '' }}>E-commerce</option>
                                    </select>
                                    @error('category')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="language">Language <span class="text-danger">*</span></label>
                                    <select class="form-control @error('language') is-invalid @enderror"
                                            id="language" name="language" required>
                                        <option value="en" {{ old('language', $template->language ?? '') == 'en' ? 'selected' : '' }}>English</option>
                                        <option value="th" {{ old('language', $template->language ?? '') == 'th' ? 'selected' : '' }}>Thai</option>
                                        <option value="ja" {{ old('language', $template->language ?? '') == 'ja' ? 'selected' : '' }}>Japanese</option>
                                        <option value="zh" {{ old('language', $template->language ?? '') == 'zh' ? 'selected' : '' }}>Chinese</option>
                                    </select>
                                    @error('language')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="tags">Tags (comma separated)</label>
                            <input type="text" class="form-control @error('tags') is-invalid @enderror"
                                   id="tags" name="tags" value="{{ old('tags', $template->tags ?? '') }}"
                                   placeholder="e.g., customer service, FAQ, automated">
                            @error('tags')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Template Configuration</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="welcome_message">Welcome Message</label>
                            <textarea class="form-control @error('welcome_message') is-invalid @enderror"
                                      id="welcome_message" name="welcome_message" rows="3">{{ old('welcome_message', $template->welcome_message ?? '') }}</textarea>
                            @error('welcome_message')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="fallback_message">Fallback Message</label>
                            <textarea class="form-control @error('fallback_message') is-invalid @enderror"
                                      id="fallback_message" name="fallback_message" rows="3">{{ old('fallback_message', $template->fallback_message ?? '') }}</textarea>
                            <small class="form-text text-muted">Message to show when bot doesn't understand user input</small>
                            @error('fallback_message')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Template Flows</label>
                            <div id="flowsContainer">
                                @forelse($template->flows ?? [] as $index => $flow)
                                <div class="card mb-2">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <strong>Flow {{ $index + 1 }}</strong>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.card').remove()">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <div class="form-group">
                                            <label>Trigger Keywords</label>
                                            <input type="text" class="form-control" name="flows[{{ $index }}][keywords]" value="{{ $flow->keywords ?? '' }}">
                                        </div>
                                        <div class="form-group mb-0">
                                            <label>Response</label>
                                            <textarea class="form-control" name="flows[{{ $index }}][response]" rows="2">{{ $flow->response ?? '' }}</textarea>
                                        </div>
                                    </div>
                                </div>
                                @empty
                                <div class="card mb-2">
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label>Trigger Keywords</label>
                                            <input type="text" class="form-control" name="flows[0][keywords]" placeholder="e.g., hello, hi, start">
                                        </div>
                                        <div class="form-group mb-0">
                                            <label>Response</label>
                                            <textarea class="form-control" name="flows[0][response]" rows="2"></textarea>
                                        </div>
                                    </div>
                                </div>
                                @endforelse
                            </div>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="addFlow()">
                                <i class="fas fa-plus mr-2"></i>Add Flow
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Publishing Options</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="status">Status <span class="text-danger">*</span></label>
                            <select class="form-control @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="draft" {{ old('status', $template->status ?? '') == 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="active" {{ old('status', $template->status ?? '') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="archived" {{ old('status', $template->status ?? '') == 'archived' ? 'selected' : '' }}>Archived</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="is_public" name="is_public" value="1" {{ old('is_public', $template->is_public ?? false) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_public">Make Public</label>
                            </div>
                            <small class="form-text text-muted">Allow other users to use this template</small>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="is_featured" name="is_featured" value="1" {{ old('is_featured', $template->is_featured ?? false) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_featured">Featured Template</label>
                            </div>
                        </div>

                        <hr>

                        <div class="form-group mb-0">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-save mr-2"></i>Update Template
                            </button>
                            <a href="{{ route('admin.bot-automation.templates.index') }}" class="btn btn-secondary btn-block">
                                Cancel
                            </a>
                            <button type="button" class="btn btn-danger btn-block" onclick="confirmDelete()">
                                <i class="fas fa-trash mr-2"></i>Delete Template
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Template Statistics</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <strong>Uses:</strong> {{ $template->uses_count ?? '0' }}
                            </li>
                            <li class="mb-2">
                                <strong>Rating:</strong> {{ number_format($template->rating ?? 0, 1) }}/5.0
                            </li>
                            <li class="mb-2">
                                <strong>Created:</strong> {{ isset($template->created_at) ? $template->created_at->format('M d, Y') : 'N/A' }}
                            </li>
                            <li class="mb-2">
                                <strong>Last Updated:</strong> {{ isset($template->updated_at) ? $template->updated_at->diffForHumans() : 'N/A' }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <form id="deleteForm" action="{{ route('admin.bot-automation.templates.destroy', $template->id ?? 0) }}" method="POST" class="d-none">
        @csrf
        @method('DELETE')
    </form>
</div>

<script>
let flowCount = {{ count($template->flows ?? []) }};

function addFlow() {
    const container = document.getElementById('flowsContainer');
    const flowHtml = `
        <div class="card mb-2">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <strong>Flow ${flowCount + 1}</strong>
                    <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.card').remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="form-group">
                    <label>Trigger Keywords</label>
                    <input type="text" class="form-control" name="flows[${flowCount}][keywords]" placeholder="e.g., help, support">
                </div>
                <div class="form-group mb-0">
                    <label>Response</label>
                    <textarea class="form-control" name="flows[${flowCount}][response]" rows="2"></textarea>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', flowHtml);
    flowCount++;
}

function confirmDelete() {
    if (confirm('Are you sure you want to delete this template? This action cannot be undone.')) {
        document.getElementById('deleteForm').submit();
    }
}
</script>
@endsection
