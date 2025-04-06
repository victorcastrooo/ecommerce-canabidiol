<?php require_once __DIR__ . '/../../../partials/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../../../partials/sidebar-vendor.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Minhas Vendas</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="exportBtn">
                            <i class="fas fa-file-export"></i> Exportar
                        </button>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-filter"></i> Filtrar
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="filterDropdown">
                                <li><a class="dropdown-item" href="#" data-filter="all">Todas as Vendas</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><h6 class="dropdown-header">Por Status</h6></li>
                                <li><a class="dropdown-item" href="#" data-filter="pending">Pendentes</a></li>
                                <li><a class="dropdown-item" href="#" data-filter="approved">Aprovadas</a></li>
                                <li><a class="dropdown-item" href="#" data-filter="shipped">Enviadas</a></li>
                                <li><a class="dropdown-item" href="#" data-filter="delivered">Entregues</a></li>
                                <li><a class="dropdown-item" href="#" data-filter="canceled">Canceladas</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><h6 class="dropdown-header">Por Período</h6></li>
                                <li><a class="dropdown-item" href="#" data-filter="today">Hoje</a></li>
                                <li><a class="dropdown-item" href="#" data-filter="week">Esta Semana</a></li>
                                <li><a class="dropdown-item" href="#" data-filter="month">Este Mês</a></li>
                                <li><a class="dropdown-item" href="#" data-filter="last-month">Mês Passado</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])) : ?>
                <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-white bg-primary mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Total de Vendas</h6>
                                    <h2 class="card-text"><?= $summary['total_orders'] ?></h2>
                                </div>
                                <i class="fas fa-shopping-bag fa-3x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-success mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Vendas Este Mês</h6>
                                    <h2 class="card-text">R$ <?= number_format($summary['monthly_sales'], 2, ',', '.') ?></h2>
                                </div>
                                <i class="fas fa-chart-line fa-3x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-info mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Comissões Disponíveis</h6>
                                    <h2 class="card-text">R$ <?= number_format($summary['available_commissions'], 2, ',', '.') ?></h2>
                                </div>
                                <i class="fas fa-hand-holding-usd fa-3x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-warning mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Pedidos Pendentes</h6>
                                    <h2 class="card-text"><?= $summary['pending_orders'] ?></h2>
                                </div>
                                <i class="fas fa-clock fa-3x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="mb-0">Histórico de Vendas</h5>
                        </div>
                        <div class="col-md-6">
                            <form class="row g-2 justify-content-end">
                                <div class="col-auto">
                                    <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Buscar pedido...">
                                </div>
                                <div class="col-auto">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="searchBtn">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th width="50">#</th>
                                    <th>Data</th>
                                    <th>Cliente</th>
                                    <th>Médico</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Comissão</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($orders)) : ?>
                                    <tr>
                                        <td colspan="8" class="text-center">Nenhuma venda encontrada</td>
                                    </tr>
                                <?php else : ?>
                                    <?php foreach ($orders as $order) : ?>
                                        <tr>
                                            <td>
                                                <a href="/vendor/sales/order/<?= $order['id'] ?>" class="text-primary">
                                                    #<?= $order['codigo'] ?>
                                                </a>
                                            </td>
                                            <td><?= date('d/m/Y', strtotime($order['data_pedido'])) ?></td>
                                            <td><?= $order['cliente']['nome'] ?></td>
                                            <td>
                                                <?php if ($order['medico_id']) : ?>
                                                    <?= $order['medico']['nome'] ?> (CRM-<?= $order['medico']['uf_crm'] ?>)
                                                <?php else : ?>
                                                    Venda Direta
                                                <?php endif; ?>
                                            </td>
                                            <td>R$ <?= number_format($order['total'], 2, ',', '.') ?></td>
                                            <td>
                                                <span class="badge bg-<?= getOrderStatusBadge($order['status']) ?>">
                                                    <?= ucfirst($order['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($order['comissao']) : ?>
                                                    <span class="badge bg-<?= getCommissionStatusBadge($order['comissao']['status']) ?>">
                                                        R$ <?= number_format($order['comissao']['valor_comissao'], 2, ',', '.') ?>
                                                    </span>
                                                <?php else : ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="/vendor/sales/order/<?= $order['id'] ?>" class="btn btn-outline-primary" title="Detalhes">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($order['status'] === 'pendente') : ?>
                                                        <button class="btn btn-outline-success" title="Aprovar" onclick="approveOrder(<?= $order['id'] ?>)">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button class="btn btn-outline-danger" title="Cancelar" onclick="cancelOrder(<?= $order['id'] ?>)">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if ($order['status'] === 'aprovado') : ?>
                                                        <button class="btn btn-outline-info" title="Marcar como Enviado" onclick="shipOrder(<?= $order['id'] ?>)">
                                                            <i class="fas fa-truck"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação -->
                    <?php if ($pagination['total_pages'] > 1) : ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?= $pagination['current_page'] == 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $pagination['current_page'] - 1 ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                
                                <?php for ($i = 1; $i <= $pagination['total_pages']; $i++) : ?>
                                    <li class="page-item <?= $i == $pagination['current_page'] ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?= $pagination['current_page'] == $pagination['total_pages'] ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $pagination['current_page'] + 1 ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
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
                <!-- Conteúdo dinâmico -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmActionBtn">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Funções para ações nos pedidos
    function approveOrder(orderId) {
        $('#confirmModalBody').html(`<p>Deseja realmente aprovar o pedido #${orderId}?</p>`);
        $('#confirmActionBtn').off('click').on('click', function() {
            window.location.href = `/vendor/sales/approve/${orderId}`;
        });
        $('#confirmModal').modal('show');
    }

    function cancelOrder(orderId) {
        $('#confirmModalBody').html(`
            <p>Deseja realmente cancelar o pedido #${orderId}?</p>
            <div class="mb-3">
                <label for="cancelReason" class="form-label">Motivo do Cancelamento</label>
                <textarea class="form-control" id="cancelReason" rows="3" required></textarea>
            </div>
        `);
        $('#confirmActionBtn').off('click').on('click', function() {
            const reason = $('#cancelReason').val();
            if (!reason) {
                alert('Por favor, informe o motivo do cancelamento.');
                return;
            }
            window.location.href = `/vendor/sales/cancel/${orderId}?reason=${encodeURIComponent(reason)}`;
        });
        $('#confirmModal').modal('show');
    }

    function shipOrder(orderId) {
        $('#confirmModalBody').html(`
            <p>Deseja marcar o pedido #${orderId} como enviado?</p>
            <div class="mb-3">
                <label for="trackingCode" class="form-label">Código de Rastreio</label>
                <input type="text" class="form-control" id="trackingCode">
            </div>
            <div class="mb-3">
                <label for="shippingCompany" class="form-label">Transportadora</label>
                <select class="form-select" id="shippingCompany">
                    <option value="Correios">Correios</option>
                    <option value="Trix">Trix Express</option>
                    <option value="Jadlog">Jadlog</option>
                    <option value="Outra">Outra</option>
                </select>
            </div>
        `);
        $('#confirmActionBtn').off('click').on('click', function() {
            const trackingCode = $('#trackingCode').val();
            const shippingCompany = $('#shippingCompany').val();
            window.location.href = `/vendor/sales/ship/${orderId}?tracking=${encodeURIComponent(trackingCode)}&company=${encodeURIComponent(shippingCompany)}`;
        });
        $('#confirmModal').modal('show');
    }

    // Filtros
    $(document).ready(function() {
        $('[data-filter]').click(function(e) {
            e.preventDefault();
            const filter = $(this).data('filter');
            window.location.href = `/vendor/sales?filter=${filter}`;
        });

        // Busca
        $('#searchBtn').click(function() {
            const searchTerm = $('#searchInput').val();
            if (searchTerm) {
                window.location.href = `/vendor/sales?search=${encodeURIComponent(searchTerm)}`;
            }
        });

        $('#searchInput').keypress(function(e) {
            if (e.which === 13) {
                $('#searchBtn').click();
            }
        });
    });
</script>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>