@extends('layouts.admin-v3')

@section('title', 'Debug Fortune -> SmsChecker')

@section('content')
<div class="container-fluid py-4">
    <h2 class="mb-4">Fortune Reading -> SmsChecker Debug</h2>

    {{-- Status Counts --}}
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Fortune Reading Status Counts</h5>
        </div>
        <div class="card-body">
            <table class="table table-sm table-bordered">
                <thead><tr><th>Status</th><th>Count</th><th>Visible in App?</th></tr></thead>
                <tbody>
                @foreach($statusCounts as $status => $count)
                    <tr>
                        <td><code>{{ $status }}</code></td>
                        <td>{{ $count }}</td>
                        <td>
                            @if(in_array($status, ['pending_payment', 'paid', 'completed']))
                                <span class="badge bg-success">Yes</span>
                            @else
                                <span class="badge bg-secondary">No</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Validation Issues --}}
    @if(count($validationIssues) > 0)
    <div class="card mb-4 border-danger">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0">Validation Issues ({{ count($validationIssues) }})</h5>
        </div>
        <div class="card-body">
            @foreach($validationIssues as $issue)
                <div class="alert alert-warning py-2">
                    <strong>Fortune #{{ $issue['fortune_reading_id'] }}</strong>
                    <ul class="mb-0 mt-1">
                        @foreach($issue['issues'] as $i)
                            <li>{{ $i }}</li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Active Devices --}}
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">Active Devices</h5>
        </div>
        <div class="card-body">
            @if($devices->isEmpty())
                <div class="alert alert-warning">No active devices found!</div>
            @else
                <table class="table table-sm table-bordered">
                    <thead><tr><th>Device ID</th><th>Name</th><th>Last Active</th><th>FCM Token</th></tr></thead>
                    <tbody>
                    @foreach($devices as $device)
                        <tr>
                            <td><code>{{ $device['device_id'] }}</code></td>
                            <td>{{ $device['device_name'] }}</td>
                            <td>{{ $device['last_active_at'] ?? 'Never' }}</td>
                            <td>
                                @if($device['has_fcm_token'])
                                    <span class="badge bg-success">Yes</span>
                                @else
                                    <span class="badge bg-danger">No</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    {{-- Transformed Orders --}}
    <div class="card mb-4">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Transformed Orders - {{ count($transformed) }} items</h5>
        </div>
        <div class="card-body">
            @if(empty($transformed))
                <div class="alert alert-info">No eligible fortune readings found</div>
            @else
                @foreach($transformed as $idx => $order)
                    <div class="card mb-3">
                        <div class="card-header py-2">
                            <strong>#{{ $idx + 1 }}</strong> |
                            ID: <code>{{ $order['id'] }}</code> |
                            Status: <code>{{ $order['approval_status'] }}</code> |
                            Amount: <strong>{{ number_format($order['order_details_json']['amount'] ?? 0, 2) }} THB</strong>
                        </div>
                        <div class="card-body py-2">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>API Response JSON:</h6>
                                    <pre class="bg-light p-2 small" style="max-height:300px;overflow-y:auto">{{ json_encode($order, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </div>
                                <div class="col-md-6">
                                    <h6>Debug Info:</h6>
                                    <pre class="bg-light p-2 small" style="max-height:300px;overflow-y:auto">{{ json_encode($order['_debug'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    {{-- Raw Fortune Readings --}}
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">Raw Fortune Readings (DB data)</h5>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-sm table-bordered table-striped small">
                <thead>
                    <tr>
                        <th>ID</th><th>Status</th><th>bill_reference</th><th>bill_amount</th>
                        <th>fortune_type</th><th>customer</th><th>paid_at</th><th>created_at</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($fortuneReadings as $r)
                    <tr>
                        <td>{{ $r->id }}</td>
                        <td><code>{{ $r->conversation_status }}</code></td>
                        <td>{{ $r->bill_reference ?? 'NULL' }}</td>
                        <td>{{ $r->bill_amount ?? 'NULL' }}</td>
                        <td>{{ $r->fortune_type ?? 'NULL' }}</td>
                        <td>{{ $r->customer_name ?? 'NULL' }}</td>
                        <td>{{ $r->paid_at ?? 'NULL' }}</td>
                        <td>{{ $r->created_at }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection