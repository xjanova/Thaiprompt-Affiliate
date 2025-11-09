@props([
    'name' => 'icon',
    'value' => '',
    'type' => 'emoji', // emoji, system, upload
    'category' => 'system',
    'label' => 'เลือกไอคอน',
    'required' => false,
])

@php
    use App\Helpers\IconHelper;

    // Parse current value
    $currentType = $type;
    $currentValue = $value;
    $currentCategory = $category;

    // Auto-detect type from value
    if ($value) {
        if (preg_match('/[\x{1F300}-\x{1F9FF}]/u', $value)) {
            $currentType = 'emoji';
        } elseif (str_starts_with($value, 'http') || str_starts_with($value, '/')) {
            $currentType = 'upload';
        } else {
            $currentType = 'system';
        }
    }

    // Get available system icons
    $systemIcons = IconHelper::list($currentCategory);
    $categories = IconHelper::categories();
@endphp

<div
    x-data="{
        type: '{{ $currentType }}',
        value: '{{ $currentValue }}',
        category: '{{ $currentCategory }}',
        systemIcons: @js($systemIcons),
        allSystemIcons: @js(array_combine(array_keys($categories), array_map(fn($cat) => IconHelper::list($cat), array_keys($categories)))),
        showPicker: false,
        emojiSearch: '',
        popularEmojis: ['📊', '👥', '🛒', '📦', '💰', '📈', '⚙️', '🏠', '🔔', '📧', '💼', '🎁', '⭐', '🏪', '🚀', '🎨', '🎓', '🔧', '📱', '💳', '🌐', '🔮', '🤖', '📝', '🔒'],

        changeType(newType) {
            this.type = newType;
            if (newType === 'emoji' && !this.value) {
                this.value = '📄';
            }
        },

        selectEmoji(emoji) {
            this.value = emoji;
            this.showPicker = false;
        },

        selectSystemIcon(icon) {
            this.value = icon.name;
            this.showPicker = false;
        },

        loadSystemIcons(cat) {
            this.category = cat;
            this.systemIcons = this.allSystemIcons[cat] || [];
        },

        get filteredEmojis() {
            if (!this.emojiSearch) return this.popularEmojis;
            return this.popularEmojis.filter(e => true); // In real app, filter by search
        },

        get displayValue() {
            if (this.type === 'emoji') return this.value;
            if (this.type === 'upload') return '🖼️';
            if (this.type === 'system') return this.value || '📄';
            return '📄';
        }
    }"
    class="space-y-2">

    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
        {{ $label }}
        @if($required)
            <span class="text-red-500">*</span>
        @endif
    </label>

    <!-- Icon Type Tabs -->
    <div class="flex gap-2 mb-3">
        <button
            type="button"
            @click="changeType('emoji')"
            :class="type === 'emoji' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300'"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors hover:opacity-80">
            😀 Emoji
        </button>
        <button
            type="button"
            @click="changeType('system')"
            :class="type === 'system' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300'"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors hover:opacity-80">
            🎨 ระบบ
        </button>
        <button
            type="button"
            @click="changeType('upload')"
            :class="type === 'upload' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300'"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors hover:opacity-80">
            📤 อัพโหลด
        </button>
    </div>

    <!-- Current Icon Preview -->
    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-slate-700 rounded-lg border-2 border-gray-200 dark:border-slate-600">
        <div class="flex items-center justify-center w-16 h-16 bg-white dark:bg-slate-800 rounded-lg shadow-sm">
            <template x-if="type === 'emoji'">
                <span class="text-3xl" x-text="displayValue"></span>
            </template>
            <template x-if="type === 'system' && value">
                <img :src="`/icons/${category}/${value}.svg`" class="w-12 h-12" onerror="this.src='/icons/system/default.svg'">
            </template>
            <template x-if="type === 'upload' && value">
                <img :src="value" class="w-12 h-12 object-cover rounded">
            </template>
        </div>
        <div class="flex-1">
            <p class="text-sm font-medium text-gray-900 dark:text-white">ไอคอนปัจจุบัน</p>
            <p class="text-xs text-gray-600 dark:text-gray-400" x-text="value || 'ยังไม่ได้เลือก'"></p>
        </div>
        <button
            type="button"
            @click="showPicker = !showPicker"
            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium">
            <span x-show="!showPicker">เลือก</span>
            <span x-show="showPicker">ปิด</span>
        </button>
    </div>

    <!-- Icon Picker Modal -->
    <div
        x-show="showPicker"
        x-transition
        class="p-4 bg-white dark:bg-slate-800 rounded-lg border-2 border-indigo-200 dark:border-indigo-700 shadow-lg max-h-96 overflow-y-auto">

        <!-- Emoji Picker -->
        <div x-show="type === 'emoji'" class="space-y-3">
            <input
                type="text"
                x-model="value"
                placeholder="พิมพ์ emoji หรือข้อความ..."
                class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500">

            <div class="grid grid-cols-8 gap-2">
                <template x-for="emoji in popularEmojis" :key="emoji">
                    <button
                        type="button"
                        @click="selectEmoji(emoji)"
                        class="p-2 text-2xl hover:bg-indigo-50 dark:hover:bg-slate-700 rounded-lg transition-colors"
                        x-text="emoji"></button>
                </template>
            </div>

            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                💡 คุณสามารถคัดลอก emoji จาก <a href="https://emojipedia.org" target="_blank" class="text-indigo-600 hover:underline">Emojipedia</a> มาวางได้เลย
            </p>
        </div>

        <!-- System Icons Picker -->
        <div x-show="type === 'system'" class="space-y-3">
            <!-- Category Tabs -->
            <div class="flex gap-2 overflow-x-auto pb-2">
                <template x-for="(catName, catKey) in {{ json_encode($categories) }}" :key="catKey">
                    <button
                        type="button"
                        @click="loadSystemIcons(catKey)"
                        :class="category === catKey ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300'"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap transition-colors"
                        x-text="catName"></button>
                </template>
            </div>

            <!-- Icons Grid -->
            <div class="grid grid-cols-6 gap-2">
                <template x-for="icon in systemIcons" :key="icon.name">
                    <button
                        type="button"
                        @click="selectSystemIcon(icon)"
                        class="p-3 hover:bg-indigo-50 dark:hover:bg-slate-700 rounded-lg transition-colors border border-gray-200 dark:border-slate-600"
                        :title="icon.name">
                        <img :src="icon.url" :alt="icon.name" class="w-8 h-8 mx-auto">
                    </button>
                </template>
            </div>

            <p class="text-xs text-gray-500 dark:text-gray-400" x-show="systemIcons.length === 0">
                ไม่พบไอคอนในหมวดนี้
            </p>
        </div>

        <!-- Upload Picker -->
        <div x-show="type === 'upload'" class="space-y-3">
            <input
                type="file"
                accept="image/svg+xml,image/png,image/jpeg,image/webp"
                @change="
                    const file = $event.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = (e) => { value = e.target.result; };
                        reader.readAsDataURL(file);
                    }
                "
                class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500">

            <p class="text-xs text-gray-500 dark:text-gray-400">
                รองรับ: SVG, PNG, JPG, WEBP (แนะนำ SVG สำหรับความคมชัด)
            </p>

            <div x-show="value" class="p-3 bg-gray-50 dark:bg-slate-700 rounded-lg">
                <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">Preview:</p>
                <img :src="value" class="w-20 h-20 object-contain mx-auto border border-gray-200 dark:border-slate-600 rounded">
            </div>
        </div>
    </div>

    <!-- Hidden Inputs -->
    <input type="hidden" :name="`${name}`" x-model="value">
    <input type="hidden" :name="`${name}_type`" x-model="type">
    <input type="hidden" :name="`${name}_category`" x-model="category" x-show="type === 'system'">
</div>
