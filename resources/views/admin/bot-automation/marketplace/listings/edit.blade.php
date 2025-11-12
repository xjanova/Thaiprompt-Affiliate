@extends('layouts.admin')

@section('title', 'Edit Marketplace Listing')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Marketplace Listing</h1>
        <a href="{{ route('admin.bot-automation.marketplace.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Marketplace
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Listing Information</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.bot-automation.marketplace.listings.update', $listing->id ?? 0) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label for="title">Listing Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror"
                                   id="title" name="title" value="{{ old('title', $listing->title ?? '') }}" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="description">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="4" required>{{ old('description', $listing->description ?? '') }}</textarea>
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
                                        <option value="sales" {{ old('category', $listing->category ?? '') == 'sales' ? 'selected' : '' }}>Sales</option>
                                        <option value="support" {{ old('category', $listing->category ?? '') == 'support' ? 'selected' : '' }}>Support</option>
                                        <option value="marketing" {{ old('category', $listing->category ?? '') == 'marketing' ? 'selected' : '' }}>Marketing</option>
                                        <option value="automation" {{ old('category', $listing->category ?? '') == 'automation' ? 'selected' : '' }}>Automation</option>
                                    </select>
                                    @error('category')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="price">Price (USD) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('price') is-invalid @enderror"
                                           id="price" name="price" value="{{ old('price', $listing->price ?? '') }}" step="0.01" min="0" required>
                                    @error('price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="features">Features (one per line)</label>
                            <textarea class="form-control @error('features') is-invalid @enderror"
                                      id="features" name="features" rows="5"
                                      placeholder="Feature 1&#10;Feature 2&#10;Feature 3">{{ old('features', $listing->features ?? '') }}</textarea>
                            @error('features')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="bot_id">Associated Bot</label>
                            <select class="form-control @error('bot_id') is-invalid @enderror" id="bot_id" name="bot_id">
                                <option value="">Select Bot (Optional)</option>
                                @foreach($bots ?? [] as $bot)
                                <option value="{{ $bot->id }}" {{ old('bot_id', $listing->bot_id ?? '') == $bot->id ? 'selected' : '' }}>
                                    {{ $bot->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('bot_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="image">Listing Image</label>
                            @if(isset($listing->image) && $listing->image)
                            <div class="mb-2">
                                <img src="{{ asset('storage/' . $listing->image) }}" alt="Current image" class="img-thumbnail" style="max-width: 200px;">
                            </div>
                            @endif
                            <input type="file" class="form-control-file @error('image') is-invalid @enderror"
                                   id="image" name="image" accept="image/*">
                            <small class="form-text text-muted">Leave empty to keep current image</small>
                            @error('image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="is_featured" name="is_featured" value="1" {{ old('is_featured', $listing->is_featured ?? false) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_featured">Featured Listing</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="status">Status <span class="text-danger">*</span></label>
                            <select class="form-control @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="pending" {{ old('status', $listing->status ?? '') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="active" {{ old('status', $listing->status ?? '') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $listing->status ?? '') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-2"></i>Update Listing
                            </button>
                            <a href="{{ route('admin.bot-automation.marketplace.index') }}" class="btn btn-secondary">
                                Cancel
                            </a>
                            <button type="button" class="btn btn-danger float-right" onclick="confirmDelete()">
                                <i class="fas fa-trash mr-2"></i>Delete
                            </button>
                        </div>
                    </form>

                    <form id="deleteForm" action="{{ route('admin.bot-automation.marketplace.listings.destroy', $listing->id ?? 0) }}" method="POST" class="d-none">
                        @csrf
                        @method('DELETE')
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Listing Statistics</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <strong>Views:</strong> {{ $listing->views ?? '0' }}
                        </li>
                        <li class="mb-2">
                            <strong>Subscribers:</strong> {{ $listing->subscribers ?? '0' }}
                        </li>
                        <li class="mb-2">
                            <strong>Revenue:</strong> ${{ number_format($listing->revenue ?? 0, 2) }}
                        </li>
                        <li class="mb-2">
                            <strong>Rating:</strong> {{ number_format($listing->rating ?? 0, 1) }}/5.0
                        </li>
                        <li class="mb-2">
                            <strong>Created:</strong> {{ isset($listing->created_at) ? $listing->created_at->format('M d, Y') : 'N/A' }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete() {
    if (confirm('Are you sure you want to delete this listing? This action cannot be undone.')) {
        document.getElementById('deleteForm').submit();
    }
}
</script>
@endsection
