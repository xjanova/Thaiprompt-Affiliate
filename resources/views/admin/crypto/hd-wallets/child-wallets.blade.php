@extends('layouts.admin')

@section('title', 'Child HD Wallets')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Child HD Wallets</h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Child Wallet Management</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Parent Wallet</th>
                            <th>Address</th>
                            <th>Balance</th>
                            <th>Derivation Path</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($childWallets ?? [] as $wallet)
                        <tr>
                            <td>{{ $wallet->id }}</td>
                            <td>{{ $wallet->parent_name ?? 'N/A' }}</td>
                            <td><code>{{ Str::limit($wallet->address ?? 'N/A', 20) }}</code></td>
                            <td>{{ $wallet->balance ?? '0' }} {{ $wallet->currency ?? 'BTC' }}</td>
                            <td><code>{{ $wallet->derivation_path ?? 'N/A' }}</code></td>
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
                            <td colspan="8" class="text-center text-muted">No child wallets found</td>
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
