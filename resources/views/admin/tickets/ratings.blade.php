@extends('layouts.admin')

@section('title', 'Ticket Ratings')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Ticket Ratings & Feedback</h1>
        <a href="{{ route('admin.tickets.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>กลับหน้าหลัก
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Average Rating</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['average_rating'] ?? 0, 1) }} / 5.0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-star fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Ratings</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_ratings'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-comments fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Response Speed</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['average_response_speed'] ?? 0, 1) }} / 5.0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tachometer-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Solution Quality</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['average_solution_quality'] ?? 0, 1) }} / 5.0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ratings List -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Recent Ratings</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Ticket</th>
                            <th>User</th>
                            <th>Rating</th>
                            <th>Response Speed</th>
                            <th>Solution Quality</th>
                            <th>Staff Friendliness</th>
                            <th>Feedback</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ratings as $rating)
                        <tr>
                            <td>
                                <a href="{{ route('admin.tickets.show', $rating->ticket_id) }}" class="text-decoration-none">
                                    {{ $rating->ticket->ticket_number ?? 'N/A' }}
                                </a>
                            </td>
                            <td>{{ $rating->user->name ?? 'N/A' }}</td>
                            <td>
                                <div class="text-warning">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="fas fa-star{{ $i <= $rating->rating ? '' : '-o' }}"></i>
                                    @endfor
                                    <span class="ml-2">{{ $rating->rating }}/5</span>
                                </div>
                            </td>
                            <td>{{ $rating->response_speed ?? '-' }}/5</td>
                            <td>{{ $rating->solution_quality ?? '-' }}/5</td>
                            <td>{{ $rating->staff_friendliness ?? '-' }}/5</td>
                            <td>{{ Str::limit($rating->feedback ?? 'No feedback', 50) }}</td>
                            <td>{{ $rating->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">No ratings yet</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-3">
                {{ $ratings->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
