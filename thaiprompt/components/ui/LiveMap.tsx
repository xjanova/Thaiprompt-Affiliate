/**
 * LiveMap — แผนที่สดขนาดเล็ก (Leaflet ใน WebView) ไม่ต้องใช้ API key
 *
 * ฉากหลังแผนที่ (2026-10-04):
 *   - หลัก = แผนที่เวกเตอร์ OpenFreeMap (positron โหมดสว่าง / dark โหมดมืด) ผ่าน maplibre-gl + @maplibre/maplibre-gl-leaflet
 *     (เลิกดึง tile.openstreetmap.org เป็นหลัก เพราะขัดนโยบายการใช้ tile ของ OSM สำหรับแอป)
 *   - สไตล์มืดโหลดไม่ได้ → positron + กลับสีด้วย CSS · ไลบรารีเวกเตอร์โหลดไม่ได้/ไม่มี WebGL → กลับไปใช้ tile แบบรูปเดิม
 *   - เครดิต © OpenFreeMap © OpenMapTiles © OpenStreetMap แสดงเสมอ
 * - โหลดสคริปต์จาก unpkg (สำรอง jsDelivr) พร้อม SRI ทุกไฟล์ · แผนที่โหลดไม่ได้ → การ์ดสำรอง + ปุ่มเปิด Google Maps
 * - หมุดอัปเดตผ่าน injectJavaScript โดยไม่โหลดหน้าใหม่ (หมุดเลื่อนนุ่มๆ ไปตำแหน่งใหม่)
 * - เส้นทางตามถนน: routePolyline (encoded polyline ความละเอียด 6 หลักจาก server) → เส้นน้ำเงินกรมท่า + เส้นประทอง
 * - หมุดคน: photoUrl (รูปลายน้ำ https) + hearts (ป้ายหัวใจ) + radiusM (วงตำแหน่งคร่าวๆ) + online (จุดสถานะ)
 * - โหมดปักหมุด: ส่ง onPick → แตะแผนที่/ลากหมุด draggableId แล้วได้พิกัดกลับมา
 *
 * ชื่อร้าน/ป้ายหมุด/รูปมาจาก server → สร้าง DOM ด้วย textContent / img.src เท่านั้น (ไม่ประกอบ HTML จากข้อมูล กัน XSS)
 *
 * react-native-webview เป็น native module → โหลดแบบ lazy ใน try/catch
 * (แอปรุ่นเก่าที่ไม่มี native module จะเห็นการ์ดสำรองแทน ไม่ล้มทั้งแอป)
 *
 * @example
 * <LiveMap
 *   markers={[{ id: 'shop', kind: 'shop', latitude: 13.75, longitude: 100.5, label: 'ครัวไทยพร้อม' }]}
 *   routePolyline={rider.route_polyline}
 *   height={200}
 * />
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { ActivityIndicator, Linking, Pressable, StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';
import { Text } from '@/components/ui/Text';
import type { WebView as WebViewType, WebViewMessageEvent } from 'react-native-webview';
import * as WebBrowser from 'expo-web-browser';
import { APP_INFO } from '@/config/appConfig';
import { useTheme, radii, spacing, typography, clayShadowStyle, palette } from '@/theme';
import { Button3D } from './Button3D';
import { Icon, type IconName } from './Icon';
import { ICON_PATHS } from './iconPaths';
import { tapHaptic } from './haptics';
import { decodePolyline, thinRoute } from '@/utils/polyline';

export { decodePolyline };

export type LiveMapMarkerKind = 'shop' | 'rider' | 'me' | 'home' | 'pin';

export interface LiveMapMarker {
  id: string;
  latitude: number;
  longitude: number;
  kind: LiveMapMarkerKind;
  /** ป้ายเล็กเหนือหมุด (ข้อความล้วน) */
  label?: string | null;
  /** รูปคน (https เท่านั้น) — แสดงเป็นหมุดรูปกลมขอบทองแทนไอคอน */
  photoUrl?: string | null;
  /** ป้ายหัวใจใต้หมุด (จำนวน) */
  hearts?: number | null;
  /** วงรัศมี (เมตร) รอบหมุด — ใช้บอกว่าตำแหน่งเป็นแบบคร่าวๆ */
  radiusM?: number | null;
  /** จุดสถานะมุมหมุด: true = เขียว (ว่าง) · false = เทา */
  online?: boolean | null;
}

export interface LiveMapProps {
  markers: LiveMapMarker[];
  /** ความสูง (ค่าเริ่มต้น 220) */
  height?: number;
  /** id หมุดที่ให้กล้องตามเมื่อหลุดขอบจอ (เช่น ไรเดอร์) */
  followId?: string;
  /** โหมดปักหมุด: แตะแผนที่/ลากหมุดแล้วได้พิกัด */
  onPick?: (coords: { latitude: number; longitude: number }) => void;
  /** id หมุดที่ลากได้ (ใช้คู่กับ onPick) */
  draggableId?: string;
  /** หมุดที่ปุ่ม "เปิดใน Google Maps" ชี้ไป (ไม่ส่ง = หมุดแรก · null = ไม่แสดงปุ่ม) */
  openTargetId?: string | null;
  /** ข้อความใต้แผนที่ เช่น "อัปเดต 10 วินาทีก่อน" */
  caption?: string | null;
  /** เส้นทางตามถนน (encoded polyline) */
  routePolyline?: string | null;
  /** ความละเอียดของ encoded polyline (ค่าเริ่มต้น 6 ตาม Valhalla/Google Routes ของ server) */
  routePrecision?: 5 | 6;
  /** แตะหมุด → ได้ id หมุด */
  onMarkerPress?: (id: string) => void;
  /** ซ่อนปุ่มจัดกล้อง (มุมขวาบน) */
  hideRecenter?: boolean;
  /** แผนที่เต็มขอบ (ไม่มีมุมโค้ง/ขอบ/เงา) — ใช้เป็นฉากหลังของหน้า */
  bare?: boolean;
  /** เปลี่ยนค่านี้เมื่ออยากให้กล้องจัดให้เห็นทุกหมุดใหม่ (เช่น กดหาตำแหน่งฉัน) */
  refitKey?: number;
  accessibilityLabel?: string;
  style?: StyleProp<ViewStyle>;
}

// โหลด WebView ครั้งเดียว — ไม่มี native module (build เก่า) = null → ใช้การ์ดสำรอง
let webViewComponent: typeof WebViewType | null | undefined;
const loadWebView = (): typeof WebViewType | null => {
  if (webViewComponent !== undefined) return webViewComponent;
  try {
    // eslint-disable-next-line @typescript-eslint/no-require-imports
    webViewComponent = (require('react-native-webview') as { WebView: typeof WebViewType }).WebView ?? null;
  } catch {
    webViewComponent = null;
  }
  return webViewComponent;
};

const MAP_BASE_URL = `${APP_INFO.WEBSITE}/`;
const READY_TIMEOUT_MS = 15000;
/** จุดของเส้นทางสูงสุดที่ส่งเข้าแผนที่ (เส้นยาวมากถูกลดจุดลง) */
const MAX_ROUTE_POINTS = 800;
const EXTERNAL_LINK_HOSTS = [
  'www.openstreetmap.org',
  'openstreetmap.org',
  'leafletjs.com',
  'osmfoundation.org',
  'wiki.osmfoundation.org',
  'openfreemap.org',
  'www.openmaptiles.org',
  'openmaptiles.org',
  'maplibre.org',
];

/** ไอคอนของหมุดแต่ละชนิด (หมุดวาดเป็นวงน้ำเงินกรมท่า + ไอคอนทอง — ไม่ใช้อีโมจิ) */
const MARKER_ICON: Record<LiveMapMarkerKind, IconName> = {
  shop: 'storefront',
  rider: 'moped',
  me: 'user',
  home: 'house',
  pin: 'map-pin',
};

/** SVG ของไอคอนหมุด ส่งเข้า HTML ของแผนที่ (path จากชุดไอคอนเดียวกับแอป) */
const markerSvg = (name: IconName, color: string, px: number = 19): string =>
  `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" width="${px}" height="${px}">${(ICON_PATHS[name].fill as readonly string[])
    .map((d) => `<path d="${d}" fill="${color}"/>`)
    .join('')}</svg>`;

const MARKER_SVGS: Record<LiveMapMarkerKind | 'heart', string> = {
  shop: markerSvg(MARKER_ICON.shop, palette.gold400),
  rider: markerSvg(MARKER_ICON.rider, palette.gold400),
  me: markerSvg(MARKER_ICON.me, palette.clayLightCard),
  home: markerSvg(MARKER_ICON.home, palette.gold400),
  pin: markerSvg(MARKER_ICON.pin, palette.gold400),
  heart: markerSvg('heart', palette.red500, 11),
};

const MARKER_NAME: Record<LiveMapMarkerKind, string> = {
  shop: 'ร้าน',
  rider: 'ไรเดอร์',
  me: 'คุณ',
  home: 'จุดส่ง',
  pin: 'หมุดที่เลือก',
};

/** URL รูปที่ยอมให้แผนที่โหลด (https เท่านั้น ไม่มีอักขระแปลก) */
const safePhotoUrl = (url: unknown): string | null =>
  typeof url === 'string' && url.length <= 2048 && /^https:\/\/[^\s"'<>\\]+$/i.test(url) ? url : null;

/** เปิดตำแหน่งใน Google Maps (แอปถ้ามี / เว็บถ้าไม่มี) */
export const openInGoogleMaps = async (
  latitude: number,
  longitude: number,
  mode: 'place' | 'directions' = 'place'
): Promise<void> => {
  if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return;
  const point = `${latitude.toFixed(6)},${longitude.toFixed(6)}`;
  const url =
    mode === 'directions'
      ? `https://www.google.com/maps/dir/?api=1&destination=${point}`
      : `https://www.google.com/maps/search/?api=1&query=${point}`;
  try {
    await Linking.openURL(url);
  } catch {
    try {
      await WebBrowser.openBrowserAsync(url);
    } catch {
      // เปิดไม่ได้ก็เงียบไว้
    }
  }
};

// =====================================================
// หน้า HTML ของแผนที่ (คงที่ — ไม่ขึ้นกับหมุด เพื่อไม่ต้องโหลดใหม่)
// =====================================================

interface MapPalette {
  background: string;
  gold: string;
  goldLight: string;
  goldDeep: string;
  success: string;
  info: string;
  navy: string;
  white: string;
  muted: string;
  danger: string;
}

const buildHtml = (p: MapPalette, iconsJson: string): string => `<!DOCTYPE html>
<html><head><meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no"/>
<link id="lcss" rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous"/>
<style>
html,body,#map{margin:0;padding:0;height:100%;width:100%;background:${p.background};}
body{-webkit-tap-highlight-color:transparent;font-family:-apple-system,Roboto,sans-serif;}
.lm{display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:18px;background:${p.navy};border:3px solid ${p.gold};box-shadow:0 4px 10px rgba(6,13,27,.45);box-sizing:border-box;}
.lm svg{display:block;}
.lm-rider{border-color:${p.success};}
.lm-me{background:${p.info};border-color:${p.white};}
.lm-home{border-color:${p.white};}
.lm-rider:after{content:'';position:absolute;width:36px;height:36px;border-radius:18px;border:2px solid ${p.success};animation:p 1.8s ease-out infinite;}
@keyframes p{0%{transform:scale(1);opacity:.8}100%{transform:scale(1.9);opacity:0}}
.lp{position:relative;width:46px;height:46px;border-radius:23px;padding:3px;box-sizing:border-box;background:linear-gradient(135deg,${p.goldLight},${p.gold},${p.goldDeep});box-shadow:0 6px 14px rgba(6,13,27,.4);}
.lp img,.lp .lp-ph{display:block;width:40px;height:40px;border-radius:20px;object-fit:cover;background:${p.navy};}
.lp .lp-ph{display:flex;align-items:center;justify-content:center;}
.lp-dot{position:absolute;right:-1px;bottom:1px;width:12px;height:12px;border-radius:6px;border:2px solid ${p.white};box-sizing:border-box;}
.lp-hearts{position:absolute;left:50%;bottom:-13px;transform:translateX(-50%);display:flex;align-items:center;gap:2px;padding:1px 6px 1px 5px;border-radius:9px;background:${p.white};box-shadow:0 2px 6px rgba(6,13,27,.25);font-size:11px;font-weight:700;color:${p.navy};white-space:nowrap;}
.lp-hearts svg{display:block;}
.leaflet-tooltip{font-size:12px;font-weight:600;padding:2px 6px;border-radius:8px;}
.leaflet-control-attribution{font-size:10px;}
body.dark.raster .leaflet-tile-pane{filter:invert(1) hue-rotate(180deg) brightness(.92) contrast(.9);}
body.glinvert .leaflet-gl-layer{filter:invert(1) hue-rotate(180deg) brightness(.92) contrast(.9);}
</style></head>
<body><div id="map"></div>
<script>
(function(){
  var RN=window.ReactNativeWebView;
  function post(o){try{RN&&RN.postMessage(JSON.stringify(o));}catch(e){}}
  var JS=['https://unpkg.com/leaflet@1.9.4/dist/leaflet.js','https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js'];
  var JS_SRI='sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=';
  var CSS_ALT='https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css';
  var CSS_SRI='sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=';
  var ML_JS=['https://unpkg.com/maplibre-gl@5.24.0/dist/maplibre-gl.js','https://cdn.jsdelivr.net/npm/maplibre-gl@5.24.0/dist/maplibre-gl.js'];
  var ML_SRI='sha384-5+cfbwT0iiub6VsQAdn6yz16nr6sDiQoHx6tm4O8OVYXHYOxcffFmCJBL0dgdvGp';
  var ML_CSS=['https://unpkg.com/maplibre-gl@5.24.0/dist/maplibre-gl.css','https://cdn.jsdelivr.net/npm/maplibre-gl@5.24.0/dist/maplibre-gl.css'];
  var ML_CSS_SRI='sha384-uTttxo/aOKbdE5RlD/SPzSDoDmNvGlUYPjONi2MN/b7c9HPSvW07OIuyP7uL6jxK';
  var MLL_JS=['https://unpkg.com/@maplibre/maplibre-gl-leaflet@0.1.4/leaflet-maplibre-gl.js','https://cdn.jsdelivr.net/npm/@maplibre/maplibre-gl-leaflet@0.1.4/leaflet-maplibre-gl.js'];
  var MLL_SRI='sha384-tXYNKOHx4T02jMP7YYCtBxPIv1B5gaA5mcVPBzqMp6d7VzWzxJgI2aWF/nJLrQdS';
  var STYLES={light:'https://tiles.openfreemap.org/styles/positron',dark:'https://tiles.openfreemap.org/styles/dark'};
  var ATTR='&copy; <a href="https://openfreemap.org">OpenFreeMap</a> &copy; <a href="https://www.openmaptiles.org/">OpenMapTiles</a> &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>';
  var OSM_ATTR='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>';
  var ICONS=${iconsJson};
  var map=null,layers={},circles={},targets={},fitted=false,opts={},pending=null,base=null,route=null,routeSig='',routeBounds=null,lastRefit=null;
  function cls(name,on){if(on){document.body.classList.add(name);}else{document.body.classList.remove(name);}}
  function textEl(s){var el=document.createElement('span');el.textContent=String(s);return el;}
  function safeUrl(u){return typeof u==='string'&&/^https:\\/\\/[^\\s"'<>\\\\]+$/i.test(u)&&u.length<=2048?u:null;}
  function iconFor(mk){
    var photo=safeUrl(mk.photo);
    if(photo||typeof mk.hearts==='number'||typeof mk.online==='boolean'){
      var wrap=document.createElement('div');wrap.className='lp';
      if(photo){var img=document.createElement('img');img.alt='';img.referrerPolicy='no-referrer';img.src=photo;img.onerror=function(){img.style.visibility='hidden';};wrap.appendChild(img);}
      else{var ph=document.createElement('div');ph.className='lp-ph';ph.innerHTML=ICONS.me;wrap.appendChild(ph);}
      if(typeof mk.online==='boolean'){var d=document.createElement('div');d.className='lp-dot';d.style.background=mk.online?'${p.success}':'${p.muted}';wrap.appendChild(d);}
      if(typeof mk.hearts==='number'&&isFinite(mk.hearts)){var h=document.createElement('div');h.className='lp-hearts';h.innerHTML=ICONS.heart;h.appendChild(textEl(Math.max(0,Math.round(mk.hearts))));wrap.appendChild(h);}
      return L.divIcon({className:'',html:wrap,iconSize:[46,46],iconAnchor:[23,23],tooltipAnchor:[0,-23]});
    }
    var k=ICONS[mk.kind]&&mk.kind!=='heart'?mk.kind:'pin';
    return L.divIcon({className:'',html:'<div class="lm lm-'+k+'">'+ICONS[k]+'</div>',iconSize:[36,36],iconAnchor:[18,18],tooltipAnchor:[0,-18]});
  }
  function sigOf(mk){return [mk.kind,mk.photo||'',mk.hearts==null?'':mk.hearts,mk.online==null?'':mk.online].join('|');}
  function moveTo(m,lat,lng){
    var from=m.getLatLng();
    if(Math.abs(from.lat-lat)<1e-7&&Math.abs(from.lng-lng)<1e-7)return;
    if(m._anim)cancelAnimationFrame(m._anim);
    var t0=null,d=900;
    function step(ts){
      if(!t0)t0=ts;
      var k=Math.min(1,(ts-t0)/d),e=k<.5?2*k*k:-1+(4-2*k)*k;
      m.setLatLng([from.lat+(lat-from.lat)*e,from.lng+(lng-from.lng)*e]);
      if(k<1)m._anim=requestAnimationFrame(step);
    }
    m._anim=requestAnimationFrame(step);
  }
  function allBounds(){
    var ids=Object.keys(targets),b=null;
    if(ids.length){b=L.latLngBounds(ids.map(function(id){return targets[id];}));}
    if(routeBounds){b=b?b.extend(routeBounds):L.latLngBounds(routeBounds.getSouthWest(),routeBounds.getNorthEast());}
    return b;
  }
  function frame(force){
    var ids=Object.keys(targets);
    if(!map||(!ids.length&&!routeBounds))return;
    if(!fitted||force){
      fitted=true;
      if(ids.length===1&&!routeBounds){map.setView(targets[ids[0]],16);}else{var fb=allBounds();if(fb)map.fitBounds(fb,{padding:[40,40],maxZoom:17});}
      return;
    }
    var view=map.getBounds().pad(-0.12);
    if(opts.followId&&targets[opts.followId]){
      if(!view.contains(targets[opts.followId]))map.panTo(targets[opts.followId]);
      return;
    }
    for(var i=0;i<ids.length;i++){
      if(!view.contains(targets[ids[i]])){
        if(ids.length===1&&!routeBounds){map.panTo(targets[ids[0]]);}else{var b2=allBounds();if(b2)map.fitBounds(b2,{padding:[40,40],maxZoom:17});}
        return;
      }
    }
  }
  function applyRoute(pts){
    var list=Array.isArray(pts)?pts.filter(function(q){return q&&isFinite(q[0])&&isFinite(q[1]);}):[];
    var sig=list.length?list.length+':'+list[0].join(',')+':'+list[list.length-1].join(','):'';
    if(sig===routeSig)return false;
    routeSig=sig;
    if(route){map.removeLayer(route);route=null;routeBounds=null;}
    if(list.length>1){
      route=L.layerGroup([
        L.polyline(list,{color:'${p.navy}',weight:7,opacity:.92,lineCap:'round',lineJoin:'round',interactive:false}),
        L.polyline(list,{color:'${p.gold}',weight:3,opacity:1,dashArray:'1 9',lineCap:'round',lineJoin:'round',interactive:false})
      ]).addTo(map);
      routeBounds=L.latLngBounds(list);
    }
    return true;
  }
  function apply(p){
    if(!map){pending=p;return;}
    var wasDark=!!opts.dark;
    opts=p||{};
    cls('dark',!!opts.dark);
    if(base&&base.kind==='vector'&&wasDark!==!!opts.dark)glStyle(opts.dark?'dark':'light');
    var seen={};
    (opts.markers||[]).forEach(function(mk){
      if(!mk||typeof mk.lat!=='number'||typeof mk.lng!=='number'||!isFinite(mk.lat)||!isFinite(mk.lng))return;
      var id=String(mk.id);seen[id]=1;targets[id]=[mk.lat,mk.lng];
      var m=layers[id];
      var drag=opts.draggableId===id;
      if(!m){
        m=L.marker([mk.lat,mk.lng],{icon:iconFor(mk),draggable:drag,keyboard:false,autoPan:drag});
        m._sig=sigOf(mk);m._drag=drag;
        if(drag){m.on('dragend',function(){var ll=m.getLatLng();targets[id]=[ll.lat,ll.lng];post({type:'pick',lat:ll.lat,lng:ll.lng});});}
        m.on('click',function(){if(opts.markerPress)post({type:'marker',id:id});});
        m.addTo(map);layers[id]=m;
      }else{
        var s=sigOf(mk);
        if(m._sig!==s){m.setIcon(iconFor(mk));m._sig=s;}
        moveTo(m,mk.lat,mk.lng);
      }
      if(mk.label){
        if(m.getTooltip()){m.setTooltipContent(textEl(mk.label));}
        else{m.bindTooltip(textEl(mk.label),{direction:'top',permanent:!!opts.permanentLabels});}
      }else if(m.getTooltip()){m.unbindTooltip();}
      var r=typeof mk.radius==='number'&&isFinite(mk.radius)&&mk.radius>0?Math.min(mk.radius,5000):0;
      if(r){
        if(!circles[id]){circles[id]=L.circle([mk.lat,mk.lng],{radius:r,color:'${p.muted}',weight:1,dashArray:'3 4',fillColor:'${p.muted}',fillOpacity:.14,interactive:false}).addTo(map);}
        else{circles[id].setLatLng([mk.lat,mk.lng]);circles[id].setRadius(r);}
      }else if(circles[id]){map.removeLayer(circles[id]);delete circles[id];}
    });
    Object.keys(layers).forEach(function(id){if(!seen[id]){map.removeLayer(layers[id]);delete layers[id];delete targets[id];if(circles[id]){map.removeLayer(circles[id]);delete circles[id];}}});
    var routeChanged=applyRoute(opts.route);
    var refitAsked=opts.refitKey!=null&&opts.refitKey!==lastRefit;
    lastRefit=opts.refitKey;
    frame(opts.refit===true||refitAsked||(routeChanged&&!!routeBounds));
  }
  // ---------- ฉากหลัง: เวกเตอร์ OpenFreeMap → สำรองเป็น tile รูป ----------
  function removeBase(){if(base&&base.layer){try{map.removeLayer(base.layer);}catch(e){}}base=null;cls('glinvert',false);cls('raster',false);}
  function raster(){
    if(!map||(base&&base.kind==='raster'))return;
    removeBase();
    var tl=L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:OSM_ATTR});
    var tileErrors=0,tileOk=0;
    tl.on('tileerror',function(){tileErrors++;if(tileErrors===8&&tileOk===0)post({type:'tiles-error'});});
    tl.on('tileload',function(){tileOk++;});
    tl.addTo(map);
    base={kind:'raster',layer:tl};
    cls('raster',true);
  }
  function glStyle(key){
    if(!base||base.kind!=='vector')return;
    base.styleKey=key;base.pending=true;
    cls('glinvert',false);
    try{base.layer.getMaplibreMap().setStyle(STYLES[key]);}catch(e){raster();}
  }
  function hasWebGL(){try{var c=document.createElement('canvas');return !!(c.getContext('webgl2')||c.getContext('webgl'));}catch(e){return false;}}
  function loadScript(urls,sri,i,ok,fail){
    if(i>=urls.length){fail();return;}
    var s=document.createElement('script');
    s.src=urls[i];s.integrity=sri;s.crossOrigin='anonymous';
    s.onload=ok;s.onerror=function(){loadScript(urls,sri,i+1,ok,fail);};
    document.head.appendChild(s);
  }
  function loadCss(urls,sri,i){
    if(i>=urls.length)return;
    var l=document.createElement('link');l.rel='stylesheet';l.href=urls[i];l.integrity=sri;l.crossOrigin='anonymous';
    l.onerror=function(){loadCss(urls,sri,i+1);};
    document.head.appendChild(l);
  }
  function startGL(){
    if(!window.maplibregl||!L.maplibreGL){raster();return;}
    try{
      var key=opts.dark?'dark':'light';
      var gl=L.maplibreGL({style:STYLES[key],attributionControl:{customAttribution:ATTR},interactive:false});
      base={kind:'vector',layer:gl,styleKey:key,pending:true};
      var guard=setTimeout(function(){if(base&&base.kind==='vector'&&base.pending)raster();},12000);
      gl.addTo(map);
      var m=gl.getMaplibreMap();
      m.on('style.load',function(){if(base&&base.kind==='vector'){base.pending=false;clearTimeout(guard);}});
      m.on('error',function(){
        if(!base||base.kind!=='vector'||!base.pending)return;
        // สไตล์มืดโหลดไม่ได้ → ใช้สไตล์สว่างแล้วกลับสีแทน · อย่างอื่น → tile รูป
        if(base.styleKey==='dark'){glStyle('light');cls('glinvert',!!opts.dark);return;}
        clearTimeout(guard);raster();
      });
    }catch(e){raster();}
  }
  function vector(){
    if(!hasWebGL()){raster();return;}
    var done=false;
    var guard=setTimeout(function(){if(!done){done=true;raster();}},12000);
    function ok(){if(done)return;done=true;clearTimeout(guard);startGL();}
    function fail(){if(done)return;done=true;clearTimeout(guard);raster();}
    loadCss(ML_CSS,ML_CSS_SRI,0);
    loadScript(ML_JS,ML_SRI,0,function(){loadScript(MLL_JS,MLL_SRI,0,ok,fail);},fail);
  }
  function init(){
    if(!window.L){post({type:'error',reason:'leaflet'});return;}
    try{
      map=L.map('map',{zoomControl:false,attributionControl:true});
      map.attributionControl.setPrefix('<a href="https://leafletjs.com">Leaflet</a>');
      map.setView([13.7563,100.5018],12);
      map.on('click',function(e){if(opts.pickable)post({type:'pick',lat:e.latlng.lat,lng:e.latlng.lng});});
      window.addEventListener('resize',function(){map&&map.invalidateSize();});
      document.addEventListener('click',function(ev){
        var t=ev.target,a=t&&t.closest?t.closest('a'):null;
        if(a&&a.href){ev.preventDefault();ev.stopPropagation();post({type:'link',href:a.href});}
      },true);
      if(pending){var pp=pending;pending=null;opts=pp||{};}
      cls('dark',!!opts.dark);
      vector();
      post({type:'ready'});
      if(pp)apply(pp);
    }catch(e){post({type:'error',reason:'init'});}
  }
  function load(i){
    if(i>=JS.length){post({type:'error',reason:'leaflet'});return;}
    var s=document.createElement('script');
    s.src=JS[i];s.integrity=JS_SRI;s.crossOrigin='anonymous';
    s.onload=init;
    s.onerror=function(){
      if(i===0){var l=document.createElement('link');l.rel='stylesheet';l.href=CSS_ALT;l.integrity=CSS_SRI;l.crossOrigin='anonymous';document.head.appendChild(l);}
      load(i+1);
    };
    document.head.appendChild(s);
  }
  window.__lm={set:apply,recenter:function(){frame(true);}};
  load(0);
})();
</script></body></html>`;

// =====================================================
// Component
// =====================================================

export const LiveMap: React.FC<LiveMapProps> = ({
  markers,
  height = 220,
  followId,
  onPick,
  draggableId,
  openTargetId,
  caption,
  routePolyline,
  routePrecision = 6,
  onMarkerPress,
  hideRecenter = false,
  bare = false,
  refitKey,
  accessibilityLabel,
  style,
}) => {
  const { colors, isDark } = useTheme();
  const WebView = useMemo(loadWebView, []);
  const webRef = useRef<WebViewType>(null);
  const [ready, setReady] = useState(false);
  const [failed, setFailed] = useState(() => loadWebView() === null);
  const [webKey, setWebKey] = useState(0);
  const onPickRef = useRef(onPick);
  onPickRef.current = onPick;
  const onMarkerPressRef = useRef(onMarkerPress);
  onMarkerPressRef.current = onMarkerPress;

  // หน้า HTML สร้างครั้งเดียวต่อ WebView (เปลี่ยนธีมใช้ class ใน payload แทนการโหลดใหม่)
  const html = useMemo(
    () =>
      buildHtml(
        {
          background: isDark ? palette.clayDark : palette.clayLight,
          gold: palette.gold400,
          goldLight: palette.gold200,
          goldDeep: palette.gold600,
          success: palette.green500,
          info: palette.blue500,
          navy: palette.navy800,
          white: palette.clayLightCard,
          muted: palette.ink400,
          danger: palette.red500,
        },
        JSON.stringify(MARKER_SVGS)
      ),
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [webKey]
  );

  const validMarkers = useMemo(
    () =>
      markers.filter(
        (m) =>
          Number.isFinite(m.latitude) &&
          Number.isFinite(m.longitude) &&
          Math.abs(m.latitude) <= 90 &&
          Math.abs(m.longitude) <= 180 &&
          !(m.latitude === 0 && m.longitude === 0)
      ),
    [markers]
  );

  const routePoints = useMemo(
    () =>
      thinRoute(decodePolyline(routePolyline, routePrecision), MAX_ROUTE_POINTS).map(
        ([la, ln]) => [Number(la.toFixed(6)), Number(ln.toFixed(6))] as [number, number]
      ),
    [routePolyline, routePrecision]
  );

  const payload = JSON.stringify({
    markers: validMarkers.map((m) => ({
      id: m.id,
      lat: Number(m.latitude.toFixed(6)),
      lng: Number(m.longitude.toFixed(6)),
      kind: m.kind,
      label: m.label ? String(m.label).slice(0, 60) : null,
      photo: safePhotoUrl(m.photoUrl),
      hearts: typeof m.hearts === 'number' && Number.isFinite(m.hearts) ? Math.max(0, Math.round(m.hearts)) : null,
      radius: typeof m.radiusM === 'number' && Number.isFinite(m.radiusM) && m.radiusM > 0 ? Math.round(m.radiusM) : null,
      online: typeof m.online === 'boolean' ? m.online : null,
    })),
    route: routePoints,
    followId: followId || null,
    draggableId: draggableId || null,
    pickable: !!onPick,
    markerPress: !!onMarkerPress,
    refitKey: typeof refitKey === 'number' ? refitKey : null,
    dark: isDark,
  });

  // ส่งหมุดใหม่เข้าแผนที่เมื่อข้อมูลเปลี่ยน (ไม่โหลดหน้าใหม่)
  useEffect(() => {
    if (!ready || failed) return;
    webRef.current?.injectJavaScript(`window.__lm&&window.__lm.set(${payload});true;`);
  }, [ready, failed, payload]);

  // โหลดไม่ขึ้นในเวลาที่กำหนด → ใช้การ์ดสำรอง
  useEffect(() => {
    if (ready || failed) return undefined;
    const timer = setTimeout(() => setFailed(true), READY_TIMEOUT_MS);
    return () => clearTimeout(timer);
  }, [ready, failed, webKey]);

  const onMessage = useCallback((event: WebViewMessageEvent) => {
    let msg: { type?: unknown; lat?: unknown; lng?: unknown; href?: unknown; id?: unknown } | null = null;
    try {
      msg = JSON.parse(event.nativeEvent.data);
    } catch {
      return;
    }
    if (!msg || typeof msg.type !== 'string') return;
    switch (msg.type) {
      case 'ready':
        setReady(true);
        break;
      case 'error':
      case 'tiles-error':
        setFailed(true);
        break;
      case 'pick': {
        const lat = Number(msg.lat);
        const lng = Number(msg.lng);
        if (Number.isFinite(lat) && Number.isFinite(lng) && Math.abs(lat) <= 90 && Math.abs(lng) <= 180) {
          tapHaptic();
          onPickRef.current?.({ latitude: lat, longitude: lng });
        }
        break;
      }
      case 'marker': {
        if (typeof msg.id === 'string' && msg.id.length <= 64) {
          tapHaptic();
          onMarkerPressRef.current?.(msg.id);
        }
        break;
      }
      case 'link': {
        const href = typeof msg.href === 'string' ? msg.href : '';
        const host = /^https:\/\/([^/?#:]+)/i.exec(href)?.[1]?.toLowerCase();
        if (host && EXTERNAL_LINK_HOSTS.includes(host)) {
          WebBrowser.openBrowserAsync(href).catch(() => {});
        }
        break;
      }
      default:
        break;
    }
  }, []);

  const retry = () => {
    if (!WebView) return;
    setFailed(false);
    setReady(false);
    setWebKey((k) => k + 1);
  };

  const recenter = () => {
    tapHaptic();
    webRef.current?.injectJavaScript('window.__lm&&window.__lm.recenter();true;');
  };

  const target =
    openTargetId === null
      ? null
      : validMarkers.find((m) => m.id === openTargetId) || (openTargetId === undefined ? validMarkers[0] : undefined) || null;

  const a11y =
    accessibilityLabel ||
    `แผนที่: ${validMarkers.map((m) => m.label || MARKER_NAME[m.kind]).join(', ') || 'ยังไม่มีตำแหน่ง'}${
      routePoints.length > 1 ? ' พร้อมเส้นทาง' : ''
    }`;
  const hasContent = validMarkers.length > 0 || routePoints.length > 1;

  // กรอบนอกไม่ตั้ง accessible — ถ้าตั้ง iOS จะรวมลูกทั้งหมดเป็น element เดียว
  // แล้ว VoiceOver กดปุ่ม "ลองโหลดแผนที่อีกครั้ง" / ปุ่มจัดกล้องไม่ได้ → ตั้ง label ที่กลุ่มเนื้อหาข้างในแทน
  return (
    <View style={style}>
      <View
        style={
          bare
            ? [styles.frameBare, { height, backgroundColor: colors.inset }]
            : [
                styles.frame,
                { height, backgroundColor: colors.inset, borderColor: colors.border },
                clayShadowStyle('md', colors.shadowDark, colors.shadowLight),
              ]
        }
      >
        {failed || !WebView ? (
          <View style={styles.fallback}>
            <View style={styles.fallbackInfo} accessible accessibilityLabel={`${a11y} · แผนที่โหลดไม่ขึ้นตอนนี้`}>
              <Icon name="map-trifold" size={34} color={colors.textFaint} style={styles.fallbackIconSvg} />
              <Text style={[typography.bodyStrong, styles.center, { color: colors.textStrong }]}>แผนที่โหลดไม่ขึ้นตอนนี้</Text>
              {validMarkers.slice(0, 3).map((m) => (
                <Text key={m.id} numberOfLines={1} style={[typography.caption, styles.center, { color: colors.textMuted }]}>
                  {m.label || MARKER_NAME[m.kind]}
                </Text>
              ))}
            </View>
            {!!WebView && (
              <Pressable onPress={retry} accessibilityRole="button" hitSlop={8} style={styles.retry}>
                <Text style={[typography.caption, { color: colors.goldDeep }]}>ลองโหลดแผนที่อีกครั้ง</Text>
              </Pressable>
            )}
          </View>
        ) : (
          <>
            <View style={styles.web} accessible accessibilityLabel={a11y}>
              <WebView
                key={webKey}
                ref={webRef}
                source={{ html, baseUrl: MAP_BASE_URL }}
                originWhitelist={['https://*']}
                onShouldStartLoadWithRequest={(req) =>
                  req.url === 'about:blank' || req.url.startsWith(MAP_BASE_URL) || req.url === APP_INFO.WEBSITE
                }
                onMessage={onMessage}
                onError={() => setFailed(true)}
                onRenderProcessGone={() => setFailed(true)}
                // iOS ปิด WebContent process (แอปอยู่เบื้องหลังนาน) → โหลดใหม่ และต้องรีเซ็ต ready
                // ไม่งั้น 'ready' รอบใหม่ไม่เปลี่ยน state → หมุดไม่ถูก inject ซ้ำ แผนที่ค้างที่มุมมองเริ่มต้น
                onContentProcessDidTerminate={() => {
                  setReady(false);
                  setWebKey((k) => k + 1);
                }}
                javaScriptEnabled
                domStorageEnabled={false}
                allowFileAccess={false}
                allowsLinkPreview={false}
                setSupportMultipleWindows={false}
                javaScriptCanOpenWindowsAutomatically={false}
                mixedContentMode="never"
                scrollEnabled={false}
                bounces={false}
                overScrollMode="never"
                nestedScrollEnabled
                style={[styles.web, { backgroundColor: colors.inset }]}
              />
            </View>
            {!ready && (
              <View style={[StyleSheet.absoluteFill, styles.loading, { backgroundColor: colors.inset }]} pointerEvents="none">
                <ActivityIndicator color={colors.gold} />
                <Text style={[typography.caption, { color: colors.textMuted }]}>กำลังโหลดแผนที่…</Text>
              </View>
            )}
            {ready && hasContent && !hideRecenter && (
              <Pressable
                onPress={recenter}
                accessibilityRole="button"
                accessibilityLabel="จัดแผนที่ให้เห็นทุกหมุด"
                hitSlop={8}
                style={({ pressed }) => [
                  styles.recenter,
                  { backgroundColor: colors.card, opacity: pressed ? 0.75 : 1 },
                  clayShadowStyle('sm', colors.shadowDark, colors.shadowLight),
                ]}
              >
                <Icon name="crosshair" size={20} color={colors.navy} />
              </Pressable>
            )}
            {!hasContent && ready && (
              <View style={[styles.emptyOverlay, { backgroundColor: colors.card }]} pointerEvents="none">
                <Text style={[typography.caption, { color: colors.textMuted }]}>ยังไม่มีตำแหน่งให้แสดง</Text>
              </View>
            )}
          </>
        )}
      </View>

      {(!!caption || target) && (
        <View style={styles.footer}>
          {!!caption && (
            <Text style={[typography.micro, styles.flex, { color: colors.textFaint }]} numberOfLines={2}>
              {caption}
            </Text>
          )}
          {target && (
            <Button3D
              title="เปิดใน Google Maps"
              icon="navigation-arrow"
              size="sm"
              variant="secondary"
              onPress={() => openInGoogleMaps(target.latitude, target.longitude)}
              accessibilityLabel={`เปิดตำแหน่ง${target.label ? ` ${target.label}` : MARKER_NAME[target.kind]} ใน Google Maps`}
              style={!caption ? styles.alignEnd : undefined}
            />
          )}
        </View>
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  fallbackIconSvg: {
    alignSelf: 'center',
    marginBottom: 4,
  },
  frame: {
    borderRadius: radii.lg,
    overflow: 'hidden',
    borderWidth: 1,
  },
  frameBare: {
    overflow: 'hidden',
  },
  web: {
    flex: 1,
  },
  loading: {
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.sm,
  },
  fallback: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: spacing.lg,
    gap: spacing.xs,
  },
  fallbackInfo: {
    alignItems: 'center',
    gap: spacing.xs,
  },
  center: {
    textAlign: 'center',
  },
  retry: {
    marginTop: spacing.sm,
    minHeight: 32,
    justifyContent: 'center',
  },
  recenter: {
    position: 'absolute',
    top: spacing.sm,
    right: spacing.sm,
    width: 40,
    height: 40,
    borderRadius: radii.md,
    alignItems: 'center',
    justifyContent: 'center',
  },
  emptyOverlay: {
    position: 'absolute',
    left: spacing.md,
    bottom: spacing.md,
    borderRadius: radii.sm,
    paddingHorizontal: spacing.sm,
    paddingVertical: spacing.xs,
  },
  footer: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.sm,
  },
  flex: {
    flex: 1,
  },
  alignEnd: {
    marginLeft: 'auto',
  },
});

export default LiveMap;
