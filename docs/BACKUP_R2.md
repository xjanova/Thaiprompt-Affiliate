# 🗄️ สำรองทั้งเครื่องขึ้น Cloudflare R2 (เข้ารหัส) + กู้คืนด้วยหน้าเว็บ

> 2026-11-08 — เจ้าของสั่ง:
> "ฐานข้อมูลทั้งก้อนของทุกเว็บในเซิร์ฟเวอร์ และทุกเว็บในเซิร์ฟเวอร์" · "ต้องไม่ให้คนอื่นเข้าถึงไฟล์ได้"
> "เก็บค่าที่จำเป็นไว้ด้วย ลง Ubuntu ใหม่แล้วทำให้เหมือนตอนนี้ได้ทันที" · "config ที่ตั้งหรือแก้ไว้ เก็บแบบเดียวกันให้เครื่อง 252 (TPIX chain) ด้วย"
> "ทำเป็นเว็บสคริปต์รีสโตร์แบบกราฟิก แค่ติดตั้งก็รีสโตร์ได้เลย"

ทุกอย่างอยู่ใน [`deployment/server-backup/`](../deployment/server-backup/) — สคริปต์ชุดเดียวใช้ได้ทุกเครื่อง ค่าที่ต่างกันอยู่ใน `hosts/<เครื่อง>.conf`

## เครื่องที่สำรอง

| เครื่อง | ไฟล์ค่า | ทุกคืน 02:00 (เวลาไทย) | วันที่ 10, 25 เพิ่ม |
|---|---|---|---|
| **123.253.62.251** DirectAdmin (main.thaiprompt.online + เว็บทั้งหมด) | `hosts/thaiprompt-251.conf` | ทุกฐานข้อมูล (41 ฐาน) + สภาพเครื่อง | ทุกเว็บ (55 โดเมน) + home ผู้ใช้ (อีเมล ฯลฯ) |
| **123.253.62.252** tpixserver (เชน TPIX: validator 4 + bootnode + Blockscout) | `hosts/tpix-252.conf` | สภาพเครื่อง + home | docker image ที่ build เอง + ฐาน Blockscout |

ข้อมูลบล็อกของเชน TPIX ไม่อยู่ในชุดนี้ เพราะมีระบบของมันเองอยู่แล้ว: `tpix-backup-chain` ทำงานตี 2:17 ส่งขึ้น bucket `tpix-chain-backup` (gpg)
- รหัสถอดไฟล์อยู่ใน `/etc/tpix-backup.env` ซึ่งอยู่ในชุด "สภาพเครื่อง" ของเรา
- ตัวกู้คืนจึงดึงข้อมูลเชนมาวางเองได้

### "สภาพเครื่อง" (system) คืออะไร

| ไฟล์ | เนื้อใน |
|---|---|
| `system/etc.tar` | `/etc` ทั้งก้อน: nginx, cron.d, systemd unit, firewall (ufw/csf/fail2ban), sudoers, SSL, `*.env` ของระบบ ฯลฯ |
| `system/config.tar` | 251 มี:<br>• DirectAdmin `conf` (รหัส da_admin), `data` (ผู้ใช้ โดเมน SSL DNS), ตัวเลือก custombuild และของที่แก้เอง, csf plugin<br>• php.ini ที่แก้เอง<br>• `/root`, `/usr/local/{bin,sbin,lib}`, cron ของทุกผู้ใช้, `/opt/{n8n,xcluadeagent,scylladb}`<br><br>252 มี:<br>• `/opt` ทั้งหมด (docker-compose ทุกชุด, genesis, **คีย์ validator ทุกตัว**, สคริปต์) ยกเว้นข้อมูลบล็อก<br>• `/usr/local`, `/root`, cron |
| `system/manifest.tar` | บัญชีเครื่อง: OS/เวอร์ชัน, แพ็กเกจที่ลงเอง, npm global, service ที่เปิด, ผู้ใช้/กลุ่ม, crontab, ports, docker (container/image/compose), ผู้ใช้/โดเมน DirectAdmin, pm2 |

ไม่เก็บสิ่งที่ติดตั้งใหม่ได้:
- cache: `.cache`, `.gradle`, `.npm`
- `node_modules`, `.git`
- log และ cache/session/view ของ Laravel
- `custombuild` ทั้งก้อน (8 GB)
- `/opt/android-sdk`, `tmp`, venv
- dump ก่อน deploy ใน `main.thaiprompt.online/public_html/backups`

## ไฟล์บน R2 — `thaiprompt-backup/<เครื่อง>/…`

```
server-123-253-62-251/
  daily/1-mon … 7-sun/          ← ทุกคืน เขียนทับวน 7 วัน
      db/<ฐาน>.sql.zst.tpbk  db/_users-grants.sql.zst.tpbk
      system/{etc,config,manifest}.tar.zst.tpbk
      _snapshot.txt             ← สรุปรอบ (เวลา จำนวนไฟล์ ขนาด ที่ล้ม) ไม่มีข้อมูลลับ
  full/day10/  full/day25/      ← ชุดเต็ม กู้ทั้งเครื่องได้จากโฟลเดอร์เดียว (เก็บ 1 เดือน)
      db/ system/ sites/<user>/<โดเมน>.tar.zst.tpbk  homes/<user>.tar.zst.tpbk
server-123-253-62-252/
  daily/…/{system,homes}/   full/…/{system,homes,docker/images,docker/pg}/
```

- ไฟล์บน R2 ถูกแทนเมื่อไฟล์ใหม่ขึ้นครบเท่านั้น
- ของที่หายไปจากเครื่อง (เช่น ฐานหรือโดเมนที่ลบแล้ว) ถูกเก็บกวาดหลังรอบนั้นสำเร็จ
- ชุดเต็ม: ฐานข้อมูลและสภาพเครื่องเป็น copy ฝั่ง R2 จากคืนเดียวกัน (ไม่ dump ซ้ำ)

**ขนาดจริง (2026-11-08)**

| เครื่อง | รายคืน | ชุดเต็ม |
|---|---|---|
| 251 | ~0.6 GB | +11 GB เว็บ และ home ผู้ใช้ |
| 252 | 133 MB | +image และฐาน Blockscout |

รวมทั้งหมดบน R2 ประมาณ 35-40 GB ≈ **$0.6/เดือน** ($0.015/GB — บัญชีนี้ใช้เกินโควต้าฟรี 10 GB อยู่แล้วจาก `tpix-chain-backup`)

**แจ้งเตือนเมื่อล้ม**
- 251: Telegram แอดมิน ผ่าน `php artisan alert:telegram` (ต้อง merge PR นี้และ deploy ก่อน)
- 252: คาดแดงหลังบ้าน tpix.online (ตัวเดียวกับ watchdog)

ชุดเต็มที่สำเร็จจะแจ้ง 251 ด้วย ส่วนรอบรายคืนแจ้งเฉพาะตอนล้ม

## 🔐 ความเป็นส่วนตัว — 3 ชั้น

1. **bucket ส่วนตัว** `thaiprompt-backup` — ไม่เปิด Public access / r2.dev / custom domain
2. **token จำกัดสิทธิ์** — Object Read & Write เฉพาะ bucket นี้ ใช้ได้จาก IP `123.253.62.251` และ `123.253.62.252` เท่านั้น
3. **เข้ารหัสทุกไฟล์ก่อนออกจากเครื่อง**
   - เซิร์ฟเวอร์ถือแค่**แม่กุญแจ** (`/etc/server-backup-r2/backup-public.pem`) ล็อกได้ แต่เปิดไม่ได้
   - **ลูกกุญแจ**อยู่กับเจ้าของคนเดียว ลายนิ้วมือ `e9bb08152fc54157`

### ⚠️ ลูกกุญแจ — เก็บ 2 ที่ ห้ามหาย

ลูกกุญแจคือ `D:\Thaiprompt-Backup-Key\backup-private.pem` ถ้าทำหายทุกสำเนา ไฟล์สำรอง**ทุกไฟล์**จะเปิดไม่ได้อีกเลย
1. ตัวจัดการรหัสผ่าน (โน้ตปลอดภัย วางเนื้อไฟล์ทั้งหมด)
2. สำเนาออฟไลน์ (USB ที่เก็บแยก / พิมพ์ลงกระดาษ) — คัดลอกโฟลเดอร์ `restore-kit` ไปด้วย

ห้ามเก็บไว้บนเซิร์ฟเวอร์ ใน git หรือใน bucket เดียวกับไฟล์สำรอง

## ไฟล์บนเครื่อง (ทั้ง 2 เครื่องเหมือนกัน)

| ที่ | อะไร |
|---|---|
| `/usr/local/sbin/server-backup-r2` | สคริปต์หลัก |
| `/etc/cron.d/server-backup-r2` | ปลุกทุกชั่วโมง รันจริงเมื่อเวลาไทย = 02 (252 ตั้งเวลาเครื่องเป็น UTC และ cron ของ Ubuntu ไม่รองรับ `CRON_TZ`) |
| `/etc/server-backup-r2/r2.conf` | ค่าของเครื่อง (จาก `hosts/*.conf`) + Account ID — root 600 |
| `/etc/server-backup-r2/r2-credentials.conf` | Access Key ID + Secret — root 600 — **เจ้าของใส่เอง** |
| `/etc/server-backup-r2/backup-public.pem` | แม่กุญแจ |
| `/var/log/server-backup-r2.log` · `/var/lib/server-backup-r2/last-*.txt` | log · ผลรอบล่าสุด |

**ติดตั้ง/อัปเดต** (รันเป็น admin บน 251 หรือ tpixadmin บน 252 — ใช้ sudo แค่ cp/tee):

```bash
bash deployment/server-backup/install.sh e1073e47af620370eed1088f297c6dcf backup-public.pem thaiprompt-251   # หรือ tpix-252
```

**ใส่/เปลี่ยนคีย์ R2** (ใส่ทั้ง 2 เครื่องในคำสั่งเดียว key ไม่ผ่านแชท และ Secret ไม่ขึ้นจอ) — รันใน Git Bash บนเครื่องเจ้าของ:

```bash
read -p "Access Key ID: " AK; read -s -p "Secret Access Key: " SK; echo; C=$(printf 'R2_ACCESS_KEY_ID="%s"\nR2_SECRET_ACCESS_KEY="%s"\n' "$AK" "$SK"); printf '%s\n' "$C" | ssh -i ~/.ssh/thaiprompt_admin admin@123.253.62.251 'sudo tee /etc/server-backup-r2/r2-credentials.conf >/dev/null && echo "251 saved"'; printf '%s\n' "$C" | ssh -i ~/.ssh/tpix_chain_new tpixadmin@123.253.62.252 'sudo install -d -m 700 /etc/server-backup-r2 && sudo sh -c "umask 077; cat > /etc/server-backup-r2/r2-credentials.conf" && echo "252 saved"'; unset AK SK C
```

## คำสั่ง (root)

```bash
server-backup-r2 check                      # เครื่องมือ + แม่กุญแจ + DB + R2 เขียน/อ่าน/ลบ
server-backup-r2 list                       # ไฟล์บน R2 + ผลรอบล่าสุด
server-backup-r2 daily                      # รอบรายคืนเดี๋ยวนี้ (ลงช่องของวันนี้)
server-backup-r2 full --slot=manual         # ชุดเต็มลงช่องแยก ไม่ทับรอบอัตโนมัติ (ลบเองใน R2)
server-backup-r2 run sites --slot=full/day10   # ทำเฉพาะส่วนประกอบ
server-backup-r2 full --local --slot=test   # ทดสอบ: เก็บใน /var/tmp/server-backup-r2/local ไม่อัปโหลด
```

บน 251 user admin ใช้ sudo รันสคริปต์ตรงๆ ไม่ได้ ให้ตั้ง `/etc/cron.d/<ชื่อ>` แบบรันครั้งเดียว ใส่ `rm -f` ตัวเองไว้ในบรรทัด แล้ว `chown admin` ไฟล์ผล

## ♻️ กู้คืนทั้งเครื่อง (หน้าเว็บ)

ชุดกู้คืน: [`deployment/server-backup/restore/`](../deployment/server-backup/restore/) — สำเนาอยู่ที่ `D:\Thaiprompt-Backup-Key\restore-kit\` คู่กับลูกกุญแจ

1. ลง Ubuntu ใหม่ (เวอร์ชันเดียวกับเดิม: 251 = 22.04, 252 = 24.04) แล้วคัดลอก restore-kit ขึ้นเครื่อง
   ```bash
   scp -r restore-kit root@<IP ใหม่>:
   ssh root@<IP ใหม่> 'bash restore-kit/install-restore.sh'
   ```
2. เปิดลิงก์ที่พิมพ์ออกมา `https://<IP>:8443/?t=…` (เบราว์เซอร์เตือนใบรับรอง → ขั้นสูง → ดำเนินการต่อ)
3. ใส่ Account ID + คีย์ R2 + ลูกกุญแจ
   - ถ้า IP ใหม่ต่างจากเดิม ให้สร้าง token **Object Read only** ชั่วคราวที่ไม่ผูก IP แล้วลบหลังกู้เสร็จ
4. เลือกเครื่องและชุด
   - กู้ทั้งเครื่อง ให้เลือก `full/day10` หรือ `full/day25`
   - แล้วค่อยกู้ฐานข้อมูลจาก `daily/…` ล่าสุดซ้ำ
5. ตรวจข้อมูลเครื่องเดิม แล้วติ๊กขั้นตอน กดเริ่ม และดู log สด
   - ขั้นที่ล้มจะหยุดรอ แก้แล้วกดต่อจากขั้นนั้นได้
6. เสร็จแล้วกด **ล้างกุญแจและปิดหน้าเว็บ** · ชี้ DNS ไป IP ใหม่ · ลบ token ชั่วคราว

**ขั้นตอน (ข้ามขั้นที่ไม่เกี่ยวกับเครื่องนั้นเอง)**

| # | ขั้น | หมายเหตุ |
|---|---|---|
| 1 | เตรียมเครื่อง | ชื่อเครื่อง, เขตเวลา, สำเนา `/etc` เดิมไว้เทียบที่ `/root/server-restore/` |
| 2 | ติดตั้งแพ็กเกจ | ติดตั้งแพ็กเกจที่ลงเองจากแหล่งเดิม (docker-ce ฯลฯ) — ข้ามเคอร์เนลและ grub |
| 3 | DirectAdmin | ต้องใช้ License key (ผูก IP — ย้าย IP ต้องแก้ใน my.directadmin.com ก่อน) แล้ว `build all` ตามตัวเลือกเดิม |
| 4 | ผู้ใช้ | uid/gid และรหัสผ่านเดิม |
| 5 | config | `/root` `/usr/local` `/opt` cron ข้อมูล DirectAdmin |
| 6 | `/etc` | คืนเฉพาะส่วนที่ตั้งเอง — ไม่ทับ fstab/netplan/ssh host key ของเครื่องใหม่ |
| 7-9 | home / เว็บ / ฐานข้อมูล | ผู้ใช้และสิทธิ์ MySQL ก่อน (`CREATE OR REPLACE USER`) แล้วทุกฐาน |
| 10-12 | docker image / ข้อมูลเชน / container | ข้อมูลเชน: ไฟล์ล่าสุดจาก `tpix-chain-backup` → validator-4 ทั้งโฟลเดอร์, ตัวอื่นแทนเฉพาะ blockchain/trie (คีย์ของแต่ละตัวมาจาก config) |
| 13-14 | service / ตรวจผล | rewrite_confs, pm2 resurrect, ยิงทุกโดเมนที่ 127.0.0.1 |

**ทดสอบโดยไม่แตะระบบ:** `install-restore.sh --test /var/tmp/rtest/root [--source <โฟลเดอร์ที่มี server-…/>]`
- เขียนลงโฟลเดอร์ทดสอบเท่านั้น
- ขั้นที่ต้องแตะระบบ (apt, systemctl, docker, mysql) จะแค่บอกว่าจะทำอะไร

**ถอดไฟล์ทีละไฟล์ด้วยมือ** (เช่น เอาฐานเดียวคืน):

```bash
scripts/backup/decrypt-backup.sh --fingerprint backup-private.pem          # → e9bb08152fc54157
scripts/backup/decrypt-backup.sh admin_thaipromptmain.sql.zst.tpbk backup-private.pem - | mysql -u root -p
scripts/backup/decrypt-backup.sh main.thaiprompt.online.tar.zst.tpbk backup-private.pem - | tar -C /home/admin/domains -xf -
```

> dump มาจาก MariaDB 10.6 บรรทัดแรกเป็น `/*M!999999\- enable the sandbox mode */` — client MariaDB อ่านได้ปกติ
> ถ้าเทเข้า **MySQL** ให้ตัดบรรทัดแรกก่อน (`| sed 1d |`)

## รูปแบบไฟล์ `.tpbk`

```
"TPBK1 0512\n"                  11 ไบต์ — 0512 = ความยาวกุญแจที่ถูกห่อ
RSA-OAEP(แม่กุญแจ, กุญแจสุ่ม)    512 ไบต์ — กุญแจสุ่มใหม่ทุกไฟล์ (แต่ละไฟล์ถอดได้ด้วยตัวเอง)
openssl enc -aes-256-ctr -pbkdf2 ของ zstd ของข้อมูลจริง
```

metadata บน R2 มี 2 ค่า:
- `sha256` ของไฟล์ `.tpbk` (ใช้เทียบหลังดาวน์โหลด)
- `keyfp` ลายนิ้วมือแม่กุญแจที่ใช้เข้ารหัส

## เปลี่ยนคู่กุญแจ

1. สร้างลูกกุญแจใหม่: `openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:4096 -out backup-private.pem`
2. สร้างแม่กุญแจจากลูกกุญแจ: `openssl pkey -in backup-private.pem -pubout -out backup-public.pem`
3. รัน install.sh ใหม่บนทั้ง 2 เครื่องด้วยแม่กุญแจใหม่

⚠️ **เก็บลูกกุญแจเก่าไว้จนครบ 1 เดือน** — ไฟล์ที่สร้างก่อนเปลี่ยนยังต้องใช้ลูกกุญแจเก่า (ดู `keyfp` ใน metadata)

## กับดักที่เจอตอนทำ (2026-11-08)

- **rclone 1.60** (apt ของ Ubuntu 24.04 บน 252) อัปโหลดขึ้น R2 สำเร็จ แต่ HEAD ตรวจผลได้ `501 NotImplemented`
  - ผลคือนับเป็นล้มทั้งที่ไฟล์ขึ้นแล้ว
  - แก้: ตั้ง `NO_HEAD=true` แล้วยืนยันขนาดด้วย list
- sudoers ของ admin บน 251 มีแค่ tee/cp/cat/find/ls/grep/sed/systemctl
  - ติดตั้งด้วย "cp ไฟล์ที่ตั้ง mode ไว้แล้ว"
  - งาน root ใช้ `/etc/cron.d` แบบรันครั้งเดียว
- `du` ที่ไม่ใช้ sudo นับ `main.thaiprompt.online` ได้ 9 GB แต่ tar จริง 7+ GB มาจาก `public_html/backups` — ใช้ตัวเลขจากรอบจริงเสมอ
