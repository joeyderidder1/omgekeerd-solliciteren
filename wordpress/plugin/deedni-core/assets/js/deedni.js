/* deedni: gedeelde onderdelen op elke pagina.
   - Kop: menuknop op mobiel, accountmenu onder de initialen, lijntje onder de kop bij scrollen.
   - Pop-ups: een knop met data-open="id" opent <dialog id="id">. data-vul (JSON) vult velden op naam,
     data-tekst-vul (JSON) vult [data-tekst="sleutel"]. data-close sluit.
   - Formulieren met data-ajax versturen zonder de pagina te verlaten (zonder JavaScript werkt alles ook):
       data-ajax           in een pop-up: daarna het blok [data-klaar]
       data-ajax="vervang" daarna het blok [data-ajax-doel] opnieuw ophalen
       data-ajax="toggle"  bewaren aan/uit
   - Elementen met class="reveal" komen in beeld als je ernaartoe scrollt.
   UI-details (versie 2.5):
   - Uitklapmenu's ([data-uitklap]) openen met hover, met een "veilige driehoek" naar het menu toe, of met klikken.
   - Snel naar (Ctrl K / Cmd K): spring naar elke pagina uit het menu, of zoek meteen kandidaten.
   - Elk formulier krijgt een eenmalige sleutel mee (_deedni_sleutel), zodat dubbel versturen nooit dubbel uitvoert.
   - Bewaren reageert meteen (optimistic update) en "Niet meer bewaard" kun je ongedaan maken.
   - Pop-ups op de telefoon (onderaan het scherm) veeg je naar beneden weg.
   - Overgangen (View Transitions) en animaties staan uit bij "minder beweging". */
(function () {
  'use strict';
  var minder = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  document.documentElement.classList.add('js');

  var fijn = window.matchMedia ? matchMedia('(hover: hover) and (pointer: fine)') : { matches: false };
  var smal = window.matchMedia ? matchMedia('(max-width: 960px)') : { matches: false };

  /* ---------- Sluiten met Escape of de terugknop (predictive back op Android) ----------
     Wat open staat (menu, uitklapmenu) sluit met Escape. Waar de browser CloseWatcher kent, sluit
     dezelfde gebaar ook met de terug-veeg van Android, in plaats van de pagina te verlaten. */
  var openLijst = [];
  function bijOpen(item) {
    if (openLijst.indexOf(item) < 0) openLijst.push(item);
    if (window.CloseWatcher && !item.w) {
      try { item.w = new CloseWatcher(); item.w.onclose = function () { item.w = null; item.sluit(true); }; } catch (e) { item.w = null; }
    }
  }
  function bijDicht(item) {
    var i = openLijst.indexOf(item);
    if (i >= 0) openLijst.splice(i, 1);
    if (item.w) { var w = item.w; item.w = null; w.destroy(); }
  }
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape' || !openLijst.length || document.querySelector('dialog[open]')) return;
    var item = openLijst[openLijst.length - 1];
    if (item.w) return; // CloseWatcher handelt Escape zelf af
    e.preventDefault();
    item.sluit(true);
  });

  /* ---------- Uitklapmenu's met een veilige driehoek ----------
     Je gaat met de muis van de knop naar het menu. Ga je daarbij schuin over een stukje buiten het menu
     (de driehoek tussen je muis en de bovenkant van het menu), dan blijft het menu open. Zo sluit het
     niet per ongeluk als je diagonaal naar een link verderop beweegt (de "Amazon safe triangle"). */
  function inDriehoek(p, a, b, c) {
    function kant(p1, p2, p3) { return (p1.x - p3.x) * (p2.y - p3.y) - (p2.x - p3.x) * (p1.y - p3.y); }
    var d1 = kant(p, a, b), d2 = kant(p, b, c), d3 = kant(p, c, a);
    var neg = d1 < 0 || d2 < 0 || d3 < 0, pos = d1 > 0 || d2 > 0 || d3 > 0;
    return !(neg && pos);
  }
  function driehoek(start, r) {
    var m = 6; // een paar pixels speling rond het menu
    if (start.y <= r.top) return [start, { x: r.left - m, y: r.top }, { x: r.right + m, y: r.top }];
    if (start.y >= r.bottom) return [start, { x: r.left - m, y: r.bottom }, { x: r.right + m, y: r.bottom }];
    if (start.x <= r.left) return [start, { x: r.left, y: r.top - m }, { x: r.left, y: r.bottom + m }];
    return [start, { x: r.right, y: r.top - m }, { x: r.right, y: r.bottom + m }];
  }
  var uitklappers = [];
  function uitklap(box) {
    var knop = box.querySelector('.uitklap-knop'), menu = box.querySelector('.uitklap-menu');
    if (!knop || !menu || box._uitklap) return;
    box._uitklap = true;
    var item = { sluit: function (focus) { zet(false); if (focus) knop.focus(); } };
    var viaHover = false, hoverTijd = 0, timer = null, start = null;
    uitklappers.push(item);
    function zet(open) {
      if ((knop.getAttribute('aria-expanded') === 'true') === open) return;
      knop.setAttribute('aria-expanded', String(open));
      box.classList.toggle('is-open', open);
      if (open) {
        uitklappers.forEach(function (o) { if (o !== item) o.sluit(false); });
        bijOpen(item);
      } else {
        viaHover = false; start = null;
        document.removeEventListener('mousemove', volg);
        bijDicht(item);
      }
    }
    function hoverMag() { return fijn.matches && !smal.matches; }
    function volg(e) {
      if (!start) return;
      var p = { x: e.clientX, y: e.clientY };
      if (box.contains(e.target)) { clearTimeout(timer); return; }
      var t = driehoek(start, menu.getBoundingClientRect());
      clearTimeout(timer);
      if (inDriehoek(p, t[0], t[1], t[2])) {
        // Op weg naar het menu: open houden. Blijft de muis stilstaan in de driehoek, dan sluit het na even.
        timer = setTimeout(function () { zet(false); }, 350);
        start = p; // de driehoek schuift mee, zodat hij steeds smaller wordt richting het menu
      } else {
        timer = setTimeout(function () { zet(false); }, 90);
      }
    }
    knop.addEventListener('click', function () {
      var open = knop.getAttribute('aria-expanded') === 'true';
      // Net met de muis geopend? Dan maakt een klik het menu vast in plaats van het te sluiten.
      if (open && viaHover && Date.now() - hoverTijd < 600) { viaHover = false; return; }
      viaHover = false;
      zet(!open);
    });
    knop.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown') {
        e.preventDefault(); zet(true);
        var eerste = menu.querySelector('a, button:not([hidden])'); if (eerste) eerste.focus();
      }
    });
    menu.addEventListener('keydown', function (e) {
      if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp') return;
      var links = Array.prototype.filter.call(menu.querySelectorAll('a, button'), function (el) { return el.offsetParent !== null; });
      var i = links.indexOf(document.activeElement);
      if (i < 0) return;
      e.preventDefault();
      links[(i + (e.key === 'ArrowDown' ? 1 : links.length - 1)) % links.length].focus();
    });
    box.addEventListener('mouseenter', function () {
      if (!hoverMag()) return;
      clearTimeout(timer);
      document.removeEventListener('mousemove', volg);
      start = null;
      if (knop.getAttribute('aria-expanded') !== 'true') {
        timer = setTimeout(function () { viaHover = true; hoverTijd = Date.now(); zet(true); }, 70);
      }
    });
    box.addEventListener('mouseleave', function (e) {
      if (!hoverMag()) return;
      clearTimeout(timer);
      if (knop.getAttribute('aria-expanded') !== 'true') return;
      if (!viaHover && box.contains(document.activeElement)) return; // met klikken of toetsen geopend: blijft open
      start = { x: e.clientX, y: e.clientY };
      document.addEventListener('mousemove', volg);
      timer = setTimeout(function () { zet(false); }, 350);
    });
    document.addEventListener('click', function (e) { if (!box.contains(e.target)) zet(false); });
    box.addEventListener('focusout', function (e) { if (e.relatedTarget && !box.contains(e.relatedTarget)) zet(false); });
  }

  /* ---------- Kop ---------- */
  function kop() {
    var header = document.getElementById('site-header');
    if (!header) return;
    var toggle = document.getElementById('menu-toggle');
    var nav = document.getElementById('site-nav');
    if (toggle && nav) {
      var navItem = { sluit: function (focus) { zet(false); if (focus) toggle.focus(); } };
      var zet = function (open) {
        toggle.setAttribute('aria-expanded', String(open));
        nav.classList.toggle('is-open', open);
        if (open) bijOpen(navItem); else bijDicht(navItem);
      };
      toggle.addEventListener('click', function () { zet(toggle.getAttribute('aria-expanded') !== 'true'); });
    }
    header.querySelectorAll('[data-uitklap]').forEach(uitklap);
    var lijn = function () { header.classList.toggle('is-scrolled', window.scrollY > 8); };
    window.addEventListener('scroll', lijn, { passive: true });
    lijn();
  }

  /* ---------- Pop-ups ---------- */
  function vulFormulier(d, data) {
    Object.keys(data || {}).forEach(function (naam) {
      var v = data[naam] == null ? '' : String(data[naam]);
      d.querySelectorAll('[name="' + naam + '"]').forEach(function (el) {
        if (el.type === 'radio' || el.type === 'checkbox') el.checked = el.value === v;
        else el.value = v;
      });
    });
  }
  function vulTekst(d, data) {
    Object.keys(data || {}).forEach(function (k) {
      d.querySelectorAll('[data-tekst="' + k + '"]').forEach(function (el) { el.textContent = data[k]; });
    });
  }
  function json(el, attr) {
    try { return JSON.parse(el.getAttribute(attr) || '{}'); } catch (e) { return {}; }
  }
  function zetKlaar(d, klaar) {
    var f = d.querySelector('form');
    var k = d.querySelector('[data-klaar]');
    if (f && k) { f.hidden = klaar; k.hidden = !klaar; }
  }
  function toonFouten(form, errors) {
    var box = form.querySelector('[data-fouten]');
    var lijst = [];
    Object.keys(errors || {}).forEach(function (k) { lijst.push(errors[k]); });
    if (!box) { if (lijst.length) alertRegio(lijst.join(' ')); return; }
    box.innerHTML = '';
    if (!lijst.length) { box.hidden = true; return; }
    var ul = document.createElement('ul');
    lijst.forEach(function (t) { var li = document.createElement('li'); li.innerHTML = t; ul.appendChild(li); });
    box.appendChild(ul);
    box.hidden = false;
    box.setAttribute('tabindex', '-1');
    box.focus();
  }

  /* ---------- Toetsen tonen (shortcut hints) ----------
     <kbd class="toets" data-toets="k"> wordt "⌘K" op een Mac en "Ctrl K" elders. Alleen zichtbaar met een muis. */
  var mac = /Mac|iPhone|iPad/.test(navigator.platform || navigator.userAgent);
  function toetsTekst(t) { return t === 'esc' ? 'Esc' : (t.length === 1 && t !== '/' ? (mac ? '⌘' : 'Ctrl ') + t.toUpperCase() : t); }
  function toetsen(root) {
    (root || document).querySelectorAll('[data-toets]').forEach(function (k) { if (!k.textContent) k.textContent = toetsTekst(k.getAttribute('data-toets')); });
  }

  /* ---------- Korte melding onderin (toast), met "Ongedaan maken" ---------- */
  var regio, laatsteHerstel = null;
  function alertRegio(tekst, opties) {
    opties = opties || {};
    if (!regio) {
      regio = document.createElement('div');
      regio.className = 'dn-toast';
      regio.setAttribute('role', 'status');
      regio.innerHTML = '<span class="dn-toast-tekst"></span>';
      document.body.appendChild(regio);
      // Met de muis erop of de focus erin blijft de melding staan.
      var pauze = function () { clearTimeout(regio._t); };
      var verder = function () { if (!regio.matches(':hover') && !regio.contains(document.activeElement)) wegNa(regio._duur); };
      regio.addEventListener('mouseenter', pauze);
      regio.addEventListener('focusin', pauze);
      regio.addEventListener('mouseleave', verder);
      regio.addEventListener('focusout', verder);
    }
    regio.querySelector('.dn-toast-tekst').textContent = tekst;
    var oud = regio.querySelector('.dn-toast-actie');
    if (oud) oud.remove();
    laatsteHerstel = null;
    if (opties.herstel) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'dn-toast-actie';
      b.textContent = opties.actie || 'Ongedaan maken';
      if (fijn.matches) { var k = document.createElement('kbd'); k.className = 'toets'; k.textContent = toetsTekst('z'); b.appendChild(k); }
      laatsteHerstel = function () { laatsteHerstel = null; b.disabled = true; regio.classList.remove('is-zichtbaar'); opties.herstel(); };
      b.addEventListener('click', function () { if (laatsteHerstel) laatsteHerstel(); });
      regio.appendChild(b);
    }
    regio._duur = opties.herstel ? 7000 : 4000;
    regio.classList.add('is-zichtbaar');
    wegNa(regio._duur);
  }
  function wegNa(ms) {
    clearTimeout(regio._t);
    regio._t = setTimeout(function () { regio.classList.remove('is-zichtbaar'); laatsteHerstel = null; }, ms);
  }
  function typtInVeld(el) { return el && (el.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(el.tagName)); }
  document.addEventListener('keydown', function (e) {
    if (laatsteHerstel && (e.metaKey || e.ctrlKey) && !e.shiftKey && e.key.toLowerCase() === 'z' && !typtInVeld(e.target)) {
      e.preventDefault();
      laatsteHerstel();
    }
  });

  /* ---------- Overgangen (View Transitions) ---------- */
  function overgang(fn) {
    if (minder || !document.startViewTransition) { fn(); return; }
    document.startViewTransition(fn);
  }

  document.addEventListener('click', function (e) {
    var open = e.target.closest('[data-open]');
    if (open) {
      var d = document.getElementById(open.getAttribute('data-open'));
      if (d && typeof d.showModal === 'function') {
        e.preventDefault();
        zetKlaar(d, false);
        var f = d.querySelector('form');
        if (f && f.hasAttribute('data-ajax')) { var box = f.querySelector('[data-fouten]'); if (box) box.hidden = true; }
        vulFormulier(d, json(open, 'data-vul'));
        vulTekst(d, json(open, 'data-tekst-vul'));
        d.dispatchEvent(new CustomEvent('deedni:open', { detail: { opener: open } }));
        d.showModal();
        d._opener = open;
      }
      return;
    }
    var close = e.target.closest('[data-close]');
    if (close) { var dl = close.closest('dialog'); if (dl) { e.preventDefault(); dl.close(); } return; }

    var chip = e.target.closest('button.chip');
    if (chip) {
      var groep = chip.closest('[data-een]');
      if (groep) groep.querySelectorAll('button.chip').forEach(function (c) { c.setAttribute('aria-pressed', String(c === chip)); });
      else chip.setAttribute('aria-pressed', String(chip.getAttribute('aria-pressed') !== 'true'));
      chip.dispatchEvent(new CustomEvent('chip', { bubbles: true }));
    }
  });

  function dialogen() {
    document.querySelectorAll('dialog.modal').forEach(function (d) {
      if (d._klaar) return;
      d._klaar = true;
      d.addEventListener('click', function (e) { if (e.target === d) d.close(); });
      veegWeg(d);
      d.addEventListener('close', function () {
        d.style.transform = ''; d.style.transition = '';
        if (d._opener && d._opener.focus) d._opener.focus();
        // Na een gelukte actie: pagina verversen, zodat alles klopt (status, tellers).
        if (d._herladen) { d._herladen = false; window.location.reload(); }
      });
    });
  }

  /* ---------- Pop-up wegvegen (swipe to dismiss) ----------
     Op de telefoon staat een pop-up als blad onderaan het scherm. Trek de bovenkant naar beneden om hem te
     sluiten; laat je eerder los, dan veert hij terug. */
  var alsBlad = window.matchMedia ? matchMedia('(max-width: 560px)') : { matches: false };
  function veegWeg(d) {
    var greep = d.querySelector('.modal-head') || d.querySelector('.modal-done');
    if (!greep) return;
    var y0 = 0, t0 = 0, dy = 0, bezig = false, id = null;
    greep.addEventListener('pointerdown', function (e) {
      if (e.pointerType !== 'touch' || !alsBlad.matches || e.target.closest('button, a, input, select, textarea, label')) return;
      bezig = true; id = e.pointerId; y0 = e.clientY; t0 = Date.now(); dy = 0;
      d.style.transition = 'none';
    });
    greep.addEventListener('pointermove', function (e) {
      if (!bezig || e.pointerId !== id) return;
      dy = Math.max(0, e.clientY - y0);
      if (dy > 4 && !greep.hasPointerCapture(id)) greep.setPointerCapture(id);
      // Iets weerstand, zodat het als een echt blad aanvoelt.
      d.style.transform = 'translateY(' + (dy < 40 ? dy : 40 + (dy - 40) * 0.9) + 'px)';
    });
    function los(e) {
      if (!bezig || e.pointerId !== id) return;
      bezig = false;
      var snelheid = dy / Math.max(1, Date.now() - t0);
      d.style.transition = minder ? 'none' : 'transform .26s cubic-bezier(.2, .8, .2, 1)';
      if (dy > Math.min(140, d.offsetHeight * 0.3) || (dy > 30 && snelheid > 0.6)) {
        d.style.transform = 'translateY(100%)';
        setTimeout(function () { d.close(); }, minder ? 0 : 200);
      } else {
        d.style.transform = '';
      }
    }
    greep.addEventListener('pointerup', los);
    greep.addEventListener('pointercancel', los);
  }

  /* ---------- Formulieren zonder herladen ---------- */
  /* Idempotency key: elke keer dat iemand bewust op Versturen drukt, een nieuwe sleutel. Gaat hetzelfde
     verzoek nog eens (dubbelklik, opnieuw proberen na een haperende verbinding), dan is de sleutel gelijk
     en voert de server het niet opnieuw uit. */
  function nieuweSleutel() {
    if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
    var s = ''; for (var i = 0; i < 4; i++) s += Math.random().toString(36).slice(2, 10);
    return (Date.now().toString(36) + '-' + s).slice(0, 60);
  }
  function zetSleutel(form) {
    if (!form.querySelector('[name="deedni_action"]')) return;
    var veld = form.querySelector('input[name="_deedni_sleutel"]');
    if (!veld) { veld = document.createElement('input'); veld.type = 'hidden'; veld.name = '_deedni_sleutel'; form.appendChild(veld); }
    veld.value = nieuweSleutel();
  }
  // Gewone formulieren (zonder data-ajax) maar één keer versturen; de server vangt de rest op met de sleutel.
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (form.hasAttribute('data-ajax') && window.fetch) return;
    if (!form.querySelector('[name="deedni_action"]')) return;
    if (form._verstuurd) { e.preventDefault(); return; }
    form._verstuurd = true;
    zetSleutel(form);
  }, true);
  window.addEventListener('pageshow', function (e) {
    if (e.persisted) document.querySelectorAll('form').forEach(function (f) { f._verstuurd = false; });
  });

  function verstuur(form, submitter, fd, stil) {
    if (!fd) {
      try { fd = new FormData(form, submitter || undefined); } catch (err) {
        fd = new FormData(form);
        if (submitter && submitter.name) fd.append(submitter.name, submitter.value);
      }
    }
    if (!stil) form.classList.add('is-bezig');
    return fetch(form.getAttribute('action') || window.location.href, {
      method: 'POST',
      body: fd,
      credentials: 'same-origin',
      headers: { 'X-Deedni-Ajax': '1', 'Accept': 'application/json' }
    }).then(function (r) { return r.json(); }).finally(function () { form.classList.remove('is-bezig'); });
  }

  /** Een stuk van de pagina opnieuw ophalen (na Ja, Nee of een reactie op een tegenvoorstel). */
  function ververs(el) {
    var metId = el.id ? el : el.closest('[id]');
    return fetch(window.location.href, { credentials: 'same-origin' })
      .then(function (r) { return r.text(); })
      .then(function (html) {
        var doc = new DOMParser().parseFromString(html, 'text/html');
        var nieuw = metId && doc.getElementById(metId.id);
        if (nieuw) {
          metId.replaceWith(nieuw);
          var kop = nieuw.querySelector('[role="status"], .offer-state, h3, h2');
          if (kop) { kop.setAttribute('tabindex', '-1'); kop.focus({ preventScroll: true }); }
          document.dispatchEvent(new CustomEvent('deedni:ververst', { detail: { el: nieuw } }));
        } else if (metId) {
          metId.remove();
        }
        // Tellers in het menu en de tabs bijwerken
        ['.nav-badge', '[data-tab-teller]'].forEach(function (sel) {
          var oud = document.querySelectorAll(sel), vers = doc.querySelectorAll(sel);
          oud.forEach(function (o, i) { if (vers[i]) o.textContent = vers[i].textContent; });
        });
      });
  }

  /* Bewaren aan/uit: de knop wisselt meteen (optimistic update). Lukt het niet, dan springt hij terug.
     Klik je snel een paar keer, dan gaan de verzoeken netjes na elkaar. */
  function bewaarMeteen(form) {
    var knop = form.querySelector('[aria-pressed]');
    var keuze = form.querySelector('[name="keuze"]');
    if (!knop || !keuze) return false;
    var was = knop.getAttribute('aria-pressed') === 'true';
    var aan = !was;
    zetSleutel(form);
    var fd = new FormData(form);
    fd.set('keuze', aan ? 'aan' : 'uit');
    function toon(staat) {
      knop.setAttribute('aria-pressed', String(staat));
      if (knop.hasAttribute('data-aan')) knop.textContent = staat ? knop.getAttribute('data-aan') : knop.getAttribute('data-uit');
      keuze.value = staat ? 'uit' : 'aan';
    }
    toon(aan);
    form._rij = (form._rij || Promise.resolve()).then(function () {
      return verstuur(form, null, fd, true).then(function (res) {
        if (!res || !res.ok) throw new Error('mislukt');
        toon(!!res.bewaard);
        if (!res.bewaard) alertRegio(res.melding || 'Niet meer bewaard.', { herstel: function () { form.requestSubmit ? form.requestSubmit() : form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true })); } });
        else if (res.melding) alertRegio(res.melding);
      }).catch(function () {
        toon(was);
        alertRegio('Dat lukte niet. Controleer je verbinding en probeer het opnieuw.');
      });
    });
    return true;
  }

  document.addEventListener('submit', function (e) {
    var form = e.target;
    var soort = form.getAttribute('data-ajax');
    if (soort === null || !window.fetch) return;
    if (soort === 'toggle') { e.preventDefault(); if (!bewaarMeteen(form)) form.submit(); return; }
    // Al onderweg (Enter twee keer, dubbelklik): niet nog eens.
    if (form.classList.contains('is-bezig')) { e.preventDefault(); return; }
    var submitter = e.submitter || null;
    var alleenBewaren = submitter && submitter.hasAttribute('data-bewaar-knop');
    if (!alleenBewaren && form.hasAttribute('novalidate')) {
      // Eigen controle: verplichte velden
      var leeg = Array.prototype.filter.call(form.querySelectorAll('[required]'), function (el) { return !String(el.value).trim(); });
      if (leeg.length) {
        e.preventDefault();
        leeg.forEach(function (el) { el.setAttribute('aria-invalid', 'true'); });
        toonFouten(form, { leeg: 'Vul de verplichte velden in: ' + leeg.map(function (el) { var l = form.querySelector('label[for="' + el.id + '"]'); return l ? l.firstChild.textContent.trim() : el.name; }).join(', ') + '.' });
        leeg[0].focus();
        return;
      }
    }
    e.preventDefault();
    zetSleutel(form);
    verstuur(form, submitter).then(function (res) {
      if (!res || !res.ok) { toonFouten(form, (res && res.errors) || { form: 'Er ging iets mis. Probeer het opnieuw.' }); return; }
      if (soort === 'vervang') {
        var doel = form.closest('[data-ajax-doel]') || form;
        if (res.melding) alertRegio(res.melding);
        ververs(doel);
        return;
      }
      var d = form.closest('dialog');
      if (d) {
        var tekst = d.querySelector('[data-klaar-tekst]');
        if (alleenBewaren && tekst && res.melding) tekst.textContent = res.melding;
        var kop = d.querySelector('[data-klaar] h2');
        if (alleenBewaren && kop) kop.textContent = 'Bewaard';
        zetKlaar(d, true);
        d._herladen = true;
        var f = d.querySelector('[data-klaar] h2, [data-klaar] [tabindex]');
        if (f) f.focus();
        return;
      }
      if (res.url) { window.location.href = res.url; return; }
      if (res.melding) alertRegio(res.melding);
    }).catch(function () {
      // Lukt het niet via JavaScript, dan gewoon versturen.
      form.removeAttribute('data-ajax');
      if (submitter && submitter.name) {
        var h = document.createElement('input'); h.type = 'hidden'; h.name = submitter.name; h.value = submitter.value; form.appendChild(h);
      }
      form.submit();
    });
  });
  document.addEventListener('input', function (e) { if (e.target.getAttribute && e.target.getAttribute('aria-invalid') === 'true' && String(e.target.value).trim()) e.target.removeAttribute('aria-invalid'); });

  /* ---------- In beeld schuiven ---------- */
  function reveal(root) {
    var items = (root || document).querySelectorAll('.reveal:not(.is-in)');
    if (minder || !('IntersectionObserver' in window)) { items.forEach(function (el) { el.classList.add('is-in'); }); return; }
    var io = new IntersectionObserver(function (es) {
      es.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('is-in'); io.unobserve(en.target); } });
    }, { rootMargin: '0px 0px -10% 0px' });
    items.forEach(function (el) { io.observe(el); });
  }

  /* ---------- Snel naar (command menu): Ctrl K of Cmd K ---------- */
  var snel = null;
  function snelItems() {
    var gezien = {}, items = [];
    document.querySelectorAll('#site-nav a[href], #acct-menu a[href]').forEach(function (a) {
      var t = a.textContent.replace(/\s+/g, ' ').trim(), href = a.href;
      if (!t || gezien[href]) return;
      gezien[href] = true;
      items.push({ tekst: t, url: href, uit: a.classList.contains('nav-out') });
    });
    // Uitloggen als laatste, zodat Enter nooit per ongeluk uitlogt.
    return items.filter(function (i) { return !i.uit; }).concat(items.filter(function (i) { return i.uit; }));
  }
  function maakSnel() {
    var d = document.createElement('dialog');
    d.className = 'modal snel';
    d.setAttribute('aria-label', 'Snel naar');
    d.innerHTML = '<div class="snel-zoek"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>' +
      '<input type="text" role="combobox" aria-expanded="true" aria-controls="snel-lijst" aria-autocomplete="list" aria-label="Waar wil je heen?" placeholder="Waar wil je heen?" autocomplete="off" spellcheck="false">' +
      '<kbd class="toets" data-toets="esc"></kbd></div><ul class="snel-lijst" id="snel-lijst" role="listbox" aria-label="Pagina\'s"></ul>' +
      '<p class="snel-voet fine"><span><kbd class="toets">↑</kbd><kbd class="toets">↓</kbd> kiezen</span><span><kbd class="toets">Enter</kbd> openen</span></p>';
    document.body.appendChild(d);
    toetsen(d);
    var input = d.querySelector('input'), lijst = d.querySelector('ul'), header = document.getElementById('site-header');
    var zoekUrl = header && header.getAttribute('data-zoek-url');
    var alle = [], zichtbaar = [], actief = 0;
    function teken() {
      var q = input.value.trim().toLowerCase();
      zichtbaar = alle.filter(function (i) { return !q || i.tekst.toLowerCase().indexOf(q) >= 0; });
      if (zoekUrl && q) {
        zichtbaar.push({ tekst: 'Zoek kandidaten op “' + input.value.trim() + '”', url: zoekUrl + (zoekUrl.indexOf('?') < 0 ? '?' : '&') + 'q=' + encodeURIComponent(input.value.trim()), zoek: true });
      }
      actief = Math.min(actief, Math.max(0, zichtbaar.length - 1));
      lijst.innerHTML = '';
      zichtbaar.forEach(function (it, i) {
        var li = document.createElement('li');
        li.id = 'snel-' + i;
        li.setAttribute('role', 'option');
        li.setAttribute('aria-selected', String(i === actief));
        li.className = it.zoek ? 'is-zoek' : '';
        li.textContent = it.tekst;
        li.addEventListener('click', function () { ga(i); });
        li.addEventListener('mousemove', function () { if (actief !== i) { actief = i; markeer(); } });
        lijst.appendChild(li);
      });
      if (!zichtbaar.length) { var leeg = document.createElement('li'); leeg.className = 'snel-leeg'; leeg.textContent = 'Niets gevonden.'; lijst.appendChild(leeg); }
      markeer();
    }
    function markeer() {
      lijst.querySelectorAll('[role="option"]').forEach(function (li, i) { li.setAttribute('aria-selected', String(i === actief)); });
      var el = document.getElementById('snel-' + actief);
      if (el) { input.setAttribute('aria-activedescendant', el.id); el.scrollIntoView({ block: 'nearest' }); } else input.removeAttribute('aria-activedescendant');
    }
    function ga(i) { var it = zichtbaar[i]; if (it) { d.close(); window.location.href = it.url; } }
    input.addEventListener('input', function () { actief = 0; teken(); });
    input.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        if (zichtbaar.length) { actief = (actief + (e.key === 'ArrowDown' ? 1 : zichtbaar.length - 1)) % zichtbaar.length; markeer(); }
      } else if (e.key === 'Enter') { e.preventDefault(); ga(actief); }
    });
    d.addEventListener('click', function (e) { if (e.target === d) d.close(); });
    d.addEventListener('close', function () { if (d._opener && d._opener.focus) d._opener.focus(); });
    return {
      open: function (opener) {
        if (d.open) return;
        uitklappers.forEach(function (o) { o.sluit(false); });
        alle = snelItems(); input.value = ''; actief = 0; teken();
        d._opener = opener || document.activeElement;
        d.showModal();
        input.focus();
      }
    };
  }
  function snelMenu() {
    if (!document.querySelector('#acct-menu')) return; // alleen voor ingelogde gebruikers
    if (fijn.matches) document.querySelectorAll('.snel-li').forEach(function (li) { li.hidden = false; });
    document.addEventListener('keydown', function (e) {
      if ((e.metaKey || e.ctrlKey) && !e.shiftKey && !e.altKey && e.key.toLowerCase() === 'k') {
        if (document.querySelector('dialog[open]:not(.snel)')) return;
        e.preventDefault();
        snel = snel || maakSnel();
        snel.open();
      }
    });
    document.addEventListener('click', function (e) {
      var b = e.target.closest('[data-snel]');
      if (!b) return;
      snel = snel || maakSnel();
      snel.open(document.getElementById('acct-knop'));
    });
  }

  /* ---------- Foto's: rustige kleurvlakken tot ze geladen zijn (in plaats van BlurHash) ---------- */
  function fotos(root) {
    (root || document).querySelectorAll('.bigphoto img, .ph img, .priv-photo img, .q-foto img, .scr--beeld img').forEach(function (img) {
      if (img.complete && img.naturalWidth) return;
      img.classList.add('is-laden');
      var klaar = function () { img.classList.remove('is-laden'); };
      img.addEventListener('load', klaar, { once: true });
      img.addEventListener('error', klaar, { once: true });
    });
  }

  /* ---------- Middle truncation: lange bestandsnamen ingekort in het midden ----------
     "cv-sam-jansen-marketing-2026.pdf" wordt "cv-sam-jan…2026.pdf": begin en extensie blijven leesbaar.
     De volledige naam staat in de tooltip (title) en blijft voor schermlezers in aria-label. */
  function midden(root) {
    var els = (root || document).querySelectorAll('[data-midden]');
    if (!els.length) return;
    function kort(el) {
      var vol = el._vol || (el._vol = el.textContent);
      el.textContent = vol;
      if (el.scrollWidth <= el.clientWidth + 1) { el.removeAttribute('aria-label'); return; }
      el.setAttribute('aria-label', vol);
      var eind = Math.min(12, Math.floor(vol.length / 3)), lo = 1, hi = vol.length - eind - 1, beste = 1;
      while (lo <= hi) {
        var m = (lo + hi) >> 1;
        el.textContent = vol.slice(0, m) + '…' + vol.slice(vol.length - eind);
        if (el.scrollWidth <= el.clientWidth + 1) { beste = m; lo = m + 1; } else hi = m - 1;
      }
      el.textContent = vol.slice(0, beste) + '…' + vol.slice(vol.length - eind);
    }
    els.forEach(kort);
    if (window.ResizeObserver) {
      var ro = new ResizeObserver(function (rs) { rs.forEach(function (r) { kort(r.target); }); });
      els.forEach(function (el) { ro.observe(el); });
    }
  }

  function start() { kop(); dialogen(); reveal(); toetsen(); snelMenu(); fotos(); midden(); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
  document.addEventListener('deedni:ververst', function (e) { reveal(e.detail.el); });

  window.deedni = { minderBeweging: minder, verstuur: verstuur, melding: alertRegio, reveal: reveal, overgang: overgang, sleutel: zetSleutel, toetsen: toetsen, fijn: fijn };
})();
