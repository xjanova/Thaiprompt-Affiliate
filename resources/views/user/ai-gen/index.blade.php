@extends('layouts.user')

@section('title', 'AI Gen - Create Amazing Images & Videos')

@section('content')
<div class="ai-gen-container">
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h1 class="hero-title">
                        Create <span class="gradient-text">Stunning</span><br>
                        Images & Videos with AI
                    </h1>
                    <p class="hero-subtitle">Transform your ideas into reality with powerful AI generation</p>
                    <button class="btn btn-primary btn-lg btn-glow" onclick="showCreateModal()">
                        <i class="fas fa-magic"></i> Start Creating Now
                    </button>
                </div>
                <div class="col-lg-6">
                    <div class="hero-image-grid">
                        <div class="grid-item" data-aos="fade-up" data-aos-delay="100">
                            <img src="/images/ai-gen-sample-1.jpg" alt="AI Generated">
                        </div>
                        <div class="grid-item" data-aos="fade-up" data-aos-delay="200">
                            <img src="/images/ai-gen-sample-2.jpg" alt="AI Generated">
                        </div>
                        <div class="grid-item" data-aos="fade-up" data-aos-delay="300">
                            <img src="/images/ai-gen-sample-3.jpg" alt="AI Generated">
                        </div>
                        <div class="grid-item" data-aos="fade-up" data-aos-delay="400">
                            <img src="/images/ai-gen-sample-4.jpg" alt="AI Generated">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Bar -->
    <div class="stats-bar">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3 col-6 mb-3 mb-md-0">
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-image"></i>
                        </div>
                        <div class="stat-value" id="stat-images">{{ $stats['remaining_images'] ?? 0 }}</div>
                        <div class="stat-label">Images Left</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3 mb-md-0">
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-video"></i>
                        </div>
                        <div class="stat-value" id="stat-videos">{{ $stats['remaining_videos'] ?? 0 }}</div>
                        <div class="stat-label">Videos Left</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-value" id="stat-generated">{{ $stats['total_generated'] ?? 0 }}</div>
                        <div class="stat-label">Created</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-crown"></i>
                        </div>
                        <div class="stat-value">{{ $package_name ?? 'Free' }}</div>
                        <div class="stat-label">Plan</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mt-5">
        <!-- Tabs -->
        <ul class="nav nav-pills nav-fill ai-gen-tabs mb-4" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-toggle="tab" href="#my-creations">
                    <i class="fas fa-images"></i> My Creations
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#explore">
                    <i class="fas fa-compass"></i> Explore
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#packages">
                    <i class="fas fa-box"></i> Packages
                </a>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- My Creations Tab -->
            <div class="tab-pane fade show active" id="my-creations">
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="btn-group" role="group">
                            <button class="btn btn-outline-primary" data-filter="all">All</button>
                            <button class="btn btn-outline-primary" data-filter="image">Images</button>
                            <button class="btn btn-outline-primary" data-filter="video">Videos</button>
                            <button class="btn btn-outline-primary" data-filter="favorite">Favorites</button>
                        </div>
                    </div>
                    <div class="col-md-4 text-right">
                        <button class="btn btn-primary btn-glow" onclick="showCreateModal()">
                            <i class="fas fa-plus"></i> Create New
                        </button>
                    </div>
                </div>

                <div class="generations-grid" id="generations-grid">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="text-muted mt-3">Loading your creations...</p>
                    </div>
                </div>
            </div>

            <!-- Explore Tab -->
            <div class="tab-pane fade" id="explore">
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="btn-group" role="group">
                            <button class="btn btn-outline-primary active" data-explore-filter="all">All</button>
                            <button class="btn btn-outline-primary" data-explore-filter="image">Images</button>
                            <button class="btn btn-outline-primary" data-explore-filter="video">Videos</button>
                            <button class="btn btn-outline-primary" data-explore-filter="popular">Popular</button>
                        </div>
                    </div>
                </div>

                <div class="generations-grid" id="explore-grid">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="text-muted mt-3">Loading trending creations...</p>
                    </div>
                </div>
            </div>

            <!-- Packages Tab -->
            <div class="tab-pane fade" id="packages">
                <div class="row" id="packages-grid">
                    <div class="col-12 text-center py-5">
                        <div class="spinner-border text-primary"></div>
                        <p class="text-muted mt-3">Loading packages...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">
                    <i class="fas fa-magic text-primary"></i> Create with AI
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="createForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>What do you want to create? *</label>
                        <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                            <label class="btn btn-outline-primary active">
                                <input type="radio" name="type" value="image" checked>
                                <i class="fas fa-image"></i> Image
                            </label>
                            <label class="btn btn-outline-primary">
                                <input type="radio" name="type" value="video">
                                <i class="fas fa-video"></i> Video
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>AI Provider *</label>
                        <select class="form-control" name="provider" id="provider-select" required>
                            <option value="">Loading providers...</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Describe what you want *</label>
                        <textarea class="form-control" name="prompt" rows="4"
                            placeholder="E.g., A beautiful sunset over the ocean with dolphins jumping"
                            required></textarea>
                        <small class="form-text text-muted">
                            <i class="fas fa-lightbulb"></i> Tip: Be specific and descriptive for better results
                        </small>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Style</label>
                                <select class="form-control" name="style">
                                    <option value="realistic">Realistic</option>
                                    <option value="artistic">Artistic</option>
                                    <option value="anime">Anime</option>
                                    <option value="cartoon">Cartoon</option>
                                    <option value="3d">3D Render</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6" id="size-group">
                            <div class="form-group">
                                <label>Size</label>
                                <select class="form-control" name="size">
                                    <option value="1024x1024">Square (1024x1024)</option>
                                    <option value="1024x768">Landscape (1024x768)</option>
                                    <option value="768x1024">Portrait (768x1024)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <span id="credits-info">Loading...</span>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-glow">
                        <i class="fas fa-magic"></i> Generate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <div>
                    <h5 class="modal-title mb-1" id="view-title">Untitled</h5>
                    <small class="text-muted" id="view-date"></small>
                </div>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="generation-preview" id="generation-preview">
                            <img src="" alt="" class="img-fluid rounded" id="preview-image">
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="generation-details">
                            <h6>Details</h6>
                            <dl class="row">
                                <dt class="col-4">Type</dt>
                                <dd class="col-8" id="detail-type"></dd>
                                <dt class="col-4">Provider</dt>
                                <dd class="col-8" id="detail-provider"></dd>
                                <dt class="col-4">Status</dt>
                                <dd class="col-8" id="detail-status"></dd>
                            </dl>

                            <h6>Prompt</h6>
                            <p class="text-muted" id="detail-prompt"></p>

                            <div class="mt-4">
                                <button class="btn btn-primary btn-block" onclick="downloadGeneration()">
                                    <i class="fas fa-download"></i> Download
                                </button>
                                <button class="btn btn-outline-warning btn-block" onclick="toggleFavorite()">
                                    <i class="fas fa-heart"></i> <span id="favorite-text">Add to Favorites</span>
                                </button>
                                <button class="btn btn-outline-danger btn-block" onclick="deleteGeneration()">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
<style>
    .ai-gen-container {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding-bottom: 60px;
    }

    .hero-section {
        padding: 80px 0 60px;
        color: white;
    }

    .hero-title {
        font-size: 3.5rem;
        font-weight: 800;
        line-height: 1.2;
        margin-bottom: 20px;
    }

    .gradient-text {
        background: linear-gradient(90deg, #ffd89b 0%, #19547b 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .hero-subtitle {
        font-size: 1.25rem;
        margin-bottom: 30px;
        opacity: 0.9;
    }

    .btn-glow {
        box-shadow: 0 0 20px rgba(255, 255, 255, 0.3);
        animation: glow 2s ease-in-out infinite;
    }

    @keyframes glow {
        0%, 100% {
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.3);
        }
        50% {
            box-shadow: 0 0 30px rgba(255, 255, 255, 0.5);
        }
    }

    .hero-image-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }

    .grid-item {
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        transition: transform 0.3s ease;
    }

    .grid-item:hover {
        transform: scale(1.05);
    }

    .grid-item img {
        width: 100%;
        height: 200px;
        object-fit: cover;
    }

    .stats-bar {
        background: white;
        padding: 30px 0;
        box-shadow: 0 -5px 30px rgba(0, 0, 0, 0.1);
        border-radius: 30px 30px 0 0;
        margin-top: -30px;
        position: relative;
        z-index: 1;
    }

    .stat-item {
        padding: 15px;
    }

    .stat-icon {
        font-size: 2rem;
        color: #667eea;
        margin-bottom: 10px;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: #333;
    }

    .stat-label {
        font-size: 0.9rem;
        color: #666;
    }

    .ai-gen-tabs .nav-link {
        border-radius: 50px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .ai-gen-tabs .nav-link.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white !important;
    }

    .generations-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 25px;
        min-height: 400px;
    }

    .generation-card {
        border-radius: 15px;
        overflow: hidden;
        background: white;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .generation-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
    }

    .generation-card img {
        width: 100%;
        height: 250px;
        object-fit: cover;
    }

    .generation-info {
        padding: 15px;
    }

    .category-card {
        background: white;
        border-radius: 15px;
        padding: 30px;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    }

    .category-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
    }

    .category-icon {
        font-size: 3rem;
        color: #667eea;
        margin-bottom: 15px;
    }

    .package-card {
        background: white;
        border-radius: 20px;
        padding: 40px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .package-card.popular::before {
        content: 'POPULAR';
        position: absolute;
        top: 20px;
        right: -35px;
        background: #ffd700;
        color: #333;
        padding: 5px 40px;
        transform: rotate(45deg);
        font-weight: 700;
        font-size: 0.8rem;
    }

    .package-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
    }

    .package-price {
        font-size: 3rem;
        font-weight: 800;
        color: #667eea;
    }

    .modal-content {
        border-radius: 20px;
        border: none;
    }

    .generation-preview {
        background: #f8f9fa;
        border-radius: 15px;
        padding: 20px;
        min-height: 400px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
    AOS.init();

    let currentGeneration = null;
    let availableProviders = [];

    async function loadDashboardData() {
        try {
            const response = await fetch('/api/v1/ai-gen/dashboard', {
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('token'),
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                updateStats(data.data);
                availableProviders = data.data.providers || [];
                updateProviderSelect();
            }
        } catch (error) {
            console.error('Error loading dashboard:', error);
        }
    }

    function updateStats(data) {
        // Update stats from dashboard data
        const quota = data.quota || {};
        const subscription = data.subscription || {};

        // Update remaining credits display
        if (subscription.has_subscription) {
            document.getElementById('stat-images').textContent = subscription.image_credits?.remaining || 0;
            document.getElementById('stat-videos').textContent = subscription.video_credits?.remaining || 0;
        } else {
            document.getElementById('stat-images').textContent = quota.image?.daily || 0;
            document.getElementById('stat-videos').textContent = quota.video?.daily || 0;
        }

        document.getElementById('stat-generated').textContent = data.stats?.total_generations || 0;
    }

    async function loadGenerations(filter = 'all') {
        const grid = document.getElementById('generations-grid');
        grid.innerHTML = '<div class="col-12 text-center py-5"><div class="spinner-border text-primary"></div></div>';

        try {
            const params = new URLSearchParams();
            if (filter !== 'all') {
                if (filter === 'image' || filter === 'video') {
                    params.append('type', filter);
                } else if (filter === 'favorite') {
                    params.append('is_favorite', 'true');
                }
            }

            const response = await fetch('/api/v1/ai-gen/generations?' + params, {
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('token'),
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success && data.data.data.length > 0) {
                renderGenerations(data.data.data);
            } else {
                grid.innerHTML = `
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-image fa-4x text-muted mb-3"></i>
                        <h5>No creations yet</h5>
                        <p class="text-muted">Start creating amazing content with AI!</p>
                        <button class="btn btn-primary btn-glow" onclick="showCreateModal()">
                            <i class="fas fa-magic"></i> Create Now
                        </button>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading generations:', error);
        }
    }

    function renderGenerations(generations) {
        const grid = document.getElementById('generations-grid');

        grid.innerHTML = generations.map(gen => `
            <div class="generation-card" onclick="viewGeneration(${gen.id})">
                <img src="${gen.file_url || gen.thumbnail_url || '/images/placeholder.jpg'}" alt="${gen.prompt}">
                <div class="generation-info">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge badge-${gen.type === 'image' ? 'primary' : 'success'}">
                            ${gen.type}
                        </span>
                        <span class="badge badge-${getStatusColor(gen.status)}">
                            ${gen.status}
                        </span>
                    </div>
                    <p class="mb-0 text-muted small">${gen.prompt.substring(0, 60)}...</p>
                    <small class="text-muted">${formatDate(gen.created_at)}</small>
                </div>
            </div>
        `).join('');
    }

    function showCreateModal() {
        $('#createModal').modal('show');
        loadDashboardData();
    }

    function updateProviderSelect() {
        const select = document.getElementById('provider-select');
        select.innerHTML = availableProviders.map(provider => `
            <option value="${provider.slug}">${provider.name} (${provider.type})</option>
        `).join('');
    }

    function getStatusColor(status) {
        const colors = {
            'completed': 'success',
            'processing': 'info',
            'pending': 'warning',
            'failed': 'danger'
        };
        return colors[status] || 'secondary';
    }

    function formatDate(dateString) {
        return new Date(dateString).toLocaleDateString();
    }

    // View generation details
    async function viewGeneration(generationId) {
        try {
            const response = await fetch(`/api/v1/ai-gen/generations/${generationId}`, {
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('token'),
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                const gen = data.data;
                currentGeneration = gen;

                // Update modal content
                document.getElementById('view-title').textContent = gen.prompt.substring(0, 50) + '...';
                document.getElementById('view-date').textContent = formatDate(gen.created_at);
                document.getElementById('preview-image').src = gen.file_url || gen.thumbnail_url || '/images/placeholder.jpg';
                document.getElementById('detail-type').textContent = gen.type;
                document.getElementById('detail-provider').textContent = gen.provider?.name || 'N/A';
                document.getElementById('detail-status').innerHTML = `<span class="badge badge-${getStatusColor(gen.status)}">${gen.status}</span>`;
                document.getElementById('detail-prompt').textContent = gen.prompt;
                document.getElementById('favorite-text').textContent = gen.is_favorite ? 'Remove from Favorites' : 'Add to Favorites';

                // Show modal
                $('#viewModal').modal('show');
            }
        } catch (error) {
            console.error('Error loading generation:', error);
            alert('Failed to load generation details');
        }
    }

    // Download generation
    function downloadGeneration() {
        if (!currentGeneration || !currentGeneration.file_url) {
            alert('No file available for download');
            return;
        }

        const link = document.createElement('a');
        link.href = currentGeneration.file_url;
        link.download = `ai-gen-${currentGeneration.id}.${currentGeneration.type === 'video' ? 'mp4' : 'png'}`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Toggle favorite
    async function toggleFavorite() {
        if (!currentGeneration) return;

        try {
            const response = await fetch(`/api/v1/ai-gen/generations/${currentGeneration.id}/favorite`, {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('token'),
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                currentGeneration.is_favorite = data.is_favorite;
                document.getElementById('favorite-text').textContent = data.is_favorite ? 'Remove from Favorites' : 'Add to Favorites';
                loadGenerations(); // Reload to update the grid
            }
        } catch (error) {
            console.error('Error toggling favorite:', error);
            alert('Failed to update favorite status');
        }
    }

    // Delete generation
    async function deleteGeneration() {
        if (!currentGeneration) return;

        if (!confirm('Are you sure you want to delete this generation?')) {
            return;
        }

        try {
            const response = await fetch(`/api/v1/ai-gen/generations/${currentGeneration.id}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('token'),
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                $('#viewModal').modal('hide');
                loadGenerations(); // Reload the grid
                alert('Generation deleted successfully');
            }
        } catch (error) {
            console.error('Error deleting generation:', error);
            alert('Failed to delete generation');
        }
    }

    // Poll generation status
    async function pollGenerationStatus(generationId, maxAttempts = 60) {
        let attempts = 0;

        const poll = async () => {
            if (attempts >= maxAttempts) {
                console.log('Stopped polling for generation', generationId);
                return;
            }

            attempts++;

            try {
                const response = await fetch(`/api/v1/ai-gen/generations/${generationId}/status`, {
                    headers: {
                        'Authorization': 'Bearer ' + localStorage.getItem('token'),
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success && data.data) {
                    const status = data.data.status;

                    if (status === 'completed' || status === 'failed') {
                        // Reload generations to show the completed/failed item
                        loadGenerations();
                        return;
                    }

                    // Continue polling
                    setTimeout(poll, 5000); // Poll every 5 seconds
                }
            } catch (error) {
                console.error('Error polling status:', error);
            }
        };

        poll();
    }

    // Load packages
    async function loadPackages() {
        const grid = document.getElementById('packages-grid');
        grid.innerHTML = '<div class="col-12 text-center py-5"><div class="spinner-border text-primary"></div></div>';

        try {
            const response = await fetch('/api/v1/ai-gen/packages', {
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('token'),
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success && data.data.length > 0) {
                grid.innerHTML = data.data.map((pkg, index) => `
                    <div class="col-lg-4 mb-4">
                        <div class="package-card ${index === 1 ? 'popular' : ''}">
                            <h3>${pkg.name}</h3>
                            <p class="text-muted">${pkg.description || ''}</p>
                            <div class="package-price">${pkg.price > 0 ? pkg.currency + ' ' + pkg.price : 'Free'}</div>
                            <ul class="list-unstyled mt-4">
                                <li><i class="fas fa-check text-success"></i> ${pkg.image_credits} Image Credits</li>
                                <li><i class="fas fa-check text-success"></i> ${pkg.video_credits} Video Credits</li>
                                <li><i class="fas fa-check text-success"></i> ${pkg.duration_days ? pkg.duration_days + ' Days' : 'Unlimited'}</li>
                            </ul>
                            <button class="btn btn-primary btn-block mt-4" onclick="purchasePackage(${pkg.id}, '${pkg.name}', ${pkg.price})">
                                ${pkg.price > 0 ? 'Purchase Now' : 'Activate Free Plan'}
                            </button>
                        </div>
                    </div>
                `).join('');
            } else {
                grid.innerHTML = '<div class="col-12 text-center py-5"><p class="text-muted">No packages available</p></div>';
            }
        } catch (error) {
            console.error('Error loading packages:', error);
            grid.innerHTML = '<div class="col-12 text-center py-5"><p class="text-danger">Failed to load packages</p></div>';
        }
    }

    // Purchase package
    async function purchasePackage(packageId, packageName, price) {
        if (!confirm(`Purchase ${packageName} for ${price > 0 ? price + ' THB' : 'FREE'}?`)) {
            return;
        }

        try {
            const response = await fetch(`/api/v1/ai-gen/packages/${packageId}/purchase`, {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('token'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    payment_method: 'credit_card' // Default for demo
                })
            });

            const data = await response.json();

            if (data.success) {
                alert(data.message || 'Package purchased successfully!');
                loadDashboardData(); // Reload stats
                loadPackages(); // Reload packages
            } else {
                alert('Error: ' + (data.error || 'Failed to purchase package'));
            }
        } catch (error) {
            console.error('Error purchasing package:', error);
            alert('An error occurred. Please try again.');
        }
    }

    // Load explore content
    async function loadExplore(filter = 'all') {
        const grid = document.getElementById('explore-grid');
        grid.innerHTML = '<div class="col-12 text-center py-5"><div class="spinner-border text-primary"></div></div>';

        try {
            const params = new URLSearchParams();
            params.append('status', 'completed'); // Only show completed generations
            params.append('per_page', '24');

            if (filter !== 'all') {
                if (filter === 'image' || filter === 'video') {
                    params.append('type', filter);
                } else if (filter === 'popular') {
                    params.append('is_favorite', 'true');
                }
            }

            // For explore, we fetch public/completed generations from all users
            // Note: This would need a backend endpoint that returns public generations
            // For now, showing user's own completed generations as demo
            const response = await fetch('/api/v1/ai-gen/generations?' + params, {
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('token'),
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success && data.data.data.length > 0) {
                renderExplore(data.data.data);
            } else {
                grid.innerHTML = `
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-compass fa-4x text-muted mb-3"></i>
                        <h5>No content to explore yet</h5>
                        <p class="text-muted">Start creating to see your work here!</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading explore:', error);
            grid.innerHTML = '<div class="col-12 text-center py-5"><p class="text-danger">Failed to load content</p></div>';
        }
    }

    function renderExplore(generations) {
        const grid = document.getElementById('explore-grid');

        grid.innerHTML = generations.map(gen => `
            <div class="generation-card" onclick="viewGeneration(${gen.id})">
                <img src="${gen.file_url || gen.thumbnail_url || '/images/placeholder.jpg'}" alt="${gen.prompt}">
                <div class="generation-info">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge badge-${gen.type === 'image' ? 'primary' : 'success'}">
                            ${gen.type}
                        </span>
                        ${gen.is_favorite ? '<i class="fas fa-heart text-danger"></i>' : ''}
                    </div>
                    <p class="mb-0 text-muted small">${gen.prompt.substring(0, 60)}...</p>
                    <small class="text-muted">${formatDate(gen.created_at)}</small>
                </div>
            </div>
        `).join('');
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        loadDashboardData();
        loadGenerations();

        // Load packages when tab is clicked
        $('a[href="#packages"]').on('shown.bs.tab', function() {
            loadPackages();
        });

        // Load explore when tab is clicked
        $('a[href="#explore"]').on('shown.bs.tab', function() {
            loadExplore();
        });

        // Filter buttons for My Creations
        document.querySelectorAll('[data-filter]').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('[data-filter]').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                loadGenerations(this.dataset.filter);
            });
        });

        // Filter buttons for Explore
        document.querySelectorAll('[data-explore-filter]').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('[data-explore-filter]').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                loadExplore(this.dataset.exploreFilter);
            });
        });

        // Create form submission
        document.getElementById('createForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            // Disable submit button and show loading
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';

            try {
                // Get form data
                const data = {
                    provider: formData.get('provider'),
                    type: formData.get('type'),
                    prompt: formData.get('prompt'),
                    parameters: {
                        style: formData.get('style'),
                        size: formData.get('size')
                    }
                };

                // Make API request
                const response = await fetch('/api/v1/ai-gen/generate', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + localStorage.getItem('token'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    // Close modal
                    $('#createModal').modal('hide');

                    // Show success message
                    alert('Generation started! Your creation will appear in a moment.');

                    // Reset form
                    this.reset();

                    // Reload generations
                    loadGenerations();

                    // Start polling for status if needed
                    if (result.data && result.data.id) {
                        pollGenerationStatus(result.data.id);
                    }
                } else {
                    alert('Error: ' + (result.error || 'Failed to start generation'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            } finally {
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    });
</script>
@endpush
