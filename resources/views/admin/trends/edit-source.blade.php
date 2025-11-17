@extends('layouts.admin-v3')

@section('title', 'Edit Trend Source')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-900 dark:text-white">Edit Trend Source</h1>
        <a href="{{ route('admin.trends.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Source Details</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.trends.sources.update', $source->id ?? 0) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="name">Source Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ $source->name ?? '' }}" required>
                </div>

                <div class="form-group">
                    <label for="url">Source URL <span class="text-danger">*</span></label>
                    <input type="url" class="form-control" id="url" name="url" value="{{ $source->url ?? '' }}" required>
                </div>

                <div class="form-group">
                    <label for="type">Source Type <span class="text-danger">*</span></label>
                    <select class="form-control" id="type" name="type" required>
                        <option value="">Select Type</option>
                        <option value="rss" {{ isset($source->type) && $source->type == 'rss' ? 'selected' : '' }}>RSS Feed</option>
                        <option value="api" {{ isset($source->type) && $source->type == 'api' ? 'selected' : '' }}>API</option>
                        <option value="scraper" {{ isset($source->type) && $source->type == 'scraper' ? 'selected' : '' }}>Web Scraper</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="active" {{ isset($source->status) && $source->status == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ isset($source->status) && $source->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-2"></i>Update Source
                </button>
                <a href="{{ route('admin.trends.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
