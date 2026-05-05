<?php

namespace App\Libraries;

use App\Models\TelegramActionTokenModel;

class TelegramActionTokenService
{
    private TelegramActionTokenModel $tokenModel;

    public function __construct()
    {
        $this->tokenModel = new TelegramActionTokenModel();
    }

    public function createToken(string $actionType, string $chatId, array $payload, int $expiresMinutes = 30, bool $singleUse = true): string
    {
        if (!$this->tokenModel->db->tableExists('telegram_action_tokens')) {
            throw new \RuntimeException('Tabel telegram_action_tokens belum tersedia. Jalankan migration Telegram terlebih dahulu.');
        }

        $token = substr(bin2hex(random_bytes(16)), 0, 24);

        $this->tokenModel->insert([
            'token' => $token,
            'action_type' => $actionType,
            'chat_id' => $chatId,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'is_single_use' => $singleUse ? 1 : 0,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+' . max(1, $expiresMinutes) . ' minutes')),
        ]);

        return $token;
    }

    public function buildCallbackData(string $token): string
    {
        return 'ta:' . $token;
    }

    public function consumeCallbackData(string $callbackData, string $chatId): array
    {
        if (!str_starts_with($callbackData, 'ta:')) {
            return ['success' => false, 'message' => 'Aksi Telegram tidak dikenali'];
        }

        return $this->consumeToken(substr($callbackData, 3), $chatId);
    }

    /**
     * Merge additional key-value pairs into a token's payload_json.
     * Used to back-fill message_id after a message is sent.
     */
    public function updatePayload(string $token, array $merge): void
    {
        if (!$this->tokenModel->db->tableExists('telegram_action_tokens')) {
            return;
        }
        $row = $this->tokenModel->where('token', $token)->first();
        if (!$row) {
            return;
        }
        $payload = json_decode((string) ($row['payload_json'] ?? '{}'), true);
        if (!is_array($payload)) {
            $payload = [];
        }
        $this->tokenModel->update($row['id'], [
            'payload_json' => json_encode(
                array_merge($payload, $merge),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ),
        ]);
    }

    /**
     * Return all active (not expired, not used) payment_action tokens
     * whose payload contains the given payment_id.
     * Used to edit all admin notification messages after a payment is processed.
     */
    public function getActivePaymentTokens(int $paymentId): array
    {
        if (!$this->tokenModel->db->tableExists('telegram_action_tokens')) {
            return [];
        }
        $rows = $this->tokenModel
            ->where('action_type', 'payment_action')
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->where('used_at IS NULL', null, false)
            ->findAll();

        return array_values(array_filter($rows, function ($row) use ($paymentId) {
            $p = json_decode((string) ($row['payload_json'] ?? '{}'), true);
            return ((int) ($p['payment_id'] ?? 0)) === $paymentId;
        }));
    }

    public function consumeToken(string $token, string $chatId): array
    {
        if (!$this->tokenModel->db->tableExists('telegram_action_tokens')) {
            return ['success' => false, 'message' => 'Fitur aksi Telegram belum siap karena tabel token belum tersedia'];
        }

        $row = $this->tokenModel->where('token', $token)->first();
        if (!$row) {
            return ['success' => false, 'message' => 'Token aksi tidak ditemukan atau sudah kedaluwarsa'];
        }

        if ((string) $row['chat_id'] !== (string) $chatId) {
            return ['success' => false, 'message' => 'Aksi ini tidak diizinkan untuk chat Anda'];
        }

        if (!empty($row['expires_at']) && strtotime((string) $row['expires_at']) < time()) {
            return ['success' => false, 'message' => 'Aksi Telegram sudah kedaluwarsa'];
        }

        if ((int) ($row['is_single_use'] ?? 1) === 1 && !empty($row['used_at'])) {
            return ['success' => false, 'message' => 'Aksi Telegram ini sudah diproses sebelumnya'];
        }

        if ((int) ($row['is_single_use'] ?? 1) === 1) {
            $this->tokenModel->update($row['id'], ['used_at' => date('Y-m-d H:i:s')]);
        }

        $payload = json_decode((string) ($row['payload_json'] ?? '{}'), true);
        if (!is_array($payload)) {
            $payload = [];
        }

        return [
            'success' => true,
            'token' => $row,
            'payload' => $payload,
            'action_type' => (string) $row['action_type'],
        ];
    }
}

