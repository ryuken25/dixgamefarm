<?php

namespace App\Models;

use CodeIgniter\Model;

class ProdukModel extends Model
{
    protected $table = 'produk';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'kategori_id',
        'nama_ayam',
        'usia_berat',
        'stok_tersedia',
        'stok_dibooking',
        'harga',
        'deskripsi',
        'foto',
        'is_active',
        'is_preorder',
        'estimasi_pre_order'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'kategori_id' => 'required|is_not_unique[kategori.id]',
        'nama_ayam' => 'required|min_length[3]',
        'harga' => 'required|decimal|greater_than[0]',
        'stok_tersedia' => 'required|integer|greater_than_equal_to[0]',
        'stok_dibooking' => 'permit_empty|integer|greater_than_equal_to[0]',
    ];

    protected $validationMessages = [
        'kategori_id' => [
            'required' => 'Kategori harus dipilih',
            'is_not_unique' => 'Kategori tidak valid'
        ],
        'nama_ayam' => [
            'required' => 'Nama produk harus diisi'
        ],
        'harga' => [
            'required' => 'Harga harus diisi',
            'greater_than' => 'Harga harus lebih dari 0'
        ]
    ];

    protected $skipValidation = false;

    /**
     * Get all products with category info
     */
    public function getProdukWithKategori($isActive = true)
    {
        $builder = $this->select('produk.*, kategori.nama_kategori')
            ->join('kategori', 'kategori.id = produk.kategori_id');

        if ($isActive !== null) {
            $builder->where('produk.is_active', $isActive);
        }

        return $builder->findAll();
    }

    /**
     * Get product by ID with category
     */
    public function getProdukById($id)
    {
        return $this->select('produk.*, kategori.nama_kategori')
            ->join('kategori', 'kategori.id = produk.kategori_id')
            ->where('produk.id', $id)
            ->first();
    }

    /**
     * Get products by category
     */
    public function getProdukByKategori($kategoriId, $isActive = true)
    {
        $builder = $this->where('kategori_id', $kategoriId);

        if ($isActive !== null) {
            $builder->where('is_active', $isActive);
        }

        return $builder->findAll();
    }

    /**
     * Check if product has sufficient stock.
     * Pre-order items (is_preorder=1) always pass — stock is not enforced.
     */
    public function checkStockAvailability($produkId, $jumlah): bool
    {
        $produk = $this->find($produkId);

        if (!$produk) {
            return false;
        }

        if (!empty($produk['is_preorder'])) {
            return true;
        }

        return $produk['stok_tersedia'] >= $jumlah;
    }

    /**
     * Returns true if the product is a pre-order item.
     */
    public function isPreorder($produkId): bool
    {
        $produk = $this->find($produkId);
        return $produk && !empty($produk['is_preorder']);
    }

    /**
     * Reserve stock (move from tersedia to dibooking) - CRITICAL TRANSACTION
     * This is called during checkout process.
     *
     * IMPORTANT: This method does NOT manage its own transaction.
     * The caller (PesananModel::createOrder) MUST wrap this in transBegin/transCommit.
     * Uses SELECT ... FOR UPDATE for true MySQL row-level locking to prevent race conditions.
     */
    public function reserveStock($produkId, $jumlah): bool
    {
        $db = \Config\Database::connect();

        try {
            // Acquire exclusive row lock using FOR UPDATE (prevents race conditions)
            $produk = $db->query(
                "SELECT id, stok_tersedia, stok_dibooking FROM {$this->table} WHERE id = ? FOR UPDATE",
                [$produkId]
            )->getRowArray();

            if (!$produk) {
                return false;
            }

            // Check if sufficient stock is available (under lock)
            if ($produk['stok_tersedia'] < $jumlah) {
                return false;
            }

            // Transfer stock from tersedia to dibooking (atomic under lock)
            $db->table($this->table)
                ->where('id', $produkId)
                ->update([
                    'stok_tersedia' => $produk['stok_tersedia'] - $jumlah,
                    'stok_dibooking' => $produk['stok_dibooking'] + $jumlah,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

            return true;
        } catch (\Exception $e) {
            log_message('error', 'Stock reservation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Release stock (return from dibooking to tersedia) - for cancelled orders
     *
     * IMPORTANT: This method does NOT manage its own transaction.
     * The caller MUST wrap this in transBegin/transCommit.
     * Uses SELECT ... FOR UPDATE for true MySQL row-level locking.
     */
    public function releaseStock($produkId, $jumlah): bool
    {
        $db = \Config\Database::connect();

        try {
            // Acquire exclusive row lock
            $produk = $db->query(
                "SELECT id, stok_tersedia, stok_dibooking FROM {$this->table} WHERE id = ? FOR UPDATE",
                [$produkId]
            )->getRowArray();

            if (!$produk) {
                return false;
            }

            // Return stock from dibooking to tersedia
            $db->table($this->table)
                ->where('id', $produkId)
                ->update([
                    'stok_tersedia' => $produk['stok_tersedia'] + $jumlah,
                    'stok_dibooking' => max(0, $produk['stok_dibooking'] - $jumlah),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

            return true;
        } catch (\Exception $e) {
            log_message('error', 'Stock release failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Finalize stock (remove from dibooking) - for completed orders
     *
     * IMPORTANT: This method does NOT manage its own transaction.
     * The caller MUST wrap this in transBegin/transCommit.
     * Uses SELECT ... FOR UPDATE for true MySQL row-level locking.
     */
    public function finalizeStock($produkId, $jumlah): bool
    {
        $db = \Config\Database::connect();

        try {
            // Acquire exclusive row lock
            $produk = $db->query(
                "SELECT id, stok_tersedia, stok_dibooking FROM {$this->table} WHERE id = ? FOR UPDATE",
                [$produkId]
            )->getRowArray();

            if (!$produk) {
                return false;
            }

            // Permanently remove from dibooking (stock is sold)
            $db->table($this->table)
                ->where('id', $produkId)
                ->update([
                    'stok_dibooking' => max(0, $produk['stok_dibooking'] - $jumlah),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

            return true;
        } catch (\Exception $e) {
            log_message('error', 'Stock finalization failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get low stock products (warning threshold)
     */
    public function getLowStockProducts($threshold = 10)
    {
        return $this->select('produk.*, kategori.nama_kategori')
            ->join('kategori', 'kategori.id = produk.kategori_id')
            ->where('produk.is_active', true)
            ->where('produk.stok_tersedia <=', $threshold)
            ->findAll();
    }

    /**
     * Get low stock count
     */
    public function getLowStockCount($threshold = 10)
    {
        return $this->where('is_active', true)
            ->where('stok_tersedia <=', $threshold)
            ->countAllResults(false);
    }

    /**
     * Search products
     */
    public function searchProduk($keyword, $kategoriId = null)
    {
        $builder = $this->select('produk.*, kategori.nama_kategori')
            ->join('kategori', 'kategori.id = produk.kategori_id')
            ->where('produk.is_active', true)
            ->groupStart()
            ->like('produk.nama_ayam', $keyword)
            ->orLike('produk.deskripsi', $keyword)
            ->groupEnd();

        if ($kategoriId) {
            $builder->where('produk.kategori_id', $kategoriId);
        }

        return $builder->findAll();
    }

    public function findByTelegramIdentifier(string $identifier, int $limit = 5): array
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return [];
        }

        if (ctype_digit($identifier)) {
            $product = $this->find((int) $identifier);
            return $product ? [$product] : [];
        }

        return $this->builder()
            ->select('id, nama_ayam, stok_tersedia, stok_dibooking, is_preorder, is_active, harga')
            ->like('nama_ayam', $identifier)
            ->orderBy('nama_ayam', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    public function mutateAvailableStock(int $produkId, string $mode, int $amount): array
    {
        $mode = strtolower(trim($mode));
        if (!in_array($mode, ['set', 'add', 'subtract'], true)) {
            return ['success' => false, 'message' => 'Mode perubahan stok tidak valid'];
        }

        if ($amount < 0) {
            return ['success' => false, 'message' => 'Jumlah stok tidak valid'];
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $product = $db->query(
                "SELECT id, nama_ayam, stok_tersedia, stok_dibooking, is_preorder FROM {$this->table} WHERE id = ? FOR UPDATE",
                [$produkId]
            )->getRowArray();

            if (!$product) {
                $db->transRollback();
                return ['success' => false, 'message' => 'Produk tidak ditemukan'];
            }

            $oldStock = (int) $product['stok_tersedia'];
            $newStock = $oldStock;

            if ($mode === 'set') {
                $newStock = $amount;
            } elseif ($mode === 'add') {
                $newStock = $oldStock + $amount;
            } else {
                $newStock = $oldStock - $amount;
            }

            if ($newStock < 0) {
                $db->transRollback();
                return ['success' => false, 'message' => 'Stok tidak boleh kurang dari 0'];
            }

            $db->table($this->table)
                ->where('id', $produkId)
                ->update([
                    'stok_tersedia' => $newStock,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            if ($db->affectedRows() < 0) {
                $db->transRollback();
                return ['success' => false, 'message' => 'Gagal memperbarui stok produk'];
            }

            $db->transCommit();

            return [
                'success' => true,
                'product' => $product,
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
            ];
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'ProdukModel::mutateAvailableStock failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kendala saat memperbarui stok'];
        }
    }
}
