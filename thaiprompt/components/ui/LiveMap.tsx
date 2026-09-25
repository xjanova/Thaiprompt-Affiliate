/**
 * LiveMap — แผนที่สดขนาดเล็ก (Leaflet + OpenStreetMap ใน WebView) ไม่ต้องใช้ API key
 *
 * - หมุดอัปเดตผ่าน injectJavaScript โดยไม่โหลดหน้าใหม่ (หมุดเลื่อนนุ่มๆ ไปตำแหน่งใหม่)
 * - โหลด Leaflet จาก unpkg (สำรอง jsDelivr) พร้อม SRI · แผนที่โหลดไม่ได้ → การ์ดสำรอง + ปุ่มเปิด Google Maps
 * - แสดงเครดิต © OpenStreetMap ตามเงื่อนไขการใช้ tile · ลิงก์ในแผนที่เปิดนอกแอปเฉพาะโดเมนที่อนุญาต
 * - โหมดปักหมุด: ส่ง onPick → แตะแผนที่/ลากหมุด draggableId แล้วได้พิกัดกลับมา
 *
 * ชื่อร้าน/ป้ายหมุดมาจาก server → ใส่ด้วย textContent เท่านั้น (ไม่ใช้ innerHTML กัน XSS)
 *
 * react-native-webview เป็น native module ใหม่ → โหลดแบบ lazy ใน try/catch
 * (แอปรุ่นเก่าที่ได้โค้ดผ่าน OTA แต่ไม่มี native module จะเห็นการ์ดสำรองแทน ไม่ล้มทั้งแอป)
 *
 * @example
 * <LiveMap
 *   markers={[{ id: 'shop', kind: 'shop', latitude: 13.75, longitude: 100.5, label: 'ครัวไทยพร้อม' }]}
 *   height={200}
 * />
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { ActivityIndicator, Linking, Pressable, StyleSheet, Text, View, type StyleProp, type ViewStyle } from 'react-native';
import type { WebView as WebViewType, WebViewMessageEvent } from 'react-native-webview';
import * as WebBrowser from 'expo-web-browser';
import { APP_INFO } from '@/config/appConfig';
import { useTheme, radii, spacing, typography, clayShadowStyle, palette } from '@/theme';
import { Button3D } from './Button3D';
import { tapHaptic } from './haptics';

export type LiveMapMarkerKind = 'shop' | 'rider' | 'me' | 'home' | 'pin';

export interface LiveMapMarker {
  id: string;
  latitude: number;
  longitude: number;
  kind: LiveMapMarkerKind;
  /** ป้ายเล็กเหนือหมุด (ข้อความล้วน) */
  label?: string | null;
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
const EXTERNAL_LINK_HOSTS = ['www.openstreetmap.org', 'openstreetmap.org', 'leafletjs.com', 'osmfoundation.org', 'wiki.osmfoundation.org'];

const MARKER_EMOJI: Record<LiveMapMarkerKind, string> = {
  shop: '🏪',
  rider: '🛵',
  me: '🧍',
  home: '🏠',
  pin: '📍',
};

const MARKER_NAME: Record<LiveMapMarkerKind, string> = {
  shop: 'ร้าน',
  rider: 'ไรเดอร์',
  me: 'คุณ',
  home: 'จุดส่ง',
  pin: 'หมุดที่เลือก',
};

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

const buildHtml = (background: string, gold: string, success: string, info: string): string => `<!DOCTYPE html>
<html><head><meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no"/>
<link id="lcss" rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous"/>
<style>
html,body,#map{margin:0;padding:0;height:100%;width:100%;background:${background};}
body{-webkit-tap-highlight-color:transparent;font-family:-apple-system,Roboto,sans-serif;}
.lm{display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:18px;background:#fffdf7;border:3px solid ${gold};box-shadow:0 3px 8px rgba(0,0,0,.35);font-size:19px;line-height:1;}
.lm-rider{border-color:${success};}
.lm-me,.lm-home{border-color:${info};}
.lm-rider:after{content:'';position:absolute;width:36px;height:36px;border-radius:18px;border:2px solid ${success};animation:p 1.8s ease-out infinite;}
@keyframes p{0%{transform:scale(1);opacity:.8}100%{transform:scale(1.9);opacity:0}}
.leaflet-tooltip{font-size:12px;font-weight:600;padding:2px 6px;border-radius:8px;}
.leaflet-control-attribution{font-size:10px;}
body.dark .leaflet-tile-pane{filter:invert(1) hue-rotate(180deg) brightness(.92) contrast(.9);}
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
  var EMOJI={shop:'\\uD83C\\uDFEA',rider:'\\uD83D\\uDEF5',me:'\\uD83E\\uDDCD',home:'\\uD83C\\uDFE0',pin:'\\uD83D\\uDCCD'};
  var map=null,layers={},targets={},fitted=false,opts={},pending=null;
  function icon(kind){
    var k=EMOJI[kind]?kind:'pin';
    return L.divIcon({className:'',html:'<div class="lm lm-'+k+'">'+EMOJI[k]+'</div>',iconSize:[36,36],iconAnchor:[18,18],tooltipAnchor:[0,-18]});
  }
  function textEl(s){var el=document.createElement('span');el.textContent=String(s);return el;}
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
  function frame(force){
    var ids=Object.keys(targets);
    if(!map||!ids.length)return;
    var pts=ids.map(function(id){return targets[id];});
    if(!fitted||force){
      fitted=true;
      if(pts.length===1){map.setView(pts[0],16);}else{map.fitBounds(L.latLngBounds(pts),{padding:[40,40],maxZoom:17});}
      return;
    }
    var view=map.getBounds().pad(-0.12);
    if(opts.followId&&targets[opts.followId]){
      if(!view.contains(targets[opts.followId]))map.panTo(targets[opts.followId]);
      return;
    }
    for(var i=0;i<pts.length;i++){
      if(!view.contains(pts[i])){
        if(pts.length===1){map.panTo(pts[0]);}else{map.fitBounds(L.latLngBounds(pts),{padding:[40,40],maxZoom:17});}
        return;
      }
    }
  }
  function apply(p){
    if(!map){pending=p;return;}
    opts=p||{};
    document.body.className=opts.dark?'dark':'';
    var seen={};
    (opts.markers||[]).forEach(function(mk){
      if(!mk||typeof mk.lat!=='number'||typeof mk.lng!=='number'||!isFinite(mk.lat)||!isFinite(mk.lng))return;
      var id=String(mk.id);seen[id]=1;targets[id]=[mk.lat,mk.lng];
      var m=layers[id];
      var drag=opts.draggableId===id;
      if(!m){
        m=L.marker([mk.lat,mk.lng],{icon:icon(mk.kind),draggable:drag,keyboard:false,autoPan:drag});
        m._kind=mk.kind;m._drag=drag;
        if(drag){m.on('dragend',function(){var ll=m.getLatLng();targets[id]=[ll.lat,ll.lng];post({type:'pick',lat:ll.lat,lng:ll.lng});});}
        m.addTo(map);layers[id]=m;
      }else{
        if(m._kind!==mk.kind){m.setIcon(icon(mk.kind));m._kind=mk.kind;}
        moveTo(m,mk.lat,mk.lng);
      }
      if(mk.label){
        if(m.getTooltip()){m.setTooltipContent(textEl(mk.label));}
        else{m.bindTooltip(textEl(mk.label),{direction:'top',permanent:!!opts.permanentLabels});}
      }else if(m.getTooltip()){m.unbindTooltip();}
    });
    Object.keys(layers).forEach(function(id){if(!seen[id]){map.removeLayer(layers[id]);delete layers[id];delete targets[id];}});
    frame(opts.refit===true);
  }
  function init(){
    if(!window.L){post({type:'error',reason:'leaflet'});return;}
    try{
      map=L.map('map',{zoomControl:false,attributionControl:true});
      map.attributionControl.setPrefix('<a href="https://leafletjs.com">Leaflet</a>');
      var tl=L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'});
      var tileErrors=0,tileOk=0;
      tl.on('tileerror',function(){tileErrors++;if(tileErrors===8&&tileOk===0)post({type:'tiles-error'});});
      tl.on('tileload',function(){tileOk++;});
      tl.addTo(map);
      map.setView([13.7563,100.5018],12);
      map.on('click',function(e){if(opts.pickable)post({type:'pick',lat:e.latlng.lat,lng:e.latlng.lng});});
      window.addEventListener('resize',function(){map&&map.invalidateSize();});
      document.addEventListener('click',function(ev){
        var t=ev.target,a=t&&t.closest?t.closest('a'):null;
        if(a&&a.href){ev.preventDefault();ev.stopPropagation();post({type:'link',href:a.href});}
      },true);
      post({type:'ready'});
      if(pending){var p=pending;pending=null;apply(p);}
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

  // หน้า HTML สร้างครั้งเดียวต่อ WebView (เปลี่ยนธีมใช้ class ใน payload แทนการโหลดใหม่)
  const html = useMemo(
    () => buildHtml(isDark ? palette.clayDark : palette.clayLight, palette.gold400, palette.green500, palette.blue500),
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

  const payload = JSON.stringify({
    markers: validMarkers.map((m) => ({
      id: m.id,
      lat: Number(m.latitude.toFixed(6)),
      lng: Number(m.longitude.toFixed(6)),
      kind: m.kind,
      label: m.label ? String(m.label).slice(0, 60) : null,
    })),
    followId: followId || null,
    draggableId: draggableId || null,
    pickable: !!onPick,
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
    let msg: { type?: unknown; lat?: unknown; lng?: unknown; href?: unknown } | null = null;
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
    `แผนที่: ${validMarkers.map((m) => m.label || MARKER_NAME[m.kind]).join(', ') || 'ยังไม่มีตำแหน่ง'}`;

  // กรอบนอกไม่ตั้ง accessible — ถ้าตั้ง iOS จะรวมลูกทั้งหมดเป็น element เดียว
  // แล้ว VoiceOver กดปุ่ม "ลองโหลดแผนที่อีกครั้ง" / 🎯 ไม่ได้ → ตั้ง label ที่กลุ่มเนื้อหาข้างในแทน
  return (
    <View style={style}>
      <View
        style={[
          styles.frame,
          { height, backgroundColor: colors.inset, borderColor: colors.border },
          clayShadowStyle('sm', colors.shadowDark, colors.shadowLight),
        ]}
      >
        {failed || !WebView ? (
          <View style={styles.fallback}>
            <View style={styles.fallbackInfo} accessible accessibilityLabel={`${a11y} · แผนที่โหลดไม่ขึ้นตอนนี้`}>
              <Text style={styles.fallbackIcon}>🗺️</Text>
              <Text style={[typography.bodyStrong, styles.center, { color: colors.textStrong }]}>แผนที่โหลดไม่ขึ้นตอนนี้</Text>
              {validMarkers.slice(0, 3).map((m) => (
                <Text key={m.id} numberOfLines={1} style={[typography.caption, styles.center, { color: colors.textMuted }]}>
                  {MARKER_EMOJI[m.kind]} {m.label || MARKER_NAME[m.kind]}
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
            {ready && validMarkers.length > 0 && (
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
                <Text style={styles.recenterIcon}>🎯</Text>
              </Pressable>
            )}
            {validMarkers.length === 0 && ready && (
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
              icon="🧭"
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
  frame: {
    borderRadius: radii.lg,
    overflow: 'hidden',
    borderWidth: 1,
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
  fallbackIcon: {
    fontSize: 30,
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
  recenterIcon: {
    fontSize: 18,
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
