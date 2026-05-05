<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBankPilihanToPesanan extends Migration
{
    public function up()
    {
        $this->forge->addColumn('pesanan', [
            'metode_pembayaran' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'comment'    => 'Bank/ewallet pilihan pelanggan saat checkout (BRI, BNI, BCA, ShopeePay, Dana, GoPay)',
                'after'      => 'tipe_pengiriman',
            ],
            'nomor_rekening_tujuan' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'comment'    => 'Nomor rekening / akun tujuan transfer',
                'after'      => 'metode_pembayaran',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('pesanan', ['metode_pembayaran', 'nomor_rekening_tujuan']);
    }
}
