# Ztráty a nálezy ČZU

School project (ITCS, PEF ČZU): lost & found web for the ČZU campus. Roles: guest, user, admin.

## Stack
- PHP 8.1+ without framework or CMS (school server: PHP + MariaDB). Server renders HTML for SEO.
- Vite bundles only JS/CSS from `assets/`. Leaflet + OpenStreetMap for maps.
- MariaDB via PDO repositories in `src/Repositories/` (`ItemRepository` interface). `php database/seed.php` recreates the schema and loads `database/mock/*.json`.
- Services come from `App\Core\App` (db, session, auth, repositories, mailer, photos). `Kernel::handle()` returns a `Response`; `redirect()` and `abort()` throw, never `exit`.

## Structure
- `public/` document root: `index.php` front controller, `build/` Vite output
- `src/` app code (`App\` namespace, own autoloader), `src/routes.php`, `src/helpers.php`
- `templates/` plain PHP templates; `partials/item-card.php` is shared by pages and `/api/items`
- `tests/` PHPUnit: `Unit` (no DB), `Integration` (repositories), `Feature` (whole requests via `FeatureTestCase`)
- `docs/` project documentation (Czech)

## Commands
- `npm run dev` + `npm run serve` (two terminals) → http://localhost:8000
- `npm run build` → production assets in `public/build`
- `composer test` → PHPUnit (needs the `ztraty_nalezy_test` DB, see README)

## Conventions
- Test first: write the failing test, then the code. Forms in `src/Forms/` validate input, controllers stay thin.
- Every POST form includes `csrf_field()`; admin and account pages are `noindex`.
- UI texts and docs in Czech; code, comments and commits in English.
- Commits: short, imperative, English (e.g. `Add item filter API`).
- Comments brief and only where the code isn't self-explanatory. Clean code, DRY.
- Escape all output with `e()`, build links with `url()` (respects `BASE_PATH`).
- Pages must work without JS; JS only enhances (AJAX filtering, maps).
- Keep PHP compatible with 8.1 (server version unknown).
- SEO per page via view data: `title`, `description`, `canonical`, `noindex`, `image`, `breadcrumbs`, `jsonLd` (see `templates/layout.php`). Listing rules live in `App\Seo\ListingMeta`.
- Search is diacritics-insensitive via `fold()`; the future PDO repository must match it (e.g. a folded search column, `utf8mb4_czech_ci` treats č ≠ c).
