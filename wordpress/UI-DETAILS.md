# UI-details en typografie (plugin 2.5.0, thema 2.2.0)

Twee lijstjes met tips ("20 UI tips" en "10 typography hacks"), per punt beoordeeld voor deedni. Veel tips komen uit apps voor iPhone en Android. Op een WordPress-site werkt een deel daarvan niet, of het levert te weinig op. **Ja** = gebouwd, **deels** = de webversie of een kleinere versie gebouwd, **nee** = bewust niet gedaan.

Alles werkt ook zonder JavaScript en in oudere browsers: die krijgen de site zoals hij was. Wie op zijn telefoon of computer "minder beweging" aan heeft, krijgt geen animaties en overgangen. De bestaande knoppen (ook de CTA-knop) en de bewerkbaarheid van de blokken in de editor zijn niet veranderd.

## 20 UI-tips

| # | Tip | Oordeel | Wat het nu doet |
|---|-----|---------|-----------------|
| 1 | Skeleton loaders | Ja | Bij *Kandidaten zoeken* verschijnen grijze kaarten in de vorm van de resultaten als laden langer dan 0,2 seconde duurt. Ze glanzen zacht, behalve bij "minder beweging". |
| 2 | Optimistic updates | Ja | *Bewaren* (het bladwijzertje) wisselt meteen, en de server volgt op de achtergrond. Lukt het niet, dan springt de knop terug met een melding. Bij de beste matches op de homepage gaat *Bewaren* en *Niet nu* meteen door naar de volgende kaart. |
| 3 | Command menu | Deels | **Ctrl K** (Mac: **⌘K**) opent *Snel naar*: typ en spring naar elke pagina uit je menu. Werkgevers kunnen er meteen kandidaten mee zoeken. Alleen voor ingelogde gebruikers, want bezoekers hebben maar een paar links. |
| 4 | Shortcut hints | Ja | De sneltoetsen staan erbij, alleen als je een muis en toetsenbord hebt: *Snel naar… Ctrl K* in het accountmenu, **/** in het zoekveld (springt naar zoeken) en **Ctrl Z** in de melding na het weghalen. Een Mac ziet ⌘. |
| 5 | Undo toasts | Ja | Na *Niet meer bewaard*, *Niet nu*, en na het weghalen van een werkervaring, project, resultaat of naam op de blokkeerlijst verschijnt een melding met **Ongedaan maken** (ook met Ctrl Z). Met de muis erop blijft de melding staan. |
| 6 | Stale-while-revalidate | Deels | Bij zoeken staat een zoekopdracht die je eerder deed (een filter aan en weer uit, terug naar pagina 1) meteen in beeld. Op de achtergrond komt de verse versie, die alleen neergezet wordt als er iets veranderd is. Pagina's met persoonlijke gegevens bewaren we niet. |
| 7 | Pre-rendering | Deels | Vanaf WordPress 6.8: voor bezoekers bouwt de browser de volgende pagina alvast als je met de muis op een link blijft staan, dus een klik opent meteen. Voor ingelogde gebruikers blijft het bij de veilige standaard van WordPress. Kandidaatprofielen worden nooit vooraf geladen, en een vooraf geladen pagina telt nooit als *bekeken*. |
| 8 | BlurHash placeholders | Deels | Echte BlurHash moet bij elke upload een code berekenen, en dat is te veel voor een paar foto's. Foto's tonen nu een zacht kleurvlak in de kleuren van de site en komen wazig-naar-scherp in beeld als ze geladen zijn. |
| 9 | View transitions | Ja | Tussen pagina's gaat een zachte overgang terwijl de kop blijft staan (Chrome, Edge, Safari 18.2+). Nieuwe zoekresultaten schuiven ook rustig in beeld. Andere browsers wisselen gewoon direct. |
| 10 | Swipe-to-dismiss sheets | Ja | Op de telefoon staan pop-ups (zoals *Aanbod sturen*) als een blad onderaan, met een greepje bovenin. Veeg naar beneden en het blad gaat dicht. Laat je te vroeg los, dan veert het terug. |
| 11 | Interruptible animations | Ja | Het accountmenu, de uitklapmenu's, het mobiele menu en de pop-ups openen en sluiten met overgangen in plaats van vaste animaties. Klik je halverwege nog eens, dan draait de beweging vloeiend om. |
| 12 | Predictive back | Deels | Waar de browser het kent (Chrome op Android), sluit de terug-veeg eerst een open menu of pop-up in plaats van de pagina te verlaten. Daarvoor gebruiken we CloseWatcher. Op een computer doet Escape hetzelfde. |
| 13 | Hit slop | Ja | Kleine knoppen (bewaren, sluiten, × op de blokkeerlijst, weghalen) hebben een groter onzichtbaar tikvlak. Op touchscreens zijn links in filters, de voet en kruimelpaden minstens 48 pixels hoog. |
| 14 | Safe area insets | Ja | Het thema zet `viewport-fit=cover`. De kop, het mobiele menu, de voet, pop-ups en meldingen blijven weg van de notch, de afgeronde hoeken en de thuisbalk. |
| 15 | One-time code autofill | Nee | deedni gebruikt geen sms-codes. Bevestigen gaat met een link in de mail. Inlog- en aanmeldvelden hadden al de juiste `autocomplete`, dus wachtwoordmanagers vullen ze goed in. |
| 16 | Tabular numbers | Ja | Salarissen, percentages, tellers, statistieken en paginanummers hebben cijfers die allemaal even breed zijn, dus er verspringt niets als een getal verandert. Het lettertype ondersteunt dit. |
| 17 | Balanced headlines | Ja | Alle koppen verdelen hun woorden gelijk over de regels. Lopende tekst krijgt `text-wrap: pretty` (geen los woord op de laatste regel). |
| 18 | Continuous corners | Deels | In browsers die het kennen (nieuwe Chrome) krijgen kaarten, pop-ups, menu's en invoervelden zachte "squircle"-hoeken zoals op de iPhone. Knoppen en ronde labels blijven zoals ze zijn. |
| 19 | Idempotency keys | Ja | Elk formulier krijgt een eenmalige sleutel mee. Komt hetzelfde verzoek twee keer binnen (dubbelklik, een haperende verbinding, opnieuw versturen), dan voert de server het één keer uit en krijgt de tweede keer hetzelfde antwoord. Dit vult de sloten tegen race conditions uit versie 2.4 aan. Gewone formulieren kun je ook maar één keer versturen. Inloggen is uitgezonderd. |
| 20 | Amazon safe triangle | Ja | Het accountmenu en nieuwe uitklapmenu's openen op een computer met hover. Beweeg je schuin van de knop naar een link in het menu, dan blijft het open zolang de muis in de "driehoek" naar het menu toe beweegt. Klikken en de pijltjestoetsen werken ook. **Nieuw:** sleep in *Weergave > Menu's* een link onder een andere, dan wordt dat een uitklapmenu (op de telefoon klapt het in het menu open). |

## 10 typografie-tips

| # | Tip | Oordeel | Wat het nu doet |
|---|-----|---------|-----------------|
| 1 | Reserved bold width | Ja | De actieve stap in *Mijn profiel* is vet, maar de stappen ernaast schuiven niet op: de breedte van de vette tekst is vooraf gereserveerd. |
| 2 | Text-box trim | Deels | In browsers die het kennen staat de tekst in knoppen, labels en tellers optisch precies in het midden. De knoppen zelf worden niet hoger of lager. |
| 3 | Underline offset | Ja | Onderstreepte links hebben de lijn iets lager en dunner, met ruimte voor letters als g en j. Bij hover wordt de lijn iets dikker. |
| 4 | Superscript spacing | Ja | Een ¹ of ² in tekst (bijvoorbeeld op de privacypagina) duwt de regels niet meer uit elkaar. |
| 5 | Fluid type | Deels | De grote koppen schaalden al mee. Nu doen kleinere koppen en de titels op kandidaatkaarten dat ook. |
| 6 | Small caps acronyms | Deels | Het lettertype heeft geen echte kleinkapitalen, en nep-kleinkapitalen van de browser zien er slecht uit. Afkortingen in `<abbr>` worden iets kleiner en ruimer gezet. |
| 7 | Curly quotes | Ja | In het quote-blok en in berichten bij een aanbod worden rechte aanhalingstekens typografisch: "zo" wordt “zo” en 's avonds wordt ’s avonds. Citaten hangen netjes buiten de tekstkolom. |
| 8 | Middle truncation | Ja | Een lange cv-bestandsnaam wordt in het midden ingekort (`cv-sam-jan…2026.pdf`), zodat begin en extensie zichtbaar blijven. De hele naam staat in de tooltip. |
| 9 | Auto hyphenation | Ja | Lange Nederlandse woorden breken af met een koppelteken in plaats van uit het scherm te lopen. Dat geldt voor lopende tekst en op de telefoon ook voor koppen. Zet de sitetaal op Nederlands (Instellingen > Algemeen), dan gebruikt de browser de Nederlandse afbreekregels. |
| 10 | Line-clamped titles | Ja | Functietitels op kandidaatkaarten, matchkaarten en aanbiedingen stoppen na twee regels met "…". De hele titel blijft in de link staan voor schermlezers. |

## Getest

In een lokale WordPress (6.7, SQLite) met Chromium, op een computer (1280 pixels) en een telefoon (Pixel 7):

- Het accountmenu opent met hover en blijft open als je schuin naar de linkerkant van het menu beweegt. Ga je zijwaarts weg, dan sluit het.
- Een submenu werkt met hover op de computer en klapt op de telefoon open in het menu.
- Ctrl K opent *Snel naar*. Typen filtert de lijst, en een werkgever kan meteen zoeken.
- Bewaren wisselt meteen, ook bij een trage server. *Niet meer bewaard* met *Ongedaan maken* werkt, net als *Niet nu* op de homepage met ongedaan maken (blijft zo na het verversen).
- Er verschijnen skeletons bij trage zoekresultaten, en een eerdere zoekopdracht staat meteen in beeld.
- Hetzelfde verzoek twee keer met dezelfde sleutel (eerst *uit*, dan *aan*) geeft twee keer het antwoord van de eerste en wordt maar één keer uitgevoerd.
- Een pop-up op de telefoon sluit als je hem naar beneden veegt.
- De stappen in *Mijn profiel* blijven even breed als de actieve stap vet wordt. Ctrl Z zet een weggehaalde werkervaring terug.
- De viewport-tag heeft `viewport-fit=cover`.

Pre-rendering is niet getest, want daarvoor is WordPress 6.8 nodig. Zonder WordPress 6.8 gebeurt er niets.
