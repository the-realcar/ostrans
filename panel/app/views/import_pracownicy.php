<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import pracowników CSV - PPUT Ostrans</title>
    <link rel="stylesheet" href="../panel_dark.css">
    <style>
        .import-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
        }
        
        .upload-area {
            border: 2px dashed var(--border);
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 20px 0;
        }
        
        .upload-area:hover {
            border-color: var(--accent);
            background: var(--hover-bg);
        }
        
        .upload-area.dragover {
            border-color: var(--accent);
            background: var(--glass);
        }
        
        .file-info {
            margin-top: 12px;
            color: var(--muted);
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: var(--glass);
            border-radius: 4px;
            overflow: hidden;
            margin: 12px 0;
            display: none;
        }
        
        .progress-bar.active {
            display: block;
        }
        
        .progress-fill {
            height: 100%;
            background: var(--accent);
            width: 0%;
            transition: width 0.3s ease;
        }
        
        .results {
            margin-top: 24px;
            display: none;
        }
        
        .results.active {
            display: block;
        }
        
        .result-item {
            padding: 12px;
            margin: 8px 0;
            border-radius: 8px;
            border-left: 4px solid;
        }
        
        .result-success {
            background: rgba(16, 185, 129, 0.1);
            border-left-color: var(--success);
        }
        
        .result-error {
            background: rgba(239, 68, 68, 0.1);
            border-left-color: var(--danger);
        }
        
        .result-warning {
            background: rgba(245, 158, 11, 0.1);
            border-left-color: var(--warning);
        }
        
        .csv-template {
            background: var(--card);
            padding: 16px;
            border-radius: 8px;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container import-container">
        <div class="card">
            <h1>Import pracowników z CSV</h1>
            <p class="small">Dodaj wielu pracowników jednocześnie używając pliku CSV</p>
            
            <div class="notice">
                <strong>Wymagany format CSV:</strong><br>
                imie,nazwisko,login,haslo,email,uprawnienie_id,stanowisko_id,discord_id
            </div>
            
            <div class="csv-template">
                <strong>Przykład pliku CSV:</strong><br>
                imie,nazwisko,login,haslo,email,uprawnienie_id,stanowisko_id,discord_id<br>
                Jan,Kowalski,jkowalski,Password123,jan@example.com,1,,<br>
                Anna,Nowak,anowak,SecurePass456,anna@example.com,2,5,123456789<br>
                Piotr,Wiśniewski,pwisniewski,MyPass789,piotr@example.com,1,3,
            </div>
            
            <div class="form-row">
                <div style="flex: 1;">
                    <strong>Uprawnienia (uprawnienie_id):</strong>
                    <ul class="small">
                        <li>1 - Kierowca</li>
                        <li>2 - Dyspozytor</li>
                        <li>3 - Zarząd</li>
                    </ul>
                </div>
            </div>
            
            <form id="importForm">
                <div class="upload-area" id="uploadArea">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    <h3>Przeciągnij plik CSV tutaj</h3>
                    <p class="small">lub kliknij, aby wybrać plik</p>
                    <input type="file" id="csvFile" name="csv" accept=".csv" style="display: none;">
                    <div class="file-info" id="fileInfo"></div>
                </div>
                
                <div class="progress-bar" id="progressBar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn" id="uploadBtn" disabled>
                        Importuj pracowników
                    </button>
                    <button type="button" class="btn alt" onclick="window.location.href='?route=employees'">
                        Anuluj
                    </button>
                    <a href="#" class="btn alt" id="downloadTemplate">
                        Pobierz szablon CSV
                    </a>
                </div>
            </form>
            
            <div class="results" id="results">
                <h3>Wyniki importu:</h3>
                <div id="resultsList"></div>
            </div>
        </div>
    </div>
    
    <script>
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('csvFile');
        const fileInfo = document.getElementById('fileInfo');
        const uploadBtn = document.getElementById('uploadBtn');
        const importForm = document.getElementById('importForm');
        const progressBar = document.getElementById('progressBar');
        const progressFill = document.getElementById('progressFill');
        const results = document.getElementById('results');
        const resultsList = document.getElementById('resultsList');
        
        // Get JWT token from localStorage
        const token = localStorage.getItem('jwt_token') || sessionStorage.getItem('jwt_token');
        
        if (!token) {
            alert('Musisz być zalogowany, aby importować pracowników');
            window.location.href = '?route=login';
        }
        
        // Click to select file
        uploadArea.addEventListener('click', () => fileInput.click());
        
        // Drag and drop
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                handleFileSelect();
            }
        });
        
        // File selection
        fileInput.addEventListener('change', handleFileSelect);
        
        function handleFileSelect() {
            const file = fileInput.files[0];
            
            if (!file) {
                return;
            }
            
            if (!file.name.endsWith('.csv')) {
                alert('Proszę wybrać plik CSV');
                fileInput.value = '';
                return;
            }
            
            const sizeMB = (file.size / 1024 / 1024).toFixed(2);
            fileInfo.innerHTML = `
                <strong>${file.name}</strong><br>
                Rozmiar: ${sizeMB} MB
            `;
            
            uploadBtn.disabled = false;
        }
        
        // Form submission
        importForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const file = fileInput.files[0];
            if (!file) {
                alert('Proszę wybrać plik CSV');
                return;
            }
            
            const formData = new FormData();
            formData.append('csv', file);
            
            // Show progress bar
            progressBar.classList.add('active');
            progressFill.style.width = '0%';
            uploadBtn.disabled = true;
            results.classList.remove('active');
            
            // Animate progress
            let progress = 0;
            const progressInterval = setInterval(() => {
                progress += Math.random() * 30;
                if (progress > 90) progress = 90;
                progressFill.style.width = progress + '%';
            }, 200);
            
            try {
                const response = await fetch('/ostrans/panel/api.php/api/admin/import/pracownicy', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + token
                    },
                    body: formData
                });
                
                clearInterval(progressInterval);
                progressFill.style.width = '100%';
                
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.error || 'Import failed');
                }
                
                // Display results
                displayResults(data.result);
                
                // Reset form after delay
                setTimeout(() => {
                    progressBar.classList.remove('active');
                    fileInput.value = '';
                    fileInfo.innerHTML = '';
                    uploadBtn.disabled = true;
                }, 2000);
                
            } catch (error) {
                clearInterval(progressInterval);
                progressBar.classList.remove('active');
                uploadBtn.disabled = false;
                
                alert('Błąd podczas importu: ' + error.message);
                console.error('Import error:', error);
            }
        });
        
        function displayResults(result) {
            results.classList.add('active');
            resultsList.innerHTML = '';
            
            // Summary
            const summary = document.createElement('div');
            summary.className = 'result-item result-success';
            summary.innerHTML = `
                <strong>Podsumowanie:</strong><br>
                ✓ Zaimportowano: ${result.imported}<br>
                ⚠ Pominięto: ${result.skipped}<br>
                ${result.errors.length > 0 ? '✗ Błędy: ' + result.errors.length : ''}
            `;
            resultsList.appendChild(summary);
            
            // Errors
            if (result.errors && result.errors.length > 0) {
                const errorsDiv = document.createElement('div');
                errorsDiv.innerHTML = '<h4>Błędy:</h4>';
                
                result.errors.forEach(error => {
                    const errorItem = document.createElement('div');
                    errorItem.className = 'result-item result-error';
                    errorItem.textContent = error;
                    errorsDiv.appendChild(errorItem);
                });
                
                resultsList.appendChild(errorsDiv);
            }
            
            // Success message
            if (result.imported > 0) {
                const successMsg = document.createElement('div');
                successMsg.className = 'result-item result-success';
                successMsg.innerHTML = `
                    <strong>✓ Sukces!</strong><br>
                    Pomyślnie zaimportowano ${result.imported} pracowników.<br>
                    <a href="?route=employees">Przejdź do listy pracowników</a>
                `;
                resultsList.appendChild(successMsg);
            }
        }
        
        // Download template
        document.getElementById('downloadTemplate').addEventListener('click', (e) => {
            e.preventDefault();
            
            const template = 'imie,nazwisko,login,haslo,email,uprawnienie_id,stanowisko_id,discord_id\n' +
                           'Jan,Kowalski,jkowalski,Password123,jan@example.com,1,,\n' +
                           'Anna,Nowak,anowak,SecurePass456,anna@example.com,2,5,123456789\n';
            
            const blob = new Blob([template], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'szablon_pracownicy.csv';
            link.click();
        });
    </script>
</body>
</html>
