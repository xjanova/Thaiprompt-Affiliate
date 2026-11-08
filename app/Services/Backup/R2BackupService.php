<?php

namespace App\Services\Backup;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * 🗄️ สำรองฐานข้อมูล / ทั้งเว็บ ขึ้น Cloudflare R2 แบบเข้ารหัส
 *
 * ## รูปแบบไฟล์ .tpbk (ถอดได้ด้วย openssl + zstd ล้วน ไม่ต้องมี Laravel)
 *   บรรทัดหัว 11 ไบต์:  "TPBK1 0512\n"            ← 0512 = ความยาวกุญแจที่ถูกห่อ
 *   กุญแจที่ถูกห่อ:     RSA-OAEP(public key, กุญแจสุ่ม 64 ตัวอักษร hex)
 *   เนื้อไฟล์:          openssl enc -aes-256-ctr -pbkdf2 (กุญแจสุ่ม) ของ zstd ของข้อมูลจริง
 *
 * กุญแจสุ่มใหม่ทุกไฟล์ และห่อไว้ "ในไฟล์เดียวกัน" ⇒ แต่ละไฟล์ถอดได้ด้วยตัวเอง
 * (ถ้าแยกไฟล์กุญแจไว้ข้างๆ แล้วอัปโหลดตัวใดตัวหนึ่งล้ม จะได้คู่ที่ไม่ตรงกัน = เปิดไม่ได้)
 *
 * ## ทำไมเซิร์ฟเวอร์ตรวจเนื้อไฟล์เองไม่ได้
 * เซิร์ฟเวอร์ไม่มี private key โดยตั้งใจ — จึงพึ่ง exit code ของทุกท่อ (mysqldump/tar, zstd, openssl)
 * + ขนาดไฟล์บน R2 ต้องตรงกับไฟล์ในเครื่อง + sha256 เก็บไว้ใน metadata ให้ตรวจตอนกู้
 */
class R2BackupService
{
    public const MAGIC = 'TPBK1';

    public const HEADER_LENGTH = 11; // "TPBK1 0512\n"

    public const CIPHER = 'aes-256-ctr';

    /** รันนานสุดต่อขั้น (วินาที) — dump ~3.5 GB ใช้ราว 3-8 นาที เผื่อไว้มาก */
    protected const PROCESS_TIMEOUT = 3 * 3600;

    /** โฟลเดอร์ทำงานที่ค้างเกินนี้ = งานเก่าที่ตายกลางทาง ลบทิ้งได้ */
    protected const STALE_WORK_HOURS = 6;

    /** @var callable|null */
    protected $logger = null;

    /**
     * ตั้งตัวรับข้อความความคืบหน้า (คำสั่ง artisan ส่ง $this->line มา)
     */
    public function setLogger(?callable $logger): static
    {
        $this->logger = $logger;

        return $this;
    }

    // ============================================================
    // ชื่อช่องเก็บ — เขียนทับวนเอง
    // ============================================================

    /**
     * ช่องฐานข้อมูลรายวัน: 7 ช่องตามวันในสัปดาห์ ⇒ เก็บย้อนหลัง 7 วันพอดี
     *
     * @example dbObjectKey(จันทร์) → "db/db-1-mon.sql.zst.tpbk"
     */
    public function dbObjectKey(CarbonInterface $at): string
    {
        return sprintf('db/db-%d-%s.sql.zst.tpbk', $at->isoWeekday(), strtolower($at->format('D')));
    }

    /**
     * ช่องสำรองทั้งเว็บ: 2 ช่องต่อเดือน (day10 / day25) — สั่งมือวันอื่นจะลงช่องที่ใกล้ที่สุดก่อนหน้า
     * วันที่ 1-17 → day10 · 18-31 → day25
     */
    public function siteSlot(CarbonInterface $at): string
    {
        return $at->day <= 17 ? 'day10' : 'day25';
    }

    // ============================================================
    // งานหลัก
    // ============================================================

    /**
     * สำรองฐานข้อมูลทั้งก้อน → db/db-{วัน}.sql.zst.tpbk
     *
     * @param  bool  $localOnly  true = สร้างไฟล์ไว้ในเครื่อง ไม่อัปโหลด (ทดสอบ)
     * @return array ผลลัพธ์ [key, size, sha256, seconds, local_path?]
     */
    public function backupDatabase(bool $localOnly = false): array
    {
        $this->preflight($localOnly);

        return $this->withLock('db', function () use ($localOnly) {
            $started = microtime(true);
            $work = $this->makeWorkDir('db');

            try {
                $key = $this->dbObjectKey(now());
                $file = $work.'/database.sql.zst.tpbk';
                $this->dumpDatabaseTo($file);

                $result = $this->finish($file, $key, 'db', $localOnly);
                $result['seconds'] = round(microtime(true) - $started, 1);
                if (! $localOnly) {
                    $this->remember('db', $result);
                }

                return $result;
            } finally {
                if (! $localOnly) {
                    $this->removeDir($work);
                }
            }
        });
    }

    /**
     * สำรองทั้งเว็บ (ไฟล์ + ฐานข้อมูล ณ วันนั้น) → site/{slot}/files.tar.zst.tpbk + database.sql.zst.tpbk
     *
     * ใส่ฐานข้อมูลด้วยเพราะชุด db รายวันอยู่แค่ 7 วัน — ถ้าต้องย้อนเว็บไปวันที่ 10
     * ต้องมีฐานข้อมูลของวันเดียวกันคู่กัน ไม่งั้นกู้แล้วไฟล์กับข้อมูลไม่ตรงกัน
     */
    public function backupSite(?string $slot = null, bool $localOnly = false): array
    {
        $slot ??= $this->siteSlot(now());
        if (! preg_match('/^[a-z0-9-]{1,20}$/', $slot)) {
            throw new RuntimeException("ชื่อช่องไม่ถูกต้อง: {$slot} (ใช้ได้แค่ a-z 0-9 -)");
        }

        $this->preflight($localOnly);

        return $this->withLock('site', function () use ($slot, $localOnly) {
            $started = microtime(true);
            $work = $this->makeWorkDir('site');

            try {
                // 1) ฐานข้อมูลก่อน (เล็ก) — อัปโหลดแล้วลบทิ้งทันทีให้พื้นที่ว่างพอสำหรับไฟล์เว็บ
                $dbFile = $work.'/database.sql.zst.tpbk';
                $this->dumpDatabaseTo($dbFile);
                $db = $this->finish($dbFile, "site/{$slot}/database.sql.zst.tpbk", 'site-db', $localOnly);
                if (! $localOnly) {
                    @unlink($dbFile);
                }

                // 2) ไฟล์เว็บ
                $filesFile = $work.'/files.tar.zst.tpbk';
                $this->archiveSiteTo($filesFile);
                $files = $this->finish($filesFile, "site/{$slot}/files.tar.zst.tpbk", 'site-files', $localOnly);

                $result = [
                    'slot' => $slot,
                    'database' => $db,
                    'files' => $files,
                    'size' => $db['size'] + $files['size'],
                    'seconds' => round(microtime(true) - $started, 1),
                ];
                if (! $localOnly) {
                    $this->remember('site', $result);
                }

                return $result;
            } finally {
                if (! $localOnly) {
                    $this->removeDir($work);
                }
            }
        });
    }

    /**
     * ตรวจของที่ต้องมีก่อนเริ่ม — ล้มเร็วก่อน dump 3.5 GB ทิ้งเปล่าๆ เพราะลืมตั้งค่า
     */
    protected function preflight(bool $localOnly): void
    {
        $this->publicKey();
        if (! $localOnly) {
            $this->disk();
        }
    }

    /**
     * ทดสอบการเชื่อม R2: เขียนไฟล์เล็ก → อ่านขนาด → ลบ
     */
    public function checkConnection(): array
    {
        $this->publicKey(); // ตรวจแม่กุญแจไปด้วย
        $disk = $this->disk();
        $key = $this->remoteKey('.connection-check');
        $body = 'ok '.now()->toIso8601String();

        $disk->put($key, $body);
        $size = $disk->size($key);
        $disk->delete($key);

        if ($size !== strlen($body)) {
            throw new RuntimeException("R2 ตอบขนาดไม่ตรง ({$size} != ".strlen($body).')');
        }

        return [
            'bucket' => config('backup.r2.bucket'),
            'prefix' => config('backup.r2.prefix'),
            'key_fingerprint' => $this->publicKeyFingerprint(),
        ];
    }

    /**
     * รายการไฟล์สำรองบน R2 (ใหม่สุดก่อน)
     *
     * @return array<int, array{key: string, size: int, modified: int}>
     */
    public function listBackups(): array
    {
        $disk = $this->disk();
        $prefix = trim((string) config('backup.r2.prefix'), '/');
        $rows = [];

        foreach ($disk->allFiles($prefix) as $path) {
            $rows[] = [
                'key' => $prefix === '' ? $path : substr($path, strlen($prefix) + 1),
                'size' => $disk->size($path),
                'modified' => $disk->lastModified($path),
            ];
        }

        usort($rows, fn ($a, $b) => $b['modified'] <=> $a['modified']);

        return $rows;
    }

    /**
     * ผลรอบล่าสุดของแต่ละงาน (เก็บใน cache — ใช้แสดงใน `backup:r2 list`)
     */
    public function lastResult(string $kind): ?array
    {
        return Cache::get("backup_r2:last:{$kind}");
    }

    public function rememberFailure(string $kind, string $error): void
    {
        Cache::forever("backup_r2:last:{$kind}", [
            'ok' => false,
            'at' => now()->toIso8601String(),
            'error' => mb_substr($error, 0, 500),
        ]);
    }

    // ============================================================
    // ผลิตไฟล์
    // ============================================================

    /**
     * dump ฐานข้อมูล → zstd → เข้ารหัส → ไฟล์
     *
     * --single-transaction: ทุกตารางเป็น InnoDB (วัดบน prod 644/644) ⇒ ได้ภาพนิ่งชุดเดียวกันโดยไม่ล็อกเว็บ
     * รหัสผ่าน DB ส่งทาง env MYSQL_PWD — ไม่โผล่ใน `ps` เหมือนการใส่ -p
     */
    protected function dumpDatabaseTo(string $file): void
    {
        $conn = config('database.connections.'.config('database.default'));
        if (! in_array($conn['driver'] ?? '', ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('รองรับเฉพาะ MySQL/MariaDB (ตอนนี้: '.($conn['driver'] ?? '?').')');
        }

        $args = [
            '--single-transaction', '--quick', '--routines', '--triggers', '--events',
            '--hex-blob', '--default-character-set=utf8mb4', '--no-tablespaces',
            '--max-allowed-packet=1G',
        ];
        $env = [
            'MYSQL_PWD' => (string) ($conn['password'] ?? ''),
            'TPBK_DB_USER' => (string) ($conn['username'] ?? ''),
            'TPBK_DB_NAME' => (string) ($conn['database'] ?? ''),
        ];

        $conTarget = '-u "$TPBK_DB_USER"';
        if (! empty($conn['unix_socket'])) {
            $env['TPBK_DB_SOCKET'] = (string) $conn['unix_socket'];
            $conTarget .= ' --socket="$TPBK_DB_SOCKET"';
        } else {
            $env['TPBK_DB_HOST'] = (string) ($conn['host'] ?? '127.0.0.1');
            $env['TPBK_DB_PORT'] = (string) ($conn['port'] ?? '3306');
            $conTarget .= ' -h "$TPBK_DB_HOST" -P "$TPBK_DB_PORT"';
        }

        $producer = escapeshellarg($this->binary('mysqldump')).' '.implode(' ', $args).' '.$conTarget.' "$TPBK_DB_NAME"';

        $this->log('⏳ dump ฐานข้อมูล '.$env['TPBK_DB_NAME'].' …');
        $this->encryptPipeline($producer, 0, 6, $file, $env);
    }

    /**
     * tar ไฟล์เว็บ (ตัด vendor/node_modules/.git/log/backups ออก) → zstd → เข้ารหัส → ไฟล์
     *
     * ⚠️ ไฟล์ส่วนตัวของลูกค้า (สลิป, eKYC, รูปโปรไฟล์, เอกสารไรเดอร์) ถูก deploy.sh ย้ายออกนอก git tree
     *    แล้ว symlink กลับมาที่ storage/app/* — tar ไม่ตาม symlink ⇒ ต้องใส่ปลายทางพวกนั้นเข้าไปเอง
     *    (วัด prod 2026-11-08: fortune_data 184 MB = รูปสลิป ถ้าไม่ตามจะหายจากไฟล์สำรองเงียบๆ)
     *
     * โครงในไฟล์ = โครงโฟลเดอร์โดเมน: public_html/… + fortune_data/… + ekyc_data/… (แตกที่โฟลเดอร์โดเมนได้เลย)
     * tar exit 1 = "ไฟล์เปลี่ยนระหว่างอ่าน" (เว็บยังวิ่งอยู่) — ยอมรับได้ ไม่ใช่ความเสียหาย
     */
    protected function archiveSiteTo(string $file): void
    {
        $members = $this->siteArchiveMembers();
        $baseName = basename(rtrim(base_path(), '/'));

        $parts = [escapeshellarg($this->binary('tar'))];
        foreach ((array) config('backup.site_excludes', []) as $exclude) {
            // config เขียนเทียบ base_path ("./vendor") → ในไฟล์คือ "public_html/vendor"
            $parts[] = escapeshellarg('--exclude='.$baseName.'/'.ltrim(preg_replace('#^\./#', '', $exclude), '/'));
        }
        $parts[] = '--warning=no-file-changed --warning=no-file-removed --warning=no-file-shrank';
        $parts[] = '-cf -';
        foreach ($members as [$dir, $name]) {
            $parts[] = '-C '.escapeshellarg($dir).' '.escapeshellarg($name);
        }

        $this->log('⏳ รวมไฟล์เว็บ: '.implode(', ', array_column($members, 1)).' …');
        $this->encryptPipeline(implode(' ', $parts), 1, 3, $file, []);
    }

    /**
     * รายการที่ต้องเก็บ: ตัวเว็บ + โฟลเดอร์ส่วนตัวนอก git tree ที่ storage/app/* ชี้ไป
     *
     * @return list<array{0: string, 1: string}> [โฟลเดอร์แม่, ชื่อ]
     */
    public function siteArchiveMembers(): array
    {
        $base = rtrim(base_path(), '/');
        $members = [$base => [dirname($base), basename($base)]];

        foreach (glob($base.'/storage/app/*') ?: [] as $path) {
            if (! is_link($path)) {
                continue;
            }
            $real = realpath($path);
            if ($real === false || ! is_dir($real) || str_starts_with($real.'/', $base.'/')) {
                continue; // ลิงก์เสีย / ชี้กลับเข้าเว็บเอง (เก็บอยู่แล้ว)
            }
            $members[$real] = [dirname($real), basename($real)];
        }

        return array_values($members);
    }

    /**
     * ท่อกลาง: producer | zstd | openssl enc >> file (ต่อท้ายหัวไฟล์ที่เขียนไว้แล้ว)
     *
     * @param  int  $maxProducerExit  exit code สูงสุดของ producer ที่ยอมรับ (tar = 1)
     * @param  int  $level  ระดับ zstd (SQL = 6 บีบดี, ไฟล์เว็บส่วนใหญ่เป็นรูป = 3 เร็ว)
     */
    protected function encryptPipeline(string $producer, int $maxProducerExit, int $level, string $file, array $env): void
    {
        $dataKey = bin2hex(random_bytes(32)); // 64 ตัวอักษร — openssl ใช้เป็น passphrase

        if (! openssl_public_encrypt($dataKey, $wrapped, $this->publicKey(), OPENSSL_PKCS1_OAEP_PADDING)) {
            throw new RuntimeException('ห่อกุญแจด้วย public key ไม่สำเร็จ: '.openssl_error_string());
        }

        $header = sprintf('%s %04d', self::MAGIC, strlen($wrapped))."\n";
        if (strlen($header) !== self::HEADER_LENGTH || file_put_contents($file, $header.$wrapped) === false) {
            throw new RuntimeException('เขียนหัวไฟล์สำรองไม่สำเร็จ: '.$file);
        }

        $threads = max(1, (int) config('backup.zstd_threads', 4));
        $script = 'umask 077; '
            .$producer
            .' | '.escapeshellarg($this->binary('zstd'))." -q -T{$threads} -{$level} -c"
            .' | '.escapeshellarg($this->binary('openssl')).' enc -'.self::CIPHER.' -pbkdf2 -salt -pass env:TPBK_KEY'
            .' >> "$TPBK_OUT"; '
            .'s=("${PIPESTATUS[@]}"); '
            ."if [ \"\${s[0]}\" -gt {$maxProducerExit} ] || [ \"\${s[1]}\" -ne 0 ] || [ \"\${s[2]}\" -ne 0 ]; then "
            .'echo "pipe exit codes: ${s[*]}" >&2; exit 1; fi';

        $process = new Process(['bash', '-c', $script], base_path(), $env + [
            'TPBK_KEY' => $dataKey,
            'TPBK_OUT' => $file,
        ], null, self::PROCESS_TIMEOUT);

        $process->run();
        unset($dataKey);

        if (! $process->isSuccessful()) {
            // stderr ของ mysqldump/tar ไม่มีรหัสผ่าน (ส่งทาง env) — ตัดให้สั้นก่อนส่งต่อ
            throw new RuntimeException('สร้างไฟล์สำรองไม่สำเร็จ: '.mb_substr(trim($process->getErrorOutput()), 0, 400));
        }

        clearstatcache(true, $file);
        $size = (int) filesize($file);
        if ($size < self::HEADER_LENGTH + strlen($wrapped) + 1024) {
            throw new RuntimeException("ไฟล์สำรองเล็กผิดปกติ ({$size} ไบต์) — ไม่อัปโหลดทับของเดิม");
        }

        $this->log('   ✓ '.$this->human($size));
    }

    /**
     * อัปโหลด (หรือเก็บไว้ในเครื่องถ้าทดสอบ) แล้วยืนยันขนาดบน R2
     */
    protected function finish(string $file, string $relativeKey, string $kind, bool $localOnly): array
    {
        $size = (int) filesize($file);
        $sha256 = hash_file('sha256', $file);

        $result = ['key' => $relativeKey, 'size' => $size, 'sha256' => $sha256];

        if ($localOnly) {
            $result['local_path'] = $file;
            $this->log("   🧪 ทดสอบ — ไม่อัปโหลด เก็บไว้ที่ {$file}");

            return $result;
        }

        $disk = $this->disk();
        $key = $this->remoteKey($relativeKey);

        $this->log("⏫ อัปโหลด {$key} ({$this->human($size)}) …");
        $stream = fopen($file, 'rb');
        try {
            // ไม่ส่ง visibility — R2 ไม่มี ACL สาธารณะ และบัคเก็ตนี้ต้องเป็นส่วนตัวเท่านั้น
            $disk->writeStream($key, $stream, [
                'ContentType' => 'application/octet-stream',
                'Metadata' => [
                    'sha256' => $sha256,
                    'kind' => $kind,
                    'created' => now()->toIso8601String(),
                    'keyfp' => $this->publicKeyFingerprint(),
                    'format' => self::MAGIC.'-'.self::CIPHER,
                ],
            ]);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $remote = $disk->size($key);
        if ($remote !== $size) {
            throw new RuntimeException("ขนาดบน R2 ไม่ตรง ({$remote} != {$size}) — {$key}");
        }

        $this->log('   ✓ ขึ้น R2 แล้ว');

        return $result;
    }

    // ============================================================
    // R2 / กุญแจ
    // ============================================================

    protected function disk(): Filesystem
    {
        $cfg = config('backup.r2');
        $missing = array_keys(array_filter([
            'BACKUP_R2_ACCOUNT_ID' => empty($cfg['account_id']),
            'BACKUP_R2_ACCESS_KEY_ID' => empty($cfg['access_key_id']),
            'BACKUP_R2_SECRET_ACCESS_KEY' => empty($cfg['secret_access_key']),
            'BACKUP_R2_BUCKET' => empty($cfg['bucket']),
        ]));
        if ($missing !== []) {
            throw new RuntimeException('ยังไม่ได้ตั้งค่า R2 ใน .env: '.implode(', ', $missing));
        }

        return Storage::build([
            'driver' => 's3',
            'key' => $cfg['access_key_id'],
            'secret' => $cfg['secret_access_key'],
            'region' => 'auto',
            'bucket' => $cfg['bucket'],
            'endpoint' => "https://{$cfg['account_id']}.r2.cloudflarestorage.com",
            'use_path_style_endpoint' => false,
            'throw' => true,
        ]);
    }

    protected function remoteKey(string $relativeKey): string
    {
        $prefix = trim((string) config('backup.r2.prefix'), '/');

        return ($prefix === '' ? '' : $prefix.'/').ltrim($relativeKey, '/');
    }

    /**
     * โหลดแม่กุญแจ — ต้องเป็น RSA ≥ 3072 บิต
     *
     * @return \OpenSSLAsymmetricKey
     */
    protected function publicKey()
    {
        $path = (string) config('backup.public_key_path');
        if (! is_file($path)) {
            throw new RuntimeException("ไม่พบแม่กุญแจ (public key) ที่ {$path} — ไม่สำรองแบบไม่เข้ารหัส");
        }

        $key = openssl_pkey_get_public((string) file_get_contents($path));
        $details = $key ? openssl_pkey_get_details($key) : false;
        if (! $details || ($details['type'] ?? null) !== OPENSSL_KEYTYPE_RSA || ($details['bits'] ?? 0) < 3072) {
            throw new RuntimeException("แม่กุญแจที่ {$path} ไม่ใช่ RSA public key ขนาด ≥ 3072 บิต");
        }

        return $key;
    }

    /**
     * ลายนิ้วมือของกุญแจ = sha256 ของ public key (DER) 16 ตัวแรก
     * scripts/backup/decrypt-backup.sh พิมพ์ค่าเดียวกันจาก private key ให้เทียบว่าคู่กัน
     */
    public function publicKeyFingerprint(): string
    {
        $pem = openssl_pkey_get_details($this->publicKey())['key'];
        $der = base64_decode(preg_replace('/-----[^-]+-----|\s+/', '', $pem));

        return substr(hash('sha256', $der), 0, 16);
    }

    // ============================================================
    // ตัวช่วย
    // ============================================================

    /**
     * กันรันซ้อน (ทั้งจาก scheduler และคนสั่งมือ) — งานเดียวกันได้ทีละตัว
     */
    protected function withLock(string $kind, callable $callback): array
    {
        $lock = Cache::lock("backup_r2:lock:{$kind}", 4 * 3600);
        if (! $lock->get()) {
            throw new RuntimeException("มีงานสำรอง {$kind} กำลังรันอยู่แล้ว");
        }

        try {
            return $callback();
        } finally {
            $lock->release();
        }
    }

    protected function makeWorkDir(string $kind): string
    {
        $base = (string) config('backup.work_dir');
        File::ensureDirectoryExists($base, 0700);

        // ลบโฟลเดอร์ของรอบที่ตายกลางทาง (เกิน 6 ชม. = ไม่มีใครใช้แล้ว เพราะ lock หมดก่อน)
        foreach (File::directories($base) as $dir) {
            if (filemtime($dir) < time() - self::STALE_WORK_HOURS * 3600) {
                $this->removeDir($dir);
            }
        }

        $free = disk_free_space($base);
        $need = (int) config('backup.min_free_gb', 8) * 1024 ** 3;
        if ($free !== false && $free < $need) {
            throw new RuntimeException('พื้นที่ว่างไม่พอ: เหลือ '.$this->human((int) $free).' ต้องการอย่างน้อย '.$this->human($need));
        }

        $dir = $base.'/'.$kind.'-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(3));
        File::ensureDirectoryExists($dir, 0700);

        return $dir;
    }

    protected function removeDir(string $dir): void
    {
        if (is_dir($dir)) {
            File::deleteDirectory($dir);
        }
    }

    /**
     * หาไฟล์โปรแกรม — cron ของ DirectAdmin มี /usr/local/bin ใน PATH แต่กันไว้เผื่อเครื่องอื่น
     */
    protected function binary(string $name): string
    {
        foreach (["/usr/local/bin/{$name}", "/usr/bin/{$name}", "/bin/{$name}"] as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        return $name;
    }

    protected function remember(string $kind, array $result): void
    {
        Cache::forever("backup_r2:last:{$kind}", [
            'ok' => true,
            'at' => now()->toIso8601String(),
            'size' => $result['size'] ?? null,
            'seconds' => $result['seconds'] ?? null,
        ]);
    }

    protected function log(string $message): void
    {
        if ($this->logger) {
            ($this->logger)($message);
        }
    }

    public function human(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $value = (float) $bytes;
        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }

        return round($value, $i >= 3 ? 2 : 1).' '.$units[$i];
    }
}
