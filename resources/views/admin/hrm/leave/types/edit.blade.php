@extends('layouts.admin')

@section('title', 'Edit Leave Type')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Leave Type</h1>
        <a href="{{ route('admin.hrm.leave.types.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Leave Types
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Leave Type Details</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.hrm.leave.types.update', $leaveType->id ?? 0) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="name">Leave Type Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                           id="name" name="name" value="{{ old('name', $leaveType->name ?? '') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror"
                              id="description" name="description" rows="3">{{ old('description', $leaveType->description ?? '') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="days_allowed">Days Allowed Per Year <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('days_allowed') is-invalid @enderror"
                                   id="days_allowed" name="days_allowed" value="{{ old('days_allowed', $leaveType->days_allowed ?? 0) }}" min="0" required>
                            @error('days_allowed')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="max_consecutive_days">Max Consecutive Days</label>
                            <input type="number" class="form-control @error('max_consecutive_days') is-invalid @enderror"
                                   id="max_consecutive_days" name="max_consecutive_days" value="{{ old('max_consecutive_days', $leaveType->max_consecutive_days ?? '') }}" min="0">
                            @error('max_consecutive_days')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="carry_forward" name="carry_forward" value="1" {{ old('carry_forward', $leaveType->carry_forward ?? false) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="carry_forward">Allow Carry Forward to Next Year</label>
                    </div>
                </div>

                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="requires_approval" name="requires_approval" value="1" {{ old('requires_approval', $leaveType->requires_approval ?? true) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="requires_approval">Requires Approval</label>
                    </div>
                </div>

                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="is_paid" name="is_paid" value="1" {{ old('is_paid', $leaveType->is_paid ?? true) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="is_paid">Paid Leave</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="status">Status <span class="text-danger">*</span></label>
                    <select class="form-control @error('status') is-invalid @enderror" id="status" name="status" required>
                        <option value="active" {{ old('status', $leaveType->status ?? '') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $leaveType->status ?? '') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group mb-0">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-2"></i>Update Leave Type
                    </button>
                    <a href="{{ route('admin.hrm.leave.types.index') }}" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
