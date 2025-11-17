@extends('layouts.admin-v3')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="devReleaseManager()">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">🛠️ Developer Release Manager</h1>
                <p class="text-gray-600 dark:text-gray-400">จัดการ Release และอนุมัติการปล่อยแพท (Developer Only)</p>
            </div>
            <div class="bg-red-100 text-red-800 px-4 py-2 rounded-xl font-semibold">
                🔒 Dev Mode: {{ $currentIp }}
            </div>
        </div>
    </div>

    <!-- Current Status -->
    <div class="grid md:grid-cols-3 gap-6 mb-8">
        <!-- Current Version -->
        <div class="glass-fusion rounded-xl shadow p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-gray-900 dark:text-white">เวอร์ชันปัจจุบัน</h3>
                <span class="text-2xl">📦</span>
            </div>
            <p class="text-3xl font-bold text-indigo-600">{{ $currentVersion }}</p>
        </div>

        <!-- Git Status -->
        @if($gitStatus)
        <div class="glass-fusion rounded-xl shadow p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-gray-900 dark:text-white">Git Status</h3>
                <span class="text-2xl">🌿</span>
            </div>
            <div class="space-y-2 text-sm">
                <p><span class="font-medium">Branch:</span> <code class="bg-gray-100/50 dark:bg-gray-800/50 px-2 py-1 rounded">{{ $gitStatus['branch'] }}</code></p>
                <p>
                    <span class="font-medium">Status:</span>
                    @if($gitStatus['has_changes'])
                        <span class="text-orange-600">⚠️ Uncommitted changes</span>
                    @else
                        <span class="text-green-600">✓ Clean</span>
                    @endif
                </p>
            </div>
        </div>
        @endif

        <!-- Release Stats -->
        <div class="glass-fusion rounded-xl shadow p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-gray-900 dark:text-white">Release Stats</h3>
                <span class="text-2xl">📊</span>
            </div>
            <div class="space-y-2 text-sm">
                <p><span class="font-medium">Official:</span> <span class="text-green-600 font-bold">{{ $officialReleases->count() }}</span></p>
                <p><span class="font-medium">Drafts:</span> <span class="text-yellow-600 font-bold">{{ $draftReleases->count() }}</span></p>
                <p><span class="font-medium">Pre-releases:</span> <span class="text-blue-600 font-bold">{{ $preReleases->count() }}</span></p>
            </div>
        </div>
    </div>

    <!-- Recent Commits (Real-time view) -->
    @if(!empty($recentCommits))
    <div class="glass-fusion rounded-xl shadow mb-8" border border-white/20 dark:border-white/10>
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">🔄 Recent Commits (Real-time)</h2>
                <button @click="refreshInfo" class="text-sm text-blue-600 hover:text-blue-700">
                    <span x-show="!refreshing">🔄 Refresh</span>
                    <span x-show="refreshing">⏳ Refreshing...</span>
                </button>
            </div>
        </div>
        <div class="divide-y divide-gray-200">
            @foreach($recentCommits as $commit)
            <div class="p-4 hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 transition">
                <div class="flex items-start gap-4">
                    <code class="bg-gray-100/50 dark:bg-gray-800/50 px-2 py-1 rounded text-sm font-mono">{{ $commit['hash'] }}</code>
                    <div class="flex-1">
                        <p class="text-gray-900 dark:text-white">{{ $commit['message'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $commit['author'] }} • {{ $commit['time'] }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Create New Release -->
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl shadow-lg p-6 mb-8 text-white">
        <h2 class="text-2xl font-bold mb-4">✨ Create New Release</h2>

        <form @submit.prevent="createRelease" class="space-y-4">
            <div class="grid md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-2">Version</label>
                    <input
                        type="text"
                        x-model="newRelease.version"
                        placeholder="v1.2.0"
                        class="w-full px-4 py-2 rounded-xl text-gray-900 dark:text-white"
                        required
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Type</label>
                    <select x-model="newRelease.type" class="w-full px-4 py-2 rounded-xl text-gray-900 dark:text-white" required>
                        <option value="patch">Patch (แก้บัค/ปรับปรุง)</option>
                        <option value="minor">Minor (ฟีเจอร์ใหม่)</option>
                        <option value="major">Major (เปลี่ยนแปลงใหญ่)</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" x-model="newRelease.is_draft" class="rounded">
                        <span class="text-sm font-medium">Create as Draft</span>
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Changelog (ภาษาไทยเท่านั้น)</label>
                <textarea
                    x-model="newRelease.changelog"
                    rows="3"
                    class="w-full px-4 py-2 rounded-xl text-gray-900 dark:text-white"
                    placeholder="เช่น: เพิ่มระบบติดตามผลแบบเรียลไทม์ พร้อมกราฟสถิติแบบโต้ตอบและรองรับหลายสกุลเงิน"
                    required
                ></textarea>
                <p class="text-xs mt-1 text-indigo-100">⚠️ เน้นประโยชน์ที่ผู้ใช้ได้รับ ไม่ต้องระบุรายละเอียดเทคนิคหรือการแก้บัค</p>
            </div>

            <div>
                <button
                    type="submit"
                    :disabled="creating"
                    class="glass-fusion text-indigo-600 px-6 py-3 rounded-xl font-bold hover:bg-indigo-50 transition disabled:opacity-50"
                >
                    <span x-show="!creating">🚀 Create Release</span>
                    <span x-show="creating">⏳ Creating...</span>
                </button>
            </div>
        </form>

        <div x-show="createMessage" class="mt-4 p-4 rounded-xl" :class="createSuccess ? 'bg-green-500' : 'bg-red-500'" x-text="createMessage"></div>
    </div>

    <!-- Tabs for different release types -->
    <div class="mb-6">
        <div class="flex space-x-2 border-b border-gray-200 dark:border-gray-700">
            <button
                @click="activeTab = 'official'"
                :class="activeTab === 'official' ? 'border-b-2 border-indigo-600 text-indigo-600' : 'text-gray-600 dark:text-gray-400'"
                class="px-4 py-2 font-medium"
            >
                ✅ Official Releases ({{ $officialReleases->count() }})
            </button>
            <button
                @click="activeTab = 'draft'"
                :class="activeTab === 'draft' ? 'border-b-2 border-yellow-600 text-yellow-600' : 'text-gray-600 dark:text-gray-400'"
                class="px-4 py-2 font-medium"
            >
                📝 Drafts ({{ $draftReleases->count() }})
            </button>
            <button
                @click="activeTab = 'prerelease'"
                :class="activeTab === 'prerelease' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 dark:text-gray-400'"
                class="px-4 py-2 font-medium"
            >
                ⚠️ Pre-releases ({{ $preReleases->count() }})
            </button>
            <button
                @click="activeTab = 'all'"
                :class="activeTab === 'all' ? 'border-b-2 border-gray-600 text-gray-600 dark:text-gray-400' : 'text-gray-500 dark:text-gray-400'"
                class="px-4 py-2 font-medium"
            >
                🗂️ All ({{ $allReleases->count() }})
            </button>
        </div>
    </div>

    <!-- Official Releases -->
    <div x-show="activeTab === 'official'" class="space-y-4">
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-4">
            <p class="text-sm text-green-800">
                <strong>✅ Official Releases:</strong> เหล่านี้คือเวอร์ชันที่ผู้ใช้เห็นในระบบอัพเดท
            </p>
        </div>

        @forelse($officialReleases as $release)
            @include('admin.dev.releases.partials.release-card', ['release' => $release, 'type' => 'official'])
        @empty
            <div class="glass-fusion rounded-xl shadow p-12 text-center" border border-white/20 dark:border-white/10>
                <p class="text-gray-500 dark:text-gray-400">ยังไม่มี Official Release</p>
            </div>
        @endforelse
    </div>

    <!-- Draft Releases -->
    <div x-show="activeTab === 'draft'" class="space-y-4">
        <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-4">
            <p class="text-sm text-yellow-800">
                <strong>📝 Draft Releases:</strong> ยังไม่เผยแพร่ ผู้ใช้ไม่เห็น ใช้สำหรับทดสอบก่อนปล่อย
            </p>
        </div>

        @forelse($draftReleases as $release)
            @include('admin.dev.releases.partials.release-card', ['release' => $release, 'type' => 'draft'])
        @empty
            <div class="glass-fusion rounded-xl shadow p-12 text-center" border border-white/20 dark:border-white/10>
                <p class="text-gray-500 dark:text-gray-400">ไม่มี Draft Release</p>
            </div>
        @endforelse
    </div>

    <!-- Pre-releases -->
    <div x-show="activeTab === 'prerelease'" class="space-y-4">
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4">
            <p class="text-sm text-blue-800">
                <strong>⚠️ Pre-releases:</strong> เวอร์ชันทดสอบ (beta, alpha) ผู้ใช้ไม่เห็น
            </p>
        </div>

        @forelse($preReleases as $release)
            @include('admin.dev.releases.partials.release-card', ['release' => $release, 'type' => 'prerelease'])
        @empty
            <div class="glass-fusion rounded-xl shadow p-12 text-center" border border-white/20 dark:border-white/10>
                <p class="text-gray-500 dark:text-gray-400">ไม่มี Pre-release</p>
            </div>
        @endforelse
    </div>

    <!-- All Releases -->
    <div x-show="activeTab === 'all'" class="space-y-4">
        @forelse($allReleases as $release)
            @include('admin.dev.releases.partials.release-card', ['release' => $release, 'type' => 'all'])
        @empty
            <div class="glass-fusion rounded-xl shadow p-12 text-center" border border-white/20 dark:border-white/10>
                <p class="text-gray-500 dark:text-gray-400">ไม่มี Release</p>
            </div>
        @endforelse
    </div>
</div>

@push('scripts')
<script>
function devReleaseManager() {
    return {
        activeTab: 'official',
        creating: false,
        createSuccess: false,
        createMessage: '',
        refreshing: false,

        newRelease: {
            version: '',
            type: 'patch',
            changelog: '',
            is_draft: false
        },

        async createRelease() {
            this.creating = true;
            this.createMessage = '';

            try {
                const response = await fetch('{{ route('admin.dev.releases.create') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.newRelease)
                });

                const data = await response.json();

                this.createSuccess = data.success;
                this.createMessage = data.message;

                if (data.success) {
                    // Reset form
                    this.newRelease = {
                        version: '',
                        type: 'patch',
                        changelog: '',
                        is_draft: false
                    };

                    // Reload page after 2 seconds
                    setTimeout(() => window.location.reload(), 2000);
                }
            } catch (error) {
                this.createSuccess = false;
                this.createMessage = 'เกิดข้อผิดพลาด: ' + error.message;
            }

            this.creating = false;
        },

        async publishRelease(tag) {
            if (!confirm(`คุณแน่ใจหรือไม่ที่จะเผยแพร่ Release ${tag}?\n\nผู้ใช้จะเห็น Release นี้ในระบบอัพเดท`)) {
                return;
            }

            try {
                const response = await fetch(`/admin/dev/releases/${tag}/publish`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                }
            } catch (error) {
                alert('เกิดข้อผิดพลาด: ' + error.message);
            }
        },

        async deleteRelease(tag) {
            if (!confirm(`คุณแน่ใจหรือไม่ที่จะลบ Release ${tag}?\n\nการกระทำนี้ไม่สามารถย้อนกลับได้`)) {
                return;
            }

            try {
                const response = await fetch(`/admin/dev/releases/${tag}/delete`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                }
            } catch (error) {
                alert('เกิดข้อผิดพลาด: ' + error.message);
            }
        },

        async refreshInfo() {
            this.refreshing = true;

            try {
                const response = await fetch('{{ route('admin.dev.releases.realtime') }}');
                const data = await response.json();

                // Reload to show fresh data
                window.location.reload();
            } catch (error) {
                alert('เกิดข้อผิดพลาด: ' + error.message);
            }

            this.refreshing = false;
        }
    }
}
</script>
@endpush
@endsection
