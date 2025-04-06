<?php require_once __DIR__ . '/../../../partials/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../../../partials/sidebar-vendor.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Cadastrar Médico Parceiro</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="/vendor/doctors" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Voltar para Lista
                    </a>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])) : ?>
                <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

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

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Informações do Médico</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="/vendor/doctors/store" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nome Completo <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?= $old['name'] ?? '' ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="crm" class="form-label">CRM <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="crm" name="crm" value="<?= $old['crm'] ?? '' ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="uf_crm" class="form-label">UF do CRM <span class="text-danger">*</span></label>
                                    <select class="form-select" id="uf_crm" name="uf_crm" required>
                                        <option value="">Selecione o Estado</option>
                                        <?php foreach (BRAZIL_STATES as $uf => $state) : ?>
                                            <option value="<?= $uf ?>" <?= isset($old['uf_crm']) && $old['uf_crm'] === $uf ? 'selected' : '' ?>>
                                                <?= $uf ?> - <?= $state ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="specialty" class="form-label">Especialidade <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="specialty" name="specialty" value="<?= $old['specialty'] ?? '' ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">E-mail <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= $old['email'] ?? '' ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="phone" class="form-label">Telefone <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="phone" name="phone" value="<?= $old['phone'] ?? '' ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="crm_front" class="form-label">Foto da Frente do CRM <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" id="crm_front" name="crm_front" accept="image/*,.pdf" required>
                                    <small class="text-muted">Formatos aceitos: JPG, PNG ou PDF (máx. 5MB)</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="crm_back" class="form-label">Foto do Verso do CRM <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" id="crm_back" name="crm_back" accept="image/*,.pdf" required>
                                    <small class="text-muted">Formatos aceitos: JPG, PNG ou PDF (máx. 5MB)</small>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Observações (Opcional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?= $old['notes'] ?? '' ?></textarea>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Após o cadastro, o médico será submetido à aprovação pela administração. Você será notificado quando o cadastro for aprovado.
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Cadastrar Médico
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    // Máscara para o campo de telefone
    $(document).ready(function() {
        $('#phone').inputmask('(99) 99999-9999');
        $('#crm').inputmask('999999');
    });
</script>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>