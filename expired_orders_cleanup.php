<?php

/**
 * Background Job Script for Auto-Cancelling Expired Orders
 *
 * This script should be run via:
 * 1. Windows Task Scheduler (every 10-15 minutes)
 * 2. MySQL Event Scheduler (recommended)
 * 3. Manual execution for testing
 *
 * Usage: php expired_orders_cleanup.php
 */

// Set timezone
date_default_timezone_set('Asia/Makassar');

// Define FCPATH (required by CI4 bootstrap)
define('FCPATH', __DIR__ . '/public' . DIRECTORY_SEPARATOR);

// Include CodeIgniter bootstrap
require_once __DIR__ . '/app/Config/Paths.php';

$paths = new Config\Paths();
require_once $paths->systemDirectory . '/bootstrap.php';

// Initialize CodeIgniter
$app = Config\Services::codeigniter();
$app->initialize();

// Models
$pesananModel = new \App\Models\PesananModel();
$produkModel = new \App\Models\ProdukModel();
$detailModel = new \App\Models\DetailPesananModel();
$notifModel = new \App\Models\NotifikasiModel();

echo "[" . date('Y-m-d H:i:s') . "] Starting expired orders cleanup...\n";

try {
    // Get expired orders that are still waiting for payment
    $expiredOrders = $pesananModel->getExpiredOrders();

    if (empty($expiredOrders)) {
        echo "No expired orders found.\n";
        exit(0);
    }

    echo "Found " . count($expiredOrders) . " expired orders.\n";

    $db = \Config\Database::connect();

    foreach ($expiredOrders as $order) {
        echo "Processing order: {$order['nomor_invoice']} (ID: {$order['id']})\n";

        $db->transBegin();

        try {
            // Get order details to release stock
            $details = $detailModel->where('pesanan_id', $order['id'])->findAll();

            // Release stock back to available (uses FOR UPDATE row locking)
            foreach ($details as $detail) {
                $success = $produkModel->releaseStock($detail['produk_id'], $detail['jumlah']);

                if ($success) {
                    echo "  - Released {$detail['jumlah']} units of product ID {$detail['produk_id']}\n";
                } else {
                    throw new Exception("Failed to release stock for product ID {$detail['produk_id']}");
                }
            }

            // Update order status to BATAL
            $pesananModel->update($order['id'], [
                'status_pesanan' => 'BATAL'
            ]);

            // Send notification to customer
            $notifModel->createNotification(
                $order['user_id'],
                'Pesanan Dibatalkan Otomatis',
                "Pesanan {$order['nomor_invoice']} telah dibatalkan otomatis karena tidak ada pembayaran dalam 24 jam. Silakan buat pesanan baru jika masih berminat."
            );

            $db->transCommit();
            echo "  Order {$order['nomor_invoice']} cancelled successfully\n";

        } catch (Exception $e) {
            $db->transRollback();
            echo "  Error processing order {$order['nomor_invoice']}: " . $e->getMessage() . "\n";
        }
    }

    echo "Cleanup completed successfully.\n";

} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}