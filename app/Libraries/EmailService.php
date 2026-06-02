<?php

namespace App\Libraries;

use App\Models\PesananModel;
use Config\Services;

/**
 * Mengirim email transaksional ke pelanggan (invoice, status pesanan).
 *
 * Konfigurasi SMTP dibaca dari Config\Email (yang mengambil nilai dari .env).
 * Semua pengiriman bersifat best-effort: kegagalan dicatat ke log dan tidak
 * boleh menggagalkan alur utama (verifikasi pembayaran).
 */
class EmailService
{
    /**
     * Kirim invoice ke pelanggan setelah pembayaran diverifikasi valid.
     *
     * @return bool true jika email terkirim
     */
    public function sendInvoicePaid(int $pesananId): bool
    {
        $pesananModel = new PesananModel();
        $data = $pesananModel->getOrderWithDetails($pesananId);

        if (!$data || empty($data['order'])) {
            log_message('error', 'EmailService::sendInvoicePaid - pesanan tidak ditemukan. id=' . $pesananId);
            return false;
        }

        $order   = $data['order'];
        $details = $data['details'] ?? [];

        $to = $order['email'] ?? null;
        if (empty($to)) {
            log_message('info', 'EmailService::sendInvoicePaid - pesanan tanpa email penerima, dilewati. id=' . $pesananId);
            return false;
        }

        $config = config('Email');
        if (empty($config->fromEmail)) {
            log_message('info', 'EmailService::sendInvoicePaid - email.fromEmail belum dikonfigurasi di .env, dilewati.');
            return false;
        }

        $subject = 'Invoice ' . ($order['nomor_invoice'] ?? '-') . ' - Pembayaran Diverifikasi';
        $body    = $this->buildInvoiceHtml($order, $details);

        $email = Services::email();
        $email->setTo($to);
        $email->setSubject($subject);
        $email->setMessage($body);

        if ($email->send(false)) {
            log_message('info', 'EmailService::sendInvoicePaid - terkirim ke ' . $to . ' untuk invoice ' . ($order['nomor_invoice'] ?? '-'));
            return true;
        }

        log_message('error', 'EmailService::sendInvoicePaid - gagal kirim ke ' . $to . '. Debug: ' . $email->printDebugger(['headers']));
        return false;
    }

    /**
     * Susun isi email invoice dalam format HTML.
     */
    private function buildInvoiceHtml(array $order, array $details): string
    {
        $rows  = '';
        $total = 0;

        foreach ($details as $item) {
            $nama  = $item['nama_ayam'] ?? 'Produk';
            $qty   = (int) ($item['jumlah'] ?? 0);
            $harga = (float) ($item['harga_satuan_snapshot'] ?? 0);
            $sub   = isset($item['subtotal']) ? (float) $item['subtotal'] : ($qty * $harga);
            $total += $sub;

            $rows .= '<tr>'
                . '<td style="padding:8px;border:1px solid #ddd">' . esc($nama) . '</td>'
                . '<td style="padding:8px;border:1px solid #ddd;text-align:center">' . $qty . '</td>'
                . '<td style="padding:8px;border:1px solid #ddd;text-align:right">Rp ' . number_format($harga, 0, ',', '.') . '</td>'
                . '<td style="padding:8px;border:1px solid #ddd;text-align:right">Rp ' . number_format($sub, 0, ',', '.') . '</td>'
                . '</tr>';
        }

        $grandTotal = isset($order['grand_total']) ? (float) $order['grand_total'] : $total;
        $nama       = esc($order['nama_lengkap'] ?? 'Pelanggan');
        $invoice    = esc($order['nomor_invoice'] ?? '-');
        $tanggal    = !empty($order['tanggal_pesanan'])
            ? date('d-m-Y H:i', strtotime($order['tanggal_pesanan']))
            : date('d-m-Y H:i');

        return '<div style="font-family:Arial,sans-serif;max-width:640px;margin:auto;color:#333">'
            . '<h2 style="color:#2c7a2c;margin-bottom:4px">DIX Game Farm</h2>'
            . '<p style="margin-top:0;color:#666">Pembayaran Anda telah diverifikasi. Berikut rincian pesanan Anda.</p>'
            . '<p>Halo <b>' . $nama . '</b>,</p>'
            . '<table style="width:100%;margin-bottom:12px"><tr>'
            . '<td>Invoice: <b>' . $invoice . '</b></td>'
            . '<td style="text-align:right">Tanggal: ' . $tanggal . '</td>'
            . '</tr></table>'
            . '<table style="border-collapse:collapse;width:100%">'
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
            . '</table>'
            . '<p style="margin-top:16px">Status pesanan Anda kini <b>sedang diproses</b>. Terima kasih telah berbelanja di DIX Game Farm.</p>'
            . '<hr style="border:none;border-top:1px solid #eee;margin:20px 0">'
            . '<p style="color:#999;font-size:12px">Email ini dikirim otomatis. Mohon tidak membalas email ini.</p>'
            . '</div>';
    }
}
