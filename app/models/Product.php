<?php
namespace App\Models;

use PDO;
use App\Lib\Database;
use App\Lib\Exceptions\ProductException;

class Product {
    // Product properties
    protected $id;
    protected $categoria_id;
    protected $nome;
    protected $descricao;
    protected $principio_ativo;
    protected $concentracao;
    protected $forma_farmaceutica;
    protected $laboratorio;
    protected $codigo_barras;
    protected $preco;
    protected $peso_gramas;
    protected $largura_cm;
    protected $altura_cm;
    protected $profundidade_cm;
    protected $ativo;
    protected $data_cadastro;
    protected $cadastrado_por;
    protected $imagem_principal;
    
    protected $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /*****************************************************************
     * CRUD Methods
     *****************************************************************/

    /**
     * Create a new product
     */
    public function create(array $data): bool {
        $this->validateProductData($data);

        $stmt = $this->db->prepare("
            INSERT INTO produtos (
                categoria_id, nome, descricao, principio_ativo, concentracao,
                forma_farmaceutica, laboratorio, codigo_barras, preco,
                peso_gramas, largura_cm, altura_cm, profundidade_cm,
                ativo, cadastrado_por, imagem_principal
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $result = $stmt->execute([
            $data['categoria_id'] ?? null,
            $data['nome'],
            $data['descricao'] ?? null,
            $data['principio_ativo'],
            $data['concentracao'],
            $data['forma_farmaceutica'],
            $data['laboratorio'],
            $data['codigo_barras'] ?? $this->generateBarcode(),
            $data['preco'],
            $data['peso_gramas'] ?? null,
            $data['largura_cm'] ?? null,
            $data['altura_cm'] ?? null,
            $data['profundidade_cm'] ?? null,
            $data['ativo'] ?? true,
            $data['cadastrado_por'],
            $data['imagem_principal'] ?? null
        ]);

        if ($result) {
            $this->id = $this->db->lastInsertId();
            $this->initializeStock();
            return true;
        }

        return false;
    }

    /**
     * Update product information
     */
    public function update(array $data): bool {
        $this->validateProductData($data, false);

        $stmt = $this->db->prepare("
            UPDATE produtos SET 
                categoria_id = ?,
                nome = ?,
                descricao = ?,
                principio_ativo = ?,
                concentracao = ?,
                forma_farmaceutica = ?,
                laboratorio = ?,
                codigo_barras = ?,
                preco = ?,
                peso_gramas = ?,
                largura_cm = ?,
                altura_cm = ?,
                profundidade_cm = ?,
                ativo = ?,
                imagem_principal = ?
            WHERE id = ?
        ");

        return $stmt->execute([
            $data['categoria_id'] ?? null,
            $data['nome'],
            $data['descricao'] ?? null,
            $data['principio_ativo'],
            $data['concentracao'],
            $data['forma_farmaceutica'],
            $data['laboratorio'],
            $data['codigo_barras'] ?? $this->codigo_barras,
            $data['preco'],
            $data['peso_gramas'] ?? null,
            $data['largura_cm'] ?? null,
            $data['altura_cm'] ?? null,
            $data['profundidade_cm'] ?? null,
            $data['ativo'] ?? $this->ativo,
            $data['imagem_principal'] ?? $this->imagem_principal,
            $this->id
        ]);
    }

    /**
     * Soft delete product (set as inactive)
     */
    public function deactivate(): bool {
        if ($this->getStockQuantity() > 0) {
            throw new ProductException('Não é possível desativar produto com estoque disponível');
        }

        $stmt = $this->db->prepare("
            UPDATE produtos SET 
                ativo = FALSE
            WHERE id = ?
        ");

        return $stmt->execute([$this->id]);
    }

    /*****************************************************************
     * Stock Management Methods
     *****************************************************************/

    /**
     * Initialize stock for new product
     */
    protected function initializeStock(): bool {
        $stmt = $this->db->prepare("
            INSERT INTO estoque (
                produto_id, quantidade, quantidade_minima
            ) VALUES (?, 0, 5)
        ");
        return $stmt->execute([$this->id]);
    }

    /**
     * Get current stock quantity
     */
    public function getStockQuantity(): int {
        $stmt = $this->db->prepare("
            SELECT quantidade FROM estoque 
            WHERE produto_id = ?
        ");
        $stmt->execute([$this->id]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get stock information
     */
    public function getStockInfo(): array {
        $stmt = $this->db->prepare("
            SELECT * FROM estoque 
            WHERE produto_id = ?
        ");
        $stmt->execute([$this->id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update stock quantity
     */
    public function updateStock(int $quantity, string $operation = 'adjust', string $observation = ''): bool {
        $this->db->beginTransaction();

        try {
            // Update stock
            $stmt = $this->db->prepare("
                UPDATE estoque SET 
                    quantidade = ?
                WHERE produto_id = ?
            ");
            $stmt->execute([$quantity, $this->id]);

            // Record movement
            $stmt = $this->db->prepare("
                INSERT INTO movimentacao_estoque (
                    produto_id, tipo, quantidade, observacao, usuario_id
                ) VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $this->id,
                $operation,
                $quantity,
                $observation,
                $_SESSION['user_id'] ?? null
            ]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw new ProductException('Erro ao atualizar estoque: ' . $e->getMessage());
        }
    }

    /**
     * Add stock quantity
     */
    public function addStock(int $quantity, string $observation = ''): bool {
        $current = $this->getStockQuantity();
        return $this->updateStock($current + $quantity, 'entrada', $observation);
    }

    /**
     * Remove stock quantity
     */
    public function removeStock(int $quantity, string $observation = ''): bool {
        $current = $this->getStockQuantity();
        
        if ($current < $quantity) {
            throw new ProductException('Quantidade indisponível em estoque');
        }

        return $this->updateStock($current - $quantity, 'saida', $observation);
    }

    /**
     * Get stock movement history
     */
    public function getStockHistory(int $limit = 100): array {
        $stmt = $this->db->prepare("
            SELECT m.*, u.nome as usuario_nome
            FROM movimentacao_estoque m
            LEFT JOIN usuarios u ON m.usuario_id = u.id
            WHERE m.produto_id = ?
            ORDER BY m.data_movimentacao DESC
            LIMIT ?
        ");
        $stmt->execute([$this->id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*****************************************************************
     * Find Methods
     *****************************************************************/

    /**
     * Find product by ID
     */
    public static function findById(int $id): ?Product {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT * FROM produtos 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetchObject(__CLASS__);
    }

    /**
     * Find product by barcode
     */
    public static function findByBarcode(string $barcode): ?Product {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT * FROM produtos 
            WHERE codigo_barras = ?
        ");
        $stmt->execute([$barcode]);
        return $stmt->fetchObject(__CLASS__);
    }

    /**
     * Find all active products
     */
    public static function findAllActive(int $limit = 100, int $offset = 0): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT p.*, e.quantidade as estoque
            FROM produtos p
            JOIN estoque e ON p.id = e.produto_id
            WHERE p.ativo = TRUE
            ORDER BY p.nome ASC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_CLASS, __CLASS__);
    }

    /**
     * Find products by category
     */
    public static function findByCategory(int $categoryId, bool $onlyActive = true): array {
        $sql = "
            SELECT p.*, e.quantidade as estoque
            FROM produtos p
            JOIN estoque e ON p.id = e.produto_id
            WHERE p.categoria_id = ?
        ";

        if ($onlyActive) {
            $sql .= " AND p.ativo = TRUE";
        }

        $sql .= " ORDER BY p.nome ASC";

        $db = Database::getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll(PDO::FETCH_CLASS, __CLASS__);
    }

    /**
     * Search products by name or active principle
     */
    public static function search(string $query, bool $onlyActive = true): array {
        $sql = "
            SELECT p.*, e.quantidade as estoque
            FROM produtos p
            JOIN estoque e ON p.id = e.produto_id
            WHERE (p.nome LIKE ? OR p.principio_ativo LIKE ?)
        ";

        if ($onlyActive) {
            $sql .= " AND p.ativo = TRUE";
        }

        $sql .= " ORDER BY p.nome ASC LIMIT 50";

        $db = Database::getConnection();
        $stmt = $db->prepare($sql);
        $searchTerm = "%$query%";
        $stmt->execute([$searchTerm, $searchTerm]);
        return $stmt->fetchAll(PDO::FETCH_CLASS, __CLASS__);
    }

    /*****************************************************************
     * Helper Methods
     *****************************************************************/

    protected function validateProductData(array $data, bool $isNew = true): void {
        $errors = [];

        // Required fields
        if (empty($data['nome'])) {
            $errors['nome'] = 'Nome do produto é obrigatório';
        }

        if (empty($data['principio_ativo'])) {
            $errors['principio_ativo'] = 'Princípio ativo é obrigatório';
        }

        if (empty($data['concentracao'])) {
            $errors['concentracao'] = 'Concentração é obrigatória';
        }

        if (empty($data['forma_farmaceutica'])) {
            $errors['forma_farmaceutica'] = 'Forma farmacêutica é obrigatória';
        }

        if (empty($data['laboratorio'])) {
            $errors['laboratorio'] = 'Laboratório é obrigatório';
        }

        if (empty($data['preco']) || $data['preco'] <= 0) {
            $errors['preco'] = 'Preço inválido';
        }

        // Unique fields
        if ($isNew || (isset($data['codigo_barras']) && $data['codigo_barras'] !== $this->codigo_barras)) {
            if (!empty($data['codigo_barras']) && self::findByBarcode($data['codigo_barras'])) {
                $errors['codigo_barras'] = 'Código de barras já está em uso';
            }
        }

        if (!empty($errors)) {
            throw new ProductException('Dados de produto inválidos', $errors);
        }
    }

    protected function generateBarcode(): string {
        return 'CAN' . time() . rand(100, 999);
    }

    /*****************************************************************
     * Getters and Setters
     *****************************************************************/

    public function getId(): ?int {
        return $this->id;
    }

    public function getNome(): string {
        return $this->nome;
    }

    public function setNome(string $nome): void {
        $this->nome = $nome;
    }

    public function getDescricao(): ?string {
        return $this->descricao;
    }

    public function setDescricao(?string $descricao): void {
        $this->descricao = $descricao;
    }

    public function getPrincipioAtivo(): string {
        return $this->principio_ativo;
    }

    public function setPrincipioAtivo(string $principio_ativo): void {
        $this->principio_ativo = $principio_ativo;
    }

    public function getConcentracao(): string {
        return $this->concentracao;
    }

    public function setConcentracao(string $concentracao): void {
        $this->concentracao = $concentracao;
    }

    public function getFormaFarmaceutica(): string {
        return $this->forma_farmaceutica;
    }

    public function setFormaFarmaceutica(string $forma_farmaceutica): void {
        $this->forma_farmaceutica = $forma_farmaceutica;
    }

    public function getLaboratorio(): string {
        return $this->laboratorio;
    }

    public function setLaboratorio(string $laboratorio): void {
        $this->laboratorio = $laboratorio;
    }

    public function getCodigoBarras(): string {
        return $this->codigo_barras;
    }

    public function getPreco(): float {
        return (float)$this->preco;
    }

    public function setPreco(float $preco): void {
        $this->preco = $preco;
    }

    public function getPrecoFormatado(): string {
        return 'R$ ' . number_format($this->preco, 2, ',', '.');
    }

    public function getPesoGramas(): ?float {
        return $this->peso_gramas;
    }

    public function getDimensoes(): array {
        return [
            'largura' => $this->largura_cm,
            'altura' => $this->altura_cm,
            'profundidade' => $this->profundidade_cm
        ];
    }

    public function isAtivo(): bool {
        return (bool)$this->ativo;
    }

    public function getImagemPrincipal(): ?string {
        return $this->imagem_principal;
    }

    public function setImagemPrincipal(?string $imagem): void {
        $this->imagem_principal = $imagem;
    }

    public function getCadastradoPor(): ?int {
        return $this->cadastrado_por;
    }

    public function getCategoriaId(): ?int {
        return $this->categoria_id;
    }

    public function setCategoriaId(?int $categoria_id): void {
        $this->categoria_id = $categoria_id;
    }
}