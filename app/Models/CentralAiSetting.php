<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Http;

/**
 * Central AI Setting Model
 *
 * จัดการการตั้งค่ารวมของระบบ Central AI
 * สำหรับควบคุม Ollama และ AI Providers อื่นๆ
 *
 * @property int $id
 * @property string $name ชื่อการตั้งค่า
 * @property bool $setup_completed การติดตั้งเสร็จสมบูรณ์
 * @property int $setup_step ขั้นตอนการติดตั้งปัจจุบัน
 * @property bool $is_active สถานะการใช้งาน
 * @property bool $is_ollama_installed Ollama ติดตั้งแล้ว
 * @property string|null $ollama_version เวอร์ชันของ Ollama
 * @property string $ollama_status สถานะของ Ollama
 * @property string $ollama_host Ollama host
 * @property int $ollama_port Ollama port
 * @property string $default_ollama_model โมเดลเริ่มต้น
 * @property array|null $installed_models รายการโมเดลที่ติดตั้ง
 * @property bool $postxagent_enabled เปิดใช้งาน PostXAgent
 * @property string $postxagent_host PostXAgent host
 * @property int $postxagent_api_port PostXAgent API port
 * @property int $postxagent_signalr_port PostXAgent SignalR port
 * @property string|null $postxagent_api_key PostXAgent API key
 * @property string $postxagent_status สถานะของ PostXAgent
 * @property string $postxagent_preferred_provider Provider ที่ต้องการใช้
 * @property int|null $cpu_cores จำนวน CPU cores
 * @property float|null $ram_gb RAM ทั้งหมด
 * @property float|null $disk_gb พื้นที่ disk ทั้งหมด
 * @property float|null $disk_available_gb พื้นที่ disk ว่าง
 * @property string|null $os_type ระบบปฏิบัติการ
 * @property bool $enable_rag เปิดใช้งาน RAG
 * @property string $embedding_provider Provider สำหรับ embeddings
 * @property string $embedding_model โมเดลสำหรับ embeddings
 * @property float $similarity_threshold Threshold สำหรับ similarity
 * @property int $max_context_chunks จำนวน chunks สูงสุด
 * @property bool $auto_reply_enabled เปิดตอบกลับอัตโนมัติ
 * @property int $response_max_tokens จำนวน tokens สูงสุด
 * @property float $default_temperature Temperature เริ่มต้น
 * @property string|null $fallback_message ข้อความสำรอง
 * @property string|null $system_prompt System prompt สำหรับกำหนดขอบเขตการตอบของ AI
 * @property int $total_requests จำนวนคำขอทั้งหมด
 * @property int $successful_requests จำนวนคำขอที่สำเร็จ
 * @property int $failed_requests จำนวนคำขอที่ล้มเหลว
 * @property \Carbon\Carbon|null $last_health_check_at เวลา health check ล่าสุด
 * @property array|null $health_check_result ผลลัพธ์ health check
 * @property array|null $extra_config การตั้งค่าเพิ่มเติม
 * @property string|null $notes หมายเหตุ
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class CentralAiSetting extends Model
{
    use SoftDeletes;

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'central_ai_settings';

    /**
     * ฟิลด์ที่สามารถ mass assign ได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'setup_completed',
        'setup_step',
        'is_active',
        'is_ollama_installed',
        'ollama_version',
        'ollama_status',
        'ollama_host',
        'ollama_port',
        'default_ollama_model',
        'installed_models',
        'postxagent_enabled',
        'postxagent_host',
        'postxagent_api_port',
        'postxagent_signalr_port',
        'postxagent_api_key',
        'postxagent_status',
        'postxagent_preferred_provider',
        'cpu_cores',
        'ram_gb',
        'disk_gb',
        'disk_available_gb',
        'os_type',
        'enable_rag',
        'embedding_provider',
        'embedding_model',
        'similarity_threshold',
        'max_context_chunks',
        'auto_reply_enabled',
        'response_max_tokens',
        'default_temperature',
        'fallback_message',
        'system_prompt',
        'total_requests',
        'successful_requests',
        'failed_requests',
        'last_health_check_at',
        'health_check_result',
        'extra_config',
        'notes',
    ];

    /**
     * ประเภทข้อมูลของแต่ละฟิลด์
     *
     * @var array<string, string>
     */
    protected $casts = [
        'setup_completed' => 'boolean',
        'setup_step' => 'integer',
        'is_active' => 'boolean',
        'is_ollama_installed' => 'boolean',
        'ollama_port' => 'integer',
        'installed_models' => 'array',
        'postxagent_enabled' => 'boolean',
        'postxagent_api_port' => 'integer',
        'postxagent_signalr_port' => 'integer',
        'cpu_cores' => 'integer',
        'ram_gb' => 'decimal:2',
        'disk_gb' => 'decimal:2',
        'disk_available_gb' => 'decimal:2',
        'enable_rag' => 'boolean',
        'similarity_threshold' => 'decimal:2',
        'max_context_chunks' => 'integer',
        'auto_reply_enabled' => 'boolean',
        'response_max_tokens' => 'integer',
        'default_temperature' => 'decimal:2',
        'total_requests' => 'integer',
        'successful_requests' => 'integer',
        'failed_requests' => 'integer',
        'last_health_check_at' => 'datetime',
        'health_check_result' => 'array',
        'extra_config' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * ดึงการตั้งค่าที่ active (Singleton pattern)
     *
     * @return self
     */
    public static function getActive(): self
    {
        $setting = self::where('is_active', true)->first();

        // ถ้าไม่มี สร้างใหม่
        if (!$setting) {
            $setting = self::create([
                'name' => 'Central AI',
                'is_active' => true,
                'fallback_message' => 'กรุณาติดต่อเจ้าหน้าที่',
            ]);
        }

        return $setting;
    }

    /**
     * ตรวจสอบสถานะ Ollama
     *
     * @return array{status: string, version: string|null, models: array}
     */
    public function checkOllamaStatus(): array
    {
        try {
            $url = "http://{$this->ollama_host}:{$this->ollama_port}/api/version";
            $response = Http::timeout(5)->get($url);

            if ($response->successful()) {
                $version = $response->json('version');

                // ดึงรายการ models (เก็บข้อมูลครบทุกฟิลด์)
                $modelsResponse = Http::timeout(5)->get(
                    "http://{$this->ollama_host}:{$this->ollama_port}/api/tags"
                );

                $models = [];
                if ($modelsResponse->successful()) {
                    $models = collect($modelsResponse->json('models', []))
                        ->map(function ($model) {
                            return [
                                'name' => $model['name'] ?? 'unknown',
                                'size' => $model['size'] ?? 0,
                                'digest' => $model['digest'] ?? null,
                                'modified_at' => $model['modified_at'] ?? null,
                                'details' => $model['details'] ?? [],
                            ];
                        })
                        ->toArray();
                }

                // อัพเดทสถานะ
                $this->update([
                    'ollama_status' => 'running',
                    'ollama_version' => $version,
                    'is_ollama_installed' => true,
                    'installed_models' => $models,
                ]);

                return [
                    'status' => 'running',
                    'version' => $version,
                    'models' => $models,
                ];
            }

            $this->update(['ollama_status' => 'stopped']);

            return [
                'status' => 'stopped',
                'version' => null,
                'models' => [],
            ];
        } catch (\Exception $e) {
            // หมายเหตุ: ถ้า Ollama ติดตั้งแล้วแต่หยุดอยู่ (connection refused)
            // ไม่ควรเซ็ต is_ollama_installed = false
            // เพราะจะทำให้เมนู/wizard คิดว่ายังไม่ได้ติดตั้ง
            $updateData = ['ollama_status' => 'stopped'];

            // ถ้ายังไม่เคยมาร์คว่าติดตั้งแล้ว ให้ลองเช็คผ่าน binary
            if (!$this->is_ollama_installed) {
                $updateData['is_ollama_installed'] = false;
            }

            $this->update($updateData);

            return [
                'status' => 'stopped',
                'version' => null,
                'models' => $this->installed_models ?? [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * ตรวจสอบสถานะ PostXAgent
     *
     * @return array{status: string, providers: array, error: string|null}
     */
    public function checkPostXAgentStatus(): array
    {
        if (!$this->postxagent_enabled) {
            return [
                'status' => 'not_configured',
                'providers' => [],
                'error' => null,
            ];
        }

        try {
            $url = "http://{$this->postxagent_host}:{$this->postxagent_api_port}/api/v1/status";

            $request = Http::timeout(5);

            if ($this->postxagent_api_key) {
                $request->withHeaders([
                    'X-API-Key' => $this->postxagent_api_key,
                ]);
            }

            $response = $request->get($url);

            if ($response->successful()) {
                $providers = $response->json('available_providers', []);

                $this->update([
                    'postxagent_status' => 'running',
                ]);

                return [
                    'status' => 'running',
                    'providers' => $providers,
                    'error' => null,
                ];
            }

            $this->update(['postxagent_status' => 'stopped']);

            return [
                'status' => 'stopped',
                'providers' => [],
                'error' => 'PostXAgent ไม่ตอบสนอง',
            ];
        } catch (\Exception $e) {
            $this->update(['postxagent_status' => 'error']);

            return [
                'status' => 'error',
                'providers' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * รันการตรวจสอบ health check ครบทุกระบบ
     *
     * @return array
     */
    public function performHealthCheck(): array
    {
        $ollamaStatus = $this->checkOllamaStatus();
        $postxagentStatus = $this->checkPostXAgentStatus();

        $result = [
            'timestamp' => now(),
            'ollama' => $ollamaStatus,
            'postxagent' => $postxagentStatus,
            'overall_status' => $this->determineOverallStatus($ollamaStatus, $postxagentStatus),
        ];

        $this->update([
            'last_health_check_at' => now(),
            'health_check_result' => $result,
        ]);

        return $result;
    }

    /**
     * กำหนดสถานะโดยรวม
     *
     * @param array $ollamaStatus
     * @param array $postxagentStatus
     * @return string
     */
    protected function determineOverallStatus(array $ollamaStatus, array $postxagentStatus): string
    {
        $ollamaOk = $ollamaStatus['status'] === 'running';
        $postxagentOk = !$this->postxagent_enabled || $postxagentStatus['status'] === 'running';

        if ($ollamaOk && $postxagentOk) {
            return 'healthy';
        }

        if ($ollamaOk || $postxagentOk) {
            return 'partial';
        }

        return 'unhealthy';
    }

    /**
     * นับคำขอเพิ่มขึ้น (สำหรับสถิติ)
     *
     * @param bool $success สำเร็จหรือไม่
     * @return void
     */
    public function incrementRequest(bool $success = true): void
    {
        $this->increment('total_requests');

        if ($success) {
            $this->increment('successful_requests');
        } else {
            $this->increment('failed_requests');
        }
    }

    /**
     * คำนวณอัตราความสำเร็จ
     *
     * @return float
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->total_requests === 0) {
            return 0;
        }

        return round(($this->successful_requests / $this->total_requests) * 100, 2);
    }

    /**
     * ดึง Ollama URL สมบูรณ์
     *
     * @return string
     */
    public function getOllamaUrlAttribute(): string
    {
        return "http://{$this->ollama_host}:{$this->ollama_port}";
    }

    /**
     * ดึง PostXAgent URL สมบูรณ์
     *
     * @return string
     */
    public function getPostxagentUrlAttribute(): string
    {
        return "http://{$this->postxagent_host}:{$this->postxagent_api_port}";
    }
}
