/* Minimalny backend Express do podłączenia panelu do bazy PostgreSQL.
   Instalacja: npm install express pg dotenv bcrypt jsonwebtoken node-fetch
   Uruchom: node server.js
*/
const express = require('express');
const bodyParser = require('body-parser');
const jwt = require('jsonwebtoken');
const bcrypt = require('bcrypt'); // jeśli hasła będą haszowane
const fetch = require('node-fetch');
const db = require('./db');
require('dotenv').config();

const app = express();
app.use(bodyParser.json());

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

app.post('/api/wnioski', authMiddleware, async (req, res) => {
  const { typ, opis } = req.body;
  if (!typ) return res.status(400).json({error:'missing typ'});
  try {
    const q = await db.query('INSERT INTO wnioski (pracownik_id, typ, opis) VALUES ($1,$2,$3) RETURNING *', [req.user.id, typ, opis || null]);
    res.json(q.rows[0]);
  } catch (e) { console.error(e); res.status(500).json({error:'server'}); }
});

// Grafiki: GET /api/grafik?userId=
app.get('/api/grafik', authMiddleware, async (req, res) => {
  const user = req.user;
  const qUser = req.query.userId ? req.query.userId : user.id;
  try {
    // proste zapytanie — rozbudować według potrzeb (JOINy z brygady/pojazdy)
    const q = await db.query('SELECT * FROM grafiki WHERE pracownik_id=$1 ORDER BY data DESC LIMIT 100', [qUser]);
    res.json(q.rows);
  } catch (e) { console.error(e); res.status(500).json({error:'server'}); }
});

// Linie + brygady
app.get('/api/linie', authMiddleware, async (req, res) => {
  try {
    const l = await db.query('SELECT * FROM linie ORDER BY nazwa');
    res.json(l.rows);
  } catch (e) { console.error(e); res.status(500).json({error:'server'}); }
});

// Pracownicy (admin/zarzad)
app.get('/api/pracownicy', authMiddleware, async (req, res) => {
  if (req.user.uprawnienie !== 'zarzad') return res.status(403).json({error:'forbidden'});
  try {
    const q = await db.query(`SELECT p.id,p.imie,p.nazwisko,p.login,u.poziom AS uprawnienie,s.nazwa AS stanowisko
                              FROM pracownicy p
                              JOIN uprawnienia u ON p.uprawnienie_id=u.id
                              JOIN stanowiska s ON p.stanowisko_id=s.id
                              ORDER BY p.nazwisko`);
    res.json(q.rows);
  } catch (e) { console.error(e); res.status(500).json({error:'server'}); }
});

/* Discord OAuth callback (szkielet)
   Serwer powinien wymienić code -> token, pobrać dane użytkownika i powiązać z pracownicy.discord_id lub stworzyć wpis.
*/
app.get('/auth/discord/callback', async (req, res) => {
  const code = req.query.code;
  if (!code) return res.status(400).send('no code');
  try {
    const params = new URLSearchParams();
    params.append('client_id', process.env.DISCORD_CLIENT_ID);
    params.append('client_secret', process.env.DISCORD_CLIENT_SECRET);
    params.append('grant_type', 'authorization_code');
    params.append('code', code);
    params.append('redirect_uri', process.env.DISCORD_REDIRECT_URI);
    const tokenRes = await fetch('https://discord.com/api/oauth2/token', {
      method:'POST', body: params, headers: {'Content-Type':'application/x-www-form-urlencoded'}
    });
    const tokenJson = await tokenRes.json();
    const userRes = await fetch('https://discord.com/api/users/@me', {headers:{Authorization:`Bearer ${tokenJson.access_token}`}});
    const userJson = await userRes.json();
    // tutaj: find pracownicy by discord_id or email -> create/join -> sign jwt
    // prosty komunikat dla demo:
    res.send('Discord callback otrzymano. Zaimplementuj powiązanie konta na serwerze.');
  } catch (e) {
    console.error(e); res.status(500).send('error');
  }
});

app.listen(PORT, () => console.log(`Server running on port ${PORT}`));
