<?php

namespace App\Commands;

use App\Libraries\TelegramService;
use App\Models\PesananModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Kirim reminder Telegram ke admin untuk pesanan kurir yang sudah >24 jam
 * di status DIPROSES tapi belum dikirim. Idempotent via
 * pesanan.reminder_terlambat_at — order yang sudah pernah di-reminder
 * tidak akan dikirim ulang.
 *
 * Jadwalkan via Task Scheduler / cron tiap 60 menit:
 *   php spark orders:remind-unshipped
 *
 * Aman dipanggil walau Telegram belum dikonfigurasi (TelegramService::isEnabled()
 * false -> skip rapi, exit 0, tidak men-stamp reminder_terlambat_at).
 */
class RemindUnshippedOrders extends BaseCommand
{
    protected $group       = 'Maintenance';
    protected $name        = 'orders:remind-unshipped';
    protected $description = 'Kirim reminder Telegram untuk pesanan kurir yang >24 jam belum dikirim';

    public function run(array $params = [])
    {
        CLI::write('Cek pesanan kurir terlambat (DIPROSES >24 jam)...', 'yellow');

        $pesananModel = new PesananModel();
        $orders = $pesananModel->getOrdersNeedingShipmentReminder();

        if (empty($orders)) {
            CLI::write('Tidak ada pesanan terlambat.', 'green');
            return;
        }

        $telegram = new TelegramService();
        if (!$telegram->isEnabled()) {
            CLI::write('[SKIP] Telegram belum dikonfigurasi (TELEGRAM_BOT_TOKEN / CHAT_ID kosong).', 'yellow');
            CLI::write('       ' . count($orders) . ' pesanan terlambat ditemukan tapi reminder tidak dikirim.', 'yellow');
            return;
        }

        CLI::write('Menemukan ' . count($orders) . ' pesanan terlambat. Kirim reminder...', 'white');

        $sent   = 0;
        $failed = 0;
        foreach ($orders as $order) {
            $invoice = $order['nomor_invoice'] ?? '-';
            CLI::write("  -> {$invoice}: kirim Telegram...", 'white');

            $msg = $this->buildReminderMessage($order);

            $ok = false;
            try {
                $ok = $telegram->sendMessage($msg, 'HTML');
            } catch (\Throwable $e) {
                log_message('error', 'RemindUnshippedOrders Telegram error: ' . $e->getMessage());
            }

            if ($ok) {
                $pesananModel->markShipmentReminderSent((int) $order['id']);
                CLI::write("     [OK] Reminder terkirim & ter-stamp.", 'green');
                $sent++;
            } else {
                CLI::write("     [FAIL] Kirim Telegram gagal. Akan dicoba ulang siklus berikutnya.", 'red');
                $failed++;
            }
        }

        CLI::write('Selesai.', 'yellow');
        CLI::write("Terkirim: {$sent}, Gagal: {$failed}", 'white');
    }

    /**
     * Format pesan Telegram HTML — pendek, scannable di mobile.
     */
    private function buildReminderMessage(array $order): string
    {
        $invoice   = $order['nomor_invoice'] ?? '-';
        $nama      = $order['nama_lengkap'] ?? 'Pelanggan';
        $total     = 'Rp ' . number_format((float) ($order['grand_total'] ?? 0), 0, ',', '.');
        $diprosesAt = !empty($order['diproses_at'])
            ? date('d M Y, H:i', strtotime($order['diproses_at'])) . ' WITA'
            : '-';

        return "\u{23F0} <b>Reminder: Pesanan Belum Dikirim</b>\n\n"
            . "Pesanan ini sudah lebih dari 24 jam di status DIPROSES tapi belum dikirim.\n\n"
            . "\u{1F9FE} Invoice: <b>" . htmlspecialchars($invoice, ENT_QUOTES) . "</b>\n"
            . "\u{1F464} Pembeli: " . htmlspecialchars($nama, ENT_QUOTES) . "\n"
            . "\u{1F4B0} Total: {$total}\n"
            . "\u{1F4E6} Pengiriman: Kurir\n"
            . "\u{1F552} Diproses sejak: {$diprosesAt}\n\n"
            . "Segera proses pengiriman & input kode resi di panel admin.";
    }
}
