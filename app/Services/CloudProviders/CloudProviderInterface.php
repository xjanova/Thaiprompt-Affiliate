<?php

namespace App\Services\CloudProviders;

use App\Models\AiRentalCloudConfig;
use App\Models\AiRentalModel;

/**
 * Cloud Provider Interface
 *
 * Interface สำหรับ Cloud GPU Providers ทุกตัว
 * ทุก Provider ต้อง implement interface นี้
 */
interface CloudProviderInterface
{
    /**
     * ทดสอบการเชื่อมต่อกับ Provider
     *
     * @param AiRentalCloudConfig $config
     * @return array ['success' => bool, 'message' => string]
     */
    public function testConnection(AiRentalCloudConfig $config): array;

    /**
     * Deploy AI Model บน Cloud Provider
     *
     * @param AiRentalCloudConfig $config
     * @param AiRentalModel $model
     * @param string $instanceType (e.g., "A100-80GB")
     * @param array $options Additional options
     * @return array ['success' => bool, 'instance_id' => string, 'endpoint_url' => string, 'message' => string]
     */
    public function deployModel(
        AiRentalCloudConfig $config,
        AiRentalModel $model,
        string $instanceType,
        array $options = []
    ): array;

    /**
     * เริ่ม Instance ที่หยุดไว้
     *
     * @param AiRentalCloudConfig $config
     * @param string $instanceId
     * @return array ['success' => bool, 'message' => string]
     */
    public function startInstance(AiRentalCloudConfig $config, string $instanceId): array;

    /**
     * หยุด Instance ที่กำลังรัน
     *
     * @param AiRentalCloudConfig $config
     * @param string $instanceId
     * @return array ['success' => bool, 'message' => string]
     */
    public function stopInstance(AiRentalCloudConfig $config, string $instanceId): array;

    /**
     * ลบ Instance
     *
     * @param AiRentalCloudConfig $config
     * @param string $instanceId
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteInstance(AiRentalCloudConfig $config, string $instanceId): array;

    /**
     * ดึงข้อมูล Instance
     *
     * @param AiRentalCloudConfig $config
     * @param string $instanceId
     * @return array ['success' => bool, 'data' => array, 'message' => string]
     */
    public function getInstanceInfo(AiRentalCloudConfig $config, string $instanceId): array;

    /**
     * ดึง Logs จาก Instance
     *
     * @param AiRentalCloudConfig $config
     * @param string $instanceId
     * @param int $lines จำนวนบรรทัด (default: 100)
     * @return array ['success' => bool, 'logs' => array, 'message' => string]
     */
    public function getLogs(AiRentalCloudConfig $config, string $instanceId, int $lines = 100): array;

    /**
     * ตรวจสอบสถานะ Health ของ Instance
     *
     * @param AiRentalCloudConfig $config
     * @param string $instanceId
     * @return array ['success' => bool, 'health' => array, 'message' => string]
     */
    public function checkHealth(AiRentalCloudConfig $config, string $instanceId): array;

    /**
     * ดึงรายการ GPU Types ที่รองรับ
     *
     * @param AiRentalCloudConfig $config
     * @return array ['success' => bool, 'gpu_types' => array, 'message' => string]
     */
    public function getAvailableGpuTypes(AiRentalCloudConfig $config): array;

    /**
     * ดึงราคา GPU Types
     *
     * @param AiRentalCloudConfig $config
     * @return array ['success' => bool, 'pricing' => array, 'message' => string]
     */
    public function getPricing(AiRentalCloudConfig $config): array;

    /**
     * ทดสอบ Inference Endpoint
     *
     * @param string $endpointUrl
     * @param array $payload
     * @return array ['success' => bool, 'response' => mixed, 'latency_ms' => int, 'message' => string]
     */
    public function testInference(string $endpointUrl, array $payload): array;
}
