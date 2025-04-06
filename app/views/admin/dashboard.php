<?php include __DIR__ . '/../partials/header.php'; ?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
        <a href="/admin/reports" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-download fa-sm text-white-50"></i> Gerar Relatório
        </a>
    </div>

    <!-- Cards de Estatísticas -->
    <div class="row">
        <!-- Vendedores -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Vendedores Cadastrados</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($stats['total_vendors']) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-store fa-2x text-gray-300"></i>
                        </div>
                    </div>
                    <div class="mt-2 text-right">
                        <a href="/admin/vendors" class="text-xs text-primary">Ver todos</a>
                        <?php if ($stats['pending_vendors'] > 0): ?>
                            <span class="badge badge-danger ml-2">
                                <?= $stats['pending_vendors'] ?> pendentes
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Clientes -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Clientes Cadastrados</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($stats['total_clients']) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                    <div class="mt-2 text-right">
                        <a href="/admin/clients" class="text-xs text-success">Ver todos</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Receitas Pendentes -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Receitas Pendentes</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($stats['pending_prescriptions']) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-prescription fa-2x text-gray-300"></i>
                        </div>
                    </div>
                    <div class="mt-2 text-right">
                        <a href="/admin/prescriptions" class="text-xs text-warning">Aprovar</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ANVISA Pendentes -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Liberações ANVISA</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($stats['pending_anvisa']) ?> pendentes
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-certificate fa-2x text-gray-300"></i>
                        </div>
                    </div>
                    <div class="mt-2 text-right">
                        <a href="/admin/anvisa" class="text-xs text-info">Verificar</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos e Tabelas -->
    <div class="row">
        <!-- Gráfico de Vendas -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Vendas Recentes</h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in"
                            aria-labelledby="dropdownMenuLink">
                            <div class="dropdown-header">Opções:</div>
                            <a class="dropdown-item" href="/admin/orders">Ver Todos</a>
                            <a class="dropdown-item" href="/admin/reports/sales">Gerar Relatório</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vendedores Recentes -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Vendedores Recentes</h6>
                    <a href="/admin/vendors" class="btn btn-sm btn-primary">Ver Todos</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentVendors as $vendor): ?>
                                <tr>
                                    <td><?= htmlspecialchars($vendor->razao_social) ?></td>
                                    <td>
                                        <?php if ($vendor->aprovado): ?>
                                            <span class="badge badge-success">Aprovado</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Pendente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="/admin/vendors/view/<?= $vendor->id ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pedidos Recentes -->
    <div class="row">
        <div class="col-12">
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
                            <div class="dropdown-header">Filtrar:</div>
                            <a class="dropdown-item" href="/admin/orders?status=pending">Pendentes</a>
                            <a class="dropdown-item" href="/admin/orders?status=processing">Em Processamento</a>
                            <a class="dropdown-item" href="/admin/orders?status=shipped">Enviados</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="/admin/orders">Ver Todos</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>Nº Pedido</th>
                                    <th>Cliente</th>
                                    <th>Vendedor</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Data</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td><?= $order->codigo ?></td>
                                    <td><?= htmlspecialchars($order->client->nome) ?></td>
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
                                    <td><?= date('d/m/Y', strtotime($order->data_pedido)) ?></td>
                                    <td>
                                        <a href="/admin/orders/view/<?= $order->id ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($order->status === 'pending'): ?>
                                            <a href="/admin/orders/approve/<?= $order->id ?>" class="btn btn-sm btn-success">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts para gráficos -->
<script src="/assets/js/chart.min.js"></script>
<script>
// Gráfico de Vendas
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('salesChart').getContext('2d');
    var salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
            datasets: [{
                label: 'Vendas em R$',
                data: [12000, 19000, 15000, 18000, 21000, 25000, 22000, 24000, 28000, 30000, 32000, 35000],
                backgroundColor: 'rgba(78, 115, 223, 0.05)',
                borderColor: 'rgba(78, 115, 223, 1)',
                pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                borderWidth: 2
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'R$ ' + value.toLocaleString('pt-BR');
                        }
                    }
                }
            },
            tooltips: {
                callbacks: {
                    label: function(tooltipItem, data) {
                        return 'R$ ' + tooltipItem.yLabel.toLocaleString('pt-BR');
                    }
                }
            }
        }
    });
});
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>