<?php

namespace App\Libraries;

use App\Models\PesananModel;
use Config\Services;

/**
 * Mengirim email transaksional ke pelanggan.
 *
 * Event yang ditangani (sesuai urutan order lifecycle):
 *   - sendOrderCreated($pesananId)    Pesanan baru, perlu pembayaran
 *   - sendInvoicePaid($pesananId)     Pembayaran sudah diverifikasi admin
 *   - sendOrderShipped($pesananId)    Pesanan dikirim / siap diambil
 *   - sendOrderCompleted($pesananId)  Pesanan selesai (closed loop)
 *   - sendOrderCancelled($pesananId, $reason)  Pesanan dibatalkan / expired
 *
 * Konfigurasi SMTP dibaca dari Config\Email (mengambil nilai dari .env).
 * Semua pengiriman bersifat best-effort: kegagalan dicatat ke log dan tidak
 * boleh menggagalkan alur utama (checkout, verifikasi, dll).
 */
class EmailService
{
    // ---- Branding & contact info — diambil sekali di sini ----
    private const FARM_NAME      = 'DIX Game Farm';
    private const FARM_ADDRESS   = 'Br. Anyar, Tabanan, Bali';
    private const FARM_WHATSAPP  = '+62 812-3456-7890';
    private const FARM_EMAIL     = 'dixgamefarm@gmail.com';
    private const COLOR_PRIMARY  = '#2c7a2c';
    private const COLOR_ACCENT   = '#d4881a';
    private const COLOR_DANGER   = '#c0392b';

    public function sendOrderCreated(int $pesananId): bool
    {
        $data = $this->loadOrder($pesananId, __METHOD__);
        if ($data === null) {
            return false;
        }
        $subject = '[' . self::FARM_NAME . '] Pesanan Diterima - ' . ($data['order']['nomor_invoice'] ?? '-');
        $body    = $this->buildOrderCreatedHtml($data['order'], $data['details'] ?? []);
        return $this->dispatch($data['order'], $subject, $body, __METHOD__);
    }

    public function sendInvoicePaid(int $pesananId): bool
    {
        $data = $this->loadOrder($pesananId, __METHOD__);
        if ($data === null) {
            return false;
        }
        $subject = 'Invoice ' . ($data['order']['nomor_invoice'] ?? '-') . ' - Pembayaran Diverifikasi';
        $body    = $this->buildInvoiceHtml($data['order'], $data['details'] ?? []);
        return $this->dispatch($data['order'], $subject, $body, __METHOD__);
    }

    public function sendOrderShipped(int $pesananId): bool
    {
        $data = $this->loadOrder($pesananId, __METHOD__);
        if ($data === null) {
            return false;
        }
        $isPickup = ($data['order']['tipe_pengiriman'] ?? '') === 'AMBIL_SENDIRI';
        $subject  = $isPickup
            ? '[' . self::FARM_NAME . '] Pesanan Siap Diambil - ' . ($data['order']['nomor_invoice'] ?? '-')
            : '[' . self::FARM_NAME . '] Pesanan Dikirim - ' . ($data['order']['nomor_invoice'] ?? '-');
        $body = $this->buildOrderShippedHtml($data['order'], $data['details'] ?? [], $isPickup);
        return $this->dispatch($data['order'], $subject, $body, __METHOD__);
    }

    public function sendOrderCompleted(int $pesananId): bool
    {
        $data = $this->loadOrder($pesananId, __METHOD__);
        if ($data === null) {
            return false;
        }
        $subject = '[' . self::FARM_NAME . '] Terima Kasih - Pesanan ' . ($data['order']['nomor_invoice'] ?? '-') . ' Selesai';
        $body    = $this->buildOrderCompletedHtml($data['order'], $data['details'] ?? []);
        return $this->dispatch($data['order'], $subject, $body, __METHOD__);
    }

    public function sendOrderCancelled(int $pesananId, string $reason = 'Pembayaran melewati batas waktu'): bool
    {
        $data = $this->loadOrder($pesananId, __METHOD__);
        if ($data === null) {
            return false;
        }
        $subject = '[' . self::FARM_NAME . '] Pesanan Dibatalkan - ' . ($data['order']['nomor_invoice'] ?? '-');
        $body    = $this->buildOrderCancelledHtml($data['order'], $reason);
        return $this->dispatch($data['order'], $subject, $body, __METHOD__);
    }

    // ================================================================
    // Internal helpers
    // ================================================================

    private function loadOrder(int $pesananId, string $caller): ?array
    {
        $data = (new PesananModel())->getOrderWithDetails($pesananId);
        if (!$data || empty($data['order'])) {
            log_message('error', $caller . ' - pesanan tidak ditemukan. id=' . $pesananId);
            return null;
        }
        return $data;
    }

    private function dispatch(array $order, string $subject, string $body, string $caller): bool
    {
        $to = $this->resolveRecipient($order);
        if ($to === '') {
            log_message('info', $caller . ' - tidak ada email penerima, dilewati. invoice=' . ($order['nomor_invoice'] ?? '-'));
            return false;
        }

        $config = config('Email');
        if (empty($config->fromEmail) || empty($config->SMTPUser) || empty($config->SMTPPass)) {
            log_message('info', $caller . ' - SMTP belum dikonfigurasi, dilewati.');
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

    private function resolveCustomerName(array $order): string
    {
        foreach ([$order['nama_penerima'] ?? null, $order['nama_lengkap'] ?? null] as $name) {
            $name = is_string($name) ? trim($name) : '';
            if ($name !== '') {
                return $name;
            }
        }
        return 'Pelanggan';
    }

    // ================================================================
    // HTML builders — inline CSS only.
    // ================================================================

    /**
     * Email 1: pesanan baru — perlu pembayaran. Berisi instruksi transfer
     * lengkap, deadline countdown, dan tombol upload bukti.
     */
    private function buildOrderCreatedHtml(array $order, array $details): string
    {
        $nama       = esc($this->resolveCustomerName($order));
        $invoice    = esc($order['nomor_invoice'] ?? '-');
        $metode     = esc($order['metode_pembayaran'] ?? '-');
        $rekening   = esc($order['nomor_rekening_tujuan'] ?? '');
        $grandTotal = isset($order['grand_total']) ? (float) $order['grand_total'] : 0.0;
        $deadline   = !empty($order['expired_at'])
            ? date('d M Y, H:i', strtotime($order['expired_at'])) . ' WITA'
            : null;
        $tipeKirim  = ($order['tipe_pengiriman'] ?? '') === 'DIKIRIM_KURIR' ? 'Dikirim Kurir' : 'Ambil Sendiri di Farm';
        $alamatKirim = esc($order['alamat_pengiriman'] ?? '');
        $catatan    = esc($order['catatan_pelanggan'] ?? '');
        $orderUrl   = base_url('customer/order/' . (int) ($order['id'] ?? 0));
        $uploadUrl  = base_url('customer/order/' . (int) ($order['id'] ?? 0) . '/upload-payment');

        $hasPo = $this->hasPreorderItems($details);

        $instructions = '<table style="width:100%;background:#f8f9fa;border:1px solid #e5e9ef;border-radius:6px;margin:16px 0">'
            . '<tr><td style="padding:14px 18px">'
            . '<div style="font-weight:bold;color:' . self::COLOR_PRIMARY . ';margin-bottom:8px;font-size:14px">Instruksi Pembayaran</div>'
            . '<div style="font-size:13px;line-height:1.7">'
            . '<b>1.</b> Transfer <b>tepat Rp ' . number_format($grandTotal, 0, ',', '.') . '</b> '
            . 'ke rekening: <b>' . $metode . ($rekening !== '' ? ' - ' . $rekening : '') . '</b> a.n. <b>' . self::FARM_NAME . '</b><br>'
            . '<b>2.</b> Setelah transfer, klik tombol di bawah & upload bukti transfer (max 2MB, JPG/PNG)<br>'
            . '<b>3.</b> Admin akan verifikasi dalam 1x24 jam<br>'
            . '<b>4.</b> Setelah valid, pesanan langsung diproses'
            . '</div></td></tr></table>';

        $deadlineBlock = $deadline
            ? '<div style="background:#fff8e6;border-left:4px solid ' . self::COLOR_ACCENT . ';padding:12px 16px;margin:14px 0;font-size:13px">'
            . '<b style="color:#8a5a0a">Batas Waktu Pembayaran:</b> ' . esc($deadline)
            . '<br><span style="color:#666">Pesanan otomatis dibatalkan & stok dilepas jika lewat batas waktu.</span>'
            . '</div>'
            : '';

        $poBlock = $hasPo
            ? '<div style="background:#eef6ff;border-left:4px solid #3b82f6;padding:12px 16px;margin:14px 0;font-size:13px">'
            . '<b style="color:#1e40af">Pesanan PRE-ORDER</b><br>'
            . 'Ada item PO di pesanan ini. Estimasi waktu pengerjaan akan dikonfirmasi admin setelah pembayaran diverifikasi.'
            . '</div>'
            : '';

        $orderInfo = '<table style="width:100%;font-size:13px;margin-bottom:10px">'
            . $this->kvRow('Nomor Invoice', '<b>' . $invoice . '</b>')
            . $this->kvRow('Tipe Pengiriman', esc($tipeKirim))
            . ($alamatKirim !== '' && $tipeKirim === 'Dikirim Kurir' ? $this->kvRow('Alamat Kirim', nl2br($alamatKirim)) : '')
            . ($catatan !== '' ? $this->kvRow('Catatan', $catatan) : '')
            . '</table>';

        $cta = '<table style="width:100%;margin:20px 0"><tr><td align="center">'
            . '<a href="' . esc($uploadUrl) . '" style="background:' . self::COLOR_ACCENT . ';color:#fff;text-decoration:none;padding:13px 30px;border-radius:5px;display:inline-block;font-weight:bold;font-size:14px">Upload Bukti Pembayaran</a>'
            . '<div style="margin-top:8px;font-size:12px">Atau <a href="' . esc($orderUrl) . '" style="color:' . self::COLOR_PRIMARY . '">lihat detail pesanan</a></div>'
            . '</td></tr></table>';

        return $this->shell(
            'Pesanan #' . $invoice,
            'Pesanan Anda berhasil dibuat. Mohon selesaikan pembayaran sesuai instruksi di bawah.',
            $this->greeting($nama)
            . $orderInfo
            . $this->buildItemsTable($details, $grandTotal)
            . $instructions
            . $deadlineBlock
            . $poBlock
            . $cta
        );
    }

    /**
     * Email 2: invoice — pembayaran sudah diverifikasi. Berisi tabel detail
     * dengan branding, status badge "DIPROSES", info next step.
     */
    private function buildInvoiceHtml(array $order, array $details): string
    {
        $nama       = esc($this->resolveCustomerName($order));
        $invoice    = esc($order['nomor_invoice'] ?? '-');
        $grandTotal = isset($order['grand_total']) ? (float) $order['grand_total'] : 0.0;
        $tanggal    = !empty($order['tanggal_pesanan'])
            ? date('d M Y, H:i', strtotime($order['tanggal_pesanan']))
            : date('d M Y, H:i');
        $tipeKirim  = ($order['tipe_pengiriman'] ?? '') === 'DIKIRIM_KURIR' ? 'Dikirim Kurir' : 'Ambil Sendiri di Farm';
        $orderUrl   = base_url('customer/order/' . (int) ($order['id'] ?? 0));
        $hasPo      = $this->hasPreorderItems($details);

        $badge = $this->statusBadge('DIPROSES', self::COLOR_PRIMARY);

        $nextStep = $hasPo
            ? 'Tim kami akan menghubungi Anda untuk konfirmasi estimasi pengerjaan PO Anda.'
            : (($order['tipe_pengiriman'] ?? '') === 'DIKIRIM_KURIR'
                ? 'Pesanan akan dipack & dikirim ke alamat Anda. Email berikutnya berisi kode resi.'
                : 'Pesanan akan disiapkan. Anda akan menerima email saat pesanan siap diambil di farm.');

        return $this->shell(
            'Pembayaran Anda Telah Diverifikasi',
            'Terima kasih, pembayaran untuk invoice ' . $invoice . ' sudah kami terima.',
            $this->greeting($nama)
            . '<div style="text-align:center;margin:14px 0">' . $badge . '</div>'
            . '<table style="width:100%;font-size:13px;margin-bottom:10px">'
            . $this->kvRow('Nomor Invoice', '<b>' . $invoice . '</b>')
            . $this->kvRow('Tanggal Pesanan', $tanggal)
            . $this->kvRow('Tipe Pengiriman', esc($tipeKirim))
            . '</table>'
            . $this->buildItemsTable($details, $grandTotal)
            . '<div style="background:#e8f5d8;border-left:4px solid ' . self::COLOR_PRIMARY . ';padding:12px 16px;margin:16px 0;font-size:13px">'
            . '<b style="color:' . self::COLOR_PRIMARY . '">Langkah Berikutnya:</b><br>' . esc($nextStep)
            . '</div>'
            . '<table style="width:100%;margin:18px 0"><tr><td align="center">'
            . '<a href="' . esc($orderUrl) . '" style="background:' . self::COLOR_PRIMARY . ';color:#fff;text-decoration:none;padding:12px 28px;border-radius:5px;display:inline-block;font-weight:bold">Lihat Detail Pesanan</a>'
            . '</td></tr></table>'
        );
    }

    /**
     * Email 3: pesanan dikirim / siap diambil.
     */
    private function buildOrderShippedHtml(array $order, array $details, bool $isPickup): string
    {
        $nama       = esc($this->resolveCustomerName($order));
        $invoice    = esc($order['nomor_invoice'] ?? '-');
        $resi       = esc($order['kode_resi'] ?? '');
        $grandTotal = isset($order['grand_total']) ? (float) $order['grand_total'] : 0.0;
        $alamat     = esc($order['alamat_pengiriman'] ?? '');
        $orderUrl   = base_url('customer/order/' . (int) ($order['id'] ?? 0));

        $headline = $isPickup
            ? 'Pesanan Siap Diambil di Farm'
            : 'Pesanan Anda Dalam Perjalanan';
        $detailRows = $isPickup
            ? $this->kvRow('Status', $this->statusBadge('SIAP DIAMBIL', self::COLOR_ACCENT))
              . $this->kvRow('Alamat Farm', esc(self::FARM_ADDRESS))
              . $this->kvRow('Jam Operasional', '08:00 – 17:00 WITA (Senin – Sabtu)')
              . $this->kvRow('Bawa', 'KTP / identitas diri untuk verifikasi')
            : $this->kvRow('Status', $this->statusBadge('DIKIRIM', self::COLOR_PRIMARY))
              . ($resi !== '' ? $this->kvRow('Kode Resi', '<b style="color:' . self::COLOR_ACCENT . ';font-size:15px">' . $resi . '</b>') : '')
              . ($alamat !== '' ? $this->kvRow('Tujuan', nl2br($alamat)) : '');

        $reminder = $isPickup
            ? 'Mohon konfirmasi via WhatsApp <b>' . self::FARM_WHATSAPP . '</b> sebelum datang untuk memastikan pesanan siap.'
            : 'Setelah pesanan sampai dan kondisi sesuai, mohon konfirmasi penerimaan via aplikasi agar status pesanan bisa kami tandai SELESAI.';

        return $this->shell(
            $headline,
            $isPickup ? 'Pesanan Anda sudah siap untuk diambil di lokasi farm.' : 'Pesanan Anda sudah dikirim. Mohon ditunggu kedatangannya.',
            $this->greeting($nama)
            . '<table style="width:100%;font-size:13px;margin-bottom:10px">'
            . $this->kvRow('Nomor Invoice', '<b>' . $invoice . '</b>')
            . $detailRows
            . '</table>'
            . $this->buildItemsTable($details, $grandTotal)
            . '<div style="background:#fff8e6;border-left:4px solid ' . self::COLOR_ACCENT . ';padding:12px 16px;margin:16px 0;font-size:13px">'
            . esc($reminder)
            . '</div>'
            . '<table style="width:100%;margin:18px 0"><tr><td align="center">'
            . '<a href="' . esc($orderUrl) . '" style="background:' . self::COLOR_PRIMARY . ';color:#fff;text-decoration:none;padding:12px 28px;border-radius:5px;display:inline-block;font-weight:bold">Lihat Status Pesanan</a>'
            . '</td></tr></table>'
        );
    }

    /**
     * Email 4: pesanan selesai — closing loop, ucapan terima kasih.
     */
    private function buildOrderCompletedHtml(array $order, array $details): string
    {
        $nama       = esc($this->resolveCustomerName($order));
        $invoice    = esc($order['nomor_invoice'] ?? '-');
        $grandTotal = isset($order['grand_total']) ? (float) $order['grand_total'] : 0.0;
        $orderUrl   = base_url('customer/order/' . (int) ($order['id'] ?? 0));
        $katalogUrl = base_url('katalog');

        return $this->shell(
            'Pesanan Selesai - Terima Kasih!',
            'Pesanan ' . $invoice . ' sudah ditandai SELESAI. Senang melayani Anda.',
            $this->greeting($nama)
            . '<div style="text-align:center;margin:18px 0">'
            . $this->statusBadge('SELESAI', self::COLOR_PRIMARY)
            . '</div>'
            . '<p style="font-size:13px">Pesanan Anda dengan invoice <b>' . $invoice . '</b> telah selesai dan ditandai SELESAI di sistem. '
            . 'Kami berharap produk yang Anda terima sesuai harapan.</p>'
            . $this->buildItemsTable($details, $grandTotal)
            . '<div style="background:#e8f5d8;border-left:4px solid ' . self::COLOR_PRIMARY . ';padding:14px 18px;margin:16px 0;font-size:13px">'
            . '<b>Butuh produk lagi?</b><br>'
            . 'Cek katalog terbaru kami — ada Boston Roundhead, Moonwalker Grey, Popeye Grey, DOC Joper, dan produk pakan/vitamin.<br>'
            . 'Kalau ada pertanyaan atau kebutuhan PO khusus, langsung WA: <b>' . self::FARM_WHATSAPP . '</b>'
            . '</div>'
            . '<table style="width:100%;margin:18px 0"><tr><td align="center">'
            . '<a href="' . esc($katalogUrl) . '" style="background:' . self::COLOR_ACCENT . ';color:#fff;text-decoration:none;padding:12px 28px;border-radius:5px;display:inline-block;font-weight:bold">Belanja Lagi</a>'
            . '&nbsp;&nbsp;'
            . '<a href="' . esc($orderUrl) . '" style="background:#fff;color:' . self::COLOR_PRIMARY . ';text-decoration:none;padding:12px 28px;border-radius:5px;display:inline-block;font-weight:bold;border:1px solid ' . self::COLOR_PRIMARY . '">Lihat Riwayat</a>'
            . '</td></tr></table>'
        );
    }

    /**
     * Email cancel: pesanan dibatalkan.
     */
    private function buildOrderCancelledHtml(array $order, string $reason): string
    {
        $nama       = esc($this->resolveCustomerName($order));
        $invoice    = esc($order['nomor_invoice'] ?? '-');
        $grandTotal = isset($order['grand_total']) ? (float) $order['grand_total'] : 0.0;
        $katalogUrl = base_url('katalog');

        return $this->shell(
            'Pesanan Dibatalkan',
            'Pesanan ' . $invoice . ' telah dibatalkan.',
            $this->greeting($nama)
            . '<div style="text-align:center;margin:16px 0">'
            . $this->statusBadge('DIBATALKAN', self::COLOR_DANGER)
            . '</div>'
            . '<div style="background:#fdebe6;border-left:4px solid ' . self::COLOR_DANGER . ';padding:14px 18px;margin:14px 0;font-size:13px">'
            . '<b>Alasan:</b> ' . esc($reason) . '<br>'
            . '<b>Nilai pesanan:</b> Rp ' . number_format($grandTotal, 0, ',', '.')
            . '</div>'
            . '<p style="font-size:13px">Stok yang sebelumnya kami pesankan untuk Anda telah dilepas kembali. '
            . 'Kalau pembatalan ini di luar ekspektasi atau ada pertanyaan, langsung hubungi admin kami via WhatsApp '
            . '<b>' . self::FARM_WHATSAPP . '</b>.</p>'
            . '<table style="width:100%;margin:18px 0"><tr><td align="center">'
            . '<a href="' . esc($katalogUrl) . '" style="background:' . self::COLOR_ACCENT . ';color:#fff;text-decoration:none;padding:12px 28px;border-radius:5px;display:inline-block;font-weight:bold">Belanja Lagi</a>'
            . '</td></tr></table>'
        );
    }

    // ----------------------------------------------------------------
    // Layout primitives
    // ----------------------------------------------------------------

    private function shell(string $heroTitle, string $heroSubtitle, string $bodyHtml): string
    {
        return '<div style="background:#f5f5f0;padding:24px 0;font-family:Arial,Helvetica,sans-serif">'
            . '<div style="max-width:640px;margin:auto;background:#fff;border-radius:8px;overflow:hidden;border:1px solid #e5e9ef">'

            // Header bar
            . '<div style="background:' . self::COLOR_PRIMARY . ';color:#fff;padding:20px 28px">'
            . '<div style="font-size:20px;font-weight:bold;letter-spacing:.5px">' . esc(self::FARM_NAME) . '</div>'
            . '<div style="font-size:11px;color:#d4e6c0;margin-top:2px">Peternakan Ayam Bangkok &amp; DOC Berkualitas</div>'
            . '</div>'

            // Hero
            . '<div style="padding:24px 28px 8px">'
            . '<div style="font-size:18px;font-weight:bold;color:#1a1a1a;margin-bottom:4px">' . esc($heroTitle) . '</div>'
            . '<div style="font-size:13px;color:#666;line-height:1.5">' . esc($heroSubtitle) . '</div>'
            . '</div>'

            // Body
            . '<div style="padding:8px 28px 20px;color:#333;font-size:13px;line-height:1.6">'
            . $bodyHtml
            . '</div>'

            // Footer
            . '<div style="background:#f8f9fa;padding:18px 28px;border-top:1px solid #e5e9ef;font-size:11px;color:#888;text-align:center;line-height:1.6">'
            . '<div style="color:' . self::COLOR_PRIMARY . ';font-weight:bold;margin-bottom:4px">' . esc(self::FARM_NAME) . '</div>'
            . esc(self::FARM_ADDRESS) . ' &middot; ' . esc(self::FARM_EMAIL) . '<br>'
            . 'WhatsApp Admin: <b>' . esc(self::FARM_WHATSAPP) . '</b><br>'
            . '<span style="color:#aaa;margin-top:8px;display:inline-block">Email ini dikirim otomatis. Mohon tidak membalas ke alamat ini.</span><br>'
            . '<span style="color:#aaa">&copy; ' . date('Y') . ' ' . esc(self::FARM_NAME) . '. All rights reserved.</span>'
            . '</div>'

            . '</div></div>';
    }

    private function greeting(string $namaSafe): string
    {
        return '<p style="margin:0 0 14px;font-size:14px">Halo <b>' . $namaSafe . '</b>,</p>';
    }

    private function kvRow(string $label, string $valueHtml): string
    {
        return '<tr>'
            . '<td style="padding:5px 0;color:#666;width:38%;vertical-align:top">' . esc($label) . '</td>'
            . '<td style="padding:5px 0;color:#1a1a1a">' . $valueHtml . '</td>'
            . '</tr>';
    }

    private function statusBadge(string $label, string $color): string
    {
        return '<span style="display:inline-block;background:' . $color . ';color:#fff;font-size:11px;font-weight:bold;letter-spacing:1px;padding:5px 14px;border-radius:20px">'
            . esc($label) . '</span>';
    }

    private function buildItemsTable(array $details, float $grandTotal): string
    {
        $rows = '';
        foreach ($details as $item) {
            $nama  = $item['nama_ayam'] ?? 'Produk';
            $qty   = (int) ($item['jumlah'] ?? 0);
            $harga = (float) ($item['harga_satuan_snapshot'] ?? 0);
            $sub   = isset($item['subtotal']) ? (float) $item['subtotal'] : ($qty * $harga);
            $isPo  = !empty($item['is_preorder_item']);

            $poTag = $isPo
                ? '<span style="display:inline-block;background:#3b82f6;color:#fff;font-size:10px;padding:2px 6px;border-radius:3px;margin-left:6px;letter-spacing:.5px">PRE-ORDER</span>'
                : '';

            $rows .= '<tr>'
                . '<td style="padding:10px 8px;border-bottom:1px solid #eef0f3">' . esc($nama) . $poTag . '</td>'
                . '<td style="padding:10px 8px;border-bottom:1px solid #eef0f3;text-align:center">' . $qty . '</td>'
                . '<td style="padding:10px 8px;border-bottom:1px solid #eef0f3;text-align:right">Rp ' . number_format($harga, 0, ',', '.') . '</td>'
                . '<td style="padding:10px 8px;border-bottom:1px solid #eef0f3;text-align:right">Rp ' . number_format($sub, 0, ',', '.') . '</td>'
                . '</tr>';
        }

        return '<table style="border-collapse:collapse;width:100%;font-size:13px;margin:10px 0">'
            . '<thead><tr style="background:' . self::COLOR_PRIMARY . ';color:#fff">'
            . '<th style="padding:10px 8px;text-align:left">Produk</th>'
            . '<th style="padding:10px 8px;width:50px">Qty</th>'
            . '<th style="padding:10px 8px;text-align:right">Harga</th>'
            . '<th style="padding:10px 8px;text-align:right">Subtotal</th>'
            . '</tr></thead>'
            . '<tbody>' . $rows . '</tbody>'
            . '<tfoot><tr>'
            . '<td colspan="3" style="padding:12px 8px;text-align:right;border-top:2px solid ' . self::COLOR_PRIMARY . ';font-weight:bold">Grand Total</td>'
            . '<td style="padding:12px 8px;text-align:right;border-top:2px solid ' . self::COLOR_PRIMARY . ';font-weight:bold;color:' . self::COLOR_PRIMARY . ';font-size:15px">Rp ' . number_format($grandTotal, 0, ',', '.') . '</td>'
            . '</tr></tfoot>'
            . '</table>';
    }

    private function hasPreorderItems(array $details): bool
    {
        foreach ($details as $item) {
            if (!empty($item['is_preorder_item'])) {
                return true;
            }
        }
        return false;
    }
}
