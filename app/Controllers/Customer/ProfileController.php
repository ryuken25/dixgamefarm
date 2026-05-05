<?php

namespace App\Controllers\Customer;

use App\Controllers\BaseController;
use App\Models\UserModel;

class ProfileController extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        helper(['form', 'url']);
    }

    /**
     * Show profile page
     */
    public function index()
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'PELANGGAN') {
            return redirect()->to(base_url('auth/login'))->with('error', 'Anda harus login sebagai pelanggan');
        }

        $user = $this->userModel->find(session()->get('id'));

        if (!$user) {
            session()->destroy();
            return redirect()->to(base_url('auth/login'))->with('error', 'Sesi tidak valid');
        }

        // Normalize optional profile fields to prevent undefined array-key notices in views
        $user['no_hp'] = $user['no_hp'] ?? '';
        $user['alamat_lengkap'] = $user['alamat_lengkap'] ?? '';

        return view('customer/profile', [
            'title' => 'Edit Profil - DIX Game Farm',
            'user' => $user,
        ]);
    }

    /**
     * Update profile (nama, no_hp, alamat)
     */
    public function update()
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'PELANGGAN') {
            return redirect()->to(base_url('auth/login'))->with('error', 'Akses ditolak');
        }

        $rules = [
            'nama_lengkap' => 'required|min_length[3]',
            'no_hp' => 'required|min_length[10]|max_length[20]',
            'alamat_lengkap' => 'required|min_length[10]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $userId = session()->get('id');

        $data = [
            'nama_lengkap' => $this->request->getPost('nama_lengkap'),
            'no_hp' => $this->request->getPost('no_hp'),
            'alamat_lengkap' => $this->request->getPost('alamat_lengkap'),
        ];

        if ($this->userModel->update($userId, $data)) {
            session()->set('nama_lengkap', $data['nama_lengkap']);
            return redirect()->to(base_url('customer/profile'))
                ->with('success', 'Profil berhasil diperbarui');
        }

        return redirect()->back()->withInput()->with('error', 'Gagal memperbarui profil');
    }

    /**
     * Update password. Requires old password verification.
     */
    public function updatePassword()
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'PELANGGAN') {
            return redirect()->to(base_url('auth/login'))->with('error', 'Akses ditolak');
        }

        $rules = [
            'password_lama' => 'required',
            'password_baru' => 'required|min_length[8]',
            'password_confirm' => 'required|matches[password_baru]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->with('errors', $this->validator->getErrors())
                ->with('password_form_open', true);
        }

        $userId = session()->get('id');
        $user = $this->userModel->find($userId);

        if (!$user || !password_verify($this->request->getPost('password_lama'), $user['password_hash'])) {
            return redirect()->back()
                ->with('error', 'Password lama tidak sesuai')
                ->with('password_form_open', true);
        }

        // UserModel beforeUpdate callback hashes `password_hash` automatically.
        if (
            $this->userModel->update($userId, [
                'password_hash' => $this->request->getPost('password_baru'),
            ])
        ) {
            return redirect()->to(base_url('customer/profile'))
                ->with('success', 'Password berhasil diganti');
        }

        return redirect()->back()
            ->with('error', 'Gagal mengganti password')
            ->with('password_form_open', true);
    }
}
