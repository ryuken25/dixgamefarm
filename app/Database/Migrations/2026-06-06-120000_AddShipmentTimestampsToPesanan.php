<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tambah 3 timestamp ke pesanan untuk tracking SLA pengiriman:
 *   diproses_at           - kapan order masuk DIPROSES (mulai countdown 24 jam kurir)
 *   dikirim_at            - kapan admin set DIKIRIM
 *   reminder_terlambat_at - penanda Telegram reminder sudah dikirim (anti-spam)
 */
class AddShipmentTimestampsToPesanan extends Migration
{
    public function up()
    {
        $columns = [];

        if (!$this->db->fieldExists('diproses_at', 'pesanan')) {
            $columns['diproses_at'] = [
                'type'    => 'DATETIME',
                'null'    => true,
                'after'   => 'kode_resi',
                'comment' => 'Saat status pindah ke DIPROSES (mulai countdown SLA 24 jam kurir)',
            ];
        }

        if (!$this->db->fieldExists('dikirim_at', 'pesanan')) {
            $columns['dikirim_at'] = [
                'type'    => 'DATETIME',
                'null'    => true,
                'after'   => 'diproses_at',
                'comment' => 'Saat status pindah ke DIKIRIM (kurir berangkat)',
            ];
        }

        if (!$this->db->fieldExists('reminder_terlambat_at', 'pesanan')) {
            $columns['reminder_terlambat_at'] = [
                'type'    => 'DATETIME',
                'null'    => true,
                'after'   => 'dikirim_at',
                'comment' => 'Tanda reminder Telegram untuk pesanan terlambat sudah dikirim',
            ];
        }

        if ($columns !== []) {
            $this->forge->addColumn('pesanan', $columns);
        }

        // Backfill: untuk order yang sudah DIPROSES, set diproses_at = updated_at
        // supaya logic countdown SLA tetap akurat untuk data lama.
        if ($this->db->fieldExists('diproses_at', 'pesanan')) {
            $this->db->query(
                "UPDATE pesanan SET diproses_at = updated_at "
                . "WHERE status_pesanan = 'DIPROSES' AND diproses_at IS NULL"
            );
            $this->db->query(
                "UPDATE pesanan SET dikirim_at = updated_at "
                . "WHERE status_pesanan IN ('DIKIRIM', 'SELESAI') AND dikirim_at IS NULL"
            );
        }
    }

    public function down()
    {
        foreach (['reminder_terlambat_at', 'dikirim_at', 'diproses_at'] as $col) {
            if ($this->db->fieldExists($col, 'pesanan')) {
                $this->forge->dropColumn('pesanan', $col);
            }
        }
    }
}
