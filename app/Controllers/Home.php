<?php

namespace App\Controllers;

use App\Libraries\TelegramService;

class Home extends BaseController
{
    public function index()
    {
        return view('public/home', [
            'title' => 'DIX Game Farm - Peternakan Ayam Berkualitas',
        ]);
    }

    public function about()
    {
        return view('public/about', [
            'title' => 'Tentang Kami - DIX Game Farm',
        ]);
    }

    public function contact()
    {
        return view('public/contact', [
            'title' => 'Kontak - DIX Game Farm',
        ]);
    }

    /**
     * Handle contact form submission — forward to admin via Telegram.
     * Returns JSON for AJAX and logs every attempt.
     */
    public function sendContact()
    {
        if (!$this->request->isAJAX() && strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->to('/kontak');
        }

        $rules = [
            'nama'   => 'required|min_length[2]|max_length[100]',
            'email'  => 'required|valid_email|max_length[150]',
            'no_hp'  => 'permit_empty|max_length[20]',
            'subjek' => 'required|max_length[100]',
            'pesan'  => 'required|min_length[5]|max_length[2000]',
        ];

        if (!$this->validate($rules)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'success' => false,
                    'message' => 'Periksa kembali isian form.',
                    'errors'  => $this->validator->getErrors(),
                ]);
        }

        $payload = [
            'nama'   => trim((string) $this->request->getPost('nama')),
            'email'  => trim((string) $this->request->getPost('email')),
            'no_hp'  => trim((string) $this->request->getPost('no_hp')),
            'subjek' => trim((string) $this->request->getPost('subjek')),
            'pesan'  => trim((string) $this->request->getPost('pesan')),
        ];

        log_message('info', 'Contact form submitted: ' . json_encode([
            'nama'   => $payload['nama'],
            'email'  => $payload['email'],
            'subjek' => $payload['subjek'],
            'ip'     => $this->request->getIPAddress(),
        ]));

        $telegram = new TelegramService();
        $sent = $telegram->notifyContactMessage($payload);

        if (!$sent) {
            log_message('error', 'Contact form: Telegram delivery failed for ' . $payload['email']);
            return $this->response
                ->setStatusCode(502)
                ->setJSON([
                    'success' => false,
                    'message' => 'Pesan gagal dikirim ke admin. Silakan coba lagi atau hubungi kami via WhatsApp.',
                ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Terima kasih! Pesan Anda sudah diteruskan ke admin.',
        ]);
    }
}
