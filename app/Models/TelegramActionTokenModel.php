<?php

namespace App\Models;

use CodeIgniter\Model;

class TelegramActionTokenModel extends Model
{
    protected $table = 'telegram_action_tokens';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'token',
        'action_type',
        'chat_id',
        'payload_json',
        'is_single_use',
        'used_at',
        'expires_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}

