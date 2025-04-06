<?php
namespace App\Lib;

use PDOException;
use PDO;
use Exception;
/**
 * Auth Class - Authentication and Authorization System
 * 
 * Handles user authentication, session management, and role-based access control
 * for the Canabidiol Commerce platform.
 */
class Auth {
    private $db;
    private $user = null;
    private $sessionTimeout = 1800; // 30 minutes in seconds
    
    public function __construct(Database $db) {
        $this->db = $db;
        $this->startSession();
        $this->checkSession();
    }
    
    /**
     * Secure session start with HTTP-only and Strict cookies
     */
    private function startSession() {
        session_set_cookie_params([
            'lifetime' => $this->sessionTimeout,
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'],
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        
        session_name('CBDCOMMERCE_SESSID');
        session_start();
        
        // Regenerate session ID periodically to prevent fixation
        if (!isset($_SESSION['last_regeneration'])) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 900) { // 15 minutes
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
    
    /**
     * Check session validity and timeout
     */
    private function checkSession() {
        if (isset($_SESSION['user_id'])) {
            // Check session timeout
            if (isset($_SESSION['last_activity']) && 
                (time() - $_SESSION['last_activity'] > $this->sessionTimeout)) {
                $this->logout();
                return;
            }
            
            $_SESSION['last_activity'] = time();
            
            // Load user data if not already loaded
            if ($this->user === null) {
                $this->loadUser($_SESSION['user_id']);
            }
        }
    }
    
    /**
     * Load user data from database
     */
    private function loadUser($userId) {
        $this->db->query("
            SELECT u.*, 
                   a.nivel_acesso AS admin_level,
                   v.razao_social AS vendor_name,
                   v.aprovado AS vendor_approved,
                   c.data_nascimento AS client_birthdate
            FROM usuarios u
            LEFT JOIN administradores a ON u.id = a.usuario_id
            LEFT JOIN vendedores v ON u.id = v.usuario_id
            LEFT JOIN clientes c ON u.id = c.usuario_id
            WHERE u.id = :id AND u.ativo = 1
        ");
        $this->db->bind(':id', $userId);
        $this->user = $this->db->single();
    }
    
    /**
     * Login user with email and password
     */
    public function login($email, $password) {
        // Find user by email
        $this->db->query("SELECT id, senha_hash FROM usuarios WHERE email = :email AND ativo = 1");
        $this->db->bind(':email', $email);
        $user = $this->db->single();
        
        if (!$user) {
            $this->logAttempt($email, false);
            return false;
        }
        
        // Verify password
        if (password_verify($password, $user->senha_hash)) {
            // Check if password needs rehashing
            if (password_needs_rehash($user->senha_hash, PASSWORD_DEFAULT)) {
                $this->updatePasswordHash($user->id, $password);
            }
            
            // Set session variables
            $_SESSION['user_id'] = $user->id;
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            $_SESSION['last_activity'] = time();
            
            // Load user data
            $this->loadUser($user->id);
            
            // Log successful login
            $this->logAttempt($email, true);
            $this->logActivity('login');
            
            return true;
        }
        
        $this->logAttempt($email, false);
        return false;
    }
    
    /**
     * Logout current user
     */
    public function logout() {
        if ($this->isLoggedIn()) {
            $this->logActivity('logout');
        }
        
        // Clear session data
        $_SESSION = [];
        
        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        session_destroy();
        $this->user = null;
    }
    
    /**
     * Register a new user
     */
    public function register($userData, $userType = 'client') {
        try {
            $this->db->beginTransaction();
            
            // Hash password
            $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            // Insert base user
            $this->db->query("
                INSERT INTO usuarios 
                (nome, email, senha_hash, tipo, cpf_cnpj, telefone, 
                 endereco_cep, endereco_logradouro, endereco_numero, 
                 endereco_complemento, endereco_cidade, endereco_estado, data_criacao)
                VALUES 
                (:name, :email, :password, :type, :doc, :phone, 
                 :cep, :street, :number, :complement, :city, :state, NOW())
            ");
            
            $this->db->bind(':name', $userData['name']);
            $this->db->bind(':email', $userData['email']);
            $this->db->bind(':password', $hashedPassword);
            $this->db->bind(':type', $userType);
            $this->db->bind(':doc', $userData['cpf_cnpj']);
            $this->db->bind(':phone', $userData['phone']);
            $this->db->bind(':cep', $userData['cep']);
            $this->db->bind(':street', $userData['street']);
            $this->db->bind(':number', $userData['number']);
            $this->db->bind(':complement', $userData['complement'] ?? null);
            $this->db->bind(':city', $userData['city']);
            $this->db->bind(':state', $userData['state']);
            
            $this->db->execute();
            $userId = $this->db->lastInsertId();
            
            // Insert type-specific data
            switch ($userType) {
                case 'client':
                    $this->db->query("
                        INSERT INTO clientes 
                        (usuario_id, data_nascimento, genero)
                        VALUES 
                        (:user_id, :birthdate, :gender)
                    ");
                    $this->db->bind(':user_id', $userId);
                    $this->db->bind(':birthdate', $userData['birthdate']);
                    $this->db->bind(':gender', $userData['gender']);
                    break;
                    
                case 'vendor':
                    $this->db->query("
                        INSERT INTO vendedores 
                        (usuario_id, razao_social, inscricao_estadual, 
                         banco_nome, banco_agencia, banco_conta, banco_tipo_conta, 
                         banco_titular, banco_cpf_titular, comissao_percentual)
                        VALUES 
                        (:user_id, :company_name, :state_registration, 
                         :bank_name, :bank_agency, :bank_account, :bank_account_type,
                         :bank_holder, :bank_holder_cpf, :commission_rate)
                    ");
                    // Bind vendor-specific parameters...
                    break;
                    
                case 'doctor':
                    // Doctor registration handled separately with vendor association
                    throw new Exception("Doctor registration requires vendor association");
            }
            
            $this->db->execute();
            $this->db->commit();
            
            // Generate activation token
            $token = bin2hex(random_bytes(32));
            $this->setActivationToken($userId, $token);
            
            return $userId;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Verify session security
        if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR'] || 
            $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            $this->logout();
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if user has a specific role
     */
    public function hasRole($role) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        switch ($role) {
            case 'admin':
                return isset($this->user->admin_level);
            case 'vendor':
                return isset($this->user->vendor_name) && $this->user->vendor_approved;
            case 'doctor':
                // Additional check would be needed for doctors
                return false;
            default: // client
                return $this->user->tipo === 'client';
        }
    }
    
    /**
     * Get current user data
     */
    public function getUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        return $this->user;
    }
    
    /**
     * Generate and store password reset token
     */
    public function generatePasswordResetToken($email) {
        $this->db->query("SELECT id FROM usuarios WHERE email = :email AND ativo = 1");
        $this->db->bind(':email', $email);
        $user = $this->db->single();
        
        if (!$user) {
            return false;
        }
        
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $this->db->query("
            UPDATE usuarios 
            SET token_reset_senha = :token, 
                token_reset_expira = :expires 
            WHERE id = :id
        ");
        $this->db->bind(':token', $token);
        $this->db->bind(':expires', $expires);
        $this->db->bind(':id', $user->id);
        $this->db->execute();
        
        return $token;
    }
    
    /**
     * Validate password reset token
     */
    public function validatePasswordResetToken($token) {
        $this->db->query("
            SELECT id 
            FROM usuarios 
            WHERE token_reset_senha = :token 
              AND token_reset_expira > NOW()
              AND ativo = 1
        ");
        $this->db->bind(':token', $token);
        $user = $this->db->single();
        
        return $user ? $user->id : false;
    }
    
    /**
     * Reset user password with token
     */
    public function resetPassword($token, $newPassword) {
        $userId = $this->validatePasswordResetToken($token);
        
        if (!$userId) {
            return false;
        }
        
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $this->db->query("
            UPDATE usuarios 
            SET senha_hash = :password,
                token_reset_senha = NULL,
                token_reset_expira = NULL,
                data_ultimo_login = NOW(),
                ip_ultimo_login = :ip
            WHERE id = :id
        ");
        $this->db->bind(':password', $hashedPassword);
        $this->db->bind(':ip', $_SERVER['REMOTE_ADDR']);
        $this->db->bind(':id', $userId);
        $this->db->execute();
        
        return true;
    }
    
    /**
     * Update user password
     */
    public function updatePassword($userId, $currentPassword, $newPassword) {
        $this->db->query("SELECT senha_hash FROM usuarios WHERE id = :id AND ativo = 1");
        $this->db->bind(':id', $userId);
        $user = $this->db->single();
        
        if (!$user || !password_verify($currentPassword, $user->senha_hash)) {
            return false;
        }
        
        return $this->updatePasswordHash($userId, $newPassword);
    }
    
    /**
     * Directly update password hash
     */
    private function updatePasswordHash($userId, $password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $this->db->query("
            UPDATE usuarios 
            SET senha_hash = :password 
            WHERE id = :id
        ");
        $this->db->bind(':password', $hashedPassword);
        $this->db->bind(':id', $userId);
        return $this->db->execute();
    }
    
    /**
     * Log security-related activities
     */
    private function logActivity($action, $details = null) {
        if (!$this->isLoggedIn()) {
            return;
        }
        
        $this->db->query("
            INSERT INTO logs_ativades 
            (usuario_id, acao, tabela_afetada, registro_id, dados_anteriores, dados_novos, ip, user_agent)
            VALUES 
            (:user_id, :action, :table, :record_id, :old_data, :new_data, :ip, :user_agent)
        ");
        
        $this->db->bind(':user_id', $_SESSION['user_id']);
        $this->db->bind(':action', $action);
        $this->db->bind(':table', $details['table'] ?? null);
        $this->db->bind(':record_id', $details['record_id'] ?? null);
        $this->db->bind(':old_data', $details['old_data'] ?? null);
        $this->db->bind(':new_data', $details['new_data'] ?? null);
        $this->db->bind(':ip', $_SERVER['REMOTE_ADDR']);
        $this->db->bind(':user_agent', $_SERVER['HTTP_USER_AGENT']);
        
        $this->db->execute();
    }
    
    /**
     * Log login attempts
     */
    private function logAttempt($email, $success) {
        $this->db->query("
            INSERT INTO login_attempts 
            (email, success, ip_address, user_agent, attempt_time)
            VALUES 
            (:email, :success, :ip, :user_agent, NOW())
        ");
        
        $this->db->bind(':email', $email);
        $this->db->bind(':success', $success ? 1 : 0);
        $this->db->bind(':ip', $_SERVER['REMOTE_ADDR']);
        $this->db->bind(':user_agent', $_SERVER['HTTP_USER_AGENT']);
        
        $this->db->execute();
    }
    
    /**
     * Set account activation token
     */
    private function setActivationToken($userId, $token) {
        $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $this->db->query("
            UPDATE usuarios 
            SET token_ativacao = :token,
                token_ativacao_expira = :expires
            WHERE id = :id
        ");
        $this->db->bind(':token', $token);
        $this->db->bind(':expires', $expires);
        $this->db->bind(':id', $userId);
        $this->db->execute();
    }
    
    /**
     * Activate account with token
     */
    public function activateAccount($token) {
        $this->db->query("
            SELECT id 
            FROM usuarios 
            WHERE token_ativacao = :token 
              AND token_ativacao_expira > NOW()
              AND ativo = 0
        ");
        $this->db->bind(':token', $token);
        $user = $this->db->single();
        
        if (!$user) {
            return false;
        }
        
        $this->db->query("
            UPDATE usuarios 
            SET ativo = 1,
                token_ativacao = NULL,
                token_ativacao_expira = NULL
            WHERE id = :id
        ");
        $this->db->bind(':id', $user->id);
        $this->db->execute();
        
        return true;
    }
    
    /**
     * Middleware for role-based access control
     */
    public function middleware($requiredRole) {
        if (!$this->isLoggedIn()) {
            header('Location: /auth/login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
        
        if (!$this->hasRole($requiredRole)) {
            header('Location: /errors/403');
            exit;
        }
        
        return true;
    }
}