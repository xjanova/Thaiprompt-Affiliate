@extends('layouts.admin-v3')

@section('title', 'Edit Job Posting')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Job Posting</h1>
        <a href="{{ route('admin.hrm.recruitment.jobs.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Jobs
        </a>
    </div>

    <form action="{{ route('admin.hrm.recruitment.jobs.update', $job->id ?? 0) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Job Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="title">Job Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror"
                                   id="title" name="title" value="{{ old('title', $job->title ?? '') }}" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="department">Department <span class="text-danger">*</span></label>
                                    <select class="form-control @error('department') is-invalid @enderror"
                                            id="department" name="department" required>
                                        <option value="">Select Department</option>
                                        <option value="engineering" {{ old('department', $job->department ?? '') == 'engineering' ? 'selected' : '' }}>Engineering</option>
                                        <option value="sales" {{ old('department', $job->department ?? '') == 'sales' ? 'selected' : '' }}>Sales</option>
                                        <option value="marketing" {{ old('department', $job->department ?? '') == 'marketing' ? 'selected' : '' }}>Marketing</option>
                                        <option value="hr" {{ old('department', $job->department ?? '') == 'hr' ? 'selected' : '' }}>Human Resources</option>
                                        <option value="finance" {{ old('department', $job->department ?? '') == 'finance' ? 'selected' : '' }}>Finance</option>
                                    </select>
                                    @error('department')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="type">Employment Type <span class="text-danger">*</span></label>
                                    <select class="form-control @error('type') is-invalid @enderror"
                                            id="type" name="type" required>
                                        <option value="">Select Type</option>
                                        <option value="full-time" {{ old('type', $job->type ?? '') == 'full-time' ? 'selected' : '' }}>Full-time</option>
                                        <option value="part-time" {{ old('type', $job->type ?? '') == 'part-time' ? 'selected' : '' }}>Part-time</option>
                                        <option value="contract" {{ old('type', $job->type ?? '') == 'contract' ? 'selected' : '' }}>Contract</option>
                                        <option value="intern" {{ old('type', $job->type ?? '') == 'intern' ? 'selected' : '' }}>Internship</option>
                                    </select>
                                    @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="location">Location <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('location') is-invalid @enderror"
                                           id="location" name="location" value="{{ old('location', $job->location ?? '') }}" required>
                                    @error('location')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="salary_range">Salary Range</label>
                                    <input type="text" class="form-control @error('salary_range') is-invalid @enderror"
                                           id="salary_range" name="salary_range" value="{{ old('salary_range', $job->salary_range ?? '') }}">
                                    @error('salary_range')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Job Description <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="6" required>{{ old('description', $job->description ?? '') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="requirements">Requirements</label>
                            <textarea class="form-control @error('requirements') is-invalid @enderror"
                                      id="requirements" name="requirements" rows="6">{{ old('requirements', $job->requirements ?? '') }}</textarea>
                            @error('requirements')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="benefits">Benefits</label>
                            <textarea class="form-control @error('benefits') is-invalid @enderror"
                                      id="benefits" name="benefits" rows="4">{{ old('benefits', $job->benefits ?? '') }}</textarea>
                            @error('benefits')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Publishing Options</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="status">Status <span class="text-danger">*</span></label>
                            <select class="form-control @error('status') is-invalid @enderror"
                                    id="status" name="status" required>
                                <option value="draft" {{ old('status', $job->status ?? '') == 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="active" {{ old('status', $job->status ?? '') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="closed" {{ old('status', $job->status ?? '') == 'closed' ? 'selected' : '' }}>Closed</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="deadline">Application Deadline</label>
                            <input type="date" class="form-control @error('deadline') is-invalid @enderror"
                                   id="deadline" name="deadline" value="{{ old('deadline', isset($job->deadline) ? date('Y-m-d', strtotime($job->deadline)) : '') }}">
                            @error('deadline')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr>

                        <div class="form-group mb-0">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-save mr-2"></i>Update Job Posting
                            </button>
                            <a href="{{ route('admin.hrm.recruitment.jobs.index') }}" class="btn btn-secondary btn-block">
                                Cancel
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Statistics</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <strong>Applications:</strong> {{ $job->applications_count ?? '0' }}
                            </li>
                            <li class="mb-2">
                                <strong>Views:</strong> {{ $job->views_count ?? '0' }}
                            </li>
                            <li class="mb-2">
                                <strong>Created:</strong> {{ isset($job->created_at) ? $job->created_at->format('M d, Y') : 'N/A' }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
