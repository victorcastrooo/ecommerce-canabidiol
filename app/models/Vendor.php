<?php
namespace App\Models;

use PDO;
use App\Lib\Database;
use App\Lib\Exceptions\VendorException;

class Vendor extends User {
    // Vendor properties
    protected $razao_social;
    protected $inscricao_estadual;
    protected $banco_nome;
    protected $banco_agencia;
    protected $banco_conta;
    protected $banco_tipo_conta;
    protected $banco_titular;
    protected $banco_cpf_titular;
    protected $comissao_percentual;
    protected $aprovado;
    protected $data_aprovacao;
    protected $aprovado_por;
    protected $motivo_rejeicao;

    // Bank account types
    const CONTA_CORRENTE = 'corrente';
    const CONTA_POUPANCA = 'poupanca';

    public function __construct() {
        parent::__construct();
    }

    /*****************************************************************
     * Vendor Registration Methods
     *****************************************************************/

    /**
     * Complete vendor registration with additional details
     */
    public function completeRegistration(array $data): bool {
        $this->validateVendorData($data);

        $stmt = $this->db->prepare("
            INSERT INTO vendedores (
                usuario_id, razao_social, inscricao_estadual,
                banco_nome, banco_agencia, banco_conta,
                banco_tipo_conta, banco_titular, banco_cpf_titular,
                comissao_percentual
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $this->id,
            $data['razao_social'],
            $data['inscricao_estadual'],
            $data['banco_nome'],
            $data['banco_agencia'],
            $data['banco_conta'],
            $data['banco_tipo_conta'],
            $data['banco_titular'],
            $data['banco_cpf_titular'],
            $data['comissao_percentual'] ?? 10.00 // Default commission
        ]);
    }

    /**
     * Check if vendor registration is complete
     */
    public function isRegistrationComplete(): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM vendedores 
            WHERE usuario_id = ?
        ");
        $stmt->execute([$this->id]);
        return (bool)$stmt->fetchColumn();
    }

    /*****************************************************************
     * Doctor Partner Methods
     *****************************************************************/

    /**
     * Register a new doctor partner
     */
    public function registerDoctor(array $data): bool {
        if (!$this->isApproved()) {
            throw new VendorException('Vendedor não aprovado');
        }

        $this->validateDoctorData($data);

        $stmt = $this->db->prepare("
            INSERT INTO medicos_parceiros (
                vendedor_id, nome, crm, uf_crm,
                especialidade, email, telefone
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $this->id,
            $data['nome'],
            $data['crm'],
            $data['uf_crm'],
            $data['especialidade'] ?? null,
            $data['email'] ?? null,
            $data['telefone'] ?? null
        ]);
    }

    /**
     * Get all registered doctors
     */
    public function getDoctors(bool $onlyApproved = true): array {
        $sql = "
            SELECT * FROM medicos_parceiros 
            WHERE vendedor_id = ?
        ";

        if ($onlyApproved) {
            $sql .= " AND aprovado = TRUE";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*****************************************************************
     * Commission Methods
     *****************************************************************/

    /**
     * Get all commissions
     */
    public function getCommissions(string $status = null): array {
        $sql = "
            SELECT c.*, p.codigo as pedido_codigo, p.total as pedido_total,
                   m.nome as medico_nome, m.crm as medico_crm
            FROM comissoes c
            JOIN pedidos p ON c.pedido_id = p.id
            LEFT JOIN medicos_parceiros m ON c.medico_id = m.id
            WHERE c.vendedor_id = ?
        ";

        $params = [$this->id];

        if ($status) {
            $sql .= " AND c.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY c.data_criacao DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get available balance for withdrawal
     */
    public function getAvailableBalance(): float {
        $stmt = $this->db->prepare("
            SELECT SUM(valor_comissao) 
            FROM comissoes 
            WHERE vendedor_id = ? AND status = 'disponivel'
        ");
        $stmt->execute([$this->id]);
        return (float)$stmt->fetchColumn();
    }

    /**
     * Request commission withdrawal
     */
    public function requestWithdrawal(): bool {
        $balance = $this->getAvailableBalance();

        if ($balance <= 0) {
            throw new VendorException('Saldo indisponível para saque');
        }

        $this->db->beginTransaction();

        try {
            // Create withdrawal request
            $stmt = $this->db->prepare("
                INSERT INTO saques_comissoes (
                    vendedor_id, valor_total, taxa_administrativa, valor_liquido,
                    status, metodo
                ) VALUES (?, ?, ?, ?, 'pendente', 'transferencia')
            ");

            $taxa = $balance * ($this->getTaxaAdministrativa() / 100);
            $liquido = $balance - $taxa;

            $stmt->execute([
                $this->id,
                $balance,
                $taxa,
                $liquido
            ]);

            $withdrawalId = $this->db->lastInsertId();

            // Mark commissions as requested
            $stmt = $this->db->prepare("
                UPDATE comissoes SET 
                    status = 'solicitado'
                WHERE vendedor_id = ? AND status = 'disponivel'
            ");
            $stmt->execute([$this->id]);

            // Link commissions to withdrawal
            $stmt = $this->db->prepare("
                INSERT INTO saques_comissoes_itens (saque_id, comissao_id)
                SELECT ?, id FROM comissoes 
                WHERE vendedor_id = ? AND status = 'solicitado'
            ");
            $stmt->execute([$withdrawalId, $this->id]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw new VendorException('Erro ao solicitar saque: ' . $e->getMessage());
        }
    }

    /**
     * Get withdrawal history
     */
    public function getWithdrawals(): array {
        $stmt = $this->db->prepare("
            SELECT * FROM saques_comissoes 
            WHERE vendedor_id = ?
            ORDER BY data_solicitacao DESC
        ");
        $stmt->execute([$this->id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*****************************************************************
     * Sales Methods
     *****************************************************************/

    /**
     * Get sales statistics
     */
    public function getSalesStatistics(): array {
        $stats = [];

        // Total sales
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count, SUM(total) as total
            FROM pedidos
            WHERE vendedor_id = ? AND status = 'entregue'
        ");
        $stmt->execute([$this->id]);
        $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC);

        // Sales by month
        $stmt = $this->db->prepare("
            SELECT 
                DATE_FORMAT(data_pedido, '%Y-%m') as month,
                COUNT(*) as count,
                SUM(total) as total
            FROM pedidos
            WHERE vendedor_id = ? AND status = 'entregue'
            GROUP BY DATE_FORMAT(data_pedido, '%Y-%m')
            ORDER BY month DESC
            LIMIT 6
        ");
        $stmt->execute([$this->id]);
        $stats['by_month'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Sales by doctor
        $stmt = $this->db->prepare("
            SELECT 
                m.nome as medico,
                COUNT(*) as count,
                SUM(p.total) as total,
                SUM(c.valor_comissao) as comissao
            FROM pedidos p
            JOIN comissoes c ON p.id = c.pedido_id
            LEFT JOIN medicos_parceiros m ON p.medico_id = m.id
            WHERE p.vendedor_id = ? AND p.status = 'entregue'
            GROUP BY p.medico_id
            ORDER BY total DESC
            LIMIT 5
        ");
        $stmt->execute([$this->id]);
        $stats['by_doctor'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $stats;
    }

    /*****************************************************************
     * Validation Methods
     *****************************************************************/

    protected function validateVendorData(array $data): void {
        $errors = [];

        if (empty($data['razao_social'])) {
            $errors['razao_social'] = 'Razão social é obrigatória';
        }

        if (empty($data['inscricao_estadual'])) {
            $errors['inscricao_estadual'] = 'Inscrição estadual é obrigatória';
        }

        if (empty($data['banco_nome'])) {
            $errors['banco_nome'] = 'Nome do banco é obrigatório';
        }

        if (empty($data['banco_agencia'])) {
            $errors['banco_agencia'] = 'Agência bancária é obrigatória';
        }

        if (empty($data['banco_conta'])) {
            $errors['banco_conta'] = 'Conta bancária é obrigatória';
        }

        if (empty($data['banco_tipo_conta']) || !in_array($data['banco_tipo_conta'], [self::CONTA_CORRENTE, self::CONTA_POUPANCA])) {
            $errors['banco_tipo_conta'] = 'Tipo de conta inválido';
        }

        if (empty($data['banco_titular'])) {
            $errors['banco_titular'] = 'Nome do titular é obrigatório';
        }

        if (empty($data['banco_cpf_titular'])) {
            $errors['banco_cpf_titular'] = 'CPF do titular é obrigatório';
        }

        if (!empty($errors)) {
            throw new VendorException('Dados de vendedor inválidos', $errors);
        }
    }

    protected function validateDoctorData(array $data): void {
        $errors = [];

        if (empty($data['nome'])) {
            $errors['nome'] = 'Nome do médico é obrigatório';
        }

        if (empty($data['crm'])) {
            $errors['crm'] = 'CRM é obrigatório';
        }

        if (empty($data['uf_crm'])) {
            $errors['uf_crm'] = 'UF do CRM é obrigatória';
        }

        if (!empty($errors)) {
            throw new VendorException('Dados de médico inválidos', $errors);
        }
    }

    /*****************************************************************
     * Getters and Setters
     *****************************************************************/

    public function isApproved(): bool {
        return (bool)$this->aprovado;
    }

    public function getComissaoPercentual(): float {
        return (float)$this->comissao_percentual;
    }

    public function setComissaoPercentual(float $percentual): void {
        $this->comissao_percentual = $percentual;
    }

    public function getTaxaAdministrativa(): float {
        // Could be fetched from system settings
        return 2.0; // Default 2%
    }

    public function getRazaoSocial(): string {
        return $this->razao_social;
    }

    public function getBankInfo(): array {
        return [
            'banco_nome' => $this->banco_nome,
            'banco_agencia' => $this->banco_agencia,
            'banco_conta' => $this->banco_conta,
            'banco_tipo_conta' => $this->banco_tipo_conta,
            'banco_titular' => $this->banco_titular,
            'banco_cpf_titular' => $this->banco_cpf_titular
        ];
    }

    /*****************************************************************
     * Factory Method
     *****************************************************************/

    public static function createFromUser(User $user): Vendor {
        if (!$user->isVendor()) {
            throw new VendorException('O usuário não é um vendedor');
        }

        $vendor = new self();
        $vendor->id = $user->getId();
        $vendor->nome = $user->getNome();
        $vendor->email = $user->getEmail();
        $vendor->tipo = $user->getTipo();

        // Load vendor-specific data
        $stmt = Database::getConnection()->prepare("
            SELECT * FROM vendedores WHERE usuario_id = ?
        ");
        $stmt->execute([$vendor->id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            $vendor->razao_social = $data['razao_social'];
            $vendor->comissao_percentual = $data['comissao_percentual'];
            $vendor->aprovado = $data['aprovado'];
            // ... set other properties
        }

        return $vendor;
    }
}