<?php
/**
 * TrixExpressService - Shipping integration for Canabidiol Commerce
 * 
 * Handles shipping calculations, label generation, and tracking
 * with special handling for medical cannabis products.
 */
class TrixExpressService {
    private $apiKey;
    private $apiUrl = 'https://api.trixexpress.com.br/v1/';
    private $sandboxMode = false;
    private $senderInfo;
    private $defaultPackage = [
        'height' => 10,  // cm
        'width' => 15,   // cm
        'length' => 20,  // cm
        'weight' => 0.5  // kg
    ];

    public function __construct($config) {
        $this->apiKey = $config['api_key'];
        $this->sandboxMode = $config['sandbox'] ?? false;
        $this->senderInfo = $config['sender_info'];
        
        if ($this->sandboxMode) {
            $this->apiUrl = 'https://sandbox.trixexpress.com.br/v1/';
        }
    }

    /**
     * Calculate shipping costs
     */
    public function calculateShipping($destination, $products, $options = []) {
        try {
            // Prepare package dimensions based on products
            $package = $this->calculatePackageDimensions($products);
            
            // Prepare request payload
            $payload = [
                'sender' => $this->senderInfo,
                'destination' => $destination,
                'package' => array_merge($this->defaultPackage, $package),
                'options' => [
                    'declared_value' => $this->calculateDeclaredValue($products),
                    'insurance' => true,
                    'medical_cargo' => $this->containsCannabisProducts($products),
                    'adult_signature' => true // Required for cannabis products
                ]
            ];

            // Add delivery time estimate
            $payload['estimated_delivery'] = $this->estimateDeliveryTime($destination['cep']);

            $response = $this->apiRequest('shipping/calculate', $payload);

            return [
                'price' => $response['price'],
                'delivery_estimate' => $response['estimated_delivery'],
                'service' => $response['service_name'],
                'carrier' => 'TrixExpress',
                'options' => $response['available_options']
            ];

        } catch (Exception $e) {
            error_log("Shipping calculation failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate shipping label
     */
    public function generateLabel($order, $customer, $products) {
        try {
            $package = $this->calculatePackageDimensions($products);

            $payload = [
                'order_id' => $order['id'],
                'customer' => $customer,
                'sender' => $this->senderInfo,
                'package' => array_merge($this->defaultPackage, $package),
                'products' => $this->sanitizeProductNames($products),
                'options' => [
                    'print_format' => 'PDF',
                    'invoice' => true,
                    'medical_cargo' => $this->containsCannabisProducts($products),
                    'restricted_substance' => $this->containsCannabisProducts($products),
                    'anvisa_notification' => $this->needsAnvisaNotification($products)
                ]
            ];

            $response = $this->apiRequest('labels/generate', $payload);

            // Save label information to database
            $this->saveLabelInfo($order['id'], $response['tracking_code'], $response['label_url']);

            return [
                'tracking_code' => $response['tracking_code'],
                'label_url' => $response['label_url'],
                'invoice_url' => $response['invoice_url'] ?? null,
                'shipping_estimate' => $response['estimated_delivery']
            ];

        } catch (Exception $e) {
            error_log("Label generation failed: " . $e->getMessage());
            throw new Exception("Failed to generate shipping label: " . $e->getMessage());
        }
    }

    /**
     * Track shipment
     */
    public function trackShipment($trackingCode) {
        try {
            $response = $this->apiRequest("tracking/{$trackingCode}");

            return [
                'status' => $response['status'],
                'last_update' => $response['last_update'],
                'history' => $response['tracking_history'],
                'estimated_delivery' => $response['estimated_delivery'] ?? null,
                'carrier' => 'TrixExpress'
            ];

        } catch (Exception $e) {
            error_log("Tracking failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Cancel shipment
     */
    public function cancelShipment($trackingCode) {
        try {
            $response = $this->apiRequest("labels/cancel", ['tracking_code' => $trackingCode], 'DELETE');

            // Update database record
            $this->updateShipmentStatus($trackingCode, 'cancelled');

            return true;

        } catch (Exception $e) {
            error_log("Shipment cancellation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Make API request
     */
    private function apiRequest($endpoint, $data = [], $method = 'POST') {
        $url = $this->apiUrl . $endpoint;
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
            'X-Platform: CanabidiolCommerce',
            'X-Version: 1.0'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            $error = json_decode($response, true) ?? $response;
            throw new Exception("API Error: " . print_r($error, true), $httpCode);
        }

        return json_decode($response, true);
    }

    /**
     * Calculate package dimensions based on products
     */
    private function calculatePackageDimensions($products) {
        $totalVolume = 0;
        $totalWeight = 0;
        
        foreach ($products as $product) {
            $volume = $product['height'] * $product['width'] * $product['length'];
            $totalVolume += $volume * $product['quantity'];
            $totalWeight += $product['weight'] * $product['quantity'];
        }
        
        // Simple box size calculation (cube root of total volume)
        $sideLength = ceil(pow($totalVolume, 1/3));
        
        return [
            'height' => $sideLength,
            'width' => $sideLength,
            'length' => $sideLength,
            'weight' => $totalWeight
        ];
    }

    /**
     * Calculate declared value for customs
     */
    private function calculateDeclaredValue($products) {
        $total = 0;
        foreach ($products as $product) {
            $total += $product['price'] * $product['quantity'];
        }
        return $total;
    }

    /**
     * Check if products contain cannabis items
     */
    private function containsCannabisProducts($products) {
        foreach ($products as $product) {
            if (stripos($product['category'], 'cannabis') !== false || 
                stripos($product['name'], 'cbd') !== false ||
                stripos($product['name'], 'thc') !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if ANVISA notification is needed
     */
    private function needsAnvisaNotification($products) {
        foreach ($products as $product) {
            if ($product['requires_anvisa_approval']) {
                return true;
            }
        }
        return false;
    }

    /**
     * Sanitize product names for shipping
     */
    private function sanitizeProductNames($products) {
        return array_map(function($product) {
            $name = preg_replace('/\b(thc|cbd|canabidiol|maconha|marijuana)\b/i', '**', $product['name']);
            return [
                'description' => substr($name, 0, 100),
                'quantity' => $product['quantity'],
                'hs_code' => $product['hs_code'] ?? '3004.90.90' // Default HS code for medicaments
            ];
        }, $products);
    }

    /**
     * Estimate delivery time based on destination
     */
    private function estimateDeliveryTime($destinationCep) {
        // Simple estimation based on region
        $region = substr($destinationCep, 0, 1);
        
        switch ($region) {
            case '0': case '1': case '2': // North/Northeast
                return '7-10 business days';
            case '3': // Southeast
                return '3-5 business days';
            case '8': case '9': // South
                return '4-6 business days';
            default: // Central-west and others
                return '5-8 business days';
        }
    }

    /**
     * Save label information to database
     */
    private function saveLabelInfo($orderId, $trackingCode, $labelUrl) {
        $db = new Database();
        $db->query("
            INSERT INTO envios 
            (pedido_id, transportadora, codigo_rastreio, url_etiqueta, status, data_criacao)
            VALUES 
            (:order_id, 'TrixExpress', :tracking_code, :label_url, 'processing', NOW())
        ");
        $db->bind(':order_id', $orderId);
        $db->bind(':tracking_code', $trackingCode);
        $db->bind(':label_url', $labelUrl);
        $db->execute();
    }

    /**
     * Update shipment status in database
     */
    private function updateShipmentStatus($trackingCode, $status) {
        $db = new Database();
        $db->query("
            UPDATE envios 
            SET status = :status, 
                data_atualizacao = NOW() 
            WHERE codigo_rastreio = :tracking_code
        ");
        $db->bind(':status', $status);
        $db->bind(':tracking_code', $trackingCode);
        $db->execute();
    }
}