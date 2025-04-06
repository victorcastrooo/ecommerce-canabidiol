</main><!-- End of main content container -->

<!-- Footer -->
<footer class="bg-dark text-white pt-5 pb-4">
    <div class="container">
        <div class="row">
            <!-- Company Info -->
            <div class="col-lg-4 col-md-6 mb-4">
                <h5 class="text-uppercase mb-4">
                    <i class="fas fa-cannabis me-2"></i> Canabidiol Commerce
                </h5>
                <p class="text-muted">
                    Plataforma especializada em produtos à base de canabidiol com qualidade farmacêutica e acompanhamento médico.
                </p>
                <div class="mt-4">
                    <a href="#" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-white me-3"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6 mb-4">
                <h5 class="text-uppercase mb-4">Links Rápidos</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="/products" class="text-muted">Produtos</a></li>
                    <li class="mb-2"><a href="/blog" class="text-muted">Blog</a></li>
                    <li class="mb-2"><a href="/about" class="text-muted">Sobre Nós</a></li>
                    <li class="mb-2"><a href="/faq" class="text-muted">FAQ</a></li>
                    <li class="mb-2"><a href="/contact" class="text-muted">Contato</a></li>
                </ul>
            </div>

            <!-- Legal Links -->
            <div class="col-lg-2 col-md-6 mb-4">
                <h5 class="text-uppercase mb-4">Jurídico</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="/terms" class="text-muted">Termos de Uso</a></li>
                    <li class="mb-2"><a href="/privacy" class="text-muted">Política de Privacidade</a></li>
                    <li class="mb-2"><a href="/shipping" class="text-muted">Política de Envios</a></li>
                    <li class="mb-2"><a href="/returns" class="text-muted">Trocas e Devoluções</a></li>
                    <li class="mb-2"><a href="/anvisa" class="text-muted">Regulamento ANVISA</a></li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div class="col-lg-4 col-md-6 mb-4">
                <h5 class="text-uppercase mb-4">Contato</h5>
                <ul class="list-unstyled text-muted">
                    <li class="mb-3">
                        <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                        Av. das Plantas Medicinais, 1234<br>
                        São Paulo/SP - CEP 01234-567
                    </li>
                    <li class="mb-3">
                        <i class="fas fa-phone-alt me-2 text-primary"></i>
                        (11) 98765-4321
                    </li>
                    <li class="mb-3">
                        <i class="fas fa-envelope me-2 text-primary"></i>
                        contato@canabidiolcommerce.com.br
                    </li>
                    <li class="mb-3">
                        <i class="fas fa-clock me-2 text-primary"></i>
                        Seg-Sex: 9:00 - 18:00
                    </li>
                </ul>
            </div>
        </div>

        <hr class="mb-4">

        <!-- Payment Methods -->
        <div class="row align-items-center">
            <div class="col-md-6 mb-3">
                <h6 class="text-uppercase mb-3">Formas de Pagamento</h6>
                <div class="payment-methods">
                    <img src="/assets/images/payments/visa.png" alt="Visa" class="img-thumbnail me-1" width="40">
                    <img src="/assets/images/payments/mastercard.png" alt="Mastercard" class="img-thumbnail me-1" width="40">
                    <img src="/assets/images/payments/amex.png" alt="American Express" class="img-thumbnail me-1" width="40">
                    <img src="/assets/images/payments/boleto.png" alt="Boleto" class="img-thumbnail me-1" width="40">
                    <img src="/assets/images/payments/pix.png" alt="PIX" class="img-thumbnail me-1" width="40">
                </div>
            </div>
            <div class="col-md-6 mb-3 text-md-end">
                <h6 class="text-uppercase mb-3">Certificações</h6>
                <div class="certifications">
                    <img src="/assets/images/certifications/anvisa.png" alt="ANVISA" class="img-thumbnail me-1" width="60">
                    <img src="/assets/images/certifications/ssl.png" alt="SSL Secure" class="img-thumbnail me-1" width="60">
                </div>
            </div>
        </div>

        <!-- Copyright -->
        <div class="text-center pt-3">
            <p class="small text-muted mb-0">
                &copy; <?= date('Y') ?> Canabidiol Commerce. Todos os direitos reservados.
                <br class="d-md-none">
                CNPJ: 12.345.678/0001-90
            </p>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<a href="#" class="btn btn-primary btn-lg back-to-top" role="button">
    <i class="fas fa-arrow-up"></i>
</a>

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script src="/assets/js/main.js"></script>
<?php if (isset($isAdmin) && $isAdmin): ?>
    <script src="/assets/js/admin.js"></script>
<?php endif; ?>

<!-- Page-specific JS -->
<?php if (isset($scripts)): ?>
    <?php foreach ($scripts as $script): ?>
        <script src="<?= $script ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=GA_MEASUREMENT_ID"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'GA_MEASUREMENT_ID');
</script>
</body>
</html>