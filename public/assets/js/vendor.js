/**
 * Vendor Portal JavaScript
 * Handles vendor-specific functionality for the cannabis e-commerce system
 */

document.addEventListener('DOMContentLoaded', function() {
    initVendorDashboard();
    initCommissionTracking();
    initDoctorManagement();
    initSalesReports();
    initWithdrawalRequests();
    initDataTables();
    initCharts();
});

// ==================== VENDOR DASHBOARD ====================
function initVendorDashboard() {
    // Quick stats toggle
    const statCards = document.querySelectorAll('.vendor-stat-card');
    statCards.forEach(card => {
        card.addEventListener('click', function() {
            const target = this.dataset.target;
            if (target) {
                document.querySelectorAll('.vendor-data-section').forEach(section => {
                    section.classList.add('hidden');
                });
                document.querySelector(target).classList.remove('hidden');
                
                // Update the active tab style
                document.querySelectorAll('.vendor-nav-link').forEach(link => {
                    link.classList.remove('active');
                });
                document.querySelector(`[href="${target}"]`).classList.add('active');
            }
        });
    });

    // Date range picker for reports
    const dateRangePicker = document.getElementById('vendor-date-range');
    if (dateRangePicker) {
        flatpickr(dateRangePicker, {
            mode: 'range',
            dateFormat: 'd/m/Y',
            locale: 'pt',
            onChange: function(selectedDates) {
                if (selectedDates.length === 2) {
                    loadVendorDashboardData(selectedDates[0], selectedDates[1]);
                }
            }
        });
    }
}

// ==================== COMMISSION TRACKING ====================
function initCommissionTracking() {
    // Commission status filter
    const commissionFilter = document.getElementById('commission-filter');
    if (commissionFilter) {
        commissionFilter.addEventListener('change', function() {
            const status = this.value;
            filterCommissions(status);
        });
    }

    // Commission detail toggles
    const commissionRows = document.querySelectorAll('.commission-row');
    commissionRows.forEach(row => {
        row.addEventListener('click', function() {
            const details = this.nextElementSibling;
            details.classList.toggle('hidden');
            this.querySelector('.toggle-icon').textContent = 
                details.classList.contains('hidden') ? '+' : '-';
        });
    });
}

function filterCommissions(status) {
    const rows = document.querySelectorAll('.commission-row');
    rows.forEach(row => {
        if (status === 'all' || row.dataset.status === status) {
            row.style.display = '';
            // Ensure details are hidden when filtering
            row.nextElementSibling.style.display = 'none';
            row.querySelector('.toggle-icon').textContent = '+';
        } else {
            row.style.display = 'none';
            row.nextElementSibling.style.display = 'none';
        }
    });
}

// ==================== DOCTOR MANAGEMENT ====================
function initDoctorManagement() {
    // Doctor registration form
    const doctorForm = document.getElementById('doctor-registration-form');
    if (doctorForm) {
        doctorForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            // Validate CRM
            if (!validateCRM(formData.get('crm'), formData.get('uf_crm'))) {
                showNotification('CRM inválido ou já cadastrado', 'error');
                return;
            }

            // Simulate API call - replace with actual fetch()
            mockVendorApiCall('register-doctor', formData)
                .then(response => {
                    if (response.success) {
                        showNotification('Médico cadastrado com sucesso! Aguarde aprovação.', 'success');
                        this.reset();
                        // Refresh doctor list
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        showNotification(response.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Doctor registration error:', error);
                    showNotification('Erro ao cadastrar médico', 'error');
                });
        });
    }

    // Doctor status filter
    const doctorFilter = document.getElementById('doctor-filter');
    if (doctorFilter) {
        doctorFilter.addEventListener('change', function() {
            const status = this.value;
            filterDoctors(status);
        });
    }
}

function validateCRM(crm, uf) {
    // Basic validation - replace with actual CRM verification
    return crm.length >= 4 && uf.length === 2;
}

function filterDoctors(status) {
    const rows = document.querySelectorAll('.doctor-row');
    rows.forEach(row => {
        if (status === 'all' || row.dataset.status === status) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// ==================== SALES REPORTS ====================
function initSalesReports() {
    // Sales report period selector
    const reportPeriod = document.getElementById('report-period');
    if (reportPeriod) {
        reportPeriod.addEventListener('change', function() {
            loadSalesReport(this.value);
        });
    }

    // Export buttons
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('export-btn')) {
            const format = e.target.dataset.format;
            exportSalesReport(format);
        }
    });
}

function loadSalesReport(period) {
    // Show loading state
    const reportContainer = document.getElementById('sales-report-container');
    reportContainer.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Carregando...</span>
            </div>
            <p class="mt-2">Gerando relatório...</p>
        </div>
    `;

    // Simulate API call - replace with actual fetch()
    setTimeout(() => {
        mockVendorApiCall('sales-report', { period })
            .then(data => {
                renderSalesReport(data);
            })
            .catch(error => {
                console.error('Sales report error:', error);
                showNotification('Erro ao carregar relatório de vendas', 'error');
            });
    }, 1200);
}

function renderSalesReport(data) {
    const reportContainer = document.getElementById('sales-report-container');
    reportContainer.innerHTML = `
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Pedido</th>
                        <th>Médico</th>
                        <th>Valor</th>
                        <th>Comissão</th>
                    </tr>
                </thead>
                <tbody>
                    ${data.sales.map(sale => `
                        <tr>
                            <td>${sale.date}</td>
                            <td>${sale.order}</td>
                            <td>${sale.doctor || 'N/A'}</td>
                            <td>R$ ${sale.amount.toFixed(2).replace('.', ',')}</td>
                            <td>R$ ${sale.commission.toFixed(2).replace('.', ',')}</td>
                        </tr>
                    `).join('')}
                </tbody>
                <tfoot>
                    <tr class="table-active">
                        <td colspan="3"><strong>Total</strong></td>
                        <td><strong>R$ ${data.totals.sales.toFixed(2).replace('.', ',')}</strong></td>
                        <td><strong>R$ ${data.totals.commissions.toFixed(2).replace('.', ',')}</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    `;
}

function exportSalesReport(format) {
    // Simulate export - in a real app this would download a file
    showNotification(`Relatório exportado como ${format.toUpperCase()}`, 'success');
}

// ==================== WITHDRAWAL REQUESTS ====================
function initWithdrawalRequests() {
    // Withdrawal form
    const withdrawalForm = document.getElementById('withdrawal-form');
    if (withdrawalForm) {
        withdrawalForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const amount = parseFloat(document.getElementById('available-balance').textContent.replace('R$ ', '').replace(',', '.'));
            if (amount <= 0) {
                showNotification('Nenhum valor disponível para saque', 'error');
                return;
            }

            const formData = new FormData(this);
            formData.append('amount', amount);

            // Calculate fee and net amount
            const fee = amount * (parseFloat(this.dataset.fee) / 100);
            const netAmount = amount - fee;
            
            // Show confirmation modal
            showWithdrawalConfirmation(amount, fee, netAmount, formData);
        });
    }

    // Withdrawal history filter
    const withdrawalFilter = document.getElementById('withdrawal-filter');
    if (withdrawalFilter) {
        withdrawalFilter.addEventListener('change', function() {
            const status = this.value;
            filterWithdrawals(status);
        });
    }
}

function showWithdrawalConfirmation(amount, fee, netAmount, formData) {
    const modal = document.getElementById('withdrawal-confirmation-modal');
    const modalBody = modal.querySelector('.modal-body');
    
    modalBody.innerHTML = `
        <div class="confirmation-details">
            <div class="detail-row">
                <span class="label">Valor solicitado:</span>
                <span class="value">R$ ${amount.toFixed(2).replace('.', ',')}</span>
            </div>
            <div class="detail-row">
                <span class="label">Taxa administrativa (${formData.get('fee')}%):</span>
                <span class="value">- R$ ${fee.toFixed(2).replace('.', ',')}</span>
            </div>
            <div class="detail-row total">
                <span class="label">Valor líquido:</span>
                <span class="value">R$ ${netAmount.toFixed(2).replace('.', ',')}</span>
            </div>
            <div class="bank-details">
                <h5>Dados Bancários</h5>
                <p><strong>Banco:</strong> ${formData.get('bank_name')}</p>
                <p><strong>Agência:</strong> ${formData.get('bank_agency')}</p>
                <p><strong>Conta:</strong> ${formData.get('bank_account')}</p>
                <p><strong>Titular:</strong> ${formData.get('account_holder')}</p>
            </div>
        </div>
    `;
    
    // Show modal
    $(modal).modal('show');
    
    // Handle confirmation
    modal.querySelector('.confirm-btn').onclick = function() {
        $(modal).modal('hide');
        
        // Simulate API call - replace with actual fetch()
        mockVendorApiCall('request-withdrawal', formData)
            .then(response => {
                if (response.success) {
                    showNotification('Solicitação de saque enviada com sucesso', 'success');
                    // Refresh withdrawal history
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showNotification(response.message, 'error');
                }
            })
            .catch(error => {
                console.error('Withdrawal error:', error);
                showNotification('Erro ao solicitar saque', 'error');
            });
    };
}

function filterWithdrawals(status) {
    const rows = document.querySelectorAll('.withdrawal-row');
    rows.forEach(row => {
        if (status === 'all' || row.dataset.status === status) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// ==================== DATA TABLES ====================
function initDataTables() {
    // Initialize DataTables for vendor tables
    $('.vendor-data-table').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Portuguese-Brasil.json'
        },
        responsive: true,
        dom: '<"top"f>rt<"bottom"lip><"clear">',
        pageLength: 10
    });
}

// ==================== CHARTS ====================
function initCharts() {
    // Commissions Chart
    const commissionsCtx = document.getElementById('commissions-chart');
    if (commissionsCtx) {
        new Chart(commissionsCtx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
                datasets: [{
                    label: 'Comissões (R$)',
                    data: [1200, 1900, 1500, 1800, 2200, 2500],
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
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

    // Sales by Doctor Chart
    const doctorsCtx = document.getElementById('doctors-chart');
    if (doctorsCtx) {
        new Chart(doctorsCtx, {
            type: 'doughnut',
            data: {
                labels: ['Dr. Silva', 'Dra. Oliveira', 'Dr. Souza', 'Outros'],
                datasets: [{
                    data: [35, 25, 20, 20],
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
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${percentage}% (R$ ${value.toLocaleString('pt-BR')})`;
                            }
                        }
                    }
                }
            }
        });
    }
}

// ==================== HELPER FUNCTIONS ====================
async function mockVendorApiCall(endpoint, data) {
    // Simulate network delay
    await new Promise(resolve => setTimeout(resolve, 800));
    
    // Return mock responses based on endpoint
    switch (endpoint) {
        case 'register-doctor':
            return {
                success: true,
                message: 'Médico cadastrado com sucesso! Aguarde aprovação.'
            };
            
        case 'sales-report':
            return {
                sales: [
                    { date: '15/06/2023', order: 'ORD-12345', doctor: 'Dr. Silva', amount: 450.00, commission: 45.00 },
                    { date: '12/06/2023', order: 'ORD-12344', doctor: 'Dra. Oliveira', amount: 380.00, commission: 38.00 },
                    { date: '10/06/2023', order: 'ORD-12343', doctor: null, amount: 220.00, commission: 22.00 },
                    { date: '08/06/2023', order: 'ORD-12342', doctor: 'Dr. Souza', amount: 510.00, commission: 51.00 },
                    { date: '05/06/2023', order: 'ORD-12341', doctor: 'Dr. Silva', amount: 290.00, commission: 29.00 }
                ],
                totals: {
                    sales: 1850.00,
                    commissions: 185.00
                }
            };
            
        case 'request-withdrawal':
            return {
                success: true,
                message: 'Solicitação de saque enviada com sucesso'
            };
            
        default:
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

// Initialize any third-party libraries when DOM is fully loaded
window.addEventListener('load', function() {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Initialize popovers
    $('[data-toggle="popover"]').popover();
});