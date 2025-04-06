<?php require_once __DIR__ . '/../partials/header.php'; ?>

<div class="auth-container">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white text-center">
                    <h3 class="mb-0">
                        <i class="fas fa-user-plus"></i> Criar Conta
                    </h3>
                    <p class="mb-0 small">Selecione seu tipo de cadastro</p>
                </div>
                
                <div class="card-body p-4">
                    <!-- Tab Navigation -->
                    <ul class="nav nav-tabs nav-fill mb-4" id="registerTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="client-tab" data-toggle="tab" href="#client" role="tab">
                                <i class="fas fa-user"></i> Cliente
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="vendor-tab" data-toggle="tab" href="#vendor" role="tab">
                                <i class="fas fa-store"></i> Vendedor
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="doctor-tab" data-toggle="tab" href="#doctor" role="tab">
                                <i class="fas fa-user-md"></i> Médico
                            </a>
                        </li>
                    </ul>
                    
                    <!-- Error/Success Messages -->
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
                    
                    <!-- Tab Content -->
                    <div class="tab-content" id="registerTabsContent">
                        <!-- Client Registration -->
                        <div class="tab-pane fade show active" id="client" role="tabpanel">
                            <form action="/auth/register-client" method="POST" class="needs-validation" novalidate>
                                <h5 class="mb-3 text-center">Informações Pessoais</h5>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="client_name">Nome Completo*</label>
                                            <input type="text" class="form-control" id="client_name" name="name" 
                                                   value="<?= isset($_SESSION['old']['name']) ? htmlspecialchars($_SESSION['old']['name']) : '' ?>" 
                                                   required>
                                            <div class="invalid-feedback">
                                                Por favor, insira seu nome completo.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="client_cpf">CPF*</label>
                                            <input type="text" class="form-control cpf-mask" id="client_cpf" name="cpf" 
                                                   value="<?= isset($_SESSION['old']['cpf']) ? htmlspecialchars($_SESSION['old']['cpf']) : '' ?>" 
                                                   required>
                                            <div class="invalid-feedback">
                                                Por favor, insira um CPF válido.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="client_birthdate">Data de Nascimento*</label>
                                            <input type="date" class="form-control" id="client_birthdate" name="birthdate" 
                                                   value="<?= isset($_SESSION['old']['birthdate']) ? htmlspecialchars($_SESSION['old']['birthdate']) : '' ?>" 
                                                   required>
                                            <div class="invalid-feedback">
                                                Por favor, insira sua data de nascimento.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="client_gender">Gênero*</label>
                                            <select class="form-control" id="client_gender" name="gender" required>
                                                <option value="">Selecione</option>
                                                <option value="M" <?= isset($_SESSION['old']['gender']) && $_SESSION['old']['gender'] === 'M' ? 'selected' : '' ?>>Masculino</option>
                                                <option value="F" <?= isset($_SESSION['old']['gender']) && $_SESSION['old']['gender'] === 'F' ? 'selected' : '' ?>>Feminino</option>
                                                <option value="O" <?= isset($_SESSION['old']['gender']) && $_SESSION['old']['gender'] === 'O' ? 'selected' : '' ?>>Outro</option>
                                            </select>
                                            <div class="invalid-feedback">
                                                Por favor, selecione seu gênero.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <h5 class="mb-3 text-center mt-4">Informações de Contato</h5>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="client_email">E-mail*</label>
                                            <input type="email" class="form-control" id="client_email" name="email" 
                                                   value="<?= isset($_SESSION['old']['email']) ? htmlspecialchars($_SESSION['old']['email']) : '' ?>" 
                                                   required>
                                            <div class="invalid-feedback">
                                                Por favor, insira um e-mail válido.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="client_phone">Telefone*</label>
                                            <input type="tel" class="form-control phone-mask" id="client_phone" name="phone" 
                                                   value="<?= isset($_SESSION['old']['phone']) ? htmlspecialchars($_SESSION['old']['phone']) : '' ?>" 
                                                   required>
                                            <div class="invalid-feedback">
                                                Por favor, insira um telefone válido.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="client_password">Senha*</label>
                                            <input type="password" class="form-control" id="client_password" name="password" required>
                                            <small class="form-text text-muted">Mínimo 8 caracteres</small>
                                            <div class="invalid-feedback">
                                                Por favor, insira uma senha válida.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="client_password_confirmation">Confirmar Senha*</label>
                                            <input type="password" class="form-control" id="client_password_confirmation" name="password_confirmation" required>
                                            <div class="invalid-feedback">
                                                As senhas devem coincidir.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <h5 class="mb-3 text-center mt-4">Endereço</h5>
                                
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="client_cep">CEP*</label>
                                            <input type="text" class="form-control cep-mask" id="client_cep" name="cep" 
                                                   value="<?= isset($_SESSION['old']['cep']) ? htmlspecialchars($_SESSION['old']['cep']) : '' ?>" 
                                                   required>
                                            <div class="invalid-feedback">
                                                Por favor, insira um CEP válido.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-7">
                                        <div class="form-group">
                                            <label for="client_street">Logradouro*</label>
                                            <input type="text" class="form-control" id="client_street" name="street" 
                                                   value="<?= isset($_SESSION['old']['street']) ? htmlspecialchars($_SESSION['old']['street']) : '' ?>" 
                                                   required>
                                            <div class="invalid-feedback">
                                                Por favor, insira seu endereço.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="client_number">Número*</label>
                                            <input type="text" class="form-control" id="client_number" name="number" 
                                                   value="<?= isset($_SESSION['old']['number']) ? htmlspecialchars($_SESSION['old']['number']) : '' ?>" 
                                                   required>
                                            <div class="invalid-feedback">
                                                Por favor, insira o número.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="client_complement">Complemento</label>
                                            <input type="text" class="form-control" id="client_complement" name="complement" 
                                                   value="<?= isset($_SESSION['old']['complement']) ? htmlspecialchars($_SESSION['old']['complement']) : '' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="client_city">Cidade*</label>
                                            <input type="text" class="form-control" id="client_city" name="city" 
                                                   value="<?= isset($_SESSION['old']['city']) ? htmlspecialchars($_SESSION['old']['city']) : '' ?>" 
                                                   required>
                                            <div class="invalid-feedback">
                                                Por favor, insira sua cidade.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="client_state">Estado*</label>
                                            <select class="form-control" id="client_state" name="state" required>
                                                <option value="">Selecione</option>
                                                <!-- Brazilian states options -->
                                                <?php foreach (['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'] as $state): ?>
                                                    <option value="<?= $state ?>" <?= isset($_SESSION['old']['state']) && $_SESSION['old']['state'] === $state ? 'selected' : '' ?>>
                                                        <?= $state ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="invalid-feedback">
                                                Por favor, selecione seu estado.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group form-check mt-4">
                                    <input type="checkbox" class="form-check-input" id="client_terms" name="terms" required>
                                    <label class="form-check-label" for="client_terms">
                                        Eu li e concordo com os <a href="/terms" target="_blank">Termos de Uso</a> e <a href="/privacy" target="_blank">Política de Privacidade</a>*
                                    </label>
                                    <div class="invalid-feedback">
                                        Você deve concordar com os termos para se registrar.
                                    </div>
                                </div>
                                
                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                                        <i class="fas fa-user-plus"></i> Cadastrar como Cliente
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Vendor Registration -->
                        <div class="tab-pane fade" id="vendor" role="tabpanel">
                            <form action="/auth/register-vendor" method="POST" class="needs-validation" novalidate>
                                <!-- Similar structure as client form but with vendor-specific fields -->
                                <!-- Include fields for CNPJ, company name, etc. -->
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> Cadastro de vendedores sujeito à aprovação da administração.
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-block btn-lg">
                                    <i class="fas fa-store"></i> Cadastrar como Vendedor
                                </button>
                            </form>
                        </div>
                        
                        <!-- Doctor Registration -->
                        <div class="tab-pane fade" id="doctor" role="tabpanel">
                            <form action="/auth/register-doctor" method="POST" class="needs-validation" novalidate>
                                <!-- Similar structure as client form but with doctor-specific fields -->
                                <!-- Include fields for CRM, specialty, etc. -->
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> Médicos devem ser vinculados a um vendedor aprovado.
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-block btn-lg">
                                    <i class="fas fa-user-md"></i> Cadastrar como Médico
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="card-footer text-center bg-light">
                    <p class="mb-0">
                        Já tem uma conta? <a href="/auth/login" class="font-weight-bold">Faça login</a>
                    </p>
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

// Input masks
$(document).ready(function() {
    $('.cpf-mask').inputmask('999.999.999-99');
    $('.cep-mask').inputmask('99999-999');
    $('.phone-mask').inputmask('(99) 9999[9]-9999');
});

// CEP auto-complete
$('#client_cep').blur(function() {
    const cep = $(this).val().replace(/\D/g, '');
    if (cep.length === 8) {
        $.getJSON(`https://viacep.com.br/ws/${cep}/json/`, function(data) {
            if (!data.erro) {
                $('#client_street').val(data.logradouro);
                $('#client_city').val(data.localidade);
                $('#client_state').val(data.uf);
                $('#client_complement').val(data.complemento);
                $('#client_number').focus();
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>