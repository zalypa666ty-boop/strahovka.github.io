<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель администратора - Полигон-страхование</title>
    <link rel="stylesheet" href="/polygon-insurance/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="card">
            <h2 class="card-title">Панель администратора</h2>
            <p>Добро пожаловать, <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
        </div>
        
        <!-- Статистика -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
            <div class="card" style="text-align: center;">
                <h3>Пользователи</h3>
                <p style="font-size: 36px; font-weight: bold; color: #0056b3;"><?php echo $stats['total_users']; ?></p>
            </div>
            <div class="card" style="text-align: center;">
                <h3>Полисы</h3>
                <p style="font-size: 36px; font-weight: bold; color: #28a745;"><?php echo $stats['total_policies']; ?></p>
            </div>
            <div class="card" style="text-align: center;">
                <h3>Выручка</h3>
                <p style="font-size: 36px; font-weight: bold; color: #ffc107;"><?php echo number_format($stats['total_revenue'], 0); ?> ₽</p>
            </div>
            <div class="card" style="text-align: center;">
                <h3>Активные полисы</h3>
                <p style="font-size: 36px; font-weight: bold; color: #17a2b8;"><?php echo $stats['active_policies']; ?></p>
            </div>
        </div>
        
        <!-- Графики -->
        <div class="card">
            <h3>Статистика полисов по типам</h3>
            <canvas id="policyChart" style="max-height: 300px;"></canvas>
        </div>
        
        <div class="card">
            <h3>Управление пользователями</h3>
            <div class="table-container">
                <table class="table" id="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>ФИО</th>
                            <th>Роль</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody id="users-list">
                        <?php foreach ($users as $user): ?>
                        <tr data-user-id="<?php echo $user['id']; ?>">
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td>
                                <select class="form-control role-select" data-user-id="<?php echo $user['id']; ?>" style="width: auto;">
                                    <option value="client" <?php echo $user['role'] === 'client' ? 'selected' : ''; ?>>Клиент</option>
                                    <option value="agent" <?php echo $user['role'] === 'agent' ? 'selected' : ''; ?>>Агент</option>
                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Админ</option>
                                </select>
                            </td>
                            <td>
                                <span class="status-<?php echo $user['status']; ?>">
                                    <?php echo $user['status'] === 'active' ? 'Активен' : 'Заблокирован'; ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-warning toggle-status" data-user-id="<?php echo $user['id']; ?>" data-current-status="<?php echo $user['status']; ?>">
                                    <?php echo $user['status'] === 'active' ? 'Заблокировать' : 'Разблокировать'; ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="card">
            <h3>Управление тарифами</h3>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Тип</th>
                            <th>Параметр</th>
                            <th>Коэффициент</th>
                            <th>Базовая цена</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $tariffs = $this->tariff->getAllTariffs();
                        foreach ($tariffs as $tariff):
                        ?>
                        <tr>
                            <td><?php echo $tariff['id']; ?></td>
                            <td><?php echo strtoupper($tariff['type']); ?></td>
                            <td><?php echo htmlspecialchars($tariff['param_name']); ?></td>
                            <td>
                                <input type="number" step="0.01" value="<?php echo $tariff['coefficient']; ?>" class="form-control tariff-coefficient" data-id="<?php echo $tariff['id']; ?>" style="width: 100px;">
                            </td>
                            <td>
                                <input type="number" step="100" value="<?php echo $tariff['base_price']; ?>" class="form-control tariff-price" data-id="<?php echo $tariff['id']; ?>" style="width: 120px;">
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary update-tariff" data-id="<?php echo $tariff['id']; ?>">Сохранить</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
    
    <script>
        // График полисов по типам
        <?php
        $stmt = $conn->prepare("SELECT type, COUNT(*) as count FROM policies GROUP BY type");
        $stmt->execute();
        $chartData = $stmt->fetchAll();
        ?>
        const ctx = document.getElementById('policyChart').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_map(function($d) {
                    return $d['type'] === 'osago' ? 'ОСАГО' : ($d['type'] === 'casco' ? 'КАСКО' : 'Здоровье');
                }, $chartData)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($chartData, 'count')); ?>,
                    backgroundColor: ['#0056b3', '#28a745', '#ffc107']
                }]
            }
        });
    </script>
    <script src="/polygon-insurance/assets/js/main.js"></script>
</body>
</html>