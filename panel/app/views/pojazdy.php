<div class="container">
    <h2>Zarządzanie pojazdami</h2>
    
    <!-- Add new vehicle form -->
    <div class="form-section">
        <h3>Dodaj nowy pojazd</h3>
        <form id="addVehicleForm">
            <div class="form-row">
                <div class="form-group">
                    <label for="nr_rejestracyjny">Numer rejestracyjny *</label>
                    <input type="text" id="nr_rejestracyjny" name="nr_rejestracyjny" required placeholder="WS 1AB">
                </div>
                <div class="form-group">
                    <label for="marka">Marka *</label>
                    <input type="text" id="marka" name="marka" required placeholder="np. Volvo">
                </div>
                <div class="form-group">
                    <label for="model">Model *</label>
                    <input type="text" id="model" name="model" required placeholder="np. B7RLE">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="rok_produkcji">Rok produkcji</label>
                    <input type="number" id="rok_produkcji" name="rok_produkcji" min="1990" max="2099">
                </div>
                <div class="form-group">
                    <label for="sprawny">Status</label>
                    <select id="sprawny" name="sprawny">
                        <option value="true">Sprawny</option>
                        <option value="false">Niesprawny</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Dodaj pojazd</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Vehicles list -->
    <div class="table-section">
        <h3>Lista pojazdów</h3>
        <table id="vehiclesTable" class="data-table">
            <thead>
                <tr>
                    <th>Nr rejestracyjny</th>
                    <th>Marka</th>
                    <th>Model</th>
                    <th>Rok produkcji</th>
                    <th>Status</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <!-- Populated by JavaScript -->
            </tbody>
        </table>
    </div>
</div>

<style>
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .form-section {
        background: #f5f5f5;
        padding: 20px;
        border-radius: 5px;
        margin-bottom: 30px;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 15px;
    }
    
    .form-group {
        display: flex;
        flex-direction: column;
    }
    
    .form-group label {
        font-weight: bold;
        margin-bottom: 5px;
        font-size: 14px;
    }
    
    .form-group input,
    .form-group select {
        padding: 8px 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }
    
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
    }
    
    .btn-primary {
        background-color: #007bff;
        color: white;
    }
    
    .btn-primary:hover {
        background-color: #0056b3;
    }
    
    .btn-edit {
        background-color: #28a745;
        color: white;
        padding: 5px 10px;
        margin-right: 5px;
    }
    
    .btn-edit:hover {
        background-color: #218838;
    }
    
    .btn-delete {
        background-color: #dc3545;
        color: white;
        padding: 5px 10px;
    }
    
    .btn-delete:hover {
        background-color: #c82333;
    }
    
    .table-section {
        margin-top: 30px;
    }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
    }
    
    .data-table th {
        background-color: #007bff;
        color: white;
        padding: 12px;
        text-align: left;
        font-weight: bold;
    }
    
    .data-table td {
        padding: 10px 12px;
        border-bottom: 1px solid #ddd;
    }
    
    .data-table tr:hover {
        background-color: #f9f9f9;
    }
    
    .status-ok {
        color: #28a745;
        font-weight: bold;
    }
    
    .status-bad {
        color: #dc3545;
        font-weight: bold;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadVehicles();
    
    document.getElementById('addVehicleForm').addEventListener('submit', function(e) {
        e.preventDefault();
        addVehicle();
    });
});

function loadVehicles() {
    const token = localStorage.getItem('token');
    if (!token) {
        window.location.href = '/index.html';
        return;
    }
    
    fetch('/api/pojazdy', {
        headers: { 'Authorization': 'Bearer ' + token }
    })
    .then(r => r.json())
    .then(data => {
        const tbody = document.querySelector('#vehiclesTable tbody');
        tbody.innerHTML = '';
        
        if (data && data.length > 0) {
            data.forEach(v => {
                const row = document.createElement('tr');
                const status = v.sprawny ? '<span class="status-ok">✓ Sprawny</span>' : '<span class="status-bad">✗ Niesprawny</span>';
                row.innerHTML = `
                    <td>${v.nr_rejestracyjny}</td>
                    <td>${v.marka}</td>
                    <td>${v.model}</td>
                    <td>${v.rok_produkcji || '-'}</td>
                    <td>${status}</td>
                    <td>
                        <button class="btn btn-edit" onclick="editVehicle(${v.id})">Edytuj</button>
                        <button class="btn btn-delete" onclick="deleteVehicle(${v.id})">Usuń</button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="6">Brak pojazdów w systemie</td></tr>';
        }
    })
    .catch(err => {
        console.error('Błąd ładowania pojazdów:', err);
        alert('Nie udało się załadować listy pojazdów');
    });
}

function addVehicle() {
    const token = localStorage.getItem('token');
    const payload = {
        nr_rejestracyjny: document.getElementById('nr_rejestracyjny').value,
        marka: document.getElementById('marka').value,
        model: document.getElementById('model').value,
        rok_produkcji: document.getElementById('rok_produkcji').value,
        sprawny: document.getElementById('sprawny').value === 'true'
    };
    
    fetch('/api/admin/pojazd', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + token
        },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) {
            alert('Błąd: ' + data.error);
        } else {
            alert('Pojazd dodany pomyślnie');
            document.getElementById('addVehicleForm').reset();
            loadVehicles();
        }
    })
    .catch(err => {
        console.error('Błąd dodawania pojazdu:', err);
        alert('Nie udało się dodać pojazdu');
    });
}

function editVehicle(vehicleId) {
    const newStatus = prompt('Nowy status pojazdu (sprawny/niesprawny):');
    if (!newStatus) return;
    
    const token = localStorage.getItem('token');
    const payload = {
        sprawny: newStatus.toLowerCase().includes('sprawny')
    };
    
    fetch('/api/admin/pojazd/' + vehicleId, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + token
        },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) {
            alert('Błąd: ' + data.error);
        } else {
            alert('Pojazd zaktualizowany');
            loadVehicles();
        }
    })
    .catch(err => {
        console.error('Błąd aktualizacji:', err);
        alert('Nie udało się zaktualizować pojazdu');
    });
}

function deleteVehicle(vehicleId) {
    if (!confirm('Czy na pewno chcesz usunąć ten pojazd?')) return;
    
    const token = localStorage.getItem('token');
    fetch('/api/admin/pojazd/' + vehicleId, {
        method: 'DELETE',
        headers: { 'Authorization': 'Bearer ' + token }
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) {
            alert('Błąd: ' + data.error);
        } else {
            alert('Pojazd usunięty');
            loadVehicles();
        }
    })
    .catch(err => {
        console.error('Błąd usuwania:', err);
        alert('Nie udało się usunąć pojazdu');
    });
}
</script>
