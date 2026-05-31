<?php
/**
 * Модель платежей
 * Управление платежами, история транзакций
 */

class Payment {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Получение платежей по полису
     */
    public function getPaymentsByPolicy($policyId) {
        $stmt = $this->db->prepare("
            SELECT * FROM payments 
            WHERE policy_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$policyId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Получение всех платежей пользователя
     */
    public function getUserPayments($userId) {
        $stmt = $this->db->prepare("
            SELECT p.*, pol.policy_number, pol.type 
            FROM payments p
            JOIN policies pol ON p.policy_id = pol.id
            WHERE pol.user_id = ?
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Создание платежа
     */
    public function createPayment($policyId, $amount, $transactionId = null) {
        $stmt = $this->db->prepare("
            INSERT INTO payments (policy_id, amount, status, transaction_id)
            VALUES (?, ?, 'pending', ?)
        ");
        return $stmt->execute([$policyId, $amount, $transactionId]);
    }
    
    /**
     * Подтверждение платежа
     */
    public function confirmPayment($paymentId) {
        $stmt = $this->db->prepare("UPDATE payments SET status = 'completed' WHERE id = ?");
        return $stmt->execute([$paymentId]);
    }
    
    /**
     * Статистика платежей (для админа)
     */
    public function getPaymentStats($period = 'month') {
        if ($period == 'month') {
            $sql = "
                SELECT DATE_FORMAT(created_at, '%Y-%m') as period, 
                       COUNT(*) as count, 
                       SUM(amount) as total
                FROM payments 
                WHERE status = 'completed'
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY period DESC
                LIMIT 12
            ";
        } else {
            $sql = "
                SELECT DATE(created_at) as period, 
                       COUNT(*) as count, 
                       SUM(amount) as total
                FROM payments 
                WHERE status = 'completed' 
                  AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(created_at)
                ORDER BY period DESC
            ";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>