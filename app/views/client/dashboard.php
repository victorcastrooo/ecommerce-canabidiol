<?php require_once __DIR__ . '/../../../partials/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../../../partials/sidebar-client.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Meu Painel</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <span class="btn btn-sm btn-outline-secondary"><?= date('d/m/Y') ?></span>
                    </div>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])) : ?>
                <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <!-- Alertas importantes -->
            <?php if (!$client['anvisa_approved'] && $needsAnvisaApproval) : ?>
                <div class="alert alert-warning">
                    <strong>Atenção!</strong> Você precisa <a href="/client/anvisa/upload" class="alert-link">enviar sua aprovação da ANVISA</a> para comprar produtos regulamentados.
                </div>
            <?php endif; ?>

            <!-- Cards Resumo -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card text-white bg-primary mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Pedidos Ativos</h6>
                                    <h2 class="card-text"><?= $dashboardData['active_orders'] ?></h2>
                                </div>
                                <i class="fas fa-shopping-bag fa-3x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-success mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Total Gasto</h6>
                                    <h2 class="card-text">R$ <?= number_format($dashboardData['total_spent'], 2, ',', '.') ?></h2>
                                </div>
                                <i class="fas fa-wallet fa-3x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-info mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Produtos Favoritos</h6>
                                    <h2 class="card-text"><?= $dashboardData['favorite_products'] ?></h2>
                                </div>
                                <i class="fas fa-heart fa-3x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Abas -->
            <ul class="nav nav-tabs mb-4" id="dashboardTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab" aria-controls="orders" aria-selected="true">
                        Meus Pedidos
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="prescriptions-tab" data-bs-toggle="tab" data-bs-target="#prescriptions" type="button" role="tab" aria-controls="prescriptions" aria-selected="false">
                        Minhas Receitas
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="anvisa-tab" data-bs-toggle="tab" data-bs-target="#anvisa" type="button" role="tab" aria-controls="anvisa" aria-selected="false">
                        Aprovações ANVISA
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="dashboardTabContent">
                <!-- TAB PEDIDOS -->
                <div class="tab-pane fade show active" id="orders" role="tabpanel" aria-labelledby="orders-tab">
                    <?php if (empty($recentOrders)) : ?>
                        <div class="alert alert-info">
                            Você ainda não fez nenhum pedido. <a href="/products" class="alert-link">Conheça nossos produtos</a>
                        </div>
                    <?php else : ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Data</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Rastreio</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentOrders as $order) : ?>
                                        <tr>
                                            <td><?= $order['codigo'] ?></td>
                                            <td><?= date('d/m/Y', strtotime($order['data_pedido'])) ?></td>
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
                                                <a href="/client/orders/<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> Detalhes
                                                </a>
                                                <?php if ($order['status'] === 'pendente') : ?>
                                                    <a href="/client/orders/cancel/<?= $order['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Tem certeza que deseja cancelar este pedido?')">
                                                        <i class="fas fa-times"></i> Cancelar
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="/client/orders" class="btn btn-outline-primary">Ver todos os pedidos</a>
                    <?php endif; ?>
                </div>

                <!-- TAB RECEITAS -->
                <div class="tab-pane fade" id="prescriptions" role="tabpanel" aria-labelledby="prescriptions-tab">
                    <?php if (empty($prescriptions)) : ?>
                        <div class="alert alert-info">
                            Você ainda não enviou nenhuma receita. <a href="/client/prescriptions/upload" class="alert-link">Envie sua primeira receita</a>
                        </div>
                    <?php else : ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Médico</th>
                                        <th>CRM</th>
                                        <th>Data</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($prescriptions as $prescription) : ?>
                                        <tr>
                                            <td><?= $prescription['nome_medico'] ?></td>
                                            <td><?= $prescription['crm_medico'] ?>/<?= $prescription['uf_crm'] ?></td>
                                            <td><?= date('d/m/Y', strtotime($prescription['data_upload'])) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $prescription['aprovada'] ? 'success' : ($prescription['motivo_rejeicao'] ? 'danger' : 'warning') ?>">
                                                    <?= $prescription['aprovada'] ? 'Aprovada' : ($prescription['motivo_rejeicao'] ? 'Rejeitada' : 'Pendente') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="/uploads/prescriptions/<?= basename($prescription['arquivo_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-download"></i> Visualizar
                                                </a>
                                                <?php if (!$prescription['aprovada'] && !$prescription['motivo_rejeicao']) : ?>
                                                    <a href="/client/prescriptions/reupload/<?= $prescription['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-upload"></i> Reenviar
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="/client/prescriptions/upload" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Enviar Nova Receita
                        </a>
                    <?php endif; ?>
                </div>

                <!-- TAB ANVISA -->
                <div class="tab-pane fade" id="anvisa" role="tabpanel" aria-labelledby="anvisa-tab">
                    <?php if (empty($anvisaApprovals)) : ?>
                        <div class="alert alert-info">
                            Você ainda não enviou nenhuma aprovação da ANVISA. <a href="/client/anvisa/upload" class="alert-link">Envie sua aprovação</a>
                        </div>
                    <?php else : ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Número do Registro</th>
                                        <th>Data Validade</th>
                                        <th>Status</th>
                                        <th>Motivo Rejeição</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($anvisaApprovals as $approval) : ?>
                                        <tr>
                                            <td><?= $approval['numero_registro'] ?></td>
                                            <td><?= date('d/m/Y', strtotime($approval['data_validade'])) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $approval['aprovado'] ? 'success' : ($approval['motivo_rejeicao'] ? 'danger' : 'warning') ?>">
                                                    <?= $approval['aprovado'] ? 'Aprovado' : ($approval['motivo_rejeicao'] ? 'Rejeitado' : 'Pendente') ?>
                                                </span>
                                            </td>
                                            <td><?= $approval['motivo_rejeicao'] ?? 'N/A' ?></td>
                                            <td>
                                                <a href="/uploads/anvisa-approvals/<?= basename($approval['arquivo_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-download"></i> Visualizar
                                                </a>
                                                <?php if (!$approval['aprovado'] && !$approval['motivo_rejeicao']) : ?>
                                                    <a href="/client/anvisa/reupload/<?= $approval['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-upload"></i> Reenviar
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="/client/anvisa/upload" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Enviar Nova Aprovação
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>