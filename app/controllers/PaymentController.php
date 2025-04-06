<?php
namespace App\Controllers;

use App\Lib\Auth;
use App\Lib\Validator;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Client;
use App\Models\ActivityLog;
use App\Services\MercadoPagoService;
use App\Services\PixPaymentService;

class PaymentController extends BaseController
{
    private $auth;
    private $validator;
    private $currentUser;

    public function __construct()
    {
        parent::__construct();
        $this->auth = new Auth();
        $this->validator = new Validator();
        $this->currentUser = $this->auth->getCurrentUser();
    }

    /**
     * Processar pagamento de um pedido
     */
    public function process($orderId)
    {
        if (!$this->auth->isClient()) {
            $this->redirect('/auth/login');
        }

        $client = Client::findByUserId($this->currentUser->id);
        $order = Order::find($orderId);

        // Verificar se o pedido pertence ao cliente e está no status correto
        if (!$client || !$order || $order->cliente_id != $client->id || $order->status != 'pending') {
            $this->setFlash('error', 'Pedido inválido para pagamento');
            $this->redirect('/client/orders');
        }

        // Verificar se já existe um pagamento em processamento
        $existingPayment = Payment::getByOrderId($order->id);
        if ($existingPayment && $existingPayment->status == 'pending') {
            $this->redirectToPaymentGateway($existingPayment);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $paymentMethod = $_POST['payment_method'] ?? '';

            // Validar método de pagamento
            if (!in_array($paymentMethod, ['mercado_pago', 'pix', 'bank_transfer'])) {
                $this->setFlash('error', 'Método de pagamento inválido');
                $this->redirect('/client/orders/view/' . $order->id);
            }

            // Criar registro de pagamento
            $payment = new Payment([
                'pedido_id' => $order->id,
                'metodo' => $paymentMethod,
                'valor' => $order->total,
                'status' => 'pending',
                'data_pagamento' => null,
                'dados_transacao_json' => null
            ]);

            if ($payment->save()) {
                // Processar conforme o método selecionado
                switch ($paymentMethod) {
                    case 'mercado_pago':
                        $result = $this->processMercadoPago($order, $payment, $client);
                        break;
                    
                    case 'pix':
                        $result = $this->processPix($order, $payment, $client);
                        break;
                        
                    case 'bank_transfer':
                        $result = $this->processBankTransfer($order, $payment, $client);
                        break;
                }

                if ($result['status']) {
                    $this->redirectToPaymentGateway($payment);
                } else {
                    $this->setFlash('error', $result['message']);
                    $this->redirect('/client/orders/view/' . $order->id);
                }
            } else {
                $this->setFlash('error', 'Erro ao registrar pagamento');
                $this->redirect('/client/orders/view/' . $order->id);
            }
        }

        $this->render('client/payments/methods', [
            'order' => $order,
            'client' => $client,
            'mercadoPagoPublicKey' => getenv('MERCADOPAGO_PUBLIC_KEY')
        ]);
    }

    /**
     * Callback para notificações de pagamento
     */
    public function callback($gateway)
    {
        switch ($gateway) {
            case 'mercado_pago':
                $this->mercadoPagoCallback();
                break;
                
            case 'pix':
                $this->pixCallback();
                break;
                
            default:
                header('HTTP/1.1 404 Not Found');
                exit;
        }
    }

    /**
     * Retorno após tentativa de pagamento
     */
    public function return($orderId)
    {
        $order = Order::find($orderId);
        if (!$order) {
            $this->redirect('/client/orders');
        }

        $payment = Payment::getByOrderId($order->id);
        if (!$payment) {
            $this->redirect('/client/orders');
        }

        // Verificar permissão
        if ($this->auth->isClient()) {
            $client = Client::findByUserId($this->currentUser->id);
            if (!$client || $order->cliente_id != $client->id) {
                $this->redirect('/client/orders');
            }
        } elseif (!$this->auth->isAdmin()) {
            $this->redirect('/auth/login');
        }

        // Atualizar status do pagamento se necessário
        if ($payment->status == 'pending') {
            $this->checkPaymentStatus($payment);
            $payment->refresh();
        }

        $this->render('client/payments/result', [
            'order' => $order,
            'payment' => $payment
        ]);
    }

    /**
     * Aprovar pagamento via transferência bancária (admin)
     */
    public function approveBankTransfer($paymentId)
    {
        if (!$this->auth->isAdmin()) {
            $this->redirect('/auth/login');
        }

        $payment = Payment::find($paymentId);
        if (!$payment || $payment->metodo != 'bank_transfer' || $payment->status != 'pending') {
            $this->setFlash('error', 'Pagamento inválido para aprovação');
            $this->redirect('/admin/payments');
        }

        $payment->status = 'paid';
        $payment->data_pagamento = date('Y-m-d H:i:s');
        $payment->dados_transacao_json = json_encode([
            'approved_by' => $this->currentUser->id,
            'approval_date' => date('Y-m-d H:i:s')
        ]);

        if ($payment->save()) {
            // Atualizar status do pedido
            $order = Order::find($payment->pedido_id);
            $order->status = 'processing';
            $order->save();

            // Registrar atividade
            ActivityLog::create([
                'usuario_id' => $this->currentUser->id,
                'acao' => 'payment_approved',
                'tabela_afetada' => 'pagamentos',
                'registro_id' => $payment->id,
                'dados_anteriores' => json_encode(['status' => 'pending']),
                'dados_novos' => json_encode(['status' => 'paid'])
            ]);

            $this->setFlash('success', 'Pagamento aprovado com sucesso');
        } else {
            $this->setFlash('error', 'Erro ao aprovar pagamento');
        }

        $this->redirect('/admin/payments');
    }

    /**
     * Rejeitar pagamento via transferência bancária (admin)
     */
    public function rejectBankTransfer($paymentId)
    {
        if (!$this->auth->isAdmin()) {
            $this->redirect('/auth/login');
        }

        $payment = Payment::find($paymentId);
        if (!$payment || $payment->metodo != 'bank_transfer' || $payment->status != 'pending') {
            $this->setFlash('error', 'Pagamento inválido para rejeição');
            $this->redirect('/admin/payments');
        }

        $reason = $_POST['reason'] ?? 'Pagamento não identificado';

        $payment->status = 'rejected';
        $payment->dados_transacao_json = json_encode([
            'rejected_by' => $this->currentUser->id,
            'rejection_date' => date('Y-m-d H:i:s'),
            'reason' => $reason
        ]);

        if ($payment->save()) {
            // Registrar atividade
            ActivityLog::create([
                'usuario_id' => $this->currentUser->id,
                'acao' => 'payment_rejected',
                'tabela_afetada' => 'pagamentos',
                'registro_id' => $payment->id,
                'dados_anteriores' => json_encode(['status' => 'pending']),
                'dados_novos' => json_encode(['status' => 'rejected', 'reason' => $reason])
            ]);

            $this->setFlash('success', 'Pagamento rejeitado com sucesso');
        } else {
            $this->setFlash('error', 'Erro ao rejeitar pagamento');
        }

        $this->redirect('/admin/payments');
    }

    /**
     * Processar pagamento com Mercado Pago
     */
    private function processMercadoPago($order, $payment, $client)
    {
        $mpService = new MercadoPagoService();
        $result = $mpService->createPayment($order, $client);

        if ($result['status']) {
            $payment->codigo_transacao = $result['transaction_id'];
            $payment->dados_transacao_json = json_encode($result['response']);
            $payment->save();

            return [
                'status' => true,
                'redirect_url' => $result['redirect_url']
            ];
        }

        return [
            'status' => false,
            'message' => $result['message']
        ];
    }

    /**
     * Processar pagamento com Pix
     */
    private function processPix($order, $payment, $client)
    {
        $pixService = new PixPaymentService();
        $result = $pixService->generateCharge($order, $client);

        if ($result['status']) {
            $payment->codigo_transacao = $result['transaction_id'];
            $payment->dados_transacao_json = json_encode([
                'pix_code' => $result['pix_code'],
                'pix_qr_code' => $result['pix_qr_code'],
                'expires_at' => $result['expires_at']
            ]);
            $payment->save();

            return [
                'status' => true,
                'redirect_url' => '/client/payments/pix/' . $order->id
            ];
        }

        return [
            'status' => false,
            'message' => $result['message']
        ];
    }

    /**
     * Processar transferência bancária
     */
    private function processBankTransfer($order, $payment, $client)
    {
        // Validar comprovante
        if (empty($_FILES['receipt']['name'])) {
            return [
                'status' => false,
                'message' => 'É necessário enviar o comprovante de transferência'
            ];
        }

        $receiptPath = $this->uploadReceipt($_FILES['receipt']);
        if (!$receiptPath) {
            return [
                'status' => false,
                'message' => 'Erro ao enviar comprovante'
            ];
        }

        // Salvar dados da transferência
        $payment->dados_transacao_json = json_encode([
            'bank_name' => $_POST['bank_name'] ?? '',
            'account_name' => $_POST['account_name'] ?? '',
            'account_number' => $_POST['account_number'] ?? '',
            'receipt_path' => $receiptPath,
            'submitted_at' => date('Y-m-d H:i:s')
        ]);
        $payment->save();

        // Registrar atividade
        ActivityLog::create([
            'usuario_id' => $client->usuario_id,
            'acao' => 'bank_transfer_submitted',
            'tabela_afetada' => 'pagamentos',
            'registro_id' => $payment->id,
            'dados_novos' => $payment->dados_transacao_json
        ]);

        return [
            'status' => true,
            'redirect_url' => '/client/payments/bank-transfer/' . $order->id
        ];
    }

    /**
     * Callback do Mercado Pago
     */
    private function mercadoPagoCallback()
    {
        $mpService = new MercadoPagoService();
        $notification = file_get_contents('php://input');
        $data = json_decode($notification, true);

        if (empty($data['data']['id'])) {
            header('HTTP/1.1 400 Bad Request');
            exit;
        }

        $paymentId = $data['data']['id'];
        $paymentInfo = $mpService->getPayment($paymentId);

        if (!$paymentInfo) {
            header('HTTP/1.1 404 Not Found');
            exit;
        }

        // Encontrar o pagamento no banco de dados
        $payment = Payment::where('codigo_transacao', $paymentId)->first();
        if (!$payment) {
            header('HTTP/1.1 404 Not Found');
            exit;
        }

        // Atualizar status
        $newStatus = $this->mapMercadoPagoStatus($paymentInfo['status']);
        if ($payment->status != $newStatus) {
            $previousStatus = $payment->status;
            $payment->status = $newStatus;
            $payment->data_pagamento = $paymentInfo['date_approved'] ?? null;
            $payment->dados_transacao_json = json_encode($paymentInfo);
            $payment->save();

            // Atualizar pedido se o pagamento foi aprovado
            if ($newStatus == 'paid') {
                $order = Order::find($payment->pedido_id);
                $order->status = 'processing';
                $order->save();
            }

            // Registrar atividade
            ActivityLog::create([
                'acao' => 'payment_status_changed',
                'tabela_afetada' => 'pagamentos',
                'registro_id' => $payment->id,
                'dados_anteriores' => json_encode(['status' => $previousStatus]),
                'dados_novos' => json_encode(['status' => $newStatus])
            ]);
        }

        header('HTTP/1.1 200 OK');
        exit;
    }

    /**
     * Callback para Pix
     */
    private function pixCallback()
    {
        $pixService = new PixPaymentService();
        $notification = file_get_contents('php://input');
        $data = json_decode($notification, true);

        if (empty($data['endToEndId'])) {
            header('HTTP/1.1 400 Bad Request');
            exit;
        }

        // Verificar pagamento
        $charge = $pixService->verifyCharge($data['endToEndId']);
        if (!$charge) {
            header('HTTP/1.1 404 Not Found');
            exit;
        }

        // Encontrar pagamento no banco de dados
        $payment = Payment::where('codigo_transacao', $charge['txid'])->first();
        if (!$payment) {
            header('HTTP/1.1 404 Not Found');
            exit;
        }

        // Atualizar status
        if ($payment->status != $charge['status']) {
            $previousStatus = $payment->status;
            $payment->status = $charge['status'];
            $payment->data_pagamento = $charge['paid_at'] ?? null;
            $payment->dados_transacao_json = json_encode($charge);
            $payment->save();

            // Atualizar pedido se o pagamento foi aprovado
            if ($charge['status'] == 'paid') {
                $order = Order::find($payment->pedido_id);
                $order->status = 'processing';
                $order->save();
            }

            // Registrar atividade
            ActivityLog::create([
                'acao' => 'payment_status_changed',
                'tabela_afetada' => 'pagamentos',
                'registro_id' => $payment->id,
                'dados_anteriores' => json_encode(['status' => $previousStatus]),
                'dados_novos' => json_encode(['status' => $charge['status']])
            ]);
        }

        header('HTTP/1.1 200 OK');
        exit;
    }

    /**
     * Verificar status do pagamento
     */
    private function checkPaymentStatus($payment)
    {
        switch ($payment->metodo) {
            case 'mercado_pago':
                $mpService = new MercadoPagoService();
                $status = $mpService->getPaymentStatus($payment->codigo_transacao);
                if ($status) {
                    $payment->status = $this->mapMercadoPagoStatus($status);
                    $payment->save();
                }
                break;
                
            case 'pix':
                $pixService = new PixPaymentService();
                $status = $pixService->getChargeStatus($payment->codigo_transacao);
                if ($status) {
                    $payment->status = $status;
                    $payment->save();
                }
                break;
        }

        // Atualizar pedido se o pagamento foi concluído
        if ($payment->status == 'paid') {
            $order = Order::find($payment->pedido_id);
            if ($order->status == 'pending') {
                $order->status = 'processing';
                $order->save();
            }
        }
    }

    /**
     * Mapear status do Mercado Pago para nosso sistema
     */
    private function mapMercadoPagoStatus($mpStatus)
    {
        switch ($mpStatus) {
            case 'approved':
                return 'paid';
            case 'pending':
            case 'in_process':
                return 'pending';
            case 'authorized':
                return 'authorized';
            case 'rejected':
            case 'cancelled':
            case 'refunded':
            case 'charged_back':
                return 'rejected';
            default:
                return 'pending';
        }
    }

    /**
     * Redirecionar para gateway de pagamento
     */
    private function redirectToPaymentGateway($payment)
    {
        switch ($payment->metodo) {
            case 'mercado_pago':
                $this->redirect($payment->dados_transacao_json['redirect_url'] ?? '/client/orders');
                break;
                
            case 'pix':
                $this->redirect('/client/payments/pix/' . $payment->pedido_id);
                break;
                
            case 'bank_transfer':
                $this->redirect('/client/payments/bank-transfer/' . $payment->pedido_id);
                break;
                
            default:
                $this->redirect('/client/orders');
        }
    }

    /**
     * Upload de comprovante de transferência
     */
    private function uploadReceipt($file)
    {
        $uploadDir = ROOT_PATH . '/public/uploads/payments/';
        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            $this->setFlash('error', 'Tipo de arquivo não permitido (apenas JPG, PNG ou PDF)');
            return false;
        }
        
        if ($file['size'] > $maxSize) {
            $this->setFlash('error', 'Arquivo muito grande (máximo 5MB)');
            return false;
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'receipt_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $destination = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return '/uploads/payments/' . $filename;
        }
        
        $this->setFlash('error', 'Erro ao fazer upload do comprovante');
        return false;
    }

    /**
     * Página de pagamento com Pix
     */
    public function pixPayment($orderId)
    {
        if (!$this->auth->isClient()) {
            $this->redirect('/auth/login');
        }

        $client = Client::findByUserId($this->currentUser->id);
        $order = Order::find($orderId);
        $payment = Payment::getByOrderId($orderId);

        if (!$client || !$order || !$payment || $order->cliente_id != $client->id || $payment->metodo != 'pix') {
            $this->redirect('/client/orders');
        }

        $pixData = json_decode($payment->dados_transacao_json, true);

        $this->render('client/payments/pix', [
            'order' => $order,
            'payment' => $payment,
            'pixCode' => $pixData['pix_code'] ?? '',
            'pixQrCode' => $pixData['pix_qr_code'] ?? '',
            'expiresAt' => $pixData['expires_at'] ?? ''
        ]);
    }

    /**
     * Página de pagamento com transferência bancária
     */
    public function bankTransferPayment($orderId)
    {
        if (!$this->auth->isClient()) {
            $this->redirect('/auth/login');
        }

        $client = Client::findByUserId($this->currentUser->id);
        $order = Order::find($orderId);
        $payment = Payment::getByOrderId($orderId);

        if (!$client || !$order || !$payment || $order->cliente_id != $client->id || $payment->metodo != 'bank_transfer') {
            $this->redirect('/client/orders');
        }

        $transferData = json_decode($payment->dados_transacao_json, true);

        $this->render('client/payments/bank_transfer', [
            'order' => $order,
            'payment' => $payment,
            'transferData' => $transferData
        ]);
    }
}