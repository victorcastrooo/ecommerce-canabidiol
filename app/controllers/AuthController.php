<?php
namespace App\Controllers;

use App\Lib\Auth;
use App\Lib\Database;
use App\Lib\Validator;
use App\Lib\Mailer;
use App\Models\User;
use App\Models\Admin;
use App\Models\Vendor;
use App\Models\Client;
use App\Models\ActivityLog;

class AuthController extends BaseController
{
    private $auth;
    private $db;
    private $validator;
    private $mailer;

    public function __construct()
    {
        parent::__construct();
        $this->auth = new Auth();
        $this->db = Database::getInstance();
        $this->validator = new Validator();
        $this->mailer = new Mailer();
    }

    /**
     * Página de login
     */
    public function login()
    {
        // Se já estiver logado, redireciona para a página apropriada
        if ($this->auth->isLoggedIn()) {
            $this->redirectToDashboard();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $remember = isset($_POST['remember']);

            // Validação básica
            if (empty($email) || empty($password)) {
                $this->setFlash('error', 'E-mail e senha são obrigatórios');
                $this->redirect('/auth/login');
            }

            // Tentativa de login
            $user = User::findByEmail($email);

            if (!$user || !$this->auth->verifyPassword($password, $user->senha_hash)) {
                ActivityLog::create([
                    'acao' => 'login_failed',
                    'dados_novos' => json_encode(['email' => $email, 'ip' => $_SERVER['REMOTE_ADDR']])
                ]);
                
                $this->setFlash('error', 'E-mail ou senha incorretos');
                $this->redirect('/auth/login');
            }

            // Verificar se a conta está ativa
            if (!$user->ativo) {
                $this->setFlash('error', 'Sua conta está desativada. Entre em contato com o suporte.');
                $this->redirect('/auth/login');
            }

            // Realizar login
            $this->auth->login($user, $remember);

            // Registrar login bem-sucedido
            ActivityLog::create([
                'usuario_id' => $user->id,
                'acao' => 'login_success',
                'ip' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);

            // Atualizar último login
            $user->data_ultimo_login = date('Y-m-d H:i:s');
            $user->ip_ultimo_login = $_SERVER['REMOTE_ADDR'];
            $user->save();

            // Redirecionar para o dashboard apropriado
            $this->redirectToDashboard();
        }

        $this->render('auth/login');
    }

    /**
     * Página de registro
     */
    public function register($userType = 'client')
    {
        // Tipos de usuário permitidos
        $allowedTypes = ['client', 'vendor'];
        if (!in_array($userType, $allowedTypes)) {
            $this->redirect('/auth/register/client');
        }

        // Se já estiver logado, redireciona para a página apropriada
        if ($this->auth->isLoggedIn()) {
            $this->redirectToDashboard();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $this->validateRegistrationData($_POST, $userType);

            // Validação adicional específica para cada tipo
            $rules = User::$rules;
            if ($userType === 'vendor') {
                $rules['cpf_cnpj'] = 'required|unique:usuarios|cpf_cnpj';
            } else {
                $rules['cpf_cnpj'] = 'required|unique:usuarios|cpf';
            }

            if ($this->validator->validate($data, $rules)) {
                // Criar usuário
                $user = new User($data);
                $user->senha_hash = $this->auth->hashPassword($data['password']);
                $user->tipo = $userType;
                $user->token_ativacao = bin2hex(random_bytes(32));
                $user->ativo = 0; // Inativo até confirmar e-mail

                if ($user->save()) {
                    // Criar registro específico do tipo de usuário
                    if ($userType === 'client') {
                        $client = new Client([
                            'usuario_id' => $user->id,
                            'data_nascimento' => $data['data_nascimento'] ?? null,
                            'genero' => $data['genero'] ?? null
                        ]);
                        $client->save();
                    } elseif ($userType === 'vendor') {
                        $vendor = new Vendor([
                            'usuario_id' => $user->id,
                            'razao_social' => $data['razao_social'] ?? '',
                            'inscricao_estadual' => $data['inscricao_estadual'] ?? '',
                            'aprovado' => 0 // Pendente de aprovação
                        ]);
                        $vendor->save();
                    }

                    // Enviar e-mail de ativação
                    $this->sendActivationEmail($user);

                    ActivityLog::create([
                        'usuario_id' => $user->id,
                        'acao' => 'register_' . $userType,
                        'ip' => $_SERVER['REMOTE_ADDR']
                    ]);

                    $this->setFlash('success', 'Registro realizado com sucesso! Por favor, verifique seu e-mail para ativar sua conta.');
                    $this->redirect('/auth/login');
                } else {
                    $this->setFlash('error', 'Erro ao salvar usuário');
                }
            } else {
                $this->setFlash('error', $this->validator->getErrors());
            }
        }

        $this->render('auth/register', ['userType' => $userType]);
    }

    /**
     * Ativação de conta
     */
    public function activate($token = null)
    {
        if (empty($token)) {
            $this->redirect('/auth/login');
        }

        $user = User::findByActivationToken($token);

        if (!$user) {
            $this->setFlash('error', 'Token de ativação inválido ou expirado');
            $this->redirect('/auth/login');
        }

        // Ativar conta
        $user->token_ativacao = null;
        $user->ativo = 1;
        $user->save();

        ActivityLog::create([
            'usuario_id' => $user->id,
            'acao' => 'account_activated',
            'ip' => $_SERVER['REMOTE_ADDR']
        ]);

        $this->setFlash('success', 'Conta ativada com sucesso! Você já pode fazer login.');
        $this->redirect('/auth/login');
    }

    /**
     * Solicitação de recuperação de senha
     */
    public function forgotPassword()
    {
        if ($this->auth->isLoggedIn()) {
            $this->redirectToDashboard();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');

            if (empty($email)) {
                $this->setFlash('error', 'Por favor, informe seu e-mail');
                $this->redirect('/auth/forgot-password');
            }

            $user = User::findByEmail($email);

            if ($user) {
                // Gerar token de reset
                $user->token_reset_senha = bin2hex(random_bytes(32));
                $user->save();

                // Enviar e-mail com link para resetar senha
                $this->sendPasswordResetEmail($user);

                ActivityLog::create([
                    'acao' => 'password_reset_request',
                    'dados_novos' => json_encode(['email' => $email, 'ip' => $_SERVER['REMOTE_ADDR']])
                ]);
            }

            // Mesmo que o e-mail não exista, mostramos a mesma mensagem por segurança
            $this->setFlash('success', 'Se o e-mail existir em nosso sistema, você receberá um link para redefinir sua senha.');
            $this->redirect('/auth/login');
        }

        $this->render('auth/forgot-password');
    }

    /**
     * Redefinição de senha
     */
    public function resetPassword($token = null)
    {
        if ($this->auth->isLoggedIn()) {
            $this->redirectToDashboard();
        }

        if (empty($token)) {
            $this->redirect('/auth/forgot-password');
        }

        $user = User::findByResetToken($token);

        if (!$user) {
            $this->setFlash('error', 'Token de redefinição inválido ou expirado');
            $this->redirect('/auth/forgot-password');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($password) || $password !== $confirmPassword) {
                $this->setFlash('error', 'As senhas não coincidem');
                $this->redirect('/auth/reset-password/' . $token);
            }

            // Atualizar senha
            $user->senha_hash = $this->auth->hashPassword($password);
            $user->token_reset_senha = null;
            $user->save();

            ActivityLog::create([
                'usuario_id' => $user->id,
                'acao' => 'password_reset_success',
                'ip' => $_SERVER['REMOTE_ADDR']
            ]);

            $this->setFlash('success', 'Senha redefinida com sucesso! Faça login com sua nova senha.');
            $this->redirect('/auth/login');
        }

        $this->render('auth/reset-password', ['token' => $token]);
    }

    /**
     * Logout
     */
    public function logout()
    {
        if ($this->auth->isLoggedIn()) {
            ActivityLog::create([
                'usuario_id' => $this->auth->getUserId(),
                'acao' => 'logout',
                'ip' => $_SERVER['REMOTE_ADDR']
            ]);
        }

        $this->auth->logout();
        $this->redirect('/auth/login');
    }

    /**
     * Métodos auxiliares privados
     */
    
    private function redirectToDashboard()
    {
        if ($this->auth->isAdmin()) {
            $this->redirect('/admin/dashboard');
        } elseif ($this->auth->isVendor()) {
            $this->redirect('/vendor/dashboard');
        } else {
            $this->redirect('/client/dashboard');
        }
    }

    private function validateRegistrationData($data, $userType)
    {
        $baseData = [
            'nome' => trim($data['nome'] ?? ''),
            'email' => trim($data['email'] ?? ''),
            'password' => $data['password'] ?? '',
            'confirm_password' => $data['confirm_password'] ?? '',
            'cpf_cnpj' => preg_replace('/[^0-9]/', '', $data['cpf_cnpj'] ?? ''),
            'telefone' => trim($data['telefone'] ?? ''),
            'endereco_cep' => trim($data['endereco_cep'] ?? ''),
            'endereco_logradouro' => trim($data['endereco_logradouro'] ?? ''),
            'endereco_numero' => trim($data['endereco_numero'] ?? ''),
            'endereco_complemento' => trim($data['endereco_complemento'] ?? ''),
            'endereco_cidade' => trim($data['endereco_cidade'] ?? ''),
            'endereco_estado' => trim($data['endereco_estado'] ?? '')
        ];

        if ($userType === 'client') {
            $baseData['data_nascimento'] = $data['data_nascimento'] ?? null;
            $baseData['genero'] = $data['genero'] ?? null;
        } elseif ($userType === 'vendor') {
            $baseData['razao_social'] = trim($data['razao_social'] ?? '');
            $baseData['inscricao_estadual'] = trim($data['inscricao_estadual'] ?? '');
        }

        return $baseData;
    }

    private function sendActivationEmail($user)
    {
        $activationLink = getenv('APP_URL') . '/auth/activate/' . $user->token_ativacao;
        
        $subject = 'Ative sua conta no ' . getenv('APP_NAME');
        $body = $this->renderEmailTemplate('auth/emails/activation', [
            'user' => $user,
            'activationLink' => $activationLink
        ]);

        $this->mailer->send(
            $user->email,
            $subject,
            $body
        );
    }

    private function sendPasswordResetEmail($user)
    {
        $resetLink = getenv('APP_URL') . '/auth/reset-password/' . $user->token_reset_senha;
        
        $subject = 'Redefinição de senha - ' . getenv('APP_NAME');
        $body = $this->renderEmailTemplate('auth/emails/reset-password', [
            'user' => $user,
            'resetLink' => $resetLink
        ]);

        $this->mailer->send(
            $user->email,
            $subject,
            $body
        );
    }

    private function renderEmailTemplate($template, $data = [])
    {
        ob_start();
        extract($data);
        require APP_PATH . '/views/' . $template . '.php';
        return ob_get_clean();
    }
}