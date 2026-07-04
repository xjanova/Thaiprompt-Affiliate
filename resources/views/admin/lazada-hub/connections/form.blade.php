@extends('layouts.admin-v4')

@section('title', $pageTitle ?? 'การเชื่อมต่อ Lazada')

@section('content')
{{-- ════════════════════════════════════════════════════════════
     หน้า: Lazada Hub — ฟอร์มเพิ่ม/แก้ไขการเชื่อมต่อ
     credential ถูกเข้ารหัสฝั่งเซิร์ฟเวอร์ (ไม่ echo กลับมาตอนแก้ไข)
     ════════════════════════════════════════════════════════════ --}}
@php $isEdit = (bool) $account; @endphp
<div x-data="{ programType: '{{ old('program_type', $account?->program_type ?? 'seller') }}' }"
     style="display:flex;flex-direction:column;gap:18px;max-width:760px;">

    {{-- ── Header ── --}}
    <div>
        <div class="tp-muted" style="font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;margin-bottom:4px;">
            <a href="{{ route('admin.lazada-hub.connections.index') }}" style="color:var(--accent2);text-decoration:none;">การเชื่อมต่อ</a> · {{ $isEdit ? 'แก้ไข' : 'เพิ่มใหม่' }}
        </div>
        <h1 style="font-size:1.5rem;font-weight:800;color:var(--ink);margin:0;display:flex;align-items:center;gap:10px;">
            <i class="fas fa-plug" style="color:var(--accent1);"></i>
            {{ $pageTitle }}
        </h1>
    </div>

    @if($errors->any())
        <div class="tp-card" style="border-left:4px solid #d9534f;color:#d9534f;font-size:.84rem;">
            <ul style="margin:0;padding-left:18px;">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ $isEdit ? route('admin.lazada-hub.connections.update', $account) : route('admin.lazada-hub.connections.store') }}"
          class="tp-card" style="display:flex;flex-direction:column;gap:16px;">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- ชนิดโปรแกรม --}}
        <div>
            <label style="display:block;font-weight:600;color:var(--ink);font-size:.85rem;margin-bottom:8px;">ชนิดโปรแกรม</label>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <label class="tp-inset" :style="programType==='seller' ? 'box-shadow:inset 0 0 0 2px var(--accent1);' : ''"
                       style="flex:1;min-width:220px;padding:12px 14px;border-radius:12px;cursor:pointer;display:flex;gap:10px;align-items:flex-start;">
                    <input type="radio" name="program_type" value="seller" x-model="programType" style="margin-top:3px;accent-color:var(--accent1);">
                    <span><b style="color:var(--ink);">🔵 Seller (Open Platform)</b><br><span class="tp-muted" style="font-size:.76rem;">ดึงสินค้า/ราคา/สต็อก/ออเดอร์ — ขายเองบวกกำไร</span></span>
                </label>
                <label class="tp-inset" :style="programType==='affiliate_native' ? 'box-shadow:inset 0 0 0 2px var(--accent1);' : ''"
                       style="flex:1;min-width:220px;padding:12px 14px;border-radius:12px;cursor:pointer;display:flex;gap:10px;align-items:flex-start;">
                    <input type="radio" name="program_type" value="affiliate_native" x-model="programType" style="margin-top:3px;accent-color:var(--accent1);">
                    <span><b style="color:var(--ink);">🟢 Lazada Affiliate (Open API)</b><br><span class="tp-muted" style="font-size:.76rem;">API ของ Lazada เอง — ได้ค่าคอมตรง ไม่ผ่านคนกลาง</span></span>
                </label>
                <label class="tp-inset" :style="programType==='affiliate' ? 'box-shadow:inset 0 0 0 2px var(--accent1);' : ''"
                       style="flex:1;min-width:220px;padding:12px 14px;border-radius:12px;cursor:pointer;display:flex;gap:10px;align-items:flex-start;">
                    <input type="radio" name="program_type" value="affiliate" x-model="programType" style="margin-top:3px;accent-color:var(--accent1);">
                    <span><b style="color:var(--ink);">🟣 Affiliate (Involve Asia)</b><br><span class="tp-muted" style="font-size:.76rem;">ผ่านคนกลาง Involve Asia ~6.5%</span></span>
                </label>
            </div>
        </div>

        {{-- ชื่อเรียก --}}
        <div>
            <label style="display:block;font-weight:600;color:var(--ink);font-size:.85rem;margin-bottom:6px;">ชื่อเรียกบัญชี <span style="color:#d9534f;">*</span></label>
            <input type="text" name="account_name" value="{{ old('account_name', $account?->account_name ?? '') }}"
                   class="tp-input" placeholder="เช่น ร้านหลัก Lazada / Affiliate Involve" required>
        </div>

        {{-- คู่คีย์หลัก --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div>
                <label style="display:block;font-weight:600;color:var(--ink);font-size:.85rem;margin-bottom:6px;">
                    <span x-show="programType==='seller'">App Key</span>
                    <span x-show="programType==='affiliate_native'" x-cloak style="display:none;">App Key (Lazada Affiliate)</span>
                    <span x-show="programType==='affiliate'" x-cloak style="display:none;">Involve Asia API Key</span>
                    @if(!$isEdit)<span style="color:#d9534f;">*</span>@endif
                </label>
                <input type="text" name="app_key" class="tp-input tp-num" autocomplete="off"
                       placeholder="{{ $isEdit ? 'เว้นว่าง = คงค่าเดิม' : 'วางคีย์ที่นี่' }}">
            </div>
            <div>
                <label style="display:block;font-weight:600;color:var(--ink);font-size:.85rem;margin-bottom:6px;">
                    <span x-show="programType==='seller'">App Secret</span>
                    <span x-show="programType==='affiliate_native'" x-cloak style="display:none;">App Secret</span>
                    <span x-show="programType==='affiliate'" x-cloak style="display:none;">Involve Asia Secret</span>
                    @if(!$isEdit)<span style="color:#d9534f;">*</span>@endif
                </label>
                <input type="password" name="app_secret" class="tp-input tp-num" autocomplete="new-password"
                       placeholder="{{ $isEdit ? 'เว้นว่าง = คงค่าเดิม' : 'วาง secret ที่นี่' }}">
            </div>
        </div>

        {{-- Access Token — Seller (จาก OAuth) หรือ Lazada Affiliate native (ไม่บังคับ เผื่อ endpoint ต้องการ) --}}
        {{-- หมายเหตุ: input เดียว name=access_token ใช้ร่วมกัน กัน field ชื่อซ้ำ (ค่าซ้อนทับกันตอน submit) --}}
        <div x-show="programType==='seller' || programType==='affiliate_native'" x-cloak style="display:none;max-width:100%;">
            <label style="display:block;font-weight:600;color:var(--ink);font-size:.85rem;margin-bottom:6px;">
                Access Token <span x-show="programType==='affiliate_native'">(ไม่บังคับ — ทดสอบคีย์ไม่ต้องใช้)</span>
            </label>
            <input type="text" name="access_token" class="tp-input tp-num" autocomplete="off"
                   placeholder="{{ $isEdit ? 'เว้นว่าง = คงค่าเดิม' : 'Seller: จาก OAuth /auth/token/create' }}">
        </div>

        {{-- Seller เท่านั้น: refresh token + shop --}}
        <div x-show="programType==='seller'" style="display:flex;flex-direction:column;gap:14px;">
            <div style="max-width:100%;">
                <label style="display:block;font-weight:600;color:var(--ink);font-size:.85rem;margin-bottom:6px;">Refresh Token</label>
                <input type="text" name="refresh_token" class="tp-input tp-num" autocomplete="off"
                       placeholder="{{ $isEdit ? 'เว้นว่าง = คงค่าเดิม' : 'ไม่บังคับ' }}">
            </div>
            <div style="max-width:280px;">
                <label style="display:block;font-weight:600;color:var(--ink);font-size:.85rem;margin-bottom:6px;">Shop ID (ไม่บังคับ)</label>
                <input type="text" name="shop_id" value="{{ old('shop_id', $account?->shop_id ?? '') }}" class="tp-input tp-num">
            </div>
        </div>

        {{-- Affiliate (ทั้ง Involve + Lazada native): sub id / tracking --}}
        <div x-show="programType==='affiliate' || programType==='affiliate_native'" x-cloak style="display:none;max-width:320px;">
            <label style="display:block;font-weight:600;color:var(--ink);font-size:.85rem;margin-bottom:6px;">Sub ID / Tracking ID (ไม่บังคับ)</label>
            <input type="text" name="sub_id" value="{{ old('sub_id', $account?->cred('sub_id') ?? '') }}" class="tp-input tp-num"
                   placeholder="ใช้แยกแหล่งที่มาของยอด">
        </div>

        {{-- Lazada native affiliate: User Token — จำเป็นสำหรับ Open API "Get product feed" (ดึงสินค้าเอง 24 ชม.) --}}
        <div x-show="programType==='affiliate_native'" x-cloak style="display:none;max-width:100%;">
            <label style="display:block;font-weight:600;color:var(--ink);font-size:.85rem;margin-bottom:6px;">
                User Token
                <span class="tp-muted" style="font-weight:400;">— กด "Acquire User Token" ในพอร์ทัล Lazada Affiliate → Open API แล้ววางที่นี่</span>
            </label>
            <input type="text" name="user_token" class="tp-input tp-num" autocomplete="off"
                   placeholder="{{ $isEdit && $account?->cred('user_token') ? 'มีค่าเดิมอยู่ — เว้นว่าง = คงเดิม' : 'วาง User Token ที่นี่' }}">
        </div>


        {{-- ตั้งค่า sync + คอม --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;align-items:end;">
            <div>
                <label style="display:block;font-weight:600;color:var(--ink);font-size:.85rem;margin-bottom:6px;">ค่าคอมเริ่มต้น (%)</label>
                <input type="number" name="commission_rate" step="0.01" min="0" max="100"
                       value="{{ old('commission_rate', $account?->commission_rate ?? '') }}" class="tp-input tp-num" placeholder="เช่น 5">
            </div>
            <div>
                <label style="display:block;font-weight:600;color:var(--ink);font-size:.85rem;margin-bottom:6px;">ความถี่ sync</label>
                @php $freq = old('sync_frequency', $account?->sync_frequency ?? 'daily'); @endphp
                <select name="sync_frequency" class="tp-input">
                    <option value="hourly" @selected($freq==='hourly')>ทุกชั่วโมง</option>
                    <option value="daily" @selected($freq==='daily')>ทุกวัน</option>
                    <option value="weekly" @selected($freq==='weekly')>ทุกสัปดาห์</option>
                </select>
            </div>
        </div>

        <div style="display:flex;flex-wrap:wrap;gap:18px;">
            <label style="display:flex;align-items:center;gap:8px;font-size:.85rem;color:var(--ink);cursor:pointer;">
                <input type="checkbox" name="auto_sync_products" value="1" style="width:18px;height:18px;accent-color:var(--accent1);"
                       @checked(old('auto_sync_products', $account?->auto_sync_products ?? false))>
                auto-sync สินค้า/ราคา
            </label>
            <label style="display:flex;align-items:center;gap:8px;font-size:.85rem;color:var(--ink);cursor:pointer;">
                <input type="checkbox" name="auto_sync_orders" value="1" style="width:18px;height:18px;accent-color:var(--accent1);"
                       @checked(old('auto_sync_orders', $account?->auto_sync_orders ?? false))>
                auto-sync ออเดอร์
            </label>
        </div>

        {{-- ปุ่ม --}}
        <div style="display:flex;gap:10px;justify-content:flex-end;padding-top:6px;">
            <a href="{{ route('admin.lazada-hub.connections.index') }}" class="tp-btn">ยกเลิก</a>
            <button type="submit" class="tp-btn tp-btn-primary">
                <i class="fas fa-floppy-disk"></i> <span>{{ $isEdit ? 'บันทึกการแก้ไข' : 'บันทึก' }}</span>
            </button>
        </div>
    </form>

    <p class="tp-muted" style="font-size:.76rem;margin:0;">
        <i class="fas fa-shield-halved" style="color:#5aa07e;"></i> คีย์ทั้งหมดถูกเข้ารหัสก่อนเก็บลงฐานข้อมูล และจะไม่แสดงกลับมาอีก
    </p>
</div>
@endsection
