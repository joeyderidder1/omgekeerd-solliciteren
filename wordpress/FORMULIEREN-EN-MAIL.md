# Formulieren en mail beheren in wp-admin

Vanaf plugin **deedni Core 3.1.0** staat er in wp-admin een nieuw menu **Formulieren & mail** met drie pagina's. Alleen beheerders zien het.

## E-maillog

Elke mail die de site verstuurt, ook de mails van WordPress zelf, komt hier te staan met:

- tijd, ontvanger, onderwerp en soort mail (bijvoorbeeld *Nieuw aanbod* of *E-mailadres bevestigen*);
- de status: **Verzonden**, **Mislukt** (met de foutmelding van de mailserver eronder) of **Uitgezet** (de mail staat uit bij Mailteksten);
- de inhoud van de mail, via **Bekijken**.

Heeft iemand een mail niet gekregen? Zoek op het e-mailadres en kijk naar de status:

| Status | Wat betekent het | Wat doe je |
|---|---|---|
| Verzonden | De mailserver heeft de mail aangenomen. | Vraag om in de spam te kijken. Klik daarna op **Opnieuw versturen**. |
| Mislukt | De mailserver weigerde. De reden staat eronder. | Meestal ligt het aan de SMTP-instellingen (zie hieronder). Los dat op en klik op **Opnieuw versturen**. |
| Uitgezet | Je hebt deze mail uitgezet bij Mailteksten. | Zet hem daar weer aan. |
| Staat er niet in | De site heeft niets verstuurd. | Bijvoorbeeld: het account bestaat niet, of de limiet van 3 mails per uur per adres is bereikt. |

**Opnieuw versturen** stuurt precies dezelfde mail nog een keer naar hetzelfde adres. Bij *E-mailadres bevestigen* en *Wachtwoord vergeten* heet de knop **Nieuwe link sturen**: de oude link wordt dan ongeldig en de ontvanger krijgt een nieuwe.

### Privacy

- Geheime links (bevestigen, wachtwoord, en alles met `key=`, `sleutel=`, `token=` en dergelijke) worden nooit leesbaar bewaard. In de log staat `[verborgen]`.
- Regels worden na de **bewaartermijn** automatisch verwijderd (standaard 30 dagen, onderaan de pagina in te stellen).
- Je kunt **Inhoud bewaren** uitzetten. Dan blijven alleen ontvanger, onderwerp, tijd en status staan (en kun je een mail niet meer opnieuw versturen).
- Verwijder je een account, dan verdwijnen de mails aan dat account ook uit de log. De log doet mee met **Extra > Persoonlijke gegevens exporteren/wissen**.

## Mailteksten

Alle mails aan kandidaten, werkgevers en jou, gegroepeerd: Account, Aanbiedingen, Reacties en Naar jou. Klik op een mail om:

- het **onderwerp**, de **tekst** en de **knoptekst** aan te passen;
- **variabelen** in te voegen, zoals `{naam}`, `{bedrijf}`, `{functie}`, `{salaris}`, `{code}` en `{link}` (klik erop om ze in te voegen);
- meteen een **voorbeeld** te zien met voorbeeldwaarden;
- een **testmail naar jezelf** te sturen (ook vóór het opslaan);
- de mail **uit te zetten** (niet bij bevestigen, wachtwoord en "je hebt al een account", want zonder die mails kan niemand inloggen);
- terug te gaan naar de **standaardtekst**.

Opmaak: een witregel begint een nieuwe alinea en `**zo**` wordt vet. HTML werkt bewust niet (dat is veiliger), de huisstijl en de knop komen er vanzelf omheen.

## Formulieren

Per formulier pas je aan:

- **Label**, **hulptekst** en **voorbeeldtekst in het vak** (laat leeg voor de standaard);
- **Verplicht** of niet;
- **Verbergen**;
- de **volgorde** (slepen aan het handvat). Velden die op de site naast elkaar staan, verhuizen samen;
- **eigen velden toevoegen**: tekst, tekstvak, keuzelijst, vinkje, getal, e-mailadres, telefoon of datum.

Formulieren: Account aanmaken (met eigen velden alleen voor kandidaten of alleen voor werkgevers), Profiel van een kandidaat, Bedrijfsprofiel, Aanbod sturen, Reageren met "Ja, maar..." en Vooraanmelding. Een contactformulier heeft de site nog niet.

**Kernvelden** (met een slotje) kun je wel anders noemen, maar niet verbergen of optioneel maken. Ze zijn nodig voor het account, de matching of de controle in de code. Zo kan een aanpassing de site nooit breken.

Waar komen de antwoorden op eigen velden terecht?

- **Aanbod sturen**: bij het aanbod in wp-admin, en in de mail aan de kandidaat (`{extra_velden}`).
- **Ja, maar...**: bij het aanbod in wp-admin, en in de mail aan de werkgever.
- **Account aanmaken**: bij het account (Gebruikers) en bij het profiel, en in de mail "Nieuwe werkgever" aan jou.
- **Profiel en bedrijfsprofiel**: bij het profiel in wp-admin. Werkgevers zien de extra profielvelden van kandidaten niet, zodat het profiel anoniem blijft.

Bij het kandidaatprofiel betekent *verplicht*: nodig om het profiel te **publiceren**. Opslaan kan altijd, zodat niemand ingevuld werk kwijtraakt.

**Standaard herstellen** zet een formulier terug zoals het was. Al ingevulde antwoorden op eigen velden blijven bewaard.

## Waarom geen formulierplugin?

De formulieren van deedni maken accounts, sturen aanbiedingen met beveiliging tegen dubbel versturen, rekenen matches uit en hebben limieten tegen misbruik. Een formulierplugin (Gravity Forms, WPForms) zou al die logica opnieuw moeten krijgen, vaak betaald en met minder grip op de beveiliging. Daarom blijven de formulieren zelf staan en ligt er een instelbare laag overheen.

## Wat je zelf moet doen

1. **Installeer WP Mail SMTP** (gratis, Plugins > Nieuwe plugin) en koppel het aan je mailadres of een dienst zoals Brevo of Mailgun. Zonder SMTP gaat mail via de webserver en komt die vaak in de spam of niet aan. De E-maillog laat een waarschuwing zien zolang er geen SMTP-plugin actief is.
2. Stuur daarna bij Mailteksten een **testmail naar jezelf** en kijk in de E-maillog of hij op *Verzonden* komt.
3. Kies een **bewaartermijn** voor de E-maillog en zet hem in je privacyverklaring ("We bewaren verstuurde e-mails 30 dagen om bezorgproblemen op te lossen").
