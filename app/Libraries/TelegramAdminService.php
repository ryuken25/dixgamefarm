<?php

namespace App\Libraries;

use App\Models\PembayaranModel;
use App\Models\PesananModel;
use App\Models\ProdukModel;
use App\Models\UserModel;

class TelegramAdminService
{
    private TelegramService $telegramService;
    private TelegramActionTokenService $tokenService;
    private PesananModel $pesananModel;
    private PembayaranModel $pembayaranModel;
    private ProdukModel $produkModel;
    private UserModel $userModel;
    private \CodeIgniter\Database\BaseConnection $db;
    private ?array $adminUserMap = null;

    public function __construct()
    {
        helper(['url']);

        $this->telegramService = new TelegramService();
        $this->tokenService = new TelegramActionTokenService();
        $this->pesananModel = new PesananModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->produkModel = new ProdukModel();
        $this->userModel = new UserModel();
        $this->db = \Config\Database::connect();
    }

    public function fetchUpdates(int $offset = 0, int $timeout = 10): array
    {
        return $this->telegramService->getUpdates($offset, $timeout);
    }

    public function handleUpdate(array $update): void
    {
        if (!empty($update['message']['text'])) {
            $message = $update['message'];
            $chatId = (string) ($message['chat']['id'] ?? '');
            $text = trim((string) ($message['text'] ?? ''));
            if ($chatId !== '' && str_starts_with($text, '/')) {
                $this->handleCommand($chatId, $text);
            }
            return;
        }

        if (!empty($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query']);
        }
    }

    public function notifyPaymentUploaded(int $paymentId, bool $isReupload = false): bool
    {
        if (!$this->telegramService->isEnabled()) {
            return false;
        }

        $payment = $this->pembayaranModel
            ->select('pembayaran.*, pesanan.nomor_invoice, pesanan.grand_total, users.nama_lengkap')
            ->join('pesanan', 'pesanan.id = pembayaran.pesanan_id')
            ->join('users', 'users.id = pesanan.user_id')
            ->where('pembayaran.id', $paymentId)
            ->first();

        if (!$payment) {
            log_message('error', 'TelegramAdminService::notifyPaymentUploaded payment not found: ' . $paymentId);
            return false;
        }

        $invoice = (string) ($payment['nomor_invoice'] ?? '-');
        $customerName = (string) ($payment['nama_lengkap'] ?? 'Pelanggan');
        $grandTotal = 'Rp ' . number_format((float) ($payment['grand_total'] ?? 0), 0, ',', '.');
        $title = $isReupload ? '🔄 Bukti Pembayaran Diperbarui' : '💳 Bukti Pembayaran Diunggah';
        $timestamp = date('d/m/Y H:i:s');

        // Resolve local file path for direct photo upload (works on localhost)
        $localPath = !empty($payment['bukti_bayar'])
            ? FCPATH . ltrim((string) $payment['bukti_bayar'], '/')
            : null;
        $hasFile = $localPath && is_file($localPath);

        // Caption shown under the photo (or as standalone text if no photo)
        $caption = "<b>{$title}</b>\n\n"
            . 'Invoice: <code>' . $this->escape($invoice) . "</code>\n"
            . 'Pelanggan: ' . $this->escape($customerName) . "\n"
            . 'Total: <b>' . $grandTotal . "</b>\n"
            . "Waktu: {$timestamp}";

        $tokensAvailable = $this->db->tableExists('telegram_action_tokens');

        if (!$tokensAvailable) {
            // Table not yet migrated — send photo/text without action buttons
            $caption .= "\n\nSilakan verifikasi di dashboard admin.";
            $anySuccess = false;
            foreach ($this->telegramService->getAuthorizedChatIds() as $chatId) {
                $sent = $hasFile
                    ? $this->telegramService->sendPhotoToChat((string) $chatId, (string) $localPath, $caption, 'HTML')
                    : $this->telegramService->sendMessageToChat((string) $chatId, $caption, 'HTML');
                if ($sent) {
                    $anySuccess = true;
                }
            }
            return $anySuccess;
        }

        $caption .= "\n\nKlik tombol untuk memproses pembayaran ini.";

        $anySuccess = false;
        foreach ($this->telegramService->getAuthorizedChatIds() as $chatId) {
            // Tokens valid 72 hours — message_id is stored so buttons can be
            // removed from ALL admin chats as soon as any admin acts.
            $approveToken = $this->tokenService->createToken('payment_action', (string) $chatId, [
                'payment_id' => (int) $paymentId,
                'decision' => 'approve',
                'message_id' => 0,
            ], 4320, true);

            $rejectToken = $this->tokenService->createToken('payment_action', (string) $chatId, [
                'payment_id' => (int) $paymentId,
                'decision' => 'reject',
                'message_id' => 0,
            ], 4320, true);

            $inlineKeyboard = [
                [
                    ['text' => '✅ Verifikasi', 'callback_data' => $this->tokenService->buildCallbackData($approveToken)],
                    ['text' => '❌ Tolak', 'callback_data' => $this->tokenService->buildCallbackData($rejectToken)],
                ],
            ];

            $options = ['reply_markup' => ['inline_keyboard' => $inlineKeyboard]];
            $sentMsgId = $hasFile
                ? $this->telegramService->sendPhotoToChat((string) $chatId, (string) $localPath, $caption, 'HTML', $options)
                : $this->telegramService->sendMessageGetId((string) $chatId, $caption, 'HTML', $options);

            if ($sentMsgId > 0) {
                $anySuccess = true;
                // Back-fill the real message_id so executePaymentAction can edit this message later
                $this->tokenService->updatePayload($approveToken, ['message_id' => $sentMsgId]);
                $this->tokenService->updatePayload($rejectToken, ['message_id' => $sentMsgId]);
            }
        }

        return $anySuccess;
    }

    private function handleCommand(string $chatId, string $text): void
    {
        if (!$this->telegramService->isAuthorizedChatId($chatId)) {
            $this->telegramService->sendMessageToChat(
                $chatId,
                "⛔ <b>Akses ditolak.</b>\nChat ID Anda belum terdaftar sebagai admin.",
                'HTML'
            );
            return;
        }

        $parts = preg_split('/\s+/', trim($text));
        $command = strtolower((string) array_shift($parts));
        $command = strtok($command, '@') ?: $command;

        try {
            switch ($command) {
                case '/start':
                case '/help':
                case '/info':
                    $this->sendHelp($chatId);
                    return;

                case '/cek':
                    $identifier = trim(implode(' ', $parts));
                    if ($identifier === '') {
                        $this->telegramService->sendMessageToChat(
                            $chatId,
                            "🔍 <b>Cek Status Pesanan</b>\n\nGunakan: <code>/cek INV-YYYYMMDD-XXXX</code>\n\nContoh:\n<code>/cek INV-20260418-1850</code>",
                            'HTML'
                        );
                    } else {
                        $this->handleOrderLookup($chatId, $identifier);
                    }
                    return;

                case '/stats':
                    $scope = strtolower((string) ($parts[0] ?? 'summary'));
                    $scope = in_array($scope, ['summary', 'orders', 'payments', 'stock'], true) ? $scope : 'summary';
                    $this->sendStats($chatId, $scope, 1);
                    return;

                case '/stok':
                    $this->handleStockCommand($chatId, $parts);
                    return;

                case '/laporan':
                    $range = strtolower((string) ($parts[0] ?? '7d'));
                    $this->sendReport($chatId, $this->normalizeRange($range));
                    return;

                default:
                    $this->telegramService->sendMessageToChat(
                        $chatId,
                        "❓ Perintah tidak dikenali.\n\nKetik /help untuk melihat daftar command.",
                        'HTML'
                    );
                    return;
            }
        } catch (\Throwable $e) {
            log_message('error', 'TelegramAdminService command error [' . $command . ']: ' . $e->getMessage());
            $this->telegramService->sendMessageToChat(
                $chatId,
                '⚠️ Terjadi kesalahan saat memproses command. Pastikan migration sudah dijalankan (<code>php spark migrate</code>), lalu coba lagi.',
                'HTML'
            );
        }
    }

    private function handleCallbackQuery(array $callbackQuery): void
    {
        $chatId = (string) ($callbackQuery['from']['id'] ?? '');
        $callbackId = (string) ($callbackQuery['id'] ?? '');
        $callbackData = (string) ($callbackQuery['data'] ?? '');
        $messageId = (int) ($callbackQuery['message']['message_id'] ?? 0);

        if (!$this->telegramService->isAuthorizedChatId($chatId)) {
            $this->telegramService->answerCallbackQuery($callbackId, 'Akses ditolak', true);
            return;
        }

        // ── nav:menu — no token needed, just navigation ───────────────────────
        if ($callbackData === 'nav:menu') {
            $this->sendHelp($chatId, $messageId);
            $this->telegramService->answerCallbackQuery($callbackId, '');
            return;
        }

        $consumed = $this->tokenService->consumeCallbackData($callbackData, $chatId);
        if (!$consumed['success']) {
            $this->telegramService->answerCallbackQuery($callbackId, $consumed['message'] ?? 'Aksi tidak valid', true);
            return;
        }

        $payload = $consumed['payload'];
        $actionType = $consumed['action_type'];

        switch ($actionType) {
            case 'help_action':
                $type = (string) ($payload['type'] ?? 'summary');
                if ($type === 'stock_low') {
                    $this->sendStockList($chatId, true, 1, $messageId);
                } elseif ($type === 'report_7d') {
                    $this->sendReport($chatId, '7d', $messageId);
                } else {
                    $this->sendStats($chatId, 'summary', 1, $messageId);
                }
                $this->telegramService->answerCallbackQuery($callbackId, '');
                return;

            case 'stats_page':
                $this->sendStats(
                    $chatId,
                    (string) ($payload['scope'] ?? 'summary'),
                    max(1, (int) ($payload['page'] ?? 1)),
                    $messageId
                );
                $this->telegramService->answerCallbackQuery($callbackId, '');
                return;

            case 'stock_list_page':
                $this->sendStockList(
                    $chatId,
                    !empty($payload['low_only']),
                    max(1, (int) ($payload['page'] ?? 1)),
                    $messageId
                );
                $this->telegramService->answerCallbackQuery($callbackId, '');
                return;

            case 'stock_mutation_confirm':
                $result = $this->executeStockMutation($chatId, $payload);
                $this->telegramService->answerCallbackQuery($callbackId, $result['message'] ?? 'Aksi diproses', !$result['success']);
                return;

            case 'stock_mutation_cancel':
                $this->sendOrEdit($chatId, $messageId, 'Perubahan stok dibatalkan.', 'HTML', []);
                $this->telegramService->answerCallbackQuery($callbackId, 'Dibatalkan');
                return;

            case 'report_range':
                $this->sendReport($chatId, $this->normalizeRange((string) ($payload['range'] ?? '7d')), $messageId);
                $this->telegramService->answerCallbackQuery($callbackId, '');
                return;

            case 'cek_order':
                // Triggered by per-row button in payment list — always sends NEW message
                // (detail may include a photo so we cannot editMessageText here)
                $invoice = (string) ($payload['invoice'] ?? '');
                if ($invoice !== '') {
                    $this->telegramService->answerCallbackQuery($callbackId, '');
                    $this->handleOrderLookup($chatId, $invoice);
                } else {
                    $this->telegramService->answerCallbackQuery($callbackId, 'Invoice tidak valid', true);
                }
                return;

            case 'payment_action':
                $result = $this->executePaymentAction($chatId, $payload, $messageId);
                $toast = $result['success']
                    ? ($payload['decision'] === 'approve' ? '✅ Pembayaran diverifikasi!' : '❌ Pembayaran ditolak!')
                    : ($result['message'] ?? 'Aksi gagal');
                $this->telegramService->answerCallbackQuery($callbackId, $toast, !$result['success']);
                return;

            default:
                $this->telegramService->answerCallbackQuery($callbackId, 'Aksi tidak didukung', true);
                return;
        }
    }

    private function sendHelp(string $chatId, int $editMessageId = 0): void
    {
        $sep = '━━━━━━━━━━━━━━━━━━━━━━━━';

        $message = "📘 <b>DIX Game Farm — Admin Bot</b>\n\n"
            . "🐓 Sistem Pemesanan dan Manajemen Stok Ayam\n"
            . "Akses khusus admin terdaftar.\n\n"
            . "{$sep}\n"
            . "🔍 <b>CEK PESANAN</b>\n"
            . "<code>/cek INV-YYYYMMDD-XXXX</code>\n"
            . "<i>Lihat detail, bukti bayar, dan tracking progress</i>\n\n"
            . "📊 <b>STATISTIK</b>\n"
            . "• <code>/stats</code> — Ringkasan operasional hari ini\n"
            . "• <code>/stats orders</code> — 5 pesanan terbaru\n"
            . "• <code>/stats payments</code> — Pending verifikasi\n"
            . "• <code>/stats stock</code> — Stok kritis\n\n"
            . "📈 <b>LAPORAN</b>\n"
            . "• <code>/laporan 1d|3d|7d|30d</code> — Omzet dan ringkasan\n\n"
            . "📦 <b>MANAJEMEN STOK</b>\n"
            . "• <code>/stok list</code> — Semua produk\n"
            . "• <code>/stok list low</code> — Stok rendah\n"
            . "• <code>/stok cek [id/nama]</code> — Cek stok produk\n"
            . "• <code>/stok tambah [id] [jml]</code>\n"
            . "• <code>/stok kurang [id] [jml]</code>\n"
            . "• <code>/stok set [id] [jml]</code>\n"
            . "{$sep}";

        // Quick-access buttons — reuse existing token types
        $statsToken = $this->tokenService->createToken('stats_page', $chatId, ['scope' => 'summary', 'page' => 1], 120, false);
        $paymentsToken = $this->tokenService->createToken('stats_page', $chatId, ['scope' => 'payments', 'page' => 1], 120, false);
        $stockToken = $this->tokenService->createToken('stock_list_page', $chatId, ['low_only' => 1, 'page' => 1], 120, false);
        $reportToken = $this->tokenService->createToken('report_range', $chatId, ['range' => '7d'], 120, false);

        $this->sendOrEdit($chatId, $editMessageId, $message, 'HTML', [
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        ['text' => '📊 Stats Hari Ini', 'callback_data' => $this->tokenService->buildCallbackData($statsToken)],
                        ['text' => '💳 Pending Bayar', 'callback_data' => $this->tokenService->buildCallbackData($paymentsToken)],
                    ],
                    [
                        ['text' => '📉 Stok Rendah', 'callback_data' => $this->tokenService->buildCallbackData($stockToken)],
                        ['text' => '📈 Laporan 7 Hari', 'callback_data' => $this->tokenService->buildCallbackData($reportToken)],
                    ],
                ],
            ],
        ]);
    }

    private function sendStats(string $chatId, string $scope = 'summary', int $page = 1, int $editMessageId = 0): void
    {
        if ($scope === 'orders') {
            $this->sendOrderStatsPage($chatId, $page, $editMessageId);
            return;
        }

        if ($scope === 'payments') {
            $this->sendPaymentStatsPage($chatId, $page, $editMessageId);
            return;
        }

        if ($scope === 'stock') {
            $this->sendStockList($chatId, false, $page, $editMessageId);
            return;
        }

        $todayStart = date('Y-m-d 00:00:00');
        $todayEnd = date('Y-m-d 23:59:59');

        $totalToday = $this->pesananModel->where('tanggal_pesanan >=', $todayStart)->where('tanggal_pesanan <=', $todayEnd)->countAllResults();
        $menungguBayar = $this->pesananModel->whereIn('status_pesanan', ['MENUNGGU_BAYAR', 'MENUNGGU_VERIFIKASI'])->countAllResults();
        $diproses = $this->pesananModel->where('status_pesanan', 'DIPROSES')->countAllResults();
        $pesananSiap = $this->pesananModel->where('status_pesanan', 'PESANAN_SIAP')->countAllResults();
        $dikirim = $this->pesananModel->where('status_pesanan', 'DIKIRIM')->countAllResults();
        $selesai = $this->pesananModel->where('status_pesanan', 'SELESAI')->countAllResults();
        $batal = $this->pesananModel->where('status_pesanan', 'BATAL')->countAllResults();
        $pendingPayments = $this->pembayaranModel->where('status_pembayaran', 'PENDING')->countAllResults();
        $stokKritis = $this->produkModel->getLowStockCount(10);

        $ordersToken = $this->tokenService->createToken('stats_page', $chatId, ['scope' => 'orders', 'page' => 1], 120, false);
        $paymentsToken = $this->tokenService->createToken('stats_page', $chatId, ['scope' => 'payments', 'page' => 1], 120, false);
        $stockToken = $this->tokenService->createToken('stats_page', $chatId, ['scope' => 'stock', 'page' => 1], 120, false);

        $sep = '━━━━━━━━━━━━━━━━━━━━━━━━';
        $now = date('d M Y', time()) . ' • ' . date('H:i', time()) . ' WITA';

        $message = "📊 <b>DIX Game Farm — Statistik Operasional</b>\n"
            . "<i>{$now}</i>\n\n"
            . "{$sep}\n"
            . "📦 <b>Pesanan hari ini: {$totalToday}</b>\n"
            . "{$sep}\n"
            . "⏳ Menunggu bayar       <b>{$menungguBayar}</b>\n"
            . "⚙️  Diproses             <b>{$diproses}</b>\n"
            . "🏪 Pesanan siap         <b>{$pesananSiap}</b>\n"
            . "🚀 Dikirim              <b>{$dikirim}</b>\n"
            . "✅ Selesai              <b>{$selesai}</b>\n"
            . "❌ Dibatalkan           <b>{$batal}</b>\n\n"
            . "{$sep}\n"
            . "💳 Pending verifikasi   <b>{$pendingPayments}</b>\n"
            . ($stokKritis > 0
                ? "⚠️  Stok kritis          <b>{$stokKritis}</b> produk\n"
                : "✅ Stok aman\n")
            . "{$sep}\n\n"
            . "Ketuk tombol untuk detail:";

        $this->sendOrEdit($chatId, $editMessageId, $message, 'HTML', [
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        ['text' => '📦 Pesanan Terbaru', 'callback_data' => $this->tokenService->buildCallbackData($ordersToken)],
                        ['text' => '💳 Pending Verifikasi', 'callback_data' => $this->tokenService->buildCallbackData($paymentsToken)],
                    ],
                    [
                        ['text' => '📦 Semua Stok', 'callback_data' => $this->tokenService->buildCallbackData($stockToken)],
                    ],
                    [
                        ['text' => '🔙 Menu Utama', 'callback_data' => 'nav:menu'],
                    ],
                ],
            ],
        ]);
    }

    private function sendOrderStatsPage(string $chatId, int $page, int $editMessageId = 0): void
    {
        $perPage = 5;
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $orders = $this->pesananModel->builder()
            ->select('pesanan.nomor_invoice, pesanan.status_pesanan, pesanan.tanggal_pesanan, COALESCE(pesanan.nama_penerima, users.nama_lengkap) AS nama_pelanggan', false)
            ->join('users', 'users.id = pesanan.user_id', 'left')
            ->orderBy('pesanan.tanggal_pesanan', 'DESC')
            ->limit($perPage, $offset)
            ->get()
            ->getResultArray();

        $total = $this->pesananModel->countAllResults();
        $sep = '━━━━━━━━━━━━━━━━━━━━━━━━';
        $message = "📦 <b>Pesanan Terbaru</b> — Hal. {$page}\n{$sep}\n\n";

        if ($orders === []) {
            $message .= 'Belum ada data pesanan.';
        } else {
            foreach ($orders as $idx => $order) {
                $num = $offset + $idx + 1;
                $status = $this->labelOrderStatus((string) $order['status_pesanan']);
                $tgl = date('d/m H:i', strtotime((string) $order['tanggal_pesanan']));
                $message .= "{$num}. <code>" . $this->escape((string) $order['nomor_invoice']) . "</code>\n"
                    . "    👤 " . $this->escape((string) $order['nama_pelanggan']) . "\n"
                    . "    📌 {$status} • {$tgl} WITA\n\n";
            }
        }

        $this->sendOrEdit($chatId, $editMessageId, trim($message), 'HTML', [
            'reply_markup' => $this->buildPaginationMarkup($chatId, 'stats_page', ['scope' => 'orders'], $page, $perPage, $total),
        ]);
    }

    private function sendPaymentStatsPage(string $chatId, int $page, int $editMessageId = 0): void
    {
        $perPage = 5;
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $payments = $this->pembayaranModel->getPendingPayments();
        $total = count($payments);
        $slice = array_slice($payments, $offset, $perPage);

        $sep = '━━━━━━━━━━━━━━━━━━━━━━━━';
        $message = "💳 <b>Pending Verifikasi</b> — Hal. {$page}\n{$sep}\n\n";

        $cekButtons = [];
        if ($slice === []) {
            $message .= 'Tidak ada pembayaran pending.';
        } else {
            foreach ($slice as $idx => $payment) {
                $num = $offset + $idx + 1;
                $tgl = date('d/m H:i', strtotime((string) $payment['tanggal_upload']));
                $nom = 'Rp ' . number_format((float) $payment['nominal_bayar'], 0, ',', '.');
                $invoice = (string) $payment['nomor_invoice'];
                $name = $this->escape((string) $payment['nama_lengkap']);
                $message .= "{$num}. <code>" . $this->escape($invoice) . "</code>\n"
                    . "    👤 {$name} • <b>{$nom}</b>\n"
                    . "    🕐 Upload: {$tgl} WITA\n\n";

                // Clickable button to jump directly to order detail
                $cekToken = $this->tokenService->createToken('cek_order', $chatId, ['invoice' => $invoice], 120, false);
                $cekButtons[] = [
                    [
                        'text' => "🔍 #{$num} Lihat — " . $invoice,
                        'callback_data' => $this->tokenService->buildCallbackData($cekToken),
                    ]
                ];
            }
        }

        // Build keyboard: per-row cek buttons → pagination → back
        $keyboard = $cekButtons;
        $totalPages = max(1, (int) ceil($total / max(1, $perPage)));
        $navRow = [];
        if ($page > 1) {
            $prevToken = $this->tokenService->createToken('stats_page', $chatId, ['scope' => 'payments', 'page' => $page - 1], 120, false);
            $navRow[] = ['text' => '◀ Prev', 'callback_data' => $this->tokenService->buildCallbackData($prevToken)];
        }
        if ($page < $totalPages) {
            $nextToken = $this->tokenService->createToken('stats_page', $chatId, ['scope' => 'payments', 'page' => $page + 1], 120, false);
            $navRow[] = ['text' => 'Next ▶', 'callback_data' => $this->tokenService->buildCallbackData($nextToken)];
        }
        if ($navRow !== []) {
            $keyboard[] = $navRow;
        }
        $keyboard[] = [['text' => '🔙 Menu Utama', 'callback_data' => 'nav:menu']];

        $this->sendOrEdit($chatId, $editMessageId, trim($message), 'HTML', [
            'reply_markup' => ['inline_keyboard' => $keyboard],
        ]);
    }

    private function handleStockCommand(string $chatId, array $parts): void
    {
        $sub = strtolower((string) ($parts[0] ?? ''));

        if ($sub === 'list') {
            $lowOnly = strtolower((string) ($parts[1] ?? '')) === 'low';
            $this->sendStockList($chatId, $lowOnly, 1);
            return;
        }

        if ($sub === 'cek') {
            $identifier = trim(implode(' ', array_slice($parts, 1)));
            $this->sendStockCheck($chatId, $identifier);
            return;
        }

        if (in_array($sub, ['tambah', 'kurang', 'set'], true)) {
            $this->prepareStockMutation($chatId, $sub, array_slice($parts, 1));
            return;
        }

        $this->telegramService->sendMessageToChat($chatId, 'Format command stok tidak valid. Gunakan /help untuk melihat contoh.', 'HTML');
    }

    private function sendStockList(string $chatId, bool $lowOnly, int $page, int $editMessageId = 0): void
    {
        $perPage = 5;
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $builder = $this->produkModel->builder()
            ->select('id, nama_ayam, stok_tersedia, stok_dibooking, is_preorder, is_active')
            ->where('is_active', 1)
            ->orderBy('stok_tersedia', 'ASC')
            ->orderBy('nama_ayam', 'ASC');

        if ($lowOnly) {
            $builder->where('stok_tersedia <=', 10);
        }

        $allRows = $builder->get()->getResultArray();
        $total = count($allRows);
        $slice = array_slice($allRows, $offset, $perPage);
        $sep = '━━━━━━━━━━━━━━━━━━━━━━━━';
        $title = $lowOnly ? '📉 Stok Rendah' : '📦 Stok Produk';
        $message = "<b>{$title}</b> — Hal. {$page}\n{$sep}\n\n";

        if ($slice === []) {
            $message .= 'Tidak ada data stok yang cocok.';
        } else {
            foreach ($slice as $idx => $product) {
                $num = $offset + $idx + 1;
                $avail = (int) $product['stok_tersedia'];
                $book = (int) $product['stok_dibooking'];
                $mode = !empty($product['is_preorder']) ? '🔖 Pre-Order' : '🟢 Siap jual';
                $warn = $avail <= 5 ? ' ⚠️' : '';
                $message .= "{$num}. <b>" . $this->escape((string) $product['nama_ayam']) . "</b>{$warn}\n"
                    . "    ID #{$product['id']} • Tersedia: <b>{$avail}</b> • Dibooking: <b>{$book}</b>\n"
                    . "    {$mode}\n\n";
            }
        }

        $this->sendOrEdit($chatId, $editMessageId, trim($message), 'HTML', [
            'reply_markup' => $this->buildPaginationMarkup($chatId, 'stock_list_page', ['low_only' => $lowOnly ? 1 : 0], $page, $perPage, $total),
        ]);
    }

    private function sendStockCheck(string $chatId, string $identifier): void
    {
        $matches = $this->produkModel->findByTelegramIdentifier($identifier, 5);
        if ($matches === []) {
            $this->telegramService->sendMessageToChat($chatId, 'Produk tidak ditemukan. Gunakan ID produk atau nama yang lebih spesifik.', 'HTML');
            return;
        }

        $message = "<b>🔎 Hasil Cek Stok</b>\n\n";
        foreach ($matches as $product) {
            $message .= '#' . $product['id'] . ' ' . $this->escape((string) $product['nama_ayam']) . "\n"
                . 'Stok tersedia: <b>' . (int) $product['stok_tersedia'] . '</b> • Dibooking: <b>' . (int) $product['stok_dibooking'] . "</b>\n"
                . 'Mode: ' . (!empty($product['is_preorder']) ? 'Preorder' : 'Stok siap jual') . "\n\n";
        }

        $this->telegramService->sendMessageToChat($chatId, trim($message), 'HTML');
    }

    private function prepareStockMutation(string $chatId, string $subCommand, array $args): void
    {
        if (count($args) < 2) {
            $this->telegramService->sendMessageToChat($chatId, 'Format command stok belum lengkap. Contoh: /stok tambah 5 10', 'HTML');
            return;
        }

        $amount = (int) array_pop($args);
        $identifier = trim(implode(' ', $args));

        if ($identifier === '' || $amount < 0) {
            $this->telegramService->sendMessageToChat($chatId, 'Identifier produk atau jumlah stok tidak valid.', 'HTML');
            return;
        }

        $matches = $this->produkModel->findByTelegramIdentifier($identifier, 5);
        if ($matches === []) {
            $this->telegramService->sendMessageToChat($chatId, 'Produk tidak ditemukan. Gunakan ID produk atau nama yang lebih spesifik.', 'HTML');
            return;
        }

        if (count($matches) > 1) {
            $message = "<b>Produk tidak spesifik</b>\nGunakan ID produk berikut agar mutasi stok aman:\n\n";
            foreach ($matches as $product) {
                $message .= '#' . $product['id'] . ' ' . $this->escape((string) $product['nama_ayam']) . ' • Stok: ' . (int) $product['stok_tersedia'] . "\n";
            }
            $this->telegramService->sendMessageToChat($chatId, trim($message), 'HTML');
            return;
        }

        $product = $matches[0];
        $mode = $subCommand === 'kurang' ? 'subtract' : ($subCommand === 'tambah' ? 'add' : 'set');
        $projected = $mode === 'set'
            ? $amount
            : ($mode === 'add' ? ((int) $product['stok_tersedia'] + $amount) : ((int) $product['stok_tersedia'] - $amount));

        if ($projected < 0) {
            $this->telegramService->sendMessageToChat($chatId, 'Mutasi stok ditolak karena hasil akhir akan menjadi negatif.', 'HTML');
            return;
        }

        $confirmToken = $this->tokenService->createToken('stock_mutation_confirm', $chatId, [
            'product_id' => (int) $product['id'],
            'mode' => $mode,
            'amount' => $amount,
        ], 30, true);

        $cancelToken = $this->tokenService->createToken('stock_mutation_cancel', $chatId, [
            'product_id' => (int) $product['id'],
        ], 30, true);

        $message = "<b>Konfirmasi Mutasi Stok</b>\n\n"
            . 'Produk: <b>' . $this->escape((string) $product['nama_ayam']) . "</b> (#{$product['id']})\n"
            . 'Stok sebelum: <b>' . (int) $product['stok_tersedia'] . "</b>\n"
            . 'Aksi: <b>' . $this->labelStockMode($mode) . "</b> {$amount}\n"
            . 'Stok sesudah: <b>' . $projected . '</b>\n\n'
            . 'Tekan tombol konfirmasi untuk commit perubahan.';

        $this->telegramService->sendMessageToChat($chatId, $message, 'HTML', [
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        [
                            'text' => 'Konfirmasi',
                            'callback_data' => $this->tokenService->buildCallbackData($confirmToken),
                        ],
                        [
                            'text' => 'Batal',
                            'callback_data' => $this->tokenService->buildCallbackData($cancelToken),
                        ],
                    ]
                ],
            ],
        ]);
    }

    private function executeStockMutation(string $chatId, array $payload): array
    {
        $adminId = $this->resolveAdminUserId($chatId);
        if ($adminId === null) {
            $message = 'Aksi stok membutuhkan mapping admin Telegram ke user admin sistem.';
            $this->telegramService->sendMessageToChat($chatId, $message, 'HTML');
            return ['success' => false, 'message' => $message];
        }

        $result = $this->produkModel->mutateAvailableStock(
            (int) ($payload['product_id'] ?? 0),
            (string) ($payload['mode'] ?? ''),
            (int) ($payload['amount'] ?? 0)
        );

        if (!$result['success']) {
            $this->telegramService->sendMessageToChat($chatId, $result['message'] ?? 'Gagal memperbarui stok.', 'HTML');
            return $result;
        }

        $timestamp = date('d/m/Y H:i:s');
        $admin = $this->userModel->find($adminId);

        $message = "<b>✅ Stok berhasil diperbarui</b>\n\n"
            . 'Produk: <b>' . $this->escape((string) ($result['product']['nama_ayam'] ?? '-')) . "</b>\n"
            . 'Stok sebelum: <b>' . (int) ($result['old_stock'] ?? 0) . "</b>\n"
            . 'Stok sesudah: <b>' . (int) ($result['new_stock'] ?? 0) . "</b>\n"
            . 'Waktu update: ' . $timestamp . "\n"
            . 'Admin: ' . $this->escape((string) ($admin['nama_lengkap'] ?? 'Admin')) . "\n";

        $this->telegramService->sendMessageToChat($chatId, $message, 'HTML');
        return ['success' => true, 'message' => 'Stok berhasil diperbarui'];
    }

    private function sendReport(string $chatId, string $range, int $editMessageId = 0): void
    {
        [$label, $startDateTime, $endDateTime] = $this->resolveRangeWindow($range);

        $ordersBuilder = $this->pesananModel->builder();
        $orderSummary = $ordersBuilder
            ->select('COUNT(*) AS total_order,
                SUM(CASE WHEN status_pesanan = "SELESAI" THEN grand_total ELSE 0 END) AS omzet,
                SUM(CASE WHEN status_pesanan = "SELESAI" THEN 1 ELSE 0 END) AS selesai,
                SUM(CASE WHEN status_pesanan = "BATAL" THEN 1 ELSE 0 END) AS batal,
                SUM(CASE WHEN status_pesanan IN ("MENUNGGU_BAYAR","MENUNGGU_VERIFIKASI") THEN 1 ELSE 0 END) AS pending_payment', false)
            ->where('tanggal_pesanan >=', $startDateTime)
            ->where('tanggal_pesanan <=', $endDateTime)
            ->get()
            ->getRowArray();

        $items = $this->db->table('detail_pesanan')
            ->select('COALESCE(SUM(detail_pesanan.jumlah), 0) AS total_item', false)
            ->join('pesanan', 'pesanan.id = detail_pesanan.pesanan_id')
            ->where('pesanan.tanggal_pesanan >=', $startDateTime)
            ->where('pesanan.tanggal_pesanan <=', $endDateTime)
            ->where('pesanan.status_pesanan', 'SELESAI')
            ->get()
            ->getRowArray();

        $topProducts = $this->db->table('detail_pesanan')
            ->select('produk.nama_ayam, SUM(detail_pesanan.jumlah) AS total_terjual', false)
            ->join('pesanan', 'pesanan.id = detail_pesanan.pesanan_id')
            ->join('produk', 'produk.id = detail_pesanan.produk_id')
            ->where('pesanan.tanggal_pesanan >=', $startDateTime)
            ->where('pesanan.tanggal_pesanan <=', $endDateTime)
            ->where('pesanan.status_pesanan', 'SELESAI')
            ->groupBy('detail_pesanan.produk_id')
            ->orderBy('total_terjual', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();

        $rangeLabels = ['1d' => '📅 1 Hari', '3d' => '📅 3 Hari', '7d' => '📅 7 Hari', '30d' => '📅 30 Hari'];
        $rangeButtons = [];
        foreach (['1d', '3d', '7d', '30d'] as $buttonRange) {
            $token = $this->tokenService->createToken('report_range', $chatId, ['range' => $buttonRange], 120, false);
            $rangeButtons[] = [
                'text' => $rangeLabels[$buttonRange] ?? strtoupper($buttonRange),
                'callback_data' => $this->tokenService->buildCallbackData($token),
            ];
        }

        $sep = '━━━━━━━━━━━━━━━━━━━━━━━━';
        $omzet = 'Rp ' . number_format((float) ($orderSummary['omzet'] ?? 0), 0, ',', '.');
        $totalOrd = (int) ($orderSummary['total_order'] ?? 0);
        $totalItem = (int) ($items['total_item'] ?? 0);
        $selesai = (int) ($orderSummary['selesai'] ?? 0);
        $batal = (int) ($orderSummary['batal'] ?? 0);
        $pending = (int) ($orderSummary['pending_payment'] ?? 0);
        $dateRange = date('d M', strtotime($startDateTime)) . ' – ' . date('d M Y', strtotime($endDateTime));

        $message = "📈 <b>DIX Game Farm — Laporan {$label}</b>\n"
            . "<i>{$dateRange}</i>\n\n"
            . "{$sep}\n"
            . "💰 Omzet (order selesai)  <b>{$omzet}</b>\n"
            . "📦 Total order            <b>{$totalOrd}</b>\n"
            . "🛒 Item terjual           <b>{$totalItem}</b>\n"
            . "{$sep}\n"
            . "✅ Order selesai          <b>{$selesai}</b>\n"
            . "❌ Order batal            <b>{$batal}</b>\n"
            . "⏳ Pending payment        <b>{$pending}</b>\n"
            . "{$sep}\n"
            . "🏆 <b>TOP PRODUK</b>\n"
            . "{$sep}\n";

        if ($topProducts === []) {
            $message .= "Belum ada produk terjual pada periode ini.";
        } else {
            foreach ($topProducts as $index => $product) {
                $medal = match ($index) { 0 => '🥇', 1 => '🥈', 2 => '🥉', default => ($index + 1) . '.'};
                $message .= "{$medal} " . $this->escape((string) $product['nama_ayam']) . " — <b>" . (int) $product['total_terjual'] . " item</b>\n";
            }
        }

        $this->sendOrEdit($chatId, $editMessageId, trim($message), 'HTML', [
            'reply_markup' => [
                'inline_keyboard' => [
                    $rangeButtons,
                    [['text' => '🔙 Menu Utama', 'callback_data' => 'nav:menu']],
                ],
            ],
        ]);
    }

    private function executePaymentAction(string $chatId, array $payload, int $notifMessageId = 0): array
    {
        $adminId = $this->resolveAdminUserId($chatId);
        if ($adminId === null) {
            $this->telegramService->sendMessageToChat(
                $chatId,
                "⚠️ Aksi tidak dapat diproses.\n\nIsi <code>TELEGRAM_ADMIN_USER_MAP</code> di file <code>.env</code> dengan format:\n<code>chatId:userId,chatId2:userId2</code>",
                'HTML'
            );
            return ['success' => false, 'message' => 'TELEGRAM_ADMIN_USER_MAP belum dikonfigurasi'];
        }

        $paymentId = (int) ($payload['payment_id'] ?? 0);
        $decision = (string) ($payload['decision'] ?? '');
        $payment = $this->pembayaranModel->find($paymentId);

        if (!$payment) {
            return ['success' => false, 'message' => 'Pembayaran tidak ditemukan'];
        }

        $currentStatus = (string) ($payment['status_pembayaran'] ?? '');

        if ($currentStatus !== 'PENDING') {
            // Already processed — edit THIS admin's message to reflect actual status
            $doneLabel = $currentStatus === 'VALID' ? '✅ sudah DIVERIFIKASI' : '❌ sudah DITOLAK';
            $this->editAllPaymentNotifications(
                $paymentId,
                "💳 <b>Pembayaran {$doneLabel}</b>\n\n<i>Sudah diproses sebelumnya.</i>"
            );
            return ['success' => false, 'message' => 'Pembayaran sudah diproses sebelumnya'];
        }

        $isValid = $decision === 'approve';
        if (!$isValid && $decision !== 'reject') {
            return ['success' => false, 'message' => 'Keputusan tidak valid'];
        }

        $result = $this->pembayaranModel->verifyPayment(
            $paymentId,
            $adminId,
            $isValid,
            $isValid ? null : 'Ditolak via Telegram Admin'
        );

        if (!empty($result['success'])) {
            $pesanan = $this->pesananModel->find($payment['pesanan_id']);
            $invoice = $this->escape((string) ($pesanan['nomor_invoice'] ?? '-'));
            $adminUser = $this->userModel->find($adminId);
            $adminName = $this->escape((string) ($adminUser['nama_lengkap'] ?? 'Admin'));
            $waktu = date('d/m/Y H:i', time()) . ' WITA';
            $label = $isValid ? '✅ DIVERIFIKASI' : '❌ DITOLAK';

            $resultText = "💳 <b>Pembayaran {$label}</b>\n\n"
                . "Invoice : <code>{$invoice}</code>\n"
                . "Oleh    : {$adminName}\n"
                . "Waktu   : {$waktu}";

            // Remove buttons from ALL admin notification messages immediately
            $this->editAllPaymentNotifications($paymentId, $resultText);
        } else {
            $errMsg = $this->escape($result['message'] ?? 'Terjadi kesalahan');
            $this->telegramService->sendMessageToChat($chatId, "⚠️ Gagal: <b>{$errMsg}</b>", 'HTML');
        }

        return [
            'success' => !empty($result['success']),
            'message' => $result['message'] ?? ($isValid ? 'Pembayaran berhasil diverifikasi' : 'Pembayaran berhasil ditolak'),
        ];
    }

    private function editAllPaymentNotifications(int $paymentId, string $resultText): void
    {
        $tokens = $this->tokenService->getActivePaymentTokens($paymentId);
        $edited = [];

        foreach ($tokens as $token) {
            $p = json_decode((string) ($token['payload_json'] ?? '{}'), true);
            $msgId = (int) ($p['message_id'] ?? 0);
            $adminChatId = (string) $token['chat_id'];
            $key = $adminChatId . ':' . $msgId;

            if ($msgId > 0 && !in_array($key, $edited, true)) {
                $this->telegramService->editMessageText($adminChatId, $msgId, $resultText, 'HTML', []);
                $edited[] = $key;
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUBLIC UTILITIES
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Register bot command list with Telegram (shows in "/" menu).
     * Call once when the polling loop starts.
     */
    public function setupBotCommands(): void
    {
        $commands = [
            ['command' => 'help', 'description' => 'Bantuan dan menu utama'],
            ['command' => 'cek', 'description' => 'Cek status pesanan: /cek INV-YYYYMMDD-XXXX'],
            ['command' => 'stats', 'description' => 'Statistik operasional'],
            ['command' => 'laporan', 'description' => 'Laporan penjualan: /laporan 1d|3d|7d|30d'],
            ['command' => 'stok', 'description' => 'Manajemen stok produk'],
        ];
        $this->telegramService->setMyCommands($commands);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // /cek — ORDER LOOKUP
    // ─────────────────────────────────────────────────────────────────────────

    private function handleOrderLookup(string $chatId, string $identifier): void
    {
        $identifier = strtoupper(trim($identifier));

        $order = $this->pesananModel->builder()
            ->select(
                'pesanan.*,'
                . ' COALESCE(pesanan.nama_penerima, users.nama_lengkap) AS nama_pelanggan,'
                . ' COALESCE(pesanan.no_hp_penerima, users.no_hp) AS no_hp_pelanggan',
                false
            )
            ->join('users', 'users.id = pesanan.user_id', 'left')
            ->where('pesanan.nomor_invoice', $identifier)
            ->get()
            ->getRowArray();

        if (!$order) {
            $this->telegramService->sendMessageToChat(
                $chatId,
                "❌ <b>Pesanan tidak ditemukan</b>\n\n"
                . "Invoice <code>" . $this->escape($identifier) . "</code> tidak ada di sistem.\n\n"
                . "Pastikan format benar:\n<code>INV-YYYYMMDD-XXXX</code>",
                'HTML'
            );
            return;
        }

        $latestPayment = $this->pembayaranModel
            ->where('pesanan_id', $order['id'])
            ->orderBy('created_at', 'DESC')
            ->first();

        $items = $this->db->table('detail_pesanan')
            ->select(
                'detail_pesanan.jumlah, detail_pesanan.harga_satuan_snapshot,'
                . ' detail_pesanan.subtotal, detail_pesanan.is_preorder_item, produk.nama_ayam',
                false
            )
            ->join('produk', 'produk.id = detail_pesanan.produk_id', 'left')
            ->where('detail_pesanan.pesanan_id', $order['id'])
            ->get()
            ->getResultArray();

        $this->sendOrderDetail($chatId, $order, $latestPayment, $items);
    }

    private function sendOrderDetail(string $chatId, array $order, ?array $latestPayment, array $items): void
    {
        $sep = '━━━━━━━━━━━━━━━━━━━━━━━━';
        $invoice = $this->escape((string) ($order['nomor_invoice'] ?? '-'));
        $customer = $this->escape((string) ($order['nama_pelanggan'] ?? 'Pelanggan'));
        $hp = $this->escape((string) ($order['no_hp_pelanggan'] ?? '-'));
        $total = 'Rp ' . number_format((float) ($order['grand_total'] ?? 0), 0, ',', '.');
        $tanggal = !empty($order['tanggal_pesanan'])
            ? date('d/m/Y H:i', strtotime($order['tanggal_pesanan'])) . ' WITA'
            : '-';
        $tipe = ($order['tipe_pengiriman'] ?? '') === 'DIKIRIM_KURIR'
            ? '🚚 Dikirim Kurir'
            : '🏪 Ambil Sendiri';
        $orderStatus = (string) ($order['status_pesanan'] ?? '');
        $paymentStatus = $latestPayment ? (string) ($latestPayment['status_pembayaran'] ?? '') : '';

        // ── Header ──────────────────────────────────────────────────────────
        $header = "🔍 <b>Detail Pesanan</b>\n\n"
            . "Invoice : <code>{$invoice}</code>\n"
            . "Pelanggan: <b>{$customer}</b>\n"
            . "HP       : {$hp}\n"
            . "Total    : <b>{$total}</b>\n"
            . "Dibuat   : {$tanggal}\n"
            . "Pengiriman: {$tipe}";

        // ── Item list ────────────────────────────────────────────────────────
        if ($items !== []) {
            $header .= "\n\n{$sep}\n📦 <b>ITEM PESANAN</b>\n{$sep}\n";
            foreach ($items as $item) {
                $qty = (int) ($item['jumlah'] ?? 0);
                $price = 'Rp ' . number_format((float) ($item['harga_satuan_snapshot'] ?? 0), 0, ',', '.');
                $sub = 'Rp ' . number_format((float) ($item['subtotal'] ?? 0), 0, ',', '.');
                $name = $this->escape((string) ($item['nama_ayam'] ?? 'Produk'));
                $pre = !empty($item['is_preorder_item']) ? ' <i>[Pre-Order]</i>' : '';
                $header .= "• {$name}{$pre}\n  {$qty} pcs × {$price} = <b>{$sub}</b>\n";
            }
        }

        // ── Case: proof uploaded, awaiting validation ────────────────────────
        if ($orderStatus === 'MENUNGGU_BAYAR' && $paymentStatus === 'PENDING') {
            $uploadTime = !empty($latestPayment['tanggal_upload'])
                ? date('d/m/Y H:i', strtotime($latestPayment['tanggal_upload'])) . ' WITA'
                : '-';
            $bankFrom = $this->escape((string) ($latestPayment['nama_bank'] ?? '-'));
            $bankTo = $this->escape((string) ($latestPayment['bank_tujuan'] ?? '-'));

            $paymentBlock = "\n{$sep}\n💳 <b>STATUS PEMBAYARAN</b>\n{$sep}\n"
                . "Status  : ⏳ <b>Menunggu Validasi Admin</b>\n"
                . "Upload  : {$uploadTime}\n"
                . "Bank    : {$bankFrom} → {$bankTo}\n"
                . "Nominal : <b>{$total}</b>\n\n"
                . "Ketuk tombol untuk memproses:";

            $approveToken = $this->tokenService->createToken('payment_action', $chatId, [
                'payment_id' => (int) $latestPayment['id'],
                'decision' => 'approve',
            ], 1440, true);
            $rejectToken = $this->tokenService->createToken('payment_action', $chatId, [
                'payment_id' => (int) $latestPayment['id'],
                'decision' => 'reject',
            ], 1440, true);
            $buttons = [
                'inline_keyboard' => [
                    [
                        ['text' => '✅ Verifikasi', 'callback_data' => $this->tokenService->buildCallbackData($approveToken)],
                        ['text' => '❌ Tolak', 'callback_data' => $this->tokenService->buildCallbackData($rejectToken)],
                    ]
                ],
            ];

            $localPath = !empty($latestPayment['bukti_bayar'])
                ? FCPATH . ltrim((string) $latestPayment['bukti_bayar'], '/')
                : null;

            if ($localPath && is_file($localPath)) {
                $this->telegramService->sendMessageToChat($chatId, $header . $paymentBlock, 'HTML');
                $this->telegramService->sendPhotoToChat(
                    $chatId,
                    $localPath,
                    "📎 Bukti pembayaran — <code>{$invoice}</code>",
                    'HTML',
                    ['reply_markup' => $buttons]
                );
            } else {
                $this->telegramService->sendMessageToChat(
                    $chatId,
                    $header . $paymentBlock . "\n\n⚠️ File bukti tidak ditemukan di server.",
                    'HTML',
                    ['reply_markup' => $buttons]
                );
            }
            return;
        }

        // ── Case: proof rejected ─────────────────────────────────────────────
        if ($orderStatus === 'MENUNGGU_BAYAR' && $paymentStatus === 'INVALID') {
            $alasan = $this->escape((string) ($latestPayment['alasan_ditolak'] ?? 'Tidak disebutkan'));
            $expired = !empty($order['expired_at'])
                ? date('d/m/Y H:i', strtotime($order['expired_at'])) . ' WITA'
                : '-';
            $rejBlock = "\n{$sep}\n❌ <b>STATUS PEMBAYARAN: DITOLAK</b>\n{$sep}\n"
                . "Alasan          : {$alasan}\n"
                . "Batas upload ulang: {$expired}\n";
            $this->telegramService->sendMessageToChat($chatId, $header . $rejBlock, 'HTML');
            return;
        }

        // ── Case: waiting for first payment upload ───────────────────────────
        if ($orderStatus === 'MENUNGGU_BAYAR') {
            $expired = !empty($order['expired_at'])
                ? date('d/m/Y H:i', strtotime($order['expired_at'])) . ' WITA'
                : '-';
            $waitBlock = "\n{$sep}\n⏳ <b>STATUS: Menunggu Pembayaran</b>\n{$sep}\n"
                . "Batas bayar: {$expired}\n";
            $this->telegramService->sendMessageToChat($chatId, $header . $waitBlock, 'HTML');
            return;
        }

        // ── Case: cancelled ──────────────────────────────────────────────────
        if ($orderStatus === 'BATAL') {
            $this->telegramService->sendMessageToChat(
                $chatId,
                $header . "\n\n{$sep}\n❌ <b>STATUS: PESANAN DIBATALKAN</b>\n{$sep}",
                'HTML'
            );
            return;
        }

        // ── Case: in-progress / completed — show tracking ────────────────────
        $this->telegramService->sendMessageToChat(
            $chatId,
            $header . $this->buildProgressTracking($order, $latestPayment),
            'HTML'
        );
    }

    private function buildProgressTracking(array $order, ?array $latestPayment): string
    {
        $sep = '━━━━━━━━━━━━━━━━━━━━━━━━';
        $status = (string) ($order['status_pesanan'] ?? '');
        $isPickup = ($order['tipe_pengiriman'] ?? '') === 'AMBIL_SENDIRI';

        $createdAt = !empty($order['tanggal_pesanan'])
            ? date('d/m H:i', strtotime($order['tanggal_pesanan']))
            : null;
        $verifiedAt = !empty($latestPayment['tanggal_verifikasi'])
            && ($latestPayment['status_pembayaran'] ?? '') === 'VALID'
            ? date('d/m H:i', strtotime($latestPayment['tanggal_verifikasi']))
            : null;

        // Build step definitions based on delivery type
        if ($isPickup) {
            $stepDefs = [
                ['key' => 'CREATED', 'label' => 'Pesanan Dibuat', 'time' => $createdAt],
                ['key' => 'PAID', 'label' => 'Pembayaran Terverifikasi', 'time' => $verifiedAt],
                ['key' => 'DIPROSES', 'label' => 'Sedang Diproses', 'time' => null],
                ['key' => 'PESANAN_SIAP', 'label' => 'Pesanan Siap Diambil', 'time' => null],
                ['key' => 'SELESAI', 'label' => 'Selesai', 'time' => null],
            ];
        } else {
            $resiLine = !empty($order['kode_resi'])
                ? "\n   Resi: <code>" . $this->escape($order['kode_resi']) . '</code>'
                : '';
            $stepDefs = [
                ['key' => 'CREATED', 'label' => 'Pesanan Dibuat', 'time' => $createdAt],
                ['key' => 'PAID', 'label' => 'Pembayaran Terverifikasi', 'time' => $verifiedAt],
                ['key' => 'DIPROSES', 'label' => 'Sedang Diproses', 'time' => null],
                ['key' => 'DIKIRIM', 'label' => 'Dalam Pengiriman' . $resiLine, 'time' => null],
                ['key' => 'SELESAI', 'label' => 'Selesai', 'time' => null],
            ];
        }

        // Map status to step index
        $orderMap = ['CREATED' => 0, 'PAID' => 1, 'DIPROSES' => 2, 'SELESAI' => 4];
        $orderMap[$isPickup ? 'PESANAN_SIAP' : 'DIKIRIM'] = 3;

        // Determine current step from order status
        $currentIdx = match ($status) {
            'DIPROSES' => 2,
            'PESANAN_SIAP' => 3,
            'DIKIRIM' => 3,
            'SELESAI' => 4,
            default => 2,
        };

        $text = "\n{$sep}\n📍 <b>TRACKING PROGRESS</b>\n{$sep}\n";

        foreach ($stepDefs as $idx => $step) {
            $timeStr = $step['time'] ? " <i>({$step['time']})</i>" : '';
            if ($idx < $currentIdx) {
                $text .= "✅ {$step['label']}{$timeStr}\n";
            } elseif ($idx === $currentIdx) {
                $text .= "🔵 <b>{$step['label']}</b> ◄ Saat ini{$timeStr}\n";
            } else {
                $text .= "⬜ {$step['label']}\n";
            }
        }

        if ($status === 'SELESAI') {
            $text .= "\n🎉 <b>Pesanan telah selesai. Terima kasih!</b>";
        }

        return $text;
    }

    private function buildPaginationMarkup(string $chatId, string $actionType, array $basePayload, int $page, int $perPage, int $total): array
    {
        $buttons = [];
        $totalPages = max(1, (int) ceil($total / max(1, $perPage)));

        if ($page > 1) {
            $prevToken = $this->tokenService->createToken($actionType, $chatId, array_merge($basePayload, ['page' => $page - 1]), 120, false);
            $buttons[] = [
                'text' => 'Prev',
                'callback_data' => $this->tokenService->buildCallbackData($prevToken),
            ];
        }

        if ($page < $totalPages) {
            $nextToken = $this->tokenService->createToken($actionType, $chatId, array_merge($basePayload, ['page' => $page + 1]), 120, false);
            $buttons[] = [
                'text' => 'Next',
                'callback_data' => $this->tokenService->buildCallbackData($nextToken),
            ];
        }

        $keyboard = [];
        if ($buttons !== []) {
            $keyboard[] = $buttons;
        }
        $keyboard[] = [['text' => '🔙 Menu Utama', 'callback_data' => 'nav:menu']];
        return ['inline_keyboard' => $keyboard];
    }

    /**
     * Edit an existing message if $editMessageId > 0, otherwise send a new one.
     * Used by all navigation/display methods for in-place updates.
     */
    private function sendOrEdit(string $chatId, int $editMessageId, string $text, string $parseMode, array $options): bool
    {
        if ($editMessageId > 0) {
            return $this->telegramService->editMessageText($chatId, $editMessageId, $text, $parseMode, $options);
        }
        return $this->telegramService->sendMessageToChat($chatId, $text, $parseMode, $options);
    }

    private function resolveAdminUserId(string $chatId): ?int
    {
        if ($this->adminUserMap === null) {
            $this->adminUserMap = [];
            $rawMap = trim((string) getenv('TELEGRAM_ADMIN_USER_MAP'));
            if ($rawMap !== '') {
                foreach (explode(',', $rawMap) as $pair) {
                    [$mappedChatId, $mappedUserId] = array_pad(explode(':', trim($pair), 2), 2, null);
                    if ($mappedChatId !== null && $mappedUserId !== null && ctype_digit(trim($mappedUserId))) {
                        $this->adminUserMap[trim($mappedChatId)] = (int) trim($mappedUserId);
                    }
                }
            }

            if ($this->adminUserMap === []) {
                $admins = $this->userModel->where('role', 'ADMIN')->findAll();
                if (count($admins) === 1) {
                    $this->adminUserMap[$chatId] = (int) $admins[0]['id'];
                }
            }
        }

        return $this->adminUserMap[$chatId] ?? null;
    }

    private function normalizeRange(string $range): string
    {
        return in_array($range, ['1d', '3d', '7d', '30d'], true) ? $range : '7d';
    }

    private function resolveRangeWindow(string $range): array
    {
        $range = $this->normalizeRange($range);
        $days = (int) rtrim($range, 'd');
        $start = date('Y-m-d 00:00:00', strtotime('-' . max(0, $days - 1) . ' days'));
        $end = date('Y-m-d 23:59:59');
        return [strtoupper($range), $start, $end];
    }

    private function labelOrderStatus(string $status): string
    {
        $map = [
            'MENUNGGU_BAYAR' => 'Menunggu Bayar',
            'MENUNGGU_VERIFIKASI' => 'Menunggu Bayar',
            'DIPROSES' => 'Diproses',
            'PESANAN_SIAP' => 'Pesanan Siap',
            'DIKIRIM' => 'Dikirim',
            'SELESAI' => 'Selesai',
            'BATAL' => 'Dibatalkan',
        ];

        return $map[$status] ?? $status;
    }

    private function labelStockMode(string $mode): string
    {
        return match ($mode) {
            'add' => 'Tambah',
            'subtract' => 'Kurang',
            default => 'Set',
        };
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

