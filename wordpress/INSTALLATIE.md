# deedni in WordPress: installatie en aanpassen

Je krijgt twee zip-bestanden:

- **deedni-core.zip** is de plugin. Die regelt alles wat "doet": rollen, inloggen en account maken, profielen, zoeken, het matchpercentage, aanbiedingen, "Ja, maar", resultaten en e-mails. Ook alle blokken waarmee je de pagina's opbouwt zitten hierin.
- **deedni-thema.zip** is het thema. Het zorgt voor de vormgeving: kleuren, lettertype, de kop met menu en de voet.

Je hebt **geen betaalde plugins** nodig. ACF is ook niet meer nodig.

## Wat je nodig hebt

- WordPress 6.6 of nieuwer.
- PHP 8.0 of nieuwer.
- Een gratis SMTP-plugin, bijvoorbeeld *WP Mail SMTP*. Zonder zo'n plugin komen e-mails vaak niet aan.

## Installeren (nieuwe site)

1. Ga naar **Plugins > Nieuwe plugin > Plugin uploaden**. Kies `deedni-core.zip` en klik op *Activeren*.
   De plugin maakt dan deze pagina's aan: Inloggen, Account maken, Overzicht, Mijn profiel en Kandidaten zoeken.
2. Ga naar **Weergave > Thema's > Nieuw thema > Thema uploaden**. Kies `deedni-thema.zip` en klik op *Activeren*.
   Het thema zet dan de menu's klaar en vult de homepage met alle secties uit het ontwerp.
   Het thema zet de pagina *Home* ook meteen als homepage (dat zie je bij **Instellingen > Lezen**).
3. Ga naar **Instellingen > Permalinks**, kies *Berichtnaam* en klik op *Opslaan*.
4. Ga naar **Instellingen > deedni** en kijk de instellingen na (zie hieronder).

**Via FTP?** Pak de zips uit. Zet de map `deedni-core` in `wp-content/plugins/` en de map `deedni` in `wp-content/themes/`. Activeer ze daarna in wp-admin.

## Overstappen vanaf de oude versie (v1 met ACF)

Doe dit eerst op een testkopie van de site, of maak een volledige back-up.

1. Upload de nieuwe `deedni-core.zip` en kies *Vervangen door geüploade versie*.
2. Upload de nieuwe `deedni-thema.zip` en kies ook *Vervangen*.
3. Ga naar **Instellingen > deedni** en klik op *Ontbrekende pagina's aanmaken* en *Ontbrekende menu's aanmaken*. Bestaande pagina's die alleen een oude shortcode hadden, worden automatisch omgezet naar de nieuwe blokken.
4. Klik daar ook op *Homepage opnieuw vullen*. Je oude homepage wordt dan vervangen door de nieuwe homepage met alle secties. De oude versie blijft bewaard als revisie.
5. Werkt alles, dan kun je ACF uitzetten.

Wat blijft er bewaard: gebruikers, rollen, kandidaatprofielen, bedrijven en aanbiedingen met hun status. De oude shortcodes blijven ook werken. De blokken die je in de oude versie met ACF had ingevuld, komen niet vanzelf over. Die vul je opnieuw in met de nieuwe blokken.

## Pagina's aanpassen

Elke pagina bestaat uit **blokken**. Elk deel van het ontwerp is een eigen blok, bijvoorbeeld "Homepage: hero", "Homepage: prijzen", "Overzicht (aanbiedingen)" of "Kandidaten zoeken".

1. Ga naar **Pagina's** en open de pagina, bijvoorbeeld *Home*.
2. Klik op een blok. Rechts verschijnen alle velden van dat blok: teksten, knoppen met links, afbeeldingen en lijstjes (zoals de vragen of de prijzen).
3. Pas aan wat je wilt. Je ziet het blok meteen veranderen.
4. Klik op *Bijwerken*.

Handig om te weten:

- **Volgorde veranderen** doe je met de pijltjes boven een blok, of door te slepen.
- **Een blok weghalen** kan via de drie puntjes boven het blok. Je kunt het later weer toevoegen via de **+**.
- **Wie ziet deze sectie?** Per blok kies je of iedereen het ziet, of alleen bezoekers, marketeers of werkgevers.
- **Achtergrond** kies je per blok (standaard, zacht, accentkleur of donker).
- **Ruimte boven dit blok** en **Ruimte onder dit blok** (onder *Opmaak*): kies *Geen*, *Klein*, *Middel*, *Groot* of *Extra groot* voor meer of minder witruimte tussen blokken. *Standaard* houdt de ruimte uit het ontwerp.
- **Voorbeeld in de editor bekijken als**: veel blokken tonen iets anders voor een bezoeker, een marketeer of een werkgever. Met dit veld zie je in de editor hoe het blok eruitziet voor elke rol. Op de site zelf ziet iedereen gewoon zijn eigen versie.
- Je kunt ook gewone WordPress-blokken (tekst, afbeelding, knoppen) tussen de deedni-blokken zetten.
- Kandidaatprofielen gebruiken het blok *Kandidaatprofiel*. Dat pas je aan via **Weergave > Editor > Templates**, bij het template voor één kandidaat.

## Menu's aanpassen

Ga naar **Weergave > Menu's**. Er is een menu per rol:

| Menu | Wie ziet het |
|---|---|
| Menu bezoeker | Iedereen die niet is ingelogd |
| Menu marketeer | Ingelogde marketeers |
| Menu werkgever | Ingelogde werkgevers |
| Accountmenu marketeer | Het menuutje onder de initialen van een marketeer |
| Accountmenu werkgever | Het menuutje onder de initialen van een werkgever |
| Links onderaan | De voet van elke pagina |

De knoppen in de kop (zoals "Inloggen" en "Account maken") en de teksten in de voet pas je aan door in een pagina op de kop of de voet te klikken. Je kunt ook gaan naar **Weergave > Editor > Patronen**, onder *Template-onderdelen*: *Kop met menu* en *Voet*.

## Kleuren en lettertype

Ga naar **Weergave > Editor > Stijlen** (het halve-maan-icoon). Onder *Kleuren* staan alle kleuren van deedni, zoals Achtergrond, Inkt (tekst), Accent (oranje) en Nacht (de donkere vlakken). Verander je een kleur, dan verandert die op de hele site.

### Achtergronden en nieuwe blokken (versie 2.1)

- Elke sectie met een kleurvlak heeft rechts onder **Opmaak > Achtergrond** de keuzes *Standaard*, *Zacht blok*, *Accentkleur*, *Donker blok met gloed*, *Geel blok* en *Licht blok met gloed*. De gloed (zachte ronde kleurverlopen) volgt automatisch je kleuren uit Stijlen.
- De hero is nu een donker vlak met gloed. Bovenaan staat een klein label (veld *Label boven de kop*), en de kleine tekst onder de knop verschijnt als losse labels: scheid ze met een punt (·).
- **Kandidaten in een boog**: anonieme kandidaatkaarten in een boog die meedraait als je scrolt, met kop, tekst en knop in het midden. De kaarten hebben dezelfde stijl als de voorbeeldkaart in de hero, zonder afbeelding. Per kaart vul je code, functie, ervaring, regio, salaris en tools in. Met *Kaarten draaien mee als je scrolt* zet je de beweging aan of uit. Handig op de homepage en op een pagina om kandidaten te ontdekken.
- **Quotes met foto's**: ervaringen met naam, functie en een foto. De foto's liggen als een stapel naast de quote en schuiven mee als je bladert.
- Heb je de homepage al? Dan voeg je deze blokken toe met de **+** in de editor (categorie *deedni*). Of gebruik *Homepage opnieuw vullen* hieronder; dan staan ze er meteen in, maar gaan eigen teksten terug naar de standaard.

## Een kandidaatprofiel aanpassen of verbergen (versie 2.2)

Staat er gevoelige of onjuiste informatie in een profiel? Bijvoorbeeld een telefoonnummer in *Over mij*, of de naam van de huidige werkgever?

1. Ga naar **Kandidaten** en klik bij het profiel op *Profiel aanpassen*.
2. Pas de velden aan in het blok **Profiel aanpassen**. Je kunt ook een werkervaring, project of resultaat weghalen, of het cv verwijderen.
3. Wil je het profiel (tijdelijk) offline halen? Vink rechts bij **Zichtbaarheid** *Verberg dit profiel voor werkgevers* aan. Vul eventueel een reden in: de marketeer ziet die op *Mijn profiel* en het overzicht.
4. Schrijf onderaan bij **Notitie bij deze wijziging** kort wat je aanpaste en waarom. Vink aan of de marketeer een mail krijgt.
5. Klik op **Bijwerken**.

Een verborgen profiel staat niet meer in Ontdekken, bij Kandidaten zoeken of in de beste matches. Werkgevers kunnen de profielpagina niet openen en geen nieuw aanbod sturen. Lopende aanbiedingen blijven bestaan. In de lijst **Kandidaten** zie je *Verborgen door beheer*, en met de snelle link *Verbergen voor werkgevers* of *Weer tonen aan werkgevers* zet je het in één klik om.

Elke wijziging komt rechts in het **Logboek**: wanneer, door wie, welke velden en je notitie.

## Instellingen > deedni

- **Pagina's**: welke pagina het inloggen, het overzicht, het profiel en het zoeken toont.
- **Werkgevers eerst goedkeuren**: staat aan. Een nieuwe werkgever kan pas zoeken als jij het account goedkeurt. Dat doe je bij **Gebruikers**, met de link *Goedkeuren*.
- **Reactietermijn**: hoeveel dagen een marketeer heeft om op een aanbod te reageren.
- **Wie keek mee**: laat marketeers zien hoe vaak hun profiel is bekeken. Dit is optioneel.
- **Standaard opnieuw klaarzetten**: knoppen om ontbrekende pagina's of menu's opnieuw aan te maken. Bestaande pagina's worden niet overschreven. De knop *Homepage opnieuw vullen* zet de homepage wel terug naar de standaardteksten.

## Hoe het werkt

**Rollen**
- *Marketeer* (kandidaat): heeft één anoniem profiel en krijgt aanbiedingen.
- *Werkgever*: heeft een bedrijfsprofiel, zoekt kandidaten en stuurt aanbiedingen.

Beide rollen komen niet in wp-admin. Alles gaat via de pagina's op de site.

**De flow**
1. Een marketeer maakt een account en vult het profiel in vijf stappen in. Daarna zet de marketeer het profiel online.
2. Een werkgever maakt een account en vult het bedrijfsprofiel in, ook "Wie je zoekt". Jij keurt het account goed.
3. De werkgever ziet matches met een matchpercentage, of zoekt zelf met filters. De werkgever bewaart kandidaten en stuurt een aanbod met salaris.
4. De marketeer kiest *Ja*, *Ja, maar* (met een tegenvoorstel) of *Nee*. Bij *Ja, maar* kan de werkgever akkoord gaan, een nieuw voorstel doen of stoppen.
5. Na een ja ziet de werkgever naam en contactgegevens. Daarna kan de werkgever melden dat de kandidaat is aangenomen. Jij krijgt dan een mail.

**Privacy**
- Werkgevers zien een code (zoals K-1010) en geen naam, tot de marketeer ja zegt.
- Kandidaatprofielen staan niet in de zoekfunctie van WordPress, de REST API of de sitemap.
- Een marketeer kan bedrijven blokkeren. Die vinden het profiel dan niet.

## Wat niet getest is

Alles is getest in een lokale WordPress 6.6 met alle rollen: account maken, profiel invullen, zoeken, bewaren, een aanbod sturen, "Ja, maar", akkoord, ja en aangenomen. Wat nog niet getest is:

- **De blok-editor** is in versie 2.2 alleen kort getest: blokken kiezen en het paneel *Opmaak* met de nieuwe ruimte-velden. Probeer grote wijzigingen eerst op een testkopie.
- **E-mails**. Er was geen mailserver. Test dit na het installeren van de SMTP-plugin.
- **Overstappen met de echte gegevens** van joey-ridder.nl. Doe dit eerst op een kopie.
