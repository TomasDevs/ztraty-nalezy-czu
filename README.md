# Ztráty a nálezy ČZU

Webová aplikace pro hlášení ztracených a nalezených věcí na kampusu ČZU. Školní projekt předmětu Internetové technologie (PEF ČZU).

- **Nepřihlášený uživatel** prohlíží a filtruje inzeráty, vidí je na mapě kampusu.
- **Přihlášený uživatel** přidává ztráty a nálezy s fotkou a místem na mapě, kontaktuje autora inzerátu a označí věc jako vrácenou.
- **Administrátor** moderuje inzeráty, spravuje uživatele, kategorie a budovy.

## Technologie

PHP 8.1+, MariaDB (záložně JSON soubory, viz níže), Vite (JS a CSS), Leaflet + OpenStreetMap. Bez frameworku a CMS.

## Spuštění lokálně

Je potřeba PHP 8.1+ (s rozšířeními `pdo_mysql`, `gd` s WebP, `mbstring`, `fileinfo`), MariaDB, Composer a Node.js 20+.

```bash
cp .env.example .env    # doplnit DB_USER a DB_PASS
npm install
composer install        # jen kvůli testům
mariadb -e "CREATE DATABASE ztraty_nalezy CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci"
php database/seed.php   # vytvoří tabulky a nahraje testovací data z database/mock/

npm run dev     # terminál 1: Vite dev server (assety, hot reload)
npm run serve   # terminál 2: PHP server
```

Web běží na http://localhost:8000. Seed smaže všechny tabulky a vytvoří je znovu.

## Testy

```bash
mariadb -e "CREATE DATABASE ztraty_nalezy_test CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci"
cp .env.testing.example .env.testing    # doplnit DB_USER a DB_PASS
composer test
```

Testovací databáze se při každém běhu maže. Bez ní se integrační a feature testy přeskočí.

Stejné testy jdou pustit i nad JSON soubory, MariaDB k tomu není potřeba:

```bash
composer test:json
```

## Data v JSON souborech

Jako záloha pro hosting bez přístupu k databázi umí aplikace ukládat data i do JSON souborů v `storage/data/`. Přepíná se v `.env`:

```bash
DATA_DRIVER=json   # mysql (výchozí) = MariaDB
```

Funguje všechno stejně jako s MariaDB, včetně přihlášení a administrace. Ukázková data do JSON souborů nahraje `DATA_DRIVER=json php database/seed.php`.

## První administrátor

Účet se zaregistruje přes web a pak se mu nastaví role. V MariaDB:

```bash
mariadb ztraty_nalezy -e "UPDATE users SET role = 'admin' WHERE email = 'admin@example.com'"
```

S JSON soubory se u daného účtu v `storage/data/users.json` přepíše `"role": "user"` na `"role": "admin"`.

## E-maily

Zprávy z kontaktního formuláře jdou autorovi inzerátu e-mailem. `MAIL_DRIVER=log` (výchozí) je jen zapisuje do `storage/logs/mail.log`, na serveru je potřeba `MAIL_DRIVER=mail` a `MAIL_FROM`.

## Produkční build

```bash
npm run build
```

Na server se nahraje celý projekt kromě `node_modules/`. Document root má mířit na `public/`. Když to hosting nedovolí (školní server), `.htaccess` v kořeni projektu přesměruje všechny požadavky do `public/`, takže zbytek projektu není z webu dostupný.

## Nasazení na školní server

Web běží na https://ete89e.pef.czu.cz/zs2627/07 s databází MariaDB. Údaje k databázi jsou na serveru v souboru `kitlab_db.php` ve složce skupiny (z webu není dostupný).

1. Otevřít spojení se serverem a zadat heslo (vydrží 2 hodiny). V terminálu Warp je potřeba napsat před příkaz `command`, jinak Warp `ssh` přepíše:

```bash
ssh -fNM -o ControlPath=~/.ssh/kitlab.sock -o ControlPersist=2h -p 56789 xlogin@ete89e.pef.czu.cz
```

2. Zkopírovat `.env.server.example` do `.env.server` a doplnit údaje z `kitlab_db.php` (soubor je v `.gitignore`).
3. Nahrát aplikaci:

```bash
scripts/deploy.sh xlogin
```

Skript nahrává commitnutý kód (`git archive`), sestaví assety s cestou `/zs2627/07` a vytvoří `.env` pro server. Data v databázi nemění.

Prázdnou databázi naplní import `database/seed.sql` (tabulky s ukázkovými daty, generuje ho `php database/export-sql.php`), třeba v [Admineru](https://ete89e.pef.czu.cz/adminer.php) s údaji z `kitlab_db.php`. Import smaže všechny tabulky i s daty. Data z JSON souborů převede do SQL příkaz `php database/export-sql.php slozka-s-daty vystup.sql`.

### Bez databáze

Jako záloha jde web nasadit s daty v JSON souborech ve složce `storage/data`: `scripts/deploy.sh xlogin --json`. Přepínač `--with-data` k tomu nahraje ukázková data a smaže na serveru všechny uživatele a inzeráty v JSON souborech.

## Struktura

```
docs/        dokumentace projektu (úvodní studie, wireframy, testy)
public/      document root (index.php, build, uploads)
src/         PHP: router, controllery, formuláře, repozitáře, helpery
tests/       PHPUnit testy
storage/     logy (storage/logs) a JSON data (storage/data)
templates/   PHP šablony
assets/      zdrojové JS a CSS pro Vite
database/    schema.sql a testovací data
```

## Jak přispět

1. Vybrat si úkol v [GitHub Issues](https://github.com/TomasDevs/ztraty-nalezy-czu/issues).
2. Vytvořit větev, např. `feature/login`.
3. Poslat Pull Request do `main`.
