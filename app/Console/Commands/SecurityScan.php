<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Process\Process;

/**
 * สแกนช่องโหว่/การตั้งค่าที่ไม่ปลอดภัยของ server + แอปนี้ (defensive, สแกนเฉพาะเครื่องตัวเอง)
 *
 * php artisan security:scan
 * php artisan security:scan --url=https://example.com --skip-deps --json
 */
class SecurityScan extends Command
{
    protected $signature = 'security:scan
        {--url= : URL ของเว็บที่จะ probe (default = APP_URL)}
        {--host= : host สำหรับตรวจ port (default = host ของ URL)}
        {--skip-deps : ข้าม composer/npm audit}
        {--skip-http : ข้ามการ probe HTTP/TLS}
        {--skip-ports : ข้ามการตรวจ port}
        {--json : แสดงผลเป็น JSON}';

    protected $description = 'สแกนช่องโหว่ของ server และแอป (config, ไฟล์, HTTP exposure, ports, dependencies, routes)';

    private array $findings = [];

    private const SEV = ['critical' => 4, 'high' => 3, 'medium' => 2, 'low' => 1, 'info' => 0];

    public function handle(): int
    {
        $url = rtrim($this->option('url') ?: config('app.url'), '/');
        $host = $this->option('host') ?: (parse_url($url, PHP_URL_HOST) ?: '127.0.0.1');

        $this->checkAppConfig();
        $this->checkPhp();
        $this->checkFiles();
        $this->checkRoutes();
        if (! $this->option('skip-http')) {
            $this->checkHttp($url);
            $this->checkTls($url);
        }
        if (! $this->option('skip-ports')) {
            $this->checkPorts($host);
        }
        if (! $this->option('skip-deps')) {
            $this->checkComposer();
            $this->checkNpm();
        }

        return $this->report();
    }

    private function add(string $sev, string $area, string $title, string $fix = ''): void
    {
        $this->findings[] = compact('sev', 'area', 'title', 'fix');
    }

    // ---------- App config ----------
    private function checkAppConfig(): void
    {
        if (config('app.debug')) {
            $this->add('high', 'config', 'APP_DEBUG=true — เปิดเผย stack trace/env', 'ตั้ง APP_DEBUG=false บน production');
        }
        if (config('app.env') !== 'production') {
            $this->add('low', 'config', 'APP_ENV='.config('app.env').' (ไม่ใช่ production)');
        }
        if (empty(config('app.key'))) {
            $this->add('critical', 'config', 'APP_KEY ว่าง — session/encryption ไม่ปลอดภัย', 'php artisan key:generate');
        }
        if (! str_starts_with((string) config('app.url'), 'https://')) {
            $this->add('medium', 'config', 'APP_URL ไม่ใช่ https', 'ใช้ HTTPS และบังคับ redirect');
        }
        if (! config('session.secure') && str_starts_with((string) config('app.url'), 'https://')) {
            $this->add('medium', 'config', 'SESSION_SECURE_COOKIE ไม่ได้เปิด', 'ตั้ง SESSION_SECURE_COOKIE=true');
        }
        if (config('session.http_only') === false) {
            $this->add('medium', 'config', 'session cookie ไม่ใช่ HttpOnly');
        }
        if (in_array(config('session.same_site'), [null, 'none'], true)) {
            $this->add('low', 'config', 'session SameSite ไม่ได้จำกัด (lax/strict)');
        }
        if (config('database.default') && config('database.connections.'.config('database.default').'.username') === 'root') {
            $this->add('medium', 'config', 'แอปเชื่อมต่อ DB ด้วย user root', 'สร้าง DB user สิทธิ์จำกัดเฉพาะ schema นี้');
        }
        if (config('database.default') && config('database.connections.'.config('database.default').'.password') === '') {
            $this->add('high', 'config', 'DB password ว่าง');
        }
    }

    // ---------- PHP ----------
    private function checkPhp(): void
    {
        if (version_compare(PHP_VERSION, '8.2.0', '<')) {
            $this->add('high', 'php', 'PHP '.PHP_VERSION.' หมดอายุ security support', 'อัปเกรดเป็น PHP 8.2+');
        }
        $on = fn (string $k) => in_array(strtolower((string) ini_get($k)), ['1', 'on', 'true', 'yes'], true);
        if ($on('display_errors') && config('app.env') === 'production') {
            $this->add('medium', 'php', 'display_errors=On', 'ปิดใน php.ini');
        }
        if ($on('expose_php')) {
            $this->add('low', 'php', 'expose_php=On — เผยเวอร์ชัน PHP ใน header', 'expose_php=Off');
        }
        if ($on('allow_url_include')) {
            $this->add('critical', 'php', 'allow_url_include=On (RFI)', 'allow_url_include=Off');
        }
        if (trim((string) ini_get('disable_functions')) === '') {
            $this->add('low', 'php', 'disable_functions ว่าง (exec/system/shell_exec เปิดอยู่)', 'ปิดฟังก์ชันที่ไม่ได้ใช้ถ้าไม่ต้องการ shell');
        }
        if (ini_get('open_basedir') === '' || ini_get('open_basedir') === false) {
            $this->add('info', 'php', 'ไม่ได้ตั้ง open_basedir');
        }
    }

    // ---------- Files ----------
    private function checkFiles(): void
    {
        $env = base_path('.env');
        if (is_file($env) && DIRECTORY_SEPARATOR === '/') {
            $perm = fileperms($env) & 0777;
            if ($perm & 0007) {
                $this->add('high', 'files', sprintf('.env permission %o — คนอื่นอ่านได้', $perm), 'chmod 640 .env');
            }
        }
        foreach (['.git', '.env', 'composer.json', 'composer.lock', 'phpunit.xml', 'artisan'] as $f) {
            if (file_exists(public_path($f))) {
                $this->add('critical', 'files', "พบ $f ใน public/ — เข้าถึงได้จากเว็บ", "ลบ public/$f");
            }
        }
        if (DIRECTORY_SEPARATOR === '/') {
            foreach (['storage', 'bootstrap/cache', 'app', 'config', 'routes'] as $d) {
                $p = base_path($d);
                if (is_dir($p) && (fileperms($p) & 0002)) {
                    $this->add('high', 'files', "$d เป็น world-writable", "chmod o-w $d");
                }
            }
            // สแกนหาไฟล์ .php ใน storage/public uploads (web shell ที่อาจถูกอัปโหลด)
            foreach ([storage_path('app/public'), public_path('uploads'), public_path('storage')] as $dir) {
                if (! is_dir($dir)) {
                    continue;
                }
                $it = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
                );
                $n = 0;
                foreach ($it as $file) {
                    if ($file->isFile() && preg_match('/\.(php\d?|phtml|phar)$/i', $file->getFilename())) {
                        $this->add('critical', 'files', 'พบไฟล์สั่งรันได้ในโฟลเดอร์อัปโหลด: '.$file->getPathname(), 'ตรวจสอบ/ลบ และบล็อกการรัน PHP ใน path นั้น');
                        if (++$n >= 20) {
                            break;
                        }
                    }
                }
            }
        }
        if (is_file(storage_path('logs/laravel.log')) && filesize(storage_path('logs/laravel.log')) > 200 * 1024 * 1024) {
            $this->add('low', 'files', 'laravel.log ใหญ่เกิน 200MB', 'ใช้ daily log + rotate');
        }
    }

    // ---------- Routes ----------
    private function checkRoutes(): void
    {
        $sensitive = '#(^|/)(admin|dashboard|telescope|horizon|debugbar|_ignition|phpinfo|log-viewer|pulse)(/|$)#i';
        $count = 0;
        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            if (! preg_match($sensitive, $uri)) {
                continue;
            }
            // gatherMiddleware() สร้าง controller จริง ซึ่ง constructor บางตัวโยน exception เมื่อตั้งค่าไม่ครบ
            // (เช่น KnowledgeBaseController ถ้าไม่มี provider OpenAI) ต้องไม่ทำให้ทั้งการสแกนล้ม
            try {
                $mw = implode(',', $route->gatherMiddleware());
            } catch (\Throwable) {
                $mw = implode(',', $route->middleware());
            }
            // juntra.user / juntra.server ยืนยันตัวตนเอง · /auth/ คือขั้นตอนก่อนล็อกอิน (เช่น /api/admin/auth/verify-2fa)
            if (! preg_match('/auth|admin|can:|role|permission|sanctum|password\.confirm|signed|throttle:login|juntra\.(user|server)/i', $mw)
                && ! preg_match('#/(auth|login|logout|register|password|forgot)(/|$)#i', $uri)) {
                if ($count++ < 25) {
                    $this->add('high', 'routes', "route อ่อนไหวที่ไม่มี auth middleware: /$uri [".implode('|', $route->methods()).']', 'เพิ่ม auth/admin middleware');
                }
            }
        }
        if ($count > 25) {
            $this->add('high', 'routes', '...และอีก '.($count - 25).' route ที่ไม่มี auth middleware');
        }
    }

    // ---------- HTTP ----------
    private function checkHttp(string $url): void
    {
        $probe = [
            '/.env' => 'critical', '/.git/HEAD' => 'critical', '/.git/config' => 'critical',
            '/storage/logs/laravel.log' => 'critical', '/composer.json' => 'medium',
            '/composer.lock' => 'medium', '/vendor/composer/installed.json' => 'high',
            '/phpinfo.php' => 'high', '/info.php' => 'high', '/telescope' => 'high',
            '/horizon' => 'medium', '/_ignition/health-check' => 'high', '/.DS_Store' => 'low',
            '/backup.sql' => 'critical', '/db.sql' => 'critical', '/dump.sql' => 'critical',
            '/server-status' => 'medium', '/phpmyadmin/' => 'medium', '/adminer.php' => 'high',
        ];
        try {
            $base = Http::timeout(10)->withoutVerifying()->withOptions(['allow_redirects' => false])->get($url.'/');
        } catch (\Throwable $e) {
            $this->add('info', 'http', "เชื่อมต่อ $url ไม่ได้ — ข้ามการ probe HTTP (".$e->getMessage().')');

            return;
        }

        $h = array_change_key_case($base->headers(), CASE_LOWER);
        $want = [
            'strict-transport-security' => ['medium', 'ไม่มี HSTS'],
            'x-content-type-options' => ['low', 'ไม่มี X-Content-Type-Options: nosniff'],
            'x-frame-options' => ['low', 'ไม่มี X-Frame-Options / frame-ancestors (clickjacking)'],
            'content-security-policy' => ['low', 'ไม่มี Content-Security-Policy'],
            'referrer-policy' => ['info', 'ไม่มี Referrer-Policy'],
        ];
        foreach ($want as $k => [$sev, $msg]) {
            if (! isset($h[$k])) {
                if ($k === 'x-frame-options' && isset($h['content-security-policy']) && str_contains(implode('', $h['content-security-policy']), 'frame-ancestors')) {
                    continue;
                }
                $this->add($sev, 'http', $msg, "เพิ่ม header $k");
            }
        }
        foreach (['server', 'x-powered-by'] as $k) {
            if (isset($h[$k]) && preg_match('/\d+\.\d+/', implode(' ', $h[$k]))) {
                $this->add('low', 'http', "header $k เผยเวอร์ชัน: ".implode(' ', $h[$k]), 'ซ่อน version (server_tokens off / expose_php off)');
            }
        }
        if (str_starts_with($url, 'https://')) {
            try {
                $r = Http::timeout(8)->withOptions(['allow_redirects' => false])->get('http://'.substr($url, 8).'/');
                if (! in_array($r->status(), [301, 302, 307, 308], true)) {
                    $this->add('medium', 'http', 'HTTP (80) ไม่ redirect ไป HTTPS', 'บังคับ redirect 80→443');
                }
            } catch (\Throwable) {
                // port 80 ปิด — ไม่เป็นปัญหา
            }
        }

        $baseBody = substr((string) $base->body(), 0, 200);
        foreach ($probe as $path => $sev) {
            try {
                $r = Http::timeout(8)->withoutVerifying()->withOptions(['allow_redirects' => false])->get($url.$path);
            } catch (\Throwable) {
                continue;
            }
            if ($r->status() !== 200) {
                continue;
            }
            $body = (string) $r->body();
            if ($body === '' || substr($body, 0, 200) === $baseBody) {
                continue; // SPA/catch-all ตอบหน้าแรก
            }
            if ($path === '/.git/HEAD' && ! str_contains($body, 'ref:')) {
                continue;
            }
            if ($path === '/.env' && ! preg_match('/^[A-Z_]+=/m', $body)) {
                continue;
            }
            $this->add($sev, 'http', "เข้าถึงได้จากภายนอก: $path (HTTP 200)", 'บล็อกใน web server / ลบไฟล์');
        }

        // directory listing
        foreach (['/storage/', '/uploads/', '/vendor/'] as $p) {
            try {
                $r = Http::timeout(8)->withoutVerifying()->get($url.$p);
                if ($r->ok() && preg_match('/<title>Index of/i', $r->body())) {
                    $this->add('medium', 'http', "directory listing เปิดอยู่: $p", 'autoindex off / Options -Indexes');
                }
            } catch (\Throwable) {
            }
        }

        // CORS wildcard พร้อม credentials
        try {
            $r = Http::timeout(8)->withoutVerifying()->withHeaders(['Origin' => 'https://evil.example'])->get($url.'/');
            $ao = $r->header('Access-Control-Allow-Origin');
            if ($ao === 'https://evil.example' && strtolower($r->header('Access-Control-Allow-Credentials')) === 'true') {
                $this->add('high', 'http', 'CORS สะท้อน Origin ใดก็ได้ พร้อม credentials', 'จำกัด allowed_origins');
            }
        } catch (\Throwable) {
        }
    }

    private function checkTls(string $url): void
    {
        if (! str_starts_with($url, 'https://')) {
            return;
        }
        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT) ?: 443;
        $ctx = stream_context_create(['ssl' => ['capture_peer_cert' => true, 'verify_peer' => false, 'verify_peer_name' => false, 'SNI_enabled' => true, 'peer_name' => $host]]);
        $s = @stream_socket_client("ssl://$host:$port", $en, $es, 8, STREAM_CLIENT_CONNECT, $ctx);
        if (! $s) {
            $this->add('info', 'tls', "ต่อ TLS $host:$port ไม่ได้");

            return;
        }
        $cert = openssl_x509_parse(stream_context_get_params($s)['options']['ssl']['peer_certificate'] ?? '');
        fclose($s);
        if (! $cert) {
            return;
        }
        $days = (int) floor(($cert['validTo_time_t'] - time()) / 86400);
        if ($days < 0) {
            $this->add('critical', 'tls', "ใบรับรอง SSL หมดอายุแล้ว ($days วัน)", 'ต่ออายุทันที');
        } elseif ($days < 14) {
            $this->add('high', 'tls', "ใบรับรอง SSL จะหมดอายุใน $days วัน", 'ต่ออายุ');
        } elseif ($days < 30) {
            $this->add('medium', 'tls', "ใบรับรอง SSL จะหมดอายุใน $days วัน");
        }
        foreach (['tlsv1.0' => STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT, 'tlsv1.1' => STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT] as $name => $m) {
            $c = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
            $t = @stream_socket_client("tcp://$host:$port", $en, $es, 5, STREAM_CLIENT_CONNECT, $c);
            if ($t && @stream_socket_enable_crypto($t, true, $m)) {
                $this->add('medium', 'tls', "server ยังรองรับ $name", 'ปิด TLS < 1.2');
            }
            if ($t) {
                fclose($t);
            }
        }
    }

    // ---------- Ports ----------
    private function checkPorts(string $host): void
    {
        $services = [
            21 => ['medium', 'FTP'], 23 => ['critical', 'Telnet'], 25 => ['info', 'SMTP'],
            3306 => ['high', 'MySQL'], 5432 => ['high', 'PostgreSQL'], 6379 => ['critical', 'Redis'],
            11211 => ['high', 'Memcached'], 27017 => ['critical', 'MongoDB'], 9200 => ['critical', 'Elasticsearch'],
            2375 => ['critical', 'Docker API (ไม่เข้ารหัส)'], 5900 => ['high', 'VNC'], 3389 => ['high', 'RDP'],
            445 => ['high', 'SMB'], 8080 => ['info', 'HTTP-alt'], 9000 => ['medium', 'PHP-FPM/อื่น ๆ'],
            6001 => ['info', 'WebSocket'],
        ];
        $isLocal = in_array($host, ['127.0.0.1', 'localhost', '::1'], true);
        foreach ($services as $port => [$sev, $name]) {
            $c = @fsockopen($host, $port, $en, $es, 1.5);
            if ($c) {
                fclose($c);
                if ($sev === 'info') {
                    $this->add('info', 'ports', "port $port ($name) เปิดอยู่");
                } elseif ($isLocal) {
                    $this->add('info', 'ports', "port $port ($name) เปิดบน localhost — ตรวจว่า bind เฉพาะ 127.0.0.1 ด้วย `ss -tlnp`");
                } else {
                    $this->add($sev, 'ports', "port $port ($name) เปิดรับจากภายนอก", 'ปิดด้วย firewall / bind 127.0.0.1');
                }
            }
        }
        if ($isLocal) {
            $this->add('info', 'ports', 'ตรวจจาก localhost — รันด้วย --host=<IP สาธารณะ> จากเครื่องอื่นเพื่อดูมุมมองภายนอกจริง');
        }
    }

    // ---------- Dependencies ----------
    private function runProcess(array $cmd, int $timeout = 120): ?string
    {
        try {
            $p = new Process($cmd, base_path(), null, null, $timeout);
            $p->run();

            return $p->getOutput() ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function checkComposer(): void
    {
        $out = $this->runProcess(['composer', 'audit', '--format=json', '--no-interaction']);
        $j = $out ? json_decode($out, true) : null;
        if (! is_array($j)) {
            $this->add('info', 'deps', 'รัน composer audit ไม่ได้ (ไม่พบ composer หรือ offline)');

            return;
        }
        foreach ($j['advisories'] ?? [] as $pkg => $list) {
            foreach ($list as $a) {
                $sev = match (strtolower($a['severity'] ?? '')) {
                    'critical' => 'critical', 'high' => 'high', 'low' => 'low', default => 'medium'
                };
                $this->add($sev, 'composer', "$pkg: ".($a['title'] ?? '').' ('.($a['cve'] ?? $a['advisoryId'] ?? '').')', 'composer update '.$pkg);
            }
        }
        foreach ($j['abandoned'] ?? [] as $pkg => $rep) {
            $this->add('low', 'composer', "$pkg ถูก abandon".($rep ? " → ใช้ $rep" : ''));
        }
    }

    private function checkNpm(): void
    {
        if (! is_file(base_path('package-lock.json'))) {
            return;
        }
        $out = $this->runProcess(['npm', 'audit', '--json', '--omit=dev'], 120);
        $j = $out ? json_decode($out, true) : null;
        if (! is_array($j) || ! isset($j['vulnerabilities'])) {
            $this->add('info', 'deps', 'รัน npm audit ไม่ได้');

            return;
        }
        foreach ($j['vulnerabilities'] as $name => $v) {
            $sev = in_array($v['severity'] ?? '', ['critical', 'high', 'moderate', 'low'], true)
                ? ($v['severity'] === 'moderate' ? 'medium' : $v['severity']) : 'low';
            $this->add($sev, 'npm', "$name ({$v['severity']})", ! empty($v['fixAvailable']) ? 'npm audit fix' : '');
        }
    }

    // ---------- Report ----------
    private function report(): int
    {
        usort($this->findings, fn ($a, $b) => self::SEV[$b['sev']] <=> self::SEV[$a['sev']]);
        $counts = array_fill_keys(array_keys(self::SEV), 0);
        foreach ($this->findings as $f) {
            $counts[$f['sev']]++;
        }

        if ($this->option('json')) {
            $this->line(json_encode(['summary' => $counts, 'findings' => $this->findings], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        } else {
            $colors = ['critical' => 'red', 'high' => 'red', 'medium' => 'yellow', 'low' => 'cyan', 'info' => 'gray'];
            foreach ($this->findings as $f) {
                $c = $colors[$f['sev']];
                $this->line(sprintf('<fg=%s>[%-8s]</> %-8s %s', $c, strtoupper($f['sev']), $f['area'], $f['title']));
                if ($f['fix']) {
                    $this->line('           ↳ แก้: '.$f['fix']);
                }
            }
            $this->newLine();
            $this->info('สรุป: '.collect($counts)->map(fn ($n, $k) => "$k=$n")->implode('  '));
        }

        return ($counts['critical'] + $counts['high']) > 0 ? self::FAILURE : self::SUCCESS;
    }
}
