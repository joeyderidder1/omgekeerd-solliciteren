/* deedni: kleine gedeelde onderdelen voor de nieuwe pagina's.
   - Pop-ups: een knop met data-open="id" opent <dialog id="id">. data-close sluit hem.
     Een <form data-verstuur> in een pop-up toont na versturen het blok [data-klaar].
   - Keuzechips: een knop met class="chip" wisselt aria-pressed (in een groep met data-een kies je er één).
   - Elementen met class="reveal" komen in beeld als je ernaartoe scrollt. */
(function () {
  'use strict';
  var minder = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;

  function zetVerstuurd(d, klaar) {
    var f = d.querySelector('[data-verstuur]');
    var k = d.querySelector('[data-klaar]');
    if (f) f.hidden = klaar;
    if (k) k.hidden = !klaar;
  }

  document.addEventListener('click', function (e) {
    var open = e.target.closest('[data-open]');
    if (open) {
      var d = document.getElementById(open.getAttribute('data-open'));
      if (d && typeof d.showModal === 'function') {
        e.preventDefault();
        zetVerstuurd(d, false);
        if (open.hasAttribute('data-voor')) {
          d.querySelectorAll('[data-voor-naam]').forEach(function (el) { el.textContent = open.getAttribute('data-voor'); });
        }
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
      if (groep) {
        groep.querySelectorAll('button.chip').forEach(function (c) { c.setAttribute('aria-pressed', String(c === chip)); });
      } else {
        chip.setAttribute('aria-pressed', String(chip.getAttribute('aria-pressed') !== 'true'));
      }
      chip.dispatchEvent(new CustomEvent('chip', { bubbles: true }));
    }
  });

  // Klik op de donkere achtergrond sluit de pop-up
  document.querySelectorAll('dialog.modal').forEach(function (d) {
    d.addEventListener('click', function (e) { if (e.target === d) d.close(); });
    d.addEventListener('close', function () { if (d._opener && d._opener.focus) d._opener.focus(); });
    var f = d.querySelector('[data-verstuur]');
    if (f) f.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!f.reportValidity()) return;
      zetVerstuurd(d, true);
      var k = d.querySelector('[data-klaar] h2, [data-klaar] [tabindex]');
      if (k) { k.setAttribute('tabindex', '-1'); k.focus(); }
      d.dispatchEvent(new CustomEvent('verstuurd'));
    });
  });

  // In beeld schuiven
  var items = document.querySelectorAll('.reveal');
  if (minder || !('IntersectionObserver' in window)) {
    items.forEach(function (el) { el.classList.add('is-in'); });
  } else {
    var io = new IntersectionObserver(function (es) {
      es.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('is-in'); io.unobserve(en.target); } });
    }, { rootMargin: '0px 0px -10% 0px' });
    items.forEach(function (el) { io.observe(el); });
  }

  window.deedni = { minderBeweging: minder };
})();
