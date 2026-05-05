<?php

namespace App\Controllers;

use App\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Controllers\Admin\LaporanController as AdminLaporanController;
use App\Controllers\Admin\PesananController as AdminPesananController;
use App\Controllers\Admin\ProdukController as AdminProdukController;
use App\Controllers\Customer\CartController as CustomerCartController;
use App\Controllers\Customer\CheckoutController as CustomerCheckoutController;
use App\Controllers\Customer\OrderController as CustomerOrderController;
use App\Controllers\Customer\ProfileController as CustomerProfileController;
use App\Models\ItemKeranjangModel;
use App\Models\KeranjangModel;
use App\Models\UserModel;
use Config\Services;

class CaptureController extends BaseController
{
    public function autoLogin()
    {
        if (ENVIRONMENT !== 'development') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $role = strtolower((string) $this->request->getGet('role'));
        $target = (string) ($this->request->getGet('target') ?? '/');
        $prepare = strtolower((string) $this->request->getGet('prepare'));

        if ($role !== '' && $target !== '') {
            $user = $this->resolveCaptureUser($role);
            if ($user !== null) {
                session()->set([
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'nama_lengkap' => $user['nama_lengkap'],
                    'role' => $user['role'],
                    'isLoggedIn' => true,
                ]);

                if ($role === 'customer') {
                    $this->prepareCustomerState((int) $user['id'], $prepare);
                }

                return redirect()->to(base_url(ltrim($target, '/')));
            }
        }

        return view('tools/auto_login', [
            'title' => 'Auto Login Helper',
        ]);
    }

    private function resolveCaptureUser(string $role): ?array
    {
        $userModel = new UserModel();

        if ($role === 'admin') {
            return $userModel->where('role', 'ADMIN')->orderBy('id', 'ASC')->first();
        }

        if ($role === 'customer') {
            return $userModel->where('id', 3)->where('role', 'PELANGGAN')->first()
                ?? $userModel->where('role', 'PELANGGAN')->orderBy('id', 'ASC')->first();
        }

        return null;
    }

    private function prepareCustomerState(int $userId, string $prepare): void
    {
        $keranjangModel = new KeranjangModel();
        $itemKeranjangModel = new ItemKeranjangModel();
        $cart = $keranjangModel->getOrCreateCart($userId);

        if (!in_array($prepare, ['cart', 'checkout'], true)) {
            return;
        }

        $keranjangModel->clearCart($userId);
        $itemKeranjangModel->addOrUpdateItem((int) $cart['id'], 1, 2);
        $itemKeranjangModel->addOrUpdateItem((int) $cart['id'], 4, 1);
        $keranjangModel->touchCart($userId);
    }

    public function customerCart()
    {
        return $this->dispatchCustomer(CustomerCartController::class, 'index', [], 'cart');
    }

    public function customerCheckout()
    {
        return $this->dispatchCustomer(CustomerCheckoutController::class, 'index', [], 'checkout');
    }

    public function customerDashboard()
    {
        return $this->dispatchCustomer(CustomerOrderController::class, 'dashboard');
    }

    public function customerOrder(int $orderId)
    {
        return $this->dispatchCustomer(CustomerOrderController::class, 'detail', [$orderId]);
    }

    public function customerUploadPayment(int $orderId)
    {
        return $this->dispatchCustomer(CustomerOrderController::class, 'uploadPayment', [$orderId]);
    }

    public function customerProfile()
    {
        return $this->dispatchCustomer(CustomerProfileController::class, 'index');
    }

    public function adminDashboard()
    {
        return $this->dispatchAdmin(AdminDashboardController::class, 'index');
    }

    public function adminProduk()
    {
        return $this->dispatchAdmin(AdminProdukController::class, 'index');
    }

    public function adminPesanan()
    {
        return $this->dispatchAdmin(AdminPesananController::class, 'index');
    }

    public function adminPesananDetail(int $orderId)
    {
        return $this->dispatchAdmin(AdminPesananController::class, 'detail', [$orderId]);
    }

    public function adminPendingPayments()
    {
        return $this->dispatchAdmin(AdminPesananController::class, 'pendingPayments');
    }

    public function adminLaporanSales()
    {
        return $this->dispatchAdmin(AdminLaporanController::class, 'salesReport');
    }

    public function adminLaporanStock()
    {
        return $this->dispatchAdmin(AdminLaporanController::class, 'stockReport');
    }

    public function adminLaporanCustomers()
    {
        return $this->dispatchAdmin(AdminLaporanController::class, 'customerReport');
    }

    private function dispatchCustomer(string $controllerClass, string $method, array $args = [], string $prepare = '')
    {
        if (ENVIRONMENT !== 'development') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $user = $this->resolveCaptureUser('customer');
        if ($user === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        session()->set([
            'id' => $user['id'],
            'email' => $user['email'],
            'nama_lengkap' => $user['nama_lengkap'],
            'role' => $user['role'],
            'isLoggedIn' => true,
        ]);

        $this->prepareCustomerState((int) $user['id'], $prepare);

        $controller = $this->makeController($controllerClass);
        return $controller->{$method}(...$args);
    }

    private function dispatchAdmin(string $controllerClass, string $method, array $args = [])
    {
        if (ENVIRONMENT !== 'development') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $user = $this->resolveCaptureUser('admin');
        if ($user === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        session()->set([
            'id' => $user['id'],
            'email' => $user['email'],
            'nama_lengkap' => $user['nama_lengkap'],
            'role' => $user['role'],
            'isLoggedIn' => true,
        ]);

        $controller = $this->makeController($controllerClass);
        return $controller->{$method}(...$args);
    }

    private function makeController(string $controllerClass)
    {
        $controller = new $controllerClass();
        $controller->initController($this->request, $this->response, Services::logger());

        return $controller;
    }
}
