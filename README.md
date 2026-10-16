# Ztráty a nálezy ČZU

Webová aplikace pro hlášení ztracených a nalezených věcí na kampusu ČZU. Školní projekt předmětu Internetové technologie (PEF ČZU).

- **Nepřihlášený uživatel** prohlíží a filtruje inzeráty, vidí je na mapě kampusu.
- **Přihlášený uživatel** přidává ztráty a nálezy s fotkou a místem na mapě, kontaktuje autora inzerátu a označí věc jako vrácenou.
- **Administrátor** moderuje inzeráty, spravuje uživatele, kategorie a budovy.

## Technologie

PHP 8.1+, MariaDB, Vite (JS a CSS), Leaflet + OpenStreetMap. Bez frameworku a CMS.

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

## Produkční build

```bash
npm run build
```

Na server se nahraje celý projekt kromě `node_modules/`. Document root má mířit na `public/`.

## Struktura

```
docs/        dokumentace projektu (úvodní studie, wireframy, testy)
public/      document root (index.php, build, uploads)
src/         PHP: router, controllery, repozitáře, helpery
templates/   PHP šablony
assets/      zdrojové JS a CSS pro Vite
database/    schema.sql a testovací data
```

## Jak přispět

1. Vezmi si úkol v [GitHub Issues](https://github.com/TomasDevs/ztraty-nalezy-czu/issues).
2. Vytvoř větev, např. `feature/login`.
3. Pošli Pull Request do `main`.
