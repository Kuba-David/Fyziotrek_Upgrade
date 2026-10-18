# Fyziotrek – nový web

Nová verze webu [fyziotrek.cz](https://fyziotrek.cz), která nahrazuje původní web z Adobe Muse.

- **Veřejný web:** úvod, kurzy jako přehledné kartičky, detail kurzu s programem a soubory ke stažení, online přihláška, kontaktní formulář.
- **Administrace (`/admin`):** přidávání, úpravy, skrývání, kopírování a mazání kurzů, přehled přihlášek se stavy a exportem do Excelu, zprávy z kontaktního formuláře, nastavení kontaktů a storno podmínek, správa uživatelů a obnova zapomenutého hesla.
- **Technologie:** čisté PHP 8.1+ bez frameworku a bez závislostí, databáze SQLite (výchozí) nebo MySQL/MariaDB. Běží na běžném webhostingu WEDOS, nic se nesestavuje ani neinstaluje.

## Struktura

```
index.php            vstupní bod (router)
.htaccess            přepis adres + zákaz přístupu do interních složek
app/
  config.sample.php  vzor konfigurace → zkopírovat jako app/config.php
  controllers/       veřejná část (site.php) a administrace (admin.php)
  lib/               databáze, přihlašování, e-maily, nahrávání souborů, sanitizace HTML
  views/             šablony webu a administrace
  schema/            SQL schéma pro SQLite a MySQL (tabulky se vytvoří samy)
assets/              CSS, JS, fonty (hostované lokálně), obrázky, editor Quill
data/                databáze SQLite + kurzy převzaté ze starého webu (data/seed)
uploads/             obrázky a soubory nahrané v administraci
```

## Nasazení na WEDOS

1. **Záloha starého webu.** Přes FTP stáhněte celý obsah složky, ve které teď leží starý web. Kdyby se cokoli pokazilo, stačí ho nahrát zpět.
2. **Verze PHP.** V administraci WEDOS nastavte pro doménu PHP 8.1 nebo novější (doporučeno 8.3).
3. **Smazání starého webu.** Ze složky webu smažte soubory z Muse (`*.html`, `css/`, `scripts/`, `images/`, `assets/` …). Pokud tam `*.html` zůstanou, server je bude dál vydávat a nebudou fungovat přesměrování ze starých adres.
4. **Nahrání nového webu.** Nahrajte přes FTP celý obsah repozitáře kromě složky `.git`. Skryté soubory `.htaccess` (v kořeni a ve složkách `app`, `data`, `uploads`) jsou nutné, některé FTP klienty je ve výchozím stavu nezobrazují.
5. **Konfigurace.** Zkopírujte `app/config.sample.php` jako `app/config.php` a zkontrolujte `base_url` a `mail.from`. Odesílatel e-mailů musí být adresa na doméně fyziotrek.cz.
6. **Práva zápisu.** Složky `data/` a `uploads/` musí být zapisovatelné pro PHP. Na WEDOS bývají, jinak jim nastavte práva 755.
7. **První spuštění.** Otevřete `https://fyziotrek.cz/admin`, vytvořte první účet a nechte zaškrtnuté *Naplnit web kurzy převzatými z původního webu*.
8. **HTTPS.** V administraci WEDOS zapněte pro doménu SSL certifikát a přesměrování na HTTPS.
9. **Test e-mailů.** Odešlete zkušební přihlášku i kontaktní zprávu a ověřte, že dorazily (podívejte se i do spamu). Adresy pro upozornění se nastavují v administraci v sekci *Nastavení*.

### Databáze

Výchozí je **SQLite**: databáze je jeden soubor `data/fyziotrek.sqlite`, který se vytvoří sám. Pokud by hosting SQLite nepodporoval (při otevření webu se objeví chyba o `pdo_sqlite`), použijte **MySQL**:

1. V administraci WEDOS založte databázi MySQL/MariaDB.
2. V `app/config.php` nastavte `'driver' => 'mysql'` a vyplňte `host`, `name`, `user`, `password`.

Tabulky se vytvoří samy při prvním načtení stránky. Obě varianty jsou otestované.

### Zálohy

Data webu tvoří databáze (`data/fyziotrek.sqlite`, případně databáze MySQL) a složka `uploads/`. Občas si je stáhněte přes FTP. WEDOS dělá i vlastní zálohy.

### Přesměrování starých adres

Staré adresy z Muse (např. `/mulligan-koncept.html` nebo `/přihláška---mulligan-koncept.html`) se trvale (301) přesměrují na nové stránky kurzů, takže odkazy z Googlu a Facebooku dál fungují. Seznam starých adres je u každého převzatého kurzu uložený v databázi.

## Lokální vývoj

```bash
cp app/config.sample.php app/config.php   # nastavte 'debug' => true a 'mail' => ['log_only' => true, …]
php -S localhost:8000 index.php
```

S volbou `mail.log_only` se e-maily neodesílají, ale zapisují do `data/mail.log`.

---

## Návod pro správu webu

Do administrace se přihlásíte na **fyziotrek.cz/admin**.

**Přidání kurzu.** *Kurzy → + Přidat kurz.* Vyplňte název, krátký popis na kartičku a termíny. U termínu stačí vybrat datum začátku a konce, text typu „27.–29. 11. 2026“ se doplní sám. Pokud termín ještě není jasný, nechte data prázdná a napište třeba „připravujeme v roce 2027“. Zájemci se pak můžou přihlásit předběžně. Kurz se na webu ukáže, až zaškrtnete *Zobrazit na webu*.

**Úprava kurzu.** Klikněte na název kurzu, upravte, co potřebujete, a dejte *Uložit změny*. Změna je na webu hned.

**Nový termín stejného kurzu.** U kurzu dejte *+ Přidat termín*. Termíny, které už proběhly, se na webu samy přeškrtnou a nedá se na ně přihlásit. Když proběhnou všechny termíny kurzu, kurz se sám přesune do sekce „Kurzy, které už proběhly“.

**Plný kurz.** Odškrtněte *Přijímat přihlášky* (celý kurz) nebo *Na tento termín se dá přihlásit* (jen jeden termín).

**Skrytí vs. smazání.** *Skrýt z webu* kurz jen schová a kdykoli ho zase zveřejníte. *Smazat kurz* je nevratné. Přihlášky na smazaný kurz ale zůstanou zachované.

**Kopie kurzu.** V nabídce **•••** je *Vytvořit kopii*. Hodí se, když chcete vypsat podobný kurz a nevypisovat všechno znovu.

**Pořadí na webu** měníte šipkami ↑ ↓ v seznamu kurzů.

**Přihlášky.** Každá nová přihláška přijde e-mailem a objeví se v sekci *Přihlášky*. Tam jí můžete nastavit stav (nová, potvrzená, zaplacená, náhradník, zrušená) a připsat interní poznámku. Tlačítko *Stáhnout pro Excel* vyexportuje přihlášky podle aktuálního filtru.

**Zapomenuté heslo.** Na přihlašovací stránce klikněte na *Zapomněli jste heslo?*. Na e-mail přijde odkaz pro nastavení nového hesla.
