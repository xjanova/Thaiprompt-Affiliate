@extends('layouts.admin')

@section('title', 'SLA Policies')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">SLA Policies</h1>
        <button class="btn btn-primary" data-toggle="modal" data-target="#createPolicyModal">
            <i class="fas fa-plus mr-2"></i>Create Policy
        </button>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Service Level Agreement Policies</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Policy Name</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>First Response</th>
                            <th>Resolution Time</th>
                            <th>Business Hours</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($policies as $policy)
                        <tr>
                            <td>
                                <strong>{{ $policy->name }}</strong>
                                @if($policy->description)
                                    <br><small class="text-muted">{{ $policy->description }}</small>
                                @endif
                            </td>
                            <td>{{ $policy->category->name ?? 'All' }}</td>
                            <td>
                                @if($policy->priority)
                                    <span class="badge badge-{{ $policy->priority === 'critical' ? 'danger' : ($policy->priority === 'high' ? 'warning' : 'info') }}">
                                        {{ ucfirst($policy->priority) }}
                                    </span>
                                @else
                                    All
                                @endif
                            </td>
                            <td>{{ $policy->first_response_time }} min</td>
                            <td>{{ $policy->resolution_time }} min</td>
                            <td>
                                <span class="badge badge-{{ $policy->business_hours_only ? 'warning' : 'success' }}">
                                    {{ $policy->business_hours_only ? 'Business Only' : '24/7' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-{{ $policy->is_active ? 'success' : 'secondary' }}">
                                    {{ $policy->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="editPolicy({{ $policy->id }})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="{{ route('admin.tickets.sla-policies.destroy', $policy->id) }}" method="POST" class="d-inline">
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
                            <td colspan="8" class="text-center text-muted">No SLA policies yet</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createPolicyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.tickets.sla-policies.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Create SLA Policy</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name">Policy Name</label>
                        <input type="text" name="name" id="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="category_id">Category (Optional)</label>
                                <select name="category_id" id="category_id" class="form-control">
                                    <option value="">All Categories</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="priority">Priority (Optional)</label>
                                <select name="priority" id="priority" class="form-control">
                                    <option value="">All Priorities</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="first_response_time">First Response Time (minutes)</label>
                                <input type="number" name="first_response_time" id="first_response_time" class="form-control" required min="1">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="resolution_time">Resolution Time (minutes)</label>
                                <input type="number" name="resolution_time" id="resolution_time" class="form-control" required min="1">
                            </div>
                        </div>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="business_hours_only" id="business_hours_only" class="form-check-input" value="1">
                        <label for="business_hours_only" class="form-check-label">Business Hours Only</label>
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
