/*
  deedni: één vast menu per rol.

  Gebruik op een pagina:
    <div data-menu data-rol="kandidaat" data-pagina="overzicht"></div>
    <script src="assets/menu.js"></script>
    ...
    <div data-voet data-rol="kandidaat"></div>

  Rollen: bezoeker, kandidaat (de werkzoekende), werkgever.
  data-rol="auto" leest de rol uit het adres: index.html?rol=kandidaat of ?rol=werkgever.
  Zonder rol in het adres is het de bezoeker. Zo is er één homepage voor iedereen.

  Wil je een menu aanpassen? Dan pas je alleen MENUS hieronder aan.
  Alle pagina's van die rol krijgen dan hetzelfde menu.
*/
(function () {
  'use strict';

  var MENUS = {
    bezoeker: {
      thuis: 'index.html',
      links: [
        { id: 'zo-werkt-het', tekst: 'Zo werkt het', href: 'index.html#zo-werkt-het' },
        { id: 'privacy', tekst: 'Privacy', href: 'index.html#privacy' },
        { id: 'prijzen', tekst: 'Prijzen', href: 'index.html#prijzen' },
        { id: 'vragen', tekst: 'Vragen', href: 'index.html#vragen' },
        { id: 'inloggen', tekst: 'Inloggen', href: 'inloggen.html' }
      ],
      knop: { id: 'aanmelden', tekst: 'Account maken', href: 'inloggen.html#aanmelden' }
    },
    kandidaat: {
      thuis: 'index.html?rol=kandidaat',
      links: [
        { id: 'home', tekst: 'Home', href: 'index.html?rol=kandidaat' },
        { id: 'overzicht', tekst: 'Aanbiedingen', href: 'kandidaat-overzicht.html', teller: 2 },
        { id: 'vragen', tekst: 'Vragen', href: 'index.html?rol=kandidaat#vragen' }
      ],
      wie: { naam: 'Sanne', letters: 'S', rol: 'kandidaat' },
      account: [
        { id: 'profiel', tekst: 'Mijn profiel', href: 'mijn-profiel.html' },
        { id: 'blokkeerlijst', tekst: 'Blokkeerlijst', href: 'mijn-profiel.html#blokkeerlijst' }
      ]
    },
    werkgever: {
      thuis: 'index.html?rol=werkgever',
      links: [
        { id: 'home', tekst: 'Home', href: 'index.html?rol=werkgever' },
        { id: 'overzicht', tekst: 'Overzicht', href: 'werkgever-overzicht.html' },
        { id: 'zoeken', tekst: 'Kandidaten zoeken', href: 'kandidaten-zoeken.html' },
        { id: 'vragen', tekst: 'Vragen', href: 'index.html?rol=werkgever#vragen' }
      ],
      wie: { naam: 'Groenwerk Utrecht', letters: 'GU', rol: 'werkgever' },
      account: [
        { id: 'bedrijf', tekst: 'Bedrijfsprofiel', href: 'werkgever-overzicht.html#bedrijfsprofiel' }
      ]
    }
  };

  function rolUitAdres() {
    var m = /[?&]rol=(kandidaat|werkgever)\b/.exec(location.search);
    return m ? m[1] : 'bezoeker';
  }
  function echteRol(r) { return r === 'auto' ? rolUitAdres() : (MENUS[r] ? r : 'bezoeker'); }

  function esc(s) {
    return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; });
  }

  function kop(rol, pagina) {
    var m = MENUS[rol];
    var h = '';
    h += '<a class="skip" href="#inhoud">Naar de inhoud</a>';
    h += '<header class="site-header" id="site-header" data-rol="' + esc(rol) + '">';
    h += '<div class="wrap header-row">';
    h += '<a class="logo" href="' + m.thuis + '" aria-label="deedni, naar de homepage">deedni<i aria-hidden="true"></i></a>';
    h += '<button class="menu-toggle" id="menu-toggle" type="button" aria-expanded="false" aria-controls="site-nav">';
    h += '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"></path></svg>Menu</button>';
    h += '<nav class="site-nav" id="site-nav" aria-label="Hoofdmenu">';
    m.links.forEach(function (l) {
      var huidig = l.id === pagina ? ' aria-current="page"' : '';
      var teller = l.teller ? '<span class="nav-badge"><span class="visually-hidden">, </span>' + l.teller + '<span class="visually-hidden"> nieuw</span></span>' : '';
      h += '<a href="' + l.href + '"' + huidig + '>' + esc(l.tekst) + teller + '</a>';
    });
    if (m.knop) {
      h += '<a class="btn btn--primary btn--small nav-cta" href="' + m.knop.href + '">' + esc(m.knop.tekst) + '</a>';
    }
    if (m.wie) {
      var inAccount = m.account.some(function (a) { return a.id === pagina; });
      h += '<div class="acct' + (inAccount ? ' is-current' : '') + '">';
      h += '<button type="button" class="who" id="acct-knop" aria-expanded="false" aria-controls="acct-menu">';
      h += '<span><span class="visually-hidden">Account van </span>' + esc(m.wie.naam) + '</span><b aria-hidden="true">' + m.wie.letters + '</b>';
      h += '<svg class="chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg></button>';
      h += '<ul class="acct-menu" id="acct-menu">';
      h += '<li class="acct-wie">' + esc(m.wie.naam) + ', ingelogd als ' + m.wie.rol + '</li>';
      m.account.forEach(function (a) {
        h += '<li><a href="' + a.href + '"' + (a.id === pagina ? ' aria-current="page"' : '') + '>' + esc(a.tekst) + '</a></li>';
      });
      h += '<li><a class="nav-out" href="index.html">Uitloggen</a></li>';
      h += '</ul></div>';
    }
    h += '</nav></div></header>';
    return h;
  }

  function voet(rol) {
    var m = MENUS[rol];
    var h = '<footer class="site-footer"><div class="wrap footer-row">';
    h += '<a class="logo" href="' + m.thuis + '">deedni<i aria-hidden="true"></i></a>';
    h += '<span>&copy; 2026 deedni. Ontwerp met fictieve gegevens.</span>';
    h += '<nav class="rolwissel" aria-label="Bekijk het ontwerp als"><span>Bekijk als:</span>';
    [['bezoeker', 'Bezoeker', 'index.html'], ['kandidaat', 'Kandidaat', 'index.html?rol=kandidaat'], ['werkgever', 'Werkgever', 'index.html?rol=werkgever']].forEach(function (r) {
      h += '<a href="' + r[2] + '"' + (r[0] === rol ? ' aria-current="true"' : '') + '>' + r[1] + '</a>';
    });
    h += '<a href="overzicht.html">Alle pagina\'s</a></nav>';
    h += '</div></footer>';
    return h;
  }

  function vervang(el, html) {
    var t = document.createElement('div');
    t.innerHTML = html;
    var p = el.parentNode;
    while (t.firstChild) p.insertBefore(t.firstChild, el);
    p.removeChild(el);
  }

  function bouw() {
    var plek = document.querySelector('[data-menu]');
    if (!plek) return;
    var rol = echteRol(plek.getAttribute('data-rol'));
    document.documentElement.setAttribute('data-rol', rol);
    vervang(plek, kop(rol, plek.getAttribute('data-pagina')));

    var toggle = document.getElementById('menu-toggle');
    var nav = document.getElementById('site-nav');
    var header = document.getElementById('site-header');
    function sluitNav() { toggle.setAttribute('aria-expanded', 'false'); nav.classList.remove('is-open'); }
    toggle.addEventListener('click', function () {
      var open = toggle.getAttribute('aria-expanded') !== 'true';
      toggle.setAttribute('aria-expanded', String(open));
      nav.classList.toggle('is-open', open);
    });
    nav.addEventListener('click', function (e) { if (e.target.closest('a')) { sluitNav(); sluitAcct(); } });

    // Accountmenu onder de initialen
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
      else if (nav.classList.contains('is-open')) { sluitNav(); toggle.focus(); }
    });

    var zet = function () { header.classList.toggle('is-scrolled', window.scrollY > 8); };
    window.addEventListener('scroll', zet, { passive: true });
    zet();
  }

  function bouwVoet() {
    var v = document.querySelector('[data-voet]');
    if (v) vervang(v, voet(echteRol(v.getAttribute('data-rol'))));
  }

  window.deedniMenus = MENUS;
  window.deedniRol = rolUitAdres;
  // Het script staat direct onder de plaatshouder, dus het menu staat er meteen.
  bouw();
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bouwVoet);
  else bouwVoet();
})();
