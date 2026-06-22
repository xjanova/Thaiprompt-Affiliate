@extends('layouts.admin-v4')

@section('title', 'Rich Menu - แม่หมอจันทรา')

@section('content')
{{-- หน้า Deploy Rich Menu ธีม V4 นวลทองคำ — คง Alpine richMenuDeploy() + AJAX preview/deploy/re-set เดิมทั้งหมด --}}
<div x-data="richMenuDeploy()" style="display:flex; flex-direction:column; gap:18px;">

    {{-- Header (ธีม V4) --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ระบบดูดวง · RICH MENU</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">Rich Menu แม่หมอจันทรา 🔮</h1>
            <p class="tp-muted" style="margin:6px 0 0; font-size:13px;">สร้างและ deploy Rich Menu สำหรับบอทดูดวงบน LINE</p>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:10px;">
            @if(Route::has('admin.fortune.dashboard'))
            <a href="{{ route('admin.fortune.dashboard') }}" class="tp-btn">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
            @endif
            @if(Route::has('admin.fortune.channels.index'))
            <a href="{{ route('admin.fortune.channels.index') }}" class="tp-btn">
                <i class="fas fa-satellite-dish"></i> ช่องทาง
            </a>
            @endif
            @if(Route::has('admin.fortune.rich-menu.editor'))
            <a href="{{ route('admin.fortune.rich-menu.editor') }}" class="tp-btn tp-btn-primary">
                <i class="fas fa-pen-ruler"></i> Editor
            </a>
            @endif
        </div>
    </div>

    {{-- สถานะ Rich Menu ปัจจุบัน --}}
    <div class="tp-card">
        <div class="tp-section-h" style="display:flex; align-items:center; gap:8px; margin-bottom:14px;">
            <i class="fas fa-thumbtack" style="color:var(--accent2);"></i>
            <span>สถานะ Rich Menu ปัจจุบัน</span>
        </div>

        @if($activeMenu)
            <div style="display:flex; flex-wrap:wrap; gap:18px;">
                {{-- ข้อมูล --}}
                <div style="flex:1 1 320px; min-width:280px;">
                    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:12px;">
                        {{-- สถานะ (เขียว) --}}
                        <div class="tp-tile tp-inset-sm" style="padding:14px;">
                            <div class="tp-muted" style="font-size:11px; font-weight:700; letter-spacing:.4px; text-transform:uppercase;">สถานะ</div>
                            <div style="display:flex; align-items:center; gap:8px; margin-top:6px;">
                                <span style="width:9px; height:9px; border-radius:50%; background:#5aa07e; box-shadow:0 0 0 3px rgba(90,160,126,.18);"></span>
                                <span class="tp-num" style="font-size:18px; font-weight:800; color:#5aa07e;">Active</span>
                            </div>
                        </div>

                        {{-- Rich Menu ID (ม่วง) --}}
                        <div class="tp-tile tp-inset-sm" style="padding:14px;">
                            <div class="tp-muted" style="font-size:11px; font-weight:700; letter-spacing:.4px; text-transform:uppercase;">Rich Menu ID</div>
                            <div class="tp-num" style="font-size:13px; font-family:ui-monospace,monospace; color:#b79ae8; margin-top:6px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $activeMenu->rich_menu_id }}">{{ $activeMenu->rich_menu_id }}</div>
                        </div>

                        {{-- Deploy เมื่อ (ฟ้า) --}}
                        <div class="tp-tile tp-inset-sm" style="padding:14px;">
                            <div class="tp-muted" style="font-size:11px; font-weight:700; letter-spacing:.4px; text-transform:uppercase;">Deploy เมื่อ</div>
                            <div class="tp-num" style="font-size:14px; font-weight:800; color:#5689b8; margin-top:6px;">{{ $activeMenu->created_at->diffForHumans() }}</div>
                        </div>

                        {{-- Deploy โดย (กลาง) --}}
                        <div class="tp-tile tp-inset-sm" style="padding:14px;">
                            <div class="tp-muted" style="font-size:11px; font-weight:700; letter-spacing:.4px; text-transform:uppercase;">Deploy โดย</div>
                            <div class="tp-num" style="font-size:14px; font-weight:800; color:var(--ink); margin-top:6px;">{{ $activeMenu->creator->name ?? 'System' }}</div>
                        </div>
                    </div>
                </div>

                {{-- ภาพ Rich Menu --}}
                @if($activeMenu->image_path)
                <div style="flex:0 0 320px; max-width:100%;">
                    <div class="tp-inset" style="padding:8px; border-radius:14px;">
                        <img src="{{ asset('storage/'.$activeMenu->image_path) }}"
                             alt="Rich Menu"
                             style="width:100%; border-radius:10px; display:block;">
                    </div>
                </div>
                @endif
            </div>
        @else
            {{-- Empty state --}}
            <div style="text-align:center; padding:40px 16px;">
                <i class="fas fa-inbox" style="font-size:42px; color:var(--ink2); opacity:.55;"></i>
                <p class="tp-num" style="font-size:17px; font-weight:700; margin:14px 0 4px;">ยังไม่มี Rich Menu ที่ active</p>
                <p class="tp-muted" style="font-size:13px; margin:0;">กดปุ่ม "Deploy Rich Menu" เพื่อสร้างและ deploy Rich Menu ใหม่</p>
            </div>
        @endif
    </div>

    {{-- จัดการ Rich Menu (Actions) --}}
    <div class="tp-card">
        <div class="tp-section-h" style="display:flex; align-items:center; gap:8px; margin-bottom:14px;">
            <i class="fas fa-rocket" style="color:var(--accent1);"></i>
            <span>จัดการ Rich Menu</span>
        </div>

        <div style="display:flex; flex-wrap:wrap; gap:12px;">
            {{-- Preview --}}
            <button @click="previewRichMenu()" :disabled="loading" class="tp-btn"
                    style="font-weight:700;" :style="loading ? 'opacity:.55; cursor:not-allowed;' : ''">
                <template x-if="previewLoading">
                    <i class="fas fa-spinner fa-spin"></i>
                </template>
                <template x-if="!previewLoading">
                    <i class="fas fa-image" style="color:#b79ae8;"></i>
                </template>
                <span>Preview ภาพ</span>
            </button>

            {{-- Deploy --}}
            <button @click="deployRichMenu()" :disabled="loading" class="tp-btn tp-btn-primary"
                    style="font-weight:700;" :style="loading ? 'opacity:.55; cursor:not-allowed;' : ''">
                <template x-if="deployLoading">
                    <i class="fas fa-spinner fa-spin"></i>
                </template>
                <template x-if="!deployLoading">
                    <i class="fas fa-rocket"></i>
                </template>
                <span>Deploy Rich Menu</span>
            </button>

            {{-- Re-set Default (ถ้ามี Rich Menu active) --}}
            @if($activeMenu)
            <button @click="reSetDefault()" :disabled="loading" class="tp-btn"
                    style="font-weight:700;" :style="loading ? 'opacity:.55; cursor:not-allowed;' : ''">
                <template x-if="reSetLoading">
                    <i class="fas fa-spinner fa-spin"></i>
                </template>
                <template x-if="!reSetLoading">
                    <i class="fas fa-rotate" style="color:#d6824a;"></i>
                </template>
                <span>Re-set Default</span>
            </button>
            @endif

            {{-- เช็คสถานะบน LINE --}}
            @if(Route::has('admin.fortune.rich-menu.check-line-status'))
            <button @click="checkLineStatus()" :disabled="loading" class="tp-btn"
                    style="font-weight:700;" :style="loading ? 'opacity:.55; cursor:not-allowed;' : ''">
                <template x-if="checkLoading">
                    <i class="fas fa-spinner fa-spin"></i>
                </template>
                <template x-if="!checkLoading">
                    <i class="fas fa-circle-check" style="color:#5689b8;"></i>
                </template>
                <span>เช็คสถานะบน LINE</span>
            </button>
            @endif
        </div>

        {{-- คำอธิบายปุ่ม --}}
        <div class="tp-well" style="margin-top:16px; padding:14px 16px; display:flex; flex-direction:column; gap:6px;">
            <div style="font-size:13px; color:var(--ink2);"><i class="fas fa-image" style="color:#b79ae8; width:18px;"></i> <strong style="color:var(--ink);">Preview</strong> — สร้างภาพดูตัวอย่าง (ไม่ deploy)</div>
            <div style="font-size:13px; color:var(--ink2);"><i class="fas fa-rocket" style="color:var(--accent1); width:18px;"></i> <strong style="color:var(--ink);">Deploy</strong> — สร้างใหม่ทั้งหมดแล้ว deploy ไป LINE</div>
            <div style="font-size:13px; color:var(--ink2);"><i class="fas fa-rotate" style="color:#d6824a; width:18px;"></i> <strong style="color:var(--ink);">Re-set Default</strong> — ใช้เมื่อ Rich Menu ไม่ขึ้น ส่งคำสั่ง set default อีกครั้ง (ไม่สร้างภาพใหม่)</div>
            @if(Route::has('admin.fortune.rich-menu.check-line-status'))
            <div style="font-size:13px; color:var(--ink2);"><i class="fas fa-circle-check" style="color:#5689b8; width:18px;"></i> <strong style="color:var(--ink);">เช็คสถานะบน LINE</strong> — ตรวจว่า default Rich Menu บน LINE ตรงกับ DB หรือไม่</div>
            @endif
        </div>

        {{-- Preview Image --}}
        <div x-show="previewUrl" x-cloak style="margin-top:18px;">
            <div class="tp-section-h" style="margin-bottom:10px;">ตัวอย่างภาพ Rich Menu</div>
            <div class="tp-inset" style="padding:8px; border-radius:14px; max-width:680px;">
                <img :src="previewUrl" alt="Preview Rich Menu" style="width:100%; border-radius:10px; display:block;">
            </div>
        </div>

        {{-- ผลลัพธ์: สำเร็จ --}}
        <div x-show="successMessage" x-cloak
             style="margin-top:16px; padding:13px 16px; border-radius:12px; background:rgba(90,160,126,.12); border:1px solid rgba(90,160,126,.35);">
            <p style="margin:0; color:#5aa07e; font-weight:700; font-size:14px;"><i class="fas fa-circle-check"></i> <span x-text="successMessage"></span></p>
        </div>

        {{-- ผลลัพธ์: ผิดพลาด --}}
        <div x-show="errorMessage" x-cloak
             style="margin-top:16px; padding:13px 16px; border-radius:12px; background:rgba(217,83,79,.12); border:1px solid rgba(217,83,79,.35);">
            <p style="margin:0; color:#d9534f; font-weight:700; font-size:14px;"><i class="fas fa-circle-exclamation"></i> <span x-text="errorMessage"></span></p>
        </div>

        {{-- ผลลัพธ์เช็คสถานะ LINE --}}
        <div x-show="lineStatus" x-cloak class="tp-well" style="margin-top:16px; padding:14px 16px;">
            <div class="tp-section-h" style="margin-bottom:10px;"><i class="fab fa-line" style="color:#06C755;"></i> สถานะบน LINE Platform</div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:10px;">
                <div class="tp-inset-sm" style="padding:11px 13px; border-radius:10px;">
                    <div class="tp-muted" style="font-size:11px; font-weight:700; text-transform:uppercase;">ความตรงกัน DB ↔ LINE</div>
                    <div style="margin-top:5px; font-weight:800; font-size:14px;"
                         :style="lineStatus && lineStatus.is_matched ? 'color:#5aa07e;' : 'color:#d9534f;'">
                        <span x-text="lineStatus && lineStatus.is_matched ? '✓ ตรงกัน' : '✗ ไม่ตรงกัน'"></span>
                    </div>
                </div>
                <div class="tp-inset-sm" style="padding:11px 13px; border-radius:10px;">
                    <div class="tp-muted" style="font-size:11px; font-weight:700; text-transform:uppercase;">Default บน LINE</div>
                    <div class="tp-num" style="margin-top:5px; font-family:ui-monospace,monospace; font-size:12px; color:var(--ink); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"
                         x-text="(lineStatus && lineStatus.line_default_rich_menu_id) || '—'"></div>
                </div>
                <div class="tp-inset-sm" style="padding:11px 13px; border-radius:10px;">
                    <div class="tp-muted" style="font-size:11px; font-weight:700; text-transform:uppercase;">Active ใน DB</div>
                    <div class="tp-num" style="margin-top:5px; font-family:ui-monospace,monospace; font-size:12px; color:var(--ink); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"
                         x-text="(lineStatus && lineStatus.db_active_rich_menu_id) || '—'"></div>
                </div>
                <div class="tp-inset-sm" style="padding:11px 13px; border-radius:10px;">
                    <div class="tp-muted" style="font-size:11px; font-weight:700; text-transform:uppercase;">Rich Menu ทั้งหมดบน LINE</div>
                    <div class="tp-num" style="margin-top:5px; font-weight:800; font-size:16px; color:#5689b8;"
                         x-text="(lineStatus && lineStatus.total_rich_menus_on_line) || 0"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ประวัติ Deploy --}}
    @if($deployHistory->isNotEmpty())
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div class="tp-section-h" style="display:flex; align-items:center; gap:8px; padding:18px 18px 0;">
            <i class="fas fa-clock-rotate-left" style="color:var(--accent2);"></i>
            <span>ประวัติ Deploy</span>
        </div>

        <div style="overflow-x:auto; margin-top:14px;">
            <table style="width:100%; border-collapse:collapse; min-width:520px;">
                <thead>
                    <tr style="text-align:left;">
                        <th style="padding:11px 18px; font-size:11px; font-weight:700; letter-spacing:.4px; text-transform:uppercase; color:var(--ink2); border-bottom:1px solid var(--sd);">Rich Menu ID</th>
                        <th style="padding:11px 18px; font-size:11px; font-weight:700; letter-spacing:.4px; text-transform:uppercase; color:var(--ink2); border-bottom:1px solid var(--sd); text-align:center;">สถานะ</th>
                        <th style="padding:11px 18px; font-size:11px; font-weight:700; letter-spacing:.4px; text-transform:uppercase; color:var(--ink2); border-bottom:1px solid var(--sd);">วันที่ Deploy</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($deployHistory as $menu)
                    <tr style="transition:background .15s;" onmouseover="this.style.background='var(--inset-sm)'" onmouseout="this.style.background='transparent'">
                        <td style="padding:13px 18px; font-size:13px; font-family:ui-monospace,monospace; color:var(--ink); border-bottom:1px solid var(--sd); max-width:240px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $menu->rich_menu_id }}">
                            {{ $menu->rich_menu_id }}
                        </td>
                        <td style="padding:13px 18px; border-bottom:1px solid var(--sd); text-align:center;">
                            @if($menu->is_active)
                                <span class="tp-pill" style="background:rgba(90,160,126,.15); color:#5aa07e; font-weight:700;">Active</span>
                            @else
                                <span class="tp-pill tp-pill-soft" style="color:var(--ink2);">Inactive</span>
                            @endif
                        </td>
                        <td style="padding:13px 18px; font-size:13px; color:var(--ink2); border-bottom:1px solid var(--sd);">
                            <span class="tp-num" style="color:var(--ink);">{{ $menu->created_at->format('d/m/Y H:i') }}</span>
                            <span class="tp-muted" style="font-size:11px;"> ({{ $menu->created_at->diffForHumans() }})</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

{{-- scripts stack: Alpine component richMenuDeploy() — คง endpoint + payload + logic AJAX เดิมทุกตัว --}}
@push('scripts')
<script>
function richMenuDeploy() {
    return {
        loading: false,
        previewLoading: false,
        deployLoading: false,
        reSetLoading: false,
        checkLoading: false,
        previewUrl: null,
        successMessage: null,
        errorMessage: null,
        lineStatus: null,

        async previewRichMenu() {
            this.previewLoading = true;
            this.loading = true;
            this.successMessage = null;
            this.errorMessage = null;

            try {
                const response = await fetch('{{ route("admin.fortune.rich-menu.preview") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                });
                const data = await response.json();

                if (data.success) {
                    this.previewUrl = data.preview_url;
                    this.successMessage = 'สร้างภาพ Preview สำเร็จ!';
                } else {
                    this.errorMessage = data.error || 'ไม่สามารถสร้าง Preview ได้';
                }
            } catch (e) {
                this.errorMessage = 'เกิดข้อผิดพลาด: ' + e.message;
            } finally {
                this.previewLoading = false;
                this.loading = false;
            }
        },

        async reSetDefault() {
            this.reSetLoading = true;
            this.loading = true;
            this.successMessage = null;
            this.errorMessage = null;

            try {
                const response = await fetch('{{ route("admin.fortune.rich-menu.re-set-default") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                });
                const data = await response.json();

                if (data.success) {
                    this.successMessage = data.message || 'Re-set default สำเร็จ!';
                } else {
                    this.errorMessage = data.error || 'Re-set default ไม่สำเร็จ';
                }
            } catch (e) {
                this.errorMessage = 'เกิดข้อผิดพลาด: ' + e.message;
            } finally {
                this.reSetLoading = false;
                this.loading = false;
            }
        },

        async deployRichMenu() {
            if (!confirm('ยืนยันการ Deploy Rich Menu?\n\nRich Menu เดิมจะถูกแทนที่ด้วย Rich Menu ใหม่')) {
                return;
            }

            this.deployLoading = true;
            this.loading = true;
            this.successMessage = null;
            this.errorMessage = null;

            try {
                const response = await fetch('{{ route("admin.fortune.rich-menu.deploy") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                });
                const data = await response.json();

                if (data.success) {
                    this.successMessage = 'Deploy สำเร็จ! Rich Menu ID: ' + data.rich_menu_id;
                    setTimeout(() => window.location.reload(), 2000);
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

@if(Route::has('admin.fortune.rich-menu.check-line-status'))
        async checkLineStatus() {
            this.checkLoading = true;
            this.loading = true;
            this.successMessage = null;
            this.errorMessage = null;

            try {
                const response = await fetch('{{ route("admin.fortune.rich-menu.check-line-status") }}', {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                });
                const data = await response.json();

                if (data.success) {
                    this.lineStatus = data;
                    this.successMessage = data.is_matched
                        ? 'Rich Menu บน LINE ตรงกับ DB'
                        : 'Rich Menu บน LINE ไม่ตรงกับ DB — ลอง Re-set Default';
                } else {
                    this.errorMessage = data.error || 'เช็คสถานะ LINE ไม่สำเร็จ';
                }
            } catch (e) {
                this.errorMessage = 'เกิดข้อผิดพลาด: ' + e.message;
            } finally {
                this.checkLoading = false;
                this.loading = false;
            }
        },
@endif
    };
}
</script>
@endpush
@endsection
