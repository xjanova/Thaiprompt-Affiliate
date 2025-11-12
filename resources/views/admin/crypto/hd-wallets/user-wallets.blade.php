@extends('layouts.admin')

@section('title', 'User HD Wallets')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">User HD Wallets</h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">User Wallet Management</h6>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" placeholder="Search user wallets..." id="searchWallets">
                </div>
                <div class="col-md-3">
                    <select class="form-control" id="filterCurrency">
                        <option value="">All Currencies</option>
                        <option value="BTC">Bitcoin (BTC)</option>
                        <option value="ETH">Ethereum (ETH)</option>
                        <option value="USDT">Tether (USDT)</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Wallet Address</th>
                            <th>Currency</th>
                            <th>Balance</th>
                            <th>Transactions</th>
                            <th>Last Activity</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($userWallets ?? [] as $wallet)
                        <tr>
                            <td>{{ $wallet->user_name ?? 'N/A' }}</td>
                            <td><code>{{ Str::limit($wallet->address ?? 'N/A', 20) }}</code></td>
                            <td>{{ $wallet->currency ?? 'BTC' }}</td>
                            <td>{{ $wallet->balance ?? '0' }}</td>
                            <td>{{ $wallet->transactions_count ?? '0' }}</td>
                            <td>{{ isset($wallet->last_activity) ? date('M d, Y', strtotime($wallet->last_activity)) : 'Never' }}</td>
                            <td>
                                <span class="badge badge-{{ ($wallet->status ?? 'active') == 'active' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($wallet->status ?? 'Active') }}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="viewWallet({{ $wallet->id }})">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">No user wallets found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function viewWallet(id) {
    console.log('Viewing wallet:', id);
}
</script>
@endsection
