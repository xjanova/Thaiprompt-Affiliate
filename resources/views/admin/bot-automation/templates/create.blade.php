@extends('layouts.admin')

@section('title', 'Create Bot Template')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Create Bot Template</h1>
        <a href="{{ route('admin.bot-automation.templates.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Templates
        </a>
    </div>

    <form action="{{ route('admin.bot-automation.templates.store') }}" method="POST">
        @csrf
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
                                   id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="description">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="3" required>{{ old('description') }}</textarea>
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
                                        <option value="sales" {{ old('category') == 'sales' ? 'selected' : '' }}>Sales</option>
                                        <option value="support" {{ old('category') == 'support' ? 'selected' : '' }}>Support</option>
                                        <option value="marketing" {{ old('category') == 'marketing' ? 'selected' : '' }}>Marketing</option>
                                        <option value="education" {{ old('category') == 'education' ? 'selected' : '' }}>Education</option>
                                        <option value="ecommerce" {{ old('category') == 'ecommerce' ? 'selected' : '' }}>E-commerce</option>
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
                                        <option value="en" {{ old('language') == 'en' ? 'selected' : '' }}>English</option>
                                        <option value="th" {{ old('language') == 'th' ? 'selected' : '' }}>Thai</option>
                                        <option value="ja" {{ old('language') == 'ja' ? 'selected' : '' }}>Japanese</option>
                                        <option value="zh" {{ old('language') == 'zh' ? 'selected' : '' }}>Chinese</option>
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
                                   id="tags" name="tags" value="{{ old('tags') }}"
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
                                      id="welcome_message" name="welcome_message" rows="3">{{ old('welcome_message') }}</textarea>
                            @error('welcome_message')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="fallback_message">Fallback Message</label>
                            <textarea class="form-control @error('fallback_message') is-invalid @enderror"
                                      id="fallback_message" name="fallback_message" rows="3">{{ old('fallback_message') }}</textarea>
                            <small class="form-text text-muted">Message to show when bot doesn't understand user input</small>
                            @error('fallback_message')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Template Flows</label>
                            <div id="flowsContainer">
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
                                <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="is_public" name="is_public" value="1" {{ old('is_public') ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_public">Make Public</label>
                            </div>
                            <small class="form-text text-muted">Allow other users to use this template</small>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="is_featured" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_featured">Featured Template</label>
                            </div>
                        </div>

                        <hr>

                        <div class="form-group mb-0">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-save mr-2"></i>Create Template
                            </button>
                            <a href="{{ route('admin.bot-automation.templates.index') }}" class="btn btn-secondary btn-block">
                                Cancel
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Template Tips</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><i class="fas fa-check text-success mr-2"></i>Use clear, concise messages</li>
                            <li class="mb-2"><i class="fas fa-check text-success mr-2"></i>Define multiple trigger keywords</li>
                            <li class="mb-2"><i class="fas fa-check text-success mr-2"></i>Test your flows thoroughly</li>
                            <li class="mb-2"><i class="fas fa-check text-success mr-2"></i>Add fallback responses</li>
                            <li class="mb-2"><i class="fas fa-check text-success mr-2"></i>Keep templates focused</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
let flowCount = 1;

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
</script>
@endsection
