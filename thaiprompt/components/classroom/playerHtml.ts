/**
 * หน้า HTML ของเครื่องเล่นบทเรียน (YouTube IFrame API ใน react-native-webview)
 *
 * - โหลดด้วย source={{ html, baseUrl: เว็บของเรา (https) }} — YouTube ต้องรู้ต้นทาง ไม่งั้นปฏิเสธการฝัง (Error 153/150)
 *   และส่ง origin เดียวกันใน playerVars
 * - controls=0 + disablekb=1 → ไม่มีแถบเลื่อนของ YouTube · ปุ่มเล่น/หยุด/ย้อน 10 วิ อยู่ฝั่งแอป (ส่งคำสั่งผ่าน window.__lesson)
 * - กันข้ามไปข้างหน้า: เลื่อนเกินจุดไกลสุดที่ดูแล้ว (+2 วิ) → ดึงกลับ (server ก็ไม่นับเวลาที่ข้ามอยู่แล้ว)
 * - ส่งข้อความกลับแอป (JSON): ready · state(s) · time(pos,dur,max) · seekBlocked · error(code)
 *
 * ค่าที่ใส่ลงหน้า: รหัสวิดีโอผ่าน safeYoutubeId (11 ตัว) แล้ว + JSON.stringify ทุกค่า — ไม่ประกอบ HTML จากข้อมูลดิบ
 */

import { safeYoutubeId } from '@/services/api/classroomApi';

export interface PlayerHtmlOptions {
  videoId: string;
  /** เริ่มเล่นที่วินาที */
  startSeconds: number;
  /** จุดไกลสุดที่เลื่อนไปได้ (วินาที) */
  maxAllowedSeconds: number;
  /** ต้นทางของหน้า (ตรงกับ baseUrl) เช่น https://main.thaiprompt.online */
  origin: string;
}

/** ข้อความจากหน้าเล่นวิดีโอ */
export type PlayerMessage =
  | { t: 'ready'; dur: number }
  | { t: 'state'; s: number; pos: number }
  | { t: 'time'; pos: number; dur: number; max: number; s: number }
  | { t: 'seekBlocked'; max: number }
  | { t: 'error'; code: string };

/** สถานะของ YouTube player (YT.PlayerState) */
export const YT_STATE = {
  UNSTARTED: -1,
  ENDED: 0,
  PLAYING: 1,
  PAUSED: 2,
  BUFFERING: 3,
  CUED: 5,
} as const;

/** แปลงข้อความจาก WebView (ไม่ใช่ JSON/รูปแบบแปลก = null) */
export const parsePlayerMessage = (data: unknown): PlayerMessage | null => {
  if (typeof data !== 'string' || data.length > 2000) return null;
  try {
    const msg = JSON.parse(data);
    if (!msg || typeof msg !== 'object' || typeof msg.t !== 'string') return null;
    const n = (v: unknown): number => (typeof v === 'number' && Number.isFinite(v) ? v : 0);
    switch (msg.t) {
      case 'ready':
        return { t: 'ready', dur: n(msg.dur) };
      case 'state':
        return { t: 'state', s: n(msg.s), pos: n(msg.pos) };
      case 'time':
        return { t: 'time', pos: n(msg.pos), dur: n(msg.dur), max: n(msg.max), s: n(msg.s) };
      case 'seekBlocked':
        return { t: 'seekBlocked', max: n(msg.max) };
      case 'error':
        return { t: 'error', code: typeof msg.code === 'string' ? msg.code.slice(0, 40) : 'unknown' };
      default:
        return null;
    }
  } catch {
    return null;
  }
};

/** คำสั่งที่แอปส่งเข้าไป (injectJavaScript) */
export const playerCommand = (cmd: 'play' | 'pause' | 'back10' | 'restart'): string =>
  `try{window.__lesson&&window.__lesson(${JSON.stringify(cmd)})}catch(e){}true;`;

/**
 * สร้างหน้า HTML ของเครื่องเล่น — รหัสวิดีโอไม่ถูกรูปแบบ = null (ห้ามเล่น)
 */
export const buildPlayerHtml = (options: PlayerHtmlOptions): string | null => {
  const videoId = safeYoutubeId(options.videoId);
  if (!videoId) return null;
  const start = Math.max(0, Math.floor(Number.isFinite(options.startSeconds) ? options.startSeconds : 0));
  const maxAllowed = Math.max(start, Number.isFinite(options.maxAllowedSeconds) ? options.maxAllowedSeconds : start);
  const origin = /^https:\/\/[A-Za-z0-9.-]+(:\d+)?$/.test(options.origin) ? options.origin : 'https://main.thaiprompt.online';
  const cfg = JSON.stringify({ videoId, start, maxAllowed, origin });

  return `<!DOCTYPE html>
<html><head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<style>
html,body{margin:0;padding:0;height:100%;background:#000;overflow:hidden}
#p,#p iframe{position:absolute;top:0;left:0;width:100%;height:100%;border:0}
</style>
</head><body>
<div id="p"></div>
<script>
(function(){
  var CFG=${cfg};
  var RN=window.ReactNativeWebView;
  function post(o){try{RN&&RN.postMessage(JSON.stringify(o))}catch(e){}}
  var player=null,ready=false,maxAllowed=CFG.maxAllowed,lastState=-1;
  function pos(){try{return player.getCurrentTime()||0}catch(e){return 0}}
  function dur(){try{return player.getDuration()||0}catch(e){return 0}}
  function st(){try{return player.getPlayerState()}catch(e){return -1}}
  function guard(){
    if(!ready)return;
    var t=pos(),s=st();
    if(t>maxAllowed+2){player.seekTo(maxAllowed,true);post({t:'seekBlocked',max:maxAllowed});t=maxAllowed;}
    else if(s===1&&t>maxAllowed){maxAllowed=t;}
    post({t:'time',pos:t,dur:dur(),max:maxAllowed,s:s});
  }
  window.__lesson=function(cmd){
    if(!ready)return;
    if(cmd==='play')player.playVideo();
    else if(cmd==='pause')player.pauseVideo();
    else if(cmd==='back10'){player.seekTo(Math.max(0,pos()-10),true);}
    else if(cmd==='restart'){player.seekTo(0,true);player.playVideo();}
  };
  window.onYouTubeIframeAPIReady=function(){
    try{
      player=new YT.Player('p',{
        videoId:CFG.videoId,
        playerVars:{playsinline:1,controls:0,disablekb:1,fs:0,rel:0,modestbranding:1,iv_load_policy:3,start:CFG.start,origin:CFG.origin,enablejsapi:1},
        events:{
          onReady:function(){ready=true;post({t:'ready',dur:dur()});setInterval(guard,1000);},
          onStateChange:function(e){lastState=e.data;post({t:'state',s:e.data,pos:pos()});},
          onError:function(e){post({t:'error',code:'yt_'+(e&&e.data)});}
        }
      });
    }catch(e){post({t:'error',code:'init'});}
  };
  var tag=document.createElement('script');
  tag.src='https://www.youtube.com/iframe_api';
  tag.onerror=function(){post({t:'error',code:'api'});};
  document.head.appendChild(tag);
  setTimeout(function(){if(!ready)post({t:'error',code:'timeout'});},20000);
})();
</script>
</body></html>`;
};

/** ข้อความไทยของ error จากเครื่องเล่น */
export const playerErrorMessage = (code: string): string => {
  if (code === 'yt_101' || code === 'yt_150' || code === 'yt_153') return 'วิดีโอนี้เปิดในแอปไม่ได้ตอนนี้ แจ้งทีมงานได้ที่หน้าช่วยเหลือนะ';
  if (code === 'yt_100') return 'ไม่พบวิดีโอของบทเรียนนี้ แจ้งทีมงานได้ที่หน้าช่วยเหลือนะ';
  if (code === 'api' || code === 'timeout') return 'โหลดวิดีโอไม่สำเร็จ ตรวจสอบอินเทอร์เน็ตแล้วลองใหม่นะ';
  return 'เล่นวิดีโอไม่สำเร็จ ลองใหม่อีกครั้งนะ';
};
