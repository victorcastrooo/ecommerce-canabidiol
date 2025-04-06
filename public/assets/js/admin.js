/**
 * Admin Panel JavaScript
 * Handles all admin-specific functionality for the cannabis e-commerce system
 */

document.addEventListener('DOMContentLoaded', function() {
    initAdminDashboard();
    initApprovalSystems();
    initProductManagement();
    initVendorApprovals();
    initDoctorApprovals();
    initAnvisaApprovals();
    initPrescriptionApprovals();
    initDataTables();
    initCharts();
});

// ==================== ADMIN DASHBOARD ====================
function initAdminDashboard() {
    // Quick stats toggle
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.addEventListener('click', function() {
            const target = this.dataset.target;
            if (target) {
                document.querySelectorAll('.data-section').forEach(section => {
                    section.classList.add('hidden');
                });
                document.querySelector(target).classList.remove('hidden');
            }
        });
    });

    // Date range picker for reports
    const dateRangePicker = document.getElementById('date-range-picker');
    if (dateRangePicker) {
        flatpickr(dateRangePicker, {
            mode: 'range',
            dateFormat: 'd/m/Y',
            locale: 'pt',
            onChange: function(selectedDates) {
                if (selectedDates.length === 2) {
                    loadDashboardData(selectedDates[0], selectedDates[1]);
                }
            }
        });
    }
}

// ==================== APPROVAL SYSTEMS ====================
function initApprovalSystems() {
    // Tab switching for approval sections
    const approvalTabs = document.querySelectorAll('.approval-tab');
    approvalTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const target = this.dataset.target;
            approvalTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            document.querySelectorAll('.approval-content').forEach(content => {
                content.classList.add('hidden');
            });
            document.querySelector(target).classList.remove('hidden');
        });
    });

    // Approve/Reject buttons with modals
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('approve-btn')) {
            const itemId = e.target.dataset.id;
            const type = e.target.dataset.type;
            showApprovalModal(itemId, type, 'approve');
        }
        
        if (e.target.classList.contains('reject-btn')) {
            const itemId = e.target.dataset.id;
            const type = e.target.dataset.type;
            showApprovalModal(itemId, type, 'reject');
        }
    });
}

function showApprovalModal(id, type, action) {
    const modal = document.getElementById('approval-modal');
    const modalTitle = modal.querySelector('.modal-title');
    const modalForm = modal.querySelector('form');
    
    // Set modal content based on action
    if (action === 'approve') {
        modalTitle.textContent = `Aprovar ${getTypeName(type)}`;
        modalForm.innerHTML = `
            <input type="hidden" name="action" value="approve">
            <input type="hidden" name="id" value="${id}">
            <input type="hidden" name="type" value="${type}">
            <div class="form-group">
                <label>Confirma a aprovação?</label>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success">Confirmar Aprovação</button>
            </div>
        `;
    } else {
        modalTitle.textContent = `Rejeitar ${getTypeName(type)}`;
        modalForm.innerHTML = `
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="id" value="${id}">
            <input type="hidden" name="type" value="${type}">
            <div class="form-group">
                <label for="rejectReason">Motivo da Rejeição</label>
                <textarea class="form-control" id="rejectReason" name="reason" required></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger">Confirmar Rejeição</button>
            </div>
        `;
    }
    
    // Show modal
    $(modal).modal('show');
    
    // Handle form submission
    modalForm.onsubmit = function(e) {
        e.preventDefault();
        processApproval(new FormData(this));
        $(modal).modal('hide');
    };
}

function getTypeName(type) {
    const types = {
        'vendor': 'Vendedor',
        'doctor': 'Médico',
        'anvisa': 'Documento ANVISA',
        'prescription': 'Receita Médica'
    };
    return types[type] || type;
}

async function processApproval(formData) {
    try {
        // Simulate API call - replace with actual fetch()
        const response = await mockApiCall(formData);
        
        if (response.success) {
            showNotification(response.message, 'success');
            // Refresh the approval list
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showNotification(response.message, 'error');
        }
    } catch (error) {
        console.error('Approval error:', error);
        showNotification('Ocorreu um erro ao processar a solicitação', 'error');
    }
}

// ==================== PRODUCT MANAGEMENT ====================
function initProductManagement() {
    // Product image upload preview
    const productImageInput = document.getElementById('product-image');
    if (productImageInput) {
        productImageInput.addEventListener('change', function() {
            const preview = document.getElementById('product-image-preview');
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }

    // Inventory management
    const inventoryForm = document.getElementById('inventory-form');
    if (inventoryForm) {
        inventoryForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            // Simulate API call - replace with actual fetch()
            mockApiCall(formData)
                .then(response => {
                    if (response.success) {
                        showNotification('Estoque atualizado com sucesso', 'success');
                        updateInventoryDisplay(formData.get('quantity'));
                    } else {
                        showNotification(response.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Inventory error:', error);
                    showNotification('Erro ao atualizar estoque', 'error');
                });
        });
    }
}

function updateInventoryDisplay(newQuantity) {
    const quantityDisplay = document.getElementById('current-quantity');
    if (quantityDisplay) {
        quantityDisplay.textContent = newQuantity;
    }
}

// ==================== VENDOR APPROVALS ====================
function initVendorApprovals() {
    // Vendor detail toggles
    const vendorRows = document.querySelectorAll('.vendor-row');
    vendorRows.forEach(row => {
        row.addEventListener('click', function() {
            const details = this.nextElementSibling;
            details.classList.toggle('hidden');
            this.querySelector('.toggle-icon').textContent = 
                details.classList.contains('hidden') ? '+' : '-';
        });
    });
}

// ==================== DOCTOR APPROVALS ====================
function initDoctorApprovals() {
    // Doctor CRM verification
    const crmInputs = document.querySelectorAll('input[name="crm"]');
    crmInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value) {
                verifyCRM(this.value, this.dataset.uf);
            }
        });
    });
}

async function verifyCRM(crm, uf) {
    try {
        // Simulate API call - replace with actual fetch()
        const response = await mockApiCall({ crm, uf });
        
        if (response.valid) {
            showNotification('CRM verificado com sucesso', 'success');
        } else {
            showNotification('CRM não encontrado ou inválido', 'warning');
        }
    } catch (error) {
        console.error('CRM verification error:', error);
    }
}

// ==================== ANVISA APPROVALS ====================
function initAnvisaApprovals() {
    // Document preview
    const docPreviewLinks = document.querySelectorAll('.doc-preview-link');
    docPreviewLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            showDocumentPreview(this.href, 'Documento ANVISA');
        });
    });
}

// ==================== PRESCRIPTION APPROVALS ====================
function initPrescriptionApprovals() {
    // Prescription preview
    const prescriptionLinks = document.querySelectorAll('.prescription-link');
    prescriptionLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            showDocumentPreview(this.href, 'Receita Médica');
        });
    });
}

function showDocumentPreview(url, title) {
    const modal = document.getElementById('document-preview-modal');
    const modalTitle = modal.querySelector('.modal-title');
    const modalBody = modal.querySelector('.modal-body');
    
    modalTitle.textContent = title;
    modalBody.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Carregando...</span>
            </div>
        </div>
    `;
    
    // Show modal immediately
    $(modal).modal('show');
    
    // Load content after a delay (simulating network request)
    setTimeout(() => {
        if (url.endsWith('.pdf')) {
            modalBody.innerHTML = `
                <embed src="${url}" type="application/pdf" width="100%" height="500px">
            `;
        } else {
            modalBody.innerHTML = `
                <img src="${url}" alt="Document Preview" class="img-fluid">
            `;
        }
    }, 800);
}

// ==================== DATA TABLES ====================
function initDataTables() {
    // Initialize DataTables for all tables with the 'data-table' class
    $('.data-table').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Portuguese-Brasil.json'
        },
        responsive: true,
        dom: '<"top"f>rt<"bottom"lip><"clear">',
        pageLength: 25
    });
}

// ==================== CHARTS ====================
function initCharts() {
    // Sales Chart
    const salesCtx = document.getElementById('sales-chart');
    if (salesCtx) {
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul'],
                datasets: [{
                    label: 'Vendas (R$)',
                    data: [12000, 19000, 15000, 18000, 22000, 25000, 28000],
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 2,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'R$ ' + context.raw.toLocaleString('pt-BR');
                            }
                        }
                    }
                },
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
    }

    // Order Status Chart
    const statusCtx = document.getElementById('status-chart');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Aprovados', 'Pendentes', 'Cancelados', 'Enviados'],
                datasets: [{
                    data: [45, 15, 5, 35],
                    backgroundColor: [
                        '#4bc0c0',
                        '#ffcd56',
                        '#ff6384',
                        '#36a2eb'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        });
    }
}

// ==================== HELPER FUNCTIONS ====================
async function mockApiCall(data) {
    // Simulate network delay
    await new Promise(resolve => setTimeout(resolve, 800));
    
    // Return mock response based on action
    if (data.get('action') === 'approve') {
        return {
            success: true,
            message: `${getTypeName(data.get('type'))} aprovado com sucesso`
        };
    } else if (data.get('action') === 'reject') {
        return {
            success: true,
            message: `${getTypeName(data.get('type'))} rejeitado com sucesso`
        };
    } else if (data.get('crm')) {
        return {
            valid: Math.random() > 0.3 // 70% chance of being valid
        };
    } else {
        return {
            success: true,
            message: 'Operação realizada com sucesso'
        };
    }
}

function showNotification(message, type = 'info') {
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
}