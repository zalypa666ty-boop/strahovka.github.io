<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель агента - Полигон-страхование</title>
    <link rel="stylesheet" href="/polygon-insurance/assets/css/style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="card">
            <h2 class="card-title">Панель страхового агента</h2>
            <p>Добро пожаловать, <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
        </div>
        
        <div class="card">
            <h3>Мои клиенты</h3>
            <div class="form-group">
                <input type="text" id="client-search" class="form-control" placeholder="Поиск клиентов по имени, email или телефону...">
                <div id="search-results" style="margin-top: 1rem;"></div>
            </div>
            
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ФИО</th>
                            <th>Email</th>
                            <th>Телефон</th>
                            <th>Дата регистрации</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $client): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($client['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($client['email']); ?></td>
                            <td><?php echo htmlspecialchars($client['phone']); ?></td>
                            <td><?php echo date('d.m.Y', strtotime($client['created_at'])); ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary create-for-client" data-client-id="<?php echo $client['id']; ?>" data-client-name="<?php echo htmlspecialchars($client['full_name']); ?>">
                                    Оформить полис
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="card">
            <h3>Оформленные полисы</h3>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>№ полиса</th>
                            <th>Клиент</th>
                            <th>Тип</th>
                            <th>Сумма</th>
                            <th>Статус</th>
                            <th>Дата</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($policies as $policy): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($policy['policy_number']); ?></td>
                            <td><?php echo htmlspecialchars($policy['client_name']); ?></td>
                            <td><?php echo $policy['type'] === 'osago' ? 'ОСАГО' : ($policy['type'] === 'casco' ? 'КАСКО' : 'Здоровье'); ?></td>
                            <td><?php echo number_format($policy['premium'], 2); ?> ₽</td>
                            <td><span class="status-<?php echo $policy['status']; ?>"><?php echo $policy['status'] === 'active' ? 'Активен' : 'Ожидание'; ?></span></td>
                            <td><?php echo date('d.m.Y', strtotime($policy['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Модальное окно для оформления полиса -->
    <div id="policy-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999;">
        <div style="max-width: 600px; margin: 50px auto; background: white; border-radius: 10px; padding: 2rem;">
            <h3>Оформление полиса для <span id="modal-client-name"></span></h3>
            <form id="agent-policy-form">
                <input type="hidden" id="modal-client-id">
                <div class="form-group">
                    <label>Вид страхования</label>
                    <select id="policy-type" class="form-control" required>
                        <option value="osago">ОСАГО</option>
                        <option value="casco">КАСКО</option>
                        <option value="health">Здоровье</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Сумма (предварительный расчёт)</label>
                    <input type="number" id="policy-premium" class="form-control" step="100" required>
                </div>
                <div class="form-group">
                    <label>Данные полиса (JSON)</label>
                    <textarea id="policy-data" class="form-control" rows="3" placeholder='{"vehicle":"Lada","power":90,"driver_age":30,"experience":10}' required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Оформить</button>
                <button type="button" class="btn" onclick="closeModal()">Отмена</button>
            </form>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
    <script src="/polygon-insurance/assets/js/main.js"></script>
    <script>
        function closeModal() {
            document.getElementById('policy-modal').style.display = 'none';
        }
        
        document.querySelectorAll('.create-for-client').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('modal-client-id').value = btn.dataset.clientId;
                document.getElementById('modal-client-name').textContent = btn.dataset.clientName;
                document.getElementById('policy-modal').style.display = 'block';
            });
        });
        
        document.getElementById('agent-policy-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const data = {
                client_id: document.getElementById('modal-client-id').value,
                type: document.getElementById('policy-type').value,
                premium: document.getElementById('policy-premium').value,
                data: document.getElementById('policy-data').value
            };
            
            const response = await fetch('/polygon-insurance/api?action=agent_create_policy', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            });
            const result = await response.json();
            
            if (result.success) {
                alert('Полис успешно оформлен!');
                location.reload();
            } else {
                alert('Ошибка: ' + result.error);
            }
        });
        
        // Поиск клиентов
        const searchInput = document.getElementById('client-search');
        if (searchInput) {
            searchInput.addEventListener('input', debounce(async (e) => {
                const search = e.target.value;
                if (search.length < 2) return;
                
                const response = await fetch(`/polygon-insurance/api?action=search_clients&search=${encodeURIComponent(search)}`);
                const clients = await response.json();
                
                const resultsDiv = document.getElementById('search-results');
                resultsDiv.innerHTML = clients.map(c => `
                    <div style="padding: 10px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between;">
                        <span><strong>${c.full_name}</strong> (${c.email})</span>
                        <button class="btn btn-sm btn-primary" onclick="selectClient(${c.id}, '${c.full_name}')">Выбрать</button>
                    </div>
                `).join('');
            }, 300));
        }
        
        function selectClient(id, name) {
            document.getElementById('modal-client-id').value = id;
            document.getElementById('modal-client-name').textContent = name;
            document.getElementById('client-search').value = '';
            document.getElementById('search-results').innerHTML = '';
            document.getElementById('policy-modal').style.display = 'block';
        }
        
        function debounce(fn, delay) {
            let timer;
            return function(...args) {
                clearTimeout(timer);
                timer = setTimeout(() => fn.apply(this, args), delay);
            };
        }
    </script>
</body>
</html>