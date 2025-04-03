<?php
namespace App\Models;

use PDO;
use App\Lib\Database;
use App\Lib\Exceptions\ClientException;

class Client extends User {
    // Client properties
    protected $data_nascimento;
    protected $genero;
    
    // Gender options
    const GENDER_MALE = 'masculino';
    const GENDER_FEMALE = 'feminino';
    const GENDER_OTHER = 'outro';

    public function __construct() {
        parent::__construct();
    }

    /*****************************************************************
     * ANVISA Approval Methods
     *****************************************************************/

    /**
     * Upload ANVISA approval document
     */
    public function uploadAnvisaApproval(array $data): bool {
        $this->validateAnvisaData($data);

        $stmt = $this->db->prepare("
            INSERT INTO liberacoes_anvisa (
                cliente_id, numero_registro, arquivo_path, data_validade
            ) VALUES (?, ?, ?, ?)
        ");

        return $stmt->execute([
            $this->id,
            $data['numero_registro'],
            $data['arquivo_path'],
            $data['data_validade']
        ]);
    }

    /**
     * Get current ANVISA approval status
     */
    public function getAnvisaApproval(): ?array {
        $stmt = $this->db->prepare("
            SELECT * FROM liberacoes_anvisa 
            WHERE cliente_id = ? 
            ORDER BY data_validade DESC 
            LIMIT 1
        ");
        $stmt->execute([$this->id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Check if client has valid ANVISA approval
     */
    public function hasValidAnvisaApproval(): bool {
        $approval = $this->getAnvisaApproval();
        return $approval && $approval['aprovado'] && 
               strtotime($approval['data_validade']) > time();
    }

    /*****************************************************************
     * Order Methods
     *****************************************************************/

    /**
     * Create a new order
     */
    public function createOrder(array $items): array {
        if (!$this->hasValidAnvisaApproval()) {
            throw new ClientException('Aprovação ANVISA inválida ou expirada');
        }

        $this->db->beginTransaction();

        try {
            // Create order
            $orderCode = 'ORD-' . strtoupper(uniqid());
            $total = $this->calculateOrderTotal($items);

            $stmt = $this->db->prepare("
                INSERT INTO pedidos (
                    codigo, cliente_id, status, subtotal, total
                ) VALUES (?, ?, 'carrinho', ?, ?)
            ");
            $stmt->execute([$orderCode, $this->id, $total, $total]);
            $orderId = $this->db->lastInsertId();

            // Add order items
            $this->addOrderItems($orderId, $items);

            $this->db->commit();

            return [
                'order_id' => $orderId,
                'order_code' => $orderCode,
                'status' => 'carrinho',
                'total' => $total
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw new ClientException('Erro ao criar pedido: ' . $e->getMessage());
        }
    }

    /**
     * Add prescription to order
     */
    public function addPrescription(int $orderId, array $prescriptionData): bool {
        $this->validatePrescriptionData($prescriptionData);

        $stmt = $this->db->prepare("
            INSERT INTO receitas (
                pedido_id, arquivo_path, crm_medico, uf_crm, nome_medico
            ) VALUES (?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $orderId,
            $prescriptionData['arquivo_path'],
            $prescriptionData['crm_medico'],
            $prescriptionData['uf_crm'],
            $prescriptionData['nome_medico']
        ]);
    }

    /**
     * Get client orders
     */
    public function getOrders(string $status = null): array {
        $sql = "
            SELECT p.*, 
                   (SELECT COUNT(*) FROM itens_pedido WHERE pedido_id = p.id) as item_count,
                   (SELECT SUM(quantidade) FROM itens_pedido WHERE pedido_id = p.id) as total_items
            FROM pedidos p
            WHERE p.cliente_id = ?
        ";

        $params = [$this->id];

        if ($status) {
            $sql .= " AND p.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY p.data_pedido DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get order details
     */
    public function getOrderDetails(int $orderId): array {
        // Verify order belongs to client
        $stmt = $this->db->prepare("
            SELECT id FROM pedidos 
            WHERE id = ? AND cliente_id = ?
        ");
        $stmt->execute([$orderId, $this->id]);

        if (!$stmt->fetch()) {
            throw new ClientException('Pedido não encontrado');
        }

        // Get order info
        $stmt = $this->db->prepare("
            SELECT p.*, r.arquivo_path as receita_path, r.aprovada as receita_aprovada
            FROM pedidos p
            LEFT JOIN receitas r ON p.id = r.pedido_id
            WHERE p.id = ?
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get order items
        $stmt = $this->db->prepare("
            SELECT i.*, p.nome as produto_nome, p.imagem_principal
            FROM itens_pedido i
            JOIN produtos p ON i.produto_id = p.id
            WHERE i.pedido_id = ?
        ");
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'order' => $order,
            'items' => $items
        ];
    }

    /*****************************************************************
     * Payment Methods
     *****************************************************************/

    /**
     * Process order payment
     */
    public function processPayment(int $orderId, array $paymentData): bool {
        // Verify order belongs to client and is ready for payment
        $stmt = $this->db->prepare("
            SELECT id FROM pedidos 
            WHERE id = ? AND cliente_id = ? AND status = 'pagamento_pendente'
        ");
        $stmt->execute([$orderId, $this->id]);

        if (!$stmt->fetch()) {
            throw new ClientException('Pedido não disponível para pagamento');
        }

        $this->db->beginTransaction();

        try {
            // Record payment
            $stmt = $this->db->prepare("
                INSERT INTO pagamentos (
                    pedido_id, metodo, valor, status, codigo_transacao, dados_transacao_json
                ) VALUES (?, ?, ?, 'processando', ?, ?)
            ");
            $stmt->execute([
                $orderId,
                $paymentData['metodo'],
                $paymentData['valor'],
                $paymentData['codigo_transacao'] ?? null,
                json_encode($paymentData['dados_transacao'] ?? [])
            ]);

            // Update order status
            $stmt = $this->db->prepare("
                UPDATE pedidos SET 
                    status = 'pago',
                    metodo_pagamento = ?,
                    data_pagamento = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $paymentData['metodo'],
                $orderId
            ]);

            // Update stock
            $this->updateInventory($orderId);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw new ClientException('Erro no processamento do pagamento: ' . $e->getMessage());
        }
    }

    /*****************************************************************
     * Helper Methods
     *****************************************************************/

    protected function calculateOrderTotal(array $items): float {
        $total = 0;

        foreach ($items as $item) {
            $stmt = $this->db->prepare("
                SELECT preco FROM produtos 
                WHERE id = ? AND ativo = TRUE
            ");
            $stmt->execute([$item['produto_id']]);
            $price = $stmt->fetchColumn();

            if (!$price) {
                throw new ClientException('Produto inválido ou indisponível: ' . $item['produto_id']);
            }

            $total += $price * $item['quantidade'];
        }

        return $total;
    }

    protected function addOrderItems(int $orderId, array $items): void {
        foreach ($items as $item) {
            $stmt = $this->db->prepare("
                INSERT INTO itens_pedido (
                    pedido_id, produto_id, quantidade, preco_unitario, total_item
                ) VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $orderId,
                $item['produto_id'],
                $item['quantidade'],
                $item['preco_unitario'],
                $item['preco_unitario'] * $item['quantidade']
            ]);
        }
    }

    protected function updateInventory(int $orderId): void {
        $stmt = $this->db->prepare("
            SELECT produto_id, quantidade 
            FROM itens_pedido 
            WHERE pedido_id = ?
        ");
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as $item) {
            $stmt = $this->db->prepare("
                UPDATE estoque SET 
                    quantidade = quantidade - ?,
                    ultima_saida = NOW()
                WHERE produto_id = ?
            ");
            $stmt->execute([$item['quantidade'], $item['produto_id']]);
        }
    }

    protected function validateAnvisaData(array $data): void {
        $errors = [];

        if (empty($data['numero_registro'])) {
            $errors['numero_registro'] = 'Número de registro ANVISA é obrigatório';
        }

        if (empty($data['arquivo_path'])) {
            $errors['arquivo_path'] = 'Documento é obrigatório';
        }

        if (empty($data['data_validade']) || strtotime($data['data_validade']) < time()) {
            $errors['data_validade'] = 'Data de validade inválida';
        }

        if (!empty($errors)) {
            throw new ClientException('Dados de aprovação ANVISA inválidos', $errors);
        }
    }

    protected function validatePrescriptionData(array $data): void {
        $errors = [];

        if (empty($data['arquivo_path'])) {
            $errors['arquivo_path'] = 'Receita médica é obrigatória';
        }

        if (empty($data['crm_medico'])) {
            $errors['crm_medico'] = 'CRM do médico é obrigatório';
        }

        if (empty($data['uf_crm'])) {
            $errors['uf_crm'] = 'UF do CRM é obrigatória';
        }

        if (!empty($errors)) {
            throw new ClientException('Dados da receita médica inválidos', $errors);
        }
    }

    /*****************************************************************
     * Getters and Setters
     *****************************************************************/

    public function getDataNascimento(): ?string {
        return $this->data_nascimento;
    }

    public function setDataNascimento(?string $data): void {
        $this->data_nascimento = $data;
    }

    public function getGenero(): ?string {
        return $this->genero;
    }

    public function setGenero(?string $genero): void {
        if ($genero && !in_array($genero, [self::GENDER_MALE, self::GENDER_FEMALE, self::GENDER_OTHER])) {
            throw new ClientException('Gênero inválido');
        }
        $this->genero = $genero;
    }

    /*****************************************************************
     * Factory Method
     *****************************************************************/

    public static function createFromUser(User $user): Client {
        if (!$user->isClient()) {
            throw new ClientException('O usuário não é um cliente');
        }

        $client = new self();
        $client->id = $user->getId();
        $client->nome = $user->getNome();
        $client->email = $user->getEmail();
        $client->tipo = $user->getTipo();

        // Load client-specific data
        $stmt = Database::getConnection()->prepare("
            SELECT data_nascimento, genero FROM clientes WHERE usuario_id = ?
        ");
        $stmt->execute([$client->id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            $client->data_nascimento = $data['data_nascimento'];
            $client->genero = $data['genero'];
        }

        return $client;
    }
}