<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PathaoService
{
    protected $baseUrl;
    protected $clientId;
    protected $clientSecret;
    protected $username;
    protected $password;
    protected $storeId;

    public function __construct()
    {
        $this->baseUrl = config('services.pathao.base_url', 'https://api-hermes.pathao.com');
        $this->clientId = config('services.pathao.client_id');
        $this->clientSecret = config('services.pathao.client_secret');
        $this->username = config('services.pathao.username');
        $this->password = config('services.pathao.password');
        $this->storeId = config('services.pathao.store_id');
    }

    /**
     * Set store ID dynamically for multi-store operations
     * @param int|string $storeId Pathao store ID
     * @return self
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
        return $this;
    }

    /**
     * Convert money/amount input to integer BDT for Pathao.
     * Accepts numbers or strings like "2500.00" / "2,500.00".
     * Pathao API REQUIRES amount_to_collect as INTEGER (no decimals).
     */
    private function toIntAmount($value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        $clean = str_replace([',', ' '], '', (string) $value);
        return (int) round((float) $clean);
    }

    /**
     * Get access token from Pathao API
     */
    public function getAccessToken()
    {
        $cacheKey = 'pathao_access_token';

        return Cache::remember($cacheKey, now()->addMinutes(50), function () {
            try {
                // Fail fast with clear message if credentials are missing
                $missing = [];
                if (empty($this->clientId)) $missing[] = 'PATHAO_CLIENT_ID';
                if (empty($this->clientSecret)) $missing[] = 'PATHAO_CLIENT_SECRET';
                if (empty($this->username)) $missing[] = 'PATHAO_USERNAME';
                if (empty($this->password)) $missing[] = 'PATHAO_PASSWORD';
                if (!empty($missing)) {
                    throw new \Exception('Pathao credentials missing: ' . implode(', ', $missing) . '. Check .env and clear config cache.');
                }

                // Pathao requires JSON format (NOT form-encoded)
                $response = Http::timeout(30)
                    ->acceptJson()
                    ->post("{$this->baseUrl}/aladdin/api/v1/issue-token", [
                        'client_id' => $this->clientId,
                        'client_secret' => $this->clientSecret,
                        'username' => $this->username,
                        'password' => $this->password,
                        'grant_type' => 'password',
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['access_token'];
                }

                Log::error('Pathao Token Error', [
                    'base_url' => $this->baseUrl,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                $bodySnippet = mb_substr($response->body() ?? '', 0, 500);
                throw new \Exception('Failed to get Pathao access token (HTTP ' . $response->status() . '): ' . $bodySnippet);

            } catch (\Exception $e) {
                Log::error('Pathao Token Exception', [
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Make authenticated API call to Pathao
     */
    protected function callAPI($method, $endpoint, $data = [])
    {
        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->baseUrl($this->baseUrl)
            ->timeout(30)
            ->{$method}("/aladdin/api/v1/{$endpoint}", $data);

        return $response;
    }

    /**
     * Create a new order in Pathao
     */
    public function createOrder(array $orderData)
    {
        try {
            // ✅ Pathao requires amount_to_collect as INTEGER (no decimals, no string)
            if (array_key_exists('amount_to_collect', $orderData)) {
                $orderData['amount_to_collect'] = $this->toIntAmount($orderData['amount_to_collect']);
            } else {
                $orderData['amount_to_collect'] = 0;
            }

            // ✅ Also ensure quantity is integer (safe)
            if (array_key_exists('item_quantity', $orderData)) {
                $orderData['item_quantity'] = (int) $orderData['item_quantity'];
            }

            // Debug log to confirm it's an int before sending
            Log::info('Pathao Create Order Payload (normalized)', [
                'amount_to_collect' => $orderData['amount_to_collect'],
                'amount_type' => gettype($orderData['amount_to_collect']),
                'item_quantity' => $orderData['item_quantity'] ?? null,
            ]);

            $response = $this->callAPI('POST', 'orders', $orderData);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()['data'] ?? [],
                    'response' => $response->json(),
                    'status' => $response->status(),
                ];
            }

            $payload = null;
            try {
                $payload = $response->json();
            } catch (\Throwable $t) {
                $payload = null;
            }

            // Pathao may return message/errors instead of error
            $error =
                ($payload['message'] ?? null) ??
                ($payload['error']['message'] ?? null) ??
                ($payload['error'] ?? null) ??
                ($payload['errors'] ?? null) ??
                ($payload['data']['message'] ?? null) ??
                $response->body();

            if (is_array($error)) $error = json_encode($error);

            Log::error('Pathao Create Order Error', [
                'status' => $response->status(),
                'response' => $payload ?? $response->body(),
                'order_data' => $orderData, // normalized payload
            ]);

            return [
                'success' => false,
                'status' => $response->status(),
                'error' => $error ?: 'Unknown error',
                'response' => $payload ?? $response->body(),
            ];

        } catch (\Exception $e) {
            Log::error('Pathao Create Order Exception', [
                'error' => $e->getMessage(),
                'order_data' => $orderData
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get order details from Pathao
     */
    public function getOrder($consignmentId)
    {
        try {
            $response = $this->callAPI('GET', "orders/{$consignmentId}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()['data'] ?? [],
                    'response' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['error'] ?? 'Unknown error'
            ];

        } catch (\Exception $e) {
            Log::error('Pathao Get Order Exception', [
                'consignment_id' => $consignmentId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get available cities from Pathao
     */
    public function getCities()
    {
        try {
            $response = $this->callAPI('GET', 'countries/1/city-list');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'cities' => $response->json()['data']['data'] ?? []
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['error'] ?? 'Unknown error'
            ];

        } catch (\Exception $e) {
            Log::error('Pathao Get Cities Exception', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get zones for a city
     */
    public function getZones($cityId)
    {
        try {
            $response = $this->callAPI('GET', "cities/{$cityId}/zone-list");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'zones' => $response->json()['data']['data'] ?? []
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['error'] ?? 'Unknown error'
            ];

        } catch (\Exception $e) {
            Log::error('Pathao Get Zones Exception', [
                'city_id' => $cityId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get areas for a zone
     */
    public function getAreas($zoneId)
    {
        try {
            $response = $this->callAPI('GET', "zones/{$zoneId}/area-list");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'areas' => $response->json()['data']['data'] ?? []
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['error'] ?? 'Unknown error'
            ];

        } catch (\Exception $e) {
            Log::error('Pathao Get Areas Exception', [
                'zone_id' => $zoneId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Calculate delivery fee
     */
    public function calculatePrice(array $pricingData)
    {
        try {
            $response = $this->callAPI('POST', 'merchant/calculate-price', $pricingData);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()['data'] ?? []
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['error'] ?? 'Unknown error'
            ];

        } catch (\Exception $e) {
            Log::error('Pathao Calculate Price Exception', [
                'error' => $e->getMessage(),
                'pricing_data' => $pricingData
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get store information
     */
    public function getStoreInfo()
    {
        try {
            $response = $this->callAPI('GET', 'merchant/info');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()['data'] ?? []
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['error'] ?? 'Unknown error'
            ];

        } catch (\Exception $e) {
            Log::error('Pathao Get Store Info Exception', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Prepare order data for Pathao API
     */
    public function prepareOrderData($shipment, $overrideStoreId = null)
    {
        $store = $shipment->store;
        $customer = $shipment->customer;
        $order = $shipment->order;

        // Use store's pathao_store_id if available, otherwise use configured/override
        $pathaoStoreId = $overrideStoreId ?? ($store->pathao_store_id ?? $this->storeId);

        return [
            'store_id' => (int) $pathaoStoreId,
            'merchant_order_id' => $shipment->shipment_number,
            'recipient_name' => $shipment->recipient_name ?? $customer->name,
            'recipient_phone' => $shipment->recipient_phone ?? $customer->phone,
            'recipient_address' => $shipment->getDeliveryAddressFormatted(),
            'recipient_city' => $shipment->delivery_address['city'] ?? null,
            'recipient_zone' => $shipment->delivery_address['zone'] ?? null,
            'recipient_area' => $shipment->delivery_address['area'] ?? null,
            'delivery_type' => $shipment->delivery_type === 'express' ? 48 : 12, // 48 for express, 12 for regular
            'item_type' => 2, // 2 for parcel
            'special_instruction' => $shipment->special_instructions,
            'item_quantity' => (int) $order->items->sum('quantity'),
            'item_weight' => $shipment->package_weight ?? 0.5,

            // ✅ Pathao requires integer for amount_to_collect
            'amount_to_collect' => $this->toIntAmount($shipment->cod_amount ?? 0),

            'item_description' => $shipment->getPackageDescription(),
        ];
    }

    /**
     * Map Pathao status to local status
     */
    public static function mapPathaoStatus($pathaoStatus)
    {
        $statusMap = [
            'pending' => 'pending',
            'pickup_requested' => 'pickup_requested',
            'picked_up' => 'picked_up',
            'at_warehouse' => 'picked_up',
            'in_transit' => 'in_transit',
            'delivered' => 'delivered',
            'returned' => 'returned',
            'cancelled' => 'cancelled',
        ];

        return $statusMap[$pathaoStatus] ?? 'pending';
    }

    /**
     * Check if Pathao service is configured
     */
    public function isConfigured()
    {
        return !empty($this->clientId) &&
               !empty($this->clientSecret) &&
               !empty($this->username) &&
               !empty($this->password) &&
               !empty($this->storeId);
    }
}