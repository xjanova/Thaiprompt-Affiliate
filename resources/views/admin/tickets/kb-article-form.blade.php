@extends('layouts.admin')

@section('title', isset($article) ? 'Edit Article' : 'Create Article')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ isset($article) ? 'Edit Article' : 'Create Article' }}</h1>
        <a href="{{ route('admin.tickets.kb-articles.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to List
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ isset($article) ? route('admin.tickets.kb-articles.update', $article->id) : route('admin.tickets.kb-articles.store') }}" method="POST">
                @csrf
                @if(isset($article))
                    @method('PUT')
                @endif

                <div class="form-group">
                    <label for="title">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror"
                           value="{{ old('title', $article->title ?? '') }}" required>
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="content">Content <span class="text-danger">*</span></label>
                    <textarea name="content" id="content" class="form-control @error('content') is-invalid @enderror"
                              rows="15" required>{{ old('content', $article->content ?? '') }}</textarea>
                    @error('content')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">
                        Supports Markdown formatting. Use **bold**, *italic*, [links](url), etc.
                    </small>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="category_id">Category</label>
                            <select name="category_id" id="category_id" class="form-control @error('category_id') is-invalid @enderror">
                                <option value="">Select Category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}"
                                            {{ old('category_id', $article->category_id ?? '') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tags">Tags (comma-separated)</label>
                            <input type="text" name="tags" id="tags" class="form-control"
                                   value="{{ old('tags', isset($article) && $article->tags ? implode(', ', $article->tags) : '') }}"
                                   placeholder="e.g., password, login, account">
                            <small class="form-text text-muted">Separate tags with commas</small>
                        </div>
                    </div>
                </div>

                <div class="form-check mb-3">
                    <input type="checkbox" name="is_public" id="is_public" class="form-check-input" value="1"
                           {{ old('is_public', $article->is_public ?? true) ? 'checked' : '' }}>
                    <label for="is_public" class="form-check-label">
                        Public (visible to all users)
                    </label>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-2"></i>{{ isset($article) ? 'Update Article' : 'Create Article' }}
                    </button>
                    <a href="{{ route('admin.tickets.kb-articles.index') }}" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Convert comma-separated tags to array before submit
document.querySelector('form').addEventListener('submit', function(e) {
    const tagsInput = document.getElementById('tags');
    if (tagsInput.value) {
        const tagsArray = tagsInput.value.split(',').map(tag => tag.trim()).filter(tag => tag);
        // Create hidden inputs for each tag
        tagsArray.forEach((tag, index) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `tags[${index}]`;
            input.value = tag;
            this.appendChild(input);
        });
        tagsInput.removeAttribute('name'); // Don't submit the original input
    }
});
</script>
@endpush
