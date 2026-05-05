<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePembayaranTable extends Migration
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
            'pesanan_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'nominal_bayar' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
            ],
            'nama_bank' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
                'comment'    => 'Bank pengirim (misal: BCA, BNI, Mandiri)',
            ],
            'bank_tujuan' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
                'comment'    => 'Bank penerima DIX Game Farm',
            ],
            'bukti_bayar' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'comment'    => 'Path file bukti transfer',
            ],
            'status_pembayaran' => [
                'type'       => 'ENUM',
                'constraint' => ['PENDING', 'VALID', 'INVALID'],
                'default'    => 'PENDING',
            ],
            'tanggal_upload' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'tanggal_verifikasi' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'verifikator_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'ID Admin yang memverifikasi',
            ],
            'alasan_ditolak' => [
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
        $this->forge->addForeignKey('pesanan_id', 'pesanan', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('verifikator_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('pembayaran');
    }

    public function down()
    {
        $this->forge->dropTable('pembayaran');
    }
}
