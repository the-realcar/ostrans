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

// --- LOAD env.txt FALLBACK (jeśli .env nie jest używany) ---
(function loadEnvTxt() {
  const envPath = path.join(__dirname, '..', 'env.txt'); // repo root env.txt
  try {
    if (fs.existsSync(envPath)) {
      const txt = fs.readFileSync(envPath, 'utf8');
      txt.split(/\r?\n/).forEach(line => {
        line = line.trim();
        if (!line || line.startsWith('#') || line.indexOf('=') === -1) return;
        const [k, ...rest] = line.split('=');
        let v = rest.join('=').trim();
        if ((v.startsWith('"') && v.endsWith('"')) || (v.startsWith("'") && v.endsWith("'"))) v = v.slice(1, -1);
        if (!(k in process.env) || !process.env[k]) process.env[k] = v;
      });
      console.log('Loaded env.txt into process.env (fallback).');
    }
  } catch (e) {
    console.warn('Failed to load env.txt fallback:', e && e.message);
  }
})();

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

// --- Prosty redirect do Discord OAuth ---
app.get('/auth/discord', (req, res) => {
  const clientId = process.env.DISCORD_CLIENT_ID;
  const redirectUri = process.env.DISCORD_REDIRECT_URI || `${MAIN_SITE}/auth/discord/callback`;
  if (!clientId) {
    console.error('DISCORD_CLIENT_ID not configured. Check .env or env.txt');
    return res.status(500).send('DISCORD_CLIENT_ID not configured');
  }
  const url = `https://discord.com/api/oauth2/authorize?client_id=${encodeURIComponent(clientId)}&redirect_uri=${encodeURIComponent(redirectUri)}&response_type=code&scope=identify%20email`;
  console.log('Redirecting to Discord OAuth:', url);
  res.redirect(url);
});

// --- Discord OAuth callback: exchange code -> token, get user, find/create pracownik, determine uprawnienie from guild roles ---
app.get('/auth/discord/callback', async (req, res) => {
  const code = req.query.code;
  if (!code) return res.status(400).send('missing code');
  const clientId = process.env.DISCORD_CLIENT_ID;
  const clientSecret = process.env.DISCORD_CLIENT_SECRET;
  const redirectUri = process.env.DISCORD_REDIRECT_URI || `${MAIN_SITE}/auth/discord/callback`;

  try {
    // exchange code for token
    const params = new URLSearchParams();
    params.append('client_id', clientId);
    params.append('client_secret', clientSecret);
    params.append('grant_type', 'authorization_code');
    params.append('code', code);
    params.append('redirect_uri', redirectUri);

    const tokenRes = await fetch('https://discord.com/api/oauth2/token', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: params.toString()
    });
    if (!tokenRes.ok) {
      console.error('token exchange failed', await tokenRes.text());
      return res.status(500).send('Discord token exchange failed');
    }
    const tokenData = await tokenRes.json();
    const accessToken = tokenData.access_token;

    // get user info
    const userRes = await fetch('https://discord.com/api/users/@me', {
      headers: { Authorization: `Bearer ${accessToken}` }
    });
    if (!userRes.ok) {
      console.error('discord user fetch failed', await userRes.text());
      return res.status(500).send('Discord user fetch failed');
    }
    const duser = await userRes.json(); // id, username, discriminator, email (if permitted)

    // --- DETERMINE ROLES FROM GUILD (requires DISCORD_GUILD_ID + DISCORD_BOT_TOKEN) ---
    const guildId = process.env.DISCORD_GUILD_ID;
    const botToken = process.env.DISCORD_BOT_TOKEN;
    let mappedUprawnienie = null; // 'zarzad' | 'dyspozytor' | 'kierowca' (fallback kierowca)
    try {
      if (guildId && botToken) {
        const memberRes = await fetch(`https://discord.com/api/guilds/${guildId}/members/${duser.id}`, {
          headers: { Authorization: `Bot ${botToken}`, 'Content-Type': 'application/json' }
        });
        if (memberRes.ok) {
          const member = await memberRes.json(); // { roles: [...] }
          const roles = Array.isArray(member.roles) ? member.roles : [];
          const roleZ = process.env.ROLE_ZARZAD_ID;
          const roleD = process.env.ROLE_DYSP_ID;
          const roleK = process.env.ROLE_KIEROWCA_ID;
          if (roleZ && roles.includes(roleZ)) mappedUprawnienie = 'zarzad';
          else if (roleD && roles.includes(roleD)) mappedUprawnienie = 'dyspozytor';
          else if (roleK && roles.includes(roleK)) mappedUprawnienie = 'kierowca';
        } else {
          // member not found / bot lacks permission — ignore and fallback
          console.warn('guild member fetch failed', await memberRes.text());
        }
      }
    } catch (e) {
      console.warn('role fetch error', e);
    }

    // fallback default if mapping failed
    if (!mappedUprawnienie) mappedUprawnienie = 'kierowca';

    // find pracownik by discord_id
    const q = await db.query('SELECT p.*, u.poziom AS uprawnienie, p.uprawnienie_id FROM pracownicy p JOIN uprawnienia u ON p.uprawnienie_id=u.id WHERE p.discord_id=$1 LIMIT 1', [duser.id]);
    let user;
    if (q.rowCount > 0) {
      user = q.rows[0];
      // update uprawnienie in DB if different
      try {
        const wanted = mappedUprawnienie;
        if (user.uprawnienie !== wanted) {
          // find uprawnienie id
          const upq = await db.query('SELECT id FROM uprawnienia WHERE poziom=$1 LIMIT 1', [wanted]);
          const upId = upq.rowCount ? upq.rows[0].id : null;
          if (upId) {
            await db.query('UPDATE pracownicy SET uprawnienie_id=$1 WHERE id=$2', [upId, user.id]);
            user.uprawnienie = wanted;
          }
        }
      } catch (e) { console.warn('unable to update uprawnienie', e); }
    } else {
      // auto-create pracownik (minimal)
      try {
        // resolve uprawnienie_id for mappedUprawnienie
        const upq = await db.query('SELECT id FROM uprawnienia WHERE poziom=$1 LIMIT 1', [mappedUprawnienie]);
        let upId = upq.rowCount ? upq.rows[0].id : null;
        if (!upId) {
          const any = await db.query('SELECT id,poziom FROM uprawnienia LIMIT 1');
          upId = any.rowCount ? any.rows[0].id : null;
        }
        const login = `discord_${duser.id}`;
        const nameParts = (duser.username || '').split(' ');
        const imie = nameParts[0] || duser.username;
        const nazwisko = nameParts.slice(1).join('') || '';
        const ins = await db.query('INSERT INTO pracownicy (imie,nazwisko,login,discord_id,uprawnienie_id) VALUES ($1,$2,$3,$4,$5) RETURNING id,imie,nazwisko,login', [imie, nazwisko, login, duser.id, upId]);
        user = { id: ins.rows[0].id, imie: ins.rows[0].imie, nazwisko: ins.rows[0].nazwisko, login: ins.rows[0].login, uprawnienie: mappedUprawnienie };
      } catch (e) {
        console.error('auto-create user failed', e);
      }
    }

    if (!user) return res.status(403).send('User mapping failed');
    // sign JWT (include uprawnienie)
    const token = jwt.sign({ id: user.id, login: user.login, uprawnienie: user.uprawnienie || mappedUprawnienie }, JWT_SECRET, { expiresIn: '8h' });
    // redirect to panel with token in query (frontend will store it)
    const redirectTo = `${MAIN_SITE.replace(/\/$/,'')}/panel/index.php?token=${encodeURIComponent(token)}`;
    return res.redirect(redirectTo);
  } catch (e) {
    console.error(e);
    return res.status(500).send('server error');
  }
});

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
  res.sendFile(path.join(__dirname,'main','index.php')); // uses existing file as base grafik page
});
app.get('/panel/wnioski', authMiddleware, (req,res) => {
  res.sendFile(path.join(__dirname,'index.php')); // main panel index includes wnioski form
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
app.get('/admin', authMiddleware, (req, res) => {
  if (req.user.uprawnienie !== 'zarzad') return res.status(403).send('Forbidden');
  res.sendFile(path.join(__dirname,'admin','index.php'));
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

// --- ADDITIONAL API GET ENDPOINTS ---
app.get('/api/pojazdy', authMiddleware, async (req, res) => {
  try {
    const q = await db.query('SELECT * FROM pojazdy ORDER BY id');
    res.json(q.rows);
  } catch (e) { console.error(e); res.status(500).json([]); }
});

app.get('/api/grafik', authMiddleware, async (req, res) => {
  try {
    const userId = req.query.userId;
    if (userId) {
      const q = await db.query('SELECT * FROM grafiki WHERE pracownik_id=$1 ORDER BY data', [parseInt(userId,10)]);
      return res.json(q.rows);
    }
    const q = await db.query('SELECT * FROM grafiki ORDER BY data LIMIT 500');
    res.json(q.rows);
  } catch (e) { console.error(e); res.status(500).json([]); }
});

app.get('/api/linie', authMiddleware, async (req, res) => {
  try {
    const q = await db.query('SELECT * FROM linie ORDER BY id');
    res.json(q.rows);
  } catch (e) { console.error(e); res.status(500).json([]); }
});

app.get('/api/pracownicy', authMiddleware, requireZarzad, async (req, res) => {
  try {
    const q = await db.query('SELECT p.id,p.imie,p.nazwisko,p.login,u.poziom as uprawnienie FROM pracownicy p JOIN uprawnienia u ON p.uprawnienie_id=u.id ORDER BY p.id');
    res.json(q.rows);
  } catch (e) { console.error(e); res.status(500).json([]); }
});

// Endpoint do ręcznego/automatycznego odświeżenia roli użytkownika z Discord (używa bot token)
app.post('/api/sync-role', authMiddleware, async (req, res) => {
  try {
    // pobierz discord_id pracownika z DB
    const q = await db.query('SELECT discord_id FROM pracownicy WHERE id=$1 LIMIT 1', [req.user.id]);
    if (q.rowCount === 0) return res.status(404).json({ error: 'no user' });
    const discordId = q.rows[0].discord_id;
    if (!discordId) return res.status(400).json({ error: 'no discord_id' });

    const guildId = process.env.DISCORD_GUILD_ID;
    const botToken = process.env.DISCORD_BOT_TOKEN;
    if (!guildId || !botToken) return res.status(500).json({ error: 'discord guild/bot not configured' });

    const memberRes = await fetch(`https://discord.com/api/guilds/${guildId}/members/${discordId}`, {
      headers: { Authorization: `Bot ${botToken}`, 'Content-Type': 'application/json' }
    });
    if (!memberRes.ok) return res.status(500).json({ error: 'failed to fetch member' });
    const member = await memberRes.json();
    const roles = Array.isArray(member.roles) ? member.roles : [];
    const roleZ = process.env.ROLE_ZARZAD_ID;
    const roleD = process.env.ROLE_DYSP_ID;
    const roleK = process.env.ROLE_KIEROWCA_ID;
    let mapped = 'kierowca';
    if (roleZ && roles.includes(roleZ)) mapped = 'zarzad';
    else if (roleD && roles.includes(roleD)) mapped = 'dyspozytor';
    else if (roleK && roles.includes(roleK)) mapped = 'kierowca';

    // get uprawnienie_id
    const upq = await db.query('SELECT id FROM uprawnienia WHERE poziom=$1 LIMIT 1', [mapped]);
    const upId = upq.rowCount ? upq.rows[0].id : null;
    if (upId) {
      await db.query('UPDATE pracownicy SET uprawnienie_id=$1 WHERE id=$2', [upId, req.user.id]);
    }
    res.json({ ok:true, uprawnienie: mapped });
  } catch (e) {
    console.error(e);
    res.status(500).json({ error:'server' });
  }
});

app.listen(PORT, () => console.log(`Server running on port ${PORT}`));
