<?php
namespace App\Controllers;

use App\Lib\Auth;
use App\Lib\Database;
use App\Lib\Validator;
use App\Models\User;
use App\Models\Client;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Prescription;
use App\Models\AnvisaApproval;
use App\Models\Payment;
use App\Models\Cart;
use App\Services\MercadoPagoService;

class ClientController extends BaseController
{
    private $auth;
    private $db;
    private $validator;
    private $currentClient;

    public function __construct()
    {
        parent::__construct();
        $this->auth = new Auth();
        $this->db = Database::getInstance();
        $this->validator = new Validator();
        
        // Verificar se o usuário é cliente
        if (!$this->auth->isClient()) {
            $this->redirect('/auth/login');
        }

        // Carregar dados do cliente atual
        $this->currentClient = Client::findByUserId($this->auth->getUserId());
        if (!$this->currentClient) {
            $this->redirect('/auth/login');
        }
    }

    /**
     * Dashboard do cliente
     */
    public function dashboard()
    {
        $stats = [
            'total_orders' => Order::count(['cliente_id' => $this->currentClient->id]),
            'pending_orders' => Order::count([
                'cliente_id' => $this->currentClient->id,
                'status' => ['pending', 'processing', 'shipped']
            ]),
            'anvisa_approved' => AnvisaApproval::isApproved($this->currentClient->id),
            'prescriptions_approved' => Prescription::count([
                'cliente_id' => $this->currentClient->id,
                'aprovada' => 1
            ])
        ];

        $recentOrders = Order::getRecentByClient($this->currentClient->id, 3);

        $this->render('client/dashboard', [
            'stats' => $stats,
            'client' => $this->currentClient,
            'recentOrders' => $recentOrders,
            'anvisaApproval' => AnvisaApproval::getCurrentApproval($this->currentClient->id)
        ]);
    }

    /**
     * Gerenciamento de perfil
     */
    public function profile($action = 'view')
    {
        $user = User::find($this->currentClient->usuario_id);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $this->validateProfileData($_POST);
            
            if ($this->validator->validate($data, User::$profileRules)) {
                // Atualizar dados do usuário
                $user->fill($data);
                
                // Atualizar dados do cliente
                $this->currentClient->data_nascimento = $data['data_nascimento'] ?? null;
                $this->currentClient->genero = $data['genero'] ?? null;
                
                if ($user->save() && $this->currentClient->save()) {
                    $this->setFlash('success', 'Perfil atualizado com sucesso');
                    $this->redirect('/client/profile');
                } else {
                    $this->setFlash('error', 'Erro ao atualizar perfil');
                }
            } else {
                $this->setFlash('error', $this->validator->getErrors());
            }
        }
        
        $this->render('client/profile', [
            'user' => $user,
            'client' => $this->currentClient
        ]);
    }

    /**
     * Gerenciamento de pedidos
     */
    public function orders($action = 'index', $id = null)
    {
        switch ($action) {
            case 'index':
                $status = $_GET['status'] ?? 'all';
                $orders = Order::getByClient($this->currentClient->id, $status);
                $this->render('client/orders/index', [
                    'orders' => $orders,
                    'status' => $status,
                    'client' => $this->currentClient
                ]);
                break;
                
            case 'view':
                if ($order = Order::find($id)) {
                    // Verificar se o pedido pertence ao cliente
                    if ($order->cliente_id == $this->currentClient->id) {
                        $order->items = OrderItem::getByOrderId($order->id);
                        $order->prescription = Prescription::getByOrderId($order->id);
                        $order->payment = Payment::getByOrderId($order->id);
                        
                        $this->render('client/orders/view', [
                            'order' => $order,
                            'client' => $this->currentClient
                        ]);
                        return;
                    }
                }
                $this->redirect('/client/orders');
                break;
                
            case 'cancel':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && $order = Order::find($id)) {
                    // Verificar se o pedido pertence ao cliente e pode ser cancelado
                    if ($order->cliente_id == $this->currentClient->id && 
                        $order->status === 'pending') {
                        
                        $order->status = 'cancelled';
                        $order->motivo_cancelamento = $_POST['reason'] ?? 'Solicitado pelo cliente';
                        
                        if ($order->save()) {
                            // Reembolsar pagamento se já foi feito
                            if ($order->status_pagamento === 'paid') {
                                $this->processRefund($order);
                            }
                            
                            $this->setFlash('success', 'Pedido cancelado com sucesso');
                        } else {
                            $this->setFlash('error', 'Erro ao cancelar pedido');
                        }
                    }
                }
                $this->redirect('/client/orders/view/' . $id);
                break;
                
            default:
                $this->redirect('/client/orders');
        }
    }

    /**
     * Carrinho de compras
     */
    public function cart($action = 'view')
    {
        $cart = new Cart($this->currentClient->id);
        
        switch ($action) {
            case 'view':
                $cartItems = $cart->getItems();
                $products = Product::findMany(array_keys($cartItems));
                $subtotal = $cart->calculateSubtotal($products);
                
                $this->render('client/cart/view', [
                    'products' => $products,
                    'cartItems' => $cartItems,
                    'subtotal' => $subtotal,
                    'client' => $this->currentClient,
                    'anvisaApproved' => AnvisaApproval::isApproved($this->currentClient->id)
                ]);
                break;
                
            case 'add':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $productId = $_POST['product_id'] ?? null;
                    $quantity = $_POST['quantity'] ?? 1;
                    
                    if ($productId && $product = Product::find($productId)) {
                        $cart->addItem($productId, $quantity);
                        $this->setFlash('success', 'Produto adicionado ao carrinho');
                    }
                }
                $this->redirectBack();
                break;
                
            case 'update':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    foreach ($_POST['quantities'] as $productId => $quantity) {
                        if ($quantity > 0) {
                            $cart->updateItem($productId, $quantity);
                        } else {
                            $cart->removeItem($productId);
                        }
                    }
                    $this->setFlash('success', 'Carrinho atualizado');
                }
                $this->redirect('/client/cart');
                break;
                
            case 'remove':
                if ($productId = $_GET['product_id'] ?? null) {
                    $cart->removeItem($productId);
                    $this->setFlash('success', 'Produto removido do carrinho');
                }
                $this->redirect('/client/cart');
                break;
                
            default:
                $this->redirect('/client/cart');
        }
    }

    /**
     * Checkout
     */
    public function checkout()
    {
        // Verificar aprovação ANVISA
        if (!AnvisaApproval::isApproved($this->currentClient->id)) {
            $this->setFlash('error', 'Você precisa ter uma liberação ANVISA aprovada para realizar compras');
            $this->redirect('/client/anvisa/upload');
        }

        $cart = new Cart($this->currentClient->id);
        $cartItems = $cart->getItems();
        
        if (empty($cartItems)) {
            $this->setFlash('error', 'Seu carrinho está vazio');
            $this->redirect('/client/cart');
        }

        $products = Product::findMany(array_keys($cartItems));
        $subtotal = $cart->calculateSubtotal($products);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar prescrição médica se necessário
            $requiresPrescription = $this->checkProductsRequirePrescription($products);
            $prescriptionApproved = false;
            
            if ($requiresPrescription) {
                if (empty($_FILES['prescription']['name'])) {
                    $this->setFlash('error', 'É necessário enviar uma receita médica para os produtos selecionados');
                    $this->redirect('/client/checkout');
                }
                
                // Processar upload da receita
                $prescriptionPath = $this->uploadPrescription($_FILES['prescription']);
                if (!$prescriptionPath) {
                    $this->redirect('/client/checkout');
                }
                
                // Criar registro da receita (pendente de aprovação)
                $prescription = Prescription::create([
                    'cliente_id' => $this->currentClient->id,
                    'arquivo_path' => $prescriptionPath,
                    'data_upload' => date('Y-m-d H:i:s'),
                    'crm_medico' => $_POST['crm_medico'] ?? '',
                    'uf_crm' => $_POST['uf_crm'] ?? '',
                    'nome_medico' => $_POST['nome_medico'] ?? '',
                    'aprovada' => 0
                ]);
                
                if (!$prescription) {
                    $this->setFlash('error', 'Erro ao registrar receita médica');
                    $this->redirect('/client/checkout');
                }
            }
            
            // Criar o pedido
            $order = $this->createOrder($products, $cartItems, $subtotal, $prescription->id ?? null);
            
            if ($order) {
                // Processar pagamento
                $paymentResult = $this->processPayment($order);
                
                if ($paymentResult['status']) {
                    // Limpar carrinho
                    $cart->clear();
                    
                    // Redirecionar conforme o método de pagamento
                    if ($paymentResult['redirect']) {
                        $this->redirect($paymentResult['redirect_url']);
                    } else {
                        $this->setFlash('success', 'Pedido realizado com sucesso!');
                        $this->redirect('/client/orders/view/' . $order->id);
                    }
                } else {
                    $this->setFlash('error', $paymentResult['message']);
                    $this->redirect('/client/checkout');
                }
            } else {
                $this->setFlash('error', 'Erro ao criar pedido');
                $this->redirect('/client/checkout');
            }
        }
        
        $this->render('client/checkout', [
            'products' => $products,
            'cartItems' => $cartItems,
            'subtotal' => $subtotal,
            'client' => $this->currentClient,
            'requiresPrescription' => $this->checkProductsRequirePrescription($products),
            'mercadoPagoPublicKey' => getenv('MERCADOPAGO_PUBLIC_KEY')
        ]);
    }

    /**
     * Gerenciamento de liberações ANVISA
     */
    public function anvisa($action = 'upload')
    {
        switch ($action) {
            case 'upload':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (empty($_FILES['anvisa_file']['name'])) {
                        $this->setFlash('error', 'Selecione um arquivo para upload');
                        $this->redirect('/client/anvisa/upload');
                    }
                    
                    $filePath = $this->uploadAnvisaFile($_FILES['anvisa_file']);
                    if (!$filePath) {
                        $this->redirect('/client/anvisa/upload');
                    }
                    
                    // Criar registro da liberação ANVISA
                    $approval = AnvisaApproval::create([
                        'cliente_id' => $this->currentClient->id,
                        'numero_registro' => $_POST['registry_number'] ?? '',
                        'arquivo_path' => $filePath,
                        'data_validade' => $_POST['expiry_date'] ?? null,
                        'aprovado' => 0 // Pendente de aprovação
                    ]);
                    
                    if ($approval) {
                        $this->setFlash('success', 'Documentação enviada com sucesso. Aguarde aprovação.');
                        $this->redirect('/client/dashboard');
                    } else {
                        $this->setFlash('error', 'Erro ao registrar documentação');
                    }
                }
                
                $currentApproval = AnvisaApproval::getCurrentApproval($this->currentClient->id);
                $this->render('client/anvisa/upload', [
                    'client' => $this->currentClient,
                    'currentApproval' => $currentApproval
                ]);
                break;
                
            case 'history':
                $approvals = AnvisaApproval::getClientHistory($this->currentClient->id);
                $this->render('client/anvisa/history', [
                    'client' => $this->currentClient,
                    'approvals' => $approvals
                ]);
                break;
                
            default:
                $this->redirect('/client/anvisa/upload');
        }
    }

    /**
     * Gerenciamento de receitas médicas
     */
    public function prescriptions($action = 'upload')
    {
        switch ($action) {
            case 'upload':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (empty($_FILES['prescription_file']['name'])) {
                        $this->setFlash('error', 'Selecione um arquivo para upload');
                        $this->redirect('/client/prescriptions/upload');
                    }
                    
                    $filePath = $this->uploadPrescription($_FILES['prescription_file']);
                    if (!$filePath) {
                        $this->redirect('/client/prescriptions/upload');
                    }
                    
                    // Criar registro da receita
                    $prescription = Prescription::create([
                        'cliente_id' => $this->currentClient->id,
                        'arquivo_path' => $filePath,
                        'data_upload' => date('Y-m-d H:i:s'),
                        'crm_medico' => $_POST['crm_medico'] ?? '',
                        'uf_crm' => $_POST['uf_crm'] ?? '',
                        'nome_medico' => $_POST['nome_medico'] ?? '',
                        'aprovada' => 0 // Pendente de aprovação
                    ]);
                    
                    if ($prescription) {
                        $this->setFlash('success', 'Receita enviada com sucesso. Aguarde aprovação.');
                        $this->redirect('/client/prescriptions/history');
                    } else {
                        $this->setFlash('error', 'Erro ao registrar receita');
                    }
                }
                
                $this->render('client/prescriptions/upload', [
                    'client' => $this->currentClient
                ]);
                break;
                
            case 'history':
                $prescriptions = Prescription::getClientHistory($this->currentClient->id);
                $this->render('client/prescriptions/history', [
                    'client' => $this->currentClient,
                    'prescriptions' => $prescriptions
                ]);
                break;
                
            default:
                $this->redirect('/client/prescriptions/upload');
        }
    }

    /**
     * Métodos auxiliares privados
     */
    
    private function validateProfileData($data)
    {
        return [
            'nome' => trim($data['nome'] ?? ''),
            'email' => trim($data['email'] ?? ''),
            'telefone' => trim($data['telefone'] ?? ''),
            'cpf_cnpj' => trim($data['cpf_cnpj'] ?? ''),
            'endereco_cep' => trim($data['endereco_cep'] ?? ''),
            'endereco_logradouro' => trim($data['endereco_logradouro'] ?? ''),
            'endereco_numero' => trim($data['endereco_numero'] ?? ''),
            'endereco_complemento' => trim($data['endereco_complemento'] ?? ''),
            'endereco_cidade' => trim($data['endereco_cidade'] ?? ''),
            'endereco_estado' => trim($data['endereco_estado'] ?? ''),
            'data_nascimento' => $data['data_nascimento'] ?? null,
            'genero' => $data['genero'] ?? null
        ];
    }
    
    private function checkProductsRequirePrescription($products)
    {
        foreach ($products as $product) {
            if ($product->requer_receita) {
                return true;
            }
        }
        return false;
    }
    
    private function uploadPrescription($file)
    {
        $uploadDir = ROOT_PATH . '/public/uploads/prescriptions/';
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            $this->setFlash('error', 'Tipo de arquivo não permitido (apenas PDF, JPEG ou PNG)');
            return false;
        }
        
        if ($file['size'] > $maxSize) {
            $this->setFlash('error', 'Arquivo muito grande (máximo 5MB)');
            return false;
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'receita_' . $this->currentClient->id . '_' . time() . '.' . $extension;
        $destination = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return '/uploads/prescriptions/' . $filename;
        }
        
        $this->setFlash('error', 'Erro ao fazer upload do arquivo');
        return false;
    }
    
    private function uploadAnvisaFile($file)
    {
        $uploadDir = ROOT_PATH . '/public/uploads/anvisa-approvals/';
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            $this->setFlash('error', 'Tipo de arquivo não permitido (apenas PDF, JPEG ou PNG)');
            return false;
        }
        
        if ($file['size'] > $maxSize) {
            $this->setFlash('error', 'Arquivo muito grande (máximo 5MB)');
            return false;
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'anvisa_' . $this->currentClient->id . '_' . time() . '.' . $extension;
        $destination = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return '/uploads/anvisa-approvals/' . $filename;
        }
        
        $this->setFlash('error', 'Erro ao fazer upload do arquivo');
        return false;
    }
    
    private function createOrder($products, $cartItems, $subtotal, $prescriptionId = null)
    {
        // Determinar vendedor (para este exemplo, pega o primeiro produto)
        $firstProduct = reset($products);
        $vendorId = $firstProduct->vendedor_id;
        
        // Criar o pedido
        $order = Order::create([
            'codigo' => 'ORD' . strtoupper(uniqid()),
            'cliente_id' => $this->currentClient->id,
            'vendedor_id' => $vendorId,
            'medico_id' => null, // Será preenchido após aprovação da receita
            'data_pedido' => date('Y-m-d H:i:s'),
            'status' => 'pending',
            'subtotal' => $subtotal,
            'desconto' => 0,
            'total' => $subtotal,
            'metodo_pagamento' => $_POST['payment_method'] ?? 'mercado_pago',
            'endereco_entrega_json' => json_encode([
                'cep' => $this->currentClient->user->endereco_cep,
                'logradouro' => $this->currentClient->user->endereco_logradouro,
                'numero' => $this->currentClient->user->endereco_numero,
                'complemento' => $this->currentClient->user->endereco_complemento,
                'cidade' => $this->currentClient->user->endereco_cidade,
                'estado' => $this->currentClient->user->endereco_estado
            ]),
            'receita_id' => $prescriptionId
        ]);
        
        if (!$order) {
            return false;
        }
        
        // Adicionar itens ao pedido
        foreach ($products as $product) {
            $quantity = $cartItems[$product->id];
            
            OrderItem::create([
                'pedido_id' => $order->id,
                'produto_id' => $product->id,
                'quantidade' => $quantity,
                'preco_unitario' => $product->preco,
                'total_item' => $product->preco * $quantity
            ]);
        }
        
        return $order;
    }
    
    private function processPayment($order)
    {
        $paymentMethod = $_POST['payment_method'] ?? 'mercado_pago';
        
        switch ($paymentMethod) {
            case 'mercado_pago':
                $mpService = new MercadoPagoService();
                return $mpService->createPayment($order, $this->currentClient);
                
            case 'bank_transfer':
                // Criar registro de pagamento pendente
                Payment::create([
                    'pedido_id' => $order->id,
                    'metodo' => 'bank_transfer',
                    'valor' => $order->total,
                    'status' => 'pending',
                    'data_pagamento' => null,
                    'dados_transacao_json' => json_encode([
                        'bank_name' => $_POST['bank_name'] ?? '',
                        'account_number' => $_POST['account_number'] ?? '',
                        'transfer_receipt' => $this->uploadTransferReceipt($_FILES['transfer_receipt'] ?? null)
                    ])
                ]);
                
                return [
                    'status' => true,
                    'redirect' => false,
                    'redirect_url' => '',
                    'message' => ''
                ];
                
            default:
                return [
                    'status' => false,
                    'redirect' => false,
                    'redirect_url' => '',
                    'message' => 'Método de pagamento inválido'
                ];
        }
    }
    
    private function processRefund($order)
    {
        $payment = Payment::getByOrderId($order->id);
        
        if ($payment && $payment->status === 'paid') {
            switch ($payment->metodo) {
                case 'mercado_pago':
                    $mpService = new MercadoPagoService();
                    return $mpService->processRefund($payment);
                    
                case 'bank_transfer':
                    // Marcar para reembolso manual
                    $payment->status = 'refund_pending';
                    $payment->save();
                    return true;
                    
                default:
                    return false;
            }
        }
        
        return false;
    }
    
    private function uploadTransferReceipt($file)
    {
        if (empty($file['name'])) {
            return null;
        }
        
        $uploadDir = ROOT_PATH . '/public/uploads/payments/';
        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            $this->setFlash('error', 'Tipo de arquivo não permitido (apenas JPEG, PNG ou PDF)');
            return null;
        }
        
        if ($file['size'] > $maxSize) {
            $this->setFlash('error', 'Arquivo muito grande (máximo 5MB)');
            return null;
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'comprovante_' . $order->id . '_' . time() . '.' . $extension;
        $destination = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return '/uploads/payments/' . $filename;
        }
        
        return null;
    }
}