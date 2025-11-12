@extends('layouts.admin')

@section('title', 'Ticket Details')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Ticket #{{ $ticket->id ?? 'N/A' }}</h1>
        <a href="{{ route('admin.bot-automation.support.tickets.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Tickets
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">{{ $ticket->subject ?? 'N/A' }}</h6>
                    <div>
                        @if(isset($ticket->priority))
                            @if($ticket->priority == 'high')
                                <span class="badge badge-danger">High Priority</span>
                            @elseif($ticket->priority == 'medium')
                                <span class="badge badge-warning">Medium Priority</span>
                            @else
                                <span class="badge badge-info">Low Priority</span>
                            @endif
                        @endif

                        @if(isset($ticket->status))
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
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">Submitted By</h6>
                            <p class="font-weight-bold">{{ $ticket->user_name ?? 'N/A' }}</p>
                            <p class="text-muted small">{{ $ticket->user_email ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Bot</h6>
                            <p class="font-weight-bold">{{ $ticket->bot_name ?? 'N/A' }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">Category</h6>
                            <p class="font-weight-bold">{{ ucfirst($ticket->category ?? 'N/A') }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Created</h6>
                            <p class="font-weight-bold">{{ isset($ticket->created_at) ? $ticket->created_at->format('M d, Y h:i A') : 'N/A' }}</p>
                        </div>
                    </div>

                    <hr>

                    <div class="mb-4">
                        <h6 class="text-muted">Description</h6>
                        <p>{{ $ticket->description ?? 'No description provided' }}</p>
                    </div>

                    @if(isset($ticket->attachments) && count($ticket->attachments) > 0)
                    <div class="mb-4">
                        <h6 class="text-muted">Attachments</h6>
                        <div class="list-group">
                            @foreach($ticket->attachments as $attachment)
                            <a href="{{ asset('storage/' . $attachment->path) }}" class="list-group-item list-group-item-action" target="_blank">
                                <i class="fas fa-paperclip mr-2"></i>{{ $attachment->name }}
                                <small class="text-muted">({{ $attachment->size }})</small>
                            </a>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <hr>

                    <div>
                        <h6 class="text-muted mb-3">Conversation</h6>
                        @forelse($ticket->replies ?? [] as $reply)
                        <div class="card mb-3 {{ $reply->is_staff ? 'border-primary' : '' }}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <strong>{{ $reply->user_name ?? 'N/A' }}</strong>
                                    <small class="text-muted">{{ isset($reply->created_at) ? $reply->created_at->diffForHumans() : 'N/A' }}</small>
                                </div>
                                <p class="mb-0">{{ $reply->message ?? '' }}</p>
                            </div>
                        </div>
                        @empty
                        <p class="text-center text-muted">No replies yet</p>
                        @endforelse
                    </div>

                    @if(isset($ticket->status) && $ticket->status != 'closed')
                    <hr>

                    <div>
                        <h6 class="text-muted mb-3">Add Reply</h6>
                        <form action="{{ route('admin.bot-automation.support.tickets.reply', $ticket->id ?? 0) }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <textarea class="form-control" name="message" rows="4" placeholder="Type your reply..." required></textarea>
                            </div>
                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane mr-2"></i>Send Reply
                                </button>
                                <div>
                                    <button type="button" class="btn btn-success" onclick="updateStatus('resolved')">
                                        <i class="fas fa-check mr-2"></i>Mark as Resolved
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="updateStatus('closed')">
                                        <i class="fas fa-times mr-2"></i>Close Ticket
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Ticket Actions</h6>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="status">Update Status</label>
                        <select class="form-control" id="status" onchange="updateTicketStatus(this.value)">
                            <option value="open" {{ isset($ticket->status) && $ticket->status == 'open' ? 'selected' : '' }}>Open</option>
                            <option value="in_progress" {{ isset($ticket->status) && $ticket->status == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="pending" {{ isset($ticket->status) && $ticket->status == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="resolved" {{ isset($ticket->status) && $ticket->status == 'resolved' ? 'selected' : '' }}>Resolved</option>
                            <option value="closed" {{ isset($ticket->status) && $ticket->status == 'closed' ? 'selected' : '' }}>Closed</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="priority">Update Priority</label>
                        <select class="form-control" id="priority" onchange="updateTicketPriority(this.value)">
                            <option value="low" {{ isset($ticket->priority) && $ticket->priority == 'low' ? 'selected' : '' }}>Low</option>
                            <option value="medium" {{ isset($ticket->priority) && $ticket->priority == 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="high" {{ isset($ticket->priority) && $ticket->priority == 'high' ? 'selected' : '' }}>High</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="assignee">Assign To</label>
                        <select class="form-control" id="assignee" onchange="assignTicket(this.value)">
                            <option value="">Unassigned</option>
                            @foreach($staff ?? [] as $member)
                            <option value="{{ $member->id }}" {{ isset($ticket->assignee_id) && $ticket->assignee_id == $member->id ? 'selected' : '' }}>
                                {{ $member->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <hr>

                    <div class="list-group">
                        <a href="#" class="list-group-item list-group-item-action" onclick="exportTicket()">
                            <i class="fas fa-download mr-2"></i>Export Ticket
                        </a>
                        <a href="#" class="list-group-item list-group-item-action" onclick="printTicket()">
                            <i class="fas fa-print mr-2"></i>Print Ticket
                        </a>
                        <a href="#" class="list-group-item list-group-item-action text-danger" onclick="deleteTicket()">
                            <i class="fas fa-trash mr-2"></i>Delete Ticket
                        </a>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Ticket Statistics</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <strong>Total Replies:</strong> {{ $ticket->replies_count ?? '0' }}
                        </li>
                        <li class="mb-2">
                            <strong>First Response:</strong> {{ isset($ticket->first_response) ? date('M d, Y', strtotime($ticket->first_response)) : 'Pending' }}
                        </li>
                        <li class="mb-2">
                            <strong>Last Updated:</strong> {{ isset($ticket->updated_at) ? $ticket->updated_at->diffForHumans() : 'N/A' }}
                        </li>
                        <li class="mb-2">
                            <strong>Resolution Time:</strong> {{ $ticket->resolution_time ?? 'N/A' }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateStatus(status) {
    if (confirm('Update ticket status to ' + status + '?')) {
        console.log('Updating status to:', status);
    }
}

function updateTicketStatus(status) {
    console.log('Updating ticket status to:', status);
}

function updateTicketPriority(priority) {
    console.log('Updating ticket priority to:', priority);
}

function assignTicket(userId) {
    console.log('Assigning ticket to user:', userId);
}

function exportTicket() {
    console.log('Exporting ticket');
}

function printTicket() {
    window.print();
}

function deleteTicket() {
    if (confirm('Are you sure you want to delete this ticket? This action cannot be undone.')) {
        console.log('Deleting ticket');
    }
}
</script>
@endsection
