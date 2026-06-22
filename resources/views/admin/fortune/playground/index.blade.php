@extends('layouts.admin-v4')

@section('title', 'AI Playground - ทดสอบดูดวง')

@section('content')
{{-- หน้า AI Playground — ธีม V4 นวลทองคำ. คงฟังก์ชัน/AJAX/Alpine เดิม 100% เปลี่ยนแค่เปลือก UI --}}
<div class="tp-pg" x-data="fortunePlayground()" style="display:flex;flex-direction:column;gap:18px;">

    {{-- ส่วนหัว: eyebrow + ชื่อหน้า + ปุ่มลัด --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:12px;letter-spacing:.04em;text-transform:uppercase;margin-bottom:6px;">
                หลังบ้าน · ระบบดูดวง · AI Playground
            </div>
            <h1 class="tp-num" style="font-size:26px;margin:0;display:flex;align-items:center;gap:10px;">
                <i class="fas fa-wand-magic-sparkles" style="color:var(--accent2);"></i>
                ทดสอบดูดวง AI
            </h1>
            <div class="tp-muted" style="font-size:13px;margin-top:6px;">
                สนทนากับ AI หมอดู ตรวจสอบคุณภาพคำทำนายก่อนใช้งานจริง
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            @if(Route::has('admin.ai-api-keys.index'))
            <a href="{{ route('admin.ai-api-keys.index') }}" class="tp-btn"
               title="จัดลำดับความสำคัญของ AI Provider (priority field) — ค่าสูง = ใช้ก่อน"
               style="display:inline-flex;align-items:center;gap:8px;">
                <i class="fas fa-bullseye" style="color:var(--accent2);"></i>
                <span>จัด Priority Provider</span>
            </a>
            @endif
            @if(Route::has('admin.fortune.settings.index'))
            <a href="{{ route('admin.fortune.settings.index') }}" class="tp-btn"
               style="display:inline-flex;align-items:center;gap:8px;">
                <i class="fas fa-gear"></i>
                <span>กลับหน้าตั้งค่า</span>
            </a>
            @endif
        </div>
    </div>

    {{-- การ์ดเลือก Provider + Model ทดสอบ --}}
    <div class="tp-card tp-raise">
        <div style="display:flex;flex-direction:column;gap:14px;">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;">
                <div style="display:flex;align-items:flex-start;gap:12px;min-width:280px;flex:1;">
                    <span style="font-size:26px;line-height:1;color:var(--accent2);"><i class="fas fa-hat-wizard"></i></span>
                    <div style="flex:1;">
                        <div class="tp-section-h" style="margin-bottom:8px;">เลือก AI Provider ทดสอบ</div>
                        <div class="tp-well tp-input" style="padding:0;">
                            <select x-model="selectedProviderIndex" @change="onProviderChange()"
                                    style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;cursor:pointer;">
                                @foreach($availableProviders as $index => $p)
                                <option value="{{ $index }}">{{ $p['label'] }}</option>
                                @endforeach
                                @if(count($availableProviders) === 0)
                                <option value="-1">ไม่มี Provider ที่พร้อมใช้</option>
                                @endif
                            </select>
                        </div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <span class="tp-pill" style="--st:#5aa07e;border-color:#5aa07e;color:#5aa07e;"
                          x-text="'✓ ' + currentProvider + ' / ' + currentModel"></span>
                    <span class="tp-pill" style="--st:#e0a52e;border-color:#e0a52e;color:#e0a52e;"
                          x-text="'📦 ' + providerSource"></span>
                    <span x-show="currentPurpose && currentPurpose !== 'any'" class="tp-pill"
                          style="--st:#b79ae8;border-color:#b79ae8;color:#b79ae8;"
                          x-text="'🔒 ' + currentPurpose"></span>
                </div>
            </div>

            {{-- Model picker — ทดสอบ model อื่นของ provider เดียวกันได้ --}}
            <div class="tp-divider"></div>
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="color:var(--accent2);"><i class="fas fa-sliders"></i></span>
                    <span class="tp-section-h" style="margin:0;">Model ทดสอบ:</span>
                </div>
                <div class="tp-well tp-input" style="padding:0;min-width:240px;flex:1;max-width:340px;">
                    <select x-model="currentModel"
                            style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;cursor:pointer;">
                        <template x-for="m in availableModelsForCurrentProvider" :key="m">
                            <option :value="m" x-text="m"></option>
                        </template>
                    </select>
                </div>
                <button @click="resetModelToDefault()" class="tp-btn tp-btn-sm"
                        title="กลับไปใช้ model เริ่มต้นของ key นี้">
                    <i class="fas fa-rotate-left"></i> Reset
                </button>
                <span class="tp-muted" style="font-size:12px;">
                    เลือกต่างจากค่าเริ่มต้นเพื่อทดสอบ — ไม่กระทบการใช้งานจริง
                </span>
            </div>
        </div>
    </div>

    {{-- เนื้อหาหลัก: แชท (ซ้าย) + sidebar (ขวา) --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:18px;align-items:start;">

        {{-- พื้นที่แชท --}}
        <div style="grid-column:1/-1;" class="tp-pg-chatcol">
            <div class="tp-card tp-raise" style="padding:0;display:flex;flex-direction:column;height:600px;overflow:hidden;">
                {{-- หัวแชท --}}
                <div style="padding:14px 16px;border-bottom:1px solid var(--sd);display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="font-size:18px;color:var(--accent2);"><i class="fas fa-crystal-ball"></i></span>
                        <span style="font-weight:700;color:var(--ink);">หมอดูประจำเพจ</span>
                        <span class="tp-spark" style="width:9px;height:9px;border-radius:50%;background:#5aa07e;"></span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div class="tp-well tp-input" style="padding:0;">
                            <select x-model="readingType"
                                    style="background:transparent;border:0;outline:0;padding:7px 12px;color:var(--ink);font-size:12px;cursor:pointer;">
                                <option value="basic">ทำนายพื้นฐาน</option>
                                <option value="deep">ทำนายเชิงลึก</option>
                            </select>
                        </div>
                        <button @click="clearChat()" class="tp-btn tp-btn-sm"
                                style="color:#d9534f;border-color:#d9534f;">
                            <i class="fas fa-trash-can"></i> ล้างแชท
                        </button>
                    </div>
                </div>

                {{-- ข้อความแชท --}}
                <div style="flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:14px;" x-ref="chatContainer">
                    {{-- ข้อความต้อนรับ --}}
                    <div x-show="messages.length === 0" style="text-align:center;padding:48px 12px;" class="tp-muted">
                        <span style="font-size:48px;display:block;margin-bottom:14px;color:var(--accent2);"><i class="fas fa-crystal-ball"></i></span>
                        <p style="font-size:17px;font-weight:600;margin:0 0 6px;color:var(--ink2);">ลองสนทนากับ AI หมอดู</p>
                        <p style="font-size:13px;margin:0;">พิมพ์ข้อความด้านล่าง หรือกดปุ่มตัวอย่างทางขวา</p>
                    </div>

                    {{-- รายการข้อความ --}}
                    <template x-for="(msg, index) in messages" :key="index">
                        <div :style="msg.role === 'user' ? 'display:flex;justify-content:flex-end;' : 'display:flex;justify-content:flex-start;'">
                            <div :style="msg.role === 'user'
                                ? 'background:linear-gradient(135deg,var(--accent1),var(--accent2));color:#3a2c10;border-radius:16px 16px 4px 16px;padding:12px 16px;max-width:80%;box-shadow:var(--raise);'
                                : 'background:var(--inset);color:var(--ink);border-radius:16px 16px 16px 4px;padding:12px 16px;max-width:80%;'">
                                <div x-show="msg.role === 'assistant'" style="display:flex;align-items:center;gap:6px;margin-bottom:5px;">
                                    <span style="font-size:13px;color:var(--accent2);"><i class="fas fa-crystal-ball"></i></span>
                                    <span style="font-size:12px;font-weight:700;color:var(--accent2);">หมอดูประจำเพจ</span>
                                </div>
                                <div style="font-size:14px;white-space:pre-wrap;line-height:1.55;" x-text="msg.content"></div>
                                <div x-show="msg.debug" style="margin-top:8px;padding-top:8px;border-top:1px solid var(--sd);">
                                    <div class="tp-muted" style="font-size:11px;display:flex;flex-direction:column;gap:2px;">
                                        <div x-show="msg.debug?.provider" x-text="'Provider: ' + msg.debug?.provider"></div>
                                        <div x-show="msg.debug?.model" x-text="'Model: ' + msg.debug?.model"></div>
                                        <div x-show="msg.debug?.tokens_used" x-text="'Tokens: ' + msg.debug?.tokens_used"></div>
                                        <div x-show="msg.debug?.response_time_ms" x-text="'เวลาตอบ: ' + msg.debug?.response_time_ms + 'ms'"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- ตัวบ่งชี้กำลังพิมพ์ --}}
                    <div x-show="loading" style="display:flex;justify-content:flex-start;">
                        <div style="background:var(--inset);border-radius:16px 16px 16px 4px;padding:12px 16px;">
                            <div style="display:flex;align-items:center;gap:6px;margin-bottom:5px;">
                                <span style="font-size:13px;color:var(--accent2);"><i class="fas fa-crystal-ball"></i></span>
                                <span style="font-size:12px;font-weight:700;color:var(--accent2);">หมอดูประจำเพจ</span>
                            </div>
                            <div style="display:flex;align-items:center;gap:5px;">
                                <span class="tp-pg-dot" style="animation-delay:0ms"></span>
                                <span class="tp-pg-dot" style="animation-delay:150ms"></span>
                                <span class="tp-pg-dot" style="animation-delay:300ms"></span>
                            </div>
                        </div>
                    </div>

                    {{-- ข้อความผิดพลาด --}}
                    <div x-show="error" x-transition style="display:flex;justify-content:center;">
                        <div style="background:rgba(217,83,79,.12);border:1px solid #d9534f;border-radius:14px;padding:12px 16px;max-width:90%;">
                            <div style="display:flex;align-items:flex-start;gap:8px;">
                                <span style="font-size:18px;color:#d9534f;"><i class="fas fa-circle-exclamation"></i></span>
                                <div>
                                    <p style="font-size:13px;font-weight:700;color:#d9534f;margin:0;">เกิดข้อผิดพลาด</p>
                                    <p style="font-size:12px;color:#d9534f;margin:4px 0 0;" x-text="error"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ช่องพิมพ์ --}}
                <div style="padding:14px 16px;border-top:1px solid var(--sd);">
                    <div style="display:flex;align-items:flex-end;gap:12px;">
                        <div class="tp-well tp-input" style="flex:1;padding:0;">
                            <textarea x-model="inputMessage"
                                      @keydown.enter.prevent="if (!$event.shiftKey) sendMessage()"
                                      :disabled="loading"
                                      rows="2"
                                      style="width:100%;background:transparent;border:0;outline:0;resize:none;padding:10px 14px;color:var(--ink);font-size:14px;font-family:inherit;"
                                      placeholder="พิมพ์ข้อความ... (Enter เพื่อส่ง, Shift+Enter เพื่อขึ้นบรรทัดใหม่)"></textarea>
                        </div>
                        <button @click="sendMessage()"
                                :disabled="loading || !inputMessage.trim()"
                                class="tp-btn tp-btn-primary"
                                style="padding:11px 22px;display:inline-flex;align-items:center;gap:8px;">
                            <span x-show="!loading">ส่ง <i class="fas fa-paper-plane"></i></span>
                            <span x-show="loading"><i class="fas fa-spinner fa-spin"></i></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar — ตัวอย่างคำถาม + สถิติ + คำแนะนำ --}}
        <div style="grid-column:1/-1;display:flex;flex-direction:column;gap:18px;" class="tp-pg-sidecol">
            {{-- ตัวอย่างคำถาม --}}
            <div class="tp-card">
                <h3 class="tp-section-h" style="margin:0 0 12px;display:flex;align-items:center;gap:6px;">
                    <i class="fas fa-comment-dots" style="color:var(--accent2);"></i> ตัวอย่างคำถาม
                </h3>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <button @click="sendQuick('สวัสดีค่ะ')" class="tp-tile tp-pg-quick">
                        👋 สวัสดีค่ะ
                    </button>

                    <div style="padding-top:4px;">
                        <p style="font-size:12px;font-weight:700;color:#b79ae8;margin:0 0 6px;">💕 ความรัก</p>
                        <button @click="sendQuick('ปีนี้จะมีคู่ครองไหมคะ อยากรู้ว่าดวงความรักเป็นอย่างไร')"
                                class="tp-tile tp-pg-quick" style="margin-bottom:6px;">
                            ปีนี้จะมีคู่ครองไหมคะ
                        </button>
                        <button @click="sendQuick('แฟนรักจริงหรือเปล่าคะ รู้สึกไม่มั่นใจ')" class="tp-tile tp-pg-quick">
                            แฟนรักจริงหรือเปล่า
                        </button>
                    </div>

                    <div style="padding-top:4px;">
                        <p style="font-size:12px;font-weight:700;color:#5689b8;margin:0 0 6px;">💼 การงาน</p>
                        <button @click="sendQuick('ควรเปลี่ยนงานดีไหมคะ ทำงานที่นี่มา 3 ปีแล้ว')"
                                class="tp-tile tp-pg-quick" style="margin-bottom:6px;">
                            ควรเปลี่ยนงานดีไหม
                        </button>
                        <button @click="sendQuick('ปีนี้จะได้เลื่อนตำแหน่งไหมคะ')" class="tp-tile tp-pg-quick">
                            จะได้เลื่อนตำแหน่งไหม
                        </button>
                    </div>

                    <div style="padding-top:4px;">
                        <p style="font-size:12px;font-weight:700;color:#5aa07e;margin:0 0 6px;">💰 การเงิน</p>
                        <button @click="sendQuick('ดวงการเงินปีนี้เป็นอย่างไรคะ ลงทุนได้ไหม')" class="tp-tile tp-pg-quick">
                            ดวงการเงินปีนี้เป็นอย่างไร
                        </button>
                    </div>

                    <div style="padding-top:4px;">
                        <p style="font-size:12px;font-weight:700;color:#d6824a;margin:0 0 6px;">🔮 ทั่วไป</p>
                        <button @click="sendQuick('ดูดวงภาพรวมปีนี้ให้หน่อยค่ะ เกิดวันที่ 15 มีนาคม 2533')"
                                class="tp-tile tp-pg-quick" style="margin-bottom:6px;">
                            ดูดวงพร้อมวันเกิด (มีนาคม 2533)
                        </button>
                        <button @click="sendQuick('เช็คสิทธิ์')" class="tp-tile tp-pg-quick" style="margin-bottom:6px;">
                            เช็คสิทธิ์
                        </button>
                        <button @click="sendQuick('คุณเป็น AI หรือเปล่า')" class="tp-tile tp-pg-quick">
                            ทดสอบถามว่าเป็น AI
                        </button>
                    </div>
                </div>
            </div>

            {{-- สถิติเซสชั่น --}}
            <div class="tp-card">
                <h3 class="tp-section-h" style="margin:0 0 12px;display:flex;align-items:center;gap:6px;">
                    <i class="fas fa-chart-simple" style="color:var(--accent2);"></i> สถิติเซสชั่นนี้
                </h3>
                <div style="display:flex;flex-direction:column;gap:10px;font-size:13px;">
                    <div style="display:flex;justify-content:space-between;" class="tp-muted">
                        <span>ข้อความทั้งหมด</span>
                        <span class="tp-num" style="color:var(--ink);" x-text="messages.length"></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;" class="tp-muted">
                        <span>Tokens ใช้ไป</span>
                        <span class="tp-num" style="color:var(--ink);" x-text="totalTokens.toLocaleString()"></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;" class="tp-muted">
                        <span>เวลาตอบเฉลี่ย</span>
                        <span class="tp-num" style="color:var(--ink);" x-text="avgResponseTime + 'ms'"></span>
                    </div>
                </div>
            </div>

            {{-- คำแนะนำ --}}
            <div class="tp-card" style="border:1px solid #e0a52e;">
                <h3 class="tp-section-h" style="margin:0 0 10px;color:#e0a52e;display:flex;align-items:center;gap:6px;">
                    <i class="fas fa-lightbulb"></i> คำแนะนำ
                </h3>
                <ul style="font-size:12px;color:var(--ink2);margin:0;padding-left:18px;display:flex;flex-direction:column;gap:6px;">
                    <li>ทดสอบทั้งคำถามดูดวงและคำถามนอกเรื่อง</li>
                    <li>ลองถามว่าเป็น AI เพื่อดูว่า bot ตอบถูกต้อง</li>
                    <li>ทดสอบส่งวันเดือนปีเกิดเพื่อดูการวิเคราะห์ราศี</li>
                    <li>สลับระหว่าง "พื้นฐาน" กับ "เชิงลึก" เพื่อเทียบคุณภาพ</li>
                    <li>ข้อความนี้ส่งตรงไป AI จริง ใช้ tokens จริง</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- ทดสอบสร้างคำทำนายเชิงลึก (Deep) — ใช้ Alpine state เดียวกับ fortunePlayground() --}}
    {{-- เดิมใช้ x-data deepPredictionTest() → $root ไม่ส่ง provider ที่เลือก รวม state เข้าอันเดียว --}}
    <div class="tp-card tp-raise" style="border:1px solid var(--accent2);">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:16px;gap:14px;flex-wrap:wrap;">
            <div>
                <h2 class="tp-num" style="font-size:20px;margin:0;display:flex;align-items:center;gap:8px;color:var(--ink);">
                    <i class="fas fa-flask" style="color:var(--accent2);"></i>
                    ทดสอบสร้างคำทำนายเชิงลึก (Deep)
                </h2>
                <p class="tp-muted" style="font-size:13px;margin:6px 0 0;">
                    ใช้ <b>prompt จริง</b> ที่ส่งให้ AI เวลาลูกค้าจ่ายเงิน — เปลี่ยน provider ดรอปดาวน์ด้านบน → มีผลที่นี่ด้วย
                </p>
            </div>
            <div class="tp-inset-sm tp-muted" style="font-size:12px;text-align:right;padding:8px 12px;border-radius:10px;">
                Provider ที่จะใช้:<br>
                <span class="tp-num" style="font-size:15px;color:var(--accent2);" x-text="currentProvider + ' / ' + currentModel"></span>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:18px;align-items:start;">
            {{-- ฟอร์ม (ซ้าย) --}}
            <div style="display:flex;flex-direction:column;gap:12px;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label class="tp-section-h" style="display:block;margin-bottom:5px;">ชื่อเล่น (ทดสอบ)</label>
                        <div class="tp-well tp-input">
                            <input type="text" x-model="deepForm.name" placeholder="เช่น สมหญิง"
                                   style="width:100%;background:transparent;border:0;outline:0;color:var(--ink);font-size:14px;">
                        </div>
                    </div>
                    <div>
                        <label class="tp-section-h" style="display:block;margin-bottom:5px;">เพศ</label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <select x-model="deepForm.gender"
                                    style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;cursor:pointer;">
                                <option value="female">หญิง</option>
                                <option value="male">ชาย</option>
                                <option value="">ไม่ระบุ</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="tp-section-h" style="display:block;margin-bottom:5px;">วันเกิด *</label>
                    <div class="tp-well tp-input">
                        <input type="date" x-model="deepForm.birth_date" :max="maxBirthDate"
                               style="width:100%;background:transparent;border:0;outline:0;color:var(--ink);font-size:14px;">
                    </div>
                </div>
                <div>
                    <label class="tp-section-h" style="display:block;margin-bottom:5px;">คำถามที่จะทำนาย *</label>
                    <div class="tp-well tp-input">
                        <textarea x-model="deepForm.question" rows="2"
                                  placeholder="เช่น ความรักปีนี้จะเป็นอย่างไร / ถูกหวยงวดนี้ไหม / จะได้เลื่อนตำแหน่งไหม"
                                  style="width:100%;background:transparent;border:0;outline:0;resize:vertical;color:var(--ink);font-size:14px;font-family:inherit;"></textarea>
                    </div>
                </div>
                <div>
                    <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--ink2);cursor:pointer;">
                        <input type="checkbox" x-model="deepForm.show_prompt">
                        แสดง prompt ที่ส่งให้ AI (debug)
                    </label>
                </div>

                {{-- ตัวอย่างคำถามด่วน --}}
                <div class="tp-divider"></div>
                <div>
                    <p class="tp-section-h" style="margin:0 0 8px;">ตัวอย่างคำถาม:</p>
                    <div style="display:flex;flex-wrap:wrap;gap:6px;">
                        <button @click="deepForm.question='ปีนี้ความรักจะเป็นอย่างไรคะ จะได้แฟนใหม่ไหม'"
                                class="tp-btn tp-btn-sm" style="color:#b79ae8;border-color:#b79ae8;">💕 ความรัก</button>
                        <button @click="deepForm.question='ถูกหวยงวดนี้ไหมคะ ลองเสี่ยงโชคได้ไหม'"
                                class="tp-btn tp-btn-sm" style="color:#e0a52e;border-color:#e0a52e;">🎰 หวย</button>
                        <button @click="deepForm.question='งานที่ทำอยู่ควรเปลี่ยนไหม จะได้เลื่อนขั้นไหม'"
                                class="tp-btn tp-btn-sm" style="color:#5689b8;border-color:#5689b8;">💼 งาน</button>
                        <button @click="deepForm.question='สุขภาพปีนี้เป็นอย่างไร ต้องระวังอะไรบ้าง'"
                                class="tp-btn tp-btn-sm" style="color:#5aa07e;border-color:#5aa07e;">🏥 สุขภาพ</button>
                    </div>
                </div>

                <button @click="runDeepTest()" :disabled="deepLoading || !deepForm.birth_date || !deepForm.question"
                        class="tp-btn tp-btn-primary"
                        style="width:100%;margin-top:6px;padding:13px;display:flex;align-items:center;justify-content:center;gap:8px;">
                    <span x-show="!deepLoading"><i class="fas fa-flask"></i> ทดสอบสร้างคำทำนาย (ใช้ <span style="text-decoration:underline;" x-text="currentProvider"></span>)</span>
                    <span x-show="deepLoading" style="display:flex;align-items:center;gap:8px;">
                        <i class="fas fa-spinner fa-spin"></i> กำลังเรียก AI... (30-60 วิ)
                    </span>
                </button>

                <p class="tp-muted" style="font-size:12px;margin:0;">
                    ⚠️ ใช้ tokens จริง + ใช้ provider ที่เลือกด้านบน + <b>ไม่กระทบ FortuneReading</b> ของลูกค้า
                </p>
            </div>

            {{-- ผลลัพธ์ (ขวา) --}}
            <div style="display:flex;flex-direction:column;gap:12px;">
                <div x-show="!deepResult && !deepError && !deepLoading"
                     class="tp-inset" style="min-height:300px;display:flex;align-items:center;justify-content:center;text-align:center;border-radius:14px;padding:24px;">
                    <div class="tp-muted">
                        <span style="font-size:42px;display:block;margin-bottom:12px;color:var(--accent2);"><i class="fas fa-crystal-ball"></i></span>
                        <p style="font-size:13px;margin:0;">ใส่ข้อมูลแล้วกด "ทดสอบสร้างคำทำนาย"<br>คำทำนายจะปรากฏที่นี่</p>
                    </div>
                </div>

                <div x-show="deepError" style="background:rgba(217,83,79,.12);border:1px solid #d9534f;border-radius:14px;padding:16px;">
                    <p style="font-size:13px;font-weight:700;color:#d9534f;margin:0 0 4px;">❌ เกิดข้อผิดพลาด</p>
                    <p style="font-size:12px;color:#d9534f;margin:0;" x-text="deepError"></p>
                </div>

                <div x-show="deepResult" x-transition>
                    {{-- Metrics --}}
                    <div class="tp-inset" style="border-radius:12px;padding:14px;margin-bottom:12px;display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:12px;">
                        <div>
                            <span class="tp-muted">Provider:</span>
                            <span class="tp-num" style="color:var(--accent2);display:block;" x-text="deepResult?.debug?.provider"></span>
                        </div>
                        <div>
                            <span class="tp-muted">Model:</span>
                            <span class="tp-num" style="color:#5689b8;display:block;" x-text="deepResult?.debug?.model"></span>
                        </div>
                        <div>
                            <span class="tp-muted">Tokens:</span>
                            <span class="tp-num" style="color:#d6824a;display:block;" x-text="(deepResult?.debug?.tokens_used || 0).toLocaleString()"></span>
                        </div>
                        <div>
                            <span class="tp-muted">เวลา:</span>
                            <span class="tp-num" style="color:#5aa07e;display:block;" x-text="(deepResult?.debug?.response_time_ms || 0) + 'ms'"></span>
                        </div>
                    </div>

                    {{-- คำทำนาย --}}
                    <div class="tp-inset" style="border-radius:14px;padding:16px;max-height:500px;overflow-y:auto;">
                        <h4 class="tp-section-h" style="margin:0 0 8px;color:var(--accent2);">📜 คำทำนาย</h4>
                        <div style="font-size:14px;color:var(--ink);white-space:pre-wrap;line-height:1.6;" x-text="deepResult?.response"></div>
                    </div>

                    {{-- จำนวนคำ/ตัวอักษร --}}
                    <div class="tp-muted" style="font-size:12px;margin-top:8px;text-align:right;" x-show="deepResult?.response">
                        ตัวอักษร: <span x-text="(deepResult?.response || '').length"></span> | คำ: <span x-text="(deepResult?.response || '').split(/\s+/).filter(w => w).length"></span>
                    </div>

                    {{-- Prompt (collapsible) --}}
                    <details x-show="deepResult?.prompt" class="tp-inset" style="margin-top:12px;border-radius:12px;padding:14px;">
                        <summary style="cursor:pointer;font-size:12px;font-weight:700;color:var(--ink2);">
                            🔍 ดู prompt ที่ส่งให้ AI (<span x-text="deepResult?.prompt_length"></span> ตัวอักษร)
                        </summary>
                        <pre style="margin-top:8px;font-size:12px;color:var(--ink2);white-space:pre-wrap;font-family:monospace;max-height:400px;overflow-y:auto;" x-text="deepResult?.prompt"></pre>
                    </details>

                    <button @click="deepResult=null; deepError=null" class="tp-btn tp-btn-sm" style="margin-top:12px;">
                        <i class="fas fa-trash-can"></i> ล้างผลลัพธ์
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- style stack: สไตล์เฉพาะหน้า (inline ใน scripts stack ตามกติกา V4 ห้าม push styles) --}}
@push('scripts')
<style>
    /* ปุ่มตัวอย่างคำถามใน sidebar — จัดข้อความชิดซ้าย ตัวเล็ก */
    .tp-pg-quick {
        width: 100%;
        text-align: left;
        font-size: 12px;
        color: var(--ink2);
        padding: 9px 12px;
        cursor: pointer;
        transition: transform .12s ease, box-shadow .12s ease;
    }
    .tp-pg-quick:hover { color: var(--ink); box-shadow: var(--raise); }
    /* จุดกระพริบ typing indicator */
    .tp-pg-dot {
        width: 8px; height: 8px; border-radius: 50%;
        background: var(--accent2);
        display: inline-block;
        animation: tp-pg-bounce 1.2s infinite ease-in-out;
    }
    @keyframes tp-pg-bounce {
        0%, 80%, 100% { opacity: .35; transform: translateY(0); }
        40% { opacity: 1; transform: translateY(-4px); }
    }
    /* layout: จอกว้าง → แชท 2 ส่วน + sidebar 1 ส่วน */
    @media (min-width: 1024px) {
        .tp-pg .tp-pg-chatcol { grid-column: span 2 !important; }
        .tp-pg .tp-pg-sidecol { grid-column: span 1 !important; }
    }
</style>
<script>
function fortunePlayground() {
    const providers = @json($availableProviders);
    // 🆕 (2026-05-06) MODELS_BY_PROVIDER จาก AiApiKey — ใช้ populate model picker
    const modelsByProvider = @json($modelsByProvider ?? []);

    return {
        messages: [],
        inputMessage: '',
        loading: false,
        error: null,
        readingType: 'basic',
        totalTokens: 0,
        totalResponseTime: 0,
        responseCount: 0,

        // Provider selection
        providers: providers,
        modelsByProvider: modelsByProvider,
        selectedProviderIndex: 0,
        currentProvider: providers.length ? providers[0].provider : '',
        currentModel: providers.length ? providers[0].model : '',
        providerSource: providers.length ? providers[0].source : '',
        poolKeyId: providers.length ? (providers[0].pool_key_id || null) : null,
        currentPurpose: providers.length ? (providers[0].purpose || 'any') : 'any',
        defaultModelForKey: providers.length ? providers[0].model : '',

        onProviderChange() {
            const idx = parseInt(this.selectedProviderIndex);
            if (idx >= 0 && idx < this.providers.length) {
                const p = this.providers[idx];
                this.currentProvider = p.provider;
                this.currentModel = p.model;
                this.providerSource = p.source;
                this.poolKeyId = p.pool_key_id || null;
                this.currentPurpose = p.purpose || 'any';
                this.defaultModelForKey = p.model;
            }
        },

        // 🆕 (2026-05-06) Model dropdown — populate จาก MODELS_BY_PROVIDER
        get availableModelsForCurrentProvider() {
            const list = this.modelsByProvider[this.currentProvider] || [];
            // ถ้า currentModel ไม่อยู่ใน list (เช่น custom) → prepend ไว้บนสุด
            if (this.currentModel && !list.includes(this.currentModel)) {
                return [this.currentModel, ...list];
            }
            return list;
        },

        // ↩️ Reset model กลับเป็น default ของ key
        resetModelToDefault() {
            if (this.defaultModelForKey) {
                this.currentModel = this.defaultModelForKey;
            }
        },

        get avgResponseTime() {
            if (this.responseCount === 0) return 0;
            return Math.round(this.totalResponseTime / this.responseCount);
        },

        async sendMessage() {
            const text = this.inputMessage.trim();
            if (!text || this.loading) return;

            this.inputMessage = '';
            this.error = null;

            // เพิ่มข้อความผู้ใช้
            this.messages.push({ role: 'user', content: text });
            this.scrollToBottom();

            this.loading = true;

            try {
                const payload = {
                    messages: this.messages.map(m => ({
                        role: m.role,
                        content: m.content
                    })),
                    reading_type: this.readingType,
                    provider: this.currentProvider,
                    model: this.currentModel,
                };
                if (this.poolKeyId) {
                    payload.pool_key_id = this.poolKeyId;
                }

                const response = await fetch('{{ route("admin.fortune.playground.chat") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (data.success) {
                    this.messages.push({
                        role: 'assistant',
                        content: data.response,
                        debug: data.debug || {}
                    });

                    // อัพเดทสถิติ
                    if (data.debug) {
                        this.totalTokens += data.debug.tokens_used || 0;
                        this.totalResponseTime += data.debug.response_time_ms || 0;
                        this.responseCount++;
                    }
                } else {
                    this.error = data.error || 'ไม่ทราบสาเหตุ';
                }
            } catch (err) {
                this.error = 'เชื่อมต่อไม่ได้: ' + err.message;
            } finally {
                this.loading = false;
                this.scrollToBottom();
            }
        },

        sendQuick(text) {
            this.inputMessage = text;
            this.sendMessage();
        },

        clearChat() {
            this.messages = [];
            this.error = null;
            this.totalTokens = 0;
            this.totalResponseTime = 0;
            this.responseCount = 0;
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const container = this.$refs.chatContainer;
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            });
        },

        // ========================================
        // 🧪 Deep Prediction Test — รวมเป็น state เดียวกับ chat
        //    เพื่อให้ provider dropdown มีผลทั้ง 2 panel
        // ========================================
        deepForm: {
            name: 'สมหญิง',
            gender: 'female',
            birth_date: '1990-03-15',
            question: '',
            show_prompt: true,
        },
        deepLoading: false,
        deepError: null,
        deepResult: null,

        get maxBirthDate() {
            const d = new Date();
            d.setDate(d.getDate() - 1);
            return d.toISOString().slice(0, 10);
        },

        async runDeepTest() {
            if (this.deepLoading) return;
            this.deepLoading = true;
            this.deepError = null;
            this.deepResult = null;

            try {
                // ใช้ currentProvider จาก state เดียวกัน (dropdown ด้านบน) — ตรงนี้แหละที่ fix bug $root
                const payload = {
                    name: this.deepForm.name || null,
                    gender: this.deepForm.gender || null,
                    birth_date: this.deepForm.birth_date,
                    question: this.deepForm.question,
                    show_prompt: this.deepForm.show_prompt,
                    provider: this.currentProvider || null,
                    model: this.currentModel || null,
                };
                if (this.poolKeyId) {
                    payload.pool_key_id = this.poolKeyId;
                }

                const response = await fetch('{{ route("admin.fortune.playground.test-deep") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (data.success) {
                    this.deepResult = data;
                } else {
                    this.deepError = data.error || 'ไม่ทราบสาเหตุ';
                }
            } catch (err) {
                this.deepError = 'เชื่อมต่อไม่ได้: ' + err.message;
            } finally {
                this.deepLoading = false;
            }
        }
    };
}
</script>
@endpush
@endsection
