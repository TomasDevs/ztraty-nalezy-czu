# Ztráty a nálezy ČZU

Webová aplikace pro hlášení ztracených a nalezených věcí na kampusu ČZU. Školní projekt předmětu Internetové technologie (PEF ČZU).

- **Nepřihlášený uživatel** prohlíží a filtruje inzeráty, vidí je na mapě kampusu.
- **Přihlášený uživatel** přidává ztráty a nálezy s fotkou a místem na mapě, kontaktuje autora inzerátu a označí věc jako vrácenou.
- **Administrátor** moderuje inzeráty, spravuje uživatele, kategorie a budovy.

## Technologie

PHP 8.1+, MariaDB, Vite (JS a CSS), Leaflet + OpenStreetMap. Bez frameworku a CMS.

## Spuštění lokálně

Potřebuješ PHP 8.1+ a Node.js 20+.

```bash
cp .env.example .env
npm install

npm run dev     # terminál 1: Vite dev server (assety, hot reload)
npm run serve   # terminál 2: PHP server
```

Web běží na http://localhost:8000. Zatím používá testovací data z `database/mock/`.

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
