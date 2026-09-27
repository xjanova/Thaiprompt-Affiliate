{{--
 | ตัวเล่นคลิปแนะนำแอป (น้องพร้อม) แบบเต็มจอ — แอป Thai Prompt APP เปิดหน้านี้ใน WebView ตอนล็อกอินครั้งแรก
 |   (ไม่ต้องเพิ่ม native module ในแอป · เปลี่ยนคลิปได้จากตาราง settings โดยไม่ต้องออกแอปเวอร์ชันใหม่)
 | ส่งสัญญาณกลับแอปผ่าน window.ReactNativeWebView.postMessage: 'ended' (ดูจบ) · 'error' (โหลดไม่ได้)
 | เปิดจากเบราว์เซอร์ปกติก็ดูได้เหมือนกัน
 --}}
@php
    $ipVideo = (string) (\App\Models\Setting::get('intro_video_url') ?: asset('storage/videos/intro/thaiprompt-intro-v1.mp4'));
    $ipPoster = (string) (\App\Models\Setting::get('intro_video_poster') ?: asset('storage/videos/intro/intro_poster.jpg').'?v=2');
@endphp
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <title>คลิปแนะนำ Thai Prompt APP</title>
    <style>
        html, body { margin: 0; height: 100%; background: #05070c; overflow: hidden; }
        video { position: fixed; inset: 0; width: 100%; height: 100%; object-fit: contain; background: #05070c; }
    </style>
</head>
<body>
    <video id="v" src="{{ $ipVideo }}" poster="{{ $ipPoster }}" playsinline autoplay controls preload="auto"
           controlslist="nodownload noplaybackrate" disablepictureinpicture
           aria-label="คลิปแนะนำแอป Thai Prompt จากน้องพร้อม"></video>
    <script>
        (function () {
            var v = document.getElementById('v');
            function post(msg) {
                try { if (window.ReactNativeWebView) window.ReactNativeWebView.postMessage(msg); } catch (e) {}
            }
            v.addEventListener('ended', function () { post('ended'); });
            v.addEventListener('error', function () { post('error'); });
            // เล่นพร้อมเสียงก่อน ถ้าเครื่อง/เบราว์เซอร์ไม่ยอม → เล่นแบบปิดเสียง (กดเปิดเสียงที่แถบควบคุมได้)
            var p = v.play();
            if (p && p.catch) {
                p.catch(function () {
                    v.muted = true;
                    var q = v.play();
                    if (q && q.catch) q.catch(function () {});
                });
            }
        })();
    </script>
</body>
</html>
