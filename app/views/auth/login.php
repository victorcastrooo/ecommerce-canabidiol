<?php require_once __DIR__ . '/../partials/header.php'; ?>

<div class="auth-container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 col-xl-5">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white text-center">
                    <h3 class="mb-0">
                        <i class="fas fa-cannabis"></i> Acesso ao Sistema
                    </h3>
                </div>
                
                <div class="card-body p-4">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?= $_SESSION['error']; ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                            <?php unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?= $_SESSION['success']; ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                            <?php unset($_SESSION['success']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="/auth/login" method="POST" class="needs-validation" novalidate>
                        <div class="form-group">
                            <label for="email">E-mail</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                </div>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= isset($_SESSION['old']['email']) ? htmlspecialchars($_SESSION['old']['email']) : '' ?>" 
                                       required autofocus>
                                <div class="invalid-feedback">
                                    Por favor, insira um e-mail válido.
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Senha</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                </div>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary toggle-password" type="button">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">
                                    Por favor, insira sua senha.
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Manter-me conectado</label>
                        </div>
                        
                        <div class="form-group mb-4">
                            <button type="submit" class="btn btn-primary btn-block btn-lg">
                                <i class="fas fa-sign-in-alt"></i> Entrar
                            </button>
                        </div>
                        
                        <div class="text-center">
                            <a href="/auth/forgot-password" class="text-muted">Esqueceu sua senha?</a>
                        </div>
                    </form>
                </div>
                
                <div class="card-footer text-center bg-light">
                    <p class="mb-0">
                        Não tem uma conta? 
                        <a href="/auth/register" class="font-weight-bold">Cadastre-se</a>
                    </p>
                    <div class="mt-2">
                        <small class="text-muted">
                            Cliente médico? <a href="/auth/register-doctor">Cadastre-se como médico</a>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

// Toggle password visibility
document.querySelector('.toggle-password').addEventListener('click', function(e) {
    const password = document.getElementById('password');
    const icon = this.querySelector('i');
    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
    password.setAttribute('type', type);
    icon.classList.toggle('fa-eye');
    icon.classList.toggle('fa-eye-slash');
});
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>