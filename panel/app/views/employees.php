<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <title>Zarządzanie Pracownikami — Panel Admin</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="/panel/employee.css">
  <style>
    .admin-section { margin: 24px 0; }
    .employee-list { display: grid; gap: 12px; }
    .employee-card {
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 8px;
      padding: 12px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .employee-info { flex: 1; }
    .employee-actions { display: flex; gap: 8px; }
    .employee-actions button { padding: 6px 12px; font-size: 0.9rem; }
    .add-form { background: #f9f9f9; padding: 16px; border-radius: 8px; margin-bottom: 16px; }
    .form-group { margin: 10px 0; }
    .form-group label { display: block; font-size: 0.9rem; margin-bottom: 4px; }
    .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    .inactive { opacity: 0.6; text-decoration: line-through; }
  </style>
</head>
<body>
  <header class="panel-header">
    <div class="logo-title">
      <a href="/"><img src="https://ostrans.famisska.pl/logo.png" alt="Logo"></a>
      <span>Panel Admin — Zarządzanie Pracownikami</span>
    </div>
    <nav><a href="/?route=dashboard">Powrót</a></nav>
  </header>

  <main class="panel-main">
    <div class="admin-section">
      <h2>Dodaj Nowego Pracownika</h2>
      <form id="addEmployeeForm" class="add-form" onsubmit="handleAddEmployee(event)">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div class="form-group">
            <label>Imię *</label>
            <input type="text" name="imie" required>
          </div>
          <div class="form-group">
            <label>Nazwisko *</label>
            <input type="text" name="nazwisko" required>
          </div>
          <div class="form-group">
            <label>Login *</label>
            <input type="text" name="login" required>
          </div>
          <div class="form-group">
            <label>Hasło *</label>
            <input type="password" name="haslo" required>
          </div>
          <div class="form-group">
            <label>Uprawnienia *</label>
            <select name="uprawnienie_id" required>
              <option value="">Wybierz...</option>
              <option value="3">Kierowca</option>
              <option value="2">Dyspozytor</option>
              <option value="1">Zarząd</option>
            </select>
          </div>
          <div class="form-group">
            <label>Discord ID</label>
            <input type="text" name="discord_id">
          </div>
        </div>
        <button type="submit" style="margin-top: 10px;">Dodaj Pracownika</button>
      </form>
    </div>

    <div class="admin-section">
      <h2>Lista Pracowników</h2>
      <div id="employeeList" class="employee-list">
        <p style="color: #999;">Ładowanie...</p>
      </div>
    </div>

    <div class="admin-section">
      <h2>Dziennik Aktywności</h2>
      <div id="activityLog" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 12px; max-height: 400px; overflow-y: auto;">
        <p style="color: #999;">Ładowanie...</p>
      </div>
    </div>
  </main>

  <script src="/panel/panel.php"></script>
  <script>
    async function handleAddEmployee(e) {
      e.preventDefault();
      const form = e.target;
      const data = new FormData(form);
      const obj = Object.fromEntries(data);
      
      try {
        const res = await fetch('/api/admin/pracownik', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${localStorage.getItem('token')}` },
          body: JSON.stringify(obj)
        });
        if (!res.ok) {
          alert('Błąd: ' + (await res.text()));
          return;
        }
        alert('Pracownik dodany pomyślnie');
        form.reset();
        loadEmployees();
      } catch (e) {
        alert('Błąd sieci: ' + e.message);
      }
    }

    async function loadEmployees() {
      try {
        const res = await fetch('/api/admin/pracownicy', {
          headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
        });
        if (!res.ok) return;
        const employees = await res.json();
        const list = document.getElementById('employeeList');
        
        if (employees.length === 0) {
          list.innerHTML = '<p style="color:#999;">Brak pracowników.</p>';
          return;
        }
        
        list.innerHTML = employees.map(e => `
          <div class="employee-card ${e.is_active === false ? 'inactive' : ''}">
            <div class="employee-info">
              <strong>${e.imie} ${e.nazwisko}</strong><br>
              <small>${e.login} — ${e.uprawnienie}</small>
              ${e.is_active === false ? '<br><small style="color:red;">[Nieaktywny]</small>' : ''}
            </div>
            <div class="employee-actions">
              <button onclick="editEmployee(${e.id})">Edytuj</button>
              ${e.is_active !== false ? `<button onclick="deactivateEmployee(${e.id})">Deaktywuj</button>` : ''}
            </div>
          </div>
        `).join('');
      } catch (e) {
        console.error('Failed to load employees:', e);
      }
    }

    function editEmployee(id) {
      const newName = prompt('Nowe imię (pozostaw puste, aby pominąć):');
      if (newName === null) return;
      
      const data = {};
      if (newName) data.imie = newName;
      data.id = id;
      
      fetch('/api/admin/pracownik', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${localStorage.getItem('token')}` },
        body: JSON.stringify(data)
      }).then(r => r.ok ? loadEmployees() : alert('Błąd')).catch(e => alert(e.message));
    }

    async function deactivateEmployee(id) {
      if (!confirm('Czy na pewno chcesz deaktywować tego pracownika?')) return;
      
      try {
        const res = await fetch(`/api/admin/employee/${id}/deactivate`, {
          method: 'POST',
          headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
        });
        if (res.ok) {
          alert('Pracownik deaktywowany');
          loadEmployees();
        }
      } catch (e) {
        alert('Błąd: ' + e.message);
      }
    }

    async function loadActivityLog() {
      try {
        const res = await fetch('/api/activity-log', {
          headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
        });
        if (!res.ok) return;
        const logs = await res.json();
        const logDiv = document.getElementById('activityLog');
        
        if (logs.length === 0) {
          logDiv.innerHTML = '<p style="color:#999;">Brak zarejestrowanych działań.</p>';
          return;
        }
        
        logDiv.innerHTML = logs.slice(0, 50).map(log => `
          <div style="padding: 8px; border-bottom: 1px solid #eee; font-size: 0.85rem;">
            <strong>${log.action}</strong> — ${log.entity_type} (#${log.entity_id})<br>
            <small style="color:#999;">${new Date(log.created_at).toLocaleString('pl-PL')}</small>
          </div>
        `).join('');
      } catch (e) {
        console.error('Failed to load activity log:', e);
      }
    }

    // Load on page load
    loadEmployees();
    loadActivityLog();
  </script>
</body>
</html>
