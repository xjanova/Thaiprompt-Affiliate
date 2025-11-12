@extends('layouts.admin')

@section('title', 'Assignment Rules')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Assignment Rules</h1>
        <button class="btn btn-primary" data-toggle="modal" data-target="#createRuleModal">
            <i class="fas fa-plus mr-2"></i>Create Rule
        </button>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Auto-Assignment Rules</h6>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-2"></i>
                Rules are executed in priority order (lower number = higher priority). First matching rule wins.
            </div>
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Priority</th>
                            <th>Rule Name</th>
                            <th>Category Filter</th>
                            <th>Priority Filter</th>
                            <th>Assign To</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rules as $rule)
                        <tr>
                            <td><strong>{{ $rule->priority }}</strong></td>
                            <td>{{ $rule->name }}</td>
                            <td>{{ $rule->category->name ?? 'All' }}</td>
                            <td>
                                @if($rule->priority_filter)
                                    <span class="badge badge-{{ $rule->priority_filter === 'critical' ? 'danger' : ($rule->priority_filter === 'high' ? 'warning' : 'info') }}">
                                        {{ ucfirst($rule->priority_filter) }}
                                    </span>
                                @else
                                    All
                                @endif
                            </td>
                            <td>{{ $rule->assignTo->name ?? 'N/A' }}</td>
                            <td>
                                <form action="{{ route('admin.tickets.assignment-rules.toggle', $rule->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-{{ $rule->is_active ? 'success' : 'secondary' }}">
                                        {{ $rule->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </form>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="editRule({{ $rule->id }})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="{{ route('admin.tickets.assignment-rules.destroy', $rule->id) }}" method="POST" class="d-inline">
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
                            <td colspan="7" class="text-center text-muted">No assignment rules yet</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createRuleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.tickets.assignment-rules.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Create Assignment Rule</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name">Rule Name</label>
                        <input type="text" name="name" id="name" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="priority">Execution Priority</label>
                                <input type="number" name="priority" id="priority" class="form-control" required min="1" value="100">
                                <small class="form-text text-muted">Lower number = higher priority</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="category_id">Category Filter</label>
                                <select name="category_id" id="category_id" class="form-control">
                                    <option value="">All Categories</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="priority_filter">Priority Filter</label>
                                <select name="priority_filter" id="priority_filter" class="form-control">
                                    <option value="">All Priorities</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="assign_to_user_id">Assign To</label>
                        <select name="assign_to_user_id" id="assign_to_user_id" class="form-control" required>
                            <option value="">Select User</option>
                            @foreach($staffUsers as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
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
@endsection
