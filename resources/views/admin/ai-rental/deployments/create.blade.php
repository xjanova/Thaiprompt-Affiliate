@extends('layouts.admin')

@section('title', 'New Deployment')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-gray-900 py-8 px-4">
    <div class="max-w-4xl mx-auto">
        {{-- Header --}}
        <div class="mb-8">
            <a href="{{ route('admin.ai-rental.deployments.index') }}"
               class="inline-flex items-center text-cyan-400 hover:text-cyan-300 mb-4 transition">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Deployments
            </a>
            <h1 class="text-4xl font-bold text-white mb-2 flex items-center gap-3">
                <i class="fas fa-rocket text-cyan-400"></i>
                New Deployment
            </h1>
            <p class="text-white/60">Deploy AI Model บน Cloud GPU</p>
        </div>

        {{-- Form --}}
        <form action="{{ route('admin.ai-rental.deployments.store') }}" method="POST">
            @csrf

            <div class="space-y-6">
                {{-- Section 1: Model Selection --}}
                <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-lg flex items-center justify-center">
                            <i class="fas fa-brain text-white"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-white">เลือก AI Model</h2>
                    </div>

                    {{-- Model Select --}}
                    <div class="mb-4">
                        <label class="block text-white/80 font-semibold mb-2">
                            AI Model <span class="text-red-400">*</span>
                        </label>
                        <select name="model_id"
                                required
                                class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-cyan-400 @error('model_id') border-red-500 @enderror">
                            <option value="">-- เลือก Model --</option>
                            @foreach($models as $model)
                                <option value="{{ $model->id }}" {{ old('model_id') == $model->id ? 'selected' : '' }}>
                                    {{ $model->name }} - {{ $model->size }}
                                </option>
                            @endforeach
                        </select>
                        @error('model_id')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Section 2: Configuration --}}
                <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 bg-gradient-to-br from-purple-400 to-pink-500 rounded-lg flex items-center justify-center">
                            <i class="fas fa-cog text-white"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-white">Configuration</h2>
                    </div>

                    {{-- Cloud Config --}}
                    <div class="mb-4">
                        <label class="block text-white/80 font-semibold mb-2">
                            Cloud Configuration <span class="text-red-400">*</span>
                        </label>
                        <select name="cloud_config_id"
                                required
                                class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-cyan-400 @error('cloud_config_id') border-red-500 @enderror">
                            @foreach($configs as $config)
                                <option value="{{ $config->id }}"
                                        {{ (old('cloud_config_id', $defaultConfig->id ?? null) == $config->id) ? 'selected' : '' }}>
                                    {{ $config->config_name }} ({{ $config->cloudProvider->name }})
                                    @if($config->is_default) - DEFAULT @endif
                                </option>
                            @endforeach
                        </select>
                        @error('cloud_config_id')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-white/50 text-sm mt-1">
                            <i class="fas fa-info-circle"></i>
                            Configuration ที่มี API Keys และตั้งค่า Cloud Provider
                        </p>
                    </div>

                    {{-- Instance Type --}}
                    <div class="mb-4">
                        <label class="block text-white/80 font-semibold mb-2">
                            GPU Instance Type <span class="text-red-400">*</span>
                        </label>
                        <select name="instance_type"
                                required
                                class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-cyan-400 @error('instance_type') border-red-500 @enderror">
                            <option value="">-- เลือก GPU Type --</option>
                            <option value="A100-80GB" {{ old('instance_type') === 'A100-80GB' ? 'selected' : '' }}>
                                A100-80GB - $2.50/hr (Recommended for large models)
                            </option>
                            <option value="A100-40GB" {{ old('instance_type') === 'A100-40GB' ? 'selected' : '' }}>
                                A100-40GB - $2.00/hr (Best performance)
                            </option>
                            <option value="A100-20GB" {{ old('instance_type') === 'A100-20GB' ? 'selected' : '' }}>
                                A100-20GB - $1.50/hr (Good for medium models)
                            </option>
                            <option value="A6000" {{ old('instance_type') === 'A6000' ? 'selected' : '' }}>
                                A6000 - $1.30/hr (Professional grade)
                            </option>
                            <option value="RTX 4090" {{ old('instance_type') === 'RTX 4090' ? 'selected' : '' }}>
                                RTX 4090 - $1.00/hr (Consumer grade)
                            </option>
                            <option value="RTX 3090" {{ old('instance_type') === 'RTX 3090' ? 'selected' : '' }}>
                                RTX 3090 - $0.80/hr (Budget friendly)
                            </option>
                            <option value="T4" {{ old('instance_type') === 'T4' ? 'selected' : '' }}>
                                T4 - $0.50/hr (Development/testing)
                            </option>
                        </select>
                        @error('instance_type')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Deployment Name --}}
                    <div class="mb-4">
                        <label class="block text-white/80 font-semibold mb-2">
                            Deployment Name (Optional)
                        </label>
                        <input type="text"
                               name="deployment_name"
                               value="{{ old('deployment_name') }}"
                               class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-cyan-400 @error('deployment_name') border-red-500 @enderror"
                               placeholder="เช่น my-llama-deployment">
                        @error('deployment_name')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-white/50 text-sm mt-1">
                            <i class="fas fa-lightbulb"></i>
                            ถ้าไม่ระบุ จะสร้างชื่ออัตโนมัติจากชื่อ model
                        </p>
                    </div>
                </div>

                {{-- Section 3: Advanced Settings --}}
                <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-lg flex items-center justify-center">
                            <i class="fas fa-sliders-h text-white"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-white">Advanced Settings</h2>
                    </div>

                    {{-- Auto Stop --}}
                    <div class="mb-4">
                        <label class="block text-white/80 font-semibold mb-2">
                            Auto Stop (Minutes)
                        </label>
                        <input type="number"
                               name="auto_stop_minutes"
                               value="{{ old('auto_stop_minutes', 60) }}"
                               min="5"
                               max="1440"
                               class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-cyan-400 @error('auto_stop_minutes') border-red-500 @enderror"
                               placeholder="60">
                        @error('auto_stop_minutes')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-white/50 text-sm mt-1">
                            <i class="fas fa-info-circle"></i>
                            หยุดอัตโนมัติเมื่อไม่มีการใช้งาน (5-1440 นาที)
                        </p>
                    </div>

                    {{-- Environment Variables (Optional) --}}
                    <div class="mb-4">
                        <label class="block text-white/80 font-semibold mb-2">
                            Environment Variables (Optional)
                        </label>
                        <div id="env-vars-container" class="space-y-2">
                            <div class="flex gap-2">
                                <input type="text"
                                       name="environment_vars[0][key]"
                                       class="flex-1 px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-cyan-400"
                                       placeholder="KEY">
                                <input type="text"
                                       name="environment_vars[0][value]"
                                       class="flex-1 px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-cyan-400"
                                       placeholder="VALUE">
                            </div>
                        </div>
                        <button type="button"
                                onclick="addEnvVar()"
                                class="mt-2 text-cyan-400 hover:text-cyan-300 text-sm">
                            <i class="fas fa-plus mr-1"></i>Add Variable
                        </button>
                    </div>
                </div>

                {{-- Cost Estimate --}}
                <div class="bg-gradient-to-r from-yellow-500/20 to-orange-500/20 backdrop-blur-lg rounded-2xl p-6 border border-yellow-500/30">
                    <div class="flex items-start gap-4">
                        <i class="fas fa-calculator text-yellow-400 text-2xl mt-1"></i>
                        <div class="flex-1">
                            <div class="text-white font-bold mb-2">💰 Cost Estimate</div>
                            <div class="text-white/80 text-sm space-y-1">
                                <div>• ราคาประมาณ: $0.50 - $2.50 ต่อชั่วโมง (ขึ้นอยู่กับ GPU)</div>
                                <div>• การใช้งาน 1 ชั่วโมง: ~$1.00 - $2.50</div>
                                <div>• การใช้งาน 24 ชั่วโมง: ~$24.00 - $60.00</div>
                            </div>
                            <div class="mt-3 text-xs text-white/60">
                                💡 Tip: ใช้ Auto Stop เพื่อประหยัดค่าใช้จ่าย
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Submit Buttons --}}
                <div class="flex gap-4">
                    <button type="submit"
                            class="flex-1 px-8 py-4 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 text-white rounded-xl font-bold text-lg transition-all duration-300 shadow-lg hover:shadow-cyan-500/50">
                        <i class="fas fa-rocket mr-2"></i>
                        Deploy Now
                    </button>
                    <a href="{{ route('admin.ai-rental.deployments.index') }}"
                       class="px-8 py-4 bg-white/10 hover:bg-white/20 text-white rounded-xl font-bold text-lg transition border border-white/30">
                        Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
let envVarIndex = 1;

function addEnvVar() {
    const container = document.getElementById('env-vars-container');
    const div = document.createElement('div');
    div.className = 'flex gap-2';
    div.innerHTML = `
        <input type="text"
               name="environment_vars[${envVarIndex}][key]"
               class="flex-1 px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-cyan-400"
               placeholder="KEY">
        <input type="text"
               name="environment_vars[${envVarIndex}][value]"
               class="flex-1 px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-cyan-400"
               placeholder="VALUE">
        <button type="button"
                onclick="this.parentElement.remove()"
                class="px-4 py-3 bg-red-500/20 hover:bg-red-500/30 text-red-400 rounded-xl border border-red-500/30 transition">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(div);
    envVarIndex++;
}
</script>
@endpush
@endsection
