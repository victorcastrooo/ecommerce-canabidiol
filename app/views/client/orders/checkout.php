<?php require_once __DIR__ . '/../../../partials/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../../../partials/sidebar-client.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Finalizar Compra</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="/cart" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Voltar ao Carrinho
                    </a>
                </div>
            </div>

            <?php if (isset($_SESSION['checkout_errors'])) : ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($_SESSION['checkout_errors'] as $error) : ?>
                            <li><?= $error ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php unset($_SESSION['checkout_errors']); ?>
            <?php endif; ?>

            <?php if (empty($cartItems)) : ?>
                <div class="alert alert-warning">
                    Seu carrinho está vazio. <a href="/products" class="alert-link">Continue comprando</a>
                </div>
            <?php else : ?>
                <div class="row">
                    <!-- Etapas do Checkout -->
                    <div class="col-md-3 mb-4">
                        <div class="list-group">
                            <a href="#" class="list-group-item list-group-item-action active" id="step1-tab" data-bs-toggle="tab" data-bs-target="#step1">
                                <i class="fas fa-user me-2"></i> Informações
                            </a>
                            <a href="#" class="list-group-item list-group-item-action disabled" id="step2-tab">
                                <i class="fas fa-truck me-2"></i> Entrega
                            </a>
                            <a href="#" class="list-group-item list-group-item-action disabled" id="step3-tab">
                                <i class="fas fa-credit-card me-2"></i> Pagamento
                            </a>
                            <a href="#" class="list-group-item list-group-item-action disabled" id="step4-tab">
                                <i class="fas fa-check me-2"></i> Confirmação
                            </a>
                        </div>
                    </div>

                    <!-- Conteúdo das Etapas -->
                    <div class="col-md-9">
                        <form id="checkoutForm" method="POST" action="/client/orders/process-checkout">
                            <div class="tab-content" id="checkoutTabContent">
                                <!-- ETAPA 1: Informações -->
                                <div class="tab-pane fade show active" id="step1" role="tabpanel" aria-labelledby="step1-tab">
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h5 class="mb-0">Informações Pessoais</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="name" class="form-label">Nome Completo <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="name" name="name" value="<?= $client['nome'] ?>" required>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="cpf" class="form-label">CPF <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="cpf" name="cpf" value="<?= $client['cpf_cnpj'] ?>" required>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="email" class="form-label">E-mail <span class="text-danger">*</span></label>
                                                <input type="email" class="form-control" id="email" name="email" value="<?= $client['email'] ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="phone" class="form-label">Telefone <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="phone" name="phone" value="<?= $client['telefone'] ?>" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end">
                                        <button type="button" class="btn btn-primary" onclick="nextStep(2)">
                                            Próximo <i class="fas fa-arrow-right ms-2"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- ETAPA 2: Entrega -->
                                <div class="tab-pane fade" id="step2" role="tabpanel" aria-labelledby="step2-tab">
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h5 class="mb-0">Endereço de Entrega</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="shipping_address" id="defaultAddress" value="default" checked>
                                                    <label class="form-check-label" for="defaultAddress">
                                                        Usar endereço cadastrado
                                                    </label>
                                                </div>
                                                <div class="card bg-light p-3 mt-2">
                                                    <p class="mb-1"><strong>Endereço:</strong> <?= $client['endereco_logradouro'] ?>, <?= $client['endereco_numero'] ?></p>
                                                    <p class="mb-1"><strong>Complemento:</strong> <?= $client['endereco_complemento'] ?: 'N/A' ?></p>
                                                    <p class="mb-1"><strong>Bairro:</strong> <?= $client['endereco_bairro'] ?></p>
                                                    <p class="mb-1"><strong>Cidade/UF:</strong> <?= $client['endereco_cidade'] ?>/<?= $client['endereco_estado'] ?></p>
                                                    <p class="mb-0"><strong>CEP:</strong> <?= $client['endereco_cep'] ?></p>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="shipping_address" id="newAddress" value="new">
                                                    <label class="form-check-label" for="newAddress">
                                                        Usar outro endereço
                                                    </label>
                                                </div>
                                            </div>

                                            <div id="newAddressFields" style="display: none;">
                                                <div class="row">
                                                    <div class="col-md-5 mb-3">
                                                        <label for="cep" class="form-label">CEP <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" id="cep" name="cep">
                                                    </div>
                                                    <div class="col-md-7 mb-3">
                                                        <label for="street" class="form-label">Logradouro <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" id="street" name="street">
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-2 mb-3">
                                                        <label for="number" class="form-label">Número <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" id="number" name="number">
                                                    </div>
                                                    <div class="col-md-5 mb-3">
                                                        <label for="complement" class="form-label">Complemento</label>
                                                        <input type="text" class="form-control" id="complement" name="complement">
                                                    </div>
                                                    <div class="col-md-5 mb-3">
                                                        <label for="neighborhood" class="form-label">Bairro <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" id="neighborhood" name="neighborhood">
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label for="city" class="form-label">Cidade <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" id="city" name="city">
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label for="state" class="form-label">Estado <span class="text-danger">*</span></label>
                                                        <select class="form-select" id="state" name="state">
                                                            <option value="">Selecione</option>
                                                            <?php foreach (BRAZIL_STATES as $uf => $state) : ?>
                                                                <option value="<?= $uf ?>"><?= $uf ?> - <?= $state ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="shipping_method" class="form-label">Método de Entrega <span class="text-danger">*</span></label>
                                                <select class="form-select" id="shipping_method" name="shipping_method" required>
                                                    <option value="">Selecione a forma de envio</option>
                                                    <?php foreach ($shippingOptions as $option) : ?>
                                                        <option value="<?= $option['id'] ?>" data-price="<?= $option['price'] ?>">
                                                            <?= $option['name'] ?> - R$ <?= number_format($option['price'], 2, ',', '.') ?> (<?= $option['delivery_time'] ?> dias úteis)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between">
                                        <button type="button" class="btn btn-secondary" onclick="prevStep(1)">
                                            <i class="fas fa-arrow-left me-2"></i> Voltar
                                        </button>
                                        <button type="button" class="btn btn-primary" onclick="nextStep(3)">
                                            Próximo <i class="fas fa-arrow-right ms-2"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- ETAPA 3: Pagamento -->
                                <div class="tab-pane fade" id="step3" role="tabpanel" aria-labelledby="step3-tab">
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h5 class="mb-0">Método de Pagamento</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="payment_method" id="creditCard" value="credit_card" checked>
                                                    <label class="form-check-label" for="creditCard">
                                                        Cartão de Crédito
                                                    </label>
                                                </div>
                                                <div id="creditCardFields" class="mt-3 p-3 border rounded">
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label for="card_number" class="form-label">Número do Cartão <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control" id="card_number" name="card_number">
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label for="card_name" class="form-label">Nome no Cartão <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control" id="card_name" name="card_name">
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-4 mb-3">
                                                            <label for="card_expiry" class="form-label">Validade (MM/AA) <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control" id="card_expiry" name="card_expiry">
                                                        </div>
                                                        <div class="col-md-4 mb-3">
                                                            <label for="card_cvv" class="form-label">CVV <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control" id="card_cvv" name="card_cvv">
                                                        </div>
                                                        <div class="col-md-4 mb-3">
                                                            <label for="card_installments" class="form-label">Parcelas <span class="text-danger">*</span></label>
                                                            <select class="form-select" id="card_installments" name="card_installments">
                                                                <option value="1">1x de R$ <?= number_format($cartTotal + ($shippingOptions[0]['price'] ?? 0), 2, ',', '.') ?> sem juros</option>
                                                                <?php for ($i = 2; $i <= 12; $i++) : ?>
                                                                    <option value="<?= $i ?>">
                                                                        <?= $i ?>x de R$ <?= number_format(($cartTotal + ($shippingOptions[0]['price'] ?? 0)) / $i, 2, ',', '.') ?> 
                                                                        <?= $i > 6 ? 'com juros' : 'sem juros' ?>
                                                                    </option>
                                                                <?php endfor; ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="payment_method" id="pix" value="pix">
                                                    <label class="form-check-label" for="pix">
                                                        PIX
                                                    </label>
                                                </div>
                                                <div id="pixFields" class="mt-3 p-3 border rounded" style="display: none;">
                                                    <p>Você receberá um QR Code para pagamento após confirmar o pedido.</p>
                                                    <p class="text-success"><i class="fas fa-bolt"></i> Pagamento instantâneo com 5% de desconto</p>
                                                </div>
                                            </div>

                                            <?php if ($needsPrescription) : ?>
                                                <div class="alert alert-warning mt-4">
                                                    <h5 class="alert-heading">Atenção!</h5>
                                                    <p>Este pedido contém produtos que exigem receita médica.</p>
                                                    <div class="mb-3">
                                                        <label for="prescription_id" class="form-label">Selecione uma receita válida <span class="text-danger">*</span></label>
                                                        <select class="form-select" id="prescription_id" name="prescription_id" required>
                                                            <option value="">Selecione uma receita</option>
                                                            <?php foreach ($validPrescriptions as $prescription) : ?>
                                                                <option value="<?= $prescription['id'] ?>">
                                                                    Receita do Dr. <?= $prescription['nome_medico'] ?> (<?= date('d/m/Y', strtotime($prescription['data_upload'])) ?>)
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <a href="/client/prescriptions/upload" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-upload"></i> Enviar Nova Receita
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between">
                                        <button type="button" class="btn btn-secondary" onclick="prevStep(2)">
                                            <i class="fas fa-arrow-left me-2"></i> Voltar
                                        </button>
                                        <button type="button" class="btn btn-primary" onclick="nextStep(4)">
                                            Próximo <i class="fas fa-arrow-right ms-2"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- ETAPA 4: Confirmação -->
                                <div class="tab-pane fade" id="step4" role="tabpanel" aria-labelledby="step4-tab">
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h5 class="mb-0">Resumo do Pedido</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h6>Informações Pessoais</h6>
                                                    <p id="summaryName"></p>
                                                    <p id="summaryEmail"></p>
                                                    <p id="summaryPhone"></p>

                                                    <h6 class="mt-4">Endereço de Entrega</h6>
                                                    <p id="summaryAddress"></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6>Método de Entrega</h6>
                                                    <p id="summaryShipping"></p>

                                                    <h6 class="mt-4">Método de Pagamento</h6>
                                                    <p id="summaryPayment"></p>
                                                </div>
                                            </div>

                                            <hr>

                                            <h5>Itens do Pedido</h5>
                                            <div class="table-responsive">
                                                <table class="table">
                                                    <thead>
                                                        <tr>
                                                            <th>Produto</th>
                                                            <th>Quantidade</th>
                                                            <th>Preço Unitário</th>
                                                            <th>Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($cartItems as $item) : ?>
                                                            <tr>
                                                                <td><?= $item['produto']['nome'] ?></td>
                                                                <td><?= $item['quantidade'] ?></td>
                                                                <td>R$ <?= number_format($item['produto']['preco'], 2, ',', '.') ?></td>
                                                                <td>R$ <?= number_format($item['quantidade'] * $item['produto']['preco'], 2, ',', '.') ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                    <tfoot>
                                                        <tr>
                                                            <th colspan="3">Subtotal</th>
                                                            <th>R$ <?= number_format($cartTotal, 2, ',', '.') ?></th>
                                                        </tr>
                                                        <tr>
                                                            <th colspan="3">Frete</th>
                                                            <th id="summaryShippingCost">-</th>
                                                        </tr>
                                                        <tr>
                                                            <th colspan="3">Total</th>
                                                            <th id="summaryTotal">R$ <?= number_format($cartTotal, 2, ',', '.') ?></th>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>

                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                                                <label class="form-check-label" for="terms">
                                                    Li e aceito os <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Termos de Compra</a>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between">
                                        <button type="button" class="btn btn-secondary" onclick="prevStep(3)">
                                            <i class="fas fa-arrow-left me-2"></i> Voltar
                                        </button>
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-check me-2"></i> Confirmar Pedido
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Modal Termos de Compra -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termsModalLabel">Termos de Compra</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php include __DIR__ . '/../../../partials/purchase-terms.php'; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Máscaras
        $('#cpf').inputmask('999.999.999-99');
        $('#phone').inputmask('(99) 99999-9999');
        $('#cep').inputmask('99999-999');
        $('#card_number').inputmask('9999 9999 9999 9999');
        $('#card_expiry').inputmask('99/99');
        $('#card_cvv').inputmask('999');

        // Alternar entre endereços
        $('input[name="shipping_address"]').change(function() {
            if ($(this).val() === 'new') {
                $('#newAddressFields').show();
            } else {
                $('#newAddressFields').hide();
            }
        });

        // Alternar entre métodos de pagamento
        $('input[name="payment_method"]').change(function() {
            $('#creditCardFields, #pixFields').hide();
            $(`#${this.value}_fields`).show();
        });

        // Busca CEP
        $('#cep').blur(function() {
            const cep = $(this).val().replace(/\D/g, '');
            if (cep.length === 8) {
                $.getJSON(`https://viacep.com.br/ws/${cep}/json/`, function(data) {
                    if (!data.erro) {
                        $('#street').val(data.logradouro);
                        $('#neighborhood').val(data.bairro);
                        $('#city').val(data.localidade);
                        $('#state').val(data.uf);
                        $('#number').focus();
                    }
                });
            }
        });

        // Atualizar parcelas quando mudar o frete
        $('#shipping_method').change(function() {
            updateInstallments();
        });
    });

    // Navegação entre etapas
    function nextStep(step) {
        // Validar etapa atual antes de avançar
        if (step === 2 && !validateStep1()) return;
        if (step === 3 && !validateStep2()) return;
        if (step === 4 && !validateStep3()) return;

        $(`#step${step-1}-tab`).removeClass('active').addClass('disabled');
        $(`#step${step}-tab`).removeClass('disabled').addClass('active');
        
        $(`#step${step-1}`).removeClass('show active');
        $(`#step${step}`).addClass('show active');

        // Atualizar resumo na etapa 4
        if (step === 4) {
            updateOrderSummary();
        }
    }

    function prevStep(step) {
        $(`#step${step+1}-tab`).removeClass('active').addClass('disabled');
        $(`#step${step}-tab`).removeClass('disabled').addClass('active');
        
        $(`#step${step+1}`).removeClass('show active');
        $(`#step${step}`).addClass('show active');
    }

    // Validações das etapas
    function validateStep1() {
        let valid = true;
        const required = ['name', 'cpf', 'email', 'phone'];
        
        required.forEach(field => {
            if (!$(`#${field}`).val()) {
                $(`#${field}`).addClass('is-invalid');
                valid = false;
            } else {
                $(`#${field}`).removeClass('is-invalid');
            }
        });

        return valid;
    }

    function validateStep2() {
        if (!$('#shipping_method').val()) {
            $('#shipping_method').addClass('is-invalid');
            return false;
        }
        $('#shipping_method').removeClass('is-invalid');
        return true;
    }

    function validateStep3() {
        const method = $('input[name="payment_method"]:checked').val();
        let valid = true;

        if (method === 'credit_card') {
            const required = ['card_number', 'card_name', 'card_expiry', 'card_cvv'];
            required.forEach(field => {
                if (!$(`#${field}`).val()) {
                    $(`#${field}`).addClass('is-invalid');
                    valid = false;
                } else {
                    $(`#${field}`).removeClass('is-invalid');
                }
            });
        }

        <?php if ($needsPrescription) : ?>
            if (!$('#prescription_id').val()) {
                $('#prescription_id').addClass('is-invalid');
                valid = false;
            } else {
                $('#prescription_id').removeClass('is-invalid');
            }
        <?php endif; ?>

        return valid;
    }

    // Atualizar resumo do pedido
    function updateOrderSummary() {
        // Informações pessoais
        $('#summaryName').text($('#name').val());
        $('#summaryEmail').text($('#email').val());
        $('#summaryPhone').text($('#phone').val());

        // Endereço
        if ($('input[name="shipping_address"]:checked').val() === 'default') {
            $('#summaryAddress').html(`
                ${$('#street').val()}, ${$('#number').val()}<br>
                ${$('#complement').val() ? $('#complement').val() + '<br>' : ''}
                ${$('#neighborhood').val()}<br>
                ${$('#city').val()} - ${$('#state').val()}<br>
                CEP: ${$('#cep').val()}
            `);
        } else {
            $('#summaryAddress').html(`
                <?= $client['endereco_logradouro'] ?>, <?= $client['endereco_numero'] ?><br>
                <?= $client['endereco_complemento'] ? $client['endereco_complemento'] . '<br>' : '' ?>
                <?= $client['endereco_bairro'] ?><br>
                <?= $client['endereco_cidade'] ?> - <?= $client['endereco_estado'] ?><br>
                CEP: <?= $client['endereco_cep'] ?>
            `);
        }

        // Entrega
        const shippingOption = $('#shipping_method option:selected');
        $('#summaryShipping').text(`${shippingOption.text()}`);
        $('#summaryShippingCost').text(`R$ ${shippingOption.data('price').toFixed(2).replace('.', ',')}`);

        // Pagamento
        const paymentMethod = $('input[name="payment_method"]:checked').val();
        if (paymentMethod === 'credit_card') {
            $('#summaryPayment').html(`
                Cartão de Crédito<br>
                ${$('#card_name').val()}<br>
                Termina em ${$('#card_number').val().slice(-4)}<br>
                ${$('#card_installments option:selected').text()}
            `);
        } else {
            $('#summaryPayment').text('PIX (Pagamento instantâneo)');
        }

        // Total
        const shippingCost = parseFloat(shippingOption.data('price')) || 0;
        const total = <?= $cartTotal ?> + shippingCost;
        $('#summaryTotal').text(`R$ ${total.toFixed(2).replace('.', ',')}`);
    }

    // Atualizar opções de parcelamento
    function updateInstallments() {
        const shippingCost = parseFloat($('#shipping_method option:selected').data('price')) || 0;
        const total = <?= $cartTotal ?> + shippingCost;
        
        $('#card_installments').empty();
        $('#card_installments').append(`<option value="1">1x de R$ ${total.toFixed(2).replace('.', ',')} sem juros</option>`);
        
        for (let i = 2; i <= 12; i++) {
            const installmentValue = total / i;
            $('#card_installments').append(
                `<option value="${i}">${i}x de R$ ${installmentValue.toFixed(2).replace('.', ',')} ${i > 6 ? 'com juros' : 'sem juros'}</option>`
            );
        }
    }
</script>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>