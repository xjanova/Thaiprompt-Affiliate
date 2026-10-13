<?php

namespace App\Services\TpixPay;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * JSON-RPC ของเชน TPIX (4289) — ยิงตัวหลัก ล้มแล้วลองตัวสำรอง
 *
 * ❗ ต้องส่ง User-Agent เสมอ: Cloudflare หน้า rpc.tpix.online ตอบ 403/429 กับคำขอที่ไม่มี UA
 * ❗ ข้อความ error ห้ามมี URL/คีย์ — ผู้เรียกบางจุดส่งต่อให้ผู้ใช้เห็น
 */
class TpixRpcClient
{
    /**
     * เรียกเมธอด JSON-RPC แล้วคืน result (null = โหนดตอบ null เช่น หาธุรกรรมไม่เจอ)
     *
     * @param  array<int, mixed>  $params
     *
     * @throws RuntimeException เมื่อติดต่อโหนดไม่ได้ทุกตัว หรือโหนดตอบ error
     */
    public function call(string $method, array $params = []): mixed
    {
        $urls = array_values(array_filter([
            (string) config('tpix_pay.rpc_url'),
            (string) config('tpix_pay.rpc_backup_url'),
        ]));

        $lastError = 'ไม่มี RPC ที่ตั้งค่าไว้';
        foreach ($urls as $url) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => (string) config('tpix_pay.user_agent'),
                    'Accept' => 'application/json',
                ])
                    ->timeout((int) config('tpix_pay.http_timeout', 8))
                    ->post($url, [
                        'jsonrpc' => '2.0',
                        'id' => 1,
                        'method' => $method,
                        'params' => $params,
                    ]);
            } catch (\Throwable $e) {
                $lastError = 'ติดต่อโหนดเชน TPIX ไม่ได้';

                continue;
            }

            if (! $response->successful()) {
                $lastError = 'โหนดเชน TPIX ตอบ HTTP '.$response->status();

                continue;
            }

            $json = $response->json();
            if (! is_array($json)) {
                $lastError = 'โหนดเชน TPIX ตอบรูปแบบไม่ถูกต้อง';

                continue;
            }

            if (isset($json['error'])) {
                // error ระดับเมธอด (เช่น พารามิเตอร์ผิด) — ตัวสำรองก็จะตอบเหมือนกัน ไม่ต้องลองต่อ
                throw new RuntimeException('โหนดเชน TPIX ปฏิเสธคำขอ: '.mb_substr((string) ($json['error']['message'] ?? 'unknown'), 0, 120));
            }

            return $json['result'] ?? null;
        }

        throw new RuntimeException($lastError);
    }

    /** chain id ที่โหนดรายงาน (ใช้ตรวจว่าชี้เชนถูก) */
    public function chainId(): int
    {
        return (int) TpixAmount::hexToDec((string) $this->call('eth_chainId'));
    }

    public function blockNumber(): int
    {
        return (int) TpixAmount::hexToDec((string) $this->call('eth_blockNumber'));
    }

    /**
     * eth_call แบบอ่านอย่างเดียว คืน hex ผลลัพธ์
     */
    public function ethCall(string $to, string $data): string
    {
        return (string) $this->call('eth_call', [['to' => $to, 'data' => $data], 'latest']);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getTransactionByHash(string $hash): ?array
    {
        $tx = $this->call('eth_getTransactionByHash', [$hash]);

        return is_array($tx) ? $tx : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getTransactionReceipt(string $hash): ?array
    {
        $receipt = $this->call('eth_getTransactionReceipt', [$hash]);

        return is_array($receipt) ? $receipt : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getBlockByNumber(int $number): ?array
    {
        $block = $this->call('eth_getBlockByNumber', ['0x'.dechex($number), false]);

        return is_array($block) ? $block : null;
    }
}
