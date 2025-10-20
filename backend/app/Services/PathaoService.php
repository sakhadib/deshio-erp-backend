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
     * Get access token from Pathao API
     */
    public function getAccessToken()
    {
        $cacheKey = 'pathao_access_token';

        return Cache::remember($cacheKey, now()->addMinutes(50), function () {
            try {
                $response = Http::timeout(30)->post("{$this->baseUrl}/aladdin/api/v1/issue-token", [
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
                    'response' => $response->body(),
                    'status' => $response->status()
                ]);

                throw new \Exception('Failed to get Pathao access token');

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
            $response = $this->callAPI('POST', 'orders', $orderData);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()['data'] ?? [],
                    'response' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['error'] ?? 'Unknown error',
                'response' => $response->json()
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
    public function prepareOrderData($shipment)
    {
        $store = $shipment->store;
        $customer = $shipment->customer;
        $order = $shipment->order;

        return [
            'store_id' => $this->storeId,
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
            'item_quantity' => $order->items->sum('quantity'),
            'item_weight' => $shipment->package_weight ?? 0.5,
            'amount_to_collect' => $shipment->cod_amount ?? 0,
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