<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\PesananModel;

class CleanupExpiredOrders extends BaseCommand
{
    protected $group       = 'Maintenance';
    protected $name        = 'orders:cleanup-expired';
    protected $description = 'Cancel expired orders and release reserved stock';

    public function run(array $params = [])
    {
        CLI::write('Starting expired orders cleanup...', 'yellow');

        $pesananModel = new PesananModel();
        $expiredOrders = $pesananModel->getExpiredOrders();

        if (empty($expiredOrders)) {
            CLI::write('No expired orders found.', 'green');
            return;
        }

        CLI::write('Found ' . count($expiredOrders) . ' expired order(s)', 'yellow');

        $cancelled = 0;
        $skipped = 0;
        $paymentWindowDays = (int) (PesananModel::PAYMENT_WINDOW_HOURS / 24);

        foreach ($expiredOrders as $order) {
            CLI::write("Processing order: {$order['nomor_invoice']}...", 'white');

            if ($pesananModel->cancelExpiredOrder((int) $order['id'], "Expired - payment not completed within {$paymentWindowDays} days")) {
                CLI::write("  -> Order {$order['nomor_invoice']} cancelled successfully", 'green');
                $cancelled++;
            } else {
                // Not necessarily an error: strict re-check inside cancelExpiredOrder()
                // skips orders that stopped being eligible between the candidate query
                // and this transaction (e.g. customer just uploaded payment proof).
                CLI::write("  -> Skipped {$order['nomor_invoice']} (no longer eligible or cancel failed)", 'yellow');
                $skipped++;
            }
        }

        CLI::write('Cleanup completed!', 'yellow');
        CLI::write("Cancelled: {$cancelled}, Skipped: {$skipped}", 'white');
    }
}
