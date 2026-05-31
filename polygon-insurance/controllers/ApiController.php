<?php
/**
 * API контроллер для AJAX-запросов
 * Обработка fetch-запросов с фронтенда
 */

require_once __DIR__ . '/../models/Policy.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Log.php';

class ApiController {
    private $db;
    private $policy;
    
    public function __construct($db) {
        $this->db = $db;
        $this->policy = new Policy($db);
    }
    
    /**
     * Обработка входящих запросов
     */
    public function handleRequest($action) {
        header('Content-Type: application/json');
        
        // Проверка CSRF для POST запросов (кроме GET)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrf_token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
                echo json_encode(['success' => false, 'error' => 'Ошибка безопасности']);
                return;
            }
        }
        
        switch ($action) {
            case 'calculate':
                $this->calculate();
                break;
            case 'create_policy':
                $this->createPolicy();
                break;
            case 'generate_pdf':
                $this->generatePDF();
                break;
            case 'get_user_policies':
                $this->getUserPolicies();
                break;
            case 'toggle_user_status':
                $this->toggleUserStatus();
                break;
            case 'get_stats':
                $this->getStats();
                break;
            default:
                echo json_encode(['success' => false, 'error' => 'Неизвестное действие']);
        }
    }
    
    /**
     * Расчёт страховки
     */
    private function calculate() {
        $type = $_POST['type'] ?? 'osago';
        $result = ['success' => true, 'type' => $type, 'data' => []];
        
        if ($type === 'osago') {
            $data = [
                'power' => intval($_POST['power'] ?? 0),
                'driver_age' => intval($_POST['driver_age'] ?? 0),
                'experience' => intval($_POST['experience'] ?? 0),
                'region' => $_POST['region'] ?? 'Москва'
            ];
            $premium = $this->policy->calculateOsago($data);
            $result['premium'] = $premium;
            $result['data'] = $data;
            
        } elseif ($type === 'casco') {
            $data = ['car_value' => intval($_POST['car_value'] ?? 0)];
            $premium = $this->policy->calculateCasco($data);
            $result['premium'] = $premium;
            $result['data'] = $data;
            
        } elseif ($type === 'health') {
            $data = [
                'person_name' => $_POST['person_name'] ?? '',
                'person_age' => intval($_POST['person_age'] ?? 0),
                'type' => $_POST['type'] ?? 'adult'
            ];
            $premium = $this->policy->calculateHealth($data);
            $result['premium'] = $premium;
            $result['data'] = $data;
        }
        
        // Сохраняем в сессию для последующего оформления
        $_SESSION['calculated_data'] = $result;
        
        echo json_encode($result);
    }
    
    /**
     * Создание полиса
     */
    private function createPolicy() {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Не авторизован']);
            return;
        }
        
        $type = $_POST['type'] ?? 'osago';
        $premium = floatval($_POST['premium'] ?? 0);
        $data = json_decode($_POST['data'] ?? '{}', true);
        
        $userId = $_SESSION['user_id'];
        $agentId = ($_SESSION['role'] === 'agent') ? $userId : null;
        
        $result = $this->policy->createPolicy($userId, $agentId, $type, $data, $premium);
        echo json_encode($result);
    }
    
    /**
     * Генерация PDF
     */
    private function generatePDF() {
        $policyId = intval($_GET['policy_id'] ?? 0);
        
        if (!$policyId) {
            echo json_encode(['success' => false, 'error' => 'ID полиса не указан']);
            return;
        }
        
        $url = $this->policy->generatePDF($policyId);
        
        if ($url) {
            echo json_encode(['success' => true, 'url' => $url]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Ошибка генерации PDF']);
        }
    }
    
    /**
     * Получение полисов пользователя
     */
    private function getUserPolicies() {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Не авторизован']);
            return;
        }
        
        $policies = $this->policy->getUserPolicies($_SESSION['user_id']);
        echo json_encode(['success' => true, 'policies' => $policies]);
    }
    
    /**
     * Переключение статуса пользователя (админ)
     */
    private function toggleUserStatus() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            echo json_encode(['success' => false, 'error' => 'Доступ запрещён']);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $userId = $data['user_id'] ?? 0;
        $status = $data['status'] ?? 'active';
        
        $userModel = new User($this->db);
        $result = $userModel->toggleUserStatus($userId, $status);
        
        echo json_encode(['success' => $result]);
    }
    
    /**
     * Получение статистики
     */
    private function getStats() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            echo json_encode(['success' => false, 'error' => 'Доступ запрещён']);
            return;
        }
        
        $policyStats = $this->db->prepare("
            SELECT type, COUNT(*) as count, SUM(premium) as total 
            FROM policies 
            WHERE status = 'active'
            GROUP BY type
        ");
        $policyStats->execute();
        
        echo json_encode([
            'success' => true,
            'policies' => $policyStats->fetchAll()
        ]);
    }
}
?>