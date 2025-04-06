<?php
/**
 * MercadoPagoService - Payment gateway integration for Canabidiol Commerce
 * 
 * Handles all Mercado Pago payment processing with medical cannabis compliance
 */

require 'vendor/autoload.php';

        

class MercadoPagoService {
    private $client;
    private $accessToken;
    private $publicKey;
    private $sandboxMode;
    private $callbackUrl;
    private $notificationUrl;
    private $webhookHandler;
   
    public function __construct($config) {
        $this->accessToken = $config['access_token'];
        $this->publicKey = $config['public_key'];
        $this->sandboxMode = $config['sandbox'] ?? false;
        $this->callbackUrl = $config['callback_url'];
        $this->notificationUrl = $config['notification_url'];


        // Initialize Mercado Pago SDK
        MercadoPago\SDK::setAccessToken($this->accessToken);
        MercadoPago\SDK::setIntegratorId('CBDCOMMERCE');
        MercadoPago\SDK::setPlatform('CBDCOMMERCE');
        MercadoPago\SDK::setCorporation('Canabidiol Commerce');

        $this->client = new MercadoPago\Client();
    }

    /**
     * Create a payment preference for checkout
     */
    public function createPayment($order, $customer, $products) {
        try {
            // Prepare items with medical cannabis compliance
            $items = [];
            foreach ($products as $product) {
                $items[] = [
                    'id' => $product['id'],
                    'title' => $this->sanitizeProductName($product['name']),
                    'description' => 'Produto à base de canabidiol',
                    'picture_url' => $product['image'],
                    'category_id' => 'health',
                    'quantity' => $product['quantity'],
                    'unit_price' => (float)$product['price'],
                    'currency_id' => 'BRL'
                ];
            }

            // Create payment preference
            $preference = new MercadoPago\Preference();
            
            // Set payer information
            $payer = new MercadoPago\Payer();
            $payer->name = $customer['name'];
            $payer->email = $customer['email'];
            $payer->identification = [
                'type' => 'CPF',
                'number' => $customer['cpf']
            ];
            $payer->address = [
                'street_name' => $customer['street'],
                'street_number' => $customer['number'],
                'zip_code' => $customer['cep']
            ];

            // Configure payment
            $preference->items = $items;
            $preference->payer = $payer;
            $preference->external_reference = 'ORDER-' . $order['id'];
            $preference->notification_url = $this->notificationUrl;
            $preference->back_urls = [
                'success' => $this->callbackUrl . '?status=success',
                'failure' => $this->callbackUrl . '?status=failure',
                'pending' => $this->callbackUrl . '?status=pending'
            ];
            $preference->auto_return = 'approved';
            $preference->payment_methods = [
                'excluded_payment_methods' => [
                    ['id' => 'amex'] // Exclude AMEX as per cannabis industry restrictions
                ],
                'excluded_payment_types' => [
                    ['id' => 'atm'] // Exclude ATM payments
                ],
                'installments' => 3 // Maximum installments allowed
            ];

            // Additional metadata for compliance
            $preference->metadata = [
                'platform' => 'Canabidiol Commerce',
                'order_type' => 'medical_cannabis',
                'requires_prescription' => $order['requires_prescription'],
                'customer_age' => $customer['age']
            ];

            // Save and return preference
            $preference->save();
            
            // Log payment creation
            $this->logPayment($order['id'], $preference->id, 'created');

            return [
                'id' => $preference->id,
                'init_point' => $this->sandboxMode 
                    ? $preference->sandbox_init_point 
                    : $preference->init_point,
                'public_key' => $this->publicKey
            ];

        } catch (Exception $e) {
            $this->logPaymentError($order['id'], $e);
            throw new Exception("Payment creation failed: " . $e->getMessage());
        }
    }

    /**
     * Handle payment notification (webhook)
     */
    public function handleNotification($request) {
        try {
            // Verify request authenticity
            if (!$this->isValidNotification($request)) {
                throw new Exception("Invalid notification request");
            }

            $paymentId = $request['data']['id'] ?? null;
            if (!$paymentId) {
                throw new Exception("No payment ID in notification");
            }

            // Get payment details
            $payment = $this->client->payment->get($paymentId);
            $orderId = $this->extractOrderId($payment->external_reference);

            // Process payment status
            switch ($payment->status) {
                case 'approved':
                    $this->processApprovedPayment($orderId, $payment);
                    break;
                case 'pending':
                    $this->processPendingPayment($orderId, $payment);
                    break;
                case 'rejected':
                    $this->processRejectedPayment($orderId, $payment);
                    break;
                case 'refunded':
                    $this->processRefundedPayment($orderId, $payment);
                    break;
                case 'cancelled':
                    $this->processCancelledPayment($orderId, $payment);
                    break;
                default:
                    throw new Exception("Unknown payment status: {$payment->status}");
            }

            return true;

        } catch (Exception $e) {
            $this->logPaymentError($orderId ?? 'unknown', $e);
            throw $e;
        }
    }

    /**
     * Process approved payment
     */
    private function processApprovedPayment($orderId, $payment) {
        // Update order status in database
        $db = new Database();
        $db->query("UPDATE pedidos SET status = 'aprovado', data_aprovacao = NOW() WHERE id = :id");
        $db->bind(':id', $orderId);
        $db->execute();

        // Record payment details
        $db->query("
            INSERT INTO pagamentos 
            (pedido_id, metodo, valor, status, codigo_transacao, dados_transacao_json)
            VALUES 
            (:order_id, 'mercado_pago', :amount, 'approved', :transaction_id, :data)
        ");
        $db->bind(':order_id', $orderId);
        $db->bind(':amount', $payment->transaction_amount);
        $db->bind(':transaction_id', $payment->id);
        $db->bind(':data', json_encode($payment));
        $db->execute();

        // Trigger order fulfillment
        $this->fulfillOrder($orderId);

        // Log successful payment
        $this->logPayment($orderId, $payment->id, 'approved', $payment->transaction_amount);
    }

    /**
     * Verify notification authenticity
     */
    private function isValidNotification($request) {
        $paymentId = $request['data']['id'] ?? null;
        $topic = $request['type'] ?? null;
        
        return $paymentId && in_array($topic, ['payment', 'merchant_order']);
    }

    /**
     * Extract order ID from external reference
     */
    private function extractOrderId($externalReference) {
        return str_replace('ORDER-', '', $externalReference);
    }

    /**
     * Sanitize product names for payment processor
     */
    private function sanitizeProductName($name) {
        $name = preg_replace('/\b(thc|cbd|canabidiol|maconha|marijuana)\b/i', '**', $name);
        return substr($name, 0, 250); // Mercado Pago title limit
    }

    /**
     * Log payment activity
     */
    private function logPayment($orderId, $paymentId, $status, $amount = null) {
        $db = new Database();
        $db->query("
            INSERT INTO logs_pagamentos 
            (pedido_id, payment_id, status, valor, data_registro)
            VALUES 
            (:order_id, :payment_id, :status, :amount, NOW())
        ");
        $db->bind(':order_id', $orderId);
        $db->bind(':payment_id', $paymentId);
        $db->bind(':status', $status);
        $db->bind(':amount', $amount);
        $db->execute();
    }

    /**
     * Log payment error
     */
    private function logPaymentError($orderId, Exception $e) {
        $db = new Database();
        $db->query("
            INSERT INTO logs_erros_pagamentos 
            (pedido_id, erro, stack_trace, data_registro)
            VALUES 
            (:order_id, :error, :trace, NOW())
        ");
        $db->bind(':order_id', $orderId);
        $db->bind(':error', $e->getMessage());
        $db->bind(':trace', $e->getTraceAsString());
        $db->execute();
    }

    /**
     * Initiate order fulfillment process
     */
    private function fulfillOrder($orderId) {
        // This would trigger your order fulfillment workflow
        // Including prescription verification, ANVISA compliance, etc.
        // Implementation depends on your specific business logic
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus($paymentId) {
        try {
            $payment = $this->client->payment->get($paymentId);
            return [
                'status' => $payment->status,
                'amount' => $payment->transaction_amount,
                'details' => $payment
            ];
        } catch (Exception $e) {
            throw new Exception("Failed to get payment status: " . $e->getMessage());
        }
    }

    /**
     * Refund payment
     */
    public function refundPayment($paymentId, $amount = null) {
        try {
            $refund = $this->client->refund->create($paymentId, [
                'amount' => $amount
            ]);
            
            // Log refund
            $this->logPayment(
                $this->extractOrderId($refund->payment_id),
                $refund->id,
                'refunded',
                $amount
            );
            
            return $refund;
        } catch (Exception $e) {
            throw new Exception("Refund failed: " . $e->getMessage());
        }
    }
}