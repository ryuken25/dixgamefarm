<?php

namespace App\Models;

use CodeIgniter\Model;

class KeranjangModel extends Model
{
    protected $table = 'keranjang';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'user_id',
        'last_updated'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = ''; // keranjang table has no updated_at column

    protected $skipValidation = false;

    /**
     * Get or create cart for user
     */
    public function getOrCreateCart($userId)
    {
        $cart = $this->where('user_id', $userId)
            ->orderBy('id', 'ASC')
            ->first();

        if (!$cart) {
            $this->insert([
                'user_id' => $userId,
                'last_updated' => date('Y-m-d H:i:s')
            ]);
            $cart = $this->where('user_id', $userId)
                ->orderBy('id', 'ASC')
                ->first();
        }

        return $cart;
    }

    /**
     * Get cart with items
     */
    public function getCartWithItems($userId)
    {
        $cart = $this->getOrCreateCart($userId);

        if (!$cart) {
            return null;
        }

        $itemModel = new ItemKeranjangModel();
        $items = $itemModel->getItemsByKeranjangId($cart['id']);

        return [
            'cart' => $cart,
            'items' => $items,
            'total' => $this->calculateTotal($items)
        ];
    }

    /**
     * Calculate total price
     */
    private function calculateTotal($items)
    {
        $total = 0;
        foreach ($items as $item) {
            $total += $item['harga'] * $item['jumlah'];
        }
        return $total;
    }

    /**
     * Clear cart after checkout
     */
    public function clearCart($userId)
    {
        $cart = $this->where('user_id', $userId)
            ->orderBy('id', 'ASC')
            ->first();

        if ($cart) {
            $itemModel = new ItemKeranjangModel();
            $itemModel->where('keranjang_id', $cart['id'])->delete();
        }

        return true;
    }

    /**
     * Update cart timestamp
     */
    public function touchCart($userId)
    {
        $cart = $this->where('user_id', $userId)
            ->orderBy('id', 'ASC')
            ->first();

        if ($cart) {
            $this->update($cart['id'], [
                'last_updated' => date('Y-m-d H:i:s')
            ]);
        }
    }
}
