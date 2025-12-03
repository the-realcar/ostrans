/* Minimalny backend Express do podłączenia panelu do bazy PostgreSQL.
   Instalacja: npm install express pg dotenv bcrypt jsonwebtoken node-fetch multer
   Uruchom: node server.js
*/
const express = require('express');
const bodyParser = require('body-parser');
const jwt = require('jsonwebtoken');
const bcrypt = require('bcrypt'); // jeśli hasła będą haszowane
const fetch = require('node-fetch');
const multer = require('multer');
const fs = require('fs');
require('dotenv').config();
const path = require('path');
const { Pool } = require('pg');

const app = express();
app.use(bodyParser.json());

// --- Konfiguracja bazy danych (Pool) ---
const pool = new Pool({
  connectionString: process.env.DATABASE_URL || process.env.PG_CONNECTION || 'postgresql://user:pass@localhost:5432/ostrans'
});
const db = {
  query: (text, params) => pool.query(text, params),
  pool
};

// add base site address constant (used in generated pages)
const MAIN_SITE = process.env.MAIN_SITE || 'https://ostrans.famisska.pl';

// uploads folder for zgłoszenia
const UPLOAD_DIR = path.join(__dirname, 'uploads');
if (!fs.existsSync(UPLOAD_DIR)) fs.mkdirSync(UPLOAD_DIR, { recursive: true });

const storage = multer.diskStorage({
  destination: (req, file, cb) => cb(null, UPLOAD_DIR),
  filename: (req, file, cb) => {
    const name = Date.now() + '-' + Math.random().toString(36).slice(2,9) + path.extname(file.originalname);
    cb(null, name);
  }
});
const upload = multer({ storage, limits:{ fileSize: 5*1024*1024 } }); // 5MB limit per file

// --- Prosty CORS / preflight (jeśli panel serwowany z innego hosta) ---
app.use((req, res, next) => {
  res.setHeader('Access-Control-Allow-Origin', process.env.ALLOW_ORIGIN || '*');
  res.setHeader('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept, Authorization');
  res.setHeader('Access-Control-Allow-Methods', 'GET,POST,PUT,DELETE,OPTIONS');
  if (req.method === 'OPTIONS') return res.sendStatus(200);
  next();
});

// Serwowanie plików statycznych (panel)
app.use(express.static(__dirname));
app.get('/', (req, res) => res.sendFile(path.join(__dirname, 'index.html')));

// konfiguracja
const JWT_SECRET = process.env.JWT_SECRET || 'change_this_secret';
const PORT = process.env.PORT || 3000;

// middleware auth
function authMiddleware(req, res, next) {
  const h = req.headers['authorization'];
  if (!h) return res.status(401).json({error:'no auth'});
  const parts = h.split(' ');
  if (parts[0] !== 'Bearer' || !parts[1]) return res.status(401).json({error:'bad auth'});
  try {
    const payload = jwt.verify(parts[1], JWT_SECRET);
    req.user = payload;
    next();
  } catch (e) {
    return res.status(401).json({error:'invalid token'});
  }
}

// --- DODATKOWE MIDDLEWARE ---
function requireZarzad(req, res, next) {
  if (!req.user || req.user.uprawnienie !== 'zarzad') return res.status(403).json({ error: 'forbidden' });
  next();
}

function requireDyspozytor(req, res, next) {
  if (!req.user) return res.status(401).json({ error: 'no auth' });
  if (req.user.uprawnienie === 'dyspozytor' || req.user.uprawnienie === 'zarzad') return next();
  return res.status(403).json({ error:'forbidden' });
}

// POST /api/login {login,password}
app.post('/api/login', async (req, res) => {
  const { login, password } = req.body;
  if (!login || !password) return res.status(400).json({error:'missing'});
  try {
    const q = await db.query('SELECT p.*, u.poziom AS uprawnienie FROM pracownicy p JOIN uprawnienia u ON p.uprawnienie_id=u.id WHERE p.login=$1 LIMIT 1', [login]);
    if (q.rowCount === 0) return res.status(401).json({error:'invalid'});
    const user = q.rows[0];
    const stored = user.haslo || '';
    let ok = false;
    if (stored.startsWith('$2b$') || stored.startsWith('$2a$')) {
      ok = await bcrypt.compare(password, stored);
    } else {
      // legacy plaintext (zalecane: migracja do hashowania)
      ok = (password === stored);
    }
    if (!ok) return res.status(401).json({error:'invalid'});
    const token = jwt.sign({id:user.id, login:user.login, uprawnienie:user.uprawnienie}, JWT_SECRET, {expiresIn:'8h'});
    res.json({token, user:{id:user.id, imie:user.imie, nazwisko:user.nazwisko, login:user.login, uprawnienie:user.uprawnienie}});
  } catch (err) {
    console.error(err);
    res.status(500).json({error:'server'});
  }
});

// GET /api/me
app.get('/api/me', authMiddleware, async (req, res) => {
  try {
    const q = await db.query('SELECT p.id, p.imie, p.nazwisko, p.login, u.poziom as uprawnienie FROM pracownicy p JOIN uprawnienia u ON p.uprawnienie_id=u.id WHERE p.id=$1', [req.user.id]);
    if (q.rowCount===0) return res.status(404).json({error:'no user'});
    res.json({user: q.rows[0]});
  } catch (e) { res.status(500).json({error:'server'}); }
});

// Wnioski: GET (dla kierowcy swoje lub dla wyższych filtrowane), POST
app.get('/api/wnioski', authMiddleware, async (req, res) => {
  const user = req.user;
  const userId = req.query.userId;
  try {
    if (user.uprawnienie === 'kierowca') {
      const q = await db.query('SELECT * FROM wnioski WHERE pracownik_id=$1 ORDER BY data_zlozenia DESC', [user.id]);
      return res.json(q.rows);
    }
    // dyspozytor/zarzad: jeśli userId podane, zwróć tylko dla niego, inaczej wszystkie
    if (userId) {
      const q = await db.query('SELECT * FROM wnioski WHERE pracownik_id=$1 ORDER BY data_zlozenia DESC', [userId]);
      return res.json(q.rows);
    }
    const q = await db.query('SELECT * FROM wnioski ORDER BY data_zlozenia DESC LIMIT 500');
    res.json(q.rows);
  } catch (e) { console.error(e); res.status(500).json({error:'server'}); }
});

// POST /api/wnioski  (walidacja pól wg typu)
app.post('/api/wnioski', authMiddleware, async (req, res) => {
  const { typ, opis } = req.body;
  if (!typ) return res.status(400).json({ error: 'missing typ' });

  // required fields map
  const required = {
    'KZW': ['data_wykonania','proponowany_pojazd'],
    'anulowanie_służby': ['sluzba_id','powod'],
    'dzień_wolny': ['data','powod'],
    'urlop': ['od','do','powod'],
    'stały_pojazd': ['pojazd_id'],
    'zmiana_stałego_pojazdu': ['pojazd_id'],
    'nieprzydzielanie_pojazdów': ['pojazdy','powod'],
    'zmiana_etatu': ['dni','powod'],
    'zwolnienie': ['powod']
  };

  const key = typ; // expecting typ keys matching above (you may map labels)
  const reqFields = required[key];
  if (!reqFields) return res.status(400).json({ error: 'unsupported typ' });

  // check fields presence
  for (const f of reqFields) {
    if (req.body[f] === undefined || req.body[f] === null || (typeof req.body[f] === 'string' && req.body[f].trim()==='')) {
      return res.status(400).json({ error: `missing field ${f} for type ${typ}` });
    }
  }

  try {
    const insertQ = `INSERT INTO wnioski (pracownik_id, typ, opis) VALUES ($1,$2,$3) RETURNING *`;
    // store generic wniosek row; detailed data can be stored in jsonb column or separate table (here we keep simple)
    const q = await db.query(insertQ, [req.user.id, typ, opis || null]);
    const wniosek = q.rows[0];

    // optional: save extra fields into table wnioski_meta (json) - create table if needed
    try {
      await db.query(`CREATE TABLE IF NOT EXISTS wnioski_meta (id SERIAL PRIMARY KEY, wniosek_id INT REFERENCES wnioski(id), meta JSONB)`);
    } catch (e) { /* ignore */ }
    await db.query('INSERT INTO wnioski_meta (wniosek_id, meta) VALUES ($1,$2)', [wniosek.id, JSON.stringify(req.body)]);

    res.json({ ok:true, wniosek });
  } catch (e) { console.error(e); res.status(500).json({ error:'server' }); }
});

// POST zgłoszenia (wypadek/awaria) z uploadem max 5 plików
app.post('/api/zgloszenia', authMiddleware, upload.array('dowody',5), async (req, res) => {
  try {
    const { pojazd_id, data_zdarzenia, opis, wyjasnienie, uwagi } = req.body;
    if (!pojazd_id || !data_zdarzenia || !opis) return res.status(400).json({ error:'missing' });
    // Save meta and files
    const files = (req.files||[]).map(f => ({ path: path.relative(__dirname, f.path), originalname: f.originalname }));
    try {
      await db.query(`CREATE TABLE IF NOT EXISTS zgloszenia (id SERIAL PRIMARY KEY, pracownik_id INT, pojazd_id INT, data_zdarzenia TIMESTAMP, opis TEXT, wyjasnienie TEXT, uwagi TEXT, files JSONB, created_at TIMESTAMP DEFAULT NOW(), FOREIGN KEY (pracownik_id) REFERENCES pracownicy(id))`);
    } catch(e){ }
    const q = await db.query('INSERT INTO zgloszenia (pracownik_id, pojazd_id, data_zdarzenia, opis, wyjasnienie, uwagi, files) VALUES ($1,$2,$3,$4,$5,$6,$7) RETURNING *', [req.user.id, parseInt(pojazd_id,10), data_zdarzenia, opis, wyjasnienie||null, uwagi||null, JSON.stringify(files)]);
    res.json({ ok:true, zgloszenie: q.rows[0] });
  } catch (e) { console.error(e); res.status(500).json({ error:'server' }); }
});

// RAPORTY endpoints (proste)
app.get('/api/raporty/pending', authMiddleware, async (req, res) => {
  try {
    // raporty do wykonania: grafiki z datą = today lub przyszłe bez raportu
    const q = await db.query(`SELECT g.* FROM grafiki g LEFT JOIN raporty r ON r.grafik_id=g.id WHERE r.id IS NULL AND g.data<=CURRENT_DATE+1 ORDER BY g.data`);
    res.json(q.rows);
  } catch(e){ console.error(e); res.status(500).json({error:'server'}); }
});
app.get('/api/raporty/sent', authMiddleware, async (req, res) => {
  try {
    const q = await db.query('SELECT * FROM raporty ORDER BY created_at DESC LIMIT 200');
    res.json(q.rows);
  } catch(e){ console.error(e); res.status(500).json({error:'server'}); }
});
app.get('/api/raporty/cancelled', authMiddleware, async (req, res) => {
  try {
    const q = await db.query("SELECT * FROM grafiki WHERE status='anulowana' ORDER BY data DESC");
    res.json(q.rows);
  } catch(e){ console.error(e); res.status(500).json({error:'server'}); }
});

// Proste serwowanie stron panelu zgodnie z wymaganiami (kierowca/dyspozytor/zarzad)
app.get('/panel/grafik', authMiddleware, (req,res) => {
  // only drivers+dyspozytor+zarzad
  res.sendFile(path.join(__dirname,'main','index.html')); // uses existing file as base grafik page
});
app.get('/panel/wnioski', authMiddleware, (req,res) => {
  res.sendFile(path.join(__dirname,'index.html')); // main panel index includes wnioski form
});
app.get('/panel/raporty', authMiddleware, (req,res) => {
  // simple listing page (frontend can call /api/raporty/*)
  res.send(`<html><head><meta charset="utf-8"><title>Raporty</title></head><body><h2>Raporty</h2><p>Użyj /api/raporty/* endpointów</p></body></html>`);
});
app.get('/panel/zgloszenia', authMiddleware, (req,res) => {
  res.send(`<html><head><meta charset="utf-8"><title>Zgłoszenia</title></head><body><h2>Zgłoszenia</h2><p>Formularz dostępny w panelu — wysyłaj na /api/zgloszenia</p></body></html>`);
});
app.get('/panel/wyslij-raport', authMiddleware, (req,res) => {
  res.send(`<html><head><meta charset="utf-8"><title>Wyślij raport</title></head><body><h2>Wyślij raport</h2><p>Strona do wysyłki raportów (frontend -> /api/raporty)</p></body></html>`);
});

// --- STRONA ADMINA (formularze) ---
app.get('/admin', authMiddleware, async (req, res) => {
  if (req.user.uprawnienie !== 'zarzad') return res.status(403).send('Forbidden');
  res.send(`
    <!doctype html>
    <html lang="pl">
    <head><meta charset="utf-8"><title>Panel danych — Ostrans</title>
    <style>
      .sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
      .form-section{margin-bottom:18px}
    </style>
    </head>
    <body>
      <a class="sr-only skip-link" href="#adminMain">Pomiń do treści</a>
      <main id="adminMain" role="main">
        <h2>Panel danych (tylko Zarząd)</h2>

        <section class="form-section" aria-labelledby="pojazdTitle">
          <h3 id="pojazdTitle">Dodaj / Edytuj pojazd</h3>
          <form id="pojazdForm" aria-describedby="pojazdNote">
            <p id="pojazdNote" class="sr-only">Formularz do dodawania lub edycji pojazdów</p>
            <label class="sr-only" for="p_id">Id (opcjonalne)</label>
            <input id="p_id" name="id" placeholder="id (opcjonalne)"><br>
            <label class="sr-only" for="p_nr">Numer rejestracyjny</label>
            <input id="p_nr" name="nr_rejestracyjny" placeholder="nr_rejestracyjny" required><br>
            <label class="sr-only" for="p_marka">Marka</label>
            <input id="p_marka" name="marka" placeholder="marka"><br>
            <label class="sr-only" for="p_model">Model</label>
            <input id="p_model" name="model" placeholder="model"><br>
            <label class="sr-only" for="p_rok">Rok produkcji</label>
            <input id="p_rok" name="rok_produkcji" placeholder="rok_produkcji" type="number"><br>
            <label><input id="p_sprawny" name="sprawny" type="checkbox" checked> Sprawny</label><br>
            <button type="submit">Zapisz pojazd</button>
          </form>
          <pre id="pojazdResult" role="status" aria-live="polite"></pre>
        </section>

        <!-- analogicznie dla pracownika, rejestracji i grafiku: etykiety .sr-only -->
        <section class="form-section" aria-labelledby="pracTitle">
          <h3 id="pracTitle">Dodaj pracownika</h3>
          <form id="pracForm">
            <label class="sr-only" for="pr_imie">Imię</label><input id="pr_imie" name="imie" placeholder="Imię" required><br>
            <label class="sr-only" for="pr_nazw">Nazwisko</label><input id="pr_nazw" name="nazwisko" placeholder="Nazwisko" required><br>
            <label class="sr-only" for="pr_login">Login</label><input id="pr_login" name="login" placeholder="login" required><br>
            <label class="sr-only" for="pr_haslo">Hasło</label><input id="pr_haslo" name="haslo" placeholder="hasło" required><br>
            <label class="sr-only" for="pr_stan">Stanowisko id</label><input id="pr_stan" name="stanowisko_id" placeholder="stanowisko_id" type="number" required><br>
            <label class="sr-only" for="pr_up">Uprawnienie id</label><input id="pr_up" name="uprawnienie_id" placeholder="uprawnienie_id" type="number" required><br>
            <button type="submit">Dodaj pracownika</button>
          </form>
          <pre id="pracResult" role="status" aria-live="polite"></pre>
        </section>

        <section class="form-section" aria-labelledby="rejTitle">
          <h3 id="rejTitle">Dodaj rejestrację</h3>
          <form id="rejForm">
            <label class="sr-only" for="r_poj">Id pojazdu</label><input id="r_poj" name="id_pojazdu" placeholder="id_pojazdu" required><br>
            <label class="sr-only" for="r_rej">Rejestracja</label><input id="r_rej" name="rejestracja" placeholder="rejestracja" required><br>
            <button type="submit">Dodaj rejestrację</button>
          </form>
          <pre id="rejResult" role="status" aria-live="polite"></pre>
        </section>

        <section class="form-section" aria-labelledby="grafTitle">
          <h3 id="grafTitle">Dodaj wpis grafiku</h3>
          <form id="grafForm">
            <label class="sr-only" for="g_pr">Id pracownika</label><input id="g_pr" name="pracownik_id" placeholder="pracownik_id" required><br>
            <label class="sr-only" for="g_data">Data</label><input id="g_data" name="data" placeholder="data YYYY-MM-DD" required><br>
            <label class="sr-only" for="g_bry">Brygada id</label><input id="g_bry" name="brygada_id" placeholder="brygada_id (opcjonalne)"><br>
            <label class="sr-only" for="g_poj">Pojazd id</label><input id="g_poj" name="pojazd_id" placeholder="pojazd_id (opcjonalne)"><br>
            <button type="submit">Dodaj grafik</button>
          </form>
          <pre id="grafResult" role="status" aria-live="polite"></pre>
        </section>

      </main>

      <script>
        const token = localStorage.getItem('ostrans_token');
        function apiPost(path, body) {
          return fetch(path, {
            method:'POST',
            headers: { 'Content-Type':'application/json', 'Authorization': token ? 'Bearer ' + token : '' },
            body: JSON.stringify(body)
          }).then(r => r.json().catch(()=>({status:r.status, text:r.statusText})));
        }

        document.getElementById('pojazdForm').addEventListener('submit', async (e)=>{
          e.preventDefault();
          const f = Object.fromEntries(new FormData(e.target).entries());
          f.sprawny = e.target.sprawny.checked;
          const res = await apiPost('/api/admin/pojazd', f);
          document.getElementById('pojazdResult').textContent = JSON.stringify(res, null, 2);
        });

        document.getElementById('pracForm').addEventListener('submit', async (e)=>{
          e.preventDefault();
          const f = Object.fromEntries(new FormData(e.target).entries());
          const res = await apiPost('/api/admin/pracownik', f);
          document.getElementById('pracResult').textContent = JSON.stringify(res, null, 2);
        });

        document.getElementById('rejForm').addEventListener('submit', async (e)=>{
          e.preventDefault();
          const f = Object.fromEntries(new FormData(e.target).entries());
          const res = await apiPost('/api/admin/rejestracja', f);
          document.getElementById('rejResult').textContent = JSON.stringify(res, null, 2);
        });

        document.getElementById('grafForm').addEventListener('submit', async (e)=>{
          e.preventDefault();
          const f = Object.fromEntries(new FormData(e.target).entries());
          const res = await apiPost('/api/admin/grafik', f);
          document.getElementById('grafResult').textContent = JSON.stringify(res, null, 2);
        });
      </script>
    </body></html>
  `);
});

// --- API: dodawanie danych (tylko zarzad) ---

// Dodaj / aktualizuj pojazd
app.post('/api/admin/pojazd', authMiddleware, requireZarzad, async (req, res) => {
  try {
    const { id, nr_rejestracyjny, marka, model, rok_produkcji, sprawny } = req.body;
    if (!nr_rejestracyjny) return res.status(400).json({ error: 'nr_rejestracyjny required' });

    let useId = id ? parseInt(id,10) : null;
    if (!useId) {
      const qmax = await db.query('SELECT MAX(id) AS maxid FROM pojazdy');
      const maxid = qmax.rows[0] && qmax.rows[0].maxid ? parseInt(qmax.rows[0].maxid,10) : 1000;
      useId = maxid + 1;
    }
    // upsert-like: spróbuj INSERT, jeśli błąd PK -> UPDATE
    try {
      await db.query('INSERT INTO pojazdy (id, nr_rejestracyjny, marka, model, rok_produkcji, sprawny) VALUES ($1,$2,$3,$4,$5,$6)', [useId, nr_rejestracyjny, marka||null, model||null, rok_produkcji ? parseInt(rok_produkcji,10) : null, (!!sprawny)]);
    } catch (e) {
      // jeśli istnieje, zaktualizuj
      await db.query('UPDATE pojazdy SET nr_rejestracyjny=$2, marka=$3, model=$4, rok_produkcji=$5, sprawny=$6 WHERE id=$1', [useId, nr_rejestracyjny, marka||null, model||null, rok_produkcji ? parseInt(rok_produkcji,10) : null, (!!sprawny)]);
    }
    const created = (await db.query('SELECT * FROM pojazdy WHERE id=$1', [useId])).rows[0];
    res.json({ ok:true, pojazd: created });
  } catch (err) { console.error(err); res.status(500).json({ error:'server' }); }
});

// Dodaj pracownika (hasło haszowane)
app.post('/api/admin/pracownik', authMiddleware, requireZarzad, async (req, res) => {
  try {
    const { imie, nazwisko, login, haslo, stanowisko_id, uprawnienie_id, discord_id } = req.body;
    if (!imie || !nazwisko || !login || !haslo || !stanowisko_id || !uprawnienie_id) return res.status(400).json({ error: 'missing' });
    const hashed = await bcrypt.hash(haslo, 10);
    const q = await db.query('INSERT INTO pracownicy (imie, nazwisko, login, haslo, discord_id, stanowisko_id, uprawnienie_id) VALUES ($1,$2,$3,$4,$5,$6,$7) RETURNING id,imie,nazwisko,login', [imie, nazwisko, login, hashed, discord_id||null, parseInt(stanowisko_id,10), parseInt(uprawnienie_id,10)]);
    res.json({ ok:true, pracownik: q.rows[0] });
  } catch (err) { console.error(err); res.status(500).json({ error:'server' }); }
});

// Dodaj rejestrację
app.post('/api/admin/rejestracja', authMiddleware, requireZarzad, async (req, res) => {
  try {
    const { id_pojazdu, rejestracja } = req.body;
    if (!id_pojazdu || !rejestracja) return res.status(400).json({ error: 'missing' });
    await db.query('INSERT INTO rejestracje (id_pojazdu, rejestracja) VALUES ($1,$2)', [parseInt(id_pojazdu,10), rejestracja]);
    res.json({ ok:true });
  } catch (err) { console.error(err); res.status(500).json({ error:'server' }); }
});

// Dodaj grafik
app.post('/api/admin/grafik', authMiddleware, requireZarzad, async (req, res) => {
  try {
    const { pracownik_id, data, brygada_id, pojazd_id } = req.body;
    if (!pracownik_id || !data) return res.status(400).json({ error: 'missing' });
    await db.query('INSERT INTO grafiki (pracownik_id, data, brygada_id, pojazd_id) VALUES ($1,$2,$3,$4)', [parseInt(pracownik_id,10), data, brygada_id ? parseInt(brygada_id,10) : null, pojazd_id ? parseInt(pojazd_id,10) : null]);
    res.json({ ok:true });
  } catch (err) { console.error(err); res.status(500).json({ error:'server' }); }
});

app.listen(PORT, () => console.log(`Server running on port ${PORT}`));
