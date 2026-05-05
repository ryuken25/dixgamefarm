<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePesananTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'nomor_invoice' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'unique'     => true,
                'comment'    => 'Format: INV-YYYYMMDD-RandomNumber',
            ],
            'tanggal_pesanan' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'expired_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => 'Batas bayar 24 jam dari tanggal_pesanan',
            ],
            'grand_total' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0.00,
            ],
            'status_pesanan' => [
                'type'       => 'ENUM',
                'constraint' => ['MENUNGGU_BAYAR', 'MENUNGGU_VERIFIKASI', 'DIPROSES', 'DIKIRIM', 'SELESAI', 'BATAL'],
                'default'    => 'MENUNGGU_BAYAR',
            ],
            'tipe_pengiriman' => [
                'type'       => 'ENUM',
                'constraint' => ['AMBIL_SENDIRI', 'DIKIRIM_KURIR'],
                'default'    => 'AMBIL_SENDIRI',
            ],
            'catatan_pelanggan' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('pesanan');
    }

    public function down()
    {
        $this->forge->dropTable('pesanan');
    }
}
