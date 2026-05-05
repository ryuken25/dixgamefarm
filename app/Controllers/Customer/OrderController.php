<?php

namespace App\Controllers\Customer;

use App\Controllers\BaseController;
use App\Models\PesananModel;
use App\Models\PembayaranModel;

class OrderController extends BaseController
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
     * Show customer dashboard with order list
     */
    public function dashboard()
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'PELANGGAN') {
            return redirect()->to(base_url('auth/login'))->with('error', 'Anda harus login sebagai pelanggan');
        }

        $userId = session()->get('id');
        $orders = $this->pesananModel->getOrdersByUser($userId);

        $data = [
            'title' => 'Dashboard Pelanggan - DIX Game Farm',
            'orders' => $orders
        ];

        return view('customer/dashboard', $data);
    }

    /**
     * Show order detail
     */
    public function detail($orderId)
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'PELANGGAN') {
            return redirect()->to(base_url('auth/login'))->with('error', 'Anda harus login sebagai pelanggan');
        }

        $orderData = $this->pesananModel->getOrderWithDetails($orderId);

        if (!$orderData || $orderData['order']['user_id'] != session()->get('id')) {
            return redirect()->to(base_url('customer/dashboard'))->with('error', 'Pesanan tidak ditemukan');
        }

        $data = [
            'title' => 'Detail Pesanan - DIX Game Farm',
            'orderData' => $orderData
        ];

        return view('customer/order_detail', $data);
    }

    /**
     * Show payment upload form (also used for re-upload when status is
     * MENUNGGU_VERIFIKASI and the existing payment is still PENDING).
     */
    public function uploadPayment($orderId)
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'PELANGGAN') {
            return redirect()->to(base_url('auth/login'))->with('error', 'Anda harus login sebagai pelanggan');
        }

        $order = $this->pesananModel->find($orderId);

        if (!$order || $order['user_id'] != session()->get('id')) {
            return redirect()->to(base_url('customer/dashboard'))->with('error', 'Pesanan tidak ditemukan');
        }

        $existingPayment = $this->pembayaranModel->getPaymentByOrderId($orderId);

        $isReupload = $this->resolvePaymentUploadMode($order, $existingPayment);
        if ($isReupload === null) {
            return redirect()->to(base_url("customer/order/{$orderId}"))->with('error', 'Bukti pembayaran tidak dapat diupload/diganti pada status ini');
        }

        $data = [
            'title' => ($isReupload ? 'Ganti' : 'Upload') . ' Bukti Pembayaran - DIX Game Farm',
            'order' => $order,
            'existingPayment' => $existingPayment,
            'isReupload' => $isReupload,
            'bankOptions' => CheckoutController::getBankOptions(),
        ];

        return view('customer/upload_payment', $data);
    }

    /**
     * Process payment upload
     */
    public function processPaymentUpload()
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'PELANGGAN') {
            return $this->paymentErrorResponse(401, 'Sesi login Anda tidak valid. Silakan login kembali.', [
                'reason' => 'unauthenticated_payment_upload'
            ]);
        }

        log_message('debug', 'Payment upload received. user_id=' . session()->get('id') . ', pesanan_id=' . (string) $this->request->getPost('pesanan_id') . ', has_nominal=' . ($this->request->getPost('nominal_bayar') !== null ? '1' : '0') . ', has_file=' . ($this->request->getFile('bukti_bayar') && $this->request->getFile('bukti_bayar')->isValid() ? '1' : '0'));

        $rules = [
            'pesanan_id' => 'required|is_not_unique[pesanan.id]',
            'nominal_bayar' => 'required|decimal|greater_than[0]',
            'bukti_bayar' => [
                'rules' => 'uploaded[bukti_bayar]|max_size[bukti_bayar,2048]|is_image[bukti_bayar]|mime_in[bukti_bayar,image/jpg,image/jpeg,image/png]',
                'errors' => [
                    'uploaded' => 'File bukti bayar harus diupload',
                    'max_size' => 'Ukuran file maksimal 2MB',
                    'is_image' => 'File harus berupa gambar',
                    'mime_in' => 'Format file harus JPG, JPEG, atau PNG'
                ]
            ]
        ];

        if (!$this->validate($rules)) {
            return $this->paymentErrorResponse(400, 'Validasi upload pembayaran gagal', [
                'user_id' => session()->get('id')
            ], $this->validator->getErrors());
        }

        $pesananId = $this->request->getPost('pesanan_id');
        $order = $this->pesananModel->find($pesananId);

        // Verify ownership
        if (!$order || $order['user_id'] != session()->get('id')) {
            return $this->paymentErrorResponse(404, 'Pesanan tidak ditemukan.', [
                'user_id' => session()->get('id'),
                'pesanan_id' => $pesananId
            ]);
        }

        $bankOptions = CheckoutController::getBankOptions();
        $paymentTargetKey = (string) ($order['metode_pembayaran'] ?? '');
        $paymentTarget = $paymentTargetKey !== '' ? ($bankOptions[$paymentTargetKey] ?? null) : null;
        $bankTujuan = $paymentTarget['label'] ?? ($paymentTargetKey !== '' ? $paymentTargetKey : null);

        // SECURITY CHECK: nominal transfer wajib sama persis dengan grand_total pesanan
        $submittedNominal = (float) $this->request->getPost('nominal_bayar');
        $requiredNominal = (float) $order['grand_total'];
        if (abs($submittedNominal - $requiredNominal) > 0.00001) {
            return $this->paymentErrorResponse(400, 'Nominal transfer tidak sesuai dengan total pembayaran pesanan.', [
                'pesanan_id' => $pesananId,
                'submitted_nominal' => $submittedNominal,
                'required_nominal' => $requiredNominal
            ]);
        }

        // Gunakan nominal resmi dari database untuk mencegah manipulasi payload
        $nominalBayar = $requiredNominal;

        $existingPayment = $this->pembayaranModel->getPaymentByOrderId($pesananId);

        // Allow first upload or replacing a still-pending proof without changing order status.
        $isReupload = $this->resolvePaymentUploadMode($order, $existingPayment);
        if ($isReupload === null) {
            return $this->paymentErrorResponse(400, 'Bukti pembayaran tidak dapat diupload pada status pesanan saat ini.', [
                'pesanan_id' => $pesananId,
                'status_pesanan' => $order['status_pesanan'] ?? null,
                'has_existing_payment' => $existingPayment ? 1 : 0
            ]);
        }

        // Handle file upload
        $file = $this->request->getFile('bukti_bayar');

        if (!$file->isValid()) {
            return $this->paymentErrorResponse(400, 'File bukti pembayaran tidak valid.', [
                'pesanan_id' => $pesananId
            ]);
        }

        // Create upload directory if not exists
        $uploadPath = FCPATH . 'uploads/bukti_bayar';
        if (!is_dir($uploadPath)) {
            if (!mkdir($uploadPath, 0755, true)) {
                return $this->paymentErrorResponse(500, 'Sistem tidak dapat menyiapkan folder upload pembayaran.', [
                    'pesanan_id' => $pesananId,
                    'upload_path' => $uploadPath
                ]);
            }
        }

        // Verify directory is writable
        if (!is_writable($uploadPath)) {
            return $this->paymentErrorResponse(500, 'Folder upload pembayaran tidak dapat ditulis.', [
                'pesanan_id' => $pesananId,
                'upload_path' => $uploadPath
            ]);
        }

        // Generate unique filename
        $newName = 'bukti_' . $order['nomor_invoice'] . '_' . time() . '.' . $file->getExtension();

        // Move file
        if ($file->move($uploadPath, $newName)) {
            $buktiBayarPath = 'uploads/bukti_bayar/' . $newName;

            log_message('debug', 'Payment upload ready to create transaction. pesanan_id=' . $pesananId . ', invoice=' . ($order['nomor_invoice'] ?? 'null') . ', is_reupload=' . ($isReupload ? '1' : '0') . ', order_status=' . ($order['status_pesanan'] ?? 'null') . ', nominal=' . $nominalBayar . ', bank_tujuan=' . (string) $bankTujuan);

            if ($isReupload) {
                // Re-upload: update existing payment row + delete old file
                $result = $this->pembayaranModel->replacePaymentProof(
                    $existingPayment['id'],
                    $nominalBayar,
                    null,
                    $bankTujuan,
                    $buktiBayarPath
                );
            } else {
                // First upload: insert new payment row
                $result = $this->pembayaranModel->uploadPaymentProof(
                    $pesananId,
                    $nominalBayar,
                    null,
                    $bankTujuan,
                    $buktiBayarPath
                );
            }

            if ($result['success']) {
                log_message('debug', 'Payment upload persisted. pesanan_id=' . $pesananId . ', is_reupload=' . ($isReupload ? '1' : '0') . ', payment_status=PENDING');

                return $this->response->setJSON([
                    'success' => true,
                    'message' => $isReupload ? 'Bukti pembayaran berhasil diganti' : 'Bukti pembayaran berhasil diupload',
                    'redirect_url' => base_url("customer/order/{$pesananId}")
                ]);
            }

            // Delete file if database update failed
            @unlink($uploadPath . '/' . $newName);

            return $this->paymentErrorResponse(500, $result['message'] ?? 'Gagal menyimpan data pembayaran.', [
                'pesanan_id' => $pesananId,
                'is_reupload' => $isReupload ? 1 : 0,
                'model_result' => $result
            ]);
        }

        return $this->paymentErrorResponse(500, 'Gagal mengupload file bukti pembayaran.', [
            'pesanan_id' => $pesananId,
            'target_file' => $newName
        ]);
    }

    /**
     * Cancel order (customer action)
     */
    public function cancelOrder()
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'PELANGGAN') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Unauthorized'
            ])->setStatusCode(401);
        }

        $orderId = $this->request->getPost('order_id');
        $order = $this->pesananModel->find($orderId);

        // Verify ownership
        if (!$order || $order['user_id'] != session()->get('id')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan'
            ])->setStatusCode(404);
        }

        // Only allow cancellation if status is MENUNGGU_BAYAR
        if ($order['status_pesanan'] !== 'MENUNGGU_BAYAR') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Pesanan tidak dapat dibatalkan'
            ])->setStatusCode(400);
        }

        $result = $this->pesananModel->cancelOrder($orderId, 'Dibatalkan oleh pelanggan');

        if ($result) {
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
     * Mark order as completed by customer (for delivered courier orders).
     */
    public function completeOrder()
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'PELANGGAN') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Unauthorized'
            ])->setStatusCode(401);
        }

        $orderId = $this->request->getPost('order_id');
        $order = $this->pesananModel->find($orderId);

        // Verify ownership
        if (!$order || $order['user_id'] != session()->get('id')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan'
            ])->setStatusCode(404);
        }

        if ($order['tipe_pengiriman'] !== 'DIKIRIM_KURIR') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Pesanan ini bukan pengiriman kurir'
            ])->setStatusCode(400);
        }

        if ($order['status_pesanan'] !== 'DIKIRIM') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Pesanan belum dapat diselesaikan'
            ])->setStatusCode(400);
        }

        $result = $this->pesananModel->updateOrderStatus($orderId, 'SELESAI');

        if ($result) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Pesanan berhasil ditandai selesai'
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Gagal menyelesaikan pesanan'
        ])->setStatusCode(500);
    }

    private function paymentErrorResponse(int $statusCode, string $message, array $context = [], array $errors = [])
    {
        $logLevel = $statusCode >= 500 ? 'error' : 'debug';
        log_message($logLevel, 'Payment upload error response. status=' . $statusCode . ', message=' . $message . ', context=' . json_encode($context) . ', errors=' . json_encode($errors));

        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        return $this->response->setJSON($payload)->setStatusCode($statusCode);
    }

    private function resolvePaymentUploadMode(array $order, ?array $existingPayment): ?bool
    {
        $orderStatus = (string) ($order['status_pesanan'] ?? '');
        $paymentStatus = (string) ($existingPayment['status_pembayaran'] ?? '');

        // Re-upload: replace the existing PENDING proof (customer uploaded wrong file)
        if (
            in_array($orderStatus, ['MENUNGGU_BAYAR', 'MENUNGGU_VERIFIKASI'], true)
            && $existingPayment
            && $paymentStatus === 'PENDING'
        ) {
            return true;
        }

        // First upload OR new submission after admin rejected the proof (INVALID).
        // In both cases we INSERT a new payment record — the INVALID one stays as history.
        if (
            $orderStatus === 'MENUNGGU_BAYAR'
            && (!$existingPayment || $paymentStatus === 'INVALID')
        ) {
            return false;
        }

        return null;
    }
}
