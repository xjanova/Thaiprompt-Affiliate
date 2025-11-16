@extends('layouts.user-arrow-x')

@section('title', 'Create Token')

@section('styles')
<style>
.create-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 40px;
    border-radius: 20px;
    color: white;
    margin-bottom: 30px;
}
.step-indicator {
    display: flex;
    justify-content: space-between;
    margin-bottom: 40px;
}
.step {
    flex: 1;
    text-align: center;
    position: relative;
}
.step::before {
    content: '';
    position: absolute;
    top: 25px;
    left: 0;
    right: 0;
    height: 2px;
    background: #e5e7eb;
    z-index: -1;
}
.step:first-child::before {
    left: 50%;
}
.step:last-child::before {
    right: 50%;
}
.step-number {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #e5e7eb;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-bottom: 10px;
}
.step.active .step-number {
    background: #667eea;
    color: white;
}
.step.completed .step-number {
    background: #10b981;
    color: white;
}
.form-section {
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}
.feature-toggle {
    border: 2px solid #e5e7eb;
    padding: 15px;
    border-radius: 10px;
    transition: all 0.3s;
    cursor: pointer;
}
.feature-toggle:hover {
    border-color: #667eea;
}
.feature-toggle input:checked + label {
    color: #667eea;
}
.preview-logo {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
}
</style>
@endsection

@section('content')
<div class="container">
    {{-- Hero --}}
    <div class="create-hero text-center">
        <h1 class="display-5 fw-bold mb-3">🚀 Create Your Token</h1>
        <p class="lead">Launch your custom token on TPIX Blockchain in minutes</p>
    </div>

    {{-- Step Indicator --}}
    <div class="step-indicator">
        <div class="step active" id="step-1">
            <div class="step-number">1</div>
            <div class="fw-bold">Basic Info</div>
        </div>
        <div class="step" id="step-2">
            <div class="step-number">2</div>
            <div class="fw-bold">Features</div>
        </div>
        <div class="step" id="step-3">
            <div class="step-number">3</div>
            <div class="fw-bold">Tokenomics</div>
        </div>
        <div class="step" id="step-4">
            <div class="step-number">4</div>
            <div class="fw-bold">Review</div>
        </div>
    </div>

    <form action="{{ route('user.tokens.store') }}" method="POST" enctype="multipart/form-data" id="createTokenForm">
        @csrf

        {{-- Step 1: Basic Information --}}
        <div class="form-section" id="section-1">
            <h4 class="mb-4">📋 Basic Information</h4>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Token Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control form-control-lg @error('name') is-invalid @enderror" placeholder="e.g., My Awesome Token" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Symbol <span class="text-danger">*</span></label>
                    <input type="text" name="symbol" class="form-control form-control-lg @error('symbol') is-invalid @enderror" placeholder="e.g., MAT" value="{{ old('symbol') }}" maxlength="20" required style="text-transform: uppercase;">
                    @error('symbol')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 mb-3">
                    <label class="form-label">Description <span class="text-danger">*</span></label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="4" placeholder="Describe your token's purpose and features (minimum 50 characters)" required>{{ old('description') }}</textarea>
                    <small class="text-muted">Minimum 50 characters</small>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Total Supply <span class="text-danger">*</span></label>
                    <input type="number" name="total_supply" class="form-control form-control-lg @error('total_supply') is-invalid @enderror" placeholder="e.g., 1000000" value="{{ old('total_supply') }}" step="0.01" required>
                    @error('total_supply')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Decimals <span class="text-danger">*</span></label>
                    <select name="decimals" class="form-select form-select-lg @error('decimals') is-invalid @enderror" required>
                        <option value="18" {{ old('decimals') == 18 ? 'selected' : '' }}>18 (Standard)</option>
                        <option value="8" {{ old('decimals') == 8 ? 'selected' : '' }}>8</option>
                        <option value="6" {{ old('decimals') == 6 ? 'selected' : '' }}>6</option>
                        <option value="2" {{ old('decimals') == 2 ? 'selected' : '' }}>2</option>
                    </select>
                    @error('decimals')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Category <span class="text-danger">*</span></label>
                    <select name="category" class="form-select form-select-lg @error('category') is-invalid @enderror" required>
                        <option value="">Select Category</option>
                        <option value="defi" {{ old('category') == 'defi' ? 'selected' : '' }}>🏦 DeFi</option>
                        <option value="gamefi" {{ old('category') == 'gamefi' ? 'selected' : '' }}>🎮 GameFi</option>
                        <option value="meme" {{ old('category') == 'meme' ? 'selected' : '' }}>😂 Meme</option>
                        <option value="utility" {{ old('category') == 'utility' ? 'selected' : '' }}>🔧 Utility</option>
                        <option value="stablecoin" {{ old('category') == 'stablecoin' ? 'selected' : '' }}>💵 Stablecoin</option>
                        <option value="nft" {{ old('category') == 'nft' ? 'selected' : '' }}>🎨 NFT</option>
                        <option value="dao" {{ old('category') == 'dao' ? 'selected' : '' }}>🏛️ DAO</option>
                        <option value="other" {{ old('category') == 'other' ? 'selected' : '' }}>🔹 Other</option>
                    </select>
                    @error('category')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Logo</label>
                    <input type="file" name="logo" class="form-control @error('logo') is-invalid @enderror" accept="image/*" onchange="previewLogo(this)">
                    <small class="text-muted">PNG, JPG, SVG (Max 2MB)</small>
                    @error('logo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div id="logoPreview" class="mt-2"></div>
                </div>
            </div>

            <div class="text-end">
                <button type="button" class="btn btn-primary btn-lg" onclick="nextStep(2)">
                    Next Step <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        {{-- Step 2: Features --}}
        <div class="form-section d-none" id="section-2">
            <h4 class="mb-4">⚙️ Token Features</h4>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="feature-toggle">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_mintable" id="mintable" {{ old('is_mintable') ? 'checked' : '' }}>
                            <label class="form-check-label" for="mintable">
                                <strong>Mintable</strong>
                                <small class="d-block text-muted">Allow creating new tokens after deployment</small>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="feature-toggle">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_burnable" id="burnable" {{ old('is_burnable', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="burnable">
                                <strong>Burnable</strong>
                                <small class="d-block text-muted">Allow destroying tokens to reduce supply</small>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="feature-toggle">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_pausable" id="pausable" {{ old('is_pausable') ? 'checked' : '' }}>
                            <label class="form-check-label" for="pausable">
                                <strong>Pausable</strong>
                                <small class="d-block text-muted">Ability to pause all token transfers</small>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="feature-toggle">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_freezable" id="freezable" {{ old('is_freezable') ? 'checked' : '' }}>
                            <label class="form-check-label" for="freezable">
                                <strong>Freezable</strong>
                                <small class="d-block text-muted">Ability to freeze specific addresses</small>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="feature-toggle">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="has_max_supply" id="maxSupply" {{ old('has_max_supply') ? 'checked' : '' }} onchange="toggleMaxSupply()">
                            <label class="form-check-label" for="maxSupply">
                                <strong>Maximum Supply Limit</strong>
                                <small class="d-block text-muted">Set a cap on total supply (required if Mintable is enabled)</small>
                            </label>
                        </div>
                        <div id="maxSupplyInput" class="mt-3 d-none">
                            <input type="number" name="max_supply" class="form-control" placeholder="Enter maximum supply">
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <button type="button" class="btn btn-outline-secondary btn-lg" onclick="prevStep(1)">
                    <i class="fas fa-arrow-left"></i> Previous
                </button>
                <button type="button" class="btn btn-primary btn-lg" onclick="nextStep(3)">
                    Next Step <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        {{-- Step 3: Tokenomics --}}
        <div class="form-section d-none" id="section-3">
            <h4 class="mb-4">💰 Tokenomics & Social Links</h4>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Initial Price (TPIX) <span class="text-danger">*</span></label>
                    <input type="number" name="initial_price_tpix" class="form-control form-control-lg @error('initial_price_tpix') is-invalid @enderror" placeholder="e.g., 0.01" value="{{ old('initial_price_tpix') }}" step="0.00000001" required>
                    @error('initial_price_tpix')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Website</label>
                    <input type="url" name="website" class="form-control form-control-lg" placeholder="https://yourtoken.com" value="{{ old('website') }}">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Whitepaper URL</label>
                    <input type="url" name="whitepaper_url" class="form-control" placeholder="https://yourtoken.com/whitepaper.pdf" value="{{ old('whitepaper_url') }}">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">GitHub Repository</label>
                    <input type="url" name="github_url" class="form-control" placeholder="https://github.com/yourproject" value="{{ old('github_url') }}">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Twitter Handle</label>
                    <div class="input-group">
                        <span class="input-group-text">@</span>
                        <input type="text" name="twitter_handle" class="form-control" placeholder="yourtoken" value="{{ old('twitter_handle') }}">
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Telegram Group</label>
                    <input type="url" name="telegram_group" class="form-control" placeholder="https://t.me/yourgroup" value="{{ old('telegram_group') }}">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Discord Invite</label>
                    <input type="url" name="discord_invite" class="form-control" placeholder="https://discord.gg/..." value="{{ old('discord_invite') }}">
                </div>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <button type="button" class="btn btn-outline-secondary btn-lg" onclick="prevStep(2)">
                    <i class="fas fa-arrow-left"></i> Previous
                </button>
                <button type="button" class="btn btn-primary btn-lg" onclick="nextStep(4)">
                    Review <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        {{-- Step 4: Review --}}
        <div class="form-section d-none" id="section-4">
            <h4 class="mb-4">✅ Review & Submit</h4>

            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Please review your token details carefully. After submission, your token will be reviewed by our team before deployment.
            </div>

            <div id="reviewContent"></div>

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="termsAgree" required>
                <label class="form-check-label" for="termsAgree">
                    I agree to the <a href="#" target="_blank">Terms of Service</a> and understand that token creation requires approval.
                </label>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <button type="button" class="btn btn-outline-secondary btn-lg" onclick="prevStep(3)">
                    <i class="fas fa-arrow-left"></i> Previous
                </button>
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="fas fa-rocket"></i> Create Token
                </button>
            </div>
        </div>
    </form>
</div>

<script>
let currentStep = 1;

function nextStep(step) {
    document.getElementById('section-' + currentStep).classList.add('d-none');
    document.getElementById('step-' + currentStep).classList.remove('active');
    document.getElementById('step-' + currentStep).classList.add('completed');

    currentStep = step;
    document.getElementById('section-' + currentStep).classList.remove('d-none');
    document.getElementById('step-' + currentStep).classList.add('active');

    if (step === 4) {
        buildReview();
    }

    window.scrollTo(0, 0);
}

function prevStep(step) {
    document.getElementById('section-' + currentStep).classList.add('d-none');
    document.getElementById('step-' + currentStep).classList.remove('active');

    currentStep = step;
    document.getElementById('section-' + currentStep).classList.remove('d-none');
    document.getElementById('step-' + step).classList.remove('completed');
    document.getElementById('step-' + step).classList.add('active');

    window.scrollTo(0, 0);
}

function previewLogo(input) {
    const preview = document.getElementById('logoPreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" class="preview-logo">`;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function toggleMaxSupply() {
    const checkbox = document.getElementById('maxSupply');
    const input = document.getElementById('maxSupplyInput');
    if (checkbox.checked) {
        input.classList.remove('d-none');
    } else {
        input.classList.add('d-none');
    }
}

function buildReview() {
    const form = document.getElementById('createTokenForm');
    const formData = new FormData(form);

    let html = '<div class="card"><div class="card-body">';
    html += '<div class="row">';

    // Basic Info
    html += '<div class="col-md-6 mb-3">';
    html += '<strong>Token Name:</strong> ' + (formData.get('name') || 'N/A') + '<br>';
    html += '<strong>Symbol:</strong> ' + (formData.get('symbol') || 'N/A') + '<br>';
    html += '<strong>Total Supply:</strong> ' + (formData.get('total_supply') || 'N/A') + '<br>';
    html += '<strong>Decimals:</strong> ' + (formData.get('decimals') || 'N/A');
    html += '</div>';

    // Features
    html += '<div class="col-md-6 mb-3">';
    html += '<strong>Features:</strong><br>';
    if (formData.get('is_mintable')) html += '<span class="badge bg-info me-1">Mintable</span>';
    if (formData.get('is_burnable')) html += '<span class="badge bg-danger me-1">Burnable</span>';
    if (formData.get('is_pausable')) html += '<span class="badge bg-warning me-1">Pausable</span>';
    if (formData.get('is_freezable')) html += '<span class="badge bg-primary me-1">Freezable</span>';
    html += '</div>';

    html += '</div></div></div>';

    document.getElementById('reviewContent').innerHTML = html;
}
</script>
@endsection
