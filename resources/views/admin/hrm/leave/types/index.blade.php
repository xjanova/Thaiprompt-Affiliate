@extends('layouts.admin-v3')

@section('title', 'Leave Types')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Leave Types</h1>
        <a href="{{ route('admin.hrm.leave.types.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-2"></i>Create Leave Type
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Leave Type Management</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Leave Type</th>
                            <th>Days Allowed</th>
                            <th>Carry Forward</th>
                            <th>Requires Approval</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leaveTypes ?? [] as $type)
                        <tr>
                            <td>{{ $type->id }}</td>
                            <td>{{ $type->name ?? 'N/A' }}</td>
                            <td>{{ $type->days_allowed ?? '0' }} days</td>
                            <td>
                                @if($type->carry_forward ?? false)
                                    <span class="badge badge-success">Yes</span>
                                @else
                                    <span class="badge badge-secondary">No</span>
                                @endif
                            </td>
                            <td>
                                @if($type->requires_approval ?? true)
                                    <span class="badge badge-warning">Yes</span>
                                @else
                                    <span class="badge badge-info">No</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-{{ ($type->status ?? 'active') == 'active' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($type->status ?? 'Active') }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.hrm.leave.types.edit', $type->id ?? 0) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">No leave types configured</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
