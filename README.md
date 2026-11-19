# deedni

deedni is een platform waar bedrijven zelf hun nieuwe medewerkers kiezen. Kandidaten maken een anoniem profiel en werkgevers zoeken daarin en sturen een aanbod.

## Wat staat waar

- **homepage/**: het ontwerp van de website als losse HTML-pagina's, die je direct in je browser kunt openen.
  - `index.html`: de homepage (de nieuwste versie).
  - `profiel.html`: het aanmaken van een kandidaatprofiel.
  - `prototype.html`: een klikbaar prototype van de hele site.
  - `index-v2.html` t/m `index-v8.html`: eerdere versies van de homepage, ter vergelijking.
  - `images/`: de foto's die de pagina's gebruiken.
- **wordpress/**: de echte website, gebouwd voor WordPress.
  - `thema/deedni/`: het WordPress-thema (de vormgeving).
  - `plugin/deedni-core/`: de WordPress-plugin (profielen, zoeken, aanbiedingen en e-mails).
  - `INSTALLATIE.md`: stap voor stap uitleg om het thema en de plugin te installeren.

## Live voorbeeld

De pagina's in `homepage/` staan automatisch online op
https://joeyderidder1.github.io/omgekeerd-solliciteren/ (de homepage) en
https://joeyderidder1.github.io/omgekeerd-solliciteren/overzicht.html (alle pagina's op een rij).

Elke wijziging die op de branch `main` komt, staat daar binnen een paar minuten. Dit regelt
`.github/workflows/live-voorbeeld.yml`. Het voorbeeld staat los van joey-ridder.nl en van WordPress.

## Installeren in WordPress

WordPress wil het thema en de plugin elk als zip-bestand. Pak daarvoor de map `wordpress/thema/deedni` in als `deedni.zip` en de map `wordpress/plugin/deedni-core` als `deedni-core.zip`, of zet de mappen via FTP direct in `wp-content/themes/` en `wp-content/plugins/`. De rest staat in `wordpress/INSTALLATIE.md`.
