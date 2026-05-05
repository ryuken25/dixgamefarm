<?php

namespace App\Models;

use CodeIgniter\Model;

class NotifikasiModel extends Model
{
    protected $table            = 'notifikasi';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'judul',
        'pesan',
        'is_read'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = ''; // notifikasi table has no updated_at column

    protected $skipValidation = false;

    /**
     * Create notification
     */
    public function createNotification($userId, $judul, $pesan)
    {
        return $this->insert([
            'user_id' => $userId,
            'judul' => $judul,
            'pesan' => $pesan,
            'is_read' => false
        ]);
    }

    /**
     * Get unread notifications
     */
    public function getUnreadNotifications($userId)
    {
        return $this->where('user_id', $userId)
                    ->where('is_read', false)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }

    /**
     * Get all notifications for user
     */
    public function getUserNotifications($userId, $limit = 20)
    {
        return $this->where('user_id', $userId)
                    ->orderBy('created_at', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }

    /**
     * Mark as read
     */
    public function markAsRead($notificationId)
    {
        return $this->update($notificationId, ['is_read' => true]);
    }

    /**
     * Mark all as read for user
     */
    public function markAllAsRead($userId)
    {
        return $this->where('user_id', $userId)
                    ->where('is_read', false)
                    ->set(['is_read' => true])
                    ->update();
    }

    /**
     * Get unread count
     */
    public function getUnreadCount($userId)
    {
        return $this->where('user_id', $userId)
                    ->where('is_read', false)
                    ->countAllResults();
    }

    /**
     * Delete old notifications (cleanup)
     */
    public function deleteOldNotifications($days = 30)
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        return $this->where('created_at <', $cutoffDate)
                    ->where('is_read', true)
                    ->delete();
    }
}
