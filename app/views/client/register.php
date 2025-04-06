<?php require_once __DIR__ . '/../../../partials/header.php'; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Criar Conta de Cliente</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['errors'])) : ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($_SESSION['errors'] as $error) : ?>
                                    <li><?= $error ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php unset($_SESSION['errors']); ?>
                    <?php endif; ?>

                    <form id="registerForm" method="POST" action="/auth/register/client">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nome Completo <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?= $old['name'] ?? '' ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="cpf" class="form-label">CPF <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="cpf" name="cpf" value="<?= $old['cpf'] ?? '' ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">E-mail <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= $old['email'] ?? '' ?>" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Senha <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <small class="text-muted">Mínimo 8 caracteres</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password_confirmation" class="form-label">Confirme a Senha <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Telefone <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?= $old['phone'] ?? '' ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="birth_date" class="form-label">Data de Nascimento <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="birth_date" name="birth_date" value="<?= $old['birth_date'] ?? '' ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Gênero <span class="text-danger">*</span></label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gender" id="male" value="masculino" <?= isset($old['gender']) && $old['gender'] === 'masculino' ? 'checked' : '' ?> required>
                                <label class="form-check-label" for="male">Masculino</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gender" id="female" value="feminino" <?= isset($old['gender']) && $old['gender'] === 'feminino' ? 'checked' : '' ?> required>
                                <label class="form-check-label" for="female">Feminino</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gender" id="other" value="outro" <?= isset($old['gender']) && $old['gender'] === 'outro' ? 'checked' : '' ?> required>
                                <label class="form-check-label" for="other">Outro</label>
                            </div>
                        </div>

                        <hr>

                        <h5 class="mb-3">Endereço</h5>
                        <div class="row">
                            <div class="col-md-5">
                                <div class="mb-3">
                                    <label for="cep" class="form-label">CEP <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="cep" name="cep" value="<?= $old['cep'] ?? '' ?>" required>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <div class="mb-3">
                                    <label for="street" class="form-label">Logradouro <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="street" name="street" value="<?= $old['street'] ?? '' ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="number" class="form-label">Número <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="number" name="number" value="<?= $old['number'] ?? '' ?>" required>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="mb-3">
                                    <label for="complement" class="form-label">Complemento</label>
                                    <input type="text" class="form-control" id="complement" name="complement" value="<?= $old['complement'] ?? '' ?>">
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="mb-3">
                                    <label for="neighborhood" class="form-label">Bairro <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="neighborhood" name="neighborhood" value="<?= $old['neighborhood'] ?? '' ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="city" class="form-label">Cidade <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="city" name="city" value="<?= $old['city'] ?? '' ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="state" class="form-label">Estado <span class="text-danger">*</span></label>
                                    <select class="form-select" id="state" name="state" required>
                                        <option value="">Selecione</option>
                                        <?php foreach (BRAZIL_STATES as $uf => $state) : ?>
                                            <option value="<?= $uf ?>" <?= isset($old['state']) && $old['state'] === $uf ? 'selected' : '' ?>>
                                                <?= $uf ?> - <?= $state ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                            <label class="form-check-label" for="terms">Li e aceito os <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Termos de Uso</a> e <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Política de Privacidade</a></label>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-user-plus"></i> Criar Conta
                            </button>
                        </div>

                        <div class="mt-3 text-center">
                            Já tem uma conta? <a href="/auth/login">Faça login</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Termos de Uso -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termsModalLabel">Termos de Uso</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php include __DIR__ . '/../../../partials/terms-of-service.php'; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Política de Privacidade -->
<div class="modal fade" id="privacyModal" tabindex="-1" aria-labelledby="privacyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="privacyModalLabel">Política de Privacidade</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php include __DIR__ . '/../../../partials/privacy-policy.php'; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Máscaras
        $('#cpf').inputmask('999.999.999-99');
        $('#phone').inputmask('(99) 99999-9999');
        $('#cep').inputmask('99999-999');

        // Busca CEP
        $('#cep').blur(function() {
            const cep = $(this).val().replace(/\D/g, '');
            if (cep.length === 8) {
                $.getJSON(`https://viacep.com.br/ws/${cep}/json/`, function(data) {
                    if (!data.erro) {
                        $('#street').val(data.logradouro);
                        $('#neighborhood').val(data.bairro);
                        $('#city').val(data.localidade);
                        $('#state').val(data.uf);
                        $('#number').focus();
                    }
                });
            }
        });

        // Validação do formulário
        $('#registerForm').validate({
            rules: {
                password: {
                    minlength: 8
                },
                password_confirmation: {
                    equalTo: "#password"
                },
                email: {
                    email: true
                },
                birth_date: {
                    date: true,
                    maxDate: true
                }
            },
            messages: {
                password_confirmation: {
                    equalTo: "As senhas não coincidem"
                }
            }
        });

        // Validação de data (máximo hoje)
        $.validator.addMethod("maxDate", function(value, element) {
            const today = new Date();
            const inputDate = new Date(value);
            return inputDate <= today;
        }, "A data deve ser anterior ou igual a hoje");
    });
</script>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>