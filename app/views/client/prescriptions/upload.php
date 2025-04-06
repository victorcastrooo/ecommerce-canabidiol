<?php require_once __DIR__ . '/../../partials/header.php'; ?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-file-medical"></i> Enviar Receita Médica
                    </h4>
                </div>
                
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success">
                            <?= $_SESSION['success']; ?>
                            <?php unset($_SESSION['success']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?= $_SESSION['error']; ?>
                            <?php unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <p class="text-muted">
                        Para comprar produtos que necessitam de receita médica, por favor envie uma cópia legível da sua receita.
                        A receita deve conter: CRM do médico, sua identificação, data e assinatura.
                    </p>
                    
                    <form action="/client/prescriptions/upload" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="doctor_name">Nome do Médico</label>
                            <input type="text" class="form-control" id="doctor_name" name="doctor_name" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="crm">CRM</label>
                                    <input type="text" class="form-control" id="crm" name="crm" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="uf_crm">UF do CRM</label>
                                    <select class="form-control" id="uf_crm" name="uf_crm" required>
                                        <option value="">Selecione</option>
                                        <option value="AC">AC</option>
                                        <option value="AL">AL</option>
                                        <!-- Add all Brazilian states -->
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="prescription_date">Data da Receita</label>
                            <input type="date" class="form-control" id="prescription_date" name="prescription_date" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="prescription_file">Arquivo da Receita</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="prescription_file" name="prescription_file" accept=".pdf,.jpg,.jpeg,.png" required>
                                <label class="custom-file-label" for="prescription_file">Selecione o arquivo (PDF, JPG ou PNG)</label>
                            </div>
                            <small class="form-text text-muted">
                                Tamanho máximo: 5MB. Formatos aceitos: PDF, JPG, PNG.
                            </small>
                        </div>
                        
                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" id="terms_accept" name="terms_accept" required>
                            <label class="form-check-label" for="terms_accept">
                                Declaro que esta receita é válida e autêntica, e estou ciente que informações falsas podem acarretar em responsabilização legal.
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg btn-block">
                            <i class="fas fa-upload"></i> Enviar Receita
                        </button>
                    </form>
                </div>
                
                <div class="card-footer bg-light">
                    <h5>Receitas Enviadas Recentemente</h5>
                    <?php if (empty($prescriptions)): ?>
                        <p class="text-muted">Nenhuma receita enviada recentemente.</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($prescriptions as $prescription): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <span>
                                            <strong>Médico:</strong> <?= htmlspecialchars($prescription['nome_medico']) ?> - 
                                            CRM-<?= htmlspecialchars($prescription['uf_crm']) ?> <?= htmlspecialchars($prescription['crm_medico']) ?>
                                        </span>
                                        <span class="badge badge-<?= $prescription['aprovada'] ? 'success' : 'warning' ?>">
                                            <?= $prescription['aprovada'] ? 'Aprovada' : 'Pendente' ?>
                                        </span>
                                    </div>
                                    <small class="text-muted">Enviado em: <?= date('d/m/Y H:i', strtotime($prescription['data_upload'])) ?></small>
                                    <?php if (!empty($prescription['motivo_rejeicao'])): ?>
                                        <div class="alert alert-danger mt-2 mb-0 p-2">
                                            <strong>Motivo rejeição:</strong> <?= htmlspecialchars($prescription['motivo_rejeicao']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Show file name when selected
document.querySelector('.custom-file-input').addEventListener('change', function(e) {
    var fileName = document.getElementById("prescription_file").files[0].name;
    var nextSibling = e.target.nextElementSibling;
    nextSibling.innerText = fileName;
});

// Set max date to today for prescription date
document.getElementById('prescription_date').max = new Date().toISOString().split("T")[0];
</script>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>