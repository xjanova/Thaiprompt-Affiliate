<?php

namespace App\Services;

use App\Models\AccountingFlowaccountConnection;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FlowAccountService
{
    protected string $sandboxUrl = 'https://openapi.flowaccount.com/sandbox';
    protected string $productionUrl = 'https://openapi.flowaccount.com/v3-alpha';
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.flowaccount.use_sandbox', true)
            ? $this->sandboxUrl
            : $this->productionUrl;
    }

    /**
     * Get connection for user
     */
    public function getConnection(User $user): ?AccountingFlowaccountConnection
    {
        return AccountingFlowaccountConnection::where('user_id', $user->id)->first();
    }

    /**
     * Create or update connection
     */
    public function saveConnection(User $user, array $data): AccountingFlowaccountConnection
    {
        return AccountingFlowaccountConnection::updateOrCreate(
            ['user_id' => $user->id],
            $data
        );
    }

    /**
     * Test connection
     */
    public function testConnection(AccountingFlowaccountConnection $connection): bool
    {
        try {
            $response = $this->request('GET', '/companies', $connection);
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('FlowAccount connection test failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get access token using OAuth
     */
    public function getAccessToken(string $clientId, string $clientSecret, string $authCode): ?array
    {
        try {
            $response = Http::post("{$this->baseUrl}/oauth/token", [
                'grant_type' => 'authorization_code',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'code' => $authCode,
                'redirect_uri' => config('services.flowaccount.redirect_uri'),
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('FlowAccount OAuth failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Refresh access token
     */
    public function refreshAccessToken(AccountingFlowaccountConnection $connection): bool
    {
        try {
            $response = Http::post("{$this->baseUrl}/oauth/token", [
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
                    'token_expires_at' => now()->addSeconds($data['expires_in']),
                ]);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('FlowAccount token refresh failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Make API request
     */
    protected function request(string $method, string $endpoint, AccountingFlowaccountConnection $connection, array $data = [])
    {
        // Check if token is expired and refresh if needed
        if ($connection->isTokenExpired() && $connection->refresh_token) {
            $this->refreshAccessToken($connection);
        }

        $url = $this->baseUrl . $endpoint;

        $response = Http::withToken($connection->access_token)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]);

        return match($method) {
            'GET' => $response->get($url, $data),
            'POST' => $response->post($url, $data),
            'PUT' => $response->put($url, $data),
            'DELETE' => $response->delete($url, $data),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
        };
    }

    /**
     * Sync invoice to FlowAccount
     */
    public function syncInvoice($invoice, AccountingFlowaccountConnection $connection): ?array
    {
        try {
            $data = [
                'document_type' => 'invoice',
                'document_date' => $invoice->document_date->format('Y-m-d'),
                'due_date' => $invoice->due_date?->format('Y-m-d'),
                'contact_id' => $invoice->contact->flowaccount_id,
                'items' => $invoice->items->map(function ($item) {
                    return [
                        'description' => $item->description,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'tax_rate' => $item->tax_rate,
                    ];
                })->toArray(),
                'note' => $invoice->note,
            ];

            $response = $this->request('POST', '/invoices', $connection, $data);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('FlowAccount invoice sync failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Sync expense to FlowAccount
     */
    public function syncExpense($expense, AccountingFlowaccountConnection $connection): ?array
    {
        try {
            $data = [
                'document_type' => 'expense',
                'document_date' => $expense->document_date->format('Y-m-d'),
                'contact_id' => $expense->contact?->flowaccount_id,
                'items' => $expense->items->map(function ($item) {
                    return [
                        'description' => $item->description,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'tax_rate' => $item->tax_rate,
                    ];
                })->toArray(),
                'note' => $expense->note,
            ];

            $response = $this->request('POST', '/expenses', $connection, $data);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('FlowAccount expense sync failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get contacts from FlowAccount
     */
    public function getContacts(AccountingFlowaccountConnection $connection): ?array
    {
        try {
            $response = $this->request('GET', '/contacts', $connection);

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
     * Get products from FlowAccount
     */
    public function getProducts(AccountingFlowaccountConnection $connection): ?array
    {
        try {
            $response = $this->request('GET', '/products', $connection);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('FlowAccount get products failed: ' . $e->getMessage());
            return null;
        }
    }
}
