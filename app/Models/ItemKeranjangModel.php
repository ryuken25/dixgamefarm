<?php

namespace App\Models;

use CodeIgniter\Model;

class ItemKeranjangModel extends Model
{
    protected $table = 'item_keranjang';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'keranjang_id',
        'produk_id',
        'jumlah'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'keranjang_id' => 'required|is_not_unique[keranjang.id]',
        'produk_id' => 'required|is_not_unique[produk.id]',
        'jumlah' => 'required|integer|greater_than[0]'
    ];

    protected $skipValidation = false;

    /**
     * Get cart items with product details
     */
    public function getItemsByKeranjangId($keranjangId)
    {
        return $this->select('item_keranjang.*, produk.nama_ayam, produk.harga, produk.foto, produk.stok_tersedia, produk.is_preorder, produk.estimasi_pre_order, kategori.nama_kategori')
            ->join('produk', 'produk.id = item_keranjang.produk_id')
            ->join('kategori', 'kategori.id = produk.kategori_id')
            ->where('item_keranjang.keranjang_id', $keranjangId)
            ->where('produk.is_active', true)
            ->findAll();
    }

    /**
     * Add or update item in cart
     */
    public function addOrUpdateItem($keranjangId, $produkId, $jumlah)
    {
        $existingItem = $this->getItemByCartAndProduct($keranjangId, $produkId);

        if ($existingItem) {
            return $this->update($existingItem['id'], [
                'jumlah' => $existingItem['jumlah'] + $jumlah
            ]);
        }

        return $this->insert([
            'keranjang_id' => $keranjangId,
            'produk_id' => $produkId,
            'jumlah' => $jumlah
        ]);
    }

    public function getItemByCartAndProduct($keranjangId, $produkId)
    {
        return $this->where('keranjang_id', $keranjangId)
            ->where('produk_id', $produkId)
            ->first();
    }

    /**
     * Update item quantity
     */
    public function updateQuantity($itemId, $jumlah)
    {
        if ($jumlah <= 0) {
            return $this->delete($itemId);
        }

        return $this->update($itemId, ['jumlah' => $jumlah]);
    }

    /**
     * Remove item from cart
     */
    public function removeItem($itemId)
    {
        return $this->delete($itemId);
    }

    /**
     * Get cart item count for user
     */
    public function getCartItemCount($keranjangId)
    {
        return $this->where('keranjang_id', $keranjangId)->countAllResults();
    }

    /**
     * Validate cart items stock before checkout
     */
    public function validateCartStock($keranjangId)
    {
        $items = $this->getItemsByKeranjangId($keranjangId);
        $errors = [];

        foreach ($items as $item) {
            // Preorder items bypass stock validation — they don't need existing stock
            if (!empty($item['is_preorder'])) {
                continue;
            }

            if ($item['stok_tersedia'] < $item['jumlah']) {
                $errors[] = [
                    'produk_id' => $item['produk_id'],
                    'nama_ayam' => $item['nama_ayam'],
                    'requested' => $item['jumlah'],
                    'available' => $item['stok_tersedia'],
                    'message' => "Stok {$item['nama_ayam']} tidak mencukupi. Tersedia: {$item['stok_tersedia']}, diminta: {$item['jumlah']}"
                ];
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
