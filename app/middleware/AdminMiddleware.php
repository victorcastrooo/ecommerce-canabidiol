<?php

namespace App\Middleware;

use App\Lib\Database;
use App\Lib\Auth;

/**
 * AdminMiddleware - Restricts access to admin-only routes
 * 
 * Verifies user has admin privileges before granting access
 */
class AdminMiddleware {
    private $auth;
    private $redirectUrl = '/admin/login';
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
                'Acesso administrativo requer autenticação',
                'error',
                true
            );
            return false;
        }

        // Check if user has admin role
        if (!$this->auth->hasRole('admin')) {
            $this->redirectWithMessage(
                $this->unauthorizedUrl,
                'Acesso restrito a administradores',
                'error'
            );
            return false;
        }

        // Additional admin-specific checks
        $user = $this->auth->getUser();
        
        // Verify admin account is active
        if (!$user->ativo) {
            $this->auth->logout();
            $this->redirectWithMessage(
                $this->redirectUrl,
                'Sua conta de administrador está inativa',
                'error'
            );
            return false;
        }

        // Verify admin access level
        if ($user->admin_level < 1) {
            $this->redirectWithMessage(
                $this->unauthorizedUrl,
                'Privilégios insuficientes para este recurso',
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
}