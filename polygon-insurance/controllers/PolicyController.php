<?php
/**
 * Контроллер управления полисами
 * Оформление, просмотр, фильтрация
 */

require_once __DIR__ . '/../models/Policy.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Log.php';

class PolicyController {
    private $db;
    private $policy;
    private $user;
    private $log;
    
    public function __construct($db) {
        $this->db = $db;
        $this->policy = new Policy($db);
        $this->user = new User($db);
        $this->log = new Log($db);
    }
    
    /**
     * Панель клиента
     */
    public function clientDashboard() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
            header('Location: /polygon-insurance/login');
            exit();
        }
        
        $policies = $this->policy->getUserPolicies($_SESSION['user_id']);
        include __DIR__ . '/../views/dashboard_client.php';
    }
    
    /**
     * Показать форму оформления полиса
     */
    public function showForm() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /polygon-insurance/login');
            exit();
        }
        
        $type = $_GET['type'] ?? 'osago';
        $calculatedData = $_SESSION['calculated_data'] ?? null;
        
        include __DIR__ . '/../views/policy_form.php';
    }
    
    /**
     * Обработка оформления полиса
     */
    public function create() {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Не авторизован']);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $userId = $_SESSION['user_id'];
        $agentId = $_SESSION['role'] === 'agent' ? $userId : null;
        $type = $data['type'] ?? 'osago';
        $policyData = json_decode($data['data'], true);
        $premium = $data['premium'] ?? 0;
        
        $result = $this->policy->createPolicy($userId, $agentId, $type, $policyData, $premium);
        
        if ($result['success']) {
            $this->log->add($userId, 'Оформление полиса', "Оформлен полис №{$result['policy_number']}");
        }
        
        echo json_encode($result);
    }
}
?>