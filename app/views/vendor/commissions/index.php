<?php require_once __DIR__ . '/../../../partials/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Minhas Comissões</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="/vendor/commissions/withdraw" class="btn btn-sm btn-success">
                            <i class="fas fa-money-bill-wave"></i> Solicitar Saque
                        </a>
                    </div>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])) : ?>
                <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])) : ?>
                <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="mb-0">Resumo de Comissões</h5>
                        </div>
                        <div class="col-md-6 text-end">
                            <strong>Total Disponível:</strong> 
                            <span class="text-success">R$ <?= number_format($summary['available'], 2, ',', '.') ?></span> | 
                            <strong>Pendente:</strong> 
                            <span class="text-warning">R$ <?= number_format($summary['pending'], 2, ',', '.') ?></span> | 
                            <strong>Pago:</strong> 
                            <span class="text-primary">R$ <?= number_format($summary['paid'], 2, ',', '.') ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <ul class="nav nav-tabs mb-4" id="commissionsTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="available-tab" data-bs-toggle="tab" data-bs-target="#available" type="button" role="tab" aria-controls="available" aria-selected="true">
                        Disponíveis (<?= count($commissions['available']) ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab" aria-controls="pending" aria-selected="false">
                        Pendentes (<?= count($commissions['pending']) ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="paid-tab" data-bs-toggle="tab" data-bs-target="#paid" type="button" role="tab" aria-controls="paid" aria-selected="false">
                        Pagas (<?= count($commissions['paid']) ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="withdrawals-tab" data-bs-toggle="tab" data-bs-target="#withdrawals" type="button" role="tab" aria-controls="withdrawals" aria-selected="false">
                        Saques (<?= count($withdrawals) ?>)
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="commissionsTabContent">
                <!-- TAB DISPONÍVEIS -->
                <div class="tab-pane fade show active" id="available" role="tabpanel" aria-labelledby="available-tab">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th width="50"><input type="checkbox" id="selectAllAvailable"></th>
                                            <th>Pedido</th>
                                            <th>Data</th>
                                            <th>Médico</th>
                                            <th>Valor</th>
                                            <th>Disponível desde</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($commissions['available'])) : ?>
                                            <tr>
                                                <td colspan="6" class="text-center">Nenhuma comissão disponível para saque</td>
                                            </tr>
                                        <?php else : ?>
                                            <?php foreach ($commissions['available'] as $commission) : ?>
                                                <tr>
                                                    <td><input type="checkbox" name="selected[]" value="<?= $commission['id'] ?>" form="withdrawForm"></td>
                                                    <td>
                                                        <a href="/vendor/sales/order/<?= $commission['pedido_id'] ?>" class="text-primary">
                                                            #<?= $commission['pedido']['codigo'] ?>
                                                        </a>
                                                    </td>
                                                    <td><?= date('d/m/Y', strtotime($commission['pedido']['data_pedido'])) ?></td>
                                                    <td>
                                                        <?php if ($commission['medico_id']) : ?>
                                                            <?= $commission['medico']['nome'] ?> (CRM-<?= $commission['medico']['uf_crm'] ?>)
                                                        <?php else : ?>
                                                            Venda Direta
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-success">R$ <?= number_format($commission['valor_comissao'], 2, ',', '.') ?></td>
                                                    <td><?= date('d/m/Y', strtotime($commission['data_disponibilidade'])) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php if (!empty($commissions['available'])) : ?>
                                <form id="withdrawForm" method="POST" action="/vendor/commissions/withdraw">
                                    <button type="submit" class="btn btn-success mt-3">
                                        <i class="fas fa-money-bill-wave"></i> Solicitar Saque das Comissões Selecionadas
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- TAB PENDENTES -->
                <div class="tab-pane fade" id="pending" role="tabpanel" aria-labelledby="pending-tab">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Pedido</th>
                                            <th>Data</th>
                                            <th>Médico</th>
                                            <th>Valor</th>
                                            <th>Disponível em</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($commissions['pending'])) : ?>
                                            <tr>
                                                <td colspan="5" class="text-center">Nenhuma comissão pendente</td>
                                            </tr>
                                        <?php else : ?>
                                            <?php foreach ($commissions['pending'] as $commission) : ?>
                                                <tr>
                                                    <td>
                                                        <a href="/vendor/sales/order/<?= $commission['pedido_id'] ?>" class="text-primary">
                                                            #<?= $commission['pedido']['codigo'] ?>
                                                        </a>
                                                    </td>
                                                    <td><?= date('d/m/Y', strtotime($commission['pedido']['data_pedido'])) ?></td>
                                                    <td>
                                                        <?php if ($commission['medico_id']) : ?>
                                                            <?= $commission['medico']['nome'] ?> (CRM-<?= $commission['medico']['uf_crm'] ?>)
                                                        <?php else : ?>
                                                            Venda Direta
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-warning">R$ <?= number_format($commission['valor_comissao'], 2, ',', '.') ?></td>
                                                    <td><?= date('d/m/Y', strtotime($commission['data_disponibilidade'])) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB PAGAS -->
                <div class="tab-pane fade" id="paid" role="tabpanel" aria-labelledby="paid-tab">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Pedido</th>
                                            <th>Data</th>
                                            <th>Médico</th>
                                            <th>Valor</th>
                                            <th>Data Pagamento</th>
                                            <th>Saque</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($commissions['paid'])) : ?>
                                            <tr>
                                                <td colspan="6" class="text-center">Nenhuma comissão paga</td>
                                            </tr>
                                        <?php else : ?>
                                            <?php foreach ($commissions['paid'] as $commission) : ?>
                                                <tr>
                                                    <td>
                                                        <a href="/vendor/sales/order/<?= $commission['pedido_id'] ?>" class="text-primary">
                                                            #<?= $commission['pedido']['codigo'] ?>
                                                        </a>
                                                    </td>
                                                    <td><?= date('d/m/Y', strtotime($commission['pedido']['data_pedido'])) ?></td>
                                                    <td>
                                                        <?php if ($commission['medico_id']) : ?>
                                                            <?= $commission['medico']['nome'] ?> (CRM-<?= $commission['medico']['uf_crm'] ?>)
                                                        <?php else : ?>
                                                            Venda Direta
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-primary">R$ <?= number_format($commission['valor_comissao'], 2, ',', '.') ?></td>
                                                    <td><?= date('d/m/Y', strtotime($commission['data_pagamento'])) ?></td>
                                                    <td>
                                                        <?php if ($commission['saque_id']) : ?>
                                                            <a href="#" data-bs-toggle="modal" data-bs-target="#withdrawalModal<?= $commission['saque_id'] ?>" class="text-info">
                                                                #<?= $commission['saque_id'] ?>
                                                            </a>
                                                        <?php else : ?>
                                                            N/A
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB SAQUES -->
                <div class="tab-pane fade" id="withdrawals" role="tabpanel" aria-labelledby="withdrawals-tab">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Data Solicitação</th>
                                            <th>Valor Bruto</th>
                                            <th>Taxa</th>
                                            <th>Valor Líquido</th>
                                            <th>Status</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($withdrawals)) : ?>
                                            <tr>
                                                <td colspan="7" class="text-center">Nenhum saque realizado</td>
                                            </tr>
                                        <?php else : ?>
                                            <?php foreach ($withdrawals as $withdrawal) : ?>
                                                <tr>
                                                    <td><?= $withdrawal['id'] ?></td>
                                                    <td><?= date('d/m/Y H:i', strtotime($withdrawal['data_solicitacao'])) ?></td>
                                                    <td>R$ <?= number_format($withdrawal['valor_total'], 2, ',', '.') ?></td>
                                                    <td>R$ <?= number_format($withdrawal['taxa_administrativa'], 2, ',', '.') ?></td>
                                                    <td class="text-success">R$ <?= number_format($withdrawal['valor_liquido'], 2, ',', '.') ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= getWithdrawalStatusBadge($withdrawal['status']) ?>">
                                                            <?= ucfirst($withdrawal['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#withdrawalModal<?= $withdrawal['id'] ?>">
                                                            <i class="fas fa-eye"></i> Detalhes
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modals para Detalhes de Saques -->
<?php foreach ($withdrawals as $withdrawal) : ?>
    <div class="modal fade" id="withdrawalModal<?= $withdrawal['id'] ?>" tabindex="-1" aria-labelledby="withdrawalModalLabel<?= $withdrawal['id'] ?>" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="withdrawalModalLabel<?= $withdrawal['id'] ?>">Detalhes do Saque #<?= $withdrawal['id'] ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Data Solicitação:</strong> <?= date('d/m/Y H:i', strtotime($withdrawal['data_solicitacao'])) ?>
                        </div>
                        <div class="col-md-4">
                            <strong>Status:</strong> 
                            <span class="badge bg-<?= getWithdrawalStatusBadge($withdrawal['status']) ?>">
                                <?= ucfirst($withdrawal['status']) ?>
                            </span>
                        </div>
                        <div class="col-md-4">
                            <strong>Método:</strong> <?= ucfirst($withdrawal['metodo']) ?>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Valor Bruto:</strong> R$ <?= number_format($withdrawal['valor_total'], 2, ',', '.') ?>
                        </div>
                        <div class="col-md-4">
                            <strong>Taxa:</strong> R$ <?= number_format($withdrawal['taxa_administrativa'], 2, ',', '.') ?>
                        </div>
                        <div class="col-md-4">
                            <strong>Valor Líquido:</strong> R$ <?= number_format($withdrawal['valor_liquido'], 2, ',', '.') ?>
                        </div>
                    </div>

                    <?php if ($withdrawal['status'] === 'processado') : ?>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Data Processamento:</strong> <?= date('d/m/Y H:i', strtotime($withdrawal['data_processamento'])) ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Processado por:</strong> <?= $withdrawal['processado_por']['nome'] ?? 'N/A' ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <h5 class="mt-4">Comissões Incluídas</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Pedido</th>
                                    <th>Data</th>
                                    <th>Médico</th>
                                    <th>Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($withdrawal['comissoes'] as $commission) : ?>
                                    <tr>
                                        <td>
                                            <a href="/vendor/sales/order/<?= $commission['pedido_id'] ?>" class="text-primary">
                                                #<?= $commission['pedido']['codigo'] ?>
                                            </a>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($commission['pedido']['data_pedido'])) ?></td>
                                        <td>
                                            <?php if ($commission['medico_id']) : ?>
                                                <?= $commission['medico']['nome'] ?> (CRM-<?= $commission['medico']['uf_crm'] ?>)
                                            <?php else : ?>
                                                Venda Direta
                                            <?php endif; ?>
                                        </td>
                                        <td>R$ <?= number_format($commission['valor_comissao'], 2, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (!empty($withdrawal['dados_pagamento_json'])) : ?>
                        <h5 class="mt-4">Dados do Pagamento</h5>
                        <pre class="bg-light p-3"><?= json_encode(json_decode($withdrawal['dados_pagamento_json']), JSON_PRETTY_PRINT) ?></pre>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script>
    // Selecionar todas as comissões disponíveis
    document.getElementById('selectAllAvailable').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('input[name="selected[]"]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });
</script>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>