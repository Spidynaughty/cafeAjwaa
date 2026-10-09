/* =============================================================================
   Menu page: instant search, diet filter, category navigation.
   Filtering is pure DOM work on 97 nodes - no library, no re-render.
   ========================================================================== */
(function () {
  'use strict';

  var $  = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  var root = $('[data-menu-root]');
  if (!root) return;

  var search   = $('[data-search]');
  var clearBtn = $('[data-search-clear]');
  var dietBtns = $$('[data-diet]');
  var bestBtn  = $('[data-best-filter]');
  var groups   = $$('[data-group]');
  var items    = $$('[data-menu-item]');
  var counter  = $('[data-result-count]');
  var empty    = $('[data-empty]');
  var chips    = $$('[data-cat-link]');

  var state = { q: '', diet: 'all', best: false };

  function normalise(s) {
    return String(s || '').toLowerCase().trim();
  }

  /* ---------------------------------------------------- admin shortcut */
  /* Typing the configured phrase into the search box jumps to the admin
     login page. It is only a shortcut, not a login bypass - login.php
     still requires the real username and password. */
  var adminPhrase = normalise(root.dataset.adminPhrase);
  var adminUrl    = root.dataset.adminUrl;

  function checkAdminShortcut(raw) {
    if (!adminPhrase || !adminUrl) return false;
    if (normalise(raw) !== adminPhrase) return false;
    window.location.href = adminUrl;
    return true;
  }

  function apply() {
    var q = normalise(state.q);
    var shown = 0;

    items.forEach(function (li) {
      var okQ    = !q || li.dataset.name.indexOf(q) !== -1;
      var okDiet = state.diet === 'all' || li.dataset.type === state.diet;
      var okBest = !state.best || li.dataset.best === '1';
      var show   = okQ && okDiet && okBest;
      li.hidden = !show;
      if (show) shown++;
    });

    // Hide a category heading when nothing inside it survived the filter.
    groups.forEach(function (g) {
      var any = $$('[data-menu-item]', g).some(function (li) { return !li.hidden; });
      g.hidden = !any;
    });

    if (counter) {
      counter.textContent = shown === items.length
        ? items.length + ' dishes'
        : shown + ' of ' + items.length + ' dishes';
    }
    if (empty) empty.hidden = shown !== 0;
    if (clearBtn) clearBtn.hidden = !state.q;
  }

  /* ------------------------------------------------------------- search */

  if (search) {
    var timer = null;
    search.addEventListener('input', function () {
      // 120ms is below the threshold where typing feels laggy, and it keeps
      // low-end phones from filtering on every single keystroke.
      clearTimeout(timer);
      if (checkAdminShortcut(search.value)) return;
      timer = setTimeout(function () {
        state.q = search.value;
        apply();
      }, 120);
    });
    search.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { search.value = ''; state.q = ''; apply(); }
    });
  }

  if (clearBtn) {
    clearBtn.addEventListener('click', function () {
      search.value = '';
      state.q = '';
      apply();
      search.focus();
    });
  }

  /* -------------------------------------------------------- diet filter */

  dietBtns.forEach(function (btn) {
    btn.addEventListener('click', function () {
      var value = btn.dataset.diet;
      state.diet = (state.diet === value) ? 'all' : value;
      dietBtns.forEach(function (b) {
        b.setAttribute('aria-pressed', b.dataset.diet === state.diet ? 'true' : 'false');
      });
      apply();
    });
  });

  if (bestBtn) {
    bestBtn.addEventListener('click', function () {
      state.best = !state.best;
      bestBtn.setAttribute('aria-pressed', state.best ? 'true' : 'false');
      apply();
    });
  }

  /* ------------------------------------------------ category navigation */

  chips.forEach(function (chip) {
    chip.addEventListener('click', function (e) {
      var id = chip.getAttribute('href');
      if (!id || id.charAt(0) !== '#') return;
      var target = document.getElementById(id.slice(1));
      if (!target) return;
      e.preventDefault();

      // Clear filters first, otherwise the target section may be hidden.
      if (target.hidden) {
        state.q = ''; state.diet = 'all'; state.best = false;
        if (search) search.value = '';
        dietBtns.forEach(function (b) { b.setAttribute('aria-pressed', 'false'); });
        if (bestBtn) bestBtn.setAttribute('aria-pressed', 'false');
        apply();
      }
      target.scrollIntoView({
        behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
        block: 'start'
      });
      history.replaceState(null, '', id);
    });
  });

  /* Scroll-spy: mark the category currently in view. */
  if ('IntersectionObserver' in window && groups.length) {
    var visible = new Set();

    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) visible.add(en.target.id);
        else visible.delete(en.target.id);
      });
      var firstVisible = groups
        .map(function (g) { return g.id; })
        .filter(function (id) { return visible.has(id); })[0];

      chips.forEach(function (c) {
        var on = firstVisible && c.getAttribute('href') === '#' + firstVisible;
        c.setAttribute('aria-current', on ? 'true' : 'false');
        if (on && c.classList.contains('cat-chip') && c.parentElement) {
          // Keep the active chip in view on the mobile scroller.
          var bar = c.parentElement;
          if (bar.scrollWidth > bar.clientWidth) {
            var left = c.offsetLeft - bar.clientWidth / 2 + c.clientWidth / 2;
            bar.scrollTo({ left: left, behavior: 'smooth' });
          }
        }
      });
    }, { rootMargin: '-140px 0px -60% 0px', threshold: 0 });

    groups.forEach(function (g) { io.observe(g); });
  }

  apply();
})();
