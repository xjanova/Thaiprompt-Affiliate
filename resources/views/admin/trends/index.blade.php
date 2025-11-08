@extends('layouts.admin')

@section('title', 'Viral Trend Detection')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Viral Trend Detection</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Trends</li>
    </ol>

    <!-- Stats Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <h4>{{ $stats['active_trends'] }}</h4>
                    <p class="mb-0">Active Trends</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <h4>{{ $stats['rising_trends'] }}</h4>
                    <p class="mb-0">Rising Trends</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <h4>{{ $stats['trending_keywords'] }}</h4>
                    <p class="mb-0">Trending Keywords</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white mb-4">
                <div class="card-body">
                    <h4>{{ $stats['new_trends_24h'] }}</h4>
                    <p class="mb-0">New Trends (24h)</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Rising Trends -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-fire me-1"></i>
            Rising Trends
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Keyword</th>
                            <th>Viral Score</th>
                            <th>Mentions</th>
                            <th>Velocity</th>
                            <th>Status</th>
                            <th>Age</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rising_trends as $trend)
                        <tr>
                            <td>{{ Str::limit($trend['title'], 50) }}</td>
                            <td><span class="badge bg-secondary">{{ $trend['keyword'] }}</span></td>
                            <td><strong>{{ number_format($trend['viral_score'], 2) }}</strong></td>
                            <td>{{ number_format($trend['total_mentions']) }}</td>
                            <td>{{ number_format($trend['velocity'], 2) }}/hr</td>
                            <td>
                                <span class="badge bg-{{ $trend['status'] === 'rising' ? 'success' : 'warning' }}">
                                    {{ ucfirst($trend['status']) }}
                                </span>
                            </td>
                            <td>{{ $trend['age_hours'] }}h</td>
                            <td>
                                <a href="{{ route('admin.trends.show', $trend['id']) }}" class="btn btn-sm btn-primary">
                                    View Details
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">No rising trends found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Top Keywords -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-hashtag me-1"></i>
            Top Keywords
            <a href="{{ route('admin.trends.keywords') }}" class="btn btn-sm btn-outline-primary float-end">View All</a>
        </div>
        <div class="card-body">
            <div class="row">
                @foreach($top_keywords as $keyword)
                <div class="col-md-4 mb-3">
                    <div class="card border-left-primary h-100">
                        <div class="card-body">
                            <h5 class="card-title">{{ $keyword['keyword'] }}</h5>
                            <p class="mb-1">
                                <strong>Score:</strong> {{ number_format($keyword['trend_score'], 2) }}
                            </p>
                            <p class="mb-1">
                                <strong>Frequency:</strong> {{ number_format($keyword['frequency']) }}
                            </p>
                            <p class="mb-0">
                                <strong>Growth:</strong>
                                <span class="text-{{ $keyword['growth_rate'] >= 0 ? 'success' : 'danger' }}">
                                    {{ $keyword['growth_rate'] >= 0 ? '+' : '' }}{{ number_format($keyword['growth_rate'], 2) }}%
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Trending Now -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-chart-line me-1"></i>
            Trending Now
        </div>
        <div class="card-body">
            <div class="row">
                @forelse($trending_now as $trend)
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ Str::limit($trend['title'], 60) }}</h5>
                            <p class="mb-2">
                                <span class="badge bg-secondary">{{ $trend['keyword'] }}</span>
                                <span class="badge bg-{{ $trend['status_color'] ?? 'primary' }}">{{ ucfirst($trend['status']) }}</span>
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Score: {{ number_format($trend['viral_score'], 2) }}</span>
                                <a href="{{ route('admin.trends.show', $trend['id']) }}" class="btn btn-sm btn-outline-primary">
                                    Details →
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12">
                    <p class="text-center text-muted">No trending items right now</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-cog me-1"></i>
            Quick Actions
        </div>
        <div class="card-body">
            <a href="{{ route('admin.trends.sources.index') }}" class="btn btn-primary me-2">
                <i class="fas fa-globe"></i> Manage Sources
            </a>
            <a href="{{ route('admin.trends.keywords') }}" class="btn btn-info me-2">
                <i class="fas fa-hashtag"></i> Browse Keywords
            </a>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Auto-refresh page every 5 minutes
    setTimeout(function() {
        location.reload();
    }, 300000);
</script>
@endsection
