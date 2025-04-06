<?php require_once __DIR__ . '/../../../partials/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../../../partials/sidebar-admin.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Aprovar Pagamento</h1>
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
                    Detalhes do Pagamento
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>ID do Pedido:</strong> <?= $payment['pedido_id'] ?></p>
                            <p><strong>Método de Pagamento:</strong> <?= $payment['metodo'] ?></p>
                            <p><strong>Valor:</strong> R$ <?= number_format($payment['valor'], 2, ',', '.') ?></p>
                            <p><strong>Status:</strong> 
                                <span class="badge bg-<?= $payment['status'] === 'aprovado' ? 'success' : ($payment['status'] === 'pendente' ? 'warning' : 'danger') ?>">
                                    <?= ucfirst($payment['status']) ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Data do Pagamento:</strong> <?= date('d/m/Y H:i', strtotime($payment['data_pagamento'])) ?></p>
                            <p><strong>Código da Transação:</strong> <?= $payment['codigo_transacao'] ?? 'N/A' ?></p>
                        </div>
                    </div>

                    <?php if (!empty($payment['dados_transacao_json'])) : ?>
                        <div class="mt-3">
                            <h5>Dados da Transação:</h5>
                            <pre class="bg-light p-3"><?= json_encode(json_decode($payment['dados_transacao_json']), JSON_PRETTY_PRINT) ?></pre>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    Detalhes do Pedido
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th>Quantidade</th>
                                    <th>Preço Unitário</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orderItems as $item) : ?>
                                    <tr>
                                        <td><?= $item['produto']['nome'] ?></td>
                                        <td><?= $item['quantidade'] ?></td>
                                        <td>R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?></td>
                                        <td>R$ <?= number_format($item['total_item'], 2, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3">Subtotal</th>
                                    <td>R$ <?= number_format($order['subtotal'], 2, ',', '.') ?></td>
                                </tr>
                                <tr>
                                    <th colspan="3">Desconto</th>
                                    <td>R$ <?= number_format($order['desconto'], 2, ',', '.') ?></td>
                                </tr>
                                <tr>
                                    <th colspan="3">Total</th>
                                    <td>R$ <?= number_format($order['total'], 2, ',', '.') ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <?php if ($payment['status'] === 'pendente') : ?>
                <div class="card">
                    <div class="card-header">
                        Aprovação do Pagamento
                    </div>
                    <div class="card-body">
                        <form method="POST" action="/admin/payments/approve/<?= $payment['id'] ?>">
                            <div class="mb-3">
                                <label for="action" class="form-label">Ação</label>
                                <select class="form-select" id="action" name="action" required>
                                    <option value="">Selecione uma ação</option>
                                    <option value="approve">Aprovar Pagamento</option>
                                    <option value="reject">Rejeitar Pagamento</option>
                                </select>
                            </div>
                            <div class="mb-3" id="rejectReasonContainer" style="display: none;">
                                <label for="reject_reason" class="form-label">Motivo da Rejeição</label>
                                <textarea class="form-control" id="reject_reason" name="reject_reason" rows="3"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Confirmar</button>
                            <a href="/admin/payments" class="btn btn-secondary">Voltar</a>
                        </form>
                    </div>
                </div>
            <?php else : ?>
                <div class="alert alert-info">
                    Este pagamento já foi <?= $payment['status'] === 'aprovado' ? 'aprovado' : 'rejeitado' ?>.
                </div>
                <a href="/admin/payments" class="btn btn-secondary">Voltar</a>
            <?php endif; ?>
        </main>
    </div>
</div>

<script>
    document.getElementById('action').addEventListener('change', function() {
        const rejectReasonContainer = document.getElementById('rejectReasonContainer');
        if (this.value === 'reject') {
            rejectReasonContainer.style.display = 'block';
        } else {
            rejectReasonContainer.style.display = 'none';
        }
    });
</script>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>