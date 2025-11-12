@extends('layouts.admin')

@section('title', 'Trending Keywords')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Trending Keywords</h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Keyword Trends</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Keyword</th>
                            <th>Search Volume</th>
                            <th>Trend</th>
                            <th>Category</th>
                            <th>Last Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($keywords ?? [] as $keyword)
                        <tr>
                            <td>{{ $keyword->keyword ?? 'N/A' }}</td>
                            <td>{{ number_format($keyword->search_volume ?? 0) }}</td>
                            <td>
                                <span class="badge badge-{{ ($keyword->trend ?? 'up') == 'up' ? 'success' : 'danger' }}">
                                    <i class="fas fa-arrow-{{ ($keyword->trend ?? 'up') == 'up' ? 'up' : 'down' }} mr-1"></i>
                                    {{ $keyword->change ?? '0' }}%
                                </span>
                            </td>
                            <td>{{ $keyword->category ?? 'N/A' }}</td>
                            <td>{{ isset($keyword->updated_at) ? $keyword->updated_at->diffForHumans() : 'N/A' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">No trending keywords found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
