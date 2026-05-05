<?php

namespace App\Controllers\Customer;

use App\Controllers\BaseController;
use App\Models\KeranjangModel;
use App\Models\ItemKeranjangModel;
use App\Models\PesananModel;
use App\Models\ProdukModel;

class CheckoutController extends BaseController
{
    protected $keranjangModel;
    protected $itemKeranjangModel;
    protected $pesananModel;
    protected $produkModel;

    public function __construct()
    {
        $this->keranjangModel = new KeranjangModel();
        $this->itemKeranjangModel = new ItemKeranjangModel();
        $this->pesananModel = new PesananModel();
        $this->produkModel = new ProdukModel();
        helper(['form', 'url']);
    }

    /**
     * List of payment methods (bank transfer + e-wallets).
     * Admin verifikasi manual — tidak pakai payment gateway.
     */
    public static function getBankOptions(): array
    {
        return [
            'BRI' => ['label' => 'Bank BRI', 'rekening' => '0123-01-123456-50-0', 'atas_nama' => 'DIX Game Farm', 'type' => 'bank'],
            'BNI' => ['label' => 'Bank BNI', 'rekening' => '0123456789', 'atas_nama' => 'DIX Game Farm', 'type' => 'bank'],
            'BCA' => ['label' => 'Bank BCA', 'rekening' => '1234567890', 'atas_nama' => 'DIX Game Farm', 'type' => 'bank'],
            'ShopeePay' => ['label' => 'ShopeePay', 'rekening' => '08123456789', 'atas_nama' => 'DIX Game Farm', 'type' => 'ewallet'],
            'Dana' => ['label' => 'DANA', 'rekening' => '08123456789', 'atas_nama' => 'DIX Game Farm', 'type' => 'ewallet'],
            'GoPay' => ['label' => 'GoPay', 'rekening' => '08123456789', 'atas_nama' => 'DIX Game Farm', 'type' => 'ewallet'],
        ];
    }

    /**
     * Show checkout page
     */
    public function index()
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'PELANGGAN') {
            return redirect()->to(base_url('auth/login'))->with('error', 'Anda harus login sebagai pelanggan');
        }

        $userId = session()->get('id');
        $cartData = $this->keranjangModel->getCartWithItems($userId);

        // Check cart exists and has items
        if (!$cartData || empty($cartData['items'])) {
            return redirect()->to(base_url('katalog'))->with('error', 'Keranjang Anda kosong');
        }

        // Validate stock availability before checkout
        $stockValidation = $this->itemKeranjangModel->validateCartStock($cartData['cart']['id']);

        if (!$stockValidation['valid']) {
            return redirect()->to(base_url('customer/cart'))->with('error', 'Beberapa produk tidak memiliki stok yang cukup. Silakan periksa keranjang Anda.');
        }

        $userModel = new \App\Models\UserModel();
        $userData = $userModel->find($userId);

        if (!$userData) {
            log_message('error', 'Checkout blocked: user data not found. user_id=' . $userId);
            return redirect()->to(base_url('auth/login'))->with('error', 'Data akun tidak ditemukan. Silakan login ulang.');
        }

        // Normalize optional profile fields to avoid undefined array-key errors in views
        $userData['no_hp'] = $userData['no_hp'] ?? '';
        $userData['alamat_lengkap'] = $userData['alamat_lengkap'] ?? '';

        // Diagnostic logging for recurring checkout view key errors (e.g. missing no_hp/alamat_lengkap)
        if (!$userData || !array_key_exists('no_hp', $userData) || !array_key_exists('alamat_lengkap', $userData)) {
            log_message('error', 'Checkout user data incomplete. user_id=' . $userId . ', keys=' . implode(',', array_keys((array) $userData)));
        }

        $data = [
            'title' => 'Checkout - DIX Game Farm',
            'cartData' => $cartData,
            'user' => $userData,
            'bankOptions' => self::getBankOptions(),
            'checkoutSubmissionToken' => $this->issueCheckoutSubmissionToken(),
        ];

        return view('customer/checkout', $data);
    }

    /**
     * Process checkout - CRITICAL TRANSACTION WITH STOCK LOCKING
     */
    public function processCheckout()
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'PELANGGAN') {
            return $this->checkoutErrorResponse(401, 'Sesi login Anda tidak valid. Silakan login kembali.', [
                'reason' => 'unauthenticated_customer_checkout'
            ]);
        }

        $bankOptions = self::getBankOptions();
        $bankKeys = implode(',', array_keys($bankOptions));

        log_message('debug', 'Checkout submission received. user_id=' . session()->get('id') . ', payload_keys=' . implode(',', array_keys($this->request->getPost() ?: [])) . ', tipe_pengiriman=' . (string) $this->request->getPost('tipe_pengiriman') . ', metode_pembayaran=' . (string) $this->request->getPost('metode_pembayaran'));

        $rules = [
            'nama_lengkap' => [
                'rules' => 'required|min_length[3]|max_length[100]',
                'errors' => [
                    'required' => 'Nama lengkap wajib diisi'
                ]
            ],
            'email' => [
                'rules' => 'required|valid_email|max_length[100]',
                'errors' => [
                    'required' => 'Email wajib diisi',
                    'valid_email' => 'Format email tidak valid'
                ]
            ],
            'no_hp' => [
                'rules' => 'required|min_length[8]|max_length[20]|regex_match[/^[0-9+\-\s]+$/]',
                'errors' => [
                    'required' => 'Nomor HP wajib diisi',
                    'regex_match' => 'Nomor HP hanya boleh berisi angka, spasi, tanda plus, atau strip'
                ]
            ],
            'alamat_lengkap' => [
                'rules' => 'required|min_length[10]|max_length[1000]',
                'errors' => [
                    'required' => 'Alamat lengkap wajib diisi'
                ]
            ],
            'tipe_pengiriman' => [
                'rules' => 'required|in_list[AMBIL_SENDIRI,DIKIRIM_KURIR]',
                'errors' => [
                    'required' => 'Tipe pengiriman harus dipilih',
                    'in_list' => 'Tipe pengiriman tidak valid'
                ]
            ],
            'metode_pembayaran' => [
                'rules' => 'required|in_list[' . $bankKeys . ']',
                'errors' => [
                    'required' => 'Metode pembayaran harus dipilih',
                    'in_list' => 'Metode pembayaran tidak valid'
                ]
            ],
            'catatan' => 'permit_empty|max_length[500]'
        ];

        if (!$this->validate($rules)) {
            return $this->checkoutErrorResponse(400, 'Validasi checkout gagal', [
                'user_id' => session()->get('id')
            ], $this->validator->getErrors());
        }

        $userId = session()->get('id');
        $namaLengkap = trim((string) $this->request->getPost('nama_lengkap'));
        $email = trim((string) $this->request->getPost('email'));
        $noHp = trim((string) $this->request->getPost('no_hp'));
        $alamatLengkap = trim((string) $this->request->getPost('alamat_lengkap'));
        $tipePengiriman = $this->request->getPost('tipe_pengiriman');
        $metodePembayaran = $this->request->getPost('metode_pembayaran');
        $nomorRekeningTujuan = $bankOptions[$metodePembayaran]['rekening'] ?? null;
        $catatan = trim((string) $this->request->getPost('catatan'));

        $checkoutSnapshot = [
            'nama_lengkap' => $namaLengkap,
            'email' => $email,
            'no_hp' => $noHp,
            'alamat_lengkap' => $alamatLengkap,
        ];

        // Get cart items
        $cartData = $this->keranjangModel->getCartWithItems($userId);

        if (empty($cartData['items'])) {
            return $this->checkoutErrorResponse(400, 'Keranjang Anda kosong. Tambahkan produk terlebih dahulu.', [
                'user_id' => $userId
            ]);
        }

        // Final stock validation
        $stockValidation = $this->itemKeranjangModel->validateCartStock($cartData['cart']['id']);

        if (!$stockValidation['valid']) {
            return $this->checkoutErrorResponse(400, 'Stok produk berubah. Silakan periksa keranjang Anda lagi.', [
                'user_id' => $userId
            ], $stockValidation['errors']);
        }

        $submissionToken = $this->consumeCheckoutSubmissionToken((string) $this->request->getPost('checkout_token'));
        if (!$submissionToken['valid']) {
            return $this->checkoutErrorResponse(409, 'Permintaan checkout yang sama sedang diproses atau sudah pernah dikirim. Silakan tunggu sebentar lalu coba lagi.', [
                'user_id' => $userId,
                'reason' => 'duplicate_or_stale_checkout_submission'
            ], [], $submissionToken['next_token']);
        }

        // Create order with ACID transaction (stok_tersedia -> stok_dibooking)
        log_message('debug', 'Checkout ready to create order. user_id=' . $userId . ', item_count=' . count($cartData['items']) . ', grand_total=' . (string) ($cartData['total'] ?? 0) . ', tipe_pengiriman=' . (string) $tipePengiriman . ', metode_pembayaran=' . (string) $metodePembayaran);

        $result = $this->pesananModel->createOrder(
            $userId,
            $cartData['items'],
            $tipePengiriman,
            $catatan,
            $metodePembayaran,
            $nomorRekeningTujuan,
            $checkoutSnapshot
        );

        log_message('debug', 'Checkout createOrder finished. user_id=' . $userId . ', success=' . (!empty($result['success']) ? '1' : '0') . ', order_id=' . ($result['order_id'] ?? 'null') . ', invoice=' . ($result['invoice_number'] ?? 'null'));

        if ($result['success']) {
            return $this->response->setJSON([
                'success' => true,
                'message' => $result['message'],
                'invoice_number' => $result['invoice_number'],
                'order_id' => $result['order_id'],
                'checkout_token' => $submissionToken['next_token'],
                'redirect_url' => base_url("customer/order/{$result['order_id']}")
            ]);
        }

        return $this->checkoutErrorResponse(500, $result['message'] ?? 'Gagal membuat pesanan', [
            'user_id' => $userId,
            'result' => $result
        ], [], $submissionToken['next_token']);
    }

    /**
     * Validate checkout (AJAX endpoint for pre-checkout validation)
     */
    public function validateCheckout()
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'PELANGGAN') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Unauthorized'
            ])->setStatusCode(401);
        }

        $userId = session()->get('id');
        $cartData = $this->keranjangModel->getCartWithItems($userId);

        if (empty($cartData['items'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Keranjang kosong'
            ]);
        }

        // Validate stock
        $stockValidation = $this->itemKeranjangModel->validateCartStock($cartData['cart']['id']);

        if (!$stockValidation['valid']) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Stok tidak mencukupi',
                'errors' => $stockValidation['errors']
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Validasi berhasil',
            'cart_summary' => [
                'total_items' => count($cartData['items']),
                'grand_total' => $cartData['total']
            ]
        ]);
    }

    private function checkoutErrorResponse(int $statusCode, string $message, array $context = [], array $errors = [], ?string $checkoutToken = null)
    {
        $logLevel = $statusCode >= 500 ? 'error' : 'debug';
        log_message($logLevel, 'Checkout error response. status=' . $statusCode . ', message=' . $message . ', context=' . json_encode($context) . ', errors=' . json_encode($errors));

        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        if ($checkoutToken !== null && $checkoutToken !== '') {
            $payload['checkout_token'] = $checkoutToken;
        }

        return $this->response->setJSON($payload)->setStatusCode($statusCode);
    }

    private function issueCheckoutSubmissionToken(): string
    {
        $token = bin2hex(random_bytes(16));
        session()->set('checkout_submission_token', $token);

        return $token;
    }

    private function consumeCheckoutSubmissionToken(string $submittedToken): array
    {
        $currentToken = (string) session()->get('checkout_submission_token');
        $isValid = $submittedToken !== ''
            && $currentToken !== ''
            && hash_equals($currentToken, $submittedToken);

        return [
            'valid' => $isValid,
            'next_token' => $this->issueCheckoutSubmissionToken(),
        ];
    }
}
