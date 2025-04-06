<?php

namespace App\Config;

use App\Controllers\{
    AuthController,
    AdminController,
    VendorController,
    ClientController,
    ProductController,
    OrderController,
    PaymentController
};
use App\Middleware\{
    AuthMiddleware,
    AdminMiddleware,
    VendorMiddleware,
    ClientMiddleware
};

class Routes {
    private static $routes = [];

    public static function getRoutes(): array {
        self::initializeRoutes();
        return self::$routes;
    }

    private static function initializeRoutes(): void {
        // Authentication Routes (no middleware)
        self::$routes['GET']['/login'] = [
            'controller' => [AuthController::class, 'showLoginForm'],
            'middleware' => []
        ];
        self::$routes['POST']['/login'] = [
            'controller' => [AuthController::class, 'login'],
            'middleware' => []
        ];
        self::$routes['GET']['/register'] = [
            'controller' => [AuthController::class, 'showRegistrationForm'],
            'middleware' => []
        ];
        self::$routes['POST']['/register'] = [
            'controller' => [AuthController::class, 'register'],
            'middleware' => []
        ];
        self::$routes['GET']['/logout'] = [
            'controller' => [AuthController::class, 'logout'],
            'middleware' => [AuthMiddleware::class]
        ];
        self::$routes['GET']['/forgot-password'] = [
            'controller' => [AuthController::class, 'showForgotPasswordForm'],
            'middleware' => []
        ];
        self::$routes['POST']['/forgot-password'] = [
            'controller' => [AuthController::class, 'sendResetLink'],
            'middleware' => []
        ];
        self::$routes['GET']['/reset-password/{token}'] = [
            'controller' => [AuthController::class, 'showResetForm'],
            'middleware' => []
        ];
        self::$routes['POST']['/reset-password'] = [
            'controller' => [AuthController::class, 'resetPassword'],
            'middleware' => []
        ];

        // Admin Routes (AdminMiddleware)
        self::$routes['GET']['/admin/dashboard'] = [
            'controller' => [AdminController::class, 'dashboard'],
            'middleware' => [AdminMiddleware::class]
        ];
        
        // Product Management (AdminMiddleware)
        self::$routes['GET']['/admin/products'] = [
            'controller' => [AdminController::class, 'listProducts'],
            'middleware' => [AdminMiddleware::class]
        ];
        self::$routes['GET']['/admin/products/create'] = [
            'controller' => [AdminController::class, 'createProductForm'],
            'middleware' => [AdminMiddleware::class]
        ];
        self::$routes['POST']['/admin/products'] = [
            'controller' => [AdminController::class, 'createProduct'],
            'middleware' => [AdminMiddleware::class]
        ];
        self::$routes['GET']['/admin/products/{id}/edit'] = [
            'controller' => [AdminController::class, 'editProductForm'],
            'middleware' => [AdminMiddleware::class]
        ];
        self::$routes['POST']['/admin/products/{id}'] = [
            'controller' => [AdminController::class, 'updateProduct'],
            'middleware' => [AdminMiddleware::class]
        ];
        self::$routes['POST']['/admin/products/{id}/delete'] = [
            'controller' => [AdminController::class, 'deleteProduct'],
            'middleware' => [AdminMiddleware::class]
        ];
        
        // Vendor Approvals (AdminMiddleware)
        self::$routes['GET']['/admin/vendors/pending'] = [
            'controller' => [AdminController::class, 'pendingVendors'],
            'middleware' => [AdminMiddleware::class]
        ];
        self::$routes['POST']['/admin/vendors/{id}/approve'] = [
            'controller' => [AdminController::class, 'approveVendor'],
            'middleware' => [AdminMiddleware::class]
        ];
        self::$routes['POST']['/admin/vendors/{id}/reject'] = [
            'controller' => [AdminController::class, 'rejectVendor'],
            'middleware' => [AdminMiddleware::class]
        ];
        
        // Doctor Approvals (AdminMiddleware)
        self::$routes['GET']['/admin/doctors/pending'] = [
            'controller' => [AdminController::class, 'pendingDoctors'],
            'middleware' => [AdminMiddleware::class]
        ];
        self::$routes['POST']['/admin/doctors/{id}/approve'] = [
            'controller' => [AdminController::class, 'approveDoctor'],
            'middleware' => [AdminMiddleware::class]
        ];
        
        // ANVISA Approvals (AdminMiddleware)
        self::$routes['GET']['/admin/anvisa/pending'] = [
            'controller' => [AdminController::class, 'pendingAnvisaApprovals'],
            'middleware' => [AdminMiddleware::class]
        ];
        self::$routes['POST']['/admin/anvisa/{id}/approve'] = [
            'controller' => [AdminController::class, 'approveAnvisa'],
            'middleware' => [AdminMiddleware::class]
        ];
        self::$routes['POST']['/admin/anvisa/{id}/reject'] = [
            'controller' => [AdminController::class, 'rejectAnvisa'],
            'middleware' => [AdminMiddleware::class]
        ];
        
        // Prescription Approvals (AdminMiddleware)
        self::$routes['GET']['/admin/prescriptions/pending'] = [
            'controller' => [AdminController::class, 'pendingPrescriptions'],
            'middleware' => [AdminMiddleware::class]
        ];
        self::$routes['POST']['/admin/prescriptions/{id}/approve'] = [
            'controller' => [AdminController::class, 'approvePrescription'],
            'middleware' => [AdminMiddleware::class]
        ];
        self::$routes['POST']['/admin/prescriptions/{id}/reject'] = [
            'controller' => [AdminController::class, 'rejectPrescription'],
            'middleware' => [AdminMiddleware::class]
        ];

        // Vendor Routes (VendorMiddleware)
        self::$routes['GET']['/vendor/dashboard'] = [
            'controller' => [VendorController::class, 'dashboard'],
            'middleware' => [VendorMiddleware::class]
        ];
        
        // Doctor Management (VendorMiddleware)
        self::$routes['GET']['/vendor/doctors'] = [
            'controller' => [VendorController::class, 'listDoctors'],
            'middleware' => [VendorMiddleware::class]
        ];
        self::$routes['GET']['/vendor/doctors/register'] = [
            'controller' => [VendorController::class, 'registerDoctorForm'],
            'middleware' => [VendorMiddleware::class]
        ];
        self::$routes['POST']['/vendor/doctors'] = [
            'controller' => [VendorController::class, 'registerDoctor'],
            'middleware' => [VendorMiddleware::class]
        ];
        
        // Commissions (VendorMiddleware)
        self::$routes['GET']['/vendor/commissions'] = [
            'controller' => [VendorController::class, 'listCommissions'],
            'middleware' => [VendorMiddleware::class]
        ];
        self::$routes['POST']['/vendor/commissions/request-withdrawal'] = [
            'controller' => [VendorController::class, 'requestWithdrawal'],
            'middleware' => [VendorMiddleware::class]
        ];
        self::$routes['GET']['/vendor/commissions/history'] = [
            'controller' => [VendorController::class, 'withdrawalHistory'],
            'middleware' => [VendorMiddleware::class]
        ];
        
        // Sales Reports (VendorMiddleware)
        self::$routes['GET']['/vendor/sales'] = [
            'controller' => [VendorController::class, 'salesReport'],
            'middleware' => [VendorMiddleware::class]
        ];

        // Client Routes (ClientMiddleware)
        self::$routes['GET']['/client/dashboard'] = [
            'controller' => [ClientController::class, 'dashboard'],
            'middleware' => [ClientMiddleware::class]
        ];
        
        // ANVISA Approval (ClientMiddleware)
        self::$routes['GET']['/client/anvisa/upload'] = [
            'controller' => [ClientController::class, 'showAnvisaUploadForm'],
            'middleware' => [ClientMiddleware::class]
        ];
        self::$routes['POST']['/client/anvisa'] = [
            'controller' => [ClientController::class, 'uploadAnvisaApproval'],
            'middleware' => [ClientMiddleware::class]
        ];
        
        // Orders (ClientMiddleware + additional checks)
        self::$routes['GET']['/client/orders'] = [
            'controller' => [ClientController::class, 'orderHistory'],
            'middleware' => [ClientMiddleware::class]
        ];
        self::$routes['GET']['/client/orders/{id}'] = [
            'controller' => [ClientController::class, 'orderDetails'],
            'middleware' => [ClientMiddleware::class]
        ];
        self::$routes['POST']['/client/orders'] = [
            'controller' => [ClientController::class, 'createOrder'],
            'middleware' => [ClientMiddleware::class]
        ];
        self::$routes['POST']['/client/orders/{id}/prescription'] = [
            'controller' => [ClientController::class, 'uploadPrescription'],
            'middleware' => [ClientMiddleware::class]
        ];
        self::$routes['POST']['/client/orders/{id}/pay'] = [
            'controller' => [ClientController::class, 'processPayment'],
            'middleware' => [ClientMiddleware::class]
        ];
        
        // Products (public or with age verification)
        self::$routes['GET']['/products'] = [
            'controller' => [ProductController::class, 'listProducts'],
            'middleware' => []
        ];
        self::$routes['GET']['/products/{id}'] = [
            'controller' => [ProductController::class, 'productDetails'],
            'middleware' => []
        ];
        self::$routes['GET']['/products/category/{categoryId}'] = [
            'controller' => [ProductController::class, 'productsByCategory'],
            'middleware' => []
        ];

        // API Routes (no middleware or custom middleware)
        self::$routes['POST']['/api/mercado-pago/webhook'] = [
            'controller' => [PaymentController::class, 'handleMercadoPagoWebhook'],
            'middleware' => []
        ];
        self::$routes['POST']['/api/trix-tracking'] = [
            'controller' => [OrderController::class, 'updateTrackingInfo'],
            'middleware' => []
        ];
        
        // 404 Catch-all (must be last)
        self::$routes['GET']['/404'] = [
            'controller' => [ClientController::class, 'notFound'],
            'middleware' => []
        ];
    }

    public static function routeExists(string $method, string $uri): bool {
        return isset(self::$routes[$method][$uri]);
    }

    public static function getRouteHandler(string $method, string $uri): ?array {
        return self::$routes[$method][$uri] ?? null;
    }
}