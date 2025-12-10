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

/* --- ACCESSIBILITY: powiadomienia zamiast alert() --- */
function notify(msg, type = 'info') {
  const el = document.getElementById('appNotif');
  if (!el) { console.log(msg); return; }
  el.textContent = msg;
  // dodatkowo krótkie pokazanie dla wzroku (niezbędne tylko jeśli element sr-only)
  el.classList.remove('sr-visible');
  void el.offsetWidth;
  el.classList.add('sr-visible');
  setTimeout(()=>{ el.classList.remove('sr-visible'); }, 4000);
}

/* small CSS class .sr-visible działa przez employee.css (możesz dodać jeśli chcesz widoczność) */

/* --- LOGIN (backend) --- */
document.getElementById('doLogin').addEventListener('click', async () => {
  const login = document.getElementById('loginInput').value.trim();
  const pass = document.getElementById('passInput').value;
  if (!login || !pass) { notify('Podaj login i hasło.', 'error'); return; }
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
    // reveal dashboard
    document.getElementById('authWrapper').style.display = 'none';
    document.getElementById('dashboard').classList.remove('hidden');
    document.getElementById('dashboard').setAttribute('aria-hidden','false');
    showDashboardFor(user);
    notify('Zalogowano pomyślnie.', 'success');
  } catch (err) {
    const demo = tryDemoLogin(login, pass);
    if (demo) {
      localStorage.setItem(USER_KEY, JSON.stringify(demo));
      showDashboardFor(demo);
      notify('Zalogowano w trybie demo.', 'success');
    } else {
      notify('Logowanie nie powiodło się (spróbuj później).', 'error');
    }
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
  const body = { typ, opis };
  // collect inputs inside extra container
  const extra = document.getElementById('wniosekExtra');
  if (extra) {
    const inputs = extra.querySelectorAll('input,select');
    inputs.forEach(inp => {
      if (inp.type === 'checkbox' && inp.name === 'dni') {
        // collect multiple
      }
      if (inp.multiple) {
        body[inp.name] = Array.from(inp.selectedOptions).map(o=>o.value);
      } else if (inp.type === 'checkbox') {
        if (!body[inp.name]) body[inp.name] = [];
        if (inp.checked) body[inp.name].push(inp.value);
      } else {
        body[inp.name] = inp.value;
      }
    });
  }
  // now send and reuse existing logic (authFetch)
  try {
    if (!localStorage.getItem(TOKEN_KEY)) throw new Error('no token');
    await authFetch('/api/wnioski', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify(body)
    });
    loadWnioski(JSON.parse(localStorage.getItem(USER_KEY) || 'null'));
    document.getElementById('wniosekOpis').value = '';
    notify('Wniosek wysłany.', 'success');
  } catch (err) {
    notify('Błąd wysyłania wniosku. Użyto lokalnego zapisu.', 'error');
    // fallback as before...
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

/* --- DYNAMICZNE POLA WNIOSKU --- */
function renderWniosekFields() {
  const typ = document.getElementById('wniosekTyp').value;
  const containerId = 'wniosekExtra';
  let container = document.getElementById(containerId);
  if (!container) {
    container = document.createElement('div');
    container.id = containerId;
    const form = document.getElementById('wniosekForm');
    form.insertBefore(container, form.querySelector('button[type="submit"]'));
  }
  container.innerHTML = '';
  // create fields per type (IDs must match server expected names)
  if (typ === 'kurs_z_wolnego' || typ === 'KZW') {
    container.innerHTML = `<label class="sr-only" for="data_wykonania">Data wykonania</label>
      <input id="data_wykonania" name="data_wykonania" type="date" required>
      <label class="sr-only" for="proponowany_pojazd">Proponowany pojazd</label>
      <select id="proponowany_pojazd" name="proponowany_pojazd"></select>`;
    // load pojazdy list (try to fetch)
    authFetch('/api/pojazdy').then(list => {
      const sel = document.getElementById('proponowany_pojazd');
      sel.innerHTML = '<option value="">-- wybierz --</option>' + list.map(p => `<option value="${p.id}">${p.nr_rejestracyjny || p.id} ${p.marka||''} ${p.model||''}</option>`).join('');
    }).catch(()=>{/*ignore*/});
  } else if (typ === 'anulowanie_służby') {
    container.innerHTML = `<label class="sr-only" for="sluzba_id">Służba</label><select id="sluzba_id" name="sluzba_id"></select>
      <label class="sr-only" for="powod">Powód</label><input id="powod" name="powod" placeholder="Powód" required>`;
    // populate planned services from grafiki
    authFetch(`/api/grafik?userId=${JSON.parse(localStorage.getItem(USER_KEY)||'null')?.id||''}`).then(s => {
      const sel = document.getElementById('sluzba_id');
      sel.innerHTML = '<option value="">-- wybierz --</option>' + s.map(g=>`<option value="${g.id}">${g.data} ${g.brygada_id||''} ${g.pojazd_id||''}</option>`).join('');
    }).catch(()=>{});
  } else if (typ === 'dzień_wolny') {
    container.innerHTML = `<label class="sr-only" for="data">Dzień</label><input id="data" name="data" type="date" required>
      <label class="sr-only" for="powod">Powód</label><input id="powod" name="powod" placeholder="Powód" required>`;
  } else if (typ === 'urlop') {
    container.innerHTML = `<label class="sr-only" for="od">Od</label><input id="od" name="od" type="date" required>
      <label class="sr-only" for="do">Do</label><input id="do" name="do" type="date" required>
      <label class="sr-only" for="powod">Powód</label><input id="powod" name="powod" placeholder="Powód" required>`;
  } else if (typ === 'stały_pojazd' || typ === 'zmiana_stałego_pojazdu') {
    container.innerHTML = `<label class="sr-only" for="pojazd_id">Pojazd</label><select id="pojazd_id" name="pojazd_id"></select>`;
    authFetch('/api/pojazdy').then(list => {
      const sel = document.getElementById('pojazd_id');
      sel.innerHTML = '<option value="">-- wybierz pojazd --</option>' + list.map(p => `<option value="${p.id}">${p.nr_rejestracyjny || p.id}</option>`).join('');
    }).catch(()=>{});
  } else if (typ === 'nieprzydzielanie_pojazdów') {
    container.innerHTML = `<label class="sr-only" for="pojazdy">Pojazdy</label><select id="pojazdy" name="pojazdy" multiple size="5"></select>
      <label class="sr-only" for="powod">Powód</label><input id="powod" name="powod" placeholder="Powód" required>`;
    authFetch('/api/pojazdy').then(list => {
      const sel = document.getElementById('pojazdy');
      sel.innerHTML = list.map(p => `<option value="${p.id}">${p.nr_rejestracyjny || p.id}</option>`).join('');
    }).catch(()=>{});
  } else if (typ === 'zmiana_etatu') {
    container.innerHTML = `<label class="sr-only">Dni</label>
      <div><label><input type="checkbox" name="dni" value="Poniedziałek">Poniedziałek</label>
      <label><input type="checkbox" name="dni" value="Wtorek">Wtorek</label>
      <label><input type="checkbox" name="dni" value="Środa">Środa</label>
      <label><input type="checkbox" name="dni" value="Czwartek">Czwartek</label>
      <label><input type="checkbox" name="dni" value="Piątek">Piątek</label>
      <label><input type="checkbox" name="dni" value="Sobota">Sobota</label>
      <label><input type="checkbox" name="dni" value="Niedziela">Niedziela</label></div>
      <label class="sr-only" for="powod">Powód</label><input id="powod" name="powod" placeholder="Powód" required>`;
  } else if (typ === 'zwolnienie') {
    container.innerHTML = `<label class="sr-only" for="powod">Powód</label><input id="powod" name="powod" placeholder="Powód" required>`;
  } else {
    container.innerHTML = ''; // no extra
  }
}

document.getElementById('wniosekTyp').addEventListener('change', renderWniosekFields);
renderWniosekFields(); // initial

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

/* --- ADMIN PANEL LINK --- */
document.addEventListener('click', () => {
  const btn = document.getElementById('openAdminPanel');
  if (btn) btn.addEventListener('click', () => {
    window.open('/admin', '_blank', 'noopener');
  });
});
