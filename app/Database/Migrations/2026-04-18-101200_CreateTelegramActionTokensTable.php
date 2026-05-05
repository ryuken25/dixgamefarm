<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTelegramActionTokensTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('telegram_action_tokens')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'token' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
            ],
            'action_type' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
            ],
            'chat_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'payload_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'is_single_use' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'used_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('token', 'uniq_telegram_action_token');
        $this->forge->addKey(['chat_id', 'expires_at'], false, false, 'idx_telegram_action_chat_expiry');
        $this->forge->createTable('telegram_action_tokens');
    }

    public function down()
    {
        $this->forge->dropTable('telegram_action_tokens', true);
    }
}
