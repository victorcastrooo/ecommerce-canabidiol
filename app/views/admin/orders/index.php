<?php include __DIR__ . '/../../../partials/header.php'; ?>

<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Gerenciamento de Pedidos</h1>
        <a href="/admin/reports/orders" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-download fa-sm text-white-50"></i> Gerar Relatório
        </a>
    </div>

    <!-- Filtros Avançados -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filtros Avançados</h6>
        </div>
        <div class="card-body">
            <form method="get" class="form">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="all" <?= ($status ?? 'all') === 'all' ? 'selected' : '' ?>>Todos os Status</option>
                                <option value="pending" <?= ($status ?? 'all') === 'pending' ? 'selected' : '' ?>>Pendentes</option>
                                <option value="processing" <?= ($status ?? 'all') === 'processing' ? 'selected' : '' ?>>Processando</option>
                                <option value="shipped" <?= ($status ?? 'all') === 'shipped' ? 'selected' : '' ?>>Enviados</option>
                                <option value="completed" <?= ($status ?? 'all') === 'completed' ? 'selected' : '' ?>>Concluídos</option>
                                <option value="cancelled" <?= ($status ?? 'all') === 'cancelled' ? 'selected' : '' ?>>Cancelados</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="date_from">Data Inicial</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" 
                                   value="<?= htmlspecialchars($date_from ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="date_to">Data Final</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" 
                                   value="<?= htmlspecialchars($date_to ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="search">Busca</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="search" name="search" 
                                       placeholder="Código ou cliente" value="<?= htmlspecialchars($search ?? '') ?>">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="vendor_id">Vendedor</label>
                            <select class="form-control" id="vendor_id" name="vendor_id">
                                <option value="">Todos os Vendedores</option>
                                <?php foreach ($allVendors as $vendor): ?>
                                    <option value="<?= $vendor->id ?>" 
                                        <?= ($vendor_id ?? '') == $vendor->id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($vendor->razao_social) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="payment_status">Status Pagamento</label>
                            <select class="form-control" id="payment_status" name="payment_status">
                                <option value="">Todos</option>
                                <option value="pending" <?= ($payment_status ?? '') === 'pending' ? 'selected' : '' ?>>Pendente</option>
                                <option value="paid" <?= ($payment_status ?? '') === 'paid' ? 'selected' : '' ?>>Pago</option>
                                <option value="refunded" <?= ($payment_status ?? '') === 'refunded' ? 'selected' : '' ?>>Reembolsado</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="prescription_status">Receita Médica</label>
                            <select class="form-control" id="prescription_status" name="prescription_status">
                                <option value="">Todos</option>
                                <option value="required" <?= ($prescription_status ?? '') === 'required' ? 'selected' : '' ?>>Com Receita</option>
                                <option value="approved" <?= ($prescription_status ?? '') === 'approved' ? 'selected' : '' ?>>Receita Aprovada</option>
                                <option value="pending" <?= ($prescription_status ?? '') === 'pending' ? 'selected' : '' ?>>Receita Pendente</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="text-right">
                    <a href="/admin/orders" class="btn btn-secondary">
                        <i class="fas fa-sync-alt"></i> Limpar Filtros
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela de Pedidos -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Pedidos Recentes</h6>
            <div class="dropdown no-arrow">
                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink"
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in"
                    aria-labelledby="dropdownMenuLink">
                    <div class="dropdown-header">Ações em Massa:</div>
                    <a class="dropdown-item" href="#" id="batchProcess">
                        <i class="fas fa-cog mr-2"></i>Processar Selecionados
                    </a>
                    <a class="dropdown-item" href="#" id="batchShip">
                        <i class="fas fa-truck mr-2"></i>Marcar como Enviado
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="/admin/orders/export?<?= http_build_query($_GET) ?>">
                        <i class="fas fa-file-export mr-2"></i>Exportar Resultados
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="ordersTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th width="20"><input type="checkbox" id="selectAll"></th>
                            <th width="120">Nº Pedido</th>
                            <th>Cliente</th>
                            <th>Vendedor</th>
                            <th width="120">Total</th>
                            <th width="120">Status</th>
                            <th width="120">Pagamento</th>
                            <th width="120">Data</th>
                            <th width="100">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><input type="checkbox" class="order-checkbox" value="<?= $order->id ?>"></td>
                            <td><?= $order->codigo ?></td>
                            <td>
                                <?= htmlspecialchars($order->client->nome) ?>
                                <?php if ($order->receita_id): ?>
                                    <span class="badge badge-info ml-2" title="Pedido com receita médica">RM</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($order->vendor->razao_social) ?></td>
                            <td>R$ <?= number_format($order->total, 2, ',', '.') ?></td>
                            <td>
                                <?php switch ($order->status):
                                    case 'pending': ?>
                                        <span class="badge badge-warning">Pendente</span>
                                        <?php break; ?>
                                    <?php case 'processing': ?>
                                        <span class="badge badge-info">Processando</span>
                                        <?php break; ?>
                                    <?php case 'shipped': ?>
                                        <span class="badge badge-primary">Enviado</span>
                                        <?php break; ?>
                                    <?php case 'completed': ?>
                                        <span class="badge badge-success">Concluído</span>
                                        <?php break; ?>
                                    <?php case 'cancelled': ?>
                                        <span class="badge badge-danger">Cancelado</span>
                                        <?php break; ?>
                                <?php endswitch; ?>
                            </td>
                            <td>
                                <?php if ($order->payment): ?>
                                    <?php switch ($order->payment->status):
                                        case 'pending': ?>
                                            <span class="badge badge-warning">Pendente</span>
                                            <?php break; ?>
                                        <?php case 'paid': ?>
                                            <span class="badge badge-success">Pago</span>
                                            <?php break; ?>
                                        <?php case 'refunded': ?>
                                            <span class="badge badge-secondary">Reembolsado</span>
                                            <?php break; ?>
                                    <?php endswitch; ?>
                                <?php else: ?>
                                    <span class="badge badge-light">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y', strtotime($order->data_pedido)) ?></td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <a href="/admin/orders/view/<?= $order->id ?>" class="btn btn-sm btn-info" title="Detalhes">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($order->status === 'pending'): ?>
                                        <a href="/admin/orders/process/<?= $order->id ?>" class="btn btn-sm btn-success" title="Processar">
                                            <i class="fas fa-cog"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <?php if ($pagination['totalPages'] > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= $pagination['currentPage'] <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" 
                           href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['currentPage'] - 1])) ?>" 
                           aria-label="Anterior">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    
                    <?php for ($i = 1; $i <= $pagination['totalPages']; $i++): ?>
                        <li class="page-item <?= $i == $pagination['currentPage'] ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?= $pagination['currentPage'] >= $pagination['totalPages'] ? 'disabled' : '' ?>">
                        <a class="page-link" 
                           href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['currentPage'] + 1])) ?>" 
                           aria-label="Próximo">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de Processamento em Massa -->
<div class="modal fade" id="batchActionModal" tabindex="-1" role="dialog" aria-labelledby="batchActionModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="batchActionModalLabel">Confirmar Ação em Massa</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="batchActionForm" method="post" action="">
                <div class="modal-body">
                    <p id="batchActionText"></p>
                    <div class="form-group" id="trackingField" style="display: none;">
                        <label for="tracking_code">Código de Rastreamento</label>
                        <input type="text" class="form-control" id="tracking_code" name="tracking_code">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <input type="hidden" name="order_ids" id="orderIds">
                    <button type="submit" class="btn btn-primary">Confirmar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../partials/footer.php'; ?>

<script>
// DataTable com seleção
$(document).ready(function() {
    var table = $('#ordersTable').DataTable({
        responsive: true,
        paging: false,
        info: false,
        searching: false,
        ordering: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Portuguese-Brasil.json'
        },
        columnDefs: [
            { orderable: false, targets: [0, 8] },
            { searchable: false, targets: [0, 5, 6, 7, 8] }
        ]
    });

    // Selecionar todos
    $('#selectAll').on('click', function() {
        $('.order-checkbox').prop('checked', this.checked);
    });

    // Ações em massa
    $('#batchProcess').on('click', function(e) {
        e.preventDefault();
        const selected = getSelectedOrders();
        if (selected.length === 0) {
            alert('Selecione pelo menos um pedido para processar.');
            return;
        }

        $('#batchActionForm').attr('action', '/admin/orders/batch-process');
        $('#batchActionText').text(`Você está prestes a marcar ${selected.length} pedido(s) como PROCESSANDO.`);
        $('#trackingField').hide();
        $('#orderIds').val(selected.join(','));
        $('#batchActionModal').modal('show');
    });

    $('#batchShip').on('click', function(e) {
        e.preventDefault();
        const selected = getSelectedOrders();
        if (selected.length === 0) {
            alert('Selecione pelo menos um pedido para marcar como enviado.');
            return;
        }

        $('#batchActionForm').attr('action', '/admin/orders/batch-ship');
        $('#batchActionText').text(`Você está prestes a marcar ${selected.length} pedido(s) como ENVIADO. Informe o código de rastreamento:`);
        $('#trackingField').show();
        $('#orderIds').val(selected.join(','));
        $('#batchActionModal').modal('show');
    });

    function getSelectedOrders() {
        const selected = [];
        $('.order-checkbox:checked').each(function() {
            selected.push($(this).val());
        });
        return selected;
    }
});
</script>