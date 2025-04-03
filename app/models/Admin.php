<?php
namespace App\Models;

use PDO;
use App\Lib\Database;
use App\Lib\Exceptions\AdminException;

class Admin extends User {
    // Admin access levels
    const LEVEL_MASTER = 'master';
    const LEVEL_OPERATIONAL = 'operacional';
    
    protected $nivel_acesso;
    
    public function __construct() {
        parent::__construct();
    }

    /*****************************************************************
     * Admin-Specific Methods
     *****************************************************************/

    /**
     * Approve a vendor registration
     */
    public function approveVendor(int $vendorId): bool {
        if (!$this->isAdmin()) {
            throw new AdminException('Apenas administradores podem aprovar vendedores');
        }

        $stmt = $this->db->prepare("
            UPDATE vendedores SET 
                aprovado = TRUE,
                data_aprovacao = NOW(),
                aprovado_por = ?
            WHERE usuario_id = ?
        ");

        return $stmt->execute([$this->id, $vendorId]);
    }

    /**
     * Reject a vendor registration
     */
    public function rejectVendor(int $vendorId, string $reason): bool {
        if (!$this->isAdmin()) {
            throw new AdminException('Apenas administradores podem rejeitar vendedores');
        }

        $stmt = $this->db->prepare("
            UPDATE vendedores SET 
                aprovado = FALSE,
                motivo_rejeicao = ?,
                data_aprovacao = NOW(),
                aprovado_por = ?
            WHERE usuario_id = ?
        ");

        return $stmt->execute([$reason, $this->id, $vendorId]);
    }

    /**
     * Approve a doctor registration
     */
    public function approveDoctor(int $doctorId): bool {
        if (!$this->isAdmin()) {
            throw new AdminException('Apenas administradores podem aprovar médicos');
        }

        $stmt = $this->db->prepare("
            UPDATE medicos_parceiros SET 
                aprovado = TRUE,
                data_aprovacao = NOW(),
                aprovado_por = ?
            WHERE id = ?
        ");

        return $stmt->execute([$this->id, $doctorId]);
    }

    /**
     * Approve ANVISA approval document
     */
    public function approveAnvisaApproval(int $approvalId): bool {
        if (!$this->isAdmin()) {
            throw new AdminException('Apenas administradores podem aprovar documentos ANVISA');
        }

        $stmt = $this->db->prepare("
            UPDATE liberacoes_anvisa SET 
                aprovado = TRUE,
                data_aprovacao = NOW(),
                aprovado_por = ?
            WHERE id = ?
        ");

        return $stmt->execute([$this->id, $approvalId]);
    }

    /**
     * Approve a prescription
     */
    public function approvePrescription(int $prescriptionId): bool {
        if (!$this->isAdmin()) {
            throw new AdminException('Apenas administradores podem aprovar receitas');
        }

        $stmt = $this->db->prepare("
            UPDATE receitas SET 
                aprovada = TRUE,
                data_aprovacao = NOW(),
                aprovado_por = ?
            WHERE id = ?
        ");

        return $stmt->execute([$this->id, $prescriptionId]);
    }

    /**
     * Release payment for an order
     */
    public function releaseOrderPayment(int $orderId): bool {
        if (!$this->isAdmin()) {
            throw new AdminException('Apenas administradores podem liberar pagamentos');
        }

        // Verify prescription is approved first
        $prescriptionApproved = $this->db->prepare("
            SELECT COUNT(*) FROM receitas 
            WHERE pedido_id = ? AND aprovada = TRUE
        ");
        $prescriptionApproved->execute([$orderId]);

        if (!$prescriptionApproved->fetchColumn()) {
            throw new AdminException('Não é possível liberar pagamento sem receita aprovada');
        }

        $stmt = $this->db->prepare("
            UPDATE pedidos SET 
                status = 'pagamento_pendente',
                data_aprovacao = NOW(),
                aprovado_por = ?
            WHERE id = ?
        ");

        return $stmt->execute([$this->id, $orderId]);
    }

    /**
     * Get all pending approvals
     */
    public function getPendingApprovals(): array {
        $pending = [];

        // Pending vendors
        $stmt = $this->db->query("
            SELECT u.id, u.nome, u.email, u.data_criacao 
            FROM usuarios u
            JOIN vendedores v ON u.id = v.usuario_id
            WHERE v.aprovado = FALSE
        ");
        $pending['vendors'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Pending doctors
        $stmt = $this->db->query("
            SELECT m.id, m.nome, m.crm, m.uf_crm, m.especialidade, u.nome as vendor_nome
            FROM medicos_parceiros m
            JOIN vendedores v ON m.vendedor_id = v.usuario_id
            JOIN usuarios u ON v.usuario_id = u.id
            WHERE m.aprovado = FALSE
        ");
        $pending['doctors'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Pending ANVISA approvals
        $stmt = $this->db->query("
            SELECT l.id, l.numero_registro, l.data_validade, u.nome as client_nome
            FROM liberacoes_anvisa l
            JOIN clientes c ON l.cliente_id = c.usuario_id
            JOIN usuarios u ON c.usuario_id = u.id
            WHERE l.aprovado = FALSE
        ");
        $pending['anvisa_approvals'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Pending prescriptions
        $stmt = $this->db->query("
            SELECT r.id, p.codigo as order_code, u.nome as client_nome
            FROM receitas r
            JOIN pedidos p ON r.pedido_id = p.id
            JOIN clientes c ON p.cliente_id = c.usuario_id
            JOIN usuarios u ON c.usuario_id = u.id
            WHERE r.aprovada = FALSE
        ");
        $pending['prescriptions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $pending;
    }

    /**
     * Get system statistics
     */
    public function getSystemStatistics(): array {
        $stats = [];

        // Count users
        $stmt = $this->db->query("
            SELECT tipo, COUNT(*) as count 
            FROM usuarios 
            GROUP BY tipo
        ");
        $stats['users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Count products
        $stmt = $this->db->query("SELECT COUNT(*) FROM produtos");
        $stats['products_count'] = $stmt->fetchColumn();

        // Count active vs inactive products
        $stmt = $this->db->query("
            SELECT ativo, COUNT(*) as count 
            FROM produtos 
            GROUP BY ativo
        ");
        $stats['products_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Order statistics
        $stmt = $this->db->query("
            SELECT status, COUNT(*) as count 
            FROM pedidos 
            GROUP BY status
        ");
        $stats['orders'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Sales revenue
        $stmt = $this->db->query("
            SELECT 
                SUM(total) as total_revenue,
                COUNT(*) as completed_orders
            FROM pedidos 
            WHERE status = 'entregue'
        ");
        $stats['revenue'] = $stmt->fetch(PDO::FETCH_ASSOC);

        return $stats;
    }

    /*****************************************************************
     * Getters and Setters
     *****************************************************************/

    public function getNivelAcesso(): string {
        return $this->nivel_acesso;
    }

    public function setNivelAcesso(string $nivel): void {
        if (!in_array($nivel, [self::LEVEL_MASTER, self::LEVEL_OPERATIONAL])) {
            throw new AdminException('Nível de acesso inválido');
        }
        $this->nivel_acesso = $nivel;
    }

    public function isMasterAdmin(): bool {
        return $this->nivel_acesso === self::LEVEL_MASTER;
    }

    public function isOperationalAdmin(): bool {
        return $this->nivel_acesso === self::LEVEL_OPERATIONAL;
    }

    /*****************************************************************
     * Factory Method
     *****************************************************************/

    /**
     * Create admin from User instance
     */
    public static function createFromUser(User $user): Admin {
        if (!$user->isAdmin()) {
            throw new AdminException('O usuário não é um administrador');
        }

        $admin = new self();
        $admin->id = $user->getId();
        $admin->nome = $user->getNome();
        $admin->email = $user->getEmail();
        $admin->tipo = $user->getTipo();

        // Load admin-specific data
        $stmt = Database::getConnection()->prepare("
            SELECT nivel_acesso FROM administradores WHERE usuario_id = ?
        ");
        $stmt->execute([$admin->id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            $admin->nivel_acesso = $data['nivel_acesso'];
        }

        return $admin;
    }
}