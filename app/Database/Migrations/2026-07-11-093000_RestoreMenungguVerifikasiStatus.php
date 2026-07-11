<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RestoreMenungguVerifikasiStatus extends Migration
{
    public function up()
    {
        // AlignOrderStatusFlowAndCheckoutSchema (2026-04-18-101000) ran after
        // AddPesananSiapAndResiToPesanan (2026-04-18-100200) in the same batch and
        // dropped MENUNGGU_VERIFIKASI from the enum again. Since the DB connection
        // uses strictOn => false, writes of 'MENUNGGU_VERIFIKASI' were silently
        // coerced to '' instead of erroring, corrupting order status whenever a
        // customer uploaded payment proof. Restore the value here.
        $this->db->query(
            "ALTER TABLE pesanan MODIFY status_pesanan ENUM('MENUNGGU_BAYAR','MENUNGGU_VERIFIKASI','DIPROSES','PESANAN_SIAP','DIKIRIM','SELESAI','BATAL') NOT NULL DEFAULT 'MENUNGGU_BAYAR'"
        );
    }

    public function down()
    {
        $this->db->query(
            "ALTER TABLE pesanan MODIFY status_pesanan ENUM('MENUNGGU_BAYAR','DIPROSES','PESANAN_SIAP','DIKIRIM','SELESAI','BATAL') NOT NULL DEFAULT 'MENUNGGU_BAYAR'"
        );
    }
}
