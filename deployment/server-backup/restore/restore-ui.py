#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
♻️ หน้าเว็บกู้คืนทั้งเครื่องจากชุดสำรอง R2 — 2026-11-08

เจ้าของสั่ง: "ทำเป็นเว็บสคริปรีสโตร์แบบกราฟิกใช้ง่ายๆ แค่ติดตั้งก็รีสโตร์ได้เลย"

- ใช้แค่ Python มาตรฐาน (มากับ Ubuntu) ไม่ต้องลงไลบรารีเพิ่ม
- HTTPS (ใบรับรองชั่วคราวที่ install-restore.sh สร้าง) + โทเค็นลับในลิงก์ — ลูกกุญแจ/คีย์ R2 ไม่วิ่งเป็นข้อความเปล่า
- ลูกกุญแจและคีย์ R2 เก็บใน /run/server-restore (RAM, root 600) — ปุ่ม "ล้างกุญแจและปิด" ลบทิ้งทันที
- งานจริงทำโดย server-restore.sh ทีละขั้น หน้าเว็บแค่สั่งและแสดง log
"""
import argparse
import hmac
import http.server
import json
import os
import re
import secrets
import shlex
import ssl
import subprocess
import sys
import threading
import time
import urllib.parse

STEP_INFO = {
    "prepare": ("เตรียมเครื่อง", "ลงเครื่องมือพื้นฐาน ตั้งชื่อเครื่อง/เขตเวลาตามเดิม เก็บสำเนา /etc เดิมไว้เทียบ"),
    "packages": ("ติดตั้งแพ็กเกจ", "คืนแหล่งแพ็กเกจ แล้วติดตั้งทุกแพ็กเกจที่เครื่องเดิมลงไว้ (+ npm global)"),
    "directadmin": ("DirectAdmin", "ติดตั้ง DirectAdmin + build PHP/เว็บเซิร์ฟเวอร์/MariaDB ตามตัวเลือกเดิม (ใช้ License key, 20-60 นาที)"),
    "users": ("ผู้ใช้ระบบ", "สร้างผู้ใช้/กลุ่มเดิม uid เดิม รหัสผ่านเดิม"),
    "config": ("config + สคริปต์", "คืน /root, /usr/local, /opt, cron, ข้อมูล DirectAdmin (ผู้ใช้ โดเมน SSL DNS)"),
    "etc": ("ตั้งค่า /etc", "คืน nginx/cron/systemd/firewall/sudoers/ssl ฯลฯ — ไม่แตะเครือข่ายและดิสก์ของเครื่องใหม่"),
    "homes": ("home ผู้ใช้", "อีเมล สคริปต์ และงานที่อยู่นอกเว็บ"),
    "sites": ("ทุกเว็บไซต์", "ไฟล์เว็บทุกโดเมน (รวมไฟล์อัปโหลด สลิป ฯลฯ)"),
    "databases": ("ฐานข้อมูล", "ผู้ใช้/สิทธิ์ MySQL แล้วทุกฐานข้อมูล"),
    "docker-images": ("docker image", "โหลด image ที่ build เอง + ดึง image ที่เหลือจาก registry"),
    "tpix-chain": ("ข้อมูลเชน TPIX", "ดึงข้อมูลบล็อกล่าสุดจาก bucket tpix-chain-backup ลง validator 1-4 + bootnode"),
    "docker-up": ("เปิด container", "คืนฐาน Blockscout แล้วเปิดทุก docker compose ตามโฟลเดอร์เดิม"),
    "services": ("เปิด service", "เปิด/สตาร์ต service ตามเครื่องเดิม + pm2 + rewrite_confs ของ DirectAdmin"),
    "verify": ("ตรวจผล", "ยิงทุกโดเมนในเครื่อง, ดู container, นับฐานข้อมูล, service ที่ล้ม"),
}
SERVER_RE = re.compile(r"^server-[a-z0-9-]{1,60}$")
SNAP_RE = re.compile(r"^(daily|full)/[a-z0-9-]{1,20}$")


class State:
    def __init__(self, args):
        self.args = args
        self.lock = threading.Lock()
        self.running = False
        self.current = ""
        self.results = {}
        self.cancel = False
        self.log_path = os.path.join(args.session, "run.log")


def write_secret(path, text):
    """เขียนไฟล์ 600 ตั้งแต่สร้าง (ไม่ใช่ chmod ทีหลัง)"""
    fd = os.open(path, os.O_WRONLY | os.O_CREAT | os.O_TRUNC, 0o600)
    with os.fdopen(fd, "w") as f:
        f.write(text)


def engine(st, *argv, timeout=600):
    env = dict(os.environ, SERVER_RESTORE_SESSION=st.args.session)
    p = subprocess.run(["bash", st.args.engine, *argv], capture_output=True, text=True, env=env, timeout=timeout)
    return p.returncode, p.stdout, p.stderr


def run_steps(st, server, snap, steps):
    env = dict(os.environ, SERVER_RESTORE_SESSION=st.args.session)
    with open(st.log_path, "a", encoding="utf-8") as log:
        log.write(f"\n════ เริ่มกู้ {server} / {snap} — {time.strftime('%F %T')} ════\n")
        for s in steps:
            if st.cancel:
                log.write("⏹ หยุดตามที่สั่ง\n")
                break
            st.current = s
            st.results[s] = "running"
            log.write(f"\n▶ ขั้น {STEP_INFO.get(s, (s,))[0]} ({s})\n")
            log.flush()
            p = subprocess.run(["bash", st.args.engine, "step", s, server, snap],
                               stdout=log, stderr=subprocess.STDOUT, env=env)
            st.results[s] = "ok" if p.returncode == 0 else "fail"
            if p.returncode != 0:
                log.write(f"✖ ขั้น {s} ล้ม (exit {p.returncode}) — แก้แล้วกดเริ่มใหม่จากขั้นนี้ได้\n")
                break
        log.write(f"════ จบ {time.strftime('%F %T')} ════\n")
    st.current = ""
    st.running = False


class Handler(http.server.BaseHTTPRequestHandler):
    server_version = "server-restore"

    def log_message(self, fmt, *a):  # ไม่ log คำขอ (มีคีย์ใน body)
        pass

    @property
    def st(self):
        return self.server.state

    def authed(self):
        c = self.headers.get("Cookie", "")
        m = re.search(r"(?:^|;\s*)srt=([0-9a-f]+)", c)
        return bool(m) and hmac.compare_digest(m.group(1), self.server.token)

    def send(self, code, body, ctype="application/json; charset=utf-8", extra=None):
        data = body if isinstance(body, bytes) else (json.dumps(body, ensure_ascii=False) if not isinstance(body, str) else body).encode()
        self.send_response(code)
        self.send_header("Content-Type", ctype)
        self.send_header("Content-Length", str(len(data)))
        self.send_header("Cache-Control", "no-store")
        self.send_header("X-Frame-Options", "DENY")
        self.send_header("Content-Security-Policy", "default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'")
        for k, v in (extra or {}).items():
            self.send_header(k, v)
        self.end_headers()
        self.wfile.write(data)

    def body(self):
        n = int(self.headers.get("Content-Length", "0") or 0)
        if n > 64 * 1024:
            raise ValueError("ข้อมูลใหญ่เกิน")
        return json.loads(self.rfile.read(n) or b"{}")

    def do_GET(self):
        u = urllib.parse.urlparse(self.path)
        q = urllib.parse.parse_qs(u.query)
        if u.path == "/":
            t = (q.get("t") or [""])[0]
            if t and hmac.compare_digest(t, self.server.token):
                return self.send(303, "", "text/plain", {
                    "Location": "/",
                    "Set-Cookie": f"srt={self.server.token}; Path=/; HttpOnly; Secure; SameSite=Strict"})
            if not self.authed():
                return self.send(403, "<meta charset=utf-8><p style='font:16px sans-serif;padding:2em'>ลิงก์ไม่ถูกต้อง — ใช้ลิงก์ที่ install-restore.sh พิมพ์ให้ (มี ?t=…)</p>", "text/html; charset=utf-8")
            with open(os.path.join(os.path.dirname(os.path.abspath(__file__)), "restore-ui.html"), "rb") as f:
                return self.send(200, f.read(), "text/html; charset=utf-8")
        if not self.authed():
            return self.send(403, {"error": "ต้องเปิดจากลิงก์ที่มีโทเค็น"})
        st = self.st
        if u.path == "/api/state":
            return self.send(200, {
                "connected": os.path.exists(os.path.join(st.args.session, "private.pem")),
                "running": st.running, "current": st.current, "results": st.results,
                "test_mode": bool(st.args.target_root and st.args.target_root != "/"),
                "source_dir": st.args.source_dir or "", "steps": STEP_INFO})
        if u.path == "/api/snapshots":
            server = (q.get("server") or [""])[0]
            if not SERVER_RE.match(server):
                return self.send(400, {"error": "ชื่อเครื่องไม่ถูกต้อง"})
            rc, out, err = engine(st, "snapshots", server)
            snaps = []
            for line in out.splitlines():
                path, _, info = line.partition("\t")
                d = dict(kv.split("=", 1) for kv in info.split("|") if "=" in kv)
                snaps.append({"slot": path, **d})
            snaps.sort(key=lambda s: s.get("finished", ""), reverse=True)
            return self.send(200 if rc == 0 else 500, {"snapshots": snaps, "error": err.strip()[-500:]})
        if u.path == "/api/log":
            try:
                frm = int((q.get("from") or ["0"])[0])
            except ValueError:
                frm = 0
            text = ""
            if os.path.exists(st.log_path):
                with open(st.log_path, "rb") as f:
                    f.seek(frm)
                    chunk = f.read(256 * 1024)
                    text = chunk.decode("utf-8", "replace")
                    frm += len(chunk)
            return self.send(200, {"text": text, "next": frm, "running": st.running,
                                   "current": st.current, "results": st.results})
        return self.send(404, {"error": "ไม่พบ"})

    def do_POST(self):
        if not self.authed():
            return self.send(403, {"error": "ต้องเปิดจากลิงก์ที่มีโทเค็น"})
        st = self.st
        try:
            b = self.body()
        except Exception as e:  # noqa: BLE001
            return self.send(400, {"error": f"อ่านข้อมูลไม่ได้: {e}"})
        u = urllib.parse.urlparse(self.path).path
        sess = st.args.session

        if u == "/api/connect":
            if st.running:
                return self.send(409, {"error": "กำลังกู้อยู่"})
            pem = (b.get("private_key") or "").strip()
            if "-----BEGIN" not in pem or "PRIVATE KEY-----" not in pem:
                return self.send(400, {"error": "ลูกกุญแจต้องเป็นไฟล์ backup-private.pem (ขึ้นต้น -----BEGIN … PRIVATE KEY-----)"})
            acct = (b.get("account_id") or "").strip().lower()
            kid = (b.get("key_id") or "").strip()
            sec = (b.get("secret") or "").strip()
            bucket = (b.get("bucket") or "thaiprompt-backup").strip()
            if not st.args.source_dir:
                if not re.fullmatch(r"[0-9a-f]{32}", acct):
                    return self.send(400, {"error": "Account ID ต้องเป็นตัวอักษร 0-9a-f 32 ตัว"})
                if not kid or not sec:
                    return self.send(400, {"error": "ใส่ Access Key ID และ Secret Access Key"})
                if not re.fullmatch(r"[a-z0-9][a-z0-9.-]{1,61}[a-z0-9]", bucket):
                    return self.send(400, {"error": "ชื่อ bucket ไม่ถูกต้อง"})
            write_secret(os.path.join(sess, "private.pem"), pem + "\n")
            lines = {
                "PRIVATE_KEY": os.path.join(sess, "private.pem"),
                "R2_ACCOUNT_ID": acct, "R2_ACCESS_KEY_ID": kid, "R2_SECRET_ACCESS_KEY": sec, "R2_BUCKET": bucket,
                "TARGET_ROOT": st.args.target_root or "/", "SOURCE_DIR": st.args.source_dir or "",
            }
            write_secret(os.path.join(sess, "session.env"),
                         "".join(f"{k}={shlex.quote(v)}\n" for k, v in lines.items()))
            rc, fp, err = engine(st, "fingerprint")
            if rc != 0 or not fp.strip():
                os.remove(os.path.join(sess, "private.pem"))
                return self.send(400, {"error": "อ่านลูกกุญแจไม่ได้ (ไฟล์เสียหรือมีรหัสผ่าน)"})
            rc, out, err = engine(st, "servers")
            servers = [s for s in out.split() if SERVER_RE.match(s)]
            if not servers:
                return self.send(400, {"error": "ต่อ R2 ไม่ได้ หรือไม่มีชุดสำรองใน bucket นี้ — ตรวจ Account ID / คีย์ / IP ที่ token อนุญาต",
                                       "detail": err.strip()[-400:]})
            return self.send(200, {"fingerprint": fp.strip(), "servers": servers})

        if u == "/api/plan":
            server, snap = b.get("server", ""), b.get("snapshot", "")
            if not SERVER_RE.match(server) or not SNAP_RE.match(snap):
                return self.send(400, {"error": "เลือกเครื่อง/ชุดไม่ถูกต้อง"})
            rc, out, err = engine(st, "plan", server, snap, timeout=900)
            if rc != 0:
                return self.send(500, {"error": "อ่านชุดสำรองไม่ได้ (ลูกกุญแจไม่ตรงกับชุดนี้?)", "detail": (out + err).strip()[-600:]})
            info, steps = {}, []
            for line in out.splitlines():
                k, _, v = line.partition("=")
                if k == "step":
                    steps.append(v)
                elif k:
                    info[k] = v
            return self.send(200, {"info": info, "steps": steps})

        if u == "/api/run":
            server, snap, steps = b.get("server", ""), b.get("snapshot", ""), b.get("steps") or []
            if not SERVER_RE.match(server) or not SNAP_RE.match(snap):
                return self.send(400, {"error": "เลือกเครื่อง/ชุดไม่ถูกต้อง"})
            if not steps or any(s not in STEP_INFO for s in steps):
                return self.send(400, {"error": "เลือกขั้นตอนอย่างน้อย 1 ขั้น"})
            da = (b.get("da_license") or "").strip()
            if da and not re.fullmatch(r"[A-Za-z0-9_-]{6,200}", da):
                return self.send(400, {"error": "License key ของ DirectAdmin มีตัวอักษรแปลก"})
            with st.lock:
                if st.running:
                    return self.send(409, {"error": "กำลังกู้อยู่"})
                envp = os.path.join(sess, "session.env")
                with open(envp, encoding="utf-8") as f:
                    kept = [ln for ln in f if not ln.startswith(("DA_LICENSE=", "TPIX_RESTORE_KEY_ID=", "TPIX_RESTORE_SECRET="))]
                if da:
                    kept.append(f"DA_LICENSE={shlex.quote(da)}\n")
                for k, f_ in (("TPIX_RESTORE_KEY_ID", "tpix_key_id"), ("TPIX_RESTORE_SECRET", "tpix_secret")):
                    v = (b.get(f_) or "").strip()
                    if v:
                        kept.append(f"{k}={shlex.quote(v)}\n")
                write_secret(envp, "".join(kept))
                st.running, st.cancel = True, False
                st.results = {s: "pending" for s in steps}
                threading.Thread(target=run_steps, args=(st, server, snap, steps), daemon=True).start()
            return self.send(200, {"started": True})

        if u == "/api/cancel":
            st.cancel = True
            return self.send(200, {"ok": True, "note": "จะหยุดหลังขั้นที่กำลังทำจบ"})

        if u == "/api/wipe":
            if st.running:
                return self.send(409, {"error": "รอให้ขั้นที่กำลังทำจบก่อน"})
            for n in ("private.pem", "session.env", "token"):
                p = os.path.join(sess, n)
                if os.path.exists(p):
                    with open(p, "r+b") as f:  # เขียนทับก่อนลบ
                        f.write(b"\0" * os.path.getsize(p))
                    os.remove(p)
            threading.Timer(2.0, lambda: os._exit(0)).start()
            return self.send(200, {"ok": True})

        return self.send(404, {"error": "ไม่พบ"})


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--bind", default="0.0.0.0")
    ap.add_argument("--port", type=int, default=8443)
    ap.add_argument("--session", default="/run/server-restore")
    ap.add_argument("--engine", default=os.path.join(os.path.dirname(os.path.abspath(__file__)), "server-restore.sh"))
    ap.add_argument("--cert", default="")
    ap.add_argument("--key", default="")
    ap.add_argument("--target-root", default="/", help="ทดสอบ: เขียนลงโฟลเดอร์นี้แทน /")
    ap.add_argument("--source-dir", default="", help="อ่านชุดสำรองจากโฟลเดอร์ (ดาวน์โหลดมาแล้ว) แทน R2")
    args = ap.parse_args()

    os.makedirs(args.session, mode=0o700, exist_ok=True)
    tok_path = os.path.join(args.session, "token")
    if os.path.exists(tok_path):
        with open(tok_path) as f:
            token = f.read().strip()
    else:
        token = secrets.token_hex(16)
        write_secret(tok_path, token)

    srv = http.server.ThreadingHTTPServer((args.bind, args.port), Handler)
    srv.state = State(args)
    srv.token = token
    if args.cert:
        ctx = ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER)
        ctx.minimum_version = ssl.TLSVersion.TLSv1_2
        ctx.load_cert_chain(args.cert, args.key)
        srv.socket = ctx.wrap_socket(srv.socket, server_side=True)
    print(f"server-restore UI on {'https' if args.cert else 'http'}://{args.bind}:{args.port}/?t={token}", flush=True)
    srv.serve_forever()


if __name__ == "__main__":
    sys.exit(main())
