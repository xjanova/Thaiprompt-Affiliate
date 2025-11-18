@extends('layouts.admin-v3')

@section('title', 'Job Postings')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Job Postings</h1>
        <a href="{{ route('admin.hrm.recruitment.jobs.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-2"></i>Create Job Posting
        </a>
    </div>

    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Jobs</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalJobs ?? '0' }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-briefcase fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Postings</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $activeJobs ?? '0' }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Applications</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalApplications ?? '0' }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Review</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $pendingApplications ?? '0' }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Job Listings</h6>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" placeholder="Search jobs..." id="searchJobs">
                </div>
                <div class="col-md-3">
                    <select class="form-control" id="filterStatus">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="closed">Closed</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-control" id="filterDepartment">
                        <option value="">All Departments</option>
                        <option value="engineering">Engineering</option>
                        <option value="sales">Sales</option>
                        <option value="marketing">Marketing</option>
                        <option value="hr">Human Resources</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Job Title</th>
                            <th>Department</th>
                            <th>Location</th>
                            <th>Type</th>
                            <th>Applications</th>
                            <th>Status</th>
                            <th>Posted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($jobs ?? [] as $job)
                        <tr>
                            <td>{{ $job->id }}</td>
                            <td>{{ $job->title ?? 'N/A' }}</td>
                            <td>{{ $job->department ?? 'N/A' }}</td>
                            <td>{{ $job->location ?? 'N/A' }}</td>
                            <td>{{ ucfirst($job->type ?? 'N/A') }}</td>
                            <td>
                                <span class="badge badge-primary">{{ $job->applications_count ?? '0' }}</span>
                            </td>
                            <td>
                                @if($job->status == 'active')
                                    <span class="badge badge-success">Active</span>
                                @elseif($job->status == 'closed')
                                    <span class="badge badge-secondary">Closed</span>
                                @else
                                    <span class="badge badge-warning">{{ ucfirst($job->status) }}</span>
                                @endif
                            </td>
                            <td>{{ isset($job->created_at) ? $job->created_at->format('M d, Y') : 'N/A' }}</td>
                            <td>
                                <a href="{{ route('admin.hrm.recruitment.jobs.show', $job->id ?? 0) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.hrm.recruitment.jobs.edit', $job->id ?? 0) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted">No job postings found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(isset($jobs) && method_exists($jobs, 'links'))
            <div class="mt-3">
                {{ $jobs->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
