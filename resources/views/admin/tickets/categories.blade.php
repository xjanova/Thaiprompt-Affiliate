@extends('layouts.admin')

@section('title', 'Ticket Categories')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Ticket Categories</h1>
        <button class="btn btn-primary" onclick="createCategory()">
            <i class="fas fa-plus mr-2"></i>Create Category
        </button>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Category Management</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Category Name</th>
                            <th>Description</th>
                            <th>Ticket Count</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories ?? [] as $category)
                        <tr>
                            <td>{{ $category->id }}</td>
                            <td>{{ $category->name ?? 'N/A' }}</td>
                            <td>{{ Str::limit($category->description ?? '', 50) }}</td>
                            <td>{{ $category->tickets_count ?? '0' }}</td>
                            <td>
                                <span class="badge badge-{{ ($category->status ?? 'active') == 'active' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($category->status ?? 'Active') }}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="editCategory({{ $category->id }})">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No categories found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function createCategory() {
    console.log('Creating category');
}

function editCategory(id) {
    console.log('Editing category:', id);
}
</script>
@endsection
