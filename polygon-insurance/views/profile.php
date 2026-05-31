<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль - Полигон-страхование</title>
    <link rel="stylesheet" href="/polygon-insurance/assets/css/style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="card">
            <h2 class="card-title">Профиль пользователя</h2>
            
            <form id="profile-form">
                <div class="form-group">
                    <label for="full_name">ФИО</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" value="<?php echo htmlspecialchars($_SESSION['full_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" class="form-control" value="<?php echo htmlspecialchars($_SESSION['email']); ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label for="phone">Телефон</label>
                    <input type="tel" id="phone" name="phone" class="form-control" placeholder="+7 (___) ___-__-__">
                </div>
                
                <button type="submit" class="btn btn-primary">Сохранить изменения</button>
            </form>
        </div>
        
        <div class="card">
            <h3>Смена пароля</h3>
            <form id="password-form">
                <div class="form-group">
                    <label for="old_password">Текущий пароль</label>
                    <input type="password" id="old_password" name="old_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="new_password">Новый пароль</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Подтверждение пароля</label>
                    <input type="password" id="confirm_password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-warning">Сменить пароль</button>
            </form>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
    <script>
        document.getElementById('profile-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            
            const response = await fetch('/polygon-insurance/api?action=update_profile', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                alert('Профиль обновлён');
                location.reload();
            } else {
                alert('Ошибка: ' + result.error);
            }
        });
        
        document.getElementById('password-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                alert('Пароли не совпадают');
                return;
            }
            
            const formData = new FormData(e.target);
            const response = await fetch('/polygon-insurance/api?action=change_password', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                alert('Пароль успешно изменён');
                e.target.reset();
            } else {
                alert('Ошибка: ' + result.error);
            }
        });
    </script>
</body>
</html>