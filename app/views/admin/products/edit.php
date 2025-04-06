<?php include __DIR__ . '/../../../partials/header.php'; ?>

<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <?= isset($product->id) ? 'Editar Produto' : 'Cadastrar Novo Produto' ?>
        </h1>
        <a href="/admin/products" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Voltar para lista
        </a>
    </div>

    <!-- Formulário -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Informações Básicas</h6>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['flash']['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= $_SESSION['flash']['error'] ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?= $product->id ?? '' ?>">

                        <div class="form-group row">
                            <label for="nome" class="col-sm-3 col-form-label">Nome do Produto *</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control <?= isset($errors['nome']) ? 'is-invalid' : '' ?>" 
                                       id="nome" name="nome" required
                                       value="<?= htmlspecialchars($product->nome ?? ($_POST['nome'] ?? '')) ?>">
                                <?php if (isset($errors['nome'])): ?>
                                    <div class="invalid-feedback">
                                        <?= $errors['nome'] ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="categoria_id" class="col-sm-3 col-form-label">Categoria *</label>
                            <div class="col-sm-9">
                                <select class="form-control <?= isset($errors['categoria_id']) ? 'is-invalid' : '' ?>" 
                                        id="categoria_id" name="categoria_id" required>
                                    <option value="">Selecione uma categoria</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category->id ?>" 
                                            <?= ($product->categoria_id ?? ($_POST['categoria_id'] ?? '')) == $category->id ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category->nome) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['categoria_id'])): ?>
                                    <div class="invalid-feedback">
                                        <?= $errors['categoria_id'] ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="descricao" class="col-sm-3 col-form-label">Descrição</label>
                            <div class="col-sm-9">
                                <textarea class="form-control <?= isset($errors['descricao']) ? 'is-invalid' : '' ?>" 
                                          id="descricao" name="descricao" rows="3"><?= htmlspecialchars($product->descricao ?? ($_POST['descricao'] ?? '')) ?></textarea>
                                <?php if (isset($errors['descricao'])): ?>
                                    <div class="invalid-feedback">
                                        <?= $errors['descricao'] ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="principio_ativo" class="col-sm-3 col-form-label">Princípio Ativo *</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control <?= isset($errors['principio_ativo']) ? 'is-invalid' : '' ?>" 
                                       id="principio_ativo" name="principio_ativo" required
                                       value="<?= htmlspecialchars($product->principio_ativo ?? ($_POST['principio_ativo'] ?? '')) ?>">
                                <?php if (isset($errors['principio_ativo'])): ?>
                                    <div class="invalid-feedback">
                                        <?= $errors['principio_ativo'] ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="concentracao" class="col-sm-3 col-form-label">Concentração *</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control <?= isset($errors['concentracao']) ? 'is-invalid' : '' ?>" 
                                       id="concentracao" name="concentracao" required
                                       value="<?= htmlspecialchars($product->concentracao ?? ($_POST['concentracao'] ?? '')) ?>">
                                <?php if (isset($errors['concentracao'])): ?>
                                    <div class="invalid-feedback">
                                        <?= $errors['concentracao'] ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="forma_farmaceutica" class="col-sm-3 col-form-label">Forma Farmacêutica *</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control <?= isset($errors['forma_farmaceutica']) ? 'is-invalid' : '' ?>" 
                                       id="forma_farmaceutica" name="forma_farmaceutica" required
                                       value="<?= htmlspecialchars($product->forma_farmaceutica ?? ($_POST['forma_farmaceutica'] ?? '')) ?>">
                                <?php if (isset($errors['forma_farmaceutica'])): ?>
                                    <div class="invalid-feedback">
                                        <?= $errors['forma_farmaceutica'] ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="laboratorio" class="col-sm-3 col-form-label">Laboratório</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control <?= isset($errors['laboratorio']) ? 'is-invalid' : '' ?>" 
                                       id="laboratorio" name="laboratorio"
                                       value="<?= htmlspecialchars($product->laboratorio ?? ($_POST['laboratorio'] ?? '')) ?>">
                                <?php if (isset($errors['laboratorio'])): ?>
                                    <div class="invalid-feedback">
                                        <?= $errors['laboratorio'] ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="codigo_barras" class="col-sm-3 col-form-label">Código de Barras</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control <?= isset($errors['codigo_barras']) ? 'is-invalid' : '' ?>" 
                                       id="codigo_barras" name="codigo_barras"
                                       value="<?= htmlspecialchars($product->codigo_barras ?? ($_POST['codigo_barras'] ?? '')) ?>">
                                <?php if (isset($errors['codigo_barras'])): ?>
                                    <div class="invalid-feedback">
                                        <?= $errors['codigo_barras'] ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="preco" class="col-sm-3 col-form-label">Preço (R$) *</label>
                            <div class="col-sm-9">
                                <input type="number" step="0.01" min="0" 
                                       class="form-control <?= isset($errors['preco']) ? 'is-invalid' : '' ?>" 
                                       id="preco" name="preco" required
                                       value="<?= htmlspecialchars($product->preco ?? ($_POST['preco'] ?? '')) ?>">
                                <?php if (isset($errors['preco'])): ?>
                                    <div class="invalid-feedback">
                                        <?= $errors['preco'] ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-sm-3">Requer Receita?</div>
                            <div class="col-sm-9">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="requer_receita" name="requer_receita" 
                                           value="1" <?= ($product->requer_receita ?? ($_POST['requer_receita'] ?? false)) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="requer_receita">
                                        Sim, este produto requer receita médica
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-sm-3">Status</div>
                            <div class="col-sm-9">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="ativo" name="ativo" 
                                           value="1" <?= ($product->ativo ?? ($_POST['ativo'] ?? true)) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="ativo">
                                        Ativar produto
                                    </label>
                                </div>
                            </div>
                        </div>

                        <hr class="mb-4">

                        <h5 class="mb-3">Dimensões e Peso</h5>

                        <div class="form-group row">
                            <label for="peso_gramas" class="col-sm-3 col-form-label">Peso (g) *</label>
                            <div class="col-sm-9">
                                <input type="number" step="1" min="0" 
                                       class="form-control <?= isset($errors['peso_gramas']) ? 'is-invalid' : '' ?>" 
                                       id="peso_gramas" name="peso_gramas" required
                                       value="<?= htmlspecialchars($product->peso_gramas ?? ($_POST['peso_gramas'] ?? '')) ?>">
                                <?php if (isset($errors['peso_gramas'])): ?>
                                    <div class="invalid-feedback">
                                        <?= $errors['peso_gramas'] ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="largura_cm" class="col-sm-3 col-form-label">Largura (cm) *</label>
                            <div class="col-sm-9">
                                <input type="number" step="0.1" min="0" 
                                       class="form-control <?= isset($errors['largura_cm']) ? 'is-invalid' : '' ?>" 
                                       id="largura_cm" name="largura_cm" required
                                       value="<?= htmlspecialchars($product->largura_cm ?? ($_POST['largura_cm'] ?? '')) ?>">
                                <?php if (isset($errors['largura_cm'])): ?>
                                    <div class="invalid-feedback">
                                        <?= $errors['largura_cm'] ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="altura_cm" class="col-sm-3 col-form-label">Altura (cm) *</label>
                            <div class="col-sm-9">
                                <input type="number" step="0.1" min="0" 
                                       class="form-control <?= isset($errors['altura_cm']) ? 'is-invalid' : '' ?>" 
                                       id="altura_cm" name="altura_cm" required
                                       value="<?= htmlspecialchars($product->altura_cm ?? ($_POST['altura_cm'] ?? '')) ?>">
                                <?php if (isset($errors['altura_cm'])): ?>
                                    <div class="invalid-feedback">
                                        <?= $errors['altura_cm'] ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="profundidade_cm" class="col-sm-3 col-form-label">Profundidade (cm) *</label>
                            <div class="col-sm-9">
                                <input type="number" step="0.1" min="0" 
                                       class="form-control <?= isset($errors['profundidade_cm']) ? 'is-invalid' : '' ?>" 
                                       id="profundidade_cm" name="profundidade_cm" required
                                       value="<?= htmlspecialchars($product->profundidade_cm ?? ($_POST['profundidade_cm'] ?? '')) ?>">
                                <?php if (isset($errors['profundidade_cm'])): ?>
                                    <div class="invalid-feedback">
                                        <?= $errors['profundidade_cm'] ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <hr class="mb-4">

                        <div class="form-group row">
                            <label for="imagem_principal" class="col-sm-3 col-form-label">Imagem Principal</label>
                            <div class="col-sm-9">
                                <?php if (!empty($product->imagem_principal)): ?>
                                    <div class="mb-3">
                                        <img src="<?= $product->imagem_principal ?>" 
                                             alt="Imagem atual do produto" 
                                             class="img-thumbnail" style="max-height: 150px;">
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="remover_imagem" name="remover_imagem" value="1">
                                            <label class="form-check-label text-danger" for="remover_imagem">
                                                Remover imagem atual
                                            </label>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="imagem_principal" name="imagem_principal">
                                    <label class="custom-file-label" for="imagem_principal">Escolher arquivo...</label>
                                </div>
                                <small class="form-text text-muted">
                                    Formatos aceitos: JPG, PNG, WebP (máx. 5MB)
                                </small>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-sm-9 offset-sm-3">
                                <button type="submit" class="btn btn-primary mr-2">
                                    <i class="fas fa-save"></i> Salvar
                                </button>
                                <a href="/admin/products" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Card de Ajuda -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Dicas de Cadastro</h6>
                </div>
                <div class="card-body">
                    <h6 class="font-weight-bold">Informações Obrigatórias</h6>
                    <p class="small">Todos os campos marcados com * são obrigatórios para o cadastro do produto.</p>
                    
                    <h6 class="font-weight-bold mt-4">Princípio Ativo e Concentração</h6>
                    <p class="small">Informe o princípio ativo principal e sua concentração exata conforme a embalagem.</p>
                    
                    <h6 class="font-weight-bold mt-4">Imagem do Produto</h6>
                    <p class="small">Utilize imagens de alta qualidade que mostrem claramente o produto e sua embalagem.</p>
                    
                    <h6 class="font-weight-bold mt-4">Requer Receita</h6>
                    <p class="small">Marque esta opção para produtos que exigem prescrição médica para venda.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../partials/footer.php'; ?>

<script>
// Mostrar nome do arquivo selecionado
document.querySelector('.custom-file-input').addEventListener('change', function(e) {
    var fileName = document.getElementById("imagem_principal").files[0].name;
    var nextSibling = e.target.nextElementSibling;
    nextSibling.innerText = fileName;
});

// Validação do formulário
document.querySelector('form').addEventListener('submit', function(e) {
    let isValid = true;
    const requiredFields = document.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        }
    });
    
    if (!isValid) {
        e.preventDefault();
        alert('Por favor, preencha todos os campos obrigatórios.');
    }
});
</script>