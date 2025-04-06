<?php
namespace App\Models;

use PDO;
use App\Lib\Database;
use App\Lib\Exceptions\CommissionException;

class Commission {
    // Commission statuses
    const STATUS_PENDING = 'pendente';
    const STATUS_AVAILABLE = 'disponivel';
    const STATUS_REQUESTED = 'solicitado';
    const STATUS_PAID = 'pago';
    
    // Commission properties
    protected $id;
    protected $vendedor_id;
    protected $pedido_id;
    protected $medico_id;
    protected $valor_comissao;
    protected $percentual_comissao;
    protected $status;
    protected $data_criacao;
    protected $data_disponibilidade;
    protected $data_pagamento;
    
    protected $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /*****************************************************************
     * Commission Calculation Methods
     *****************************************************************/

    /**
     * Calculate commission for an order
     */
    public static function calculateForOrder(int $orderId): bool {
        $db = Database::getConnection();
        $order = Order::findById($orderId);

        if (!$order || !$order->getVendedorId()) {
            return false;
        }

        // Get vendor commission rate
        $vendor = Vendor::createFromUser(User::findById($order->getVendedorId()));
        $commissionRate = $vendor->getComissaoPercentual();

        // Calculate commission amount
        $commissionValue = $order->getTotal() * ($commissionRate / 100);

        // Check if doctor is a partner
        $doctorId = null;
        if ($order->getMedicoId()) {
            $doctor = Doctor::findById($order->getMedicoId());
            if ($doctor && $doctor->isAprovado()) {
                $doctorId = $doctor->getId();
            }
        }

        // Create commission record
        $stmt = $db->prepare("
            INSERT INTO comissoes (
                vendedor_id, pedido_id, medico_id, 
                valor_comissao, percentual_comissao, status
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $order->getVendedorId(),
            $order->getId(),
            $doctorId,
            $commissionValue,
            $commissionRate,
            self::STATUS_PENDING
        ]);
    }

    /**
     * Release pending commissions after waiting period
     */
    public static function releasePendingCommissions(int $days = 30): int {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE comissoes SET 
                status = ?,
                data_disponibilidade = NOW()
            WHERE status = ? 
            AND data_criacao < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([
            self::STATUS_AVAILABLE,
            self::STATUS_PENDING,
            $days
        ]);
        return $stmt->rowCount();
    }

    /*****************************************************************
     * Commission Management Methods
     *****************************************************************/

    /**
     * Mark commission as paid
     */
    public function markAsPaid(): bool {
        if ($this->status !== self::STATUS_REQUESTED) {
            throw new CommissionException('Comissão não está solicitada para pagamento');
        }

        $stmt = $this->db->prepare("
            UPDATE comissoes SET 
                status = ?,
                data_pagamento = NOW()
            WHERE id = ?
        ");

        return $stmt->execute([
            self::STATUS_PAID,
            $this->id
        ]);
    }

    /**
     * Get associated order details
     */
    public function getOrder(): ?Order {
        return Order::findById($this->pedido_id);
    }

    /**
     * Get associated vendor
     */
    public function getVendor(): ?Vendor {
        return Vendor::createFromUser(User::findById($this->vendedor_id));
    }

    /**
     * Get associated doctor (if any)
     */
    public function getDoctor(): ?Doctor {
        if (!$this->medico_id) {
            return null;
        }
        return Doctor::findById($this->medico_id);
    }

    /*****************************************************************
     * Find Methods
     *****************************************************************/

    /**
     * Find commission by ID
     */
    public static function findById(int $id): ?Commission {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT * FROM comissoes 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetchObject(__CLASS__);
    }

    /**
     * Find commissions by vendor
     */
    public static function findByVendor(int $vendorId, string $status = null): array {
        $sql = "
            SELECT c.*, p.codigo as pedido_codigo, p.total as pedido_total
            FROM comissoes c
            JOIN pedidos p ON c.pedido_id = p.id
            WHERE c.vendedor_id = ?
        ";

        $params = [$vendorId];

        if ($status) {
            $sql .= " AND c.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY c.data_criacao DESC";

        $db = Database::getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_CLASS, __CLASS__);
    }

    /**
     * Find commissions by doctor
     */
    public static function findByDoctor(int $doctorId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT c.*, v.nome as vendedor_nome
            FROM comissoes c
            JOIN vendedores ve ON c.vendedor_id = ve.usuario_id
            JOIN usuarios v ON ve.usuario_id = v.id
            WHERE c.medico_id = ?
            ORDER BY c.data_criacao DESC
        ");
        $stmt->execute([$doctorId]);
        return $stmt->fetchAll(PDO::FETCH_CLASS, __CLASS__);
    }

    /**
     * Find commissions ready for withdrawal
     */
    public static function findAvailableForWithdrawal(int $vendorId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT * FROM comissoes 
            WHERE vendedor_id = ? AND status = ?
            ORDER BY data_disponibilidade ASC
        ");
        $stmt->execute([$vendorId, self::STATUS_AVAILABLE]);
        return $stmt->fetchAll(PDO::FETCH_CLASS, __CLASS__);
    }

    /**
     * Find commissions included in a withdrawal request
     */
    public static function findByWithdrawal(int $withdrawalId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT c.* 
            FROM comissoes c
            JOIN saques_comissoes_itens s ON c.id = s.comissao_id
            WHERE s.saque_id = ?
        ");
        $stmt->execute([$withdrawalId]);
        return $stmt->fetchAll(PDO::FETCH_CLASS, __CLASS__);
    }

    /*****************************************************************
     * Report Methods
     *****************************************************************/

    /**
     * Get vendor's commission summary
     */
    public static function getVendorSummary(int $vendorId): array {
        $db = Database::getConnection();
        $summary = [];

        // Total commissions
        $stmt = $db->prepare("
            SELECT 
                SUM(valor_comissao) as total,
                COUNT(*) as count
            FROM comissoes 
            WHERE vendedor_id = ?
        ");
        $stmt->execute([$vendorId]);
        $summary['total'] = $stmt->fetch(PDO::FETCH_ASSOC);

        // By status
        $stmt = $db->prepare("
            SELECT 
                status,
                SUM(valor_comissao) as total,
                COUNT(*) as count
            FROM comissoes 
            WHERE vendedor_id = ?
            GROUP BY status
        ");
        $stmt->execute([$vendorId]);
        $summary['by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // By month
        $stmt = $db->prepare("
            SELECT 
                DATE_FORMAT(data_criacao, '%Y-%m') as month,
                SUM(valor_comissao) as total,
                COUNT(*) as count
            FROM comissoes 
            WHERE vendedor_id = ?
            GROUP BY DATE_FORMAT(data_criacao, '%Y-%m')
            ORDER BY month DESC
            LIMIT 6
        ");
        $stmt->execute([$vendorId]);
        $summary['by_month'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // By doctor
        $stmt = $db->prepare("
            SELECT 
                m.nome as medico,
                SUM(c.valor_comissao) as total,
                COUNT(*) as count
            FROM comissoes c
            LEFT JOIN medicos_parceiros m ON c.medico_id = m.id
            WHERE c.vendedor_id = ?
            GROUP BY c.medico_id
            ORDER BY total DESC
            LIMIT 5
        ");
        $stmt->execute([$vendorId]);
        $summary['by_doctor'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $summary;
    }

    /*****************************************************************
     * Getters and Setters
     *****************************************************************/

    public function getId(): ?int {
        return $this->id;
    }

    public function getVendedorId(): int {
        return $this->vendedor_id;
    }

    public function getPedidoId(): int {
        return $this->pedido_id;
    }

    public function getMedicoId(): ?int {
        return $this->medico_id;
    }

    public function getValorComissao(): float {
        return (float)$this->valor_comissao;
    }

    public function getValorComissaoFormatado(): string {
        return 'R$ ' . number_format($this->valor_comissao, 2, ',', '.');
    }

    public function getPercentualComissao(): float {
        return (float)$this->percentual_comissao;
    }

    public function getStatus(): string {
        return $this->status;
    }

    public function getStatusLabel(): string {
        $statuses = [
            self::STATUS_PENDING => 'Pendente',
            self::STATUS_AVAILABLE => 'Disponível',
            self::STATUS_REQUESTED => 'Solicitado',
            self::STATUS_PAID => 'Pago'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getDataCriacao(): string {
        return $this->data_criacao;
    }

    public function getDataDisponibilidade(): ?string {
        return $this->data_disponibilidade;
    }

    public function getDataPagamento(): ?string {
        return $this->data_pagamento;
    }

    public function isAvailable(): bool {
        return $this->status === self::STATUS_AVAILABLE;
    }

    public function isPaid(): bool {
        return $this->status === self::STATUS_PAID;
    }
}