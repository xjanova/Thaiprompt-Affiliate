{{--
 | เล่นย้อนหลังเส้นทาง GPS 24 ชม. (admin.riders.playback) — ธีม V4 + OpenStreetMap/Leaflet
 | ตัวแปรจาก Admin\RiderController@locationPlayback: $rider, $logs (RiderLocation เก่า → ใหม่), $pageTitle
--}}
@extends('layouts.admin-v4')

@section('title', $pageTitle ?? 'เล่นย้อนหลัง GPS')

@section('content')
@include('admin.riders.partials.v4-kit')
@include('admin.riders.partials.leaflet')
@include('admin.riders.partials.track-js')
@php
    $trackPoints = $logs->map(fn ($log) => [
        'lat' => (float) $log->latitude,
        'lng' => (float) $log->longitude,
        't' => ($log->recorded_at ?? $log->created_at)?->toIso8601String(),
        'speed' => $log->speed !== null ? (float) $log->speed : null,
        'heading' => $log->heading !== null ? (float) $log->heading : null,
        'accuracy' => $log->accuracy !== null ? (float) $log->accuracy : null,
        'battery' => $log->battery_level !== null ? (int) $log->battery_level : null,
        'job_id' => $log->job_id !== null ? (int) $log->job_id : null,
    ])->values()->all();
@endphp
<div x-data="w1Track(@js($trackPoints), {})" x-init="boot()" style="display:flex; flex-direction:column; gap:16px;">

    {{-- ===== หัวเรื่อง ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div style="display:flex; align-items:center; gap:14px;">
            <a href="{{ route('admin.riders.locations', $rider) }}" class="tp-icon-btn" title="กลับหน้าตำแหน่ง GPS"><i class="fas fa-arrow-left"></i></a>
            <div>
                <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ไรเดอร์ · เล่นย้อนหลัง</div>
                <h1 class="tp-num" style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:4px 0 0;">เล่นย้อนหลัง: {{ $rider->full_name }}</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">{{ number_format($logs->count()) }} จุด · ข้อมูล 24 ชั่วโมงล่าสุด</div>
            </div>
        </div>
        <a href="{{ route('admin.riders.show', $rider) }}" class="tp-btn tp-btn-sm"><i class="fas fa-user"></i> โปรไฟล์ไรเดอร์</a>
    </div>

    @if ($logs->isEmpty())
        <div class="tp-card" style="padding:40px 20px; text-align:center; color:var(--ink2);">
            <i class="fas fa-map-location-dot" style="font-size:30px; opacity:.5; display:block; margin-bottom:10px;"></i>
            ไม่มีข้อมูลตำแหน่งใน 24 ชั่วโมงล่าสุด — ระบบบันทึกเส้นทางเฉพาะตอนที่ไรเดอร์มีงาน
        </div>
    @else
        <div style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-start;">
            <div class="tp-card" style="padding:0; overflow:hidden; flex:3 1 520px; min-width:0;">
                <div x-ref="map" style="width:100%; height:min(62vh, 560px); min-height:320px;"></div>
                <div style="padding:14px 16px; display:flex; flex-direction:column; gap:10px;">
                    <input type="range" class="tp-range" min="0" :max="points.length - 1" :value="index" @input="seek($event.target.value)">
                    <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px;">
                        <div style="display:flex; gap:6px; align-items:center;">
                            <button type="button" class="tp-btn tp-btn-primary" style="width:54px;" @click="toggle()" :title="playing ? 'หยุด' : 'เล่น'"><i class="fas" :class="playing ? 'fa-pause' : 'fa-play'"></i></button>
                            <button type="button" class="tp-icon-btn" style="width:38px; height:38px;" @click="seek(index - 1)" title="ถอยหนึ่งจุด"><i class="fas fa-backward-step"></i></button>
                            <button type="button" class="tp-icon-btn" style="width:38px; height:38px;" @click="seek(index + 1)" title="เดินหน้าหนึ่งจุด"><i class="fas fa-forward-step"></i></button>
                            <select class="tp-input" style="width:auto; padding:7px 10px; font-size:12.5px;" @change="setSpeed($event.target.value)">
                                <option value="0.5">0.5x</option>
                                <option value="1" selected>1x</option>
                                <option value="2">2x</option>
                                <option value="5">5x</option>
                                <option value="10">10x</option>
                            </select>
                        </div>
                        <div style="font-size:12.5px; color:var(--ink2); display:flex; flex-wrap:wrap; gap:12px;">
                            <span><i class="fas fa-clock"></i> <b class="tp-num" style="color:var(--ink);" x-text="time(current() && current().t)"></b></span>
                            <span><i class="fas fa-gauge-high"></i> <b class="tp-num" style="color:var(--ink);" x-text="num(current() && current().speed, 1)"></b> กม./ชม.</span>
                            <span><i class="fas fa-route"></i> <b class="tp-num" style="color:var(--ink);" x-text="distanceUntil(index).toFixed(2)"></b> กม.</span>
                            <span><i class="fas fa-map-pin"></i> <b class="tp-num" style="color:var(--ink);" x-text="(index + 1) + '/' + points.length"></b></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== ไทม์ไลน์ ===== --}}
            <div class="tp-card" style="padding:16px; flex:1 1 280px; min-width:0;">
                <div class="tp-section-h" style="margin-bottom:10px;"><i class="fas fa-clock-rotate-left"></i> ไทม์ไลน์ตำแหน่ง</div>
                <div style="display:flex; flex-direction:column; gap:6px; max-height:560px; overflow-y:auto; padding:2px;">
                    <template x-for="(p, i) in points" :key="i">
                        <button type="button" @click="seek(i)"
                                style="display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:12px; border:0; cursor:pointer; text-align:left; font-family:inherit; color:var(--ink); background:var(--card-bg);"
                                :style="{ boxShadow: i === index ? 'var(--inset-sm)' : 'var(--raise)' }">
                            <span class="tp-num" style="width:30px; font-size:11px; color:var(--ink2);" x-text="i + 1"></span>
                            <span style="flex:1; min-width:0;">
                                <span class="tp-num" style="display:block; font-size:12.5px; font-weight:700;" x-text="time(p.t)"></span>
                                <span style="display:block; font-size:11px; color:var(--ink2);" x-text="p.speed ? num(p.speed, 1) + ' กม./ชม.' : 'หยุดนิ่ง/ไม่ทราบความเร็ว'"></span>
                            </span>
                            <span x-show="p.battery !== null" style="font-size:11px;" :style="{ color: p.battery !== null && p.battery < 20 ? 'var(--w-bad)' : 'var(--ink2)' }">
                                <i class="fas fa-battery-half"></i> <span x-text="p.battery + '%'"></span>
                            </span>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
