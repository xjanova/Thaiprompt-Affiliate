<script>
/**
 * Homepage Manager Alpine.js Component
 *
 * Visual Editor สำหรับจัดการหน้าแรกของเว็บไซต์
 */
function homepageManager() {
    return {
        // State
        sections: [],
        templates: [],
        selectedSection: null,
        selectedElement: null,
        elementSection: null,

        // UI State
        loading: true,
        saving: false,
        deviceMode: 'desktop',
        zoom: 100,
        showGrid: false,
        leftPanelTab: 'elements',
        rightPanelTab: 'properties',
        searchQuery: '',

        // Modals
        showAddElementModal: false,
        showExportModal: false,
        showImportModal: false,
        showAiGeneratorModal: false,
        showShortcutsModal: false,
        showSaveTemplateModal: false,

        // Import/Export
        importData: '',
        replaceOnImport: false,

        // AI Generator
        aiContentType: 'headline',
        aiBusinessType: 'affiliate',
        aiKeywords: '',
        aiTone: 'กระตุ้นขาย',
        aiGeneratedContent: '',
        aiGenerating: false,

        // Template
        newTemplateName: '',
        newTemplateCategory: 'custom',
        newTemplateDescription: '',

        // History
        history: [],
        historyIndex: -1,

        // Toasts
        toasts: [],

        // Accordion States
        propAccordion: {
            general: true,
            size: false,
            background: true,
            animation: false,
            typography: true,
            colors: false,
            border: false,
            elemAnimation: false,
        },

        // Element Type Definitions
        sectionTypes: {
            hero: 'Hero Section',
            features: 'Features',
            stats: 'Statistics',
            testimonials: 'Testimonials',
            cta: 'Call to Action',
            gallery: 'Gallery',
            pricing: 'Pricing',
            team: 'Team',
            faq: 'FAQ',
            contact: 'Contact',
            custom: 'Custom Section',
        },

        basicElements: [
            { type: 'heading', label: 'Heading', desc: 'H1-H6 Title', icon: 'fa-heading', color: 'linear-gradient(135deg, #6366f1, #8b5cf6)' },
            { type: 'text', label: 'Text', desc: 'Paragraph', icon: 'fa-paragraph', color: 'linear-gradient(135deg, #10b981, #059669)' },
            { type: 'image', label: 'Image', desc: 'รูปภาพ', icon: 'fa-image', color: 'linear-gradient(135deg, #f59e0b, #d97706)' },
            { type: 'button', label: 'Button', desc: 'ปุ่มกด', icon: 'fa-hand-pointer', color: 'linear-gradient(135deg, #ef4444, #dc2626)' },
            { type: 'video', label: 'Video', desc: 'วิดีโอ', icon: 'fa-video', color: 'linear-gradient(135deg, #ec4899, #db2777)' },
            { type: 'icon', label: 'Icon', desc: 'ไอคอน', icon: 'fa-icons', color: 'linear-gradient(135deg, #8b5cf6, #7c3aed)' },
            { type: 'divider', label: 'Divider', desc: 'เส้นแบ่ง', icon: 'fa-minus', color: 'linear-gradient(135deg, #6b7280, #4b5563)' },
            { type: 'spacer', label: 'Spacer', desc: 'ช่องว่าง', icon: 'fa-arrows-alt-v', color: 'linear-gradient(135deg, #9ca3af, #6b7280)' },
        ],

        advancedElements: [
            { type: 'container', label: 'Container', desc: 'กล่องเนื้อหา', icon: 'fa-square', color: 'linear-gradient(135deg, #3b82f6, #2563eb)' },
            { type: 'card', label: 'Card', desc: 'การ์ดข้อมูล', icon: 'fa-id-card', color: 'linear-gradient(135deg, #14b8a6, #0d9488)' },
            { type: 'grid', label: 'Grid', desc: 'Grid Layout', icon: 'fa-th', color: 'linear-gradient(135deg, #a855f7, #9333ea)' },
            { type: 'list', label: 'List', desc: 'รายการ', icon: 'fa-list', color: 'linear-gradient(135deg, #f97316, #ea580c)' },
            { type: 'html', label: 'Custom HTML', desc: 'HTML โค้ด', icon: 'fa-code', color: 'linear-gradient(135deg, #64748b, #475569)' },
            { type: 'map', label: 'Map', desc: 'แผนที่', icon: 'fa-map-marker-alt', color: 'linear-gradient(135deg, #22c55e, #16a34a)' },
            { type: 'social', label: 'Social Links', desc: 'โซเชียล', icon: 'fa-share-alt', color: 'linear-gradient(135deg, #0ea5e9, #0284c7)' },
            { type: 'logo', label: 'Logo', desc: 'โลโก้', icon: 'fa-certificate', color: 'linear-gradient(135deg, #eab308, #ca8a04)' },
        ],

        interactiveElements: [
            { type: 'counter', label: 'Counter', desc: 'ตัวเลขนับ', icon: 'fa-sort-numeric-up', color: 'linear-gradient(135deg, #06b6d4, #0891b2)' },
            { type: 'timer', label: 'Countdown', desc: 'นับถอยหลัง', icon: 'fa-clock', color: 'linear-gradient(135deg, #f43f5e, #e11d48)' },
            { type: 'form', label: 'Form', desc: 'ฟอร์ม', icon: 'fa-envelope-open-text', color: 'linear-gradient(135deg, #8b5cf6, #7c3aed)' },
            { type: 'tabs', label: 'Tabs', desc: 'แท็บเนื้อหา', icon: 'fa-folder', color: 'linear-gradient(135deg, #f59e0b, #d97706)' },
            { type: 'accordion', label: 'Accordion', desc: 'พับ/ขยาย', icon: 'fa-chevron-down', color: 'linear-gradient(135deg, #84cc16, #65a30d)' },
            { type: 'carousel', label: 'Carousel', desc: 'สไลด์', icon: 'fa-images', color: 'linear-gradient(135deg, #ec4899, #db2777)' },
            { type: 'progress', label: 'Progress', desc: 'แถบความคืบหน้า', icon: 'fa-tasks', color: 'linear-gradient(135deg, #10b981, #059669)' },
            { type: 'rating', label: 'Rating', desc: 'ให้คะแนน', icon: 'fa-star', color: 'linear-gradient(135deg, #fbbf24, #f59e0b)' },
        ],

        prebuiltBlocks: {
            'Hero': [
                { name: 'Hero Gradient', preview: 'linear-gradient(135deg, #667eea, #764ba2)', data: null },
                { name: 'Hero Image', preview: 'linear-gradient(135deg, #1e3a5f, #0d1b2a)', data: null },
                { name: 'Hero Video', preview: 'linear-gradient(135deg, #0f172a, #1e293b)', data: null },
            ],
            'Features': [
                { name: '3 Columns', preview: 'linear-gradient(135deg, #f8fafc, #e2e8f0)', data: null },
                { name: '4 Columns', preview: 'linear-gradient(135deg, #ecfdf5, #d1fae5)', data: null },
                { name: 'With Icons', preview: 'linear-gradient(135deg, #fef3c7, #fde68a)', data: null },
            ],
            'Statistics': [
                { name: '4 Numbers', preview: 'linear-gradient(135deg, #1e3a5f, #0d1b2a)', data: null },
                { name: 'With Icons', preview: 'linear-gradient(135deg, #6366f1, #8b5cf6)', data: null },
            ],
            'CTA': [
                { name: 'Simple CTA', preview: 'linear-gradient(135deg, #f8fafc, #e2e8f0)', data: null },
                { name: 'Gradient CTA', preview: 'linear-gradient(135deg, #667eea, #764ba2)', data: null },
            ],
            'Pricing': [
                { name: '3 Plans', preview: 'linear-gradient(135deg, #f0fdf4, #dcfce7)', data: null },
            ],
        },

        gradientPresets: [
            'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
            'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
            'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
            'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
            'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
            'linear-gradient(135deg, #5ee7df 0%, #b490ca 100%)',
            'linear-gradient(135deg, #d299c2 0%, #fef9d7 100%)',
            'linear-gradient(135deg, #1e3a5f 0%, #0d1b2a 100%)',
            'linear-gradient(135deg, #0f172a 0%, #1e293b 100%)',
        ],

        animations: {
            '': 'None',
            'fadeIn': 'Fade In',
            'fadeInUp': 'Fade In Up',
            'fadeInDown': 'Fade In Down',
            'fadeInLeft': 'Fade In Left',
            'fadeInRight': 'Fade In Right',
            'zoomIn': 'Zoom In',
            'slideInUp': 'Slide In Up',
            'bounce': 'Bounce',
            'pulse': 'Pulse',
        },

        /**
         * Initialize component
         */
        async init() {
            await this.loadSections();
            await this.loadTemplates();
            this.setupKeyboardShortcuts();
            this.setupSortable();
            this.loading = false;
        },

        /**
         * Load sections from API
         */
        async loadSections() {
            try {
                const response = await fetch('{{ route("admin.homepage-manager.sections.data") }}');
                const data = await response.json();
                if (data.success) {
                    this.sections = data.data.map(s => ({ ...s, expanded: true }));
                    this.saveToHistory();
                }
            } catch (error) {
                console.error('Error loading sections:', error);
                this.showToast('ไม่สามารถโหลดข้อมูลได้', 'error');
            }
        },

        /**
         * Load templates
         */
        async loadTemplates() {
            try {
                const response = await fetch('{{ route("admin.homepage-manager.templates.get") }}');
                const data = await response.json();
                if (data.success) {
                    this.templates = data.data || [];
                }
            } catch (error) {
                console.error('Error loading templates:', error);
            }
        },

        /**
         * Setup keyboard shortcuts
         */
        setupKeyboardShortcuts() {
            document.addEventListener('keydown', (e) => {
                // Ctrl+S: Save
                if (e.ctrlKey && e.key === 's') {
                    e.preventDefault();
                    this.saveAll();
                }
                // Ctrl+Z: Undo
                if (e.ctrlKey && e.key === 'z') {
                    e.preventDefault();
                    this.undo();
                }
                // Ctrl+Y: Redo
                if (e.ctrlKey && e.key === 'y') {
                    e.preventDefault();
                    this.redo();
                }
                // Delete: Remove selected element
                if (e.key === 'Delete' && this.selectedElement) {
                    e.preventDefault();
                    this.deleteElement(this.selectedElement);
                }
                // Ctrl+D: Duplicate
                if (e.ctrlKey && e.key === 'd' && this.selectedElement) {
                    e.preventDefault();
                    this.duplicateElement(this.selectedElement);
                }
                // Escape: Deselect
                if (e.key === 'Escape') {
                    this.selectedSection = null;
                    this.selectedElement = null;
                }
                // Ctrl+G: Toggle grid
                if (e.ctrlKey && e.key === 'g') {
                    e.preventDefault();
                    this.showGrid = !this.showGrid;
                }
            });
        },

        /**
         * Setup SortableJS for drag and drop
         */
        setupSortable() {
            setTimeout(() => {
                const container = document.getElementById('sections-container');
                if (container) {
                    new Sortable(container, {
                        animation: 200,
                        handle: '.section-handle',
                        ghostClass: 'sortable-ghost',
                        chosenClass: 'sortable-chosen',
                        onEnd: (evt) => {
                            const movedSection = this.sections.splice(evt.oldIndex, 1)[0];
                            this.sections.splice(evt.newIndex, 0, movedSection);
                            this.updateSectionOrder();
                        }
                    });
                }
            }, 100);
        },

        /**
         * Get section icon based on type
         */
        getSectionIcon(type) {
            const icons = {
                hero: 'fa-star',
                features: 'fa-th-large',
                stats: 'fa-chart-bar',
                testimonials: 'fa-quote-right',
                cta: 'fa-bullhorn',
                gallery: 'fa-images',
                pricing: 'fa-tags',
                team: 'fa-users',
                faq: 'fa-question-circle',
                contact: 'fa-envelope',
                custom: 'fa-puzzle-piece',
            };
            return icons[type] || 'fa-layer-group';
        },

        /**
         * Get element icon
         */
        getElementIcon(type) {
            const allElements = [...this.basicElements, ...this.advancedElements, ...this.interactiveElements];
            const elem = allElements.find(e => e.type === type);
            return elem?.icon || 'fa-cube';
        },

        /**
         * Get section style
         */
        getSectionStyle(section) {
            let style = {};

            if (section.min_height) style.minHeight = section.min_height;
            if (section.max_height) style.maxHeight = section.max_height;
            if (section.padding) style.padding = section.padding;

            const bg = section.background || {};
            if (bg.type === 'color' && bg.color) {
                style.backgroundColor = bg.color;
            } else if (bg.type === 'gradient' && bg.gradient) {
                style.background = bg.gradient;
            } else if (bg.type === 'image' && bg.image) {
                style.backgroundImage = `url(${bg.image})`;
                style.backgroundPosition = bg.position || 'center center';
                style.backgroundSize = bg.size || 'cover';
            }

            return style;
        },

        /**
         * Get element style
         */
        getElementStyle(element) {
            let style = {};

            // Typography
            const typo = element.typography || {};
            if (typo.font_size) style.fontSize = typo.font_size;
            if (typo.font_weight) style.fontWeight = typo.font_weight;
            if (typo.line_height) style.lineHeight = typo.line_height;
            if (typo.text_align) style.textAlign = typo.text_align;

            // Colors
            const colors = element.colors || {};
            if (colors.color) style.color = colors.color;
            if (colors.background_color) style.backgroundColor = colors.background_color;

            // Border
            const border = element.border || {};
            if (border.width && border.width !== '0') {
                style.borderWidth = border.width;
                style.borderStyle = border.style || 'solid';
            }
            if (border.radius) style.borderRadius = border.radius;

            // Spacing
            const spacing = element.spacing || {};
            if (spacing.padding) style.padding = spacing.padding;
            if (spacing.margin) style.margin = spacing.margin;

            // Size
            const size = element.size || {};
            if (size.width && size.width !== 'auto') style.width = size.width;
            if (size.max_width) style.maxWidth = size.max_width;

            return style;
        },

        /**
         * Render element HTML
         */
        renderElement(element) {
            const content = element.content || {};
            const type = element.type;
            const text = content.text || '';

            switch (type) {
                case 'heading':
                    const level = element.settings?.heading_level || 'h2';
                    return `<${level}>${this.escapeHtml(text)}</${level}>`;

                case 'text':
                    return `<p>${this.escapeHtml(text)}</p>`;

                case 'button':
                    return `<a href="${content.link_url || '#'}" class="inline-block" target="${content.link_target || '_self'}">${this.escapeHtml(text)}</a>`;

                case 'image':
                    return `<img src="${content.image_url || '/images/placeholder.png'}" alt="${content.alt || ''}" class="max-w-full h-auto">`;

                case 'video':
                    if (content.video_url?.includes('youtube')) {
                        const videoId = this.getYoutubeId(content.video_url);
                        return `<iframe src="https://www.youtube.com/embed/${videoId}" class="w-full aspect-video" frameborder="0" allowfullscreen></iframe>`;
                    }
                    return `<video src="${content.video_url}" controls class="w-full"></video>`;

                case 'icon':
                    return `<i class="${content.icon_class || 'fas fa-star'} text-4xl"></i>`;

                case 'divider':
                    return `<hr class="border-gray-300 dark:border-gray-600">`;

                case 'spacer':
                    return `<div style="height: ${element.size?.height || '40px'}"></div>`;

                case 'html':
                case 'container':
                    return text;

                case 'counter':
                    return `<div class="text-center"><span class="text-4xl font-bold">${content.number || '0'}</span><span class="block text-sm text-gray-500">${this.escapeHtml(text)}</span></div>`;

                case 'social':
                    return `<div class="flex space-x-4"><i class="fab fa-facebook text-2xl text-blue-600"></i><i class="fab fa-twitter text-2xl text-sky-500"></i><i class="fab fa-instagram text-2xl text-pink-500"></i><i class="fab fa-line text-2xl text-green-500"></i></div>`;

                default:
                    return `<div class="p-4 bg-gray-100 dark:bg-gray-700 rounded-lg text-center text-gray-500">${type} element</div>`;
            }
        },

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        getYoutubeId(url) {
            const match = url.match(/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/);
            return match ? match[1] : '';
        },

        /**
         * Add new section
         */
        async addSection(type = 'custom') {
            const newSection = {
                name: this.sectionTypes[type] || 'New Section',
                type: type,
                type_label: this.sectionTypes[type] || 'Custom Section',
                order: this.sections.length,
                is_active: true,
                is_fullwidth: type === 'hero',
                min_height: type === 'hero' ? '500px' : '300px',
                padding: '60px 20px',
                background: {
                    type: type === 'hero' ? 'gradient' : 'color',
                    color: '#ffffff',
                    color_dark: '#1f2937',
                    gradient: type === 'hero' ? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' : null,
                },
                animation: { type: 'fadeIn', delay: 0 },
                elements: [],
                expanded: true,
            };

            try {
                const response = await fetch('{{ route("admin.homepage-manager.sections.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(newSection)
                });
                const data = await response.json();
                if (data.success) {
                    this.sections.push({ ...data.data, expanded: true });
                    this.saveToHistory();
                    this.showToast('เพิ่ม Section สำเร็จ', 'success');
                }
            } catch (error) {
                console.error('Error adding section:', error);
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
        },

        /**
         * Select section
         */
        selectSection(section) {
            this.selectedSection = section;
            this.selectedElement = null;
            this.rightPanelTab = 'properties';
        },

        /**
         * Select element
         */
        selectElement(element, section) {
            this.selectedElement = element;
            this.selectedSection = section;
            this.elementSection = section;
            this.rightPanelTab = 'properties';
        },

        /**
         * Update section
         */
        async updateSection() {
            if (!this.selectedSection) return;

            try {
                const updateUrl = '{{ route("admin.homepage-manager.sections.update", ["section" => "__SECTION_ID__"]) }}'.replace('__SECTION_ID__', this.selectedSection.id);
                await fetch(updateUrl, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.selectedSection)
                });
                this.saveToHistory();
            } catch (error) {
                console.error('Error updating section:', error);
            }
        },

        /**
         * Update element
         */
        async updateElement() {
            if (!this.selectedElement || !this.elementSection) return;

            try {
                const updateUrl = '{{ route("admin.homepage-manager.elements.update", ["element" => "__ELEMENT_ID__"]) }}'.replace('__ELEMENT_ID__', this.selectedElement.id);
                await fetch(updateUrl, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.selectedElement)
                });
                this.saveToHistory();
            } catch (error) {
                console.error('Error updating element:', error);
            }
        },

        /**
         * Move section up/down
         */
        moveSection(index, direction) {
            const newIndex = index + direction;
            if (newIndex < 0 || newIndex >= this.sections.length) return;

            const temp = this.sections[index];
            this.sections[index] = this.sections[newIndex];
            this.sections[newIndex] = temp;
            this.updateSectionOrder();
        },

        /**
         * Update section order
         */
        async updateSectionOrder() {
            // ส่งเฉพาะ array ของ section IDs ตามลำดับใหม่
            const sections = this.sections.map(s => s.id);

            try {
                await fetch('{{ route("admin.homepage-manager.sections.reorder") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ sections })
                });
                this.saveToHistory();
            } catch (error) {
                console.error('Error reordering sections:', error);
            }
        },

        /**
         * Duplicate section
         */
        async duplicateSection(section) {
            try {
                const duplicateUrl = '{{ route("admin.homepage-manager.sections.duplicate", ["section" => "__SECTION_ID__"]) }}'.replace('__SECTION_ID__', section.id);
                const response = await fetch(duplicateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    this.sections.push({ ...data.data, expanded: true });
                    this.saveToHistory();
                    this.showToast('ทำสำเนา Section สำเร็จ', 'success');
                }
            } catch (error) {
                console.error('Error duplicating section:', error);
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
        },

        /**
         * Toggle section visibility
         */
        async toggleSection(section) {
            section.is_active = !section.is_active;
            this.selectedSection = section;
            await this.updateSection();
        },

        /**
         * Delete section
         */
        async deleteSection(section) {
            if (!confirm('ต้องการลบ Section นี้?')) return;

            try {
                const deleteUrl = '{{ route("admin.homepage-manager.sections.destroy", ["section" => "__SECTION_ID__"]) }}'.replace('__SECTION_ID__', section.id);
                await fetch(deleteUrl, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                this.sections = this.sections.filter(s => s.id !== section.id);
                this.selectedSection = null;
                this.saveToHistory();
                this.showToast('ลบ Section สำเร็จ', 'success');
            } catch (error) {
                console.error('Error deleting section:', error);
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
        },

        /**
         * Add element to section
         */
        addElement(section) {
            this.selectedSection = section;
            this.showAddElementModal = true;
        },

        /**
         * Create new element
         */
        async createNewElement(type) {
            if (!this.selectedSection) return;

            const newElement = {
                section_id: this.selectedSection.id,
                name: this.getElementLabel(type),
                type: type,
                type_label: this.getElementLabel(type),
                order: this.selectedSection.elements?.length || 0,
                is_active: true,
                content: { text: '', image_url: '', link_url: '', link_target: '_self' },
                typography: { font_size: '16px', font_weight: '400', text_align: 'left', line_height: '1.5' },
                colors: { color: '#000000', background_color: '' },
                border: { width: '0', style: 'solid', radius: '0' },
                spacing: { padding: '0', margin: '0' },
                animation: { type: '', delay: 0, duration: 500 },
                settings: type === 'heading' ? { heading_level: 'h2' } : {},
            };

            try {
                const storeUrl = '{{ route("admin.homepage-manager.elements.store", ["section" => "__SECTION_ID__"]) }}'.replace('__SECTION_ID__', this.selectedSection.id);
                const response = await fetch(storeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(newElement)
                });
                const data = await response.json();
                if (data.success) {
                    if (!this.selectedSection.elements) {
                        this.selectedSection.elements = [];
                    }
                    this.selectedSection.elements.push(data.data);
                    this.saveToHistory();
                    this.showAddElementModal = false;
                    this.showToast('เพิ่ม Element สำเร็จ', 'success');
                }
            } catch (error) {
                console.error('Error creating element:', error);
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
        },

        getElementLabel(type) {
            const allElements = [...this.basicElements, ...this.advancedElements, ...this.interactiveElements];
            const elem = allElements.find(e => e.type === type);
            return elem?.label || type;
        },

        /**
         * Duplicate element
         */
        async duplicateElement(element) {
            try {
                const duplicateUrl = '{{ route("admin.homepage-manager.elements.duplicate", ["element" => "__ELEMENT_ID__"]) }}'.replace('__ELEMENT_ID__', element.id);
                const response = await fetch(duplicateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (data.success && this.elementSection) {
                    this.elementSection.elements.push(data.data);
                    this.saveToHistory();
                    this.showToast('ทำสำเนา Element สำเร็จ', 'success');
                }
            } catch (error) {
                console.error('Error duplicating element:', error);
            }
        },

        /**
         * Delete element
         */
        async deleteElement(element) {
            if (!confirm('ต้องการลบ Element นี้?')) return;

            try {
                const deleteUrl = '{{ route("admin.homepage-manager.elements.destroy", ["element" => "__ELEMENT_ID__"]) }}'.replace('__ELEMENT_ID__', element.id);
                await fetch(deleteUrl, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                if (this.elementSection) {
                    this.elementSection.elements = this.elementSection.elements.filter(e => e.id !== element.id);
                }
                this.selectedElement = null;
                this.saveToHistory();
                this.showToast('ลบ Element สำเร็จ', 'success');
            } catch (error) {
                console.error('Error deleting element:', error);
            }
        },

        /**
         * Drag element type
         */
        dragElementType(event, type) {
            event.dataTransfer.setData('element-type', type);
        },

        /**
         * Drop element
         */
        async dropElement(event, section) {
            event.currentTarget.classList.remove('ring-2', 'ring-indigo-500', 'ring-dashed');

            const type = event.dataTransfer.getData('element-type');
            if (type) {
                this.selectedSection = section;
                await this.createNewElement(type);
            }
        },

        /**
         * Save all
         */
        async saveAll() {
            this.saving = true;
            try {
                await fetch('{{ route("admin.homepage-manager.save-all") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ sections: this.sections })
                });
                this.showToast('บันทึกสำเร็จ', 'success');
            } catch (error) {
                console.error('Error saving:', error);
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
            this.saving = false;
        },

        /**
         * Clear all
         */
        async clearAll() {
            if (!confirm('ต้องการลบ Section ทั้งหมด? การกระทำนี้ไม่สามารถย้อนกลับได้')) return;

            this.loading = true;
            try {
                await fetch('{{ route("admin.homepage-manager.clear") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                this.sections = [];
                this.selectedSection = null;
                this.selectedElement = null;
                this.showToast('ลบข้อมูลทั้งหมดสำเร็จ', 'success');
            } catch (error) {
                console.error('Error clearing:', error);
            }
            this.loading = false;
        },

        /**
         * Export
         */
        showExport() {
            this.$refs.exportData.value = JSON.stringify(this.sections, null, 2);
            this.showExportModal = true;
        },

        copyExportData() {
            this.$refs.exportData.select();
            document.execCommand('copy');
            this.showToast('คัดลอกสำเร็จ', 'success');
        },

        downloadExportData() {
            const blob = new Blob([this.$refs.exportData.value], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'homepage-export.json';
            a.click();
            URL.revokeObjectURL(url);
        },

        /**
         * Import
         */
        async importHomepage() {
            try {
                const data = JSON.parse(this.importData);
                await fetch('{{ route("admin.homepage-manager.import") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        sections: data,
                        replace: this.replaceOnImport
                    })
                });
                await this.loadSections();
                this.showImportModal = false;
                this.importData = '';
                this.showToast('Import สำเร็จ', 'success');
            } catch (error) {
                console.error('Error importing:', error);
                this.showToast('ข้อมูล JSON ไม่ถูกต้อง', 'error');
            }
        },

        /**
         * Import template
         */
        async importTemplate(templateId) {
            if (!confirm('ต้องการใช้ Template นี้? ข้อมูลเดิมจะถูกแทนที่')) return;

            this.loading = true;
            try {
                const importUrl = '{{ route("admin.homepage-manager.templates.import", ["template" => "__TEMPLATE_ID__"]) }}'.replace('__TEMPLATE_ID__', templateId);
                await fetch(importUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                await this.loadSections();
                this.showToast('ใช้ Template สำเร็จ', 'success');
            } catch (error) {
                console.error('Error applying template:', error);
            }
            this.loading = false;
        },

        /**
         * Save as template
         */
        async saveAsTemplate() {
            if (!this.newTemplateName) {
                this.showToast('กรุณาใส่ชื่อ Template', 'warning');
                return;
            }

            try {
                await fetch('{{ route("admin.homepage-manager.templates.save") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        name: this.newTemplateName,
                        category: this.newTemplateCategory,
                        description: this.newTemplateDescription,
                        sections: this.sections
                    })
                });
                await this.loadTemplates();
                this.showSaveTemplateModal = false;
                this.newTemplateName = '';
                this.newTemplateDescription = '';
                this.showToast('บันทึก Template สำเร็จ', 'success');
            } catch (error) {
                console.error('Error saving template:', error);
            }
        },

        /**
         * AI Content Generator
         */
        async generateAiContent() {
            this.aiGenerating = true;

            // Simulate AI generation (ในระบบจริงจะเรียก API)
            const templates = {
                headline: {
                    affiliate: ['เริ่มต้นสร้างรายได้ออนไลน์วันนี้', 'ระบบ Affiliate ที่ดีที่สุดในไทย', 'สร้างธุรกิจออนไลน์ของคุณ'],
                    ecommerce: ['ช้อปสินค้าคุณภาพ ราคาถูก', 'ส่งฟรีทั่วประเทศ เมื่อซื้อครบ ฿500', 'สินค้าใหม่มาแรง ลดสูงสุด 70%'],
                    saas: ['ซอฟต์แวร์ที่ช่วยธุรกิจคุณเติบโต', 'ทดลองใช้ฟรี 14 วัน ไม่ต้องใช้บัตรเครดิต', 'อัตโนมัติทุกกระบวนการทางธุรกิจ'],
                },
                paragraph: {
                    affiliate: ['ระบบ Affiliate Marketing ที่ช่วยให้คุณสร้างรายได้แบบ Passive Income ได้จริง ด้วยเครื่องมือที่ครบครัน ใช้งานง่าย'],
                    ecommerce: ['เราคัดสรรสินค้าคุณภาพจากแบรนด์ชั้นนำ พร้อมบริการจัดส่งที่รวดเร็ว และการดูแลหลังการขายอย่างครบวงจร'],
                },
                cta: {
                    affiliate: ['เริ่มต้นฟรี', 'สมัครวันนี้', 'รับคอมมิชชั่นทันที'],
                    ecommerce: ['ช้อปเลย', 'ดูสินค้า', 'สั่งซื้อตอนนี้'],
                }
            };

            setTimeout(() => {
                const type = this.aiContentType;
                const biz = this.aiBusinessType;
                const options = templates[type]?.[biz] || templates[type]?.['affiliate'] || ['เนื้อหาตัวอย่าง'];
                this.aiGeneratedContent = options[Math.floor(Math.random() * options.length)];
                this.aiGenerating = false;
            }, 1500);
        },

        useAiContent() {
            if (this.selectedElement && this.aiGeneratedContent) {
                this.selectedElement.content.text = this.aiGeneratedContent;
                this.updateElement();
                this.showAiGeneratorModal = false;
                this.aiGeneratedContent = '';
            }
        },

        /**
         * History
         */
        saveToHistory() {
            this.history = this.history.slice(0, this.historyIndex + 1);
            this.history.push(JSON.stringify(this.sections));
            this.historyIndex = this.history.length - 1;
            if (this.history.length > 50) {
                this.history.shift();
                this.historyIndex--;
            }
        },

        undo() {
            if (this.historyIndex > 0) {
                this.historyIndex--;
                this.sections = JSON.parse(this.history[this.historyIndex]);
            }
        },

        redo() {
            if (this.historyIndex < this.history.length - 1) {
                this.historyIndex++;
                this.sections = JSON.parse(this.history[this.historyIndex]);
            }
        },

        /**
         * Toast notification
         */
        showToast(message, type = 'info') {
            const toast = { message, type, show: true };
            this.toasts.push(toast);
            setTimeout(() => {
                const index = this.toasts.indexOf(toast);
                if (index > -1) this.toasts.splice(index, 1);
            }, 3000);
        },

        /**
         * Prebuilt block
         */
        addPrebuiltBlock(block) {
            // ใช้ข้อมูล block.data ถ้ามี หรือสร้าง section ใหม่
            if (block.data) {
                // Import block data
            } else {
                this.addSection(block.name.toLowerCase().includes('hero') ? 'hero' :
                               block.name.toLowerCase().includes('feature') ? 'features' :
                               block.name.toLowerCase().includes('stat') ? 'stats' :
                               block.name.toLowerCase().includes('cta') ? 'cta' :
                               block.name.toLowerCase().includes('pricing') ? 'pricing' : 'custom');
            }
        },

        /**
         * Edit element content (double-click)
         */
        editElementContent(element) {
            // Focus on content editing
            this.selectElement(element, this.elementSection);
            this.rightPanelTab = 'properties';
        },

        /**
         * Move element layer
         */
        moveElementLayer(element, direction) {
            if (!this.elementSection?.elements) return;
            const index = this.elementSection.elements.findIndex(e => e.id === element.id);
            const newIndex = index + direction;
            if (newIndex < 0 || newIndex >= this.elementSection.elements.length) return;

            const temp = this.elementSection.elements[index];
            this.elementSection.elements[index] = this.elementSection.elements[newIndex];
            this.elementSection.elements[newIndex] = temp;
        },

        /**
         * Resize element
         */
        startResize(event, element, handle) {
            // Placeholder for resize functionality
            console.log('Start resize:', handle);
        },

        /**
         * Upload background image for section
         */
        uploadBackgroundImage() {
            this.uploadImage((url) => {
                if (this.selectedSection) {
                    if (!this.selectedSection.background) {
                        this.selectedSection.background = {};
                    }
                    this.selectedSection.background.image = url;
                    this.updateSection();
                    this.showToast('อัปโหลดรูปภาพสำเร็จ', 'success');
                }
            });
        },

        /**
         * Upload image for element
         */
        uploadElementImage() {
            this.uploadImage((url) => {
                if (this.selectedElement) {
                    if (!this.selectedElement.content) {
                        this.selectedElement.content = {};
                    }
                    this.selectedElement.content.image_url = url;
                    this.updateElement();
                    this.showToast('อัปโหลดรูปภาพสำเร็จ', 'success');
                }
            });
        },

        /**
         * Generic upload image function
         */
        uploadImage(callback) {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*';
            input.onchange = async (e) => {
                const file = e.target.files[0];
                if (!file) return;

                // ตรวจสอบขนาดไฟล์ (max 10MB)
                if (file.size > 10 * 1024 * 1024) {
                    this.showToast('ไฟล์มีขนาดใหญ่เกิน 10MB', 'error');
                    return;
                }

                const formData = new FormData();
                formData.append('image', file);

                try {
                    const response = await fetch('{{ route("admin.homepage-manager.upload") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: formData
                    });
                    const data = await response.json();
                    if (data.success) {
                        callback(data.url);
                    } else {
                        this.showToast(data.message || 'อัปโหลดล้มเหลว', 'error');
                    }
                } catch (error) {
                    console.error('Upload error:', error);
                    this.showToast('เกิดข้อผิดพลาดในการอัปโหลด', 'error');
                }
            };
            input.click();
        },
    };
}
</script>
