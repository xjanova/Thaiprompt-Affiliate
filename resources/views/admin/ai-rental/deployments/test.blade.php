@extends('layouts.admin')

@section('title', 'Test Deployment')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-gray-900 py-8 px-4">
    <div class="max-w-7xl mx-auto">
        {{-- Header --}}
        <div class="mb-8">
            <a href="{{ route('admin.ai-rental.deployments.show', $deployment) }}"
               class="inline-flex items-center text-cyan-400 hover:text-cyan-300 mb-4 transition">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Deployment
            </a>
            <h1 class="text-4xl font-bold text-white mb-2 flex items-center gap-3">
                <i class="fas fa-flask text-cyan-400"></i>
                Test Deployment
            </h1>
            <p class="text-white/60">{{ $deployment->name }} - Test Inference API</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left: Request Editor --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Endpoint Info --}}
                <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                    <h2 class="text-xl font-bold text-white mb-4">Endpoint Information</h2>
                    <div class="space-y-3">
                        <div>
                            <label class="text-white/60 text-sm">Endpoint URL</label>
                            <div class="mt-1 flex gap-2">
                                <input type="text"
                                       id="endpointUrl"
                                       value="{{ $deployment->endpoint_url ?? 'https://api.example.com/v1/generate' }}"
                                       class="flex-1 px-4 py-2 bg-black/30 border border-white/30 rounded-xl text-white font-mono text-sm"
                                       readonly>
                                <button onclick="copyEndpoint()"
                                        class="px-4 py-2 bg-cyan-500 hover:bg-cyan-600 text-white rounded-xl transition">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="flex-1">
                                <label class="text-white/60 text-sm">Method</label>
                                <div class="mt-1 px-4 py-2 bg-green-500/20 border border-green-500/30 rounded-xl text-green-400 font-bold text-center">
                                    POST
                                </div>
                            </div>
                            <div class="flex-1">
                                <label class="text-white/60 text-sm">Status</label>
                                <div class="mt-1 px-4 py-2 bg-{{ $deployment->status === 'running' ? 'green' : 'gray' }}-500/20 border border-{{ $deployment->status === 'running' ? 'green' : 'gray' }}-500/30 rounded-xl text-{{ $deployment->status === 'running' ? 'green' : 'gray' }}-400 font-bold text-center uppercase">
                                    {{ $deployment->status }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Request Body Editor --}}
                <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-white">Request Payload</h2>
                        <div class="flex gap-2">
                            <button onclick="loadTemplate('text-generation')"
                                    class="px-3 py-1 bg-white/10 hover:bg-white/20 text-white text-sm rounded-lg transition">
                                Text Gen
                            </button>
                            <button onclick="loadTemplate('chat')"
                                    class="px-3 py-1 bg-white/10 hover:bg-white/20 text-white text-sm rounded-lg transition">
                                Chat
                            </button>
                            <button onclick="loadTemplate('image-gen')"
                                    class="px-3 py-1 bg-white/10 hover:bg-white/20 text-white text-sm rounded-lg transition">
                                Image
                            </button>
                        </div>
                    </div>

                    {{-- JSON Editor --}}
                    <textarea id="requestBody"
                              class="w-full h-64 px-4 py-3 bg-black/50 border border-white/30 rounded-xl text-green-400 font-mono text-sm resize-none focus:outline-none focus:ring-2 focus:ring-cyan-400"
                              placeholder='{\n  "prompt": "Hello, how are you?",\n  "max_tokens": 100,\n  "temperature": 0.7\n}'></textarea>

                    {{-- Send Button --}}
                    <div class="mt-4 flex gap-3">
                        <button onclick="sendRequest()"
                                class="flex-1 px-6 py-3 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 text-white rounded-xl font-bold transition shadow-lg hover:shadow-cyan-500/50">
                            <i class="fas fa-paper-plane mr-2"></i>
                            Send Request
                        </button>
                        <button onclick="clearRequest()"
                                class="px-6 py-3 bg-white/10 hover:bg-white/20 text-white rounded-xl transition border border-white/30">
                            <i class="fas fa-eraser"></i>
                        </button>
                    </div>
                </div>

                {{-- Response Viewer --}}
                <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-white">Response</h2>
                        <div id="responseStatus" class="text-white/60 text-sm"></div>
                    </div>

                    {{-- Response Content --}}
                    <div id="responseContainer"
                         class="min-h-[200px] p-4 bg-black/50 border border-white/30 rounded-xl overflow-auto">
                        <div class="text-white/40 text-center py-8">
                            <i class="fas fa-inbox text-3xl mb-2"></i>
                            <div>No response yet. Send a request to see results.</div>
                        </div>
                    </div>

                    {{-- Response Metrics --}}
                    <div id="responseMetrics" class="mt-4 hidden">
                        <div class="grid grid-cols-3 gap-4">
                            <div class="bg-white/5 rounded-xl p-3 text-center">
                                <div class="text-white/60 text-xs mb-1">Status</div>
                                <div id="metricStatus" class="text-white font-bold">-</div>
                            </div>
                            <div class="bg-white/5 rounded-xl p-3 text-center">
                                <div class="text-white/60 text-xs mb-1">Latency</div>
                                <div id="metricLatency" class="text-white font-bold">-</div>
                            </div>
                            <div class="bg-white/5 rounded-xl p-3 text-center">
                                <div class="text-white/60 text-xs mb-1">Size</div>
                                <div id="metricSize" class="text-white font-bold">-</div>
                            </div>
                        </div>
                    </div>

                    {{-- Copy Response --}}
                    <button onclick="copyResponse()"
                            id="copyResponseBtn"
                            class="mt-4 w-full px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-xl transition border border-white/30 hidden">
                        <i class="fas fa-copy mr-2"></i>
                        Copy Response
                    </button>
                </div>
            </div>

            {{-- Right: Quick Info & Templates --}}
            <div class="space-y-6">
                {{-- Model Info --}}
                <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                    <h2 class="text-lg font-bold text-white mb-4">Model Info</h2>
                    <div class="space-y-3 text-sm">
                        <div>
                            <div class="text-white/60 mb-1">Model</div>
                            <div class="text-white font-semibold">{{ $deployment->model->name }}</div>
                        </div>
                        <div>
                            <div class="text-white/60 mb-1">Size</div>
                            <div class="text-white font-semibold">{{ $deployment->model->size }}</div>
                        </div>
                        <div>
                            <div class="text-white/60 mb-1">Provider</div>
                            <div class="text-white font-semibold">{{ $deployment->cloudConfig->cloudProvider->name }}</div>
                        </div>
                        <div>
                            <div class="text-white/60 mb-1">Instance</div>
                            <div class="text-white font-semibold">{{ $deployment->instance_type }}</div>
                        </div>
                    </div>
                </div>

                {{-- Request Templates --}}
                <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                    <h2 class="text-lg font-bold text-white mb-4">Request Templates</h2>
                    <div class="space-y-2">
                        <button onclick="loadTemplate('text-generation')"
                                class="w-full px-4 py-3 bg-white/5 hover:bg-white/10 text-white rounded-xl transition text-left border border-white/20">
                            <div class="font-semibold mb-1">Text Generation</div>
                            <div class="text-white/60 text-xs">Generate text from prompt</div>
                        </button>
                        <button onclick="loadTemplate('chat')"
                                class="w-full px-4 py-3 bg-white/5 hover:bg-white/10 text-white rounded-xl transition text-left border border-white/20">
                            <div class="font-semibold mb-1">Chat Completion</div>
                            <div class="text-white/60 text-xs">Multi-turn conversation</div>
                        </button>
                        <button onclick="loadTemplate('image-gen')"
                                class="w-full px-4 py-3 bg-white/5 hover:bg-white/10 text-white rounded-xl transition text-left border border-white/20">
                            <div class="font-semibold mb-1">Image Generation</div>
                            <div class="text-white/60 text-xs">Text-to-image</div>
                        </button>
                        <button onclick="loadTemplate('embeddings')"
                                class="w-full px-4 py-3 bg-white/5 hover:bg-white/10 text-white rounded-xl transition text-left border border-white/20">
                            <div class="font-semibold mb-1">Embeddings</div>
                            <div class="text-white/60 text-xs">Get text embeddings</div>
                        </button>
                    </div>
                </div>

                {{-- Tips --}}
                <div class="bg-gradient-to-br from-cyan-500/20 to-blue-600/20 backdrop-blur-lg rounded-2xl p-6 border border-cyan-500/30">
                    <div class="flex items-start gap-3 mb-3">
                        <i class="fas fa-lightbulb text-yellow-400 text-xl mt-1"></i>
                        <h2 class="text-lg font-bold text-white">Tips</h2>
                    </div>
                    <ul class="text-white/80 text-sm space-y-2">
                        <li>• Use JSON format for request body</li>
                        <li>• Click templates for quick start</li>
                        <li>• Check response latency</li>
                        <li>• Copy curl command for CLI</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Request templates
const templates = {
    'text-generation': {
        prompt: "Write a short story about a robot learning to paint.",
        max_tokens: 200,
        temperature: 0.7,
        top_p: 0.9
    },
    'chat': {
        messages: [
            { role: "system", content: "You are a helpful assistant." },
            { role: "user", content: "What is machine learning?" }
        ],
        max_tokens: 150,
        temperature: 0.7
    },
    'image-gen': {
        prompt: "A beautiful sunset over mountains, digital art",
        width: 512,
        height: 512,
        steps: 30
    },
    'embeddings': {
        text: "Hello, world!",
        model: "text-embedding-ada-002"
    }
};

let lastResponse = null;

// Load template
function loadTemplate(type) {
    const template = templates[type];
    if (template) {
        document.getElementById('requestBody').value = JSON.stringify(template, null, 2);
    }
}

// Copy endpoint
function copyEndpoint() {
    const endpoint = document.getElementById('endpointUrl').value;
    navigator.clipboard.writeText(endpoint).then(() => {
        alert('Endpoint copied to clipboard!');
    });
}

// Clear request
function clearRequest() {
    document.getElementById('requestBody').value = '';
    document.getElementById('responseContainer').innerHTML = `
        <div class="text-white/40 text-center py-8">
            <i class="fas fa-inbox text-3xl mb-2"></i>
            <div>No response yet. Send a request to see results.</div>
        </div>
    `;
    document.getElementById('responseMetrics').classList.add('hidden');
    document.getElementById('copyResponseBtn').classList.add('hidden');
}

// Send request
async function sendRequest() {
    const endpointUrl = document.getElementById('endpointUrl').value;
    const requestBody = document.getElementById('requestBody').value;

    if (!requestBody.trim()) {
        alert('Please enter request body');
        return;
    }

    try {
        // Parse JSON
        const payload = JSON.parse(requestBody);

        // Update UI
        document.getElementById('responseContainer').innerHTML = `
            <div class="text-cyan-400 text-center py-8">
                <i class="fas fa-spinner fa-spin text-3xl mb-2"></i>
                <div>Sending request...</div>
            </div>
        `;
        document.getElementById('responseStatus').textContent = 'Sending...';

        // Send request
        const startTime = Date.now();
        const response = await fetch(endpointUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });
        const latency = Date.now() - startTime;

        // Parse response
        const data = await response.json();
        lastResponse = data;

        // Calculate response size
        const responseSize = new Blob([JSON.stringify(data)]).size;

        // Display response
        displayResponse(data, response.status, latency, responseSize);

    } catch (error) {
        console.error('Request error:', error);
        document.getElementById('responseContainer').innerHTML = `
            <div class="text-red-400 p-4">
                <div class="font-bold mb-2">Error:</div>
                <div>${error.message}</div>
            </div>
        `;
        document.getElementById('responseStatus').textContent = 'Error';
    }
}

// Display response
function displayResponse(data, status, latency, size) {
    const container = document.getElementById('responseContainer');

    // Format JSON with syntax highlighting
    const formatted = JSON.stringify(data, null, 2);
    container.innerHTML = `
        <pre class="text-green-400 text-sm font-mono whitespace-pre-wrap">${escapeHtml(formatted)}</pre>
    `;

    // Update metrics
    document.getElementById('metricStatus').textContent = status;
    document.getElementById('metricLatency').textContent = latency + ' ms';
    document.getElementById('metricSize').textContent = formatBytes(size);
    document.getElementById('responseMetrics').classList.remove('hidden');
    document.getElementById('copyResponseBtn').classList.remove('hidden');

    // Update status
    const statusText = status >= 200 && status < 300 ? 'Success' : 'Error';
    document.getElementById('responseStatus').textContent = statusText;
}

// Copy response
function copyResponse() {
    if (lastResponse) {
        const formatted = JSON.stringify(lastResponse, null, 2);
        navigator.clipboard.writeText(formatted).then(() => {
            alert('Response copied to clipboard!');
        });
    }
}

// Helper: escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Helper: format bytes
function formatBytes(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

// Load default template on page load
document.addEventListener('DOMContentLoaded', () => {
    loadTemplate('text-generation');
});
</script>
@endpush
@endsection
