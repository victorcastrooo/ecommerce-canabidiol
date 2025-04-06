<?php require_once __DIR__ . '/../../../partials/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Solicitar Saque de Comissões</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="/vendor/commissions" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                </div>
            </div>

            <?php if (isset($_SESSION['error'])) : ?>
                <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Resumo do Saque</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="/vendor/commissions/process-withdraw">
                                <div class="mb-3">
                                    <label class="form-label">Comissões Selecionadas</label>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Pedido</th>
                                                    <th>Data</th>
                                                    <th>Valor</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($selectedCommissions as $commission) : ?>
                                                    <tr>
                                                        <td>#<?= $commission['pedido']['codigo'] ?></td>
                                                        <td><?= date('d/m/Y', strtotime($commission['pedido']['data_pedido'])) ?></td>
                                                        <td>R$ <?= number_format($commission['valor_comissao'], 2, ',', '.') ?></td>
                                                        <input type="hidden" name="commission_ids[]" value="<?= $commission['id'] ?>">
                                                    </tr>
                                                <?php endforeach; ?>
                                                <tr class="table-active">
                                                    <td colspan="2" class="text-end"><strong>Total:</strong></td>
                                                    <td><strong>R$ <?= number_format($totalAmount, 2, ',', '.') ?></strong></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="withdrawal_method" class="form-label">Método de Recebimento</label>
                                    <select class="form-select" id="withdrawal_method" name="withdrawal_method" required>
                                        <option value="">Selecione o método</option>
                                        <option value="pix" <?= ($vendor['banco_tipo_conta'] ?? '') === 'pix' ? 'selected' : '' ?>>PIX</option>
                                        <option value="ted" <?= ($vendor['banco_tipo_conta'] ?? '') === 'ted' ? 'selected' : '' ?>>TED/DOC</option>
                                    </select>
                                </div>

                                <div id="bankDetailsSection" style="<?= ($vendor['banco_tipo_conta'] ?? '') !== 'pix' ? 'display: none;' : '' ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Dados Cadastrados para Recebimento</label>
                                        <div class="card bg-light p-3">
                                            <p><strong>Banco:</strong> <?= $vendor['banco_nome'] ?? 'Não informado' ?></p>
                                            <p><strong>Agência:</strong> <?= $vendor['banco_agencia'] ?? 'Não informada' ?></p>
                                            <p><strong>Conta:</strong> <?= $vendor['banco_conta'] ?? 'Não informada' ?> (<?= $vendor['banco_tipo_conta'] === 'conta_corrente' ? 'Corrente' : 'Poupança' ?>)</p>
                                            <p><strong>Titular:</strong> <?= $vendor['banco_titular'] ?? 'Não informado' ?></p>
                                            <p><strong>CPF Titular:</strong> <?= $vendor['banco_cpf_titular'] ?? 'Não informado' ?></p>
                                        </div>
                                    </div>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i> Verifique se seus dados bancários estão corretos antes de solicitar o saque.
                                        <a href="/vendor/profile" class="alert-link">Atualizar dados bancários</a>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="notes" class="form-label">Observações (Opcional)</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="terms_accept" name="terms_accept" required>
                                        <label class="form-check-label" for="terms_accept">
                                            Concordo com os <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Termos de Saque</a>
                                        </label>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-check-circle"></i> Confirmar Solicitação de Saque
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Taxas e Prazos</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Taxa Administrativa
                                    <span class="badge bg-danger"><?= $withdrawalFee * 100 ?>%</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Valor Líquido Estimado
                                    <span class="text-success">R$ <?= number_format($totalAmount * (1 - $withdrawalFee), 2, ',', '.') ?></span>
                                </li>
                                <li class="list-group-item">
                                    <small class="text-muted">Prazos de processamento: até 5 dias úteis</small>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Histórico Recente</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recentWithdrawals)) : ?>
                                <p class="text-muted">Nenhum saque recente</p>
                            <?php else : ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($recentWithdrawals as $withdrawal) : ?>
                                        <a href="#" class="list-group-item list-group-item-action" data-bs-toggle="modal" data-bs-target="#withdrawalModal<?= $withdrawal['id'] ?>">
                                            <div class="d-flex w-100 justify-content-between">
                                                <small class="text-muted">#<?= $withdrawal['id'] ?></small>
                                                <small><?= date('d/m/Y', strtotime($withdrawal['data_solicitacao'])) ?></small>
                                            </div>
                                            <div class="d-flex w-100 justify-content-between">
                                                <strong>R$ <?= number_format($withdrawal['valor_liquido'], 2, ',', '.') ?></strong>
                                                <span class="badge bg-<?= getWithdrawalStatusBadge($withdrawal['status']) ?>">
                                                    <?= ucfirst($withdrawal['status']) ?>
                                                </span>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Termos de Saque -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termsModalLabel">Termos de Saque</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h5>Política de Saque de Comissões</h5>
                <ol>
                    <li>O valor mínimo para saque é de R$ 100,00.</li>
                    <li>Taxa administrativa de <?= $withdrawalFee * 100 ?>% será aplicada sobre o valor bruto.</li>
                    <li>O prazo para processamento do pagamento é de até 5 dias úteis após a aprovação.</li>
                    <li>O vendedor é responsável pela veracidade dos dados bancários informados.</li>
                    <li>Saques podem ser cancelados ou suspensos em caso de irregularidades identificadas.</li>
                </ol>
                <h5 class="mt-4">Declaração de Concordância</h5>
                <p>Ao solicitar o saque, declaro que:</p>
                <ul>
                    <li>Li e concordo com todos os termos acima</li>
                    <li>As informações bancárias cadastradas estão corretas</li>
                    <li>Estou ciente da taxa administrativa aplicada</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modals para Histórico de Saques -->
<?php foreach ($recentWithdrawals as $withdrawal) : ?>
    <div class="modal fade" id="withdrawalModal<?= $withdrawal['id'] ?>" tabindex="-1" aria-labelledby="withdrawalModalLabel<?= $withdrawal['id'] ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="withdrawalModalLabel<?= $withdrawal['id'] ?>">Saque #<?= $withdrawal['id'] ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <strong>Status:</strong> 
                        <span class="badge bg-<?= getWithdrawalStatusBadge($withdrawal['status']) ?>">
                            <?= ucfirst($withdrawal['status']) ?>
                        </span>
                    </div>
                    <div class="mb-3">
                        <strong>Data Solicitação:</strong> <?= date('d/m/Y H:i', strtotime($withdrawal['data_solicitacao'])) ?>
                    </div>
                    <?php if ($withdrawal['status'] === 'processado') : ?>
                        <div class="mb-3">
                            <strong>Data Processamento:</strong> <?= date('d/m/Y H:i', strtotime($withdrawal['data_processamento'])) ?>
                        </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <strong>Valor Bruto:</strong> R$ <?= number_format($withdrawal['valor_total'], 2, ',', '.') ?>
                    </div>
                    <div class="mb-3">
                        <strong>Taxa:</strong> R$ <?= number_format($withdrawal['taxa_administrativa'], 2, ',', '.') ?>
                    </div>
                    <div class="mb-3">
                        <strong>Valor Líquido:</strong> R$ <?= number_format($withdrawal['valor_liquido'], 2, ',', '.') ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script>
    // Mostrar/ocultar detalhes bancários conforme método selecionado
    document.getElementById('withdrawal_method').addEventListener('change', function() {
        const bankDetailsSection = document.getElementById('bankDetailsSection');
        if (this.value === 'pix' || this.value === 'ted') {
            bankDetailsSection.style.display = 'block';
        } else {
            bankDetailsSection.style.display = 'none';
        }
    });
</script>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>