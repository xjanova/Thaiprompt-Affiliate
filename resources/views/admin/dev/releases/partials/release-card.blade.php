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
            <div class="flex items-center gap-6 text-sm text-gray-500 mb-4">
                <span>📅 {{ \Carbon\Carbon::parse($release['published_at'])->format('d M Y H:i') }}</span>
                <span>👤 {{ $release['author'] }}</span>
                <a href="{{ $release['html_url'] }}" target="_blank" class="text-blue-600 hover:text-blue-700">
                    🔗 GitHub
                </a>
            </div>

            <!-- File Changes Section -->
            @if(isset($release['file_changes']) && $release['file_changes']['total'] > 0)
                <div x-data="{ showFiles: false }" class="border-t pt-4">
                    <button
                        @click="showFiles = !showFiles"
                        class="flex items-center gap-2 text-sm font-semibold text-gray-700 hover:text-gray-900 transition"
                    >
                        <svg x-show="!showFiles" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        <svg x-show="showFiles" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                        <span>📁 ไฟล์ที่เปลี่ยนแปลง ({{ $release['file_changes']['total'] }} ไฟล์)</span>

                        @if($release['file_changes']['blocked_count'] > 0)
                            <span class="px-2 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800">
                                🚫 {{ $release['file_changes']['blocked_count'] }} ไฟล์ถูก Block
                            </span>
                        @endif

                        @if($release['file_changes']['sensitive_count'] > 0)
                            <span class="px-2 py-1 rounded-full text-xs font-bold bg-orange-100 text-orange-800">
                                ⚠️ {{ $release['file_changes']['sensitive_count'] }} ไฟล์ละเอียดอ่อน
                            </span>
                        @endif
                    </button>

                    <!-- File List -->
                    <div x-show="showFiles" x-collapse class="mt-4">
                        <!-- Warning Alert -->
                        @if($release['file_changes']['blocked_count'] > 0)
                            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-bold text-red-800">⚠️ มีไฟล์ที่ไม่ควรหลุดไปฝั่ง Production!</h3>
                                        <p class="text-sm text-red-700 mt-1">
                                            ไฟล์เหล่านี้ถูก Block โดย .gitattributes และจะไม่ปรากฏใน ZIP ที่ผู้ใช้ดาวน์โหลด
                                            <br>ตรวจสอบให้แน่ใจว่าไม่มีข้อมูลสำคัญหลุดไป!
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Info about previous version -->
                        <p class="text-xs text-gray-500 mb-3">
                            📊 เปรียบเทียบกับ: <code class="bg-gray-100 px-2 py-1 rounded">{{ $release['file_changes']['previous_tag'] }}</code>
                        </p>

                        <!-- File List Table -->
                        <div class="max-h-96 overflow-y-auto bg-gray-50 rounded-lg border">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-100 sticky top-0">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">File Path</th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Category</th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Security</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($release['file_changes']['files'] as $file)
                                        <tr class="hover:bg-gray-50 transition {{ $file['is_blocked'] ? 'bg-red-50' : ($file['is_sensitive'] ? 'bg-orange-50' : '') }}">
                                            <!-- Status -->
                                            <td class="px-4 py-2 whitespace-nowrap">
                                                <span class="px-2 py-1 rounded text-xs font-medium
                                                    {{ $file['status_code'] === 'A' ? 'bg-green-100 text-green-800' : '' }}
                                                    {{ $file['status_code'] === 'M' ? 'bg-blue-100 text-blue-800' : '' }}
                                                    {{ $file['status_code'] === 'D' ? 'bg-red-100 text-red-800' : '' }}
                                                    {{ !in_array($file['status_code'], ['A', 'M', 'D']) ? 'bg-gray-100 text-gray-800' : '' }}
                                                ">
                                                    {{ $file['status'] }}
                                                </span>
                                            </td>

                                            <!-- File Path -->
                                            <td class="px-4 py-2">
                                                <code class="text-xs {{ $file['is_blocked'] ? 'text-red-700 font-bold' : 'text-gray-700' }}">
                                                    {{ $file['path'] }}
                                                </code>
                                            </td>

                                            <!-- Category -->
                                            <td class="px-4 py-2 whitespace-nowrap text-xs text-gray-600">
                                                {{ $file['category'] }}
                                            </td>

                                            <!-- Security Status -->
                                            <td class="px-4 py-2 whitespace-nowrap">
                                                @if($file['is_blocked'])
                                                    <span class="px-2 py-1 rounded text-xs font-bold bg-red-100 text-red-800">
                                                        🚫 BLOCKED
                                                    </span>
                                                @elseif($file['is_sensitive'])
                                                    <span class="px-2 py-1 rounded text-xs font-bold bg-orange-100 text-orange-800">
                                                        ⚠️ Sensitive
                                                    </span>
                                                @else
                                                    <span class="px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                                                        ✅ Safe
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Legend -->
                        <div class="mt-4 p-3 bg-blue-50 rounded-lg">
                            <p class="text-xs font-semibold text-blue-800 mb-2">📌 คำอธิบาย:</p>
                            <div class="grid grid-cols-2 gap-2 text-xs text-blue-700">
                                <div><span class="font-bold">🚫 BLOCKED:</span> ไฟล์จะไม่ถูกรวมใน production release (ตาม .gitattributes)</div>
                                <div><span class="font-bold">⚠️ Sensitive:</span> ไฟล์ละเอียดอ่อน ให้ตรวจสอบความปลอดภัย</div>
                                <div><span class="font-bold">✅ Safe:</span> ไฟล์ปลอดภัย จะถูกรวมใน production</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
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
