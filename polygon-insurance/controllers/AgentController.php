<?php
/**
 * Контроллер страхового агента
 * Управление клиентами, оформление полисов для клиентов
 */

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Policy.php';
require_once __DIR__ . '/../models/Log.php';

class AgentController {
    private $db;
    private $user;
    private $policy;
    private $log;
    
    public function __construct($db) {
        $this->db = $db;
        $this->user = new User($db);
        $this->policy = new Policy($db);
        $this->log = new Log($db);
    }
    
    /**
     * Панель агента
     */
    public function dashboard() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'agent') {
            header('Location: /polygon-insurance/login');
            exit();
        }
        
        $clients = $this->user->getClientsByAgent($_SESSION['user_id']);
        $policies = $this->policy->getAgentPolicies($_SESSION['user_id']);
        
        include __DIR__ . '/../views/dashboard_agent.php';
    }
    
    /**
     * Оформление полиса для клиента
     */
    public function createForClient() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'agent') {
            echo json_encode(['success' => false, 'error' => 'Доступ запрещён']);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $clientId = $data['client_id'] ?? 0;
        $type = $data['type'] ?? 'osago';
        $policyData = json_decode($data['data'], true);
        $premium = $data['premium'] ?? 0;
        
        $result = $this->policy->createPolicy($clientId, $_SESSION['user_id'], $type, $policyData, $premium);
        
        if ($result['success']) {
            $this->log->add($_SESSION['user_id'], 'Оформление полиса для клиента', 
                "Оформлен полис №{$result['policy_number']} для клиента id=$clientId");
        }
        
        echo json_encode($result);
    }
    
    /**
     * Поиск клиентов
     */
    public function searchClients() {
        $search = $_GET['search'] ?? '';
        
        $stmt = $this->db->prepare("
            SELECT id, email, full_name, phone 
            FROM users 
            WHERE role = 'client' 
              AND (email LIKE ? OR full_name LIKE ? OR phone LIKE ?)
            LIMIT 20
        ");
        $searchTerm = "%$search%";
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        
        header('Content-Type: application/json');
        echo json_encode($stmt->fetchAll());
    }
}
?>