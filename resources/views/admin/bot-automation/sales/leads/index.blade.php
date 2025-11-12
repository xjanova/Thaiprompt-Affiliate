@extends('layouts.admin')

@section('title', 'Sales Leads')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Sales Leads</h1>
        <a href="{{ route('admin.bot-automation.sales.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
        </a>
    </div>

    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Leads</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalLeads ?? '0' }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Hot Leads</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $hotLeads ?? '0' }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-fire fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">New This Week</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $weeklyLeads ?? '0' }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-week fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Avg Lead Score</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($avgLeadScore ?? 0, 0) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-star fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Lead Management</h6>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" placeholder="Search leads..." id="searchLeads">
                </div>
                <div class="col-md-2">
                    <select class="form-control" id="filterStatus">
                        <option value="">All Status</option>
                        <option value="new">New</option>
                        <option value="contacted">Contacted</option>
                        <option value="qualified">Qualified</option>
                        <option value="converted">Converted</option>
                        <option value="lost">Lost</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-control" id="filterScore">
                        <option value="">All Scores</option>
                        <option value="hot">Hot (80-100)</option>
                        <option value="warm">Warm (50-79)</option>
                        <option value="cold">Cold (0-49)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-control" id="filterBot">
                        <option value="">All Bots</option>
                        @foreach($bots ?? [] as $bot)
                        <option value="{{ $bot->id }}">{{ $bot->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" class="form-control" placeholder="Filter by date" id="filterDate">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered" id="leadsTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Bot</th>
                            <th>Score</th>
                            <th>Status</th>
                            <th>Value</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leads ?? [] as $lead)
                        <tr>
                            <td>{{ $lead->id }}</td>
                            <td>{{ $lead->name ?? 'N/A' }}</td>
                            <td>{{ $lead->email ?? 'N/A' }}</td>
                            <td>{{ $lead->bot_name ?? 'N/A' }}</td>
                            <td>
                                <span class="badge badge-{{ $lead->score >= 80 ? 'danger' : ($lead->score >= 50 ? 'warning' : 'secondary') }}">
                                    {{ $lead->score ?? '0' }}
                                </span>
                            </td>
                            <td>
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
                            </td>
                            <td>${{ number_format($lead->value ?? 0, 2) }}</td>
                            <td>{{ isset($lead->created_at) ? $lead->created_at->format('M d, Y') : 'N/A' }}</td>
                            <td>
                                <a href="{{ route('admin.bot-automation.sales.leads.show', $lead->id ?? 0) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button class="btn btn-sm btn-primary" onclick="contactLead({{ $lead->id }})">
                                    <i class="fas fa-phone"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted">No leads found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(isset($leads) && method_exists($leads, 'links'))
            <div class="mt-3">
                {{ $leads->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<script>
function contactLead(leadId) {
    console.log('Contacting lead:', leadId);
}
</script>
@endsection
