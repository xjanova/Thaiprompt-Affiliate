@extends('layouts.admin-v4')

@section('title', 'Rich Menu Editor - แม่หมอจันทรา')

@section('content')
{{-- คอนเทนเนอร์หลัก — Alpine root (คง x-data/x-init เดิม 100%) --}}
<div x-data="richMenuEditor()" x-init="loadConfig()" style="display:flex;flex-direction:column;gap:18px;">

    {{-- ===== Header นวลทองคำ ===== --}}
    <div style="display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:16px;">
        <div>
            <div class="tp-muted" style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;">
                หลังบ้าน · ระบบดูดวง · Rich Menu
            </div>
            <h1 class="tp-num" style="font-size:28px;margin:6px 0 4px;">
                <i class="fas fa-palette" style="color:var(--accent2);margin-right:8px;"></i>Rich Menu Editor
            </h1>
            <div class="tp-muted" style="font-size:14px;">แก้ไขปุ่ม สี ข้อความ Rich Menu แม่หมอจันทรา</div>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:10px;">
            @if(Route::has('admin.fortune.rich-menu.index'))
            <a href="{{ route('admin.fortune.rich-menu.index') }}" class="tp-btn">
                <i class="fas fa-paper-plane"></i> Deploy
            </a>
            @endif
            @if(Route::has('admin.fortune.dashboard'))
            <a href="{{ route('admin.fortune.dashboard') }}" class="tp-btn">
                <i class="fas fa-chart-line"></i> แดชบอร์ด
            </a>
            @endif
        </div>
    </div>

    {{-- ===== Tab Navigation ===== --}}
    <div class="tp-card" style="padding:8px;">
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
            <button @click="activeTab = 'config'"
                    :class="activeTab === 'config' ? 'tp-btn tp-btn-primary' : 'tp-btn'"
                    style="flex:1;min-width:160px;justify-content:center;">
                <i class="fas fa-pen-ruler"></i> Config Editor
            </button>
            <button @click="activeTab = 'custom'"
                    :class="activeTab === 'custom' ? 'tp-btn tp-btn-primary' : 'tp-btn'"
                    style="flex:1;min-width:160px;justify-content:center;">
                <i class="fas fa-image"></i> Custom Upload
            </button>
        </div>
    </div>

    {{-- ========== Tab 1: Config Editor ========== --}}
    {{-- หมายเหตุ: x-show+x-cloak ครอบ wrapper เปล่า (default block) — กริดอยู่ชั้นใน
         เพื่อกัน gotcha theme-v4: inline display:grid + x-show จะถูก Alpine cache แล้วคืนเป็น block (กริดหาย) --}}
    <div x-show="activeTab === 'config'" x-cloak>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(420px,1fr));gap:18px;">

        {{-- Editor Panel (ซ้าย) --}}
        <div style="display:flex;flex-direction:column;gap:18px;">

            {{-- Theme Settings --}}
            <div class="tp-card">
                <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                    <i class="fas fa-palette" style="color:var(--accent2);"></i> ตั้งค่า Theme
                </div>

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;">
                    {{-- Gradient Start --}}
                    <div>
                        <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">สีพื้นหลัง (บน)</label>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <input type="color" x-model="config.theme.bg_gradient_start"
                                   style="width:40px;height:40px;border-radius:10px;cursor:pointer;border:1px solid var(--sd);background:transparent;">
                            <div class="tp-well tp-input" style="padding:0;flex:1;">
                                <input type="text" x-model="config.theme.bg_gradient_start"
                                       style="width:100%;background:transparent;border:0;outline:0;padding:10px 12px;color:var(--ink);font-size:13px;font-family:monospace;">
                            </div>
                        </div>
                    </div>

                    {{-- Gradient End --}}
                    <div>
                        <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">สีพื้นหลัง (ล่าง)</label>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <input type="color" x-model="config.theme.bg_gradient_end"
                                   style="width:40px;height:40px;border-radius:10px;cursor:pointer;border:1px solid var(--sd);background:transparent;">
                            <div class="tp-well tp-input" style="padding:0;flex:1;">
                                <input type="text" x-model="config.theme.bg_gradient_end"
                                       style="width:100%;background:transparent;border:0;outline:0;padding:10px 12px;color:var(--ink);font-size:13px;font-family:monospace;">
                            </div>
                        </div>
                    </div>

                    {{-- Grid Line Color --}}
                    <div>
                        <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">สีเส้นแบ่ง</label>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <input type="color" x-model="config.theme.grid_line_color"
                                   style="width:40px;height:40px;border-radius:10px;cursor:pointer;border:1px solid var(--sd);background:transparent;">
                            <div class="tp-well tp-input" style="padding:0;flex:1;">
                                <input type="text" x-model="config.theme.grid_line_color"
                                       style="width:100%;background:transparent;border:0;outline:0;padding:10px 12px;color:var(--ink);font-size:13px;font-family:monospace;">
                            </div>
                        </div>
                    </div>

                    {{-- Branding Color --}}
                    <div>
                        <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">สี Branding</label>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <input type="color" x-model="config.theme.branding_color"
                                   style="width:40px;height:40px;border-radius:10px;cursor:pointer;border:1px solid var(--sd);background:transparent;">
                            <div class="tp-well tp-input" style="padding:0;flex:1;">
                                <input type="text" x-model="config.theme.branding_color"
                                       style="width:100%;background:transparent;border:0;outline:0;padding:10px 12px;color:var(--ink);font-size:13px;font-family:monospace;">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Branding Text --}}
                <div style="margin-top:14px;">
                    <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">ข้อความ Branding</label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="text" x-model="config.theme.branding_text"
                               style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                    </div>
                </div>

                {{-- Toggles --}}
                <div style="margin-top:14px;display:flex;flex-wrap:wrap;gap:22px;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" x-model="config.theme.show_stars"
                               style="width:16px;height:16px;cursor:pointer;accent-color:var(--accent2);">
                        <span class="tp-muted" style="font-size:14px;">แสดงดาว</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" x-model="config.theme.show_moon"
                               style="width:16px;height:16px;cursor:pointer;accent-color:var(--accent2);">
                        <span class="tp-muted" style="font-size:14px;">แสดงพระจันทร์</span>
                    </label>
                </div>
            </div>

            {{-- Button Editor --}}
            <div class="tp-card">
                <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                    <i class="fas fa-toggle-on" style="color:var(--accent2);"></i>
                    แก้ไขปุ่ม (<span x-text="config.buttons.length"></span> ปุ่ม)
                </div>

                {{-- Button Tabs --}}
                <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px;">
                    <template x-for="(btn, idx) in config.buttons" :key="idx">
                        <button @click="activeButton = idx"
                                :class="activeButton === idx ? 'tp-btn tp-btn-primary tp-btn-sm' : 'tp-btn tp-btn-sm'">
                            <span x-text="'ปุ่ม ' + (idx + 1)"></span>
                            <span style="opacity:.75;" x-text="': ' + (btn.label || '-')"></span>
                        </button>
                    </template>
                </div>

                {{-- Active Button Editor --}}
                <template x-if="config.buttons[activeButton]">
                    <div class="tp-inset" style="display:flex;flex-direction:column;gap:14px;padding:16px;border-radius:14px;">
                        {{-- Label + Subtitle --}}
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;">
                            <div>
                                <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">ข้อความหลัก</label>
                                <div class="tp-well tp-input" style="padding:0;">
                                    <input type="text" x-model="config.buttons[activeButton].label"
                                           style="width:100%;background:transparent;border:0;outline:0;padding:10px 12px;color:var(--ink);font-size:14px;">
                                </div>
                            </div>
                            <div>
                                <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">ข้อความรอง</label>
                                <div class="tp-well tp-input" style="padding:0;">
                                    <input type="text" x-model="config.buttons[activeButton].subtitle"
                                           style="width:100%;background:transparent;border:0;outline:0;padding:10px 12px;color:var(--ink);font-size:14px;">
                                </div>
                            </div>
                        </div>

                        {{-- Extra Text --}}
                        <div>
                            <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">ข้อความเพิ่มเติม (บรรทัด 3)</label>
                            <div class="tp-well tp-input" style="padding:0;">
                                <input type="text" x-model="config.buttons[activeButton].extra_text" placeholder="(ไม่บังคับ)"
                                       style="width:100%;background:transparent;border:0;outline:0;padding:10px 12px;color:var(--ink);font-size:14px;">
                            </div>
                        </div>

                        {{-- Icon + Glow --}}
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;">
                            <div>
                                <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">ไอคอน</label>
                                <div class="tp-well tp-input" style="padding:0;">
                                    <select x-model="config.buttons[activeButton].icon"
                                            style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;cursor:pointer;">
                                        <option value="crystal_ball">🔮 ลูกแก้ว (Crystal Ball)</option>
                                        <option value="star">⭐ ดาว (Star)</option>
                                        <option value="scroll">📜 ม้วนกระดาษ (Scroll)</option>
                                        <option value="status">✅ สถานะ (Status)</option>
                                        <option value="chart">📊 กราฟ (Chart)</option>
                                        <option value="warning">⚠️ คำเตือน (Warning)</option>
                                        <option value="info">ℹ️ ข้อมูล (Info)</option>
                                    </select>
                                </div>
                            </div>
                            <div style="display:flex;align-items:flex-end;">
                                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                                    <input type="checkbox" x-model="config.buttons[activeButton].glow"
                                           style="width:16px;height:16px;cursor:pointer;accent-color:var(--accent2);">
                                    <span class="tp-muted" style="font-size:14px;">เอฟเฟกต์เรืองแสง (Glow)</span>
                                </label>
                            </div>
                        </div>

                        {{-- Colors --}}
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;">
                            <div>
                                <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">สีข้อความ</label>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <input type="color" x-model="config.buttons[activeButton].text_color"
                                           style="width:34px;height:34px;border-radius:9px;cursor:pointer;border:1px solid var(--sd);background:transparent;">
                                    <div class="tp-well tp-input" style="padding:0;flex:1;">
                                        <input type="text" x-model="config.buttons[activeButton].text_color"
                                               style="width:100%;background:transparent;border:0;outline:0;padding:8px 10px;color:var(--ink);font-size:12px;font-family:monospace;">
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">สี Subtitle</label>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <input type="color" x-model="config.buttons[activeButton].subtitle_color"
                                           style="width:34px;height:34px;border-radius:9px;cursor:pointer;border:1px solid var(--sd);background:transparent;">
                                    <div class="tp-well tp-input" style="padding:0;flex:1;">
                                        <input type="text" x-model="config.buttons[activeButton].subtitle_color"
                                               style="width:100%;background:transparent;border:0;outline:0;padding:8px 10px;color:var(--ink);font-size:12px;font-family:monospace;">
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">สีพื้นปุ่ม</label>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <input type="color"
                                           :value="config.buttons[activeButton].bg_color || '#000000'"
                                           @input="config.buttons[activeButton].bg_color = $event.target.value"
                                           style="width:34px;height:34px;border-radius:9px;cursor:pointer;border:1px solid var(--sd);background:transparent;">
                                    <div class="tp-well tp-input" style="padding:0;flex:1;">
                                        <input type="text"
                                               :value="config.buttons[activeButton].bg_color || ''"
                                               @input="config.buttons[activeButton].bg_color = $event.target.value || null"
                                               placeholder="ไม่มี"
                                               style="width:100%;background:transparent;border:0;outline:0;padding:8px 10px;color:var(--ink);font-size:12px;font-family:monospace;">
                                    </div>
                                </div>
                                <button @click="config.buttons[activeButton].bg_color = null"
                                        style="margin-top:5px;font-size:12px;color:#d9534f;background:transparent;border:0;cursor:pointer;">ล้างสีพื้น</button>
                            </div>
                        </div>

                        {{-- Font Sizes --}}
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;">
                            <div>
                                <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">
                                    ขนาดตัวอักษร: <span style="font-family:monospace;color:var(--accent2);" x-text="config.buttons[activeButton].font_size + 'px'"></span>
                                </label>
                                <input type="range" x-model.number="config.buttons[activeButton].font_size"
                                       min="24" max="96" step="2"
                                       style="width:100%;height:8px;border-radius:8px;cursor:pointer;accent-color:var(--accent2);">
                            </div>
                            <div>
                                <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">
                                    ขนาด Subtitle: <span style="font-family:monospace;color:var(--accent2);" x-text="config.buttons[activeButton].subtitle_size + 'px'"></span>
                                </label>
                                <input type="range" x-model.number="config.buttons[activeButton].subtitle_size"
                                       min="16" max="64" step="2"
                                       style="width:100%;height:8px;border-radius:8px;cursor:pointer;accent-color:var(--accent2);">
                            </div>
                        </div>

                        {{-- Action --}}
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;">
                            <div>
                                <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Action Type</label>
                                <div class="tp-well tp-input" style="padding:0;">
                                    <select x-model="config.buttons[activeButton].action_type"
                                            style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;cursor:pointer;">
                                        <option value="message">Message (ส่งข้อความ)</option>
                                        <option value="postback">Postback (ส่ง data)</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Action Data</label>
                                <div class="tp-well tp-input" style="padding:0;">
                                    <input type="text" x-model="config.buttons[activeButton].action_data"
                                           style="width:100%;background:transparent;border:0;outline:0;padding:10px 12px;color:var(--ink);font-size:14px;font-family:monospace;">
                                </div>
                            </div>
                            <div>
                                <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Display Text</label>
                                <div class="tp-well tp-input" style="padding:0;">
                                    <input type="text" x-model="config.buttons[activeButton].display_text" placeholder="(postback เท่านั้น)"
                                           style="width:100%;background:transparent;border:0;outline:0;padding:10px 12px;color:var(--ink);font-size:14px;">
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Preview Panel (ขวา) --}}
        <div style="display:flex;flex-direction:column;gap:18px;">
            <div class="tp-card tp-raise" style="position:sticky;top:16px;">
                <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                    <i class="fas fa-eye" style="color:var(--accent2);"></i> Live Preview
                </div>

                {{-- Preview Image --}}
                <div class="tp-inset" style="border-radius:14px;overflow:hidden;min-height:200px;display:flex;align-items:center;justify-content:center;padding:0;">
                    <template x-if="previewUrl">
                        <img :src="previewUrl + '?t=' + Date.now()" alt="Preview Rich Menu"
                             style="width:100%;border-radius:14px;display:block;">
                    </template>
                    <template x-if="!previewUrl && !previewLoading">
                        <div style="text-align:center;padding:48px 12px;" class="tp-muted">
                            <div style="font-size:34px;margin-bottom:8px;"><i class="fas fa-image"></i></div>
                            <p>กด "Preview" เพื่อดูตัวอย่าง</p>
                        </div>
                    </template>
                    <template x-if="previewLoading">
                        <div style="text-align:center;padding:48px 12px;">
                            <i class="fas fa-spinner fa-spin" style="font-size:28px;color:var(--accent2);margin-bottom:10px;"></i>
                            <p class="tp-muted" style="font-size:14px;">กำลังสร้าง preview...</p>
                        </div>
                    </template>
                </div>

                {{-- Action Buttons --}}
                <div style="margin-top:14px;display:flex;flex-wrap:wrap;gap:10px;">
                    <button @click="generatePreview()" :disabled="loading"
                            class="tp-btn" style="flex:1;min-width:130px;justify-content:center;">
                        <template x-if="previewLoading"><i class="fas fa-spinner fa-spin"></i></template>
                        <i class="fas fa-image"></i> Preview
                    </button>
                    <button @click="saveConfigDraft()" :disabled="loading"
                            class="tp-btn" style="flex:1;min-width:130px;justify-content:center;">
                        <i class="fas fa-floppy-disk"></i> Save Draft
                    </button>
                    <button @click="deployFromEditor('config')" :disabled="loading"
                            class="tp-btn tp-btn-primary" style="flex:1;min-width:150px;justify-content:center;">
                        <template x-if="deployLoading"><i class="fas fa-spinner fa-spin"></i></template>
                        <i class="fas fa-rocket"></i> Deploy to LINE
                    </button>
                </div>
            </div>
        </div>
    </div>
    </div>

    {{-- ========== Tab 2: Custom Upload ========== --}}
    {{-- กัน gotcha เดียวกัน: x-show ครอบ wrapper เปล่า, กริดอยู่ชั้นใน --}}
    <div x-show="activeTab === 'custom'" x-cloak>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(420px,1fr));gap:18px;">

        {{-- Upload Panel (ซ้าย) --}}
        <div style="display:flex;flex-direction:column;gap:18px;">

            {{-- Upload Zone --}}
            <div class="tp-card">
                <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                    <i class="fas fa-folder-open" style="color:var(--accent2);"></i> อัปโหลดภาพ Rich Menu
                </div>

                <div class="tp-inset"
                     style="border:2px dashed var(--sd);border-radius:14px;padding:32px;text-align:center;cursor:pointer;transition:border-color .2s;"
                     @click="$refs.fileInput.click()"
                     @dragover.prevent="dragOver = true"
                     @dragleave.prevent="dragOver = false"
                     @drop.prevent="handleFileDrop($event)"
                     :style="dragOver ? 'border-color:var(--accent2);' : ''">
                    <input type="file" x-ref="fileInput" @change="handleFileSelect($event)"
                           accept="image/png,image/jpeg" style="display:none;">
                    <div style="font-size:34px;margin-bottom:12px;color:var(--accent2);"><i class="fas fa-upload"></i></div>
                    <p style="color:var(--ink);font-weight:600;">คลิกหรือลากไฟล์มาวาง</p>
                    <p class="tp-muted" style="font-size:13px;margin-top:4px;">PNG/JPG ขนาด 2500x1686 หรือ 2500x843</p>
                </div>

                <template x-if="uploadLoading">
                    <div style="margin-top:14px;display:flex;align-items:center;gap:10px;color:var(--accent2);">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span style="font-size:14px;font-weight:600;">กำลังอัปโหลด...</span>
                    </div>
                </template>
            </div>

            {{-- Areas Editor --}}
            <div class="tp-card">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px;flex-wrap:wrap;">
                    <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;">
                        <i class="fas fa-vector-square" style="color:var(--accent2);"></i> พื้นที่คลิก (Areas)
                    </div>
                    <button @click="useDefaultAreas()" class="tp-btn tp-btn-sm">
                        ใช้ Default 6 ปุ่ม
                    </button>
                </div>

                {{-- Areas List --}}
                <div style="display:flex;flex-direction:column;gap:12px;max-height:384px;overflow-y:auto;">
                    <template x-for="(area, idx) in customAreas" :key="idx">
                        <div class="tp-inset" style="border-radius:12px;padding:12px;display:flex;align-items:flex-start;gap:12px;">
                            <div style="flex:1;display:flex;flex-direction:column;gap:8px;">
                                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                    <span class="tp-pill tp-pill-gold" style="font-size:11px;" x-text="'Area ' + (idx + 1)"></span>
                                    <span class="tp-muted" style="font-size:11px;font-family:monospace;"
                                          x-text="area.bounds.x + ',' + area.bounds.y + ' ' + area.bounds.width + 'x' + area.bounds.height"></span>
                                </div>
                                <div style="display:grid;grid-template-columns:1fr 2fr;gap:8px;">
                                    <div class="tp-well tp-input" style="padding:0;">
                                        <select x-model="area.action.type"
                                                style="width:100%;background:transparent;border:0;outline:0;padding:7px 9px;color:var(--ink);font-size:12px;cursor:pointer;">
                                            <option value="message">Message</option>
                                            <option value="postback">Postback</option>
                                        </select>
                                    </div>
                                    <div class="tp-well tp-input" style="padding:0;">
                                        <input type="text"
                                               :value="area.action.type === 'message' ? area.action.text : area.action.data"
                                               @input="area.action.type === 'message' ? (area.action.text = $event.target.value) : (area.action.data = $event.target.value)"
                                               placeholder="Action data"
                                               style="width:100%;background:transparent;border:0;outline:0;padding:7px 9px;color:var(--ink);font-size:12px;font-family:monospace;">
                                    </div>
                                </div>
                            </div>
                            <button @click="customAreas.splice(idx, 1)"
                                    class="tp-icon-btn" style="color:#d9534f;flex-shrink:0;">
                                <i class="fas fa-xmark"></i>
                            </button>
                        </div>
                    </template>
                </div>

                <template x-if="customAreas.length === 0">
                    <p class="tp-muted" style="font-size:13px;text-align:center;padding:16px 0;">ยังไม่มี area — กด "ใช้ Default 6 ปุ่ม" หรือวาดบนภาพ</p>
                </template>
            </div>
        </div>

        {{-- Preview + Canvas Panel (ขวา) --}}
        <div style="display:flex;flex-direction:column;gap:18px;">
            <div class="tp-card tp-raise" style="position:sticky;top:16px;">
                <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                    <i class="fas fa-image" style="color:var(--accent2);"></i> ภาพที่อัปโหลด
                </div>

                {{-- Canvas Area --}}
                <div class="tp-inset" style="position:relative;border-radius:14px;overflow:hidden;min-height:200px;padding:0;">
                    <template x-if="customImageUrl">
                        <div style="position:relative;">
                            <img :src="customImageUrl" alt="Custom Rich Menu" style="width:100%;border-radius:14px;display:block;"
                                 x-ref="customImage"
                                 @load="canvasReady = true">

                            {{-- Area Overlays --}}
                            <template x-for="(area, idx) in customAreas" :key="'overlay-' + idx">
                                <div style="position:absolute;border:2px solid #d9534f;background:rgba(217,83,79,.1);display:flex;align-items:center;justify-content:center;cursor:pointer;"
                                     :style="getAreaOverlayStyle(area.bounds)"
                                     :title="'Area ' + (idx + 1) + ': ' + (area.action.text || area.action.data || '')">
                                    <span style="color:#d9534f;font-weight:700;font-size:12px;background:rgba(255,255,255,.85);padding:0 4px;border-radius:4px;"
                                          x-text="idx + 1"></span>
                                </div>
                            </template>
                        </div>
                    </template>
                    <template x-if="!customImageUrl">
                        <div style="text-align:center;padding:48px 12px;" class="tp-muted">
                            <div style="font-size:34px;margin-bottom:8px;"><i class="fas fa-image"></i></div>
                            <p>อัปโหลดภาพเพื่อดูตัวอย่าง</p>
                        </div>
                    </template>
                </div>

                {{-- Deploy Custom --}}
                <div style="margin-top:14px;display:flex;flex-wrap:wrap;gap:10px;">
                    <button @click="deployFromEditor('custom')"
                            :disabled="loading || !customImagePath || customAreas.length === 0"
                            class="tp-btn tp-btn-primary" style="flex:1;min-width:200px;justify-content:center;">
                        <template x-if="deployLoading"><i class="fas fa-spinner fa-spin"></i></template>
                        <i class="fas fa-rocket"></i> Deploy Custom Image
                    </button>
                </div>

                <template x-if="!customImagePath || customAreas.length === 0">
                    <p class="tp-muted" style="margin-top:8px;font-size:12px;">
                        <template x-if="!customImagePath">
                            <span>กรุณาอัปโหลดภาพก่อน</span>
                        </template>
                        <template x-if="customImagePath && customAreas.length === 0">
                            <span>กรุณากำหนด areas ก่อน deploy</span>
                        </template>
                    </p>
                </template>
            </div>
        </div>
    </div>
    </div>

    {{-- ========== Messages (toast — คง logic เดิม) ========== --}}
    <div x-show="successMessage" x-cloak x-transition
         class="tp-card tp-raise"
         style="position:fixed;bottom:24px;right:24px;z-index:50;max-width:380px;border-left:4px solid #5aa07e;">
        <div style="display:flex;align-items:flex-start;gap:12px;">
            <span style="color:#5aa07e;font-size:20px;"><i class="fas fa-circle-check"></i></span>
            <div style="flex:1;">
                <p style="color:var(--ink);font-weight:600;font-size:14px;" x-text="successMessage"></p>
            </div>
            <button @click="successMessage = null" class="tp-icon-btn" style="color:var(--ink2);">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
    </div>

    <div x-show="errorMessage" x-cloak x-transition
         class="tp-card tp-raise"
         style="position:fixed;bottom:24px;right:24px;z-index:50;max-width:380px;border-left:4px solid #d9534f;">
        <div style="display:flex;align-items:flex-start;gap:12px;">
            <span style="color:#d9534f;font-size:20px;"><i class="fas fa-circle-xmark"></i></span>
            <div style="flex:1;">
                <p style="color:var(--ink);font-weight:600;font-size:14px;" x-text="errorMessage"></p>
            </div>
            <button @click="errorMessage = null" class="tp-icon-btn" style="color:var(--ink2);">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function richMenuEditor() {
    return {
        // สถานะ
        activeTab: 'config',
        activeButton: 0,
        loading: false,
        previewLoading: false,
        deployLoading: false,
        uploadLoading: false,
        dragOver: false,
        canvasReady: false,
        successMessage: null,
        errorMessage: null,
        previewUrl: null,

        // Config Editor
        config: {
            theme: {
                bg_gradient_start: '#0d0521',
                bg_gradient_end: '#1a0a3e',
                grid_line_color: '#4C1D95',
                branding_text: '~~ แม่หมอจันทราพยากรณ์ ~~',
                branding_color: '#FFD700',
                show_stars: true,
                show_moon: true,
            },
            buttons: [],
        },

        // Custom Upload
        customImagePath: null,
        customImageUrl: null,
        customAreas: [],

        // CSRF token
        get csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        },

        // === โหลด config เริ่มต้น ===
        async loadConfig() {
            try {
                const res = await fetch('{{ route("admin.fortune.rich-menu.editor.config") }}', {
                    headers: { 'Accept': 'application/json' },
                });
                const data = await res.json();

                if (data.success && data.config) {
                    this.config = data.config;
                    if (data.editor_mode) {
                        this.activeTab = data.editor_mode;
                    }
                    if (data.image_url) {
                        this.previewUrl = data.image_url;
                        if (data.editor_mode === 'custom') {
                            this.customImageUrl = data.image_url;
                        }
                    }
                }
            } catch (e) {
                console.error('โหลด config ล้มเหลว:', e);
            }
        },

        // === Config: สร้าง Preview ===
        async generatePreview() {
            this.previewLoading = true;
            this.loading = true;
            this.clearMessages();

            try {
                const res = await fetch('{{ route("admin.fortune.rich-menu.editor.preview") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ config: this.config }),
                });
                const data = await res.json();

                if (data.success) {
                    this.previewUrl = data.preview_url;
                    this.successMessage = 'สร้าง Preview สำเร็จ!';
                    this.autoHideSuccess();
                } else {
                    this.errorMessage = data.error || 'สร้าง Preview ไม่สำเร็จ';
                }
            } catch (e) {
                this.errorMessage = 'เกิดข้อผิดพลาด: ' + e.message;
            } finally {
                this.previewLoading = false;
                this.loading = false;
            }
        },

        // === Config: บันทึก Draft ===
        async saveConfigDraft() {
            this.loading = true;
            this.clearMessages();

            try {
                const res = await fetch('{{ route("admin.fortune.rich-menu.editor.save-config") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        config: this.config,
                        editor_mode: this.activeTab,
                    }),
                });
                const data = await res.json();

                if (data.success) {
                    this.successMessage = 'บันทึก Draft สำเร็จ!';
                    this.autoHideSuccess();
                } else {
                    this.errorMessage = data.error || 'บันทึก Draft ไม่สำเร็จ';
                }
            } catch (e) {
                this.errorMessage = 'เกิดข้อผิดพลาด: ' + e.message;
            } finally {
                this.loading = false;
            }
        },

        // === Deploy (ทั้ง config และ custom) ===
        async deployFromEditor(mode) {
            const confirmMsg = mode === 'config'
                ? 'ยืนยันการ Deploy Rich Menu จาก Config?\n\nRich Menu เดิมจะถูกแทนที่'
                : 'ยืนยันการ Deploy ภาพ Custom Rich Menu?\n\nRich Menu เดิมจะถูกแทนที่';

            if (!confirm(confirmMsg)) return;

            this.deployLoading = true;
            this.loading = true;
            this.clearMessages();

            try {
                const body = {
                    editor_mode: mode,
                };

                if (mode === 'config') {
                    body.config = this.config;
                } else {
                    body.custom_image_path = this.customImagePath;
                    body.custom_areas = this.customAreas;
                }

                const res = await fetch('{{ route("admin.fortune.rich-menu.editor.deploy") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(body),
                });
                const data = await res.json();

                if (data.success) {
                    this.successMessage = 'Deploy Rich Menu สำเร็จ! ID: ' + (data.rich_menu_id || '');
                    setTimeout(() => window.location.reload(), 3000);
                } else {
                    this.errorMessage = data.error || 'Deploy ไม่สำเร็จ';
                }
            } catch (e) {
                this.errorMessage = 'เกิดข้อผิดพลาด: ' + e.message;
            } finally {
                this.deployLoading = false;
                this.loading = false;
            }
        },

        // === Custom: อัปโหลดภาพ ===
        async handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) await this.uploadFile(file);
        },

        async handleFileDrop(event) {
            this.dragOver = false;
            const file = event.dataTransfer.files[0];
            if (file) await this.uploadFile(file);
        },

        async uploadFile(file) {
            if (!file.type.match(/image\/(png|jpeg|jpg)/)) {
                this.errorMessage = 'รองรับเฉพาะไฟล์ PNG/JPG';
                return;
            }

            this.uploadLoading = true;
            this.loading = true;
            this.clearMessages();

            try {
                const formData = new FormData();
                formData.append('image', file);

                const res = await fetch('{{ route("admin.fortune.rich-menu.editor.upload") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });
                const data = await res.json();

                if (data.success) {
                    this.customImagePath = data.image_path;
                    this.customImageUrl = data.image_url;
                    this.successMessage = 'อัปโหลดภาพสำเร็จ! (' + data.dimensions.width + 'x' + data.dimensions.height + ')';
                    this.autoHideSuccess();
                } else {
                    this.errorMessage = data.error || 'อัปโหลดไม่สำเร็จ';
                }
            } catch (e) {
                this.errorMessage = 'เกิดข้อผิดพลาด: ' + e.message;
            } finally {
                this.uploadLoading = false;
                this.loading = false;
            }
        },

        // === Custom: ใช้ Default Areas (6 ปุ่ม 3x2 grid) ===
        useDefaultAreas() {
            const colWidths = [833, 834, 833];
            const rowHeight = 843;
            const defaultActions = [
                { type: 'message', text: 'ดูดวง' },
                { type: 'postback', data: 'action=deep_reading', displayText: 'ดูดวงละเอียด' },
                { type: 'postback', data: 'action=view_last_reading', displayText: 'ดูคำทำนายล่าสุด' },
                { type: 'postback', data: 'action=check_status', displayText: 'ดูสถานะสิทธิ์' },
                { type: 'postback', data: 'action=report_payment', displayText: 'แจ้งปัญหาโอน' },
                { type: 'postback', data: 'action=help', displayText: 'วิธีใช้งาน' },
            ];

            this.customAreas = [];
            for (let i = 0; i < 6; i++) {
                const row = Math.floor(i / 3);
                const col = i % 3;
                let x = 0;
                for (let c = 0; c < col; c++) x += colWidths[c];

                this.customAreas.push({
                    bounds: {
                        x: x,
                        y: row * rowHeight,
                        width: colWidths[col],
                        height: rowHeight,
                    },
                    action: { ...defaultActions[i] },
                });
            }

            this.successMessage = 'โหลด Default Areas 6 ปุ่ม (3x2) สำเร็จ!';
            this.autoHideSuccess();
        },

        // === Overlay style สำหรับ areas บนภาพ custom ===
        getAreaOverlayStyle(bounds) {
            // คำนวณ % จากภาพ 2500x1686
            const imageWidth = 2500;
            const imageHeight = 1686;
            return {
                left: (bounds.x / imageWidth * 100) + '%',
                top: (bounds.y / imageHeight * 100) + '%',
                width: (bounds.width / imageWidth * 100) + '%',
                height: (bounds.height / imageHeight * 100) + '%',
            };
        },

        // === Helpers ===
        clearMessages() {
            this.successMessage = null;
            this.errorMessage = null;
        },

        autoHideSuccess() {
            setTimeout(() => { this.successMessage = null; }, 5000);
        },
    };
}
</script>
@endpush
@endsection
