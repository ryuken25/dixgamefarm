<?php

namespace App\Libraries;

/**
 * TelegramService - One-way push notifications to farm admins via Telegram Bot API.
 *
 * Configuration (.env):
 *   TELEGRAM_BOT_TOKEN="..."
 *   TELEGRAM_CHAT_ID="..."           # primary admin chat id (fallback)
 *   TELEGRAM_ADMIN_IDS="id1,id2,..." # optional, overrides TELEGRAM_CHAT_ID when set
 *
 * Failures are logged but never block the main business flow.
 */
class TelegramService
{
    private string $botToken;
    /** @var string[] */
    private array $chatIds = [];
    private string $apiUrl = 'https://api.telegram.org/bot';
    private bool $enabled = false;
    private int $connectTimeoutMs = 700;
    private int $requestTimeoutMs = 1500;

    public function __construct()
    {
        $this->botToken = getenv('TELEGRAM_BOT_TOKEN') ?: '';

        $adminIds = getenv('TELEGRAM_ADMIN_IDS') ?: '';
        if ($adminIds !== '') {
            foreach (explode(',', $adminIds) as $id) {
                $id = trim($id);
                if ($id !== '' && $id !== 'ISI_ID_CHAT_ADMIN_DI_SINI') {
                    $this->chatIds[] = $id;
                }
            }
        }

        if (empty($this->chatIds)) {
            $primary = trim(getenv('TELEGRAM_CHAT_ID') ?: '');
            if ($primary !== '' && $primary !== 'ISI_ID_CHAT_ADMIN_DI_SINI') {
                $this->chatIds[] = $primary;
            }
        }

        $this->enabled = !empty($this->botToken)
            && $this->botToken !== 'ISI_TOKEN_DARI_BOTFATHER_DI_SINI'
            && !empty($this->chatIds);
    }

    /**
     * Send a message to every configured admin chat.
     * Returns true if at least one admin successfully received the message.
     */
    public function sendMessage(string $message, string $parseMode = 'HTML'): bool
    {
        if (!$this->enabled) {
            log_message('debug', 'TelegramService: Disabled (token/chat_id not configured)');
            return false;
        }

        $anySuccess = false;
        foreach ($this->chatIds as $chatId) {
            if ($this->sendMessageToChat($chatId, $message, $parseMode)) {
                $anySuccess = true;
            }
        }
        return $anySuccess;
    }

    public function sendMessageToChat(string $chatId, string $message, string $parseMode = 'HTML', array $options = []): bool
    {
        if (!$this->enabled) {
            log_message('debug', 'TelegramService: Disabled (token/chat_id not configured)');
            return false;
        }

        return $this->sendToChat($chatId, $message, $parseMode, $options) > 0;
    }

    /**
     * Returns the sent message_id on success (> 0), or 0 on failure.
     * Callers that only need bool can use `> 0`.
     */
    private function sendToChat(string $chatId, string $message, string $parseMode, array $options = []): int
    {
        $postData = [
            'chat_id'                  => $chatId,
            'text'                     => $message,
            'parse_mode'               => $parseMode,
            'disable_web_page_preview' => true,
        ];

        if (!empty($options['reply_markup'])) {
            $postData['reply_markup'] = json_encode($options['reply_markup'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (!empty($options['reply_to_message_id'])) {
            $postData['reply_to_message_id'] = (int) $options['reply_to_message_id'];
        }

        if (array_key_exists('disable_notification', $options)) {
            $postData['disable_notification'] = (bool) $options['disable_notification'];
        }

        $result = $this->apiRequest('sendMessage', $postData, $chatId);
        if (!$result['ok']) {
            return 0;
        }
        // API result structure: apiRequest wraps decoded JSON in ['result' => $decoded]
        // so the Message object is at $result['result']['result']
        return (int) ($result['result']['result']['message_id'] ?? 0);
    }

    /** Returns the Telegram message_id of the sent message, or 0 on failure. */
    public function sendMessageGetId(string $chatId, string $message, string $parseMode = 'HTML', array $options = []): int
    {
        if (!$this->enabled) {
            return 0;
        }
        return $this->sendToChat($chatId, $message, $parseMode, $options);
    }

    /**
     * Send a local image file directly to a single chat.
     * Uses multipart upload so the photo lives on Telegram servers
     * and is always accessible, regardless of localhost/server environment.
     */
    /** Returns the Telegram message_id of the sent photo, or 0 on failure. */
    public function sendPhotoToChat(string $chatId, string $localFilePath, string $caption = '', string $parseMode = 'HTML', array $options = []): int
    {
        if (!$this->enabled) {
            return false;
        }

        if (!is_file($localFilePath)) {
            log_message('warning', 'TelegramService::sendPhotoToChat file not found: ' . $localFilePath);
            return false;
        }

        $url = $this->apiUrl . $this->botToken . '/sendPhoto';

        $postData = [
            'chat_id'    => $chatId,
            'photo'      => new \CURLFile($localFilePath),
            'parse_mode' => $parseMode,
        ];

        if ($caption !== '') {
            $postData['caption'] = $caption;
        }

        if (!empty($options['reply_markup'])) {
            $postData['reply_markup'] = json_encode(
                $options['reply_markup'],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        }

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $postData,   // array = multipart (required for CURLFile)
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_NOSIGNAL       => true,
                CURLOPT_TIMEOUT_MS     => 20000,       // photo upload needs more time
                CURLOPT_CONNECTTIMEOUT_MS => 5000,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $response  = curl_exec($ch);
            $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                log_message('error', "TelegramService sendPhotoToChat cURL error ({$chatId}): {$curlError}");
                return 0;
            }

            if ($httpCode !== 200) {
                log_message('error', "TelegramService sendPhotoToChat HTTP {$httpCode} ({$chatId}): {$response}");
                return 0;
            }

            $result = json_decode($response, true);
            if (!($result['ok'] ?? false)) {
                log_message('error', "TelegramService sendPhotoToChat API error ({$chatId}): " . ($result['description'] ?? 'Unknown'));
                return 0;
            }

            log_message('info', "TelegramService: sendPhoto success for {$chatId}");
            return (int) ($result['result']['message_id'] ?? 0);

        } catch (\Exception $e) {
            log_message('error', "TelegramService sendPhotoToChat exception ({$chatId}): " . $e->getMessage());
            return 0;
        }
    }

    public function getUpdates(int $offset = 0, int $timeout = 10): array
    {
        $timeout = max(1, $timeout);
        $payload = [
            'offset'          => $offset,
            'timeout'         => $timeout,
            'allowed_updates' => json_encode(['message', 'callback_query'], JSON_UNESCAPED_UNICODE),
        ];

        // cURL must wait longer than the Telegram long-polling timeout
        $curlTimeoutMs = ($timeout + 5) * 1000;
        $result = $this->apiRequest('getUpdates', $payload, 'poller', $curlTimeoutMs);
        if (!$result['ok']) {
            return [];
        }

        return $result['result']['result'] ?? [];
    }

    public function answerCallbackQuery(string $callbackQueryId, string $text = '', bool $showAlert = false): bool
    {
        $payload = [
            'callback_query_id' => $callbackQueryId,
            'text' => $text,
            'show_alert' => $showAlert,
        ];

        $result = $this->apiRequest('answerCallbackQuery', $payload, 'callback');
        return $result['ok'];
    }

    public function getAuthorizedChatIds(): array
    {
        return $this->chatIds;
    }

    public function isAuthorizedChatId(string $chatId): bool
    {
        return in_array((string) $chatId, array_map('strval', $this->chatIds), true);
    }

    public function buildFileUrl(string $filePath): string
    {
        return 'https://api.telegram.org/file/bot' . $this->botToken . '/' . ltrim($filePath, '/');
    }

    private function apiRequest(string $method, array $postData = [], string $chatLabel = 'system', int $timeoutOverrideMs = 0): array
    {
        $url        = $this->apiUrl . $this->botToken . '/' . $method;
        $requestMs  = $timeoutOverrideMs > 0 ? $timeoutOverrideMs : $this->requestTimeoutMs;

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL               => $url,
                CURLOPT_POST              => true,
                CURLOPT_POSTFIELDS        => http_build_query($postData),
                CURLOPT_RETURNTRANSFER    => true,
                CURLOPT_NOSIGNAL          => true,
                CURLOPT_TIMEOUT_MS        => $requestMs,
                CURLOPT_CONNECTTIMEOUT_MS => $this->connectTimeoutMs,
                CURLOPT_SSL_VERIFYPEER    => true,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                log_message('error', "TelegramService cURL error ({$method}/{$chatLabel}): {$error}");
                return ['ok' => false, 'result' => null];
            }

            if ($httpCode !== 200) {
                log_message('error', "TelegramService HTTP {$httpCode} ({$method}/{$chatLabel}): {$response}");
                return ['ok' => false, 'result' => null];
            }

            $result = json_decode($response, true);
            if (!($result['ok'] ?? false)) {
                log_message('error', "TelegramService API error ({$method}/{$chatLabel}): " . ($result['description'] ?? 'Unknown'));
                return ['ok' => false, 'result' => $result];
            }

            log_message('info', "TelegramService: {$method} success for {$chatLabel}");
            return ['ok' => true, 'result' => $result];

        } catch (\Exception $e) {
            log_message('error', "TelegramService exception ({$method}/{$chatLabel}): " . $e->getMessage());
            return ['ok' => false, 'result' => null];
        }
    }

    public function notifyNewOrder(string $invoiceNumber, float $grandTotal, int $totalItems): bool
    {
        $formattedTotal = 'Rp ' . number_format($grandTotal, 0, ',', '.');
        $timestamp = date('d/m/Y H:i:s');

        $message = "<b>🛒 Pesanan Baru Masuk</b>\n\n"
            . "Invoice: <code>" . $this->escape($invoiceNumber) . "</code>\n"
            . "Total: <b>{$formattedTotal}</b>\n"
            . "Jumlah Item: {$totalItems} produk\n"
            . "Waktu: {$timestamp}\n\n"
            . "Silakan cek dashboard admin untuk detail pesanan.";

        return $this->sendMessage($message);
    }

    public function notifyPaymentUploaded(string $invoiceNumber, string $customerName, ?string $proofUrl = null, array $replyMarkup = []): bool
    {
        $timestamp = date('d/m/Y H:i:s');

        $message = "<b>💳 Bukti Pembayaran Diunggah</b>\n\n"
            . "Invoice: <code>" . $this->escape($invoiceNumber) . "</code>\n"
            . "Pelanggan: " . $this->escape($customerName) . "\n"
            . "Waktu: {$timestamp}\n";

        if ($proofUrl) {
            $message .= "Bukti: <a href=\"" . $this->escape($proofUrl) . "\">Lihat bukti pembayaran</a>\n";
        }

        $message .= "\nSilakan verifikasi di dashboard admin.";

        if ($replyMarkup !== []) {
            $anySuccess = false;
            foreach ($this->chatIds as $chatId) {
                if ($this->sendMessageToChat($chatId, $message, 'HTML', ['reply_markup' => $replyMarkup])) {
                    $anySuccess = true;
                }
            }

            return $anySuccess;
        }

        return $this->sendMessage($message);
    }

    /**
     * Send a contact-form message from the public /kontak page to admin Telegram.
     */
    public function notifyContactMessage(array $payload): bool
    {
        $nama = $this->escape($payload['nama'] ?? '-');
        $email = $this->escape($payload['email'] ?? '-');
        $noHp = $this->escape($payload['no_hp'] ?? '-');
        $subjek = $this->escape($payload['subjek'] ?? '-');
        $pesan = $this->escape($payload['pesan'] ?? '-');
        $timestamp = date('d/m/Y H:i:s');

        $message = "<b>📨 Pesan Baru dari /kontak</b>\n\n"
            . "<b>Nama:</b> {$nama}\n"
            . "<b>Email:</b> {$email}\n"
            . "<b>No. HP:</b> {$noHp}\n"
            . "<b>Subjek:</b> {$subjek}\n"
            . "<b>Waktu:</b> {$timestamp}\n\n"
            . "<b>Pesan:</b>\n<blockquote>{$pesan}</blockquote>";

        return $this->sendMessage($message);
    }

    /**
     * Edit an existing message in-place (replaces text + keyboard).
     * Returns true on success. "message is not modified" is treated as success.
     */
    public function editMessageText(string $chatId, int $messageId, string $text, string $parseMode = 'HTML', array $options = []): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $postData = [
            'chat_id'                  => $chatId,
            'message_id'               => $messageId,
            'text'                     => $text,
            'parse_mode'               => $parseMode,
            'disable_web_page_preview' => true,
        ];

        if (!empty($options['reply_markup'])) {
            $postData['reply_markup'] = json_encode(
                $options['reply_markup'],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        }

        $result = $this->apiRequest('editMessageText', $postData, $chatId);

        if (!$result['ok']) {
            $desc = (string) ($result['result']['description'] ?? '');
            // Not modified is benign — content is already what we want
            if (str_contains($desc, 'message is not modified')) {
                return true;
            }
            return false;
        }

        return true;
    }

    /**
     * Register bot commands so the "/" menu in Telegram shows them.
     * Call once when the polling loop starts.
     */
    public function setMyCommands(array $commands): bool
    {
        if (!$this->enabled) {
            return false;
        }
        $result = $this->apiRequest('setMyCommands', [
            'commands' => json_encode($commands, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ], 'system');
        return $result['ok'];
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
