<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdatePaymentWindowComment extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('pesanan', [
            'expired_at' => [
                'name'    => 'expired_at',
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => 'Batas bayar 3 hari dari tanggal_pesanan (auto-cancel oleh cron orders:cleanup-expired)',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('pesanan', [
            'expired_at' => [
                'name'    => 'expired_at',
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => 'Batas bayar 24 jam dari tanggal_pesanan',
            ],
        ]);
    }
}
