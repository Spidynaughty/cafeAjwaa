/* =============================================================================
   Gallery lightbox. Keyboard accessible, touch friendly, no library.
   ========================================================================== */
(function () {
  'use strict';

  var cells = Array.prototype.slice.call(document.querySelectorAll('[data-lightbox]'));
  if (!cells.length) return;

  var box     = document.querySelector('[data-lightbox-root]');
  var img     = box && box.querySelector('[data-lightbox-img]');
  var caption = box && box.querySelector('[data-lightbox-cap]');
  if (!box || !img) return;

  var index = 0;
  var opener = null;

  function show(i) {
    index = (i + cells.length) % cells.length;
    var cell = cells[index];
    img.src = cell.dataset.full || cell.querySelector('img').src;
    img.alt = cell.dataset.alt || cell.querySelector('img').alt || '';
    if (caption) caption.textContent = (index + 1) + ' of ' + cells.length + (img.alt ? ' — ' + img.alt : '');
  }

  function open(i, trigger) {
    opener = trigger || null;
    show(i);
    box.hidden = false;
    document.body.style.overflow = 'hidden';
    var close = box.querySelector('.lightbox-close');
    if (close) close.focus();
    document.addEventListener('keydown', onKey);
  }

  function close() {
    box.hidden = true;
    img.removeAttribute('src');
    document.body.style.overflow = '';
    document.removeEventListener('keydown', onKey);
    if (opener) { opener.focus(); opener = null; }
  }

  function onKey(e) {
    if (e.key === 'Escape')      { close(); }
    else if (e.key === 'ArrowRight') { show(index + 1); }
    else if (e.key === 'ArrowLeft')  { show(index - 1); }
    else if (e.key === 'Tab') {
      // Trap focus inside the dialog.
      var f = Array.prototype.slice.call(box.querySelectorAll('button'));
      if (!f.length) return;
      var first = f[0], last = f[f.length - 1];
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    }
  }

  cells.forEach(function (cell, i) {
    cell.addEventListener('click', function () { open(i, cell); });
  });

  box.addEventListener('click', function (e) { if (e.target === box) close(); });
  var c = box.querySelector('.lightbox-close'); if (c) c.addEventListener('click', close);
  var p = box.querySelector('.lightbox-prev');  if (p) p.addEventListener('click', function () { show(index - 1); });
  var n = box.querySelector('.lightbox-next');  if (n) n.addEventListener('click', function () { show(index + 1); });

  /* Horizontal swipe to move between images. */
  var x0 = null, y0 = null;
  box.addEventListener('touchstart', function (e) {
    x0 = e.changedTouches[0].clientX; y0 = e.changedTouches[0].clientY;
  }, { passive: true });
  box.addEventListener('touchend', function (e) {
    if (x0 === null) return;
    var dx = e.changedTouches[0].clientX - x0;
    var dy = e.changedTouches[0].clientY - y0;
    if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) show(index + (dx < 0 ? 1 : -1));
    x0 = y0 = null;
  }, { passive: true });
})();
