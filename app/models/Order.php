<?php
namespace App\Models;

use PDO;
use App\Lib\Database;
use App\Lib\Exceptions\OrderException;

class Order {
    // Order statuses
    const STATUS_CART = 'carrinho';
    const STATUS_PENDING_PRESCRIPTION = 'aguardando_receita';
    const STATUS_PENDING_APPROVAL = 'aguardando_aprovacao';
    const STATUS_APPROVED = 'aprovado';
    const STATUS_PENDING_PAYMENT = 'pagamento_pendente';
    const STATUS_PAID = 'pago';
    const STATUS_PREPARING = 'preparando_envio';
    const STATUS_SHIPPED = 'enviado';
    const STATUS_DELIVERED = 'entregue';
    const STATUS_CANCELLED = 'cancelado';

    // Order properties
    protected $id;
    protected $codigo;
    protected $cliente_id;
    protected $vendedor_id;
    protected $medico_id;
    protected $data_pedido;
    protected $status;
    protected $subtotal;
    protected $desconto;
    protected $total;
    protected $metodo_pagamento;
    protected $endereco_entrega_json;
    protected $tracking_code;
    protected $transportadora;
    protected $data_aprovacao;
    protected $aprovado_por;
    protected $motivo_cancelamento;
    
    protected $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /*****************************************************************
     * Order Lifecycle Methods
     *****************************************************************/

    /**
     * Create a new order from cart
     */
    public function createFromCart(int $clientId, array $items, array $shippingAddress): bool {
        $this->validateItems($items);
        $this->validateShippingAddress($shippingAddress);

        $this->db->beginTransaction();

        try {
            // Create order
            $this->codigo = 'ORD-' . strtoupper(uniqid());
            $this->cliente_id = $clientId;
            $this->status = self::STATUS_PENDING_PRESCRIPTION;
            $this->subtotal = $this->calculateSubtotal($items);
            $this->total = $this->subtotal; // No discount initially
            $this->endereco_entrega_json = json_encode($shippingAddress);

            $stmt = $this->db->prepare("
                INSERT INTO pedidos (
                    codigo, cliente_id, status, subtotal, total, endereco_entrega_json
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $this->codigo,
                $this->cliente_id,
                $this->status,
                $this->subtotal,
                $this->total,
                $this->endereco_entrega_json
            ]);
            $this->id = $this->db->lastInsertId();

            // Add order items
            $this->addItems($items);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw new OrderException('Erro ao criar pedido: ' . $e->getMessage());
        }
    }

    /**
     * Add prescription to order
     */
    public function addPrescription(array $prescriptionData): bool {
        if ($this->status !== self::STATUS_PENDING_PRESCRIPTION) {
            throw new OrderException('Pedido não está aguardando receita');
        }

        $this->validatePrescriptionData($prescriptionData);

        $stmt = $this->db->prepare("
            INSERT INTO receitas (
                pedido_id, arquivo_path, crm_medico, uf_crm, nome_medico
            ) VALUES (?, ?, ?, ?, ?)
        ");

        if ($stmt->execute([
            $this->id,
            $prescriptionData['arquivo_path'],
            $prescriptionData['crm_medico'],
            $prescriptionData['uf_crm'],
            $prescriptionData['nome_medico']
        ])) {
            // Update order status
            $this->updateStatus(self::STATUS_PENDING_APPROVAL);
            return true;
        }

        return false;
    }

    /**
     * Approve order (admin only)
     */
    public function approve(int $adminId): bool {
        if ($this->status !== self::STATUS_PENDING_APPROVAL) {
            throw new OrderException('Pedido não está aguardando aprovação');
        }

        // Verify prescription exists
        if (!$this->hasPrescription()) {
            throw new OrderException('Pedido não possui receita médica');
        }

        $stmt = $this->db->prepare("
            UPDATE pedidos SET 
                status = ?,
                data_aprovacao = NOW(),
                aprovado_por = ?
            WHERE id = ?
        ");

        if ($stmt->execute([self::STATUS_PENDING_PAYMENT, $adminId, $this->id])) {
            $this->status = self::STATUS_PENDING_PAYMENT;
            $this->data_aprovacao = date('Y-m-d H:i:s');
            $this->aprovado_por = $adminId;
            return true;
        }

        return false;
    }

    /**
     * Process payment
     */
    public function processPayment(array $paymentData): bool {
        if ($this->status !== self::STATUS_PENDING_PAYMENT) {
            throw new OrderException('Pedido não está pronto para pagamento');
        }

        $this->validatePaymentData($paymentData);

        $this->db->beginTransaction();

        try {
            // Record payment
            $stmt = $this->db->prepare("
                INSERT INTO pagamentos (
                    pedido_id, metodo, valor, status, codigo_transacao, dados_transacao_json
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $this->id,
                $paymentData['metodo'],
                $this->total,
                'pago',
                $paymentData['codigo_transacao'] ?? null,
                json_encode($paymentData['dados_transacao'] ?? [])
            ]);

            // Update order status
            $this->updateStatus(self::STATUS_PAID);

            // Update inventory
            $this->updateInventory();

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw new OrderException('Erro no processamento do pagamento: ' . $e->getMessage());
        }
    }

    /**
     * Cancel order
     */
    public function cancel(string $reason, int $cancelledBy = null): bool {
        if (in_array($this->status, [self::STATUS_DELIVERED, self::STATUS_CANCELLED])) {
            throw new OrderException('Pedido não pode ser cancelado');
        }

        $stmt = $this->db->prepare("
            UPDATE pedidos SET 
                status = ?,
                motivo_cancelamento = ?,
                data_cancelamento = NOW()
            WHERE id = ?
        ");

        if ($stmt->execute([self::STATUS_CANCELLED, $reason, $this->id])) {
            // Restore inventory if order was paid
            if ($this->status === self::STATUS_PAID) {
                $this->restoreInventory();
            }
            return true;
        }

        return false;
    }

    /**
     * Update order status
     */
    public function updateStatus(string $status): bool {
        $validStatuses = [
            self::STATUS_CART,
            self::STATUS_PENDING_PRESCRIPTION,
            self::STATUS_PENDING_APPROVAL,
            self::STATUS_APPROVED,
            self::STATUS_PENDING_PAYMENT,
            self::STATUS_PAID,
            self::STATUS_PREPARING,
            self::STATUS_SHIPPED,
            self::STATUS_DELIVERED,
            self::STATUS_CANCELLED
        ];

        if (!in_array($status, $validStatuses)) {
            throw new OrderException('Status de pedido inválido');
        }

        $stmt = $this->db->prepare("
            UPDATE pedidos SET 
                status = ?
            WHERE id = ?
        ");

        if ($stmt->execute([$status, $this->id])) {
            $this->status = $status;
            return true;
        }

        return false;
    }

    /*****************************************************************
     * Order Items Methods
     *****************************************************************/

    /**
     * Add items to order
     */
    protected function addItems(array $items): void {
        foreach ($items as $item) {
            $product = Product::findById($item['produto_id']);
            
            if (!$product || !$product->isAtivo()) {
                throw new OrderException('Produto inválido ou indisponível: ' . $item['produto_id']);
            }

            if ($product->getStockQuantity() < $item['quantidade']) {
                throw new OrderException('Quantidade indisponível para o produto: ' . $product->getNome());
            }

            $stmt = $this->db->prepare("
                INSERT INTO itens_pedido (
                    pedido_id, produto_id, quantidade, preco_unitario, total_item
                ) VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $this->id,
                $item['produto_id'],
                $item['quantidade'],
                $product->getPreco(),
                $product->getPreco() * $item['quantidade']
            ]);
        }
    }

    /**
     * Get order items
     */
    public function getItems(): array {
        $stmt = $this->db->prepare("
            SELECT i.*, p.nome as produto_nome, p.imagem_principal
            FROM itens_pedido i
            JOIN produtos p ON i.produto_id = p.id
            WHERE i.pedido_id = ?
        ");
        $stmt->execute([$this->id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*****************************************************************
     * Inventory Methods
     *****************************************************************/

    /**
     * Update inventory after payment
     */
    protected function updateInventory(): void {
        $items = $this->getItems();

        foreach ($items as $item) {
            $product = Product::findById($item['produto_id']);
            $product->removeStock($item['quantidade'], "Venda pedido #{$this->codigo}");
        }
    }

    /**
     * Restore inventory after cancellation
     */
    protected function restoreInventory(): void {
        $items = $this->getItems();

        foreach ($items as $item) {
            $product = Product::findById($item['produto_id']);
            $product->addStock($item['quantidade'], "Cancelamento pedido #{$this->codigo}");
        }
    }

    /*****************************************************************
     * Prescription Methods
     *****************************************************************/

    /**
     * Check if order has prescription
     */
    public function hasPrescription(): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM receitas 
            WHERE pedido_id = ?
        ");
        $stmt->execute([$this->id]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Get prescription details
     */
    public function getPrescription(): ?array {
        $stmt = $this->db->prepare("
            SELECT * FROM receitas 
            WHERE pedido_id = ?
        ");
        $stmt->execute([$this->id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /*****************************************************************
     * Payment Methods
     *****************************************************************/

    /**
     * Get payment details
     */
    public function getPayment(): ?array {
        $stmt = $this->db->prepare("
            SELECT * FROM pagamentos 
            WHERE pedido_id = ?
            ORDER BY data_pagamento DESC
            LIMIT 1
        ");
        $stmt->execute([$this->id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /*****************************************************************
     * Shipping Methods
     *****************************************************************/

    /**
     * Update shipping information
     */
    public function updateShippingInfo(array $data): bool {
        $this->validateShippingData($data);

        $stmt = $this->db->prepare("
            UPDATE pedidos SET 
                tracking_code = ?,
                transportadora = ?,
                status = ?
            WHERE id = ?
        ");

        return $stmt->execute([
            $data['tracking_code'],
            $data['transportadora'],
            self::STATUS_SHIPPED,
            $this->id
        ]);
    }

    /*****************************************************************
     * Find Methods
     *****************************************************************/

    /**
     * Find order by ID
     */
    public static function findById(int $id): ?Order {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT * FROM pedidos 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetchObject(__CLASS__);
    }

    /**
     * Find order by code
     */
    public static function findByCode(string $code): ?Order {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT * FROM pedidos 
            WHERE codigo = ?
        ");
        $stmt->execute([$code]);
        return $stmt->fetchObject(__CLASS__);
    }

    /**
     * Find orders by client
     */
    public static function findByClient(int $clientId, string $status = null): array {
        $sql = "
            SELECT * FROM pedidos 
            WHERE cliente_id = ?
        ";

        $params = [$clientId];

        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY data_pedido DESC";

        $db = Database::getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_CLASS, __CLASS__);
    }

    /**
     * Find orders by vendor
     */
    public static function findByVendor(int $vendorId, string $status = null): array {
        $sql = "
            SELECT p.*, c.nome as cliente_nome
            FROM pedidos p
            JOIN clientes cl ON p.cliente_id = cl.usuario_id
            JOIN usuarios c ON cl.usuario_id = c.id
            WHERE p.vendedor_id = ?
        ";

        $params = [$vendorId];

        if ($status) {
            $sql .= " AND p.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY p.data_pedido DESC";

        $db = Database::getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_CLASS, __CLASS__);
    }

    /**
     * Find orders by doctor
     */
    public static function findByDoctor(int $doctorId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT p.*, c.nome as cliente_nome
            FROM pedidos p
            JOIN clientes cl ON p.cliente_id = cl.usuario_id
            JOIN usuarios c ON cl.usuario_id = c.id
            WHERE p.medico_id = ?
            ORDER BY p.data_pedido DESC
        ");
        $stmt->execute([$doctorId]);
        return $stmt->fetchAll(PDO::FETCH_CLASS, __CLASS__);
    }

    /*****************************************************************
     * Validation Methods
     *****************************************************************/

    protected function validateItems(array $items): void {
        if (empty($items)) {
            throw new OrderException('Pedido deve conter itens');
        }

        foreach ($items as $item) {
            if (empty($item['produto_id']) || empty($item['quantidade']) || $item['quantidade'] <= 0) {
                throw new OrderException('Item de pedido inválido');
            }
        }
    }

    protected function validateShippingAddress(array $address): void {
        $required = ['cep', 'logradouro', 'numero', 'cidade', 'estado'];
        $errors = [];

        foreach ($required as $field) {
            if (empty($address[$field])) {
                $errors[$field] = "Campo obrigatório";
            }
        }

        if (!empty($errors)) {
            throw new OrderException('Endereço de entrega inválido', $errors);
        }
    }

    protected function validatePrescriptionData(array $data): void {
        $required = ['arquivo_path', 'crm_medico', 'uf_crm'];
        $errors = [];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[$field] = "Campo obrigatório";
            }
        }

        if (!empty($errors)) {
            throw new OrderException('Dados da receita inválidos', $errors);
        }
    }

    protected function validatePaymentData(array $data): void {
        if (empty($data['metodo'])) {
            throw new OrderException('Método de pagamento é obrigatório');
        }
    }

    protected function validateShippingData(array $data): void {
        if (empty($data['tracking_code']) || empty($data['transportadora'])) {
            throw new OrderException('Dados de envio inválidos');
        }
    }

    protected function calculateSubtotal(array $items): float {
        $subtotal = 0;

        foreach ($items as $item) {
            $product = Product::findById($item['produto_id']);
            $subtotal += $product->getPreco() * $item['quantidade'];
        }

        return $subtotal;
    }

    /*****************************************************************
     * Getters and Setters
     *****************************************************************/

    public function getId(): ?int {
        return $this->id;
    }

    public function getCodigo(): string {
        return $this->codigo;
    }

    public function getClienteId(): int {
        return $this->cliente_id;
    }

    public function getVendedorId(): ?int {
        return $this->vendedor_id;
    }

    public function getMedicoId(): ?int {
        return $this->medico_id;
    }

    public function getDataPedido(): string {
        return $this->data_pedido;
    }

    public function getStatus(): string {
        return $this->status;
    }

    public function getStatusLabel(): string {
        $statuses = [
            self::STATUS_CART => 'Carrinho',
            self::STATUS_PENDING_PRESCRIPTION => 'Aguardando Receita',
            self::STATUS_PENDING_APPROVAL => 'Aguardando Aprovação',
            self::STATUS_APPROVED => 'Aprovado',
            self::STATUS_PENDING_PAYMENT => 'Pagamento Pendente',
            self::STATUS_PAID => 'Pago',
            self::STATUS_PREPARING => 'Preparando Envio',
            self::STATUS_SHIPPED => 'Enviado',
            self::STATUS_DELIVERED => 'Entregue',
            self::STATUS_CANCELLED => 'Cancelado'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getSubtotal(): float {
        return (float)$this->subtotal;
    }

    public function getDesconto(): float {
        return (float)$this->desconto;
    }

    public function getTotal(): float {
        return (float)$this->total;
    }

    public function getTotalFormatado(): string {
        return 'R$ ' . number_format($this->total, 2, ',', '.');
    }

    public function getMetodoPagamento(): ?string {
        return $this->metodo_pagamento;
    }

    public function getShippingAddress(): array {
        return json_decode($this->endereco_entrega_json, true);
    }

    public function getTrackingCode(): ?string {
        return $this->tracking_code;
    }

    public function getTransportadora(): ?string {
        return $this->transportadora;
    }

    public function getDataAprovacao(): ?string {
        return $this->data_aprovacao;
    }

    public function getAprovadoPor(): ?int {
        return $this->aprovado_por;
    }

    public function getMotivoCancelamento(): ?string {
        return $this->motivo_cancelamento;
    }
}