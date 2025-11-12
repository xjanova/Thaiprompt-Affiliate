@extends('layouts.admin')

@section('title', 'Support Tickets')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Support Tickets</h1>
        <a href="{{ route('admin.bot-automation.support.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Ticket Management</h6>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" placeholder="Search tickets..." id="searchTickets">
                </div>
                <div class="col-md-2">
                    <select class="form-control" id="filterStatus">
                        <option value="">All Status</option>
                        <option value="open">Open</option>
                        <option value="in_progress">In Progress</option>
                        <option value="pending">Pending</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-control" id="filterPriority">
                        <option value="">All Priorities</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
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
                    <select class="form-control" id="filterCategory">
                        <option value="">All Categories</option>
                        <option value="technical">Technical Issue</option>
                        <option value="billing">Billing</option>
                        <option value="feature">Feature Request</option>
                        <option value="bug">Bug Report</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered" id="ticketsTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Subject</th>
                            <th>User</th>
                            <th>Bot</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tickets ?? [] as $ticket)
                        <tr>
                            <td>{{ $ticket->id }}</td>
                            <td>{{ Str::limit($ticket->subject ?? 'N/A', 40) }}</td>
                            <td>{{ $ticket->user_name ?? 'N/A' }}</td>
                            <td>{{ $ticket->bot_name ?? 'N/A' }}</td>
                            <td>{{ ucfirst($ticket->category ?? 'N/A') }}</td>
                            <td>
                                @if($ticket->priority == 'high')
                                    <span class="badge badge-danger">High</span>
                                @elseif($ticket->priority == 'medium')
                                    <span class="badge badge-warning">Medium</span>
                                @else
                                    <span class="badge badge-info">Low</span>
                                @endif
                            </td>
                            <td>
                                @if($ticket->status == 'open')
                                    <span class="badge badge-warning">Open</span>
                                @elseif($ticket->status == 'in_progress')
                                    <span class="badge badge-primary">In Progress</span>
                                @elseif($ticket->status == 'resolved')
                                    <span class="badge badge-success">Resolved</span>
                                @elseif($ticket->status == 'closed')
                                    <span class="badge badge-secondary">Closed</span>
                                @else
                                    <span class="badge badge-info">{{ ucfirst($ticket->status) }}</span>
                                @endif
                            </td>
                            <td>{{ isset($ticket->created_at) ? $ticket->created_at->format('M d, Y') : 'N/A' }}</td>
                            <td>
                                <a href="{{ route('admin.bot-automation.support.tickets.show', $ticket->id ?? 0) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($ticket->status != 'closed')
                                <button class="btn btn-sm btn-success" onclick="updateStatus({{ $ticket->id }}, 'resolved')">
                                    <i class="fas fa-check"></i>
                                </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted">No tickets found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(isset($tickets) && method_exists($tickets, 'links'))
            <div class="mt-3">
                {{ $tickets->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<script>
function updateStatus(ticketId, status) {
    if (confirm('Update ticket status to ' + status + '?')) {
        console.log('Updating ticket', ticketId, 'to', status);
    }
}
</script>
@endsection
