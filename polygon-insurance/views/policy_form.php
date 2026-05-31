<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оформление полиса - Полигон-страхование</title>
    <link rel="stylesheet" href="/polygon-insurance/assets/css/style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="card">
            <h2 class="card-title">Оформление страхового полиса</h2>
            
            <?php if ($calculatedData): ?>
                <div class="alert alert-info">
                    <strong>Результат расчёта:</strong> стоимость полиса составила <strong><?php echo number_format($calculatedData['premium'], 2); ?> ₽</strong>
                </div>
                
                <form id="policy-create-form">
                    <input type="hidden" name="type" value="<?php echo $calculatedData['type']; ?>">
                    <input type="hidden" name="premium" value="<?php echo $calculatedData['premium']; ?>">
                    <input type="hidden" name="data" value='<?php echo json_encode($calculatedData['data']); ?>'>
                    
                    <div class="form-group">
                        <label for="full_name">ФИО страхователя</label>
                        <input type="text" id="full_name" class="form-control" value="<?php echo htmlspecialchars($_SESSION['full_name']); ?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" class="form-control" value="<?php echo htmlspecialchars($_SESSION['email']); ?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Контактный телефон</label>
                        <input type="tel" id="phone" class="form-control" required placeholder="+7 (___) ___-__-__">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Подтвердить оформление</button>
                    <a href="/polygon-insurance/calculator" class="btn">Назад к калькулятору</a>
                </form>
            <?php else: ?>
                <div class="alert alert-warning">
                    Нет данных для оформления. Пожалуйста, выполните расчёт на <a href="/polygon-insurance/calculator">странице калькулятора</a>.
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
    <script>
        document.getElementById('policy-create-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const data = {
                type: formData.get('type'),
                premium: formData.get('premium'),
                data: formData.get('data'),
                phone: formData.get('phone')
            };
            
            const response = await fetch('/polygon-insurance/api?action=create_policy', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            });
            const result = await response.json();
            
            if (result.success) {
                alert('Полис успешно оформлен! Номер: ' + result.policy_number);
                window.location.href = '/polygon-insurance/dashboard';
            } else {
                alert('Ошибка: ' + result.error);
            }
        });
    </script>
</body>
</html>