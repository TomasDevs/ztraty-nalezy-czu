# Ztráty a nálezy ČZU

School project (ITCS, PEF ČZU): lost & found web for the ČZU campus. Roles: guest, user, admin.

## Stack
- PHP 8.1+ without framework or CMS (school server: PHP + MariaDB). Server renders HTML for SEO.
- Vite bundles only JS/CSS from `assets/`. Leaflet + OpenStreetMap for maps.
- Data from `database/mock/*.json` (`JsonItemRepository`) until the DB is ready, then a PDO repository behind `ItemRepository`.

## Structure
- `public/` document root: `index.php` front controller, `build/` Vite output
- `src/` app code (`App\` namespace, own autoloader), `src/routes.php`, `src/helpers.php`
- `templates/` plain PHP templates; `partials/item-card.php` is shared by pages and `/api/items`
- `docs/` project documentation (Czech)

## Commands
- `npm run dev` + `npm run serve` (two terminals) → http://localhost:8000
- `npm run build` → production assets in `public/build`

## Conventions
- UI texts and docs in Czech; code, comments and commits in English.
- Commits: short, imperative, English (e.g. `Add item filter API`).
- Comments brief and only where the code isn't self-explanatory. Clean code, DRY.
- Escape all output with `e()`, build links with `url()` (respects `BASE_PATH`).
- Pages must work without JS; JS only enhances (AJAX filtering, maps).
- Keep PHP compatible with 8.1 (server version unknown).
