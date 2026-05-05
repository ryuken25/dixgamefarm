<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class CustomerFilter implements FilterInterface
{
    /**
     * Check if user is logged in and has PELANGGAN role
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to(base_url('auth/login'))->with('error', 'Anda harus login terlebih dahulu');
        }

        if (session()->get('role') !== 'PELANGGAN') {
            return redirect()->to(base_url('/'))->with('error', 'Akses ditolak. Halaman ini khusus untuk pelanggan.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
