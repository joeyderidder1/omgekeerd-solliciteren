# Is deedni klaar om te testen met echte mensen?

Gecheckt op 20 november 2026, met plugin 2.3.0 en het thema deedni. Ook de live site joey-ridder.nl is bekeken (alleen gelezen, niets aangepast).

**Kort antwoord:** ja, functioneel is alles er. Account maken, profiel invullen, zoeken, matchen, een aanbod sturen, "Ja, maar...", ja en aangenomen werken allemaal en zijn eerder getest. Voordat je echte testers uitnodigt, moeten er wel vier dingen geregeld zijn. Die staan hieronder als *Blokkeert*.

## Blokkeert (eerst regelen)

1. **E-mail werkt nog niet.** Zonder e-mail hoort een marketeer niet dat er een aanbod is, krijgt een werkgever geen mail na goedkeuring en werkt *Wachtwoord vergeten* niet. De e-mails zijn nooit echt verstuurd getest.
   *Oplossing:* installeer de gratis plugin **WP Mail SMTP** en koppel die aan een mailadres van je eigen domein (bijvoorbeeld via je hoster). Stuur met de plugin een testmail. Doorloop daarna één keer zelf: account maken, aanbod sturen, wachtwoord vergeten. Kijk of de mails aankomen en niet in de spam staan.
2. **Er is geen privacyverklaring.** Het vakje bij *Account maken* en bij de vooraanmelding verwijst ernaar, maar de pagina bestaat niet (op joey-ridder.nl geeft `/privacybeleid/` een 404). Je verwerkt cv's, salarisgegevens en namen, dus dit is verplicht onder de AVG.
   *Oplossing:* maak een pagina *Privacyverklaring* (WordPress helpt bij **Instellingen > Privacy**; de plugin zet daar al een stukje tekst over de vooraanmelding klaar). Kies hem daar als privacypagina en zet hem in het menu *Links onderaan*. Zet er ook kort testvoorwaarden bij: dit is een test, er kan iets misgaan, er worden geen kosten gerekend en gegevens worden na de test verwijderd.
3. **Zonder kandidaten valt er voor werkgevers niets te zoeken.** Een werkgever die inlogt op een lege site ziet geen matches, en dan test je niets.
   *Oplossing:* nodig eerst de marketeers uit (via de vooraanmelding), en pas de werkgevers als er zo'n 10 tot 15 profielen online staan. Of maak zelf een paar duidelijk herkenbare testprofielen, met *Test* in de functie.
4. **Opruimen op de live site.** De WordPress-pagina *Voorbeeld-pagina* staat nog online en de site heeft geen titel (de tab in de browser is leeg). Verwijder de voorbeeldpagina en vul een sitetitel in bij **Instellingen > Algemeen**.

## Kan wachten (wel handig)

- **Feedback verzamelen.** Er zit geen feedbackknop in de site. Voor een kleine groep werkt een gratis formulier (Google Forms of Microsoft Forms) met 5 vragen, of een kort gesprek van 15 minuten, het best. Zet de link in de uitnodigingsmail en in het menu *Links onderaan*.
- **Testaccounts.** Maak voor jezelf een marketeer- en een werkgeveraccount (met een ander e-mailadres dan je beheerdersaccount). Dan kun je zien wat testers zien en ze helpen als ze vastlopen.
- **Uitnodigen en toegang.** Met de vooraanmelding zie je wie mee wil doen (zie hieronder). Je mailt hen daarna zelf de link naar *Account maken*, of voor werkgevers `/aanmelden/?rol=werkgever`. Werkgevers keur je nog steeds zelf goed bij **Gebruikers**, dus een onbekend bedrijf kan niet zomaar zoeken. Uitnodigingscodes zijn voor een kleine test niet nodig.
- **Nepdata en echte data.** De homepage toont nu voorbeeldcijfers en voorbeeldprofielen. Die staan als *Voorbeeld* gemarkeerd, dus dat is eerlijk. Zet het blok *Snel zoeken* op *echte aantallen* zodra er genoeg profielen zijn.
- **Beveiliging.** Wat er al is: formulieren met beveiligingscode (nonce), een onzichtbaar veld tegen spam, werkgevers zien geen namen tot een ja, cv's zijn alleen via een beveiligde link te downloaden, profielen staan niet in zoekmachines en de site staat op *noindex*. Voeg nog toe: de gratis plugin **Limit Login Attempts Reloaded** (tegen wachtwoord raden), een sterk wachtwoord voor je beheerdersaccount, en automatische back-ups (bijvoorbeeld de gratis versie van **UpdraftPlus**).
- **Account verwijderen.** Testers kunnen hun account niet zelf verwijderen. Vraagt iemand erom, verwijder dan de gebruiker bij **Gebruikers**. Kies dan *Alle inhoud verwijderen*, dan gaan het profiel en het cv ook weg.
- **Mobiel.** De pagina's zijn eerder getest op telefoonbreedte. Het nieuwe formulier is getest op 390 pixels breed. Vraag testers wel welk toestel ze gebruikten.
- **Cookies.** De site gebruikt geen statistieken of advertentiecookies, en het lettertype staat op je eigen server. Alleen de inlogcookie van WordPress. Een cookiebanner is daarom niet nodig. Voeg je later Google Analytics toe, dan wel.

## Nieuw: vooraanmelding

Een tijdelijk aanmeldformulier op `/vooraanmelden/` (pagina *Doe mee met de test*). Mensen kiezen *Ik zoek werk* of *Ik zoek personeel* en vullen naam, e-mail en optioneel bedrijf en functie in, met een verplicht toestemmingsvakje. Ze maken nog geen account.

In wp-admin onder **Aanmeldingen** zie je de aantallen per groep, vink je per persoon aan of je diegene hebt uitgenodigd, download je alles als CSV en verwijder je na de test alles in één keer. Uitleg staat in `INSTALLATIE.md`.
