/* deedni: één algemene editor voor alle deedni-blokken.
   De velden komen uit PHP (window.deedniBlokken). Rechts in de zijbalk pas je ze aan,
   in het midden zie je meteen het echte blok (ServerSideRender). Geen bouwstap nodig. */
(function (wp) {
  'use strict';
  if (!wp || !wp.blocks || !window.deedniBlokken) return;
  var el = wp.element.createElement;
  var Fragment = wp.element.Fragment;
  var be = wp.blockEditor;
  var c = wp.components;
  var SSR = wp.serverSideRender;
  var data = window.deedniBlokken;

  var EIGEN = '__eigen__';

  function veldHelp(v) { return v.help || undefined; }

  /** Eén veld in de zijbalk. */
  function Veld(props) {
    var v = props.veld, waarde = props.waarde, zet = props.zet, key = props.naam;
    switch (v.type) {
      case 'textarea':
        return el(c.TextareaControl, { label: v.label, help: veldHelp(v), value: waarde || '', onChange: zet, rows: 4, __nextHasNoMarginBottom: true });
      case 'number':
        return el(c.TextControl, { label: v.label, help: veldHelp(v), type: 'number', value: waarde === undefined ? '' : waarde, onChange: function (x) { zet(x === '' ? 0 : Number(x)); }, __nextHasNoMarginBottom: true });
      case 'toggle':
        return el(c.ToggleControl, { label: v.label, help: veldHelp(v), checked: !!waarde, onChange: zet, __nextHasNoMarginBottom: true });
      case 'keuze':
        return el(c.SelectControl, {
          label: v.label, help: veldHelp(v), value: waarde || '',
          options: Object.keys(v.opties || {}).map(function (k) { return { label: v.opties[k], value: k }; }),
          onChange: zet, __nextHasNoMarginBottom: true
        });
      case 'link':
        return el(LinkVeld, props);
      case 'image':
        return el(BeeldVeld, props);
      case 'lijst':
        return el(LijstVeld, props);
      default:
        return el(c.TextControl, { label: v.label, help: veldHelp(v), value: waarde || '', onChange: zet, __nextHasNoMarginBottom: true });
    }
  }

  function LinkVeld(props) {
    var v = props.veld, waarde = props.waarde || '', zet = props.zet;
    var opties = data.links.slice();
    var bekend = opties.some(function (o) { return o.value === waarde; });
    var keuze = bekend ? waarde : EIGEN;
    opties.push({ label: 'Eigen adres…', value: EIGEN });
    return el('div', { className: 'deedni-veld-link' },
      el(c.SelectControl, {
        label: v.label, help: veldHelp(v), value: keuze, options: opties,
        onChange: function (x) { zet(x === EIGEN ? (bekend ? '' : waarde) : x); }, __nextHasNoMarginBottom: true
      }),
      keuze === EIGEN ? el(c.TextControl, { label: 'Adres', help: 'Een volledig adres (https://…), een pad (/over-ons) of een anker (#prijzen).', value: waarde, onChange: zet, __nextHasNoMarginBottom: true }) : null
    );
  }

  function BeeldVeld(props) {
    var v = props.veld, waarde = props.waarde || {}, zet = props.zet;
    return el(be.MediaUploadCheck, {},
      el('div', { className: 'deedni-veld-beeld' },
        el('p', { className: 'components-base-control__label' }, el('strong', {}, v.label)),
        waarde.url ? el('img', { src: waarde.url, alt: '' }) : null,
        el(be.MediaUpload, {
          allowedTypes: ['image'], value: waarde.id,
          onSelect: function (m) { zet({ id: m.id, url: (m.sizes && m.sizes.large ? m.sizes.large.url : m.url), alt: m.alt || '' }); },
          render: function (o) {
            return el('div', { style: { display: 'flex', gap: '8px', flexWrap: 'wrap' } },
              el(c.Button, { variant: 'secondary', onClick: o.open }, waarde.url ? 'Andere afbeelding' : 'Kies afbeelding'),
              waarde.url ? el(c.Button, { variant: 'tertiary', isDestructive: true, onClick: function () { zet({}); } }, 'Weghalen') : null
            );
          }
        }),
        waarde.url ? el(c.TextControl, { label: 'Beschrijving voor schermlezers (alt-tekst)', value: waarde.alt || '', onChange: function (x) { zet(Object.assign({}, waarde, { alt: x })); }, __nextHasNoMarginBottom: true }) : null,
        v.help ? el('p', { className: 'components-base-control__help' }, v.help) : null
      )
    );
  }

  function LijstVeld(props) {
    var v = props.veld, items = Array.isArray(props.waarde) ? props.waarde : [], zet = props.zet;
    var sub = v.velden || {};
    var eerste = Object.keys(sub)[0];
    function wijzig(i, k, x) { var n = items.slice(); n[i] = Object.assign({}, n[i]); n[i][k] = x; zet(n); }
    function weg(i) { var n = items.slice(); n.splice(i, 1); zet(n); }
    function schuif(i, d) { var n = items.slice(), j = i + d; if (j < 0 || j >= n.length) return; var t = n[i]; n[i] = n[j]; n[j] = t; zet(n); }
    return el('div', { className: 'deedni-veld-lijst' },
      el('p', { className: 'components-base-control__label' }, el('strong', {}, v.label)),
      v.help ? el('p', { className: 'components-base-control__help' }, v.help) : null,
      items.map(function (item, i) {
        var titel = (item && eerste && typeof item[eerste] === 'string' && item[eerste]) ? item[eerste] : 'Item ' + (i + 1);
        return el('div', { className: 'deedni-veld-item', key: i },
          el('div', { className: 'deedni-veld-item-kop' },
            el('span', {}, titel.length > 34 ? titel.slice(0, 34) + '…' : titel),
            el('span', {},
              el(c.Button, { icon: 'arrow-up-alt2', label: 'Omhoog', onClick: function () { schuif(i, -1); }, disabled: i === 0 }),
              el(c.Button, { icon: 'arrow-down-alt2', label: 'Omlaag', onClick: function () { schuif(i, 1); }, disabled: i === items.length - 1 }),
              el(c.Button, { icon: 'trash', label: 'Verwijderen', isDestructive: true, onClick: function () { weg(i); } })
            )
          ),
          Object.keys(sub).map(function (k) {
            return el('div', { key: k, style: { marginBottom: '10px' } },
              el(Veld, { naam: k, veld: sub[k], waarde: item ? item[k] : undefined, zet: function (x) { wijzig(i, k, x); } }));
          })
        );
      }),
      el(c.Button, { variant: 'secondary', onClick: function () { zet(items.concat([JSON.parse(JSON.stringify(v.nieuw || {}))])); } }, '+ Toevoegen')
    );
  }

  data.blokken.forEach(function (b) {
    var groepen = {};
    var volgorde = [];
    Object.keys(b.velden).forEach(function (k) {
      var g = b.velden[k].groep || 'Inhoud';
      if (!groepen[g]) { groepen[g] = []; volgorde.push(g); }
      groepen[g].push(k);
    });
    // Opmaak altijd onderaan
    volgorde.sort(function (x, y) { return (x === 'Opmaak') - (y === 'Opmaak'); });

    wp.blocks.registerBlockType('deedni/' + b.naam, {
      apiVersion: 3,
      title: b.titel,
      description: b.beschrijving,
      category: 'deedni',
      icon: b.icoon,
      edit: function (props) {
        var a = props.attributes;
        var blockProps = be.useBlockProps({ className: 'deedni-blok-editor' });
        var postId = wp.data.select('core/editor') && wp.data.select('core/editor').getCurrentPostId ? wp.data.select('core/editor').getCurrentPostId() : null;
        var panelen = volgorde.map(function (g, i) {
          return el(c.PanelBody, { title: g, initialOpen: i === 0, key: g },
            groepen[g].map(function (k) {
              return el('div', { key: k, style: { marginBottom: '16px' } },
                el(Veld, { naam: k, veld: b.velden[k], waarde: a[k], zet: function (x) { var o = {}; o[k] = x; props.setAttributes(o); } }));
            })
          );
        });
        return el(Fragment, {},
          el(be.InspectorControls, {},
            b.uitleg ? el('div', { className: 'deedni-uitleg' }, b.uitleg) : null,
            panelen
          ),
          el('div', blockProps,
            el('span', { className: 'deedni-blok-label' }, b.titel + ' · klik en pas rechts aan'),
            el(SSR, {
              block: 'deedni/' + b.naam,
              attributes: a,
              httpMethod: 'POST',
              urlQueryArgs: postId ? { post_id: postId } : {},
              LoadingResponsePlaceholder: function () { return el('div', { className: 'deedni-editor-leeg' }, 'Voorbeeld laden…'); },
              EmptyResponsePlaceholder: function () { return el('div', { className: 'deedni-editor-leeg' }, b.titel + ': hier is nu niets te zien.'); },
              ErrorResponsePlaceholder: function () { return el('div', { className: 'deedni-editor-leeg' }, b.titel + ': het voorbeeld lukt niet. Sla op en bekijk de pagina.'); }
            })
          )
        );
      },
      save: function () { return null; }
    });
  });
})(window.wp);
