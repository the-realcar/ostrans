<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zarządzanie Liniami i Brygadami - PPUT Ostrans</title>
    <link rel="stylesheet" href="/panel/panel_dark.css">
    <style>
        .management-container { max-width: 1400px; margin: 20px auto; padding: 20px; }
        .section { background: #2a2a2a; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
        .section h2 { color: #4CAF50; margin-bottom: 15px; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #444; }
        table th { background: #333; color: #4CAF50; font-weight: bold; }
        table tr:hover { background: #333; }
        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; margin-right: 5px; }
        .btn-primary { background: #4CAF50; color: white; }
        .btn-secondary { background: #2196F3; color: white; }
        .btn-danger { background: #f44336; color: white; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); }
        .modal-content { background: #2a2a2a; margin: 5% auto; padding: 30px; border-radius: 8px; width: 90%; max-width: 600px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h3 { color: #4CAF50; margin: 0; }
        .close { color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: #ccc; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; background: #333; border: 1px solid #555; border-radius: 4px; color: #fff; }
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; }
        .tab { padding: 10px 20px; background: #333; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        .tab.active { background: #4CAF50; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .badge { padding: 4px 8px; border-radius: 3px; font-size: 12px; }
        .badge-dzienna { background: #2196F3; color: white; }
        .badge-nocna { background: #673AB7; color: white; }
    </style>
</head>
<body>
    <div class="management-container">
        <h1>Zarządzanie Liniami i Brygadami</h1>
        
        <div class="tabs">
            <button class="tab active" onclick="switchTab('lines')">Linie</button>
            <button class="tab" onclick="switchTab('brigades')">Brygady</button>
        </div>

        <div id="lines-tab" class="tab-content active">
            <div class="section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h2>Linie</h2>
                    <button class="btn btn-primary" onclick="showLineModal()">+ Dodaj Linię</button>
                </div>
                <div class="table-container">
                    <table id="lines-table">
                        <thead>
                            <tr><th>ID</th><th>Numer</th><th>Typ</th><th>Początek</th><th>Koniec</th><th>Akcje</th></tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="6" style="text-align: center;">Ładowanie...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="brigades-tab" class="tab-content">
            <div class="section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h2>Brygady</h2>
                    <button class="btn btn-primary" onclick="showBrigadeModal()">+ Dodaj Brygadę</button>
                </div>
                <div class="table-container">
                    <table id="brigades-table">
                        <thead>
                            <tr><th>ID</th><th>Nazwa</th><th>Linia</th><th>Typ Brygady</th><th>Akcje</th></tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="5" style="text-align: center;">Ładowanie...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="line-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="line-modal-title">Dodaj Linię</h3>
                <span class="close" onclick="closeLineModal()">&times;</span>
            </div>
            <form id="line-form">
                <input type="hidden" id="line-id">
                <div class="form-group">
                    <label>Numer Linii *</label>
                    <input type="text" id="line-nr" required>
                </div>
                <div class="form-group">
                    <label>Typ *</label>
                    <select id="line-typ" required>
                        <option value="bus">Autobus</option>
                        <option value="tram">Tramwaj</option>
                        <option value="trol">Trolejbus</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Początek</label>
                    <input type="text" id="line-start">
                </div>
                <div class="form-group">
                    <label>Koniec</label>
                    <input type="text" id="line-end">
                </div>
                <div style="text-align: right;">
                    <button type="button" class="btn btn-secondary" onclick="closeLineModal()">Anuluj</button>
                    <button type="submit" class="btn btn-primary">Zapisz</button>
                </div>
            </form>
        </div>
    </div>

    <div id="brigade-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="brigade-modal-title">Dodaj Brygadę</h3>
                <span class="close" onclick="closeBrigadeModal()">&times;</span>
            </div>
            <form id="brigade-form">
                <input type="hidden" id="brigade-id">
                <div class="form-group">
                    <label>Nazwa *</label>
                    <input type="text" id="brigade-nazwa" required>
                </div>
                <div class="form-group">
                    <label>Linia</label>
                    <select id="brigade-linia">
                        <option value="">-- Wybierz --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Typ *</label>
                    <select id="brigade-typ" required>
                        <option value="dzienna">Dzienna</option>
                        <option value="nocna">Nocna</option>
                    </select>
                </div>
                <div style="text-align: right;">
                    <button type="button" class="btn btn-secondary" onclick="closeBrigadeModal()">Anuluj</button>
                    <button type="submit" class="btn btn-primary">Zapisz</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const API_BASE = '/panel/api.php';
        let authToken = localStorage.getItem('auth_token');
        let linesData = [], brigadesData = [];

        async function apiCall(endpoint, method = 'GET', body = null) {
            const options = { method, headers: { 'Authorization': `Bearer ${authToken}`, 'Content-Type': 'application/json' } };
            if (body) options.body = JSON.stringify(body);
            const response = await fetch(API_BASE + endpoint, options);
            if (!response.ok) throw new Error((await response.json()).error || 'Failed');
            return response.json();
        }

        function switchTab(tab) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            if (tab === 'lines') {
                document.querySelector('.tab:nth-child(1)').classList.add('active');
                document.getElementById('lines-tab').classList.add('active');
                loadLines();
            } else {
                document.querySelector('.tab:nth-child(2)').classList.add('active');
                document.getElementById('brigades-tab').classList.add('active');
                loadBrigades();
            }
        }

        async function loadLines() {
            try {
                linesData = await apiCall('/linie');
                document.querySelector('#lines-table tbody').innerHTML = linesData.length ? linesData.map(l => `
                    <tr>
                        <td>${l.id}</td>
                        <td>${l.nr_linii}</td>
                        <td>${l.typ || '-'}</td>
                        <td>${l.start_point || '-'}</td>
                        <td>${l.end_point || '-'}</td>
                        <td>
                            <button class="btn btn-secondary" onclick="editLine(${l.id})">Edytuj</button>
                            <button class="btn btn-danger" onclick="deleteLine(${l.id})">Usuń</button>
                        </td>
                    </tr>
                `).join('') : '<tr><td colspan="6" style="text-align:center;">Brak linii</td></tr>';
            } catch (e) { alert('Błąd: ' + e.message); }
        }

        async function loadBrigades() {
            try {
                brigadesData = await apiCall('/brygady');
                document.querySelector('#brigades-table tbody').innerHTML = brigadesData.length ? brigadesData.map(b => `
                    <tr>
                        <td>${b.id}</td>
                        <td>${b.nazwa}</td>
                        <td>${b.nr_linii || '-'}</td>
                        <td><span class="badge badge-${b.typ_brygady || 'dzienna'}">${b.typ_brygady || 'dzienna'}</span></td>
                        <td>
                            <button class="btn btn-secondary" onclick="editBrigade(${b.id})">Edytuj</button>
                            <button class="btn btn-danger" onclick="deleteBrigade(${b.id})">Usuń</button>
                        </td>
                    </tr>
                `).join('') : '<tr><td colspan="5" style="text-align:center;">Brak brygad</td></tr>';
            } catch (e) { alert('Błąd: ' + e.message); }
        }

        function showLineModal(lineId = null) {
            document.getElementById('line-modal').style.display = 'block';
            document.getElementById('line-form').reset();
            document.getElementById('line-id').value = '';
            document.getElementById('line-modal-title').textContent = 'Dodaj Linię';
            if (lineId) {
                const line = linesData.find(l => l.id === lineId);
                if (line) {
                    document.getElementById('line-id').value = line.id;
                    document.getElementById('line-nr').value = line.nr_linii;
                    document.getElementById('line-typ').value = line.typ || 'bus';
                    document.getElementById('line-start').value = line.start_point || '';
                    document.getElementById('line-end').value = line.end_point || '';
                    document.getElementById('line-modal-title').textContent = 'Edytuj Linię';
                }
            }
        }

        function closeLineModal() { document.getElementById('line-modal').style.display = 'none'; }
        function editLine(id) { showLineModal(id); }
        async function deleteLine(id) {
            if (!confirm('Usunąć linię?')) return;
            try {
                await apiCall(`/admin/linia/${id}`, 'DELETE');
                alert('Usunięto');
                loadLines();
            } catch (e) { alert('Błąd: ' + e.message); }
        }

        document.getElementById('line-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const id = document.getElementById('line-id').value;
            const data = {
                nr_linii: document.getElementById('line-nr').value,
                typ: document.getElementById('line-typ').value,
                start_point: document.getElementById('line-start').value,
                end_point: document.getElementById('line-end').value
            };
            try {
                await apiCall(id ? `/admin/linia/${id}` : '/admin/linia', id ? 'PUT' : 'POST', data);
                alert('Zapisano');
                closeLineModal();
                loadLines();
            } catch (e) { alert('Błąd: ' + e.message); }
        });

        function showBrigadeModal(brigadeId = null) {
            document.getElementById('brigade-modal').style.display = 'block';
            document.getElementById('brigade-form').reset();
            document.getElementById('brigade-id').value = '';
            document.getElementById('brigade-modal-title').textContent = 'Dodaj Brygadę';
            document.getElementById('brigade-linia').innerHTML = '<option value="">-- Wybierz --</option>' + 
                linesData.map(l => `<option value="${l.id}">${l.nr_linii}</option>`).join('');
            if (brigadeId) {
                const brigade = brigadesData.find(b => b.id === brigadeId);
                if (brigade) {
                    document.getElementById('brigade-id').value = brigade.id;
                    document.getElementById('brigade-nazwa').value = brigade.nazwa;
                    document.getElementById('brigade-linia').value = brigade.linia_id || '';
                    document.getElementById('brigade-typ').value = brigade.typ_brygady || 'dzienna';
                    document.getElementById('brigade-modal-title').textContent = 'Edytuj Brygadę';
                }
            }
        }

        function closeBrigadeModal() { document.getElementById('brigade-modal').style.display = 'none'; }
        function editBrigade(id) { showBrigadeModal(id); }
        async function deleteBrigade(id) {
            if (!confirm('Usunąć brygadę?')) return;
            try {
                await apiCall(`/admin/brygada/${id}`, 'DELETE');
                alert('Usunięto');
                loadBrigades();
            } catch (e) { alert('Błąd: ' + e.message); }
        }

        document.getElementById('brigade-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const id = document.getElementById('brigade-id').value;
            const data = {
                nazwa: document.getElementById('brigade-nazwa').value,
                linia_id: document.getElementById('brigade-linia').value || null,
                typ_brygady: document.getElementById('brigade-typ').value
            };
            try {
                await apiCall(id ? `/admin/brygada/${id}` : '/admin/brygada', id ? 'PUT' : 'POST', data);
                alert('Zapisano');
                closeBrigadeModal();
                loadBrigades();
            } catch (e) { alert('Błąd: ' + e.message); }
        });

        (async () => {
            if (!authToken) {
                alert('Nie jesteś zalogowany');
                window.location.href = '/panel/index.php';
                return;
            }
            await loadLines();
        })();

        window.onclick = (e) => { if (e.target.classList.contains('modal')) e.target.style.display = 'none'; }
    </script>
</body>
</html>
