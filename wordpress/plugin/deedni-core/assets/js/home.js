/* deedni: beweging op de homepage. Elk onderdeel werkt alleen als het blok op de pagina staat. */
(function () {
  'use strict';
  var minder = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  var fijn = window.matchMedia && matchMedia('(hover: hover) and (pointer: fine)').matches;
  function $(s, r) { return (r || document).querySelector(s); }
  function $$(s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); }
  function json(el, a) { try { return JSON.parse(el.getAttribute(a) || 'null'); } catch (e) { return null; } }

  /* 1. Grote koppen opknippen in letters, zodat ze kunnen bewegen */
  var letters = [];
  $$('[data-mega] .w').forEach(function (w) {
    var tekst = w.textContent; w.textContent = '';
    Array.from(tekst).forEach(function (c) {
      var s = document.createElement('span'); s.className = 'ch'; s.textContent = c;
      s.style.setProperty('--i', letters.length % 24);
      w.appendChild(s); letters.push(s);
    });
  });

  /* 2. Letters reageren op de muis: dichtbij worden ze smaller en lichter */
  if (fijn && !minder && letters.length) {
    var mx = -9999, my = -9999, bezig = false;
    var teken = function () {
      bezig = false;
      for (var i = 0; i < letters.length; i++) {
        var r = letters[i].getBoundingClientRect();
        if (r.bottom < -50 || r.top > innerHeight + 50) continue;
        var dx = mx - (r.left + r.width / 2), dy = my - (r.top + r.height / 2);
        var f = Math.max(0, 1 - Math.sqrt(dx * dx + dy * dy) / 260);
        letters[i].style.setProperty('--wg', Math.round(700 - f * 300));
        letters[i].style.setProperty('--wd', Math.round(100 - f * 25));
      }
    };
    addEventListener('pointermove', function (e) { mx = e.clientX; my = e.clientY; if (!bezig) { bezig = true; requestAnimationFrame(teken); } }, { passive: true });
    document.addEventListener('pointerleave', function () { mx = my = -9999; requestAnimationFrame(teken); });
  }

  /* 3. Scrollen: koppen schuiven uit elkaar, foto gaat open, tijdlijn loopt mee, vaste knop op mobiel */
  var drift = $$('[data-drift]');
  var bigs = $$('[data-bigphoto]');
  var tls = $$('[data-tl]');
  var sticky = document.getElementById('sticky-cta'), hero = $('.hero');
  function scrol() {
    var vh = innerHeight;
    if (!minder) {
      drift.forEach(function (el) {
        var p;
        if (el.getAttribute('data-mode') === 'uit') p = Math.min(1.5, scrollY / vh) * 10;
        else { var r = (el.closest('section') || el).getBoundingClientRect(); p = (1 - Math.max(0, Math.min(1, (vh - r.top) / (vh * 0.85)))) * 14; }
        el.style.transform = 'translateX(' + (p * +el.getAttribute('data-drift')).toFixed(2) + 'vw)';
      });
      bigs.forEach(function (big) {
        var b = big.getBoundingClientRect();
        var k = 1 - Math.max(0, Math.min(1, -b.top / Math.max(1, b.height - vh) * 1.6));
        big.style.setProperty('--k', k.toFixed(3));
      });
    }
    tls.forEach(function (tl) {
      var items = tl.querySelectorAll('li');
      var t = tl.getBoundingClientRect();
      var p2 = minder ? 1 : Math.max(0, Math.min(1, (vh * 0.65 - t.top) / t.height));
      tl.style.setProperty('--tl', p2.toFixed(3));
      items.forEach(function (li, i) { li.classList.toggle('on', p2 >= i / Math.max(1, items.length - 1) * 0.92 + 0.02); });
    });
    if (sticky && hero) sticky.classList.toggle('is-visible', hero.getBoundingClientRect().bottom < 0);
  }
  var wacht = false;
  addEventListener('scroll', function () { if (!wacht) { wacht = true; requestAnimationFrame(function () { wacht = false; scrol(); }); } }, { passive: true });
  addEventListener('resize', scrol);
  scrol();

  /* 4. Hero: werk of personeel */
  $$('[data-hero-modes]').forEach(function (seg) {
    var modes = json(seg, 'data-hero-modes') || {};
    var root = seg.closest('.hero');
    var cta = $('[data-cta]', root), ctaTxt = $('[data-cta-txt]', root), cta2 = $('[data-cta-2]', root), note = $('[data-cta-note]', root), ico = $('[data-cta-ico]', root);
    var iconen = {};
    $$('[data-wie]', seg).forEach(function (b) {
      b.addEventListener('click', function () {
        var wie = b.getAttribute('data-wie'), m = modes[wie];
        if (!m) return;
        $$('[data-wie]', seg).forEach(function (x) { x.setAttribute('aria-pressed', String(x === b)); });
        $$('.hero-card [data-voor]', root).forEach(function (x) { x.hidden = x.getAttribute('data-voor') !== wie; });
        if (cta) { cta.href = m.href; }
        if (ctaTxt) ctaTxt.textContent = m.cta;
        if (ico) {
          if (!iconen.user) iconen.user = ico.innerHTML;
          ico.innerHTML = m.ico === 'search' ? '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="M16 16l4.5 4.5"/></svg>' : iconen.user;
        }
        if (cta2) { cta2.textContent = m.cta2; cta2.href = m.href2; cta2.hidden = !m.cta2; }
        if (note) {
          note.innerHTML = '';
          String(m.note || '').split(/\s*[·|]\s*/).forEach(function (deel) {
            if (!deel) return;
            var li = document.createElement('li'), sp = document.createElement('span');
            li.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>';
            sp.textContent = deel; li.appendChild(sp); note.appendChild(li);
          });
          note.hidden = !m.note;
        }
        document.dispatchEvent(new CustomEvent('deedni:wie', { detail: wie === 'werk' ? 'k' : 'w' }));
      });
    });
  });

  /* 5. Hero: probeer zelf ja of nee */
  $$('[data-demo-teksten]').forEach(function (demo) {
    var t = json(demo, 'data-demo-teksten') || {};
    var uit = $('[data-demo-uit]', demo), acts = $('[data-demo-acts]', demo), naam = $('[data-demo-naam]', demo);
    var verborgen = naam ? naam.innerHTML : '';
    acts.addEventListener('click', function (e) {
      var b = e.target.closest('[data-demo]'); if (!b) return;
      var k = b.getAttribute('data-demo');
      acts.hidden = true; uit.hidden = false; uit.textContent = (t[k] || '') + ' ';
      if (k === 'ja' && naam) { naam.innerHTML = ''; var s = document.createElement('strong'); s.className = 'shown'; s.textContent = t.naam || ''; naam.appendChild(s); }
      var opnieuw = document.createElement('button'); opnieuw.type = 'button'; opnieuw.className = 'link-btn'; opnieuw.textContent = 'Opnieuw';
      opnieuw.addEventListener('click', function () { acts.hidden = false; uit.hidden = true; if (naam) naam.innerHTML = verborgen; acts.querySelector('button').focus(); });
      uit.appendChild(opnieuw);
    });
  });

  /* 6. Lopende band pauzeren */
  $$('[data-band-pauze]').forEach(function (bp) {
    var band = bp.closest('.band-run');
    bp.addEventListener('click', function () {
      var p = !band.classList.contains('is-paused'); band.classList.toggle('is-paused', p);
      bp.setAttribute('aria-pressed', String(p)); bp.textContent = p ? 'Speel af' : 'Pauzeer';
    });
  });

  /* 7. Zo werkt het: stappen met automatische voortgang */
  $$('[data-how-stappen]').forEach(function (how) {
    var S = json(how, 'data-how-stappen') || {};
    var lijst = $('[data-how-lijst]', how), phone = $('[data-how-phone]', how), hp = $('[data-how-pauze]', how);
    var wie = how.getAttribute('data-how-start') || 'k', stap = 0, timer = null, gepauzeerd = !!minder, inBeeld = false;
    var dur = +how.getAttribute('data-how-duur') || 5200;
    function stappen() { return S[wie] || []; }
    function teken2() {
      var st = stappen();
      if (!st.length) return;
      if (stap >= st.length) stap = 0;
      lijst.innerHTML = '';
      st.forEach(function (s, i) {
        var li = document.createElement('li'), b = document.createElement('button');
        b.type = 'button'; if (i === stap) b.setAttribute('aria-current', 'step');
        var n = document.createElement('span'); n.className = 'n'; n.textContent = i + 1;
        var st1 = document.createElement('strong'); st1.textContent = s[0];
        var d = document.createElement('span'); d.className = 'd'; d.textContent = s[1];
        var bar = document.createElement('span'); bar.className = 'bar'; bar.setAttribute('aria-hidden', 'true'); bar.innerHTML = '<i style="--dur:' + dur + 'ms"></i>';
        b.appendChild(n); b.appendChild(st1); b.appendChild(d); b.appendChild(bar);
        b.addEventListener('click', function () { stap = i; teken2(); start(); });
        li.appendChild(b); lijst.appendChild(li);
      });
      if (phone) {
        var gevonden = false;
        $$('.scr', phone).forEach(function (s) { var aan = s.getAttribute('data-scr') === wie + stap; s.hidden = !aan; gevonden = gevonden || aan; });
        if (!gevonden) { var eerste = $('.scr[data-scr^="' + wie + '"]', phone); if (eerste) eerste.hidden = false; }
        phone.setAttribute('aria-label', 'Telefoon bij stap ' + (stap + 1) + ': ' + st[stap][0]);
      }
    }
    function start() {
      clearTimeout(timer);
      if (gepauzeerd || !inBeeld) return;
      timer = setTimeout(function () { stap = (stap + 1) % Math.max(1, stappen().length); teken2(); start(); }, dur);
    }
    function kies(w) {
      if (!S[w]) return;
      wie = w; stap = 0;
      $$('[data-how]', how).forEach(function (x) { x.setAttribute('aria-pressed', String(x.getAttribute('data-how') === w)); });
      teken2(); start();
    }
    $$('[data-how]', how).forEach(function (b) { b.addEventListener('click', function () { kies(b.getAttribute('data-how')); }); });
    document.addEventListener('deedni:wie', function (e) { kies(e.detail); });
    if (hp) {
      hp.addEventListener('click', function () {
        gepauzeerd = !gepauzeerd; how.classList.toggle('is-paused', gepauzeerd);
        hp.setAttribute('aria-pressed', String(gepauzeerd)); hp.textContent = gepauzeerd ? 'Speel de animatie af' : 'Pauzeer de animatie';
        start();
      });
      if (minder) { how.classList.add('is-paused'); hp.hidden = true; }
    }
    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (es) { inBeeld = es[0].isIntersecting; if (inBeeld) { teken2(); start(); } else clearTimeout(timer); }, { threshold: 0.35 }).observe(how);
    }
    teken2();
  });

  /* 8. Snel zoeken: het aantal rekent mee. Voorbeeld = vaste getallen, echt = de profielen op de site. */
  $$('[data-quick]').forEach(function (q) {
    var num = $('[data-q-num]', q), link = $('[data-q-link]', q);
    var echt = q.getAttribute('data-quick') === 'echt';
    var data = echt ? (json(q, 'data-quick-data') || []) : null;
    var basis = q.getAttribute('data-quick-link') || (link && link.href);
    function v(k) { var b = $('[data-q="' + k + '"] [aria-pressed="true"]', q); return b ? b.getAttribute('data-v') : ''; }
    function aantal() {
      if (!echt) { num.textContent = Math.max(3, Math.round(+v('spec') * +v('exp') * +v('reg'))); return; }
      var spec = v('spec'), erv = +v('exp') || 0, reg = v('reg');
      var n = data.filter(function (d) {
        if (spec && d[0].indexOf(spec) < 0) return false;
        if (erv && d[1] < erv) return false;
        if (reg === 'remote') return !!d[3];
        if (reg && d[2].indexOf(reg) < 0) return false;
        return true;
      }).length;
      num.textContent = n;
      if (link && basis) {
        var p = [];
        if (spec) p.push('vak[]=' + encodeURIComponent(spec));
        if (reg) p.push('regio[]=' + encodeURIComponent(reg));
        if (erv) p.push('erv=' + (erv >= 5 ? 5 : 2));
        link.href = basis + (p.length ? (basis.indexOf('?') < 0 ? '?' : '&') + p.join('&') : '');
      }
    }
    q.addEventListener('chip', aantal);
    aantal();
  });

  /* 9. Cijfers tellen op als ze in beeld komen */
  var fmt = function (n) { return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '.'); };
  var tellers = $$('[data-tel]');
  if (!minder && 'IntersectionObserver' in window && tellers.length) {
    var io = new IntersectionObserver(function (es) {
      es.forEach(function (en) {
        if (!en.isIntersecting) return; io.unobserve(en.target);
        var el = en.target, doel = +el.getAttribute('data-tel'), eind = el.textContent, t0 = performance.now();
        var voor = (eind.match(/^[^\d]*/) || [''])[0], na = (eind.match(/[^\d.,]*$/) || [''])[0];
        (function stapje(t) {
          var p = Math.min(1, (t - t0) / 1400), e = 1 - Math.pow(1 - p, 3);
          el.textContent = p < 1 ? voor + fmt(Math.round(doel * e)) + na : eind;
          if (p < 1) requestAnimationFrame(stapje);
        })(t0);
      });
    }, { threshold: 0.6 });
    tellers.forEach(function (el) { io.observe(el); });
  }

  /* 10. Privacy: zo ziet een bedrijf het, of de werknemer zelf */
  $$('[data-priv-blok]').forEach(function (blok) {
    var uitleg = $('[data-priv-uitleg]', blok), teksten = json(blok, 'data-uitleg') || [];
    $$('[data-priv]', blok).forEach(function (b) {
      b.addEventListener('click', function () {
        var w = b.getAttribute('data-priv') === 'w';
        $$('[data-priv]', blok).forEach(function (x) { x.setAttribute('aria-pressed', String(x === b)); });
        if (uitleg) uitleg.textContent = teksten[w ? 1 : 0] || '';
        $$('[data-geheim]', blok).forEach(function (dd) {
          var g = dd.getAttribute('data-geheim');
          dd.innerHTML = '';
          if (w) { var s = document.createElement('span'); s.className = 'open'; s.textContent = g; dd.appendChild(s); }
          else { var r = document.createElement('span'); r.className = 'redact' + (g.length > 16 ? ' redact--l' : ''); r.setAttribute('role', 'img'); r.setAttribute('aria-label', 'verborgen'); dd.appendChild(r); }
        });
      });
    });
  });

  /* 11. Kandidaten in een boog: de kaarten draaien mee met het scrollen.
     Hoe verder je langs de sectie scrolt, hoe verder de boog draait. Kaarten die aan de ene kant
     wegvallen, komen aan de andere kant weer binnen. Bij "minder beweging" staat alles stil. */
  $$('[data-boog]').forEach(function (boog) {
    var kaarten = $$('.boog-kaart', boog), n = kaarten.length;
    if (n < 2 || minder || !boog.hasAttribute('data-boog-scroll')) return;
    var midden = Math.floor((n - 1) / 2);
    var zicht = Math.min(4, Math.floor((n - 1) / 2));
    var bereik = Math.min(n, 6); // aantal kaartplekken dat de boog draait terwijl je langs de sectie scrolt
    var doel = 0, nu = 0, bezig = false;
    function meet() {
      var r = boog.getBoundingClientRect(), vh = window.innerHeight || 800;
      var p = (vh - r.top) / (vh + r.height);
      p = Math.max(0, Math.min(1, p));
      doel = (p - 0.5) * bereik;
    }
    function zet(pos) {
      kaarten.forEach(function (k, i) {
        var o = i - midden - pos;
        o = ((o % n) + n) % n;
        if (o > n / 2) o -= n;
        var zichtbaar = Math.max(0, Math.min(1, zicht + 0.6 - Math.abs(o)));
        k.style.setProperty('--o', o.toFixed(3));
        k.style.opacity = zichtbaar.toFixed(3);
        k.classList.toggle('is-weg', false);
        k.toggleAttribute('aria-hidden', zichtbaar < 0.5);
      });
    }
    function tik() {
      var verschil = doel - nu;
      nu = Math.abs(verschil) < 0.002 ? doel : nu + verschil * 0.14;
      zet(nu);
      if (nu !== doel) { requestAnimationFrame(tik); } else { bezig = false; }
    }
    function opScroll() {
      meet();
      if (!bezig) { bezig = true; requestAnimationFrame(tik); }
    }
    window.addEventListener('scroll', opScroll, { passive: true });
    window.addEventListener('resize', opScroll);
    meet(); nu = doel; zet(nu);
  });

  /* 12. Quotes: één tegelijk, de foto van die quote komt bovenop de stapel */
  $$('[data-quotes]').forEach(function (sec) {
    var items = $$('[data-q-item]', sec), fotos = $$('[data-q-foto]', sec), n = items.length;
    if (n < 2) return;
    var nav = $('[data-q-nav]', sec), teller = $('[data-q-teller]', sec), nu = 0;
    sec.classList.add('is-slider');
    function toon(i) {
      nu = (i + n) % n;
      items.forEach(function (it, j) { it.classList.toggle('is-aan', j === nu); it.hidden = j !== nu; });
      fotos.forEach(function (f) {
        var j = +f.getAttribute('data-q-foto');
        f.style.setProperty('--plek', ((j - nu) % n + n) % n);
      });
      if (teller) teller.textContent = (nu + 1) + ' / ' + n;
    }
    if (nav) {
      nav.hidden = false;
      $$('[data-q-stap]', nav).forEach(function (b) { b.addEventListener('click', function () { toon(nu + +b.getAttribute('data-q-stap')); }); });
    }
    fotos.forEach(function (f) { f.addEventListener('click', function () { toon(+f.getAttribute('data-q-foto')); }); });
    toon(0);
  });
})();
