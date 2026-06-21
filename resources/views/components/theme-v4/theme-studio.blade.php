{{--
 | Theme Studio — แผงปรับแต่งธีม "นวลทองคำ" (ปุ่ม 🎨 มุมขวาล่าง)
 | ผูกกับ $store.tp ทั้งหมด — สีหลัก (พรีเซ็ต/กำหนดเอง oklch) / สีพื้นผิว / variant / โหมด / ลาวา / กระจก
 | นี่คือ "ระบบคัสตอมสีในตัว" ของธีม — ไม่ใช่ระบบเลือกหลายธีม
 --}}
@php
    $tpHues = [14, 34, 52, 135, 165, 192, 226, 262, 298, 328, 350];   // สีหลัก custom (oklch hue)
    $tpSurfHues = [70, 45, 20, 330, 285, 230, 195, 150];               // สีพื้นผิว custom
    $tpPalettes = [
        'ทองคำ' => ['#e6b347','#d98e3f'], 'นวลชมพู' => ['#e69ec0','#b79ae8'],
        'ลาเวนเดอร์' => ['#c4a6e0','#9fb0e8'], 'มินต์' => ['#86cfae','#8fbfe0'],
        'มหาสมุทร' => ['#6fc3d4','#7d9ee0'], 'พระอาทิตย์' => ['#f0a86a','#ec828f'],
    ];
    $tpVariants = ['นวลนุ่ม Clay','แบนทอง Flat','กระจกใส Glass'];
    $swActive = '0 0 0 2px var(--surf), 0 0 0 4px var(--ink)';   // วงแหวนเลือก
@endphp

{{-- x-data="{}" จำเป็น: Alpine v3 init เฉพาะ subtree ที่มี x-data root --}}
<div x-data="{}">
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
         class="tp-root tp-studio-panel"
         style="position:fixed; right:0; top:0; bottom:0; z-index:80; width:min(360px,92vw); background:var(--bg); box-shadow:-12px 0 40px rgba(0,0,0,.25); padding:20px; overflow-y:auto;">

        <div style="display:flex; align-items:center; gap:10px;">
            <span class="tp-tile" style="width:40px; height:40px; border-radius:13px; font-size:18px;"><i class="fas fa-palette"></i></span>
            <div style="flex:1;">
                <div style="font-weight:700; font-size:16px;">Theme Studio</div>
                <div style="font-size:11px; color:var(--ink2);">ปรับแต่งธีมนวลทองคำ</div>
            </div>
            <button type="button" @click="$store.tp.studioOpen=false" class="tp-icon-btn" style="width:36px; height:36px; border-radius:11px; color:var(--ink2);"><i class="fas fa-xmark"></i></button>
        </div>

        {{-- สีหลัก กำหนดเอง (11 เฉด oklch) --}}
        <div>
            <div class="tp-studio-h">สีหลัก · MAIN COLOR</div>
            <div style="display:flex; flex-wrap:wrap; gap:9px;">
                @foreach($tpHues as $h)
                    <button type="button" @click="$store.tp.setHue({{ $h }})" class="tp-sw"
                            style="background:linear-gradient(135deg, oklch(0.73 0.145 {{ $h }}), oklch(0.66 0.145 {{ $h + 16 }}));"
                            :style="{ boxShadow: ($store.tp.colorMode==='custom' && $store.tp.baseHue==={{ $h }}) ? '{{ $swActive }}' : 'var(--raise)' }"></button>
                @endforeach
            </div>
            {{-- ไล่เฉดสองสี --}}
            <button type="button" @click="$store.tp.toggleGradient()" class="tp-toggle-btn" style="margin-top:14px;"
                    :style="{ boxShadow: $store.tp.gradientOn ? 'var(--raise)' : 'var(--inset-sm)', background: $store.tp.gradientOn ? 'linear-gradient(135deg,var(--accent1),var(--accent2))' : 'var(--surf)', color: $store.tp.gradientOn ? '#fff' : 'var(--ink2)' }">
                <span>ไล่เฉดสองสี · Gradient</span><span style="font-weight:700;" x-text="$store.tp.gradientOn ? 'เปิด' : 'ปิด'"></span>
            </button>
            {{-- สีปลายเฉด --}}
            <div x-show="$store.tp.gradientOn" x-cloak style="margin-top:13px;">
                <div class="tp-studio-h">สีปลายเฉด · GRADIENT END</div>
                <div style="display:flex; flex-wrap:wrap; gap:9px;">
                    @foreach($tpHues as $h)
                        <button type="button" @click="$store.tp.setEndHue({{ $h }})" class="tp-sw"
                                style="background:linear-gradient(135deg, oklch(0.73 0.145 {{ $h }}), oklch(0.66 0.145 {{ $h + 16 }}));"
                                :style="{ boxShadow: ($store.tp.colorMode==='custom' && $store.tp.gradientOn && $store.tp.endHue==={{ $h }}) ? '{{ $swActive }}' : 'var(--raise)' }"></button>
                    @endforeach
                </div>
            </div>
        </div>

        <hr class="tp-divider">

        {{-- พรีเซ็ตสำเร็จรูป --}}
        <div>
            <div class="tp-studio-h">พรีเซ็ตสำเร็จรูป · PRESETS</div>
            <div style="display:flex; flex-wrap:wrap; gap:8px;">
                @foreach($tpPalettes as $name => $cols)
                    <button type="button" @click="$store.tp.setPalette('{{ $name }}')"
                            style="cursor:pointer; border:0; display:flex; align-items:center; gap:7px; padding:6px 12px 6px 6px; border-radius:20px; font-family:inherit; font-size:11.5px; font-weight:600; color:var(--ink); background:var(--surf);"
                            :style="{ boxShadow: ($store.tp.colorMode==='preset' && $store.tp.palette==='{{ $name }}') ? '{{ $swActive }}' : 'var(--raise)' }">
                        <span style="width:20px; height:20px; border-radius:50%; background:linear-gradient(135deg,{{ $cols[0] }},{{ $cols[1] }});"></span>{{ $name }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- สีพื้นผิว (ครีม + 8 เฉด) --}}
        <div>
            <div class="tp-studio-h">สีพื้นผิว · SURFACE TONE</div>
            <div style="display:flex; flex-wrap:wrap; gap:9px; align-items:center;">
                <button type="button" @click="$store.tp.setSurfDefault()" class="tp-btn tp-btn-sm"
                        :style="{ boxShadow: $store.tp.surfMode==='preset' ? 'var(--inset)' : 'var(--raise)', color: $store.tp.surfMode==='preset' ? 'var(--deep1)' : 'var(--ink)' }">ครีม</button>
                @foreach($tpSurfHues as $h)
                    <button type="button" @click="$store.tp.setSurfHue({{ $h }})" class="tp-sw"
                            style="background:oklch(0.9 0.04 {{ $h }});"
                            :style="{ boxShadow: ($store.tp.surfMode==='custom' && $store.tp.surfHue==={{ $h }}) ? '{{ $swActive }}' : 'var(--raise)' }"></button>
                @endforeach
            </div>
            {{-- ไล่เฉดพื้นหลัง --}}
            <button type="button" @click="$store.tp.toggleBgGrad()" class="tp-toggle-btn" style="margin-top:14px;"
                    :style="{ boxShadow: $store.tp.bgGradOn ? 'var(--raise)' : 'var(--inset-sm)', background: $store.tp.bgGradOn ? 'linear-gradient(135deg,var(--accent1),var(--accent2))' : 'var(--surf)', color: $store.tp.bgGradOn ? '#fff' : 'var(--ink2)' }">
                <span>ไล่เฉดพื้นหลัง · Surface</span><span style="font-weight:700;" x-text="$store.tp.bgGradOn ? 'เปิด' : 'ปิด'"></span>
            </button>
            <div x-show="$store.tp.bgGradOn" x-cloak style="margin-top:13px;">
                <div class="tp-studio-h">โทนปลายเฉด · END TONE</div>
                <div style="display:flex; flex-wrap:wrap; gap:9px;">
                    @foreach($tpSurfHues as $h)
                        <button type="button" @click="$store.tp.setSurfEnd({{ $h }})" class="tp-sw"
                                style="background:oklch(0.9 0.04 {{ $h }});"
                                :style="{ boxShadow: ($store.tp.surfMode==='custom' && $store.tp.bgGradOn && $store.tp.surfHue2==={{ $h }}) ? '{{ $swActive }}' : 'var(--raise)' }"></button>
                    @endforeach
                </div>
            </div>
        </div>

        <hr class="tp-divider">

        {{-- พื้นผิว variant --}}
        <div>
            <div class="tp-studio-h">พื้นผิว · SURFACE</div>
            <div class="tp-well" style="display:flex; padding:4px; gap:4px; border-radius:13px;">
                @foreach($tpVariants as $v)
                    <button type="button" @click="$store.tp.setVariant('{{ $v }}')" class="tp-seg" :style="$store.tp.variant==='{{ $v }}' && 'box-shadow:var(--raise); background:linear-gradient(135deg,var(--accent1),var(--accent2)); color:#fff;'">{{ \Illuminate\Support\Str::before($v,' ') }}</button>
                @endforeach
            </div>
        </div>

        {{-- โหมด สว่าง/มืด --}}
        <div>
            <div class="tp-studio-h">โหมด · MODE</div>
            <div class="tp-well" style="display:flex; padding:4px; gap:4px; border-radius:13px;">
                <button type="button" @click="if($store.tp.dark){$store.tp.toggleDark()}" class="tp-seg" :style="!$store.tp.dark && 'box-shadow:var(--raise); background:linear-gradient(135deg,var(--accent1),var(--accent2)); color:#fff;'">☀️ สว่าง</button>
                <button type="button" @click="if(!$store.tp.dark){$store.tp.toggleDark()}" class="tp-seg" :style="$store.tp.dark && 'box-shadow:var(--raise); background:linear-gradient(135deg,var(--accent1),var(--accent2)); color:#fff;'">🌙 มืด</button>
            </div>
        </div>

        {{-- ลาวา + กระจก --}}
        <div>
            <div class="tp-studio-h" style="display:flex; justify-content:space-between;">ความเข้มลาวา <span class="tp-num" x-text="$store.tp.lavaLevel"></span></div>
            <input type="range" min="0" max="100" x-model.number="$store.tp.lavaLevel" @input="$store.tp.commit()" class="tp-range">
            <div class="tp-studio-h" style="display:flex; justify-content:space-between; margin-top:12px;">ความเร็วลาวา <span class="tp-num" x-text="$store.tp.lavaSpeed"></span></div>
            <input type="range" min="0" max="100" x-model.number="$store.tp.lavaSpeed" @input="$store.tp.commit()" class="tp-range">
            <div class="tp-studio-h" style="display:flex; justify-content:space-between; margin-top:12px;">ความใสกระจก <span class="tp-num" x-text="$store.tp.glassLevel"></span></div>
            <input type="range" min="0" max="100" x-model.number="$store.tp.glassLevel" @input="$store.tp.commit()" class="tp-range">
        </div>

        <button type="button" @click="$store.tp.reset()" class="tp-btn" style="width:100%;">
            <i class="fas fa-rotate-left"></i> รีเซ็ตเป็นค่าเริ่มต้น
        </button>
    </div>
</div>
{{-- หมายเหตุ: คลาส .tp-sw / .tp-toggle-btn / .tp-studio-* อยู่ใน resources/css/theme-v4.css --}}
