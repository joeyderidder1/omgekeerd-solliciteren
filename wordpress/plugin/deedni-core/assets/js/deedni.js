/* deedni: gedeelde onderdelen op elke pagina.
   - Kop: menuknop op mobiel, accountmenu onder de initialen, lijntje onder de kop bij scrollen.
   - Pop-ups: een knop met data-open="id" opent <dialog id="id">. data-vul (JSON) vult velden op naam,
     data-tekst-vul (JSON) vult [data-tekst="sleutel"]. data-close sluit.
   - Formulieren met data-ajax versturen zonder de pagina te verlaten (zonder JavaScript werkt alles ook):
       data-ajax           in een pop-up: daarna het blok [data-klaar]
       data-ajax="vervang" daarna het blok [data-ajax-doel] opnieuw ophalen
       data-ajax="toggle"  bewaren aan/uit
   - Elementen met class="reveal" komen in beeld als je ernaartoe scrollt. */
(function () {
  'use strict';
  var minder = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  document.documentElement.classList.add('js');

  /* ---------- Kop ---------- */
  function kop() {
    var header = document.getElementById('site-header');
    if (!header) return;
    var toggle = document.getElementById('menu-toggle');
    var nav = document.getElementById('site-nav');
    function sluitNav() { if (toggle) { toggle.setAttribute('aria-expanded', 'false'); nav.classList.remove('is-open'); } }
    if (toggle && nav) {
      toggle.addEventListener('click', function () {
        var open = toggle.getAttribute('aria-expanded') !== 'true';
        toggle.setAttribute('aria-expanded', String(open));
        nav.classList.toggle('is-open', open);
      });
    }
    var knop = document.getElementById('acct-knop');
    var acct = knop && knop.parentNode;
    function sluitAcct() { if (knop) { knop.setAttribute('aria-expanded', 'false'); acct.classList.remove('is-open'); } }
    if (knop) {
      knop.addEventListener('click', function () {
        var open = knop.getAttribute('aria-expanded') !== 'true';
        knop.setAttribute('aria-expanded', String(open));
        acct.classList.toggle('is-open', open);
      });
      document.addEventListener('click', function (e) { if (!acct.contains(e.target)) sluitAcct(); });
    }
    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Escape') return;
      if (knop && acct.classList.contains('is-open')) { sluitAcct(); knop.focus(); }
      else if (nav && nav.classList.contains('is-open')) { sluitNav(); toggle.focus(); }
    });
    var zet = function () { header.classList.toggle('is-scrolled', window.scrollY > 8); };
    window.addEventListener('scroll', zet, { passive: true });
    zet();
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

  var regio;
  function alertRegio(tekst) {
    if (!regio) {
      regio = document.createElement('div');
      regio.className = 'dn-toast';
      regio.setAttribute('role', 'status');
      document.body.appendChild(regio);
    }
    regio.textContent = tekst;
    regio.classList.add('is-zichtbaar');
    clearTimeout(regio._t);
    regio._t = setTimeout(function () { regio.classList.remove('is-zichtbaar'); }, 4000);
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
      d.addEventListener('close', function () {
        if (d._opener && d._opener.focus) d._opener.focus();
        // Na een gelukte actie: pagina verversen, zodat alles klopt (status, tellers).
        if (d._herladen) { d._herladen = false; window.location.reload(); }
      });
    });
  }

  /* ---------- Formulieren zonder herladen ---------- */
  function verstuur(form, submitter) {
    var fd;
    try { fd = new FormData(form, submitter || undefined); } catch (err) {
      fd = new FormData(form);
      if (submitter && submitter.name) fd.append(submitter.name, submitter.value);
    }
    form.classList.add('is-bezig');
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

  document.addEventListener('submit', function (e) {
    var form = e.target;
    var soort = form.getAttribute('data-ajax');
    if (soort === null || !window.fetch) return;
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
    verstuur(form, submitter).then(function (res) {
      if (!res || !res.ok) { toonFouten(form, (res && res.errors) || { form: 'Er ging iets mis. Probeer het opnieuw.' }); return; }
      if (soort === 'toggle') {
        var knop = form.querySelector('[aria-pressed]');
        var keuze = form.querySelector('[name="keuze"]');
        var aan = !!res.bewaard;
        if (knop) {
          knop.setAttribute('aria-pressed', String(aan));
          if (knop.hasAttribute('data-aan')) knop.textContent = aan ? knop.getAttribute('data-aan') : knop.getAttribute('data-uit');
        }
        if (keuze) keuze.value = aan ? 'uit' : 'aan';
        if (res.melding) alertRegio(res.melding);
        return;
      }
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

  function start() { kop(); dialogen(); reveal(); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
  document.addEventListener('deedni:ververst', function (e) { reveal(e.detail.el); });

  window.deedni = { minderBeweging: minder, verstuur: verstuur, melding: alertRegio, reveal: reveal };
})();
