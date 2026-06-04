<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class HardenKeranjangUniqueness extends Migration
{
    public function up()
    {
        $duplicates = $this->db->query(
            'SELECT user_id, MIN(id) AS keeper_id, COUNT(*) AS total_row
             FROM keranjang
             GROUP BY user_id
             HAVING COUNT(*) > 1'
        )->getResultArray();

        foreach ($duplicates as $duplicate) {
            $userId = (int) $duplicate['user_id'];
            $keeperId = (int) $duplicate['keeper_id'];

            $otherCarts = $this->db->table('keranjang')
                ->select('id')
                ->where('user_id', $userId)
                ->where('id !=', $keeperId)
                ->get()
                ->getResultArray();

            foreach ($otherCarts as $cart) {
                $cartId = (int) $cart['id'];
                $this->db->query(
                    'INSERT INTO item_keranjang (keranjang_id, produk_id, jumlah, created_at, updated_at)
                     SELECT ?, produk_id, jumlah, created_at, updated_at
                     FROM item_keranjang
                     WHERE keranjang_id = ?
                     ON DUPLICATE KEY UPDATE jumlah = item_keranjang.jumlah + VALUES(jumlah), updated_at = VALUES(updated_at)',
                    [$keeperId, $cartId]
                );

                $this->db->table('item_keranjang')->where('keranjang_id', $cartId)->delete();
                $this->db->table('keranjang')->where('id', $cartId)->delete();
            }
        }

        $indexes = array_column($this->db->query('SHOW INDEX FROM keranjang')->getResultArray(), 'Key_name');
        if (!in_array('uniq_keranjang_user', $indexes, true)) {
            $this->db->query('ALTER TABLE keranjang ADD UNIQUE KEY uniq_keranjang_user (`user_id`)');
        }
    }

    public function down()
    {
        // MySQL menolak drop index 'uniq_keranjang_user' selama masih ada FK
        // di kolom user_id (FK butuh index). Strategi: tambahkan dulu index
        // non-unique sebagai pengganti, baru drop yang unique. FK tetap aman
        // karena selalu ada index yang menutupi kolomnya.
        $indexes = array_column($this->db->query('SHOW INDEX FROM keranjang')->getResultArray(), 'Key_name');

        if (in_array('uniq_keranjang_user', $indexes, true)) {
            if (!in_array('idx_keranjang_user', $indexes, true)) {
                $this->db->query('ALTER TABLE keranjang ADD INDEX idx_keranjang_user (`user_id`)');
            }
            $this->db->query('ALTER TABLE keranjang DROP INDEX uniq_keranjang_user');
        }
    }
}
