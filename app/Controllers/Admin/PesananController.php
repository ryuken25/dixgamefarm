<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PesananModel;
use App\Models\PembayaranModel;

class PesananController extends BaseController
{
    protected $pesananModel;
    protected $pembayaranModel;

    public function __construct()
    {
        $this->pesananModel = new PesananModel();
        $this->pembayaranModel = new PembayaranModel();
        helper(['form', 'url']);
    }

    /**
     * Order list (admin)
     */
    public function index()
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'ADMIN') {
            return redirect()->to(base_url('auth/login'))->with('error', 'Akses ditolak');
        }

        $status = $this->request->getGet('status');
        $orders = $this->pesananModel->getAllOrdersWithUser($status, 100);

        $data = [
            'title' => 'Manajemen Pesanan - DIX Game Farm',
            'orders' => $orders,
            'filter_status' => $status
        ];

        return view('admin/pesanan/index', $data);
    }

    /**
     * Order detail (admin)
     */
    public function detail($orderId)
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'ADMIN') {
            return redirect()->to(base_url('auth/login'))->with('error', 'Akses ditolak');
        }

        $orderData = $this->pesananModel->getOrderWithDetails($orderId);

        if (!$orderData) {
            return redirect()->to(base_url('admin/pesanan'))->with('error', 'Pesanan tidak ditemukan');
        }

        $data = [
            'title' => 'Detail Pesanan - DIX Game Farm',
            'orderData' => $orderData
        ];

        return view('admin/pesanan/detail', $data);
    }

    /**
     * Update order status
     */
    public function updateStatus()
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'ADMIN') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Unauthorized'
            ])->setStatusCode(401);
        }

        // Diagnostic log to validate CSRF/header propagation on admin status update flow
        log_message('debug', 'Admin updateStatus request accepted by controller. has_csrf_header=' . ($this->request->hasHeader('X-CSRF-TOKEN') ? '1' : '0'));

        $orderId = (int) $this->request->getPost('order_id');
        $newStatus = strtoupper(trim((string) $this->request->getPost('status')));
        $kodeResi = trim((string) $this->request->getPost('kode_resi'));

        if (!$orderId || !$newStatus) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Data tidak valid'
            ])->setStatusCode(400);
        }

        $order = $this->pesananModel->find($orderId);

        if (!$order) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan'
            ])->setStatusCode(404);
        }

        $validTransitions = [
            'MENUNGGU_BAYAR' => ['DIPROSES'],
            'MENUNGGU_VERIFIKASI' => ['DIPROSES'],
            'DIPROSES' => ['PESANAN_SIAP', 'DIKIRIM', 'BATAL'],
            'PESANAN_SIAP' => ['SELESAI'],
            'DIKIRIM' => ['SELESAI']
        ];

        // Validate status transition
        $currentStatus = $order['status_pesanan'];

        if (!isset($validTransitions[$currentStatus]) || !in_array($newStatus, $validTransitions[$currentStatus])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Transisi status tidak valid'
            ])->setStatusCode(400);
        }

        if ($newStatus === 'DIKIRIM' && $order['tipe_pengiriman'] !== 'DIKIRIM_KURIR') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Status Dikirim hanya untuk pesanan dengan pengiriman kurir'
            ])->setStatusCode(400);
        }

        if ($newStatus === 'PESANAN_SIAP' && $order['tipe_pengiriman'] !== 'AMBIL_SENDIRI') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Status Pesanan Siap hanya untuk pesanan Ambil Sendiri'
            ])->setStatusCode(400);
        }

        if ($newStatus === 'DIKIRIM' && $order['tipe_pengiriman'] === 'DIKIRIM_KURIR' && $kodeResi === '') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Kode Resi wajib diisi sebelum mengubah status menjadi Dikirim'
            ])->setStatusCode(400);
        }

        if ($newStatus === 'DIKIRIM') {
            $verifiedPayment = $this->pembayaranModel
                ->where('pesanan_id', $orderId)
                ->where('status_pembayaran', 'VALID')
                ->first();

            if (!$verifiedPayment) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Pesanan tidak boleh dikirim sebelum pembayaran valid diverifikasi admin'
                ])->setStatusCode(400);
            }
        }

        $extraData = [];
        if ($newStatus === 'DIKIRIM') {
            $extraData['kode_resi'] = $kodeResi;
        }

        $extraData['admin_id'] = (int) session()->get('id');

        // Update status
        if ($this->pesananModel->updateOrderStatus($orderId, $newStatus, $extraData)) {
            // Read-after-write guard to prevent silent invalid ENUM fallback (e.g. empty string)
            $updatedOrder = $this->pesananModel->find($orderId);
            if (($updatedOrder['status_pesanan'] ?? null) !== $newStatus) {
                log_message('error', 'Order status mismatch after update. order_id=' . $orderId . ', expected=' . $newStatus . ', actual=' . ($updatedOrder['status_pesanan'] ?? 'NULL'));

                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Status pesanan gagal tersimpan secara valid di database'
                ])->setStatusCode(500);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Status pesanan berhasil diperbarui'
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Gagal memperbarui status pesanan'
        ])->setStatusCode(500);
    }

    /**
     * Verify payment (admin action)
     */
    public function verifyPayment()
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'ADMIN') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Unauthorized'
            ])->setStatusCode(401);
        }

        $paymentId = $this->request->getPost('payment_id');
        $action = $this->request->getPost('action'); // 'approve' or 'reject'
        $alasan = $this->request->getPost('alasan');

        if (!$paymentId || !in_array($action, ['approve', 'reject'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Data tidak valid'
            ])->setStatusCode(400);
        }

        if ($action === 'reject' && empty($alasan)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Alasan penolakan harus diisi'
            ])->setStatusCode(400);
        }

        $isValid = ($action === 'approve');
        $adminId = session()->get('id');

        $result = $this->pembayaranModel->verifyPayment($paymentId, $adminId, $isValid, $alasan);

        if ($result['success']) {
            return $this->response->setJSON([
                'success' => true,
                'message' => $result['message']
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => $result['message']
        ])->setStatusCode(500);
    }

    /**
     * Cancel order (admin action)
     * Only allows cancellation if order is not yet shipped or completed.
     */
    public function cancelOrder()
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'ADMIN') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Unauthorized'
            ])->setStatusCode(401);
        }

        $orderId = $this->request->getPost('order_id');
        $alasan = $this->request->getPost('alasan');

        if (!$orderId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Data tidak valid'
            ])->setStatusCode(400);
        }

        // Check order status — prevent cancelling shipped/completed/already cancelled
        $order = $this->pesananModel->find($orderId);
        if (!$order) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan'
            ])->setStatusCode(404);
        }

        if (in_array($order['status_pesanan'], ['PESANAN_SIAP', 'DIKIRIM', 'SELESAI', 'BATAL'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Pesanan dengan status ' . $order['status_pesanan'] . ' tidak dapat dibatalkan'
            ])->setStatusCode(400);
        }

        if (empty($alasan)) {
            $alasan = 'Dibatalkan oleh admin';
        }

        if ($this->pesananModel->cancelOrder($orderId, $alasan)) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Pesanan berhasil dibatalkan'
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Gagal membatalkan pesanan'
        ])->setStatusCode(500);
    }

    /**
     * Pending payments view
     */
    public function pendingPayments()
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'ADMIN') {
            return redirect()->to(base_url('auth/login'))->with('error', 'Akses ditolak');
        }

        $pendingPayments = $this->pembayaranModel->getPendingPayments();

        $data = [
            'title' => 'Verifikasi Pembayaran - DIX Game Farm',
            'payments' => $pendingPayments
        ];

        return view('admin/pesanan/pending_payments', $data);
    }
}
