/*
  deedni: één vast menu per rol.

  Gebruik op een pagina:
    <div data-menu data-rol="kandidaat" data-pagina="overzicht"></div>
    <script src="assets/menu.js"></script>
    ...
    <div data-voet data-rol="kandidaat"></div>

  Rollen: bezoeker, kandidaat (de marketeer), werkgever.
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
      thuis: 'kandidaat-overzicht.html',
      links: [
        { id: 'overzicht', tekst: 'Overzicht', href: 'kandidaat-overzicht.html' },
        { id: 'profiel', tekst: 'Mijn profiel', href: 'mijn-profiel.html' },
        { id: 'aanbiedingen', tekst: 'Aanbiedingen', href: 'kandidaat-overzicht.html#aanbiedingen', teller: 2 },
        { id: 'vragen', tekst: 'Vragen', href: 'index.html#vragen' }
      ],
      wie: { naam: 'Sanne', letters: 'S', rol: 'marketeer' }
    },
    werkgever: {
      thuis: 'werkgever-overzicht.html',
      links: [
        { id: 'overzicht', tekst: 'Overzicht', href: 'werkgever-overzicht.html' },
        { id: 'zoeken', tekst: 'Kandidaten zoeken', href: 'kandidaten-zoeken.html' },
        { id: 'bedrijf', tekst: 'Bedrijfsprofiel', href: 'werkgever-overzicht.html#bedrijfsprofiel' },
        { id: 'vragen', tekst: 'Vragen', href: 'index.html#vragen' }
      ],
      wie: { naam: 'Studio Noord', letters: 'SN', rol: 'werkgever' }
    }
  };

  function esc(s) {
    return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; });
  }

  function kop(rol, pagina) {
    var m = MENUS[rol] || MENUS.bezoeker;
    var h = '';
    h += '<a class="skip" href="#inhoud">Naar de inhoud</a>';
    h += '<header class="site-header" id="site-header" data-rol="' + esc(rol) + '">';
    h += '<div class="wrap header-row">';
    h += '<a class="logo" href="' + m.thuis + '" aria-label="deedni, naar ' + (rol === 'bezoeker' ? 'de homepage' : 'je overzicht') + '">deedni<i aria-hidden="true"></i></a>';
    h += '<button class="menu-toggle" id="menu-toggle" type="button" aria-expanded="false" aria-controls="site-nav">';
    h += '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"></path></svg>Menu</button>';
    h += '<nav class="site-nav" id="site-nav" aria-label="Hoofdmenu">';
    m.links.forEach(function (l) {
      var huidig = l.id === pagina ? ' aria-current="page"' : '';
      var teller = l.teller ? '<span class="nav-badge" aria-label="' + l.teller + ' nieuw">' + l.teller + '</span>' : '';
      h += '<a href="' + l.href + '"' + huidig + '>' + esc(l.tekst) + teller + '</a>';
    });
    if (m.knop) {
      h += '<a class="btn btn--primary btn--small nav-cta" href="' + m.knop.href + '">' + esc(m.knop.tekst) + '</a>';
    }
    if (m.wie) {
      h += '<span class="who"><span><span class="visually-hidden">Ingelogd als ' + m.wie.rol + ': </span>' + esc(m.wie.naam) + '</span><b aria-hidden="true">' + m.wie.letters + '</b></span>';
      h += '<a class="nav-out" href="index.html">Uitloggen</a>';
    }
    h += '</nav></div></header>';
    return h;
  }

  function voet(rol) {
    var m = MENUS[rol] || MENUS.bezoeker;
    var h = '<footer class="site-footer"><div class="wrap footer-row">';
    h += '<a class="logo" href="' + m.thuis + '">deedni<i aria-hidden="true"></i></a>';
    h += '<span>&copy; 2026 deedni. Ontwerp met fictieve gegevens.</span>';
    h += '<nav class="rolwissel" aria-label="Bekijk het ontwerp als"><span>Bekijk als:</span>';
    [['bezoeker', 'Bezoeker', 'index.html'], ['kandidaat', 'Marketeer', 'kandidaat-overzicht.html'], ['werkgever', 'Werkgever', 'werkgever-overzicht.html']].forEach(function (r) {
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
    if (plek) vervang(plek, kop(plek.getAttribute('data-rol'), plek.getAttribute('data-pagina')));

    var toggle = document.getElementById('menu-toggle');
    var nav = document.getElementById('site-nav');
    var header = document.getElementById('site-header');
    if (toggle && nav) {
      toggle.addEventListener('click', function () {
        var open = toggle.getAttribute('aria-expanded') !== 'true';
        toggle.setAttribute('aria-expanded', String(open));
        nav.classList.toggle('is-open', open);
      });
      nav.addEventListener('click', function (e) {
        if (e.target.closest('a')) { toggle.setAttribute('aria-expanded', 'false'); nav.classList.remove('is-open'); }
      });
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && nav.classList.contains('is-open')) {
          toggle.setAttribute('aria-expanded', 'false'); nav.classList.remove('is-open'); toggle.focus();
        }
      });
    }
    if (header) {
      var zet = function () { header.classList.toggle('is-scrolled', window.scrollY > 8); };
      window.addEventListener('scroll', zet, { passive: true });
      zet();
    }
  }

  function bouwVoet() {
    var v = document.querySelector('[data-voet]');
    if (v) vervang(v, voet(v.getAttribute('data-rol')));
  }

  window.deedniMenus = MENUS;
  // Het script staat direct onder de plaatshouder, dus het menu staat er meteen.
  bouw();
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bouwVoet);
  else bouwVoet();
})();
