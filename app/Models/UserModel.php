<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'email',
        'password_hash',
        'nama_lengkap',
        'role',
        'no_hp',
        'alamat_lengkap'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules = [
        'email'         => 'required|valid_email|is_unique[users.email,id,{id}]',
        'password_hash' => 'required|min_length[8]',
        'nama_lengkap'  => 'required|min_length[3]',
        'role'          => 'required|in_list[ADMIN,PELANGGAN]',
        'no_hp'         => 'permit_empty|min_length[10]|max_length[20]',
    ];

    protected $validationMessages = [
        'email' => [
            'required'   => 'Email harus diisi',
            'valid_email' => 'Email tidak valid',
            'is_unique'  => 'Email sudah terdaftar'
        ],
        'password_hash' => [
            'required'   => 'Password harus diisi',
            'min_length' => 'Password minimal 8 karakter'
        ],
        'nama_lengkap' => [
            'required'   => 'Nama lengkap harus diisi',
            'min_length' => 'Nama minimal 3 karakter'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['hashPassword'];
    protected $beforeUpdate   = ['hashPassword'];

    protected function hashPassword(array $data)
    {
        if (isset($data['data']['password_hash'])) {
            $data['data']['password_hash'] = password_hash($data['data']['password_hash'], PASSWORD_DEFAULT);
        }
        return $data;
    }

    /**
     * Verify user credentials for login
     */
    public function verifyLogin(string $email, string $password)
    {
        $user = $this->where('email', $email)->first();

        if (!$user) {
            return false;
        }

        if (password_verify($password, $user['password_hash'])) {
            return $user;
        }

        return false;
    }

    /**
     * Get user by email
     */
    public function getUserByEmail(string $email)
    {
        return $this->where('email', $email)->first();
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(int $userId): bool
    {
        $user = $this->find($userId);
        return $user && $user['role'] === 'ADMIN';
    }
}
