<?php 
http_response_code(403);
$pageTitle = "Acesso Proibido";
require_once __DIR__ . '/../../partials/header.php'; 
?>

<div class="container my-5 py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 text-center">
            <div class="error-card p-4 p-lg-5 shadow-sm rounded-3 bg-light">
                <div class="error-icon mb-4">
                    <i class="fas fa-ban fa-4x text-danger"></i>
                </div>
                
                <h1 class="display-5 fw-bold text-danger mb-4">403 - Acesso Proibido</h1>
                
                <p class="lead mb-4">
                    Você não tem permissão para acessar esta página ou recurso.
                </p>
                
                <div class="alert alert-warning mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Atenção:</strong> O acesso a esta área requer privilégios específicos.
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        Você precisa estar autenticado para acessar este conteúdo.
                    <?php endif; ?>
                </div>
                
                <div class="d-flex flex-wrap justify-content-center gap-3 mt-4">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="<?= 
                            match($_SESSION['user_type']) {
                                'admin' => '/admin/dashboard',
                                'vendor' => '/vendor/dashboard',
                                'doctor' => '/doctor/dashboard',
                                default => '/client/dashboard'
                            } 
                        ?>" class="btn btn-primary btn-lg px-4">
                            <i class="fas fa-tachometer-alt me-2"></i> Voltar ao Painel
                        </a>
                    <?php else: ?>
                        <a href="/auth/login" class="btn btn-primary btn-lg px-4">
                            <i class="fas fa-sign-in-alt me-2"></i> Fazer Login
                        </a>
                        <a href="/auth/register" class="btn btn-outline-primary btn-lg px-4">
                            <i class="fas fa-user-plus me-2"></i> Criar Conta
                        </a>
                    <?php endif; ?>
                    
                    <a href="/" class="btn btn-outline-secondary btn-lg px-4">
                        <i class="fas fa-home me-2"></i> Página Inicial
                    </a>
                </div>
                
                <?php if (isset($_SESSION['user_id']) && in_array($_SESSION['user_type'], ['admin', 'vendor'])): ?>
                    <div class="mt-4 pt-3 border-top">
                        <p class="small text-muted mb-2">
                            Se você acredita que isto é um erro, entre em contato com o suporte técnico.
                        </p>
                        <a href="mailto:suporte@canabidiolcommerce.com.br" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-envelope me-1"></i> Relatar Problema
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.error-card {
    background-color: #f8f9fa;
    border: 1px solid rgba(0,0,0,.125);
}
.error-icon {
    animation: bounce 1s infinite alternate;
}
@keyframes bounce {
    from { transform: translateY(0); }
    to { transform: translateY(-10px); }
}
</style>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>