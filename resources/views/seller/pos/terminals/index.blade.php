@extends('layouts.seller-v4')

@section('title', 'เครื่อง POS (Desktop)')

@php
    $posCtl = \App\Http\Controllers\Seller\SellerPosController::class;
    // สถานะ API Key → โทนสี
    $keyTone = function ($k) {
        if (! $k->is_active) {
            return 'muted';
        }
        if ($k->is_blocked) {
            return 'bad';
        }
        if ($k->expires_at && $k->expires_at->isPast()) {
            return 'warn';
        }

        return 'ok';
    };
    $mask = fn ($key) => $key ? substr($key, 0, 6).'••••••'.substr($key, -4) : '—';
    $th = 'padding:11px 14px; text-align:left; font-size:10.5px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;';
    $td = 'padding:12px 14px; font-size:13px; color:var(--ink);';
    $row = 'border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);';
    $newKey = session('new_api_key');
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="เครื่อง POS (Desktop / แท็บเล็ต)" icon="🖥️" crumb="ร้านค้า · POS"
                         subtitle="สร้าง API Key เพื่อเชื่อมโปรแกรม POS บนเครื่องหน้าร้านเข้ากับร้านของคุณ" />

    @include('seller.pos.partials.nav')

    <x-seller-kit.errors />

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:14px;">
        <x-seller-kit.stat label="API Key ทั้งหมด" :value="number_format($stats['total_api_keys'])" icon="🔑" tone="info" />
        <x-seller-kit.stat label="Key ใช้งานได้" :value="number_format($stats['active_api_keys'])" icon="✅" tone="ok" />
        <x-seller-kit.stat label="เครื่องที่ลงทะเบียน" :value="number_format($stats['total_terminals'])" icon="🖥️" tone="violet" />
        <x-seller-kit.stat label="เครื่องที่เปิดใช้" :value="number_format($stats['active_terminals'])" icon="⚡" tone="gold" />
        <x-seller-kit.stat label="ออนไลน์ตอนนี้" :value="number_format($stats['online_terminals'])" icon="📡" tone="ok" hint="เห็นเครื่องภายใน 10 นาที" />
    </div>

    {{-- API Key ใหม่ (แสดงครั้งเดียวหลังสร้าง) --}}
    @if($newKey)
        <div class="tp-card" x-data="{ copied: false }" style="border-left:4px solid var(--tp-ok, #5aa07e);">
            <div style="display:flex; gap:12px; align-items:flex-start; flex-wrap:wrap;">
                <span class="tp-tile" style="width:44px; height:44px; font-size:20px;" aria-hidden="true">🔑</span>
                <div style="flex:1; min-width:220px;">
                    <div class="tp-section-h">สร้าง API Key ใหม่สำเร็จ</div>
                    <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">คัดลอกไปวางในโปรแกรม POS ตอนนี้เลย — เพื่อความปลอดภัย Key เต็มจะแสดงเฉพาะครั้งนี้</div>
                    <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:10px; align-items:center;">
                        <code class="tp-inset-sm tp-num" style="padding:10px 14px; border-radius:12px; font-size:13px; overflow-wrap:anywhere; user-select:all;">{{ $newKey }}</code>
                        <button type="button" class="tp-btn tp-btn-primary tp-btn-sm"
                                @click="navigator.clipboard.writeText(@js($newKey)).then(() => { copied = true; window.showNotification('คัดลอก API Key แล้ว', 'success'); })">
                            <span x-text="copied ? '✓ คัดลอกแล้ว' : '📋 คัดลอก'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(300px,1fr)); gap:16px;">
        {{-- สร้าง API Key --}}
        <form method="POST" action="{{ route('seller.pos.api-keys.store') }}" class="tp-card" style="display:flex; flex-direction:column; gap:12px;"
              x-data="{ busy: false }" @submit="busy = true">
            @csrf
            <div class="tp-section-h">➕ สร้าง API Key ใหม่</div>
            <div>
                <label for="k-name" style="font-size:12.5px; font-weight:700;">ชื่อเรียก (ไม่บังคับ)</label>
                <input id="k-name" type="text" name="name" maxlength="255" value="{{ old('name') }}" placeholder="เช่น เครื่องหน้าร้าน, แคชเชียร์ 1" class="tp-input" style="margin-top:6px;">
            </div>
            <div>
                <label for="k-desc" style="font-size:12.5px; font-weight:700;">รายละเอียด (ไม่บังคับ)</label>
                <input id="k-desc" type="text" name="description" maxlength="500" value="{{ old('description') }}" class="tp-input" style="margin-top:6px;">
            </div>
            <button type="submit" class="tp-btn tp-btn-primary" :disabled="busy" :style="{ opacity: busy ? .6 : 1 }">🔑 สร้าง API Key</button>
        </form>

        {{-- วิธีเชื่อมเครื่อง --}}
        <div class="tp-card" style="background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 14%, transparent), transparent 70%);">
            <div class="tp-section-h">📖 วิธีเชื่อมโปรแกรม POS</div>
            <ol style="margin:10px 0 0; padding-left:20px; font-size:13px; line-height:1.9;">
                <li>กด “สร้าง API Key” แล้วคัดลอก Key ที่ได้</li>
                <li>เปิดโปรแกรม POS แล้ววาง Key ในช่อง API Key</li>
                <li>รหัสร้าน: <code class="tp-inset-sm tp-num" style="padding:2px 8px; border-radius:8px;">{{ $store->id }}</code></li>
                <li>Server URL: <code class="tp-inset-sm" style="padding:2px 8px; border-radius:8px; overflow-wrap:anywhere;">{{ config('app.url') }}</code></li>
                <li>กด Activate — เครื่องจะขึ้นในตารางด้านล่าง</li>
            </ol>
        </div>
    </div>

    {{-- ตาราง API Keys --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div class="tp-section-h" style="padding:16px 18px;">🔑 API Keys ของร้าน</div>
        @if($apiKeys->count() > 0)
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; min-width:760px;">
                    <thead>
                        <tr style="background:color-mix(in srgb, var(--ink2) 8%, transparent);">
                            <th style="{{ $th }}">ชื่อ</th>
                            <th style="{{ $th }}">API Key</th>
                            <th style="{{ $th }} text-align:center;">สถานะ</th>
                            <th style="{{ $th }} text-align:center;">เครื่อง</th>
                            <th style="{{ $th }}">ใช้ล่าสุด</th>
                            <th style="{{ $th }} text-align:right;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($apiKeys as $apiKey)
                            <tr style="{{ $row }}">
                                <td style="{{ $td }}">
                                    <a href="{{ route('seller.pos.api-keys.show', $apiKey) }}" style="font-weight:700; color:var(--ink); text-decoration:none;">{{ $apiKey->name ?: 'ไม่ระบุชื่อ' }}</a>
                                    @if($apiKey->description)<div style="font-size:11px; color:var(--ink2);">{{ \Illuminate\Support\Str::limit($apiKey->description, 50) }}</div>@endif
                                </td>
                                <td style="{{ $td }}"><code class="tp-num" style="font-size:12px;">{{ $mask($apiKey->key) }}</code></td>
                                <td style="{{ $td }} text-align:center;"><x-seller-kit.pill :tone="$keyTone($apiKey)">{{ $apiKey->getStatusText() }}</x-seller-kit.pill></td>
                                <td style="{{ $td }} text-align:center;" class="tp-num">{{ $apiKey->terminals->count() }}</td>
                                <td style="{{ $td }} color:var(--ink2);">{{ $apiKey->last_used_at?->diffForHumans() ?? 'ยังไม่เคยใช้' }}</td>
                                <td style="{{ $td }} text-align:right; white-space:nowrap;">
                                    <a href="{{ route('seller.pos.api-keys.show', $apiKey) }}" class="tp-btn tp-btn-sm">ดู</a>
                                    <form method="POST" action="{{ route('seller.pos.api-keys.toggle-block', $apiKey) }}" style="display:inline;"
                                          onsubmit="return confirm('{{ $apiKey->is_blocked ? 'ปลดบล็อก API Key นี้?' : 'บล็อก API Key นี้? เครื่องที่ใช้ Key นี้จะซิงก์ไม่ได้ทันที' }}');">
                                        @csrf
                                        <button type="submit" class="tp-btn tp-btn-sm" style="color:{{ $apiKey->is_blocked ? 'var(--tp-ok, #4f9a74)' : 'var(--tp-warn, #c98a1b)' }};">{{ $apiKey->is_blocked ? '✅ ปลดบล็อก' : '⛔ บล็อก' }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('seller.pos.api-keys.destroy', $apiKey) }}" style="display:inline;"
                                          onsubmit="return confirm('ลบ API Key นี้? เครื่อง POS ที่ผูกกับ Key นี้จะถูกลบด้วย');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="tp-btn tp-btn-sm" style="color:var(--tp-bad, #d9534f);">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-seller-kit.empty icon="🔑" title="ยังไม่มี API Key" text="สร้าง Key แรกด้านบนเพื่อเชื่อมโปรแกรม POS" />
        @endif
    </div>

    {{-- ตารางเครื่อง POS --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div class="tp-section-h" style="padding:16px 18px;">🖥️ เครื่อง POS ที่ลงทะเบียนแล้ว</div>
        @if($terminals->count() > 0)
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; min-width:760px;">
                    <thead>
                        <tr style="background:color-mix(in srgb, var(--ink2) 8%, transparent);">
                            <th style="{{ $th }}">เครื่อง</th>
                            <th style="{{ $th }}">Product Key</th>
                            <th style="{{ $th }} text-align:center;">สถานะ</th>
                            <th style="{{ $th }} text-align:center;">ออนไลน์</th>
                            <th style="{{ $th }}">ซิงก์ล่าสุด</th>
                            <th style="{{ $th }} text-align:right;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($terminals as $terminal)
                            @php
                                $tActive = $posCtl::terminalIsActive($terminal);
                                $tOnline = $posCtl::terminalIsOnline($terminal);
                            @endphp
                            <tr style="{{ $row }}">
                                <td style="{{ $td }}">
                                    <a href="{{ route('seller.pos.terminals.show', $terminal) }}" style="font-weight:700; color:var(--ink); text-decoration:none;">{{ $terminal->terminal_name ?? $terminal->device_name ?? 'POS Terminal' }}</a>
                                    <div style="font-size:11px; color:var(--ink2);">{{ trim(($terminal->device_model ?? '').' · '.($terminal->platform ?? ''), ' ·') ?: '—' }}</div>
                                </td>
                                <td style="{{ $td }}"><code class="tp-num" style="font-size:12px;">{{ $terminal->product_key }}</code></td>
                                <td style="{{ $td }} text-align:center;"><x-seller-kit.pill :tone="$tActive ? 'ok' : 'muted'">{{ $tActive ? 'เปิดใช้' : 'ปิดอยู่' }}</x-seller-kit.pill></td>
                                <td style="{{ $td }} text-align:center;"><x-seller-kit.pill :tone="$tOnline ? 'ok' : 'muted'">{{ $tOnline ? '● ออนไลน์' : '○ ออฟไลน์' }}</x-seller-kit.pill></td>
                                <td style="{{ $td }} color:var(--ink2);">{{ $terminal->last_sync_at?->diffForHumans() ?? 'ยังไม่เคย' }}</td>
                                <td style="{{ $td }} text-align:right; white-space:nowrap;">
                                    <a href="{{ route('seller.pos.terminals.show', $terminal) }}" class="tp-btn tp-btn-sm">ดู</a>
                                    <form method="POST" action="{{ route('seller.pos.terminals.toggle-status', $terminal) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="tp-btn tp-btn-sm">{{ $tActive ? '⏸️ ปิด' : '▶️ เปิด' }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('seller.pos.terminals.destroy', $terminal) }}" style="display:inline;"
                                          onsubmit="return confirm('ลบเครื่อง POS นี้ออกจากร้าน?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="tp-btn tp-btn-sm" style="color:var(--tp-bad, #d9534f);">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-seller-kit.empty icon="🖥️" title="ยังไม่มีเครื่อง POS ลงทะเบียน" text="เมื่อกรอก API Key ในโปรแกรม POS แล้ว เครื่องจะขึ้นที่นี่โดยอัตโนมัติ" />
        @endif
    </div>
</div>
@endsection
