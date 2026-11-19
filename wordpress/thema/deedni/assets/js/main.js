(function () {
  // Menu op mobiel
  var toggle = document.getElementById('menu-toggle');
  var nav = document.getElementById('site-nav');
  if (toggle && nav) {
    var closeMenu = function () { nav.classList.remove('is-open'); toggle.setAttribute('aria-expanded', 'false'); };
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    nav.addEventListener('click', function (e) { if (e.target.closest('a')) closeMenu(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeMenu(); });
  }

  // Kop krijgt een rand zodra je scrolt
  var header = document.getElementById('site-header');
  if (header) {
    var onScroll = function () { header.classList.toggle('is-scrolled', window.scrollY > 8); };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  // Doelgroep: marketeer of werkgever. Wisselt teksten op de homepage en velden in het aanmeldformulier.
  var navCta = document.getElementById('nav-cta');
  var switches = document.querySelectorAll('[data-set-audience]');
  var radios = document.querySelectorAll('input[name="rol"]');
  function setAudience(who, remember) {
    document.querySelectorAll('[data-for]').forEach(function (el) { el.hidden = el.getAttribute('data-for') !== who; });
    switches.forEach(function (b) { b.setAttribute('aria-pressed', b.getAttribute('data-set-audience') === who ? 'true' : 'false'); });
    var radio = document.getElementById('rol-' + who);
    if (radio) radio.checked = true;
    if (navCta) {
      navCta.textContent = who === 'werkgever' ? 'Start als werkgever' : 'Gratis profiel maken';
      var href = navCta.getAttribute('data-href-' + who);
      if (href) navCta.setAttribute('href', href);
    }
    if (remember) { try { localStorage.setItem('deedni-doelgroep', who); } catch (e) {} }
  }
  switches.forEach(function (b) {
    b.addEventListener('click', function () { setAudience(b.getAttribute('data-set-audience'), true); });
  });
  radios.forEach(function (r) {
    r.addEventListener('change', function () { if (r.checked) setAudience(r.value, true); });
  });
  if (switches.length || radios.length) {
    var start = null;
    var param = new URLSearchParams(location.search).get('rol');
    if (param === 'werkgever' || param === 'kandidaat') start = param;
    else if (location.hash === '#werkgevers') start = 'werkgever';
    else if (!switches.length) {
      var checked = document.querySelector('input[name="rol"]:checked');
      start = checked ? checked.value : null;
    } else { try { start = localStorage.getItem('deedni-doelgroep'); } catch (e) {} }
    setAudience(start === 'werkgever' ? 'werkgever' : 'kandidaat', false);
  }

  // Voorbeeldkaarten in de hero: een klik toont de reactie die in het blok is ingevuld
  document.querySelectorAll('[data-demo-actions]').forEach(function (actions) {
    var result = actions.parentNode.querySelector('[data-demo-result]');
    if (!result) return;
    actions.querySelectorAll('[data-demo]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        actions.hidden = true;
        result.innerHTML = '';
        var span = document.createElement('span'); span.textContent = btn.getAttribute('data-demo');
        var reset = document.createElement('button'); reset.type = 'button'; reset.textContent = 'Opnieuw';
        reset.addEventListener('click', function () { result.hidden = true; actions.hidden = false; btn.focus(); });
        result.appendChild(span); result.appendChild(reset);
        result.hidden = false;
      });
    });
  });

  // Rekenvoorbeeld in het blok Prijzen
  var fmt = new Intl.NumberFormat('nl-NL', { maximumFractionDigits: 0 });
  document.querySelectorAll('[data-calc]').forEach(function (box) {
    var range = box.querySelector('input[type="range"]');
    var pct = Number(box.getAttribute('data-pct')) / 100;
    var bMin = Number(box.getAttribute('data-bureau-min')) / 100;
    var bMax = Number(box.getAttribute('data-bureau-max')) / 100;
    var out = box.querySelector('[data-calc-out]');
    var us = box.querySelector('[data-calc-us]');
    var bureau = box.querySelector('[data-calc-bureau]');
    function calc() {
      var s = Number(range.value);
      out.textContent = '€' + fmt.format(s);
      range.setAttribute('aria-valuetext', '€' + fmt.format(s) + ' per jaar');
      us.textContent = '€' + fmt.format(s * pct);
      if (bureau) bureau.textContent = '€' + fmt.format(s * bMin) + (bMax ? '–' + fmt.format(s * bMax) : '');
    }
    range.addEventListener('input', calc);
    calc();
  });

  // Formulieren: eerste veld met een fout in beeld en focus
  var invalid = document.querySelector('.dn-form [aria-invalid="true"]');
  if (invalid) {
    invalid.scrollIntoView({ block: 'center' });
    invalid.focus({ preventScroll: true });
  }

  // Filters: automatisch versturen na kiezen van een keuzelijst
  var filters = document.querySelector('.dn-filters');
  if (filters) {
    filters.querySelectorAll('select').forEach(function (s) {
      s.addEventListener('change', function () { filters.requestSubmit ? filters.requestSubmit() : filters.submit(); });
    });
  }
})();
