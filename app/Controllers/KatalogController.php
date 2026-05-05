<?php

namespace App\Controllers;

use App\Models\ProdukModel;
use App\Models\KategoriModel;

class KatalogController extends BaseController
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
     * Show product catalog (public)
     */
    public function index()
    {
        $kategoriId = $this->request->getGet('kategori');
        $keyword = $this->request->getGet('keyword');

        if ($keyword) {
            $products = $this->produkModel->searchProduk($keyword, $kategoriId);
        } elseif ($kategoriId) {
            $products = $this->produkModel->getProdukByKategori($kategoriId, true);
        } else {
            $products = $this->produkModel->getProdukWithKategori(true);
        }

        $categories = $this->kategoriModel->findAll();

        $data = [
            'title' => 'Katalog Produk - DIX Game Farm',
            'products' => $products,
            'categories' => $categories,
            'selected_kategori' => $kategoriId,
            'keyword' => $keyword
        ];

        return view('public/katalog', $data);
    }

    /**
     * Show product detail
     */
    public function detail($id)
    {
        $product = $this->produkModel->getProdukById($id);

        if (!$product || !$product['is_active']) {
            return redirect()->to(base_url('katalog'))->with('error', 'Produk tidak ditemukan');
        }

        $data = [
            'title' => $product['nama_ayam'] . ' - DIX Game Farm',
            'product' => $product
        ];

        return view('public/product_detail', $data);
    }

    /**
     * Get product info (AJAX - for quick view modal)
     */
    public function getProductInfo($id)
    {
        $product = $this->produkModel->getProdukById($id);

        if (!$product || !$product['is_active']) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Produk tidak ditemukan'
            ])->setStatusCode(404);
        }

        return $this->response->setJSON([
            'success' => true,
            'product' => $product
        ]);
    }

    /**
     * Check stock availability (AJAX - real-time stock check)
     */
    public function checkStock($id)
    {
        $product = $this->produkModel->find($id);

        if (!$product || !$product['is_active']) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Produk tidak ditemukan'
            ])->setStatusCode(404);
        }

        $stockStatus = 'habis';
        $stockBadge = 'danger';

        // Pre-order: label as such regardless of stok_tersedia
        if (!empty($product['is_preorder'])) {
            $stockStatus = 'preorder';
            $stockBadge = 'info';
        } elseif ($product['stok_tersedia'] >= 5) {
            $stockStatus = 'tersedia';
            $stockBadge = 'success';
        } elseif ($product['stok_tersedia'] > 0) {
            $stockStatus = 'terbatas';
            $stockBadge = 'warning';
        }

        $message = match ($stockStatus) {
            'preorder' => 'Pre-Order' . (!empty($product['estimasi_pre_order']) ? ' — ' . $product['estimasi_pre_order'] : ''),
            'habis'    => 'Stok habis',
            default    => "Stok tersedia: {$product['stok_tersedia']}",
        };

        return $this->response->setJSON([
            'success' => true,
            'stok_tersedia' => $product['stok_tersedia'],
            'stok_dibooking' => $product['stok_dibooking'],
            'is_preorder' => !empty($product['is_preorder']),
            'estimasi_pre_order' => $product['estimasi_pre_order'] ?? null,
            'status' => $stockStatus,
            'badge' => $stockBadge,
            'message' => $message,
        ]);
    }
}
