<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPreorderToProduk extends Migration
{
    public function up()
    {
        $this->forge->addColumn('produk', [
            'is_preorder' => [
                'type'    => 'BOOLEAN',
                'default' => false,
                'comment' => 'Jika true, produk bisa dipesan meskipun stok = 0 (DOC / Day Old Chick)',
                'after'   => 'is_active',
            ],
            'estimasi_pre_order' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'comment'    => 'Estimasi ready (misal: "2-3 minggu", "H+7 setelah konfirmasi")',
                'after'      => 'is_preorder',
            ],
        ]);

        // Add a flag on detail_pesanan so each order line remembers whether it was pre-order.
        $this->forge->addColumn('detail_pesanan', [
            'is_preorder_item' => [
                'type'    => 'BOOLEAN',
                'default' => false,
                'comment' => 'Snapshot: apakah item ini dipesan sebagai pre-order',
                'after'   => 'subtotal',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('produk', ['is_preorder', 'estimasi_pre_order']);
        $this->forge->dropColumn('detail_pesanan', 'is_preorder_item');
    }
}
