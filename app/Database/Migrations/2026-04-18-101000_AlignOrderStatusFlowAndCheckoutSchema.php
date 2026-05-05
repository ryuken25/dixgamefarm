<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlignOrderStatusFlowAndCheckoutSchema extends Migration
{
    public function up()
    {
        $this->ensureColumnExists('pesanan', 'metode_pembayaran', [
            'type' => 'VARCHAR',
            'constraint' => 50,
            'null' => true,
            'comment' => 'Bank/ewallet pilihan pelanggan saat checkout',
        ]);

        $this->ensureColumnExists('pesanan', 'nomor_rekening_tujuan', [
            'type' => 'VARCHAR',
            'constraint' => 100,
            'null' => true,
            'comment' => 'Nomor rekening / akun tujuan transfer',
        ]);

        $this->ensureColumnExists('pesanan', 'nama_penerima', [
            'type' => 'VARCHAR',
            'constraint' => 100,
            'null' => true,
        ]);

        $this->ensureColumnExists('pesanan', 'email_penerima', [
            'type' => 'VARCHAR',
            'constraint' => 100,
            'null' => true,
        ]);

        $this->ensureColumnExists('pesanan', 'no_hp_penerima', [
            'type' => 'VARCHAR',
            'constraint' => 20,
            'null' => true,
        ]);

        $this->ensureColumnExists('pesanan', 'alamat_pengiriman', [
            'type' => 'TEXT',
            'null' => true,
        ]);

        $this->db->query(
            "UPDATE pesanan o
            LEFT JOIN (
                SELECT pesanan_id, MAX(CASE WHEN status_pembayaran = 'VALID' THEN 1 ELSE 0 END) AS has_valid_payment
                FROM pembayaran
                GROUP BY pesanan_id
            ) p ON p.pesanan_id = o.id
            SET o.status_pesanan = CASE
                WHEN COALESCE(p.has_valid_payment, 0) = 1 THEN 'DIPROSES'
                ELSE 'MENUNGGU_BAYAR'
            END
            WHERE o.status_pesanan = 'MENUNGGU_VERIFIKASI'"
        );

        $this->db->query(
            "UPDATE pembayaran p
            INNER JOIN pesanan o ON o.id = p.pesanan_id
            SET p.status_pembayaran = 'VALID',
                p.tanggal_verifikasi = COALESCE(p.tanggal_verifikasi, NOW()),
                p.alasan_ditolak = NULL
            WHERE o.status_pesanan IN ('DIPROSES', 'PESANAN_SIAP', 'DIKIRIM', 'SELESAI')
              AND p.status_pembayaran = 'PENDING'"
        );

        $this->db->query(
            "ALTER TABLE pesanan MODIFY status_pesanan ENUM('MENUNGGU_BAYAR','DIPROSES','PESANAN_SIAP','DIKIRIM','SELESAI','BATAL') NOT NULL DEFAULT 'MENUNGGU_BAYAR'"
        );
    }

    public function down()
    {
        $this->db->query(
            "ALTER TABLE pesanan MODIFY status_pesanan ENUM('MENUNGGU_BAYAR','MENUNGGU_VERIFIKASI','DIPROSES','PESANAN_SIAP','DIKIRIM','SELESAI','BATAL') NOT NULL DEFAULT 'MENUNGGU_BAYAR'"
        );
    }

    private function ensureColumnExists(string $table, string $column, array $definition): void
    {
        if ($this->db->fieldExists($column, $table)) {
            return;
        }

        $this->forge->addColumn($table, [
            $column => $definition,
        ]);
    }
}
