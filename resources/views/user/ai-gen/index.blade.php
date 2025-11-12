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
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap');

    .ai-gen-container {
        font-family: 'Poppins', sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding-bottom: 60px;
        position: relative;
        overflow: hidden;
    }

    /* Animated Background Particles */
    .ai-gen-container::before {
        content: '';
        position: absolute;
        width: 200%;
        height: 200%;
        top: -50%;
        left: -50%;
        background:
            radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
            radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.08) 0%, transparent 50%),
            radial-gradient(circle at 40% 20%, rgba(255, 255, 255, 0.05) 0%, transparent 50%);
        animation: floating 20s ease-in-out infinite;
        pointer-events: none;
    }

    @keyframes floating {
        0%, 100% { transform: translate(0, 0) rotate(0deg); }
        33% { transform: translate(30px, -30px) rotate(120deg); }
        66% { transform: translate(-20px, 20px) rotate(240deg); }
    }

    .hero-section {
        padding: 100px 0 80px;
        color: white;
        position: relative;
        z-index: 1;
    }

    .hero-title {
        font-size: 4rem;
        font-weight: 900;
        line-height: 1.1;
        margin-bottom: 24px;
        text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        letter-spacing: -1px;
    }

    .gradient-text {
        background: linear-gradient(135deg, #FFD700 0%, #FFA500 50%, #FF6347 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        position: relative;
        display: inline-block;
        animation: shimmer 3s ease-in-out infinite;
    }

    @keyframes shimmer {
        0%, 100% { filter: brightness(1) hue-rotate(0deg); }
        50% { filter: brightness(1.2) hue-rotate(10deg); }
    }

    .hero-subtitle {
        font-size: 1.35rem;
        margin-bottom: 40px;
        opacity: 0.95;
        font-weight: 300;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    }

    .btn-glow {
        padding: 16px 40px;
        font-size: 1.1rem;
        font-weight: 600;
        border-radius: 50px;
        background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
        border: none;
        color: #333;
        box-shadow: 0 10px 40px rgba(255, 215, 0, 0.4), 0 0 0 0 rgba(255, 215, 0, 0.5);
        animation: pulse-glow 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .btn-glow::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.5);
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }

    .btn-glow:hover::before {
        width: 300px;
        height: 300px;
    }

    .btn-glow:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 50px rgba(255, 215, 0, 0.6);
    }

    @keyframes pulse-glow {
        0%, 100% {
            box-shadow: 0 10px 40px rgba(255, 215, 0, 0.4), 0 0 0 0 rgba(255, 215, 0, 0.5);
        }
        50% {
            box-shadow: 0 10px 40px rgba(255, 215, 0, 0.4), 0 0 0 15px rgba(255, 215, 0, 0);
        }
    }

    .hero-image-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 24px;
        perspective: 1000px;
    }

    .grid-item {
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 15px 50px rgba(0, 0, 0, 0.4);
        transition: all 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        position: relative;
        transform-style: preserve-3d;
    }

    .grid-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(255, 215, 0, 0.2) 0%, rgba(255, 105, 180, 0.2) 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
        z-index: 1;
    }

    .grid-item:hover {
        transform: translateY(-10px) rotateX(5deg);
        box-shadow: 0 25px 70px rgba(0, 0, 0, 0.5);
    }

    .grid-item:hover::before {
        opacity: 1;
    }

    .grid-item img {
        width: 100%;
        height: 200px;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .grid-item:hover img {
        transform: scale(1.1);
    }

    .stats-bar {
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(20px);
        padding: 40px 0;
        box-shadow: 0 -10px 60px rgba(0, 0, 0, 0.15);
        border-radius: 40px 40px 0 0;
        margin-top: -40px;
        position: relative;
        z-index: 1;
    }

    .stat-item {
        padding: 20px;
        border-radius: 20px;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .stat-item:hover {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        transform: translateY(-5px);
    }

    .stat-icon {
        font-size: 2.5rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 12px;
        transition: transform 0.3s ease;
    }

    .stat-item:hover .stat-icon {
        transform: scale(1.2) rotate(5deg);
    }

    .stat-value {
        font-size: 2.5rem;
        font-weight: 800;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 5px;
    }

    .stat-label {
        font-size: 0.95rem;
        color: #666;
        font-weight: 500;
        letter-spacing: 0.5px;
    }

    .ai-gen-tabs {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border-radius: 60px;
        padding: 8px;
    }

    .ai-gen-tabs .nav-link {
        border-radius: 50px;
        font-weight: 600;
        transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        padding: 14px 32px;
        color: #666;
        position: relative;
        overflow: hidden;
    }

    .ai-gen-tabs .nav-link::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        transition: left 0.5s;
    }

    .ai-gen-tabs .nav-link:hover::before {
        left: 100%;
    }

    .ai-gen-tabs .nav-link.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white !important;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        transform: translateY(-2px);
    }

    .generations-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 30px;
        min-height: 400px;
    }

    .generation-card {
        border-radius: 24px;
        overflow: hidden;
        background: white;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        cursor: pointer;
        position: relative;
        transform-style: preserve-3d;
    }

    .generation-card::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
        pointer-events: none;
    }

    .generation-card:hover {
        transform: translateY(-12px) scale(1.02);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
    }

    .generation-card:hover::after {
        opacity: 1;
    }

    .generation-card img {
        width: 100%;
        height: 280px;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .generation-card:hover img {
        transform: scale(1.1) rotate(2deg);
    }

    .generation-info {
        padding: 20px;
        position: relative;
        z-index: 1;
    }

    .category-card {
        background: white;
        border-radius: 24px;
        padding: 40px 30px;
        text-align: center;
        transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        cursor: pointer;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        position: relative;
        overflow: hidden;
    }

    .category-card::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        transform: rotate(45deg);
        transition: all 0.5s ease;
        opacity: 0;
    }

    .category-card:hover::before {
        opacity: 1;
        transform: rotate(45deg) translate(10%, 10%);
    }

    .category-card:hover {
        transform: translateY(-12px) scale(1.05);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
    }

    .category-icon {
        font-size: 3.5rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 20px;
        transition: transform 0.4s ease;
        position: relative;
        z-index: 1;
    }

    .category-card:hover .category-icon {
        transform: scale(1.2) rotate(10deg);
    }

    .package-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%);
        border-radius: 28px;
        padding: 50px 40px;
        box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
        transition: all 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        position: relative;
        overflow: hidden;
        border: 2px solid transparent;
    }

    .package-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        opacity: 0;
        transition: opacity 0.4s ease;
    }

    .package-card.popular {
        border: 2px solid #FFD700;
        transform: scale(1.05);
    }

    .package-card.popular::after {
        content: '⭐ POPULAR';
        position: absolute;
        top: 24px;
        right: -32px;
        background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
        color: #333;
        padding: 8px 50px;
        transform: rotate(45deg);
        font-weight: 700;
        font-size: 0.75rem;
        letter-spacing: 1px;
        box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
    }

    .package-card:hover {
        transform: translateY(-15px) scale(1.02);
        box-shadow: 0 25px 70px rgba(0, 0, 0, 0.25);
        border-color: #667eea;
    }

    .package-card:hover::before {
        opacity: 1;
    }

    .package-card.popular:hover {
        transform: translateY(-15px) scale(1.07);
    }

    .package-price {
        font-size: 3.5rem;
        font-weight: 900;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin: 20px 0;
        letter-spacing: -2px;
    }

    .modal-content {
        border-radius: 28px;
        border: none;
        overflow: hidden;
        box-shadow: 0 25px 100px rgba(0, 0, 0, 0.3);
    }

    .modal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 24px 30px;
    }

    .modal-body {
        padding: 30px;
    }

    .modal-footer {
        padding: 20px 30px;
        background: #f8f9fa;
    }

    .generation-preview {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 20px;
        padding: 30px;
        min-height: 450px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }

    .generation-preview::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background:
            repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(102, 126, 234, 0.03) 10px, rgba(102, 126, 234, 0.03) 20px);
    }

    .generation-preview img {
        max-width: 100%;
        max-height: 100%;
        border-radius: 16px;
        box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
        position: relative;
        z-index: 1;
    }

    /* Custom scrollbar */
    ::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }

    ::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .hero-title {
            font-size: 2.5rem;
        }

        .stat-value {
            font-size: 2rem;
        }

        .package-price {
            font-size: 2.5rem;
        }

        .generations-grid {
            grid-template-columns: 1fr;
        }
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

    // Initialize API token
    @if(isset($api_token))
        localStorage.setItem('token', '{{ $api_token }}');
    @endif

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
