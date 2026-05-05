<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCheckoutSnapshotToPesanan extends Migration
{
    public function up()
    {
        $columns = [
            'nama_penerima' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'email_penerima' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'no_hp_penerima' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'alamat_pengiriman' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ];

        foreach ($columns as $column => $definition) {
            if ($this->db->fieldExists($column, 'pesanan')) {
                continue;
            }

            $this->forge->addColumn('pesanan', [
                $column => $definition,
            ]);
        }
    }

    public function down()
    {
        $columns = [];

        foreach (['nama_penerima', 'email_penerima', 'no_hp_penerima', 'alamat_pengiriman'] as $column) {
            if ($this->db->fieldExists($column, 'pesanan')) {
                $columns[] = $column;
            }
        }

        if ($columns !== []) {
            $this->forge->dropColumn('pesanan', $columns);
        }
    }
}
