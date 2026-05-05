<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPerformanceIndexes extends Migration
{
    public function up()
    {
        // pesanan
        $this->createIndexIfNotExists('pesanan', 'idx_pesanan_status_tanggal', '`status_pesanan`, `tanggal_pesanan`');
        $this->createIndexIfNotExists('pesanan', 'idx_pesanan_user_tanggal', '`user_id`, `tanggal_pesanan`');
        $this->createIndexIfNotExists('pesanan', 'idx_pesanan_status_expired', '`status_pesanan`, `expired_at`');
        $this->createIndexIfNotExists('pesanan', 'idx_pesanan_tanggal', '`tanggal_pesanan`');

        // pembayaran
        $this->createIndexIfNotExists('pembayaran', 'idx_pembayaran_status_upload', '`status_pembayaran`, `tanggal_upload`');
        $this->createIndexIfNotExists('pembayaran', 'idx_pembayaran_pesanan_created', '`pesanan_id`, `created_at`');

        // detail_pesanan
        $this->createIndexIfNotExists('detail_pesanan', 'idx_detail_pesanan_pesanan_produk', '`pesanan_id`, `produk_id`');

        // item_keranjang
        $this->createIndexIfNotExists('item_keranjang', 'idx_item_keranjang_cart_product', '`keranjang_id`, `produk_id`');

        // produk
        $this->createIndexIfNotExists('produk', 'idx_produk_active_stock', '`is_active`, `stok_tersedia`');
        $this->createIndexIfNotExists('produk', 'idx_produk_active_kategori', '`is_active`, `kategori_id`');
    }

    public function down()
    {
        $this->dropIndexIfExists('pesanan', 'idx_pesanan_status_tanggal');
        $this->dropIndexIfExists('pesanan', 'idx_pesanan_user_tanggal');
        $this->dropIndexIfExists('pesanan', 'idx_pesanan_status_expired');
        $this->dropIndexIfExists('pesanan', 'idx_pesanan_tanggal');

        $this->dropIndexIfExists('pembayaran', 'idx_pembayaran_status_upload');
        $this->dropIndexIfExists('pembayaran', 'idx_pembayaran_pesanan_created');

        $this->dropIndexIfExists('detail_pesanan', 'idx_detail_pesanan_pesanan_produk');
        $this->dropIndexIfExists('item_keranjang', 'idx_item_keranjang_cart_product');

        $this->dropIndexIfExists('produk', 'idx_produk_active_stock');
        $this->dropIndexIfExists('produk', 'idx_produk_active_kategori');
    }

    private function createIndexIfNotExists(string $table, string $indexName, string $columns): void
    {
        if ($this->indexExists($table, $indexName)) {
            return;
        }

        $this->db->query("CREATE INDEX `{$indexName}` ON `{$table}` ({$columns})");
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (!$this->indexExists($table, $indexName)) {
            return;
        }

        $this->db->query("DROP INDEX `{$indexName}` ON `{$table}`");
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $sql = 'SELECT 1
                FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                  AND table_name = ?
                  AND index_name = ?
                LIMIT 1';

        $row = $this->db->query($sql, [$table, $indexName])->getRowArray();

        return !empty($row);
    }
}

