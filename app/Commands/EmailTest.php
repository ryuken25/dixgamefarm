<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Kirim email uji coba untuk memverifikasi konfigurasi SMTP (Gmail) di .env.
 *
 * Contoh:
 *   php spark email:test tujuan@example.com
 *   php spark email:test            (tanpa argumen -> dikirim ke email.fromEmail)
 */
class EmailTest extends BaseCommand
{
    protected $group       = 'Email';
    protected $name        = 'email:test';
    protected $description = 'Kirim email uji coba untuk memverifikasi konfigurasi SMTP.';
    protected $usage       = 'email:test [recipient]';
    protected $arguments   = [
        'recipient' => 'Alamat email tujuan (opsional; default: email.fromEmail di .env).',
    ];

    public function run(array $params = [])
    {
        $config = config('Email');

        $to = $params[0] ?? $config->fromEmail;

        if (empty($to)) {
            CLI::error('Tidak ada alamat tujuan. Beri argumen atau isi email.fromEmail di .env.');
            return;
        }

        if (empty($config->SMTPHost) || empty($config->SMTPUser)) {
            CLI::error('Konfigurasi SMTP belum lengkap. Cek blok EMAIL (Gmail SMTP) di .env.');
            return;
        }

        CLI::write("Mengirim email uji ke: {$to}", 'yellow');
        CLI::write("Lewat: {$config->SMTPHost}:{$config->SMTPPort} sebagai {$config->SMTPUser}", 'white');

        $email = service('email');
        $email->setTo($to);
        $email->setSubject('Tes Email DIX Game Farm');
        $email->setMessage(
            '<h3>Tes Email Berhasil</h3>'
            . '<p>Jika Anda menerima email ini, konfigurasi Gmail SMTP DIX Game Farm sudah benar.</p>'
            . '<p>Dikirim: ' . date('Y-m-d H:i:s') . '</p>'
        );

        if ($email->send()) {
            CLI::write('Email berhasil dikirim!', 'green');
        } else {
            CLI::error('Gagal mengirim email.');
            CLI::write($email->printDebugger(['headers']), 'red');
        }
    }
}
