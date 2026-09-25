{{--
 | ไรเดอร์รอตรวจสอบ (admin.riders.pending) — ธีม V4
 | ตัวแปรจาก Admin\RiderController@pending:
 |   $riders (paginator, เก่าสุดก่อน), $documentsByRider[rider_id]{documents[{type,label,uploaded,required,url}], missing[], complete}, $pendingCount, $pageTitle
 | เอกสารเปิดผ่าน admin.riders.document เท่านั้น (private disk) · อนุมัติ/ปฏิเสธ = ฟอร์ม POST ผ่านโมดัลยืนยัน
--}}
@extends('layouts.admin-v4')

@section('title', $pageTitle ?? 'ไรเดอร์รอตรวจสอบ')

@section('content')
@include('admin.riders.partials.v4-kit')
@include('admin.riders.partials.doc-viewer')
<div x-data="{}" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== หัวเรื่อง ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div style="display:flex; align-items:center; gap:14px;">
            <a href="{{ route('admin.riders.index') }}" class="tp-icon-btn" title="กลับหน้ารายชื่อไรเดอร์"><i class="fas fa-arrow-left"></i></a>
            <div>
                <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ไรเดอร์ · รออนุมัติ</div>
                <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    ใบสมัครรออนุมัติ
                    @if ($pendingCount > 0)
                        @include('admin.riders.partials.pill', ['pillTone' => 'warn', 'pillText' => number_format($pendingCount).' รายการ', 'pillIcon' => 'fa-clock', 'pillTitle' => null])
                    @endif
                </h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">ตรวจรูปบัตร ใบขับขี่ และทะเบียนรถ ก่อนอนุมัติ — เรียงจากสมัครก่อนไปหลัง</div>
            </div>
        </div>
        <form method="GET" action="{{ route('admin.riders.pending') }}" style="display:flex; gap:8px; flex-wrap:wrap; width:100%; max-width:420px;">
            <input type="search" name="search" value="{{ request('search') }}" class="tp-input" style="flex:1; min-width:180px;" placeholder="ค้นหาชื่อ หรือเบอร์โทร">
            <button type="submit" class="tp-btn tp-btn-primary"><i class="fas fa-magnifying-glass"></i> ค้นหา</button>
        </form>
    </div>

    @include('admin.riders.partials.flash')

    @if ($riders->count() > 0)
        <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(min(100%,330px),1fr)); gap:16px;">
            @foreach ($riders as $rider)
                @php
                    $docInfo = $documentsByRider[$rider->id] ?? ['documents' => [], 'missing' => [], 'complete' => false];
                    $viewerItems = collect($docInfo['documents'])
                        ->filter(fn ($d) => ! empty($d['url']))
                        ->map(fn ($d) => ['label' => $d['label'], 'url' => $d['url']])
                        ->values()
                        ->all();
                    $waitingDays = $rider->created_at ? (int) $rider->created_at->diffInDays(now()) : 0;
                    $profileIndex = collect($viewerItems)->search(fn ($d) => str_contains($d['url'], '/document/profile'));
                    $profileIndex = $profileIndex === false ? 0 : (int) $profileIndex;
                    $approveStyle = 'flex:1; color:var(--w-on); background:linear-gradient(135deg, var(--w-ok), color-mix(in srgb, var(--w-ok) 72%, var(--ink)));'
                        .($docInfo['complete'] ? '' : ' opacity:.55; cursor:not-allowed;');
                @endphp
                <div class="tp-card" style="padding:18px; display:flex; flex-direction:column; gap:14px;">
                    {{-- ข้อมูลผู้สมัคร --}}
                    <div style="display:flex; align-items:center; gap:12px;">
                        @if ($rider->profile_image)
                            <button type="button" style="border:0; padding:0; background:none; cursor:zoom-in; flex:none;"
                                    @click="$dispatch('w1-doc', @js(['items' => $viewerItems, 'index' => $profileIndex]))">
                                <img src="{{ route('admin.riders.document', [$rider, 'profile']) }}" alt="" loading="lazy"
                                     style="width:54px; height:54px; border-radius:50%; object-fit:cover; box-shadow:var(--raise);">
                            </button>
                        @else
                            <span class="tp-tile" style="width:54px; height:54px; border-radius:50%; font-size:20px; font-weight:800;">{{ mb_substr($rider->full_name ?: 'R', 0, 1) }}</span>
                        @endif
                        <div style="min-width:0; flex:1;">
                            <div style="font-weight:700; font-size:15px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $rider->full_name }}</div>
                            <div style="font-size:12.5px; color:var(--ink2);"><a href="tel:{{ $rider->phone }}" class="w1-link">{{ $rider->phone }}</a> · #{{ $rider->id }}</div>
                        </div>
                        @if ($waitingDays >= 2)
                            @include('admin.riders.partials.pill', ['pillTone' => 'bad', 'pillText' => 'รอ '.$waitingDays.' วัน', 'pillIcon' => 'fa-hourglass-end', 'pillTitle' => 'รอตรวจนานแล้ว'])
                        @endif
                    </div>

                    <div class="tp-well" style="padding:12px 14px; display:grid; grid-template-columns:auto 1fr; gap:6px 12px; font-size:13px;">
                        <span style="color:var(--ink2);">เลขบัตร</span>
                        <span class="tp-num">{{ app(\App\Services\RiderAccountService::class)->maskIdCard($rider->id_card_number) ?? '-' }}</span>
                        <span style="color:var(--ink2);">ยานพาหนะ</span>
                        <span>{{ $rider->vehicle_type_text }} @if ($rider->vehicle_plate)<span class="tp-num" style="color:var(--ink2);">· {{ $rider->vehicle_plate }}</span>@endif</span>
                        <span style="color:var(--ink2);">พื้นที่</span>
                        <span>{{ collect([$rider->district, $rider->province])->filter()->implode(', ') ?: '-' }}</span>
                        <span style="color:var(--ink2);">สมัครเมื่อ</span>
                        <span>{{ $rider->created_at?->thaidate('j M Y H:i') }}</span>
                    </div>

                    {{-- เอกสาร (ดูในหน้าได้ทันที) --}}
                    <div>
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:8px;">
                            <span style="font-size:12.5px; font-weight:700;"><i class="fas fa-folder-open" style="color:var(--ink2);"></i> เอกสาร</span>
                            @if ($docInfo['complete'])
                                @include('admin.riders.partials.pill', ['pillTone' => 'ok', 'pillText' => 'ครบตามที่ต้องใช้', 'pillIcon' => 'fa-circle-check', 'pillTitle' => null])
                            @else
                                @include('admin.riders.partials.pill', ['pillTone' => 'bad', 'pillText' => 'ยังขาด '.count($docInfo['missing']).' รายการ', 'pillIcon' => 'fa-triangle-exclamation', 'pillTitle' => null])
                            @endif
                        </div>
                        <div style="display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:8px;">
                            @foreach ($docInfo['documents'] as $doc)
                                @if ($doc['url'])
                                    @php $docIndex = (int) collect($viewerItems)->search(fn ($d) => $d['url'] === $doc['url']); @endphp
                                    <button type="button" title="{{ $doc['label'] }}"
                                            style="border:0; padding:0; cursor:zoom-in; border-radius:11px; overflow:hidden; box-shadow:var(--inset-sm); background:var(--bg); aspect-ratio:1; position:relative;"
                                            @click="$dispatch('w1-doc', @js(['items' => $viewerItems, 'index' => $docIndex]))">
                                        <img src="{{ $doc['url'] }}" alt="{{ $doc['label'] }}" loading="lazy" style="width:100%; height:100%; object-fit:cover; display:block;">
                                        <span style="position:absolute; left:0; right:0; bottom:0; font-size:10px; padding:3px 4px; background:linear-gradient(0deg, rgba(0,0,0,.65), transparent); color:var(--w-on); text-align:center;">{{ $doc['label'] }}</span>
                                    </button>
                                @else
                                    <div title="{{ $doc['label'] }}"
                                         style="border-radius:11px; aspect-ratio:1; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:4px; text-align:center; padding:4px; box-shadow:var(--inset-sm); font-size:10px; color:{{ $doc['required'] ? 'var(--w-bad)' : 'var(--ink2)' }};">
                                        <i class="fas {{ $doc['required'] ? 'fa-circle-exclamation' : 'fa-minus' }}" style="font-size:15px;"></i>
                                        <span>{{ $doc['label'] }}{{ $doc['required'] ? ' (บังคับ)' : '' }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    {{-- ปุ่มจัดการ --}}
                    <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:auto;">
                        <a href="{{ route('admin.riders.show', $rider) }}" class="tp-btn tp-btn-sm" style="flex:1;"><i class="fas fa-eye"></i> รายละเอียด</a>
                        <button type="button" class="tp-btn tp-btn-sm" style="{{ $approveStyle }}"
                                @if (! $docInfo['complete']) disabled title="ยังขาดเอกสารที่บังคับ" @endif
                                @click="$dispatch('w1-action', @js(['url' => route('admin.riders.approve', $rider), 'title' => 'อนุมัติ '.$rider->full_name, 'message' => 'ตรวจเอกสารครบแล้ว ไรเดอร์จะเริ่มรับงานได้หลังเปิดรับงานในแอป', 'reason' => 'none', 'confirm' => 'อนุมัติ', 'tone' => 'ok', 'icon' => 'fa-circle-check']))">
                            <i class="fas fa-check"></i> อนุมัติ
                        </button>
                        <button type="button" class="tp-btn tp-btn-sm" style="color:var(--w-bad);"
                                @click="$dispatch('w1-action', @js(['url' => route('admin.riders.reject', $rider), 'title' => 'ปฏิเสธใบสมัคร '.$rider->full_name, 'message' => 'ไรเดอร์จะเห็นเหตุผลในแอป และแก้ไขแล้วส่งใหม่ได้', 'reason' => 'required', 'reasonLabel' => 'เหตุผลที่ไม่อนุมัติ', 'placeholder' => 'เช่น รูปใบขับขี่ไม่ชัด กรุณาถ่ายใหม่ให้เห็นเลขครบ', 'confirm' => 'ปฏิเสธ', 'tone' => 'bad', 'icon' => 'fa-circle-xmark']))">
                            <i class="fas fa-xmark"></i> ปฏิเสธ
                        </button>
                    </div>
                    @if (! $docInfo['complete'])
                        <div style="font-size:11.5px; color:var(--w-bad);">
                            <i class="fas fa-circle-info"></i> ยังขาด: {{ app(\App\Services\RiderAccountService::class)->documentLabels($docInfo['missing']) }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        @if ($riders->hasPages())
            <div>{{ $riders->links() }}</div>
        @endif
    @else
        <div class="tp-card" style="padding:44px 20px; text-align:center;">
            <span class="tp-tile" style="width:64px; height:64px; border-radius:50%; font-size:26px; margin:0 auto 14px; background:linear-gradient(135deg, var(--w-ok), color-mix(in srgb, var(--w-ok) 70%, var(--ink)));"><i class="fas fa-circle-check"></i></span>
            <div class="tp-section-h" style="font-size:17px;">{{ request('search') ? 'ไม่พบใบสมัครตามคำค้น' : 'ไม่มีใบสมัครรอตรวจ' }}</div>
            <p style="color:var(--ink2); font-size:13px; margin:6px 0 16px;">ใบสมัครทั้งหมดได้รับการตรวจเรียบร้อยแล้ว</p>
            <a href="{{ route('admin.riders.index') }}" class="tp-btn"><i class="fas fa-arrow-left"></i> กลับรายชื่อไรเดอร์</a>
        </div>
    @endif
</div>
@endsection
