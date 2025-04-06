<?php
namespace App\Middleware;

use App\Lib\Database;
use App\Lib\Auth;
/**
 * AuthMiddleware - Authentication Middleware
 * 
 * Verifies user authentication and handles role-based access control
 * for protected routes in the Canabidiol Commerce platform.
 */
class AuthMiddleware {
    private $auth;
    private $allowedRoles = [];
    private $redirectTo;
    private $loginRoute = '/auth/login';

    public function __construct(Auth $auth, $options = []) {
        $this->auth = $auth;
        $this->allowedRoles = $options['roles'] ?? [];
        $this->redirectTo = $options['redirect'] ?? $this->loginRoute;
    }

    /**
     * Middleware handler
     */
    public function handle() {
        // Check if user is authenticated
        if (!$this->auth->isLoggedIn()) {
            $this->redirectWithMessage(
                $this->redirectTo,
                'Você precisa estar autenticado para acessar esta página',
                'error'
            );
            return false;
        }

        // Check if user has required role
        if (!empty($this->allowedRoles)) {
            $hasRole = false;
            
            foreach ($this->allowedRoles as $role) {
                if ($this->auth->hasRole($role)) {
                    $hasRole = true;
                    break;
                }
            }

            if (!$hasRole) {
                $this->redirectWithMessage(
                    '/errors/403',
                    'Acesso não autorizado',
                    'error'
                );
                return false;
            }
        }

        // Check if vendor is approved (if applicable)
        if ($this->auth->hasRole('vendor')) {
            $user = $this->auth->getUser();
            if (!$user->vendor_approved) {
                $this->redirectWithMessage(
                    '/vendor/pending-approval',
                    'Seu cadastro como vendedor está aguardando aprovação',
                    'warning'
                );
                return false;
            }
        }

        // Check if account is active
        $user = $this->auth->getUser();
        if (!$user->ativo) {
            $this->auth->logout();
            $this->redirectWithMessage(
                $this->loginRoute,
                'Sua conta está inativa. Entre em contato com o suporte.',
                'error'
            );
            return false;
        }

        // Check session security
        if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR'] || 
            $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            $this->auth->logout();
            $this->redirectWithMessage(
                $this->loginRoute,
                'Sessão inválida detectada. Por favor, faça login novamente.',
                'error'
            );
            return false;
        }

        return true;
    }

    /**
     * Redirect with flash message
     */
    private function redirectWithMessage($url, $message, $type = 'error') {
        if (!isset($_SESSION)) {
            session_start();
        }

        $_SESSION['flash_messages'] = $_SESSION['flash_messages'] ?? [];
        $_SESSION['flash_messages'][] = [
            'type' => $type,
            'message' => $message
        ];

        // Store intended URL for redirect after login
        if ($url === $this->loginRoute) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
        }

        header('Location: ' . $url);
        exit;
    }

    /**
     * Static helper to create middleware instance
     */
    public static function protect($options = []) {
        return function() use ($options) {
            $auth = new Auth(new Database());
            $middleware = new self($auth, $options);
            return $middleware->handle();
        };
    }
}