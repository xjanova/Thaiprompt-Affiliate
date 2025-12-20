{{-- Add Element Modal --}}
<div x-show="showAddElementModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm" @click.self="showAddElementModal = false">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl p-6 m-4" @click.stop x-transition>
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-plus-circle text-indigo-500 mr-3"></i>
                เพิ่ม Element ใหม่
            </h3>
            <button @click="showAddElementModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                <i class="fas fa-times text-gray-500"></i>
            </button>
        </div>

        {{-- Element Categories --}}
        <div class="space-y-4">
            {{-- Basic --}}
            <div>
                <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-3 uppercase tracking-wider">Basic</h4>
                <div class="grid grid-cols-4 gap-3">
                    <template x-for="elem in basicElements" :key="elem.type">
                        <button @click="createNewElement(elem.type)" class="p-4 border border-gray-200 dark:border-gray-600 rounded-xl hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition text-center group">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center mx-auto mb-2 transition group-hover:scale-110" :style="'background:' + elem.color">
                                <i class="fas text-white text-xl" :class="elem.icon"></i>
                            </div>
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="elem.label"></div>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Advanced --}}
            <div>
                <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-3 uppercase tracking-wider">Advanced</h4>
                <div class="grid grid-cols-4 gap-3">
                    <template x-for="elem in advancedElements" :key="elem.type">
                        <button @click="createNewElement(elem.type)" class="p-4 border border-gray-200 dark:border-gray-600 rounded-xl hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition text-center group">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center mx-auto mb-2 transition group-hover:scale-110" :style="'background:' + elem.color">
                                <i class="fas text-white text-xl" :class="elem.icon"></i>
                            </div>
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="elem.label"></div>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Interactive --}}
            <div>
                <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-3 uppercase tracking-wider">Interactive</h4>
                <div class="grid grid-cols-4 gap-3">
                    <template x-for="elem in interactiveElements" :key="elem.type">
                        <button @click="createNewElement(elem.type)" class="p-4 border border-gray-200 dark:border-gray-600 rounded-xl hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition text-center group">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center mx-auto mb-2 transition group-hover:scale-110" :style="'background:' + elem.color">
                                <i class="fas text-white text-xl" :class="elem.icon"></i>
                            </div>
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="elem.label"></div>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Export Modal --}}
<div x-show="showExportModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm" @click.self="showExportModal = false">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-3xl p-6 m-4" @click.stop x-transition>
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-download text-green-500 mr-3"></i>
                Export Homepage
            </h3>
            <button @click="showExportModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                <i class="fas fa-times text-gray-500"></i>
            </button>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">คัดลอก JSON ด้านล่างเพื่อ backup หรือย้ายไปยังเว็บไซต์อื่น</p>
        <textarea x-ref="exportData" class="w-full h-72 px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 font-mono text-sm focus:ring-2 focus:ring-indigo-500 transition" readonly></textarea>
        <div class="flex justify-end space-x-3 mt-4">
            <button @click="copyExportData()" class="px-5 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition flex items-center">
                <i class="fas fa-copy mr-2"></i> Copy to Clipboard
            </button>
            <button @click="downloadExportData()" class="px-5 py-2.5 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl hover:from-green-600 hover:to-emerald-700 transition flex items-center shadow-lg shadow-green-500/25">
                <i class="fas fa-download mr-2"></i> Download JSON
            </button>
        </div>
    </div>
</div>

{{-- Import Modal --}}
<div x-show="showImportModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm" @click.self="showImportModal = false">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-3xl p-6 m-4" @click.stop x-transition>
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-upload text-blue-500 mr-3"></i>
                Import Homepage
            </h3>
            <button @click="showImportModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                <i class="fas fa-times text-gray-500"></i>
            </button>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">วาง JSON ที่ export มาจากระบบนี้</p>
        <textarea x-model="importData" class="w-full h-72 px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 font-mono text-sm focus:ring-2 focus:ring-indigo-500 transition" placeholder='วาง JSON data ที่นี่...'></textarea>
        <div class="flex items-center justify-between mt-4">
            <label class="flex items-center cursor-pointer">
                <input type="checkbox" x-model="replaceOnImport" class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">ลบข้อมูลเดิมทั้งหมดก่อน import</span>
            </label>
            <div class="flex space-x-3">
                <button @click="showImportModal = false" class="px-5 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                    ยกเลิก
                </button>
                <button @click="importHomepage()" class="px-5 py-2.5 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-xl hover:from-blue-600 hover:to-indigo-700 transition flex items-center shadow-lg shadow-blue-500/25">
                    <i class="fas fa-upload mr-2"></i> Import
                </button>
            </div>
        </div>
    </div>
</div>

{{-- AI Content Generator Modal --}}
<div x-show="showAiGeneratorModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm" @click.self="showAiGeneratorModal = false">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl p-6 m-4" @click.stop x-transition>
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                <div class="w-10 h-10 rounded-xl ai-generate-btn flex items-center justify-center mr-3">
                    <i class="fas fa-magic text-white"></i>
                </div>
                AI Content Generator
            </h3>
            <button @click="showAiGeneratorModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                <i class="fas fa-times text-gray-500"></i>
            </button>
        </div>

        <div class="space-y-4">
            {{-- Content Type --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ประเภทเนื้อหา</label>
                <div class="grid grid-cols-3 gap-2">
                    <button @click="aiContentType = 'headline'" :class="aiContentType === 'headline' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30' : 'border-gray-200 dark:border-gray-600'" class="p-3 border-2 rounded-xl text-center transition">
                        <i class="fas fa-heading text-xl text-indigo-500 mb-1"></i>
                        <div class="text-sm font-medium">Headline</div>
                    </button>
                    <button @click="aiContentType = 'paragraph'" :class="aiContentType === 'paragraph' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30' : 'border-gray-200 dark:border-gray-600'" class="p-3 border-2 rounded-xl text-center transition">
                        <i class="fas fa-paragraph text-xl text-green-500 mb-1"></i>
                        <div class="text-sm font-medium">Paragraph</div>
                    </button>
                    <button @click="aiContentType = 'cta'" :class="aiContentType === 'cta' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30' : 'border-gray-200 dark:border-gray-600'" class="p-3 border-2 rounded-xl text-center transition">
                        <i class="fas fa-bullhorn text-xl text-orange-500 mb-1"></i>
                        <div class="text-sm font-medium">CTA Text</div>
                    </button>
                </div>
            </div>

            {{-- Business Type --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ประเภทธุรกิจ</label>
                <select x-model="aiBusinessType" class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500 transition">
                    <option value="affiliate">Affiliate Marketing</option>
                    <option value="ecommerce">E-Commerce</option>
                    <option value="saas">SaaS / Software</option>
                    <option value="service">บริการทั่วไป</option>
                    <option value="education">การศึกษา</option>
                    <option value="health">สุขภาพและความงาม</option>
                    <option value="finance">การเงินและการลงทุน</option>
                    <option value="travel">ท่องเที่ยว</option>
                    <option value="food">อาหารและเครื่องดื่ม</option>
                    <option value="tech">เทคโนโลยี</option>
                </select>
            </div>

            {{-- Keywords/Topic --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">หัวข้อ / Keywords (ถ้ามี)</label>
                <input type="text" x-model="aiKeywords" class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500 transition" placeholder="เช่น: รายได้เสริม, ออนไลน์, ง่าย">
            </div>

            {{-- Tone --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">โทนการเขียน</label>
                <div class="flex flex-wrap gap-2">
                    <template x-for="tone in ['เป็นทางการ', 'เป็นกันเอง', 'กระตุ้นขาย', 'น่าเชื่อถือ', 'สร้างสรรค์']" :key="tone">
                        <button @click="aiTone = tone" :class="aiTone === tone ? 'bg-indigo-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400'" class="px-4 py-2 rounded-lg text-sm font-medium transition" x-text="tone"></button>
                    </template>
                </div>
            </div>

            {{-- Generated Content --}}
            <div x-show="aiGeneratedContent">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ผลลัพธ์</label>
                <div class="relative">
                    <textarea x-model="aiGeneratedContent" class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 focus:ring-2 focus:ring-indigo-500 transition" rows="4"></textarea>
                    <button @click="useAiContent()" class="absolute bottom-3 right-3 px-3 py-1.5 bg-indigo-500 text-white text-sm rounded-lg hover:bg-indigo-600 transition">
                        <i class="fas fa-check mr-1"></i> ใช้งาน
                    </button>
                </div>
            </div>
        </div>

        <div class="flex justify-end mt-6">
            <button @click="generateAiContent()" :disabled="aiGenerating" class="px-6 py-3 ai-generate-btn text-white rounded-xl hover:opacity-90 transition flex items-center shadow-lg disabled:opacity-50">
                <i class="fas mr-2" :class="aiGenerating ? 'fa-spinner fa-spin' : 'fa-sparkles'"></i>
                <span x-text="aiGenerating ? 'กำลังสร้าง...' : 'สร้างเนื้อหา'"></span>
            </button>
        </div>
    </div>
</div>

{{-- Keyboard Shortcuts Modal --}}
<div x-show="showShortcutsModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm" @click.self="showShortcutsModal = false">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg p-6 m-4" @click.stop x-transition>
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-keyboard text-indigo-500 mr-3"></i>
                Keyboard Shortcuts
            </h3>
            <button @click="showShortcutsModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                <i class="fas fa-times text-gray-500"></i>
            </button>
        </div>

        <div class="space-y-4">
            <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                <span class="text-gray-700 dark:text-gray-300">บันทึก</span>
                <div class="flex items-center space-x-1">
                    <span class="kbd">Ctrl</span>
                    <span class="text-gray-400">+</span>
                    <span class="kbd">S</span>
                </div>
            </div>
            <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                <span class="text-gray-700 dark:text-gray-300">Undo</span>
                <div class="flex items-center space-x-1">
                    <span class="kbd">Ctrl</span>
                    <span class="text-gray-400">+</span>
                    <span class="kbd">Z</span>
                </div>
            </div>
            <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                <span class="text-gray-700 dark:text-gray-300">Redo</span>
                <div class="flex items-center space-x-1">
                    <span class="kbd">Ctrl</span>
                    <span class="text-gray-400">+</span>
                    <span class="kbd">Y</span>
                </div>
            </div>
            <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                <span class="text-gray-700 dark:text-gray-300">ลบ Element ที่เลือก</span>
                <span class="kbd">Delete</span>
            </div>
            <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                <span class="text-gray-700 dark:text-gray-300">Duplicate Element</span>
                <div class="flex items-center space-x-1">
                    <span class="kbd">Ctrl</span>
                    <span class="text-gray-400">+</span>
                    <span class="kbd">D</span>
                </div>
            </div>
            <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                <span class="text-gray-700 dark:text-gray-300">ยกเลิกการเลือก</span>
                <span class="kbd">Esc</span>
            </div>
            <div class="flex items-center justify-between py-2">
                <span class="text-gray-700 dark:text-gray-300">Toggle Grid</span>
                <div class="flex items-center space-x-1">
                    <span class="kbd">Ctrl</span>
                    <span class="text-gray-400">+</span>
                    <span class="kbd">G</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Save Template Modal --}}
<div x-show="showSaveTemplateModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm" @click.self="showSaveTemplateModal = false">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md p-6 m-4" @click.stop x-transition>
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-save text-indigo-500 mr-3"></i>
                บันทึกเป็น Template
            </h3>
            <button @click="showSaveTemplateModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                <i class="fas fa-times text-gray-500"></i>
            </button>
        </div>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชื่อ Template</label>
                <input type="text" x-model="newTemplateName" class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500 transition" placeholder="My Custom Template">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">หมวดหมู่</label>
                <select x-model="newTemplateCategory" class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500 transition">
                    <option value="landing">Landing Page</option>
                    <option value="business">Business</option>
                    <option value="portfolio">Portfolio</option>
                    <option value="ecommerce">E-Commerce</option>
                    <option value="custom">Custom</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">คำอธิบาย (ถ้ามี)</label>
                <textarea x-model="newTemplateDescription" class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500 transition" rows="2" placeholder="รายละเอียดเพิ่มเติม..."></textarea>
            </div>
        </div>

        <div class="flex justify-end space-x-3 mt-6">
            <button @click="showSaveTemplateModal = false" class="px-5 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                ยกเลิก
            </button>
            <button @click="saveAsTemplate()" class="px-5 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-xl hover:from-indigo-600 hover:to-purple-700 transition flex items-center shadow-lg shadow-indigo-500/25">
                <i class="fas fa-save mr-2"></i> บันทึก Template
            </button>
        </div>
    </div>
</div>

{{-- Insert Position Modal - เลือกตำแหน่งแทรก Section --}}
<div x-show="showInsertModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm" @click.self="showInsertModal = false">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md p-6 m-4" @click.stop x-transition>
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center mr-3">
                    <i class="fas fa-layer-group text-white"></i>
                </div>
                เลือกตำแหน่งแทรก Section
            </h3>
            <button @click="showInsertModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                <i class="fas fa-times text-gray-500"></i>
            </button>
        </div>

        {{-- Section Type Badge --}}
        <div class="mb-6">
            <div class="inline-flex items-center px-4 py-2 rounded-xl bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300">
                <i class="fas fa-cube mr-2"></i>
                <span class="font-medium" x-text="sectionTypes[insertType] || 'Section ใหม่'"></span>
            </div>
        </div>

        {{-- Position Options --}}
        <div class="space-y-3 mb-6">
            {{-- First Position --}}
            <label class="flex items-center p-4 border-2 rounded-xl cursor-pointer transition"
                   :class="insertPosition === 'first' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30' : 'border-gray-200 dark:border-gray-600 hover:border-indigo-300'">
                <input type="radio" name="insert_position" value="first" x-model="insertPosition" class="hidden">
                <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/50 flex items-center justify-center mr-4">
                    <i class="fas fa-arrow-up text-green-600 dark:text-green-400"></i>
                </div>
                <div class="flex-1">
                    <div class="font-medium text-gray-900 dark:text-white">บนสุด</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">แทรกที่ตำแหน่งแรกสุด</div>
                </div>
                <i class="fas fa-check text-indigo-500" x-show="insertPosition === 'first'"></i>
            </label>

            {{-- Last Position --}}
            <label class="flex items-center p-4 border-2 rounded-xl cursor-pointer transition"
                   :class="insertPosition === 'last' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30' : 'border-gray-200 dark:border-gray-600 hover:border-indigo-300'">
                <input type="radio" name="insert_position" value="last" x-model="insertPosition" class="hidden">
                <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center mr-4">
                    <i class="fas fa-arrow-down text-blue-600 dark:text-blue-400"></i>
                </div>
                <div class="flex-1">
                    <div class="font-medium text-gray-900 dark:text-white">ล่างสุด</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">แทรกที่ตำแหน่งสุดท้าย</div>
                </div>
                <i class="fas fa-check text-indigo-500" x-show="insertPosition === 'last'"></i>
            </label>

            {{-- After Specific Section --}}
            <label class="flex items-start p-4 border-2 rounded-xl cursor-pointer transition"
                   :class="insertPosition === 'after' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30' : 'border-gray-200 dark:border-gray-600 hover:border-indigo-300'"
                   x-show="sections.length > 0">
                <input type="radio" name="insert_position" value="after" x-model="insertPosition" class="hidden">
                <div class="w-10 h-10 rounded-lg bg-orange-100 dark:bg-orange-900/50 flex items-center justify-center mr-4 flex-shrink-0">
                    <i class="fas fa-arrow-right text-orange-600 dark:text-orange-400"></i>
                </div>
                <div class="flex-1">
                    <div class="font-medium text-gray-900 dark:text-white mb-2">ต่อจาก Section</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-3">เลือก section ที่ต้องการแทรกต่อจาก</div>
                    <select x-model.number="insertAfterId" x-show="insertPosition === 'after'"
                            class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500 transition">
                        <template x-for="section in sections" :key="section.id">
                            <option :value="section.id" x-text="section.name + ' (' + (section.type_label || section.type) + ')'"></option>
                        </template>
                    </select>
                </div>
                <i class="fas fa-check text-indigo-500 flex-shrink-0 mt-2" x-show="insertPosition === 'after'"></i>
            </label>
        </div>

        {{-- Action Buttons --}}
        <div class="flex justify-end space-x-3">
            <button @click="showInsertModal = false" class="px-5 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                ยกเลิก
            </button>
            <button @click="confirmInsertSection()" class="px-5 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-xl hover:from-indigo-600 hover:to-purple-700 transition flex items-center shadow-lg shadow-indigo-500/25">
                <i class="fas fa-plus-circle mr-2"></i> เพิ่ม Section
            </button>
        </div>
    </div>
</div>

{{-- Loading Overlay --}}
<div x-show="loading" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 text-center shadow-2xl">
        <div class="w-16 h-16 rounded-2xl ai-generate-btn flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-spinner fa-spin text-3xl text-white"></i>
        </div>
        <p class="text-gray-700 dark:text-gray-300 font-medium">กำลังโหลด...</p>
    </div>
</div>
