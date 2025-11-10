<div class="bg-white rounded-lg shadow p-6">
    <div class="flex items-start justify-between">
        <div class="flex-1">
            <!-- Release Header -->
            <div class="flex items-center gap-3 mb-3">
                <h3 class="text-xl font-bold text-gray-800">{{ $release['tag_name'] }}</h3>

                <!-- Status Badges -->
                @if($release['draft'])
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-800">
                        📝 DRAFT
                    </span>
                @endif

                @if($release['prerelease'])
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800">
                        ⚠️ PRE-RELEASE
                    </span>
                @endif

                @if(!$release['draft'] && !$release['prerelease'])
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800">
                        ✅ OFFICIAL
                    </span>
                @endif
            </div>

            <!-- Release Name -->
            @if($release['name'] && $release['name'] !== $release['tag_name'])
                <p class="text-lg font-medium text-gray-700 mb-2">{{ $release['name'] }}</p>
            @endif

            <!-- Release Body/Changelog -->
            @if($release['body'])
                <div class="prose prose-sm max-w-none mb-4 text-gray-600">
                    {{ Str::limit($release['body'], 300) }}
                </div>
            @endif

            <!-- Release Info -->
            <div class="flex items-center gap-6 text-sm text-gray-500">
                <span>📅 {{ \Carbon\Carbon::parse($release['published_at'])->format('d M Y H:i') }}</span>
                <span>👤 {{ $release['author'] }}</span>
                <a href="{{ $release['html_url'] }}" target="_blank" class="text-blue-600 hover:text-blue-700">
                    🔗 GitHub
                </a>
            </div>
        </div>

        <!-- Actions -->
        <div class="ml-6 flex flex-col gap-2">
            @if($release['draft'])
                <!-- Draft Actions -->
                <button
                    @click="publishRelease('{{ $release['tag_name'] }}')"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm font-medium"
                >
                    ✅ Publish
                </button>
                <button
                    @click="deleteRelease('{{ $release['tag_name'] }}')"
                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm font-medium"
                >
                    🗑️ Delete
                </button>
            @else
                <!-- Published Release Actions -->
                <a
                    href="{{ $release['html_url'] }}"
                    target="_blank"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium text-center"
                >
                    👁️ View
                </a>
                <a
                    href="{{ $release['zipball_url'] }}"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm font-medium text-center"
                >
                    📥 Download
                </a>
                @if($type !== 'official')
                <button
                    @click="deleteRelease('{{ $release['tag_name'] }}')"
                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm font-medium"
                >
                    🗑️ Delete
                </button>
                @endif
            @endif
        </div>
    </div>
</div>
