<?php
namespace App\Models;

use PDO;
use App\Lib\Database;
use App\Lib\Exceptions\AnvisaException;

class AnvisaApproval {
    // Approval properties
    protected $id;
    protected $cliente_id;
    protected $numero_registro;
    protected $arquivo_path;
    protected $data_validade;
    protected $aprovado;
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
     * Create a new ANVISA approval record
     */
    public function create(array $data): bool {
        $this->validateApprovalData($data);

        $stmt = $this->db->prepare("
            INSERT INTO liberacoes_anvisa (
                cliente_id, numero_registro, arquivo_path, data_validade
            ) VALUES (?, ?, ?, ?)
        ");

        return $stmt->execute([
            $data['cliente_id'],
            $data['numero_registro'],
            $data['arquivo_path'],
            $data['data_validade']
        ]);
    }

    /**
     * Approve ANVISA document (admin only)
     */
    public function approve(int $adminId): bool {
        if ($this->aprovado) {
            return true; // Already approved
        }

        // Check if approval is still valid
        if (strtotime($this->data_validade) < time()) {
            throw new AnvisaException('Documento ANVISA expirado');
        }

        $stmt = $this->db->prepare("
            UPDATE liberacoes_anvisa SET 
                aprovado = TRUE,
                data_aprovacao = NOW(),
                aprovado_por = ?,
                motivo_rejeicao = NULL
            WHERE id = ?
        ");

        return $stmt->execute([$adminId, $this->id]);
    }

    /**
     * Reject ANVISA document (admin only)
     */
    public function reject(int $adminId, string $reason): bool {
        $stmt = $this->db->prepare("
            UPDATE liberacoes_anvisa SET 
                aprovado = FALSE,
                data_aprovacao = NOW(),
                aprovado_por = ?,
                motivo_rejeicao = ?
            WHERE id = ?
        ");

        return $stmt->execute([$adminId, $reason, $this->id]);
    }

    /**
     * Renew ANVISA approval
     */
    public function renew(array $data): bool {
        $this->validateApprovalData($data);

        $this->db->beginTransaction();

        try {
            // Archive current approval
            $stmt = $this->db->prepare("
                UPDATE liberacoes_anvisa SET 
                    ativo = FALSE
                WHERE id = ?
            ");
            $stmt->execute([$this->id]);

            // Create new approval
            $stmt = $this->db->prepare("
                INSERT INTO liberacoes_anvisa (
                    cliente_id, numero_registro, arquivo_path, data_validade
                ) VALUES (?, ?, ?, ?)
            ");
            $result = $stmt->execute([
                $this->cliente_id,
                $data['numero_registro'],
                $data['arquivo_path'],
                $data['data_validade']
            ]);

            $this->db->commit();
            return $result;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw new AnvisaException('Erro ao renovar aprovação ANVISA: ' . $e->getMessage());
        }
    }

    /*****************************************************************
     * Find Methods
     *****************************************************************/

    /**
     * Find approval by ID
     */
    public static function findById(int $id): ?AnvisaApproval {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT * FROM liberacoes_anvisa 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetchObject(__CLASS__);
    }

    /**
     * Find current approval for client
     */
    public static function findCurrentByClient(int $clientId): ?AnvisaApproval {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT * FROM liberacoes_anvisa 
            WHERE cliente_id = ? 
            ORDER BY data_validade DESC 
            LIMIT 1
        ");
        $stmt->execute([$clientId]);
        return $stmt->fetchObject(__CLASS__);
    }

    /**
     * Find all approvals for client
     */
    public static function findAllByClient(int $clientId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT * FROM liberacoes_anvisa 
            WHERE cliente_id = ?
            ORDER BY data_validade DESC
        ");
        $stmt->execute([$clientId]);
        return $stmt->fetchAll(PDO::FETCH_CLASS, __CLASS__);
    }

    /**
     * Find pending approvals
     */
    public static function findPendingApprovals(): array {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT l.*, u.nome as cliente_nome
            FROM liberacoes_anvisa l
            JOIN clientes c ON l.cliente_id = c.usuario_id
            JOIN usuarios u ON c.usuario_id = u.id
            WHERE l.aprovado IS NULL
            ORDER BY l.data_validade ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_CLASS, __CLASS__);
    }

    /**
     * Find expired approvals
     */
    public static function findExpiredApprovals(): array {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT l.*, u.nome as cliente_nome
            FROM liberacoes_anvisa l
            JOIN clientes c ON l.cliente_id = c.usuario_id
            JOIN usuarios u ON c.usuario_id = u.id
            WHERE l.data_validade < NOW()
            AND l.aprovado = TRUE
            ORDER BY l.data_validade DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_CLASS, __CLASS__);
    }

    /*****************************************************************
     * Validation Methods
     *****************************************************************/

    protected function validateApprovalData(array $data): void {
        $errors = [];

        if (empty($data['cliente_id'])) {
            $errors['cliente_id'] = 'ID do cliente é obrigatório';
        }

        if (empty($data['numero_registro'])) {
            $errors['numero_registro'] = 'Número de registro ANVISA é obrigatório';
        }

        if (empty($data['arquivo_path'])) {
            $errors['arquivo_path'] = 'Documento digitalizado é obrigatório';
        }

        if (empty($data['data_validade']) || strtotime($data['data_validade']) < time()) {
            $errors['data_validade'] = 'Data de validade inválida ou expirada';
        }

        if (!empty($errors)) {
            throw new AnvisaException('Dados de aprovação ANVISA inválidos', $errors);
        }
    }

    /*****************************************************************
     * Status Check Methods
     *****************************************************************/

    /**
     * Check if approval is valid
     */
    public function isValid(): bool {
        return $this->aprovado && strtotime($this->data_validade) > time();
    }

    /**
     * Check if approval is pending
     */
    public function isPending(): bool {
        return $this->aprovado === null;
    }

    /**
     * Check if approval is expired
     */
    public function isExpired(): bool {
        return strtotime($this->data_validade) < time();
    }

    /*****************************************************************
     * Getters and Setters
     *****************************************************************/

    public function getId(): ?int {
        return $this->id;
    }

    public function getClienteId(): int {
        return $this->cliente_id;
    }

    public function getNumeroRegistro(): string {
        return $this->numero_registro;
    }

    public function getArquivoPath(): string {
        return $this->arquivo_path;
    }

    public function getDataValidade(): string {
        return $this->data_validade;
    }

    public function isAprovado(): ?bool {
        return $this->aprovado;
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

    public function getClient(): Client {
        return Client::createFromUser(User::findById($this->cliente_id));
    }

    public function getApprover(): ?User {
        if (!$this->aprovado_por) {
            return null;
        }
        return User::findById($this->aprovado_por);
    }
}