<?php

namespace App\Models;

use CodeIgniter\Model;

class KategoriModel extends Model
{
    protected $table            = 'kategori';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'nama_kategori',
        'deskripsi'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules = [
        'nama_kategori' => 'required|min_length[3]|max_length[100]',
        'deskripsi'     => 'permit_empty|max_length[500]'
    ];

    protected $validationMessages = [
        'nama_kategori' => [
            'required'   => 'Nama kategori harus diisi',
            'min_length' => 'Nama kategori minimal 3 karakter'
        ]
    ];

    protected $skipValidation = false;

    /**
     * Get all categories with active product count
     * Uses conditional aggregation to preserve LEFT JOIN behavior
     * (categories with zero active products still appear)
     */
    public function getKategoriWithCount()
    {
        return $this->select('kategori.*, COUNT(CASE WHEN produk.is_active = 1 THEN 1 END) as jumlah_produk', false)
                    ->join('produk', 'produk.kategori_id = kategori.id', 'left')
                    ->groupBy('kategori.id')
                    ->findAll();
    }
}
