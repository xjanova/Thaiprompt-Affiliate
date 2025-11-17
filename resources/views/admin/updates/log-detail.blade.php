@extends('layouts.admin-v3')

@section('title', 'Update Log Details')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Update Log Details</h1>
        <a href="{{ route('admin.updates.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Updates
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Version {{ $update->version ?? 'N/A' }}</h6>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <h6 class="text-muted">Version</h6>
                    <p class="font-weight-bold">{{ $update->version ?? 'N/A' }}</p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted">Release Date</h6>
                    <p class="font-weight-bold">{{ isset($update->released_at) ? date('M d, Y', strtotime($update->released_at)) : 'N/A' }}</p>
                </div>
            </div>

            <div class="mb-3">
                <h6 class="text-muted">Release Notes</h6>
                <div class="border p-3 bg-light">
                    {!! $update->notes ?? 'No release notes available' !!}
                </div>
            </div>

            <div class="mb-3">
                <h6 class="text-muted">Changes</h6>
                <ul>
                    @forelse($update->changes ?? [] as $change)
                    <li>{{ $change }}</li>
                    @empty
                    <li class="text-muted">No changes listed</li>
                    @endforelse
                </ul>
            </div>

            <div class="mb-3">
                <h6 class="text-muted">Status</h6>
                <span class="badge badge-{{ ($update->status ?? 'active') == 'active' ? 'success' : 'secondary' }}">
                    {{ ucfirst($update->status ?? 'Active') }}
                </span>
            </div>
        </div>
    </div>
</div>
@endsection
