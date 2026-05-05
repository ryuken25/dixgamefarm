<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(false); // Disable auto-routing for security

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// Public Routes
$routes->get('/', 'Home::index');
$routes->get('/tentang', 'Home::about');
$routes->get('/kontak', 'Home::contact');
$routes->post('/kontak/send', 'Home::sendContact');
$routes->get('/capture/auto-login', 'CaptureController::autoLogin');
$routes->get('/capture/customer/cart', 'CaptureController::customerCart');
$routes->get('/capture/customer/checkout', 'CaptureController::customerCheckout');
$routes->get('/capture/customer/dashboard', 'CaptureController::customerDashboard');
$routes->get('/capture/customer/order/(:num)', 'CaptureController::customerOrder/$1');
$routes->get('/capture/customer/order/(:num)/upload-payment', 'CaptureController::customerUploadPayment/$1');
$routes->get('/capture/customer/profile', 'CaptureController::customerProfile');
$routes->get('/capture/admin/dashboard', 'CaptureController::adminDashboard');
$routes->get('/capture/admin/produk', 'CaptureController::adminProduk');
$routes->get('/capture/admin/pesanan', 'CaptureController::adminPesanan');
$routes->get('/capture/admin/pesanan/detail/(:num)', 'CaptureController::adminPesananDetail/$1');
$routes->get('/capture/admin/pesanan/pending-payments', 'CaptureController::adminPendingPayments');
$routes->get('/capture/admin/laporan/sales', 'CaptureController::adminLaporanSales');
$routes->get('/capture/admin/laporan/stock', 'CaptureController::adminLaporanStock');
$routes->get('/capture/admin/laporan/customers', 'CaptureController::adminLaporanCustomers');

// Katalog (Public)
$routes->get('/katalog', 'KatalogController::index');
$routes->get('/katalog/detail/(:num)', 'KatalogController::detail/$1');
$routes->get('/katalog/product-info/(:num)', 'KatalogController::getProductInfo/$1');
$routes->get('/katalog/check-stock/(:num)', 'KatalogController::checkStock/$1');

// Authentication Routes
$routes->group('auth', function ($routes) {
    $routes->get('login', 'Auth\AuthController::login');
    $routes->post('login', 'Auth\AuthController::processLogin');
    $routes->get('register', 'Auth\AuthController::register');
    $routes->post('register', 'Auth\AuthController::processRegister');
    $routes->get('logout', 'Auth\AuthController::logout');
});

// Customer Routes (Protected by CustomerFilter)
$routes->group('customer', ['filter' => 'customer'], function ($routes) {
    // Dashboard
    $routes->get('dashboard', 'Customer\OrderController::dashboard');

    // Cart
    $routes->get('cart', 'Customer\CartController::index');
    $routes->post('cart/add', 'Customer\CartController::addItem');
    $routes->post('cart/update', 'Customer\CartController::updateQuantity');
    $routes->post('cart/remove', 'Customer\CartController::removeItem');
    $routes->post('cart/clear', 'Customer\CartController::clearCart');
    $routes->get('cart/count', 'Customer\CartController::getCartCount');

    // Checkout
    $routes->get('checkout', 'Customer\CheckoutController::index');
    $routes->post('checkout/process', 'Customer\CheckoutController::processCheckout');
    $routes->post('checkout/validate', 'Customer\CheckoutController::validateCheckout');

    // Orders
    $routes->get('order/(:num)', 'Customer\OrderController::detail/$1');
    $routes->get('order/(:num)/upload-payment', 'Customer\OrderController::uploadPayment/$1');
    $routes->post('order/upload-payment', 'Customer\OrderController::processPaymentUpload');
    $routes->post('order/cancel', 'Customer\OrderController::cancelOrder');
    $routes->post('order/complete', 'Customer\OrderController::completeOrder');

    // Profile
    $routes->get('profile', 'Customer\ProfileController::index');
    $routes->post('profile/update', 'Customer\ProfileController::update');
    $routes->post('profile/update-password', 'Customer\ProfileController::updatePassword');
});

// Admin Routes (Protected by AdminFilter)
$routes->group('admin', ['filter' => 'admin'], function ($routes) {
    // Dashboard
    $routes->get('dashboard', 'Admin\DashboardController::index');

    // Kategori Management
    $routes->get('kategori', 'Admin\KategoriController::index');
    $routes->post('kategori/create', 'Admin\KategoriController::create');
    $routes->post('kategori/update/(:num)', 'Admin\KategoriController::update/$1');
    $routes->post('kategori/delete/(:num)', 'Admin\KategoriController::delete/$1');

    // Produk Management
    $routes->get('produk', 'Admin\ProdukController::index');
    $routes->post('produk/create', 'Admin\ProdukController::create');
    $routes->post('produk/update/(:num)', 'Admin\ProdukController::update/$1');
    $routes->post('produk/delete/(:num)', 'Admin\ProdukController::delete/$1');
    $routes->post('produk/update-stock', 'Admin\ProdukController::updateStock');

    // Pesanan Management
    $routes->get('pesanan', 'Admin\PesananController::index');
    $routes->get('pesanan/detail/(:num)', 'Admin\PesananController::detail/$1');
    $routes->post('pesanan/update-status', 'Admin\PesananController::updateStatus');
    $routes->post('pesanan/cancel', 'Admin\PesananController::cancelOrder');

    // Payment Verification
    $routes->get('pesanan/pending-payments', 'Admin\PesananController::pendingPayments');
    $routes->post('pesanan/verify-payment', 'Admin\PesananController::verifyPayment');

    // Laporan (Reports)
    $routes->get('laporan', 'Admin\LaporanController::index');
    $routes->get('laporan/sales', 'Admin\LaporanController::salesReport');
    $routes->get('laporan/stock', 'Admin\LaporanController::stockReport');
    $routes->get('laporan/customer', 'Admin\LaporanController::customerReport');
    $routes->get('laporan/customers', 'Admin\LaporanController::customerReport');
    $routes->get('laporan/export-sales', 'Admin\LaporanController::exportSales');
});
