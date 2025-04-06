<?php include __DIR__ . '/../../../partials/header.php'; ?>

<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Gerenciamento de Produtos</h1>
        <a href="/admin/products/create" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Cadastrar Produto
        </a>
    </div>

    <!-- Filtros -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filtrar Produtos</h6>
        </div>
        <div class="card-body">
            <form method="get" class="form-inline">
                <div class="form-group mr-2 mb-2">
                    <label for="search" class="sr-only">Busca</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Nome ou código" value="<?= htmlspecialchars($search ?? '') ?>">
                </div>
                <div class="form-group mr-2 mb-2">
                    <label for="status" class="sr-only">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="all" <?= ($status ?? 'all') === 'all' ? 'selected' : '' ?>>Todos os Status</option>
                        <option value="active" <?= ($status ?? 'all') === 'active' ? 'selected' : '' ?>>Ativos</option>
                        <option value="inactive" <?= ($status ?? 'all') === 'inactive' ? 'selected' : '' ?>>Inativos</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary mb-2">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
                <a href="/admin/products" class="btn btn-secondary mb-2 ml-2">
                    <i class="fas fa-sync-alt"></i> Limpar
                </a>
            </form>
        </div>
    </div>

    <!-- Tabela de Produtos -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Produtos Cadastrados</h6>
            <div class="dropdown no-arrow">
                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink"
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in"
                    aria-labelledby="dropdownMenuLink">
                    <div class="dropdown-header">Exportar:</div>
                    <a class="dropdown-item" href="/admin/products/export?format=csv&<?= http_build_query($_GET) ?>">CSV</a>
                    <a class="dropdown-item" href="/admin/products/export?format=excel&<?= http_build_query($_GET) ?>">Excel</a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="productsTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th width="100">Imagem</th>
                            <th>Nome</th>
                            <th>Categoria</th>
                            <th>Preço</th>
                            <th>Estoque</th>
                            <th>Status</th>
                            <th width="120">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($products) > 0): ?>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td class="text-center">
                                    <?php if ($product->imagem_principal): ?>
                                        <img src="<?= $product->imagem_principal ?>" 
                                             alt="<?= htmlspecialchars($product->nome) ?>" 
                                             class="img-thumbnail" style="max-height: 60px;">
                                    <?php else: ?>
                                        <span class="text-muted">Sem imagem</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($product->nome) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($product->principio_ativo) ?> - <?= htmlspecialchars($product->concentracao) ?></small>
                                </td>
                                <td><?= htmlspecialchars($product->categoria->nome ?? 'N/A') ?></td>
                                <td>R$ <?= number_format($product->preco, 2, ',', '.') ?></td>
                                <td>
                                    <?php if ($product->estoque->quantidade <= $product->estoque->quantidade_minima): ?>
                                        <span class="badge badge-warning">
                                            <?= $product->estoque->quantidade ?> (mín: <?= $product->estoque->quantidade_minima ?>)
                                        </span>
                                    <?php else: ?>
                                        <?= $product->estoque->quantidade ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($product->ativo): ?>
                                        <span class="badge badge-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Inativo</span>
                                    <?php endif; ?>
                                    <?php if ($product->requer_receita): ?>
                                        <span class="badge badge-info mt-1 d-block">Requer receita</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="/admin/products/edit/<?= $product->id ?>" 
                                           class="btn btn-sm btn-primary" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="/admin/products/stock/<?= $product->id ?>" 
                                           class="btn btn-sm btn-info" title="Estoque">
                                            <i class="fas fa-boxes"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-sm <?= $product->ativo ? 'btn-warning' : 'btn-success' ?> toggle-status" 
                                                title="<?= $product->ativo ? 'Desativar' : 'Ativar' ?>"
                                                data-id="<?= $product->id ?>">
                                            <i class="fas <?= $product->ativo ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-box-open fa-3x mb-3"></i><br>
                                        Nenhum produto encontrado
                                    </div>
                                    <a href="/admin/products/create" class="btn btn-primary mt-2">
                                        <i class="fas fa-plus"></i> Cadastrar primeiro produto
                                    </a>
                                </td>
                            </tr>
                        <?php endif; ?>
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

<!-- Modal de Confirmação -->
<div class="modal fade" id="confirmModal" tabindex="-1" role="dialog" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmModalLabel">Alterar Status do Produto</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja <span id="actionText"></span> este produto?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <form id="toggleStatusForm" method="post" action="/admin/products/toggle-status">
                    <input type="hidden" name="id" id="productId">
                    <button type="submit" class="btn btn-primary">Confirmar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../partials/footer.php'; ?>

<script>
// Toggle de status com confirmação
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.toggle-status').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            const isActive = this.classList.contains('btn-warning');
            
            document.getElementById('productId').value = productId;
            document.getElementById('actionText').textContent = isActive ? 'desativar' : 'ativar';
            
            $('#confirmModal').modal('show');
        });
    });

    // DataTable
    $('#productsTable').DataTable({
        responsive: true,
        paging: false,
        info: false,
        searching: false,
        ordering: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Portuguese-Brasil.json'
        },
        columnDefs: [
            { orderable: false, targets: [0, 6] }
        ]
    });
});
</script>