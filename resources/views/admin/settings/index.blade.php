@extends('layouts.admin-v3')

@section('title', 'ตั้งค่าระบบ - Arrow X')

@section('content')
<div class="container-fluid px-4 py-6" x-data="settingsManager()">
    {{-- Arrow X Header --}}
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-4xl font-black bg-gradient-to-r from-purple-600 via-pink-600 to-orange-600 bg-clip-text text-transparent mb-2">
                    <i class="fas fa-cog mr-3"></i>ตั้งค่าระบบ
                </h1>
                <p class="text-gray-600 dark:text-gray-400 text-lg">
                    จัดการการตั้งค่าทั้งหมดของระบบ Arrow X Platform
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.dashboard') }}" class="px-6 py-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-300 border-2 border-gray-200 dark:border-gray-700">
                    <i class="fas fa-arrow-left mr-2"></i>กลับ
                </a>
            </div>
        </div>
    </div>

    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-xl shadow-lg flex items-center gap-3 animate-fade-in">
            <i class="fas fa-check-circle text-2xl"></i>
            <span class="font-semibold">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-gradient-to-r from-red-500 to-pink-500 text-white rounded-xl shadow-lg flex items-center gap-3 animate-fade-in">
            <i class="fas fa-exclamation-circle text-2xl"></i>
            <span class="font-semibold">{{ session('error') }}</span>
        </div>
    @endif

    {{-- Tabs Navigation --}}
    <div class="glass-fusion-card rounded-2xl p-2 mb-6 shadow-2xl border border-white/20 dark:border-gray-700/50">
        <div class="flex flex-wrap gap-2">
            <button @click="activeTab = 'general'" :class="activeTab === 'general' ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-xl' : 'bg-white/50 dark:bg-gray-800/50 text-gray-700 dark:text-gray-300 hover:bg-white/70 dark:hover:bg-gray-800/70'" class="px-6 py-3 rounded-xl font-bold transition-all duration-300 flex items-center gap-2">
                <i class="fas fa-sliders-h"></i>
                <span class="hidden sm:inline">ทั่วไป</span>
            </button>

            <button @click="activeTab = 'permissions'" :class="activeTab === 'permissions' ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-xl' : 'bg-white/50 dark:bg-gray-800/50 text-gray-700 dark:text-gray-300 hover:bg-white/70 dark:hover:bg-gray-800/70'" class="px-6 py-3 rounded-xl font-bold transition-all duration-300 flex items-center gap-2">
                <i class="fas fa-shield-alt"></i>
                <span class="hidden sm:inline">สิทธิ์การใช้งาน</span>
            </button>

            <button @click="activeTab = 'affiliate'" :class="activeTab === 'affiliate' ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-xl' : 'bg-white/50 dark:bg-gray-800/50 text-gray-700 dark:text-gray-300 hover:bg-white/70 dark:hover:bg-gray-800/70'" class="px-6 py-3 rounded-xl font-bold transition-all duration-300 flex items-center gap-2">
                <i class="fas fa-network-wired"></i>
                <span class="hidden sm:inline">Affiliate</span>
            </button>

            <button @click="activeTab = 'api'" :class="activeTab === 'api' ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-xl' : 'bg-white/50 dark:bg-gray-800/50 text-gray-700 dark:text-gray-300 hover:bg-white/70 dark:hover:bg-gray-800/70'" class="px-6 py-3 rounded-xl font-bold transition-all duration-300 flex items-center gap-2">
                <i class="fas fa-key"></i>
                <span class="hidden sm:inline">API Keys</span>
            </button>

            <button @click="activeTab = 'advanced'" :class="activeTab === 'advanced' ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-xl' : 'bg-white/50 dark:bg-gray-800/50 text-gray-700 dark:text-gray-300 hover:bg-white/70 dark:hover:bg-gray-800/70'" class="px-6 py-3 rounded-xl font-bold transition-all duration-300 flex items-center gap-2">
                <i class="fas fa-tools"></i>
                <span class="hidden sm:inline">ขั้นสูง</span>
            </button>
        </div>
    </div>

    {{-- Form --}}
    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" @change="formChanged = true" x-ref="settingsForm">
        @csrf
        @method('PUT')

        {{-- Tab: General --}}
        <div x-show="activeTab === 'general'" x-transition class="glass-fusion-card rounded-2xl p-6 md:p-8 shadow-2xl border border-white/20 dark:border-gray-700/50">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-sliders-h text-white text-xl"></i>
                </div>
                การตั้งค่าทั่วไป
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($settings->get('general', []) as $setting)
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                            {{ $setting->display_name }}
                        </label>

                        @if($setting->type === 'text')
                            <input type="text" name="settings[{{ $setting->key }}]" value="{{ old('settings.' . $setting->key, $setting->value) }}" class="w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-4 focus:ring-purple-500/50 focus:border-purple-500 dark:focus:border-purple-400 text-gray-900 dark:text-white transition-all" placeholder="{{ $setting->details }}">

                        @elseif($setting->type === 'textarea')
                            <textarea name="settings[{{ $setting->key }}]" rows="4" class="w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-4 focus:ring-purple-500/50 focus:border-purple-500 dark:focus:border-purple-400 text-gray-900 dark:text-white transition-all" placeholder="{{ $setting->details }}">{{ old('settings.' . $setting->key, $setting->value) }}</textarea>

                        @elseif($setting->type === 'checkbox')
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="settings[{{ $setting->key }}]" value="1" {{ old('settings.' . $setting->key, $setting->value) ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-14 h-8 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-6 peer-checked:after:border-white after:content-[''] after:absolute after:top-1 after:left-1 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-gradient-to-r peer-checked:from-purple-600 peer-checked:to-pink-600"></div>
                            </label>

                        @elseif($setting->type === 'select')
                            <select name="settings[{{ $setting->key }}]" class="w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-4 focus:ring-purple-500/50 focus:border-purple-500 dark:focus:border-purple-400 text-gray-900 dark:text-white transition-all">
                                @foreach(json_decode($setting->details, true) as $option)
                                    <option value="{{ $option }}" {{ old('settings.' . $setting->key, $setting->value) == $option ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                            </select>
                        @endif

                        @if($setting->details && $setting->type !== 'select')
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                <i class="fas fa-info-circle mr-1"></i>{{ $setting->details }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Tab: Permissions --}}
        <div x-show="activeTab === 'permissions'" x-transition class="glass-fusion-card rounded-2xl p-6 md:p-8 shadow-2xl border border-white/20 dark:border-gray-700/50">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-shield-alt text-white text-xl"></i>
                </div>
                สิทธิ์การใช้งาน & Permissions
            </h2>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @foreach($availablePermissions as $category => $permissions)
                    <div class="glass-fusion-card rounded-xl p-6 border border-white/20 dark:border-gray-700/50">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <i class="fas fa-folder text-purple-600 dark:text-purple-400"></i>
                            {{ ucfirst($category) }}
                        </h3>

                        <div class="space-y-3">
                            @foreach($permissions as $permission)
                                <label class="flex items-center justify-between p-3 bg-white/50 dark:bg-gray-800/50 rounded-lg hover:bg-white/70 dark:hover:bg-gray-800/70 transition-all cursor-pointer">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $permission }}</span>
                                    <input type="checkbox" name="permissions[]" value="{{ $permission }}" class="w-5 h-5 text-purple-600 bg-gray-100 border-gray-300 rounded focus:ring-purple-500 dark:focus:ring-purple-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Tab: Affiliate --}}
        <div x-show="activeTab === 'affiliate'" x-transition class="glass-fusion-card rounded-2xl p-6 md:p-8 shadow-2xl border border-white/20 dark:border-gray-700/50">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-teal-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-network-wired text-white text-xl"></i>
                </div>
                การตั้งค่า Affiliate & MLM
            </h2>

            <div class="grid grid-cols-1 gap-6">
                @foreach($settings->get('affiliate', []) as $setting)
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                            {{ $setting->display_name }}
                        </label>

                        @if($setting->type === 'text')
                            <input type="text" name="settings[{{ $setting->key }}]" value="{{ old('settings.' . $setting->key, $setting->value) }}" class="w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-4 focus:ring-purple-500/50 focus:border-purple-500 dark:focus:border-purple-400 text-gray-900 dark:text-white transition-all">

                        @elseif($setting->type === 'number')
                            <input type="number" name="settings[{{ $setting->key }}]" value="{{ old('settings.' . $setting->key, $setting->value) }}" step="0.01" class="w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-4 focus:ring-purple-500/50 focus:border-purple-500 dark:focus:border-purple-400 text-gray-900 dark:text-white transition-all">

                        @elseif($setting->type === 'checkbox')
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="settings[{{ $setting->key }}]" value="1" {{ old('settings.' . $setting->key, $setting->value) ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-14 h-8 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-6 peer-checked:after:border-white after:content-[''] after:absolute after:top-1 after:left-1 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-gradient-to-r peer-checked:from-green-600 peer-checked:to-teal-600"></div>
                            </label>
                        @endif

                        @if($setting->details)
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                <i class="fas fa-info-circle mr-1"></i>{{ $setting->details }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Tab: API Keys --}}
        <div x-show="activeTab === 'api'" x-transition class="glass-fusion-card rounded-2xl p-6 md:p-8 shadow-2xl border border-white/20 dark:border-gray-700/50">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-red-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-key text-white text-xl"></i>
                </div>
                API Keys & Integration
            </h2>

            <div class="grid grid-cols-1 gap-6">
                @foreach($settings->get('api', []) as $setting)
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                            {{ $setting->display_name }}
                        </label>

                        @if($setting->type === 'text' || $setting->type === 'password')
                            <div class="relative">
                                <input type="{{ $setting->type }}" name="settings[{{ $setting->key }}]" value="{{ old('settings.' . $setting->key, $setting->value) }}" class="w-full px-4 py-3 pr-12 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-4 focus:ring-purple-500/50 focus:border-purple-500 dark:focus:border-purple-400 text-gray-900 dark:text-white transition-all font-mono text-sm" placeholder="Enter {{ $setting->display_name }}">
                                <button type="button" @click="copyToClipboard($event.target.previousElementSibling.value)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>

                        @elseif($setting->type === 'textarea')
                            <textarea name="settings[{{ $setting->key }}]" rows="4" class="w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-4 focus:ring-purple-500/50 focus:border-purple-500 dark:focus:border-purple-400 text-gray-900 dark:text-white transition-all font-mono text-sm">{{ old('settings.' . $setting->key, $setting->value) }}</textarea>
                        @endif

                        @if($setting->details)
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                <i class="fas fa-info-circle mr-1"></i>{{ $setting->details }}
                            </p>
                        @endif
                    </div>
                @endforeach

                <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 rounded-lg">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400 mt-1"></i>
                        <div>
                            <h4 class="font-bold text-yellow-800 dark:text-yellow-300 mb-1">คำเตือนด้านความปลอดภัย</h4>
                            <p class="text-sm text-yellow-700 dark:text-yellow-400">
                                กรุณาเก็บ API Keys ของคุณให้ปลอดภัย ไม่ควรแชร์หรือเปิดเผยต่อบุคคลอื่น
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab: Advanced --}}
        <div x-show="activeTab === 'advanced'" x-transition class="glass-fusion-card rounded-2xl p-6 md:p-8 shadow-2xl border border-white/20 dark:border-gray-700/50">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-br from-gray-600 to-gray-800 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-tools text-white text-xl"></i>
                </div>
                การตั้งค่าขั้นสูง
            </h2>

            <div class="grid grid-cols-1 gap-6">
                @foreach($settings->get('advanced', []) as $setting)
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                            {{ $setting->display_name }}
                        </label>

                        @if($setting->type === 'textarea')
                            <textarea name="settings[{{ $setting->key }}]" rows="6" class="w-full px-4 py-3 bg-gray-900 text-green-400 border-2 border-gray-700 rounded-xl focus:ring-4 focus:ring-purple-500/50 focus:border-purple-500 transition-all font-mono text-sm">{{ old('settings.' . $setting->key, $setting->value) }}</textarea>

                        @elseif($setting->type === 'checkbox')
                            <div class="glass-fusion-card rounded-xl p-6 border border-white/20 dark:border-gray-700/50">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <h3 class="font-bold text-gray-900 dark:text-white text-lg">{{ $setting->display_name }}</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $setting->details }}</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="settings[{{ $setting->key }}]" value="1" {{ old('settings.' . $setting->key, $setting->value) ? 'checked' : '' }} class="sr-only peer">
                                        <div class="w-14 h-8 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-red-300 dark:peer-focus:ring-red-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-6 peer-checked:after:border-white after:content-[''] after:absolute after:top-1 after:left-1 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-gradient-to-r peer-checked:from-red-600 peer-checked:to-orange-600"></div>
                                    </label>
                                </div>
                            </div>

                        @else
                            <input type="{{ $setting->type }}" name="settings[{{ $setting->key }}]" value="{{ old('settings.' . $setting->key, $setting->value) }}" class="w-full px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-4 focus:ring-purple-500/50 focus:border-purple-500 dark:focus:border-purple-400 text-gray-900 dark:text-white transition-all">
                        @endif

                        @if($setting->details && $setting->type !== 'checkbox')
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                <i class="fas fa-exclamation-triangle text-yellow-500 mr-1"></i>{{ $setting->details }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Static Save Button (Mobile) --}}
        <div class="mt-8 lg:hidden">
            <button type="submit" class="w-full px-8 py-4 bg-gradient-to-r from-purple-600 via-pink-600 to-orange-600 hover:from-purple-700 hover:via-pink-700 hover:to-orange-700 text-white font-bold text-lg rounded-xl shadow-2xl hover:shadow-3xl transform hover:scale-105 transition-all duration-300 flex items-center justify-center gap-3">
                <i class="fas fa-save text-2xl"></i>
                <span>บันทึกการตั้งค่า</span>
            </button>
        </div>
    </form>

    {{-- Floating Save Button (Desktop) --}}
    <div x-show="formChanged" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="hidden lg:block fixed bottom-6 left-1/2 transform -translate-x-1/2 z-50" style="display: none;">
        <div class="glass-fusion-card rounded-full shadow-2xl p-2 flex items-center gap-4 border-2 border-purple-500/50">
            <button type="button" @click="$refs.settingsForm.submit()" class="px-8 py-4 bg-gradient-to-r from-purple-600 via-pink-600 to-orange-600 hover:from-purple-700 hover:via-pink-700 hover:to-orange-700 text-white font-bold text-lg rounded-full shadow-2xl hover:shadow-3xl transform hover:scale-105 transition-all duration-300 flex items-center gap-3">
                <i class="fas fa-save text-2xl"></i>
                <span>บันทึกการตั้งค่า</span>
            </button>
            <button type="button" @click="formChanged = false; window.location.reload()" class="px-6 py-4 bg-white/20 hover:bg-white/30 dark:bg-gray-800/50 dark:hover:bg-gray-800/70 text-gray-700 dark:text-gray-300 font-semibold rounded-full transition-all">
                <i class="fas fa-times mr-2"></i>ยกเลิก
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function settingsManager() {
    return {
        activeTab: 'general',
        formChanged: false,

        /**
         * คัดลอกข้อความไปยัง Clipboard
         */
        copyToClipboard(text) {
            if (!text) {
                alert('ไม่มีข้อความให้คัดลอก');
                return;
            }

            navigator.clipboard.writeText(text).then(() => {
                this.showNotification('คัดลอกสำเร็จ!', 'success');
            }).catch(() => {
                alert('เกิดข้อผิดพลาดในการคัดลอก');
            });
        },

        /**
         * แสดงการแจ้งเตือน
         */
        showNotification(message, type = 'success') {
            // ใช้ระบบ notification ของ Arrow X
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            const bgColor = type === 'success' ? 'from-green-500 to-emerald-500' : 'from-red-500 to-pink-500';

            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 p-4 bg-gradient-to-r ${bgColor} text-white rounded-xl shadow-lg flex items-center gap-3 z-50 animate-fade-in`;
            notification.innerHTML = `
                <i class="fas ${icon} text-2xl"></i>
                <span class="font-semibold">${message}</span>
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
    };
}
</script>
@endpush
@endsection
