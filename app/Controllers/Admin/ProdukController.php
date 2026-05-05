<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ProdukModel;
use App\Models\KategoriModel;

class ProdukController extends BaseController
{
    protected $produkModel;
    protected $kategoriModel;

    public function __construct()
    {
        $this->produkModel = new ProdukModel();
        $this->kategoriModel = new KategoriModel();
        helper(['form', 'url']);
    }

    /**
     * Product list (admin)
     */
    public function index()
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'ADMIN') {
            return redirect()->to(base_url('auth/login'))->with('error', 'Akses ditolak');
        }

        $products = $this->produkModel->getProdukWithKategori(null);
        $categories = $this->kategoriModel->findAll();

        $data = [
            'title' => 'Manajemen Produk - DIX Game Farm',
            'products' => $products,
            'categories' => $categories
        ];

        return view('admin/produk/index', $data);
    }

    /**
     * Create product
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
            'kategori_id' => 'required|is_not_unique[kategori.id]',
            'nama_ayam' => 'required|min_length[3]',
            'harga' => 'required|decimal|greater_than[0]',
            'stok_tersedia' => 'required|integer|greater_than_equal_to[0]',
            'foto' => [
                'rules' => 'permit_empty|uploaded[foto]|max_size[foto,2048]|is_image[foto]|mime_in[foto,image/jpg,image/jpeg,image/png]',
                'errors' => [
                    'max_size' => 'Ukuran file maksimal 2MB',
                    'is_image' => 'File harus berupa gambar',
                    'mime_in' => 'Format file harus JPG, JPEG, atau PNG'
                ]
            ]
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $this->validator->getErrors()
            ])->setStatusCode(400);
        }

        $fotoPath = null;

        // Handle file upload
        if ($this->request->getFile('foto') && $this->request->getFile('foto')->isValid()) {
            $file = $this->request->getFile('foto');
            $uploadPath = FCPATH . 'uploads/produk';

            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            $newName = 'produk_' . time() . '_' . uniqid() . '.' . $file->getExtension();

            if ($file->move($uploadPath, $newName)) {
                $fotoPath = 'uploads/produk/' . $newName;
            }
        }

        $isPreorder = $this->request->getPost('is_preorder') ? true : false;

        $data = [
            'kategori_id' => $this->request->getPost('kategori_id'),
            'nama_ayam' => $this->request->getPost('nama_ayam'),
            'usia_berat' => $this->request->getPost('usia_berat'),
            'harga' => $this->request->getPost('harga'),
            'stok_tersedia' => $this->request->getPost('stok_tersedia'),
            'stok_dibooking' => 0,
            'deskripsi' => $this->request->getPost('deskripsi'),
            'foto' => $fotoPath,
            'is_active' => $this->request->getPost('is_active') ? true : false,
            'is_preorder' => $isPreorder,
            'estimasi_pre_order' => $isPreorder ? ($this->request->getPost('estimasi_pre_order') ?: null) : null,
        ];

        if ($this->produkModel->insert($data)) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Produk berhasil ditambahkan'
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Gagal menambahkan produk'
        ])->setStatusCode(500);
    }

    /**
     * Update product
     */
    public function update($id)
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'ADMIN') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Unauthorized'
            ])->setStatusCode(401);
        }

        $product = $this->produkModel->find($id);

        if (!$product) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Produk tidak ditemukan'
            ])->setStatusCode(404);
        }

        $rules = [
            'kategori_id' => 'required|is_not_unique[kategori.id]',
            'nama_ayam' => 'required|min_length[3]',
            'harga' => 'required|decimal|greater_than[0]',
            'stok_tersedia' => 'required|integer|greater_than_equal_to[0]'
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $this->validator->getErrors()
            ])->setStatusCode(400);
        }

        $fotoPath = $product['foto'];

        // Handle new file upload
        if ($this->request->getFile('foto') && $this->request->getFile('foto')->isValid()) {
            $file = $this->request->getFile('foto');
            $uploadPath = FCPATH . 'uploads/produk';

            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            $newName = 'produk_' . time() . '_' . uniqid() . '.' . $file->getExtension();

            if ($file->move($uploadPath, $newName)) {
                // Delete old file
                if ($product['foto'] && file_exists(FCPATH . $product['foto'])) {
                    @unlink(FCPATH . $product['foto']);
                }

                $fotoPath = 'uploads/produk/' . $newName;
            }
        }

        $isPreorder = $this->request->getPost('is_preorder') ? true : false;

        $data = [
            'kategori_id' => $this->request->getPost('kategori_id'),
            'nama_ayam' => $this->request->getPost('nama_ayam'),
            'usia_berat' => $this->request->getPost('usia_berat'),
            'harga' => $this->request->getPost('harga'),
            'stok_tersedia' => $this->request->getPost('stok_tersedia'),
            'deskripsi' => $this->request->getPost('deskripsi'),
            'foto' => $fotoPath,
            'is_active' => $this->request->getPost('is_active') ? true : false,
            'is_preorder' => $isPreorder,
            'estimasi_pre_order' => $isPreorder ? ($this->request->getPost('estimasi_pre_order') ?: null) : null,
        ];

        if ($this->produkModel->update($id, $data)) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Produk berhasil diperbarui'
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Gagal memperbarui produk'
        ])->setStatusCode(500);
    }

    /**
     * Delete product
     */
    public function delete($id)
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'ADMIN') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Unauthorized'
            ])->setStatusCode(401);
        }

        $product = $this->produkModel->find($id);

        if (!$product) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Produk tidak ditemukan'
            ])->setStatusCode(404);
        }

        // Check if product has booked stock
        if ($product['stok_dibooking'] > 0) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Tidak dapat menghapus produk dengan stok yang sedang dibooking'
            ])->setStatusCode(400);
        }

        if ($this->produkModel->delete($id)) {
            // Delete product photo
            if ($product['foto'] && file_exists(FCPATH . $product['foto'])) {
                @unlink(FCPATH . $product['foto']);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Produk berhasil dihapus'
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Gagal menghapus produk'
        ])->setStatusCode(500);
    }

    /**
     * Update stock (separate endpoint for stock management)
     * Uses row locking to prevent race conditions with concurrent checkouts.
     */
    public function updateStock()
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'ADMIN') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Unauthorized'
            ])->setStatusCode(401);
        }

        $produkId = $this->request->getPost('produk_id');
        $stokTersedia = $this->request->getPost('stok_tersedia');

        if (!$produkId || $stokTersedia < 0) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Data tidak valid'
            ])->setStatusCode(400);
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            // Lock the row to prevent race with concurrent checkout
            $product = $db->query(
                "SELECT * FROM produk WHERE id = ? FOR UPDATE",
                [$produkId]
            )->getRowArray();

            if (!$product) {
                $db->transRollback();
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Produk tidak ditemukan'
                ])->setStatusCode(404);
            }

            $db->table('produk')->where('id', $produkId)->update([
                'stok_tersedia' => $stokTersedia,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            $db->transCommit();

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Stok berhasil diperbarui'
            ]);
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Stock update failed: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal memperbarui stok'
            ])->setStatusCode(500);
        }
    }
}
