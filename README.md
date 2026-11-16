# Ztráty a nálezy ČZU

Webová aplikace pro hlášení ztracených a nalezených věcí na kampusu ČZU. Školní projekt předmětu Internetové technologie (PEF ČZU).

- **Nepřihlášený uživatel** prohlíží a filtruje inzeráty, vidí je na mapě kampusu.
- **Přihlášený uživatel** přidává ztráty a nálezy s fotkou a místem na mapě, kontaktuje autora inzerátu a označí věc jako vrácenou.
- **Administrátor** moderuje inzeráty, spravuje uživatele, kategorie a budovy.

## Technologie

PHP 8.1+, MariaDB (nebo JSON soubory, viz níže), Vite (JS a CSS), Leaflet + OpenStreetMap. Bez frameworku a CMS.

## Spuštění lokálně

Potřebuješ PHP 8.1+ (s rozšířeními `pdo_mysql`, `gd` s WebP, `mbstring`, `fileinfo`), MariaDB, Composer a Node.js 20+.

```bash
cp .env.example .env    # doplň DB_USER a DB_PASS
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
cp .env.testing.example .env.testing    # doplň DB_USER a DB_PASS
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

Zaregistruj se přes web a pak nastav roli. V MariaDB:

```bash
mariadb ztraty_nalezy -e "UPDATE users SET role = 'admin' WHERE email = 'tvuj@email.cz'"
```

S JSON soubory přepiš u svého účtu v `storage/data/users.json` hodnotu `"role": "user"` na `"role": "admin"`.

## E-maily

Zprávy z kontaktního formuláře jdou autorovi inzerátu e-mailem. `MAIL_DRIVER=log` (výchozí) je jen zapisuje do `storage/logs/mail.log`, na serveru nastav `MAIL_DRIVER=mail` a `MAIL_FROM`.

## Produkční build

```bash
npm run build
```

Na server se nahraje celý projekt kromě `node_modules/`. Document root má mířit na `public/`. Když to hosting nedovolí (školní server), `.htaccess` v kořeni projektu přesměruje všechny požadavky do `public/`, takže zbytek projektu není z webu dostupný.

## Nasazení na školní server

Web běží na https://ete89e.pef.czu.cz/zs2627/07 s daty v MariaDB.

1. Zkopíruj `.env.server.example` na `.env.server` a doplň údaje z `kitlab_db.php` (soubor je v `.gitignore`).
2. V [Admineru](https://ete89e.pef.czu.cz/adminer.php) se přihlas stejnými údaji a importuj `database/seed.sql`. Vytvoří tabulky s ukázkovými daty (soubor se generuje příkazem `php database/export-sql.php`).
3. Otevři spojení se serverem a zadej heslo (vydrží 2 hodiny):

```bash
ssh -fNM -o ControlPath=~/.ssh/kitlab.sock -o ControlPersist=2h -p 56789 xlogin@ete89e.pef.czu.cz
```

4. Nahraj aplikaci:

```bash
scripts/deploy.sh xlogin
```

Skript nahrává commitnutý kód (`git archive`), sestaví assety a vytvoří `.env` pro server. Data v databázi nemění.

Bez databáze jde nasadit s JSON soubory: `scripts/deploy.sh xlogin --json --with-data` (poprvé, s ukázkovými daty) a dál `scripts/deploy.sh xlogin --json`.

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

1. Vezmi si úkol v [GitHub Issues](https://github.com/TomasDevs/ztraty-nalezy-czu/issues).
2. Vytvoř větev, např. `feature/login`.
3. Pošli Pull Request do `main`.
