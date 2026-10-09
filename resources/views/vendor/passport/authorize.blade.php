{{--
    🪪 (2026-10-09) หน้าขออนุญาตของ OAuth — ทับ view ของ Passport (vendor เป็นภาษาอังกฤษ + พึ่ง /css/app.css ที่เราไม่มี)

    ใช้กับ client ที่ skip_authorization = false เท่านั้น (ตอนนี้ = TPIX TRADE)
    จันทรา skip consent อยู่แล้ว ไม่เคยเห็นหน้านี้

    ⚠️ ฟอร์มต้องส่ง state / client_id / auth_token ไปที่ passport.authorizations.{approve,deny}
       ตามแบบ vendor เป๊ะ — ขาดตัวใดตัวหนึ่ง Passport ปฏิเสธทั้งคำขอ
    ตัวแปรจาก Passport: $client, $user, $scopes, $request, $authToken
--}}
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>อนุญาตให้ {{ $client->name }} เข้าถึงข้อมูล — Thaiprompt</title>
    <style>
        :root { --bg:#f4f6fb; --card:#fff; --ink:#1f2937; --muted:#6b7280; --line:#e5e7eb; --brand:#16a34a; --brand-ink:#fff; --deny:#f3f4f6; }
        @media (prefers-color-scheme: dark) {
            :root { --bg:#0f172a; --card:#111827; --ink:#f3f4f6; --muted:#9ca3af; --line:#1f2937; --deny:#1f2937; }
        }
        * { box-sizing: border-box; }
        body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:16px;
               background:var(--bg); color:var(--ink); font-family: "Noto Sans Thai", "Sarabun", system-ui, -apple-system, "Segoe UI", sans-serif; }
        .card { width:100%; max-width:440px; background:var(--card); border:1px solid var(--line); border-radius:20px; padding:28px 24px; box-shadow:0 12px 32px -16px rgba(0,0,0,.25); }
        .brand { font-weight:700; color:var(--brand); letter-spacing:.2px; margin-bottom:18px; }
        h1 { font-size:20px; line-height:1.4; margin:0 0 6px; }
        .sub { color:var(--muted); font-size:14px; margin:0 0 20px; line-height:1.6; }
        .box { border:1px solid var(--line); border-radius:14px; padding:14px 16px; margin-bottom:16px; }
        .box h2 { font-size:13px; color:var(--muted); font-weight:600; margin:0 0 8px; }
        ul { margin:0; padding-left:20px; line-height:1.7; font-size:15px; }
        .note { font-size:13px; color:var(--muted); line-height:1.6; margin:0 0 20px; }
        .who { font-size:13px; color:var(--muted); margin-bottom:20px; }
        .who b { color:var(--ink); }
        .actions { display:flex; gap:10px; }
        .actions form { flex:1; margin:0; }
        button { width:100%; border:0; border-radius:12px; padding:13px 14px; font-size:15px; font-weight:600; cursor:pointer; font-family:inherit; }
        .approve { background:var(--brand); color:var(--brand-ink); }
        .deny { background:var(--deny); color:var(--ink); }
        button:focus-visible { outline:3px solid #86efac; outline-offset:2px; }
    </style>
</head>
<body>
    <main class="card">
        <div class="brand">Thaiprompt</div>

        <h1><strong>{{ $client->name }}</strong> ขอใช้ข้อมูลจากบัญชี Thaiprompt ของคุณ</h1>
        <p class="sub">กดอนุญาตเพื่อใช้ผลการยืนยันตัวตนที่ทำไว้กับ Thaiprompt โดยไม่ต้องส่งเอกสารซ้ำ</p>

        @if (count($scopes) > 0)
            <div class="box">
                <h2>สิ่งที่แอปนี้จะเห็น</h2>
                <ul>
                    @foreach ($scopes as $scope)
                        <li>{{ $scope->description }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <p class="note">
            เลขบัตรประชาชน ชื่อ วันเกิด และรูปถ่ายของคุณจะไม่ถูกส่งไป
            ยกเลิกการอนุญาตได้ทุกเมื่อโดยติดต่อทีมงาน Thaiprompt
        </p>

        <div class="who">
            เข้าสู่ระบบเป็น <b>{{ $user->name ?: ('บัญชี #'.$user->getKey()) }}</b>
        </div>

        <div class="actions">
            <form method="post" action="{{ route('passport.authorizations.deny') }}">
                @csrf
                @method('DELETE')
                <input type="hidden" name="state" value="{{ $request->state }}">
                <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button type="submit" class="deny">ไม่อนุญาต</button>
            </form>

            <form method="post" action="{{ route('passport.authorizations.approve') }}">
                @csrf
                <input type="hidden" name="state" value="{{ $request->state }}">
                <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button type="submit" class="approve">อนุญาต</button>
            </form>
        </div>
    </main>
</body>
</html>
