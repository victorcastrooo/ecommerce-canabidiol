<?php
namespace App\Models;

use PDO;
use App\Lib\Database;
use App\Lib\Exceptions\DoctorException;

class Doctor {
    // Doctor properties
    protected $id;
    protected $vendedor_id;
    protected $nome;
    protected $crm;
    protected $uf_crm;
    protected $especialidade;
    protected $email;
    protected $telefone;
    protected $aprovado;
    protected $data_aprovacao;
    protected $aprovado_por;
    
    protected $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /*****************************************************************
     * CRUD Methods
     *****************************************************************/

    /**
     * Create a new doctor record
     */
    public function create(array $data): bool {
        $this->validateDoctorData($data);

        $stmt = $this->db->prepare("
            INSERT INTO medicos_parceiros (
                vendedor_id, nome, crm, uf_crm, 
                especialidade, email, telefone
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $data['vendedor_id'],
            $data['nome'],
            $data['crm'],
            $data['uf_crm'],
            $data['especialidade'] ?? null,
            $data['email'] ?? null,
            $data['telefone'] ?? null
        ]);
    }

    /**
     * Update doctor information
     */
    public function update(array $data): bool {
        $this->validateDoctorData($data);

        $stmt = $this->db->prepare("
            UPDATE medicos_parceiros SET 
                nome = ?,
                crm = ?,
                uf_crm = ?,
                especialidade = ?,
                email = ?,
                telefone = ?
            WHERE id = ?
        ");

        return $stmt->execute([
            $data['nome'],
            $data['crm'],
            $data['uf_crm'],
            $data['especialidade'] ?? null,
            $data['email'] ?? null,
            $data['telefone'] ?? null,
            $this->id
        ]);
    }

    /**
     * Delete doctor record
     */
    public function delete(): bool {
        // Check if doctor has associated prescriptions
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM receitas 
            WHERE crm_medico = ? AND uf_crm = ?
        ");
        $stmt->execute([$this->crm, $this->uf_crm]);

        if ($stmt->fetchColumn() > 0) {
            throw new DoctorException('Não é possível excluir médico com receitas associadas');
        }

        $stmt = $this->db->prepare("DELETE FROM medicos_parceiros WHERE id = ?");
        return $stmt->execute([$this->id]);
    }

    /*****************************************************************
     * Find Methods
     *****************************************************************/

    /**
     * Find doctor by ID
     */
    public static function findById(int $id): ?Doctor {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT * FROM medicos_parceiros 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetchObject(__CLASS__);
    }

    /**
     * Find doctor by CRM
     */
    public static function findByCrm(string $crm, string $uf): ?Doctor {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT * FROM medicos_parceiros 
            WHERE crm = ? AND uf_crm = ?
        ");
        $stmt->execute([$crm, $uf]);
        return $stmt->fetchObject(__CLASS__);
    }

    /**
     * Find all doctors by vendor
     */
    public static function findByVendor(int $vendorId, bool $onlyApproved = true): array {
        $sql = "
            SELECT * FROM medicos_parceiros 
            WHERE vendedor_id = ?
        ";

        if ($onlyApproved) {
            $sql .= " AND aprovado = TRUE";
        }

        $sql .= " ORDER BY nome ASC";

        $db = Database::getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute([$vendorId]);
        return $stmt->fetchAll(PDO::FETCH_CLASS, __CLASS__);
    }

    /**
     * Find all pending approvals
     */
    public static function findPendingApprovals(): array {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT m.*, u.nome as vendor_nome 
            FROM medicos_parceiros m
            JOIN usuarios u ON m.vendedor_id = u.id
            WHERE m.aprovado = FALSE
            ORDER BY m.data_criacao DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*****************************************************************
     * Prescription Methods
     *****************************************************************/

    /**
     * Get all prescriptions associated with this doctor
     */
    public function getPrescriptions(): array {
        $stmt = $this->db->prepare("
            SELECT r.*, p.codigo as order_code, u.nome as client_name
            FROM receitas r
            JOIN pedidos p ON r.pedido_id = p.id
            JOIN clientes c ON p.cliente_id = c.usuario_id
            JOIN usuarios u ON c.usuario_id = u.id
            WHERE r.crm_medico = ? AND r.uf_crm = ?
            ORDER BY r.data_upload DESC
        ");
        $stmt->execute([$this->crm, $this->uf_crm]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get prescription statistics
     */
    public function getPrescriptionStats(): array {
        $stats = [];

        // Total prescriptions
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total_prescriptions
            FROM receitas
            WHERE crm_medico = ? AND uf_crm = ?
        ");
        $stmt->execute([$this->crm, $this->uf_crm]);
        $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC);

        // Approved vs rejected
        $stmt = $this->db->prepare("
            SELECT aprovada, COUNT(*) as count
            FROM receitas
            WHERE crm_medico = ? AND uf_crm = ?
            GROUP BY aprovada
        ");
        $stmt->execute([$this->crm, $this->uf_crm]);
        $stats['status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // By month
        $stmt = $this->db->prepare("
            SELECT 
                DATE_FORMAT(data_upload, '%Y-%m') as month,
                COUNT(*) as count
            FROM receitas
            WHERE crm_medico = ? AND uf_crm = ?
            GROUP BY DATE_FORMAT(data_upload, '%Y-%m')
            ORDER BY month DESC
            LIMIT 6
        ");
        $stmt->execute([$this->crm, $this->uf_crm]);
        $stats['by_month'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $stats;
    }

    /*****************************************************************
     * Approval Methods
     *****************************************************************/

    /**
     * Approve this doctor (admin only)
     */
    public function approve(int $adminId): bool {
        if ($this->aprovado) {
            return true; // Already approved
        }

        $stmt = $this->db->prepare("
            UPDATE medicos_parceiros SET 
                aprovado = TRUE,
                data_aprovacao = NOW(),
                aprovado_por = ?
            WHERE id = ?
        ");

        return $stmt->execute([$adminId, $this->id]);
    }

    /**
     * Reject this doctor (admin only)
     */
    public function reject(int $adminId, string $reason): bool {
        $stmt = $this->db->prepare("
            UPDATE medicos_parceiros SET 
                aprovado = FALSE,
                motivo_rejeicao = ?,
                data_aprovacao = NOW(),
                aprovado_por = ?
            WHERE id = ?
        ");

        return $stmt->execute([$reason, $adminId, $this->id]);
    }

    /*****************************************************************
     * Validation Methods
     *****************************************************************/

    protected function validateDoctorData(array $data): void {
        $errors = [];

        if (empty($data['nome'])) {
            $errors['nome'] = 'Nome é obrigatório';
        }

        if (empty($data['crm'])) {
            $errors['crm'] = 'CRM é obrigatório';
        }

        if (empty($data['uf_crm']) || strlen($data['uf_crm']) != 2) {
            $errors['uf_crm'] = 'UF do CRM é obrigatória (2 caracteres)';
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'E-mail inválido';
        }

        if (!empty($errors)) {
            throw new DoctorException('Dados de médico inválidos', $errors);
        }
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

    public function getNome(): string {
        return $this->nome;
    }

    public function setNome(string $nome): void {
        $this->nome = $nome;
    }

    public function getCrm(): string {
        return $this->crm;
    }

    public function getCrmCompleto(): string {
        return $this->crm . '/' . $this->uf_crm;
    }

    public function setCrm(string $crm, string $uf): void {
        $this->crm = $crm;
        $this->uf_crm = $uf;
    }

    public function getEspecialidade(): ?string {
        return $this->especialidade;
    }

    public function setEspecialidade(?string $especialidade): void {
        $this->especialidade = $especialidade;
    }

    public function getEmail(): ?string {
        return $this->email;
    }

    public function setEmail(?string $email): void {
        $this->email = $email;
    }

    public function getTelefone(): ?string {
        return $this->telefone;
    }

    public function setTelefone(?string $telefone): void {
        $this->telefone = $telefone;
    }

    public function isAprovado(): bool {
        return (bool)$this->aprovado;
    }

    public function getDataAprovacao(): ?string {
        return $this->data_aprovacao;
    }

    public function getAssociatedVendor(): Vendor {
        return Vendor::createFromUser(User::findById($this->vendedor_id));
    }
}