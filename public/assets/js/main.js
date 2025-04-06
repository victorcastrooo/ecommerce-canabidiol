/**
 * Cannabis E-commerce Main JavaScript File
 * Handles core frontend functionality for all user types
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize modules based on current page
    initCartFunctionality();
    initFormValidations();
    initMobileMenu();
    initProductInteractions();
    initOrderTracking();
    initAnvisaUpload();
    initPrescriptionUpload();
    initNotificationSystem();
});

// ==================== CART FUNCTIONALITY ====================
function initCartFunctionality() {
    if (!document.querySelector('.cart-container')) return;

    const cart = {
        items: JSON.parse(localStorage.getItem('cartItems')) || [],
        updateCartDisplay: function() {
            const countElement = document.getElementById('cart-item-count');
            const totalElement = document.getElementById('cart-total');
            
            if (countElement) {
                const itemCount = this.items.reduce((total, item) => total + item.quantity, 0);
                countElement.textContent = itemCount;
                countElement.style.display = itemCount > 0 ? 'block' : 'none';
            }
            
            if (totalElement) {
                const total = this.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                totalElement.textContent = `R$ ${total.toFixed(2).replace('.', ',')}`;
            }
        },
        addItem: function(productId, productName, price, quantity = 1) {
            const existingItem = this.items.find(item => item.id === productId);
            
            if (existingItem) {
                existingItem.quantity += quantity;
            } else {
                this.items.push({
                    id: productId,
                    name: productName,
                    price: price,
                    quantity: quantity
                });
            }
            
            this.saveCart();
            this.updateCartDisplay();
            showNotification('Produto adicionado ao carrinho', 'success');
        },
        removeItem: function(productId) {
            this.items = this.items.filter(item => item.id !== productId);
            this.saveCart();
            this.updateCartDisplay();
            showNotification('Produto removido do carrinho', 'info');
        },
        updateQuantity: function(productId, newQuantity) {
            const item = this.items.find(item => item.id === productId);
            if (item) {
                item.quantity = parseInt(newQuantity);
                this.saveCart();
                this.updateCartDisplay();
            }
        },
        saveCart: function() {
            localStorage.setItem('cartItems', JSON.stringify(this.items));
        },
        clearCart: function() {
            this.items = [];
            this.saveCart();
            this.updateCartDisplay();
        }
    };

    // Event Delegation for Cart Actions
    document.addEventListener('click', function(e) {
        // Add to Cart
        if (e.target.closest('.add-to-cart')) {
            const button = e.target.closest('.add-to-cart');
            const productId = button.dataset.productId;
            const productName = button.dataset.productName;
            const price = parseFloat(button.dataset.productPrice);
            const quantity = parseInt(button.dataset.quantity || 1);
            
            cart.addItem(productId, productName, price, quantity);
        }
        
        // Remove from Cart
        if (e.target.closest('.remove-from-cart')) {
            const button = e.target.closest('.remove-from-cart');
            const productId = button.dataset.productId;
            cart.removeItem(productId);
        }
    });

    // Quantity Changes
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('cart-item-quantity')) {
            const input = e.target;
            const productId = input.dataset.productId;
            const newQuantity = input.value;
            
            if (newQuantity > 0) {
                cart.updateQuantity(productId, newQuantity);
            } else {
                cart.removeItem(productId);
            }
        }
    });

    // Initialize cart display
    cart.updateCartDisplay();
}

// ==================== FORM VALIDATIONS ====================
function initFormValidations() {
    // Common validation patterns
    const validations = {
        required: value => value.trim() !== '',
        email: value => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),
        cpf: value => /^\d{3}\.\d{3}\.\d{3}\-\d{2}$/.test(value),
        cnpj: value => /^\d{2}\.\d{3}\.\d{3}\/\d{4}\-\d{2}$/.test(value),
        cep: value => /^\d{5}-\d{3}$/.test(value),
        phone: value => /^\(\d{2}\) \d{4,5}-\d{4}$/.test(value),
        date: value => /^\d{2}\/\d{2}\/\d{4}$/.test(value),
        anvisaNumber: value => /^[A-Z]{2}\d{6}$/.test(value),
        crm: value => /^\d{4,6}$/.test(value)
    };

    // Validate on form submission
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (!form.classList.contains('needs-validation')) return;
        
        let isValid = true;
        const inputs = form.querySelectorAll('[data-validation]');
        
        inputs.forEach(input => {
            const rules = input.dataset.validation.split('|');
            let inputValid = true;
            
            for (const rule of rules) {
                if (!validations[rule](input.value)) {
                    inputValid = false;
                    break;
                }
            }
            
            if (inputValid) {
                input.classList.remove('is-invalid');
                input.classList.add('is-valid');
            } else {
                input.classList.remove('is-valid');
                input.classList.add('is-invalid');
                isValid = false;
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            e.stopPropagation();
            
            // Scroll to first invalid input
            const firstInvalid = form.querySelector('.is-invalid');
            if (firstInvalid) {
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
        
        form.classList.add('was-validated');
    });
}

// ==================== MOBILE MENU ====================
function initMobileMenu() {
    const menuToggle = document.getElementById('mobile-menu-toggle');
    const menu = document.getElementById('mobile-menu');
    
    if (menuToggle && menu) {
        menuToggle.addEventListener('click', function() {
            menu.classList.toggle('hidden');
            menuToggle.classList.toggle('open');
        });
    }
}

// ==================== PRODUCT INTERACTIONS ====================
function initProductInteractions() {
    // Product Image Gallery
    const mainImage = document.querySelector('.product-main-image');
    const thumbnails = document.querySelectorAll('.product-thumbnail');
    
    if (mainImage && thumbnails.length) {
        thumbnails.forEach(thumb => {
            thumb.addEventListener('click', function() {
                mainImage.src = this.src.replace('-thumb', '');
                thumbnails.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            });
        });
    }
    
    // Product Quantity Selector
    document.querySelectorAll('.quantity-selector').forEach(selector => {
        const minus = selector.querySelector('.quantity-minus');
        const plus = selector.querySelector('.quantity-plus');
        const input = selector.querySelector('.quantity-input');
        
        minus.addEventListener('click', () => {
            const value = parseInt(input.value);
            if (value > 1) input.value = value - 1;
        });
        
        plus.addEventListener('click', () => {
            const value = parseInt(input.value);
            input.value = value + 1;
        });
    });
}

// ==================== ORDER TRACKING ====================
function initOrderTracking() {
    const orderTrackingForm = document.getElementById('order-tracking-form');
    
    if (orderTrackingForm) {
        orderTrackingForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const orderId = this.querySelector('input[name="order_id"]').value;
            const orderCode = this.querySelector('input[name="order_code"]').value;
            
            // Simulate API call - replace with actual fetch()
            setTimeout(() => {
                const trackingInfo = document.getElementById('tracking-info');
                if (trackingInfo) {
                    trackingInfo.innerHTML = `
                        <div class="tracking-progress">
                            <div class="step completed">
                                <span class="step-icon">✓</span>
                                <span class="step-text">Pedido Recebido</span>
                            </div>
                            <div class="step ${orderId % 2 ? 'completed' : 'active'}">
                                <span class="step-icon">${orderId % 2 ? '✓' : '↻'}</span>
                                <span class="step-text">Em Preparação</span>
                            </div>
                            <div class="step">
                                <span class="step-icon">◯</span>
                                <span class="step-text">Enviado</span>
                            </div>
                            <div class="step">
                                <span class="step-icon">◯</span>
                                <span class="step-text">Entregue</span>
                            </div>
                        </div>
                        <div class="tracking-details">
                            <p><strong>Código de Rastreio:</strong> TRX${orderCode.toUpperCase()}</p>
                            <p><strong>Transportadora:</strong> Trix Express</p>
                            <p><strong>Previsão de Entrega:</strong> ${new Date(Date.now() + 5 * 24 * 60 * 60 * 1000).toLocaleDateString('pt-BR')}</p>
                        </div>
                    `;
                    trackingInfo.style.display = 'block';
                }
            }, 1000);
        });
    }
}

// ==================== ANVISA UPLOAD ====================
function initAnvisaUpload() {
    const anvisaUpload = document.getElementById('anvisa-upload');
    
    if (anvisaUpload) {
        const fileInput = anvisaUpload.querySelector('input[type="file"]');
        const preview = anvisaUpload.querySelector('.document-preview');
        const fileName = anvisaUpload.querySelector('.file-name');
        
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                
                // Validate file type (PDF or image)
                const validTypes = ['application/pdf', 'image/jpeg', 'image/png'];
                if (!validTypes.includes(file.type)) {
                    showNotification('Por favor, envie um PDF ou imagem (JPEG/PNG)', 'error');
                    this.value = '';
                    return;
                }
                
                // Validate file size (max 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    showNotification('O arquivo deve ter menos de 5MB', 'error');
                    this.value = '';
                    return;
                }
                
                // Show preview
                fileName.textContent = file.name;
                
                if (file.type.includes('image')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                    };
                    reader.readAsDataURL(file);
                } else {
                    preview.innerHTML = '<div class="pdf-preview">PDF Document</div>';
                }
                
                preview.style.display = 'block';
            }
        });
    }
}

// ==================== PRESCRIPTION UPLOAD ====================
function initPrescriptionUpload() {
    const prescriptionUpload = document.getElementById('prescription-upload');
    
    if (prescriptionUpload) {
        const fileInput = prescriptionUpload.querySelector('input[type="file"]');
        const preview = prescriptionUpload.querySelector('.document-preview');
        const fileName = prescriptionUpload.querySelector('.file-name');
        const crmInput = prescriptionUpload.querySelector('input[name="crm_medico"]');
        const ufInput = prescriptionUpload.querySelector('select[name="uf_crm"]');
        
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                
                // Validate file type (PDF or image)
                const validTypes = ['application/pdf', 'image/jpeg', 'image/png'];
                if (!validTypes.includes(file.type)) {
                    showNotification('Por favor, envie um PDF ou imagem (JPEG/PNG)', 'error');
                    this.value = '';
                    return;
                }
                
                // Validate file size (max 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    showNotification('O arquivo deve ter menos de 5MB', 'error');
                    this.value = '';
                    return;
                }
                
                // Show preview
                fileName.textContent = file.name;
                
                if (file.type.includes('image')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                    };
                    reader.readAsDataURL(file);
                } else {
                    preview.innerHTML = '<div class="pdf-preview">PDF Document</div>';
                }
                
                preview.style.display = 'block';
            }
        });
        
        // Auto-fill doctor name if CRM exists in system
        if (crmInput && ufInput) {
            crmInput.addEventListener('blur', async function() {
                if (this.value && ufInput.value) {
                    try {
                        // Simulate API call - replace with actual fetch()
                        const doctors = {
                            '1234/SP': 'Dr. João Silva',
                            '5678/RJ': 'Dra. Maria Oliveira',
                            '9012/MG': 'Dr. Carlos Souza'
                        };
                        
                        const key = `${this.value}/${ufInput.value}`;
                        if (doctors[key]) {
                            const nameInput = prescriptionUpload.querySelector('input[name="nome_medico"]');
                            if (nameInput) {
                                nameInput.value = doctors[key];
                                showNotification('Médico reconhecido no sistema', 'success');
                            }
                        }
                    } catch (error) {
                        console.error('Error fetching doctor info:', error);
                    }
                }
            });
        }
    }
}

// ==================== NOTIFICATION SYSTEM ====================
function initNotificationSystem() {
    window.showNotification = function(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <span class="notification-message">${message}</span>
            <span class="notification-close">&times;</span>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            notification.classList.add('fade-out');
            setTimeout(() => notification.remove(), 300);
        }, 5000);
        
        // Manual close
        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.classList.add('fade-out');
            setTimeout(() => notification.remove(), 300);
        });
    };
}

// ==================== HELPER FUNCTIONS ====================
function formatCurrency(value) {
    return value.toFixed(2).replace('.', ',').replace(/(\d)(?=(\d{3})+\,)/g, '$1.');
}

function debounce(func, wait) {
    let timeout;
    return function() {
        const context = this, args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), wait);
    };
}