/* deedni: pagina's voor ingelogde gebruikers. Alles werkt ook zonder JavaScript; dit maakt het sneller en prettiger. */
(function () {
  'use strict';
  function $(s, r) { return (r || document).querySelector(s); }
  function $$(s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); }
  function json(el, a) { try { return JSON.parse(el.getAttribute(a) || 'null'); } catch (e) { return null; } }
  /** Melding onderin met "Ongedaan maken" (undo toast). */
  function herstelbaar(tekst, fn) { if (window.deedni && deedni.melding) deedni.melding(tekst, { herstel: fn }); }
  function overgang(fn) { if (window.deedni && deedni.overgang) deedni.overgang(fn); else fn(); }

  /* ---------- Tabs (aanbiedingen: nieuw en beantwoord) ---------- */
  function tabs(root) {
    $$('[data-tabs]', root).forEach(function (box) {
      if (box._tabs) return; box._tabs = true;
      var knoppen = $$('[role="tab"]', box);
      function kies(k, focus) {
        knoppen.forEach(function (b) {
          var aan = b === k;
          b.setAttribute('aria-selected', String(aan));
          b.tabIndex = aan ? 0 : -1;
          var p = document.getElementById(b.getAttribute('aria-controls'));
          if (p) p.hidden = !aan;
        });
        if (focus) k.focus();
      }
      knoppen.forEach(function (b, i) {
        b.addEventListener('click', function () { kies(b); });
        b.addEventListener('keydown', function (e) {
          if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
            e.preventDefault();
            kies(knoppen[(i + (e.key === 'ArrowRight' ? 1 : knoppen.length - 1)) % knoppen.length], true);
          }
        });
      });
      // Link naar een aanbod dat in een verborgen tab staat
      var doel = location.hash && document.getElementById(location.hash.slice(1));
      if (doel && box.contains(doel)) {
        var paneel = doel.closest('[role="tabpanel"]');
        var knop = paneel && knoppen.filter(function (b) { return b.getAttribute('aria-controls') === paneel.id; })[0];
        if (knop) { kies(knop); setTimeout(function () { doel.scrollIntoView({ block: 'center' }); }, 50); }
      }
    });
  }

  /* ---------- Inloggen: uitleg wisselt mee met de rol ---------- */
  $$('[data-hints]').forEach(function (form) {
    var hints = json(form, 'data-hints') || {};
    var label = $('[data-naam-label]', form), hint = $('[data-naam-hint]', form), input = $('#a-naam', form);
    $$('input[name="rol"]', form).forEach(function (r) {
      r.addEventListener('change', function () {
        var h = hints[r.value]; if (!h) return;
        if (label) label.textContent = h[0];
        if (hint) hint.textContent = h[1];
        if (input) input.setAttribute('autocomplete', r.value === 'werkgever' ? 'organization' : 'given-name');
      });
    });
  });

  /* ---------- Ja, maar: veld "iets anders" ---------- */
  document.addEventListener('change', function (e) {
    var cb = e.target;
    if (!cb.matches || !cb.matches('input[type="checkbox"]')) return;
    var form = cb.closest('form'); if (!form) return;
    var anders = $('[data-anders]', form), veld = $('[data-anders-veld]', form);
    if (anders && veld && (cb === anders)) { veld.hidden = !anders.checked; if (anders.checked) $('input', veld).focus(); }
  });
  document.addEventListener('deedni:open', function (e) {
    var veld = $('[data-anders-veld]', e.target);
    var anders = $('[data-anders]', e.target);
    if (veld && anders) veld.hidden = !anders.checked;
  }, true);

  /* ---------- Welkom voor werkgevers: beste matches als kaarten ---------- */
  $$('[data-matches]').forEach(function (box) {
    var M = json(box, 'data-matches') || [], nonce = box.getAttribute('data-nonce');
    var kaart = $('[data-m-kaart]', box), i = 0;
    function zet(k, v) { var el = $('[data-m="' + k + '"]', box); if (el) el.textContent = v; return el; }
    function toon() {
      if (!M.length) return;
      var m = M[i % M.length];
      zet('nr', (i % M.length) + 1);
      zet('code', m.code);
      zet('pct', m.p + '%');
      var ring = $('[data-m="ring"]', box); if (ring) ring.style.setProperty('--p', m.p);
      zet('titel', m.t);
      zet('meta', m.meta);
      var kp = $('[data-m="kpis"]', box);
      if (kp) { kp.innerHTML = ''; (m.k || []).forEach(function (x) { var s = document.createElement('span'), st = document.createElement('strong'); st.textContent = x[0]; s.appendChild(st); s.appendChild(document.createTextNode(x[1])); kp.appendChild(s); }); }
      var why = $('[data-m="why"]', box), wb = $('[data-m="why-blok"]', box);
      if (why) { why.innerHTML = ''; (m.w || []).forEach(function (x) { var li = document.createElement('li'); li.textContent = x; why.appendChild(li); }); }
      if (wb) wb.hidden = !(m.w && m.w.length);
      var url = $('[data-m="url"]', box); if (url) url.href = m.url;
      var ab = $('[data-m="aanbod"]', box);
      if (ab) { ab.setAttribute('data-vul', JSON.stringify(m.vul)); ab.setAttribute('data-tekst-vul', JSON.stringify(m.tekst)); }
      kaart.classList.remove('is-in'); void kaart.offsetWidth; kaart.classList.add('is-in');
    }
    function stuur(id, keuze) {
      var fd = new FormData();
      fd.append('deedni_action', 'bewaren'); fd.append('_deedni_nonce', nonce);
      fd.append('kandidaat_id', id); fd.append('keuze', keuze);
      fd.append('_deedni_sleutel', (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : String(Date.now()) + Math.random().toString(36).slice(2) + Math.random().toString(36).slice(2));
      return fetch(location.href, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-Deedni-Ajax': '1' } })
        .then(function (r) { return r.json(); })
        .then(function (res) { if (!res || !res.ok) throw new Error('mislukt'); return res; });
    }
    function leeg() { kaart.innerHTML = '<div class="t">Je hebt alle matches bekeken</div><p class="fine">Zoek zelf verder of kom later terug.</p>'; }
    var origineel = kaart.innerHTML;
    box.addEventListener('click', function (e) {
      var b = e.target.closest('[data-m-actie]'); if (!b || !M.length) return;
      var plek = i % M.length, m = M[plek], actie = b.getAttribute('data-m-actie');
      // Meteen door naar de volgende kaart (optimistic update); de server volgt op de achtergrond.
      M.splice(plek, 1);
      function terugzetten() {
        M.splice(Math.min(plek, M.length), 0, m);
        if (!$('[data-m="code"]', box)) kaart.innerHTML = origineel;
        i = plek; toon();
      }
      if (!M.length) leeg(); else { i = plek % M.length; toon(); }
      var tekst = actie === 'aan' ? m.code + ' is bewaard.' : m.code + ' staat onder Niet nu.';
      zet('melding', tekst + ' Je vindt ze terug op je overzicht.');
      stuur(m.id, actie).then(function () {
        if (window.deedni) deedni.melding(tekst, {
          herstel: function () {
            terugzetten();
            stuur(m.id, 'herstel').then(function () { zet('melding', 'Ongedaan gemaakt. ' + m.code + ' staat weer tussen je matches.'); })
              .catch(function () { zet('melding', 'Ongedaan maken lukte niet. Je vindt ' + m.code + ' op je overzicht.'); });
          }
        });
      }).catch(function () {
        terugzetten();
        zet('melding', 'Dat lukte niet. Controleer je verbinding en probeer het opnieuw.');
      });
    });
    toon();
  });

  /* ---------- Mijn profiel: stappen, lijsten, live voorbeeld, blokkeerlijst ---------- */
  $$('[data-profiel]').forEach(function (form) {
    var root = form.closest('.deedni-profiel') || document;
    var links = $$('.steps a[data-stap]', form);
    var volgorde = links.map(function (a) { return a.getAttribute('data-stap'); });
    function toon(stap, focus) {
      if (volgorde.indexOf(stap) < 0) return;
      volgorde.forEach(function (s, j) {
        var sec = document.getElementById('stap-' + s);
        if (sec) sec.classList.toggle('is-actief', s === stap);
        if (s === stap) links[j].setAttribute('aria-current', 'step'); else links[j].removeAttribute('aria-current');
      });
      if (focus) { var h = $('#stap-' + stap + ' h2'); if (h) h.focus({ preventScroll: true }); window.scrollTo({ top: 0 }); }
    }
    function vanHash(focus) {
      var h = location.hash.slice(1);
      if (h === 'blokkeerlijst') { toon('wat-zoek-je', false); setTimeout(function () { var b = document.getElementById('blokkeerlijst'); if (b) b.scrollIntoView(); }, 0); return; }
      toon(volgorde.indexOf(h) >= 0 ? h : volgorde[0], focus);
    }
    window.addEventListener('hashchange', function () { vanHash(true); });
    vanHash(false);
    // De knop "volgende" onthoudt het formulier; zonder wijzigingen schakelt hij alleen.
    var gewijzigd = false;
    form.addEventListener('input', function () { gewijzigd = true; });
    form.addEventListener('change', function () { gewijzigd = true; });
    form.addEventListener('click', function (e) {
      var b = e.target.closest('button[name="naar"]');
      if (b && !gewijzigd) { e.preventDefault(); location.hash = b.value; }
    });
    window.addEventListener('beforeunload', function (e) { if (gewijzigd && !form._weg) { e.preventDefault(); e.returnValue = ''; } });
    form.addEventListener('submit', function () { form._weg = true; });

    // Lijsten: werkervaring en projecten toevoegen of verwijderen
    var teller = { werkervaring: 1000, projecten: 1000, resultaat: 1000 };
    function nieuw(soort, vervang) {
      var tpl = $('template[data-sjabloon="' + soort + '"]', form);
      var html = tpl.innerHTML;
      Object.keys(vervang).forEach(function (k) { html = html.split(k).join(vervang[k]); });
      var t = document.createElement('template'); t.innerHTML = html.trim();
      return t.content.firstElementChild;
    }
    form.addEventListener('click', function (e) {
      var plus = e.target.closest('[data-plus]');
      if (plus) {
        var soort = plus.getAttribute('data-plus');
        var li = nieuw(soort, { '__i__': String(teller[soort]++) });
        $('[data-lijst="' + soort + '"]', form).appendChild(li);
        var d = $('details', li); if (d) d.open = true;
        var inp = $('input', li); if (inp) inp.focus();
        gewijzigd = true;
        return;
      }
      var weg = e.target.closest('[data-weg]');
      if (weg) {
        var item = weg.closest('[data-item]'), lijst = item.parentNode, na = item.nextSibling;
        var samen = $('[data-sam="titel"], [data-sam="functie"]', item);
        var naam = samen ? samen.textContent.trim() : '';
        item.remove(); gewijzigd = true;
        var volgende = $('[data-item] summary', lijst) || $('[data-plus]', form); if (volgende) volgende.focus();
        liveRes();
        herstelbaar((naam ? '“' + naam.slice(0, 40) + '” is' : 'Het item is') + ' weggehaald. Pas na opslaan is het echt weg.', function () {
          lijst.insertBefore(item, na && na.parentNode === lijst ? na : null); liveRes();
          var f = $('summary', item) || $('input', item); if (f) f.focus();
        });
        return;
      }
      var rp = e.target.closest('[data-res-plus]');
      if (rp) {
        var rl = $('[data-res-lijst]', rp.closest('fieldset'));
        var rij = nieuw('resultaat', { '__i__': rl.getAttribute('data-i'), '__j__': String(teller.resultaat++) });
        rl.appendChild(rij); $('input', rij).focus(); gewijzigd = true;
        return;
      }
      var wr = e.target.closest('[data-weg-res]');
      if (wr) {
        var r = wr.closest('[data-res-rij]'), l = r.parentNode, rna = r.nextSibling;
        r.remove(); gewijzigd = true; liveRes();
        var f = $('input', l) || $('[data-res-plus]', l.closest('fieldset')); if (f) f.focus();
        herstelbaar('Resultaat weggehaald.', function () { l.insertBefore(r, rna && rna.parentNode === l ? rna : null); liveRes(); $('input', r).focus(); });
      }
    });
    // Samenvatting van een item loopt mee met de titel
    form.addEventListener('input', function (e) {
      var k = e.target.getAttribute('data-sam-bron');
      if (k) { var it = e.target.closest('[data-item]'), s = it && $('[data-sam="' + k + '"]', it); if (s) s.textContent = e.target.value || (k === 'titel' ? 'Nieuw project' : 'Nieuwe werkervaring'); }
    });

    // Live voorbeeld rechts
    function zet(naam, waarde) { $$('[data-pv="' + naam + '"]', root).forEach(function (el) { el.textContent = waarde || '…'; }); }
    function afgerond(v) { var n = parseInt(String(v).replace(/[^\d]/g, ''), 10); if (!n) return ''; n = Math.floor(n / 100) * 100; return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }
    form.addEventListener('input', function (e) {
      var k = e.target.getAttribute('data-live');
      if (!k) return;
      zet(k, k === 'salaris' ? afgerond(e.target.value) : e.target.value);
      if (k === 'regio' && !e.target.value) { var p = $('#p-provincie', form); zet('regio', p && p.value ? p.options[p.selectedIndex].text : ''); }
    });
    var prov = $('#p-provincie', form);
    if (prov) prov.addEventListener('change', function () { var r = $('#p-regio', form); if (r && !r.value) zet('regio', prov.value ? prov.options[prov.selectedIndex].text : ''); });
    var pvRes = $('[data-pv-res]', root), pvTools = $('[data-pv-tools]', root);
    function liveRes() {
      if (!pvRes) return;
      pvRes.innerHTML = '';
      $$('[data-res-rij]', form).forEach(function (r) {
        var c = $('[data-res="cijfer"]', r).value.trim(), t = $('[data-res="tekst"]', r).value.trim();
        if (!c && !t) return;
        var d = document.createElement('div'); d.className = 'res';
        if (c) { var s = document.createElement('strong'); s.textContent = c; d.appendChild(s); }
        d.appendChild(document.createTextNode(t));
        pvRes.appendChild(d);
      });
    }
    form.addEventListener('input', function (e) { if (e.target.hasAttribute('data-res')) liveRes(); });
    form.addEventListener('change', function (e) {
      if (!e.target.hasAttribute('data-tool') || !pvTools) return;
      var namen = $$('input[data-tool]:checked', form).map(function (i) { return i.getAttribute('data-tool'); });
      pvTools.innerHTML = '';
      if (!namen.length) return;
      var box = document.createElement('div'); box.className = 'tools';
      namen.forEach(function (n) { var s = document.createElement('span'); s.className = 'tool'; s.textContent = n; box.appendChild(s); });
      pvTools.appendChild(box);
    });
    $$('[data-als]', root).forEach(function (b) {
      b.addEventListener('click', function () {
        var als = b.getAttribute('data-als');
        $$('[data-als]', root).forEach(function (x) { x.setAttribute('aria-pressed', String(x === b)); });
        $$('[data-alleen]', root).forEach(function (x) { x.hidden = x.getAttribute('data-alleen') !== als; });
        var l = $('[data-pv-label]', root); if (l) l.textContent = als === 'bedrijf' ? 'Bedrijf kijkt mee' : 'Bezoeker kijkt mee';
      });
    });

    // Blokkeerlijst als labels; het tekstveld eronder blijft de bron (werkt zonder JavaScript).
    var blocks = $('[data-blocks]', form), ta = $('#p-blokkeerlijst', form), add = $('[data-blok-toevoegen]', form), inp2 = $('[data-blok-input]', form);
    if (blocks && ta && add) {
      $('[data-blok-tekst]', form).hidden = true; blocks.hidden = false; add.hidden = false;
      var lijst = function () { return ta.value.split(/\r?\n/).map(function (s) { return s.trim(); }).filter(Boolean); };
      var teken = function () {
        blocks.innerHTML = '';
        lijst().forEach(function (naam, idx) {
          var s = document.createElement('span'); s.textContent = naam + ' ';
          var b = document.createElement('button'); b.type = 'button'; b.setAttribute('aria-label', 'Haal ' + naam + ' van de lijst'); b.textContent = '×';
          b.addEventListener('click', function () {
            var l = lijst(); l.splice(idx, 1); ta.value = l.join('\n'); gewijzigd = true; teken(); inp2.focus();
            herstelbaar(naam + ' staat niet meer op je blokkeerlijst.', function () { var l2 = lijst(); if (l2.indexOf(naam) < 0) l2.splice(idx, 0, naam); ta.value = l2.join('\n'); teken(); inp2.focus(); });
          });
          s.appendChild(b); blocks.appendChild(s);
        });
        if (!lijst().length) { var leeg = document.createElement('span'); leeg.className = 'fine'; leeg.style.background = 'none'; leeg.style.color = 'var(--muted)'; leeg.textContent = 'Nog niemand op je lijst.'; blocks.appendChild(leeg); }
      };
      var voeg = function () {
        var naam = inp2.value.trim(); if (!naam) { inp2.focus(); return; }
        var l = lijst(); if (l.indexOf(naam) < 0) l.push(naam);
        ta.value = l.join('\n'); inp2.value = ''; gewijzigd = true; teken(); inp2.focus();
      };
      $('[data-blok-plus]', form).addEventListener('click', voeg);
      inp2.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); voeg(); } });
      teken();
    }
  });

  /* ---------- Kandidaten zoeken: lijst ververst meteen ---------- */
  $$('[data-zoek-form]').forEach(function (form) {
    var root = form.closest('.deedni-zoeken') || document.body;
    var velden = function () { return Array.prototype.slice.call(form.elements); };
    var ft = $('[data-filter-toggle]', root), box = $('#filters-box', root);
    if (ft && box) ft.addEventListener('click', function () {
      var open = ft.getAttribute('aria-expanded') !== 'true';
      ft.setAttribute('aria-expanded', String(open)); box.classList.toggle('is-open', open);
      ft.textContent = open ? 'Verberg filters' : 'Toon filters';
    });
    var t = null, nr = 0, cache = {};
    /* Skeleton: duurt laden even, dan staan er grijze kaarten in de vorm van de resultaten. */
    function skelet() {
      var res = $('[data-zoek-resultaten]', root); if (!res) return;
      var lijst = $('.cands', res), n = Math.max(2, Math.min(4, lijst ? lijst.children.length : 3));
      var h = '<div class="cands skelet-lijst" aria-hidden="true">';
      for (var k = 0; k < n; k++) h += '<div class="card cand skelet"><div class="stack-s"><span class="sk sk--s"></span><span class="sk sk--l"></span><span class="sk sk--m"></span><span class="sk-rij"><span class="sk sk--chip"></span><span class="sk sk--chip"></span><span class="sk sk--chip"></span></span></div><div class="right"><span class="sk sk--s"></span><span class="sk sk--knop"></span></div></div>';
      h += '</div>';
      var kop = $('.result-head', res);
      Array.prototype.slice.call(res.children).forEach(function (c) { if (c !== kop && !c.classList.contains('active-filters')) res.removeChild(c); });
      res.insertAdjacentHTML('beforeend', h);
      res.setAttribute('aria-busy', 'true');
    }
    /* Resultaten in de pagina zetten, met een zachte overgang waar de browser dat kan. */
    function plaats(html, adres) {
      var doc = new DOMParser().parseFromString(html, 'text/html');
      var nieuw = $('[data-zoek-resultaten]', doc), oud = $('[data-zoek-resultaten]', root);
      overgang(function () {
        if (nieuw && oud) oud.replaceWith(nieuw);
        var nf = $('#filters-box', doc);
        if (nf) $$('#filters-box .opts .n', root).forEach(function (n, i) { var v = $$('.opts .n', nf)[i]; if (v) n.textContent = v.textContent; });
      });
      history.replaceState(null, '', adres);
      document.dispatchEvent(new CustomEvent('deedni:ververst', { detail: { el: nieuw || root } }));
    }
    function url() {
      var p = new URLSearchParams(new FormData(form));
      ['erv', 'besch'].forEach(function (k) { if (p.get(k) === '0') p['delete'](k); });
      if (!p.get('q')) p['delete']('q');
      var s = p.toString();
      return form.action.split('?')[0] + (s ? '?' + s : '');
    }
    /* Stale-while-revalidate: een zoekopdracht die je al eerder deed (filter aan en weer uit, terug naar
       pagina 1) staat er meteen. Op de achtergrond halen we de verse versie op en zetten die neer als er
       iets veranderd is. */
    function laad(adres) {
      var mijn = ++nr, bewaard = cache[adres], sk = null;
      if (bewaard) plaats(bewaard, adres);
      else {
        root.classList.add('is-laden');
        sk = setTimeout(function () { if (mijn === nr) skelet(); }, 180);
      }
      fetch(adres, { credentials: 'same-origin' }).then(function (r) { return r.text(); }).then(function (html) {
        cache[adres] = html;
        if (mijn !== nr || html === bewaard) return;
        plaats(html, adres);
      }).catch(function () { if (!bewaard) location.href = adres; }).finally(function () {
        clearTimeout(sk);
        if (mijn === nr) { root.classList.remove('is-laden'); var r = $('[data-zoek-resultaten]', root); if (r) r.removeAttribute('aria-busy'); }
      });
    }
    // De eerste pagina zelf ook bewaren, zodat terug naar "geen filters" meteen staat.
    cache[location.href] = document.documentElement.outerHTML;
    // "/" springt naar het zoekveld (zoals op veel sites). Het tekentje staat in het veld als hint.
    var q = form.querySelector('input[name="q"]');
    if (q && window.deedni && deedni.fijn && deedni.fijn.matches) {
      var hint = document.createElement('kbd'); hint.className = 'toets zoek-toets'; hint.textContent = '/'; hint.setAttribute('aria-hidden', 'true');
      q.parentNode.appendChild(hint);
      q.setAttribute('aria-keyshortcuts', '/');
    }
    document.addEventListener('keydown', function (e) {
      if (e.key !== '/' || !q || e.ctrlKey || e.metaKey || e.altKey) return;
      var el = e.target;
      if ((el && (el.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(el.tagName))) || document.querySelector('dialog[open]')) return;
      e.preventDefault(); q.focus(); q.select();
    });
    root.addEventListener('change', function (e) { if (e.target.form === form) laad(url()); });
    form.addEventListener('input', function (e) { if (e.target.name === 'q') { clearTimeout(t); t = setTimeout(function () { laad(url()); }, 300); } });
    form.addEventListener('submit', function (e) { e.preventDefault(); laad(url()); });
    root.addEventListener('click', function (e) {
      var a = e.target.closest('.active-filters a, .pager a, [data-wis]');
      if (!a) return;
      e.preventDefault();
      var u = new URL(a.href, location.href);
      velden().forEach(function (i) {
        if (i.type === 'checkbox' || i.type === 'radio') {
          var waarden = u.searchParams.getAll(i.name);
          i.checked = (i.type === 'radio' && !waarden.length) ? i.value === '0' : waarden.indexOf(i.value) >= 0;
        } else if (i.name === 'q') { i.value = u.searchParams.get('q') || ''; }
      });
      laad(u.href);
    });
  });

  /* ---------- Kandidaatprofiel: publieke weergave of na een ja ---------- */
  $$('[data-k-profiel]').forEach(function (root) {
    var knoppen = $$('[data-view]', root);
    function zet(v) {
      root.classList.toggle('is-shared', v === 'shared');
      knoppen.forEach(function (b) { b.setAttribute('aria-pressed', String(b.getAttribute('data-view') === v)); });
    }
    knoppen.forEach(function (b) { b.addEventListener('click', function () { zet(b.getAttribute('data-view')); }); });
    if (location.hash === '#gedeeld' && knoppen.length) zet('shared');
  });

  tabs(document);
  document.addEventListener('deedni:ververst', function (e) { tabs(e.detail.el.parentNode || document); });
})();
