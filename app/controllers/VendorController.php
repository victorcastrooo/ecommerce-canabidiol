<?php
namespace App\Controllers;

use App\Lib\Auth;
use App\Lib\Database;
use App\Lib\Validator;
use App\Models\Vendor;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Product;
use App\Models\Order;
use App\Models\Commission;
use App\Models\Withdrawal;
use App\Models\Prescription;
use App\Models\ActivityLog;

class VendorController extends BaseController
{
    private $auth;
    private $db;
    private $validator;
    private $currentVendor;

    public function __construct()
    {
        parent::__construct();
        $this->auth = new Auth();
        $this->db = Database::getInstance();
        $this->validator = new Validator();
        
        // Verificar se o usuário é vendedor
        if (!$this->auth->isVendor()) {
            $this->redirect('/auth/login');
        }

        // Carregar dados do vendedor atual
        $this->currentVendor = Vendor::findByUserId($this->auth->getUserId());
        if (!$this->currentVendor || !$this->currentVendor->aprovado) {
            $this->redirect('/auth/login');
        }
    }

    /**
     * Dashboard do vendedor
     */
    public function dashboard()
    {
        $stats = [
            'total_products' => Product::count(['vendedor_id' => $this->currentVendor->id]),
            'total_orders' => Order::count(['vendedor_id' => $this->currentVendor->id]),
            'pending_orders' => Order::count([
                'vendedor_id' => $this->currentVendor->id,
                'status' => 'pending'
            ]),
            'available_balance' => Commission::getAvailableBalance($this->currentVendor->id),
            'total_withdrawn' => Withdrawal::getTotalWithdrawn($this->currentVendor->id),
            'total_doctors' => Doctor::count([
                'vendedor_id' => $this->currentVendor->id,
                'aprovado' => 1
            ])
        ];

        $recentOrders = Order::getRecentByVendor($this->currentVendor->id, 5);
        $recentCommissions = Commission::getRecentByVendor($this->currentVendor->id, 5);

        $this->render('vendor/dashboard', [
            'stats' => $stats,
            'vendor' => $this->currentVendor,
            'recentOrders' => $recentOrders,
            'recentCommissions' => $recentCommissions
        ]);
    }

    /**
     * Gerenciamento de produtos
     */
    public function products($action = 'index', $id = null)
    {
        switch ($action) {
            case 'index':
                $products = Product::getByVendor($this->currentVendor->id);
                $this->render('vendor/products/index', [
                    'products' => $products,
                    'vendor' => $this->currentVendor
                ]);
                break;
                
            case 'create':
                $this->editProduct();
                break;
                
            case 'edit':
                $this->editProduct($id);
                break;
                
            case 'delete':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && $product = Product::find($id)) {
                    // Verificar se o produto pertence ao vendedor
                    if ($product->vendedor_id == $this->currentVendor->id) {
                        if ($product->delete()) {
                            $this->setFlash('success', 'Produto removido com sucesso');
                        } else {
                            $this->setFlash('error', 'Erro ao remover produto');
                        }
                    }
                }
                $this->redirect('/vendor/products');
                break;
                
            default:
                $this->redirect('/vendor/products');
        }
    }

    private function editProduct($id = null)
    {
        $product = $id ? Product::find($id) : new Product();
        
        // Verificar se o produto pertence ao vendedor (em caso de edição)
        if ($id && $product->vendedor_id != $this->currentVendor->id) {
            $this->redirect('/vendor/products');
        }

        $categories = ProductCategory::getAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $this->validateProductData($_POST);
            $data['vendedor_id'] = $this->currentVendor->id;
            
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
                    $this->redirect('/vendor/products');
                } else {
                    $this->setFlash('error', 'Erro ao salvar produto');
                }
            } else {
                $this->setFlash('error', $this->validator->getErrors());
            }
        }
        
        $this->render('vendor/products/edit', [
            'product' => $product,
            'categories' => $categories,
            'vendor' => $this->currentVendor
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
                $orders = Order::getByVendor($this->currentVendor->id, $status);
                $this->render('vendor/orders/index', [
                    'orders' => $orders,
                    'status' => $status,
                    'vendor' => $this->currentVendor
                ]);
                break;
                
            case 'view':
                if ($order = Order::find($id)) {
                    // Verificar se o pedido pertence ao vendedor
                    if ($order->vendedor_id == $this->currentVendor->id) {
                        $order->items = OrderItem::getByOrderId($order->id);
                        $order->client = Client::find($order->cliente_id);
                        $order->prescription = Prescription::getByOrderId($order->id);
                        
                        $this->render('vendor/orders/view', [
                            'order' => $order,
                            'vendor' => $this->currentVendor
                        ]);
                        return;
                    }
                }
                $this->redirect('/vendor/orders');
                break;
                
            case 'update-status':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && $order = Order::find($id)) {
                    // Verificar se o pedido pertence ao vendedor
                    if ($order->vendedor_id == $this->currentVendor->id) {
                        $status = $_POST['status'] ?? '';
                        
                        if (in_array($status, ['processing', 'shipped', 'completed'])) {
                            $order->status = $status;
                            
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
                            }
                        }
                    }
                }
                $this->redirect('/vendor/orders/view/' . $id);
                break;
                
            default:
                $this->redirect('/vendor/orders');
        }
    }

    /**
     * Gerenciamento de comissões
     */
    public function commissions($action = 'index', $id = null)
    {
        switch ($action) {
            case 'index':
                $status = $_GET['status'] ?? 'available'; // available, pending, paid
                $commissions = Commission::getByVendor($this->currentVendor->id, $status);
                
                $this->render('vendor/commissions/index', [
                    'commissions' => $commissions,
                    'status' => $status,
                    'vendor' => $this->currentVendor,
                    'availableBalance' => Commission::getAvailableBalance($this->currentVendor->id)
                ]);
                break;
                
            case 'withdraw':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $amount = (float)($_POST['amount'] ?? 0);
                    $availableBalance = Commission::getAvailableBalance($this->currentVendor->id);
                    
                    if ($amount <= 0 || $amount > $availableBalance) {
                        $this->setFlash('error', 'Valor inválido para saque');
                        $this->redirect('/vendor/commissions');
                    }
                    
                    // Criar solicitação de saque
                    $withdrawal = new Withdrawal([
                        'vendedor_id' => $this->currentVendor->id,
                        'valor_total' => $amount,
                        'taxa_administrativa' => $this->calculateWithdrawalFee($amount),
                        'valor_liquido' => $amount - $this->calculateWithdrawalFee($amount),
                        'status' => 'pending',
                        'metodo' => 'bank_transfer', // Poderia ser configurável
                        'data_solicitacao' => date('Y-m-d H:i:s')
                    ]);
                    
                    if ($withdrawal->save()) {
                        // Atualizar status das comissões para "pending_withdrawal"
                        $commissions = Commission::getAvailableForWithdrawal($this->currentVendor->id);
                        foreach ($commissions as $commission) {
                            if ($amount <= 0) break;
                            
                            $commission->status = 'pending_withdrawal';
                            $commission->save();
                            $amount -= $commission->valor_comissao;
                            
                            // Vincular comissão ao saque
                            WithdrawalItem::create([
                                'saque_id' => $withdrawal->id,
                                'comissao_id' => $commission->id
                            ]);
                        }
                        
                        $this->setFlash('success', 'Solicitação de saque enviada com sucesso');
                    } else {
                        $this->setFlash('error', 'Erro ao solicitar saque');
                    }
                }
                $this->redirect('/vendor/commissions');
                break;
                
            default:
                $this->redirect('/vendor/commissions');
        }
    }

    /**
     * Gerenciamento de médicos parceiros
     */
    public function doctors($action = 'index', $id = null)
    {
        switch ($action) {
            case 'index':
                $doctors = Doctor::getByVendor($this->currentVendor->id);
                $this->render('vendor/doctors/index', [
                    'doctors' => $doctors,
                    'vendor' => $this->currentVendor
                ]);
                break;
                
            case 'register':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $data = $this->validateDoctorData($_POST);
                    $data['vendedor_id'] = $this->currentVendor->id;
                    
                    if ($this->validator->validate($data, Doctor::$rules)) {
                        $doctor = new Doctor($data);
                        
                        if ($doctor->save()) {
                            $this->setFlash('success', 'Médico cadastrado com sucesso. Aguarde aprovação.');
                            $this->redirect('/vendor/doctors');
                        } else {
                            $this->setFlash('error', 'Erro ao cadastrar médico');
                        }
                    } else {
                        $this->setFlash('error', $this->validator->getErrors());
                    }
                }
                
                $this->render('vendor/doctors/register', [
                    'doctor' => new Doctor(),
                    'vendor' => $this->currentVendor
                ]);
                break;
                
            case 'view':
                if ($doctor = Doctor::find($id)) {
                    // Verificar se o médico pertence ao vendedor
                    if ($doctor->vendedor_id == $this->currentVendor->id) {
                        $this->render('vendor/doctors/view', [
                            'doctor' => $doctor,
                            'vendor' => $this->currentVendor
                        ]);
                        return;
                    }
                }
                $this->redirect('/vendor/doctors');
                break;
                
            default:
                $this->redirect('/vendor/doctors');
        }
    }

    /**
     * Relatórios de vendas
     */
    public function salesReports()
    {
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        
        $salesData = Order::getSalesReport($this->currentVendor->id, $startDate, $endDate);
        $topProducts = Product::getTopSelling($this->currentVendor->id, $startDate, $endDate, 5);
        $commissionsReport = Commission::getReport($this->currentVendor->id, $startDate, $endDate);
        
        $this->render('vendor/sales/report', [
            'salesData' => $salesData,
            'topProducts' => $topProducts,
            'commissionsReport' => $commissionsReport,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'vendor' => $this->currentVendor
        ]);
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
    
    private function validateDoctorData($data)
    {
        return [
            'nome' => trim($data['nome'] ?? ''),
            'crm' => trim($data['crm'] ?? ''),
            'uf_crm' => trim($data['uf_crm'] ?? ''),
            'especialidade' => trim($data['especialidade'] ?? ''),
            'email' => trim($data['email'] ?? ''),
            'telefone' => trim($data['telefone'] ?? '')
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
    
    private function calculateWithdrawalFee($amount)
    {
        // Taxa fixa de R$ 5,00 ou 2% do valor, o que for maior
        return max(5, $amount * 0.02);
    }
}