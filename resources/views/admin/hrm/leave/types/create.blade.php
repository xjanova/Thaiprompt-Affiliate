@extends('layouts.admin')

@section('title', 'Create Leave Type')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Create Leave Type</h1>
        <a href="{{ route('admin.hrm.leave.types.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Leave Types
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Leave Type Details</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.hrm.leave.types.store') }}" method="POST">
                        @csrf

                        <div class="form-group">
                            <label for="name">Leave Type Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="days_allowed">Days Allowed Per Year <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('days_allowed') is-invalid @enderror"
                                           id="days_allowed" name="days_allowed" value="{{ old('days_allowed', 0) }}" min="0" required>
                                    @error('days_allowed')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="max_consecutive_days">Max Consecutive Days</label>
                                    <input type="number" class="form-control @error('max_consecutive_days') is-invalid @enderror"
                                           id="max_consecutive_days" name="max_consecutive_days" value="{{ old('max_consecutive_days') }}" min="0">
                                    @error('max_consecutive_days')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="carry_forward" name="carry_forward" value="1" {{ old('carry_forward') ? 'checked' : '' }}>
                                <label class="custom-control-label" for="carry_forward">Allow Carry Forward to Next Year</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="requires_approval" name="requires_approval" value="1" {{ old('requires_approval', true) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="requires_approval">Requires Approval</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="is_paid" name="is_paid" value="1" {{ old('is_paid', true) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_paid">Paid Leave</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="status">Status <span class="text-danger">*</span></label>
                            <select class="form-control @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-2"></i>Create Leave Type
                            </button>
                            <a href="{{ route('admin.hrm.leave.types.index') }}" class="btn btn-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Common Leave Types</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="fas fa-check text-success mr-2"></i>Annual Leave</li>
                        <li class="mb-2"><i class="fas fa-check text-success mr-2"></i>Sick Leave</li>
                        <li class="mb-2"><i class="fas fa-check text-success mr-2"></i>Maternity Leave</li>
                        <li class="mb-2"><i class="fas fa-check text-success mr-2"></i>Paternity Leave</li>
                        <li class="mb-2"><i class="fas fa-check text-success mr-2"></i>Unpaid Leave</li>
                        <li class="mb-2"><i class="fas fa-check text-success mr-2"></i>Compassionate Leave</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
