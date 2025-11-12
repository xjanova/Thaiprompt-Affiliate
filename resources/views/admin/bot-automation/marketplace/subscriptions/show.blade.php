@extends('layouts.admin')

@section('title', 'Subscription Details')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Subscription Details</h1>
        <a href="{{ route('admin.bot-automation.marketplace.subscriptions.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Subscriptions
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Subscription Information</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">Subscription ID</h6>
                            <p class="font-weight-bold">{{ $subscription->id ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Status</h6>
                            <p>
                                @if(isset($subscription->status))
                                    @if($subscription->status == 'active')
                                        <span class="badge badge-success">Active</span>
                                    @elseif($subscription->status == 'expired')
                                        <span class="badge badge-danger">Expired</span>
                                    @elseif($subscription->status == 'cancelled')
                                        <span class="badge badge-secondary">Cancelled</span>
                                    @else
                                        <span class="badge badge-warning">{{ ucfirst($subscription->status) }}</span>
                                    @endif
                                @else
                                    N/A
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">User</h6>
                            <p class="font-weight-bold">{{ $subscription->user_name ?? 'N/A' }}</p>
                            <p class="small text-muted">{{ $subscription->user_email ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Listing</h6>
                            <p class="font-weight-bold">{{ $subscription->listing_title ?? 'N/A' }}</p>
                            <p class="small text-muted">{{ $subscription->listing_category ?? 'N/A' }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">Start Date</h6>
                            <p class="font-weight-bold">{{ isset($subscription->start_date) ? date('F d, Y', strtotime($subscription->start_date)) : 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">End Date</h6>
                            <p class="font-weight-bold">{{ isset($subscription->end_date) ? date('F d, Y', strtotime($subscription->end_date)) : 'N/A' }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">Amount</h6>
                            <p class="font-weight-bold text-success">${{ number_format($subscription->amount ?? 0, 2) }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Billing Cycle</h6>
                            <p class="font-weight-bold">{{ ucfirst($subscription->billing_cycle ?? 'monthly') }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="text-muted">Notes</h6>
                            <p>{{ $subscription->notes ?? 'No notes available' }}</p>
                        </div>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">Created: {{ isset($subscription->created_at) ? $subscription->created_at->format('M d, Y h:i A') : 'N/A' }}</small><br>
                            <small class="text-muted">Last Updated: {{ isset($subscription->updated_at) ? $subscription->updated_at->format('M d, Y h:i A') : 'N/A' }}</small>
                        </div>
                        <div>
                            @if(isset($subscription->status) && $subscription->status == 'active')
                            <button class="btn btn-warning" onclick="cancelSubscription()">
                                <i class="fas fa-ban mr-2"></i>Cancel Subscription
                            </button>
                            <button class="btn btn-success" onclick="renewSubscription()">
                                <i class="fas fa-redo mr-2"></i>Renew
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Payment History</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Transaction ID</th>
                                    <th>Method</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($paymentHistory ?? [] as $payment)
                                <tr>
                                    <td>{{ isset($payment->date) ? date('M d, Y', strtotime($payment->date)) : 'N/A' }}</td>
                                    <td>${{ number_format($payment->amount ?? 0, 2) }}</td>
                                    <td>
                                        @if($payment->status == 'completed')
                                            <span class="badge badge-success">Completed</span>
                                        @elseif($payment->status == 'failed')
                                            <span class="badge badge-danger">Failed</span>
                                        @else
                                            <span class="badge badge-warning">{{ ucfirst($payment->status) }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $payment->transaction_id ?? 'N/A' }}</td>
                                    <td>{{ ucfirst($payment->method ?? 'N/A') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No payment history available</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
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
                        <a href="#" class="list-group-item list-group-item-action">
                            <i class="fas fa-envelope mr-2"></i>Send Email to User
                        </a>
                        <a href="#" class="list-group-item list-group-item-action">
                            <i class="fas fa-file-invoice mr-2"></i>View Invoice
                        </a>
                        <a href="#" class="list-group-item list-group-item-action">
                            <i class="fas fa-download mr-2"></i>Download Receipt
                        </a>
                        <a href="#" class="list-group-item list-group-item-action">
                            <i class="fas fa-edit mr-2"></i>Edit Subscription
                        </a>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Usage Statistics</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <strong>Total API Calls:</strong> {{ $subscription->api_calls ?? '0' }}
                        </li>
                        <li class="mb-2">
                            <strong>Last Used:</strong> {{ isset($subscription->last_used) ? date('M d, Y', strtotime($subscription->last_used)) : 'Never' }}
                        </li>
                        <li class="mb-2">
                            <strong>Active Bots:</strong> {{ $subscription->active_bots ?? '0' }}
                        </li>
                        <li class="mb-2">
                            <strong>Storage Used:</strong> {{ $subscription->storage_used ?? '0' }} MB
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function cancelSubscription() {
    if (confirm('Are you sure you want to cancel this subscription? This action cannot be undone.')) {
        // Submit cancellation request
        console.log('Cancelling subscription');
    }
}

function renewSubscription() {
    if (confirm('Are you sure you want to renew this subscription?')) {
        // Submit renewal request
        console.log('Renewing subscription');
    }
}
</script>
@endsection
