{{-- ฟอร์มแคมเปญ (ใช้ร่วม create/edit) — $campaign = null สำหรับสร้างใหม่ --}}
@php
    /** @var \App\Models\FortuneContentCampaign|null $campaign */
    $campaign = $campaign ?? null;
    $joinLines = fn ($arr) => implode("\n", is_array($arr) ? $arr : []);

    // เวลาโพสตั้งต้นสำหรับ repeater — validation พลาดใช้ old ก่อน แล้วค่อย schedule ของแคมเปญ
    $initialSlots = old('schedule_text') !== null
        ? array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', old('schedule_text')))))
        : array_values($campaign->schedule ?? []);
@endphp

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px;">
    <div>
        <label class="tp-muted" style="font-size:12px;display:block;margin-bottom:4px;">ชื่อแคมเปญ *</label>
        <input type="text" name="name_th" value="{{ old('name_th', $campaign->name_th ?? '') }}" required maxlength="200"
               class="tp-input" style="width:100%;" placeholder="เช่น กำลังใจ+ความเชื่อ">
    </div>
    <div>
        <label class="tp-muted" style="font-size:12px;display:block;margin-bottom:4px;">Emoji</label>
        <input type="text" name="emoji" value="{{ old('emoji', $campaign->emoji ?? '') }}" maxlength="10"
               class="tp-input" style="width:100%;" placeholder="เช่น 💪">
    </div>
</div>

{{-- ── ตารางเวลาโพส: repeater กด + เพิ่มได้หลายเวลา (ไม่ใช่ 2 เวลาตายตัว) ── --}}
<div style="margin-top:14px;">
    <label class="tp-muted" style="font-size:12px;display:block;margin-bottom:6px;">
        <i class="fas fa-clock"></i> เวลาโพส (Asia/Bangkok) — กด “เพิ่มเวลา” ได้เรื่อยๆ
    </label>
    <div x-data="{ slots: {{ Illuminate\Support\Js::from($initialSlots) }}, max: 12 }">
        {{-- ส่งค่าเป็น schedule_text (comma-join) — controller parse ผ่าน normalizeSlot() เดิม --}}
        <input type="hidden" name="schedule_text" :value="slots.join(',')">

        <template x-for="(slot, idx) in slots" :key="idx">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                <input type="time" x-model="slots[idx]"
                       class="tp-input" style="max-width:200px;">
                <button type="button" @click="slots.splice(idx, 1)"
                        class="tp-btn" style="color:#d9534f;padding:0 12px;" title="ลบเวลานี้">
                    <i class="fas fa-trash-can"></i>
                </button>
            </div>
        </template>

        <template x-if="slots.length === 0">
            <p class="tp-muted" style="font-size:12px;margin:2px 0 8px;">ยังไม่มีเวลา — กด “เพิ่มเวลา” (ถ้าเว้นว่างแคมเปญจะไม่โพสอัตโนมัติ)</p>
        </template>

        <button type="button" @click="if (slots.length < max) slots.push('12:00')" :disabled="slots.length >= max"
                class="tp-btn tp-btn-sm" style="margin-top:4px;"
                :style="slots.length >= max ? 'opacity:.5;cursor:not-allowed;' : ''">
            <i class="fas fa-plus"></i> เพิ่มเวลา
        </button>
        <span class="tp-muted" style="font-size:11px;margin-left:10px;">
            <span x-text="slots.length"></span>/<span x-text="max"></span> เวลา · แนะนำไม่ถี่เกินไปกันโดน FB ลด reach
        </span>
    </div>
</div>

<div style="margin-top:12px;">
    <label class="tp-muted" style="font-size:12px;display:block;margin-bottom:4px;">คำอธิบายแนวคอนเทนต์ (AI ใช้เป็นทิศทางการเขียน)</label>
    <textarea name="description_th" rows="2" class="tp-input" style="width:100%;"
              placeholder="เช่น โพสให้กำลังใจคนท้อ ผสมความเชื่อ/ธรรมะเบาๆ อ่านแล้วใจฟู">{{ old('description_th', $campaign->description_th ?? '') }}</textarea>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px;margin-top:12px;">
    <div>
        <label class="tp-muted" style="font-size:12px;display:block;margin-bottom:4px;">แหล่งหัวข้อ</label>
        <select name="topic_source" class="tp-input" style="width:100%;">
            <option value="inline" {{ old('topic_source', $campaign->topic_source ?? 'inline') === 'inline' ? 'selected' : '' }}>
                กำหนดเองในแคมเปญนี้ (คีย์เวิร์ด/pool ด้านล่าง)
            </option>
            <option value="mystic_topics" {{ old('topic_source', $campaign->topic_source ?? '') === 'mystic_topics' ? 'selected' : '' }}>
                คลังหัวข้อสายมูเดิม (หมุน 5 หมวด LRU)
            </option>
        </select>
    </div>
    <div>
        <label class="tp-muted" style="font-size:12px;display:block;margin-bottom:4px;">บุคลิกผู้เขียน (persona)</label>
        <input type="text" name="persona" value="{{ old('persona', $campaign->persona ?? '') }}" maxlength="200"
               class="tp-input" style="width:100%;" placeholder="เว้นว่าง = ใช้ชื่อแบรนด์ (แม่หมอจันทรา)">
    </div>
    <div>
        <label class="tp-muted" style="font-size:12px;display:block;margin-bottom:4px;">สไตล์ภาพเสริม (ต่อท้าย prompt ที่ AI สร้างจากเนื้อหา)</label>
        <input type="text" name="image_style_hint" value="{{ old('image_style_hint', $campaign->image_style_hint ?? '') }}" maxlength="500"
               class="tp-input" style="width:100%;" placeholder="เช่น warm cinematic tone, golden hour">
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px;margin-top:12px;">
    <div>
        <label class="tp-muted" style="font-size:12px;display:block;margin-bottom:4px;">คีย์เวิร์ดแนวคอนเทนต์ (1 บรรทัด/คำ)</label>
        <textarea name="keywords_text" rows="4" class="tp-input" style="width:100%;"
                  placeholder="กำลังใจ&#10;ความหวัง&#10;ข้อคิดชีวิต">{{ old('keywords_text', $joinLines($campaign->keywords ?? [])) }}</textarea>
    </div>
    <div>
        <label class="tp-muted" style="font-size:12px;display:block;margin-bottom:4px;">หัวข้อย่อยสุ่ม (1 บรรทัด/หัวข้อ — เว้นว่าง = AI เลือกแง่มุมเอง)</label>
        <textarea name="sub_topic_pool_text" rows="4" class="tp-input" style="width:100%;"
                  placeholder="วันที่เหนื่อยที่สุด มักใกล้จุดเปลี่ยน&#10;ทำไมคนใจดีมักโดนเอาเปรียบ">{{ old('sub_topic_pool_text', $joinLines($campaign->sub_topic_pool ?? [])) }}</textarea>
    </div>
    <div>
        <label class="tp-muted" style="font-size:12px;display:block;margin-bottom:4px;">Hashtag pool (1 บรรทัด/แท็ก)</label>
        <textarea name="hashtag_pool_text" rows="4" class="tp-input" style="width:100%;"
                  placeholder="#กำลังใจ&#10;#ข้อคิดดีๆ">{{ old('hashtag_pool_text', $joinLines($campaign->hashtag_pool ?? [])) }}</textarea>
    </div>
    <div>
        <label class="tp-muted" style="font-size:12px;display:block;margin-bottom:4px;">คำค้น web search (1 บรรทัด/คำ — เว้นว่าง = สร้างอัตโนมัติ)</label>
        <textarea name="search_queries_text" rows="4" class="tp-input" style="width:100%;"
                  placeholder="วิธีให้กำลังใจตัวเอง&#10;จิตวิทยากำลังใจ">{{ old('search_queries_text', $joinLines($campaign->search_queries ?? [])) }}</textarea>
    </div>
</div>

<div style="margin-top:12px;">
    <label class="tp-muted" style="font-size:12px;display:block;margin-bottom:4px;">
        Custom prompt (ขั้นสูง — เว้นว่าง = ระบบสร้าง prompt จากข้อมูลด้านบนให้เอง)
    </label>
    <textarea name="content_prompt" rows="3" class="tp-input" style="width:100%;font-family:monospace;font-size:12px;"
              placeholder="ใช้ {sub_topic} {min_len} {max_len} เป็น placeholder ได้">{{ old('content_prompt', $campaign->content_prompt ?? '') }}</textarea>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-top:12px;align-items:end;">
    <div>
        <label class="tp-muted" style="font-size:12px;display:block;margin-bottom:4px;">Caption สั้นสุด (ตัวอักษร)</label>
        <input type="number" name="caption_min" value="{{ old('caption_min', $campaign->caption_min ?? '') }}" min="100" max="2000"
               class="tp-input" style="width:100%;" placeholder="ว่าง = ค่ากลาง (400)">
    </div>
    <div>
        <label class="tp-muted" style="font-size:12px;display:block;margin-bottom:4px;">Caption ยาวสุด</label>
        <input type="number" name="caption_max" value="{{ old('caption_max', $campaign->caption_max ?? '') }}" min="200" max="3000"
               class="tp-input" style="width:100%;" placeholder="ว่าง = ค่ากลาง (700)">
    </div>
    <div>
        <label class="tp-muted" style="font-size:12px;display:block;margin-bottom:4px;">จำนวน hashtag</label>
        <input type="number" name="hashtag_count" value="{{ old('hashtag_count', $campaign->hashtag_count ?? '') }}" min="0" max="10"
               class="tp-input" style="width:100%;" placeholder="ว่าง = ค่ากลาง (6)">
    </div>
    <div style="display:flex;flex-direction:column;gap:8px;">
        <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">
            <input type="hidden" name="use_web_search" value="0">
            <input type="checkbox" name="use_web_search" value="1"
                   {{ old('use_web_search', ($campaign->use_web_search ?? true) ? '1' : '0') == '1' ? 'checked' : '' }}>
            ค้นเว็บหาข้อมูลอ้างอิงก่อนเขียน
        </label>
        <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">
            <input type="hidden" name="is_enabled" value="0">
            <input type="checkbox" name="is_enabled" value="1"
                   {{ old('is_enabled', ($campaign->is_enabled ?? false) ? '1' : '0') == '1' ? 'checked' : '' }}>
            เปิดใช้งานแคมเปญ
        </label>
    </div>
</div>
