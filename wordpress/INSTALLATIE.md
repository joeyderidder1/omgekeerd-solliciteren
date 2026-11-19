# deedni in WordPress: installatie

Je krijgt twee onderdelen:

- **deedni-core.zip**: de plugin. Hierin zitten de custom post types, de rollen, de ACF-velden, het zoeken voor werkgevers, aanbiedingen en e-mails.
- **deedni-thema.zip**: het thema. Het is gebaseerd op het homepage-ontwerp en geeft alle pagina's dezelfde stijl.

## Wat je nodig hebt

- WordPress 6.3 of nieuwer, met PHP 8.0 of nieuwer.
- **ACF Pro** (6.x). De pagina-blokken gebruiken flexible content, clones, repeaters en een optiepagina, en die zitten alleen in Pro. Heb je geen Pro, dan werkt de gratis plugin **Secure Custom Fields** ook: die heeft dezelfde functies.
- Een SMTP-plugin zoals WP Mail SMTP, zodat de e-mails echt aankomen.

## Installeren in 6 stappen

1. Installeer en activeer **ACF Pro** (of *Secure Custom Fields* via Plugins > Nieuwe plugin).
2. Ga naar **Plugins > Nieuwe plugin > Plugin uploaden**, kies `deedni-core.zip` en activeer de plugin.
   De plugin maakt dan automatisch vijf pagina's aan:

   | Pagina | Adres | Shortcode |
   |---|---|---|
   | Aanmelden | /aanmelden | `[deedni_registreren]` |
   | Inloggen | /inloggen | `[deedni_inloggen]` |
   | Mijn deedni (overzicht) | /mijn-deedni | `[deedni_dashboard]` |
   | Mijn profiel | /mijn-profiel | `[deedni_profiel]` |
   | Kandidaten zoeken | /kandidaten | `[deedni_zoeken]` |

3. Ga naar **Weergave > Thema's > Nieuw thema > Thema uploaden**, kies `deedni-thema.zip` en activeer het thema.
4. Ga naar **Instellingen > Permalinks** en kies *Berichtnaam*. Klik op Opslaan, ook als die optie al aan stond.
5. Ga naar **Instellingen > Algemeen** en zet de taal van de site op *Nederlands*. Datums en de teksten van ACF worden dan ook Nederlands.
**Via FTP?** Pak de zips uit en zet de map `deedni-core` in `wp-content/plugins/` en de map `deedni` in `wp-content/themes/`. Activeer ze daarna in wp-admin. Bestaan de mappen al, overschrijf ze dan; je ingevulde blokken staan in de database en blijven bewaard.

6. Maak onder **Instellingen > Privacy** een privacyverklaring. Het aanmeldformulier linkt daar automatisch naar.

## Hoe het werkt

**Rollen**
- *Kandidaat*: een marketeer. Heeft één anoniem profiel en ontvangt aanbiedingen.
- *Werkgever*: een bedrijf. Heeft een bedrijfsprofiel en kan zoeken en aanbiedingen sturen.

Beide rollen komen nooit in wp-admin. Alles gaat via de pagina's op de site.

**Custom post types**
- *Kandidaten* (`kandidaat`): het anonieme profiel. Bedrijven zien een code zoals K-1010, nooit een naam.
- *Bedrijven* (`bedrijf`): bedrijfsnaam, plaats, website, KvK-nummer, omschrijving en logo.
- *Aanbiedingen* (`aanbod`): functie, salaris van en tot, uren, werkplek, bericht en status. De status is *wacht op reactie*, *geaccepteerd*, *afgewezen* of *aangenomen*.

**Taxonomieën** (voor de filters): *Specialismen*, *Tools en vaardigheden* en *Provincies*. Bij activeren worden ze gevuld met de specialismen uit het ontwerp, 21 tools en de 12 provincies. Je kunt ze aanvullen in wp-admin onder Kandidaten.

**De flow**
1. Een marketeer meldt zich aan en vult het profiel in. Dat is een ACF-formulier met twee tabbladen: het anonieme deel en het deel *Pas zichtbaar na jouw ja* (naam, telefoon, LinkedIn, huidige werkgever en cv).
2. Een werkgever meldt zich aan. Jij krijgt een mail en keurt het account goed bij **Gebruikers** (link *Goedkeuren* in de kolom deedni). Wil je dat niet, zet het dan uit onder **Instellingen > deedni**.
3. De werkgever zoekt en filtert op zoekwoord, specialisme, provincie, werkplek, ervaring, beschikbaarheid, salariswens, tools en *alleen actief op zoek*. Daarna stuurt de werkgever een aanbod met salaris. Een salaris is verplicht.
4. De kandidaat krijgt een mail en kiest *Interesse* of *Nee, bedankt*.
5. Bij een ja ziet de werkgever naam, e-mail, telefoon, LinkedIn, huidige werkgever en het cv. Het cv gaat via een beveiligde downloadlink. De werkgever kan daarna *Kandidaat aangenomen* melden, en jij krijgt een mail om te factureren.

**Privacy**
- Kandidaatprofielen staan niet in de REST API, de zoekfunctie van WordPress of de sitemap. Ze hebben ook een noindex-tag.
- Alleen goedgekeurde werkgevers, beheerders en de kandidaat zelf kunnen een profiel openen.
- Een kandidaat kan e-maildomeinen blokkeren, bijvoorbeeld van de huidige werkgever. Werkgevers met dat domein vinden het profiel niet en kunnen het ook niet openen.
- Er zijn geen velden voor leeftijd, geslacht of foto.

## ACF-velden aanpassen

De veldgroepen staan als ACF JSON in `deedni-core/acf-json/` en laden automatisch. Je ziet ze onder **ACF > Veldgroepen** met de optie *Synchroniseren*.

- Wil je velden aanpassen, synchroniseer dan eerst. Daarna bewaart ACF je wijzigingen in de database, of in de map `acf-json` van je thema als die bestaat.
- Laat de **veldnamen** van deze velden staan: `functietitel`, `profiel_zichtbaar`, `salaris_min`, `ervaring_jaren`, `werkvorm`, `beschikbaarheid`, `zoekstatus`, `voornaam`, `achternaam`, `cv`, `bedrijfsnaam`, `plaats` en de velden van het aanbod. Het zoeken en de privacyregels gebruiken die namen.
- Nieuwe velden toevoegen kan zonder problemen. Ze komen vanzelf in het profielformulier. Wil je ze ook op het profiel laten zien, pas dan het template aan (zie hieronder).

## Pagina's opbouwen met blokken

Elke sectie op een pagina is een ACF-blok, ook op de homepage. Je bouwt een pagina op met **Nieuwe rij** en sleept rijen in de volgorde die je wilt. De WordPress-editor is voor pagina's uitgezet, alles gaat via de velden.

**Veldgroepen** (staan als ACF JSON in `deedni/acf-json/` van het thema, zie ACF > Veldgroepen > *Synchroniseren*):

| Veldgroep | Waar |
|---|---|
| Template: Default pagina | Alle pagina's behalve de homepage. Tabblad *Banner* (boventitel, titel, tekst, 2 buttons, afbeelding, USP's aan/uit) en tabblad *Blokken*. |
| Template: Homepage | De pagina die als homepage is ingesteld. Alleen blokken, de hero is zelf een blok. |
| Blokken | De flexible content met alle blokken. Inactief, wordt in beide templates gecloned. |
| Block: … (12 stuks) | Eén groep per blok, inactief, gecloned in *Blokken*. Pas je een blok aan, dan verandert het overal. |
| Stijl | Optiepagina *Thema-instellingen*, accordion *Kleuren* (zie hieronder). |
| Thema-instellingen | Zelfde optiepagina: de USP's in de banner en de footertekst. |

**De blokken**: Hero (met keuze marketeer/werkgever en voorbeeldkaart), Chips, Stappen, Kolommen, Vergelijking, Prijzen (met rekenvoorbeeld), FAQ, Aanmelden, Content, Afbeelding + content, Call to action en **Onderdeel**. Elk blok heeft een tabblad *Instellingen* met achtergrond (standaard of wit) en een anker, zodat menu-links als `/#prijzen` werken.

**Homepage**: bij het activeren van het thema maakt het thema een pagina *Home* aan, zet die als homepage en vult hem met alle secties uit het ontwerp. Is de homepage leeg, dan zie je in wp-admin een knop *Homepage vullen met de standaardblokken*.

**Pagina's van de plugin** (aanmelden, inloggen, mijn deedni, profiel, kandidaten): zolang je daar geen blokken toevoegt, toont het thema gewoon de shortcode. Wil je er blokken omheen, voeg dan het blok *Onderdeel* toe en kies welk formulier of overzicht erin moet.

**Nieuw blok maken**: maak een veldgroep *Block: Naam* (inactief), voeg in *Blokken* een layout toe met een clone (naadloos) van die groep, en maak een bestand `template-parts/blocks/naam.php` in het thema. De layoutnaam bepaalt de bestandsnaam (underscores worden streepjes).

**Kleuren** stel je in bij **Thema-instellingen > Stijl > Kleuren**:

- *Primair* (kleur + tekst): accentkleur van knoppen, gekleurde woorden in titels, labels en de voorbeeldkaart.
- *Secundair*: achtergrond van de pagina en de gewone tekstkleur. Lijnen en grijze teksten worden daar automatisch van afgeleid.
- *Tertiair*: donkere vlakken en donkere knoppen.
- *Licht*: witte vlakken zoals kaarten en formulieren.
- *Rood*: foutmeldingen. *Groen*: meldingen dat iets gelukt is en de vinkjes op donkere vlakken.
- *Donkere modus*: aan = bezoekers met donkere modus zien een donkere versie, waarin alleen de primaire kleur meegaat. Uit = iedereen ziet precies jouw kleuren.

Het thema zet de kleuren om in CSS-variabelen. Is een kleur te licht om als tekst leesbaar te zijn (bijvoorbeeld geel op lichtgrijs), dan maakt het thema hem voor tekst iets donkerder. Knoppen en vlakken houden de kleur die je kiest.

In teksten van titels kun je `*woord*` gebruiken voor de accentkleur en `**woord**` voor vet.

## Templates aanpassen

Alle HTML van de plugin staat in `deedni-core/templates/`. Wil je iets anders, kopieer het bestand dan naar een map `deedni/` in je thema en pas het daar aan. Een update van de plugin overschrijft je wijziging dan niet.
Voorbeeld: `wp-content/themes/deedni/deedni/kandidaat-kaart.php`.

Menu's kun je instellen onder **Weergave > Menu's** (Hoofdmenu en Footer). Zonder menu toont het thema de links uit het ontwerp.

## Getest

Getest op WordPress 6.8.3 met PHP 8.3. De blokken zijn getest met Secure Custom Fields (ACF Pro kon ik hier niet installeren); het formaat van de velden is gelijk aan ACF Pro. De rest is getest met ACF 6.8, op desktop en mobiel. Deze flow werkt van begin tot eind:

- aanmelden met foutmeldingen en profiel invullen, inclusief tools, provincie en cv-upload
- een werkgever die op goedkeuring wacht, wordt goedgekeurd en daarna zoekt en filtert
- een aanbod versturen, met controle op ontbrekende velden
- de kandidaat accepteert
- de werkgever ziet contactgegevens, downloadt het cv en meldt de aanname

Een geblokkeerde werkgever vindt het profiel niet en krijgt geen toegang tot het profiel of het cv.

## Nog niet ingebouwd

Deze onderdelen kun je later toevoegen:

- **Betalen.** De prijzen op de homepage (€195 per contact, abonnement of 10% bij aanname) zijn nog niet gekoppeld. Een aanname wordt wel geregistreerd en jij krijgt een mail.
- **Limieten per abonnement.** Bijvoorbeeld maximaal 5 open aanbiedingen.
- **Meer gebruikers per bedrijf**, en gedeelde shortlists.
- **Chat** tussen bedrijf en kandidaat. Nu verloopt het contact na een ja via e-mail of telefoon.
- **Account verwijderen door de gebruiker zelf.** Voor de AVG kan dat nu via wp-admin, bij Gebruikers.
- **Cv-bestanden** staan in de gewone uploads-map. De link op de site is beveiligd, maar wie het exacte adres van het bestand kent, kan het openen. Wil je dat helemaal dichtzetten, blokkeer dan de uploads-map voor .pdf/.doc in je serverconfiguratie, of vraag je hostingpartij om hulp.
