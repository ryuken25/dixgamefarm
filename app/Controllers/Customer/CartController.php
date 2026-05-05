<?php

namespace App\Controllers\Customer;

use App\Controllers\BaseController;
use App\Models\KeranjangModel;
use App\Models\ItemKeranjangModel;
use App\Models\ProdukModel;

class CartController extends BaseController
{
    protected $keranjangModel;
    protected $itemKeranjangModel;
    protected $produkModel;

    public function __construct()
    {
        $this->keranjangModel = new KeranjangModel();
        $this->itemKeranjangModel = new ItemKeranjangModel();
        $this->produkModel = new ProdukModel();
        helper(['form', 'url']);
    }

    /**
     * Show cart page
     */
    public function index()
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'PELANGGAN') {
            return redirect()->to(base_url('auth/login'))->with('error', 'Anda harus login sebagai pelanggan');
        }

        $userId = session()->get('id');
        $cartData = $this->keranjangModel->getCartWithItems($userId);

        $data = [
            'title' => 'Keranjang Belanja - DIX Game Farm',
            'cartData' => $cartData
        ];

        return view('customer/cart', $data);
    }

    /**
     * Add item to cart (AJAX)
     */
    public function addItem()
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'PELANGGAN') {
            return $this->cartResponse(401, false, 'Anda harus login sebagai pelanggan');
        }

        $produkId = (int) ($this->request->getPost('produk_id') ?? 0);
        $jumlah = (int) ($this->request->getPost('jumlah') ?? 1);

        if (!$produkId || $jumlah < 1) {
            return $this->cartResponse(400, false, 'Data tidak valid');
        }

        $produk = $this->produkModel->find($produkId);

        if (!$produk || !$produk['is_active']) {
            return $this->cartResponse(404, false, 'Produk tidak ditemukan atau tidak aktif');
        }

        $userId = session()->get('id');
        $cart = $this->keranjangModel->getOrCreateCart($userId);
        $existingItem = $this->itemKeranjangModel->getItemByCartAndProduct($cart['id'], $produkId);
        $targetQuantity = (int) ($existingItem['jumlah'] ?? 0) + $jumlah;

        if (!$this->produkModel->checkStockAvailability($produkId, $targetQuantity)) {
            return $this->cartResponse(400, false, 'Stok tidak mencukupi', [
                'available_stock' => (int) ($produk['stok_tersedia'] ?? 0),
                'requested_quantity' => $targetQuantity,
            ]);
        }

        $result = $this->itemKeranjangModel->addOrUpdateItem($cart['id'], $produkId, $jumlah);

        if ($result) {
            $this->keranjangModel->touchCart($userId);
            $cartData = $this->keranjangModel->getCartWithItems($userId);
            $updatedItem = $this->itemKeranjangModel->getItemByCartAndProduct($cart['id'], $produkId);

            return $this->cartResponse(200, true, $existingItem ? 'Jumlah produk di keranjang berhasil diperbarui' : 'Produk berhasil ditambahkan ke keranjang', [
                'cart_count' => count($cartData['items']),
                'cart_total' => $cartData['total'],
                'item_quantity' => (int) ($updatedItem['jumlah'] ?? $jumlah),
            ]);
        }

        return $this->cartResponse(500, false, 'Gagal menambahkan produk ke keranjang');
    }

    /**
     * Update item quantity (AJAX)
     */
    public function updateQuantity()
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'PELANGGAN') {
            return $this->cartResponse(401, false, 'Unauthorized');
        }

        $itemId = (int) ($this->request->getPost('item_id') ?? 0);
        $jumlah = (int) ($this->request->getPost('jumlah') ?? 0);

        if (!$itemId || $jumlah < 0) {
            return $this->cartResponse(400, false, 'Data tidak valid');
        }

        $item = $this->itemKeranjangModel->find($itemId);

        if (!$item) {
            return $this->cartResponse(404, false, 'Item tidak ditemukan');
        }

        $userId = session()->get('id');
        $cart = $this->keranjangModel->getOrCreateCart($userId);
        if ($item['keranjang_id'] != $cart['id']) {
            return $this->cartResponse(403, false, 'Item tidak ditemukan');
        }

        if ($jumlah > 0) {
            $produk = $this->produkModel->find($item['produk_id']);

            if (!$this->produkModel->checkStockAvailability($item['produk_id'], $jumlah)) {
                return $this->cartResponse(400, false, 'Stok tidak mencukupi', [
                    'available_stock' => (int) ($produk['stok_tersedia'] ?? 0)
                ]);
            }
        }

        $result = $this->itemKeranjangModel->updateQuantity($itemId, $jumlah);

        if ($result) {
            $cartData = $this->keranjangModel->getCartWithItems($userId);

            return $this->cartResponse(200, true, 'Jumlah berhasil diperbarui', [
                'cart_count' => count($cartData['items']),
                'cart_total' => $cartData['total']
            ]);
        }

        return $this->cartResponse(500, false, 'Gagal memperbarui jumlah');
    }

    /**
     * Remove item from cart (AJAX)
     */
    public function removeItem()
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'PELANGGAN') {
            return $this->cartResponse(401, false, 'Unauthorized');
        }

        $itemId = (int) ($this->request->getPost('item_id') ?? 0);

        if (!$itemId) {
            return $this->cartResponse(400, false, 'Data tidak valid');
        }

        $userId = session()->get('id');
        $item = $this->itemKeranjangModel->find($itemId);

        if (!$item) {
            return $this->cartResponse(404, false, 'Item tidak ditemukan');
        }

        $cart = $this->keranjangModel->getOrCreateCart($userId);
        if ($item['keranjang_id'] != $cart['id']) {
            return $this->cartResponse(403, false, 'Item tidak ditemukan');
        }

        $result = $this->itemKeranjangModel->removeItem($itemId);

        if ($result) {
            $cartData = $this->keranjangModel->getCartWithItems($userId);

            return $this->cartResponse(200, true, 'Item berhasil dihapus', [
                'cart_count' => count($cartData['items']),
                'cart_total' => $cartData['total']
            ]);
        }

        return $this->cartResponse(500, false, 'Gagal menghapus item');
    }

    /**
     * Clear entire cart (AJAX)
     */
    public function clearCart()
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'PELANGGAN') {
            return $this->cartResponse(401, false, 'Unauthorized');
        }

        $userId = session()->get('id');
        $result = $this->keranjangModel->clearCart($userId);

        if ($result) {
            return $this->cartResponse(200, true, 'Keranjang berhasil dikosongkan', [
                'cart_count' => 0,
                'cart_total' => 0
            ]);
        }

        return $this->cartResponse(500, false, 'Gagal mengosongkan keranjang');
    }

    /**
     * Get cart count (AJAX - for header badge)
     */
    public function getCartCount()
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'PELANGGAN') {
            return $this->response->setJSON([
                'count' => 0,
                'total' => 0,
                'csrf_token' => csrf_token(),
                'csrf_hash' => csrf_hash(),
            ]);
        }

        $userId = session()->get('id');
        $cartData = $this->keranjangModel->getCartWithItems($userId);

        return $this->response->setJSON([
            'count' => count($cartData['items']),
            'total' => $cartData['total'],
            'csrf_token' => csrf_token(),
            'csrf_hash' => csrf_hash(),
        ]);
    }

    private function cartResponse(int $statusCode, bool $success, string $message, array $extra = [])
    {
        return $this->response->setJSON(array_merge([
            'success' => $success,
            'message' => $message,
            'csrf_token' => csrf_token(),
            'csrf_hash' => csrf_hash(),
        ], $extra))->setStatusCode($statusCode);
    }
}
