<?php

namespace App\Libraries;

use App\Models\PesananModel;
use Config\Services;

/**
 * Mengirim email transaksional ke pelanggan.
 *
 * Event yang ditangani:
 *  - sendOrderCreated($pesananId)    Pesanan baru dibuat (perlu pembayaran)
 *  - sendInvoicePaid($pesananId)     Pembayaran sudah diverifikasi admin
 *  - sendOrderShipped($pesananId)    Pesanan dikirim / siap diambil
 *  - sendOrderCancelled($pesananId, $reason)  Pesanan dibatalkan / expired
 *
 * Konfigurasi SMTP dibaca dari Config\Email (yang mengambil nilai dari .env).
 * Semua pengiriman bersifat best-effort: kegagalan dicatat ke log dan tidak
 * boleh menggagalkan alur utama (checkout, verifikasi pembayaran, dll).
 */
class EmailService
{
    /**
     * Email untuk pesanan baru. Wajib include detail produk + deadline bayar.
     */
    public function sendOrderCreated(int $pesananId): bool
    {
        $data = $this->loadOrder($pesananId, __METHOD__);
        if ($data === null) {
            return false;
        }

        $order   = $data['order'];
        $details = $data['details'] ?? [];

        $subject = '[DIX Game Farm] Pesanan Diterima - ' . ($order['nomor_invoice'] ?? '-');
        $body    = $this->buildOrderCreatedHtml($order, $details);

        return $this->dispatch($order, $subject, $body, __METHOD__);
    }

    /**
     * Kirim invoice ke pelanggan setelah pembayaran diverifikasi valid.
     */
    public function sendInvoicePaid(int $pesananId): bool
    {
        $data = $this->loadOrder($pesananId, __METHOD__);
        if ($data === null) {
            return false;
        }

        $order   = $data['order'];
        $details = $data['details'] ?? [];

        $subject = 'Invoice ' . ($order['nomor_invoice'] ?? '-') . ' - Pembayaran Diverifikasi';
        $body    = $this->buildInvoiceHtml($order, $details);

        return $this->dispatch($order, $subject, $body, __METHOD__);
    }

    /**
     * Email saat pesanan dikirim (DIKIRIM) ATAU siap diambil (PESANAN_SIAP).
     */
    public function sendOrderShipped(int $pesananId): bool
    {
        $data = $this->loadOrder($pesananId, __METHOD__);
        if ($data === null) {
            return false;
        }

        $order   = $data['order'];
        $details = $data['details'] ?? [];

        $isPickup = ($order['tipe_pengiriman'] ?? '') === 'AMBIL_SENDIRI';
        $subject = $isPickup
            ? '[DIX Game Farm] Pesanan Siap Diambil - ' . ($order['nomor_invoice'] ?? '-')
            : '[DIX Game Farm] Pesanan Dikirim - ' . ($order['nomor_invoice'] ?? '-');
        $body    = $this->buildOrderShippedHtml($order, $details, $isPickup);

        return $this->dispatch($order, $subject, $body, __METHOD__);
    }

    /**
     * Email saat pesanan dibatalkan (manual cancel / auto-expired).
     */
    public function sendOrderCancelled(int $pesananId, string $reason = 'Pembayaran melewati batas waktu'): bool
    {
        $data = $this->loadOrder($pesananId, __METHOD__);
        if ($data === null) {
            return false;
        }

        $order = $data['order'];

        $subject = '[DIX Game Farm] Pesanan Dibatalkan - ' . ($order['nomor_invoice'] ?? '-');
        $body    = $this->buildOrderCancelledHtml($order, $reason);

        return $this->dispatch($order, $subject, $body, __METHOD__);
    }

    // ----------------------------------------------------------------
    // Internal helpers
    // ----------------------------------------------------------------

    /**
     * Ambil order + details dari DB. Return null kalau order tidak ada.
     */
    private function loadOrder(int $pesananId, string $caller): ?array
    {
        $data = (new PesananModel())->getOrderWithDetails($pesananId);

        if (!$data || empty($data['order'])) {
            log_message('error', $caller . ' - pesanan tidak ditemukan. id=' . $pesananId);
            return null;
        }

        return $data;
    }

    /**
     * Eksekusi pengiriman email. Cek konfigurasi, log hasilnya.
     * Selalu return bool (tidak throw) — caller pakai pola fire-and-forget.
     */
    private function dispatch(array $order, string $subject, string $body, string $caller): bool
    {
        $to = $this->resolveRecipient($order);
        if ($to === '') {
            log_message('info', $caller . ' - tidak ada email penerima, dilewati. invoice=' . ($order['nomor_invoice'] ?? '-'));
            return false;
        }

        $config = config('Email');
        if (empty($config->fromEmail) || empty($config->SMTPUser) || empty($config->SMTPPass)) {
            log_message('info', $caller . ' - SMTP belum dikonfigurasi (email.fromEmail/SMTPUser/SMTPPass kosong), dilewati.');
            return false;
        }

        try {
            $email = Services::email();
            $email->clear();
            $email->setTo($to);
            $email->setSubject($subject);
            $email->setMessage($body);

            if ($email->send(false)) {
                log_message('info', $caller . ' - terkirim ke ' . $to . ' (invoice ' . ($order['nomor_invoice'] ?? '-') . ')');
                return true;
            }

            log_message('error', $caller . ' - gagal kirim ke ' . $to . '. Debug: ' . $email->printDebugger(['headers']));
            return false;
        } catch (\Throwable $e) {
            log_message('error', $caller . ' - exception saat kirim ke ' . $to . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Pilih email penerima: utamakan email_penerima dari snapshot checkout,
     * fallback ke email akun user.
     */
    private function resolveRecipient(array $order): string
    {
        foreach ([$order['email_penerima'] ?? null, $order['email'] ?? null] as $candidate) {
            $candidate = is_string($candidate) ? trim($candidate) : '';
            if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                return $candidate;
            }
        }
        return '';
    }

    // ----------------------------------------------------------------
    // HTML builders — inline CSS only (email-client friendly).
    // ----------------------------------------------------------------

    /**
     * Susun isi email invoice (pembayaran sudah valid).
     */
    private function buildInvoiceHtml(array $order, array $details): string
    {
        $tableRows = $this->buildItemsTableRows($details);
        $grandTotal = isset($order['grand_total']) ? (float) $order['grand_total'] : 0.0;
        $nama       = esc($this->resolveCustomerName($order));
        $invoice    = esc($order['nomor_invoice'] ?? '-');
        $tanggal    = !empty($order['tanggal_pesanan'])
            ? date('d-m-Y H:i', strtotime($order['tanggal_pesanan']))
            : date('d-m-Y H:i');

        return $this->shell(
            'Pembayaran Anda telah diverifikasi. Berikut rincian pesanan Anda.',
            'Halo <b>' . $nama . '</b>,'
            . '<table style="width:100%;margin-bottom:12px"><tr>'
            . '<td>Invoice: <b>' . $invoice . '</b></td>'
            . '<td style="text-align:right">Tanggal: ' . $tanggal . '</td>'
            . '</tr></table>'
            . $this->buildItemsTable($tableRows, $grandTotal)
            . '<p style="margin-top:16px">Status pesanan Anda kini <b>sedang diproses</b>. Terima kasih telah berbelanja di DIX Game Farm.</p>'
        );
    }

    /**
     * Email order created — fokus ke instruksi bayar + deadline.
     */
    private function buildOrderCreatedHtml(array $order, array $details): string
    {
        $tableRows = $this->buildItemsTableRows($details);
        $grandTotal = isset($order['grand_total']) ? (float) $order['grand_total'] : 0.0;
        $nama    = esc($this->resolveCustomerName($order));
        $invoice = esc($order['nomor_invoice'] ?? '-');
        $metode  = esc($order['metode_pembayaran'] ?? '-');
        $rekening = esc($order['nomor_rekening_tujuan'] ?? '');
        $deadline = !empty($order['expired_at'])
            ? date('d-m-Y H:i', strtotime($order['expired_at'])) . ' WITA'
            : null;
        $orderUrl = base_url('customer/order/' . (int) ($order['id'] ?? 0));

        $deadlineBlock = '';
        if ($deadline) {
            $deadlineBlock = '<div style="background:#fff8e6;border-left:4px solid #d4881a;padding:12px 16px;margin:16px 0;color:#1a1a1a">'
                . '<b>Batas waktu pembayaran:</b> ' . esc($deadline) . '. '
                . 'Pesanan dibatalkan otomatis jika lewat batas waktu.'
                . '</div>';
        }

        return $this->shell(
            'Pesanan Anda berhasil dibuat. Mohon selesaikan pembayaran dalam batas waktu yang ditentukan.',
            'Halo <b>' . $nama . '</b>,'
            . '<p>Terima kasih sudah memesan di DIX Game Farm. Pesanan Anda menunggu pembayaran.</p>'
            . '<table style="width:100%;margin-bottom:12px">'
            . '<tr><td>Invoice: <b>' . $invoice . '</b></td><td style="text-align:right">Metode: ' . $metode
                . ($rekening !== '' ? ' (' . $rekening . ')' : '') . '</td></tr>'
            . '</table>'
            . $this->buildItemsTable($tableRows, $grandTotal)
            . $deadlineBlock
            . '<p style="text-align:center;margin:20px 0">'
            . '<a href="' . esc($orderUrl) . '" style="background:#2c7a2c;color:#fff;text-decoration:none;padding:10px 20px;border-radius:4px;display:inline-block">Lihat & Upload Bukti Bayar</a>'
            . '</p>'
        );
    }

    /**
     * Email pesanan dikirim / siap diambil.
     */
    private function buildOrderShippedHtml(array $order, array $details, bool $isPickup): string
    {
        $tableRows = $this->buildItemsTableRows($details);
        $grandTotal = isset($order['grand_total']) ? (float) $order['grand_total'] : 0.0;
        $nama    = esc($this->resolveCustomerName($order));
        $invoice = esc($order['nomor_invoice'] ?? '-');
        $resi    = esc($order['kode_resi'] ?? '');
        $orderUrl = base_url('customer/order/' . (int) ($order['id'] ?? 0));

        $resiBlock = '';
        if (!$isPickup && $resi !== '') {
            $resiBlock = '<p>Kode resi: <b style="color:#d4881a">' . $resi . '</b></p>';
        }

        $msg = $isPickup
            ? 'Pesanan Anda sudah siap diambil di lokasi farm.'
            : 'Pesanan Anda sudah dikirim. Mohon ditunggu kedatangannya.';

        return $this->shell(
            $msg,
            'Halo <b>' . $nama . '</b>,'
            . '<p>' . esc($msg) . '</p>'
            . '<p>Invoice: <b>' . $invoice . '</b></p>'
            . $resiBlock
            . $this->buildItemsTable($tableRows, $grandTotal)
            . '<p style="text-align:center;margin:20px 0">'
            . '<a href="' . esc($orderUrl) . '" style="background:#2c7a2c;color:#fff;text-decoration:none;padding:10px 20px;border-radius:4px;display:inline-block">Lihat Status Pesanan</a>'
            . '</p>'
        );
    }

    /**
     * Email pesanan dibatalkan / expired.
     */
    private function buildOrderCancelledHtml(array $order, string $reason): string
    {
        $nama    = esc($this->resolveCustomerName($order));
        $invoice = esc($order['nomor_invoice'] ?? '-');
        $total   = isset($order['grand_total']) ? (float) $order['grand_total'] : 0.0;
        $katalogUrl = base_url('katalog');

        return $this->shell(
            'Pesanan Anda telah dibatalkan.',
            'Halo <b>' . $nama . '</b>,'
            . '<div style="background:#fdebe6;border-left:4px solid #c0392b;padding:12px 16px;margin:12px 0">'
            . '<b>Pesanan ' . $invoice . ' dibatalkan.</b><br>'
            . 'Alasan: ' . esc($reason)
            . '</div>'
            . '<p>Nilai pesanan: <b>Rp ' . number_format($total, 0, ',', '.') . '</b></p>'
            . '<p>Stok yang sebelumnya kami pesankan untuk Anda sudah dilepas kembali. '
            . 'Kalau masih berminat dengan produk yang sama, silakan buat pesanan baru.</p>'
            . '<p style="text-align:center;margin:20px 0">'
            . '<a href="' . esc($katalogUrl) . '" style="background:#d4881a;color:#fff;text-decoration:none;padding:10px 20px;border-radius:4px;display:inline-block">Belanja Lagi</a>'
            . '</p>'
        );
    }

    /**
     * Wrapper layout common ke semua email (header, footer, container).
     */
    private function shell(string $intro, string $bodyHtml): string
    {
        return '<div style="font-family:Arial,sans-serif;max-width:640px;margin:auto;color:#333">'
            . '<h2 style="color:#2c7a2c;margin-bottom:4px">DIX Game Farm</h2>'
            . '<p style="margin-top:0;color:#666">' . esc($intro) . '</p>'
            . $bodyHtml
            . '<hr style="border:none;border-top:1px solid #eee;margin:20px 0">'
            . '<p style="color:#999;font-size:12px">Email ini dikirim otomatis. Mohon tidak membalas email ini.</p>'
            . '</div>';
    }

    private function buildItemsTableRows(array $details): string
    {
        $rows = '';
        foreach ($details as $item) {
            $nama  = $item['nama_ayam'] ?? 'Produk';
            $qty   = (int) ($item['jumlah'] ?? 0);
            $harga = (float) ($item['harga_satuan_snapshot'] ?? 0);
            $sub   = isset($item['subtotal']) ? (float) $item['subtotal'] : ($qty * $harga);

            $rows .= '<tr>'
                . '<td style="padding:8px;border:1px solid #ddd">' . esc($nama) . '</td>'
                . '<td style="padding:8px;border:1px solid #ddd;text-align:center">' . $qty . '</td>'
                . '<td style="padding:8px;border:1px solid #ddd;text-align:right">Rp ' . number_format($harga, 0, ',', '.') . '</td>'
                . '<td style="padding:8px;border:1px solid #ddd;text-align:right">Rp ' . number_format($sub, 0, ',', '.') . '</td>'
                . '</tr>';
        }
        return $rows;
    }

    private function buildItemsTable(string $rows, float $grandTotal): string
    {
        return '<table style="border-collapse:collapse;width:100%">'
            . '<tr style="background:#f2f2f2">'
            . '<th style="padding:8px;border:1px solid #ddd;text-align:left">Produk</th>'
            . '<th style="padding:8px;border:1px solid #ddd">Qty</th>'
            . '<th style="padding:8px;border:1px solid #ddd;text-align:right">Harga</th>'
            . '<th style="padding:8px;border:1px solid #ddd;text-align:right">Subtotal</th>'
            . '</tr>'
            . $rows
            . '<tr>'
            . '<td colspan="3" style="padding:8px;border:1px solid #ddd;text-align:right"><b>Grand Total</b></td>'
            . '<td style="padding:8px;border:1px solid #ddd;text-align:right"><b>Rp ' . number_format($grandTotal, 0, ',', '.') . '</b></td>'
            . '</tr>'
            . '</table>';
    }

    private function resolveCustomerName(array $order): string
    {
        $candidates = [$order['nama_penerima'] ?? null, $order['nama_lengkap'] ?? null];
        foreach ($candidates as $name) {
            $name = is_string($name) ? trim($name) : '';
            if ($name !== '') {
                return $name;
            }
        }
        return 'Pelanggan';
    }
}
