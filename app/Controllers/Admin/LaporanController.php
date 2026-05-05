<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PesananModel;
use App\Models\DetailPesananModel;
use App\Models\ProdukModel;
use App\Models\UserModel;

class LaporanController extends BaseController
{
    protected $pesananModel;
    protected $detailModel;
    protected $produkModel;
    protected $userModel;

    public function __construct()
    {
        $this->pesananModel = new PesananModel();
        $this->detailModel = new DetailPesananModel();
        $this->produkModel = new ProdukModel();
        $this->userModel = new UserModel();
        helper(['form', 'url']);
    }

    /**
     * Reports dashboard
     */
    public function index()
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'ADMIN') {
            return redirect()->to(base_url('auth/login'))->with('error', 'Akses ditolak');
        }

        $data = [
            'title' => 'Laporan - DIX Game Farm'
        ];

        return view('admin/laporan/index', $data);
    }

    /**
     * Sales report (Laporan Penjualan)
     */
    public function salesReport()
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'ADMIN') {
            return redirect()->to(base_url('auth/login'))->with('error', 'Akses ditolak');
        }

        $startDate = $this->request->getGet('start_date') ?: date('Y-m-01'); // First day of month
        $endDate = $this->request->getGet('end_date') ?: date('Y-m-d');

        // Normalize invalid range (swap if needed)
        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $startDateTime = $startDate . ' 00:00:00';
        $endDateTime = $endDate . ' 23:59:59';
        $validSalesStatuses = ['SELESAI'];

        // Orders list for table rendering
        $orders = $this->pesananModel->builder()
            ->where('tanggal_pesanan >=', $startDateTime)
            ->where('tanggal_pesanan <=', $endDateTime)
            ->whereIn('status_pesanan', $validSalesStatuses)
            ->orderBy('tanggal_pesanan', 'DESC')
            ->get()
            ->getResultArray();

        // Aggregate summary in SQL (COUNT + SUM)
        $summary = $this->pesananModel->builder()
            ->select('COUNT(*) AS total_orders, COALESCE(SUM(grand_total), 0) AS total_revenue', false)
            ->where('tanggal_pesanan >=', $startDateTime)
            ->where('tanggal_pesanan <=', $endDateTime)
            ->whereIn('status_pesanan', $validSalesStatuses)
            ->get()
            ->getRowArray();

        $totalOrders = (int) ($summary['total_orders'] ?? 0);
        $totalRevenue = (float) ($summary['total_revenue'] ?? 0);

        // Get best selling products
        $bestSellingProducts = $this->getBestSellingProducts($startDate, $endDate);

        $data = [
            'title' => 'Laporan Penjualan - DIX Game Farm',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'orders' => $orders,
            'order_list' => $orders,
            'total_revenue' => $totalRevenue,
            'total_orders' => $totalOrders,
            'best_selling' => $bestSellingProducts
        ];

        return view('admin/laporan/sales', $data);
    }

    /**
     * Stock report (Laporan Stok)
     */
    public function stockReport()
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'ADMIN') {
            return redirect()->to(base_url('auth/login'))->with('error', 'Akses ditolak');
        }

        $products = $this->produkModel->getProdukWithKategori(null);

        // Calculate stock values
        $totalStockValue = 0;
        $lowStockCount = 0;
        $outOfStockCount = 0;

        foreach ($products as &$product) {
            $product['total_value'] = $product['stok_tersedia'] * $product['harga'];
            $totalStockValue += $product['total_value'];

            if ($product['stok_tersedia'] == 0) {
                $outOfStockCount++;
            } elseif ($product['stok_tersedia'] <= 10) {
                $lowStockCount++;
            }
        }

        $data = [
            'title' => 'Laporan Stok - DIX Game Farm',
            'products' => $products,
            'total_stock_value' => $totalStockValue,
            'low_stock_count' => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount
        ];

        return view('admin/laporan/stock', $data);
    }

    /**
     * Customer report (Laporan Pelanggan)
     */
    public function customerReport()
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'ADMIN') {
            return redirect()->to(base_url('auth/login'))->with('error', 'Akses ditolak');
        }

        // Get customers with their order statistics
        // Use conditional aggregation to avoid LEFT JOIN + WHERE filtering out customers with no matching orders
        $customers = $this->userModel->select('users.*,
                COUNT(CASE WHEN pesanan.status_pesanan IN ("DIPROSES","PESANAN_SIAP","DIKIRIM","SELESAI") THEN 1 END) as total_orders,
                COALESCE(SUM(CASE WHEN pesanan.status_pesanan IN ("DIPROSES","PESANAN_SIAP","DIKIRIM","SELESAI") THEN pesanan.grand_total END), 0) as total_spent', false)
            ->join('pesanan', 'pesanan.user_id = users.id', 'left')
            ->where('users.role', 'PELANGGAN')
            ->groupBy('users.id')
            ->findAll();

        $data = [
            'title' => 'Laporan Pelanggan - DIX Game Farm',
            'customers' => $customers
        ];

        return view('admin/laporan/customers', $data);
    }

    /**
     * Export sales report to CSV
     */
    public function exportSales()
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'ADMIN') {
            return redirect()->to(base_url('auth/login'));
        }

        $startDate = $this->request->getGet('start_date') ?: date('Y-m-01');
        $endDate = $this->request->getGet('end_date') ?: date('Y-m-d');

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $startDateTime = $startDate . ' 00:00:00';
        $endDateTime = $endDate . ' 23:59:59';

        $orders = $this->pesananModel->builder()
            ->where('tanggal_pesanan >=', $startDateTime)
            ->where('tanggal_pesanan <=', $endDateTime)
            ->whereIn('status_pesanan', ['SELESAI'])
            ->orderBy('tanggal_pesanan', 'DESC')
            ->get()
            ->getResultArray();

        // Pre-fetch user names to avoid N+1 queries
        $userIds = array_unique(array_column($orders, 'user_id'));
        $usersMap = [];
        if (!empty($userIds)) {
            $users = $this->userModel->whereIn('id', $userIds)->findAll();
            foreach ($users as $u) {
                $usersMap[$u['id']] = $u['nama_lengkap'];
            }
        }

        // Generate CSV
        $filename = 'laporan_penjualan_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');

        // BOM for UTF-8
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Headers
        fputcsv($output, ['No. Invoice', 'Tanggal', 'Nama Pelanggan', 'Status', 'Total', 'Tipe Pengiriman']);

        // Data
        foreach ($orders as $order) {
            fputcsv($output, [
                $order['nomor_invoice'],
                $order['tanggal_pesanan'],
                $usersMap[$order['user_id']] ?? '-',
                $order['status_pesanan'],
                number_format($order['grand_total'], 0, ',', '.'),
                $order['tipe_pengiriman']
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Helper: Get best selling products
     */
    private function getBestSellingProducts($startDate, $endDate, $limit = 10)
    {
        $startDateTime = $startDate . ' 00:00:00';
        $endDateTime = $endDate . ' 23:59:59';

        return $this->detailModel->select('produk.nama_ayam, kategori.nama_kategori, SUM(detail_pesanan.jumlah) as total_terjual, SUM(detail_pesanan.subtotal) as total_pendapatan')
            ->join('pesanan', 'pesanan.id = detail_pesanan.pesanan_id')
            ->join('produk', 'produk.id = detail_pesanan.produk_id')
            ->join('kategori', 'kategori.id = produk.kategori_id')
            ->where('pesanan.tanggal_pesanan >=', $startDateTime)
            ->where('pesanan.tanggal_pesanan <=', $endDateTime)
            ->whereIn('pesanan.status_pesanan', ['SELESAI'])
            ->groupBy('detail_pesanan.produk_id')
            ->orderBy('total_terjual', 'DESC')
            ->limit($limit)
            ->findAll();
    }
}
