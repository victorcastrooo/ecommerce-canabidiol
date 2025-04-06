<?php
/**
 * NFeIOService - Brazilian Electronic Invoice (NF-e) integration
 * 
 * Handles NF-e generation, cancellation, and management for Canabidiol Commerce
 * with special handling for medical cannabis products.
 */
class NFeIOService {
    private $apiKey;
    private $apiUrl = 'https://api.nfe.io/v1/';
    private $sandboxMode = false;
    private $certificatePath;
    private $certificatePassword;
    private $companyInfo;
    private $productTaxCodes = [
        'cbd_oil' => '2106.90.90',
        'cbd_capsules' => '3004.90.90',
        'cbd_topical' => '3304.99.00'
    ];

    public function __construct($config) {
        $this->apiKey = $config['api_key'];
        $this->sandboxMode = $config['sandbox'] ?? false;
        $this->certificatePath = $config['certificate_path'];
        $this->certificatePassword = $config['certificate_password'];
        $this->companyInfo = $config['company_info'];
        
        if ($this->sandboxMode) {
            $this->apiUrl = 'https://sandbox.nfe.io/v1/';
        }
    }

    /**
     * Generate NF-e for an order
     */
    public function generateNFe($order, $customer, $products) {
        try {
            // Validate order before generating NF-e
            $this->validateOrderForNFe($order, $customer, $products);
            
            // Prepare NF-e payload
            $payload = [
                'environment' => $this->sandboxMode ? 'homologation' : 'production',
                'customer' => $this->prepareCustomerData($customer),
                'order' => $this->prepareOrderData($order),
                'products' => $this->prepareProductsData($products),
                'additional_information' => $this->getAdditionalInfo($products),
                'payment' => $this->preparePaymentData($order)
            ];

            // Add medical cannabis specific data
            if ($this->containsCannabisProducts($products)) {
                $payload['regulatory_information'] = $this->getRegulatoryInfo($order, $products);
            }

            $response = $this->apiRequest('invoices', $payload);

            // Save NF-e information to database
            $this->saveNFeInfo($order['id'], $response['id'], $response['access_key']);

            return [
                'nfe_id' => $response['id'],
                'access_key' => $response['access_key'],
                'number' => $response['number'],
                'series' => $response['series'],
                'pdf_url' => $response['pdf'],
                'xml_url' => $response['xml'],
                'status' => $response['status']
            ];

        } catch (Exception $e) {
            $this->logNFeError($order['id'], $e);
            throw new Exception("NF-e generation failed: " . $e->getMessage());
        }
    }

    /**
     * Cancel an NF-e
     */
    public function cancelNFe($nfeId, $reason = "Erro no processamento") {
        try {
            $payload = [
                'reason' => substr($reason, 0, 255),
                'environment' => $this->sandboxMode ? 'homologation' : 'production'
            ];

            $response = $this->apiRequest("invoices/{$nfeId}/cancel", $payload, 'POST');

            // Update NF-e status in database
            $this->updateNFeStatus($nfeId, 'cancelled');

            return [
                'cancellation_protocol' => $response['protocol'],
                'cancellation_date' => $response['processed_at'],
                'status' => 'cancelled'
            ];

        } catch (Exception $e) {
            throw new Exception("NF-e cancellation failed: " . $e->getMessage());
        }
    }

    /**
     * Get NF-e status
     */
    public function getNFeStatus($nfeId) {
        try {
            $response = $this->apiRequest("invoices/{$nfeId}", [], 'GET');

            return [
                'status' => $response['status'],
                'authorization_date' => $response['authorized_at'] ?? null,
                'access_key' => $response['access_key'],
                'pdf_url' => $response['pdf'],
                'xml_url' => $response['xml']
            ];

        } catch (Exception $e) {
            throw new Exception("Failed to get NF-e status: " . $e->getMessage());
        }
    }

    /**
     * Download NF-e PDF
     */
    public function downloadPdf($nfeId) {
        try {
            $response = $this->apiRequest("invoices/{$nfeId}/pdf", [], 'GET', true);
            return $response;

        } catch (Exception $e) {
            throw new Exception("Failed to download NF-e PDF: " . $e->getMessage());
        }
    }

    /**
     * Download NF-e XML
     */
    public function downloadXml($nfeId) {
        try {
            $response = $this->apiRequest("invoices/{$nfeId}/xml", [], 'GET', true);
            return $response;

        } catch (Exception $e) {
            throw new Exception("Failed to download NF-e XML: " . $e->getMessage());
        }
    }

    /**
     * Make API request with certificate authentication
     */
    private function apiRequest($endpoint, $data = [], $method = 'POST', $rawResponse = false) {
        $url = $this->apiUrl . $endpoint;
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: ' . $this->apiKey,
            'X-Platform: CanabidiolCommerce',
            'X-Version: 1.0'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSLCERT, $this->certificatePath);
        curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $this->certificatePassword);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'GET' && !empty($data)) {
            curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            $error = json_decode($response, true) ?? $response;
            throw new Exception("API Error: " . print_r($error, true), $httpCode);
        }

        return $rawResponse ? $response : json_decode($response, true);
    }

    /**
     * Validate order before generating NF-e
     */
    private function validateOrderForNFe($order, $customer, $products) {
        // Check required customer information
        $requiredCustomerFields = ['name', 'document', 'address', 'city', 'state', 'cep'];
        foreach ($requiredCustomerFields as $field) {
            if (empty($customer[$field])) {
                throw new Exception("Customer {$field} is required for NF-e");
            }
        }

        // Check products
        if (empty($products)) {
            throw new Exception("At least one product is required for NF-e");
        }

        // Check for cannabis products without prescription
        if ($this->containsCannabisProducts($products) && empty($order['prescription_id'])) {
            throw new Exception("Prescription is required for cannabis products");
        }
    }

    /**
     * Prepare customer data for NF-e
     */
    private function prepareCustomerData($customer) {
        return [
            'taxpayer_id' => preg_replace('/[^0-9]/', '', $customer['document']),
            'name' => substr($customer['name'], 0, 60), // NF-e name limit
            'email' => $customer['email'] ?? '',
            'address' => [
                'street' => substr($customer['address'], 0, 60),
                'number' => $customer['number'] ?? 'S/N',
                'complement' => substr($customer['complement'] ?? '', 0, 60),
                'neighborhood' => substr($customer['neighborhood'] ?? '', 0, 60),
                'city' => $customer['city'],
                'state' => $customer['state'],
                'postal_code' => preg_replace('/[^0-9]/', '', $customer['cep'])
            ]
        ];
    }

    /**
     * Prepare order data for NF-e
     */
    private function prepareOrderData($order) {
        return [
            'id' => $order['id'],
            'issued_at' => date('c', strtotime($order['created_at'])),
            'shipping_cost' => $order['shipping_cost'] ?? 0,
            'discount' => $order['discount'] ?? 0
        ];
    }

    /**
     * Prepare products data for NF-e
     */
    private function prepareProductsData($products) {
        $items = [];
        
        foreach ($products as $product) {
            $taxCode = $this->productTaxCodes[$product['category']] ?? '2106.90.90';
            $name = $this->sanitizeProductDescription($product['name']);

            $items[] = [
                'code' => $product['id'],
                'description' => $name,
                'cfop' => '5102', // CFOP for e-commerce
                'ncm' => $taxCode,
                'quantity' => $product['quantity'],
                'unit_price' => $product['price'],
                'total_price' => $product['price'] * $product['quantity'],
                'unit' => 'UN', // Unit type (UN = unit)
                'taxes' => $this->calculateTaxes($product)
            ];
        }
        
        return $items;
    }

    /**
     * Calculate taxes for product
     */
    private function calculateTaxes($product) {
        // Basic tax calculation - adjust according to your needs
        $taxRate = 0.18; // 18% average tax rate
        
        if ($this->isCannabisProduct($product)) {
            $taxRate = 0.12; // Reduced rate for medical products
        }
        
        return [
            'icms' => [
                'origin' => 0, // 0 = National
                'cst' => '00', // CST 00 = Taxed normally
                'rate' => $taxRate
            ],
            'pis' => [
                'cst' => '01', // CST 01 = Taxed
                'rate' => 0.0065 // 0.65%
            ],
            'cofins' => [
                'cst' => '01', // CST 01 = Taxed
                'rate' => 0.03 // 3%
            ]
        ];
    }

    /**
     * Prepare payment data for NF-e
     */
    private function preparePaymentData($order) {
        $paymentMethods = [
            'credit_card' => '03',
            'debit_card' => '03',
            'boleto' => '15',
            'pix' => '16',
            'mercado_pago' => '99'
        ];
        
        $method = $paymentMethods[$order['payment_method']] ?? '99';
        
        return [
            'method' => $method,
            'amount' => $order['total'],
            'installments' => $order['installments'] ?? 1
        ];
    }

    /**
     * Get regulatory information for cannabis products
     */
    private function getRegulatoryInfo($order, $products) {
        $cannabisProducts = array_filter($products, [$this, 'isCannabisProduct']);
        
        return [
            'anvisa_notification' => true,
            'prescription_id' => $order['prescription_id'],
            'medical_purpose' => true,
            'products' => array_map(function($product) {
                return [
                    'product_id' => $product['id'],
                    'concentration' => $product['concentration'] ?? null,
                    'anvisa_registration' => $product['anvisa_registration'] ?? null
                ];
            }, $cannabisProducts)
        ];
    }

    /**
     * Get additional information for NF-e
     */
    private function getAdditionalInfo($products) {
        $info = [
            'fiscal_reference' => 'Nota fiscal emitida conforme Lei nº 13.874/2019',
            'additional_data' => []
        ];

        if ($this->containsCannabisProducts($products)) {
            $info['additional_data'][] = 'Produto sujeito a controle especial - Portaria 344/98';
            $info['additional_data'][] = 'Venda condicionada à prescrição médica';
        }

        return $info;
    }

    /**
     * Check if product is cannabis-related
     */
    private function isCannabisProduct($product) {
        return stripos($product['category'], 'cannabis') !== false || 
               stripos($product['name'], 'cbd') !== false ||
               stripos($product['name'], 'thc') !== false;
    }

    /**
     * Check if order contains cannabis products
     */
    private function containsCannabisProducts($products) {
        foreach ($products as $product) {
            if ($this->isCannabisProduct($product)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Sanitize product description for NF-e
     */
    private function sanitizeProductDescription($description) {
        $description = preg_replace('/\b(thc|cbd|canabidiol|maconha|marijuana)\b/i', '**', $description);
        return substr($description, 0, 100); // NF-e description limit
    }

    /**
     * Save NF-e information to database
     */
    private function saveNFeInfo($orderId, $nfeId, $accessKey) {
        $db = new Database();
        $db->query("
            INSERT INTO nfe 
            (pedido_id, nfe_id, chave_acesso, status, data_emissao)
            VALUES 
            (:order_id, :nfe_id, :access_key, 'pending', NOW())
        ");
        $db->bind(':order_id', $orderId);
        $db->bind(':nfe_id', $nfeId);
        $db->bind(':access_key', $accessKey);
        $db->execute();
    }

    /**
     * Update NF-e status in database
     */
    private function updateNFeStatus($nfeId, $status) {
        $db = new Database();
        $db->query("
            UPDATE nfe 
            SET status = :status, 
                data_atualizacao = NOW() 
            WHERE nfe_id = :nfe_id
        ");
        $db->bind(':status', $status);
        $db->bind(':nfe_id', $nfeId);
        $db->execute();
    }

    /**
     * Log NF-e error
     */
    private function logNFeError($orderId, Exception $e) {
        $db = new Database();
        $db->query("
            INSERT INTO nfe_erros 
            (pedido_id, erro, stack_trace, data_registro)
            VALUES 
            (:order_id, :error, :trace, NOW())
        ");
        $db->bind(':order_id', $orderId);
        $db->bind(':error', $e->getMessage());
        $db->bind(':trace', $e->getTraceAsString());
        $db->execute();
    }
}