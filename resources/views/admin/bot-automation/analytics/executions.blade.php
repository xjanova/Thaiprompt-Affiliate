@extends('layouts.admin')

@section('title', 'Bot Executions')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Bot Executions</h1>
        <a href="{{ route('admin.bot-automation.analytics.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Execution History</h6>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="filterStatus">Filter by Status</label>
                    <select class="form-control" id="filterStatus">
                        <option value="">All Statuses</option>
                        <option value="success">Success</option>
                        <option value="failed">Failed</option>
                        <option value="pending">Pending</option>
                        <option value="running">Running</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="filterBot">Filter by Bot</label>
                    <select class="form-control" id="filterBot">
                        <option value="">All Bots</option>
                        @foreach($bots ?? [] as $bot)
                        <option value="{{ $bot->id }}">{{ $bot->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="filterDate">Filter by Date</label>
                    <input type="date" class="form-control" id="filterDate">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered" id="executionsTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Bot Name</th>
                            <th>Status</th>
                            <th>Started At</th>
                            <th>Completed At</th>
                            <th>Duration</th>
                            <th>Result</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($executions ?? [] as $execution)
                        <tr>
                            <td>{{ $execution->id }}</td>
                            <td>{{ $execution->bot_name ?? 'N/A' }}</td>
                            <td>
                                @if($execution->status == 'success')
                                    <span class="badge badge-success">Success</span>
                                @elseif($execution->status == 'failed')
                                    <span class="badge badge-danger">Failed</span>
                                @elseif($execution->status == 'running')
                                    <span class="badge badge-primary">Running</span>
                                @else
                                    <span class="badge badge-secondary">{{ ucfirst($execution->status) }}</span>
                                @endif
                            </td>
                            <td>{{ $execution->started_at ?? 'N/A' }}</td>
                            <td>{{ $execution->completed_at ?? 'N/A' }}</td>
                            <td>{{ $execution->duration ?? '0' }}s</td>
                            <td>{{ $execution->result ?? 'N/A' }}</td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="viewDetails({{ $execution->id }})">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">No execution records found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(isset($executions) && method_exists($executions, 'links'))
            <div class="mt-3">
                {{ $executions->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
