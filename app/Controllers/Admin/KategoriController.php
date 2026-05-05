<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\KategoriModel;

class KategoriController extends BaseController
{
    protected $kategoriModel;

    public function __construct()
    {
        $this->kategoriModel = new KategoriModel();
        helper(['form', 'url']);
    }

    /**
     * Category list
     */
    public function index()
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'ADMIN') {
            return redirect()->to(base_url('auth/login'))->with('error', 'Akses ditolak');
        }

        $categories = $this->kategoriModel->getKategoriWithCount();

        $data = [
            'title' => 'Manajemen Kategori - DIX Game Farm',
            'categories' => $categories
        ];

        return view('admin/kategori/index', $data);
    }

    /**
     * Create category
     */
    public function create()
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'ADMIN') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Unauthorized'
            ])->setStatusCode(401);
        }

        $rules = [
            'nama_kategori' => 'required|min_length[3]|max_length[100]',
            'deskripsi' => 'permit_empty|max_length[500]'
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $this->validator->getErrors()
            ])->setStatusCode(400);
        }

        $data = [
            'nama_kategori' => $this->request->getPost('nama_kategori'),
            'deskripsi' => $this->request->getPost('deskripsi')
        ];

        if ($this->kategoriModel->insert($data)) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Kategori berhasil ditambahkan'
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Gagal menambahkan kategori'
        ])->setStatusCode(500);
    }

    /**
     * Update category
     */
    public function update($id)
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'ADMIN') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Unauthorized'
            ])->setStatusCode(401);
        }

        $category = $this->kategoriModel->find($id);

        if (!$category) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Kategori tidak ditemukan'
            ])->setStatusCode(404);
        }

        $rules = [
            'nama_kategori' => 'required|min_length[3]|max_length[100]',
            'deskripsi' => 'permit_empty|max_length[500]'
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $this->validator->getErrors()
            ])->setStatusCode(400);
        }

        $data = [
            'nama_kategori' => $this->request->getPost('nama_kategori'),
            'deskripsi' => $this->request->getPost('deskripsi')
        ];

        if ($this->kategoriModel->update($id, $data)) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Kategori berhasil diperbarui'
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Gagal memperbarui kategori'
        ])->setStatusCode(500);
    }

    /**
     * Delete category
     */
    public function delete($id)
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'ADMIN') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Unauthorized'
            ])->setStatusCode(401);
        }

        $category = $this->kategoriModel->find($id);

        if (!$category) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Kategori tidak ditemukan'
            ])->setStatusCode(404);
        }

        // Check if category has products
        $produkModel = new \App\Models\ProdukModel();
        $productCount = $produkModel->where('kategori_id', $id)->countAllResults();

        if ($productCount > 0) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Tidak dapat menghapus kategori yang memiliki produk'
            ])->setStatusCode(400);
        }

        if ($this->kategoriModel->delete($id)) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Kategori berhasil dihapus'
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Gagal menghapus kategori'
        ])->setStatusCode(500);
    }
}
