<?php include __DIR__ . '/../../../partials/header.php'; ?>

<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Gerenciamento de Vendedores</h1>
        <a href="/admin/vendors/approve" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-user-check fa-sm text-white-50"></i> Aprovar Cadastros
        </a>
    </div>

    <!-- Filtros -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filtrar Vendedores</h6>
        </div>
        <div class="card-body">
            <form method="get" class="form-inline">
                <div class="form-group mr-2 mb-2">
                    <label for="search" class="sr-only">Busca</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Nome ou CNPJ" value="<?= htmlspecialchars($search ?? '') ?>">
                </div>
                <div class="form-group mr-2 mb-2">
                    <label for="status" class="sr-only">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="all" <?= ($status ?? 'all') === 'all' ? 'selected' : '' ?>>Todos</option>
                        <option value="approved" <?= ($status ?? 'all') === 'approved' ? 'selected' : '' ?>>Aprovados</option>
                        <option value="pending" <?= ($status ?? 'all') === 'pending' ? 'selected' : '' ?>>Pendentes</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary mb-2">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
                <a href="/admin/vendors" class="btn btn-secondary mb-2 ml-2">
                    <i class="fas fa-sync-alt"></i> Limpar
                </a>
            </form>
        </div>
    </div>

    <!-- Tabela de Vendedores -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Vendedores Cadastrados</h6>
            <div class="dropdown no-arrow">
                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink"
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in"
                    aria-labelledby="dropdownMenuLink">
                    <div class="dropdown-header">Exportar:</div>
                    <a class="dropdown-item" href="/admin/vendors/export?format=csv&<?= http_build_query($_GET) ?>">CSV</a>
                    <a class="dropdown-item" href="/admin/vendors/export?format=excel&<?= http_build_query($_GET) ?>">Excel</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="/admin/vendors/report">Gerar Relatório</a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="vendorsTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th>Razão Social</th>
                            <th>CNPJ</th>
                            <th>Inscrição Estadual</th>
                            <th>Status</th>
                            <th>Data Cadastro</th>
                            <th width="150">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($vendors) > 0): ?>
                            <?php foreach ($vendors as $vendor): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($vendor->razao_social) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($vendor->user->email) ?></small>
                                </td>
                                <td><?= formatCnpj($vendor->user->cpf_cnpj) ?></td>
                                <td><?= $vendor->inscricao_estadual ?: 'N/A' ?></td>
                                <td>
                                    <?php if ($vendor->aprovado): ?>
                                        <span class="badge badge-success">Aprovado</span><br>
                                        <small><?= date('d/m/Y', strtotime($vendor->data_aprovacao)) ?></small>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Pendente</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d/m/Y', strtotime($vendor->user->data_criacao)) ?></td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="/admin/vendors/view/<?= $vendor->id ?>" 
                                           class="btn btn-sm btn-info" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if (!$vendor->aprovado): ?>
                                            <a href="/admin/vendors/approve/<?= $vendor->id ?>" 
                                               class="btn btn-sm btn-success" title="Aprovar">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-danger delete-vendor" 
                                                title="Remover"
                                                data-id="<?= $vendor->id ?>"
                                                data-name="<?= htmlspecialchars($vendor->razao_social) ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-store-alt fa-3x mb-3"></i><br>
                                        Nenhum vendedor encontrado
                                    </div>
                                    <?php if (($status ?? 'all') === 'pending'): ?>
                                        <a href="/admin/vendors/approve" class="btn btn-primary mt-2">
                                            <i class="fas fa-user-check"></i> Verificar Pendências
                                        </a>
                                    <?php endif; ?>
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
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" role="dialog" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmar Exclusão</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja remover o vendedor <strong id="vendorName"></strong>?</p>
                <p class="text-danger"><small>Esta ação não pode ser desfeita e removerá todos os produtos e dados associados.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <form id="deleteVendorForm" method="post" action="/admin/vendors/delete">
                    <input type="hidden" name="id" id="vendorId">
                    <button type="submit" class="btn btn-danger">Confirmar Exclusão</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../partials/footer.php'; ?>

<script>
// DataTable
$(document).ready(function() {
    $('#vendorsTable').DataTable({
        responsive: true,
        paging: false,
        info: false,
        searching: false,
        ordering: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Portuguese-Brasil.json'
        },
        columnDefs: [
            { orderable: false, targets: [5] }
        ]
    });

    // Modal de confirmação para exclusão
    $('.delete-vendor').on('click', function() {
        const vendorId = $(this).data('id');
        const vendorName = $(this).data('name');
        
        $('#vendorId').val(vendorId);
        $('#vendorName').text(vendorName);
        $('#confirmDeleteModal').modal('show');
    });
});
</script>