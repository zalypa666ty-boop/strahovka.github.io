<?php
/**
 * Контроллер администратора
 * Управление пользователями, тарифами, статистика
 */

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Policy.php';
require_once __DIR__ . '/../models/Tariff.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/Log.php';

class AdminController {
    private $db;
    private $user;
    private $policy;
    private $tariff;
    private $payment;
    private $log;
    
    public function __construct($db) {
        $this->db = $db;
        $this->user = new User($db);
        $this->policy = new Policy($db);
        $this->tariff = new Tariff($db);
        $this->payment = new Payment($db);
        $this->log = new Log($db);
    }
    
    /**
     * Панель администратора
     */
    public function dashboard() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: /polygon-insurance/login');
            exit();
        }
        
        // Статистика
        $users = $this->user->getAllUsers();
        $policies = $this->policy->getAllPolicies();
        $paymentStats = $this->payment->getPaymentStats();
        
        $stats = [
            'total_users' => count($users),
            'total_policies' => count($policies),
            'total_revenue' => array_sum(array_column($paymentStats, 'total')),
            'active_policies' => count(array_filter($policies, function($p) {
                return $p['status'] === 'active';
            }))
        ];
        
        include __DIR__ . '/../views/dashboard_admin.php';
    }
    
    /**
     * Управление пользователями
     */
    public function manageUsers() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $userId = $_POST['user_id'] ?? 0;
            
            if ($action === 'toggle_status') {
                $status = $_POST['status'] ?? 'active';
                $this->user->toggleUserStatus($userId, $status);
                $this->log->add($_SESSION['user_id'], 'Управление пользователем', "Изменение статуса user_id=$userId на $status");
            } elseif ($action === 'change_role') {
                $role = $_POST['role'] ?? 'client';
                $this->user->changeUserRole($userId, $role);
                $this->log->add($_SESSION['user_id'], 'Управление пользователем', "Изменение роли user_id=$userId на $role");
            }
        }
        
        $users = $this->user->getAllUsers();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'users' => $users]);
    }
    
    /**
     * Управление тарифами
     */
    public function manageTariffs() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            
            if ($action === 'update') {
                $id = $_POST['id'] ?? 0;
                $coefficient = $_POST['coefficient'] ?? 0;
                $basePrice = $_POST['base_price'] ?? 0;
                $this->tariff->updateTariff($id, $coefficient, $basePrice);
                $this->log->add($_SESSION['user_id'], 'Изменение тарифа', "Обновлён тариф id=$id");
            } elseif ($action === 'create') {
                $type = $_POST['type'] ?? '';
                $paramName = $_POST['param_name'] ?? '';
                $coefficient = $_POST['coefficient'] ?? 0;
                $basePrice = $_POST['base_price'] ?? 0;
                $this->tariff->createTariff($type, $paramName, $coefficient, $basePrice);
                $this->log->add($_SESSION['user_id'], 'Создание тарифа', "Создан тариф $paramName");
            } elseif ($action === 'delete') {
                $id = $_POST['id'] ?? 0;
                $this->tariff->deleteTariff($id);
                $this->log->add($_SESSION['user_id'], 'Удаление тарифа', "Удалён тариф id=$id");
            }
        }
        
        $tariffs = $this->tariff->getAllTariffs();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'tariffs' => $tariffs]);
    }
    
    /**
     * Получение статистики для графиков
     */
    public function getStats() {
        $policyStats = $this->db->prepare("
            SELECT type, COUNT(*) as count 
            FROM policies 
            GROUP BY type
        ");
        $policyStats->execute();
        
        $paymentStats = $this->payment->getPaymentStats('month');
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'policy_by_type' => $policyStats->fetchAll(),
            'revenue_by_month' => $paymentStats
        ]);
    }
}
?>