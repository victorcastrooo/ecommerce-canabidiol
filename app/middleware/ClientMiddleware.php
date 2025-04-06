<?php
namespace App\Middleware;

use App\Lib\Database;
use App\Lib\Auth;
use Carbon\Carbon;
/**
 * ClientMiddleware - Restricts access to client-only routes
 * 
 * Verifies user is an authenticated client with valid account status
 * and handles age verification for cannabis purchases.
 */
class ClientMiddleware {
    private $auth;
    private $redirectUrl = '/auth/login';
    private $ageVerificationUrl = '/client/verify-age';
    private $unauthorizedUrl = '/errors/403';

    public function __construct(Auth $auth) {
        $this->auth = $auth;
    }

    /**
     * Middleware handler - Basic client verification
     */
    public function handle() {
        // Check authentication
        if (!$this->auth->isLoggedIn()) {
            $this->redirectWithMessage(
                $this->redirectUrl,
                'Por favor, faça login para acessar esta área',
                'error',
                true
            );
            return false;
        }

        // Check client role
        if (!$this->auth->hasRole('client')) {
            $this->redirectWithMessage(
                $this->unauthorizedUrl,
                'Acesso restrito a clientes',
                'error'
            );
            return false;
        }

        $user = $this->auth->getUser();

        // Check account status
        if (!$user->ativo) {
            $this->auth->logout();
            $this->redirectWithMessage(
                $this->redirectUrl,
                'Sua conta está inativa. Entre em contato com o suporte.',
                'error'
            );
            return false;
        }

        // Session security
        if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR'] || 
            $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            $this->auth->logout();
            $this->redirectWithMessage(
                $this->redirectUrl,
                'Sessão inválida detectada. Por favor, faça login novamente.',
                'error'
            );
            return false;
        }

        return true;
    }

    /**
     * Age verification check (18+)
     */
    public function verifyAge() {
        if (!$this->handle()) {
            return false;
        }

        $user = $this->auth->getUser();

        // Check if age is verified in session
        if (isset($_SESSION['age_verified'])) {
            return true;
        }

        // Check birthdate from database
        if (!empty($user->client_birthdate)) {
            try {
                $birthdate = new \DateTime($user->client_birthdate); // Adicionando a barra invertida
                $today = new \DateTime();
                $age = $today->diff($birthdate)->y;
        
                if ($age >= 18) {
                    $_SESSION['age_verified'] = true;
                    return true;
                }
            } catch (\Exception $e) {
                echo "Erro ao processar a data: " . $e->getMessage();
            }
        }

        $this->redirectWithMessage(
            $this->ageVerificationUrl,
            'Verificação de idade necessária para produtos de canabidiol',
            'warning',
            true
        );
        return false;
    }

    /**
     * Prescription requirement check
     */
    public function needsPrescription() {
        if (!$this->verifyAge()) {
            return false;
        }

        $user = $this->auth->getUser();

        // Check if prescription is on file
        $db = new Database();
        $db->query("SELECT COUNT(*) as count FROM receitas WHERE cliente_id = :client_id AND aprovada = 1");
        $db->bind(':client_id', $user->id);
        $result = $db->single();

        if ($result->count > 0) {
            return true;
        }

        $this->redirectWithMessage(
            '/client/prescriptions/upload',
            'Receita médica necessária para este produto',
            'warning',
            true
        );
        return false;
    }

    /**
     * ANVISA approval check
     */
    public function needsAnvisaApproval() {
        if (!$this->verifyAge()) {
            return false;
        }

        $user = $this->auth->getUser();

        // Check ANVISA approval
        $db = new Database();
        $db->query("SELECT COUNT(*) as count FROM liberacoes_anvisa WHERE cliente_id = :client_id AND aprovado = 1 AND data_validade > NOW()");
        $db->bind(':client_id', $user->id);
        $result = $db->single();

        if ($result->count > 0) {
            return true;
        }

        $this->redirectWithMessage(
            '/client/anvisa/upload',
            'Aprovação ANVISA necessária para este produto',
            'warning',
            true
        );
        return false;
    }

    private function redirectWithMessage($url, $message, $type = 'error', $storeIntended = false) {
        if (!isset($_SESSION)) {
            session_start();
        }

        $_SESSION['flash_messages'] = $_SESSION['flash_messages'] ?? [];
        $_SESSION['flash_messages'][] = [
            'type' => $type,
            'message' => $message
        ];

        if ($storeIntended) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
        }

        header('Location: ' . $url);
        exit;
    }

    public static function protect() {
        return function() {
            $auth = new Auth(new Database());
            $middleware = new self($auth);
            return $middleware->handle();
        };
    }

    public static function ageVerification() {
        return function() {
            $auth = new Auth(new Database());
            $middleware = new self($auth);
            return $middleware->verifyAge();
        };
    }

    public static function prescriptionRequired() {
        return function() {
            $auth = new Auth(new Database());
            $middleware = new self($auth);
            return $middleware->needsPrescription();
        };
    }

    public static function anvisaApprovalRequired() {
        return function() {
            $auth = new Auth(new Database());
            $middleware = new self($auth);
            return $middleware->needsAnvisaApproval();
        };
    }
}