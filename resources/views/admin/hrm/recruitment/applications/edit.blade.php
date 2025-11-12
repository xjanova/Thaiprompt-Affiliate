@extends('layouts.admin')

@section('title', 'Edit Application')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Application</h1>
        <a href="{{ route('admin.hrm.recruitment.applications.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Applications
        </a>
    </div>

    <form action="{{ route('admin.hrm.recruitment.applications.update', $application->id ?? 0) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Application Status</h6>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="status">Status <span class="text-danger">*</span></label>
                    <select class="form-control @error('status') is-invalid @enderror"
                            id="status" name="status" required>
                        <option value="pending" {{ old('status', $application->status ?? '') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="shortlisted" {{ old('status', $application->status ?? '') == 'shortlisted' ? 'selected' : '' }}>Shortlisted</option>
                        <option value="interview" {{ old('status', $application->status ?? '') == 'interview' ? 'selected' : '' }}>Interview</option>
                        <option value="accepted" {{ old('status', $application->status ?? '') == 'accepted' ? 'selected' : '' }}>Accepted</option>
                        <option value="rejected" {{ old('status', $application->status ?? '') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="notes">Admin Notes</label>
                    <textarea class="form-control @error('notes') is-invalid @enderror"
                              id="notes" name="notes" rows="4">{{ old('notes', $application->notes ?? '') }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group mb-0">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-2"></i>Update Application
                    </button>
                    <a href="{{ route('admin.hrm.recruitment.applications.index') }}" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
