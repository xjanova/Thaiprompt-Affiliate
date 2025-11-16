/**
 * Alpine.js x-sortable Directive
 *
 * ใช้ SortableJS กับ Alpine.js สำหรับ drag & drop
 *
 * @example พื้นฐาน
 * <div x-sortable>
 *   <div>Item 1</div>
 *   <div>Item 2</div>
 * </div>
 *
 * @example พร้อม options
 * <div x-sortable="{ animation: 150, handle: '.drag-handle', onEnd: handleSort }">
 *   <div><span class="drag-handle">⠿</span> Item 1</div>
 *   <div><span class="drag-handle">⠿</span> Item 2</div>
 * </div>
 *
 * @example เก็บ order ลง localStorage
 * <div x-sortable="{ storageKey: 'dashboard-layout' }">
 *   <div data-id="card-1">Card 1</div>
 *   <div data-id="card-2">Card 2</div>
 * </div>
 */

import Sortable from 'sortablejs';

export default function (Alpine) {
    Alpine.directive('sortable', (el, { expression }, { evaluate, cleanup }) => {
        // ประเมินค่า options จาก expression
        let options = {};
        if (expression) {
            options = evaluate(expression) || {};
        }

        // Default options สำหรับ V3 design
        const defaultOptions = {
            animation: 200,
            easing: 'cubic-bezier(0.4, 0, 0.2, 1)',
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            delay: 0,
            delayOnTouchOnly: true,
            touchStartThreshold: 5,
            forceFallback: false,
            fallbackClass: 'sortable-fallback',
            fallbackOnBody: false,
            fallbackTolerance: 0,
            scroll: true,
            scrollSensitivity: 30,
            scrollSpeed: 10,
            bubbleScroll: true,
        };

        // รวม options
        const sortableOptions = {
            ...defaultOptions,
            ...options,
        };

        // ถ้ามี storageKey ให้โหลด order จาก localStorage
        const storageKey = options.storageKey;
        if (storageKey) {
            const savedOrder = localStorage.getItem(storageKey);
            if (savedOrder) {
                try {
                    const order = JSON.parse(savedOrder);
                    reorderElements(el, order);
                } catch (e) {
                    console.error('❌ ไม่สามารถโหลด sortable order จาก localStorage:', e);
                }
            }
        }

        // เพิ่ม onEnd handler เพื่อบันทึก order
        const originalOnEnd = sortableOptions.onEnd;
        sortableOptions.onEnd = function (evt) {
            // บันทึก order ลง localStorage ถ้ามี storageKey
            if (storageKey) {
                const order = getElementOrder(el);
                localStorage.setItem(storageKey, JSON.stringify(order));
                console.log('💾 บันทึก sortable order:', order);
            }

            // เรียก original onEnd handler ถ้ามี
            if (originalOnEnd) {
                originalOnEnd.call(this, evt);
            }

            // Dispatch custom event
            el.dispatchEvent(new CustomEvent('sortable-update', {
                detail: {
                    oldIndex: evt.oldIndex,
                    newIndex: evt.newIndex,
                    item: evt.item,
                    order: getElementOrder(el),
                },
                bubbles: true,
            }));
        };

        // สร้าง Sortable instance
        const sortable = Sortable.create(el, sortableOptions);

        // Cleanup เมื่อ element ถูกลบ
        cleanup(() => {
            sortable.destroy();
        });
    });
}

/**
 * ดึง order ของ elements (ใช้ data-id attribute)
 *
 * @param {HTMLElement} container
 * @returns {string[]}
 */
function getElementOrder(container) {
    const elements = Array.from(container.children);
    return elements.map(el => el.dataset.id || el.id || el.textContent.trim());
}

/**
 * เรียงลำดับ elements ตาม order array
 *
 * @param {HTMLElement} container
 * @param {string[]} order
 */
function reorderElements(container, order) {
    const elements = Array.from(container.children);

    // สร้าง map สำหรับค้นหา element ตาม id
    const elementMap = new Map();
    elements.forEach(el => {
        const id = el.dataset.id || el.id || el.textContent.trim();
        elementMap.set(id, el);
    });

    // เรียงลำดับใหม่ตาม order
    const fragment = document.createDocumentFragment();
    order.forEach(id => {
        const el = elementMap.get(id);
        if (el) {
            fragment.appendChild(el);
        }
    });

    // ใส่ elements ที่ไม่อยู่ใน order ต่อท้าย
    elements.forEach(el => {
        if (!fragment.contains(el) && el.parentNode === container) {
            fragment.appendChild(el);
        }
    });

    // Replace container children
    container.innerHTML = '';
    container.appendChild(fragment);
}
