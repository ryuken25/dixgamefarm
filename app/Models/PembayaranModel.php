<?php

namespace App\Models;

use CodeIgniter\Model;

class PembayaranModel extends Model
{
    protected $table = 'pembayaran';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'pesanan_id',
        'nominal_bayar',
        'nama_bank',
        'bank_tujuan',
        'bukti_bayar',
        'status_pembayaran',
        'tanggal_upload',
        'tanggal_verifikasi',
        'verifikator_id',
        'alasan_ditolak'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $skipValidation = false;

    /**
     * Upload payment proof
     */
    public function uploadPaymentProof($pesananId, $nominalBayar, $namaBank, $bankTujuan, $buktiBayarPath)
    {
        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            log_message('debug', 'PembayaranModel::uploadPaymentProof started. pesanan_id=' . $pesananId . ', nominal=' . $nominalBayar . ', bank_tujuan=' . (string) $bankTujuan . ', has_bank_pengirim=' . (!empty($namaBank) ? '1' : '0'));

            // Insert payment record
            $paymentId = $this->insert([
                'pesanan_id' => $pesananId,
                'nominal_bayar' => $nominalBayar,
                'nama_bank' => $namaBank,
                'bank_tujuan' => $bankTujuan,
                'bukti_bayar' => $buktiBayarPath,
                'status_pembayaran' => 'PENDING',
                'tanggal_upload' => date('Y-m-d H:i:s')
            ]);

            if (!$paymentId) {
                $db->transRollback();
                log_message('error', 'PembayaranModel::uploadPaymentProof insert failed. pesanan_id=' . $pesananId . ', errors=' . json_encode($this->errors()) . ', db_error=' . json_encode($db->error()));
                return [
                    'success' => false,
                    'message' => 'Gagal membuat transaksi pembayaran'
                ];
            }

            // Keep order in MENUNGGU_BAYAR. Payment verification is tracked via
            // pembayaran.status_pembayaran. Clear expired_at so the auto-expiry
            // cron does not cancel an order that is actively awaiting validation.
            $pesananModel = new PesananModel();
            log_message('debug', 'PembayaranModel::uploadPaymentProof normalizing order status to MENUNGGU_BAYAR. pesanan_id=' . $pesananId . ', payment_id=' . $paymentId);

            $updated = $pesananModel->update($pesananId, [
                'status_pesanan' => 'MENUNGGU_BAYAR',
                'expired_at' => null,
            ]);

            if (!$updated) {
                $db->transRollback();
                log_message('error', 'PembayaranModel::uploadPaymentProof failed updating order status. pesanan_id=' . $pesananId . ', errors=' . json_encode($pesananModel->errors()) . ', db_error=' . json_encode($db->error()));
                return [
                    'success' => false,
                    'message' => 'Gagal memperbarui status pesanan setelah pembayaran dibuat'
                ];
            }

            // Get order info for notification
            $pesanan = $pesananModel->find($pesananId);

            // Notify user
            $notifModel = new NotifikasiModel();
            $notifModel->createNotification(
                $pesanan['user_id'],
                'Bukti Pembayaran Diterima',
                "Bukti pembayaran untuk invoice {$pesanan['nomor_invoice']} telah diterima dan menunggu verifikasi admin."
            );

            $db->transCommit();

            // Send Telegram notification to admin (outside transaction - fire and forget)
            try {
                $telegramAdminService = new \App\Libraries\TelegramAdminService();
                $telegramAdminService->notifyPaymentUploaded((int) $paymentId, false);
            } catch (\Exception $e) {
                log_message('error', 'Telegram payment notification failed: ' . $e->getMessage());
            }

            return [
                'success' => true,
                'payment_id' => $paymentId
            ];

        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Payment upload failed. pesanan_id=' . $pesananId . ', message=' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Terjadi kendala saat membuat transaksi pembayaran'
            ];
        }
    }

    public function markOrderPaymentAsValid(int $pesananId, ?int $adminId = null, ?int $paymentId = null): array
    {
        $order = $this->db->table('pesanan')
            ->select('id, grand_total, metode_pembayaran')
            ->where('id', $pesananId)
            ->get()
            ->getRowArray();

        if (!$order) {
            return ['success' => false, 'message' => 'Pesanan tidak ditemukan'];
        }

        $timestamp = date('Y-m-d H:i:s');
        $targetPayment = null;

        if ($paymentId !== null) {
            $targetPayment = $this->where('id', $paymentId)
                ->where('pesanan_id', $pesananId)
                ->first();

            if (!$targetPayment) {
                return ['success' => false, 'message' => 'Pembayaran tidak ditemukan'];
            }
        }

        if (!$targetPayment) {
            $targetPayment = $this->where('pesanan_id', $pesananId)
                ->orderBy('created_at', 'DESC')
                ->orderBy('id', 'DESC')
                ->first();
        }

        if ($targetPayment) {
            $updated = $this->update($targetPayment['id'], [
                'status_pembayaran' => 'VALID',
                'tanggal_verifikasi' => $targetPayment['tanggal_verifikasi'] ?: $timestamp,
                'verifikator_id' => $adminId,
                'alasan_ditolak' => null,
            ]);

            if (!$updated) {
                return ['success' => false, 'message' => 'Gagal memvalidasi pembayaran'];
            }

            return ['success' => true, 'payment_id' => $targetPayment['id']];
        }

        $inserted = $this->insert([
            'pesanan_id' => $pesananId,
            'nominal_bayar' => $order['grand_total'],
            'nama_bank' => null,
            'bank_tujuan' => $order['metode_pembayaran'] ?? null,
            'bukti_bayar' => null,
            'status_pembayaran' => 'VALID',
            'tanggal_upload' => $timestamp,
            'tanggal_verifikasi' => $timestamp,
            'verifikator_id' => $adminId,
            'alasan_ditolak' => null,
        ]);

        if (!$inserted) {
            return ['success' => false, 'message' => 'Gagal membuat catatan pembayaran valid'];
        }

        return ['success' => true, 'payment_id' => $inserted];
    }

    /**
     * Replace existing PENDING payment proof (re-upload by customer when
     * they uploaded the wrong file). Keeps the order in MENUNGGU_VERIFIKASI
     * and deletes the previous bukti file from disk.
     */
    public function replacePaymentProof($paymentId, $nominalBayar, $namaBank, $bankTujuan, $buktiBayarPath)
    {
        log_message('debug', 'PembayaranModel::replacePaymentProof started. payment_id=' . $paymentId . ', nominal=' . $nominalBayar . ', bank_tujuan=' . (string) $bankTujuan);

        $payment = $this->find($paymentId);

        if (!$payment) {
            return ['success' => false, 'message' => 'Pembayaran tidak ditemukan'];
        }

        if ($payment['status_pembayaran'] !== 'PENDING') {
            return ['success' => false, 'message' => 'Bukti yang sudah diverifikasi tidak dapat diganti'];
        }

        $oldPath = $payment['bukti_bayar'] ?? null;

        $ok = $this->update($paymentId, [
            'nominal_bayar' => $nominalBayar,
            'nama_bank' => $namaBank,
            'bank_tujuan' => $bankTujuan,
            'bukti_bayar' => $buktiBayarPath,
            'tanggal_upload' => date('Y-m-d H:i:s'),
            'status_pembayaran' => 'PENDING',
            'alasan_ditolak' => null,
        ]);

        if (!$ok) {
            return ['success' => false, 'message' => 'Gagal memperbarui bukti pembayaran'];
        }

        // Delete old file after successful update (best-effort; ignore failure)
        if ($oldPath && $oldPath !== $buktiBayarPath) {
            $full = FCPATH . ltrim($oldPath, '/');
            if (is_file($full)) {
                @unlink($full);
            }
        }

        // Clear order expiry — re-uploaded proof is awaiting validation again
        $pesananModel = new PesananModel();
        $pesananModel->update($payment['pesanan_id'], ['expired_at' => null]);

        // Notify admin via Telegram (fire-and-forget)
        try {
            $telegramAdminService = new \App\Libraries\TelegramAdminService();
            $telegramAdminService->notifyPaymentUploaded((int) $paymentId, true);
        } catch (\Exception $e) {
            log_message('error', 'Telegram reupload notification failed: ' . $e->getMessage());
        }

        return ['success' => true, 'payment_id' => $paymentId];
    }

    /**
     * Verify payment (admin action)
     *
     * Uses a single manual transaction (transBegin/transCommit) to avoid
     * nested transaction issues. Stock release on rejection is inlined
     * instead of calling cancelOrder() which would start its own transaction.
     */
    public function verifyPayment($paymentId, $adminId, $isValid, $alasan = null)
    {
        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $payment = $this->find($paymentId);

            if (!$payment) {
                $db->transRollback();
                return ['success' => false, 'message' => 'Pembayaran tidak ditemukan'];
            }

            if (($payment['status_pembayaran'] ?? null) !== 'PENDING') {
                $db->transRollback();
                return ['success' => false, 'message' => 'Pembayaran ini sudah pernah diverifikasi'];
            }

            $newStatus = $isValid ? 'VALID' : 'INVALID';

            // Update order status
            $pesananModel = new PesananModel();
            $pesanan = $pesananModel->find($payment['pesanan_id']);

            if (!$pesanan) {
                $db->transRollback();
                return ['success' => false, 'message' => 'Pesanan tidak ditemukan'];
            }

            if ($isValid) {
                $paymentSync = $this->markOrderPaymentAsValid((int) $payment['pesanan_id'], (int) $adminId, (int) $paymentId);
                if (!$paymentSync['success']) {
                    $db->transRollback();
                    return $paymentSync;
                }

                if (in_array($pesanan['status_pesanan'], ['MENUNGGU_BAYAR', 'MENUNGGU_VERIFIKASI'], true)) {
                    $pesananModel->update($payment['pesanan_id'], [
                        'status_pesanan' => 'DIPROSES',
                        'expired_at' => null,
                    ]);
                }

                $notifMessage = "Pembayaran Anda untuk invoice {$pesanan['nomor_invoice']} telah diverifikasi. Pesanan sedang diproses.";
            } else {
                // Mark payment INVALID
                $this->update($paymentId, [
                    'status_pembayaran' => 'INVALID',
                    'tanggal_verifikasi' => date('Y-m-d H:i:s'),
                    'verifikator_id' => $adminId,
                    'alasan_ditolak' => $alasan,
                ]);

                // Reset order to MENUNGGU_BAYAR with a fresh 24-hour window so
                // the customer can upload corrected proof. Stock reservation is
                // intentionally kept — do NOT release here. Stock is only freed
                // when the order is explicitly cancelled or auto-expires via cron.
                $pesananModel->update($payment['pesanan_id'], [
                    'status_pesanan' => 'MENUNGGU_BAYAR',
                    'expired_at' => date('Y-m-d H:i:s', strtotime('+24 hours')),
                ]);

                $notifMessage = "Bukti pembayaran untuk invoice {$pesanan['nomor_invoice']} ditolak."
                    . ' Alasan: ' . ($alasan ?? 'Tidak disebutkan')
                    . '. Silakan login dan upload ulang bukti yang benar dalam 24 jam.';
            }

            // Notify user
            $notifModel = new NotifikasiModel();
            $notifModel->createNotification(
                $pesanan['user_id'],
                $isValid ? 'Pembayaran Diverifikasi' : 'Pembayaran Ditolak',
                $notifMessage
            );

            $db->transCommit();

            // Send invoice email to customer when payment is approved
            // (outside transaction - fire and forget, must not break the flow)
            if ($isValid) {
                try {
                    $emailService = new \App\Libraries\EmailService();
                    $emailService->sendInvoicePaid((int) $payment['pesanan_id']);
                } catch (\Throwable $e) {
                    log_message('error', 'Invoice email notification failed: ' . $e->getMessage());
                }
            }

            return [
                'success' => true,
                'message' => $isValid ? 'Pembayaran berhasil diverifikasi' : 'Pembayaran ditolak'
            ];

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Payment verification failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get pending payments (for admin)
     */
    public function getPendingPayments(?int $limit = null)
    {
        $builder = $this->select('pembayaran.id, pembayaran.pesanan_id, pembayaran.nominal_bayar, pembayaran.nama_bank, pembayaran.bank_tujuan, pembayaran.bukti_bayar, pembayaran.status_pembayaran, pembayaran.tanggal_upload, pembayaran.created_at, pesanan.nomor_invoice, pesanan.grand_total, users.nama_lengkap, users.email')
            ->join('pesanan', 'pesanan.id = pembayaran.pesanan_id')
            ->join('users', 'users.id = pesanan.user_id')
            ->whereIn('pesanan.status_pesanan', ['MENUNGGU_BAYAR', 'MENUNGGU_VERIFIKASI'])
            ->where('pembayaran.status_pembayaran', 'PENDING')
            ->orderBy('pembayaran.tanggal_upload', 'ASC');

        if ($limit !== null && $limit > 0) {
            $builder->limit($limit);
        }

        return $builder->findAll();
    }

    /**
     * Get payment by order ID
     */
    public function getPaymentByOrderId($pesananId)
    {
        return $this->where('pesanan_id', $pesananId)
            ->orderBy('created_at', 'DESC')
            ->first();
    }
}
