<?php
namespace App\Middleware;

use App\Lib\Database;
use App\Lib\Auth;
/**
 * VendorMiddleware - Restricts access to vendor-only routes
 * 
 * Verifies user has vendor privileges and is approved before granting access
 */
class VendorMiddleware {
    private $auth;
    private $redirectUrl = '/vendor/login';
    private $pendingUrl = '/vendor/pending';
    private $unauthorizedUrl = '/errors/403';

    public function __construct(Auth $auth) {
        $this->auth = $auth;
    }

    /**
     * Middleware handler
     */
    public function handle() {
        // Check if user is authenticated
        if (!$this->auth->isLoggedIn()) {
            $this->redirectWithMessage(
                $this->redirectUrl,
                'Você precisa estar autenticado como vendedor',
                'error',
                true
            );
            return false;
        }

        // Check if user has vendor role
        if (!$this->auth->hasRole('vendor')) {
            $this->redirectWithMessage(
                $this->unauthorizedUrl,
                'Acesso restrito a vendedores cadastrados',
                'error'
            );
            return false;
        }

        $user = $this->auth->getUser();

        // Check if vendor is approved
        if (!$user->vendor_approved) {
            $this->redirectWithMessage(
                $this->pendingUrl,
                'Seu cadastro está em análise pela nossa equipe',
                'warning'
            );
            return false;
        }

        // Verify vendor account is active
        if (!$user->ativo) {
            $this->auth->logout();
            $this->redirectWithMessage(
                $this->redirectUrl,
                'Sua conta de vendedor está inativa',
                'error'
            );
            return false;
        }

        // Session security check
        if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR'] || 
            $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            $this->auth->logout();
            $this->redirectWithMessage(
                $this->redirectUrl,
                'Sessão inválida detectada',
                'error'
            );
            return false;
        }

        return true;
    }

    /**
     * Redirect with flash message
     */
    private function redirectWithMessage($url, $message, $type = 'error', $storeIntended = false) {
        if (!isset($_SESSION)) {
            session_start();
        }

        $_SESSION['flash_messages'] = $_SESSION['flash_messages'] ?? [];
        $_SESSION['flash_messages'][] = [
            'type' => $type,
            'message' => $message
        ];

        // Store intended URL for redirect after login
        if ($storeIntended) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
        }

        header('Location: ' . $url);
        exit;
    }

    /**
     * Static helper to create middleware instance
     */
    public static function protect() {
        return function() {
            $auth = new Auth(new Database());
            $middleware = new self($auth);
            return $middleware->handle();
        };
    }

    /**
     * Check if vendor has at least one approved doctor
     */
    public function hasApprovedDoctors() {
        if (!$this->handle()) return false;

        $user = $this->auth->getUser();
        $db = new Database();
        
        $db->query("SELECT COUNT(*) as count FROM medicos_parceiros WHERE vendedor_id = :vendor_id AND aprovado = 1");
        $db->bind(':vendor_id', $user->id);
        $result = $db->single();

        if ($result->count < 1) {
            $this->redirectWithMessage(
                '/vendor/doctors',
                'Você precisa ter pelo menos um médico aprovado para acessar esta área',
                'warning'
            );
            return false;
        }

        return true;
    }
}