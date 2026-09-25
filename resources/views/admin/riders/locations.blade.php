{{--
 | ตำแหน่ง GPS ของไรเดอร์ (admin.riders.locations) — ธีม V4 + OpenStreetMap/Leaflet
 | ตัวแปรจาก Admin\RiderController@locations: $rider, $latestLocation (RiderLocation|null), $locationHistory (24 ชม. ล่าสุด 100 จุด ใหม่→เก่า),
 |   $lastLocation (พิกัดล่าสุดจากตาราง riders + is_stale), $pageTitle
 | ปุ่ม "ตำแหน่งตอนนี้" เรียก admin.riders.latest-location (JSON)
--}}
@extends('layouts.admin-v4')

@section('title', $pageTitle ?? 'ตำแหน่ง GPS')

@section('content')
@include('admin.riders.partials.v4-kit')
@include('admin.riders.partials.leaflet')
@include('admin.riders.partials.track-js')
@php
    $trackPoints = $locationHistory->sortBy(fn ($log) => ($log->recorded_at ?? $log->created_at)?->getTimestamp() ?? 0)
        ->map(fn ($log) => [
            'lat' => (float) $log->latitude,
            'lng' => (float) $log->longitude,
            't' => ($log->recorded_at ?? $log->created_at)?->toIso8601String(),
            'speed' => $log->speed !== null ? (float) $log->speed : null,
            'heading' => $log->heading !== null ? (float) $log->heading : null,
            'accuracy' => $log->accuracy !== null ? (float) $log->accuracy : null,
            'battery' => $log->battery_level !== null ? (int) $log->battery_level : null,
            'job_id' => $log->job_id !== null ? (int) $log->job_id : null,
        ])->values()->all();
    $latestAt = $latestLocation ? ($latestLocation->recorded_at ?? $latestLocation->created_at) : null;
@endphp
<div x-data="w1Track(@js($trackPoints), { latestUrl: @js(route('admin.riders.latest-location', $rider)) })" x-init="boot()" style="display:flex; flex-direction:column; gap:16px;">

    {{-- ===== หัวเรื่อง ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div style="display:flex; align-items:center; gap:14px;">
            <a href="{{ route('admin.riders.show', $rider) }}" class="tp-icon-btn" title="กลับหน้าไรเดอร์"><i class="fas fa-arrow-left"></i></a>
            <div>
                <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ไรเดอร์ · ตำแหน่ง GPS</div>
                <h1 class="tp-num" style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:4px 0 0; display:flex; flex-wrap:wrap; gap:9px; align-items:center;">
                    {{ $rider->full_name }}
                    @include('admin.riders.partials.status', ['statusKind' => 'availability', 'statusValue' => $rider->availability, 'statusLabel' => null])
                    @if ($lastLocation)
                        @include('admin.riders.partials.pill', ['pillTone' => $lastLocation['is_stale'] ? 'warn' : 'ok', 'pillText' => $lastLocation['is_stale'] ? 'สัญญาณเงียบ' : 'สัญญาณสด', 'pillIcon' => 'fa-satellite-dish', 'pillTitle' => null])
                    @endif
                </h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">เส้นทาง 24 ชั่วโมงล่าสุด (บันทึกเฉพาะช่วงที่มีงาน) + ตำแหน่งปัจจุบัน</div>
            </div>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:9px;">
            <button type="button" class="tp-btn tp-btn-sm tp-btn-primary" @click="refreshLatest()" :disabled="loading">
                <i class="fas fa-location-crosshairs" :class="loading ? 'fa-spin' : ''"></i> ตำแหน่งตอนนี้
            </button>
            <a href="{{ route('admin.riders.playback', $rider) }}" class="tp-btn tp-btn-sm"><i class="fas fa-circle-play"></i> เล่นย้อนหลังแบบละเอียด</a>
            <a href="{{ route('admin.riders.map') }}" class="tp-btn tp-btn-sm"><i class="fas fa-map"></i> แผนที่รวม</a>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,280px),1fr)); gap:16px; align-items:start;">

        {{-- ===== แผนที่ + ตัวเล่น ===== --}}
        <div class="tp-card" style="padding:0; overflow:hidden; grid-column:1 / -1;">
            <div x-ref="map" style="width:100%; height:min(60vh, 520px); min-height:320px;"></div>
            @if (count($trackPoints) > 1)
                <div style="padding:14px 16px; display:flex; flex-direction:column; gap:10px;">
                    <input type="range" class="tp-range" min="0" :max="points.length - 1" :value="index" @input="seek($event.target.value)">
                    <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px;">
                        <div style="display:flex; gap:6px; align-items:center;">
                            <button type="button" class="tp-icon-btn" style="width:38px; height:38px;" @click="seek(0)" title="ไปจุดแรก"><i class="fas fa-backward-fast"></i></button>
                            <button type="button" class="tp-icon-btn" style="width:38px; height:38px;" @click="seek(index - 1)" title="ถอยหนึ่งจุด"><i class="fas fa-backward-step"></i></button>
                            <button type="button" class="tp-btn tp-btn-primary" style="width:54px;" @click="toggle()" :title="playing ? 'หยุด' : 'เล่น'"><i class="fas" :class="playing ? 'fa-pause' : 'fa-play'"></i></button>
                            <button type="button" class="tp-icon-btn" style="width:38px; height:38px;" @click="seek(index + 1)" title="เดินหน้าหนึ่งจุด"><i class="fas fa-forward-step"></i></button>
                            <button type="button" class="tp-icon-btn" style="width:38px; height:38px;" @click="seek(points.length - 1)" title="ไปจุดล่าสุด"><i class="fas fa-forward-fast"></i></button>
                            <select class="tp-input" style="width:auto; padding:7px 10px; font-size:12.5px;" @change="setSpeed($event.target.value)">
                                <option value="1">1x</option>
                                <option value="2">2x</option>
                                <option value="5">5x</option>
                                <option value="10">10x</option>
                            </select>
                        </div>
                        <div style="font-size:12.5px; color:var(--ink2); display:flex; flex-wrap:wrap; gap:12px;">
                            <span><i class="fas fa-clock"></i> <b class="tp-num" style="color:var(--ink);" x-text="time(current() && current().t)"></b></span>
                            <span><i class="fas fa-route"></i> <b class="tp-num" style="color:var(--ink);" x-text="distanceUntil(index).toFixed(2)"></b> กม.</span>
                            <span><i class="fas fa-map-pin"></i> จุด <b class="tp-num" style="color:var(--ink);" x-text="(index + 1) + '/' + points.length"></b></span>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- ===== ตำแหน่งล่าสุด ===== --}}
        <div class="tp-card" style="padding:18px;">
            <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-location-dot"></i> ตำแหน่งล่าสุด</div>
            @if ($lastLocation)
                <div style="display:grid; grid-template-columns:auto 1fr; gap:7px 12px; font-size:13px;">
                    <span style="color:var(--ink2);">พิกัด</span>
                    <span class="tp-num">{{ number_format($lastLocation['latitude'], 6) }}, {{ number_format($lastLocation['longitude'], 6) }}</span>
                    <span style="color:var(--ink2);">อัปเดต</span>
                    <span>{{ $lastLocation['updated_ago'] ?? '-' }}</span>
                    @if ($latestLocation)
                        <span style="color:var(--ink2);">ความเร็ว</span>
                        <span class="tp-num">{{ $latestLocation->speed !== null ? number_format((float) $latestLocation->speed, 1).' กม./ชม.' : '-' }}</span>
                        <span style="color:var(--ink2);">ความแม่นยำ</span>
                        <span class="tp-num">{{ $latestLocation->accuracy !== null ? '±'.number_format((float) $latestLocation->accuracy, 0).' ม.' : '-' }}</span>
                        <span style="color:var(--ink2);">แบตเตอรี่</span>
                        <span>
                            @if ($latestLocation->battery_level !== null)
                                <span style="display:inline-flex; align-items:center; gap:8px; width:100%;">
                                    <span class="tp-inset-sm" style="flex:1; height:8px; border-radius:8px; overflow:hidden; display:block;">
                                        <span style="display:block; height:100%; width:{{ max(0, min(100, (int) $latestLocation->battery_level)) }}%; background:var({{ (int) $latestLocation->battery_level <= 20 ? '--w-bad' : '--w-ok' }});"></span>
                                    </span>
                                    <b class="tp-num">{{ (int) $latestLocation->battery_level }}%</b>
                                </span>
                            @else
                                -
                            @endif
                        </span>
                        <span style="color:var(--ink2);">บันทึกเมื่อ</span>
                        <span>{{ $latestAt?->thaidate('j M Y H:i:s') ?? '-' }}</span>
                    @endif
                </div>
                <a href="https://www.openstreetmap.org/?mlat={{ $lastLocation['latitude'] }}&mlon={{ $lastLocation['longitude'] }}#map=17/{{ $lastLocation['latitude'] }}/{{ $lastLocation['longitude'] }}"
                   target="_blank" rel="noopener" class="tp-btn tp-btn-sm" style="margin-top:12px; width:100%;"><i class="fas fa-up-right-from-square"></i> เปิดใน OpenStreetMap</a>
            @else
                <p style="font-size:13px; color:var(--ink2); margin:0;">ไรเดอร์ยังไม่เคยส่งตำแหน่ง GPS</p>
            @endif
            <template x-if="liveInfo">
                <div class="tp-well" style="margin-top:12px; padding:10px 12px; font-size:12.5px;">
                    <b>ตอนนี้:</b> <span class="tp-num" x-text="num(liveInfo.latitude, 5) + ', ' + num(liveInfo.longitude, 5)"></span>
                    <span x-show="liveInfo.battery !== null" x-text="' · แบต ' + liveInfo.battery + '%'"></span>
                    <span x-text="' · ' + dateTime(liveInfo.updated_at)"></span>
                </div>
            </template>
            <p x-show="liveError" x-cloak style="font-size:12px; color:var(--w-bad); margin:10px 0 0;" x-text="liveError"></p>
        </div>

        {{-- ===== สรุป 24 ชม. ===== --}}
        <div class="tp-card" style="padding:18px;">
            <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-chart-line"></i> สรุป 24 ชั่วโมง</div>
            <div style="display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px;">
                <div class="tp-well" style="padding:12px; text-align:center;">
                    <div class="tp-num" style="font-size:22px; font-weight:800;">{{ number_format(count($trackPoints)) }}</div>
                    <div style="font-size:11.5px; color:var(--ink2);">จุด GPS</div>
                </div>
                <div class="tp-well" style="padding:12px; text-align:center;">
                    <div class="tp-num" style="font-size:22px; font-weight:800;" x-text="distanceUntil(points.length - 1).toFixed(2)">0</div>
                    <div style="font-size:11.5px; color:var(--ink2);">กม. โดยประมาณ</div>
                </div>
            </div>
            @if (count($trackPoints) === 0)
                <p style="font-size:12.5px; color:var(--ink2); margin:12px 0 0;">ยังไม่มีเส้นทางใน 24 ชม. — ระบบบันทึกเส้นทางเฉพาะตอนที่ไรเดอร์มีงาน</p>
            @endif
        </div>
    </div>
</div>
@endsection
