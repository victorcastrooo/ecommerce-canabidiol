<?php include __DIR__ . '/../../../partials/header.php'; ?>

<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Aprovação de Médicos Parceiros</h1>
        <a href="/admin/doctors" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Voltar para lista
        </a>
    </div>

    <!-- Mensagens de Status -->
    <?php if (isset($_SESSION['flash']['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $_SESSION['flash']['success'] ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash']['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $_SESSION['flash']['error'] ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <!-- Card de Médicos Pendentes -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Cadastros Pendentes</h6>
            <span class="badge badge-pill badge-warning">
                <?= count($pendingDoctors) ?> pendente(s)
            </span>
        </div>
        <div class="card-body">
            <?php if (count($pendingDoctors) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th width="50">ID</th>
                                <th>Nome</th>
                                <th>CRM/UF</th>
                                <th>Especialidade</th>
                                <th>Vendedor</th>
                                <th width="200">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingDoctors as $doctor): ?>
                            <tr>
                                <td><?= $doctor->id ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($doctor->nome) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($doctor->email) ?></small>
                                </td>
                                <td><?= htmlspecialchars($doctor->crm) ?>/<?= htmlspecialchars($doctor->uf_crm) ?></td>
                                <td><?= htmlspecialchars($doctor->especialidade) ?></td>
                                <td><?= htmlspecialchars($doctor->vendor->razao_social) ?></td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <button type="button" 
                                                class="btn btn-sm btn-success approve-doctor" 
                                                title="Aprovar"
                                                data-id="<?= $doctor->id ?>"
                                                data-name="<?= htmlspecialchars($doctor->nome) ?>">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button type="button" 
                                                class="btn btn-sm btn-danger reject-doctor" 
                                                title="Rejeitar"
                                                data-id="<?= $doctor->id ?>"
                                                data-name="<?= htmlspecialchars($doctor->nome) ?>">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <a href="/admin/doctors/view/<?= $doctor->id ?>" 
                                           class="btn btn-sm btn-info" title="Detalhes">
                                            <i class="fas fa-search"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <div class="text-muted">
                        <i class="fas fa-check-circle fa-3x mb-3 text-success"></i><br>
                        Nenhum cadastro médico pendente de aprovação
                    </div>
                    <a href="/admin/doctors" class="btn btn-primary mt-3">
                        <i class="fas fa-user-md"></i> Ver todos os médicos
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal de Aprovação -->
    <div class="modal fade" id="approveModal" tabindex="-1" role="dialog" aria-labelledby="approveModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="approveModalLabel">Confirmar Aprovação</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="approveForm" method="post" action="/admin/doctors/approve">
                    <div class="modal-body">
                        <p>Tem certeza que deseja aprovar o cadastro do(a) médico(a) <strong id="approveDoctorName"></strong>?</p>
                        <div class="form-group">
                            <label for="observacoes">Observações (opcional)</label>
                            <textarea class="form-control" id="observacoes" name="observacoes" rows="2"></textarea>
                            <small class="form-text text-muted">Informações adicionais que serão registradas</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <input type="hidden" name="id" id="approveDoctorId">
                        <button type="submit" class="btn btn-success">Confirmar Aprovação</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Rejeição -->
    <div class="modal fade" id="rejectModal" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectModalLabel">Confirmar Rejeição</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="rejectForm" method="post" action="/admin/doctors/reject">
                    <div class="modal-body">
                        <p>Tem certeza que deseja rejeitar o cadastro do(a) médico(a) <strong id="rejectDoctorName"></strong>?</p>
                        <div class="form-group">
                            <label for="motivo_rejeicao">Motivo da Rejeição *</label>
                            <textarea class="form-control" id="motivo_rejeicao" name="motivo_rejeicao" rows="3" required></textarea>
                            <small class="form-text text-muted">Este motivo será enviado ao vendedor responsável</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <input type="hidden" name="id" id="rejectDoctorId">
                        <button type="submit" class="btn btn-danger">Confirmar Rejeição</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../partials/footer.php'; ?>

<script>
// Modal de Aprovação
document.querySelectorAll('.approve-doctor').forEach(button => {
    button.addEventListener('click', function() {
        const doctorId = this.getAttribute('data-id');
        const doctorName = this.getAttribute('data-name');
        
        document.getElementById('approveDoctorId').value = doctorId;
        document.getElementById('approveDoctorName').textContent = doctorName;
        
        $('#approveModal').modal('show');
    });
});

// Modal de Rejeição
document.querySelectorAll('.reject-doctor').forEach(button => {
    button.addEventListener('click', function() {
        const doctorId = this.getAttribute('data-id');
        const doctorName = this.getAttribute('data-name');
        
        document.getElementById('rejectDoctorId').value = doctorId;
        document.getElementById('rejectDoctorName').textContent = doctorName;
        
        $('#rejectModal').modal('show');
    });
});

// Validação do formulário de rejeição
document.getElementById('rejectForm').addEventListener('submit', function(e) {
    const motivo = document.getElementById('motivo_rejeicao').value.trim();
    
    if (!motivo) {
        e.preventDefault();
        alert('Por favor, informe o motivo da rejeição.');
        document.getElementById('motivo_rejeicao').focus();
    }
});
</script>