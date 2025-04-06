<?php
namespace App\Config;

class Constants {
    // User Types
    const USER_TYPE_ADMIN = 'admin';
    const USER_TYPE_VENDOR = 'vendedor';
    const USER_TYPE_CLIENT = 'cliente';

    // Order Statuses
    const ORDER_STATUS_CART = 'carrinho';
    const ORDER_STATUS_PENDING_PRESCRIPTION = 'aguardando_receita';
    const ORDER_STATUS_PENDING_APPROVAL = 'aguardando_aprovacao';
    const ORDER_STATUS_APPROVED = 'aprovado';
    const ORDER_STATUS_PENDING_PAYMENT = 'pagamento_pendente';
    const ORDER_STATUS_PAID = 'pago';
    const ORDER_STATUS_PREPARING = 'preparando_envio';
    const ORDER_STATUS_SHIPPED = 'enviado';
    const ORDER_STATUS_DELIVERED = 'entregue';
    const ORDER_STATUS_CANCELLED = 'cancelado';

    // Commission Statuses
    const COMMISSION_PENDING = 'pendente';
    const COMMISSION_AVAILABLE = 'disponivel';
    const COMMISSION_REQUESTED = 'solicitado';
    const COMMISSION_PAID = 'pago';

    // Approval Statuses
    const APPROVAL_PENDING = null;
    const APPROVAL_APPROVED = true;
    const APPROVAL_REJECTED = false;

    // Payment Methods
    const PAYMENT_MERCADO_PAGO = 'mercado_pago';
    const PAYMENT_TRANSFER = 'transferencia';
    const PAYMENT_PIX = 'pix';

    // Shipping Carriers
    const CARRIER_TRIX_EXPRESS = 'trix_express';
    const CARRIER_OTHER = 'outra';

    // Document Types
    const DOCUMENT_ANVISA_APPROVAL = 'anvisa_approval';
    const DOCUMENT_PRESCRIPTION = 'prescription';

    // Gender Options
    const GENDER_MALE = 'masculino';
    const GENDER_FEMALE = 'feminino';
    const GENDER_OTHER = 'outro';

    // Bank Account Types
    const BANK_ACCOUNT_CHECKING = 'corrente';
    const BANK_ACCOUNT_SAVINGS = 'poupanca';

    // Admin Access Levels
    const ADMIN_LEVEL_MASTER = 'master';
    const ADMIN_LEVEL_OPERATIONAL = 'operacional';

    // File Upload Paths
    const UPLOAD_PATHS = [
        'anvisa_approvals' => __DIR__ . '/../../public/uploads/anvisa-approvals/',
        'prescriptions' => __DIR__ . '/../../public/uploads/prescriptions/',
        'products' => __DIR__ . '/../../public/uploads/products/'
    ];

    // Default Commission Rates
    const DEFAULT_VENDOR_COMMISSION = 10.00; // 10%
    const DEFAULT_WITHDRAWAL_FEE = 2.00; // 2%

    // Business Rules
    const COMMISSION_RELEASE_DAYS = 30; // Days before commission becomes available
    const PRESCRIPTION_APPROVAL_HOURS = 48; // Hours to approve prescriptions

    // Pagination
    const ITEMS_PER_PAGE = 15;

    // Security
    const PASSWORD_RESET_EXPIRY = 3600; // 1 hour in seconds
    const ACCOUNT_ACTIVATION_EXPIRY = 86400; // 24 hours in seconds

    // API Configuration
    const API_RATE_LIMIT = 60; // Requests per minute
    const API_RATE_LIMIT_AUTH = 5; // Authentication attempts per minute

    // System Settings
    const APP_NAME = 'Canabidiol Commerce';
    const SUPPORT_EMAIL = 'suporte@canabidiolcommerce.com.br';
    const LEGAL_CNPJ = '00.000.000/0001-00';
    const ANVISA_REGULATION_REFERENCE = 'RDC 327/2019';

    /**
     * Get order status display text
     */
    public static function getOrderStatusText(string $status): string {
        $statuses = [
            self::ORDER_STATUS_CART => 'Carrinho',
            self::ORDER_STATUS_PENDING_PRESCRIPTION => 'Aguardando Receita',
            self::ORDER_STATUS_PENDING_APPROVAL => 'Aguardando Aprovação',
            self::ORDER_STATUS_APPROVED => 'Aprovado',
            self::ORDER_STATUS_PENDING_PAYMENT => 'Pagamento Pendente',
            self::ORDER_STATUS_PAID => 'Pago',
            self::ORDER_STATUS_PREPARING => 'Preparando Envio',
            self::ORDER_STATUS_SHIPPED => 'Enviado',
            self::ORDER_STATUS_DELIVERED => 'Entregue',
            self::ORDER_STATUS_CANCELLED => 'Cancelado'
        ];
        return $statuses[$status] ?? $status;
    }

    /**
     * Get commission status display text
     */
    public static function getCommissionStatusText(string $status): string {
        $statuses = [
            self::COMMISSION_PENDING => 'Pendente',
            self::COMMISSION_AVAILABLE => 'Disponível',
            self::COMMISSION_REQUESTED => 'Solicitado',
            self::COMMISSION_PAID => 'Pago'
        ];
        return $statuses[$status] ?? $status;
    }

    /**
     * Get all valid user types
     */
    public static function getUserTypes(): array {
        return [
            self::USER_TYPE_ADMIN,
            self::USER_TYPE_VENDOR,
            self::USER_TYPE_CLIENT
        ];
    }

    /**
     * Get all valid order statuses
     */
    public static function getOrderStatuses(): array {
        return [
            self::ORDER_STATUS_CART,
            self::ORDER_STATUS_PENDING_PRESCRIPTION,
            self::ORDER_STATUS_PENDING_APPROVAL,
            self::ORDER_STATUS_APPROVED,
            self::ORDER_STATUS_PENDING_PAYMENT,
            self::ORDER_STATUS_PAID,
            self::ORDER_STATUS_PREPARING,
            self::ORDER_STATUS_SHIPPED,
            self::ORDER_STATUS_DELIVERED,
            self::ORDER_STATUS_CANCELLED
        ];
    }
}