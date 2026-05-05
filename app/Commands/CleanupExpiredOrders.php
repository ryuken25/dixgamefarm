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
        $failed = 0;

        foreach ($expiredOrders as $order) {
            CLI::write("Processing order: {$order['nomor_invoice']}...", 'white');

            if ($pesananModel->cancelOrder($order['id'], 'Expired - payment not completed within 24 hours')) {
                CLI::write("  -> Order {$order['nomor_invoice']} cancelled successfully", 'green');
                $cancelled++;
            } else {
                CLI::write("  -> Failed to cancel order {$order['nomor_invoice']}", 'red');
                $failed++;
            }
        }

        CLI::write('Cleanup completed!', 'yellow');
        CLI::write("Cancelled: {$cancelled}, Failed: {$failed}", 'white');
    }
}
