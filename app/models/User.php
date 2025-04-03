<?php
namespace App\Models;

use PDO;
use App\Lib\Database;
use App\Lib\Exceptions\ValidationException;

class User {
    // Database connection
    protected $db;

    // User properties
    protected $id;
    protected $nome;
    protected $email;
    protected $senha_hash;
    protected $tipo;
    protected $cpf_cnpj;
    protected $telefone;
    protected $endereco_cep;
    protected $endereco_logradouro;
    protected $endereco_numero;
    protected $endereco_complemento;
    protected $endereco_cidade;
    protected $endereco_estado;
    protected $data_criacao;
    protected $ativo;
    protected $token_ativacao;
    protected $token_reset_senha;
    protected $data_ultimo_login;
    protected $ip_ultimo_login;

    // User types
    const TYPE_ADMIN = 'admin';
    const TYPE_VENDOR = 'vendedor';
    const TYPE_CLIENT = 'cliente';

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /*****************************************************************
     * CRUD Methods
     *****************************************************************/

    /**
     * Create a new user in database
     */
    public function create(array $data): bool {
        $this->validateUserData($data);

        $stmt = $this->db->prepare("
            INSERT INTO usuarios (
                nome, email, senha_hash, tipo, cpf_cnpj, telefone,
                endereco_cep, endereco_logradouro, endereco_numero,
                endereco_complemento, endereco_cidade, endereco_estado,
                ativo, token_ativacao
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $this->hashPassword($data['senha']);

        return $stmt->execute([
            $data['nome'],
            $data['email'],
            $this->senha_hash,
            $data['tipo'],
            $data['cpf_cnpj'] ?? null,
            $data['telefone'] ?? null,
            $data['endereco_cep'] ?? null,
            $data['endereco_logradouro'] ?? null,
            $data['endereco_numero'] ?? null,
            $data['endereco_complemento'] ?? null,
            $data['endereco_cidade'] ?? null,
            $data['endereco_estado'] ?? null,
            $data['tipo'] === self::TYPE_ADMIN ? 1 : 0, // Admins are auto-activated
            $this->generateActivationToken()
        ]);
    }

    /**
     * Update user information
     */
    public function update(array $data): bool {
        $this->validateUserData($data, false);

        $sql = "UPDATE usuarios SET 
                nome = ?, email = ?, cpf_cnpj = ?, telefone = ?,
                endereco_cep = ?, endereco_logradouro = ?, endereco_numero = ?,
                endereco_complemento = ?, endereco_cidade = ?, endereco_estado = ?
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $data['nome'],
            $data['email'],
            $data['cpf_cnpj'] ?? null,
            $data['telefone'] ?? null,
            $data['endereco_cep'] ?? null,
            $data['endereco_logradouro'] ?? null,
            $data['endereco_numero'] ?? null,
            $data['endereco_complemento'] ?? null,
            $data['endereco_cidade'] ?? null,
            $data['endereco_estado'] ?? null,
            $this->id
        ]);
    }

    /**
     * Delete user from database
     */
    public function delete(): bool {
        $stmt = $this->db->prepare("DELETE FROM usuarios WHERE id = ?");
        return $stmt->execute([$this->id]);
    }

    /*****************************************************************
     * Authentication Methods
     *****************************************************************/

    /**
     * Authenticate user by email and password
     */
    public static function authenticate(string $email, string $password): ?User {
        $user = self::findByEmail($email);

        if ($user && $user->verifyPassword($password)) {
            $user->updateLastLogin();
            return $user;
        }

        return null;
    }

    /**
     * Verify if password matches hash
     */
    public function verifyPassword(string $password): bool {
        return password_verify($password, $this->senha_hash);
    }

    /**
     * Update last login info
     */
    public function updateLastLogin(): bool {
        $stmt = $this->db->prepare("
            UPDATE usuarios SET 
                data_ultimo_login = NOW(),
                ip_ultimo_login = ?
            WHERE id = ?
        ");
        
        $this->ip_ultimo_login = $_SERVER['REMOTE_ADDR'] ?? null;
        
        return $stmt->execute([
            $this->ip_ultimo_login,
            $this->id
        ]);
    }

    /**
     * Activate user account
     */
    public function activate(string $token): bool {
        if ($this->ativo || $this->token_ativacao !== $token) {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE usuarios SET 
                ativo = TRUE,
                token_ativacao = NULL
            WHERE id = ?
        ");

        return $stmt->execute([$this->id]);
    }

    /*****************************************************************
     * Password Recovery Methods
     *****************************************************************/

    /**
     * Generate and save password reset token
     */
    public function generatePasswordResetToken(): string {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt = $this->db->prepare("
            UPDATE usuarios SET 
                token_reset_senha = ?,
                token_reset_expira = ?
            WHERE id = ?
        ");

        $stmt->execute([$token, $expires, $this->id]);

        return $token;
    }

    /**
     * Reset user password with token
     */
    public static function resetPassword(string $token, string $newPassword): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT id FROM usuarios 
            WHERE token_reset_senha = ? 
            AND token_reset_expira > NOW()
        ");
        $stmt->execute([$token]);
        $userId = $stmt->fetchColumn();

        if (!$userId) {
            return false;
        }

        $user = self::findById($userId);
        $user->setPassword($newPassword);

        $stmt = $db->prepare("
            UPDATE usuarios SET 
                senha_hash = ?,
                token_reset_senha = NULL,
                token_reset_expira = NULL
            WHERE id = ?
        ");

        return $stmt->execute([$user->senha_hash, $userId]);
    }

    /*****************************************************************
     * Find Methods
     *****************************************************************/

    /**
     * Find user by ID
     */
    public static function findById(int $id): ?User {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetchObject(__CLASS__);
    }

    /**
     * Find user by email
     */
    public static function findByEmail(string $email): ?User {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetchObject(__CLASS__);
    }

    /**
     * Find user by CPF/CNPJ
     */
    public static function findByCpfCnpj(string $cpfCnpj): ?User {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE cpf_cnpj = ?");
        $stmt->execute([$cpfCnpj]);
        return $stmt->fetchObject(__CLASS__);
    }

    /**
     * Find all users by type
     */
    public static function findAllByType(string $type, int $limit = 100, int $offset = 0): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT * FROM usuarios 
            WHERE tipo = ? 
            ORDER BY nome ASC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$type, $limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_CLASS, __CLASS__);
    }

    /*****************************************************************
     * Helper Methods
     *****************************************************************/

    /**
     * Validate user data before create/update
     */
    protected function validateUserData(array $data, bool $isNew = true): void {
        $errors = [];

        // Required fields
        if (empty($data['nome'])) {
            $errors['nome'] = 'Nome é obrigatório';
        }

        if (empty($data['email'])) {
            $errors['email'] = 'E-mail é obrigatório';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'E-mail inválido';
        }

        if ($isNew && empty($data['senha'])) {
            $errors['senha'] = 'Senha é obrigatória';
        } elseif ($isNew && strlen($data['senha']) < 8) {
            $errors['senha'] = 'Senha deve ter no mínimo 8 caracteres';
        }

        if (empty($data['tipo']) || !in_array($data['tipo'], [self::TYPE_ADMIN, self::TYPE_VENDOR, self::TYPE_CLIENT])) {
            $errors['tipo'] = 'Tipo de usuário inválido';
        }

        // Unique fields
        if ($isNew || $data['email'] !== $this->email) {
            if (self::findByEmail($data['email'])) {
                $errors['email'] = 'E-mail já está em uso';
            }
        }

        if (!empty($data['cpf_cnpj']) && ($isNew || $data['cpf_cnpj'] !== $this->cpf_cnpj)) {
            if (self::findByCpfCnpj($data['cpf_cnpj'])) {
                $errors['cpf_cnpj'] = 'CPF/CNPJ já está em uso';
            }
        }

        if (!empty($errors)) {
            throw new ValidationException('Dados de usuário inválidos', $errors);
        }
    }

    /**
     * Hash the user password
     */
    protected function hashPassword(string $password): void {
        $this->senha_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Generate account activation token
     */
    protected function generateActivationToken(): string {
        $this->token_ativacao = bin2hex(random_bytes(32));
        return $this->token_ativacao;
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

    public function getEmail(): string {
        return $this->email;
    }

    public function setEmail(string $email): void {
        $this->email = $email;
    }

    public function getTipo(): string {
        return $this->tipo;
    }

    public function setTipo(string $tipo): void {
        $this->tipo = $tipo;
    }

    public function getCpfCnpj(): ?string {
        return $this->cpf_cnpj;
    }

    public function setCpfCnpj(?string $cpf_cnpj): void {
        $this->cpf_cnpj = $cpf_cnpj;
    }

    public function getTelefone(): ?string {
        return $this->telefone;
    }

    public function setTelefone(?string $telefone): void {
        $this->telefone = $telefone;
    }

    public function getEnderecoCompleto(): string {
        return implode(', ', array_filter([
            $this->endereco_logradouro,
            $this->endereco_numero,
            $this->endereco_complemento,
            $this->endereco_cidade,
            $this->endereco_estado
        ]));
    }

    public function isAtivo(): bool {
        return (bool)$this->ativo;
    }

    public function setAtivo(bool $ativo): void {
        $this->ativo = $ativo;
    }

    public function setPassword(string $password): void {
        $this->hashPassword($password);
    }

    public function isAdmin(): bool {
        return $this->tipo === self::TYPE_ADMIN;
    }

    public function isVendor(): bool {
        return $this->tipo === self::TYPE_VENDOR;
    }

    public function isClient(): bool {
        return $this->tipo === self::TYPE_CLIENT;
    }
}