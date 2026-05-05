<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPesananSiapAndResiToPesanan extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('kode_resi', 'pesanan')) {
            $this->forge->addColumn('pesanan', [
                'kode_resi' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => true,
                    'after' => 'status_pesanan',
                    'comment' => 'Kode tracking pengiriman kurir',
                ],
            ]);
        }

        // Add PESANAN_SIAP status for pickup flow
        $this->db->query(
            "ALTER TABLE pesanan MODIFY status_pesanan ENUM('MENUNGGU_BAYAR','MENUNGGU_VERIFIKASI','DIPROSES','PESANAN_SIAP','DIKIRIM','SELESAI','BATAL') NOT NULL DEFAULT 'MENUNGGU_BAYAR'"
        );
    }

    public function down()
    {
        // Revert status enum
        $this->db->query(
            "ALTER TABLE pesanan MODIFY status_pesanan ENUM('MENUNGGU_BAYAR','MENUNGGU_VERIFIKASI','DIPROSES','DIKIRIM','SELESAI','BATAL') NOT NULL DEFAULT 'MENUNGGU_BAYAR'"
        );

        if ($this->db->fieldExists('kode_resi', 'pesanan')) {
            $this->forge->dropColumn('pesanan', 'kode_resi');
        }
    }
}

