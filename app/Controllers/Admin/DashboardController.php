<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PesananModel;
use App\Models\ProdukModel;
use App\Models\PembayaranModel;
use App\Models\UserModel;

class DashboardController extends BaseController
{
    protected $pesananModel;
    protected $produkModel;
    protected $pembayaranModel;
    protected $userModel;

    public function __construct()
    {
        $this->pesananModel = new PesananModel();
        $this->produkModel = new ProdukModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->userModel = new UserModel();
        helper(['form', 'url']);
    }

    /**
     * Admin dashboard
     */
    public function index()
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'ADMIN') {
            return redirect()->to(base_url('auth/login'))->with('error', 'Akses ditolak');
        }

        // Get statistics
        $stats = [
            'total_orders_today' => $this->getTodayOrdersCount(),
            'pending_payments' => $this->getPendingPaymentsCount(),
            'low_stock_products' => $this->produkModel->getLowStockCount(10),
            'total_revenue' => $this->getTotalRevenue(),
            'total_customers' => $this->getTotalCustomers()
        ];

        // Get recent orders
        $recentOrders = $this->pesananModel->getAllOrdersWithUser(null, 10);

        // Get pending payment verifications
        $pendingPayments = $this->pembayaranModel->getPendingPayments(10);

        // Get low stock products
        $lowStockProducts = $this->produkModel->getLowStockProducts(10);

        $data = [
            'title' => 'Dashboard Admin - DIX Game Farm',
            'stats' => $stats,
            'recent_orders' => $recentOrders,
            'pending_payments' => $pendingPayments,
            'low_stock_products' => $lowStockProducts
        ];

        return view('admin/dashboard', $data);
    }

    /**
     * Helper: Get today's order count
     */
    private function getTodayOrdersCount()
    {
        $start = date('Y-m-d 00:00:00');
        $end = date('Y-m-d 23:59:59');

        return $this->pesananModel
            ->where('tanggal_pesanan >=', $start)
            ->where('tanggal_pesanan <=', $end)
            ->countAllResults();
    }

    /**
     * Helper: Get pending payments count
     */
    private function getPendingPaymentsCount()
    {
        return $this->pembayaranModel->where('status_pembayaran', 'PENDING')->countAllResults();
    }

    /**
     * Helper: Get monthly revenue
     */
    private function getTotalRevenue()
    {
        $result = $this->pesananModel
            ->selectSum('grand_total')
            ->whereIn('status_pesanan', ['SELESAI'])
            ->first();

        return (float) ($result['grand_total'] ?? 0);
    }

    /**
     * Helper: Get total customers
     */
    private function getTotalCustomers()
    {
        return $this->userModel->where('role', 'PELANGGAN')->countAllResults();
    }
}
