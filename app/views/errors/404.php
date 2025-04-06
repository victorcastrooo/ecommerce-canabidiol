<?php
http_response_code(404);
$pageTitle = "Página Não Encontrada";
require_once __DIR__ . '/../../partials/header.php';
?>

<div class="container my-5 py-5">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8 text-center">
            <div class="error-card p-4 p-lg-5 shadow-sm rounded-3 bg-light">
                <div class="error-icon mb-4">
                    <i class="fas fa-map-marked-alt fa-4x text-primary"></i>
                </div>
                
                <h1 class="display-5 fw-bold text-primary mb-4">404 - Página Não Encontrada</h1>
                
                <p class="lead mb-4">
                    O conteúdo que você está procurando não existe ou foi movido.
                </p>
                
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    Você pode tentar uma das opções abaixo ou usar nossa busca:
                </div>
                
                <!-- Search Form -->
                <form action="/search" method="GET" class="mb-4">
                    <div class="input-group input-group-lg">
                        <input type="text" class="form-control" name="q" placeholder="Buscar produtos..." required>
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
                
                <!-- Popular Products Section -->
                <div class="popular-products mb-4">
                    <h5 class="mb-3">Produtos Populares</h5>
                    <div class="row g-3">
                        <?php
                        $popularProducts = [
                            ['name' => 'Óleo de CBD 10%', 'url' => '/produtos/oleo-cbd-10'],
                            ['name' => 'Cápsulas de Canabidiol', 'url' => '/produtos/capsulas-canabidiol'],
                            ['name' => 'Pomada de CBD', 'url' => '/produtos/pomada-cbd'],
                            ['name' => 'Combo Iniciante', 'url' => '/produtos/combo-iniciante']
                        ];
                        
                        foreach ($popularProducts as $product): ?>
                            <div class="col-6 col-md-3">
                                <a href="<?= $product['url'] ?>" class="btn btn-outline-primary w-100">
                                    <?= $product['name'] ?>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Main Action Buttons -->
                <div class="d-flex flex-wrap justify-content-center gap-3 mt-4">
                    <a href="/" class="btn btn-primary btn-lg px-4">
                        <i class="fas fa-home me-2"></i> Página Inicial
                    </a>
                    <a href="/products" class="btn btn-outline-primary btn-lg px-4">
                        <i class="fas fa-capsules me-2"></i> Nossos Produtos
                    </a>
                    <a href="/contact" class="btn btn-outline-secondary btn-lg px-4">
                        <i class="fas fa-envelope me-2"></i> Contato
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.error-card {
    background-color: #f8f9fa;
    border: 1px solid rgba(0,0,0,.125);
}
.error-icon {
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}
.popular-products {
    background-color: rgba(13, 110, 253, 0.05);
    padding: 1.5rem;
    border-radius: 0.5rem;
}
</style>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>