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

  // Els missatges d'èxit desapareixen sols
  setTimeout(function () {
    document.querySelectorAll('.alert-success').forEach(function (a) { a.remove(); });
  }, 6000);
})();
