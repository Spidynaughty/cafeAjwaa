/* =============================================================================
   Cafe Ajwaa - core behaviour.
   No dependencies. Everything degrades to a working server-rendered page.
   ========================================================================== */
(function () {
  'use strict';

  var $  = function (s, r) { return (r || document).querySelector(s); };
  /* The site may be installed in a sub-folder (XAMPP: /cafe-ajwaa).
     PHP writes the prefix onto <body data-base>, so fetch() can find the API
     whether the site sits at a domain root or inside a folder. */
  var BASE = (document.body && document.body.dataset.base) || '';


  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  /* ---------------------------------------------------------- mobile nav */

  (function nav() {
    var toggle = $('[data-nav-toggle]');
    var panel  = $('[data-nav-panel]');
    var scrim  = $('[data-nav-scrim]');
    if (!toggle || !panel) return;

    function setOpen(open) {
      panel.setAttribute('data-open', open ? 'true' : 'false');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
      document.body.style.overflow = open ? 'hidden' : '';
      if (scrim) {
        scrim.hidden = false;
        scrim.setAttribute('data-open', open ? 'true' : 'false');
      }
      if (open) {
        var first = panel.querySelector('a, button');
        if (first) first.focus();
      }
    }

    toggle.addEventListener('click', function () {
      setOpen(toggle.getAttribute('aria-expanded') !== 'true');
    });
    if (scrim) scrim.addEventListener('click', function () { setOpen(false); });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
        setOpen(false);
        toggle.focus();
      }
    });

    // Keep focus inside the drawer while it is open.
    panel.addEventListener('keydown', function (e) {
      if (e.key !== 'Tab') return;
      var f = $$('a, button', panel).filter(function (el) { return el.offsetParent !== null; });
      if (!f.length) return;
      var first = f[0], last = f[f.length - 1];
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    });
  })();

  /* --------------------------------------------------------------- cart

     The server session is authoritative. This mirror exists so the badge and
     the sticky bar update the instant something is tapped, which matters on a
     slow connection. Every add still posts to /api/cart.php and the response
     overwrites the mirror.                                                  */

  var Cart = {
    count: 0,
    subtotal: 0,

    apply: function (data) {
      if (!data) return;
      this.count = data.count || 0;
      this.subtotal = data.subtotal || 0;
      this.render();
    },

    render: function () {
      var badge = $('[data-cart-badge]');
      if (badge) {
        badge.textContent = this.count;
        badge.hidden = this.count === 0;
        if (this.count > 0) {
          badge.classList.remove('is-bumped');
          void badge.offsetWidth;              // restart the animation
          badge.classList.add('is-bumped');
        }
      }
      var bar = $('[data-cart-bar]');
      if (bar) {
        bar.setAttribute('data-visible', this.count > 0 ? 'true' : 'false');
        document.body.classList.toggle('has-cart-bar', this.count > 0);
        var c = $('[data-cart-bar-count]', bar);
        var t = $('[data-cart-bar-total]', bar);
        if (c) c.textContent = this.count;
        if (t) t.textContent = '\u20B9' + Math.round(this.subtotal).toLocaleString('en-IN');
      }
    },

    post: function (action, payload) {
      var body = new FormData();
      body.append('action', action);
      Object.keys(payload || {}).forEach(function (k) { body.append(k, payload[k]); });
      return fetch(BASE + '/api/cart.php', {
        method: 'POST',
        body: body,
        headers: { 'X-Requested-With': 'fetch' },
        credentials: 'same-origin'
      }).then(function (r) {
        if (!r.ok) throw new Error('cart request failed');
        return r.json();
      });
    }
  };

  // Seed from what the server already rendered.
  (function seedCart() {
    var bar = $('[data-cart-bar]');
    var badge = $('[data-cart-badge]');
    if (badge) Cart.count = parseInt(badge.textContent, 10) || 0;
    if (bar) {
      var t = $('[data-cart-bar-total]', bar);
      if (t) Cart.subtotal = parseFloat(String(t.textContent).replace(/[^0-9.]/g, '')) || 0;
    }
    document.body.classList.toggle('has-cart-bar', Cart.count > 0);
  })();

  function flashButton(btn, text) {
    if (!btn || btn.dataset.busy === '1') return;
    var original = btn.textContent;
    btn.dataset.busy = '1';
    btn.textContent = text;
    setTimeout(function () {
      btn.textContent = original;
      btn.dataset.busy = '0';
    }, 900);
  }

  function addToCart(itemId, variantId, btn) {
    if (btn) { btn.disabled = true; }
    Cart.post('add', { item: itemId, variant: variantId, qty: 1 })
      .then(function (data) {
        Cart.apply(data);
        flashButton(btn, 'Added');
      })
      .catch(function () {
        if (btn) btn.textContent = 'Try again';
        setTimeout(function () { if (btn) btn.textContent = 'Add'; }, 1500);
      })
      .then(function () { if (btn) btn.disabled = false; });
  }

  document.addEventListener('click', function (e) {
    var add = e.target.closest('[data-add]');
    if (add) {
      e.preventDefault();
      addToCart(add.dataset.item, add.dataset.variant, add);
      return;
    }
    var openVar = e.target.closest('[data-open-variants]');
    if (openVar) {
      e.preventDefault();
      openVariantSheet(openVar);
    }
  });

  /* ------------------------------------------- portion chooser (Half/Full)

     Built on demand so items with a single price cost nothing extra.        */

  var sheet = null, sheetOpener = null;

  function openVariantSheet(btn) {
    var variants;
    try { variants = JSON.parse(btn.dataset.variants); } catch (err) { return; }
    closeVariantSheet();
    sheetOpener = btn;

    sheet = document.createElement('div');
    sheet.className = 'lightbox';
    sheet.setAttribute('role', 'dialog');
    sheet.setAttribute('aria-modal', 'true');
    sheet.setAttribute('aria-label', 'Choose a portion for ' + btn.dataset.itemName);

    var card = document.createElement('div');
    card.className = 'form-card';
    card.style.maxWidth = '340px';
    card.style.width = '100%';

    var h = document.createElement('h3');
    h.textContent = btn.dataset.itemName;
    card.appendChild(h);

    var p = document.createElement('p');
    p.className = 'text-sm text-mute';
    p.textContent = 'Choose a portion.';
    card.appendChild(p);

    variants.forEach(function (v) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'btn btn--ghost btn--block';
      b.style.marginBottom = '.5rem';
      b.style.justifyContent = 'space-between';
      b.innerHTML = '<span></span><span></span>';
      b.firstChild.textContent = v.label;
      b.lastChild.textContent = v.price === null
        ? 'On request'
        : '\u20B9' + Number(v.price).toLocaleString('en-IN');
      b.disabled = v.price === null;
      b.addEventListener('click', function () {
        addToCart(btn.dataset.openVariants, v.id, null);
        flashButton(btn, 'Added');
        closeVariantSheet();
      });
      card.appendChild(b);
    });

    var cancel = document.createElement('button');
    cancel.type = 'button';
    cancel.className = 'link-remove';
    cancel.style.marginTop = '.25rem';
    cancel.textContent = 'Cancel';
    cancel.addEventListener('click', closeVariantSheet);
    card.appendChild(cancel);

    sheet.appendChild(card);
    sheet.addEventListener('click', function (e) { if (e.target === sheet) closeVariantSheet(); });
    document.body.appendChild(sheet);
    document.body.style.overflow = 'hidden';
    card.querySelector('button').focus();
    document.addEventListener('keydown', sheetKeys);
  }

  function sheetKeys(e) {
    if (e.key === 'Escape') closeVariantSheet();
  }

  function closeVariantSheet() {
    if (!sheet) return;
    document.removeEventListener('keydown', sheetKeys);
    sheet.remove();
    sheet = null;
    document.body.style.overflow = '';
    if (sheetOpener) { sheetOpener.focus(); sheetOpener = null; }
  }

  /* --------------------------------------------- quantity steppers (cart) */

  $$('[data-qty]').forEach(function (group) {
    var out  = $('output', group);
    var item = group.dataset.item;
    var variant = group.dataset.variant;

    group.addEventListener('click', function (e) {
      var btn = e.target.closest('button[data-step]');
      if (!btn) return;
      var next = Math.max(0, (parseInt(out.value, 10) || 0) + parseInt(btn.dataset.step, 10));
      out.value = next;
      Cart.post('set', { item: item, variant: variant, qty: next })
        .then(function () { window.location.reload(); })
        .catch(function () { window.location.reload(); });
    });
  });

  /* ------------------------------------------------- broken-image fallback

     A missing photo should look deliberate, not broken.                     */

  document.addEventListener('error', function (e) {
    var img = e.target;
    if (!img || img.tagName !== 'IMG' || img.dataset.failed === '1') return;
    img.dataset.failed = '1';
    var box = document.createElement('div');
    box.className = img.classList.contains('menu-item-thumb') ? 'thumb-fallback' : 'thumb-fallback';
    box.setAttribute('aria-hidden', 'true');
    box.textContent = 'CA';
    if (img.parentNode) img.parentNode.replaceChild(box, img);
  }, true);

  window.AjwaaCart = Cart;
})();
