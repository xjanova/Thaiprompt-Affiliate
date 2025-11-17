@extends('layouts.admin-v3')

@section('title', 'Create Application')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Create Application</h1>
        <a href="{{ route('admin.hrm.recruitment.applications.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Applications
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Application Form</h6>
        </div>
        <div class="card-body">
            <p class="text-muted">This page is under construction.</p>
        </div>
    </div>
</div>
@endsection
