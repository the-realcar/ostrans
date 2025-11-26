/* Zastępuje demo logikę i łączy panel z backendem REST (/api). 
   W produkcji serwować panel z tego samego hosta lub ustawić CORS. */

const API_BASE = ''; // pusty = ten sam host (np. http://localhost:3000 jeśli hostujesz)
const TOKEN_KEY = 'ostrans_token';
const USER_KEY = 'ostrans_user_localdemo'; // tylko dla demo fallback

/* --- HELPERS --- */
function authFetch(path, opts = {}) {
  const headers = opts.headers || {};
  const token = localStorage.getItem(TOKEN_KEY);
  if (token) headers['Authorization'] = `Bearer ${token}`;
  opts.headers = headers;
  return fetch(API_BASE + path, opts).then(r => {
    if (!r.ok) throw r;
    return r.json();
  });
}

function showDashboardFor(user) {
  document.getElementById('authWrapper').style.display = 'none';
  const dash = document.getElementById('dashboard');
  dash.classList.remove('hidden');
  document.getElementById('currentUser').textContent = `${user.imie ? user.imie + ' ' + user.nazwisko : user.name} (${user.role || user.uprawnienie})`;
  document.querySelectorAll('.role-view').forEach(el => el.classList.add('hidden'));
  const role = user.role || user.uprawnienie;
  if (role === 'kierowca') { document.getElementById('driverView').classList.remove('hidden'); loadGrafik(user); loadWnioski(user); }
  if (role === 'dyspozytor') { document.getElementById('dispoView').classList.remove('hidden'); loadLinieBrygady(); loadWnioski(user); }
  if (role === 'zarzad') { document.getElementById('adminView').classList.remove('hidden'); loadUsers(); loadWnioski(user); }
}

/* --- LOGIN (backend) --- */
document.getElementById('doLogin').addEventListener('click', async () => {
  const login = document.getElementById('loginInput').value.trim();
  const pass = document.getElementById('passInput').value;
  if (!login || !pass) return alert('Podaj login i hasło.');
  try {
    const res = await fetch(API_BASE + '/api/login', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({login, password: pass})
    });
    if (!res.ok) throw new Error('auth failed');
    const { token, user } = await res.json();
    localStorage.setItem(TOKEN_KEY, token);
    localStorage.setItem(USER_KEY, JSON.stringify(user));
    showDashboardFor(user);
  } catch (err) {
    // fallback demo — jeśli backend nie działa
    const demo = tryDemoLogin(login, pass);
    if (demo) { localStorage.setItem(USER_KEY, JSON.stringify(demo)); showDashboardFor(demo); }
    else alert('Logowanie nie powiodło się (spróbuj później).');
  }
});

/* Discord OAuth link (serwer powinien obsłużyć wymianę kodu) */
const discordClientId = 'YOUR_DISCORD_CLIENT_ID';
const redirectUri = encodeURIComponent('http://localhost:3000/auth/discord/callback'); // dopasuj
document.getElementById('discordLogin').href =
  `https://discord.com/api/oauth2/authorize?client_id=${discordClientId}&redirect_uri=${redirectUri}&response_type=code&scope=identify%20email`;

/* --- LOGOUT --- */
document.getElementById('logoutBtn').addEventListener('click', () => {
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem(USER_KEY);
  location.reload();
});

/* --- ROLE SWITCH (UI) --- */
document.querySelectorAll('[data-show]').forEach(btn => {
  btn.addEventListener('click', () => {
    const sel = btn.getAttribute('data-show');
    document.querySelectorAll('.role-view').forEach(el => el.classList.add('hidden'));
    document.getElementById(sel).classList.remove('hidden');
  });
});

/* --- WNIOSKI --- */
document.getElementById('wniosekForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const typ = document.getElementById('wniosekTyp').value;
  const opis = document.getElementById('wniosekOpis').value;
  const storedUser = JSON.parse(localStorage.getItem(USER_KEY) || 'null');
  try {
    if (!localStorage.getItem(TOKEN_KEY)) throw new Error('no token');
    await authFetch('/api/wnioski', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({typ, opis})
    });
    loadWnioski(storedUser);
    document.getElementById('wniosekOpis').value = '';
    alert('Wniosek wysłany.');
  } catch (err) {
    // fallback demo
    const user = storedUser || {id: 999, name: 'Demo', imie:'Demo', nazwisko:''};
    const key = `wnioski_user_${user.id}`;
    const current = JSON.parse(localStorage.getItem(key) || '[]');
    current.push({id: Date.now(), typ, opis, status:'oczekujący', data:new Date().toISOString()});
    localStorage.setItem(key, JSON.stringify(current));
    loadWnioski(user);
    document.getElementById('wniosekOpis').value = '';
    alert('Wniosek zapisany lokalnie (fallback).');
  }
});

async function loadWnioski(user) {
  const el = document.getElementById('wnioskiList');
  try {
    const data = await authFetch(`/api/wnioski${user && user.id ? `?userId=${user.id}` : ''}`);
    if (!Array.isArray(data)) throw new Error();
    if (data.length === 0) el.innerHTML = '<p>Brak wniosków.</p>';
    else el.innerHTML = data.map(w => `<div style="border-bottom:1px solid #eee;padding:8px 0;"><strong>${w.typ}</strong> — ${w.status} <div class="small-muted">${new Date(w.data_zlozenia || w.data).toLocaleString()}</div><div>${w.opis}</div></div>`).join('');
  } catch (_) {
    const key = `wnioski_user_${user.id || 0}`;
    const list = JSON.parse(localStorage.getItem(key) || '[]');
    if (list.length === 0) el.innerHTML = '<p>Brak wniosków.</p>';
    else el.innerHTML = list.map(w => `<div style="border-bottom:1px solid #eee;padding:8px 0;"><strong>${w.typ}</strong> — ${w.status} <div class="small-muted">${new Date(w.data).toLocaleString()}</div><div>${w.opis}</div></div>`).join('');
  }
}

/* --- GRAFIK --- */
async function loadGrafik(user) {
  const el = document.getElementById('grafik');
  try {
    const data = await authFetch(`/api/grafik?userId=${user.id}`);
    el.innerHTML = `<pre>${JSON.stringify(data, null, 2)}</pre>`;
  } catch (_) {
    el.innerHTML = `<p>Grafik dla ${user.imie ? user.imie + ' ' + user.nazwisko : user.name} (lokalny demo).</p>`;
  }
}

/* --- LINIE/Brygady --- */
async function loadLinieBrygady() {
  const el = document.getElementById('linieBrygady');
  try {
    const data = await authFetch('/api/linie');
    el.innerHTML = data.map(l => `<div><strong>${l.nazwa}</strong><div class="small-muted">${l.opis||''}</div></div>`).join('');
  } catch (_) {
    el.innerHTML = '<p>Linia 1 — Brygada A (demo). Backend required.</p>';
  }
}

/* --- PRACOWNICY (admin) --- */
async function loadUsers() {
  const el = document.getElementById('adminUsers');
  try {
    const data = await authFetch('/api/pracownicy');
    el.innerHTML = data.map(u => `<div>${u.imie} ${u.nazwisko} — ${u.login} — ${u.uprawnienie}</div>`).join('');
  } catch (_) {
    const demo = tryDemoUsers();
    el.innerHTML = demo.map(u => `<div>${u.name} — ${u.login} — ${u.role}</div>`).join('');
  }
}

/* --- DEMO FALLBACKS --- */
function tryDemoLogin(login, pass) {
  const demo = tryDemoUsers().find(u => u.login === login && u.pass === pass);
  if (demo) {
    // map demo shape to expected user
    const mapped = {id: demo.id, login: demo.login, imie: demo.name.split(' ')[0], nazwisko: demo.name.split(' ')[1]||'', uprawnienie: demo.role};
    localStorage.setItem(USER_KEY, JSON.stringify(mapped));
    return mapped;
  }
  return null;
}
function tryDemoUsers() {
  return [
    {id:1, login:'driver1', pass:'dpass', role:'kierowca', name:'Jan Kowalski'},
    {id:2, login:'dispo1', pass:'dpass', role:'dyspozytor', name:'Anna Nowak'},
    {id:3, login:'admin1', pass:'dpass', role:'zarzad', name:'Piotr Zarzad'}
  ];
}

/* --- AUTOLOGIN (jeśli token/user w localStorage) --- */
(async function init() {
  const storedUser = JSON.parse(localStorage.getItem(USER_KEY) || 'null');
  const token = localStorage.getItem(TOKEN_KEY);
  if (storedUser && token) {
    // pobierz opcjonalnie profil serwera, jeśli backend działa
    try {
      const me = await authFetch('/api/me');
      localStorage.setItem(USER_KEY, JSON.stringify(me.user || storedUser));
      showDashboardFor(me.user || storedUser);
    } catch (_) {
      showDashboardFor(storedUser);
    }
  }
})();
