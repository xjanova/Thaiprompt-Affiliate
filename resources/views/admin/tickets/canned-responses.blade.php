@extends('layouts.admin')

@section('title', 'Canned Responses')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Canned Responses</h1>
        <button class="btn btn-primary" data-toggle="modal" data-target="#createResponseModal">
            <i class="fas fa-plus mr-2"></i>Create Response
        </button>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Quick Reply Templates</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Shortcode</th>
                            <th>Category</th>
                            <th>Tags</th>
                            <th>Public</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($responses as $response)
                        <tr>
                            <td>{{ $response->title }}</td>
                            <td><code>{{ $response->shortcode }}</code></td>
                            <td>{{ $response->category->name ?? 'All' }}</td>
                            <td>
                                @if($response->tags)
                                    @foreach($response->tags as $tag)
                                        <span class="badge badge-secondary">{{ $tag }}</span>
                                    @endforeach
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-{{ $response->is_public ? 'success' : 'warning' }}">
                                    {{ $response->is_public ? 'Yes' : 'No' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-{{ $response->is_active ? 'success' : 'secondary' }}">
                                    {{ $response->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="viewResponse({{ $response->id }}, '{{ addslashes($response->content) }}')">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-primary" onclick="editResponse({{ $response->id }})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="{{ route('admin.tickets.canned-responses.destroy', $response->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">No canned responses yet</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Modal -->
<div class="modal fade" id="createResponseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.tickets.canned-responses.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Create Canned Response</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" name="title" id="title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="shortcode">Shortcode (e.g., /greeting)</label>
                        <input type="text" name="shortcode" id="shortcode" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="content">Content</label>
                        <textarea name="content" id="content" class="form-control" rows="6" required></textarea>
                        <small class="form-text text-muted">
                            Available variables: {user_name}, {ticket_number}, {agent_name}
                        </small>
                    </div>
                    <div class="form-group">
                        <label for="category_id">Category (Optional)</label>
                        <select name="category_id" id="category_id" class="form-control">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_public" id="is_public" class="form-check-input" value="1" checked>
                        <label for="is_public" class="form-check-label">Public (visible to all agents)</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Modal -->
<div class="modal fade" id="viewResponseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Response Content</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <pre id="responseContent" class="bg-light p-3 rounded"></pre>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function viewResponse(id, content) {
    document.getElementById('responseContent').textContent = content;
    $('#viewResponseModal').modal('show');
}
</script>
@endpush
