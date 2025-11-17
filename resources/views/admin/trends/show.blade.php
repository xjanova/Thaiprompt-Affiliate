@extends('layouts.admin-v3')

@section('title', 'Trend Details - ' . $trend->title)

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">{{ $trend->title }}</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.trends.index') }}">Trends</a></li>
        <li class="breadcrumb-item active">{{ Str::limit($trend->title, 30) }}</li>
    </ol>

    <div class="row">
        <!-- Left Column - Trend Info -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-info-circle"></i> Trend Information
                </div>
                <div class="card-body">
                    <h5>{{ $trend->keyword->keyword }}</h5>
                    <hr>
                    <p><strong>Status:</strong>
                        <span class="badge bg-{{ $trend->status_color }}">{{ ucfirst($trend->status) }}</span>
                    </p>
                    <p><strong>Viral Score:</strong> {{ number_format($trend->viral_score, 2) }}</p>
                    <p><strong>Total Mentions:</strong> {{ number_format($trend->total_mentions) }}</p>
                    <p><strong>Total Engagement:</strong> {{ number_format($trend->total_engagement) }}</p>
                    <p><strong>Velocity:</strong> {{ number_format($trend->velocity, 2) }} mentions/hour</p>
                    <p><strong>Started:</strong> {{ $trend->started_at->diffForHumans() }}</p>
                    @if($trend->peak_at)
                    <p><strong>Peaked:</strong> {{ $trend->peak_at->diffForHumans() }}</p>
                    @endif
                    <p><strong>Age:</strong> {{ $trend->age_in_hours }} hours</p>
                </div>
            </div>

            <!-- Generate Content -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-magic"></i> Generate Content with AI
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary" onclick="generateContent('blog')">
                            <i class="fas fa-newspaper"></i> Blog Post
                        </button>
                        <button class="btn btn-info" onclick="generateContent('social')">
                            <i class="fas fa-share-alt"></i> Social Media
                        </button>
                        <button class="btn btn-warning" onclick="generateContent('summary')">
                            <i class="fas fa-file-alt"></i> Summary
                        </button>
                        <button class="btn btn-success" onclick="generateContent('newsletter')">
                            <i class="fas fa-envelope"></i> Newsletter
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Charts & Analytics -->
        <div class="col-lg-8">
            <!-- Mention Chart -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-line"></i> Mentions Over Time
                </div>
                <div class="card-body">
                    <canvas id="mentionsChart" height="100"></canvas>
                </div>
            </div>

            <!-- Engagement Chart -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-bar"></i> Engagement Over Time
                </div>
                <div class="card-body">
                    <canvas id="engagementChart" height="100"></canvas>
                </div>
            </div>

            <!-- Analytics Data -->
            @if(!empty($trend->analytics))
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-analytics"></i> Top Posts
                </div>
                <div class="card-body">
                    @if(!empty($trend->analytics['top_posts']))
                        <div class="list-group">
                            @foreach($trend->analytics['top_posts'] as $post)
                            <div class="list-group-item">
                                <h6>{{ $post['title'] ?? 'Untitled' }}</h6>
                                <p class="mb-1 small text-muted">
                                    Viral Score: {{ $post['viral_score'] ?? 0 }} |
                                    Engagement: {{ $post['engagement'] ?? 0 }}
                                </p>
                                @if(!empty($post['url']))
                                <a href="{{ $post['url'] }}" target="_blank" class="small">View Post →</a>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted">No top posts data available</p>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Generated Content Modal -->
<div class="modal fade" id="contentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Generated Content</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="generatedContent" class="p-3">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Generating content with AI...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="copyContent()">Copy to Clipboard</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const chartData = @json($chartData);

    // Mentions Chart
    const mentionsCtx = document.getElementById('mentionsChart').getContext('2d');
    new Chart(mentionsCtx, {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [{
                label: 'Mentions',
                data: chartData.mentions,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Engagement Chart
    const engagementCtx = document.getElementById('engagementChart').getContext('2d');
    new Chart(engagementCtx, {
        type: 'bar',
        data: {
            labels: chartData.labels,
            datasets: [{
                label: 'Engagement',
                data: chartData.engagement,
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgb(54, 162, 235)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Generate Content
    function generateContent(type) {
        const modal = new bootstrap.Modal(document.getElementById('contentModal'));
        modal.show();

        fetch('{{ route("admin.trends.generate-content", $trend->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ content_type: type })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let contentHtml = '<div class="alert alert-success">Content generated successfully!</div>';

                if (type === 'blog') {
                    contentHtml += `
                        <h4>${data.content.title}</h4>
                        <p class="text-muted">${data.content.excerpt}</p>
                        <hr>
                        <div>${data.content.content.replace(/\n/g, '<br>')}</div>
                        <hr>
                        <p><strong>Tags:</strong> ${data.content.tags.join(', ')}</p>
                    `;
                } else if (type === 'social') {
                    contentHtml += '<h5>Social Media Posts</h5>';
                    Object.entries(data.content).forEach(([platform, post]) => {
                        contentHtml += `
                            <div class="card mb-3">
                                <div class="card-header">${platform.toUpperCase()}</div>
                                <div class="card-body">
                                    <p>${post.text}</p>
                                    <p class="small text-muted">${post.hashtags.join(' ')}</p>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    contentHtml += `<pre class="bg-light p-3">${JSON.stringify(data.content, null, 2)}</pre>`;
                }

                document.getElementById('generatedContent').innerHTML = contentHtml;
            } else {
                document.getElementById('generatedContent').innerHTML =
                    `<div class="alert alert-danger">Error: ${data.error || 'Failed to generate content'}</div>`;
            }
        })
        .catch(error => {
            document.getElementById('generatedContent').innerHTML =
                `<div class="alert alert-danger">Error: ${error.message}</div>`;
        });
    }

    function copyContent() {
        const content = document.getElementById('generatedContent').innerText;
        navigator.clipboard.writeText(content).then(() => {
            alert('Content copied to clipboard!');
        });
    }
</script>
@endsection
