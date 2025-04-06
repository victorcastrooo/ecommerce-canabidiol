<?php require_once __DIR__ . '/../../../partials/header.php'; ?>

<div class="container-fluid">
    <div class="row">

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Painel do Vendedor</h1>
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

            <?php if (!$vendor['aprovado']) : ?>
                <div class="alert alert-warning">
                    <strong>Atenção!</strong> Sua conta ainda não foi aprovada. Você não poderá vender produtos até que um administrador aprove seu cadastro.
                </div>
            <?php endif; ?>

            <!-- Cards Resumo -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card text-white bg-primary mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Vendas Hoje</h6>
                                    <h2 class="card-text"><?= $dashboardData['sales_today'] ?></h2>
                                </div>
                                <i class="fas fa-shopping-cart fa-3x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-success mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Comissões Disponíveis</h6>
                                    <h2 class="card-text">R$ <?= number_format($dashboardData['available_commissions'], 2, ',', '.') ?></h2>
                                </div>
                                <i class="fas fa-hand-holding-usd fa-3x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-info mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Médicos Cadastrados</h6>
                                    <h2 class="card-text"><?= $dashboardData['registered_doctors'] ?></h2>
                                </div>
                                <i class="fas fa-user-md fa-3x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos e Tabelas -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Vendas dos Últimos 7 Dias</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="salesChart" height="250"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Status dos Pedidos</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="ordersChart" height="250"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Últimos Pedidos</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Data</th>
                                            <th>Cliente</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentOrders as $order) : ?>
                                            <tr>
                                                <td><?= $order['codigo'] ?></td>
                                                <td><?= date('d/m/Y', strtotime($order['data_pedido'])) ?></td>
                                                <td><?= $order['cliente']['nome'] ?></td>
                                                <td>R$ <?= number_format($order['total'], 2, ',', '.') ?></td>
                                                <td>
                                                    <span class="badge bg-<?= getOrderStatusBadge($order['status']) ?>">
                                                        <?= ucfirst($order['status']) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <a href="/vendor/sales" class="btn btn-sm btn-outline-primary">Ver todos</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Comissões Pendentes</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>Pedido</th>
                                            <th>Médico</th>
                                            <th>Valor</th>
                                            <th>Disponível em</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pendingCommissions as $commission) : ?>
                                            <tr>
                                                <td>#<?= $commission['pedido']['codigo'] ?></td>
                                                <td><?= $commission['medico']['nome'] ?? 'N/A' ?></td>
                                                <td>R$ <?= number_format($commission['valor_comissao'], 2, ',', '.') ?></td>
                                                <td><?= date('d/m/Y', strtotime($commission['data_disponibilidade'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <a href="/vendor/commissions" class="btn btn-sm btn-outline-primary">Ver todas</a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    // Gráfico de Vendas
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_keys($salesData)) ?>,
            datasets: [{
                label: 'Vendas (R$)',
                data: <?= json_encode(array_values($salesData)) ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                tension: 0.1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'R$ ' + value.toLocaleString('pt-BR');
                        }
                    }
                }
            }
        }
    });

    // Gráfico de Status de Pedidos
    const ordersCtx = document.getElementById('ordersChart').getContext('2d');
    const ordersChart = new Chart(ordersCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_keys($ordersStatusData)) ?>,
            datasets: [{
                data: <?= json_encode(array_values($ordersStatusData)) ?>,
                backgroundColor: [
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(153, 102, 255, 0.7)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
</script>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>