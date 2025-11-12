@extends('layouts.admin')

@section('title', 'Master HD Wallets')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Master HD Wallets</h1>
        <button class="btn btn-primary">
            <i class="fas fa-plus mr-2"></i>Create Master Wallet
        </button>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Master Wallet Management</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Wallet Name</th>
                            <th>Address</th>
                            <th>Balance</th>
                            <th>Child Wallets</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($masterWallets ?? [] as $wallet)
                        <tr>
                            <td>{{ $wallet->id }}</td>
                            <td>{{ $wallet->name ?? 'N/A' }}</td>
                            <td><code>{{ Str::limit($wallet->address ?? 'N/A', 20) }}</code></td>
                            <td>{{ $wallet->balance ?? '0' }} {{ $wallet->currency ?? 'BTC' }}</td>
                            <td>{{ $wallet->child_wallets_count ?? '0' }}</td>
                            <td>
                                <span class="badge badge-{{ ($wallet->status ?? 'active') == 'active' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($wallet->status ?? 'Active') }}
                                </span>
                            </td>
                            <td>{{ isset($wallet->created_at) ? $wallet->created_at->format('M d, Y') : 'N/A' }}</td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="viewWallet({{ $wallet->id }})">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">No master wallets found</td>
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
