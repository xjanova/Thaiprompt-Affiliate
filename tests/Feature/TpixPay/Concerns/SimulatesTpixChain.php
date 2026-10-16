<?php

namespace Tests\Feature\TpixPay\Concerns;

use App\Services\TpixPay\TpixAmount;
use App\Services\TpixPay\TpixPaySettings;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * จำลองเชน TPIX 4289 (RPC + explorer + อัตราแลกเปลี่ยน) ด้วย Http::fake — ชุดเดียวกับ TpixPaymentFlowTest
 *
 * ราคาจำลอง: พูล 199.9 TPIX / 19.99 USDT = 0.10 USD × 32.5 บาท = 3.25 บาทต่อ TPIX
 * เพดานสภาพคล่อง (max_pool_ratio 5) = 19.99 × 32.5 × 5 ≈ 3,248 บาทต่อรายการ
 */
trait SimulatesTpixChain
{
    protected const TPIX_RECEIVE = '0x1111111111111111111111111111111111111111';

    /** @var array{latest: int, txs: array<string, array<string, mixed>>, explorer: array<int, array<string, mixed>>, fx_ok: bool, blocks: array<int, int>} */
    protected array $chain;

    /**
     * เปิดรับ TPIX + จำลองเชน + ประวัติราคา (เรียกใน setUp)
     *
     * @param  array<string, mixed>  $settings
     */
    protected function bootTpixChain(array $settings = []): void
    {
        Cache::flush();
        config(['tpix_pay.allow_network_in_tests' => true]);
        $this->chain = ['latest' => 1000, 'txs' => [], 'explorer' => [], 'fx_ok' => true, 'blocks' => []];
        $this->fakeTpixNetwork();

        app(TpixPaySettings::class)->save(array_merge([
            'enabled' => true,
            'show_prices' => true,
            'receive_address' => self::TPIX_RECEIVE,
            'confirmations' => 3,
            'max_per_payment_thb' => 20000,
            'max_per_user_day_thb' => 50000,
            'max_pool_ratio' => 5,
        ], $settings));

        foreach ([3, 2, 1] as $minutesAgo) {
            \App\Models\TpixPriceSnapshot::create([
                'price_usd' => '0.1',
                'usd_thb' => '32.5',
                'price_thb' => '3.25',
                'reserve_tpix' => '199.9',
                'reserve_usdt' => '19.99',
                'source' => 'dex',
                'created_at' => now()->subMinutes($minutesAgo),
            ]);
        }
    }

    /** โอนเหรียญ (ธุรกรรมสำเร็จ เข้าบล็อกแล้ว) → เลขธุรกรรม */
    protected function sendTpixTx(string $to, string $valueWei, int $confirmations = 5, string $status = '0x1'): string
    {
        $hash = '0x'.bin2hex(random_bytes(32));
        $block = $this->chain['latest'] - $confirmations + 1;
        $this->chain['blocks'][$block] = $this->chain['blocks'][$block] ?? now()->getTimestamp();
        $this->chain['txs'][$hash] = [
            'hash' => $hash,
            'from' => '0x9999999999999999999999999999999999999999',
            'to' => $to,
            'value' => TpixAmount::decToHex($valueWei),
            'blockNumber' => '0x'.dechex($block),
            'chainId' => '0x10c1',
            'status' => $status,
        ];

        return $hash;
    }

    private function fakeTpixNetwork(): void
    {
        Http::fake(function (Request $request) {
            $url = $request->url();

            if (str_contains($url, 'er-api.com') || str_contains($url, 'exchangerate-api.com')) {
                return $this->chain['fx_ok'] ? Http::response(['rates' => ['THB' => 32.5]]) : Http::response('down', 503);
            }

            if (str_contains($url, 'explorer.tpix.online')) {
                return Http::response(['items' => $this->chain['explorer'], 'next_page_params' => null]);
            }

            if (str_contains($url, 'rpc')) {
                $body = $request->data();

                if (array_is_list($body)) {
                    return Http::response(array_map(
                        fn ($call) => ['jsonrpc' => '2.0', 'id' => $call['id'], 'result' => $this->tpixRpcResult($call['method'], $call['params'])],
                        $body
                    ));
                }

                return Http::response(['jsonrpc' => '2.0', 'id' => 1, 'result' => $this->tpixRpcResult($body['method'] ?? '', $body['params'] ?? [])]);
            }

            return Http::response([], 404);
        });
    }

    private function tpixRpcResult(string $method, array $params): mixed
    {
        $tx = isset($params[0]) && is_string($params[0]) ? ($this->chain['txs'][$params[0]] ?? null) : null;

        return match ($method) {
            'eth_chainId' => '0x10c1',
            'eth_blockNumber' => '0x'.dechex($this->chain['latest']),
            'eth_call' => $this->tpixEthCall($params[0]['data'] ?? ''),
            'eth_getTransactionByHash' => $tx ? array_diff_key($tx, ['status' => 1]) : null,
            'eth_getTransactionReceipt' => $tx && $tx['blockNumber'] ? ['status' => $tx['status'], 'blockNumber' => $tx['blockNumber']] : null,
            'eth_getBlockByNumber' => $this->tpixBlock(hexdec($params[0]), (bool) ($params[1] ?? false)),
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function tpixBlock(int $number, bool $full): array
    {
        $block = ['number' => '0x'.dechex($number), 'timestamp' => '0x'.dechex($this->chain['blocks'][$number] ?? now()->getTimestamp())];
        if ($full) {
            $block['transactions'] = array_values(array_map(
                fn ($tx) => array_diff_key($tx, ['status' => 1]),
                array_filter($this->chain['txs'], fn ($tx) => $tx['blockNumber'] !== null && hexdec($tx['blockNumber']) === $number)
            ));
        }

        return $block;
    }

    private function tpixEthCall(string $data): string
    {
        if ($data === '0x0dfe1681') { // token0() = WTPIX
            return '0x'.str_pad(substr(strtolower((string) config('tpix_pay.dex.wtpix')), 2), 64, '0', STR_PAD_LEFT);
        }

        $r0 = substr(TpixAmount::decToHex('199900000000000000000'), 2);
        $r1 = substr(TpixAmount::decToHex('19990000'), 2);

        return '0x'.str_pad($r0, 64, '0', STR_PAD_LEFT).str_pad($r1, 64, '0', STR_PAD_LEFT).str_pad(dechex(time()), 64, '0', STR_PAD_LEFT);
    }
}
