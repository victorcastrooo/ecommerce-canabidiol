<?php
namespace App\Controllers;

use App\Lib\Auth;
use App\Lib\Validator;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductStock;
use App\Models\ProductMovement;
use App\Models\Vendor;
use App\Models\ActivityLog;

class ProductController extends BaseController
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
     * Listagem de produtos (pública)
     */
    public function index()
    {
        $search = $_GET['search'] ?? '';
        $category = $_GET['category'] ?? '';
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = 12;

        $products = Product::getActiveProducts($search, $category, $page, $perPage);
        $categories = ProductCategory::getActiveCategories();
        $totalProducts = Product::countActive($search, $category);

        $this->render('products/index', [
            'products' => $products,
            'categories' => $categories,
            'search' => $search,
            'selectedCategory' => $category,
            'pagination' => [
                'total' => $totalProducts,
                'perPage' => $perPage,
                'currentPage' => $page,
                'totalPages' => ceil($totalProducts / $perPage)
            ]
        ]);
    }

    /**
     * Detalhes do produto (público)
     */
    public function show($id)
    {
        $product = Product::findActive($id);
        
        if (!$product) {
            $this->render('errors/404');
            return;
        }

        // Verificar se precisa de receita médica
        $requiresPrescription = $product->requer_receita;

        // Verificar se o usuário está logado e tem aprovação ANVISA (se necessário)
        $canPurchase = true;
        $anvisaApproved = false;
        
        if ($this->auth->isLoggedIn()) {
            if ($this->auth->isClient()) {
                $anvisaApproved = AnvisaApproval::isApproved($this->auth->getCurrentClient()->id);
                $canPurchase = !$requiresPrescription || $anvisaApproved;
            } else {
                $canPurchase = false; // Apenas clientes podem comprar
            }
        }

        // Produtos relacionados (mesma categoria)
        $relatedProducts = Product::getRelatedProducts($product->id, $product->categoria_id, 4);

        $this->render('products/show', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
            'requiresPrescription' => $requiresPrescription,
            'canPurchase' => $canPurchase,
            'anvisaApproved' => $anvisaApproved,
            'inStock' => ProductStock::getStock($product->id) > 0
        ]);
    }

    /**
     * Gerenciamento de produtos (admin/vendedor)
     */
    public function manage($action = 'index', $id = null)
    {
        // Verificar permissões
        if ($this->auth->isAdmin()) {
            $this->manageAsAdmin($action, $id);
        } elseif ($this->auth->isVendor()) {
            $this->manageAsVendor($action, $id);
        } else {
            $this->redirect('/auth/login');
        }
    }

    /**
     * Gerenciamento como administrador
     */
    private function manageAsAdmin($action, $id)
    {
        switch ($action) {
            case 'index':
                $search = $_GET['search'] ?? '';
                $status = $_GET['status'] ?? 'active'; // active, inactive
                $page = max(1, intval($_GET['page'] ?? 1));
                $perPage = 15;

                $products = Product::getAllProducts($search, $status, $page, $perPage);
                $totalProducts = Product::countAll($search, $status);

                $this->render('admin/products/index', [
                    'products' => $products,
                    'search' => $search,
                    'status' => $status,
                    'pagination' => [
                        'total' => $totalProducts,
                        'perPage' => $perPage,
                        'currentPage' => $page,
                        'totalPages' => ceil($totalProducts / $perPage)
                    ]
                ]);
                break;

            case 'create':
                $this->editProductForm();
                break;

            case 'edit':
                $this->editProductForm($id);
                break;

            case 'update':
                $this->updateProduct($id);
                break;

            case 'toggle-status':
                $this->toggleProductStatus($id);
                break;

            case 'stock':
                $this->manageStock($id);
                break;

            default:
                $this->redirect('/admin/products');
        }
    }

    /**
     * Gerenciamento como vendedor
     */
    private function manageAsVendor($action, $id)
    {
        $vendor = Vendor::findByUserId($this->currentUser->id);
        
        if (!$vendor || !$vendor->aprovado) {
            $this->redirect('/vendor/dashboard');
        }

        switch ($action) {
            case 'index':
                $search = $_GET['search'] ?? '';
                $status = $_GET['status'] ?? 'active'; // active, inactive
                $page = max(1, intval($_GET['page'] ?? 1));
                $perPage = 15;

                $products = Product::getVendorProducts($vendor->id, $search, $status, $page, $perPage);
                $totalProducts = Product::countVendor($vendor->id, $search, $status);

                $this->render('vendor/products/index', [
                    'products' => $products,
                    'vendor' => $vendor,
                    'search' => $search,
                    'status' => $status,
                    'pagination' => [
                        'total' => $totalProducts,
                        'perPage' => $perPage,
                        'currentPage' => $page,
                        'totalPages' => ceil($totalProducts / $perPage)
                    ]
                ]);
                break;

            case 'create':
                $this->editProductForm(null, $vendor);
                break;

            case 'edit':
                $this->editProductForm($id, $vendor);
                break;

            case 'update':
                $this->updateProduct($id, $vendor);
                break;

            case 'toggle-status':
                $this->toggleProductStatus($id, $vendor);
                break;

            case 'stock':
                $this->manageStock($id, $vendor);
                break;

            default:
                $this->redirect('/vendor/products');
        }
    }

    /**
     * Formulário de edição/criação de produto
     */
    private function editProductForm($id = null, $vendor = null)
    {
        $product = $id ? Product::find($id) : new Product();
        
        // Verificar se o produto pertence ao vendedor (se aplicável)
        if ($id && $vendor && $product->vendedor_id != $vendor->id) {
            $this->redirect($vendor ? '/vendor/products' : '/admin/products');
        }

        $categories = ProductCategory::getActiveCategories();

        $this->render($vendor ? 'vendor/products/edit' : 'admin/products/edit', [
            'product' => $product,
            'categories' => $categories,
            'vendor' => $vendor,
            'stock' => $id ? ProductStock::getStock($id) : 0
        ]);
    }

    /**
     * Atualização de produto
     */
    private function updateProduct($id = null, $vendor = null)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect($vendor ? '/vendor/products' : '/admin/products');
        }

        $product = $id ? Product::find($id) : new Product();
        
        // Verificar permissões
        if ($id && $vendor && $product->vendedor_id != $vendor->id) {
            $this->redirect('/vendor/products');
        }

        $data = $this->validateProductData($_POST);
        
        // Definir vendedor se for um vendedor
        if ($vendor) {
            $data['vendedor_id'] = $vendor->id;
        }

        if ($this->validator->validate($data, Product::$rules)) {
            // Upload de imagem
            if (!empty($_FILES['imagem_principal']['name'])) {
                $imagePath = $this->uploadProductImage($_FILES['imagem_principal']);
                if ($imagePath) {
                    $data['imagem_principal'] = $imagePath;
                }
            }

            // Definir quem está cadastrando/atualizando
            if (!$id) {
                $data['cadastrado_por'] = $this->currentUser->id;
                $data['data_cadastro'] = date('Y-m-d H:i:s');
            }

            $product->fill($data);

            if ($product->save()) {
                // Se for um novo produto, criar registro de estoque
                if (!$id) {
                    ProductStock::create([
                        'produto_id' => $product->id,
                        'quantidade' => $_POST['initial_stock'] ?? 0,
                        'quantidade_minima' => $_POST['min_stock'] ?? 5
                    ]);
                }

                // Registrar atividade
                ActivityLog::create([
                    'usuario_id' => $this->currentUser->id,
                    'acao' => $id ? 'product_updated' : 'product_created',
                    'tabela_afetada' => 'produtos',
                    'registro_id' => $product->id,
                    'dados_novos' => json_encode($product->toArray())
                ]);

                $this->setFlash('success', 'Produto ' . ($id ? 'atualizado' : 'cadastrado') . ' com sucesso!');
            } else {
                $this->setFlash('error', 'Erro ao salvar produto');
            }
        } else {
            $this->setFlash('error', $this->validator->getErrors());
        }

        $this->redirect(($vendor ? '/vendor/products/edit/' : '/admin/products/edit/') . ($id ?: $product->id));
    }

    /**
     * Alternar status do produto (ativo/inativo)
     */
    private function toggleProductStatus($id, $vendor = null)
    {
        if ($product = Product::find($id)) {
            // Verificar permissões
            if ($vendor && $product->vendedor_id != $vendor->id) {
                $this->redirect('/vendor/products');
            }

            $previousStatus = $product->ativo;
            $product->ativo = $product->ativo ? 0 : 1;
            
            if ($product->save()) {
                // Registrar atividade
                ActivityLog::create([
                    'usuario_id' => $this->currentUser->id,
                    'acao' => 'product_status_changed',
                    'tabela_afetada' => 'produtos',
                    'registro_id' => $product->id,
                    'dados_anteriores' => json_encode(['ativo' => $previousStatus]),
                    'dados_novos' => json_encode(['ativo' => $product->ativo])
                ]);

                $this->setFlash('success', 'Status do produto atualizado');
            }
        }

        $this->redirect($vendor ? '/vendor/products' : '/admin/products');
    }

    /**
     * Gerenciamento de estoque
     */
    private function manageStock($productId, $vendor = null)
    {
        $product = Product::find($productId);
        
        // Verificar permissões
        if (!$product || ($vendor && $product->vendedor_id != $vendor->id)) {
            $this->redirect($vendor ? '/vendor/products' : '/admin/products');
        }

        $stock = ProductStock::firstOrCreate(['produto_id' => $productId]);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? 'update'; // update, add, remove
            $quantity = intval($_POST['quantity'] ?? 0);
            $observation = trim($_POST['observation'] ?? '');

            if ($quantity <= 0) {
                $this->setFlash('error', 'Quantidade inválida');
                $this->redirectBack();
            }

            $previousQuantity = $stock->quantidade;

            switch ($action) {
                case 'add':
                    $stock->quantidade += $quantity;
                    $movementType = 'entrada';
                    break;
                
                case 'remove':
                    if ($quantity > $stock->quantidade) {
                        $this->setFlash('error', 'Quantidade indisponível em estoque');
                        $this->redirectBack();
                    }
                    $stock->quantidade -= $quantity;
                    $movementType = 'saida';
                    break;
                
                default: // update
                    $stock->quantidade = $quantity;
                    $movementType = 'ajuste';
            }

            // Atualizar quantidade mínima se fornecida
            if (isset($_POST['min_stock'])) {
                $stock->quantidade_minima = max(0, intval($_POST['min_stock']));
            }

            if ($stock->save()) {
                // Registrar movimentação
                ProductMovement::create([
                    'produto_id' => $productId,
                    'tipo' => $movementType,
                    'quantidade' => $quantity,
                    'observacao' => $observation ?: ($movementType === 'entrada' ? 'Entrada de estoque' : 'Saída de estoque'),
                    'usuario_id' => $this->currentUser->id,
                    'data_movimentacao' => date('Y-m-d H:i:s')
                ]);

                // Registrar atividade
                ActivityLog::create([
                    'usuario_id' => $this->currentUser->id,
                    'acao' => 'stock_updated',
                    'tabela_afetada' => 'estoque',
                    'registro_id' => $stock->id,
                    'dados_anteriores' => json_encode(['quantidade' => $previousQuantity]),
                    'dados_novos' => json_encode(['quantidade' => $stock->quantidade])
                ]);

                $this->setFlash('success', 'Estoque atualizado com sucesso');
            } else {
                $this->setFlash('error', 'Erro ao atualizar estoque');
            }
        }

        $movements = ProductMovement::getByProduct($productId, 10);

        $this->render($vendor ? 'vendor/products/stock' : 'admin/products/stock', [
            'product' => $product,
            'stock' => $stock,
            'movements' => $movements,
            'vendor' => $vendor
        ]);
    }

    /**
     * Validação dos dados do produto
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
            'preco' => floatval($data['preco'] ?? 0),
            'peso_gramas' => floatval($data['peso_gramas'] ?? 0),
            'largura_cm' => floatval($data['largura_cm'] ?? 0),
            'altura_cm' => floatval($data['altura_cm'] ?? 0),
            'profundidade_cm' => floatval($data['profundidade_cm'] ?? 0),
            'requer_receita' => isset($data['requer_receita']) ? 1 : 0,
            'ativo' => isset($data['ativo']) ? 1 : 0
        ];
    }

    /**
     * Upload de imagem do produto
     */
    private function uploadProductImage($file)
    {
        $uploadDir = ROOT_PATH . '/public/uploads/products/';
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            $this->setFlash('error', 'Tipo de arquivo não permitido (apenas JPG, PNG ou WebP)');
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
}