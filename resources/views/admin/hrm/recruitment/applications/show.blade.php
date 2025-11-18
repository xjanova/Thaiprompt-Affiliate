@extends('layouts.admin-v3')

@section('title', 'Application Details')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Application Details</h1>
        <div>
            <a href="{{ route('admin.hrm.recruitment.applications.edit', $application->id ?? 0) }}" class="btn btn-primary">
                <i class="fas fa-edit mr-2"></i>Edit
            </a>
            <a href="{{ route('admin.hrm.recruitment.applications.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i>Back
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Applicant Information</h6>
                    <span class="badge badge-{{ isset($application->status) && $application->status == 'accepted' ? 'success' : (isset($application->status) && $application->status == 'rejected' ? 'danger' : 'warning') }}">
                        {{ isset($application->status) ? ucfirst($application->status) : 'N/A' }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">Full Name</h6>
                            <p class="font-weight-bold">{{ $application->name ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Email</h6>
                            <p class="font-weight-bold">{{ $application->email ?? 'N/A' }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">Phone</h6>
                            <p class="font-weight-bold">{{ $application->phone ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Applied For</h6>
                            <p class="font-weight-bold">{{ $application->job_title ?? 'N/A' }}</p>
                        </div>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <h6 class="text-muted">Cover Letter</h6>
                        <p>{{ $application->cover_letter ?? 'No cover letter provided' }}</p>
                    </div>

                    <div class="mb-3">
                        <h6 class="text-muted">Resume/CV</h6>
                        @if(isset($application->resume_path))
                            <a href="{{ asset('storage/' . $application->resume_path) }}" target="_blank" class="btn btn-sm btn-primary">
                                <i class="fas fa-download mr-2"></i>Download Resume
                            </a>
                        @else
                            <p class="text-muted">No resume uploaded</p>
                        @endif
                    </div>

                    <div class="mb-3">
                        <h6 class="text-muted">Admin Notes</h6>
                        <p>{{ $application->notes ?? 'No notes added' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="{{ route('admin.hrm.recruitment.applications.edit', $application->id ?? 0) }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-edit mr-2"></i>Edit Application
                        </a>
                        <a href="#" class="list-group-item list-group-item-action" onclick="changeStatus('shortlisted')">
                            <i class="fas fa-star mr-2"></i>Shortlist
                        </a>
                        <a href="#" class="list-group-item list-group-item-action" onclick="changeStatus('interview')">
                            <i class="fas fa-calendar mr-2"></i>Schedule Interview
                        </a>
                        <a href="#" class="list-group-item list-group-item-action" onclick="changeStatus('accepted')">
                            <i class="fas fa-check mr-2 text-success"></i>Accept
                        </a>
                        <a href="#" class="list-group-item list-group-item-action" onclick="changeStatus('rejected')">
                            <i class="fas fa-times mr-2 text-danger"></i>Reject
                        </a>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Application Info</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <strong>Applied:</strong> {{ isset($application->created_at) ? $application->created_at->format('M d, Y') : 'N/A' }}
                        </li>
                        <li class="mb-2">
                            <strong>Last Updated:</strong> {{ isset($application->updated_at) ? $application->updated_at->diffForHumans() : 'N/A' }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function changeStatus(status) {
    if (confirm('Change application status to ' + status + '?')) {
        console.log('Changing status to:', status);
    }
}
</script>
@endsection
