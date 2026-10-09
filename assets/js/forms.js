/* =============================================================================
   Client-side form hints. The server rules in includes/validation.php are the
   real ones; this only saves the user a round trip.
   ========================================================================== */
(function () {
  'use strict';

  var forms = Array.prototype.slice.call(document.querySelectorAll('[data-validate]'));
  if (!forms.length) return;

  var RULES = {
    name:    { test: function (v) { return v.trim().length >= 2; },
               msg: 'Enter your name.' },
    mobile:  { test: function (v) { return /^[6-9]\d{9}$/.test(v.replace(/\D/g, '').replace(/^91/, '').replace(/^0/, '')); },
               msg: 'Enter a 10-digit Indian mobile number.' },
    email:   { test: function (v) { return v === '' || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v); },
               msg: 'Enter a valid email or leave it blank.' },
    pincode: { test: function (v) { return /^[1-9]\d{5}$/.test(v); },
               msg: 'Enter a 6-digit PIN code.' },
    address: { test: function (v) { return v.trim().length >= 10; },
               msg: 'Enter a full delivery address.' },
    date:    { test: function (v) { return !!v; }, msg: 'Choose a date.' },
    time:    { test: function (v) { return !!v; }, msg: 'Choose a time.' },
    guests:  { test: function (v) { return +v >= 1 && +v <= 30; },
               msg: 'Enter between 1 and 30 guests.' },
    message: { test: function (v) { return v.trim().length >= 5; },
               msg: 'Write at least 5 characters.' }
  };

  function fieldWrap(input) { return input.closest('.field'); }

  function setError(input, msg) {
    var wrap = fieldWrap(input);
    if (!wrap) return;
    var err = wrap.querySelector('.field-error');
    if (msg) {
      wrap.classList.add('has-error');
      input.setAttribute('aria-invalid', 'true');
      if (!err) {
        err = document.createElement('p');
        err.className = 'field-error';
        err.id = (input.id || input.name) + '-error';
        wrap.appendChild(err);
      }
      err.textContent = msg;
      input.setAttribute('aria-describedby', err.id);
    } else {
      wrap.classList.remove('has-error');
      input.removeAttribute('aria-invalid');
      if (err) err.remove();
    }
  }

  function checkField(input) {
    var rule = RULES[input.dataset.rule || input.name];
    if (!rule) return true;
    if (!input.required && input.value.trim() === '' && input.name !== 'email') {
      setError(input, null);
      return true;
    }
    var ok = rule.test(input.value);
    setError(input, ok ? null : rule.msg);
    return ok;
  }

  forms.forEach(function (form) {
    var fields = Array.prototype.slice.call(form.querySelectorAll('input, select, textarea'))
      .filter(function (el) { return RULES[el.dataset.rule || el.name]; });

    fields.forEach(function (el) {
      // Validate on blur, then live once the field has been corrected.
      el.addEventListener('blur', function () { checkField(el); });
      el.addEventListener('input', function () {
        if (fieldWrap(el) && fieldWrap(el).classList.contains('has-error')) checkField(el);
      });
    });

    form.addEventListener('submit', function (e) {
      var firstBad = null;
      fields.forEach(function (el) {
        if (el.offsetParent === null) return;          // skip hidden branches
        if (!checkField(el) && !firstBad) firstBad = el;
      });
      if (firstBad) {
        e.preventDefault();
        firstBad.focus();
        firstBad.scrollIntoView({ block: 'center', behavior: 'smooth' });
      }
    });
  });

  /* Delivery/pickup switch: show the address block only when it applies. */
  var typeInputs = Array.prototype.slice.call(document.querySelectorAll('[name="order_type"]'));
  var addressBlock = document.querySelector('[data-address-block]');
  if (typeInputs.length && addressBlock) {
    function sync() {
      var delivery = typeInputs.some(function (i) { return i.checked && i.value === 'delivery'; });
      addressBlock.hidden = !delivery;
      Array.prototype.slice.call(addressBlock.querySelectorAll('input, textarea'))
        .forEach(function (el) { el.required = delivery && el.dataset.optional !== '1'; });
    }
    typeInputs.forEach(function (i) { i.addEventListener('change', sync); });
    sync();
  }
})();
