<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои полисы - Полигон-страхование</title>
    <link rel="stylesheet" href="/polygon-insurance/assets/css/style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="card">
            <h2 class="card-title">Мои страховые полисы</h2>
            <p>Управление вашими страховыми продуктами</p>
        </div>
        
        <div class="card">
            <!-- Фильтры и поиск -->
            <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem;">
                <div style="flex: 1;">
                    <input type="text" id="search-policies" class="form-control" placeholder="Поиск по номеру полиса или типу...">
                </div>
                <div>
                    <select id="filter-status" class="form-control">
                        <option value="all">Все статусы</option>
                        <option value="active">Активные</option>
                        <option value="pending">Ожидание</option>
                        <option value="expired">Истекшие</option>
                        <option value="cancelled">Отмененные</option>
                    </select>
                </div>
                <div>
                    <select id="filter-type" class="form-control">
                        <option value="all">Все типы</option>
                        <option value="osago">ОСАГО</option>
                        <option value="casco">КАСКО</option>
                        <option value="health">Здоровье</option>
                    </select>
                </div>
                <div>
                    <button id="reset-filters" class="btn btn-secondary">Сбросить</button>
                </div>
            </div>
            
            <!-- Статистика полисов -->
            <div id="stats-container" style="display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem; padding: 1rem; background: #f5f5f5; border-radius: 10px;">
                <div><strong>Всего полисов:</strong> <span id="total-count">0</span></div>
                <div><strong>Активных:</strong> <span id="active-count">0</span></div>
                <div><strong>Общая сумма:</strong> <span id="total-premium">0</span> ₽</div>
            </div>
            
            <!-- Таблица полисов -->
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>№ полиса</th>
                            <th>Тип</th>
                            <th>Страховая сумма</th>
                            <th>Статус</th>
                            <th>Дата оформления</th>
                            <th>Действует до</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody id="policies-table-body">
                        <tr>
                            <td colspan="7" style="text-align: center;">Загрузка...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Пагинация -->
            <div id="pagination" style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 1.5rem;"></div>
        </div>
        
        <div class="card">
            <h3>Краткая информация</h3>
            <div class="alert alert-info">
                <p>📄 <strong>Как скачать PDF-полис?</strong> Нажмите на кнопку "PDF" в строке нужного полиса. Документ будет сгенерирован автоматически.</p>
                <p>🔄 <strong>Что делать при истечении срока?</strong> Оформите новый полис через <a href="/polygon-insurance/calculator">калькулятор</a>.</p>
                <p>📞 <strong>Нужна помощь?</strong> Свяжитесь с нами по телефону +7 (999) 123-45-67</p>
            </div>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
    
    <script>
        // Текущая страница и фильтры
        let currentPage = 1;
        let currentFilters = {
            search: '',
            status: 'all',
            type: 'all'
        };
        
        // Загрузка полисов
        async function loadPolicies() {
            try {
                const params = new URLSearchParams({
                    page: currentPage,
                    ...currentFilters
                });
                
                const response = await fetch(`/polygon-insurance/api?action=get_user_policies&${params.toString()}`);
                const data = await response.json();
                
                if (data.success) {
                    renderPoliciesTable(data.policies);
                    renderPagination(data.pagination);
                    updateStats(data.policies);
                } else {
                    showError(data.error || 'Ошибка загрузки полисов');
                }
            } catch (error) {
                showError('Ошибка соединения с сервером');
            }
        }
        
        // Отображение таблицы
        function renderPoliciesTable(policies) {
            const tbody = document.getElementById('policies-table-body');
            
            if (!policies || policies.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">У вас пока нет оформленных полисов. <a href="/polygon-insurance/calculator">Оформить первый полис</a></td></tr>';
                return;
            }
            
            tbody.innerHTML = policies.map(policy => {
                let statusClass = '';
                let statusText = '';
                
                switch(policy.status) {
                    case 'active':
                        statusClass = 'status-active';
                        statusText = 'Активен ✓';
                        break;
                    case 'pending':
                        statusClass = 'status-pending';
                        statusText = 'Ожидание ⏳';
                        break;
                    case 'expired':
                        statusClass = 'status-expired';
                        statusText = 'Истек ✗';
                        break;
                    case 'cancelled':
                        statusClass = 'status-expired';
                        statusText = 'Отменен ✗';
                        break;
                    default:
                        statusText = policy.status;
                }
                
                let typeText = '';
                switch(policy.type) {
                    case 'osago': typeText = '🚗 ОСАГО'; break;
                    case 'casco': typeText = '🚙 КАСКО'; break;
                    case 'health': typeText = '🏥 Здоровье'; break;
                    default: typeText = policy.type;
                }
                
                return `
                    <tr class="policy-row" data-status="${policy.status}" data-type="${policy.type}" data-policy-id="${policy.id}">
                        <td><strong>${escapeHtml(policy.policy_number)}</strong></td>
                        <td>${typeText}</td>
                        <td>${formatPrice(policy.premium)} ₽</td>
                        <td><span class="${statusClass}">${statusText}</span></td>
                        <td>${formatDate(policy.created_at)}</td>
                        <td>${policy.valid_to ? formatDate(policy.valid_to) : '—'}</td>
                        <td>
                            <button class="btn btn-sm btn-primary pdf-download" data-policy-id="${policy.id}" title="Скачать PDF">
                                📄 PDF
                            </button>
                            <button class="btn btn-sm btn-info view-details" data-policy-id="${policy.id}" data-policy-data='${escapeHtml(JSON.stringify(policy.data_json || {}))}' title="Детали полиса">
                                🔍 Детали
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
            
            // Привязываем обработчики к кнопкам
            attachButtonHandlers();
        }
        
        // Привязка обработчиков к динамическим кнопкам
        function attachButtonHandlers() {
            document.querySelectorAll('.pdf-download').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    e.preventDefault();
                    const policyId = btn.dataset.policyId;
                    await generatePDF(policyId);
                });
            });
            
            document.querySelectorAll('.view-details').forEach(btn => {
                btn.addEventListener('click', () => {
                    const policyId = btn.dataset.policyId;
                    const policyData = btn.dataset.policyData;
                    showPolicyDetails(policyId, policyData);
                });
            });
        }
        
        // Генерация PDF
        async function generatePDF(policyId) {
            showLoading(true);
            try {
                const response = await fetch(`/polygon-insurance/api?action=generate_pdf&policy_id=${policyId}`);
                const data = await response.json();
                
                if (data.success && data.url) {
                    window.open(data.url, '_blank');
                } else {
                    showError('Ошибка генерации PDF: ' + (data.error || 'Неизвестная ошибка'));
                }
            } catch (error) {
                showError('Ошибка соединения с сервером');
            } finally {
                showLoading(false);
            }
        }
        
        // Показ деталей полиса в модальном окне
        function showPolicyDetails(policyId, policyData) {
            // Создаем модальное окно
            let modal = document.getElementById('policy-details-modal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'policy-details-modal';
                modal.style.cssText = `
                    display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
                    background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center;
                `;
                document.body.appendChild(modal);
            }
            
            let dataHtml = '';
            try {
                const data = JSON.parse(policyData || '{}');
                dataHtml = Object.entries(data).map(([key, value]) => 
                    `<div><strong>${key}:</strong> ${escapeHtml(String(value))}</div>`
                ).join('');
            } catch(e) {
                dataHtml = `<div>${escapeHtml(policyData || 'Нет дополнительных данных')}</div>`;
            }
            
            modal.innerHTML = `
                <div style="max-width: 500px; background: white; border-radius: 10px; padding: 2rem; margin: 20px;">
                    <h3>Детали полиса №${policyId}</h3>
                    <div style="margin: 1rem 0; padding: 1rem; background: #f5f5f5; border-radius: 5px;">
                        ${dataHtml}
                    </div>
                    <button class="btn btn-primary" onclick="document.getElementById('policy-details-modal').style.display='none'">Закрыть</button>
                </div>
            `;
            modal.style.display = 'flex';
        }
        
        // Пагинация
        function renderPagination(pagination) {
            const container = document.getElementById('pagination');
            if (!pagination || pagination.total_pages <= 1) {
                container.innerHTML = '';
                return;
            }
            
            let html = '';
            if (pagination.current_page > 1) {
                html += `<button class="btn btn-sm" onclick="goToPage(${pagination.current_page - 1})">← Назад</button>`;
            }
            
            for (let i = 1; i <= pagination.total_pages; i++) {
                if (i === pagination.current_page) {
                    html += `<button class="btn btn-sm btn-primary" disabled>${i}</button>`;
                } else if (Math.abs(i - pagination.current_page) <= 2) {
                    html += `<button class="btn btn-sm" onclick="goToPage(${i})">${i}</button>`;
                } else if (i === 1 || i === pagination.total_pages) {
                    html += `<button class="btn btn-sm" onclick="goToPage(${i})">${i}</button>`;
                } else if (Math.abs(i - pagination.current_page) === 3) {
                    html += `<span>...</span>`;
                }
            }
            
            if (pagination.current_page < pagination.total_pages) {
                html += `<button class="btn btn-sm" onclick="goToPage(${pagination.current_page + 1})">Вперед →</button>`;
            }
            
            container.innerHTML = html;
        }
        
        function goToPage(page) {
            currentPage = page;
            loadPolicies();
        }
        
        // Обновление статистики
        function updateStats(policies) {
            const totalCount = policies.length;
            const activeCount = policies.filter(p => p.status === 'active').length;
            const totalPremium = policies.reduce((sum, p) => sum + parseFloat(p.premium || 0), 0);
            
            document.getElementById('total-count').textContent = totalCount;
            document.getElementById('active-count').textContent = activeCount;
            document.getElementById('total-premium').textContent = formatPrice(totalPremium);
        }
        
        // Фильтрация на клиенте (для таблицы)
        function applyClientFilters() {
            const searchText = currentFilters.search.toLowerCase();
            const statusFilter = currentFilters.status;
            const typeFilter = currentFilters.type;
            
            const rows = document.querySelectorAll('.policy-row');
            let visibleCount = 0;
            
            rows.forEach(row => {
                let show = true;
                const text = row.innerText.toLowerCase();
                const status = row.dataset.status;
                const type = row.dataset.type;
                
                if (searchText && !text.includes(searchText)) show = false;
                if (statusFilter !== 'all' && status !== statusFilter) show = false;
                if (typeFilter !== 'all' && type !== typeFilter) show = false;
                
                row.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });
        }
        
        // Обработчики фильтров
        document.getElementById('search-policies')?.addEventListener('input', (e) => {
            currentFilters.search = e.target.value;
            currentPage = 1;
            loadPolicies();
        });
        
        document.getElementById('filter-status')?.addEventListener('change', (e) => {
            currentFilters.status = e.target.value;
            currentPage = 1;
            loadPolicies();
        });
        
        document.getElementById('filter-type')?.addEventListener('change', (e) => {
            currentFilters.type = e.target.value;
            currentPage = 1;
            loadPolicies();
        });
        
        document.getElementById('reset-filters')?.addEventListener('click', () => {
            currentFilters = { search: '', status: 'all', type: 'all' };
            currentPage = 1;
            document.getElementById('search-policies').value = '';
            document.getElementById('filter-status').value = 'all';
            document.getElementById('filter-type').value = 'all';
            loadPolicies();
        });
        
        // Вспомогательные функции
        function formatPrice(price) {
            return new Intl.NumberFormat('ru-RU').format(parseFloat(price || 0));
        }
        
        function formatDate(dateString) {
            if (!dateString) return '—';
            const date = new Date(dateString);
            return date.toLocaleDateString('ru-RU');
        }
        
        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>]/g, function(m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                return m;
            });
        }
        
        function showError(message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'alert alert-danger';
            errorDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 300px;';
            errorDiv.textContent = message;
            document.body.appendChild(errorDiv);
            setTimeout(() => errorDiv.remove(), 3000);
        }
        
        function showLoading(show) {
            let loader = document.getElementById('loading-overlay');
            if (show) {
                if (!loader) {
                    loader = document.createElement('div');
                    loader.id = 'loading-overlay';
                    loader.style.cssText = `
                        position: fixed; top: 0; left: 0; right: 0; bottom: 0;
                        background: rgba(0,0,0,0.5); display: flex; align-items: center;
                        justify-content: center; z-index: 9999;
                    `;
                    loader.innerHTML = '<div style="background: white; padding: 20px; border-radius: 10px;">Загрузка...</div>';
                    document.body.appendChild(loader);
                }
                loader.style.display = 'flex';
            } else if (loader) {
                loader.style.display = 'none';
            }
        }
        
        // Инициализация
        document.addEventListener('DOMContentLoaded', () => {
            loadPolicies();
        });
        
        // Обновление статистики при фильтрации через API (для точности)
        async function updateStatsFromAPI() {
            try {
                const response = await fetch('/polygon-insurance/api?action=get_user_stats');
                const data = await response.json();
                if (data.success) {
                    document.getElementById('total-count').textContent = data.total_count;
                    document.getElementById('active-count').textContent = data.active_count;
                    document.getElementById('total-premium').textContent = formatPrice(data.total_premium);
                }
            } catch(e) {}
        }
        updateStatsFromAPI();
    </script>
</body>
</html>