<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class HardenCartItemUniqueness extends Migration
{
    public function up()
    {
        $duplicates = $this->db->query(
            'SELECT keranjang_id, produk_id, MIN(id) AS keeper_id, SUM(jumlah) AS total_jumlah, COUNT(*) AS total_row
             FROM item_keranjang
             GROUP BY keranjang_id, produk_id
             HAVING COUNT(*) > 1'
        )->getResultArray();

        foreach ($duplicates as $duplicate) {
            $keeperId = (int) $duplicate['keeper_id'];
            $keranjangId = (int) $duplicate['keranjang_id'];
            $produkId = (int) $duplicate['produk_id'];

            $this->db->table('item_keranjang')
                ->where('id', $keeperId)
                ->update([
                    'jumlah' => (int) $duplicate['total_jumlah'],
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            $this->db->table('item_keranjang')
                ->where('keranjang_id', $keranjangId)
                ->where('produk_id', $produkId)
                ->where('id !=', $keeperId)
                ->delete();
        }

        $indexes = array_column($this->db->query('SHOW INDEX FROM item_keranjang')->getResultArray(), 'Key_name');
        if (!in_array('uniq_item_keranjang_cart_product', $indexes, true)) {
            $this->db->query('ALTER TABLE item_keranjang ADD UNIQUE KEY uniq_item_keranjang_cart_product (`keranjang_id`, `produk_id`)');
        }
    }

    public function down()
    {
        // Pastikan FK keranjang_id tetap punya cover index sebelum drop UNIQUE.
        $indexes = array_column($this->db->query('SHOW INDEX FROM item_keranjang')->getResultArray(), 'Key_name');

        if (in_array('uniq_item_keranjang_cart_product', $indexes, true)) {
            if (!in_array('idx_item_keranjang_cart', $indexes, true)) {
                $this->db->query('ALTER TABLE item_keranjang ADD INDEX idx_item_keranjang_cart (`keranjang_id`)');
            }
            $this->db->query('ALTER TABLE item_keranjang DROP INDEX uniq_item_keranjang_cart_product');
        }
    }
}
