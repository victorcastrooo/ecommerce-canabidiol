<?php
namespace App\Controllers;

use App\Lib\Auth;
use App\Lib\Database;
use App\Lib\Validator;
use App\Models\Admin;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Client;
use App\Models\Doctor;
use App\Models\Product;
use App\Models\Order;
use App\Models\Commission;
use App\Models\Prescription;
use App\Models\AnvisaApproval;

class AdminController extends BaseController
{
    private $auth;
    private $db;
    private $validator;

    public function __construct()
    {
        parent::__construct();
        $this->auth = new Auth();
        $this->db = Database::getInstance();
        $this->validator = new Validator();
        
        // Verificar se o usuário é admin
        if (!$this->auth->isAdmin()) {
            $this->redirect('/auth/login');
        }
    }

    /**
     * Dashboard administrativo
     */
    public function dashboard()
    {
        $stats = [
            'total_vendors' => Vendor::count(),
            'pending_vendors' => Vendor::count(['aprovado' => 0]),
            'total_clients' => Client::count(),
            'pending_prescriptions' => Prescription::count(['aprovada' => 0]),
            'pending_anvisa' => AnvisaApproval::count(['aprovado' => 0]),
            'total_products' => Product::count(),
            'pending_orders' => Order::count(['status' => 'pending']),
            'total_sales' => Order::sum('total', ['status' => 'completed'])
        ];

        $recentOrders = Order::getRecent(5);
        $recentVendors = Vendor::getRecent(5);

        $this->render('admin/dashboard', [
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'recentVendors' => $recentVendors
        ]);
    }

    /**
     * Gerenciamento de vendedores
     */
    public function vendors($action = 'index', $id = null)
    {
        switch ($action) {
            case 'index':
                $vendors = Vendor::getAll();
                $this->render('admin/vendors/index', ['vendors' => $vendors]);
                break;
                
            case 'approve':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $vendorId = $_POST['vendor_id'] ?? null;
                    $approved = $_POST['approved'] ?? 0;
                    $reason = $_POST['reason'] ?? '';
                    
                    if ($vendorId && $vendor = Vendor::find($vendorId)) {
                        $vendor->aprovado = $approved;
                        $vendor->data_aprovacao = date('Y-m-d H:i:s');
                        $vendor->aprovado_por = $this->auth->getUserId();
                        
                        if (!$approved) {
                            $vendor->motivo_rejeicao = $reason;
                        }
                        
                        if ($vendor->save()) {
                            // Enviar e-mail de notificação
                            $user = User::find($vendor->usuario_id);
                            $this->sendVendorApprovalEmail($user, $approved, $reason);
                            
                            $this->setFlash('success', 'Vendedor ' . ($approved ? 'aprovado' : 'rejeitado') . ' com sucesso');
                        } else {
                            $this->setFlash('error', 'Erro ao atualizar status do vendedor');
                        }
                    }
                    $this->redirect('/admin/vendors');
                }
                break;
                
            case 'view':
                if ($vendor = Vendor::find($id)) {
                    $vendor->user = User::find($vendor->usuario_id);
                    $this->render('admin/vendors/view', ['vendor' => $vendor]);
                } else {
                    $this->redirect('/admin/vendors');
                }
                break;
                
            default:
                $this->redirect('/admin/vendors');
        }
    }

    /**
     * Gerenciamento de produtos
     */
    public function products($action = 'index', $id = null)
    {
        switch ($action) {
            case 'index':
                $products = Product::getAll();
                $this->render('admin/products/index', ['products' => $products]);
                break;
                
            case 'edit':
                $product = $id ? Product::find($id) : new Product();
                $categories = ProductCategory::getAll();
                
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $data = $this->validateProductData($_POST);
                    
                    if ($this->validator->validate($data, Product::$rules)) {
                        $product->fill($data);
                        
                        // Upload de imagem
                        if (!empty($_FILES['imagem_principal']['name'])) {
                            $imagePath = $this->uploadProductImage($_FILES['imagem_principal']);
                            if ($imagePath) {
                                $product->imagem_principal = $imagePath;
                            }
                        }
                        
                        if ($product->save()) {
                            $this->setFlash('success', 'Produto ' . ($id ? 'atualizado' : 'cadastrado') . ' com sucesso');
                            $this->redirect('/admin/products');
                        } else {
                            $this->setFlash('error', 'Erro ao salvar produto');
                        }
                    } else {
                        $this->setFlash('error', $this->validator->getErrors());
                    }
                }
                
                $this->render('admin/products/edit', [
                    'product' => $product,
                    'categories' => $categories
                ]);
                break;
                
            case 'delete':
                if ($product = Product::find($id)) {
                    if ($product->delete()) {
                        $this->setFlash('success', 'Produto removido com sucesso');
                    } else {
                        $this->setFlash('error', 'Erro ao remover produto');
                    }
                }
                $this->redirect('/admin/products');
                break;
                
            default:
                $this->redirect('/admin/products');
        }
    }

    /**
     * Gerenciamento de pedidos
     */
    public function orders($action = 'index', $id = null)
    {
        switch ($action) {
            case 'index':
                $status = $_GET['status'] ?? 'all';
                $orders = Order::getAllByStatus($status);
                $this->render('admin/orders/index', ['orders' => $orders, 'status' => $status]);
                break;
                
            case 'view':
                if ($order = Order::find($id)) {
                    $order->items = OrderItem::getByOrderId($order->id);
                    $order->client = Client::find($order->cliente_id);
                    $order->vendor = Vendor::find($order->vendedor_id);
                    $order->prescription = Prescription::getByOrderId($order->id);
                    
                    $this->render('admin/orders/view', ['order' => $order]);
                } else {
                    $this->redirect('/admin/orders');
                }
                break;
                
            case 'update-status':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && $order = Order::find($id)) {
                    $status = $_POST['status'] ?? '';
                    $reason = $_POST['reason'] ?? '';
                    
                    if (in_array($status, ['pending', 'processing', 'shipped', 'completed', 'cancelled'])) {
                        $order->status = $status;
                        
                        if ($status === 'cancelled') {
                            $order->motivo_cancelamento = $reason;
                        }
                        
                        if ($order->save()) {
                            // Registrar na tabela de logs
                            ActivityLog::create([
                                'usuario_id' => $this->auth->getUserId(),
                                'acao' => 'order_status_update',
                                'tabela_afetada' => 'pedidos',
                                'registro_id' => $order->id,
                                'dados_anteriores' => json_encode(['status' => $order->getOriginal('status')]),
                                'dados_novos' => json_encode(['status' => $status])
                            ]);
                            
                            $this->setFlash('success', 'Status do pedido atualizado com sucesso');
                        } else {
                            $this->setFlash('error', 'Erro ao atualizar status do pedido');
                        }
                    }
                }
                $this->redirect('/admin/orders/view/' . $id);
                break;
                
            default:
                $this->redirect('/admin/orders');
        }
    }

    /**
     * Gerenciamento de médicos parceiros
     */
    public function doctors($action = 'index', $id = null)
    {
        switch ($action) {
            case 'index':
                $doctors = Doctor::getAll();
                $this->render('admin/doctors/index', ['doctors' => $doctors]);
                break;
                
            case 'approve':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $doctorId = $_POST['doctor_id'] ?? null;
                    $approved = $_POST['approved'] ?? 0;
                    $reason = $_POST['reason'] ?? '';
                    
                    if ($doctorId && $doctor = Doctor::find($doctorId)) {
                        $doctor->aprovado = $approved;
                        $doctor->data_aprovacao = date('Y-m-d H:i:s');
                        $doctor->aprovado_por = $this->auth->getUserId();
                        
                        if (!$approved) {
                            $doctor->motivo_rejeicao = $reason;
                        }
                        
                        if ($doctor->save()) {
                            $this->setFlash('success', 'Médico ' . ($approved ? 'aprovado' : 'rejeitado') . ' com sucesso');
                        } else {
                            $this->setFlash('error', 'Erro ao atualizar status do médico');
                        }
                    }
                    $this->redirect('/admin/doctors');
                }
                break;
                
            case 'view':
                if ($doctor = Doctor::find($id)) {
                    $vendor = Vendor::find($doctor->vendedor_id);
                    $this->render('admin/doctors/view', ['doctor' => $doctor, 'vendor' => $vendor]);
                } else {
                    $this->redirect('/admin/doctors');
                }
                break;
                
            default:
                $this->redirect('/admin/doctors');
        }
    }

    /**
     * Gerenciamento de liberações ANVISA
     */
    public function anvisaApprovals($action = 'index', $id = null)
    {
        switch ($action) {
            case 'index':
                $approvals = AnvisaApproval::getAll();
                $this->render('admin/anvisa/index', ['approvals' => $approvals]);
                break;
                
            case 'approve':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $approvalId = $_POST['approval_id'] ?? null;
                    $approved = $_POST['approved'] ?? 0;
                    $reason = $_POST['reason'] ?? '';
                    
                    if ($approvalId && $approval = AnvisaApproval::find($approvalId)) {
                        $approval->aprovado = $approved;
                        $approval->data_aprovacao = date('Y-m-d H:i:s');
                        $approval->aprovado_por = $this->auth->getUserId();
                        
                        if (!$approved) {
                            $approval->motivo_rejeicao = $reason;
                        }
                        
                        if ($approval->save()) {
                            $client = Client::find($approval->cliente_id);
                            $user = User::find($client->usuario_id);
                            $this->sendAnvisaApprovalEmail($user, $approved, $reason);
                            
                            $this->setFlash('success', 'Liberação ANVISA ' . ($approved ? 'aprovada' : 'rejeitada') . ' com sucesso');
                        } else {
                            $this->setFlash('error', 'Erro ao atualizar status da liberação');
                        }
                    }
                    $this->redirect('/admin/anvisa');
                }
                break;
                
            case 'view':
                if ($approval = AnvisaApproval::find($id)) {
                    $client = Client::find($approval->cliente_id);
                    $approval->client = $client;
                    $approval->user = User::find($client->usuario_id);
                    
                    $this->render('admin/anvisa/view', ['approval' => $approval]);
                } else {
                    $this->redirect('/admin/anvisa');
                }
                break;
                
            default:
                $this->redirect('/admin/anvisa');
        }
    }

    /**
     * Métodos auxiliares privados
     */
    
    private function validateProductData($data)
    {
        return [
            'categoria_id' => $data['categoria_id'] ?? null,
            'nome' => trim($data['nome'] ?? ''),
            'descricao' => trim($data['descricao'] ?? ''),
            'principio_ativo' => trim($data['principio_ativo'] ?? ''),
            'concentracao' => trim($data['concentracao'] ?? ''),
            'forma_farmaceutica' => trim($data['forma_farmaceutica'] ?? ''),
            'laboratorio' => trim($data['laboratorio'] ?? ''),
            'codigo_barras' => trim($data['codigo_barras'] ?? ''),
            'preco' => (float)($data['preco'] ?? 0),
            'peso_gramas' => (float)($data['peso_gramas'] ?? 0),
            'largura_cm' => (float)($data['largura_cm'] ?? 0),
            'altura_cm' => (float)($data['altura_cm'] ?? 0),
            'profundidade_cm' => (float)($data['profundidade_cm'] ?? 0),
            'ativo' => isset($data['ativo']) ? 1 : 0
        ];
    }
    
    private function uploadProductImage($file)
    {
        $uploadDir = ROOT_PATH . '/public/uploads/products/';
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            $this->setFlash('error', 'Tipo de arquivo não permitido');
            return false;
        }
        
        if ($file['size'] > $maxSize) {
            $this->setFlash('error', 'Arquivo muito grande (máximo 5MB)');
            return false;
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'prod_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $destination = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return '/uploads/products/' . $filename;
        }
        
        $this->setFlash('error', 'Erro ao fazer upload da imagem');
        return false;
    }
    
    private function sendVendorApprovalEmail($user, $approved, $reason = '')
    {
        $subject = $approved ? 'Seu cadastro como vendedor foi aprovado' : 'Seu cadastro como vendedor foi reprovado';
        
        $message = $approved 
            ? "Parabéns! Seu cadastro como vendedor em nossa plataforma foi aprovado."
            : "Seu cadastro como vendedor foi reprovado. Motivo: " . $reason;
            
        // Implementar lógica de envio de e-mail aqui
        // Pode usar a classe Mailer ou um serviço externo
    }
    
    private function sendAnvisaApprovalEmail($user, $approved, $reason = '')
    {
        $subject = $approved ? 'Sua liberação ANVISA foi aprovada' : 'Sua liberação ANVISA foi reprovada';
        
        $message = $approved 
            ? "Sua documentação para liberação ANVISA foi aprovada. Você já pode realizar compras em nossa plataforma."
            : "Sua documentação para liberação ANVISA foi reprovada. Motivo: " . $reason;
            
        // Implementar lógica de envio de e-mail aqui
    }
}