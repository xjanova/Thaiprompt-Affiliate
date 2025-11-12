@extends('layouts.admin')

@section('title', 'Lead Details')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Lead Details</h1>
        <a href="{{ route('admin.bot-automation.sales.leads.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Leads
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Lead Information</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">Name</h6>
                            <p class="font-weight-bold">{{ $lead->name ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Email</h6>
                            <p class="font-weight-bold">{{ $lead->email ?? 'N/A' }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">Phone</h6>
                            <p class="font-weight-bold">{{ $lead->phone ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Company</h6>
                            <p class="font-weight-bold">{{ $lead->company ?? 'N/A' }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">Status</h6>
                            <p>
                                @if(isset($lead->status))
                                    @if($lead->status == 'converted')
                                        <span class="badge badge-success">Converted</span>
                                    @elseif($lead->status == 'qualified')
                                        <span class="badge badge-info">Qualified</span>
                                    @elseif($lead->status == 'contacted')
                                        <span class="badge badge-primary">Contacted</span>
                                    @elseif($lead->status == 'new')
                                        <span class="badge badge-warning">New</span>
                                    @else
                                        <span class="badge badge-secondary">{{ ucfirst($lead->status) }}</span>
                                    @endif
                                @else
                                    N/A
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Lead Score</h6>
                            <p>
                                <span class="badge badge-{{ isset($lead->score) && $lead->score >= 80 ? 'danger' : (isset($lead->score) && $lead->score >= 50 ? 'warning' : 'secondary') }}">
                                    {{ $lead->score ?? '0' }}/100
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">Bot</h6>
                            <p class="font-weight-bold">{{ $lead->bot_name ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Source</h6>
                            <p class="font-weight-bold">{{ $lead->source ?? 'N/A' }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">Estimated Value</h6>
                            <p class="font-weight-bold text-success">${{ number_format($lead->value ?? 0, 2) }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Created</h6>
                            <p class="font-weight-bold">{{ isset($lead->created_at) ? $lead->created_at->format('M d, Y h:i A') : 'N/A' }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="text-muted">Notes</h6>
                            <p>{{ $lead->notes ?? 'No notes available' }}</p>
                        </div>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between">
                        <div>
                            <button class="btn btn-primary" onclick="updateLead()">
                                <i class="fas fa-edit mr-2"></i>Update Lead
                            </button>
                            <button class="btn btn-success" onclick="convertLead()">
                                <i class="fas fa-check mr-2"></i>Mark as Converted
                            </button>
                        </div>
                        <div>
                            <button class="btn btn-danger" onclick="deleteLead()">
                                <i class="fas fa-trash mr-2"></i>Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Interaction History</h6>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        @forelse($interactions ?? [] as $interaction)
                        <div class="timeline-item mb-3">
                            <div class="d-flex">
                                <div class="mr-3">
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fas fa-{{ $interaction->icon ?? 'comment' }}"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">{{ $interaction->title ?? 'Interaction' }}</h6>
                                    <p class="text-muted mb-1">{{ $interaction->description ?? 'No description' }}</p>
                                    <small class="text-muted">{{ isset($interaction->created_at) ? $interaction->created_at->diffForHumans() : 'N/A' }}</small>
                                </div>
                            </div>
                        </div>
                        @empty
                        <p class="text-center text-muted">No interaction history available</p>
                        @endforelse
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
                        <a href="#" class="list-group-item list-group-item-action" onclick="sendEmail()">
                            <i class="fas fa-envelope mr-2"></i>Send Email
                        </a>
                        <a href="#" class="list-group-item list-group-item-action" onclick="makeCall()">
                            <i class="fas fa-phone mr-2"></i>Make Call
                        </a>
                        <a href="#" class="list-group-item list-group-item-action" onclick="scheduleFollowup()">
                            <i class="fas fa-calendar-plus mr-2"></i>Schedule Follow-up
                        </a>
                        <a href="#" class="list-group-item list-group-item-action" onclick="addNote()">
                            <i class="fas fa-sticky-note mr-2"></i>Add Note
                        </a>
                        <a href="#" class="list-group-item list-group-item-action" onclick="assignTo()">
                            <i class="fas fa-user-plus mr-2"></i>Assign To
                        </a>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Lead Statistics</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <strong>Total Interactions:</strong> {{ $lead->interactions_count ?? '0' }}
                        </li>
                        <li class="mb-2">
                            <strong>Last Contact:</strong> {{ isset($lead->last_contact) ? date('M d, Y', strtotime($lead->last_contact)) : 'Never' }}
                        </li>
                        <li class="mb-2">
                            <strong>Days in Pipeline:</strong> {{ $lead->days_in_pipeline ?? '0' }}
                        </li>
                        <li class="mb-2">
                            <strong>Conversion Probability:</strong> {{ $lead->conversion_probability ?? '0' }}%
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateLead() {
    console.log('Updating lead');
}

function convertLead() {
    if (confirm('Mark this lead as converted?')) {
        console.log('Converting lead');
    }
}

function deleteLead() {
    if (confirm('Are you sure you want to delete this lead? This action cannot be undone.')) {
        console.log('Deleting lead');
    }
}

function sendEmail() {
    console.log('Sending email');
}

function makeCall() {
    console.log('Making call');
}

function scheduleFollowup() {
    console.log('Scheduling follow-up');
}

function addNote() {
    console.log('Adding note');
}

function assignTo() {
    console.log('Assigning lead');
}
</script>
@endsection
