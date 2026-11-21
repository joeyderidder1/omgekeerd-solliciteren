# Beveiliging van deedni

Gecheckt op 20 november 2026: de code van plugin deedni Core (2.3.0) en thema deedni, en de live site joey-ridder.nl (alleen gelezen, niets aangevallen of aangepast). Aanleiding: de lijst "20 ways someone hacks your vibecoded app". Fixes zitten in plugin **2.4.0**. Alles is getest op een lokale WordPress met testaccounts.

**Kort:** de meeste punten uit de lijst gaan over Next.js, Supabase of AI-apps en gelden niet voor WordPress. Wat wel gold, is gerepareerd. De belangrijkste vondsten stonden niet eens op de lijst: twee datalekken waarbij iedereen zonder account online profielen kon zien. Die staan bovenaan.

## Eerst: twee lekken die echt open stonden

| Lek | Wat er mis was | Live? | Fix in 2.4 |
|---|---|---|---|
| **Zoekresultaten via de REST API** | `/wp-json/wp/v2/pages/<id>` van de pagina *Kandidaten zoeken* toonde aan iedereen, ook zonder account, alle online profielen (code, functie, ervaring, regio, tools, salarisindicatie). Ook de blokkeerlijst werd zo omzeild: een huidige werkgever kon het profiel toch zien. De plugin dacht dat zo'n verzoek "de editor" was en sloeg de toegangscontrole over. Zou iemand het blok *Kandidaatprofiel* op een gewone pagina zetten, dan lekten ook naam, telefoon en e-mail. | Ja, de live site draait 2.2.0 met deze code | De editor-weergave geldt alleen nog voor ingelogde beheerders. Getest: oude code lekte, nieuwe niet. |
| **Kandidatenlijst via de feed** | `/feed/?post_type=kandidaat` en `/?post_type=kandidaat` gaven een lijst met alle profielcodes, met de **voornaam** van de kandidaat als auteur. Op joey-ridder.nl stond zo "K-1065, Joey". | Ja | Kandidaten, bedrijven en aanbiedingen bestaan alleen nog als losse pagina, met toegangscontrole. Lijsten, feeds en archieven geven een 404. |

## De 20 punten uit de lijst

| # | Risico | Geldt voor deedni? | Hoe het was | Wat er nu is |
|---|---|---|---|---|
| 1 | Next.js-header | Nee, dit is WordPress. Wel vergelijkbaar: de site verraadt zijn versie. | `<meta name="generator" content="WordPress 7.1.5">` in elke pagina, plus `readme.html` en `license.txt`. | Generator-tag weg. `readme.html` en `license.txt` moet je zelf verwijderen of laten blokkeren. |
| 2 | `.git`-map bereikbaar | Ja | `/.git/`, `/.env`, `debug.log` geven 403 op de live site. Geen zips of back-ups in de webroot gevonden. | In orde (de hoster blokkeert het). Zet zips nooit in de webroot. |
| 3 | Open storage (Supabase) | Ja, als "de uploadmap" | Cv's stonden in de gewone uploadmap met de originele bestandsnaam (vaak met naam erin), en via de bijlagepagina (`?attachment_id=…`) kwam je bij het bestand uit. | Cv's in een eigen map met een willekeurige naam, bijlagepagina's van cv's geven 404, oude cv-bestanden worden verwijderd bij vervangen. Zelf doen: nginx-regel voor de cv-map. |
| 4 | Prijs uit de browser | Nauwelijks: er wordt niets betaald. | Het salaris in een aanbod komt uit het formulier, maar wordt gecontroleerd (van/tot, 500 tot 50.000). Bij "akkoord" gebruikt de site het opgeslagen tegenvoorstel, niet wat de browser stuurt. | In orde. Extra: een aangepast aanbod moet bij dezelfde kandidaat horen. |
| 5 | Credential stuffing | Ja | Geen enkele limiet op inloggen. XML-RPC stond in WordPress aan (de hoster blokkeert het wel). | Maximaal 20 foute pogingen per IP en 10 per e-mailadres per kwartier, ook op wp-login.php. XML-RPC uit. Zelf doen: tweestapsverificatie voor beheerders. |
| 6 | Open database | Ja, MySQL | Niet van buitenaf te controleren. De plugin gebruikt overal voorbereide queries (geen SQL-injectie gevonden). | Zelf checken bij de hoster: database niet bereikbaar vanaf internet, sterk databasewachtwoord. |
| 7 | Polyfill.io (externe scripts) | Ja | Live geladen: alleen scripts en lettertype van joey-ridder.nl zelf. Geen CDN's. | In orde. |
| 8 | SMS pumping | Nee, er gaat geen sms uit. Wel vergelijkbaar: mail laten versturen naar willekeurige adressen. | *Wachtwoord vergeten* kon onbeperkt mails versturen. | Maximaal 3 mails per uur per adres en 5 aanvragen per uur per IP. |
| 9 | Prompt injection | Nee, er zit geen AI in de site. | – | – |
| 10 | Zwak JWT-secret | Nee, WordPress gebruikt geen JWT. Wel vergelijkbaar: de geheime sleutels in `wp-config.php`. | Niet te zien van buitenaf. | Zelf checken: unieke sleutels in `wp-config.php` (niet "put your unique phrase here"). |
| 11 | Race condition | Ja | Twee keer snel klikken of twee tabbladen gaf twee aanbiedingen aan dezelfde kandidaat, of tegelijk "ja" en "ja, maar". "Aangenomen melden" kon twee mails sturen. | Elke statuswissel gebeurt in één databasebewerking: het tweede verzoek krijgt "je hebt al gereageerd". Nieuw aanbod sturen gebruikt een slot. Getest. |
| 12 | Account pre-hijacking | Ja | Een account werkte direct, zonder e-mailcheck. Iemand kon een account maken op het adres van een ander. | Nieuwe accounts bevestigen eerst hun e-mailadres. Een nieuw wachtwoord kiezen meldt alle andere sessies af en bevestigt het adres. Onbevestigde accounts worden na 7 dagen verwijderd. Getest. |
| 13 | Subdomain takeover | Ja, via DNS | Niet te zien in de code. | Zelf checken: wijst een subdomein van joey-ridder.nl naar een dienst die je niet meer gebruikt (oude hosting, GitHub Pages, Netlify)? Verwijder dat DNS-record. |
| 14 | SSRF | Nee | De plugin haalt nooit zelf een adres op. LinkedIn en website worden alleen opgeslagen en als link getoond. | In orde. Pingbacks staan nu ook uit. |
| 15 | Path traversal | Ja, gecontroleerd | Cv downloaden gaat via een ID met toegangscheck, nooit via een bestandsnaam uit de browser. | In orde. |
| 16 | Reset poisoning | Ja, gecontroleerd | WordPress maakt de resetlink uit de site-instelling, niet uit de Host-header. | In orde. |
| 17 | Source maps | Nee | Geen build-stap, het JavaScript bevat geen sleutels. | In orde. |
| 18 | Clickjacking | Ja | Alleen wp-login.php had X-Frame-Options. De rest van de site kon in een iframe op een andere site. | X-Frame-Options, CSP `frame-ancestors 'self'`, nosniff, HSTS en Referrer-Policy op elke pagina. Getest. |
| 19 | Open redirect | Ja, gecontroleerd | `redirect_to` gaat via `wp_validate_redirect`, dus alleen naar de eigen site. | In orde. |
| 20 | User enumeration | Ja | Account maken zei "Er is al een account met dit e-mailadres". wp-login.php en *Wachtwoord vergeten* gaven een andere melding voor een onbekend adres. `/?author=1` stuurde door naar `/author/admin/` en `/wp-json/wp/v2/users` gaf de gebruikerslijst. | Overal dezelfde melding. Account maken zegt altijd "kijk in je mail" (wie al een account heeft, krijgt een mail met een resetlink). Auteurspagina's en de gebruikerslijst geven 404/401. Getest. |

## Ook nagekeken (in orde)

- **Formulieren**: elk formulier heeft een nonce. Rollen worden bij elke actie gecontroleerd: een kandidaat kan alleen op eigen aanbiedingen reageren, een werkgever alleen eigen aanbiedingen aanpassen. Eén kleinigheid gerepareerd: "Bewaar voor later" accepteerde elk ID, nu alleen kandidaten.
- **Invoer en uitvoer**: alles wordt opgeschoond bij opslaan en ge-escaped bij tonen. De CSV-export van aanmeldingen beschermt tegen formules in Excel.
- **Uploads**: alleen pdf, doc en docx (cv) en jpg, png en webp (logo), met controle van het echte bestandstype en een maximale grootte.
- **Afgeschermde gegevens**: naam, contact, werkgevers, klanten en links staan alleen in de HTML voor wie ze mag zien. Niet verstopt met CSS, echt weggelaten.
- **wp-admin**: kandidaten en werkgevers komen er niet in.

## Wat je zelf moet doen

1. **Update de plugin naar 2.4.0**, zo snel mogelijk vanwege de twee lekken hierboven. Upload `deedni-core.zip` via **Plugins > Nieuwe plugin > Plugin uploaden** en kies *Vervangen*.
2. **Zorg dat de site mail kan versturen** (WP Mail SMTP). Zonder mail kan niemand een account bevestigen. Lukt dat nog niet, zet dan tijdelijk **Instellingen > deedni > E-mailadres bevestigen** uit.
3. **Nginx-regel voor de cv-map** (vraag je hoster): `location ^~ /wp-content/uploads/deedni-cv/ { deny all; }`
4. **Beheerder `admin` vervangen** door een account met een andere naam, sterk wachtwoord en tweestapsverificatie (plugin **Two Factor**).
5. **`readme.html` en `license.txt` verwijderen** uit de hoofdmap.
6. **Even checken bij de hoster**: database niet open naar internet, unieke sleutels in `wp-config.php`, automatische back-ups.
7. **DNS nalopen** op subdomeinen die nergens meer heen wijzen.
8. **Oude cv's** (van voor 2.4) opnieuw laten uploaden of verwijderen bij Media.
9. **De koppeling waarmee Claude de site bekijkt** heeft beheerdersrechten. Is dat een applicatiewachtwoord, trek het dan in als het niet meer nodig is (Gebruikers > Profiel > Applicatiewachtwoorden).

## Formulieren & mail (plugin 3.1.0)

- **Alleen beheerders** (`manage_options`) zien en wijzigen formulieren, mailteksten en de e-maillog. Elk formulier en elke knop (opslaan, testmail, opnieuw versturen, log legen) heeft een nonce.
- **Mailteksten** zijn platte tekst: HTML wordt ge-escaped, alleen `**vet**` en witregels werken. Waarden van variabelen (namen, bedrijfsnamen) worden altijd ge-escaped.
- **E-maillog**: geheime links (bevestigen, wachtwoord, `key=`, `sleutel=`, `token=` enzovoort) worden nooit leesbaar bewaard. Opnieuw versturen van zo'n mail maakt een nieuwe link. De inhoud van mails wordt getoond in een afgeschermd frame (`sandbox`), zodat er geen scripts kunnen draaien. Regels verdwijnen na de bewaartermijn (standaard 30 dagen) en bij het verwijderen van een account.
- **Formulieren**: kernvelden (account, matching, controle) zijn niet te verbergen of optioneel te maken. Verplichte velden en eigen velden worden ook op de server gecontroleerd en opgeschoond. Eigen velden in een kandidaatprofiel zijn nooit zichtbaar voor werkgevers.
