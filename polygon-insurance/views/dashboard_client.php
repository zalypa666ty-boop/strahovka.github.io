<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет - Полигон-страхование</title>
    <link rel="stylesheet" href="/polygon-insurance/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="card">
            <h2 class="card-title">Добро пожаловать, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>
            <p>Ваш личный кабинет страхователя</p>
        </div>
        
        <div class="card">
            <h3>Мои страховые полисы</h3>
            
            <div class="form-group" style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <input type="text" id="search-policies" class="form-control" placeholder="Поиск по полисам..." style="flex: 1;">
                <select id="filter-status" class="form-control" style="width: auto;">
                    <option value="all">Все статусы</option>
                    <option value="active">Активные</option>
                    <option value="pending">Ожидание</option>
                    <option value="expired">Истекшие</option>
                </select>
            </div>
            
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>№ полиса</th>
                            <th>Тип</th>
                            <th>Сумма</th>
                            <th>Статус</th>
                            <th>Дата начала</th>
                            <th>Дата окончания</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($policies as $policy): ?>
                        <tr class="policy-row" data-status="<?php echo $policy['status']; ?>">
                            <td><?php echo htmlspecialchars($policy['policy_number']); ?></td>
                            <td>
                                <?php 
                                    $types = ['osago' => 'ОСАГО', 'casco' => 'КАСКО', 'health' => 'Здоровье'];
                                    echo $types[$policy['type']] ?? $policy['type'];
                                ?>
                            </td>
                            <td><?php echo number_format($policy['premium'], 2); ?> ₽</td>
                            <td>
                                <span class="status-<?php echo $policy['status']; ?>">
                                    <?php echo $policy['status'] === 'active' ? 'Активен' : ($policy['status'] === 'pending' ? 'Ожидание' : 'Завершён'); ?>
                                </span>
                            </td>
                            <td><?php echo $policy['valid_from'] ?? '—'; ?></td>
                            <td><?php echo $policy['valid_to'] ?? '—'; ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary pdf-download" data-policy-id="<?php echo $policy['id']; ?>">📄 PDF</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($policies)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">У вас пока нет оформленных полисов</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 1rem;">
                <a href="/polygon-insurance/calculator" class="btn btn-success">Оформить новый полис</a>
            </div>
        </div>
        
        <div class="card">
            <h3>История платежей</h3>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Дата</th>
                            <th>Полис</th>
                            <th>Сумма</th>
                            <th>Статус</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $paymentModel = new Payment($conn);
                        $payments = $paymentModel->getUserPayments($_SESSION['user_id']);
                        ?>
                        <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?php echo date('d.m.Y', strtotime($payment['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($payment['policy_number']); ?></td>
                            <td><?php echo number_format($payment['amount'], 2); ?> ₽</td>
                            <td>
                                <span class="status-<?php echo $payment['status']; ?>">
                                    <?php echo $payment['status'] === 'completed' ? 'Оплачен' : 'В обработке'; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
    <script src="/polygon-insurance/assets/js/main.js"></script>
</body>
</html>