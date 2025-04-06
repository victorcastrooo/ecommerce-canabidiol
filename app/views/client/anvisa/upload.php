<?php require_once __DIR__ . '/../../../partials/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../../../partials/sidebar-client.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Enviar Aprovação ANVISA</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="/client/dashboard" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Voltar
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

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Documento de Aprovação</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Para comprar produtos regulamentados, você precisa enviar sua aprovação da ANVISA.
                        O documento será analisado pela nossa equipe em até 48 horas.
                    </div>

                    <form method="POST" action="/client/anvisa/store" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="approval_number" class="form-label">Número do Registro ANVISA <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="approval_number" name="approval_number" value="<?= $old['approval_number'] ?? '' ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="expiry_date" class="form-label">Data de Validade <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="expiry_date" name="expiry_date" value="<?= $old['expiry_date'] ?? '' ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="document" class="form-label">Documento Comprobatório <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="document" name="document" accept=".pdf,.jpg,.jpeg,.png" required>
                            <small class="text-muted">Formatos aceitos: PDF, JPG, PNG (tamanho máximo: 5MB)</small>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Observações (Opcional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?= $old['notes'] ?? '' ?></textarea>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload"></i> Enviar Documento
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (!empty($previousApprovals)) : ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Histórico de Envios</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Número do Registro</th>
                                        <th>Data Envio</th>
                                        <th>Validade</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($previousApprovals as $approval) : ?>
                                        <tr>
                                            <td><?= $approval['numero_registro'] ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($approval['data_upload'])) ?></td>
                                            <td><?= date('d/m/Y', strtotime($approval['data_validade'])) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $approval['aprovado'] ? 'success' : ($approval['motivo_rejeicao'] ? 'danger' : 'warning') ?>">
                                                    <?= $approval['aprovado'] ? 'Aprovado' : ($approval['motivo_rejeicao'] ? 'Rejeitado' : 'Pendente') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="/uploads/anvisa-approvals/<?= basename($approval['arquivo_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> Visualizar
                                                </a>
                                                <?php if (!$approval['aprovado'] && !$approval['motivo_rejeicao']) : ?>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="cancelSubmission(<?= $approval['id'] ?>)">
                                                        <i class="fas fa-times"></i> Cancelar
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Modal de Confirmação -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmModalLabel">Confirmar Ação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="confirmModalBody">
                Tem certeza que deseja cancelar este envio?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Não</button>
                <button type="button" class="btn btn-danger" id="confirmCancelBtn">Sim, Cancelar</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Validação de data futura
    document.getElementById('expiry_date').addEventListener('change', function() {
        const today = new Date();
        const expiryDate = new Date(this.value);
        
        if (expiryDate <= today) {
            alert('A data de validade deve ser futura');
            this.value = '';
        }
    });

    // Cancelar envio pendente
    let currentApprovalId = null;
    
    function cancelSubmission(approvalId) {
        currentApprovalId = approvalId;
        $('#confirmModal').modal('show');
    }
    
    $('#confirmCancelBtn').click(function() {
        if (currentApprovalId) {
            window.location.href = `/client/anvisa/cancel/${currentApprovalId}`;
        }
    });
</script>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>