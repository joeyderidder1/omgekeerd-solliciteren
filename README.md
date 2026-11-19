# deedni

deedni is een platform waar bedrijven zelf hun nieuwe medewerkers kiezen. Kandidaten maken een anoniem profiel en werkgevers zoeken daarin en sturen een aanbod.

## Wat staat waar

- **homepage/**: het ontwerp van de website als losse HTML-pagina's, die je direct in je browser kunt openen.
  - `index.html`: de homepage voor bezoekers (de nieuwste versie).
  - `inloggen.html`: inloggen of een account maken, als marketeer of werkgever.
  - `kandidaat-overzicht.html` en `mijn-profiel.html`: de pagina's voor een ingelogde marketeer.
  - `werkgever-overzicht.html`, `kandidaten-zoeken.html` en `kandidaat.html`: de pagina's voor een ingelogde werkgever.
  - `profiel.html`: het aanmaken van een kandidaatprofiel (de eerste keer).
  - `prototype.html`: een klikbaar prototype van de hele site (oudere opzet).
  - `index-v2.html` t/m `index-v9.html`: eerdere versies van de homepage, ter vergelijking.
  - `assets/menu.js` en `assets/menu.css`: het vaste menu per rol (bezoeker, marketeer, werkgever). Pas je hier een menu aan, dan verandert het op alle pagina's van die rol.
  - `assets/deedni.css` en `assets/deedni.js`: de gedeelde huisstijl, knoppen en pop-ups.
  - `images/`: de foto's die de pagina's gebruiken.
- **wordpress/**: de echte website, gebouwd voor WordPress.
  - `thema/deedni/`: het WordPress-thema (de vormgeving).
  - `plugin/deedni-core/`: de WordPress-plugin (rollen, inloggen, profielen, zoeken, matchpercentage, aanbiedingen, e-mails en alle bewerkbare blokken).
  - `deedni-core.zip` en `deedni-thema.zip`: dezelfde twee onderdelen, klaar om te uploaden in WordPress.
  - `INSTALLATIE.md`: stap voor stap uitleg om het thema en de plugin te installeren.

## Live voorbeeld

De pagina's in `homepage/` staan automatisch online op
https://joeyderidder1.github.io/omgekeerd-solliciteren/ (de homepage) en
https://joeyderidder1.github.io/omgekeerd-solliciteren/overzicht.html (alle pagina's op een rij).

Elke wijziging die op de branch `main` komt, staat daar binnen een paar minuten. Dit regelt
`.github/workflows/live-voorbeeld.yml`. Het voorbeeld staat los van joey-ridder.nl en van WordPress.

## Installeren in WordPress

Upload `wordpress/deedni-core.zip` bij Plugins en `wordpress/deedni-thema.zip` bij Thema's. Je hebt geen betaalde plugins nodig. De stappen en uitleg over het aanpassen van pagina's staan in `wordpress/INSTALLATIE.md`.
