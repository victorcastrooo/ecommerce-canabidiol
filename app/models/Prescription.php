<?php
namespace App\Models;

use PDO;
use App\Lib\Database;
use App\Lib\Exceptions\PrescriptionException;

class Prescription {
    // Prescription properties
    protected $id;
    protected $pedido_id;
    protected $arquivo_path;
    protected $data_upload;
    protected $crm_medico;
    protected $uf_crm;
    protected $nome_medico;
    protected $aprovada;
    protected $data_aprovacao;
    protected $motivo_rejeicao;
    protected $aprovado_por;
    
    protected $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /*****************************************************************
     * CRUD Methods
     *****************************************************************/

    /**
     * Create a new prescription record
     */
    public function create(array $data): bool {
        $this->validatePrescriptionData($data);

        $stmt = $this->db->prepare("
            INSERT INTO receitas (
                pedido_id, arquivo_path, crm_medico, uf_crm, nome_medico
            ) VALUES (?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $data['pedido_id'],
            $data['arquivo_path'],
            $data['crm_medico'],
            $data['uf_crm'],
            $data['nome_medico'] ?? null
        ]);
    }

    /**
     * Approve prescription (admin only)
     */
    public function approve(int $adminId): bool {
        if ($this->aprovada) {
            return true; // Already approved
        }

        $stmt = $this->db->prepare("
            UPDATE receitas SET 
                aprovada = TRUE,
                data_aprovacao = NOW(),
                aprovado_por = ?,
                motivo_rejeicao = NULL
            WHERE id = ?
        ");

        if ($stmt->execute([$adminId, $this->id])) {
            // Update order status to approved
            $order = Order::findById($this->pedido_id);
            return $order->updateStatus(Order::STATUS_APPROVED);
        }

        return false;
    }

    /**
     * Reject prescription (admin only)
     */
    public function reject(int $adminId, string $reason): bool {
        $stmt = $this->db->prepare("
            UPDATE receitas SET 
                aprovada = FALSE,
                data_aprovacao = NOW(),
                aprovado_por = ?,
                motivo_rejeicao = ?
            WHERE id = ?
        ");

        if ($stmt->execute([$adminId, $reason, $this->id])) {
            // Update order status to needs prescription
            $order = Order::findById($this->pedido_id);
            return $order->updateStatus(Order::STATUS_PENDING_PRESCRIPTION);
        }

        return false;
    }

    /*****************************************************************
     * Find Methods
     *****************************************************************/

    /**
     * Find prescription by ID
     */
    public static function findById(int $id): ?Prescription {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT * FROM receitas 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetchObject(__CLASS__);
    }

    /**
     * Find prescription by order ID
     */
    public static function findByOrder(int $orderId): ?Prescription {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT * FROM receitas 
            WHERE pedido_id = ?
            ORDER BY data_upload DESC
            LIMIT 1
        ");
        $stmt->execute([$orderId]);
        return $stmt->fetchObject(__CLASS__);
    }

    /**
     * Find prescriptions by doctor CRM
     */
    public static function findByDoctor(string $crm, string $uf): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT r.*, p.codigo as pedido_codigo
            FROM receitas r
            JOIN pedidos p ON r.pedido_id = p.id
            WHERE r.crm_medico = ? AND r.uf_crm = ?
            ORDER BY r.data_upload DESC
        ");
        $stmt->execute([$crm, $uf]);
        return $stmt->fetchAll(PDO::FETCH_CLASS, __CLASS__);
    }

    /**
     * Find all pending prescriptions
     */
    public static function findPendingApprovals(): array {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT r.*, p.codigo as pedido_codigo, u.nome as cliente_nome
            FROM receitas r
            JOIN pedidos p ON r.pedido_id = p.id
            JOIN clientes c ON p.cliente_id = c.usuario_id
            JOIN usuarios u ON c.usuario_id = u.id
            WHERE r.aprovada IS NULL
            ORDER BY r.data_upload ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_CLASS, __CLASS__);
    }

    /*****************************************************************
     * Validation Methods
     *****************************************************************/

    protected function validatePrescriptionData(array $data): void {
        $errors = [];

        if (empty($data['pedido_id'])) {
            $errors['pedido_id'] = 'ID do pedido é obrigatório';
        }

        if (empty($data['arquivo_path'])) {
            $errors['arquivo_path'] = 'Arquivo da receita é obrigatório';
        }

        if (empty($data['crm_medico'])) {
            $errors['crm_medico'] = 'CRM do médico é obrigatório';
        }

        if (empty($data['uf_crm']) || strlen($data['uf_crm']) != 2) {
            $errors['uf_crm'] = 'UF do CRM é obrigatória (2 caracteres)';
        }

        if (!empty($errors)) {
            throw new PrescriptionException('Dados da receita inválidos', $errors);
        }
    }

    /*****************************************************************
     * Getters and Setters
     *****************************************************************/

    public function getId(): ?int {
        return $this->id;
    }

    public function getPedidoId(): int {
        return $this->pedido_id;
    }

    public function getArquivoPath(): string {
        return $this->arquivo_path;
    }

    public function getDataUpload(): string {
        return $this->data_upload;
    }

    public function getCrmMedico(): string {
        return $this->crm_medico;
    }

    public function getUfCrm(): string {
        return $this->uf_crm;
    }

    public function getCrmCompleto(): string {
        return $this->crm_medico . '/' . $this->uf_crm;
    }

    public function getNomeMedico(): ?string {
        return $this->nome_medico;
    }

    public function isAprovada(): bool {
        return (bool)$this->aprovada;
    }

    public function getDataAprovacao(): ?string {
        return $this->data_aprovacao;
    }

    public function getMotivoRejeicao(): ?string {
        return $this->motivo_rejeicao;
    }

    public function getAprovadoPor(): ?int {
        return $this->aprovado_por;
    }

    public function getOrder(): Order {
        return Order::findById($this->pedido_id);
    }

    public function getDoctor(): ?Doctor {
        return Doctor::findByCrm($this->crm_medico, $this->uf_crm);
    }
}