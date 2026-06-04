<?php

namespace App\Models;

use CodeIgniter\Model;

class PesananModel extends Model
{
    protected $table = 'pesanan';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'user_id',
        'nomor_invoice',
        'tanggal_pesanan',
        'expired_at',
        'grand_total',
        'status_pesanan',
        'kode_resi',
        'tipe_pengiriman',
        'metode_pembayaran',
        'nomor_rekening_tujuan',
        'nama_penerima',
        'email_penerima',
        'no_hp_penerima',
        'alamat_pengiriman',
        'catatan_pelanggan'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'user_id' => 'required|is_not_unique[users.id]',
        'nomor_invoice' => 'required|is_unique[pesanan.nomor_invoice]',
        'grand_total' => 'required|decimal|greater_than[0]',
        'tipe_pengiriman' => 'required|in_list[AMBIL_SENDIRI,DIKIRIM_KURIR]'
    ];

    protected $skipValidation = false;

    private function getMissingCheckoutSchemaColumns(): array
    {
        $db = \Config\Database::connect();
        $missing = [];

        $pesananFields = array_flip($db->getFieldNames($this->table));
        foreach (['metode_pembayaran', 'nomor_rekening_tujuan', 'nama_penerima', 'email_penerima', 'no_hp_penerima', 'alamat_pengiriman'] as $field) {
            if (!isset($pesananFields[$field])) {
                $missing[] = $this->table . '.' . $field;
            }
        }

        $detailFields = array_flip($db->getFieldNames('detail_pesanan'));
        if (!isset($detailFields['is_preorder_item'])) {
            $missing[] = 'detail_pesanan.is_preorder_item';
        }

        return $missing;
    }

    /**
     * Generate unique invoice number
     */
    public function generateInvoiceNumber(): string
    {
        $date = date('Ymd');
        $random = rand(1000, 9999);
        $invoice = "INV-{$date}-{$random}";

        // Check if invoice exists, regenerate if needed
        while ($this->where('nomor_invoice', $invoice)->first()) {
            $random = rand(1000, 9999);
            $invoice = "INV-{$date}-{$random}";
        }

        return $invoice;
    }

    /**
     * Create order with items - CRITICAL TRANSACTION LOGIC
     * Uses manual transaction control (transBegin/transCommit/transRollback)
     * with SELECT ... FOR UPDATE row locking for ACID compliance.
     */
    public function createOrder($userId, $cartItems, $tipePengiriman, $catatan = null, $metodePembayaran = null, $nomorRekeningTujuan = null, array $checkoutSnapshot = [])
    {
        $missingColumns = $this->getMissingCheckoutSchemaColumns();
        if ($missingColumns !== []) {
            log_message('error', 'Checkout schema mismatch detected before createOrder. user_id=' . $userId . ', missing=' . implode(',', $missingColumns));
            return [
                'success' => false,
                'message' => 'Checkout sedang diperbarui. Silakan muat ulang halaman lalu coba lagi.'
            ];
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        log_message('debug', 'PesananModel::createOrder started. user_id=' . $userId . ', items=' . count($cartItems) . ', tipe_pengiriman=' . (string) $tipePengiriman . ', metode_pembayaran=' . (string) $metodePembayaran . ', has_catatan=' . (!empty($catatan) ? '1' : '0') . ', has_checkout_snapshot=' . (!empty($checkoutSnapshot) ? '1' : '0'));

        try {
            // Calculate grand total and validate stock
            $grandTotal = 0;
            $orderItems = [];

            $produkModel = new ProdukModel();

            foreach ($cartItems as $item) {
                // Pre-order items skip the stock reservation step entirely —
                // stock doesn't exist yet, admin fulfills when ready.
                $isPreorder = !empty($item['is_preorder']);

                if (!$isPreorder) {
                    // Reserve stock with FOR UPDATE row locking (ACID compliance)
                    // This locks the row and prevents concurrent overbooking
                    if (!$produkModel->reserveStock($item['produk_id'], $item['jumlah'])) {
                        $db->transRollback();
                        return [
                            'success' => false,
                            'message' => "Stok tidak mencukupi untuk {$item['nama_ayam']}"
                        ];
                    }
                }

                $subtotal = $item['harga'] * $item['jumlah'];
                $grandTotal += $subtotal;

                $orderItems[] = [
                    'produk_id' => $item['produk_id'],
                    'harga_satuan_snapshot' => $item['harga'], // Price snapshot!
                    'jumlah' => $item['jumlah'],
                    'subtotal' => $subtotal,
                    'is_preorder_item' => $isPreorder ? 1 : 0,
                ];
            }

            // Create order
            $invoiceNumber = $this->generateInvoiceNumber();
            $tanggalPesanan = date('Y-m-d H:i:s');
            $expiredAt = date('Y-m-d H:i:s', strtotime('+24 hours')); // 24-hour payment window

            log_message('debug', 'PesananModel::createOrder creating order record. user_id=' . $userId . ', invoice=' . $invoiceNumber . ', grand_total=' . $grandTotal . ', tipe_pengiriman=' . (string) $tipePengiriman . ', metode_pembayaran=' . (string) $metodePembayaran);

            $orderId = $this->insert([
                'user_id' => $userId,
                'nomor_invoice' => $invoiceNumber,
                'tanggal_pesanan' => $tanggalPesanan,
                'expired_at' => $expiredAt,
                'grand_total' => $grandTotal,
                'status_pesanan' => 'MENUNGGU_BAYAR',
                'tipe_pengiriman' => $tipePengiriman,
                'metode_pembayaran' => $metodePembayaran,
                'nomor_rekening_tujuan' => $nomorRekeningTujuan,
                'nama_penerima' => $checkoutSnapshot['nama_lengkap'] ?? null,
                'email_penerima' => $checkoutSnapshot['email'] ?? null,
                'no_hp_penerima' => $checkoutSnapshot['no_hp'] ?? null,
                'alamat_pengiriman' => $checkoutSnapshot['alamat_lengkap'] ?? null,
                'catatan_pelanggan' => $catatan
            ]);

            if (!$orderId) {
                $db->transRollback();
                log_message('error', 'PesananModel::createOrder pesanan insert failed. user_id=' . $userId . ', errors=' . json_encode($this->errors()) . ', db_error=' . json_encode($db->error()));
                return [
                    'success' => false,
                    'message' => 'Gagal membuat pesanan'
                ];
            }

            // Insert order details (stock already reserved above)
            $detailModel = new DetailPesananModel();

            foreach ($orderItems as $item) {
                $inserted = $detailModel->insert([
                    'pesanan_id' => $orderId,
                    'produk_id' => $item['produk_id'],
                    'harga_satuan_snapshot' => $item['harga_satuan_snapshot'],
                    'jumlah' => $item['jumlah'],
                    'subtotal' => $item['subtotal'],
                    'is_preorder_item' => $item['is_preorder_item'],
                ]);
                if (!$inserted) {
                    $db->transRollback();
                    log_message('error', 'PesananModel::createOrder detail insert failed. produk_id=' . $item['produk_id'] . ', errors=' . json_encode($detailModel->errors()));
                    return ['success' => false, 'message' => 'Gagal menyimpan detail pesanan'];
                }
            }

            // Clear cart
            $keranjangModel = new KeranjangModel();
            $keranjangModel->clearCart($userId);

            // Create notification
            $notifModel = new NotifikasiModel();
            $notifModel->createNotification(
                $userId,
                'Pesanan Berhasil Dibuat',
                "Pesanan Anda dengan nomor invoice {$invoiceNumber} telah dibuat. Silakan lakukan pembayaran dalam 24 jam."
            );

            log_message('debug', 'PesananModel::createOrder persisted order. order_id=' . $orderId . ', invoice=' . $invoiceNumber . ', grand_total=' . $grandTotal . ', status=MENUNGGU_BAYAR');

            $db->transCommit();

            // Send Telegram notification to admin (outside transaction - fire and forget)
            try {
                $telegramService = new \App\Libraries\TelegramService();
                $telegramService->notifyNewOrder($invoiceNumber, $grandTotal, count($orderItems));
            } catch (\Exception $e) {
                log_message('error', 'Telegram notification failed: ' . $e->getMessage());
                // Do not fail the order just because Telegram failed
            }

            // Send order-created email to customer (fire-and-forget, outside transaction)
            try {
                (new \App\Libraries\EmailService())->sendOrderCreated((int) $orderId);
            } catch (\Throwable $e) {
                log_message('error', 'Order-created email failed: ' . $e->getMessage());
            }

            return [
                'success' => true,
                'message' => 'Pesanan berhasil dibuat',
                'order_id' => $orderId,
                'invoice_number' => $invoiceNumber
            ];

        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Order creation failed. user_id=' . $userId . ', message=' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Terjadi kendala saat membuat pesanan. Silakan coba lagi.'
            ];
        }
    }

    /**
     * Get orders by user — includes latest payment status so the customer
     * dashboard can distinguish "needs upload" / "awaiting validation" /
     * "proof rejected" without a separate query per row.
     */
    public function getOrdersByUser($userId, $status = null)
    {
        $paymentSubquery = '(SELECT pb.status_pembayaran FROM pembayaran pb'
            . ' WHERE pb.pesanan_id = pesanan.id'
            . ' ORDER BY pb.created_at DESC LIMIT 1)';

        $builder = $this->db->table('pesanan')
            ->select(
                'pesanan.id, pesanan.nomor_invoice, pesanan.tanggal_pesanan,'
                . ' pesanan.grand_total, pesanan.status_pesanan,'
                . ' pesanan.tipe_pengiriman, pesanan.kode_resi,'
                . ' ' . $paymentSubquery . ' AS latest_payment_status',
                false
            )
            ->where('pesanan.user_id', $userId);

        if ($status) {
            $builder->where('pesanan.status_pesanan', $status);
        }

        return $builder->orderBy('pesanan.tanggal_pesanan', 'DESC')->get()->getResultArray();
    }

    /**
     * Get order with details
     */
    public function getOrderWithDetails($orderId)
    {
        $order = $this->select('pesanan.*, users.nama_lengkap AS akun_nama_lengkap, users.email AS akun_email, users.no_hp AS akun_no_hp, users.alamat_lengkap AS akun_alamat_lengkap, COALESCE(pesanan.nama_penerima, users.nama_lengkap) AS nama_lengkap, COALESCE(pesanan.email_penerima, users.email) AS email, COALESCE(pesanan.no_hp_penerima, users.no_hp) AS no_hp, COALESCE(pesanan.alamat_pengiriman, users.alamat_lengkap) AS alamat_lengkap', false)
            ->join('users', 'users.id = pesanan.user_id', 'left')
            ->where('pesanan.id', $orderId)
            ->first();

        if (!$order) {
            return null;
        }

        $detailModel = new DetailPesananModel();
        $details = $detailModel->getDetailsByPesananId($orderId);

        $pembayaranModel = new PembayaranModel();
        $pembayaran = $pembayaranModel->where('pesanan_id', $orderId)->orderBy('created_at', 'DESC')->findAll();

        return [
            'order' => $order,
            'details' => $details,
            'pembayaran' => $pembayaran
        ];
    }

    /**
     * Cancel order and release stock
     * Prevents double-cancel by checking for both SELESAI and BATAL statuses.
     * Uses manual transactions with FOR UPDATE row locking.
     */
    public function cancelOrder($orderId, $reason = 'Dibatalkan oleh sistem')
    {
        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $order = $this->find($orderId);

            // CRITICAL: Prevent double-cancel and shipped/completed cancel
            // DIKIRIM orders have goods in transit — stock can't be released
            if (!$order || in_array($order['status_pesanan'], ['PESANAN_SIAP', 'DIKIRIM', 'SELESAI', 'BATAL'])) {
                $db->transRollback();
                return false;
            }

            // Release reserved stock back to available
            $detailModel = new DetailPesananModel();
            $details = $detailModel->where('pesanan_id', $orderId)->findAll();

            $produkModel = new ProdukModel();

            foreach ($details as $detail) {
                // Preorder items never reserved stock — nothing to release
                if (!empty($detail['is_preorder_item'])) {
                    continue;
                }
                if (!$produkModel->releaseStock($detail['produk_id'], $detail['jumlah'])) {
                    $db->transRollback();
                    return false;
                }
            }

            // Update order status
            $this->update($orderId, [
                'status_pesanan' => 'BATAL'
            ]);

            // Notify user
            $notifModel = new NotifikasiModel();
            $notifModel->createNotification(
                $order['user_id'],
                'Pesanan Dibatalkan',
                "Pesanan {$order['nomor_invoice']} telah dibatalkan. Alasan: {$reason}"
            );

            $db->transCommit();

            // Email pelanggan (fire-and-forget, di luar transaksi)
            try {
                (new \App\Libraries\EmailService())->sendOrderCancelled((int) $orderId, $reason);
            } catch (\Throwable $e) {
                log_message('error', 'Order-cancelled email failed: ' . $e->getMessage());
            }

            return true;

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Order cancellation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get expired orders (for background processing)
     */
    public function getExpiredOrders()
    {
        return $this->select('id, user_id, nomor_invoice, status_pesanan, expired_at')
            ->where('status_pesanan', 'MENUNGGU_BAYAR')
            ->where('expired_at IS NOT NULL', null, false)
            ->where('expired_at <', date('Y-m-d H:i:s'))
            ->findAll();
    }

    /**
     * Update order status
     * Uses manual transactions with FOR UPDATE row locking for SELESAI and BATAL.
     */
    public function updateOrderStatus($orderId, $newStatus, array $extraData = [])
    {
        $validStatuses = ['MENUNGGU_BAYAR', 'MENUNGGU_VERIFIKASI', 'DIPROSES', 'PESANAN_SIAP', 'DIKIRIM', 'SELESAI', 'BATAL'];
        $persistExtraData = $extraData;
        $adminId = isset($persistExtraData['admin_id']) ? (int) $persistExtraData['admin_id'] : null;
        unset($persistExtraData['admin_id']);

        if (!in_array($newStatus, $validStatuses)) {
            return false;
        }

        if ($newStatus === 'DIPROSES') {
            $db = \Config\Database::connect();
            $db->transBegin();

            try {
                $paymentResult = (new PembayaranModel())->markOrderPaymentAsValid((int) $orderId, $adminId);
                if (!$paymentResult['success']) {
                    $db->transRollback();
                    return false;
                }

                $updated = $this->update($orderId, array_merge([
                    'status_pesanan' => 'DIPROSES',
                    'expired_at' => null,
                ], $persistExtraData));

                if (!$updated) {
                    $db->transRollback();
                    return false;
                }

                $db->transCommit();
                return true;

            } catch (\Throwable $e) {
                $db->transRollback();
                log_message('error', 'Order processing status update failed: ' . $e->getMessage());
                return false;
            }
        }

        // If status is SELESAI, finalize stock (remove from dibooking)
        if ($newStatus === 'SELESAI') {
            $db = \Config\Database::connect();
            $db->transBegin();

            try {
                $detailModel = new DetailPesananModel();
                $details = $detailModel->where('pesanan_id', $orderId)->findAll();

                $produkModel = new ProdukModel();

                foreach ($details as $detail) {
                    // Preorder items never occupied stok_dibooking — nothing to finalize
                    if (!empty($detail['is_preorder_item'])) {
                        continue;
                    }
                    if (!$produkModel->finalizeStock($detail['produk_id'], $detail['jumlah'])) {
                        $db->transRollback();
                        return false;
                    }
                }

                $this->update($orderId, array_merge(['status_pesanan' => $newStatus], $persistExtraData));

                $db->transCommit();

                // Email pelanggan: pesanan selesai (fire-and-forget)
                try {
                    (new \App\Libraries\EmailService())->sendOrderCompleted((int) $orderId);
                } catch (\Throwable $e) {
                    log_message('error', 'Order-completed email failed: ' . $e->getMessage());
                }

                return true;

            } catch (\Exception $e) {
                $db->transRollback();
                log_message('error', 'Order completion failed: ' . $e->getMessage());
                return false;
            }
        }

        // If status is BATAL, release reserved stock back to available
        if ($newStatus === 'BATAL') {
            $db = \Config\Database::connect();
            $db->transBegin();

            try {
                $order = $this->find($orderId);

                // Prevent double-cancel
                if (!$order || in_array($order['status_pesanan'], ['PESANAN_SIAP', 'DIKIRIM', 'SELESAI', 'BATAL'])) {
                    $db->transRollback();
                    return false;
                }

                $detailModel = new DetailPesananModel();
                $details = $detailModel->where('pesanan_id', $orderId)->findAll();

                $produkModel = new ProdukModel();

                foreach ($details as $detail) {
                    // Preorder items never reserved stock — nothing to release
                    if (!empty($detail['is_preorder_item'])) {
                        continue;
                    }
                    if (!$produkModel->releaseStock($detail['produk_id'], $detail['jumlah'])) {
                        $db->transRollback();
                        return false;
                    }
                }

                $this->update($orderId, array_merge(['status_pesanan' => 'BATAL'], $persistExtraData));

                // Notify user
                $notifModel = new NotifikasiModel();
                $notifModel->createNotification(
                    $order['user_id'],
                    'Pesanan Dibatalkan',
                    "Pesanan {$order['nomor_invoice']} telah dibatalkan."
                );

                $db->transCommit();

                // Email pelanggan (fire-and-forget, di luar transaksi)
                try {
                    (new \App\Libraries\EmailService())->sendOrderCancelled((int) $orderId, 'Dibatalkan oleh admin');
                } catch (\Throwable $e) {
                    log_message('error', 'Order-cancelled email failed: ' . $e->getMessage());
                }

                return true;

            } catch (\Exception $e) {
                $db->transRollback();
                log_message('error', 'Order cancellation via status update failed: ' . $e->getMessage());
                return false;
            }
        }

        $updated = $this->update($orderId, array_merge(['status_pesanan' => $newStatus], $persistExtraData));

        // Email pelanggan untuk transisi DIKIRIM / PESANAN_SIAP (fire-and-forget).
        if ($updated && in_array($newStatus, ['DIKIRIM', 'PESANAN_SIAP'], true)) {
            try {
                (new \App\Libraries\EmailService())->sendOrderShipped((int) $orderId);
            } catch (\Throwable $e) {
                log_message('error', 'Order-shipped email failed: ' . $e->getMessage());
            }
        }

        return $updated;
    }

    /**
     * Get all orders with user info (for admin)
     */
    public function getAllOrdersWithUser($status = null, $limit = 100)
    {
        $builder = $this->select('pesanan.id, pesanan.user_id, pesanan.nomor_invoice, pesanan.tanggal_pesanan, pesanan.grand_total, pesanan.status_pesanan, pesanan.tipe_pengiriman, pesanan.kode_resi, COALESCE(pesanan.nama_penerima, users.nama_lengkap) AS nama_lengkap, COALESCE(pesanan.email_penerima, users.email) AS email, COALESCE(pesanan.no_hp_penerima, users.no_hp) AS no_hp', false)
            ->join('users', 'users.id = pesanan.user_id');

        if ($status) {
            $builder->where('pesanan.status_pesanan', $status);
        }

        return $builder->orderBy('pesanan.tanggal_pesanan', 'DESC')
            ->limit($limit)
            ->findAll();
    }
}
