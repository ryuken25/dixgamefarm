<?php

namespace App\Commands;

use App\Libraries\TelegramAdminService;
use App\Libraries\TelegramService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class TelegramPollAdmin extends BaseCommand
{
    protected $group       = 'Maintenance';
    protected $name        = 'telegram:poll-admin';
    protected $description = 'Memproses command dan callback Telegram admin.';
    protected $usage       = 'telegram:poll-admin [loop]';
    protected $arguments   = [
        'loop' => 'Jalankan terus-menerus dengan long polling (30 dtk/siklus). Ctrl+C untuk berhenti.',
    ];

    public function run(array $params = [])
    {
        $telegram = new TelegramService();
        if (!$telegram->isEnabled()) {
            CLI::write('Telegram admin tidak aktif. Periksa konfigurasi bot di .env.', 'yellow');
            return;
        }

        $service  = new TelegramAdminService();
        $loopMode = !empty($params[0]) && strtolower($params[0]) === 'loop';

        if (!$loopMode) {
            // One-shot mode — untuk Task Scheduler atau manual check
            $count = $this->processOnce($service, 5);
            CLI::write($count > 0
                ? "Update Telegram admin berhasil diproses: {$count}"
                : 'Tidak ada update Telegram baru.',
                'green'
            );
            return;
        }

        // ── Continuous long-polling loop ──────────────────────────────────────
        CLI::write('╔══════════════════════════════════════════╗', 'cyan');
        CLI::write('║  Bot Telegram Admin — MODE LOOP AKTIF   ║', 'cyan');
        CLI::write('║  Kirim /help dari Telegram untuk test   ║', 'cyan');
        CLI::write('║  Ctrl+C untuk berhenti                  ║', 'cyan');
        CLI::write('╚══════════════════════════════════════════╝', 'cyan');
        CLI::newLine();

        // Register command list so "/" menu in Telegram shows available commands
        $service->setupBotCommands();
        CLI::write('[' . date('H:i:s') . '] Bot commands terdaftar di Telegram.', 'green');

        while (true) {
            try {
                $count = $this->processOnce($service, 30);
                if ($count > 0) {
                    CLI::write('[' . date('H:i:s') . '] ' . $count . ' update diproses', 'green');
                }
            } catch (\Throwable $e) {
                CLI::write('[' . date('H:i:s') . '] Error: ' . $e->getMessage(), 'red');
                sleep(5); // jeda singkat sebelum retry agar tidak spam jika error terus-menerus
            }
        }
    }

    private function processOnce(TelegramAdminService $service, int $timeout): int
    {
        $offset  = $this->readOffset();
        $updates = $service->fetchUpdates($offset, $timeout);

        if ($updates === []) {
            sleep(1);
            return 0;
        }

        $maxUpdateId = $offset;
        foreach ($updates as $update) {
            $service->handleUpdate($update);
            $maxUpdateId = max($maxUpdateId, (int) ($update['update_id'] ?? $offset));
        }

        $this->writeOffset($maxUpdateId + 1);
        return count($updates);
    }

    private function readOffset(): int
    {
        $path = WRITEPATH . 'telegram_admin_offset.txt';
        if (!is_file($path)) {
            return 0;
        }
        $value = trim((string) file_get_contents($path));
        return ctype_digit($value) ? (int) $value : 0;
    }

    private function writeOffset(int $offset): void
    {
        file_put_contents(WRITEPATH . 'telegram_admin_offset.txt', (string) max(0, $offset));
    }
}
