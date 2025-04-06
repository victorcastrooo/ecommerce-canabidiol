<?php require_once __DIR__ . '/../../../partials/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../../../partials/sidebar-client.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Meus Pedidos</h1>
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
                                <li><a class="dropdown-item" href="#" data-filter="all">Todos os Pedidos</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><h6 class="dropdown-header">Por Status</h6></li>
                                <li><a class="dropdown-item" href="#" data-filter="pending">Pendentes</a></li>
                                <li><a class="dropdown-item" href="#" data-filter="approved">Aprovados</a></li>
                                <li><a class="dropdown-item" href="#" data-filter="shipped">Enviados</a></li>
                                <li><a class="dropdown-item" href="#" data-filter="delivered">Entregues</a></li>
                                <li><a class="dropdown-item" href="#" data-filter="canceled">Cancelados</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><h6 class="dropdown-header">Por Período</h6></li>
                                <li><a class="dropdown-item" href="#" data-filter="last-30-days">Últimos 30 dias</a></li>
                                <li><a class="dropdown-item" href="#" data-filter="last-6-months">Últimos 6 meses</a></li>
                                <li><a class="dropdown-item" href="#" data-filter="this-year">Este Ano</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])) : ?>
                <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (empty($orders)) : ?>
                <div class="alert alert-info">
                    Você ainda não fez nenhum pedido. <a href="/products" class="alert-link">Conheça nossos produtos</a>
                </div>
            <?php else : ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Nº Pedido</th>
                                        <th>Data</th>
                                        <th>Itens</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Rastreio</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order) : ?>
                                        <tr>
                                            <td>
                                                <a href="/client/orders/<?= $order['id'] ?>" class="text-primary">
                                                    #<?= $order['codigo'] ?>
                                                </a>
                                            </td>
                                            <td><?= date('d/m/Y', strtotime($order['data_pedido'])) ?></td>
                                            <td><?= count($order['itens']) ?> item(s)</td>
                                            <td>R$ <?= number_format($order['total'], 2, ',', '.') ?></td>
                                            <td>
                                                <span class="badge bg-<?= getOrderStatusBadge($order['status']) ?>">
                                                    <?= ucfirst($order['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($order['tracking_code']) : ?>
                                                    <a href="<?= getTrackingUrl($order['transportadora'], $order['tracking_code']) ?>" target="_blank" class="text-primary">
                                                        <?= $order['tracking_code'] ?>
                                                    </a>
                                                <?php else : ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="/client/orders/<?= $order['id'] ?>" class="btn btn-outline-primary" title="Detalhes">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($order['status'] === 'pendente') : ?>
                                                        <button class="btn btn-outline-danger" title="Cancelar" onclick="confirmCancel(<?= $order['id'] ?>)">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if ($order['status'] === 'entregue') : ?>
                                                        <button class="btn btn-outline-success" title="Avaliar" onclick="openReviewModal(<?= $order['id'] ?>)">
                                                            <i class="fas fa-star"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
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
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Modal de Cancelamento -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelModalLabel">Cancelar Pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja cancelar este pedido?</p>
                <div class="mb-3">
                    <label for="cancelReason" class="form-label">Motivo do cancelamento</label>
                    <textarea class="form-control" id="cancelReason" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                <button type="button" class="btn btn-danger" id="confirmCancelBtn">Confirmar Cancelamento</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Avaliação -->
<div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reviewModalLabel">Avaliar Pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="reviewForm" method="POST" action="/client/orders/review">
                <div class="modal-body">
                    <input type="hidden" name="order_id" id="reviewOrderId">
                    <div class="mb-3">
                        <label class="form-label">Avaliação</label>
                        <div class="rating-stars">
                            <?php for ($i = 1; $i <= 5; $i++) : ?>
                                <i class="fas fa-star" data-rating="<?= $i ?>"></i>
                            <?php endfor; ?>
                            <input type="hidden" name="rating" id="selectedRating" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="reviewComment" class="form-label">Comentário (Opcional)</label>
                        <textarea class="form-control" id="reviewComment" name="comment" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Enviar Avaliação</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Filtros
    $(document).ready(function() {
        $('[data-filter]').click(function(e) {
            e.preventDefault();
            const filter = $(this).data('filter');
            window.location.href = `/client/orders?filter=${filter}`;
        });

        // Exportar
        $('#exportBtn').click(function() {
            window.location.href = `/client/orders/export?filter=<?= $currentFilter ?? 'all' ?>`;
        });
    });

    // Cancelamento de pedido
    let cancelOrderId = null;

    function confirmCancel(orderId) {
        cancelOrderId = orderId;
        $('#cancelModal').modal('show');
    }

    $('#confirmCancelBtn').click(function() {
        const reason = $('#cancelReason').val();
        if (!reason) {
            alert('Por favor, informe o motivo do cancelamento.');
            return;
        }
        window.location.href = `/client/orders/cancel/${cancelOrderId}?reason=${encodeURIComponent(reason)}`;
    });

    // Avaliação de pedido
    function openReviewModal(orderId) {
        $('#reviewOrderId').val(orderId);
        $('.rating-stars i').removeClass('active');
        $('#selectedRating').val('');
        $('#reviewComment').val('');
        $('#reviewModal').modal('show');
    }

    $('.rating-stars i').hover(function() {
        const rating = $(this).data('rating');
        $(this).prevAll().addBack().addClass('hover');
    }, function() {
        $('.rating-stars i').removeClass('hover');
    });

    $('.rating-stars i').click(function() {
        const rating = $(this).data('rating');
        $('#selectedRating').val(rating);
        $('.rating-stars i').removeClass('active hover');
        $(this).prevAll().addBack().addClass('active');
    });

    // Validação do formulário de avaliação
    $('#reviewForm').validate({
        errorPlacement: function(error, element) {
            if (element.attr('name') === 'rating') {
                error.insertAfter('.rating-stars');
            } else {
                error.insertAfter(element);
            }
        }
    });
</script>

<style>
    .rating-stars {
        font-size: 24px;
        color: #ddd;
        cursor: pointer;
    }
    .rating-stars i.hover,
    .rating-stars i.active {
        color: #ffc107;
    }
</style>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>