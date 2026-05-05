<?php

namespace App\Models;

use CodeIgniter\Model;

class DetailPesananModel extends Model
{
    protected $table            = 'detail_pesanan';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'pesanan_id',
        'produk_id',
        'harga_satuan_snapshot',
        'jumlah',
        'subtotal',
        'is_preorder_item'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $skipValidation = false;

    /**
     * Get order details with product info
     */
    public function getDetailsByPesananId($pesananId)
    {
        return $this->select('detail_pesanan.*, produk.nama_ayam, produk.foto, kategori.nama_kategori')
                    ->join('produk', 'produk.id = detail_pesanan.produk_id')
                    ->join('kategori', 'kategori.id = produk.kategori_id')
                    ->where('detail_pesanan.pesanan_id', $pesananId)
                    ->findAll();
    }

    /**
     * Get total items in order
     */
    public function getTotalItemsByPesananId($pesananId)
    {
        $result = $this->selectSum('jumlah')
                       ->where('pesanan_id', $pesananId)
                       ->first();

        return $result['jumlah'] ?? 0;
    }
}
