<?php
namespace App\Controllers;

use App\Lib\Auth;
use App\Lib\Validator;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Client;
use App\Models\Vendor;
use App\Models\Product;
use App\Models\Prescription;
use App\Models\AnvisaApproval;
use App\Models\Payment;
use App\Models\Commission;
use App\Models\ActivityLog;
use App\Services\MercadoPagoService;
use App\Services\TrixExpressService;

class OrderController extends BaseController
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
     * Listagem de pedidos (admin/vendor/client)
     */
    public function index()
    {
        if ($this->auth->isAdmin()) {
            $this->adminIndex();
        } elseif ($this->auth->isVendor()) {
            $this->vendorIndex();
        } elseif ($this->auth->isClient()) {
            $this->clientIndex();
        } else {
            $this->redirect('/auth/login');
        }
    }

    /**
     * Listagem para administradores
     */
    private function adminIndex()
    {
        $status = $_GET['status'] ?? 'all';
        $search = $_GET['search'] ?? '';
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = 15;

        $orders = Order::getAllOrders($status, $search, $page, $perPage);
        $totalOrders = Order::countAll($status, $search);

        $this->render('admin/orders/index', [
            'orders' => $orders,
            'status' => $status,
            'search' => $search,
            'pagination' => [
                'total' => $totalOrders,
                'perPage' => $perPage,
                'currentPage' => $page,
                'totalPages' => ceil($totalOrders / $perPage)
            ]
        ]);
    }

    /**
     * Listagem para vendedores
     */
    private function vendorIndex()
    {
        $vendor = Vendor::findByUserId($this->currentUser->id);
        if (!$vendor || !$vendor->aprovado) {
            $this->redirect('/vendor/dashboard');
        }

        $status = $_GET['status'] ?? 'all';
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = 15;

        $orders = Order::getVendorOrders($vendor->id, $status, $page, $perPage);
        $totalOrders = Order::countVendor($vendor->id, $status);

        $this->render('vendor/orders/index', [
            'orders' => $orders,
            'vendor' => $vendor,
            'status' => $status,
            'pagination' => [
                'total' => $totalOrders,
                'perPage' => $perPage,
                'currentPage' => $page,
                'totalPages' => ceil($totalOrders / $perPage)
            ]
        ]);
    }

    /**
     * Listagem para clientes
     */
    private function clientIndex()
    {
        $client = Client::findByUserId($this->currentUser->id);
        if (!$client) {
            $this->redirect('/client/dashboard');
        }

        $status = $_GET['status'] ?? 'all';
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = 10;

        $orders = Order::getClientOrders($client->id, $status, $page, $perPage);
        $totalOrders = Order::countClient($client->id, $status);

        $this->render('client/orders/index', [
            'orders' => $orders,
            'client' => $client,
            'status' => $status,
            'pagination' => [
                'total' => $totalOrders,
                'perPage' => $perPage,
                'currentPage' => $page,
                'totalPages' => ceil($totalOrders / $perPage)
            ]
        ]);
    }

    /**
     * Visualização de pedido
     */
    public function show($id)
    {
        $order = Order::find($id);
        if (!$order) {
            $this->render('errors/404');
            return;
        }

        // Verificar permissões
        if ($this->auth->isClient()) {
            $client = Client::findByUserId($this->currentUser->id);
            if ($order->cliente_id != $client->id) {
                $this->redirect('/client/orders');
            }
            $template = 'client/orders/show';
        } elseif ($this->auth->isVendor()) {
            $vendor = Vendor::findByUserId($this->currentUser->id);
            if ($order->vendedor_id != $vendor->id) {
                $this->redirect('/vendor/orders');
            }
            $template = 'vendor/orders/show';
        } elseif ($this->auth->isAdmin()) {
            $template = 'admin/orders/show';
        } else {
            $this->redirect('/auth/login');
        }

        // Carregar dados relacionados
        $order->items = OrderItem::getByOrderId($order->id);
        $order->client = Client::find($order->cliente_id);
        $order->vendor = Vendor::find($order->vendedor_id);
        $order->prescription = Prescription::find($order->receita_id);
        $order->payment = Payment::getByOrderId($order->id);
        $order->shipping_data = $this->getShippingData($order);

        $this->render($template, [
            'order' => $order,
            'canCancel' => $this->canCancelOrder($order),
            'canProcess' => $this->canProcessOrder($order)
        ]);
    }

    /**
     * Atualização de status do pedido
     */
    public function updateStatus($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectBack();
        }

        $order = Order::find($id);
        if (!$order) {
            $this->redirectBack();
        }

        // Verificar permissões
        $isAdmin = $this->auth->isAdmin();
        $isVendor = $this->auth->isVendor();
        $isClient = $this->auth->isClient();

        if ($isVendor) {
            $vendor = Vendor::findByUserId($this->currentUser->id);
            if ($order->vendedor_id != $vendor->id) {
                $this->redirect('/vendor/orders');
            }
        } elseif ($isClient) {
            $client = Client::findByUserId($this->currentUser->id);
            if ($order->cliente_id != $client->id) {
                $this->redirect('/client/orders');
            }
        } elseif (!$isAdmin) {
            $this->redirect('/auth/login');
        }

        $newStatus = $_POST['status'] ?? '';
        $reason = $_POST['reason'] ?? '';

        // Validar transição de status
        if (!$this->isValidStatusTransition($order->status, $newStatus, $isAdmin, $isVendor, $isClient)) {
            $this->setFlash('error', 'Transição de status inválida');
            $this->redirectBack();
        }

        // Atualizar status
        $previousStatus = $order->status;
        $order->status = $newStatus;

        // Campos adicionais para cancelamento
        if ($newStatus === 'cancelled') {
            $order->motivo_cancelamento = $reason;
            $order->data_cancelamento = date('Y-m-d H:i:s');
            $order->cancelado_por = $this->currentUser->id;

            // Processar reembolso se o pagamento foi realizado
            if ($order->payment && $order->payment->status === 'paid') {
                $this->processRefund($order);
            }
        }

        // Campos adicionais para aprovação
        if ($newStatus === 'processing' && $isAdmin) {
            $order->data_aprovacao = date('Y-m-d H:i:s');
            $order->aprovado_por = $this->currentUser->id;
        }

        // Campos adicionais para envio
        if ($newStatus === 'shipped') {
            $trackingCode = $_POST['tracking_code'] ?? '';
            $shippingCompany = $_POST['shipping_company'] ?? 'TrixExpress';

            if (empty($trackingCode)) {
                $this->setFlash('error', 'Código de rastreamento é obrigatório');
                $this->redirectBack();
            }

            $order->tracking_code = $trackingCode;
            $order->transportadora = $shippingCompany;
            $order->data_envio = date('Y-m-d H:i:s');

            // Registrar comissões para o vendedor
            $this->registerCommissions($order);
        }

        if ($order->save()) {
            // Registrar atividade
            ActivityLog::create([
                'usuario_id' => $this->currentUser->id,
                'acao' => 'order_status_changed',
                'tabela_afetada' => 'pedidos',
                'registro_id' => $order->id,
                'dados_anteriores' => json_encode(['status' => $previousStatus]),
                'dados_novos' => json_encode(['status' => $newStatus, 'reason' => $reason])
            ]);

            // Enviar notificação por e-mail
            $this->sendStatusEmail($order, $previousStatus);

            $this->setFlash('success', 'Status do pedido atualizado com sucesso');
        } else {
            $this->setFlash('error', 'Erro ao atualizar status do pedido');
        }

        $this->redirectBack();
    }

    /**
     * Processar reembolso
     */
    private function processRefund($order)
    {
        $payment = Payment::getByOrderId($order->id);
        if (!$payment || $payment->status !== 'paid') {
            return false;
        }

        switch ($payment->metodo) {
            case 'mercado_pago':
                $mpService = new MercadoPagoService();
                return $mpService->processRefund($payment);
                
            case 'bank_transfer':
                // Marcar para reembolso manual
                $payment->status = 'refund_pending';
                return $payment->save();
                
            default:
                return false;
        }
    }

    /**
     * Registrar comissões para o vendedor
     */
    private function registerCommissions($order)
    {
        // Verificar se já existem comissões para este pedido
        if (Commission::count(['pedido_id' => $order->id]) > 0) {
            return false;
        }

        $vendor = Vendor::find($order->vendedor_id);
        if (!$vendor) {
            return false;
        }

        $commissionPercent = $vendor->comissao_percentual ?? 10; // Percentual padrão de 10%
        $items = OrderItem::getByOrderId($order->id);

        foreach ($items as $item) {
            $commissionValue = ($item->total_item * $commissionPercent) / 100;

            Commission::create([
                'vendedor_id' => $vendor->id,
                'pedido_id' => $order->id,
                'medico_id' => $order->medico_id,
                'valor_comissao' => $commissionValue,
                'percentual_comissao' => $commissionPercent,
                'status' => 'pending', // Pendente até o período de liberação
                'data_criacao' => date('Y-m-d H:i:s'),
                'data_disponibilidade' => date('Y-m-d H:i:s', strtotime('+30 days')) // Disponível em 30 dias
            ]);
        }

        return true;
    }

    /**
     * Verificar se um pedido pode ser cancelado
     */
    private function canCancelOrder($order)
    {
        // Clientes só podem cancelar pedidos pendentes
        if ($this->auth->isClient()) {
            return $order->status === 'pending';
        }

        // Vendedores podem cancelar pedidos pendentes ou em processamento
        if ($this->auth->isVendor()) {
            return in_array($order->status, ['pending', 'processing']);
        }

        // Admins podem cancelar qualquer pedido não finalizado
        if ($this->auth->isAdmin()) {
            return !in_array($order->status, ['completed', 'cancelled', 'shipped']);
        }

        return false;
    }

    /**
     * Verificar se um pedido pode ser processado
     */
    private function canProcessOrder($order)
    {
        // Apenas admins e vendedores podem processar pedidos
        if (!$this->auth->isAdmin() && !$this->auth->isVendor()) {
            return false;
        }

        // Verificar se há receita médica pendente para produtos que exigem
        if ($order->receita_id) {
            $prescription = Prescription::find($order->receita_id);
            if (!$prescription || !$prescription->aprovada) {
                return false;
            }
        }

        return $order->status === 'pending';
    }

    /**
     * Verificar se a transição de status é válida
     */
    private function isValidStatusTransition($currentStatus, $newStatus, $isAdmin, $isVendor, $isClient)
    {
        $validTransitions = [
            'pending' => ['processing', 'cancelled'],
            'processing' => ['shipped', 'cancelled'],
            'shipped' => ['completed'],
            'completed' => [],
            'cancelled' => []
        ];

        // Clientes só podem cancelar
        if ($isClient) {
            return $newStatus === 'cancelled' && in_array($newStatus, $validTransitions[$currentStatus]);
        }

        // Vendedores podem processar e cancelar
        if ($isVendor) {
            return in_array($newStatus, ['processing', 'cancelled']) && 
                   in_array($newStatus, $validTransitions[$currentStatus]);
        }

        // Admins podem fazer qualquer transição válida
        if ($isAdmin) {
            return in_array($newStatus, $validTransitions[$currentStatus]);
        }

        return false;
    }

    /**
     * Obter dados de envio
     */
    private function getShippingData($order)
    {
        if (!$order->tracking_code) {
            return null;
        }

        $shippingService = new TrixExpressService();
        return $shippingService->getTrackingInfo($order->tracking_code);
    }

    /**
     * Enviar e-mail de notificação de status
     */
    private function sendStatusEmail($order, $previousStatus)
    {
        $client = Client::find($order->cliente_id);
        $user = User::find($client->usuario_id);

        $subject = "Status do seu pedido #{$order->codigo} foi atualizado";
        $template = 'emails/order_status_changed';

        $data = [
            'order' => $order,
            'client' => $client,
            'previousStatus' => $previousStatus,
            'newStatus' => $order->status
        ];

        $this->mailer->send(
            $user->email,
            $subject,
            $this->renderEmailTemplate($template, $data)
        );
    }

    /**
     * Renderizar template de e-mail
     */
    private function renderEmailTemplate($template, $data = [])
    {
        ob_start();
        extract($data);
        require APP_PATH . '/views/' . $template . '.php';
        return ob_get_clean();
    }
}