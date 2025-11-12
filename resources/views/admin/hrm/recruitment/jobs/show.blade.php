@extends('layouts.admin')

@section('title', 'Job Details')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ $job->title ?? 'Job Details' }}</h1>
        <div>
            <a href="{{ route('admin.hrm.recruitment.jobs.edit', $job->id ?? 0) }}" class="btn btn-primary">
                <i class="fas fa-edit mr-2"></i>Edit
            </a>
            <a href="{{ route('admin.hrm.recruitment.jobs.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i>Back
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Job Information</h6>
                    <span class="badge badge-{{ isset($job->status) && $job->status == 'active' ? 'success' : 'secondary' }}">
                        {{ isset($job->status) ? ucfirst($job->status) : 'N/A' }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">Department</h6>
                            <p class="font-weight-bold">{{ isset($job->department) ? ucfirst($job->department) : 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Employment Type</h6>
                            <p class="font-weight-bold">{{ isset($job->type) ? ucfirst($job->type) : 'N/A' }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">Location</h6>
                            <p class="font-weight-bold">{{ $job->location ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Salary Range</h6>
                            <p class="font-weight-bold">{{ $job->salary_range ?? 'Not specified' }}</p>
                        </div>
                    </div>

                    <hr>

                    <div class="mb-4">
                        <h6 class="text-muted">Job Description</h6>
                        <p>{{ $job->description ?? 'No description available' }}</p>
                    </div>

                    <div class="mb-4">
                        <h6 class="text-muted">Requirements</h6>
                        <p>{{ $job->requirements ?? 'No requirements specified' }}</p>
                    </div>

                    <div class="mb-4">
                        <h6 class="text-muted">Benefits</h6>
                        <p>{{ $job->benefits ?? 'No benefits listed' }}</p>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Applications ({{ $job->applications_count ?? '0' }})</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Applicant</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Applied</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($job->applications ?? [] as $application)
                                <tr>
                                    <td>{{ $application->name ?? 'N/A' }}</td>
                                    <td>{{ $application->email ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge badge-{{ $application->status == 'pending' ? 'warning' : ($application->status == 'approved' ? 'success' : 'secondary') }}">
                                            {{ ucfirst($application->status ?? 'N/A') }}
                                        </span>
                                    </td>
                                    <td>{{ isset($application->created_at) ? $application->created_at->format('M d, Y') : 'N/A' }}</td>
                                    <td>
                                        <a href="{{ route('admin.hrm.recruitment.applications.show', $application->id ?? 0) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No applications yet</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Statistics</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <strong>Total Applications:</strong> {{ $job->applications_count ?? '0' }}
                        </li>
                        <li class="mb-2">
                            <strong>Total Views:</strong> {{ $job->views_count ?? '0' }}
                        </li>
                        <li class="mb-2">
                            <strong>Posted:</strong> {{ isset($job->created_at) ? $job->created_at->format('M d, Y') : 'N/A' }}
                        </li>
                        <li class="mb-2">
                            <strong>Deadline:</strong> {{ isset($job->deadline) ? date('M d, Y', strtotime($job->deadline)) : 'No deadline' }}
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="{{ route('admin.hrm.recruitment.jobs.edit', $job->id ?? 0) }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-edit mr-2"></i>Edit Job
                        </a>
                        <a href="#" class="list-group-item list-group-item-action">
                            <i class="fas fa-share mr-2"></i>Share Job
                        </a>
                        <a href="#" class="list-group-item list-group-item-action">
                            <i class="fas fa-download mr-2"></i>Export Applications
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
