# Updates via wp-admin

Vanaf plugin **deedni Core 2.6.0** en thema **deedni 2.3.0** hoef je geen zips meer te uploaden. WordPress haalt nieuwe versies zelf op uit GitHub, en je installeert ze onder **Dashboard > Updates** met één klik, net als bij elke andere plugin.

## Eenmalig: de nieuwe versie installeren

De versie die nu op je site staat, weet nog niet dat er updates op GitHub staan. Daarom upload je één keer nog met de hand:

1. Ga naar de releases van de repository: https://github.com/joeyderidder1/omgekeerd-solliciteren/releases
2. Download bij de nieuwste release `deedni-core-2.6.0.zip` en `deedni-thema-2.3.0.zip` (onder *Assets*).
3. In WordPress: **Plugins > Nieuwe plugin > Plugin uploaden**, kies `deedni-core-2.6.0.zip` en klik op **Vervangen door geüpload bestand**.
4. **Weergave > Thema's > Nieuw thema > Thema uploaden**, kies `deedni-thema-2.3.0.zip` en klik weer op **Vervangen**.

Je instellingen, pagina's, profielen en menu's blijven gewoon staan.

## Daarna: updates in wp-admin

Komt er een nieuwe versie, dan zie je die binnen zes uur bij **Dashboard > Updates** en in de lijst bij **Plugins** of **Thema's**. Wil je niet wachten, klik dan op **Opnieuw controleren** bovenaan **Dashboard > Updates**.

Wil je dat updates vanzelf worden geïnstalleerd, klik dan bij **Plugins** naast deedni Core op **Automatische updates inschakelen** (en bij het thema hetzelfde). Advies: laat dat uit zolang deedni in de testfase is, zodat je een update eerst bekijkt.

## Hoe een nieuwe versie ontstaat

Je hoeft hier zelf niets voor te doen:

1. Een wijziging aan de plugin of het thema verhoogt het versienummer (bij de plugin op twee plekken in `deedni-core.php`, bij het thema in `style.css`).
2. Zodra die wijziging op `main` staat (de pull request is samengevoegd), maakt GitHub automatisch een release met de twee zips. Dat doet `.github/workflows/wordpress-release.yml`. Je ziet het onder het tabblad **Actions** van de repository.
3. Zonder hoger versienummer komt er geen release, en dus ook geen update in WordPress.

Maak zelf geen losse releases op GitHub zonder deze zips: WordPress kijkt alleen naar de nieuwste release.

## Is er een toegangstoken nodig?

**Nee.** De repository is openbaar, dus WordPress kan de releases zonder wachtwoord ophalen. (Gecontroleerd op 21 november 2026.)

Maak je de repository later privé, dan stoppen de updates totdat je een token instelt:

1. Maak op GitHub een token: **Settings > Developer settings > Personal access tokens > Fine-grained tokens > Generate new token**.
   - *Repository access*: alleen `omgekeerd-solliciteren`.
   - *Permissions*: alleen **Contents: Read-only**.
   - Kies een vervaldatum (bijvoorbeeld een jaar) en zet een herinnering om hem te vernieuwen.
2. Zet hem in `wp-config.php` op de server, boven de regel `/* That's all, stop editing! */`:

   ```php
   define( 'DEEDNI_GITHUB_TOKEN', 'github_pat_...' );
   ```

Waarom zo: `wp-config.php` staat niet in de database, niet in de code op GitHub en is niet via de browser te openen. Zet het token dus nooit in de plugin, het thema, een instelling in wp-admin of een bericht. Het token wordt alleen meegestuurd naar `api.github.com`, nooit naar de browser of een andere website, en heeft alleen leesrechten op deze ene repository.

## Veiligheid

- WordPress accepteert alleen zips uit de releases van precies deze repository. Een andere downloadlink wordt genegeerd.
- Alleen beheerders kunnen updates installeren. Marketeers en werkgevers komen niet in wp-admin.
- Is GitHub even onbereikbaar, dan gebeurt er niets: je ziet dan gewoon geen update, en wp-admin wordt er niet traag van.
- Updates via GitHub tijdelijk uitzetten kan in `wp-config.php` met `define( 'DEEDNI_UPDATES_UIT', true );`
