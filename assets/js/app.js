/* HockeyManager — interaccions de la interfície (sense dependències). */
(function () {
  'use strict';
  var root = document.documentElement;

  function closeSidebar() { root.classList.remove('sidebar-open'); }

  document.addEventListener('click', function (ev) {
    var t = ev.target;
    if (!(t instanceof Element)) return;

    if (t.closest('[data-sidebar-open]')) {
      root.classList.add('sidebar-open');
      var first = document.querySelector('#sidebar .nav-link');
      if (first) first.focus();
      return;
    }
    if (t.closest('[data-sidebar-close]')) { closeSidebar(); return; }

    if (t.closest('[data-sidebar-collapse]')) {
      var collapsed = root.classList.toggle('sidebar-collapsed');
      try { localStorage.setItem('hm-sidebar', collapsed ? 'collapsed' : 'expanded'); } catch (e) { /* sense emmagatzematge */ }
      return;
    }

    var dismiss = t.closest('[data-dismiss]');
    if (dismiss) {
      var alert = dismiss.closest('.alert');
      if (alert) alert.remove();
      return;
    }

    // Tanca els desplegables oberts en clicar fora
    document.querySelectorAll('details.dropdown[open]').forEach(function (d) {
      if (!d.contains(t)) d.removeAttribute('open');
    });
  });

  // Només un desplegable obert alhora
  document.querySelectorAll('details.dropdown').forEach(function (d) {
    d.addEventListener('toggle', function () {
      if (!d.open) return;
      document.querySelectorAll('details.dropdown[open]').forEach(function (o) {
        if (o !== d) o.removeAttribute('open');
      });
    });
  });

  document.addEventListener('keydown', function (ev) {
    if (ev.key !== 'Escape') return;
    closeSidebar();
    document.querySelectorAll('details.dropdown[open]').forEach(function (d) {
      d.removeAttribute('open');
      var s = d.querySelector('summary');
      if (s) s.focus();
    });
  });

  // Selector d'equip: s'aplica en canviar
  document.querySelectorAll('select[data-autosubmit]').forEach(function (sel) {
    sel.addEventListener('change', function () { if (sel.form) sel.form.submit(); });
  });

  // Confirmació abans d'accions destructives
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (ev) {
      if (!window.confirm(f.getAttribute('data-confirm'))) ev.preventDefault();
    });
  });

  // Ressalta la fila d'equip marcada al formulari de jugador/a
  // (delegat: també funciona en contingut substituït en directe)
  document.addEventListener('change', function (ev) {
    var cb = ev.target;
    if (!cb.matches || !cb.matches('input[data-toggle-row]')) return;
    var row = cb.closest('.team-check');
    if (row) row.classList.toggle('is-on', cb.checked);
    if (cb.checked && row) { var d = row.querySelector('.team-check-dorsal input'); if (d && !d.value) d.focus(); }
  });

  // Amaga un camp quan es marca una casella (data-hide="id")
  document.querySelectorAll('input[data-hide]').forEach(function (cb) {
    var target = document.getElementById(cb.getAttribute('data-hide'));
    if (!target) return;
    var sync = function () {
      target.hidden = cb.checked;
      var inp = target.querySelector('input');
      if (inp) { inp.required = !cb.checked; if (cb.checked) inp.value = ''; }
    };
    cb.addEventListener('change', sync); sync();
  });

  // Mostra/amaga un camp lligat a una casella (data-reveal="id")
  document.querySelectorAll('input[data-reveal]').forEach(function (cb) {
    var target = document.getElementById(cb.getAttribute('data-reveal'));
    if (!target) return;
    cb.addEventListener('change', function () {
      target.hidden = !cb.checked;
      var inp = target.querySelector('input');
      if (inp) { inp.required = cb.checked; if (cb.checked) inp.focus(); }
    });
  });

  // Passar llista: tothom present per defecte; tocar una fila obre el selector d'estat
  var roll = document.querySelector('form[data-attendance]');
  var sheet = document.querySelector('[data-roll-sheet]');
  if (roll && sheet) {
    var LABELS = { present: 'Present', retard: 'Retard', justificat: 'Justificada', absent: 'Absent', '': 'Sense marcar' };
    var rows = Array.prototype.slice.call(roll.querySelectorAll('[data-roll]'));
    var counter = roll.querySelector('[data-count]');
    var current = null;
    var paint = function (btn) {
      var li = btn.parentNode, v = li.querySelector('[data-status-input]').value;
      btn.className = 'roll2-item st-' + (v || 'none');
      btn.querySelector('[data-status-text]').textContent = LABELS[v];
      var n = li.querySelector('[data-note-input]').value;
      var nt = btn.querySelector('[data-note-text]'); nt.textContent = n; nt.hidden = !n;
    };
    var refresh = function () {
      var c = { present: 0, retard: 0, justificat: 0, absent: 0 };
      rows.forEach(function (b) { var v = b.parentNode.querySelector('[data-status-input]').value; if (c[v] !== undefined) c[v]++; paint(b); });
      var parts = [c.present + ' presents'];
      if (c.retard) parts.push(c.retard + ' retard' + (c.retard > 1 ? 's' : ''));
      if (c.justificat) parts.push(c.justificat + ' justif.');
      if (c.absent) parts.push(c.absent + ' absent' + (c.absent > 1 ? 's' : ''));
      if (counter) counter.textContent = parts.join(' · ');
    };
    var noteInput = sheet.querySelector('[data-sheet-note]');
    var open = function (btn) {
      current = btn;
      var li = btn.parentNode, v = li.querySelector('[data-status-input]').value;
      sheet.querySelector('[data-sheet-name]').textContent = btn.querySelector('strong').textContent;
      sheet.querySelectorAll('.roll-opt').forEach(function (o) { o.classList.toggle('is-on', o.getAttribute('data-set') === v); o.setAttribute('aria-pressed', o.getAttribute('data-set') === v ? 'true' : 'false'); });
      noteInput.value = li.querySelector('[data-note-input]').value;
      sheet.hidden = false;
      document.body.classList.add('sheet-open');
      requestAnimationFrame(function () { sheet.classList.add('is-open'); });
      var on = sheet.querySelector('.roll-opt.is-on') || sheet.querySelector('.roll-opt'); on.focus();
    };
    var close = function () {
      if (!current) return;
      current.parentNode.querySelector('[data-note-input]').value = noteInput.value.trim();
      sheet.classList.remove('is-open');
      document.body.classList.remove('sheet-open');
      var back = current; current = null;
      setTimeout(function () { sheet.hidden = true; }, 180);
      refresh(); back.focus({ preventScroll: true });
    };
    rows.forEach(function (b) { b.addEventListener('click', function () { open(b); }); });
    sheet.querySelectorAll('[data-set]').forEach(function (o) {
      o.addEventListener('click', function () {
        if (!current) return;
        current.parentNode.querySelector('[data-status-input]').value = o.getAttribute('data-set');
        close();
      });
    });
    sheet.querySelectorAll('[data-sheet-close]').forEach(function (x) { x.addEventListener('click', close); });
    noteInput.addEventListener('keydown', function (ev) { if (ev.key === 'Enter') { ev.preventDefault(); close(); } });
    document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape' && current) close(); });
    refresh();
  }

  // Formulari de partit: mostra el resultat només si està disputat
  var scoreBox = document.getElementById('score-box');
  if (scoreBox) {
    document.querySelectorAll('input[data-status]').forEach(function (r) {
      r.addEventListener('change', function () { scoreBox.hidden = !(r.checked && r.value === 'jugat'); });
    });
  }

  // Acta: desplega les estadístiques de qui ha jugat
  var sheetCount = document.querySelector('[data-sheet-count]');
  var updateSheet = function () {
    var n = document.querySelectorAll('input[data-played]:checked').length;
    if (sheetCount) sheetCount.textContent = n + ' han jugat';
  };
  document.querySelectorAll('input[data-played]').forEach(function (cb) {
    cb.addEventListener('change', function () {
      var li = cb.closest('.sheet-item');
      li.classList.toggle('st-present', cb.checked);
      var st = li.querySelector('.sheet-stats');
      if (st) st.hidden = !cb.checked;
      updateSheet();
    });
  });
  updateSheet();

  // Convocatòria: comptador de seleccionades
  var pickCount = document.querySelector('[data-pick-count]');
  var updatePick = function () {
    var n = document.querySelectorAll('input[data-pick]:checked').length;
    if (pickCount) pickCount.textContent = n + ' convocades';
  };
  document.querySelectorAll('input[data-pick]').forEach(function (cb) {
    cb.addEventListener('change', function () { cb.closest('.pick').classList.toggle('is-on', cb.checked); updatePick(); });
  });
  updatePick();


  // Canvis en directe (sense recarregar ni moure la pàgina): formularis amb data-live.
  // S'envia per fetch i es substitueixen les seccions marcades amb id de la pàgina resultant.
  var LIVE_IDS = ['ev-hero', 'equips', 'convocades'];
  var toast = function (msg, type) {
    var t = document.createElement('div');
    t.className = 'live-toast live-toast-' + (type || 'success');
    t.textContent = msg;
    document.body.appendChild(t);
    requestAnimationFrame(function () { t.classList.add('is-on'); });
    setTimeout(function () { t.classList.remove('is-on'); setTimeout(function () { t.remove(); }, 300); }, 2200);
  };
  document.addEventListener('submit', function (ev) {
    var form = ev.target;
    if (!form.matches || !form.matches('form[data-live]') || !window.fetch || !window.DOMParser) return;
    ev.preventDefault();
    var fd = new FormData(form);
    var btn = ev.submitter;
    if (btn && btn.name) fd.append(btn.name, btn.value);
    if (btn) btn.disabled = true;
    var openAdd = !!document.querySelector('.roster-add[open]');
    fetch(form.getAttribute('action'), { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
      .then(function (r) { if (!r.ok) throw new Error(r.status); return r.text(); })
      .then(function (html) {
        var doc = new DOMParser().parseFromString(html, 'text/html');
        var ids = form.getAttribute('data-live') ? form.getAttribute('data-live').split(',') : LIVE_IDS;
        ids.forEach(function (id) {
          var cur = document.getElementById(id), next = doc.getElementById(id);
          if (cur && next) cur.replaceWith(next);
        });
        var add = document.querySelector('.roster-add');
        if (add && openAdd) add.open = true;
        var alert = doc.querySelector('.alert');
        if (alert) toast(alert.textContent.trim(), alert.classList.contains('alert-error') ? 'error' : 'success');
      })
      .catch(function () { form.removeAttribute('data-live'); if (btn && btn.name) { var h = document.createElement('input'); h.type = 'hidden'; h.name = btn.name; h.value = btn.value; form.appendChild(h); } form.submit(); });
  });
  // Caselles de selecció també dins de contingut substituït
  document.addEventListener('change', function (ev) {
    var cb = ev.target;
    if (cb.matches && cb.matches('input[data-pick]') && cb.closest('.pick')) cb.closest('.pick').classList.toggle('is-on', cb.checked);
  });


  // Al mòbil, no obris el teclat sol en entrar a un formulari
  if (window.matchMedia && window.matchMedia('(pointer: coarse)').matches) {
    var af = document.querySelector('[autofocus]');
    if (af && document.activeElement === af) af.blur();
  }

  // Botó d'enrere a la capçalera: agafa l'enllaç «‹ …» de la pàgina (al mòbil substitueix l'enllaç de dins)
  var backLink = document.querySelector('main .back-link, .main .back-link');
  var topBack = document.querySelector('[data-topbar-back]');
  if (backLink && topBack) {
    topBack.href = backLink.getAttribute('href');
    var lbl = backLink.textContent.trim();
    topBack.setAttribute('aria-label', 'Torna a ' + lbl);
    topBack.title = 'Torna a ' + lbl;
    topBack.hidden = false;
    document.body.classList.add('has-topbar-back');
  }


  // Filtre en directe mentre s'escriu (input[data-filter="selector dels elements"])
  var norm = function (t) { return (t || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); };
  document.querySelectorAll('input[data-filter]').forEach(function (inp) {
    var items = Array.prototype.slice.call(document.querySelectorAll(inp.getAttribute('data-filter')));
    var countEl = inp.getAttribute('data-filter-count') ? document.querySelector(inp.getAttribute('data-filter-count')) : null;
    var word = inp.getAttribute('data-filter-word') || '';
    var countItems = inp.getAttribute('data-filter-items') ? Array.prototype.slice.call(document.querySelectorAll(inp.getAttribute('data-filter-items'))) : items;
    var form = inp.form;
    if (form) form.addEventListener('submit', function (ev) { ev.preventDefault(); inp.blur(); });
    var run = function () {
      var q = norm(inp.value.trim());
      var shown = 0;
      items.forEach(function (el) {
        var ok = !q || norm(el.textContent).indexOf(q) !== -1;
        el.hidden = !ok; if (ok) shown++;
      });
      if (countEl && word) {
        var base = countItems.length;
        var vis = countItems.filter(function (el) { return !el.hidden; }).length;
        countEl.textContent = (q ? vis + ' de ' + base : base) + ' ' + word;
      }
    };
    inp.addEventListener('input', run);
    if (inp.value) run();
  });

  // Formulari d'usuari: camps segons el rol
  var rolePanels = document.querySelectorAll('[data-role-panel]');
  document.querySelectorAll('input[data-role]').forEach(function (r) {
    r.addEventListener('change', function () {
      if (!r.checked) return;
      rolePanels.forEach(function (p) { p.hidden = p.getAttribute('data-role-panel') !== r.value; });
    });
  });

  // Esdeveniment: llista de jugadores només si es tria "Només unes jugadores"
  var whoList = document.getElementById('who-list');
  var whoTeams = document.getElementById('who-teams');
  if (whoList) {
    document.querySelectorAll('input[data-who]').forEach(function (r) {
      r.addEventListener('change', function () {
        if (!r.checked) return;
        whoList.hidden = r.value !== 'llista';
        if (whoTeams) whoTeams.hidden = r.value === 'llista';
      });
    });
  }

  // Mòbil: cercador desplegable
  var st = document.querySelector('[data-search-toggle]');
  if (st) st.addEventListener('click', function () {
    var bar = st.closest('.topbar');
    bar.classList.toggle('search-open');
    if (bar.classList.contains('search-open')) { var i = bar.querySelector('.search input'); if (i) i.focus(); }
  });

  // Mòbil: tocar un dia del mes porta a la seva llista (si n'hi ha) en lloc d'obrir la vista de dia
  document.querySelectorAll('.cal-daynum[data-day]').forEach(function (a) {
    a.addEventListener('click', function (ev) {
      if (window.matchMedia('(max-width: 760px)').matches) {
        var target = document.getElementById('dia-' + a.getAttribute('data-day'));
        if (target) { ev.preventDefault(); target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
      }
    });
  });

  // Els missatges d'èxit desapareixen sols
  setTimeout(function () {
    document.querySelectorAll('.alert-success').forEach(function (a) { a.remove(); });
  }, 6000);
})();
