<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' : '' ?>Canabidiol Commerce</title>
    
    <!-- Favicon -->
    <link rel="icon" href="/assets/images/favicon.ico" type="image/x-icon">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/main.css">
    <?php if (isset($isAdmin) && $isAdmin): ?>
        <link rel="stylesheet" href="/assets/css/admin.css">
    <?php endif; ?>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- InputMask -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.6/jquery.inputmask.min.js"></script>
    
    <!-- Meta Tags -->
    <meta name="description" content="Plataforma especializada em produtos à base de canabidiol com receituário controlado">
    <meta name="keywords" content="canabidiol, CBD, cannabis medicinal, produtos naturais, saúde">
    
    <!-- Open Graph / Social Media -->
    <meta property="og:title" content="Canabidiol Commerce">
    <meta property="og:description" content="Plataforma especializada em produtos à base de canabidiol">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= isset($_SERVER['HTTPS']) ? 'https' : 'http' ?>://<?= $_SERVER['HTTP_HOST'] ?>">
    <meta property="og:image" content="/assets/images/og-image.jpg">
</head>
<body>
    <!-- Top Alert Bar -->
    <?php if (isset($_SESSION['maintenance_message'])): ?>
        <div class="alert alert-warning alert-dismissible rounded-0 mb-0 text-center">
            <?= $_SESSION['maintenance_message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Main Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <!-- Brand Logo -->
            <a class="navbar-brand" href="/">
                <i class="fas fa-cannabis me-2"></i>
                <span class="fw-bold">Canabidiol</span> Commerce
            </a>
            
            <!-- Mobile Toggle -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Navigation Links -->
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/products"><i class="fas fa-capsules me-1"></i> Produtos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/about"><i class="fas fa-info-circle me-1"></i> Sobre</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/blog"><i class="fas fa-newspaper me-1"></i> Blog</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/contact"><i class="fas fa-envelope me-1"></i> Contato</a>
                    </li>
                </ul>
                
                <!-- Right Side Navigation -->
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- User is logged in -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-1"></i>
                                <?= htmlspecialchars($_SESSION['user_name']) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php if ($_SESSION['user_type'] === 'admin'): ?>
                                    <li><a class="dropdown-item" href="/admin/dashboard"><i class="fas fa-tachometer-alt me-2"></i>Painel Admin</a></li>
                                <?php elseif ($_SESSION['user_type'] === 'vendor'): ?>
                                    <li><a class="dropdown-item" href="/vendor/dashboard"><i class="fas fa-store me-2"></i>Painel Vendedor</a></li>
                                <?php elseif ($_SESSION['user_type'] === 'doctor'): ?>
                                    <li><a class="dropdown-item" href="/doctor/dashboard"><i class="fas fa-user-md me-2"></i>Painel Médico</a></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item" href="/client/dashboard"><i class="fas fa-user me-2"></i>Minha Conta</a></li>
                                <?php endif; ?>
                                
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/orders"><i class="fas fa-shopping-bag me-2"></i>Meus Pedidos</a></li>
                                <li><a class="dropdown-item" href="/prescriptions"><i class="fas fa-file-medical me-2"></i>Minhas Receitas</a></li>
                                <li><a class="dropdown-item" href="/settings"><i class="fas fa-cog me-2"></i>Configurações</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="/auth/logout" method="POST">
                                        <button type="submit" class="dropdown-item">
                                            <i class="fas fa-sign-out-alt me-2"></i>Sair
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                        
                        <!-- Cart Icon -->
                        <li class="nav-item">
                            <a class="nav-link position-relative" href="/cart">
                                <i class="fas fa-shopping-cart"></i>
                                <?php if (isset($_SESSION['cart_count']) && $_SESSION['cart_count'] > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        <?= $_SESSION['cart_count'] ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php else: ?>
                        <!-- User is not logged in -->
                        <li class="nav-item">
                            <a class="nav-link" href="/auth/login"><i class="fas fa-sign-in-alt me-1"></i> Entrar</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/auth/register"><i class="fas fa-user-plus me-1"></i> Cadastrar</a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <!-- Search Form -->
                <form class="d-flex ms-3" action="/search" method="GET">
                    <div class="input-group">
                        <input class="form-control" type="search" name="q" placeholder="Buscar produtos..." aria-label="Search">
                        <button class="btn btn-light" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </nav>

    <!-- Breadcrumb -->
    <?php if (isset($breadcrumbs)): ?>
        <nav aria-label="breadcrumb" class="bg-light py-2">
            <div class="container">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
                    <?php foreach ($breadcrumbs as $crumb): ?>
                        <?php if (isset($crumb['url'])): ?>
                            <li class="breadcrumb-item"><a href="<?= $crumb['url'] ?>"><?= htmlspecialchars($crumb['title']) ?></a></li>
                        <?php else: ?>
                            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($crumb['title']) ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ol>
            </div>
        </nav>
    <?php endif; ?>

    <!-- Main Content Container -->
    <main class="container py-4">
        <!-- Notifications -->
        <?php require_once __DIR__ . '/notifications.php'; ?>