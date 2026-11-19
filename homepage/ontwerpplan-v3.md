# deedni homepage, versie 3: ontwerpplan

Dit plan hoort bij [index.html](/mnt/project-files/deedni/homepage/index.html). De vorige versie staat nog in [index-v2.html](/mnt/project-files/deedni/homepage/index-v2.html).

## Wat de pagina moet doen

Eén taak: een online marketeer zet zich op de lijst voor de start van deedni. Werkgevers zijn de tweede doelgroep. Het product is nog niet live, dus de pagina is eerlijk dat het om een wachtlijst gaat.

## Behoeften

| Wie | Wat diegene nodig heeft | Hoe de pagina daarop antwoordt |
| --- | --- | --- |
| Marketeer met baan | Zeker weten dat de huidige werkgever niets ziet | Zwarte balken in het voorbeeldprofiel: je ziet wat verborgen blijft en wat na jouw ja zichtbaar wordt. Blokkeerlijst staat erbij. |
| Marketeer | Weten wat het oplevert | Salaris staat in elk aanbod en is overal geel gemarkeerd |
| Marketeer | Controle | Ja of nee per aanbod, zelf uit te proberen in de hero |
| Werkgever | Bewijs van vakmanschap vóór contact | Anoniem profiel met tools, ervaring en salarisindicatie |
| Werkgever | Geen financieel risico | Betalen bij aanname staat vooraan, met een rekenvoorbeeld naast een bureau |
| Beiden | Vertrouwen in een nieuw platform | Geen verzonnen aantallen of reviews. Duidelijk wat er gebeurt na aanmelden. |

## UX-regels die zijn toegepast

- Eén hoofdactie per scherm, met overal dezelfde naam: "Zet me op de lijst".
- Een knop zegt wat er gebeurt. De vorige versie beloofde "profiel maken", terwijl je op een wachtlijst komt.
- Het systeem toont wat er gebeurt: live rekenvoorbeeld, uitkomst bij elke demo met een "Opnieuw", bevestiging met het ingevulde e-mailadres.
- Herkennen in plaats van onthouden: zichtbare labels, geen placeholder als label, fouten die zeggen hoe je ze herstelt.
- Verborgen tot nodig: vragen in een uitklapper, privacy als schakelaar tussen twee toestanden.
- Mobiel: doelen van minstens 48 px, vergelijkingstabel wordt een gestapelde lijst (geen zijwaarts scrollen), vaste knop onderin zodra de hero uit beeld is.
- Leesbaarheid: 17 tot 18 px lopende tekst, regels van maximaal ongeveer 65 tekens, contrast minimaal 4,5 op 1, zichtbare focusrand, geen beweging bij "minder beweging".

## Ontwerp

Palet
- Inkt `#0F1A3D`, papier `#F5F7FC`, vel `#FFFFFF`
- Ultramarijn `#2842E0` voor acties en links
- Markeerstift `#FFE14D` alleen voor salaris
- Groen `#0B7A4F` voor bevestiging

Type: Schibsted Grotesk in één familie. Het onderscheid komt uit gewicht en maat, niet uit een tweede lettertype.

Idee: de zwarte balk van een geredigeerd document. De balk staat voor "hier ben jij nog anoniem". Hij verdwijnt pas als de marketeer ja zegt. Dat is het enige opvallende element. De rest is rustig, zonder schaduw en zonder decoratie.

Wireframe van de hero

```
deedni▬            Zo werkt het  Privacy  Prijzen  Vragen     [Zet me op de lijst]

(Ik zoek werk | Ik zoek personeel)
Bedrijven komen            +-------------------------------------+
naar jou.                  | Aanbod van Studio Noord   Voorbeeld |
                           | Senior performance marketeer        |
Maak een anoniem profiel.  | [ €4.200 - 4.800 ] per maand        |
...                        | "Je cases passen bij onze klanten"  |
[Zet me op de lijst]       | Studio Noord ziet nu: Naam [██████] |
gratis · anoniem · salaris | [Ja, deel mijn naam]  [Nee, bedankt]|
                           +-------------------------------------+
```

## Toets tegen de opdracht: wat is gewijzigd

Mijn eerste schets (en de vorige versie) had de kenmerken die de skill als standaard markeert:
- warm wit met terracotta accent;
- één accentwoord in de kop ("naar jou");
- een label boven elke kop;
- pijlen in knoppen;
- drie even grote prijskaarten met dezelfde schaduw.

Daarom is dit veranderd:
- koel papier met ultramarijn, plus geel dat één betekenis heeft (salaris);
- de kop zonder accentwoord;
- geen labels boven koppen en geen pijlen in knoppen;
- prijzen als lijst in plaats van drie kaarten;
- schaduw alleen op de voorbeeldkaart in de hero, verschillende hoeken per soort element;
- nummers alleen bij "Zo werkt het", want dat is echt een volgorde.
