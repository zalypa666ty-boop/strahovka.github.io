<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация - Полигон-страхование</title>
    <link rel="stylesheet" href="/polygon-insurance/assets/css/style.css">
</head>
<body>
    <div class="container" style="max-width: 500px;">
        <div class="card">
            <h2 class="card-title">Регистрация</h2>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" data-validate="true">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label for="full_name">ФИО</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Телефон</label>
                    <input type="tel" id="phone" name="phone" class="form-control" placeholder="+7 (___) ___-__-__">
                </div>
                
                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Подтверждение пароля</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="role">Регистрация как</label>
                    <select id="role" name="role" class="form-control">
                        <option value="client">Клиент</option>
                        <option value="agent">Страховой агент</option>
                    </select>
                    <small>Если вы регистрируетесь как агент, дождитесь подтверждения администратора</small>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Зарегистрироваться</button>
            </form>
            
            <div style="text-align: center; margin-top: 1rem;">
                <a href="/polygon-insurance/login">Уже есть аккаунт? Войдите</a>
            </div>
        </div>
    </div>
</body>
</html>