{{--
 | Theme Studio — แผงปรับแต่งธีม "นวลทองคำ" (ปุ่ม 🎨 มุมขวาล่าง)
 | ผูกกับ $store.tp ทั้งหมด — โหมด / พาเลตต์ / สีพื้นผิว / พื้นผิว(variant) / ลาวา / กระจก
 | นี่คือ "ระบบคัสตอมสีในตัว" ของธีม — ไม่ใช่ระบบเลือกหลายธีม
 --}}
@php
    $tpPalettes = [
        'ทองคำ' => ['#e6b347','#d98e3f'], 'นวลชมพู' => ['#e69ec0','#b79ae8'],
        'ลาเวนเดอร์' => ['#c4a6e0','#9fb0e8'], 'มินต์' => ['#86cfae','#8fbfe0'],
        'มหาสมุทร' => ['#6fc3d4','#7d9ee0'], 'พระอาทิตย์' => ['#f0a86a','#ec828f'],
    ];
    $tpSurfaces = ['ครีม'=>null,'พีช'=>70,'ทราย'=>45,'กุหลาบ'=>20,'ม่วง'=>330,'คราม'=>285,'ฟ้า'=>230,'เขียวน้ำ'=>195,'เซจ'=>150];
    $tpVariants = ['นวลนุ่ม Clay','แบนทอง Flat','กระจกใส Glass'];
@endphp

<div>
    {{-- ปุ่มเปิด --}}
    <button type="button" @click="$store.tp.studioOpen = !$store.tp.studioOpen"
            class="tp-tile"
            style="position:fixed; right:20px; bottom:20px; z-index:70; width:54px; height:54px; border-radius:18px; font-size:22px; cursor:pointer; border:0;"
            title="ปรับแต่งธีม (Theme Studio)">
        <i class="fas fa-palette"></i>
    </button>

    {{-- ฉากมืด --}}
    <div x-show="$store.tp.studioOpen" x-cloak @click="$store.tp.studioOpen=false" x-transition.opacity
         style="position:fixed; inset:0; z-index:75; background:rgba(0,0,0,.35);"></div>

    {{-- แผง --}}
    <div x-show="$store.tp.studioOpen" x-cloak
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
         class="tp-root"
         style="position:fixed; right:0; top:0; bottom:0; z-index:80; width:min(360px,92vw); background:var(--bg); box-shadow:-12px 0 40px rgba(0,0,0,.25); padding:20px; overflow-y:auto; display:flex; flex-direction:column; gap:18px;">

        <div style="display:flex; align-items:center; gap:10px;">
            <span class="tp-tile" style="width:40px; height:40px; border-radius:13px; font-size:18px;"><i class="fas fa-palette"></i></span>
            <div style="flex:1;">
                <div style="font-weight:700; font-size:16px;">Theme Studio</div>
                <div style="font-size:11px; color:var(--ink2);">ปรับแต่งธีมนวลทองคำ</div>
            </div>
            <button type="button" @click="$store.tp.studioOpen=false" class="tp-icon-btn" style="width:36px; height:36px; border-radius:11px; color:var(--ink2);"><i class="fas fa-xmark"></i></button>
        </div>

        {{-- โหมด สว่าง/มืด --}}
        <div>
            <div class="tp-studio-h">โหมด</div>
            <div class="tp-well" style="display:flex; padding:4px; gap:4px; border-radius:13px;">
                <button type="button" @click="if($store.tp.dark){$store.tp.toggleDark()}" class="tp-seg" :style="!$store.tp.dark && 'box-shadow:var(--raise); background:linear-gradient(135deg,var(--accent1),var(--accent2)); color:#fff;'">☀️ สว่าง</button>
                <button type="button" @click="if(!$store.tp.dark){$store.tp.toggleDark()}" class="tp-seg" :style="$store.tp.dark && 'box-shadow:var(--raise); background:linear-gradient(135deg,var(--accent1),var(--accent2)); color:#fff;'">🌙 มืด</button>
            </div>
        </div>

        {{-- พาเลตต์สีหลัก --}}
        <div>
            <div class="tp-studio-h">สีหลัก</div>
            <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:8px;">
                @foreach($tpPalettes as $name => $cols)
                    <button type="button" @click="$store.tp.setPalette('{{ $name }}')"
                            class="tp-card" style="cursor:pointer; padding:8px; border-radius:13px; display:flex; flex-direction:column; align-items:center; gap:6px;"
                            :style="$store.tp.colorMode==='preset' && $store.tp.palette==='{{ $name }}' ? 'box-shadow:var(--inset);' : 'box-shadow:var(--raise);'">
                        <span style="width:100%; height:26px; border-radius:8px; background:linear-gradient(135deg,{{ $cols[0] }},{{ $cols[1] }});"></span>
                        <span style="font-size:10.5px; color:var(--ink2); font-weight:600;">{{ $name }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- สีพื้นผิว --}}
        <div>
            <div class="tp-studio-h">สีพื้นผิว</div>
            <div style="display:flex; flex-wrap:wrap; gap:6px;">
                @foreach($tpSurfaces as $name => $hue)
                    <button type="button" @click="$store.tp.setSurface('{{ $name }}')"
                            class="tp-btn tp-btn-sm"
                            :style="( '{{ $hue === null ? 'cream' : $hue }}'==='cream' ? ($store.tp.surfMode==='preset') : ($store.tp.surfMode==='custom' && $store.tp.surfHue==={{ $hue ?? 0 }}) ) ? 'box-shadow:var(--inset); color:var(--deep1);' : ''">
                        {{ $name }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- พื้นผิว variant --}}
        <div>
            <div class="tp-studio-h">พื้นผิว</div>
            <div class="tp-well" style="display:flex; padding:4px; gap:4px; border-radius:13px;">
                @foreach($tpVariants as $v)
                    <button type="button" @click="$store.tp.setVariant('{{ $v }}')" class="tp-seg" :style="$store.tp.variant==='{{ $v }}' && 'box-shadow:var(--raise); background:linear-gradient(135deg,var(--accent1),var(--accent2)); color:#fff;'">{{ \Illuminate\Support\Str::before($v,' ') }}</button>
                @endforeach
            </div>
        </div>

        {{-- ลาวา --}}
        <div>
            <div class="tp-studio-h" style="display:flex; justify-content:space-between;">ความเข้มลาวา <span class="tp-num" x-text="$store.tp.lavaLevel"></span></div>
            <input type="range" min="0" max="100" x-model.number="$store.tp.lavaLevel" @input="$store.tp.commit()" class="tp-range">
            <div class="tp-studio-h" style="display:flex; justify-content:space-between; margin-top:10px;">ความเร็วลาวา <span class="tp-num" x-text="$store.tp.lavaSpeed"></span></div>
            <input type="range" min="0" max="100" x-model.number="$store.tp.lavaSpeed" @input="$store.tp.commit()" class="tp-range">
        </div>

        {{-- กระจก --}}
        <div>
            <div class="tp-studio-h" style="display:flex; justify-content:space-between;">ความใสกระจก <span class="tp-num" x-text="$store.tp.glassLevel"></span></div>
            <input type="range" min="0" max="100" x-model.number="$store.tp.glassLevel" @input="$store.tp.commit()" class="tp-range">
        </div>

        <button type="button" @click="$store.tp.reset()" class="tp-btn" style="width:100%;">
            <i class="fas fa-rotate-left"></i> รีเซ็ตเป็นค่าเริ่มต้น
        </button>
    </div>
</div>
{{-- หมายเหตุ: คลาส .tp-studio-h / .tp-seg / .tp-range อยู่ใน resources/css/theme-v4.css --}}
