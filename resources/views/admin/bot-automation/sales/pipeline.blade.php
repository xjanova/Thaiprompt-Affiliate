@extends('layouts.admin')

@section('title', 'Sales Pipeline')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Sales Pipeline</h1>
        <a href="{{ route('admin.bot-automation.sales.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
        </a>
    </div>

    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted">Lead</h6>
                    <h4>{{ $pipelineStats['lead'] ?? '0' }}</h4>
                    <small>${{ number_format($pipelineValue['lead'] ?? 0, 0) }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted">Qualified</h6>
                    <h4>{{ $pipelineStats['qualified'] ?? '0' }}</h4>
                    <small>${{ number_format($pipelineValue['qualified'] ?? 0, 0) }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted">Proposal</h6>
                    <h4>{{ $pipelineStats['proposal'] ?? '0' }}</h4>
                    <small>${{ number_format($pipelineValue['proposal'] ?? 0, 0) }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted">Negotiation</h6>
                    <h4>{{ $pipelineStats['negotiation'] ?? '0' }}</h4>
                    <small>${{ number_format($pipelineValue['negotiation'] ?? 0, 0) }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center border-success">
                <div class="card-body">
                    <h6 class="text-success">Closed Won</h6>
                    <h4 class="text-success">{{ $pipelineStats['won'] ?? '0' }}</h4>
                    <small>${{ number_format($pipelineValue['won'] ?? 0, 0) }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center border-danger">
                <div class="card-body">
                    <h6 class="text-danger">Closed Lost</h6>
                    <h4 class="text-danger">{{ $pipelineStats['lost'] ?? '0' }}</h4>
                    <small>${{ number_format($pipelineValue['lost'] ?? 0, 0) }}</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Pipeline Visualization</h6>
        </div>
        <div class="card-body">
            <div class="chart-area">
                <canvas id="pipelineChart"></canvas>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Pipeline Details</h6>
            <div>
                <select class="form-control form-control-sm" id="filterStage">
                    <option value="">All Stages</option>
                    <option value="lead">Lead</option>
                    <option value="qualified">Qualified</option>
                    <option value="proposal">Proposal</option>
                    <option value="negotiation">Negotiation</option>
                    <option value="won">Closed Won</option>
                    <option value="lost">Closed Lost</option>
                </select>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Lead Name</th>
                            <th>Bot</th>
                            <th>Stage</th>
                            <th>Value</th>
                            <th>Probability</th>
                            <th>Expected Close</th>
                            <th>Last Contact</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pipelineLeads ?? [] as $lead)
                        <tr>
                            <td>{{ $lead->name ?? 'N/A' }}</td>
                            <td>{{ $lead->bot_name ?? 'N/A' }}</td>
                            <td>
                                <span class="badge badge-{{ $lead->stage_color ?? 'secondary' }}">
                                    {{ ucfirst($lead->stage ?? 'N/A') }}
                                </span>
                            </td>
                            <td>${{ number_format($lead->value ?? 0, 2) }}</td>
                            <td>{{ $lead->probability ?? '0' }}%</td>
                            <td>{{ isset($lead->expected_close) ? date('M d, Y', strtotime($lead->expected_close)) : 'N/A' }}</td>
                            <td>{{ isset($lead->last_contact) ? date('M d, Y', strtotime($lead->last_contact)) : 'Never' }}</td>
                            <td>
                                <a href="{{ route('admin.bot-automation.sales.leads.show', $lead->id ?? 0) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button class="btn btn-sm btn-primary" onclick="updateStage({{ $lead->id }})">
                                    <i class="fas fa-exchange-alt"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">No leads in pipeline</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function updateStage(leadId) {
    // Show modal to update lead stage
    console.log('Updating stage for lead:', leadId);
}
</script>
@endsection
