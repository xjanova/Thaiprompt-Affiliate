<?php

namespace App\Services;

use App\Models\AccountingFlowaccountConnection;
use App\Models\AccountingFlowaccountSyncLog;
use App\Models\AccountingContact;
use App\Models\AccountingProduct;
use App\Models\AccountingInvoice;
use App\Models\AccountingExpense;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * FlowAccount API Integration Service
 *
 * บริการเชื่อมต่อกับ FlowAccount API อย่างเต็มรูปแบบ
 * รองรับทุก endpoints ของ FlowAccount Open API v3
 *
 * @see https://developers.flowaccount.com/api-reference
 */
class FlowAccountService
{
    /**
     * Sandbox API URL
     */
    protected string $sandboxUrl = 'https://openapi.flowaccount.com/sandbox';

    /**
     * Production API URL
     */
    protected string $productionUrl = 'https://openapi.flowaccount.com/v3-alpha';

    /**
     * Base URL ที่ใช้งาน
     */
    protected string $baseUrl;

    /**
     * Culture/Language setting
     */
    protected string $culture = 'th-TH';

    /**
     * HTTP timeout (seconds)
     */
    protected int $timeout = 30;

    /**
     * Retry attempts
     */
    protected int $retryAttempts = 3;

    /**
     * Constructor - กำหนดค่าเริ่มต้น
     */
    public function __construct()
    {
        $this->baseUrl = config('flowaccount.use_sandbox', true)
            ? $this->sandboxUrl
            : $this->productionUrl;

        $this->culture = config('flowaccount.culture', 'th-TH');
        $this->timeout = config('flowaccount.timeout', 30);
        $this->retryAttempts = config('flowaccount.retry_attempts', 3);
    }

    // =========================================================================
    // CONNECTION MANAGEMENT - การจัดการการเชื่อมต่อ
    // =========================================================================

    /**
     * ดึงข้อมูลการเชื่อมต่อของ user
     *
     * @param User $user ผู้ใช้
     * @return AccountingFlowaccountConnection|null
     */
    public function getConnection(User $user): ?AccountingFlowaccountConnection
    {
        return AccountingFlowaccountConnection::where('user_id', $user->id)->first();
    }

    /**
     * บันทึกหรืออัปเดตการเชื่อมต่อ
     *
     * @param User $user ผู้ใช้
     * @param array $data ข้อมูลการเชื่อมต่อ
     * @return AccountingFlowaccountConnection
     */
    public function saveConnection(User $user, array $data): AccountingFlowaccountConnection
    {
        return AccountingFlowaccountConnection::updateOrCreate(
            ['user_id' => $user->id],
            $data
        );
    }

    /**
     * ทดสอบการเชื่อมต่อ
     *
     * @param AccountingFlowaccountConnection $connection การเชื่อมต่อที่จะทดสอบ
     * @return array ผลการทดสอบ ['success' => bool, 'message' => string, 'data' => array]
     */
    public function testConnection(AccountingFlowaccountConnection $connection): array
    {
        try {
            $response = $this->request('GET', '/companies', $connection);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message' => 'เชื่อมต่อสำเร็จ',
                    'data' => $data,
                ];
            }

            return [
                'success' => false,
                'message' => 'ไม่สามารถเชื่อมต่อได้: ' . ($response->json()['message'] ?? 'Unknown error'),
                'data' => [],
            ];
        } catch (\Exception $e) {
            Log::error('FlowAccount connection test failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
                'data' => [],
            ];
        }
    }

    /**
     * ตรวจสอบสถานะการเชื่อมต่อ
     *
     * @param AccountingFlowaccountConnection $connection
     * @return array ข้อมูลสถานะการเชื่อมต่อ
     */
    public function getConnectionStatus(AccountingFlowaccountConnection $connection): array
    {
        return [
            'is_connected' => $connection->isConnected(),
            'is_token_expired' => $connection->isTokenExpired(),
            'token_expires_at' => $connection->token_expires_at?->format('Y-m-d H:i:s'),
            'last_sync_at' => $connection->last_sync_at?->format('Y-m-d H:i:s'),
            'auto_sync' => $connection->auto_sync,
            'sync_settings' => $connection->sync_settings ?? [],
        ];
    }

    // =========================================================================
    // OAUTH AUTHENTICATION - การยืนยันตัวตน
    // =========================================================================

    /**
     * สร้าง Authorization URL สำหรับ OAuth
     *
     * @param string $clientId
     * @param string|null $redirectUri
     * @param string $scope
     * @param string|null $state
     * @return string
     */
    public function getAuthorizationUrl(
        string $clientId,
        ?string $redirectUri = null,
        string $scope = 'read write',
        ?string $state = null
    ): string {
        $params = [
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri ?? config('flowaccount.redirect_uri'),
            'response_type' => 'code',
            'scope' => $scope,
        ];

        if ($state) {
            $params['state'] = $state;
        }

        return "{$this->baseUrl}/oauth/authorize?" . http_build_query($params);
    }

    /**
     * แลกเปลี่ยน Authorization Code เป็น Access Token
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param string $authCode
     * @param string|null $redirectUri
     * @return array|null
     */
    public function getAccessToken(
        string $clientId,
        string $clientSecret,
        string $authCode,
        ?string $redirectUri = null
    ): ?array {
        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->retryAttempts, 1000)
                ->asForm()
                ->post("{$this->baseUrl}/oauth/token", [
                    'grant_type' => 'authorization_code',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'code' => $authCode,
                    'redirect_uri' => $redirectUri ?? config('flowaccount.redirect_uri'),
                ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('FlowAccount OAuth token obtained successfully');
                return $data;
            }

            Log::error('FlowAccount OAuth failed: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('FlowAccount OAuth exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * รีเฟรช Access Token
     *
     * @param AccountingFlowaccountConnection $connection
     * @return bool
     */
    public function refreshAccessToken(AccountingFlowaccountConnection $connection): bool
    {
        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->retryAttempts, 1000)
                ->asForm()
                ->post("{$this->baseUrl}/oauth/token", [
                    'grant_type' => 'refresh_token',
                    'client_id' => $connection->client_id,
                    'client_secret' => $connection->client_secret,
                    'refresh_token' => $connection->refresh_token,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $connection->update([
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'] ?? $connection->refresh_token,
                    'token_expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
                ]);

                Log::info('FlowAccount token refreshed successfully for user: ' . $connection->user_id);
                return true;
            }

            Log::error('FlowAccount token refresh failed: ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error('FlowAccount token refresh exception: ' . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // COMPANY - ข้อมูลบริษัท
    // =========================================================================

    /**
     * ดึงข้อมูลบริษัท
     *
     * @param AccountingFlowaccountConnection $connection
     * @return array|null
     */
    public function getCompanyInfo(AccountingFlowaccountConnection $connection): ?array
    {
        try {
            $response = $this->request('GET', '/companies', $connection);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('FlowAccount get company info failed: ' . $e->getMessage());
            return null;
        }
    }

    // =========================================================================
    // CONTACTS - ผู้ติดต่อ (ลูกค้า/ผู้จำหน่าย)
    // =========================================================================

    /**
     * ดึงรายการผู้ติดต่อ
     *
     * @param AccountingFlowaccountConnection $connection
     * @param array $params ['CurrentPage', 'PageSize', 'Filter', 'SearchString', 'SortBy']
     * @return array|null
     */
    public function getContacts(AccountingFlowaccountConnection $connection, array $params = []): ?array
    {
        try {
            $defaultParams = [
                'CurrentPage' => 1,
                'PageSize' => 50,
            ];
            $params = array_merge($defaultParams, $params);

            $response = $this->request('GET', "/api/{$this->culture}/contacts", $connection, $params);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('FlowAccount get contacts failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * ดึงข้อมูลผู้ติดต่อตาม ID
     *
     * @param AccountingFlowaccountConnection $connection
     * @param int $contactId
     * @return array|null
     */
    public function getContact(AccountingFlowaccountConnection $connection, int $contactId): ?array
    {
        try {
            $response = $this->request('GET', "/api/{$this->culture}/contacts/{$contactId}", $connection);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error("FlowAccount get contact {$contactId} failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * สร้างผู้ติดต่อใหม่
     *
     * @param AccountingFlowaccountConnection $connection
     * @param array $data ข้อมูลผู้ติดต่อ
     * @return array|null
     */
    public function createContact(AccountingFlowaccountConnection $connection, array $data): ?array
    {
        try {
            $response = $this->request('POST', "/api/{$this->culture}/contacts", $connection, $data);

            if ($response->successful()) {
                $this->logSync($connection, 'contact', 'create', true);
                return $response->json();
            }

            $this->logSync($connection, 'contact', 'create', false, $response->body());
            return null;
        } catch (\Exception $e) {
            $this->logSync($connection, 'contact', 'create', false, $e->getMessage());
            Log::error('FlowAccount create contact failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * อัปเดตผู้ติดต่อ
     *
     * @param AccountingFlowaccountConnection $connection
     * @param int $contactId
     * @param array $data
     * @return array|null
     */
    public function updateContact(AccountingFlowaccountConnection $connection, int $contactId, array $data): ?array
    {
        try {
            $response = $this->request('PUT', "/api/{$this->culture}/contacts/{$contactId}", $connection, $data);

            if ($response->successful()) {
                $this->logSync($connection, 'contact', 'update', true, null, $contactId);
                return $response->json();
            }

            $this->logSync($connection, 'contact', 'update', false, $response->body(), $contactId);
            return null;
        } catch (\Exception $e) {
            $this->logSync($connection, 'contact', 'update', false, $e->getMessage(), $contactId);
            Log::error("FlowAccount update contact {$contactId} failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * ลบผู้ติดต่อ
     *
     * @param AccountingFlowaccountConnection $connection
     * @param int $contactId
     * @return bool
     */
    public function deleteContact(AccountingFlowaccountConnection $connection, int $contactId): bool
    {
        try {
            $response = $this->request('DELETE', "/api/{$this->culture}/contacts/{$contactId}", $connection);

            if ($response->successful()) {
                $this->logSync($connection, 'contact', 'delete', true, null, $contactId);
                return true;
            }

            $this->logSync($connection, 'contact', 'delete', false, $response->body(), $contactId);
            return false;
        } catch (\Exception $e) {
            $this->logSync($connection, 'contact', 'delete', false, $e->getMessage(), $contactId);
            Log::error("FlowAccount delete contact {$contactId} failed: " . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // PRODUCTS - สินค้า/บริการ
    // =========================================================================

    /**
     * ดึงรายการสินค้า
     *
     * @param AccountingFlowaccountConnection $connection
     * @param array $params
     * @return array|null
     */
    public function getProducts(AccountingFlowaccountConnection $connection, array $params = []): ?array
    {
        try {
            $defaultParams = [
                'CurrentPage' => 1,
                'PageSize' => 50,
            ];
            $params = array_merge($defaultParams, $params);

            $response = $this->request('GET', "/api/{$this->culture}/products", $connection, $params);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('FlowAccount get products failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * ดึงข้อมูลสินค้าตาม ID
     *
     * @param AccountingFlowaccountConnection $connection
     * @param int $productId
     * @return array|null
     */
    public function getProduct(AccountingFlowaccountConnection $connection, int $productId): ?array
    {
        try {
            $response = $this->request('GET', "/api/{$this->culture}/products/{$productId}", $connection);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error("FlowAccount get product {$productId} failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * สร้างสินค้าใหม่
     *
     * @param AccountingFlowaccountConnection $connection
     * @param array $data
     * @return array|null
     */
    public function createProduct(AccountingFlowaccountConnection $connection, array $data): ?array
    {
        try {
            $response = $this->request('POST', "/api/{$this->culture}/products", $connection, $data);

            if ($response->successful()) {
                $this->logSync($connection, 'product', 'create', true);
                return $response->json();
            }

            $this->logSync($connection, 'product', 'create', false, $response->body());
            return null;
        } catch (\Exception $e) {
            $this->logSync($connection, 'product', 'create', false, $e->getMessage());
            Log::error('FlowAccount create product failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * อัปเดตสินค้า
     *
     * @param AccountingFlowaccountConnection $connection
     * @param int $productId
     * @param array $data
     * @return array|null
     */
    public function updateProduct(AccountingFlowaccountConnection $connection, int $productId, array $data): ?array
    {
        try {
            $response = $this->request('PUT', "/api/{$this->culture}/products/{$productId}", $connection, $data);

            if ($response->successful()) {
                $this->logSync($connection, 'product', 'update', true, null, $productId);
                return $response->json();
            }

            $this->logSync($connection, 'product', 'update', false, $response->body(), $productId);
            return null;
        } catch (\Exception $e) {
            $this->logSync($connection, 'product', 'update', false, $e->getMessage(), $productId);
            Log::error("FlowAccount update product {$productId} failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * ลบสินค้า
     *
     * @param AccountingFlowaccountConnection $connection
     * @param int $productId
     * @return bool
     */
    public function deleteProduct(AccountingFlowaccountConnection $connection, int $productId): bool
    {
        try {
            $response = $this->request('DELETE', "/api/{$this->culture}/products/{$productId}", $connection);

            if ($response->successful()) {
                $this->logSync($connection, 'product', 'delete', true, null, $productId);
                return true;
            }

            $this->logSync($connection, 'product', 'delete', false, $response->body(), $productId);
            return false;
        } catch (\Exception $e) {
            $this->logSync($connection, 'product', 'delete', false, $e->getMessage(), $productId);
            Log::error("FlowAccount delete product {$productId} failed: " . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // TAX INVOICES - ใบกำกับภาษี
    // =========================================================================

    /**
     * ดึงรายการใบกำกับภาษี
     *
     * @param AccountingFlowaccountConnection $connection
     * @param array $params
     * @return array|null
     */
    public function getTaxInvoices(AccountingFlowaccountConnection $connection, array $params = []): ?array
    {
        try {
            $defaultParams = [
                'CurrentPage' => 1,
                'PageSize' => 50,
            ];
            $params = array_merge($defaultParams, $params);

            $response = $this->request('GET', "/api/{$this->culture}/tax-invoices", $connection, $params);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('FlowAccount get tax invoices failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * ดึงใบกำกับภาษีตาม ID
     *
     * @param AccountingFlowaccountConnection $connection
     * @param int $documentId
     * @return array|null
     */
    public function getTaxInvoice(AccountingFlowaccountConnection $connection, int $documentId): ?array
    {
        try {
            $response = $this->request('GET', "/api/{$this->culture}/tax-invoices/{$documentId}", $connection);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error("FlowAccount get tax invoice {$documentId} failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * สร้างใบกำกับภาษี
     *
     * @param AccountingFlowaccountConnection $connection
     * @param array $data
     * @return array|null
     */
    public function createTaxInvoice(AccountingFlowaccountConnection $connection, array $data): ?array
    {
        try {
            $response = $this->request('POST', "/api/{$this->culture}/tax-invoices/simple", $connection, $data);

            if ($response->successful()) {
                $this->logSync($connection, 'tax_invoice', 'create', true);
                return $response->json();
            }

            $this->logSync($connection, 'tax_invoice', 'create', false, $response->body());
            return null;
        } catch (\Exception $e) {
            $this->logSync($connection, 'tax_invoice', 'create', false, $e->getMessage());
            Log::error('FlowAccount create tax invoice failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * อัปเดตสถานะใบกำกับภาษี
     *
     * @param AccountingFlowaccountConnection $connection
     * @param int $documentId
     * @param string $status
     * @return bool
     */
    public function updateTaxInvoiceStatus(AccountingFlowaccountConnection $connection, int $documentId, string $status): bool
    {
        try {
            $response = $this->request('POST', "/api/{$this->culture}/tax-invoices/{$documentId}/status", $connection, [
                'Status' => $status,
            ]);

            if ($response->successful()) {
                $this->logSync($connection, 'tax_invoice', 'update_status', true, null, $documentId);
                return true;
            }

            $this->logSync($connection, 'tax_invoice', 'update_status', false, $response->body(), $documentId);
            return false;
        } catch (\Exception $e) {
            $this->logSync($connection, 'tax_invoice', 'update_status', false, $e->getMessage(), $documentId);
            Log::error("FlowAccount update tax invoice status {$documentId} failed: " . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // CASH INVOICES - ใบกำกับภาษี/ใบเสร็จรับเงิน (เงินสด)
    // =========================================================================

    /**
     * ดึงรายการใบกำกับภาษีเงินสด
     *
     * @param AccountingFlowaccountConnection $connection
     * @param array $params
     * @return array|null
     */
    public function getCashInvoices(AccountingFlowaccountConnection $connection, array $params = []): ?array
    {
        try {
            $defaultParams = [
                'CurrentPage' => 1,
                'PageSize' => 50,
            ];
            $params = array_merge($defaultParams, $params);

            $response = $this->request('GET', "/api/{$this->culture}/cash-invoices", $connection, $params);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('FlowAccount get cash invoices failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * สร้างใบกำกับภาษีเงินสด
     *
     * @param AccountingFlowaccountConnection $connection
     * @param array $data
     * @return array|null
     */
    public function createCashInvoice(AccountingFlowaccountConnection $connection, array $data): ?array
    {
        try {
            $response = $this->request('POST', "/api/{$this->culture}/cash-invoices/simple", $connection, $data);

            if ($response->successful()) {
                $this->logSync($connection, 'cash_invoice', 'create', true);
                return $response->json();
            }

            $this->logSync($connection, 'cash_invoice', 'create', false, $response->body());
            return null;
        } catch (\Exception $e) {
            $this->logSync($connection, 'cash_invoice', 'create', false, $e->getMessage());
            Log::error('FlowAccount create cash invoice failed: ' . $e->getMessage());
            return null;
        }
    }

    // =========================================================================
    // RECEIVABLE INVOICES - ใบแจ้งหนี้ (ลูกหนี้)
    // =========================================================================

    /**
     * ดึงรายการใบแจ้งหนี้
     *
     * @param AccountingFlowaccountConnection $connection
     * @param array $params
     * @return array|null
     */
    public function getReceivableInvoices(AccountingFlowaccountConnection $connection, array $params = []): ?array
    {
        try {
            $defaultParams = [
                'CurrentPage' => 1,
                'PageSize' => 50,
            ];
            $params = array_merge($defaultParams, $params);

            $response = $this->request('GET', "/api/{$this->culture}/receivable-invoices", $connection, $params);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('FlowAccount get receivable invoices failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * สร้างใบแจ้งหนี้
     *
     * @param AccountingFlowaccountConnection $connection
     * @param array $data
     * @return array|null
     */
    public function createReceivableInvoice(AccountingFlowaccountConnection $connection, array $data): ?array
    {
        try {
            $response = $this->request('POST', "/api/{$this->culture}/receivable-invoices/simple", $connection, $data);

            if ($response->successful()) {
                $this->logSync($connection, 'receivable_invoice', 'create', true);
                return $response->json();
            }

            $this->logSync($connection, 'receivable_invoice', 'create', false, $response->body());
            return null;
        } catch (\Exception $e) {
            $this->logSync($connection, 'receivable_invoice', 'create', false, $e->getMessage());
            Log::error('FlowAccount create receivable invoice failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * บันทึกการรับชำระเงิน
     *
     * @param AccountingFlowaccountConnection $connection
     * @param int $documentId
     * @param array $paymentData
     * @return array|null
     */
    public function recordReceivablePayment(AccountingFlowaccountConnection $connection, int $documentId, array $paymentData): ?array
    {
        try {
            $response = $this->request(
                'POST',
                "/api/{$this->culture}/receivable-invoices/{$documentId}/payments",
                $connection,
                $paymentData
            );

            if ($response->successful()) {
                $this->logSync($connection, 'receivable_invoice', 'payment', true, null, $documentId);
                return $response->json();
            }

            $this->logSync($connection, 'receivable_invoice', 'payment', false, $response->body(), $documentId);
            return null;
        } catch (\Exception $e) {
            $this->logSync($connection, 'receivable_invoice', 'payment', false, $e->getMessage(), $documentId);
            Log::error("FlowAccount record payment for {$documentId} failed: " . $e->getMessage());
            return null;
        }
    }

    // =========================================================================
    // QUOTATIONS - ใบเสนอราคา
    // =========================================================================

    /**
     * ดึงรายการใบเสนอราคา
     *
     * @param AccountingFlowaccountConnection $connection
     * @param array $params
     * @return array|null
     */
    public function getQuotations(AccountingFlowaccountConnection $connection, array $params = []): ?array
    {
        try {
            $defaultParams = [
                'CurrentPage' => 1,
                'PageSize' => 50,
            ];
            $params = array_merge($defaultParams, $params);

            $response = $this->request('GET', "/api/{$this->culture}/quotations", $connection, $params);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('FlowAccount get quotations failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * สร้างใบเสนอราคา
     *
     * @param AccountingFlowaccountConnection $connection
     * @param array $data
     * @return array|null
     */
    public function createQuotation(AccountingFlowaccountConnection $connection, array $data): ?array
    {
        try {
            $response = $this->request('POST', "/api/{$this->culture}/quotations/simple", $connection, $data);

            if ($response->successful()) {
                $this->logSync($connection, 'quotation', 'create', true);
                return $response->json();
            }

            $this->logSync($connection, 'quotation', 'create', false, $response->body());
            return null;
        } catch (\Exception $e) {
            $this->logSync($connection, 'quotation', 'create', false, $e->getMessage());
            Log::error('FlowAccount create quotation failed: ' . $e->getMessage());
            return null;
        }
    }

    // =========================================================================
    // BILLING NOTES - ใบวางบิล
    // =========================================================================

    /**
     * ดึงรายการใบวางบิล
     *
     * @param AccountingFlowaccountConnection $connection
     * @param array $params
     * @return array|null
     */
    public function getBillingNotes(AccountingFlowaccountConnection $connection, array $params = []): ?array
    {
        try {
            $defaultParams = [
                'CurrentPage' => 1,
                'PageSize' => 50,
            ];
            $params = array_merge($defaultParams, $params);

            $response = $this->request('GET', "/api/{$this->culture}/billing-notes", $connection, $params);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('FlowAccount get billing notes failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * สร้างใบวางบิล
     *
     * @param AccountingFlowaccountConnection $connection
     * @param array $data
     * @return array|null
     */
    public function createBillingNote(AccountingFlowaccountConnection $connection, array $data): ?array
    {
        try {
            $response = $this->request('POST', "/api/{$this->culture}/billing-notes/simple", $connection, $data);

            if ($response->successful()) {
                $this->logSync($connection, 'billing_note', 'create', true);
                return $response->json();
            }

            $this->logSync($connection, 'billing_note', 'create', false, $response->body());
            return null;
        } catch (\Exception $e) {
            $this->logSync($connection, 'billing_note', 'create', false, $e->getMessage());
            Log::error('FlowAccount create billing note failed: ' . $e->getMessage());
            return null;
        }
    }

    // =========================================================================
    // RECEIPTS - ใบเสร็จรับเงิน
    // =========================================================================

    /**
     * ดึงรายการใบเสร็จรับเงิน
     *
     * @param AccountingFlowaccountConnection $connection
     * @param array $params
     * @return array|null
     */
    public function getReceipts(AccountingFlowaccountConnection $connection, array $params = []): ?array
    {
        try {
            $defaultParams = [
                'CurrentPage' => 1,
                'PageSize' => 50,
            ];
            $params = array_merge($defaultParams, $params);

            $response = $this->request('GET', "/api/{$this->culture}/receipts", $connection, $params);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('FlowAccount get receipts failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * สร้างใบเสร็จรับเงิน
     *
     * @param AccountingFlowaccountConnection $connection
     * @param array $data
     * @return array|null
     */
    public function createReceipt(AccountingFlowaccountConnection $connection, array $data): ?array
    {
        try {
            $response = $this->request('POST', "/api/{$this->culture}/receipts/simple", $connection, $data);

            if ($response->successful()) {
                $this->logSync($connection, 'receipt', 'create', true);
                return $response->json();
            }

            $this->logSync($connection, 'receipt', 'create', false, $response->body());
            return null;
        } catch (\Exception $e) {
            $this->logSync($connection, 'receipt', 'create', false, $e->getMessage());
            Log::error('FlowAccount create receipt failed: ' . $e->getMessage());
            return null;
        }
    }

    // =========================================================================
    // EXPENSES - ค่าใช้จ่าย
    // =========================================================================

    /**
     * ดึงรายการค่าใช้จ่าย
     *
     * @param AccountingFlowaccountConnection $connection
     * @param array $params
     * @return array|null
     */
    public function getExpenses(AccountingFlowaccountConnection $connection, array $params = []): ?array
    {
        try {
            $defaultParams = [
                'CurrentPage' => 1,
                'PageSize' => 50,
            ];
            $params = array_merge($defaultParams, $params);

            $response = $this->request('GET', "/api/{$this->culture}/expenses", $connection, $params);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('FlowAccount get expenses failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * สร้างค่าใช้จ่าย
     *
     * @param AccountingFlowaccountConnection $connection
     * @param array $data
     * @return array|null
     */
    public function createExpense(AccountingFlowaccountConnection $connection, array $data): ?array
    {
        try {
            $response = $this->request('POST', "/api/{$this->culture}/expenses/simple", $connection, $data);

            if ($response->successful()) {
                $this->logSync($connection, 'expense', 'create', true);
                return $response->json();
            }

            $this->logSync($connection, 'expense', 'create', false, $response->body());
            return null;
        } catch (\Exception $e) {
            $this->logSync($connection, 'expense', 'create', false, $e->getMessage());
            Log::error('FlowAccount create expense failed: ' . $e->getMessage());
            return null;
        }
    }

    // =========================================================================
    // SYNC OPERATIONS - การซิงค์ข้อมูล
    // =========================================================================

    /**
     * ซิงค์ผู้ติดต่อจาก FlowAccount มายังระบบ
     *
     * @param AccountingFlowaccountConnection $connection
     * @param int $userId
     * @return array ผลการซิงค์
     */
    public function syncContactsFromFlowAccount(AccountingFlowaccountConnection $connection, int $userId): array
    {
        $result = ['created' => 0, 'updated' => 0, 'failed' => 0, 'errors' => []];

        try {
            $page = 1;
            $pageSize = 50;

            do {
                $response = $this->getContacts($connection, [
                    'CurrentPage' => $page,
                    'PageSize' => $pageSize,
                ]);

                if (!$response || empty($response['data'])) {
                    break;
                }

                foreach ($response['data'] as $contactData) {
                    try {
                        $existingContact = AccountingContact::where('user_id', $userId)
                            ->where('flowaccount_id', $contactData['id'])
                            ->first();

                        $mappedData = $this->mapFlowAccountContactToLocal($contactData);
                        $mappedData['user_id'] = $userId;

                        if ($existingContact) {
                            $existingContact->update($mappedData);
                            $result['updated']++;
                        } else {
                            AccountingContact::create($mappedData);
                            $result['created']++;
                        }
                    } catch (\Exception $e) {
                        $result['failed']++;
                        $result['errors'][] = "Contact ID {$contactData['id']}: " . $e->getMessage();
                    }
                }

                $page++;
                $totalPages = ceil(($response['total'] ?? 0) / $pageSize);
            } while ($page <= $totalPages);

            // อัปเดตเวลาซิงค์
            $connection->update(['last_sync_at' => now()]);

        } catch (\Exception $e) {
            $result['errors'][] = $e->getMessage();
        }

        return $result;
    }

    /**
     * ซิงค์สินค้าจาก FlowAccount มายังระบบ
     *
     * @param AccountingFlowaccountConnection $connection
     * @param int $userId
     * @return array ผลการซิงค์
     */
    public function syncProductsFromFlowAccount(AccountingFlowaccountConnection $connection, int $userId): array
    {
        $result = ['created' => 0, 'updated' => 0, 'failed' => 0, 'errors' => []];

        try {
            $page = 1;
            $pageSize = 50;

            do {
                $response = $this->getProducts($connection, [
                    'CurrentPage' => $page,
                    'PageSize' => $pageSize,
                ]);

                if (!$response || empty($response['data'])) {
                    break;
                }

                foreach ($response['data'] as $productData) {
                    try {
                        $existingProduct = AccountingProduct::where('user_id', $userId)
                            ->where('flowaccount_id', $productData['id'])
                            ->first();

                        $mappedData = $this->mapFlowAccountProductToLocal($productData);
                        $mappedData['user_id'] = $userId;

                        if ($existingProduct) {
                            $existingProduct->update($mappedData);
                            $result['updated']++;
                        } else {
                            AccountingProduct::create($mappedData);
                            $result['created']++;
                        }
                    } catch (\Exception $e) {
                        $result['failed']++;
                        $result['errors'][] = "Product ID {$productData['id']}: " . $e->getMessage();
                    }
                }

                $page++;
                $totalPages = ceil(($response['total'] ?? 0) / $pageSize);
            } while ($page <= $totalPages);

            $connection->update(['last_sync_at' => now()]);

        } catch (\Exception $e) {
            $result['errors'][] = $e->getMessage();
        }

        return $result;
    }

    /**
     * ซิงค์ใบแจ้งหนี้ไปยัง FlowAccount
     *
     * @param AccountingInvoice $invoice
     * @param AccountingFlowaccountConnection $connection
     * @return array|null
     */
    public function syncInvoice(AccountingInvoice $invoice, AccountingFlowaccountConnection $connection): ?array
    {
        try {
            $data = $this->mapLocalInvoiceToFlowAccount($invoice);

            // เลือกประเภทเอกสารตาม invoice type
            $endpoint = match ($invoice->type ?? 'tax_invoice') {
                'cash_invoice' => "/api/{$this->culture}/cash-invoices/simple",
                'receivable_invoice' => "/api/{$this->culture}/receivable-invoices/simple",
                default => "/api/{$this->culture}/tax-invoices/simple",
            };

            $response = $this->request('POST', $endpoint, $connection, $data);

            if ($response->successful()) {
                $result = $response->json();

                // อัปเดต invoice กับ flowaccount_id
                $invoice->update([
                    'flowaccount_id' => $result['id'] ?? $result['data']['id'] ?? null,
                    'synced_at' => now(),
                ]);

                $this->logSync($connection, 'invoice', 'sync', true, null, $invoice->id);
                return $result;
            }

            $this->logSync($connection, 'invoice', 'sync', false, $response->body(), $invoice->id);
            return null;
        } catch (\Exception $e) {
            $this->logSync($connection, 'invoice', 'sync', false, $e->getMessage(), $invoice->id);
            Log::error('FlowAccount invoice sync failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * ซิงค์ค่าใช้จ่ายไปยัง FlowAccount
     *
     * @param AccountingExpense $expense
     * @param AccountingFlowaccountConnection $connection
     * @return array|null
     */
    public function syncExpense(AccountingExpense $expense, AccountingFlowaccountConnection $connection): ?array
    {
        try {
            $data = $this->mapLocalExpenseToFlowAccount($expense);

            $response = $this->request('POST', "/api/{$this->culture}/expenses/simple", $connection, $data);

            if ($response->successful()) {
                $result = $response->json();

                $expense->update([
                    'flowaccount_id' => $result['id'] ?? $result['data']['id'] ?? null,
                    'synced_at' => now(),
                ]);

                $this->logSync($connection, 'expense', 'sync', true, null, $expense->id);
                return $result;
            }

            $this->logSync($connection, 'expense', 'sync', false, $response->body(), $expense->id);
            return null;
        } catch (\Exception $e) {
            $this->logSync($connection, 'expense', 'sync', false, $e->getMessage(), $expense->id);
            Log::error('FlowAccount expense sync failed: ' . $e->getMessage());
            return null;
        }
    }

    // =========================================================================
    // BATCH IMPORT - นำเข้าข้อมูลจำนวนมาก
    // =========================================================================

    /**
     * สร้าง batch import สำหรับเอกสาร
     *
     * @param AccountingFlowaccountConnection $connection
     * @param string $documentType ประเภทเอกสาร (invoice, expense, etc.)
     * @param array $documents รายการเอกสาร
     * @return array|null
     */
    public function createBatchImport(AccountingFlowaccountConnection $connection, string $documentType, array $documents): ?array
    {
        try {
            $response = $this->request('POST', "/api/{$this->culture}/batch-import/{$documentType}", $connection, [
                'documents' => $documents,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('FlowAccount batch import failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * ดึงสถานะ batch import
     *
     * @param AccountingFlowaccountConnection $connection
     * @param string $batchId
     * @return array|null
     */
    public function getBatchStatus(AccountingFlowaccountConnection $connection, string $batchId): ?array
    {
        try {
            $response = $this->request('GET', "/api/{$this->culture}/batch-import/{$batchId}/status", $connection);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error("FlowAccount get batch status {$batchId} failed: " . $e->getMessage());
            return null;
        }
    }

    // =========================================================================
    // DATA MAPPING - แปลงรูปแบบข้อมูล
    // =========================================================================

    /**
     * แปลงข้อมูลผู้ติดต่อจาก FlowAccount เป็นรูปแบบของระบบ
     *
     * @param array $data
     * @return array
     */
    protected function mapFlowAccountContactToLocal(array $data): array
    {
        return [
            'flowaccount_id' => $data['id'] ?? null,
            'type' => ($data['contactType'] ?? 0) == 1 ? 'customer' : 'vendor',
            'name' => $data['name'] ?? '',
            'contact_person' => $data['contactPerson'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'tax_id' => $data['taxId'] ?? null,
            'branch_code' => $data['branchCode'] ?? null,
            'address' => $data['address'] ?? null,
            'province' => $data['province'] ?? null,
            'district' => $data['district'] ?? null,
            'subdistrict' => $data['subdistrict'] ?? null,
            'postal_code' => $data['postalCode'] ?? null,
            'credit_days' => $data['creditDays'] ?? 0,
            'is_active' => ($data['status'] ?? 1) == 1,
            'synced_at' => now(),
        ];
    }

    /**
     * แปลงข้อมูลสินค้าจาก FlowAccount เป็นรูปแบบของระบบ
     *
     * @param array $data
     * @return array
     */
    protected function mapFlowAccountProductToLocal(array $data): array
    {
        return [
            'flowaccount_id' => $data['id'] ?? null,
            'type' => ($data['productType'] ?? 0) == 1 ? 'product' : 'service',
            'sku' => $data['sku'] ?? null,
            'name' => $data['name'] ?? '',
            'description' => $data['description'] ?? null,
            'unit' => $data['unit'] ?? null,
            'sell_price' => $data['sellPrice'] ?? 0,
            'buy_price' => $data['buyPrice'] ?? 0,
            'is_active' => ($data['status'] ?? 1) == 1,
            'synced_at' => now(),
        ];
    }

    /**
     * แปลงข้อมูลใบแจ้งหนี้จากระบบเป็นรูปแบบ FlowAccount
     *
     * @param AccountingInvoice $invoice
     * @return array
     */
    protected function mapLocalInvoiceToFlowAccount(AccountingInvoice $invoice): array
    {
        $items = $invoice->items->map(function ($item) {
            return [
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unitPrice' => $item->unit_price,
                'discount' => $item->discount ?? 0,
                'vatType' => $item->vat_type ?? 7, // 7 = VAT 7%
            ];
        })->toArray();

        return [
            'documentDate' => $invoice->document_date->format('Y-m-d'),
            'dueDate' => $invoice->due_date?->format('Y-m-d'),
            'contactId' => $invoice->contact?->flowaccount_id,
            'contactName' => $invoice->contact?->name ?? $invoice->customer_name,
            'contactAddress' => $invoice->contact?->full_address ?? $invoice->customer_address,
            'contactTaxId' => $invoice->contact?->tax_id ?? $invoice->customer_tax_id,
            'items' => $items,
            'discount' => $invoice->discount ?? 0,
            'note' => $invoice->note,
            'internalNote' => $invoice->internal_note,
        ];
    }

    /**
     * แปลงข้อมูลค่าใช้จ่ายจากระบบเป็นรูปแบบ FlowAccount
     *
     * @param AccountingExpense $expense
     * @return array
     */
    protected function mapLocalExpenseToFlowAccount(AccountingExpense $expense): array
    {
        $items = $expense->items->map(function ($item) {
            return [
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unitPrice' => $item->unit_price,
                'discount' => $item->discount ?? 0,
                'vatType' => $item->vat_type ?? 7,
            ];
        })->toArray();

        return [
            'documentDate' => $expense->document_date->format('Y-m-d'),
            'dueDate' => $expense->due_date?->format('Y-m-d'),
            'contactId' => $expense->contact?->flowaccount_id,
            'contactName' => $expense->contact?->name ?? $expense->vendor_name,
            'items' => $items,
            'discount' => $expense->discount ?? 0,
            'note' => $expense->note,
        ];
    }

    // =========================================================================
    // HELPER METHODS - เมธอดช่วยเหลือ
    // =========================================================================

    /**
     * ทำ HTTP Request ไปยัง FlowAccount API
     *
     * @param string $method HTTP Method (GET, POST, PUT, DELETE)
     * @param string $endpoint API Endpoint
     * @param AccountingFlowaccountConnection $connection การเชื่อมต่อ
     * @param array $data ข้อมูลที่จะส่ง
     * @return \Illuminate\Http\Client\Response
     */
    protected function request(string $method, string $endpoint, AccountingFlowaccountConnection $connection, array $data = [])
    {
        // เช็คและรีเฟรช token ถ้าหมดอายุ
        if ($connection->isTokenExpired() && $connection->refresh_token) {
            if (!$this->refreshAccessToken($connection)) {
                throw new \Exception('ไม่สามารถรีเฟรช token ได้');
            }
            $connection->refresh();
        }

        $url = $this->baseUrl . $endpoint;

        $response = Http::timeout($this->timeout)
            ->retry($this->retryAttempts, 1000)
            ->withToken($connection->access_token)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]);

        return match (strtoupper($method)) {
            'GET' => $response->get($url, $data),
            'POST' => $response->post($url, $data),
            'PUT' => $response->put($url, $data),
            'DELETE' => $response->delete($url, $data),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
        };
    }

    /**
     * บันทึก log การซิงค์
     *
     * @param AccountingFlowaccountConnection $connection
     * @param string $type ประเภทข้อมูล
     * @param string $action การดำเนินการ
     * @param bool $success สำเร็จหรือไม่
     * @param string|null $errorMessage ข้อความ error (ถ้ามี)
     * @param int|null $recordId ID ของ record
     */
    protected function logSync(
        AccountingFlowaccountConnection $connection,
        string $type,
        string $action,
        bool $success,
        ?string $errorMessage = null,
        ?int $recordId = null
    ): void {
        try {
            // ถ้ามี model AccountingFlowaccountSyncLog
            if (class_exists(AccountingFlowaccountSyncLog::class)) {
                AccountingFlowaccountSyncLog::create([
                    'connection_id' => $connection->id,
                    'type' => $type,
                    'action' => $action,
                    'record_id' => $recordId,
                    'success' => $success,
                    'error_message' => $errorMessage,
                    'synced_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to log FlowAccount sync: ' . $e->getMessage());
        }
    }

    /**
     * ดึงสถิติการซิงค์
     *
     * @param AccountingFlowaccountConnection $connection
     * @return array
     */
    public function getSyncStats(AccountingFlowaccountConnection $connection): array
    {
        $userId = $connection->user_id;

        return [
            'invoices' => [
                'total' => AccountingInvoice::where('user_id', $userId)->count(),
                'synced' => AccountingInvoice::where('user_id', $userId)->whereNotNull('flowaccount_id')->count(),
                'pending' => AccountingInvoice::where('user_id', $userId)->whereNull('flowaccount_id')->where('status', '!=', 'draft')->count(),
            ],
            'expenses' => [
                'total' => AccountingExpense::where('user_id', $userId)->count(),
                'synced' => AccountingExpense::where('user_id', $userId)->whereNotNull('flowaccount_id')->count(),
                'pending' => AccountingExpense::where('user_id', $userId)->whereNull('flowaccount_id')->where('status', '!=', 'draft')->count(),
            ],
            'contacts' => [
                'total' => AccountingContact::where('user_id', $userId)->count(),
                'synced' => AccountingContact::where('user_id', $userId)->whereNotNull('flowaccount_id')->count(),
            ],
            'products' => [
                'total' => AccountingProduct::where('user_id', $userId)->count(),
                'synced' => AccountingProduct::where('user_id', $userId)->whereNotNull('flowaccount_id')->count(),
            ],
            'last_sync' => $connection->last_sync_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get base URL (public accessor)
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * ตรวจสอบว่าใช้ sandbox หรือไม่
     *
     * @return bool
     */
    public function isSandbox(): bool
    {
        return $this->baseUrl === $this->sandboxUrl;
    }
}
